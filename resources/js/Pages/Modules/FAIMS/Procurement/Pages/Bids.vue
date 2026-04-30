<template>
  
  <div >
  <div>
  <PageHeader class="pt-2" title="Abstract of Bids"/>
    <div >
      <div class="aob-header-left">
      
        <div class="btn-group aob-view-toggle" role="group" aria-label="AOB view mode">
          <button
            type="button"
            class="btn btn-sm"
            :class="aobViewMode === 'supplier' ? 'btn-primary' : 'btn-outline-primary'"
            @click="setAobViewMode('supplier')"
          >
            By Supplier
          </button>
          <button
            type="button"
            class="btn btn-sm"
            :class="aobViewMode === 'items' ? 'btn-primary' : 'btn-outline-primary'"
            @click="setAobViewMode('items')"
          >
            All Items
          </button>
        </div>
      </div>
      <div class="aob-header-right d-flex flex-wrap justify-content-end gap-1">
        <button
          v-if="canGenerateBACResolution"
          @click="openBACReso(procurement)"
          class="btn btn-outline-primary btn-sm me-2"
          v-b-tooltip.hover
          title="Generate BAC Resolution"
        >
          <i class="ri-file-line"></i>
          Generate BAC Resolution
        </button>

        <button
          v-if="
            canRecommendBidForAward &&
            hasCheckedBidItems
          "
          class="btn btn-primary btn-sm me-2"
          @click="openRecommendAward()"
          v-b-tooltip.hover
          title="Save Bids For Award"
        >
          <i class="ri-save-line"></i>
          Save Bids For Award
        </button>

        <button
          v-if="procurement.quotations.length > 0"
          @click="printBids(procurement)"
          class="btn btn-outline-dark btn-sm me-2"
          v-b-tooltip.hover
          title="Print"
        >
          <i class="ri-printer-line"></i>
          Print AOB
        </button>
      </div>
    </div>
  </div>
  <div
    v-if="procurement.quotations.filter((bid) => isPendingBid(bid)).length === 0"
    class="text-center py-5"
  >
    <div class="empty-state">
      <div class="empty-state-icon">
        <i class="ri-auction-line"></i>
      </div>
      <h6 class="empty-state-title">No Abstract of Bids</h6>
      <p class="empty-state-message">
        No bids have been submitted for this procurement yet.
      </p>
    </div>
  </div>

  <template v-else>
  <b-tabs
    v-if="aobViewMode === 'supplier'"
    v-model="activeBidTab"
    @update:modelValue="persistActiveBidTab"
    class="horizontal-scroll-tabs bg-white"
  >
    <template
      v-for="(bid, bidIndex) in availableBids"
      :key="bid.id"
    >
      <b-tab>
        <template #title>
          {{ bid.supplier.name }}

          <b-badge variant="primary" v-if="getSupplierCheckedCount(bid) > 0">
            {{ getSupplierCheckedCount(bid) }}
          </b-badge>
        </template>

        <div>
          <div
            class="w-100 pt-0 pb-0 aob-scroll-area"
            style="height: calc(100vh - 305px); overflow-y: auto; overflow-x: hidden"
            ref="box"
          >
            <div>
              <div class="aob-table-wrap">
              <table class="aob-table aob-supplier-table">
                <thead>
                  <tr>
                    <th class="aob-col-item-no">Item No</th>
                    <th class="aob-col-status">Status</th>
                    <th class="aob-col-description">Description</th>
                    <th class="aob-col-qty">Quantity / Unit</th>
                    <th class="aob-col-money">Unit Cost</th>
                    <th class="aob-col-money">ABC</th>
                    <th class="aob-col-money">Bid Price</th>
                    <th class="aob-col-money">Total Bid Price</th>
                    <th class="aob-col-offer">Technical Proposal / Offer</th>
                    <th class="aob-col-delivery">Delivery Term</th>

                    <th
                      class="aob-col-check"
                      v-if="canRecommendBidForAward"
                    >
                      Recommend Bid For Award?
                    </th>
                  </tr>
                </thead>

                <tbody>
                  <tr v-for="(item, itemIndex) in bid.items" :key="item.item_id">
                    <td class="aob-cell-index">{{ itemIndex + 1 }}</td>
                    <td class="aob-cell-status">
                      <b-badge
                        v-if="item.status"
                        :variant="getBadgeVariant(item.status.name)"
                        class="aob-status-badge"
                        style="color: white"
                      >
                        {{ item.status.name }}
                        <i
                          v-if="item.status.name == 'Not Available for Award'"
                          class="ri-close-line"
                        ></i>
                        <i
                          v-if="item.status.name == 'Available for Award'"
                          class="ri-check-line"
                        ></i>
                        <i v-if="item.status.name == 'Awarded'" class="ri-check-line"></i>
                      </b-badge>
                      <span v-else class="aob-muted-text">No status</span>
                    </td>
                    <td class="aob-cell-action">
                      <button
                        type="button"
                        class="btn btn-outline-secondary btn-sm aob-action-btn aob-action-btn-muted"
                        @click="openItemDescription(item.item)"
                      >
                        View Description
                      </button>
                    </td>

                    <td class="aob-cell-qty">
                      <div class="aob-qty-value">{{ item.item.item_quantity }}</div>
                      <div class="aob-qty-unit">
                        {{
                          item.item.item_quantity > 1
                            ? item.item.item_unit_type.name_long
                            : item.item.item_unit_type.name_short
                        }}
                      </div>
                    </td>
                    <td class="aob-cell-money">
                      {{ formatCurrency(item.item.item_unit_cost) }}
                    </td>
                    <td class="aob-cell-money">
                      {{ formatCurrency(item.item.total_cost) }}
                    </td>

                    <td
                      class="aob-cell-money"
                      :class="{ 'aob-clickable-cell': canEditOffer() }"
                      @click="openEditOffer(item, bid)"
                    >
                      <span v-if="item.is_free" class="aob-price aob-price-free">
                        free
                      </span>
                      <span v-else-if="item.is_no_offer" class="aob-price aob-price-mutedstate">
                        no offer
                      </span>
                      <span v-else-if="item.is_not_applicable" class="aob-price aob-price-mutedstate">
                        not applicable
                      </span>
                      <span
                        v-else-if="Number(item.bid_price) > 0"
                        :class="[
                          'aob-price',
                          'aob-price-link',
                          {
                            'aob-price-over': Number(item.bid_price) > item.item.item_unit_cost,
                          },
                        ]"
                      >
                        {{ formatCurrency(item.bid_price) }}
                      </span>
                      <span
                        v-else
                        :class="[
                          'aob-price',
                          'aob-price-pending',
                          { 'aob-price-editable': canEditOffer() },
                        ]"
                      >
                        not set
                      </span>
                    </td>
                    <td class="aob-cell-money">
                      <span v-if="item.is_free" class="aob-price aob-price-free">
                        free
                      </span>
                      <span v-else-if="item.is_no_offer" class="aob-price aob-price-mutedstate">
                        no offer
                      </span>
                      <span v-else-if="item.is_not_applicable" class="aob-price aob-price-mutedstate">
                        not applicable
                      </span>
                      <span v-else-if="!(Number(item.bid_price) > 0)" class="aob-price aob-price-pending">
                        not set
                      </span>
                      <span v-else>
                        {{ formatCurrency(item.item.item_quantity * item.bid_price) }}
                      </span>
                    </td>

                    <td class="aob-cell-action">
                      <button
                        type="button"
                        class="btn btn-outline-secondary btn-sm aob-action-btn aob-action-btn-muted"
                        @click="openOfferDescription(item, bid)"
                      >
                        View Offer
                      </button>
                    </td>
                    <td class="aob-cell-delivery">
                      {{ bid.delivery_term || "-" }}
                    </td>

                    <td
                      class="aob-cell-check"
                      v-if="canRecommendBidForAward"
                    >
                      <span class="d-flex justify-content-center">
                        <input
                          v-model="item.is_checked"
                          type="checkbox"
                          class="form-check-input aob-award-checkbox bg-primary"
                          :disabled="
                            isOtherSupplierChecked(itemIndex, bid) ||
                            !hasAwardableOffer(item) ||
                            isBidPriceOverUnitCost(item)
                          "
                          @change="handleCheckboxChange(itemIndex, bid)"
                        />
                      </span>
                    </td>
                  </tr>
                </tbody>
              </table>
              </div>

              <Pagination
                class="ms-2 me-2"
                v-if="meta"
                @fetch="fetch"
                :lists="lists.length"
                :links="links"
                :pagination="meta"
              />
            </div>
          </div>
        </div>
      </b-tab>
    </template>
  </b-tabs>

  <div v-else class="bg-white">
    <div
      class="w-100 pt-0 pb-0 aob-scroll-area"
      style="height: calc(100vh - 305px); overflow-y: auto; overflow-x: hidden"
    >
      <div class="aob-table-wrap">
      <table class="aob-table aob-items-table">
        <thead>
          <tr>
            <th class="aob-col-item-no">Item No</th>
            <th class="aob-col-item-name">Item Name</th>
            <th class="aob-col-description">Description</th>
            <th class="aob-col-qty">Quantity / Unit</th>
            <th class="aob-col-money">Unit Cost</th>
            <th class="aob-col-money">ABC</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="item in allBidItems" :key="item.id">
            <td class="aob-cell-index">{{ item.item_no || "-" }}</td>
            <td class="aob-cell-item">
              <div class="aob-item-title">{{ item.item_name || "-" }}</div>
              <div class="aob-item-actions">
                <button
                  type="button"
                  class="btn btn-outline-primary btn-sm aob-action-btn"
                  @click="openAllOffers({ item })"
                >
                  View Item Offer
                </button>
              </div>
            </td>
            <td class="aob-cell-action">
              <button
                type="button"
                class="btn btn-outline-secondary btn-sm aob-action-btn aob-action-btn-muted"
                @click="openItemDescription(item)"
              >
                View Description
              </button>
            </td>
            <td class="aob-cell-qty">
              <div class="aob-qty-value">{{ item.item_quantity }}</div>
              <div class="aob-qty-unit">
                {{
                  item.item_quantity > 1
                    ? item.item_unit_type?.name_long
                    : item.item_unit_type?.name_short
                }}
              </div>
            </td>
            <td class="aob-cell-money">{{ formatCurrency(item.item_unit_cost) }}</td>
            <td class="aob-cell-money">{{ formatCurrency(item.total_cost) }}</td>
          </tr>
          <tr v-if="allBidItems.length === 0">
            <td colspan="6" class="aob-empty-row">No items available.</td>
          </tr>
        </tbody>
      </table>
      </div>
    </div>
  </div>
  </template>

  <b-modal
    v-model="showItemDescriptionModal"
    title="Item Description"
    :size="itemDescriptionModalSize"
    header-class="p-3 bg-light"
    class="v-modal-custom procurement-bids-modal"
    centered
    hide-footer
  >
    <div class="offer-modal-content">
      <div class="mb-2 fw-semibold">
        {{ selectedItemName || "-" }}
      </div>
      <div v-html="selectedItemDescription"></div>
    </div>
  </b-modal>

  <b-modal
    v-model="showOfferModal"
    title="Offer Details"
    :size="offerModalSize"
    header-class="p-3 bg-light"
    class="v-modal-custom procurement-bids-modal"
    centered
    hide-footer
  >
    <div class="offer-modal-content">
      <div class="mb-2 fw-semibold">
        {{ selectedItemName || "-" }}
      </div>
      <div v-if="selectedOfferDetails" class="offer-detail-summary mb-3">
        <div class="offer-detail-grid">
          <div class="offer-detail-card">
            <span class="offer-detail-label">Supplier</span>
            <span class="offer-detail-value">{{ selectedOfferDetails.supplier_name }}</span>
          </div>
          <div class="offer-detail-card">
            <span class="offer-detail-label">Status</span>
            <span class="offer-detail-value">{{ selectedOfferDetails.status_name }}</span>
          </div>
          <div class="offer-detail-card">
            <span class="offer-detail-label">Bid Price</span>
            <span class="offer-detail-value">{{ offerDisplayValue(selectedOfferDetails.bid_price, selectedOfferDetails) }}</span>
          </div>
          <div class="offer-detail-card">
            <span class="offer-detail-label">Total Bid Price</span>
            <span class="offer-detail-value">{{ offerDisplayValue(selectedOfferDetails.total_bid_price, selectedOfferDetails) }}</span>
          </div>
          <div class="offer-detail-card">
            <span class="offer-detail-label">Delivery Term</span>
            <span class="offer-detail-value">{{ selectedOfferDetails.delivery_term }}</span>
          </div>
          <div class="offer-detail-card">
            <span class="offer-detail-label">Quantity / Unit</span>
            <span class="offer-detail-value">{{ selectedOfferDetails.quantity_label }}</span>
          </div>
          <div class="offer-detail-card">
            <span class="offer-detail-label">Unit Cost</span>
            <span class="offer-detail-value">{{ offerDisplayValue(selectedOfferDetails.unit_cost) }}</span>
          </div>
          <div class="offer-detail-card">
            <span class="offer-detail-label">ABC</span>
            <span class="offer-detail-value">{{ offerDisplayValue(selectedOfferDetails.abc) }}</span>
          </div>
        </div>
      </div>
      <div class="offer-detail-section-label">Technical Proposal / Offer</div>
      <div class="offer-modal-content" v-html="selectedOfferDescription"></div>
    </div>
  </b-modal>

  <b-modal
    v-model="showAllOffersModal"
    title="All Offers For Item"
    size="xl"
    header-class="p-3 bg-light"
    class="v-modal-custom procurement-bids-modal"
    centered
    hide-footer
  >
    <div class="offer-compare-summary mb-3">
      <div class="offer-compare-title">{{ selectedItemName || "-" }}</div>
      <div class="offer-compare-meta">
        <div class="offer-meta-pill">
          <span class="offer-meta-label">Item No</span>
          <span class="offer-meta-value">{{ selectedItemNo || "-" }}</span>
        </div>
        <div class="offer-meta-pill">
          <span class="offer-meta-label">Unit Price</span>
          <span class="offer-meta-value">
            {{ selectedItemUnitCost ? formatCurrency(selectedItemUnitCost) : "-" }}
          </span>
        </div>
        <div class="offer-meta-pill">
          <span class="offer-meta-label">ABC</span>
          <span class="offer-meta-value">
            {{ selectedItemAbc ? formatCurrency(selectedItemAbc) : "-" }}
          </span>
        </div>
      </div>
      <div class="offer-compare-section">
        <button
          type="button"
          class="btn btn-sm btn-outline-secondary"
          @click="toggleAllOffersDescription"
        >
          {{ isAllOffersDescriptionExpanded ? "Hide Description" : "View Description" }}
        </button>
        <div
          v-if="isAllOffersDescriptionExpanded"
          class="offer-compare-description mt-3"
          v-html="selectedItemDescription"
        ></div>
      </div>
    </div>

    <div class="table-responsive offer-compare-table-wrap">
      <table class="table align-middle mb-0 offer-compare-table">
        <thead>
          <tr>
            <th>Supplier</th>
            <th>Bid Price</th>
            <th>Total Bid Price</th>
            <th>Technical Proposal / Offer</th>
            <th>Delivery Term</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <template v-for="offer in allOffersForSelectedItem" :key="offer.supplier_id">
            <tr>
              <td class="fw-semibold">{{ offer.supplier_name }}</td>
              <td>
                <span v-if="offer.is_free"><b><i class="text-primary">free</i></b></span>
                <span v-else-if="offer.is_no_offer"><b><i class="text-muted">no offer</i></b></span>
                <span v-else-if="offer.is_not_applicable"><b><i class="text-muted">not applicable</i></b></span>
                <span v-else-if="offer.bid_price > 0">{{ formatCurrency(offer.bid_price) }}</span>
                <span v-else>-</span>
              </td>
              <td>
                <span v-if="offer.is_free"><b><i class="text-primary">free</i></b></span>
                <span v-else-if="offer.is_no_offer"><b><i class="text-muted">no offer</i></b></span>
                <span v-else-if="offer.is_not_applicable"><b><i class="text-muted">not applicable</i></b></span>
                <span v-else-if="offer.total_bid_price > 0">{{ formatCurrency(offer.total_bid_price) }}</span>
                <span v-else>-</span>
              </td>
              <td>
                <button
                  type="button"
                  class="btn btn-sm btn-outline-secondary"
                  @click="openComparedOfferModal(offer)"
                >
                  View Offer
                </button>
              </td>
              <td>{{ offer.delivery_term || "-" }}</td>
              <td>
                <b-badge
                  v-if="offer.status_name"
                  :variant="getBadgeVariant(offer.status_name)"
                  style="color: white"
                >
                  {{ offer.status_name }}
                </b-badge>
                <span v-else>-</span>
              </td>
            </tr>
          </template>
          <tr v-if="allOffersForSelectedItem.length === 0">
            <td colspan="6" class="text-center text-muted py-4">No offers available.</td>
          </tr>
        </tbody>
      </table>
    </div>
  </b-modal>

  <b-modal
    v-model="showComparedOfferModal"
    title="Supplier Offer Details"
    :size="comparedOfferModalSize"
    header-class="p-3 bg-light"
    class="v-modal-custom procurement-bids-modal"
    centered
    hide-footer
  >
    <div class="offer-modal-content">
      <div class="mb-2 fw-semibold">
        {{ selectedComparedOfferSupplier || "-" }}
      </div>
      <div class="small text-muted mb-3">
        {{ selectedItemName || "-" }}
      </div>
      <div v-if="selectedComparedOfferDetails" class="offer-detail-summary mb-3">
        <div class="offer-detail-grid">
          <div class="offer-detail-card">
            <span class="offer-detail-label">Status</span>
            <span class="offer-detail-value">{{ selectedComparedOfferDetails.status_name }}</span>
          </div>
          <div class="offer-detail-card">
            <span class="offer-detail-label">Bid Price</span>
            <span class="offer-detail-value">{{ offerDisplayValue(selectedComparedOfferDetails.bid_price, selectedComparedOfferDetails) }}</span>
          </div>
          <div class="offer-detail-card">
            <span class="offer-detail-label">Total Bid Price</span>
            <span class="offer-detail-value">{{ offerDisplayValue(selectedComparedOfferDetails.total_bid_price, selectedComparedOfferDetails) }}</span>
          </div>
          <div class="offer-detail-card">
            <span class="offer-detail-label">Delivery Term</span>
            <span class="offer-detail-value">{{ selectedComparedOfferDetails.delivery_term }}</span>
          </div>
          <div class="offer-detail-card">
            <span class="offer-detail-label">Quantity / Unit</span>
            <span class="offer-detail-value">{{ selectedComparedOfferDetails.quantity_label }}</span>
          </div>
          <div class="offer-detail-card">
            <span class="offer-detail-label">Unit Cost</span>
            <span class="offer-detail-value">{{ offerDisplayValue(selectedComparedOfferDetails.unit_cost) }}</span>
          </div>
          <div class="offer-detail-card">
            <span class="offer-detail-label">ABC</span>
            <span class="offer-detail-value">{{ offerDisplayValue(selectedComparedOfferDetails.abc) }}</span>
          </div>
        </div>
      </div>
      <div class="offer-detail-section-label">Technical Proposal / Offer</div>
      <div class="offer-modal-content" v-html="selectedComparedOfferDescription"></div>
    </div>
  </b-modal>

  <Offer ref="editOffer" />
  <Award ref="award" :procurement="procurement" />
  <BACResolution ref="BACReso" :procurement="procurement" :action="'Award'" />
  </div>
</template>
<script>
import Checkbox from "@/Shared/Components/Forms/Checkbox.vue";
import InputError from "@/Shared/Components/Forms/InputError.vue";
import InputLabel from "@/Shared/Components/Forms/InputLabel.vue";
import TextInput from "@/Shared/Components/Forms/TextInput.vue";
import Multiselect from "@vueform/multiselect";
import Award from "../Modals/Award.vue";
import BACResolution from "../Modals/BACResolution.vue";
import Offer from "../Modals/Offer.vue";
import PageHeader from "@/Shared/Components/PageHeader.vue";

export default {
  components: {
    InputError,
    InputLabel,
    TextInput,
    Multiselect,
    Checkbox,
    Offer,
    Award,
    BACResolution,
    PageHeader
  },
  props: ["procurement", "dropdowns", "option"],
  data() {
    return {
      currentUrl: window.location.origin,
      lists: [],
      meta: {},
      links: {},
      filter: {
        keyword: null,
      },
      index: null,
      is_checked: false,
      recommendedBidsForAward: {},
      showBACResoForm: false,
      activeBidTab: 0,
      aobViewMode: "supplier",
      showItemDescriptionModal: false,
      showOfferModal: false,
      showAllOffersModal: false,
      showComparedOfferModal: false,
      isAllOffersDescriptionExpanded: false,
      selectedItemName: "",
      selectedItemDescription: "",
      selectedOfferDescription: "",
      selectedComparedOfferDescription: "",
      selectedComparedOfferSupplier: "",
      selectedProcurementItemId: null,
      selectedItemNo: null,
      selectedItemUnitCost: null,
      selectedItemAbc: null,
      selectedItemQuantity: null,
      selectedItemUnitLabel: "",
      selectedOfferDetails: null,
      selectedComparedOfferDetails: null,
    };
  },

  computed: {
    currentRoles() {
      return this.$page?.props?.roles || [];
    },
    availableBids() {
      return (this.procurement?.quotations || []).filter((bid) => this.isPendingBid(bid));
    },
    allBidItems() {
      return (this.procurement?.items || []).slice().sort((a, b) => {
        return (Number(a.item_no) || 0) - (Number(b.item_no) || 0);
      });
    },
    itemDescriptionModalSize() {
      const plainText = (this.selectedItemDescription || "")
        .replace(/<[^>]*>/g, " ")
        .replace(/\s+/g, " ")
        .trim();

      return plainText.length > 220 ? "xl" : "lg";
    },
    offerModalSize() {
      const plainText = (this.selectedOfferDescription || "")
        .replace(/<[^>]*>/g, " ")
        .replace(/\s+/g, " ")
        .trim();

      return plainText.length > 220 ? "xl" : "lg";
    },
    comparedOfferModalSize() {
      const plainText = (this.selectedComparedOfferDescription || "")
        .replace(/<[^>]*>/g, " ")
        .replace(/\s+/g, " ")
        .trim();

      return plainText.length > 220 ? "xl" : "lg";
    },
    bidTabStorageKey() {
      return `procurement-bids-active-tab-${this.procurement?.id || "default"}`;
    },
    isAtBACResolutionStep() {
      return (
        this.procurement?.status?.name === "For BAC Resolution" ||
        (this.procurement?.status?.name === "Rebid" &&
          this.procurement?.sub_status?.name === "For BAC Resolution")
      );
    },
    canGenerateBACResolution() {
      const roles = this.$page?.props?.roles || [];

      return (
        this.isAtBACResolutionStep &&
        (roles.includes("Procurement Officer") ||
          roles.includes("Procurement Staff"))
      );
    },
    isAtBidEvaluationStep() {
      return (
        this.procurement?.status?.name === "For Bids" ||
        this.procurement?.sub_status?.name === "For Bids"
      );
    },
    canManageBidAwardActions() {
      return (
        this.currentRoles.includes("Procurement Officer") ||
        this.currentRoles.includes("Procurement Staff")
      );
    },
    canRecommendBidForAward() {
      return (
        !this.isForBACResolution &&
        this.isAtBidEvaluationStep &&
        this.canManageBidAwardActions
      );
    },
    isForBACResolution() {
      return (
        this.procurement?.status?.name === "For BAC Resolution" ||
        (this.procurement?.status?.name === "Rebid" &&
          this.procurement?.sub_status?.name === "For BAC Resolution") ||
        this.procurement?.status?.name === "For Approval of BAC Resolution" ||
        (this.procurement?.status?.name === "Rebid" &&
          this.procurement?.sub_status?.name === "For Approval of BAC Resolution")
      );
    },
    isForApprovalOfBACResolution() {
      return (
        this.procurement?.status?.name === "For Approval of BAC Resolution" ||
        (this.procurement?.status?.name === "Rebid" &&
          this.procurement?.sub_status?.name === "For Approval of BAC Resolution")
      );
    },
    hasAwardableBid() {
      return this.availableBids.some((bid) =>
        (bid.items || []).some((item) => item.is_free || Number(item.bid_price) > 0)
      );
    },
    hasCheckedBidItems() {
      return this.availableBids.some((bid) =>
        (bid.items || []).some(
          (item) => Boolean(item.is_checked) && !this.isBidPriceOverUnitCost(item)
        )
      );
    },
    allOffersForSelectedItem() {
      if (!this.selectedProcurementItemId) {
        return [];
      }

      return this.availableBids
        .map((bid) => {
          const matchedItem = (bid.items || []).find(
            (item) =>
              item.procurement_item_id === this.selectedProcurementItemId ||
              item.item?.id === this.selectedProcurementItemId ||
              item.item_id === this.selectedProcurementItemId
          );

          if (!matchedItem) {
            return null;
          }

          return {
            supplier_id: bid.id,
            supplier_name: bid.supplier?.name || "-",
            status_name: matchedItem.status?.name || null,
            bid_price: Number(matchedItem.bid_price) || 0,
            total_bid_price:
              matchedItem.is_free || matchedItem.is_no_offer || matchedItem.is_not_applicable
                ? 0
                : (Number(matchedItem.bid_price) || 0) *
                  (Number(matchedItem.item?.item_quantity) || 0),
            technical_proposal: matchedItem.technical_proposal,
            delivery_term: bid.delivery_term,
            is_free: Boolean(matchedItem.is_free),
            is_no_offer: Boolean(matchedItem.is_no_offer),
            is_not_applicable: Boolean(matchedItem.is_not_applicable),
          };
        })
        .filter(Boolean);
    },
  },

  methods: {
    isPendingBid(bid) {
      return bid?.status?.name === "Pending" || Number(bid?.status_id) === 46;
    },
    setAobViewMode(mode) {
      this.aobViewMode = mode;
    },
    toggleAllOffersDescription() {
      this.isAllOffersDescriptionExpanded = !this.isAllOffersDescriptionExpanded;
    },
    persistActiveBidTab(index) {
      localStorage.setItem(this.bidTabStorageKey, String(index ?? 0));
    },
    restoreActiveBidTab() {
      const saved = Number(localStorage.getItem(this.bidTabStorageKey) ?? 0);
      if (Number.isFinite(saved) && saved >= 0 && saved < this.availableBids.length) {
        this.activeBidTab = saved;
        return;
      }
      this.activeBidTab = 0;
    },
    canEditOffer() {
      return this.isAtBidEvaluationStep;
    },
    openEditOffer(item, bid) {
      if (this.canEditOffer()) {
        this.$refs.editOffer.edit(item, bid);
      }
    },

    openRecommendAward() {
      this.$refs.award.edit(this.getCheckedItems());
    },

    getCurrentDate() {
      const today = new Date();
      const year = today.getFullYear();
      const month = String(today.getMonth() + 1).padStart(2, "0"); // Months are zero-based
      const day = String(today.getDate()).padStart(2, "0");
      return `${year}-${month}-${day}`;
    },

    formatCurrency(value) {
      return new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
      }).format(value);
    },
    isBidPriceOverUnitCost(item) {
      if (item?.is_free || item?.is_no_offer || item?.is_not_applicable) {
        return false;
      }

      const bidPrice = Number(item?.bid_price) || 0;
      const unitCost = Number(item?.item?.item_unit_cost) || 0;

      return bidPrice > 0 && unitCost > 0 && bidPrice > unitCost;
    },
    hasAwardableOffer(item) {
      return Boolean(item?.is_free) || Number(item?.bid_price) > 0;
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
    getOfferDescriptionFallback(offer) {
      if (offer?.is_no_offer) {
        return "<p>No offer was submitted for this item.</p>";
      }

      if (offer?.is_not_applicable) {
        return "<p>This item was marked not applicable for this supplier.</p>";
      }

      return "<p>No offer available.</p>";
    },
    formatQuantityLabel(quantity, unitType) {
      const qty = Number(quantity);
      const unitLabel =
        qty > 1 ? unitType?.name_long || unitType?.name_short : unitType?.name_short || unitType?.name_long;

      if (!Number.isFinite(qty) || qty <= 0) {
        return unitLabel || "-";
      }

      return `${qty} ${unitLabel || ""}`.trim();
    },
    buildOfferDetails(item, bid) {
      const procurementItem = item?.item || {};
      const quantity = Number(procurementItem?.item_quantity) || 0;
      const bidPrice = Number(item?.bid_price) || 0;

      return {
        supplier_name: bid?.supplier?.name || "-",
        status_name: item?.status?.name || "-",
        bid_price: bidPrice,
        total_bid_price:
          item?.is_free || item?.is_no_offer || item?.is_not_applicable
            ? 0
            : bidPrice * quantity,
        delivery_term: bid?.delivery_term || "-",
        quantity_label: this.formatQuantityLabel(quantity, procurementItem?.item_unit_type),
        unit_cost: Number(procurementItem?.item_unit_cost) || 0,
        abc: Number(procurementItem?.total_cost) || 0,
        is_free: Boolean(item?.is_free),
        is_no_offer: Boolean(item?.is_no_offer),
        is_not_applicable: Boolean(item?.is_not_applicable),
      };
    },
    buildComparedOfferDetails(offer) {
      return {
        status_name: offer?.status_name || "-",
        bid_price: Number(offer?.bid_price) || 0,
        total_bid_price: Number(offer?.total_bid_price) || 0,
        delivery_term: offer?.delivery_term || "-",
        quantity_label: this.formatQuantityLabel(this.selectedItemQuantity, { name_short: this.selectedItemUnitLabel, name_long: this.selectedItemUnitLabel }),
        unit_cost: Number(this.selectedItemUnitCost) || 0,
        abc: Number(this.selectedItemAbc) || 0,
        is_free: Boolean(offer?.is_free),
        is_no_offer: Boolean(offer?.is_no_offer),
        is_not_applicable: Boolean(offer?.is_not_applicable),
      };
    },

    getBadgeVariant(status_name) {
      switch (status_name) {
        case "Not Available for Award/Re-award":
          return "danger"; // Maps to Bootstrap's warning variant
        case "Not Awarded":
          return "warning"; // Maps to Bootstrap's warning variant
        case "For Bids":
          return "info"; // Maps to Bootstrap's info variant
        case "Awarded":
          return "success"; // Maps to Bootstrap's success variant
        case "Completed":
          return "dark"; // Maps to Bootstrap's dark variant
        default:
          return "secondary"; // Default variant if none match
      }
    },

    handleCheckboxChange(itemIndex, currentBid) {
      if (
        !this.hasAwardableOffer(currentBid.items[itemIndex]) ||
        this.isBidPriceOverUnitCost(currentBid.items[itemIndex])
      ) {
        currentBid.items[itemIndex].is_checked = false;
        return;
      }
      // If checked → store supplier name
      if (currentBid.items[itemIndex].is_checked) {
        this.availableBids.forEach((bid) => {
          if (bid.id === currentBid.id || !bid.items?.[itemIndex]) {
            return;
          }

          bid.items[itemIndex].is_checked = false;
        });
      }
    },

    getCheckedItems() {
      return this.availableBids.flatMap((bid) =>
        (bid.items || []).filter(
          (item) => item.is_checked && !this.isBidPriceOverUnitCost(item)
        )
      );
    },

    isOtherSupplierChecked(itemIndex, currentItem) {
      const checkedBid = this.availableBids.find(
        (bid) => bid.items?.[itemIndex]?.is_checked
      );

      return checkedBid ? checkedBid.id !== currentItem.id : false;
    },

    //count how how many items checked in a supplier
    getCheckedBidsCount(items) {
      return items.filter(
        (item) => Boolean(item.is_checked) && !this.isBidPriceOverUnitCost(item)
      ).length;
    },
    getSupplierCheckedCount(bid) {
      return this.getCheckedBidsCount(bid.items || []);
    },

    getTotalBidPrice() {
      this.form.total_bid_price = this.form.bid_price * this.form.item_quantity;
      return this.form.total_bid_price;
    },

    openEditItemBidOffer(bid_item) {
      if (this.procurement.status_id == 4) {
        this.$refs.editItem.edit(bid_item);
      }
    },
    openBACReso(data) {
      this.$refs.BACReso.show("Award");
    },
    openItemDescription(item) {
      this.selectedItemName = item?.item_name || "";
      this.selectedItemDescription = item?.item_description || "<p>No description available.</p>";
      this.showItemDescriptionModal = true;
    },
    openOfferDescription(item, bid) {
      this.selectedItemName = item?.item?.item_name || "";
      this.selectedOfferDescription =
        item?.technical_proposal || this.getOfferDescriptionFallback(item);
      this.selectedOfferDetails = this.buildOfferDetails(item, bid);
      this.showOfferModal = true;
    },
    openComparedOfferModal(offer) {
      this.selectedComparedOfferSupplier = offer?.supplier_name || "";
      this.selectedComparedOfferDescription =
        offer?.technical_proposal || this.getOfferDescriptionFallback(offer);
      this.selectedComparedOfferDetails = this.buildComparedOfferDetails(offer);
      this.showComparedOfferModal = true;
    },
    openAllOffers(item) {
      this.selectedProcurementItemId =
        item?.procurement_item_id || item?.item?.id || item?.item_id || null;
      this.selectedItemName = item?.item?.item_name || "";
      this.selectedItemNo = item?.item?.item_no || item?.item_no || null;
      this.selectedItemUnitCost = Number(item?.item?.item_unit_cost) || null;
      this.selectedItemAbc = Number(item?.item?.total_cost) || null;
      this.selectedItemQuantity = Number(item?.item?.item_quantity) || null;
      this.selectedItemUnitLabel =
        (Number(item?.item?.item_quantity) || 0) > 1
          ? item?.item?.item_unit_type?.name_long || item?.item?.item_unit_type?.name_short || ""
          : item?.item?.item_unit_type?.name_short || item?.item?.item_unit_type?.name_long || "";
      this.selectedItemDescription =
        item?.item?.item_description || "<p>No description available.</p>";
      this.isAllOffersDescriptionExpanded = false;
      this.showAllOffersModal = true;
    },

    printBids(data) {
      window.open(`/faims/procurements/${data.id}?option=print&type=abstract_of_bids`);
    },
  },
  mounted() {
    this.restoreActiveBidTab();
  },
};
</script>

<style>
.horizontal-scroll-tabs {
    padding-left: 0;
    padding-right: 0;
}

.aob-page-header {
  background: #fff;
  border-bottom: 1px solid #e5e7eb;
  padding: 0.9rem 1.1rem;
}

.horizontal-scroll-tabs .nav-tabs {
    display: flex;
    flex-wrap: nowrap;
    overflow-x: auto;
    overflow-y: hidden;
    white-space: nowrap;
    scrollbar-width: thin;
    margin-left: 0;
    margin-right: 0;
    margin-top: 0;
    margin-bottom: 1rem;
    padding-left: 0;
    padding-right: 0;
    padding-top: 0;
    padding-bottom: 0.5rem;
}

.horizontal-scroll-tabs .tab-content {
    padding-left: 0;
    padding-right: 0;
}

.horizontal-scroll-tabs .tab-pane.card-body {
    padding-left: 0;
    padding-right: 0;
}

.horizontal-scroll-tabs .nav-tabs::-webkit-scrollbar {
    height: 8px;
}

.horizontal-scroll-tabs .nav-tabs .nav-link {
    background-color: white !important;
    color: black !important; /* Ensure text is visible */
    border-bottom: 5px lightgrey solid;
  border-top: 5px lightgrey solid;
  width: 200px;
    min-width: 200px;
    flex: 0 0 auto;
}

.horizontal-scroll-tabs .nav-item:first-child .nav-link {
    margin-left: 0 !important;
}

/* Change background when tab is active */
.horizontal-scroll-tabs .nav-tabs .nav-link.active {
  background: rgba(99, 102, 241, 0.12) !important;
  border-bottom: 5px #6366f1 solid;
  border-top: 5px #6366f1 solid;
  font-weight: bolder;
  color: #4338ca !important;
  box-shadow: inset 0 0 0 1px rgba(99, 102, 241, 0.2), 0 0 14px rgba(99, 102, 241, 0.28);
}

.offer-compare-summary {
  background: linear-gradient(135deg, #f8fafc 0%, #eef2ff 100%);
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  padding: 1rem 1.1rem;
}

.offer-compare-title {
  font-size: 1.1rem;
  font-weight: 700;
  color: #1e293b;
  margin-bottom: 0.75rem;
}

.offer-compare-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 0.65rem;
  margin-bottom: 0.75rem;
}

.offer-meta-pill {
  background: #ffffff;
  border: 1px solid #dbe3f0;
  border-radius: 999px;
  padding: 0.45rem 0.8rem;
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
}

.offer-meta-label {
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: #64748b;
}

.offer-meta-value {
  font-weight: 700;
  color: #1e293b;
}

.offer-compare-description {
  color: #475569;
  font-size: 0.92rem;
}

.offer-compare-description p:last-child,
.offer-compare-description ul:last-child,
.offer-compare-description ol:last-child {
  margin-bottom: 0;
}

.offer-compare-section {
  margin-top: 0.25rem;
}

.offer-compare-table-wrap {
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  overflow: hidden;
}

.offer-compare-table thead th {
  background: #f8fafc;
  color: #334155;
  font-weight: 700;
  border-bottom: 1px solid #e2e8f0;
  white-space: nowrap;
}

.offer-compare-table td,
.offer-compare-table th {
  padding: 0.9rem 1rem;
  vertical-align: top;
}

.offer-compare-table tbody tr:not(:last-child) td {
  border-bottom: 1px solid #eef2f7;
}

.offer-compare-table tbody td {
  color: #1f2937;
}

.offer-detail-summary {
  background: linear-gradient(180deg, #f8fbff 0%, #f1f5f9 100%);
  border: 1px solid #dbe4f0;
  border-radius: 16px;
  padding: 1rem;
}

.offer-detail-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 0.75rem;
}

.offer-detail-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 0.85rem 0.95rem;
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
}

.offer-detail-label {
  font-size: 0.72rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: #64748b;
}

.offer-detail-value {
  font-size: 0.96rem;
  font-weight: 700;
  color: #1e293b;
  word-break: break-word;
}

.offer-detail-section-label {
  font-size: 0.8rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: #475569;
  margin-bottom: 0.65rem;
}

.offer-modal-content {
  color: #334155;
  text-align: left;
}

.offer-modal-content p:last-child,
.offer-modal-content ul:last-child,
.offer-modal-content ol:last-child {
  margin-bottom: 0;
}

.offer-modal-content ul,
.offer-modal-content ol {
  padding-left: 1.25rem;
}

.aob-header-bar {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  flex-wrap: wrap;
}

.aob-header-left {
  display: flex;
  align-items: center;
  gap: 0.85rem;
  flex-wrap: wrap;
}

.aob-header-title {
  font-size: 1.05rem;
  font-weight: 600;
  color: #495057;
  text-transform: uppercase;
}

.aob-view-toggle .btn {
  min-width: auto;
  padding-left: 0.85rem;
  padding-right: 0.85rem;
}

.aob-header-right {
  margin-left: auto;
}

[data-bs-theme="dark"] .procurement-bids-modal .offer-detail-summary,
[data-bs-theme="dark"] .procurement-bids-modal .offer-compare-summary,
[data-bs-theme="dark"] .procurement-bids-modal .offer-compare-table-wrap {
  background: #1b2230;
  border-color: rgba(148, 163, 184, 0.18);
}

[data-bs-theme="dark"] .procurement-bids-modal .offer-detail-card,
[data-bs-theme="dark"] .procurement-bids-modal .offer-meta-pill {
  background: #232c3a;
  border-color: rgba(148, 163, 184, 0.18);
}

[data-bs-theme="dark"] .procurement-bids-modal .offer-detail-label,
[data-bs-theme="dark"] .procurement-bids-modal .offer-detail-section-label,
[data-bs-theme="dark"] .procurement-bids-modal .offer-meta-label,
[data-bs-theme="dark"] .procurement-bids-modal .offer-compare-description,
[data-bs-theme="dark"] .procurement-bids-modal .offer-modal-content,
[data-bs-theme="dark"] .procurement-bids-modal .small.text-muted {
  color: #94a3b8 !important;
}

[data-bs-theme="dark"] .procurement-bids-modal .offer-detail-value,
[data-bs-theme="dark"] .procurement-bids-modal .offer-compare-title,
[data-bs-theme="dark"] .procurement-bids-modal .offer-meta-value,
[data-bs-theme="dark"] .procurement-bids-modal .offer-compare-table tbody td,
[data-bs-theme="dark"] .procurement-bids-modal .fw-semibold {
  color: #e5edf7 !important;
}

[data-bs-theme="dark"] .procurement-bids-modal .offer-compare-table thead th {
  background: #232c3a;
  color: #e5edf7;
  border-color: rgba(148, 163, 184, 0.18);
}

[data-bs-theme="dark"] .procurement-bids-modal .offer-compare-table td,
[data-bs-theme="dark"] .procurement-bids-modal .offer-compare-table th {
  border-color: rgba(148, 163, 184, 0.18);
}

[data-bs-theme="dark"] .procurement-bids-modal .offer-compare-table tbody tr:hover td {
  background: rgba(148, 163, 184, 0.06);
}

[data-bs-theme="dark"] .procurement-bids-modal .btn-outline-secondary {
  color: #cbd5e1;
  border-color: rgba(148, 163, 184, 0.28);
  background: transparent;
}

[data-bs-theme="dark"] .procurement-bids-modal .btn-outline-secondary:hover {
  color: #e5edf7;
  border-color: rgba(148, 163, 184, 0.42);
  background: rgba(148, 163, 184, 0.08);
}

[data-bs-theme="dark"] .procurement-bids-page {
  color: #e5edf7;
  --aob-table-wrap-bg: #1b2230;
  --aob-table-border: rgba(148, 163, 184, 0.18);
  --aob-table-head-bg: #232c3a;
  --aob-table-head-text: #e5edf7;
  --aob-table-cell-bg: #1b2230;
  --aob-table-cell-bg-alt: #202937;
  --aob-table-cell-hover: rgba(148, 163, 184, 0.06);
  --aob-table-text: #e5edf7;
  --aob-table-index: #cbd5e1;
}

[data-bs-theme="dark"] .procurement-bids-page .aob-page-header {
  background: #1b2230;
  border-bottom-color: rgba(148, 163, 184, 0.2);
}

[data-bs-theme="dark"] .procurement-bids-page .aob-header-title,
[data-bs-theme="dark"] .procurement-bids-page .offer-compare-title,
[data-bs-theme="dark"] .procurement-bids-page .offer-meta-value,
[data-bs-theme="dark"] .procurement-bids-page .aob-item-title,
[data-bs-theme="dark"] .procurement-bids-page .aob-qty-value,
[data-bs-theme="dark"] .procurement-bids-page .offer-compare-table tbody td {
  color: #e5edf7;
}

[data-bs-theme="dark"] .procurement-bids-page .horizontal-scroll-tabs,
[data-bs-theme="dark"] .procurement-bids-page .bg-white {
  background: transparent !important;
}

[data-bs-theme="dark"] .procurement-bids-page .horizontal-scroll-tabs .nav-tabs .nav-link {
  background-color: #1b2230 !important;
  color: #cbd5e1 !important;
  border-top-color: #334155;
  border-bottom-color: #334155;
}

[data-bs-theme="dark"] .procurement-bids-page .horizontal-scroll-tabs .nav-tabs .nav-link:hover:not(.active) {
  background: rgba(59, 130, 246, 0.1) !important;
  color: #e5edf7 !important;
  border-top-color: #475569;
  border-bottom-color: #475569;
}

[data-bs-theme="dark"] .procurement-bids-page .horizontal-scroll-tabs .nav-tabs .nav-link.active {
  background: rgba(59, 130, 246, 0.18) !important;
  color: #bfdbfe !important;
  border-top-color: #3b82f6;
  border-bottom-color: #3b82f6;
  box-shadow: inset 0 0 0 1px rgba(96, 165, 250, 0.28), 0 0 14px rgba(59, 130, 246, 0.18);
}

[data-bs-theme="dark"] .procurement-bids-page .aob-table-wrap,
[data-bs-theme="dark"] .procurement-bids-page .offer-compare-summary,
[data-bs-theme="dark"] .procurement-bids-page .offer-compare-table-wrap {
  background: #1b2230;
  border-color: rgba(148, 163, 184, 0.18);
}

[data-bs-theme="dark"] .procurement-bids-page .offer-meta-pill {
  background: #232c3a;
  border-color: rgba(148, 163, 184, 0.18);
}

[data-bs-theme="dark"] .procurement-bids-page .offer-meta-label,
[data-bs-theme="dark"] .procurement-bids-page .offer-compare-description,
[data-bs-theme="dark"] .procurement-bids-page .aob-qty-unit,
[data-bs-theme="dark"] .procurement-bids-page .aob-price-mutedstate,
[data-bs-theme="dark"] .procurement-bids-page .aob-price-pending,
[data-bs-theme="dark"] .procurement-bids-page .aob-delivery-pill,
[data-bs-theme="dark"] .procurement-bids-page .aob-muted-text,
[data-bs-theme="dark"] .procurement-bids-page .aob-empty-row {
  color: #94a3b8;
}

[data-bs-theme="dark"] .procurement-bids-page .aob-delivery-pill {
  background: #232c3a;
  border-color: rgba(148, 163, 184, 0.18);
}

[data-bs-theme="dark"] .procurement-bids-page .offer-compare-table thead th,
[data-bs-theme="dark"] .procurement-bids-page .aob-table thead th {
  background: #232c3a;
  color: #e5edf7;
  border-color: rgba(148, 163, 184, 0.18);
}

[data-bs-theme="dark"] .procurement-bids-page .aob-table td,
[data-bs-theme="dark"] .procurement-bids-page .aob-table th,
[data-bs-theme="dark"] .procurement-bids-page .offer-compare-table td,
[data-bs-theme="dark"] .procurement-bids-page .offer-compare-table th {
  border-color: rgba(148, 163, 184, 0.18);
}

[data-bs-theme="dark"] .procurement-bids-page .aob-table tbody tr:hover td,
[data-bs-theme="dark"] .procurement-bids-page .offer-compare-table tbody tr:hover td {
  background: rgba(148, 163, 184, 0.06);
}

[data-bs-theme="dark"] .procurement-bids-page .aob-price-link {
  color: #93c5fd;
}

[data-bs-theme="dark"] .procurement-bids-page .aob-price-over {
  color: #fca5a5;
}

[data-bs-theme="dark"] .procurement-bids-page .aob-price-free {
  color: #93c5fd;
}

[data-bs-theme="dark"] .procurement-bids-page .aob-action-btn-muted,
[data-bs-theme="dark"] .procurement-bids-page .btn-outline-secondary,
[data-bs-theme="dark"] .procurement-bids-page .btn-outline-dark {
  color: #cbd5e1;
  border-color: rgba(148, 163, 184, 0.28);
  background: transparent;
}

[data-bs-theme="dark"] .procurement-bids-page .aob-action-btn-muted:hover,
[data-bs-theme="dark"] .procurement-bids-page .btn-outline-secondary:hover,
[data-bs-theme="dark"] .procurement-bids-page .btn-outline-dark:hover {
  color: #e5edf7;
  border-color: rgba(148, 163, 184, 0.42);
  background: rgba(148, 163, 184, 0.08);
}
</style>

<style>
[data-bs-theme="dark"] .procurement-bids-page .aob-table-wrap {
  background: #1b2230 !important;
  border-color: rgba(148, 163, 184, 0.18) !important;
}

[data-bs-theme="dark"] .procurement-bids-page .aob-table tbody td {
  background: #1b2230 !important;
  color: #e5edf7 !important;
  border-color: rgba(148, 163, 184, 0.18) !important;
}

[data-bs-theme="dark"] .procurement-bids-page .aob-table tbody tr:nth-child(even) td {
  background: #202937 !important;
}

[data-bs-theme="dark"] .procurement-bids-page .aob-table tbody tr:hover td {
  background: rgba(148, 163, 184, 0.06) !important;
}

[data-bs-theme="dark"] .procurement-bids-page .aob-cell-index {
  color: #cbd5e1 !important;
}
</style>

<style scoped>
.procurement-bids-page {
  --aob-table-wrap-bg: #ffffff;
  --aob-table-border: #e2e8f0;
  --aob-table-head-bg: linear-gradient(180deg, #f8fbff 0%, #eef4fb 100%);
  --aob-table-head-text: #334155;
  --aob-table-cell-bg: #ffffff;
  --aob-table-cell-bg-alt: #fbfdff;
  --aob-table-cell-hover: #f5f8ff;
  --aob-table-text: #1e293b;
  --aob-table-index: #475569;
}

.aob-scroll-area {
  padding: 1rem 0;
}

.aob-table-wrap {
  border: 1px solid var(--aob-table-border);
  border-radius: 16px;
  overflow: auto;
  max-width: 100%;
  background: var(--aob-table-wrap-bg);
  box-shadow: 0 10px 28px rgba(15, 23, 42, 0.06);
  scrollbar-width: thin;
  scrollbar-color: #94a3b8 #eef2f7;
}

.aob-table-wrap::-webkit-scrollbar {
  height: 10px;
  width: 10px;
}

.aob-table-wrap::-webkit-scrollbar-track {
  background: #eef2f7;
}

.aob-table-wrap::-webkit-scrollbar-thumb {
  background: #94a3b8;
  border-radius: 999px;
  border: 2px solid #eef2f7;
}

.aob-table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
}

.aob-supplier-table {
  min-width: 1220px;
}

.aob-items-table {
  min-width: 980px;
  table-layout: fixed;
}

.aob-table th,
.aob-table td {
  padding: 0.9rem 0.85rem;
  vertical-align: top;
  text-align: center;
  border-right: 1px solid var(--aob-table-border);
  border-bottom: 1px solid var(--aob-table-border);
}

.aob-table th:last-child,
.aob-table td:last-child {
  border-right: 0;
}

.aob-table tbody tr:last-child td {
  border-bottom: 0;
}

.aob-table thead th {
  position: sticky;
  top: 0;
  z-index: 2;
  background: var(--aob-table-head-bg);
  color: var(--aob-table-head-text);
  font-size: 0.76rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  line-height: 1.45;
}

.aob-table tbody td {
  color: var(--aob-table-text);
  background: var(--aob-table-cell-bg);
}

.aob-table tbody tr:nth-child(even) td {
  background: var(--aob-table-cell-bg-alt);
}

.aob-table tbody tr:hover td {
  background: var(--aob-table-cell-hover);
}

.aob-col-item-no {
  width: 78px;
}

.aob-col-status {
  width: 140px;
}

.aob-col-item-name {
  width: 260px;
}

.aob-col-description {
  width: 170px;
}

.aob-col-qty {
  width: 120px;
}

.aob-col-money {
  width: 150px;
}

.aob-col-offer {
  width: 170px;
}

.aob-col-delivery {
  width: 170px;
}

.aob-col-check {
  width: 120px;
}

.aob-items-table .aob-col-item-no {
  width: 8%;
}

.aob-items-table .aob-col-item-name {
  width: 25%;
}

.aob-items-table .aob-col-description {
  width: 18%;
}

.aob-items-table .aob-col-qty {
  width: 12%;
}

.aob-items-table .aob-col-money {
  width: 15%;
}

.aob-items-table .aob-col-offer {
  width: 14%;
}

.aob-cell-index {
  font-weight: 700;
  color: var(--aob-table-index);
}

.aob-cell-status,
.aob-cell-action,
.aob-cell-check {
  vertical-align: middle;
}

.aob-award-checkbox {
  width: 1.1rem;
  height: 1.1rem;
  margin: 0;
  float: none;
  cursor: pointer;
  accent-color: #0d6efd;
}

.aob-award-checkbox:disabled {
  cursor: not-allowed;
  opacity: 0.55;
}

.aob-cell-item {
  text-align: left !important;
}

.aob-item-title {
  font-weight: 700;
  color: #1e293b;
  line-height: 1.45;
  word-break: break-word;
}

.aob-item-actions {
  margin-top: 0.7rem;
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.aob-cell-qty {
  vertical-align: middle;
}

.aob-qty-value {
  font-weight: 700;
  color: #1e293b;
}

.aob-qty-unit {
  margin-top: 0.15rem;
  font-size: 0.82rem;
  color: #64748b;
}

.aob-cell-money {
  text-align: right !important;
  white-space: nowrap;
  font-variant-numeric: tabular-nums;
  font-weight: 600;
  vertical-align: middle;
}

.aob-clickable-cell {
  cursor: pointer;
}

.aob-price {
  display: inline-block;
  font-weight: 700;
}

.aob-price-link {
  color: #1d4ed8;
  text-decoration: underline;
  text-decoration-thickness: 1px;
  text-underline-offset: 2px;
}

.aob-price-over {
  color: #dc2626;
}

.aob-price-free {
  color: #2563eb;
  font-style: italic;
  text-transform: lowercase;
}

.aob-price-mutedstate {
  color: #475569;
  font-style: italic;
  text-transform: lowercase;
}

.aob-price-pending {
  color: #64748b;
  font-style: italic;
  text-transform: lowercase;
}

.aob-price-editable {
  text-decoration: underline;
  text-decoration-thickness: 1px;
  text-underline-offset: 2px;
}

.aob-cell-delivery {
  vertical-align: middle;
}

.aob-delivery-pill {
  display: inline-block;
  padding: 0.45rem 0.7rem;
  border-radius: 999px;
  background: #f8fafc;
  border: 1px solid #dbe4f0;
  color: #334155;
  line-height: 1.4;
  text-align: center;
}

.aob-action-btn {
  border-radius: 10px;
  padding: 0.42rem 0.78rem;
  font-weight: 600;
  min-width: 138px;
}

.aob-items-table .aob-action-btn {
  min-width: 118px;
}

.aob-action-btn-muted {
  color: #475569;
  border-color: #cbd5e1;
}

.aob-action-btn-muted:hover {
  color: #1e293b;
  border-color: #94a3b8;
  background: #f8fafc;
}

.aob-status-badge {
  padding: 0.45rem 0.62rem;
  border-radius: 999px;
  font-weight: 700;
}

.aob-muted-text {
  color: #94a3b8;
  font-size: 0.82rem;
}

.aob-empty-row {
  padding: 2.2rem 1rem !important;
  text-align: center;
  color: #64748b;
  font-weight: 500;
}

[data-bs-theme="dark"] .procurement-bids-page {
  color: #e5edf7;
}

[data-bs-theme="dark"] .procurement-bids-page .aob-page-header {
  background: #1b2230;
  border-bottom-color: rgba(148, 163, 184, 0.2);
}

[data-bs-theme="dark"] .procurement-bids-page .aob-header-title,
[data-bs-theme="dark"] .procurement-bids-page .offer-compare-title,
[data-bs-theme="dark"] .procurement-bids-page .offer-meta-value,
[data-bs-theme="dark"] .procurement-bids-page .aob-item-title,
[data-bs-theme="dark"] .procurement-bids-page .aob-qty-value,
[data-bs-theme="dark"] .procurement-bids-page .offer-compare-table tbody td {
  color: #e5edf7;
}

[data-bs-theme="dark"] .procurement-bids-page .horizontal-scroll-tabs,
[data-bs-theme="dark"] .procurement-bids-page .bg-white {
  background: transparent !important;
}

[data-bs-theme="dark"] .procurement-bids-page .horizontal-scroll-tabs .nav-tabs .nav-link {
  background-color: #1b2230 !important;
  color: #cbd5e1 !important;
  border-top-color: #334155;
  border-bottom-color: #334155;
}

[data-bs-theme="dark"] .procurement-bids-page .horizontal-scroll-tabs .nav-tabs .nav-link:hover:not(.active) {
  background: rgba(59, 130, 246, 0.1) !important;
  color: #e5edf7 !important;
  border-top-color: #475569;
  border-bottom-color: #475569;
}

[data-bs-theme="dark"] .procurement-bids-page .horizontal-scroll-tabs .nav-tabs .nav-link.active {
  background: rgba(59, 130, 246, 0.18) !important;
  color: #bfdbfe !important;
  border-top-color: #3b82f6;
  border-bottom-color: #3b82f6;
  box-shadow: inset 0 0 0 1px rgba(96, 165, 250, 0.28), 0 0 14px rgba(59, 130, 246, 0.18);
}

[data-bs-theme="dark"] .procurement-bids-page .aob-table-wrap,
[data-bs-theme="dark"] .procurement-bids-page .offer-compare-summary,
[data-bs-theme="dark"] .procurement-bids-page .offer-compare-table-wrap {
  background: #1b2230;
  border-color: rgba(148, 163, 184, 0.18);
}

[data-bs-theme="dark"] .procurement-bids-page .offer-meta-pill {
  background: #232c3a;
  border-color: rgba(148, 163, 184, 0.18);
}

[data-bs-theme="dark"] .procurement-bids-page .offer-meta-label,
[data-bs-theme="dark"] .procurement-bids-page .offer-compare-description,
[data-bs-theme="dark"] .procurement-bids-page .aob-qty-unit,
[data-bs-theme="dark"] .procurement-bids-page .aob-price-mutedstate,
[data-bs-theme="dark"] .procurement-bids-page .aob-price-pending,
[data-bs-theme="dark"] .procurement-bids-page .aob-delivery-pill,
[data-bs-theme="dark"] .procurement-bids-page .aob-muted-text,
[data-bs-theme="dark"] .procurement-bids-page .aob-empty-row {
  color: #94a3b8;
}

[data-bs-theme="dark"] .procurement-bids-page .aob-delivery-pill {
  background: #232c3a;
  border-color: rgba(148, 163, 184, 0.18);
}

[data-bs-theme="dark"] .procurement-bids-page .offer-compare-table thead th,
[data-bs-theme="dark"] .procurement-bids-page .aob-table thead th {
  background: #232c3a;
  color: #e5edf7;
  border-color: rgba(148, 163, 184, 0.18);
}

[data-bs-theme="dark"] .procurement-bids-page .aob-table td,
[data-bs-theme="dark"] .procurement-bids-page .aob-table th,
[data-bs-theme="dark"] .procurement-bids-page .offer-compare-table td,
[data-bs-theme="dark"] .procurement-bids-page .offer-compare-table th {
  border-color: rgba(148, 163, 184, 0.18);
}

[data-bs-theme="dark"] .procurement-bids-page .aob-table tbody tr:hover td,
[data-bs-theme="dark"] .procurement-bids-page .offer-compare-table tbody tr:hover td {
  background: rgba(148, 163, 184, 0.06);
}

[data-bs-theme="dark"] .procurement-bids-page .aob-price-link {
  color: #93c5fd;
}

[data-bs-theme="dark"] .procurement-bids-page .aob-price-over {
  color: #fca5a5;
}

[data-bs-theme="dark"] .procurement-bids-page .aob-price-free {
  color: #93c5fd;
}

[data-bs-theme="dark"] .procurement-bids-page .aob-action-btn-muted,
[data-bs-theme="dark"] .procurement-bids-page .btn-outline-secondary,
[data-bs-theme="dark"] .procurement-bids-page .btn-outline-dark {
  color: #cbd5e1;
  border-color: rgba(148, 163, 184, 0.28);
  background: transparent;
}

[data-bs-theme="dark"] .procurement-bids-page .aob-action-btn-muted:hover,
[data-bs-theme="dark"] .procurement-bids-page .btn-outline-secondary:hover,
[data-bs-theme="dark"] .procurement-bids-page .btn-outline-dark:hover {
  color: #e5edf7;
  border-color: rgba(148, 163, 184, 0.42);
  background: rgba(148, 163, 184, 0.08);
}

@media (max-width: 992px) {
  .aob-supplier-table {
    min-width: 1080px;
  }

  .aob-items-table {
    min-width: 900px;
  }

  .aob-action-btn {
    min-width: 124px;
  }
}
</style>
