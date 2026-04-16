<template>
  <div class="po-tab-shell details-tab">
    <section class="details-hero">
      <div class="details-hero-copy">
        <span class="details-eyebrow">Purchase Order Overview</span>
        <h3 class="details-title">{{ purchaseOrder?.code || "Purchase Order" }}</h3>
        <p class="details-subtitle">
          Review the main purchase order details, supplier information, and item list in one place.
        </p>
      </div>
    </section>

    <div class="details-card-grid">
      <section class="details-card">
        <div class="details-card-header">
          <div class="details-card-icon details-card-icon--emerald">
            <i class="ri-shopping-cart-2-line"></i>
          </div>
          <div>
            <h4>Purchase Order Details</h4>
            <p>Key dates, delivery target, and current status.</p>
          </div>
        </div>

        <div class="details-list">
          <div class="details-list-item">
            <span class="details-label">PO No.</span>
            <strong class="details-value details-value--primary">{{ purchaseOrder?.code || "-" }}</strong>
          </div>
          <div class="details-list-item">
            <span class="details-label">PO Date</span>
            <strong class="details-value">{{ formatDisplayDate(purchaseOrder?.po_date) }}</strong>
          </div>
          <div v-if="purchaseOrder?.created_at" class="details-list-item">
            <span class="details-label">Date Created</span>
            <strong class="details-value">{{ formatDisplayDate(purchaseOrder.created_at) }}</strong>
          </div>
          <div v-if="purchaseOrder?.released_at" class="details-list-item">
            <span class="details-label">Date Issued</span>
            <strong class="details-value">{{ formatDisplayDate(purchaseOrder.released_at) }}</strong>
          </div>
          <div v-if="purchaseOrder?.conformed_at" class="details-list-item">
            <span class="details-label">Date Conformed</span>
            <strong class="details-value">{{ formatDisplayDate(purchaseOrder.conformed_at) }}</strong>
          </div>
          <div v-if="purchaseOrder?.date_of_delivery" class="details-list-item">
            <span class="details-label">Target Delivery Date</span>
            <strong class="details-value">{{ formatDisplayDate(purchaseOrder.date_of_delivery) }}</strong>
          </div>
          <div v-if="purchaseOrder?.resolved_actual_delivery_date_display" class="details-list-item">
            <span class="details-label">Delivered On</span>
            <strong class="details-value">{{ purchaseOrder.resolved_actual_delivery_date_display }}</strong>
          </div>
          <div v-if="modeOfProcurement" class="details-list-item">
            <span class="details-label">Mode of Procurement</span>
            <strong class="details-value">{{ modeOfProcurement }}</strong>
          </div>
          <div class="details-list-item details-list-item--status">
            <span class="details-label">Status</span>
            <div class="details-status-stack">
              <b-badge :class="purchaseOrder?.status?.bg" class="details-status-badge">
                {{ purchaseOrder?.status?.name || "-" }}
              </b-badge>
              <div v-if="purchaseOrder?.sub_status?.name" class="details-status-subline">
                <span class="details-status-sub-label">Sub-status</span>
                <strong class="details-value details-value--status-sub">
                  {{ purchaseOrder?.sub_status?.name }}
                </strong>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="details-card">
        <div class="details-card-header">
          <div class="details-card-icon details-card-icon--blue">
            <i class="ri-file-list-3-line"></i>
          </div>
          <div>
            <h4>Notice of Award Details</h4>
            <p>Supplier information connected to this purchase order.</p>
          </div>
        </div>

        <div class="details-list">
          <div class="details-list-item">
            <span class="details-label">NOA No.</span>
            <strong class="details-value details-value--primary">{{ noa?.code || "-" }}</strong>
          </div>
          <div class="details-list-item">
            <span class="details-label">Supplier</span>
            <strong class="details-value">{{ supplierName }}</strong>
          </div>
          <div v-if="supplierAddress" class="details-list-item">
            <span class="details-label">Address</span>
            <strong class="details-value">{{ supplierAddress }}</strong>
          </div>
          <div v-if="supplierTin" class="details-list-item">
            <span class="details-label">TIN</span>
            <strong class="details-value">{{ supplierTin }}</strong>
          </div>
        </div>
      </section>
    </div>

    <section class="details-table-card">
      <div class="details-table-header">
        <div>
          <span class="details-eyebrow">Ordered Items</span>
          <h4>Items included in this Purchase Order</h4>
          <p>Use this table to review quantity, unit, and amount for every ordered item.</p>
        </div>
      </div>

      <div class="table-responsive">
        <table class="details-table">
          <thead>
            <tr>
              <th class="text-center">#</th>
              <th>Description</th>
              <th class="text-center">Qty</th>
              <th class="text-center">Unit</th>
              <th class="text-end">Unit Cost</th>
              <th class="text-end">Total</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(item, index) in noaItems" :key="index">
              <td class="text-center details-table-number">{{ index + 1 }}</td>
              <td class="details-table-description">
                <div v-html="item.item.item.item_description"></div>
              </td>
              <td class="text-center">{{ formatQuantity(item.item.item.item_quantity) }}</td>
              <td class="text-center">{{ resolveUnit(item) }}</td>
              <td class="text-end details-table-money">{{ formatCurrency(item.item.bid_price) }}</td>
              <td class="text-end details-table-money">
                {{ formatCurrency(item.item.bid_price * item.item.item.item_quantity) }}
              </td>
            </tr>
            <tr v-if="!noaItems.length">
              <td colspan="6" class="text-center py-4 text-muted">
                No purchase order items found.
              </td>
            </tr>
          </tbody>
          <tfoot v-if="noaItems.length">
            <tr>
              <td colspan="5" class="text-end details-table-footer-label">Grand Total</td>
              <td class="text-end details-table-footer-amount">{{ formatCurrency(totalAmount) }}</td>
            </tr>
          </tfoot>
        </table>
      </div>
    </section>
  </div>
</template>

<script>
export default {
  props: {
    purchaseOrder: {
      type: Object,
      default: null,
    },
    noa: {
      type: Object,
      default: null,
    },
    procurement: {
      type: Object,
      default: null,
    },
  },
  computed: {
    noaItems() {
      return Array.isArray(this.noa?.items) ? this.noa.items : [];
    },
    totalItems() {
      return this.noaItems.length;
    },
    totalAmount() {
      return this.noaItems.reduce((sum, item) => {
        return sum + ((Number(item?.item?.bid_price) || 0) * (Number(item?.item?.item?.item_quantity) || 0));
      }, 0);
    },
    supplierName() {
      return this.noa?.procurement_quotation?.supplier?.name || "-";
    },
    supplierAddress() {
      return this.noa?.procurement_quotation?.supplier?.address?.address || "";
    },
    supplierTin() {
      return this.noa?.procurement_quotation?.supplier?.tin || "";
    },
    modeOfProcurement() {
      const names = Array.isArray(this.procurement?.codes)
        ? this.procurement.codes
          .map((code) => code?.procurement_code?.mode_of_procurement?.name)
          .filter(Boolean)
        : [];

      return [...new Set(names)].join(", ");
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
    formatDisplayDate(value) {
      if (!value) {
        return "-";
      }

      if (typeof value === "string") {
        const dateMatch = value.match(/^(\d{4}-\d{2}-\d{2})/);

        if (dateMatch) {
          const normalizedDate = new Date(`${dateMatch[1]}T00:00:00`);

          if (!Number.isNaN(normalizedDate.getTime())) {
            return new Intl.DateTimeFormat("en-PH", {
              year: "numeric",
              month: "long",
              day: "numeric",
            }).format(normalizedDate);
          }
        }
      }

      const parsedDate = new Date(value);

      if (Number.isNaN(parsedDate.getTime())) {
        return value;
      }

      return new Intl.DateTimeFormat("en-PH", {
        year: "numeric",
        month: "long",
        day: "numeric",
      }).format(parsedDate);
    },
    resolveUnit(item) {
      const quantity = Number(item?.item?.item?.item_quantity || 0);
      const unitType = item?.item?.item?.item_unit_type;

      return quantity > 1
        ? unitType?.name_long || unitType?.name_short || "-"
        : unitType?.name_short || unitType?.name_long || "-";
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

.details-hero {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 1rem;
  padding: 1.5rem 1.65rem;
  border-radius: 24px;
  background:
    radial-gradient(circle at top right, rgba(37, 99, 235, 0.2), transparent 32%),
    linear-gradient(135deg, #ffffff, #eef8ff 58%, #f8fbff);
  border: 1px solid rgba(191, 219, 254, 0.85);
  box-shadow: 0 22px 40px rgba(15, 23, 42, 0.08);
}

.details-eyebrow {
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

.details-hero-copy {
  max-width: 760px;
}

.details-title {
  margin: 0;
  font-size: 1.4rem;
  font-weight: 800;
  color: #0f172a;
}

.details-subtitle {
  max-width: 760px;
  margin: 0.55rem 0 0;
  color: #475569;
  font-size: 0.86rem;
  line-height: 1.6;
}

.details-card-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 1.5rem;
}

.details-card,
.details-table-card {
  border-radius: 24px;
  background: #ffffff;
  border: 1px solid rgba(226, 232, 240, 0.9);
  box-shadow: 0 18px 34px rgba(15, 23, 42, 0.07);
}

.details-card {
  padding: 1.5rem;
}

.details-card-header {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  margin-bottom: 1.35rem;
}

.details-card-header h4,
.details-table-header h4 {
  margin: 0;
  color: #0f172a;
  font-size: 0.96rem;
  font-weight: 800;
}

.details-card-header p,
.details-table-header p {
  margin: 0.25rem 0 0;
  color: #64748b;
  font-size: 0.84rem;
  line-height: 1.55;
}

.details-card-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 52px;
  height: 52px;
  border-radius: 18px;
  font-size: 1.3rem;
  flex-shrink: 0;
}

.details-card-icon--emerald {
  background: linear-gradient(135deg, rgba(16, 185, 129, 0.18), rgba(5, 150, 105, 0.08));
  color: #047857;
}

.details-card-icon--blue {
  background: linear-gradient(135deg, rgba(59, 130, 246, 0.18), rgba(37, 99, 235, 0.08));
  color: #1d4ed8;
}

.details-list {
  display: grid;
  gap: 0.9rem;
}

.details-list-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 0.9rem 1rem;
  border-radius: 18px;
  background: linear-gradient(135deg, #f8fbff, #ffffff);
  border: 1px solid rgba(226, 232, 240, 0.95);
}

.details-label {
  color: #64748b;
  font-size: 0.76rem;
  font-weight: 700;
  letter-spacing: 0.04em;
  text-transform: uppercase;
}

.details-value {
  color: #1e293b;
  font-size: 0.88rem;
  font-weight: 700;
  text-align: right;
}

.details-value--primary {
  color: #1d4ed8;
}

.details-status-badge {
  padding: 0.5rem 0.85rem;
  border-radius: 999px;
  font-size: 0.74rem;
}

.details-status-stack {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 0.55rem;
}

.details-status-subline {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 0.15rem;
}

.details-status-sub-label {
  color: #94a3b8;
  font-size: 0.68rem;
  font-weight: 700;
  letter-spacing: 0.04em;
  text-transform: uppercase;
}

.details-value--status-sub {
  font-size: 0.82rem;
}

.details-table-card {
  overflow: hidden;
}

.details-table-header {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
  align-items: flex-end;
  flex-wrap: wrap;
  padding: 1.5rem 1.5rem 1rem;
}

.details-table {
  width: 100%;
  border-collapse: collapse;
  background: #ffffff;
}

.details-table thead {
  background: var(--po-table-header-bg);
  color: #ffffff;
}

.details-table th {
  padding: 1rem 0.85rem;
  font-size: 0.72rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.details-table td {
  padding: 1rem 0.85rem;
  border-bottom: 1px solid rgba(226, 232, 240, 0.8);
  vertical-align: top;
  font-size: 0.84rem;
}

.details-table tbody tr:hover {
  background: rgba(37, 99, 235, 0.03);
}

.details-table-number {
  color: #1d4ed8;
  font-weight: 800;
}

.details-table-description {
  min-width: 260px;
  color: #0f172a;
  font-weight: 600;
  font-size: 0.84rem;
  line-height: 1.5;
}

.details-table-money {
  color: #047857;
  font-weight: 800;
}

.details-table tfoot tr {
  background: linear-gradient(135deg, #f8fafc, #eef4ff);
}

.details-table-footer-label,
.details-table-footer-amount {
  padding: 1rem 0.85rem;
  font-size: 0.88rem;
  font-weight: 800;
}

.details-table-footer-label {
  color: #334155;
}

.details-table-footer-amount {
  color: #047857;
}

@media (max-width: 991px) {
  .details-card-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 767px) {
  .details-hero,
  .details-card,
  .details-table-header {
    padding: 1.25rem;
  }

  .details-list-item {
    flex-direction: column;
    align-items: flex-start;
  }

  .details-value {
    text-align: left;
  }

  .details-status-stack,
  .details-status-subline {
    align-items: flex-start;
  }

  .details-title {
    font-size: 1.26rem;
  }

  .details-table-description {
    min-width: 220px;
  }
}
</style>
