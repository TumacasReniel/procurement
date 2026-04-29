<template>
  <b-modal
    :model-value="modelValue"
    header-class="p-3 bg-light"
    title="Received Items"
    size="lg"
    class="v-modal-custom"
    modal-class="zoomIn"
    centered
    @update:modelValue="$emit('update:modelValue', $event)"
  >
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
      <div>
        <div class="text-muted fs-12">Receiving No.</div>
        <div class="fw-semibold text-primary">{{ record?.code || "-" }}</div>
      </div>
      <div>
        <div class="text-muted fs-12">Purchase Order</div>
        <div class="fw-semibold">{{ record?.po_code || "-" }}</div>
      </div>
      <div class="text-md-end">
        <div class="text-muted fs-12">Received</div>
        <div class="fw-semibold">{{ record?.received_at || "-" }}</div>
      </div>
    </div>

    <div class="table-responsive border rounded">
      <table class="table align-middle mb-0">
        <thead class="table-light">
          <tr class="fs-11">
            <th style="width: 12%">Item No</th>
            <th>Description</th>
            <th style="width: 14%" class="text-center">Received Qty</th>
            <th style="width: 12%" class="text-center">Unit</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="item in items" :key="item.item_id">
            <td>{{ item.item_no || "-" }}</td>
            <td>
              <div v-html="item.description || '-'" />
            </td>
            <td class="text-center">{{ formatQuantity(item.delivered_quantity) }}</td>
            <td class="text-center">{{ item.unit || "-" }}</td>
          </tr>
          <tr v-if="!items.length">
            <td colspan="4" class="text-center text-muted py-4">
              No items were found for this receiving record.
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <template v-slot:footer>
      <b-button variant="light" @click="$emit('update:modelValue', false)">Close</b-button>
      <b-button
        v-if="record?.can_edit_received_items"
        variant="primary"
        @click="$emit('edit-record', record)"
      >
        <i class="ri-pencil-line me-1"></i>
        Edit
      </b-button>
    </template>
  </b-modal>
</template>

<script>
export default {
  name: "ReceivingRecordItems",
  props: {
    modelValue: { type: Boolean, default: false },
    record: { type: Object, default: null },
  },
  emits: ["update:modelValue", "edit-record"],
  computed: {
    items() {
      return Array.isArray(this.record?.items) ? this.record.items : [];
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
