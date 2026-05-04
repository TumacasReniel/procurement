@php
    $preparedName = strtoupper(
        $procurement->created_by?->profile?->fullname
            ?? $procurement->created_by?->profile?->full_name
            ?? $procurement->created_by?->name
            ?? ''
    );

    $submittedName = strtoupper(
        $procurement->requested_by?->profile?->fullname
            ?? $procurement->requested_by?->profile?->full_name
            ?? $procurement->requested_by?->name
            ?? ''
    );

    $modeOfProcurement = $procurement->codes
        ?->pluck('procurement_code.mode_of_procurement.name')
        ->filter()
        ->unique()
        ->implode(', ');

    $planName = $procurement->plan_name_override ?? $procurement->reference_app?->name ?? 'PPMP';
    $planShortName = match ($planName) {
        'Annual Procurement Plan' => 'APP',
        'Supplemental Procurement Plan' => 'SPP',
        default => 'PPMP',
    };
    $documentTitle = match ($planName) {
        'Annual Procurement Plan' => 'ANNUAL PROCUREMENT PLAN (APP) NO.',
        'Supplemental Procurement Plan' => 'SUPPLEMENTAL PROCUREMENT PLAN (SPP) NO.',
        default => 'PROJECT PROCUREMENT MANAGEMENT PLAN (PPMP) NO.',
    };
    $isFinal = $planName !== 'PPMP' || $procurement->status?->name === 'Reviewed';
    $ppmpYear = $procurement->date ? date('Y', strtotime($procurement->date)) : date('Y', strtotime((string) $procurement->created_at));
    $ppmpNo = $procurement->ppmp_no_override ?: 'PPMP-' . $ppmpYear . '-' . str_pad((string) $procurement->id, 4, '0', STR_PAD_LEFT);
    $prNo = $procurement->pr_no_override ?: ($procurement->code ?: '');
    $unitName = $procurement->unit_name_override ?: ($procurement->unit?->name ?? '-');
    $classificationName = $procurement->classification_override ?: ($procurement->classification?->name ?? '-');
    $sourceOfFunds = $procurement->source_of_funds_override ?: ($procurement->fund_cluster?->name ?? '-');
    $startDate = $procurement->start_date_override ?: $procurement->date;
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{{ $planShortName }} {{ $ppmpNo }}</title>
    <style>
        @page {
            margin: 18px 22px 34px 22px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            color: #000;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 8.2px;
            line-height: 1.22;
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

        .agency-header {
            position: relative;
            min-height: 96px;
            border-bottom: 2.5px solid #000;
            margin-bottom: 4px;
        }

        .agency-logo {
            position: absolute;
            top: -5px;
            left: 62px;
            width: 92px;
            height: 92px;
            object-fit: contain;
        }

        .bagong-logo {
            position: absolute;
            top: 0;
            right: 58px;
            width: 108px;
            height: 88px;
            object-fit: contain;
        }

        .agency-copy {
            padding-top: 10px;
            text-align: center;
            font-weight: bold;
        }

        .agency-copy .line-one {
            font-size: 17px;
        }

        .agency-copy .department {
            margin-top: 4px;
            font-size: 18px;
        }

        .agency-copy .region {
            margin-top: 4px;
            font-size: 17px;
        }

        .title-block {
            margin: 2px 0 8px;
            text-align: center;
        }

        .document-title {
            font-size: 19px;
            font-weight: bold;
            line-height: 1.15;
        }

        .title-line {
            display: inline-block;
            min-width: 82px;
            border-bottom: 2.5px solid #000;
            padding: 0 8px 1px;
        }

        .status-row {
            margin-top: 4px;
            text-align: center;
        }

        .status-option {
            display: inline-block;
            margin: 0 50px;
            font-size: 18px;
            font-weight: bold;
            vertical-align: middle;
        }

        .box {
            display: inline-block;
            width: 30px;
            height: 30px;
            border: 1.5px solid #000;
            margin-right: 10px;
            text-align: center;
            line-height: 29px;
            vertical-align: middle;
            font-size: 18px;
        }

        .meta-table {
            margin-bottom: 5px;
            border: 1px solid #000;
        }

        .meta-table td {
            border: 1px solid #000;
            padding: 3px 6px;
            vertical-align: top;
        }

        .field-label {
            font-weight: bold;
        }

        .field-value {
            font-weight: bold;
        }

        .ppmp-table {
            table-layout: fixed;
            border: 1.8px solid #000;
        }

        thead {
            display: table-header-group;
        }

        .ppmp-table th,
        .ppmp-table td {
            border: 1px solid #000;
            padding: 3px 4px;
            vertical-align: top;
            word-wrap: break-word;
        }

        .ppmp-table th {
            text-align: center;
            font-size: 7px;
            font-weight: bold;
            vertical-align: middle;
            line-height: 1.12;
        }

        .column-label th {
            padding: 2px 4px 1px;
            font-size: 6.8px;
        }

        .item-name {
            display: block;
            margin-bottom: 2px;
            font-weight: bold;
        }

        .item-description {
            line-height: 1.25;
        }

        .item-list {
            margin: 0;
            padding-left: 12px;
        }

        .item-list li {
            margin-bottom: 3px;
        }

        .total-row td {
            font-weight: bold;
            background: #fff;
        }

        .signatory-table {
            margin-top: 16px;
            page-break-inside: avoid;
        }

        .signatory-table td {
            padding: 22px 18px 0;
            text-align: center;
            vertical-align: bottom;
        }

        .signature-line {
            display: block;
            min-height: 18px;
            border-bottom: 1px solid #000;
            padding-bottom: 2px;
            font-weight: bold;
        }

        .signature-label {
            margin-top: 4px;
            font-size: 8px;
            font-weight: bold;
        }

        .signature-role {
            margin-top: 2px;
            font-size: 7.5px;
        }
    </style>
</head>
<body>
    <div class="agency-header">
        <img src="{{ public_path('images/logo-sm.png') }}" alt="DOST Logo" class="agency-logo">
        <div class="agency-copy">
            <div class="line-one">Republic of the Philippines</div>
            <div class="department">DEPARTMENT OF SCIENCE AND TECHNOLOGY</div>
            <div class="region">Regional Office IX</div>
        </div>
        @if (file_exists(public_path('images/bp-logo.webp')))
            <img src="{{ public_path('images/bp-logo.webp') }}" alt="Bagong Pilipinas Logo" class="bagong-logo">
        @endif
    </div>

    <div class="title-block">
        <div class="document-title">
            {{ $documentTitle }}
            <span class="title-line">{{ $ppmpNo }}</span>
        </div>
        <div class="status-row">
            <span class="status-option"><span class="box">{{ $isFinal ? '' : 'X' }}</span> INDICATIVE</span>
            <span class="status-option"><span class="box">{{ $isFinal ? 'X' : '' }}</span> FINAL</span>
        </div>
    </div>

    <table class="meta-table">
        <tr>
            <td width="25%">
                <span class="field-label">{{ $planShortName === 'APP' ? 'Included PR Nos.:' : 'PR No.:' }}</span>
                <span class="field-value">{{ $prNo ?: '-' }}</span>
            </td>
            <td width="25%">
                <span class="field-label">Plan:</span>
                <span class="field-value">{{ $planName }}</span>
            </td>
            <td width="25%">
                <span class="field-label">{{ $planShortName === 'APP' ? 'Coverage:' : 'Unit:' }}</span>
                <span class="field-value">{{ $unitName }}</span>
            </td>
            <td width="25%">
                <span class="field-label">Date:</span>
                <span class="field-value">{{ $procurement->date ? date('F j, Y', strtotime($procurement->date)) : '-' }}</span>
            </td>
        </tr>
        <tr>
            <td colspan="4">
                <span class="field-label">Total ABC:</span>
                <span class="field-value">PHP {{ number_format((float) $totalAmount, 2) }}</span>
            </td>
        </tr>
    </table>

    <table class="ppmp-table">
        <thead>
            <tr>
                <th width="15%">General Description and Objective of the Project to be Procured</th>
                <th width="10%">Type of the Project to be Procured</th>
                <th width="12%">Quantity and Size of the Project to be Procured</th>
                <th width="10%">Recommended Mode of Procurement</th>
                <th width="8%">Pre-Procurement Conference, if applicable</th>
                <th width="8%">Start of Procurement Activity</th>
                <th width="8%">End of Procurement Activity</th>
                <th width="8%">Expected Delivery/Implementation Period</th>
                <th width="9%">Source of Funds</th>
                <th width="8%">Estimated Budget / Authorized Budgetary Allocation (PHP)</th>
                <th width="7%">Attached Supporting Documents</th>
                <th width="7%">Remarks</th>
            </tr>
            <tr class="column-label">
                @for ($column = 1; $column <= 12; $column++)
                    <th>Column {{ $column }}</th>
                @endfor
            </tr>
        </thead>
        <tbody>
            @if ($items->isNotEmpty())
                <tr>
                    <td>
                        <span class="item-name">{{ $procurement->title ?: $procurement->purpose }}</span>
                        <div class="item-description">{{ $procurement->purpose }}</div>
                    </td>
                    <td class="text-center">{{ $classificationName }}</td>
                    <td>
                        <ul class="item-list">
                            @foreach ($items as $item)
                                @php
                                    $quantity = (float) ($item->item_quantity ?? 0);
                                    $unitCost = (float) ($item->item_unit_cost ?? 0);
                                    $lineTotal = (float) ($item->total_cost ?? ($quantity * $unitCost));
                                    $unitName = $item->item_unit_type?->name ?? $item->item_unit_type?->name_short ?? '';
                                @endphp
                                <li>
                                    <strong>{{ $item->item_name ?: 'Item ' . $loop->iteration }}</strong><br>
                                    {{ rtrim(rtrim(number_format($quantity, 2), '0'), '.') }} {{ $unitName }}
                                    x PHP {{ number_format($unitCost, 2) }}
                                    = PHP {{ number_format($lineTotal, 2) }}
                                </li>
                            @endforeach
                        </ul>
                    </td>
                    <td>{{ $modeOfProcurement ?: '-' }}</td>
                    <td class="text-center">N/A</td>
                    <td class="text-center">{{ $startDate ? date('m/d/Y', strtotime($startDate)) : '-' }}</td>
                    <td class="text-center">-</td>
                    <td class="text-center">-</td>
                    <td>{{ $sourceOfFunds }}</td>
                    <td class="text-right nowrap">{{ number_format((float) $totalAmount, 2) }}</td>
                    <td class="text-center">-</td>
                    <td class="text-center">-</td>
                </tr>
            @else
                <tr>
                    <td colspan="12" class="text-center">No items found.</td>
                </tr>
            @endif
            <tr class="total-row">
                <td colspan="9" class="text-right">TOTAL ABC</td>
                <td class="text-right nowrap">{{ number_format((float) $totalAmount, 2) }}</td>
                <td colspan="2"></td>
            </tr>
        </tbody>
    </table>

    <table class="signatory-table">
        <tr>
            <td width="50%">
                <span class="signature-line">{{ $preparedName }}</span>
                <div class="signature-label">Prepared By</div>
                <div class="signature-role">{{ $planShortName === 'SPP' ? 'Agency' : 'End-User / Requesting Office' }}</div>
            </td>
            <td width="50%">
                <span class="signature-line">{{ $submittedName }}</span>
                <div class="signature-label">Submitted By</div>
                <div class="signature-role">Head of Requesting Unit</div>
            </td>
        </tr>
    </table>

    <script type="text/php">
        if ( isset($pdf) ) {
            $font = $fontMetrics->get_font("Arial, Helvetica, sans-serif", "normal");
            $size = 7;
            $width = $pdf->get_width();
            $height = $pdf->get_height();
            $y_axis = $height - 22;

            $text_code = "{{ $ppmpNo }}";
            $pdf->page_text(28, $y_axis, $text_code, $font, $size, array(0,0,0));

            $text_page = "Page {PAGE_NUM} of {PAGE_COUNT}";
            $text_width = $fontMetrics->get_text_width($text_page, $font, $size);
            $pdf->page_text($width - $text_width - 28, $y_axis, $text_page, $font, $size, array(0,0,0));
        }
    </script>
</body>
</html>
