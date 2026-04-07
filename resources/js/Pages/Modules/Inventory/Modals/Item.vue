<template>
  <b-modal
    :model-value="modelValue"
    header-class="p-3 bg-light"
    :title="form?.id ? 'Edit Item' : 'Add Item'"
    class="v-modal-custom"
    modal-class="zoomIn"
    centered
    no-close-on-backdrop
    hide-footer
    @update:modelValue="(value) => $emit('update:modelValue', value)"
  >
    <form class="customform" @submit.prevent="$emit('submit')">
      <BRow class="g-3">
        <BCol lg="6">
          <InputLabel for="item_code" value="Code" :message="errors.code" />
          <TextInput id="item_code" :model-value="form.code" type="text" class="form-control" :light="true" @update:modelValue="updateField('code', $event)" />
        </BCol>
        <BCol lg="6">
          <InputLabel for="item_name" value="Name" :message="errors.name" />
          <TextInput id="item_name" :model-value="form.name" type="text" class="form-control" :light="true" @update:modelValue="updateField('name', $event)" />
        </BCol>
        <BCol lg="6">
          <InputLabel for="stock_id" value="Stock" :message="errors.stock_id" />
          <select id="stock_id" :value="form.stock_id" class="form-select" @change="updateField('stock_id', $event.target.value)">
            <option value="">Select Stock</option>
            <option v-for="stock in stocks" :key="stock.id" :value="String(stock.id)">{{ stock.name }} ({{ stock.code }})</option>
          </select>
        </BCol>
        <BCol lg="6">
          <InputLabel for="category_id" value="Category" :message="errors.category_id" />
          <select id="category_id" :value="form.category_id" class="form-select" @change="updateField('category_id', $event.target.value)">
            <option value="">Select Category</option>
            <option v-for="category in categories" :key="category.id ?? category.value" :value="String(category.id ?? category.value)">{{ category.name }}</option>
          </select>
        </BCol>
        <BCol lg="4">
          <InputLabel for="item_quantity" value="Quantity" :message="errors.quantity" />
          <TextInput id="item_quantity" :model-value="form.quantity" type="number" class="form-control" :light="true" @update:modelValue="updateField('quantity', $event)" />
        </BCol>
        <BCol lg="4">
          <InputLabel for="unit_cost" value="Unit Cost" :message="errors.unit_cost" />
          <TextInput id="unit_cost" :model-value="form.unit_cost" type="number" class="form-control" :light="true" @update:modelValue="updateField('unit_cost', $event)" />
        </BCol>
        <BCol lg="4">
          <InputLabel for="expiration" value="Expiration" :message="errors.expiration" />
          <TextInput id="expiration" :model-value="form.expiration" type="date" class="form-control" :light="true" @update:modelValue="updateField('expiration', $event)" />
        </BCol>
        <BCol lg="12" class="mt-2">
          <div class="d-flex justify-content-end gap-2">
            <b-button type="button" variant="light" @click="$emit('update:modelValue', false)">Cancel</b-button>
            <b-button type="submit" variant="primary" :disabled="saving">{{ saving ? 'Saving...' : (form?.id ? 'Update' : 'Submit') }}</b-button>
          </div>
        </BCol>
      </BRow>
    </form>
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
  },
  emits: ['update:modelValue', 'update:form', 'submit'],
  methods: {
    updateField(field, value) {
      this.$emit('update:form', { ...this.form, [field]: value });
    },
  },
};
</script>
