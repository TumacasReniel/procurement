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
                  <b-badge :variant="ppmp.is_final ? 'success' : 'secondary'">
                    {{ ppmp.ppmp_status || "Indicative" }}
                  </b-badge>
                  <b-badge :variant="planBadgeVariant">
                    {{ planShortName }}
                  </b-badge>
                </div>
                <p v-if="ppmp.plan_type === 'ppmp'" class="ppmp-subtitle mb-0">
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
                Print PDF
              </b-button>
          
              <b-button
                v-if="ppmp.can_submit_final"
                variant="primary"
                :disabled="submitFinalForm.processing"
                @click="submitFinal"
              >
                <i class="ri-check-double-line align-bottom me-1"></i>
                Mark as Final PPMP
              </b-button>
            </div>
          </div>
        </div>

        <div class="card-body bg-white">
          <div class="plan-detail-banner mb-3" :class="`plan-detail-banner--${ppmp.plan_type || 'ppmp'}`">
            <div>
              <div class="plan-detail-banner__eyebrow">{{ planScope }}</div>
              <div class="plan-detail-banner__title">{{ planLongName }}</div>
              <div class="plan-detail-banner__copy">{{ planDescription }}</div>
              <div v-if="ppmp.can_submit_final" class="plan-detail-banner__hint">
                Review this indicative PPMP, then mark it as final when the unit plan is ready.
              </div>
            </div>
            <div class="plan-detail-banner__meta">
              <div>
                <span>{{ formatCurrency(ppmp.estimated_budget) }}</span>
                <small>Total ABC</small>
              </div>
              <div>
                <span>{{ ppmp.items_count || (ppmp.source_ppmps || []).length || 0 }}</span>
                <small>{{ ppmp.plan_type !== "ppmp" ? "Source PPMPs" : "Items" }}</small>
              </div>
              <div>
                <span>{{ ppmp.source_of_funds || ppmp.fund_cluster?.name || "-" }}</span>
                <small>Fund Source</small>
              </div>
            </div>
          </div>

          <div class="row g-3 mb-3">
            <div v-if="ppmp.plan_type === 'ppmp'" class="col-xl-4 col-md-6">
              <div class="overview-box">
                <i class="ri-file-text-line overview-icon"></i>
                <span class="overview-label">{{ planNumberLabel }}</span>
                <span class="overview-value">{{ ppmp.pr_no || ppmp.code || "-" }}</span>
              </div>
            </div>
            <div v-if="ppmp.plan_type === 'ppmp'" class="col-xl-4 col-md-6">
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
                      <span>{{ ppmp.plan_type === "ppmp" ? "Unit" : "Coverage" }}</span>
                      <strong>{{ ppmp.unit?.name || "-" }}</strong>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div v-if="ppmp.plan_type === 'ppmp'" class="info-row">
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
                  <div>
                    <span>{{ ppmp.plan_type === "annual" ? "Prepared / Consolidated By" : "Reviewed By" }}</span>
                    <strong>{{ ppmp.reviewed_by || "-" }}</strong>
                    <small v-if="ppmp.plan_type === 'supplemental'" class="text-muted">Agency</small>
                  </div>
                  <div>
                    <span>{{ ppmp.plan_type === "annual" ? "Submitted / Certified By" : "Approved By" }}</span>
                    <strong>{{ ppmp.approved_by || "-" }}</strong>
                  </div>
                </div>
              
              </div>
            </div>
          </div>

          <div class="section-panel mt-3">
            <div class="section-heading">
              <h6 class="mb-0 fs-14">{{ ppmp.plan_type !== "ppmp" ? "Approved Final PPMPs" : `${planShortName} Items` }}</h6>
              <div class="d-flex flex-wrap align-items-center justify-content-end gap-2">
                <span class="text-muted fs-12">
                  <template v-if="ppmp.plan_type !== 'ppmp'">
                    {{ (ppmp.source_ppmps || []).length }} PPMP{{ (ppmp.source_ppmps || []).length === 1 ? "" : "s" }}
                  </template>
                  <template v-else>
                    {{ ppmp.items_count || 0 }} item{{ (ppmp.items_count || 0) === 1 ? "" : "s" }}
                  </template>
                </span>
                <b-button
                  v-if="canAddDraftItem"
                  variant="success"
                  size="sm"
                  @click="openAddItemModal"
                >
                  <i class="ri-add-line align-bottom me-1"></i>
                  Add Item
                </b-button>
              </div>
            </div>
            <div class="table-responsive ppmp-table-wrap">
              <table v-if="ppmp.plan_type !== 'ppmp'" class="table align-middle table-hover mb-0">
                <thead class="table-light">
                  <tr class="fs-12">
                    <th style="width: 4%" class="text-center">#</th>
                    <th style="width: 18%">PPMP No.</th>
                    <th>End-User Unit</th>
                    <th style="width: 14%" class="text-end">ABC</th>
                    <th style="width: 16%" class="text-center">Approval</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(source, index) in ppmp.source_ppmps" :key="source.id || index">
                    <td class="text-center fw-semibold">{{ index + 1 }}</td>
                    <td class="fw-medium text-primary">{{ source.ppmp_no || "-" }}</td>
                    <td>
                      <div class="fw-medium">{{ source.unit?.name || "-" }}</div>
                      <small class="text-muted">{{ source.division?.name || "-" }}</small>
                    </td>
                    <td class="text-end fw-semibold">{{ formatCurrency(source.total_amount) }}</td>
                    <td class="text-center">
                      <b-badge variant="success">{{ source.approval_status || "Approved in APP" }}</b-badge>
                    </td>
                  </tr>
                  <tr v-if="!ppmp.source_ppmps?.length">
                    <td colspan="5" class="text-center text-muted py-4">No approved final PPMPs found.</td>
                  </tr>
                </tbody>
                <tfoot>
                  <tr>
                    <th colspan="3" class="text-end">{{ planShortName }} Total ABC</th>
                    <th class="text-end">{{ formatCurrency(ppmp.estimated_budget) }}</th>
                    <th></th>
                  </tr>
                </tfoot>
              </table>

              <table v-else class="table align-middle table-hover mb-0 ppmp-items-table">
                <thead class="table-light">
                  <tr class="fs-12">
                    <th style="width: 4%" class="text-center">#</th>
                    <th style="width: 28%">Item Description</th>
                    <th>PPMP Details</th>
                    <th style="width: 10%" class="text-center">Qty/Unit</th>
                    <th style="width: 14%" class="text-end">Unit Price</th>
                    <th style="width: 14%" class="text-end">ABC</th>
                    <th v-if="canEditIndicativeItems" style="width: 70px" class="text-center"></th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(item, index) in ppmp.item_details" :key="item.id">
                    <td class="text-center fw-semibold">{{ index + 1 }}</td>
                    <td>
                      <span class="fw-semibold text-dark">{{ item.name || "-" }}</span>
                      <div class="text-muted small mt-1 item-description" v-html="item.description || '-'"></div>
                    </td>
                    <td class="fs-12">
                      <div class="detail-chip-list">
                        <span v-if="item.project_type"><strong>Type</strong>{{ item.project_type }}</span>
                        <span v-if="item.item_category"><strong>Category</strong>{{ item.item_category }}</span>
                        <span v-if="item.recommended_mode_of_procurement"><strong>Mode</strong>{{ item.recommended_mode_of_procurement }}</span>
                        <span v-if="item.pre_procurement_conference"><strong>Pre-Proc</strong>{{ item.pre_procurement_conference }}</span>
                        <span v-if="item.attached_supporting_documents"><strong>Docs</strong>{{ item.attached_supporting_documents }}</span>
                        <span v-if="item.remarks"><strong>Remarks</strong>{{ item.remarks }}</span>
                      </div>
                      <a
                        v-if="item.supporting_document_url"
                        :href="item.supporting_document_url"
                        target="_blank"
                        rel="noopener"
                        class="d-inline-block mt-1"
                      >
                        {{ item.supporting_document_original_name || "View attachment" }}
                      </a>
                    </td>

                    <td class="text-center">
                      <span>{{ formatQuantity(item.quantity) }}</span>
                      {{ item.unit || "-" }}
                    </td>
                    <td class="text-end">{{ formatCurrency(item.unit_price) }}</td>
                    <td class="text-end fw-semibold">{{ formatCurrency(item.abc) }}</td>
                    <td v-if="canEditIndicativeItems" class="text-center">
                      <div class="d-flex justify-content-center gap-1">
                        <b-button
                          type="button"
                          variant="success"
                          size="sm"
                          class="btn-icon"
                          style="border-radius: 8px;"
                          @click="openEditItemModal(item)"
                        >
                          <i class="ri-edit-2-line"></i>
                        </b-button>
                        <b-button
                          type="button"
                          variant="danger"
                          size="sm"
                          class="btn-icon"
                          style="border-radius: 8px;"
                          @click="openDeleteItemModal(item)"
                        >
                          <i class="ri-delete-bin-line"></i>
                        </b-button>
                      </div>
                    </td>
                  </tr>
                  <tr v-if="!ppmp.item_details?.length">
                    <td :colspan="canEditIndicativeItems ? 7 : 6" class="text-center text-muted py-4">No items found.</td>
                  </tr>
                </tbody>
                <tfoot>
                  <tr>
                    <th :colspan="canEditIndicativeItems ? 6 :5 " class="text-end">Total ABC</th>
                    <th class="text-end">{{ formatCurrency(ppmp.estimated_budget) }}</th>
                    <th v-if="canEditIndicativeItems"></th>
                  </tr>
                </tfoot>
              </table>
            </div>
          </div>
        </div>
      </div>
    </BCol>
  </BRow>

  <AddItemModal
    ref="addItemModal"
    :ppmp="ppmp"
    :dropdowns="dropdowns"
  />

  <DeleteItemModal ref="deleteItemModal" :ppmp="ppmp" />
</template>

<script>
import PageHeader from "@/Shared/Components/PageHeader.vue";
import { router, useForm } from "@inertiajs/vue3";
import AddItemModal from "./Modals/AddItem.vue";
import DeleteItemModal from "./Modals/DeleteItem.vue";

export default {
  props: ["ppmp", "dropdowns"],
  components: { PageHeader, AddItemModal, DeleteItemModal },
  data() {
    return {
      submitFinalForm: useForm({
        option: "submit_final",
      }),
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
    canAddDraftItem() {
      const ppmpStatus = String(this.ppmp.ppmp_status || "").trim().toLowerCase();
      const approvalStatus = String(this.ppmp.approval_status || "").trim().toLowerCase();
      const isDraftIndicative = approvalStatus === "draft" && ppmpStatus === "indicative";

      return this.ppmp.plan_type === "ppmp" && (this.ppmp.can_add_items || isDraftIndicative);
    },
    planShortName() {
      if (this.ppmp.plan_type === "annual") {
        return "APP";
      }

      if (this.ppmp.plan_type === "supplemental") {
        return "SPP";
      }

      return "PPMP";
    },
    planLongName() {
      if (this.ppmp.plan_type === "annual") {
        return "Annual Procurement Plan";
      }

      if (this.ppmp.plan_type === "supplemental") {
        return "Supplemental Procurement Plan";
      }

      return "Project Procurement Management Plan";
    },
    planScope() {
      if (this.ppmp.plan_type === "supplemental") {
        return "Agency update to approved APP";
      }

      if (this.ppmp.plan_type === "annual") {
        return "Agency consolidated plan";
      }

      return "End-user unit plan";
    },
    planBadgeVariant() {
      if (this.ppmp.plan_type === "annual") {
        return "primary";
      }

      if (this.ppmp.plan_type === "supplemental") {
        return "warning";
      }

      return "info";
    },
    planNumberLabel() {
      if (["annual", "supplemental"].includes(this.ppmp.plan_type)) {
        return "Included PR Nos.";
      }

      return "PR No.";
    },
    planDescription() {
      if (this.ppmp.plan_type === "annual") {
        return "Agency-wide consolidated annual procurement plan";
      }

      if (this.ppmp.plan_type === "supplemental") {
        return "Agency-prepared update to the approved APP for new needs or budget changes";
      }

      return `Project procurement management plan for ${this.ppmp.unit?.name || "unit"}`;
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
      window.open(`/faims/procurement-ppmp/${this.ppmp.id}?option=print&type=ppmp`, "_blank");
    },
    submitFinal() {
      this.submitFinalForm.patch(`/faims/procurement-ppmp/${this.ppmp.id}`, {
        preserveScroll: true,
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
  },
};
</script>

<style scoped>
.ppmp-view-page {
  --ppmp-border: #e9ebec;
  --ppmp-muted: #6c757d;
  --ppmp-soft: #f8fafc;
}

.ppmp-shell {
  border-radius: 10px;
  overflow: hidden;
}

.ppmp-header {
  padding: 1rem 1.15rem;
  background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
  border-bottom: 1px solid var(--ppmp-border);
}

.ppmp-header-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 42px;
  height: 42px;
  color: #405189;
  background: #eef2ff;
  border: 1px solid #dfe5ff;
  border-radius: 8px;
  font-size: 23px;
}

.ppmp-title {
  color: #212529;
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
  border: 1px solid #e9ebec;
  border-radius: 8px;
  padding: 14px;
  background: #fff;
  overflow: hidden;
}

.overview-icon {
  position: absolute;
  right: 14px;
  bottom: 10px;
  color: #e7ebf6;
  font-size: 34px;
}

.overview-label,
.info-row span,
.signatory-list span {
  color: #878a99;
  font-size: 12px;
  font-weight: 600;
}

.overview-value {
  margin-top: 7px;
  color: #212529;
  font-size: 16px;
  font-weight: 700;
}

.section-panel {
  border: 1px solid #e9ebec;
  border-radius: 8px;
  padding: 16px;
  background: #fff;
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
  border-bottom: 1px solid #f0f2f5;
}

.info-row {
  display: flex;
  flex-direction: column;
  gap: 4px;
  min-height: 52px;
}

.info-row strong,
.signatory-list strong {
  color: #212529;
  font-size: 13px;
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
  border: 1px solid #e9ebec;
  border-radius: 8px;
  background: #f8fafc;
}

.plan-detail-banner--annual {
  background: #f7fbff;
  border-color: #d8e9ff;
}

.plan-detail-banner--supplemental {
  background: #fffaf0;
  border-color: #fde7ba;
}

.plan-detail-banner__eyebrow {
  color: #64748b;
  font-size: 11px;
  font-weight: 800;
  text-transform: uppercase;
}

.plan-detail-banner__title {
  margin-top: 2px;
  color: #111827;
  font-size: 16px;
  font-weight: 800;
}

.plan-detail-banner__copy {
  margin-top: 4px;
  color: #64748b;
  font-size: 12px;
}

.plan-detail-banner__hint {
  margin-top: 8px;
  color: #334155;
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
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  background: #fff;
}

.plan-detail-banner__meta span {
  color: #111827;
  font-size: 14px;
  font-weight: 800;
}

.plan-detail-banner__meta small {
  color: #6b7280;
  font-size: 11px;
  font-weight: 600;
}

.ppmp-table-wrap {
  border: 1px solid #eef0f3;
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
  color: #495057;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0;
  border-bottom: 1px solid #e9ecef;
  white-space: nowrap;
}

.ppmp-items-table tbody td {
  border-color: #eef0f3;
}

.item-description {
  line-height: 1.45;
}

.detail-chip-list {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}

.detail-chip-list span {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 4px 7px;
  color: #495057;
  background: #f8f9fa;
  border: 1px solid #edf0f2;
  border-radius: 6px;
}

.detail-chip-list strong {
  color: #878a99;
  font-size: 10px;
  text-transform: uppercase;
}

@media (max-width: 992px) {
  .plan-detail-banner {
    flex-direction: column;
  }

  .plan-detail-banner__meta {
    min-width: 0;
    grid-template-columns: 1fr;
  }
}
</style>
