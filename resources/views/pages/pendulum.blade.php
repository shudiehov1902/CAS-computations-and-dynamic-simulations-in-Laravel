@extends('layouts.app', ['title' => __('messages.pendulum_title')])

@section('content')
    <section class="page-heading">
        <p class="eyebrow">{{ __('messages.simulation_eyebrow') }}</p>
        <h1>{{ __('messages.pendulum_heading') }}</h1>
        <p>{{ __('messages.pendulum_intro') }}</p>
    </section>

    <section class="simulation-layout">
        <form class="tool-panel">
            <label for="pendulum-reference">{{ __('messages.reference_label') }}</label>
            <input id="pendulum-reference" type="number" step="0.01" value="0.2">
            <label for="pendulum-position">{{ __('messages.initial_position_label') }}</label>
            <input id="pendulum-position" type="number" step="0.01" value="0">
            <label for="pendulum-angle">{{ __('messages.initial_angle_label') }}</label>
            <input id="pendulum-angle" type="number" step="0.01" value="0">
            <button class="button primary" type="button">{{ __('messages.run_simulation') }}</button>
        </form>

        <div class="visual-panel">
            <canvas width="720" height="360" aria-label="{{ __('messages.pendulum_canvas_label') }}"></canvas>
            <div class="chart-placeholder">{{ __('messages.chart_placeholder') }}</div>
        </div>
    </section>
@endsection
