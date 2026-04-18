<template>
  <div class="inventory-page">
    <Head title="Inventory Management" />
    <PageHeader title="Inventory Management" pageTitle="Inventory" />

    <section class="inventory-hero card border-0 mb-4">
      <div class="card-body p-4 p-xl-5">
        <div class="inventory-hero-grid">
          <div class="inventory-hero-copy">
            <p class="inventory-hero-text">
              Manage stock groups, catalog inventory items, log receivings, and
              monitor withdrawals from one clean workspace.
            </p>

            <div class="inventory-hero-actions">
              <button type="button" class="btn inventory-primary-btn" @click="openActiveCreate">
                <i class="ri-add-circle-fill me-2"></i>{{ currentCreateLabel }}
              </button>
              <Link href="/inventory-dashboard" class="btn inventory-secondary-btn">
                <i class="ri-bar-chart-box-line me-2"></i>View Dashboard
              </Link>
            </div>
          </div>

          <div class="inventory-hero-stats">
            <article
              v-for="card in inventoryHeroCards"
              :key="card.label"
              class="inventory-stat-card"
            >
              <div class="inventory-stat-icon">
                <i :class="card.icon"></i>
              </div>
              <div>
                <span class="inventory-stat-label">{{ card.label }}</span>
                <strong class="inventory-stat-value">{{ card.value }}</strong>
                <p class="inventory-stat-note mb-0">{{ card.note }}</p>
              </div>
            </article>
          </div>
        </div>
      </div>
    </section>

    <BRow>
      <BCol lg="12">
        <BCard no-body class="inv-shell">
          <BCardBody class="p-0">
            <div class="inventory-module-shell">
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
                      <span class="module-tile-copy">
                        <span>{{ module.label }}</span>
                        <small>{{ moduleMeta(module.key) }}</small>
                      </span>
                    </button>
                  </div>
                </aside>

                <section class="module-content">
                  <div v-if="activeModule === 'items'" class="card bg-light-subtle shadow-none border ledger-card">
                    <div class="card-header bg-light-subtle">
                      <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
                        <div class="d-flex">
                          <div class="flex-shrink-0 me-3">
                            <div style="height: 2.5rem; width: 2.5rem">
                              <span class="avatar-title bg-success-subtle rounded p-2 mt-n1">
                                <i class="ri-barcode-box-line text-success fs-24"></i>
                              </span>
                            </div>
                          </div>
                          <div class="flex-grow-1">
                            <h5 class="mb-0 fs-14"><span class="text-body">Inventory Items</span></h5>
                            <p class="text-muted fs-12 mb-0">Manage item quantities, categories, unit cost, and expiry details.</p>
                          </div>
                        </div>
                      </div>
                    </div>

                    <div class="card-body bg-white rounded-bottom mt-3">
                      <b-row class="mb-2 ms-1 me-1 mb-5">
                        <b-col lg>
                          <div class="ledger-toolbar-wrap">
                            <div class="ledger-toolbar">
                              <div class="input-group ledger-search-group">
                                <span class="input-group-text">
                                  <i class="ri-search-line search-icon"></i>
                                </span>
                                <input
                                  v-model="itemKeyword"
                                  type="text"
                                  placeholder="Search Items"
                                  class="form-control"
                                />
                              </div>
                            </div>

                            <button
                              type="button"
                              class="btn ledger-refresh-btn"
                              title="Refresh"
                              v-b-tooltip.hover
                              @click="handleItemRefresh"
                            >
                              <i class="bx bx-refresh search-icon"></i>
                            </button>
                            <button type="button" class="btn withdrawal-create-btn" @click="openItemCreate">
                              <i class="ri-add-circle-fill me-2"></i>Create
                            </button>
                          </div>
                        </b-col>
                      </b-row>

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
                            <tr v-else-if="filteredItemRows.length === 0">
                              <td colspan="8" class="text-center text-muted py-4">No inventory items found.</td>
                            </tr>
                            <tr v-else v-for="item in filteredItemRows" :key="item.id">
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
                      <Pagination v-if="itemMeta && itemMeta.total" :links="itemLinks" :pagination="itemMeta" :lists="filteredItemRows.length" @fetch="fetchItems" />
                    </div>
                  </div>

                  <Stocks
                    v-else-if="activeModule === 'stocks'"
                    :rows="stockRows"
                    :loading="loading"
                    :meta="stockMeta"
                    :links="stockLinks"
                    :keyword="stockKeyword"
                    @create="openStockCreate"
                    @fetch="fetchStocks"
                    @refresh="refreshStocks"
                    @update:keyword="handleStockKeywordChange"
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
      @update:form="stockForm = $event"
      @submit="saveStock"
    />

    <ItemModal
      v-model="showItemModal"
      :form="itemForm"
      :errors="itemErrors"
      :saving="saving"
      :stocks="stockOptionRows"
      :categories="categories"
      @update:form="itemForm = $event"
      @submit="saveItem"
    />

    <ReceivingModal
      v-model="showReceivingModal"
      :form="receivingForm"
      :errors="receivingErrors"
      :saving="saving"
      :items="itemOptionRows"
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
      :items="itemOptionRows"
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
import { Head, Link } from '@inertiajs/vue3';
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
  components: { Head, Link, PageHeader, Pagination, StockModal, ItemModal, ReceivingModal, WithdrawModal, RecordViewModal, Stocks, ReceivingLedger, WithdrawalLedger },
  props: {
    initialTab: { type: String, default: 'stocks' },
    dropdowns: { type: Object, default: () => ({}) },
    users: { type: Array, default: () => [] },
    stockOptions: { type: Array, default: () => [] },
    itemOptions: { type: Array, default: () => [] },
    stocks: { type: [Array, Object], default: () => [] },
    items: { type: [Array, Object], default: () => [] },
    receivings: { type: [Array, Object], default: () => [] },
    withdrawals: { type: [Array, Object], default: () => [] },
  },
  data() {
    return {
      loading: false,
      saving: false,
      activeModule: 'stocks',
      modules: [
        { key: 'stocks', label: 'Stocks', icon: 'ri-shopping-cart-2-line' },
        { key: 'items', label: 'Items', icon: 'ri-barcode-box-line' },
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
      stockKeyword: '',
      stockSearchTimer: null,
      stockOptionRows: [],
      itemOptionRows: [],
      showStockModal: false,
      showItemModal: false,
      showReceivingModal: false,
      showWithdrawalModal: false,
      showViewModal: false,
      viewRecordType: '',
      viewRecord: null,
      itemKeyword: '',
      stockForm: { id: null, code: '', name: '', entry_date: '' },
      itemForm: { id: null, code: '', name: '', stock_id: '', category_id: '', quantity: '', unit_cost: '', expiration: '' },
      receivingForm: { id: null, item_id: '', approved_by_id: '', status_id: '', received_at: '', remarks: '' },
      withdrawalForm: { id: null, inventory_id: '', requested_by_id: '', approved_by_id: '', status_id: '', released_at: '', remarks: '' },
      stockErrors: {},
      itemErrors: {},
      receivingErrors: {},
      withdrawalErrors: {},
    };
  },
  computed: {
    categories() {
      return this.dropdowns?.categories?.data || this.dropdowns?.categories || [];
    },
    userOptions() {
      return this.users || [];
    },
    statusOptions() {
      return this.dropdowns?.statuses?.data || this.dropdowns?.statuses || [];
    },
    currentCreateLabel() {
      return {
        stocks: 'Add Stock',
        items: 'Add Item',
        receivings: 'Log Receiving',
        withdrawals: 'Log Withdrawal',
      }[this.activeModule] || 'Create';
    },
    totalTrackedQuantity() {
      return this.itemRows.reduce((total, item) => total + Number(item.quantity || 0), 0);
    },
    lowBalanceItems() {
      return this.itemRows.filter((item) => {
        const quantity = Number(item.quantity || 0);
        return quantity > 0 && quantity <= 5;
      }).length;
    },
    inventoryHeroCards() {
      return [
        {
          label: 'Stock Groups',
          value: this.formatNumber(this.stockMeta?.total ?? this.stockRows.length),
          note: 'Shelf headers and storage buckets',
          icon: 'ri-stack-line',
        },
        {
          label: 'Tracked Items',
          value: this.formatNumber(this.itemMeta?.total ?? this.itemRows.length),
          note: 'Cataloged records ready for movement',
          icon: 'ri-barcode-box-line',
        },
        {
          label: 'Units On Hand',
          value: this.formatNumber(this.totalTrackedQuantity),
          note: 'Summed quantity from the current inventory list',
          icon: 'ri-database-2-line',
        },
        {
          label: 'Low Balance',
          value: this.formatNumber(this.lowBalanceItems),
          note: 'Items already nearing replenishment level',
          icon: 'ri-alarm-warning-line',
        },
      ];
    },
    filteredItemRows() {
      const keyword = (this.itemKeyword || '').toLowerCase();

      return this.itemRows.filter((item) => {
        const searchable = [
          item.code,
          item.name,
          item.stock_name,
          item.category,
          item.quantity,
          item.unit_cost,
          item.expiration,
        ]
          .filter(Boolean)
          .join(' ')
          .toLowerCase();

        return searchable.includes(keyword);
      });
    },
  },
  created() {
    this.activeModule = ['stocks', 'items', 'receivings', 'withdrawals'].includes(this.initialTab)
      ? this.initialTab
      : 'stocks';

    this.assignPaginated('stockRows', 'stockMeta', 'stockLinks', this.stocks);
    this.assignPaginated('itemRows', 'itemMeta', 'itemLinks', this.items);
    this.assignPaginated('receivingRows', 'receivingMeta', 'receivingLinks', this.receivings);
    this.assignPaginated('withdrawalRows', 'withdrawalMeta', 'withdrawalLinks', this.withdrawals);
    this.stockOptionRows = [...this.stockOptions];
    this.itemOptionRows = [...this.itemOptions];
  },
  beforeUnmount() {
    if (this.stockSearchTimer) {
      clearTimeout(this.stockSearchTimer);
    }
  },
  methods: {
    assignPaginated(rowsKey, metaKey, linksKey, payload) {
      this[rowsKey] = payload?.data || payload || [];
      this[metaKey] = payload?.meta || null;
      this[linksKey] = payload?.links || null;
    },
    collectionParams(extra = {}) {
      return {
        json: 1,
        count: 10,
        ...extra,
      };
    },
    async fetchStocks(pageUrl = '/inventory-stocks') {
      this.loading = true;
      try {
        const response = await axios.get(pageUrl, {
          params: this.collectionParams({
            keyword: this.stockKeyword || undefined,
          }),
        });
        this.assignPaginated('stockRows', 'stockMeta', 'stockLinks', response.data);
      } finally {
        this.loading = false;
      }
    },
    async fetchItems(pageUrl = '/inventory-items') {
      this.loading = true;
      try {
        const response = await axios.get(pageUrl, { params: this.collectionParams() });
        this.assignPaginated('itemRows', 'itemMeta', 'itemLinks', response.data);
      } finally {
        this.loading = false;
      }
    },
    async fetchReceivings(pageUrl = '/inventory-receivings') {
      this.loading = true;
      try {
        const response = await axios.get(pageUrl, { params: this.collectionParams() });
        this.assignPaginated('receivingRows', 'receivingMeta', 'receivingLinks', response.data);
      } finally {
        this.loading = false;
      }
    },
    async fetchWithdrawals(pageUrl = '/inventory-withdrawals') {
      this.loading = true;
      try {
        const response = await axios.get(pageUrl, { params: this.collectionParams() });
        this.assignPaginated('withdrawalRows', 'withdrawalMeta', 'withdrawalLinks', response.data);
      } finally {
        this.loading = false;
      }
    },
    openActiveCreate() {
      if (this.activeModule === 'items') {
        this.openItemCreate();
        return;
      }

      if (this.activeModule === 'receivings') {
        this.openReceivingCreate();
        return;
      }

      if (this.activeModule === 'withdrawals') {
        this.openWithdrawalCreate();
        return;
      }

      this.openStockCreate();
    },
    refreshStocks() {
      this.stockKeyword = '';
      this.fetchStocks();
    },
    handleStockKeywordChange(value) {
      this.stockKeyword = value;

      if (this.stockSearchTimer) {
        clearTimeout(this.stockSearchTimer);
      }

      this.stockSearchTimer = setTimeout(() => {
        this.fetchStocks();
      }, 300);
    },
    handleItemRefresh() {
      this.itemKeyword = '';
      this.fetchItems();
    },
    openViewModal(type, row) {
      this.viewRecordType = type;
      this.viewRecord = row;
      this.showViewModal = true;
    },
    openStockCreate() {
      this.stockForm = { id: null, code: '', name: '', entry_date: '' };
      this.stockErrors = {};
      this.showStockModal = true;
    },
    openStockEdit(row) {
      this.stockForm = { id: row.id, code: row.code || '', name: row.name || '', entry_date: this.toInputDateTime(row.entry_date) };
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
      this.withdrawalForm = { id: null, inventory_id: '', requested_by_id: '', approved_by_id: '', status_id: '', released_at: '', remarks: '' };
      this.withdrawalErrors = {};
      this.showWithdrawalModal = true;
    },
    openWithdrawalEdit(row) {
      this.withdrawalForm = { id: row.id, inventory_id: String(row.inventory_id || row.item_id || ''), requested_by_id: String(row.requested_by_id || ''), approved_by_id: String(row.approved_by_id || ''), status_id: String(row.status_id || ''), released_at: this.toInputDateTime(row.released_at), remarks: row.remarks || '' };
      this.withdrawalErrors = {};
      this.showWithdrawalModal = true;
    },
    async saveStock() {
      const response = await this.submitEntity('/inventory-stocks', this.stockForm, 'showStockModal', 'stockErrors', this.fetchStocks);
      this.syncOptionRow('stockOptionRows', response?.data?.data);
    },
    async saveItem() {
      const response = await this.submitEntity('/inventory-items', this.itemForm, 'showItemModal', 'itemErrors', this.fetchItems);
      this.syncOptionRow('itemOptionRows', response?.data?.data);
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
        let response;

        if (form.id) {
          response = await axios.put(`${baseUrl}/${form.id}`, payload);
        } else {
          response = await axios.post(baseUrl, payload);
        }

        this[modalKey] = false;
        await refreshFn.call(this);

        return response;
      } catch (error) {
        if (error?.response?.status === 422) {
          this[errorKey] = error.response.data.errors || {};
        }

        return null;
      } finally {
        this.saving = false;
      }
    },
    syncOptionRow(listKey, record) {
      if (!record?.id) return;

      const next = [
        ...this[listKey].filter((item) => Number(item.id) !== Number(record.id)),
        {
          id: record.id,
          code: record.code || '',
          name: record.name || record.item_name || '',
        },
      ].sort((a, b) => String(a.name || '').localeCompare(String(b.name || '')));

      this[listKey] = next;
    },
    removeOptionRow(listKey, id) {
      this[listKey] = this[listKey].filter((item) => Number(item.id) !== Number(id));
    },
    async removeStock(row) {
      if (!confirm(`Delete stock "${row.name}"?`)) return;
      await axios.delete(`/inventory-stocks/${row.id}`);
      this.removeOptionRow('stockOptionRows', row.id);
      this.fetchStocks();
    },
    async removeItem(row) {
      if (!confirm(`Delete item "${row.name}"?`)) return;
      await axios.delete(`/inventory-items/${row.id}`);
      this.removeOptionRow('itemOptionRows', row.id);
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
    moduleMeta(moduleKey) {
      if (moduleKey === 'stocks') {
        return `${this.formatNumber(this.stockMeta?.total ?? this.stockRows.length)} records`;
      }

      if (moduleKey === 'items') {
        return `${this.formatNumber(this.itemMeta?.total ?? this.itemRows.length)} items`;
      }

      if (moduleKey === 'receivings') {
        return `${this.formatNumber(this.receivingMeta?.total ?? this.receivingRows.length)} logs`;
      }

      return `${this.formatNumber(this.withdrawalMeta?.total ?? this.withdrawalRows.length)} logs`;
    },
  },
};
</script>

<style scoped>
.inventory-page {
  --inventory-brand: #4b5b93;
  --inventory-brand-deep: #38467a;
  --inventory-brand-soft: #edf1fb;
}

.inventory-hero {
  background:
    radial-gradient(circle at top right, rgba(255, 255, 255, 0.18), transparent 34%),
    linear-gradient(135deg, var(--inventory-brand) 0%, var(--inventory-brand-deep) 100%);
  color: #fff;
  overflow: hidden;
  box-shadow: 0 26px 48px rgba(56, 70, 122, 0.2);
}

.inventory-hero-grid {
  display: grid;
  grid-template-columns: minmax(0, 1.2fr) minmax(0, 1fr);
  gap: 24px;
  align-items: center;
}

.inventory-hero-kicker {
  display: inline-flex;
  align-items: center;
  padding: 8px 12px;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.12);
  font-size: 11px;
  font-weight: 800;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  margin-bottom: 16px;
}

.inventory-hero-title {
  font-size: clamp(1.9rem, 2.4vw, 2.7rem);
  line-height: 1.08;
  font-weight: 800;
  margin-bottom: 12px;
}

.inventory-hero-text {
  max-width: 620px;
  color: rgba(255, 255, 255, 0.82);
  font-size: 15px;
  margin-bottom: 20px;
}

.inventory-hero-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
}

.inventory-primary-btn,
.inventory-secondary-btn {
  min-width: 170px;
  border-radius: 14px;
  padding: 11px 18px;
  font-weight: 700;
}

.inventory-primary-btn {
  background: #fff;
  border: 1px solid #fff;
  color: var(--inventory-brand-deep);
}

.inventory-primary-btn:hover,
.inventory-primary-btn:focus {
  color: var(--inventory-brand-deep);
  background: #f8f9ff;
  border-color: #f8f9ff;
}

.inventory-secondary-btn {
  border: 1px solid rgba(255, 255, 255, 0.28);
  color: #fff;
  background: rgba(255, 255, 255, 0.08);
}

.inventory-secondary-btn:hover,
.inventory-secondary-btn:focus {
  color: #fff;
  background: rgba(255, 255, 255, 0.14);
}

.inventory-hero-stats {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 14px;
}

.inventory-stat-card {
  display: grid;
  grid-template-columns: auto 1fr;
  gap: 14px;
  align-items: start;
  padding: 18px;
  border-radius: 20px;
  background: rgba(255, 255, 255, 0.11);
  border: 1px solid rgba(255, 255, 255, 0.12);
  backdrop-filter: blur(8px);
}

.inventory-stat-icon {
  width: 48px;
  height: 48px;
  border-radius: 16px;
  background: rgba(255, 255, 255, 0.16);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
}

.inventory-stat-label {
  display: block;
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: rgba(255, 255, 255, 0.72);
  margin-bottom: 6px;
}

.inventory-stat-value {
  display: block;
  font-size: 1.2rem;
  font-weight: 800;
  margin-bottom: 4px;
}

.inventory-stat-note {
  color: rgba(255, 255, 255, 0.74);
  font-size: 12px;
}

.inv-shell {
  border: 1px solid #dce4f2;
  overflow: hidden;
  border-radius: 28px;
  box-shadow: 0 18px 42px rgba(15, 23, 42, 0.06);
}

.inventory-module-shell {
  padding: 0.85rem;
}

.module-layout {
  display: grid;
  grid-template-columns: 240px minmax(0, 1fr);
  gap: 16px;
  align-items: start;
}

.module-sidebar {
  border: 1px solid #dce4f2;
  border-radius: 24px;
  background: linear-gradient(180deg, #f8fbff, #eef2fb);
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
  border-radius: 18px;
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
  transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
}

.module-tile:hover {
  transform: translateY(-2px);
  box-shadow: 0 12px 28px rgba(15, 23, 42, 0.08);
}

.module-tile.active {
  border-color: var(--inventory-brand);
  background: linear-gradient(180deg, #edf1fb, #dfe7fb);
  box-shadow: 0 16px 30px rgba(75, 91, 147, 0.14);
}

.module-tile-copy {
  display: flex;
  flex-direction: column;
  gap: 3px;
}

.module-tile-copy small {
  color: #64748b;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.02em;
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

.ledger-toolbar-wrap {
  display: flex;
  align-items: stretch;
  flex-wrap: nowrap;
  gap: 0;
  width: 100%;
}

.ledger-toolbar {
  flex: 1 1 auto;
  min-width: 0;
}

.ledger-search-group {
  height: 100%;
}

.ledger-refresh-btn {
  flex: 0 0 52px;
  min-width: 52px;
  border: 1px solid #d7dfef;
  border-left: 0;
  border-radius: 0;
  background: #f8fbff;
  color: #334155;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.withdrawal-create-btn {
  flex: 0 0 176px;
  min-width: 176px;
  border-radius: 0 4px 4px 0;
  background: var(--inventory-brand-deep);
  border: 1px solid var(--inventory-brand-deep);
  color: #fff;
  font-weight: 600;
  white-space: nowrap;
}

@media (max-width: 991.98px) {
  .inventory-hero-grid,
  .inventory-hero-stats {
    grid-template-columns: 1fr;
  }

  .module-layout {
    grid-template-columns: 1fr;
  }

  .module-sidebar {
    position: static;
  }
}

@media (min-width: 768px) {
  .inventory-module-shell {
    padding: 0.95rem;
  }
}

@media (max-width: 768px) {
  .inventory-primary-btn,
  .inventory-secondary-btn {
    width: 100%;
  }

  .ledger-toolbar-wrap {
    overflow-x: auto;
  }
}
</style>
