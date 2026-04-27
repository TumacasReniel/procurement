<template>
  <b-modal
    v-model="showModal"
    header-class="p-3"
    :title="isEditing ? 'Edit Item' : 'Add Item'"
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
        <BCol lg="12" class="mt-3">
          <InputLabel value="Item Name" :message="form.errors.item_name" />
          <div class="item-name-autocomplete">
            <TextInput
              v-model="form.item_name"
              type="text"
              class="form-control"
              placeholder="Enter item name"
              autocomplete="off"
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

        <BCol lg="12" class="mt-3">
          <InputLabel value="Description" :message="form.errors.item_description" />
          <CustomEditorMini v-model="form.item_description" :modal-size="modal_size" />
        </BCol>

        <BCol lg="4" class="mt-2">
          <InputLabel value="Quantity" />
          <TextInput
            v-model="form.item_quantity"
            type="number"
            class="form-control"
            placeholder="0"
          />
        </BCol>
        <BCol lg="4" class="mt-2">
          <InputLabel for="unit_type" value="Unit Type" />
          <Multiselect
            :options="dropdowns.unit_types"
            v-model="form.item_unit_type_id"
            :searchable="true"
            :label="unitTypeLabel"
            placeholder="Select Item Unit Type"
          />
        </BCol>

        <BCol lg="4" class="mt-2">
          <InputLabel value="Unit Cost" />
          <Amount @amount="amount" ref="amountComponent" />
        </BCol>
        <BCol lg="12"><hr class="text-muted mt-4 mb-0" /></BCol>
      </BRow>
    </form>

    <template v-slot:footer>
      <b-button @click="hide()" variant="light" block>Cancel</b-button>
      <b-button @click="addItem(form)" variant="primary" :disabled="!isItemFormValid || form.processing" block
        >{{ isEditing ? 'Update' : 'add' }}</b-button
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
  },
  data() {
    return {
      currentUrl: window.location.origin,
      form: useForm({
        id: null,
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
      if (value) {
        this.getItemUnitType(value);
      }
    },
    "form.item_quantity": function (value) {
      this.calculateTotalCost();
    },
  },

  computed: {
    unitTypeLabel() {
      return this.form.item_quantity > 1 ? "name_long" : "name_short";
    },

    shouldShowItemNameDropdown() {
      return this.isItemNameFocused && this.itemNameSuggestions.length > 0;
    },

    isItemFormValid() {
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

    show() {
      this.form.reset();
      this.form.item_unit_cost = this.$refs.amountComponent.emitValue(0.0);
      this.showModal = true;
      this.fetchItemNameSuggestions("");
    },

    edit(item, index) {
      this.isEditing = true;
      this.editItem = item;
      this.editIndex = index;
      this.form.reset();
      this.form.item_name = item.item_name || "";
      this.form.item_description = item.item_description;
      this.form.item_quantity = item.item_quantity;
      this.form.item_unit_cost = parseFloat(item.item_unit_cost);
      this.$refs.amountComponent.emitValue((this.form.item_unit_cost).toFixed(2));
      this.form.item_unit_type_id = item.item_unit_type_id;
      this.form.item_unit_type = item.item_unit_type;
      this.calculateTotalCost();
      this.form.id = item.id;
      this.showModal = true;
      this.fetchItemNameSuggestions(this.form.item_name);
    },

    addItem(item) {
      // Step 1: Parse the existing array
      this.itemsAdded = JSON.parse(localStorage.getItem(this.storageKey)) || [];

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
</style>
