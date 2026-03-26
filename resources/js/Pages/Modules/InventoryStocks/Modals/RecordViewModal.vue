<template>
  <b-modal
    :model-value="modelValue"
    :title="modalTitle"
    centered
    hide-footer
    @update:modelValue="(value) => $emit('update:modelValue', value)"
  >
    <div v-if="record" class="detail-grid">
      <div v-for="field in fields" :key="field.label" class="detail-row">
        <div class="detail-label">{{ field.label }}</div>
        <div class="detail-value">{{ field.value || '-' }}</div>
      </div>
    </div>

    <div class="d-flex justify-content-end mt-3">
      <button type="button" class="btn btn-light" @click="$emit('update:modelValue', false)">Close</button>
    </div>
  </b-modal>
</template>

<script>
export default {
  name: 'InventoryRecordViewModal',
  props: {
    modelValue: { type: Boolean, default: false },
    type: { type: String, default: '' },
    record: { type: Object, default: null },
  },
  emits: ['update:modelValue'],
  computed: {
    modalTitle() {
      if (this.type === 'receiving') return 'Receiving Details';
      if (this.type === 'withdrawal') return 'Withdrawal Details';
      return 'Details';
    },
    fields() {
      if (!this.record) return [];

      if (this.type === 'receiving') {
        return [
          { label: 'P.O. Number', value: this.record.po_number },
          { label: 'Supplier Name', value: this.record.supplier_name },
          { label: 'Remarks', value: this.record.remarks },
          { label: 'Date', value: this.record.received_at },
          { label: 'Status', value: this.record.status },
        ];
      }

      if (this.type === 'withdrawal') {
        return [
          { label: 'Reference No', value: this.record.reference_no },
          { label: 'Requested By', value: this.record.requested_by },
          { label: 'Item', value: this.record.item_name },
          { label: 'Quantity', value: this.record.quantity },
          { label: 'Date Released', value: this.record.released_at },
          { label: 'Status', value: this.record.status },
        ];
      }

      return Object.keys(this.record).map((key) => ({
        label: key,
        value: this.record[key],
      }));
    },
  },
};
</script>

<style scoped>
.detail-grid {
  display: grid;
  gap: 10px;
}

.detail-row {
  display: grid;
  gap: 4px;
  padding: 10px 12px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  background: #f8fafc;
}

.detail-label {
  font-size: 12px;
  font-weight: 700;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.detail-value {
  color: #1e293b;
  font-weight: 600;
}
</style>
