<template>
  <b-modal
    :model-value="modelValue"
    header-class="p-3 bg-body-tertiary border-bottom"
    content-class="border-0 shadow-lg"
    body-class="bg-body"
    footer-class="bg-body-tertiary border-top"
    :title="form?.id ? 'Edit Stock' : 'Add Stock'"
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
          <i class="ri-stack-line"></i>
        </span>
        <div>
          <h6 class="mb-1">{{ form?.id ? 'Update stock group' : 'Create a stock group' }}</h6>
          <p class="mb-0 text-muted">Use stock groups to organize inventory items by shelf, storage area, or supply category. The entry date is saved automatically.</p>
        </div>
      </div>

      <div class="card border shadow-none mb-0">
        <div class="card-body">
        <BRow class="g-3">
          <BCol v-if="form?.id" lg="6" class="mt-0">
            <InputLabel for="stock_code" value="Code" :message="errors.code" />
            <TextInput
              id="stock_code"
              :model-value="form.code"
              type="text"
              class="form-control"
              :light="true"
              placeholder="e.g. STK-042026-0001"
              @update:modelValue="updateField('code', $event)"
            />
          </BCol>
          <BCol :lg="form?.id ? 6 : 12" class="mt-0">
            <InputLabel for="stock_name" value="Name" :message="errors.name" />
            <TextInput id="stock_name" :model-value="form.name" type="text" class="form-control" :light="true" placeholder="Enter stock name" @update:modelValue="updateField('name', $event)" />
          </BCol>
        </BRow>
        </div>
      </div>
    </form>

    <template #footer>
      <b-button type="button" variant="light" @click="$emit('update:modelValue', false)">Cancel</b-button>
      <b-button type="button" variant="primary" :disabled="saving" @click="$emit('submit')">
        {{ form?.id ? 'Update Stock' : 'Save Stock' }}
      </b-button>
    </template>
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
