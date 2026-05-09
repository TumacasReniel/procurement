<template>
  <Head title="PPMP" />
  <PageHeader title="Project Procurement Management Plan" pageTitle="Procurement" />

  <BRow class="procurement-index-page ppmp-index-page">
    <div class="col-md-12">
      <div class="card ppmp-shell shadow-none border">
        <div class="card-header ppmp-shell__header">
          <div class="d-flex mb-n3">
            <div class="flex-shrink-0 me-3">
              <div style="height: 2.5rem; width: 2.5rem">
                <span class="avatar-title bg-primary-subtle rounded p-2 mt-n1">
                  <i class="ri-file-list-3-line text-primary fs-24"></i>
                </span>
              </div>
            </div>
            <div class="flex-grow-1">
              <h5 class="mb-0 fs-14">
                <span class="text-body">{{ activePlanTitle }}</span>
              </h5>
              <p class="text-muted text-truncate-two-lines fs-12">
                {{ activePlanDescription }}
              </p>
            </div>
          </div>
        </div>

        <div class="card-body ppmp-filter-panel border-bottom shadow-none">
          <div class="px-3 pt-3">
            <ul class="nav nav-tabs nav-tabs-custom" role="tablist">
              <li class="nav-item" role="presentation">
                <button
                  type="button"
                  class="nav-link"
                  :class="{ active: filter.plan_type === 'all' }"
                  @click="setPlanTab('all')"
                >
                  <i class="ri-table-line align-bottom me-1"></i>
                  All
                </button>
              </li>
              <li class="nav-item" role="presentation">
                <button
                  type="button"
                  class="nav-link"
                  :class="{ active: filter.plan_type === 'annual' }"
                  @click="setPlanTab('annual')"
                >
                  <i class="ri-file-list-3-line align-bottom me-1"></i>
                  APP
                </button>
              </li>
              <li class="nav-item" role="presentation">
                <button
                  type="button"
                  class="nav-link"
                  :class="{ active: filter.plan_type === 'supplemental' }"
                  @click="setPlanTab('supplemental')"
                >
                  <i class="ri-add-circle-line align-bottom me-1"></i>
                  SPP
                </button>
              </li>
            </ul>

          </div>
          <b-row class="mb-2 ms-1 me-1" style="margin-top: 12px">
            <b-col lg>
              <div class="input-group mb-1">
                <span class="input-group-text">
                  <i class="ri-search-line search-icon"></i>
                </span>
                <input
                  type="text"
                  v-model="filter.keyword"
                  :placeholder="`Search ${activePlanLabel}`"
                  class="form-control"
                  :style="{ width: filter.plan_type === 'annual' || filter.plan_type === 'supplemental' ? '52%' : '37%' }"
                />
                <Multiselect
                  v-if="filter.plan_type === 'all'"
                  class="white"
                  style="width: 15%"
                  :options="unitOptions"
                  v-model="filter.unit"
                  label="name"
                  value-prop="value"
                  :searchable="true"
                  :append-to-body="true"
                  placeholder="Select Unit"
                />
                <Multiselect
                  class="white"
                  style="width: 14%"
                  :options="statusOptions"
                  v-model="filter.status"
                  label="name"
                  value-prop="value"
                  :searchable="true"
                  :append-to-body="true"
                  placeholder="Select Status"
                />
                <select v-model="filter.sort" class="form-select" style="width: 14%">
                  <option value="latest">Latest Date</option>
                  <option value="oldest">Oldest Date</option>
                  <option v-if="filter.plan_type === 'all'" value="pr_asc">PR Number A-Z</option>
                  <option v-if="filter.plan_type === 'all'" value="pr_desc">PR Number Z-A</option>
                </select>
                <span
                  @click="refresh()"
                  class="input-group-text"
                  v-b-tooltip.hover
                  title="Refresh"
                  style="cursor: pointer"
                >
                  <i class="bx bx-refresh search-icon"></i>
                </span>
                <b-button
                  v-if="filter.plan_type === 'all'"
                  variant="primary"
                  @click="openCreatePpmpModal"
                >
                  <i class="ri-add-circle-line align-bottom me-1"></i>
                  Create PPMP
                </b-button>
                <b-button
                  v-if="filter.plan_type === 'annual' && canManagePPMP"
                  variant="primary"
                  @click="openCreateAppModal"
                >
                  <i class="ri-file-add-line align-bottom me-1"></i>
                  Create APP
                </b-button>
                <b-button
                  v-if="filter.plan_type === 'supplemental' && canCreateSPP"
                  variant="warning"
                  @click="openCreateSppModal"
                >
                  <i class="ri-add-circle-line align-bottom me-1"></i>
                  Create SPP
                </b-button>
              </div>
            </b-col>
          </b-row>
        </div>

        <b-card no-body class="ppmp-list-card">
          <div class="card-body ppmp-table-panel rounded-bottom">
            <div
              class="table-responsive table-card ppmp-list-scroll"
            >
              <table class="table align-middle table-hover mb-0 ppmp-table">
                <thead class="table-light thead-fixed">
                  <tr class="fs-12 fw-semibold align-middle">
                    <th style="width: 4%" class="text-center">#</th>
                    <th style="min-width: 160px">Plan No.</th>
                    <th style="min-width: 260px">{{ isPlanRegister ? "Description" : "Summary" }}</th>
                    <th style="min-width: 180px">Scope / Plan</th>
                    <th style="min-width: 210px">{{ isPlanRegister ? "Included PPMPs" : "Items" }}</th>
                    <th style="min-width: 130px" class="text-end">Total ABC</th>
                    <th style="min-width: 140px">Schedule</th>
                    <th style="min-width: 165px" class="text-center">Status</th>
                    <th style="min-width: 100px" class="text-center">Actions</th>
                  </tr>
                </thead>

                <tbody class="table-group-divider">
                  <tr
                    v-for="(list, index) in lists"
                    :key="list.id"
                    @click="selectRow(list.id)"
                    :class="{ 'table-active': selectedRow === list.id }"
                    class="cursor-pointer"
                  >
                    <td class="text-center fw-semibold">
                      {{ index + 1 }}
                    </td>
                    <td>
                      <div class="fw-semibold text-primary">{{ list.ppmp_no || "-" }}</div>
                      <small class="d-block text-muted">{{ planShortName(list) }} - {{ list.ppmp_count || 1 }} source entr{{ (list.ppmp_count || 1) === 1 ? "y" : "ies" }}</small>
                    </td>
                    <td>
                      <div 
                        class="text-truncate fw-medium"
                        style="max-width: 260px"
                        v-b-tooltip.hover
                        :title="list.general_description_objective"
                      >
                        {{ list.general_description_objective || "-" }}
                      </div>
                      <small class="d-block text-muted">{{ list.type_of_project || "Unclassified" }}</small>
                      <small class="d-block text-muted">{{ list.recommended_mode_of_procurement || "No mode set" }}</small>
                    </td>
                    <td>
                      <div class="fw-medium">{{ list.unit?.name || "-" }}</div>
                      <small class="text-muted">{{ list.plan_type === "ppmp" ? list.division?.name || "-" : "All end-user units" }}</small>
                      <small class="d-block text-muted">{{ list.plan_name || "PPMP" }}</small>
                    </td>
                    <td>
                      <template v-if="['annual', 'supplemental'].includes(list.plan_type)">
                        <div class="fw-semibold">{{ (list.source_ppmps || []).length }} approved PPMP{{ (list.source_ppmps || []).length === 1 ? "" : "s" }}</div>
                        <small
                          v-if="(list.source_ppmps || []).length"
                          class="d-block text-muted text-truncate"
                          style="max-width: 210px"
                          v-b-tooltip.hover
                          :title="(list.source_ppmps || []).map(ppmp => ppmp.unit?.name).filter(Boolean).join(', ')"
                        >
                          {{ (list.source_ppmps || []).map(ppmp => ppmp.unit?.name).filter(Boolean).slice(0, 2).join(", ") }}
                        </small>
                      </template>
                      <template v-else>
                        <div class="fw-semibold">{{ list.items_count || 0 }} item{{ (list.items_count || 0) === 1 ? "" : "s" }}</div>
                      </template>
                      <small
                        v-if="!['annual', 'supplemental'].includes(list.plan_type) && (list.item_details || []).length"
                        class="d-block text-muted text-truncate"
                        style="max-width: 210px"
                        v-b-tooltip.hover
                        :title="(list.item_details || []).map(item => item.name || item.description).filter(Boolean).join(', ')"
                      >
                        {{ (list.item_details || []).map(item => item.name || item.description).filter(Boolean).slice(0, 2).join(", ") }}
                      </small>
                    </td>
                    <td class="text-end">
                      <span class="fw-semibold">{{ formatCurrency(list.estimated_budget) }}</span>
                      <small class="d-block text-muted">{{ list.source_of_funds || "-" }}</small>
                    </td>
                    <td>
                      <div>{{ formatDate(list.start_of_procurement_activity) }}</div>
                      <small class="text-muted">Start date</small>
                    </td>
                    <td class="text-center">
                      <b-badge :variant="ppmpStatusBadgeVariant(list)">
                        {{ list.ppmp_status || "Indicative" }}
                      </b-badge>
                      <small v-if="list.reviewed_by" class="d-block text-muted mt-1">
                        Reviewed by {{ list.reviewed_by }}
                      </small>
                    </td>
                    <td class="text-center">
                      <div class="ppmp-action-group">
                        <b-button
                          @click.stop="goPPMPPage(list)"
                          size="sm"
                          variant="info"
                          class="btn-icon"
                          v-b-tooltip.hover
                          :title="`View ${planShortName(list)} Details`"
                          style="border-radius: 8px"
                        >

                          <i class="ri-eye-line"></i>
                        </b-button>
                        <b-button
                          v-if="canApprovePPMP(list)"
                          @click.stop="openApproveFinalModal(list)"
                          size="sm"
                          variant="success"
                          class="btn-icon"
                          v-b-tooltip.hover
                          title="Mark as final PPMP"
                          style="border-radius: 8px"
                          :disabled="approveFinalForm.processing"
                        >
                          <i class="ri-check-double-line"></i>
                        </b-button>
                        <b-button
                          @click.stop="printPPMP(list)"
                          size="sm"
                          variant="dark"
                          class="btn-icon"
                          v-b-tooltip.hover
                          title="Print"
                          style="border-radius: 8px"
                        >
                          <i class="ri-printer-line"></i>
                        </b-button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>

              <div v-if="!lists.length" class="text-center text-muted py-4">
                No {{ activePlanLabel }} entries found. {{ emptyStateHint }}
              </div>

              <div class="card-footer">
                <Pagination
                  class="ms-2 me-2 mt-n1"
                  v-if="meta"
                  @fetch="fetch"
                  :lists="lists.length"
                  :links="links"
                  :pagination="meta"
                />
              </div>
            </div>
          </div>
        </b-card>
      </div>
    </div>
  </BRow>

  <b-modal
    v-model="approveFinalModal.show"
    header-class="p-3"
    title="Confirm Final PPMP"
    size="md"
    class="v-modal-custom"
    modal-class="zoomIn"
    centered
    no-close-on-backdrop
  >
    <div v-if="approveFinalModal.data" class="ppmp-confirm">
      <div class="ppmp-confirm__icon">
        <i class="ri-check-double-line"></i>
      </div>
      <div>
        <h5 class="mb-1">Mark this PPMP as final?</h5>
        <p class="text-muted mb-3">
          This will approve the plan for consolidation and further procurement planning.
        </p>
      </div>

      <div class="ppmp-confirm__summary">
        <div>
          <span>Plan No.</span>
          <strong>{{ approveFinalModal.data.ppmp_no || "-" }}</strong>
        </div>
        <div>
          <span>Unit</span>
          <strong>{{ approveFinalModal.data.unit?.name || "-" }}</strong>
        </div>
        <div>
          <span>Total ABC</span>
          <strong>{{ formatCurrency(approveFinalModal.data.estimated_budget) }}</strong>
        </div>
      </div>
    </div>

    <template v-slot:footer>
      <b-button @click="closeApproveFinalModal" variant="light" block>
        Cancel
      </b-button>
      <b-button
        @click="approveFinal"
        variant="success"
        :disabled="approveFinalForm.processing"
        block
      >
        <i class="ri-check-double-line align-bottom me-1"></i>
        {{ approveFinalForm.processing ? "Approving..." : "Approve Final" }}
      </b-button>
    </template>
  </b-modal>

  <b-modal
    v-model="createPpmpModal.show"
    header-class="p-3"
    title="Create Unit PPMP"
    size="md"
    class="v-modal-custom"
    modal-class="zoomIn"
    centered
    no-close-on-backdrop
  >
    <form class="customform">
      <BRow>
        <BCol lg="12" class="mt-2">
          <label class="form-label">Plan Year</label>
          <Multiselect
            :options="yearOptions"
            v-model="createPpmpForm.year"
            label="name"
            value-prop="value"
            :searchable="true"
            :class="{ 'is-invalid': createPpmpForm.errors.year }"
            placeholder="Select Year"
          />
          <div v-if="createPpmpForm.errors.year" class="invalid-feedback d-block">
            {{ createPpmpForm.errors.year }}
          </div>
        </BCol>

        <BCol lg="12" class="mt-2">
          <label class="form-label">Unit Without PPMP</label>
          <Multiselect
            :options="availablePpmpUnits"
            v-model="createPpmpForm.unit_id"
            label="name"
            value-prop="value"
            :searchable="true"
            :loading="createPpmpModal.loading"
            :class="{ 'is-invalid': createPpmpForm.errors.unit_id }"
            placeholder="Select Unit"
          />
          <div v-if="createPpmpForm.errors.unit_id" class="invalid-feedback d-block">
            {{ createPpmpForm.errors.unit_id }}
          </div>
          <small v-if="!createPpmpModal.loading && !availablePpmpUnits.length" class="text-muted d-block mt-2">
            All active units already have a PPMP for the selected year.
          </small>
        </BCol>
      </BRow>
    </form>

    <template v-slot:footer>
      <b-button @click="closeCreatePpmpModal" variant="light" block>Close</b-button>
      <b-button
        @click="submitCreatePpmp"
        variant="primary"
        :disabled="createPpmpForm.processing || !createPpmpForm.unit_id"
        block
      >
        {{ createPpmpForm.processing ? "Creating..." : "Create PPMP" }}
      </b-button>
    </template>
  </b-modal>

  <b-modal
    v-model="createAppModal.show"
    header-class="p-3"
    title="Create APP"
    size="md"
    class="v-modal-custom"
    modal-class="zoomIn"
    centered
    no-close-on-backdrop
  >
    <form class="customform">
      <BRow>
        <BCol lg="12" class="mt-2">
          <label class="form-label">APP Year</label>
          <Multiselect
            :options="availableAppYearOptions"
            v-model="createAppForm.year"
            label="name"
            value-prop="value"
            :searchable="true"
            :class="{ 'is-invalid': createAppForm.errors.year || createAppForm.errors.plan_type }"
            placeholder="Select Year"
          />
          <div v-if="createAppForm.errors.year || createAppForm.errors.plan_type" class="invalid-feedback d-block">
            {{ createAppForm.errors.year || createAppForm.errors.plan_type }}
          </div>
          <small v-if="!availableAppYearOptions.length" class="text-muted d-block mt-2">
            All selectable years already have an APP.
          </small>
        </BCol>

        <BCol lg="12" class="mt-3">
          <div class="alert alert-info mb-0 fs-13">
            This creates one APP register for the selected year using final PPMP entries.
          </div>
        </BCol>
      </BRow>
    </form>

    <template v-slot:footer>
      <b-button @click="closeCreateAppModal" variant="light" block>Close</b-button>
      <b-button
        @click="submitCreateApp"
        variant="primary"
        :disabled="createAppForm.processing || !createAppForm.year || appYearHasExisting"
        block
      >
        {{ createAppForm.processing ? "Creating..." : "Create APP" }}
      </b-button>
    </template>
  </b-modal>

  <CreateSppModal
    v-model="createSppModal.show"
    :form="createSppForm"
    :dropdowns="dropdowns"
    :unit-options="unitOptions"
    :current-year="currentYear"
    @close="closeCreateSppModal"
    @submit="submitCreateSpp"
  />

  <b-modal
    v-model="detailModal.show"
    header-class="p-3"
    title="PPMP Details"
    size="xl"
    class="v-modal-custom"
    modal-class="zoomIn"
    centered
    no-close-on-backdrop
  >
    <div v-if="detailModal.data" class="ppmp-print-area">
      <div class="ppmp-document-header">
        <img src="/images/logo-sm.png" alt="DOST Logo" class="ppmp-header-logo" />
        <div class="text-center flex-grow-1">
          <div class="fw-semibold fs-18">Republic of the Philippines</div>
          <div class="fw-bold fs-20">DEPARTMENT OF SCIENCE AND TECHNOLOGY</div>
          <div class="fw-semibold fs-18">Regional Office IX</div>
        </div>
        <img src="/images/bp-logo.webp" alt="Bagong Pilipinas Logo" class="ppmp-header-logo" />
      </div>

      <div class="ppmp-title-block">
        <div class="fw-bold fs-22">
          PROJECT PROCUREMENT MANAGEMENT PLAN (PPMP) NO.
          <span class="ppmp-line">{{ detailModal.data.ppmp_no || "" }}</span>
        </div>
        <div class="d-flex justify-content-center gap-5 mt-2">
          <div class="ppmp-status-check">
            <span class="ppmp-checkbox" :class="{ checked: !detailModal.data.is_final }"></span>
            <span>INDICATIVE</span>
          </div>
          <div class="ppmp-status-check">
            <span class="ppmp-checkbox" :class="{ checked: detailModal.data.is_final }"></span>
            <span>FINAL</span>
          </div>
        </div>
      </div>

      <div class="row g-2 mb-3">
        <div class="col-md-4">
          <div class="border rounded p-2 h-100">
            <small class="text-muted d-block">Plan</small>
            <span class="fw-semibold">{{ detailModal.data.plan_name }}</span>
          </div>
        </div>
        <div class="col-md-4">
          <div class="border rounded p-2 h-100">
            <small class="text-muted d-block">Unit</small>
            <span class="fw-semibold">{{ detailModal.data.unit?.name || "-" }}</span>
          </div>
        </div>
        <div class="col-md-4">
          <div class="border rounded p-2 h-100">
            <small class="text-muted d-block">Total ABC</small>
            <span class="fw-semibold">{{ formatCurrency(detailModal.data.estimated_budget) }}</span>
          </div>
        </div>
      </div>

      <div class="table-responsive">
        <table class="table table-bordered align-middle mb-0">
          <thead class="table-light">
            <tr class="fs-12 text-center">
              <th style="width: 4%">#</th>
              <th>Item</th>
              <th>Description</th>
              <th style="width: 10%">Qty</th>
              <th style="width: 10%">Unit</th>
              <th style="width: 14%">Unit Price</th>
              <th style="width: 14%">ABC</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(item, itemIndex) in detailModal.data.item_details" :key="item.id">
              <td class="text-center">{{ itemIndex + 1 }}</td>
              <td>{{ item.name || "-" }}</td>
              <td>{{ item.description || "-" }}</td>
              <td class="text-end">{{ formatQuantity(item.quantity) }}</td>
              <td>{{ item.unit || "-" }}</td>
              <td class="text-end">{{ formatCurrency(item.unit_price) }}</td>
              <td class="text-end fw-semibold">{{ formatCurrency(item.abc) }}</td>
            </tr>
            <tr v-if="!detailModal.data.item_details.length">
              <td colspan="7" class="text-center text-muted">No items found.</td>
            </tr>
          </tbody>
          <tfoot>
            <tr>
              <th colspan="6" class="text-end">Total ABC</th>
              <th class="text-end">{{ formatCurrency(detailModal.data.estimated_budget) }}</th>
            </tr>
          </tfoot>
        </table>
      </div>

      <div class="ppmp-signatories">
        <div class="ppmp-signatory">
          <div class="ppmp-signatory-line">{{ detailModal.data.prepared_by || "" }}</div>
          <div class="ppmp-signatory-label">Prepared By</div>
        </div>
        <div class="ppmp-signatory">
          <div class="ppmp-signatory-line">{{ detailModal.data.submitted_by || "" }}</div>
          <div class="ppmp-signatory-label">Submitted By</div>
        </div>
      </div>
    </div>

    <template v-slot:footer>
      <b-button @click="detailModal.show = false" variant="light" block>Close</b-button>
      <b-button @click="printPPMP(detailModal.data)" variant="dark" block>
        <i class="ri-printer-line align-bottom me-1"></i>
        Print
      </b-button>
    </template>
  </b-modal>
</template>

<script>
import _ from "lodash";
import { router, useForm } from "@inertiajs/vue3";
import Multiselect from "@vueform/multiselect";
import PageHeader from "@/Shared/Components/PageHeader.vue";
import Pagination from "@/Shared/Components/Pagination.vue";
import CreateSppModal from "./Modals/CreateSpp.vue";

export default {
  props: ["dropdowns"],
  components: {
    Multiselect,
    PageHeader,
    Pagination,
    CreateSppModal,
  },
  data() {
    return {
      lists: [],
      meta: {},
      links: {},
      selectedRow: null,
      filter: {
        keyword: null,
        status: null,
        unit: null,
        plan_type: "all",
        sort: "latest",
      },
      createPpmpModal: {
        show: false,
        loading: false,
      },
      createAppModal: {
        show: false,
      },
      createSppModal: {
        show: false,
      },
      createPpmpForm: useForm({
        option: "create_unit_ppmp",
        unit_id: null,
        year: new Date().getFullYear(),
      }),
      createAppForm: useForm({
        year: new Date().getFullYear(),
        plan_type: "annual",
      }),
      createSppForm: useForm({
        year: new Date().getFullYear(),
        plan_type: "supplemental",
        unit_id: null,
        item_name: "",
        item_description: "",
        project_type: "",
        item_category_id: null,
        item_category: "",
        recommended_mode_of_procurement: "",
        pre_procurement_conference: "",
        item_quantity: null,
        item_unit_type_id: null,
        item_unit_cost: null,
        end_of_procurement_activity: null,
        expected_delivery_date: null,
        attached_supporting_documents: "",
        supporting_document_file: null,
        remarks: "",
      }),
      availablePpmpUnits: [],
      detailModal: {
        show: false,
        data: null,
      },
      approveFinalForm: useForm({
        option: "submit_final",
      }),
      approveFinalModal: {
        show: false,
        data: null,
      },
    };
  },
  computed: {
    currentRoles() {
      return Array.isArray(this.$page?.props?.roles) ? this.$page.props.roles : [];
    },
    canManagePPMP() {
      return this.currentRoles.some((role) => ["Procurement Officer", "Administrator"].includes(role));
    },
    canCreateSPP() {
      return this.currentRoles.some((role) => ["Procurement Officer"].includes(role));
    },
    isPlanRegister() {
      return ["annual", "supplemental"].includes(this.filter.plan_type);
    },
    unitOptions() {
      return this.normalizeOptions(this.dropdowns?.units);
    },
    defaultUserUnitId() {
      const unitId = this.$page.props.user?.data?.organization?.unit_id;

      return unitId ? Number(unitId) : null;
    },
    statusOptions() {
      return this.normalizeOptions(this.dropdowns?.statuses);
    },
    yearOptions() {
      const years = [];

      for (let year = this.currentYear + 2; year >= this.currentYear - 5; year--) {
        years.push({ value: year, name: year.toString() });
      }

      return years;
    },
    currentYear() {
      return new Date().getFullYear();
    },
    annualAppYears() {
      const years = this.dropdowns?.annual_app_years || [];
      return Array.isArray(years) ? years.map((year) => Number(year)) : [];
    },
    availableAppYearOptions() {
      const usedYears = new Set(this.annualAppYears);
      return this.yearOptions.filter((year) => !usedYears.has(Number(year.value)));
    },
    appYearHasExisting() {
      return this.annualAppYears.includes(Number(this.createAppForm.year));
    },
    activePlanTitle() {
      if (this.filter.plan_type === "annual") {
        return "Annual Procurement Plan";
      }

      if (this.filter.plan_type === "supplemental") {
        return "Supplemental Procurement Plan";
      }

      return "Project Procurement Management Plans";
    },
    activePlanDescription() {
      if (this.filter.plan_type === "annual") {
        return "This is the agency-wide consolidated plan built only from final PPMPs approved by the Procurement Officer.";
      }

      if (this.filter.plan_type === "supplemental") {
        return "SPP is prepared by the agency after APP approval to add or change items due to new needs or budget.";
      }

      return "PPMP entries are organized per end-user unit and become the source for purchase request items.";
    },
    activePlanLabel() {
      if (this.filter.plan_type === "supplemental") {
        return "SPP";
      }

      if (this.filter.plan_type === "annual") {
        return "agency-wide APP";
      }

      return "PPMP";
    },
    emptyStateHint() {
      if (this.filter.plan_type === "annual") {
        return "Approve unit PPMPs as final first, then the APP list appears here by year.";
      }

      if (this.filter.plan_type === "supplemental") {
        return "Approve the APP first, then submit new or changed PPMP entries for the SPP update.";
      }

      return "Create PPMP items first from purchase planning.";
    },
  },
  watch: {
    "filter.keyword"() {
      this.checkSearchStr();
    },
    "filter.status"() {
      this.fetch();
    },
    "filter.unit"() {
      this.fetch();
    },
    "filter.sort"() {
      this.fetch();
    },
    "createPpmpForm.year"() {
      if (this.createPpmpModal.show) {
        this.fetchAvailablePpmpUnits();
      }
    },
  },
  created() {
    this.fetch();
  },
  methods: {
    canApprovePPMP(item) {
      if (!this.currentRoles.some((role) => ["Procurement Officer"].includes(role))) {
        return false;
      }

      if (!item || item.is_final || (item.ppmp_status || "").toLowerCase() === "final") {
        return false;
      }

      const statusName = typeof item.status === "string"
        ? item.status
        : item.status?.name;

      const isPending = [statusName, item.ppmp_status]
        .filter(Boolean)
        .some((status) => String(status).toLowerCase() === "pending");

      return Boolean(item.can_submit_final) || isPending;
    },
    normalizeOptions(options) {
      if (Array.isArray(options)) {
        return options;
      }

      if (options && typeof options === "object") {
        return Object.values(options);
      }

      return [];
    },
    checkSearchStr: _.debounce(function () {
      this.fetch();
    }, 300),
    fetch(pageUrl) {
      const url = pageUrl || "/faims/procurement-ppmp";

      axios
        .get(url, {
          params: {
            keyword: this.filter.keyword,
            status: this.filter.status,
            unit: this.filter.unit,
            plan_type: this.filter.plan_type,
            sort: this.filter.sort,
            count: 10,
            option: "lists",
          },
        })
        .then((response) => {
          this.lists = response.data.data || [];
          this.meta = response.data.meta || {};
          this.links = response.data.links || {};
        })
        .catch((error) => console.log(error));
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
    formatCurrency(value) {
      return new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
      }).format(Number(value || 0));
    },
    formatQuantity(value) {
      const amount = Number(value || 0);

      return Number.isInteger(amount) ? amount.toString() : amount.toFixed(2);
    },
    planShortName(item) {
      if (item?.plan_type === "annual") {
        return "APP";
      }

      if (item?.plan_type === "supplemental") {
        return "SPP";
      }

      return "PPMP";
    },
    ppmpStatusBadgeVariant(item) {
      if ((item?.ppmp_status || "").toLowerCase() === "final" || item?.is_final) {
        return "success";
      }

      return "secondary";
    },
    goViewPage(data) {
      router.get(`/faims/procurements/${data.id}`, { option: "view" });
    },
    goPPMPPage(data) {
      router.get(`/faims/procurement-ppmp/${data.id}`, {
        plan_type: data?.plan_type || "ppmp",
      });
    },
    openApproveFinalModal(data) {
      if (!data?.id || this.approveFinalForm.processing) {
        return;
      }

      this.approveFinalForm.clearErrors();
      this.approveFinalModal.data = data;
      this.approveFinalModal.show = true;
    },
    closeApproveFinalModal() {
      if (this.approveFinalForm.processing) {
        return;
      }

      this.approveFinalModal.show = false;
      this.approveFinalModal.data = null;
      this.approveFinalForm.clearErrors();
    },
    approveFinal() {
      const data = this.approveFinalModal.data;
      if (!data?.id || this.approveFinalForm.processing) {
        return;
      }

      this.approveFinalForm.patch(`/faims/procurement-ppmp/${data.id}`, {
        preserveScroll: true,
        onSuccess: () => {
          this.approveFinalModal.show = false;
          this.approveFinalModal.data = null;
          this.fetch();
        },
      });
    },
    openPrint(data) {
      window.open(`/faims/procurements/${data.id}?option=print&type=procurement`);
    },
    openDetails(data) {
      this.detailModal.data = data;
      this.detailModal.show = true;
    },
    printPPMP(data) {
      if (!data?.id) {
        return;
      }

      window.open(`/faims/procurement-ppmp/${data.id}?option=print&type=ppmp`, "_blank");
      return;

      const rows = (data.item_details || [])
        .map((item, index) => `
          <tr>
            <td class="text-center">${index + 1}</td>
            <td>${this.escapeHtml(item.name || "-")}</td>
            <td>${this.escapeHtml(item.description || "-")}</td>
            <td class="text-right">${this.escapeHtml(this.formatQuantity(item.quantity))}</td>
            <td>${this.escapeHtml(item.unit || "-")}</td>
            <td class="text-right">${this.escapeHtml(this.formatCurrency(item.unit_price))}</td>
            <td class="text-right">${this.escapeHtml(this.formatCurrency(item.abc))}</td>
          </tr>
        `)
        .join("");

      const printWindow = window.open("", "_blank", "width=1200,height=800");

      if (!printWindow) {
        return;
      }

      printWindow.document.write(`
        <!doctype html>
        <html>
          <head>
            <title>PPMP ${this.escapeHtml(data.ppmp_no || "")}</title>
            <style>
              * { box-sizing: border-box; }
              body { font-family: Arial, Helvetica, sans-serif; color: #000; margin: 24px; }
              .document-header { display: flex; align-items: center; justify-content: space-between; border-bottom: 2px solid #000; padding-bottom: 12px; margin-bottom: 18px; }
              .header-logo { width: 95px; height: 95px; object-fit: contain; }
              .agency { text-align: center; flex: 1; line-height: 1.25; }
              .agency .country { font-weight: 700; font-size: 22px; }
              .agency .department { font-weight: 800; font-size: 24px; }
              .agency .office { font-weight: 700; font-size: 22px; }
              .title { text-align: center; font-size: 26px; font-weight: 800; margin: 14px 0 4px; }
              .line { display: inline-block; min-width: 90px; border-bottom: 3px solid #000; }
              .checks { display: flex; justify-content: center; gap: 90px; align-items: center; font-size: 24px; font-weight: 800; margin-bottom: 18px; }
              .box { display: inline-flex; width: 32px; height: 32px; border: 1.5px solid #000; margin-right: 12px; align-items: center; justify-content: center; vertical-align: middle; }
              .box.checked::after { content: "✓"; font-size: 24px; line-height: 1; }
            .meta { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-bottom: 14px; font-size: 12px; }
            .meta > div { border: 1px solid #333; padding: 8px; }
            .meta strong { display: block; font-size: 11px; color: #444; margin-bottom: 4px; }
            table { width: 100%; border-collapse: collapse; font-size: 11px; }
            th, td { border: 1px solid #000; padding: 6px; vertical-align: top; }
            th { background: #f1f1f1; text-align: center; font-weight: 800; }
            .text-center { text-align: center; }
            .text-right { text-align: right; }
            tfoot th { font-size: 12px; }
              .signatories { display: grid; grid-template-columns: repeat(2, 1fr); gap: 90px; margin-top: 72px; }
              .signatory { text-align: center; }
              .signatory-line { border-bottom: 1.5px solid #000; min-height: 24px; font-weight: 700; text-transform: uppercase; padding-bottom: 3px; }
              .signatory-label { margin-top: 7px; font-size: 12px; font-weight: 700; }
              @media print { body { margin: 12mm; } }
            </style>
          </head>
          <body>
            <div class="document-header">
              <img src="/images/logo-sm.png" class="header-logo" alt="DOST Logo">
              <div class="agency">
                <div class="country">Republic of the Philippines</div>
                <div class="department">DEPARTMENT OF SCIENCE AND TECHNOLOGY</div>
                <div class="office">Regional Office IX</div>
              </div>
              <img src="/images/bp-logo.webp" class="header-logo" alt="Bagong Pilipinas Logo">
            </div>
            <div class="title">
              PROJECT PROCUREMENT MANAGEMENT PLAN (PPMP) NO.
              <span class="line">${this.escapeHtml(data.ppmp_no || "")}</span>
            </div>
            <div class="checks">
              <div><span class="box ${data.is_final ? "" : "checked"}"></span>INDICATIVE</div>
              <div><span class="box ${data.is_final ? "checked" : ""}"></span>FINAL</div>
            </div>
            <div class="meta">
              <div><strong>Plan</strong>${this.escapeHtml(data.plan_name || "PPMP")}</div>
              <div><strong>Unit</strong>${this.escapeHtml(data.unit?.name || "-")}</div>
              <div><strong>Total ABC</strong>${this.escapeHtml(this.formatCurrency(data.estimated_budget))}</div>
            </div>
            <table>
              <thead>
                <tr>
                  <th style="width: 4%">#</th>
                  <th>Item</th>
                  <th>Description</th>
                  <th style="width: 10%">Qty</th>
                  <th style="width: 10%">Unit</th>
                  <th style="width: 14%">Unit Price</th>
                  <th style="width: 14%">ABC</th>
                </tr>
              </thead>
              <tbody>
                ${rows || '<tr><td colspan="7" class="text-center">No items found.</td></tr>'}
              </tbody>
              <tfoot>
                <tr>
                  <th colspan="6" class="text-right">Total ABC</th>
                  <th class="text-right">${this.escapeHtml(this.formatCurrency(data.estimated_budget))}</th>
                </tr>
              </tfoot>
            </table>
            <div class="signatories">
              <div class="signatory">
                <div class="signatory-line">${this.escapeHtml(data.prepared_by || "")}</div>
                <div class="signatory-label">Prepared By</div>
              </div>
              <div class="signatory">
                <div class="signatory-line">${this.escapeHtml(data.submitted_by || "")}</div>
                <div class="signatory-label">Submitted By</div>
              </div>
            </div>
          </body>
        </html>
      `);
      printWindow.document.close();
      printWindow.focus();
      setTimeout(() => printWindow.print(), 300);
    },
    escapeHtml(value) {
      return String(value ?? "")
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
    },
    selectRow(index) {
      this.selectedRow = this.selectedRow == index ? null : index;
    },
    refresh() {
      this.filter.keyword = null;
      this.filter.status = null;
      this.filter.unit = null;
      this.filter.sort = "latest";
      this.fetch();
    },
    setPlanTab(planType) {
      if (this.filter.plan_type === planType) {
        return;
      }

      this.filter.plan_type = planType;
      this.selectedRow = null;
      this.fetch();
    },
    openCreatePpmpModal() {
      const currentYear = new Date().getFullYear();

      this.createPpmpForm.clearErrors();
      this.createPpmpForm.unit_id = this.defaultUserUnitId;
      this.createPpmpForm.year = currentYear;
      this.createPpmpModal.show = true;
      this.fetchAvailablePpmpUnits();
    },
    closeCreatePpmpModal() {
      this.createPpmpModal.show = false;
      this.createPpmpForm.clearErrors();
    },
    openCreateAppModal() {
      this.createAppForm.clearErrors();
      const currentYear = new Date().getFullYear();
      const currentYearAvailable = this.availableAppYearOptions.some((year) => Number(year.value) === currentYear);
      this.createAppForm.year = currentYearAvailable
        ? currentYear
        : (this.availableAppYearOptions[0]?.value || null);
      this.createAppForm.plan_type = "annual";
      this.createAppModal.show = true;
    },
    closeCreateAppModal() {
      this.createAppModal.show = false;
      this.createAppForm.clearErrors();
    },
    openCreateSppModal() {
      this.createSppForm.clearErrors();
      this.createSppForm.year = this.currentYear;
      this.createSppForm.plan_type = "supplemental";
      this.createSppForm.unit_id = this.defaultUnitExistsInOptions() ? this.defaultUserUnitId : null;
      this.createSppForm.item_name = "";
      this.createSppForm.item_description = "";
      this.createSppForm.project_type = "";
      this.createSppForm.item_category_id = null;
      this.createSppForm.item_category = "";
      this.createSppForm.recommended_mode_of_procurement = "";
      this.createSppForm.pre_procurement_conference = "";
      this.createSppForm.item_quantity = null;
      this.createSppForm.item_unit_type_id = null;
      this.createSppForm.item_unit_cost = null;
      this.createSppForm.end_of_procurement_activity = null;
      this.createSppForm.expected_delivery_date = null;
      this.createSppForm.attached_supporting_documents = "";
      this.createSppForm.supporting_document_file = null;
      this.createSppForm.remarks = "";
      this.createSppModal.show = true;
    },
    defaultUnitExistsInOptions(options = this.unitOptions) {
      return Boolean(
        this.defaultUserUnitId &&
        options.some((unit) => Number(unit.value) === this.defaultUserUnitId)
      );
    },
    closeCreateSppModal() {
      this.createSppModal.show = false;
      this.createSppForm.clearErrors();
    },
    fetchAvailablePpmpUnits() {
      this.createPpmpModal.loading = true;

      axios
        .get("/faims/procurement-ppmp", {
          params: {
            option: "available_units",
            year: this.createPpmpForm.year,
          },
        })
        .then((response) => {
          this.availablePpmpUnits = Array.isArray(response.data) ? response.data : [];
          if (!this.createPpmpForm.unit_id && this.defaultUnitExistsInOptions(this.availablePpmpUnits)) {
            this.createPpmpForm.unit_id = this.defaultUserUnitId;
          }
          if (!this.availablePpmpUnits.some((unit) => Number(unit.value) === Number(this.createPpmpForm.unit_id))) {
            this.createPpmpForm.unit_id = null;
          }
        })
        .catch((error) => {
          console.log(error);
          this.availablePpmpUnits = [];
        })
        .finally(() => {
          this.createPpmpModal.loading = false;
        });
    },
    submitCreatePpmp() {
      this.createPpmpForm.option = "create_unit_ppmp";

      this.createPpmpForm.post("/faims/procurement-ppmp", {
        preserveScroll: true,
        onSuccess: () => {
          this.closeCreatePpmpModal();
          this.filter.unit = null;
          this.filter.plan_type = "all";
          this.fetch();
        },
      });
    },
    submitCreateApp() {
      if (!this.createAppForm.year || this.appYearHasExisting) {
        this.createAppForm.setError('year', 'An APP already exists for the selected year.');
        return;
      }

      this.createAppForm.plan_type = "annual";

      this.createAppForm.post("/faims/procurement-ppmp", {
        preserveScroll: true,
        onSuccess: () => {
          this.closeCreateAppModal();
          this.filter.plan_type = "annual";
          this.filter.unit = null;
          this.fetch();
        },
      });
    },
    submitCreateSpp() {
      this.createSppForm.year = this.currentYear;
      this.createSppForm.plan_type = "supplemental";

      this.createSppForm.post("/faims/procurement-ppmp", {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
          this.closeCreateSppModal();
          this.filter.plan_type = "supplemental";
          this.filter.unit = null;
          this.fetch();
        },
      });
    },
  },
};
</script>

<style scoped>
.ppmp-index-page {
  --ppmp-bg: #f3f6ff;
  --ppmp-card: #ffffff;
  --ppmp-card-soft: #f8fafc;
  --ppmp-header: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
  --ppmp-border: rgba(91, 105, 153, .14);
  --ppmp-ink: #182039;
  --ppmp-muted: #6f7895;
  --ppmp-input: #ffffff;
  --ppmp-hover: rgba(64, 81, 137, .06);
  --ppmp-shadow: rgba(31, 45, 92, .08);
  padding: .5rem;
  background: var(--ppmp-bg);
}

.ppmp-shell {
  overflow: hidden;
  border-color: var(--ppmp-border) !important;
  border-radius: 16px;
  background: var(--ppmp-card);
  color: var(--ppmp-ink);
  box-shadow: 0 16px 34px var(--ppmp-shadow) !important;
}

.ppmp-shell__header,
.ppmp-filter-panel,
.ppmp-table-panel {
  background: var(--ppmp-card) !important;
  color: var(--ppmp-ink);
}

.ppmp-shell__header {
  background: var(--ppmp-header) !important;
  border-bottom-color: var(--ppmp-border);
}

.ppmp-filter-panel {
  padding: .75rem .75rem .55rem;
  border-color: var(--ppmp-border) !important;
}

.ppmp-table-panel {
  padding: .65rem;
}

.ppmp-list-card {
  border: 0;
  background: var(--ppmp-card);
}

.ppmp-list-scroll {
  min-height: 360px;
  max-height: calc(100vh - 306px);
  overflow: auto;
  border: 1px solid var(--ppmp-border);
  border-radius: 12px;
  background: var(--ppmp-card);
}

.ppmp-index-page .input-group-text,
.ppmp-index-page .form-control,
.ppmp-index-page .form-select,
.ppmp-index-page :deep(.multiselect) {
  border-color: var(--ppmp-border);
  background: var(--ppmp-input);
  color: var(--ppmp-ink);
}

.ppmp-index-page .nav-tabs-custom .nav-link {
  color: var(--ppmp-muted);
}

.ppmp-index-page .nav-tabs-custom .nav-link.active {
  color: #405189;
  background: var(--ppmp-card);
}

.ppmp-table {
  --bs-table-bg: transparent;
  --bs-table-color: var(--ppmp-ink);
  --bs-table-hover-bg: var(--ppmp-hover);
  --bs-table-hover-color: var(--ppmp-ink);
  color: var(--ppmp-ink);
}

.ppmp-table > :not(caption) > * > * {
  padding: .48rem .55rem;
}

.ppmp-table thead th {
  position: sticky;
  top: 0;
  z-index: 2;
  background: var(--ppmp-card-soft);
  color: var(--ppmp-muted);
  border-color: var(--ppmp-border);
}

.ppmp-table tbody td {
  border-color: var(--ppmp-border);
}

.ppmp-index-page .card-footer {
  background: var(--ppmp-card);
  border-color: var(--ppmp-border);
}

.ppmp-index-page .btn-icon {
  width: 30px;
  height: 30px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0;
}

.ppmp-action-group {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: .28rem;
  white-space: nowrap;
}

.ppmp-confirm {
  display: grid;
  grid-template-columns: 44px minmax(0, 1fr);
  gap: .85rem;
  color: var(--ppmp-ink);
}

.ppmp-confirm__icon {
  width: 44px;
  height: 44px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 14px;
  background: rgba(10, 179, 156, .14);
  color: #0ab39c;
  font-size: 1.35rem;
}

.ppmp-confirm__summary {
  grid-column: 1 / -1;
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: .5rem;
}

.ppmp-confirm__summary > div {
  padding: .65rem;
  border: 1px solid var(--ppmp-border);
  border-radius: 12px;
  background: var(--ppmp-card-soft);
}

.ppmp-confirm__summary span {
  display: block;
  color: var(--ppmp-muted);
  font-size: .72rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .04em;
}

.ppmp-confirm__summary strong {
  display: block;
  margin-top: .18rem;
  color: var(--ppmp-ink);
  font-size: .9rem;
  line-height: 1.25;
}

@media (max-width: 1199.98px) {
  .ppmp-index-page .input-group {
    display: grid;
    grid-template-columns: 42px minmax(220px, 1fr) repeat(3, minmax(150px, .55fr)) auto auto;
    gap: .35rem;
  }

  .ppmp-index-page .input-group > * {
    width: 100% !important;
    border-radius: .375rem !important;
  }
}

@media (max-width: 767.98px) {
  .ppmp-index-page .input-group {
    grid-template-columns: 1fr;
  }

  .ppmp-list-scroll {
    max-height: none;
  }

  .ppmp-confirm,
  .ppmp-confirm__summary {
    grid-template-columns: 1fr;
  }
}

:global([data-bs-theme="dark"]) .ppmp-index-page {
  --ppmp-bg: #0b1220;
  --ppmp-card: #151e33;
  --ppmp-card-soft: #10192c;
  --ppmp-header: linear-gradient(180deg, #172136 0%, #121b30 100%);
  --ppmp-border: rgba(170, 184, 220, .16);
  --ppmp-ink: #e8edf9;
  --ppmp-muted: #9aa8c7;
  --ppmp-input: #0f1728;
  --ppmp-hover: rgba(142, 164, 255, .1);
  --ppmp-shadow: rgba(0, 0, 0, .24);
}

:global([data-bs-theme="dark"]) .ppmp-index-page .card,
:global([data-bs-theme="dark"]) .ppmp-index-page .card-body,
:global([data-bs-theme="dark"]) .ppmp-index-page .card-header,
:global([data-bs-theme="dark"]) .ppmp-index-page .table-responsive,
:global([data-bs-theme="dark"]) .ppmp-index-page .table-card {
  background: var(--ppmp-card) !important;
  color: var(--ppmp-ink);
  border-color: var(--ppmp-border) !important;
}

:global([data-bs-theme="dark"]) .ppmp-index-page .bg-white,
:global([data-bs-theme="dark"]) .ppmp-index-page .bg-light,
:global([data-bs-theme="dark"]) .ppmp-index-page .bg-light-subtle,
:global([data-bs-theme="dark"]) .ppmp-index-page .table-light {
  background-color: var(--ppmp-card-soft) !important;
  color: var(--ppmp-ink) !important;
}

:global([data-bs-theme="dark"]) .ppmp-index-page .text-body,
:global([data-bs-theme="dark"]) .ppmp-index-page .text-primary {
  color: var(--ppmp-ink) !important;
}

:global([data-bs-theme="dark"]) .ppmp-index-page .text-muted {
  color: var(--ppmp-muted) !important;
}

:global([data-bs-theme="dark"]) .ppmp-index-page .table,
:global([data-bs-theme="dark"]) .ppmp-index-page .table > :not(caption) > * > * {
  --bs-table-bg: transparent;
  --bs-table-color: var(--ppmp-ink);
  --bs-table-hover-bg: var(--ppmp-hover);
  --bs-table-hover-color: var(--ppmp-ink);
  color: var(--ppmp-ink);
  border-color: var(--ppmp-border);
}

:global([data-bs-theme="dark"]) .ppmp-index-page .table-active,
:global([data-bs-theme="dark"]) .ppmp-index-page .table-active > * {
  --bs-table-bg-state: rgba(142, 164, 255, .14);
  color: var(--ppmp-ink);
}

:global([data-bs-theme="dark"]) .ppmp-index-page .input-group-text,
:global([data-bs-theme="dark"]) .ppmp-index-page .form-control,
:global([data-bs-theme="dark"]) .ppmp-index-page .form-select,
:global([data-bs-theme="dark"]) .ppmp-index-page :deep(.multiselect),
:global([data-bs-theme="dark"]) .ppmp-index-page :deep(.multiselect-dropdown),
:global([data-bs-theme="dark"]) .ppmp-index-page :deep(.multiselect-options),
:global([data-bs-theme="dark"]) .ppmp-index-page :deep(.multiselect-search),
:global([data-bs-theme="dark"]) .ppmp-index-page :deep(.multiselect-single-label) {
  background: var(--ppmp-input) !important;
  border-color: var(--ppmp-border) !important;
  color: var(--ppmp-ink) !important;
}

.ppmp-document-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-bottom: 2px solid #111;
  padding-bottom: 12px;
  margin-bottom: 16px;
}

.ppmp-header-logo {
  width: 82px;
  height: 82px;
  object-fit: contain;
}

.ppmp-title-block {
  text-align: center;
  margin-bottom: 16px;
}

.ppmp-line {
  display: inline-block;
  min-width: 86px;
  border-bottom: 3px solid #111;
}

.ppmp-status-check {
  display: inline-flex;
  align-items: center;
  font-size: 20px;
  font-weight: 800;
}

.ppmp-checkbox {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border: 1.5px solid #111;
  margin-right: 12px;
}

.ppmp-checkbox.checked::after {
  content: "✓";
  font-size: 24px;
  line-height: 1;
}

.ppmp-signatories {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 72px;
  margin-top: 64px;
}

.ppmp-signatory {
  text-align: center;
}

.ppmp-signatory-line {
  min-height: 26px;
  border-bottom: 1.5px solid #111;
  padding-bottom: 4px;
  font-weight: 700;
  text-transform: uppercase;
}

.ppmp-signatory-label {
  margin-top: 7px;
  font-size: 12px;
  font-weight: 700;
}

</style>
