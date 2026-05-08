<?php

namespace Tests\Unit;

use App\Services\OctaveExecutionException;
use App\Services\PendulumService;
use Tests\Support\CreatesFakeOctave;
use Tests\TestCase;

class PendulumServiceTest extends TestCase
{
    use CreatesFakeOctave;

    public function test_service_parses_valid_pendulum_json_output(): void
    {
        $output = <<<OUTPUT
some octave text
__WEBTE2_PENDULUM_JSON_START__
{"time":[0,0.05,0.1],"position":[0,0.01,0.02],"angle":[0,-0.001,-0.002]}
__WEBTE2_PENDULUM_JSON_END__
OUTPUT;

        $result = app(PendulumService::class)->parseOutput($output);

        $this->assertSame([0.0, 0.05, 0.1], $result['time']);
        $this->assertSame([0.0, 0.01, 0.02], $result['position']);
        $this->assertSame([0.0, -0.001, -0.002], $result['angle']);
    }

    public function test_service_rejects_malformed_output_without_json_markers(): void
    {
        $this->expectException(OctaveExecutionException::class);
        $this->expectExceptionMessage('Pendulum simulation output did not contain JSON data.');

        app(PendulumService::class)->parseOutput('plain octave output');
    }

    public function test_service_rejects_invalid_json_output(): void
    {
        $this->expectException(OctaveExecutionException::class);
        $this->expectExceptionMessage('Pendulum simulation returned invalid JSON');

        app(PendulumService::class)->parseOutput(<<<OUTPUT
__WEBTE2_PENDULUM_JSON_START__
{"time":[0],"position":
__WEBTE2_PENDULUM_JSON_END__
OUTPUT);
    }

    public function test_service_runs_generated_script_and_returns_arrays(): void
    {
        config([
            'cas.octave_path' => $this->createFakeOctaveExecutable('pendulum'),
        ]);

        $result = app(PendulumService::class)->simulate([
            'reference' => 0.2,
            'initial_position' => 0,
            'initial_angle' => 0,
            'time_step' => 0.05,
            'duration' => 10,
        ]);

        $this->assertSame([0.0, 0.05, 0.1], $result['time']);
        $this->assertSame([0.0, 0.01, 0.02], $result['position']);
        $this->assertSame([0.0, -0.001, -0.002], $result['angle']);
    }
}
