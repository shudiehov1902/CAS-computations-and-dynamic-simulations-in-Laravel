<?php

namespace Tests\Feature;

use App\Models\CasLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Support\CreatesFakeOctave;
use Tests\TestCase;

class BallBeamSimulationTest extends TestCase
{
    use CreatesFakeOctave;
    use RefreshDatabase;

    public function test_missing_api_key_is_rejected_before_ball_beam_backend(): void
    {
        $response = $this->postJson('/api/simulations/ball-beam', [
            'reference' => 0.25,
        ]);

        $response->assertStatus(401);
        $this->assertDatabaseCount('cas_logs', 0);
    }

    public function test_invalid_payload_returns_validation_error_and_is_logged(): void
    {
        config(['cas.api_key' => 'test-api-key']);

        $response = $this->withHeader('X-CAS-API-Key', 'test-api-key')
            ->postJson('/api/simulations/ball-beam', [
                'reference' => 99,
            ]);

        $response
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'output' => '',
            ]);

        $this->assertDatabaseHas('cas_logs', [
            'command' => 'simulation.ball_beam',
            'status' => 'error',
        ]);
    }

    public function test_valid_payload_returns_ball_beam_arrays_and_creates_success_log(): void
    {
        config([
            'cas.api_key' => 'test-api-key',
            'cas.octave_path' => $this->createFakeOctaveExecutable('ball-beam'),
        ]);

        $response = $this->withHeader('X-CAS-API-Key', 'test-api-key')
            ->postJson('/api/simulations/ball-beam', [
                'reference' => 0.25,
                'initial_velocity' => 0,
                'initial_acceleration' => 0,
                'time_step' => 0.01,
                'duration' => 5,
            ]);

        $response
            ->assertStatus(200)
            ->assertJson([
                'time' => [0, 0.01, 0.02],
                'ball_position' => [0, 0.01, 0.02],
                'beam_angle' => [0, 0.0001, 0.00008],
            ]);

        $log = CasLog::query()->firstOrFail();

        $this->assertSame('simulation.ball_beam', $log->command);
        $this->assertSame('success', $log->status);
        $this->assertSame(0.25, $log->request_payload['reference']);
        $this->assertTrue(Str::isUuid((string) $log->user_token));
        $this->assertStringContainsString('"ball_position":[0,0.01,0.02]', (string) $log->output);
    }

    public function test_failed_octave_simulation_returns_error_and_creates_error_log(): void
    {
        config([
            'cas.api_key' => 'test-api-key',
            'cas.octave_path' => $this->createFakeOctaveExecutable('fail'),
        ]);

        $response = $this->withHeader('X-CAS-API-Key', 'test-api-key')
            ->postJson('/api/simulations/ball-beam', [
                'reference' => 0.25,
            ]);

        $response
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'output' => '',
                'error' => 'fake octave error',
            ]);

        $this->assertDatabaseHas('cas_logs', [
            'command' => 'simulation.ball_beam',
            'status' => 'error',
            'error_message' => 'fake octave error',
        ]);
    }
}
