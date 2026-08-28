<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 25px 30px; }
        * { font-family: DejaVu Sans, sans-serif; box-sizing: border-box; }
        body { margin: 0; padding: 0; color: #1a1a1a; font-size: 11px; }
        .page { width: 100%; margin: 0 auto; }

        /* Header */
        .header { display: table; width: 100%; border-bottom: 3px solid #026e00; padding-bottom: 14px; margin-bottom: 18px; }
        .header-left { display: table-cell; vertical-align: middle; width: 65%; }
        .header-right { display: table-cell; vertical-align: middle; text-align: right; width: 35%; }
        .brand { display: table; }
        .brand-logo-cell { display: table-cell; vertical-align: middle; width: 70px; }
        .brand-logo { height: 55px; width: auto; }
        .brand-text-cell { display: table-cell; vertical-align: middle; padding-left: 12px; }
        .brand-text h1 { color: #026e00; font-size: 22px; margin: 0; font-weight: 900; letter-spacing: -0.5px; }
        .brand-text p { color: #666; font-size: 10px; margin: 3px 0 0; font-style: italic; }
        .doc-meta .label { font-size: 9px; color: #999; text-transform: uppercase; letter-spacing: 1px; }
        .doc-meta .date { font-size: 11px; color: #555; margin-top: 4px; }

        /* Title */
        .title-section { text-align: center; margin-bottom: 18px; }
        .title-section h2 { font-size: 18px; color: #026e00; margin: 0; text-transform: uppercase; letter-spacing: 2px; font-weight: 900; }
        .title-section .subtitle { font-size: 10px; color: #999; margin-top: 5px; }

        /* Filters */
        .filters-box { background: #f0f7f0; border-left: 4px solid #026e00; border-radius: 6px; padding: 12px 16px; margin-bottom: 18px; }
        .filters-box h3 { font-size: 11px; color: #026e00; margin: 0 0 6px; text-transform: uppercase; letter-spacing: 1px; }
        .filters-box p { font-size: 10px; color: #444; margin: 2px 0; }
        .filters-box .filter-row { display: inline; margin-right: 16px; }

        /* Summary */
        .summary { display: table; width: 100%; margin-bottom: 18px; }
        .summary-card { display: table-cell; width: 33%; background: #f0f7f0; border-left: 4px solid #026e00; border-radius: 6px; padding: 12px 14px; vertical-align: top; }
        .summary-card .label { font-size: 9px; color: #026e00; text-transform: uppercase; letter-spacing: 1px; font-weight: 700; margin-bottom: 4px; }
        .summary-card .value { font-size: 18px; color: #1a1a1a; font-weight: 700; }
        .summary-spacer { display: table-cell; width: 8px; }

        /* Table */
        .data-table { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
        .data-table th { background: #026e00; color: white; padding: 7px 8px; text-align: left; font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px; }
        .data-table td { padding: 6px 8px; border-bottom: 1px solid #e5e5e5; font-size: 9px; vertical-align: top; }
        .data-table tr:nth-child(even) td { background: #f6faf6; }
        .data-table .action-cell { font-weight: 700; color: #026e00; }
        .data-table .category-badge { display: inline-block; padding: 2px 6px; border-radius: 3px; font-size: 8px; font-weight: 700; text-transform: uppercase; }
        .cat-auth { background: #dbeafe; color: #1e40af; }
        .cat-store { background: #d1fae5; color: #065f46; }
        .cat-loan { background: #fef3c7; color: #92400e; }
        .cat-system { background: #e5e7eb; color: #374151; }
        .cat-wms { background: #ede9fe; color: #5b21b6; }
        .cat-farmer { background: #fce7f3; color: #9d174d; }
        .cat-other { background: #f3f4f6; color: #4b5563; }
        .metadata-cell { font-size: 8px; color: #888; max-width: 200px; word-break: break-all; }

        /* Footer */
        .footer { border-top: 2px solid #026e00; padding-top: 10px; margin-top: 18px; display: table; width: 100%; }
        .footer-left { display: table-cell; font-size: 9px; color: #999; }
        .footer-right { display: table-cell; text-align: right; font-size: 10px; font-weight: 700; color: #026e00; }

        .watermark { position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%) rotate(-45deg); font-size: 80px; color: rgba(2, 110, 0, 0.04); font-weight: 900; z-index: -1; pointer-events: none; }
    </style>
</head>
<body>
    @php
        $logoPath = public_path('images/agriAid-logo.png');
        if (!file_exists($logoPath)) {
            $logoPath = public_path('agriAid-logo.png');
        }
        $logoBase64 = '';
        if (file_exists($logoPath)) {
            $logoData = file_get_contents($logoPath);
            $logoBase64 = 'data:image/png;base64,' . base64_encode($logoData);
        }
        $categoryClass = function($cat) {
            return match($cat) {
                'auth' => 'cat-auth',
                'store' => 'cat-store',
                'loan' => 'cat-loan',
                'system' => 'cat-system',
                'wms' => 'cat-wms',
                'farmer' => 'cat-farmer',
                default => 'cat-other',
            };
        };
        $categoryCounts = [];
        foreach ($logs as $log) {
            $cat = $log->category ?? 'system';
            $categoryCounts[$cat] = ($categoryCounts[$cat] ?? 0) + 1;
        }
        arsort($categoryCounts);
        $topCategory = !empty($categoryCounts) ? array_key_first($categoryCounts) : '—';
        $topCategoryCount = !empty($categoryCounts) ? reset($categoryCounts) : 0;
    @endphp

    <div class="watermark">agriAid</div>
    <div class="page">
        <div class="header">
            <div class="header-left">
                <div class="brand">
                    <div class="brand-logo-cell">
                        @if($logoBase64)
                            <img src="{{ $logoBase64 }}" class="brand-logo" />
                        @endif
                    </div>
                    <div class="brand-text-cell">
                        <div class="brand-text">
                            <h1>agriAid Platform</h1>
                            <p>Empowering Cameroon's Agricultural Future</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="header-right">
                <div class="doc-meta">
                    <div class="label">Generated</div>
                    <div class="date">{{ $generatedAt }}</div>
                </div>
            </div>
        </div>

        <div class="title-section">
            <h2>Audit Log Report</h2>
            <div class="subtitle">System Activity &amp; Security Audit Trail</div>
        </div>

        <div class="summary">
            <div class="summary-card">
                <div class="label">Total Records</div>
                <div class="value">{{ $logs->count() }}</div>
            </div>
            <div class="summary-spacer"></div>
            <div class="summary-card">
                <div class="label">Top Category</div>
                <div class="value" style="font-size: 14px;">{{ ucfirst($topCategory) }} ({{ $topCategoryCount }})</div>
            </div>
            <div class="summary-spacer"></div>
            <div class="summary-card">
                <div class="label">Categories</div>
                <div class="value">{{ count($categoryCounts) }}</div>
            </div>
        </div>

        @if(!empty(array_filter($filters)))
        <div class="filters-box">
            <h3>Applied Filters</h3>
            @if($filters['category'])<span class="filter-row"><strong>Category:</strong> {{ $filters['category'] }}</span>@endif
            @if($filters['action'])<span class="filter-row"><strong>Action:</strong> {{ $filters['action'] }}</span>@endif
            @if($filters['from_date'])<span class="filter-row"><strong>From:</strong> {{ $filters['from_date'] }}</span>@endif
            @if($filters['to_date'])<span class="filter-row"><strong>To:</strong> {{ $filters['to_date'] }}</span>@endif
        </div>
        @endif

        @if($logs->count() > 0)
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 110px;">Date / Time</th>
                    <th style="width: 100px;">Actor</th>
                    <th style="width: 120px;">Action</th>
                    <th style="width: 70px;">Category</th>
                    <th style="width: 80px;">IP Address</th>
                    <th>Metadata</th>
                </tr>
            </thead>
            <tbody>
                @foreach($logs as $log)
                    <tr>
                        <td>{{ $log->created_at?->format('Y-m-d H:i:s') ?? '—' }}</td>
                        <td>{{ $log->actor_name ?? 'System' }}</td>
                        <td class="action-cell">{{ $log->action }}</td>
                        <td>
                            @if($log->category)
                                <span class="category-badge {{ $categoryClass($log->category) }}">{{ $log->category }}</span>
                            @else
                                —
                            @endif
                        </td>
                        <td>{{ $log->ip_address ?? '—' }}</td>
                        <td class="metadata-cell">
                            @if($log->metadata)
                                {{ json_encode($log->metadata) }}
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <div style="text-align: center; padding: 40px; color: #999;">
            <p style="font-size: 14px; font-weight: 700;">No audit log records found.</p>
        </div>
        @endif

        <div class="footer">
            <div class="footer-left">
                Generated by agriAid Platform on {{ $generatedAt }}
            </div>
            <div class="footer-right">
                agriAid Platform &middot; Empowering Cameroon's Agricultural Future
            </div>
        </div>
    </div>
</body>
</html>
