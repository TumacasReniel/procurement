<template>
  <b-modal
    v-model="modal.show"
    header-class="p-3"
    :title="modalTitle"
    size="xl"
    class="v-modal-custom"
    modal-class="zoomIn"
    centered
    no-close-on-backdrop
  >
    <form class="customform">
      <BRow>
        <BCol lg="12" class="mb-3">
          <InputLabel value="General Description and Objective(Column 1)" />
          <textarea
            v-model="form.general_description_objective"
            class="form-control"
            :class="inputInvalidClass('general_description_objective')"
            rows="3"
            placeholder="General description and objective of the project to be procured"
          ></textarea>
          <div
            v-if="fieldError('general_description_objective')"
            class="invalid-feedback d-block"
          >
            {{ fieldError("general_description_objective") }}
          </div>
        </BCol>
        <BCol lg="4">
          <InputLabel value="Type of Project to be Procured(Column 2)" />
          <Multiselect
            :class="multiselectInvalidClass('project_type')"
            :options="projectTypeOptions"
            v-model="form.project_type"
            :searchable="true"
            label="name"
            value-prop="name"
            placeholder="Select project type"
          />
          <div v-if="fieldError('project_type')" class="invalid-feedback d-block">
            {{ fieldError("project_type") }}
          </div>
        </BCol>

        <BCol lg="4">
          <InputLabel value="Recommended Mode of Procurement(Column 4)" />
          <Multiselect
            :class="multiselectInvalidClass('recommended_mode_of_procurement')"
            :options="modeOfProcurementOptions"
            v-model="form.recommended_mode_of_procurement"
            :searchable="true"
            label="name"
            value-prop="name"
            placeholder="Select mode"
          />
          <div
            v-if="fieldError('recommended_mode_of_procurement')"
            class="invalid-feedback d-block"
          >
            {{ fieldError("recommended_mode_of_procurement") }}
          </div>
        </BCol>


          <BCol lg="4">
          <InputLabel value="Pre-Procurement Conference(Column 5)" />
          <Multiselect
            :class="multiselectInvalidClass('pre_procurement_conference')"
            :options="preProcurementConferenceOptions"
            v-model="form.pre_procurement_conference"
            :searchable="false"
            label="label"
            value-prop="value"
            placeholder="Select option"
          />
          <div
            v-if="fieldError('pre_procurement_conference')"
            class="invalid-feedback d-block"
          >
            {{ fieldError("pre_procurement_conference") }}
          </div>
        </BCol>

        <BCol lg="4" class="mt-3">
          <InputLabel value="Start of Procurement Activity(Column 6)" />
          <div class="position-relative month-picker-wrap">
            <i class="ri-calendar-2-line month-picker-icon"></i>
            <flat-pickr
              v-model="form.start_of_procurement_activity"
              :config="monthPickerConfig"
            />
          </div>
          <div
            v-if="fieldError('start_of_procurement_activity')"
            class="invalid-feedback d-block"
          >
            {{ fieldError("start_of_procurement_activity") }}
          </div>
        </BCol>

        <BCol lg="4" class="mt-3">
          <InputLabel value="End of Procurement Activity(Column 7)" />
          <div class="position-relative month-picker-wrap">
            <i class="ri-calendar-2-line month-picker-icon"></i>
            <flat-pickr
              v-model="form.end_of_procurement_activity"
              :config="monthPickerConfig"
            />
          </div>
          <div
            v-if="fieldError('end_of_procurement_activity')"
            class="invalid-feedback d-block"
          >
            {{ fieldError("end_of_procurement_activity") }}
          </div>
        </BCol>

        <BCol lg="4" class="mt-3">
          <InputLabel value="Expected Delivery Date(Column 8)" />
          <div class="position-relative month-picker-wrap">
            <i class="ri-calendar-2-line month-picker-icon"></i>
            <flat-pickr
              v-model="form.expected_delivery_date"
              :config="monthPickerConfig"
            />
          </div>
          <div
            v-if="fieldError('expected_delivery_date')"
            class="invalid-feedback d-block"
          >
            {{ fieldError("expected_delivery_date") }}
          </div>
        </BCol>

        <BCol lg="6" class="mt-3">
          <InputLabel value="Supporting Document Type(Column 11)" />
          <TextInput
            v-model="form.attached_supporting_documents"
            type="text"
            class="form-control"
            :class="inputInvalidClass('attached_supporting_documents')"
            placeholder="Document name or type"
          />
          <div
            v-if="fieldError('attached_supporting_documents')"
            class="invalid-feedback d-block"
          >
            {{ fieldError("attached_supporting_documents") }}
          </div>
        </BCol>

      

        <BCol lg="12" class="mt-3">
          <InputLabel value="Attachment" />
          <FileDropzone
            :file="form.supporting_document_file"
            :existing-file-name="editingItem?.supporting_document_original_name || ''"
            :invalid="hasFieldError('supporting_document_file')"
            accept="application/pdf,.pdf"
            :allowed-mime-types="supportingDocumentMimeTypes"
            :allowed-extensions="supportingDocumentExtensions"
            :max-size-mb="10"
            invalid-type-message="Please attach a PDF file only."
            invalid-size-message="Please attach a PDF file up to 10 MB only."
            title="Drop attachment here or click to browse"
            hint="PDF files only, up to 10 MB"
            @selected="setSupportingDocumentFile"
            @rejected="rejectSupportingDocumentFile"
            @remove="removeSupportingDocument"
          />
          <div
            v-if="fieldError('supporting_document_file')"
            class="invalid-feedback d-block"
          >
            {{ fieldError("supporting_document_file") }}
          </div>
        </BCol>

        <BCol lg="12" class="mt-3">
          <InputLabel value="Remarks(Column 12)" />
          <textarea
            v-model="form.remarks"
            class="form-control"
            :class="inputInvalidClass('remarks')"
            rows="3"
            placeholder="Remarks"
          ></textarea>
          <div v-if="fieldError('remarks')" class="invalid-feedback d-block">
            {{ fieldError("remarks") }}
          </div>
        </BCol>

        <BCol lg="12" class="mt-3" v-if="!isEditing">
          <div class="mb-2 fw-semibold text-dark" style="font-size:13px;">Does this project have items? <span class="text-danger">*</span></div>
          <div class="d-flex gap-3">
            <div class="form-check">
              <input class="form-check-input" type="radio" :id="'hasItemsYes_' + $.uid" v-model="hasItems" :value="true" @change="showHasItemsError = false" />
              <label class="form-check-label" :for="'hasItemsYes_' + $.uid">Yes — list items below</label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="radio" :id="'hasItemsNo_' + $.uid" v-model="hasItems" :value="false" @change="showHasItemsError = false" />
              <label class="form-check-label" :for="'hasItemsNo_' + $.uid">No — enter total budget</label>
            </div>
          </div>
          <div v-if="showHasItemsError" class="text-danger small mt-1">Please select whether this project has items.</div>
        </BCol>

        <BCol lg="12" class="mt-3" v-if="hasItems === false">
          <label class="form-label fw-semibold">Total Budget (ABC) <span class="text-danger">*</span></label>
          <Amount
            ref="totalBudgetAmount"
            :class="{ 'is-invalid': Boolean(form.errors.project_total_budget) }"
            @amount="onTotalBudgetAmount"
          />
          <div v-if="form.errors.project_total_budget" class="invalid-feedback d-block">
            {{ form.errors.project_total_budget }}
          </div>
        </BCol>

        <BCol lg="12" class="mt-3" v-if="hasItems === true || isEditing">
          <div class="items-table-toolbar">
            <div>
              <h6 class="items-table-title">Items(Column 3)</h6>
              <span class="items-table-subtitle">
                {{ itemRows.length }} item{{ itemRows.length === 1 ? "" : "s" }}
                {{ isEditing ? "selected" : "queued" }}
              </span>
            </div>
            <b-button
              type="button"
              size="sm"
              variant="primary"
              @click="openItemRowModal"
            >
              <i class="ri-add-line me-1"></i>
              Add Item
            </b-button>
          </div>
          <div class="items-table-container">
            <div class="table-responsive">
              <table class="items-table ppmp-item-entry-table">
                <thead>
                  <tr>
                    <th style="width: 48px" class="text-center">#</th>
                    <th style="width: 18%">Item Name</th>
                    <th>Description</th>
                    <th style="width: 120px">Unit Type</th>
                    <th style="width: 90px" class="text-end">Qty</th>
                    <th style="width: 140px" class="text-end">Unit Cost</th>
                    <th style="width: 140px" class="text-end">Total</th>
                    <th style="width: 54px"></th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="row in paginatedItemRows" :key="row.key" class="item-row">
                    <td class="text-center item-number">{{ row.displayIndex }}</td>
                    <td class="item-description">
                      <span>{{ row.item_name }}</span>
                    </td>
                    <td
                      class="item-description text-muted small"
                      v-html="row.item_description"
                    ></td>
                    <td class="item-unit">
                      <span class="unit-badge">{{
                        unitTypeName(row.item_unit_type_id, row.item_quantity)
                      }}</span>
                    </td>
                    <td class="text-end item-quantity">
                      {{ formatQuantity(row.item_quantity) }}
                    </td>
                    <td class="text-end item-cost">
                      {{ formatCurrency(row.item_unit_cost) }}
                    </td>
                    <td class="text-end item-cost">
                      {{ formatCurrency(row.total_cost) }}
                    </td>
                    <td class="text-center">
                      <div class="d-flex justify-content-center gap-1">
                        <b-button
                          type="button"
                          variant="success"
                          size="sm"
                          class="btn-icon"
                          style="border-radius: 8px"
                          @click="editItemRow(row.originalIndex)"
                        >
                          <i class="ri-edit-2-line"></i>
                        </b-button>
                        <b-button
                          v-if="!isEditing"
                          type="button"
                          variant="danger"
                          size="sm"
                          class="btn-icon"
                          style="border-radius: 8px"
                          @click="removeItemRow(row.originalIndex)"
                        >
                          <i class="ri-delete-bin-line"></i>
                        </b-button>
                      </div>
                    </td>
                  </tr>
                  <tr v-if="!itemRows.length" class="items-empty-row">
                    <td colspan="8" class="text-center">
                      Click Add Item to open the item form and queue an item in this
                      table.
                    </td>
                  </tr>
                </tbody>
                <tfoot>
                  <tr class="grand-total-row">
                    <td colspan="6" class="text-end grand-total-label">Total:</td>
                    <td class="text-end grand-total-amount">
                      {{
                        formatCurrency(
                          itemRows.length ? itemRowsTotalCost : itemTotalCost
                        )
                      }}
                    </td>
                    <td></td>
                  </tr>
                </tfoot>
              </table>
            </div>
          </div>
          <div
            v-if="itemRows.length"
            class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3"
          >
            <div class="text-muted fs-12">
              Showing {{ paginationStart }}-{{ paginationEnd }} of
              {{ itemRows.length }} item(s)
            </div>

            <div class="d-flex align-items-center gap-2">
              <select
                v-model.number="itemsPerPage"
                class="form-select form-select-sm ppmp-page-size"
              >
                <option :value="5">5</option>
                <option :value="10">10</option>
                <option :value="20">20</option>
                <option :value="50">50</option>
              </select>

              <b-button
                size="sm"
                variant="light"
                :disabled="page <= 1"
                @click="page -= 1"
              >
                <i class="ri-arrow-left-s-line"></i>
              </b-button>

              <span class="text-muted fs-12">Page {{ page }} of {{ totalPages }}</span>

              <b-button
                size="sm"
                variant="light"
                :disabled="page >= totalPages"
                @click="page += 1"
              >
                <i class="ri-arrow-right-s-line"></i>
              </b-button>
            </div>
          </div>
        </BCol>

        <BCol lg="12" class="mt-3" v-if="hasItems !== false">
          <div class="item-total-preview">
            <span>{{
              isEditing
                ? "Updated Total Amount"
                : itemRows.length
                ? "Batch Total Amount"
                : "Total Amount"
            }}</span>
            <strong>{{
              formatCurrency(itemRows.length ? itemRowsTotalCost : itemTotalCost)
            }}</strong>
          </div>
          <div v-if="itemTableError" class="text-danger small fw-semibold mt-2">
            {{ itemTableError }}
          </div>
        </BCol>
      </BRow>
    </form>

    <template v-slot:footer>
      <div v-if="form.errors['item']" class="w-100 mb-2">
        <div class="alert alert-danger py-2 px-3 mb-0 small">{{ form.errors['item'] }}</div>
      </div>
      <div v-if="backendError" class="w-100 mb-2">
        <div class="alert alert-danger py-2 px-3 mb-0 small">{{ backendError }}</div>
      </div>
      <b-button @click="hide" variant="light" block>Cancel</b-button>
      <b-button @click="submit" variant="primary" :disabled="form.processing" block>
        {{ form.processing ? "Saving..." : submitLabel }}
      </b-button>
    </template>
  </b-modal>

  <AddItemTableModal
    ref="itemTableModal"
    :unit-type-options="unitTypeOptions"
    :item-category-options="itemCategoryOptions"
    :errors="form.errors"
    @save="saveItemRow"
    @category-added="onCategoryAdded"
  />
</template>

<script>
import { useForm } from "@inertiajs/vue3";
import Multiselect from "@vueform/multiselect";
import InputLabel from "@/Shared/Components/Forms/InputLabel.vue";
import TextInput from "@/Shared/Components/Forms/TextInput.vue";
import FileDropzone from "@/Shared/Components/Forms/FileDropzone.vue";
import AddItemTableModal from "./AddItemTableModal.vue";
import Amount from "@/Shared/Components/Forms/Amount.vue";
import flatPickr from "vue-flatpickr-component";
import monthSelectPlugin from "flatpickr/dist/plugins/monthSelect/index.js";
import "flatpickr/dist/plugins/monthSelect/style.css";

export default {
  components: { Multiselect, InputLabel, TextInput, FileDropzone, AddItemTableModal, Amount, flatPickr },
  props: {
    ppmp: {
      type: Object,
      default: null,
    },
    dropdowns: {
      type: Object,
      default: () => ({}),
    },
    mode: {
      type: String,
      default: "save",
    },
    draftItem: {
      type: Object,
      default: null,
    },
  },
  emits: ["draft"],
  data() {
    return {
      monthPickerConfig: {
        plugins: [
          new monthSelectPlugin({
            shorthand: false,
            dateFormat: "Y-m",
            altFormat: "F Y",
          }),
        ],
        altInput: true,
        altInputClass: "form-control",
        onReady(_dates, _str, instance) {
          if (instance.altInput) {
            instance.altInput.placeholder = "Month YYYY";
          }
        },
      },
      modal: {
        show: false,
      },
      editingItem: null,
      hasItems: null,
      showHasItemsError: false,
      backendError: null,
      form: useForm({
        option: "add_item",
        plan_type: null,
        item_id: null,
        item_name: "",
        item_description: "",
        general_description_objective: "",
        project_type: "",
        recommended_mode_of_procurement: "",
        pre_procurement_conference: "",
        item_quantity: 1,
        item_unit_type_id: null,
        item_unit_cost: null,
        items: [],
        target_ppmp_id: null,
        project_total_budget: null,
        start_of_procurement_activity: null,
        end_of_procurement_activity: null,
        expected_delivery_date: null,
        attached_supporting_documents: "",
        supporting_document_file: null,
        remarks: "",
      }),
      itemRows: [],
      page: 1,
      itemsPerPage: 5,
      extraItemCategories: [],
    };
  },
  computed: {
    unitTypeOptions() {
      const options = this.dropdowns?.unit_types || [];

      return Array.isArray(options) ? options : Object.values(options);
    },
    projectTypeOptions() {
      const options = this.dropdowns?.classifications || [];

      return Array.isArray(options) ? options : Object.values(options);
    },
    modeOfProcurementOptions() {
      const options = this.dropdowns?.mode_of_procurements || [];

      return (Array.isArray(options) ? options : Object.values(options))
        .map((option) => ({
          ...option,
          name: option.name || option.label || option.title || option.code || "",
        }))
        .filter((option) => option.name);
    },
    itemCategoryOptions() {
      const options = this.dropdowns?.item_categories || [];

      const existingOptions = (Array.isArray(options) ? options : Object.values(options))
        .map((option) => ({
          ...option,
          name: option.name || option.label || option.title || option.code || "",
        }))
        .filter((option) => option.name);

      const categoryIds = new Set(
        existingOptions.map((option) => Number(option.value ?? option.id))
      );

      return [
        ...existingOptions,
        ...this.extraItemCategories.filter(
          (option) => !categoryIds.has(Number(option.value ?? option.id))
        ),
      ].sort((first, second) => String(first.name).localeCompare(String(second.name)));
    },
    preProcurementConferenceOptions() {
      return [
        { label: "Yes", value: "Yes" },
        { label: "No", value: "No" },
        { label: "Not Applicable", value: "Not Applicable" },
      ];
    },
    supportingDocumentMimeTypes() {
      return ["application/pdf"];
    },
    supportingDocumentExtensions() {
      return ["pdf"];
    },
    itemUnitTypeLabel() {
      return "display_name";
    },
    itemTableError() {
      return (
        this.form.errors.item_name ||
        this.form.errors.item_description ||
        this.form.errors.item_unit_type_id ||
        this.form.errors.item_unit_cost ||
        this.form.errors.items ||
        this.firstItemRowError ||
        null
      );
    },
    firstItemRowError() {
      const itemErrorKey = Object.keys(this.form.errors || {}).find((key) =>
        key.startsWith("items.")
      );

      return itemErrorKey ? this.form.errors[itemErrorKey] : null;
    },
    itemTotalCost() {
      return Number(this.form.item_unit_cost || 0);
    },
    itemRowsTotalCost() {
      return this.itemRows.reduce((sum, row) => sum + Number(row.total_cost || 0), 0);
    },
    totalPages() {
      return Math.max(1, Math.ceil(this.itemRows.length / this.itemsPerPage));
    },
    paginatedItemRows() {
      const start = (this.page - 1) * this.itemsPerPage;

      return this.itemRows.slice(start, start + this.itemsPerPage).map((row, index) => ({
        ...row,
        originalIndex: start + index,
        displayIndex: start + index + 1,
      }));
    },
    paginationStart() {
      if (!this.itemRows.length) {
        return 0;
      }

      return (this.page - 1) * this.itemsPerPage + 1;
    },
    paginationEnd() {
      return Math.min(this.page * this.itemsPerPage, this.itemRows.length);
    },
    isEditingProject() {
      return Boolean(this.editingItem?._isPpmpProject);
    },
    isEditing() {
      return Boolean(this.editingItem) && !this.editingItem?._isPpmpProject;
    },
    planLabel() {
      switch (this.ppmp?.plan_type) {
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
    modalTitle() {
      if (this.isEditingProject) return `Edit ${this.planLabel} Project`;
      return this.isEditing
        ? `Edit ${this.planLabel} Item`
        : `Add Procurement Project`;
    },
    isFormValid() {
      const projectFieldsValid = Boolean(
        this.form.project_type &&
          this.form.recommended_mode_of_procurement &&
          this.form.pre_procurement_conference &&
          this.form.general_description_objective &&
          this.form.start_of_procurement_activity &&
          this.form.end_of_procurement_activity &&
          this.form.expected_delivery_date &&
          this.form.attached_supporting_documents &&
          this.form.remarks
      );

      if (this.isEditingProject) {
        if (this.hasItems === true) return projectFieldsValid && this.itemRows.length > 0;
        return projectFieldsValid && Number(this.form.project_total_budget || 0) > 0;
      }

      if (this.isEditing) {
        return projectFieldsValid && this.itemRows.length > 0;
      }

      if (this.hasItems === null) return false;

      if (this.hasItems === false) {
        return projectFieldsValid && Number(this.form.project_total_budget || 0) > 0;
      }

      return projectFieldsValid && this.itemRows.length > 0;
    },
    canAddItemRow() {
      return this.itemRows.length > 0;
    },
    submitLabel() {
      if (this.isEditingProject) return `Update ${this.planLabel} Project`;
      if (this.isEditing) return `Update ${this.planLabel} Item`;
      return this.mode === "draft" ? "Use Item" : `Add ${this.planLabel} Item`;
    },
  },
  watch: {
    "form.general_description_objective"(value) {
      this.clearErrorWhenFilled("general_description_objective", value);
    },
    "form.project_type"(value) {
      this.clearErrorWhenFilled("project_type", value);
    },
    "form.recommended_mode_of_procurement"(value) {
      this.clearErrorWhenFilled("recommended_mode_of_procurement", value);
    },
    "form.pre_procurement_conference"(value) {
      this.clearErrorWhenFilled("pre_procurement_conference", value);
    },
    "form.start_of_procurement_activity"(value) {
      this.clearErrorWhenFilled("start_of_procurement_activity", value);
    },
    "form.end_of_procurement_activity"(value) {
      this.clearErrorWhenFilled("end_of_procurement_activity", value);
    },
    "form.expected_delivery_date"(value) {
      this.clearErrorWhenFilled("expected_delivery_date", value);
    },
    "form.attached_supporting_documents"(value) {
      this.clearErrorWhenFilled("attached_supporting_documents", value);
    },
    "form.supporting_document_file"(value) {
      this.clearErrorWhenFilled("supporting_document_file", value);
    },
    "form.remarks"(value) {
      this.clearErrorWhenFilled("remarks", value);
    },
    hasItems(val) {
      if (val === false) {
        if (this.form.project_total_budget === null) {
          this.form.project_total_budget = 0;
        }
        this.syncTotalBudgetAmount();
      }
    },
    itemRows: {
      deep: true,
      handler(value) {
        if (value.length) {
          this.clearItemErrors();
        }

        this.clampItemPage();
      },
    },
    itemsPerPage() {
      this.page = 1;
      this.clampItemPage();
    },
  },
  methods: {
    show(editingItem = null) {
      this.form.clearErrors();
      this.form.reset();
      this.itemRows = [];
      this.page = 1;
      this.editingItem = editingItem;
      this.form.plan_type = this.ppmp?.plan_type || null;

      if (editingItem?._isPpmpProject) {
        this.editingItem = editingItem;
        this.hasItems = false;
        this.form.option = "update_project";
        this.form.target_ppmp_id = editingItem.ppmp_id;
        this.form.general_description_objective = editingItem.general_description_objective || "";
        this.form.project_type = editingItem.project_type || "";
        this.form.recommended_mode_of_procurement = editingItem.recommended_mode_of_procurement || "";
        this.form.pre_procurement_conference = editingItem.pre_procurement_conference || "No";
        this.form.start_of_procurement_activity = this.dateInputValue(editingItem.start_of_procurement_activity);
        this.form.end_of_procurement_activity = this.dateInputValue(editingItem.end_of_procurement_activity);
        this.form.expected_delivery_date = this.dateInputValue(editingItem.expected_delivery_date);
        this.form.attached_supporting_documents = editingItem.attached_supporting_documents || "";
        this.form.remarks = editingItem.remarks || "";
        this.form.project_total_budget = editingItem.project_total_budget || null;
        this.modal.show = true;
        this.syncTotalBudgetAmount();
        return;
      }

      if (editingItem) {
        this.hasItems = true;
        const entryItems = this.entryItemsForEdit(editingItem);
        const representativeItem = entryItems[0] || editingItem;

        this.form.option = "update_item";
        this.form.item_id = editingItem.id;
        this.form.project_type = editingItem.project_type || "";
        this.form.recommended_mode_of_procurement =
          editingItem.recommended_mode_of_procurement || "";
        this.form.pre_procurement_conference =
          editingItem.pre_procurement_conference || "No";
        this.form.general_description_objective =
          this.ppmp?.general_description_objective || this.ppmp?.title || "";
        this.form.item_quantity = editingItem.quantity || 1;
        this.form.start_of_procurement_activity =
          this.dateInputValue(
            editingItem.start_of_procurement_activity ||
              representativeItem.start_of_procurement_activity ||
              this.ppmp?.start_of_procurement_activity ||
              this.ppmp?.date
          );
        this.form.end_of_procurement_activity =
          this.dateInputValue(
            editingItem.end_of_procurement_activity ||
              representativeItem.end_of_procurement_activity
          );
        this.form.expected_delivery_date = this.dateInputValue(
          editingItem.expected_delivery_date ||
            representativeItem.expected_delivery_date
        );
        this.form.attached_supporting_documents =
          editingItem.attached_supporting_documents || "";
        this.form.supporting_document_file = null;
        this.form.remarks = editingItem.remarks || "";
        this.itemRows = entryItems.map((item) => this.itemToRow(item));
        this.modal.show = true;
        return;
      }

      const draftItem = this.mode === "draft" ? this.draftItem : null;

      if (draftItem) {
        this.form.project_type = draftItem.project_type || "";
        this.form.recommended_mode_of_procurement =
          draftItem.recommended_mode_of_procurement || "";
        this.form.pre_procurement_conference =
          draftItem.pre_procurement_conference || "No";
        this.form.general_description_objective =
          draftItem.general_description_objective || "";
        this.form.item_quantity = 1;
        this.form.start_of_procurement_activity =
          draftItem.start_of_procurement_activity || null;
        this.form.end_of_procurement_activity =
          draftItem.end_of_procurement_activity || null;
        this.form.expected_delivery_date = draftItem.expected_delivery_date || null;
        this.form.attached_supporting_documents =
          draftItem.attached_supporting_documents || "";
        this.form.supporting_document_file = draftItem.supporting_document_file || null;
        this.form.remarks = draftItem.remarks || "";
        this.itemRows = [
          {
            key: `${Date.now()}-${Math.random()}`,
            item_name: draftItem.item_name || "",
            item_description: draftItem.item_description || "",
            item_quantity: 1,
            item_unit_type_id: draftItem.item_unit_type_id ?? null,
            item_unit_cost: Number(draftItem.item_unit_cost || 0),
            total_cost: Number(draftItem.item_unit_cost || 0),
            item_category_id: draftItem.item_category_id ?? null,
          },
        ];
      } else {
        this.hasItems = null;
        this.form.item_quantity = 1;
        this.form.item_unit_cost = 0.0;
      }

      this.modal.show = true;
    },
    hide() {
      this.modal.show = false;
      this.form.clearErrors();
      this.form.reset();
      this.editingItem = null;
      this.hasItems = null;
      this.form.item_quantity = 1;
      this.form.item_unit_cost = 0.0;
      this.form.target_ppmp_id = null;
      this.form.project_total_budget = null;
      this.$refs.totalBudgetAmount?.empty();
      this.form.supporting_document_file = null;
      this.itemRows = [];
      this.page = 1;
      this.showHasItemsError = false;
      this.backendError = null;
    },
    submit() {
      this.backendError = null;
      this.showHasItemsError = false;
      this.form.clearErrors();

      if (!this.isEditing && !this.isEditingProject) {
        const errors = {};

        if (!this.form.general_description_objective?.trim())
          errors.general_description_objective = 'General description and objective is required.';
        if (!this.form.project_type)
          errors.project_type = 'Type of project is required.';
        if (!this.form.recommended_mode_of_procurement)
          errors.recommended_mode_of_procurement = 'Recommended mode of procurement is required.';
        if (!this.form.pre_procurement_conference)
          errors.pre_procurement_conference = 'Pre-procurement conference is required.';
        if (!this.form.start_of_procurement_activity)
          errors.start_of_procurement_activity = 'Start of procurement activity is required.';
        if (!this.form.end_of_procurement_activity)
          errors.end_of_procurement_activity = 'End of procurement activity is required.';
        if (!this.form.expected_delivery_date)
          errors.expected_delivery_date = 'Expected delivery date is required.';
        if (!this.form.attached_supporting_documents?.trim())
          errors.attached_supporting_documents = 'Supporting document type is required.';
        if (!this.form.supporting_document_file)
          errors.supporting_document_file = 'Attachment is required.';
        if (!this.form.remarks?.trim())
          errors.remarks = 'Remarks is required.';

        if (this.hasItems === null) {
          this.showHasItemsError = true;
        } else if (this.hasItems === true && this.itemRows.length === 0) {
          errors.items = 'At least one item is required.';
        } else if (this.hasItems === false && Number(this.form.project_total_budget || 0) <= 0) {
          errors.project_total_budget = 'Total Budget (ABC) must be greater than zero.';
        }

        if (Object.keys(errors).length > 0) {
          this.form.setError(errors);
        }

        if (Object.keys(errors).length > 0 || this.showHasItemsError) {
          return;
        }
      }

      const rowsToSubmit = this.itemRows;
      this.form.items = rowsToSubmit.map(({ key, total_cost, ...row }) => row);
      this.form.start_of_procurement_activity = this.monthValueToDate(this.form.start_of_procurement_activity);
      this.form.end_of_procurement_activity = this.monthValueToDate(this.form.end_of_procurement_activity);
      this.form.expected_delivery_date = this.monthValueToDate(this.form.expected_delivery_date);

      if (this.isEditingProject) {
        if (this.hasItems === false) {
          this.form.items = [];
          this.form.option = "update_project";
        } else {
          this.form.option = rowsToSubmit.length > 0 ? "add_item" : "update_project";
        }
        this.form.patch(`/faims/procurement-ppmp/${this.ppmp.id}`, {
          forceFormData: true,
          preserveScroll: true,
          onSuccess: (page) => this.handleSuccess(page),
          onError: () => this.restoreMonthValues(),
        });
        return;
      }

      if (this.isEditing) {
        const firstRow = rowsToSubmit[0];
        this.form.option = "update_item";
        this.form.item_id = this.editingItem.id;
        this.form.item_name = firstRow?.item_name || "";
        this.form.item_description = firstRow?.item_description || "";
        this.form.item_quantity = firstRow?.item_quantity || null;
        this.form.item_unit_type_id = firstRow?.item_unit_type_id || null;
        this.form.item_unit_cost = firstRow ? Number(firstRow.item_unit_cost || 0) : null;

        this.form.patch(`/faims/procurement-ppmp/${this.ppmp.id}`, {
          forceFormData: true,
          preserveScroll: true,
          onSuccess: (page) => this.handleSuccess(page),
          onError: () => this.restoreMonthValues(),
        });
        return;
      }

      this.form.option = "add_item";

      if (this.hasItems === false) {
        this.form.items = [];
      }

      if (this.mode === "draft" && this.hasItems !== false) {
        const firstRow = rowsToSubmit[0];

        this.$emit("draft", {
          item_name: firstRow?.item_name,
          item_description: firstRow.item_description,
          project_type: this.form.project_type,
          item_category_id: firstRow.item_category_id ?? null,
          recommended_mode_of_procurement: this.form.recommended_mode_of_procurement,
          pre_procurement_conference: this.form.pre_procurement_conference,
          general_description_objective: this.form.general_description_objective,
          item_quantity: 1,
          item_unit_type_id: firstRow.item_unit_type_id,
          item_unit_cost: Number(firstRow.item_unit_cost || 0),
          items: this.form.items,
          start_of_procurement_activity: this.form.start_of_procurement_activity,
          end_of_procurement_activity: this.form.end_of_procurement_activity,
          expected_delivery_date: this.form.expected_delivery_date,
          attached_supporting_documents: this.form.attached_supporting_documents,
          supporting_document_file: this.form.supporting_document_file,
          remarks: this.form.remarks,
          total_cost: firstRow.total_cost,
        });
        this.hide();
        return;
      }

      if (!this.ppmp?.id) {
        this.backendError = "Unable to save: no procurement plan selected. Please refresh the page and try again.";
        return;
      }

      this.form.patch(`/faims/procurement-ppmp/${this.ppmp.id}`, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: (page) => this.handleSuccess(page),
        onError: (errors) => {
          this.restoreMonthValues();
          if (!Object.keys(errors || {}).length) {
            this.backendError = "An error occurred while saving. Please try again.";
          }
        },
      });
    },
    handleSuccess(page) {
      const flash = page?.props?.flash;
      if (flash && flash.status === false) {
        this.backendError = flash.message || "An error occurred while saving. Please try again.";
        this.restoreMonthValues();
        return;
      }
      this.hide();
    },
    restoreMonthValues() {
      this.form.start_of_procurement_activity = this.dateInputValue(this.form.start_of_procurement_activity);
      this.form.end_of_procurement_activity = this.dateInputValue(this.form.end_of_procurement_activity);
      this.form.expected_delivery_date = this.dateInputValue(this.form.expected_delivery_date);
    },
    onTotalBudgetAmount(val) {
      this.form.project_total_budget = this.cleanCurrency(val);
      if (Number(this.form.project_total_budget) > 0) {
        this.form.clearErrors('project_total_budget');
      }
    },
    cleanCurrency(value) {
      if (!value) return 0;
      const cleaned = value.toString().replace(/[^0-9.]/g, "");
      return parseFloat(cleaned) || 0;
    },
    syncTotalBudgetAmount() {
      this.$nextTick(() => {
        const budget = Number(this.form.project_total_budget || 0);
        this.$refs.totalBudgetAmount?.emitValue(budget.toFixed(2));
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
      return Number.isInteger(amount)
        ? amount.toString()
        : amount.toFixed(4).replace(/\.?0+$/, "");
    },
    saveItemRow({ row, editIndex }) {
      if (editIndex === null) {
        this.itemRows.push(row);
        this.page = this.totalPages;
      } else {
        this.itemRows.splice(editIndex, 1, {
          ...row,
          key: this.itemRows[editIndex]?.key || row.key,
        });
      }
    },
    openItemRowModal() {
      this.$refs.itemTableModal?.show();
    },
    editItemRow(index) {
      const row = this.itemRows[index];

      if (!row) {
        return;
      }

      this.$refs.itemTableModal?.show(row, index);
    },
    removeItemRow(index) {
      this.itemRows.splice(index, 1);
      this.clampItemPage();
    },
    clampItemPage() {
      if (this.page > this.totalPages) {
        this.page = this.totalPages;
      }

      if (this.page < 1) {
        this.page = 1;
      }
    },
    entryItemsForEdit(editingItem) {
      return Array.isArray(editingItem.__entry_items) && editingItem.__entry_items.length
        ? editingItem.__entry_items
        : [editingItem];
    },
    itemToRow(item) {
      return {
        id: item.id,
        key: `${item.id || "new"}-${Date.now()}-${Math.random()}`,
        item_name: item.name || item.item_name || "",
        item_description: item.description || item.item_description || "",
        item_quantity: item.quantity || item.item_quantity || 1,
        funded_quantity: item.quantity || item.item_quantity || 1,
        requested_quantity: item.quantity || item.item_quantity || 1,
        unfunded_quantity: 0,
        is_partial_funding: false,
        item_unit_type_id: item.item_unit_type_id ?? null,
        item_unit_cost: Number(item.unit_price || item.item_unit_cost || 0),
        total_cost: Number(item.abc || item.total_cost || 0),
        item_category_id: item.item_category_id ?? null,
        q1_indicative_amount: item.q1_indicative_amount ?? null,
        q2_indicative_amount: item.q2_indicative_amount ?? null,
        q3_indicative_amount: item.q3_indicative_amount ?? null,
        q4_indicative_amount: item.q4_indicative_amount ?? null,
      };
    },
    dateInputValue(value) {
      if (!value) {
        return null;
      }

      const text = String(value).trim();
      const fullMatch = text.match(/^(\d{4}-\d{2})-\d{2}/);

      if (fullMatch) {
        return fullMatch[1];
      }

      const monthMatch = text.match(/^(\d{4}-\d{2})$/);

      if (monthMatch) {
        return monthMatch[1];
      }

      const date = new Date(text);

      return Number.isNaN(date.getTime())
        ? null
        : `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, "0")}`;
    },
    monthValueToDate(value) {
      if (!value) {
        return null;
      }

      const match = String(value).trim().match(/^(\d{4}-\d{2})$/);

      return match ? `${match[1]}-01` : value;
    },
    onCategoryAdded(category) {
      const alreadyExists = this.extraItemCategories.some(
        (o) => Number(o.value ?? o.id) === Number(category.value ?? category.id)
      );

      if (!alreadyExists) {
        this.extraItemCategories.push(category);
      }
    },
    fieldError(field) {
      return this.form.errors?.[field] || "";
    },
    hasFieldError(field) {
      return Boolean(this.fieldError(field));
    },
    inputInvalidClass(field) {
      return { "is-invalid": this.hasFieldError(field) };
    },
    multiselectInvalidClass(field) {
      return { "is-invalid": this.hasFieldError(field) };
    },
    clearErrorWhenFilled(field, value) {
      if (this.hasValue(value)) {
        this.form.clearErrors(field);
      }
    },
    hasValue(value) {
      if (Array.isArray(value)) {
        return value.length > 0;
      }

      if (value && typeof value === "object") {
        return true;
      }

      return String(value ?? "").trim() !== "";
    },
    clearItemErrors() {
      const itemErrorKeys = Object.keys(this.form.errors || {}).filter(
        (key) =>
          key === "items" ||
          key === "item_name" ||
          key === "item_description" ||
          key === "item_unit_type_id" ||
          key === "item_unit_cost" ||
          key.startsWith("items.")
      );

      if (itemErrorKeys.length) {
        this.form.clearErrors(...itemErrorKeys);
      }
    },
    unitTypeName(unitTypeId, quantity = 1) {
      const unitType = this.unitTypeOptions.find(
        (option) => Number(option.value ?? option.id) === Number(unitTypeId)
      );

      if (!unitType) {
        return "-";
      }

      return Number(quantity || 0) > 1
        ? unitType.name_long ||
            unitType.name_short ||
            unitType.name ||
            unitType.label ||
            "-"
        : unitType.name_short ||
            unitType.name_long ||
            unitType.name ||
            unitType.label ||
            "-";
    },
    setSupportingDocumentFile(file) {
      if (!file) {
        return;
      }

      this.form.clearErrors("supporting_document_file");
      this.form.supporting_document_file = file;
    },
    rejectSupportingDocumentFile({ message }) {
      this.form.setError("supporting_document_file", message);
      this.removeSupportingDocument();
    },
    removeSupportingDocument() {
      this.form.supporting_document_file = null;
    },
  },
};
</script>

<style scoped>
.customform {
  --procurement-create-table-bg: #ffffff;
  --procurement-create-table-border: rgba(91, 105, 153, 0.14);
  --procurement-create-table-row-alt: #f8fafc;
  --procurement-create-table-row-hover: rgba(64, 81, 137, 0.06);
  --procurement-create-header-bg: #f8fafc;
  --procurement-create-text: #182039;
  --procurement-create-unit-badge-bg: rgba(102, 126, 234, 0.12);
  --procurement-create-unit-badge-text: #405189;
}

.item-total-preview {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 12px 14px;
  border: 1px solid #e9ebec;
  border-radius: 8px;
  background: #f8f9fb;
}

.item-total-preview span {
  color: #6b7280;
  font-size: 12px;
  font-weight: 700;
}

.item-total-preview strong {
  color: #0f766e;
  font-size: 15px;
  font-weight: 800;
}

.ppmp-page-size {
  width: 76px;
}

:deep(.multiselect.is-invalid),
:deep(.multiselect.is-invalid .multiselect-wrapper) {
  border-color: #f06548 !important;
}

:deep(.multiselect.is-invalid) {
  box-shadow: 0 0 0 0.125rem rgba(240, 101, 72, 0.12);
}

.items-table-toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 8px;
}

.items-table-title {
  margin: 0;
  color: var(--procurement-create-text);
  font-size: 14px;
  font-weight: 800;
}

.items-table-subtitle {
  display: block;
  margin-top: 2px;
  color: #6b7280;
  font-size: 12px;
  font-weight: 600;
}

.items-table-container {
  border-radius: 10px;
  overflow: hidden;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
  border: 1px solid var(--procurement-create-table-border);
}

.items-table {
  width: 100%;
  min-width: 920px;
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

.items-table tfoot td {
  border-bottom: 0;
  background: var(--procurement-create-table-row-alt);
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
}

.item-unit {
  font-weight: 600;
  color: var(--procurement-create-text);
}

.item-cost {
  font-weight: 700;
  color: #28a745;
  font-family: "Courier New", monospace;
}

.ppmp-item-entry-table .ppmp-item-entry-row td {
  background: var(--procurement-create-table-row-alt);
}

.ppmp-item-entry-table .ppmp-item-entry-row:hover td {
  background: var(--procurement-create-table-row-alt);
}

.ppmp-item-entry-table textarea {
  min-height: 38px;
  resize: vertical;
}

.items-empty-row td {
  color: #6b7280;
  font-size: 13px;
  font-weight: 600;
}

.grand-total-label {
  color: #6b7280;
  font-weight: 800;
}

.grand-total-amount {
  color: #28a745;
  font-family: "Courier New", monospace;
  font-weight: 800;
}

@media (max-width: 576px) {
  .items-table-toolbar {
    align-items: stretch;
    flex-direction: column;
  }
}

.month-picker-wrap {
  display: block;
}

.month-picker-icon {
  position: absolute;
  top: 50%;
  left: 10px;
  transform: translateY(-50%);
  pointer-events: none;
  color: #6c757d;
  z-index: 2;
  font-size: 14px;
}

:deep(.month-picker-wrap .flatpickr-alt-input),
:deep(.month-picker-wrap input.form-control) {
  padding-left: 2rem;
  width: 100%;
}
</style>
