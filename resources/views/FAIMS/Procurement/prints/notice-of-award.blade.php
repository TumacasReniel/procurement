<!DOCTYPE html>
<html>
<head>
    <title>Notice of Award</title>
    <style>
        html, body {
            font-family: Arial, sans-serif;
            margin: 50px 50px 50px 50px;
            font-size: 12px;
            line-height: 1.5;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .text-left {
            text-align: left;
        }

        .header img {
            width: 100px;
            height: 100px;
        }

        .letter-date {
            margin-bottom: 20px;
        }

        .letter-body p {
            margin: 10px 0;
            text-align: justify;
        }

        .signature {
            margin-top: 50px;
        }

        .signature span {
            display: block;
        }
    </style>
</head>
<body>
    <!-- Optional Header -->
    <!--
    <div class="header text-center">
        <img src="{{ public_path('/images/logo-sm.png') }}" alt="Logo Left" style="float:left;">
        <img src="{{ public_path('/images/bp-logo.webp') }}" alt="Logo Right" style="float:right;">
        <div style="margin-top:20px;">
            <h3>Department of Science and Technology</h3>
            <p>Regional Office No. IX</p>
        </div>
    </div>
    -->

    <div class=" text-center">
       <h2> NOTICE OF AWARD</h2>
    </div>

    <div style="font-size: 16px">
        <br>
        <!-- Date -->
        <div class="letter-date ">
            {{ $noa->created_at->format('d F Y') }}
        </div>

        <!-- Recipient -->
        <div class="recipient-block text-left" style="margin-bottom: 20px; line-height: 1.4;">
            <p style="margin: 0;"> {{ $noa->procurement_quotation->supplier?->conformes[0]->name ?? "" }}</p>
            <p style="margin: 0;">{{ $noa->procurement_quotation->supplier?->name }}</p>
            <p style="margin: 0;">{{ $noa->procurement_quotation->supplier?->address?->address }}</p>

        </div>


        <!-- Salutation -->
        <p>Dear {{ $noa->procurement_quotation->supplier?->conformes[0]?->name  ?? ""}},</p>

        <!-- Body -->
        <div class="letter-body">
            <p>
                 @php
                    $count = count($item_nos);

                    if ($count === 1) {
                        $item_text = $item_nos[0];
                         $item_label = "item no";
                    } elseif ($count === 2) {
                        $item_text = $item_nos[0] . ' & ' . $item_nos[1];
                        $item_label = "item nos";
                    } else {
                        $item_text = implode(', ', array_slice($item_nos, 0, -1)) . ', & ' . end($item_nos);
                        $item_label = "item nos";
                    }
                @endphp
                We are pleased to inform you that the procurement for the <b>"{{ $procurement->title }}"</b>, {{ $item_label }}
                <span style="font-weight:bold">{{ $item_text }}</span>, 
                under our reference PR no. <b>{{ $procurement->code }}</b> has been awarded to your company with the total contract
                amount of <b>{{ $amount_to_words }} (Php {{ $total_amount }})</b>.
            </p>

            <p>
                The item awarded under this procurement is as follows: {{ $item_label }}. {{ $item_text }}
            </p>

            <p>
                The delivery term is within {{ $noa->procurement_quotation->delivery_term }} upon receipt of the Notice to Proceed, 
                and the delivery shall be made to the Department of Science and Technology – Regional Office IX, 
                Pettit Barracks, Zone IV, Zamboanga City.
            </p>

            <p>
                We appreciate your interest in this opportunity and we look forward to your satisfactory performance of your obligations 
                under the project.
            </p>

            <p>Thank you.</p>
        </div>

        <!-- Closing / Signature -->
        <div class="signature text-left">
            <p>Very truly yours,</p>
            <p><b>{{ $regional_director['name'] }}</b></p>
            <span style="text: line 0.1em;">  {{  $regional_director['designation']?->name }}</span>
        </div>
    </div>

    <div style="page-break-before: always;">
        <p>The item awarded under this procurement is as follow</p>
         <table style="width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 10px;">
                <thead>
                    <tr style="background-color: #f2f2f2;">
                        <th style="border: 1px solid #000; padding: 3px; text-align: center; font-size: 10px;">Item No.</th>
                        <th style="border: 1px solid #000; padding: 3px; text-align: center; font-size: 10px;">Unit</th>
                        <th style="border: 1px solid #000; padding: 3px; text-align: center; font-size: 10px;">Description</th>
                        <th style="border: 1px solid #000; padding: 3px; text-align: center; font-size: 10px;">Quantity</th>
                        <th style="border: 1px solid #000; padding: 3px; text-align: center; font-size: 10px;">Unit Cost</th>
                        <th style="border: 1px solid #000; padding: 3px; text-align: center; font-size: 10px;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($noa->items as $item)
                        <tr>
                            <td style="border: 1px solid #000; padding: 3px; text-align: center; font-size: 9px;">{{ $item->item->item->item_no }}</td>
                            <td style="border: 1px solid #000; padding: 3px; text-align: center; font-size: 9px;">{{ $item->item->item->item_unit_type->name_short ?? '' }}</td>
                            <td style="border: 1px solid #000; padding: 3px; text-align: justify; font-size: 9px;">{!! $item->item->item->item_description !!}</td>
                            <td style="border: 1px solid #000; padding: 3px; text-align: center; font-size: 9px;">{{ $item->item->item->item_quantity }}</td>
                            <td style="border: 1px solid #000; padding: 3px; text-align: center; font-size: 9px;">{{ number_format($item->item->bid_price, 2) }}</td>
                            <td style="border: 1px solid #000; padding: 3px; text-align: center; font-size: 9px;">{{ number_format($item->item->bid_price * $item->item->item->item_quantity, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
    </div>


  <script type="text/php">
        if ( isset($pdf) ) {
            $font = $fontMetrics->get_font("Arial, Helvetica, sans-serif", "normal");
            $size = 8;
            $width = $pdf->get_width();
            $height = $pdf->get_height();
            $y_axis = $height - 25; 

            // LEFT: NOA Code
            $text_code = "{{ $noa->code }}";
            $pdf->page_text(35, $y_axis, $text_code, $font, $size, array(0,0,0));

            // RIGHT: Page Counter
            $text_page = "Page {PAGE_NUM} of {PAGE_COUNT}";
            $text_width = $fontMetrics->get_text_width($text_page, $font, $size);
            $pdf->page_text($width - $text_width + 50, $y_axis, $text_page, $font, $size, array(0,0,0));
        }
    </script>
</body>
</html>
