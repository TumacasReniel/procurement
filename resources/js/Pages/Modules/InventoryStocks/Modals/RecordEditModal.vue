<template>
  <b-modal
    :model-value="modelValue"
    :title="modalTitle"
    centered
    header-class="p-3 bg-light"
    body-class="p-4"
    class="v-modal-custom"
    modal-class="zoomIn"
    hide-footer
    @update:modelValue="(value) => $emit('update:modelValue', value)"
  >
    <div class="row g-3 inventory-modal-form">
      <template v-if="type === 'receiving'">
        <div class="col-md-6">
          <label class="form-label">P.O. Number</label>
          <input v-model="localForm.po_number" type="text" class="form-control" />
        </div>
        <div class="col-md-6">
          <label class="form-label">Supplier Name</label>
          <input v-model="localForm.supplier_name" type="text" class="form-control" />
        </div>
        <div class="col-12">
          <label class="form-label">Remarks</label>
          <textarea v-model="localForm.remarks" rows="2" class="form-control"></textarea>
        </div>
        <div class="col-md-6">
          <label class="form-label">Date</label>
          <input v-model="localForm.received_at" type="text" class="form-control" />
        </div>
        <div class="col-md-6">
          <label class="form-label">Status</label>
          <input v-model="localForm.status" type="text" class="form-control" />
        </div>
      </template>

      <template v-else-if="type === 'withdrawal'">
        <div class="col-md-6">
          <label class="form-label">Reference No</label>
          <input v-model="localForm.reference_no" type="text" class="form-control" />
        </div>
        <div class="col-md-6">
          <label class="form-label">Requested By</label>
          <input v-model="localForm.requested_by" type="text" class="form-control" />
        </div>
        <div class="col-md-6">
          <label class="form-label">Item</label>
          <input v-model="localForm.item_name" type="text" class="form-control" />
        </div>
        <div class="col-md-6">
          <label class="form-label">Quantity</label>
          <input v-model.number="localForm.quantity" type="number" min="0" step="0.01" class="form-control" />
        </div>
        <div class="col-md-6">
          <label class="form-label">Date Released</label>
          <input v-model="localForm.released_at" type="text" class="form-control" />
        </div>
        <div class="col-md-6">
          <label class="form-label">Status</label>
          <input v-model="localForm.status" type="text" class="form-control" />
        </div>
      </template>
    </div>

    <div class="d-flex justify-content-end gap-2 mt-3">
      <button type="button" class="btn btn-light" @click="$emit('update:modelValue', false)">Cancel</button>
      <button type="button" class="btn btn-primary" @click="save">Save Changes</button>
    </div>
  </b-modal>
</template>

<script>
export default {
  name: 'InventoryRecordEditModal',
  props: {
    modelValue: { type: Boolean, default: false },
    type: { type: String, default: '' },
    form: { type: Object, default: () => ({}) },
  },
  emits: ['update:modelValue', 'save'],
  data() {
    return {
      localForm: {},
    };
  },
  computed: {
    modalTitle() {
      if (this.type === 'receiving') return 'Edit Purchase Order Receiving';
      if (this.type === 'withdrawal') return 'Edit Inventory Withdrawal';
      return 'Edit Inventory Record';
    },
  },
  watch: {
    form: {
      handler(value) {
        this.localForm = { ...(value || {}) };
      },
      immediate: true,
      deep: true,
    },
  },
  methods: {
    save() {
      this.$emit('save', {
        type: this.type,
        data: { ...this.localForm },
      });
    },
  },
};
</script>


<style scoped>
.inventory-modal-form .form-label {
  font-size: 12px;
  font-weight: 700;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}
</style>
