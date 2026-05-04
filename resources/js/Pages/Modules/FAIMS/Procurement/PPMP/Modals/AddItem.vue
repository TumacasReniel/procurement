<template>
  <b-modal
    v-model="modal.show"
    header-class="p-3"
    title="Add PPMP Item"
    size="lg"
    class="v-modal-custom"
    modal-class="zoomIn"
    centered
    no-close-on-backdrop
  >
    <form class="customform">
      <BRow>
        <BCol lg="12" class="mt-2">
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

        <BCol lg="12" class="mt-3">
          <InputLabel value="Description" :message="form.errors.item_description" />
          <CustomEditorMini v-model="form.item_description" />
        </BCol>

        <BCol lg="4" class="mt-3">
          <InputLabel value="Quantity" :message="form.errors.item_quantity" />
          <TextInput
            v-model="form.item_quantity"
            type="number"
            min="0"
            step="0.0001"
            class="form-control"
            placeholder="0"
          />
        </BCol>

        <BCol lg="4" class="mt-3">
          <InputLabel value="Unit Type" :message="form.errors.item_unit_type_id" />
          <Multiselect
            :options="unitTypeOptions"
            v-model="form.item_unit_type_id"
            :searchable="true"
            :label="itemUnitTypeLabel"
            value-prop="value"
            placeholder="Select unit"
          />
        </BCol>

        <BCol lg="4" class="mt-3">
          <InputLabel value="Unit Cost" :message="form.errors.item_unit_cost" />
          <Amount @amount="amount" ref="amountComponent" />
        </BCol>

        <BCol lg="12" class="mt-3">
          <div class="item-total-preview">
            <span>Total Amount</span>
            <strong>{{ formatCurrency(itemTotalCost) }}</strong>
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
        {{ form.processing ? "Saving..." : "Add Item" }}
      </b-button>
    </template>
  </b-modal>
</template>

<script>
import { useForm } from "@inertiajs/vue3";
import Multiselect from "@vueform/multiselect";
import InputLabel from "@/Shared/Components/Forms/InputLabel.vue";
import TextInput from "@/Shared/Components/Forms/TextInput.vue";
import CustomEditorMini from "@/Shared/Components/Forms/CustomEditorMini.vue";
import Amount from "@/Shared/Components/Forms/Amount.vue";

export default {
  components: { Multiselect, InputLabel, TextInput, CustomEditorMini, Amount },
  props: {
    ppmp: {
      type: Object,
      required: true,
    },
    dropdowns: {
      type: Object,
      default: () => ({}),
    },
  },
  data() {
    return {
      modal: {
        show: false,
      },
      form: useForm({
        option: "add_item",
        item_name: "",
        item_description: "",
        item_quantity: null,
        item_unit_type_id: null,
        item_unit_cost: null,
      }),
      itemNameSuggestions: [],
      itemNameLookupTimeout: null,
      itemNameBlurTimeout: null,
      latestItemNameKeyword: "",
      isItemNameFocused: false,
      activeSuggestionIndex: -1,
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
    itemUnitTypeLabel() {
      return Number(this.form.item_quantity || 0) > 1 ? "name_long" : "name_short";
    },
    itemTotalCost() {
      return Number(this.form.item_quantity || 0) * Number(this.form.item_unit_cost || 0);
    },
    isFormValid() {
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
  },
  beforeUnmount() {
    this.clearItemNameSuggestionState();
  },
  methods: {
    show() {
      this.form.clearErrors();
      this.form.reset();
      this.form.item_unit_cost = 0.0;
      this.$refs.amountComponent?.emitValue(0.0);
      this.modal.show = true;
      this.fetchItemNameSuggestions("");
    },
    hide() {
      this.modal.show = false;
      this.form.clearErrors();
      this.form.reset();
      this.form.item_unit_cost = 0.0;
      this.$refs.amountComponent?.emitValue(0.0);
      this.clearItemNameSuggestionState();
    },
    submit() {
      if (!this.isFormValid) {
        return;
      }

      this.form.option = "add_item";

      this.form.patch(`/faims/procurement-ppmp/${this.ppmp.id}`, {
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
    amount(value) {
      this.form.item_unit_cost = this.cleanCurrency(value);
    },
    cleanCurrency(value) {
      if (!value) {
        return 0;
      }

      const cleaned = value.toString().replace(/[^0-9.]/g, "");
      return parseFloat(cleaned) || 0;
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
</style>
