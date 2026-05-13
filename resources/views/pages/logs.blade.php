@extends('layouts.app', ['title' => __('messages.logs_title')])

@section('content')
    <section class="page-heading">
        <p class="eyebrow">{{ __('messages.logs_eyebrow') }}</p>
        <h1>{{ __('messages.logs_heading') }}</h1>
        <p>{{ __('messages.logs_intro') }}</p>
    </section>

    <div class="table-actions">
        <a class="button primary" href="{{ route('logs.export') }}">{{ __('messages.logs_export_csv') }}</a>
    </div>

    @if ($logs->count() > 0)
        <section class="tool-panel">
            <div class="table-scroll">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>{{ __('messages.logs_column_time') }}</th>
                            <th>{{ __('messages.logs_column_command') }}</th>
                            <th>{{ __('messages.logs_column_status') }}</th>
                            <th>{{ __('messages.logs_column_payload') }}</th>
                            <th>{{ __('messages.logs_column_output') }}</th>
                            <th>{{ __('messages.logs_column_error') }}</th>
                            <th>{{ __('messages.logs_column_ip') }}</th>
                            <th>{{ __('messages.logs_column_user') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($logs as $log)
                            @php
                                $payload = $log->request_payload === null
                                    ? ''
                                    : json_encode($log->request_payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                                $output = (string) ($log->output ?? '');
                                $error = (string) ($log->error_message ?? '');
                                $shortToken = $log->user_token && strlen($log->user_token) > 16
                                    ? substr($log->user_token, 0, 8) . '-...-' . substr($log->user_token, -4)
                                    : $log->user_token;
                            @endphp

                            <tr>
                                <td>{{ $log->created_at?->format('Y-m-d H:i:s') }}</td>
                                <td><code>{{ $log->command }}</code></td>
                                <td>
                                    <span @class([
                                        'status-badge',
                                        'success' => $log->status === 'success',
                                        'error' => $log->status === 'error',
                                    ])>
                                        {{ $log->status }}
                                    </span>
                                </td>
                                <td>
                                    @if ($payload !== '')
                                        <code class="long-code" title="{{ $payload }}">{{ \Illuminate\Support\Str::limit($payload, 140) }}</code>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if ($output !== '')
                                        <code class="long-code" title="{{ $output }}">{{ \Illuminate\Support\Str::limit($output, 140) }}</code>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if ($error !== '')
                                        <code class="long-code" title="{{ $error }}">{{ \Illuminate\Support\Str::limit($error, 140) }}</code>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>{{ $log->ip_address ?? '-' }}</td>
                                <td>
                                    @if ($log->user_token)
                                        <code class="token-code" title="{{ $log->user_token }}">{{ $shortToken }}</code>
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="pagination-shell">
                {{ $logs->links() }}
            </div>
        </section>
    @else
        <section class="empty-state">
            <h2>{{ __('messages.logs_empty_title') }}</h2>
            <p>{{ __('messages.logs_empty_text') }}</p>
        </section>
    @endif
@endsection
