<template>
  <div class="plan-status-flow-wrap">
    <!-- APP-specific context banner -->
    <div v-if="normalizedPlanType === 'APP'" class="app-timeline-banner">
      <i class="ri-file-chart-2-line app-timeline-banner__icon"></i>
      <div>
        <div class="app-timeline-banner__title">Annual Procurement Plan Lifecycle</div>
        <div class="app-timeline-banner__desc">
          Pending → For Review → Reviewed/For Submission → Submitted/For Implementation. The APP ends at final submission, unlike PPMP/SPP which continue to consolidation.
        </div>
      </div>
    </div>

    <div class="plan-status-flow">
      <div
        v-for="(step, index) in resolvedSteps"
        :key="step.key"
        class="plan-status-flow__step-wrapper"
      >
        <div
          v-if="index > 0"
          class="plan-status-flow__line"
          :class="{
            completed: resolvedSteps[index - 1].state === 'completed',
            active: resolvedSteps[index - 1].state === 'active',
          }"
        >
          <span class="plan-status-flow__line-bar"></span>
          <i class="ri-arrow-right-s-line plan-status-flow__line-arrow"></i>
        </div>

        <div class="plan-status-flow__step" :class="step.state">
          <div class="plan-status-flow__dot">
            <i v-if="step.state === 'completed'" class="ri-check-line"></i>
            <i v-else-if="step.state === 'active'" class="ri-star-fill"></i>
            <i v-else class="ri-circle-line"></i>
          </div>
          <div class="plan-status-flow__label">{{ step.label }}</div>
          <div class="plan-status-flow__time" :class="{ pending: !step.date }">
            <template v-if="step.date">
              <span>{{ formatTimelineDate(step.date) }}</span>
              <span>{{ formatTimelineTime(step.date) }}</span>
            </template>
            <template v-else>
              {{ step.meta }}
            </template>
          </div>
          <div class="plan-status-flow__note">
            {{ step.note }}
          </div>
          <div v-if="step.user" class="plan-status-flow__actor">
            {{ step.user }}
          </div>
        </div>
      </div>
    </div>

    <div class="plan-status-flow__progress">
      <span class="plan-status-flow__progress-pill">
        Step {{ activeIndex + 1 }} of {{ resolvedSteps.length }}
      </span>
      <span class="plan-status-flow__progress-label">
        {{ currentStatus }}
      </span>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    plan: { type: Object, required: true },
    planType: { type: String, default: "PPMP" },
  },
  computed: {
    normalizedPlanType() {
      const t = (this.planType ?? "").toLowerCase();
      if (t === "app" || t === "annual" || t.includes("annual procurement")) return "APP";
      if (t === "spp" || t === "supplemental") return "SPP";
      return "PPMP";
    },
    currentStatus() {
      return String(
        this.plan.ppmp_status ||
          this.plan.approval_status ||
          this.plan.status?.name ||
          this.plan.status ||
          "Pending"
      );
    },
    normalizedStatus() {
      return this.currentStatus.toLowerCase();
    },
    lifecycleSteps() {
      if (this.normalizedPlanType === "APP") {
        return this.appLifecycleSteps;
      }
      return this.ppmpLifecycleSteps;
    },
    appLifecycleSteps() {
      return [
        {
          key: "pending",
          label: "Pending",
          date: this.plan.created_at || this.plan.date,
          user: this.userName(this.plan.created_by),
          note: "APP created and consolidated from approved PPMP/SPP entries.",
          pendingMeta: "Not started",
        },
        {
          key: "for-review",
          label: "For Review",
          date: this.appStatusDate("for-review"),
          user: this.statusUser("for-review"),
          note: "APP submitted for BAC Secretariat review.",
          pendingMeta: "Awaiting submission for review",
        },
        {
          key: "reviewed",
          label: "Reviewed/For Submission",
          date: this.appStatusDate("reviewed"),
          user: this.statusUser("reviewed"),
          note: "APP reviewed and endorsed by the BAC.",
          pendingMeta: "Awaiting BAC review",
        },
        {
          key: "submitted",
          label: "Submitted/For Implementation",
          date: this.appStatusDate("submitted"),
          user: this.statusUser("submitted"),
          note: "APP submitted for implementation. Approved by the Head of the Procuring Entity.",
          pendingMeta: "Awaiting final submission",
        },
      ];
    },
    ppmpLifecycleSteps() {
      const finalPlanName = this.normalizedPlanType === "SPP" ? "SPP" : "APP";
      const finalSubmissionLabel = this.normalizedPlanType === "SPP"
        ? "Submitted/For Implementation"
        : "Submitted/For Consolidation";

      return [
        {
          key: "pending",
          label: "Pending",
          date: this.plan.created_at || this.plan.date,
          user: this.userName(this.plan.created_by),
          note: "Plan has been created and recorded.",
          pendingMeta: "Not started",
        },
        {
          key: "for-review",
          label: "For Review",
          date: this.ppmpStatusDate("for-review"),
          user: this.statusUser("for-review"),
          note: "Submitted for review by the responsible office.",
          pendingMeta: "Awaiting submission",
        },
        {
          key: "reviewed",
          label: "Reviewed/For Submission",
          date: this.ppmpStatusDate("reviewed"),
          user: this.statusUser("reviewed"),
          note: "Budget review is done and the plan is ready for submission.",
          pendingMeta: "Awaiting budget review",
        },
        {
          key: "submitted",
          label: finalSubmissionLabel,
          date: this.ppmpStatusDate("submitted"),
          user: this.statusUser("submitted"),
          note: this.normalizedPlanType === "SPP"
            ? "SPP is submitted for implementation."
            : "Plan is submitted for BAC consolidation.",
          pendingMeta: "Awaiting procurement review",
        },
        {
          key: "consolidated",
          label: `Consolidated/Added to ${finalPlanName}`,
          date: this.ppmpStatusDate("consolidated"),
          user: this.statusUser("consolidated"),
          note: "Plan has been added to the consolidated procurement plan.",
          pendingMeta: "Awaiting consolidation",
        },
      ];
    },
    activeIndex() {
      if (this.normalizedPlanType === "APP") {
        return this.appActiveIndex;
      }
      return this.ppmpActiveIndex;
    },
    appActiveIndex() {
      const s = this.normalizedStatus;
      // Match the actual status labels used by the backend for APP
      if (s.includes("implementation") || s.includes("submitted/for"))  return 3;
      if (s.includes("reviewed") || s.includes("for submission"))        return 2;
      if (s.includes("for review"))                                       return 1;
      // Fallback: derive from timestamps if status name isn't set
      if (this.plan.approved_at) return 3;
      if (this.plan.reviewed_at) return 2;
      if (this.plan.submitted_for_review_at || this.plan.submitted_at) return 1;
      return 0;
    },
    ppmpActiveIndex() {
      const index = this.ppmpLifecycleSteps.findIndex(
        (step) => this.normalizedStatus === step.label.toLowerCase()
      );
      if (index >= 0) return index;
      if (this.normalizedStatus.includes("implementation")) return 3;
      if (this.normalizedStatus.includes("consolidated")) return this.ppmpLifecycleSteps.length - 1;
      if (this.normalizedStatus.includes("submitted")) return 3;
      if (this.normalizedStatus.includes("reviewed")) return 2;
      if (this.normalizedStatus.includes("for review")) return 1;
      return 0;
    },
    resolvedSteps() {
      return this.lifecycleSteps.map((step, index) => ({
        ...step,
        state: index < this.activeIndex ? "completed" : index === this.activeIndex ? "active" : "pending",
        meta: step.date
          ? this.formatTimelineDate(step.date)
          : index <= this.activeIndex
            ? "Date not recorded"
            : step.pendingMeta,
      }));
    },
  },
  methods: {
    appStatusDate(statusKey) {
      const dates = {
        "for-review": this.plan.submitted_for_review_at || this.plan.submitted_at,
        reviewed: this.plan.reviewed_at,
        submitted: this.plan.approved_at || this.plan.submitted_for_implementation_at,
      };

      return dates[statusKey] || null;
    },
    ppmpStatusDate(statusKey) {
      const dates = {
        "for-review": this.plan.submitted_for_review_at || this.plan.submitted_at,
        reviewed: this.plan.reviewed_at,
        submitted: this.plan.submitted_for_consolidation_at || this.plan.submitted_for_implementation_at || this.plan.approved_at,
        consolidated: this.plan.consolidated_at,
      };

      return dates[statusKey] || null;
    },
    statusUser(statusKey) {
      const users = {
        "for-review": this.plan.submitted_for_review_by || this.plan.submitted_by,
        reviewed: this.plan.reviewed_by,
        submitted: this.plan.submitted_for_consolidation_by || this.plan.submitted_for_implementation_by || this.plan.approved_by,
        consolidated: this.plan.consolidated_by,
      };

      return this.userName(users[statusKey]);
    },
    formatTimelineDate(value) {
      if (!value) {
        return "-";
      }

      const parsedDate = new Date(value);

      if (Number.isNaN(parsedDate.getTime())) {
        return value;
      }

      return parsedDate.toLocaleDateString("en-PH", {
        year: "numeric",
        month: "short",
        day: "numeric",
      });
    },
    formatTimelineTime(value) {
      if (!value) {
        return "";
      }

      const parsedDate = new Date(value);

      if (Number.isNaN(parsedDate.getTime())) {
        return "";
      }

      return parsedDate.toLocaleTimeString("en-PH", {
        hour: "numeric",
        minute: "2-digit",
      });
    },
    userName(value) {
      if (!value) {
        return "";
      }

      if (typeof value === "string") {
        return value;
      }

      return value.name || value.profile?.full_name || value.profile?.name || "";
    },
  },
};
</script>

<style scoped>
.plan-status-flow-wrap {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
}

.plan-status-flow {
  display: flex;
  align-items: stretch;
  gap: 0;
  padding: 2px 0 4px;
  overflow-x: auto;
  scrollbar-width: thin;
  max-width: 100%;
}

.plan-status-flow__step-wrapper {
  display: flex;
  align-items: center;
}

/* Connector line */
.plan-status-flow__line {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  position: relative;
  min-width: 28px;
  gap: 0;
}

.plan-status-flow__line-bar {
  display: block;
  width: 100%;
  height: 3px;
  border-radius: 2px;
  background: rgba(148, 163, 184, 0.28);
  transition: background 0.3s ease;
  flex: 1;
}

.plan-status-flow__line-arrow {
  color: rgba(148, 163, 184, 0.42);
  font-size: 18px;
  flex-shrink: 0;
  line-height: 1;
}

.plan-status-flow__line.completed .plan-status-flow__line-bar {
  background: #22c55e;
}

.plan-status-flow__line.completed .plan-status-flow__line-arrow {
  color: #22c55e;
}

.plan-status-flow__line.active .plan-status-flow__line-bar {
  background: linear-gradient(90deg, #22c55e 60%, rgba(59, 130, 246, 0.4) 100%);
}

.plan-status-flow__line.active .plan-status-flow__line-arrow {
  color: #3b82f6;
}

/* Step card */
.plan-status-flow__step {
  display: inline-flex;
  flex-direction: column;
  gap: 6px;
  min-width: 172px;
  max-width: 172px;
  min-height: 168px;
  padding: 12px;
  border: 1px solid var(--ppmp-border, rgba(148, 163, 184, 0.22));
  border-radius: 14px;
  background: rgba(255, 255, 255, 0.74);
  box-shadow: 0 10px 22px rgba(15, 23, 42, 0.05);
}

.plan-status-flow__step.completed {
  border-color: rgba(34, 197, 94, 0.28);
  background: linear-gradient(135deg, rgba(220, 252, 231, 0.95), rgba(240, 253, 244, 0.95));
}

.plan-status-flow__step.active {
  border-color: rgba(59, 130, 246, 0.3);
  background: linear-gradient(135deg, rgba(219, 234, 254, 0.96), rgba(239, 246, 255, 0.98));
  box-shadow: 0 14px 28px rgba(37, 99, 235, 0.12);
}

.plan-status-flow__step.pending {
  background: rgba(248, 250, 252, 0.94);
}

/* Dot icons */
.plan-status-flow__dot {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 34px;
  height: 34px;
  border-radius: 999px;
  background: rgba(148, 163, 184, 0.14);
  color: #64748b;
  font-size: 15px;
  flex-shrink: 0;
}

.plan-status-flow__step.completed .plan-status-flow__dot {
  background: linear-gradient(135deg, #22c55e, #16a34a);
  color: #ffffff;
}

.plan-status-flow__step.active .plan-status-flow__dot {
  background: linear-gradient(135deg, #3b82f6, #2563eb);
  color: #ffffff;
  animation: plan-status-pulse 2s ease-in-out infinite;
}

@keyframes plan-status-pulse {
  0%, 100% {
    box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.5);
  }
  50% {
    box-shadow: 0 0 0 7px rgba(59, 130, 246, 0);
  }
}

.plan-status-flow__label {
  color: var(--ppmp-text, #1e293b);
  font-size: 12px;
  font-weight: 800;
  line-height: 1.25;
}

.plan-status-flow__time {
  display: flex;
  flex-direction: column;
  gap: 2px;
  min-height: 35px;
  color: var(--ppmp-muted, #64748b);
  font-size: 11px;
  line-height: 1.25;
}

.plan-status-flow__time.pending {
  color: #94a3b8;
  font-style: italic;
}

.plan-status-flow__note {
  margin-top: auto;
  color: var(--ppmp-muted, #64748b);
  font-size: 11px;
  line-height: 1.35;
}

.plan-status-flow__actor {
  color: var(--ppmp-text, #1e293b);
  font-size: 10.5px;
  font-weight: 700;
  line-height: 1.25;
  overflow-wrap: anywhere;
}

/* APP banner */
.app-timeline-banner {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  width: 100%;
  padding: 10px 14px;
  border-radius: 10px;
  background: linear-gradient(135deg, rgba(254, 243, 199, 0.7), rgba(255, 251, 235, 0.9));
  border: 1px solid rgba(251, 191, 36, 0.28);
  margin-bottom: 4px;
}

.app-timeline-banner__icon {
  font-size: 1.25rem;
  color: #d97706;
  flex-shrink: 0;
  margin-top: 1px;
}

.app-timeline-banner__title {
  font-size: 12px;
  font-weight: 800;
  color: #92400e;
  margin-bottom: 2px;
}

.app-timeline-banner__desc {
  font-size: 11px;
  color: #b45309;
  line-height: 1.4;
}

[data-bs-theme="dark"] .app-timeline-banner {
  background: linear-gradient(135deg, rgba(120, 53, 15, 0.25), rgba(92, 45, 12, 0.22));
  border-color: rgba(251, 191, 36, 0.18);
}

[data-bs-theme="dark"] .app-timeline-banner__title { color: #fcd34d; }
[data-bs-theme="dark"] .app-timeline-banner__desc  { color: #fbbf24; }

/* Progress pill row */
.plan-status-flow__progress {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 0 2px;
}

.plan-status-flow__progress-pill {
  display: inline-flex;
  align-items: center;
  padding: 2px 10px;
  border: 1px solid rgba(59, 130, 246, 0.25);
  border-radius: 999px;
  background: rgba(219, 234, 254, 0.6);
  color: #2563eb;
  font-size: 10.5px;
  font-weight: 800;
  white-space: nowrap;
}

.plan-status-flow__progress-label {
  color: var(--ppmp-muted, #64748b);
  font-size: 11px;
  font-weight: 600;
}

/* Dark mode */
[data-bs-theme="dark"] .plan-status-flow__step {
  background: rgba(27, 34, 48, 0.92);
  box-shadow: none;
}

[data-bs-theme="dark"] .plan-status-flow__step.completed {
  background: linear-gradient(135deg, rgba(22, 163, 74, 0.18), rgba(21, 128, 61, 0.16));
}

[data-bs-theme="dark"] .plan-status-flow__step.active {
  background: linear-gradient(135deg, rgba(30, 64, 175, 0.48), rgba(30, 41, 59, 0.96));
}

[data-bs-theme="dark"] .plan-status-flow__step.pending {
  background: rgba(35, 44, 58, 0.92);
}

[data-bs-theme="dark"] .plan-status-flow__dot {
  background: rgba(148, 163, 184, 0.14);
  color: #cbd5e1;
}

[data-bs-theme="dark"] .plan-status-flow__line-bar {
  background: rgba(148, 163, 184, 0.2);
}

[data-bs-theme="dark"] .plan-status-flow__line-arrow {
  color: rgba(148, 163, 184, 0.34);
}

[data-bs-theme="dark"] .plan-status-flow__line.completed .plan-status-flow__line-bar {
  background: #4ade80;
}

[data-bs-theme="dark"] .plan-status-flow__line.completed .plan-status-flow__line-arrow {
  color: #4ade80;
}

[data-bs-theme="dark"] .plan-status-flow__line.active .plan-status-flow__line-arrow {
  color: #fbbf24;
}

[data-bs-theme="dark"] .plan-status-flow__progress-pill {
  background: rgba(30, 64, 175, 0.28);
  border-color: rgba(59, 130, 246, 0.3);
  color: #93c5fd;
}

@media (max-width: 992px) {
  .plan-status-flow__step {
    min-width: 164px;
    max-width: 164px;
    min-height: 158px;
  }
}
</style>
