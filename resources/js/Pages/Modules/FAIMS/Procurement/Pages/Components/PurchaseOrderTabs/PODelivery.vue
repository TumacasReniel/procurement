<template>
  <div class="po-tab-shell delivery-tab">
    <section class="delivery-hero">
      <div class="delivery-hero-copy">
        <span class="delivery-eyebrow">Delivery Tracking</span>
        <h3>Monitor Delivered and Pending Items</h3>
        <p>
          Check what was delivered, what is still needed, and whether the supplier delivered on time.
        </p>
      </div>

      <div class="delivery-rule-card">
        <div class="delivery-rule-icon">
          <i class="ri-timer-flash-line"></i>
        </div>
        <div>
          <span class="delivery-rule-label">Late Delivery Rule</span>
          <strong>1% deduction per day from the delivered amount</strong>
        </div>
      </div>
    </section>

    <section class="delivery-summary-grid">
      <article class="delivery-summary-card">
        <div class="delivery-summary-icon delivery-summary-icon--emerald">
          <i class="ri-checkbox-circle-line"></i>
        </div>
        <span class="delivery-summary-label">Delivered</span>
        <strong class="delivery-summary-value">{{ deliverySummary.delivered_items || 0 }}</strong>
        <small>Items fully delivered</small>
      </article>

      <article class="delivery-summary-card">
        <div class="delivery-summary-icon delivery-summary-icon--amber">
          <i class="ri-loader-4-line"></i>
        </div>
        <span class="delivery-summary-label">Partial Delivery</span>
        <strong class="delivery-summary-value">{{ deliverySummary.partial_items || 0 }}</strong>
        <small>Items with incomplete delivery</small>
      </article>

      <article class="delivery-summary-card">
        <div class="delivery-summary-icon delivery-summary-icon--blue">
          <i class="ri-truck-line"></i>
        </div>
        <span class="delivery-summary-label">Still Needed</span>
        <strong class="delivery-summary-value">{{ deliverySummary.needs_delivery_items || 0 }}</strong>
        <small>Items still waiting for delivery</small>
      </article>

      <article class="delivery-summary-card">
        <div class="delivery-summary-icon delivery-summary-icon--rose">
          <i class="ri-alarm-warning-line"></i>
        </div>
        <span class="delivery-summary-label">Late / Overdue</span>
        <strong class="delivery-summary-value">
          {{ (deliverySummary.late_items || 0) + (deliverySummary.overdue_items || 0) }}
        </strong>
        <small>Items needing attention</small>
      </article>
    </section>

    <section class="delivery-table-card">
      <div class="delivery-table-header">
        <div>
          <span class="delivery-eyebrow">Item Status Table</span>
          <h4>Delivery Progress Per Item</h4>
          <p>Use this table to review quantities, dates, on-time status, and late deductions.</p>
        </div>

      </div>

      <div class="delivery-total-strip-wrap">
        <div class="delivery-total-strip">
          <div>
            <span>Delivered Amount</span>
            <strong>{{ formatCurrency(deliverySummary.delivered_amount_total) }}</strong>
          </div>
          <div>
            <span>Late Deduction</span>
            <strong class="text-danger">{{ formatCurrency(deliverySummary.penalty_amount_total) }}</strong>
          </div>
          <div>
            <span>Final Amount</span>
            <strong class="text-success">{{ formatCurrency(deliverySummary.adjusted_amount_total) }}</strong>
          </div>
        </div>
      </div>

      <div class="table-responsive">
        <table class="delivery-table">
          <thead>
            <tr>
              <th class="text-center">#</th>
              <th>Item</th>
              <th class="text-center">Ordered</th>
              <th class="text-center">Delivered</th>
              <th class="text-center">Still Needed</th>
              <th class="text-center">Unit</th>
              <th class="text-center">Target Date</th>
              <th class="text-center">Delivered On</th>
              <th class="text-center">Delivery</th>
              <th class="text-center">On-Time</th>
              <th class="text-end">Delivered Amount</th>
              <th class="text-end">Late Deduction</th>
              <th class="text-end">Final Amount</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="(item, index) in deliveryMonitoringItems"
              :key="item.id || index"
              :class="{ 'delivery-table-row--attention': item.needs_delivery }"
            >
              <td class="text-center delivery-table-number">
               {{ item.item_no }}
              </td>
              <td class="delivery-table-item">
                <div class="delivery-item-description" v-html="item.description || '-'" />
              </td>
              <td class="text-center">{{ formatQuantity(item.ordered_quantity) }}</td>
              <td class="text-center">{{ formatQuantity(item.delivered_quantity) }}</td>
              <td class="text-center">{{ formatQuantity(item.remaining_quantity) }}</td>
              <td class="text-center">{{ item.unit || "-" }}</td>
              <td class="text-center">{{ item.expected_delivery_date_display || "-" }}</td>
              <td class="text-center">{{ item.actual_delivery_date_display || "-" }}</td>
              <td class="text-center">
                <span :class="badgeClass(item.delivery_status_variant)">
                  {{ item.delivery_status || "-" }}
                </span>
              </td>
              <td class="text-center">
                <span :class="badgeClass(item.timeliness_variant)">
                  {{ item.timeliness || "-" }}
                </span>
                <small v-if="item.timeliness_detail" class="delivery-detail-text">
                  {{ item.timeliness_detail }}
                </small>
              </td>
              <td class="text-end delivery-money">{{ formatCurrency(item.delivered_amount) }}</td>
              <td class="text-end delivery-money">
                <div>{{ formatCurrency(item.penalty_amount) }}</div>
                <small v-if="item.penalty_days > 0" class="delivery-detail-text delivery-detail-text--right">
                  1% x {{ item.penalty_days }} day<span v-if="item.penalty_days !== 1">s</span>
                </small>
              </td>
              <td class="text-end delivery-money delivery-money--final">
                {{ formatCurrency(item.adjusted_amount) }}
              </td>
            </tr>

            <tr v-if="!deliveryMonitoringItems.length">
              <td colspan="13" class="text-center py-5 text-muted">
                No delivery tracking data is available for this purchase order yet.
              </td>
            </tr>
          </tbody>
          <tfoot v-if="deliveryMonitoringItems.length">
            <tr>
              <td colspan="10" class="text-end delivery-table-footer-label">Total</td>
              <td class="text-end delivery-money">{{ formatCurrency(deliverySummary.delivered_amount_total) }}</td>
              <td class="text-end delivery-money text-danger">
                {{ formatCurrency(deliverySummary.penalty_amount_total) }}
              </td>
              <td class="text-end delivery-money delivery-money--final">
                {{ formatCurrency(deliverySummary.adjusted_amount_total) }}
              </td>
            </tr>
          </tfoot>
        </table>
      </div>
    </section>
  </div>
</template>

<script>
export default {
  emits: ["update-delivered-items"],
  props: {
    deliverySummary: {
      type: Object,
      default: () => ({}),
    },
    deliveryMonitoringItems: {
      type: Array,
      default: () => [],
    },
    canUpdateDeliveredItems: {
      type: Boolean,
      default: false,
    },
  },
  methods: {
    formatCurrency(value) {
      return new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
      }).format(Number(value || 0));
    },
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
        "delivery-pill",
        `delivery-pill--${variant || "secondary"}`,
      ];
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

.delivery-hero {
  display: flex;
  align-items: stretch;
  justify-content: space-between;
  gap: 1rem;
  flex-wrap: wrap;
  padding: 1.5rem 1.65rem;
  border-radius: 24px;
  background:
    radial-gradient(circle at top right, rgba(37, 99, 235, 0.2), transparent 32%),
    linear-gradient(135deg, #ffffff, #eef8ff 58%, #f8fbff);
  border: 1px solid rgba(191, 219, 254, 0.85);
  box-shadow: 0 22px 40px rgba(15, 23, 42, 0.08);
}

.delivery-eyebrow {
  display: inline-flex;
  margin-bottom: 0.5rem;
  padding: 0.28rem 0.75rem;
  border-radius: 999px;
  background: rgba(8, 145, 178, 0.12);
  color: #0f766e;
  font-size: 0.68rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.delivery-hero-copy h3 {
  margin: 0;
  color: #0f172a;
  font-size: 1.4rem;
  font-weight: 800;
}

.delivery-hero-copy p {
  max-width: 620px;
  margin: 0.55rem 0 0;
  color: #475569;
  font-size: 0.86rem;
  line-height: 1.6;
}

.delivery-rule-card {
  display: flex;
  gap: 0.9rem;
  align-items: center;
  min-width: min(100%, 320px);
  padding: 1rem 1.1rem;
  border-radius: 20px;
  background: linear-gradient(135deg, #fff7ed, #ffffff);
  border: 1px solid rgba(251, 191, 36, 0.4);
}

.delivery-rule-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 48px;
  height: 48px;
  border-radius: 16px;
  background: rgba(251, 146, 60, 0.16);
  color: #c2410c;
  font-size: 1.25rem;
}

.delivery-rule-label {
  display: block;
  color: #9a3412;
  font-size: 0.68rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.delivery-rule-card strong {
  color: #7c2d12;
  font-size: 0.86rem;
  line-height: 1.5;
}

.delivery-summary-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 1rem;
}

.delivery-summary-card {
  padding: 1.2rem;
  border-radius: 22px;
  background: #ffffff;
  border: 1px solid rgba(226, 232, 240, 0.95);
  box-shadow: 0 14px 28px rgba(15, 23, 42, 0.06);
}

.delivery-summary-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 46px;
  height: 46px;
  margin-bottom: 0.85rem;
  border-radius: 15px;
  font-size: 1.15rem;
}

.delivery-summary-icon--emerald {
  background: rgba(16, 185, 129, 0.15);
  color: #047857;
}

.delivery-summary-icon--amber {
  background: rgba(245, 158, 11, 0.14);
  color: #b45309;
}

.delivery-summary-icon--blue {
  background: rgba(37, 99, 235, 0.12);
  color: #1d4ed8;
}

.delivery-summary-icon--rose {
  background: rgba(244, 63, 94, 0.12);
  color: #be123c;
}

.delivery-summary-label {
  display: block;
  color: #64748b;
  font-size: 0.7rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.delivery-summary-value {
  display: block;
  margin-top: 0.35rem;
  color: #0f172a;
  font-size: 1.35rem;
  font-weight: 800;
  line-height: 1.1;
}

.delivery-summary-card small {
  display: block;
  margin-top: 0.45rem;
  color: #64748b;
  font-size: 0.8rem;
  line-height: 1.45;
}

.delivery-table-card {
  overflow: hidden;
  border-radius: 24px;
  background: #ffffff;
  border: 1px solid rgba(226, 232, 240, 0.92);
  box-shadow: 0 18px 34px rgba(15, 23, 42, 0.07);
}

.delivery-table-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  gap: 1rem;
  flex-wrap: wrap;
  padding: 1.45rem 1.45rem 1rem;
}

.delivery-table-header h4 {
  margin: 0;
  color: #0f172a;
  font-size: 0.96rem;
  font-weight: 800;
}

.delivery-table-header p {
  margin: 0.28rem 0 0;
  color: #64748b;
  font-size: 0.84rem;
  line-height: 1.55;
}

.delivery-header-action {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.45rem;
  min-height: 42px;
  padding: 0.75rem 1rem;
  border: 0;
  border-radius: 999px;
  background: linear-gradient(135deg, #4a5b93, #5a6ba6);
  color: #ffffff;
  font-size: 0.76rem;
  font-weight: 800;
  letter-spacing: 0.02em;
  box-shadow: 0 12px 24px rgba(74, 91, 147, 0.2);
  transition: transform 0.2s ease, box-shadow 0.2s ease, opacity 0.2s ease;
}

.delivery-header-action:not(:disabled):hover {
  transform: translateY(-1px);
  box-shadow: 0 16px 28px rgba(74, 91, 147, 0.24);
}

.delivery-header-action:disabled {
  cursor: not-allowed;
  opacity: 0.55;
  box-shadow: none;
}

.delivery-total-strip-wrap {
  padding: 0 1.45rem 1rem;
}

.delivery-total-strip {
  display: grid;
  grid-template-columns: repeat(3, minmax(130px, 1fr));
  gap: 0.75rem;
}

.delivery-total-strip > div {
  padding: 0.9rem 1rem;
  border-radius: 18px;
  background: linear-gradient(135deg, #f8fbff, #ffffff);
  border: 1px solid rgba(191, 219, 254, 0.9);
}

.delivery-total-strip span {
  display: block;
  color: #64748b;
  font-size: 0.68rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.delivery-total-strip strong {
  display: block;
  margin-top: 0.3rem;
  color: #0f172a;
  font-size: 0.9rem;
}

.delivery-table {
  width: 100%;
  border-collapse: collapse;
}

.delivery-table thead {
  background: var(--po-table-header-bg);
  color: #ffffff;
}

.delivery-table th {
  padding: 1rem 0.8rem;
  font-size: 0.7rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.delivery-table td {
  padding: 1rem 0.8rem;
  border-bottom: 1px solid rgba(226, 232, 240, 0.82);
  vertical-align: top;
  font-size: 0.82rem;
}

.delivery-table tbody tr:hover {
  background: rgba(37, 99, 235, 0.035);
}

.delivery-table-row--attention {
  background: rgba(251, 191, 36, 0.08);
}

.delivery-table-number {
  color: #1d4ed8;
  font-weight: 800;
}

.delivery-table-item {
  min-width: 270px;
}

.delivery-item-no {
  display: inline-flex;
  margin-bottom: 0.4rem;
  padding: 0.24rem 0.65rem;
  border-radius: 999px;
  background: rgba(37, 99, 235, 0.09);
  color: #1d4ed8;
  font-size: 0.68rem;
  font-weight: 800;
}

.delivery-item-description {
  color: #0f172a;
  font-weight: 600;
  font-size: 0.84rem;
  line-height: 1.6;
}

.delivery-pill {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 118px;
  padding: 0.42rem 0.72rem;
  border-radius: 999px;
  font-size: 0.72rem;
  font-weight: 800;
}

.delivery-pill--success {
  background: rgba(16, 185, 129, 0.12);
  color: #047857;
}

.delivery-pill--warning {
  background: rgba(245, 158, 11, 0.16);
  color: #b45309;
}

.delivery-pill--danger {
  background: rgba(239, 68, 68, 0.12);
  color: #b91c1c;
}

.delivery-pill--info {
  background: rgba(14, 165, 233, 0.13);
  color: #0369a1;
}

.delivery-pill--secondary {
  background: rgba(148, 163, 184, 0.18);
  color: #475569;
}

.delivery-detail-text {
  display: block;
  margin-top: 0.4rem;
  color: #64748b;
  font-size: 0.68rem;
  line-height: 1.4;
}

.delivery-detail-text--right {
  text-align: right;
}

.delivery-money {
  color: #0f172a;
  font-weight: 700;
}

.delivery-money--final {
  color: #047857;
  font-weight: 800;
}

.delivery-table tfoot tr {
  background: linear-gradient(135deg, #f8fafc, #eef4ff);
}

.delivery-table-footer-label {
  font-size: 0.86rem;
  font-weight: 800;
  color: #334155;
}

@media (max-width: 1100px) {
  .delivery-summary-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 767px) {
  .delivery-hero,
  .delivery-table-header {
    padding: 1.2rem;
  }

  .delivery-summary-grid {
    grid-template-columns: 1fr;
  }

  .delivery-header-action {
    width: 100%;
  }

  .delivery-total-strip-wrap {
    padding: 0 1.2rem 1rem;
  }

  .delivery-total-strip {
    grid-template-columns: 1fr;
    width: 100%;
  }

  .delivery-table-item {
    min-width: 230px;
  }
}
</style>
