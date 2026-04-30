@php
    $totalAmount = 0;

    foreach ($items as $item) {
        $totalAmount += ((float) $item->item_quantity * (float) $item->item_unit_cost);
    }

    $requestedName = strtoupper(
        $procurement->requested_by?->profile?->fullname
            ?? $procurement->requested_by?->profile?->full_name
            ?? $procurement->requested_by?->name
            ?? ''
    );

    $approvedName = strtoupper(
        $procurement->approved_by?->profile?->fullname
            ?? $procurement->approved_by?->profile?->full_name
            ?? $procurement->approved_by?->name
            ?? ''
    );

    $requestedDesignation = $procurement->requested_by?->org_chart?->designation?->name
        ?? $procurement->requested_by?->designation
        ?? 'Division Head';

    $approvedDesignation = $procurement->approved_by?->org_chart?->designation?->name
        ?? $procurement->approved_by?->designation
        ?? 'Regional Director';

    $basePageHeight = 470;
    $estimatedContentHeight = 0;

    foreach ($items as $item) {
        $plainDescription = trim(strip_tags((string) $item->item_description));
        $charCount = strlen($plainDescription) + strlen((string) $item->item_name);
        $lines = max(substr_count($plainDescription, "\n") + 1, ceil($charCount / 70));
        $estimatedContentHeight += ($lines * 15) + 12;
    }

    $fillerHeight = $estimatedContentHeight < $basePageHeight
        ? $basePageHeight - $estimatedContentHeight
        : 0;
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 42px 46px 46px 46px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            color: #000;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 9px;
            line-height: 1.35;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .bold { font-weight: bold; }
        .uppercase { text-transform: uppercase; }
        .nowrap { white-space: nowrap; }

        .document-header {
            position: relative;
            min-height: 72px;
            margin-bottom: 8px;
        }

        .agency-logo {
            position: absolute;
            top: 0;
            left: 36px;
            width: 52px;
            height: 52px;
        }

        .agency-copy {
            text-align: center;
            padding-top: 2px;
        }

        .agency-copy .republic {
            font-size: 10px;
            text-transform: uppercase;
        }

        .agency-copy .department {
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .form-code {
            position: absolute;
            top: 0;
            right: 0;
            min-width: 110px;
            border: 1px solid #000;
            padding: 4px 8px;
            text-align: center;
            font-size: 9px;
            line-height: 1.25;
        }

        .document-title {
            margin-top: 14px;
            text-align: center;
            font-size: 12px;
            font-weight: bold;
            letter-spacing: .02em;
            text-transform: uppercase;
        }

        .meta-table {
            border: 1.5px solid #000;
            margin-top: 6px;
        }

        .meta-table td {
            border: 1px solid #000;
            padding: 5px 7px;
            vertical-align: middle;
        }

        .field-label {
            font-weight: bold;
        }

        .field-value {
            display: inline-block;
            min-width: 75px;
            border-bottom: 1px solid #000;
            padding: 0 3px 1px;
            font-weight: bold;
        }

        .items-table {
            border: 1.5px solid #000;
            border-top: 0;
            table-layout: fixed;
        }

        thead {
            display: table-header-group;
        }

        .items-table th {
            border: 1px solid #000;
            padding: 6px 5px;
            text-align: center;
            vertical-align: middle;
            font-weight: bold;
            background: #fff;
        }

        .items-table td {
            border: 1px solid #000;
            padding: 5px 7px;
            vertical-align: top;
            word-wrap: break-word;
        }

        .item-name {
            display: block;
            margin-bottom: 3px;
            font-weight: bold;
        }

        .item-description {
            line-height: 1.35;
        }

        .total-row td {
            padding: 6px 7px;
            font-weight: bold;
        }

        .purpose-box {
            border-left: 1.5px solid #000;
            border-right: 1.5px solid #000;
            border-bottom: 1px solid #000;
            padding: 9px 10px;
            min-height: 45px;
        }

        .signature-table {
            border-left: 1.5px solid #000;
            border-right: 1.5px solid #000;
            border-bottom: 1.5px solid #000;
        }

        .signature-table td {
            padding: 6px 8px;
            vertical-align: middle;
        }

        .signature-heading td {
            padding-top: 8px;
            font-weight: bold;
            text-align: center;
        }

        .signature-line {
            height: 34px;
            text-align: center;
            vertical-align: bottom !important;
        }

        .name-line {
            display: inline-block;
            min-width: 210px;
            border-bottom: 1px solid #000;
            padding-bottom: 1px;
            font-weight: bold;
            text-align: center;
        }

        .designation {
            text-align: center;
            font-size: 8.5px;
        }

        .footer-note {
            position: fixed;
            left: 0;
            bottom: 0;
            width: 100%;
        }
    </style>
</head>
<body>
    <div class="document-header">
        <img src="{{ public_path('images/logo-sm.png') }}" alt="DOST Logo" class="agency-logo">

        <div class="agency-copy">
            <div class="republic">Republic of the Philippines</div>
            <div class="department">Department of Science and Technology</div>
            <div class="document-title">Purchase Request</div>
        </div>

        <div class="form-code">
            <div class="bold">FASS-PUR F08</div>
            <div>Rev. 1/07-01-23</div>
        </div>
    </div>

    <table class="meta-table">
        <tr>
            <td colspan="4">
                <span class="field-label">Entity Name:</span>
                <span class="field-value">Department of Science and Technology - IX</span>
            </td>
            <td colspan="2">
                <span class="field-label">Fund Cluster:</span>
                <span class="field-value">{{ $procurement->fund_cluster?->name ?? 'Regular Fund' }}</span>
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <span class="field-label">Office/Section:</span>
                <span class="field-value">{{ $procurement->division?->name ?? 'N/A' }}</span>
            </td>
            <td colspan="2">
                <span class="field-label">PR No.:</span>
                <span class="field-value">{{ $procurement->code }}</span>
            </td>
            <td colspan="2">
                <span class="field-label">Date:</span>
                <span class="field-value">{{ $procurement->date ? date('m-d-Y', strtotime($procurement->date)) : 'N/A' }}</span>
            </td>
        </tr>
        <tr>
            <td colspan="6">
                <span class="field-label">Responsibility Center Code:</span>
                <span class="field-value">{{ $procurement->unit?->responsibility_center_code ?? 'N/A' }}</span>
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th width="8%">Stock No.</th>
                <th width="8%">Unit</th>
                <th width="46%">Item Description</th>
                <th width="10%">Qty</th>
                <th width="14%">Unit Cost</th>
                <th width="14%">Total Cost</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($items as $item)
                <tr>
                    <td class="text-center">{{ $item->item_no }}</td>
                    <td class="text-center">{{ $item->item_unit_type?->name_short }}</td>
                    <td>
                        <span class="item-name">{{ $item->item_name }}</span>
                        <div class="item-description">{!! $item->item_description !!}</div>
                    </td>
                    <td class="text-center nowrap">{{ $item->item_quantity }}</td>
                    <td class="text-right nowrap">{{ number_format((float) $item->item_unit_cost, 2) }}</td>
                    <td class="text-right nowrap bold">{{ number_format((float) $item->item_quantity * (float) $item->item_unit_cost, 2) }}</td>
                </tr>
            @endforeach

            @if ($fillerHeight > 0)
                <tr>
                    <td style="height: {{ $fillerHeight }}px; border-bottom: none;"></td>
                    <td style="border-bottom: none;"></td>
                    <td style="border-bottom: none;"></td>
                    <td style="border-bottom: none;"></td>
                    <td style="border-bottom: none;"></td>
                    <td style="border-bottom: none;"></td>
                </tr>
            @endif

            <tr class="total-row">
                <td colspan="5" class="text-right">TOTAL</td>
                <td class="text-right">{{ number_format($totalAmount, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="purpose-box">
        <span class="field-label">Purpose:</span>
        {{ $procurement->purpose }}
    </div>

    <table class="signature-table">
        <tr class="signature-heading">
            <td width="16%"></td>
            <td width="42%">Requested By</td>
            <td width="42%">Approved By</td>
        </tr>
        <tr>
            <td class="field-label">Signature:</td>
            <td class="signature-line">______________________________</td>
            <td class="signature-line">______________________________</td>
        </tr>
        <tr>
            <td class="field-label">Printed Name:</td>
            <td class="text-center"><span class="name-line">{{ $requestedName }}</span></td>
            <td class="text-center"><span class="name-line">{{ $approvedName }}</span></td>
        </tr>
        <tr>
            <td class="field-label">Designation:</td>
            <td class="designation">{{ $requestedDesignation }}</td>
            <td class="designation">{{ $approvedDesignation }}</td>
        </tr>
    </table>

    <script type="text/php">
        if ( isset($pdf) ) {
            $font = $fontMetrics->get_font("Arial, Helvetica, sans-serif", "normal");
            $size = 8;
            $width = $pdf->get_width();
            $height = $pdf->get_height();
            $y_axis = $height - 25;

            $text_code = "{{ $procurement->code }}";
            $pdf->page_text(35, $y_axis, $text_code, $font, $size, array(0,0,0));

            $text_page = "Page {PAGE_NUM} of {PAGE_COUNT}";
            $text_width = $fontMetrics->get_text_width($text_page, $font, $size);
            $pdf->page_text($width - $text_width - 35, $y_axis, $text_page, $font, $size, array(0,0,0));
        }
    </script>
</body>
</html>
