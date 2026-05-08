<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <title>{{ $info['title'] ?? 'API Documentation' }}</title>
        <style>
            @page {
                margin: 86px 42px 64px;
            }

            body {
                color: #18181b;
                font-family: DejaVu Sans, sans-serif;
                font-size: 11px;
                line-height: 1.45;
            }

            h1,
            h2,
            h3 {
                color: #064e3b;
                margin: 0;
            }

            h1 {
                font-size: 25px;
                margin-bottom: 8px;
            }

            h2 {
                border-bottom: 1px solid #d4d4d8;
                font-size: 18px;
                margin-top: 24px;
                padding-bottom: 6px;
            }

            h3 {
                font-size: 13px;
                margin-bottom: 6px;
            }

            p {
                margin: 4px 0 10px;
            }

            pre {
                background: #f4f4f5;
                border: 1px solid #e4e4e7;
                border-radius: 4px;
                color: #27272a;
                font-family: DejaVu Sans Mono, monospace;
                font-size: 8.5px;
                line-height: 1.35;
                margin: 6px 0 10px;
                padding: 8px;
                white-space: pre-wrap;
                word-wrap: break-word;
            }

            .document-header {
                border-bottom: 3px solid #047857;
                margin-bottom: 18px;
                padding-bottom: 12px;
            }

            .meta {
                color: #52525b;
                font-size: 10px;
            }

            .security-note {
                background: #ecfdf5;
                border: 1px solid #a7f3d0;
                border-radius: 6px;
                margin: 14px 0;
                padding: 10px;
            }

            .endpoint {
                border: 1px solid #d4d4d8;
                border-radius: 6px;
                margin: 12px 0;
                padding: 10px;
                page-break-inside: avoid;
            }

            .endpoint-title {
                font-size: 12px;
                font-weight: 700;
                margin-bottom: 6px;
            }

            .method {
                background: #047857;
                border-radius: 3px;
                color: #fff;
                display: inline-block;
                font-size: 9px;
                font-weight: 700;
                margin-right: 6px;
                padding: 3px 6px;
            }

            .path {
                font-family: DejaVu Sans Mono, monospace;
            }

            .auth {
                color: #047857;
                font-size: 9px;
                font-weight: 700;
                text-transform: uppercase;
            }

            .example-title {
                color: #3f3f46;
                font-size: 10px;
                font-weight: 700;
                margin-top: 10px;
            }

            .footer {
                bottom: -42px;
                color: #71717a;
                font-size: 9px;
                left: 0;
                position: fixed;
                right: 0;
                text-align: center;
            }

            .footer .page-number::before {
                content: counter(page);
            }

            .footer .page-count::before {
                content: counter(pages);
            }
        </style>
    </head>
    <body>
        <div class="footer">
            Page <span class="page-number"></span> / <span class="page-count"></span>
        </div>

        <header class="document-header">
            <h1>{{ $info['title'] ?? 'API Documentation' }}</h1>
            <p>{{ $info['description'] ?? '' }}</p>
            <p class="meta">
                Version: {{ $info['version'] ?? '1.0.0' }} |
                Generated: {{ $generatedAt }}
            </p>
        </header>

        <section class="security-note">
            <h3>Authentication</h3>
            <p>
                Protected REST API endpoints require the
                <strong>X-CAS-API-Key</strong> header. Browser pages do not expose the secret key.
            </p>
        </section>

        @foreach ($groups as $group)
            <section>
                <h2>{{ $group['name'] }}</h2>

                @if ($group['description'] !== '')
                    <p>{{ $group['description'] }}</p>
                @endif

                @foreach ($group['endpoints'] as $endpoint)
                    <article class="endpoint">
                        <div class="endpoint-title">
                            <span class="method">{{ $endpoint['method'] }}</span>
                            <span class="path">{{ $endpoint['path'] }}</span>
                        </div>

                        <p><strong>{{ $endpoint['summary'] }}</strong></p>

                        @if ($endpoint['description'] !== '')
                            <p>{{ $endpoint['description'] }}</p>
                        @endif

                        @if ($endpoint['requiresAuth'])
                            <p class="auth">Requires API key</p>
                        @endif

                        @foreach ($endpoint['requestExamples'] as $example)
                            <div class="example-title">
                                Request example{{ $example['summary'] !== '' ? ': '.$example['summary'] : '' }}
                            </div>
                            <pre>{{ $example['body'] }}</pre>
                        @endforeach

                        @foreach ($endpoint['responseExamples'] as $example)
                            <div class="example-title">
                                Response {{ $example['status'] }} ({{ $example['contentType'] }})
                            </div>
                            <pre>{{ $example['body'] }}</pre>
                        @endforeach
                    </article>
                @endforeach
            </section>
        @endforeach
    </body>
</html>
