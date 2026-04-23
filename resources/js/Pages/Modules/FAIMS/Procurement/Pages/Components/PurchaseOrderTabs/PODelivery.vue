<template>
  <div class="d-grid gap-2">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
      <div>
        <div class="text-uppercase fw-semibold text-primary small mb-1">
          Delivery Tracking
        </div>
        <h4 class="h6 fw-bold mb-1">Monitor Delivered and Pending Items</h4>
        <p class="text-muted small mb-0">
          Check what was delivered, what is still needed, and whether the supplier delivered on time.
        </p>
      </div>

      <div class="border rounded bg-light px-3 py-2">
        <div class="text-uppercase fw-semibold text-warning small mb-1">
          Late Delivery Rule
        </div>
        <div class="fw-semibold small">
          1% deduction per day from the delivered amount
        </div>
      </div>
    </div>

    <b-row class="g-2">
      <b-col md="6" xl="3">
        <b-card class="border-0 shadow-sm h-100">
          <div
            class="rounded-circle bg-light border text-success d-inline-flex align-items-center justify-content-center p-2 mb-2"
          >
            <i class="ri-checkbox-circle-line"></i>
          </div>
          <div class="text-uppercase fw-semibold text-muted small">Delivered</div>
          <div class="fs-4 fw-bold mt-1">{{ delivery_summary.delivered_items || 0 }}</div>
          <div class="small text-muted mt-1">Items fully delivered</div>
        </b-card>
      </b-col>

      <b-col md="6" xl="3">
        <b-card class="border-0 shadow-sm h-100">
          <div
            class="rounded-circle bg-light border text-warning d-inline-flex align-items-center justify-content-center p-2 mb-2"
          >
            <i class="ri-loader-4-line"></i>
          </div>
          <div class="text-uppercase fw-semibold text-muted small">Partial Delivery</div>
          <div class="fs-4 fw-bold mt-1">{{ delivery_summary.partial_items || 0 }}</div>
          <div class="small text-muted mt-1">Items with incomplete delivery</div>
        </b-card>
      </b-col>

      <b-col md="6" xl="3">
        <b-card class="border-0 shadow-sm h-100">
          <div
            class="rounded-circle bg-light border text-primary d-inline-flex align-items-center justify-content-center p-2 mb-2"
          >
            <i class="ri-truck-line"></i>
          </div>
          <div class="text-uppercase fw-semibold text-muted small">Still Needed</div>
          <div class="fs-4 fw-bold mt-1">{{ delivery_summary.needs_delivery_items || 0 }}</div>
          <div class="small text-muted mt-1">Items still waiting for delivery</div>
        </b-card>
      </b-col>

      <b-col md="6" xl="3">
        <b-card class="border-0 shadow-sm h-100">
          <div
            class="rounded-circle bg-light border text-danger d-inline-flex align-items-center justify-content-center p-2 mb-2"
          >
            <i class="ri-alarm-warning-line"></i>
          </div>
          <div class="text-uppercase fw-semibold text-muted small">Late / Overdue</div>
          <div class="fs-4 fw-bold mt-1">
            {{ (delivery_summary.late_items || 0) + (delivery_summary.overdue_items || 0) }}
          </div>
          <div class="small text-muted mt-1">Items needing attention</div>
        </b-card>
      </b-col>
    </b-row>

    <b-card no-body class="border-0 shadow-sm">
      <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 p-3 border-bottom">
        <div>
          <div class="text-uppercase fw-semibold text-primary small mb-1">
            Item
          </div>
          <h4 class="h6 fw-bold mb-1">Delivery Progress Per Item</h4>
          <p class="text-muted small mb-0">
            Use this table to review quantities, dates, on-time status, and late deductions.
          </p>
        </div>
      </div>

      <div class="row g-2 p-3 border-bottom bg-light">
        <div class="col-md-4">
          <div class="border rounded bg-white px-3 py-2 h-100">
            <div class="small text-uppercase text-muted fw-semibold">Delivered Amount</div>
            <div class="fw-bold mt-1">{{ format_currency(delivery_summary.delivered_amount_total) }}</div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="border rounded bg-white px-3 py-2 h-100">
            <div class="small text-uppercase text-muted fw-semibold">Late Deduction</div>
            <div class="fw-bold text-danger mt-1">{{ format_currency(delivery_summary.penalty_amount_total) }}</div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="border rounded bg-white px-3 py-2 h-100">
            <div class="small text-uppercase text-muted fw-semibold">Final Amount</div>
            <div class="fw-bold text-success mt-1">{{ format_currency(delivery_summary.adjusted_amount_total) }}</div>
          </div>
        </div>
      </div>

      <div class="table-responsive">
        <table class="table align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th class="text-center">#</th>
              <th>Item</th>
              <th>Quantity Overview</th>
              <th>Schedule</th>
              <th>Delivery Status</th>
              <th>Amount Summary</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="(item, index) in delivery_monitoring_items"
              :key="item.id || index"
              :class="{ 'table-warning': item.needs_delivery }"
            >
              <td class="text-center fw-semibold">
                {{ item.item_no }}
              </td>
              <td>
                <div class="d-grid gap-2">
                  <div class="fw-semibold text-dark">
                    {{ resolve_item_name(item) }}
                  </div>
                  <div v-if="item.needs_delivery" class="d-flex flex-wrap gap-2">
                    <span class="badge rounded-pill text-bg-warning">
                      Needs Delivery
                    </span>
                  </div>
                </div>
                <div
                  v-if="has_extra_description(item)"
                  class="small text-muted mt-1"
                  v-html="item.description"
                ></div>
              </td>
              <td>
                <div class="row g-2">
                  <div class="col-12 col-xl-4">
                    <div class="border rounded bg-light px-3 py-2 h-100">
                      <div class="small text-uppercase text-muted fw-semibold">
                        Ordered
                      </div>
                      <div class="fw-bold">
                        {{ format_quantity(item.ordered_quantity) }}
                      </div>
                    </div>
                  </div>
                  <div class="col-12 col-xl-4">
                    <div class="border rounded bg-light px-3 py-2 h-100">
                      <div class="small text-uppercase text-muted fw-semibold">
                        Delivered
                      </div>
                      <div class="fw-bold text-success">
                        {{ format_quantity(item.delivered_quantity) }}
                      </div>
                    </div>
                  </div>
                  <div class="col-12 col-xl-4">
                    <div class="border rounded bg-light px-3 py-2 h-100">
                      <div class="small text-uppercase text-muted fw-semibold">
                        Still Needed
                      </div>
                      <div
                        class="fw-bold"
                        :class="Number(item.remaining_quantity || 0) > 0 ? 'text-warning' : 'text-success'"
                      >
                        {{ format_quantity(item.remaining_quantity) }}
                      </div>
                    </div>
                  </div>
                </div>
                <div class="small text-muted mt-2">
                  Unit: <span class="fw-semibold text-dark">{{ item.unit || "-" }}</span>
                </div>
              </td>
              <td>
                <div class="d-grid gap-2">
                  <div class="border rounded px-3 py-2 bg-light">
                    <div class="small text-uppercase text-muted fw-semibold">
                      Target Date
                    </div>
                    <div class="fw-semibold">
                      {{ item.expected_delivery_date_display || "-" }}
                    </div>
                  </div>
                  <div class="border rounded px-3 py-2 bg-light">
                    <div class="small text-uppercase text-muted fw-semibold">
                      Delivered On
                    </div>
                    <div class="fw-semibold">
                      {{ item.actual_delivery_date_display || "-" }}
                    </div>
                  </div>
                </div>
              </td>
              <td>
                <div class="d-grid gap-2">
                  <div>
                    <div class="small text-uppercase text-muted fw-semibold mb-1">
                      Delivery
                    </div>
                    <b-badge pill :variant="badge_variant(item.delivery_status_variant)">
                      {{ item.delivery_status || "-" }}
                    </b-badge>
                  </div>
                  <div>
                    <div class="small text-uppercase text-muted fw-semibold mb-1">
                      On-Time Status
                    </div>
                    <b-badge pill :variant="badge_variant(item.timeliness_variant)">
                      {{ item.timeliness || "-" }}
                    </b-badge>
                    <div v-if="item.timeliness_detail" class="small text-muted mt-1">
                      {{ item.timeliness_detail }}
                    </div>
                  </div>
                </div>
              </td>
              <td>
                <div class="d-grid gap-2">
                  <div class="border rounded px-3 py-2 bg-light">
                    <div class="small text-uppercase text-muted fw-semibold">
                      Delivered Amount
                    </div>
                    <div class="fw-bold">
                      {{ format_currency(item.delivered_amount) }}
                    </div>
                  </div>
                  <div class="border rounded px-3 py-2 bg-light">
                    <div class="small text-uppercase text-muted fw-semibold">
                      Late Deduction
                    </div>
                    <div class="fw-bold text-danger">
                      {{ format_currency(item.penalty_amount) }}
                    </div>
                    <div v-if="item.penalty_days > 0" class="small text-muted mt-1">
                      1% x {{ item.penalty_days }} day<span v-if="item.penalty_days !== 1">s</span>
                    </div>
                  </div>
                  <div class="border rounded px-3 py-2 bg-light">
                    <div class="small text-uppercase text-muted fw-semibold">
                      Final Amount
                    </div>
                    <div class="fw-bold text-success">
                      {{ format_currency(item.adjusted_amount) }}
                    </div>
                  </div>
                </div>
              </td>
            </tr>

            <tr v-if="!delivery_monitoring_items.length">
              <td colspan="6" class="text-center py-5 text-muted">
                No delivery tracking data is available for this purchase order yet.
              </td>
            </tr>
          </tbody>
          <tfoot v-if="delivery_monitoring_items.length" class="table-light">
            <tr>
              <td colspan="5" class="text-end fw-semibold align-middle">Total</td>
              <td>
                <div class="d-grid gap-2">
                  <div class="border rounded px-3 py-2 bg-white">
                    <div class="small text-uppercase text-muted fw-semibold">
                      Delivered Amount
                    </div>
                    <div class="fw-bold">
                      {{ format_currency(delivery_summary.delivered_amount_total) }}
                    </div>
                  </div>
                  <div class="border rounded px-3 py-2 bg-white">
                    <div class="small text-uppercase text-muted fw-semibold">
                      Late Deduction
                    </div>
                    <div class="fw-bold text-danger">
                      {{ format_currency(delivery_summary.penalty_amount_total) }}
                    </div>
                  </div>
                  <div class="border rounded px-3 py-2 bg-white">
                    <div class="small text-uppercase text-muted fw-semibold">
                      Final Amount
                    </div>
                    <div class="fw-bold text-success">
                      {{ format_currency(delivery_summary.adjusted_amount_total) }}
                    </div>
                  </div>
                </div>
              </td>
            </tr>
          </tfoot>
        </table>
      </div>
    </b-card>
  </div>
</template>

<script>
export default {
  emits: ["update-delivered-items"],
  props: ["delivery_summary", "delivery_monitoring_items", "can_update_delivered_items"],
  methods: {
    strip_html(value) {
      return String(value || "")
        .replace(/<[^>]*>/g, " ")
        .replace(/&nbsp;/gi, " ")
        .replace(/\s+/g, " ")
        .trim();
    },
    resolve_item_name(item) {
      const item_name = String(item?.item_name || "").trim();
      if (item_name) {
        return item_name;
      }

      const description_text = this.strip_html(item?.description);
      return description_text || "-";
    },
    has_extra_description(item) {
      const description_html = String(item?.description || "").trim();
      if (!description_html) {
        return false;
      }

      const description_text = this.strip_html(description_html);
      if (!description_text) {
        return false;
      }

      return description_text !== this.resolve_item_name(item);
    },
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
    badge_variant(variant) {
      const allowed_variants = [
        "primary",
        "secondary",
        "success",
        "danger",
        "warning",
        "info",
        "light",
        "dark",
      ];

      return allowed_variants.includes(variant) ? variant : "secondary";
    },
  },
};
</script>
