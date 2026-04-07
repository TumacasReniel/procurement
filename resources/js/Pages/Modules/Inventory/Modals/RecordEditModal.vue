<template>
  <b-modal
    :model-value="modelValue"
    title="Edit Record"
    centered
    header-class="p-3 bg-light"
    class="v-modal-custom"
    modal-class="zoomIn"
    hide-footer
    @update:modelValue="(value) => $emit('update:modelValue', value)"
  >
    <div class="text-muted">
      Dedicated edit flow is currently available for stock records from the stock tables.
    </div>
  </b-modal>
</template>

<script>
export default {
  name: 'RecordEditModal',
  props: {
    modelValue: { type: Boolean, default: false },
    type: { type: String, default: '' },
    form: { type: Object, default: null },
  },
  emits: ['update:modelValue', 'save'],
};
</script>
