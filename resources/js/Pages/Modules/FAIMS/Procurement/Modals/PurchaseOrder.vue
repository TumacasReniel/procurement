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
        <BCol lg="12" class="mt-2">
          <InputLabel value="NOA Number" />
          <TextInput
            v-model="noa.code"
            type="text"
            class="form-control"
            :light="true"
            readonly
          />
        </BCol>

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
            v-model="form.supplier.address"
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

      <BRow>
        <div class="table-responsive">
          <table class="table align-middle mb-0">
            <thead class="table-light">
              <tr class="fs-11">
                <th>Item No</th>
                <th>Unit</th>
                <th>Description</th>
                <th>Quantity</th>
                <th>Unit Cost</th>
                <th>Amount</th>
              </tr>
            </thead>

            <tbody style="vertical-align: top">
              <tr v-for="(item, index) in form.items" :key="index">
                <td>{{ item.item.item.item_no }}</td>
                <td>
                  {{
                    item.item.item.item_quantity > 1
                      ? item.item.item.item_unit_type.name_long
                      : item.item.item.item_unit_type.name_short
                  }}
                </td>

                <td>
                  <div v-html="item.item.item.item_description"></div>
                </td>
                <td>{{ item.item.item.item_quantity }}</td>

                <td>{{ formatCurrency(item.item.bid_price) }}</td>

                <td>
                  {{ formatCurrency(item.item.bid_price * item.item.item.item_quantity) }}
                </td>
              </tr>

              <!-- Total Row -->
              <tr class="fw-bold">
                <td colspan="5" class="text-end">Total:</td>
                <td>{{ formatCurrency(totalAmount) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </BRow>
    </form>

    <template v-slot:footer>
      <b-button @click="hide()" variant="light" block>Cancel</b-button>
      <b-button
        @click="submit()"
        variant="primary"
        :disabled="form.processing || !form.place_of_delivery_id"
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
  props: ["procurement", "noa", "dropdowns"],
  data() {
    return {
      currentUrl: window.location.origin,
      form: useForm({
        id: null,
        procurement_id: this.procurement.id,
        noa_id: null,
        po_date: null,
        code: null,
        supplier: this.noa.procurement_quotation.supplier,
        place_of_delivery_id: null,
        date_of_delivery: this.getDatePlusWorkingDays(15),
        delivery_term: null,
        payment_term: "within 30 calendar days after IAR",
        items: this.noa.items,
        option: "",
      }),
      showModal: false,
    };
  },

  computed: {
    totalAmount() {
      return this.noa.items.reduce((sum, item) => {
        return sum + item.item.bid_price * item.item.item.item_quantity;
      }, 0);
    },
  },

  methods: {
    show(existingPO = null) {
      this.showModal = true;

      if (existingPO) {
        // Editing existing PO - populate form with existing data
        this.form.id = existingPO.id;
        this.form.procurement_id = existingPO.procurement_id;
        this.form.noa_id = existingPO.noa_id;
        this.form.po_date = existingPO.po_date;
        this.form.code = existingPO.code;
        this.form.supplier = existingPO.supplier || this.noa?.procurement_quotation?.supplier;
        this.form.place_of_delivery_id = existingPO.place_of_delivery_id;
        this.form.date_of_delivery = existingPO.date_of_delivery;
        this.form.delivery_term = existingPO.delivery_term;
        this.form.payment_term = existingPO.payment_term;
        this.form.items = existingPO.items || this.noa.items;
        this.form.option = "update";
      } else {
        // Creating new PO - use default values
        this.form.supplier = this.noa?.procurement_quotation?.supplier;
        this.form.delivery_term = this.noa?.procurement_quotation?.delivery_term;
        this.form.place_of_delivery_id = this.noa?.procurement_quotation?.place_of_delivery_id;
        this.form.noa_id = this.noa?.id;
        this.form.option = "";
      }
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
