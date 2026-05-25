<template>
  <div class="procurement-category-create procurement-create-container">
    <Head :title="pageTitle" />

    <div class="hero-header mb-3">
      <div class="hero-gradient">
        <div class="container-fluid">
          <div class="row align-items-center">
            <div class="col-lg-8">
              <div class="d-flex align-items-center">
                <div class="hero-icon-wrapper me-3">
                  <i class="ri-shopping-bag-3-line hero-icon"></i>
                </div>
                <div>
                  <h1 class="hero-title mb-1">{{ pageTitle }}</h1>
                  <p class="hero-subtitle mb-0">Create one purchase request from approved PPMP items across units</p>
                </div>
              </div>
            </div>
            <div class="col-lg-4 text-end mt-3 mt-lg-0">
              <b-button type="button" variant="light" class="hero-back-btn" @click="goBack">
                <i class="ri-arrow-left-line align-bottom me-1"></i>
                Back
              </b-button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <form @submit.prevent="submit">
      <div class="content-card request-details-card mb-3">
        <div class="card-body-custom request-details-body">
          <div class="row g-3">
            <div class="col-lg-3">
              <div class="form-group compact-form-group">
                <InputLabel value="PR Date" :message="form.errors.date" />
                <TextInput v-model="form.date" type="date" class="form-control modern-input" readonly/>
              </div>
            </div>

            <div class="col-lg-3">
              <div class="form-group compact-form-group">
                <InputLabel value="Fund Cluster" :message="form.errors.fund_cluster_id" />
                <Multiselect
                  v-model="form.fund_cluster_id"
                  :options="fundClusterOptions"
                  :searchable="true"
                  label="name"
                  valueProp="value"
                  placeholder="Select fund cluster"
                  class="modern-select"
                  :append-to-body="true"
                />
              </div>
            </div>

            <div class="col-lg-6">
              <div class="form-group compact-form-group">
                <InputLabel value="Item Category" :message="form.errors.item_category_id" />
                <Multiselect
                  v-model="form.item_category_id"
                  :options="categoryOptions"
                  :searchable="true"
                  label="name"
                  valueProp="value"
                  placeholder="Select Item Category"
                  class="modern-select"
                  :append-to-body="true"
                />
              </div>
            </div>

            <div class="col-lg-6" v-if="form.fund_cluster_id && form.item_category_id">
              <div class="form-group compact-form-group">
                <InputLabel value="Procurement Codes" :message="form.errors.procurement_code_ids" />
                <Multiselect
                  v-model="form.procurement_code_ids"
                  :options="procurementCodeOptions"
                  :searchable="true"
                  mode="tags"
                  label="label"
                  valueProp="value"
                  trackBy="label"
                  placeholder="Select Procurement code/s"
                  class="modern-select"
                  :append-to-body="true"
                />
              </div>
            </div>


            <div class="col-lg-6" v-if="form.fund_cluster_id && form.item_category_id">
              <div class="form-group compact-form-group">
                <InputLabel value="Title" :message="form.errors.title" />
                <TextInput v-model="form.title" type="text" class="form-control modern-input" placeholder="PR title" />
              </div>
            </div>

            <div v-if="isLockedMode" class="col-lg-6" >
              <div class="form-group compact-form-group">
                <InputLabel value="Current APP" :message="form.errors.procurement_app_id" />
                <Multiselect
                  v-model="form.procurement_app_id"
                  :options="currentAppOptions"
                  :searchable="true"
                  label="name"
                  valueProp="value"
                  placeholder="Select current APP"
                  class="modern-select"
                  :append-to-body="true"
                />
              </div>
            </div>

        

            <div class="col-12" v-if="form.fund_cluster_id && form.item_category_id">
              <div class="form-group compact-form-group">
                <InputLabel value="Purpose" :message="form.errors.purpose" />
                <textarea
                  v-model="form.purpose"
                  class="form-control modern-input"
                  rows="2"
                  placeholder="Purpose of this purchase request"
                ></textarea>
              </div>
            </div>
          </div>

          <div class="category-action-bar mt-3">
            <div class="selection-total">
              <span class="selection-count">{{ selectedItems.length }}</span>
              <span>selected</span>
              <span class="divider-dot"></span>
              <strong>{{ formatCurrency(selectedTotal) }}</strong>
            </div>
            <b-button
              v-if="!isLockedMode"
              type="button"
              variant="primary"
              :disabled="!canLoadItems || loadingItems"
              @click="fetchItems"
            >
              <span v-if="loadingItems" class="spinner-border spinner-border-sm me-1"></span>
              <i v-else class="ri-download-cloud-2-line align-bottom me-1"></i>
              Load Items
            </b-button>
          </div>
        </div>
      </div>

      <div class="content-card">

        <div class="card-body-custom">
          <div class="table-responsive category-items-table">
            <table class="items-table mb-0">
              <thead>
                <tr>
                  <th class="text-center" style="width: 46px">Pick</th>
                  <th style="width: 18%">Acquisition Unit</th>
                  <th style="width: 13%">Category</th>
                  <th>Item</th>
                  <th style="width: 11%">Quantity/Unit</th>
                  <th style="width: 12%">Unit Cost</th>
                  <th style="width: 12%">ABC</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="loadingItems">
                  <td colspan="8" class="text-center text-muted py-4">Loading items...</td>
                </tr>
                <tr v-else-if="items.length === 0">
                  <td colspan="8" class="text-center text-muted py-4">
                    {{ emptyItemsMessage }}
                  </td>
                </tr>
                <tr v-for="item in items" :key="item.value" class="item-row">
                  <td class="text-center">
                    <input
                      class="form-check-input"
                      type="checkbox"
                      :checked="selectedIds.includes(item.value)"
                      @change="toggleItem(item)"
                      :disabled="isLockedMode"
                    />
                  </td>
                  <td>{{ item.unit_name || '-' }}</td>
                  <td>{{ item.item_category || '-' }}</td>
                  <td>
                    <div class="fw-semibold">{{ item.item_name }}</div>
                    <div v-if="item.item_description" class="text-muted small">
                    <span v-html="item.item_description"></span>
                    </div>
                  </td>
                  <td>{{ item.quantity_label || item.item_quantity }}</td>
                  <td>{{ formatCurrency(item.item_unit_cost) }}</td>
                  <td class="fw-semibold">{{ formatCurrency(item.total_cost) }}</td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="action-footer">
            <div class="signatory-fields">
              <div class="row g-3">
                <div class="col-lg-6">
                  <div class="form-group compact-form-group">
                    <InputLabel value="Requested By" :message="form.errors.requested_by_id" />
                    <Multiselect
                      v-model="form.requested_by_id"
                      :options="requesterOptions"
                      :searchable="true"
                      label="name"
                      valueProp="value"
                      placeholder="Select requester"
                      class="modern-select"
                      :append-to-body="true"
                    />
                  </div>
                </div>

                <div class="col-lg-6">
                  <div class="form-group compact-form-group">
                    <InputLabel value="Approved By" :message="form.errors.approved_by_id" />
                    <Multiselect
                      v-model="form.approved_by_id"
                      :options="approverOptions"
                      :searchable="true"
                      label="name"
                      valueProp="value"
                      placeholder="Select approver"
                      class="modern-select"
                      :append-to-body="true"
                    />
                  </div>
                </div>
              </div>
            </div>
            <div class="footer-buttons">
            <b-button type="button" variant="outline-secondary" @click="goBack">Cancel</b-button>
            <b-button type="submit" variant="success" :disabled="!canSubmit || form.processing">
              <span v-if="form.processing" class="spinner-border spinner-border-sm me-1"></span>
              <i v-else class="ri-check-line align-bottom me-1"></i>
              {{ submitLabel }}
            </b-button>
            </div>
          </div>
        </div>
      </div>
    </form>

    <CategoryItemSelectionModal
      v-model="itemSelectionModal.show"
      :items="availableItems"
      :selected-ids="selectedIds"
      @load="loadSelectedItems"
    />
  </div>
</template>

<script>
import { Head, router, useForm } from "@inertiajs/vue3";
import axios from "axios";
import Multiselect from "@vueform/multiselect";
import InputLabel from "@/Shared/Components/Forms/InputLabel.vue";
import TextInput from "@/Shared/Components/Forms/TextInput.vue";
import CategoryItemSelectionModal from "./Modals/CategoryItemSelection.vue";

export default {
  components: { Head, InputLabel, TextInput, Multiselect, CategoryItemSelectionModal },
  props: ["dropdowns", "option", "procurement"],
  data() {
    return {
      loadingItems: false,
      hydratingProcurement: false,
      restoringDraft: false,
      draftStorageKey: "procurementRequestByCategoryDraft",
      items: [],
      selectedIds: [],
      availableItems: [],
      itemSelectionModal: {
        show: false,
      },
      form: useForm({
        id: null,
        option: "create_by_category",
        date: this.getCurrentDate(),
        purpose: null,
        title: null,
        division_id: null,
        unit_id: null,
        fund_cluster_id: null,
        classification_id: null,
        reference_app_id: null,
        procurement_app_id: null,
        requested_by_id: null,
        approved_by_id: null,
        procurement_code_ids: [],
        item_category_id: null,
        items: [],
      }),
    };
  },
  computed: {
    isApproveMode() {
      return this.option === "approve";
    },
    isReviewMode() {
      return this.option === "review";
    },
    isLockedMode() {
      return this.isReviewMode || this.isApproveMode;
    },
    pageTitle() {
      if (this.isApproveMode) {
        return "Approve Purchase Request by Category";
      }

      if (this.isReviewMode) {
        return "Review Purchase Request by Category";
      }

      return "Create Purchase Request";
    },
    submitLabel() {
      if (this.isApproveMode) {
        return "Approve PR";
      }

      if (this.isReviewMode) {
        return "Review PR";
      }

      return "Create PR";
    },
    emptyItemsMessage() {
      return this.isLockedMode
        ? "No items are attached to this purchase request."
        : "Choose a fund cluster and item category, then load items.";
    },
    canLoadItems() {
      return Boolean(this.form.item_category_id && this.form.fund_cluster_id);
    },
    fundClusterOptions() {
      return this.normalizeDropdownOptions(this.dropdowns?.fund_clusters);
    },
    procurementCodeOptions() {
      return this.normalizeDropdownOptions(this.dropdowns?.procurement_codes, "label");
    },
    requesterOptions() {
      return this.normalizeDropdownOptions(this.dropdowns?.requesters);
    },
    approverOptions() {
      return this.normalizeDropdownOptions(this.dropdowns?.approvers);
    },
    categoryOptions() {
      const ppmpCategories = this.dropdowns.ppmp_item_categories || [];
      const options = ppmpCategories.length > 0 ? ppmpCategories : (this.dropdowns.item_categories || []);
      return this.normalizeDropdownOptions(options);
    },
    referenceAppOptions() {
      const options = this.normalizeList(this.dropdowns?.reference_apps);
      const fallbackOptions = this.normalizeList(this.dropdowns?.app_types);
      const source = options.length ? options : fallbackOptions;

      return this.normalizeDropdownOptions(source);
    },
    currentAppOptions() {
      return this.normalizeDropdownOptions(this.dropdowns?.current_apps);
    },
    selectedItems() {
      return this.items.filter((item) => this.selectedIds.includes(item.value));
    },
    selectedTotal() {
      return this.selectedItems.reduce((sum, item) => sum + Number(item.total_cost || 0), 0);
    },
    allVisibleSelected() {
      return this.items.length > 0 && this.items.every((item) => this.selectedIds.includes(item.value));
    },
    canSubmit() {
      if (this.isReviewMode && !this.form.procurement_app_id) {
        return false;
      }

      return Boolean(
        this.form.date &&
        this.form.fund_cluster_id &&
        this.form.purpose &&
        this.form.procurement_code_ids.length > 0 &&
        this.selectedItems.length > 0
      );
    },
  },
  watch: {
    "form.item_category_id"() {
      this.clearItems();
      this.saveDraft();
    },
    "form.fund_cluster_id"() {
      this.clearItems();
      this.saveDraft();
    },
    "form.procurement_code_ids"() {
      this.refreshTitleFromCodes();
      this.saveDraft();
    },
    "form.date"() {
      this.applyAutomaticCurrentApp(true);
      this.saveDraft();
    },
    "form.title"() {
      this.saveDraft();
    },
    "form.purpose"() {
      this.saveDraft();
    },
    "form.procurement_app_id"() {
      this.saveDraft();
    },
    "form.requested_by_id"() {
      this.saveDraft();
    },
    "form.approved_by_id"() {
      this.saveDraft();
    },
  },
  mounted() {
    if (this.procurement?.id) {
      this.hydrateProcurement();
      return;
    }

    if (this.dropdowns?.regional_director?.value) {
      this.form.approved_by_id = this.dropdowns.regional_director.value;
    }
    this.prefillUserDivisionAndUnit();
    this.restoreDraft();
  },
  methods: {
    getCurrentDate() {
      return new Date().toISOString().split("T")[0];
    },
    hydrateProcurement() {
      this.hydratingProcurement = true;
      this.form.id = this.procurement.id;
      this.form.option = this.option || "create_by_category";
      this.form.date = this.normalizeDate(this.procurement.date);
      this.form.purpose = this.procurement.purpose;
      this.form.title = this.procurement.title;
      this.form.division_id = this.procurement.division_id;
      this.form.unit_id = this.procurement.unit_id;
      this.form.fund_cluster_id = this.procurement.fund_cluster_id;
      this.form.classification_id = this.procurement.classification_id;
      this.form.reference_app_id = this.procurement.reference_app_id;
      this.form.procurement_app_id = this.procurement.procurement_app_id;
      this.applyAutomaticCurrentApp();
      this.form.requested_by_id = this.procurement.requested_by_id;
      this.form.approved_by_id = this.procurement.approved_by_id;
      const codes = this.normalizeList(this.procurement.codes);
      const items = this.normalizeList(this.procurement.items);

      this.form.procurement_code_ids = codes
        .map((code) => code.procurement_code_id)
        .filter(Boolean);

      this.items = items.map((item) => this.normalizeProcurementItem(item));
      this.selectedIds = this.items.map((item) => item.value);
      this.form.item_category_id = this.items[0]?.item_category_id || null;
      this.syncFormItems();
      this.$nextTick(() => {
        this.hydratingProcurement = false;
      });
    },
    normalizeList(value) {
      if (Array.isArray(value)) {
        return value;
      }

      if (value && typeof value === "object") {
        return Object.values(value);
      }

      return [];
    },
    normalizeDropdownOptions(value, labelKey = "name") {
      return this.normalizeList(value).map((option) => {
        const normalizedValue = option.value ?? option.id;
        const normalizedLabel = option[labelKey] ?? option.name ?? option.label ?? option.code ?? option.title;

        return {
          ...option,
          value: normalizedValue,
          [labelKey]: normalizedLabel,
          name: option.name ?? normalizedLabel,
          label: option.label ?? normalizedLabel,
        };
      });
    },
    applyAutomaticCurrentApp(force = false) {
      if (!this.isReviewMode || (this.form.procurement_app_id && !force) || !this.currentAppOptions.length) {
        return;
      }

      const prYear = this.form.date
        ? Number(new Date(this.form.date).getFullYear())
        : Number(new Date().getFullYear());
      const matchingApp = this.currentAppOptions.find((app) => Number(app.year) === prYear);

      this.form.procurement_app_id = matchingApp ? Number(matchingApp.value) : null;
    },
    normalizeDate(value) {
      if (!value) return this.getCurrentDate();

      const date = new Date(value);
      if (Number.isNaN(date.getTime())) return value;

      return date.toISOString().split("T")[0];
    },
    normalizeProcurementItem(item) {
      const ppmpItem = item.ppmp_item || {};
      const ppmp = ppmpItem.ppmp || {};
      const value = item.ppmp_item_id || item.id;

      return {
        value,
        ppmp_item_id: item.ppmp_item_id,
        ppmp_no: ppmp.code,
        unit_name: ppmp.unit?.name || ppmp.unit_name,
        item_category_id: ppmpItem.item_category_id,
        item_category: ppmpItem.item_category?.name,
        item_unit_type_id: item.item_unit_type_id,
        item_name: item.item_name,
        item_unit_cost: item.item_unit_cost,
        item_quantity: item.item_quantity,
        item_description: item.item_description,
        total_cost: item.total_cost,
        quantity_label: item.item_quantity,
      };
    },
    clearItems() {
      if (this.hydratingProcurement || this.restoringDraft) {
        return;
      }

      this.items = [];
      this.selectedIds = [];
      this.availableItems = [];
      this.form.items = [];
      this.saveDraft();
    },
    refreshTitleFromCodes() {
      if (this.restoringDraft) {
        return;
      }

      const selectedCodes = this.dropdowns.procurement_codes.filter((code) =>
        this.form.procurement_code_ids.includes(code.value)
      );
      this.form.title = selectedCodes.map((code) => code.title || code.code || code.name).filter(Boolean).join(", ");
    },
    prefillUserDivisionAndUnit() {
      const organization = this.$page.props.user?.data?.organization || {};

      if (organization.division_id) {
        this.form.division_id = Number(organization.division_id);
      }

      if (organization.unit_id) {
        this.form.unit_id = Number(organization.unit_id);
      }
    },
    async fetchItems() {
      if (!this.canLoadItems) return;

      this.loadingItems = true;
      this.clearItems();

      try {
        const { data } = await axios.get("/faims/procurements/create-by-category", {
          params: {
            option: "ppmp_category_items",
            item_category_id: this.form.item_category_id,
            fund_cluster_id: this.form.fund_cluster_id,
          },
        });

        this.availableItems = Array.isArray(data) ? data : [];
        this.saveDraft();
        this.itemSelectionModal.show = true;
      } finally {
        this.loadingItems = false;
      }
    },
    loadSelectedItems(items) {
      this.items = items;
      this.selectedIds = this.items.map((item) => item.value);
      this.syncFormItems();
      this.itemSelectionModal.show = false;
      this.saveDraft();
    },
    toggleItem(item) {
      if (this.isLockedMode) return;

      if (this.selectedIds.includes(item.value)) {
        this.selectedIds = this.selectedIds.filter((id) => id !== item.value);
      } else {
        this.selectedIds = [...this.selectedIds, item.value];
      }
      this.syncFormItems();
      this.saveDraft();
    },
    toggleAll() {
      if (this.isLockedMode) return;

      this.selectedIds = this.allVisibleSelected ? [] : this.items.map((item) => item.value);
      this.syncFormItems();
      this.saveDraft();
    },
    syncFormItems() {
      this.form.items = this.selectedItems.map((item) => ({
        ppmp_item_id: item.value,
        item_unit_type_id: item.item_unit_type_id,
        item_name: item.item_name,
        item_unit_cost: item.item_unit_cost,
        item_quantity: item.item_quantity,
        item_description: item.item_description,
        total_cost: item.total_cost,
      }));
      this.saveDraft();
    },
    draftPayload() {
      return {
        form: {
          date: this.form.date,
          purpose: this.form.purpose,
          title: this.form.title,
          division_id: this.form.division_id,
          unit_id: this.form.unit_id,
          fund_cluster_id: this.form.fund_cluster_id,
          classification_id: this.form.classification_id,
          reference_app_id: this.form.reference_app_id,
          procurement_app_id: this.form.procurement_app_id,
          requested_by_id: this.form.requested_by_id,
          approved_by_id: this.form.approved_by_id,
          procurement_code_ids: Array.isArray(this.form.procurement_code_ids)
            ? this.form.procurement_code_ids
            : [],
          item_category_id: this.form.item_category_id,
          items: Array.isArray(this.form.items) ? this.form.items : [],
        },
        items: this.items,
        selectedIds: this.selectedIds,
        availableItems: this.availableItems,
        saved_at: new Date().toISOString(),
      };
    },
    saveDraft() {
      if (this.isLockedMode || this.hydratingProcurement || this.restoringDraft) {
        return;
      }

      try {
        localStorage.setItem(this.draftStorageKey, JSON.stringify(this.draftPayload()));
      } catch (error) {
        console.error("Unable to save category PR draft:", error);
      }
    },
    restoreDraft() {
      let draft = null;

      try {
        draft = JSON.parse(localStorage.getItem(this.draftStorageKey));
      } catch (error) {
        localStorage.removeItem(this.draftStorageKey);
        return;
      }

      if (!draft || typeof draft !== "object") {
        return;
      }

      this.restoringDraft = true;

      const draftForm = draft.form || {};

      [
        "date",
        "purpose",
        "title",
        "division_id",
        "unit_id",
        "fund_cluster_id",
        "classification_id",
        "reference_app_id",
        "procurement_app_id",
        "requested_by_id",
        "approved_by_id",
        "item_category_id",
      ].forEach((key) => {
        if (Object.prototype.hasOwnProperty.call(draftForm, key)) {
          this.form[key] = draftForm[key];
        }
      });

      this.form.procurement_code_ids = Array.isArray(draftForm.procurement_code_ids)
        ? draftForm.procurement_code_ids
        : [];
      this.items = Array.isArray(draft.items) ? draft.items : [];
      this.selectedIds = Array.isArray(draft.selectedIds) ? draft.selectedIds : [];
      this.availableItems = Array.isArray(draft.availableItems) ? draft.availableItems : [];
      this.form.items = Array.isArray(draftForm.items) ? draftForm.items : [];

      this.$nextTick(() => {
        this.restoringDraft = false;
        this.syncFormItems();
        this.saveDraft();
      });
    },
    clearDraft() {
      localStorage.removeItem(this.draftStorageKey);
    },
    submit() {
      this.applyAutomaticCurrentApp();
      this.syncFormItems();
      this.form.option = this.isApproveMode
        ? "approve"
        : (this.isReviewMode ? "review" : "create_by_category");

      if (this.isLockedMode && this.form.id) {
        this.form.put(`/faims/procurements/${this.form.id}`, {
          preserveScroll: true,
        });
        return;
      }

      this.form.post("/faims/procurements", {
        preserveScroll: true,
        onSuccess: () => {
          this.clearDraft();
        },
      });
    },
    goBack() {
      router.get("/faims/procurements");
    },
    formatCurrency(value) {
      return new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
      }).format(Number(value || 0));
    },
  },
};
</script>

<style scoped>
.procurement-create-container {
  --procurement-create-page-bg: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  --procurement-create-card-bg: #ffffff;
  --procurement-create-card-border: rgba(255, 255, 255, 0.8);
  --procurement-create-card-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
  --procurement-create-card-hover-shadow: 0 15px 35px rgba(0, 0, 0, 0.12);
  --procurement-create-text: #2c3e50;
  --procurement-create-muted: #64748b;
  --procurement-create-input-bg: #ffffff;
  --procurement-create-input-border: #ced4da;
  --procurement-create-table-row-alt: #f8fafc;
  --procurement-create-table-row-hover: #eef2ff;
  --procurement-create-table-border: rgba(0, 0, 0, 0.05);
  background: var(--procurement-create-page-bg);
  min-height: 100vh;
  padding: 0 0 1rem 0;
}

.hero-header {
  position: relative;
  overflow: hidden;
  border-radius: 14px;
  box-shadow: 0 8px 22px rgba(0, 0, 0, 0.09);
}

.hero-gradient {
  background: #4c5f98;
  padding: 0.45rem 0;
  position: relative;
}

.hero-icon-wrapper {
  width: 58px;
  height: 58px;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
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
}

.hero-subtitle {
  font-size: 0.95rem;
  color: rgba(255, 255, 255, 0.9);
}

.hero-back-btn {
  border-radius: 10px;
  font-weight: 600;
}

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

.request-details-body,
.card-body-custom {
  padding: 1.25rem;
}

.compact-form-group {
  margin-bottom: 0;
}

.modern-input {
  border-radius: 10px;
  border: 2px solid var(--procurement-create-input-border);
  background: var(--procurement-create-input-bg);
  color: var(--procurement-create-text);
  transition: all 0.3s ease;
}

.modern-input:focus {
  border-color: #667eea;
  box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.15);
}

.modern-select {
  border-radius: 10px;
}

.category-action-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  flex-wrap: wrap;
}

.selection-total {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  color: var(--procurement-create-muted);
}

.selection-count {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 28px;
  height: 28px;
  border-radius: 999px;
  background: #eef2ff;
  color: #4c5f98;
  font-weight: 700;
}

.divider-dot {
  width: 5px;
  height: 5px;
  border-radius: 999px;
  background: #94a3b8;
}

.card-header-custom {
  background: linear-gradient(135deg, #f8f9fa, #e9ecef);
  padding: 1rem 1.25rem;
  border-bottom: 1px solid rgba(0, 0, 0, 0.05);
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.card-header-icon {
  color: #4c5f98;
  font-size: 1.25rem;
}

.card-header-title {
  margin: 0;
  font-size: 1rem;
  font-weight: 700;
  color: var(--procurement-create-text);
}

.category-items-table {
  max-height: calc(100vh - 430px);
  overflow: auto;
}

.items-table {
  width: 100%;
  border-collapse: collapse;
  background: white;
}

.items-table thead th {
  position: sticky;
  top: 0;
  z-index: 1;
  background: #f8fafc;
  color: #334155;
  font-size: 0.78rem;
  font-weight: 700;
  padding: 0.85rem 0.75rem;
  border-bottom: 1px solid var(--procurement-create-table-border);
}

.items-table td {
  padding: 0.8rem 0.75rem;
  border-bottom: 1px solid var(--procurement-create-table-border);
  vertical-align: middle;
}

.items-table tbody tr:nth-child(even) {
  background: var(--procurement-create-table-row-alt);
}

.items-table tbody tr:hover {
  background: var(--procurement-create-table-row-hover);
}

.action-footer {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  padding-top: 1rem;
}

.signatory-fields {
  width: 100%;
  padding: 1rem;
  border-radius: 12px;
  background: #f8fafc;
  border: 1px solid rgba(0, 0, 0, 0.05);
}

.footer-buttons {
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
  flex-wrap: wrap;
}

@media (max-width: 768px) {
  .hero-gradient {
    padding: 0.75rem 0;
  }

  .hero-title {
    font-size: 1.1rem;
  }

  .hero-icon-wrapper {
    width: 48px;
    height: 48px;
  }
}
</style>
