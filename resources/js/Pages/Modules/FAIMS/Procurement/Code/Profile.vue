<template>
  <Head :title="pageTitle" />

  <div class="pap-profile-page container-fluid py-1 px-1">
    <div class="pap-profile-shell">
      <b-alert
        v-if="flashFeedback.message"
        show
        :variant="flashFeedback.variant"
        class="mb-2 pap-profile-flash"
      >
        <div class="fw-semibold">{{ flashFeedback.message }}</div>
        <div v-if="flashFeedback.info" class="small mt-1">
          {{ flashFeedback.info }}
        </div>
      </b-alert>

      <b-row class="g-2 mb-2 align-items-start">
        <b-col cols="12" xxl="9" xl="9">
          <div class="pap-profile-main-stack d-flex flex-column gap-2">
            <b-card
              class="pap-profile-card pap-profile-hero-card border shadow-sm bg-body"
              body-class="p-2"
            >
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
              <div class="d-flex flex-wrap align-items-center gap-2">
                <b-button
                  variant="outline-secondary"
                  size="sm"
                  @click="goBack"
                >
                  <i class="ri-arrow-left-line me-1"></i>
                  Back
                </b-button>

                <b-badge class="bg-success-subtle text-success px-2 py-1">
                  {{ papCode.code }}
                </b-badge>

                <b-badge
                  class="px-2 py-1"
                  :class="budgetHealthBadgeClass"
                >
                  {{ budgetHealthLabel }}
                </b-badge>

                <b-badge
                  v-if="pendingBudgetRequestsCount"
                  class="bg-info-subtle text-info px-2 py-1"
                >
                  {{ pendingBudgetRequestsCount }}
                  {{ pendingBudgetRequestsCount === 1 ? "pending request" : "pending requests" }}
                </b-badge>
              </div>

            </div>

            <div class="row g-2">
              <div class="col-12">
                <h1 class="h4 mb-1 text-body">
                  {{ papCode.title || "Untitled Procurement code" }}
                </h1>

                <p class="text-body-secondary mb-0 pap-profile-lead">
                  Review the budget standing, request additional funding, and track all
                  deductions and top-ups for this PAP code from one page.
                </p>
              </div>

              <div class="col-12">
                <div class="row g-2">
                  <div
                    v-for="fact in overviewFacts"
                    :key="fact.label"
                    class="col-sm-6 col-xxl-3"
                  >
                    <div class="card border pap-profile-fact-card">
                      <div class="text-uppercase small text-body-secondary mb-1">
                        {{ fact.label }}
                      </div>
                      <div class="fw-semibold text-body">
                        {{ fact.value }}
                      </div>
                      <div v-if="fact.caption" class="small text-body-secondary">
                        {{ fact.caption }}
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="col-12">
                <div class="pap-profile-inline-card border p-2">
                  <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                    <div>
                      <h6 class="mb-1 text-body">End Users</h6>
                      <div class="small text-body-secondary">
                        Units currently associated with this PAP code.
                      </div>
                    </div>

                    <b-badge class="bg-primary-subtle text-primary px-2 py-1">
                      {{ endUsers.length }} linked {{ endUsers.length === 1 ? "unit" : "units" }}
                    </b-badge>
                  </div>

                  <div v-if="endUsers.length" class="d-flex flex-wrap gap-1 mt-2">
                    <b-badge
                      v-for="(endUser, index) in endUsers"
                      :key="index"
                      class="bg-primary-subtle text-primary px-2 py-1"
                    >
                      {{ endUser.end_user?.name || "Unknown end user" }}
                    </b-badge>
                  </div>

                  <b-alert v-else show variant="secondary" class="mb-0 mt-3">
                    No end users are assigned to this PAP code yet.
                  </b-alert>
                </div>
              </div>
            </div>
            </b-card>

            <b-row class="g-2 pap-profile-summary-grid">
              <b-col
                v-for="card in summaryCards"
                :key="card.label"
                class="pap-profile-summary-col"
                cols="12"
                sm="6"
                lg="4"
              >
                <b-card
                  class="pap-profile-card pap-profile-summary-card border shadow-sm bg-body h-100"
                  body-class="pap-profile-summary-body"
                >
                  <div class="d-flex justify-content-between align-items-start gap-2">
                    <div>
                      <div class="text-uppercase small text-body-secondary ">
                        {{ card.label }}
                      </div>
                      <div class="h5 mb-1" :class="card.valueClass">
                        {{ card.value }}
                      </div>
                      <div class="small text-body-secondary">
                        {{ card.caption }}
                      </div>
                    </div>

                    <b-badge
                      pill
                      class="px-2 py-1"
                      :class="card.badgeClass"
                    >
                      <i :class="card.icon"></i>
                    </b-badge>
                  </div>
                </b-card>
              </b-col>
            </b-row>
          </div>
        </b-col>

        <b-col cols="12" xxl="3" xl="3">
          <div class="pap-profile-side-stack d-flex flex-column gap-2">
            <b-card
              class="pap-profile-card pap-profile-balance-card border shadow-sm bg-body"
              body-class="p-2"
            >
              <div class="small text-uppercase text-body-secondary mb-2">
                Budget Utilization
              </div>
              <div class="row g-2 mt-2">
                <div
                  v-for="item in budgetHighlights"
                  :key="item.label"
                  class="col-sm-6"
                >
                  <div class="pap-profile-balance-stat border h-100 p-2">
                    <div class="small text-uppercase text-body-secondary mb-1">
                      {{ item.label }}
                    </div>
                    <div class="fw-semibold" :class="item.valueClass">
                      {{ item.value }}
                    </div>
                  </div>
                </div>
              </div>

              <div class="mt-2">
                <div class="d-flex justify-content-between small text-body-secondary mb-1">
                  <span>Budget used</span>
                  <span>{{ budgetUsagePercent }}%</span>
                </div>
                <div class="progress pap-profile-progress">
                  <div
                    class="progress-bar"
                    :class="budgetUsageBarClass"
                    role="progressbar"
                    :style="{ width: `${budgetUsagePercentDisplay}%` }"
                    :aria-valuenow="budgetUsagePercentDisplay"
                    aria-valuemin="0"
                    aria-valuemax="100"
                  ></div>
                </div>
                <div class="small text-body-secondary mt-2">
                  {{ formatCurrency(papCode.remaining_budget) }} still available for future requests.
                </div>
              </div>

             
            </b-card>

            <b-card
              class="pap-profile-card pap-profile-guidance-card border shadow-sm bg-body"
              body-class="p-2"
            >
              <div class="d-flex align-items-start gap-2">
                <span class="pap-profile-guidance-icon rounded-circle d-inline-flex align-items-center justify-content-center">
                  <i :class="guidanceCard.icon"></i>
                </span>

                <div class="flex-grow-1">
                  <div class="fw-semibold text-body mb-1">
                    {{ guidanceCard.title }}
                  </div>
                  <div class="small text-body-secondary">
                    {{ guidanceCard.text }}
                  </div>
                </div>
              </div>
            </b-card>
          </div>
        </b-col>
      </b-row>

      <b-alert
        v-for="alert in budgetAlerts"
        :key="alert.title"
        show
        :variant="alert.variant"
        class="mb-2 pap-profile-alert"
      >
        <div class="fw-semibold mb-1">
          <i :class="`${alert.icon} me-2`"></i>
          {{ alert.title }}
        </div>
        <div>{{ alert.text }}</div>
      </b-alert>

      <div class="pap-profile-history card bg-light-subtle shadow-none border">
        <div class="pap-profile-history__header card-header bg-light-subtle">
          <div class="d-flex flex-wrap align-items-start justify-content-between gap-2">
            <div class="d-flex align-items-start gap-2 flex-grow-1">
              <div class="flex-shrink-0">
                <div class="pap-profile-history__icon-wrap">
                  <span class="avatar-title bg-primary-subtle rounded p-2">
                    <i class="ri-history-line text-primary fs-24"></i>
                  </span>
                </div>
              </div>

              <div class="flex-grow-1">
                <h5 class="mb-1 fs-14 text-body">Budget History & Requests</h5>
                <p class="text-muted text-truncate-two-lines fs-12 mb-0">
                  Approved deductions, requested top-ups, and review decisions for this PAP code.
                </p>
              </div>
            </div>

            <div class="flex-shrink-0 text-start text-md-end">
              <p class="text-muted fs-12 mb-0">
                Showing {{ filteredLogs.length }} of {{ logs.length }}
                {{ logs.length === 1 ? "entry" : "entries" }}
              </p>
            </div>
          </div>
        </div>

        <div class="pap-profile-history__toolbar card-body bg-white border-bottom shadow-none">
          <div class="pap-profile-history__search">
            <span class="input-group-text">
              <i class="ri-search-line search-icon"></i>
            </span>
            <input
              v-model="historySearch"
              type="text"
              placeholder="Search Budget History"
              class="pap-profile-history__search-input form-control"
            />
            <select
              v-model="historyFilter"
              class="pap-profile-history__search-filter form-select"
            >
              <option value="all">All History</option>
              <option value="pending">Pending Requests</option>
              <option value="budget_increase">Budget Top-Ups</option>
              <option value="approval_deduction">Budget Deductions</option>
            </select>
            <select
              v-model="historySort"
              class="pap-profile-history__search-sort form-select"
            >
              <option value="latest">Latest First</option>
              <option value="oldest">Oldest First</option>
              <option value="amount_desc">Highest Amount</option>
              <option value="amount_asc">Lowest Amount</option>
            </select>
            <span
              class="input-group-text"
              v-b-tooltip.hover
              title="Refresh"
              style="cursor: pointer"
              @click="clearHistoryFilters"
            >
              <i class="bx bx-refresh search-icon"></i>
            </span>
            <b-button
              v-if="canRequestBudgetIncrease"
              type="button"
              variant="primary"
              class="pap-profile-history__action"
              @click="openBudgetRequestModal"
            >
              <i class="ri-add-circle-fill align-bottom me-1"></i>
              Request Budget
            </b-button>
          </div>
        </div>

        <b-card no-body class="border-0 rounded-0 shadow-none bg-transparent">
          <div class="pap-profile-history__body card-body bg-white rounded-bottom">
            <div
              v-if="filteredLogs.length"
              class="pap-profile-history__table-wrap table-responsive table-card"
            >
              <table class="pap-profile-history__table table align-middle table-hover mb-0">
                <thead class="table-light thead-fixed">
                  <tr class="fs-12 fw-semibold">
                    <th>Date</th>
                    <th>Type / Status</th>
                    <th>Reference / Description</th>
                    <th>Requested / Reviewed By</th>
                    <th class="text-end">Amounts</th>
                    <th
                      v-if="canReviewPendingRequests"
                      class="text-center"
                    >
                      Actions
                    </th>
                  </tr>
                </thead>
                <tbody class="table-group-divider">
                  <tr v-for="log in filteredLogs" :key="log.id">
                    <td class="small">
                      <div class="fw-semibold text-body">
                        {{ formatDate(log.created_at) }}
                      </div>
                      <div
                        v-if="log.reviewed_at"
                        class="text-body-secondary mt-1"
                      >
                        Reviewed: {{ formatDate(log.reviewed_at) }}
                      </div>
                    </td>

                    <td>
                      <div class="d-flex flex-column gap-1">
                        <b-badge
                          class="px-2 py-1 align-self-start"
                          :class="logTypeBadgeClass(log)"
                        >
                          {{ log.request_type_label || log.type_label }}
                        </b-badge>

                        <b-badge
                          class="px-2 py-1 align-self-start"
                          :class="statusBadgeClass(log.status)"
                        >
                          {{ log.status_label }}
                        </b-badge>
                      </div>
                    </td>

                    <td class="small">
                      <div class="fw-semibold text-body">
                        {{ log.procurement?.code || "Direct budget request" }}
                      </div>
                      <div
                        v-if="log.source_procurement_code"
                        class="text-info mt-1"
                      >
                        Realigned from: {{ log.source_procurement_code.code }} -
                        {{ log.source_procurement_code.title }}
                      </div>
                      <div class="text-body-secondary mt-1">
                        {{ log.description || log.type_label }}
                      </div>
                      <div
                        v-if="log.procurement?.title"
                        class="text-body-secondary mt-1"
                      >
                        {{ log.procurement.title }}
                      </div>
                      <div
                        v-if="log.procurement?.status"
                        class="text-body-secondary mt-1"
                      >
                        Status: {{ log.procurement.status }}
                      </div>
                      <div v-if="log.attachment_url" class="mt-2">
                        <a
                          :href="log.attachment_url"
                          target="_blank"
                          rel="noopener"
                          class="link-primary"
                        >
                          <i class="ri-attachment-2 me-1"></i>
                          {{ log.attachment_name || "View supporting basis" }}
                        </a>
                      </div>
                    </td>

                    <td class="small">
                      <div class="text-body">
                        Requested by: {{ getRequestedBy(log) }}
                      </div>
                      <div class="text-body-secondary mt-1">
                        Reviewed / processed by: {{ getReviewedBy(log) }}
                      </div>
                    </td>

                    <td class="text-end small">
                      <div class="fw-semibold" :class="logAmountClass(log)">
                        {{ amountPrefix(log) }}{{ formatCurrency(log.amount) }}
                      </div>
                      <div class="text-body-secondary mt-1">
                        Before: {{ formatCurrency(log.balance_before) }}
                      </div>
                      <div class="mt-1" :class="balanceAfterClass(log.balance_after)">
                        After: {{ formatCurrency(log.balance_after) }}
                      </div>
                      <div
                        v-if="shouldShowLogExcessFunds(log)"
                        class="mt-1"
                        :class="logExcessFundsClass(log)"
                      >
                      </div>
                    </td>

                    <td
                      v-if="canReviewPendingRequests"
                      class="text-center"
                    >
                      <div
                        v-if="isPendingBudgetIncrease(log)"
                        class="d-flex justify-content-center gap-2"
                      >
                        <b-button
                          v-if="canApprove"
                          size="sm"
                          variant="success"
                          class="btn-icon"
                          style="border-radius: 8px"
                          :disabled="processingLogId === log.id"
                          @click="approveRequest(log)"
                        >
                          <span
                            v-if="processingLogId === log.id"
                            class="spinner-border spinner-border-sm"
                          ></span>
                          <i v-else class="ri-check-line"></i>
                        </b-button>

                        <b-button
                          v-if="canApprove"
                          size="sm"
                          variant="outline-danger"
                          class="btn-icon"
                          style="border-radius: 8px"
                          :disabled="processingLogId === log.id"
                          @click="rejectRequest(log)"
                        >
                          <i class="ri-close-line"></i>
                        </b-button>
                      </div>

                      <span v-else class="small text-body-secondary">No action</span>
                    </td>
                  </tr>
                </tbody>
              </table>
              <div class="card-footer">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                  <span class="text-muted fs-12">
                    {{ historyFooterText }}
                  </span>
                  <span v-if="historyFilter !== 'all' || historySearch" class="text-muted fs-12">
                    Filtered results
                  </span>
                </div>
              </div>
            </div>

            <b-alert v-else show variant="secondary" class="mb-0">
              {{ historyEmptyMessage }}
            </b-alert>
          </div>
        </b-card>
      </div>

      <ProcurementCodeBudgetIncrease
        ref="budgetIncreaseModal"
      />
    </div>
  </div>
</template>

<script>
import { router } from "@inertiajs/vue3";
import ProcurementCodeBudgetIncrease from "@/Pages/Modules/FAIMS/Procurement/Modals/ProcurementCodeBudgetIncrease.vue";

export default {
  components: {
    ProcurementCodeBudgetIncrease,
  },
  props: {
    papCode: {
      type: Object,
      required: true,
    },
    logs: {
      type: Array,
      default: () => [],
    },
  },
  data() {
    return {
      processingLogId: null,
      historySearch: "",
      historyFilter: "all",
      historySort: "latest",
    };
  },
  computed: {
    pageTitle() {
      return this.papCode?.code
        ? `PAP Code ${this.papCode.code}`
        : "PAP Code Profile";
    },
    currentRoles() {
      return Array.isArray(this.$page?.props?.roles) ? this.$page.props.roles : [];
    },
    flashFeedback() {
      const flash = this.$page?.props?.flash || {};

      if (!flash.message && !flash.info) {
        return {
          variant: "success",
          message: null,
          info: null,
        };
      }

      return {
        variant: flash.status === false ? "danger" : "success",
        message: flash.message || null,
        info: flash.info || null,
      };
    },
    canRequestBudgetIncrease() {
      return this.currentRoles.some((role) => {
        return role === "Procurement Officer";
      });
    },

     canApprove() {
      return this.currentRoles.some((role) => {
        return ["Budget Officer"].includes(role);
      });
    },
    canReviewPendingRequests() {
      return this.currentRoles.some((role) => {
        return role === "Budget Officer";
      });
    },
    pendingBudgetRequestsCount() {
      return Number(this.papCode.pending_budget_increase_requests_count || 0);
    },
    budgetHealthLabel() {
      if (Number(this.papCode.remaining_budget) < 0) {
        return "Over-allocated";
      }

      if (this.isLowBalance) {
        return "Low remaining balance";
      }

      if (this.pendingBudgetRequestsCount > 0) {
        return "Pending review activity";
      }

      return "Healthy balance";
    },
    budgetHealthBadgeClass() {
      if (Number(this.papCode.remaining_budget) < 0) {
        return "bg-danger-subtle text-danger";
      }

      if (this.isLowBalance || this.pendingBudgetRequestsCount > 0) {
        return "bg-warning-subtle text-warning";
      }

      return "bg-success-subtle text-success";
    },
    remainingBalanceClass() {
      return Number(this.papCode.remaining_budget) < 0 ? "text-danger" : "text-success";
    },
    budgetUsagePercent() {
      const allocated = Number(this.papCode.allocated_budget || 0);
      const deducted = Number(this.papCode.approved_budget_amount ?? this.papCode.deducted_budget ?? 0);

      if (allocated <= 0) {
        return deducted > 0 ? 100 : 0;
      }

      return Math.max(0, Math.round((deducted / allocated) * 100));
    },
    budgetUsagePercentDisplay() {
      return Math.min(100, this.budgetUsagePercent);
    },
    budgetUsageBarClass() {
      if (Number(this.papCode.remaining_budget) < 0) {
        return "bg-danger";
      }

      if (this.isLowBalance) {
        return "bg-warning";
      }

      return "bg-success";
    },
    endUsers() {
      return Array.isArray(this.papCode?.end_users) ? this.papCode.end_users : [];
    },
    isLowBalance() {
      const allocated = Number(this.papCode.allocated_budget || 0);
      const remaining = Number(this.papCode.remaining_budget || 0);

      return allocated > 0 && remaining >= 0 && remaining <= allocated * 0.15;
    },
    excessFundsAmount() {
      if (this.papCode.award_variance_amount !== undefined && this.papCode.award_variance_amount !== null) {
        return Number(this.papCode.award_variance_amount || 0);
      }

      const approved = Number(this.papCode.approved_budget_amount ?? this.papCode.deducted_budget ?? 0);
      const awarded = Number(this.papCode.actual_awarded_amount || 0);

      return approved - awarded;
    },
    overviewFacts() {
      return [
        {
          label: "Mode of Procurement",
          value: this.papCode.mode_of_procurement?.name || "Not assigned",
          caption: "Selected acquisition method",
        },
        {
          label: "APP Type",
          value: this.papCode.app_type?.name || "Not assigned",
          caption: "Planning category used",
        },
        {
          label: "Fiscal Year",
          value: this.papCode.year || "Not specified",
          caption: "Applicable budget cycle",
        },
        {
          label: "Linked End Users",
          value: `${this.endUsers.length} ${this.endUsers.length === 1 ? "unit" : "units"}`,
          caption: "Units using this PAP code",
        },
      ];
    },
    budgetHighlights() {
      return [
        {
          label: "Budget Used",
          value: `${this.budgetUsagePercent}%`,
          valueClass: this.budgetUsagePercent >= 85 ? "text-warning" : "text-primary",
        },
        {
          label: "Pending Review",
          value: `${this.pendingBudgetRequestsCount}`,
          valueClass: this.pendingBudgetRequestsCount > 0 ? "text-info" : "text-body",
        },
      ];
    },
    summaryCards() {
      return [
        {
          label: "ABC",
          value: this.formatCurrency(this.papCode.allocated_budget),
          caption: "Approved contract budget",
          icon: "ri-wallet-3-line",
          badgeClass: "bg-primary-subtle text-primary",
          valueClass: "text-body",
        },
        {
          label: "Approved PR Amount",
          value: this.formatCurrency(this.papCode.approved_budget_amount || this.papCode.deducted_budget),
          caption: "Approved purchase requests",
          icon: "ri-arrow-left-right-line",
          badgeClass: "bg-warning-subtle text-warning",
          valueClass: "text-warning",
        },
        {
          label: "Purchase Orders (PO)",
          value: this.formatCurrency(this.papCode.actual_awarded_amount),
          caption: "Completed awarded amount",
          icon: "ri-award-line",
          badgeClass: "bg-info-subtle text-info",
          valueClass: "text-info",
        },
        {
          label: "Remaining Balance",
          value: this.formatCurrency(this.excessFundsAmount),
          caption: "ABC less awarded amount",
          icon: "ri-refund-2-line",
          badgeClass: this.excessFundsAmount < 0 ? "bg-danger-subtle text-danger" : "bg-success-subtle text-success",
          valueClass: this.excessFundsAmount < 0 ? "text-danger" : "text-success",
        },
        {
          label: "History Entries",
          value: Number(this.papCode.budget_logs_count || this.logs.length),
          caption: "Budget activity records",
          icon: "ri-history-line",
          badgeClass: "bg-secondary-subtle text-secondary",
          valueClass: "text-body",
        },
      ];
    },
    budgetAlerts() {
      const alerts = [];
      const remaining = Number(this.papCode.remaining_budget || 0);
      const pending = this.pendingBudgetRequestsCount;

      if (remaining < 0) {
        alerts.push({
          title: "Budget allocation needs attention",
          text: `This PAP code is over-allocated by ${this.formatCurrency(Math.abs(remaining))}.`,
          variant: "danger",
          icon: "ri-error-warning-line",
        });
      } else if (this.isLowBalance) {
        alerts.push({
          title: "Remaining balance is running low",
          text: `Only ${this.formatCurrency(remaining)} remains available for this PAP code.`,
          variant: "warning",
          icon: "ri-alarm-warning-line",
        });
      }

      if (pending > 0) {
        alerts.push({
          title: "Pending budget increase requests",
          text: `There ${pending === 1 ? "is" : "are"} ${pending} request${pending === 1 ? "" : "s"} waiting for review.`,
          variant: "info",
          icon: "ri-funds-line",
        });
      }

      return alerts;
    },
    latestLog() {
      return Array.isArray(this.logs) && this.logs.length ? this.logs[0] : null;
    },
    guidanceCard() {
      if (this.canRequestBudgetIncrease && (this.isLowBalance || Number(this.papCode.remaining_budget) < 0)) {
        return {
          title: "Budget may need adjustment",
          text: "If future procurement needs will exceed the remaining balance, submit an additional budget request from this page.",
          icon: "ri-funds-line",
        };
      }

      if (this.latestLog) {
        return {
          title: "Latest activity",
          text: `${this.latestLog.type_label} was recorded on ${this.formatDate(this.latestLog.created_at)}.`,
          icon: "ri-history-line",
        };
      }

      return {
        title: "No activity yet",
        text: "This page will start showing deductions, top-ups, and review decisions once budget activity begins.",
        icon: "ri-information-line",
      };
    },
    filteredLogs() {
      const keyword = this.normalizeText(this.historySearch);
      const filtered = this.logs.filter((log) => {
        const matchesFilter =
          this.historyFilter === "all"
            ? true
            : this.historyFilter === "pending"
              ? this.isPendingBudgetIncrease(log)
              : log?.type === this.historyFilter;

        if (!matchesFilter) {
          return false;
        }

        if (!keyword) {
          return true;
        }

        const haystack = this.normalizeText([
          log?.type_label,
          log?.status_label,
          log?.description,
          log?.procurement?.code,
          log?.procurement?.title,
          log?.procurement?.status,
          this.getRequestedBy(log),
          this.getReviewedBy(log),
          log?.attachment_name,
        ].join(" "));

        return haystack.includes(keyword);
      });

      return [...filtered].sort((a, b) => {
        switch (this.historySort) {
          case "oldest":
            return new Date(a.created_at || 0) - new Date(b.created_at || 0);
          case "amount_desc":
            return Number(b.amount || 0) - Number(a.amount || 0);
          case "amount_asc":
            return Number(a.amount || 0) - Number(b.amount || 0);
          default:
            return new Date(b.created_at || 0) - new Date(a.created_at || 0);
        }
      });
    },
    historyEmptyMessage() {
      if (this.logs.length === 0) {
        return "No budget history has been recorded for this PAP code yet.";
      }

      return "No history entries match the current search or filter.";
    },
    historyFooterText() {
      return `Showing ${this.filteredLogs.length} of ${this.logs.length} ${this.logs.length === 1 ? "entry" : "entries"}.`;
    },
  },
  methods: {
    goBack() {
      if (window.history.length > 1) {
        window.history.back();
        return;
      }

      this.goToPapList();
    },
    goToPapList() {
      router.get("/faims/procurement-codes");
    },
    openBudgetRequestModal() {
      this.$refs.budgetIncreaseModal?.show(this.papCode);
    },
    clearHistoryFilters() {
      this.historySearch = "";
      this.historyFilter = "all";
      this.historySort = "latest";
    },
    isPendingBudgetIncrease(log) {
      return log?.type === "budget_increase" && log?.status === "pending";
    },
    approveRequest(log) {
      this.reviewRequest(log, "approve");
    },
    rejectRequest(log) {
      this.reviewRequest(log, "reject");
    },
    reviewRequest(log, action) {
      if (!this.isPendingBudgetIncrease(log) || this.processingLogId) {
        return;
      }

      this.processingLogId = log.id;

      router.patch(
        `/faims/procurement-codes/${this.papCode.id}/budget-increase-requests/${log.id}/${action}`,
        {},
        {
          preserveScroll: true,
          preserveState: true,
          onFinish: () => {
            this.processingLogId = null;
          },
        }
      );
    },
    logTypeBadgeClass(log) {
      return log?.type === "approval_deduction"
        ? "bg-warning-subtle text-warning"
        : "bg-primary-subtle text-primary";
    },
    statusBadgeClass(status) {
      switch (status) {
        case "approved":
          return "bg-success-subtle text-success";
        case "rejected":
          return "bg-danger-subtle text-danger";
        default:
          return "bg-warning-subtle text-warning";
      }
    },
    amountPrefix(log) {
      return log?.amount_direction === "decrease" ? "-" : "+";
    },
    logAmountClass(log) {
      return log?.amount_direction === "decrease" ? "text-warning" : "text-primary";
    },
    balanceAfterClass(value) {
      return Number(value) < 0 ? "text-danger" : "text-success";
    },
    shouldShowLogExcessFunds(log) {
      return log?.type === "approval_deduction";
    },
    logExcessFundsAmount(log) {
      if (log?.excess_funds_amount !== undefined && log?.excess_funds_amount !== null) {
        return Number(log.excess_funds_amount) || 0;
      }

      return Number(log?.amount || 0) - Number(log?.actual_awarded_amount || 0);
    },
    logExcessFundsClass(log) {
      return this.logExcessFundsAmount(log) < 0 ? "text-danger" : "text-success";
    },
    getRequestedBy(log) {
      return log?.requested_by?.name || log?.processed_by?.name || "System";
    },
    getReviewedBy(log) {
      return log?.reviewed_by?.name || log?.processed_by?.name || "Pending review";
    },
    normalizeText(value) {
      return String(value || "").toLowerCase().trim();
    },
    formatCurrency(value) {
      return new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
      }).format(Number(value || 0));
    },
    formatDate(value) {
      if (!value) {
        return "N/A";
      }

      return new Date(value).toLocaleString("en-PH", {
        year: "numeric",
        month: "short",
        day: "numeric",
        hour: "2-digit",
        minute: "2-digit",
      });
    },
  },
};
</script>

<style scoped>
.pap-profile-page {
  --pap-page-bg: linear-gradient(180deg, #f5f7fb 0%, #eef3f9 100%);
  --pap-surface: #ffffff;
  --pap-surface-alt: #f7f9fc;
  --pap-border: rgba(148, 163, 184, 0.22);
  --pap-border-strong: rgba(148, 163, 184, 0.34);
  --pap-text: #1e293b;
  --pap-muted: #64748b;
  --pap-primary: #2563eb;
  --pap-progress-track: #e2e8f0;
  --pap-row-hover: rgba(37, 99, 235, 0.05);
  --pap-shadow: 0 10px 24px rgba(15, 23, 42, 0.07);
  --pap-shadow-soft: 0 6px 16px rgba(15, 23, 42, 0.05);
  background: var(--pap-page-bg);
  min-height: 100vh;
}

.pap-profile-shell {
  width: 100%;
  max-width: 1860px;
  margin: 0 auto;
}

.pap-profile-card,
.pap-profile-history {
  border-color: var(--pap-border) !important;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: var(--pap-shadow-soft) !important;
}

.pap-profile-hero-card {
  position: relative;
  box-shadow: var(--pap-shadow) !important;
}

.pap-profile-hero-card::before {
  content: "";
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 3px;
  background: linear-gradient(90deg, #2563eb, #0ea5e9, #10b981);
}

.pap-profile-mini-card,
.pap-profile-inline-card,
.pap-profile-balance-stat,
.pap-profile-guidance-card {
  background: var(--pap-surface-alt) !important;
  border-color: var(--pap-border) !important;
  border-radius: 10px;
}

.pap-profile-lead {
  max-width: 62rem;
  line-height: 1.35;
  font-size: 0.82rem;
}

.pap-profile-mini-card--tall {
  min-height: 96px;
}

.pap-profile-fact-card {
  padding: 0.55rem 0.65rem;
  min-height: 64px;
}

.pap-profile-inline-card,
.pap-profile-balance-stat,
.pap-profile-guidance-card {
  box-shadow: inset 0 0 0 1px rgba(37, 99, 235, 0.04);
}

.pap-profile-guidance-icon {
  width: 2rem;
  height: 2rem;
  background: rgba(37, 99, 235, 0.12);
  color: var(--pap-primary);
  font-size: 1.05rem;
  flex-shrink: 0;
}

.pap-profile-history__icon-wrap {
  width: 2rem;
  height: 2rem;
}

.pap-profile-progress {
  height: 0.45rem;
}

.pap-profile-flash,
.pap-profile-alert {
  border-radius: 10px;
  box-shadow: var(--pap-shadow-soft);
  padding: 0.6rem 0.75rem;
}

.pap-profile-summary-grid {
  --bs-gutter-x: 0.45rem;
  --bs-gutter-y: 0.45rem;
}

.pap-profile-summary-card {
  transition: transform 0.2s ease, box-shadow 0.2s ease;
  min-height: 82px;
}

:deep(.pap-profile-summary-body) {
  padding: 0.5rem 0.6rem !important;
}

.pap-profile-summary-card .small {
  line-height: 1.12;
}

.pap-profile-summary-card .h5 {
  font-size: 1.05rem;
  line-height: 1.08;
}

.pap-profile-summary-card .badge {
  line-height: 1;
  padding: 0.25rem 0.45rem !important;
}

.pap-profile-summary-card:hover {
  transform: translateY(-2px);
  box-shadow: var(--pap-shadow) !important;
}

.pap-profile-history__header,
.pap-profile-history__toolbar,
:deep(.pap-profile-card__header),
:deep(.pap-profile-history__body),
:deep(.pap-profile-history .card-footer) {
  border-color: var(--pap-border) !important;
}

.pap-profile-history__header,
.pap-profile-history__toolbar,
:deep(.pap-profile-card__header),
:deep(.pap-profile-history .card-footer) {
  background: var(--pap-surface-alt) !important;
}

:deep(.pap-profile-history__body) {
  background: var(--pap-surface) !important;
  padding: 0.55rem !important;
}

:deep(.pap-profile-page .bg-white) {
  background: var(--pap-surface) !important;
}

:deep(.pap-profile-page .bg-light-subtle),
:deep(.pap-profile-page .bg-body-tertiary) {
  background: var(--pap-surface-alt) !important;
}

@media (min-width: 1200px) {
  .pap-profile-summary-col {
    flex: 0 0 20%;
    max-width: 20%;
  }
}

.pap-profile-history__search {
  display: flex;
  flex-wrap: wrap;
  gap: 0.35rem;
  align-items: stretch;
}

.pap-profile-history__toolbar {
  padding: 0.55rem !important;
}

:deep(.pap-profile-history__search .input-group-text),
:deep(.pap-profile-history__search .form-control),
:deep(.pap-profile-history__search .form-select) {
  border-radius: 8px !important;
}

:deep(.pap-profile-history__search .input-group-text) {
  background: var(--pap-surface-alt) !important;
  border-color: var(--pap-border) !important;
  color: var(--pap-muted) !important;
}

:deep(.pap-profile-history__search .form-control),
:deep(.pap-profile-history__search .form-select) {
  background: var(--pap-surface) !important;
  border-color: var(--pap-border) !important;
  color: var(--pap-text) !important;
}

:deep(.pap-profile-history__search .form-control::placeholder) {
  color: var(--pap-muted);
}

:deep(.pap-profile-history__search .form-control:focus),
:deep(.pap-profile-history__search .form-select:focus) {
  border-color: rgba(37, 99, 235, 0.45) !important;
  box-shadow: 0 0 0 0.2rem rgba(37, 99, 235, 0.12) !important;
}

:deep(.pap-profile-history__search-input) {
  flex: 1 1 320px;
  min-width: 280px;
}

:deep(.pap-profile-history__search-filter) {
  flex: 0 0 180px;
}

:deep(.pap-profile-history__search-sort) {
  flex: 0 0 170px;
}

:deep(.pap-profile-history__action) {
  flex: 0 0 auto;
  min-width: 170px;
}

:deep(.pap-profile-history__table-wrap) {
  border: 1px solid var(--pap-border);
  border-radius: 10px;
  background: var(--pap-surface);
  max-height: 640px;
  overflow: auto;
}

:deep(.pap-profile-history__header) {
  padding: 0.6rem 0.75rem !important;
}

:deep(.pap-profile-history__table thead th),
:deep(.pap-profile-history__table tbody td) {
  padding: 0.45rem 0.55rem;
}

:deep(.pap-profile-history__table thead th) {
  position: sticky;
  top: 0;
  z-index: 1;
  background: var(--pap-surface-alt) !important;
  color: var(--pap-muted) !important;
  border-bottom: 1px solid var(--pap-border) !important;
}

:deep(.pap-profile-history__table tbody td) {
  background: var(--pap-surface);
  color: var(--pap-text);
  border-bottom: 1px solid var(--pap-border);
}

:deep(.pap-profile-history__table.table-hover tbody tr:hover td) {
  background: var(--pap-row-hover);
}

:deep(.pap-profile-page .table-group-divider) {
  border-top-color: var(--pap-border);
}

:deep(.pap-profile-page .progress) {
  background: var(--pap-progress-track);
  border-radius: 999px;
  overflow: hidden;
}

:deep(.pap-profile-page .btn-outline-secondary) {
  border-color: var(--pap-border-strong);
  color: var(--pap-text);
}

:deep(.pap-profile-page .btn-outline-secondary:hover) {
  background: var(--pap-surface-alt);
  color: var(--pap-text);
}

:deep(.pap-profile-page .btn-primary) {
  border: none;
  box-shadow: 0 6px 14px rgba(37, 99, 235, 0.12);
}

:deep(.pap-profile-page .alert-secondary) {
  background: var(--pap-surface-alt);
  border-color: var(--pap-border);
  color: var(--pap-muted);
}

:deep(.pap-profile-page .card-footer) {
  border-top: 1px solid var(--pap-border) !important;
  padding: 0.5rem 0.75rem;
}

[data-bs-theme="dark"] .pap-profile-page {
  --pap-page-bg: linear-gradient(180deg, #101826 0%, #0f172a 100%);
  --pap-surface: #131d2b;
  --pap-surface-alt: #182235;
  --pap-border: rgba(148, 163, 184, 0.18);
  --pap-border-strong: rgba(148, 163, 184, 0.28);
  --pap-text: #e5edf7;
  --pap-muted: #9fb0c7;
  --pap-primary: #8cb4ff;
  --pap-progress-track: #243247;
  --pap-row-hover: rgba(96, 165, 250, 0.12);
  --pap-shadow: 0 22px 46px rgba(2, 6, 23, 0.42);
  --pap-shadow-soft: 0 16px 34px rgba(2, 6, 23, 0.3);
}

[data-bs-theme="dark"] .pap-profile-hero-card::before {
  background: linear-gradient(90deg, #60a5fa, #38bdf8, #34d399);
}

[data-bs-theme="dark"] .pap-profile-inline-card,
[data-bs-theme="dark"] .pap-profile-balance-stat,
[data-bs-theme="dark"] .pap-profile-guidance-card {
  box-shadow: inset 0 0 0 1px rgba(140, 180, 255, 0.08);
}

[data-bs-theme="dark"] .pap-profile-guidance-icon {
  background: rgba(140, 180, 255, 0.12);
}

[data-bs-theme="dark"] :deep(.pap-profile-page .btn-primary) {
  box-shadow: 0 10px 22px rgba(37, 99, 235, 0.22);
}

@media (max-width: 991px) {
  :deep(.pap-profile-history__search-input),
  :deep(.pap-profile-history__search-filter),
  :deep(.pap-profile-history__search-sort) {
    flex: 1 1 100%;
    min-width: 100%;
  }

  .pap-profile-history__header .d-flex {
    flex-direction: column;
    align-items: flex-start !important;
  }

  :deep(.pap-profile-history__action) {
    flex: 1 1 100%;
    min-width: 100%;
  }
}

@media (max-width: 767px) {
  .pap-profile-page {
    padding-left: 0.4rem;
    padding-right: 0.4rem;
  }

  .pap-profile-card,
  .pap-profile-history {
    border-radius: 16px;
  }

  .pap-profile-mini-card,
  .pap-profile-inline-card,
  .pap-profile-balance-stat,
  .pap-profile-guidance-card {
    padding: 0.85rem !important;
  }
}
</style>
