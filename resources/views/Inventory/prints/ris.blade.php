<!DOCTYPE html>
<html>
<head>
    <title>RIS - {{ $ris->ris_no }}</title>
    <style>
        html, body { font-family: Arial, sans-serif; font-size: 11px; margin: 15px; padding: 0; }
        .agency { text-align: center; margin-bottom: 6px; }
        .form-title { text-align: center; font-weight: bold; font-size: 13px; margin: 4px 0 8px; text-transform: uppercase; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        th, td { border: 1px solid #000; padding: 3px 5px; }
        th { background: #f0f0f0; text-align: center; font-size: 10px; }
        .meta { width: 100%; margin-bottom: 6px; }
        .meta td { border: none; padding: 2px 6px; }
        .sig-row td { border: none; text-align: center; padding-top: 24px; }
        @media print { body { margin: 8px; } }
    </style>
</head>
<body>
    <div class="agency">
        <div>Republic of the Philippines &bull; DOST Regional Office IX</div>
    </div>
    <div class="form-title">Requisition and Issue Slip</div>

    <table class="meta">
        <tr>
            <td><b>Entity Name:</b> DOST-IX</td>
            <td><b>Fund Cluster:</b> {{ $ris->fund_cluster ?? '________________' }}</td>
            <td><b>RIS No.:</b> {{ $ris->ris_no }}</td>
        </tr>
        <tr>
            <td><b>Division:</b> {{ $ris->division ?? '________________' }}</td>
            <td><b>Responsibility Center:</b> {{ $ris->responsibility_center ?? '________________' }}</td>
            <td><b>Date:</b> {{ $ris->ris_date?->format('m/d/Y') }}</td>
        </tr>
    </table>

    <table style="margin-bottom: 6px;">
        <tr><td><b>Purpose:</b> {{ $ris->purpose }}</td></tr>
    </table>

    <table>
        <thead>
            <tr>
                <th>Stock No.</th>
                <th>Unit</th>
                <th>Description</th>
                <th>Qty Requested</th>
                <th>Qty Issued</th>
                <th>Remarks</th>
            </tr>
        </thead>
        <tbody>
            @forelse($ris->items as $line)
            <tr>
                <td>{{ $line->item?->code }}</td>
                <td>{{ $line->unit_of_issue }}</td>
                <td>{{ $line->item?->name }}</td>
                <td style="text-align:right">{{ number_format($line->quantity_requested, 2) }}</td>
                <td style="text-align:right">{{ number_format($line->quantity_issued, 2) }}</td>
                <td>{{ $line->remarks }}</td>
            </tr>
            @empty
            <tr><td colspan="6" style="text-align:center">No items.</td></tr>
            @endforelse
        </tbody>
    </table>

    <table style="margin-top: 14px;">
        <tr class="sig-row">
            <td style="width:25%">
                <div style="border-top:1px solid #000; padding-top:3px;">{{ $ris->requestedBy?->profile?->fullname ?? '________________' }}</div>
                <div>Requested by / Date</div>
            </td>
            <td style="width:25%">
                <div style="border-top:1px solid #000; padding-top:3px;">{{ $ris->approvedBy?->profile?->fullname ?? '________________' }}</div>
                <div>Approved by / Date</div>
            </td>
            <td style="width:25%">
                <div style="border-top:1px solid #000; padding-top:3px;">{{ $ris->issuedBy?->profile?->fullname ?? '________________' }}</div>
                <div>Issued by / Date</div>
            </td>
            <td style="width:25%">
                <div style="border-top:1px solid #000; padding-top:3px;">{{ $ris->receivedBy?->profile?->fullname ?? '________________' }}</div>
                <div>Received by / Date</div>
            </td>
        </tr>
    </table>
</body>
</html>
