@php
    $totalAmount = 0;
    foreach ($items as $item) {
        $totalAmount += ($item->item_quantity * $item->item_unit_cost);
    }

    /**
     * ADAPTABLE FILLER LOGIC
     * Determines the space needed on the LAST page to push the TOTAL row
     * to the bottom of the content area.
     */
    $basePageHeight = 500; 
    $estimatedContentHeight = 0;
    foreach ($items as $item) {
        $charCount = strlen($item->item_description);
        $lines = max(substr_count($item->item_description, "\n") + 1, ceil($charCount / 65));
        $estimatedContentHeight += ($lines * 16) + 10; 
    }
    
    // Calculate filler for the final page
    $fillerHeight = ($estimatedContentHeight < $basePageHeight) 
        ? ($basePageHeight - $estimatedContentHeight) 
        : 0;
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        @page { 
              margin: 50px 50px 50px 50px;
        }
        body { font-family: Arial, sans-serif; font-size: 9px; margin: 0; }

        .content {
            margin-bottom: 20px; /* Space for the footer */
        }
        
        .main-table {
            width: 100%;
            border-collapse: collapse;
            /* Outer border of the table */
            border: 1.5px solid black; 
            table-layout: fixed;
        }

        /* REPEATING HEADER */
        thead { display: table-header-group; }
        
        /* REPEATING "CLOSING" LINE */
        /* This forces the table to draw a bottom border at every page break */
        tfoot { display: table-footer-group; }
        .tfoot-spacer { height: 0px; border-top: 1.5px solid black; }

        .main-table th { 
            border: 1px solid black; 
            padding: 5px; 
            background: #ffffff; 
            text-align: center;
        }

        .main-table td {
            /* Full borders ensure the table looks "closed" on all sides */
            border: 1px solid black;
            padding: 4px 8px;
            vertical-align: top;
            word-wrap: break-word;
        }

    
        /* .total-row td {
            border-top: 1.5px solid black !important;
            border-bottom: 1.5px solid black !important;
            font-weight: bold;
        } */

        .sig-table {
            width: 100%;
            border-collapse: collapse;
            border: 1.5px solid black;
            background-color: white;
        }
        .sig-table td { padding: 5px; border: none; }
        .border-bottom { border-bottom: 1px solid black !important; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .bold { font-weight: bold; }

         input[type=checkbox] { display: inline; }
        input[type=checkbox]:before { font-family: DejaVu Sans; }
        label {
            display: inline-block;
        }

        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            left: 0;
            margin-left: auto;
            margin-right: auto;
        }
        .page-break {
            page-break-after: always;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
      <!-- <div class="footer">
        <table style="margin-top: -5px; border-bottom-style: hidden; border-right-style: hidden; border-top-style: hidden; border-left-style: hidden;">
            <tr>
                <td style="border-right-style: hidden; width: 3%; text-align: right;">-</td>
                <td style="border-right-style: hidden;" style="width: 50%; text-align: left; font-size: 10px;"><br/> <span style="font-weight: bold; color: #072388;">{{ $procurement->code }}</span></td>
                
            </tr>
        </table>
    </div> -->

    <div class="content">
            <div style="font-family:Arial;">
                <img src="{{ public_path('images/logo-sm.png') }}" alt="tag" style="position: absolute; top: -4; left: 60; width: 50px; height: 50px;">
                <center style="font-size: 10px; margin-bottom: 0px; text-transform: uppercase;">Republic of the Philippines</center>
                <center style="font-size: 11px; margin-bottom: 0px; font-weight: bold;">DEPARTMENT OF SCIENCE AND TECHNOLOGY</center>
                <!-- <center style="font-size: 11px;">Pettit Baracks, Zamboanga City | (062) 991-1024 | dost9info@gmail.com</center> -->
                <br/>
                <!-- <center style="margin-top: 8px; font-size: 11px;  color:#000; font-weight: bold; padding: 2px;">DOST Region Office No. IX</center> -->
                 <div style="float: right; border: 1px solid black; padding: 2px 8px; font-size: 10px; margin-top: -30px; text-align: center;">
                        <span class="bold">FASS-PUR F08</span><br>
                        <span>Rev. 1/07-01-23</span>
                </div>
                <center style="font-size: 11px;  font-weight: bold; padding: 2px; text-transform: uppercase;margin-top: 10px">
                    Procurement Request
                </center>
                
            </div> 
        </div>

    <table style="width: 100%; border-collapse: collapse; border: 1.5px solid black; ">
        <tr>
            <td colspan="4" style="border: 1px solid black; padding: 4px;">Entity Name: <u>Department of Science and Technology - IX</u></td>
            <td colspan="2" style="border: 1px solid black; padding: 4px;">Fund Cluster: <u>{{ $procurement->fund_cluster?->name ?? 'Regular Fund' }}</u></td>
        </tr>
        <tr>
            <td colspan="2" style="border: 1px solid black; padding: 4px;">Office/Section: {{ $procurement->division->name }}</td>
            <td colspan="2" style="border: 1px solid black; padding: 4px;">PR No: <strong>{{ $procurement->code }}</strong></td>
            <td colspan="2"  rowspan="2" style="border: 1px solid black; padding: 4px;">Date: <strong>{{ date('m-d-Y', strtotime($procurement->date)) }}</strong></td>
        </tr>
        <tr>
            <td colspan="4">Responsibility Center Code: <br>{{ $procurement->unit->responsibility_center_code }}</td>
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
            @foreach ($items as $item)
                <tr>
                    <td class="text-center">{{ $item->item_no }}</td>
                    <td class="text-center">{{ $item->item_unit_type->name_short }}</td>
                    <td>
                        <div>
                            {!! $item->item_description !!}
                        </div>
                    </td>
                    <td class="text-center">{{ $item->item_quantity }}</td>
                    <td class="text-center">{{ number_format($item->item_unit_cost, 2) }}</td>
                    <td class="text-center bold">{{ number_format($item->item_quantity * $item->item_unit_cost, 2) }}</td>
                </tr>
            @endforeach

            @if($fillerHeight > 0)
                <tr>
                    <td style="height: {{ $fillerHeight }}px; border-bottom: none;"></td>
                    <td style="border-bottom: none;"></td>
                    <td style="border-bottom: none;"></td>
                    <td style="border-bottom: none;"></td>
                    <td style="border-bottom: none;"></td>
                    <td style="border-bottom: none;"></td>
                </tr>
            @endif

            <tr class="total-row">
                <td colspan="5" class="bold">TOTAL</td>
                <td class="text-right bold">{{ number_format($totalAmount, 2) }}</td>
            </tr>
        </tbody>
    </table>
    <table class="sig-table">
            <tr>
                <td colspan="6" class="border-bottom" style="padding: 10px;">
                    <strong>Purpose:</strong> {{ $procurement->purpose }}
                </td>
            </tr>
            <tr class="text-center bold">
                <td width="15%"></td>
                <td colspan="2">Requested By:</td>
                <td colspan="3">Approved By:</td>
            </tr>
            <tr style="height: 35px;">
                <td>Signature:</td>
                <td colspan="2" class="text-center">__________________________</td>
                <td colspan="3" class="text-center">__________________________</td>
            </tr>
            <tr class="text-center bold">
                <td>Printed Name:</td>
                <td colspan="2"><u>{{ strtoupper($procurement->requested_by->profile->fullname) }}</u></td>
                <td colspan="3"><u>{{ strtoupper($procurement->approved_by->profile->fullname) }}</u></td>
            </tr>
            <tr class="text-center">
                <td>Designation:</td>
                <td colspan="2">{{ $procurement->requested_by->designation ?? 'Division Head' }}</td>
                <td colspan="3">{{ $procurement->approved_by->designation ?? 'Regional Director' }}</td>
            </tr>
        </table>


  
    
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