@php
    $preparedName = strtoupper(
        $procurement->created_by?->profile?->fullname
            ?? $procurement->created_by?->profile?->full_name
            ?? $procurement->created_by?->name
            ?? ''
    );
    $preparedDesignation = $procurement->created_by?->org_chart?->designation?->name
        ?? $procurement->created_by?->organization?->position?->name
        ?? $procurement->created_by?->designation
        ?? ($planShortName === 'SPP' ? 'Agency' : 'End-User / Requesting Office');

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
    $submittedUser = $isFinal && $procurement->approved_by
        ? $procurement->approved_by
        : $procurement->requested_by;
    $submittedName = strtoupper(
        $submittedUser?->profile?->fullname
            ?? $submittedUser?->profile?->full_name
            ?? $submittedUser?->name
            ?? ''
    );
    $ppmpYear = $procurement->date ? date('Y', strtotime($procurement->date)) : date('Y', strtotime((string) $procurement->created_at));
    $ppmpNo = $procurement->ppmp_no_override ?: 'PPMP-' . $ppmpYear . '-' . str_pad((string) $procurement->id, 4, '0', STR_PAD_LEFT);
    $prNo = $procurement->pr_no_override ?: ($procurement->code ?: '');
    $unitName = $procurement->unit_name_override ?: ($procurement->unit?->name ?? '-');
    $classificationName = $procurement->classification_override ?: ($procurement->classification?->name ?? '-');
    $sourceOfFunds = $procurement->source_of_funds_override ?: ($procurement->fund_cluster?->name ?? '-');
    $startDate = $procurement->start_date_override ?: $procurement->date;
    $printItems = $items->values();
    $rowspansFor = function ($resolver) use ($printItems) {
        $rowspans = [];
        $lastValue = null;
        $lastIndex = null;

        foreach ($printItems as $index => $item) {
            $value = trim((string) $resolver($item));

            if ($lastIndex !== null && $value === $lastValue) {
                $rowspans[$lastIndex]++;
                $rowspans[$index] = 0;
                continue;
            }

            $lastValue = $value;
            $lastIndex = $index;
            $rowspans[$index] = 1;
        }

        return $rowspans;
    };
    $generalDescriptionRowspans = $rowspansFor(fn ($item) => $item->print_general_description ?: ($procurement->title ?: $procurement->purpose));
    $projectTypeRowspans = $rowspansFor(fn ($item) => $item->project_type ?: ($item->print_classification_name ?: $classificationName));
    $supportingDocumentsRowspans = $rowspansFor(fn ($item) => $item->attached_supporting_documents ?: '-');
    $remarksRowspans = $rowspansFor(fn ($item) => $item->remarks ?: '-');
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
            width: 90px;
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
            margin-bottom:20px;
        }

        .title-line {
            display: inline-block;
            min-width: 2px;
            font-size: 15px;
            border-bottom: 1px solid #000;
            padding: 0 8px 1px;
            color:red;
        }

        .status-row {
            margin-top: 4px;
            text-align: center;
        }

        .status-option {
            display: inline-block;
            margin: 0 50px;
            font-size: 15px;
            font-weight: bold;
            vertical-align: middle;
        }

     .box {
        display: inline-block;
        width: 14px;
        height: 14px;
        border: 1px solid #000;
        vertical-align: middle;
        margin-right: 4px;
    }

    .box.filled {
        background-color: #000;
    }

    @media print {
        .box.filled {
            background-color: #000 !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
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

        .table-header-info {
            margin: 4px 0 3px;
            font-size: 10px;
            line-height: 1.45;
        }

        .table-header-info div {
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

        .ppmp-table .group-header th {
            padding: 8px 4px;
            font-size: 7.2px;
            line-height: 1.1;
        }

        .ppmp-table .main-header th {
            padding: 4px 4px;
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
            min-height: 0px;
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
        @if (file_exists(public_path('images/bp-sm.png')))
            <img src="{{ public_path('images/bp-sm.png') }}" alt="Bagong Pilipinas Logo" class="bagong-logo">
        @endif
    </div>

    <div class="title-block">
        <div class="document-title">
            {{ $documentTitle }}
            <span class="title-line">{{ $ppmpNo }}</span>
        </div>
        <div class="status-row">
            <span class="status-option">
                <span class="box {{ !$isFinal ? 'filled' : '' }}"></span>
                INDICATIVE
            </span>

            <span class="status-option">
                <span class="box {{ $isFinal ? 'filled' : '' }}"></span>
                FINAL
            </span>
        </div>
    </div>


    <div class="table-header-info">
        <div>Fiscal Year : {{ $ppmpYear }}</div>
        <div>End-User or Implementing Unit: {{ $unitName }}</div>
    </div>

    <table class="ppmp-table">
        <colgroup>
            <col style="width: 15%;">
            <col style="width: 10%;">
            <col style="width: 18%;">
            <col style="width: 8%;">
            <col style="width: 6%;">
            <col style="width: 7%;">
            <col style="width: 7%;">
            <col style="width: 7%;">
            <col style="width: 8%;">
            <col style="width: 8%;">
            <col style="width: 6%;">
            <col style="width: 6%;">
        </colgroup>
        <thead>
            <tr class="group-header">
                <th colspan="5">PROCUREMENT PROJECT DETAILS</th>
                <th colspan="3">PROJECTED TIMELINE (MM/YYYY)</th>
                <th colspan="2">FUNDING DETAILS</th>
                <th rowspan="2">ATTACHED SUPPORTING DOCUMENTS</th>
                <th rowspan="2">REMARKS</th>
            </tr>
            <tr class="main-header">
                <th>General Description and Objective of the Project to be Procured</th>
                <th>Type of the Project to be Procured (whether Goods, Infrastructure and Consulting Services)</th>
                <th>Quantity and Size of the Project to be Procured</th>
                <th>Recommended Mode of Procurement</th>
                <th>Pre-Procurement Conference, if applicable</th>
                <th>Start of Procurement Activity</th>
                <th>End of Procurement Activity</th>
                <th>Expected Delivery/Implementation Period</th>
                <th>Source of Funds</th>
                <th>Estimated Budget / Authorized Budgetary Allocation (PHP)</th>
            </tr>
            <tr class="column-label">
                @for ($column = 1; $column <= 12; $column++)
                    <th>Column {{ $column }}</th>
                @endfor
            </tr>
        </thead>
        <tbody>
            @if ($printItems->isNotEmpty())
                @foreach ($printItems as $item)
                    @php
                        $itemIndex = $loop->index;
                        $quantity = (float) ($item->item_quantity ?? 0);
                        $unitCost = (float) ($item->item_unit_cost ?? 0);
                        $lineTotal = (float) ($item->total_cost ?? ($quantity * $unitCost));
                        $unitName = $item->item_unit_type?->name ?? $item->item_unit_type?->name_short ?? '';
                        $itemClassificationName = $item->project_type ?: ($item->print_classification_name ?: $classificationName);
                        $itemModeOfProcurement = $item->recommended_mode_of_procurement ?: ($item->print_mode_of_procurement ?: $modeOfProcurement);
                        $itemPreProcurementConference = $item->pre_procurement_conference ?: 'No';
                        $itemSourceOfFunds = $item->print_source_of_funds ?: $sourceOfFunds;
                        $itemStartDate = $item->print_start_date ?: $startDate;
                        $itemEndDate = $item->end_of_procurement_activity;
                        $itemExpectedDeliveryDate = $item->expected_delivery_date;
                        $itemGeneralDescription = $item->print_general_description ?: ($procurement->title ?: $procurement->purpose);
                    @endphp
                    <tr>
                        @if (($generalDescriptionRowspans[$itemIndex] ?? 1) > 0)
                            <td rowspan="{{ $generalDescriptionRowspans[$itemIndex] }}">
                                <div class="item-description">{{ $itemGeneralDescription ?: '-' }}</div>
                            </td>
                        @endif
                        @if (($projectTypeRowspans[$itemIndex] ?? 1) > 0)
                            <td rowspan="{{ $projectTypeRowspans[$itemIndex] }}" class="text-center">{{ $itemClassificationName ?: '-' }}</td>
                        @endif
                        <td>
                            <div class="item-description">
                                &bull; {{ rtrim(rtrim(number_format($quantity, 2), '0'), '.') }} {{ $unitName }}
                                {{ $item->item_name ?: 'Item ' . $loop->iteration }}
                            </div>
                            @if ($item->item_description)
                                <div class="item-description">{!! $item->item_description !!}</div>
                            @endif
                        </td>
                        <td>{{ $itemModeOfProcurement ?: '-' }}</td>
                        <td class="text-center">{{ $itemPreProcurementConference }}</td>
                        <td class="text-center">{{ $itemStartDate ? date('M-d', strtotime($itemStartDate)) : '-' }}</td>
                        <td class="text-center">{{ $itemEndDate ? date('M-d', strtotime($itemEndDate)) : '-' }}</td>
                        <td class="text-center">{{ $itemExpectedDeliveryDate ? date('M-d', strtotime($itemExpectedDeliveryDate)) : '-' }}</td>
                        <td class="text-center">{{ $itemSourceOfFunds ?: '-' }}</td>
                        <td class="text-right nowrap">{{ number_format($lineTotal, 2) }}</td>
                        @if (($supportingDocumentsRowspans[$itemIndex] ?? 1) > 0)
                            <td rowspan="{{ $supportingDocumentsRowspans[$itemIndex] }}" class="text-center">{{ $item->attached_supporting_documents ?: '-' }}</td>
                        @endif
                        @if (($remarksRowspans[$itemIndex] ?? 1) > 0)
                            <td rowspan="{{ $remarksRowspans[$itemIndex] }}" class="text-center">{{ $item->remarks ?: '-' }}</td>
                        @endif
                    </tr>
                @endforeach
            @else
                <tr>
                    <td colspan="12" class="text-center">No items found.</td>
                </tr>
            @endif
            <tr class="total-row">
                <td colspan="9" class="text-right">TOTAL BUDGET</td>
                <td class="text-right nowrap">{{ number_format((float) $totalAmount, 2) }}</td>
                <td colspan="2"></td>
            </tr>
        </tbody>
    </table>

    <table class="signatory-table">
        <tr>
            <td width="50%">
                <div class="signature-label" style="margin-left:-120px; margin-bottom:20px">Prepared By</div>
                <span class="signature-line"><u>{{ $submittedName }}</u></span>
                <div class="signature-role">{{ $preparedDesignation }}</div>
                 <div style="margin-top:20px">Date:______________</div>
            </td>
            <td width="50%">
                <div class="signature-label" style="margin-left:-120px;margin-bottom:20px">Submitted By</div>
                <span class="signature-line"><u>{{ $submittedName }}</u></span>
                <div class="signature-role">AOV/Procurement Officer</div>
                <div style="margin-top:20px">Date:______________</div>
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

            $text_code = "";
            $pdf->page_text(28, $y_axis, $text_code, $font, $size, array(0,0,0));

            $text_page = "Page {PAGE_NUM} of {PAGE_COUNT}";
            $text_width = $fontMetrics->get_text_width($text_page, $font, $size);
            $pdf->page_text($width - $text_width - 28, $y_axis, $text_page, $font, $size, array(0,0,0));
        }
    </script>
</body>
</html>
