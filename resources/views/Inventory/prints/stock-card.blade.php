<!DOCTYPE html>
<html>
<head>
    <title>Stock Card - {{ $item->code }}</title>
    <style>
        html, body { font-family: Arial, sans-serif; font-size: 11px; margin: 15px; padding: 0; }
        h2 { margin: 4px 0; font-size: 14px; }
        .agency { text-align: center; margin-bottom: 8px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #000; padding: 3px 5px; }
        th { background: #f0f0f0; text-align: center; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .meta { display: flex; gap: 20px; margin: 8px 0; }
        .meta div { flex: 1; }
        .meta label { font-weight: bold; }
        .form-title { text-align: center; font-weight: bold; font-size: 13px; margin: 4px 0 8px; text-transform: uppercase; border-bottom: 2px solid #000; padding-bottom: 4px; }
        @media print { body { margin: 8px; } }
    </style>
</head>
<body>
    <div class="agency">
        <div>Republic of the Philippines</div>
        <div>Department of Science and Technology - Regional Office IX</div>
    </div>
    <div class="form-title">Stock Card</div>

    <div class="meta">
        <div><label>Entity Name:</label> DOST-IX</div>
        <div><label>Fund Cluster:</label> _________________</div>
    </div>
    <div class="meta">
        <div><label>Item:</label> {{ $item->name }}</div>
        <div><label>Item Code:</label> {{ $item->code }}</div>
    </div>
    <div class="meta">
        <div><label>Category:</label> {{ $item->category?->name ?? '—' }}</div>
        <div><label>Re-Order Point:</label> {{ number_format($item->reorder_level, 2) }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th rowspan="2">Date</th>
                <th rowspan="2">Reference</th>
                <th colspan="2">Receipt</th>
                <th colspan="2">Issue</th>
                <th colspan="2">Balance</th>
                <th rowspan="2">No. of Days to Consume</th>
            </tr>
            <tr>
                <th>Qty</th>
                <th>Unit Cost</th>
                <th>Qty</th>
                <th>Unit Cost</th>
                <th>Qty</th>
                <th>Unit Cost</th>
            </tr>
        </thead>
        <tbody>
            @forelse($ledger as $row)
            <tr>
                <td class="text-center">{{ $row['date'] ? \Carbon\Carbon::parse($row['date'])->format('m/d/Y') : '—' }}</td>
                <td>{{ $row['type'] }}{{ $row['ref'] ? ' #'.$row['ref'] : '' }}</td>
                <td class="text-right">{{ $row['in'] > 0 ? number_format($row['in'], 2) : '' }}</td>
                <td class="text-right">{{ $row['in'] > 0 && $row['unit_cost'] !== null ? number_format($row['unit_cost'], 2) : '' }}</td>
                <td class="text-right">{{ $row['out'] > 0 ? number_format($row['out'], 2) : '' }}</td>
                <td class="text-right">{{ $row['out'] > 0 && $row['unit_cost'] !== null ? number_format($row['unit_cost'], 2) : '' }}</td>
                <td class="text-right">{{ number_format($row['balance'], 2) }}</td>
                <td class="text-right">{{ number_format($row['balance_cost'], 2) }}</td>
                <td>{{ $row['remarks'] }}</td>
            </tr>
            @empty
            <tr><td colspan="9" class="text-center">No transactions recorded.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div style="margin-top: 24px; display: flex; gap: 40px;">
        <div style="flex:1; text-align: center;">
            <div style="border-top: 1px solid #000; padding-top: 4px; margin-top: 30px;">Supply Officer</div>
        </div>
        <div style="flex:1; text-align: center;">
            <div style="border-top: 1px solid #000; padding-top: 4px; margin-top: 30px;">Date</div>
        </div>
    </div>
</body>
</html>
