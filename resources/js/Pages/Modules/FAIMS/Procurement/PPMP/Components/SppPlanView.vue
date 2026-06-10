<template>
  <div class="card-body ppmp-view-body">
    <div class="plan-detail-banner mb-3 plan-detail-banner--supplemental">
      <div>
        <div class="plan-detail-banner__eyebrow">Unit supplemental plan</div>
        <div class="plan-detail-banner__title">Supplemental Procurement Plan</div>
        <div class="plan-detail-banner__copy">
          Supplemental procurement plan for {{ implementingUnitLabel }}
        </div>
      </div>
      <div class="plan-detail-banner__meta">
        <div>
          <span>{{ formatCurrency(ppmp.estimated_budget || totalBudget) }}</span>
          <small>Total Budget</small>
        </div>
        <div>
          <span>{{ ppmp.items_count || consolidatedItems.length }}</span>
          <small>Items</small>
        </div>
        <div>
          <span>{{ fiscalYear }}</span>
          <small>Fiscal Year</small>
        </div>
        <div>
          <span>{{ sourcePpmps.length }}</span>
          <small>Sources</small>
        </div>
      </div>
    </div>

    <div class="app-view-toolbar mb-3">
      <div class="app-view-tabs">
        <button
          type="button"
          class="app-view-tab"
          :class="{ active: activeTab === 'document' }"
          @click="activeTab = 'document'"
        >
          SPP Format
          <span v-if="consolidatedItems.length" class="app-view-tab__badge">
            {{ consolidatedItems.length }}
          </span>
        </button>
        <button
          type="button"
          class="app-view-tab"
          :class="{ active: activeTab === 'ppmps' }"
          @click="activeTab = 'ppmps'"
        >
          Source PPMPs
          <span v-if="sourcePpmps.length" class="app-view-tab__badge app-view-tab__badge--accent">
            {{ sourcePpmps.length }}
          </span>
        </button>
        <button
          type="button"
          class="app-view-tab"
          :class="{ active: activeTab === 'prs' }"
          @click="activeTab = 'prs'"
        >
          Purchase Requests
          <span v-if="purchaseRequests.length" class="app-view-tab__badge">
            {{ purchaseRequests.length }}
          </span>
        </button>
      </div>
      <b-button variant="soft-secondary" size="sm" @click="showTimelineModal = true">
        <i class="ri-git-branch-line align-bottom me-1"></i>
        Status Timeline
        <b-badge :variant="planStatusVariant" class="ms-1">
          {{ ppmp.ppmp_status || ppmp.approval_status || "Pending" }}
        </b-badge>
      </b-button>
    </div>

    <b-modal
      v-model="showTimelineModal"
      title="Status Timeline"
      size="xl"
      centered
      hide-footer
    >
      <PlanStatusTimeline :plan="ppmp" plan-type="SPP" />
    </b-modal>

    <!-- SPP Format tab -->
    <div v-if="activeTab === 'document'" class="ppmp-print-area app-document-view">
      <div class="ppmp-title-block">
        <div class="fw-bold fs-22">SUPPLEMENTAL PROCUREMENT PLAN</div>
        <div class="mt-1 fw-semibold">
          SPP NO.
          <span class="ppmp-line">{{ ppmp.ppmp_no || ppmp.code || "" }}</span>
        </div>
        <div class="text-muted fs-12 mt-2">
          Supplemental procurement plan for {{ implementingUnitLabel }}
        </div>
      </div>

      <div class="app-table-meta">
        <div>Fiscal Year : {{ fiscalYear }}</div>
        <div>End-User or Implementing Unit: {{ implementingUnitLabel }}</div>
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
                <small v-if="item.pr_no || item.ppmp_no" class="text-muted d-block mt-1">
                  {{ [item.pr_no, item.ppmp_no].filter(Boolean).join(" / ") }}
                </small>
              </td>
              <td>{{ item.recommended_mode_of_procurement || ppmp.recommended_mode_of_procurement || "-" }}</td>
              <td class="text-center">{{ item.pre_procurement_conference || ppmp.pre_procurement_conference || "No" }}</td>
              <td class="text-center">{{ formatPrintDate(ppmp.start_of_procurement_activity || ppmp.date) }}</td>
              <td class="text-center">{{ formatPrintDate(item.end_of_procurement_activity || ppmp.end_of_procurement_activity) }}</td>
              <td class="text-center">{{ formatPrintDate(item.expected_delivery_date || ppmp.expected_delivery_implementation_period) }}</td>
              <td class="text-center">{{ ppmp.source_of_funds || ppmp.fund_cluster?.name || "-" }}</td>
              <td class="text-end fw-semibold">{{ formatCurrency(item.abc) }}</td>
              <td v-if="item.supportRowspan" :rowspan="item.supportRowspan" class="text-center ppmp-entry-cell">
                {{ item.attached_supporting_documents || item.supporting_document_original_name || "-" }}
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
              <td colspan="13" class="p-0">
                <div class="spp-empty-state">
                  <div class="spp-empty-state__icon">
                    <i class="ri-file-list-3-line"></i>
                  </div>
                  <div class="spp-empty-state__title">No SPP items yet</div>
                  <div class="spp-empty-state__copy">
                    Items will appear here once they are added to this Supplemental Procurement Plan.
                  </div>
                </div>
              </td>
            </tr>
          </tbody>
          <tfoot v-if="groupedItemRows.length">
            <tr>
              <th colspan="9" class="text-end">Total Budget</th>
              <th class="text-end">{{ formatCurrency(ppmp.estimated_budget || totalBudget) }}</th>
              <th colspan="3"></th>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>

    <!-- Source PPMPs tab -->
    <div v-else-if="activeTab === 'ppmps'" class="app-ppmp-list-view">
      <PlanSourceAccordion
        :plans="sourcePpmps"
        title="Included PPMPs/SPPs"
        count-label="plan"
        empty-text="No source PPMPs or SPPs have been consolidated into this SPP."
        @view="viewSourcePpmp"
        @print="printSourcePpmp"
      />
    </div>

    <!-- Purchase Requests tab -->
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
              <th style="width: 20%">Source</th>
              <th style="width: 14%" class="text-end">ABC</th>
              <th style="width: 90px" class="text-center">Action</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(request, index) in purchaseRequests" :key="request.pr_no || index">
              <td class="text-center fw-semibold">{{ index + 1 }}</td>
              <td class="fw-semibold text-primary">{{ request.pr_no || "-" }}</td>
              <td>
                <div class="fw-medium">
                  {{ request.items_count }} item{{ request.items_count === 1 ? "" : "s" }}
                </div>
                <small class="text-muted">{{ request.item_names || "-" }}</small>
              </td>
              <td>{{ request.ppmp_no || ppmp.ppmp_no || "-" }}</td>
              <td class="text-end fw-semibold">{{ formatCurrency(request.total_amount) }}</td>
              <td class="text-center">
                <b-button variant="soft-primary" size="sm" @click="openPrItems(request)">
                  <i class="ri-eye-line align-bottom me-1"></i>
                  View
                </b-button>
              </td>
            </tr>
            <tr v-if="!purchaseRequests.length">
              <td colspan="6" class="p-0">
                <div class="spp-empty-state">
                  <div class="spp-empty-state__icon">
                    <i class="ri-receipt-line"></i>
                  </div>
                  <div class="spp-empty-state__title">No purchase requests</div>
                  <div class="spp-empty-state__copy">
                    Purchase requests linked to this SPP will appear here.
                  </div>
                </div>
              </td>
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
              <small class="text-muted d-block">Source SPP</small>
              <span class="fw-semibold">{{ selectedRequest.ppmp_no || ppmp.ppmp_no || "-" }}</span>
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
                <td>{{ plainText(item.description) }}</td>
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
import { router } from "@inertiajs/vue3";
import PlanSourceAccordion from "./PlanSourceAccordion.vue";
import PlanStatusTimeline from "./PlanStatusTimeline.vue";

export default {
  components: {
    PlanSourceAccordion,
    PlanStatusTimeline,
  },
  props: {
    ppmp: { type: Object, required: true },
    dropdowns: { type: Object, default: () => ({}) },
  },
  data() {
    return {
      activeTab: "document",
      selectedRequest: null,
      showPrItemsModal: false,
      showTimelineModal: false,
    };
  },
  computed: {
    consolidatedItems() {
      return this.ppmp.item_details || [];
    },
    sourcePpmps() {
      return this.ppmp.source_ppmps || [];
    },
    implementingUnitLabel() {
      return this.ppmp.unit?.name || "Unit";
    },
    fiscalYear() {
      const value = this.ppmp.start_of_procurement_activity || this.ppmp.date;

      return value ? new Date(value).getFullYear() : new Date().getFullYear();
    },
    totalBudget() {
      return this.consolidatedItems.reduce(
        (total, item) => total + Number(item.abc || 0),
        0
      );
    },
    planStatusVariant() {
      const status = String(this.ppmp.ppmp_status || this.ppmp.approval_status || "").toLowerCase();

      if (status.includes("consolidated") || status.includes("approved")) {
        return "success";
      }

      if (status.includes("submitted") || status.includes("reviewed") || status.includes("for")) {
        return "warning";
      }

      return "secondary";
    },
    groupedItemRows() {
      const rows = this.consolidatedItems;
      const entryGroups = new Map();

      rows.forEach((item) => {
        const itemStartDate = this.ppmp.start_of_procurement_activity || this.ppmp.date;
        const entryScopeKey = [
          this.cleanValue(item.ppmp_no),
          this.cleanValue(item.recommended_mode_of_procurement || this.ppmp.recommended_mode_of_procurement),
          this.cleanValue(item.pre_procurement_conference || this.ppmp.pre_procurement_conference || "No"),
          this.cleanValue(itemStartDate),
          this.cleanValue(item.end_of_procurement_activity || this.ppmp.end_of_procurement_activity),
          this.cleanValue(item.expected_delivery_date || this.ppmp.expected_delivery_implementation_period),
          this.cleanValue(item.attached_supporting_documents || item.supporting_document_original_name),
          this.cleanValue(item.remarks),
        ].join("|");
        const entryKey = [
          entryScopeKey,
          this.cleanValue(this.ppmp.general_description_objective || this.ppmp.title || this.ppmp.purpose),
          this.cleanValue(item.project_type || this.ppmp.type_of_project),
        ].join("|");
        const supportKey = [
          entryScopeKey,
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

        return Array.from(supportGroups.values()).flatMap((supportItems) =>
          supportItems.map((item, index) => {
            const row = {
              ...item,
              entryRowspan: isFirstEntryRow ? entryItems.length : 0,
              supportRowspan: index === 0 ? supportItems.length : 0,
            };

            isFirstEntryRow = false;

            return row;
          })
        );
      });
    },
    purchaseRequests() {
      const requests = new Map();

      (this.ppmp.raw_item_details || this.ppmp.item_details || []).forEach((item) => {
        this.itemPurchaseRequests(item).forEach((purchaseRequest) => {
          const prNo = purchaseRequest.code || "-";
          const request = requests.get(prNo) || {
            pr_id: purchaseRequest.id || null,
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

          request.items.push({
            ...item,
            pr_id: purchaseRequest.id || item.pr_id || null,
            pr_no: prNo,
          });
          request.items_count += 1;
          request.total_amount += Number(item.abc || 0);
          requests.set(prNo, request);
        });
      });

      return Array.from(requests.values())
        .map((request) => ({
          ...request,
          ppmp_no: Array.from(request.ppmp_nos).join(", "),
          item_names:
            request.item_names.slice(0, 3).join(", ") +
            (request.item_names.length > 3 ? "..." : ""),
        }))
        .sort((first, second) => String(first.pr_no).localeCompare(String(second.pr_no)));
    },
  },
  methods: {
    viewSourcePpmp(source) {
      if (!source?.id) {
        return;
      }

      router.get(`/faims/procurement-ppmp/${source.id}`, {
        option: "view",
        plan_type: this.sourcePlanTypeLabel(source),
      });
    },
    printSourcePpmp(source) {
      if (!source?.id) {
        return;
      }

      const params = new URLSearchParams({
        option: "print",
        type: "ppmp",
        plan_type: this.sourcePlanTypeLabel(source),
      });

      window.open(`/faims/procurement-ppmp/${source.id}?${params.toString()}`, "_blank");
    },
    sourcePlanTypeLabel(source) {
      const type = String(
        source?.plan_type || source?.plan_name || source?.ppmp_no || ""
      ).toLowerCase();

      return type.includes("spp") || type.includes("supplemental") ? "SPP" : "PPMP";
    },
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

      return Number.isInteger(number)
        ? number.toString()
        : number.toLocaleString(undefined, { maximumFractionDigits: 2 });
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
    itemPurchaseRequests(item) {
      if (Array.isArray(item.purchase_requests) && item.purchase_requests.length) {
        return item.purchase_requests
          .map((request) => ({
            id: request.id,
            code: request.code,
          }))
          .filter((request) => request.code);
      }

      return String(item.pr_no || "")
        .split(",")
        .map((code) => code.trim())
        .filter(Boolean)
        .map((code) => ({
          id: item.pr_id || null,
          code,
        }));
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
  display: inline-flex;
  align-items: center;
  gap: 6px;
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
  box-shadow: 0 1px 2px rgba(15, 23, 42, 0.08);
}

.app-view-tab__badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 18px;
  height: 18px;
  padding: 0 5px;
  border-radius: 999px;
  background: rgba(100, 116, 139, 0.15);
  color: var(--ppmp-muted, #64748b);
  font-size: 10px;
  font-weight: 800;
  line-height: 1;
}

.app-view-tab.active .app-view-tab__badge {
  background: rgba(64, 81, 137, 0.12);
  color: var(--ppmp-primary, #405189);
}

.app-view-tab__badge--accent {
  background: rgba(245, 158, 11, 0.15);
  color: #b45309;
}

.app-view-tab.active .app-view-tab__badge--accent {
  background: rgba(245, 158, 11, 0.18);
  color: #92400e;
}

.app-view-toolbar {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
}

.app-pr-list-view {
  border: 1px solid var(--ppmp-border, #e9ebec);
  border-radius: 8px;
  padding: 16px;
  background: var(--ppmp-surface, #ffffff);
}

.app-ppmp-list-view {
  border: 0;
}

.app-document-view {
  padding: 8px;
  background: transparent;
  color: #000000;
  overflow-x: auto;
}

.ppmp-title-block {
  text-align: center;
  margin-bottom: 16px;
}

.ppmp-line {
  display: inline-block;
  min-width: 140px;
  border-bottom: 2px solid #000000;
}

.ppmp-document-items-table {
  width: max-content;
  min-width: 1770px;
  table-layout: fixed;
  border: 1.8px solid #000;
  border-collapse: collapse;
  background: #ffffff;
}

[data-bs-theme="dark"] .app-document-view {
  background: #ffffff;
}

.app-document-view .ppmp-table-wrap {
  overflow-x: visible;
}

.ppmp-col-description {
  width: 190px;
}
.ppmp-col-type {
  width: 130px;
}
.ppmp-col-quantity {
  width: 360px;
}
.ppmp-col-mode {
  width: 150px;
}
.ppmp-col-conference {
  width: 120px;
}
.ppmp-col-date {
  width: 105px;
}
.ppmp-col-funds {
  width: 130px;
}
.ppmp-col-budget {
  width: 155px;
}
.ppmp-col-docs {
  width: 145px;
}
.ppmp-col-remarks {
  width: 140px;
}
.ppmp-col-status {
  width: 90px;
}

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
  color: #000000;
  font-size: 13px;
  font-weight: 700;
  line-height: 1.8;
}

.ppmp-document-items-table th {
  color: #000;
  background: #f2f2f2;
  font-size: 10.5px;
  font-weight: 700;
  line-height: 1.2;
  text-align: center;
  vertical-align: middle;
}

.ppmp-document-items-table td {
  color: #000;
  background: #ffffff;
  font-size: 11px;
  line-height: 1.25;
}

.ppmp-document-items-table :deep(.badge) {
  color: #ffffff !important;
}

.ppmp-document-items-table :deep(.bg-warning),
.ppmp-document-items-table :deep(.text-bg-warning) {
  color: #111827 !important;
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
  background: #f8fafc !important;
  color: #000000 !important;
}

.ppmp-document-items-table tfoot th {
  border: 1px solid #000;
  padding: 4px;
  color: #000;
  background: #f2f2f2;
  font-size: 11px;
  font-weight: 800;
}

/* Empty state */
.spp-empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
  padding: 32px 16px;
  color: var(--ppmp-muted, #64748b);
  text-align: center;
}

.spp-empty-state__icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 48px;
  height: 48px;
  border-radius: 12px;
  background: var(--ppmp-surface-soft, #f1f5f9);
  color: #94a3b8;
  font-size: 24px;
}

.spp-empty-state__title {
  color: var(--ppmp-text, #374151);
  font-size: 13px;
  font-weight: 700;
}

.spp-empty-state__copy {
  max-width: 340px;
  color: var(--ppmp-muted, #6b7280);
  font-size: 12px;
  line-height: 1.5;
}
</style>
