<?php

namespace Tests\Feature;

use App\Models\CasLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Tests\Support\CreatesFakeOctave;
use Tests\TestCase;

class CasExecuteTest extends TestCase
{
    use CreatesFakeOctave;
    use RefreshDatabase;

    public function test_missing_api_key_is_rejected_before_cas_execution(): void
    {
        $response = $this->postJson('/api/cas/execute', [
            'command' => '1 + 1',
        ]);

        $response->assertStatus(401);
        $this->assertDatabaseCount('cas_logs', 0);
    }

    public function test_missing_command_returns_validation_error_and_is_logged(): void
    {
        config(['cas.api_key' => 'test-api-key']);

        $response = $this->withHeader('X-CAS-API-Key', 'test-api-key')
            ->postJson('/api/cas/execute', []);

        $response
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'output' => '',
            ]);

        $this->assertDatabaseHas('cas_logs', [
            'command' => 'cas.execute',
            'status' => 'error',
        ]);
    }

    public function test_blocked_command_returns_error_and_is_logged(): void
    {
        config(['cas.api_key' => 'test-api-key']);

        $logPath = $this->fakeOctaveLogPath('blocked-feature');
        $fakeOctave = $this->createFakeOctaveExecutable('success', $logPath);

        config(['cas.octave_path' => $fakeOctave]);

        $response = $this->withHeader('X-CAS-API-Key', 'test-api-key')
            ->postJson('/api/cas/execute', [
                'command' => 'system("ls")',
            ]);

        $response
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'output' => '',
                'error' => 'Command contains a blocked Octave operation.',
            ]);

        $this->assertFileDoesNotExist($logPath);
        $this->assertDatabaseHas('cas_logs', [
            'command' => 'cas.execute',
            'status' => 'error',
            'error_message' => 'Command contains a blocked Octave operation.',
        ]);
    }

    public function test_valid_command_returns_output_and_creates_success_log(): void
    {
        config(['cas.api_key' => 'test-api-key']);

        $logPath = $this->fakeOctaveLogPath('success-feature');
        $sessionDirectory = storage_path('framework/testing/feature-octave-sessions');
        $fakeOctave = $this->createFakeOctaveExecutable('success', $logPath);

        File::deleteDirectory($sessionDirectory);

        config([
            'cas.octave_path' => $fakeOctave,
            'cas.octave_session_directory' => $sessionDirectory,
        ]);

        $response = $this->withHeader('X-CAS-API-Key', 'test-api-key')
            ->postJson('/api/cas/execute', [
                'command' => '1 + 1',
            ]);

        $response
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'output' => 'ans = 2',
                'error' => null,
            ]);

        $log = CasLog::query()->firstOrFail();

        $this->assertSame('cas.execute', $log->command);
        $this->assertSame('success', $log->status);
        $this->assertSame('ans = 2', $log->output);
        $this->assertNull($log->error_message);
        $this->assertSame('1 + 1', $log->request_payload['command']);
        $this->assertTrue(Str::isUuid((string) $log->user_token));
    }

    public function test_failed_octave_execution_returns_error_and_creates_error_log(): void
    {
        config(['cas.api_key' => 'test-api-key']);

        $fakeOctave = $this->createFakeOctaveExecutable('fail');

        config(['cas.octave_path' => $fakeOctave]);

        $response = $this->withHeader('X-CAS-API-Key', 'test-api-key')
            ->postJson('/api/cas/execute', [
                'command' => 'unknown_function()',
            ]);

        $response
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'output' => '',
                'error' => 'fake octave error',
            ]);

        $this->assertDatabaseHas('cas_logs', [
            'command' => 'cas.execute',
            'status' => 'error',
            'error_message' => 'fake octave error',
        ]);
    }
}
