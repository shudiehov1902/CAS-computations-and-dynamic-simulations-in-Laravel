<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;
use Throwable;

class OctaveService
{
    /**
     * @return array{output: string, session_file: string}
     */
    public function execute(string $command, string $userToken): array
    {
        $command = trim($command);

        $this->ensureCommandIsAllowed($command);

        $sessionFile = $this->sessionFilePath($userToken);
        $temporaryDirectory = storage_path('app/private/octave_temp');

        File::ensureDirectoryExists(dirname($sessionFile));
        File::ensureDirectoryExists($temporaryDirectory);

        $commandFile = tempnam($temporaryDirectory, 'command_');
        $wrapperFile = tempnam($temporaryDirectory, 'wrapper_');

        if ($commandFile === false || $wrapperFile === false) {
            throw new OctaveExecutionException('Unable to create temporary Octave files.', 500);
        }

        try {
            File::put($commandFile, $command.PHP_EOL);
            File::put($wrapperFile, $this->buildWrapperScript($commandFile, $sessionFile));

            $process = new Process([
                (string) config('cas.octave_path'),
                '--quiet',
                '--no-gui',
                '--no-window-system',
                $wrapperFile,
            ]);

            $process->setTimeout((int) config('cas.octave_timeout_seconds', 10));
            $process->run();

            $output = trim($process->getOutput());
            $errorOutput = trim($process->getErrorOutput());

            if (! $process->isSuccessful()) {
                throw new OctaveExecutionException(
                    $errorOutput !== '' ? $errorOutput : 'Octave command failed.',
                    422,
                    $output
                );
            }

            return [
                'output' => $output,
                'session_file' => $sessionFile,
            ];
        } catch (ProcessTimedOutException) {
            throw new OctaveExecutionException('Octave execution timed out.', 504);
        } catch (OctaveExecutionException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new OctaveExecutionException(
                'Unable to start or complete Octave execution: '.$exception->getMessage(),
                500
            );
        } finally {
            $this->deleteTemporaryFile($commandFile);
            $this->deleteTemporaryFile($wrapperFile);
        }
    }

    /**
     * Run a generated Octave script that does not need CAS session persistence.
     *
     * @return array{output: string}
     */
    public function executeScript(string $script): array
    {
        $temporaryDirectory = storage_path('app/private/octave_temp');

        File::ensureDirectoryExists($temporaryDirectory);

        $scriptFile = tempnam($temporaryDirectory, 'simulation_');

        if ($scriptFile === false) {
            throw new OctaveExecutionException('Unable to create temporary Octave script.', 500);
        }

        try {
            File::put($scriptFile, $script);

            $process = new Process([
                (string) config('cas.octave_path'),
                '--quiet',
                '--no-gui',
                '--no-window-system',
                $scriptFile,
            ]);

            $process->setTimeout((int) config('cas.octave_timeout_seconds', 10));
            $process->run();

            $output = trim($process->getOutput());
            $errorOutput = trim($process->getErrorOutput());

            if (! $process->isSuccessful()) {
                throw new OctaveExecutionException(
                    $errorOutput !== '' ? $errorOutput : 'Octave script failed.',
                    422,
                    $output
                );
            }

            return [
                'output' => $output,
            ];
        } catch (ProcessTimedOutException) {
            throw new OctaveExecutionException('Octave execution timed out.', 504);
        } catch (OctaveExecutionException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new OctaveExecutionException(
                'Unable to start or complete Octave execution: '.$exception->getMessage(),
                500
            );
        } finally {
            $this->deleteTemporaryFile($scriptFile);
        }
    }

    public function sessionFilePath(string $userToken): string
    {
        $safeToken = preg_replace('/[^A-Za-z0-9-]/', '', $userToken) ?: 'anonymous';

        return rtrim((string) config('cas.octave_session_directory'), DIRECTORY_SEPARATOR)
            .DIRECTORY_SEPARATOR
            .$safeToken
            .'.m';
    }

    private function ensureCommandIsAllowed(string $command): void
    {
        if (preg_match('/(^|\R)\s*!/', $command) === 1) {
            throw new OctaveExecutionException('Command contains a blocked shell escape.', 422);
        }

        if (preg_match('/\b(system|unix|dos|popen)\s*\(/i', $command) === 1) {
            throw new OctaveExecutionException('Command contains a blocked Octave operation.', 422);
        }
    }

    private function buildWrapperScript(string $commandFile, string $sessionFile): string
    {
        return sprintf(
            <<<'OCTAVE'
more off;
page_screen_output(0);
page_output_immediately(1);

function __cas_run__()
  __cas_session_file__ = %s;
  __cas_command_file__ = %s;

  if exist(__cas_session_file__, 'file') == 2
    source(__cas_session_file__);
  endif

  try
    source(__cas_command_file__);

    __cas_vars__ = who();
    __cas_keep__ = {};

    for __cas_i__ = 1:numel(__cas_vars__)
      __cas_name__ = __cas_vars__{__cas_i__};

      if isempty(regexp(__cas_name__, '^__cas_', 'once'))
        __cas_keep__{end + 1} = __cas_name__;
      endif
    endfor

    if numel(__cas_keep__) > 0
      save('-text', __cas_session_file__, __cas_keep__{:});
    else
      __cas_fid__ = fopen(__cas_session_file__, 'w');

      if __cas_fid__ >= 0
        fclose(__cas_fid__);
      endif
    endif
  catch __cas_error__
    fprintf(2, 'Octave error: %%s\n', __cas_error__.message);
    exit(1);
  end_try_catch
endfunction

__cas_run__();
OCTAVE,
            $this->octaveString($sessionFile),
            $this->octaveString($commandFile)
        );
    }

    private function octaveString(string $value): string
    {
        return "'".str_replace("'", "''", $value)."'";
    }

    private function deleteTemporaryFile(string|false $path): void
    {
        if (is_string($path) && File::exists($path)) {
            File::delete($path);
        }
    }
}
