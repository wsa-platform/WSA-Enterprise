<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Models\UserIdentity;
use App\Services\Ownership\ServiceOwnerRegistrationService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class OAuthService
{
    public function __construct(
        private IdentityService $identities,
        private ServiceOwnerRegistrationService $registration,
    ) {}

    /** @return array{url: string, state: string}|array{error: string} */
    public function googleRedirectUrl(): array
    {
        if (! $this->googleConfigured()) {
            return ['error' => 'Google OAuth is not connected'];
        }

        $state = Str::random(40);
        cache()->put('oauth:google:'.$state, true, 600);

        $params = http_build_query([
            'client_id' => config('services.google.client_id'),
            'redirect_uri' => config('services.google.redirect'),
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'access_type' => 'online',
            // Official Google account chooser — do not silently pick a stored account.
            'prompt' => 'select_account',
        ]);

        return [
            'url' => 'https://accounts.google.com/o/oauth2/v2/auth?'.$params,
            'state' => $state,
        ];
    }

    /** @return array{user: User, token: string, created: bool} */
    public function handleGoogleCallback(string $code, string $state, string $deviceName = 'web'): array
    {
        abort_unless($this->googleConfigured(), 503, 'Google OAuth is not connected');
        abort_unless(cache()->pull('oauth:google:'.$state), 422, 'Invalid OAuth state.');

        $tokenResponse = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'code' => $code,
            'client_id' => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'redirect_uri' => config('services.google.redirect'),
            'grant_type' => 'authorization_code',
        ]);

        abort_unless($tokenResponse->successful(), 422, 'Google token exchange failed.');

        $idToken = (string) $tokenResponse->json('id_token');
        abort_unless($idToken !== '', 422, 'Google identity token was missing.');

        $claims = $this->verifyGoogleIdToken($idToken);

        return $this->resolveOAuthUser(
            provider: UserIdentity::PROVIDER_GOOGLE,
            providerId: $claims['sub'],
            email: $claims['email'],
            name: $claims['name'],
            deviceName: $deviceName,
            emailVerified: true,
            metadata: $claims,
        );
    }

    /** @return array{url: string, state: string}|array{error: string} */
    public function facebookRedirectUrl(): array
    {
        if (! $this->facebookConfigured()) {
            return ['error' => 'Facebook OAuth is not connected'];
        }

        $state = Str::random(40);
        cache()->put('oauth:facebook:'.$state, true, 600);

        $params = http_build_query([
            'client_id' => $this->facebookClientId(),
            'redirect_uri' => $this->facebookRedirect(),
            'state' => $state,
            'scope' => 'email,public_profile',
        ]);

        return [
            'url' => 'https://www.facebook.com/v18.0/dialog/oauth?'.$params,
            'state' => $state,
        ];
    }

    /** @return array{user: User, token: string, created: bool} */
    public function handleFacebookCallback(string $code, string $state, string $deviceName = 'web'): array
    {
        abort_unless($this->facebookConfigured(), 503, 'Facebook OAuth is not connected');
        abort_unless(cache()->pull('oauth:facebook:'.$state), 422, 'Invalid OAuth state.');

        $tokenResponse = Http::get('https://graph.facebook.com/v18.0/oauth/access_token', [
            'client_id' => $this->facebookClientId(),
            'client_secret' => $this->facebookClientSecret(),
            'redirect_uri' => $this->facebookRedirect(),
            'code' => $code,
        ]);

        abort_unless($tokenResponse->successful(), 422, 'Facebook token exchange failed.');

        $accessToken = (string) $tokenResponse->json('access_token');
        abort_unless($accessToken !== '', 422, 'Facebook access token was missing.');

        $appUserId = $this->verifyFacebookAccessToken($accessToken);

        $profile = Http::get('https://graph.facebook.com/me', [
            'fields' => 'id,name,email',
            'access_token' => $accessToken,
        ]);
        abort_unless($profile->successful(), 422, 'Failed to fetch Facebook profile.');

        $facebookId = (string) $profile->json('id');
        abort_unless($facebookId !== '' && $facebookId === $appUserId, 422, 'Facebook identity did not match the verified token.');

        $email = strtolower(trim((string) $profile->json('email')));
        abort_unless($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL), 422, 'Facebook did not provide a verified email.');

        $name = (string) ($profile->json('name') ?: $email);

        return $this->resolveOAuthUser(
            provider: UserIdentity::PROVIDER_FACEBOOK,
            providerId: $facebookId,
            email: $email,
            name: $name,
            deviceName: $deviceName,
            emailVerified: true,
            metadata: $profile->json() ?? [],
        );
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @return array{user: User, token: string, created: bool}
     */
    private function resolveOAuthUser(
        string $provider,
        string $providerId,
        string $email,
        string $name,
        string $deviceName,
        bool $emailVerified,
        array $metadata,
    ): array {
        $identity = UserIdentity::where('provider', $provider)->where('provider_id', $providerId)->first();
        $created = false;

        if ($identity !== null) {
            $user = $identity->user;
        } else {
            abort_unless($emailVerified && $email !== '', 422, 'Provider email is not verified.');

            $user = User::where('email', $email)->first();
            if ($user === null) {
                abort_unless(config('app.allow_registration'), 403, 'Registration is disabled.');
                $registration = $this->registration->register([
                    'name' => $name,
                    'email' => $email,
                    'password' => Str::random(32),
                ]);
                $user = $registration['user'];
                $created = true;
            }

            $this->identities->link($user, $provider, $providerId, $email, null, $metadata);
        }

        return [
            'user' => $user,
            'token' => $user->createToken($deviceName)->plainTextToken,
            'created' => $created,
        ];
    }

    /** @return array{sub: string, email: string, name: string, iss: string, aud: string} */
    private function verifyGoogleIdToken(string $idToken): array
    {
        $response = Http::acceptJson()->get('https://oauth2.googleapis.com/tokeninfo', [
            'id_token' => $idToken,
        ]);
        abort_unless($response->successful(), 422, 'Google identity token could not be verified.');

        $iss = (string) $response->json('iss');
        $aud = (string) $response->json('aud');
        $sub = (string) $response->json('sub');
        $email = strtolower(trim((string) $response->json('email')));
        $verified = filter_var($response->json('email_verified'), FILTER_VALIDATE_BOOLEAN);
        $exp = (int) $response->json('exp');

        abort_unless(in_array($iss, ['accounts.google.com', 'https://accounts.google.com'], true), 422, 'Google token issuer is invalid.');
        abort_unless($aud === (string) config('services.google.client_id'), 422, 'Google token audience is invalid.');
        abort_unless($sub !== '', 422, 'Google subject is missing.');
        abort_unless($verified && $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL), 422, 'Google email is not verified.');
        abort_unless($exp > time(), 422, 'Google identity token has expired.');

        return [
            'sub' => $sub,
            'email' => $email,
            'name' => (string) ($response->json('name') ?: $email),
            'iss' => $iss,
            'aud' => $aud,
        ];
    }

    private function verifyFacebookAccessToken(string $accessToken): string
    {
        $appToken = $this->facebookClientId().'|'.$this->facebookClientSecret();
        $response = Http::acceptJson()->get('https://graph.facebook.com/debug_token', [
            'input_token' => $accessToken,
            'access_token' => $appToken,
        ]);
        abort_unless($response->successful(), 422, 'Facebook token could not be verified.');

        $data = $response->json('data') ?? [];
        abort_unless(($data['is_valid'] ?? false) === true, 422, 'Facebook token is invalid.');
        abort_unless((string) ($data['app_id'] ?? '') === (string) $this->facebookClientId(), 422, 'Facebook token audience is invalid.');

        $userId = (string) ($data['user_id'] ?? '');
        abort_unless($userId !== '', 422, 'Facebook subject is missing.');

        return $userId;
    }

    private function googleConfigured(): bool
    {
        return filled(config('services.google.client_id'))
            && filled(config('services.google.client_secret'));
    }

    private function facebookConfigured(): bool
    {
        return filled($this->facebookClientId()) && filled($this->facebookClientSecret());
    }

    private function facebookClientId(): ?string
    {
        return config('services.facebook.client_id') ?: config('providers.facebook.client_id');
    }

    private function facebookClientSecret(): ?string
    {
        return config('services.facebook.client_secret') ?: config('providers.facebook.client_secret');
    }

    private function facebookRedirect(): ?string
    {
        return config('services.facebook.redirect') ?: config('providers.facebook.redirect');
    }
}
