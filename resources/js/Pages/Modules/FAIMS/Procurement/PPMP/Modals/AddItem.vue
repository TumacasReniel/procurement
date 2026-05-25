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
          <InputLabel value="General Description and Objective" :message="form.errors.general_description_objective" />
          <textarea
            v-model="form.general_description_objective"
            class="form-control"
            rows="3"
            placeholder="General description and objective of the project to be procured"
          ></textarea>
        </BCol>
        <BCol lg="4" >
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

        <BCol lg="4">
          <InputLabel value="Item Category" :message="form.errors.item_category_id" />
          <Multiselect
            :options="itemCategoryOptions"
            v-model="form.item_category_id"
            :searchable="true"
            label="name"
            placeholder="Select item category"
          />
        </BCol>

        <BCol lg="4">
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

        
        <BCol lg="4" class="mt-3">
          <InputLabel value="Start of Procurement Activity" :message="form.errors.start_of_procurement_activity" />
          <TextInput
            v-model="form.start_of_procurement_activity"
            type="date"
            class="form-control"
          />
        </BCol>

        <BCol lg="4" class="mt-3">
          <InputLabel value="End of Procurement Activity" :message="form.errors.end_of_procurement_activity" />
          <TextInput
            v-model="form.end_of_procurement_activity"
            type="date"
            class="form-control"
          />
        </BCol>

        <BCol lg="4" class="mt-3">
          <InputLabel value="Expected Delivery Date" :message="form.errors.expected_delivery_date" />
          <TextInput
            v-model="form.expected_delivery_date"
            type="date"
            class="form-control"
          />
        </BCol>

        <BCol lg="6" class="mt-3">
          <InputLabel value="Attached Supporting Document Name" :message="form.errors.attached_supporting_documents" />
          <TextInput
            v-model="form.attached_supporting_documents"
            type="text"
            class="form-control"
            placeholder="Document name or type"
          />
        </BCol>

        <BCol lg="6" class="mt-3">
          <InputLabel value="Pre-Procurement Conference" :message="form.errors.pre_procurement_conference" />
          <Multiselect
            :options="preProcurementConferenceOptions"
            v-model="form.pre_procurement_conference"
            :searchable="false"
            label="label"
            value-prop="value"
            placeholder="Select option"
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
              accept="application/pdf,.pdf"
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
              <div class="supporting-document-dropzone__hint">PDF files only, up to 10 MB</div>
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
          <div class="items-table-toolbar">
            <div>
              <h6 class="items-table-title">Items</h6>
              <span class="items-table-subtitle">
                {{ itemRows.length }} item{{ itemRows.length === 1 ? "" : "s" }} {{ isEditing ? "selected" : "queued" }}
              </span>
            </div>
            <b-button
              v-if="!isEditing"
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
                <tr v-for="(row, index) in itemRows" :key="row.key" class="item-row">
                  <td class="text-center item-number">{{ index + 1 }}</td>
                  <td class="item-description">
                    <span>{{ row.item_name }}</span>
                  </td>
                  <td class="item-description text-muted small" v-html="row.item_description"></td>
                  <td class="item-unit">
                    <span class="unit-badge">{{ unitTypeName(row.item_unit_type_id, row.item_quantity) }}</span>
                  </td>
                  <td class="text-end item-quantity">{{ formatQuantity(row.item_quantity) }}</td>
                  <td class="text-end item-cost">{{ formatCurrency(row.item_unit_cost) }}</td>
                  <td class="text-end item-cost">{{ formatCurrency(row.total_cost) }}</td>
                  <td class="text-center">
                    <div class="d-flex justify-content-center gap-1">
                      <b-button
                        type="button"
                        variant="success"
                        size="sm"
                        class="btn-icon"
                        style="border-radius: 8px;"
                        @click="editItemRow(index)"
                      >
                        <i class="ri-edit-2-line"></i>
                      </b-button>
                      <b-button
                        v-if="!isEditing"
                        type="button"
                        variant="danger"
                        size="sm"
                        class="btn-icon"
                        style="border-radius: 8px;"
                        @click="removeItemRow(index)"
                      >
                        <i class="ri-delete-bin-line"></i>
                      </b-button>
                    </div>
                  </td>
                </tr>
                <tr v-if="!itemRows.length" class="items-empty-row">
                  <td colspan="8" class="text-center">
                    Click Add Item to open the item form and queue an item in this table.
                  </td>
                </tr>
              </tbody>
              <tfoot>
                <tr class="grand-total-row">
                  <td colspan="6" class="text-end grand-total-label">Queued Total:</td>
                  <td class="text-end grand-total-amount">
                    {{ formatCurrency(itemRows.length ? itemRowsTotalCost : itemTotalCost) }}
                  </td>
                  <td></td>
                </tr>
              </tfoot>
            </table>
            </div>
          </div>
        </BCol>

           <BCol lg="12" class="mt-3">
          <div class="item-total-preview">
            <span>{{ isEditing ? "Updated Total Amount" : (itemRows.length ? "Batch Total Amount" : "Total Amount") }}</span>
            <strong>{{ formatCurrency(itemRows.length ? itemRowsTotalCost : itemTotalCost) }}</strong>
          </div>
          <div v-if="form.errors.item" class="text-danger small fw-semibold mt-2">
            {{ form.errors.item }}
          </div>
          <div v-if="itemTableError" class="text-danger small fw-semibold mt-2">
            {{ itemTableError }}
          </div>
        </BCol>
      </BRow>
    </form>

    <template v-slot:footer>
      <b-button @click="hide" variant="light" block>Cancel</b-button>
      <b-button
        @click="submit"
        variant="primary"
        :disabled="form.processing"
        block
      >
        {{ form.processing ? "Saving..." : "Save" }}
      </b-button>
    </template>
  </b-modal>

  <AddItemTableModal
    ref="itemTableModal"
    :unit-type-options="unitTypeOptions"
    :errors="form.errors"
    @save="saveItemRow"
  />
</template>

<script>
import { useForm } from "@inertiajs/vue3";
import Multiselect from "@vueform/multiselect";
import InputLabel from "@/Shared/Components/Forms/InputLabel.vue";
import TextInput from "@/Shared/Components/Forms/TextInput.vue";
import AddItemTableModal from "./AddItemTableModal.vue";

export default {
  components: { Multiselect, InputLabel, TextInput, AddItemTableModal },
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
      editingItem: null,
      form: useForm({
        option: "add_item",
        plan_type: null,
        item_id: null,
        item_name: "",
        item_description: "",
        general_description_objective: "",
        project_type: "",
        item_category_id: null,
        recommended_mode_of_procurement: "",
        pre_procurement_conference: "",
        item_quantity: 1,
        item_unit_type_id: null,
        item_unit_cost: null,
        items: [],
        start_of_procurement_activity: null,
        end_of_procurement_activity: null,
        expected_delivery_date: null,
        attached_supporting_documents: "",
        supporting_document_file: null,
        remarks: "",
      }),
      itemRows: [],
      isSupportingDocumentDragging: false,
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

      return (Array.isArray(options) ? options : Object.values(options))
        .map((option) => ({
          ...option,
          name: option.name || option.label || option.title || option.code || "",
        }))
        .filter((option) => option.name);
    },
    selectedItemCategoryName() {
      const category = this.itemCategoryOptions.find((option) => Number(option.value) === Number(this.form.item_category_id));

      return category?.name || "";
    },
    preProcurementConferenceOptions() {
      return [
        { label: "Yes", value: "Yes" },
        { label: "No", value: "No" },
        { label: "Not Applicable", value: "Not Applicable" },
      ];
    },
    itemUnitTypeLabel() {
      return "display_name";
    },
    itemTableError() {
      return this.form.errors.item_name ||
        this.form.errors.item_description ||
        this.form.errors.item_unit_type_id ||
        this.form.errors.item_unit_cost ||
        this.form.errors.items ||
        this.firstItemRowError ||
        null;
    },
    firstItemRowError() {
      const itemErrorKey = Object.keys(this.form.errors || {}).find((key) => key.startsWith("items."));

      return itemErrorKey ? this.form.errors[itemErrorKey] : null;
    },
    itemTotalCost() {
      return Number(this.form.item_unit_cost || 0);
    },
    itemRowsTotalCost() {
      return this.itemRows.reduce((sum, row) => sum + Number(row.total_cost || 0), 0);
    },
    isEditing() {
      return Boolean(this.editingItem);
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
      return this.isEditing ? `Edit ${this.planLabel} Item` : `Add ${this.planLabel} Item`;
    },
    isFormValid() {
      if (this.isEditing) {
        return Boolean(
          this.form.project_type &&
          this.form.item_category_id &&
          this.form.recommended_mode_of_procurement &&
          this.form.pre_procurement_conference &&
          this.form.general_description_objective &&
          this.form.end_of_procurement_activity &&
          this.form.expected_delivery_date &&
          this.form.attached_supporting_documents &&
          this.form.remarks &&
          this.itemRows.length === 1
        );
      }

      return Boolean(
        this.form.project_type &&
        this.form.item_category_id &&
        this.form.recommended_mode_of_procurement &&
        this.form.pre_procurement_conference &&
        this.form.general_description_objective &&
        this.form.end_of_procurement_activity &&
        this.form.expected_delivery_date &&
        this.form.attached_supporting_documents &&
        this.form.supporting_document_file &&
        this.form.remarks &&
        this.itemRows.length > 0
      );
    },
    canAddItemRow() {
      return this.itemRows.length > 0;
    },
    submitLabel() {
      if (this.isEditing) {
        return `Update ${this.planLabel} Item`;
      }

      return this.mode === "draft" ? "Use Item" : `Add ${this.planLabel} Item`;
    },
    supportingDocumentName() {
      return this.form.supporting_document_file?.name || this.editingItem?.supporting_document_original_name || "";
    },
  },
  methods: {
    show(editingItem = null) {
      this.form.clearErrors();
      this.form.reset();
      this.itemRows = [];
      this.editingItem = editingItem;
      this.form.plan_type = this.ppmp?.plan_type || null;

      if (editingItem) {
        this.form.option = "update_item";
        this.form.item_id = editingItem.id;
        this.form.project_type = editingItem.project_type || "";
        this.form.item_category_id = editingItem.item_category_id || null;
        this.form.recommended_mode_of_procurement = editingItem.recommended_mode_of_procurement || "";
        this.form.pre_procurement_conference = editingItem.pre_procurement_conference || "No";
        this.form.general_description_objective = this.ppmp?.general_description_objective || this.ppmp?.title || "";
        this.form.item_quantity = editingItem.quantity || 1;
        this.form.end_of_procurement_activity = editingItem.end_of_procurement_activity || null;
        this.form.expected_delivery_date = editingItem.expected_delivery_date || null;
        this.form.attached_supporting_documents = editingItem.attached_supporting_documents || "";
        this.form.supporting_document_file = null;
        this.form.remarks = editingItem.remarks || "";
        this.itemRows = [{
          key: `${editingItem.id}-${Date.now()}`,
          item_name: editingItem.name || "",
          item_description: editingItem.description || "",
          item_quantity: editingItem.quantity || 1,
          item_unit_type_id: editingItem.item_unit_type_id ?? null,
          item_unit_cost: Number(editingItem.unit_price || 0),
          total_cost: Number(editingItem.abc || 0),
        }];
        this.modal.show = true;
        return;
      }

      const draftItem = this.mode === "draft" ? this.draftItem : null;

      if (draftItem) {
        this.form.project_type = draftItem.project_type || "";
        this.form.item_category_id = draftItem.item_category_id || null;
        this.form.recommended_mode_of_procurement = draftItem.recommended_mode_of_procurement || "";
        this.form.pre_procurement_conference = draftItem.pre_procurement_conference || "No";
        this.form.general_description_objective = draftItem.general_description_objective || "";
        this.form.item_quantity = 1;
        this.form.end_of_procurement_activity = draftItem.end_of_procurement_activity || null;
        this.form.expected_delivery_date = draftItem.expected_delivery_date || null;
        this.form.attached_supporting_documents = draftItem.attached_supporting_documents || "";
        this.form.supporting_document_file = draftItem.supporting_document_file || null;
        this.form.remarks = draftItem.remarks || "";
        this.itemRows = [{
          key: `${Date.now()}-${Math.random()}`,
          item_name: draftItem.item_name || "",
          item_description: draftItem.item_description || "",
          item_quantity: 1,
          item_unit_type_id: draftItem.item_unit_type_id ?? null,
          item_unit_cost: Number(draftItem.item_unit_cost || 0),
          total_cost: Number(draftItem.item_unit_cost || 0),
        }];
      } else {
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
      this.form.item_quantity = 1;
      this.form.item_unit_cost = 0.0;
      this.form.supporting_document_file = null;
      this.itemRows = [];
      if (this.$refs.supportingDocumentInput) {
        this.$refs.supportingDocumentInput.value = "";
      }
    },
    submit() {
      const rowsToSubmit = this.itemRows;
      this.form.items = rowsToSubmit.map(({ key, total_cost, ...row }) => row);

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
          onSuccess: () => this.hide(),
        });
        return;
      }

      this.form.option = "add_item";

      if (this.mode === "draft") {
        const firstRow = rowsToSubmit[0];

        if (!firstRow) {
          this.form.setError("items", "Please add at least one item to the table.");
          return;
        }

        this.$emit("draft", {
          item_name: firstRow.item_name,
          item_description: firstRow.item_description,
          project_type: this.form.project_type,
          item_category_id: this.form.item_category_id,
          item_category: this.selectedItemCategoryName,
          recommended_mode_of_procurement: this.form.recommended_mode_of_procurement,
          pre_procurement_conference: this.form.pre_procurement_conference,
          general_description_objective: this.form.general_description_objective,
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
    formatQuantity(value) {
      const amount = Number(value || 0);
      return Number.isInteger(amount) ? amount.toString() : amount.toFixed(4).replace(/\.?0+$/, "");
    },
    saveItemRow({ row, editIndex }) {
      if (editIndex === null) {
        this.itemRows.push(row);
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
    },
    unitTypeName(unitTypeId, quantity = 1) {
      const unitType = this.unitTypeOptions.find((option) => Number(option.value ?? option.id) === Number(unitTypeId));

      if (!unitType) {
        return "-";
      }

      return Number(quantity || 0) > 1
        ? unitType.name_long || unitType.name_short || unitType.name || unitType.label || "-"
        : unitType.name_short || unitType.name_long || unitType.name || unitType.label || "-";
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

      const isPdf = file.type === "application/pdf" || file.name.toLowerCase().endsWith(".pdf");

      if (!isPdf) {
        this.form.setError("supporting_document_file", "Please attach a PDF file only.");
        this.removeSupportingDocument();
        return;
      }

      this.form.clearErrors("supporting_document_file");
      this.form.supporting_document_file = file;
    },
    removeSupportingDocument() {
      this.form.supporting_document_file = null;

      if (this.$refs.supportingDocumentInput) {
        this.$refs.supportingDocumentInput.value = "";
      }
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
</style>
