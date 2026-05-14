<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureAnonymousToken;
use App\Models\CasLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CasLogsTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_logs_without_key_returns_unauthorized(): void
    {
        config(['cas.api_key' => 'test-api-key']);

        $response = $this->getJson('/api/logs');

        $response
            ->assertStatus(401)
            ->assertJson([
                'message' => 'Invalid or missing API key.',
            ]);
    }

    public function test_api_logs_with_valid_key_returns_paginated_data(): void
    {
        config(['cas.api_key' => 'test-api-key']);

        $log = $this->createCasLog([
            'command' => 'cas.execute',
            'request_payload' => ['command' => '1 + 1'],
            'status' => 'success',
            'output' => 'ans = 2',
        ]);

        $response = $this->withHeader('X-CAS-API-Key', 'test-api-key')
            ->getJson('/api/logs');

        $response
            ->assertStatus(200)
            ->assertJsonPath('data.0.id', $log->id)
            ->assertJsonPath('data.0.command', 'cas.execute')
            ->assertJsonPath('data.0.status', 'success')
            ->assertJsonPath('data.0.request_payload.command', '1 + 1')
            ->assertJsonPath('data.0.output', 'ans = 2')
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.per_page', 20)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('meta.last_page', 1);
    }

    public function test_api_logs_with_valid_key_returns_logs_for_all_users(): void
    {
        config(['cas.api_key' => 'test-api-key']);

        $this->createCasLog([
            'command' => 'cas.execute',
            'user_token' => (string) Str::uuid(),
        ]);
        $this->createCasLog([
            'command' => 'simulation.pendulum',
            'user_token' => (string) Str::uuid(),
        ]);

        $response = $this->withHeader('X-CAS-API-Key', 'test-api-key')
            ->getJson('/api/logs');

        $response
            ->assertStatus(200)
            ->assertJsonPath('meta.total', 2);
    }

    public function test_api_logs_are_ordered_newest_first(): void
    {
        config(['cas.api_key' => 'test-api-key']);

        $oldLog = $this->createCasLog([
            'command' => 'cas.execute',
            'created_at' => now()->subMinutes(5),
        ]);

        $newLog = $this->createCasLog([
            'command' => 'simulation.ball_beam',
            'created_at' => now(),
        ]);

        $response = $this->withHeader('X-CAS-API-Key', 'test-api-key')
            ->getJson('/api/logs');

        $response->assertStatus(200);

        $this->assertSame($newLog->id, $response->json('data.0.id'));
        $this->assertSame($oldLog->id, $response->json('data.1.id'));
    }

    public function test_api_logs_export_returns_csv(): void
    {
        config(['cas.api_key' => 'test-api-key']);

        $this->createCasLog([
            'command' => 'cas.execute',
            'request_payload' => ['command' => '1 + 1'],
            'status' => 'success',
            'output' => 'ans = 2',
            'ip_address' => '127.0.0.1',
            'user_token' => 'api-token-one',
        ]);
        $this->createCasLog([
            'command' => 'simulation.pendulum',
            'status' => 'error',
            'error_message' => 'api-only error',
            'user_token' => 'api-token-two',
        ]);

        $response = $this->withHeader('X-CAS-API-Key', 'test-api-key')
            ->get('/api/logs/export');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $content = $response->getContent();

        $this->assertStringContainsString('id,created_at,command,status,user_token,ip_address,request_payload,output,error_message', $content);
        $this->assertStringContainsString('cas.execute', $content);
        $this->assertStringContainsString('simulation.pendulum', $content);
        $this->assertStringContainsString('127.0.0.1', $content);
        $this->assertStringContainsString('ans = 2', $content);
        $this->assertStringContainsString('api-only error', $content);
    }

    public function test_web_logs_page_shows_only_current_anonymous_user_logs(): void
    {
        $currentToken = (string) Str::uuid();
        $otherToken = (string) Str::uuid();

        $this->createCasLog([
            'command' => 'simulation.pendulum',
            'status' => 'error',
            'error_message' => 'current user octave error',
            'user_token' => $currentToken,
        ]);
        $this->createCasLog([
            'command' => 'simulation.ball_beam',
            'status' => 'success',
            'output' => 'other user hidden output',
            'user_token' => $otherToken,
        ]);

        $response = $this->withCookie(EnsureAnonymousToken::COOKIE_NAME, $currentToken)
            ->get('/logs');

        $response
            ->assertStatus(200)
            ->assertSee('CAS Logs')
            ->assertSee('simulation.pendulum')
            ->assertSee('current user octave error')
            ->assertSee('Export CSV')
            ->assertDontSee('simulation.ball_beam')
            ->assertDontSee('other user hidden output');
    }

    public function test_web_logs_export_returns_csv_for_current_anonymous_user_only(): void
    {
        $currentToken = (string) Str::uuid();
        $otherToken = (string) Str::uuid();

        $this->createCasLog([
            'command' => 'simulation.ball_beam',
            'status' => 'success',
            'output' => 'current user csv output',
            'user_token' => $currentToken,
        ]);
        $this->createCasLog([
            'command' => 'cas.execute',
            'status' => 'success',
            'output' => 'other user csv output',
            'user_token' => $otherToken,
        ]);

        $response = $this->withCookie(EnsureAnonymousToken::COOKIE_NAME, $currentToken)
            ->get('/logs/export');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $content = $response->getContent();

        $this->assertStringContainsString('simulation.ball_beam', $content);
        $this->assertStringContainsString('current user csv output', $content);
        $this->assertStringNotContainsString('cas.execute', $content);
        $this->assertStringNotContainsString('other user csv output', $content);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createCasLog(array $attributes = []): CasLog
    {
        $createdAt = $attributes['created_at'] ?? now();
        unset($attributes['created_at']);

        $log = CasLog::create(array_merge([
            'command' => 'cas.execute',
            'request_payload' => ['command' => '1 + 1'],
            'status' => 'success',
            'output' => 'ans = 2',
            'error_message' => null,
            'ip_address' => '127.0.0.1',
            'user_token' => 'test-user-token',
        ], $attributes));

        $log->forceFill([
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ])->save();

        return $log->refresh();
    }
}
