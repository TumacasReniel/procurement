<template>
  <div class="card-body ppmp-view-body">
    <div class="plan-detail-banner mb-3 plan-detail-banner--annual">
      <div>
        <div class="plan-detail-banner__eyebrow">Agency consolidated plan</div>
        <div class="plan-detail-banner__title">{{ planLongName }}</div>
        <div class="plan-detail-banner__copy">{{ planDescription }}</div>
      </div>
      <div class="plan-detail-banner__meta">
        <div>
          <span>{{ formatCurrency(ppmp.estimated_budget || totalBudget) }}</span>
          <small>Total Budget</small>
        </div>
        <div>
          <span>{{ consolidatedItems.length }}</span>
          <small>Items</small>
        </div>
        <div>
          <span>{{ visibleSourcePpmps.length }}</span>
          <small>Sources</small>
        </div>
      </div>
    </div>

    <div class="app-view-toolbar mb-3">
      <div class="app-view-tabs">
        <!-- Source PPMPs/SPPs — individual unit plans that fed into this APP -->
        <button
          type="button"
          class="app-view-tab"
          :class="{ active: activeTab === 'sources' }"
          @click="activeTab = 'sources'"
        >
          <i class="ri-folders-line app-view-tab__icon"></i>
          <span>Source PPMPs/SPPs</span>
          <span class="app-view-tab__badge">{{ visibleSourcePpmps.length }}</span>
        </button>

        <!-- Visual flow indicator showing consolidation direction -->
        <div class="app-view-tabs__flow-arrow" aria-hidden="true">
          <i class="ri-arrow-right-s-line"></i>
          <span>consolidated into</span>
        </div>

        <!-- Consolidated APP — the merged agency-wide document -->
        <button
          type="button"
          class="app-view-tab app-view-tab--primary"
          :class="{ active: activeTab === 'consolidated' }"
          @click="activeTab = 'consolidated'"
        >
          <i class="ri-file-chart-2-line app-view-tab__icon"></i>
          <span>Consolidated APP</span>
          <span class="app-view-tab__badge app-view-tab__badge--accent">
            {{ consolidatedItems.length }}
          </span>
        </button>

        <div class="app-view-tabs__sep" aria-hidden="true"></div>

        <!-- Purchase Requests -->
        <button
          type="button"
          class="app-view-tab"
          :class="{ active: activeTab === 'prs' }"
          @click="activeTab = 'prs'"
        >
          <i class="ri-receipt-line app-view-tab__icon"></i>
          <span>Purchase Requests</span>
          <span v-if="purchaseRequests.length" class="app-view-tab__badge">
            {{ purchaseRequests.length }}
          </span>
        </button>
      </div>
      <b-button variant="soft-secondary" size="sm" @click="showTimelineModal = true">
        <i class="ri-git-branch-line align-bottom me-1"></i>
        Status Timeline
        <b-badge :variant="sourceStatusVariant(ppmp)" class="ms-1">
          {{ ppmp.ppmp_status || ppmp.approval_status || "Pending" }}
        </b-badge>
      </b-button>
    </div>

    <b-modal
      v-model="showTimelineModal"
      size="lg"
      centered
      hide-footer
      header-class="border-bottom pb-2"
    >
      <template #header>
        <div class="d-flex align-items-center gap-2 w-100">
          <span class="avatar-title bg-warning-subtle rounded p-2" style="width:2rem;height:2rem;display:inline-flex;align-items:center;justify-content:center;flex-shrink:0">
            <i class="ri-git-branch-line text-warning"></i>
          </span>
          <div class="flex-grow-1">
            <div class="fw-bold fs-14">APP Status Timeline</div>
            <div class="text-muted fs-12">
              {{ ppmp.code || ppmp.ppmp_no || 'Annual Procurement Plan' }}
              &nbsp;·&nbsp;
              {{ ppmp.ppmp_status || ppmp.approval_status || 'Pending' }}
            </div>
          </div>
          <button type="button" class="btn-close" @click="showTimelineModal = false"></button>
        </div>
      </template>
      <PlanStatusTimeline :plan="ppmp" :plan-type="planShortName" />
    </b-modal>

      <div v-if="activeTab === 'consolidated'" class="ppmp-print-area app-document-view">
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
        <div>End-User or Implementing Unit: {{ implementingUnitLabel }}</div>
      </div>

      <div class="table-responsive ppmp-table-wrap">
        <table
          class="table table-bordered align-middle mb-0 ppmp-items-table ppmp-document-items-table"
        >
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
              <td
                v-if="item.entryRowspan"
                :rowspan="item.entryRowspan"
                class="ppmp-entry-cell"
              >
                {{
                  ppmp.general_description_objective || ppmp.title || ppmp.purpose || "-"
                }}
              </td>

              <td
                v-if="item.entryRowspan"
                :rowspan="item.entryRowspan"
                class="text-center ppmp-entry-cell"
              >
                {{ item.project_type || ppmp.type_of_project || "-" }}
              </td>

              <td>
                <div>
                  &bull; {{ formatQuantity(item.quantity) }} {{ item.unit || "" }}
                  <span class="fw-semibold">{{ item.name || "-" }}</span>
                </div>
                <div class="text-muted small mt-1 item-description">
                  {{ plainText(item.description) }}
                </div>

                <!-- Per-unit quantity breakdown (shows when item came from multiple acquiring units) -->
                <div v-if="unitBreakdownFor(item).length" class="unit-qty-breakdown mt-2">
                  <div class="unit-qty-breakdown__label">
                    <i class="ri-building-2-line"></i> By acquiring unit
                  </div>
                  <div
                    v-for="(row, bi) in unitBreakdownFor(item)"
                    :key="bi"
                    class="unit-qty-breakdown__row"
                  >
                    <span class="unit-qty-breakdown__unit">{{ row.unit_name }}</span>
                    <span class="unit-qty-breakdown__qty">{{ formatQuantity(row.quantity) }} {{ row.unit_type }}</span>
                  </div>
                </div>

                <!-- Pricing method badge (only for non-default methods) -->
                <div
                  v-if="item.pricing_method && item.pricing_method !== 'weighted'"
                  class="item-pricing-badge mt-1"
                >
                  <i :class="item.pricing_method === 'manual' ? 'ri-pencil-line' : 'ri-scales-line'"></i>
                  {{ item.pricing_method === 'manual' ? 'Manual price' : 'Averaged price' }}
                </div>

                <!-- Price variance indicator (spread > 5% among source items) -->
                <div v-if="item.has_price_variance" class="item-price-variance mt-1">
                  <i class="ri-alert-fill"></i>
                  <span>Price spread {{ item.price_spread_rate }}%</span>
                  <span class="item-price-range">
                    ({{ formatCurrency(item.min_unit_price) }} – {{ formatCurrency(item.max_unit_price) }})
                  </span>
                </div>

                <!-- PR price mismatch: source-PPMP PRs were issued at a different unit price -->
                <div v-if="prMismatchFor(item).length" class="item-pr-mismatch mt-1">
                  <i class="ri-file-warning-line"></i>
                  <span class="item-pr-mismatch__label">PR price mismatch</span>
                  <span
                    v-for="(m, mi) in prMismatchFor(item)"
                    :key="mi"
                    class="item-pr-mismatch__entry"
                  >
                    {{ m.pr_no }}:
                    <span class="item-pr-mismatch__old">{{ formatCurrency(m.original_price) }}</span>
                    <i class="ri-arrow-right-s-line"></i>
                    <span class="item-pr-mismatch__new">{{ formatCurrency(m.consolidated_price) }}</span>
                  </span>
                </div>

                <small v-if="item.consolidated_count > 1" class="text-muted d-block mt-1">
                  {{ item.consolidated_count }} matching items
                </small>
                <small v-if="item.pr_no || item.ppmp_no" class="text-muted d-block mt-1">
                  {{ [item.pr_no, item.ppmp_no].filter(Boolean).join(" / ") }}
                </small>
              </td>

              <td>
                {{
                  item.recommended_mode_of_procurement ||
                  ppmp.recommended_mode_of_procurement ||
                  "-"
                }}
              </td>

              <td class="text-center">
                {{
                  item.pre_procurement_conference ||
                  ppmp.pre_procurement_conference ||
                  "No"
                }}
              </td>

              <td class="text-center">
                {{ formatMonthYear(ppmp.start_of_procurement_activity || ppmp.date) }}
              </td>

              <td class="text-center">
                {{
                  formatMonthYear(
                    item.end_of_procurement_activity || ppmp.end_of_procurement_activity
                  )
                }}
              </td>

              <td class="text-center">
                {{
                  formatMonthYear(
                    item.expected_delivery_date ||
                      ppmp.expected_delivery_implementation_period
                  )
                }}
              </td>

              <td class="text-center">
                {{ ppmp.source_of_funds || ppmp.fund_cluster?.name || "-" }}
              </td>

              <td class="text-end fw-semibold">
                {{ formatCurrency(item.abc) }}
              </td>

              <td
                v-if="item.supportRowspan"
                :rowspan="item.supportRowspan"
                class="text-center ppmp-entry-cell"
              >
                <div>
                  {{
                    item.attached_supporting_documents ||
                    item.supporting_document_original_name ||
                    "-"
                  }}
                </div>
              </td>

              <td
                v-if="item.supportRowspan"
                :rowspan="item.supportRowspan"
                class="text-center ppmp-entry-cell"
              >
                {{ item.remarks || "-" }}
              </td>

              <td class="text-center">
                <b-badge :variant="itemStatusVariant(item)">
                  {{ itemStatus(item) }}
                </b-badge>
              </td>
            </tr>
            <tr v-if="!groupedItemRows.length">
              <td colspan="13" class="text-center text-muted">
                No consolidated items found.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Source PPMPs/SPPs — individual unit plans before consolidation -->
    <div v-if="activeTab === 'sources'" class="app-sources-view">
      <div class="sources-intro-card mb-3">
        <div class="sources-intro-card__icon">
          <i class="ri-git-merge-line"></i>
        </div>
        <div class="sources-intro-card__body">
          <div class="sources-intro-card__title">Source PPMPs &amp; SPPs</div>
          <div class="sources-intro-card__copy">
            These are the individual procurement plans submitted by each unit before being consolidated into this {{ planShortName }}.
            Click <strong>View</strong> to open a source plan, or <strong>Print</strong> to export it.
          </div>
        </div>
        <div v-if="visibleSourcePpmps.length" class="sources-intro-card__stat">
          <span class="sources-intro-card__num">{{ visibleSourcePpmps.length }}</span>
          <span class="sources-intro-card__label">source plan{{ visibleSourcePpmps.length === 1 ? '' : 's' }}</span>
        </div>
      </div>
      <PlanSourceAccordion
        :plans="visibleSourcePpmps"
        title="Included PPMPs &amp; SPPs"
        count-label="plan"
        empty-text="No PPMPs or SPPs have been consolidated into this plan yet."
        @view="viewSourcePpmp"
        @print="printSourcePpmp"
      />
    </div>

    <div v-if="activeTab === 'prs'" class="app-pr-list-view">
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
            <tr
              v-for="(request, index) in purchaseRequests"
              :key="request.pr_no || index"
            >
              <td class="text-center fw-semibold">{{ index + 1 }}</td>
              <td class="fw-semibold text-primary">{{ request.pr_no || "-" }}</td>
              <td>
                <div class="fw-medium">
                  {{ request.items_count }} item{{ request.items_count === 1 ? "" : "s" }}
                </div>
                <small class="text-muted">{{ request.item_names || "-" }}</small>
              </td>
              <td>{{ request.ppmp_no || "-" }}</td>
              <td class="text-end fw-semibold">
                {{ formatCurrency(request.total_amount) }}
              </td>
              <td class="text-center">
                <b-button variant="soft-primary" size="sm" @click="openPrItems(request)">
                  <i class="ri-eye-line align-bottom me-1"></i>
                  View
                </b-button>
              </td>
            </tr>
            <tr v-if="!purchaseRequests.length">
              <td colspan="6" class="text-center text-muted py-4">
                No purchase requests found.
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
              <small class="text-muted d-block">Source</small>
              <span class="fw-semibold">{{ selectedRequest.ppmp_no || "-" }}</span>
            </div>
          </div>
          <div class="col-md-4">
            <div class="border rounded p-2 h-100">
              <small class="text-muted d-block">Total ABC</small>
              <span class="fw-semibold">{{
                formatCurrency(selectedRequest.total_amount)
              }}</span>
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
                <th class="text-end">
                  {{ formatCurrency(selectedRequest.total_amount) }}
                </th>
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
    planKind: { type: String, default: "APP" },
  },
  data() {
    return {
      activeTab: "consolidated",
      showTimelineModal: false,
      selectedRequest: null,
      showPrItemsModal: false,
    };
  },
  computed: {
    sourcePpmps() {
      return this.ppmp.source_ppmps || [];
    },
    visibleSourcePpmps() {
      return this.sourcePpmps;
    },
    consolidatedItems() {
      return this.ppmp.item_details || [];
    },
    groupedItemRows() {
      const rows = this.consolidatedItems;
      const entryGroups = new Map();

      rows.forEach((item) => {
        const itemStartDate = this.ppmp.start_of_procurement_activity || this.ppmp.date;
        const entryScopeKey = [
          this.cleanValue(
            item.ppmp_no ||
              (Array.isArray(item.source_ppmp_nos) ? item.source_ppmp_nos.join(",") : "")
          ),
          this.cleanValue(
            item.recommended_mode_of_procurement ||
              this.ppmp.recommended_mode_of_procurement
          ),
          this.cleanValue(
            item.pre_procurement_conference ||
              this.ppmp.pre_procurement_conference ||
              "No"
          ),
          this.cleanValue(itemStartDate),
          this.cleanValue(
            item.end_of_procurement_activity || this.ppmp.end_of_procurement_activity
          ),
          this.cleanValue(
            item.expected_delivery_date ||
              this.ppmp.expected_delivery_implementation_period
          ),
          this.cleanValue(
            item.attached_supporting_documents || item.supporting_document_original_name
          ),
          this.cleanValue(item.remarks),
        ].join("|");
        const entryKey = [
          entryScopeKey,
          this.cleanValue(
            this.ppmp.general_description_objective ||
              this.ppmp.title ||
              this.ppmp.purpose
          ),
          this.cleanValue(item.project_type || this.ppmp.type_of_project),
        ].join("|");
        const supportKey = [
          entryScopeKey,
          this.cleanValue(
            item.attached_supporting_documents || item.supporting_document_original_name
          ),
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
        ? `Supplemental procurement plan for ${this.implementingUnitLabel}`
        : "Agency-wide consolidated annual procurement plan";
    },
    implementingUnitLabel() {
      return this.ppmp.unit?.name || (this.planKind === "SPP" ? "Unit" : "Agency-wide");
    },
    fiscalYear() {
      const value = this.ppmp.start_of_procurement_activity || this.ppmp.date;

      if (!value) {
        return new Date().getFullYear();
      }

      return new Date(value).getFullYear();
    },
    totalBudget() {
      return this.consolidatedItems.reduce(
        (total, item) => total + Number(item.abc || 0),
        0
      );
    },
    ppmpUnitLookup() {
      const lookup = new Map();
      (this.ppmp.source_ppmps || []).forEach((plan) => {
        const key = String(plan.ppmp_no || plan.code || "").trim();
        if (!key) return;
        const unitName =
          (plan.unit &&
            typeof plan.unit === "object" &&
            (plan.unit.name || plan.unit.label || plan.unit.short)) ||
          (typeof plan.unit === "string" ? plan.unit : "") ||
          plan.title ||
          plan.code ||
          key;
        lookup.set(key, unitName);
      });
      return lookup;
    },
    prMismatchMap() {
      // For each consolidated item with a price variance, find raw source items that had a PR
      // at a different price — those PRs are now misaligned with the consolidated unit price.
      const consolidatedByName = new Map();
      this.consolidatedItems.forEach((item) => {
        const name = String(item.name || '').toLowerCase().trim();
        if (name && item.has_price_variance) {
          consolidatedByName.set(name, Number(item.unit_price || 0));
        }
      });

      const result = new Map();
      (this.ppmp.raw_item_details || []).forEach((rawItem) => {
        const name = String(rawItem.name || '').toLowerCase().trim();
        if (!name) return;

        const consolidatedPrice = consolidatedByName.get(name);
        if (consolidatedPrice === undefined) return;

        const rawPrice = Number(rawItem.unit_price || 0);
        const prNo = String(rawItem.pr_no || '').trim();
        if (!prNo) return;

        if (!result.has(name)) result.set(name, []);
        const list = result.get(name);

        prNo.split(',').map((p) => p.trim()).filter(Boolean).forEach((pr) => {
          if (!list.some((e) => e.pr_no === pr && e.original_price === rawPrice)) {
            list.push({ pr_no: pr, original_price: rawPrice, consolidated_price: consolidatedPrice });
          }
        });
      });

      return result;
    },
    unitBreakdownMap() {
      const raw = this.ppmp.raw_item_details || [];
      const result = new Map();

      raw.forEach((rawItem) => {
        const name = String(rawItem.name || "").toLowerCase().trim();
        if (!name) return;

        const ppmpNo = String(rawItem.ppmp_no || "").trim();
        const unitName =
          (ppmpNo && this.ppmpUnitLookup.get(ppmpNo)) || ppmpNo || "Unknown Unit";
        const qty = Number(rawItem.quantity || 0);
        const unitType = rawItem.unit || "";

        if (!result.has(name)) {
          result.set(name, new Map());
        }

        const unitMap = result.get(name);
        const existing = unitMap.get(unitName);

        if (existing) {
          existing.quantity += qty;
        } else {
          unitMap.set(unitName, { unit_name: unitName, quantity: qty, unit_type: unitType });
        }
      });

      return result;
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
      return (
        this.optionText(item.status?.name ?? item.status ?? item.approval_status) ||
        "Pending"
      );
    },
    itemStatusVariant(item) {
      const status = this.itemStatus(item).toLowerCase();

      if (status.includes("approved") || status.includes("consolidated")) {
        return "success";
      }

      if (
        status.includes("reviewed") ||
        status.includes("submitted") ||
        status.includes("for")
      ) {
        return "warning";
      }

      if (
        status.includes("cancel") ||
        status.includes("reject") ||
        status.includes("delete")
      ) {
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
    sourceLabel(value) {
      return this.optionText(value);
    },
    sourceStatusVariant(source) {
      const status = String(source?.approval_status || "").toLowerCase();

      if (status.includes("consolidated") || status.includes("approved")) {
        return "success";
      }

      if (
        status.includes("submitted") ||
        status.includes("reviewed") ||
        status.includes("for")
      ) {
        return "warning";
      }

      return "secondary";
    },
    sourcePlanTypeLabel(source) {
      const type = String(
        source?.plan_type || source?.plan_name || source?.ppmp_no || ""
      ).toLowerCase();

      return type.includes("spp") || type.includes("supplemental") ? "SPP" : "PPMP";
    },
    sourcePlanTypeVariant(source) {
      return this.sourcePlanTypeLabel(source) === "SPP" ? "warning" : "primary";
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
    unitBreakdownFor(item) {
      const name = String(item.name || "").toLowerCase().trim();
      if (!name) return [];

      const unitMap = this.unitBreakdownMap.get(name);
      if (!unitMap || unitMap.size < 2) return [];

      return Array.from(unitMap.values()).sort((a, b) => b.quantity - a.quantity);
    },
    prMismatchFor(item) {
      const name = String(item.name || "").toLowerCase().trim();
      if (!name) return [];

      return this.prMismatchMap.get(name) || [];
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
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 2px;
  padding: 4px;
  border: 1px solid var(--ppmp-border, #e9ebec);
  border-radius: 10px;
  background: var(--ppmp-surface-soft, #f8fafc);
}

.app-view-tab {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  min-height: 34px;
  padding: 5px 11px;
  border: 0;
  border-radius: 6px;
  background: transparent;
  color: var(--ppmp-muted, #6c757d);
  font-size: 12.5px;
  font-weight: 700;
  white-space: nowrap;
  cursor: pointer;
  transition: background 0.12s ease, color 0.12s ease;
}

.app-view-tab__icon {
  font-size: 14px;
  line-height: 1;
  flex-shrink: 0;
}

.app-view-tab__badge {
  display: inline-flex;
  align-items: center;
  padding: 1px 7px;
  border-radius: 999px;
  background: rgba(148, 163, 184, 0.18);
  color: var(--ppmp-muted, #6c757d);
  font-size: 10px;
  font-weight: 800;
  line-height: 1.4;
  flex-shrink: 0;
}

.app-view-tab__badge--accent {
  background: rgba(64, 81, 137, 0.1);
  color: #405189;
}

.app-view-tab.active {
  background: var(--ppmp-surface, #ffffff);
  color: var(--ppmp-text, #212529);
  box-shadow: 0 1px 3px rgba(15, 23, 42, 0.1);
}

.app-view-tab--primary.active {
  background: #405189;
  color: #ffffff;
  box-shadow: 0 2px 8px rgba(64, 81, 137, 0.28);
}

.app-view-tab--primary.active .app-view-tab__badge--accent {
  background: rgba(255, 255, 255, 0.22);
  color: #ffffff;
}

/* Flow arrow between Source → Consolidated */
.app-view-tabs__flow-arrow {
  display: inline-flex;
  flex-direction: column;
  align-items: center;
  gap: 1px;
  padding: 0 4px;
  color: var(--ppmp-muted, #94a3b8);
  flex-shrink: 0;
  pointer-events: none;
}

.app-view-tabs__flow-arrow i {
  font-size: 16px;
  line-height: 1;
}

.app-view-tabs__flow-arrow span {
  font-size: 8px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  white-space: nowrap;
  line-height: 1;
}

/* Vertical separator */
.app-view-tabs__sep {
  width: 1px;
  height: 22px;
  background: var(--ppmp-border, #d1d5db);
  margin: 0 3px;
  flex-shrink: 0;
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

/* ── Sources tab ── */
.app-sources-view {
  padding: 2px 0;
}

.sources-intro-card {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 14px 18px;
  border: 1px solid var(--ppmp-border, #e9ebec);
  border-left: 3px solid #405189;
  border-radius: 10px;
  background: var(--ppmp-surface-soft, #f8fafc);
}

.sources-intro-card__icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 44px;
  height: 44px;
  border-radius: 10px;
  background: rgba(64, 81, 137, 0.1);
  color: #405189;
  font-size: 22px;
  flex-shrink: 0;
}

.sources-intro-card__body {
  flex: 1;
  min-width: 0;
}

.sources-intro-card__title {
  color: var(--ppmp-text, #212529);
  font-size: 13.5px;
  font-weight: 800;
  line-height: 1.2;
  margin-bottom: 4px;
}

.sources-intro-card__copy {
  color: var(--ppmp-muted, #6c757d);
  font-size: 12px;
  line-height: 1.45;
}

.sources-intro-card__stat {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 3px;
  padding-left: 16px;
  border-left: 1px solid var(--ppmp-border, #e9ebec);
  flex-shrink: 0;
}

.sources-intro-card__num {
  color: #405189;
  font-size: 24px;
  font-weight: 900;
  line-height: 1;
}

.sources-intro-card__label {
  color: var(--ppmp-muted, #94a3b8);
  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  white-space: nowrap;
}

[data-bs-theme="dark"] .sources-intro-card {
  background: rgba(35, 44, 58, 0.7);
}

[data-bs-theme="dark"] .sources-intro-card__icon {
  background: rgba(64, 81, 137, 0.2);
}

[data-bs-theme="dark"] .sources-intro-card__stat {
  border-left-color: rgba(148, 163, 184, 0.2);
}

[data-bs-theme="dark"] .app-view-tab--primary.active {
  background: #4b5c96;
}

[data-bs-theme="dark"] .app-view-tab__badge--accent {
  background: rgba(64, 81, 137, 0.25);
  color: #93c5fd;
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

/* ── Unit quantity breakdown in Column 3 ── */
.unit-qty-breakdown {
  border: 1px solid rgba(64, 81, 137, 0.18);
  border-left: 3px solid rgba(64, 81, 137, 0.55);
  border-radius: 6px;
  padding: 6px 8px;
  background: rgba(64, 81, 137, 0.04);
}

.unit-qty-breakdown__label {
  display: flex;
  align-items: center;
  gap: 4px;
  margin-bottom: 5px;
  color: #405189;
  font-size: 9.5px;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  line-height: 1;
}

.unit-qty-breakdown__label i {
  font-size: 11px;
}

.unit-qty-breakdown__row {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
  gap: 6px;
  padding: 2px 0;
  border-top: 1px solid rgba(64, 81, 137, 0.08);
}

.unit-qty-breakdown__row:first-of-type {
  border-top: none;
}

.unit-qty-breakdown__unit {
  color: #374151;
  font-size: 10px;
  font-weight: 600;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  max-width: 62%;
  flex-shrink: 1;
}

.unit-qty-breakdown__qty {
  color: #405189;
  font-size: 10px;
  font-weight: 800;
  white-space: nowrap;
  flex-shrink: 0;
}

/* ── Pricing method badge ── */
.item-pricing-badge {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 2px 7px;
  border: 1px solid rgba(100, 116, 139, 0.25);
  border-radius: 999px;
  background: rgba(100, 116, 139, 0.08);
  color: #64748b;
  font-size: 9.5px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.item-pricing-badge i {
  font-size: 10px;
}

/* ── Price variance indicator ── */
.item-price-variance {
  display: inline-flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 4px;
  padding: 2px 7px;
  border: 1px solid rgba(234, 88, 12, 0.3);
  border-radius: 999px;
  background: rgba(255, 237, 213, 0.7);
  color: #c2410c;
  font-size: 9.5px;
  font-weight: 700;
}

.item-price-variance i {
  font-size: 10px;
  color: #ea580c;
  flex-shrink: 0;
}

.item-price-range {
  color: #9a3412;
  font-weight: 600;
}

/* ── PR price mismatch indicator ── */
.item-pr-mismatch {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 4px;
  padding: 3px 8px;
  border: 1px solid rgba(185, 28, 28, 0.3);
  border-left: 3px solid #dc2626;
  border-radius: 5px;
  background: rgba(254, 226, 226, 0.6);
  color: #991b1b;
  font-size: 9.5px;
  font-weight: 700;
}

.item-pr-mismatch i {
  font-size: 11px;
  flex-shrink: 0;
}

.item-pr-mismatch__label {
  text-transform: uppercase;
  letter-spacing: 0.04em;
  margin-right: 2px;
}

.item-pr-mismatch__entry {
  display: inline-flex;
  align-items: center;
  gap: 2px;
  padding: 1px 5px;
  border: 1px solid rgba(185, 28, 28, 0.2);
  border-radius: 4px;
  background: rgba(255, 255, 255, 0.7);
  font-weight: 600;
}

.item-pr-mismatch__old {
  color: #6b7280;
  text-decoration: line-through;
}

.item-pr-mismatch__new {
  color: #b91c1c;
  font-weight: 800;
}
</style>
