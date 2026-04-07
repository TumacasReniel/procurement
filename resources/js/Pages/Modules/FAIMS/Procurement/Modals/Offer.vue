<template>
  <b-modal
    v-model="showModal"
    header-class="p-3"
    title="Edit Bid Offer"
    size="xl"
    class="v-modal-custom"
    modal-class="zoomIn"
    centered
    no-close-on-backdrop
  >
    <form class="customform">
      <BRow>
        <BCol lg="6" class="mt-2">
          <InputLabel value="Bid Price" :message="form.errors.bid_price" />
          <Amount @amount="amount" ref="amountComponent" :readonly="isFree" />
          <div class="form-check mt-2">
            <input
              id="is-free-offer"
              v-model="isFree"
              class="form-check-input"
              type="checkbox"
              @change="onToggleFree"
            />
            <label class="form-check-label" for="is-free-offer">
              Free
            </label>
          </div>
          <small class="text-muted d-block mt-1">
            Leave blank or enter 0 to keep this bid not set. Check Free for a free offer.
          </small>
        </BCol>
        <BCol lg="6" class="mt-2">
          <InputLabel value="Delivery Term" />
          <TextInput
            v-model="form.delivery_term"
            type="text"
            class="form-control"
            :light="true"
          />
        </BCol>
        <BCol lg="12" class="mt-2">
          <InputLabel value="Technical Proposal" />
          <CustomEditorMini v-model="form.technical_proposal" :modal-size="modal_size" />
        </BCol>

        <BCol lg="12"><hr class="text-muted mt-4 mb-0" /></BCol>
      </BRow>
    </form>

    <template v-slot:footer>
      <b-button @click="hide()" variant="light" block>Cancel</b-button>
      <b-button
        @click="updateItemBidOffer(form)"
        variant="primary"
        :disabled="form.processing"
        block
        >Update</b-button
      >
    </template>
  </b-modal>
</template>
<script>
import axios from "axios";
import { useForm } from "@inertiajs/vue3";
import Multiselect from "@vueform/multiselect";
import InputError from "@/Shared/Components/Forms/InputError.vue";
import InputLabel from "@/Shared/Components/Forms/InputLabel.vue";
import TextInput from "@/Shared/Components/Forms/TextInput.vue";
import Amount from "@/Shared/Components/Forms/Amount.vue";
import CustomEditorMini from "@/Shared/Components/Forms/CustomEditorMini.vue";

export default {
  components: {
    Amount,
    InputError,
    InputLabel,
    TextInput,
    Multiselect,
    CustomEditorMini
  },
  props: ["dropdowns"],
  data() {
    return {
      currentUrl: window.location.origin,
      form: useForm({
        id: null,
        bid_price: null,
        technical_proposal: "",
        delivery_term: "7 days upon received of PO",
      }),
      action_type: null,
      showModal: false,
      isFree: false,
      currentItem: null,
      currentBid: null,

    };
  },

  watch: {
    "form.item_unit_id": function (value) {
      if (value) {
        this.getItemUnitType(value);
      }
    },
  },

  methods: {
    amount(val) {
      if (this.isFree) return;
      this.form.bid_price = this.cleanCurrency(val);
    },
    cleanCurrency(value) {
      if (value === null || value === undefined || value === "") return null;
      const cleaned = value.toString().replace(/[^0-9.]/g, "");
      if (cleaned === "") return null;
      const parsed = parseFloat(cleaned);
      return Number.isNaN(parsed) ? null : parsed;
    },
    edit(item, bid = null) {
      this.currentItem = item;
      this.currentBid = bid;
      this.form.id = item.id;
      if (item.technical_proposal) {
        this.form.technical_proposal = item.technical_proposal;
      } else {
        this.form.technical_proposal = item.item.item_description;
      }

      if (item.delivery_term) {
        this.form.delivery_term = item.delivery_term;
      } else {
        this.form.delivery_term = "7 days upon received of Notice to Proceed";
      }
      this.isFree = Boolean(item.is_free);
      this.form.bid_price = this.normalizeBidPrice(item.bid_price, this.isFree);
      this.showModal = true;
      this.$nextTick(() => {
        if (this.$refs.amountComponent) {
          this.$refs.amountComponent.emitValue(
            this.isFree
              ? "0.00"
              : this.form.bid_price === null
                ? ""
                : Number(this.form.bid_price).toFixed(2)
          );
        }
      });
    },

    updateItemBidOffer() {
      this.form.bid_price = this.normalizeBidPrice(this.form.bid_price, this.isFree);
      this.form.processing = true;
      axios
        .post("/faims/offers", {
          id: this.form.id,
          bid_price: this.form.bid_price,
          is_free: this.isFree,
          technical_proposal: this.form.technical_proposal,
          delivery_term: this.form.delivery_term,
        })
        .then(() => {
          if (this.currentItem) {
            this.currentItem.bid_price = this.form.bid_price;
            this.currentItem.is_free = this.isFree;
            this.currentItem.technical_proposal = this.form.technical_proposal;
          }
          if (this.currentBid) {
            this.currentBid.delivery_term = this.form.delivery_term;
          }
          this.hide();
        })
        .catch(() => {
          console.error("Failed to update bid price");
        })
        .finally(() => {
          this.form.processing = false;
        });
    },

    hide() {
      this.form.reset();
      this.isFree = false;
      if (this.$refs.amountComponent) {
        this.$refs.amountComponent.emitValue("");
      }
      this.currentItem = null;
      this.currentBid = null;
      this.showModal = false;
    },
    onToggleFree() {
      if (!this.$refs.amountComponent) return;
      if (this.isFree) {
        this.form.bid_price = 0;
        this.$refs.amountComponent.emitValue("0.00");
      } else {
        this.form.bid_price = null;
        this.$refs.amountComponent.emitValue("");
      }
    },
    normalizeBidPrice(value, isFree = false) {
      if (isFree) return 0;
      const cleaned = this.cleanCurrency(value);
      return cleaned === null || cleaned <= 0 ? null : cleaned;
    },
  },
};
</script>
