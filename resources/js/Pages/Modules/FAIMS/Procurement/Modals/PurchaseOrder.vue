<template>
  <b-modal
    v-model="showModal"
    header-class="p-3"
    :title="modalTitle"
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
            :modelValue="currentNoa?.code || ''"
            type="text"
            class="form-control"
            :light="true"
            readonly
          />
        </BCol>

        <BCol lg="6" class="mt-2">
          <InputLabel value="Supplier" />
          <TextInput
            :modelValue="supplierName"
            type="text"
            class="form-control"
            :light="true"
            readonly
          />
        </BCol>

        <BCol lg="6" class="mt-2">
          <InputLabel value="Supplier Address" />
          <TextInput
            :modelValue="supplierAddress"
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
        >{{ isEditing ? "Update" : "Save" }}</b-button
      >
    </template>
  </b-modal>
</template>
<script>
import { useForm } from "@inertiajs/vue3";
import axios from "axios";
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
        supplier: null,
        place_of_delivery_id: null,
        date_of_delivery: this.getDatePlusWorkingDays(15),
        delivery_term: null,
        payment_term: "within 30 calendar days after IAR",
        items: [],
        option: "",
      }),
      showModal: false,
      currentNoa: this.noa,
    };
  },

  computed: {
    isEditing() {
      return this.form.option === "update";
    },
    modalTitle() {
      return this.isEditing ? "Edit Purchase Order" : "Create Purchase Order";
    },
    supplierName() {
      return this.form.supplier?.name || "";
    },
    supplierAddress() {
      return this.form.supplier?.address?.address || "";
    },
    totalAmount() {
      return this.form.items.reduce((sum, item) => {
        return sum + item.item.bid_price * item.item.item.item_quantity;
      }, 0);
    },
  },

  methods: {
    show(existingPO = null) {
      this.form.clearErrors();
      this.showModal = true;

      if (existingPO) {
        this.currentNoa = existingPO.noa || this.noa;
        this.form.id = existingPO.id;
        this.form.procurement_id = existingPO.procurement_id || this.procurement.id;
        this.form.noa_id = existingPO.noa_id || this.currentNoa?.id;
        this.form.po_date = this.normalizeDateInput(existingPO.po_date);
        this.form.code = existingPO.code;
        this.form.supplier = this.currentNoa?.procurement_quotation?.supplier || null;
        this.form.place_of_delivery_id =
          existingPO.place_of_delivery_id ||
          this.currentNoa?.procurement_quotation?.place_of_delivery_id ||
          null;
        this.form.date_of_delivery = this.normalizeDateInput(existingPO.date_of_delivery);
        this.form.delivery_term = existingPO.delivery_term;
        this.form.payment_term = existingPO.payment_term;
        this.form.items = this.currentNoa?.items || [];
        this.form.option = "update";
      } else {
        this.currentNoa = this.noa;
        this.form.id = null;
        this.form.procurement_id = this.procurement.id;
        this.form.noa_id = this.currentNoa?.id;
        this.form.po_date = this.getCurrentDate();
        this.form.code = null;
        this.form.supplier = this.currentNoa?.procurement_quotation?.supplier || null;
        this.form.delivery_term = this.currentNoa?.procurement_quotation?.delivery_term || null;
        this.form.place_of_delivery_id =
          this.currentNoa?.procurement_quotation?.place_of_delivery_id || null;
        this.form.date_of_delivery = this.getDatePlusWorkingDays(15);
        this.form.payment_term = "within 30 calendar days after IAR";
        this.form.items = this.currentNoa?.items || [];
        this.form.option = "";
      }
    },
    hide() {
      this.form.reset();
      this.showModal = false;
      this.currentNoa = this.noa;
    },

    formatCurrency(value) {
      return new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
      }).format(value);
    },

    submit() {
      if (this.isEditing) {
        this.form.clearErrors();
        this.form.processing = true;

        axios
          .put(
            `/faims/purchase-orders/${this.form.id}`,
            {
              procurement_id: this.form.procurement_id,
              noa_id: this.form.noa_id,
              po_date: this.form.po_date,
              place_of_delivery_id: this.form.place_of_delivery_id,
              date_of_delivery: this.form.date_of_delivery,
              delivery_term: this.form.delivery_term,
              payment_term: this.form.payment_term,
              option: "update",
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
                "po_date",
                response?.data?.info || "Unable to update this Purchase Order."
              );
              return;
            }

            this.$emit("add", true);
            this.hide();
          })
          .catch((error) => {
            if (error.response?.status === 422 && error.response?.data?.errors) {
              this.form.setError(error.response.data.errors);
              return;
            }

            console.error("Update failed");
          })
          .finally(() => {
            this.form.processing = false;
          });
        return;
      }

      this.form.post("/faims/purchase-orders", {
        onSuccess: () => {
          this.$emit("add", true);
          this.hide();
        },
        onError: () => {
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
