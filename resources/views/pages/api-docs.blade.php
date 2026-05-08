@extends('layouts.app', ['title' => __('messages.api_docs_title')])

@section('content')
    <section class="page-heading">
        <p class="eyebrow">{{ __('messages.api_docs_eyebrow') }}</p>
        <h1>{{ __('messages.api_docs_heading') }}</h1>
        <p>{{ __('messages.api_docs_intro') }}</p>
    </section>

    <section class="tool-panel">
        <h2>{{ __('messages.planned_endpoints') }}</h2>
        <ul class="endpoint-list">
            <li><code>POST /api/cas/execute</code></li>
            <li><code>POST /api/simulations/pendulum</code></li>
            <li><code>POST /api/simulations/ball-beam</code></li>
            <li><code>GET /api/logs</code></li>
            <li><code>GET /api/logs/export</code></li>
            <li><code>GET /api/statistics</code></li>
            <li><code>GET /api/statistics/{animation}</code></li>
            <li><code>GET /api/openapi</code></li>
            <li><code>GET /api/docs/pdf</code></li>
        </ul>
    </section>
@endsection
