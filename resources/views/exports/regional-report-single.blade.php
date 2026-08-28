<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 30px 40px; }
        * { font-family: DejaVu Sans, sans-serif; box-sizing: border-box; }
        body { margin: 0; padding: 0; color: #1a1a1a; font-size: 12px; }
        .page { width: 100%; margin: 0 auto; }

        /* Header */
        .header { display: table; width: 100%; border-bottom: 3px solid #026e00; padding-bottom: 18px; margin-bottom: 22px; }
        .header-left { display: table-cell; vertical-align: middle; width: 65%; }
        .header-right { display: table-cell; vertical-align: middle; text-align: right; width: 35%; }
        .brand { display: table; }
        .brand-logo-cell { display: table-cell; vertical-align: middle; width: 70px; }
        .brand-logo { height: 60px; width: auto; }
        .brand-text-cell { display: table-cell; vertical-align: middle; padding-left: 12px; }
        .brand-text h1 { color: #026e00; font-size: 24px; margin: 0; font-weight: 900; letter-spacing: -0.5px; }
        .brand-text p { color: #666; font-size: 10px; margin: 3px 0 0; font-style: italic; }
        .doc-meta .label { font-size: 9px; color: #999; text-transform: uppercase; letter-spacing: 1px; }
        .doc-meta .date { font-size: 11px; color: #555; margin-top: 4px; }

        /* Title */
        .title-section { text-align: center; margin-bottom: 22px; }
        .title-section h2 { font-size: 20px; color: #026e00; margin: 0; text-transform: uppercase; letter-spacing: 2px; font-weight: 900; }
        .title-section .subtitle { font-size: 10px; color: #999; margin-top: 5px; }

        /* Region banner */
        .region-banner { background: #026e00; border-radius: 8px; padding: 18px 22px; margin-bottom: 22px; color: white; text-align: center; }
        .region-banner .region-name { font-size: 22px; font-weight: 900; }
        .region-banner .region-sub { font-size: 11px; opacity: 0.85; margin-top: 5px; }

        /* Section title */
        .section-title { font-size: 12px; color: #026e00; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin: 0 0 10px; border-left: 4px solid #026e00; padding-left: 8px; }

        /* Detail grid - table-based for DOMPDF compatibility */
        .detail-grid { display: table; width: 100%; border-collapse: separate; border-spacing: 8px; margin-bottom: 22px; }
        .detail-row { display: table-row; }
        .detail-card { display: table-cell; width: 50%; background: #f0f7f0; border-left: 4px solid #026e00; border-radius: 6px; padding: 14px 16px; vertical-align: top; }
        .detail-card .label { font-size: 9px; color: #026e00; text-transform: uppercase; letter-spacing: 1px; font-weight: 700; margin-bottom: 6px; }
        .detail-card .value { font-size: 16px; color: #1a1a1a; font-weight: 700; }
        .detail-card .unit { font-size: 10px; color: #888; margin-left: 4px; font-weight: 400; }

        /* Metrics */
        .metrics-section { margin-bottom: 22px; }
        .metrics-list { list-style: none; padding: 0; margin: 0; }
        .metrics-list li { font-size: 11px; color: #444; padding: 7px 0; border-bottom: 1px solid #eee; }
        .metrics-list li strong { color: #026e00; }

        /* Footer */
        .footer { border-top: 2px solid #026e00; padding-top: 12px; margin-top: 20px; display: table; width: 100%; }
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
            <h2>Regional Report</h2>
            <div class="subtitle">Location-Based Agricultural Insights &amp; Production Overview</div>
        </div>

        <div class="region-banner">
            <div class="region-name">{{ $report->region }}</div>
            <div class="region-sub">
                @if($report->city){{ $report->city }} &middot; @endif
                @if($report->report_type){{ $report->report_type }}@endif
            </div>
        </div>

        <h3 class="section-title">Report Details</h3>
        <div class="detail-grid">
            <div class="detail-row">
                <div class="detail-card">
                    <div class="label">Region</div>
                    <div class="value">{{ $report->region }}</div>
                </div>
                <div class="detail-card">
                    <div class="label">City</div>
                    <div class="value">{{ $report->city ?? '-' }}</div>
                </div>
            </div>
            <div class="detail-row">
                <div class="detail-card">
                    <div class="label">Report Type</div>
                    <div class="value">{{ $report->report_type ?? '-' }}</div>
                </div>
                <div class="detail-card">
                    <div class="label">Period</div>
                    <div class="value" style="font-size: 13px;">
                        @if($report->period_start || $report->period_end)
                            {{ $report->period_start?->format('Y-m-d') ?? '?' }} &mdash; {{ $report->period_end?->format('Y-m-d') ?? '?' }}
                        @else
                            -
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <h3 class="section-title">Production &amp; Stock</h3>
        <div class="detail-grid">
            <div class="detail-row">
                <div class="detail-card">
                    <div class="label">Total Production</div>
                    <div class="value">
                        {{ $report->total_production_mt ? number_format((float) $report->total_production_mt, 2) : '-' }}
                        <span class="unit">MT</span>
                    </div>
                </div>
                <div class="detail-card">
                    <div class="label">Warehouse Stock</div>
                    <div class="value">
                        {{ $report->warehouse_stock_mt ? number_format((float) $report->warehouse_stock_mt, 2) : '-' }}
                        <span class="unit">MT</span>
                    </div>
                </div>
            </div>
        </div>

        <h3 class="section-title">Financing &amp; Farmers</h3>
        <div class="detail-grid">
            <div class="detail-row">
                <div class="detail-card">
                    <div class="label">Financing Volume</div>
                    <div class="value">
                        {{ $report->financing_volume_fcfa ? number_format((float) $report->financing_volume_fcfa, 0) : '-' }}
                        <span class="unit">FCFA</span>
                    </div>
                </div>
                <div class="detail-card">
                    <div class="label">Active Farmers</div>
                    <div class="value">{{ $report->active_farmers ?? '-' }}</div>
                </div>
            </div>
        </div>

        @if(!empty($report->metrics))
        <h3 class="section-title">Additional Metrics</h3>
        <div class="metrics-section">
            <ul class="metrics-list">
                @foreach($report->metrics as $key => $value)
                    <li><strong>{{ ucfirst(str_replace('_', ' ', $key)) }}:</strong> {{ is_array($value) ? json_encode($value) : $value }}</li>
                @endforeach
            </ul>
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
