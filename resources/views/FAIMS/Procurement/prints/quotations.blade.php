<!DOCTYPE html>
<html>
<head>
    <title>Quotation Request</title>
    <style>
        .page-counter:before {
            content: counter(page) " of " counter(pages);
        }
        
        body {
            font-family: Arial, sans-serif;
            margin: -30px;
            padding: 20px;
            
        }
        .header {
            margin-bottom: 20px;
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
            padding: 5px;
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
        th, td {
            border: 1px solid black;
            border-collapse: collapse;
            padding: 7px;
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
        position: fixed;
        bottom: -20px;
        left: 0px;
        right: 0px;
        height: 50px;
        width: 100%;
    }
    .footer-table {
        width: 100%;
        border: none !important; /* Ensures no borders show */
    }
    .footer-table td {
        border: none !important;
        font-size: 10px;
        vertical-align: bottom;
    }
    .pagenum:before {
        content: counter(page) " of " counter(pages);
    }

    </style>
</head>
<body>
    <div class="text-right">
        <div class="border-container">
            <p class="bold">FASS-PUR F06</p>
            <p class="small-text">Rev.1/07-01-2023</p>
        </div>
    </div>
    <div class="text-center">
        <span style="font-size: 12px">Republic of the Philippines</span>
        <h3 style="line-height: .1; font-size: 12px">DEPARTMENT OF SCIENCE AND TECHNOLOGY</h3>
        <p style="line-height: .1; font-size: 12px">Regional Office No. IX</p>
        <p style="line-height: .1; font-size: 12px">Pettit Barracks, Zone IV, Zamboanga City</p>
        <h3>
            REQUEST FOR QUOTATION
        </h3>

        <div class="text-right-date" style="font-size: 10px;">
            <h3>Date: <u>{{  $quotation->created_at->format('m-d-Y') ?? ___________________________ }}</u></h3>
    
            <h3>RFQ No.: <u> {{  $quotation->code ?? '________________________'}}</u> </h3>
        </div>
    <div class="text-left" style="font-size: 10px; margin-top: 40px;">
        <h3>
            <h3 style="margin-bottom:-30px; margin-left: 215px">
                <span>___________________________</span>
            </h3>
            <h3>
                Company Name/Business Name: {{ $supplier->name }}
            </h3>
            <h3 style="margin-bottom:-30px; margin-left: 60px">
                <span>___________________________</span>
            </h3>
            <h3>
                Address:{{ $supplier->address?->address }}
            </h3>
            <h3 style="margin-bottom:-30px; margin-left:210px">
                <span>_____________________________</span>
            </h3>
            <h3>
                Business/Mayor’s Permit No.:__________________________________________________________________
            </h3>
            <h3 style="margin-bottom:-30px; margin-left:290px">
                <span>_____________________</span>
            </h3>
            <h3>
                Company Tax Identification Number (TIN):________________________________________________________
            </h3>
            <h3 style="margin-bottom:-30px; margin-left:220px">
                <span>____________________________</span>
            </h3>
            <h3>
                PhilGEPS Registration Number:_________________________________________________________________
            </h3>
        </h3>

    </div>

    <div class="border-container2 " style="font-size:11px">
        <p class="text-left">
            <i>
                <b style="padding:10px">
                    TO WHOM IT MAY CONCERN:
                </b>
            </i>
        </p>
        <p style="text-indent: 50px; text-align: justify; padding: 10px ">
            <i>
                    Please quote your <b>best offer with lowest price/s</b> on the lot or item/s below, subject to the General Conditions indicated
                herein, stating the shortest time of delivery and submit your quotation duly signed by your official representative not later
                than <u>{{ $quotation->submission_not_later_than }}</u> at <u>9:00</u> am to the address listed above.
            </i>
        </p>

      
        <div class="text-left" style="margin-left: 450px">
            <p style="text-align: center">
                <i>
                    <p >
                        Very truly yours,
                        
                    </p>
                    <br>
                    <p class=" bold" style="line-height: 1; text-transform: uppercase;">
                           <u>{{ $supply_officer->profile->fullname }}</u>
                        <br>
                        <p>
                          
                           <span >
                             Supply Officer
                           </span>
                        </p>
                    </p>

                </i>
            </p>
        </div>
    </div>

    <div class="border-container3 " style="font-size: 11px; line-height: 1.5; margin-top:20px">
        <u>
            <b>GENERAL CONDITIONS</b>
        </u>

        <ol style="text-align: justify ; text-decoration: none; list">
            <li>
                Bidders shall provide correct and accurate information required in this form.
            </li>
            <li>
                Any interlineations, erasures, or overwriting shall be valid only if they are signed or initialed by you or any of your duly
                authorized representative/s.
            </li>
            <li>
                 All entries must be typewritten or must be legible if handwritten;
            </li>
            <li>
                Price quotation/s must be valid for a period of ninety (90) calendar days from the deadline of submission.
            </li>
           <li>
                Price quotation/s, to be denominated in Philippine peso, shall include all taxes, duties, and/or levies payable.
           </li>
           <li>
                Quotations exceeding the Approved Budget for the Contract (ABC) shall be rejected.
           </li>
           <li>
                Award of contract shall be made to the lowest quotation which complies with the technical specifications, requirements and
           other terms and conditions stated herein.
           </li>
           <li>
                The item/s shall be delivered according to the accepted offer of the bidder.
           </li>
            <li>
                Item/s delivered shall be inspected on the scheduled date and time of DOST-IX. The delivery of the item/s shall be
                acknowledged upon the delivery to confirm the compliance with the technical specifications.
            </li>
            <li>
                Item/s delivered must have warranties for unit replacements, parts, labor or other services as applicable.
            </li>

            <li>
                Payment shall be made after delivery and upon the submission of the required supporting documents, i.e., Order Slip and/or
                Billing statement, Charge/Sales Invoice by the supplier, contractor, or consultant. Our Government Servicing Bank, i.e., the Land
                Bank of the Philippines (LBP), shall credit the amount due to the LBP account of the supplier, contractor, or consultant<b> not
                earlier than twenty-four (24) hours, but not later than forty-eight (48) hours</b>, upon receipt of our advice.
            </li>
            <li>
                Liquidated damages equivalent to one-tenth of one percent (0.1%) of the value of the goods not delivered within the prescribed
                delivery period shall be imposed per day of delay. DOST-IX may terminate the contract once the cumulative amount of
                liquidated damages reaches ten percent (10%) of the amount of the contract, without prejudice to other courses of action and
                remedies open to it.
            </li>

     </ol>
    </div>

     <!-- Footer -->
     <div class="footer">
        
    </div>

    <!-- Second Page -->
    <div class="table-container page-break" style="font-size: 10px; text-align:left">
        <div>
        <p style="font-size:12px"> After having carefully read and accepted Your General Conditions, I/We submit our quotation/s for the item/s as follows:</p>
        </div>
       
        <div>
            <table>
                <thead>
                    <!-- First Row (Higher-Level Headers) -->
                    <tr >
                        <th colspan="1" rowspan="3" style="vertical-align: middle">No.</th>
                        <th colspan="1"  rowspan="3" class="text-center" style="vertical-align: middle">Item Description</th>
                        <th rowspan="2" style="vertical-align: middle">Quantity</th>
                        <th colspan="1" rowspan="3" style="vertical-align: middle">ABC</th>
                        <th colspan="4" rowspan="1" class="th-background" style="text-align: left; background: grey; color: white">TO BE FILLED UP BY SUPPLIER/CONTRACTOR/CONSULTANT</th>
                        

                    </tr>
                    <!-- Second Row (Subheaders) -->
                    <tr>
                        <td colspan="2" class="text-center">
                            <b>Financial Proposal </b>
                            <div style="font-size: 9px">
                                (Please Indicate your price offer)
                            </div>
                        </td>
                        <td rowspan="2" class="text-center" style="vertical-align: middle">
                            <b>Technical Proposal</b>
                            <div style="font-size: 9px">
                                (Please indicate Brand/Model offer, if applicable)
                            </div>
                        </td>

                        <th rowspan="2" style="vertical-align: middle">Delivery Term</th> 
                        
                    </tr>

                    <tr>
                        <th style="vertical-align: middle">Unit</th>
                        <th>Unit Price</th>
                        <th>Total Price</th>
                    </tr>
                </thead>
                <tbody >
                @foreach ($items as $index => $item)
                    <tr>
                        <td colspan="1" style="padding-top:15px">{{ $index+1 }}</td>
                        <td colspan="1" >
                            <span style="padding-top: -10px">
                                {!! $item->item->item_description !!}
                            </span>
                        </td>
                        <td style="padding-top:15px">{{ $item->item->item_quantity }} {{ $item->item->item_unit_type->name_long}}</td>
                        <td style="padding-top:15px">{{ number_format($item->item->total_cost, 2) }}</td>
                        <td  style="padding-top:15px"></td>
                        <td  style="padding-top:15px"></td>
                        <td  style="padding-top:15px"></td>
                        <td  style="padding-top:15px"> </td>       
                    </tr>

                    @endforeach
                </tbody>
                

              
                
            
            </table>
        </div>

        <div class="border-container3" style="margin-top:10px">
          
          <b class>  
              <u>
                  TERMS OF PAYMENT
              </u>
          </b>
          <p style="text-align: justify;margin-bottom: 20px">
              <b >
                  Payment shall be made through Land Bank’s LDDAP-ADA facility, within thirty (30) days after Submission of Billing Statements or Charge/Sales Invoice
                  and Inspection and User Acceptance of the products/services.
              </b>
          </p>
         <p >
              <b>
                  <i>
                      <u>Landbank (LBP) Account Details:</u>
                  </i>
              </b>
              
         </p>
         <p style="line-height: .5">
              Account Number: _______________________________________________________________________________________________
         </p>
         <p style="line-height: .5; margin-bottom: 20px" >
              Account Name: _______________________________________________________________________________________________
         </p>

         <p style="text-align: justify">
         In case of unavailability of LBP Account, payments shall be made through check payable to the registered company/business name.
         Check may be sent through courier at the registered company/business address or may be claimed at the DOST-IX Office, Zamboanga City.
         </p>
      </div>
      <div style="font-size: 13px ; margin-left: 50px;  margin-top:10px ">
          <p>
              I hereby certify that the above information is true and correct.
            
          </p>
         <div style="line-height:.5">
          <p >
                  ________________________________________________
              </p>
              <p>     
                  Signature over Printed Name of Authorized Representative

              </p>
         </div>
        <div style="line-height:.5">
          <p >
              ________________________________________________
              </p>
              <p>
                  Position/Designation
              
              </p>
          </div>
         <div style="line-height:.5">
          <p>
              ________________________________________________
              </p>
              <p>

                  Contact Nos.
              </p>
         </div>
      </div>

  

  </div>
  <script type="text/php">
    if ( isset($pdf) ) {
        $font = $fontMetrics->get_font("Arial, Helvetica, sans-serif", "normal");
        $size = 8;
        $width = $pdf->get_width();
        $height = $pdf->get_height();
        $y_axis = $height - 25; 

            // 1. LEFT SIDE: Procurement Code
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


