<template>
  <b-modal
    v-model="showModal"
    header-class="p-3"
    title="Save Bids For Award?"
    size="xl"
    class="v-modal-custom"
    modal-class="zoomIn"
    centered
    no-close-on-backdrop
  >
    <form class="customform">
      <!-- Confirm section -->
      <div>
        <div class="mb-2">
          <b>Confirm the following details:</b>
        </div>

        <div class="table-responsive">
          <table
            class="table table-bordered align-middle text-center"
            style="width: 100%"
          >
            <thead class="table-light">
              <tr>
                <th>Rank</th>
                <th>Item No.</th>
                <th>Quantity / Unit</th>
                <th>ABC</th>
                <th>Total Bid Price</th>
                <th>Bid Description</th>
                <th>Technical Proposal / Offer</th>
                <!-- <th>Delivery Term</th> -->
                <th>Supplier</th>
              </tr>
            </thead>

            <tbody v-if="checked_items">
              <tr v-for="(bid_item, indexData) in bidsForAward" :key="indexData">
                <td>{{ bid_item.rank }}</td>
                <td class="text-center">{{ bid_item?.item_no }}</td>
                <td>
                  {{ bid_item?.item_quantity }}
                  {{
                    bid_item?.item_quantity > 1
                      ? bid_item.item_unit_type?.name_long
                      : bid_item.item_unit_type?.name_short
                  }}
                </td>
                <td>{{ formatCurrency(bid_item.total_cost) }}</td>
                <td>
                  {{ formatCurrency(bid_item.bid_price * bid_item.item_quantity) }}
                </td>
                <td><span v-html="bid_item.item_description"></span></td>
                <td><span v-html="bid_item.technical_proposal"></span></td>
                <!-- <td><span v-html="bid_item.delivery_term"></span></td> -->
                <td>{{ bid_item.supplier?.name || "—" }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <br />
        <BRow>
          <BCol lg="8" class="mt-2">
            <b-form-checkbox v-model="checkedBidsDescriptions">
              <h5>Each bid offer is correct.</h5>
            </b-form-checkbox>
            <b-form-checkbox v-model="checkedBidsPrice">
              <h5>Each bid price is correct.</h5>
            </b-form-checkbox>
          </BCol>
        </BRow>
      </div>

      <hr class="my-1" />

      <!-- Ranking section -->
      <div class="mt-4"  v-if="(unawardedWithBidPriceCount > 0) && (checkedBidsDescriptions && checkedBidsPrice)">
        <div
          v-for="(group, groupIndex) in groupedUnawardedBids"
          :key="group.item_no"
          class="mb-4"
        >
          <div>
            <b class="text-info">Item No: {{ group.item_no }}</b>
          </div>

          <table class="table table-bordered" style="width: 100%" >
            <thead>
              <tr>
                <th>Rank</th>
                <th>Quantity/Unit</th>
                <th>ABC</th>
                <th>Total Bid Price</th>
                <th>Bid Description</th>
                <th>Technical Proposal</th>
                <!-- <th>Delivery Term</th> -->
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
              <template #item="{ element, index }">
                <tr v-if="element.bid_price > 0">
                  <td class="text-center">{{ element.rank }}</td>
                  <td>
                    <span class="m-2">{{ element.item_quantity }}</span>
                    <span>
                      {{
                        element.item_quantity > 1
                          ? element.item_unit_type?.name_long
                          : element.item_unit_type?.name_short
                      }}
                    </span>
                  </td>
                  <td>{{ formatCurrency(element.total_cost) }}</td>
                  <td>
                    {{ formatCurrency(element.bid_price * element.item_quantity) }}
                  </td>
                  <td><span v-html="element.item_description"></span></td>
                  <td><span v-html="element.technical_proposal"></span></td>
                  <!-- <td><span v-html="element.delivery_term"></span></td> -->
                  <td>{{ element.supplier.name }}</td>
                </tr>
              </template>
            </draggable>
          </table>
        </div>

        <div>
          <b-form-checkbox v-model="checkedBidsRank"  v-if="unawardedWithBidPriceCount > 0">
            <h5>The bid items are correctly ranked from first to last.</h5>
          </b-form-checkbox>
        </div>
      </div>
    </form>

    <template #footer >
      <b-button @click="hide()" variant="light" block>No</b-button>
      <b-button @click="submit()" variant="success" block :disabled="!rankChecked"  v-if="unawardedWithBidPriceCount > 0" 
        >Yes</b-button
      >
        <b-button @click="submit()" variant="success" block :disabled="!bothChecked"  v-else
        >Yes</b-button 
      >


    </template>
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
    };
  },

 computed: {
  bothChecked() {
    // if NO items to rank → require 2 checks
    if (this.checkedBidsDescriptions  && this.checkedBidsPrice) {
        return true;
    }

  },

  rankChecked() {
      if (this.checkedBidsDescriptions  && this.checkedBidsPrice && this.checkedBidsRank) {
          return true;
      }
  },

  unawardedWithBidPriceCount() {
    return this.groupedUnawardedBids.reduce((count, group) => {
      return (
        count +
        group.quotations.filter(
          q => q.bid_price !== null && q.bid_price > 0
        ).length
      );
    }, 0);
  }


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

    sortData() {
      this.bidsForAward = [];
      this.bidsNotForAward = [];
      const grouped = {};


    this.procurement.quotations.forEach((quotation) => {
       // 🔹 Only process items with status_id === 46 or "Pending"
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
          total_bid_price: item.bid_price * item.item.item_quantity,
          technical_proposal: item.technical_proposal,
          delivery_term: item.delivery_term,
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


      // 🔽 Sort each group by lowest total bid price
      this.groupedUnawardedBids = Object.entries(grouped)
        .sort(([a], [b]) => parseInt(a) - parseInt(b))
        .map(([item_no, quotations]) => ({
          item_no,
          quotations: quotations
            .sort(
              (a, b) =>
                parseFloat(a.bid_price) * a.item_quantity - parseFloat(b.bid_price) * b.item_quantity
            )
            .map((quotation, index) => ({
              ...quotation,
              rank: index + 2,
            })),
        }));


     

      // // Flatten for posting
      this.bidsNotForAward = Object.entries(grouped)
        .sort(([a], [b]) => parseInt(a) - parseInt(b))
        .flatMap(([item_no, quotations]) =>
          quotations
            .sort((a, b) => parseFloat(a.bid_price) * a.item_quantity - parseFloat(b.bid_price) * b.item_quantity)
            .map((quotation, index) => ({
              ...quotation,
              rank: index + 1,
            }))
        );


    },
   
    updateRanks(groupIndex) {
      this.groupedUnawardedBids[groupIndex].quotations.forEach((quotation, index) => {
        quotation.rank = index + 1;
      });
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
          onSuccess: (response) => {
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
tr,
td,
th {
  border: 1px solid;
  padding: 2px;
  vertical-align: top;
  text-align: left;
}
th {
  text-align: center;
}
</style>
