<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        @page { margin: 20px 22px 26px; }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9px;
            color: #0f172a;
            background: #eef2ff;
            margin: 0;
            padding: 0;
        }

        p { margin: 0; }

        .cover {
            background: #ffffff;
            border: 1px solid #c7d2fe;
            border-radius: 16px;
            padding: 16px;
            margin-bottom: 12px;
        }

        .brand {
            color: #4f46e5;
            font-size: 8.5px;
            font-weight: bold;
            letter-spacing: 1.6px;
            text-transform: uppercase;
            margin: 0 0 8px;
        }

        .header { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .header td { vertical-align: top; padding: 0; }

        h1 {
            font-size: 22px;
            color: #0f172a;
            margin: 0 0 6px;
            font-weight: bold;
        }

        .meta-line { color: #64748b; font-size: 8px; line-height: 1.45; }
        .gen-label { color: #94a3b8; font-size: 7px; text-align: right; margin-bottom: 2px; text-transform: uppercase; }
        .gen-value { color: #312e81; font-size: 9px; font-weight: bold; text-align: right; }

        .hero {
            background: #4f46e5;
            border-radius: 12px;
            padding: 12px 14px;
            color: #ffffff;
            margin-bottom: 12px;
        }

        .hero .eyebrow { font-size: 7px; letter-spacing: 1.2px; text-transform: uppercase; opacity: 0.85; margin-bottom: 3px; }
        .hero .title { font-size: 13px; font-weight: bold; margin-bottom: 3px; }
        .hero .desc { font-size: 8px; opacity: 0.92; }

        .grid {
            width: 100%;
            border-collapse: separate;
            border-spacing: 8px;
            margin: 0 0 4px -8px;
        }

        .kpi {
            text-align: center;
            border-radius: 12px;
            padding: 11px 8px;
            border: 1px solid #c7d2fe;
            background: #ffffff;
        }

        .kpi .label {
            color: #64748b;
            font-size: 6.5px;
            font-weight: bold;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .kpi .value {
            color: #0f172a;
            font-size: 12px;
            font-weight: bold;
        }

        .kpi .sub {
            color: #64748b;
            font-size: 6.5px;
            margin-top: 3px;
            line-height: 1.3;
        }

        .kpi-mint { background: #ecfdf5; border-color: #a7f3d0; }
        .kpi-blue { background: #eff6ff; border-color: #bfdbfe; }
        .kpi-violet { background: #f5f3ff; border-color: #ddd6fe; }
        .kpi-amber { background: #fffbeb; border-color: #fde68a; }

        .section-break { page-break-before: always; }

        .section-head {
            background: #ffffff;
            border: 1px solid #c7d2fe;
            border-radius: 12px;
            padding: 11px 13px;
            margin-bottom: 10px;
        }

        .section-kicker {
            color: #4f46e5;
            font-size: 7px;
            font-weight: bold;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 3px;
        }

        .section-title {
            font-size: 13px;
            font-weight: bold;
            color: #0f172a;
            margin: 0 0 3px;
        }

        .section-sub { color: #64748b; font-size: 8px; }

        .panel {
            background: #ffffff;
            border: 1px solid #c7d2fe;
            border-radius: 12px;
            padding: 12px;
            margin-bottom: 10px;
            page-break-inside: avoid;
        }

        .panel-accent {
            width: 28px;
            height: 3px;
            background: #4f46e5;
            border-radius: 99px;
            margin-bottom: 8px;
        }

        .panel-title {
            font-size: 11px;
            font-weight: bold;
            color: #0f172a;
            margin: 0 0 8px;
        }

        .chart-frame {
            background: #ffffff;
            border: 1px solid #e0e7ff;
            border-radius: 10px;
            padding: 8px;
        }

        .chart-frame img {
            width: 100%;
            background: #ffffff;
            display: block;
        }

        table.data {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #e0e7ff;
        }

        table.data th {
            background: #4f46e5;
            color: #ffffff;
            font-size: 8px;
            font-weight: bold;
            padding: 9px 10px;
            text-align: left;
            border: none;
            border-right: 1px solid rgba(255, 255, 255, 0.18);
        }

        table.data th:last-child { border-right: none; }

        table.data td {
            border: none;
            border-bottom: 1px solid #eef2f7;
            padding: 9px 10px;
            font-size: 8.5px;
            color: #1e293b;
            background: #ffffff;
        }

        table.data tr:nth-child(even) td { background: #f8fafc; }
        table.data tr:last-child td { border-bottom: none; }
        table.data .empty { text-align: center; color: #94a3b8; padding: 14px; }

        .footer {
            margin-top: 12px;
            padding: 9px 12px;
            background: #ffffff;
            border: 1px solid #c7d2fe;
            border-radius: 10px;
            color: #64748b;
            font-size: 7.5px;
            text-align: center;
        }
    </style>
</head>
<body>
    @php
        $summary = $summary ?? [];
        $tables = $tables ?? [];
        $charts = $charts ?? [];
        $sections = $sections ?? [];
        $subtitle = $subtitle ?? 'Events, ticket sales, and revenue snapshot';
        $kpis = $kpis ?? [];
        $filters = $filters ?? [];
        $authUser = auth()->user();
        $preparedFor = $authUser?->email ?? ($authUser?->full_name ?? 'Organizer');

        $kpiStyles = ['kpi-mint', 'kpi-blue', 'kpi-violet', 'kpi-amber'];

        // Keep executive summary compact: prefer explicit KPIs, else first summary rows.
        $heroKpis = ! empty($kpis) ? $kpis : array_slice($summary, 0, 8);

        $sectionHints = [
            'performance' => 'Headline KPIs, charts, and operational tables for the selected filters.',
            'overview' => 'Headline KPIs, revenue and ticket trends, and a snapshot of the selected period.',
            'revenue' => 'Income trends, refunds, and contribution by event.',
            'tickets' => 'Sales volume, conversion, and category performance.',
            'events' => 'Fill rate, rankings, and revenue contribution.',
            'attendance' => 'Check-in mix, timing, and attendance by event.',
            'audience' => 'Attendee mix, demographics, and top customers.',
            'engagement' => 'Likes, saves, comments, ratings, and momentum.',
            'activity' => 'Latest confirmed and refund-related transactions.',
            'today' => 'Queue, handoffs, refunds, and events happening today.',
            'attendance' => 'Check-in mix, timing, and assigned-event attendance.',
            'support' => 'Volume trends, resolution mix, and feedback themes.',
            'inquiry' => 'Inquiry status, response speed, and volume by event.',
            'complaints' => 'Categories, submission volume, and handling progress.',
        ];

        $titleToKey = [
            'performance' => 'performance',
            'revenue' => 'revenue',
            'tickets' => 'tickets',
            'events' => 'events',
            'attendance' => 'attendance',
            'audience' => 'audience',
            'engagement' => 'engagement',
            'activity' => 'activity',
        ];

        $chartsBySection = [];
        foreach ($charts as $chart) {
            $chartTitle = (string) ($chart['title'] ?? 'Chart');
            $sectionKey = strtolower(trim((string) ($chart['section'] ?? '')));

            if ($sectionKey === '') {
                $parts = explode(' — ', $chartTitle, 2);
                $prefix = strtolower(trim($parts[0] ?? ''));
                $sectionKey = $titleToKey[$prefix] ?? 'other';
                $chartTitle = count($parts) === 2 ? $parts[1] : $chartTitle;
            }

            $chartsBySection[$sectionKey][] = [
                'title' => $chartTitle,
                'image' => $chart['image'] ?? '',
            ];
        }

        $knownKeys = collect($sections)->pluck('key')->filter()->all();
        $leftoverCharts = collect($chartsBySection)
            ->reject(fn ($groupCharts, $key) => in_array($key, $knownKeys, true))
            ->flatten(1)
            ->values()
            ->all();
    @endphp

    <div class="cover">
        <p class="brand">EventHub</p>
        <table class="header">
            <tr>
                <td width="68%">
                    <h1>{{ $title }}</h1>
                    <p class="meta-line">{{ $subtitle }}</p>
                    <p class="meta-line">Prepared for: {{ $preparedFor }}</p>
                    @foreach ($filters as $item)
                        <p class="meta-line">{{ $item['label'] ?? 'Filter' }}: {{ $item['value'] ?? '—' }}</p>
                    @endforeach
                </td>
                <td width="32%">
                    <p class="gen-label">Generated</p>
                    <p class="gen-value">{{ $generatedAt }}</p>
                </td>
            </tr>
        </table>

        <div class="hero">
            <p class="eyebrow">Dashboard Export</p>
            <p class="title">Performance snapshot</p>
            <p class="desc">Each dashboard tab is exported with its charts directly above the matching tables.</p>
        </div>

        @if (! empty($heroKpis))
            @php $kpiIndex = 0; @endphp
            @foreach (array_chunk($heroKpis, 4) as $chunk)
                <table class="grid">
                    <tr>
                        @foreach ($chunk as $item)
                            <td width="{{ (int) floor(100 / max(1, count($chunk))) }}%">
                                <div class="kpi {{ $kpiStyles[$kpiIndex % count($kpiStyles)] }}">
                                    <p class="label">{{ $item['label'] ?? 'Metric' }}</p>
                                    <p class="value">{{ $item['value'] ?? '—' }}</p>
                                    @if (! empty($item['sub']))
                                        <p class="sub">{{ $item['sub'] }}</p>
                                    @endif
                                </div>
                            </td>
                            @php $kpiIndex++; @endphp
                        @endforeach
                        @for ($i = count($chunk); $i < 4; $i++)
                            <td width="25%"></td>
                        @endfor
                    </tr>
                </table>
            @endforeach
        @endif
    </div>

    @foreach ($sections as $index => $section)
        @php
            $sectionKey = (string) ($section['key'] ?? '');
            $sectionTitle = $section['title'] ?? 'Section';
            $sectionCharts = $chartsBySection[$sectionKey] ?? [];
            $sectionTables = $section['tables'] ?? [];
            $sectionKpis = $section['summary'] ?? [];
        @endphp

        @if (! empty($sectionCharts) || ! empty($sectionTables) || ! empty($sectionKpis))
        <div class="section-break">
            <div class="section-head">
                <p class="section-kicker">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }} · Dashboard tab</p>
                <p class="section-title">{{ $sectionTitle }}</p>
                <p class="section-sub">{{ $sectionHints[$sectionKey] ?? 'Charts and supporting tables for this topic.' }}</p>
            </div>

            @if (! empty($sectionKpis))
                @php $sectionKpiIndex = 0; @endphp
                @foreach (array_chunk($sectionKpis, 4) as $chunk)
                    <table class="grid">
                        <tr>
                            @foreach ($chunk as $item)
                                <td width="{{ (int) floor(100 / max(1, count($chunk))) }}%">
                                    <div class="kpi {{ $kpiStyles[$sectionKpiIndex % count($kpiStyles)] }}">
                                        <p class="label">{{ $item['label'] ?? 'Metric' }}</p>
                                        <p class="value">{{ $item['value'] ?? '—' }}</p>
                                        @if (! empty($item['sub']))
                                            <p class="sub">{{ $item['sub'] }}</p>
                                        @endif
                                    </div>
                                </td>
                                @php $sectionKpiIndex++; @endphp
                            @endforeach
                            @for ($i = count($chunk); $i < 4; $i++)
                                <td width="25%"></td>
                            @endfor
                        </tr>
                    </table>
                @endforeach
            @endif

            @foreach ($sectionCharts as $chart)
                <div class="panel">
                    <div class="panel-accent"></div>
                    <p class="panel-title">{{ $chart['title'] ?? 'Chart' }}</p>
                    <div class="chart-frame">
                        <img src="{{ $chart['image'] }}" alt="{{ $chart['title'] ?? 'Chart' }}">
                    </div>
                </div>
            @endforeach

            @foreach ($sectionTables as $table)
                <div class="panel">
                    <div class="panel-accent"></div>
                    <p class="panel-title">{{ $table['heading'] ?? 'Data' }}</p>
                    <table class="data">
                        <thead>
                            <tr>
                                @foreach ($table['headers'] as $header)
                                    <th>{{ $header }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($table['rows'] as $row)
                                <tr>
                                    @foreach ($row as $cell)
                                        <td>{{ $cell }}</td>
                                    @endforeach
                                </tr>
                            @empty
                                <tr>
                                    <td class="empty" colspan="{{ max(1, count($table['headers'] ?? [])) }}">
                                        No data available.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endforeach
        </div>
        @endif
    @endforeach

    @if (! empty($leftoverCharts))
        <div class="section-break">
            <div class="section-head">
                <p class="section-kicker">Visuals</p>
                <p class="section-title">Additional Charts</p>
                <p class="section-sub">Supporting visuals captured from the dashboard.</p>
            </div>

            @foreach ($leftoverCharts as $chart)
                <div class="panel">
                    <div class="panel-accent"></div>
                    <p class="panel-title">{{ $chart['title'] ?? 'Chart' }}</p>
                    <div class="chart-frame">
                        <img src="{{ $chart['image'] }}" alt="{{ $chart['title'] ?? 'Chart' }}">
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    @if (empty($sections))
        @if (! empty($charts))
            <div class="section-head">
                <p class="section-kicker">Visuals</p>
                <p class="section-title">Dashboard Charts</p>
                <p class="section-sub">Captured from the live dashboard with a white chart background.</p>
            </div>

            @foreach ($charts as $chart)
                <div class="panel">
                    <div class="panel-accent"></div>
                    <p class="panel-title">{{ $chart['title'] ?? 'Chart' }}</p>
                    <div class="chart-frame">
                        <img src="{{ $chart['image'] }}" alt="{{ $chart['title'] ?? 'Chart' }}">
                    </div>
                </div>
            @endforeach
        @endif

        @if (! empty($tables))
            <div class="section-break">
                <div class="section-head">
                    <p class="section-kicker">Data</p>
                    <p class="section-title">Dashboard Tables</p>
                    <p class="section-sub">Supporting operational details for this export.</p>
                </div>

                @foreach ($tables as $table)
                    <div class="panel">
                        <div class="panel-accent"></div>
                        <p class="panel-title">{{ $table['heading'] ?? 'Data' }}</p>
                        <table class="data">
                            <thead>
                                <tr>
                                    @foreach ($table['headers'] as $header)
                                        <th>{{ $header }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($table['rows'] as $row)
                                    <tr>
                                        @foreach ($row as $cell)
                                            <td>{{ $cell }}</td>
                                        @endforeach
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="empty" colspan="{{ max(1, count($table['headers'] ?? [])) }}">
                                            No data available.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @endforeach
            </div>
        @endif
    @endif

    <div class="footer">
        EventHub · {{ $title }} export · Generated {{ $generatedAt }}
    </div>
</body>
</html>
