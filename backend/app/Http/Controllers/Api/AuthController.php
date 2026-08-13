<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Services\Audit\AuditService;
use App\Services\Ownership\ServiceOwnerRegistrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    public function __construct(
        private AuditService $auditService,
        private ServiceOwnerRegistrationService $serviceOwnerRegistration,
    ) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        abort_unless(config('app.allow_registration'), 403, 'Registration is disabled.');

        $data = $request->validated();
        $registration = $this->serviceOwnerRegistration->register($data);
        $user = $registration['user'];

        $this->auditService->record(
            action: 'auth.register',
            userId: $user->id,
            auditable: $user,
            newValues: [
                'email' => $user->email,
                'name' => $user->name,
                'organization_id' => $registration['organization']->id,
            ],
            request: $request,
        );

        return response()->json([
            ...$this->authenticatedPayload($user, $data['device_name'] ?? 'web'),
            'organization' => $registration['organization']->only(['id', 'name', 'slug']),
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $data = $request->validated();
        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            $this->auditService->record(
                action: 'auth.login_failed',
                newValues: ['email' => $data['email']],
                request: $request,
            );

            throw ValidationException::withMessages(['email' => ['The provided credentials are incorrect.']]);
        }

        if (! $user->organizations()->wherePivot('is_active', true)->exists()
            && $user->organizations()->exists()) {
            throw ValidationException::withMessages([
                'email' => ['Your account is inactive in all organizations. Contact an administrator.'],
            ]);
        }

        $this->auditService->record(
            action: 'auth.login',
            userId: $user->id,
            auditable: $user,
            newValues: ['email' => $user->email, 'device_name' => $data['device_name'] ?? 'web'],
            request: $request,
        );

        return $this->authenticatedResponse($user, $data['device_name'] ?? 'web', $request);
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user !== null) {
            $this->auditService->record(
                action: 'auth.logout',
                userId: $user->id,
                auditable: $user,
                request: $request,
            );
        }

        $token = $user?->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        } elseif ($bearer = $request->bearerToken()) {
            PersonalAccessToken::findToken($bearer)?->delete();
        }

        return response()->json(status: 204);
    }

    public function sessions(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user !== null, 401);

        $currentTokenId = $user->currentAccessToken()?->id;
        if ($currentTokenId === null && ($bearer = $request->bearerToken())) {
            $currentTokenId = PersonalAccessToken::findToken($bearer)?->id;
        }

        $sessions = $user->tokens()
            ->orderByDesc('last_used_at')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (PersonalAccessToken $token) => [
                'id' => $token->id,
                'name' => $token->name,
                'last_used_at' => $token->last_used_at,
                'created_at' => $token->created_at,
                'is_current' => $token->id === $currentTokenId,
            ])
            ->values();

        return response()->json($sessions);
    }

    public function revokeSession(Request $request, int $token): JsonResponse
    {
        $user = $request->user();
        abort_unless($user !== null, 401);

        $accessToken = $user->tokens()->whereKey($token)->first();
        abort_unless($accessToken !== null, 404);

        abort_if(
            $accessToken->id === ($user->currentAccessToken()?->id
                ?? PersonalAccessToken::findToken((string) $request->bearerToken())?->id),
            422,
            'Cannot revoke the active session. Use logout instead.'
        );

        $accessToken->delete();

        $this->auditService->record(
            action: 'auth.session_revoked',
            userId: $user->id,
            auditable: $user,
            newValues: ['token_id' => $token, 'token_name' => $accessToken->name],
            request: $request,
        );

        return response()->json(status: 204);
    }

    private function authenticatedResponse(User $user, string $deviceName, Request $request): JsonResponse
    {
        return response()->json($this->authenticatedPayload($user, $deviceName));
    }

    /** @return array{token: string, user: array<string, mixed>} */
    private function authenticatedPayload(User $user, string $deviceName): array
    {
        return [
            'token' => $user->createToken($deviceName)->plainTextToken,
            'user' => $user->only(['id', 'name', 'email']),
        ];
    }
}
