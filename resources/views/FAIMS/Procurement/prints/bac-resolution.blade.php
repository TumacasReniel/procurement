y<!DOCTYPE html>
<html>
<head>
    <title>BAC Resolution</title>
    <style>


        html, body {
            font-family: Arial, sans-serif;
            margin: 15px 15px 15px 15px;
            padding: 0;
            height: 100%;
            font-size: 10px;
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
        }
        td {
            border-collapse: collapse;
            padding: 0px;
            vertical-align: top;
        }

        th {
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

        .content-container {
            page-break-inside: auto;
            overflow: visible;
        }

        .content-container p, .content-container div, .content-container table {
            page-break-inside: avoid;
            page-break-after: auto;
        }

        .border-none{
            border: none; 
        }

    </style>
</head>
<body>

    <!-- <div style="font-size: 20px" >
        <img src="{{ public_path('/images/logo-sm.png') }}" style="position: absolute; top: -2; left: 30; width: 100px; height:100px;" alt="">
        <div style="position: absolute; top: 10; left: 110;">
            <span style="font-size: 16px">Republic of the Philippines</span>
            <h3 style="line-height: -3; font-size: 20px">DEPARTMENT OF SCIENCE AND TECHNOLOGY</h3>
            <p style="line-height: .1; font-size: 16px">Regional Office No. IX</p>
        </div>
        <img src="{{ public_path('/images/bp-logo.webp') }}" style="position: absolute; top: -2; right: 30; width: 100px; height:100px;" alt="">
      
    </div> -->

    <div style="margin: 50px 90px 50px 90px;text-align:justify; font-size: 16px; font-width: bold">
        <div style="text-align:center">
        <b>
            BAC Resolution No. <u style="color: red">{{ $bac_resolution['code'] }}</u>
       </b>
        </div>

        <div>
            {!! $bac_resolution['body'] !!}
        </div>


        <p style="text-align: left;margin-bottom:40px">Recommended by:</p>  
       <table style="text-align:center;margin-bottom:50px">
         @foreach ($bac_members as $member)
            <th>
                {{  strtoupper($member['name']) }}        
            </th>
        @endforeach
        <tr>
            <td>
                Member, BAC
            </td>
            <td>
                Member, BAC
            </td>
            <td>
                Member, BAC
            </td>
        </tr>

      
        </table>
        <br>
        <table style="margin-bottom:20px">
            <tr>
                <th style="text-align: center;">
                    {{ $bac_vice_chairperson['name'] }}
                </th>
                <th style="text-align: center; ">
                   {{ $bac_chairperson['name'] }}
                </th>
                </tr>
            <tr>
                <td style="text-align: center">
                    Vice Chairperson, BAC
                </td >
                <td style="text-align: center">
                    Chairperson, BAC
                </td>
            </tr>
        </table>

      <table>
        <br>
        <tr style="padding-bottom:40px ">
            <td colspan="3" style="text-align:center;padding-bottom:40px">
                Approved by:
            </td>
        </tr>
   
        <tr>
            <th colspan="3">   {{ $regional_director['name'] }}</th>
        </tr>
        <tr>
            <td colspan="3" style="text-align:center">
                {{  $regional_director['designation']->name }}
                
            </td>
        </tr>
        <tr>
            <td colspan="3" style="text-align:center">
                Date:_________<
            </td>
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

            // LEFT: bac_resolution Code
            $text_code = "{{ $bac_resolution->code }}";
            $pdf->page_text(35, $y_axis, $text_code, $font, $size, array(0,0,0));

            // RIGHT: Page Counter
            $text_page = "Page {PAGE_NUM} of {PAGE_COUNT}";
            $text_width = $fontMetrics->get_text_width($text_page, $font, $size);
            $pdf->page_text($width - $text_width + 50, $y_axis, $text_page, $font, $size, array(0,0,0));
        }
    </script>
</body>
</html>
