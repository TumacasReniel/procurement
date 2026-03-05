<template>
  <b-modal v-model="showModal" header-class="p-3" :title="action + ' Notice of Award'" size="xl" centered no-close-on-backdrop>
    <form class="customform">
      <BRow>
        <BCol class="mt-2" >
          <InputLabel value="Norice of Award Number" />
          <TextInput v-model="form.code" class="form-control" :light="true" readonly/>
        </BCol>

        <BCol  class="mt-2" >
          <InputLabel value="Procurement Number" />
          <TextInput v-model="procurement.code" class="form-control" :light="true" disabled />
        </BCol>
 
        <BCol lg="12" class="mt-2" >
          <InputLabel value="Content" />
           <CustomEditor  v-model="form.body" />

        </BCol>
      </BRow> 
    </form>

    <template v-slot:footer>
      <b-button @click="hide()" variant="light" block>Cancel</b-button>
      <b-button @click="submit(form)" variant="success" v-if="action == 'Create'" block>Save</b-button>
      <b-button @click="submit(form)" variant="success" v-if="action == 'Update'" block>Update</b-button>
    </template>
  </b-modal>
</template>

<script>
import { useForm } from '@inertiajs/vue3';
import Multiselect from "@vueform/multiselect";
import InputError from '@/Shared/Components/Forms/InputError.vue';
import InputLabel from '@/Shared/Components/Forms/InputLabel.vue';
import TextInput from '@/Shared/Components/Forms/TextInput.vue';
import axios from 'axios';
import CustomEditor from "@/Shared/Components/Forms/CustomEditor.vue";

export default {
  components: {
    InputError,
    InputLabel,
    TextInput,
    Multiselect,
    CustomEditor,
    // ckeditor: CKEditor.component
  },
  props: ['procurement'],
  data() {
    return {
    isEditorReady :false,
      form: useForm({
        id: null,
        code: null,
        procurement_id: this.procurement.id,
        body:  '',
        type: null,
      }),


      showModal: false,
      awardedQuotations: {},
      submission_not_later_than: null,
      submission_not_later_than_with_format: '',
      action: '',
      award_body: '',
      rebid_body: '',
      reaward_body: '',
    };
  },

  watch: {
    'submission_not_later_than': function(value) {
        if (value) {
            this.submission_not_later_than_with_format = this.dateFormat(value);
        }
    },

    'form.type': function(value) {
      if(this.action == 'Create'){
        if (value === "Award") {
          this.form.body = this.award_body;
        }   
        else if (value === "Rebid") {
          this.form.body = this.rebid_body;
        }
        else if (value === "Re-award") {
          this.form.body = this.reaward_body;
        }
      }
       
    },

  },

  mounted() {
    // this.getDateSubmisionNotLaterThan();

  },

  methods: {
    show(type) {
      this.showModal = true;
      this.form.type = type;
      this.action = "Create";
      this.Content();
    },

    edit(data) {
      this.form.id = data.id;
      this.form.code = data.code;
      this.form.procurement_id = data.procurement_id;
      this.form.body = data.body;
      this.form.type = data.type;
      this.showModal = true;
      this.action = "Update";
    },

    hide() {
      this.showModal = false;
    },

    numberToWords(num) {
      if (!num || num === 0) return "ZERO";
      // ensure integer
      num = Math.floor(num);

      const ones = ["", "ONE", "TWO", "THREE", "FOUR", "FIVE", "SIX", "SEVEN", "EIGHT", "NINE"];
      const teens = ["ELEVEN", "TWELVE", "THIRTEEN", "FOURTEEN", "FIFTEEN", "SIXTEEN", "SEVENTEEN", "EIGHTEEN", "NINETEEN"];
      const tens = ["", "TEN", "TWENTY", "THIRTY", "FORTY", "FIFTY", "SIXTY", "SEVENTY", "EIGHTY", "NINETY"];
      const thousands = ["", "THOUSAND", "MILLION", "BILLION", "TRILLION"];

      function convertChunk(n) {
        let str = "";
        if (n >= 100) {
          str += ones[Math.floor(n / 100)] + " HUNDRED ";
          n %= 100;
        }
        if (n >= 11 && n <= 19) {
          str += teens[n - 11] + " ";
          return str;
        } else if (n >= 10) {
          str += tens[Math.floor(n / 10)] + " ";
          n %= 10;
        }
        if (n > 0) {
          str += ones[n] + " ";
        }
        return str;
      }

      let word = "";
      let chunkIndex = 0;

      while (num > 0) {
        let chunk = num % 1000;
        if (chunk > 0) {
          word = convertChunk(chunk) + thousands[chunkIndex] + " " + word;
        }
        num = Math.floor(num / 1000);
        chunkIndex++;
      }

      return word.trim();
    },


    dateFormat(dateString) {
      const date = new Date(dateString);
      const day = date.getDate();
      const month = date.toLocaleString('default', { month: 'long' });
      const year = date.getFullYear();
      const getOrdinalSuffix = (num) => {
        if (num >= 11 && num <= 13) return "th";
        const lastDigit = num % 10;
        if (lastDigit === 1) return "st";
        if (lastDigit === 2) return "nd";
        if (lastDigit === 3) return "rd";
        return "th";
      };
      const ordinalSuffix = getOrdinalSuffix(day);
      return `${day}<sup>${ordinalSuffix}</sup> day of ${month} ${year}`;
    },

    // get current date with format
    getFormattedDate() {
      const date = new Date();
      const day = date.getDate();
      const month = date.toLocaleString('default', { month: 'long' });
      const year = date.getFullYear();
      const getOrdinalSuffix = (num) => {
        if (num >= 11 && num <= 13) return "th";
        const lastDigit = num % 10;
        if (lastDigit === 1) return "st";
        if (lastDigit === 2) return "nd";
        if (lastDigit === 3) return "rd";
        return "th";
      };
      const ordinalSuffix = getOrdinalSuffix(day);
      return `${day}<sup>${ordinalSuffix}</sup> day of ${month} ${year}`;
    },

    Content() {
      const current_date = this.getFormattedDate();

      // Filter only awarded quotation
      const awardedQuotations = (this.procurement.quotations || []).filter(quotation =>
        (quotation.items  || []).some(item => item.status_id == 43 || item.status?.id == 43 && bid_item.bid_price != null)
      );

      const modeOfProcurement_names = (this.procurement.codes.map(code => code.procurement_code?.mode_of_procurement?.name).filter(Boolean).join(", ")).toUpperCase();
      const modeOfProcurement_ra_nos = (this.procurement.codes.map(code => code.procurement_code?.mode_of_procurement?.others).filter(Boolean).join(", ")).toUpperCase();
      const appTypes = this.procurement.codes.map(code => code.procurement_code?.app_type?.name).filter(Boolean).join(", ");
      const allocatedBudget =  this.procurement.codes.map(code => code.procurement_code?.allocated_budget).filter(Boolean).join(", ");
      const budgetInWords = this.numberToWords(allocatedBudget);
      

      // Filter rfq suppliers who bid same bid items
      const bidders = (this.procurement.quotations).filter(quotation =>
        (quotation.items  || []).some(item => item.status_id == 43 || item.status?.id == 43 && bid_item.bid_price != null)
      );

      const awardedSupplierNames = (awardedQuotations.map(quotation => quotation.supplier?.name).filter(Boolean).join(", ")).toUpperCase();
      const allPRSupplierNames = (this.procurement.quotations).map(quotation => quotation.supplier?.name).filter(Boolean).join(", ");

      // purchase request date formatted
      const prDate = new Date(this.procurement.date).toLocaleDateString('en-GB', {
        day: '2-digit',
        month: 'long',
        year: 'numeric'
      });

      const bidderCount = new Set(bidders).size;
      const calculatedBidderLabelCap = (bidderCount === 1) ? "SINGLE" : "LOWEST";
      const calculatedBidderLabelSmallCap = (bidderCount === 1) ? "single" : "lowest";

      const submission_not_later_than_with_format = this.dateFormat(this.procurement.quotations[0].submission_not_later_than);

      // roman converter
      function toRoman(num) {
        const romanMap = [
          { value: 1000, numeral: "M" },
          { value: 900, numeral: "CM" },
          { value: 500, numeral: "D" },
          { value: 400, numeral: "CD" },
          { value: 100, numeral: "C" },
          { value: 90, numeral: "XC" },
          { value: 50, numeral: "L" },
          { value: 40, numeral: "XL" },
          { value: 10, numeral: "X" },
          { value: 9, numeral: "IX" },
          { value: 5, numeral: "V" },
          { value: 4, numeral: "IV" },
          { value: 1, numeral: "I" }
        ];
        let result = "";
        for (const { value, numeral } of romanMap) {
          while (num >= value) {
            result += numeral;
            num -= value;
          }
        }
        return result;
      }

      let counter = 2;
      const awardedQuotationsList = awardedQuotations.map(quotation => {
        const filteredItems = (quotation.items).filter(item => item.status_id == 43 || item.status?.id == 43);
        if (filteredItems.length === 0) return "";
        const itemNumbers = filteredItems.map(item => item.item.item_no).join(", ");
        const totalPrice = filteredItems.reduce((sum, item) => {
          const bp = parseFloat(item.bid_price) || 0;
          const bq = parseFloat(item.item.item_quantity) || 0;
          return sum + bp * bq;
        }, 0);
        const roman = toRoman(counter++);
        return `
          <li style="text-align: justify;  margin-bottom: 1em; ">
            <b>${roman}.</b> To recommend to the Head of Department of Science and Technology
            Regional Office No. IX for her consideration and approval of the award
            of contract for <b>Item No. ${itemNumbers}</b> of the project entitled  <b>"${this.procurement.title.toUpperCase()}"</b>
            to the ${calculatedBidderLabelSmallCap} calculated and responsive bidder, <b>${quotation.supplier?.name}</b>,
            with the total contract amount of <b>Php${Number(totalPrice).toLocaleString()}</b> only.
          </li>
        `;
      }).join("");

      // === CORRECT total accumulation across awarded bids ===
      const AwardedBidTotalPrice = awardedQuotations.reduce((total, bid) => {
        const filteredBidDetails = (bid.bid_items || []).filter(bid_item => bid_item.status_id == 8 || bid_item.status?.id == 8);
        const totalPriceForBid = filteredBidDetails.reduce((sum, detail) => {
          const bp = parseFloat(detail.item_bid_price) || 0;
          const bq = parseFloat(detail.item_quantity) || 0;
          return sum + bp * bq;
        }, 0);
        return total + totalPriceForBid; // accumulate correctly
      }, 0);

      const total_amount_contract_in_words = this.numberToWords(AwardedBidTotalPrice);

      const awardedTableRows = awardedQuotations
        .map(bid => {
          const filteredBidItems = (bid.bid_items || []).filter(bid_item => bid_item.status_id == 8 || bid_item.status?.id == 8);
          if (filteredBidItems.length === 0) return null;
          const BidItemIds = filteredBidItems.map(bid_item => bid_item.item_no).join(", ");
          return `
            <tr>
              <td>${bid.supplier?.name}</td>
              <td>${BidItemIds}</td>
            </tr>
          `;
        })
        .filter(row => row !== null);

      const awardedTableHtml = bidderCount.length > 2
      ? `
        <b>WHEREAS</b>, after thorough evaluation on the technical specifications of the bidders, the BAC 
        determined the following:
        <table style="width: 100%; border-collapse: collapse;" border="1">
          <tr>
            <th>Supplier</th>
            <th>Item No.</th>
          </tr>
          ${awardedTableRows.join("")}
        </table>
      `
      : '';


      // ====== AWARD BODY ======
      this.award_body = `
        <div style=" font-size: 16px;">
          <p style="text-align: justify ;   text-align-last: center;  margin-bottom: 2em; ">
            <b>
              RECOMMENDING AWARD OF CONTRACT TO ${awardedSupplierNames}, AS THE ${calculatedBidderLabelCap} CALCULATED AND RESPONSIVE BIDDER FOR THE PROCUREMENT
              "${this.procurement.title.toUpperCase()}" UNDERTAKEN THROUGH SECTION 53.9 (${modeOfProcurement_names})
              OF THE REVISED IMPLEMENTING RULES AND REGULATIONS OF (${modeOfProcurement_ra_nos})
            </b>
          </p>
          <p style="text-align: justify;  margin-bottom: 1em; ">
            <b>WHEREAS</b>, the Regional Director, Ms. ${this.procurement.approved_by.profile.fullname}, approved the DOST-IX ${appTypes } for CY${new Date(this.procurement.date).getFullYear()} upon favorable recommendation of the Bids and Awards Committee;
          </p>

          <p style="text-align: justify;  margin-bottom: 1em; ">
            <b>WHEREAS</b>, the  ${ appTypes } (Annex "A") contains the procurement
            <i style="font-size:12px">"${this.procurement.title.toUpperCase()}"</i>
            with allocated budget of ${budgetInWords} (PHP ${Number(allocatedBudget || 0).toLocaleString()}),
            to be procured through Section 53.9 of the revised Implementing Rules and Regulations (IRR) of ${ modeOfProcurement_ra_nos} ;
          </p>

          <p style="text-align: justify; margin-bottom: 1em; ">
            <b>WHEREAS</b>, the BAC has duly received an approved purchase request for the procurement titled
            "${this.procurement.title.toUpperCase()}" to be bid per item. Detailed technical
            specifications pertaining to this procurement are meticulously outlined in
            PR no. ${this.procurement.code} dated ${prDate} (refer to Annex "B");
          </p>
           
          <p style="text-align: justify;  margin-bottom: 1em; ">
            <b>WHEREAS</b>, the BAC initiated the procurement through its secretariat through dissemination
            of the request for quotation to at least four suppliers of known qualifications in
            Zamboanga City to wit: ${allPRSupplierNames};
          </p>
           
          <p style="text-align: justify;  margin-bottom: 1em; ">
            <b>WHEREAS</b>, among the above-mentioned bidders, ${awardedSupplierNames} responded by submitting
            its price quotation to the BAC before opening of bids on ${ submission_not_later_than_with_format }.
          </p>
           
          ${awardedTableHtml}
          <p style="text-align: justify;  margin-bottom: 1em; ">
            <b>NOW, THEREFORE,</b> We the members of the Bids and Awards Committee, by virtue of the powers vested on Us by the Law, hereby RESOLVE as it hereby RESOLVED;
          </p>
  
          <p style="text-align: justify;  margin-bottom: 1em; margin-top: 20px; margin-left: 0 ">
            <ul style="list-style-type: none;">
              <li style="text-align: justify; margin-bottom: 1em; "><b>I. </b> To declare ${awardedSupplierNames} as the ${calculatedBidderLabelSmallCap} Calculated and Responsive Bidder of the procurement for <b>"${this.procurement.title.toUpperCase()}"</b>
              </li>
              ${awardedQuotationsList}
            </ul>
          </p>

          <p style="text-align: justify;  margin-bottom: 1em; ">
            <b>RESOLVED</b>, at the Department of Science and Technology Regional Office IX, Pettit Barracks, Zamboanga City this ${current_date}.
          </p>

        </div>
      `;

      // ====== REBID BODY (uses awarded total price in words) ======
      this.rebid_body = `
        <div style=" font-size: 16px;">
          <p style="text-align: center;"><b>DECLARATION OF FAILURE OF BIDDING FOR THE PROCUREMENT "${this.procurement.title.toUpperCase()}"</b></p>
          <p style="text-align: justify"><b>WHEREAS</b>, the Regional Director, Mr. ${this.procurement.approved_by}, approved the DOST-IX 2nd Supplemental Annual Procurement Plan for CY${new Date().getFullYear()} upon favorable recommendation of the Bids and Awards Committee;</p>
          <p style="text-align: justify">
            <b>WHEREAS</b>, the 2nd Supplemental Annual Procurement Plan (Annex "A") contains the procurement
            <i style="font-size:12px">"${this.procurement.title.toUpperCase()}"</i>
            with allocated budget of ${budgetInWords} (PHP ${Number(this.procurement.pap_codes?.[0]?.pap_code?.allocated_budget || 0).toLocaleString()}),
            to be procured through Section 53.9 of the revised IRR of ${this.procurement.pap_codes?.[0]?.pap_code?.mode_of_procurement?.republic_act_number };
          </p>
          <p style="text-align: justify">
            <b>WHEREAS</b>, the BAC received an approved Purchase Request (PR No. ${this.procurement.code}, dated ${prDate})
            from the end-user for the <b>"${this.procurement.title.toUpperCase()}"</b>, with a total Approved Budget for the Contract (ABC) of
            <b>${total_amount_contract_in_words}</b> (PHP ${Number(AwardedBidTotalPrice).toLocaleString()}), to be procured on a per-item basis and following detailed technical specifications (Annex “B”);
          </p>
          <p style="text-align: justify">
            <b>WHEREAS</b>, the BAC, in full compliance with ${this.procurement.pap_codes?.[0]?.pap_code?.mode_of_procurement?.republic_act_number } and its IRR, initiated the procurement process by issuing Requests for Quotation (RFQs) to at least three (3) suppliers with established qualifications, namely: ${allPRSupplierNames};
          </p>
          <p style="text-align: justify">
            <b>WHEREAS</b>, upon opening and evaluation of the bid documents, the BAC found that ${awardedSupplierNames} met the legal requirements but failed to comply with the technical specifications; thus, no responsive and eligible bid was identified;
          </p>
          <p style="text-align: justify">
            <b>NOW, THEREFORE,</b> We the members of the Bids and Awards Committee, by virtue of the powers vested on Us by the Law, hereby RESOLVE as it hereby RESOLVED;
          </p>
          <p style="text-align: justify; margin-top:-20px">
            <ul style="list-style-type: none; padding-left: 0;">
              <li style="text-align: justify"><b>I.</b> To declare a failure of bidding for the project <b>"${this.procurement.title.toUpperCase()}"</b> due to the absence of a technically compliant bid;</li>
              <li style="text-align: justify"><b>II.</b> To recommend to the Head of the Procuring Entity (HOPE), the Regional Director of DOST-IX, the immediate review and evaluation of the causes of the bidding failure and to undertake appropriate measures or revisions in accordance with the IRR of ${this.procurement.pap_codes?.[0]?.pap_code?.mode_of_procurement?.republic_act_number };</li>
              <li style="text-align: justify"><b>III.</b> To recommend the conduct of a re-bidding for the same project, subject to the necessary adjustments and compliance with applicable procurement rules and procedures.</li>
            </ul>
          </p>
          <p style="text-align: justify"><b>RESOLVED</b>, at the Department of Science and Technology Regional Office IX, Pettit Barracks, Zamboanga City this ${current_date}.</p>
        </div>
      `;
      
      // reaward_body kept similar to award_body but with wording "SECOND LOWEST" — you can adapt as needed
      this.reaward_body = this.award_body.replace('RECOMMENDING AWARD OF CONTRACT TO', 'RECOMMENDING AWARD OF CONTRACT TO'); // minimal placeholder
    },

    submit(data) {
      if(this.action == 'Create'){
        this.form.post('/faims/notice-of-awards');
        this.hide();
  
      }
      else{
        this.form.option == 'update';
        this.form.put('/faims/notice-of-awards/'+data.id);
        this.hide();
  
      }
     
    },
  }
};
</script>
