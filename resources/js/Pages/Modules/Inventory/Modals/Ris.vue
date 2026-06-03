<template>
  <b-modal
    :model-value="modelValue"
    header-class="p-0 border-0"
    content-class="border-0 shadow-lg rounded-4 overflow-hidden"
    body-class="p-0"
    footer-class="border-top px-4 py-3 bg-body-tertiary"
    size="xl"
    centered
    scrollable
    no-close-on-backdrop
    @update:modelValue="(v) => $emit('update:modelValue', v)"
  >
    <template #header="{ hide }">
      <div class="ris-modal-header d-flex align-items-center justify-content-between gap-3 w-100 px-4 py-3">
        <div class="d-flex align-items-center gap-3">
          <div class="ris-modal-icon"><i class="ri-file-list-3-line"></i></div>
          <div>
            <h5 class="mb-0 fw-bold text-white">{{ form?.id ? 'Edit RIS' : 'New Requisition and Issue Slip' }}</h5>
            <p class="mb-0 small" style="color:rgba(255,255,255,.72)">{{ form?.id ? form.ris_no : 'Auto-generated RIS number' }}</p>
          </div>
        </div>
        <button type="button" class="ris-modal-close" @click="hide"><i class="ri-close-line"></i></button>
      </div>
    </template>

    <div class="px-4 py-4">

      <!-- Global error summary -->
      <div v-if="hasErrors" class="alert alert-danger d-flex align-items-start gap-2 py-2 px-3 mb-3">
        <i class="ri-error-warning-line fs-5 flex-shrink-0 mt-1"></i>
        <div>
          <strong class="d-block mb-1">Please fix the following errors:</strong>
          <ul class="mb-0 ps-3">
            <li v-for="(msgs, field) in errors" :key="field">{{ msgs[0] }}</li>
          </ul>
        </div>
      </div>

      <!-- Header fields -->
      <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-3">
          <label class="form-label fw-semibold">RIS Date <span class="text-danger">*</span></label>
          <input type="date" :value="form.ris_date" class="form-control" :class="{'is-invalid': errors.ris_date}"
            @change="updateField('ris_date', $event.target.value)" />
          <div v-if="errors.ris_date" class="invalid-feedback">{{ errors.ris_date[0] }}</div>
        </div>
        <div class="col-sm-6 col-lg-3">
          <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
          <select :value="form.status_id" class="form-select" :class="{'is-invalid': errors.status_id}"
            @change="updateField('status_id', $event.target.value)">
            <option value="">— Select —</option>
            <option v-for="s in statuses" :key="s.id" :value="String(s.id)">{{ s.name }}</option>
          </select>
          <div v-if="errors.status_id" class="invalid-feedback">{{ errors.status_id[0] }}</div>
        </div>
        <div class="col-sm-6 col-lg-3">
          <label class="form-label fw-semibold">Fund Cluster</label>
          <input type="text" :value="form.fund_cluster" class="form-control"
            :class="{'is-invalid': errors.fund_cluster}"
            placeholder="e.g. 01"
            @input="updateField('fund_cluster', $event.target.value)" />
          <div v-if="errors.fund_cluster" class="invalid-feedback">{{ errors.fund_cluster[0] }}</div>
        </div>
        <div class="col-sm-6 col-lg-3">
          <label class="form-label fw-semibold">Division / Office</label>
          <input type="text" :value="form.division" class="form-control"
            :class="{'is-invalid': errors.division}"
            placeholder="e.g. Admin"
            @input="updateField('division', $event.target.value)" />
          <div v-if="errors.division" class="invalid-feedback">{{ errors.division[0] }}</div>
        </div>
        <div class="col-sm-6">
          <label class="form-label fw-semibold">Responsibility Center</label>
          <input type="text" :value="form.responsibility_center" class="form-control"
            :class="{'is-invalid': errors.responsibility_center}"
            @input="updateField('responsibility_center', $event.target.value)" />
          <div v-if="errors.responsibility_center" class="invalid-feedback">{{ errors.responsibility_center[0] }}</div>
        </div>
        <div class="col-sm-6">
          <label class="form-label fw-semibold">Purpose</label>
          <input type="text" :value="form.purpose" class="form-control"
            :class="{'is-invalid': errors.purpose}"
            placeholder="Purpose of requisition"
            @input="updateField('purpose', $event.target.value)" />
          <div v-if="errors.purpose" class="invalid-feedback">{{ errors.purpose[0] }}</div>
        </div>
        <div class="col-sm-6 col-lg-3">
          <label class="form-label fw-semibold">Requested By</label>
          <select :value="form.requested_by_id" class="form-select"
            :class="{'is-invalid': errors.requested_by_id}"
            @change="updateField('requested_by_id', $event.target.value)">
            <option value="">— Select —</option>
            <option v-for="u in users" :key="u.id" :value="String(u.id)">{{ u.name }}</option>
          </select>
          <div v-if="errors.requested_by_id" class="invalid-feedback">{{ errors.requested_by_id[0] }}</div>
        </div>
        <div class="col-sm-6 col-lg-3">
          <label class="form-label fw-semibold">Approved By</label>
          <select :value="form.approved_by_id" class="form-select"
            :class="{'is-invalid': errors.approved_by_id}"
            @change="updateField('approved_by_id', $event.target.value)">
            <option value="">— Select —</option>
            <option v-for="u in users" :key="u.id" :value="String(u.id)">{{ u.name }}</option>
          </select>
          <div v-if="errors.approved_by_id" class="invalid-feedback">{{ errors.approved_by_id[0] }}</div>
        </div>
        <div class="col-sm-6 col-lg-3">
          <label class="form-label fw-semibold">Issued By</label>
          <select :value="form.issued_by_id" class="form-select"
            :class="{'is-invalid': errors.issued_by_id}"
            @change="updateField('issued_by_id', $event.target.value)">
            <option value="">— Select —</option>
            <option v-for="u in users" :key="u.id" :value="String(u.id)">{{ u.name }}</option>
          </select>
          <div v-if="errors.issued_by_id" class="invalid-feedback">{{ errors.issued_by_id[0] }}</div>
        </div>
        <div class="col-sm-6 col-lg-3">
          <label class="form-label fw-semibold">Received By</label>
          <select :value="form.received_by_id" class="form-select"
            :class="{'is-invalid': errors.received_by_id}"
            @change="updateField('received_by_id', $event.target.value)">
            <option value="">— Select —</option>
            <option v-for="u in users" :key="u.id" :value="String(u.id)">{{ u.name }}</option>
          </select>
          <div v-if="errors.received_by_id" class="invalid-feedback">{{ errors.received_by_id[0] }}</div>
        </div>
      </div>

      <!-- Line Items -->
      <div class="ris-items-section" :class="{ 'ris-items-error': errors.items_empty }">
        <div class="d-flex align-items-center justify-content-between mb-2">
          <div>
            <h6 class="mb-0 fw-bold">Items Requested</h6>
            <div v-if="errors.items_empty" class="text-danger small mt-1">
              <i class="ri-error-warning-line me-1"></i>{{ errors.items_empty[0] }}
            </div>
          </div>
          <button type="button" class="btn btn-sm btn-primary rounded-pill px-3" @click="addLine">
            <i class="ri-add-line me-1"></i>Add Item
          </button>
        </div>
        <div class="table-responsive">
          <table class="table table-sm align-middle mb-0 ris-item-table">
            <thead class="table-light">
              <tr>
                <th>Item</th>
                <th style="width:100px">Unit of Issue</th>
                <th style="width:110px" class="text-center">Qty Requested</th>
                <th style="width:110px" class="text-center">Qty Issued</th>
                <th>Remarks</th>
                <th style="width:40px"></th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="!form.items?.length">
                <td colspan="6" class="text-center text-muted py-3 fst-italic">No items added yet. Click "Add Item" to begin.</td>
              </tr>
              <tr v-else v-for="(line, i) in form.items" :key="i">
                <td>
                  <Multiselect
                    :model-value="line.item_id ? String(line.item_id) : ''"
                    :options="itemOptions"
                    :searchable="true"
                    :class="{ 'is-invalid': lineError(i, 'item_id') }"
                    placeholder="Select item"
                    @update:modelValue="updateLine(i, 'item_id', $event)"
                  />
                  <div v-if="lineError(i, 'item_id')" class="invalid-feedback d-block">{{ lineError(i, 'item_id') }}</div>
                </td>
                <td>
                  <input type="text" :value="line.unit_of_issue" class="form-control form-control-sm" placeholder="pcs"
                    @input="updateLine(i, 'unit_of_issue', $event.target.value)" />
                </td>
                <td>
                  <input type="number" min="0" step="0.01" :value="line.quantity_requested" class="form-control form-control-sm text-center"
                    :class="{ 'is-invalid': lineError(i, 'quantity_requested') }"
                    @input="updateLine(i, 'quantity_requested', $event.target.value)" />
                </td>
                <td>
                  <input type="number" min="0" step="0.01" :value="line.quantity_issued" class="form-control form-control-sm text-center"
                    @input="updateLine(i, 'quantity_issued', $event.target.value)" />
                </td>
                <td>
                  <input type="text" :value="line.remarks" class="form-control form-control-sm"
                    @input="updateLine(i, 'remarks', $event.target.value)" />
                </td>
                <td class="text-center">
                  <button type="button" class="btn btn-sm btn-outline-danger px-1 py-0" @click="removeLine(i)" title="Remove">
                    <i class="ri-delete-bin-line"></i>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <template #footer>
      <div class="d-flex gap-2 justify-content-end w-100">
        <b-button variant="light" class="px-4" @click="$emit('update:modelValue', false)">Cancel</b-button>
        <b-button variant="primary" class="px-4" :disabled="saving" @click="$emit('submit')">
          <span v-if="saving"><span class="spinner-border spinner-border-sm me-2"></span>Saving…</span>
          <span v-else>{{ form?.id ? 'Update RIS' : 'Save RIS' }}</span>
        </b-button>
      </div>
    </template>
  </b-modal>
</template>

<script>
import Multiselect from '@vueform/multiselect';

export default {
  name: 'RisModal',
  components: { Multiselect },
  props: {
    modelValue: { type: Boolean, default: false },
    form:       { type: Object, default: () => ({}) },
    errors:     { type: Object, default: () => ({}) },
    saving:     { type: Boolean, default: false },
    items:      { type: Array, default: () => [] },
    users:      { type: Array, default: () => [] },
    statuses:   { type: Array, default: () => [] },
  },
  emits: ['update:modelValue', 'update:form', 'submit', 'update:errors'],
  computed: {
    itemOptions() {
      return this.items.map((item) => ({
        value: String(item.id),
        label: item.code ? `[${item.code}] ${item.name}` : item.name,
      }));
    },
    hasErrors() {
      return Object.keys(this.errors || {}).length > 0;
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
    updateLine(index, field, value) {
      const items = [...(this.form.items || [])];
      items[index] = { ...items[index], [field]: value };
      this.$emit('update:form', { ...this.form, items });
      const errKey = `items.${index}.${field}`;
      if (this.errors?.[errKey]) {
        const next = { ...this.errors };
        delete next[errKey];
        this.$emit('update:errors', next);
      }
    },
    addLine() {
      const items = [...(this.form.items || []), {
        item_id: '', unit_of_issue: '', quantity_requested: 1, quantity_issued: 0, remarks: '',
      }];
      this.$emit('update:form', { ...this.form, items });
      if (this.errors?.items_empty) {
        const next = { ...this.errors };
        delete next.items_empty;
        this.$emit('update:errors', next);
      }
    },
    removeLine(index) {
      const items = [...(this.form.items || [])];
      items.splice(index, 1);
      this.$emit('update:form', { ...this.form, items });
    },
    lineError(index, field) {
      const key = `items.${index}.${field}`;
      return this.errors?.[key]?.[0] ?? null;
    },
  },
};
</script>

<style scoped>
.ris-modal-header {
  background: linear-gradient(135deg, #4b5b93 0%, #38467a 100%);
  min-height: 74px;
}
.ris-modal-icon {
  width: 42px; height: 42px; border-radius: 14px;
  background: rgba(255,255,255,.15);
  display: inline-flex; align-items: center; justify-content: center;
  font-size: 1.25rem; color: #fff; flex-shrink: 0;
}
.ris-modal-close {
  width: 34px; height: 34px; border-radius: 999px;
  border: 1px solid rgba(255,255,255,.22);
  background: rgba(255,255,255,.1); color: #fff;
  display: inline-flex; align-items: center; justify-content: center;
  font-size: 1.1rem; flex-shrink: 0; transition: background .15s;
}
.ris-modal-close:hover { background: rgba(255,255,255,.22); }
.ris-items-section {
  border: 1px solid #dce4f2; border-radius: 12px; padding: 1rem;
  background: #f8fbff; transition: border-color .2s;
}
.ris-items-error {
  border-color: #f87171 !important;
  background: #fff5f5 !important;
}
.ris-item-table th { font-size: .78rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; }
</style>
