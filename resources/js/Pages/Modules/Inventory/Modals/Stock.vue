<template>
  <b-modal
    :model-value="modelValue"
    header-class="p-0 border-0"
    content-class="border-0 shadow-lg rounded-4 overflow-hidden"
    body-class="p-0"
    footer-class="border-top px-4 py-3 bg-body-tertiary"
    :title="form?.id ? 'Edit Stock Record' : 'Add Stock Record'"
    size="md"
    class="v-modal-custom"
    modal-class="zoomIn"
    centered
    no-close-on-backdrop
    @update:modelValue="(value) => $emit('update:modelValue', value)"
  >
    <template #header="{ hide }">
      <div class="stock-modal-header d-flex align-items-center justify-content-between gap-3 w-100 px-4 py-3">
        <div class="d-flex align-items-center gap-3">
          <div class="stock-modal-icon">
            <i class="ri-stack-line"></i>
          </div>
          <div>
            <h5 class="mb-0 fw-bold text-white">
              {{ form?.id ? 'Edit Stock Record' : 'Add Stock Record' }}
            </h5>
            <p class="mb-0 small" style="color: rgba(255,255,255,.72)">
              Track quantity, unit, and unit cost for an inventory item
            </p>
          </div>
        </div>
        <button type="button" class="stock-modal-close" @click="hide">
          <i class="ri-close-line"></i>
        </button>
      </div>
    </template>

    <form class="px-4 py-4" @submit.prevent="$emit('submit')">
      <!-- Global error summary -->
      <div v-if="hasErrors" class="alert alert-danger d-flex align-items-start gap-2 py-2 px-3 mb-3">
        <i class="ri-error-warning-line fs-5 flex-shrink-0 mt-1"></i>
        <div>
          <strong class="d-block mb-1 small">Please fix the following:</strong>
          <ul class="mb-0 ps-3 small">
            <li v-for="(msgs, field) in errors" :key="field">{{ Array.isArray(msgs) ? msgs[0] : msgs }}</li>
          </ul>
        </div>
      </div>

      <div class="row g-3">
        <div class="col-12">
          <label class="form-label fw-semibold" for="stock_item_id">
            Item <span class="text-danger">*</span>
          </label>
          <Multiselect
            id="stock_item_id"
            :model-value="form.item_id ? String(form.item_id) : ''"
            :options="itemOptions"
            :searchable="true"
            :disabled="!!form.id || !!form._lock_item"
            :class="{ 'is-invalid': errors.item_id }"
            placeholder="Select an inventory item"
            @update:modelValue="updateField('item_id', $event)"
          />
          <div v-if="errors.item_id" class="invalid-feedback d-block">{{ errors.item_id[0] }}</div>
        </div>

        <div class="col-sm-6">
          <label class="form-label fw-semibold" for="stock_quantity">
            Quantity <span class="text-danger">*</span>
          </label>
          <input
            id="stock_quantity"
            type="number"
            min="0"
            step="0.01"
            :value="form.quantity"
            class="form-control"
            :class="{ 'is-invalid': errors.quantity }"
            placeholder="0"
            @input="updateField('quantity', $event.target.value)"
          />
          <div v-if="errors.quantity" class="invalid-feedback">{{ errors.quantity[0] }}</div>
        </div>

        <div class="col-sm-6">
          <label class="form-label fw-semibold" for="stock_unit_id">
            Unit <span class="text-danger">*</span>
          </label>
          <select
            id="stock_unit_id"
            :value="form.unit_id"
            class="form-select"
            :class="{ 'is-invalid': errors.unit_id }"
            @change="updateField('unit_id', $event.target.value)"
          >
            <option value="">— Select unit —</option>
            <option v-for="u in units" :key="u.id" :value="String(u.id)">
              {{ u.name_short }} — {{ u.name_long }}
            </option>
          </select>
          <div v-if="errors.unit_id" class="invalid-feedback">{{ errors.unit_id[0] }}</div>
        </div>

        <div class="col-12">
          <label class="form-label fw-semibold" for="stock_unit_cost">
            Unit Cost <span class="text-danger">*</span>
          </label>
          <div class="input-group" :class="{ 'is-invalid': errors.unit_cost }">
            <span class="input-group-text">₱</span>
            <input
              id="stock_unit_cost"
              type="number"
              min="0"
              step="0.01"
              :value="form.unit_cost"
              class="form-control"
              :class="{ 'is-invalid': errors.unit_cost }"
              placeholder="0.00"
              @input="updateField('unit_cost', $event.target.value)"
            />
          </div>
          <div v-if="errors.unit_cost" class="invalid-feedback d-block">{{ errors.unit_cost[0] }}</div>
        </div>

        <div class="col-12">
          <label class="form-label fw-semibold" for="stock_description">Description</label>
          <textarea
            id="stock_description"
            :value="form.description"
            class="form-control"
            :class="{ 'is-invalid': errors.description }"
            rows="3"
            placeholder="Optional notes about this stock entry…"
            @input="updateField('description', $event.target.value)"
          ></textarea>
          <div v-if="errors.description" class="invalid-feedback">{{ errors.description[0] }}</div>
        </div>
      </div>
    </form>

    <template #footer>
      <div class="d-flex gap-2 justify-content-end w-100">
        <b-button variant="light" class="px-4" @click="$emit('update:modelValue', false)">Cancel</b-button>
        <b-button variant="primary" class="px-4" :disabled="saving" @click="$emit('submit')">
          <span v-if="saving"><span class="spinner-border spinner-border-sm me-2"></span>Saving…</span>
          <span v-else>{{ form?.id ? 'Update Stock' : 'Save Stock' }}</span>
        </b-button>
      </div>
    </template>
  </b-modal>
</template>

<script>
import Multiselect from '@vueform/multiselect';

export default {
  name: 'StockModal',
  components: { Multiselect },
  props: {
    modelValue: { type: Boolean, default: false },
    form:       { type: Object, default: () => ({}) },
    errors:     { type: Object, default: () => ({}) },
    saving:     { type: Boolean, default: false },
    items:      { type: Array, default: () => [] },
    units:      { type: Array, default: () => [] },
  },
  emits: ['update:modelValue', 'update:form', 'update:errors', 'submit'],
  computed: {
    hasErrors() {
      return Object.keys(this.errors || {}).length > 0;
    },
    itemOptions() {
      return this.items.map((item) => ({
        value: String(item.id),
        label: item.code ? `[${item.code}] ${item.name}` : item.name,
      }));
    },
  },
  methods: {
    updateField(field, value) {
      this.$emit('update:form', { ...this.form, [field]: value });
      if (this.errors?.[field]) {
        const next = { ...this.errors };
        delete next[field];
        this.$emit('update:errors', next);
      }
    },
  },
};
</script>

<style scoped>
.stock-modal-header {
  background: linear-gradient(135deg, #4b5b93 0%, #38467a 100%);
  min-height: 74px;
}
.stock-modal-icon {
  width: 42px; height: 42px;
  border-radius: 14px;
  background: rgba(255,255,255,.15);
  display: inline-flex; align-items: center; justify-content: center;
  font-size: 1.25rem; color: #fff; flex-shrink: 0;
}
.stock-modal-close {
  width: 34px; height: 34px; border-radius: 999px;
  border: 1px solid rgba(255,255,255,.22);
  background: rgba(255,255,255,.1); color: #fff;
  display: inline-flex; align-items: center; justify-content: center;
  font-size: 1.1rem; flex-shrink: 0; transition: background .15s;
}
.stock-modal-close:hover { background: rgba(255,255,255,.22); }
</style>
