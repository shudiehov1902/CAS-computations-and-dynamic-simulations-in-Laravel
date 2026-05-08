<?php

namespace Tests\Feature;

use Tests\TestCase;

class ApiKeyMiddlewareTest extends TestCase
{
    public function test_api_request_without_key_is_rejected(): void
    {
        config(['cas.api_key' => 'test-api-key']);

        $response = $this->getJson('/api/logs');

        $response
            ->assertStatus(401)
            ->assertJson([
                'message' => 'Invalid or missing API key.',
            ]);
    }

    public function test_api_request_with_wrong_key_is_rejected(): void
    {
        config(['cas.api_key' => 'test-api-key']);

        $response = $this->withHeader('X-CAS-API-Key', 'wrong-key')
            ->getJson('/api/logs');

        $response
            ->assertStatus(401)
            ->assertJson([
                'message' => 'Invalid or missing API key.',
            ]);
    }

    public function test_api_request_with_valid_key_reaches_controller(): void
    {
        config(['cas.api_key' => 'test-api-key']);

        $response = $this->withHeader('X-CAS-API-Key', 'test-api-key')
            ->getJson('/api/logs');

        $response
            ->assertStatus(501)
            ->assertJson([
                'message' => 'CAS logs will be implemented after the database tables are created.',
            ]);
    }

    public function test_web_pages_do_not_require_api_key(): void
    {
        config(['cas.api_key' => 'test-api-key']);

        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
