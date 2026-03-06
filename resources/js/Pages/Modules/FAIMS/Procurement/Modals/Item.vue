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
  props: ["dropdowns", "refresh"],
  data() {
    return {
      currentUrl: window.location.origin,
      form: useForm({
        id: null,
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
    };
  },

  watch: {
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

    isItemFormValid() {
      return this.form.item_description &&
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
    },

    edit(item, index) {
      this.isEditing = true;
      this.editItem = item;
      this.editIndex = index;
      this.form.reset();
      this.form.item_description = item.item_description;
      this.form.item_quantity = item.item_quantity;
      this.form.item_unit_cost = parseFloat(item.item_unit_cost);
      this.$refs.amountComponent.emitValue((this.form.item_unit_cost).toFixed(2));
      this.form.item_unit_type_id = item.item_unit_type_id;
      this.form.item_unit_type = item.item_unit_type;
      this.calculateTotalCost();
      this.form.id = item.id;
      this.showModal = true;
    },

    addItem(item) {
      // Step 1: Parse the existing array
      this.itemsAdded = JSON.parse(localStorage.getItem("itemsAdded")) || [];

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
      localStorage.setItem("itemsAdded", JSON.stringify(this.itemsAdded));

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

    hide() {
      this.form.reset();
      this.form.item_unit_cost = 0.0;
      this.isEditing = false;
      this.editItem = null;
      this.editIndex = null;
      this.showModal = false;
    },
  },
};
</script>
