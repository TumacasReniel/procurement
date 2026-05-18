<template>
  <b-modal
    v-model="showModal"
    header-class="p-3"
    :title="modalTitle"
    :size="modal_size"
    class="v-modal-custom"
    modal-class="zoomIn"
    centered
    no-close-on-backdrop
  >
    <form class="customform">
      <div>
        <b-form-group label="Select Modal Size:">
          <b-form-radio-group
            v-model="modal_size"
            name="some-radios"
            buttons
            size="sm"
            button-variant="outline-primary"
          >
            <b-form-radio value="lg">Large</b-form-radio>
            <b-form-radio value="xl">X Large</b-form-radio>
            <b-form-radio value="fullscreen">Fullscreen</b-form-radio>
          </b-form-radio-group>
        </b-form-group>
      </div>

      <BRow>
        <BCol v-if="requirePpmpItem" lg="12" class="mt-3">
          <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div class="ppmp-item-search">
              <i class="ri-search-line"></i>
              <input
                v-model="ppmpItemKeyword"
                type="search"
                class="form-control form-control-sm"
                placeholder="Search PPMP items..."
              />
            </div>

            <div class="text-muted fs-12">
              {{ selectedPpmpItemCount }} of {{ ppmpItems.length }} item(s) selected
            </div>
          </div>

          <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
            <div class="form-check mb-0">
              <input
                id="ppmp-select-all-items"
                class="form-check-input"
                type="checkbox"
                :checked="allFilteredPpmpItemsSelected"
                :disabled="!filteredPpmpItems.length"
                @change="toggleAllPpmpItems($event.target.checked)"
              />
              <label class="form-check-label" for="ppmp-select-all-items">
                Select all visible PPMP items
              </label>
            </div>
            <strong class="text-primary fs-12">{{ formatCurrency(selectedPpmpItemAmount) }}</strong>
          </div>

          <div class="table-responsive border rounded ppmp-selection-table-wrap">
            <table class="table align-middle mb-0 ppmp-selection-table">
              <thead>
                <tr class="fs-11">
                  <th style="width: 6%" class="text-center">Pick</th>
                  <th style="width: 16%">PPMP No.</th>
                  <th>Item</th>
                  <th style="width: 10%" class="text-center">Qty</th>
                  <th style="width: 10%" class="text-center">Unit</th>
                  <th style="width: 14%" class="text-end">Unit Cost</th>
                  <th style="width: 14%" class="text-end">ABC</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in paginatedPpmpItems" :key="item.value">
                  <td class="text-center">
                    <input
                      :id="`ppmp-item-${item.value}`"
                      class="form-check-input"
                      type="checkbox"
                      :checked="isPpmpItemSelected(item.value)"
                      @change="togglePpmpItem(item.value, $event.target.checked)"
                    />
                  </td>
                  <td>
                    <span class="fw-semibold text-primary">{{ item.ppmp_no || "-" }}</span>
                    <small class="d-block text-muted">{{ item.plan_name || "PPMP" }}</small>
                  </td>
                  <td>
                    <div class="fw-semibold">{{ item.item_name || "-" }}</div>
                    <div class="text-muted small ppmp-selection-description" v-html="item.item_description || '-'" />
                  </td>
                  <td class="text-center">{{ formatQuantity(item.item_quantity) }}</td>
                  <td class="text-center">{{ item.unit_label || unitFromQuantityLabel(item) || "-" }}</td>
                  <td class="text-end">{{ formatCurrency(item.item_unit_cost) }}</td>
                  <td class="text-end fw-semibold">{{ formatCurrency(item.total_cost) }}</td>
                </tr>

                <tr v-if="isLoadingPpmpItems">
                  <td colspan="7" class="text-center text-muted py-4">Loading PPMP items...</td>
                </tr>
                <tr v-else-if="!filteredPpmpItems.length">
                  <td colspan="7" class="text-center text-muted py-4">
                    {{ ppmpItems.length ? "No PPMP items match your search." : "No PPMP items are available for the selected PAP code/end user." }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div
            v-if="filteredPpmpItems.length"
            class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3"
          >
            <div class="text-muted fs-12">
              Showing {{ ppmpPaginationStart }}-{{ ppmpPaginationEnd }} of {{ filteredPpmpItems.length }} item(s)
            </div>

            <div class="d-flex align-items-center gap-2">
              <select v-model.number="ppmpItemsPerPage" class="form-select form-select-sm ppmp-page-size">
                <option :value="5">5</option>
                <option :value="10">10</option>
                <option :value="20">20</option>
                <option :value="50">50</option>
              </select>

              <b-button
                size="sm"
                variant="light"
                :disabled="ppmpItemPage <= 1"
                @click="ppmpItemPage -= 1"
              >
                <i class="ri-arrow-left-s-line"></i>
              </b-button>

              <span class="text-muted fs-12">
                Page {{ ppmpItemPage }} of {{ ppmpTotalPages }}
              </span>

              <b-button
                size="sm"
                variant="light"
                :disabled="ppmpItemPage >= ppmpTotalPages"
                @click="ppmpItemPage += 1"
              >
                <i class="ri-arrow-right-s-line"></i>
              </b-button>
            </div>
          </div>
        </BCol>

        <BCol v-if="!requirePpmpItem" lg="12" class="mt-3">
          <InputLabel value="Item Name" :message="form.errors.item_name" />
          <div class="item-name-autocomplete">
            <TextInput
              v-model="form.item_name"
              type="text"
              class="form-control"
              placeholder="Enter item name"
              autocomplete="off"
              :readonly="requirePpmpItem"
              @focus="handleItemNameFocus"
              @blur="handleItemNameBlur"
              @keydown.down.prevent="moveSuggestionSelection(1)"
              @keydown.up.prevent="moveSuggestionSelection(-1)"
              @keydown.enter.prevent="confirmActiveSuggestion"
              @keydown.esc.prevent="closeItemNameDropdown"
            />

            <div
              v-if="shouldShowItemNameDropdown"
              class="item-name-suggestions"
            >
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
        </BCol>

        <BCol v-if="!requirePpmpItem" lg="12" class="mt-3">
          <InputLabel value="Description" :message="form.errors.item_description" />
          <CustomEditorMini v-model="form.item_description" :modal-size="modal_size" />
        </BCol>

        <BCol v-if="!requirePpmpItem" lg="4" class="mt-2">
          <InputLabel value="Quantity" />
          <TextInput
            v-model="form.item_quantity"
            type="number"
            class="form-control"
            placeholder="0"
            :readonly="requirePpmpItem"
          />
        </BCol>
        <BCol v-if="!requirePpmpItem" lg="4" class="mt-2">
          <InputLabel for="unit_type" value="Unit Type" />
          <Multiselect
            :options="dropdowns.unit_types"
            v-model="form.item_unit_type_id"
            :searchable="true"
            :label="unitTypeLabel"
            placeholder="Select Item Unit Type"
            :disabled="requirePpmpItem"
          />
        </BCol>

        <BCol v-if="!requirePpmpItem" lg="4" class="mt-2">
          <InputLabel value="Unit Cost" />
          <TextInput
            v-if="requirePpmpItem"
            :model-value="form.item_unit_cost"
            type="number"
            class="form-control"
            readonly
          />
          <Amount v-else @amount="amount" ref="amountComponent" />
        </BCol>
        <BCol lg="12"><hr class="text-muted mt-4 mb-0" /></BCol>
      </BRow>
    </form>

    <template v-slot:footer>
      <b-button @click="hide()" variant="light" block>Cancel</b-button>
      <b-button @click="addItem(form)" variant="primary" :disabled="!isItemFormValid || form.processing" block
        >{{ submitLabel }}</b-button
      >
    </template>
  </b-modal>
</template>
<script>
import { useForm } from "@inertiajs/vue3";
import Multiselect from "@vueform/multiselect";
import InputError from "@/Shared/Components/Forms/InputError.vue";
import InputLabel from "@/Shared/Components/Forms/InputLabel.vue";
import TextInput from "@/Shared/Components/Forms/TextInput.vue";
import Amount from "@/Shared/Components/Forms/Amount.vue";
import CustomEditorMini from "@/Shared/Components/Forms/CustomEditorMini.vue";


export default {
  components: {
    Amount,
    InputError,
    InputLabel,
    TextInput,
    Multiselect,
    CustomEditorMini
  },
  props: {
    dropdowns: {
      type: Object,
      required: true,
    },
    refresh: {
      type: Function,
      required: false,
    },
    storageKey: {
      type: String,
      default: "itemsAdded",
    },
    ppmpItems: {
      type: Array,
      default: () => [],
    },
    requirePpmpItem: {
      type: Boolean,
      default: false,
    },
    isLoadingPpmpItems: {
      type: Boolean,
      default: false,
    },
  },
  data() {
    return {
      currentUrl: window.location.origin,
      form: useForm({
        id: null,
        ppmp_item_id: null,
        ppmp_no: null,
        ppmp_id: null,
        item_name: "",
        item_description: "",
        item_unit_type: null,
        item_unit_type_id: null,
        item_quantity: null,
        item_unit_cost: null,
        total_cost: null,
      }),
      itemsAdded: [],
      showModal: false,
      modal_size: "lg",
      //editorData: "",
      //editor: ClassicEditor,
      isEditing: false,
      itemNameSuggestions: [],
      itemNameLookupTimeout: null,
      itemNameBlurTimeout: null,
      latestItemNameKeyword: "",
      isItemNameFocused: false,
      activeSuggestionIndex: -1,
      ppmpItemKeyword: "",
      selectedPpmpItemIds: [],
      ppmpItemPage: 1,
      ppmpItemsPerPage: 10,
    };
  },

  watch: {
    "form.item_name": function (value) {
      if (!this.showModal) {
        return;
      }

      this.queueItemNameSuggestions(value);
    },
    "form.item_unit_type_id": function (value) {
      if (value && !this.requirePpmpItem) {
        this.getItemUnitType(value);
      }
    },
    "form.item_quantity": function (value) {
      this.calculateTotalCost();
    },
    "form.ppmp_item_id": function (value) {
      if (!this.requirePpmpItem || !value) {
        return;
      }

      this.fillFromPpmpItem(this.selectedPpmpItem);
    },
    ppmpItemKeyword() {
      this.ppmpItemPage = 1;
    },
    ppmpItemsPerPage() {
      this.ppmpItemPage = 1;
    },
    filteredPpmpItems() {
      if (this.ppmpItemPage > this.ppmpTotalPages) {
        this.ppmpItemPage = this.ppmpTotalPages;
      }
    },
  },

  computed: {
    selectedPpmpItem() {
      if (!this.form.ppmp_item_id) {
        return null;
      }

      return this.ppmpItems.find((item) => Number(item.value) === Number(this.form.ppmp_item_id)) || null;
    },
    filteredPpmpItems() {
      const keyword = this.ppmpItemKeyword.trim().toLowerCase();

      if (!keyword) {
        return this.ppmpItems;
      }

      return this.ppmpItems.filter((item) => {
        const searchable = [
          item.ppmp_no,
          item.plan_name,
          item.item_name,
          item.item_description,
          item.quantity_label,
          item.unit_label,
          item.total_cost,
        ]
          .filter((value) => value !== null && value !== undefined)
          .join(" ")
          .toLowerCase();

        return searchable.includes(keyword);
      });
    },

    selectedPpmpItems() {
      const selectedIds = new Set(this.selectedPpmpItemIds.map((id) => Number(id)));
      return this.ppmpItems.filter((item) => selectedIds.has(Number(item.value)));
    },

    ppmpTotalPages() {
      return Math.max(Math.ceil(this.filteredPpmpItems.length / this.ppmpItemsPerPage), 1);
    },

    paginatedPpmpItems() {
      const start = (this.ppmpItemPage - 1) * this.ppmpItemsPerPage;
      return this.filteredPpmpItems.slice(start, start + this.ppmpItemsPerPage);
    },

    ppmpPaginationStart() {
      if (!this.filteredPpmpItems.length) {
        return 0;
      }

      return ((this.ppmpItemPage - 1) * this.ppmpItemsPerPage) + 1;
    },

    ppmpPaginationEnd() {
      return Math.min(this.ppmpItemPage * this.ppmpItemsPerPage, this.filteredPpmpItems.length);
    },

    selectedPpmpItemCount() {
      return this.selectedPpmpItemIds.length;
    },

    selectedPpmpItemAmount() {
      return this.selectedPpmpItems.reduce((sum, item) => sum + (Number(item.total_cost) || 0), 0);
    },

    allFilteredPpmpItemsSelected() {
      return this.filteredPpmpItems.length > 0
        && this.filteredPpmpItems.every((item) => this.isPpmpItemSelected(item.value));
    },

    modalTitle() {
      if (this.requirePpmpItem) {
        return this.isEditing ? "Change PPMP Item" : "Select PPMP Item";
      }

      return this.isEditing ? "Edit Item" : "Add Item";
    },

    submitLabel() {
      if (this.requirePpmpItem) {
        return this.isEditing ? "Update Selected Item" : "Use Selected PPMP Item";
      }

      return this.isEditing ? "Update" : "Add";
    },

    unitTypeLabel() {
      return this.form.item_quantity > 1 ? "name_long" : "name_short";
    },

    shouldShowItemNameDropdown() {
      return !this.requirePpmpItem && this.isItemNameFocused && this.itemNameSuggestions.length > 0;
    },

    isItemFormValid() {
      if (this.requirePpmpItem) {
        return this.selectedPpmpItemCount > 0;
      }

      return this.form.item_name &&
             this.form.item_description &&
             this.form.item_quantity &&
             this.form.item_unit_type_id &&
             this.form.item_unit_cost;
    },

    editorConfig() {
      return {
        height: this.modal_size === 'fullscreen' ? '400px' : '200px',
      };
    },
  },

  methods: {
    amount(val) {
      this.form.item_unit_cost = this.cleanCurrency(val);
      this.calculateTotalCost();
    },

    calculateTotalCost() {
      this.form.total_cost = (this.form.item_quantity || 0) * (this.form.item_unit_cost || 0);
    },

    cleanCurrency(value) {
      if (!value) return 0;
      // Remove ₱, commas, and spaces
      const cleaned = value.toString().replace(/[^0-9.]/g, "");
      return parseFloat(cleaned);
    },

    formatCurrency(value) {
      return new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
      }).format(Number(value || 0));
    },

    formatQuantity(value) {
      const quantity = Number(value || 0);

      return Number.isInteger(quantity)
        ? quantity.toString()
        : quantity.toLocaleString("en-US", {
            minimumFractionDigits: 0,
            maximumFractionDigits: 4,
          });
    },

    unitFromQuantityLabel(item) {
      const quantity = String(item?.item_quantity ?? "").trim();
      const label = String(item?.quantity_label || "").trim();

      if (!label) {
        return "";
      }

      return label.replace(quantity, "").trim();
    },

    isPpmpItemSelected(itemId) {
      return this.selectedPpmpItemIds.some((id) => Number(id) === Number(itemId));
    },

    togglePpmpItem(itemId, checked) {
      const numericId = Number(itemId);

      if (checked) {
        if (!this.isPpmpItemSelected(numericId)) {
          this.selectedPpmpItemIds.push(numericId);
        }
      } else {
        this.selectedPpmpItemIds = this.selectedPpmpItemIds.filter((id) => Number(id) !== numericId);
      }

      this.form.ppmp_item_id = this.selectedPpmpItemIds[0] || null;
      this.form.clearErrors();
    },

    toggleAllPpmpItems(checked) {
      const visibleIds = this.filteredPpmpItems.map((item) => Number(item.value));

      if (checked) {
        this.selectedPpmpItemIds = Array.from(new Set([
          ...this.selectedPpmpItemIds.map((id) => Number(id)),
          ...visibleIds,
        ]));
      } else {
        const visibleIdSet = new Set(visibleIds);
        this.selectedPpmpItemIds = this.selectedPpmpItemIds.filter((id) => !visibleIdSet.has(Number(id)));
      }

      this.form.ppmp_item_id = this.selectedPpmpItemIds[0] || null;
      this.form.clearErrors();
    },

    fillFromPpmpItem(ppmpItem) {
      if (!ppmpItem) {
        return;
      }

      this.form.ppmp_item_id = ppmpItem.value;
      this.form.ppmp_id = ppmpItem.ppmp_id;
      this.form.ppmp_no = ppmpItem.ppmp_no;
      this.form.item_name = ppmpItem.item_name || "";
      this.form.item_description = ppmpItem.item_description || "";
      this.form.item_quantity = ppmpItem.item_quantity;
      this.form.item_unit_type_id = ppmpItem.item_unit_type_id;
      this.form.item_unit_type = ppmpItem.item_unit_type;
      this.form.item_unit_cost = Number(ppmpItem.item_unit_cost) || 0;
      this.form.total_cost = Number(ppmpItem.total_cost) || 0;
      this.$refs.amountComponent?.emitValue(this.form.item_unit_cost.toFixed(2));
    },

    show() {
      this.form.reset();
      this.form.item_unit_cost = 0.0;
      this.ppmpItemKeyword = "";
      this.selectedPpmpItemIds = [];
      this.$refs.amountComponent?.emitValue(0.0);
      this.showModal = true;
      if (!this.requirePpmpItem) {
        this.fetchItemNameSuggestions("");
      }
    },

    edit(item, index) {
      this.isEditing = true;
      this.editItem = item;
      this.editIndex = index;
      this.form.reset();
      this.form.ppmp_item_id = item.ppmp_item_id || null;
      this.selectedPpmpItemIds = item.ppmp_item_id ? [Number(item.ppmp_item_id)] : [];
      this.form.ppmp_id = item.ppmp_id || null;
      this.form.ppmp_no = item.ppmp_no || null;
      this.form.item_name = item.item_name || "";
      this.form.item_description = item.item_description;
      this.form.item_quantity = item.item_quantity;
      this.form.item_unit_cost = parseFloat(item.item_unit_cost);
      this.$refs.amountComponent?.emitValue((this.form.item_unit_cost).toFixed(2));
      this.form.item_unit_type_id = item.item_unit_type_id;
      this.form.item_unit_type = item.item_unit_type;
      this.calculateTotalCost();
      this.form.id = item.id;
      this.showModal = true;
      if (!this.requirePpmpItem) {
        this.fetchItemNameSuggestions(this.form.item_name);
      }
    },

    addItem(item) {
      // Step 1: Parse the existing array
      this.itemsAdded = JSON.parse(localStorage.getItem(this.storageKey)) || [];

      if (this.requirePpmpItem) {
        const existingPpmpItemIds = new Set(
          this.itemsAdded
            .map((existingItem) => Number(existingItem.ppmp_item_id))
            .filter(Boolean)
        );
        const selectedItems = this.selectedPpmpItems
          .filter((ppmpItem) => this.isEditing || !existingPpmpItemIds.has(Number(ppmpItem.value)))
          .map((ppmpItem) => ({
            id: Date.now() + Number(ppmpItem.value),
            is_new: true,
            ppmp_item_id: ppmpItem.value,
            ppmp_id: ppmpItem.ppmp_id,
            ppmp_no: ppmpItem.ppmp_no,
            item_name: ppmpItem.item_name || "",
            item_description: ppmpItem.item_description || "",
            item_quantity: ppmpItem.item_quantity,
            item_unit_type_id: ppmpItem.item_unit_type_id,
            item_unit_type: ppmpItem.item_unit_type,
            item_unit_cost: Number(ppmpItem.item_unit_cost) || 0,
            total_cost: Number(ppmpItem.total_cost) || 0,
          }));

        if (this.isEditing) {
          this.itemsAdded.splice(this.editIndex, 1, ...selectedItems);
        } else {
          this.itemsAdded.push(...selectedItems);
        }

        localStorage.setItem(this.storageKey, JSON.stringify(this.itemsAdded));
        this.$emit("refresh");
        this.hide();
        return;
      }

      if (this.isEditing) {
        // Update existing item
        this.itemsAdded[this.editIndex] = { ...item, id: this.editItem.id };
      } else {
        // Step 2: Clone the item (avoid reactivity leaks)
        const newItem = { ...item, id: Date.now(), is_new: true };

        // Step 3: Add the new item to the array
        this.itemsAdded.push(newItem);
      }

      // Step 4: Save it back to localStorage
      localStorage.setItem(this.storageKey, JSON.stringify(this.itemsAdded));

      // Step 5: Notify parent to refresh data
      this.$emit("refresh");

      // Step 6: Hide modal
      this.hide();
    },

    getItemUnitType(unit_type_id) {
      axios
        .get("/faims/procurements/create", {
          params: {
            code: unit_type_id,
            option: "unit_type",
          },
        })
        .then((response) => {
          if (response) {
            this.form.item_unit_type = response.data;
          }
        })
        .catch((err) => console.log(err));
    },

    handleItemNameFocus() {
      if (this.requirePpmpItem) {
        return;
      }

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
      if (this.requirePpmpItem) {
        return;
      }

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

      this.selectItemNameSuggestion(
        this.itemNameSuggestions[this.activeSuggestionIndex]
      );
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

          this.itemNameSuggestions = Array.isArray(response.data)
            ? response.data
            : [];
          this.activeSuggestionIndex = this.itemNameSuggestions.length ? 0 : -1;
        })
        .catch((err) => console.log(err));
    },

    hide() {
      clearTimeout(this.itemNameLookupTimeout);
      clearTimeout(this.itemNameBlurTimeout);
      this.form.reset();
      this.form.item_unit_cost = 0.0;
      this.form.ppmp_item_id = null;
      this.form.ppmp_id = null;
      this.form.ppmp_no = null;
      this.ppmpItemKeyword = "";
      this.selectedPpmpItemIds = [];
      this.isEditing = false;
      this.editItem = null;
      this.editIndex = null;
      this.closeItemNameDropdown();
      this.showModal = false;
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
  border-radius: 14px;
  background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
  border: 1px solid rgba(191, 219, 254, 0.9);
  box-shadow: 0 18px 30px rgba(15, 23, 42, 0.14);
}

.item-name-suggestion {
  width: 100%;
  padding: 0.7rem 0.8rem;
  border: 0;
  border-radius: 10px;
  background: transparent;
  color: #1e293b;
  font-size: 0.95rem;
  text-align: left;
  transition: background-color 0.16s ease, color 0.16s ease;
}

.item-name-suggestion:hover,
.item-name-suggestion--active {
  background: #eaf2ff;
  color: #2846a6;
}

.ppmp-item-preview {
  padding: 0.85rem;
  border: 1px solid rgba(59, 130, 246, 0.18);
  border-radius: 8px;
  background: #f8fbff;
}

.ppmp-item-search {
  position: relative;
  width: min(360px, 100%);
}

.ppmp-item-search i {
  position: absolute;
  top: 50%;
  left: 0.75rem;
  z-index: 1;
  color: #94a3b8;
  transform: translateY(-50%);
}

.ppmp-item-search .form-control {
  padding-left: 2rem;
}

.ppmp-selection-table-wrap {
  max-height: 430px;
  overflow: auto;
}

.ppmp-selection-table thead {
  position: sticky;
  top: 0;
  z-index: 2;
  background: #4a5b93;
  color: #ffffff;
}

.ppmp-selection-table th {
  background-color: #4a5b93;
  border-bottom: 0;
  color: inherit;
  font-size: 0.72rem;
  font-weight: 800;
  letter-spacing: 0.06em;
  text-transform: uppercase;
}

.ppmp-selection-description {
  display: -webkit-box;
  max-height: 2.75rem;
  overflow: hidden;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 2;
}

.ppmp-page-size {
  width: 76px;
}

.selected-ppmp-summary {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 0.5rem;
}

.selected-ppmp-summary > div {
  padding: 0.75rem;
  border: 1px solid rgba(59, 130, 246, 0.16);
  border-radius: 8px;
  background: #f8fbff;
}

.selected-ppmp-summary span {
  display: block;
  color: #64748b;
  font-size: 0.72rem;
  font-weight: 700;
  text-transform: uppercase;
}

.selected-ppmp-summary strong {
  display: block;
  margin-top: 0.25rem;
  color: #1e293b;
}

@media (max-width: 768px) {
  .selected-ppmp-summary {
    grid-template-columns: 1fr;
  }
}
</style>
