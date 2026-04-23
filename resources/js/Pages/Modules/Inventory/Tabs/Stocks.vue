<template>
  <div class="card bg-light-subtle shadow-none border ledger-card">
    <div class="card-header bg-light-subtle">
      <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
        <div class="d-flex">
          <div class="flex-shrink-0 me-3">
            <div style="height: 2.5rem; width: 2.5rem">
              <span class="avatar-title bg-primary-subtle rounded p-2 mt-n1">
                <i class="ri-shopping-bag-3-line text-primary fs-24"></i>
              </span>
            </div>
          </div>
          <div class="flex-grow-1">
            <h5 class="mb-0 fs-14">
              <span class="text-body">Inventory Stocks</span>
            </h5>
            <p class="text-muted text-truncate-two-lines fs-12 mb-0">
              Manage stock headers and keep item counts aligned with the latest inventory records.
            </p>
          </div>
        </div>
      </div>
    </div>

    <div class="card-body bg-white rounded-bottom stock-card-body">
      <div class="stock-toolbar-row">
        <div class="input-group stock-toolbar-group">
          <span class="input-group-text">
            <i class="ri-search-line search-icon"></i>
          </span>
          <input
            :value="keyword"
            type="text"
            placeholder="Search Inventory Stocks"
            class="form-control stock-search-input"
            @input="$emit('update:keyword', $event.target.value)"
          />
          <span
            class="input-group-text stock-refresh-trigger"
            title="Refresh"
            v-b-tooltip.hover
            @click="handleRefresh"
          >
            <i class="bx bx-refresh search-icon"></i>
          </span>
          <select v-model="sort" class="form-select stock-sort-select" aria-label="Sort inventory stocks">
            <option value="latest">Latest Entry</option>
            <option value="oldest">Oldest Entry</option>
            <option value="name_asc">Name A-Z</option>
            <option value="code_asc">Code A-Z</option>
            <option value="quantity_desc">Quantity High-Low</option>
          </select>
          <button
            type="button"
            class="btn btn-primary stock-create-btn"
            @click="$emit('create')"
          >
            <i class="ri-add-circle-fill align-bottom me-1"></i>
            Create
          </button>
        </div>
      </div>

      <div class="table-responsive table-card ledger-table-wrap">
        <table class="table align-middle table-hover mb-0">
          <thead class="table-light thead-fixed">
            <tr class="fs-12 fw-semibold">
              <th style="width: 4%" class="text-center">#</th>
              <th style="width: 14%">Code</th>
              <th style="width: 26%">Stock Name</th>
              <th style="width: 14%" class="text-end">Items</th>
              <th style="width: 16%" class="text-end">Total Quantity</th>
              <th style="width: 16%">Entry Date</th>
              <th style="width: 10%" class="text-center">Actions</th>
            </tr>
          </thead>
          <tbody class="table-group-divider">
            <tr v-if="loading">
              <td colspan="7" class="text-center text-muted py-5">Loading stock records...</td>
            </tr>
            <tr v-else-if="sortedRows.length === 0">
              <td colspan="7" class="text-center text-muted py-5">
                {{ keyword ? 'No stocks matched your search.' : 'No stock records found.' }}
              </td>
            </tr>
            <tr v-else v-for="(stock, index) in sortedRows" :key="stock.id">
              <td class="text-center fw-semibold">{{ displayRowNumber(index) }}</td>
              <td>
                <div class="d-flex align-items-center">
                  <div>
                    <h6 class="mb-0 fs-14 fw-semibold text-primary">
                      {{ stock.code || '-' }}
                    </h6>
                  </div>
                </div>
              </td>
              <td>{{ stock.name || '-' }}</td>
              <td class="text-end">{{ formatNumber(stock.item_count) }}</td>
              <td class="text-end">{{ formatNumber(stock.total_quantity) }}</td>
              <td>{{ formatEntryDate(stock.entry_date) }}</td>
              <td class="text-center">
                <div class="d-flex justify-content-center gap-1">
                  <button
                    class="btn btn-sm btn-info btn-icon"
                    type="button"
                    title="View"
                    style="border-radius: 8px"
                    v-b-tooltip.hover
                    @click="$emit('view', stock)"
                  >
                    <i class="ri-eye-line"></i>
                  </button>
                  <button
                    class="btn btn-sm btn-primary btn-icon"
                    type="button"
                    title="Edit"
                    style="border-radius: 8px"
                    v-b-tooltip.hover
                    @click="$emit('edit', stock)"
                  >
                    <i class="ri-pencil-line"></i>
                  </button>
                  <button
                    class="btn btn-sm btn-danger btn-icon"
                    type="button"
                    title="Delete"
                    style="border-radius: 8px"
                    v-b-tooltip.hover
                    @click="$emit('delete', stock)"
                  >
                    <i class="ri-delete-bin-line"></i>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>

        <div class="card-footer">
          <Pagination
            class="ms-2 me-2 mt-n1"
            v-if="meta && meta.total"
            :links="links"
            :pagination="meta"
            :lists="sortedRows.length"
            @fetch="(pageUrl) => $emit('fetch', pageUrl)"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import Pagination from '@/Shared/Components/Pagination.vue';

export default {
  name: 'StocksLedger',
  components: { Pagination },
  props: {
    rows: { type: [Array, Object], default: () => [] },
    loading: { type: Boolean, default: false },
    meta: { type: Object, default: null },
    links: { type: Array, default: null },
    keyword: { type: String, default: '' },
  },
  emits: ['create', 'fetch', 'refresh', 'update:keyword', 'view', 'edit', 'delete'],
  data() {
    return {
      sort: 'latest',
    };
  },
  computed: {
    sortedRows() {
      const rows = [...this.normalizedRows];
      const normalizeText = (value) => String(value || '').toLowerCase();
      const normalizeNumber = (value) => Number(value || 0);
      const normalizeDate = (value) => {
        if (!value) return 0;

        const timestamp = new Date(String(value).replace(' ', 'T')).getTime();
        return Number.isNaN(timestamp) ? 0 : timestamp;
      };

      const sorters = {
        oldest: (left, right) => normalizeDate(left.entry_date) - normalizeDate(right.entry_date),
        name_asc: (left, right) => normalizeText(left.name).localeCompare(normalizeText(right.name)),
        code_asc: (left, right) => normalizeText(left.code).localeCompare(normalizeText(right.code)),
        quantity_desc: (left, right) => normalizeNumber(right.total_quantity) - normalizeNumber(left.total_quantity),
      };

      return rows.sort(sorters[this.sort] || ((left, right) => normalizeDate(right.entry_date) - normalizeDate(left.entry_date)));
    },
    normalizedRows() {
      if (Array.isArray(this.rows)) {
        return this.rows;
      }

      if (Array.isArray(this.rows?.data)) {
        return this.rows.data;
      }

      return [];
    },
  },
  methods: {
    handleRefresh() {
      this.$emit('refresh');
    },
    displayRowNumber(index) {
      return Number(this.meta?.from || 1) + index;
    },
    formatNumber(value) {
      return new Intl.NumberFormat().format(Number(value || 0));
    },
    formatEntryDate(value) {
      if (!value) return '-';

      return new Intl.DateTimeFormat('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
      }).format(new Date(String(value).replace(' ', 'T')));
    },
  },
};
</script>

<style scoped>
.stock-card-body {
  padding: 0.65rem 0.65rem 0;
}

.stock-toolbar-row {
  margin-bottom: 0.55rem;
}

.stock-toolbar-group {
  width: 100%;
  flex-wrap: nowrap;
  align-items: stretch;
}

.stock-toolbar-group .input-group-text,
.stock-toolbar-group .form-control,
.stock-toolbar-group .btn {
  min-height: 44px;
}

.stock-search-input {
  min-width: 0;
}

.stock-refresh-trigger {
  cursor: pointer;
}

.stock-sort-select {
  flex: 0 0 190px;
  min-width: 190px;
  border-left: 0;
  border-radius: 0;
}

.stock-create-btn {
  min-width: 120px;
}

.ledger-table-wrap {
  margin-top: 0;
  max-height: calc(100vh - 268px);
  overflow: auto;
}

.ledger-card .card-header {
  border-bottom: 1px solid rgba(15, 23, 42, 0.08);
  padding: 0.8rem 0.85rem 0.65rem;
}

.ledger-table-wrap :deep(.card-footer) {
  padding: 0.65rem 0.75rem;
  background: #fff;
}

.ledger-table-wrap td,
.ledger-table-wrap th {
  vertical-align: middle;
}

:global([data-bs-theme="dark"]) .ledger-card,
:global([data-bs-theme="dark"]) .ledger-card .card-header,
:global([data-bs-theme="dark"]) .stock-card-body,
:global([data-bs-theme="dark"]) .ledger-table-wrap,
:global([data-bs-theme="dark"]) .ledger-table-wrap :deep(.card-footer) {
  border-color: #2e3a59 !important;
  background-color: #111827 !important;
  color: #e5e7eb !important;
}

:global([data-bs-theme="dark"]) .ledger-table-wrap :deep(.table) {
  --vz-table-bg: #111827;
  --vz-table-color: #e5e7eb;
  --vz-table-hover-bg: #182035;
  --vz-table-border-color: #2e3a59;
  background-color: #111827 !important;
  color: #e5e7eb !important;
}

:global([data-bs-theme="dark"]) .ledger-table-wrap :deep(.table-light th) {
  background-color: #182035 !important;
  color: #dbeafe !important;
}

:global([data-bs-theme="dark"]) .stock-toolbar-group :deep(.input-group-text),
:global([data-bs-theme="dark"]) .stock-toolbar-group :deep(.form-control),
:global([data-bs-theme="dark"]) .stock-sort-select,
:global([data-bs-theme="dark"]) .stock-refresh-trigger {
  border-color: #2e3a59 !important;
  background-color: #182035 !important;
  color: #e5e7eb !important;
}

:global([data-bs-theme="dark"]) .stock-toolbar-group :deep(.form-control::placeholder) {
  color: #8ea0b8 !important;
}

@media (max-width: 767.98px) {
  .stock-toolbar-group {
    flex-wrap: wrap;
    gap: 0.5rem;
  }

  .stock-toolbar-group .input-group-text {
    border-radius: var(--vz-border-radius);
    border-right: 1px solid var(--vz-border-color);
  }

  .stock-toolbar-group .form-control {
    flex: 1 1 100%;
    border-radius: var(--vz-border-radius);
  }

  .stock-refresh-trigger,
  .stock-sort-select,
  .stock-create-btn {
    border-radius: var(--vz-border-radius);
  }

  .stock-sort-select {
    flex: 1 1 100%;
  }

  .stock-create-btn {
    width: 100%;
  }
}
</style>
