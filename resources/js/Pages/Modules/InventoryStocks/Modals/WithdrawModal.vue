<template>
  <b-modal
    :model-value="modelValue"
    title="Items Withdrawal"
    size="xl"
    centered
    no-close-on-backdrop
    hide-footer
    header-class="sales-withdraw-header"
    body-class="sales-withdraw-body"
    modal-class="zoomIn"
    @update:modelValue="(value) => $emit('update:modelValue', value)"
  >
    <form class="sales-withdraw-form" @submit.prevent="$emit('submit')">
      <div class="sales-withdraw-shell">
        <section class="sales-main">


          <div class="sales-card sales-meta-card">
            <div class="sales-meta-grid">
         

              <div class="sales-field">
                <label class="form-label">Employee</label>
                <div class="sales-select-input">
                  <i class="ri-user-3-line"></i>
                  <Multiselect
                    :model-value="selectedEmployeeId"
                    :options="employeeOptions"
                    :searchable="true"
                    :can-clear="true"
                    value-prop="id"
                    label="employee_label"
                    :placeholder="loadingEmployees ? 'Loading employees...' : 'Search employee'"
                    @update:model-value="selectEmployee"
                  />
                </div>
              </div>
            </div>
          </div>

          <div class="sales-actions-row">
            <button
              v-if="canAddItems"
              type="button"
              class="btn sales-table-add-btn"
              @click="openAddItemModal"
            >
              <i class="ri-add-line me-1"></i>Add Item
            </button>
          </div>

          <div class="sales-card sales-table-card">
            <div class="sales-table-wrap">
              <table class="table sales-table mb-0">
                <thead>
                  <tr>
                    <th>Unit</th>
                    <th>Item Name</th>
                    <th>Unit Price</th>
                    <th>Quantity</th>
                    <th>Total</th>
                    <th>More Info</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="!normalizedItems.length">
                    <td colspan="6">
                      <div class="sales-empty-state">
                        <i class="ri-shopping-basket-2-line"></i>
                        <div>No items added yet.</div>
                      </div>
                    </td>
                  </tr>
                  <tr v-for="(item, index) in normalizedItems" :key="item.uid || `withdraw-${index}`">
                    <td>
                      <div class="sales-unit-cell">{{ stockMeta(item).inventory_unit || 'Unit N/A' }}</div>
                    </td>
                    <td>
                      <div v-if="selectable && hasItemsArray" class="sales-select-wrap">
                        <Multiselect
                          :model-value="selectedStockId(item)"
                          :options="stockOptionsFor(index)"
                          :searchable="true"
                          :can-clear="true"
                          value-prop="id"
                          label="stock_label"
                          placeholder="Search item"
                          @update:model-value="selectStock(index, $event)"
                        />
                        <div class="invalid-feedback">{{ itemError(index, 'inventory_id') || itemError(index, 'id') }}</div>
                      </div>
                      <div class="sales-product-block">
                        <div class="sales-product-name">{{ item.inventory_name || 'Select stock item' }}</div>
                      </div>
                    </td>
                    <td>
                      <div class="sales-money-cell">-</div>
                    </td>
                    <td>
                      <input
                        :value="item.quantity"
                        @input="updateItemQuantity(index, $event)"
                        type="number"
                        min="0.01"
                        step="0.01"
                        class="form-control sales-qty-input"
                        :class="{ 'is-invalid': itemError(index, 'quantity') || (!hasItemsArray && errors?.quantity) }"
                        placeholder="0"
                      />
                      <div class="invalid-feedback d-block">
                        {{ itemError(index, 'quantity') || (!hasItemsArray ? errors?.quantity : '') }}
                      </div>
                    </td>
                    <td>
                      <div class="sales-money-cell">-</div>
                    </td>
                    <td>
                      <div class="sales-product-meta sales-more-info">
                        <span class="sales-meta-tag">{{ stockMeta(item).location_name || primaryLocation }}</span>
                        <span class="sales-meta-tag status">{{ item.current_status || 'available' }}</span>
                        <button
                          v-if="canRemoveItems"
                          type="button"
                          class="btn sales-remove-btn"
                          @click="removeItem(index)"
                        >
                          <i class="ri-delete-bin-line"></i>
                        </button>
                      </div>
                      <button
                        v-if="false"
                        type="button"
                        class="btn sales-remove-btn"
                        @click="removeItem(index)"
                      >
                        <i class="ri-delete-bin-line"></i>
                      </button>
                      <span v-else class="sales-action-muted">Auto</span>
                    </td>
                  </tr>
                </tbody>
                <tfoot>
                  <tr>
                    <td colspan="4" class="text-end sales-total-label">Total Quantity:</td>
                    <td colspan="2" class="sales-total-value">{{ formatNumber(totalWithdrawalQuantity) }}</td>
                  </tr>
                </tfoot>
              </table>
            </div>
          </div>
        </section>

        <aside class="sales-side">
          <div class="sales-card sales-side-card">
            <div class="sales-side-heading">
              <i class="ri-user-settings-line"></i>
              <span>Employee Information</span>
            </div>
            <div v-if="!selectedEmployeeId" class="sales-info-empty">
              <div class="sales-info-empty-inner">
                <i class="ri-user-search-line"></i>
                <span>Select employee first</span>
              </div>
            </div>
            <div v-else class="sales-info-list">
              <div class="sales-info-item">
                <span>Name</span>
                <strong>{{ selectedEmployee.name || '-' }}</strong>
              </div>
              <div class="sales-info-item">
                <span>Position</span>
                <strong>{{ selectedEmployee.position || '-' }}</strong>
              </div>

            </div>
          </div>

          <div class="sales-card sales-side-card">

            <div class="sales-field">
              <label class="form-label">Remarks</label>
              <textarea
                :value="form.remarks"
                @input="updateField('remarks', $event.target.value)"
                rows="5"
                class="form-control"
                placeholder="Purpose of withdrawal or office note"
              ></textarea>
            </div>
          </div>

          <div class="sales-card sales-side-card sales-summary-card">
            <div class="sales-side-heading">
              <i class="ri-bar-chart-box-line"></i>
              <span>Summary</span>
            </div>
            <div class="sales-summary-item">
              <span>Total Items</span>
              <strong>{{ normalizedItems.length }}</strong>
            </div>
            <div class="sales-summary-item">
              <span>Total Quantity</span>
              <strong>{{ formatNumber(totalWithdrawalQuantity) }}</strong>
            </div>

          </div>

          <div class="sales-footer">
            <button type="button" class="btn sales-cancel-btn" @click="$emit('update:modelValue', false)">Cancel</button>
            <button type="submit" class="btn sales-submit-btn" :disabled="saving">
              <i class="ri-check-line me-1"></i>{{ saving ? 'Saving...' : 'Submit Withdrawal' }}
            </button>
          </div>
        </aside>
      </div>
    </form>
  </b-modal>

  <AddItemModal
    v-model="showAddItemModal"
    :stock-options="stockOptions"
    :receiving-id="form.receiving_id"
    :selected-item-ids="selectedItemIds"
    @select="handleSelectedStock"
  />
</template>

<script>
import axios from 'axios';
import Multiselect from '@vueform/multiselect';
import AddItemModal from '@/Pages/Modules/InventoryStocks/Modals/AddItemModal.vue';

export default {
  name: 'WithdrawModal',
  components: { Multiselect, AddItemModal },
  props: {
    modelValue: { type: Boolean, default: false },
    form: { type: Object, default: () => ({}) },
    errors: { type: Object, default: () => ({}) },
    saving: { type: Boolean, default: false },
    stockOptions: { type: Array, default: () => [] },
    selectable: { type: Boolean, default: false },
  },
  emits: ['update:modelValue', 'update:form', 'submit'],
  data() {
    return {
      employeeOptions: [],
      loadingEmployees: false,
      showAddItemModal: false,
    };
  },
  computed: {
    currentUser() {
      return this.$page?.props?.user?.data || {};
    },
    selectedEmployeeId() {
      return this.form?.requested_by_id ? String(this.form.requested_by_id) : '';
    },
    selectedEmployee() {
      const selected = this.employeeOptions.find((employee) => String(employee.id) === this.selectedEmployeeId);

      if (selected) {
        return selected;
      }

      return {
        id: this.currentUser.id,
        name: this.form?.requested_by || this.currentUser.name || this.currentUser.username || '',
        username: this.currentUser.username || '',
        position: this.currentUser.position || '',
      };
    },
    hasItemsArray() {
      return Array.isArray(this.form?.items);
    },
    normalizedItems() {
      if (this.hasItemsArray) {
        return this.form.items;
      }

      if (!this.form || Object.keys(this.form).length === 0) {
        return [];
      }

      return [{
        uid: 'single-withdraw-item',
        id: this.form.id ?? null,
        inventory_id: this.form.inventory_id ?? '',
        location_id: this.form.location_id ?? '',
        inventory_name: this.form.inventory_name ?? '',
        available_quantity: Number(this.form.available_quantity || 0),
        quantity: this.form.quantity ?? null,
        current_status: this.form.current_status ?? 'available',
      }];
    },
    availableStockOptions() {
      return this.stockOptions
        .filter((stock) => Number(stock.quantity || 0) > 0)
        .map((stock) => ({
          ...stock,
          id: String(stock.id),
          stock_label: `${stock.inventory_name || 'Item'}${stock.location_name ? ` - ${stock.location_name}` : ''} (${this.formatNumber(stock.quantity)})`,
        }));
    },
    canAddItems() {
      return this.selectable && this.hasItemsArray;
    },
    canRemoveItems() {
      return this.hasItemsArray && this.normalizedItems.length > 1;
    },
    totalWithdrawalQuantity() {
      return this.normalizedItems.reduce((sum, item) => sum + Number(item.quantity || 0), 0);
    },
    primaryLocation() {
      const firstItem = this.normalizedItems[0];
      const meta = this.stockMeta(firstItem);
      return meta.location_name || 'Auto-detected location';
    },
    selectedItemIds() {
      return this.normalizedItems
        .map((item) => String(item.id || ''))
        .filter(Boolean);
    },
  },
  watch: {
    modelValue: {
      immediate: true,
      handler(value) {
        if (value) {
          this.fetchEmployees();
        }
      },
    },
  },
  methods: {
    updateField(field, value) {
      this.$emit('update:form', {
        ...this.form,
        [field]: value,
      });
    },
    updateItems(items) {
      this.$emit('update:form', {
        ...this.form,
        items,
      });
    },
    emptyItem() {
      return {
        uid: `withdraw-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`,
        id: null,
        inventory_id: '',
        location_id: '',
        inventory_name: '',
        available_quantity: 0,
        quantity: null,
        current_status: 'available',
      };
    },
    async fetchEmployees() {
      if (this.employeeOptions.length) return;

      this.loadingEmployees = true;

      try {
        const response = await axios.get('/search', {
          params: {
            option: 'users',
            keyword: '',
          },
        });

        const employees = Array.isArray(response.data) ? response.data : [];

        this.employeeOptions = employees.map((employee) => ({
          id: employee.id || employee.value,
          name: employee.name || employee.fullname || employee.username || 'Unnamed User',
          username: employee.username || employee.code || '-',
          position: employee.position || employee.designation || '',
          division: employee.division || '',
          employee_label: `${employee.name || employee.fullname || employee.username || 'Unnamed User'}${employee.position || employee.designation ? ` - ${employee.position || employee.designation}` : ''}`,
        }));
      } catch (error) {
        this.employeeOptions = [{
          id: this.currentUser.id,
          name: this.currentUser.name || this.currentUser.username || 'Current Employee',
          username: this.currentUser.username || '-',
          position: this.currentUser.position || '',
          employee_label: `${this.currentUser.name || this.currentUser.username || 'Current Employee'}${this.currentUser.position ? ` - ${this.currentUser.position}` : ''}`,
        }];
      } finally {
        this.loadingEmployees = false;
      }
    },
    selectEmployee(value) {
      const selected = this.employeeOptions.find((employee) => String(employee.id) === String(value));

      this.$emit('update:form', {
        ...this.form,
        requested_by_id: selected?.id || null,
        requested_by: selected?.name || '',
      });
    },
    openAddItemModal() {
      this.showAddItemModal = true;
    },
    handleSelectedStock(selected) {
      if (this.hasItemsArray) {
        this.updateItems([
          ...this.normalizedItems,
          {
            ...this.emptyItem(),
            id: selected.id,
            inventory_id: String(selected.inventory_id ?? ''),
            location_id: selected.location_id ? String(selected.location_id) : '',
            inventory_name: selected.inventory_name || '',
            available_quantity: Number(selected.quantity || 0),
            quantity: null,
            current_status: selected.status || 'available',
          },
        ]);
      } else {
        this.$emit('update:form', {
          ...this.form,
          id: selected.id,
          inventory_id: String(selected.inventory_id ?? ''),
          location_id: selected.location_id ? String(selected.location_id) : '',
          inventory_name: selected.inventory_name || '',
          available_quantity: Number(selected.quantity || 0),
          quantity: null,
          current_status: selected.status || 'available',
        });
      }
    },
    addItem() {
      if (!this.hasItemsArray) return;
      this.updateItems([...this.normalizedItems, this.emptyItem()]);
    },
    removeItem(index) {
      if (!this.hasItemsArray) return;
      this.updateItems(this.normalizedItems.filter((_, itemIndex) => itemIndex !== index));
    },
    updateItemQuantity(index, event) {
      const value = event.target.value === '' ? null : Number(event.target.value);

      if (this.hasItemsArray) {
        const items = this.normalizedItems.map((item, itemIndex) => (
          itemIndex === index ? { ...item, quantity: value } : item
        ));
        this.updateItems(items);
        return;
      }

      this.$emit('update:form', {
        ...this.form,
        quantity: value,
      });
    },
    stockOptionsFor(index) {
      const selectedIds = this.normalizedItems
        .map((item, itemIndex) => (itemIndex === index ? null : String(item.id || '')))
        .filter(Boolean);

      return this.availableStockOptions.filter((stock) => !selectedIds.includes(String(stock.id)));
    },
    selectedStockId(item) {
      return item?.id ? String(item.id) : '';
    },
    stockMeta(item) {
      return this.stockOptions.find((stock) => String(stock.id) === String(item?.id)) || {};
    },
    itemError(index, field) {
      return this.errors?.items?.[index]?.[field] || '';
    },
    selectStock(index, value) {
      if (!this.hasItemsArray) return;

      const selected = this.availableStockOptions.find((stock) => String(stock.id) === String(value));
      const items = [...this.normalizedItems];

      if (!selected) {
        items[index] = this.emptyItem();
        this.updateItems(items);
        return;
      }

      items[index] = {
        ...items[index],
        id: selected.id,
        inventory_id: String(selected.inventory_id ?? ''),
        location_id: selected.location_id ? String(selected.location_id) : '',
        inventory_name: selected.inventory_name || '',
        available_quantity: Number(selected.quantity || 0),
        quantity: null,
        current_status: selected.status || 'available',
      };

      this.updateItems(items);
    },
    formatNumber(value) {
      return new Intl.NumberFormat().format(Number(value || 0));
    },
  },
};
</script>

<style scoped>
.sales-withdraw-header {
  padding: 0 !important;
  border-bottom: 0 !important;
  background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
  color: #fff;
}

.sales-withdraw-header :deep(.modal-title) {
  font-size: 2rem;
  font-weight: 800;
  padding: 1.25rem 1.5rem;
  color: #fff;
}

.sales-withdraw-body {
  padding: 1.5rem !important;
  background: linear-gradient(180deg, #f8fafb 0%, #f1f5f9 100%);
}

.sales-withdraw-form {
  color: #243447;
}

.sales-withdraw-shell {
  display: grid;
  grid-template-columns: minmax(0, 1.85fr) minmax(320px, 0.85fr);
  gap: 1.5rem;
}

.sales-main,
.sales-side {
  min-width: 0;
}

.sales-banner {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 1rem;
  padding: 1rem 1.15rem;
  margin-bottom: 1rem;
  border-radius: 22px;
  background: linear-gradient(135deg, rgba(59, 130, 246, 0.14) 0%, rgba(59, 130, 246, 0.06) 100%);
}

.sales-banner-chip {
  display: inline-flex;
  align-items: center;
  padding: 0.35rem 0.75rem;
  border-radius: 999px;
  background: rgba(63, 151, 127, 0.16);
  color: #166a57;
  font-size: 0.78rem;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.08em;
}

.sales-title {
  margin: 0.8rem 0 0.35rem;
  font-size: 1.7rem;
  font-weight: 800;
  color: #16313d;
}

.sales-subtitle {
  margin: 0;
  color: #617080;
  max-width: 720px;
}

.sales-card {
  border: 1px solid #e3e8ee;
  border-radius: 20px;
  background: #fff;
  box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
}

.sales-meta-card {
  padding: 1.2rem;
}

.sales-meta-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 1rem;
}

.sales-field .form-label {
  margin-bottom: 0.55rem;
  font-size: 0.95rem;
  font-weight: 800;
  color: #304457;
}

.sales-static-input,
.sales-readonly-pill,
.sales-select-input {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  min-height: 58px;
  padding: 0 1rem;
  border: 1px solid #dce4ea;
  border-radius: 14px;
  background: #fff;
}

.sales-static-input i,
.sales-readonly-pill i,
.sales-select-input i {
  font-size: 1.25rem;
  color: #7b8a8f;
}

.sales-static-input .form-control {
  border: 0;
  padding-inline: 0;
  box-shadow: none;
  background: transparent;
}

.sales-select-input .form-select {
  border: 0;
  padding-inline: 0;
  box-shadow: none;
  background: transparent;
}

.sales-readonly-pill span {
  font-weight: 600;
  color: #293846;
}

.sales-actions-row {
  margin: 1.15rem 0 0.8rem;
}

.sales-add-btn,
.sales-table-add-btn {
  border: 0;
  border-radius: 14px;
  background: #3b82f6;
  color: #fff;
  font-weight: 700;
  padding: 0.9rem 1.1rem;
}

.sales-table-card {
  overflow: hidden;
}

.sales-table-wrap {
  overflow-x: auto;
}

.sales-table {
  min-width: 760px;
}

.sales-table thead th {
  padding: 1rem;
  border-bottom: 0;
  background: #dbeafe;
  color: #1e40af;
  font-size: 0.8rem;
  font-weight: 800;
  letter-spacing: 0.02em;
  white-space: nowrap;
}

.sales-table tbody td,
.sales-table tfoot td {
  padding: 1rem;
  vertical-align: top;
  border-color: #edf1f5;
}

.sales-select-wrap {
  margin-bottom: 0.75rem;
}

.sales-unit-cell {
  font-size: 0.95rem;
  font-weight: 700;
  color: #3f4f5c;
}

.sales-product-name {
  font-size: 1rem;
  font-weight: 700;
  color: #1f2d3d;
}

.sales-money-cell {
  font-size: 0.95rem;
  font-weight: 700;
  color: #526272;
}

.sales-product-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 0.45rem;
  margin-top: 0.55rem;
}

.sales-meta-tag {
  display: inline-flex;
  align-items: center;
  padding: 0.28rem 0.65rem;
  border-radius: 999px;
  background: #eef4f7;
  color: #5f6f7e;
  font-size: 0.76rem;
  font-weight: 700;
}

.sales-meta-tag.status {
  background: #e4f5ee;
  color: #20725e;
  text-transform: capitalize;
}

.sales-more-info {
  align-items: center;
}

.sales-number-box {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 88px;
  min-height: 52px;
  padding: 0.5rem 0.75rem;
  border: 1px solid #dce4ea;
  border-radius: 14px;
  background: #fbfcfd;
  font-size: 1rem;
  font-weight: 800;
  color: #215544;
}

.sales-qty-input,
.sales-withdraw-form .form-control,
.sales-withdraw-form .form-select {
  min-height: 52px;
  border-radius: 14px;
  border-color: #dce4ea;
  box-shadow: none;
}

.sales-withdraw-form textarea.form-control {
  min-height: 130px;
  resize: vertical;
}

.sales-remove-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 44px;
  min-height: 44px;
  border: 1px solid #f3d2d2;
  border-radius: 12px;
  background: #fff5f5;
  color: #c24141;
}

.sales-action-muted {
  font-size: 0.88rem;
  font-weight: 700;
  color: #97a3af;
}

.sales-total-label,
.sales-total-value {
  font-size: 1rem;
  font-weight: 800;
  color: #1f2d3d;
}

.sales-empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 2.5rem 1rem;
  color: #8b95a1;
}

.sales-empty-state i {
  font-size: 2rem;
}

.sales-side {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.sales-side-card {
  padding: 1.25rem;
}

.sales-side-heading {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  margin-bottom: 1rem;
  color: #1d4ed8;
  font-size: 0.98rem;
  font-weight: 800;
}

.sales-side-heading i {
  font-size: 1.1rem;
}

.sales-info-list {
  display: flex;
  flex-direction: column;
  gap: 0.85rem;
}

.sales-info-empty {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 180px;
  padding: 1.25rem;
  border-radius: 14px;
  background: #f8fafb;
  color: #64748b;
  font-weight: 700;
  text-align: center;
}

.sales-info-empty-inner {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.65rem;
}

.sales-info-empty-inner i {
  font-size: 2rem;
  color: #94a3b8;
}

.sales-info-item {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  padding: 0.85rem 0.95rem;
  border-radius: 14px;
  background: #f8fafb;
}

.sales-info-item span,
.sales-summary-item span {
  color: #7b8794;
  font-size: 0.84rem;
}

.sales-info-item strong,
.sales-summary-item strong {
  color: #263645;
}

.sales-summary-card {
  background: linear-gradient(180deg, #f9fbfc 0%, #f2f7f5 100%);
}

.sales-summary-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem 0;
  border-bottom: 1px solid #e7edf2;
}

.sales-summary-item:last-child {
  border-bottom: 0;
}

.sales-footer {
  display: flex;
  gap: 0.75rem;
}

.sales-cancel-btn,
.sales-submit-btn {
  flex: 1 1 0;
  min-height: 52px;
  border-radius: 14px;
  font-weight: 800;
}

.sales-cancel-btn {
  background: #eef2f6;
  color: #3d4e5f;
  border: 1px solid #dce4ea;
}

.sales-submit-btn {
  border: 0;
  background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
  color: #fff;
}

@media (max-width: 1199.98px) {
  .sales-withdraw-shell {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 767.98px) {
  .sales-meta-grid {
    grid-template-columns: 1fr;
  }

  .sales-banner,
  .sales-footer {
    flex-direction: column;
  }
}
</style>
