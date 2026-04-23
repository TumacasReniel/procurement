<template>
  <b-modal
    :model-value="modelValue"
    header-class="p-3 bg-body-tertiary border-bottom"
    content-class="border-0 shadow-lg"
    body-class="bg-body"
    footer-class="bg-body-tertiary border-top"
    :title="form?.id ? 'Edit Item' : 'Add Item'"
    size="lg"
    class="v-modal-custom"
    modal-class="zoomIn"
    centered
    no-close-on-backdrop
    @update:modelValue="(value) => $emit('update:modelValue', value)"
  >
    <form class="customform" @submit.prevent="$emit('submit')">
      <div class="alert alert-info bg-info-subtle border-info-subtle text-body mb-3 d-flex align-items-start gap-3">
        <span class="avatar-title rounded bg-primary-subtle text-primary fs-4 flex-shrink-0" style="width: 42px; height: 42px">
          <i class="ri-barcode-box-line"></i>
        </span>
        <div>
          <h6 class="mb-1">{{ form?.id ? 'Update inventory item' : 'Add inventory item' }}</h6>
          <p class="mb-0 text-muted">Record the item identity, current quantity, cost, category, and expiration details.</p>
        </div>
      </div>

      <div class="card border shadow-none">
        <div class="card-body">
        <div class="fw-semibold text-body mb-3">
          <i class="ri-price-tag-3-line"></i>
          Item Profile
        </div>
        <BRow class="g-3">
          <BCol lg="6">
            <InputLabel for="item_code" value="Code" :message="errors.code" />
            <TextInput id="item_code" :model-value="form.code" type="text" class="form-control" :light="true" placeholder="e.g. ITM-001" @update:modelValue="updateField('code', $event)" />
          </BCol>
          <BCol lg="6">
            <InputLabel for="item_name" value="Name" :message="errors.name" />
            <TextInput id="item_name" :model-value="form.name" type="text" class="form-control" :light="true" placeholder="Enter item name" @update:modelValue="updateField('name', $event)" />
          </BCol>
          <BCol lg="6">
            <InputLabel for="stock_id" value="Stock" :message="errors.stock_id" />
            <select
              id="stock_id"
              :value="form.stock_id"
              class="form-select"
              :disabled="lockStock"
              @change="updateField('stock_id', $event.target.value)"
            >
              <option value="">Select Stock</option>
              <option v-for="stock in stocks" :key="stock.id" :value="String(stock.id)">{{ stock.name }} ({{ stock.code }})</option>
            </select>
            <small v-if="lockStock" class="text-muted d-block mt-1">This item will be added under the selected stock.</small>
          </BCol>
          <BCol lg="6">
            <InputLabel for="category_id" value="Category" :message="errors.category_id" />
            <select id="category_id" :value="form.category_id" class="form-select" @change="updateField('category_id', $event.target.value)">
              <option value="">Select Category</option>
              <option v-for="category in categories" :key="category.id ?? category.value" :value="String(category.id ?? category.value)">{{ category.name }}</option>
            </select>
          </BCol>
        </BRow>
        </div>
      </div>

      <div class="card border shadow-none mt-3">
        <div class="card-body">
        <div class="fw-semibold text-body mb-3">
          <i class="ri-calculator-line"></i>
          Quantity and Cost
        </div>
        <BRow class="g-3">
          <BCol lg="4">
            <InputLabel for="item_quantity" value="Quantity" :message="errors.quantity" />
            <TextInput id="item_quantity" :model-value="form.quantity" type="number" class="form-control" :light="true" placeholder="0" @update:modelValue="updateField('quantity', $event)" />
          </BCol>
          <BCol lg="4">
            <InputLabel for="unit_cost" value="Unit Cost" :message="errors.unit_cost" />
            <TextInput id="unit_cost" :model-value="form.unit_cost" type="number" class="form-control" :light="true" placeholder="0" @update:modelValue="updateField('unit_cost', $event)" />
          </BCol>
          <BCol lg="4">
            <InputLabel for="expiration" value="Expiration" :message="errors.expiration" />
            <TextInput id="expiration" :model-value="form.expiration" type="date" class="form-control" :light="true" @update:modelValue="updateField('expiration', $event)" />
          </BCol>
        </BRow>
        </div>
      </div>
    </form>

    <template #footer>
      <b-button type="button" variant="light" @click="$emit('update:modelValue', false)">Cancel</b-button>
      <b-button type="button" variant="primary" :disabled="saving" @click="$emit('submit')">
        {{ saving ? 'Saving...' : (form?.id ? 'Update Item' : 'Save Item') }}
      </b-button>
    </template>
  </b-modal>
</template>

<script>
import InputLabel from '@/Shared/Components/Forms/InputLabel.vue';
import TextInput from '@/Shared/Components/Forms/TextInput.vue';

export default {
  name: 'ItemModal',
  components: { InputLabel, TextInput },
  props: {
    modelValue: { type: Boolean, default: false },
    form: { type: Object, default: () => ({}) },
    errors: { type: Object, default: () => ({}) },
    saving: { type: Boolean, default: false },
    stocks: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
    lockStock: { type: Boolean, default: false },
  },
  emits: ['update:modelValue', 'update:form', 'submit'],
  methods: {
    updateField(field, value) {
      this.$emit('update:form', { ...this.form, [field]: value });
    },
  },
};
</script>
