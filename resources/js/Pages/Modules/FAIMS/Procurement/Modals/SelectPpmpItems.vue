<template>
  <b-modal
    v-model="showModal"
    header-class="p-3 bg-light"
    :title="isEditing ? 'Change Item' : 'Add Items'"
    size="xl"
    class="v-modal-custom"
    modal-class="zoomIn"
    centered
    no-close-on-backdrop
  >
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
      <div class="ppmp-item-search">
        <i class="ri-search-line"></i>
        <input
          v-model="keyword"
          type="search"
          class="form-control form-control-sm"
          placeholder="Search items..."
        />
      </div>

      <div class="text-muted fs-12">
        {{ selectedCount }} of {{ availableItems.length }} item(s) selected
      </div>
    </div>

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
      <div class="form-check mb-0">
        <input
          id="ppmp-select-all-items"
          class="form-check-input"
          type="checkbox"
          :checked="allFilteredSelected"
          :disabled="!filteredItems.length || isEditing"
          @change="toggleAll($event.target.checked)"
        />
        <label class="form-check-label" for="ppmp-select-all-items">
          Select all items
        </label>
      </div>
      <strong class="text-primary fs-12">{{ formatCurrency(selectedAmount) }}</strong>
    </div>

    <div class="table-responsive border rounded ppmp-selection-table-wrap">
      <table class="table align-middle mb-0 ppmp-selection-table">
        <thead>
          <tr class="fs-11">
            <th style="width: 6%" class="text-center">Select</th>
            <th>Item</th>
            <th style="width: 16%" class="text-center">Qty/Unit</th>
            <th style="width: 14%" class="text-end">Unit Cost</th>
            <th style="width: 14%" class="text-end">ABC</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="item in paginatedItems" :key="item.value">
            <td class="text-center">
              <input
                :id="`ppmp-item-${item.value}`"
                class="form-check-input"
                type="checkbox"
                :checked="isSelected(item.value)"
                @change="toggleItem(item.value, $event.target.checked)"
              />
            </td>

            <td>
              <div class="fw-semibold">{{ item.item_name || "-" }}</div>
              <div
                class="text-muted small ppmp-selection-description"
                v-html="item.item_description || '-'"
              />
            </td>
            
          
            <td class="text-center">
              {{ formatQuantity(item.item_quantity) }} {{ item.item_qunatity > 1 ? item.item_unit_type_short : item.item_unit_type.name_long }}
            </td>
            <td class="text-end">{{ formatCurrency(item.item_unit_cost) }}</td>

            <td class="text-end fw-semibold">{{ formatCurrency(item.total_cost) }}</td>
          </tr>

          <tr v-if="isLoading">
            <td colspan="7" class="text-center text-muted py-4">Loading PPMP items...</td>
          </tr>
          <tr v-else-if="!filteredItems.length">
            <td colspan="7" class="text-center text-muted py-4">
              <div>{{ emptyMessage }}</div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div
      v-if="filteredItems.length"
      class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3"
    >
      <div class="text-muted fs-12">
        Showing {{ paginationStart }}-{{ paginationEnd }} of
        {{ filteredItems.length }} item(s)
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
      <b-button @click="hide" variant="light" block>Cancel</b-button>

      <b-button @click="saveSelection" variant="primary" :disabled="!selectedCount" block>
        {{ isEditing ? "Update Selected Item" : "Use Selected Items" }}
      </b-button>
    </template>
  </b-modal>
</template>

<script>
export default {
  props: {
    ppmpItems: {
      type: Array,
      default: () => [],
    },
    isLoading: {
      type: Boolean,
      default: false,
    },
    existingItems: {
      type: Array,
      default: () => [],
    },
    storageKey: {
      type: String,
      default: "itemsAdded",
    },
  },
  emits: ["refresh", "switch-to-manual"],
  data() {
    return {
      showModal: false,
      keyword: "",
      selectedIds: [],
      page: 1,
      itemsPerPage: 10,
      isEditing: false,
      editIndex: null,
      itemMode: "ppmp",
    };
  },
  watch: {
    keyword() {
      this.page = 1;
    },
    itemsPerPage() {
      this.page = 1;
    },
    filteredItems() {
      if (this.page > this.totalPages) {
        this.page = this.totalPages;
      }
    },
  },
  computed: {
    filteredItems() {
      const keyword = this.keyword.trim().toLowerCase();
      const sourceItems = this.availableItems;

      if (!keyword) {
        return sourceItems;
      }

      return sourceItems.filter((item) => {
        const searchable = [
          item.ppmp_no,
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
    selectedItems() {
      const selectedIds = new Set(this.selectedIds.map((id) => Number(id)));
      return this.ppmpItems.filter((item) => selectedIds.has(Number(item.value)));
    },
    selectedExistingPpmpItemIds() {
      return new Set(
        this.existingItems.map((item) => Number(item.ppmp_item_id)).filter(Boolean)
      );
    },
    availableItems() {
      if (this.isEditing) {
        return this.ppmpItems;
      }

      return this.ppmpItems.filter(
        (item) => !this.selectedExistingPpmpItemIds.has(Number(item.value))
      );
    },
    ppmpNoDisplay() {
      const ppmpNos = this.ppmpItems.map((item) => item.ppmp_no).filter(Boolean);
      const uniquePpmpNos = [...new Set(ppmpNos)];

      if (!uniquePpmpNos.length) {
        return "-";
      }

      if (uniquePpmpNos.length === 1) {
        return uniquePpmpNos[0];
      }

      return `${uniquePpmpNos.length} PPMPs`;
    },
    planDisplay() {
      const planNames = this.ppmpItems
        .map((item) => item.plan_name || "PPMP")
        .filter(Boolean);
      const uniquePlanNames = [...new Set(planNames)];

      if (!uniquePlanNames.length) {
        return "-";
      }

      return uniquePlanNames.length === 1 ? uniquePlanNames[0] : "Multiple Plans";
    },
    selectedCount() {
      return this.selectedIds.length;
    },
    selectedAmount() {
      return this.selectedItems.reduce(
        (sum, item) => sum + (Number(item.total_cost) || 0),
        0
      );
    },
    allFilteredSelected() {
      return (
        this.filteredItems.length > 0 &&
        this.filteredItems.every((item) => this.isSelected(item.value))
      );
    },
    totalPages() {
      return Math.max(Math.ceil(this.filteredItems.length / this.itemsPerPage), 1);
    },
    paginatedItems() {
      const start = (this.page - 1) * this.itemsPerPage;
      return this.filteredItems.slice(start, start + this.itemsPerPage);
    },
    paginationStart() {
      if (!this.filteredItems.length) {
        return 0;
      }

      return (this.page - 1) * this.itemsPerPage + 1;
    },
    paginationEnd() {
      return Math.min(this.page * this.itemsPerPage, this.filteredItems.length);
    },
    emptyMessage() {
      if (this.ppmpItems.length && !this.availableItems.length) {
        return "All available PPMP items are already selected in this PR or another active PR.";
      }

      return this.ppmpItems.length
        ? "No PPMP items match your search."
        : "No consolidated PPMP items are available for the selected unit.";
    },
  },
  methods: {
    show() {
      this.keyword = "";
      this.selectedIds = [];
      this.page = 1;
      this.isEditing = false;
      this.editIndex = null;
      this.itemMode = "ppmp";
      this.showModal = true;
    },
    edit(item, index) {
      this.keyword = "";
      this.selectedIds = item?.ppmp_item_id ? [Number(item.ppmp_item_id)] : [];
      this.page = 1;
      this.isEditing = true;
      this.editIndex = index;
      this.itemMode = "ppmp";
      this.showModal = true;
    },
    hide() {
      this.showModal = false;
      this.keyword = "";
      this.selectedIds = [];
      this.page = 1;
      this.isEditing = false;
      this.editIndex = null;
      this.itemMode = "ppmp";
    },
    switchToManual() {
      this.hide();
      this.$emit("switch-to-manual");
    },
    isSelected(itemId) {
      return this.selectedIds.some((id) => Number(id) === Number(itemId));
    },
    toggleItem(itemId, checked) {
      const numericId = Number(itemId);

      if (this.isEditing) {
        this.selectedIds = checked ? [numericId] : [];
        return;
      }

      if (checked) {
        if (!this.isSelected(numericId)) {
          this.selectedIds.push(numericId);
        }
      } else {
        this.selectedIds = this.selectedIds.filter((id) => Number(id) !== numericId);
      }
    },
    toggleAll(checked) {
      if (this.isEditing) {
        return;
      }

      const visibleIds = this.filteredItems.map((item) => Number(item.value));

      if (checked) {
        this.selectedIds = Array.from(
          new Set([...this.selectedIds.map((id) => Number(id)), ...visibleIds])
        );
      } else {
        const visibleIdSet = new Set(visibleIds);
        this.selectedIds = this.selectedIds.filter((id) => !visibleIdSet.has(Number(id)));
      }
    },
    saveSelection() {
      const itemsAdded = JSON.parse(localStorage.getItem(this.storageKey)) || [];
      const existingPpmpItemIds = new Set(
        itemsAdded
          .map((existingItem) => Number(existingItem.ppmp_item_id))
          .filter(Boolean)
      );
      const selectedItems = this.selectedItems
        .filter(
          (ppmpItem) => this.isEditing || !existingPpmpItemIds.has(Number(ppmpItem.value))
        )
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
        itemsAdded.splice(this.editIndex, 1, ...selectedItems);
      } else {
        itemsAdded.push(...selectedItems);
      }

      localStorage.setItem(this.storageKey, JSON.stringify(itemsAdded));
      this.$emit("refresh");
      this.hide();
    },
    unitFromQuantityLabel(item) {
      const quantity = String(item?.item_quantity ?? "").trim();
      const label = String(item?.quantity_label || "").trim();

      if (!label) {
        return "";
      }

      return label.replace(quantity, "").trim();
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
  line-clamp: 2;
}

.ppmp-page-size {
  width: 76px;
}
</style>
