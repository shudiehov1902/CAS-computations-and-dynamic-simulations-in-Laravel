@extends('layouts.app', ['title' => __('messages.ball_beam_title')])

@push('head')
    @vite(['resources/js/ball-beam.js'])
@endpush

@section('content')
    <section class="page-heading">
        <p class="eyebrow">{{ __('messages.simulation_eyebrow') }}</p>
        <h1>{{ __('messages.ball_beam_heading') }}</h1>
        <p>{{ __('messages.ball_beam_intro') }}</p>
    </section>

    <section
        class="simulation-layout"
        data-ball-beam-simulation
        data-endpoint="{{ route('simulations.ball-beam.run') }}"
        data-loading-text="{{ __('messages.simulation_loading') }}"
        data-ready-text="{{ __('messages.simulation_ready') }}"
        data-playing-text="{{ __('messages.simulation_playing') }}"
        data-paused-text="{{ __('messages.simulation_paused') }}"
        data-reset-text="{{ __('messages.simulation_reset') }}"
        data-network-error="{{ __('messages.network_error') }}"
        data-invalid-data="{{ __('messages.simulation_invalid_data') }}"
        data-time-label="{{ __('messages.time_label') }}"
        data-ball-position-label="{{ __('messages.ball_position_label') }}"
        data-beam-angle-label="{{ __('messages.beam_angle_label') }}"
    >
        <form class="tool-panel simulation-controls" data-simulation-form>
            <div class="control-grid">
                <div>
                    <label for="ball-reference">{{ __('messages.reference_label') }}</label>
                    <input id="ball-reference" name="reference" type="number" min="-0.5" max="0.5" step="0.01" value="0.25" required>
                </div>
                <div>
                    <label for="ball-speed">{{ __('messages.initial_velocity_label') }}</label>
                    <input id="ball-speed" name="initial_velocity" type="number" min="-0.5" max="0.5" step="0.01" value="0" required>
                </div>
                <div>
                    <label for="ball-acceleration">{{ __('messages.initial_acceleration_label') }}</label>
                    <input id="ball-acceleration" name="initial_acceleration" type="number" min="-0.35" max="0.35" step="0.01" value="0" required>
                </div>
                <div>
                    <label for="ball-time-step">{{ __('messages.time_step_label') }}</label>
                    <input id="ball-time-step" name="time_step" type="number" min="0.001" max="1" step="0.001" value="0.01" required>
                </div>
                <div>
                    <label for="ball-duration">{{ __('messages.duration_label') }}</label>
                    <input id="ball-duration" name="duration" type="number" min="0.1" max="60" step="0.1" value="5" required>
                </div>
                <div>
                    <label for="ball-speed-control">{{ __('messages.speed_label') }}</label>
                    <select id="ball-speed-control" data-speed-control>
                        <option value="0.1">{{ __('messages.speed_0_1x') }}</option>
                        <option value="0.25">{{ __('messages.speed_0_25x') }}</option>
                        <option value="0.5">{{ __('messages.speed_0_5x') }}</option>
                        <option value="1" selected>{{ __('messages.speed_1x') }}</option>
                        <option value="2">{{ __('messages.speed_2x') }}</option>
                    </select>
                </div>
            </div>

            <div class="form-actions">
                <button class="button primary" type="submit" data-run-button>{{ __('messages.run_simulation') }}</button>
                <button class="button secondary" type="button" data-play-button disabled>{{ __('messages.play_simulation') }}</button>
                <button class="button secondary" type="button" data-pause-button disabled>{{ __('messages.pause_simulation') }}</button>
                <button class="button secondary" type="button" data-reset-button disabled>{{ __('messages.reset_simulation') }}</button>
            </div>

            <span class="inline-status" data-simulation-status role="status" aria-live="polite"></span>
            <div class="simulation-error" data-simulation-error role="alert" hidden></div>
        </form>

        <div class="visual-panel">
            <div class="canvas-frame">
                <canvas width="720" height="360" data-animation-canvas aria-label="{{ __('messages.ball_beam_canvas_label') }}"></canvas>
            </div>
            <div class="chart-frame">
                <canvas data-chart-canvas aria-label="{{ __('messages.ball_beam_chart_label') }}"></canvas>
            </div>
        </div>
    </section>
@endsection
