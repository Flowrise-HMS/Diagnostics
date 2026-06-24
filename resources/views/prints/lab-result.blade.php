<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lab Result — {{ $subject['name'] ?? 'Report' }}</title>
    <style>
        :root {
            color-scheme: light;
            font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            line-height: 1.5;
            color: #111827;
        }

        body {
            margin: 0;
            padding: 24px;
            background: #f3f4f6;
        }

        .sheet {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 32px;
        }

        .toolbar {
            max-width: 800px;
            margin: 0 auto 16px;
            display: flex;
            gap: 12px;
        }

        .toolbar button {
            border: 0;
            border-radius: 6px;
            background: #111827;
            color: #fff;
            padding: 10px 16px;
            cursor: pointer;
            font: inherit;
        }

        .header {
            display: flex;
            justify-content: space-between;
            gap: 24px;
            border-bottom: 2px solid #111827;
            padding-bottom: 16px;
            margin-bottom: 24px;
        }

        .org-name {
            font-size: 1.25rem;
            font-weight: 700;
        }

        .branch-name,
        .meta {
            color: #4b5563;
            font-size: 0.875rem;
        }

        .report-title {
            font-size: 1.125rem;
            font-weight: 700;
            margin: 0 0 8px;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px 24px;
            margin-bottom: 24px;
        }

        .label {
            display: block;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #6b7280;
            margin-bottom: 2px;
        }

        table.results {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
        }

        table.results th,
        table.results td {
            border: 1px solid #d1d5db;
            padding: 10px 12px;
            text-align: left;
            vertical-align: top;
        }

        table.results th {
            background: #f9fafb;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .notes,
        .footer {
            font-size: 0.875rem;
            color: #374151;
        }

        .footer {
            margin-top: 32px;
            padding-top: 16px;
            border-top: 1px solid #e5e7eb;
        }

        @media print {
            body {
                background: #fff;
                padding: 0;
            }

            .toolbar {
                display: none;
            }

            .sheet {
                border: 0;
                border-radius: 0;
                padding: 0;
                max-width: none;
            }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button type="button" onclick="window.print()">Print</button>
        <button type="button" onclick="window.close()">Close</button>
    </div>

    <div class="sheet">
        <header class="header">
            <div>
                <div class="org-name">{{ $organization?->display_name ?? $organization?->name ?? config('app.name') }}</div>
                @if ($branch)
                    <div class="branch-name">{{ $branch->display_name ?? $branch->name }}</div>
                @endif
                @if ($branch?->contact['phone'] ?? null)
                    <div class="meta">Tel: {{ $branch->contact['phone'] }}</div>
                @endif
            </div>
            <div class="meta" style="text-align: right;">
                <div><strong>Request:</strong> {{ $requestNumber ?? '—' }}</div>
                @if ($reportVersion)
                    <div><strong>Report v{{ $reportVersion }}</strong> ({{ ucfirst(is_string($reportStatus) ? $reportStatus : ($reportStatus?->value ?? 'final')) }})</div>
                @endif
                <div><strong>Printed:</strong> {{ now()->format('Y-m-d H:i') }}</div>
            </div>
        </header>

        <h1 class="report-title">Laboratory Result Report</h1>

        <div class="grid">
            <div>
                <span class="label">{{ ($subject['type'] ?? 'patient') === 'guest' ? 'Guest' : 'Patient' }}</span>
                <strong>{{ $subject['name'] ?? '—' }}</strong>
            </div>
            <div>
                <span class="label">Test</span>
                <strong>{{ $serviceName }}</strong>
            </div>
            @if (! empty($subject['identifier']))
                <div>
                    <span class="label">{{ $subject['identifier_label'] ?? 'Identifier' }}</span>
                    <span>{{ $subject['identifier'] }}</span>
                </div>
            @endif
            @if (! empty($subject['age']))
                <div>
                    <span class="label">Age</span>
                    <span>{{ $subject['age'] }} years</span>
                </div>
            @endif
            @if (! empty($subject['gender']))
                <div>
                    <span class="label">Gender</span>
                    <span>{{ $subject['gender'] }}</span>
                </div>
            @endif
            @if (! empty($subject['phone']))
                <div>
                    <span class="label">Phone</span>
                    <span>{{ $subject['phone'] }}</span>
                </div>
            @endif
            @if ($collectedAt)
                <div>
                    <span class="label">Collected</span>
                    <span>{{ $collectedAt->format('Y-m-d H:i') }}</span>
                </div>
            @endif
            @if ($reportedAt)
                <div>
                    <span class="label">Reported</span>
                    <span>{{ $reportedAt->format('Y-m-d H:i') }}</span>
                </div>
            @endif
        </div>

        <table class="results">
            <thead>
                <tr>
                    <th>Test / Analyte</th>
                    <th>Result</th>
                    @if ($resultRows->contains(fn ($row) => ! empty($row['reference'])))
                        <th>Reference</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @foreach ($resultRows as $row)
                    <tr>
                        <td>{{ $row['label'] }}</td>
                        <td><strong>{{ $row['value'] }}</strong></td>
                        @if ($resultRows->contains(fn ($item) => ! empty($item['reference'])))
                            <td>{{ $row['reference'] ?? '—' }}</td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>

        @if (! empty($notes))
            <div class="notes">
                <span class="label">Notes</span>
                <p>{{ $notes }}</p>
            </div>
        @endif

        <div class="footer">
            @if ($performedBy)
                <div><strong>Performed by:</strong> {{ $performedBy }}</div>
            @endif
            @if ($signatures->isNotEmpty())
                <div style="margin-top: 12px;">
                    <span class="label">Signatures</span>
                    @foreach ($signatures as $signature)
                        <div>{{ $signature->signedBy?->name ?? 'Staff' }}@if ($signature->role) ({{ str_replace('_', ' ', $signature->role) }})@endif — {{ $signature->signed_at?->format('Y-m-d H:i') }}</div>
                    @endforeach
                </div>
            @endif
            <div style="margin-top: 16px; font-size: 0.75rem; color: #6b7280;">
                This report is generated from the facility laboratory information system. For clinical interpretation, consult a qualified healthcare provider.
            </div>
        </div>
    </div>

    @if (request()->boolean('auto'))
        <script>window.addEventListener('load', () => window.print());</script>
    @endif
</body>
</html>
