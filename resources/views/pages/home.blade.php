@extends('layouts.app', ['title' => __('messages.home_title')])

@section('content')
    <section class="hero">
        <div>
            <h1>{{ __('messages.home_heading') }}</h1>
            <div class="actions">
                <a class="button primary" href="{{ route('cas-console') }}">{{ __('messages.open_cas') }}</a>
                <a class="button secondary" href="{{ route('pendulum') }}">{{ __('messages.open_simulations') }}</a>
            </div>
        </div>
    </section>

    <section class="feature-grid" aria-label="{{ __('messages.feature_overview') }}">
        <article>
            <h2>{{ __('messages.feature_cas_title') }}</h2>
            <p>{{ __('messages.feature_cas_text') }}</p>
        </article>
        <article>
            <h2>{{ __('messages.feature_simulations_title') }}</h2>
            <p>{{ __('messages.feature_simulations_text') }}</p>
        </article>
        <article>
            <h2>{{ __('messages.feature_docs_title') }}</h2>
            <p>{{ __('messages.feature_docs_text') }}</p>
        </article>
    </section>
@endsection
