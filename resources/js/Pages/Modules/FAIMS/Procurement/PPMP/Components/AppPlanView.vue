<template>
  <div class="card-body ppmp-view-body">
    <div class="app-view-toolbar mb-3">
      <div class="app-view-tabs">
        <button
          type="button"
          class="app-view-tab"
          :class="{ active: activeTab === 'document' }"
          @click="activeTab = 'document'"
        >
          {{ planShortName }} Format
        </button>
        <button
          type="button"
          class="app-view-tab"
          :class="{ active: activeTab === 'prs' }"
          @click="activeTab = 'prs'"
        >
          Purchase Requests
        </button>
      </div>

      <div class="app-view-filters">
        <select v-model="selectedPpmp" class="form-select form-select-sm">
          <option value="">All PPMPs</option>
          <option v-for="source in filteredPpmpOptions" :key="source.value" :value="source.value">
            {{ source.label }}
          </option>
        </select>
      </div>
    </div>

    <div v-if="activeTab === 'document'" class="ppmp-print-area app-document-view">
      <div class="ppmp-title-block">
        <div class="fw-bold fs-22">
          {{ planLongName.toUpperCase() }}
        </div>
        <div class="mt-1 fw-semibold">
          {{ planShortName }} NO.
          <span class="ppmp-line">{{ ppmp.ppmp_no || ppmp.code || "" }}</span>
        </div>
        <div class="text-muted fs-12 mt-2">{{ planDescription }}</div>
      </div>

      <div class="app-table-meta">
        <div>Fiscal Year : {{ fiscalYear }}</div>
        <div>End-User or Implementing Unit: {{ ppmp.unit?.name || "Agency-wide" }}</div>
      </div>

      <div class="table-responsive ppmp-table-wrap">
        <table class="table table-bordered align-middle mb-0 ppmp-items-table ppmp-document-items-table">
          <colgroup>
            <col class="ppmp-col-description" />
            <col class="ppmp-col-type" />
            <col class="ppmp-col-quantity" />
            <col class="ppmp-col-mode" />
            <col class="ppmp-col-conference" />
            <col class="ppmp-col-date" />
            <col class="ppmp-col-date" />
            <col class="ppmp-col-date" />
            <col class="ppmp-col-funds" />
            <col class="ppmp-col-budget" />
            <col class="ppmp-col-docs" />
            <col class="ppmp-col-remarks" />
            <col class="ppmp-col-status" />
          </colgroup>
          <thead class="table-light">
            <tr class="fs-12 text-center ppmp-document-group-header">
              <th colspan="5">PROCUREMENT PROJECT DETAILS</th>
              <th colspan="3">PROJECTED TIMELINE (MM/YYYY)</th>
              <th colspan="2">FUNDING DETAILS</th>
              <th rowspan="2">ATTACHED SUPPORTING DOCUMENTS</th>
              <th rowspan="2">REMARKS</th>
              <th rowspan="2">ITEM STATUS</th>
            </tr>
            <tr class="fs-12 text-center">
              <th>General Description and Objective of the Project to be Procured</th>
              <th>Type of the Project to be Procured</th>
              <th>Quantity and Size of the Project to be Procured</th>
              <th>Recommended Mode of Procurement</th>
              <th>Pre-Procurement Conference, if applicable</th>
              <th>Start of Procurement Activity</th>
              <th>End of Procurement Activity</th>
              <th>Expected Delivery/Implementation Period</th>
              <th>Source of Funds</th>
              <th>Estimated Budget / Authorized Budgetary Allocation (PHP)</th>
            </tr>
            <tr class="fs-12 text-center ppmp-column-number-row">
              <th>Column 1</th>
              <th>Column 2</th>
              <th>Column 3</th>
              <th>Column 4</th>
              <th>Column 5</th>
              <th>Column 6</th>
              <th>Column 7</th>
              <th>Column 8</th>
              <th>Column 9</th>
              <th>Column 10</th>
              <th>Column 11</th>
              <th>Column 12</th>
              <th>Column 13</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(item, index) in groupedItemRows" :key="item.id || index">
              <td v-if="item.entryRowspan" :rowspan="item.entryRowspan" class="ppmp-entry-cell">
                {{ ppmp.general_description_objective || ppmp.title || ppmp.purpose || "-" }}
              </td>

              <td v-if="item.entryRowspan" :rowspan="item.entryRowspan" class="text-center ppmp-entry-cell">
                {{ item.project_type || ppmp.type_of_project || "-" }}
              </td>

              <td>
                <div>
                  &bull; {{ formatQuantity(item.quantity) }} {{ item.unit || "" }}
                  <span class="fw-semibold">{{ item.name || "-" }}</span>
                </div>
                <div class="text-muted small mt-1 item-description">{{ plainText(item.description) }}</div>
                <small v-if="item.consolidated_count > 1" class="text-muted d-block mt-1">
                  {{ item.consolidated_count }} matching items
                </small>
                <small v-if="item.pr_no || item.ppmp_no" class="text-muted d-block mt-1">
                  {{ [item.pr_no, item.ppmp_no].filter(Boolean).join(" / ") }}
                </small>
              </td>

              <td>
                {{ item.recommended_mode_of_procurement || ppmp.recommended_mode_of_procurement || "-" }}
              </td>

              <td class="text-center">
                {{ item.pre_procurement_conference || ppmp.pre_procurement_conference || "No" }}
              </td>

              <td class="text-center">
                {{ formatPrintDate(ppmp.start_of_procurement_activity || ppmp.date) }}
              </td>

              <td class="text-center">
                {{ formatPrintDate(item.end_of_procurement_activity || ppmp.end_of_procurement_activity) }}
              </td>

              <td class="text-center">
                {{ formatPrintDate(item.expected_delivery_date || ppmp.expected_delivery_implementation_period) }}
              </td>

              <td class="text-center">
                {{ ppmp.source_of_funds || ppmp.fund_cluster?.name || "-" }}
              </td>

              <td class="text-end fw-semibold">
                {{ formatCurrency(item.abc) }}
              </td>

              <td v-if="item.supportRowspan" :rowspan="item.supportRowspan" class="text-center ppmp-entry-cell">
                <div>{{ item.attached_supporting_documents || item.supporting_document_original_name || "-" }}</div>
              </td>

              <td v-if="item.supportRowspan" :rowspan="item.supportRowspan" class="text-center ppmp-entry-cell">
                {{ item.remarks || "-" }}
              </td>

              <td class="text-center">
                <b-badge :variant="itemStatusVariant(item)">
                  {{ itemStatus(item) }}
                </b-badge>
              </td>
            </tr>
            <tr v-if="!groupedItemRows.length">
              <td colspan="13" class="text-center text-muted">No consolidated items found.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div v-else class="app-pr-list-view">
      <div class="section-heading">
        <h6 class="mb-0 fs-14"></h6>
        <span class="text-muted fs-12">
          {{ purchaseRequests.length }} PR{{ purchaseRequests.length === 1 ? "" : "s" }}
        </span>
      </div>
      <div class="table-responsive ppmp-table-wrap">
        <table class="table align-middle table-hover mb-0">
          <thead class="table-light">
            <tr class="fs-12">
              <th style="width: 4%" class="text-center">#</th>
              <th style="width: 18%">PR No.</th>
              <th>Items</th>
              <th style="width: 20%">Source PPMP</th>
              <th style="width: 14%" class="text-end">ABC</th>
              <th style="width: 90px" class="text-center">Action</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(request, index) in purchaseRequests" :key="request.pr_no || index">
              <td class="text-center fw-semibold">{{ index + 1 }}</td>
              <td class="fw-semibold text-primary">{{ request.pr_no || "-" }}</td>
              <td>
                <div class="fw-medium">{{ request.items_count }} item{{ request.items_count === 1 ? "" : "s" }}</div>
                <small class="text-muted">{{ request.item_names || "-" }}</small>
              </td>
              <td>{{ request.ppmp_no || "-" }}</td>
              <td class="text-end fw-semibold">{{ formatCurrency(request.total_amount) }}</td>
              <td class="text-center">
                <b-button variant="soft-primary" size="sm" @click="openPrItems(request)">
                  <i class="ri-eye-line align-bottom me-1"></i>
                  View
                </b-button>
              </td>
            </tr>
            <tr v-if="!purchaseRequests.length">
              <td colspan="6" class="text-center text-muted py-4">No purchase requests found.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <b-modal
      v-model="showPrItemsModal"
      size="xl"
      centered
      hide-footer
      :title="`PR Items - ${selectedRequest?.pr_no || ''}`"
    >
      <div v-if="selectedRequest">
        <div class="row g-2 mb-3">
          <div class="col-md-4">
            <div class="border rounded p-2 h-100">
              <small class="text-muted d-block">PR No.</small>
              <span class="fw-semibold">{{ selectedRequest.pr_no || "-" }}</span>
            </div>
          </div>
          <div class="col-md-4">
            <div class="border rounded p-2 h-100">
              <small class="text-muted d-block">Source PPMP</small>
              <span class="fw-semibold">{{ selectedRequest.ppmp_no || "-" }}</span>
            </div>
          </div>
          <div class="col-md-4">
            <div class="border rounded p-2 h-100">
              <small class="text-muted d-block">Total ABC</small>
              <span class="fw-semibold">{{ formatCurrency(selectedRequest.total_amount) }}</span>
            </div>
          </div>
        </div>

        <div class="table-responsive">
          <table class="table table-bordered align-middle mb-0">
            <thead class="table-light">
              <tr class="fs-12 text-center">
                <th style="width: 4%">#</th>
                <th style="width: 18%">Item</th>
                <th>Description</th>
                <th style="width: 10%">Qty</th>
                <th style="width: 10%">Unit</th>
                <th style="width: 14%">Unit Price</th>
                <th style="width: 14%">ABC</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(item, index) in selectedRequest.items" :key="item.id || index">
                <td class="text-center">{{ index + 1 }}</td>
                <td class="fw-semibold">{{ item.name || "-" }}</td>
                <td>
                  <div>{{ plainText(item.description) }}</div>
                  <small v-if="item.ppmp_no" class="text-muted d-block mt-1">
                    {{ item.ppmp_no }}
                  </small>
                </td>
                <td class="text-end">{{ formatQuantity(item.quantity) }}</td>
                <td>{{ item.unit || "-" }}</td>
                <td class="text-end">{{ formatCurrency(item.unit_price) }}</td>
                <td class="text-end fw-semibold">{{ formatCurrency(item.abc) }}</td>
              </tr>
            </tbody>
            <tfoot>
              <tr>
                <th colspan="6" class="text-end">Total ABC</th>
                <th class="text-end">{{ formatCurrency(selectedRequest.total_amount) }}</th>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </b-modal>
  </div>
</template>

<script>
export default {
  props: {
    ppmp: { type: Object, required: true },
    dropdowns: { type: Object, default: () => ({}) },
    planKind: { type: String, default: "APP" },
  },
  data() {
    return {
      activeTab: "document",
      selectedPpmp: "",
      selectedRequest: null,
      showPrItemsModal: false,
    };
  },
  computed: {
    sourcePpmps() {
      return this.ppmp.source_ppmps || [];
    },
    ppmpOptions() {
      return this.sourcePpmps
        .map((source) => ({
          value: source.ppmp_no,
          label: [source.ppmp_no, this.optionText(source.unit)].filter(Boolean).join(" - "),
          unitValue: this.optionText(source.unit_id || source.unit || ""),
        }))
        .filter((source) => source.value);
    },
    filteredPpmpOptions() {
      return this.ppmpOptions;
    },
    consolidatedItems() {
      return (this.ppmp.item_details || []).filter((item) => this.matchesSelectedSource(item));
    },
    groupedItemRows() {
      const rows = this.consolidatedItems;
      const entryGroups = new Map();

      rows.forEach((item) => {
        const entryKey = [
          this.cleanValue(this.ppmp.general_description_objective || this.ppmp.title || this.ppmp.purpose),
          this.cleanValue(item.project_type || this.ppmp.type_of_project),
        ].join("|");
        const supportKey = [
          this.cleanValue(item.attached_supporting_documents || item.supporting_document_original_name),
          this.cleanValue(item.remarks),
        ].join("|");

        if (!entryGroups.has(entryKey)) {
          entryGroups.set(entryKey, new Map());
        }

        const supportGroups = entryGroups.get(entryKey);

        if (!supportGroups.has(supportKey)) {
          supportGroups.set(supportKey, []);
        }

        supportGroups.get(supportKey).push(item);
      });

      return Array.from(entryGroups.values()).flatMap((supportGroups) => {
        const entryItems = Array.from(supportGroups.values()).flat();
        let isFirstEntryRow = true;

        return Array.from(supportGroups.values()).flatMap((supportItems) => supportItems.map((item, index) => {
          const row = {
            ...item,
            entryRowspan: isFirstEntryRow ? entryItems.length : 0,
            supportRowspan: index === 0 ? supportItems.length : 0,
          };

          isFirstEntryRow = false;

          return row;
        }));
      });
    },
    purchaseRequests() {
      const requests = new Map();

      (this.ppmp.raw_item_details || this.ppmp.item_details || [])
        .filter((item) => this.matchesSelectedSource(item))
        .forEach((item) => {
        const prNo = item.pr_no || "-";
        const request = requests.get(prNo) || {
          pr_no: prNo,
          ppmp_nos: new Set(),
          item_names: [],
          items: [],
          items_count: 0,
          total_amount: 0,
        };

        if (item.ppmp_no) {
          request.ppmp_nos.add(item.ppmp_no);
        }

        if (item.name) {
          request.item_names.push(item.name);
        }

        request.items.push(item);
        request.items_count += 1;
        request.total_amount += Number(item.abc || 0);
        requests.set(prNo, request);
      });

      return Array.from(requests.values()).map((request) => ({
        ...request,
        ppmp_no: Array.from(request.ppmp_nos).join(", "),
        item_names: request.item_names.slice(0, 3).join(", ") + (request.item_names.length > 3 ? "..." : ""),
      }));
    },
    planShortName() {
      return this.planKind === "SPP" ? "SPP" : "APP";
    },
    planLongName() {
      return this.planKind === "SPP"
        ? "Supplemental Procurement Plan"
        : "Annual Procurement Plan";
    },
    planDescription() {
      return this.planKind === "SPP"
        ? "Agency-prepared update to the approved APP for new needs or budget changes"
        : "Agency-wide consolidated annual procurement plan";
    },
    fiscalYear() {
      const value = this.ppmp.start_of_procurement_activity || this.ppmp.date;

      if (!value) {
        return new Date().getFullYear();
      }

      return new Date(value).getFullYear();
    },
  },
  methods: {
    openPrItems(request) {
      this.selectedRequest = request;
      this.showPrItemsModal = true;
    },
    formatCurrency(value) {
      return new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
      }).format(Number(value || 0));
    },
    formatDate(value) {
      if (!value) {
        return "-";
      }

      return new Date(value).toLocaleDateString("en-US", {
        year: "numeric",
        month: "short",
        day: "numeric",
      });
    },
    formatMonthYear(value) {
      if (!value) {
        return "-";
      }

      return new Date(value).toLocaleDateString("en-US", {
        month: "2-digit",
        year: "numeric",
      });
    },
    formatPrintDate(value) {
      if (!value) {
        return "-";
      }

      return new Date(value).toLocaleDateString("en-US", {
        month: "short",
        day: "2-digit",
      });
    },
    formatQuantity(value) {
      const number = Number(value || 0);

      return Number.isInteger(number) ? number.toString() : number.toLocaleString(undefined, { maximumFractionDigits: 2 });
    },
    plainText(value) {
      if (!value) {
        return "-";
      }

      const element = document.createElement("div");
      element.innerHTML = String(value);

      return element.textContent?.trim() || "-";
    },
    cleanValue(value) {
      return this.plainText(value).replace(/\s+/g, " ").trim();
    },
    itemStatus(item) {
      return this.optionText(item.status?.name ?? item.status ?? item.approval_status) || "Pending";
    },
    itemStatusVariant(item) {
      const status = this.itemStatus(item).toLowerCase();

      if (status.includes("approved") || status.includes("consolidated")) {
        return "success";
      }

      if (status.includes("reviewed") || status.includes("submitted") || status.includes("for")) {
        return "warning";
      }

      if (status.includes("cancel") || status.includes("reject") || status.includes("delete")) {
        return "danger";
      }

      return "secondary";
    },
    optionText(value) {
      if (!value) {
        return "";
      }

      if (typeof value === "object") {
        return String(value.name ?? value.label ?? value.short ?? value.value ?? "");
      }

      return String(value);
    },
    normalizeOptions(options) {
      if (Array.isArray(options)) {
        return options;
      }

      if (options && typeof options === "object") {
        return Object.values(options);
      }

      return [];
    },
    itemPpmpNumbers(item) {
      return [
        ...(Array.isArray(item.source_ppmp_nos) ? item.source_ppmp_nos : []),
        item.ppmp_no,
      ]
        .flatMap((value) => String(value || "").split(","))
        .map((value) => value.trim())
        .filter(Boolean);
    },
    matchesSelectedSource(item) {
      const ppmpNumbers = this.itemPpmpNumbers(item);

      if (this.selectedPpmp && !ppmpNumbers.includes(this.selectedPpmp)) {
        return false;
      }

      return true;
    },
  },
};
</script>

<style scoped>
.app-view-tabs {
  display: inline-flex;
  gap: 4px;
  padding: 4px;
  border: 1px solid var(--ppmp-border, #e9ebec);
  border-radius: 8px;
  background: var(--ppmp-surface-soft, #f8fafc);
}

.app-view-tab {
  min-height: 34px;
  padding: 6px 12px;
  border: 0;
  border-radius: 6px;
  background: transparent;
  color: var(--ppmp-muted, #6c757d);
  font-size: 13px;
  font-weight: 700;
}

.app-view-tab.active {
  background: var(--ppmp-surface, #ffffff);
  color: var(--ppmp-text, #212529);
  box-shadow: 0 1px 2px rgba(15, 23, 42, .08);
}

.app-view-toolbar {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
}

.app-view-filters {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.app-view-filters .form-select {
  min-width: 190px;
}

.app-pr-list-view {
  border: 1px solid var(--ppmp-border, #e9ebec);
  border-radius: 8px;
  padding: 16px;
  background: var(--ppmp-surface, #ffffff);
}

.app-document-view {
  padding: 8px;
  color: var(--ppmp-text, var(--bs-body-color, #212529));
}

.ppmp-title-block {
  text-align: center;
  margin-bottom: 16px;
}

.ppmp-line {
  display: inline-block;
  min-width: 140px;
  border-bottom: 2px solid var(--ppmp-text, var(--bs-body-color, #212529));
}

.ppmp-document-items-table {
  min-width: 1770px;
  table-layout: fixed;
  border: 1.8px solid #000;
  border-collapse: collapse;
}

.ppmp-col-description { width: 190px; }
.ppmp-col-type { width: 130px; }
.ppmp-col-quantity { width: 360px; }
.ppmp-col-mode { width: 150px; }
.ppmp-col-conference { width: 120px; }
.ppmp-col-date { width: 105px; }
.ppmp-col-funds { width: 130px; }
.ppmp-col-budget { width: 155px; }
.ppmp-col-docs { width: 145px; }
.ppmp-col-remarks { width: 140px; }
.ppmp-col-status { width: 90px; }

.ppmp-document-items-table th,
.ppmp-document-items-table td {
  border: 1px solid #000;
  padding: 3px 4px;
  vertical-align: top;
  overflow-wrap: anywhere;
  word-wrap: break-word;
  hyphens: auto;
}

.app-table-meta {
  margin-bottom: 10px;
  color: var(--ppmp-text, var(--bs-body-color, #212529));
  font-size: 13px;
  font-weight: 700;
  line-height: 1.8;
}

.ppmp-document-items-table th {
  color: #000;
  font-size: 10.5px;
  font-weight: 700;
  line-height: 1.2;
  text-align: center;
  vertical-align: middle;
}

.ppmp-document-items-table td {
  color: #000;
  font-size: 11px;
  line-height: 1.25;
}

.ppmp-document-group-header th {
  padding: 8px 4px;
  font-size: 11px;
  font-weight: 800;
  line-height: 1.2;
}

.ppmp-column-number-row th {
  padding: 2px 4px 1px;
  font-size: 9.5px;
}

.ppmp-entry-cell {
  background: var(--ppmp-surface-soft, var(--bs-tertiary-bg, #f8fafc));
  color: var(--ppmp-text, var(--bs-body-color, #212529));
}

</style>
