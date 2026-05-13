<?php

namespace Tests\Feature;

use App\Models\AnimationUsage;
use App\Models\CasLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesFakeOctave;
use Tests\TestCase;

class WebWrapperRoutesTest extends TestCase
{
    use CreatesFakeOctave;
    use RefreshDatabase;

    public function test_cas_console_web_wrapper_executes_without_exposing_api_key(): void
    {
        config([
            'cas.api_key' => 'test-api-key',
            'cas.octave_path' => $this->createFakeOctaveExecutable('success'),
        ]);

        $response = $this->postJson('/cas-console/execute', [
            'command' => '1 + 1',
        ]);

        $response
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'output' => 'ans = 2',
                'error' => null,
            ]);

        $this->assertDatabaseHas('cas_logs', [
            'command' => 'cas.execute',
            'status' => 'success',
            'output' => 'ans = 2',
        ]);
    }

    public function test_pendulum_web_wrapper_returns_data_and_records_usage(): void
    {
        config([
            'cas.api_key' => 'test-api-key',
            'cas.octave_path' => $this->createFakeOctaveExecutable('pendulum'),
        ]);

        $response = $this->postJson('/simulations/pendulum/run', [
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

        $this->assertDatabaseHas('cas_logs', [
            'command' => 'simulation.pendulum',
            'status' => 'success',
        ]);
        $this->assertSame(1, AnimationUsage::query()->where('animation_type', 'pendulum')->count());
    }

    public function test_ball_beam_web_wrapper_returns_data_and_records_usage(): void
    {
        config([
            'cas.api_key' => 'test-api-key',
            'cas.octave_path' => $this->createFakeOctaveExecutable('ball-beam'),
        ]);

        $response = $this->postJson('/simulations/ball-beam/run', [
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

        $this->assertDatabaseHas('cas_logs', [
            'command' => 'simulation.ball_beam',
            'status' => 'success',
        ]);
        $this->assertSame(1, AnimationUsage::query()->where('animation_type', 'ball_beam')->count());
    }

    public function test_web_wrapper_validation_errors_are_logged(): void
    {
        config([
            'cas.api_key' => 'test-api-key',
            'cas.octave_path' => $this->createFakeOctaveExecutable('success'),
        ]);

        $response = $this->postJson('/cas-console/execute', [
            'command' => 'system("ls")',
        ]);

        $response
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'output' => '',
                'error' => 'Command contains a blocked Octave operation.',
            ]);

        $this->assertDatabaseHas('cas_logs', [
            'command' => 'cas.execute',
            'status' => 'error',
            'error_message' => 'Command contains a blocked Octave operation.',
        ]);
        $this->assertDatabaseCount('animation_usages', 0);
        $this->assertSame(1, CasLog::query()->count());
    }
}
