<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureAnonymousToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AnonymousTokenMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_web_request_without_cookie_sets_anonymous_user_token(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertCookie(EnsureAnonymousToken::COOKIE_NAME);

        $cookie = $response->getCookie(EnsureAnonymousToken::COOKIE_NAME);

        $this->assertNotNull($cookie);
        $this->assertTrue(Str::isUuid($cookie->getValue()));
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertSame('lax', strtolower((string) $cookie->getSameSite()));
    }

    public function test_web_request_with_existing_valid_cookie_keeps_same_token(): void
    {
        $existingToken = (string) Str::uuid();

        $response = $this->withCookie(EnsureAnonymousToken::COOKIE_NAME, $existingToken)
            ->get('/');

        $response
            ->assertStatus(200)
            ->assertCookie(EnsureAnonymousToken::COOKIE_NAME, $existingToken);
    }

    public function test_api_request_with_valid_key_sets_anonymous_user_token(): void
    {
        config(['cas.api_key' => 'test-api-key']);

        $response = $this->withHeader('X-CAS-API-Key', 'test-api-key')
            ->getJson('/api/logs');

        $response->assertStatus(200);
        $response->assertCookie(EnsureAnonymousToken::COOKIE_NAME);

        $cookie = $response->getCookie(EnsureAnonymousToken::COOKIE_NAME);

        $this->assertNotNull($cookie);
        $this->assertTrue(Str::isUuid($cookie->getValue()));
        $this->assertTrue($cookie->isHttpOnly());
    }

    public function test_api_request_with_existing_valid_cookie_keeps_same_token(): void
    {
        config(['cas.api_key' => 'test-api-key']);

        $existingToken = (string) Str::uuid();

        $response = $this->withCredentials()
            ->withCookie(EnsureAnonymousToken::COOKIE_NAME, $existingToken)
            ->withHeader('X-CAS-API-Key', 'test-api-key')
            ->getJson('/api/logs');

        $response
            ->assertStatus(200)
            ->assertCookie(EnsureAnonymousToken::COOKIE_NAME, $existingToken);
    }

    public function test_api_request_without_key_still_returns_unauthorized(): void
    {
        config(['cas.api_key' => 'test-api-key']);

        $response = $this->getJson('/api/logs');

        $response
            ->assertStatus(401)
            ->assertJson([
                'message' => 'Invalid or missing API key.',
            ]);
    }
}
