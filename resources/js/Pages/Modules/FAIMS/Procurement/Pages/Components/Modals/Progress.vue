<template>
  <b-modal
    v-model="modalValue"
    header-class="p-3 bg-light"
    title="Procurement Progress"
    centered
    hide-footer
    size="xl"
    modal-class="progress-floating-modal"
    body-class="progress-modal-body"
  >
    <div class="status-flow-panel progress-modal-panel">
      <div
        class="status-flow-banner-header"
        @click="toggleStatusFlow"
        style="cursor: pointer;"
      >
        <div class="d-flex align-items-center flex-wrap gap-2">
          <i class="ri-flow-chart text-white status-flow-header-icon"></i>
          <span class="fw-bold text-white">Procurement Progress</span>
          <span class="status-flow-glass-badge ms-2">
            {{ procurement.status?.name || "N/A" }}
          </span>
          <span
            v-if="procurement.sub_status"
            class="status-flow-glass-badge status-flow-glass-badge--sub ms-1"
          >
            {{ procurement.sub_status?.name }}
          </span>
        </div>
        <i
          :class="isStatusFlowCollapsed ? 'ri-arrow-down-s-line' : 'ri-arrow-up-s-line'"
          class="text-white status-flow-toggle-icon"
        ></i>
      </div>

      <div v-show="!isStatusFlowCollapsed" class="status-flow-banner-content">
        <div class="status-flow-section">
          <div class="status-flow-section-label">
            <i class="ri-route-line me-1"></i>Main Status
          </div>
          <div class="status-flow-banner-track">
            <div
              v-for="(status, index) in statusFlowNav"
              :key="`main-${status.name}`"
              class="status-flow-banner-step-wrapper"
            >
              <div
                v-if="index > 0"
                class="status-flow-banner-line"
                :class="{
                  connected: statusFlowNav[index - 1].isPast,
                  active: statusFlowNav[index - 1].isPast && status.isCurrent,
                }"
              >
                <i class="ri-arrow-right-s-line"></i>
              </div>
              <div
                class="status-flow-banner-step"
                :class="{
                  completed: status.isPast,
                  active: status.isCurrent,
                  pending: !status.isPast && !status.isCurrent,
                }"
                :style="{ cursor: 'pointer' }"
                @click="$emit('open-status-tip', status.name)"
              >
                <div class="status-flow-banner-dot">
                  <i v-if="status.isPast" class="ri-check-line"></i>
                  <i v-else-if="status.isCurrent" class="ri-star-fill"></i>
                  <i v-else class="ri-circle-line"></i>
                </div>
                <div class="status-flow-banner-label">{{ status.name }}</div>
                <div
                  v-if="shouldShowStatusTimeline(status)"
                  class="status-flow-banner-time"
                  :class="{ pending: !status.updatedAt }"
                >
                  <template v-if="status.updatedAt">
                    <span>{{ formatStatusTimelineDate(status.updatedAt) }}</span>
                    <span>{{ formatStatusTimelineTime(status.updatedAt) }}</span>
                    <span
                      v-if="status.updatedBy"
                      class="status-flow-banner-actor"
                    >
                      By {{ status.updatedBy }}
                    </span>
                  </template>
                  <template v-else>Not yet</template>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div v-if="procurement.sub_status" class="status-flow-section mt-2">
          <div class="status-flow-section-label">
            <i class="ri-git-branch-line me-1"></i>Sub Status
          </div>
          <div class="status-flow-banner-track">
            <div
              v-for="(status, index) in subStatusFlowNav"
              :key="`sub-${status.name}`"
              class="status-flow-banner-step-wrapper"
            >
              <div
                v-if="index > 0"
                class="status-flow-banner-line"
                :class="{
                  connected: subStatusFlowNav[index - 1].isPast,
                  active: subStatusFlowNav[index - 1].isPast && status.isCurrent,
                }"
              >
                <i class="ri-arrow-right-s-line"></i>
              </div>
              <div
                class="status-flow-banner-step"
                :class="{
                  completed: status.isPast,
                  active: status.isCurrent,
                  pending: !status.isPast && !status.isCurrent,
                }"
                :style="{ cursor: 'pointer' }"
                @click="$emit('open-status-tip', status.name)"
              >
                <div class="status-flow-banner-dot">
                  <i v-if="status.isPast" class="ri-check-line"></i>
                  <i v-else-if="status.isCurrent" class="ri-star-fill"></i>
                  <i v-else class="ri-circle-line"></i>
                </div>
                <div class="status-flow-banner-label">{{ status.name }}</div>
                <div
                  v-if="shouldShowStatusTimeline(status)"
                  class="status-flow-banner-time"
                  :class="{ pending: !status.updatedAt }"
                >
                  <template v-if="status.updatedAt">
                    <span>{{ formatStatusTimelineDate(status.updatedAt) }}</span>
                    <span>{{ formatStatusTimelineTime(status.updatedAt) }}</span>
                    <span
                      v-if="status.updatedBy"
                      class="status-flow-banner-actor"
                    >
                      By {{ status.updatedBy }}
                    </span>
                  </template>
                  <template v-else>Not yet</template>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>
     <div class="status-flow-note mt-3">
        <i class="ri-information-line"></i>
        <span><strong>Note:</strong> Click a card status to show more details.</span>
      </div>
  </b-modal>
</template>

<script>
export default {
  props: {
    modelValue: {
      type: Boolean,
      default: false,
    },
    procurement: {
      type: Object,
      default: () => ({}),
    },
    statusFlowNav: {
      type: Array,
      default: () => [],
    },
    subStatusFlowNav: {
      type: Array,
      default: () => [],
    },
  },
  emits: ["update:modelValue", "open-status-tip"],
  data() {
    return {
      isStatusFlowCollapsed: false,
    };
  },
  computed: {
    modalValue: {
      get() {
        return this.modelValue;
      },
      set(value) {
        this.$emit("update:modelValue", value);
      },
    },
  },
  methods: {
    toggleStatusFlow() {
      this.isStatusFlowCollapsed = !this.isStatusFlowCollapsed;
    },
    normalizeStatusLabel(statusName) {
      const aliases = {
        "For RFQ": "For Quotations",
        "For BAC": "For BAC Resolution",
        "For Approval": "For Approval of BAC Resolution",
        "NOA Served": "NOA Served to Supplier",
        "NOA Confirmed": "NOA Conformed",
        "PO Items Delivered": "Items Delivered",
        "PO Delivered/For Inspection": "PO Items Delivered",
        "Delivered/For Inspection": "Items Delivered",
        Delivered: "Items Delivered",
      };

      return aliases[statusName] || statusName;
    },
    shouldShowStatusTimeline(status) {
      const normalizedStatus = this.normalizeStatusLabel(status?.name || "");

      if (status?.updatedAt) {
        return true;
      }

      if (status?.isPast || status?.isCurrent) {
        return false;
      }

      return normalizedStatus !== "For Quotations";
    },
    formatStatusTimelineDate(dateString) {
      const date = new Date(dateString);
      if (Number.isNaN(date.getTime())) {
        return "Invalid date";
      }

      return date.toLocaleDateString("en-US", {
        month: "short",
        day: "numeric",
        year: "numeric",
      });
    },
    formatStatusTimelineTime(dateString) {
      const date = new Date(dateString);
      if (Number.isNaN(date.getTime())) {
        return "";
      }

      return date.toLocaleTimeString("en-US", {
        hour: "numeric",
        minute: "2-digit",
      });
    },
  },
};
</script>

<style scoped>
:deep(.progress-floating-modal .modal-dialog) {
  max-width: min(1450px, calc(100vw - 32px));
}

:deep(.progress-floating-modal .modal-content) {
  border: 0;
  border-radius: 20px;
  overflow: hidden;
  box-shadow: 0 24px 60px rgba(15, 23, 42, 0.28);
}

:deep(.progress-modal-body) {
  padding: 0;
  background: linear-gradient(135deg, #f4f6fb 0%, #e7eaf3 100%);
}

.progress-modal-panel {
  margin-bottom: 0;
}

/* Base glass surface: rich gradient + blurred aurora blobs behind frosted cards */
.status-flow-panel {
  position: relative;
  overflow: hidden;
  padding: 1.1rem 1.3rem 1.3rem;
  border-radius: 18px;
  background: linear-gradient(135deg, #4c5da3 0%, #3a4788 55%, #2f3b73 100%);
  isolation: isolate;
}

.status-flow-panel::before,
.status-flow-panel::after {
  content: "";
  position: absolute;
  border-radius: 50%;
  filter: blur(30px);
  pointer-events: none;
  z-index: 0;
}

.status-flow-panel::before {
  width: 280px;
  height: 280px;
  top: -120px;
  left: -80px;
  background: radial-gradient(circle, rgba(129, 140, 248, 0.55), transparent 70%);
}

.status-flow-panel::after {
  width: 320px;
  height: 320px;
  bottom: -160px;
  right: -100px;
  background: radial-gradient(circle, rgba(251, 191, 36, 0.28), transparent 70%);
}

.status-flow-banner-header,
.status-flow-banner-content,
.status-flow-note {
  position: relative;
  z-index: 1;
}

.status-flow-banner-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  color: white;
  font-size: 1rem;
  margin-bottom: 0.9rem;
  padding: 0.2rem 0 0.9rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.16);
}

.status-flow-header-icon {
  font-size: 1.3rem;
  background: rgba(255, 255, 255, 0.14);
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.22);
  padding: 0.5rem;
  border-radius: 10px;
  line-height: 1;
}

.status-flow-banner-header .fw-bold {
  font-size: 1.1rem;
  letter-spacing: 0.3px;
}

.status-flow-glass-badge {
  display: inline-flex;
  align-items: center;
  font-size: 0.75rem;
  font-weight: 700;
  padding: 0.35rem 0.75rem;
  border-radius: 999px;
  color: #ffffff;
  background: rgba(255, 255, 255, 0.16);
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.3);
  box-shadow: 0 2px 10px rgba(15, 23, 42, 0.18);
}

.status-flow-glass-badge--sub {
  font-size: 0.7rem;
  padding: 0.3rem 0.65rem;
  background: rgba(251, 191, 36, 0.22);
  border-color: rgba(251, 191, 36, 0.4);
}

.status-flow-toggle-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 2rem;
  height: 2rem;
  font-size: 1.1rem;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.12);
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
  border: 1px solid rgba(255, 255, 255, 0.2);
  transition: background 0.2s ease, transform 0.2s ease;
}

.status-flow-banner-header:hover .status-flow-toggle-icon {
  background: rgba(255, 255, 255, 0.2);
}

.status-flow-banner-content {
  padding-top: 0.35rem;
}

.status-flow-section {
  margin-bottom: 1rem;
}

.status-flow-section-label {
  font-size: 0.75rem;
  font-weight: 700;
  color: rgba(255, 255, 255, 0.85);
  text-transform: uppercase;
  letter-spacing: 1px;
  margin-bottom: 0.85rem;
  display: flex;
  align-items: center;
  background: rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
  border: 1px solid rgba(255, 255, 255, 0.14);
  padding: 0.45rem 0.85rem;
  border-radius: 20px;
  width: fit-content;
}

.status-flow-section-label i {
  margin-right: 0.5rem;
}

.status-flow-banner-track {
  display: flex;
  align-items: center;
  gap: 0;
  overflow-x: auto;
  padding: 0.65rem 0.1rem 0.75rem;
  scrollbar-width: thin;
  scrollbar-color: rgba(255, 255, 255, 0.3) transparent;
}

.status-flow-banner-track::-webkit-scrollbar {
  height: 6px;
}

.status-flow-banner-track::-webkit-scrollbar-track {
  background: rgba(255, 255, 255, 0.1);
  border-radius: 3px;
}

.status-flow-banner-track::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.3);
  border-radius: 3px;
}

.status-flow-banner-step-wrapper {
  display: flex;
  align-items: center;
  gap: 0;
}

.status-flow-banner-line {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0 0.15rem;
  flex-shrink: 0;
  min-width: 22px;
}

.status-flow-banner-line::before {
  content: "";
  position: absolute;
  top: 50%;
  left: 0;
  right: 0;
  height: 2px;
  background: rgba(255, 255, 255, 0.14);
  transform: translateY(-50%);
  z-index: 0;
  transition: background 0.3s ease;
}

.status-flow-banner-line.connected::before {
  background: linear-gradient(90deg, rgba(74, 222, 128, 0.75), rgba(74, 222, 128, 0.35));
}

.status-flow-banner-line.active::before {
  background: linear-gradient(90deg, rgba(74, 222, 128, 0.6), rgba(251, 191, 36, 0.75));
}

.status-flow-banner-line i {
  position: relative;
  z-index: 1;
  font-size: 1.1rem;
  color: rgba(255, 255, 255, 0.35);
  transition: all 0.3s ease;
}

.status-flow-banner-line.connected i {
  color: #4ade80;
  text-shadow: 0 0 8px rgba(74, 222, 128, 0.6);
}

.status-flow-banner-line.active i {
  color: #fbbf24;
  text-shadow: 0 0 10px rgba(251, 191, 36, 0.8);
  animation: linePulse 1.5s ease-in-out infinite;
}

.status-flow-banner-step {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.35rem;
  min-width: 96px;
  padding: 0.72rem 0.5rem;
  border-radius: 14px;
  background: rgba(255, 255, 255, 0.06);
  backdrop-filter: blur(14px);
  -webkit-backdrop-filter: blur(14px);
  border: 1px solid rgba(255, 255, 255, 0.14);
  box-shadow: 0 4px 16px rgba(15, 23, 42, 0.16);
  transition: transform 0.25s ease, box-shadow 0.25s ease, background 0.25s ease, border-color 0.25s ease;
  cursor: default;
}

.status-flow-banner-step:hover {
  transform: translateY(-3px);
  box-shadow: 0 10px 24px rgba(15, 23, 42, 0.24);
}

.status-flow-banner-step.completed {
  background: linear-gradient(160deg, rgba(34, 197, 94, 0.24), rgba(34, 197, 94, 0.06));
  border-color: rgba(74, 222, 128, 0.4);
  box-shadow: 0 6px 20px rgba(34, 197, 94, 0.16), inset 0 1px 0 rgba(255, 255, 255, 0.12);
}

.status-flow-banner-step.active {
  background: linear-gradient(160deg, rgba(251, 191, 36, 0.34), rgba(251, 191, 36, 0.08));
  border-color: rgba(251, 191, 36, 0.5);
  box-shadow: 0 8px 26px rgba(251, 191, 36, 0.3), inset 0 1px 0 rgba(255, 255, 255, 0.18);
}

.status-flow-banner-step.pending {
  background: rgba(255, 255, 255, 0.04);
  border-color: rgba(255, 255, 255, 0.1);
  opacity: 0.88;
}

.status-flow-banner-dot {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.85rem;
  flex-shrink: 0;
  transition: all 0.3s ease;
}

.status-flow-banner-step.completed .status-flow-banner-dot {
  background: linear-gradient(135deg, #22c55e, #16a34a);
  color: #ffffff;
  border: 1px solid rgba(255, 255, 255, 0.3);
  box-shadow: 0 3px 10px rgba(34, 197, 94, 0.35), inset 0 1px 1px rgba(255, 255, 255, 0.35);
}

.status-flow-banner-step.active .status-flow-banner-dot {
  background: linear-gradient(135deg, #fbbf24, #f59e0b);
  color: white;
  box-shadow: 0 0 15px rgba(251, 191, 36, 0.6), inset 0 1px 1px rgba(255, 255, 255, 0.4);
  animation: pulseBannerDot 1.5s ease-in-out infinite;
}

.status-flow-banner-step.pending .status-flow-banner-dot {
  background: rgba(255, 255, 255, 0.12);
  color: rgba(255, 255, 255, 0.55);
  border: 2px dashed rgba(255, 255, 255, 0.22);
}

.status-flow-banner-label {
  font-size: 0.6rem;
  font-weight: 600;
  color: rgba(255, 255, 255, 0.9);
  text-align: center;
  line-height: 1.2;
}

.status-flow-banner-time {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.05rem;
  font-size: 0.53rem;
  line-height: 1.15;
  text-align: center;
  color: rgba(255, 255, 255, 0.78);
  min-height: 2.55rem;
}

.status-flow-banner-time span {
  display: block;
}

.status-flow-banner-actor {
  max-width: 100%;
  font-size: 0.48rem;
  font-weight: 600;
  line-height: 1.2;
  white-space: normal;
  word-break: break-word;
}

.status-flow-banner-step.completed .status-flow-banner-label {
  color: #bbf7d0;
}

.status-flow-banner-step.active .status-flow-banner-label {
  color: #fef3c7;
  font-weight: 700;
  text-shadow: 0 0 8px rgba(251, 191, 36, 0.5);
}

.status-flow-banner-step.pending .status-flow-banner-label {
  color: rgba(255, 255, 255, 0.48);
}

.status-flow-banner-step.completed .status-flow-banner-time {
  color: rgba(220, 252, 231, 0.88);
}

.status-flow-banner-step.active .status-flow-banner-time {
  color: rgba(254, 243, 199, 0.95);
}

.status-flow-banner-time.pending {
  color: rgba(255, 255, 255, 0.42);
}

.status-flow-note {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.6rem 0.9rem;
  border-radius: 12px;
  background: rgba(64, 81, 137, 0.06);
  border: 1px solid rgba(64, 81, 137, 0.14);
  color: #405189;
  font-size: 0.82rem;
}

.status-flow-note i {
  font-size: 1rem;
  flex-shrink: 0;
}

@keyframes linePulse {
  0%,
  100% {
    opacity: 1;
    transform: scale(1);
  }
  50% {
    opacity: 0.7;
    transform: scale(1.1);
  }
}

@keyframes pulseBannerDot {
  0%,
  100% {
    transform: scale(1);
    box-shadow: 0 0 10px rgba(251, 191, 36, 0.5);
  }
  50% {
    transform: scale(1.12);
    box-shadow: 0 0 20px rgba(251, 191, 36, 0.8);
  }
}

@media (max-width: 768px) {
  .status-flow-panel {
    padding: 0.9rem 0.95rem 1rem;
  }

  .status-flow-banner-step {
    min-width: 84px;
    padding: 0.4rem 0.3rem;
  }

  .status-flow-banner-dot {
    width: 26px;
    height: 26px;
    font-size: 0.7rem;
  }

  .status-flow-banner-label {
    font-size: 0.55rem;
  }

  .status-flow-banner-time {
    font-size: 0.48rem;
  }

  .status-flow-banner-actor {
    font-size: 0.44rem;
  }
}
</style>
