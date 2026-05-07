<template>
  <Head :title="`${planShortName} Details`" />
  <PageHeader :title="`${planShortName} Details`" pageTitle="Project Procurement Management Plan" />

  <BRow>
    <BCol lg="12">
      <div class="card bg-light-subtle shadow-none border">
        <div class="card-header bg-white border-bottom">
          <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div class="d-flex align-items-center gap-2">
              <span class="p-1 bg-primary-subtle rounded">
                <i class="ri-file-list-3-line text-primary fs-24 " ></i>
              </span>
              <div>
                <div class="d-flex flex-wrap align-items-center gap-2">
                  <h5 class="mb-0 fs-15">{{ ppmp.ppmp_no }}</h5>
                  <b-badge :variant="ppmp.is_final ? 'success' : 'secondary'">
                    {{ ppmp.ppmp_status || "Indicative" }}
                  </b-badge>
                  <b-badge :variant="planBadgeVariant">
                    {{ planShortName }}
                  </b-badge>
                </div>
                <p v-if="ppmp.plan_type === 'ppmp'" class="text-muted mb-0 fs-12">
                  {{ planDescription }} with {{ ppmp.items_count || 0 }}
                  item{{ (ppmp.items_count || 0) === 1 ? "" : "s" }}
                </p>
                <p v-else class="text-muted mb-0 fs-12">
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
              <div v-else-if="ppmp.reviewed_by" class="plan-detail-banner__hint">
                Final PPMP reviewed by {{ ppmp.reviewed_by }}.
              </div>
            </div>
          
          </div>

          <div class="row g-3 mb-3">
            <div v-if="ppmp.plan_type === 'ppmp'" class="col-xl-4 col-md-6">
              <div class="overview-box">
                <span class="overview-label">{{ planNumberLabel }}</span>
                <span class="overview-value">{{ ppmp.pr_no || ppmp.code || "-" }}</span>
              </div>
            </div>
            <div v-if="ppmp.plan_type === 'ppmp'" class="col-xl-4 col-md-6">
              <div class="overview-box">
                <span class="overview-label">Items</span>
                <span class="overview-value">{{ ppmp.items_count || 0 }}</span>
              </div>
            </div>
            <div class="col-xl-4 col-md-6">
              <div class="overview-box">
                <span class="overview-label">Total ABC</span>
                <span class="overview-value text-primary">{{ formatCurrency(ppmp.estimated_budget) }}</span>
              </div>
            </div>
            <div v-if="ppmp.is_final" class="col-xl-4 col-md-6">
              <div class="overview-box">
                <span class="overview-label">Reviewed By</span>
                <span class="overview-value">{{ ppmp.reviewed_by || "-" }}</span>
              </div>
            </div>
          </div>

          <div class="row g-3">
            <div class="col-lg-8">
              <div class="section-panel">
                <div class="section-heading">
                  <h6 class="mb-0 fs-14">Overview</h6>
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
                  <div v-if="ppmp.plan_type === 'ppmp'" class="col-md-6">
                    <div class="info-row">
                      <span>{{ ppmp.plan_type === "ppmp" ? "Type of Project" : "Consolidated Project Types" }}</span>
                      <strong>{{ ppmp.type_of_project || "-" }}</strong>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="info-row">
                      <span>Source of Funds</span>
                      <strong>{{ ppmp.source_of_funds || "-" }}</strong>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="info-row">
                      <span>{{ ppmp.plan_type === "ppmp" ? "Start of Procurement" : "Earliest Procurement Date" }}</span>
                      <strong>{{ formatDate(ppmp.start_of_procurement_activity) }}</strong>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="info-row">
                      <span>End of Procurement</span>
                      <strong>{{ formatDate(ppmp.end_of_procurement_activity) }}</strong>
                    </div>
                  </div>
                  <div v-if="ppmp.plan_type === 'ppmp'" class="col-md-6">
                    <div class="info-row">
                      <span>Pre-Procurement Conference</span>
                      <strong>{{ ppmp.pre_procurement_conference || "-" }}</strong>
                    </div>
                  </div>
                  <div v-if="ppmp.plan_type === 'ppmp'" class="col-md-6">
                    <div class="info-row">
                      <span>Expected Delivery / Implementation</span>
                      <strong>{{ ppmp.expected_delivery_implementation_period || "-" }}</strong>
                    </div>
                  </div>
                  <div v-if="ppmp.plan_type === 'ppmp'" class="col-12">
                    <div class="info-row">
                      <span>Recommended Mode of Procurement</span>
                      <strong>{{ ppmp.recommended_mode_of_procurement || "-" }}</strong>
                    </div>
                  </div>
                  <div v-if="ppmp.plan_type === 'ppmp'" class="col-12">
                    <div class="info-row">
                      <span>Quantity and Item Coverage</span>
                      <strong>{{ ppmp.quantity_and_size || "-" }}</strong>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-lg-4">
              <div class="section-panel h-100">
                <div class="section-heading">
                  <h6 class="mb-0 fs-14">Signatories</h6>
                </div>
                <div class="signatory-list">
                  <div>
                    <span>{{ ppmp.plan_type === "annual" ? "Prepared / Consolidated By" : "Prepared By" }}</span>
                    <strong>{{ ppmp.prepared_by || "-" }}</strong>
                    <small v-if="ppmp.plan_type === 'supplemental'" class="text-muted">Agency</small>
                  </div>
                  <div>
                    <span>{{ ppmp.plan_type === "annual" ? "Submitted / Certified By" : "Submitted By" }}</span>
                    <strong>{{ ppmp.submitted_by || "-" }}</strong>
                  </div>
                </div>
                <hr class="text-muted" />
                <div class="info-row mb-2">
                  <span>Supporting Documents</span>
                  <strong>{{ ppmp.attached_supporting_documents || "-" }}</strong>
                </div>
                <div class="info-row">
                  <span>Remarks</span>
                  <strong>{{ ppmp.remarks || "-" }}</strong>
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
            <div class="table-responsive">
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

              <table v-else class="table align-middle table-hover mb-0">
                <thead class="table-light">
                  <tr class="fs-12">
                    <th style="width: 4%" class="text-center">#</th>
                    <th style="width: 18%">Item</th>
                    <th>Description</th>
                    <th style="width: 18%">PPMP Details</th>
                    <th style="width: 10%" class="text-end">Qty</th>
                    <th style="width: 10%">Unit</th>
                    <th style="width: 14%" class="text-end">Unit Price</th>
                    <th style="width: 14%" class="text-end">ABC</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(item, index) in ppmp.item_details" :key="item.id">
                    <td class="text-center fw-semibold">{{ index + 1 }}</td>
                    <td class="fw-medium">{{ item.name || "-" }}</td>
                    <td>
                      <div class="text-muted" v-html="item.description || '-'"></div>
                    </td>
                    <td class="fs-12">
                      <div><strong>Type:</strong> {{ item.project_type || "-" }}</div>
                      <div><strong>Mode:</strong> {{ item.recommended_mode_of_procurement || "-" }}</div>
                      <div><strong>Docs:</strong> {{ item.attached_supporting_documents || "-" }}</div>
                      <a
                        v-if="item.supporting_document_url"
                        :href="item.supporting_document_url"
                        target="_blank"
                        rel="noopener"
                        class="d-inline-block mt-1"
                      >
                        {{ item.supporting_document_original_name || "View attachment" }}
                      </a>
                      <div><strong>Remarks:</strong> {{ item.remarks || "-" }}</div>
                    </td>
                    <td class="text-end">{{ formatQuantity(item.quantity) }}</td>
                    <td>{{ item.unit || "-" }}</td>
                    <td class="text-end">{{ formatCurrency(item.unit_price) }}</td>
                    <td class="text-end fw-semibold">{{ formatCurrency(item.abc) }}</td>
                  </tr>
                  <tr v-if="!ppmp.item_details?.length">
                    <td colspan="8" class="text-center text-muted py-4">No items found.</td>
                  </tr>
                </tbody>
                <tfoot>
                  <tr>
                    <th colspan="7" class="text-end">Total ABC</th>
                    <th class="text-end">{{ formatCurrency(ppmp.estimated_budget) }}</th>
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
</template>

<script>
import PageHeader from "@/Shared/Components/PageHeader.vue";
import { router, useForm } from "@inertiajs/vue3";
import AddItemModal from "./Modals/AddItem.vue";

export default {
  props: ["ppmp", "dropdowns"],
  components: { PageHeader, AddItemModal },
  data() {
    return {
      submitFinalForm: useForm({
        option: "submit_final",
      }),
    };
  },
  computed: {
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
      if (this.ppmp.plan_type === "annual") {
        return "Agency-wide consolidated plan";
      }

      if (this.ppmp.plan_type === "supplemental") {
        return "Agency update to approved APP";
      }

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
  },
};
</script>

<style scoped>
.overview-box {
  display: flex;
  flex-direction: column;
  min-height: 76px;
  border: 1px solid #e9ebec;
  border-radius: 8px;
  padding: 14px;
  background: #ffff;
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
  gap: 16px;
  padding: 16px;
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
