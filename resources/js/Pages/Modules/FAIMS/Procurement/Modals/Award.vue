<template>
  <b-modal
    v-model="showModal"
    style="--vz-modal-width: 1280px"
    header-class="p-3 bg-light"
    title="Save Bids For Award?"
    class="v-modal-custom"
    modal-class="zoomIn"
    body-class="award-modal-body"
    centered
    no-close-on-backdrop
  >
    <form class="customform">
      <div>
        <div class="award-modal-intro">
          <div class="fw-semibold">Confirm the following details:</div>
          <div class="text-muted small mt-1">
            Review the selected bids and ranking before saving the recommendation for award.
          </div>
        </div>

        <div class="table-responsive">
          <table class="table table-bordered align-middle text-center award-table" style="width: 100%">
            <thead class="table-light">
              <tr>
                <th>Rank</th>
                <th>Item No.</th>
                <th>Quantity / Unit</th>
                <th>ABC</th>
                <th>Total Bid Price</th>
                <th>Bid Description</th>
                <th>Technical Proposal / Offer</th>
                <th>Supplier</th>
              </tr>
            </thead>

            <tbody v-if="checked_items">
              <tr v-for="(bidItem, indexData) in bidsForAward" :key="indexData">
                <td class="text-center">{{ bidItem.rank }}</td>
                <td class="text-center">{{ bidItem?.item_no }}</td>
                <td>
                  {{ bidItem?.item_quantity }}
                  {{
                    bidItem?.item_quantity > 1
                      ? bidItem.item_unit_type?.name_long
                      : bidItem.item_unit_type?.name_short
                  }}
                </td>
                <td class="text-center">{{ formatCurrency(bidItem.total_cost) }}</td>
                <td class="text-center">
                  {{ bidItem.is_free ? "free" : formatCurrency(bidItem.bid_price * bidItem.item_quantity) }}
                </td>
                <td class="text-center">
                  <b-button
                    type="button"
                    size="sm"
                    variant="outline-secondary"
                    @click="openDetailModal('Bid Description', bidItem.item_description)"
                  >
                    View
                  </b-button>
                </td>
                <td class="text-center">
                  <b-button
                    type="button"
                    size="sm"
                    variant="outline-secondary"
                    @click="openDetailModal('Technical Proposal / Offer', bidItem.technical_proposal)"
                  >
                    View
                  </b-button>
                </td>
                <td class="text-center">{{ bidItem.supplier?.name || "-" }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <b-row class="award-checklist">
          <b-col lg="8" >
            <b-form-checkbox v-model="checkedBidsDescriptions">
              <span class="fw-semibold fs-6 text-info">Each bid offer is correct.</span>
            </b-form-checkbox>
            <b-form-checkbox v-model="checkedBidsPrice">
              <span class="fw-semibold fs-6 text-info">Each bid price is correct.</span>
            </b-form-checkbox>
          </b-col>
        </b-row>
      </div>

      <hr class="text-muted my-4" />

      <div
        v-if="unawardedWithBidPriceCount > 0 && checkedBidsDescriptions && checkedBidsPrice"
        class="mt-4"
      >
        <div
          v-for="(group, groupIndex) in groupedUnawardedBids"
          :key="group.item_no"
          class="mb-4"
        >
          <div>
            <b class="text-info">Item No: {{ group.item_no }}</b>
          </div>

          <table class="table table-bordered award-table" style="width: 100%">
            <thead class="table-light">
              <tr>
                <th>Rank</th>
                <th>Quantity/Unit</th>
                <th>ABC</th>
                <th>Total Bid Price</th>
                <th>Bid Description</th>
                <th>Technical Proposal</th>
                <th>Supplier</th>
              </tr>
            </thead>
            <draggable
              v-model="group.quotations"
              tag="tbody"
              :animation="200"
              item-key="id"
              @change="updateRanks(groupIndex)"
            >
              <template #item="{ element }" >
                <tr >
                  <td class="text-center">{{ element.rank }}</td>
                  <td class="text-center">
                    <span class="m-2">{{ element.item_quantity }}</span>
                    <span>
                      {{
                        element.item_quantity > 1
                          ? element.item_unit_type?.name_long
                          : element.item_unit_type?.name_short
                      }}
                    </span>
                  </td>
                  <td class="text-center">{{ formatCurrency(element.total_cost) }}</td>
                  <td class="text-center">
                    {{ element.is_free ? "free" : formatCurrency(element.bid_price * element.item_quantity) }}
                  </td>
                  <td class="text-center">
                    <b-button
                      type="button"
                      size="sm"
                      variant="outline-secondary"
                      @click="openDetailModal('Bid Description', element.item_description)"
                    >
                      View
                    </b-button>
                  </td>
                  <td class="text-center">
                    <b-button
                      type="button"
                      size="sm"
                      variant="outline-secondary"
                      @click="openDetailModal('Technical Proposal / Offer', element.technical_proposal)"
                    >
                      View
                    </b-button>
                  </td>
                  <td class="text-center">{{ element.supplier.name }}</td>
                </tr>
              </template>
            </draggable>
          </table>
        </div>

        <div>
          <b-form-checkbox v-if="unawardedWithBidPriceCount > 0" v-model="checkedBidsRank">
            <span class="fw-semibold fs-6 text-info">The bid items are correctly ranked from first to last.</span>
          </b-form-checkbox>
        </div>
      </div>
    </form>

    <template #footer>
      <b-button @click="hide()" variant="light" block>Close</b-button>
      <b-button @click="submit()" variant="primary" block :disabled="!canSubmit">
        Save Bids For Award
      </b-button>
    </template>
  </b-modal>

  <b-modal
    v-model="showDetailModal"
    :title="detailModalTitle"
    header-class="p-3 bg-light"
    body-class="award-detail-modal-body"
    centered
    hide-footer
  >
    <div class="award-detail-content" v-html="detailModalContent"></div>
  </b-modal>
</template>

<script>
import { router } from "@inertiajs/vue3";
import draggable from "vuedraggable";

export default {
  components: { draggable },
  props: ["procurement"],

  data() {
    return {
      currentUrl: window.location.origin,
      showModal: false,
      checkedBidsDescriptions: false,
      checkedBidsPrice: false,
      checkedBidsRank: false,
      checked_items: [],
      unchecked_items: [],
      bidsForAward: [],
      bidsNotForAward: [],
      groupedUnawardedBids: [],
      showDetailModal: false,
      detailModalTitle: "",
      detailModalContent: "",
    };
  },

  computed: {
    bothChecked() {
      return this.checkedBidsDescriptions && this.checkedBidsPrice;
    },

    rankChecked() {
      return this.checkedBidsDescriptions && this.checkedBidsPrice && this.checkedBidsRank;
    },

    canSubmit() {
      return this.unawardedWithBidPriceCount > 0 ? this.rankChecked : this.bothChecked;
    },

    unawardedWithBidPriceCount() {
      return this.groupedUnawardedBids.reduce((count, group) => {
        return (
          count +
          group.quotations.filter((quotation) => quotation.is_free || Number(quotation.bid_price) > 0).length
        );
      }, 0);
    },
  },

  methods: {
    edit(checkedItems) {
      this.showModal = true;
      this.checked_items = checkedItems;
      this.sortData();
    },

    hide() {
      this.showModal = false;
    },

    openDetailModal(title, content) {
      this.detailModalTitle = title;
      this.detailModalContent = content || "<p>No details available.</p>";
      this.showDetailModal = true;
    },

    sortData() {
      this.bidsForAward = [];
      this.bidsNotForAward = [];
      const grouped = {};

      this.procurement.quotations.forEach((quotation) => {
        if (quotation.status_id !== 46) return;

        quotation.items.forEach((item) => {
          const entry = {
            id: item.id,
            item_no: item.item.item_no,
            item_quantity: item.item.item_quantity,
            item_unit_type: item.item.item_unit_type,
            item_description: item.item.item_description,
            supplier_id: quotation.supplier.id,
            supplier: quotation.supplier,
            total_cost: item.item.item_quantity * item.item.item_unit_cost,
            bid_price: item.bid_price,
            is_free: item.is_free,
            item_unit_cost: item.item.item_unit_cost,
            total_bid_price: item.bid_price * item.item.item_quantity,
            technical_proposal: item.technical_proposal,
            delivery_term: quotation.delivery_term,
            status: item.status_id,
            rank: 1,
            is_checked: item.is_checked,
          };

          if (item.is_checked) {
            entry.rank = 1;
            this.bidsForAward.push(entry);
          } else {
            this.bidsNotForAward.push(entry);
            if (!grouped[item.item.item_no]) grouped[item.item.item_no] = [];
            grouped[item.item.item_no].push(entry);
          }
        });
      });

      this.groupedUnawardedBids = Object.entries(grouped)
        .sort(([a], [b]) => parseInt(a, 10) - parseInt(b, 10))
        .map(([item_no, quotations]) => {
          const rankableQuotations = quotations
            .filter((quotation) => this.isRankableBid(quotation))
            .sort((a, b) => this.getRankAmount(a) - this.getRankAmount(b))
            .map((quotation, index) => ({
              ...quotation,
              rank: index + 2,
            }));

          return {
            item_no,
            quotations: rankableQuotations,
          };
        })
        .filter((group) => group.quotations.length > 0);

      this.bidsNotForAward = Object.entries(grouped)
        .sort(([a], [b]) => parseInt(a, 10) - parseInt(b, 10))
        .flatMap(([item_no, quotations]) => {
          let rank = 1;

          return quotations
            .sort((a, b) => this.getRankAmount(a) - this.getRankAmount(b))
            .map((quotation) => ({
              ...quotation,
              rank: this.isRankableBid(quotation) ? rank++ : null,
            }));
        });
    },

    updateRanks(groupIndex) {
      this.groupedUnawardedBids[groupIndex].quotations.forEach((quotation, index) => {
        quotation.rank = index + 2;
      });
    },

    isBidPriceOverUnitCost(item) {
      if (item?.is_free || item?.is_no_offer || item?.is_not_applicable) {
        return false;
      }

      const bidPrice = Number(item?.bid_price) || 0;
      const unitCost = Number(item?.item_unit_cost) || 0;

      return bidPrice > 0 && unitCost > 0 && bidPrice > unitCost;
    },

    isRankableBid(item) {
      return (Boolean(item?.is_free) || Number(item?.bid_price) > 0) && !this.isBidPriceOverUnitCost(item);
    },

    getRankAmount(item) {
      if (item?.is_free) {
        return 0;
      }

      return (Number(item?.bid_price) || Number.MAX_SAFE_INTEGER) * (Number(item?.item_quantity) || 1);
    },

    submit() {
      const data = {
        procurement_id: this.procurement.id,
        items: this.bidsForAward,
        itemsNotAvailableForAward: this.bidsNotForAward,
        action: this.procurement.status_id == 13 ? "rebid" : "bid",
        option: "save_bid_for_award",
      };

      router.post("/faims/offers/", data, {
        onSuccess: () => {
          this.$emit("update", true);
          this.hide();
        },
      });

      this.hide();
    },

    formatCurrency(value) {
      return new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
      }).format(value);
    },
  },
};
</script>

<style scoped>
.award-table td,
.award-table th {
  border: 1px solid;
  padding: 2px;
  vertical-align: top;
  text-align: left;
}

.award-table th {
  text-align: center;
}

.award-modal-intro {
  margin-bottom: 1rem;
}

.award-checklist :deep(.form-check) + :deep(.form-check) {
  margin-top: 0.75rem;
}

.award-modal-body {
  max-height: calc(100vh - 11rem);
  overflow-y: auto;
}

.award-detail-modal-body {
  max-height: calc(100vh - 12rem);
  overflow-y: auto;
}

.award-detail-content {
  overflow-wrap: anywhere;
}
</style>
