<template>
  <div class="aob-page-header mt-2">
    <div class="aob-header-bar">
      <div class="aob-header-left">
        <h4 class="aob-header-title mb-0">Abstract of Bids</h4>
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
          v-if="
            !isForApprovalOfBACResolution &&
            (((this.procurement.status?.name == 'For BAC Resolution' ||
              (this.procurement.status?.name === 'Rebid' &&
                this.procurement.sub_status?.name === 'For BAC Resolution')) &&
              $page.props.roles.includes('Procurement Officer')) ||
              $page.props.roles.includes('Procurement Staff'))
          "
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
            !isForBACResolution &&
            hasCheckedBidItems &&
            (((procurement?.status.name == 'For Bids' ||
              procurement?.sub_status?.name == 'For Bids') &&
              $page.props.roles.includes('Procurement Officer')) ||
              $page.props.roles.includes('Procurement Staff'))
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
            class="w-100 pt-0 pb-0 "
            style="height: calc(100vh - 305px); overflow: auto"
            ref="box"
          >
            <div>
              <table style="width: 100%; border-collapse: collapse; border: 1px solid">
                <thead>
                  <tr>
                    <th style="width: 2px">Item No</th>
                    <th style="width: 20px">Status</th>
                    <th style="width: 240px">Item Name</th>
                    <th style="width: 140px">Description</th>
                    <th style="width: 20px">Quantity/Unit</th>
                    <th style="width: 20px">Unit Cost</th>
                    <th style="width: 20px">ABC</th>
                    <th style="width: 20px">Bid Price</th>
                    <th style="width: 20px">Total Bid Price</th>
                    <th style="width: 160px">Technical Proposal / Offer</th>
                    <th style="width: 100px">Delivery Term</th>

                    <th
                      v-if="
                        !isForBACResolution &&
                        (this.procurement.status.name === 'For Bids' ||
                          (this.procurement.status.name === 'Rebid' &&
                            this.procurement.sub_status?.name === 'For Bids' &&
                            $page.props.roles.includes('Procurement Officer')) ||
                          $page.props.roles.includes('Procurement Staff'))
                      "
                    >
                      Recommend Bid For Award?
                    </th>
                  </tr>
                </thead>

                <tbody>
                  <tr v-for="(item, itemIndex) in bid.items" :key="item.item_id">
                    <td>{{ itemIndex + 1 }}</td>
                    <td>
                      <b-badge
                        v-if="item.status"
                        :variant="getBadgeVariant(item.status.name)"
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
                    </td>
                    <td
                      style="
                        text-align: left;
                        word-break: break-word;
                        white-space: normal;
                      "
                    >
                      {{ item.item.item_name || "-" }}
                      <div class="mt-2">
                        <button
                          type="button"
                          class="btn btn-outline-primary btn-sm"
                          @click="openAllOffers(item)"
                        >
                          View All Offers
                        </button>
                      </div>
                    </td>
                    <td class="text-center">
                      <button
                        type="button"
                        class="btn btn-outline-secondary btn-sm"
                        @click="openItemDescription(item.item)"
                      >
                        View Description
                      </button>
                    </td>

                    <td>
                      {{ item.item.item_quantity }}
                      {{
                        item.item.item_quantity > 1
                          ? item.item.item_unit_type.name_long
                          : item.item.item_unit_type.name_short
                      }}
                    </td>
                    <td>
                      {{ formatCurrency(item.item.item_unit_cost) }}
                    </td>
                    <td>
                      {{ formatCurrency(item.item.total_cost) }}
                    </td>

                    <td @click="openEditOffer(item, bid)">
                      <span v-if="item.is_free">
                        <b
                          ><i class="text-primary"><u>free</u></i></b
                        >
                      </span>
                      <span
                        v-else-if="Number(item.bid_price) > 0"
                        :class="{
                          'text-danger':
                            Number(item.bid_price) > item.item.item_unit_cost,
                        }"
                      >
                        <u>{{ formatCurrency(item.bid_price) }}</u>
                      </span>
                      <span v-else>
                        <b
                          ><i class="text-primary"><u>not set</u></i></b
                        >
                      </span>
                    </td>
                    <td>
                      <span v-if="item.is_free">
                        <b><i class="text-primary">free</i></b>
                      </span>
                      <span v-else-if="!(Number(item.bid_price) > 0)">
                        <b><i class="text-primary">not set</i></b>
                      </span>
                      <span v-else>
                        {{ formatCurrency(item.item.item_quantity * item.bid_price) }}
                      </span>
                    </td>

                    <td class="text-center">
                      <button
                        type="button"
                        class="btn btn-outline-secondary btn-sm"
                        @click="openOfferDescription(item)"
                      >
                        View Offer
                      </button>
                    </td>
                    <td>{{ bid.delivery_term }}</td>

                    <td
                      v-if="
                        !isForBACResolution &&
                        (((procurement.status.name == 'For Bids' ||
                          (this.procurement.status.name === 'Rebid' &&
                            this.procurement.sub_status?.name === 'For Bids')) &&
                          $page.props.roles.includes('Procurement Officer')) ||
                          $page.props.roles.includes('Procurement Staff'))
                      "
                    >
                      <span class="d-flex justify-content-center">
                        <b-form-checkbox
                          v-model="item.is_checked"
                          name="checkbox"
                          class="border-primary bg-primary"
                          :value="true"
                          :disabled="
                            isOtherSupplierChecked(itemIndex, bid) ||
                            (!item.is_free && !(Number(item.bid_price) > 0))
                          "
                          @change="handleCheckboxChange(itemIndex, bid)"
                        >
                        </b-form-checkbox>
                      </span>
                    </td>
                  </tr>
                </tbody>
              </table>

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
      class="w-100 pt-0 pb-0"
      style="height: calc(100vh - 305px); overflow: auto"
    >
      <table style="width: 100%; border-collapse: collapse; border: 1px solid">
        <thead>
          <tr>
            <th style="width: 2px">Item No</th>
            <th style="width: 160px">Item Name</th>
            <th style="width: 220px">Description</th>
            <th style="width: 20px">Quantity/Unit</th>
            <th style="width: 20px">Unit Cost</th>
            <th style="width: 20px">ABC</th>
            <th style="width: 120px">Offers</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="item in allBidItems" :key="item.id">
            <td>{{ item.item_no || "-" }}</td>
            <td>{{ item.item_name || "-" }}</td>
            <td class="text-center">
              <button
                type="button"
                class="btn btn-outline-secondary btn-sm"
                @click="openItemDescription(item)"
              >
                View Description
              </button>
            </td>
            <td>
              {{ item.item_quantity }}
              {{
                item.item_quantity > 1
                  ? item.item_unit_type?.name_long
                  : item.item_unit_type?.name_short
              }}
            </td>
            <td>{{ formatCurrency(item.item_unit_cost) }}</td>
            <td>{{ formatCurrency(item.total_cost) }}</td>
            <td class="text-center">
              <button
                type="button"
                class="btn btn-outline-primary btn-sm"
                @click="openAllOffers({ item })"
              >
                View Offers
              </button>
            </td>
          </tr>
          <tr v-if="allBidItems.length === 0">
            <td colspan="7" class="text-center text-muted py-4">No items available.</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
  </template>

  <b-modal
    v-model="showItemDescriptionModal"
    title="Item Description"
    :size="itemDescriptionModalSize"
    centered
    hide-footer
  >
    <div class="mb-2 fw-semibold">
      {{ selectedItemName || "-" }}
    </div>
    <div v-html="selectedItemDescription"></div>
  </b-modal>

  <b-modal
    v-model="showOfferModal"
    title="Technical Proposal / Offer"
    :size="offerModalSize"
    centered
    hide-footer
  >
    <div class="mb-2 fw-semibold">
      {{ selectedItemName || "-" }}
    </div>
    <div v-html="selectedOfferDescription"></div>
  </b-modal>

  <b-modal
    v-model="showAllOffersModal"
    title="All Offers For Item"
    size="xl"
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
                <span v-else-if="offer.bid_price > 0">{{ formatCurrency(offer.bid_price) }}</span>
                <span v-else>-</span>
              </td>
              <td>
                <span v-if="offer.is_free"><b><i class="text-primary">free</i></b></span>
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
    title="Supplier Offer"
    :size="comparedOfferModalSize"
    centered
    hide-footer
  >
    <div class="mb-2 fw-semibold">
      {{ selectedComparedOfferSupplier || "-" }}
    </div>
    <div class="small text-muted mb-3">
      {{ selectedItemName || "-" }}
    </div>
    <div class="offer-modal-content" v-html="selectedComparedOfferDescription"></div>
  </b-modal>

  <Offer ref="editOffer" />
  <Award ref="award" :procurement="procurement" />
  <BACResolution ref="BACReso" :procurement="procurement" :action="'Award'" />
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
    };
  },

  computed: {
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
        (bid.items || []).some((item) => Boolean(item.is_checked))
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
              matchedItem.is_free
                ? 0
                : (Number(matchedItem.bid_price) || 0) *
                  (Number(matchedItem.item?.item_quantity) || 0),
            technical_proposal: matchedItem.technical_proposal,
            delivery_term: bid.delivery_term,
            is_free: Boolean(matchedItem.is_free),
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
    openEditOffer(item, bid) {
      if (
        this.procurement.status?.name == "For Bids" ||
        (this.procurement.status.name === "Rebid" &&
          this.procurement.sub_status?.name === "For Bids")
      ) {
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
        (bid.items || []).filter((item) => item.is_checked)
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
      return items.filter((item) => Boolean(item.is_checked)).length;
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
    openOfferDescription(item) {
      this.selectedItemName = item?.item?.item_name || "";
      this.selectedOfferDescription = item?.technical_proposal || "<p>No offer available.</p>";
      this.showOfferModal = true;
    },
    openComparedOfferModal(offer) {
      this.selectedComparedOfferSupplier = offer?.supplier_name || "";
      this.selectedComparedOfferDescription =
        offer?.technical_proposal || "<p>No offer available.</p>";
      this.showComparedOfferModal = true;
    },
    openAllOffers(item) {
      this.selectedProcurementItemId =
        item?.procurement_item_id || item?.item?.id || item?.item_id || null;
      this.selectedItemName = item?.item?.item_name || "";
      this.selectedItemNo = item?.item?.item_no || item?.item_no || null;
      this.selectedItemUnitCost = Number(item?.item?.item_unit_cost) || null;
      this.selectedItemAbc = Number(item?.item?.total_cost) || null;
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
</style>

<style scoped>
td,
th {
  border: 1px solid;
  padding: 5px;
  vertical-align: top;
  text-align: center;
}
</style>
