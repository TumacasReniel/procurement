<template>
  <b-card no-body class="border-0 shadow-sm po-status-flow-card">
    <div class="po-status-flow-header">
      <div class="d-flex align-items-start gap-3">
        <div class="po-status-flow-icon">
          <i class="ri-flow-chart"></i>
        </div>
        <div>
          <div class="po-status-flow-eyebrow">Purchase Order Status Flow</div>
          <h4 class="po-status-flow-title mb-1">
            {{ purchaseOrder?.code || "Purchase Order" }}
          </h4>
          <p class="po-status-flow-subtitle mb-0">
            {{ statusSummary }}
          </p>
        </div>
      </div>

      <div class="d-flex flex-column align-items-start align-items-lg-end gap-2">
        <div class="d-flex align-items-center gap-2">
          <b-badge
            pill
            class="po-status-flow-badge"
            :class="purchaseOrder?.status?.bg || 'bg-secondary'"
          >
            {{ purchaseOrder?.status?.name || "N/A" }}
          </b-badge>
          <b-button
            type="button"
            size="sm"
            variant="light"
            class="po-status-flow-toggle"
            :aria-expanded="String(!isCollapsed)"
            @click="toggleCollapsed"
          >
            <i :class="isCollapsed ? 'ri-arrow-down-s-line' : 'ri-arrow-up-s-line'"></i>
            <span>{{ isCollapsed ? "Show" : "Hide" }}</span>
          </b-button>
        </div>
        <span v-if="statusHint" class="po-status-flow-hint text-info">
          {{ statusHint }}
        </span>
      </div>
    </div>

    <b-collapse v-model="isExpanded">
      <div class="po-status-flow-track">
        <div
          v-for="(step, index) in statusFlow"
          :key="step.key"
          class="po-status-flow-step-wrapper"
        >
          <div
            v-if="index > 0"
            class="po-status-flow-line"
            :class="{
              completed: statusFlow[index - 1].isPast,
              active: statusFlow[index - 1].isPast && step.isCurrent,
            }"
          >
            <i class="ri-arrow-right-s-line"></i>
          </div>

          <div
            class="po-status-flow-step"
            :class="{
              completed: step.isPast,
              active: step.isCurrent,
              pending: !step.isPast && !step.isCurrent,
            }"
          >
            <div class="po-status-flow-dot">
              <i v-if="step.isPast" class="ri-check-line"></i>
              <i v-else-if="step.isCurrent" class="ri-star-fill"></i>
              <i v-else class="ri-circle-line"></i>
            </div>
            <div class="po-status-flow-label">{{ step.name }}</div>
            <div
              v-if="step.updatedAt || step.isCurrent || step.isPast"
              class="po-status-flow-time"
              :class="{ pending: !step.updatedAt }"
            >
              <template v-if="step.updatedAt">
                <span>{{ formatTimelineDate(step.updatedAt) }}</span>
                <span>{{ formatTimelineTime(step.updatedAt) }}</span>
              </template>
              <template v-else>
                In progress
              </template>
            </div>
            <div class="po-status-flow-note">
              {{ step.note }}
            </div>
          </div>
        </div>
      </div>

      <div class="po-status-flow-footer">
        <span class="po-status-flow-chip">
          Remaining deliveries: {{ deliverySummary.needs_delivery_items || 0 }}
        </span>
        <span class="po-status-flow-chip">
          Pending IARs: {{ pendingIarReportsCount }}
        </span>
        <span class="po-status-flow-chip">
          Target delivery: {{ formatTimelineDate(purchaseOrder?.date_of_delivery) }}
        </span>
      </div>
    </b-collapse>
  </b-card>
</template>

<script>
export default {
  props: {
    purchaseOrder: {
      type: Object,
      default: null,
    },
  },
  data() {
    return {
      isExpanded: true,
    };
  },
  computed: {
    isCollapsed() {
      return !this.isExpanded;
    },
    collapseStorageKey() {
      return `po-status-flow-expanded:${this.purchaseOrder?.id || this.purchaseOrder?.code || "default"}`;
    },
    normalizedPurchaseOrderStatus() {
      return this.normalizePurchaseOrderStatus(this.purchaseOrder?.status?.name);
    },
    deliverySummary() {
      return this.purchaseOrder?.delivery_monitoring_summary || {
        needs_delivery_items: 0,
        total_items: 0,
        delivered_items: 0,
        partial_items: 0,
      };
    },
    deliveryMonitoringItems() {
      return Array.isArray(this.purchaseOrder?.delivery_monitoring_items)
        ? this.purchaseOrder.delivery_monitoring_items
        : [];
    },
    hasReceivedDeliveries() {
      return this.deliveryMonitoringItems.some((item) => Number(item?.delivered_quantity || 0) > 0);
    },
    allItemsDelivered() {
      return (
        Number(this.deliverySummary.total_items || 0) > 0
        && Number(this.deliverySummary.needs_delivery_items || 0) === 0
      );
    },
    iarReports() {
      return Array.isArray(this.purchaseOrder?.iars)
        ? this.purchaseOrder.iars
        : (this.purchaseOrder?.iar ? [this.purchaseOrder.iar] : []);
    },
    hasIarReports() {
      return this.iarReports.length > 0;
    },
    pendingIarReportsCount() {
      return this.iarReports.filter(
        (report) => String(report?.status?.name || "").trim() !== "Completed"
      ).length;
    },
    allIarReportsCompleted() {
      return this.hasIarReports && this.pendingIarReportsCount === 0;
    },
    deliveredAt() {
      if (this.purchaseOrder?.resolved_actual_delivery_date) {
        return this.purchaseOrder.resolved_actual_delivery_date;
      }

      if (this.purchaseOrder?.actual_delivery_date) {
        return this.purchaseOrder.actual_delivery_date;
      }

      const latestDeliveryItem = [...this.deliveryMonitoringItems]
        .filter((item) => item?.actual_delivery_date)
        .sort((left, right) => {
          return new Date(right.actual_delivery_date).getTime() - new Date(left.actual_delivery_date).getTime();
        })[0];

      return latestDeliveryItem?.actual_delivery_date || null;
    },
    inspectionUpdatedAt() {
      const latestIar = [...this.iarReports]
        .filter((report) => report?.updated_at || report?.created_at)
        .sort((left, right) => {
          const leftTime = new Date(left?.updated_at || left?.created_at || 0).getTime();
          const rightTime = new Date(right?.updated_at || right?.created_at || 0).getTime();

          return rightTime - leftTime;
        })[0];

      return latestIar?.updated_at || latestIar?.created_at || null;
    },
    completedAt() {
      if (this.normalizedPurchaseOrderStatus !== "Completed") {
        return null;
      }

      const latestCompletedIar = [...this.iarReports]
        .filter((report) => String(report?.status?.name || "").trim() === "Completed")
        .sort((left, right) => {
          const leftTime = new Date(left?.updated_at || left?.created_at || 0).getTime();
          const rightTime = new Date(right?.updated_at || right?.created_at || 0).getTime();

          return rightTime - leftTime;
        })[0];

      return (
        this.purchaseOrder?.updated_at
        || latestCompletedIar?.updated_at
        || latestCompletedIar?.created_at
        || null
      );
    },
    currentStepKey() {
      const status = this.normalizedPurchaseOrderStatus;

      if (status === "Not Conformed") {
        return "Issued";
      }

      if (status === "Completed") {
        return "Completed";
      }

      if (this.allItemsDelivered) {
        return "Inspection";
      }

      const stepMap = {
        Created: "Created",
        Issued: "Issued",
        Conformed: "Conformed",
      };

      return stepMap[status] || "";
    },
    statusFlow() {
      const steps = [
        {
          key: "Created",
          name: "PO Created",
          updatedAt: this.purchaseOrder?.created_at || this.purchaseOrder?.po_date || null,
          note: "Purchase order has been created and recorded.",
        },
        {
          key: "Issued",
          name: "PO Issued",
          updatedAt: this.purchaseOrder?.released_at || null,
          note: this.normalizedPurchaseOrderStatus === "Not Conformed"
            ? "Supplier marked this issued purchase order as not conformed."
            : "Waiting for supplier acknowledgment and conformity.",
        },
        {
          key: "Conformed",
          name: "PO Conformed/Items For Delivery",
          updatedAt: this.purchaseOrder?.conformed_at || null,
          note: this.hasReceivedDeliveries
            ? "Supplier has conformed. Delivery receiving is in progress."
            : "Supplier has confirmed the purchase order terms.",
        },
        {
          key: "Items Delivered",
          name: "Items Delivered",
          updatedAt: this.deliveredAt,
          note: (this.deliverySummary.needs_delivery_items || 0) > 0
            ? `${this.deliverySummary.needs_delivery_items} item(s) still need delivery tracking.`
            : "Supply has received all ordered items.",
        },
        {
          key: "Inspection",
          name: "For Inspection and Acceptance",
          updatedAt: this.inspectionUpdatedAt,
          note: !this.allItemsDelivered
            ? "Inspection starts after all delivered items are received."
            : !this.hasIarReports
              ? "Generate the IAR for inspection and acceptance."
              : this.pendingIarReportsCount > 0
                ? `${this.pendingIarReportsCount} IAR report(s) still need inspection completion.`
                : "IAR inspection and acceptance are complete.",
        },
        {
          key: "Completed",
          name: "Completed",
          updatedAt: this.completedAt,
          note: this.pendingIarReportsCount > 0
            ? `${this.pendingIarReportsCount} IAR report(s) still need inspection completion.`
            : "Inspection, acceptance, and PO completion are done.",
        },
      ];

      const currentIndex = steps.findIndex((step) => step.key === this.currentStepKey);

      return steps.map((step, index) => ({
        ...step,
        isCurrent: index === currentIndex,
        isPast: currentIndex >= 0 ? index < currentIndex : false,
      }));
    },
    statusSummary() {
      if (this.normalizedPurchaseOrderStatus === "Completed") {
        return "This purchase order has completed delivery, inspection, and acceptance.";
      }

      if (this.allItemsDelivered) {
        if (!this.hasIarReports) {
          return "All items are delivered. IAR generation is the next inspection step.";
        }

        return this.pendingIarReportsCount > 0
          ? "All items are delivered. Complete the remaining IAR inspection steps."
          : "All items are delivered and accepted. The PO can proceed to completion.";
      }

      if (this.normalizedPurchaseOrderStatus === "Conformed") {
        return (this.deliverySummary.needs_delivery_items || 0) > 0
          ? "The supplier has conformed. Delivery monitoring is the next focus."
          : "Delivery records are complete. You can proceed with inspection updates.";
      }

      if (this.normalizedPurchaseOrderStatus === "Issued") {
        return "The purchase order has been issued and is waiting for supplier conformity.";
      }

      if (this.normalizedPurchaseOrderStatus === "Not Conformed") {
        return "The supplier did not conform to this purchase order. Review the next corrective action.";
      }

      return "Track each purchase order milestone from creation through completion.";
    },
    statusHint() {
      if (this.normalizedPurchaseOrderStatus === "Created") {
        return "Next: issue the purchase order";
      }

      if (this.normalizedPurchaseOrderStatus === "Issued") {
        return "Next: wait for supplier conformity";
      }

      if (this.normalizedPurchaseOrderStatus === "Conformed") {
        return "Next: receive delivered items";
      }

      if (this.allItemsDelivered) {
        return "Next: complete inspection and acceptance";
      }

      if (this.normalizedPurchaseOrderStatus === "Completed") {
        return "Process complete";
      }

      if (this.normalizedPurchaseOrderStatus === "Not Conformed") {
        return "Requires review";
      }

      return "";
    },
  },
  mounted() {
    const storedValue = localStorage.getItem(this.collapseStorageKey);

    if (storedValue !== null) {
      this.isExpanded = storedValue === "true";
    }
  },
  methods: {
    toggleCollapsed() {
      this.isExpanded = !this.isExpanded;
      localStorage.setItem(this.collapseStorageKey, String(this.isExpanded));
    },
    normalizePurchaseOrderStatus(statusName) {
      const normalized = String(statusName || "").trim();

      if (!normalized) {
        return "";
      }

      if (["PO Created", "Partially Created", "PO Partially Created"].includes(normalized)) {
        return "Created";
      }

      if (["PO Issued", "Partially Issued", "PO Partially Issued"].includes(normalized)) {
        return "Issued";
      }

      if (["PO Conformed", "Partially Conformed", "PO Partially Conformed"].includes(normalized)) {
        return "Conformed";
      }

      if (
        [
          "Items Delivered",
          "PO Items Delivered",
          "Partially Delivered/For Inspection",
          "PO Delivered/For Inspection",
          "PO Partially Delivered/For Inspection",
          "Items Partially Delivered",
          "PO Items Partially Delivered",
          "Delivered/For Inspection",
          "Delivered",
        ].includes(normalized)
      ) {
        return "Items Delivered";
      }

      if (["Not Conformed", "PO Not Conformed"].includes(normalized)) {
        return "Not Conformed";
      }

      return normalized;
    },
    formatTimelineDate(value) {
      if (!value) {
        return "Not set";
      }

      const parsedDate = new Date(value);

      if (Number.isNaN(parsedDate.getTime())) {
        return value;
      }

      return parsedDate.toLocaleDateString("en-PH", {
        month: "short",
        day: "numeric",
        year: "numeric",
      });
    },
    formatTimelineTime(value) {
      if (!value) {
        return "";
      }

      const parsedDate = new Date(value);

      if (Number.isNaN(parsedDate.getTime())) {
        return "";
      }

      return parsedDate.toLocaleTimeString("en-PH", {
        hour: "numeric",
        minute: "2-digit",
      });
    },
  },
};
</script>

<style scoped>
.po-status-flow-card {
  overflow: hidden;
  background:
    radial-gradient(circle at top left, rgba(14, 165, 233, 0.08), transparent 28%),
    radial-gradient(circle at top right, rgba(37, 99, 235, 0.08), transparent 24%),
    linear-gradient(135deg, var(--po-detail-surface, #ffffff), var(--po-detail-surface-soft, #f8fbff));
}

.po-status-flow-header {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
  padding: 1rem 1.1rem 0.9rem;
  border-bottom: 1px solid var(--po-detail-border, rgba(148, 163, 184, 0.22));
}

.po-status-flow-icon {
  width: 44px;
  height: 44px;
  border-radius: 14px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #dbeafe, #eff6ff);
  color: #1d4ed8;
  font-size: 1.15rem;
  flex-shrink: 0;
}

.po-status-flow-eyebrow {
  font-size: 0.72rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: #2563eb;
  margin-bottom: 0.2rem;
}

.po-status-flow-title {
  color: var(--po-detail-text, #1e293b);
}

.po-status-flow-subtitle,
.po-status-flow-hint {
  color: var(--po-detail-muted, #64748b);
  font-size: 0.85rem;
  line-height: 1.5;
}

.po-status-flow-badge {
  font-size: 0.78rem;
}

.po-status-flow-toggle {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  border: 1px solid var(--po-detail-border, rgba(148, 163, 184, 0.22));
  color: var(--po-detail-text, #1e293b);
  font-weight: 700;
  line-height: 1;
  padding: 0.35rem 0.55rem;
}

.po-status-flow-toggle i {
  font-size: 1rem;
}

.po-status-flow-track {
  display: flex;
  align-items: stretch;
  gap: 0;
  overflow-x: auto;
  padding: 1rem 1.1rem 1.05rem;
  scrollbar-width: thin;
}

.po-status-flow-step-wrapper {
  display: flex;
  align-items: center;
}

.po-status-flow-line {
  display: flex;
  align-items: center;
  justify-content: center;
  min-width: 18px;
  color: rgba(148, 163, 184, 0.4);
  font-size: 1.1rem;
}

.po-status-flow-line.completed {
  color: #22c55e;
}

.po-status-flow-line.active {
  color: #f59e0b;
}

.po-status-flow-step {
  min-width: 180px;
  max-width: 180px;
  min-height: 178px;
  border-radius: 18px;
  border: 1px solid var(--po-detail-border, rgba(148, 163, 184, 0.22));
  background: rgba(255, 255, 255, 0.72);
  padding: 0.85rem 0.8rem;
  display: flex;
  flex-direction: column;
  gap: 0.45rem;
  box-shadow: 0 12px 24px rgba(15, 23, 42, 0.05);
}

.po-status-flow-step.completed {
  background: linear-gradient(135deg, rgba(220, 252, 231, 0.95), rgba(240, 253, 244, 0.95));
  border-color: rgba(34, 197, 94, 0.28);
}

.po-status-flow-step.active {
  background: linear-gradient(135deg, rgba(219, 234, 254, 0.96), rgba(239, 246, 255, 0.98));
  border-color: rgba(59, 130, 246, 0.3);
  box-shadow: 0 16px 30px rgba(37, 99, 235, 0.12);
}

.po-status-flow-step.pending {
  background: rgba(248, 250, 252, 0.92);
}

.po-status-flow-dot {
  width: 34px;
  height: 34px;
  border-radius: 999px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: rgba(148, 163, 184, 0.14);
  color: #64748b;
  font-size: 0.95rem;
}

.po-status-flow-step.completed .po-status-flow-dot {
  background: linear-gradient(135deg, #22c55e, #16a34a);
  color: #ffffff;
}

.po-status-flow-step.active .po-status-flow-dot {
  background: linear-gradient(135deg, #3b82f6, #2563eb);
  color: #ffffff;
}

.po-status-flow-label {
  font-size: 0.86rem;
  font-weight: 800;
  color: var(--po-detail-text, #1e293b);
  line-height: 1.3;
}

.po-status-flow-time {
  display: flex;
  flex-direction: column;
  gap: 0.1rem;
  font-size: 0.72rem;
  color: var(--po-detail-muted, #64748b);
  min-height: 2.2rem;
}

.po-status-flow-time.pending {
  color: #94a3b8;
}

.po-status-flow-note {
  font-size: 0.74rem;
  line-height: 1.45;
  color: var(--po-detail-muted, #64748b);
  margin-top: auto;
}

.po-status-flow-footer {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  padding: 0 1.1rem 1rem;
}

.po-status-flow-chip {
  display: inline-flex;
  align-items: center;
  padding: 0.45rem 0.7rem;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.8);
  border: 1px solid var(--po-detail-border, rgba(148, 163, 184, 0.22));
  color: var(--po-detail-text, #1e293b);
  font-size: 0.76rem;
  font-weight: 700;
}

@media (max-width: 991.98px) {
  .po-status-flow-header {
    flex-direction: column;
  }
}

@media (max-width: 767.98px) {
  .po-status-flow-track {
    padding-inline: 0.85rem;
  }

  .po-status-flow-step {
    min-width: 164px;
    max-width: 164px;
  }

  .po-status-flow-footer {
    padding-inline: 0.85rem;
  }
}

[data-bs-theme="dark"] .po-status-flow-card {
  background:
    radial-gradient(circle at top left, rgba(59, 130, 246, 0.12), transparent 28%),
    radial-gradient(circle at top right, rgba(96, 165, 250, 0.12), transparent 24%),
    linear-gradient(135deg, rgba(27, 34, 48, 0.98), rgba(35, 44, 58, 0.98));
}

[data-bs-theme="dark"] .po-status-flow-icon {
  background: linear-gradient(135deg, rgba(30, 64, 175, 0.32), rgba(30, 41, 59, 0.92));
  color: #93c5fd;
}

[data-bs-theme="dark"] .po-status-flow-eyebrow {
  color: #93c5fd;
}

[data-bs-theme="dark"] .po-status-flow-step {
  background: rgba(27, 34, 48, 0.92);
  box-shadow: none;
}

[data-bs-theme="dark"] .po-status-flow-step.completed {
  background: linear-gradient(135deg, rgba(22, 163, 74, 0.18), rgba(21, 128, 61, 0.16));
}

[data-bs-theme="dark"] .po-status-flow-step.active {
  background: linear-gradient(135deg, rgba(30, 64, 175, 0.48), rgba(30, 41, 59, 0.96));
}

[data-bs-theme="dark"] .po-status-flow-step.pending {
  background: rgba(35, 44, 58, 0.92);
}

[data-bs-theme="dark"] .po-status-flow-dot {
  background: rgba(148, 163, 184, 0.14);
  color: #cbd5e1;
}

[data-bs-theme="dark"] .po-status-flow-line {
  color: rgba(148, 163, 184, 0.34);
}

[data-bs-theme="dark"] .po-status-flow-line.completed {
  color: #4ade80;
}

[data-bs-theme="dark"] .po-status-flow-line.active {
  color: #fbbf24;
}

[data-bs-theme="dark"] .po-status-flow-toggle {
  background: rgba(27, 34, 48, 0.94);
  color: var(--po-detail-text, #e5edf7);
}

[data-bs-theme="dark"] .po-status-flow-chip {
  background: rgba(27, 34, 48, 0.94);
  color: var(--po-detail-text, #e5edf7);
}
</style>
