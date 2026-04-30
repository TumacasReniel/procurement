<!DOCTYPE html>
<html>
<head>
    <title>Notice of Award</title>
    <style>
        @page { 
            margin: 8px 24px 26px 24px;
        }
        body { font-family: Arial, sans-serif; font-size: 12px; line-height: 1; margin: 0; }

        .content {
            margin-bottom: 10px;
        }
        
        .main-table {
            width: 100%;
            border-collapse: collapse;
            border: 1.5px solid black; 
            table-layout: fixed;
        }

        /* Forces header to repeat on every page */
        thead { display: table-header-group; }
        
        /* Forces bottom border on every page break */
        tfoot { display: table-footer-group; }

        .main-table th, .main-table td { 
            border: 1px solid black; 
            padding: 3px; 
            vertical-align: top;
            word-wrap: break-word;
        }

        .main-table th { 
            background: #ffffff; 
            text-align: center;
            font-weight: bold;
        }

        .supplier-header {
            text-align: center;
            line-height: 1.15;
        }

        .supplier-award-check {
            display: inline-block;
            width: 13px;
            height: 13px;
            margin-right: 4px;
            border: 1px solid #000;
            text-align: center;
            font-size: 11px;
            line-height: 12px;
            font-weight: bold;
            vertical-align: middle;
        }

        .supplier-award-label {
            display: inline-block;
            vertical-align: middle;
        }

        .sig-table {
            width: 100%;
            border-collapse: collapse;
            border: 1.5px solid black;
            background-color: white;
            margin-top: 20px;
        }

        .aob-document {
            position: relative;
            min-height: 525px;
            padding-bottom: 96px;
            box-sizing: border-box;
        }
        
        /* Prevents signatures from splitting across pages */
        .footer-assig {
            page-break-inside: avoid;
            line-height: 1; 
            font-size: 10px;
            position: absolute;
            left: 0;
            right: 0;
            bottom: 36px;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .bold { font-weight: bold; }
        .description-cell {
            font-size: 12px;
            line-height: 1.15;
        }

        .description-continuation {
            text-align: left;
            white-space: pre-line;
        }

        .continuation-row td {
            border-top: 0;
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

        .header-shell {
            margin-bottom: 8px;
        }

        .document-code-wrap {
            text-align: right;
            margin-bottom: -34px;
        }

        .document-code-box {
            display: inline-block;
            border: 1px solid #000;
            padding: 5px 10px 4px;
            text-align: center;
            font-size: 10px;
            line-height: 1.2;
            min-width: 110px;
        }

        .document-code-title {
            font-weight: bold;
            font-style: italic;
        }

        .document-code-subtitle {
            font-style: italic;
        }

        .agency-header {
            text-align: center;
            color: #555;
            line-height: 1.15;
        }

        .agency-header .country {
            font-size: 13px;
        }

        .agency-header .department {
            margin: 3px 0;
            font-size: 18px;
            font-weight: bold;
            letter-spacing: 0.4px;
        }

        .agency-header .office {
            font-size: 14px;
        }

        .report-title {
            margin: 22px 0 12px;
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            color: #666;
            letter-spacing: 0.6px;
        }

        .header-meta {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2px;
            color: #666;
            font-size: 11px;
        }

        .header-meta td {
            padding: 1px 0;
            vertical-align: top;
            line-height: 1;
        }

        .header-meta-left {
            width: 33%;
            padding-right: 16px;
        }

        .header-meta-spacer {
            width: 20%;
        }

        .header-meta-right {
            width: 47%;
            padding-left: 16px;
            text-align: right;
        }

        .meta-label {
            display: inline-block;
            min-width: 102px;
        }

        .meta-label-compact {
            min-width: 0;
        }

        .meta-divider {
            display: inline-block;
            width: 10px;
            text-align: center;
        }

        .meta-divider-compact {
            width: auto;
            margin: 0 6px 0 0;
        }

        .meta-value {
            display: inline-block;
        }

        .meta-line-value {
            display: inline-block;
            min-width: 170px;
            border-bottom: 1px solid #888;
            padding: 0 6px 2px;
            line-height: 1.1;
            text-align: left;
        }

    </style>
</head>
<body>
    <div class="aob-document">

    @php
        $quotationChunks = collect($quotations)->chunk(5);
        $items = count($quotations) > 0
            ? $quotations[0]->items
                ->sortBy(fn ($quotationItem) => (int) ($quotationItem->item->item_no ?? 0))
                ->values()
            : collect();
        $projectName = $procurement->title ?: $procurement->purpose ?: '________________________';
        $projectLocation = $procurement->unit?->name ?: $procurement->division?->name ?: '________________________';

        $normalizeDescriptionLines = function ($html) {
            $text = (string) $html;
            $text = preg_replace('/<br\s*\/?>/i', "\n", $text);
            $text = preg_replace('/<\/p>/i', "\n", $text);
            $text = preg_replace('/<\/div>/i', "\n", $text);
            $text = preg_replace('/<\/li>/i', "\n", $text);
            $text = preg_replace('/<li[^>]*>/i', '- ', $text);
            $text = strip_tags($text);
            $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $text = preg_replace("/\r\n|\r/", "\n", $text);

            return collect(explode("\n", $text))
                ->map(fn ($line) => trim(preg_replace('/\s+/', ' ', $line)))
                ->filter(fn ($line) => $line !== '')
                ->values();
        };

        $splitDescriptionChunks = function ($html, $preferSingleRow = false) use ($normalizeDescriptionLines) {
            $lines = $normalizeDescriptionLines($html);

            if ($lines->isEmpty()) {
                return collect(['']);
            }

            if ($preferSingleRow && $lines->count() <= 55) {
                return collect([$lines->implode("\n")]);
            }

            return $lines->chunk(55)->map(fn ($chunk) => $chunk->implode("\n"))->values();
        };

        $resolveDescriptionFontSize = function ($itemName, $html, $chunkCount = 1) use ($normalizeDescriptionLines) {
            $descriptionText = $normalizeDescriptionLines($html)->implode(' ');
            $fullText = trim(collect([$itemName, $descriptionText])->filter()->implode(' '));
            $characterCount = mb_strlen($fullText);

            if ($chunkCount > 1 || $characterCount > 1200) {
                return 7;
            }

            if ($characterCount > 900) {
                return 8;
            }

            if ($characterCount > 650) {
                return 9;
            }

            if ($characterCount > 420) {
                return 10;
            }

            if ($characterCount > 220) {
                return 11;
            }

            return 12;
        };

        $resolveDescriptionLineHeight = function ($fontSize) {
            if ($fontSize <= 8) {
                return 1.0;
            }

            if ($fontSize <= 10) {
                return 1.08;
            }

            return 1.15;
        };
    @endphp

    @foreach ($quotationChunks as $chunkIndex => $quotationChunk)
        <div class="header-shell">
            <div class="document-code-wrap">
                <div class="document-code-box">
                    <div class="document-code-title">FASS-PUR F07</div>
                    <div class="document-code-subtitle">Rev. 0/08-16-07</div>
                </div>
            </div>

            <div class="agency-header">
                <div class="country">Republic of The Philippines</div>
                <div class="department">DEPARTMENT OF SCIENCE AND TECHNOLOGY</div>
                <div class="office">Regional Office No. IX</div>
            </div>

            <div class="report-title">ABSTRACT OF BIDS</div>
        </div>

        <table class="header-meta">
            <tbody>
                <tr>
                    <td class="header-meta-left">
                        <span class="meta-label meta-label-compact">Standard Form Number</span>
                        <span class="meta-divider meta-divider-compact">:</span>
                        <span class="meta-value">SF-GOOD-40</span>
                    </td>
                    <td class="header-meta-spacer"></td>
                    <td class="header-meta-right">
                        <span class="meta-label">Project Reference No.</span>
                        <span class="meta-divider">:</span>
                        <span class="meta-line-value"></span>
                    </td>
                </tr>
                <tr>
                    <td class="header-meta-left">
                        <span class="meta-label meta-label-compact">Revised</span>
                        <span class="meta-divider meta-divider-compact">:</span>
                        <span class="meta-value">May 24, 2004</span>
                    </td>
                    <td class="header-meta-spacer"></td>
                    <td class="header-meta-right">
                        <span class="meta-label">Name of the Project</span>
                        <span class="meta-divider">:</span>
                        <span class="meta-line-value"></span>
                    </td>
                </tr>
                <tr>
                    <td class="header-meta-left">&nbsp;</td>
                    <td class="header-meta-spacer"></td>
                    <td class="header-meta-right">
                        <span class="meta-label">Location of the Project</span>
                        <span class="meta-divider">:</span>
                        <span class="meta-line-value"></span>
                    </td>
                </tr>
            </tbody>
        </table>

        <table class="main-table">
            <thead>
                <tr>
                    <th style="width:4%">Item No.</th>
                    <th style="width:10%">Quantity/Unit</th>
                    <th style="width:42%">Description</th>
                    @foreach ($quotationChunk as $quotation)
                        @php
                            $isSupplierAwarded = $quotation->items->contains(function ($quotationItem) use ($awardedStatusId) {
                                return (int) $quotationItem->status_id === (int) $awardedStatusId;
                            });
                        @endphp
                        <th style="width:auto">
                            <div class="supplier-header">
                                @if ($isSupplierAwarded)
                                    <span class="supplier-award-check">&#10003;</span>
                                @endif
                                <span class="supplier-award-label">{{ $quotation->supplier->name }}</span>
                            </div>
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($items as $index => $item)
                    @php
                        $currentProcurementItemId = $item->procurement_item_id ?? $item->item?->id;
                        $descriptionChunks = $splitDescriptionChunks(
                            $item->item->item_description,
                            $items->count() === 1
                        );
                        $descriptionFontSize = $resolveDescriptionFontSize(
                            $item->item->item_name,
                            $item->item->item_description,
                            $descriptionChunks->count()
                        );
                        $descriptionLineHeight = $resolveDescriptionLineHeight($descriptionFontSize);
                        $descriptionCellStyle = "font-size: {$descriptionFontSize}px; line-height: {$descriptionLineHeight};";
                    @endphp

                    @foreach ($descriptionChunks as $chunkIndex => $descriptionChunk)
                        @php
                            $rowClass = '';
                            $descriptionText = ltrim((string) $descriptionChunk);

                            if ($descriptionChunks->count() > 1) {
                                if ($chunkIndex === 0) {
                                    $rowClass = 'row-fragment-start';
                                } elseif ($chunkIndex === $descriptionChunks->count() - 1) {
                                    $rowClass = 'row-fragment-end';
                                } else {
                                    $rowClass = 'row-fragment-middle';
                                }
                            } elseif ($chunkIndex > 0) {
                                $rowClass = 'continuation-row';
                            }

                            if ($chunkIndex === 0) {
                                $itemName = trim((string) ($item->item->item_name ?? ''));
                                $descriptionText = $itemName;

                                if (ltrim((string) $descriptionChunk) !== '') {
                                    $descriptionText = $descriptionText === ''
                                        ? ltrim((string) $descriptionChunk)
                                        : $descriptionText."\n".ltrim((string) $descriptionChunk);
                                }
                            }
                        @endphp
                        <tr class="{{ $rowClass }}">
                            @if ($chunkIndex === 0)
                                <td class="text-center">{{ $index + 1 }}</td>
                                <td class="text-center">
                                    {{ $item->item->item_quantity }}
                                    {{ $item->item->item_quantity > 1 ? $item->item->item_unit_type->name_long : $item->item->item_unit_type->name_short }}
                                </td>

                                <td class="description-cell description-continuation" style="{{ $descriptionCellStyle }}">{{ $descriptionText }}</td>

                                @foreach ($quotationChunk as $quotation)
                                    <td class="text-center">
                                        @php
                                            $quotationItem = $quotation->items
                                                ->firstWhere('procurement_item_id', $currentProcurementItemId);
                                            $price = $quotationItem?->bid_price;
                                            $isFree = (bool) ($quotationItem?->is_free);
                                            $isNoOffer = (bool) ($quotationItem?->is_no_offer);
                                            $isNotApplicable = (bool) ($quotationItem?->is_not_applicable);
                                        @endphp

                                        @if ($isFree)
                                            free
                                        @elseif ($isNoOffer)
                                            No Offer
                                        @elseif ($isNotApplicable)
                                            Not Applicable
                                        @elseif (is_null($price) || (float) $price <= 0)
                                            No Bid
                                        @else
                                            {{ number_format($price, 2) }}
                                        @endif
                                    </td>
                                @endforeach
                            @else
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                                <td class="description-cell description-continuation" style="{{ $descriptionCellStyle }}">{{ $descriptionText }}</td>
                                @foreach ($quotationChunk as $quotation)
                                    <td>&nbsp;</td>
                                @endforeach
                            @endif
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>

        @if (! $loop->last)
            <div style="page-break-after: always;"></div>
        @endif
    @endforeach


    <div class="footer-assig">
        <table style="width:100%; margin: 15px 0 10px; border-collapse: collapse; border: none; table-layout: fixed;">
            <tr>
                <td style="border: none; text-align: left;">Awarding Committee:</td>
                <td style="border: none;"></td>
                @foreach ($bac_members as $member)
                    <td style="border: none;"></td>
                @endforeach
                <td style="border: none; text-align: start;">Approved by:</td>
            </tr>
        </table>
        <table style="width:100%; text-align:center; margin-bottom:30px; border-collapse: collapse; border: none; table-layout: fixed;">
            <tr>
                <th style="border: none;"><u>{{ data_get($bac_chairperson, 'name', '') }}</u></th>
                <th style="border: none;"><u>{{ data_get($bac_vice_chairperson, 'name', '') }}</u></th>
                @foreach ($bac_members as $member)
                    <th style="border: none;"><u>{{ strtoupper(data_get($member, 'name', '')) }}</u></th>
                @endforeach
                <th style="border: none;"><u>{{ data_get($regional_director, 'name', '') }}</u></th>
            </tr>
            <tr>
                <td style="border: none;">Chairperson, BAC</td>
                <td style="border: none;">Vice Chairperson, BAC</td>
                @foreach ($bac_members as $member)
                    <td style="border: none;">Member, BAC</td>
                @endforeach
                <td style="border: none;">Regional Director</td>
            </tr>
        </table>
    </div>
    </div>

    <script type="text/php">
        if ( isset($pdf) ) {
            $font = $fontMetrics->get_font("Arial, Helvetica, sans-serif", "normal");
            $size = 12;
            $width = $pdf->get_width();
            $height = $pdf->get_height();
            $left_margin = 24;
            $right_margin = 24;
            $bottom_margin = 20;
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
