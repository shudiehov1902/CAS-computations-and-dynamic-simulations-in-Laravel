<?php

namespace Tests\Unit;

use App\Services\OctaveExecutionException;
use App\Services\OctaveService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Tests\Support\CreatesFakeOctave;
use Tests\TestCase;

class OctaveServiceTest extends TestCase
{
    use CreatesFakeOctave;

    public function test_service_runs_octave_with_separate_arguments_and_creates_session_file(): void
    {
        $logPath = $this->fakeOctaveLogPath('success');
        $fakeOctave = $this->createFakeOctaveExecutable('success', $logPath);
        $sessionDirectory = storage_path('framework/testing/octave-sessions');
        $userToken = (string) Str::uuid();

        File::deleteDirectory($sessionDirectory);

        config([
            'cas.octave_path' => $fakeOctave,
            'cas.octave_timeout_seconds' => 10,
            'cas.octave_session_directory' => $sessionDirectory,
        ]);

        $result = app(OctaveService::class)->execute('1 + 1', $userToken);

        $this->assertSame('ans = 2', $result['output']);
        $this->assertSame($sessionDirectory.'/'.$userToken.'.m', $result['session_file']);
        $this->assertFileExists($result['session_file']);

        $fakeLog = json_decode((string) File::get($logPath), true);

        $this->assertSame(['--quiet', '--no-gui', '--no-window-system'], array_slice($fakeLog['arguments'], 0, 3));
        $this->assertSame($result['session_file'], $fakeLog['session_file']);
        $this->assertStringContainsString('1 + 1', $fakeLog['command_content']);
    }

    public function test_service_uses_configured_timeout(): void
    {
        $fakeOctave = $this->createFakeOctaveExecutable('sleep');

        config([
            'cas.octave_path' => $fakeOctave,
            'cas.octave_timeout_seconds' => 1,
            'cas.octave_session_directory' => storage_path('framework/testing/octave-timeout-sessions'),
        ]);

        try {
            app(OctaveService::class)->execute('1 + 1', (string) Str::uuid());
            $this->fail('Expected Octave execution to time out.');
        } catch (OctaveExecutionException $exception) {
            $this->assertSame(504, $exception->httpStatus());
            $this->assertSame('Octave execution timed out.', $exception->getMessage());
        }
    }

    public function test_service_blocks_dangerous_commands_before_starting_process(): void
    {
        $logPath = $this->fakeOctaveLogPath('blocked');
        $fakeOctave = $this->createFakeOctaveExecutable('success', $logPath);

        config([
            'cas.octave_path' => $fakeOctave,
            'cas.octave_timeout_seconds' => 10,
            'cas.octave_session_directory' => storage_path('framework/testing/octave-blocked-sessions'),
        ]);

        try {
            app(OctaveService::class)->execute('system("ls")', (string) Str::uuid());
            $this->fail('Expected dangerous command to be blocked.');
        } catch (OctaveExecutionException $exception) {
            $this->assertSame(422, $exception->httpStatus());
            $this->assertFileDoesNotExist($logPath);
        }
    }
}
