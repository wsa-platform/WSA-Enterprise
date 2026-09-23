<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\ResearchFeedbackRecord;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SpaCsrfAuthenticationContractTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, string> */
    private array $spaOrigin = [
        'Origin' => 'http://localhost:5173',
        'Referer' => 'http://localhost:5173/',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        Organization::create([
            'name' => 'WSA Demo',
            'slug' => 'wsa-demo',
            'is_active' => true,
        ]);

        // PHPUnit default host is APP_URL (localhost). The Vite SPA host is localhost:5173.
        $this->withServerVariables(['HTTP_HOST' => 'localhost:5173']);
        config(['sanctum.stateful' => ['localhost', 'localhost:5173', '127.0.0.1:5173']]);
    }

    public function test_csrf_middleware_rejects_missing_and_invalid_tokens(): void
    {
        // Laravel skips CSRF while runningUnitTests(). Force production-like
        // validation so register/login remain protected in this contract test.
        $middleware = new class($this->app, $this->app->make(\Illuminate\Contracts\Encryption\Encrypter::class)) extends ValidateCsrfToken {
            protected function runningUnitTests(): bool
            {
                return false;
            }
        };

        $session = $this->app['session']->driver();
        $session->start();

        $missing = Request::create('/api/v1/auth/register', 'POST', $this->ownerPayload());
        $missing->setLaravelSession($session);
        $missing->headers->set('Origin', 'http://localhost:5173');
        $missing->headers->set('Accept', 'application/json');

        try {
            $middleware->handle($missing, static fn () => response('ok'));
            $this->fail('Missing CSRF token must be rejected.');
        } catch (TokenMismatchException) {
            $this->assertTrue(true);
        }

        $invalid = Request::create('/api/v1/auth/login', 'POST', [
            'email' => 'nobody@wsa.test',
            'password' => 'password123',
        ]);
        $invalid->setLaravelSession($session);
        $invalid->headers->set('X-XSRF-TOKEN', 'not-a-valid-token');
        $invalid->headers->set('Accept', 'application/json');

        try {
            $middleware->handle($invalid, static fn () => response('ok'));
            $this->fail('Invalid CSRF token must be rejected.');
        } catch (TokenMismatchException) {
            $this->assertTrue(true);
        }
    }

    public function test_register_succeeds_with_sanctum_csrf_cookie_and_header(): void
    {
        config(['app.allow_registration' => true]);

        $this->assertNotNull($this->spaCsrfToken());

        $this->withHeaders($this->spaHeaders())
            ->postJson('/api/v1/auth/register', $this->ownerPayload())
            ->assertCreated()
            ->assertJsonStructure(['token', 'user']);

        $this->assertDatabaseHas('users', ['email' => 'spa-owner@wsa.test']);
    }

    public function test_job_seeker_register_succeeds_with_csrf_when_generic_registration_is_disabled(): void
    {
        config([
            'app.allow_registration' => false,
            'app.allow_job_seeker_registration' => true,
        ]);

        $this->spaCsrfToken();

        $this->withHeaders($this->spaHeaders())
            ->postJson('/api/v1/auth/register', [
                'name' => 'SPA Seeker',
                'email' => 'spa-seeker@wsa.test',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'audience' => 'job_seeker',
                'device_name' => 'wsa-web-dashboard',
            ])
            ->assertCreated()
            ->assertJsonPath('user.email', 'spa-seeker@wsa.test');
    }

    public function test_generic_register_stays_disabled_even_with_valid_csrf(): void
    {
        config(['app.allow_registration' => false]);
        $this->spaCsrfToken();

        $this->withHeaders($this->spaHeaders())
            ->postJson('/api/v1/auth/register', $this->ownerPayload())
            ->assertForbidden();
    }

    public function test_register_validation_and_duplicate_email_remain_422(): void
    {
        config(['app.allow_registration' => true]);
        $this->spaCsrfToken();

        $this->withHeaders($this->spaHeaders())
            ->postJson('/api/v1/auth/register', [
                'name' => '',
                'email' => 'not-an-email',
                'password' => 'short',
                'password_confirmation' => 'other',
            ])
            ->assertStatus(422);

        User::create([
            'name' => 'Existing',
            'email' => 'spa-owner@wsa.test',
            'password' => Hash::make('password123'),
        ]);

        $this->withHeaders($this->spaHeaders())
            ->postJson('/api/v1/auth/register', $this->ownerPayload())
            ->assertStatus(422);
    }

    public function test_login_and_authenticated_request_succeed_with_csrf(): void
    {
        $user = User::create([
            'name' => 'Login User',
            'email' => 'spa-login@wsa.test',
            'password' => Hash::make('password123'),
        ]);

        $this->spaCsrfToken();

        $login = $this->withHeaders($this->spaHeaders())
            ->postJson('/api/v1/auth/login', [
                'email' => $user->email,
                'password' => 'password123',
                'device_name' => 'wsa-web-dashboard',
            ])
            ->assertOk()
            ->assertJsonStructure(['token', 'user']);

        $token = $login->json('token');
        $this->assertIsString($token);

        $this->withHeaders(['Authorization' => 'Bearer '.$token])
            ->getJson('/api/v1/user')
            ->assertOk()
            ->assertJsonPath('email', $user->email);

        $this->withHeaders($this->spaHeaders() + ['Authorization' => 'Bearer '.$token])
            ->postJson('/api/v1/auth/logout')
            ->assertSuccessful();
    }

    public function test_invalid_password_is_422_not_419_when_csrf_is_valid(): void
    {
        User::create([
            'name' => 'Login User',
            'email' => 'spa-login-bad@wsa.test',
            'password' => Hash::make('password123'),
        ]);

        $this->spaCsrfToken();

        $this->withHeaders($this->spaHeaders())
            ->postJson('/api/v1/auth/login', [
                'email' => 'spa-login-bad@wsa.test',
                'password' => 'wrong-password',
                'device_name' => 'wsa-web-dashboard',
            ])
            ->assertStatus(422);
    }

    public function test_public_positive_feedback_persists_with_csrf(): void
    {
        $this->spaCsrfToken();

        $this->withHeaders($this->spaHeaders())
            ->postJson('/api/v1/public/research-agent/feedback', [
                'polarity' => 'positive',
                'research_source' => 'home',
                'question' => 'csrf feedback ok',
                'ui_locale' => 'en',
            ])
            ->assertCreated()
            ->assertJsonPath('persisted', true);

        $this->assertSame(1, ResearchFeedbackRecord::query()->count());
    }

    /** @return array<string, string> */
    private function spaHeaders(): array
    {
        return $this->spaOrigin + ['X-CSRF-TOKEN' => csrf_token()];
    }

    private function spaCsrfToken(): string
    {
        $this->withHeaders($this->spaOrigin)->get('/sanctum/csrf-cookie')->assertSuccessful();

        return csrf_token();
    }

    /** @return array<string, string> */
    private function ownerPayload(): array
    {
        return [
            'name' => 'SPA Owner',
            'email' => 'spa-owner@wsa.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'device_name' => 'wsa-web-dashboard',
        ];
    }
}
