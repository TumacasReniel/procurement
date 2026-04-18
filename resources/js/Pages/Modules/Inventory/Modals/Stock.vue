<template>
  <b-modal
    :model-value="modelValue"
    header-class="p-3 bg-light"
    :title="form?.id ? 'Edit Stock' : 'Add Stock'"
    class="v-modal-custom"
    modal-class="zoomIn"
    centered
    no-close-on-backdrop
    hide-footer
    @update:modelValue="(value) => $emit('update:modelValue', value)"
  >
    <form class="customform" @submit.prevent="$emit('submit')">
      <BRow class="g-3">
        <BCol lg="6" class="mt-0">
          <InputLabel for="stock_code" value="Code" :message="errors.code" />
          <TextInput id="stock_code" :model-value="form.code" type="text" class="form-control" :light="true" @update:modelValue="updateField('code', $event)" />
        </BCol>
        <BCol lg="6" class="mt-0">
          <InputLabel for="stock_name" value="Name" :message="errors.name" />
          <TextInput id="stock_name" :model-value="form.name" type="text" class="form-control" :light="true" @update:modelValue="updateField('name', $event)" />
        </BCol>
        <BCol lg="12" class="mt-0">
          <InputLabel for="entry_date" value="Entry Date" :message="errors.entry_date" />
          <TextInput
            id="entry_date"
            :model-value="form.entry_date"
            type="datetime-local"
            class="form-control"
            :light="true"
            @update:modelValue="updateField('entry_date', $event)"
          />
          <div class="invalid-feedback d-block" v-if="errors.entry_date">{{ errors.entry_date }}</div>
        </BCol>

        <BCol lg="12" class="mt-2">
          <div class="d-flex justify-content-end gap-2">
            <b-button type="button" variant="light" @click="$emit('update:modelValue', false)">Cancel</b-button>
            <b-button type="submit" variant="primary" :disabled="saving">
              {{ saving ? 'Saving...' : (form?.id ? 'Update' : 'Submit') }}
            </b-button>
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
  name: 'StockModal',
  components: { InputLabel, TextInput },
  props: {
    modelValue: { type: Boolean, default: false },
    form: { type: Object, default: () => ({}) },
    errors: { type: Object, default: () => ({}) },
    saving: { type: Boolean, default: false },
  },
  emits: ['update:modelValue', 'update:form', 'submit'],
  methods: {
    updateField(field, value) {
      this.$emit('update:form', {
        ...this.form,
        [field]: value,
      });
    },
  },
};
</script>
