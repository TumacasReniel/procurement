<template>
  <b-modal
    :model-value="modelValue"
    header-class="p-3 bg-light"
    title="Items Left to Receive"
    size="xl"
    class="v-modal-custom"
    modal-class="zoomIn"
    centered
    @update:modelValue="$emit('update:modelValue', $event)"
  >
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
      <div>
        <div class="text-muted fs-12">Purchase Order</div>
        <div class="fw-semibold">{{ po?.code || "-" }}</div>
      </div>
      <div>
        <div class="text-muted fs-12">Supplier</div>
        <div class="fw-semibold">{{ po?.supplier_name || "-" }}</div>
      </div>
      <div class="text-md-end">
        <div class="text-muted fs-12">Items Left</div>
        <div class="fw-semibold text-warning">{{ itemsLeft.length }}</div>
      </div>
    </div>

    <div class="table-responsive border rounded">
      <table class="table align-middle mb-0">
        <thead class="table-light">
          <tr class="fs-11">
            <th style="width: 12%">Item No</th>
            <th style="width: 18%">Description</th>
            <th style="width: 14%" class="text-center">Ordered Qty</th>
            <th style="width: 14%" class="text-center">Received Qty</th>
            <th style="width: 14%" class="text-center">Left to Receive</th>
            <th style="width: 12%" class="text-center">Unit</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="item in itemsLeft" :key="item.id">
            <td class="fw-semibold">{{ item.item_no || "-" }}</td>
            <td>
              <button
                type="button"
                class="btn btn-sm btn-outline-secondary description-btn"
                @click="openDescription(item)"
              >
                View Description
              </button>
            </td>
            <td class="text-center">{{ formatQuantity(item.ordered_quantity || item.quantity) }}</td>
            <td class="text-center">{{ formatQuantity(item.delivered_quantity) }}</td>
            <td class="text-center fw-semibold text-warning">
              {{ formatQuantity(item.remaining_quantity) }}
            </td>
            <td class="text-center">{{ resolveUnit(item) }}</td>
          </tr>
          <tr v-if="!itemsLeft.length">
            <td colspan="6" class="text-center text-muted py-4">
              No items are left to receive for this Purchase Order.
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <template v-slot:footer>
      <b-button variant="light" @click="$emit('update:modelValue', false)">Close</b-button>
    </template>
  </b-modal>

  <b-modal
    v-model="showDescriptionModal"
    header-class="p-3 bg-light"
    title="Item Description"
    size="lg"
    class="v-modal-custom"
    modal-class="zoomIn"
    centered
    hide-footer
  >
    <div class="fw-semibold mb-2">{{ selectedItemName || "-" }}</div>
    <div
      v-if="selectedItemDescription"
      class="item-description-content"
      v-html="selectedItemDescription"
    ></div>
    <div v-else class="text-muted">No description available.</div>
  </b-modal>
</template>

<script>
export default {
  name: "ItemsLeftToReceive",
  props: {
    modelValue: { type: Boolean, default: false },
    po: { type: Object, default: null },
  },
  emits: ["update:modelValue"],
  data() {
    return {
      showDescriptionModal: false,
      selectedItemName: "",
      selectedItemDescription: "",
    };
  },
  computed: {
    itemsLeft() {
      return (this.po?.delivery_monitoring_items || [])
        .filter((item) => Number(item.remaining_quantity || 0) > 0);
    },
  },
  methods: {
    openDescription(item) {
      this.selectedItemName = item?.item_name || "";
      this.selectedItemDescription = item?.description || "";
      this.showDescriptionModal = true;
    },
    resolveUnit(item) {
      const quantityLeftToReceive = Number(item?.remaining_quantity ?? 0);
      const unitLong =
        item?.unit_long ||
        item?.unit_type?.name_long ||
        item?.item_unit_type?.name_long ||
        "";
      const unitShort =
        item?.unit_short ||
        item?.unit_type?.name_short ||
        item?.item_unit_type?.name_short ||
        "";

      if (quantityLeftToReceive > 1) {
        return unitLong || item?.unit || unitShort || "-";
      }

      return unitShort || item?.unit || unitLong || "-";
    },
    formatQuantity(value) {
      const numericValue = Number(value ?? 0);

      if (!Number.isFinite(numericValue)) {
        return "-";
      }

      return Number.isInteger(numericValue)
        ? numericValue.toLocaleString("en-PH")
        : numericValue.toLocaleString("en-PH", {
            minimumFractionDigits: 0,
            maximumFractionDigits: 4,
          });
    },
  },
};
</script>

<style scoped>
.description-btn {
  min-width: 118px;
  border-radius: 8px;
  font-size: 0.74rem;
  font-weight: 600;
}

.item-description-content {
  color: #334155;
  line-height: 1.5;
}

.item-description-content :deep(p:last-child),
.item-description-content :deep(ul:last-child),
.item-description-content :deep(ol:last-child) {
  margin-bottom: 0;
}
</style>
