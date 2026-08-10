<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Services\Audit\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    public function __construct(private AuditService $auditService) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        abort_unless(config('app.allow_registration'), 403, 'Registration is disabled.');

        $data = $request->validated();

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $this->auditService->record(
            action: 'auth.register',
            userId: $user->id,
            auditable: $user,
            newValues: ['email' => $user->email, 'name' => $user->name],
            request: $request,
        );

        return $this->authenticatedResponse($user, $data['device_name'] ?? 'web', $request);
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

    private function authenticatedResponse(User $user, string $deviceName, Request $request): JsonResponse
    {
        return response()->json([
            'token' => $user->createToken($deviceName)->plainTextToken,
            'user' => $user->only(['id', 'name', 'email']),
        ]);
    }
}
