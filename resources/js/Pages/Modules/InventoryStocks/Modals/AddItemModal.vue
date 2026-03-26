<template>
  <b-modal
    :model-value="modelValue"
    title="Add Item"
    centered
    no-close-on-backdrop
    hide-footer
    header-class="add-item-header"
    body-class="add-item-body"
    @update:modelValue="(value) => $emit('update:modelValue', value)"
    @hidden="resetPendingItem"
  >
    <div class="add-item-form">
      <div class="mb-3">
        <label class="form-label">Stock Item</label>
        <Multiselect
          v-model="pendingItem.stockId"
          :options="pendingStockOptions"
          :searchable="true"
          :can-clear="true"
          value-prop="id"
          label="stock_label"
          :placeholder="loadingStockOptions ? 'Loading items...' : 'Search item'"
          :class="{ 'is-invalid': pendingItemError }"
        />
        <div class="invalid-feedback">{{ pendingItemError }}</div>
      </div>

      <div class="add-item-table-card">
        <table class="table add-item-table mb-0">
          <thead>
            <tr>
              <th>Unit</th>
              <th>Item Name</th>
              <th>Unit Price</th>
              <th>Quantity</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="pendingSelectedStock">
              <td>{{ pendingSelectedStock.inventory_unit || 'Unit N/A' }}</td>
              <td>
                <div class="add-item-name">{{ pendingSelectedStock.inventory_name }}</div>
                <div class="add-item-subtext">{{ pendingSelectedStock.location_name || 'No location' }}</div>
              </td>
              <td>-</td>
              <td>{{ formatNumber(pendingSelectedStock.quantity) }}</td>
            </tr>
            <tr v-else>
              <td colspan="4" class="add-item-empty">Select an item to preview its details.</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="add-item-footer">
        <button type="button" class="btn add-item-cancel-btn" @click="$emit('update:modelValue', false)">Cancel</button>
        <button type="button" class="btn add-item-submit-btn" @click="confirm">Ok</button>
      </div>
    </div>
  </b-modal>
</template>

<script>
import axios from 'axios';
import Multiselect from '@vueform/multiselect';

export default {
  name: 'AddWithdrawalItemModal',
  components: { Multiselect },
  props: {
    modelValue: { type: Boolean, default: false },
    stockOptions: { type: Array, default: () => [] },
    receivingId: { type: [Number, String, null], default: null },
    selectedItemIds: { type: Array, default: () => [] },
  },
  emits: ['update:modelValue', 'select'],
  data() {
    return {
      loadingStockOptions: false,
      localStockOptions: [],
      pendingItem: {
        stockId: '',
      },
      pendingItemError: '',
    };
  },
  computed: {
    availableStockOptions() {
      const sourceOptions = this.localStockOptions.length ? this.localStockOptions : this.stockOptions;

      return sourceOptions
        .filter((stock) => Number(stock.quantity || 0) > 0)
        .map((stock) => ({
          ...stock,
          id: String(stock.id),
          stock_label: `${stock.inventory_name || 'Item'}${stock.location_name ? ` - ${stock.location_name}` : ''} (${this.formatNumber(stock.quantity)})`,
        }));
    },
    pendingStockOptions() {
      const selectedIds = this.selectedItemIds.map((id) => String(id)).filter(Boolean);
      return this.availableStockOptions.filter((stock) => !selectedIds.includes(String(stock.id)));
    },
    pendingSelectedStock() {
      return this.availableStockOptions.find((stock) => String(stock.id) === String(this.pendingItem.stockId)) || null;
    },
  },
  watch: {
    modelValue: {
      immediate: true,
      async handler(value) {
        if (value) {
          await this.ensureStockOptions();
          return;
        }

        this.localStockOptions = [];
      },
    },
  },
  methods: {
    async ensureStockOptions() {
      this.pendingItemError = '';

      if (this.availableStockOptions.length || !this.receivingId) return;

      this.loadingStockOptions = true;

      try {
        const response = await axios.get('/inventory-stocks', {
          params: {
            option: 'receiving-withdrawal-items',
            receiving_id: this.receivingId,
          },
        });

        this.localStockOptions = response.data?.data || [];
      } catch (error) {
        this.localStockOptions = [];
        this.pendingItemError = 'Unable to fetch receiving items.';
      } finally {
        this.loadingStockOptions = false;
      }
    },
    resetPendingItem() {
      this.pendingItemError = '';
      this.pendingItem = { stockId: '' };
    },
    confirm() {
      if (!this.pendingItem.stockId) {
        this.pendingItemError = 'Select an item to add.';
        return;
      }

      const selected = this.availableStockOptions.find((stock) => String(stock.id) === String(this.pendingItem.stockId));

      if (!selected) {
        this.pendingItemError = 'Selected stock item is unavailable.';
        return;
      }

      this.$emit('select', selected);
      this.$emit('update:modelValue', false);
    },
    formatNumber(value) {
      return new Intl.NumberFormat().format(Number(value || 0));
    },
  },
};
</script>

<style scoped>
.add-item-header {
  background: linear-gradient(135deg, #eff6ff 0%, #f8fbff 100%);
  border-bottom: 1px solid #dbe8ff;
}

.add-item-body {
  padding: 1.25rem !important;
}

.add-item-form :deep(.multiselect),
.add-item-form .form-select {
  min-height: 52px;
}

.add-item-table-card {
  overflow: hidden;
  border: 1px solid #dbe8ff;
  border-radius: 14px;
  background: #fff;
}

.add-item-table thead th {
  background: #dbeafe;
  color: #1d4ed8;
  border-bottom: 0;
  font-size: 0.85rem;
  font-weight: 800;
  padding: 0.9rem 1rem;
  white-space: nowrap;
}

.add-item-table tbody td {
  padding: 0.95rem 1rem;
  vertical-align: top;
  border-color: #edf2ff;
  color: #334155;
}

.add-item-name {
  font-weight: 700;
  color: #1f2d3d;
}

.add-item-subtext {
  margin-top: 0.25rem;
  font-size: 0.8rem;
  color: #64748b;
}

.add-item-empty {
  text-align: center;
  color: #94a3b8;
  padding: 1.2rem 1rem !important;
}

.add-item-footer {
  display: flex;
  justify-content: flex-end;
  gap: 0.65rem;
  margin-top: 1.25rem;
}

.add-item-cancel-btn,
.add-item-submit-btn {
  min-width: 88px;
  min-height: 46px;
  border-radius: 12px;
  font-weight: 700;
}

.add-item-cancel-btn {
  border: 0;
  background: #3b82f6;
  color: #fff;
}

.add-item-submit-btn {
  border: 0;
  background: #40528f;
  color: #fff;
}
</style>
