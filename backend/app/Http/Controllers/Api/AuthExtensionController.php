<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserIdentity;
use App\Services\Auth\IdentityService;
use App\Services\Auth\OAuthService;
use App\Services\Auth\PhoneOtpService;
use App\Services\Audit\AuditService;
use App\Services\Ownership\ServiceOwnerRegistrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthExtensionController extends Controller
{
    public function __construct(
        private OAuthService $oauth,
        private PhoneOtpService $phoneOtp,
        private IdentityService $identities,
        private ServiceOwnerRegistrationService $registration,
        private AuditService $audit,
    ) {}

    public function googleRedirect(): JsonResponse
    {
        $result = $this->oauth->googleRedirectUrl();
        if (isset($result['error'])) {
            return response()->json($result, 503);
        }

        return response()->json($result);
    }

    public function googleCallback(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string'],
            'state' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ]);

        $auth = $this->oauth->handleGoogleCallback(
            $data['code'],
            $data['state'],
            $data['device_name'] ?? 'web',
        );

        if ($auth['created']) {
            $this->afterRegistration($auth['user'], $request);
        }

        $this->audit->record('auth.oauth.login', userId: $auth['user']->id, auditable: $auth['user'], newValues: ['provider' => 'google'], request: $request);

        return response()->json([
            'token' => $auth['token'],
            'user' => $auth['user']->only(['id', 'name', 'email']),
            'created' => $auth['created'],
        ]);
    }

    public function facebookRedirect(): JsonResponse
    {
        $result = $this->oauth->facebookRedirectUrl();
        if (isset($result['error'])) {
            return response()->json($result, 503);
        }

        return response()->json($result);
    }

    public function facebookCallback(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string'],
            'state' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ]);

        $auth = $this->oauth->handleFacebookCallback(
            $data['code'],
            $data['state'],
            $data['device_name'] ?? 'web',
        );

        if ($auth['created']) {
            $this->afterRegistration($auth['user'], $request);
        }

        $this->audit->record('auth.oauth.login', userId: $auth['user']->id, auditable: $auth['user'], newValues: ['provider' => 'facebook'], request: $request);

        return response()->json([
            'token' => $auth['token'],
            'user' => $auth['user']->only(['id', 'name', 'email']),
            'created' => $auth['created'],
        ]);
    }

    public function sendPhoneOtp(Request $request): JsonResponse
    {
        $data = $request->validate([
            'phone' => ['required', 'string', 'max:32'],
        ]);

        $result = $this->phoneOtp->sendOtp($data['phone'], $request->user());

        return response()->json($result, $result['sent'] ? 200 : 503);
    }

    public function verifyPhoneOtp(Request $request): JsonResponse
    {
        $data = $request->validate([
            'phone' => ['required', 'string', 'max:32'],
            'code' => ['required', 'string', 'max:10'],
            'name' => ['nullable', 'string', 'max:255'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ]);

        $verification = $this->phoneOtp->verifyOtp($data['phone'], $data['code']);
        $user = $verification->user;

        if ($user === null) {
            abort_unless(config('app.allow_registration'), 403, 'Registration is disabled.');
            $email = Str::slug($data['phone']).'@phone.local';
            $registration = $this->registration->register([
                'name' => $data['name'] ?? 'Phone User',
                'email' => $email,
                'password' => Str::random(32),
            ]);
            $user = $registration['user'];
            $this->afterRegistration($user, $request, $registration['organization']->id);
        }

        $this->identities->link($user, UserIdentity::PROVIDER_PHONE, $data['phone'], null, $data['phone']);

        $token = $user->createToken($data['device_name'] ?? 'web')->plainTextToken;
        $this->audit->record('auth.phone.login', userId: $user->id, auditable: $user, request: $request);

        return response()->json([
            'token' => $token,
            'user' => $user->only(['id', 'name', 'email']),
        ]);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $data = $request->validate(['email' => ['required', 'email']]);

        Password::sendResetLink(['email' => $data['email']]);

        return response()->json(['message' => 'If the email exists, a reset link was sent.']);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $status = Password::reset($data, function (User $user, string $password): void {
            $user->forceFill(['password' => Hash::make($password)])->save();
        });

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => [__($status)]]);
        }

        return response()->json(['message' => 'Password reset successfully.']);
    }

    public function verifyEmail(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user !== null, 401);

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified.']);
        }

        $user->markEmailAsVerified();
        $this->identities->ensureEmailIdentity($user)->update(['verified_at' => now()]);
        $this->audit->record('auth.email.verified', userId: $user->id, auditable: $user, request: $request);

        return response()->json(['message' => 'Email verified.']);
    }

    public function resendVerification(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user !== null, 401);

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified.']);
        }

        $user->sendEmailVerificationNotification();

        return response()->json(['message' => 'Verification notification sent if mail is configured.']);
    }

    public function listIdentities(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user !== null, 401);

        return response()->json(
            $this->identities->listForUser($user)->map(fn ($i) => [
                'id' => $i->id,
                'provider' => $i->provider,
                'email' => $i->email,
                'phone' => $i->phone,
                'verified_at' => $i->verified_at,
            ]),
        );
    }

    public function linkIdentity(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user !== null, 401);

        $data = $request->validate([
            'provider' => ['required', 'string', 'in:google,facebook,phone,email'],
            'provider_id' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string', 'max:32'],
        ]);

        $identity = $this->identities->link(
            $user,
            $data['provider'],
            $data['provider_id'],
            $data['email'] ?? null,
            $data['phone'] ?? null,
        );

        $this->audit->record('auth.identity.linked', userId: $user->id, auditable: $identity, newValues: ['provider' => $data['provider']], request: $request);

        return response()->json($identity->only(['id', 'provider', 'email', 'phone', 'verified_at']), 201);
    }

    public function unlinkIdentity(Request $request, int $identity): JsonResponse
    {
        $user = $request->user();
        abort_unless($user !== null, 401);

        $this->identities->unlink($user, $identity);
        $this->audit->record('auth.identity.unlinked', userId: $user->id, newValues: ['identity_id' => $identity], request: $request);

        return response()->json(status: 204);
    }

    private function afterRegistration(User $user, Request $request, ?int $organizationId = null): void
    {
        $this->identities->ensureEmailIdentity($user);
        $this->audit->record('auth.register', userId: $user->id, auditable: $user, newValues: ['email' => $user->email], request: $request);
    }
}
