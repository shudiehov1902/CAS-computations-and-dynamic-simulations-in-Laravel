@extends('layouts.app', ['title' => __('messages.api_docs_title')])

@push('head')
    @vite(['resources/js/api-docs.js'])
@endpush

@section('content')
    <section class="page-heading">
        <p class="eyebrow">{{ __('messages.api_docs_eyebrow') }}</p>
        <h1>{{ __('messages.api_docs_heading') }}</h1>
        <p>{{ __('messages.api_docs_intro') }}</p>
    </section>

    <section class="swagger-panel" aria-label="{{ __('messages.api_docs_heading') }}">
        <div id="swagger-ui" data-openapi-url="{{ route('openapi.public') }}"></div>
    </section>
@endsection
