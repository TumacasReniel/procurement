<!DOCTYPE html>
<html>
<head>
    <title>Notice of Award</title>
    <style>
        @page { 
            margin: 10px 50px 50px 50px;
        }
        body { font-family: Arial, sans-serif; font-size: 9px; margin: 0; }

        .content {
            margin-bottom: 20px;
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
            padding: 5px; 
            vertical-align: top;
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
            position: fixed;
            bottom: -20px;
            left: 0;
            right: 0;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .bold { font-weight: bold; }
    </style>
</head>
<body>

    <div class="text-center">
        <img src="{{ public_path('/images/logo-sm.png') }}" alt="Logo Left" style="float:left; height:50px; width: 50px" >
        <div style="line-height: .1">
            <p>Republic of the Philippines</p>
            <h3>Department of Science and Technology</h3>
            <p>Regional Office No. IX</p>
        </div>
    </div>

    <center style="margin-right:-30px"> <h2>ABSTRACT OF BIDS</h2></center>


    <table class="main-table">
        <thead>
            <tr>
                <td style="text-align: left;" colspan="2">Standard Form Number:</td>
                <td style="text-align: left;" colspan="{{ count($quotations) + 1 }}">Project Reference No.:</td>
            </tr>
            <tr>
                <td style="text-align: left;" colspan="2">Revised Date:</td>
                <td style="text-align: left;" colspan="2">Name of the Project:</td>
                <td style="text-align: left;" colspan="{{ count($quotations) - 1 }}">Location of the Project:</td>
            </tr>
            <tr>
                <th style="width:5%">Item No.</th>
                <th style="width:12%">Quantity/Unit</th>
                <th style="width:35%">Description</th>
                @foreach ($quotations as $quotation)
                    <th style="width:auto">{{ $quotation->supplier->name }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @php
                $items = $quotations[0]->items;
            @endphp

            @foreach ($items as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="text-center">
                        {{ $item->item->item_quantity }} 
                        {{ $item->item->item_quantity > 1 ? $item->item->item_unit_type->name_long : $item->item->item_unit_type->name_short }}
                    </td>
                    <td> 
                        <div style="margin-top: -5px">
                            {!! $item->item->item_description !!}
                        </div>
                    </td>

                    @foreach ($quotations as $quotation)
                        <td class="text-center">
                            @php
                                $price = $quotation->items[$index]->bid_price;
                            @endphp

                            @if (is_null($price))
                                No Bid
                            @elseif ($price == 0)
                                Free
                            @else
                                {{ number_format($price, 2) }}
                            @endif
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>


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
            $size = 8;
            $width = $pdf->get_width();
            $height = $pdf->get_height();
            $y_axis = $height - 25; 

            // LEFT: Procurement Code
            $text_code = "{{ $procurement->code }}";
            $pdf->page_text(35, $y_axis, $text_code, $font, $size, array(0,0,0));

            // RIGHT: Page Counter
            $text_page = "Page {PAGE_NUM} of {PAGE_COUNT}";
            $text_width = $fontMetrics->get_text_width($text_page, $font, $size);
            $pdf->page_text($width - $text_width + 50, $y_axis, $text_page, $font, $size, array(0,0,0));
        }
    </script>
</body>
</html>