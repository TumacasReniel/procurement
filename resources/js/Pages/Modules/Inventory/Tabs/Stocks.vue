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
            <tr v-else-if="rows.length === 0">
              <td colspan="7" class="text-center text-muted py-5">
                {{ keyword ? 'No stocks matched your search.' : 'No stock records found.' }}
              </td>
            </tr>
            <tr v-else v-for="(stock, index) in rows" :key="stock.id">
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
            :lists="rows.length"
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
    rows: { type: Array, default: () => [] },
    loading: { type: Boolean, default: false },
    meta: { type: Object, default: null },
    links: { type: Array, default: null },
    keyword: { type: String, default: '' },
  },
  emits: ['create', 'fetch', 'refresh', 'update:keyword', 'view', 'edit', 'delete'],
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
  padding: 0.85rem 0.85rem 0;
}

.stock-toolbar-row {
  margin-bottom: 0.75rem;
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

.stock-create-btn {
  min-width: 120px;
}

.ledger-table-wrap {
  margin-top: 0;
  max-height: calc(100vh - 318px);
  overflow: auto;
}

.ledger-card .card-header {
  border-bottom: 1px solid rgba(15, 23, 42, 0.08);
  padding: 1rem 1rem 0.85rem;
}

.ledger-table-wrap :deep(.card-footer) {
  padding: 0.65rem 0.75rem;
  background: #fff;
}

.ledger-table-wrap td,
.ledger-table-wrap th {
  vertical-align: middle;
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
  .stock-create-btn {
    border-radius: var(--vz-border-radius);
  }

  .stock-create-btn {
    width: 100%;
  }
}
</style>
