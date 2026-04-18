<template>
  <div class="card bg-light-subtle shadow-none border ledger-card">
    <div class="card-header bg-light-subtle">
      <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
        <div class="d-flex">
          <div class="flex-shrink-0 me-3">
            <div style="height: 2.5rem; width: 2.5rem">
              <span class="avatar-title bg-success-subtle rounded p-2 mt-n1">
                <i class="ri-arrow-left-right-line text-success fs-24"></i>
              </span>
            </div>
          </div>
          <div class="flex-grow-1">
            <h5 class="mb-0 fs-14">
              <span class="text-body">Item Withdrawals</span>
            </h5>
            <p class="text-muted text-truncate-two-lines fs-12 mb-0">
              A running withdrawal ledger for released inventory items.
            </p>
          </div>
        </div>
      </div>
    </div>

    <div class="card-body bg-white rounded-bottom mt-3">
      <b-row class="mb-2 ms-1 me-1 mb-5">
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
                  placeholder="Search Withdrawal Ledger"
                  class="form-control"
                />
              </div>
            </div>

            <button
              type="button"
              class="btn ledger-refresh-btn"
              title="Refresh"
              v-b-tooltip.hover
              @click="handleRefresh"
            >
              <i class="bx bx-refresh search-icon"></i>
            </button>
            <button
              type="button"
              class="btn withdrawal-create-btn"
              @click="$emit('create')"
            >
              <i class="ri-add-circle-fill me-2"></i>Create
            </button>
          </div>
        </b-col>
      </b-row>

      <div class="table-responsive table-card ledger-table-wrap">
        <table class="table align-middle table-hover mb-0 ledger-table">
          <thead class="table-light thead-fixed">
            <tr>
              <th>Item</th>
              <th>Requested By</th>
              <th>Approved By</th>
              <th>Date Released</th>
              <th>Status</th>
              <th>Remarks</th>
              <th class="text-center" style="width: 140px">Action</th>
            </tr>
          </thead>
          <tbody class="table-group-divider">
            <tr v-if="loading">
              <td colspan="7" class="text-center text-muted py-4">Loading withdrawal records...</td>
            </tr>
            <tr v-else-if="filteredRows.length === 0">
              <td colspan="7" class="text-center text-muted py-4">No withdrawal records found.</td>
            </tr>
            <tr v-else v-for="item in filteredRows" :key="item.id">
              <td class="fw-semibold">{{ item.item_name }}</td>
              <td>{{ item.requested_by }}</td>
              <td>{{ item.approved_by }}</td>
              <td>{{ item.released_at }}</td>
              <td>
                <span class="badge withdrawal-status-chip">
                  <i class="ri-checkbox-circle-fill me-1"></i>{{ item.status }}
                </span>
              </td>
              <td>{{ item.remarks }}</td>
              <td class="text-center">
                <div class="d-inline-flex gap-1">
                  <button
                    class="btn btn-sm btn-outline-primary withdrawal-action-btn"
                    type="button"
                    title="View"
                    @click="$emit('view', item)"
                  >
                    <i class="ri-eye-line"></i>
                  </button>
                  <button class="btn btn-sm btn-outline-warning withdrawal-action-btn" type="button" title="Edit" @click="$emit('edit', item)">
                    <i class="ri-pencil-line"></i>
                  </button>
                  <button class="btn btn-sm btn-outline-danger withdrawal-action-btn" type="button" title="Delete" @click="$emit('delete', item)">
                    <i class="ri-delete-bin-line"></i>
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
        :lists="filteredRows.length"
        @fetch="(pageUrl) => $emit('fetch', pageUrl)"
      />
    </div>
  </div>
</template>

<script>
import Pagination from '@/Shared/Components/Pagination.vue';

export default {
  name: 'WithdrawalLedger',
  components: { Pagination },
  props: {
    rows: { type: Array, default: () => [] },
    loading: { type: Boolean, default: false },
    meta: { type: Object, default: null },
    links: { type: Array, default: null },
  },
  emits: ['create', 'fetch', 'refresh', 'view', 'edit', 'delete'],
  data() {
    return {
      filters: {
        keyword: '',
      },
    };
  },
  computed: {
    filteredRows() {
      const keyword = (this.filters.keyword || '').toLowerCase();

      return this.rows.filter((item) => {
        const searchable = [item.item_name, item.requested_by, item.approved_by, item.released_at, item.status, item.remarks]
          .filter(Boolean)
          .join(' ')
          .toLowerCase();

        return searchable.includes(keyword);
      });
    },
  },
  methods: {
    handleRefresh() {
      this.filters.keyword = '';
      this.$emit('refresh');
    },
  },
};
</script>

<style scoped>
.ledger-card .card-header {
  border-bottom: 1px solid rgba(15, 23, 42, 0.08);
}

.ledger-table-wrap {
  margin-top: -39px;
  max-height: calc(100vh - 350px);
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

.ledger-table thead th {
  font-weight: 700;
  font-size: 12px;
}

.ledger-table tbody td {
  vertical-align: middle;
}

.withdrawal-status-chip {
  background: #cff4fc;
  color: #055160;
  border: 1px solid #b6effb;
  border-radius: 999px;
  padding: 5px 9px;
  font-weight: 700;
}

.withdrawal-action-btn {
  border-width: 1px;
}

.withdrawal-create-btn {
  flex: 0 0 176px;
  min-width: 176px;
  border-radius: 0 4px 4px 0;
  background: #3f4d85;
  border: 1px solid #3f4d85;
  color: #fff;
  font-weight: 600;
  white-space: nowrap;
}

.withdrawal-create-btn:hover,
.withdrawal-create-btn:focus {
  background: #364273;
  border-color: #364273;
  color: #fff;
}

.ledger-card :deep(.pagination) {
  margin-top: 1rem;
}

@media (max-width: 768px) {
  .ledger-toolbar-wrap {
    overflow-x: auto;
  }
}
</style>
