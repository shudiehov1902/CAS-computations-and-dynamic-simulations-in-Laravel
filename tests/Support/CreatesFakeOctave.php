<?php

namespace Tests\Support;

use Illuminate\Support\Facades\File;

trait CreatesFakeOctave
{
    protected function createFakeOctaveExecutable(string $mode = 'success', ?string $logPath = null): string
    {
        $directory = storage_path('framework/testing/fake-octave');

        File::ensureDirectoryExists($directory);

        $path = $directory.'/fake-octave-'.uniqid('', true).'.php';
        $logPath ??= $directory.'/fake-octave-'.uniqid('', true).'.json';

        $script = sprintf(
            <<<'PHP'
#!/usr/bin/env php
<?php

$mode = %s;
$logPath = %s;
$arguments = array_slice($argv, 1);
$wrapperFile = end($arguments);
$wrapperContent = is_string($wrapperFile) && is_file($wrapperFile) ? file_get_contents($wrapperFile) : '';

preg_match("/__cas_session_file__\s*=\s*'([^']+)'/", $wrapperContent, $sessionMatch);
preg_match("/__cas_command_file__\s*=\s*'([^']+)'/", $wrapperContent, $commandMatch);

$sessionFile = $sessionMatch[1] ?? null;
$commandFile = $commandMatch[1] ?? null;
$commandContent = $commandFile && is_file($commandFile) ? file_get_contents($commandFile) : '';

file_put_contents($logPath, json_encode([
    'arguments' => $arguments,
    'wrapper_file' => $wrapperFile,
    'session_file' => $sessionFile,
    'command_file' => $commandFile,
    'command_content' => $commandContent,
], JSON_PRETTY_PRINT));

if ($mode === 'sleep') {
    sleep(5);
    exit(0);
}

if ($mode === 'pendulum') {
    echo "__WEBTE2_PENDULUM_JSON_START__\n";
    echo '{"time":[0,0.05,0.1],"position":[0,0.01,0.02],"angle":[0,-0.001,-0.002]}'."\n";
    echo "__WEBTE2_PENDULUM_JSON_END__\n";
    exit(0);
}

if ($mode === 'pendulum-malformed') {
    echo "__WEBTE2_PENDULUM_JSON_START__\n";
    echo '{"time":[0],"position":'."\n";
    echo "__WEBTE2_PENDULUM_JSON_END__\n";
    exit(0);
}

if ($mode === 'ball-beam') {
    echo "__WEBTE2_BALL_BEAM_JSON_START__\n";
    echo '{"time":[0,0.01,0.02],"ball_position":[0,0.01,0.02],"beam_angle":[0,0.0001,0.00008]}'."\n";
    echo "__WEBTE2_BALL_BEAM_JSON_END__\n";
    exit(0);
}

if ($mode === 'ball-beam-malformed') {
    echo "__WEBTE2_BALL_BEAM_JSON_START__\n";
    echo '{"time":[0],"ball_position":'."\n";
    echo "__WEBTE2_BALL_BEAM_JSON_END__\n";
    exit(0);
}

if ($mode === 'fail') {
    fwrite(STDERR, "fake octave error\n");
    exit(1);
}

if ($sessionFile !== null) {
    if (! is_dir(dirname($sessionFile))) {
        mkdir(dirname($sessionFile), 0777, true);
    }

    file_put_contents($sessionFile, "# fake session\n");
}

echo "ans = 2\n";
exit(0);
PHP,
            var_export($mode, true),
            var_export($logPath, true)
        );

        File::put($path, $script);
        chmod($path, 0755);

        return $path;
    }

    protected function fakeOctaveLogPath(string $name): string
    {
        $directory = storage_path('framework/testing/fake-octave');

        File::ensureDirectoryExists($directory);

        return $directory.'/'.$name.'-'.uniqid('', true).'.json';
    }
}
