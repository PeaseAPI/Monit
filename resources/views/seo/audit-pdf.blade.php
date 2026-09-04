<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $audit->url }} - {{ __('seo.audit_report') }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: #18181b; font-size: 13px; line-height: 1.5; padding: 40px; }
        h1 { font-size: 20px; margin-bottom: 4px; word-break: break-all; }
        .meta { color: #71717a; font-size: 12px; margin-bottom: 20px; }
        .score-badge { display: inline-block; padding: 2px 10px; border-radius: 999px; font-size: 13px; font-weight: 600; }
        .score-poor { background: #fef2f2; color: #b91c1c; }
        .score-decent { background: #fefce8; color: #a16207; }
        .score-good { background: #ecfdf5; color: #047857; }
        .overview { margin-bottom: 24px; }
        .overview-bar { height: 10px; border-radius: 5px; overflow: hidden; background: #f4f4f5; display: flex; margin: 8px 0; }
        .overview-bar > div { height: 100%; }
        .overview-legend { display: flex; gap: 16px; font-size: 11px; color: #71717a; }
        .overview-legend span::before { content: ''; display: inline-block; width: 8px; height: 8px; border-radius: 50%; margin-right: 4px; vertical-align: middle; }
        .legend-major::before { background: #ef4444; }
        .legend-moderate::before { background: #eab308; }
        .legend-minor::before { background: #a1a1aa; }
        .legend-passed::before { background: #10b981; }
        .cat-scores { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 24px; }
        .cat-card { border: 1px solid #e4e4e7; border-radius: 12px; padding: 12px; }
        .cat-card h3 { font-size: 12px; color: #52525b; font-weight: 500; }
        .cat-card .score { font-size: 24px; font-weight: 700; margin-top: 4px; }
        .section { margin-bottom: 20px; page-break-inside: avoid; }
        .section-header { background: #fafafa; padding: 8px 16px; border: 1px solid #e4e4e7; border-bottom: none; border-radius: 12px 12px 0 0; display: flex; justify-content: space-between; align-items: center; }
        .section-header h2 { font-size: 14px; }
        .section-header .cat-score { font-size: 12px; color: #71717a; }
        table { width: 100%; border-collapse: collapse; border: 1px solid #e4e4e7; }
        table td { padding: 8px 16px; border-bottom: 1px solid #f4f4f5; vertical-align: top; }
        table tr:last-child td { border-bottom: none; }
        .badge { display: inline-block; padding: 1px 8px; border-radius: 999px; font-size: 11px; font-weight: 600; }
        .badge-pass { background: #ecfdf5; color: #047857; }
        .badge-fail-major { background: #fef2f2; color: #b91c1c; }
        .badge-fail-moderate { background: #fefce8; color: #a16207; }
        .print-btn { position: fixed; bottom: 24px; right: 24px; padding: 10px 24px; background: #18181b; color: #fff; border: none; border-radius: 8px; font-size: 14px; cursor: pointer; }
        @media print { .print-btn { display: none; } body { padding: 20px; } }
    </style>
</head>
<body>
    <h1>{{ $audit->url }}</h1>
    <p class="meta">
        <span class="score-badge score-{{ $audit->band }}">{{ $audit->score }}/100</span>
        &nbsp;·&nbsp; {{ $audit->passed_tests }}/{{ $audit->total_tests }} {{ __('seo.passed') }}
        &nbsp;·&nbsp; {{ __('seo.response_time') }}: {{ $audit->response_time_ms }} ms
        &nbsp;·&nbsp; {{ __('seo.page_size') }}: {{ number_format($audit->page_size_bytes / 1024, 1) }} KB
        &nbsp;·&nbsp; {{ $audit->created_at?->format('Y-m-d H:i') }}
    </p>

    @php $total = max(1, $audit->total_tests); @endphp
    <div class="overview">
        <div class="overview-bar">
            @if($audit->major_issues)<div style="width:{{ round($audit->major_issues/$total*100,1) }}%;background:#ef4444"></div>@endif
            @if($audit->moderate_issues)<div style="width:{{ round($audit->moderate_issues/$total*100,1) }}%;background:#eab308"></div>@endif
            @if($audit->minor_issues)<div style="width:{{ round($audit->minor_issues/$total*100,1) }}%;background:#a1a1aa"></div>@endif
            @if($audit->passed_tests)<div style="width:{{ round($audit->passed_tests/$total*100,1) }}%;background:#10b981"></div>@endif
        </div>
        <div class="overview-legend">
            <span class="legend-major">{{ __('seo.major_issue') }}: {{ $audit->major_issues }}</span>
            <span class="legend-moderate">{{ __('seo.moderate_issue') }}: {{ $audit->moderate_issues }}</span>
            <span class="legend-minor">{{ __('seo.minor_issue') }}: {{ $audit->minor_issues }}</span>
            <span class="legend-passed">{{ __('seo.passed') }}: {{ $audit->passed_tests }}</span>
        </div>
    </div>

    @if($audit->category_scores)
        <div class="cat-scores">
            @foreach($audit->category_scores as $category => $catScore)
                <div class="cat-card">
                    <h3>{{ __("seo.category_{$category}") }}</h3>
                    <div class="score" style="color:{{ $catScore > 79 ? '#047857' : ($catScore > 49 ? '#a16207' : '#b91c1c') }}">{{ $catScore }}</div>
                </div>
            @endforeach
        </div>
    @endif

    @foreach($grouped as $category => $rows)
        <div class="section">
            <div class="section-header">
                <h2>{{ __("seo.category_{$category}") }}</h2>
                <span class="cat-score">{{ $audit->category_scores[$category] ?? '-' }}/100</span>
            </div>
            <table>
                @foreach($rows as $key => $row)
                    <tr>
                        <td style="width:80px">
                            <span class="badge {{ ($row['passed'] ?? false) ? 'badge-pass' : ($row['importance'] === 'major' ? 'badge-fail-major' : 'badge-fail-moderate') }}">
                                {{ ($row['passed'] ?? false) ? __('seo.pass') : __('seo.fail') }}
                            </span>
                        </td>
                        <td>
                            <div>{{ \Illuminate\Support\Str::headline($key) }}</div>
                            @if(!empty($row['detail']))
                                <div style="font-size:11px;color:#71717a;margin-top:2px">{{ $row['detail'] }}</div>
                            @endif
                        </td>
                        <td style="width:120px;text-align:right;color:#71717a">{{ $row['value'] ?? '-' }}</td>
                    </tr>
                @endforeach
            </table>
        </div>
    @endforeach

    <button class="print-btn" onclick="window.print()">{{ __('seo.print_or_save_pdf') }}</button>
</body>
</html>