@extends('layouts.app', ['title' => __('messages.pendulum_title')])

@push('head')
    @vite(['resources/js/pendulum.js'])
@endpush

@section('content')
    <section class="page-heading">
        <p class="eyebrow">{{ __('messages.simulation_eyebrow') }}</p>
        <h1>{{ __('messages.pendulum_heading') }}</h1>
        <p>{{ __('messages.pendulum_intro') }}</p>
    </section>

    <section
        class="simulation-layout"
        data-pendulum-simulation
        data-endpoint="{{ route('simulations.pendulum.run') }}"
        data-loading-text="{{ __('messages.simulation_loading') }}"
        data-ready-text="{{ __('messages.simulation_ready') }}"
        data-playing-text="{{ __('messages.simulation_playing') }}"
        data-paused-text="{{ __('messages.simulation_paused') }}"
        data-reset-text="{{ __('messages.simulation_reset') }}"
        data-network-error="{{ __('messages.network_error') }}"
        data-invalid-data="{{ __('messages.simulation_invalid_data') }}"
        data-time-label="{{ __('messages.time_label') }}"
        data-position-label="{{ __('messages.position_label') }}"
        data-angle-label="{{ __('messages.angle_label') }}"
    >
        <form class="tool-panel simulation-controls" data-simulation-form>
            <div class="control-grid">
                <div>
                    <label for="pendulum-reference">{{ __('messages.reference_label') }}</label>
                    <input id="pendulum-reference" name="reference" type="number" min="-5" max="5" step="0.01" value="0.2" required>
                </div>
                <div>
                    <label for="pendulum-position">{{ __('messages.initial_position_label') }}</label>
                    <input id="pendulum-position" name="initial_position" type="number" min="-5" max="5" step="0.01" value="0" required>
                </div>
                <div>
                    <label for="pendulum-angle">{{ __('messages.initial_angle_label') }}</label>
                    <input id="pendulum-angle" name="initial_angle" type="number" min="-1.57" max="1.57" step="0.01" value="0" required>
                </div>
                <div>
                    <label for="pendulum-time-step">{{ __('messages.time_step_label') }}</label>
                    <input id="pendulum-time-step" name="time_step" type="number" min="0.001" max="1" step="0.001" value="0.05" required>
                </div>
                <div>
                    <label for="pendulum-duration">{{ __('messages.duration_label') }}</label>
                    <input id="pendulum-duration" name="duration" type="number" min="0.1" max="60" step="0.1" value="10" required>
                </div>
                <div>
                    <label for="pendulum-speed">{{ __('messages.speed_label') }}</label>
                    <select id="pendulum-speed" data-speed-control>
                        <option value="0.5">0.5x</option>
                        <option value="1" selected>1x</option>
                        <option value="2">2x</option>
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
                <canvas width="720" height="360" data-animation-canvas aria-label="{{ __('messages.pendulum_canvas_label') }}"></canvas>
            </div>
            <div class="chart-frame">
                <canvas data-chart-canvas aria-label="{{ __('messages.pendulum_chart_label') }}"></canvas>
            </div>
        </div>
    </section>
@endsection
