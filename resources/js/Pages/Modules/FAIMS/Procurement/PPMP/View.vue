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
                  <b-badge
                    v-if="ppmp.ppmp_type_label"
                    :variant="ppmp.ppmp_type === 'final' ? 'success' : 'info'"
                    style="font-size: 10px"
                  >
                    {{ ppmp.ppmp_type_label }}
                    V{{ normalizedPlanType === 'APP' ? (ppmp.version || 1) : (ppmp.ppmp_type_version || 1) }}
                  </b-badge>
                  <b-badge
                    v-if="ppmp.ppmp_type === 'final' && ppmp.is_current === false"
                    variant="secondary"
                    style="font-size: 10px"
                  >Archived</b-badge>
                  <span v-else-if="!ppmp.ppmp_type_label" class="version-badge">
                    Version {{ normalizedPlanType === 'APP' ? (ppmp.version || 1) : (ppmp.ppmp_type_version || 1) }}
                  </span>
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
              <b-button
                variant="light"
                v-b-tooltip="{ title: 'Procurement Process Guide (PPMP → APP → SPP → RFQ → PO)', variant: 'primary' }"
                @click="processInfoModal.show = true"
              >
                <i class="ri-question-line align-bottom text-primary"></i>
              </b-button>
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
                @click="openSubmitFinalModal"
              >
                <i class="ri-check-double-line align-bottom me-1"></i>
                {{ advanceActionLabel }}
              </b-button>
              <b-button
                v-if="canMarkAsFinal"
                variant="warning"
                :disabled="markFinalForm.processing"
                @click="openMarkAsFinalModal"
              >
                <i class="ri-flag-line align-bottom me-1"></i>
                Mark as Final
              </b-button>
              <b-button
                v-if="canCreateRevision"
                variant="warning"
                :disabled="createRevisionForm.processing"
                @click="openCreateRevisionModal"
              >
                <i class="ri-git-branch-line align-bottom me-1"></i>
                Create Revision
              </b-button>
              <b-button
                v-if="canFinalizeToFinalApp"
                variant="success"
                :disabled="finalizeAppForm.processing"
                v-b-tooltip.hover
                title="Mark this APP and its PPMPs as Final (post-GAA)"
                @click="openFinalizeAppModal"
              >
                <i class="ri-flag-2-fill align-bottom me-1"></i>
                Proceed to Final APP
              </b-button>
              <b-button
                v-if="canAddSppItem"
                variant="success"
                class="spp-add-item-button"
                @click="openAddItemModal"
              >
                <i class="ri-add-line align-bottom me-1"></i>
                Add Item
              </b-button>
              <b-button
                v-if="canShowConsolidateAction"
                variant="success"
                :disabled="approveAppForm.processing"
                @click="approveAppModal.show = true"
              >
                <i class="ri-checkbox-circle-line align-bottom me-1"></i>
                Consolidate
              </b-button>
              <b-button
                v-if="ppmp.can_revert_status"
                variant="warning"
                :disabled="revertStatusForm.processing"
                @click="revertStatusModal.show = true"
              >
                <i class="ri-arrow-go-back-line align-bottom me-1"></i>
                Revert Status
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
    :plan-type="normalizedPlanType"
    :processing="submitFinalForm.processing"
    :error="submitFinalModal.error"
    @cancel="closeSubmitFinalModal"
    @confirm="updateStatus"
  />

  <ApproveToAppModal
    v-model:show="approveAppModal.show"
    :ppmp="ppmp"
    :processing="approveAppForm.processing"
    :error="approveAppModal.error"
    @cancel="closeApproveAppModal"
    @confirm="approveToApp"
  />

  <RevertStatusModal
    v-model:show="revertStatusModal.show"
    :plan="ppmp"
    :plan-type="normalizedPlanType"
    :processing="revertStatusForm.processing"
    :error="revertStatusModal.error"
    @cancel="closeRevertStatusModal"
    @confirm="revertStatus"
  />

  <FloatingPlanChat ref="planChat" :plan="ppmp" :show-trigger="true" />

  <MarkAsFinalModal
    v-model:show="markFinalModal.show"
    :ppmp="ppmp"
    :processing="markFinalForm.processing"
    :error="markFinalModal.error"
    @cancel="closeMarkAsFinalModal"
    @confirm="markAsFinal"
  />

  <CreateRevisionModal
    v-model:show="createRevisionModal.show"
    :ppmp="ppmp"
    :processing="createRevisionForm.processing"
    :error="createRevisionModal.error"
    @cancel="closeCreateRevisionModal"
    @confirm="createRevision"
  />

  <ProcurementProcessInfoModal
    v-model:show="processInfoModal.show"
    @close="processInfoModal.show = false"
  />

  <FinalizeToFinalAppModal
    v-model:show="finalizeAppModal.show"
    :ppmp="ppmp"
    :processing="finalizeAppForm.processing"
    :error="finalizeAppModal.error"
    @cancel="closeFinalizeAppModal"
    @confirm="finalizeToFinalApp"
  />
</template>

<script>
import PageHeader from "@/Shared/Components/PageHeader.vue";
import { router, useForm } from "@inertiajs/vue3";
import AddItemModal from "./Modals/AddItem.vue";
import DeleteItemModal from "./Modals/DeleteItem.vue";
import ApproveToAppModal from "./Modals/ApproveToApp.vue";
import SubmitForApprovalModal from "./Modals/SubmitForApproval.vue";
import RevertStatusModal from "./Modals/RevertStatus.vue";
import MarkAsFinalModal from "./Modals/MarkAsFinal.vue";
import CreateRevisionModal from "./Modals/CreateRevision.vue";
import AppPlanView from "./Components/AppPlanView.vue";
import SppPlanView from "./Components/SppPlanView.vue";
import PpmpPlanView from "./Components/PpmpPlanView.vue";
import FloatingPlanChat from "./Components/FloatingPlanChat.vue";
import ProcurementProcessInfoModal from "./Modals/ProcurementProcessInfoModal.vue";
import FinalizeToFinalAppModal from "./Modals/FinalizeToFinalAppModal.vue";

export default {
  props: ["ppmp", "dropdowns", "versions"],
  components: {
    PageHeader,
    AddItemModal,
    DeleteItemModal,
    ApproveToAppModal,
    SubmitForApprovalModal,
    RevertStatusModal,
    MarkAsFinalModal,
    CreateRevisionModal,
    AppPlanView,
    SppPlanView,
    PpmpPlanView,
    FloatingPlanChat,
    ProcurementProcessInfoModal,
    FinalizeToFinalAppModal,
  },
  data() {
    return {
      submitFinalForm: useForm({
        option: "update_status",
        plan_type: this.ppmp?.plan_type || "PPMP",
      }),
      submitFinalModal: {
        show: false,
        error: "",
      },
      approveAppForm: useForm({
        option: "approve_to_app",
        plan_type: this.ppmp?.plan_type || "PPMP",
        consolidation_review_acknowledged: false,
        consolidation_pricing: [],
      }),
      approveAppModal: {
        show: false,
        error: "",
      },
      revertStatusForm: useForm({
        option: "revert_status",
        plan_type: this.ppmp?.plan_type || "PPMP",
        revert_reason: "",
      }),
      revertStatusModal: {
        show: false,
        error: "",
      },
      markFinalForm: useForm({
        option: "mark_as_final",
        plan_type: this.ppmp?.plan_type || "PPMP",
      }),
      markFinalModal: {
        show: false,
        error: "",
      },
      createRevisionForm: useForm({
        option: "create_revision",
        plan_type: this.ppmp?.plan_type || "PPMP",
      }),
      createRevisionModal: {
        show: false,
        error: "",
      },
      processInfoModal: { show: false },
      finalizeAppForm: useForm({
        option: "finalize_to_final_app",
        app_id: null,
      }),
      finalizeAppModal: {
        show: false,
        error: "",
      },
    };
  },
  mounted() {
    this.subscribeToPlanUpdates();
    this.openNotificationChatIfRequested();
  },
  beforeUnmount() {
    this.unsubscribeFromPlanUpdates();
  },
  computed: {
    unitTypeOptions() {
      const options = this.dropdowns?.unit_types || [];

      return Array.isArray(options) ? options : Object.values(options);
    },
    canEditIndicativeItems() {
      return this.canAddDraftItem;
    },
    canMarkAsFinal() {
      return this.normalizedPlanType === "PPMP" && Boolean(this.ppmp?.can_mark_as_final);
    },
    canCreateRevision() {
      return this.normalizedPlanType === "PPMP" && Boolean(this.ppmp?.can_create_revision);
    },
    canFinalizeToFinalApp() {
      return (
        this.normalizedPlanType === "APP" &&
        this.ppmp?.plan_phase === "indicative" &&
        (this.isProcurementOfficer || this.isAdministrator)
      );
    },
    isPpmpCreator() {
      const currentUserId = this.$page?.props?.user?.data?.id;

      return Boolean(currentUserId && Number(currentUserId) === Number(this.ppmp?.created_by_id));
    },
    isSameUserUnit() {
      const userUnitId = this.$page?.props?.user?.data?.organization?.unit_id;
      const planUnitId = this.ppmp?.unit_id || this.ppmp?.unit?.id || this.ppmp?.unit?.value;

      return Boolean(userUnitId && planUnitId && Number(userUnitId) === Number(planUnitId));
    },
    canAddDraftItem() {
      const ppmpStatus = String(this.ppmp.ppmp_status || "").trim().toLowerCase();
      const approvalStatus = String(this.ppmp.approval_status || "").trim().toLowerCase();
      const is_pending = approvalStatus === "pending" && ppmpStatus === "pending";

      return (this.isPpmpCreator || this.isSameUserUnit)
        && this.normalizedPlanType === "PPMP"
        && (this.ppmp.can_add_items || is_pending);
    },
    canAddSppItem() {
      const planStatus = String(this.ppmp.ppmp_status || this.ppmp.approval_status || "Pending").trim().toLowerCase();

      return this.normalizedPlanType === "SPP"
        && planStatus === "pending"
        && (this.isPpmpCreator || this.isSameUserUnit);
    },
    normalizedPlanType() {
      switch (this.ppmp.plan_type || this.ppmp.plan_name || this.ppmp.title) {
        case "APP":
        case "annual":
        case "Annual Procurement Plan":
          return "APP";

        case "SPP":
        case "supplemental":
        case "Supplemental Procurement Plan":
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
    currentRoleNames() {
      return this.currentRoles
        .map((role) => typeof role === "string" ? role : role?.name)
        .filter(Boolean);
    },
    isBudgetOfficer() {
      return this.hasRole("Budget Officer");
    },
    isAdministrator() {
      return this.hasRole("Administrator");
    },
    isProcurementOfficer() {
      return this.hasRole("Procurement Officer");
    },
    isProcurementUser() {
      return this.hasRole("Procurement Officer") || this.hasRole("Procurement Staff");
    },
    isPendingPpmp() {
      return String(this.ppmp.ppmp_status || "").trim().toLowerCase() === "pending";
    },
    isReviewedForSubmission() {
      return String(this.ppmp.ppmp_status || "").trim().toLowerCase() === "reviewed/for submission";
    },
    isForReview() {
      return String(this.ppmp.ppmp_status || "").trim().toLowerCase() === "for review";
    },
    isConsolidatedToApp() {
      return String(this.ppmp.ppmp_status || "").trim().toLowerCase() === "consolidated/added to app";
    },
    canConsolidatePpmp() {
      const bacDesignations = ["BAC Chairperson", "BAC Vice Chairperson", "BAC Member"];
      const designation = this.$page.props.user?.data?.designation;

      return this.hasRole("BAC User")
        || this.hasRole("BAC Chairperson")
        || this.hasRole("BAC Vice Chairperson")
        || this.hasRole("BAC Member")
        || bacDesignations.includes(designation);
    },
    canShowConsolidateAction() {
      const status = String(this.ppmp.ppmp_status || "").trim().toLowerCase();

      return this.canConsolidatePpmp
        && (Boolean(this.ppmp.can_approve_to_app) || status === "submitted/for consolidation")
        && !this.isConsolidatedToApp;
    },
    canShowAdvanceAction() {
      if (this.normalizedPlanType === "APP") {
        if (this.isReviewedForSubmission && this.isProcurementOfficer) {
          return true;
        }

        if (this.isPendingPpmp) {
          return this.canSubmitPendingPpmp;
        }

        return this.isForReview && this.isBudgetOfficer;
      }

      if (this.normalizedPlanType === "SPP") {
        if (this.isForReview) {
          return this.isBudgetOfficer;
        }

        if (this.isReviewedForSubmission) {
          return this.isProcurementOfficer;
        }

        if (this.isPendingPpmp) {
          return this.canSubmitPendingPpmp;
        }
      }

      if (this.isReviewedForSubmission && this.isProcurementOfficer) {
        return true;
      }

      if (this.isPendingPpmp && this.canSubmitPendingPpmp) {
        return true;
      }

      if (this.isForReview && this.isBudgetOfficer) {
        return true;
      }

      if (this.isReviewedForSubmission) {
        return this.isProcurementOfficer;
      }

      if (this.isPendingPpmp) {
        return this.canSubmitPendingPpmp;
      }

      return false;
    },
    canSubmitPendingPpmp() {
      return this.isPpmpCreator || this.isSameUserUnit || this.isProcurementUser || this.isAdministrator;
    },
    hasPpmpItems() {
      return Number(this.ppmp?.items_count || 0) > 0
        || (this.ppmp?.project_rows?.length || 0) > 0;
    },
    requiresItemsBeforeAdvance() {
      return ["PPMP", "SPP"].includes(this.normalizedPlanType);
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
        return "Unit supplemental plan";
      }

      if (this.normalizedPlanType === "APP") {
        return "Agency consolidated plan";
      }

      return "End-user unit plan";
    },
    planBadgeVariant() {
      if (this.normalizedPlanType === "SPP") {
        return "warning";
      }

      return "primary";
    },
    ppmpStatusVariant() {
      const status = String(this.ppmp.ppmp_status || "").toLowerCase();

      if (status === "consolidated/added to app" || status === "consolidated/added to spp") {
        return "success";
      }

      if (status === "for review" || status === "reviewed/for submission" || status === "submitted/for consolidation" || status === "submitted/for implementation") {
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
        return `Supplemental procurement plan for ${this.ppmp.unit?.name || "unit"}`;
      }

      return `Project procurement management plan for ${this.ppmp.unit?.name || "unit"}`;
    },
    advanceActionLabel() {
      if (this.normalizedPlanType === "APP") {
        if (this.isReviewedForSubmission) {
          return "Submit for Implementation";
        }

        return this.isForReview ? "Mark Reviewed/For Submission" : "Move to For Review";
      }

      if (this.isForReview) {
        return "Mark Reviewed";
      }

      if (this.isReviewedForSubmission) {
        return "Submit for Consolidation";
      }

      if (this.isPendingPpmp) {
        return "Submit for Review";
      }

      return "Submit for Review";
    },
  },
  methods: {
    hasRole(roleName) {
      return Array.isArray(this.$page?.props?.roles)
        && this.$page.props.roles.includes(roleName);
    },
    subscribeToPlanUpdates() {
      if (!window.Echo) {
        return;
      }

      window.Echo.channel("procurement-plans")
        .listen(".procurement-plan.status-updated", (event) => {
          const updatedPlanId = Number(event?.plan?.id || 0);

          if (!updatedPlanId || updatedPlanId !== Number(this.ppmp?.id || 0)) {
            return;
          }

          router.reload({
            only: ["ppmp"],
            preserveScroll: true,
          });
        });
    },
    unsubscribeFromPlanUpdates() {
      if (!window.Echo) {
        return;
      }

      window.Echo.leave("procurement-plans");
    },
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

      this.submitFinalModal.error = "";
      this.submitFinalModal.show = true;
    },
    updateStatus() {
      if (this.requiresItemsBeforeAdvance && !this.hasPpmpItems) {
        this.submitFinalModal.error = this.emptyPpmpItemsMessage();
        return;
      }

      this.submitFinalModal.error = "";
      this.submitFinalForm.option = "update_status";
      this.submitFinalForm.plan_type = this.normalizedPlanType;
      this.submitFinalForm.patch(`/faims/procurement-ppmp/${this.ppmp.id}`, {
        preserveScroll: true,
        onSuccess: () => {
          this.submitFinalModal.show = false;
          this.submitFinalModal.error = "";
        },
        onError: (errors) => {
          this.submitFinalModal.error = this.firstFormError(errors);
        },
      });
    },
    closeSubmitFinalModal() {
      if (this.submitFinalForm.processing) {
        return;
      }

      this.submitFinalModal.show = false;
      this.submitFinalModal.error = "";
      this.submitFinalForm.clearErrors();
    },
    approveToApp(review = {}) {
      this.approveAppForm.option = "approve_to_app";
      this.approveAppForm.plan_type = this.normalizedPlanType;
      this.approveAppForm.consolidation_review_acknowledged = Boolean(review.consolidation_review_acknowledged);
      this.approveAppForm.consolidation_pricing = review.consolidation_pricing || [];
      this.approveAppModal.error = "";
      this.approveAppForm.patch(`/faims/procurement-ppmp/${this.ppmp.id}`, {
        preserveScroll: true,
        onSuccess: () => {
          this.approveAppModal.show = false;
          this.approveAppModal.error = "";
        },
        onError: (errors) => {
          const first = Object.values(errors || {}).flat().find(Boolean);
          this.approveAppModal.error = first || "Consolidation failed. Please try again.";
        },
      });
    },
    revertStatus(reason) {
      this.revertStatusForm.option = "revert_status";
      this.revertStatusForm.plan_type = this.normalizedPlanType;
      this.revertStatusForm.revert_reason = reason;
      this.revertStatusForm.patch(`/faims/procurement-ppmp/${this.ppmp.id}`, {
        preserveScroll: true,
        onSuccess: () => {
          this.revertStatusModal.show = false;
          this.revertStatusModal.error = "";
        },
        onError: (errors) => {
          this.revertStatusModal.error = Object.values(errors || {})[0] || "Unable to revert status.";
        },
      });
    },
    closeRevertStatusModal() {
      if (this.revertStatusForm.processing) {
        return;
      }
      this.revertStatusModal.show = false;
      this.revertStatusModal.error = "";
      this.revertStatusForm.clearErrors();
    },
    closeApproveAppModal() {
      if (this.approveAppForm.processing) {
        return;
      }

      this.approveAppModal.show = false;
      this.approveAppModal.error = "";
      this.approveAppForm.clearErrors();
    },
    openMarkAsFinalModal() {
      if (this.markFinalForm.processing) {
        return;
      }
      this.markFinalModal.error = "";
      this.markFinalModal.show = true;
    },
    closeMarkAsFinalModal() {
      if (this.markFinalForm.processing) return;
      this.markFinalModal.show = false;
      this.markFinalModal.error = "";
      this.markFinalForm.clearErrors();
    },
    markAsFinal() {
      this.markFinalModal.error = "";
      this.markFinalForm.option = "mark_as_final";
      this.markFinalForm.plan_type = this.normalizedPlanType;
      this.markFinalForm.patch(`/faims/procurement-ppmp/${this.ppmp.id}`, {
        onSuccess: () => {
          this.markFinalModal.show = false;
        },
        onError: (errors) => {
          this.markFinalModal.error = this.firstFormError(errors);
        },
      });
    },
    openCreateRevisionModal() {
      if (this.createRevisionForm.processing) return;
      this.createRevisionModal.error = "";
      this.createRevisionModal.show = true;
    },
    closeCreateRevisionModal() {
      if (this.createRevisionForm.processing) return;
      this.createRevisionModal.show = false;
      this.createRevisionModal.error = "";
      this.createRevisionForm.clearErrors();
    },
    createRevision() {
      this.createRevisionModal.error = "";
      this.createRevisionForm.option = "create_revision";
      this.createRevisionForm.plan_type = this.normalizedPlanType;
      this.createRevisionForm.patch(`/faims/procurement-ppmp/${this.ppmp.id}`, {
        onSuccess: () => {
          this.createRevisionModal.show = false;
        },
        onError: (errors) => {
          this.createRevisionModal.error = this.firstFormError(errors);
        },
      });
    },
    openFinalizeAppModal() {
      if (this.finalizeAppForm.processing) return;
      this.finalizeAppModal.error = "";
      this.finalizeAppModal.show = true;
    },
    closeFinalizeAppModal() {
      if (this.finalizeAppForm.processing) return;
      this.finalizeAppModal.show = false;
      this.finalizeAppModal.error = "";
      this.finalizeAppForm.clearErrors();
    },
    finalizeToFinalApp() {
      this.finalizeAppModal.error = "";
      this.finalizeAppForm.option = "finalize_to_final_app";
      this.finalizeAppForm.app_id = this.ppmp.id;
      this.finalizeAppForm.post("/faims/procurement-ppmp", {
        preserveScroll: true,
        onSuccess: () => {
          this.finalizeAppModal.show = false;
          this.finalizeAppModal.error = "";
          router.get("/faims/procurement-ppmp", { plan_type: "APP" });
        },
        onError: (errors) => {
          this.finalizeAppModal.error = this.firstFormError(errors);
        },
      });
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
    openNotificationChatIfRequested() {
      const url = new URL(
        this.$page?.url || window.location.href,
        window.location.origin
      );
      const params = url.searchParams;

      if (params.get("open_chat") !== "1" && !params.get("comment_id")) {
        return;
      }

      this.$nextTick(() => {
        this.$refs.planChat?.openPlan({
          ...this.ppmp,
          plan_type: this.normalizedPlanType,
        });
        this.clearNotificationChatParams();
      });
    },
    clearNotificationChatParams() {
      const url = new URL(window.location.href);

      url.searchParams.delete("open_chat");
      url.searchParams.delete("comment_id");
      window.history.replaceState({}, "", url.toString());
    },
    emptyPpmpItemsMessage() {
      return "Please add at least one procurement project before updating or submitting this PPMP for review.";
    },
    firstFormError(errors) {
      const firstError = Object.values(errors || {}).flat().find(Boolean);

      return firstError || "Unable to update this plan status.";
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

.spp-add-item-button {
  min-height: 34px;
  padding: 6px 14px;
  border-color: #0ab39c !important;
  background-color: #0ab39c !important;
  color: #ffffff !important;
  font-weight: 500;
  border-radius: 4px;
  box-shadow: none;
}

.spp-add-item-button:hover,
.spp-add-item-button:focus {
  border-color: #099885 !important;
  background-color: #099885 !important;
  color: #ffffff !important;
}

.spp-add-item-button i {
  color: inherit !important;
  font-size: 13px;
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

[data-bs-theme="dark"] .ppmp-view-page {
  --ppmp-surface: #151e33;
  --ppmp-surface-soft: #10192c;
  --ppmp-surface-softer: #1d2942;
  --ppmp-border: rgba(170, 184, 220, .18);
  --ppmp-border-soft: rgba(170, 184, 220, .12);
  --ppmp-text: #f4f7ff;
  --ppmp-muted: #c5cde0;
  --ppmp-primary: #9bb2ff;
  background: #0b1220;
  color: var(--ppmp-text);
}

[data-bs-theme="dark"] .ppmp-view-page .text-muted {
  color: var(--ppmp-muted) !important;
}

[data-bs-theme="dark"] .ppmp-view-page .text-body,
[data-bs-theme="dark"] .ppmp-view-page .text-primary,
[data-bs-theme="dark"] .ppmp-view-page h1,
[data-bs-theme="dark"] .ppmp-view-page h2,
[data-bs-theme="dark"] .ppmp-view-page h3,
[data-bs-theme="dark"] .ppmp-view-page h4,
[data-bs-theme="dark"] .ppmp-view-page h5,
[data-bs-theme="dark"] .ppmp-view-page h6,
[data-bs-theme="dark"] .ppmp-view-page strong {
  color: var(--ppmp-text) !important;
}

[data-bs-theme="dark"] .ppmp-view-page .table:not(.ppmp-document-items-table),
[data-bs-theme="dark"] .ppmp-view-page .table:not(.ppmp-document-items-table) > :not(caption) > * > * {
  --bs-table-bg: transparent;
  --bs-table-color: var(--ppmp-text);
  --bs-table-hover-bg: rgba(142, 164, 255, .1);
  --bs-table-hover-color: var(--ppmp-text);
  color: var(--ppmp-text);
  border-color: var(--ppmp-border);
}

[data-bs-theme="dark"] .ppmp-view-page .table-light {
  --bs-table-bg: var(--ppmp-surface-soft);
  --bs-table-color: var(--ppmp-text);
  background-color: var(--ppmp-surface-soft) !important;
  color: var(--ppmp-text) !important;
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
