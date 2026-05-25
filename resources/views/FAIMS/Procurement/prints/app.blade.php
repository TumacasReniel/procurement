@php
    $planName = $procurement->plan_name_override ?? $procurement->reference_app?->name ?? 'PPMP';
    $planShortName = match ($planName) {
        'Annual Procurement Plan' => 'APP',
        'Supplemental Procurement Plan' => 'SPP',
        default => 'PPMP',
    };
    $preparedUser = $procurement->created_by ?? $prepared_user ?? auth()->user();
    $preparedName = strtoupper(
        $preparedUser?->profile?->fullname
            ?? $preparedUser?->profile?->full_name
            ?? $preparedUser?->name
            ?? $procurement->created_by?->profile?->fullname
            ?? $procurement->created_by?->profile?->full_name
            ?? $procurement->created_by?->name
            ?? ''
    );
    $preparedDesignation = $preparedUser?->org_chart?->designation?->name
        ?? $preparedUser?->organization?->position?->name
        ?? $preparedUser?->designation
        ?? $procurement->created_by?->org_chart?->designation?->name
        ?? $procurement->created_by?->organization?->position?->name
        ?? $procurement->created_by?->designation
        ?? ($planShortName === 'SPP' ? 'Agency' : 'End-User / Requesting Office');

    $modeOfProcurement = $procurement->codes
        ?->pluck('procurement_code.mode_of_procurement.name')
        ->filter()
        ->unique()
        ->implode(', ');

    $documentTitle = match ($planName) {
        'Annual Procurement Plan' => 'ANNUAL PROCUREMENT PLAN (APP) NO.',
        'Supplemental Procurement Plan' => 'SUPPLEMENTAL PROCUREMENT PLAN (SPP) NO.',
        default => 'PROJECT PROCUREMENT MANAGEMENT PLAN (PPMP) NO.',
    };
    $isFinal = $planName !== 'PPMP' || in_array($procurement->status?->name, ['Reviewed', 'Approved'], true);
    $submittedUser = ($procurement->status?->name === 'Approved' || $planName !== 'PPMP') && $procurement->approved_by
        ? $procurement->approved_by
        : null;
    $submittedName = strtoupper(
        $submittedUser?->profile?->fullname
            ?? $submittedUser?->profile?->full_name
            ?? $submittedUser?->name
            ?? ''
    );
    $reviewedUser = $procurement->reviewed_by;
    $reviewedName = strtoupper(
        $reviewedUser?->profile?->fullname
            ?? $reviewedUser?->profile?->full_name
            ?? $reviewedUser?->name
            ?? ''
    );
    $preparedDate = $procurement->created_at
        ? date('F d, Y', strtotime((string) $procurement->created_at))
        : date('F d, Y');
    $submittedDate = $isFinal && $procurement->updated_at
        ? date('F d, Y', strtotime((string) $procurement->updated_at))
        : '';
    $reviewedDate = $reviewedUser && $procurement->updated_at
        ? date('F d, Y', strtotime((string) $procurement->updated_at))
        : '';
    $showReviewedSignature = trim($reviewedName) !== '';
    $showSubmittedSignature = trim($submittedName) !== '';
    $signatureColumnWidth = 100 / (1 + ($showReviewedSignature ? 1 : 0) + ($showSubmittedSignature ? 1 : 0));
    $ppmpYear = $procurement->date ? date('Y', strtotime($procurement->date)) : date('Y', strtotime((string) $procurement->created_at));
    $ppmpNo = $procurement->ppmp_no_override ?: 'PPMP-' . $ppmpYear . '-' . str_pad((string) $procurement->id, 4, '0', STR_PAD_LEFT);
    $displayPpmpNo = preg_match('/-(\d{2})$/', (string) $ppmpNo, $numberMatch)
        ? $numberMatch[1]
        : $ppmpNo;
    $appVersion = (int) ($procurement->app_version_override ?? 1);
    $isUpdatedApp = $planShortName === 'APP' && $appVersion > 1;
    $prNo = $procurement->pr_no_override ?: ($procurement->code ?: '');
    $unitName = $procurement->unit_name_override ?: ($procurement->unit?->name ?? '-');
    $classificationName = $procurement->classification_override ?: ($procurement->classification?->name ?? '-');
    $sourceOfFunds = $procurement->source_of_funds_override ?: ($procurement->fund_cluster?->name ?? '-');
    $startDate = $procurement->start_date_override ?: $procurement->date;
    $printItems = $items->values();
    $cleanText = function ($value, $fallback = '-') {
        $text = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags((string) $value))));

        return $text !== '' ? $text : $fallback;
    };
    $formatPrintDate = function ($value) {
        if (!$value || strtotime((string) $value) === false) {
            return '-';
        }

        return date('m/Y', strtotime((string) $value));
    };
    $rowspansFor = function ($resolver, $mergeBlankValues = true, $matchSameEntry = false) use ($printItems, $cleanText) {
        $rowspans = [];
        $lastValue = null;
        $lastEntryId = null;
        $lastIndex = null;

        foreach ($printItems as $index => $item) {
            $value = $cleanText($resolver($item), '');
            $entryId = $matchSameEntry ? ($item->print_source_procurement_id ?? 'selected-entry') : null;

            if (
                $lastIndex !== null
                && $value === $lastValue
                && (! $matchSameEntry || $entryId === $lastEntryId)
                && ($mergeBlankValues || !in_array($value, ['', '-'], true))
            ) {
                $rowspans[$lastIndex]++;
                $rowspans[$index] = 0;
                continue;
            }

            $lastValue = $value;
            $lastEntryId = $entryId;
            $lastIndex = $index;
            $rowspans[$index] = 1;
        }

        return $rowspans;
    };
    $generalDescriptionRowspans = $rowspansFor(fn ($item) => $item->print_general_description ?: ($procurement->title ?: $procurement->purpose), true, true);
    $projectTypeRowspans = $rowspansFor(fn ($item) => $item->project_type ?: ($item->print_classification_name ?: $classificationName), true, true);
    $supportingDocumentsRowspans = $rowspansFor(fn ($item) => $item->attached_supporting_documents, false);
    $estimatedRowsPerPrintPage = 9;
    $mergeCellClass = function ($rowspans, $index, $showValue = false) use ($estimatedRowsPerPrintPage) {
        $span = $rowspans[$index] ?? 1;
        $isEstimatedPageEnd = (($index + 1) % $estimatedRowsPerPrintPage) === 0;

        if ($span > 1) {
            return 'visual-merge-start';
        }

        if ($span === 0 && $showValue) {
            return (($rowspans[$index + 1] ?? 1) === 0)
                ? 'visual-merge-repeat'
                : 'visual-merge-repeat-last';
        }

        if ($span === 0) {
            if ($isEstimatedPageEnd) {
                return 'visual-merge-page-end';
            }

            return (($rowspans[$index + 1] ?? 1) === 0)
                ? 'visual-merge-middle'
                : 'visual-merge-end';
        }

        return '';
    };
    $showMergeCellValue = function ($rowspans, $index) use ($estimatedRowsPerPrintPage) {
        $span = $rowspans[$index] ?? 1;

        if ($span > 0) {
            return true;
        }

        $groupStartIndex = $index - 1;

        while ($groupStartIndex >= 0 && ($rowspans[$groupStartIndex] ?? 1) === 0) {
            $groupStartIndex--;
        }

        $offset = $index - $groupStartIndex;

        return $groupStartIndex >= 0
            && $offset > 1
            && $index % $estimatedRowsPerPrintPage === 0;
    };
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{{ $planShortName }} {{ $displayPpmpNo }}</title>
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
            width: 100px;
            height: 100px;
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
            display: inline-flex;
            align-items: baseline;
            justify-content: center;
            min-width: 2px;
            font-size: 15px;
            border-bottom: 1px solid #000;
            line-height: 1.15;
            padding: 0 8px;
            color:red;
            vertical-align: baseline;
        }

        .status-row {
            margin-top: 4px;
            text-align: center;
        }

        .status-option {
            display: inline-block;
            margin: 0 34px;
            font-size: 15px;
            font-weight: bold;
            vertical-align: middle;
        }

        .updated-option {
            margin-right: 0;
        }

        .version-line {
            display: inline-flex;
            align-items: baseline;
            justify-content: center;
            min-width: 42px;
            border-bottom: 1px solid #000;
            line-height: 1.15;
            padding: 0 4px;
            text-align: center;
            vertical-align: baseline;
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
            font-size: 12px;
            line-height: 1.45;
        }

        .table-header-info div {
            font-weight: bold;
        }

        .ppmp-table {
            table-layout: fixed;
            border: 1.8px solid #000;
            page-break-inside: auto;
        }

        thead {
            display: table-header-group;
        }

        tbody {
            display: table-row-group;
        }

        tr {
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .ppmp-table tbody tr {
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .ppmp-table tbody td {
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .ppmp-table th,
        .ppmp-table td {
            border: 1px solid #000;
            padding: 3px 4px;
            vertical-align: top;
            word-wrap: break-word;
            overflow-wrap: break-word;
            hyphens: auto;
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

        .width-guide th {
            height: 0;
            padding: 0 !important;
            border: 0 !important;
            font-size: 0;
            line-height: 0;
        }

        .item-name {
            display: block;
            margin-bottom: 2px;
            font-weight: bold;
        }

        .item-description {
            line-height: 1.25;
            white-space: normal;
        }

        .compact-cell {
            font-size: 7.2px;
            line-height: 1.16;
        }

        .ppmp-col-3 { width: 33% !important; max-width: 33% !important; }
        .ppmp-col-4,
        .ppmp-col-5,
        .ppmp-col-6,
        .ppmp-col-7,
        .ppmp-col-8,
        .ppmp-col-9,
        .ppmp-col-10 {
            width: 5% !important;
            max-width: 5% !important;
            word-break: break-all !important;
            overflow-wrap: anywhere !important;
            hyphens: manual !important;
        }

        .amount-cell {
            font-size: 7.4px;
        }

        .visual-merge-start {
            border-bottom-color: transparent !important;
        }

        .visual-merge-middle {
            border-top-color: transparent !important;
            border-bottom-color: transparent !important;
        }

        .visual-merge-end {
            border-top-color: transparent !important;
        }

        .visual-merge-page-end {
            border-top-color: transparent !important;
            border-bottom: 1px solid #000 !important;
        }

        .visual-merge-repeat {
            border-top: 1px solid #000 !important;
            border-bottom-color: transparent !important;
        }

        .visual-merge-repeat-last {
            border-top: 1px solid #000 !important;
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
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .signatory-table {
            margin-top: 16px;
            font-size: 12px;
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
            font-size: 12px;
            font-weight: bold;
        }

        .signature-role {
            margin-top: 2px;
            font-size: 12px;
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
            <span class="title-line">{{ $displayPpmpNo }}</span>
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

            @if ($planShortName === 'APP')
                <span class="status-option updated-option">
                    <span class="box {{ $isUpdatedApp ? 'filled' : '' }}"></span>
                    UPDATED [version No. <span class="version-line">{{ $isUpdatedApp ? $appVersion : '' }}</span>]
                </span>
            @endif
        </div>
    </div>


    <div class="table-header-info">
        <div>Fiscal Year : {{ $ppmpYear }}</div>
        <div>End-User or Implementing Unit: {{ $unitName }}</div>
    </div>

    <table class="ppmp-table">
        <colgroup>
            <col style="width: 6%;">
            <col style="width: 6%;">
            <col style="width: 33%;">
            <col style="width: 5%;">
            <col style="width: 5%;">
            <col style="width: 5%;">
            <col style="width: 5%;">
            <col style="width: 5%;">
            <col style="width: 5%;">
            <col style="width: 5%;">
            <col style="width: 10%;">
            <col style="width: 10%;">
        </colgroup>
        <thead>
            <tr class="width-guide">
                <th style="width: 6%;"></th>
                <th style="width: 6%;"></th>
                <th style="width: 33%;"></th>
                <th style="width: 5%;"></th>
                <th style="width: 5%;"></th>
                <th style="width: 5%;"></th>
                <th style="width: 5%;"></th>
                <th style="width: 5%;"></th>
                <th style="width: 5%;"></th>
                <th style="width: 5%;"></th>
                <th style="width: 10%;"></th>
                <th style="width: 10%;"></th>
            </tr>
            <tr class="group-header">
                <th colspan="5">PROCUREMENT PROJECT DETAILS</th>
                <th colspan="3">PROJECTED TIMELINE (MM/YYYY)</th>
                <th colspan="2">FUNDING DETAILS</th>
                <th rowspan="2">ATTACHED SUPPORTING DOCUMENTS</th>
                <th rowspan="2">REMARKS</th>
            </tr>
            <tr class="main-header">
                <th style="width: 6%;">General Description and Objective of the Project to be Procured</th>
                <th style="width: 6%;">Type of the Project to be Procured (whether Goods, Infrastructure and Consulting Services)</th>
                <th class="ppmp-col-3" style="width: 33%;">Quantity and Size of the Project to be Procured</th>
                <th class="ppmp-col-4" style="width: 5%;">Recommended Mode of Procurement</th>
                <th class="ppmp-col-5" style="width: 5%;">Pre-Procurement Conference, if applicable</th>
                <th class="ppmp-col-6" style="width: 5%;">Start of Procurement Activity</th>
                <th class="ppmp-col-7" style="width: 5%;">End of Procurement Activity</th>
                <th class="ppmp-col-8" style="width: 5%;">Expected Delivery/Implementation Period</th>
                <th class="ppmp-col-9" style="width: 5%;">Source of Funds</th>
                <th class="ppmp-col-10" style="width: 5%;">Estimated Budget / Authorized Budgetary Allocation (PHP)</th>
            </tr>
            <tr class="column-label">
                @for ($column = 1; $column <= 12; $column++)
                    <th @class([
                        'ppmp-col-3' => $column === 3,
                        'ppmp-col-4' => $column === 4,
                        'ppmp-col-5' => $column === 5,
                        'ppmp-col-6' => $column === 6,
                        'ppmp-col-7' => $column === 7,
                        'ppmp-col-8' => $column === 8,
                        'ppmp-col-9' => $column === 9,
                        'ppmp-col-10' => $column === 10,
                    ])>Column {{ $column }}</th>
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
                        $itemDescription = $cleanText($item->item_description, '');
                        $showGeneralDescription = $showMergeCellValue($generalDescriptionRowspans, $itemIndex);
                        $showProjectType = $showMergeCellValue($projectTypeRowspans, $itemIndex);
                        $showSupportingDocuments = $showMergeCellValue($supportingDocumentsRowspans, $itemIndex);
                    @endphp
                    <tr>
                        <td class="compact-cell {{ $mergeCellClass($generalDescriptionRowspans, $itemIndex, $showGeneralDescription) }}">
                            @if ($showGeneralDescription)
                                <div class="item-description">{{ $cleanText($itemGeneralDescription) }}</div>
                            @else
                                &nbsp;
                            @endif
                        </td>
                        <td class="text-center compact-cell {{ $mergeCellClass($projectTypeRowspans, $itemIndex, $showProjectType) }}">
                            @if ($showProjectType)
                                {{ $cleanText($itemClassificationName) }}
                            @else
                                &nbsp;
                            @endif
                        </td>
                        <td class="ppmp-col-3" style="width: 33%;">
                            <div class="item-description">
                                &bull; {{ rtrim(rtrim(number_format($quantity, 2), '0'), '.') }} {{ $unitName }}
                                {{ $cleanText($item->item_name, 'Item ' . $loop->iteration) }}
                            </div>
                            @if ($itemDescription)
                                <div class="item-description">{{ $itemDescription }}</div>
                            @endif
                        </td>
                        <td class="compact-cell ppmp-col-4" style="width: 5%;">{{ $cleanText($itemModeOfProcurement) }}</td>
                        <td class="text-center compact-cell ppmp-col-5" style="width: 5%;">{{ $cleanText($itemPreProcurementConference) }}</td>
                        <td class="text-center compact-cell ppmp-col-6" style="width: 5%;">{{ $formatPrintDate($itemStartDate) }}</td>
                        <td class="text-center compact-cell ppmp-col-7" style="width: 5%;">{{ $formatPrintDate($itemEndDate) }}</td>
                        <td class="text-center compact-cell ppmp-col-8" style="width: 5%;">{{ $formatPrintDate($itemExpectedDeliveryDate) }}</td>
                        <td class="text-center compact-cell ppmp-col-9" style="width: 5%;">{{ $cleanText($itemSourceOfFunds) }}</td>
                        <td class="text-right amount-cell ppmp-col-10" style="width: 5%;">{{ number_format($lineTotal, 2) }}</td>
                        <td class="text-center compact-cell {{ $mergeCellClass($supportingDocumentsRowspans, $itemIndex, $showSupportingDocuments) }}">
                            @if ($showSupportingDocuments)
                                {{ $cleanText($item->attached_supporting_documents) }}
                            @else
                                &nbsp;
                            @endif
                        </td>
                        <td class="text-center compact-cell">{{ $cleanText($item->remarks) }}</td>
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
            <td width="{{ $signatureColumnWidth }}%">
                <div class="signature-label" style="margin-left:-120px; margin-bottom:20px">Prepared By</div>
                <span class="signature-line"><u>{{ $preparedName }}</u></span>
                <div class="signature-role">{{ $preparedDesignation }}</div>
                 <div style="margin-top:20px">Date: {{ $preparedDate }}</div>
            </td>
            @if ($showReviewedSignature)
                <td width="{{ $signatureColumnWidth }}%">
                    <div class="signature-label" style="margin-left:-120px;margin-bottom:20px">Reviewed By</div>
                    <span class="signature-line"><u>{{ $reviewedName }}</u></span>
                    <div class="signature-role">Budget Officer</div>
                    <div style="margin-top:20px">Date: {{ $reviewedDate ?: '______________' }}</div>
                </td>
            @endif
            @if ($showSubmittedSignature)
                <td width="{{ $signatureColumnWidth }}%">
                    <div class="signature-label" style="margin-left:-120px;margin-bottom:20px">Submitted By</div>
                    <span class="signature-line"><u>{{ $submittedName }}</u></span>
                    <div class="signature-role">AOV/Procurement Officer</div>
                    <div style="margin-top:20px">Date: {{ $submittedDate ?: '______________' }}</div>
                </td>
            @endif
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
