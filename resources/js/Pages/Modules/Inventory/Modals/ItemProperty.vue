<template>
  <b-modal
    :model-value="modelValue"
    header-class="p-0 border-0"
    content-class="border-0 shadow-lg rounded-4 overflow-hidden"
    body-class="p-0"
    footer-class="border-top px-4 py-3 bg-body-tertiary"
    :title="form?.id ? 'Edit Property Record' : 'Add Property Record'"
    size="md"
    class="v-modal-custom"
    modal-class="zoomIn"
    centered
    no-close-on-backdrop
    @update:modelValue="(value) => $emit('update:modelValue', value)"
  >
    <template #header="{ hide }">
      <div class="property-modal-header d-flex align-items-center justify-content-between gap-3 w-100 px-4 py-3">
        <div class="d-flex align-items-center gap-3">
          <div class="property-modal-icon">
            <i class="ri-shield-star-line"></i>
          </div>
          <div>
            <h5 class="mb-0 fw-bold text-white">
              {{ form?.id ? 'Edit Property Record' : 'Add Property Record' }}
            </h5>
            <p class="mb-0 small" style="color: rgba(255,255,255,.72)">
              Track a serialized/tagged fixed asset under an inventory item
            </p>
          </div>
        </div>
        <button type="button" class="property-modal-close" @click="hide">
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
          <label class="form-label fw-semibold" for="property_item_id">
            Item <span class="text-danger">*</span>
          </label>
          <Multiselect
            id="property_item_id"
            :model-value="form.inventory_item_id ? String(form.inventory_item_id) : ''"
            :options="itemOptions"
            :searchable="true"
            :class="{ 'is-invalid': errors.inventory_item_id }"
            placeholder="Select an inventory item"
            @update:modelValue="updateField('inventory_item_id', $event)"
          />
          <div v-if="errors.inventory_item_id" class="invalid-feedback d-block">{{ errors.inventory_item_id[0] }}</div>
        </div>

        <div v-if="form.property_code" class="col-12">
          <label class="form-label fw-semibold">Property Code</label>
          <input type="text" class="form-control" :value="form.property_code" disabled />
          <div class="form-text">Auto-generated on create.</div>
        </div>

        <div class="col-sm-6">
          <label class="form-label fw-semibold" for="property_model">
            Model <span class="text-danger">*</span>
          </label>
          <input
            id="property_model"
            type="text"
            :value="form.model"
            class="form-control"
            :class="{ 'is-invalid': errors.model }"
            placeholder="e.g. Dell Latitude 5420"
            @input="updateField('model', $event.target.value)"
          />
          <div v-if="errors.model" class="invalid-feedback">{{ errors.model[0] }}</div>
        </div>

        <div class="col-sm-6">
          <label class="form-label fw-semibold" for="property_serial_no">
            Serial No. <span class="text-danger">*</span>
          </label>
          <input
            id="property_serial_no"
            type="text"
            :value="form.serial_no"
            class="form-control"
            :class="{ 'is-invalid': errors.serial_no }"
            placeholder="Serial / asset tag number"
            @input="updateField('serial_no', $event.target.value)"
          />
          <div v-if="errors.serial_no" class="invalid-feedback">{{ errors.serial_no[0] }}</div>
        </div>

        <div class="col-sm-6">
          <label class="form-label fw-semibold" for="property_acquisition_date">
            Acquisition Date <span class="text-danger">*</span>
          </label>
          <input
            id="property_acquisition_date"
            type="date"
            :value="form.acquisition_date"
            class="form-control"
            :class="{ 'is-invalid': errors.acquisition_date }"
            @input="updateField('acquisition_date', $event.target.value)"
          />
          <div v-if="errors.acquisition_date" class="invalid-feedback">{{ errors.acquisition_date[0] }}</div>
        </div>

        <div class="col-sm-6">
          <label class="form-label fw-semibold" for="property_status">
            Status <span class="text-danger">*</span>
          </label>
          <select
            id="property_status"
            :value="form.status"
            class="form-select"
            :class="{ 'is-invalid': errors.status }"
            @change="updateField('status', $event.target.value)"
          >
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>
          <div v-if="errors.status" class="invalid-feedback">{{ errors.status[0] }}</div>
        </div>

        <div class="col-sm-6">
          <label class="form-label fw-semibold" for="property_acquisition_cost">
            Acquisition Cost <span class="text-danger">*</span>
          </label>
          <div class="input-group" :class="{ 'is-invalid': errors.acquisition_cost }">
            <span class="input-group-text">₱</span>
            <input
              id="property_acquisition_cost"
              type="number"
              min="0"
              step="0.01"
              :value="form.acquisition_cost"
              class="form-control"
              :class="{ 'is-invalid': errors.acquisition_cost }"
              placeholder="0.00"
              @input="updateField('acquisition_cost', $event.target.value)"
            />
          </div>
          <div v-if="errors.acquisition_cost" class="invalid-feedback d-block">{{ errors.acquisition_cost[0] }}</div>
        </div>

        <div class="col-sm-6">
          <label class="form-label fw-semibold" for="property_depreciation_rate">
            Depreciation Rate <span class="text-danger">*</span>
          </label>
          <div class="input-group" :class="{ 'is-invalid': errors.depreciation_rate }">
            <input
              id="property_depreciation_rate"
              type="number"
              min="0"
              max="100"
              step="0.01"
              :value="form.depreciation_rate"
              class="form-control"
              :class="{ 'is-invalid': errors.depreciation_rate }"
              placeholder="0.00"
              @input="updateField('depreciation_rate', $event.target.value)"
            />
            <span class="input-group-text">% / year</span>
          </div>
          <div v-if="errors.depreciation_rate" class="invalid-feedback d-block">{{ errors.depreciation_rate[0] }}</div>
        </div>

        <div class="col-12">
          <label class="form-label fw-semibold" for="property_remarks">Remarks</label>
          <textarea
            id="property_remarks"
            :value="form.remarks"
            class="form-control"
            :class="{ 'is-invalid': errors.remarks }"
            rows="3"
            placeholder="Optional notes about this property record…"
            @input="updateField('remarks', $event.target.value)"
          ></textarea>
          <div v-if="errors.remarks" class="invalid-feedback">{{ errors.remarks[0] }}</div>
        </div>
      </div>
    </form>

    <template #footer>
      <div class="d-flex gap-2 justify-content-end w-100">
        <b-button variant="light" class="px-4" @click="$emit('update:modelValue', false)">Cancel</b-button>
        <b-button variant="primary" class="px-4" :disabled="saving" @click="$emit('submit')">
          <span v-if="saving"><span class="spinner-border spinner-border-sm me-2"></span>Saving…</span>
          <span v-else>{{ form?.id ? 'Update Property' : 'Save Property' }}</span>
        </b-button>
      </div>
    </template>
  </b-modal>
</template>

<script>
import Multiselect from '@vueform/multiselect';

export default {
  name: 'ItemPropertyModal',
  components: { Multiselect },
  props: {
    modelValue: { type: Boolean, default: false },
    form:       { type: Object, default: () => ({}) },
    errors:     { type: Object, default: () => ({}) },
    saving:     { type: Boolean, default: false },
    items:      { type: Array, default: () => [] },
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
.property-modal-header {
  background: linear-gradient(135deg, #4b5b93 0%, #38467a 100%);
  min-height: 74px;
}
.property-modal-icon {
  width: 42px; height: 42px;
  border-radius: 14px;
  background: rgba(255,255,255,.15);
  display: inline-flex; align-items: center; justify-content: center;
  font-size: 1.25rem; color: #fff; flex-shrink: 0;
}
.property-modal-close {
  width: 34px; height: 34px; border-radius: 999px;
  border: 1px solid rgba(255,255,255,.22);
  background: rgba(255,255,255,.1); color: #fff;
  display: inline-flex; align-items: center; justify-content: center;
  font-size: 1.1rem; flex-shrink: 0; transition: background .15s;
}
.property-modal-close:hover { background: rgba(255,255,255,.22); }
</style>
