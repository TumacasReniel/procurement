<template>
  <div class="card bg-light-subtle shadow-none border ledger-card">
    <!-- Header -->
    <div class="card-header bg-light-subtle">
      <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
        <div class="d-flex">
          <div class="flex-shrink-0 me-3">
            <div style="height: 2.5rem; width: 2.5rem">
              <span class="avatar-title bg-primary-subtle rounded p-2 mt-n1">
                <i class="ri-arrow-left-down-line text-primary fs-24"></i>
              </span>
            </div>
          </div>
          <div class="flex-grow-1">
            <h5 class="mb-0 fs-14">
              <span class="text-body">From Procurement</span>
            </h5>
            <p class="text-muted text-truncate-two-lines fs-12 mb-0">
              Items auto-received from completed Purchase Orders — grouped by PO with live stock levels.
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Body -->
    <div class="card-body bg-white rounded-bottom pr-card-body">
      <b-row class="mb-3">
        <b-col lg>
          <div class="ledger-toolbar-wrap">
            <div class="ledger-toolbar">
              <div class="input-group ledger-search-group">
                <span class="input-group-text">
                  <i class="ri-search-line search-icon"></i>
                </span>
                <input
                  v-model="keyword"
                  type="text"
                  placeholder="Search PO number or item name…"
                  class="form-control"
                  @input="onSearch"
                />
              </div>
            </div>

            <select v-model="sort" class="form-select ledger-sort-select" aria-label="Sort">
              <option value="latest">Latest PO Date</option>
              <option value="oldest">Oldest PO Date</option>
              <option value="value_desc">Highest Value</option>
              <option value="items_desc">Most Items</option>
            </select>

            <button
              type="button"
              class="btn ledger-refresh-btn"
              title="Refresh"
              v-b-tooltip.hover
              :disabled="loading"
              @click="handleRefresh"
            >
              <i class="bx bx-refresh search-icon" :class="{ 'spin': loading }"></i>
            </button>

            <button
              type="button"
              class="btn pr-export-btn"
              title="Export as CSV"
              v-b-tooltip.hover
              :disabled="!rows.length || loading"
              @click="exportCsv"
            >
              <i class="ri-download-2-line me-1"></i>Export
            </button>
          </div>
        </b-col>
      </b-row>

      <div class="table-responsive table-card ledger-table-wrap">
        <table class="table align-middle table-hover mb-0 ledger-table">
          <thead class="table-light thead-fixed">
            <tr>
              <th style="width: 14%">PO Number</th>
              <th style="width: 18%">IAR</th>
              <th>Items Received</th>
              <th style="width: 14%" class="text-end">Total Value</th>
              <th style="width: 14%" class="text-center">Stock Status</th>
              <th class="text-center" style="width: 80px">Action</th>
            </tr>
          </thead>
          <tbody class="table-group-divider">
            <!-- Loading -->
            <tr v-if="loading">
              <td colspan="6" class="text-center text-muted py-4">
                <div class="d-flex align-items-center justify-content-center gap-2">
                  <div class="spinner-border spinner-border-sm text-primary"></div>
                  Loading transfers…
                </div>
              </td>
            </tr>
            <!-- Empty -->
            <tr v-else-if="sortedRows.length === 0">
              <td colspan="6" class="text-center text-muted py-4">
                <div>No procurement transfers found.</div>
                <div v-if="keyword" class="small mt-1">
                  Try clearing your search.
                </div>
              </td>
            </tr>
            <!-- Rows -->
            <template v-else v-for="po in sortedRows" :key="po.id">
              <!-- PO Row -->
              <tr
                class="pr-po-row"
                :class="{ 'pr-po-row--expanded': expandedPos.has(po.id) }"
                @click="togglePo(po.id)"
                style="cursor: pointer"
              >
                <td>
                  <div class="fw-semibold text-primary">{{ po.code }}</div>
                  <div class="small text-muted">
                    <i class="ri-calendar-line me-1"></i>{{ formatDate(po.po_date) }}
                  </div>
                </td>
                <td>
                  <div v-if="po.iars && po.iars.length" class="d-flex flex-wrap gap-1">
                    <span
                      v-for="iar in po.iars"
                      :key="iar.id"
                      class="badge pr-iar-badge"
                    >
                      <i class="ri-file-check-line me-1"></i>{{ iar.code }}
                    </span>
                  </div>
                  <span v-else class="small text-muted fst-italic">No IAR</span>
                </td>
                <td>
                  <div class="d-flex align-items-center gap-2">
                    <span class="pr-item-count-badge">
                      {{ (po.inventory_transfers || []).length }}
                      item{{ (po.inventory_transfers || []).length !== 1 ? 's' : '' }}
                    </span>
                    <span class="small text-muted text-truncate" style="max-width: 280px">
                      {{ itemNamePreview(po) }}
                    </span>
                  </div>
                </td>
                <td class="text-end fw-semibold">
                  ₱{{ formatNumber(poSubtotal(po)) }}
                </td>
                <td class="text-center">
                  <div class="d-flex align-items-center justify-content-center gap-1">
                    <span v-if="poDepletedCount(po) > 0" class="badge pr-badge-depleted">
                      <i class="ri-close-circle-line me-1"></i>{{ poDepletedCount(po) }} depleted
                    </span>
                    <span v-if="poLowCount(po) > 0" class="badge pr-badge-low">
                      <i class="ri-error-warning-line me-1"></i>{{ poLowCount(po) }} low
                    </span>
                    <span v-if="poDepletedCount(po) === 0 && poLowCount(po) === 0" class="badge pr-badge-ok">
                      <i class="ri-checkbox-circle-line me-1"></i>All in stock
                    </span>
                  </div>
                </td>
                <td class="text-center">
                  <div class="d-inline-flex gap-1">
                    <button
                      class="btn btn-sm btn-outline-primary pr-action-btn"
                      :title="expandedPos.has(po.id) ? 'Collapse' : 'Expand items'"
                      v-b-tooltip.hover
                      @click.stop="togglePo(po.id)"
                    >
                      <i
                        class="ri-arrow-down-s-line"
                        :style="expandedPos.has(po.id) ? 'transform:rotate(180deg)' : ''"
                        style="transition: transform 0.2s; display:inline-block"
                      ></i>
                    </button>
                    <a
                      :href="`/purchase-orders/${po.id}`"
                      class="btn btn-sm btn-outline-secondary pr-action-btn"
                      title="Open PO"
                      target="_blank"
                      v-b-tooltip.hover
                      @click.stop
                    >
                      <i class="ri-external-link-line"></i>
                    </a>
                  </div>
                </td>
              </tr>
              <!-- Expanded items sub-rows -->
              <tr
                v-if="expandedPos.has(po.id)"
                v-for="(transfer, idx) in po.inventory_transfers"
                :key="`${po.id}-${transfer.id}`"
                class="pr-item-subrow"
              >
                <td>
                  <span class="pr-subrow-indent"></span>
                  <span class="pr-item-code">{{ transfer.inventory_item?.code ?? '—' }}</span>
                </td>
                <td class="small text-muted">
                  {{ idx === 0 ? formatDate(transfer.transferred_at) : '' }}
                </td>
                <td class="fw-semibold small">{{ transfer.inventory_item?.name ?? '—' }}</td>
                <td class="text-end small">
                  <span class="text-muted">{{ formatQty(transfer.quantity) }}
                    {{ transfer.inventory_stock?.unit?.name_short ?? '' }}
                    × ₱{{ formatNumber(transfer.inventory_stock?.unit_cost ?? 0) }}
                  </span>
                  <div class="fw-semibold">₱{{ formatNumber(computeTotal(transfer)) }}</div>
                </td>
                <td class="text-center">
                  <span class="badge" :class="stockBadgeClass(transfer.inventory_stock?.quantity)">
                    <i class="ri-stack-line me-1"></i>{{ formatQty(transfer.inventory_stock?.quantity) }} in stock
                  </span>
                </td>
                <td></td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="meta && meta.last_page > 1" class="d-flex align-items-center justify-content-between mt-2 px-1">
        <span class="small text-muted">
          Showing {{ meta.from ?? 1 }}–{{ meta.to ?? rows.length }} of {{ meta.total }} PO(s)
        </span>
        <nav>
          <ul class="pagination pagination-sm mb-0">
            <li class="page-item" :class="{ disabled: meta.current_page === 1 }">
              <button class="page-link" @click="changePage(meta.current_page - 1)">‹</button>
            </li>
            <template v-for="page in visiblePages" :key="page">
              <li v-if="page === '...'" class="page-item disabled">
                <span class="page-link">…</span>
              </li>
              <li v-else class="page-item" :class="{ active: meta.current_page === page }">
                <button class="page-link" @click="changePage(page)">{{ page }}</button>
              </li>
            </template>
            <li class="page-item" :class="{ disabled: meta.current_page === meta.last_page }">
              <button class="page-link" @click="changePage(meta.current_page + 1)">›</button>
            </li>
          </ul>
        </nav>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: "ProcurementReceivings",
  props: {
    rows:    { type: Array,   default: () => [] },
    meta:    { type: Object,  default: null },
    loading: { type: Boolean, default: false },
  },
  emits: ["filter"],
  data() {
    return {
      keyword:     "",
      sort:        "latest",
      searchTimer: null,
      expandedPos: new Set(),
    };
  },
  computed: {
    sortedRows() {
      const rows = [...this.rows];
      const date  = v => new Date(String(v || '').replace(' ', 'T')).getTime() || 0;
      if (this.sort === 'oldest')     return rows.sort((a, b) => date(a.po_date) - date(b.po_date));
      if (this.sort === 'value_desc') return rows.sort((a, b) => this.poSubtotal(b) - this.poSubtotal(a));
      if (this.sort === 'items_desc') return rows.sort((a, b) => (b.inventory_transfers?.length ?? 0) - (a.inventory_transfers?.length ?? 0));
      return rows.sort((a, b) => date(b.po_date) - date(a.po_date));
    },
    visiblePages() {
      const current = this.meta?.current_page ?? 1;
      const last    = this.meta?.last_page    ?? 1;
      if (last <= 7) return Array.from({ length: last }, (_, i) => i + 1);
      const pages = [1];
      if (current > 3) pages.push("...");
      for (let p = Math.max(2, current - 1); p <= Math.min(last - 1, current + 1); p++) pages.push(p);
      if (current < last - 2) pages.push("...");
      pages.push(last);
      return pages;
    },
  },
  methods: {
    emitFilter(page = 1) {
      this.$emit("filter", { page, search: this.keyword, status: "" });
    },
    onSearch() {
      clearTimeout(this.searchTimer);
      this.searchTimer = setTimeout(() => this.emitFilter(1), 300);
    },
    handleRefresh() {
      this.keyword = "";
      this.emitFilter(1);
    },
    changePage(page) {
      this.emitFilter(page);
    },
    togglePo(id) {
      const next = new Set(this.expandedPos);
      next.has(id) ? next.delete(id) : next.add(id);
      this.expandedPos = next;
    },
    itemNamePreview(po) {
      const names = (po.inventory_transfers || [])
        .map(t => t.inventory_item?.name)
        .filter(Boolean);
      if (!names.length) return '—';
      const preview = names.slice(0, 2).join(', ');
      return names.length > 2 ? `${preview} +${names.length - 2} more` : preview;
    },
    poSubtotal(po) {
      return (po.inventory_transfers || []).reduce((sum, t) => sum + this.computeTotal(t), 0);
    },
    poDepletedCount(po) {
      return (po.inventory_transfers || []).filter(t => parseFloat(t.inventory_stock?.quantity ?? 0) <= 0).length;
    },
    poLowCount(po) {
      return (po.inventory_transfers || []).filter(t => {
        const q = parseFloat(t.inventory_stock?.quantity ?? 0);
        return q > 0 && q < 5;
      }).length;
    },
    computeTotal(t) {
      return parseFloat(t.quantity ?? 0) * parseFloat(t.inventory_stock?.unit_cost ?? 0);
    },
    formatQty(val) {
      const n = parseFloat(val ?? 0);
      return isNaN(n) ? '0' : n % 1 === 0 ? n.toString() : n.toFixed(2);
    },
    formatNumber(val) {
      const n = parseFloat(val ?? 0);
      return isNaN(n) ? '0.00' : new Intl.NumberFormat('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(n);
    },
    formatDate(val) {
      if (!val) return '—';
      return new Date(String(val).replace(' ', 'T')).toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' });
    },
    stockBadgeClass(qty) {
      const n = parseFloat(qty ?? 0);
      if (n <= 0) return 'pr-badge-depleted';
      if (n < 5)  return 'pr-badge-low';
      return 'pr-badge-ok';
    },
    exportCsv() {
      const headers = ["PO Code","IAR","PO Date","Item Code","Item Name","Unit","Qty Received","Unit Cost","Total Value","Current Stock"];
      const dataRows = [];
      for (const po of this.rows) {
        const iarCodes = (po.iars || []).map(i => i.code).join('; ');
        for (const t of po.inventory_transfers || []) {
          dataRows.push([
            po.code, iarCodes, this.formatDate(po.po_date),
            t.inventory_item?.code ?? '', t.inventory_item?.name ?? '',
            t.inventory_stock?.unit?.name_short ?? '',
            t.quantity ?? 0, t.inventory_stock?.unit_cost ?? 0,
            this.computeTotal(t).toFixed(2), t.inventory_stock?.quantity ?? 0,
          ]);
        }
      }
      const escape = v => `"${String(v).replace(/"/g, '""')}"`;
      const csv  = [headers, ...dataRows].map(r => r.map(escape).join(',')).join('\n');
      const blob = new Blob(['﻿' + csv], { type: 'text/csv;charset=utf-8;' });
      const url  = URL.createObjectURL(blob);
      const a    = document.createElement('a');
      a.href = url; a.download = `procurement-receivings-${new Date().toISOString().slice(0,10)}.csv`;
      a.click(); URL.revokeObjectURL(url);
    },
  },
};
</script>

<style scoped>
.ledger-card .card-header {
  border-bottom: 1px solid rgba(15, 23, 42, 0.08);
}

.pr-card-body {
  padding: 0.65rem;
}

.ledger-table-wrap {
  max-height: calc(100vh - 278px);
  overflow: auto;
}

/* ── Toolbar ──────────────────────────────────────────── */
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

.ledger-search-group { height: 100%; }

.ledger-sort-select {
  flex: 0 0 180px;
  min-width: 180px;
  border-left: 0;
  border-radius: 0;
}

.ledger-refresh-btn {
  flex: 0 0 52px;
  min-width: 52px;
  border: 1px solid #d7dfef;
  border-left: 0;
  border-radius: 0;
  background: #f8fbff;
  color: #334155;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
}
.ledger-refresh-btn:hover:not(:disabled),
.ledger-refresh-btn:focus:not(:disabled) { background: #eef4ff; color: #1d4ed8; }
.ledger-refresh-btn:disabled { opacity: 0.45; cursor: not-allowed; }

.pr-export-btn {
  flex: 0 0 100px;
  min-width: 100px;
  border: 1px solid #d7dfef;
  border-left: 0;
  border-radius: 0 4px 4px 0;
  background: #f8fbff;
  color: #334155;
  font-weight: 600;
  font-size: 0.82rem;
  white-space: nowrap;
}
.pr-export-btn:hover:not(:disabled) { background: #eef4ff; color: #1d4ed8; }
.pr-export-btn:disabled { opacity: 0.45; cursor: not-allowed; }

@keyframes spin { to { transform: rotate(360deg); } }
.spin { animation: spin 0.75s linear infinite; display: inline-block; }

/* ── Table ────────────────────────────────────────────── */
.ledger-table thead th {
  font-weight: 700;
  font-size: 12px;
}

.ledger-table tbody td {
  vertical-align: middle;
}

/* ── PO row ───────────────────────────────────────────── */
.pr-po-row:hover td { background: #f0f5ff !important; }
.pr-po-row--expanded > td:first-child {
  border-left: 3px solid #3b4f8a;
}

/* ── Sub-rows ─────────────────────────────────────────── */
.pr-item-subrow td {
  background: #fafcff;
  font-size: 0.82rem;
  border-bottom: 1px solid #f1f5ff;
}
.pr-item-subrow:hover td { background: #f5f8ff !important; }
.pr-subrow-indent {
  display: inline-block;
  width: 16px;
  border-bottom: 1px dashed #c7d3f0;
  vertical-align: middle;
  margin-right: 6px;
}

/* ── Chips & badges ───────────────────────────────────── */
.pr-iar-badge {
  background: #e0f2fe;
  color: #0284c7;
  border: 1px solid #bae6fd;
  font-size: 0.7rem;
  font-weight: 700;
  padding: 3px 8px;
}

.pr-item-count-badge {
  display: inline-block;
  background: rgba(59,79,138,0.1);
  color: #3b4f8a;
  font-size: 0.72rem;
  font-weight: 700;
  padding: 2px 8px;
  border-radius: 10px;
  white-space: nowrap;
}

.pr-item-code {
  display: inline-block;
  background: #f1f5f9;
  color: #475569;
  font-size: 0.7rem;
  font-family: monospace;
  font-weight: 600;
  padding: 1px 6px;
  border-radius: 4px;
}

.pr-badge-ok       { background: #d1e7dd; color: #0f5132; border: 1px solid #badbcc; font-weight: 700; }
.pr-badge-low      { background: #fff3cd; color: #664d03; border: 1px solid #ffecb5; font-weight: 700; }
.pr-badge-depleted { background: #f8d7da; color: #842029; border: 1px solid #f5c2c7; font-weight: 700; }

.pr-action-btn { border-width: 1px; }

/* ── Dark mode ────────────────────────────────────────── */
:global([data-bs-theme="dark"]) .ledger-card,
:global([data-bs-theme="dark"]) .ledger-card .card-header,
:global([data-bs-theme="dark"]) .pr-card-body,
:global([data-bs-theme="dark"]) .ledger-table-wrap {
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

:global([data-bs-theme="dark"]) .ledger-toolbar-wrap :deep(.input-group-text),
:global([data-bs-theme="dark"]) .ledger-toolbar-wrap :deep(.form-control),
:global([data-bs-theme="dark"]) .ledger-sort-select,
:global([data-bs-theme="dark"]) .ledger-refresh-btn,
:global([data-bs-theme="dark"]) .pr-export-btn {
  border-color: #2e3a59 !important;
  background-color: #182035 !important;
  color: #e5e7eb !important;
}

:global([data-bs-theme="dark"]) .pr-item-subrow td {
  background-color: #141e30 !important;
}

@media (max-width: 768px) {
  .ledger-toolbar-wrap { overflow-x: auto; }
}
</style>
