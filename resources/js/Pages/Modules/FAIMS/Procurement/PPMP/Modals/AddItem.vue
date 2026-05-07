<template>
  <b-modal
    v-model="modal.show"
    header-class="p-3"
    title="Add PPMP Item"
    size="xl"
    class="v-modal-custom"
    modal-class="zoomIn"
    centered
    no-close-on-backdrop
  >
    <form class="customform">
      <BRow>
        <BCol lg="6" >
          <InputLabel value="Type of Project to be Procured" :message="form.errors.project_type" />
          <Multiselect
            :options="projectTypeOptions"
            v-model="form.project_type"
            :searchable="true"
            label="name"
            value-prop="name"
            placeholder="Select project type"
          />
        </BCol>

        <BCol lg="6">
          <InputLabel value="Recommended Mode of Procurement" :message="form.errors.recommended_mode_of_procurement" />
          <Multiselect
            :options="modeOfProcurementOptions"
            v-model="form.recommended_mode_of_procurement"
            :searchable="true"
            label="name"
            value-prop="name"
            placeholder="Select mode"
          />
        </BCol>

        <BCol lg="6" class="mt-3">
          <InputLabel value="End of Procurement Activity" :message="form.errors.end_of_procurement_activity" />
          <TextInput
            v-model="form.end_of_procurement_activity"
            type="date"
            class="form-control"
          />
        </BCol>

        <BCol lg="6" class="mt-3">
          <InputLabel value="Expected Delivery Date" :message="form.errors.expected_delivery_date" />
          <TextInput
            v-model="form.expected_delivery_date"
            type="date"
            class="form-control"
          />
        </BCol>

        <BCol lg="12" class="mt-3">
          <InputLabel value="Attached Supporting Document Name" :message="form.errors.attached_supporting_documents" />
          <TextInput
            v-model="form.attached_supporting_documents"
            type="text"
            class="form-control"
            placeholder="Document name or type"
          />
        </BCol>

        <BCol lg="12" class="mt-3">
          <InputLabel value="Attachment" :message="form.errors.supporting_document_file" />
          <div
            :class="['supporting-document-dropzone', { 'is-dragging': isSupportingDocumentDragging }]"
            @dragenter.prevent="isSupportingDocumentDragging = true"
            @dragover.prevent="isSupportingDocumentDragging = true"
            @dragleave.prevent="isSupportingDocumentDragging = false"
            @drop.prevent="handleSupportingDocumentDrop"
            @click="openSupportingDocumentPicker"
          >
            <input
              ref="supportingDocumentInput"
              type="file"
              class="d-none"
              @change="handleSupportingDocumentSelect"
            />
            <div class="supporting-document-dropzone__icon">
              <i class="ri-upload-cloud-2-line"></i>
            </div>
            <div>
              <div class="supporting-document-dropzone__title">
                {{ supportingDocumentName || "Drop attachment here or click to browse" }}
              </div>
              <div class="supporting-document-dropzone__hint">PDF, image, Word, or spreadsheet files up to 10 MB</div>
            </div>
          </div>
          <div v-if="supportingDocumentName" class="supporting-document-file">
            <span>{{ supportingDocumentName }}</span>
            <button type="button" class="btn btn-sm btn-link text-danger p-0" @click="removeSupportingDocument">
              Remove
            </button>
          </div>
        </BCol>

        <BCol lg="12" class="mt-3">
          <InputLabel value="Remarks" :message="form.errors.remarks" />
          <textarea
            v-model="form.remarks"
            class="form-control"
            rows="3"
            placeholder="Remarks"
          ></textarea>
        </BCol>

     

        <BCol lg="12" class="mt-3">
        <BRow>
        <BCol lg="12" class="text-end mb-1">
             <b-button  size="sm" >
              Add Item
            </b-button>
        </BCol>
     
        </BRow>
        
       
          <div class="items-table-container">
            <div class="table-responsive">
            <table class="items-table ppmp-item-entry-table">
              <thead>
                <tr>
                  <th style="width: 48px" class="text-center">#</th>
                  <th style="width: 18%">Item Name</th>
                  <th>Description</th>
                  <th style="width: 120px">Unit Type</th>
                  <th style="width: 140px" class="text-end">Unit Cost</th>
                  <th style="width: 54px"></th>
                </tr>
              </thead>
              <tbody>
                <tr class="ppmp-item-entry-row">
                  <td class="text-center text-muted">New</td>
                  <td>
                    <div class="item-name-autocomplete">
                      <TextInput
                        v-model="form.item_name"
                        type="text"
                        class="form-control form-control-sm"
                        placeholder="Item name"
                        autocomplete="off"
                        @focus="handleItemNameFocus"
                        @blur="handleItemNameBlur"
                        @keydown.down.prevent="moveSuggestionSelection(1)"
                        @keydown.up.prevent="moveSuggestionSelection(-1)"
                        @keydown.enter.prevent="confirmActiveSuggestion"
                        @keydown.esc.prevent="closeItemNameDropdown"
                      />

                      <div v-if="shouldShowItemNameDropdown" class="item-name-suggestions">
                        <button
                          v-for="(suggestion, index) in itemNameSuggestions"
                          :key="suggestion"
                          type="button"
                          :class="[
                            'item-name-suggestion',
                            { 'item-name-suggestion--active': index === activeSuggestionIndex },
                          ]"
                          @mousedown.prevent="selectItemNameSuggestion(suggestion)"
                        >
                          {{ suggestion }}
                        </button>
                      </div>
                    </div>
                  </td>
                  <td>
                    <textarea
                      v-model="form.item_description"
                      class="form-control form-control-sm"
                      rows="2"
                      placeholder="Description"
                    ></textarea>
                  </td>
                  <td>
                    <Multiselect
                      :options="unitTypeOptions"
                      v-model="form.item_unit_type_id"
                      :searchable="true"
                      :label="itemUnitTypeLabel"
                      value-prop="value"
                      placeholder="Unit"
                    />
                  </td>
                  <td class="text-end">
                    <TextInput
                      v-model="form.item_unit_cost"
                      type="number"
                      min="0"
                      step="0.01"
                      class="form-control form-control-sm text-end"
                      placeholder="0.00"
                    />
                  </td>
                  <td class="text-center">
                    <b-button
                      type="button"
                      size="sm"
                      variant="primary"
                      :disabled="!canAddItemRow"
                      @click="addItemRow"
                    >
                      <i class="ri-add-line me-1"></i>
                      Add
                    </b-button>
                  </td>
                </tr>
                <tr v-for="(row, index) in itemRows" :key="row.key" class="item-row">
                  <td class="text-center item-number">{{ index + 1 }}</td>
                  <td class="item-description">
                    <span>{{ row.item_name }}</span>
                  </td>
                  <td class="item-description text-muted small" v-html="row.item_description"></td>
                  <td class="item-unit">
                    <span class="unit-badge">{{ unitTypeName(row.item_unit_type_id) }}</span>
                  </td>
                  <td class="text-end item-cost">{{ formatCurrency(row.item_unit_cost) }}</td>
                  <td class="text-center">
                    <b-button
                      type="button"
                      variant="danger"
                      size="sm"
                      class="btn-icon"
                      style="border-radius: 8px;"
                      @click="removeItemRow(index)"
                    >
                      <i class="ri-delete-bin-line"></i>
                    </b-button>
                  </td>
                </tr>
              </tbody>
            </table>
            </div>
          </div>
        </BCol>

           <BCol lg="12" class="mt-3">
          <div class="item-total-preview">
            <span>{{ itemRows.length ? "Batch Total Amount" : "Total Amount" }}</span>
            <strong>{{ formatCurrency(itemRows.length ? itemRowsTotalCost : itemTotalCost) }}</strong>
          </div>
          <div v-if="form.errors.item" class="text-danger small fw-semibold mt-2">
            {{ form.errors.item }}
          </div>
        </BCol>
      </BRow>
    </form>

    <template v-slot:footer>
      <b-button @click="hide" variant="light" block>Cancel</b-button>
      <b-button
        @click="submit"
        variant="primary"
        :disabled="form.processing || !isFormValid"
        block
      >
        {{ form.processing ? "Saving..." : submitLabel }}
      </b-button>
    </template>
  </b-modal>
</template>

<script>
import { useForm } from "@inertiajs/vue3";
import Multiselect from "@vueform/multiselect";
import InputLabel from "@/Shared/Components/Forms/InputLabel.vue";
import TextInput from "@/Shared/Components/Forms/TextInput.vue";

export default {
  components: { Multiselect, InputLabel, TextInput },
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
      modal: {
        show: false,
      },
      form: useForm({
        option: "add_item",
        item_name: "",
        item_description: "",
        project_type: "",
        recommended_mode_of_procurement: "",
        item_quantity: 1,
        item_unit_type_id: null,
        item_unit_cost: null,
        items: [],
        end_of_procurement_activity: null,
        expected_delivery_date: null,
        attached_supporting_documents: "",
        supporting_document_file: null,
        remarks: "",
      }),
      itemNameSuggestions: [],
      itemRows: [],
      itemNameLookupTimeout: null,
      itemNameBlurTimeout: null,
      latestItemNameKeyword: "",
      isItemNameFocused: false,
      activeSuggestionIndex: -1,
      isSupportingDocumentDragging: false,
    };
  },
  watch: {
    "form.item_name"(value) {
      if (!this.modal.show) {
        return;
      }

      this.queueItemNameSuggestions(value);
    },
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
    itemUnitTypeLabel() {
      return "name_short";
    },
    itemTableError() {
      return this.form.errors.item_name ||
        this.form.errors.item_description ||
        this.form.errors.item_unit_type_id ||
        this.form.errors.item_unit_cost ||
        this.form.errors.items ||
        null;
    },
    itemTotalCost() {
      return Number(this.form.item_unit_cost || 0);
    },
    itemRowsTotalCost() {
      return this.itemRows.reduce((sum, row) => sum + Number(row.total_cost || 0), 0);
    },
    isFormValid() {
      return Boolean(
        this.form.project_type &&
        this.form.recommended_mode_of_procurement &&
        (this.itemRows.length > 0 || this.canAddItemRow)
      );
    },
    canAddItemRow() {
      return Boolean(
        this.form.item_name &&
        this.form.item_description &&
        this.form.item_unit_type_id &&
        Number(this.form.item_unit_cost) >= 0
      );
    },
    shouldShowItemNameDropdown() {
      return this.isItemNameFocused && this.itemNameSuggestions.length > 0;
    },
    submitLabel() {
      return this.mode === "draft" ? "Use Item" : "Add Item";
    },
    supportingDocumentName() {
      return this.form.supporting_document_file?.name || "";
    },
  },
  beforeUnmount() {
    this.clearItemNameSuggestionState();
  },
  methods: {
    show() {
      this.form.clearErrors();
      this.form.reset();
      this.itemRows = [];
      const draftItem = this.mode === "draft" ? this.draftItem : null;

      if (draftItem) {
        this.form.item_name = draftItem.item_name || "";
        this.form.item_description = draftItem.item_description || "";
        this.form.project_type = draftItem.project_type || "";
        this.form.recommended_mode_of_procurement = draftItem.recommended_mode_of_procurement || "";
        this.form.item_quantity = 1;
        this.form.item_unit_type_id = draftItem.item_unit_type_id ?? null;
        this.form.item_unit_cost = Number(draftItem.item_unit_cost || 0);
        this.form.end_of_procurement_activity = draftItem.end_of_procurement_activity || null;
        this.form.expected_delivery_date = draftItem.expected_delivery_date || null;
        this.form.attached_supporting_documents = draftItem.attached_supporting_documents || "";
        this.form.supporting_document_file = draftItem.supporting_document_file || null;
        this.form.remarks = draftItem.remarks || "";
      } else {
        this.form.item_quantity = 1;
        this.form.item_unit_cost = 0.0;
      }

      this.modal.show = true;
      this.fetchItemNameSuggestions("");
    },
    hide() {
      this.modal.show = false;
      this.form.clearErrors();
      this.form.reset();
      this.form.item_quantity = 1;
      this.form.item_unit_cost = 0.0;
      this.form.supporting_document_file = null;
      this.itemRows = [];
      if (this.$refs.supportingDocumentInput) {
        this.$refs.supportingDocumentInput.value = "";
      }
      this.clearItemNameSuggestionState();
    },
    submit() {
      if (!this.isFormValid) {
        return;
      }

      this.form.option = "add_item";
      const rowsToSubmit = this.itemRows.length ? this.itemRows : [this.currentItemRow()];
      this.form.items = rowsToSubmit.map(({ key, total_cost, ...row }) => row);

      if (this.mode === "draft") {
        const firstRow = rowsToSubmit[0];
        this.$emit("draft", {
          item_name: firstRow.item_name,
          item_description: firstRow.item_description,
          project_type: this.form.project_type,
          recommended_mode_of_procurement: this.form.recommended_mode_of_procurement,
          item_quantity: 1,
          item_unit_type_id: firstRow.item_unit_type_id,
          item_unit_cost: Number(firstRow.item_unit_cost || 0),
          items: this.form.items,
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
        return;
      }

      this.form.patch(`/faims/procurement-ppmp/${this.ppmp.id}`, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => this.hide(),
      });
    },
    formatCurrency(value) {
      return new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
      }).format(Number(value || 0));
    },
    currentItemRow() {
      const quantity = 1;
      const unitCost = Number(this.form.item_unit_cost || 0);

      return {
        key: `${Date.now()}-${Math.random()}`,
        item_name: this.form.item_name,
        item_description: this.form.item_description,
        item_quantity: quantity,
        item_unit_type_id: this.form.item_unit_type_id,
        item_unit_cost: unitCost,
        total_cost: quantity * unitCost,
      };
    },
    addItemRow() {
      if (!this.canAddItemRow) {
        return;
      }

      this.itemRows.push(this.currentItemRow());
      this.form.item_name = "";
      this.form.item_description = "";
      this.form.item_quantity = 1;
      this.form.item_unit_type_id = null;
      this.form.item_unit_cost = 0.0;
    },
    removeItemRow(index) {
      this.itemRows.splice(index, 1);
    },
    unitTypeName(unitTypeId) {
      const unitType = this.unitTypeOptions.find((option) => Number(option.value) === Number(unitTypeId));

      if (!unitType) {
        return "-";
      }

      return unitType.name_short || unitType.name || unitType.name_long || "-";
    },
    openSupportingDocumentPicker() {
      this.$refs.supportingDocumentInput?.click();
    },
    handleSupportingDocumentSelect(event) {
      this.setSupportingDocumentFile(event.target.files?.[0] || null);
    },
    handleSupportingDocumentDrop(event) {
      this.isSupportingDocumentDragging = false;
      this.setSupportingDocumentFile(event.dataTransfer.files?.[0] || null);
    },
    setSupportingDocumentFile(file) {
      if (!file) {
        return;
      }

      this.form.supporting_document_file = file;
    },
    removeSupportingDocument() {
      this.form.supporting_document_file = null;

      if (this.$refs.supportingDocumentInput) {
        this.$refs.supportingDocumentInput.value = "";
      }
    },
    handleItemNameFocus() {
      clearTimeout(this.itemNameBlurTimeout);
      this.isItemNameFocused = true;
      this.activeSuggestionIndex = -1;
      this.ensureItemNameSuggestions();
    },
    handleItemNameBlur() {
      this.itemNameBlurTimeout = setTimeout(() => {
        this.closeItemNameDropdown();
      }, 120);
    },
    ensureItemNameSuggestions() {
      if (!this.itemNameSuggestions.length) {
        this.fetchItemNameSuggestions(this.form.item_name || "");
      }
    },
    closeItemNameDropdown() {
      this.isItemNameFocused = false;
      this.activeSuggestionIndex = -1;
    },
    moveSuggestionSelection(direction) {
      if (!this.itemNameSuggestions.length) {
        return;
      }

      this.isItemNameFocused = true;

      if (this.activeSuggestionIndex === -1) {
        this.activeSuggestionIndex = direction > 0 ? 0 : this.itemNameSuggestions.length - 1;
        return;
      }

      const nextIndex = this.activeSuggestionIndex + direction;

      if (nextIndex < 0) {
        this.activeSuggestionIndex = this.itemNameSuggestions.length - 1;
      } else if (nextIndex >= this.itemNameSuggestions.length) {
        this.activeSuggestionIndex = 0;
      } else {
        this.activeSuggestionIndex = nextIndex;
      }
    },
    confirmActiveSuggestion() {
      if (
        this.activeSuggestionIndex < 0 ||
        this.activeSuggestionIndex >= this.itemNameSuggestions.length
      ) {
        return;
      }

      this.selectItemNameSuggestion(this.itemNameSuggestions[this.activeSuggestionIndex]);
    },
    selectItemNameSuggestion(suggestion) {
      this.form.item_name = suggestion;
      this.closeItemNameDropdown();
    },
    queueItemNameSuggestions(keyword) {
      clearTimeout(this.itemNameLookupTimeout);
      this.itemNameLookupTimeout = setTimeout(() => {
        this.fetchItemNameSuggestions(keyword);
      }, 250);
    },
    fetchItemNameSuggestions(keyword = "") {
      const searchKeyword = (keyword || "").trim();
      this.latestItemNameKeyword = searchKeyword;

      axios
        .get("/faims/procurements/create", {
          params: {
            option: "item_names",
            keyword: searchKeyword,
          },
        })
        .then((response) => {
          if (this.latestItemNameKeyword !== searchKeyword) {
            return;
          }

          this.itemNameSuggestions = Array.isArray(response.data) ? response.data : [];
          this.activeSuggestionIndex = this.itemNameSuggestions.length ? 0 : -1;
        })
        .catch((err) => {
          console.log(err);
          this.itemNameSuggestions = [];
          this.activeSuggestionIndex = -1;
        });
    },
    clearItemNameSuggestionState() {
      clearTimeout(this.itemNameLookupTimeout);
      clearTimeout(this.itemNameBlurTimeout);
      this.itemNameSuggestions = [];
      this.latestItemNameKeyword = "";
      this.closeItemNameDropdown();
    },
  },
};
</script>

<style scoped>
.customform {
  --procurement-create-table-bg: #ffffff;
  --procurement-create-table-border: rgba(91, 105, 153, .14);
  --procurement-create-table-row-alt: #f8fafc;
  --procurement-create-table-row-hover: rgba(64, 81, 137, .06);
  --procurement-create-header-bg: #f8fafc;
  --procurement-create-text: #182039;
  --procurement-create-unit-badge-bg: rgba(102, 126, 234, .12);
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

.item-name-autocomplete {
  position: relative;
}

.item-name-suggestions {
  position: absolute;
  top: calc(100% + 0.45rem);
  left: 0;
  right: 0;
  z-index: 30;
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
  max-height: 220px;
  overflow-y: auto;
  padding: 0.45rem;
  border: 1px solid rgba(191, 219, 254, 0.9);
  border-radius: 8px;
  background: #fff;
  box-shadow: 0 18px 30px rgba(15, 23, 42, 0.14);
}

.item-name-suggestion {
  width: 100%;
  padding: 0.65rem 0.75rem;
  border: 0;
  border-radius: 6px;
  background: transparent;
  color: #1e293b;
  font-size: 0.95rem;
  text-align: left;
}

.item-name-suggestion:hover,
.item-name-suggestion--active {
  background: #eaf2ff;
  color: #2846a6;
}

.supporting-document-dropzone {
  display: flex;
  align-items: center;
  gap: 12px;
  min-height: 92px;
  padding: 16px;
  border: 1px dashed #b7c4d8;
  border-radius: 8px;
  background: #f8fafc;
  color: #334155;
  cursor: pointer;
  transition: border-color .15s ease, background-color .15s ease, box-shadow .15s ease;
}

.supporting-document-dropzone:hover,
.supporting-document-dropzone.is-dragging {
  border-color: #405189;
  background: #eef4ff;
  box-shadow: 0 8px 20px rgba(64, 81, 137, .12);
}

.supporting-document-dropzone__icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 44px;
  height: 44px;
  flex: 0 0 44px;
  border-radius: 8px;
  background: #e0ebff;
  color: #405189;
  font-size: 24px;
}

.supporting-document-dropzone__title {
  font-weight: 700;
}

.supporting-document-dropzone__hint {
  margin-top: 2px;
  color: #64748b;
  font-size: 12px;
}

.supporting-document-file {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-top: 8px;
  padding: 8px 10px;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  background: #fff;
  color: #1f2937;
  font-size: 13px;
}

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

.ppmp-item-entry-table textarea {
  min-height: 38px;
  resize: vertical;
}
</style>
