<!DOCTYPE html>
<html>
<head>
    <title>PAR - {{ $par->par_no }}</title>
    <style>
        html, body { font-family: Arial, sans-serif; font-size: 11px; margin: 15px; padding: 0; }
        .agency { text-align: center; margin-bottom: 6px; }
        .form-title { text-align: center; font-weight: bold; font-size: 13px; margin: 4px 0 8px; text-transform: uppercase; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        th, td { border: 1px solid #000; padding: 3px 5px; }
        th { background: #f0f0f0; text-align: center; font-size: 10px; }
        .meta td { border: none; padding: 2px 6px; }
        @media print { body { margin: 8px; } }
    </style>
</head>
<body>
    <div class="agency">
        <div>Republic of the Philippines &bull; DOST Regional Office IX</div>
    </div>
    <div class="form-title">Property Acknowledgment Receipt</div>

    <table class="meta" style="margin-bottom:6px;">
        <tr>
            <td><b>Entity Name:</b> DOST-IX</td>
            <td><b>Fund Cluster:</b> {{ $par->fund_cluster ?? '_______________' }}</td>
            <td><b>PAR No.:</b> {{ $par->par_no }}</td>
        </tr>
        <tr>
            <td><b>Date:</b> {{ $par->par_date?->format('m/d/Y') }}</td>
            <td colspan="2"></td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th>Qty</th>
                <th>Unit</th>
                <th>Description</th>
                <th>Property No.</th>
                <th>Date Acquired</th>
                <th>Amount</th>
                <th>Remarks</th>
            </tr>
        </thead>
        <tbody>
            @forelse($par->items as $line)
            <tr>
                <td style="text-align:right">{{ number_format($line->quantity, 2) }}</td>
                <td></td>
                <td>{{ $line->description ?? $line->item?->name }}</td>
                <td>{{ $line->property_no }}</td>
                <td style="text-align:center">{{ $line->date_acquired?->format('m/d/Y') }}</td>
                <td style="text-align:right">{{ $line->amount ? number_format($line->amount, 2) : '' }}</td>
                <td>{{ $line->remarks }}</td>
            </tr>
            @empty
            <tr><td colspan="7" style="text-align:center">No items.</td></tr>
            @endforelse
        </tbody>
    </table>

    <table style="margin-top:16px; border:none;">
        <tr>
            <td style="border:none; width:50%; padding-top:28px; text-align:center;">
                <div style="border-top:1px solid #000; padding-top:3px;">{{ $par->issuedBy?->profile?->fullname ?? '________________________' }}</div>
                <div>Issued by (Supply Officer) / Date</div>
            </td>
            <td style="border:none; width:50%; padding-top:28px; text-align:center;">
                <div style="border-top:1px solid #000; padding-top:3px;">{{ $par->receivedBy?->profile?->fullname ?? '________________________' }}</div>
                <div>Received by / Position / Date</div>
            </td>
        </tr>
    </table>
</body>
</html>
