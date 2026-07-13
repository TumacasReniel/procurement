<!DOCTYPE html>
<html>
<head>
    <title>Waste Material Report</title>
    <style>
        html, body { font-family: Arial, sans-serif; font-size: 11px; margin: 15px; padding: 0; }
        .agency { text-align: center; margin-bottom: 6px; }
        .form-title { text-align: center; font-weight: bold; font-size: 13px; margin: 4px 0 8px; text-transform: uppercase; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        th, td { border: 1px solid #000; padding: 3px 5px; }
        th { background: #f0f0f0; text-align: center; font-size: 10px; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        tfoot td { font-weight: bold; }
        @media print { body { margin: 8px; } }
    </style>
</head>
<body>
    <div class="agency">
        <div>Republic of the Philippines &bull; DOST Regional Office IX</div>
    </div>
    <div class="form-title">Waste Material Report</div>

    <table style="border:none; margin-bottom:6px;">
        <tr>
            <td style="border:none;"><b>Entity Name:</b> DOST-IX</td>
            <td style="border:none;"><b>Period Covered:</b>
                {{ $from ? \Carbon\Carbon::parse($from)->format('m/d/Y') : '___' }}
                to
                {{ $to ? \Carbon\Carbon::parse($to)->format('m/d/Y') : '___' }}
            </td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Adj. No.</th>
                <th>Item Code</th>
                <th>Item Name</th>
                <th>Qty Disposed</th>
                <th>Reason</th>
                <th>Prepared by</th>
                <th>Approved by</th>
            </tr>
        </thead>
        <tbody>
            @forelse($adjustments as $row)
            <tr>
                <td class="text-center">{{ $row->adjustment_date?->format('m/d/Y') }}</td>
                <td>{{ $row->adjustment_no }}</td>
                <td>{{ $row->item?->code }}</td>
                <td>{{ $row->item?->name }}</td>
                <td class="text-right">{{ number_format($row->quantity_adjusted, 2) }}</td>
                <td>{{ $row->reason }}</td>
                <td>{{ $row->adjustedBy?->profile?->fullname }}</td>
                <td>{{ $row->approvedBy?->profile?->fullname }}</td>
            </tr>
            @empty
            <tr><td colspan="8" class="text-center">No waste material records found.</td></tr>
            @endforelse
        </tbody>
        @if($adjustments->count())
        <tfoot>
            <tr>
                <td colspan="4" class="text-right">Total Disposed:</td>
                <td class="text-right">{{ number_format($adjustments->sum('quantity_adjusted'), 2) }}</td>
                <td colspan="3"></td>
            </tr>
        </tfoot>
        @endif
    </table>

    <div style="margin-top:24px; display:flex; gap:40px;">
        <div style="flex:1; text-align:center;">
            <div style="border-top:1px solid #000; padding-top:3px; margin-top:30px;">Supply Officer / Date</div>
        </div>
        <div style="flex:1; text-align:center;">
            <div style="border-top:1px solid #000; padding-top:3px; margin-top:30px;">Head of Agency / Date</div>
        </div>
    </div>
</body>
</html>
