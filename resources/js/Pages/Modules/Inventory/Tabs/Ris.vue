<template>
  <div class="inv-module-card">
    <!-- Header -->
    <div class="inv-module-header">
      <div class="inv-module-header-left">
        <div class="inv-module-header-icon">
          <i class="ri-file-list-3-line"></i>
        </div>
        <div>
          <h5 class="inv-module-title">Requisition and Issue Slips</h5>
          <p class="inv-module-subtitle">{{ meta?.total ?? normalizedRows.length }} slips · track item requests and issuances</p>
        </div>
      </div>
      <div class="inv-module-header-actions">
        <div class="inv-toolbar">
          <div class="inv-search-wrap">
            <i class="ri-search-line inv-search-icon"></i>
            <input v-model="keyword" type="text" placeholder="Search RIS…" class="inv-search-input" />
            <button v-if="keyword" class="inv-search-clear" @click="keyword = ''"><i class="ri-close-line"></i></button>
          </div>
          <button class="inv-icon-btn" title="Refresh" v-b-tooltip.hover @click="$emit('refresh')">
            <i class="ri-refresh-line"></i>
          </button>
          <button class="inv-create-btn" @click="$emit('create')">
            <i class="ri-add-line"></i> Create RIS
          </button>
        </div>
      </div>
    </div>

    <!-- RIS Modal -->
    <RisModal
      v-model="showModal"
      :form="form"
      :errors="errors"
      :saving="saving"
      :items="items"
      :users="users"
      :statuses="statuses"
      @update:form="form = $event"
      @update:errors="errors = $event"
      @submit="saveRis"
    />

    <!-- View Modal -->
    <b-modal v-model="showViewModal" title="" size="lg" centered scrollable hide-footer>
      <template #header>
        <div class="d-flex align-items-center gap-3 px-2 py-1 w-100">
          <span class="avatar-title bg-primary-subtle rounded p-2" style="width:38px;height:38px">
            <i class="ri-file-list-3-line text-primary"></i>
          </span>
          <div>
            <h6 class="mb-0 fw-bold">{{ viewRecord?.ris_no }}</h6>
            <small class="text-muted">{{ formatDate(viewRecord?.ris_date) }}</small>
          </div>
          <span class="ms-auto badge" :class="statusClass(viewRecord?.status)">{{ viewRecord?.status }}</span>
        </div>
      </template>
      <div v-if="viewRecord">
        <div class="row g-2 mb-3 fs-13">
          <div class="col-sm-6"><span class="text-muted">Division:</span> <strong>{{ viewRecord.division || '—' }}</strong></div>
          <div class="col-sm-6"><span class="text-muted">Resp. Center:</span> <strong>{{ viewRecord.responsibility_center || '—' }}</strong></div>
          <div class="col-sm-6"><span class="text-muted">Fund Cluster:</span> <strong>{{ viewRecord.fund_cluster || '—' }}</strong></div>
          <div class="col-sm-6"><span class="text-muted">Requested by:</span> <strong>{{ viewRecord.requested_by || '—' }}</strong></div>
          <div class="col-12"><span class="text-muted">Purpose:</span> {{ viewRecord.purpose || '—' }}</div>
        </div>
        <table class="table table-sm table-bordered align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>Item</th>
              <th class="text-center" style="width:80px">Unit</th>
              <th class="text-center" style="width:100px">Requested</th>
              <th class="text-center" style="width:100px">Issued</th>
              <th>Remarks</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="!viewRecord.items?.length">
              <td colspan="5" class="text-center text-muted py-3">No items.</td>
            </tr>
            <tr v-else v-for="line in viewRecord.items" :key="line.id">
              <td>
                <div class="fw-semibold">{{ line.item_name }}</div>
                <small class="text-muted">{{ line.item_code }}</small>
              </td>
              <td class="text-center">{{ line.unit_of_issue || '—' }}</td>
              <td class="text-center">{{ line.quantity_requested }}</td>
              <td class="text-center">{{ line.quantity_issued }}</td>
              <td>{{ line.remarks || '—' }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </b-modal>

    <!-- Table -->
    <div class="inv-table-shell">
      <table class="inv-table">
        <thead>
          <tr>
            <th style="width:48px" class="text-center">#</th>
            <th style="width:160px">RIS No.</th>
            <th>Division / Purpose</th>
            <th style="width:110px" class="text-center">Date</th>
            <th style="width:80px" class="text-center">Items</th>
            <th style="width:100px" class="text-center">Status</th>
            <th style="width:120px" class="text-center">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading" class="inv-table-empty">
            <td colspan="7">
              <div class="inv-loading-state"><div class="inv-spinner"></div><span>Loading RIS records…</span></div>
            </td>
          </tr>
          <tr v-else-if="normalizedRows.length === 0" class="inv-table-empty">
            <td colspan="7">
              <div class="inv-empty-state">
                <i class="ri-file-list-3-line"></i>
                <p>No RIS records yet.</p>
                <button class="inv-create-btn" @click="$emit('create')"><i class="ri-add-line"></i> Create RIS</button>
              </div>
            </td>
          </tr>
          <tr v-else v-for="(row, index) in normalizedRows" :key="row.id" class="inv-table-row">
            <td class="text-center inv-row-num">{{ displayRowNumber(index) }}</td>
            <td><span class="inv-code-chip">{{ row.ris_no }}</span></td>
            <td>
              <div class="fw-semibold">{{ row.division || '—' }}</div>
              <small class="text-muted">{{ row.purpose ? row.purpose.substring(0, 60) + (row.purpose.length > 60 ? '…' : '') : '' }}</small>
            </td>
            <td class="text-center inv-date-cell">{{ formatDate(row.ris_date) }}</td>
            <td class="text-center"><span class="inv-count-badge">{{ row.items_count ?? 0 }}</span></td>
            <td class="text-center">
              <span class="badge rounded-pill" :class="statusClass(row.status)">{{ row.status }}</span>
            </td>
            <td class="text-center">
              <div class="inv-row-actions">
                <button class="inv-action-btn view" title="View" v-b-tooltip.hover @click="openView(row)"><i class="ri-eye-line"></i></button>
                <button class="inv-action-btn edit" title="Edit" v-b-tooltip.hover @click="openEdit(row)"><i class="ri-pencil-line"></i></button>
                <button class="inv-action-btn del" title="Delete" v-b-tooltip.hover @click="deleteRis(row)"><i class="ri-delete-bin-line"></i></button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
      <div v-if="meta && meta.total" class="inv-pagination-bar">
        <Pagination :links="links" :pagination="meta" :lists="normalizedRows.length" @fetch="(url) => $emit('fetch', url)" />
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import Pagination from '@/Shared/Components/Pagination.vue';
import RisModal from '@/Pages/Modules/Inventory/Modals/Ris.vue';

export default {
  name: 'RisLedger',
  components: { Pagination, RisModal },
  props: {
    rows:     { type: [Array, Object], default: () => [] },
    loading:  { type: Boolean, default: false },
    meta:     { type: Object, default: null },
    links:    { type: Array, default: null },
    items:    { type: Array, default: () => [] },
    users:    { type: Array, default: () => [] },
    statuses: { type: Array, default: () => [] },
  },
  emits: ['create', 'fetch', 'refresh'],
  data() {
    return {
      keyword: '',
      showModal: false,
      showViewModal: false,
      viewRecord: null,
      saving: false,
      errors: {},
      form: this.emptyForm(),
    };
  },
  computed: {
    normalizedRows() {
      if (Array.isArray(this.rows)) return this.rows;
      if (Array.isArray(this.rows?.data)) return this.rows.data;
      return [];
    },
  },
  watch: {
    showModal(val) {
      if (!val) this.errors = {};
    },
  },
  methods: {
    emptyForm() {
      return {
        id: null, ris_no: '', fund_cluster: '', division: '',
        responsibility_center: '', purpose: '',
        ris_date: new Date().toISOString().slice(0, 10),
        requested_by_id: '', approved_by_id: '', issued_by_id: '', received_by_id: '',
        status_id: '',
        items: [],
      };
    },
    openCreate() {
      this.form = this.emptyForm();
      this.errors = {};
      this.showModal = true;
    },
    openEdit(row) {
      axios.get(`/inventory-ris/${row.id}`).then((res) => {
        const d = res.data.data ?? res.data;
        this.form = {
          id: d.id,
          ris_no: d.ris_no || '',
          fund_cluster: d.fund_cluster || '',
          division: d.division || '',
          responsibility_center: d.responsibility_center || '',
          purpose: d.purpose || '',
          ris_date: d.ris_date || '',
          requested_by_id: String(d.requested_by_id || ''),
          approved_by_id: String(d.approved_by_id || ''),
          issued_by_id: String(d.issued_by_id || ''),
          received_by_id: String(d.received_by_id || ''),
          status_id: String(d.status_id || ''),
          items: (d.items || []).map((line) => ({
            id: line.id,
            item_id: String(line.item_id),
            unit_of_issue: line.unit_of_issue || '',
            quantity_requested: line.quantity_requested,
            quantity_issued: line.quantity_issued,
            remarks: line.remarks || '',
          })),
        };
        this.errors = {};
        this.showModal = true;
      });
    },
    async openView(row) {
      const res = await axios.get(`/inventory-ris/${row.id}`);
      this.viewRecord = res.data.data ?? res.data;
      this.showViewModal = true;
    },
    async saveRis() {
      // Client-side validation first
      const clientErrors = {};
      if (!this.form.ris_date) clientErrors.ris_date = ['RIS date is required.'];
      if (!this.form.status_id) clientErrors.status_id = ['Please select a status.'];
      if (!this.form.items?.length) {
        clientErrors.items_empty = ['Please add at least one item to the RIS.'];
      } else {
        this.form.items.forEach((line, i) => {
          if (!line.item_id) clientErrors[`items.${i}.item_id`] = ['Please select an item.'];
          if (!line.quantity_requested || Number(line.quantity_requested) <= 0)
            clientErrors[`items.${i}.quantity_requested`] = ['Quantity must be greater than 0.'];
        });
      }
      if (Object.keys(clientErrors).length) {
        this.errors = clientErrors;
        return;
      }

      this.saving = true;
      this.errors = {};
      try {
        if (this.form.id) {
          await axios.put(`/inventory-ris/${this.form.id}`, this.form);
        } else {
          await axios.post('/inventory-ris', this.form);
        }
        this.showModal = false;
        this.$emit('refresh');
      } catch (err) {
        if (err?.response?.status === 422) {
          this.errors = err.response.data.errors || {};
        } else {
          this.errors = { ris_date: ['An unexpected error occurred. Please try again.'] };
        }
      } finally {
        this.saving = false;
      }
    },
    async deleteRis(row) {
      if (!confirm(`Delete RIS "${row.ris_no}"?`)) return;
      await axios.delete(`/inventory-ris/${row.id}`);
      this.$emit('refresh');
    },
    displayRowNumber(idx) { return Number(this.meta?.from || 1) + idx; },
    formatDate(v) {
      if (!v) return '—';
      return new Intl.DateTimeFormat('en-US', { year: 'numeric', month: 'short', day: 'numeric' })
        .format(new Date(String(v).replace(' ', 'T')));
    },
    statusClass(status) {
      const map = {
        Pending: 'bg-warning-subtle text-warning',
        Approved: 'bg-success-subtle text-success',
        Completed: 'bg-primary-subtle text-primary',
        Cancelled: 'bg-danger-subtle text-danger',
        Disapproved: 'bg-secondary-subtle text-secondary',
      };
      return map[status] || 'bg-secondary-subtle text-secondary';
    },
  },
};
</script>

<style scoped>
.inv-module-card {
  --inv-brand: #4b5b93; --inv-brand-soft: #edf1fb; --inv-border: #dce4f2;
  --inv-surface: #ffffff; --inv-muted: #64748b; --inv-ink: #0f172a;
  border: 1px solid var(--inv-border); border-radius: 20px;
  background: var(--inv-surface); overflow: hidden;
}
.inv-module-header {
  display: flex; align-items: center; justify-content: space-between;
  gap: 1rem; padding: .85rem 1rem;
  background: linear-gradient(180deg, #f8fbff, #f0f5ff);
  border-bottom: 1px solid var(--inv-border); flex-wrap: wrap;
}
.inv-module-header-left { display: flex; align-items: center; gap: .75rem; }
.inv-module-header-icon {
  width: 40px; height: 40px; border-radius: 13px;
  background: linear-gradient(135deg, #4b5b93, #38467a);
  color: #fff; display: inline-flex; align-items: center; justify-content: center;
  font-size: 1.1rem; flex-shrink: 0;
}
.inv-module-title { margin: 0; font-size: .9rem; font-weight: 800; color: var(--inv-ink); }
.inv-module-subtitle { margin: 0; font-size: .72rem; color: var(--inv-muted); font-weight: 500; }
.inv-toolbar { display: flex; align-items: center; gap: .45rem; flex-wrap: wrap; }
.inv-search-wrap { position: relative; display: flex; align-items: center; }
.inv-search-icon { position: absolute; left: .65rem; color: var(--inv-muted); font-size: .95rem; pointer-events: none; }
.inv-search-input {
  height: 38px; padding: 0 2rem 0 2.1rem; border: 1px solid var(--inv-border);
  border-radius: 10px; background: #fff; color: var(--inv-ink); font-size: .84rem; width: 220px;
  outline: none; transition: border-color .15s;
}
.inv-search-input:focus { border-color: var(--inv-brand); }
.inv-search-clear { position: absolute; right: .5rem; border: 0; background: transparent; color: #94a3b8; cursor: pointer; padding: 0; }
.inv-icon-btn {
  width: 38px; height: 38px; border: 1px solid var(--inv-border); border-radius: 10px;
  background: #fff; color: var(--inv-muted); display: inline-flex; align-items: center;
  justify-content: center; font-size: 1rem; cursor: pointer; transition: background .15s, color .15s;
}
.inv-icon-btn:hover { background: var(--inv-brand-soft); color: var(--inv-brand); }
.inv-create-btn {
  display: inline-flex; align-items: center; gap: .3rem;
  height: 38px; padding: 0 1rem; border: 0; border-radius: 10px;
  background: linear-gradient(135deg, #4b5b93, #38467a);
  color: #fff; font-size: .84rem; font-weight: 700; cursor: pointer; white-space: nowrap; transition: opacity .15s;
}
.inv-create-btn:hover { opacity: .9; }
.inv-table-shell { overflow: auto; max-height: calc(100vh - 310px); min-height: 200px; }
.inv-table { width: 100%; border-collapse: separate; border-spacing: 0; }
.inv-table thead th {
  position: sticky; top: 0; z-index: 2;
  background: linear-gradient(180deg, #f3f7ff, #eaf0fd);
  padding: .65rem .85rem; font-size: .72rem; font-weight: 800;
  text-transform: uppercase; letter-spacing: .06em; color: var(--inv-muted);
  border-bottom: 1px solid var(--inv-border); white-space: nowrap;
}
.inv-table-row td {
  padding: .72rem .85rem; border-bottom: 1px solid #f1f5ff;
  font-size: .85rem; color: var(--inv-ink); vertical-align: middle;
  background: var(--inv-surface); transition: background .12s;
}
.inv-table-row:hover td { background: #f8fbff; }
.inv-table-empty td { padding: 0; border: 0; }
.inv-row-num { color: var(--inv-muted); font-size: .78rem; font-weight: 700; }
.inv-code-chip {
  display: inline-block; padding: .22rem .6rem; border-radius: 7px;
  background: rgba(75,91,147,.08); color: var(--inv-brand);
  font-size: .78rem; font-weight: 800; font-family: ui-monospace, monospace;
  border: 1px solid rgba(75,91,147,.15);
}
.inv-count-badge {
  display: inline-block; min-width: 40px; padding: .2rem .5rem; border-radius: 20px;
  background: rgba(75,91,147,.1); color: var(--inv-brand); font-size: .78rem; font-weight: 800; text-align: center;
}
.inv-date-cell { color: var(--inv-muted); font-size: .8rem; }
.inv-row-actions { display: inline-flex; gap: .3rem; }
.inv-action-btn {
  width: 30px; height: 30px; border: 1px solid var(--inv-border); border-radius: 8px; background: #fff;
  display: inline-flex; align-items: center; justify-content: center; font-size: .88rem; cursor: pointer; transition: all .15s;
}
.inv-action-btn.view:hover { background: #e0f2fe; border-color: #38bdf8; color: #0284c7; }
.inv-action-btn.edit:hover { background: #fef3c7; border-color: #f59e0b; color: #d97706; }
.inv-action-btn.del:hover  { background: #fee2e2; border-color: #f87171; color: #dc2626; }
.inv-loading-state, .inv-empty-state {
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  gap: .75rem; padding: 3.5rem 1rem; color: var(--inv-muted);
}
.inv-spinner {
  width: 36px; height: 36px; border: 3px solid #e2e8f0;
  border-top-color: var(--inv-brand); border-radius: 50%; animation: inv-spin .7s linear infinite;
}
@keyframes inv-spin { to { transform: rotate(360deg); } }
.inv-empty-state i { font-size: 2.5rem; opacity: .35; }
.inv-empty-state p { margin: 0; font-size: .9rem; font-weight: 500; }
.inv-pagination-bar { padding: .6rem 1rem; border-top: 1px solid var(--inv-border); background: #fafbff; }
.fs-13 { font-size: 13px; }
</style>
