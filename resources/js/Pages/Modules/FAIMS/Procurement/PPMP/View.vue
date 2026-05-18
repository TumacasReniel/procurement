<template>
  <Head :title="`${planShortName} Details`" />
  <PageHeader :title="`${planShortName} Details`" pageTitle="Project Procurement Management Plan" />

  <BRow class="ppmp-view-page">
    <BCol lg="12">
      <div class="card ppmp-shell shadow-none border-0">
        <div class="card-header ppmp-header">
          <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div class="d-flex align-items-center gap-2">
              <span class="ppmp-header-icon">
                <i class="ri-file-list-3-line"></i>
              </span>
              <div>
                <div class="d-flex flex-wrap align-items-center gap-2">
                  <h5 class="mb-0 ppmp-title">{{ ppmp.ppmp_no }}</h5>
                  <b-badge :variant="ppmpStatusVariant">
                    {{ ppmp.ppmp_status || "Pending" }}
                  </b-badge>
                  <b-badge :variant="planBadgeVariant">
                    {{ planShortName }}
                  </b-badge>
                </div>
                <p v-if="normalizedPlanType === 'PPMP'" class="ppmp-subtitle mb-0">
                  {{ planDescription }} with {{ ppmp.items_count || 0 }}
                  item{{ (ppmp.items_count || 0) === 1 ? "" : "s" }}
                </p>
                <p v-else class="ppmp-subtitle mb-0">
                  {{ planDescription }}
                </p>
              </div>
            </div>
            <div class="d-flex gap-2">
              <b-button variant="light" @click="goBack">
                <i class="ri-arrow-left-line align-bottom me-1"></i>
                Back
              </b-button>
              <b-button variant="dark" @click="printPPMP">
                <i class="ri-printer-line align-bottom me-1"></i>
                Print
              </b-button>
          
              <b-button
                v-if="canShowAdvanceAction"
                variant="primary"
                :disabled="submitFinalForm.processing"
                @click="submitFinalModal.show = true"
              >
                <i class="ri-check-double-line align-bottom me-1"></i>
                {{ advanceActionLabel }}
              </b-button>
              <b-button
                v-if="ppmp.can_approve_to_app"
                variant="success"
                :disabled="approveAppForm.processing"
                @click="approveAppModal.show = true"
              >
                <i class="ri-checkbox-circle-line align-bottom me-1"></i>
                Consolidate
              </b-button>
            </div>
          </div>
        </div>

        <AppPlanView v-if="normalizedPlanType === 'APP'" :ppmp="ppmp" :dropdowns="dropdowns" />
        <SppPlanView v-else-if="normalizedPlanType === 'SPP'" :ppmp="ppmp" :dropdowns="dropdowns" />
        <PpmpPlanView
          v-else
          :ppmp="ppmp"
          :can-add-draft-item="canAddDraftItem"
          :can-edit-indicative-items="canEditIndicativeItems"
          @add-item="openAddItemModal"
          @edit-item="openEditItemModal"
          @delete-item="openDeleteItemModal"
          @advance-status="openSubmitFinalModal"
        />
      </div>
    </BCol>
  </BRow>

  <AddItemModal
    ref="addItemModal"
    :ppmp="ppmp"
    :dropdowns="dropdowns"
  />

  <DeleteItemModal ref="deleteItemModal" :ppmp="ppmp" />

  <SubmitForApprovalModal
    v-model:show="submitFinalModal.show"
    :ppmp="ppmp"
    :processing="submitFinalForm.processing"
    @cancel="closeSubmitFinalModal"
    @confirm="updateStatus"
  />

  <ApproveToAppModal
    v-model:show="approveAppModal.show"
    :ppmp="ppmp"
    :processing="approveAppForm.processing"
    @cancel="closeApproveAppModal"
    @confirm="approveToApp"
  />
</template>

<script>
import PageHeader from "@/Shared/Components/PageHeader.vue";
import { router, useForm } from "@inertiajs/vue3";
import AddItemModal from "./Modals/AddItem.vue";
import DeleteItemModal from "./Modals/DeleteItem.vue";
import ApproveToAppModal from "./Modals/ApproveToApp.vue";
import SubmitForApprovalModal from "./Modals/SubmitForApproval.vue";
import AppPlanView from "./Components/AppPlanView.vue";
import SppPlanView from "./Components/SppPlanView.vue";
import PpmpPlanView from "./Components/PpmpPlanView.vue";

export default {
  props: ["ppmp", "dropdowns"],
  components: {
    PageHeader,
    AddItemModal,
    DeleteItemModal,
    ApproveToAppModal,
    SubmitForApprovalModal,
    AppPlanView,
    SppPlanView,
    PpmpPlanView,
  },
  data() {
    return {
      submitFinalForm: useForm({
        option: "update_status",
        plan_type: this.ppmp?.plan_type || "PPMP",
      }),
      submitFinalModal: {
        show: false,
      },
      approveAppForm: useForm({
        option: "approve_to_app",
        plan_type: this.ppmp?.plan_type || "PPMP",
      }),
      approveAppModal: {
        show: false,
      },
    };
  },
  computed: {
    unitTypeOptions() {
      const options = this.dropdowns?.unit_types || [];

      return Array.isArray(options) ? options : Object.values(options);
    },
    canEditIndicativeItems() {
      return this.canAddDraftItem;
    },
    isPpmpCreator() {
      const currentUserId = this.$page?.props?.user?.data?.id;

      return Boolean(currentUserId && Number(currentUserId) === Number(this.ppmp?.created_by_id));
    },
    canAddDraftItem() {
      const ppmpStatus = String(this.ppmp.ppmp_status || "").trim().toLowerCase();
      const approvalStatus = String(this.ppmp.approval_status || "").trim().toLowerCase();
      const is_pending = approvalStatus === "pending" && ppmpStatus === "pending";

      return this.isPpmpCreator
        && this.normalizedPlanType === "PPMP"
        && (this.ppmp.can_add_items || is_pending);
    },
    normalizedPlanType() {
      switch (this.ppmp.plan_type) {
        case "APP":
        case "annual":
          return "APP";

        case "SPP":
        case "supplemental":
          return "SPP";

        case "PPMP":
        case "ppmp":
        default:
          return "PPMP";
      }
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
      return String(this.ppmp.ppmp_status || "").trim().toLowerCase() === "pending";
    },
    isReviewedForSubmission() {
      return String(this.ppmp.ppmp_status || "").trim().toLowerCase() === "reviewed/for submission";
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

    planShortName() {
      if (this.normalizedPlanType === "APP") {
        return "APP";
      }

      if (this.normalizedPlanType === "SPP") {
        return "SPP";
      }

      return "PPMP";
    },
    planLongName() {
      if (this.normalizedPlanType === "APP") {
        return "Annual Procurement Plan";
      }

      if (this.normalizedPlanType === "SPP") {
        return "Supplemental Procurement Plan";
      }

      return "Project Procurement Management Plan";
    },
    planScope() {
      if (this.normalizedPlanType === "SPP") {
        return "Agency update to approved APP";
      }

      if (this.normalizedPlanType === "APP") {
        return "Agency consolidated plan";
      }

      return "End-user unit plan";
    },
    planBadgeVariant() {
      if (this.normalizedPlanType === "APP") {
        return "primary";
      }

      if (this.normalizedPlanType === "SPP") {
        return "warning";
      }

      return "info";
    },
    ppmpStatusVariant() {
      const status = String(this.ppmp.ppmp_status || "").toLowerCase();

      if (status === "consolidated/added to app" || status === "consolidated/added to spp") {
        return "success";
      }

      if (status === "reviewed/for submission" || status === "submitted/for consolidation") {
        return "warning";
      }

      if (status === "consolidated/added to app" || status === "consolidated/added to spp") {
        return "success";
      }

      return "secondary";
    },
    planNumberLabel() {
      if (["APP", "SPP"].includes(this.normalizedPlanType)) {
        return "Included PR Nos.";
      }

      return "PR No.";
    },
    planDescription() {
      if (this.normalizedPlanType === "APP") {
        return "Agency-wide consolidated annual procurement plan";
      }

      if (this.normalizedPlanType === "SPP") {
        return "Agency-prepared update to the approved APP for new needs or budget changes";
      }

      return `Project procurement management plan for ${this.ppmp.unit?.name || "unit"}`;
    },
    advanceActionLabel() {
      if (this.isReviewedForSubmission) {
        return "Submit for Consolidation";
      }

      return "Review";
    },
  },
  methods: {
    goBack() {
      router.get("/faims/procurement-ppmp");
    },
    formatCurrency(value) {
      return new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
      }).format(Number(value || 0));
    },
    formatDate(date) {
      if (!date) {
        return "-";
      }

      return new Date(date).toLocaleDateString("en-US", {
        year: "numeric",
        month: "short",
        day: "numeric",
      });
    },
    formatQuantity(value) {
      const amount = Number(value || 0);
      return Number.isInteger(amount) ? amount.toString() : amount.toFixed(2);
    },
    printPPMP() {
      const params = new URLSearchParams({
        option: "print",
        type: "ppmp",
        plan_type: this.normalizedPlanType,
      });

      window.open(`/faims/procurement-ppmp/${this.ppmp.id}?${params.toString()}`, "_blank");
    },
    openSubmitFinalModal() {
      if (this.submitFinalForm.processing) {
        return;
      }

      this.submitFinalModal.show = true;
    },
    updateStatus() {
      this.submitFinalForm.option = "update_status";
      this.submitFinalForm.plan_type = this.normalizedPlanType;
      this.submitFinalForm.patch(`/faims/procurement-ppmp/${this.ppmp.id}`, {
        preserveScroll: true,
        onSuccess: () => {
          this.submitFinalModal.show = false;
        },
      });
    },
    closeSubmitFinalModal() {
      if (this.submitFinalForm.processing) {
        return;
      }

      this.submitFinalModal.show = false;
      this.submitFinalForm.clearErrors();
    },
    approveToApp() {
      this.approveAppForm.option = "approve_to_app";
      this.approveAppForm.plan_type = this.normalizedPlanType;
      this.approveAppForm.patch(`/faims/procurement-ppmp/${this.ppmp.id}`, {
        preserveScroll: true,
        onSuccess: () => {
          this.approveAppModal.show = false;
        },
      });
    },
    closeApproveAppModal() {
      if (this.approveAppForm.processing) {
        return;
      }

      this.approveAppModal.show = false;
      this.approveAppForm.clearErrors();
    },
    openAddItemModal() {
      this.$refs.addItemModal?.show();
    },
    openEditItemModal(item) {
      this.$refs.addItemModal?.show(item);
    },
    openDeleteItemModal(item) {
      this.$refs.deleteItemModal?.show(item);
    },
  },
};
</script>

<style>
.ppmp-view-page {
  --ppmp-surface: var(--vz-card-bg, var(--bs-card-bg, #ffffff));
  --ppmp-surface-soft: var(--vz-tertiary-bg, var(--bs-tertiary-bg, #f8fafc));
  --ppmp-surface-softer: var(--vz-secondary-bg, var(--bs-secondary-bg, #eef2ff));
  --ppmp-border: var(--vz-border-color, var(--bs-border-color, #e9ebec));
  --ppmp-border-soft: var(--vz-border-color-translucent, var(--bs-border-color-translucent, #f0f2f5));
  --ppmp-text: var(--vz-body-color, var(--bs-body-color, #212529));
  --ppmp-muted: var(--vz-secondary-color, var(--bs-secondary-color, #6c757d));
  --ppmp-soft: var(--ppmp-surface-soft);
  --ppmp-primary: var(--vz-primary, var(--bs-primary, #405189));
  background: var(--vz-body-bg, var(--bs-body-bg, transparent));
  color: var(--ppmp-text);
}

.ppmp-shell {
  border-radius: 10px;
  overflow: hidden;
  background: var(--ppmp-surface) !important;
  color: var(--ppmp-text);
}

.ppmp-view-body {
  background: var(--ppmp-surface) !important;
  color: var(--ppmp-text);
}

.ppmp-header {
  padding: 1rem 1.15rem;
  background: var(--ppmp-surface-soft);
  border-bottom: 1px solid var(--ppmp-border);
}

.ppmp-header-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 42px;
  height: 42px;
  color: var(--ppmp-primary);
  background: var(--ppmp-surface-softer);
  border: 1px solid var(--ppmp-border);
  border-radius: 8px;
  font-size: 23px;
}

.ppmp-title {
  color: var(--ppmp-text);
  font-size: 16px;
  font-weight: 800;
}

.ppmp-subtitle {
  margin-top: 3px;
  color: var(--ppmp-muted);
  font-size: 12px;
}

.overview-box {
  position: relative;
  display: flex;
  flex-direction: column;
  min-height: 76px;
  border: 1px solid var(--ppmp-border);
  border-radius: 8px;
  padding: 14px;
  background: var(--ppmp-surface);
  overflow: hidden;
}

.overview-icon {
  position: absolute;
  right: 14px;
  bottom: 10px;
  color: var(--ppmp-border);
  font-size: 34px;
}

.overview-label,
.info-row span,
.signatory-list span {
  color: var(--ppmp-muted);
  font-size: 12px;
  font-weight: 600;
}

.overview-value {
  margin-top: 7px;
  color: var(--ppmp-text);
  font-size: 16px;
  font-weight: 700;
}

.section-panel {
  border: 1px solid var(--ppmp-border);
  border-radius: 8px;
  padding: 16px;
  background: var(--ppmp-surface);
}

.section-heading {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 14px;
}

.section-heading.compact {
  margin-bottom: 12px;
  padding-bottom: 10px;
  border-bottom: 1px solid var(--ppmp-border-soft);
}

.info-row {
  display: flex;
  flex-direction: column;
  gap: 4px;
  min-height: 52px;
}

.info-row strong,
.signatory-list strong {
  color: var(--ppmp-text);
  font-size: 13px;
  font-weight: 700;
}

.pr-list {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}

.pr-list__item {
  display: inline-flex;
  align-items: center;
  min-height: 26px;
  padding: 4px 9px;
  border: 1px solid var(--ppmp-border);
  border-radius: 8px;
  background: var(--ppmp-surface-soft);
  color: var(--ppmp-text);
  font-size: 12px;
  font-weight: 700;
}

.signatory-list {
  display: grid;
  gap: 16px;
}

.signatory-list div {
  display: flex;
  flex-direction: column;
  gap: 5px;
}

.plan-detail-banner {
  display: flex;
  justify-content: space-between;
  align-items: stretch;
  gap: 18px;
  padding: 18px;
  border: 1px solid var(--ppmp-border);
  border-radius: 8px;
  background: var(--ppmp-surface-soft);
}

.plan-detail-banner--annual {
  background: var(--ppmp-surface-soft);
  border-color: var(--ppmp-border);
}

.plan-detail-banner--supplemental {
  background: var(--ppmp-surface-soft);
  border-color: var(--ppmp-border);
}

.plan-detail-banner__eyebrow {
  color: var(--ppmp-muted);
  font-size: 11px;
  font-weight: 800;
  text-transform: uppercase;
}

.plan-detail-banner__title {
  margin-top: 2px;
  color: var(--ppmp-text);
  font-size: 16px;
  font-weight: 800;
}

.plan-detail-banner__copy {
  margin-top: 4px;
  color: var(--ppmp-muted);
  font-size: 12px;
}

.plan-detail-banner__hint {
  margin-top: 8px;
  color: var(--ppmp-text);
  font-size: 12px;
  font-weight: 600;
}

.plan-detail-banner__meta {
  display: grid;
  grid-template-columns: repeat(3, minmax(92px, 1fr));
  gap: 8px;
  min-width: 350px;
}

.plan-detail-banner__meta > div {
  display: flex;
  flex-direction: column;
  justify-content: center;
  min-height: 58px;
  padding: 10px 12px;
  border: 1px solid var(--ppmp-border);
  border-radius: 8px;
  background: var(--ppmp-surface);
}

.plan-detail-banner__meta span {
  color: var(--ppmp-text);
  font-size: 14px;
  font-weight: 800;
}

.plan-detail-banner__meta small {
  color: var(--ppmp-muted);
  font-size: 11px;
  font-weight: 600;
}

.ppmp-table-wrap {
  border: 1px solid var(--ppmp-border);
  border-radius: 8px;
  max-height: 58vh;
}

.ppmp-table-wrap table {
  margin-bottom: 0;
}

.ppmp-table-wrap thead th {
  position: sticky;
  top: 0;
  z-index: 2;
  color: var(--ppmp-muted);
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0;
  border-bottom: 1px solid var(--ppmp-border);
  white-space: nowrap;
}

.ppmp-items-table tbody td {
  border-color: var(--ppmp-border);
}

.item-description {
  line-height: 1.45;
}

.ppmp-details-cell {
  min-width: 300px;
}

.detail-summary {
  display: grid;
  grid-template-columns: repeat(2, minmax(120px, 1fr));
  gap: 8px 12px;
}

.detail-summary__item {
  display: flex;
  flex-direction: column;
  gap: 2px;
  min-width: 0;
}

.detail-summary__item--wide {
  grid-column: 1 / -1;
}

.detail-summary__item span {
  color: var(--ppmp-muted);
  font-size: 10px;
  font-weight: 700;
  line-height: 1.2;
  text-transform: uppercase;
}

.detail-summary__item strong {
  color: var(--ppmp-text);
  font-size: 12px;
  font-weight: 600;
  line-height: 1.35;
  overflow-wrap: anywhere;
}

.detail-attachment {
  display: inline-flex;
  align-items: center;
  max-width: 100%;
  margin-top: 8px;
  color: #405189;
  font-size: 12px;
  font-weight: 600;
  overflow-wrap: anywhere;
}

.ppmp-confirm {
  display: grid;
  grid-template-columns: 48px 1fr;
  gap: 14px;
}

.ppmp-confirm__icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 48px;
  height: 48px;
  color: #0ab39c;
  background: rgba(10, 179, 156, .1);
  border-radius: 8px;
  font-size: 24px;
}

.ppmp-confirm__summary {
  grid-column: 1 / -1;
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 8px;
}

.ppmp-confirm__summary > div {
  padding: 10px;
  background: var(--ppmp-surface-soft);
  border: 1px solid var(--ppmp-border);
  border-radius: 8px;
}

.ppmp-confirm__summary span {
  display: block;
  color: var(--ppmp-muted);
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
}

.ppmp-confirm__summary strong {
  color: var(--ppmp-text);
  font-size: 13px;
  font-weight: 700;
}

@media (max-width: 992px) {
  .plan-detail-banner {
    flex-direction: column;
  }

  .plan-detail-banner__meta {
    min-width: 0;
    grid-template-columns: 1fr;
  }

  .detail-summary {
    grid-template-columns: 1fr;
  }

  .ppmp-confirm,
  .ppmp-confirm__summary {
    grid-template-columns: 1fr;
  }
}
</style>
