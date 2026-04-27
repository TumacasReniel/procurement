<!DOCTYPE html>
<html>
<head>
    <title>Quotation Request</title>
    <style>


        html, body {
            font-family: Arial, sans-serif;
            margin: 15px 15px 15px 15px;
            padding: 0;
            height: 100%;
            font-size: 12px;
            line-height: 1;
        }

        .header {
            margin-bottom: 10px;
        }
        h1 {
            margin: 10px 0;
        }
        .subheader span {
            display: block;
            margin: 5px 0;
        }
        .text-center{
            text-align: center;
        }
        .text-right{
            text-align: right ;
            line-height: 1;
        }
        .text-left{
            text-align: left ;
            line-height: 1;
        }


        .text-right-date {
            text-align: left;
            position:absolute;
            right:0;
            line-height: 1;
        }
        .border-container {
            margin-top: 0px;
            border: solid 1px black;
            padding: 2px 8px 2px 8px;
            display: inline-block; /* Keeps the border close to the content */
            margin-right: 20px;
        }

        .border-container2 {
            margin-top:-20px;
            border: solid 1px black;

        }

        .border-container3 {
            border: solid 1px black;
            font-size: 11px;
            margin-bottom: 20px;

        }

        .bold {
            font-weight: bold;
            font-size:11px;
        }
        .small-text {
            font-size: 8px;
            padding-right: 15px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;;
        }
        td {
            border: 1px solid ;
            border-collapse: collapse;
            padding: 0px;
            vertical-align: top;
        }

        th {
            border: 1px solid ;
            border-collapse: collapse;
            padding: 2px;
            vertical-align: top;
        }

        .page-break {
            page-break-before: always; /* Forces a new page when printing */
        }

        . text-left{
            background: gray;
            color: white;
        }

        .footer {
            bottom: 10px; /* Distance from bottom of the page */
            width: 100%;
            font-size: 12px;
            color: black;
            text-align: left;
            padding:0px
        }

        .border-none{
            border: none; 
        }

    </style>
</head>
<body>
    <div class="text-right">
        <div class="border-container">
            <p class="bold">FASS-PUR F09</p>
            <p class="small-text">Rev.0/08-16-17</p>
        </div>
    </div>
    <div class="text-center" style="margin-top:-40px;">
        <span style="font-size: 12px">Republic of the Philippines</span>
        <h3 style="line-height: 1; font-size: 12px">DEPARTMENT OF SCIENCE AND TECHNOLOGY</h3>
        <p style="line-height: 1; font-size: 12px">Regional Office No. IX</p>


    </div>
    <br> 
    <h2 style="text-align:center; margin-top:-10px;">
        <b > INSPECTION & ACCEPTANCE REPORT</b>
    </h2> 
    
    <table>
        <tr>
            <td colspan="4" style="padding-left:5px">
               <p>
                 Supplier: <u>{{ $supplier->name  }}</u>
               </p>
               <p>
                PO No/Date: <u>{{ $purchase_order->code }}/{{ $purchase_order->po_date->format('m-d-Y') }}</u>
               </p>

               <p>
                Responsibility Center Code: <u>{{ $rc_code ?? '_________________________' }}</u>
               </p>

               <p>
                Requesiting Office/Dept.: <u>{{ $procurement->division->name  ?? '_________________________' }} </u>
               </p>
             
             
            </td>

            <td colspan="3" style="padding-left:5px">
                <p>
                 IAR No: <u>{{ $iar?->code  ?? '________________________________________' }}</u>
               </p>
                <p>
                 Date: <u>{{ $iar?->created_at?->format('m-d-Y') ?: '__________________________' }}</u>
               </p>
                <p>
                Invoice No: <u>{{ $iar?->invoice_no ?: '_______________________' }}</u>
                </p>
               <p>
                 Date: <u>{{ $iar?->invoice_date?->format('m-d-Y') ?: '__________________________' }}</u>
               </p>

            
            </td>
        </tr>

        <tr>
            <th>Stock/Property No.</th>
            <th colspan="4">Description</th>
            <th>Unit</th>
            <th>Quantiy</th>
        </tr>
        @php
            $total_amount = 0;
            $formatQuantity = function ($quantity) {
                $quantity = (float) $quantity;

                return floor($quantity) == $quantity
                    ? number_format($quantity, 0)
                    : rtrim(rtrim(number_format($quantity, 4, '.', ','), '0'), '.');
            };
        @endphp

        @foreach ($items as $item)
            @php
                $delivered_quantity = $item->delivered_quantity ?? $item->item->item->item_quantity;
                $line_total = $item->line_total ?? ($item->item->bid_price * $delivered_quantity);
                $total_amount += $line_total;
            @endphp
            <tr class="text-center">
                <td>{{ $item->item->item->item_no }}</td>
                <td colspan="4" style="padding: 6px; text-align: justify;">
                    <span>{{ $item->item->item->item_name }}</span>
                    <div style=" line-height: 1; word-wrap: break-word;">
                        {!! $item->item->item->item_description !!}
                    </div>
                </td>
                <td>{{ $item->item->item->item_unit_type->name_short ?? '' }}</td>
                <td>{{ $formatQuantity($delivered_quantity) }}</td>
            </tr>


        @endforeach

        
        <!-- Total Row -->
      <tr>
        <td colspan="6" style="border-right:none; padding: 5px"></td>
        <td colspan="1" style="text-align: center;border-left:none; padding: 5px">{{ number_format($total_amount, 2) }}</td>
      </tr>
      @php
          $inspectionDateText = $iar?->created_at?->format('d F Y')
              ?? $delivery_date?->format('d F Y')
              ?? '____________________________';
          $inspectionSignatoryLine = filled($inspected_by_name ?? null)
              ? strtoupper($inspected_by_name)
              : ' ';
          $inspectionSignatoryLabel = filled($inspected_by_name ?? null)
              ? 'Inspection Officer'
              : 'Inspection Officer/Inspection Committee';
      @endphp

      <tr >
        <td colspan="4"  style="padding:left: 10px">
            <p style="text-align: center; margin-bottom: 14px;"><b>INSPECTION</b></p>
            <p style="margin: 0 0 18px 12px;">
                <span style="font-weight: bold;">Date Inspected :</span>
                <span style="display:inline-block; min-width:190px; border-bottom:1px solid black; padding: 0 4px 2px 4px;">
                    {{ $inspectionDateText }}
                </span>
            </p>

            <div style="display: table; width: 100%; margin: 0 0 28px 12px;">
                <div style="display: table-cell; width: 26px; vertical-align: top;">
                    <span style="display:inline-block; width:18px; height:18px; border:1px solid black; text-align:center; line-height:18px; font-size:14px;">/</span>
                </div>
                <div style="display: table-cell; vertical-align: top; padding-left: 8px; line-height: 1.2;">
                    Inspected, verified and found in order as to quantity and
                    specifications
                </div>
            </div>

            <div class="text-center" style="padding: 0 10px 12px 10px;">
                <div style="height: 28px;"></div>
                <div style="border-bottom: 1px solid black; height: 1px; margin-bottom: 4px;"></div>
                <div style="text-transform: uppercase; font-weight: bold; font-size: 10px; line-height: 1.2;">
                    {{ $inspectionSignatoryLine }}
                </div>
                <div style="margin-top: 4px;">
                    {{ $inspectionSignatoryLabel }}
                </div>
            </div>
        </td>
        <td colspan="3" style="padding:left: 10px ; ">
        <p style="text-align: center"><b>ACCEPTANCE</b></p>
        <div>Date Received:____________________________</div>
        <br><br>
       <div style="margin-bottom:20px">
            <span style="display:inline-block; width:28px; height:28px; border:1px solid black; text-align:center; line-height:28px; vertical-align:middle;">{!! $is_partial_delivery ? '&nbsp;' : '/' !!}</span>
            <span style="margin-left:8px; vertical-align:middle;">Full</span>
        </div>

        <div>
            <span style="display:inline-block; width:28px; height:28px; border:1px solid black; text-align:center; line-height:28px; vertical-align:middle;">{!! $is_partial_delivery ? '/' : '&nbsp;' !!}</span>
            <span style="margin-left:8px; vertical-align:middle;">Partial</span>
        </div>
        <div class="text-center" style="; margin-top:30px; margin-bottom:20px">
            <div style="text-transform: uppercase; font-weight: bold;">
                {{ $supply_officer->fullname ?? '_________________________' }}
            </div>
            <div>
                Supply and/or Property Custodian
            </div>
        </div>
        </td>
      </tr>

   
    </table>

      <script type="text/php">
        if ( isset($pdf) ) {
            $font = $fontMetrics->get_font("Arial, Helvetica, sans-serif", "normal");
            $size = 12;
            $width = $pdf->get_width();
            $height = $pdf->get_height();
            $left_margin = 35;
            $right_margin = 35;
            $y_axis = $height - 25; 

            // LEFT: bac_resolution Code
            $text_code = "{{ $iar?->code ?? ($purchase_order?->iar?->code ?? '') }}";
            $pdf->page_text($left_margin, $y_axis, $text_code, $font, $size, array(0,0,0));

            // RIGHT: Page Counter
            $text_page = "Page {PAGE_NUM} of {PAGE_COUNT}";
            $text_width = $fontMetrics->get_text_width("Page 1 of 1", $font, $size);
            $pdf->page_text($width - $text_width - $right_margin, $y_axis, $text_page, $font, $size, array(0,0,0));
        }
    </script>
          
   
 
  
</body>
</html>
