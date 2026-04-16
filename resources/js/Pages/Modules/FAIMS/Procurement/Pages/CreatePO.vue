<template>
  <!-- Page Header -->
  <PageHeader title="Purchase Order" class="m-3 mt-4" />

  <!-- Enhanced Action Buttons -->
  <div class="d-flex justify-content-between align-items-center mb-4 mt-3">
    <div class="d-flex gap-2">
      <b-button
        type="button"
        variant="outline-primary"
        @click="goBackPage()"
        class="btn-modern shadow-sm"
        size="sm"
        v-b-tooltip.hover
        title="Back"
      >
        <i class="ri-arrow-left-line align-bottom me-1"></i>
        Back
      </b-button>

      <b-button
        @click="createPO()"
        v-if="!purchase_order"
        variant="success"
        class="btn-modern shadow-sm"
        size="sm"
        v-b-tooltip.hover
        title="Create Purchase Order"
      >
        <i class="ri-add-fill align-bottom me-1"></i>
        Create PO
      </b-button>

      <b-button
        v-if="purchase_order && canEditPO"
        variant="outline-success"
        class="btn-modern shadow-sm"
        size="sm"
        v-b-tooltip.hover
        title="Edit Purchase Order"
        @click="editPO()"
      >
        <i class="ri-pencil-fill align-bottom me-1"></i>
        Edit PO
      </b-button>

      <b-button
        v-if="canUpdatePOStatus"
        variant="outline-info"
        class="btn-modern shadow-sm"
        size="sm"
        v-b-tooltip.hover
        title="Update Status"
        @click="updateStatus(purchase_order)"
      >
        <i class="ri-edit-fill align-bottom me-1"></i>
        Update Status
      </b-button>

      <b-button
        variant="outline-warning"
        class="btn-modern shadow-sm"
        size="sm"
        v-if="purchase_order && canRevertPOStatus"
        @click="revertStatus(purchase_order)"
        v-b-tooltip.hover
        title="Revert Status"
      >
        <i class="ri-arrow-go-back-line align-bottom me-1"></i>
        Revert
      </b-button>

      <b-button
        variant="outline-danger"
        class="btn-modern shadow-sm"
        size="sm"
        v-if="purchase_order && purchase_order.status.name == 'Issued'"
        @click="notConformed(purchase_order)"
        v-b-tooltip.hover
        title="Not Conformed"
      >
        <i class="ri-close-circle-fill align-bottom me-1"></i>
        Not Conformed
      </b-button>

      <b-button
        variant="outline-primary"
        class="btn-modern shadow-sm"
        size="sm"
        v-if="canEditNTP"
        @click="editNTP()"
        v-b-tooltip.hover
        title="Edit Notice to Proceed"
      >
        <i class="ri-file-edit-line align-bottom me-1"></i>
        Edit NTP
      </b-button>

      <b-button
        variant="outline-success"
        class="btn-modern shadow-sm"
        size="sm"
        v-if="purchase_order && canPrintNTP"
        @click="printNTP(purchase_order)"
        v-b-tooltip.hover
        title="Notice to Proceed"
      >
        <i class="ri-file-fill align-bottom me-1"></i>
        Notice to Proceed
      </b-button>

      <b-button
        variant="outline-dark"
        class="btn-modern shadow-sm"
        v-if="purchase_order"
        @click="printPO(purchase_order)"
        size="sm"
        v-b-tooltip.hover
        title="Print Purchase Order"
      >
        <i class="ri-printer-fill align-bottom me-1"></i>
        Print PO
      </b-button>
    </div>
  </div>

  <!-- Enhanced Main Content -->
  <div class="main-content-wrapper">
    <div>
      <!-- Purchase Order Content -->
      <div
        v-if="purchase_order"
        class="po-content po-content-scroll p-4"
        ref="box"
      >
        <b-tabs v-model="activePOTab" class="po-view-tabs">
          <b-tab>
            <template #title>
              <i class="ri-file-list-3-line me-1 align-bottom"></i>
              Details
            </template>

            <PODetailsTab
              class="pt-3"
              :purchase-order="purchase_order"
              :noa="noa"
              :procurement="procurement"
            />
          </b-tab>

          <b-tab v-if="canAccessProgressTabs">
            <template #title>
              <i class="ri-truck-line me-1 align-bottom"></i>
              Delivery
              <b-badge v-if="deliveryTabCount > 0" variant="danger" class="ms-1 po-tab-badge">
                {{ deliveryTabCount }}
              </b-badge>
            </template>

            <PODeliveryTab
              class="pt-3"
              :delivery-summary="deliverySummary"
              :delivery-monitoring-items="deliveryMonitoringItems"
              :can-update-delivered-items="canUpdateDeliveredItems"
              @update-delivered-items="openDeliveredItemsEditor"
            />
          </b-tab>

          <b-tab v-if="canAccessInspectionTab">
            <template #title>
              <i class="ri-file-search-line me-1 align-bottom"></i>
              Inspection & Acceptance
              <b-badge v-if="inspectionAlertCount > 0" variant="danger" class="ms-1 po-tab-badge">
                {{ inspectionAlertCount }}
              </b-badge>
            </template>

            <POInspectionTab
              class="pt-3"
              :purchase-order="purchase_order"
              :delivered-monitoring-items="deliveredMonitoringItems"
              :delivery-summary="deliverySummary"
              :can-generate-iar-report="canGenerateIARReport"
              :can-print-iar-report="canPrintIARReport"
              :processing-iar-id="updatingIarId"
              @open-iar="openIARSelection"
              @edit-iar="editIAR"
              @print-iar="printIAR"
              @inspect-iar="inspectIAR"
              @revert-iar="revertIAR"
            />
          </b-tab>
        </b-tabs>
      </div>

      <!-- Enhanced Empty State -->
      <div
        v-else
        class="empty-state-container d-flex justify-content-center align-items-center"
        style="height: calc(90vh - 180px)"
      >
        <div class="text-center empty-state-content">
          <div class="empty-state-icon mb-4">
            <i class="ri-shopping-cart-line display-1 text-muted"></i>
          </div>
          <h4 class="text-muted mb-3 fw-bold">No Purchase Order Found</h4>
          <p class="text-muted mb-4 fs-6">
            You haven't created a Purchase Order yet. Click the button below to get started.
          </p>

        </div>
      </div>
    </div>
  </div>

  <!-- Update Status Modal -->
  <PurchaseOrder
    :procurement="procurement"
    :noa="noa"
    :dropdowns="dropdowns"
    @add="fetch()"
    ref="create"
  />
  <NTP
    :procurement="procurement"
    :noa="noa"
    @update="fetch()"
    ref="ntp"
  />
  <UpdateStatus :procurement="procurement" @add="fetch()" ref="updateStatus" />
  <IARItemSelection @updated="fetch()" ref="iarSelection" />
  <RevertResultModal
    v-model="showRevertResultModal"
    :title="revertResultMessage"
    :info="revertResultInfo"
    :variant="revertResultVariant"
  />
  <b-modal
    v-model="showInspectIarModal"
    style="--vz-modal-width: 500px"
    header-class="p-3 bg-light"
    :title="pendingInspectIarAction === 'revert' ? 'Revert IAR Status' : 'Update IAR Status'"
    class="v-modal-custom"
    modal-class="zoomIn"
    centered
    no-close-on-backdrop
    :hide-header-close="updatingIarId === pendingInspectIar?.id"
  >
    <div class="inspect-iar-modal-content text-center">
      <p class="inspect-iar-modal-message mb-0">
        {{ pendingInspectIarAction === "revert" ? "Revert" : "Mark" }}
        <strong>IAR {{ pendingInspectIar?.code || pendingInspectIar?.id || "" }}</strong>
        {{ pendingInspectIarAction === "revert" ? "to" : "as" }}
        <strong>{{ pendingInspectIarAction === "revert" ? "Generated" : "Inspected/Completed" }}</strong>?
      </p>
    </div>
    <template #footer>
      <b-button
        type="button"
        variant="light"
        :disabled="updatingIarId === pendingInspectIar?.id"
        @click="hideInspectIarModal()"
      >
        Cancel
      </b-button>
      <b-button
        type="button"
        variant="primary"
        :disabled="updatingIarId === pendingInspectIar?.id"
        @click="confirmIARStatusAction()"
      >
        <span v-if="updatingIarId === pendingInspectIar?.id">Updating...</span>
        <span v-else>{{ pendingInspectIarAction === "revert" ? "Revert" : "OK" }}</span>
      </b-button>
    </template>
  </b-modal>
</template>

<script>
import PageHeader from "@/Shared/Components/PageHeader.vue";
import UpdateStatus from "../Modals/UpdateStatus.vue";
import { router } from "@inertiajs/vue3";
import PurchaseOrder from "../Modals/PurchaseOrder.vue";
import NTP from "../Modals/NTP.vue";
import IARItemSelection from "../Modals/IARItemSelection.vue";
import RevertResultModal from "@/Shared/Components/RevertResultModal.vue";
import PODetailsTab from "./Components/PurchaseOrderTabs/PODetails.vue";
import PODeliveryTab from "./Components/PurchaseOrderTabs/PODelivery.vue";
import POInspectionTab from "./Components/PurchaseOrderTabs/POInspection.vue";

export default {
  props: ["noa", "procurement", "dropdowns"],
  components: {
    PageHeader,
    UpdateStatus,
    PurchaseOrder,
    NTP,
    IARItemSelection,
    RevertResultModal,
    PODetailsTab,
    PODeliveryTab,
    POInspectionTab,
  },
  data() {
    return {
      activePOTab: 0,
      purchase_order: null,
      updatingIarId: null,
      showInspectIarModal: false,
      pendingInspectIar: null,
      pendingInspectIarAction: "complete",
      showRevertResultModal: false,
      revertResultMessage: "",
      revertResultInfo: "",
      revertResultVariant: "success",
    };
  },
  created() {
    this.fetch();
  },

  computed: {
    canAccessProgressTabs() {
      return ["Conformed", "Delivered/For Inspection", "Completed"].includes(
        this.normalizedPurchaseOrderStatus
      );
    },
    canAccessInspectionTab() {
      return this.canAccessProgressTabs;
    },
    canEditNTP() {
      return this.isNormalizedPOStatus("Conformed");
    },
    canEditPO() {
      const status = this.normalizedPurchaseOrderStatus;

      return status && !["Not Conformed", "Completed", "Delivered/For Inspection"].includes(status);
    },
    canUpdatePOStatus() {
      if (!this.purchase_order) {
        return false;
      }

      if (["Created", "Issued"].includes(this.normalizedPurchaseOrderStatus)) {
        return true;
      }

      if (this.normalizedPurchaseOrderStatus === "Conformed") {
        return !this.hasRemainingDeliveries
          && this.iarReports.length > 0
          && this.pendingIarReportsCount === 0;
      }

      if (this.normalizedPurchaseOrderStatus === "Delivered/For Inspection") {
        return !this.hasRemainingDeliveries
          && this.iarReports.length > 0
          && this.pendingIarReportsCount === 0;
      }

      return false;
    },
    canRevertPOStatus() {
      return ["Issued", "Conformed", "Delivered/For Inspection"].includes(
        this.normalizedPurchaseOrderStatus
      );
    },
    canPrintNTP() {
      return ["Conformed", "Delivered/For Inspection", "Completed"].includes(
        this.normalizedPurchaseOrderStatus
      );
    },
    normalizedPurchaseOrderStatus() {
      return this.normalizePurchaseOrderStatus(this.purchase_order?.status?.name);
    },
    deliveryMonitoringItems() {
      return Array.isArray(this.purchase_order?.delivery_monitoring_items)
        ? this.purchase_order.delivery_monitoring_items
        : [];
    },
    deliverySummary() {
      return this.purchase_order?.delivery_monitoring_summary || {
        total_items: 0,
        delivered_items: 0,
        partial_items: 0,
        pending_items: 0,
        needs_delivery_items: 0,
        overdue_items: 0,
        late_items: 0,
        delivered_amount_total: 0,
        penalty_amount_total: 0,
        adjusted_amount_total: 0,
      };
    },
    deliveryTabCount() {
      return (this.deliverySummary.needs_delivery_items || 0)
        + (this.deliverySummary.late_items || 0)
        + (this.deliverySummary.overdue_items || 0);
    },
    deliveredMonitoringItems() {
      return this.deliveryMonitoringItems.filter((item) => Number(item.delivered_quantity || 0) > 0);
    },
    deliveredItemsCount() {
      return this.deliveredMonitoringItems.length;
    },
    hasRemainingDeliveries() {
      return (this.deliverySummary.needs_delivery_items || 0) > 0;
    },
    iarReports() {
      return Array.isArray(this.purchase_order?.iars)
        ? this.purchase_order.iars
        : (this.purchase_order?.iar ? [this.purchase_order.iar] : []);
    },
    pendingIarReportsCount() {
      return this.iarReports.filter((report) => report?.status?.name !== "Completed").length;
    },
    inspectionAlertCount() {
      if (this.normalizedPurchaseOrderStatus !== "Delivered/For Inspection") {
        return 0;
      }

      return this.pendingIarReportsCount;
    },
    canGenerateIARReport() {
      return (
        Boolean(this.purchase_order)
        && this.hasRemainingDeliveries
        && ["Conformed", "Delivered/For Inspection"].includes(this.normalizedPurchaseOrderStatus)
      );
    },
    canPrintIARReport() {
      const hasGeneratedIar = Boolean(this.purchase_order?.iar?.code)
        || (Array.isArray(this.purchase_order?.iars)
          && this.purchase_order.iars.some((report) => Boolean(report?.code)));

      return hasGeneratedIar;
    },
    canUpdateDeliveredItems() {
      return Boolean(this.purchase_order)
        && this.hasRemainingDeliveries
        && ["Conformed", "Delivered/For Inspection"].includes(
          this.normalizedPurchaseOrderStatus
        );
    },
  },

  methods: {
    fetch(page_url) {
      page_url = "/faims/purchase-orders";
      return axios
        .get(page_url, {
          params: {
            option: "purchase_order",
            noa_id: this.noa.id,
          },
        })
        .then((response) => {
          if (response) {
            this.purchase_order = response.data;
          }
        })
        .catch((err) => console.log(err));
    },

    normalizePurchaseOrderStatus(statusName) {
      const normalized = String(statusName || "").trim();

      if (!normalized) {
        return "";
      }

      if (
        ["PO Conformed", "Partially Conformed", "PO Partially Conformed"].includes(normalized)
      ) {
        return "Conformed";
      }

      if (
        [
          "Partially Delivered/For Inspection",
          "PO Delivered/For Inspection",
          "PO Partially Delivered/For Inspection",
        ].includes(normalized)
      ) {
        return "Delivered/For Inspection";
      }

      return normalized;
    },
    isNormalizedPOStatus(expectedStatus) {
      return this.normalizedPurchaseOrderStatus === expectedStatus;
    },

    createPO() {
      this.$refs.create.show();
    },

    editPO() {
      if (this.purchase_order) {
        this.$refs.create.show(this.purchase_order);
      }
    },
    editNTP() {
      if (this.canEditNTP) {
        this.$refs.ntp.edit(this.purchase_order);
      }
    },
    openDeliveredItemsEditor() {
      if (!this.purchase_order || !this.canUpdateDeliveredItems) {
        return;
      }

      this.$refs.iarSelection.show(this.purchase_order, {
        title: "Record Delivered Items",
        submitLabel: "Generate IAR",
        infoMessage:
          "Only items with remaining quantity are shown. Select the newly delivered items and enter the quantity delivered for each one. Saving will create a generated IAR report for this delivery batch.",
        printAfterSave: false,
        onSuccess: () => {
          this.focusInspectionTab();
        },
      });
    },
    openIARSelection() {
      if (!this.purchase_order || !this.canGenerateIARReport) {
        return;
      }

      this.$refs.iarSelection.show(this.purchase_order, {
        title: "Generate New IAR",
        submitLabel: "Generate IAR",
        infoMessage:
          "Only items with remaining quantity are shown. Select the delivered items and enter the actual delivered quantity for each one. Saving will generate a new IAR report and return you to the report list.",
        printAfterSave: false,
        onSuccess: () => {
          this.focusInspectionTab();
        },
      });
    },
    editIAR(report) {
      const statusName = String(report?.status?.name || "").trim();

      if (
        !this.purchase_order
        || !report?.id
        || !["Generated", "Pending"].includes(statusName)
      ) {
        return;
      }

      this.$refs.iarSelection.show(this.purchase_order, {
        title: "Edit Generated IAR",
        submitLabel: "Save IAR Changes",
        infoMessage:
          "Update the selected delivered items and quantities for this generated IAR report. You can still print it again after saving.",
        printAfterSave: false,
        iarId: report.id,
      });
    },
    printIAR(data) {
      const poId = this.purchase_order?.id || data?.id;
      const iarId = data?.code ? data.id : data?.iar_id;

      if (!poId) {
        return;
      }

      const params = new URLSearchParams({
        option: "print",
        type: "iar",
      });

      if (iarId) {
        params.set("iar_id", iarId);
      }

      window.open(`/faims/purchase-orders/${poId}?${params.toString()}`);
    },
    focusInspectionTab() {
      if (this.canAccessInspectionTab) {
        this.activePOTab = 2;
      }
    },
    inspectIAR(report) {
      if (!this.purchase_order?.id || !report?.id) {
        return;
      }

      if (report?.status?.name === "Completed") {
        window.alert("This IAR report is already Inspected/Completed.");
        return;
      }

      this.pendingInspectIarAction = "complete";
      this.pendingInspectIar = report;
      this.showInspectIarModal = true;
    },
    revertIAR(report) {
      if (!this.purchase_order?.id || !report?.id) {
        return;
      }

      if (report?.status?.name !== "Completed") {
        window.alert("Only Inspected/Completed IAR reports can be reverted.");
        return;
      }

      if (this.normalizedPurchaseOrderStatus !== "Conformed") {
        window.alert("IAR reports can only be reverted while the Purchase Order is Conformed.");
        return;
      }

      this.pendingInspectIarAction = "revert";
      this.pendingInspectIar = report;
      this.showInspectIarModal = true;
    },
    hideInspectIarModal(force = false) {
      if (!force && this.pendingInspectIar?.id && this.updatingIarId === this.pendingInspectIar.id) {
        return;
      }

      this.showInspectIarModal = false;
      this.pendingInspectIar = null;
      this.pendingInspectIarAction = "complete";
    },
    confirmIARStatusAction() {
      const report = this.pendingInspectIar;
      const isRevertAction = this.pendingInspectIarAction === "revert";
      const requestOption = isRevertAction ? "revert_iar_status" : "update_iar_status";
      const errorMessage = isRevertAction
        ? "Unable to revert the IAR report."
        : "Unable to update the IAR report.";

      if (!this.purchase_order?.id || !report?.id) {
        this.hideInspectIarModal();
        return;
      }

      this.updatingIarId = report.id;

      axios
        .put(
          `/faims/purchase-orders/${this.purchase_order.id}`,
          {
            iar_id: report.id,
            option: requestOption,
          },
          {
            headers: {
              Accept: "application/json",
              "X-Requested-With": "XMLHttpRequest",
            },
          }
        )
        .then((response) => {
          const status = response?.data?.status;

          if (status !== true && status !== "success") {
            window.alert(response?.data?.info || errorMessage);
            return;
          }

          this.hideInspectIarModal(true);
          this.fetch();
        })
        .catch((error) => {
          console.error(error);
          window.alert(errorMessage);
        })
        .finally(() => {
          this.updatingIarId = null;
        });
    },
    updateStatus(data) {
      this.$refs.updateStatus.show(data, "PO");
    },
    revertStatus(data) {
      this.$refs.updateStatus.show(data, "PO", "revert");
    },
    notConformed(data) {
      this.$refs.updateStatus.show(data, "PO Not Conformed");
    },
    printPO(data) {
      window.open(`/faims/purchase-orders/${data.id}?option=print&type=purchase_order`);
    },
    printNTP(data) {
      window.open(
        `/faims/purchase-orders/${data.id}?option=print&type=notice_to_proceed`
      );
    },
    goBackPage() {
      router.get(`/faims/procurements/${this.procurement.id}`, {
        option: "view",
        tab: 5,
      });
    },
  },
};
</script>

<style scoped>
/* Modern Button Styles */
.btn-modern {
  border-radius: 14px;
  font-weight: 700;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
  border: none;
}

.btn-modern:hover {
  transform: translateY(-1px);
  box-shadow: 0 14px 28px rgba(15, 23, 42, 0.12) !important;
}

/* Main Content Wrapper */
.main-content-wrapper {
  min-height: calc(100vh - 200px);
}

/* Modern Card */
.modern-card {
  overflow: hidden;
  border-radius: 28px;
  background:
    radial-gradient(circle at top left, rgba(14, 165, 233, 0.08), transparent 28%),
    radial-gradient(circle at top right, rgba(37, 99, 235, 0.08), transparent 22%),
    linear-gradient(135deg, #ffffff, #f8fbff 62%, #f5f9ff);
}

/* PO Content */
.po-content {
  background: transparent;
  font-size: 0.95rem;
}

.po-content-scroll {
  max-height: calc(90vh - 180px);
  overflow-y: auto;
  padding-bottom: 0.6rem !important;
}

:deep(.po-view-tabs .nav-tabs) {
  display: flex;
  flex-wrap: nowrap;
  overflow-x: auto;
  overflow-y: hidden;
  white-space: nowrap;
  gap: 0.75rem;
  border-bottom: 0;
  margin-bottom: 0;
  padding: 0 0 0.75rem;
}

:deep(.po-view-tabs .nav-link) {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.35rem;
  background: rgba(255, 255, 255, 0.85) !important;
  color: #475569 !important;
  border: 1px solid rgba(191, 219, 254, 0.9) !important;
  border-radius: 20px !important;
  padding: 0.8rem 1.05rem !important;
  font-size: 0.88rem;
  font-weight: 800;
  min-width: 190px;
  box-shadow: 0 14px 24px rgba(15, 23, 42, 0.06);
  backdrop-filter: blur(10px);
}

:deep(.po-view-tabs .nav-link.active) {
  color: #1d4ed8 !important;
  background: linear-gradient(135deg, rgba(219, 234, 254, 0.96), rgba(239, 246, 255, 0.98)) !important;
  border-color: rgba(96, 165, 250, 0.9) !important;
  box-shadow:
    inset 0 0 0 1px rgba(96, 165, 250, 0.42),
    0 18px 30px rgba(37, 99, 235, 0.14);
}

:deep(.po-view-tabs .tab-content) {
  padding-top: 1.15rem;
}

/* Empty State */
.empty-state-container {
  background:
    radial-gradient(circle at top left, rgba(191, 219, 254, 0.55), transparent 30%),
    linear-gradient(135deg, rgba(255, 255, 255, 0.96), rgba(248, 250, 252, 0.96));
  border-radius: 24px;
}

.empty-state-content {
  padding: 3rem 1rem;
}

.empty-state-icon {
  width: 88px;
  height: 88px;
  border-radius: 26px;
  background: linear-gradient(135deg, #eff6ff, #dbeafe);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2rem;
  color: #1d4ed8;
  margin: 0 auto 1.5rem;
}

.po-tab-badge {
  min-width: 1.35rem;
  border-radius: 999px;
  font-weight: 800;
}

.inspect-iar-modal-content {
  padding: 1rem 0.25rem 0.5rem;
}

.inspect-iar-modal-message {
  color: #334155;
  font-size: 1rem;
  line-height: 1.7;
}
</style>
