<template>
  <b-modal
    :model-value="modelValue"
    header-class="p-3 bg-body-tertiary border-bottom"
    content-class="border-0 shadow-lg"
    body-class="bg-body"
    footer-class="bg-body-tertiary border-top"
    :title="form?.id ? 'Edit Receiving' : 'Add Receiving'"
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
          <i class="ri-inbox-archive-line"></i>
        </span>
        <div>
          <h6 class="mb-1">{{ form?.id ? 'Update receiving record' : 'Log a receiving record' }}</h6>
          <p class="mb-0 text-muted">Track items that entered inventory and record the approval and status details.</p>
        </div>
      </div>

      <div class="card border shadow-none mb-0">
        <div class="card-body">
        <div class="fw-semibold text-body mb-3">
          <i class="ri-clipboard-line"></i>
          Receiving Details
        </div>
        <BRow class="g-3">
          <BCol lg="12">
            <InputLabel for="receiving_item_id" value="Item" :message="errors.item_id" />
            <select id="receiving_item_id" :value="form.item_id" class="form-select" @change="updateField('item_id', $event.target.value)">
              <option value="">Select Item</option>
              <option v-for="item in items" :key="item.id" :value="String(item.id)">{{ item.name }} ({{ item.code }})</option>
            </select>
          </BCol>
          <BCol lg="6">
            <InputLabel for="receiving_approved_by_id" value="Approved By" :message="errors.approved_by_id" />
            <select id="receiving_approved_by_id" :value="form.approved_by_id" class="form-select" @change="updateField('approved_by_id', $event.target.value)">
              <option value="">Select Approver</option>
              <option v-for="user in users" :key="user.id" :value="String(user.id)">{{ user.name }}</option>
            </select>
          </BCol>
          <BCol lg="6">
            <InputLabel for="receiving_status_id" value="Status" :message="errors.status_id" />
            <select id="receiving_status_id" :value="form.status_id" class="form-select" @change="updateField('status_id', $event.target.value)">
              <option value="">Select Status</option>
              <option v-for="status in statuses" :key="status.id" :value="String(status.id)">{{ status.name }}</option>
            </select>
          </BCol>
          <BCol lg="12">
            <InputLabel for="received_at" value="Received At" :message="errors.received_at" />
            <TextInput id="received_at" :model-value="form.received_at" type="datetime-local" class="form-control" :light="true" @update:modelValue="updateField('received_at', $event)" />
          </BCol>
          <BCol lg="12">
            <InputLabel for="receiving_remarks" value="Remarks" :message="errors.remarks" />
            <textarea id="receiving_remarks" :value="form.remarks" class="form-control" rows="4" placeholder="Add notes or receiving references" @input="updateField('remarks', $event.target.value)"></textarea>
          </BCol>
        </BRow>
        </div>
      </div>
    </form>

    <template #footer>
      <b-button type="button" variant="light" @click="$emit('update:modelValue', false)">Cancel</b-button>
      <b-button type="button" variant="primary" :disabled="saving" @click="$emit('submit')">
        {{ saving ? 'Saving...' : (form?.id ? 'Update Receiving' : 'Save Receiving') }}
      </b-button>
    </template>
  </b-modal>
</template>

<script>
import InputLabel from '@/Shared/Components/Forms/InputLabel.vue';
import TextInput from '@/Shared/Components/Forms/TextInput.vue';

export default {
  name: 'ReceivingModal',
  components: { InputLabel, TextInput },
  props: {
    modelValue: { type: Boolean, default: false },
    form: { type: Object, default: () => ({}) },
    errors: { type: Object, default: () => ({}) },
    saving: { type: Boolean, default: false },
    items: { type: Array, default: () => [] },
    users: { type: Array, default: () => [] },
    statuses: { type: Array, default: () => [] },
  },
  emits: ['update:modelValue', 'update:form', 'submit'],
  methods: {
    updateField(field, value) {
      this.$emit('update:form', { ...this.form, [field]: value });
    },
  },
};
</script>
