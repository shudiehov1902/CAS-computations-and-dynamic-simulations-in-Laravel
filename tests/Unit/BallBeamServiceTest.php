<?php

namespace Tests\Unit;

use App\Services\BallBeamService;
use App\Services\OctaveExecutionException;
use Tests\Support\CreatesFakeOctave;
use Tests\TestCase;

class BallBeamServiceTest extends TestCase
{
    use CreatesFakeOctave;

    public function test_service_parses_valid_ball_beam_json_output(): void
    {
        $output = <<<OUTPUT
some octave text
__WEBTE2_BALL_BEAM_JSON_START__
{"time":[0,0.01,0.02],"ball_position":[0,0.01,0.02],"beam_angle":[0,0.0001,0.00008]}
__WEBTE2_BALL_BEAM_JSON_END__
OUTPUT;

        $result = app(BallBeamService::class)->parseOutput($output);

        $this->assertSame([0.0, 0.01, 0.02], $result['time']);
        $this->assertSame([0.0, 0.01, 0.02], $result['ball_position']);
        $this->assertSame([0.0, 0.0001, 0.00008], $result['beam_angle']);
    }

    public function test_service_rejects_malformed_output_without_json_markers(): void
    {
        $this->expectException(OctaveExecutionException::class);
        $this->expectExceptionMessage('Ball and beam simulation output did not contain JSON data.');

        app(BallBeamService::class)->parseOutput('plain octave output');
    }

    public function test_service_rejects_invalid_json_output(): void
    {
        $this->expectException(OctaveExecutionException::class);
        $this->expectExceptionMessage('Ball and beam simulation returned invalid JSON');

        app(BallBeamService::class)->parseOutput(<<<OUTPUT
__WEBTE2_BALL_BEAM_JSON_START__
{"time":[0],"ball_position":
__WEBTE2_BALL_BEAM_JSON_END__
OUTPUT);
    }

    public function test_service_runs_generated_script_and_returns_arrays(): void
    {
        config([
            'cas.octave_path' => $this->createFakeOctaveExecutable('ball-beam'),
        ]);

        $result = app(BallBeamService::class)->simulate([
            'reference' => 0.25,
            'initial_velocity' => 0,
            'initial_acceleration' => 0,
            'time_step' => 0.01,
            'duration' => 5,
        ]);

        $this->assertSame([0.0, 0.01, 0.02], $result['time']);
        $this->assertSame([0.0, 0.01, 0.02], $result['ball_position']);
        $this->assertSame([0.0, 0.0001, 0.00008], $result['beam_angle']);
    }

    public function test_generated_script_scales_reference_without_scaling_initial_state(): void
    {
        $logPath = $this->fakeOctaveLogPath('ball-beam-script');

        config([
            'cas.octave_path' => $this->createFakeOctaveExecutable('ball-beam', $logPath),
        ]);

        app(BallBeamService::class)->simulate([
            'reference' => 0.25,
            'initial_velocity' => 1,
            'initial_acceleration' => 0,
            'time_step' => 0.01,
            'duration' => 5,
        ]);

        $log = json_decode(file_get_contents($logPath), true, flags: JSON_THROW_ON_ERROR);
        $script = $log['wrapper_content'];

        $this->assertStringContainsString('sys = ss(A-B*K,B*N,C,D);', $script);
        $this->assertStringContainsString('[y,t,x] = lsim(sys,r*ones(size(t)),t,[initPozicia;0;initUhol;0]);', $script);
        $this->assertStringNotContainsString('lsim(N*sys', $script);
    }
}
