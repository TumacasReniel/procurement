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
        <BCol lg="12" class="mt-2">
          <div class="offer-edit-summary">
            <div class="offer-edit-summary__header">
              <div>
                <div class="offer-edit-summary__eyebrow">Item Being Edited</div>
                <div class="offer-edit-summary__title">
                  {{ editingItemDetails.item_name }}
                </div>
              </div>
              <span class="offer-edit-summary__status">
                {{ editingItemDetails.status_name }}
              </span>
            </div>

            <div class="offer-edit-summary__grid">
              <div class="offer-edit-summary__card">
                <span class="offer-edit-summary__label">Item No.</span>
                <span class="offer-edit-summary__value">{{ editingItemDetails.item_no }}</span>
              </div>
              <div class="offer-edit-summary__card">
                <span class="offer-edit-summary__label">Supplier</span>
                <span class="offer-edit-summary__value">{{ editingItemDetails.supplier_name }}</span>
              </div>
              <div class="offer-edit-summary__card">
                <span class="offer-edit-summary__label">Quantity / Unit</span>
                <span class="offer-edit-summary__value">{{ editingItemDetails.quantity_label }}</span>
              </div>
              <div class="offer-edit-summary__card">
                <span class="offer-edit-summary__label">Unit Cost</span>
                <span class="offer-edit-summary__value">{{ formatCurrency(editingItemDetails.unit_cost) }}</span>
              </div>
              <div class="offer-edit-summary__card">
                <span class="offer-edit-summary__label">ABC</span>
                <span class="offer-edit-summary__value">{{ formatCurrency(editingItemDetails.abc) }}</span>
              </div>
              <div class="offer-edit-summary__card">
                <span class="offer-edit-summary__label">Current Bid</span>
                <span class="offer-edit-summary__value">{{ editingItemDetails.current_bid_label }}</span>
              </div>
            </div>

            <div
              v-if="editingItemDetails.description"
              class="offer-edit-summary__description"
            >
              <div class="offer-edit-summary__description-header">
                <div class="offer-edit-summary__label mb-0">Item Description</div>
                <button
                  type="button"
                  class="btn btn-sm btn-outline-secondary offer-edit-summary__toggle"
                  @click="isItemDescriptionExpanded = !isItemDescriptionExpanded"
                >
                  {{ isItemDescriptionExpanded ? "Hide Description" : "View Description" }}
                </button>
              </div>
              <div v-if="isItemDescriptionExpanded" class="offer-edit-summary__description-body">
                <div v-html="editingItemDetails.description"></div>
              </div>
            </div>
          </div>
        </BCol>

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
      isItemDescriptionExpanded: false,

    };
  },

  computed: {
    isBidPriceLocked() {
      return this.isFree || this.isNoOffer || this.isNotApplicable;
    },
    isTechnicalProposalLocked() {
      return this.isNoOffer || this.isNotApplicable;
    },
    editingItemDetails() {
      const procurementItem = this.currentItem?.item || {};
      const quantity = Number(procurementItem?.item_quantity) || 0;
      const unitType = procurementItem?.item_unit_type || {};
      const currentBid = Number(this.currentItem?.bid_price);

      return {
        item_name: procurementItem?.item_name || "-",
        item_no: procurementItem?.item_no || "-",
        supplier_name: this.currentBid?.supplier?.name || "-",
        status_name: this.currentItem?.status?.name || "Pending",
        quantity_label: this.formatQuantityLabel(quantity, unitType),
        unit_cost: Number(procurementItem?.item_unit_cost) || 0,
        abc: Number(procurementItem?.total_cost) || 0,
        current_bid_label: this.offerDisplayValue(currentBid, this.currentItem),
        description: procurementItem?.item_description || "",
      };
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
    formatCurrency(value) {
      const amount = Number(value) || 0;
      return new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
      }).format(amount);
    },
    formatQuantityLabel(quantity, unitType = {}) {
      const qty = Number(quantity);
      const unitLabel =
        qty > 1
          ? unitType?.name_long || unitType?.name_short
          : unitType?.name_short || unitType?.name_long;

      if (!Number.isFinite(qty) || qty <= 0) {
        return unitLabel || "-";
      }

      return `${qty} ${unitLabel || ""}`.trim();
    },
    offerDisplayValue(value, options = {}) {
      if (options?.is_free) {
        return "free";
      }

      if (options?.is_no_offer) {
        return "no offer";
      }

      if (options?.is_not_applicable) {
        return "not applicable";
      }

      const amount = Number(value);
      if (amount > 0) {
        return this.formatCurrency(amount);
      }

      return "not set";
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
      this.isItemDescriptionExpanded = false;
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
            this.currentItem.is_checked = this.canKeepAwardSelection(this.currentItem);
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
      this.isItemDescriptionExpanded = false;
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
    canKeepAwardSelection(item) {
      if (item?.is_free) {
        return Boolean(item?.is_checked);
      }

      if (item?.is_no_offer || item?.is_not_applicable) {
        return false;
      }

      const bidPrice = Number(item?.bid_price) || 0;
      const unitCost = Number(item?.item?.item_unit_cost) || 0;

      if (!(bidPrice > 0)) {
        return false;
      }

      if (unitCost > 0 && bidPrice > unitCost) {
        return false;
      }

      return Boolean(item?.is_checked);
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

<style scoped>
.offer-edit-summary {
  background: linear-gradient(180deg, #f8fbff 0%, #f1f5f9 100%);
  border: 1px solid #dbe4f0;
  border-radius: 16px;
  padding: 1rem;
}

.offer-edit-summary__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: 1rem;
}

.offer-edit-summary__eyebrow {
  font-size: 0.72rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: #64748b;
}

.offer-edit-summary__title {
  font-size: 1rem;
  font-weight: 700;
  color: #1e293b;
  line-height: 1.4;
}

.offer-edit-summary__status {
  padding: 0.45rem 0.75rem;
  border-radius: 999px;
  background: #ffffff;
  border: 1px solid #dbe4f0;
  color: #334155;
  font-size: 0.8rem;
  font-weight: 700;
  white-space: nowrap;
}

.offer-edit-summary__grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
  gap: 0.75rem;
}

.offer-edit-summary__card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 0.85rem 0.95rem;
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
}

.offer-edit-summary__label {
  font-size: 0.72rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: #64748b;
}

.offer-edit-summary__value {
  font-size: 0.96rem;
  font-weight: 700;
  color: #1e293b;
  word-break: break-word;
}

.offer-edit-summary__description {
  margin-top: 1rem;
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 0.95rem;
  color: #334155;
}

.offer-edit-summary__description-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
}

.offer-edit-summary__description-body {
  margin-top: 0.9rem;
}

.offer-edit-summary__toggle {
  flex-shrink: 0;
  border-radius: 999px;
}

.offer-edit-summary__description :deep(p:last-child),
.offer-edit-summary__description :deep(ul:last-child),
.offer-edit-summary__description :deep(ol:last-child) {
  margin-bottom: 0;
}

.offer-edit-summary__description :deep(ul),
.offer-edit-summary__description :deep(ol) {
  padding-left: 1.25rem;
}

[data-bs-theme="dark"] .offer-edit-summary {
  background: #1b2230;
  border-color: rgba(148, 163, 184, 0.18);
}

[data-bs-theme="dark"] .offer-edit-summary__card,
[data-bs-theme="dark"] .offer-edit-summary__description,
[data-bs-theme="dark"] .offer-edit-summary__status {
  background: #232c3a;
  border-color: rgba(148, 163, 184, 0.18);
}

[data-bs-theme="dark"] .offer-edit-summary__eyebrow,
[data-bs-theme="dark"] .offer-edit-summary__label {
  color: #94a3b8;
}

[data-bs-theme="dark"] .offer-edit-summary__title,
[data-bs-theme="dark"] .offer-edit-summary__value,
[data-bs-theme="dark"] .offer-edit-summary__status,
[data-bs-theme="dark"] .offer-edit-summary__description {
  color: #e5edf7;
}
</style>
