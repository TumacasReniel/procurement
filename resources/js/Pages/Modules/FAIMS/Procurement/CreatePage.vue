<template>
  <div class="procurement-create-container">
    <!-- Hero Header Section -->
    <div class="hero-header mb-3">
      <div class="hero-gradient">
        <div class="container-fluid">
          <div class="row align-items-center">
            <div class="col-lg-8">
              <div class="hero-content">
                <div class="d-flex align-items-center mb-2">
                  <div class="hero-icon-wrapper me-3">
                    <i class="ri-file-2-line hero-icon"></i>
                  </div>
                  <div>
                    <h1 class="hero-title mb-1">
                      {{ option === 'create' ? 'Create Purchase Request' :
                         option === 'edit' ? 'Edit Purchase Request' :
                         option === 'review' ? 'Review Purchase Request' :
                         option === 'approve' ? 'Approve Purchase Request' :
                         'View Procurement Request' }}
                    </h1>
                    <p class="hero-subtitle mb-0">Manage purchase request details and items</p>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-lg-4 text-end">
              <div class="status-display">
                <div class="status-badge-wrapper">
                  <b-badge
                    :class="getStatusBadgeClass()"
                    style="font-size: 1rem; padding: 0.5rem 1rem;"
                  >
                    <i :class="getStatusIcon()" class="me-2"></i>
                    {{ getStatusText() }}
                  </b-badge>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div>
      <div class="row d-flex">
        <div class="col-12" style="transition: all 0.3s ease; height: 100%; overflow: hidden;">

            <form class="customform">
              <!-- Basic Information Section -->
              <div class="row g-3 mb-3">
                <div class="col-12">
                  <div class="content-card request-details-card">
                    <div class="card-body-custom request-details-body">
                      <div class="row g-3 align-items-stretch">
                        <div class="col-xxl-8 col-xl-7">
                          <div class="row g-3">
                            <div class="col-lg-6">
                              <div class="form-group compact-form-group">
                                <InputLabel for="division" value="Division" :message="form.errors.division_id" />
                                <Multiselect
                                  :options="dropdowns.divisions"
                                  v-model="form.division_id"
                                  :searchable="true"
                                  label="name"
                                  placeholder="Select Division"
                                  class="modern-select"
                                />
                              </div>
                            </div>

                            <div class="col-lg-6">
                              <div class="form-group compact-form-group">
                                <InputLabel value="PR Date" :message="form.errors.date" />
                                <TextInput
                                  v-model="form.date"
                                  type="date"
                                  class="form-control modern-input"
                                />
                              </div>
                            </div>

                            <div class="col-lg-6">
                              <div class="form-group compact-form-group">
                                <InputLabel for="unit" value="Unit" :message="form.errors.unit_id" />
                                <Multiselect
                                  :options="units"
                                  v-model="form.unit_id"
                                  :searchable="true"
                                  label="name"
                                  placeholder="Select Unit"
                                  class="modern-select"
                                />
                              </div>
                            </div>

                            <div class="col-lg-6">
                              <div class="form-group compact-form-group">
                                <InputLabel for="fund_cluster" value="Fund Cluster" :message="form.errors.fund_cluster_id" />
                                <Multiselect
                                  :options="dropdowns.fund_clusters"
                                  v-model="form.fund_cluster_id"
                                  :searchable="true"
                                  label="name"
                                  placeholder="Select Fund Cluster"
                                  class="modern-select"
                                />
                              </div>
                            </div>

                            <div class="col-12">
                              <div class="form-group compact-form-group">
                                <InputLabel for="Mode Procurement " value="Procurement Codes" :message="form.errors.procurement_code_ids" />
                                <Multiselect
                                  :options="availableProcurementCodes"
                                  v-model="form.procurement_code_ids"
                                  :searchable="true"
                                  label="label"
                                  placeholder="Select PAP Codes"
                                  mode="tags"
                                  class="modern-select"
                                />
                                <small
                                  v-if="form.errors.procurement_code_ids || procurementCodeBudgetErrorMessage"
                                  class="text-danger d-block mt-2 fs-5 fw-bold"
                                >
                                  {{ form.errors.procurement_code_ids || procurementCodeBudgetErrorMessage }}
                                </small>
                              </div>
                            </div>

                            <div
                              v-if="showClassificationField"
                              class="col-lg-6"
                            >
                              <div class="form-group compact-form-group">
                                <InputLabel
                                  for="classification"
                                  value="Classification"
                                  :message="form.errors.classification_id"
                                />
                                <Multiselect
                                  :options="classificationOptions"
                                  v-model="form.classification_id"
                                  :searchable="true"
                                  label="name"
                                  placeholder="Select Classification"
                                  class="modern-select"
                                />
                              </div>
                            </div>

                            <div
                              v-if="showReferenceAppField"
                              class="col-lg-6"
                            >
                              <div class="form-group compact-form-group">
                                <InputLabel
                                  for="reference_app"
                                  value="Reference APP"
                                  :message="form.errors.reference_app_id"
                                />
                                <Multiselect
                                  :options="referenceAppOptions"
                                  v-model="form.reference_app_id"
                                  :searchable="true"
                                  label="name"
                                  placeholder="Select Reference APP"
                                  class="modern-select"
                                />
                              </div>
                            </div>
                          </div>
                        </div>

                        <div class="col-xxl-4 col-xl-5">
                          <div class="form-group compact-form-group h-100">
                            <InputLabel for="purpose" value="Request Purpose" :message="form.errors.purpose" />
                            <b-form-textarea
                              id="purpose"
                              v-model="form.purpose"
                              placeholder="Enter your request purpose"
                              rows="7"
                              max-rows="10"
                              class="modern-textarea request-purpose-textarea"
                            ></b-form-textarea>
                          </div>
                        </div>

                        <div class="col-12" v-if="option == 'review' || option == 'approve'">
                          <div class="form-group compact-form-group mb-0">
                            <InputLabel for="title" value="Request Title" :message="form.errors.title" />
                            <b-form-textarea
                              id="title"
                              v-model="form.title"
                              placeholder="Enter request title"
                              rows="2"
                              max-rows="4"
                              class="modern-textarea"
                            ></b-form-textarea>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Items Section -->
              <div class="row g-3 mb-3">
                <div class="col-12">
                  <div class="content-card">
                    <div class="card-header-custom">
                      <i class="ri-shopping-bag-line card-header-icon"></i>
                      <h5 class="card-header-title">Procurement Items</h5>
                      <div class="ms-auto">
                        <b-button
                          v-if="canManageRequestDetails"
                          :disabled="!form.division_id || !form.unit_id || !form.fund_cluster_id || !form.purpose"
                          @click="openAddItem()"
                          variant="primary"
                          size="sm"
                          class="add-item-btn"
                        >
                          <i class="ri-add-line me-1"></i>
                          Add Item
                        </b-button>
                      </div>
                    </div>
                    <div class="card-body-custom">
                      <div v-if="form.items && form.items.length > 0" class="items-table-container">
                        <div class="table-responsive">
                          <table class="items-table">
                            <thead>
                              <tr>
                                <th class="text-center">#</th>
                                <th>Unit</th>
                                <th>Name/Description</th>
                                <th class="text-center">Qty</th>
                                <th class="text-end">Unit Cost</th>
                                <th class="text-end">Total</th>
                                <th class="text-center"></th>
                              </tr>
                            </thead>
                            <tbody>
                              <tr v-for="(item, index) in form.items" :key="index" class="item-row">
                                <td class="text-center item-number">{{ index + 1 }}</td>
                                <td class="item-unit">
                                  <span class="unit-badge">
                                    {{
                                      item.item_quantity > 1
                                        ? item.item_unit_type?.[0]?.name_long || item.item_unit_type?.name_long || ""
                                        : item.item_unit_type?.[0]?.name_short || item.item_unit_type?.name_short || ""
                                    }}
                                  </span>
                                </td>
                                <td class="item-description">
                                  <span>  {{ item.item_name || "-" }}</span>
                                  <div v-html="item.item_description"></div>
                                </td>
                                <td class="text-center item-quantity">{{ item.item_quantity }}</td>
                                <td class="text-end item-cost">{{ formatCurrency(item.item_unit_cost) }}</td>
                                <td class="text-end item-total">{{ formatCurrency(item.total_cost) }}</td>
                                <td class="text-center">
                                  <div class="d-flex justify-content-center gap-1">
                                    <b-button
                                      v-if="canManageRequestDetails"
                                      @click="editItem(index)"
                                      variant="success"
                                      size="sm"
                                      class="btn-icon"
                                      v-b-tooltip.hover title="Edit Item"
                                      style="border-radius: 8px;"
                                    >
                                      <i class="ri-edit-2-line"></i>
                                    </b-button>

                                      <b-button
                                        v-if="canManageRequestDetails"
                                      @click="removeItem(index)"
                                      variant="danger"
                                      size="sm"
                                      class="btn-icon"
                                      v-b-tooltip.hover title="Remove Item"
                                      style="border-radius: 8px;"
                                    >
                                      <i class="ri-delete-bin-line"></i>
                                    </b-button>
                                  </div>
                                </td>
                              </tr>
                            </tbody>
                            <tfoot>
                              <tr class="grand-total-row">
                                <td colspan="5" class="text-end grand-total-label">Grand Total:</td>
                                <td class="text-end grand-total-amount">{{ formatCurrency(totalCostSum) }}</td>
                              </tr>
                            </tfoot>
                          </table>
                        </div>
                      </div>
                      <div v-else class="empty-state">
                        <div class="empty-state-icon">
                          <i class="ri-shopping-bag-line"></i>
                        </div>
                        <h6 class="empty-state-title">No Items Added</h6>
                        <p class="empty-state-text">Click "Add Item" to start adding procurement items.</p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Assignees Section -->
              <div class="row g-3 mb-3">
                <div class="col-12">
                  <div class="content-card">
                    <div class="card-header-custom">
                      <i class="ri-user-line card-header-icon"></i>
                      <h5 class="card-header-title">Assignees</h5>
                    </div>
                    <div class="card-body-custom">
                      <div class="row g-3 align-items-end">
                        <div class="col-xl-4 col-lg-6">
                          <div class="form-group compact-form-group">
                            <InputLabel for="requested_by" value="Requested By" :message="form.errors.requested_by_id" />
                            <Multiselect
                              :options="dropdowns.requesters"
                              v-model="form.requested_by_id"
                              :searchable="true"
                              label="name"
                              placeholder="Select Requester"
                              class="modern-select"
                            />
                          </div>
                        </div>
                        <div class="col-xl-4 col-lg-6">
                          <div class="form-group compact-form-group">
                            <InputLabel for="approved_by" value="Approved By" :message="form.errors.approved_by_id" />
                            <Multiselect
                              :options="dropdowns.approvers"
                              v-model="form.approved_by_id"
                              :searchable="true"
                              label="name"
                              placeholder="Select Approver"
                              class="modern-select"
                            />
                          </div>
                        </div>

                         <div class="col-xl-4">
                           <div class="action-buttons-group justify-content-xl-end">
                      <b-button
                        v-if="option == 'create'"
                        :disabled="!canCreateRequest"
                        @click="submit()"
                        variant="primary"
                        size="sm"
                        class="action-btn primary-btn"
                      >
                        <i class="ri-save-line me-2"></i>
                        Save Request
                      </b-button>

                      <b-button
                        v-if="option == 'edit'"
                        @click="update(form)"
                        variant="primary"
                        size="sm"
                        class="action-btn primary-btn"
                      >
                        <i class="ri-edit-line me-2"></i>
                        Update Request
                      </b-button>

                      <b-button
                        v-if="option == 'review'"
                        :disabled="!canReviewRequest"
                        @click="review(form)"
                        variant="success"
                        size="sm"
                        class="action-btn success-btn"
                      >
                        <i class="ri-check-line me-2"></i>
                        Confirm Review
                      </b-button>

                      <b-button
                        v-if="option == 'approve'"
                        @click="approve(form)"
                        variant="success"
                        size="sm"
                        class="action-btn success-btn"
                      >
                        <i class="ri-check-double-line me-2"></i>
                        Approve Request
                      </b-button>

                      <b-button
                        @click="goBackPage()"
                        variant="outline-secondary"
                        size="sm"
                        class="action-btn back-btn"
                      >
                        <i class="ri-arrow-left-line me-2"></i>
                        Back to List
                      </b-button>
                           </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </form>
      
        </div>
      </div>
    </div>

    <RightSidebar
      v-if="option != 'create'"
      :procurement="procurement"
      :logs="logs"
      :isRightCollapsed="isRightCollapsed"
      @toggleRightSidebar="toggleRightSidebar"
    />

    <Item :dropdowns="dropdowns" @refresh="getDataFromLocalStorage()" ref="item" />
  </div>
</template>
<script>
import Item from "./Modals/Item.vue";
import PageHeader from "@/Shared/Components/PageHeader.vue";
import { useForm } from "@inertiajs/vue3";
import Multiselect from "@vueform/multiselect";
import InputError from "@/Shared/Components/Forms/InputError.vue";
import InputLabel from "@/Shared/Components/Forms/InputLabel.vue";
import TextInput from "@/Shared/Components/Forms/TextInput.vue";
import { router } from "@inertiajs/vue3";
import RightSidebar from "./Pages/Components/RightSidebar.vue";

export default {
  components: { PageHeader, InputError, InputLabel, TextInput, Multiselect, Item, RightSidebar },
  props: ["procurement", "dropdowns", "option", "regional_director"],
  data() {
    return {
      currentUrl: window.location.origin,
      form: useForm({
        id: null,
        code: null,
        purpose: null,
        title: null,
        date: this.getCurrentDate(),
        division_id: null,
        unit_id: null,
        fund_cluster_id: null,
        classification_id: null,
        reference_app_id: null,
        items: null,
        requested_by_id: null,
        approved_by_id: null,
        procurement_code_ids: [],
        option: null,
      }),
      action: null,
      showModal: false,
      units: [],
      isRightCollapsed: false,
      isCollapsed: false,
    };
  },

  watch: {
    "form.division_id"(newVal) {
      if (newVal) {
        this.getUnits(newVal);
      }
    },

    "form.procurement_code_ids": function (value) {
      if (this.action == "create") {
        if (Array.isArray(value) && value.length > 0) {
          // Reset the title before adding new ones
          this.form.title = "";

          value.forEach((id) => {
            this.getProcurementTitle(id);
          });
        }
      }
    },

    action: function (value) {
      if (value == "edit" || value == "review" || value == "approve" || value == "view") {
        this.form.id = this.procurement.id;
        this.form.code = this.procurement.code;
        this.form.purpose = this.procurement.purpose;
        this.form.title = this.procurement.title;
        this.form.date = this.procurement.date;
        this.form.division_id = this.procurement.division_id;
        this.form.unit_id = this.procurement.unit_id;
        this.form.fund_cluster_id = this.procurement.fund_cluster_id;
        this.form.classification_id = this.procurement.classification_id;
        this.form.reference_app_id = this.procurement.reference_app_id;
        this.form.procurement_code_ids = this.procurement.codes.map(
          (code) => code.procurement_code_id
        );
        this.form.requested_by_id = this.procurement.requested_by_id;
        this.form.approved_by_id = this.procurement.approved_by_id;
        this.form.items = this.procurement.items;
        // this.getDataFromLocalStorage(); // update items
      }
    },
  },

  computed: {
    canManageRequestDetails() {
      return ["create", "edit", "review"].includes(this.option);
    },
    totalCostSum() {
      if (!Array.isArray(this.form.items)) return 0;

      return this.form.items.reduce((sum, item) => {
        return sum + (parseFloat(item.total_cost) || 0);
      }, 0);
    },
    showClassificationField() {
      return ["review", "approve"].includes(this.option) || Boolean(this.form.classification_id);
    },
    showReferenceAppField() {
      return ["review", "approve"].includes(this.option) || Boolean(this.form.reference_app_id);
    },
    classificationOptions() {
      const options =
        this.dropdowns?.classifications ??
        this.dropdowns?.procurement_classifications ??
        [];

      return Array.isArray(options) ? options : Object.values(options);
    },
    referenceAppOptions() {
      const options =
        this.dropdowns?.reference_apps ??
        [];

      return Array.isArray(options) ? options : Object.values(options);
    },
    normalizedProcurementCodes() {
      const options = Array.isArray(this.dropdowns?.procurement_codes)
        ? this.dropdowns.procurement_codes
        : [];

      return options.map((option) => {
        const remainingBudget = Number(
          option.remaining_budget ?? option.allocated_budget ?? 0
        );
        const baseLabel = option.label || option.code || option.title || "";

        return {
          ...option,
          remaining_budget: remainingBudget,
          label: baseLabel,
        };
      });
    },
    availableProcurementCodes() {
      if (this.option !== "create") {
        return this.normalizedProcurementCodes;
      }

      const selectedIds = new Set(
        Array.isArray(this.form.procurement_code_ids)
          ? this.form.procurement_code_ids.map((id) => Number(id))
          : []
      );

      return this.normalizedProcurementCodes.filter((option) => {
        return selectedIds.has(Number(option.value)) || option.remaining_budget > 0;
      });
    },
    selectedProcurementCodeBalance() {
      if (!Array.isArray(this.form.procurement_code_ids) || this.form.procurement_code_ids.length === 0) {
        return 0;
      }

      const selectedIds = new Set(this.form.procurement_code_ids.map((id) => Number(id)));

      return this.normalizedProcurementCodes.reduce((sum, option) => {
        if (!selectedIds.has(Number(option.value))) {
          return sum;
        }

        return sum + (Number(option.remaining_budget) || 0);
      }, 0);
    },
    selectedProcurementCodeDetails() {
      if (!Array.isArray(this.form.procurement_code_ids) || this.form.procurement_code_ids.length === 0) {
        return [];
      }

      const optionMap = this.normalizedProcurementCodes.reduce((map, option) => {
        map.set(Number(option.value), option);
        return map;
      }, new Map());

      return this.form.procurement_code_ids
        .map((id) => optionMap.get(Number(id)))
        .filter(Boolean);
    },
    procurementCodeBudgetGap() {
      return this.selectedProcurementCodeBalance - this.totalCostSum;
    },
    procurementCodeBudgetStatusClass() {
      if (!Array.isArray(this.form.procurement_code_ids) || this.form.procurement_code_ids.length === 0) {
        return "pap-budget-overview--idle";
      }

      if (this.totalCostSum <= 0) {
        return "pap-budget-overview--idle";
      }

      return this.hasEnoughSelectedProcurementCodeBalance
        ? "pap-budget-overview--covered"
        : "pap-budget-overview--short";
    },
    hasEnoughSelectedProcurementCodeBalance() {
      if (this.option !== "create") {
        return true;
      }

      if (!Array.isArray(this.form.procurement_code_ids) || this.form.procurement_code_ids.length === 0) {
        return true;
      }

      if (this.totalCostSum <= 0) {
        return true;
      }

      return this.selectedProcurementCodeBalance + 0.009 >= this.totalCostSum;
    },
    procurementCodeBalanceHelper() {
      if (this.option !== "create") {
        return null;
      }

      if (this.totalCostSum <= 0) {
        return "Only PAP codes with available remaining balance are shown.";
      }

      if (!Array.isArray(this.form.procurement_code_ids) || this.form.procurement_code_ids.length === 0) {
        return `Select PAP code(s) with enough combined remaining balance for the current total of ${this.formatCurrency(this.totalCostSum)}.`;
      }

      if (!this.hasEnoughSelectedProcurementCodeBalance) {
        return `Selected PAP codes only cover ${this.formatCurrency(this.selectedProcurementCodeBalance)} of the ${this.formatCurrency(this.totalCostSum)} request total.`;
      }

      return `Selected PAP codes cover ${this.formatCurrency(this.selectedProcurementCodeBalance)} for the current total of ${this.formatCurrency(this.totalCostSum)}.`;
    },
    procurementCodeBalanceHelperClass() {
      return this.hasEnoughSelectedProcurementCodeBalance ? "text-muted" : "text-danger";
    },
    procurementCodeBudgetErrorMessage() {
      if (this.option !== "create") {
        return null;
      }

      if (!Array.isArray(this.form.procurement_code_ids) || this.form.procurement_code_ids.length === 0) {
        return null;
      }

      if (this.totalCostSum <= 0 || this.hasEnoughSelectedProcurementCodeBalance) {
        return null;
      }

      return `The selected PAP codes only have ${this.formatCurrency(this.selectedProcurementCodeBalance)} remaining, which is not enough for the request total of ${this.formatCurrency(this.totalCostSum)}. You cannot create this procurement request until the selected balance is enough.`;
    },
    canReviewRequest() {
      return Boolean(this.form.classification_id  && this.form.reference_app_id );
    },
    canCreateRequest() {
      return this.isFormValid && this.hasEnoughSelectedProcurementCodeBalance;
    },

    isFormValid() {
      return this.form.division_id &&
             this.form.unit_id &&
             this.form.fund_cluster_id &&
             this.form.purpose &&
             this.form.requested_by_id &&
             this.form.approved_by_id &&
             this.form.items &&
             this.form.items.length > 0;
    },
  },

  mounted() {
    // Load from localStorage on component mount
    this.getDataFromLocalStorage();

    this.action = this.option;
    if (this.option === 'create' && this.dropdowns.regional_director) {
      this.form.approved_by_id = this.dropdowns.regional_director.value;
 
    }
    try {
      this.isRightCollapsed = JSON.parse(localStorage.getItem("isRightCollapsed")) ?? true;
    } catch (e) {
      this.isRightCollapsed = true;
      localStorage.setItem("isRightCollapsed", JSON.stringify(true));
    }
  },
  

  methods: {

    getStatusBadgeClass() {
      if (this.option === 'create') return 'bg-primary';
      if (this.option === 'edit') return 'bg-warning';
      if (this.option === 'review') return 'bg-info';
      if (this.option === 'approve') return 'bg-success';
      return 'bg-secondary';
    },

    getStatusIcon() {
      if (this.option === 'create') return 'ri-add-circle-line';
      if (this.option === 'edit') return 'ri-edit-line';
      if (this.option === 'review') return 'ri-eye-line';
      if (this.option === 'approve') return 'ri-check-double-line';
      return 'ri-file-line';
    },

    getStatusText() {
      if (this.option === 'create') return 'Creating';
      if (this.option === 'edit') return 'Editing';
      if (this.option === 'review') return 'Reviewing';
      if (this.option === 'approve') return 'Approving';
      return 'Viewing';
    },

    toggleRightSidebar() {
      this.isRightCollapsed = !this.isRightCollapsed;
      localStorage.setItem("isRightCollapsed", this.isRightCollapsed);
    },

    openAddItem() {
      this.$refs.item.show();
    },

    openProcurementCodeProfile(id) {
      if (!id) {
        return;
      }

      window.open(`/faims/procurement-codes/${id}`, "_blank", "noopener");
    },

    editItem(index) {
      this.$refs.item.edit(this.form.items[index], index);
    },

    removeItem(index) {
      // Get the current items
      let items = JSON.parse(localStorage.getItem("itemsAdded")) || [];

      // Remove 1 item at that index
      if (index >= 0 && index < items.length) {
        items.splice(index, 1);
      }

      // Save the updated array back to localStorage
      localStorage.setItem("itemsAdded", JSON.stringify(items));

      // Update your form items immediately
      this.form.items = items;
    },

    submit() {
      if (!this.canCreateRequest) {
        return;
      }

      this.form.post("/faims/procurements", {
        onSuccess: () => {
          localStorage.removeItem("itemsAdded");
        },
        onError: (errors) => {
          console.error("Submission failed:", errors);
        },
      });
    },

    update(data) {
      this.form.option = this.action;
      this.form.put(`/faims/procurements/${data.id}`, {
        onSuccess: () => {
          localStorage.removeItem("itemsAdded");
          this.form.reset();
        },
        onError: (errors) => {
          console.error("Update failed:", errors);
        },
      });
    },

    review(data) {
      this.form.option = this.action;
      this.form.put("/faims/procurements/" + data.id);
      this.form.reset();
    },

    approve(data) {
      this.form.option = this.action;
      this.form.put("/faims/procurements/" + data.id);
      this.form.reset();
    },

    goBackPage() {
      router.get("/faims/procurements");
    },

    getDataFromLocalStorage() {
      // Get existing items from localStorage
      let storedItems = [];
      try {
        storedItems = JSON.parse(localStorage.getItem("itemsAdded")) || [];
      } catch (e) {
        storedItems = [];
        localStorage.setItem("itemsAdded", JSON.stringify([]));
      }

      // If form.items is not set yet, initialize it
      if (!Array.isArray(this.form.items)) {
        this.form.items = [];
      }

      // Merge locally stored ones with DB (form.items), giving priority to stored items
      const combined = [...storedItems, ...this.form.items];

      // Remove duplicates based on item id
      const uniqueItems = combined.filter(
        (item, index, self) => index === self.findIndex((t) => t.id === item.id)
      );

      // Update both localStorage and the form
      this.form.items = uniqueItems;
      localStorage.setItem("itemsAdded", JSON.stringify(uniqueItems));
    },

    getCurrentDate() {
      const today = new Date();
      const year = today.getFullYear();
      const month = String(today.getMonth() + 1).padStart(2, "0"); // Months are zero-based
      const day = String(today.getDate()).padStart(2, "0");
      return `${year}-${month}-${day}`;
    },

    formatCurrency(value) {
      return new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
      }).format(value);
    },

    getProcurementCodeBalanceClass(value) {
      return Number(value) < 0 ? "text-danger" : "text-success";
    },

    getUnits(division_id) {
      axios
        .get("/faims/procurements/create", {
          params: {
            code: division_id,
            option: "units",
          },
        })
        .then((response) => {
          if (response) {
            this.units = response.data;
          }
        })
        .catch((err) => console.log(err));
    },

    getProcurementTitle(id) {
      axios
        .get("/faims/procurements/create", {
          params: {
            id: id,
            option: "title",
          },
        })
        .then((response) => {
          if (response) {
            if (this.form.title) {
              this.form.title += ", " + response.data;
            } else {
              this.form.title = response.data;
            }
          }
        })
        .catch((err) => console.log(err));
    },
  },
};
</script>

<style scoped>
/* Container */
.procurement-create-container {
  --procurement-create-page-bg: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  --procurement-create-content-bg: #ffffff;
  --procurement-create-card-bg: #ffffff;
  --procurement-create-card-border: rgba(255, 255, 255, 0.8);
  --procurement-create-card-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
  --procurement-create-card-hover-shadow: 0 15px 35px rgba(0, 0, 0, 0.12);
  --procurement-create-text: #2c3e50;
  --procurement-create-muted: #64748b;
  --procurement-create-header-bg: linear-gradient(135deg, #f8f9fa, #e9ecef);
  --procurement-create-header-border: rgba(0, 0, 0, 0.05);
  --procurement-create-input-bg: #ffffff;
  --procurement-create-input-border: #ced4da;
  --procurement-create-input-text: #334155;
  --procurement-create-placeholder: #94a3b8;
  --procurement-create-table-bg: #ffffff;
  --procurement-create-table-row-alt: #f8fafc;
  --procurement-create-table-row-hover: #eef2ff;
  --procurement-create-table-border: rgba(0, 0, 0, 0.05);
  --procurement-create-unit-badge-bg: linear-gradient(135deg, #e9ecef, #dee2e6);
  --procurement-create-unit-badge-text: #495057;
  --procurement-create-empty-icon-bg: linear-gradient(135deg, #f8f9fa, #e9ecef);
  --procurement-create-empty-icon-text: #6c757d;
  --procurement-create-action-surface-bg: linear-gradient(135deg, #f8f9fa, #e9ecef);
  --procurement-create-back-btn-bg: transparent;
  --procurement-create-back-btn-border: #6c757d;
  --procurement-create-back-btn-text: #6c757d;
  --procurement-create-back-btn-hover-bg: #6c757d;
  --procurement-create-back-btn-hover-text: #ffffff;
  background: var(--procurement-create-page-bg);
  min-height: 100vh;
  padding: 0 0 1rem 0;
}

/* Hero Header */
.hero-header {
  position: relative;
  overflow: hidden;
  border-radius: 14px;
  box-shadow: 0 8px 22px rgba(0, 0, 0, 0.09);
  margin-bottom: 1rem;
}

.hero-gradient {
  background: #4c5f98;
  padding: 0.45rem 0;
  position: relative;
}

.hero-gradient::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="75" cy="75" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="50" cy="10" r="0.5" fill="rgba(255,255,255,0.1)"/><circle cx="90" cy="40" r="0.5" fill="rgba(255,255,255,0.1)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
  opacity: 0.3;
}

.hero-icon-wrapper {
  width: 58px;
  height: 58px;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  backdrop-filter: blur(10px);
  border: 2px solid rgba(255, 255, 255, 0.3);
}

.hero-icon {
  font-size: 1.9rem;
  color: white;
}

.hero-title {
  font-size: 1.35rem;
  font-weight: 700;
  color: white;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
  margin-bottom: 0.5rem;
}

.hero-subtitle {
  font-size: 0.95rem;
  color: rgba(255, 255, 255, 0.9);
  font-weight: 400;
}

.status-badge-main {
  border-radius: 25px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
}

/* Content Wrapper */
.content-wrapper {
  background: var(--procurement-create-content-bg);
  border-radius: 15px;
  padding: 2rem;
  box-shadow: var(--procurement-create-card-shadow);
  margin-bottom: 2rem;
  color: var(--procurement-create-text);
}

/* Content Cards */
.content-card {
  background: var(--procurement-create-card-bg);
  border-radius: 12px;
  box-shadow: var(--procurement-create-card-shadow);
  border: 1px solid var(--procurement-create-card-border);
  overflow: visible;
  transition: all 0.3s ease;
  color: var(--procurement-create-text);
}

.content-card:hover {
  transform: translateY(-1px);
  box-shadow: var(--procurement-create-card-hover-shadow);
}

.request-details-card {
  min-height: auto;
}

.request-details-body {
  padding: 1.25rem !important;
}

.request-purpose-textarea {
  min-height: 168px;
  height: calc(100% - 1.55rem);
}

.classification-helper-text {
  display: inline-block;
  margin-top: 0.45rem;
  color: var(--procurement-create-muted);
  font-size: 0.76rem;
  line-height: 1.5;
}

.pap-profile-panel {
  border: 1px solid rgba(102, 126, 234, 0.14);
  border-radius: 14px;
  padding: 1rem;
  background: linear-gradient(180deg, rgba(248, 250, 252, 0.95), rgba(255, 255, 255, 0.98));
}

.pap-profile-panel__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 0.75rem;
  margin-bottom: 0.85rem;
}

.pap-profile-panel__subtitle {
  color: var(--procurement-create-muted);
  font-size: 0.8rem;
  line-height: 1.5;
}

.pap-profile-panel__hint {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.5rem 0.75rem;
  border-radius: 999px;
  background: rgba(102, 126, 234, 0.08);
  color: #4c63d2;
  font-size: 0.75rem;
  font-weight: 600;
  white-space: nowrap;
}

.pap-budget-overview {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 0.75rem;
  margin-top: 0.85rem;
}

.pap-budget-overview__card {
  border-radius: 12px;
  padding: 0.9rem 1rem;
  border: 1px solid rgba(148, 163, 184, 0.18);
  background: rgba(255, 255, 255, 0.92);
  box-shadow: 0 10px 20px rgba(15, 23, 42, 0.04);
}

.pap-budget-overview__label {
  display: block;
  margin-bottom: 0.35rem;
  color: var(--procurement-create-muted);
  font-size: 0.74rem;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.pap-budget-overview__value {
  display: block;
  color: var(--procurement-create-text);
  font-size: 1rem;
  font-weight: 700;
}

.pap-budget-overview--covered .pap-budget-overview__card {
  border-color: rgba(34, 197, 94, 0.16);
}

.pap-budget-overview--short .pap-budget-overview__card {
  border-color: rgba(239, 68, 68, 0.16);
}

.pap-selected-code-list {
  display: grid;
  gap: 0.75rem;
  margin-top: 0.85rem;
}

.pap-selected-code {
  width: 100%;
  padding: 0.95rem 1rem;
  border-radius: 12px;
  border: 1px solid rgba(102, 126, 234, 0.16);
  background: rgba(255, 255, 255, 0.96);
  text-align: left;
  transition: all 0.2s ease;
}

.pap-selected-code:hover {
  transform: translateY(-1px);
  border-color: rgba(102, 126, 234, 0.3);
  box-shadow: 0 14px 28px rgba(102, 126, 234, 0.08);
}

.pap-selected-code__top,
.pap-selected-code__bottom {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
}

.pap-selected-code__bottom {
  margin-top: 0.45rem;
}

.pap-selected-code__code {
  display: inline-flex;
  align-items: center;
  padding: 0.25rem 0.6rem;
  border-radius: 999px;
  background: rgba(34, 197, 94, 0.12);
  color: #15803d;
  font-size: 0.78rem;
  font-weight: 700;
  letter-spacing: 0.04em;
}

.pap-selected-code__balance {
  font-size: 0.82rem;
  font-weight: 700;
  white-space: nowrap;
}

.pap-selected-code__title {
  color: var(--procurement-create-text);
  font-size: 0.88rem;
  font-weight: 600;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.pap-selected-code__action {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  color: #4c63d2;
  font-size: 0.78rem;
  font-weight: 700;
  white-space: nowrap;
}

.card-header-custom {
  background: var(--procurement-create-header-bg);
  padding: 0.8rem 1rem;
  border-bottom: 1px solid var(--procurement-create-header-border);
  display: flex;
  align-items: center;
  gap: 1rem;
}

.card-header-icon {
  font-size: 1.2rem;
  color: #667eea;
  background: rgba(102, 126, 234, 0.1);
  padding: 0.45rem;
  border-radius: 10px;
}

.card-header-title {
  font-size: 1.05rem;
  color: var(--procurement-create-text);
  margin: 0;
}

.card-body-custom {
  padding: 1rem;
  overflow: visible;
}

/* Form Groups */
.form-group {
  margin-bottom: 0.75rem;
}

.compact-form-group {
  margin-bottom: 0;
}

/* Modern Select */
.modern-select {
  border-radius: 8px;
  border: 1px solid var(--procurement-create-input-border);
  transition: all 0.3s ease;
}

.modern-select:focus {
  border-color: #667eea;
  box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

/* Modern Input */
.modern-input {
  border-radius: 8px;
  border: 1px solid var(--procurement-create-input-border);
  background: var(--procurement-create-input-bg);
  color: var(--procurement-create-input-text);
  padding: 0.45rem 0.85rem;
  transition: all 0.3s ease;
}

.modern-input:focus {
  border-color: #667eea;
  box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
  background: var(--procurement-create-input-bg);
  color: var(--procurement-create-input-text);
}

/* Modern Textarea */
.modern-textarea {
  border-radius: 8px;
  border: 1px solid var(--procurement-create-input-border);
  background: var(--procurement-create-input-bg);
  color: var(--procurement-create-input-text);
  padding: 0.65rem 0.85rem;
  resize: vertical;
  transition: all 0.3s ease;
}

.modern-textarea:focus {
  border-color: #667eea;
  box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
  background: var(--procurement-create-input-bg);
  color: var(--procurement-create-input-text);
}

.modern-input::placeholder,
.modern-textarea::placeholder {
  color: var(--procurement-create-placeholder);
}

/* Add Item Button */
.add-item-btn {
  border-radius: 25px;
  padding: 0.4rem 1.15rem;
  font-weight: 600;
  transition: all 0.3s ease;
}

.add-item-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 15px rgba(0, 123, 255, 0.3);
}

/* Items Table */
.items-table-container {
  border-radius: 10px;
  overflow: hidden;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
  border: 1px solid var(--procurement-create-table-border);
}

.items-table {
  width: 100%;
  border-collapse: collapse;
  background: var(--procurement-create-table-bg);
}

.items-table thead {
  background: #4c5f98;
  color: white;
}

.items-table th {
  padding: 0.75rem 0.85rem;
  font-weight: 600;
  font-size: 0.9rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.items-table tbody tr {
  transition: all 0.3s ease;
}

.items-table tbody tr:nth-child(even) td {
  background: var(--procurement-create-table-row-alt);
}

.items-table tbody tr:hover td {
  background: var(--procurement-create-table-row-hover);
}

.items-table td {
  padding: 0.75rem 0.85rem;
  border-bottom: 1px solid var(--procurement-create-table-border);
  vertical-align: top;
  background: var(--procurement-create-table-bg);
  color: var(--procurement-create-text);
}

.item-number {
  font-weight: 600;
  color: #667eea;
}

.unit-badge {
  background: var(--procurement-create-unit-badge-bg);
  color: var(--procurement-create-unit-badge-text);
  padding: 0.25rem 0.75rem;
  border-radius: 15px;
  font-size: 0.8rem;
  font-weight: 500;
}

.item-description {
  font-weight: 500;
  color: var(--procurement-create-text);
  max-width: 300px;
}

.item-quantity, .item-unit {
  font-weight: 600;
  color: var(--procurement-create-text);
}

.item-cost, .item-total {
  font-weight: 700;
  color: #28a745;
  font-family: 'Courier New', monospace;
}

.grand-total-row {
  background: var(--procurement-create-header-bg);
  border-top: 2px solid #667eea;
}

.grand-total-label {
  font-weight: 700;
  color: var(--procurement-create-text);
  font-size: 0.9rem;
}

.grand-total-amount {
  font-weight: 700;
  color: #28a745;
  font-size: 1rem;
  font-family: 'Courier New', monospace;
}

/* Action Buttons */
.action-buttons {
  display: flex;
  gap: 0.5rem;
  justify-content: center;
}

.action-btn {
  border-radius: 8px;
  padding: 0.4rem 0.8rem;
  transition: all 0.3s ease;
}



/* Empty State */
.empty-state {
  text-align: center;
  padding: 2rem 1rem;
}

.empty-state-icon {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  background: var(--procurement-create-empty-icon-bg);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2rem;
  color: var(--procurement-create-empty-icon-text);
  margin: 0 auto 1rem;
}

.empty-state-title {
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--procurement-create-text);
  margin-bottom: 0.5rem;
}

.empty-state-text {
  color: var(--procurement-create-muted);
  font-size: 0.9rem;
}

/* Action Buttons Container */
.action-buttons-container {
  background: var(--procurement-create-action-surface-bg);
  border-radius: 15px;
  padding: 2rem;
}

.action-buttons-group {
  display: flex;
  gap: 0.65rem;
  justify-content: center;
  flex-wrap: wrap;
}

.action-btn {
  border-radius: 25px;
  padding: 0.55rem 1.2rem;
  font-weight: 600;
  font-size: 0.9rem;
  transition: all 0.3s ease;
  min-width: 145px;
}

.primary-btn {
  background: linear-gradient(135deg, #667eea, #764ba2);
  border: none;
  color: white;
}

.primary-btn:hover {
  transform: translateY(-3px);
  box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
}

.success-btn {
  background: linear-gradient(135deg, #28a745, #20c997);
  border: none;
  color: white;
}

.success-btn:hover {
  transform: translateY(-3px);
  box-shadow: 0 8px 25px rgba(40, 167, 69, 0.4);
}

.back-btn {
  border: 2px solid var(--procurement-create-back-btn-border);
  color: var(--procurement-create-back-btn-text);
  background: var(--procurement-create-back-btn-bg);
}

.back-btn:hover {
  background: var(--procurement-create-back-btn-hover-bg);
  color: var(--procurement-create-back-btn-hover-text);
  transform: translateY(-3px);
  box-shadow: 0 8px 25px rgba(108, 117, 125, 0.4);
}

/* Responsive Design */
@media (max-width: 768px) {
  .hero-title {
    font-size: 1.75rem;
  }

  .hero-subtitle {
    font-size: 0.9rem;
  }

  .hero-icon-wrapper {
    width: 60px;
    height: 60px;
  }

  .hero-icon {
    font-size: 2rem;
  }

.content-wrapper {
  padding: 1rem;
  overflow: visible;
}

  .card-body-custom {
    padding: 1rem;
  }

  .pap-profile-panel__header,
  .pap-selected-code__top,
  .pap-selected-code__bottom {
    flex-direction: column;
    align-items: flex-start;
  }

  .pap-profile-panel__hint {
    white-space: normal;
  }

  .pap-budget-overview {
    grid-template-columns: 1fr;
  }

  .action-buttons-group {
    flex-direction: column;
    align-items: stretch;
  }

  .action-btn {
    min-width: auto;
    padding: 0.4rem 1rem;
    font-size: 0.85rem;
  }

  .items-table th,
  .items-table td {
    padding: 0.75rem 0.5rem;
  }

  .item-description {
    max-width: 200px;
  }
}

@media (max-width: 576px) {
  .hero-gradient {
    padding: 1.5rem 0;
  }

  .hero-title {
    font-size: 1.5rem;
  }

  .status-display {
    text-align: center;
    margin-top: 1rem;
  }

  .content-card {
    margin-bottom: 1rem;
  }

  .action-buttons-container {
    padding: 1rem;
  }
}
</style>

<style>
[data-bs-theme="dark"] .procurement-create-container {
  --procurement-create-page-bg: linear-gradient(180deg, #161d27 0%, #111827 100%);
  --procurement-create-content-bg: linear-gradient(180deg, #202937 0%, #1a2230 100%);
  --procurement-create-card-bg: linear-gradient(180deg, #232c3a 0%, #1d2531 100%);
  --procurement-create-card-border: rgba(148, 163, 184, 0.18);
  --procurement-create-card-shadow: 0 18px 38px rgba(2, 6, 23, 0.34);
  --procurement-create-card-hover-shadow: 0 22px 42px rgba(2, 6, 23, 0.42);
  --procurement-create-text: #e5edf7;
  --procurement-create-muted: #9fb0c7;
  --procurement-create-header-bg: linear-gradient(135deg, #202937, #1b2230);
  --procurement-create-header-border: rgba(148, 163, 184, 0.14);
  --procurement-create-input-bg: #1b2230;
  --procurement-create-input-border: rgba(148, 163, 184, 0.22);
  --procurement-create-input-text: #e5edf7;
  --procurement-create-placeholder: #94a3b8;
  --procurement-create-table-bg: #1b2230;
  --procurement-create-table-row-alt: #202937;
  --procurement-create-table-row-hover: rgba(96, 165, 250, 0.12);
  --procurement-create-table-border: rgba(148, 163, 184, 0.14);
  --procurement-create-unit-badge-bg: linear-gradient(135deg, #263346, #1d2838);
  --procurement-create-unit-badge-text: #dbe7f5;
  --procurement-create-empty-icon-bg: linear-gradient(135deg, #263346, #1d2838);
  --procurement-create-empty-icon-text: #a8b9cf;
  --procurement-create-action-surface-bg: linear-gradient(135deg, #202937, #1b2230);
  --procurement-create-back-btn-bg: rgba(30, 41, 59, 0.6);
  --procurement-create-back-btn-border: rgba(148, 163, 184, 0.28);
  --procurement-create-back-btn-text: #dbe7f5;
  --procurement-create-back-btn-hover-bg: #475569;
  --procurement-create-back-btn-hover-text: #ffffff;
}

[data-bs-theme="dark"] .procurement-create-container .card-header-icon {
  color: #9cb7ff;
  background: rgba(140, 164, 255, 0.14);
}

[data-bs-theme="dark"] .procurement-create-container .grand-total-amount,
[data-bs-theme="dark"] .procurement-create-container .item-cost,
[data-bs-theme="dark"] .procurement-create-container .item-total {
  color: #4ade80;
}

[data-bs-theme="dark"] .procurement-create-container .modern-select.multiselect,
[data-bs-theme="dark"] .procurement-create-container .modern-select .multiselect,
[data-bs-theme="dark"] .procurement-create-container .modern-select .multiselect-wrapper,
[data-bs-theme="dark"] .procurement-create-container .modern-select .multiselect-tags {
  background: var(--procurement-create-input-bg) !important;
  border-color: var(--procurement-create-input-border) !important;
  color: var(--procurement-create-input-text) !important;
}

[data-bs-theme="dark"] .procurement-create-container .modern-select.multiselect.is-active,
[data-bs-theme="dark"] .procurement-create-container .modern-select .multiselect.is-active {
  box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.2) !important;
}

[data-bs-theme="dark"] .procurement-create-container .modern-select .multiselect-search,
[data-bs-theme="dark"] .procurement-create-container .modern-select.multiselect .multiselect-search,
[data-bs-theme="dark"] .procurement-create-container .modern-select .multiselect-tags-search {
  background: var(--procurement-create-input-bg) !important;
  color: var(--procurement-create-input-text) !important;
}

[data-bs-theme="dark"] .procurement-create-container .modern-select .multiselect-placeholder,
[data-bs-theme="dark"] .procurement-create-container .modern-select .multiselect-single-label,
[data-bs-theme="dark"] .procurement-create-container .modern-select .multiselect-multiple-label {
  color: var(--procurement-create-input-text) !important;
}

[data-bs-theme="dark"] .procurement-create-container .modern-select .multiselect-caret,
[data-bs-theme="dark"] .procurement-create-container .modern-select .multiselect-clear {
  color: #cbd5e1 !important;
  background-color: transparent !important;
}

[data-bs-theme="dark"] .procurement-create-container .modern-select .multiselect-dropdown {
  background: #232c3a !important;
  border-color: var(--procurement-create-input-border) !important;
}

[data-bs-theme="dark"] .procurement-create-container .modern-select .multiselect-option {
  background: #232c3a !important;
  color: #dbe7f5 !important;
}

[data-bs-theme="dark"] .procurement-create-container .modern-select .multiselect-option.is-pointed,
[data-bs-theme="dark"] .procurement-create-container .modern-select .multiselect-option.is-selected,
[data-bs-theme="dark"] .procurement-create-container .modern-select .multiselect-option.is-selected.is-pointed {
  background: rgba(96, 165, 250, 0.18) !important;
  color: #eff6ff !important;
}

[data-bs-theme="dark"] .procurement-create-container .modern-select .multiselect-tag {
  background: linear-gradient(135deg, #4f6bdc, #6378ff) !important;
  color: #ffffff !important;
}

[data-bs-theme="dark"] .procurement-create-container .pap-profile-panel {
  background: linear-gradient(180deg, rgba(27, 34, 48, 0.95), rgba(35, 44, 58, 0.98));
  border-color: rgba(140, 164, 255, 0.2);
}

[data-bs-theme="dark"] .procurement-create-container .pap-profile-panel__hint {
  background: rgba(140, 164, 255, 0.14);
  color: #c7d5ff;
}

[data-bs-theme="dark"] .procurement-create-container .pap-budget-overview__card,
[data-bs-theme="dark"] .procurement-create-container .pap-selected-code {
  background: rgba(27, 34, 48, 0.92);
  border-color: rgba(148, 163, 184, 0.18);
  box-shadow: 0 18px 28px rgba(2, 6, 23, 0.14);
}

[data-bs-theme="dark"] .procurement-create-container .pap-selected-code__code {
  background: rgba(74, 222, 128, 0.14);
  color: #86efac;
}

[data-bs-theme="dark"] .procurement-create-container .pap-selected-code__action {
  color: #c7d5ff;
}
</style>
