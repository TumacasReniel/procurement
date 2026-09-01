<template>
  <b-modal
    v-model="visible"
    header-class="p-3 bg-light"
    title="Select Items"
    size="xl"
    class="v-modal-custom"
    modal-class="zoomIn"
    centered
    no-close-on-backdrop
  >
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
      <div>
        <div class="text-muted fs-12">Available Items</div>
        <div class="fw-semibold text-primary">{{ items.length }}</div>
      </div>

      <div class="text-md-end">
        <div class="text-muted fs-12">Selected Amount</div>
        <div class="fw-semibold text-success">{{ formatCurrency(selectedTotal) }}</div>
      </div>
    </div>

    <div :class="['selection-strip', { 'selection-strip--invalid': noSelectionError }]">
      <div class="form-check mb-0">
        <input
          id="category-select-all-items"
          class="form-check-input"
          type="checkbox"
          :checked="allSelected"
          :disabled="items.length === 0"
          @change="toggleAll"
        />
        <label class="form-check-label" for="category-select-all-items">
          Select items
        </label>
      </div>

      <div class="text-muted fs-12">
        {{ selectedItems.length }} of {{ items.length }} item(s) selected
      </div>
    </div>
    <div v-if="noSelectionError" class="text-danger small fw-semibold mb-2">
      {{ noSelectionError }}
    </div>

    <div class="table-responsive border rounded ppmp-selection-table-wrap">
      <table class="table align-middle mb-0 ppmp-selection-table">
        <thead>
          <tr class="fs-11">
            <th style="width: 6%" class="text-center">Select</th>
            <th>Item</th>
            <th>Requesting Unit</th>
            <th style="width: 10%" class="text-center">Qty/Unit</th>
            <th style="width: 14%" class="text-end">Unit Cost</th>
            <th style="width: 14%" class="text-end">ABC</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="items.length === 0">
            <td colspan="8" class="text-center text-muted py-4">
              No items matched the selected fund source and item category in APP.
            </td>
          </tr>
          <tr v-for="item in paginatedItems" :key="item.value" class="item-row">
            <td class="text-center">
              <input
                class="form-check-input"
                type="checkbox"
                :checked="localSelectedIds.includes(item.value)"
                @change="toggleItem(item)"
              />
            </td>
            
            <td>
              <div class="fw-semibold">{{ item.item_name || "-" }}</div>
              <div
                v-if="item.item_description"
                class="text-muted small ppmp-selection-description"
                v-html="item.item_description"
              />
            </td>
            <td>
            {{ item.unit_name }}
            </td>
            <td class="text-center">{{ item.quantity_label || item.item_quantity }}</td>
            <td class="text-end">{{ formatCurrency(item.item_unit_cost) }}</td>
            <td class="text-end fw-semibold">{{ formatCurrency(item.total_cost) }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div
      v-if="items.length"
      class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3"
    >
      <div class="text-muted fs-12">
        Showing {{ paginationStart }}-{{ paginationEnd }} of {{ items.length }} item(s)
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

        <b-button size="sm" variant="light" :disabled="page <= 1" @click="page -= 1">
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

    <template v-slot:footer>
      <b-button type="button" variant="light" block @click="close">Cancel</b-button>
      <b-button type="button" variant="primary" block @click="loadSelected">
        Load Selected Items
      </b-button>
    </template>
  </b-modal>
</template>

<script>
export default {
  props: {
    modelValue: {
      type: Boolean,
      default: false,
    },
    items: {
      type: Array,
      default: () => [],
    },
    selectedIds: {
      type: Array,
      default: () => [],
    },
  },
  emits: ["update:modelValue", "load"],
  data() {
    return {
      localSelectedIds: [],
      page: 1,
      itemsPerPage: 10,
      noSelectionError: "",
    };
  },
  computed: {
    visible: {
      get() {
        return this.modelValue;
      },
      set(value) {
        this.$emit("update:modelValue", value);
      },
    },
    selectedItems() {
      return this.items.filter((item) => this.localSelectedIds.includes(item.value));
    },
    selectedTotal() {
      return this.selectedItems.reduce(
        (sum, item) => sum + Number(item.total_cost || 0),
        0
      );
    },
    allSelected() {
      return (
        this.items.length > 0 &&
        this.items.every((item) => this.localSelectedIds.includes(item.value))
      );
    },
    totalPages() {
      return Math.max(Math.ceil(this.items.length / this.itemsPerPage), 1);
    },
    paginatedItems() {
      const start = (this.page - 1) * this.itemsPerPage;
      return this.items.slice(start, start + this.itemsPerPage);
    },
    paginationStart() {
      if (!this.items.length) {
        return 0;
      }

      return (this.page - 1) * this.itemsPerPage + 1;
    },
    paginationEnd() {
      return Math.min(this.page * this.itemsPerPage, this.items.length);
    },
  },
  watch: {
    modelValue(value) {
      if (value) {
        this.page = 1;
        this.noSelectionError = "";
        this.localSelectedIds = this.items
          .filter((item) => this.selectedIds.includes(item.value))
          .map((item) => item.value);
      }
    },
    items() {
      if (this.page > this.totalPages) {
        this.page = this.totalPages;
      }

      if (this.modelValue) {
        this.localSelectedIds = this.items
          .filter((item) => this.selectedIds.includes(item.value))
          .map((item) => item.value);
      }
    },
    itemsPerPage() {
      this.page = 1;
    },
  },
  methods: {
    close() {
      this.noSelectionError = "";
      this.visible = false;
    },
    toggleItem(item) {
      if (this.localSelectedIds.includes(item.value)) {
        this.localSelectedIds = this.localSelectedIds.filter((id) => id !== item.value);
      } else {
        this.localSelectedIds = [...this.localSelectedIds, item.value];
      }

      if (this.selectedItems.length) {
        this.noSelectionError = "";
      }
    },
    toggleAll() {
      this.localSelectedIds = this.allSelected
        ? []
        : this.items.map((item) => item.value);

      if (this.selectedItems.length) {
        this.noSelectionError = "";
      }
    },
    loadSelected() {
      if (!this.selectedItems.length) {
        this.noSelectionError = "Please select at least one item.";
        return;
      }

      this.$emit("load", this.selectedItems);
    },
    formatCurrency(value) {
      return new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
      }).format(Number(value || 0));
    },
  },
};
</script>

<style scoped>
.selection-strip {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: 0.5rem;
  padding: 0.5rem 0.65rem;
  border: 1px solid transparent;
  border-radius: 8px;
}

.selection-strip--invalid {
  border-color: #f06548;
  background: rgba(240, 101, 72, 0.06);
}

.ppmp-selection-table-wrap {
  max-height: 58vh;
  overflow: auto;
  background: #fff;
}

.ppmp-selection-table thead th {
  position: sticky;
  top: 0;
  z-index: 2;
  background: #f8f9fa;
  color: #495057;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0;
  border-bottom: 1px solid #e9ecef;
  white-space: nowrap;
}

.ppmp-selection-table tbody td {
  border-color: #eef0f3;
}

.ppmp-selection-table tbody tr:hover {
  background: #f8fbff;
}

.ppmp-selection-description {
  max-width: 520px;
  line-height: 1.4;
}

.ppmp-page-size {
  width: 76px;
}

@media (max-width: 768px) {
  .selection-strip {
    align-items: flex-start;
    flex-direction: column;
  }
}
</style>
