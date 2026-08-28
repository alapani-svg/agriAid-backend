<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { margin: 0; padding: 0; color: #1a1a1a; font-size: 12px; }
        .page { width: 100%; max-width: 800px; margin: 0 auto; padding: 30px 40px; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 3px solid #026e00; padding-bottom: 20px; margin-bottom: 25px; }
        .brand { display: flex; align-items: center; gap: 14px; }
        .logo { width: 56px; height: 56px; background: linear-gradient(135deg, #026e00 0%, #00b300 100%); border-radius: 16px; display: flex; align-items: center; justify-content: center; color: white; font-size: 28px; font-weight: 900; box-shadow: 0 4px 12px rgba(2,110,0,0.3); }
        .brand-text h1 { color: #026e00; font-size: 24px; margin: 0; font-weight: 900; letter-spacing: -0.5px; }
        .brand-text p { color: #666; font-size: 10px; margin: 3px 0 0; font-style: italic; }
        .doc-meta { text-align: right; }
        .doc-meta .label { font-size: 9px; color: #999; text-transform: uppercase; letter-spacing: 1px; }
        .doc-meta .date { font-size: 11px; color: #555; margin-top: 4px; }
        .title-section { text-align: center; margin-bottom: 25px; }
        .title-section h2 { font-size: 18px; color: #1a1a1a; margin: 0; text-transform: uppercase; letter-spacing: 2px; }
        .title-section .subtitle { font-size: 10px; color: #999; margin-top: 4px; }
        .section-title { font-size: 12px; color: #026e00; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin: 0 0 10px; border-left: 4px solid #026e00; padding-left: 8px; }
        .profile-grid { display: table; width: 100%; border-collapse: collapse; margin-bottom: 25px; }
        .profile-row { display: table-row; }
        .profile-cell { display: table-cell; padding: 8px 12px; border-bottom: 1px solid #eee; font-size: 11px; }
        .profile-label { color: #999; text-transform: uppercase; letter-spacing: 1px; font-size: 9px; width: 30%; }
        .profile-value { color: #1a1a1a; font-weight: 600; }
        .verified-badge { display: inline-block; padding: 2px 10px; border-radius: 20px; font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; background: #d4edda; color: #155724; }
        .unverified-badge { display: inline-block; padding: 2px 10px; border-radius: 20px; font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; background: #eee; color: #666; }
        .data-table { width: 100%; border-collapse: collapse; margin-bottom: 25px; }
        .data-table th { background: #026e00; color: white; padding: 10px 12px; text-align: left; font-size: 10px; text-transform: uppercase; letter-spacing: 1px; }
        .data-table td { padding: 10px 12px; border-bottom: 1px solid #eee; font-size: 11px; }
        .data-table tr:nth-child(even) { background: #f9f9f9; }
        .empty-note { font-size: 10px; color: #999; font-style: italic; padding: 8px 0; }
        .score-box { background: #f0f7f0; border-radius: 10px; padding: 18px 20px; margin-bottom: 25px; }
        .score-box .score-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
        .score-box .score-value { font-size: 28px; font-weight: 900; color: #026e00; }
        .score-box .tier-badge { display: inline-block; padding: 3px 12px; border-radius: 20px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; background: #d4edda; color: #155724; }
        .score-box .breakdown { font-size: 10px; color: #555; line-height: 1.8; }
        .score-box .breakdown .weight-label { font-weight: 700; color: #026e00; }
        .score-na { color: #666; }
        .footer { border-top: 2px solid #026e00; padding-top: 15px; margin-top: 20px; display: flex; justify-content: space-between; align-items: center; }
        .footer-left { font-size: 9px; color: #999; }
        .footer-right { text-align: right; }
        .footer-right .brand-name { font-size: 10px; font-weight: 700; color: #026e00; }
        .watermark { position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%) rotate(-45deg); font-size: 80px; color: rgba(2, 110, 0, 0.04); font-weight: 900; z-index: -1; pointer-events: none; }
        .page-break { page-break-before: always; }
    </style>
</head>
<body>
    <div class="watermark">agriAid</div>
    <div class="page">
        <div class="header">
            <div class="brand">
                <div class="logo">A</div>
                <div class="brand-text">
                    <h1>agriAid</h1>
                    <p>Empowering Cameroon's Agricultural Future</p>
                </div>
            </div>
            <div class="doc-meta">
                <div class="label">Report Date</div>
                <div class="date">{{ $generatedAt }}</div>
            </div>
        </div>

        <div class="title-section">
            <h2>Farmer Registry Report</h2>
            <div class="subtitle">Detailed Farmer Profile &amp; Activity Overview</div>
        </div>

        {{-- Farmer profile --}}
        <h3 class="section-title">Farmer Profile</h3>
        <div class="profile-grid">
            <div class="profile-row">
                <div class="profile-cell profile-label">Farm Name</div>
                <div class="profile-cell profile-value">{{ $farmer->farm_name }}</div>
            </div>
            @if ($farmer->user)
            <div class="profile-row">
                <div class="profile-cell profile-label">Owner</div>
                <div class="profile-cell profile-value">{{ $farmer->user->name }}</div>
            </div>
            <div class="profile-row">
                <div class="profile-cell profile-label">Email</div>
                <div class="profile-cell profile-value">{{ $farmer->user->email }}</div>
            </div>
            @endif
            <div class="profile-row">
                <div class="profile-cell profile-label">Region</div>
                <div class="profile-cell profile-value">{{ $farmer->region }}</div>
            </div>
            <div class="profile-row">
                <div class="profile-cell profile-label">Village</div>
                <div class="profile-cell profile-value">{{ $farmer->village }}</div>
            </div>
            <div class="profile-row">
                <div class="profile-cell profile-label">Phone</div>
                <div class="profile-cell profile-value">{{ $farmer->phone ?: '—' }}</div>
            </div>
            <div class="profile-row">
                <div class="profile-cell profile-label">Farm Size</div>
                <div class="profile-cell profile-value">{{ $farmer->farm_size }} ha</div>
            </div>
            <div class="profile-row">
                <div class="profile-cell profile-label">Crops</div>
                <div class="profile-cell profile-value">{{ is_array($farmer->crops) ? implode(', ', $farmer->crops) : ($farmer->crops ?: '—') }}</div>
            </div>
            <div class="profile-row">
                <div class="profile-cell profile-label">Cooperative</div>
                <div class="profile-cell profile-value">{{ $farmer->cooperative_name ?: '—' }}</div>
            </div>
            <div class="profile-row">
                <div class="profile-cell profile-label">Verified Status</div>
                <div class="profile-cell profile-value">
                    @if ($farmer->verified)
                        <span class="verified-badge">Verified</span>
                    @else
                        <span class="unverified-badge">Unverified</span>
                    @endif
                </div>
            </div>
            <div class="profile-row">
                <div class="profile-cell profile-label">Account Status</div>
                <div class="profile-cell profile-value">{{ ucfirst($farmer->status) }}</div>
            </div>
            <div class="profile-row">
                <div class="profile-cell profile-label">Registration Date</div>
                <div class="profile-cell profile-value">{{ $farmer->created_at?->format('Y-m-d') ?? '—' }}</div>
            </div>
        </div>

        {{-- Credibility score --}}
        @if ($credibility)
            <h3 class="section-title">Credibility Score</h3>
            <div class="score-box">
                <div class="score-header">
                    <div>
                        <span class="score-value">{{ $credibility['score'] }}</span>
                        <span style="font-size: 11px; color: #999;">/ 100</span>
                    </div>
                    <div>
                        <span class="tier-badge">{{ $credibility['tier_label'] }}</span>
                    </div>
                </div>
                <div class="breakdown">
                    <span class="weight-label">Max financing term:</span> {{ $credibility['max_financing_term_years'] }} years<br/>
                    <span class="weight-label">Movement consistency:</span> {{ $credibility['movement_consistency_pct'] }}%<br/>
                    <span class="weight-label">Verified movements:</span> {{ $credibility['verified_movements_pct'] }}%<br/>
                    <span class="weight-label">Repayment history:</span> {{ $credibility['repayment_history_pct'] }}%<br/>
                    <span class="weight-label">Platform use length:</span> {{ $credibility['platform_use_length_pct'] }}%<br/>
                    <span class="weight-label">Certified stock volume:</span> {{ $credibility['certified_stock_volume_pct'] }}%
                </div>
            </div>
        @endif

        {{-- Harvest history --}}
        <h3 class="section-title">Harvest History</h3>
        @if ($harvests->isNotEmpty())
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Crop</th>
                        <th style="text-align: right;">Quantity (kg)</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($harvests as $harvest)
                        <tr>
                            <td>{{ $harvest->harvest_date?->format('Y-m-d') ?? '—' }}</td>
                            <td>{{ ucfirst($harvest->crop_type) }}</td>
                            <td style="text-align: right;">{{ number_format((float) $harvest->quantity_kg, 2) }}</td>
                            <td>{{ ucfirst(str_replace('_', ' ', $harvest->status ?? '')) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p class="empty-note">No harvest records on file.</p>
        @endif

        {{-- Current stock --}}
        <h3 class="section-title">Current Stock</h3>
        @if ($stocks->isNotEmpty())
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Crop</th>
                        <th style="text-align: right;">Quantity (kg)</th>
                        <th>Status</th>
                        <th>Validation</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($stocks as $stock)
                        <tr>
                            <td>{{ ucfirst($stock->crop_type) }}</td>
                            <td style="text-align: right;">{{ number_format((float) $stock->quantity_kg, 2) }}</td>
                            <td>{{ ucfirst(str_replace('_', ' ', $stock->status ?? '')) }}</td>
                            <td>{{ ucfirst(str_replace('_', ' ', $stock->validation_status ?? '—')) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p class="empty-note">No current stock records on file.</p>
        @endif

        {{-- Warehouse receipts --}}
        <h3 class="section-title">Warehouse Receipts</h3>
        @if ($receipts->isNotEmpty())
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Receipt No.</th>
                        <th>Crop</th>
                        <th style="text-align: right;">Quantity (kg)</th>
                        <th>Issue Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($receipts as $receipt)
                        <tr>
                            <td>{{ $receipt->receipt_number }}</td>
                            <td>{{ ucfirst($receipt->crop_type) }}</td>
                            <td style="text-align: right;">{{ number_format((float) $receipt->quantity_kg, 2) }}</td>
                            <td>{{ $receipt->issue_date?->format('Y-m-d') ?? '—' }}</td>
                            <td>{{ ucfirst($receipt->status) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p class="empty-note">No warehouse receipts on file.</p>
        @endif

        <div class="footer">
            <div class="footer-left">
                Generated by agriAid Platform on {{ $generatedAt }}
            </div>
            <div class="footer-right">
                <div class="brand-name">agriAid Platform</div>
            </div>
        </div>
    </div>
</body>
</html>
