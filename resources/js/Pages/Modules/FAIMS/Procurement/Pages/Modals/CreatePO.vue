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
            v-model="form.supplier_address"
            type="text"
            class="form-control"
            :light="true"
            readonly
          />
        </BCol>

        <BCol lg="6" class="mt-2">
          <InputLabel value="Delivery Term" :message="form.errors.date_of_delivery" />
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
            :options="dropdowns.delivery_places"
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
    };
  },

  computed: {
    // totalAmount() {
    //   return this.form.items.reduce((sum, item) => {
    //     return sum + item.item.bid_price * item.item.item.item_quantity;
    //   }, 0);
    // },
  },

  methods: {
    show(data) {
      this.showModal = true;
      this.noas.map((noa) => {
        if (noa) {
          this.notice_of_awards.push({
            value: noa.id,
            name: noa.code + " - " + noa.procurement_quotation.supplier.name,
          });
        }
      });
      this.form.supplier = data.supplier;
      this.form.delivery_term = data.procurement_quotation.delivery_term;
      this.form.place_of_delivery_id = data.place_of_delivery_id;
    },
    hide() {
      this.form.reset();
      this.showModal = false;
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
  },
};
</script>
