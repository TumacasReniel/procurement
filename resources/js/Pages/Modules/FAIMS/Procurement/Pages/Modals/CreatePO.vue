<template>
  <b-modal
    v-model="showModal"
    header-class="p-3"
    title="Create Purchase Order"
    size="xl"
    class="v-modal-custom"
    modal-class="zoomIn"
    centered
    no-close-on-backdrop
  >
    <form class="customform">
      <BRow>
        <BCol lg="6" class="mt-2">
          <InputLabel value="Notice of Award" :message="form.errors.noa_id" />
          <Multiselect
            :options="notice_of_awards"
            v-model="form.noa_id"
            :searchable="true"
            :multiple="true"
            label="name"
            placeholder="Select NOAs"
          />
        </BCol>

        <!-- <BCol lg="12" class="mt-2">
          <InputLabel value="NOA Number" />
          <TextInput
            v-model="noa.code"
            type="text"
            class="form-control"
            :light="true"
            readonly
          />
        </BCol> -->

        <BCol lg="6" class="mt-2">
          <InputLabel value="Supplier" />
          <TextInput
            v-model="form.supplier.name"
            type="text"
            class="form-control"
            :light="true"
            readonly
          />
        </BCol>

        <BCol lg="6" class="mt-2">
          <InputLabel value="Supplier Address" />
          <TextInput
            v-model="form.supplier_address.address"
            type="text"
            class="form-control"
            :light="true"
            readonly
          />
        </BCol>

        <BCol lg="6" class="mt-2">
          <InputLabel value="PO Date" :message="form.errors.po_date" />
          <TextInput
            v-model="form.po_date"
            type="date"
            class="form-control"
            :light="true"
          />
        </BCol>

        <BCol lg="6" class="mt-2">
          <InputLabel value="Delivery Term" :message="form.errors.delivery_term" />
          <TextInput
            v-model="form.delivery_term"
            type="text"
            class="form-control"
            :light="true"
          />
        </BCol>

        <BCol lg="6" class="mt-2">
          <InputLabel value="Payment Term" :message="form.errors.payment_term" />
          <TextInput
            v-model="form.payment_term"
            type="text"
            class="form-control"
            :light="true"
          />
        </BCol>

        <BCol lg="6" class="mt-2">
          <InputLabel
            value="Place of Delivery"
            :message="form.errors.place_of_delivery_id"
          />

          <Multiselect
            :options="deliveryPlaceOptions"
            v-model="form.place_of_delivery_id"
            :searchable="true"
            label="name"
            placeholder="Select Place of Delivery"
          />
        </BCol>

        <BCol lg="6" class="mt-2">
          <InputLabel value="Date of Delivery" :message="form.errors.date_of_delivery" />
          <TextInput
            v-model="form.date_of_delivery"
            type="date"
            class="form-control"
            :light="true"
          />
        </BCol>

        <BCol lg="12"><hr class="text-muted mt-4 mb-0" /></BCol>
      </BRow>
    </form>

    <template v-slot:footer>
      <b-button @click="hide()" variant="outline-light" block>Cancel</b-button>
      <b-button
        @click="submit()"
        variant="outline-primary"
        :disabled="form.processing || !form.place_of_delivery_id || !form.noa_id.length"
        block
        >Save</b-button
      >
    </template>
  </b-modal>
</template>
<script>
import { useForm } from "@inertiajs/vue3";
import Multiselect from "@vueform/multiselect";
import InputError from "@/Shared/Components/Forms/InputError.vue";
import InputLabel from "@/Shared/Components/Forms/InputLabel.vue";
import TextInput from "@/Shared/Components/Forms/TextInput.vue";
import Amount from "@/Shared/Components/Forms/Amount.vue";

export default {
  components: {
    Amount,
    InputError,
    InputLabel,
    TextInput,
    Multiselect,
  },
  props: ["procurement", "dropdowns", "noas"],
  data() {
    return {
      currentUrl: window.location.origin,
      form: useForm({
        id: null,
        procurement_id: this.procurement.id,
        noa_id: [],
        po_date: null,
        code: null,
        supplier: [],
        place_of_delivery_id: null,
        date_of_delivery: this.getDatePlusWorkingDays(15),
        delivery_term: null,
        payment_term: "within 30 calendar days after IAR",
        option: "",
      }),
      notice_of_awards: [],
      showModal: false,
      deliveryTermTemplate: "",
      isSyncingDeliveryTerm: false,
    };
  },

  computed: {
    deliveryPlaceOptions() {
      if (Array.isArray(this.dropdowns?.delivery_places)) {
        return this.dropdowns.delivery_places;
      }

      if (this.dropdowns?.delivery_places && typeof this.dropdowns.delivery_places === "object") {
        return Object.values(this.dropdowns.delivery_places);
      }

      return [];
    },

  },

  watch: {
    "form.po_date"() {
      this.syncDeliveryTermWithDeliveryDate();
    },
    "form.date_of_delivery"() {
      this.syncDeliveryTermWithDeliveryDate();
    },
    "form.delivery_term"(value) {
      if (this.isSyncingDeliveryTerm) {
        return;
      }

      this.deliveryTermTemplate = String(value || "").trim();
    },
  },

  methods: {
    show(data) {
      const baseTerm = data.procurement_quotation?.delivery_term || "";

      this.showModal = true;
      this.noas.map((noa) => {
        if (noa) {
          this.notice_of_awards.push({
            value: noa.id,
            name: noa.code + " - " + noa.procurement_quotation.supplier.name,
          });
        }
      });
      this.form.po_date = this.getCurrentDate();
      this.form.supplier = data.supplier;
      this.form.place_of_delivery_id =
        data.place_of_delivery_id ||
        data.procurement_quotation?.place_of_delivery_id ||
        null;
      this.deliveryTermTemplate = baseTerm;
      this.form.date_of_delivery = this.addCalendarDays(
        this.form.po_date,
        this.extractDeliveryTermDays(baseTerm) ?? 15
      );
      this.form.delivery_term = baseTerm;
      this.syncDeliveryTermWithDeliveryDate();
    },
    hide() {
      this.form.reset();
      this.showModal = false;
      this.deliveryTermTemplate = "";
      this.isSyncingDeliveryTerm = false;
    },

    formatCurrency(value) {
      return new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
      }).format(value);
    },

    submit() {
      this.form.post("/faims/purchase-orders", {
        onSuccess: () => {
          this.$emit("add", true);
          this.hide();
        },
        onError: (errors) => {
          console.error("Submission failed");
        },
      });
    },

    normalizeDateInput(value) {
      if (!value) {
        return null;
      }

      if (typeof value === "string") {
        const match = value.match(/^(\d{4}-\d{2}-\d{2})/);
        if (match) {
          return match[1];
        }
      }

      const date = new Date(value);
      if (Number.isNaN(date.getTime())) {
        return null;
      }

      const year = date.getFullYear();
      const month = String(date.getMonth() + 1).padStart(2, "0");
      const day = String(date.getDate()).padStart(2, "0");

      return `${year}-${month}-${day}`;
    },

    parseDateInput(value) {
      const normalized = this.normalizeDateInput(value);

      if (!normalized) {
        return null;
      }

      const [year, month, day] = normalized.split("-").map(Number);
      return new Date(year, month - 1, day);
    },

    formatDateInput(date) {
      if (!(date instanceof Date) || Number.isNaN(date.getTime())) {
        return null;
      }

      const year = date.getFullYear();
      const month = String(date.getMonth() + 1).padStart(2, "0");
      const day = String(date.getDate()).padStart(2, "0");

      return `${year}-${month}-${day}`;
    },

    extractDeliveryTermDays(term) {
      const match = String(term || "").match(/(\d+)/);

      return match ? Number.parseInt(match[1], 10) : null;
    },

    addCalendarDays(baseDateValue, days) {
      const baseDate = this.parseDateInput(baseDateValue) || new Date();
      const normalizedDays = Math.max(0, Number(days) || 0);
      const nextDate = new Date(baseDate);

      nextDate.setDate(nextDate.getDate() + normalizedDays);

      return this.formatDateInput(nextDate);
    },

    getCalendarDayDifference(startValue, endValue) {
      const startDate = this.parseDateInput(startValue);
      const endDate = this.parseDateInput(endValue);

      if (!startDate || !endDate) {
        return null;
      }

      const millisecondsPerDay = 24 * 60 * 60 * 1000;
      const rawDifference = Math.round((endDate - startDate) / millisecondsPerDay);

      return Math.max(0, rawDifference);
    },

    buildDeliveryTerm(days) {
      const normalizedDays = Math.max(0, Number(days) || 0);
      const dayLabel = `day${normalizedDays === 1 ? "" : "s"}`;
      const template = String(this.deliveryTermTemplate || "").trim();

      if (!template) {
        return `${normalizedDays} ${dayLabel}`;
      }

      if (/\d+/.test(template)) {
        return template.replace(/\d+/, String(normalizedDays));
      }

      if (/day/i.test(template)) {
        return `${normalizedDays} ${template}`.trim();
      }

      return `${normalizedDays} ${dayLabel} ${template}`.trim();
    },

    syncDeliveryTermWithDeliveryDate() {
      const days = this.getCalendarDayDifference(
        this.form.po_date,
        this.form.date_of_delivery
      );

      if (days === null) {
        return;
      }

      const nextDeliveryTerm = this.buildDeliveryTerm(days);

      if (this.form.delivery_term === nextDeliveryTerm) {
        return;
      }

      this.isSyncingDeliveryTerm = true;
      this.form.delivery_term = nextDeliveryTerm;

      this.$nextTick(() => {
        this.isSyncingDeliveryTerm = false;
      });
    },

    getDatePlusWorkingDays(days) {
      let date = new Date();
      let addedDays = 0;

      while (addedDays < days) {
        date.setDate(date.getDate() + 1);
        const dayOfWeek = date.getDay();
        if (dayOfWeek !== 0 && dayOfWeek !== 6) {
          addedDays++;
        }
      }

      const year = date.getFullYear();
      const month = String(date.getMonth() + 1).padStart(2, "0");
      const day = String(date.getDate()).padStart(2, "0");

      return `${year}-${month}-${day}`;
    },
    getCurrentDate() {
      const date = new Date();
      const year = date.getFullYear();
      const month = String(date.getMonth() + 1).padStart(2, "0");
      const day = String(date.getDate()).padStart(2, "0");

      return `${year}-${month}-${day}`;
    },
  },
};
</script>
