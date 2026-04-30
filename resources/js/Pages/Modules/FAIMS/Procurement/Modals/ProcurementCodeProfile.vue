<template>
  <b-modal
    v-model="showModal"
    title="PAP Code Profile"
    size="xl"
    centered
    scrollable
    hide-footer
    header-class="border-0 pb-0 px-4 pt-4"
    body-class="px-4 pb-4 pt-3"
    content-class="border-0 shadow-lg"
  >
    <div v-if="loading" class="pap-profile-loading">
      <b-spinner variant="primary" class="mb-3" />
      <p class="pap-profile-loading__title mb-1">Loading PAP code profile...</p>
      <p class="pap-profile-loading__subtitle mb-0">
        Pulling the latest balance, status, and budget history.
      </p>
    </div>

    <div v-else-if="selected" class="pap-profile">
      <div
        v-if="feedback.message"
        class="pap-profile-feedback alert rounded-4 border-0"
        :class="feedback.type === 'error' ? 'alert-danger' : 'alert-success'"
      >
        <div class="fw-semibold">{{ feedback.message }}</div>
        <div v-if="feedback.info" class="fs-12 mt-1">{{ feedback.info }}</div>
      </div>

      <section
        class="pap-profile-hero"
        :class="`pap-profile-hero--${budgetHealthTone}`"
      >
        <div class="pap-profile-hero__main">
          <div>
            <div class="pap-profile-hero__eyebrow">Budget profile overview</div>
            <span class="pap-profile-hero__code">{{ selected.code }}</span>
            <h3 class="pap-profile-hero__title">
              {{ selected.title || "Untitled PAP code" }}
            </h3>
            <p class="pap-profile-hero__subtitle mb-0">
              {{ selected.mode_of_procurement?.name || "No mode of procurement" }}
              <span class="mx-2">|</span>
              {{ selected.app_type?.name || "No APP type" }}
              <span class="mx-2">|</span>
              {{ selected.year || "No year" }}
            </p>

            <div class="pap-profile-hero__meta">
              <span class="pap-profile-hero__pill">
                <i class="ri-file-list-3-line"></i>
                {{ selected.budget_logs_count || logs.length }} history entr{{ (selected.budget_logs_count || logs.length) === 1 ? "y" : "ies" }}
              </span>
              <span class="pap-profile-hero__pill">
                <i class="ri-group-line"></i>
                {{ (selected.end_users || []).length }} end user{{ (selected.end_users || []).length === 1 ? "" : "s" }}
              </span>
            </div>
          </div>

          <div class="pap-profile-hero__aside">
            <span class="pap-profile-hero__status">{{ budgetHealthLabel }}</span>
            <div class="pap-profile-hero__balance">
              {{ formatCurrency(selected.remaining_budget) }}
            </div>
            <div class="pap-profile-hero__balance-caption">Remaining balance</div>

            <div v-if="showBudgetIncreaseAction" class="pap-profile-hero__actions">
              <b-button variant="light" size="sm" @click="requestAdditionalBudget">
                <i class="ri-funds-line align-bottom me-1"></i>
                Request Additional Budget
              </b-button>
            </div>
          </div>
        </div>

        <div class="pap-profile-hero__progress">
          <div class="pap-profile-hero__progress-copy">
            <span>Budget used</span>
            <strong>{{ budgetUsageDisplay }}</strong>
          </div>
          <div class="pap-profile-progress">
            <div
              class="pap-profile-progress__bar"
              :class="`pap-profile-progress__bar--${budgetHealthTone}`"
              :style="{ width: `${budgetUsagePercentCapped}%` }"
            ></div>
          </div>
          <div class="pap-profile-hero__progress-meta">
            <span>Allocated {{ formatCurrency(selected.allocated_budget) }}</span>
            <span>Approved {{ formatCurrency(selected.approved_budget_amount || selected.deducted_budget) }}</span>
          </div>
        </div>

        <div class="pap-profile-hero__chips">
          <span
            v-for="(endUser, index) in selected.end_users || []"
            :key="index"
            class="pap-profile-hero__end-user"
          >
            {{ endUser.end_user?.name || "Unknown end user" }}
          </span>
          <span
            v-if="!(selected.end_users || []).length"
            class="pap-profile-hero__end-user pap-profile-hero__end-user--empty"
          >
            No end users assigned yet
          </span>
        </div>
      </section>

      <div class="row g-3 mb-4">
        <div
          v-for="card in summaryCards"
          :key="card.label"
          class="col-sm-6 col-xl-3"
        >
          <div class="pap-summary-card">
            <div class="pap-summary-card__icon">
              <i :class="card.icon"></i>
            </div>
            <div class="pap-summary-card__content">
              <span class="pap-summary-card__label">{{ card.label }}</span>
              <strong
                class="pap-summary-card__value"
                :class="card.valueClass"
              >
                {{ card.value }}
              </strong>
              <span class="pap-summary-card__caption">{{ card.caption }}</span>
            </div>
          </div>
        </div>
      </div>

      <div
        v-if="budgetAlerts.length"
        class="pap-alert-list mb-4"
      >
        <div
          v-for="alert in budgetAlerts"
          :key="alert.title"
          class="pap-alert"
          :class="`pap-alert--${alert.tone}`"
        >
          <div class="pap-alert__icon">
            <i :class="alert.icon"></i>
          </div>
          <div>
            <div class="pap-alert__title">{{ alert.title }}</div>
            <div class="pap-alert__text">{{ alert.text }}</div>
          </div>
        </div>
      </div>

      <section class="pap-history">
        <div class="pap-history__header">
          <div>
            <h5 class="mb-1">Budget History & Requests</h5>
            <p class="pap-history__subtitle mb-0">
            Track approved deductions, requested budget top-ups, and Budget Officer decisions.
            </p>
          </div>

          <div class="pap-history__legend">
            <span class="pap-history__legend-item">
              <i class="ri-arrow-down-line"></i>
              Deductions
            </span>
            <span class="pap-history__legend-item">
              <i class="ri-arrow-up-line"></i>
              Top-ups
            </span>
            <span
              v-if="showPendingRequestActions"
              class="pap-history__legend-item"
            >
              <i class="ri-time-line"></i>
              Pending review
            </span>
          </div>
        </div>

        <div v-if="logs.length">
          <div class="d-lg-none pap-log-card-list">
            <article
              v-for="log in logs"
              :key="log.id"
              class="pap-log-card"
            >
              <div class="pap-log-card__top">
                <div>
                  <div class="pap-log-card__date">{{ formatDate(log.created_at) }}</div>
                  <div v-if="log.reviewed_at" class="pap-log-card__reviewed">
                    Reviewed {{ formatDate(log.reviewed_at) }}
                  </div>
                </div>
                <div class="pap-log-card__badges">
                  <span class="badge" :class="logTypeClass(log)">
                    {{ log.request_type_label || log.type_label }}
                  </span>
                  <span class="badge" :class="statusClass(log.status)">
                    {{ log.status_label }}
                  </span>
                </div>
              </div>

              <div class="pap-log-card__description">
                {{ log.description || log.type_label }}
                <span v-if="log.source_procurement_code" class="d-block text-info mt-1">
                  Realigned from: {{ log.source_procurement_code.code }} -
                  {{ log.source_procurement_code.title }}
                </span>
              </div>

              <div class="pap-log-card__reference">
                <strong>{{ log.procurement?.code || "Direct budget request" }}</strong>
                <span>{{ log.procurement?.status || "No procurement reference" }}</span>
              </div>

              <div class="pap-log-card__people">
                <span>Requested by: {{ getRequestedBy(log) }}</span>
                <span>Processed by: {{ getReviewedBy(log) }}</span>
              </div>

              <a
                v-if="log.attachment_url"
                :href="log.attachment_url"
                target="_blank"
                rel="noopener"
                class="pap-log-card__link"
              >
                <i class="ri-attachment-2 me-1"></i>
                {{ log.attachment_name || "View supporting basis" }}
              </a>

              <div class="pap-log-card__amounts">
                <span class="fw-semibold" :class="logAmountClass(log)">
                  {{ amountPrefix(log) }}{{ formatCurrency(log.amount) }}
                </span>
                <span>Before {{ formatCurrency(log.balance_before) }}</span>
                <span :class="balanceClass(log.balance_after)">
                  After {{ formatCurrency(log.balance_after) }}
                </span>
                <span
                  v-if="shouldShowLogExcessFunds(log)"
                  :class="logExcessFundsClass(log)"
                >
                  Excess Funds {{ formatCurrency(logExcessFundsAmount(log)) }}
                </span>
              </div>

              <div
                v-if="showPendingRequestActions"
                class="pap-log-card__actions"
              >
                <div
                  v-if="isPendingBudgetIncrease(log)"
                  class="d-flex justify-content-start gap-2"
                >
                  <b-button
                    size="sm"
                    variant="success"
                    :disabled="processingLogId === log.id"
                    @click="approveRequest(log)"
                  >
                    <span
                      v-if="processingLogId === log.id"
                      class="spinner-border spinner-border-sm"
                    ></span>
                    <template v-else>
                      <i class="ri-check-line me-1"></i>
                      Approve
                    </template>
                  </b-button>
                  <b-button
                    size="sm"
                    variant="outline-danger"
                    :disabled="processingLogId === log.id"
                    @click="rejectRequest(log)"
                  >
                    <i class="ri-close-line me-1"></i>
                    Reject
                  </b-button>
                </div>
                <span v-else class="text-muted fs-12">No action needed</span>
              </div>
            </article>
          </div>

          <div class="table-responsive d-none d-lg-block">
            <table class="table pap-history-table align-middle mb-0">
              <thead>
                <tr>
                  <th>Timeline</th>
                  <th>Activity</th>
                  <th>Reference</th>
                  <th>People</th>
                  <th class="text-end">Amounts</th>
                  <th v-if="showPendingRequestActions" class="text-center">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="log in logs" :key="log.id">
                  <td>
                    <div class="pap-history-table__date">{{ formatDate(log.created_at) }}</div>
                    <div v-if="log.reviewed_at" class="pap-history-table__meta">
                      Reviewed {{ formatDate(log.reviewed_at) }}
                    </div>
                    <div class="d-flex gap-1 flex-wrap mt-2">
                      <span class="badge" :class="logTypeClass(log)">
                        {{ log.request_type_label || log.type_label }}
                      </span>
                      <span class="badge" :class="statusClass(log.status)">
                        {{ log.status_label }}
                      </span>
                    </div>
                  </td>
                  <td>
                    <div class="pap-history-table__description">
                      {{ log.description || log.type_label }}
                      <span v-if="log.source_procurement_code" class="d-block text-info mt-1">
                        Realigned from: {{ log.source_procurement_code.code }}
                      </span>
                    </div>
                    <div v-if="log.procurement?.title" class="pap-history-table__meta">
                      {{ log.procurement.title }}
                    </div>
                    <div v-if="log.attachment_url" class="mt-2">
                      <a
                        :href="log.attachment_url"
                        target="_blank"
                        rel="noopener"
                        class="pap-history-table__link"
                      >
                        <i class="ri-attachment-2 me-1"></i>
                        {{ log.attachment_name || "View supporting basis" }}
                      </a>
                    </div>
                  </td>
                  <td>
                    <div class="fw-semibold">{{ log.procurement?.code || "Direct budget request" }}</div>
                    <div class="pap-history-table__meta">
                      {{ log.procurement?.status || "No procurement reference" }}
                    </div>
                  </td>
                  <td>
                    <div>{{ getRequestedBy(log) }}</div>
                    <div class="pap-history-table__meta">
                      Reviewed / processed by {{ getReviewedBy(log) }}
                    </div>
                  </td>
                  <td class="text-end">
                    <div class="fw-semibold" :class="logAmountClass(log)">
                      {{ amountPrefix(log) }}{{ formatCurrency(log.amount) }}
                    </div>
                    <div class="pap-history-table__meta">
                      Before {{ formatCurrency(log.balance_before) }}
                    </div>
                    <div class="pap-history-table__meta" :class="balanceClass(log.balance_after)">
                      After {{ formatCurrency(log.balance_after) }}
                    </div>
                    <div
                      v-if="shouldShowLogExcessFunds(log)"
                      class="pap-history-table__meta"
                      :class="logExcessFundsClass(log)"
                    >
                      Excess Funds {{ formatCurrency(logExcessFundsAmount(log)) }}
                    </div>
                  </td>
                  <td v-if="showPendingRequestActions" class="text-center">
                    <div
                      v-if="isPendingBudgetIncrease(log)"
                      class="d-flex justify-content-center gap-2"
                    >
                      <b-button
                        size="sm"
                        variant="success"
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
                        size="sm"
                        variant="outline-danger"
                        :disabled="processingLogId === log.id"
                        @click="rejectRequest(log)"
                      >
                        <i class="ri-close-line"></i>
                      </b-button>
                    </div>
                    <span v-else class="text-muted fs-12">No action</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div v-else class="pap-history-empty">
          <i class="ri-history-line pap-history-empty__icon"></i>
          <h6 class="mt-3 mb-1">No budget history yet</h6>
          <p class="pap-history-empty__text mb-0">
            Budget deductions and top-up requests will appear here once activity starts.
          </p>
        </div>
      </section>
    </div>
  </b-modal>
</template>

<script>
import { router } from "@inertiajs/vue3";

export default {
  props: {
    allowBudgetActions: {
      type: Boolean,
      default: true,
    },
  },
  data() {
    return {
      showModal: false,
      loading: false,
      selected: null,
      logs: [],
      currentId: null,
      processingLogId: null,
      feedback: {
        type: null,
        message: null,
        info: null,
      },
    };
  },
  computed: {
    currentRoles() {
      return Array.isArray(this.$page?.props?.roles) ? this.$page.props.roles : [];
    },
    canRequestBudgetIncrease() {
      return this.currentRoles.some((role) => {
        return role === "Procurement Officer";
      });
    },
    canReviewPendingRequests() {
      return this.currentRoles.some((role) => {
        return role === "Budget Officer";
      });
    },
    showBudgetIncreaseAction() {
      return this.allowBudgetActions && this.canRequestBudgetIncrease;
    },
    showPendingRequestActions() {
      return this.allowBudgetActions && this.canReviewPendingRequests;
    },
    budgetUsagePercent() {
      const allocatedBudget = Number(this.selected?.allocated_budget || 0);
      const deductedBudget = Number(this.selected?.approved_budget_amount ?? this.selected?.deducted_budget ?? 0);

      if (allocatedBudget <= 0) {
        return deductedBudget > 0 ? 100 : 0;
      }

      return (deductedBudget / allocatedBudget) * 100;
    },
    budgetUsagePercentCapped() {
      return Math.min(100, Math.max(0, Math.round(this.budgetUsagePercent)));
    },
    budgetUsageDisplay() {
      return `${Math.max(0, Math.round(this.budgetUsagePercent))}%`;
    },
    budgetHealthTone() {
      const remainingBudget = Number(this.selected?.remaining_budget || 0);
      const allocatedBudget = Number(this.selected?.allocated_budget || 0);
      const pendingRequests = Number(this.selected?.pending_budget_increase_requests_count || 0);

      if (remainingBudget < 0) {
        return "danger";
      }

      if (allocatedBudget > 0 && remainingBudget <= allocatedBudget * 0.15) {
        return "warning";
      }

      if (pendingRequests > 0) {
        return "warning";
      }

      return "success";
    },
    budgetHealthLabel() {
      switch (this.budgetHealthTone) {
        case "danger":
          return "Over-allocated";
        case "warning":
          return "Needs attention";
        default:
          return "Healthy balance";
      }
    },
    excessFundsAmount() {
      if (!this.selected) {
        return 0;
      }

      if (this.selected.award_variance_amount !== undefined && this.selected.award_variance_amount !== null) {
        return Number(this.selected.award_variance_amount || 0);
      }

      const approvedBudget = Number(this.selected.approved_budget_amount ?? this.selected.deducted_budget ?? 0);
      const awardedBudget = Number(this.selected.actual_awarded_amount || 0);

      return approvedBudget - awardedBudget;
    },
    summaryCards() {
      if (!this.selected) {
        return [];
      }

      return [
        {
          label: "Allocated Budget",
          value: this.formatCurrency(this.selected.allocated_budget),
          caption: "Approved allocation for this PAP code",
          icon: "ri-wallet-3-line",
          valueClass: "pap-summary-card__value--neutral",
        },
        {
          label: "Approved Budget",
          value: this.formatCurrency(this.selected.approved_budget_amount || this.selected.deducted_budget),
          caption: "Total approved budget for procurement requests",
          icon: "ri-arrow-left-right-line",
          valueClass: "pap-summary-card__value--warning",
        },
        {
          label: "Actual Awarded",
          value: this.formatCurrency(this.selected.actual_awarded_amount),
          caption: "Completed item awards charged to this PAP code",
          icon: "ri-award-line",
          valueClass: "pap-summary-card__value--primary",
        },
        {
          label: "Excess Funds",
          value: this.formatCurrency(this.excessFundsAmount),
          caption: "Approved budget less actual awarded amount",
          icon: "ri-refund-2-line",
          valueClass:
            this.excessFundsAmount < 0
              ? "pap-summary-card__value--danger"
              : "pap-summary-card__value--success",
        },
        {
          label: "Remaining Balance",
          value: this.formatCurrency(this.selected.remaining_budget),
          caption: "Available balance after current deductions",
          icon: "ri-line-chart-line",
          valueClass:
            Number(this.selected.remaining_budget) < 0
              ? "pap-summary-card__value--danger"
              : "pap-summary-card__value--success",
        },
      ];
    },
    budgetAlerts() {
      if (!this.selected) {
        return [];
      }

      const alerts = [];
      const remainingBudget = Number(this.selected.remaining_budget || 0);
      const allocatedBudget = Number(this.selected.allocated_budget || 0);
      const pendingRequests = Number(this.selected.pending_budget_increase_requests_count || 0);

      if (remainingBudget < 0) {
        alerts.push({
          tone: "danger",
          icon: "ri-error-warning-line",
          title: "This PAP code is over-allocated",
          text: `Current deductions exceed the available budget by ${this.formatCurrency(Math.abs(remainingBudget))}.`,
        });
      } else if (allocatedBudget > 0 && remainingBudget <= allocatedBudget * 0.15) {
        alerts.push({
          tone: "warning",
          icon: "ri-alarm-warning-line",
          title: "Remaining balance is running low",
          text: `Only ${this.formatCurrency(remainingBudget)} remains available under this PAP code.`,
        });
      }

      if (pendingRequests > 0) {
        alerts.push({
          tone: "info",
          icon: "ri-funds-line",
          title: "Pending budget increase requests",
          text: `There ${pendingRequests === 1 ? "is" : "are"} ${pendingRequests} request${pendingRequests === 1 ? "" : "s"} waiting for review.`,
        });
      }

      return alerts;
    },
  },
  methods: {
    show(id) {
      this.showModal = true;
      this.loading = true;
      this.currentId = id;
      this.selected = null;
      this.logs = [];
      this.feedback = {
        type: null,
        message: null,
        info: null,
      };

      axios
        .get(`/faims/procurement-codes/${id}`)
        .then((response) => {
          this.applyPayload({
            data: response.data?.data || null,
            logs: response.data?.logs || [],
          });
        })
        .catch((error) => {
          console.error("Failed to load PAP code profile:", error);
        })
        .finally(() => {
          this.loading = false;
        });
    },
    applyPayload(payload) {
      if (!payload?.data) {
        return;
      }

      if (this.currentId && payload.data.id !== this.currentId && this.selected?.id !== payload.data.id) {
        return;
      }

      this.selected = payload.data;
      this.logs = Array.isArray(payload.logs) ? payload.logs : [];
      this.currentId = payload.data.id;

      if (payload.message) {
        this.feedback = {
          type: payload.type || "success",
          message: payload.message,
          info: payload.info || null,
        };
      }
    },
    requestAdditionalBudget() {
      if (!this.selected) {
        return;
      }

      this.$emit("request-budget-increase", this.selected);
    },
    isPendingBudgetIncrease(log) {
      return log?.type === "budget_increase" && log?.status === "pending";
    },
    logTypeClass(log) {
      return log?.type === "approval_deduction"
        ? "bg-warning-subtle text-warning"
        : "bg-primary-subtle text-primary";
    },
    amountPrefix(log) {
      return log?.amount_direction === "decrease" ? "-" : "+";
    },
    logAmountClass(log) {
      return log?.amount_direction === "decrease" ? "text-warning" : "text-primary";
    },
    balanceClass(value) {
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
    approveRequest(log) {
      this.reviewRequest(log, "approve");
    },
    rejectRequest(log) {
      this.reviewRequest(log, "reject");
    },
    reviewRequest(log, action) {
      if (!this.selected || !this.isPendingBudgetIncrease(log) || this.processingLogId) {
        return;
      }

      this.processingLogId = log.id;

      router.patch(
        `/faims/procurement-codes/${this.selected.id}/budget-increase-requests/${log.id}/${action}`,
        {},
        {
          preserveScroll: true,
          preserveState: true,
          onSuccess: () => {
            const flash = this.$page.props.flash || {};

            if (flash.status === false) {
              this.feedback = {
                type: "error",
                message: flash.message || "Failed to update the budget request.",
                info: flash.info || null,
              };
              return;
            }

            const payload = {
              ...(flash.data || {}),
              message: flash.message || "Budget request updated successfully.",
              info: flash.info || null,
              type: flash.status === false ? "error" : "success",
            };

            this.applyPayload(payload);
            this.$emit("updated", payload);
          },
          onError: () => {
            this.feedback = {
              type: "error",
              message: "Failed to update the budget request.",
              info: null,
            };
          },
          onFinish: () => {
            this.processingLogId = null;
          },
        }
      );
    },
    statusClass(status) {
      switch (status) {
        case "approved":
          return "bg-success-subtle text-success";
        case "rejected":
          return "bg-danger-subtle text-danger";
        default:
          return "bg-warning-subtle text-warning";
      }
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
.pap-profile {
  --pap-profile-surface: #ffffff;
  --pap-profile-surface-soft: #f8fafc;
  --pap-profile-border: rgba(148, 163, 184, 0.22);
  --pap-profile-text: #0f172a;
  --pap-profile-muted: #64748b;
  --pap-profile-primary: #2563eb;
  --pap-profile-success: #15803d;
  --pap-profile-warning: #b45309;
  --pap-profile-danger: #b91c1c;
  color: var(--pap-profile-text);
}

.pap-profile-loading {
  text-align: center;
  padding: 3rem 1rem;
}

.pap-profile-loading__title {
  color: #0f172a;
  font-weight: 700;
}

.pap-profile-loading__subtitle {
  color: #64748b;
  font-size: 0.92rem;
}

.pap-profile-feedback {
  margin-bottom: 1rem;
}

.pap-profile-hero {
  border-radius: 22px;
  padding: 1.5rem;
  margin-bottom: 1.25rem;
  color: #ffffff;
  box-shadow: 0 24px 40px rgba(15, 23, 42, 0.16);
}

.pap-profile-hero--success {
  background: linear-gradient(135deg, #0f766e, #15803d 58%, #166534);
}

.pap-profile-hero--warning {
  background: linear-gradient(135deg, #b45309, #d97706 55%, #92400e);
}

.pap-profile-hero--danger {
  background: linear-gradient(135deg, #b91c1c, #dc2626 55%, #9f1239);
}

.pap-profile-hero__main {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
}

.pap-profile-hero__eyebrow {
  margin-bottom: 0.4rem;
  font-size: 0.78rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  opacity: 0.85;
}

.pap-profile-hero__code {
  display: inline-flex;
  align-items: center;
  padding: 0.3rem 0.7rem;
  border-radius: 999px;
  margin-bottom: 0.75rem;
  background: rgba(255, 255, 255, 0.18);
  font-size: 0.78rem;
  font-weight: 700;
  letter-spacing: 0.06em;
}

.pap-profile-hero__title {
  margin-bottom: 0.45rem;
  font-size: 1.55rem;
  font-weight: 800;
}

.pap-profile-hero__subtitle {
  color: rgba(255, 255, 255, 0.86);
  font-size: 0.92rem;
}

.pap-profile-hero__meta {
  display: flex;
  flex-wrap: wrap;
  gap: 0.6rem;
  margin-top: 1rem;
}

.pap-profile-hero__pill {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.45rem 0.75rem;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.14);
  font-size: 0.78rem;
  font-weight: 600;
}

.pap-profile-hero__aside {
  min-width: 220px;
  text-align: right;
}

.pap-profile-hero__status {
  display: inline-flex;
  align-items: center;
  padding: 0.4rem 0.75rem;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.14);
  font-size: 0.78rem;
  font-weight: 700;
}

.pap-profile-hero__balance {
  margin-top: 0.85rem;
  font-size: 2rem;
  font-weight: 800;
  line-height: 1.1;
}

.pap-profile-hero__balance-caption {
  margin-top: 0.25rem;
  color: rgba(255, 255, 255, 0.82);
  font-size: 0.82rem;
}

.pap-profile-hero__actions {
  margin-top: 0.9rem;
}

.pap-profile-hero__progress {
  margin-top: 1.25rem;
  padding-top: 1rem;
  border-top: 1px solid rgba(255, 255, 255, 0.18);
}

.pap-profile-hero__progress-copy,
.pap-profile-hero__progress-meta {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
}

.pap-profile-hero__progress-copy {
  margin-bottom: 0.55rem;
  font-size: 0.88rem;
}

.pap-profile-hero__progress-meta {
  margin-top: 0.55rem;
  color: rgba(255, 255, 255, 0.82);
  font-size: 0.78rem;
}

.pap-profile-progress {
  height: 10px;
  border-radius: 999px;
  overflow: hidden;
  background: rgba(255, 255, 255, 0.16);
}

.pap-profile-progress__bar {
  height: 100%;
  border-radius: inherit;
  transition: width 0.25s ease;
}

.pap-profile-progress__bar--success {
  background: linear-gradient(90deg, rgba(255, 255, 255, 0.5), #dcfce7);
}

.pap-profile-progress__bar--warning {
  background: linear-gradient(90deg, rgba(255, 255, 255, 0.4), #fef3c7);
}

.pap-profile-progress__bar--danger {
  background: linear-gradient(90deg, rgba(255, 255, 255, 0.35), #fee2e2);
}

.pap-profile-hero__chips {
  display: flex;
  flex-wrap: wrap;
  gap: 0.6rem;
  margin-top: 1rem;
}

.pap-profile-hero__end-user {
  display: inline-flex;
  align-items: center;
  padding: 0.45rem 0.75rem;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.14);
  font-size: 0.78rem;
  font-weight: 600;
}

.pap-profile-hero__end-user--empty {
  color: rgba(255, 255, 255, 0.86);
}

.pap-summary-card {
  display: flex;
  align-items: flex-start;
  gap: 0.85rem;
  height: 100%;
  padding: 1rem;
  border: 1px solid var(--pap-profile-border);
  border-radius: 18px;
  background: var(--pap-profile-surface);
  box-shadow: 0 16px 30px rgba(15, 23, 42, 0.06);
}

.pap-summary-card__icon {
  width: 2.8rem;
  height: 2.8rem;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 14px;
  background: rgba(37, 99, 235, 0.1);
  color: var(--pap-profile-primary);
  font-size: 1.2rem;
}

.pap-summary-card__content {
  min-width: 0;
}

.pap-summary-card__label {
  display: block;
  margin-bottom: 0.25rem;
  color: var(--pap-profile-muted);
  font-size: 0.76rem;
  text-transform: uppercase;
  letter-spacing: 0.06em;
}

.pap-summary-card__value {
  display: block;
  font-size: 1.15rem;
  font-weight: 800;
}

.pap-summary-card__caption {
  display: block;
  margin-top: 0.35rem;
  color: var(--pap-profile-muted);
  font-size: 0.82rem;
  line-height: 1.5;
}

.pap-summary-card__value--neutral {
  color: var(--pap-profile-text);
}

.pap-summary-card__value--warning {
  color: var(--pap-profile-warning);
}

.pap-summary-card__value--primary {
  color: var(--pap-profile-primary);
}

.pap-summary-card__value--success {
  color: var(--pap-profile-success);
}

.pap-summary-card__value--danger {
  color: var(--pap-profile-danger);
}

.pap-alert-list {
  display: grid;
  gap: 0.8rem;
}

.pap-alert {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  padding: 0.95rem 1rem;
  border-radius: 16px;
}

.pap-alert__icon {
  width: 2.3rem;
  height: 2.3rem;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 12px;
  font-size: 1rem;
}

.pap-alert__title {
  font-weight: 700;
  margin-bottom: 0.15rem;
}

.pap-alert__text {
  font-size: 0.9rem;
  line-height: 1.5;
}

.pap-alert--danger {
  background: rgba(254, 226, 226, 0.88);
  color: #7f1d1d;
}

.pap-alert--danger .pap-alert__icon {
  background: rgba(185, 28, 28, 0.12);
}

.pap-alert--warning {
  background: rgba(254, 243, 199, 0.92);
  color: #78350f;
}

.pap-alert--warning .pap-alert__icon {
  background: rgba(217, 119, 6, 0.12);
}

.pap-alert--info {
  background: rgba(219, 234, 254, 0.92);
  color: #1d4ed8;
}

.pap-alert--info .pap-alert__icon {
  background: rgba(37, 99, 235, 0.1);
}

.pap-history {
  overflow: hidden;
  border: 1px solid var(--pap-profile-border);
  border-radius: 22px;
  background: var(--pap-profile-surface);
}

.pap-history__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  padding: 1.25rem 1.25rem 1rem;
  border-bottom: 1px solid var(--pap-profile-border);
}

.pap-history__subtitle {
  color: var(--pap-profile-muted);
  font-size: 0.84rem;
}

.pap-history__legend {
  display: flex;
  flex-wrap: wrap;
  justify-content: flex-end;
  gap: 0.5rem;
}

.pap-history__legend-item {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.45rem 0.7rem;
  border-radius: 999px;
  background: var(--pap-profile-surface-soft);
  color: var(--pap-profile-muted);
  font-size: 0.76rem;
  font-weight: 600;
}

.pap-history-table thead th {
  background: var(--pap-profile-surface-soft);
  color: var(--pap-profile-muted);
  font-size: 0.74rem;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  border-bottom: 1px solid var(--pap-profile-border);
  padding: 0.9rem 1rem;
}

.pap-history-table td {
  padding: 1rem;
  border-bottom: 1px solid rgba(148, 163, 184, 0.12);
  vertical-align: top;
}

.pap-history-table__date {
  color: var(--pap-profile-text);
  font-weight: 700;
}

.pap-history-table__description {
  color: var(--pap-profile-text);
  font-weight: 600;
  line-height: 1.45;
}

.pap-history-table__meta {
  margin-top: 0.2rem;
  color: var(--pap-profile-muted);
  font-size: 0.8rem;
  line-height: 1.5;
}

.pap-history-table__link {
  color: var(--pap-profile-primary);
  font-size: 0.82rem;
  font-weight: 700;
  text-decoration: none;
}

.pap-history-table__link:hover {
  text-decoration: underline;
}

.pap-log-card-list {
  display: grid;
  gap: 0.9rem;
  padding: 1rem;
}

.pap-log-card {
  padding: 1rem;
  border-radius: 18px;
  border: 1px solid var(--pap-profile-border);
  background: var(--pap-profile-surface-soft);
}

.pap-log-card__top {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 0.75rem;
}

.pap-log-card__date {
  color: var(--pap-profile-text);
  font-weight: 700;
}

.pap-log-card__reviewed,
.pap-log-card__reference span,
.pap-log-card__people span {
  color: var(--pap-profile-muted);
  font-size: 0.8rem;
}

.pap-log-card__badges {
  display: flex;
  flex-wrap: wrap;
  justify-content: flex-end;
  gap: 0.35rem;
}

.pap-log-card__description {
  margin-top: 0.9rem;
  color: var(--pap-profile-text);
  font-weight: 600;
  line-height: 1.5;
}

.pap-log-card__reference,
.pap-log-card__people,
.pap-log-card__amounts {
  display: grid;
  gap: 0.3rem;
  margin-top: 0.75rem;
}

.pap-log-card__link {
  display: inline-flex;
  align-items: center;
  margin-top: 0.75rem;
  color: var(--pap-profile-primary);
  font-size: 0.82rem;
  font-weight: 700;
  text-decoration: none;
}

.pap-log-card__actions {
  margin-top: 0.9rem;
}

.pap-history-empty {
  text-align: center;
  padding: 3rem 1.25rem;
}

.pap-history-empty__icon {
  font-size: 2.5rem;
  color: var(--pap-profile-muted);
}

.pap-history-empty__text {
  color: var(--pap-profile-muted);
  font-size: 0.92rem;
}

@media (max-width: 991px) {
  .pap-profile-hero__main,
  .pap-history__header {
    flex-direction: column;
  }

  .pap-profile-hero__aside {
    min-width: 0;
    width: 100%;
    text-align: left;
  }

  .pap-profile-hero__progress-copy,
  .pap-profile-hero__progress-meta {
    flex-direction: column;
    align-items: flex-start;
  }

  .pap-history__legend {
    justify-content: flex-start;
  }
}

@media (max-width: 576px) {
  .pap-profile-hero {
    padding: 1.15rem;
  }

  .pap-profile-hero__title {
    font-size: 1.3rem;
  }

  .pap-summary-card {
    padding: 0.9rem;
  }

  .pap-log-card__top {
    flex-direction: column;
  }

  .pap-log-card__badges {
    justify-content: flex-start;
  }
}

[data-bs-theme="dark"] .pap-profile {
  --pap-profile-surface: #0f172a;
  --pap-profile-surface-soft: #111c31;
  --pap-profile-border: rgba(148, 163, 184, 0.18);
  --pap-profile-text: #e2e8f0;
  --pap-profile-muted: #94a3b8;
  --pap-profile-primary: #93c5fd;
  --pap-profile-success: #86efac;
  --pap-profile-warning: #fdba74;
  --pap-profile-danger: #fca5a5;
}

[data-bs-theme="dark"] .pap-profile-loading__title {
  color: #e2e8f0;
}

[data-bs-theme="dark"] .pap-profile-loading__subtitle {
  color: #94a3b8;
}

[data-bs-theme="dark"] .pap-summary-card,
[data-bs-theme="dark"] .pap-history,
[data-bs-theme="dark"] .pap-log-card {
  box-shadow: 0 18px 34px rgba(2, 6, 23, 0.22);
}

[data-bs-theme="dark"] .pap-summary-card__icon {
  background: rgba(147, 197, 253, 0.14);
}

[data-bs-theme="dark"] .pap-alert--danger {
  background: rgba(127, 29, 29, 0.3);
  color: #fecaca;
}

[data-bs-theme="dark"] .pap-alert--warning {
  background: rgba(120, 53, 15, 0.34);
  color: #fed7aa;
}

[data-bs-theme="dark"] .pap-alert--info {
  background: rgba(30, 64, 175, 0.28);
  color: #bfdbfe;
}
</style>
