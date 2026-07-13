<template>
  <b-modal
    :model-value="modelValue"
    header-class="p-0 border-0"
    content-class="border-0 shadow-lg rounded-4 overflow-hidden"
    body-class="p-0"
    footer-class="border-top px-4 py-3 bg-body-tertiary"
    :title="form?.id ? 'Edit Item' : 'Add Item'"
    size="md"
    class="v-modal-custom"
    modal-class="zoomIn"
    centered
    no-close-on-backdrop
    @update:modelValue="(value) => $emit('update:modelValue', value)"
  >
    <template #header="{ hide }">
      <div
        class="item-modal-header d-flex align-items-center justify-content-between gap-3 w-100 px-4 py-3"
      >
        <div class="d-flex align-items-center gap-3">
          <div class="item-modal-icon">
            <i class="ri-barcode-box-line"></i>
          </div>
          <div>
            <h5 class="mb-0 fw-bold text-white">
              {{ form?.id ? "Edit Inventory Item" : "New Inventory Item" }}
            </h5>
            <p class="mb-0 small" style="color: rgba(255, 255, 255, 0.72)">
              Catalog entry — stock levels are managed in Stocks
            </p>
          </div>
        </div>
        <button type="button" class="item-modal-close" @click="hide">
          <i class="ri-close-line"></i>
        </button>
      </div>
    </template>

    <form class="px-4 py-4" @submit.prevent="$emit('submit')">
      <div class="row g-3">
        <div class="col-12">
          <label class="form-label fw-semibold" for="item_name">
            Item Name <span class="text-danger">*</span>
          </label>
          <input
            id="item_name"
            :value="form.name"
            class="form-control form-control-lg"
            :class="{ 'is-invalid': errors.name }"
            placeholder="e.g. Bond Paper"
            @input="updateField('name', $event.target.value)"
          />
          <div v-if="errors.name" class="invalid-feedback">{{ errors.name[0] }}</div>
        </div>

        <div v-if="form.id" class="col-sm-5">
          <label class="form-label fw-semibold" for="item_code">Item Code</label>
          <input
            id="item_code"
            :value="form.code"
            class="form-control font-monospace"
            :class="{ 'is-invalid': errors.code }"
            @input="updateField('code', $event.target.value)"
          />
          <div v-if="errors.code" class="invalid-feedback">{{ errors.code[0] }}</div>
        </div>

        <div :class="form.id ? 'col-sm-7' : 'col-12'">
          <label class="form-label fw-semibold" for="category_id">
            Category <span class="text-danger">*</span>
          </label>
          <Multiselect
            id="category_id"
            :model-value="form.category_id ? String(form.category_id) : ''"
            :options="categoryOptions"
            :searchable="true"
            :class="{ 'is-invalid': errors.category_id }"
            placeholder="Select a category"
            @update:modelValue="updateField('category_id', $event)"
          />
          <div v-if="errors.category_id" class="invalid-feedback d-block">
            {{ errors.category_id[0] }}
          </div>
        </div>
      </div>

      <div class="item-modal-hint mt-4">
        <i class="ri-information-line me-2"></i>
        Stock levels, unit cost, and asset properties are managed separately after the
        item is created.
      </div>
    </form>

    <template #footer>
      <div class="d-flex gap-2 justify-content-end w-100">
        <b-button variant="light" class="px-4" @click="$emit('update:modelValue', false)"
          >Cancel</b-button
        >
        <b-button
          variant="primary"
          class="px-4"
          :disabled="saving"
          @click="$emit('submit')"
        >
          <span v-if="saving"
            ><span class="spinner-border spinner-border-sm me-2"></span>Saving…</span
          >
          <span v-else>{{ form?.id ? "Update Item" : "Save Item" }}</span>
        </b-button>
      </div>
    </template>
  </b-modal>
</template>

<script>
import Multiselect from "@vueform/multiselect";

export default {
  name: "ItemModal",
  components: { Multiselect },
  props: {
    modelValue: { type: Boolean, default: false },
    form: { type: Object, default: () => ({}) },
    errors: { type: Object, default: () => ({}) },
    saving: { type: Boolean, default: false },
    categories: { type: Array, default: () => [] },
  },
  emits: ["update:modelValue", "update:form", "submit"],
  computed: {
    categoryOptions() {
      return this.categories.map((cat) => ({
        value: String(cat.id ?? cat.value),
        label: cat.name,
      }));
    },
  },
  methods: {
    updateField(field, value) {
      this.$emit("update:form", { ...this.form, [field]: value });
    },
  },
};
</script>

<style scoped>
.item-modal-header {
  background: linear-gradient(135deg, #4b5b93 0%, #38467a 100%);
  min-height: 74px;
}

.item-modal-icon {
  width: 42px;
  height: 42px;
  border-radius: 14px;
  background: rgba(255, 255, 255, 0.15);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
  color: #fff;
  flex-shrink: 0;
}

.item-modal-close {
  width: 34px;
  height: 34px;
  border-radius: 999px;
  border: 1px solid rgba(255, 255, 255, 0.22);
  background: rgba(255, 255, 255, 0.1);
  color: #fff;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 1.1rem;
  flex-shrink: 0;
  transition: background 0.15s;
}

.item-modal-close:hover {
  background: rgba(255, 255, 255, 0.22);
}

.item-code-auto-badge {
  display: inline-flex;
  align-items: center;
  padding: 0.1rem 0.4rem;
  margin-left: 0.35rem;
  border-radius: 5px;
  background: rgba(75, 91, 147, 0.1);
  color: #4b5b93;
  font-size: 0.65rem;
  font-weight: 800;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  vertical-align: middle;
}

.item-code-wrap {
  position: relative;
  display: flex;
  align-items: center;
}

.item-code-input {
  padding-right: 2.4rem;
}

.item-code-regen {
  position: absolute;
  right: 0.45rem;
  width: 26px;
  height: 26px;
  border: 0;
  background: transparent;
  color: #94a3b8;
  cursor: pointer;
  border-radius: 6px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 0.9rem;
  transition: background 0.15s, color 0.15s;
}

.item-code-regen:hover {
  background: rgba(75, 91, 147, 0.1);
  color: #4b5b93;
}

.item-modal-hint {
  display: flex;
  align-items: flex-start;
  gap: 0;
  padding: 0.65rem 0.85rem;
  border-radius: 12px;
  background: rgba(75, 91, 147, 0.07);
  border: 1px solid rgba(75, 91, 147, 0.12);
  color: #64748b;
  font-size: 0.82rem;
  line-height: 1.45;
}
</style>
