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

        $accessToken = $tokenResponse->json('access_token');
        $profile = Http::withToken($accessToken)->get('https://www.googleapis.com/oauth2/v2/userinfo');
        abort_unless($profile->successful(), 422, 'Failed to fetch Google profile.');

        $googleId = (string) $profile->json('id');
        $email = (string) $profile->json('email');
        $name = (string) ($profile->json('name') ?: $email);

        return $this->resolveOAuthUser(
            provider: UserIdentity::PROVIDER_GOOGLE,
            providerId: $googleId,
            email: $email,
            name: $name,
            deviceName: $deviceName,
            metadata: $profile->json() ?? [],
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

        $accessToken = $tokenResponse->json('access_token');
        $profile = Http::get('https://graph.facebook.com/me', [
            'fields' => 'id,name,email',
            'access_token' => $accessToken,
        ]);
        abort_unless($profile->successful(), 422, 'Failed to fetch Facebook profile.');

        $facebookId = (string) $profile->json('id');
        $email = (string) ($profile->json('email') ?: $facebookId.'@facebook.local');
        $name = (string) ($profile->json('name') ?: 'Facebook User');

        return $this->resolveOAuthUser(
            provider: UserIdentity::PROVIDER_FACEBOOK,
            providerId: $facebookId,
            email: $email,
            name: $name,
            deviceName: $deviceName,
            metadata: $profile->json() ?? [],
        );
    }

    /** @param  array<string, mixed>  $metadata
     * @return array{user: User, token: string, created: bool}
     */
    private function resolveOAuthUser(string $provider, string $providerId, string $email, string $name, string $deviceName, array $metadata): array
    {
        $identity = UserIdentity::where('provider', $provider)->where('provider_id', $providerId)->first();
        $created = false;

        if ($identity !== null) {
            $user = $identity->user;
        } else {
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
