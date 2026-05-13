@extends('layouts.app', ['title' => __('messages.statistics_title')])

@section('content')
    @php
        $animationLabel = fn (string $animationType): string => $animationType === 'ball_beam'
            ? __('messages.ball_beam_heading')
            : __('messages.pendulum_heading');
    @endphp

    <section class="page-heading">
        <p class="eyebrow">{{ __('messages.statistics_eyebrow') }}</p>
        <h1>{{ __('messages.statistics_heading') }}</h1>
        <p>{{ __('messages.statistics_intro') }}</p>
    </section>

    <section class="statistics-grid" aria-label="{{ __('messages.statistics_heading') }}">
        @foreach ($statistics as $item)
            <a
                href="{{ route('statistics', ['animation' => $item['animation_type']]) }}"
                @class([
                    'stat-card',
                    'active' => $selectedAnimation === $item['animation_type'],
                ])
            >
                <span>{{ $animationLabel($item['animation_type']) }}</span>
                <strong>{{ $item['count'] }}</strong>
                <small>{{ __('messages.statistics_total_count') }}</small>
            </a>
        @endforeach
    </section>

    <section class="tool-panel">
        <div class="section-header">
            <div>
                <h2>{{ __('messages.statistics_details_heading', ['animation' => $selectedLabel]) }}</h2>
                <p>{{ __('messages.statistics_interval_note', ['minutes' => $intervalMinutes]) }}</p>
            </div>
        </div>

        @if ($details->count() > 0)
            <div class="table-scroll">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>{{ __('messages.statistics_column_used_at') }}</th>
                            <th>{{ __('messages.statistics_column_user') }}</th>
                            <th>{{ __('messages.statistics_column_ip') }}</th>
                            <th>{{ __('messages.statistics_column_city') }}</th>
                            <th>{{ __('messages.statistics_column_country') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($details as $usage)
                            @php
                                $shortToken = strlen($usage->user_token) > 16
                                    ? substr($usage->user_token, 0, 8) . '-...-' . substr($usage->user_token, -4)
                                    : $usage->user_token;
                            @endphp
                            <tr>
                                <td>{{ $usage->used_at?->format('Y-m-d H:i:s') }}</td>
                                <td>
                                    <code class="token-code" title="{{ $usage->user_token }}">{{ $shortToken }}</code>
                                </td>
                                <td>{{ $usage->ip_address ?? '-' }}</td>
                                <td>{{ $usage->city }}</td>
                                <td>{{ $usage->country }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="pagination-shell">
                {{ $details->links() }}
            </div>
        @else
            <div class="empty-state compact">
                <h2>{{ __('messages.statistics_no_details_title') }}</h2>
                <p>{{ __('messages.statistics_no_details_text') }}</p>
            </div>
        @endif
    </section>
@endsection
