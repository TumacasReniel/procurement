@php
    $totalAmount = 0;
    foreach ($items as $item) {
        $totalAmount += ($item->item_quantity * $item->item_unit_cost);
    }

    $latestComment = $procurement->comments
        ? $procurement->comments->sortByDesc('created_at')->first()
        : null;
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        @page { 
              margin: 6px 12px 18px 12px;
        }
        body { font-family: Arial, sans-serif; font-size: 12px; line-height: 1; margin: 0; color: #000; }

        .content {
            margin-bottom: 4px;
        }

        .page-header {
            position: relative;
            margin-bottom: 4px;
            min-height: 38px;
        }

        .page-header img {
            position: absolute;
            top: 0;
            left: 0;
            width: 28px;
            height: 28px;
        }

        .page-header-text {
            text-align: center;
            line-height: 1;
            padding-top: 2px;
        }

        .page-header-text .line-1 {
            font-size: 6px;
            text-transform: uppercase;
        }

        .page-header-text .line-2 {
            font-size: 7px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .page-header-text .line-3 {
            font-size: 6px;
        }

        .document-code-box {
            position: absolute;
            top: 0;
            right: 0;
            border: 1px solid black;
            padding: 1px 5px;
            font-size: 7px;
            line-height: 1;
            text-align: left;
            min-width: 62px;
        }

        .page-title {
            text-align: center;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 10px;
            margin: 4px 0 4px;
        }

        .details-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin-bottom: 0;
        }

        .details-table td {
            border: 1px solid black;
            padding: 2px 4px;
            vertical-align: top;
        }

        .main-table {
            width: 100%;
            border-collapse: collapse;
            border: 1.5px solid black; 
            table-layout: fixed;
            border-top: 0;
        }

        thead { display: table-header-group; }

        .main-table th { 
            border: 1px solid black; 
            padding: 2px; 
            background: #ffffff; 
            text-align: center;
            font-weight: bold;
        }

        .main-table td {
            border: 1px solid black;
            padding: 2px 4px;
            vertical-align: top;
            word-wrap: break-word;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .bold { font-weight: bold; }
        .description-cell {
            font-size: 7px;
            line-height: 1;
            white-space: pre-line;
        }
        .row-fragment-start td {
            border-bottom: 1px solid black;
        }
        .row-fragment-middle td {
            border-top: 0;
            border-bottom: 1px solid black;
        }
        .row-fragment-end td {
            border-top: 0;
            border-bottom: 1px solid black;
        }

        .footer-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            border-top: 1px solid black;
        }

        .footer-table td {
            padding: 2px 4px;
            vertical-align: top;
        }

        .purpose-cell {
            min-height: 34px;
            height: 34px;
            border-bottom: 1px solid black;
        }

        .signature-line {
            display: block;
            width: 92%;
            margin: 0;
            border-bottom: 1px solid black;
            height: 10px;
        }

        .signatory-name {
            font-weight: bold;
            text-transform: uppercase;
            text-decoration: underline;
        }

        .footer-label-cell {
            width: 18%;
            white-space: nowrap;
            padding-left: 2px !important;
            padding-right: 2px !important;
        }

        .footer-party-heading {
            text-align: center;
            font-weight: normal;
        }

        .footer-party-cell {
            width: 41%;
            text-align: left;
        }

        .footer-name-cell {
            vertical-align: bottom;
        }

        .footer-designation-cell {
            vertical-align: top;
        }

        .footer-heading-row td {
            border-bottom: 0;
            padding-top: 4px;
            padding-bottom: 2px;
        }

        .footer-signature-row td {
            border-bottom: 0;
            padding-top: 0;
            padding-bottom: 2px;
        }

        .footer-name-row td {
            border-bottom: 0;
            padding-top: 0;
            padding-bottom: 1px;
        }

        .footer-designation-row td {
            padding-top: 0;
        }

        .floating-comment {
            position: fixed;
            right: 12px;
            bottom: 28px;
            z-index: 999;
        }

        .floating-comment-icon {
            width: 26px;
            height: 26px;
            margin-left: auto;
            border: 1px solid #000;
            border-radius: 50%;
            background: #fff;
            text-align: center;
            line-height: 24px;
        }

        .floating-comment-icon svg {
            width: 12px;
            height: 12px;
            vertical-align: middle;
        }
    </style>
</head>
<body>
    @if ($latestComment && filled($latestComment->content))
        <div class="floating-comment" title="{{ $latestComment->content }}">
            <div class="floating-comment-icon">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M6 7.5H18M6 11.5H15M8 16.5H6V5.5H18V16.5H12.5L8 20V16.5Z" stroke="#000" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
        </div>
    @endif

    <div class="content">
        <div class="page-header">
            <img src="{{ public_path('images/logo-sm.png') }}" alt="tag">
            <div class="page-header-text">
                <div class="line-1">Republic of the Philippines</div>
                <div class="line-2">Department of Science and Technology</div>
                <div class="line-3">Regional Office No. IX</div>
            </div>
            <div class="document-code-box">
                <div><strong>FASS-PUR F08</strong></div>
                <div>Rev. 1/07-01-23</div>
            </div>
        </div>
        <div class="page-title">Purchase Request</div>
    </div>

    <table class="details-table">
        <tr>
            <td colspan="4">Entity Name: <u>Department of Science and Technology - IX</u></td>
            <td colspan="2">Fund Cluster: <u>{{ $procurement->fund_cluster?->name ?? 'Regular Fund' }}</u></td>
        </tr>
        <tr>
            <td colspan="2">Office/Section: <u>{{ $procurement->division->name }}</u></td>
            <td colspan="2">PR No. <strong>{{ $procurement->code }}</strong></td>
            <td colspan="2" rowspan="2">Date : <strong>{{ date('m-d-Y', strtotime($procurement->date)) }}</strong></td>
        </tr>
        <tr>
            <td colspan="4">Responsibility Center Code :<br>{{ $procurement->unit->responsibility_center_code }}</td>
        </tr>
    </table>

    <table class="main-table">
        <thead>
            <tr>
                <th width="8%">Stock No.</th>
                <th width="8%">Unit</th>
                <th width="48%">Item Description</th>
                <th width="10%">Quantity</th>
                <th width="13%">Unit Cost</th>
                <th width="13%">Total Cost</th>
            </tr>
        </thead>
        <tbody>
            @php
                $splitDescriptionChunks = function ($html) use ($items) {
                    $text = (string) $html;
                    $text = preg_replace('/<br\s*\/?>/i', "\n", $text);
                    $text = preg_replace('/<\/p>/i', "\n", $text);
                    $text = preg_replace('/<\/div>/i', "\n", $text);
                    $text = preg_replace('/<\/li>/i', "\n", $text);
                    $text = preg_replace('/<li[^>]*>/i', '- ', $text);
                    $text = strip_tags($text);
                    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    $text = preg_replace("/\r\n|\r/", "\n", $text);

                    $lines = collect(explode("\n", $text))
                        ->map(fn ($line) => trim(preg_replace('/\s+/', ' ', $line)))
                        ->filter(fn ($line) => $line !== '')
                        ->values();

                    if ($lines->isEmpty()) {
                        return collect(['']);
                    }

                    if ($items->count() === 1 && $lines->count() <= 110) {
                        return collect([$lines->implode("\n")]);
                    }

                    return $lines->chunk(110)->map(fn ($chunk) => $chunk->implode("\n"))->values();
                };
            @endphp

            @foreach ($items as $item)
                @php
                    $descriptionChunks = $splitDescriptionChunks($item->item_description);
                @endphp

                @foreach ($descriptionChunks as $chunkIndex => $descriptionChunk)
                    @php
                        $rowClass = '';

                        if ($descriptionChunks->count() > 1) {
                            if ($chunkIndex === 0) {
                                $rowClass = 'row-fragment-start';
                            } elseif ($chunkIndex === $descriptionChunks->count() - 1) {
                                $rowClass = 'row-fragment-end';
                            } else {
                                $rowClass = 'row-fragment-middle';
                            }
                        }
                    @endphp
                    <tr class="{{ $rowClass }}">
                        @if ($chunkIndex === 0)
                            <td class="text-center">{{ $item->item_no }}</td>
                            <td class="text-center">{{ $item->item_unit_type->name_short }}</td>
                            <td class="description-cell">{{ $descriptionChunk }}</td>
                            <td class="text-center">{{ $item->item_quantity }}</td>
                            <td class="text-center">{{ number_format($item->item_unit_cost, 2) }}</td>
                            <td class="text-center bold">{{ number_format($item->item_quantity * $item->item_unit_cost, 2) }}</td>
                        @else
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <td class="description-cell">{{ $descriptionChunk }}</td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                        @endif
                    </tr>
                @endforeach
            @endforeach

            <tr class="total-row">
                <td colspan="5" class="bold">TOTAL</td>
                <td class="text-right bold">{{ number_format($totalAmount, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <table class="footer-table">
        <tr>
            <td colspan="3" class="purpose-cell">
                [{{ $procurement->purpose }}]
            </td>
        </tr>
        <tr class="text-center footer-heading-row">
            <td class="footer-label-cell">&nbsp;</td>
            <td class="footer-party-heading">Requested By:</td>
            <td class="footer-party-heading">Approved By:</td>
        </tr>
        <tr class="footer-signature-row">
            <td class="footer-label-cell">Signature:</td>
            <td class="footer-party-cell"><span class="signature-line"></span></td>
            <td class="footer-party-cell"><span class="signature-line"></span></td>
        </tr>
        <tr class="footer-name-row">
            <td class="footer-label-cell">Printed Name:</td>
            <td class="footer-party-cell footer-name-cell">
                <span class="signatory-name">{{ strtoupper($procurement->requested_by->profile->fullname) }}</span>
            </td>
            <td class="footer-party-cell footer-name-cell">
                <span class="signatory-name">{{ strtoupper($procurement->approved_by->profile->fullname) }}</span>
            </td>
        </tr>
        <tr class="footer-designation-row">
            <td class="footer-label-cell">Designation:</td>
            <td class="footer-party-cell footer-designation-cell">{{ $procurement->requested_by->designation ?? 'Division Head' }}</td>
            <td class="footer-party-cell footer-designation-cell">{{ $procurement->approved_by->designation ?? 'Regional Director' }}</td>
        </tr>
    </table>

    <script type="text/php">
        if ( isset($pdf) ) {
            $font = $fontMetrics->get_font("Arial, Helvetica, sans-serif", "normal");
            $size = 12;
            $width = $pdf->get_width();
            $height = $pdf->get_height();
            $left_margin = 18;
            $right_margin = 18;
            $bottom_margin = 12;
            $y_axis = $height - $bottom_margin; 

            // LEFT: Procurement Code
            $text_code = "{{ $procurement->code }}";
            $pdf->page_text($left_margin, $y_axis, $text_code, $font, $size, array(0,0,0));

            // RIGHT: Page Counter
            $text_page = "Page {PAGE_NUM} of {PAGE_COUNT}";
            $text_width = $fontMetrics->get_text_width("Page 1 of 1", $font, $size);
            $pdf->page_text($width - $text_width - $right_margin, $y_axis, $text_page, $font, $size, array(0,0,0));
        }
    </script>
</body>
</html>
