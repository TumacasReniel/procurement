<template>
  <b-modal
    v-model="showModal"
    header-class="p-3"
    :title="editable ? 'Update BAC Resolution' : 'Create BAC Resolution'"
    size="xl"
    centered
    no-close-on-backdrop
    body-class="modal-body-scroll"
  >
    <b-row>
      <b-col
        :class="['transition-all', isRightCollapsed ? 'col-md-11' : 'col-md-8']"
        style="transition: all 0.3s ease"
      >
        <form class="customform">
          <BRow>
            <BCol class="mt-2" v-if="form.code">
              <InputLabel value="BAC Resolution Number" />
              <TextInput
                v-model="form.code"
                class="form-control"
                :light="true"
                readonly
              />
            </BCol>
            <BCol class="mt-2">
              <InputLabel value="Type" />
              <Multiselect
                :options="['Award', 'Re-award', 'Rebid']"
                v-model="form.type"
                :searchable="true"
                label="name"
                disabled
                placeholder="Select BAC Resolution Type"
              />
            </BCol>

            <BCol class="mt-2">
              <InputLabel value="Procurement Number" />
              <TextInput
                v-model="procurement.code"
                class="form-control"
                :light="true"
                disabled
              />
            </BCol>

            <BCol lg="12" class="mt-2">
              <InputLabel value="Content" />
              <div v-if="contentPages.length > 1" class="mb-3">
                <b-pagination
                  v-model="currentPage"
                  :total-rows="contentPages.length"
                  :per-page="1"
                  class="mb-0"
                ></b-pagination>
              </div>
              <CustomEditor v-model="currentPageContent" />
              <div v-if="contentPages.length > 1" class="mt-3 text-center">
                <small class="text-muted"
                  >Page {{ currentPage }} of {{ contentPages.length }}</small
                >
              </div>
            </BCol>
          </BRow>
        </form>
      </b-col>
      <b-col
        :class="['transition-all', isRightCollapsed ? 'col-md-1' : 'col-md-4']"
        style="transition: all 0.3s ease"
      >
        <div
          class="card shadow-lg border-0"
          style="
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 15px;
            height: 100vh;
          "
        >
          <div
            class="card-header bg-gradient-primary border-0 d-flex align-items-center justify-content-between"
            style="border-radius: 15px 15px 0 0 !important; padding: 1rem"
          >
            <div v-if="!isRightCollapsed">
              <h6 class="card-title mb-0 text-dark">
                <span class="position-relative me-2">
                  <i class="ri-file-list-line"></i>
                  <span
                    v-if="logsCount > 0"
                    class="badge bg-danger position-absolute top-0 start-100 translate-middle"
                    style="font-size: 0.7rem; padding: 0.2rem 0.4rem"
                    >{{ logsCount }}</span
                  >
                </span>
                Activity Logs
              </h6>
            </div>
            <button
              @click="toggleRightSidebar"
              class="btn btn-sm btn-light rounded-circle p-2 ms-2"
              style="width: 40px; height: 40px"
            >
              <i
                :class="isRightCollapsed ? 'ri-arrow-left-line' : 'ri-arrow-right-line'"
                class="text-primary fs-6"
              ></i>
            </button>
          </div>
          <div
            v-if="!isRightCollapsed"
            class="card-body p-0"
            style="height: 100vh; overflow: auto; border-radius: 0 0 15px 15px"
          >
            <div class="p-3">
              <div
                class="nav nav-tabs nav-justified mb-3"
                style="
                  border-bottom: 2px solid #007bff;
                  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
                  border-radius: 10px;
                  padding: 5px;
                  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
                "
              >
                <button
                  :class="['nav-link', activeRightTab === 1 ? 'active' : '']"
                  @click="showRightTab(1)"
                >
                  <i class="ri-file-list-line me-1"></i>Logs
                </button>
              </div>
              <div v-if="activeRightTab === 1" class="logs-section">
                <div v-if="modalLogs && modalLogs.length > 0" class="logs-list">
                  <div
                    v-for="log in modalLogs"
                    :key="log.id"
                    class="log-item p-3 mb-3"
                    style="
                      background: #f8f9fa;
                      border-left: 4px solid #007bff;
                      transition: all 0.3s ease;
                    "
                  >
                    <div class="d-flex justify-content-between align-items-start">
                      <div class="flex-grow-1">
                        <div class="log-description mb-2">
                          <strong>{{ log.description }}</strong>
                        </div>
                        <div class="log-details small text-muted">
                          <span v-if="log.causer">
                            <i class="ri-user-line me-1"></i
                            >{{ log.causer.profile?.fullname || log.causer.name }}
                          </span>
                          <span class="ms-2">
                            <i class="ri-time-line me-1"></i
                            >{{ formatDate(log.created_at) }}
                          </span>
                        </div>
                        <div
                          v-if="log.changes && Object.keys(log.changes).length > 0"
                          class="log-changes mt-2"
                          style="
                            background: #ffffff;
                            padding: 0.5rem;
                            border-radius: 4px;
                            border: 1px solid #dee2e6;
                          "
                        >
                          <div class="small fw-bold text-muted mb-1">Changes:</div>
                          <div
                            v-for="(value, key) in log.changes"
                            :key="key"
                            class="change-item"
                            style="
                              display: flex;
                              justify-content: space-between;
                              margin-bottom: 0.25rem;
                            "
                          >
                            <span
                              class="change-key"
                              style="font-weight: 600; color: #495057"
                              >{{ key }}:</span
                            >
                            <span
                              class="change-value"
                              style="
                                color: #007bff;
                                font-family: monospace;
                                font-size: 0.85rem;
                              "
                              >{{ value }}</span
                            >
                          </div>
                        </div>
                      </div>
                      <div class="log-icon">
                        <i class="ri-file-list-line fs-4"></i>
                      </div>
                    </div>
                  </div>
                </div>
                <div v-else class="text-center text-muted mt-3">
                  <i class="ri-file-list-line fs-1"></i>
                  <p class="mt-2">No logs available</p>
                  <small>Activity logs will appear here</small>
                </div>
              </div>
            </div>
          </div>
          <div
            v-else
            class="card-body p-0"
            style="height: 100vh; overflow: auto; border-radius: 0 0 15px 15px"
          >
            <div class="p-2 d-flex flex-column align-items-center">
              <div class="position-relative">
                <button
                  :class="[
                    'nav-link mb-2 rounded-pill border-0 transition-all p-2',
                    activeRightTab === 1
                      ? 'bg-primary text-white shadow-sm'
                      : 'bg-white text-dark hover-bg-light',
                  ]"
                  @click="showRightTab(1)"
                  style="transition: all 0.3s ease; width: 50px; height: 50px"
                  v-b-tooltip.hover
                  title="Logs"
                >
                  <i class="ri-file-list-line fs-5"></i>
                </button>
                <span
                  v-if="logsCount > 0"
                  class="badge bg-danger position-absolute"
                  style="
                    font-size: 0.9rem;
                    padding: 0.2rem 0.4rem;
                    font-weight: bold;
                    top: -5px;
                    right: -5px;
                  "
                  >{{ logsCount }}</span
                >
              </div>
            </div>
          </div>
        </div>
      </b-col>
    </b-row>

    <template v-slot:footer>
      <b-button @click="hide()" variant="light" block>Cancel</b-button>
      <b-button @click="submit(form)" variant="success" v-if="editable" block
        >Update</b-button
      >
      <b-button @click="submit(form)" variant="success" v-else block>Save</b-button>
    </template>
  </b-modal>
</template>

<script>
import { useForm } from "@inertiajs/vue3";
import Multiselect from "@vueform/multiselect";
import InputError from "@/Shared/Components/Forms/InputError.vue";
import InputLabel from "@/Shared/Components/Forms/InputLabel.vue";
import TextInput from "@/Shared/Components/Forms/TextInput.vue";
import axios from "axios";
import CustomEditor from "@/Shared/Components/Forms/CustomEditor.vue";

export default {
  components: {
    InputError,
    InputLabel,
    TextInput,
    Multiselect,
    CustomEditor,
  },
  props: ["procurement", "logs"],
  data() {
    return {
      isEditorReady: false,
      form: useForm({
        id: null,
        code: null,
        procurement_id: this.procurement.id,
        body: "",
        type: null,
        option: null,
      }),

      showModal: false,
      awardedQuotations: {},
      submission_not_later_than: null,
      submission_not_later_than_with_format: "",
      editable: false,
      award_body: "",
      rebid_body: "",
      reaward_body: "",
      activeRightTab: 1,
      isRightCollapsed: true,
      modalLogs: [],
      currentPage: 1,
      contentPages: [],
    };
  },

  watch: {
    submission_not_later_than: function (value) {
      if (value) {
        this.submission_not_later_than_with_format = this.dateFormat(value);
      }
    },

    "form.type": function (value) {
      if (!this.editable) {
        if (value === "Award") {
          this.form.body = this.award_body;
        } else if (value === "Rebid") {
          this.form.body = this.rebid_body;
        } else if (value === "Re-award") {
          this.form.body = this.reaward_body;
        }
      }
    },

    "form.body": function (value) {
      this.splitContentIntoPages(value);
    },
  },

  computed: {
    logsCount() {
      return this.modalLogs ? this.modalLogs.length : 0;
    },
    currentPageContent: {
      get() {
        return this.contentPages[this.currentPage - 1] || this.form.body;
      },
      set(value) {
        if (this.contentPages.length > 1) {
          this.contentPages[this.currentPage - 1] = value;
          this.form.body = this.contentPages.join("\n\n---PAGE BREAK---\n\n");
        } else {
          this.form.body = value;
        }
      },
    },
  },

  mounted() {
    // this.getDateSubmisionNotLaterThan();
    this.isRightCollapsed = localStorage.getItem("isRightCollapsed") === "true" || true;
  },

  methods: {
    show(type) {
      this.showModal = true;
      this.form.type = type;
      this.editable = false;
      this.modalLogs = this.logs;
      this.Content();
    },

    edit(data) {
      this.form.id = data.id;
      this.form.code = data.code;
      this.form.procurement_id = data.procurement_id;
      this.form.body = data.body;
      this.form.type = data.type;
      this.showModal = true;
      this.editable = true;
      this.fetchLogsForBACResolution(data.id);
    },

    hide() {
      this.showModal = false;
    },

    numberToWords(num) {
      if (!num || num === 0) return "ZERO";
      // ensure integer
      num = Math.floor(num);

      const ones = [
        "",
        "ONE",
        "TWO",
        "THREE",
        "FOUR",
        "FIVE",
        "SIX",
        "SEVEN",
        "EIGHT",
        "NINE",
      ];
      const teens = [
        "ELEVEN",
        "TWELVE",
        "THIRTEEN",
        "FOURTEEN",
        "FIFTEEN",
        "SIXTEEN",
        "SEVENTEEN",
        "EIGHTEEN",
        "NINETEEN",
      ];
      const tens = [
        "",
        "TEN",
        "TWENTY",
        "THIRTY",
        "FORTY",
        "FIFTY",
        "SIXTY",
        "SEVENTY",
        "EIGHTY",
        "NINETY",
      ];
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
      const month = date.toLocaleString("default", { month: "long" });
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
      const month = date.toLocaleString("default", { month: "long" });
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

    // roman converter
    toRoman(num) {
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
        { value: 1, numeral: "I" },
      ];
      let result = "";
      for (const { value, numeral } of romanMap) {
        while (num >= value) {
          result += numeral;
          num -= value;
        }
      }
      return result;
    },

    // ordinal number converter
    toOrdinal(n) {
      const v = n % 100;
      let suffix = "th";

      if (v < 11 || v > 13) {
        switch (n % 10) {
          case 1:
            suffix = "st";
            break;
          case 2:
            suffix = "nd";
            break;
          case 3:
            suffix = "rd";
            break;
        }
      }

      return `${n}<sup>${suffix}</sup>`;
    },

    Content() {
      const current_date = this.getFormattedDate();

      const mode_of_procurement_names = this.procurement.codes
        .map((code) => code.procurement_code?.mode_of_procurement?.name)
        .filter(Boolean)
        .join(", ")
        .toUpperCase();
      const mode_of_procurement_ra_nos = this.procurement.codes
        .map((code) => code.procurement_code?.mode_of_procurement?.others)
        .filter(Boolean)
        .join(", ")
        .toUpperCase();
      const app_types = this.procurement.codes
        .map((code) => code.procurement_code?.app_type?.name)
        .filter(Boolean)
        .join(", ");
      const allocated_budget = this.procurement.codes
        .map((code) => code.procurement_code?.allocated_budget)
        .filter(Boolean)
        .join(", ");
      const budget_in_words = this.numberToWords(allocated_budget);

      const all_pr_supplier_names = this.procurement.quotations
        .map((quotation) => quotation.supplier?.name)
        .filter(Boolean)
        .join(", ");

      // Filter bidders
      const bidders = this.procurement.quotations.filter((quotation) =>
        quotation.items.some((item) => item.bid_price != null && item.is_rebid == 0)
      );

      // purchase request date formatted
      const pr_date = new Date(this.procurement.date).toLocaleDateString("en-GB", {
        day: "2-digit",
        month: "long",
        year: "numeric",
      });

      const submission_not_later_than_with_format = this.dateFormat(
        this.procurement.quotations[0].submission_not_later_than
      );

      this.awardContent(
        current_date,
        mode_of_procurement_names,
        mode_of_procurement_ra_nos,
        app_types,
        allocated_budget,
        budget_in_words,
        all_pr_supplier_names,
        pr_date,
        submission_not_later_than_with_format,
        bidders
      );

      this.reawardContent(
        current_date,
        mode_of_procurement_names,
        mode_of_procurement_ra_nos,
        app_types,
        allocated_budget,
        budget_in_words,
        all_pr_supplier_names,
        pr_date,
        submission_not_later_than_with_format,
        bidders
      );

      this.rebidContent(
        current_date,
        mode_of_procurement_names,
        mode_of_procurement_ra_nos,
        app_types,
        allocated_budget,
        budget_in_words,
        all_pr_supplier_names,
        pr_date,
        submission_not_later_than_with_format,
        bidders
      );
    },

    awardContent(
      current_date,
      mode_of_procurement_names,
      mode_of_procurement_ra_nos,
      app_types,
      allocated_budget,
      budget_in_words,
      all_pr_supplier_names,
      pr_date,
      submission_not_later_than_with_format,
      bidders
    ) {
      // Filter only awarded quotation
      const awarded_quotations = this.procurement.quotations.filter((quotation) =>
        quotation.items.some(
          (item) => item.status.name === 'Awarded' && item.bid_price != null && item.is_rebid == 0
        )
      );

      const awarded_supplier_names = awarded_quotations
        .map((quotation) => quotation.supplier?.name)
        .filter(Boolean)
        .join(", ")
        .toUpperCase();
      const bidder_count = new Set(bidders).size;

      const bid_type_label_cap = bidder_count === 1 ? "SINGLE" : "LOWEST";
      const bid_type_label_small_cap = bidder_count === 1 ? "single" : "lowest";

      let counter = 2;
      const awarded_quotations_list = awarded_quotations
        .map((quotation) => {
          const filtered_items = quotation.items.filter(
            (item) => item.status.name === 'Awarded' && item.is_rebid == 0
          );
          if (filtered_items.length === 0) return "";
          const item_numbers = filtered_items.map((item) => item.item.item_no).join(", ");
          const total_price = filtered_items.reduce((sum, item) => {
            const bp = parseFloat(item.bid_price) || 0;
            const bq = parseFloat(item.item.item_quantity) || 0;
            return sum + bp * bq;
          }, 0);
          const roman = this.toRoman(counter++);
          return `
          <li style="text-align: justify;  margin-bottom: 1em; ">
            <b>${roman}.</b> To recommend to the Head of Department of Science and Technology
            Regional Office No. IX for her consideration and approval of the award
            of contract for <b>Item No. ${item_numbers}</b> of the project entitled  <b>"${this.procurement.title.toUpperCase()}"</b>
            to the ${bid_type_label_small_cap} calculated and responsive bidder, <b>${
            quotation.supplier?.name
          }</b>,
            with the total contract amount of <b>Php${Number(
              total_price
            ).toLocaleString()}</b> only.
          </li>
        `;
        })
        .join("");

      // === CORRECT total accumulation across awarded bids ===
      const award_bid_total_price = awarded_quotations.reduce((total, bid) => {
        const filtered_items = (bid.bid_items || []).filter(
          (item) => item.status.name = 'Awarded' && item.is_rebid == 0
        );
        const total_price = filtered_items.reduce((sum, quotation) => {
          const bp = parseFloat(quotation.bid_price) || 0;
          const bq = parseFloat(quotation.item_quantity) || 0;
          return sum + bp * bq;
        }, 0);
        return total + total_price; // accumulate correctly
      }, 0);

      const total_amount_contract_in_words = this.numberToWords(award_bid_total_price);

      const awarded_table_rows = awarded_quotations
        .map((quotation) => {
          const filtered_items = quotation.items.filter(
            (item) => item.status.name === 'Awarded' && item.is_rebid == 1
          );
          if (filtered_items.length === 0) return null;
          const item_ids = filtered_items.map((bid_item) => bid_item.item_no).join(", ");
          return `
            <tr>
              <td>${quotation.supplier?.name}</td>
              <td>${item_ids}</td>
            </tr>
          `;
        })
        .filter((row) => row !== null);

      const awarded_table_html =
        bidder_count.length > 2
          ? `
        <b>WHEREAS</b>, after thorough evaluation on the technical specifications of the bidders, the BAC
        determined the following:
        <table style="width: 100%; border-collapse: collapse;" border="1">
          <tr>
            <th>Supplier</th>
            <th>Item No.</th>
          </tr>
          ${awarded_table_rows.join("")}
        </table>
      `
          : "";

      // ====== AWARD BODY ======
      this.award_body = `
        <div style=" font-size: 16px;">
          <p style="text-align: justify ;   text-align-last: center;  margin-bottom: 2em; ">
            <b>
              RECOMMENDING AWARD OF CONTRACT TO ${awarded_supplier_names}, AS THE ${bid_type_label_cap} CALCULATED AND RESPONSIVE BIDDER FOR THE PROCUREMENT
              "${this.procurement.title.toUpperCase()}" UNDERTAKEN THROUGH SECTION 53.9 (${mode_of_procurement_names})
              OF THE REVISED IMPLEMENTING RULES AND REGULATIONS OF (${mode_of_procurement_ra_nos})
            </b>
          </p>
          <p style="text-align: justify;  margin-bottom: 1em; ">
            <b>WHEREAS</b>, the Regional Director, Ms. ${
              this.procurement.approved_by.profile.fullname
            }, approved the DOST-IX ${app_types} for CY${new Date(
        this.procurement.date
      ).getFullYear()} upon favorable recommendation of the Bids and Awards Committee;
          </p>

          <p style="text-align: justify;  margin-bottom: 1em; ">
            <b>WHEREAS</b>, the  ${app_types} (Annex "A") contains the procurement
            <i style="font-size:12px">"${this.procurement.title.toUpperCase()}"</i>
            with allocated budget of ${budget_in_words} (PHP ${Number(
        allocated_budget || 0
      ).toLocaleString()}),
            to be procured through Section 53.9 of the revised Implementing Rules and Regulations (IRR) of ${mode_of_procurement_ra_nos} ;
          </p>

          <p style="text-align: justify; margin-bottom: 1em; ">
            <b>WHEREAS</b>, the BAC has duly received an approved purchase request for the procurement titled
            "${this.procurement.title.toUpperCase()}" to be bid per item. Detailed technical
            specifications pertaining to this procurement are meticulously outlined in
            PR no. ${this.procurement.code} dated ${pr_date} (refer to Annex "B");
          </p>

          <p style="text-align: justify;  margin-bottom: 1em; ">
            <b>WHEREAS</b>, the BAC initiated the procurement through its secretariat through dissemination
            of the request for quotation to at least four suppliers of known qualifications in
            Zamboanga City to wit: ${all_pr_supplier_names};
          </p>

          <p style="text-align: justify;  margin-bottom: 1em; ">
            <b>WHEREAS</b>, among the above-mentioned bidders, ${awarded_supplier_names} responded by submitting
            its price quotation to the BAC before opening of bids on ${submission_not_later_than_with_format}.
          </p>

          ${awarded_table_html}
          <p style="text-align: justify;  margin-bottom: 1em; ">
            <b>NOW, THEREFORE,</b> We the members of the Bids and Awards Committee, by virtue of the powers vested on Us by the Law, hereby RESOLVE as it hereby RESOLVED;
          </p>

          <p style="text-align: justify;  margin-bottom: 1em; margin-top: 20px; margin-left: 0 ">
            <ul style="list-style-type: none;">
              <li style="text-align: justify; margin-bottom: 1em; "><b>I. </b> To declare ${awarded_supplier_names} as the ${bid_type_label_small_cap} Calculated and Responsive Bidder of the procurement for <b>"${this.procurement.title.toUpperCase()}"</b>
              </li>
              ${awarded_quotations_list}
            </ul>
          </p>

          <p style="text-align: justify;  margin-bottom: 1em; ">
            <b>RESOLVED</b>, at the Department of Science and Technology Regional Office IX, Pettit Barracks, Zamboanga City this ${current_date}.
          </p>

        </div>
      `;
    },

    reawardContent(
      current_date,
      mode_of_procurement_names,
      mode_of_procurement_ra_nos,
      app_types,
      allocated_budget,
      budget_in_words,
      all_pr_supplier_names,
      pr_date,
      submission_not_later_than_with_format,
      bidders
    ) {
      // Filter rfq suppliers who bid same bid items
      const reawarded_quotations = this.procurement.quotations.filter((quotation) =>
        quotation.items.some((item) => item.status.name = 'Awarded' && item.bid_price != null)
      );

      const bidder_count = new Set(bidders).size;

      const bid_type_cap = bidder_count === 1 ? "SINGLE" : "LOWEST";
      const bid_type_small_cap = bidder_count === 1 ? "single" : "lowest";

      let counter = 2;
      const reawarded_quotations_list = reawarded_quotations
        .map((quotation) => {
          const filtered_items = quotation.items.filter((item) => item.status.name === 'Awarded');
          if (filtered_items.length === 0) return "";
          const item_numbers = filtered_items.map((item) => item.item.item_no).join(", ");
          const total_price = filtered_items.reduce((sum, item) => {
            const bp = parseFloat(item.bid_price);
            const bq = parseFloat(item.item.item_quantity);
            return sum + bp * bq;
          }, 0);
          const roman = this.toRoman(counter++);
          return `
          <li style="text-align: justify;  margin-bottom: 1em; ">
            <b>${roman}.</b> To recommend to the Head of Department of Science and Technology
            Regional Office No. IX for her consideration and approval of the award
            of contract for <b>Item No. ${item_numbers}</b> of the project entitled  <b>"${this.procurement.title.toUpperCase()}"</b>
            to the ${bid_type_small_cap} calculated and responsive bidder, <b>${
            quotation.supplier?.name
          }</b>,
            with the total contract amount of <b>Php${Number(
              total_price
            ).toLocaleString()}</b> only.
          </li>
        `;
        })
        .join("");

      const reawarded_supplier_names = reawarded_quotations
        .map((quotation) => quotation.supplier?.name)
        .filter(Boolean)
        .join(", ")
        .toUpperCase();

      // === CORRECT total accumulation across reawarded bids ===
      const reawarded_bid_total_price = reawarded_quotations.reduce((total, bid) => {
        const filtered_bid_details = (bid.bid_items || []).filter(
          (bid_item) => bid_item.status.name == "Awarded"
        );
        const total_price_for_bid = filtered_bid_details.reduce((sum, detail) => {
          const bp = parseFloat(detail.item_bid_price) || 0;
          const bq = parseFloat(detail.item_quantity) || 0;
          return sum + bp * bq;
        }, 0);
        return total + total_price_for_bid; // accumulate correctly
      }, 0);

      const reaward_total_amount_contract_in_words = this.numberToWords(
        reawarded_bid_total_price
      );

      const reawarded_table_rows = reawarded_quotations
        .map((quotation) => {
          const filtered_items = (quotation.items || []).filter(
            (item) => item.status.name = 'Awarded'
          );
          if (filtered_items.length === 0) return null;
          const item_ids = filtered_items.map((item) => item.item.item_no).join(", ");
          return `
            <tr>
              <td>${quotation.supplier?.name}</td>
              <td>${item_ids}</td>
            </tr>
          `;
        })
        .filter((row) => row !== null);

      const reawarded_table_html =
        bidder_count.length > 2
          ? `
        <b>WHEREAS</b>, after thorough evaluation on the technical specifications of the bidders, the BAC
        determined the following:
        <table style="width: 100%; border-collapse: collapse;" border="1">
          <tr>
            <th>Supplier</th>
            <th>Item No.</th>
          </tr>
          ${reawarded_table_rows.join("")}
        </table>
      `
          : "";

      // ====== REAWARD BODY ======
      this.reaward_body = `
        <div style=" font-size: 16px;">
          <p style="text-align: justify ;   text-align-last: center;  margin-bottom: 2em; ">
            <b>
              RECOMMENDING FOR RE-AWARD OF CONTRACT TO ${reawarded_supplier_names}, AS THE ${this.toOrdinal(
        this.procurement.reawarded_count + 1
      )} ${bid_type_cap} CALCULATED AND RESPONSIVE BIDDER FOR THE PROCUREMENT
              "${this.procurement.title.toUpperCase()}" UNDERTAKEN THROUGH SECTION 53.9 (${mode_of_procurement_names})
              OF THE REVISED IMPLEMENTING RULES AND REGULATIONS OF (${mode_of_procurement_ra_nos})
            </b>
          </p>
          <p style="text-align: justify;  margin-bottom: 1em; ">
            <b>WHEREAS</b>, the Regional Director, Ms. ${
              this.procurement.approved_by.profile.fullname
            }, approved the DOST-IX ${app_types} for CY${new Date(
        this.procurement.date
      ).getFullYear()} upon favorable recommendation of the Bids and Awards Committee;
          </p>

          <p style="text-align: justify;  margin-bottom: 1em; ">
            <b>WHEREAS</b>, the  ${app_types} (Annex "A") contains the procurement
            <i style="font-size:12px">"${this.procurement.title.toUpperCase()}"</i>
            with allocated budget of ${budget_in_words} (PHP ${Number(
        allocated_budget
      ).toLocaleString()}),
            to be procured through Section 53.9 of the revised Implementing Rules and Regulations (IRR) of ${mode_of_procurement_ra_nos} ;
          </p>

          <p style="text-align: justify; margin-bottom: 1em; ">
            <b>WHEREAS</b>, the BAC has duly received an approved purchase request for the procurement titled
            "${this.procurement.title.toUpperCase()}" to be bid per item. Detailed technical
            specifications pertaining to this procurement are meticulously outlined in
            PR no. ${this.procurement.code} dated ${pr_date} (refer to Annex "B");
          </p>

          <p style="text-align: justify;  margin-bottom: 1em; ">
            <b>WHEREAS</b>, the BAC initiated the procurement through its secretariat through dissemination
            of the request for quotation to at least four suppliers of known qualifications in
            Zamboanga City to wit: ${all_pr_supplier_names};
          </p>

          <p style="text-align: justify;  margin-bottom: 1em; ">
            <b>WHEREAS</b>, among the above-mentioned bidders, ${reawarded_supplier_names} responded by submitting
            its price quotation to the BAC before opening of bids on ${submission_not_later_than_with_format}.
          </p>

          ${reawarded_table_html}
          <p style="text-align: justify;  margin-bottom: 1em; ">
            <b>NOW, THEREFORE,</b> We the members of the Bids and Awards Committee, by virtue of the powers vested on Us by the Law, hereby RESOLVE as it hereby RESOLVED;
          </p>

          <p style="text-align: justify;  margin-bottom: 1em; margin-top: 20px; margin-left: 0 ">
            <ul style="list-style-type: none;">
              <li style="text-align: justify; margin-bottom: 1em; "><b>I. </b> To declare ${reawarded_supplier_names} as the ${bid_type_small_cap} Calculated and Responsive Bidder of the procurement for
                <b>"${this.procurement.title.toUpperCase()}"</b>
              </li>
              ${reawarded_quotations_list}
            </ul>
          </p>

          <p style="text-align: justify;  margin-bottom: 1em; ">
            <b>RESOLVED</b>, at the Department of Science and Technology Regional Office IX, Pettit Barracks, Zamboanga City this ${current_date}.
          </p>

        </div>
      `;
    },

    rebidContent(
      current_date,
      mode_of_procurement_names,
      mode_of_procurement_ra_nos,
      app_types,
      allocated_budget,
      budget_in_words,
      all_pr_supplier_names,
      pr_date,
      submission_not_later_than_with_format,
      bidders
    ) {
      // Filter only awarded quotation
      const awarded_quotations = this.procurement.quotations.filter((quotation) =>
        quotation.items.some(
          (item) => (item.status.name === 'Awarded' && item.bid_price != null) || item.is_rebid == 0
        )
      );

      const awarded_supplier_names = awarded_quotations
        .map((quotation) => quotation.supplier?.name)
        .filter(Boolean)
        .join(", ")
        .toUpperCase();
      const bidder_count = new Set(bidders).size;
      const bid_type_label_cap = bidder_count === 1 ? "SINGLE" : "LOWEST";
      const bid_type_label_small_cap = bidder_count === 1 ? "single" : "lowest";

      let counter = 2;
      const awarded_quotations_list = awarded_quotations
        .map((quotation) => {
          const filtered_items = quotation.items.filter(
            (item) => item.status.name = 'Awarded' || item.is_rebid == 0
          );
          if (filtered_items.length === 0) return "";
          const item_numbers = filtered_items.map((item) => item.item.item_no).join(", ");
          const total_price = filtered_items.reduce((sum, item) => {
            const bp = parseFloat(item.bid_price) || 0;
            const bq = parseFloat(item.item.item_quantity) || 0;
            return sum + bp * bq;
          }, 0);
          const roman = this.toRoman(counter++);
          return `
          <li style="text-align: justify;  margin-bottom: 1em; ">
            <b>${roman}.</b> To recommend to the Head of Department of Science and Technology
            Regional Office No. IX for her consideration and approval of the award
            of contract for <b>Item No. ${item_numbers}</b> of the project entitled  <b>"${this.procurement.title.toUpperCase()}"</b>
            to the ${bid_type_label_small_cap} calculated and responsive bidder, <b>${
            quotation.supplier?.name
          }</b>,
            with the total contract amount of <b>Php${Number(
              total_price
            ).toLocaleString()}</b> only.
          </li>
        `;
        })
        .join("");

      // === CORRECT total accumulation across awarded bids ===
      const award_bid_total_price = awarded_quotations.reduce((total, quotation) => {
        const filtered_items = quotation.items.filter(
          (item) => item.status.name === 'Awarded' || item.is_rebid == 0
        );
        const total_price = filtered_items.reduce((sum, item) => {
          const bp = parseFloat(item.bid_price) || 0;
          const bq = parseFloat(item.item.item_quantity) || 0;
          return sum + bp * bq;
        }, 0);
        return total + total_price; // accumulate correctly
      }, 0);

      const total_amount_contract_in_words = this.numberToWords(award_bid_total_price);

      // ====== REBID BODY ======
      this.rebid_body = `
        <div style=" font-size: 16px;">
          <p style="text-align: center;"><b>DECLARATION OF FAILURE OF BIDDING FOR THE PROCUREMENT "${this.procurement.title.toUpperCase()}"</b></p>
          <p style="text-align: justify"><b>WHEREAS</b>, the Regional Director, Ms. ${
            this.procurement.approved_by.profile.fullname
          }, approved the DOST-IX 2nd Supplemental Annual Procurement Plan for CY${new Date().getFullYear()} upon favorable recommendation of the Bids and Awards Committee;</p>
          <p style="text-align: justify">
            <b>WHEREAS</b>, the ${app_types} (Annex "A") contains the procurement
            <i style="font-size:12px">"${this.procurement.title.toUpperCase()}"</i>
            with allocated budget of ${budget_in_words} (PHP ${Number(
        allocated_budget
      ).toLocaleString()}),
            to be procured through Section 53.9 of the revised IRR of ${mode_of_procurement_ra_nos};
          </p>
          <p style="text-align: justify">
            <b>WHEREAS</b>, the BAC received an approved Purchase Request (PR No. ${
              this.procurement.code
            }, dated ${pr_date})
            from the end-user for the <b>"${this.procurement.title.toUpperCase()}"</b>, with a total Approved Budget for the Contract (ABC) of
            <b>${total_amount_contract_in_words}</b> (PHP ${Number(
        award_bid_total_price
      ).toLocaleString()}), to be procured on a per-item basis and following detailed technical specifications (Annex “B”);
          </p>
          <p style="text-align: justify">
            <b>WHEREAS</b>, the BAC, in full compliance with  ${mode_of_procurement_ra_nos}; and its IRR, initiated the procurement process by issuing Requests for Quotation (RFQs)
            to at least three (3) suppliers with established qualifications, namely: ${all_pr_supplier_names};
          </p>
          <p style="text-align: justify">
            <b>WHEREAS</b>, upon opening and evaluation of the bid documents, the BAC found that ${awarded_supplier_names} met the legal requirements but failed to comply with the technical specifications; thus, no responsive and eligible bid was identified;
          </p>
          <p style="text-align: justify">
            <b>NOW, THEREFORE,</b> We the members of the Bids and Awards Committee, by virtue of the powers vested on Us by the Law, hereby RESOLVE as it hereby RESOLVED;
          </p>
          <p style="text-align: justify; margin-top:-20px">
           <ul style="list-style-type:none; padding-left:0; text-align:justify;">
            <div style="height:1em;"></div>

            <li style="padding-left:2em; text-align:justify; text-indent:-1.5em;">
              <b>I.</b> To declare a failure of bidding for the project
              <b>"${this.procurement.title.toUpperCase()}"</b> due to the absence of a technically compliant bid;
            </li>

            <div style="height:1em;"></div>

          <li style="padding-left:2em; text-align:justify; text-indent:-1.5em;">
            <b>II.</b> To recommend to the Head of the Procuring Entity (HOPE), the Regional Director of DOST-IX,
            the immediate review and evaluation of the causes of the bidding failure and to undertake appropriate
            measures or revisions in accordance with the IRR of
            ${
              this.procurement.pap_codes?.[0]?.pap_code?.mode_of_procurement
                ?.republic_act_number
            };
          </li>

          <div style="height:1em;"></div>

          <li style="padding-left:2em; text-align:justify; text-indent:-1.5em;">
            <b>III.</b> To recommend the conduct of a re-bidding for the same project, subject to the necessary
            adjustments and compliance with applicable procurement rules and procedures.
          </li>

        </ul>
          </p>
          <p style="text-align: justify"><b>RESOLVED</b>, at the Department of Science and Technology Regional Office IX, Pettit Barracks, Zamboanga City this ${current_date}.</p>
        </div>
      `;
    },

    submit() {
      if (this.editable) {
        this.form.option = "update";
        this.form.put(`/faims/bac-resolutions/` + this.form.id, {
          preserveScroll: true,
          onSuccess: (response) => {
            this.$emit("update", true);
            this.form.reset();
            this.hide();
          },
        });
      } else {
        this.form.post("/faims/bac-resolutions", {
          preserveScroll: true,
          onSuccess: (response) => {
            this.$emit("add", true);
            this.hide();
          },
        });
      }
    },

    toggleRightSidebar() {
      this.isRightCollapsed = !this.isRightCollapsed;
      localStorage.setItem("isRightCollapsed", this.isRightCollapsed);
    },

    showRightTab(tab) {
      this.activeRightTab = tab;
      localStorage.setItem("activeRightTab", tab);
    },

    formatDate(dateString) {
      const date = new Date(dateString);
      return date.toLocaleString("en-US", {
        year: "numeric",
        month: "short",
        day: "numeric",
        hour: "2-digit",
        minute: "2-digit",
      });
    },

    fetchLogsForBACResolution(bacResolutionId) {
      axios
        .get(`/faims/procurements/${this.procurement.id}`, {
          params: {
            option: "bac_resolution_logs",
            bac_resolution_id: bacResolutionId,
          },
        })
        .then((response) => {
          this.modalLogs = response.data.logs;
        })
        .catch((err) => console.log(err));
    },

    splitContentIntoPages(content) {
      if (!content) {
        this.contentPages = [""];
        this.currentPage = 1;
        return;
      }

      // First, try to split by the delimiter
      let pages = content.split("\n\n---PAGE BREAK---\n\n");

      // If no delimiters, split by length (approximately 10000 characters per page for maximum fit)
      if (pages.length === 1) {
        const maxLength = 10000;
        pages = [];
        let remaining = content;
        while (remaining.length > maxLength) {
          let splitIndex = remaining.lastIndexOf(" ", maxLength);
          if (splitIndex === -1) splitIndex = maxLength;
          pages.push(remaining.substring(0, splitIndex));
          remaining = remaining.substring(splitIndex).trim();
        }
        if (remaining.length > 0) {
          pages.push(remaining);
        }
      }

      this.contentPages = pages;
      this.currentPage = 1;
    },
  },
};
</script>

<style scoped>
.modal-body-scroll {
  max-height: 100vh;
  overflow-y: auto;
}
</style>
