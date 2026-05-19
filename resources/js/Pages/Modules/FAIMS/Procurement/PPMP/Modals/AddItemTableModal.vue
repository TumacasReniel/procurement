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
        <BCol lg="12">
          <InputLabel value="Item Name" :message="errors.item_name" />
          <div class="item-name-autocomplete">
            <TextInput
              v-model="form.item_name"
              type="text"
              class="form-control"
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
        </BCol>

        <BCol lg="12">
          <InputLabel value="Description" :message="errors.item_description" />
          <CustomEditorMini v-model="form.item_description" modal-size="lg" />
        </BCol>

        <BCol lg="4">
          <InputLabel value="Unit Type" :message="errors.item_unit_type_id" />
          <Multiselect
            :options="normalizedUnitTypeOptions"
            v-model="form.item_unit_type_id"
            :searchable="true"
            label="display_name"
            valueProp="value"
            trackBy="display_name"
            placeholder="Unit"
          />
        </BCol>

        <BCol lg="4">
          <InputLabel value="Quantity" :message="errors.item_quantity" />
          <TextInput
            v-model="form.item_quantity"
            type="number"
            class="form-control"
            min="0.0001"
            step="0.0001"
            placeholder="1"
          />
        </BCol>

        <BCol lg="4">
          <InputLabel value="Unit Cost" :message="errors.item_unit_cost" />
          <Amount @amount="amount" ref="amountComponent" />
        </BCol>

      
      </BRow>
    </form>

    <template v-slot:footer>
      <b-button @click="hide" variant="light" block>Cancel</b-button>
      <b-button
        @click="save"
        variant="primary"
        :disabled="!isValid"
        block
      >
        {{ isEditing ? "Update Item" : "Add to Table" }}
      </b-button>
    </template>
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
    errors: {
      type: Object,
      default: () => ({}),
    },
  },
  emits: ["save"],
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
    };
  },
  computed: {
    isEditing() {
      return this.editIndex !== null;
    },
    isValid() {
      return Boolean(
        this.form.item_name &&
        this.form.item_description &&
        Number(this.form.item_quantity) > 0 &&
        this.form.item_unit_type_id &&
        Number(this.form.item_unit_cost) >= 0
      );
    },
    shouldShowItemNameDropdown() {
      return this.isItemNameFocused && this.itemNameSuggestions.length > 0;
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
  },
  beforeUnmount() {
    this.clearItemNameSuggestionState();
  },
  methods: {
    defaultForm() {
      return {
        item_name: "",
        item_description: "",
        item_quantity: 1,
        item_unit_type_id: null,
        item_unit_cost: 0.0,
      };
    },
    show(row = null, editIndex = null) {
      this.editIndex = editIndex;
      this.form = row
        ? {
            item_name: row.item_name || "",
            item_description: row.item_description || "",
            item_quantity: row.item_quantity || 1,
            item_unit_type_id: row.item_unit_type_id ?? null,
            item_unit_cost: Number(row.item_unit_cost || 0),
          }
        : this.defaultForm();
      this.modal.show = true;
      this.fetchItemNameSuggestions(this.form.item_name || "");
    },
    hide() {
      this.modal.show = false;
      this.editIndex = null;
      this.form = this.defaultForm();
      this.clearItemNameSuggestionState();
      this.$refs.amountComponent?.empty();
    },
    save() {
      if (!this.isValid) {
        return;
      }

      const quantity = Number(this.form.item_quantity || 0);
      const unitCost = Number(this.form.item_unit_cost || 0);

      this.$emit("save", {
        row: {
          key: `${Date.now()}-${Math.random()}`,
          item_name: this.form.item_name,
          item_description: this.form.item_description,
          item_quantity: quantity,
          item_unit_type_id: this.form.item_unit_type_id,
          item_unit_cost: unitCost,
          total_cost: quantity * unitCost,
        },
        editIndex: this.editIndex,
      });
      this.hide();
    },
    amount(val) {
      this.form.item_unit_cost = this.cleanCurrency(val);
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
  watch: {
    "form.item_name"(value) {
      if (!this.modal.show) {
        return;
      }

      clearTimeout(this.itemNameLookupTimeout);
      this.itemNameLookupTimeout = setTimeout(() => {
        this.fetchItemNameSuggestions(value);
      }, 250);
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
</style>
