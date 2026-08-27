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
            <p class="mb-0 small" style="color:rgba(255,255,255,.72)">{{ form?.id ? form.ris_no : 'Auto-generated · Status: Pending' }}</p>
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
      <div class="row g-3 mb-3">
        <!-- RIS Date -->
        <div class="col-sm-6 col-lg-3">
          <label class="form-label fw-semibold">RIS Date <span class="text-danger">*</span></label>
          <input type="date" :value="form.ris_date" class="form-control" :class="{'is-invalid': errors.ris_date}"
            @change="updateField('ris_date', $event.target.value)" />
          <div v-if="errors.ris_date" class="invalid-feedback">{{ errors.ris_date[0] }}</div>
        </div>

        <!-- Fund Cluster (dropdown) -->
        <div class="col-sm-6 col-lg-3">
          <label class="form-label fw-semibold">Fund Cluster</label>
          <select :value="form.fund_cluster" class="form-select" :class="{'is-invalid': errors.fund_cluster}"
            @change="updateField('fund_cluster', $event.target.value)">
            <option value="">— Select —</option>
            <option v-for="fc in fundClusters" :key="fc.id" :value="fc.name">{{ fc.name }}</option>
          </select>
          <div v-if="errors.fund_cluster" class="invalid-feedback">{{ errors.fund_cluster[0] }}</div>
        </div>

        <!-- Status — only visible on edit -->
        <div v-if="form.id" class="col-sm-6 col-lg-3">
          <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
          <select :value="form.status_id" class="form-select" :class="{'is-invalid': errors.status_id}"
            @change="updateField('status_id', $event.target.value)">
            <option value="">— Select —</option>
            <option v-for="s in statuses" :key="s.id" :value="String(s.id)">{{ s.name }}</option>
          </select>
          <div v-if="errors.status_id" class="invalid-feedback">{{ errors.status_id[0] }}</div>
        </div>

        <!-- Pending badge — shown on create -->
        <div v-else class="col-sm-6 col-lg-3 d-flex align-items-end">
          <div class="ris-auto-badge">
            <i class="ri-checkbox-circle-line me-1"></i>
            Status: <strong>Pending</strong>
          </div>
        </div>

        <!-- Purpose -->
        <div class="col-sm-12 col-lg-3">
          <label class="form-label fw-semibold">Purpose</label>
          <input type="text" :value="form.purpose" class="form-control"
            :class="{'is-invalid': errors.purpose}"
            placeholder="Purpose of requisition"
            @input="updateField('purpose', $event.target.value)" />
          <div v-if="errors.purpose" class="invalid-feedback">{{ errors.purpose[0] }}</div>
        </div>

        <!-- Division / Office -->
        <div class="col-sm-6">
          <label class="form-label fw-semibold">Division / Office</label>
          <select class="form-select" :value="form.division"
            :class="{'is-invalid': errors.division}"
            @change="onDivisionChange($event.target.value)">
            <option value="">— Select —</option>
            <option v-for="d in divisions" :key="d.id" :value="d.name">{{ d.name }}</option>
          </select>
          <div v-if="errors.division" class="invalid-feedback d-block">{{ errors.division[0] }}</div>
        </div>

        <!-- Responsibility Center -->
        <div class="col-sm-6">
          <label class="form-label fw-semibold">Responsibility Center</label>
          <select class="form-select" :value="form.responsibility_center"
            :class="{'is-invalid': errors.responsibility_center}"
            @change="updateField('responsibility_center', $event.target.value)">
            <option value="">— Select —</option>
            <option v-for="u in availableResponsibilityCenters" :key="u.id" :value="u.responsibility_center_code">
              {{ u.name }} ({{ u.responsibility_center_code }})
            </option>
          </select>
          <div v-if="errors.responsibility_center" class="invalid-feedback d-block">{{ errors.responsibility_center[0] }}</div>
        </div>
      </div>

      <!-- Signatories section -->
      <div class="ris-signatories-section mb-4">
        <div class="ris-signatories-header">
          <i class="ri-user-3-line me-1"></i> Signatories
        </div>
        <div class="row g-3 px-3 pb-3">
          <!-- Requested By -->
          <div class="col-sm-6 col-lg-3">
            <label class="form-label fw-semibold">Requested By</label>
            <template v-if="!form.id">
              <div class="ris-auto-field">
                <i class="ri-user-line me-1 text-primary"></i>
                <span>{{ currentUser?.name || '—' }}</span>
                <span class="ris-auto-tag">Auto</span>
              </div>
            </template>
            <template v-else>
              <select :value="form.requested_by_id" class="form-select"
                :class="{'is-invalid': errors.requested_by_id}"
                @change="updateField('requested_by_id', $event.target.value)">
                <option value="">— Select —</option>
                <option v-for="u in users" :key="u.id" :value="String(u.id)">{{ u.name }}</option>
              </select>
            </template>
            <div v-if="errors.requested_by_id" class="invalid-feedback d-block">{{ errors.requested_by_id[0] }}</div>
          </div>

          <!-- Approved By -->
          <div class="col-sm-6 col-lg-3">
            <label class="form-label fw-semibold">Approved By</label>
            <select :value="form.approved_by_id" class="form-select"
              :class="{'is-invalid': errors.approved_by_id}"
              @change="updateField('approved_by_id', $event.target.value)">
              <option value="">— Select —</option>
              <option v-for="u in users" :key="u.id" :value="String(u.id)">{{ u.name }}</option>
            </select>
            <div v-if="errors.approved_by_id" class="invalid-feedback">{{ errors.approved_by_id[0] }}</div>
            <div v-if="!form.id && risDefaults?.regional_director_id" class="ris-pre-hint">
              <i class="ri-arrow-up-circle-line"></i> Pre-set to Regional Director
            </div>
          </div>

          <!-- Issued By -->
          <div class="col-sm-6 col-lg-3">
            <label class="form-label fw-semibold">Issued By</label>
            <select :value="form.issued_by_id" class="form-select"
              :class="{'is-invalid': errors.issued_by_id}"
              @change="updateField('issued_by_id', $event.target.value)">
              <option value="">— Select —</option>
              <option v-for="u in users" :key="u.id" :value="String(u.id)">{{ u.name }}</option>
            </select>
            <div v-if="errors.issued_by_id" class="invalid-feedback">{{ errors.issued_by_id[0] }}</div>
            <div v-if="!form.id && risDefaults?.supply_officer_id" class="ris-pre-hint">
              <i class="ri-arrow-up-circle-line"></i> Pre-set to Supply Officer
            </div>
          </div>

          <!-- Received By -->
          <div class="col-sm-6 col-lg-3">
            <label class="form-label fw-semibold">Received By</label>
            <template v-if="!form.id">
              <div class="ris-auto-field">
                <i class="ri-user-line me-1 text-primary"></i>
                <span>{{ currentUser?.name || '—' }}</span>
                <span class="ris-auto-tag">Auto</span>
              </div>
            </template>
            <template v-else>
              <select :value="form.received_by_id" class="form-select"
                :class="{'is-invalid': errors.received_by_id}"
                @change="updateField('received_by_id', $event.target.value)">
                <option value="">— Select —</option>
                <option v-for="u in users" :key="u.id" :value="String(u.id)">{{ u.name }}</option>
              </select>
            </template>
            <div v-if="errors.received_by_id" class="invalid-feedback d-block">{{ errors.received_by_id[0] }}</div>
          </div>
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
          <button type="button" class="btn btn-sm btn-primary rounded-pill px-3" @click="openAddItemModal">
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

  <!-- Add Item modal -->
  <b-modal
    v-model="showAddItemModal"
    title="Add Item"
    size="md"
    centered
    no-close-on-backdrop
  >
    <div class="mb-3">
      <label class="form-label fw-semibold">Item <span class="text-danger">*</span></label>
      <Multiselect
        v-model="newItemForm.item_id"
        :options="itemOptions"
        :searchable="true"
        :class="{ 'is-invalid': addItemError }"
        placeholder="Select item"
      />
      <div v-if="addItemError" class="invalid-feedback d-block">{{ addItemError }}</div>
    </div>
    <div class="row g-3">
      <div class="col-sm-6">
        <label class="form-label fw-semibold">Unit of Issue</label>
        <input type="text" v-model="newItemForm.unit_of_issue" class="form-control" placeholder="pcs" />
      </div>
      <div class="col-sm-6">
        <label class="form-label fw-semibold">Qty Requested</label>
        <input type="number" min="0" step="0.01" v-model="newItemForm.quantity_requested" class="form-control" />
      </div>
      <div class="col-sm-12">
        <label class="form-label fw-semibold">Remarks</label>
        <input type="text" v-model="newItemForm.remarks" class="form-control" />
      </div>
    </div>
    <template #footer>
      <div class="d-flex gap-2 justify-content-end w-100">
        <b-button variant="light" class="px-4" @click="showAddItemModal = false">Cancel</b-button>
        <b-button variant="primary" class="px-4" @click="confirmAddItem">
          <i class="ri-add-line me-1"></i>Add Item
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
    modelValue:  { type: Boolean, default: false },
    form:        { type: Object, default: () => ({}) },
    errors:      { type: Object, default: () => ({}) },
    saving:      { type: Boolean, default: false },
    items:       { type: Array, default: () => [] },
    users:       { type: Array, default: () => [] },
    statuses:    { type: Array, default: () => [] },
    risDefaults: { type: Object, default: () => ({}) },
    fundClusters:{ type: Array, default: () => [] },
    divisions:   { type: Array, default: () => [] },
    units:       { type: Array, default: () => [] },
    currentUser: { type: Object, default: null },
  },
  emits: ['update:modelValue', 'update:form', 'submit', 'update:errors'],
  data() {
    return {
      showAddItemModal: false,
      newItemForm: { item_id: '', unit_of_issue: '', quantity_requested: 1, remarks: '' },
      addItemError: '',
    };
  },
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
    selectedDivisionId() {
      const division = this.divisions.find((d) => d.name === this.form.division);
      return division ? Number(division.id) : null;
    },
    availableResponsibilityCenters() {
      const divisionId = this.selectedDivisionId;
      return this.units.filter((u) => (
        u.responsibility_center_code
        && (!divisionId || Number(u.division_id) === divisionId)
      ));
    },
  },
  methods: {
    onDivisionChange(value) {
      const divisionId = value
        ? Number((this.divisions.find((d) => d.name === value) || {}).id)
        : null;
      const stillValid = this.units.some((u) => (
        u.responsibility_center_code === this.form.responsibility_center
        && (!divisionId || Number(u.division_id) === divisionId)
      ));
      const next = { ...this.form, division: value };
      if (!stillValid) {
        next.responsibility_center = '';
      }
      this.$emit('update:form', next);
      if (this.errors?.division || (!stillValid && this.errors?.responsibility_center)) {
        const nextErrors = { ...this.errors };
        delete nextErrors.division;
        if (!stillValid) delete nextErrors.responsibility_center;
        this.$emit('update:errors', nextErrors);
      }
    },
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
    openAddItemModal() {
      this.newItemForm = { item_id: '', unit_of_issue: '', quantity_requested: 1, remarks: '' };
      this.addItemError = '';
      this.showAddItemModal = true;
    },
    confirmAddItem() {
      if (!this.newItemForm.item_id) {
        this.addItemError = 'Please select an item.';
        return;
      }
      const items = [...(this.form.items || []), {
        item_id: this.newItemForm.item_id,
        unit_of_issue: this.newItemForm.unit_of_issue,
        quantity_requested: this.newItemForm.quantity_requested || 0,
        quantity_issued: 0,
        remarks: this.newItemForm.remarks,
      }];
      this.$emit('update:form', { ...this.form, items });
      if (this.errors?.items_empty) {
        const next = { ...this.errors };
        delete next.items_empty;
        this.$emit('update:errors', next);
      }
      this.showAddItemModal = false;
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

.ris-auto-badge {
  height: 38px; border-radius: 10px; padding: 0 .85rem;
  background: rgba(75,91,147,.08); border: 1px solid rgba(75,91,147,.2);
  color: #4b5b93; font-size: .84rem; font-weight: 600;
  display: inline-flex; align-items: center; gap: .25rem; width: 100%;
}

.ris-signatories-section {
  border: 1px solid #dce4f2; border-radius: 14px; overflow: hidden;
  background: #f8fbff;
}
.ris-signatories-header {
  background: linear-gradient(180deg, #f0f4ff, #e8eeff);
  border-bottom: 1px solid #dce4f2; padding: .55rem .85rem;
  font-size: .78rem; font-weight: 700; text-transform: uppercase;
  letter-spacing: .06em; color: #4b5b93;
}
.ris-auto-field {
  height: 38px; border-radius: 10px; padding: 0 .85rem;
  background: #fff; border: 1px dashed #a5b4d6;
  font-size: .84rem; color: #1e2d6b; font-weight: 600;
  display: flex; align-items: center; gap: .35rem; width: 100%;
}
.ris-auto-tag {
  margin-left: auto; font-size: .68rem; font-weight: 700;
  padding: .15rem .45rem; border-radius: 6px;
  background: rgba(75,91,147,.1); color: #4b5b93; letter-spacing: .04em;
}
.ris-pre-hint {
  font-size: .72rem; color: #64748b; margin-top: .3rem;
  display: flex; align-items: center; gap: .25rem;
}
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
