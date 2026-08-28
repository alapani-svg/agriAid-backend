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
        .summary-box { background: #f0f7f0; border-radius: 10px; padding: 18px 20px; margin-bottom: 25px; }
        .summary-box h3 { font-size: 12px; color: #026e00; margin: 0 0 8px; }
        .summary-box p { font-size: 11px; color: #444; margin: 0 0 10px; line-height: 1.6; }
        .summary-box .weights { font-size: 10px; color: #555; line-height: 1.7; }
        .summary-box .weights .weight-item { display: block; }
        .summary-box .weights .weight-label { font-weight: 700; color: #026e00; }
        .data-table { width: 100%; border-collapse: collapse; margin-bottom: 25px; }
        .data-table th { background: #026e00; color: white; padding: 10px 12px; text-align: left; font-size: 10px; text-transform: uppercase; letter-spacing: 1px; }
        .data-table td { padding: 10px 12px; border-bottom: 1px solid #eee; font-size: 11px; }
        .data-table tr:nth-child(even) { background: #f9f9f9; }
        .data-table .farm-name { font-weight: 700; }
        .data-table .score-cell { font-weight: 700; font-size: 13px; text-align: center; }
        .score-green { color: #155724; background: #d4edda; }
        .score-blue { color: #004085; background: #cce5ff; }
        .score-amber { color: #856404; background: #fff3cd; }
        .score-red { color: #721c24; background: #f8d7da; }
        .score-na { color: #666; background: #eee; }
        .tier-badge { display: inline-block; padding: 2px 10px; border-radius: 20px; font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; }
        .tier-high { background: #d4edda; color: #155724; }
        .tier-strong { background: #cce5ff; color: #004085; }
        .tier-established { background: #fff3cd; color: #856404; }
        .tier-building { background: #ffe0cc; color: #854d0e; }
        .tier-unavailable { background: #eee; color: #666; }
        .footer { border-top: 2px solid #026e00; padding-top: 15px; margin-top: 20px; display: flex; justify-content: space-between; align-items: center; }
        .footer-left { font-size: 9px; color: #999; }
        .footer-right { text-align: right; }
        .footer-right .brand-name { font-size: 10px; font-weight: 700; color: #026e00; }
        .watermark { position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%) rotate(-45deg); font-size: 80px; color: rgba(2, 110, 0, 0.04); font-weight: 900; z-index: -1; pointer-events: none; }
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
            <h2>Credibility Score Report</h2>
            <div class="subtitle">Farmer Trust &amp; Financing Eligibility Overview</div>
        </div>

        <div class="summary-box">
            <h3>About the Credibility Score</h3>
            <p>
                The agriAid credibility score is a 0&ndash;100 metric that quantifies a farmer's
                trustworthiness and financing eligibility based on their activity on the platform.
                It is used by financial institutions to assess loan risk and determine the maximum
                financing term a farmer qualifies for.
            </p>
            <div class="weights">
                <span class="weight-item"><span class="weight-label">30%</span> &mdash; Movement consistency / frequency</span>
                <span class="weight-item"><span class="weight-label">25%</span> &mdash; Independently verified movements</span>
                <span class="weight-item"><span class="weight-label">25%</span> &mdash; Repayment history</span>
                <span class="weight-item"><span class="weight-label">10%</span> &mdash; Length of platform use</span>
                <span class="weight-item"><span class="weight-label">10%</span> &mdash; Volume / value of certified stock</span>
            </div>
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th>Farm Name</th>
                    <th>Region</th>
                    <th>Village</th>
                    <th style="text-align: center;">Score</th>
                    <th>Tier</th>
                    <th style="text-align: center;">Max Term (yrs)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($farmers as $farmer)
                    @php
                        $scoreClass = 'score-na';
                        if ($farmer['score'] !== null) {
                            if ($farmer['score'] >= 80) $scoreClass = 'score-green';
                            elseif ($farmer['score'] >= 60) $scoreClass = 'score-blue';
                            elseif ($farmer['score'] >= 40) $scoreClass = 'score-amber';
                            else $scoreClass = 'score-red';
                        }
                        $tierClass = 'tier-' . ($farmer['tier'] ?? 'unavailable');
                    @endphp
                    <tr>
                        <td class="farm-name">{{ $farmer['farm_name'] }}</td>
                        <td>{{ $farmer['region'] }}</td>
                        <td>{{ $farmer['village'] }}</td>
                        <td class="score-cell {{ $scoreClass }}">{{ $farmer['score'] ?? '-' }}</td>
                        <td><span class="tier-badge {{ $tierClass }}">{{ $farmer['tier'] ?? 'unavailable' }}</span></td>
                        <td style="text-align: center;">{{ $farmer['max_financing_term_years'] ?? 'N/A' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

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
