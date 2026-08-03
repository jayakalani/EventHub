<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        @page { margin: 18px 20px 24px; }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9px;
            color: #0f172a;
            background: #eef2ff;
            margin: 0;
            padding: 0;
        }

        p { margin: 0; }

        .shell {
            background: #eef2ff;
            padding: 2px;
        }

        .glass {
            background: #ffffff;
            border: 1px solid #c7d2fe;
            border-radius: 16px;
        }

        .glass-soft {
            background: #f8faff;
            border: 1px solid #e0e7ff;
            border-radius: 14px;
        }

        .doc-meta {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .doc-meta td {
            color: #64748b;
            font-size: 7.5px;
            padding: 0;
        }

        .doc-meta .center { text-align: center; }
        .doc-meta .right { text-align: right; }

        .cover {
            background: #ffffff;
            border: 1px solid #c7d2fe;
            border-radius: 18px;
            padding: 16px 16px 14px;
            margin-bottom: 12px;
        }

        .brand {
            color: #4f46e5;
            font-size: 8.5px;
            font-weight: bold;
            letter-spacing: 1.8px;
            text-transform: uppercase;
            margin: 0 0 8px;
        }

        .header {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }

        .header td { vertical-align: top; padding: 0; }

        h1 {
            font-size: 22px;
            color: #0f172a;
            margin: 0 0 6px;
            line-height: 1.15;
            font-weight: bold;
        }

        .meta-line {
            color: #64748b;
            font-size: 8px;
            line-height: 1.45;
        }

        .gen-label {
            color: #94a3b8;
            font-size: 7px;
            text-align: right;
            margin-bottom: 2px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .gen-value {
            color: #312e81;
            font-size: 9px;
            font-weight: bold;
            text-align: right;
        }

        .hero {
            background: #4f46e5;
            border-radius: 14px;
            padding: 14px 16px;
            margin-bottom: 12px;
            color: #ffffff;
        }

        .hero-inner {
            width: 100%;
            border-collapse: collapse;
        }

        .hero-inner td { vertical-align: middle; padding: 0; }

        .hero .eyebrow {
            font-size: 7px;
            letter-spacing: 1.3px;
            text-transform: uppercase;
            opacity: 0.85;
            margin-bottom: 4px;
        }

        .hero .title {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 3px;
        }

        .hero .desc {
            font-size: 8px;
            opacity: 0.92;
            line-height: 1.4;
        }

        .hero-badge {
            background: #6366f1;
            border: 1px solid #a5b4fc;
            border-radius: 999px;
            color: #eef2ff;
            font-size: 7.5px;
            font-weight: bold;
            letter-spacing: 0.4px;
            padding: 6px 10px;
            text-align: center;
        }

        .grid {
            width: 100%;
            border-collapse: separate;
            border-spacing: 8px;
            margin: 0 0 4px -8px;
        }

        .chip {
            background: #f8faff;
            border: 1px solid #c7d2fe;
            border-radius: 12px;
            padding: 10px 11px;
        }

        .chip .label {
            color: #6366f1;
            font-size: 6.5px;
            font-weight: bold;
            letter-spacing: 0.55px;
            text-transform: uppercase;
            margin-bottom: 4px;
        }

        .chip .value {
            color: #0f172a;
            font-size: 10px;
            font-weight: bold;
            line-height: 1.25;
        }

        .kpi {
            text-align: center;
            border-radius: 14px;
            padding: 13px 8px;
            border: 1px solid #c7d2fe;
            background: #ffffff;
        }

        .kpi .label {
            color: #64748b;
            font-size: 6.5px;
            font-weight: bold;
            letter-spacing: 0.55px;
            text-transform: uppercase;
            margin-bottom: 6px;
        }

        .kpi .value {
            color: #0f172a;
            font-size: 14px;
            font-weight: bold;
            line-height: 1.15;
        }

        .kpi-mint { background: #ecfdf5; border-color: #a7f3d0; }
        .kpi-blue { background: #eff6ff; border-color: #bfdbfe; }
        .kpi-violet { background: #f5f3ff; border-color: #ddd6fe; }
        .kpi-amber { background: #fffbeb; border-color: #fde68a; }
        .kpi-sky { background: #f0f9ff; border-color: #bae6fd; }
        .kpi-rose { background: #fff1f2; border-color: #fecdd3; }

        .section {
            margin-top: 6px;
        }

        .section-break {
            page-break-before: always;
        }

        .section-head {
            background: #ffffff;
            border: 1px solid #c7d2fe;
            border-radius: 14px;
            padding: 12px 14px;
            margin-bottom: 10px;
        }

        .section-kicker {
            color: #4f46e5;
            font-size: 7px;
            font-weight: bold;
            letter-spacing: 1.1px;
            text-transform: uppercase;
            margin-bottom: 3px;
        }

        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #0f172a;
            margin: 0 0 3px;
        }

        .section-sub {
            color: #64748b;
            font-size: 8px;
            line-height: 1.4;
        }

        .panel {
            background: #ffffff;
            border: 1px solid #c7d2fe;
            border-radius: 14px;
            padding: 12px 12px 10px;
            margin-bottom: 10px;
            page-break-inside: avoid;
        }

        .panel-title {
            font-size: 10.5px;
            font-weight: bold;
            color: #0f172a;
            margin: 0 0 8px;
        }

        .panel-accent {
            width: 28px;
            height: 3px;
            background: #4f46e5;
            border-radius: 99px;
            margin-bottom: 8px;
        }

        .chart-frame {
            background: #ffffff;
            border: 1px solid #e0e7ff;
            border-radius: 10px;
            padding: 8px;
        }

        .chart-frame img {
            width: 100%;
            height: auto;
            max-height: 250px;
            background: #ffffff;
            display: block;
        }

        .mini-grid {
            width: 100%;
            border-collapse: separate;
            border-spacing: 6px;
            margin: 0 0 8px -6px;
        }

        .mini {
            background: #f8faff;
            border: 1px solid #e0e7ff;
            border-radius: 10px;
            padding: 8px;
            text-align: center;
        }

        .mini .label {
            color: #6366f1;
            font-size: 6.5px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            margin-bottom: 3px;
            font-weight: bold;
        }

        .mini .value {
            color: #0f172a;
            font-size: 10.5px;
            font-weight: bold;
        }

        table.data {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #e0e7ff;
            border-radius: 8px;
            overflow: hidden;
        }

        table.data th {
            background: #4f46e5;
            color: #ffffff;
            font-size: 8px;
            font-weight: bold;
            letter-spacing: 0.15px;
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
            vertical-align: middle;
            font-size: 8.5px;
            color: #1e293b;
            background: #ffffff;
        }

        table.data tr:nth-child(even) td { background: #f8fafc; }
        table.data tr:last-child td { border-bottom: none; }

        table.data .empty {
            text-align: center;
            color: #94a3b8;
            padding: 16px;
            background: #ffffff;
        }

        .toc {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
        }

        .toc td {
            padding: 7px 0;
            border-bottom: 1px solid #e0e7ff;
            color: #334155;
            font-size: 8.5px;
        }

        .toc .num {
            width: 26px;
            color: #4f46e5;
            font-weight: bold;
        }

        .footer {
            margin-top: 14px;
            padding: 10px 12px;
            background: #ffffff;
            border: 1px solid #c7d2fe;
            border-radius: 12px;
            color: #64748b;
            font-size: 7.5px;
            text-align: center;
        }
    </style>
</head>
<body>
    @php
        $filters = $filters ?? [];
        $kpis = $kpis ?? [];
        $sections = $sections ?? [];
        $charts = $charts ?? [];
        $subtitle = $subtitle ?? 'Organizer performance analytics';
        $authUser = auth()->user();
        $preparedFor = $authUser?->email ?? ($authUser?->full_name ?? 'Organizer');

        $periodParts = [];
        foreach ($filters as $item) {
            $periodParts[] = ($item['label'] ?? 'Filter').': '.($item['value'] ?? '—');
        }
        $periodLine = count($periodParts)
            ? implode(' · ', $periodParts)
            : 'All events · All dates · All statuses';

        $kpiStyles = ['kpi-mint', 'kpi-blue', 'kpi-violet', 'kpi-amber', 'kpi-sky', 'kpi-rose'];

        $groupedCharts = [];
        foreach ($charts as $chart) {
            $chartTitle = (string) ($chart['title'] ?? 'Chart');
            $parts = explode(' — ', $chartTitle, 2);
            $group = count($parts) === 2 ? $parts[0] : 'Insights';
            $label = count($parts) === 2 ? $parts[1] : $chartTitle;
            $groupedCharts[$group][] = [
                'title' => $label,
                'image' => $chart['image'],
            ];
        }

        $sectionHints = [
            'Overview' => 'High-level snapshot of revenue, tickets, and engagement.',
            'Revenue' => 'Income trends, refunds, and contribution by event.',
            'Tickets' => 'Sales volume, conversion, and category performance.',
            'Events' => 'Fill rate, rankings, and revenue contribution.',
            'Audience' => 'Attendee mix, demographics, and top customers.',
            'Engagement' => 'Likes, saves, comments, ratings, and momentum.',
            'Activity' => 'Latest confirmed and refund-related transactions.',
        ];
    @endphp

    <div class="shell">
        {{-- PAGE 1: Executive cover --}}
        <table class="doc-meta">
            <tr>
                <td width="28%">{{ $generatedAt }}</td>
                <td width="44%" class="center">Organizer Reports — EventHub</td>
                <td width="28%" class="right">Premium Export</td>
            </tr>
        </table>

        <div class="cover">
            <p class="brand">EventHub</p>

            <table class="header">
                <tr>
                    <td width="68%">
                        <h1>Organizer Reports</h1>
                        <p class="meta-line">{{ $periodLine }}</p>
                        <p class="meta-line">Prepared for: {{ $preparedFor }}</p>
                    </td>
                    <td width="32%">
                        <p class="gen-label">Generated</p>
                        <p class="gen-value">{{ $generatedAt }}</p>
                    </td>
                </tr>
            </table>

            <div class="hero">
                <table class="hero-inner">
                    <tr>
                        <td width="78%">
                            <p class="eyebrow">Executive Overview</p>
                            <p class="title">{{ $title }}</p>
                            <p class="desc">{{ $subtitle }}</p>
                        </td>
                        <td width="22%" align="right">
                            <div class="hero-badge">FULL REPORT</div>
                        </td>
                    </tr>
                </table>
            </div>

            @if (! empty($filters))
                <table class="grid">
                    <tr>
                        @foreach ($filters as $item)
                            <td width="{{ (int) floor(100 / max(1, count($filters))) }}%">
                                <div class="chip">
                                    <p class="label">{{ $item['label'] }}</p>
                                    <p class="value">{{ $item['value'] }}</p>
                                </div>
                            </td>
                        @endforeach
                    </tr>
                </table>
            @endif

            @if (! empty($kpis))
                @php $kpiIndex = 0; @endphp
                @foreach (array_chunk($kpis, 3) as $chunk)
                    <table class="grid">
                        <tr>
                            @foreach ($chunk as $item)
                                <td width="{{ (int) floor(100 / max(1, count($chunk))) }}%">
                                    <div class="kpi {{ $kpiStyles[$kpiIndex % count($kpiStyles)] }}">
                                        <p class="label">{{ $item['label'] }}</p>
                                        <p class="value">{{ $item['value'] }}</p>
                                    </div>
                                </td>
                                @php $kpiIndex++; @endphp
                            @endforeach
                            @for ($i = count($chunk); $i < 3; $i++)
                                <td width="33%"></td>
                            @endfor
                        </tr>
                    </table>
                @endforeach
            @endif

            @if (count($sections) > 1)
                <div class="glass-soft" style="padding: 12px 14px; margin-top: 8px;">
                    <p class="section-kicker">Contents</p>
                    <p class="section-title" style="font-size: 12px;">Report structure</p>
                    <table class="toc">
                        @foreach ($sections as $index => $section)
                            <tr>
                                <td class="num">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</td>
                                <td>{{ $section['title'] ?? 'Section' }}</td>
                                <td style="text-align: right; color: #64748b;">{{ count($section['tables'] ?? []) }} tables</td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            @endif
        </div>

        {{-- Remaining pages: each section with matching charts + tables --}}
        @foreach ($sections as $index => $section)
            @php
                $sectionTitle = $section['title'] ?? 'Section';
                $sectionCharts = $groupedCharts[$sectionTitle] ?? [];
            @endphp

            <div class="section section-break">
                <div class="section-head">
                    <p class="section-kicker">Section {{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</p>
                    <p class="section-title">{{ $sectionTitle }}</p>
                    <p class="section-sub">{{ $sectionHints[$sectionTitle] ?? 'Detailed metrics and supporting tables.' }}</p>
                </div>

                @if (! empty($section['summary']))
                    <table class="mini-grid">
                        @foreach (array_chunk($section['summary'], 3) as $chunk)
                            <tr>
                                @foreach ($chunk as $item)
                                    <td width="{{ (int) floor(100 / max(1, count($chunk))) }}%">
                                        <div class="mini">
                                            <p class="label">{{ $item['label'] }}</p>
                                            <p class="value">{{ $item['value'] }}</p>
                                        </div>
                                    </td>
                                @endforeach
                                @for ($i = count($chunk); $i < 3; $i++)
                                    <td width="33%"></td>
                                @endfor
                            </tr>
                        @endforeach
                    </table>
                @endif

                @foreach ($sectionCharts as $chart)
                    <div class="panel">
                        <div class="panel-accent"></div>
                        <p class="panel-title">{{ $chart['title'] }}</p>
                        <div class="chart-frame">
                            <img src="{{ $chart['image'] }}" alt="{{ $chart['title'] }}">
                        </div>
                    </div>
                @endforeach

                @foreach ($section['tables'] ?? [] as $table)
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
                                            No data available for this table.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @endforeach
            </div>
        @endforeach

        {{-- Any leftover chart groups not mapped to a section title --}}
        @php
            $mapped = collect($sections)->pluck('title')->filter()->all();
            $leftoverCharts = collect($groupedCharts)
                ->reject(fn ($_, $group) => in_array($group, $mapped, true))
                ->all();
        @endphp

        @if (! empty($leftoverCharts))
            <div class="section section-break">
                <div class="section-head">
                    <p class="section-kicker">Visuals</p>
                    <p class="section-title">Additional Charts</p>
                    <p class="section-sub">Supporting visuals for the selected report scope.</p>
                </div>

                @foreach ($leftoverCharts as $group => $groupCharts)
                    @foreach ($groupCharts as $chart)
                        <div class="panel">
                            <div class="panel-accent"></div>
                            <p class="panel-title">{{ $group }} — {{ $chart['title'] }}</p>
                            <div class="chart-frame">
                                <img src="{{ $chart['image'] }}" alt="{{ $chart['title'] }}">
                            </div>
                        </div>
                    @endforeach
                @endforeach
            </div>
        @endif

        <div class="footer">
            EventHub · Confidential premium organizer export · Generated {{ $generatedAt }}
        </div>
    </div>
</body>
</html>
