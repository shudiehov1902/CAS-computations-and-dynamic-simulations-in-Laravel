<?php

namespace Tests\Feature;

use App\Models\CasLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Support\CreatesFakeOctave;
use Tests\TestCase;

class PendulumSimulationTest extends TestCase
{
    use CreatesFakeOctave;
    use RefreshDatabase;

    public function test_missing_api_key_is_rejected_before_pendulum_backend(): void
    {
        $response = $this->postJson('/api/simulations/pendulum', [
            'reference' => 0.2,
        ]);

        $response->assertStatus(401);
        $this->assertDatabaseCount('cas_logs', 0);
    }

    public function test_invalid_payload_returns_validation_error_and_is_logged(): void
    {
        config(['cas.api_key' => 'test-api-key']);

        $response = $this->withHeader('X-CAS-API-Key', 'test-api-key')
            ->postJson('/api/simulations/pendulum', [
                'reference' => 99,
            ]);

        $response
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'output' => '',
            ]);

        $this->assertDatabaseHas('cas_logs', [
            'command' => 'simulation.pendulum',
            'status' => 'error',
        ]);
    }

    public function test_valid_payload_returns_pendulum_arrays_and_creates_success_log(): void
    {
        config([
            'cas.api_key' => 'test-api-key',
            'cas.octave_path' => $this->createFakeOctaveExecutable('pendulum'),
        ]);

        $response = $this->withHeader('X-CAS-API-Key', 'test-api-key')
            ->postJson('/api/simulations/pendulum', [
                'reference' => 0.2,
                'initial_position' => 0,
                'initial_angle' => 0,
                'time_step' => 0.05,
                'duration' => 10,
            ]);

        $response
            ->assertStatus(200)
            ->assertJson([
                'time' => [0, 0.05, 0.1],
                'position' => [0, 0.01, 0.02],
                'angle' => [0, -0.001, -0.002],
            ]);

        $log = CasLog::query()->firstOrFail();

        $this->assertSame('simulation.pendulum', $log->command);
        $this->assertSame('success', $log->status);
        $this->assertSame(0.2, $log->request_payload['reference']);
        $this->assertTrue(Str::isUuid((string) $log->user_token));
        $this->assertStringContainsString('"time":[0,0.05,0.1]', (string) $log->output);
    }

    public function test_failed_octave_simulation_returns_error_and_creates_error_log(): void
    {
        config([
            'cas.api_key' => 'test-api-key',
            'cas.octave_path' => $this->createFakeOctaveExecutable('fail'),
        ]);

        $response = $this->withHeader('X-CAS-API-Key', 'test-api-key')
            ->postJson('/api/simulations/pendulum', [
                'reference' => 0.2,
            ]);

        $response
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'output' => '',
                'error' => 'fake octave error',
            ]);

        $this->assertDatabaseHas('cas_logs', [
            'command' => 'simulation.pendulum',
            'status' => 'error',
            'error_message' => 'fake octave error',
        ]);
    }
}
