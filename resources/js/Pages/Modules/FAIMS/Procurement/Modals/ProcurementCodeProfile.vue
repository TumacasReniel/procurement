<template>
  <b-modal
    v-model="showModal"
    title="PAP Code Profile"
    size="xl"
    centered
    scrollable
    hide-footer
  >
    <div v-if="loading" class="text-center py-5">
      <b-spinner variant="primary" class="mb-3" />
      <p class="text-muted mb-0">Loading PAP code profile...</p>
    </div>

    <div v-else-if="selected">
      <div
        v-if="feedback.message"
        class="alert rounded-4 border-0"
        :class="feedback.type === 'error' ? 'alert-danger' : 'alert-success'"
      >
        <div class="fw-semibold">{{ feedback.message }}</div>
        <div v-if="feedback.info" class="fs-12 mt-1">{{ feedback.info }}</div>
      </div>

      <div class="rounded-4 border bg-light-subtle p-4 mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
          <div>
            <span class="badge bg-success-subtle text-success fs-12 mb-2">
              {{ selected.code }}
            </span>
            <h4 class="mb-2">{{ selected.title }}</h4>
            <p class="text-muted mb-0">
              {{ selected.mode_of_procurement?.name || "No mode of procurement" }}
              <span class="mx-2">/</span>
              {{ selected.app_type?.name || "No APP type" }}
              <span class="mx-2">/</span>
              {{ selected.year || "No year" }}
            </p>
          </div>

          <div class="text-md-end">
            <div class="text-muted fs-12 mb-1">History Entries</div>
            <div class="fs-4 fw-semibold">{{ selected.budget_logs_count || logs.length }}</div>
            <div v-if="canRequestBudgetIncrease" class="mt-3">
              <b-button variant="primary" size="sm" @click="requestAdditionalBudget">
                <i class="ri-funds-line align-bottom me-1"></i>
                Request Additional Budget
              </b-button>
            </div>
          </div>
        </div>

        <div class="mt-3">
          <span
            v-for="(endUser, index) in selected.end_users || []"
            :key="index"
            class="badge bg-primary-subtle text-primary me-2 mb-2"
          >
            {{ endUser.end_user?.name || "Unknown end user" }}
          </span>
        </div>
      </div>

      <div class="row g-3 mb-4">
        <div class="col-md-3">
          <div class="rounded-4 border h-100 p-3 bg-white">
            <div class="text-muted fs-12 text-uppercase mb-1">Allocated Budget</div>
            <div class="fs-3 fw-semibold text-dark">
              {{ formatCurrency(selected.allocated_budget) }}
            </div>
          </div>
        </div>

        <div class="col-md-3">
          <div class="rounded-4 border h-100 p-3 bg-white">
            <div class="text-muted fs-12 text-uppercase mb-1">Deducted Budget</div>
            <div class="fs-3 fw-semibold text-warning">
              {{ formatCurrency(selected.deducted_budget) }}
            </div>
          </div>
        </div>

        <div class="col-md-3">
          <div class="rounded-4 border h-100 p-3 bg-white">
            <div class="text-muted fs-12 text-uppercase mb-1">Pending Top-Ups</div>
            <div class="fs-3 fw-semibold text-primary">
              {{ selected.pending_budget_increase_requests_count || 0 }}
            </div>
          </div>
        </div>

        <div class="col-md-3">
          <div class="rounded-4 border h-100 p-3 bg-white">
            <div class="text-muted fs-12 text-uppercase mb-1">Remaining Balance</div>
            <div
              class="fs-3 fw-semibold"
              :class="selected.remaining_budget < 0 ? 'text-danger' : 'text-success'"
            >
              {{ formatCurrency(selected.remaining_budget) }}
            </div>
          </div>
        </div>
      </div>

      <div
        v-if="selected.remaining_budget < 0"
        class="alert alert-danger rounded-4 border-0"
      >
        This PAP code is currently over-allocated by
        <strong>{{ formatCurrency(Math.abs(selected.remaining_budget)) }}</strong>.
      </div>

      <div
        v-if="Number(selected.pending_budget_increase_requests_count) > 0"
        class="alert alert-info rounded-4 border-0"
      >
        There
        {{ Number(selected.pending_budget_increase_requests_count) === 1 ? "is" : "are" }}
        <strong>{{ selected.pending_budget_increase_requests_count }}</strong>
        pending budget increase
        {{ Number(selected.pending_budget_increase_requests_count) === 1 ? "request" : "requests" }}
        waiting for review.
      </div>

      <div class="rounded-4 border bg-white">
        <div class="border-bottom px-4 py-3">
          <h5 class="mb-1">Budget History & Requests</h5>
          <p class="text-muted fs-12 mb-0">
            Track approved deductions, requested budget top-ups, and Budget Officer decisions.
          </p>
        </div>

        <div v-if="logs.length" class="table-responsive">
          <table class="table align-middle mb-0">
            <thead class="table-light">
              <tr class="fs-12 text-uppercase">
                <th>Date</th>
                <th>Type</th>
                <th>Status</th>
                <th>Reference</th>
                <th>Description</th>
                <th>Requested By</th>
                <th>Reviewed / Processed By</th>
                <th class="text-end">Amount</th>
                <th class="text-end">Before</th>
                <th class="text-end">After</th>
                <th v-if="canReviewPendingRequests" class="text-center">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="log in logs" :key="log.id">
                <td>
                  <div class="text-nowrap">{{ formatDate(log.created_at) }}</div>
                  <div v-if="log.reviewed_at" class="text-muted fs-12">
                    Reviewed: {{ formatDate(log.reviewed_at) }}
                  </div>
                </td>
                <td>
                  <span
                    class="badge"
                    :class="
                      log.type === 'approval_deduction'
                        ? 'bg-warning-subtle text-warning'
                        : 'bg-primary-subtle text-primary'
                    "
                  >
                    {{ log.type_label }}
                  </span>
                </td>
                <td>
                  <span class="badge" :class="statusClass(log.status)">
                    {{ log.status_label }}
                  </span>
                </td>
                <td>
                  <div class="fw-semibold">{{ log.procurement?.code || "Direct budget request" }}</div>
                  <div class="text-muted fs-12">
                    {{ log.procurement?.status || "No procurement reference" }}
                  </div>
                </td>
                <td>
                  <div>{{ log.description || log.type_label }}</div>
                  <div class="text-muted fs-12">
                    {{ log.procurement?.title || "No linked procurement title" }}
                  </div>
                </td>
                <td>
                  {{ log.requested_by?.name || log.processed_by?.name || "System" }}
                </td>
                <td>
                  <div>{{ log.reviewed_by?.name || log.processed_by?.name || "Pending review" }}</div>
                  <div v-if="log.reviewed_at" class="text-muted fs-12">
                    {{ formatDate(log.reviewed_at) }}
                  </div>
                </td>
                <td
                  class="text-end fw-semibold"
                  :class="log.amount_direction === 'decrease' ? 'text-warning' : 'text-primary'"
                >
                  {{ log.amount_direction === "decrease" ? "-" : "+" }}
                  {{ formatCurrency(log.amount) }}
                </td>
                <td class="text-end">{{ formatCurrency(log.balance_before) }}</td>
                <td
                  class="text-end fw-semibold"
                  :class="log.balance_after < 0 ? 'text-danger' : 'text-success'"
                >
                  {{ formatCurrency(log.balance_after) }}
                </td>
                <td v-if="canReviewPendingRequests" class="text-center">
                  <div
                    v-if="isPendingBudgetIncrease(log)"
                    class="d-flex justify-content-center gap-1"
                  >
                    <b-button
                      size="sm"
                      variant="success"
                      class="btn-icon"
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
                      variant="danger"
                      class="btn-icon"
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

        <div v-else class="text-center py-5">
          <i class="ri-history-line fs-1 text-muted"></i>
          <h6 class="mt-3 mb-1">No budget history yet</h6>
          <p class="text-muted mb-0">
            Budget deductions and top-up requests will appear here once activity starts.
          </p>
        </div>
      </div>
    </div>
  </b-modal>
</template>

<script>
import { router } from "@inertiajs/vue3";

export default {
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
        return ["Procurement Staff", "Procurement Officer", "Administrator"].includes(role);
      });
    },
    canReviewPendingRequests() {
      return this.currentRoles.some((role) => {
        return ["Budget Officer", "Administrator"].includes(role);
      });
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
