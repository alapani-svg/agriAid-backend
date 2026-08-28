<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { color: #1a1a1a; margin: 0; padding: 20px; font-size: 12px; }
        .header { text-align: center; border-bottom: 3px solid #026e00; padding-bottom: 20px; margin-bottom: 25px; }
        .header .logo-wrap { display: flex; justify-content: center; margin-bottom: 10px; }
        .header .logo { width: 56px; height: 56px; background: linear-gradient(135deg, #026e00 0%, #00b300 100%); border-radius: 16px; display: inline-flex; align-items: center; justify-content: center; color: white; font-size: 28px; font-weight: 900; box-shadow: 0 4px 12px rgba(2,110,0,0.3); }
        .header h1 { color: #026e00; font-size: 24px; margin: 0; font-weight: 900; letter-spacing: -0.5px; }
        .header .tagline { color: #666; font-size: 10px; margin: 3px 0 0; font-style: italic; }
        .header p { color: #999; font-size: 11px; margin: 8px 0 0; }
        .farmer-info { background: #f0f7f0; border-radius: 8px; padding: 15px; margin-bottom: 20px; }
        .farmer-info h2 { color: #026e00; font-size: 16px; margin: 0 0 8px; }
        .farmer-info .row { display: flex; justify-content: space-between; margin: 3px 0; }
        .farmer-info .label { font-weight: bold; color: #555; }
        .section { margin-bottom: 25px; }
        .section h3 { color: #026e00; font-size: 14px; border-bottom: 1px solid #ddd; padding-bottom: 5px; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; font-size: 11px; }
        th { background: #026e00; color: white; padding: 8px; text-align: left; font-weight: bold; }
        td { padding: 6px 8px; border-bottom: 1px solid #eee; }
        tr:nth-child(even) { background: #f9f9f9; }
        .status-badge { padding: 2px 8px; border-radius: 10px; font-size: 10px; font-weight: bold; }
        .status-good { background: #d4edda; color: #155724; }
        .status-warning { background: #fff3cd; color: #856404; }
        .status-critical { background: #f8d7da; color: #721c24; }
        .status-expired { background: #e2e3e5; color: #383d41; }
        .footer { margin-top: 30px; text-align: center; font-size: 10px; color: #999; border-top: 1px solid #eee; padding-top: 10px; }
        .summary-cards { display: flex; gap: 10px; margin-bottom: 20px; }
        .summary-card { flex: 1; background: #f0f7f0; border-radius: 8px; padding: 12px; text-align: center; }
        .summary-card .number { font-size: 22px; font-weight: bold; color: #026e00; }
        .summary-card .label { font-size: 10px; color: #666; text-transform: uppercase; }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo-wrap"><div class="logo">A</div></div>
        <h1>agriAid Farmer Report</h1>
        <p class="tagline">Empowering Cameroon's Agricultural Future</p>
        <p>Generated on {{ $generatedAt }}</p>
    </div>

    <div class="farmer-info">
        <h2>{{ $farmer->farm_name }}</h2>
        <div class="row"><span class="label">Region:</span><span>{{ $farmer->region }}</span></div>
        <div class="row"><span class="label">Village:</span><span>{{ $farmer->village }}</span></div>
        <div class="row"><span class="label">Farm Size:</span><span>{{ $farmer->farm_size }} hectares</span></div>
        <div class="row"><span class="label">Crops:</span><span>{{ implode(', ', $farmer->crops ?? []) }}</span></div>
        @if($farmer->phone)
        <div class="row"><span class="label">Phone:</span><span>{{ $farmer->phone }}</span></div>
        @endif
        @if($farmer->cooperative_name)
        <div class="row"><span class="label">Cooperative:</span><span>{{ $farmer->cooperative_name }}</span></div>
        @endif
    </div>

    <div class="summary-cards">
        <div class="summary-card">
            <div class="number">{{ count($harvests) }}</div>
            <div class="label">Harvests</div>
        </div>
        <div class="summary-card">
            <div class="number">{{ count($stocks) }}</div>
            <div class="label">Stock Lots</div>
        </div>
        <div class="summary-card">
            <div class="number">{{ count($warehouses) }}</div>
            <div class="label">Warehouses</div>
        </div>
        <div class="summary-card">
            <div class="number">{{ count($receipts) }}</div>
            <div class="label">Receipts</div>
        </div>
    </div>

    <div class="section">
        <h3>Harvests ({{ count($harvests) }})</h3>
        @if(count($harvests) > 0)
        <table>
            <thead>
                <tr>
                    <th>Crop</th>
                    <th>Quantity (kg)</th>
                    <th>Quality</th>
                    <th>Harvest Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($harvests as $h)
                <tr>
                    <td>{{ ucfirst(str_replace('_', ' ', $h['crop_type'])) }}</td>
                    <td>{{ number_format($h['quantity_kg'], 0) }}</td>
                    <td>{{ $h['quality_grade'] ?? '—' }}</td>
                    <td>{{ $h['harvest_date'] ?? '—' }}</td>
                    <td><span class="status-badge status-{{ $h['status'] === 'harvested' ? 'good' : ($h['status'] === 'sold' ? 'good' : 'warning') }}">{{ $h['status'] }}</span></td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <p style="color: #999;">No harvests recorded.</p>
        @endif
    </div>

    <div class="section">
        <h3>Stock in Warehouses ({{ count($stocks) }})</h3>
        @if(count($stocks) > 0)
        <table>
            <thead>
                <tr>
                    <th>Crop</th>
                    <th>Quantity (kg)</th>
                    <th>Warehouse</th>
                    <th>Entry Date</th>
                    <th>Status</th>
                    <th>Utilization</th>
                </tr>
            </thead>
            <tbody>
                @foreach($stocks as $s)
                <tr>
                    <td>{{ ucfirst(str_replace('_', ' ', $s['crop_type'])) }}</td>
                    <td>{{ number_format($s['quantity_kg'], 0) }}</td>
                    <td>{{ $s['warehouse_name'] }}</td>
                    <td>{{ $s['entry_date'] ?? '—' }}</td>
                    <td>{{ $s['status'] }}</td>
                    <td>{{ $s['utilization_percentage'] }}%</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <p style="color: #999;">No stock recorded.</p>
        @endif
    </div>

    <div class="section">
        <h3>Warehouses ({{ count($warehouses) }})</h3>
        @if(count($warehouses) > 0)
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Region</th>
                    <th>Capacity (kg)</th>
                    <th>Used (kg)</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($warehouses as $w)
                <tr>
                    <td>{{ $w['name'] }}</td>
                    <td>{{ $w['region'] }}</td>
                    <td>{{ number_format($w['capacity_total_kg'], 0) }}</td>
                    <td>{{ number_format($w['capacity_used_kg'], 0) }}</td>
                    <td>{{ $w['status'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <p style="color: #999;">No warehouses registered.</p>
        @endif
    </div>

    <div class="section">
        <h3>Warehouse Receipts ({{ count($receipts) }})</h3>
        @if(count($receipts) > 0)
        <table>
            <thead>
                <tr>
                    <th>Receipt #</th>
                    <th>Crop</th>
                    <th>Quantity (kg)</th>
                    <th>Issue Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($receipts as $r)
                <tr>
                    <td>{{ $r['receipt_number'] }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $r['crop_type'])) }}</td>
                    <td>{{ number_format($r['quantity_kg'], 0) }}</td>
                    <td>{{ $r['issue_date'] ?? '—' }}</td>
                    <td>{{ $r['status'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <p style="color: #999;">No receipts issued.</p>
        @endif
    </div>

    <div class="footer">
        <p>This report was generated by agriAid — Smart Agriculture Platform for Cameroon.</p>
        <p>Document is system-generated and does not require a signature.</p>
    </div>
</body>
</html>
