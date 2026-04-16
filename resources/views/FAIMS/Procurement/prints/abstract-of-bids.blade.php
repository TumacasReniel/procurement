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

        .meta-table {
            width: 100%;
            border-collapse: collapse;
            border: 1.5px solid black;
            table-layout: fixed;
            margin-bottom: 5px;
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

        .meta-table td {
            border: 1px solid black;
            padding: 3px;
            word-wrap: break-word;
        }

        .main-table th { 
            background: #ffffff; 
            text-align: center;
            font-weight: bold;
        }

        .sig-table {
            width: 100%;
            border-collapse: collapse;
            border: 1.5px solid black;
            background-color: white;
            margin-top: 20px;
        }
        
        /* Prevents signatures from splitting across pages */
        .footer-assig {
            page-break-inside: avoid;
            line-height: 1; 
            font-size: 10px;
            margin-top: 18px;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .bold { font-weight: bold; }
        .description-cell {
            font-size: 7px;
            line-height: 1;
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

    </style>
</head>
<body>

    @php
        $quotationChunks = collect($quotations)->chunk(5);
        $items = count($quotations) > 0 ? $quotations[0]->items : collect();

        $splitDescriptionChunks = function ($html, $preferSingleRow = false) {
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

            if ($preferSingleRow && $lines->count() <= 55) {
                return collect([$lines->implode("\n")]);
            }

            return $lines->chunk(55)->map(fn ($chunk) => $chunk->implode("\n"))->values();
        };
    @endphp

    @foreach ($quotationChunks as $chunkIndex => $quotationChunk)
        <div class="text-center">
            <img src="{{ public_path('/images/logo-sm.png') }}" alt="Logo Left" style="float:left; height:42px; width: 42px" >
            <div style="line-height: 1">
                <p>Republic of the Philippines</p>
                <h3>Department of Science and Technology</h3>
                <p>Regional Office No. IX</p>
            </div>
        </div>

        <center style="margin-right:-20px"> <h2 style="margin: 4px 0 6px;">ABSTRACT OF BIDS</h2></center>

        <table class="meta-table">
            <tbody>
                <tr>
                    <td style="text-align: left;" colspan="2">Standard Form Number:</td>
                    <td style="text-align: left;" colspan="{{ $quotationChunk->count() + 1 }}">Project Reference No.:</td>
                </tr>
                <tr>
                    <td style="text-align: left;" colspan="2">Revised Date:</td>
                    <td style="text-align: left;" colspan="2">Name of the Project:</td>
                    <td style="text-align: left;" colspan="{{ max($quotationChunk->count() - 1, 1) }}">Location of the Project:</td>
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
                        <th style="width:auto">{{ $quotation->supplier->name }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($items as $index => $item)
                    @php
                        $descriptionChunks = $splitDescriptionChunks(
                            $item->item->item_description,
                            $items->count() === 1
                        );
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
                            } elseif ($chunkIndex > 0) {
                                $rowClass = 'continuation-row';
                            }
                        @endphp
                        <tr class="{{ $rowClass }}">
                            @if ($chunkIndex === 0)
                                <td class="text-center">{{ $index + 1 }}</td>
                                <td class="text-center">
                                    {{ $item->item->item_quantity }}
                                    {{ $item->item->item_quantity > 1 ? $item->item->item_unit_type->name_long : $item->item->item_unit_type->name_short }}
                                </td>

                                <td class="description-cell description-continuation">{{ $descriptionChunk }}</td>

                                @foreach ($quotationChunk as $quotation)
                                    <td class="text-center">
                                        @php
                                            $quotationItem = $quotation->items[$index] ?? null;
                                            $price = $quotationItem?->bid_price;
                                        @endphp

                                        @if ($quotationItem?->is_free)
                                            free
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
                                <td class="description-cell description-continuation">{{ $descriptionChunk }}</td>
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
        <div style="margin: 15px 0; text-align: left;">Awarding Committee:</div>
        <table style="width:100%; text-align:center; margin-bottom:30px; border-collapse: collapse; border: none;">
            <tr>
                <th style="border: none;"><u>{{ $bac_chairperson['name'] }}</u></th>
                <th style="border: none;"><u>{{ $bac_vice_chairperson['name'] }}</u></th>
                @foreach ($bac_members as $member)
                    <th style="border: none;"><u>{{ strtoupper($member['name']) }}</u></th>
                @endforeach
                <th style="border: none;"><u>{{ $regional_director['name'] }}</u></th>
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
