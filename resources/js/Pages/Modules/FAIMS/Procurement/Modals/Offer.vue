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
          <Amount @amount="amount" ref="amountComponent" :readonly="isBidPriceLocked" />
          <div class="d-flex flex-wrap gap-3 mt-2">
            <div class="form-check">
              <input
                id="is-free-offer"
                v-model="isFree"
                class="form-check-input"
                type="checkbox"
                @change="onToggleOfferState('free')"
              />
              <label class="form-check-label" for="is-free-offer">
                Free
              </label>
            </div>
            <div class="form-check">
              <input
                id="is-no-offer"
                v-model="isNoOffer"
                class="form-check-input"
                type="checkbox"
                @change="onToggleOfferState('no_offer')"
              />
              <label class="form-check-label" for="is-no-offer">
                No Offer
              </label>
            </div>
            <div class="form-check">
              <input
                id="is-not-applicable-offer"
                v-model="isNotApplicable"
                class="form-check-input"
                type="checkbox"
                @change="onToggleOfferState('not_applicable')"
              />
              <label class="form-check-label" for="is-not-applicable-offer">
                Not Applicable
              </label>
            </div>
          </div>
         
        </BCol>
    
        <BCol lg="6" class="mt-2">
          <InputLabel value="Delivery Term (Supplier / RFQ)" />
          <TextInput
            v-model="form.delivery_term"
            type="text"
            class="form-control"
            :light="true"
          />
          <small class="text-muted d-block mt-1">
            This delivery term is shared by the selected supplier/RFQ across its offered items.
          </small>
        </BCol>

           <BCol lg="12" class="mt-2">
           <small class="text-muted mt-1">
            Leave blank or enter 0 to keep this bid not set. Use the checkboxes for Free, No Offer, or Not Applicable when needed.
          </small>
        </BCol>
        <BCol lg="12" class="mt-2">
          <InputLabel value="Technical Proposal" />
          <CustomEditorMini
            v-model="form.technical_proposal"
            :modal-size="modal_size"
            :disabled="isTechnicalProposalLocked"
          />
          <small v-if="isTechnicalProposalLocked" class="text-muted d-block mt-2">
            Technical proposal is cleared automatically when the offer is marked as No Offer or Not Applicable.
          </small>
        </BCol>

        <BCol lg="12"><hr class="text-muted mt-4 mb-0" /></BCol>
        <BCol lg="12" class="mt-3">
          <div class="form-check">
            <input
              id="confirm-final-offer"
              v-model="confirmFinalOffer"
              class="form-check-input"
              type="checkbox"
            />
            <label class="form-check-label fw-semibold" for="confirm-final-offer">
              I confirm this edited offer has been reviewed and is final.
            </label>
          </div>
          <small class="text-muted d-block mt-1">
            This confirmation helps ensure the procurement officer or encoder is saving the final version of the offer.
          </small>
        </BCol>
      </BRow>
    </form>

    <template v-slot:footer>
      <b-button @click="hide()" variant="light" block>Cancel</b-button>
      <b-button
        @click="updateItemBidOffer(form)"
        variant="primary"
        :disabled="form.processing || !confirmFinalOffer"
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
        delivery_term: "7 days upon received of Notice to Proceed",
      }),
      action_type: null,
      showModal: false,
      isFree: false,
      isNoOffer: false,
      isNotApplicable: false,
      confirmFinalOffer: false,
      currentItem: null,
      currentBid: null,
      lastEditableTechnicalProposal: "",

    };
  },

  computed: {
    isBidPriceLocked() {
      return this.isFree || this.isNoOffer || this.isNotApplicable;
    },
    isTechnicalProposalLocked() {
      return this.isNoOffer || this.isNotApplicable;
    },
  },

  watch: {
    "form.bid_price"() {
      this.resetFinalConfirmation();
    },
    "form.delivery_term"() {
      this.resetFinalConfirmation();
    },
    "form.technical_proposal"() {
      this.resetFinalConfirmation();
    },
    isFree() {
      this.resetFinalConfirmation();
    },
    isNoOffer() {
      this.resetFinalConfirmation();
    },
    isNotApplicable() {
      this.resetFinalConfirmation();
    },
  },

  methods: {
    resetFinalConfirmation() {
      if (this.showModal && this.confirmFinalOffer) {
        this.confirmFinalOffer = false;
      }
    },
    amount(val) {
      if (this.isBidPriceLocked) return;
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
      this.lastEditableTechnicalProposal =
        item.technical_proposal || item.item?.item_description || "";

      if (item.is_no_offer || item.is_not_applicable) {
        this.form.technical_proposal = "";
      } else if (item.technical_proposal) {
        this.form.technical_proposal = item.technical_proposal;
      } else {
        this.form.technical_proposal = item.item.item_description;
      }

      this.form.delivery_term =
        bid?.delivery_term ||
        item?.delivery_term ||
        "7 days upon received of Notice to Proceed";
      this.isFree = Boolean(item.is_free);
      this.isNoOffer = Boolean(item.is_no_offer);
      this.isNotApplicable = Boolean(item.is_not_applicable);
      this.confirmFinalOffer = false;
      this.form.bid_price = this.normalizeBidPrice(
        item.bid_price,
        this.isFree,
        this.isNoOffer,
        this.isNotApplicable
      );
      this.showModal = true;
      this.$nextTick(() => {
        this.syncAmountInput();
      });
    },

    updateItemBidOffer() {
      const offerFlags = this.normalizeOfferFlags();
      this.form.bid_price = this.normalizeBidPrice(
        this.form.bid_price,
        offerFlags.isFree,
        offerFlags.isNoOffer,
        offerFlags.isNotApplicable
      );
      this.form.technical_proposal = this.normalizeTechnicalProposal(
        this.form.technical_proposal,
        offerFlags.isNoOffer,
        offerFlags.isNotApplicable
      );
      this.form.processing = true;
      axios
        .post("/faims/offers", {
          id: this.form.id,
          bid_price: this.form.bid_price,
          is_free: offerFlags.isFree,
          is_no_offer: offerFlags.isNoOffer,
          is_not_applicable: offerFlags.isNotApplicable,
          technical_proposal: this.form.technical_proposal,
          delivery_term: this.form.delivery_term,
        })
        .then(() => {
          if (this.currentItem) {
            this.currentItem.bid_price = this.form.bid_price;
            this.currentItem.is_free = offerFlags.isFree;
            this.currentItem.is_no_offer = offerFlags.isNoOffer;
            this.currentItem.is_not_applicable = offerFlags.isNotApplicable;
            this.currentItem.technical_proposal = this.form.technical_proposal;
            this.currentItem.delivery_term = this.form.delivery_term;
          }
          if (this.currentBid) {
            this.currentBid.delivery_term = this.form.delivery_term;
            (this.currentBid.items || []).forEach((bidItem) => {
              bidItem.delivery_term = this.form.delivery_term;
            });
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
      this.isNoOffer = false;
      this.isNotApplicable = false;
      this.confirmFinalOffer = false;
      this.lastEditableTechnicalProposal = "";
      this.syncAmountInput();
      this.currentItem = null;
      this.currentBid = null;
      this.showModal = false;
    },
    normalizeOfferFlags() {
      const isFree = Boolean(this.isFree);
      const isNoOffer = !isFree && Boolean(this.isNoOffer);
      const isNotApplicable = !isFree && !isNoOffer && Boolean(this.isNotApplicable);

      this.isFree = isFree;
      this.isNoOffer = isNoOffer;
      this.isNotApplicable = isNotApplicable;

      return { isFree, isNoOffer, isNotApplicable };
    },
    syncAmountInput() {
      if (!this.$refs.amountComponent) return;

      const offerFlags = this.normalizeOfferFlags();

      if (offerFlags.isFree) {
        this.form.bid_price = 0;
        this.$refs.amountComponent.emitValue("0.00");
        return;
      }

      if (offerFlags.isNoOffer || offerFlags.isNotApplicable) {
        this.form.bid_price = null;
        this.$refs.amountComponent.emitValue("");
        return;
      }

      const normalizedBidPrice = this.normalizeBidPrice(this.form.bid_price);
      this.form.bid_price = normalizedBidPrice;
      this.$refs.amountComponent.emitValue(
        normalizedBidPrice === null ? "" : Number(normalizedBidPrice).toFixed(2)
      );
    },
    syncTechnicalProposal() {
      if (this.isTechnicalProposalLocked) {
        this.rememberTechnicalProposal();
        this.form.technical_proposal = "";
        return;
      }

      if (!this.normalizeTechnicalProposal(this.form.technical_proposal)) {
        this.form.technical_proposal = this.getDefaultTechnicalProposal();
      }
    },
    getDefaultTechnicalProposal() {
      return (
        this.lastEditableTechnicalProposal ||
        this.currentItem?.technical_proposal ||
        this.currentItem?.item?.item_description ||
        ""
      );
    },
    rememberTechnicalProposal(value = this.form.technical_proposal) {
      const normalized = this.normalizeTechnicalProposal(value);

      if (normalized) {
        this.lastEditableTechnicalProposal = normalized;
        return;
      }

      const fallback = this.currentItem?.item?.item_description || "";
      if (fallback) {
        this.lastEditableTechnicalProposal = fallback;
      }
    },
    onToggleOfferState(type) {
      if (type === "free" && this.isFree) {
        this.isNoOffer = false;
        this.isNotApplicable = false;
      }

      if (type === "no_offer" && this.isNoOffer) {
        this.isFree = false;
        this.isNotApplicable = false;
      }

      if (type === "not_applicable" && this.isNotApplicable) {
        this.isFree = false;
        this.isNoOffer = false;
      }

      if (type === "free" && !this.isFree) {
        this.isFree = false;
      }

      if (type === "no_offer" && !this.isNoOffer) {
        this.isNoOffer = false;
      }

      if (type === "not_applicable" && !this.isNotApplicable) {
        this.isNotApplicable = false;
      }

      this.syncAmountInput();
      this.syncTechnicalProposal();
    },
    normalizeBidPrice(
      value,
      isFree = false,
      isNoOffer = false,
      isNotApplicable = false
    ) {
      if (isFree) return 0;
      if (isNoOffer || isNotApplicable) return null;
      const cleaned = this.cleanCurrency(value);
      return cleaned === null || cleaned <= 0 ? null : cleaned;
    },
    normalizeTechnicalProposal(value, isNoOffer = false, isNotApplicable = false) {
      if (isNoOffer || isNotApplicable) {
        return null;
      }

      if (value === null || value === undefined) {
        return null;
      }

      const plainText = value
        .toString()
        .replace(/<[^>]*>/g, " ")
        .replace(/\s+/g, " ")
        .trim();

      return plainText ? value : null;
    },
  },
};
</script>
