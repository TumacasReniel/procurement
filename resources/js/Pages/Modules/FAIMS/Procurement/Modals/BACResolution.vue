<template>
  <b-modal
    v-model="showModal"
    header-class="p-3 bac-resolution-modal-header"
    :title="editable ? 'Update BAC Resolution' : 'Create BAC Resolution'"
    size="xl"
    centered
    no-close-on-backdrop
    content-class="bac-resolution-modal-content"
    body-class="modal-body-scroll bac-resolution-modal-body"
    footer-class="bac-resolution-modal-footer"
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
                class="form-control bac-resolution-input"
                readonly
              />
            </BCol>
            <BCol class="mt-2">
              <InputLabel value="Type" />
              <TextInput
                v-model="form.type"
                class="form-control bac-resolution-input"
                readonly
              />
            </BCol>

            <BCol class="mt-2">
              <InputLabel value="Procurement Number" />
              <TextInput
                v-model="procurement.code"
                class="form-control bac-resolution-input"
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
          class="card shadow-lg border-0 bac-resolution-sidebar"
        >
          <div
            class="card-header bg-gradient-primary border-0 d-flex align-items-center justify-content-between bac-resolution-sidebar__header"
          >
            <div v-if="!isRightCollapsed">
              <h6 class="card-title mb-0 bac-resolution-sidebar__title">
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
              class="btn btn-sm rounded-circle p-2 ms-2 bac-resolution-sidebar__toggle"
            >
              <i
                :class="isRightCollapsed ? 'ri-arrow-left-line' : 'ri-arrow-right-line'"
                class="fs-6 bac-resolution-sidebar__toggle-icon"
              ></i>
            </button>
          </div>
          <div
            v-if="!isRightCollapsed"
            class="card-body p-0 bac-resolution-sidebar__body"
          >
            <div class="p-3">
              <div
                class="nav nav-tabs nav-justified mb-3 bac-resolution-log-tabs"
              >
                <button
                  :class="['nav-link bac-resolution-log-tab', activeRightTab === 1 ? 'active' : '']"
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
                    class="log-item p-3 mb-3 bac-resolution-log-item"
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
                          class="log-changes mt-2 bac-resolution-log-changes"
                        >
                          <div class="small fw-bold text-muted mb-1">Changes:</div>
                          <div
                            v-for="(value, key) in log.changes"
                            :key="key"
                            class="change-item bac-resolution-change-item"
                          >
                            <span class="change-key bac-resolution-change-key">{{ key }}:</span>
                            <span class="change-value bac-resolution-change-value">{{ value }}</span>
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
            class="card-body p-0 bac-resolution-sidebar__body"
          >
            <div class="p-2 d-flex flex-column align-items-center">
              <div class="position-relative">
                <button
                  :class="[
                    'nav-link mb-2 rounded-pill border-0 transition-all p-2 bac-resolution-log-tab-icon',
                    { 'is-active': activeRightTab === 1 },
                  ]"
                  @click="showRightTab(1)"
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
      <b-button @click="hide()" variant="light" class="bac-resolution-cancel-btn" block>Cancel</b-button>
      <b-button @click="submit(form)" variant="success" v-if="editable" block
        >Update</b-button
      >
      <b-button @click="submit(form)" variant="success" v-else block>Save</b-button>
    </template>
  </b-modal>
</template>

<script>
import { useForm } from "@inertiajs/vue3";
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
      contentPages: [""],
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
      this.splitContentIntoPages(value, this.currentPage);
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
        const pages = [...this.contentPages];
        pages[this.currentPage - 1] = value;
        this.form.body = this.serializePages(pages);
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
      this.currentPage = 1;
      this.contentPages = [""];
      this.form.type = type;
      this.editable = false;
      this.modalLogs = this.logs;
      this.Content();
    },

    edit(data) {
      this.form.id = data.id;
      this.form.code = data.code;
      this.form.procurement_id = data.procurement_id;
      this.currentPage = 1;
      this.contentPages = [""];
      this.form.body = data.body;
      this.form.type = data.type;
      this.showModal = true;
      this.editable = true;
      this.fetchLogsForBACResolution(data.id);
    },

    hide() {
      this.showModal = false;
      this.currentPage = 1;
      this.contentPages = [""];
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

    uniqueTextValues(values = []) {
      return [...new Set(
        values
          .map((value) => (typeof value === "string" ? value.trim() : value))
          .filter(
            (value) =>
              typeof value === "string" &&
              value.length > 0 &&
              value.toLowerCase() !== "n/a"
          )
      )];
    },

    joinWithAnd(values = []) {
      if (values.length === 0) return "";
      if (values.length === 1) return values[0];
      if (values.length === 2) return `${values[0]} and ${values[1]}`;

      return `${values.slice(0, -1).join(", ")}, and ${values[values.length - 1]}`;
    },

    sumNumericValues(values = []) {
      return values.reduce((sum, value) => sum + (Number(value) || 0), 0);
    },

    normalizeSupplierLocation(location = "") {
      const trimmedLocation = String(location || "").trim();
      if (!trimmedLocation) return null;

      const normalizedLocation = trimmedLocation
        .toLowerCase()
        .replace(/\./g, "")
        .replace(/\s+/g, " ")
        .trim();

      const locationMap = {
        "zamboanga city": "Zamboanga City",
        zamboanga: "Zamboanga City",
        "pagadian city": "Pagadian City",
        pagadian: "Pagadian City",
        "dipolog city": "Dipolog City",
        dipolog: "Dipolog City",
        "isabela city": "Isabela City",
        isabela: "Isabela City",
        ipil: "Ipil",
      };

      return locationMap[normalizedLocation] || trimmedLocation;
    },

    extractSupplierLocation(address = "") {
      const cleanedAddress = String(address || "").trim();
      if (!cleanedAddress) return null;

      const segments = cleanedAddress
        .split(",")
        .map((segment) => segment.trim())
        .filter(Boolean);
      const reversedSegments = [...segments].reverse();

      const citySegment = reversedSegments.find((segment) => /\bcity\b/i.test(segment));
      if (citySegment) {
        return this.normalizeSupplierLocation(citySegment);
      }

      const municipalitySegment = reversedSegments.find((segment) =>
        /\bmunicipality\b/i.test(segment)
      );
      if (municipalitySegment) {
        return this.normalizeSupplierLocation(municipalitySegment);
      }

      const provinceSegment = reversedSegments.find((segment) =>
        /\bprovince\b/i.test(segment)
      );
      if (provinceSegment) {
        return this.normalizeSupplierLocation(provinceSegment);
      }

      const genericPlaceSegment = reversedSegments.find(
        (segment) =>
          /[A-Za-z]/.test(segment) &&
          !/^\d+$/.test(segment) &&
          segment.length <= 40
      );

      return this.normalizeSupplierLocation(genericPlaceSegment);
    },

    procurementQuotationContext() {
      const quotations = this.procurement.quotations || [];
      const supplierNames = this.uniqueTextValues(
        quotations.map((quotation) => quotation.supplier?.name)
      );
      const supplierLocations = this.uniqueTextValues(
        quotations.map((quotation) =>
          this.extractSupplierLocation(quotation.supplier?.address?.address)
        )
      );

      const quotationCount = quotations.length;
      const supplierCount = supplierNames.length;

      return {
        quotation_count: quotationCount,
        quotation_label:
          quotationCount === 1
            ? "request for quotation (RFQ)"
            : "requests for quotation (RFQs)",
        supplier_count: supplierCount,
        supplier_label: supplierCount === 1 ? "supplier" : "suppliers",
        supplier_location_phrase: supplierLocations.length
          ? ` in ${this.joinWithAnd(supplierLocations)}`
          : "",
        supplier_names: this.joinWithAnd(supplierNames),
      };
    },

    extractSectionNumber(sectionValue) {
      const normalizedSection = String(sectionValue || "").trim();
      if (!normalizedSection) {
        return null;
      }

      const match = normalizedSection.match(/(\d+(?:\.\d+)?)/);
      return match ? match[1] : null;
    },

    procurementSectionNumber(modeName) {
      const normalizedMode = (modeName || "").trim().toLowerCase();
      const sectionMap = {
        "competitive bidding": "27",
        "competitive public bidding": "27",
        "limited source bidding": "28",
        "competitive dialogue": "29",
        "unsolicited offer with bid matching": "30",
        "direct contracting": "31",
        "direct acquisition": "32",
        "repeat order": "33",
        shopping: "34",
        "small value procurement": "34",
        "negotiated procurement": "35",
        "negotiated procurement - two failed biddings": "35.1",
        "two failed biddings": "35.1",
        "negotiated procurement - emergency cases": "35.2",
        "emergency cases": "35.2",
        "negotiated procurement - take-over of contracts": "35.3",
        "negotiated procurement - take over of contracts": "35.3",
        "take-over of contracts": "35.3",
        "take over of contracts": "35.3",
        "negotiated procurement - adjacent of contiguous": "35.4",
        "negotiated procurement - adjacent or contiguous": "35.4",
        "adjacent of contiguous": "35.4",
        "adjacent or contiguous": "35.4",
        "negotiated procurement - agency-to-agency": "35.5",
        "negotiated procurement - agency to agency": "35.5",
        "agency-to-agency": "35.5",
        "agency to agency": "35.5",
        "negotiated procurement - scientific, scholarly or artistic work, exclusive technology and media services":
          "35.6",
        "scientific, scholarly or artistic work": "35.6",
        "scientific, scholarly or artistic work, exclusive technology and media services":
          "35.6",
        "exclusive technology and media services": "35.6",
        "negotiated procurement - highly technical consultants": "35.7",
        "highly technical consultant": "35.7",
        "highly technical consultants": "35.7",
        "negotiated procurement - defense cooperation agreements and inventory-based items":
          "35.8",
        "negotiated procurement - lease of real property and venue": "35.9",
        "lease of venue and community facilities": "35.9",
        "lease of real property and venue": "35.9",
        "lease of real property or venue": "35.9",
        "negotiated procurement - non-government organization (ngo) participation":
          "35.10",
        "negotiated procurement - community participation": "35.11",
        "negotiated procurement - united nations (un) agencies, international organizations or international financing institutions":
          "35.12",
        "negotiated procurement - direct retail purchase of petroleum fuel, oil and lubricant products, electronic charging devices, and online subscriptions":
          "35.13",
        "direct sales": "36",
        "direct procurement for science, technology, and innovation": "37",
      };

      return sectionMap[normalizedMode] || null;
    },

    procurementModeContext() {
      const codes = this.procurement.codes || [];

      const modeNames = this.uniqueTextValues(
        codes.map((code) => code.procurement_code?.mode_of_procurement?.name)
      );
      const sectionNumbers = this.uniqueTextValues(
        codes.map((code) => {
          const mode = code.procurement_code?.mode_of_procurement;
          return (
            this.extractSectionNumber(mode?.others) ||
            this.procurementSectionNumber(mode?.name)
          );
        })
      );

      let sectionLabel = "";
      if (sectionNumbers.length === 1) {
        sectionLabel = `Section ${sectionNumbers[0]}`;
      } else if (sectionNumbers.length > 1) {
        sectionLabel = `Sections ${this.joinWithAnd(sectionNumbers)}`;
      }

      return {
        mode_of_procurement_names: this.joinWithAnd(modeNames).toUpperCase(),
        mode_of_procurement_ra_nos: "R.A. 12009",
        mode_of_procurement_sections: sectionLabel,
      };
    },

    Content() {
      const current_date = this.getFormattedDate();

      const {
        mode_of_procurement_names,
        mode_of_procurement_ra_nos,
        mode_of_procurement_sections,
      } = this.procurementModeContext();
      const app_types = this.procurement.codes
        .map((code) => code.procurement_code?.app_type?.name)
        .filter(Boolean)
        .join(", ");
      const allocated_budget = this.sumNumericValues(
        this.procurement.codes.map((code) => code.procurement_code?.allocated_budget)
      );
      const budget_in_words = this.numberToWords(allocated_budget);
      const approved_budget = this.sumNumericValues(
        (this.procurement.items || []).map((item) => item?.total_cost)
      );
      const approved_budget_in_words = this.numberToWords(approved_budget);
      const quotation_context = this.procurementQuotationContext();

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
        mode_of_procurement_sections,
        app_types,
        allocated_budget,
        budget_in_words,
        approved_budget,
        approved_budget_in_words,
        quotation_context,
        pr_date,
        submission_not_later_than_with_format,
        bidders
      );

      this.reawardContent(
        current_date,
        mode_of_procurement_names,
        mode_of_procurement_ra_nos,
        mode_of_procurement_sections,
        app_types,
        allocated_budget,
        budget_in_words,
        approved_budget,
        approved_budget_in_words,
        quotation_context,
        pr_date,
        submission_not_later_than_with_format,
        bidders
      );

      this.rebidContent(
        current_date,
        mode_of_procurement_names,
        mode_of_procurement_ra_nos,
        mode_of_procurement_sections,
        app_types,
        allocated_budget,
        budget_in_words,
        approved_budget,
        approved_budget_in_words,
        quotation_context,
        pr_date,
        submission_not_later_than_with_format,
        bidders
      );
    },

    awardContent(
      current_date,
      mode_of_procurement_names,
      mode_of_procurement_ra_nos,
      mode_of_procurement_sections,
      app_types,
      allocated_budget,
      budget_in_words,
      approved_budget,
      approved_budget_in_words,
      quotation_context,
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
          (item) => item.status.name === 'Awarded' && item.is_rebid == 0
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
              "${this.procurement.title.toUpperCase()}" UNDERTAKEN THROUGH ${mode_of_procurement_sections.toUpperCase()} (${mode_of_procurement_names})
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
      ).toLocaleString()}), and an Approved Budget for the Contract (ABC) of ${approved_budget_in_words}
            (PHP ${Number(approved_budget || 0).toLocaleString()}),
            to be procured through ${mode_of_procurement_sections} of the revised Implementing Rules and Regulations (IRR) of ${mode_of_procurement_ra_nos} ;
          </p>

          <p style="text-align: justify; margin-bottom: 1em; ">
            <b>WHEREAS</b>, the BAC has duly received an approved purchase request for the procurement titled
            "${this.procurement.title.toUpperCase()}" to be bid per item. Detailed technical
            specifications pertaining to this procurement are meticulously outlined in
            PR no. ${this.procurement.code} dated ${pr_date} (refer to Annex "B");
          </p>

          <p style="text-align: justify;  margin-bottom: 1em; ">
            <b>WHEREAS</b>, the BAC initiated the procurement through its secretariat through dissemination
            of ${quotation_context.quotation_count} ${quotation_context.quotation_label} to 
            suppliers of known qualifications,
            to wit: ${quotation_context.supplier_names};
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
      mode_of_procurement_sections,
      app_types,
      allocated_budget,
      budget_in_words,
      approved_budget,
      approved_budget_in_words,
      quotation_context,
      pr_date,
      submission_not_later_than_with_format,
      bidders
    ) {
      const resolveProcurementItemId = (item) =>
        item?.procurement_item_id ?? item?.item?.id ?? item?.item?.procurement_item_id ?? null;
      const latestFailedNoa = [...(this.procurement.noas || [])]
        .filter((noa) =>
          ["Not Conformed", "PO Not Conformed"].includes(noa.status?.name)
        )
        .sort((left, right) => {
          const leftDate = new Date(left?.updated_at || left?.created_at || 0).getTime();
          const rightDate = new Date(right?.updated_at || right?.created_at || 0).getTime();

          return rightDate - leftDate;
        })[0];
      const failedNoaTargetItemIds = new Set(
        (latestFailedNoa?.items || [])
          .map((noaItem) => resolveProcurementItemId(noaItem?.item))
          .filter((itemId) => itemId != null)
      );
      const notConformedTargetItemIds = new Set(
        (this.procurement.quotations || [])
          .flatMap((quotation) => quotation.items || [])
          .filter((item) => item.status?.name === "Not Conformed")
          .map((item) => resolveProcurementItemId(item))
          .filter((itemId) => itemId != null)
      );
      const reawardTargetItemIds =
        failedNoaTargetItemIds.size > 0
          ? failedNoaTargetItemIds
          : notConformedTargetItemIds;
      const selectedReawardedItems = Array.from(reawardTargetItemIds)
        .map((targetItemId) => {
          const candidates = (this.procurement.quotations || []).flatMap((quotation) =>
            (quotation.items || [])
              .filter((item) => {
                return (
                  item.status?.name === "Awarded" &&
                  item.bid_price != null &&
                  resolveProcurementItemId(item) === targetItemId
                );
              })
              .map((item) => ({
                quotation,
                item,
              }))
          );

          if (!candidates.length) {
            return null;
          }

          return candidates.sort((left, right) => {
            const leftFreeRank = left.item?.is_free ? 0 : 1;
            const rightFreeRank = right.item?.is_free ? 0 : 1;

            if (leftFreeRank !== rightFreeRank) {
              return leftFreeRank - rightFreeRank;
            }

            const leftPrice = Number(left.item?.bid_price) || 0;
            const rightPrice = Number(right.item?.bid_price) || 0;

            if (leftPrice !== rightPrice) {
              return leftPrice - rightPrice;
            }

            return (Number(left.quotation?.id) || 0) - (Number(right.quotation?.id) || 0);
          })[0];
        })
        .filter(Boolean);
      const reawardedQuotationsMap = new Map();

      selectedReawardedItems.forEach(({ quotation, item }) => {
        const key = quotation?.id ?? quotation?.supplier_id;

        if (!reawardedQuotationsMap.has(key)) {
          reawardedQuotationsMap.set(key, {
            quotation,
            awarded_items: [],
          });
        }

        reawardedQuotationsMap.get(key).awarded_items.push(item);
      });

      const reawarded_quotations = Array.from(reawardedQuotationsMap.values());

      const bidder_count = new Set(bidders).size;

      const bid_type_cap = bidder_count === 1 ? "SINGLE" : "LOWEST";
      const bid_type_small_cap = bidder_count === 1 ? "single" : "lowest";

      const reawarded_supplier_name_values = this.uniqueTextValues(
        reawarded_quotations.map(({ quotation }) => quotation.supplier?.name)
      );
      const reawarded_supplier_names_display = this.joinWithAnd(
        reawarded_supplier_name_values
      );
      const reawarded_supplier_names = reawarded_supplier_names_display.toUpperCase();
      const reawarded_supplier_count = reawarded_supplier_name_values.length;
      const reawarded_bidder_label_cap =
        reawarded_supplier_count === 1 ? "BIDDER" : "BIDDERS";
      const reawarded_bidder_label_small =
        reawarded_supplier_count === 1 ? "bidder" : "bidders";
      const reawarded_quotation_label =
        reawarded_supplier_count === 1 ? "price quotation" : "price quotations";
      const reawarded_pronoun = reawarded_supplier_count === 1 ? "its" : "their";

      let counter = 2;
      const reawarded_quotations_list = reawarded_quotations
        .map(({ quotation, awarded_items }) => {
          if (awarded_items.length === 0) return "";
          const item_numbers = awarded_items.map((item) => item.item.item_no).join(", ");
          const total_price = awarded_items.reduce((sum, item) => {
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

      const reawarded_bid_total_price = reawarded_quotations.reduce(
        (total, { awarded_items }) => {
          const total_price_for_bid = awarded_items.reduce((sum, item) => {
            const bp = parseFloat(item.bid_price) || 0;
            const bq = parseFloat(item.item.item_quantity) || 0;
            return sum + bp * bq;
          }, 0);
          return total + total_price_for_bid;
        },
        0
      );

      const reaward_total_amount_contract_in_words = this.numberToWords(
        reawarded_bid_total_price
      );

      const reawarded_table_rows = reawarded_quotations
        .map(({ quotation, awarded_items }) => {
          if (awarded_items.length === 0) return null;
          const item_ids = awarded_items.map((item) => item.item.item_no).join(", ");
          return `
            <tr>
              <td>${quotation.supplier?.name}</td>
              <td>${item_ids}</td>
            </tr>
          `;
        })
        .filter((row) => row !== null);

      const reawarded_table_html =
        reawarded_supplier_count > 1
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
      )} ${bid_type_cap} CALCULATED AND RESPONSIVE ${reawarded_bidder_label_cap} FOR THE PROCUREMENT
              "${this.procurement.title.toUpperCase()}" UNDERTAKEN THROUGH ${mode_of_procurement_sections.toUpperCase()} (${mode_of_procurement_names})
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
      ).toLocaleString()}), and an Approved Budget for the Contract (ABC) of ${approved_budget_in_words}
            (PHP ${Number(approved_budget || 0).toLocaleString()}),
            to be procured through ${mode_of_procurement_sections} of the revised Implementing Rules and Regulations (IRR) of ${mode_of_procurement_ra_nos} ;
          </p>

          <p style="text-align: justify; margin-bottom: 1em; ">
            <b>WHEREAS</b>, the BAC has duly received an approved purchase request for the procurement titled
            "${this.procurement.title.toUpperCase()}" to be bid per item. Detailed technical
            specifications pertaining to this procurement are meticulously outlined in
            PR no. ${this.procurement.code} dated ${pr_date} (refer to Annex "B");
          </p>

          <p style="text-align: justify;  margin-bottom: 1em; ">
            <b>WHEREAS</b>, the BAC initiated the procurement through its secretariat through dissemination
            of ${quotation_context.quotation_count} ${quotation_context.quotation_label} to suppliers of known qualifications${quotation_context.supplier_location_phrase},
            to wit: ${quotation_context.supplier_names};
          </p>

          <p style="text-align: justify;  margin-bottom: 1em; ">
            <b>WHEREAS</b>, among the above-mentioned bidders, ${reawarded_supplier_names} responded by submitting
            ${reawarded_pronoun} ${reawarded_quotation_label} to the BAC before opening of bids on ${submission_not_later_than_with_format}.
          </p>

          <p style="text-align: justify;  margin-bottom: 1em; ">
            <b>WHEREAS</b>, the total contract amount for the re-award recommendation is
            <b>PHP ${Number(reawarded_bid_total_price).toLocaleString()}</b> (${reaward_total_amount_contract_in_words});
          </p>

          ${reawarded_table_html}
          <p style="text-align: justify;  margin-bottom: 1em; ">
            <b>NOW, THEREFORE,</b> We the members of the Bids and Awards Committee, by virtue of the powers vested on Us by the Law, hereby RESOLVE as it hereby RESOLVED;
          </p>

          <p style="text-align: justify;  margin-bottom: 1em; margin-top: 20px; margin-left: 0 ">
            <ul style="list-style-type: none;">
              <li style="text-align: justify; margin-bottom: 1em; "><b>I. </b> To declare ${reawarded_supplier_names} as the ${bid_type_small_cap} Calculated and Responsive ${reawarded_bidder_label_small} of the procurement for
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
      mode_of_procurement_sections,
      app_types,
      allocated_budget,
      budget_in_words,
      approved_budget,
      approved_budget_in_words,
      quotation_context,
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
            (item) => item.status.name === 'Awarded' || item.is_rebid == 0
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
      ).toLocaleString()}), and an Approved Budget for the Contract (ABC) of ${approved_budget_in_words}
            (PHP ${Number(approved_budget || 0).toLocaleString()}),
            to be procured through ${mode_of_procurement_sections} of the revised IRR of ${mode_of_procurement_ra_nos};
          </p>
          <p style="text-align: justify">
            <b>WHEREAS</b>, the BAC received an approved Purchase Request (PR No. ${
              this.procurement.code
            }, dated ${pr_date})
            from the end-user for the <b>"${this.procurement.title.toUpperCase()}"</b>, with a total Approved Budget for the Contract (ABC) of
            <b>${approved_budget_in_words}</b> (PHP ${Number(
        approved_budget || 0
      ).toLocaleString()}), to be procured on a per-item basis and following detailed technical specifications (Annex “B”);
          </p>
          <p style="text-align: justify">
            <b>WHEREAS</b>, the BAC, in full compliance with  ${mode_of_procurement_ra_nos}; and its IRR, initiated the procurement process by issuing
            ${quotation_context.quotation_count} ${quotation_context.quotation_label} to ${quotation_context.supplier_count}
            ${quotation_context.supplier_label} with established qualifications${quotation_context.supplier_location_phrase},
            namely: ${quotation_context.supplier_names};
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
            ${mode_of_procurement_ra_nos};
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

    getPageBreakHtml() {
      return '<div class="bac-page-break" data-page-break="true"></div>';
    },

    normalizePageBreaks(content) {
      return String(content || "")
        .replace(/\r\n/g, "\n")
        .replace(/\n\s*\n---PAGE BREAK---\n\s*\n/g, "__BAC_PAGE_BREAK__")
        .replace(/<div[^>]*data-page-break=(['"])true\1[^>]*>\s*<\/div>/gi, "__BAC_PAGE_BREAK__")
        .replace(/<div[^>]*class=(['"])[^'"]*bac-page-break[^'"]*\1[^>]*>\s*<\/div>/gi, "__BAC_PAGE_BREAK__");
    },

    extractStoredPages(content) {
      const pages = this.normalizePageBreaks(content)
        .split("__BAC_PAGE_BREAK__")
        .map((page) => this.normalizePageContent(page));

      return pages.filter((page, index) => page.length > 0 || pages.length === 1);
    },

    serializePages(pages) {
      const cleanedPages = (pages || [])
        .map((page) => this.normalizePageContent(page))
        .filter((page, index, allPages) => page.length > 0 || allPages.length === 1);

      if (cleanedPages.length <= 1) {
        return cleanedPages[0] || "";
      }

      return cleanedPages.join(this.getPageBreakHtml());
    },

    normalizePageContent(content) {
      return String(content || "").trim();
    },

    getMeaningfulNodes(nodeList) {
      return Array.from(nodeList).filter(
        (node) => !(node.nodeType === Node.TEXT_NODE && !node.textContent.trim())
      );
    },

    serializeNode(node) {
      return node.nodeType === Node.TEXT_NODE ? node.textContent : node.outerHTML;
    },

    getOpeningTag(element) {
      const clone = element.cloneNode(false);
      const closingTag = `</${element.tagName.toLowerCase()}>`;

      return clone.outerHTML.endsWith(closingTag)
        ? clone.outerHTML.slice(0, -closingTag.length)
        : clone.outerHTML;
    },

    extractPageStructure(content) {
      const container = document.createElement("div");
      container.innerHTML = content;

      let nodes = this.getMeaningfulNodes(container.childNodes);

      if (nodes.length === 1 && nodes[0].nodeType === Node.ELEMENT_NODE) {
        const root = nodes[0];
        const childBlocks = this.getMeaningfulNodes(root.childNodes);

        if (childBlocks.length > 1) {
          return {
            wrapperOpen: this.getOpeningTag(root),
            wrapperClose: `</${root.tagName.toLowerCase()}>`,
            blocks: childBlocks.map((node) => this.serializeNode(node)),
          };
        }
      }

      return {
        wrapperOpen: "",
        wrapperClose: "",
        blocks: nodes.map((node) => this.serializeNode(node)),
      };
    },

    buildPageHtml(wrapperOpen, wrapperClose, blocks) {
      const pageBlocks = Array.isArray(blocks) ? blocks : [];

      return `${wrapperOpen}${pageBlocks.join("")}${wrapperClose}`;
    },

    createPageMeasurer() {
      const measurer = document.createElement("div");
      measurer.style.position = "fixed";
      measurer.style.left = "-99999px";
      measurer.style.top = "0";
      measurer.style.width = "210mm";
      measurer.style.height = "297mm";
      measurer.style.padding = "2cm";
      measurer.style.boxSizing = "border-box";
      measurer.style.overflow = "hidden";
      measurer.style.visibility = "hidden";
      measurer.style.pointerEvents = "none";
      measurer.style.background = "#fff";
      measurer.style.fontFamily = '"Calibri", "Times New Roman", serif';
      measurer.style.fontSize = "12pt";
      measurer.style.lineHeight = "1.5";
      measurer.style.color = "#222";
      measurer.style.whiteSpace = "normal";
      measurer.style.wordBreak = "break-word";

      document.body.appendChild(measurer);

      return measurer;
    },

    fitsOnPage(measurer, html) {
      measurer.innerHTML = html || "";

      return measurer.scrollHeight <= measurer.clientHeight + 2;
    },

    paginateByA4Height(content, depth = 0) {
      const normalizedContent = this.normalizePageContent(content);

      if (!normalizedContent) {
        return [""];
      }

      const { wrapperOpen, wrapperClose, blocks } = this.extractPageStructure(normalizedContent);

      if (!blocks.length) {
        return [normalizedContent];
      }

      const measurer = this.createPageMeasurer();
      const pages = [];
      let currentBlocks = [];

      try {
        for (const block of blocks) {
          const candidateBlocks = [...currentBlocks, block];
          const candidateHtml = this.buildPageHtml(wrapperOpen, wrapperClose, candidateBlocks);

          if (this.fitsOnPage(measurer, candidateHtml)) {
            currentBlocks = candidateBlocks;
            continue;
          }

          if (currentBlocks.length) {
            pages.push(this.buildPageHtml(wrapperOpen, wrapperClose, currentBlocks));
            currentBlocks = [];
          }

          const singleBlockHtml = this.buildPageHtml(wrapperOpen, wrapperClose, [block]);

          if (this.fitsOnPage(measurer, singleBlockHtml) || depth >= 2) {
            currentBlocks = [block];
            continue;
          }

          const nestedPages = this.paginateByA4Height(block, depth + 1);

          if (
            nestedPages.length === 1 &&
            this.normalizePageContent(nestedPages[0]) === this.normalizePageContent(block)
          ) {
            currentBlocks = [block];
            continue;
          }

          pages.push(...nestedPages);
        }

        if (currentBlocks.length || !pages.length) {
          pages.push(this.buildPageHtml(wrapperOpen, wrapperClose, currentBlocks));
        }
      } finally {
        measurer.remove();
      }

      return pages
        .map((page) => this.normalizePageContent(page))
        .filter((page, index) => page.length > 0 || pages.length === 1);
    },

    splitContentIntoPages(content, preferredPage = 1) {
      if (!content) {
        this.contentPages = [""];
        this.currentPage = 1;
        return;
      }

      const storedPages = this.extractStoredPages(content);
      const pages = storedPages.flatMap((page) => this.paginateByA4Height(page));

      this.contentPages = pages.length ? pages : [""];
      this.currentPage = Math.min(preferredPage || 1, this.contentPages.length);
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

<style>
.bac-resolution-modal-content {
  --bac-resolution-surface: #ffffff;
  --bac-resolution-surface-alt: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  --bac-resolution-panel: #f8fafc;
  --bac-resolution-panel-inner: #ffffff;
  --bac-resolution-border: rgba(148, 163, 184, 0.22);
  --bac-resolution-text: #1e293b;
  --bac-resolution-muted: #64748b;
  --bac-resolution-accent: #3b82f6;
  background: var(--bac-resolution-surface);
  border: 1px solid rgba(226, 232, 240, 0.95);
  border-radius: 18px;
  overflow: hidden;
  box-shadow: 0 24px 48px rgba(15, 23, 42, 0.16);
}

.bac-resolution-modal-header,
.bac-resolution-modal-body,
.bac-resolution-modal-footer {
  background: transparent;
  color: var(--bac-resolution-text);
  border-color: var(--bac-resolution-border);
}

.bac-resolution-modal-header .modal-title,
.bac-resolution-modal-footer {
  color: var(--bac-resolution-text);
}

.bac-resolution-input {
  background: var(--bac-resolution-panel-inner) !important;
  border-color: var(--bac-resolution-border) !important;
  color: var(--bac-resolution-text) !important;
}

.bac-resolution-input[readonly],
.bac-resolution-input:disabled {
  background: var(--bac-resolution-panel) !important;
  color: var(--bac-resolution-text) !important;
  opacity: 1;
}

.bac-resolution-sidebar {
  background: var(--bac-resolution-surface-alt);
  border-radius: 15px;
  height: 100vh;
  border: 1px solid var(--bac-resolution-border);
  overflow: hidden;
}

.bac-resolution-sidebar__header {
  border-radius: 15px 15px 0 0 !important;
  padding: 1rem;
}

.bac-resolution-sidebar__title {
  color: var(--bac-resolution-text);
}

.bac-resolution-sidebar__toggle {
  width: 40px;
  height: 40px;
  background: rgba(255, 255, 255, 0.92);
  border: 1px solid rgba(255, 255, 255, 0.55);
}

.bac-resolution-sidebar__toggle-icon {
  color: var(--bac-resolution-accent);
}

.bac-resolution-sidebar__body {
  height: 100vh;
  overflow: auto;
  border-radius: 0 0 15px 15px;
  background: transparent;
}

.bac-resolution-log-tabs {
  border-bottom: 2px solid var(--bac-resolution-accent);
  background: var(--bac-resolution-surface-alt);
  border-radius: 10px;
  padding: 5px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.bac-resolution-log-tab {
  color: var(--bac-resolution-muted);
  border-radius: 8px;
  transition: background-color 0.18s ease, color 0.18s ease, box-shadow 0.18s ease;
}

.bac-resolution-log-tab:hover {
  background: rgba(59, 130, 246, 0.08) !important;
  color: var(--bac-resolution-text) !important;
}

.bac-resolution-log-tab.active {
  background: rgba(59, 130, 246, 0.14) !important;
  color: var(--bac-resolution-text) !important;
  box-shadow: inset 0 0 0 1px rgba(59, 130, 246, 0.2);
}

.bac-resolution-log-item {
  background: var(--bac-resolution-panel);
  border-left: 4px solid var(--bac-resolution-accent);
  transition: all 0.3s ease;
  border-radius: 10px;
  color: var(--bac-resolution-text);
}

.bac-resolution-log-changes {
  background: var(--bac-resolution-panel-inner);
  padding: 0.5rem;
  border-radius: 4px;
  border: 1px solid var(--bac-resolution-border);
}

.bac-resolution-change-item {
  display: flex;
  justify-content: space-between;
  margin-bottom: 0.25rem;
  gap: 0.75rem;
}

.bac-resolution-change-key {
  font-weight: 600;
  color: var(--bac-resolution-text);
}

.bac-resolution-change-value {
  color: var(--bac-resolution-accent);
  font-family: monospace;
  font-size: 0.85rem;
}

.bac-resolution-log-tab-icon {
  transition: all 0.3s ease;
  width: 50px;
  height: 50px;
  background: rgba(255, 255, 255, 0.92);
  color: var(--bac-resolution-text);
}

.bac-resolution-log-tab-icon:hover {
  background: rgba(59, 130, 246, 0.08) !important;
  color: var(--bac-resolution-text) !important;
}

.bac-resolution-log-tab-icon.is-active {
  background: var(--bac-resolution-accent) !important;
  color: #ffffff !important;
  box-shadow: 0 6px 16px rgba(59, 130, 246, 0.28);
}

.bac-resolution-cancel-btn {
  border-color: var(--bac-resolution-border);
}

[data-bs-theme="dark"] .bac-resolution-modal-content {
  --bac-resolution-surface: linear-gradient(180deg, #161d27 0%, #111827 100%);
  --bac-resolution-surface-alt: linear-gradient(135deg, #1b2230 0%, #232c3a 100%);
  --bac-resolution-panel: #202937;
  --bac-resolution-panel-inner: #1b2230;
  --bac-resolution-border: rgba(148, 163, 184, 0.18);
  --bac-resolution-text: #e5edf7;
  --bac-resolution-muted: #9fb0c7;
  --bac-resolution-accent: #60a5fa;
  border-color: rgba(148, 163, 184, 0.2);
  box-shadow: 0 26px 52px rgba(2, 6, 23, 0.42);
}

[data-bs-theme="dark"] .bac-resolution-modal-header,
[data-bs-theme="dark"] .bac-resolution-modal-body,
[data-bs-theme="dark"] .bac-resolution-modal-footer {
  border-color: rgba(148, 163, 184, 0.18);
}

[data-bs-theme="dark"] .bac-resolution-sidebar__title,
[data-bs-theme="dark"] .bac-resolution-modal-header .modal-title,
[data-bs-theme="dark"] .bac-resolution-log-item,
[data-bs-theme="dark"] .bac-resolution-log-empty {
  color: #e5edf7 !important;
}

[data-bs-theme="dark"] .bac-resolution-sidebar__toggle {
  background: rgba(15, 23, 42, 0.38);
  border-color: rgba(148, 163, 184, 0.22);
}

[data-bs-theme="dark"] .bac-resolution-sidebar__toggle-icon {
  color: #bfdbfe;
}

[data-bs-theme="dark"] .bac-resolution-log-tabs {
  border-bottom-color: rgba(96, 165, 250, 0.5) !important;
  background: linear-gradient(135deg, #1b2230 0%, #232c3a 100%) !important;
}

[data-bs-theme="dark"] .bac-resolution-log-item {
  background: #202937;
}

[data-bs-theme="dark"] .bac-resolution-log-changes {
  background: #1b2230;
  border-color: rgba(148, 163, 184, 0.18);
}

[data-bs-theme="dark"] .bac-resolution-log-tab {
  color: #cbd5e1 !important;
}

[data-bs-theme="dark"] .bac-resolution-log-tab:hover {
  background: rgba(59, 130, 246, 0.12) !important;
  color: #e5edf7 !important;
}

[data-bs-theme="dark"] .bac-resolution-log-tab.active {
  background: rgba(59, 130, 246, 0.2) !important;
  color: #e5edf7 !important;
  box-shadow: inset 0 0 0 1px rgba(96, 165, 250, 0.28);
}

[data-bs-theme="dark"] .bac-resolution-log-tab-icon {
  background: rgba(15, 23, 42, 0.38);
  color: #e5edf7;
}

[data-bs-theme="dark"] .bac-resolution-log-tab-icon:hover:not(.is-active) {
  background: rgba(59, 130, 246, 0.12) !important;
  color: #e5edf7 !important;
}

[data-bs-theme="dark"] .bac-resolution-cancel-btn {
  background: rgba(30, 41, 59, 0.78);
  border-color: rgba(148, 163, 184, 0.22);
  color: #e5edf7;
}

[data-bs-theme="dark"] .bac-resolution-cancel-btn:hover {
  background: rgba(51, 65, 85, 0.95);
  color: #ffffff;
}
</style>
