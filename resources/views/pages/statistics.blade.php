@extends('layouts.app', ['title' => __('messages.statistics_title')])

@section('content')
    <section class="page-heading">
        <p class="eyebrow">{{ __('messages.statistics_eyebrow') }}</p>
        <h1>{{ __('messages.statistics_heading') }}</h1>
        <p>{{ __('messages.statistics_intro') }}</p>
    </section>

    <section class="feature-grid">
        <article>
            <h2>{{ __('messages.pendulum_heading') }}</h2>
            <p>{{ __('messages.statistics_placeholder') }}</p>
        </article>
        <article>
            <h2>{{ __('messages.ball_beam_heading') }}</h2>
            <p>{{ __('messages.statistics_placeholder') }}</p>
        </article>
    </section>
@endsection
