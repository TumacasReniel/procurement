<template>
  <b-modal
    :model-value="modelValue"
    title="Edit Record"
    centered
    header-class="p-3 bg-body-tertiary border-bottom"
    content-class="border-0 shadow-lg"
    body-class="bg-body"
    footer-class="bg-body-tertiary border-top"
    class="v-modal-custom"
    modal-class="zoomIn"
    @update:modelValue="(value) => $emit('update:modelValue', value)"
  >
    <div class="alert alert-info bg-info-subtle border-info-subtle text-body mb-0 d-flex align-items-start gap-3">
      <span class="avatar-title rounded bg-primary-subtle text-primary fs-4 flex-shrink-0" style="width: 42px; height: 42px">
        <i class="ri-edit-2-line"></i>
      </span>
      <div>
        <h6 class="mb-1">Edit from the selected ledger</h6>
        <p class="mb-0 text-muted">Dedicated edit flows are available through the action buttons in each inventory table.</p>
      </div>
    </div>

    <template #footer>
      <b-button type="button" variant="light" @click="$emit('update:modelValue', false)">Close</b-button>
    </template>
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
