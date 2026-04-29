<template>
  <div class="d-grid gap-2 fs-12">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
      <div>
        <div class="text-uppercase fw-semibold text-primary small mb-1">
          Purchase Order Overview
        </div>
        <h3 class="h6 fw-bold mb-1">{{ purchaseOrder?.code || "Purchase Order" }}</h3>
        <p class="text-muted small mb-0">
          Review the main purchase order details, supplier information, and item list in one place.
        </p>
      </div>

      <b-badge variant="secondary" pill class="align-self-start">
        {{ total_items }} item<span v-if="total_items !== 1">s</span>
      </b-badge>
    </div>

    <b-row class="g-2">
      <b-col lg="6">
        <b-card class="border-0 shadow-sm h-100">
          <div class="d-flex align-items-start gap-2 mb-2">
            <div
              class="rounded-circle bg-light border text-success d-inline-flex align-items-center justify-content-center p-2 flex-shrink-0"
            >
              <i class="ri-shopping-cart-2-line"></i>
            </div>
            <div>
              <h4 class="h6 fw-bold mb-1">Purchase Order Details</h4>
              <p class="text-muted small mb-0">
                Key dates, delivery target, and current status.
              </p>
            </div>
          </div>

          <div class="d-grid gap-2">
            <div class="border rounded bg-light px-3 py-2 d-flex justify-content-between align-items-start gap-3">
              <div class="small text-uppercase text-muted fw-semibold">PO No.</div>
              <div class="fw-semibold text-primary text-end">{{ purchaseOrder?.code || "-" }}</div>
            </div>
            <div class="border rounded bg-light px-3 py-2 d-flex justify-content-between align-items-start gap-3">
              <div class="small text-uppercase text-muted fw-semibold">PO Date</div>
              <div class="fw-semibold text-end">{{ format_display_date(purchaseOrder?.po_date) }}</div>
            </div>
            <div
              v-if="purchaseOrder?.created_at"
              class="border rounded bg-light px-3 py-2 d-flex justify-content-between align-items-start gap-3"
            >
              <div class="small text-uppercase text-muted fw-semibold">Date Created</div>
              <div class="fw-semibold text-end">{{ format_display_date(purchaseOrder.created_at) }}</div>
            </div>
            <div
              v-if="purchaseOrder?.released_at"
              class="border rounded bg-light px-3 py-2 d-flex justify-content-between align-items-start gap-3"
            >
              <div class="small text-uppercase text-muted fw-semibold">Date Issued</div>
              <div class="fw-semibold text-end">{{ format_display_date(purchaseOrder.released_at) }}</div>
            </div>
            <div
              v-if="purchaseOrder?.conformed_at"
              class="border rounded bg-light px-3 py-2 d-flex justify-content-between align-items-start gap-3"
            >
              <div class="small text-uppercase text-muted fw-semibold">Date Conformed</div>
              <div class="fw-semibold text-end">{{ format_display_date(purchaseOrder.conformed_at) }}</div>
            </div>
            <div
              v-if="purchaseOrder?.date_of_delivery"
              class="border rounded bg-light px-3 py-2 d-flex justify-content-between align-items-start gap-3"
            >
              <div class="small text-uppercase text-muted fw-semibold">Target Delivery Date</div>
              <div class="fw-semibold text-end">{{ format_display_date(purchaseOrder.date_of_delivery) }}</div>
            </div>
            <div
              v-if="purchaseOrder?.resolved_actual_delivery_date_display"
              class="border rounded bg-light px-3 py-2 d-flex justify-content-between align-items-start gap-3"
            >
              <div class="small text-uppercase text-muted fw-semibold">Delivered On</div>
              <div class="fw-semibold text-end">{{ purchaseOrder.resolved_actual_delivery_date_display }}</div>
            </div>
            <div
              v-if="mode_of_procurement"
              class="border rounded bg-light px-3 py-2 d-flex justify-content-between align-items-start gap-3"
            >
              <div class="small text-uppercase text-muted fw-semibold">Mode of Procurement</div>
              <div class="fw-semibold text-end">{{ mode_of_procurement }}</div>
            </div>
            <div class="border rounded bg-light px-3 py-2 d-flex justify-content-between align-items-start gap-3">
              <div class="small text-uppercase text-muted fw-semibold">Status</div>
              <div class="text-end">
                <span class="badge rounded-pill" :class="purchaseOrder?.status?.bg || 'bg-secondary'">
                  {{ purchaseOrder?.status?.name || "-" }}
                </span>
                <div v-if="purchaseOrder?.sub_status?.name" class="small text-muted mt-2">
                  Sub-status:
                  <span class="fw-semibold text-dark">{{ purchaseOrder?.sub_status?.name }}</span>
                </div>
              </div>
            </div>
          </div>
        </b-card>
      </b-col>

      <b-col lg="6">
        <b-card class="border-0 shadow-sm h-100">
          <div class="d-flex align-items-start gap-2 mb-2">
            <div
              class="rounded-circle bg-light border text-primary d-inline-flex align-items-center justify-content-center p-2 flex-shrink-0"
            >
              <i class="ri-file-list-3-line"></i>
            </div>
            <div>
              <h4 class="h6 fw-bold mb-1">Notice of Award Details</h4>
              <p class="text-muted small mb-0">
                Supplier information connected to this purchase order.
              </p>
            </div>
          </div>

          <div class="d-grid gap-2">
            <div class="border rounded bg-light px-3 py-2 d-flex justify-content-between align-items-start gap-3">
              <div class="small text-uppercase text-muted fw-semibold">NOA No.</div>
              <div class="fw-semibold text-primary text-end">{{ noa?.code || "-" }}</div>
            </div>
            <div class="border rounded bg-light px-3 py-2 d-flex justify-content-between align-items-start gap-3">
              <div class="small text-uppercase text-muted fw-semibold">Supplier</div>
              <div class="fw-semibold text-end">{{ supplier_name }}</div>
            </div>
            <div
              v-if="supplier_address"
              class="border rounded bg-light px-3 py-2 d-flex justify-content-between align-items-start gap-3"
            >
              <div class="small text-uppercase text-muted fw-semibold">Address</div>
              <div class="fw-semibold text-end">{{ supplier_address }}</div>
            </div>
            <div
              v-if="supplier_tin"
              class="border rounded bg-light px-3 py-2 d-flex justify-content-between align-items-start gap-3"
            >
              <div class="small text-uppercase text-muted fw-semibold">TIN</div>
              <div class="fw-semibold text-end">{{ supplier_tin }}</div>
            </div>
          </div>
        </b-card>
      </b-col>
    </b-row>

    <b-card no-body class="border-0 shadow-sm">
      <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 p-3 border-bottom">
        <div>
          <div class="text-uppercase fw-semibold text-primary small mb-1">
            Ordered Items
          </div>
          <h4 class="h6 fw-bold mb-1">Items Included in This Purchase Order</h4>
          <p class="text-muted small mb-0">
            Use this table to review quantity, unit, and amount for every ordered item.
          </p>
        </div>
      </div>

      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th class="text-center">Item No.</th>
              <th>Description</th>
              <th class="text-center">Qty/Unit</th>
              <th class="text-center">Unit Cost</th>
              <th class="text-center">Total</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(item, index) in noa_items" :key="index">
              <td class="text-center fw-semibold">{{ item.item.item.item_no }}</td>
              <td>
                <div v-if="resolve_item_title(item)" class="fw-semibold mb-1">
                  {{ resolve_item_title(item) }}
                </div>
                <div
                  v-if="resolve_item_description(item)"
                  class="small text-muted"
                  v-html="resolve_item_description(item)"
                ></div>
                <span v-if="!resolve_item_title(item) && !resolve_item_description(item)" class="text-muted">
                  -
                </span>
              </td>
              <td class="text-center">{{ format_quantity(item.item.item.item_quantity) }} {{ resolve_unit(item) }}</td>
              <td class="text-center fw-semibold">{{ format_currency(item.item.bid_price) }}</td>
              <td class="text-center fw-semibold">
                {{ format_currency(item.item.bid_price * item.item.item.item_quantity) }}
              </td>
            </tr>
            <tr v-if="!noa_items.length">
              <td colspan="5" class="text-center py-4 text-muted">
                No purchase order items found.
              </td>
            </tr>
          </tbody>
          <tfoot v-if="noa_items.length" class="table-light">
            <tr>
              <td colspan="4" class="text-end fw-semibold">Grand Total</td>
              <td class="text-center fw-bold">{{ format_currency(total_amount) }}</td>
            </tr>
          </tfoot>
        </table>
      </div>
    </b-card>
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
    noa_items() {
      return Array.isArray(this.noa?.items) ? this.noa.items : [];
    },
    total_items() {
      return this.noa_items.length;
    },
    total_amount() {
      return this.noa_items.reduce((sum, item) => {
        return sum + ((Number(item?.item?.bid_price) || 0) * (Number(item?.item?.item?.item_quantity) || 0));
      }, 0);
    },
    supplier_name() {
      return this.noa?.procurement_quotation?.supplier?.name || "-";
    },
    supplier_address() {
      return this.noa?.procurement_quotation?.supplier?.address?.address || "";
    },
    supplier_tin() {
      return this.noa?.procurement_quotation?.supplier?.tin || "";
    },
    mode_of_procurement() {
      const names = Array.isArray(this.procurement?.codes)
        ? this.procurement.codes
          .map((code) => code?.procurement_code?.mode_of_procurement?.name)
          .filter(Boolean)
        : [];

      return [...new Set(names)].join(", ");
    },
  },
  methods: {
    format_currency(value) {
      return new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
      }).format(Number(value || 0));
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
    format_display_date(value) {
      if (!value) {
        return "-";
      }

      if (typeof value === "string") {
        const date_match = value.match(/^(\d{4}-\d{2}-\d{2})/);

        if (date_match) {
          const normalized_date = new Date(`${date_match[1]}T00:00:00`);

          if (!Number.isNaN(normalized_date.getTime())) {
            return new Intl.DateTimeFormat("en-PH", {
              year: "numeric",
              month: "long",
              day: "numeric",
            }).format(normalized_date);
          }
        }
      }

      const parsed_date = new Date(value);

      if (Number.isNaN(parsed_date.getTime())) {
        return value;
      }

      return new Intl.DateTimeFormat("en-PH", {
        year: "numeric",
        month: "long",
        day: "numeric",
      }).format(parsed_date);
    },
    resolve_unit(item) {
      const quantity = Number(item?.item?.item?.item_quantity || 0);
      const unit_type = item?.item?.item?.item_unit_type;

      return quantity > 1
        ? unit_type?.name_long || unit_type?.name_short || "-"
        : unit_type?.name_short || unit_type?.name_long || "-";
    },
    resolve_item_title(item) {
      return item?.item?.item?.item_name || "";
    },
    resolve_item_description(item) {
      return item?.item?.item?.item_description || "";
    },
  },
};
</script>
