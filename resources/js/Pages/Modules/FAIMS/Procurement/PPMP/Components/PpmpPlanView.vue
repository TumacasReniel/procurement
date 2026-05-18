<template>
  <div class="card-body ppmp-view-body">
    <div class="plan-detail-banner mb-3 plan-detail-banner--ppmp">
      <div>
        <div class="plan-detail-banner__eyebrow">End-user unit plan</div>
        <div class="plan-detail-banner__title">Project Procurement Management Plan</div>
        <div class="plan-detail-banner__copy">{{ planDescription }}</div>
        <div v-if="ppmp.can_submit_final" class="plan-detail-banner__hint">
          {{ actionHint }}
        </div>

      </div>
      <div class="plan-detail-banner__meta">
        <div>
          <span>{{ formatCurrency(ppmp.estimated_budget) }}</span>
          <small>Total Budget</small>
        </div>
        <div>
          <span>{{ ppmp.items_count || 0 }}</span>
          <small>Items</small>
        </div>
        <div>
          <span>{{ ppmp.source_of_funds || ppmp.fund_cluster?.name || "-" }}</span>
          <small>Fund Source</small>
        </div>
      </div>
    </div>

    <div class="row g-3 mb-3">
      <div class="col-xl-4 col-md-6">
        <div class="overview-box">
          <i class="ri-file-text-line overview-icon"></i>
          <span class="overview-label">PR No.</span>
          <span class="overview-value">{{ ppmp.pr_no || ppmp.code || "-" }}</span>
        </div>
      </div>
      <div class="col-xl-4 col-md-6">
        <div class="overview-box">
          <i class="ri-list-check-3 overview-icon"></i>
          <span class="overview-label">Items</span>
          <span class="overview-value">{{ ppmp.items_count || 0 }}</span>
        </div>
      </div>
      <div class="col-xl-4 col-md-6">
        <div class="overview-box">
          <i class="ri-money-dollar-circle-line overview-icon"></i>
          <span class="overview-label">Total ABC</span>
          <span class="overview-value text-primary">{{ formatCurrency(ppmp.estimated_budget) }}</span>
        </div>
      </div>
    </div>

    <div class="row g-3">
      <div class="col-lg-8">
        <div class="section-panel">
          <div class="section-heading compact">
            <h6 class="mb-0 fs-14">Plan Information</h6>
          </div>
          <div class="row g-3">
            <div class="col-md-6">
              <div class="info-row">
                <span>Unit</span>
                <strong>{{ ppmp.unit?.name || "-" }}</strong>
              </div>
            </div>
            <div class="col-md-6">
              <div class="info-row">
                <span>Division</span>
                <strong>{{ ppmp.division?.name || "-" }}</strong>
              </div>
            </div>
          
          </div>
        </div>
      </div>

      <div class="col-lg-4">
        <div class="section-panel h-100">
          <div class="section-heading compact">
            <h6 class="mb-0 fs-14">Review and Approval</h6>
          </div>
          <div class="signatory-list">
            <div v-if="showReviewAction">
              <span>{{ ppmp.ppmp_status === "Reviewed/For Submission" ? "Reviewed By" : "Latest Action By" }}</span>
              <strong>{{ ppmp.reviewed_by || "-" }}</strong>
              <small v-if="ppmp.reviewed_at" class="text-muted">
                {{ formatDate(ppmp.reviewed_at) }}
              </small>
            </div>
            <div v-if="showFinalAction">
              <span>{{ finalActionLabel }}</span>
              <strong>{{ finalActionUser }}</strong>
              <small v-if="finalActionDate" class="text-muted">
                {{ formatDate(finalActionDate) }}
              </small>
            </div>
            <div v-if="!showReviewAction && !showFinalAction">
              <span>Status</span>
              <strong>{{ ppmp.ppmp_status || "Pending" }}</strong>
            </div>
          </div>

        </div>
      </div>
    </div>

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-3 mb-3">
      <div class="ppmp-view-tabs">
        <button
          type="button"
          class="ppmp-view-tab"
          :class="{ active: activeTab === 'document' }"
          @click="activeTab = 'document'"
        >
          PPMP Format
        </button>
        <button
          type="button"
          class="ppmp-view-tab"
          :class="{ active: activeTab === 'prs' }"
          @click="activeTab = 'prs'"
        >
          Purchase Requests
        </button>
      </div>

      <div class="d-flex flex-wrap align-items-center gap-2">
        <b-button
          v-if="canAddDraftItem"
          variant="success"
          size="sm"
          @click="$emit('add-item')"
        >
          <i class="ri-add-line align-bottom me-1"></i>
          Add Item
        </b-button>
      </div>
    </div>

    <div v-if="activeTab === 'document'" class="section-panel mt-3">
      <div class="section-heading">
        <h6 class="mb-0 fs-14"></h6>
        <div class="d-flex flex-wrap align-items-center justify-content-end gap-2">
          <span class="text-muted fs-12">
            {{ ppmp.items_count || 0 }} item{{ (ppmp.items_count || 0) === 1 ? "" : "s" }}
          </span>
        </div>
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
            <col v-if="canEditIndicativeItems" class="ppmp-col-actions" />
          </colgroup>
          <thead class="table-light">
            <tr class="fs-12 text-center ppmp-document-group-header">
              <th colspan="5">PROCUREMENT PROJECT DETAILS</th>
              <th colspan="3">PROJECTED TIMELINE (MM/YYYY)</th>
              <th colspan="2">FUNDING DETAILS</th>
              <th rowspan="2">ATTACHED SUPPORTING DOCUMENTS</th>
              <th rowspan="2">REMARKS</th>
              <th rowspan="2">ITEM STATUS</th>
              <th v-if="canEditIndicativeItems" rowspan="3" style="width: 70px"></th>
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

              <td>
                {{ item.recommended_mode_of_procurement || ppmp.recommended_mode_of_procurement || "-" }}
              </td>

              <td class="text-center">
                {{ item.pre_procurement_conference || "No" }}
              </td>

              <td class="text-center">
                {{ formatPrintDate(ppmp.start_of_procurement_activity || ppmp.date) }}
              </td>

              <td class="text-center">
                {{ formatPrintDate(item.end_of_procurement_activity) }}
              </td>

              <td class="text-center">
                {{ formatPrintDate(item.expected_delivery_date) }}
              </td>

              <td class="text-center">
                {{ ppmp.source_of_funds || ppmp.fund_cluster?.name || "-" }}
              </td>

              <td class="text-end fw-semibold">
                {{ formatCurrency(item.abc) }}
              </td>

              <td v-if="item.supportRowspan" :rowspan="item.supportRowspan" class="text-center ppmp-entry-cell">
                <div>{{ item.attached_supporting_documents || "-" }}</div>
                <a
                  v-if="item.supporting_document_url"
                  :href="item.supporting_document_url"
                  target="_blank"
                  rel="noopener"
                  class="detail-attachment"
                >
                  <i class="ri-attachment-2 align-bottom me-1"></i>
                  {{ item.supporting_document_original_name || "View attachment" }}
                </a>
              </td>

              <td v-if="item.supportRowspan" :rowspan="item.supportRowspan" class="text-center ppmp-entry-cell">
                {{ item.remarks || "-" }}
              </td>

              <td class="text-center">
                <b-badge :variant="itemStatusVariant(item)">
                  {{ itemStatus(item) }}
                </b-badge>
              </td>

              <td v-if="canEditIndicativeItems" class="text-center">
                <div class="d-flex justify-content-center gap-1">
                  <b-button
                    type="button"
                    variant="success"
                    size="sm"
                    class="btn-icon"
                    style="border-radius: 8px;"
                    @click="$emit('edit-item', item)"
                  >
                    <i class="ri-edit-2-line"></i>
                  </b-button>
                  <b-button
                    type="button"
                    variant="danger"
                    size="sm"
                    class="btn-icon"
                    style="border-radius: 8px;"
                    @click="$emit('delete-item', item)"
                  >
                    <i class="ri-delete-bin-line"></i>
                  </b-button>
                </div>
              </td>
            </tr>
            <tr v-if="!ppmp.item_details?.length">
              <td :colspan="canEditIndicativeItems ? 14 : 13" class="text-center text-muted py-4">No items found.</td>
            </tr>
          </tbody>
          <tfoot>
            <tr>
              <th colspan="9" class="text-end">Total Budget</th>
              <th class="text-end">{{ formatCurrency(ppmp.estimated_budget) }}</th>
              <th colspan="3"></th>
              <th v-if="canEditIndicativeItems"></th>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>

    <div v-else class="section-panel mt-3">
      <div class="section-heading">
        <h6 class="mb-0 fs-14">Created Purchase Requests</h6>
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
              <th style="width: 16%" class="text-end">ABC</th>
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
              <td class="text-end fw-semibold">{{ formatCurrency(request.total_amount) }}</td>
              <td class="text-center">
                <b-button variant="soft-primary" size="sm" @click="openPrItems(request)">
                  <i class="ri-eye-line align-bottom me-1"></i>
                  View
                </b-button>
              </td>
            </tr>
            <tr v-if="!purchaseRequests.length">
              <td colspan="5" class="text-center text-muted py-4">
                No created purchase requests found for this PPMP.
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
          <div class="col-md-6">
            <div class="border rounded p-2 h-100">
              <small class="text-muted d-block">PR No.</small>
              <span class="fw-semibold">{{ selectedRequest.pr_no || "-" }}</span>
            </div>
          </div>
          <div class="col-md-6">
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
export default {
  props: {
    ppmp: { type: Object, required: true },
    canAddDraftItem: { type: Boolean, default: false },
    canEditIndicativeItems: { type: Boolean, default: false },
  },
  emits: ["add-item", "edit-item", "delete-item", "advance-status"],
  data() {
    return {
      activeTab: "document",
      selectedRequest: null,
      showPrItemsModal: false,
    };
  },
  computed: {
    planDescription() {
      return `Project procurement management plan for ${this.ppmp.unit?.name || "unit"}`;
    },
    
    prNumbers() {
      return String(this.ppmp.pr_no || this.ppmp.code || "")
        .split(",")
        .map((prNo) => prNo.trim())
        .filter(Boolean);
    },
    purchaseRequests() {
      const requests = new Map();

      (this.ppmp.raw_item_details || this.ppmp.item_details || []).forEach((item) => {
        const prNumbers = this.itemPrNumbers(item);

        if (!prNumbers.length) {
          return;
        }

        prNumbers.forEach((prNo) => {
        const request = requests.get(prNo) || {
          pr_no: prNo,
          item_names: [],
          items: [],
          items_count: 0,
          total_amount: 0,
        };

        if (item.name) {
          request.item_names.push(item.name);
        }

        request.items.push(item);
        request.items_count += 1;
        request.total_amount += Number(item.abc || 0);
        requests.set(prNo, request);
        });
      });

      return Array.from(requests.values()).map((request) => ({
        ...request,
        item_names: request.item_names.slice(0, 3).join(", ") + (request.item_names.length > 3 ? "..." : ""),
      })).sort((first, second) => String(first.pr_no).localeCompare(String(second.pr_no)));
    },
    currentRoles() {
      return Array.isArray(this.$page?.props?.roles) ? this.$page.props.roles : [];
    },
    isBudgetOfficer() {
      return this.currentRoles.includes("Budget Officer");
    },
    isAdministrator() {
      return this.currentRoles.includes("Administrator");
    },
    isProcurementOfficer() {
      return this.currentRoles.includes("Procurement Officer");
    },
    isPendingPpmp() {
      return this.ppmp.ppmp_status === "Pending";
    },
    isReviewedForSubmission() {
      return this.ppmp.ppmp_status === "Reviewed/For Submission";
    },
    canShowAdvanceAction() {
      if (!this.ppmp.can_submit_final) {
        return false;
      }

      if (this.isAdministrator) {
        return true;
      }

      if (this.isReviewedForSubmission) {
        return this.isProcurementOfficer;
      }

      if (this.isPendingPpmp) {
        return this.isBudgetOfficer;
      }

      return false;
    },
    actionHint() {
      if (this.isReviewedForSubmission) {
        return "Submit this reviewed PPMP for BAC consolidation when the unit plan is ready.";
      }

      return "Review this pending PPMP to move it to Reviewed/For Submission.";
    },
    advanceActionLabel() {
      if (this.isReviewedForSubmission) {
        return "Submit for Consolidation";
      }

      return "Review";
    },

    finalActionLabel() {
      if (this.isConsolidated) {
        return "Consolidated By";
      }

      return "Submitted By";
    },

    finalActionUser() {
      if (this.isConsolidated) {
        return this.ppmp.consolidated_by || this.ppmp.approved_by || "-";
      }

      return this.ppmp.submitted_by || this.ppmp.approved_by || "-";
    },
    finalActionDate() {
      if (this.isConsolidated) {
        return this.ppmp.consolidated_at || this.ppmp.approved_at;
      }

      return this.ppmp.submitted_at || this.ppmp.approved_at;
    },
    isConsolidated() {
      return ["Consolidated/Added to APP", "Consolidated/Added to SPP", "Consolidated/Added to PPMP"].includes(this.ppmp.ppmp_status);
    },
    showReviewAction() {
      return ["Reviewed/For Submission", "Submitted/For Consolidation", "Consolidated/Added to APP", "Consolidated/Added to SPP", "Consolidated/Added to PPMP"].includes(this.ppmp.ppmp_status);
    },
    showFinalAction() {
      return ["Submitted/For Consolidation", "Consolidated/Added to APP", "Consolidated/Added to SPP", "Consolidated/Added to PPMP"].includes(this.ppmp.ppmp_status);
    },
    groupedItemRows() {
      const rows = this.ppmp.item_details || [];
      const entryGroups = new Map();

      rows.forEach((item) => {
        const entryKey = [
          this.cleanValue(this.ppmp.general_description_objective || this.ppmp.title || this.ppmp.purpose),
          this.cleanValue(item.project_type || this.ppmp.type_of_project),
        ].join("|");
        const supportKey = [
          this.cleanValue(item.attached_supporting_documents),
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
    formatQuantity(value) {
      const amount = Number(value || 0);
      return Number.isInteger(amount) ? amount.toString() : amount.toFixed(2);
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
    formatPrintDate(value) {
      if (!value) {
        return "-";
      }

      return new Date(value).toLocaleDateString("en-US", {
        month: "short",
        day: "2-digit",
      });
    },
    cleanValue(value) {
      return this.plainText(value).replace(/\s+/g, " ").trim();
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
    itemStatus(item) {
      return this.optionText(item.status?.name ?? item.status ?? item.approval_status) || "Pending";
    },
    itemPrNumbers(item) {
      return String(item.pr_no || "")
        .split(",")
        .map((prNo) => prNo.trim())
        .filter(Boolean);
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
    plainText(value) {
      if (!value) {
        return "-";
      }

      const element = document.createElement("div");
      element.innerHTML = String(value);

      return element.textContent?.trim() || "-";
    },
  },
};
</script>

<style scoped>
.ppmp-view-tabs {
  display: inline-flex;
  gap: 4px;
  padding: 4px;
  border: 1px solid var(--ppmp-border, #e9ebec);
  border-radius: 8px;
  background: var(--ppmp-surface-soft, #f8fafc);
}

.ppmp-view-tab {
  min-height: 34px;
  padding: 6px 12px;
  border: 0;
  border-radius: 6px;
  background: transparent;
  color: var(--ppmp-muted, #6c757d);
  font-size: 13px;
  font-weight: 700;
}

.ppmp-view-tab.active {
  background: var(--ppmp-surface, #ffffff);
  color: var(--ppmp-text, #212529);
  box-shadow: 0 1px 2px rgba(15, 23, 42, .08);
}

.ppmp-document-items-table {
  min-width: 2240px;
  table-layout: fixed;
  border: 1.8px solid #000;
  border-collapse: collapse;
}

.ppmp-col-description { width: 260px; }
.ppmp-col-type { width: 180px; }
.ppmp-col-quantity { width: 430px; }
.ppmp-col-mode { width: 210px; }
.ppmp-col-conference { width: 160px; }
.ppmp-col-date { width: 135px; }
.ppmp-col-funds { width: 170px; }
.ppmp-col-budget { width: 190px; }
.ppmp-col-docs { width: 210px; }
.ppmp-col-remarks { width: 190px; }
.ppmp-col-status { width: 130px; }
.ppmp-col-actions { width: 90px; }

.ppmp-document-items-table th,
.ppmp-document-items-table td {
  border: 1px solid #000;
  padding: 3px 4px;
  vertical-align: top;
  overflow-wrap: anywhere;
  word-wrap: break-word;
  hyphens: auto;
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
