<template>
  <div class="plan-source-list">
    <!-- Header row -->
    <div class="source-list-header">
      <div class="source-list-header__left">
        <h6 class="mb-0 fs-14">{{ title }}</h6>
        <span class="source-count-pill">
          {{ plans.length }} {{ countLabel }}{{ plans.length === 1 ? "" : "s" }}
        </span>
      </div>
    </div>

    <!-- Search bar (only when list is long) -->
    <div v-if="plans.length > 3" class="source-search-wrap">
      <i class="ri-search-line source-search-wrap__icon"></i>
      <input
        v-model="searchQuery"
        type="text"
        class="source-search-input"
        placeholder="Search by plan no. or unit..."
      />
      <button
        v-if="searchQuery"
        type="button"
        class="source-search-clear"
        @click="searchQuery = ''"
      >
        <i class="ri-close-line"></i>
      </button>
    </div>

    <!-- Flat list -->
    <div v-if="filteredPlans.length" class="source-list">
      <div
        v-for="(plan, index) in filteredPlans"
        :key="plan.id || plan.ppmp_no || index"
        class="source-list-item"
        :class="planTypeClass(plan)"
      >
        <div class="source-list-item__identity">
          <strong class="source-list-item__code">
            {{ plan.ppmp_no || plan.code || "-" }}
          </strong>
          <small class="source-list-item__unit">
            {{ sourceLabel(plan.unit) || "Agency-wide" }}
          </small>
          <small class="source-list-item__meta">
            {{ sourceLabel(plan.division) || "" }}
            <template v-if="plan.fund_cluster?.name || plan.source_of_funds">
              · {{ plan.fund_cluster?.name || plan.source_of_funds }}
            </template>
            <template v-if="fiscalYear(plan)">
              · FY {{ fiscalYear(plan) }}
            </template>
          </small>
        </div>

        <div class="source-list-item__metrics">
          <span class="source-metric-chip">
            <i class="ri-file-list-line"></i>
            {{ Number(plan.items_count || 0).toLocaleString() }} item{{ Number(plan.items_count || 0) === 1 ? "" : "s" }}
          </span>
          <span class="source-metric-chip source-metric-chip--money">
            {{ formatCurrency(plan.total_abc || plan.total_amount || plan.estimated_budget) }}
          </span>
        </div>

        <div class="source-list-item__badges">
          <b-badge :variant="planTypeVariant(plan)">{{ planTypeLabel(plan) }}</b-badge>
          <b-badge :variant="statusVariant(plan)">{{ planStatus(plan) }}</b-badge>
        </div>

        <div class="source-list-item__actions">
          <b-button
            variant="soft-primary"
            size="sm"
            :disabled="!plan.id"
            @click="$emit('view', plan)"
          >
            <i class="ri-eye-line align-bottom me-1"></i>
            View
          </b-button>
          <b-button
            variant="outline-secondary"
            size="sm"
            :disabled="!plan.id"
            @click="$emit('print', plan)"
          >
            <i class="ri-printer-line align-bottom"></i>
          </b-button>
        </div>
      </div>
    </div>

    <!-- No results after search -->
    <div v-else-if="searchQuery && plans.length" class="source-empty-state">
      <div class="source-empty-state__icon"><i class="ri-search-line"></i></div>
      <strong class="source-empty-state__title">No results for "{{ searchQuery }}"</strong>
      <span class="source-empty-state__copy">Try a different plan number or unit name.</span>
    </div>

    <!-- Truly empty -->
    <div v-else class="source-empty-state">
      <div class="source-empty-state__icon"><i class="ri-inbox-line"></i></div>
      <strong class="source-empty-state__title">No source plans found</strong>
      <span class="source-empty-state__copy">{{ emptyText }}</span>
    </div>

    <!-- Summary footer -->
    <div v-if="plans.length" class="source-summary-bar">
      <span><strong>{{ plans.length }}</strong> {{ countLabel }}{{ plans.length === 1 ? "" : "s" }}</span>
      <span class="source-summary-bar__sep"></span>
      <span><strong>{{ totalItemsCount.toLocaleString() }}</strong> total items</span>
      <span class="source-summary-bar__sep"></span>
      <span><strong>{{ formatCurrency(totalAbc) }}</strong> total ABC</span>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    plans: { type: Array, default: () => [] },
    title: { type: String, default: "Source PPMPs/SPPs" },
    countLabel: { type: String, default: "plan" },
    emptyText: { type: String, default: "No source PPMPs/SPPs found." },
  },
  emits: ["view", "print"],
  data() {
    return {
      searchQuery: "",
    };
  },
  computed: {
    filteredPlans() {
      if (!this.searchQuery.trim()) {
        return this.plans;
      }

      const query = this.searchQuery.trim().toLowerCase();

      return this.plans.filter((plan) => {
        const code = String(plan.ppmp_no || plan.code || "").toLowerCase();
        const unit = String(this.sourceLabel(plan.unit) || "").toLowerCase();

        return code.includes(query) || unit.includes(query);
      });
    },
    totalItemsCount() {
      return this.plans.reduce((sum, plan) => sum + Number(plan.items_count || 0), 0);
    },
    totalAbc() {
      return this.plans.reduce(
        (sum, plan) => sum + Number(plan.total_abc || plan.total_amount || plan.estimated_budget || 0),
        0
      );
    },
  },
  methods: {
    formatCurrency(value) {
      return new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
      }).format(Number(value || 0));
    },
    fiscalYear(plan) {
      const value = plan.start_of_procurement_activity || plan.date || plan.created_at;

      return value ? new Date(value).getFullYear() : "";
    },
    planStatus(plan) {
      return plan.ppmp_status || plan.approval_status || plan.status?.name || "Pending";
    },
    statusVariant(plan) {
      const status = String(this.planStatus(plan)).toLowerCase();

      if (status.includes("consolidated") || status.includes("approved")) return "success";
      if (status.includes("submitted") || status.includes("reviewed") || status.includes("for")) return "warning";
      if (status.includes("reject") || status.includes("cancel")) return "danger";

      return "secondary";
    },
    planTypeLabel(plan) {
      const type = String(plan.plan_type || plan.plan_name || plan.ppmp_no || "").toLowerCase();

      return type.includes("spp") || type.includes("supplemental") ? "SPP" : "PPMP";
    },
    planTypeVariant(plan) {
      return this.planTypeLabel(plan) === "SPP" ? "warning" : "primary";
    },
    planTypeClass(plan) {
      return this.planTypeLabel(plan) === "SPP" ? "source-item--spp" : "source-item--ppmp";
    },
    sourceLabel(value) {
      if (!value) return "";
      if (typeof value === "object") return String(value.name ?? value.label ?? value.short ?? value.value ?? "");

      return String(value);
    },
  },
};
</script>

<style scoped>
.plan-source-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

/* ── Header ── */
.source-list-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
}

.source-list-header__left {
  display: flex;
  align-items: center;
  gap: 8px;
}

.source-count-pill {
  display: inline-flex;
  align-items: center;
  padding: 2px 9px;
  border: 1px solid var(--ppmp-border, #e9ebec);
  border-radius: 999px;
  background: var(--ppmp-surface-soft, #f8fafc);
  color: var(--ppmp-muted, #6c757d);
  font-size: 11px;
  font-weight: 700;
}

/* ── Search ── */
.source-search-wrap {
  position: relative;
  display: flex;
  align-items: center;
}

.source-search-wrap__icon {
  position: absolute;
  left: 10px;
  color: var(--ppmp-muted, #94a3b8);
  font-size: 15px;
  pointer-events: none;
}

.source-search-input {
  width: 100%;
  height: 36px;
  padding: 0 32px 0 32px;
  border: 1px solid var(--ppmp-border, #e9ebec);
  border-radius: 8px;
  background: var(--ppmp-surface, #ffffff);
  color: var(--ppmp-text, #212529);
  font-size: 13px;
  outline: none;
  transition: border-color 0.15s ease, box-shadow 0.15s ease;
}

.source-search-input:focus {
  border-color: rgba(64, 81, 137, 0.4);
  box-shadow: 0 0 0 3px rgba(64, 81, 137, 0.08);
}

.source-search-clear {
  position: absolute;
  right: 8px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 20px;
  height: 20px;
  border: 0;
  border-radius: 4px;
  background: transparent;
  color: var(--ppmp-muted, #94a3b8);
  font-size: 14px;
  cursor: pointer;
}

.source-search-clear:hover {
  background: var(--ppmp-surface-soft, #f1f5f9);
}

/* ── List ── */
.source-list {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

/* ── List item ── */
.source-list-item {
  display: grid;
  grid-template-columns: 1fr auto auto auto;
  align-items: center;
  gap: 12px;
  padding: 12px 14px;
  border: 1px solid var(--ppmp-border, #e9ebec);
  border-left-width: 3px;
  border-radius: 10px;
  background: var(--ppmp-surface, #ffffff);
  transition: box-shadow 0.12s ease;
}

.source-list-item:hover {
  box-shadow: 0 3px 10px rgba(15, 23, 42, 0.07);
}

.source-list-item.source-item--ppmp {
  border-left-color: #405189;
}

.source-list-item.source-item--spp {
  border-left-color: #f59e0b;
}

/* Identity column */
.source-list-item__identity {
  display: flex;
  flex-direction: column;
  gap: 2px;
  min-width: 0;
}

.source-list-item__code {
  color: var(--ppmp-primary, #405189);
  font-size: 13px;
  font-weight: 800;
  line-height: 1.2;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.source-list-item__unit {
  color: var(--ppmp-text, #374151);
  font-size: 12px;
  font-weight: 600;
  line-height: 1.3;
}

.source-list-item__meta {
  color: var(--ppmp-muted, #6c757d);
  font-size: 11px;
  font-weight: 500;
  line-height: 1.3;
}

/* Metrics column */
.source-list-item__metrics {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 5px;
}

.source-metric-chip {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 3px 8px;
  border: 1px solid var(--ppmp-border, #e9ebec);
  border-radius: 999px;
  background: var(--ppmp-surface-soft, #f8fafc);
  color: var(--ppmp-muted, #6c757d);
  font-size: 11px;
  font-weight: 700;
  white-space: nowrap;
}

.source-metric-chip i {
  font-size: 12px;
}

.source-metric-chip--money {
  color: #16a34a;
  border-color: rgba(22, 163, 74, 0.2);
  background: rgba(220, 252, 231, 0.5);
}

/* Badges column */
.source-list-item__badges {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 5px;
}

/* Actions column */
.source-list-item__actions {
  display: flex;
  align-items: center;
  gap: 5px;
  flex-shrink: 0;
}

/* ── Empty state ── */
.source-empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
  padding: 36px 16px;
  border: 1px dashed var(--ppmp-border, #d1d5db);
  border-radius: 10px;
  background: var(--ppmp-surface-soft, #f9fafb);
  text-align: center;
}

.source-empty-state__icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 46px;
  height: 46px;
  border-radius: 12px;
  background: var(--ppmp-surface, #ffffff);
  border: 1px solid var(--ppmp-border, #e9ebec);
  color: #94a3b8;
  font-size: 21px;
}

.source-empty-state__title {
  color: var(--ppmp-text, #374151);
  font-size: 13px;
  font-weight: 700;
}

.source-empty-state__copy {
  max-width: 320px;
  color: var(--ppmp-muted, #6b7280);
  font-size: 12px;
  line-height: 1.5;
}

/* ── Summary bar ── */
.source-summary-bar {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 10px;
  padding: 9px 14px;
  border: 1px solid var(--ppmp-border, #e9ebec);
  border-radius: 8px;
  background: var(--ppmp-surface-soft, #f8fafc);
  color: var(--ppmp-muted, #6c757d);
  font-size: 12px;
}

.source-summary-bar strong {
  color: var(--ppmp-text, #212529);
  font-weight: 800;
}

.source-summary-bar__sep {
  display: inline-block;
  width: 1px;
  height: 14px;
  background: var(--ppmp-border, #d1d5db);
  flex-shrink: 0;
}

/* ── Dark mode ── */
[data-bs-theme="dark"] .source-list-item {
  background: rgba(27, 34, 48, 0.92);
}

[data-bs-theme="dark"] .source-metric-chip {
  background: rgba(35, 44, 58, 0.8);
}

[data-bs-theme="dark"] .source-empty-state {
  background: rgba(35, 44, 58, 0.5);
}

[data-bs-theme="dark"] .source-summary-bar {
  background: rgba(35, 44, 58, 0.7);
}

[data-bs-theme="dark"] .source-search-input {
  background: rgba(27, 34, 48, 0.9);
  color: #e2e8f0;
}

/* ── Mobile ── */
@media (max-width: 768px) {
  .source-list-item {
    grid-template-columns: 1fr auto;
    grid-template-rows: auto auto;
  }

  .source-list-item__metrics {
    display: none;
  }

  .source-list-item__badges {
    grid-row: 2;
    grid-column: 1;
  }

  .source-list-item__actions {
    grid-row: 1 / 3;
    grid-column: 2;
    flex-direction: column;
    align-self: center;
  }
}
</style>
