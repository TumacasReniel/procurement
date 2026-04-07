<template>
  <div>
    <Head title="Inventory Management" />
    <PageHeader title="Inventory Management" pageTitle="Inventory" />

    <BRow>
      <BCol lg="12">
        <BCard no-body class="inv-shell">
          <BCardBody class="p-0">
            <div class="p-3 p-md-4">
              <div class="module-layout">
                <aside class="module-sidebar">
                  <div class="module-sidebar-title">Inventory Modules</div>
                  <div class="module-grid">
                    <button
                      v-for="module in modules"
                      :key="module.key"
                      type="button"
                      class="module-tile"
                      :class="{ active: activeModule === module.key }"
                      @click="activeModule = module.key"
                    >
                      <i :class="module.icon"></i>
                      <span>{{ module.label }}</span>
                    </button>
                  </div>
                </aside>

                <section class="module-content">
                  <div v-if="activeModule === 'items'" class="card bg-light-subtle shadow-none border ledger-card">
                    <div class="card-header bg-light-subtle d-flex justify-content-between align-items-center">
                      <div>
                        <h5 class="mb-0 fs-14"><span class="text-body">Inventory Items</span></h5>
                        <p class="text-muted fs-12 mb-0">Manage inventory items based on the new item migration.</p>
                      </div>
                      <button type="button" class="btn btn-primary" @click="openItemCreate">Create Item</button>
                    </div>

                    <div class="card-body bg-white rounded-bottom mt-3">
                      <div class="table-responsive inv-table-wrap">
                        <table class="table table-hover align-middle mb-0 inv-table">
                          <thead>
                            <tr>
                              <th>Code</th>
                              <th>Name</th>
                              <th>Stock</th>
                              <th>Category</th>
                              <th class="text-end">Quantity</th>
                              <th class="text-end">Unit Cost</th>
                              <th>Expiration</th>
                              <th class="text-center" style="width: 170px">Actions</th>
                            </tr>
                          </thead>
                          <tbody>
                            <tr v-if="loading">
                              <td colspan="8" class="text-center text-muted py-4">Loading items...</td>
                            </tr>
                            <tr v-else-if="itemRows.length === 0">
                              <td colspan="8" class="text-center text-muted py-4">No inventory items found.</td>
                            </tr>
                            <tr v-else v-for="item in itemRows" :key="item.id">
                              <td class="fw-semibold">{{ item.code }}</td>
                              <td>{{ item.name }}</td>
                              <td>{{ item.stock_name }}</td>
                              <td>{{ item.category }}</td>
                              <td class="text-end">{{ formatNumber(item.quantity) }}</td>
                              <td class="text-end">{{ formatNumber(item.unit_cost) }}</td>
                              <td>{{ item.expiration }}</td>
                              <td class="text-center">
                                <div class="d-inline-flex gap-1">
                                  <button class="btn btn-sm btn-outline-primary" @click="openViewModal('item', item)"><i class="ri-eye-line"></i></button>
                                  <button class="btn btn-sm btn-outline-warning" @click="openItemEdit(item)"><i class="ri-pencil-line"></i></button>
                                  <button class="btn btn-sm btn-outline-danger" @click="removeItem(item)"><i class="ri-delete-bin-line"></i></button>
                                </div>
                              </td>
                            </tr>
                          </tbody>
                        </table>
                      </div>
                      <Pagination v-if="itemMeta && itemMeta.total" :links="itemLinks" :pagination="itemMeta" :lists="itemRows.length" @fetch="fetchItems" />
                    </div>
                  </div>

                  <Stocks
                    v-else-if="activeModule === 'stocks'"
                    :rows="stockRows"
                    :loading="loading"
                    @create="openStockCreate"
                    @refresh="() => fetchStocks()"
                    @view="(row) => openViewModal('stock', row)"
                    @edit="openStockEdit"
                    @delete="removeStock"
                  />

                  <ReceivingLedger
                    v-else-if="activeModule === 'receivings'"
                    :rows="receivingRows"
                    :loading="loading"
                    :meta="receivingMeta"
                    :links="receivingLinks"
                    @create="openReceivingCreate"
                    @fetch="fetchReceivings"
                    @refresh="() => fetchReceivings()"
                    @view="(row) => openViewModal('receiving', row)"
                    @edit="openReceivingEdit"
                    @delete="removeReceiving"
                  />

                  <WithdrawalLedger
                    v-else-if="activeModule === 'withdrawals'"
                    :rows="withdrawalRows"
                    :loading="loading"
                    :meta="withdrawalMeta"
                    :links="withdrawalLinks"
                    @create="openWithdrawalCreate"
                    @fetch="fetchWithdrawals"
                    @refresh="() => fetchWithdrawals()"
                    @view="(row) => openViewModal('withdrawal', row)"
                    @edit="openWithdrawalEdit"
                    @delete="removeWithdrawal"
                  />
                </section>
              </div>
            </div>
          </BCardBody>
        </BCard>
      </BCol>
    </BRow>

    <StockModal
      v-model="showStockModal"
      :form="stockForm"
      :errors="stockErrors"
      :saving="saving"
      :inventories="inventoryOptions"
      @update:form="stockForm = $event"
      @submit="saveStock"
    />

    <ItemModal
      v-model="showItemModal"
      :form="itemForm"
      :errors="itemErrors"
      :saving="saving"
      :stocks="stockRows"
      :categories="categories"
      @update:form="itemForm = $event"
      @submit="saveItem"
    />

    <ReceivingModal
      v-model="showReceivingModal"
      :form="receivingForm"
      :errors="receivingErrors"
      :saving="saving"
      :items="itemRows"
      :users="userOptions"
      :statuses="statusOptions"
      @update:form="receivingForm = $event"
      @submit="saveReceiving"
    />

    <WithdrawModal
      v-model="showWithdrawalModal"
      :form="withdrawalForm"
      :errors="withdrawalErrors"
      :saving="saving"
      :items="itemRows"
      :users="userOptions"
      :statuses="statusOptions"
      @update:form="withdrawalForm = $event"
      @submit="saveWithdrawal"
    />

    <RecordViewModal v-model="showViewModal" :type="viewRecordType" :record="viewRecord" />
  </div>
</template>

<script>
import axios from 'axios';
import { Head } from '@inertiajs/vue3';
import PageHeader from '@/Shared/Components/PageHeader.vue';
import Pagination from '@/Shared/Components/Pagination.vue';
import StockModal from '@/Pages/Modules/Inventory/Modals/Stock.vue';
import ItemModal from '@/Pages/Modules/Inventory/Modals/Item.vue';
import ReceivingModal from '@/Pages/Modules/Inventory/Modals/Receiving.vue';
import WithdrawModal from '@/Pages/Modules/Inventory/Modals/Withdraw.vue';
import RecordViewModal from '@/Pages/Modules/Inventory/Modals/RecordViewModal.vue';
import Stocks from '@/Pages/Modules/Inventory/Tabs/Stocks.vue';
import ReceivingLedger from '@/Pages/Modules/Inventory/Tabs/Receiving.vue';
import WithdrawalLedger from '@/Pages/Modules/Inventory/Tabs/Withdrawal.vue';

export default {
  components: { Head, PageHeader, Pagination, StockModal, ItemModal, ReceivingModal, WithdrawModal, RecordViewModal, Stocks, ReceivingLedger, WithdrawalLedger },
  props: {
    dropdowns: { type: Object, default: () => ({}) },
    users: { type: Array, default: () => [] },
    inventories: { type: [Array, Object], default: () => [] },
    stocks: { type: [Array, Object], default: () => [] },
    items: { type: [Array, Object], default: () => [] },
    receivings: { type: [Array, Object], default: () => [] },
    withdrawals: { type: [Array, Object], default: () => [] },
  },
  data() {
    return {
      loading: false,
      saving: false,
      activeModule: 'items',
      modules: [
        { key: 'items', label: 'Items', icon: 'ri-barcode-box-line' },
        { key: 'stocks', label: 'Stocks', icon: 'ri-shopping-cart-2-line' },
        { key: 'receivings', label: 'Receivings', icon: 'ri-inbox-archive-line' },
        { key: 'withdrawals', label: 'Withdrawals', icon: 'ri-shopping-cart-line' },
      ],
      stockRows: [],
      stockMeta: null,
      stockLinks: null,
      itemRows: [],
      itemMeta: null,
      itemLinks: null,
      receivingRows: [],
      receivingMeta: null,
      receivingLinks: null,
      withdrawalRows: [],
      withdrawalMeta: null,
      withdrawalLinks: null,
      showStockModal: false,
      showItemModal: false,
      showReceivingModal: false,
      showWithdrawalModal: false,
      showViewModal: false,
      viewRecordType: '',
      viewRecord: null,
      stockForm: { id: null, code: '', name: '', inventory_id: '', entry_date: '' },
      itemForm: { id: null, code: '', name: '', stock_id: '', category_id: '', quantity: '', unit_cost: '', expiration: '' },
      receivingForm: { id: null, item_id: '', approved_by_id: '', status_id: '', received_at: '', remarks: '' },
      withdrawalForm: { id: null, item_id: '', requested_by_id: '', approved_by_id: '', status_id: '', released_at: '', remarks: '' },
      stockErrors: {},
      itemErrors: {},
      receivingErrors: {},
      withdrawalErrors: {},
    };
  },
  computed: {
    inventoryOptions() {
      return this.inventories?.data || this.inventories || [];
    },
    categories() {
      return this.dropdowns?.categories?.data || this.dropdowns?.categories || [];
    },
    userOptions() {
      return this.users || [];
    },
    statusOptions() {
      return this.dropdowns?.statuses?.data || this.dropdowns?.statuses || [];
    },
  },
  created() {
    this.assignPaginated('stockRows', 'stockMeta', 'stockLinks', this.stocks);
    this.assignPaginated('itemRows', 'itemMeta', 'itemLinks', this.items);
    this.assignPaginated('receivingRows', 'receivingMeta', 'receivingLinks', this.receivings);
    this.assignPaginated('withdrawalRows', 'withdrawalMeta', 'withdrawalLinks', this.withdrawals);
  },
  methods: {
    assignPaginated(rowsKey, metaKey, linksKey, payload) {
      this[rowsKey] = payload?.data || payload || [];
      this[metaKey] = payload?.meta || null;
      this[linksKey] = payload?.links || null;
    },
    async fetchStocks(pageUrl = '/inventory-stocks') {
      this.loading = true;
      try {
        const response = await axios.get(pageUrl, { params: { json: 1, count: 10 } });
        this.assignPaginated('stockRows', 'stockMeta', 'stockLinks', response.data);
      } finally {
        this.loading = false;
      }
    },
    async fetchItems(pageUrl = '/inventory-items') {
      this.loading = true;
      try {
        const response = await axios.get(pageUrl, { params: { count: 10 } });
        this.assignPaginated('itemRows', 'itemMeta', 'itemLinks', response.data);
      } finally {
        this.loading = false;
      }
    },
    async fetchReceivings(pageUrl = '/inventory-receivings') {
      this.loading = true;
      try {
        const response = await axios.get(pageUrl, { params: { count: 10 } });
        this.assignPaginated('receivingRows', 'receivingMeta', 'receivingLinks', response.data);
      } finally {
        this.loading = false;
      }
    },
    async fetchWithdrawals(pageUrl = '/inventory-withdrawals') {
      this.loading = true;
      try {
        const response = await axios.get(pageUrl, { params: { count: 10 } });
        this.assignPaginated('withdrawalRows', 'withdrawalMeta', 'withdrawalLinks', response.data);
      } finally {
        this.loading = false;
      }
    },
    openViewModal(type, row) {
      this.viewRecordType = type;
      this.viewRecord = row;
      this.showViewModal = true;
    },
    openStockCreate() {
      this.stockForm = { id: null, code: '', name: '', inventory_id: '', entry_date: '' };
      this.stockErrors = {};
      this.showStockModal = true;
    },
    openStockEdit(row) {
      this.stockForm = { id: row.id, code: row.code || '', name: row.name || '', inventory_id: String(row.inventory_id || ''), entry_date: this.toInputDateTime(row.entry_date) };
      this.stockErrors = {};
      this.showStockModal = true;
    },
    openItemCreate() {
      this.itemForm = { id: null, code: '', name: '', stock_id: '', category_id: '', quantity: '', unit_cost: '', expiration: '' };
      this.itemErrors = {};
      this.showItemModal = true;
    },
    openItemEdit(row) {
      this.itemForm = { id: row.id, code: row.code || '', name: row.name || '', stock_id: String(row.stock_id || ''), category_id: String(row.category_id || ''), quantity: row.quantity || '', unit_cost: row.unit_cost || '', expiration: row.expiration || '' };
      this.itemErrors = {};
      this.showItemModal = true;
    },
    openReceivingCreate() {
      this.receivingForm = { id: null, item_id: '', approved_by_id: '', status_id: '', received_at: '', remarks: '' };
      this.receivingErrors = {};
      this.showReceivingModal = true;
    },
    openReceivingEdit(row) {
      this.receivingForm = { id: row.id, item_id: String(row.item_id || ''), approved_by_id: String(row.approved_by_id || ''), status_id: String(row.status_id || ''), received_at: this.toInputDateTime(row.received_at), remarks: row.remarks || '' };
      this.receivingErrors = {};
      this.showReceivingModal = true;
    },
    openWithdrawalCreate() {
      this.withdrawalForm = { id: null, item_id: '', requested_by_id: '', approved_by_id: '', status_id: '', released_at: '', remarks: '' };
      this.withdrawalErrors = {};
      this.showWithdrawalModal = true;
    },
    openWithdrawalEdit(row) {
      this.withdrawalForm = { id: row.id, item_id: String(row.item_id || ''), requested_by_id: String(row.requested_by_id || ''), approved_by_id: String(row.approved_by_id || ''), status_id: String(row.status_id || ''), released_at: this.toInputDateTime(row.released_at), remarks: row.remarks || '' };
      this.withdrawalErrors = {};
      this.showWithdrawalModal = true;
    },
    async saveStock() {
      await this.submitEntity('/inventory-stocks', this.stockForm, 'showStockModal', 'stockErrors', this.fetchStocks);
    },
    async saveItem() {
      await this.submitEntity('/inventory-items', this.itemForm, 'showItemModal', 'itemErrors', this.fetchItems);
    },
    async saveReceiving() {
      await this.submitEntity('/inventory-receivings', this.receivingForm, 'showReceivingModal', 'receivingErrors', this.fetchReceivings);
    },
    async saveWithdrawal() {
      await this.submitEntity('/inventory-withdrawals', this.withdrawalForm, 'showWithdrawalModal', 'withdrawalErrors', this.fetchWithdrawals);
    },
    async submitEntity(baseUrl, form, modalKey, errorKey, refreshFn) {
      this.saving = true;
      this[errorKey] = {};
      const payload = { ...form };
      if (payload.entry_date) payload.entry_date = payload.entry_date.replace('T', ' ') + ':00';
      if (payload.received_at) payload.received_at = payload.received_at.replace('T', ' ') + ':00';
      if (payload.released_at) payload.released_at = payload.released_at.replace('T', ' ') + ':00';
      try {
        if (form.id) {
          await axios.put(`${baseUrl}/${form.id}`, payload);
        } else {
          await axios.post(baseUrl, payload);
        }
        this[modalKey] = false;
        await refreshFn.call(this);
      } catch (error) {
        if (error?.response?.status === 422) {
          this[errorKey] = error.response.data.errors || {};
        }
      } finally {
        this.saving = false;
      }
    },
    async removeStock(row) {
      if (!confirm(`Delete stock "${row.name}"?`)) return;
      await axios.delete(`/inventory-stocks/${row.id}`);
      this.fetchStocks();
    },
    async removeItem(row) {
      if (!confirm(`Delete item "${row.name}"?`)) return;
      await axios.delete(`/inventory-items/${row.id}`);
      this.fetchItems();
    },
    async removeReceiving(row) {
      if (!confirm(`Delete receiving for "${row.item_name}"?`)) return;
      await axios.delete(`/inventory-receivings/${row.id}`);
      this.fetchReceivings();
    },
    async removeWithdrawal(row) {
      if (!confirm(`Delete withdrawal for "${row.item_name}"?`)) return;
      await axios.delete(`/inventory-withdrawals/${row.id}`);
      this.fetchWithdrawals();
    },
    formatNumber(value) {
      return new Intl.NumberFormat().format(Number(value || 0));
    },
    toInputDateTime(value) {
      if (!value) return '';
      return String(value).replace(' ', 'T').slice(0, 16);
    },
  },
};
</script>

<style scoped>
.inv-shell {
  border: 1px solid #dce4f2;
  overflow: hidden;
}

.module-layout {
  display: grid;
  grid-template-columns: 240px minmax(0, 1fr);
  gap: 20px;
  align-items: start;
}

.module-sidebar {
  border: 1px solid #dce4f2;
  border-radius: 18px;
  background: linear-gradient(180deg, #f8fbff, #eef4fb);
  padding: 16px;
  position: sticky;
  top: 16px;
}

.module-sidebar-title {
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: #64748b;
  margin-bottom: 14px;
}

.module-grid {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.module-content {
  min-width: 0;
}

.module-tile {
  border: 1px solid #d7dfef;
  border-radius: 14px;
  background: #fff;
  color: #334155;
  min-height: 64px;
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px 16px;
  font-size: 14px;
  font-weight: 700;
  text-align: left;
}

.module-tile.active {
  border-color: #3b82f6;
  background: linear-gradient(180deg, #eef4ff, #dce9ff);
}

.inv-table-wrap {
  overflow: hidden;
  border: 1px solid #dbe5f1;
  border-radius: 20px;
  background: #fff;
}

.inv-table thead th {
  background: linear-gradient(180deg, #f8fbff 0%, #eef4ff 100%);
  color: #24415f;
  font-weight: 800;
}

@media (max-width: 991.98px) {
  .module-layout {
    grid-template-columns: 1fr;
  }

  .module-sidebar {
    position: static;
  }
}
</style>
