<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { margin: 0; padding: 0; color: #1a1a1a; font-size: 12px; }
        .page { width: 100%; max-width: 595px; margin: 0 auto; padding: 30px 40px; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 3px solid #026e00; padding-bottom: 20px; margin-bottom: 25px; }
        .brand { display: flex; align-items: center; gap: 14px; }
        .logo { width: 56px; height: 56px; object-fit: contain; border-radius: 12px; box-shadow: 0 4px 12px rgba(2,110,0,0.15); }
        .brand-text h1 { color: #026e00; font-size: 24px; margin: 0; font-weight: 900; letter-spacing: -0.5px; }
        .brand-text p { color: #666; font-size: 10px; margin: 3px 0 0; font-style: italic; }
        .receipt-meta { text-align: right; }
        .receipt-meta .label { font-size: 9px; color: #999; text-transform: uppercase; letter-spacing: 1px; }
        .receipt-meta .number { font-size: 16px; font-weight: 900; color: #026e00; font-family: monospace; }
        .receipt-meta .date { font-size: 11px; color: #555; margin-top: 4px; }
        .title-section { text-align: center; margin-bottom: 25px; }
        .title-section h2 { font-size: 18px; color: #1a1a1a; margin: 0; text-transform: uppercase; letter-spacing: 2px; }
        .title-section .subtitle { font-size: 10px; color: #999; margin-top: 4px; }
        .status-badge { display: inline-block; padding: 4px 16px; border-radius: 20px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin-top: 8px; }
        .status-active { background: #d4edda; color: #155724; }
        .status-redeemed { background: #cce5ff; color: #004085; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        .info-grid { display: flex; gap: 20px; margin-bottom: 25px; }
        .info-card { flex: 1; background: #f0f7f0; border-radius: 10px; padding: 15px; }
        .info-card .card-label { font-size: 9px; color: #026e00; text-transform: uppercase; letter-spacing: 1px; font-weight: 700; margin-bottom: 6px; }
        .info-card .card-value { font-size: 13px; font-weight: 700; color: #1a1a1a; }
        .info-card .card-sub { font-size: 10px; color: #666; margin-top: 2px; }
        .details-table { width: 100%; border-collapse: collapse; margin-bottom: 25px; }
        .details-table th { background: #026e00; color: white; padding: 10px 12px; text-align: left; font-size: 10px; text-transform: uppercase; letter-spacing: 1px; }
        .details-table td { padding: 10px 12px; border-bottom: 1px solid #eee; font-size: 12px; }
        .details-table tr:nth-child(even) { background: #f9f9f9; }
        .details-table .crop-name { font-weight: 700; text-transform: capitalize; }
        .details-table .qty { font-weight: 700; color: #026e00; font-size: 14px; }
        .qr-section { display: flex; gap: 20px; align-items: center; background: #f0f7f0; border-radius: 10px; padding: 20px; margin-bottom: 20px; }
        .qr-code { width: 100px; height: 100px; flex-shrink: 0; }
        .qr-info { flex: 1; }
        .qr-info h3 { font-size: 12px; color: #026e00; margin: 0 0 4px; }
        .qr-info p { font-size: 10px; color: #666; margin: 0; line-height: 1.5; }
        .integrity { margin-top: 8px; }
        .integrity .label { font-size: 9px; color: #999; text-transform: uppercase; }
        .integrity .hash { font-family: monospace; font-size: 9px; color: #555; word-break: break-all; }
        .footer { border-top: 2px solid #026e00; padding-top: 15px; margin-top: 20px; display: flex; justify-content: space-between; align-items: center; }
        .footer-left { font-size: 9px; color: #999; }
        .footer-right { text-align: right; }
        .footer-right .verified { font-size: 10px; font-weight: 700; color: #026e00; }
        .footer-right .verified-icon { display: inline-block; width: 14px; height: 14px; background: #026e00; border-radius: 50%; color: white; text-align: center; line-height: 14px; font-size: 8px; margin-right: 4px; }
        .watermark { position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%) rotate(-45deg); font-size: 80px; color: rgba(2, 110, 0, 0.04); font-weight: 900; z-index: -1; pointer-events: none; }
        .terms { font-size: 8px; color: #aaa; margin-top: 15px; line-height: 1.5; }
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
            <div class="brand">
                @if($logoBase64)
                    <img src="{{ $logoBase64 }}" class="logo" />
                @endif
                <div class="brand-text">
                    <h1>agriAid</h1>
                    <p>Smart Agriculture Platform · Cameroon</p>
                </div>
            </div>
            <div class="receipt-meta">
                <div class="label">Receipt Number</div>
                <div class="number">{{ $receipt->receipt_number }}</div>
                <div class="date">Issued: {{ $receipt->issue_date?->format('d M Y') }}</div>
            </div>
        </div>

        <div class="title-section">
            <h2>Warehouse Receipt</h2>
            <div class="subtitle">Digital Collateral Document · Verified &amp; Tamper-Proof</div>
            <div class="status-badge status-{{ $receipt->status }}">{{ $receipt->status }}</div>
        </div>

        <div class="info-grid">
            <div class="info-card">
                <div class="card-label">Farmer / Owner</div>
                <div class="card-value">{{ $farmer?->farm_name ?? 'N/A' }}</div>
                <div class="card-sub">{{ $farmer?->region ?? '' }}{{ $farmer?->village ? ', ' . $farmer->village : '' }}</div>
            </div>
            <div class="info-card">
                <div class="card-label">Warehouse</div>
                <div class="card-value">{{ $warehouse?->name ?? 'N/A' }}</div>
                <div class="card-sub">{{ $warehouse?->region ?? '' }}</div>
            </div>
        </div>

        <table class="details-table">
            <thead>
                <tr>
                    <th>Commodity</th>
                    <th>Quantity</th>
                    <th>Issue Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="crop-name">{{ str_replace('_', ' ', $receipt->crop_type) }}</td>
                    <td class="qty">{{ number_format((float)$receipt->quantity_kg, 0) }} kg</td>
                    <td>{{ $receipt->issue_date?->format('d M Y') }}</td>
                    <td style="text-transform: capitalize;">{{ $receipt->status }}</td>
                </tr>
            </tbody>
        </table>

        <div class="qr-section">
            @if($qrSvg)
            <div class="qr-code">{!! $qrSvg !!}</div>
            @endif
            <div class="qr-info">
                <h3>Scan to Verify</h3>
                <p>Scan this QR code with any device to verify the authenticity and integrity of this warehouse receipt.</p>
                @if($receipt->integrity_hash)
                <div class="integrity">
                    <div class="label">SHA-256 Integrity Hash</div>
                    <div class="hash">{{ $receipt->integrity_hash }}</div>
                </div>
                @endif
            </div>
        </div>

        <div class="footer">
            <div class="footer-left">
                Generated by agriAid on {{ $generatedAt }}<br>
                Document ID: {{ $receipt->receipt_number }}
            </div>
            <div class="footer-right">
                <div class="verified">
                    <span class="verified-icon">&#10003;</span>
                    Digitally Verified
                </div>
            </div>
        </div>

        <div class="terms">
            This warehouse receipt is a digitally signed document issued by agriAid's warehouse management system.
            The integrity hash ensures tamper detection. This document may be used as collateral evidence for
            financial institutions participating in the agriAid ecosystem. All transactions are logged on the
            agriAid platform and can be audited by authorized parties.
        </div>
    </div>
</body>
</html>
