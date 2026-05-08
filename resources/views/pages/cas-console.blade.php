@extends('layouts.app', ['title' => __('messages.cas_title')])

@section('content')
    <section class="page-heading">
        <p class="eyebrow">{{ __('messages.cas_eyebrow') }}</p>
        <h1>{{ __('messages.cas_heading') }}</h1>
        <p>{{ __('messages.cas_intro') }}</p>
    </section>

    <section class="tool-panel">
        <label for="cas-command">{{ __('messages.cas_command_label') }}</label>
        <textarea id="cas-command" rows="10" placeholder="a = 1 + 1"></textarea>
        <button class="button primary" type="button">{{ __('messages.run_command') }}</button>
        <pre class="output-panel">{{ __('messages.output_placeholder') }}</pre>
    </section>
@endsection
