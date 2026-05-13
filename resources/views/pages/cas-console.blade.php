@extends('layouts.app', ['title' => __('messages.cas_title')])

@push('head')
    @vite(['resources/js/cas-console.js'])
@endpush

@section('content')
    <section class="page-heading">
        <p class="eyebrow">{{ __('messages.cas_eyebrow') }}</p>
        <h1>{{ __('messages.cas_heading') }}</h1>
        <p>{{ __('messages.cas_intro') }}</p>
    </section>

    <section
        class="tool-panel cas-console-panel"
        data-cas-console
        data-endpoint="{{ route('cas-console.execute') }}"
        data-loading-text="{{ __('messages.loading_command') }}"
        data-empty-text="{{ __('messages.cas_empty_command') }}"
        data-network-error="{{ __('messages.network_error') }}"
        data-output-placeholder="{{ __('messages.output_placeholder') }}"
    >
        <form class="cas-console-form">
            <label for="cas-command">{{ __('messages.cas_command_label') }}</label>
            <textarea id="cas-command" rows="10">1 + 1</textarea>
            <div class="editor-shell" data-editor-host></div>

            <div class="form-actions">
                <button class="button primary" type="submit" data-run-button>{{ __('messages.run_command') }}</button>
                <span class="inline-status" data-status role="status" aria-live="polite"></span>
            </div>
        </form>

        <div>
            <h2>{{ __('messages.output_heading') }}</h2>
            <pre class="output-panel" data-output>{{ __('messages.output_placeholder') }}</pre>
        </div>
    </section>
@endsection
