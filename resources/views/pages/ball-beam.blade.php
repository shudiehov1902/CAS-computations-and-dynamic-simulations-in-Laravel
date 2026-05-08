@extends('layouts.app', ['title' => __('messages.ball_beam_title')])

@section('content')
    <section class="page-heading">
        <p class="eyebrow">{{ __('messages.simulation_eyebrow') }}</p>
        <h1>{{ __('messages.ball_beam_heading') }}</h1>
        <p>{{ __('messages.ball_beam_intro') }}</p>
    </section>

    <section class="simulation-layout">
        <form class="tool-panel">
            <label for="ball-reference">{{ __('messages.reference_label') }}</label>
            <input id="ball-reference" type="number" step="0.01" value="0.25">
            <label for="ball-speed">{{ __('messages.initial_velocity_label') }}</label>
            <input id="ball-speed" type="number" step="0.01" value="0">
            <label for="ball-acceleration">{{ __('messages.initial_acceleration_label') }}</label>
            <input id="ball-acceleration" type="number" step="0.01" value="0">
            <button class="button primary" type="button">{{ __('messages.run_simulation') }}</button>
        </form>

        <div class="visual-panel">
            <canvas width="720" height="360" aria-label="{{ __('messages.ball_beam_canvas_label') }}"></canvas>
            <div class="chart-placeholder">{{ __('messages.chart_placeholder') }}</div>
        </div>
    </section>
@endsection
