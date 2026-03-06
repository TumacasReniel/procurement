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
            line-height: 0.1;
        }
        .text-left{
            text-align: left ;
            line-height: 0.5;
        }


        .text-right-date {
            text-align: left;
            position:absolute;
            right:0;
            line-height: 0.5;
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
        <h3 style="line-height: .1; font-size: 12px">DEPARTMENT OF SCIENCE AND TECHNOLOGY</h3>
        <p style="line-height: .1; font-size: 12px">Regional Office No. IX</p>


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
                PO No: <u>{{ $purchase_order->code }}</u>
               </p>
               <p>
                Date: <u>_________________________</u>
               </p>
             
             
            </td>

            <td colspan="3" style="padding-left:5px">
                <p>
                 IAR No: <u>{{ $iar?->code  ?? '________________________________________' }}</u>
               </p>
                <p>
                Invoice No: <u>_______________________</u>
                </p>
               <p>
                 Date: <u>__________________________</u>
               </p>

            
            </td>
        </tr>

        <tr>
           <td colspan="7" style="padding-left:5px">
             <p >
            Requesiting Office/Department: <u>{{ $procurement->division->name }}</u>
            </p>
           </td>
        </tr>

        <tr>
            <th>Stock No.</th>
            <th>Unit</th>
            <th colspan="2">Description</th>
            <th>Quantiy</th>
            <th>Unit Cost</th>
            <th>Amount</th>
        </tr>
        @php
            $total_amount = 0;
        @endphp

        @foreach ($items as $item)
            @php
                $line_total = $item->item->bid_price * $item->item->item->item_quantity;
                $total_amount += $line_total;
            @endphp
            <tr class="text-center">
                <td>{{ $item->item->item->item_no }}</td>
                <td>{{ $item->item->item->item_unit_type->name_short ?? '' }}</td>
                  <td colspan="2" style="padding: 6px; text-align: justify;">
                    <div style="margin-top:-5px;lline-height: 1.3; word-wrap: break-word;">
                        {!! $item->item->item->item_description !!}
                    </div>
                </td>
                <td>{{ $item->item->item->item_quantity }}</td>
                <td>{{ number_format($item->item->bid_price, 2) }}</td>
                <td>{{ number_format($line_total, 2) }}</td>
            </tr>


        @endforeach

        
        <!-- Total Row -->
      <tr>
        <td colspan="6" style="border-right:none; padding: 5px"></td>
        <td colspan="1" style="text-align: center;border-left:none; padding: 5px">{{ number_format($total_amount, 2) }}</td>
      </tr>
     
      <tr >
        <td colspan="4"  style="padding:left: 10px">
            <p style="text-align: center"><b >INSPECTION</b></p>
            <p style="margin-bottom:40px">Funds Available:____________________________</p>
            <p class="box ">Inspected, verified and found OK as to quantity &
                specifications.
            </p>
            <div class="text-center">
                <div style="text-transform: uppercase; font-weight: bold; margin-top:30px">
                    {{ $iar_chairperson['name'] ?? '_________________________' }}
                </div>
                <div style="margin-bottom:25px ; ">
                    Chairperson
                </div>

                @foreach ($iar_members as $member)
                    <div  style="text-transform: uppercase; font-weight: bold; margin-top:30px">{{  strtoupper($member['name']) }}     </div>   
                    <div style="margin-bottom:25px">
                        Member
                    </div>
                @endforeach
                
             


            
            </div>
        </td>
        <td colspan="3" style="padding:left: 10px ; ">
        <p style="text-align: center"><b>ACCEPTANCE</b></p>
        <div>Date Received:____________________________</div>
        <br><br>
       <div style="margin-bottom:45px"> 
            <span style="border:1px solid; padding:15px""> </span>  Full
        </div>

        <div > <span style="border:1px solid; padding:15px">  </span> Partial </div>
        <div class="text-center" style="; margin-top:30px; margin-bottom:20px">
            <div style="text-transform: uppercase; font-weight: bold;">
                {{ $supply_officer->fullname ?? '_________________________' }}
            </div>
            <div>
                Supply Officer
            </div>
        </div>
        </td>
      </tr>

   
    </table>

      <script type="text/php">
        if ( isset($pdf) ) {
            $font = $fontMetrics->get_font("Arial, Helvetica, sans-serif", "normal");
            $size = 8;
            $width = $pdf->get_width();
            $height = $pdf->get_height();
            $y_axis = $height - 25; 

            // LEFT: bac_resolution Code
            $text_code = "{{ $purchase_order?->iar?->code }}";
            $pdf->page_text(35, $y_axis, $text_code, $font, $size, array(0,0,0));

            // RIGHT: Page Counter
            $text_page = "Page {PAGE_NUM} of {PAGE_COUNT}";
            $text_width = $fontMetrics->get_text_width($text_page, $font, $size);
            $pdf->page_text($width - $text_width + 50, $y_axis, $text_page, $font, $size, array(0,0,0));
        }
    </script>
          
   
 
  
</body>
</html>