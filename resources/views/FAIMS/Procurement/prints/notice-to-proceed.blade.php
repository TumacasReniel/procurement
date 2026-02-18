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
    
    <!-- <div class="header text-center">
        <img src="{{ public_path('/images/logo-sm.png') }}" alt="Logo Left" style="float:left;">
        <img src="{{ public_path('/images/bp-logo.webp') }}" alt="Logo Right" style="float:right;">
        <div style="margin-top:20px;">
            <h3>Department of Science and Technology</h3>
            <p>Regional Office No. IX</p>
        </div>
    </div> -->
   

    <div class=" text-center">
       <h2> NOTICE TO PROCEED</h2>
    </div>

    <div style="font-size: 16px">
        <br>
           <!-- Date -->
        <div class="letter-date ">
            {{ $noa->created_at->format('d F Y') }}
        </div>

        <!-- Recipient -->
        <div class="recipient-block text-left" style="margin-bottom: 20px; line-height: 1.4;">
            <p style="margin: 0;"> {{ $noa->procurement_quotation->supplier->conformes[0]->name }}</p>
            <p style="margin: 0;">{{ $noa->procurement_quotation->supplier->name }}</p>
            <p style="margin: 0;">{{ $noa->procurement_quotation->supplier->address?->address }}</p>

        </div>


        <!-- Salutation -->
        <p>Dear {{ $noa->procurement_quotation->supplier->conformes[0]->name }},</p>
      

        <!-- Body -->
        <div class="letter-body">
            <p>
        
                Please be informed that you are hereby given the Notice to Proceed for the implementation of the project 
                {{ $procurement->title }}, under reference <b>{{ $procurement->code }}</b>, which has been awarded
                to your company with a total contract amount of <b>{{ $amount_to_words }} (Php {{ $total_amount }})</b>.
              
            </p>

            <p>
                 You are instructed to commence the delivery and completion of the awarded item/s effective immediately upon receipt 
                of this Notice. The delivery term is {{ $quotation->delivery_term }}, and the delivery shall be made to the 
                {{ $po->place_of_delivery->name }}.
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
            <br>
            <p><b>{{ $regional_director['name'] }}</b></p>
            <span>Regional Director</span>
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
    </div>
</body>
</html>
