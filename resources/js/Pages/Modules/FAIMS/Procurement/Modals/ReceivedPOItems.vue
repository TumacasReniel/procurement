<template>
  <b-modal
    :model-value="modelValue"
    header-class="p-3 bg-light"
    title="Received PO Items"
    size="lg"
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
        <div class="text-muted fs-12">Delivered Items</div>
        <div class="fw-semibold text-success">{{ receivedItems.length }}</div>
      </div>
    </div>

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
      <div>
        <div class="text-muted fs-12">Receiving Numbers</div>
        <div class="fw-semibold">{{ receivedDeliveryRecords.length }} record(s)</div>
      </div>
      <b-button
        v-if="po?.can_receive_delivery"
        type="button"
        size="sm"
        variant="primary"
        @click="$emit('add-receive')"
      >
        <i class="ri-add-line me-1"></i>
        Add Receive
      </b-button>
    </div>

    <div class="table-responsive border rounded mb-3">
      <table class="table align-middle mb-0">
        <thead class="table-light">
          <tr class="fs-11">
            <th style="width: 16%">RCV No.</th>
            <th style="width: 22%">Received</th>
            <th style="width: 18%">Received By</th>
            <th style="width: 14%" class="text-center">Items</th>
            <th style="width: 14%" class="text-center">Action</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="record in receivedDeliveryRecords" :key="record.id">
            <td class="fw-semibold text-primary">{{ record.code }}</td>
            <td>{{ record.received_at || "-" }}</td>
            <td>{{ record.received_by || "-" }}</td>
            <td class="text-center">
              <div class="fw-semibold">{{ record.items_count || 0 }} item(s)</div>
              <div class="text-muted fs-12">{{ formatQuantity(record.total_quantity) }} total</div>
            </td>
            <td class="text-center">
              <b-button
                v-if="canEdit"
                type="button"
                size="sm"
                variant="soft-primary"
                class="receiving-action-btn"
                @click="$emit('edit-record', record)"
              >
                <i class="ri-pencil-line me-1"></i>
                Edit
              </b-button>
            </td>
          </tr>
          <tr v-if="!receivedDeliveryRecords.length">
            <td colspan="6" class="text-center text-muted py-4">
              No receiving numbers have been recorded for this Purchase Order yet.
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div
      v-if="receivedItems.length && !canEdit"
      class="alert alert-warning py-2 fs-12"
    >
      Received items can no longer be edited after an IAR is generated.
    </div>

    <div class="table-responsive border rounded">
      <table class="table align-middle mb-0">
        <thead class="table-light">
          <tr class="fs-11">
            <th style="width: 12%">Item No</th>
            <th>Description</th>
            <th style="width: 14%" class="text-center">Received Qty</th>
            <th style="width: 12%" class="text-center">Unit</th>
            <th style="width: 16%" class="text-center">Status</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="item in receivedItems" :key="item.id">
            <td>{{ item.item_no || "-" }}</td>
            <td>
              <div class="fw-semibold">{{ item.item_name || "-" }}</div>
            </td>
            <td class="text-center">{{ formatQuantity(item.delivered_quantity) }}</td>
            <td class="text-center">{{ item.unit || "-" }}</td>
            <td class="text-center">
              <span
                class="badge rounded-pill"
                :class="`text-bg-${item.delivery_status_variant || 'secondary'}`"
              >
                {{ item.delivery_status || "Not Yet Delivered" }}
              </span>
            </td>
          </tr>
          <tr v-if="!receivedItems.length">
            <td colspan="5" class="text-center text-muted py-4">
              No received items have been recorded for this Purchase Order yet.
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <template v-slot:footer>
      <b-button variant="light" @click="$emit('update:modelValue', false)">Close</b-button>
    </template>
  </b-modal>
</template>

<script>
export default {
  name: "ReceivedPOItems",
  props: {
    modelValue: { type: Boolean, default: false },
    po: { type: Object, default: null },
    canEdit: { type: Boolean, default: false },
  },
  emits: ["update:modelValue", "add-receive", "edit-record"],
  computed: {
    receivedItems() {
      return (this.po?.delivery_monitoring_items || [])
        .filter((item) => Number(item.delivered_quantity || 0) > 0);
    },
    receivedDeliveryRecords() {
      return Array.isArray(this.po?.received_deliveries)
        ? this.po.received_deliveries
        : [];
    },
  },
  methods: {
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
.receiving-action-btn {
  --vz-btn-padding-x: 0.45rem;
  --vz-btn-padding-y: 0.18rem;
  --vz-btn-font-size: 0.7rem;
  border-color: transparent !important;
  box-shadow: none;
  line-height: 1.2;
}

.receiving-action-btn i {
  font-size: 0.78rem;
}
</style>
