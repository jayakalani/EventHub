<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1e293b; }
        h1 { font-size: 18px; margin-bottom: 4px; color: #4f46e5; }
        .meta { color: #64748b; margin-bottom: 20px; font-size: 9px; }
        .summary { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .summary td { padding: 6px 10px; border: 1px solid #e2e8f0; }
        .summary .label { background: #f8fafc; font-weight: bold; width: 30%; }
        h2 { font-size: 13px; margin: 16px 0 8px; color: #334155; }
        table.data { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table.data th, table.data td { border: 1px solid #e2e8f0; padding: 5px 7px; text-align: left; }
        table.data th { background: #4f46e5; color: #fff; font-weight: bold; }
        table.data tr:nth-child(even) { background: #f8fafc; }
        table.charts-row { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        table.charts-row td { width: 50%; vertical-align: top; padding: 6px 8px; }
        .chart-title { font-size: 11px; font-weight: bold; color: #334155; margin-bottom: 6px; }
        table.charts-row img { width: 100%; max-height: 230px; height: auto; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <p class="meta">EventHub Report · Generated {{ $generatedAt }}</p>

    @if(!empty($summary))
        <table class="summary">
            @foreach(array_chunk($summary, 2) as $pair)
                <tr>
                    @foreach($pair as $item)
                        <td class="label">{{ $item['label'] }}</td>
                        <td>{{ $item['value'] }}</td>
                    @endforeach
                    @if(count($pair) === 1)
                        <td class="label"></td><td></td>
                    @endif
                </tr>
            @endforeach
        </table>
    @endif

    @if(!empty($charts))
        <h2>Charts &amp; graphs</h2>
        @php
            $groupedCharts = [];
            foreach ($charts as $chart) {
                $title = (string) ($chart['title'] ?? 'Chart');
                $parts = explode(' — ', $title, 2);
                $group = count($parts) === 2 ? $parts[0] : 'Charts';
                $label = count($parts) === 2 ? $parts[1] : $title;
                $groupedCharts[$group][] = [
                    'title' => $label,
                    'image' => $chart['image'],
                ];
            }
        @endphp
        @foreach ($groupedCharts as $group => $groupCharts)
            <h2>{{ $group }}</h2>
            @foreach(array_chunk($groupCharts, 2) as $pair)
                <table class="charts-row">
                    <tr>
                        @foreach($pair as $chart)
                            <td width="50%">
                                <div class="chart-title">{{ $chart['title'] }}</div>
                                <img src="{{ $chart['image'] }}" alt="{{ $chart['title'] }}">
                            </td>
                        @endforeach
                        @if(count($pair) === 1)
                            <td width="50%"></td>
                        @endif
                    </tr>
                </table>
            @endforeach
        @endforeach
    @endif

    @foreach($tables as $table)
        <h2>{{ $table['heading'] }}</h2>
        <table class="data">
            <thead>
                <tr>
                    @foreach($table['headers'] as $header)
                        <th>{{ $header }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($table['rows'] as $row)
                    <tr>
                        @foreach($row as $cell)
                            <td>{{ $cell }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($table['headers']) }}">No data available.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    @endforeach
</body>
</html>
