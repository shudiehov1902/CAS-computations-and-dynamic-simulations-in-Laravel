<?php

return [
    /*
    |--------------------------------------------------------------------------
    | CAS API Key
    |--------------------------------------------------------------------------
    |
    | External REST API clients must send this key with requests. The middleware
    | implemented in a later step will compare incoming API keys against it.
    |
    */

    'api_key' => env('CAS_API_KEY', 'change_me_secret_key'),

    /*
    |--------------------------------------------------------------------------
    | GNU Octave Executable
    |--------------------------------------------------------------------------
    |
    | Use "octave" when Octave is available in PATH. On machines where Octave is
    | installed elsewhere, set OCTAVE_PATH to the full executable path.
    |
    */

    'octave_path' => env('OCTAVE_PATH', 'octave'),

    'octave_timeout_seconds' => (int) env('OCTAVE_TIMEOUT_SECONDS', 10),

    'octave_session_directory' => storage_path('app/private/octave_sessions'),

    /*
    |--------------------------------------------------------------------------
    | Simulation Delay
    |--------------------------------------------------------------------------
    |
    | This default delay helps synchronize simulation playback speed between the
    | numerical data returned by Octave and the frontend animation timeline.
    |
    */

    'simulation_delay_ms' => (int) env('SIMULATION_DELAY_MS', 50),

    /*
    |--------------------------------------------------------------------------
    | Statistics Counting Interval
    |--------------------------------------------------------------------------
    |
    | Repeated launches of the same animation by the same anonymous user are only
    | counted again after this interval.
    |
    */

    'statistics_interval_minutes' => (int) env('STATISTICS_INTERVAL_MINUTES', 10),
];
