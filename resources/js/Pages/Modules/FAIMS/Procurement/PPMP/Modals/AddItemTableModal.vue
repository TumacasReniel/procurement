<template>
  <b-modal
    v-model="modal.show"
    header-class="p-3"
    :title="isEditing ? 'Edit Item' : 'Add Item'"
    size="lg"
    class="v-modal-custom"
    modal-class="zoomIn"
    centered
    no-close-on-backdrop
    @shown="syncAmountInput"
  >
    <form class="customform" @submit.prevent="save">
      <BRow class="g-3">
        <BCol v-if="showProjectSelect" lg="12">
          <InputLabel value="PPMP Project" />
          <Multiselect
            :class="multiselectInvalidClass('ppmp_id')"
            :options="projectOptions"
            v-model="form.ppmp_id"
            :searchable="true"
            label="name"
            valueProp="value"
            trackBy="name"
            placeholder="Select PPMP project"
          />
          <div v-if="fieldError('ppmp_id')" class="invalid-feedback d-block">
            {{ fieldError("ppmp_id") }}
          </div>

          <div v-if="selectedProject && selectedProject.total_budget > 0" class="mt-2 p-2 rounded border bg-light">
            <div class="d-flex justify-content-between mb-1" style="font-size: 12px">
              <span class="text-muted">Total Budget</span>
              <strong>{{ formatCurrency(selectedProject.total_budget) }}</strong>
            </div>
            <div class="d-flex justify-content-between mb-1" style="font-size: 12px">
              <span class="text-muted">Used in PRs</span>
              <span class="text-danger">{{ formatCurrency(selectedProject.used_budget) }}</span>
            </div>
            <div v-if="alreadyUsedInForm > 0" class="d-flex justify-content-between mb-1" style="font-size: 12px">
              <span class="text-muted">Added in this PR</span>
              <span class="text-warning">{{ formatCurrency(alreadyUsedInForm) }}</span>
            </div>
            <div class="d-flex justify-content-between border-top pt-1 mt-1" style="font-size: 12px">
              <span class="fw-semibold">Available</span>
              <strong :class="availableForThisItem >= 0 ? 'text-success' : 'text-danger'">
                {{ formatCurrency(availableForThisItem) }}
              </strong>
            </div>
            <div v-if="availableForThisItem < currentItemTotal && currentItemTotal > 0" class="mt-1 text-danger" style="font-size: 11px">
              <i class="ri-error-warning-line me-1"></i>
              Item total ({{ formatCurrency(currentItemTotal) }}) exceeds available budget.
            </div>
          </div>
        </BCol>

        <BCol lg="8">
          <InputLabel value="Item Name" />
          <div class="item-name-autocomplete">
            <TextInput
              v-model="form.item_name"
              type="text"
              class="form-control"
              :class="inputInvalidClass('item_name')"
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
          <div v-if="fieldError('item_name')" class="invalid-feedback d-block">
            {{ fieldError("item_name") }}
          </div>
        </BCol>

        <BCol lg="4">
          <InputLabel value="Item Category" />
          <div class="item-category-control">
            <Multiselect
              :class="multiselectInvalidClass('item_category_id')"
              :options="allItemCategoryOptions"
              v-model="form.item_category_id"
              :searchable="true"
              label="name"
              placeholder="Select category"
            />
            <button
              type="button"
              class="btn btn-outline-primary btn-icon item-category-control__add"
              title="Add item category"
              @click="openItemCategoryModal"
            >
              <i class="ri-add-line"></i>
            </button>
          </div>
          <div v-if="fieldError('item_category_id')" class="invalid-feedback d-block">
            {{ fieldError("item_category_id") }}
          </div>
        </BCol>

        <BCol lg="12">
          <InputLabel value="Description" />
          <div v-if="itemDescriptionSuggestions.length" class="description-suggestions-trigger">
            <button
              type="button"
              class="btn btn-outline-info btn-sm description-suggestions-btn"
              @click="descriptionSuggestionsModal = true"
            >
              <i class="ri-lightbulb-line me-1"></i>
              {{ itemDescriptionSuggestions.length }} suggestion{{ itemDescriptionSuggestions.length === 1 ? '' : 's' }} available
            </button>
          </div>
          <div :class="editorInvalidClass('item_description')">
            <CustomEditorMini v-model="form.item_description" modal-size="lg" />
          </div>
          <div v-if="fieldError('item_description')" class="invalid-feedback d-block">
            {{ fieldError("item_description") }}
          </div>
        </BCol>

        <BCol lg="4">
          <InputLabel value="Unit Type" />
          <Multiselect
            :class="multiselectInvalidClass('item_unit_type_id')"
            :options="normalizedUnitTypeOptions"
            v-model="form.item_unit_type_id"
            :searchable="true"
            label="display_name"
            valueProp="value"
            trackBy="display_name"
            placeholder="Select Unit Type"
          />
          <div v-if="fieldError('item_unit_type_id')" class="invalid-feedback d-block">
            {{ fieldError("item_unit_type_id") }}
          </div>
        </BCol>

        <BCol lg="4">
          <InputLabel value="Quantity" />
          <TextInput
            v-model="form.item_quantity"
            type="number"
            class="form-control"
            :class="inputInvalidClass('item_quantity')"
            min="0.0001"
            step="0.0001"
            placeholder="1"
          />
          <div v-if="fieldError('item_quantity')" class="invalid-feedback d-block">
            {{ fieldError("item_quantity") }}
          </div>
        </BCol>

        <BCol lg="4">
          <InputLabel value="Unit Cost" />
          <div :class="amountInvalidClass('item_unit_cost')">
            <Amount @amount="amount" ref="amountComponent" />
          </div>
          <div v-if="fieldError('item_unit_cost')" class="invalid-feedback d-block">
            {{ fieldError("item_unit_cost") }}
          </div>
        </BCol>
      </BRow>
    </form>

    <template v-slot:footer>
      <b-button @click="hide" variant="light" block>Cancel</b-button>
      <b-button
        @click="save"
        variant="primary"
        block
      >
        {{ isEditing ? "Update Item" : "Add to Table" }}
      </b-button>
    </template>
  </b-modal>

  <b-modal
    v-model="descriptionSuggestionsModal"
    header-class="p-3"
    title="Choose Description"
    size="lg"
    class="v-modal-custom"
    modal-class="zoomIn"
    centered
    hide-footer
  >
    <div class="description-suggestions-list">
      <div
        v-for="(desc, index) in itemDescriptionSuggestions"
        :key="index"
        class="description-suggestion-item"
      >
        <div class="description-suggestion-item__body" v-html="desc"></div>
        <div class="description-suggestion-item__footer">
          <b-button
            size="sm"
            variant="primary"
            @click="selectDescriptionSuggestion(desc); descriptionSuggestionsModal = false"
          >
            <i class="ri-check-line me-1"></i>
            Use this
          </b-button>
        </div>
      </div>
    </div>
  </b-modal>

  <b-modal
    v-model="itemCategoryModal.show"
    header-class="p-3"
    title="Add Item Category"
    class="v-modal-custom"
    modal-class="zoomIn"
    centered
    no-close-on-backdrop
    hide-footer
  >
    <div>
      <InputLabel value="Item Category" />
      <TextInput
        v-model="itemCategoryModal.name"
        type="text"
        class="form-control"
        :class="{ 'is-invalid': Boolean(itemCategoryModal.error) }"
        placeholder="Enter item category"
        @keyup.enter="storeItemCategory"
      />
      <div v-if="itemCategoryModal.error" class="invalid-feedback d-block">
        {{ itemCategoryModal.error }}
      </div>
      <div class="d-flex justify-content-end gap-2 mt-3">
        <b-button type="button" variant="light" @click="closeItemCategoryModal">
          Cancel
        </b-button>
        <b-button
          type="button"
          variant="primary"
          :disabled="itemCategoryModal.processing"
          @click="storeItemCategory"
        >
          {{ itemCategoryModal.processing ? "Saving..." : "Save Category" }}
        </b-button>
      </div>
    </div>
  </b-modal>
</template>

<script>
import Multiselect from "@vueform/multiselect";
import InputLabel from "@/Shared/Components/Forms/InputLabel.vue";
import TextInput from "@/Shared/Components/Forms/TextInput.vue";
import CustomEditorMini from "@/Shared/Components/Forms/CustomEditorMini.vue";
import Amount from "@/Shared/Components/Forms/Amount.vue";

export default {
  components: { Multiselect, InputLabel, TextInput, CustomEditorMini, Amount },
  props: {
    unitTypeOptions: {
      type: Array,
      default: () => [],
    },
    itemCategoryOptions: {
      type: Array,
      default: () => [],
    },
    projectOptions: {
      type: Array,
      default: () => [],
    },
    showProjectSelect: {
      type: Boolean,
      default: false,
    },
    existingFormItems: {
      type: Array,
      default: () => [],
    },
    errors: {
      type: Object,
      default: () => ({}),
    },
  },
  emits: ["save", "category-added"],
  data() {
    return {
      modal: {
        show: false,
      },
      editIndex: null,
      form: this.defaultForm(),
      itemNameSuggestions: [],
      itemNameLookupTimeout: null,
      itemNameBlurTimeout: null,
      latestItemNameKeyword: "",
      isItemNameFocused: false,
      activeSuggestionIndex: -1,
      localErrors: {},
      extraLocalCategories: [],
      itemDescriptionSuggestions: [],
      descriptionSuggestionsModal: false,
      itemCategoryModal: {
        show: false,
        name: "",
        error: "",
        processing: false,
      },
    };
  },
  computed: {
    isEditing() {
      return this.editIndex !== null;
    },
    isValid() {
      return Object.keys(this.validateForm()).length === 0;
    },
    shouldShowItemNameDropdown() {
      return this.isItemNameFocused && this.itemNameSuggestions.length > 0;
    },
    allItemCategoryOptions() {
      const existingIds = new Set(
        this.itemCategoryOptions.map((o) => Number(o.value ?? o.id))
      );

      return [
        ...this.itemCategoryOptions,
        ...this.extraLocalCategories.filter(
          (o) => !existingIds.has(Number(o.value ?? o.id))
        ),
      ].sort((a, b) => String(a.name).localeCompare(String(b.name)));
    },
    normalizedUnitTypeOptions() {
      return this.unitTypeOptions
        .map((option) => ({
          ...option,
          value: option.value ?? option.id,
          display_name: this.unitTypeLabel(option, this.form.item_quantity),
          name_short: option.name_short || option.name || option.name_long || option.label || "Unit",
          name_long: option.name_long || option.name || option.name_short || option.label || "Unit",
        }))
        .filter((option) => option.value !== null && option.value !== undefined);
    },
    selectedProject() {
      if (!this.form.ppmp_id || !this.projectOptions.length) return null;
      return this.projectOptions.find((p) => String(p.value) === String(this.form.ppmp_id)) ?? null;
    },
    alreadyUsedInForm() {
      if (!this.form.ppmp_id) return 0;
      return this.existingFormItems.reduce((sum, item, index) => {
        if (String(item.ppmp_id) !== String(this.form.ppmp_id)) return sum;
        if (this.editIndex !== null && index === this.editIndex) return sum;
        return sum + (Number(item.total_cost) || 0);
      }, 0);
    },
    availableForThisItem() {
      if (!this.selectedProject) return Infinity;
      const remaining = Number(this.selectedProject.remaining_budget ?? 0);
      return remaining - this.alreadyUsedInForm;
    },
    currentItemTotal() {
      return Number(this.form.item_quantity || 0) * Number(this.form.item_unit_cost || 0);
    },
  },
  beforeUnmount() {
    this.clearItemNameSuggestionState();
  },
  methods: {
    defaultForm() {
      return {
        id: null,
        ppmp_id: null,
        item_name: "",
        item_description: "",
        item_quantity: 1,
        item_unit_type_id: null,
        item_unit_cost: 0.0,
        item_category_id: null,
      };
    },
    show(row = null, editIndex = null) {
      this.editIndex = editIndex;
      this.form = row
        ? {
            id: row.id ?? null,
            ppmp_id: row.ppmp_id ?? null,
            item_name: row.item_name || "",
            item_description: row.item_description || "",
            item_quantity: row.item_quantity || 1,
            item_unit_type_id: row.item_unit_type_id ?? null,
            item_unit_cost: Number(row.item_unit_cost || 0),
            item_category_id: row.item_category_id ?? null,
          }
        : this.defaultForm();
      this.localErrors = {};
      this.modal.show = true;
      this.fetchItemNameSuggestions(this.form.item_name || "");
    },
    hide() {
      this.modal.show = false;
      this.editIndex = null;
      this.form = this.defaultForm();
      this.localErrors = {};
      this.clearItemNameSuggestionState();
      this.$refs.amountComponent?.empty();
    },
    save() {
      const errors = this.validateForm();

      if (Object.keys(errors).length) {
        this.localErrors = errors;
        return;
      }

      const quantity = Number(this.form.item_quantity || 0);
      const unitCost = Number(this.form.item_unit_cost || 0);

      this.$emit("save", {
        row: {
          id: this.form.id,
          key: `${Date.now()}-${Math.random()}`,
          ppmp_id: this.form.ppmp_id ?? null,
          item_name: this.form.item_name,
          item_description: this.form.item_description,
          item_quantity: quantity,
          funded_quantity: quantity,
          requested_quantity: quantity,
          unfunded_quantity: 0,
          is_partial_funding: false,
          item_unit_type_id: this.form.item_unit_type_id,
          item_unit_cost: unitCost,
          total_cost: quantity * unitCost,
          item_category_id: this.form.item_category_id,
        },
        editIndex: this.editIndex,
      });
      this.hide();
    },
    amount(val) {
      this.form.item_unit_cost = this.cleanCurrency(val);
      this.clearErrorWhenValid("item_unit_cost");
    },
    syncAmountInput() {
      const unitCost = Number(this.form.item_unit_cost || 0);
      this.$refs.amountComponent?.emitValue(unitCost.toFixed(2));
    },
    cleanCurrency(value) {
      if (!value) return 0;

      const cleaned = value.toString().replace(/[^0-9.]/g, "");
      return parseFloat(cleaned || 0);
    },
    unitTypeLabel(unitType, quantity) {
      const amount = Number(quantity || 0);

      return amount > 1
        ? unitType.name_long || unitType.name_short || unitType.name || unitType.label || "Unit"
        : unitType.name_short || unitType.name_long || unitType.name || unitType.label || "Unit";
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
      this.fetchItemDescriptionSuggestions(suggestion);
    },
    fetchItemDescriptionSuggestions(itemName = "") {
      const name = (itemName || "").trim();

      if (!name) {
        this.itemDescriptionSuggestions = [];
        return;
      }

      axios
        .get("/faims/procurements/create", {
          params: { option: "item_descriptions", keyword: name },
        })
        .then((response) => {
          this.itemDescriptionSuggestions = Array.isArray(response.data) ? response.data : [];
        })
        .catch(() => {
          this.itemDescriptionSuggestions = [];
        });
    },
    selectDescriptionSuggestion(desc) {
      this.form.item_description = desc;
      this.itemDescriptionSuggestions = [];
      this.clearErrorWhenValid("item_description");
    },
    descriptionPreview(html) {
      const el = document.createElement("div");
      el.innerHTML = String(html || "");
      const text = (el.textContent || "").trim();
      return text.length > 90 ? text.slice(0, 90) + "…" : text;
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
      this.itemDescriptionSuggestions = [];
      this.latestItemNameKeyword = "";
      this.closeItemNameDropdown();
    },
    formatCurrency(value) {
      return new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
      }).format(Number(value || 0));
    },
    validateForm() {
      const errors = {};

      if (this.showProjectSelect && !this.form.ppmp_id) {
        errors.ppmp_id = "Please select a PPMP project.";
      }

      if (!this.hasValue(this.form.item_name)) {
        errors.item_name = "Item name is required.";
      }

      if (!this.hasValue(this.form.item_category_id)) {
        errors.item_category_id = "Item category is required.";
      }

      if (!this.hasValue(this.form.item_unit_type_id)) {
        errors.item_unit_type_id = "Unit type is required.";
      }

      if (Number(this.form.item_quantity) <= 0) {
        errors.item_quantity = "Quantity must be greater than zero.";
      }

      if (!this.hasValue(this.form.item_unit_cost) || Number(this.form.item_unit_cost) < 0) {
        errors.item_unit_cost = "Unit cost is required.";
      }

      if (
        this.showProjectSelect &&
        this.selectedProject &&
        this.selectedProject.total_budget > 0 &&
        this.currentItemTotal > this.availableForThisItem
      ) {
        errors.item_unit_cost =
          `Item total (${this.formatCurrency(this.currentItemTotal)}) exceeds the available budget of ${this.formatCurrency(this.availableForThisItem)}.`;
      }

      return errors;
    },
    openItemCategoryModal() {
      this.itemCategoryModal.name = "";
      this.itemCategoryModal.error = "";
      this.itemCategoryModal.show = true;
    },
    closeItemCategoryModal(force = false) {
      if (this.itemCategoryModal.processing && !force) {
        return;
      }

      this.itemCategoryModal.show = false;
      this.itemCategoryModal.error = "";
      this.itemCategoryModal.name = "";
    },
    storeItemCategory() {
      const name = String(this.itemCategoryModal.name || "").trim();

      if (!name) {
        this.itemCategoryModal.error = "Please enter the item category name.";
        return;
      }

      const existing = this.allItemCategoryOptions.find(
        (o) => String(o.name || "").trim().toLowerCase() === name.toLowerCase()
      );

      if (existing) {
        this.form.item_category_id = existing.value ?? existing.id;
        this.closeItemCategoryModal();
        return;
      }

      this.itemCategoryModal.processing = true;
      this.itemCategoryModal.error = "";

      axios
        .post("/faims/procurement-ppmp/item-categories", { name })
        .then((response) => {
          const category = response.data?.data;

          if (!category?.value) {
            this.itemCategoryModal.error = "Unable to save this item category.";
            return;
          }

          this.extraLocalCategories.push(category);
          this.form.item_category_id = category.value;
          this.$emit("category-added", category);
          this.closeItemCategoryModal(true);
        })
        .catch((error) => {
          this.itemCategoryModal.error =
            error.response?.data?.errors?.name?.[0] ||
            error.response?.data?.message ||
            "Unable to save this item category.";
        })
        .finally(() => {
          this.itemCategoryModal.processing = false;
        });
    },
    fieldError(field) {
      return this.localErrors[field] || this.errors[field] || "";
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
    editorInvalidClass(field) {
      return { "editor-invalid": this.hasFieldError(field) };
    },
    amountInvalidClass(field) {
      return { "amount-invalid": this.hasFieldError(field) };
    },
    hasValue(value) {
      if (value && typeof value === "object") {
        return true;
      }

      return String(value ?? "").trim() !== "";
    },
    clearErrorWhenValid(field) {
      if (!this.localErrors[field]) {
        return;
      }

      const errors = this.validateForm();

      if (!errors[field]) {
        const { [field]: _removed, ...remainingErrors } = this.localErrors;
        this.localErrors = remainingErrors;
      }
    },
  },
  watch: {
    "form.ppmp_id"() {
      this.clearErrorWhenValid("ppmp_id");
    },
    "form.item_name"(value) {
      if (!this.modal.show) {
        return;
      }

      clearTimeout(this.itemNameLookupTimeout);
      this.itemNameLookupTimeout = setTimeout(() => {
        this.fetchItemNameSuggestions(value);
      }, 250);

      this.itemDescriptionSuggestions = [];
      this.clearErrorWhenValid("item_name");
    },
    "form.item_description"() {
      this.clearErrorWhenValid("item_description");
    },
    "form.item_category_id"() {
      this.clearErrorWhenValid("item_category_id");
    },
    "form.item_unit_type_id"() {
      this.clearErrorWhenValid("item_unit_type_id");
    },
    "form.item_quantity"() {
      this.clearErrorWhenValid("item_quantity");
    },
    "itemCategoryModal.name"(value) {
      if (String(value || "").trim()) {
        this.itemCategoryModal.error = "";
      }
    },
  },
};
</script>

<style scoped>
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

.description-suggestions-trigger {
  margin-bottom: 8px;
}

.description-suggestions-btn {
  font-size: 12px;
}

.description-suggestions-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.description-suggestion-item {
  border: 1px solid #e9ebec;
  border-radius: 8px;
  overflow: hidden;
}

.description-suggestion-item__body {
  padding: 12px 14px;
  font-size: 13px;
  line-height: 1.6;
  background: #f8fafc;
  max-height: 200px;
  overflow-y: auto;
}

.description-suggestion-item__footer {
  padding: 8px 12px;
  background: #fff;
  border-top: 1px solid #e9ebec;
  display: flex;
  justify-content: flex-end;
}

.item-category-control {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 38px;
  gap: 8px;
  align-items: stretch;
}

.item-category-control__add {
  width: 38px;
  min-width: 38px;
  height: 38px;
}

:deep(.multiselect.is-invalid),
:deep(.multiselect.is-invalid .multiselect-wrapper),
.editor-invalid :deep(.ql-toolbar),
.editor-invalid :deep(.ql-container),
.amount-invalid :deep(input),
.amount-invalid :deep(.form-control) {
  border-color: #f06548 !important;
}

:deep(.multiselect.is-invalid),
.editor-invalid,
.amount-invalid :deep(input),
.amount-invalid :deep(.form-control) {
  box-shadow: 0 0 0 .125rem rgba(240, 101, 72, .12);
}
</style>
