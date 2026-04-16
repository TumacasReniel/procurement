<template>
  <b-modal
    v-model="showModal"
    header-class="p-3"
    :title="action + ' Notice of Award'"
    size="xl"
    centered
    no-close-on-backdrop
  >
    <form class="customform">
      <BRow>
        <BCol md="4" class="mt-2">
          <InputLabel value="Notice of Award Number" />
          <TextInput :modelValue="form.code" class="form-control" :light="true" readonly />
        </BCol>

        <BCol md="4" class="mt-2">
          <InputLabel value="Procurement Number" />
          <TextInput :modelValue="procurement.code" class="form-control" :light="true" readonly />
        </BCol>

        <BCol md="4" class="mt-2">
          <InputLabel value="Supplier" />
          <TextInput :modelValue="supplierName" class="form-control" :light="true" readonly />
        </BCol>

        <BCol lg="12" class="mt-2">
          <InputLabel value="Content" :message="form.errors.body" />
          <CustomEditor v-model="form.body" />
          <InputError :message="form.errors.body" />
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
  props: ["procurement"],
  data() {
    return {
      form: useForm({
        id: null,
        code: null,
        procurement_id: this.procurement.id,
        body: "",
        type: null,
        option: "update",
      }),
      showModal: false,
      action: "Edit",
      selectedNoa: null,
    };
  },
  computed: {
    supplierName() {
      return this.selectedNoa?.procurement_quotation?.supplier?.name || "";
    },
  },
  methods: {
    edit(data) {
      this.selectedNoa = data;
      this.action = "Edit";
      this.form.clearErrors();
      this.form.id = data.id;
      this.form.code = data.code;
      this.form.procurement_id = data.procurement_id || this.procurement.id;
      this.form.type = data.type || data.bac_resolution?.type || null;
      this.form.option = "update";
      this.form.body = data.body || this.buildDefaultBody(data);
      this.showModal = true;
    },

    hide() {
      this.showModal = false;
      this.form.clearErrors();
    },

    buildDefaultBody(noa) {
      const itemNumbers = (noa.items || [])
        .map((entry) => entry?.item?.item?.item_no)
        .filter(Boolean);
      const { itemLabel, itemText } = this.getItemReference(itemNumbers);
      const totalAmount = (noa.items || []).reduce((sum, entry) => {
        return sum + Number(entry?.item?.bid_price || 0);
      }, 0);
      const formattedAmount = this.formatCurrency(totalAmount);
      const amountInWords = this.numberToWords(totalAmount);
      const procurementTitle = noa.procurement?.title || this.procurement.title || "";
      const procurementCode = noa.procurement?.code || this.procurement.code || "";
      const deliveryTerm = noa.procurement_quotation?.delivery_term || "the agreed delivery term";

      return `
        <p>
          We are pleased to inform you that the procurement for the <b>"${procurementTitle}"</b>, ${itemLabel}
          <span style="font-weight:bold">${itemText}</span>,
          under our reference PR no. <b>${procurementCode}</b> has been awarded to your company with the total contract
          amount of <b>${amountInWords} (Php ${formattedAmount})</b>.
        </p>
        <p>
          The item awarded under this procurement is as follows: ${itemLabel}. ${itemText}
        </p>
        <p>
          The delivery term is within ${deliveryTerm} upon receipt of the Notice to Proceed,
          and the delivery shall be made to the Department of Science and Technology - Regional Office IX,
          Pettit Barracks, Zone IV, Zamboanga City.
        </p>
        <p>
          We appreciate your interest in this opportunity and we look forward to your satisfactory performance of your obligations
          under the project.
        </p>
        <p>Thank you.</p>
      `.trim();
    },

    getItemReference(itemNumbers) {
      if (itemNumbers.length === 0) {
        return {
          itemLabel: "item no.",
          itemText: "",
        };
      }

      if (itemNumbers.length === 1) {
        return {
          itemLabel: "item no.",
          itemText: itemNumbers[0],
        };
      }

      if (itemNumbers.length === 2) {
        return {
          itemLabel: "item nos.",
          itemText: `${itemNumbers[0]} & ${itemNumbers[1]}`,
        };
      }

      return {
        itemLabel: "item nos.",
        itemText: `${itemNumbers.slice(0, -1).join(", ")}, & ${itemNumbers[itemNumbers.length - 1]}`,
      };
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
      this.form.option = "update";
      this.form.clearErrors();
      this.form.processing = true;
      const payload = {
        id: this.form.id,
        code: this.form.code,
        procurement_id: this.form.procurement_id,
        body: this.form.body || "",
        type: this.form.type,
        option: this.form.option,
      };

      axios
        .put(
          `/faims/notice-of-awards/${this.form.id}`,
          payload,
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
            this.form.setError("body", response?.data?.info || "Unable to update this NOA.");
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
            "body",
            error.response?.data?.info ||
              error.response?.data?.message ||
              "Unable to update this NOA."
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
