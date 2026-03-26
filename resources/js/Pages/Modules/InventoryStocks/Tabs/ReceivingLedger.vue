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
              <span class="text-body">Purchase Order Receivings</span>
            </h5>
            <p class="text-muted text-truncate-two-lines fs-12 mb-0">
              Completed procurement purchase orders ready for inventory receiving review.
            </p>
          </div>
        </div>
      </div>
    </div>

    <div class="card-body bg-white rounded-bottom mt-3">
      <b-row class="mb-5 ms-1 me-1">
        <b-col lg>
          <div class="input-group mb-1 ledger-toolbar">
            <span class="input-group-text">
              <i class="ri-search-line search-icon"></i>
            </span>
            <input
              v-model="filters.keyword"
              type="text"
              placeholder="Search Receiving Ledger"
              class="form-control"
              style="width: 40%"
            />
            <Multiselect
              v-model="filters.status"
              class="white ledger-status-select"
              style="width: 15%"
              :options="statusOptions"
              :searchable="true"
              placeholder="Select Status"
            />
            <span
              class="input-group-text"
              style="cursor: pointer"
              title="Refresh"
              v-b-tooltip.hover
              @click="handleRefresh"
            >
              <i class="bx bx-refresh search-icon"></i>
            </span>
          </div>
        </b-col>
      </b-row>

      <div class="table-responsive table-card ledger-table-wrap">
        <table class="table align-middle table-hover mb-0 ledger-table">
          <thead class="table-light thead-fixed">
            <tr>
              <th>P.O. Number</th>
              <th>Supplier Name</th>
              <th>Remarks</th>
              <th>Date</th>
              <th>Status</th>
              <th class="text-center" style="width: 150px">Action</th>
            </tr>
          </thead>
          <tbody class="table-group-divider">
            <tr v-if="loading">
              <td colspan="6" class="text-center text-muted py-4">Loading receiving records...</td>
            </tr>
            <tr v-else-if="filteredRows.length === 0">
              <td colspan="6" class="text-center text-muted py-4">No receiving records found.</td>
            </tr>
            <tr v-else v-for="item in filteredRows" :key="item.id">
              <td class="fw-semibold">{{ item.po_number }}</td>
              <td>{{ item.supplier_name }}</td>
              <td>{{ item.remarks }}</td>
              <td>{{ item.received_at }}</td>
              <td>
                <span class="badge receiving-status-chip">
                  <i class="ri-checkbox-circle-fill me-1"></i>{{ item.status }}
                </span>
              </td>
              <td class="text-center">
                <div class="d-inline-flex gap-1">
                  <button
                    class="btn btn-sm btn-outline-success receiving-action-btn"
                    type="button"
                    title="View"
                    @click="$emit('view', item)"
                  >
                    <i class="ri-eye-line"></i>
                  </button>
                  <button
                    class="btn btn-sm btn-outline-primary receiving-action-btn"
                    type="button"
                    title="Edit"
                    @click="$emit('edit', item)"
                  >
                    <i class="ri-pencil-line"></i>
                  </button>
                  <button
                    class="btn btn-sm btn-outline-secondary receiving-action-btn"
                    type="button"
                    title="Transfer to Inventory"
                    @click="$emit('transfer', item)"
                  >
                    <i class="ri-exchange-box-line"></i>
                  </button>
                  <button
                    class="btn btn-sm btn-outline-warning receiving-action-btn"
                    type="button"
                    title="Withdraw"
                    @click="$emit('withdraw', item)"
                  >
                    <i class="ri-arrow-up-circle-line"></i>
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
import Multiselect from '@vueform/multiselect';
import Pagination from '@/Shared/Components/Pagination.vue';

export default {
  name: 'ReceivingLedger',
  components: { Multiselect, Pagination },
  props: {
    rows: { type: Array, default: () => [] },
    loading: { type: Boolean, default: false },
    meta: { type: Object, default: null },
    links: { type: Array, default: null },
  },
  emits: ['fetch', 'refresh', 'view', 'edit', 'transfer', 'withdraw'],
  data() {
    return {
      filters: {
        keyword: '',
        status: '',
      },
    };
  },
  computed: {
    statusOptions() {
      return [...new Set(this.rows.map((item) => item.status).filter(Boolean))];
    },
    filteredRows() {
      const keyword = (this.filters.keyword || '').toLowerCase();
      const status = this.filters.status || '';

      return this.rows.filter((item) => {
        const searchable = [item.po_number, item.supplier_name, item.remarks, item.received_at]
          .filter(Boolean)
          .join(' ')
          .toLowerCase();

        return searchable.includes(keyword) && (!status || item.status === status);
      });
    },
  },
  methods: {
    handleRefresh() {
      this.filters.keyword = '';
      this.filters.status = '';
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
</style>
