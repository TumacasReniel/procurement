<template>
  <div class="po-tab-shell inspection-tab">

    <section class="inspection-note-card">
      <div class="inspection-note-icon">
        <i class="ri-shield-check-line"></i>
      </div>
      <div>
        <h4>Generated IAR reports</h4>
        <p>
          This section shows the IAR reports already generated for this purchase order. Use the button on the right
          to create a new report for each delivery batch, print it, update its inspection status, and revert a
          completed report if the PO is moved back to Conformed.
        </p>
      </div>
    </section>

    <section class="inspection-table-card">
      <div class="inspection-table-header">
        <div>
          <span class="inspection-eyebrow">Generated Reports</span>
          <h4>List of Generated IAR Reports</h4>
          <p>Review each generated report here, print it, complete it when ready, or revert it back to Generated when allowed.</p>
        </div>

        <button
          v-if="canGenerateIarReport"
          type="button"
          class="inspection-header-action"
          @click="$emit('open-iar')"
        >
          <i class="ri-add-line"></i>
          Generate New IAR Report
        </button>
      </div>

      <div v-if="iarReports.length" class="table-responsive">
        <table class="inspection-table">
          <thead>
            <tr>
              <th class="text-center">#</th>
              <th>IAR Report</th>
              <th class="text-center">Generated On</th>
              <th class="text-center">Last Updated</th>
              <th class="text-center">Selected Items</th>
              <th class="text-center">Report Status</th>
              <th class="text-center">Action</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(report, index) in iarReports" :key="report.id || index">
              <td class="text-center inspection-table-number">{{ index + 1 }}</td>
              <td class="inspection-table-item">
                <div class="inspection-item-no">{{ report.code || "IAR" }}</div>
                <div class="inspection-item-description">
                  {{ report.description }}
                </div>
              </td>
              <td class="text-center">
                {{ formatDateTime(report.created_at) }}
              </td>
              <td class="text-center">
                {{ formatDateTime(report.updated_at) }}
              </td>
              <td class="text-center">{{ report.selected_items_count }}</td>
              <td class="text-center">
                <span :class="badgeClass(report.status_variant)">
                  {{ report.status_label }}
                </span>
              </td>
              <td class="text-center">
                <div class="inspection-row-actions">
                  <button
                    v-if="report.can_edit"
                    type="button"
                    class="inspection-table-action inspection-table-action--icon inspection-table-action--info"
                    :disabled="processingIarId === report.id"
                    @click="$emit('edit-iar', report)"
                    v-b-tooltip.hover
                    title="Edit generated IAR report"
                    aria-label="Edit generated IAR report"
                  >
                    <i class="ri-pencil-line"></i>
                  </button>
                  <button
                    v-if="report.can_update_status"
                    type="button"
                    class="inspection-table-action inspection-table-action--icon inspection-table-action--primary"
                    :disabled="processingIarId === report.id"
                    @click="$emit('inspect-iar', report)"
                    v-b-tooltip.hover
                    title="Mark as Inspected/Completed"
                    aria-label="Mark as Inspected/Completed"
                  >
                    <i :class="processingIarId === report.id ? 'ri-loader-4-line' : 'ri-shield-check-line'"></i>
                  </button>
                  <button
                    v-if="report.can_revert_status"
                    type="button"
                    class="inspection-table-action inspection-table-action--icon inspection-table-action--warning"
                    :disabled="processingIarId === report.id"
                    @click="$emit('revert-iar', report)"
                    v-b-tooltip.hover
                    title="Revert to Generated"
                    aria-label="Revert to Generated"
                  >
                    <i :class="processingIarId === report.id ? 'ri-loader-4-line' : 'ri-arrow-go-back-line'"></i>
                  </button>
                  <button
                    v-if="report.can_print"
                    type="button"
                    class="inspection-table-action inspection-table-action--icon inspection-table-action--secondary"
                    @click="printIarReport(report)"
                    v-b-tooltip.hover
                    title="Print IAR report"
                    aria-label="Print IAR report"
                  >
                    <i class="ri-printer-line"></i>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-else class="inspection-empty-state">
        <div class="inspection-empty-icon">
          <i class="ri-inbox-archive-line"></i>
        </div>
        <h4>No generated IAR reports yet</h4>
        <p>Generate the first IAR report from the delivered items, and it will appear in this list.</p>
      </div>
    </section>
  </div>
</template>

<script>
export default {
  emits: ["open-iar", "edit-iar", "print-iar", "inspect-iar", "revert-iar"],
  props: {
    purchaseOrder: {
      type: Object,
      default: null,
    },
    deliveredMonitoringItems: {
      type: Array,
      default: () => [],
    },
    deliverySummary: {
      type: Object,
      default: () => ({}),
    },
    canGenerateIarReport: {
      type: Boolean,
      default: false,
    },
    canPrintIarReport: {
      type: Boolean,
      default: false,
    },
    processingIarId: {
      type: [Number, String],
      default: null,
    },
  },
  computed: {
    iarReports() {
      const reports = Array.isArray(this.purchaseOrder?.iars)
        ? this.purchaseOrder.iars
        : (this.purchaseOrder?.iar ? [this.purchaseOrder.iar] : []);

      return reports.map((report, index) => ({
        ...report,
        id: report?.id ?? index,
        code: report?.code || "IAR",
        can_print: Boolean(report?.code && this.purchaseOrder?.id),
        created_at: report?.created_at,
        updated_at: report?.updated_at,
        selected_items_count: Array.isArray(report?.selected_item_ids)
          ? report.selected_item_ids.length
          : 0,
        delivered_quantity_total: Array.isArray(report?.selected_item_ids)
          ? report.selected_item_ids.reduce((sum, item) => sum + (Number(item?.delivered_quantity) || 0), 0)
          : 0,
        can_edit: ["Generated", "Pending"].includes(String(report?.status?.name || "").trim()),
        can_update_status: ["Generated", "Pending"].includes(String(report?.status?.name || "").trim()),
        can_revert_status:
          this.normalizedPurchaseOrderStatus === "Conformed"
          && String(report?.status?.name || "").trim() === "Completed",
        status_label: this.resolveReportStatusLabel(report),
        status_variant: this.resolveReportStatusVariant(report),
        description: this.resolveReportDescription(report),
      }));
    },
    latestIarCode() {
      return this.iarReports[0]?.code || "To be generated";
    },
    normalizedPurchaseOrderStatus() {
      const statusName = String(this.purchaseOrder?.status?.name || "").trim();

      if (
        ["Partially Delivered/For Inspection", "PO Delivered/For Inspection", "PO Partially Delivered/For Inspection"].includes(statusName)
      ) {
        return "Delivered/For Inspection";
      }

      if (
        ["PO Conformed", "Partially Conformed", "PO Partially Conformed"].includes(statusName)
      ) {
        return "Conformed";
      }

      return statusName;
    },
  },
  methods: {
    formatQuantity(value) {
      const numericValue = Number(value ?? 0);

      if (!Number.isFinite(numericValue)) {
        return "-";
      }

      return Number.isInteger(numericValue)
        ? numericValue.toLocaleString("en-PH")
        : numericValue.toLocaleString("en-PH", {
            minimumFractionDigits: 0,
            maximumFractionDigits: 4,
          });
    },
    badgeClass(variant) {
      return [
        "inspection-pill",
        `inspection-pill--${variant || "secondary"}`,
      ];
    },
    formatDateTime(value) {
      if (!value) {
        return "-";
      }

      const parsedDate = new Date(value);

      if (Number.isNaN(parsedDate.getTime())) {
        return value;
      }

      return new Intl.DateTimeFormat("en-PH", {
        year: "numeric",
        month: "short",
        day: "numeric",
        hour: "numeric",
        minute: "2-digit",
      }).format(parsedDate);
    },
    resolveReportStatusLabel(report) {
      const statusName = String(report?.status?.name || "").trim();

      if (statusName === "Completed") {
        return "Inspected/Completed";
      }

      if (["Generated", "Pending"].includes(statusName)) {
        return "Generated";
      }

      return statusName || "Generated";
    },
    resolveReportStatusVariant(report) {
      const label = this.resolveReportStatusLabel(report);

      if (label === "Inspected/Completed") {
        return "success";
      }

      return "secondary";
    },
    resolveReportDescription(report) {
      const count = Array.isArray(report?.selected_item_ids) ? report.selected_item_ids.length : 0;
      const quantity = Number(report?.delivered_quantity_total || 0);

      if (!count) {
        return "No delivered items selected yet.";
      }

      return `${count} delivered item${count === 1 ? "" : "s"} with ${this.formatQuantity(quantity)} total quantity included in this report.`;
    },
    printIarReport(report) {
      if (!report?.can_print || !this.purchaseOrder?.id) {
        return;
      }

      this.$emit("print-iar", report);
    },
  },
};
</script>

<style scoped>
.po-tab-shell {
  --po-table-header-bg: #4a5b93;
  display: grid;
  gap: 1.25rem;
  font-size: 0.92rem;
}

.inspection-eyebrow {
  display: inline-flex;
  margin-bottom: 0.55rem;
  padding: 0.28rem 0.75rem;
  border-radius: 999px;
  background: rgba(74, 91, 147, 0.12);
  color: #4a5b93;
  font-size: 0.68rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.inspection-note-card,
.inspection-table-card {
  border-radius: 24px;
  background: #ffffff;
  border: 1px solid rgba(226, 232, 240, 0.94);
  box-shadow: 0 18px 34px rgba(15, 23, 42, 0.07);
}

.inspection-note-card {
  display: flex;
  gap: 1rem;
  align-items: flex-start;
  padding: 1.3rem 1.4rem;
  background: linear-gradient(135deg, #f8fbff, #ffffff);
}

.inspection-note-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 42px;
  height: 42px;
  border-radius: 16px;
  background: rgba(37, 99, 235, 0.1);
  color: #1d4ed8;
  font-size: 1.05rem;
  flex-shrink: 0;
}

.inspection-note-card h4,
.inspection-table-header h4 {
  margin: 0;
  color: #0f172a;
  font-size: 0.96rem;
  font-weight: 800;
}

.inspection-note-card p,
.inspection-table-header p {
  margin: 0.3rem 0 0;
  color: #64748b;
  font-size: 0.84rem;
  line-height: 1.6;
}

.inspection-table-card {
  overflow: hidden;
}

.inspection-table-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  gap: 1rem;
  flex-wrap: wrap;
  padding: 1.4rem 1.45rem 1rem;
}

.inspection-header-action {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.45rem;
  min-height: 42px;
  padding: 0.75rem 1rem;
  border: 0;
  border-radius: 999px;
  background: linear-gradient(135deg, #1d4ed8, #2563eb);
  color: #ffffff;
  font-size: 0.76rem;
  font-weight: 800;
  letter-spacing: 0.02em;
  box-shadow: 0 12px 24px rgba(37, 99, 235, 0.2);
  transition: transform 0.2s ease, box-shadow 0.2s ease, opacity 0.2s ease;
}

.inspection-header-action:not(:disabled):hover {
  transform: translateY(-1px);
  box-shadow: 0 16px 28px rgba(37, 99, 235, 0.24);
}

.inspection-header-action:disabled {
  cursor: not-allowed;
  opacity: 0.55;
  box-shadow: none;
}

.inspection-table {
  width: 100%;
  border-collapse: collapse;
}

.inspection-table thead {
  background: var(--po-table-header-bg);
  color: #ffffff;
}

.inspection-table th {
  padding: 1rem 0.8rem;
  font-size: 0.7rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.inspection-table td {
  padding: 1rem 0.8rem;
  border-bottom: 1px solid rgba(226, 232, 240, 0.84);
  vertical-align: top;
  font-size: 0.82rem;
}

.inspection-table tbody tr:hover {
  background: rgba(37, 99, 235, 0.03);
}

.inspection-table-number {
  color: #1d4ed8;
  font-weight: 800;
}

.inspection-table-item {
  min-width: 290px;
}

.inspection-item-no {
  display: inline-flex;
  margin-bottom: 0.45rem;
  padding: 0.24rem 0.65rem;
  border-radius: 999px;
  background: rgba(37, 99, 235, 0.08);
  color: #1d4ed8;
  font-size: 0.68rem;
  font-weight: 800;
}

.inspection-item-description {
  color: #0f172a;
  font-weight: 600;
  font-size: 0.84rem;
  line-height: 1.6;
}

.inspection-row-actions {
  display: inline-flex;
  justify-content: center;
  gap: 0.5rem;
  flex-wrap: nowrap;
  white-space: nowrap;
}

.inspection-table-action {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 80px;
  padding: 0.45rem 0.75rem;
  border-radius: 999px;
  border: 0;
  font-size: 0.7rem;
  font-weight: 800;
  transition: transform 0.2s ease, opacity 0.2s ease;
}

.inspection-table-action:not(:disabled):hover {
  transform: translateY(-1px);
}

.inspection-table-action:disabled {
  opacity: 0.55;
  cursor: not-allowed;
}

.inspection-table-action--icon {
  min-width: 42px;
  width: 42px;
  height: 42px;
  padding: 0;
  border-radius: 14px;
}

.inspection-table-action--icon i {
  font-size: 1rem;
}

.inspection-table-action--primary {
  background: rgba(37, 99, 235, 0.12);
  color: #1d4ed8;
}

.inspection-table-action--secondary {
  background: rgba(15, 23, 42, 0.08);
  color: #0f172a;
}

.inspection-table-action--info {
  background: rgba(14, 165, 233, 0.12);
  color: #0369a1;
}

.inspection-table-action--warning {
  background: rgba(245, 158, 11, 0.16);
  color: #b45309;
}

.inspection-pill {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 116px;
  padding: 0.42rem 0.72rem;
  border-radius: 999px;
  font-size: 0.72rem;
  font-weight: 800;
}

.inspection-pill--success {
  background: rgba(16, 185, 129, 0.12);
  color: #047857;
}

.inspection-pill--warning {
  background: rgba(245, 158, 11, 0.15);
  color: #b45309;
}

.inspection-pill--danger {
  background: rgba(239, 68, 68, 0.12);
  color: #b91c1c;
}

.inspection-pill--info {
  background: rgba(14, 165, 233, 0.12);
  color: #0369a1;
}

.inspection-pill--secondary {
  background: rgba(148, 163, 184, 0.18);
  color: #475569;
}

.inspection-empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.7rem;
  min-height: 280px;
  padding: 2rem 1.5rem;
  text-align: center;
}

.inspection-empty-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 72px;
  height: 72px;
  border-radius: 22px;
  background: linear-gradient(135deg, #eff6ff, #dbeafe);
  color: #1d4ed8;
  font-size: 1.8rem;
}

.inspection-empty-state h4 {
  margin: 0;
  color: #0f172a;
  font-size: 0.98rem;
  font-weight: 800;
}

.inspection-empty-state p {
  max-width: 460px;
  margin: 0;
  color: #64748b;
  font-size: 0.84rem;
  line-height: 1.6;
}

@media (max-width: 767px) {
  .inspection-note-card,
  .inspection-table-header {
    padding: 1.2rem;
  }

  .inspection-table-item {
    min-width: 230px;
  }
}
</style>
