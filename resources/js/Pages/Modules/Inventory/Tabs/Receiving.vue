<template>
  <div class="card bg-light-subtle shadow-none border ledger-card">
    <div class="card-header bg-light-subtle">
      <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
        <div class="d-flex">
          <div class="flex-shrink-0 me-3">
            <div style="height: 2.5rem; width: 2.5rem">
              <span class="avatar-title bg-success-subtle rounded p-2 mt-n1">
                <i class="ri-inbox-archive-line text-success fs-24"></i>
              </span>
            </div>
          </div>
          <div class="flex-grow-1">
            <h5 class="mb-0 fs-14">
              <span class="text-body">Purchase Order Receiving</span>
            </h5>
            <p class="text-muted text-truncate-two-lines fs-12 mb-0">
              Purchase orders from procurement with received item details available for inventory review.
            </p>
          </div>
        </div>
      </div>
    </div>

    <div class="card-body bg-white rounded-bottom receiving-card-body">
      <b-row class="mb-3">
        <b-col lg>
          <div class="ledger-toolbar-wrap">
            <div class="ledger-toolbar">
              <div class="input-group ledger-search-group">
                <span class="input-group-text">
                  <i class="ri-search-line search-icon"></i>
                </span>
                <input
                  v-model="filters.keyword"
                  type="text"
                  placeholder="Search Receiving Ledger"
                  class="form-control"
                />
              </div>
            </div>

            <select v-model="filters.sort" class="form-select ledger-sort-select" aria-label="Sort receiving records">
              <option value="latest">Latest Received</option>
              <option value="oldest">Oldest Received</option>
              <option value="item_asc">Item A-Z</option>
              <option value="status_asc">Status A-Z</option>
            </select>

            <button
              type="button"
              class="btn ledger-refresh-btn"
              title="Refresh"
              v-b-tooltip.hover
              @click="handleRefresh"
            >
              <i class="bx bx-refresh search-icon"></i>
            </button>
          </div>
        </b-col>
      </b-row>

      <div class="table-responsive table-card ledger-table-wrap">
        <table class="table align-middle table-hover mb-0 ledger-table">
          <thead class="table-light thead-fixed">
            <tr>
              <th style="width: 14%">PO No.</th>
              <th>Procurement</th>
              <th style="width: 18%">Supplier</th>
              <th>Status</th>
              <th style="width: 16%" class="text-center">Items</th>
              <th class="text-center" style="width: 120px">Action</th>
            </tr>
          </thead>
          <tbody class="table-group-divider">
            <tr v-if="loading">
              <td colspan="6" class="text-center text-muted py-4">Loading purchase orders...</td>
            </tr>
            <tr v-else-if="sortedRows.length === 0">
              <td colspan="6" class="text-center text-muted py-4">No purchase orders found for receiving.</td>
            </tr>
            <tr v-else v-for="item in sortedRows" :key="item.id">
              <td>
                <div class="fw-semibold text-primary">{{ item.code || '-' }}</div>
                <div class="small text-muted">{{ item.date_of_delivery || item.po_date || '-' }}</div>
              </td>
              <td>
                <div class="fw-semibold">{{ item.procurement_title || '-' }}</div>
                <div class="small text-muted">{{ item.procurement_code || 'No PR code' }}</div>
              </td>
              <td>{{ item.supplier_name || '-' }}</td>
              <td>
                <span class="badge receiving-status-chip">
                  <i class="ri-checkbox-circle-fill me-1"></i>{{ item.status?.name || item.status || '-' }}
                </span>
              </td>
              <td class="text-center">
                <div class="fw-semibold">
                  {{ item.delivered_items_count || 0 }}/{{ item.total_items_count || 0 }} received
                </div>
                <div class="small text-muted">
                  {{ formatQuantity(totalReceivedQuantity(item)) }} total qty
                </div>
              </td>
              <td class="text-center">
                <div class="d-inline-flex gap-1">
                  <button
                    class="btn btn-sm btn-outline-success receiving-action-btn"
                    type="button"
                    title="View items"
                    @click="$emit('view', item)"
                  >
                    <i class="ri-eye-line"></i>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <Pagination
        v-if="meta && meta.total"
        :links="links"
        :pagination="meta"
        :lists="sortedRows.length"
        @fetch="(pageUrl) => $emit('fetch', pageUrl)"
      />
    </div>
  </div>
</template>

<script>
import Pagination from '@/Shared/Components/Pagination.vue';

export default {
  name: 'ReceivingLedger',
  components: { Pagination },
  props: {
    rows: { type: Array, default: () => [] },
    loading: { type: Boolean, default: false },
    meta: { type: Object, default: null },
    links: { type: Array, default: null },
  },
  emits: ['fetch', 'refresh', 'view'],
  data() {
    return {
      filters: {
        keyword: '',
        status: '',
        sort: 'latest',
      },
    };
  },
  computed: {
    filteredRows() {
      const keyword = (this.filters.keyword || '').toLowerCase();

      return this.rows.filter((item) => {
        const searchable = [
          item.code,
          item.procurement_code,
          item.procurement_title,
          item.supplier_name,
          item.date_of_delivery,
          item.status?.name || item.status,
        ]
          .filter(Boolean)
          .join(' ')
          .toLowerCase();

        return searchable.includes(keyword);
      });
    },
    sortedRows() {
      const rows = [...this.filteredRows];
      const normalizeText = (value) => String(value || '').toLowerCase();
      const normalizeDate = (value) => {
        if (!value) return 0;

        const timestamp = new Date(String(value).replace(' ', 'T')).getTime();
        return Number.isNaN(timestamp) ? 0 : timestamp;
      };

      const sorters = {
        oldest: (left, right) => normalizeDate(left.received_at) - normalizeDate(right.received_at),
        item_asc: (left, right) => normalizeText(left.procurement_title).localeCompare(normalizeText(right.procurement_title)),
        status_asc: (left, right) => normalizeText(left.status?.name || left.status).localeCompare(normalizeText(right.status?.name || right.status)),
      };

      return rows.sort(sorters[this.filters.sort] || ((left, right) => normalizeDate(right.date_of_delivery_raw || right.date_of_delivery || right.po_date) - normalizeDate(left.date_of_delivery_raw || left.date_of_delivery || left.po_date)));
    },
  },
  methods: {
    handleRefresh() {
      this.filters.keyword = '';
      this.$emit('refresh');
    },
    formatQuantity(value) {
      const number = Number(value ?? 0);

      if (!Number.isFinite(number)) {
        return '-';
      }

      return Number.isInteger(number)
        ? number.toLocaleString('en-PH')
        : number.toLocaleString('en-PH', {
          minimumFractionDigits: 0,
          maximumFractionDigits: 4,
        });
    },
    totalReceivedQuantity(po) {
      return (po?.delivery_monitoring_items || []).reduce(
        (total, item) => total + Number(item?.delivered_quantity || 0),
        0,
      );
    },
  },
};
</script>

<style scoped>
.ledger-card .card-header {
  border-bottom: 1px solid rgba(15, 23, 42, 0.08);
}

.receiving-card-body {
  padding: 0.65rem;
}

.ledger-table-wrap {
  max-height: calc(100vh - 278px);
  overflow: auto;
}

.ledger-toolbar-wrap {
  display: flex;
  align-items: stretch;
  flex-wrap: nowrap;
  gap: 0;
  width: 100%;
}

.ledger-toolbar {
  flex: 1 1 auto;
  min-width: 0;
}

.ledger-search-group {
  height: 100%;
}

.ledger-sort-select {
  flex: 0 0 190px;
  min-width: 190px;
  border-left: 0;
  border-radius: 0;
}

.ledger-refresh-btn {
  flex: 0 0 52px;
  cursor: pointer;
  min-width: 52px;
  border: 1px solid #d7dfef;
  border-left: 0;
  border-radius: 0;
  background: #f8fbff;
  color: #334155;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.ledger-refresh-btn:hover,
.ledger-refresh-btn:focus {
  background: #eef4ff;
  color: #1d4ed8;
}

.receiving-create-btn {
  flex: 0 0 176px;
  min-width: 176px;
  border-radius: 0 4px 4px 0;
  background: #3f4d85;
  border: 1px solid #3f4d85;
  color: #fff;
  font-weight: 600;
  white-space: nowrap;
}

.receiving-create-btn:hover,
.receiving-create-btn:focus {
  background: #364273;
  border-color: #364273;
  color: #fff;
}

.ledger-table thead th {
  font-weight: 700;
  font-size: 12px;
}

.ledger-table tbody td {
  vertical-align: middle;
}

.receiving-status-chip {
  background: #d1e7dd;
  color: #0f5132;
  border: 1px solid #badbcc;
  border-radius: 999px;
  padding: 5px 9px;
  font-weight: 700;
}

.receiving-action-btn {
  border-width: 1px;
}

:global([data-bs-theme="dark"] .ledger-card),
:global([data-bs-theme="dark"] .ledger-card .card-header),
:global([data-bs-theme="dark"] .receiving-card-body),
:global([data-bs-theme="dark"] .ledger-table-wrap){
  border-color: #2e3a59 !important;
  background-color: #111827 !important;
  color: #e5e7eb !important;
}

:global([data-bs-theme="dark"] .ledger-table-wrap .table){
  --vz-table-bg: #111827;
  --vz-table-color: #e5e7eb;
  --vz-table-hover-bg: #182035;
  --vz-table-border-color: #2e3a59;
  background-color: #111827 !important;
  color: #e5e7eb !important;
}

:global([data-bs-theme="dark"] .ledger-table-wrap .table-light th){
  background-color: #182035 !important;
  color: #dbeafe !important;
}

:global([data-bs-theme="dark"] .ledger-toolbar-wrap .input-group-text),
:global([data-bs-theme="dark"] .ledger-toolbar-wrap .form-control),
:global([data-bs-theme="dark"] .ledger-sort-select),
:global([data-bs-theme="dark"] .ledger-refresh-btn){
  border-color: #2e3a59 !important;
  background-color: #182035 !important;
  color: #e5e7eb !important;
}

:global([data-bs-theme="dark"] .ledger-toolbar-wrap .form-control::placeholder){
  color: #8ea0b8 !important;
}

@media (max-width: 768px) {
  .ledger-toolbar-wrap {
    overflow-x: auto;
  }
}
</style>
