<template>
  <div class="inv-module-card">
    <!-- Header -->
    <div class="inv-module-header">
      <div class="inv-module-header-left">
        <div class="inv-module-header-icon">
          <i class="ri-shield-star-line"></i>
        </div>
        <div>
          <h5 class="inv-module-title">Property Records</h5>
          <p class="inv-module-subtitle">
            {{ meta?.total ?? normalizedRows.length }} records · serialized/tagged fixed assets
          </p>
        </div>
      </div>

      <div class="inv-module-header-actions">
        <div class="inv-toolbar">
          <div class="inv-search-wrap">
            <i class="ri-search-line inv-search-icon"></i>
            <input
              :value="keyword"
              type="text"
              placeholder="Search property…"
              class="inv-search-input"
              @input="$emit('update:keyword', $event.target.value)"
            />
            <button
              class="inv-search-clear"
              v-if="keyword"
              @click="$emit('update:keyword', '')"
            >
              <i class="ri-close-line"></i>
            </button>
          </div>

          <select v-model="statusFilter" class="inv-select">
            <option value="">All Statuses</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>

          <button
            class="inv-icon-btn"
            title="Refresh"
            v-b-tooltip.hover
            @click="$emit('refresh')"
          >
            <i class="ri-refresh-line"></i>
          </button>

          <button class="inv-create-btn" @click="$emit('create')">
            <i class="ri-add-line"></i>
            Add Property
          </button>
        </div>
      </div>
    </div>

    <!-- Table -->
    <div class="inv-table-shell">
      <table class="inv-table">
        <thead>
          <tr>
            <th style="width: 48px" class="text-center">#</th>
            <th style="width: 130px">Property Code</th>
            <th>Item / Model</th>
            <th style="width: 140px">Serial No.</th>
            <th style="width: 110px" class="text-center">Acq. Cost</th>
            <th style="width: 90px" class="text-center">Depr. %</th>
            <th style="width: 110px" class="text-center">Book Value</th>
            <th style="width: 90px" class="text-center">Status</th>
            <th style="width: 110px" class="text-center">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading" class="inv-table-empty">
            <td colspan="9">
              <div class="inv-loading-state">
                <div class="inv-spinner"></div>
                <span>Loading property records…</span>
              </div>
            </td>
          </tr>
          <tr v-else-if="normalizedRows.length === 0" class="inv-table-empty">
            <td colspan="9">
              <div class="inv-empty-state">
                <i class="ri-shield-star-line"></i>
                <p>
                  {{
                    keyword ? "No property records matched your search." : "No property records yet."
                  }}
                </p>
              </div>
            </td>
          </tr>
          <tr
            v-else
            v-for="(property, index) in normalizedRows"
            :key="property.id"
            class="inv-table-row"
          >
            <td class="text-center inv-row-num">{{ displayRowNumber(index) }}</td>
            <td>
              <span class="inv-code-chip">{{ property.property_code || "—" }}</span>
            </td>
            <td>
              <div class="fw-semibold">{{ property.item_name || "—" }}</div>
              <div class="inv-date-cell">{{ property.model }}</div>
            </td>
            <td class="inv-date-cell">{{ property.serial_no || "—" }}</td>
            <td class="text-center inv-date-cell">₱{{ formatNumber(property.acquisition_cost) }}</td>
            <td class="text-center inv-date-cell">{{ formatNumber(property.depreciation_rate) }}%</td>
            <td class="text-center inv-date-cell">₱{{ formatNumber(property.book_value) }}</td>
            <td class="text-center">
              <span
                class="inv-status-chip"
                :class="property.status === 'active' ? 'is-active' : 'is-inactive'"
              >
                {{ property.status === "active" ? "Active" : "Inactive" }}
              </span>
            </td>
            <td class="text-center">
              <div class="inv-row-actions">
                <button
                  class="inv-action-btn edit"
                  title="Edit"
                  v-b-tooltip.hover
                  @click="$emit('edit', property)"
                >
                  <i class="ri-pencil-line"></i>
                </button>
                <button
                  class="inv-action-btn del"
                  title="Delete"
                  v-b-tooltip.hover
                  @click="$emit('delete', property)"
                >
                  <i class="ri-delete-bin-line"></i>
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <div v-if="meta && meta.total" class="inv-pagination-bar">
        <Pagination
          :links="links"
          :pagination="meta"
          :lists="normalizedRows.length"
          @fetch="(url) => $emit('fetch', url)"
        />
      </div>
    </div>
  </div>
</template>

<script>
import Pagination from "@/Shared/Components/Pagination.vue";

export default {
  name: "InventoryPropertyLedger",
  components: { Pagination },
  props: {
    rows: { type: [Array, Object], default: () => [] },
    loading: { type: Boolean, default: false },
    meta: { type: Object, default: null },
    links: { type: Array, default: null },
    keyword: { type: String, default: "" },
  },
  emits: ["create", "fetch", "refresh", "update:keyword", "update:status", "edit", "delete"],
  data() {
    return { statusFilter: "" };
  },
  watch: {
    statusFilter(value) {
      this.$emit("update:status", value);
    },
  },
  computed: {
    normalizedRows() {
      if (Array.isArray(this.rows)) return this.rows;
      if (Array.isArray(this.rows?.data)) return this.rows.data;
      return [];
    },
  },
  methods: {
    displayRowNumber(idx) {
      return Number(this.meta?.from || 1) + idx;
    },
    formatNumber(v) {
      return new Intl.NumberFormat().format(Number(v || 0));
    },
  },
};
</script>

<style scoped>
.inv-module-card {
  --inv-brand: #4b5b93;
  --inv-brand-soft: #edf1fb;
  --inv-border: #dce4f2;
  --inv-surface: #ffffff;
  --inv-muted: #64748b;
  --inv-ink: #0f172a;
  border: 1px solid var(--inv-border);
  border-radius: 20px;
  background: var(--inv-surface);
  overflow: hidden;
}

.inv-module-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 0.85rem 1rem;
  background: linear-gradient(180deg, #f8fbff, #f0f5ff);
  border-bottom: 1px solid var(--inv-border);
  flex-wrap: wrap;
}
.inv-module-header-left {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}
.inv-module-header-icon {
  width: 40px;
  height: 40px;
  border-radius: 13px;
  background: linear-gradient(135deg, #4b5b93, #38467a);
  color: #fff;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 1.1rem;
  flex-shrink: 0;
}
.inv-module-title {
  margin: 0;
  font-size: 0.9rem;
  font-weight: 800;
  color: var(--inv-ink);
}
.inv-module-subtitle {
  margin: 0;
  font-size: 0.72rem;
  color: var(--inv-muted);
  font-weight: 500;
}

.inv-toolbar {
  display: flex;
  align-items: center;
  gap: 0.45rem;
  flex-wrap: wrap;
}
.inv-search-wrap {
  position: relative;
  display: flex;
  align-items: center;
}
.inv-search-icon {
  position: absolute;
  left: 0.65rem;
  color: var(--inv-muted);
  font-size: 0.95rem;
  pointer-events: none;
}
.inv-search-input {
  height: 38px;
  padding: 0 2rem 0 2.1rem;
  border: 1px solid var(--inv-border);
  border-radius: 10px;
  background: #fff;
  color: var(--inv-ink);
  font-size: 0.84rem;
  width: 220px;
  outline: none;
  transition: border-color 0.15s, box-shadow 0.15s;
}
.inv-search-input:focus {
  border-color: var(--inv-brand);
  box-shadow: 0 0 0 3px rgba(75, 91, 147, 0.1);
}
.inv-search-clear {
  position: absolute;
  right: 0.5rem;
  border: 0;
  background: transparent;
  color: #94a3b8;
  cursor: pointer;
  padding: 0;
}
.inv-select {
  height: 38px;
  padding: 0 0.7rem;
  border: 1px solid var(--inv-border);
  border-radius: 10px;
  background: #fff;
  color: var(--inv-ink);
  font-size: 0.84rem;
  font-weight: 600;
  outline: none;
  cursor: pointer;
}
.inv-icon-btn {
  width: 38px;
  height: 38px;
  border: 1px solid var(--inv-border);
  border-radius: 10px;
  background: #fff;
  color: var(--inv-muted);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 1rem;
  cursor: pointer;
  transition: background 0.15s, color 0.15s;
}
.inv-icon-btn:hover {
  background: var(--inv-brand-soft);
  color: var(--inv-brand);
}
.inv-create-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.3rem;
  height: 38px;
  padding: 0 1rem;
  border: 0;
  border-radius: 10px;
  background: linear-gradient(135deg, #4b5b93, #38467a);
  color: #fff;
  font-size: 0.84rem;
  font-weight: 700;
  cursor: pointer;
  white-space: nowrap;
  transition: opacity 0.15s;
}
.inv-create-btn:hover {
  opacity: 0.9;
}

.inv-table-shell {
  overflow: auto;
  max-height: calc(100vh - 310px);
  min-height: 200px;
}
.inv-table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
}
.inv-table thead th {
  position: sticky;
  top: 0;
  z-index: 2;
  background: linear-gradient(180deg, #f3f7ff, #eaf0fd);
  padding: 0.65rem 0.85rem;
  font-size: 0.72rem;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  color: var(--inv-muted);
  border-bottom: 1px solid var(--inv-border);
  white-space: nowrap;
}
.inv-table-row td {
  padding: 0.72rem 0.85rem;
  border-bottom: 1px solid #f1f5ff;
  font-size: 0.85rem;
  color: var(--inv-ink);
  vertical-align: middle;
  background: var(--inv-surface);
  transition: background 0.12s;
}
.inv-table-row:hover td {
  background: #f8fbff;
}
.inv-table-empty td {
  padding: 0;
  border: 0;
}
.inv-row-num {
  color: var(--inv-muted);
  font-size: 0.78rem;
  font-weight: 700;
}
.inv-code-chip {
  display: inline-block;
  padding: 0.22rem 0.6rem;
  border-radius: 7px;
  background: rgba(75, 91, 147, 0.08);
  color: var(--inv-brand);
  font-size: 0.78rem;
  font-weight: 800;
  font-family: ui-monospace, monospace;
  border: 1px solid rgba(75, 91, 147, 0.15);
}
.inv-status-chip {
  display: inline-block;
  padding: 0.2rem 0.55rem;
  border-radius: 20px;
  font-size: 0.72rem;
  font-weight: 700;
}
.inv-status-chip.is-active {
  background: rgba(16, 185, 129, 0.1);
  color: #059669;
}
.inv-status-chip.is-inactive {
  background: rgba(148, 163, 184, 0.16);
  color: #64748b;
}
.inv-date-cell {
  color: var(--inv-muted);
  font-size: 0.8rem;
}
.inv-row-actions {
  display: inline-flex;
  gap: 0.3rem;
}
.inv-action-btn {
  width: 30px;
  height: 30px;
  border: 1px solid var(--inv-border);
  border-radius: 8px;
  background: #fff;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 0.88rem;
  cursor: pointer;
  transition: all 0.15s;
}
.inv-action-btn.edit:hover {
  background: #fef3c7;
  border-color: #f59e0b;
  color: #d97706;
}
.inv-action-btn.del:hover {
  background: #fee2e2;
  border-color: #f87171;
  color: #dc2626;
}

.inv-loading-state,
.inv-empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  padding: 3.5rem 1rem;
  color: var(--inv-muted);
}
.inv-spinner {
  width: 36px;
  height: 36px;
  border: 3px solid #e2e8f0;
  border-top-color: var(--inv-brand);
  border-radius: 50%;
  animation: inv-spin 0.7s linear infinite;
}
@keyframes inv-spin {
  to {
    transform: rotate(360deg);
  }
}
.inv-empty-state i {
  font-size: 2.5rem;
  opacity: 0.35;
}
.inv-empty-state p {
  margin: 0;
  font-size: 0.9rem;
  font-weight: 500;
}
.inv-pagination-bar {
  padding: 0.6rem 1rem;
  border-top: 1px solid var(--inv-border);
  background: #fafbff;
}

/* ── Dark mode ──────────────────────────────────────── */
:global([data-bs-theme="dark"] .inv-module-card),
:global([data-layout-mode="dark"] .inv-module-card){
  --inv-brand-soft: #1a2540;
  --inv-border: #2e3a59;
  --inv-surface: #111827;
  --inv-muted: #9ca9c7;
  --inv-ink: #e5e7eb;
}
:global([data-bs-theme="dark"] .inv-module-header),
:global([data-layout-mode="dark"] .inv-module-header),
:global([data-bs-theme="dark"] .inv-table thead th),
:global([data-layout-mode="dark"] .inv-table thead th){
  background: linear-gradient(180deg, #151e33, #111827);
}
:global([data-bs-theme="dark"] .inv-search-input),
:global([data-layout-mode="dark"] .inv-search-input),
:global([data-bs-theme="dark"] .inv-select),
:global([data-layout-mode="dark"] .inv-select),
:global([data-bs-theme="dark"] .inv-icon-btn),
:global([data-layout-mode="dark"] .inv-icon-btn),
:global([data-bs-theme="dark"] .inv-action-btn),
:global([data-layout-mode="dark"] .inv-action-btn){
  background: #151e33;
  border-color: var(--inv-border);
  color: var(--inv-ink);
}
:global([data-bs-theme="dark"] .inv-icon-btn:hover),
:global([data-layout-mode="dark"] .inv-icon-btn:hover){
  background: #1a2540;
  color: #8ea0f4;
}
:global([data-bs-theme="dark"] .inv-table-row td),
:global([data-layout-mode="dark"] .inv-table-row td){
  border-bottom-color: #1c2740;
}
:global([data-bs-theme="dark"] .inv-table-row:hover td),
:global([data-layout-mode="dark"] .inv-table-row:hover td){
  background: #151e33;
}
:global([data-bs-theme="dark"] .inv-code-chip),
:global([data-layout-mode="dark"] .inv-code-chip){
  background: rgba(142, 160, 244, 0.14);
  border-color: rgba(142, 160, 244, 0.28);
  color: #8ea0f4;
}
:global([data-bs-theme="dark"] .inv-status-chip.is-inactive),
:global([data-layout-mode="dark"] .inv-status-chip.is-inactive){
  background: rgba(148, 163, 184, 0.16);
  color: #9ca9c7;
}
:global([data-bs-theme="dark"] .inv-pagination-bar),
:global([data-layout-mode="dark"] .inv-pagination-bar){
  background: #111827;
  border-top-color: var(--inv-border);
}
:global([data-bs-theme="dark"] .inv-action-btn.edit:hover),
:global([data-layout-mode="dark"] .inv-action-btn.edit:hover){
  background: rgba(245, 158, 11, 0.14);
  border-color: #f59e0b;
  color: #fbbf24;
}
:global([data-bs-theme="dark"] .inv-action-btn.del:hover),
:global([data-layout-mode="dark"] .inv-action-btn.del:hover){
  background: rgba(248, 113, 113, 0.14);
  border-color: #f87171;
  color: #f87171;
}
</style>
