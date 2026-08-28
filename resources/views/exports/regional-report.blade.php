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

        /* Summary box */
        .summary-box { background: #f0f7f0; border-left: 4px solid #026e00; border-radius: 6px; padding: 16px 20px; margin-bottom: 22px; }
        .summary-box h3 { font-size: 12px; color: #026e00; margin: 0 0 6px; text-transform: uppercase; letter-spacing: 1px; }
        .summary-box p { font-size: 10px; color: #444; margin: 0; line-height: 1.6; }

        /* Table */
        .data-table { width: 100%; border-collapse: collapse; margin-bottom: 22px; }
        .data-table th { background: #026e00; color: white; padding: 9px 10px; text-align: left; font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px; }
        .data-table td { padding: 8px 10px; border-bottom: 1px solid #e5e5e5; font-size: 10px; }
        .data-table tr:nth-child(even) td { background: #f6faf6; }
        .data-table .region-cell { font-weight: 700; color: #026e00; }
        .data-table .num-cell { text-align: right; }

        /* Totals */
        .totals-row td { background: #e8f5e8 !important; font-weight: 700; color: #026e00; border-top: 2px solid #026e00; font-size: 11px; }

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
        $totalProduction = 0;
        $totalStock = 0;
        $totalFinancing = 0;
        $totalFarmers = 0;
        foreach ($reports as $r) {
            $totalProduction += (float) ($r->total_production_mt ?? 0);
            $totalStock += (float) ($r->warehouse_stock_mt ?? 0);
            $totalFinancing += (float) ($r->financing_volume_fcfa ?? 0);
            $totalFarmers += (int) ($r->active_farmers ?? 0);
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

        <div class="summary-box">
            <h3>About This Report</h3>
            <p>
                Regional reports provide a comprehensive overview of agricultural activity across
                different regions of Cameroon. Each report captures key metrics including total
                production volume, warehouse stock levels, financing volumes in FCFA, and the number
                of active farmers in a given area. These reports enable administrators and partner
                institutions to monitor regional performance, identify trends, and make data-driven
                decisions to support Cameroon's agricultural sector.
            </p>
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th>Region</th>
                    <th>City</th>
                    <th>Type</th>
                    <th>Period</th>
                    <th style="text-align: right;">Production (MT)</th>
                    <th style="text-align: right;">Stock (MT)</th>
                    <th style="text-align: right;">Financing (FCFA)</th>
                    <th style="text-align: right;">Farmers</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reports as $report)
                    <tr>
                        <td class="region-cell">{{ $report->region }}</td>
                        <td>{{ $report->city ?? '-' }}</td>
                        <td>{{ $report->report_type ?? '-' }}</td>
                        <td>
                            @if($report->period_start || $report->period_end)
                                {{ $report->period_start?->format('Y-m-d') ?? '?' }}
                                &mdash;
                                {{ $report->period_end?->format('Y-m-d') ?? '?' }}
                            @else
                                -
                            @endif
                        </td>
                        <td class="num-cell">{{ $report->total_production_mt ? number_format((float) $report->total_production_mt, 2) : '-' }}</td>
                        <td class="num-cell">{{ $report->warehouse_stock_mt ? number_format((float) $report->warehouse_stock_mt, 2) : '-' }}</td>
                        <td class="num-cell">{{ $report->financing_volume_fcfa ? number_format((float) $report->financing_volume_fcfa, 0) : '-' }}</td>
                        <td class="num-cell">{{ $report->active_farmers ?? '-' }}</td>
                    </tr>
                @endforeach
                <tr class="totals-row">
                    <td colspan="4" style="text-align: right;">TOTALS</td>
                    <td class="num-cell">{{ number_format($totalProduction, 2) }}</td>
                    <td class="num-cell">{{ number_format($totalStock, 2) }}</td>
                    <td class="num-cell">{{ number_format($totalFinancing, 0) }}</td>
                    <td class="num-cell">{{ $totalFarmers }}</td>
                </tr>
            </tbody>
        </table>

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
