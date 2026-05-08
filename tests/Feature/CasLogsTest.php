<?php

namespace Tests\Feature;

use App\Models\CasLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        ]);

        $response = $this->withHeader('X-CAS-API-Key', 'test-api-key')
            ->get('/api/logs/export');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $content = $response->getContent();

        $this->assertStringContainsString('id,created_at,command,status,user_token,ip_address,request_payload,output,error_message', $content);
        $this->assertStringContainsString('cas.execute', $content);
        $this->assertStringContainsString('127.0.0.1', $content);
        $this->assertStringContainsString('ans = 2', $content);
    }

    public function test_web_logs_page_works_without_api_key(): void
    {
        $this->createCasLog([
            'command' => 'simulation.pendulum',
            'status' => 'error',
            'error_message' => 'fake octave error',
        ]);

        $response = $this->get('/logs');

        $response
            ->assertStatus(200)
            ->assertSee('CAS Logs')
            ->assertSee('simulation.pendulum')
            ->assertSee('fake octave error')
            ->assertSee('Export CSV');
    }

    public function test_web_logs_export_returns_csv(): void
    {
        $this->createCasLog([
            'command' => 'simulation.ball_beam',
            'status' => 'success',
            'output' => '{"time":[0]}',
        ]);

        $response = $this->get('/logs/export');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('simulation.ball_beam', $response->getContent());
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
