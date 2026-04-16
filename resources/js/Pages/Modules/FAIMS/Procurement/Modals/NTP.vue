<template>
  <b-modal
    v-model="showModal"
    header-class="p-3"
    title="Edit Notice to Proceed"
    size="xl"
    centered
    no-close-on-backdrop
  >
    <form class="customform">
      <BRow>
        <BCol md="4" class="mt-2">
          <InputLabel value="NTP Number" />
          <TextInput :modelValue="form.code" class="form-control" :light="true" readonly />
        </BCol>

        <BCol md="4" class="mt-2">
          <InputLabel value="Purchase Order Number" />
          <TextInput :modelValue="purchaseOrderCode" class="form-control" :light="true" readonly />
        </BCol>

        <BCol md="4" class="mt-2">
          <InputLabel value="Supplier" />
          <TextInput :modelValue="supplierName" class="form-control" :light="true" readonly />
        </BCol>

        <BCol lg="12" class="mt-2">
          <InputLabel value="Content" :message="form.errors.ntp_body" />
          <CustomEditor v-model="form.ntp_body" />
          <InputError :message="form.errors.ntp_body" />
        </BCol>
      </BRow>
    </form>

    <template v-slot:footer>
      <b-button type="button" @click="hide()" variant="light" block>Cancel</b-button>
      <b-button type="button" @click="submit()" variant="success" :disabled="form.processing" block>
        Update
      </b-button>
    </template>
  </b-modal>
</template>

<script>
import { useForm } from "@inertiajs/vue3";
import axios from "axios";
import InputError from "@/Shared/Components/Forms/InputError.vue";
import InputLabel from "@/Shared/Components/Forms/InputLabel.vue";
import TextInput from "@/Shared/Components/Forms/TextInput.vue";
import CustomEditor from "@/Shared/Components/Forms/CustomEditor.vue";

export default {
  components: {
    InputError,
    InputLabel,
    TextInput,
    CustomEditor,
  },
  props: ["procurement", "noa"],
  data() {
    return {
      form: useForm({
        po_id: null,
        ntp_id: null,
        code: null,
        ntp_body: "",
        option: "update_ntp",
      }),
      showModal: false,
      selectedPO: null,
    };
  },
  computed: {
    purchaseOrderCode() {
      return this.selectedPO?.code || "";
    },
    supplierName() {
      return this.noa?.procurement_quotation?.supplier?.name || "";
    },
  },
  methods: {
    edit(purchaseOrder) {
      const ntp = purchaseOrder?.ntp || {};

      this.selectedPO = purchaseOrder;
      this.form.clearErrors();
      this.form.po_id = purchaseOrder?.id || null;
      this.form.ntp_id = ntp?.id || null;
      this.form.code = ntp?.code || "";
      this.form.option = "update_ntp";
      this.form.ntp_body = ntp?.remarks || this.buildDefaultBody(purchaseOrder);
      this.showModal = true;
    },

    hide() {
      this.showModal = false;
      this.form.clearErrors();
    },

    buildDefaultBody(purchaseOrder) {
      const quotation = this.noa?.procurement_quotation || {};
      const procurementTitle = this.procurement?.title || "";
      const procurementCode = this.procurement?.code || "";
      const deliveryTerm = quotation?.delivery_term || "the agreed delivery term";
      const placeOfDelivery = purchaseOrder?.place_of_delivery?.name || "the designated place of delivery";
      const totalAmount = (this.noa?.items || []).reduce((sum, entry) => {
        return sum + (Number(entry?.item?.bid_price || 0) * Number(entry?.item?.item?.item_quantity || 0));
      }, 0);

      return `
        <p>
          Please be informed that you are hereby given the Notice to Proceed for the implementation of the project
          <b>${procurementTitle}</b>, under reference <b>${procurementCode}</b>, which has been awarded
          to your company with a total contract amount of <b>${this.numberToWords(totalAmount)} (Php ${this.formatCurrency(totalAmount)})</b>.
        </p>
        <p>
          You are instructed to commence the delivery and completion of the awarded item/s effective immediately upon receipt
          of this Notice. The delivery term is ${deliveryTerm}, and the delivery shall be made to ${placeOfDelivery}.
        </p>
        <p>
          We appreciate your interest in this opportunity and we look forward to your satisfactory performance of your obligations
          under the project.
        </p>
        <p>Thank you.</p>
      `.trim();
    },

    formatCurrency(value) {
      return Number(value || 0).toLocaleString("en-US", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      });
    },

    numberToWords(value) {
      const amount = Number(value || 0);
      const whole = Math.floor(amount);
      const cents = Math.round((amount - whole) * 100);

      let words = `${this.convertWholeNumber(whole)} Pesos`;

      if (cents > 0) {
        words += ` and ${this.convertWholeNumber(cents)} Centavos`;
      }

      return words;
    },

    convertWholeNumber(num) {
      if (num === 0) {
        return "Zero";
      }

      const ones = ["", "One", "Two", "Three", "Four", "Five", "Six", "Seven", "Eight", "Nine"];
      const teens = ["Ten", "Eleven", "Twelve", "Thirteen", "Fourteen", "Fifteen", "Sixteen", "Seventeen", "Eighteen", "Nineteen"];
      const tens = ["", "", "Twenty", "Thirty", "Forty", "Fifty", "Sixty", "Seventy", "Eighty", "Ninety"];
      const thousands = ["", "Thousand", "Million", "Billion", "Trillion"];

      const convertChunk = (chunk) => {
        let text = "";
        const hundred = Math.floor(chunk / 100);
        const remainder = chunk % 100;

        if (hundred) {
          text += `${ones[hundred]} Hundred`;
        }

        if (remainder) {
          if (text) {
            text += " ";
          }

          if (remainder < 10) {
            text += ones[remainder];
          } else if (remainder < 20) {
            text += teens[remainder - 10];
          } else {
            text += tens[Math.floor(remainder / 10)];
            if (remainder % 10) {
              text += ` ${ones[remainder % 10]}`;
            }
          }
        }

        return text.trim();
      };

      const chunks = [];
      let remaining = num;

      while (remaining > 0) {
        chunks.push(remaining % 1000);
        remaining = Math.floor(remaining / 1000);
      }

      return chunks
        .map((chunk, index) => {
          if (!chunk) {
            return "";
          }

          const suffix = thousands[index] ? ` ${thousands[index]}` : "";
          return `${convertChunk(chunk)}${suffix}`.trim();
        })
        .filter(Boolean)
        .reverse()
        .join(" ");
    },

    submit() {
      this.form.clearErrors();
      this.form.processing = true;

      axios
        .put(
          `/faims/purchase-orders/${this.form.po_id}`,
          {
            ntp_id: this.form.ntp_id,
            ntp_body: this.form.ntp_body || "",
            option: this.form.option,
          },
          {
            headers: {
              Accept: "application/json",
              "X-Requested-With": "XMLHttpRequest",
            },
          }
        )
        .then((response) => {
          const status = response?.data?.status;

          if (status !== true && status !== "success") {
            this.form.setError(
              "ntp_body",
              response?.data?.info || "Unable to update this Notice to Proceed."
            );
            return;
          }

          this.$emit("update");
          this.hide();
        })
        .catch((error) => {
          if (error.response?.status === 422 && error.response?.data?.errors) {
            this.form.setError(error.response.data.errors);
            return;
          }

          this.form.setError(
            "ntp_body",
            error.response?.data?.info ||
              error.response?.data?.message ||
              "Unable to update this Notice to Proceed."
          );
          console.error(error);
        })
        .finally(() => {
          this.form.processing = false;
        });
    },
  },
};
</script>
