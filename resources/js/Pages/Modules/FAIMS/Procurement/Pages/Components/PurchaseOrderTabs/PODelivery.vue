<template>
  <div class="po-delivery-list">
    <div class="po-delivery-list-toolbar">
      <div>
        <div class="text-uppercase fw-semibold text-primary small mb-1">
          Delivery Records
        </div>
        <h4 class="h6 fw-bold mb-1">Received Purchase Order Deliveries</h4>
        <p class="text-muted small mb-0">
          Review all RCV records created from received deliveries for this Purchase Order.
        </p>
      </div>
    </div>

    <div class="row g-2 mb-3">
      <div class="col-md-4">
        <div class="po-delivery-list-stat">
          <span class="po-delivery-list-stat-icon text-primary"><i class="ri-inbox-archive-line"></i></span>
          <div>
            <div class="small text-muted fw-semibold text-uppercase">Receiving Records</div>
            <div class="fw-bold fs-5">{{ receivingRecords.length }}</div>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="po-delivery-list-stat">
          <span class="po-delivery-list-stat-icon text-success"><i class="ri-stack-line"></i></span>
          <div>
            <div class="small text-muted fw-semibold text-uppercase">Items Received</div>
            <div class="fw-bold fs-5">{{ totalItems }}</div>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="po-delivery-list-stat">
          <span class="po-delivery-list-stat-icon text-info"><i class="ri-calculator-line"></i></span>
          <div>
            <div class="small text-muted fw-semibold text-uppercase">Quantity Received</div>
            <div class="fw-bold fs-5">{{ format_quantity(totalQuantity) }}</div>
          </div>
        </div>
      </div>
    </div>

    <b-card no-body class="border-0 shadow-sm po-delivery-list-card">
      <div class="table-responsive">
        <table class="table align-middle mb-0 fs-10">
          <thead class="table-light">
            <tr>
              <th style="width: 14%">RCV No.</th>
              <th style="width: 14%">Invoice</th>
              <th>Purchase Order</th>
              <th style="width: 18%">Received</th>
              <th style="width: 12%" class="text-center">Items</th>
              <th style="width: 12%" class="text-center">Quantity</th>
              <th style="width: 12%" class="text-center">Action</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="record in receivingRecords" :key="record.id || record.code">
              <td>
                <div class="fw-bold text-primary">{{ record.code }}</div>
              </td>
              <td>
                <div class="fw-semibold">{{ record.invoice_no || "No invoice" }}</div>
                <div class="small text-muted">{{ record.invoice_date || "-" }}</div>
              </td>
              <td>
                <div class="fw-semibold">{{ purchase_order?.code || "-" }}</div>
                <div class="small text-muted">{{ purchase_order?.status?.name || "-" }}</div>
              </td>
              <td>
                <div class="fw-semibold">{{ record.received_at || "-" }}</div>
                <div class="small text-muted">{{ record.received_by || "No receiver" }}</div>
              </td>
              <td class="text-center">
                <div class="fw-semibold">{{ record.items_count || 0 }} item(s)</div>
              </td>
              <td class="text-center">
                <div class="fw-semibold">{{ format_quantity(record.total_quantity) }}</div>
              </td>
              <td class="text-center">
                <b-button
                  v-if="canEditReceivedItems"
                  type="button"
                  size="sm"
                  variant="soft-primary"
                  class="po-delivery-list-action-btn"
                  @click="$emit('update-delivered-items', record)"
                >
                  <i class="ri-pencil-line me-1"></i>
                  Edit
                </b-button>
              </td>
            </tr>
            <tr v-if="!receivingRecords.length">
              <td colspan="7" class="text-center text-muted py-5">
                No receiving records found for this Purchase Order.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </b-card>
  </div>
</template>

<script>
export default {
  emits: ["update-delivered-items"],
  props: {
    purchase_order: { type: Object, default: null },
    delivery_summary: { type: Object, default: () => ({}) },
    delivery_monitoring_items: { type: Array, default: () => [] },
    can_update_delivered_items: { type: Boolean, default: false },
  },
  computed: {
    monitoringItemsById() {
      return this.delivery_monitoring_items.reduce((items, item) => {
        items[Number(item.id)] = item;
        return items;
      }, {});
    },
    receivingRecords() {
      if (Array.isArray(this.purchase_order?.received_deliveries)) {
        return this.purchase_order.received_deliveries;
      }

      return (this.purchase_order?.deliveries || []).map((delivery) => {
        const items = this.normalizedDeliveredItems(delivery);

        return {
          id: delivery.id,
          code: delivery.code || this.receivingCode(delivery.id),
          invoice_no: delivery.invoice_no,
          invoice_date: this.format_date(delivery.invoice_date || delivery.invoice_date_raw),
          received_at: this.format_date_time(delivery.created_at || delivery.received_at),
          received_by: this.receiverName(delivery),
          items_count: items.length,
          total_quantity: items.reduce((sum, item) => {
            return sum + Number(item.delivered_quantity || 0);
          }, 0),
          items,
        };
      });
    },
    totalItems() {
      return this.receivingRecords.reduce((sum, record) => sum + Number(record.items_count || 0), 0);
    },
    totalQuantity() {
      return this.receivingRecords.reduce((sum, record) => sum + Number(record.total_quantity || 0), 0);
    },
    iarReports() {
      return Array.isArray(this.purchase_order?.iars)
        ? this.purchase_order.iars
        : (this.purchase_order?.iar ? [this.purchase_order.iar] : []);
    },
    canEditReceivedItems() {
      if (typeof this.purchase_order?.can_edit_received_items !== "undefined") {
        return Boolean(this.purchase_order.can_edit_received_items);
      }

      return this.receivingRecords.length > 0 && this.iarReports.length === 0;
    },
  },
  methods: {
    normalizedDeliveredItems(delivery) {
      return (delivery?.items || delivery?.delivered_items || [])
        .map((entry) => {
          const itemId = Number(entry.item_id || entry.id);
          const monitoringItem = this.monitoringItemsById[itemId] || {};

          return {
            item_id: itemId,
            item_no: entry.item_no || monitoringItem.item_no,
            delivered_quantity: Number(entry.delivered_quantity || 0),
            unit: entry.unit || monitoringItem.unit,
          };
        })
        .filter((entry) => entry.item_id > 0);
    },
    receivingCode(id) {
      return id ? `RCV-${String(id).padStart(6, "0")}` : "-";
    },
    receiverName(delivery) {
      return delivery?.received_by?.profile?.fullname
        || delivery?.received_by?.profile?.full_name
        || delivery?.received_by?.name
        || delivery?.received_by?.username
        || delivery?.received_by
        || null;
    },
    format_date(value) {
      if (!value) {
        return null;
      }

      const parsed_date = new Date(value);

      if (Number.isNaN(parsed_date.getTime())) {
        return value;
      }

      return new Intl.DateTimeFormat("en-PH", {
        year: "numeric",
        month: "short",
        day: "numeric",
      }).format(parsed_date);
    },
    format_date_time(value) {
      if (!value) {
        return null;
      }

      const parsed_date = new Date(value);

      if (Number.isNaN(parsed_date.getTime())) {
        return value;
      }

      return new Intl.DateTimeFormat("en-PH", {
        year: "numeric",
        month: "short",
        day: "numeric",
        hour: "numeric",
        minute: "2-digit",
      }).format(parsed_date);
    },
    format_quantity(value) {
      const numeric_value = Number(value ?? 0);

      if (!Number.isFinite(numeric_value)) {
        return "-";
      }

      return Number.isInteger(numeric_value)
        ? numeric_value.toLocaleString("en-PH")
        : numeric_value.toLocaleString("en-PH", {
            minimumFractionDigits: 0,
            maximumFractionDigits: 4,
          });
    },
  },
};
</script>

<style scoped>
.po-delivery-list {
  --po-delivery-list-border: rgba(148, 163, 184, 0.22);
  --po-delivery-list-surface: #ffffff;
  --po-delivery-list-soft: #f8fafc;
}

.po-delivery-list-toolbar {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 1rem;
  margin-bottom: 0.75rem;
}

.po-delivery-list-stat {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.85rem 1rem;
  border: 1px solid var(--po-delivery-list-border);
  border-radius: 8px;
  background: var(--po-delivery-list-surface);
  box-shadow: 0 10px 22px rgba(15, 23, 42, 0.05);
}

.po-delivery-list-stat-icon {
  width: 38px;
  height: 38px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 999px;
  background: var(--po-delivery-list-soft);
  border: 1px solid var(--po-delivery-list-border);
  font-size: 1rem;
}

.po-delivery-list-card {
  border-radius: 8px;
  overflow: hidden;
}

.po-delivery-list-action-btn {
  --vz-btn-padding-x: 0.45rem;
  --vz-btn-padding-y: 0.18rem;
  --vz-btn-font-size: 0.65rem;
  border-color: transparent !important;
  box-shadow: none;
  line-height: 1.2;
}

.po-delivery-list-action-btn i {
  font-size: 0.72rem;
}

[data-bs-theme="dark"] .po-delivery-list {
  --po-delivery-list-border: rgba(148, 163, 184, 0.18);
  --po-delivery-list-surface: #1b2230;
  --po-delivery-list-soft: #232c3a;
}

[data-bs-theme="dark"] .po-delivery-list-stat,
[data-bs-theme="dark"] .po-delivery-list-card {
  background: var(--po-delivery-list-surface) !important;
  border-color: var(--po-delivery-list-border) !important;
}
</style>
