<template>
  <div class="d-grid gap-2 fs-12">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
      <div>
        <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
          <div class="text-uppercase fw-semibold text-primary small">
            Inspection Reports
          </div>
        </div>

      </div>
    </div>

    <b-card no-body class="border-0 shadow-sm">
      <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 p-3 border-bottom">
        <div>
          <div class="text-uppercase fw-semibold text-primary small mb-1">
            Inspection Reports
          </div>
          <h4 class="h6 fw-bold mb-1">{{ reports_section_title }}</h4>
          <p class="text-muted small mb-0">
            {{ reports_section_description }}
          </p>
        </div>

        <b-button
          v-if="can_show_generate_button"
          variant="primary"
          size="sm"
          @click="$emit('open-iar')"
        >
          <i class="ri-add-line me-1"></i>
          Generate New IAR
        </b-button>
      </div>

      <div v-if="iar_reports.length" class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th class="text-center">#</th>
              <th>IAR Report</th>
              <th class="text-center">Generated On</th>
              <th class="text-center">Last Updated</th>
              <th class="text-center">Selected Items</th>
              <th class="text-center">Report Status</th>
              <th v-if="show_action_column" class="text-center">{{ action_column_label }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(report, index) in iar_reports" :key="report.id || index">
              <td class="text-center fw-semibold">{{ index + 1 }}</td>
              <td>
                <div class="fw-semibold">{{ report.code || "IAR" }}</div>
                <div class="text-muted small">
                  {{ report.description }}
                </div>
              </td>
              <td class="text-center">
                {{ format_date_time(report.created_at) }}
              </td>
              <td class="text-center">
                {{ format_date_time(report.updated_at) }}
              </td>
              <td class="text-center">{{ report.selected_items_count }}</td>
              <td class="text-center">
                <b-badge pill :variant="badge_variant(report.status_variant)">
                  {{ report.status_label }}
                </b-badge>
              </td>
              <td v-if="show_action_column" class="text-center">
                <div class="d-flex flex-wrap justify-content-center gap-2">
                  <b-button
                    v-if="report.can_edit"
                    type="button"
                    variant="outline-info"
                    size="sm"
                    class="px-2"
                    :disabled="processing_iar_id === report.id"
                    @click="$emit('edit-iar', report)"
                    v-b-tooltip.hover
                    title="Edit generated IAR report"
                    aria-label="Edit generated IAR report"
                  >
                    <i class="ri-pencil-line"></i>
                  </b-button>
                  <b-button
                    v-if="report.can_update_status"
                    type="button"
                    variant="outline-primary"
                    size="sm"
                    class="px-2"
                    :disabled="processing_iar_id === report.id"
                    @click="$emit('inspect-iar', report)"
                    v-b-tooltip.hover
                    title="Mark as Inspected/Completed"
                    aria-label="Mark as Inspected/Completed"
                  >
                    <i :class="processing_iar_id === report.id ? 'ri-loader-4-line' : 'ri-shield-check-line'"></i>
                  </b-button>
                  <b-button
                    v-if="report.can_revert_status"
                    type="button"
                    variant="outline-warning"
                    size="sm"
                    class="px-2"
                    :disabled="processing_iar_id === report.id"
                    @click="$emit('revert-iar', report)"
                    v-b-tooltip.hover
                    title="Revert to Generated"
                    aria-label="Revert to Generated"
                  >
                    <i :class="processing_iar_id === report.id ? 'ri-loader-4-line' : 'ri-arrow-go-back-line'"></i>
                  </b-button>
                  <b-button
                    v-if="report.can_print"
                    type="button"
                    variant="outline-secondary"
                    size="sm"
                    class="px-2"
                    @click="print_iar_report(report)"
                    v-b-tooltip.hover
                    title="Print IAR report"
                    aria-label="Print IAR report"
                  >
                    <i class="ri-printer-line"></i>
                  </b-button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-else class="text-center py-5 px-4">
        <div class="text-primary mb-3">
          <i class="ri-inbox-archive-line fs-1"></i>
        </div>
        <h4 class="h6 fw-bold mb-2">{{ empty_state_title }}</h4>
        <p class="text-muted mb-0">
          {{ empty_state_description }}
        </p>
      </div>
    </b-card>
  </div>
</template>

<script>
export default {
  emits: ["open-iar", "edit-iar", "print-iar", "inspect-iar", "revert-iar"],
  props: [
    "purchase_order",
    "delivered_monitoring_items",
    "delivery_summary",
    "can_generate_iar_report",
    "can_print_iar_report",
    "processing_iar_id",
  ],
  computed: {
    current_roles() {
      return Array.isArray(this.$page?.props?.roles) ? this.$page.props.roles : [];
    },
    is_employee_only_role() {
      return this.current_roles.length === 1 && this.current_roles.includes("Employee");
    },
    can_manage_iar_reports() {
      return this.current_roles.some((role) => {
        return ["Procurement Staff", "Procurement Officer", "Administrator"].includes(role);
      });
    },
    view_mode_label() {
      if (this.is_employee_only_role) {
        return "Employee View";
      }

      if (this.current_roles.includes("Administrator")) {
        return "Admin View";
      }

      if (this.current_roles.includes("Procurement Officer")) {
        return "Procurement Officer";
      }

      if (this.current_roles.includes("Procurement Staff")) {
        return "Procurement Staff";
      }

      return "Purchase Order View";
    },
    hero_title() {
      return this.is_employee_only_role
        ? "Inspection and Acceptance Reports"
        : "Generated IAR Reports";
    },

    reports_section_title() {
      return this.is_employee_only_role
        ? "Inspection and Acceptance Report List"
        : "List of Generated Inspection and Acceptance Reports";
    },
    reports_section_description() {
      if (this.is_employee_only_role) {
        return "Review the reports generated for this purchase order and print them when a copy is available.";
      }

      return "Review each generated report here, print it, complete it when ready, edit it while it is still generated, or revert it back to Generated when allowed.";
    },
    can_show_generate_button() {
      return this.can_manage_iar_reports && this.can_generate_iar_report;
    },
    action_column_label() {
      return this.is_employee_only_role ? "Available Copy" : "Actions";
    },
    iar_reports() {
      const reports = Array.isArray(this.purchase_order?.iars)
        ? this.purchase_order.iars
        : (this.purchase_order?.iar ? [this.purchase_order.iar] : []);

      return reports.map((report, index) => {
        const status_name = String(report?.status?.name || "").trim();
        const can_update_status = typeof report?.can_update_status === "boolean"
          ? report.can_update_status
          : ["Generated", "Pending"].includes(status_name);
        const can_revert_status = typeof report?.can_revert_status === "boolean"
          ? report.can_revert_status
          : (
            this.normalized_purchase_order_status === "Conformed"
            && status_name === "Completed"
          );

        return {
          ...report,
          id: report?.id ?? index,
          code: report?.code || "IAR",
          can_print: Boolean(report?.code && this.purchase_order?.id && this.can_print_iar_report),
          created_at: report?.created_at,
          updated_at: report?.updated_at,
          selected_items_count: Number(report?.selected_items_count) > 0
            ? Number(report.selected_items_count)
            : (Array.isArray(report?.selected_item_ids) ? report.selected_item_ids.length : 0),
          delivered_quantity_total: Number(report?.delivered_quantity_total) > 0
            ? Number(report.delivered_quantity_total)
            : (Array.isArray(report?.selected_item_ids)
                ? report.selected_item_ids.reduce(
                    (sum, item) => sum + (Number(item?.delivered_quantity) || 0),
                    0
                  )
                : 0),
          can_edit: this.can_manage_iar_reports && ["Generated", "Pending"].includes(status_name),
          can_update_status: this.can_manage_iar_reports && can_update_status,
          can_revert_status: this.can_manage_iar_reports && can_revert_status,
          status_label: this.resolve_report_status_label(report),
          status_variant: this.resolve_report_status_variant(report),
          description: this.resolve_report_description(report),
        };
      });
    },
    show_action_column() {
      return this.iar_reports.some((report) => {
        return report.can_edit || report.can_update_status || report.can_revert_status || report.can_print;
      });
    },
    empty_state_title() {
      return this.is_employee_only_role
        ? "No Inspection Reports Available Yet"
        : "No Generated IAR Reports Yet";
    },
    empty_state_description() {
      if (this.is_employee_only_role) {
        return "Once an IAR report is generated for this purchase order, it will appear in this list.";
      }

      return "Generate the first IAR report from the delivered items, and it will appear in this list.";
    },
    normalized_purchase_order_status() {
      const status_name = String(this.purchase_order?.status?.name || "").trim();

      if (
        [
          "Items Delivered",
          "PO Items Delivered",
          "Partially Delivered/For Inspection",
          "PO Delivered/For Inspection",
          "PO Partially Delivered/For Inspection",
          "Items Partially Delivered",
          "PO Items Partially Delivered",
        ].includes(status_name)
      ) {
        return "Items Delivered";
      }

      if (
        ["PO Conformed", "Partially Conformed", "PO Partially Conformed"].includes(status_name)
      ) {
        return "Conformed";
      }

      return status_name;
    },
  },
  methods: {
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
    format_date_time(value) {
      if (!value) {
        return "-";
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
    resolve_report_status_label(report) {
      const status_name = String(report?.status?.name || "").trim();

      if (status_name === "Completed") {
        return "Inspected/Completed";
      }

      if (["Generated", "Pending"].includes(status_name)) {
        return "Generated";
      }

      return status_name || "Generated";
    },
    resolve_report_status_variant(report) {
      const label = this.resolve_report_status_label(report);

      if (label === "Inspected/Completed") {
        return "success";
      }

      return "secondary";
    },
    resolve_report_description(report) {
      const count = Number(report?.selected_items_count) || 0;
      const quantity = Number(report?.delivered_quantity_total || 0);

      if (!count) {
        return "No delivered items selected yet.";
      }

      if (quantity <= 0) {
        return `${count} delivered item${count === 1 ? "" : "s"} included in this report.`;
      }

      return `${count} delivered item${count === 1 ? "" : "s"} with ${this.format_quantity(quantity)} total quantity included in this report.`;
    },
    print_iar_report(report) {
      if (!report?.can_print || !this.purchase_order?.id) {
        return;
      }

      this.$emit("print-iar", report);
    },
  },
};
</script>
