<template>
  <b-modal
    :model-value="modelValue"
    :title="title"
    centered
    header-class="p-3 bg-light"
    class="v-modal-custom"
    modal-class="zoomIn"
    hide-footer
    @update:modelValue="(value) => $emit('update:modelValue', value)"
  >
    <div v-if="record" class="record-view-grid">
      <div v-for="field in fields" :key="field.label" class="record-view-item">
        <div class="record-view-label">{{ field.label }}</div>
        <div class="record-view-value">{{ field.value || '-' }}</div>
      </div>
    </div>
    <div v-else class="text-muted">No record selected.</div>
  </b-modal>
</template>

<script>
export default {
  name: 'RecordViewModal',
  props: {
    modelValue: { type: Boolean, default: false },
    type: { type: String, default: '' },
    record: { type: Object, default: null },
  },
  emits: ['update:modelValue'],
  computed: {
    title() {
      if (this.type === 'item') return 'View Item';
      if (this.type === 'stock') return 'View Stock';
      if (this.type === 'receiving') return 'View Receiving';
      if (this.type === 'withdrawal') return 'View Withdrawal';
      return 'View Record';
    },
    fields() {
      if (!this.record) return [];
      return Object.entries(this.record).map(([key, value]) => ({
        label: key.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase()),
        value,
      }));
    },
  },
};
</script>

<style scoped>
.record-view-grid {
  display: grid;
  gap: 0.75rem;
}

.record-view-item {
  padding: 0.85rem 1rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  background: #f8fafc;
}

.record-view-label {
  font-size: 12px;
  font-weight: 700;
  color: #64748b;
  text-transform: uppercase;
}

.record-view-value {
  color: #1e293b;
  font-weight: 600;
}
</style>
