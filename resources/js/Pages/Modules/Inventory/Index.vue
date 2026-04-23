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

                    <div class="card-body bg-white rounded-bottom inventory-items-body">
                      <b-row class="mb-3">
                        <b-col lg>
                          <div class="ledger-toolbar-wrap inventory-items-toolbar">
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

                            <select
                              v-model="itemSort"
                              class="form-select inventory-sort-select"
                              aria-label="Sort inventory items"
                            >
                              <option value="latest">Latest Added</option>
                              <option value="oldest">Oldest Added</option>
                              <option value="name_asc">Name A-Z</option>
                              <option value="name_desc">Name Z-A</option>
                              <option value="quantity_desc">Quantity High-Low</option>
                              <option value="quantity_asc">Quantity Low-High</option>
                              <option value="expiration_asc">Expiration Soonest</option>
                              <option value="expiration_desc">Expiration Latest</option>
                              <option value="custom">Custom Order</option>
                            </select>

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
                          <div class="inventory-toolbar-note">
                            <i class="ri-drag-move-2-line me-1"></i>
                            Use the arrows in Custom Order to rearrange the currently loaded items.
                          </div>
                        </b-col>
                      </b-row>

                      <div class="table-responsive inv-table-wrap">
                        <table class="table table-hover align-middle mb-0 inv-table">
                          <thead>
                            <tr>
                              <th class="text-center" style="width: 96px">Order</th>
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
                              <td colspan="9" class="text-center text-muted py-4">Loading items...</td>
                            </tr>
                            <tr v-else-if="sortedItemRows.length === 0">
                              <td colspan="9" class="text-center text-muted py-4">No inventory items found.</td>
                            </tr>
                            <tr v-else v-for="(item, index) in sortedItemRows" :key="item.id">
                              <td class="text-center">
                                <div class="inventory-order-controls">
                                  <button
                                    type="button"
                                    class="btn btn-sm btn-light inventory-order-btn"
                                    :disabled="index === 0"
                                    title="Move up"
                                    v-b-tooltip.hover
                                    @click="moveItemRow(item, -1)"
                                  >
                                    <i class="ri-arrow-up-s-line"></i>
                                  </button>
                                  <span class="inventory-order-number">{{ displayItemNumber(index) }}</span>
                                  <button
                                    type="button"
                                    class="btn btn-sm btn-light inventory-order-btn"
                                    :disabled="index === sortedItemRows.length - 1"
                                    title="Move down"
                                    v-b-tooltip.hover
                                    @click="moveItemRow(item, 1)"
                                  >
                                    <i class="ri-arrow-down-s-line"></i>
                                  </button>
                                </div>
                              </td>
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
                      <Pagination v-if="itemMeta && itemMeta.total" :links="itemLinks" :pagination="itemMeta" :lists="sortedItemRows.length" @fetch="fetchItems" />
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
      :lock-stock="lockItemStock"
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

    <RecordViewModal
      :model-value="showViewModal"
      :type="viewRecordType"
      :record="viewRecord"
      :stock-items="viewStockItems"
      :stock-items-loading="viewStockItemsLoading"
      :can-add-stock-item="canAddStockItemToViewedStock"
      @update:modelValue="handleViewModalVisibility"
      @add-stock-item="openStockItemCreate"
    />
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
      itemSort: 'latest',
      itemSearchTimer: null,
      manualItemOrder: [],
      lockItemStock: false,
      showStockModal: false,
      showItemModal: false,
      showReceivingModal: false,
      showWithdrawalModal: false,
      showViewModal: false,
      viewRecordType: '',
      viewRecord: null,
      viewStockItems: [],
      viewStockItemsLoading: false,
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
          icon: 'ri-stack-line',
        },
        {
          label: 'Tracked Items',
          value: this.formatNumber(this.itemMeta?.total ?? this.itemRows.length),
          icon: 'ri-barcode-box-line',
        },
        {
          label: 'Units On Hand',
          value: this.formatNumber(this.totalTrackedQuantity),
          icon: 'ri-database-2-line',
        },
        {
          label: 'Low Balance',
          value: this.formatNumber(this.lowBalanceItems),
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
    sortedItemRows() {
      const rows = [...this.filteredItemRows];
      const normalizeText = (value) => String(value || '').toLowerCase();
      const normalizeNumber = (value) => Number(value || 0);
      const normalizeDate = (value) => {
        if (!value) return 0;

        const timestamp = new Date(value).getTime();
        return Number.isNaN(timestamp) ? 0 : timestamp;
      };

      if (this.itemSort === 'custom') {
        const orderMap = new Map(this.manualItemOrder.map((id, index) => [Number(id), index]));

        return rows.sort((left, right) => {
          const leftOrder = orderMap.has(Number(left.id)) ? orderMap.get(Number(left.id)) : Number.MAX_SAFE_INTEGER;
          const rightOrder = orderMap.has(Number(right.id)) ? orderMap.get(Number(right.id)) : Number.MAX_SAFE_INTEGER;

          if (leftOrder !== rightOrder) {
            return leftOrder - rightOrder;
          }

          return Number(right.id || 0) - Number(left.id || 0);
        });
      }

      const sorters = {
        oldest: (left, right) => Number(left.id || 0) - Number(right.id || 0),
        name_asc: (left, right) => normalizeText(left.name).localeCompare(normalizeText(right.name)),
        name_desc: (left, right) => normalizeText(right.name).localeCompare(normalizeText(left.name)),
        quantity_desc: (left, right) => normalizeNumber(right.quantity) - normalizeNumber(left.quantity),
        quantity_asc: (left, right) => normalizeNumber(left.quantity) - normalizeNumber(right.quantity),
        expiration_asc: (left, right) => normalizeDate(left.expiration) - normalizeDate(right.expiration),
        expiration_desc: (left, right) => normalizeDate(right.expiration) - normalizeDate(left.expiration),
      };

      return rows.sort(sorters[this.itemSort] || ((left, right) => Number(right.id || 0) - Number(left.id || 0)));
    },
    currentRoles() {
      return Array.isArray(this.$page?.props?.roles) ? this.$page.props.roles : [];
    },
    canManageStockItems() {
      const allowedRoles = ['administrator', 'supply', 'supply officer', 'supply staff'];
      return this.currentRoles.some((role) => allowedRoles.includes(String(role || '').toLowerCase()));
    },
    canAddStockItemToViewedStock() {
      return this.viewRecordType === 'stock' && this.canManageStockItems;
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
    this.hydrateManualItemOrder();
  },
  mounted() {
    if (this.activeModule === 'stocks' && this.stockRows.length === 0) {
      this.fetchStocks();
    }
  },
  watch: {
    itemKeyword() {
      if (this.itemSearchTimer) {
        clearTimeout(this.itemSearchTimer);
      }

      this.itemSearchTimer = setTimeout(() => {
        this.fetchItems();
      }, 300);
    },
    itemSort(value) {
      if (value === 'custom') {
        this.hydrateManualItemOrder();
        return;
      }

      this.fetchItems();
    },
    showItemModal(value) {
      if (!value) {
        this.lockItemStock = false;
      }
    },
  },
  beforeUnmount() {
    if (this.stockSearchTimer) {
      clearTimeout(this.stockSearchTimer);
    }

    if (this.itemSearchTimer) {
      clearTimeout(this.itemSearchTimer);
    }
  },
  methods: {
    assignPaginated(rowsKey, metaKey, linksKey, payload) {
      this[rowsKey] = this.normalizeCollectionRows(payload);
      this[metaKey] = payload?.meta || payload?.data?.meta || null;
      this[linksKey] = payload?.links || payload?.data?.links || null;

      if (rowsKey === 'itemRows') {
        this.hydrateManualItemOrder();
      }
    },
    normalizeCollectionRows(payload) {
      if (Array.isArray(payload)) {
        return payload;
      }

      if (Array.isArray(payload?.data)) {
        return payload.data;
      }

      if (Array.isArray(payload?.data?.data)) {
        return payload.data.data;
      }

      return [];
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
        const response = await axios.get(pageUrl, {
          params: this.collectionParams({
            keyword: this.itemKeyword || undefined,
            sort: this.itemSort === 'custom' ? 'latest' : this.itemSort,
          }),
        });
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
      if (this.itemSearchTimer) {
        clearTimeout(this.itemSearchTimer);
      }

      this.itemKeyword = '';
      this.itemSort = 'latest';
      this.fetchItems();
    },
    openViewModal(type, row) {
      this.viewRecordType = type;
      this.viewRecord = row;
      this.viewStockItems = [];
      this.showViewModal = true;

      if (type === 'stock') {
        this.fetchStockItems(row.id);
      }
    },
    handleViewModalVisibility(value) {
      this.showViewModal = value;

      if (!value) {
        this.viewRecordType = '';
        this.viewRecord = null;
        this.viewStockItems = [];
        this.viewStockItemsLoading = false;
      }
    },
    async fetchStockItems(stockId) {
      const currentStockId = Number(stockId);
      this.viewStockItemsLoading = true;

      try {
        const response = await axios.get('/inventory-items', {
          params: this.collectionParams({
            stock_id: currentStockId,
            count: 100,
            sort: 'name_asc',
          }),
        });

        if (this.viewRecordType === 'stock' && Number(this.viewRecord?.id) === currentStockId) {
          this.viewStockItems = response.data?.data || [];
        }
      } finally {
        if (this.viewRecordType === 'stock' && Number(this.viewRecord?.id) === currentStockId) {
          this.viewStockItemsLoading = false;
        }
      }
    },
    openStockCreate() {
      this.stockForm = { id: null, name: '', entry_date: this.currentInputDateTime() };
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
      this.lockItemStock = false;
      this.itemErrors = {};
      this.showItemModal = true;
    },
    openStockItemCreate(stock) {
      this.handleViewModalVisibility(false);
      this.itemForm = {
        id: null,
        code: '',
        name: '',
        stock_id: String(stock?.id || ''),
        category_id: '',
        quantity: '',
        unit_cost: '',
        expiration: '',
      };
      this.lockItemStock = true;
      this.itemErrors = {};
      this.showItemModal = true;
    },
    openItemEdit(row) {
      this.itemForm = { id: row.id, code: row.code || '', name: row.name || '', stock_id: String(row.stock_id || ''), category_id: String(row.category_id || ''), quantity: row.quantity || '', unit_cost: row.unit_cost || '', expiration: row.expiration || '' };
      this.lockItemStock = false;
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
      const form = {
        ...this.stockForm,
        entry_date: this.stockForm.entry_date || this.currentInputDateTime(),
      };
      const response = await this.submitEntity('/inventory-stocks', form, 'showStockModal', 'stockErrors', this.fetchStocks);
      this.syncOptionRow('stockOptionRows', response?.data?.data);
    },
    async saveItem() {
      const response = await this.submitEntity('/inventory-items', this.itemForm, 'showItemModal', 'itemErrors', this.fetchItems);
      if (!response) return;

      this.syncOptionRow('itemOptionRows', response?.data?.data);
      this.lockItemStock = false;
      await this.fetchStocks();
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
    hydrateManualItemOrder() {
      const savedOrder = this.readStoredItemOrder();
      const currentIds = this.itemRows.map((item) => Number(item.id)).filter(Boolean);
      const missingIds = currentIds.filter((id) => !savedOrder.includes(id));

      this.manualItemOrder = [...savedOrder, ...missingIds];
      this.persistManualItemOrder();
    },
    readStoredItemOrder() {
      if (typeof window === 'undefined' || !window.localStorage) {
        return [];
      }

      try {
        const stored = JSON.parse(window.localStorage.getItem('inventory:item-order') || '[]');

        return Array.isArray(stored)
          ? stored.map((id) => Number(id)).filter(Boolean)
          : [];
      } catch (error) {
        return [];
      }
    },
    persistManualItemOrder() {
      if (typeof window === 'undefined' || !window.localStorage) {
        return;
      }

      window.localStorage.setItem('inventory:item-order', JSON.stringify(this.manualItemOrder));
    },
    moveItemRow(item, direction) {
      const visibleRows = [...this.sortedItemRows];
      const currentIndex = visibleRows.findIndex((row) => Number(row.id) === Number(item.id));
      const targetIndex = currentIndex + direction;

      if (currentIndex < 0 || targetIndex < 0 || targetIndex >= visibleRows.length) {
        return;
      }

      const [movingRow] = visibleRows.splice(currentIndex, 1);
      visibleRows.splice(targetIndex, 0, movingRow);

      const visibleIds = visibleRows.map((row) => Number(row.id));
      const currentIds = this.itemRows.map((row) => Number(row.id));
      const remainingStoredIds = this.manualItemOrder.filter((id) => !visibleIds.includes(id));
      const missingIds = currentIds.filter((id) => !visibleIds.includes(id) && !remainingStoredIds.includes(id));

      this.manualItemOrder = [...visibleIds, ...remainingStoredIds, ...missingIds];
      this.itemSort = 'custom';
      this.persistManualItemOrder();
    },
    displayItemNumber(index) {
      return Number(this.itemMeta?.from || 1) + index;
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
    currentInputDateTime() {
      const now = new Date();
      const offset = now.getTimezoneOffset();
      const local = new Date(now.getTime() - offset * 60000);

      return local.toISOString().slice(0, 16);
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
  margin-bottom: 1rem !important;
}

.inventory-hero > .card-body {
  padding: 1.15rem 1.35rem !important;
}

.inventory-hero-grid {
  display: grid;
  grid-template-columns: minmax(280px, 0.8fr) minmax(0, 1.45fr);
  gap: 18px;
  align-items: center;
}

.inventory-hero-copy {
  min-width: 0;
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
  font-size: 14px;
  line-height: 1.45;
  margin-bottom: 14px;
}

.inventory-hero-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.inventory-primary-btn,
.inventory-secondary-btn {
  min-width: 150px;
  border-radius: 12px;
  padding: 9px 15px;
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
  gap: 10px;
}

.inventory-stat-card {
  display: grid;
  grid-template-columns: auto 1fr;
  gap: 11px;
  align-items: center;
  padding: 13px 14px;
  min-height: 92px;
  border-radius: 16px;
  background: rgba(255, 255, 255, 0.11);
  border: 1px solid rgba(255, 255, 255, 0.12);
  backdrop-filter: blur(8px);
}

.inventory-stat-icon {
  width: 40px;
  height: 40px;
  border-radius: 13px;
  background: rgba(255, 255, 255, 0.16);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
}

.inventory-stat-label {
  display: block;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: rgba(255, 255, 255, 0.72);
  margin-bottom: 4px;
}

.inventory-stat-value {
  display: block;
  font-size: 1.1rem;
  font-weight: 800;
  margin-bottom: 2px;
}

.inventory-stat-note {
  color: rgba(255, 255, 255, 0.74);
  font-size: 11px;
  line-height: 1.35;
}

.inv-shell {
  border: 1px solid #dce4f2;
  overflow: hidden;
  border-radius: 28px;
  box-shadow: 0 18px 42px rgba(15, 23, 42, 0.06);
}

.inventory-module-shell {
  padding: 0.65rem;
}

.module-layout {
  display: grid;
  grid-template-columns: 220px minmax(0, 1fr);
  gap: 12px;
  align-items: start;
}

.module-sidebar {
  border: 1px solid #dce4f2;
  border-radius: 18px;
  background: linear-gradient(180deg, #f8fbff, #eef2fb);
  padding: 12px;
  position: sticky;
  top: 10px;
}

.module-sidebar-title {
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: #64748b;
  margin-bottom: 10px;
}

.module-grid {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.module-content {
  min-width: 0;
}

.module-tile {
  border: 1px solid #d7dfef;
  border-radius: 14px;
  background: #fff;
  color: #334155;
  min-height: 52px;
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 12px;
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
  max-height: calc(100vh - 278px);
  overflow: auto;
  border: 1px solid #dbe5f1;
  border-radius: 16px;
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

.inventory-items-body {
  padding: 0.85rem;
}

.inventory-items-toolbar {
  align-items: stretch;
}

.inventory-sort-select {
  flex: 0 0 220px;
  min-width: 220px;
  border-left: 0;
  border-radius: 0;
}

.inventory-toolbar-note {
  color: #64748b;
  font-size: 12px;
  font-weight: 600;
  margin-top: 0.55rem;
}

.inventory-order-controls {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.25rem;
  padding: 0.2rem;
  border: 1px solid #dbe5f1;
  border-radius: 999px;
  background: #f8fbff;
}

.inventory-order-btn {
  width: 26px;
  height: 26px;
  padding: 0;
  border: 0;
  border-radius: 999px;
  color: #334155;
  background: #fff;
}

.inventory-order-btn:not(:disabled):hover {
  color: #fff;
  background: var(--inventory-brand-deep);
}

.inventory-order-number {
  min-width: 26px;
  color: #475569;
  font-size: 12px;
  font-weight: 800;
}

:global([data-bs-theme="dark"]) .inventory-page {
  --inventory-brand: #6d7fc8;
  --inventory-brand-deep: #8ea0f4;
  --inventory-brand-soft: #1e2744;
  background: #0f172a;
  color: #e5e7eb;
}

:global([data-bs-theme="dark"]) .inv-shell,
:global([data-bs-theme="dark"]) .module-sidebar,
:global([data-bs-theme="dark"]) .inv-table-wrap,
:global([data-bs-theme="dark"]) .inventory-page :deep(.ledger-card),
:global([data-bs-theme="dark"]) .inventory-page :deep(.ledger-table-wrap) {
  border-color: #2e3a59 !important;
  background: #151c2f !important;
  box-shadow: none;
}

:global([data-bs-theme="dark"]) .inventory-page :deep(.card),
:global([data-bs-theme="dark"]) .inventory-page :deep(.bg-white),
:global([data-bs-theme="dark"]) .inventory-page :deep(.bg-light-subtle),
:global([data-bs-theme="dark"]) .inventory-page :deep(.table-card),
:global([data-bs-theme="dark"]) .inventory-page :deep(.table-responsive) {
  border-color: #2e3a59 !important;
  background-color: #111827 !important;
  color: #e5e7eb !important;
}

:global([data-bs-theme="dark"]) .module-sidebar {
  background: linear-gradient(180deg, #182035, #111827);
}

:global([data-bs-theme="dark"]) .inventory-hero {
  background:
    radial-gradient(circle at top right, rgba(142, 160, 244, 0.2), transparent 34%),
    linear-gradient(135deg, #17213a 0%, #0f172a 58%, #111827 100%);
  box-shadow: none;
}

:global([data-bs-theme="dark"]) .inventory-stat-card {
  border-color: rgba(142, 160, 244, 0.22);
  background: rgba(17, 24, 39, 0.62);
}

:global([data-bs-theme="dark"]) .inventory-stat-icon {
  background: rgba(142, 160, 244, 0.16);
  color: #dbeafe;
}

:global([data-bs-theme="dark"]) .inventory-primary-btn {
  border-color: #8ea0f4;
  background: #8ea0f4;
  color: #0f172a;
}

:global([data-bs-theme="dark"]) .inventory-secondary-btn {
  border-color: rgba(142, 160, 244, 0.32);
  background: rgba(17, 24, 39, 0.44);
  color: #e5e7eb;
}

:global([data-bs-theme="dark"]) .module-sidebar-title,
:global([data-bs-theme="dark"]) .module-tile-copy small,
:global([data-bs-theme="dark"]) .inventory-toolbar-note,
:global([data-bs-theme="dark"]) .inventory-page :deep(.text-muted) {
  color: #9ca9c7;
}

:global([data-bs-theme="dark"]) .module-tile {
  border-color: #2e3a59;
  background: #111827;
  color: #e5e7eb;
}

:global([data-bs-theme="dark"]) .module-tile.active {
  border-color: #8ea0f4;
  background: linear-gradient(180deg, #202b49, #182035);
  box-shadow: none;
}

:global([data-bs-theme="dark"]) .inventory-page :deep(.card-header),
:global([data-bs-theme="dark"]) .inventory-page :deep(.card-body.bg-white),
:global([data-bs-theme="dark"]) .inventory-page :deep(.stock-card-body),
:global([data-bs-theme="dark"]) .inventory-page :deep(.receiving-card-body),
:global([data-bs-theme="dark"]) .inventory-page :deep(.withdrawal-card-body),
:global([data-bs-theme="dark"]) .inventory-items-body,
:global([data-bs-theme="dark"]) .inventory-page :deep(.card-footer) {
  background: #111827 !important;
  color: #e5e7eb;
}

:global([data-bs-theme="dark"]) .inventory-page :deep(.table),
:global([data-bs-theme="dark"]) .inv-table {
  --vz-table-bg: #111827;
  --vz-table-color: #e5e7eb;
  --vz-table-hover-bg: #182035;
  --vz-table-border-color: #2e3a59;
  color: #e5e7eb !important;
  background-color: #111827 !important;
}

:global([data-bs-theme="dark"]) .inv-table thead th,
:global([data-bs-theme="dark"]) .inventory-page :deep(.table-light th) {
  background: #182035 !important;
  color: #dbeafe !important;
}

:global([data-bs-theme="dark"]) .ledger-refresh-btn,
:global([data-bs-theme="dark"]) .inventory-page :deep(.stock-refresh-trigger),
:global([data-bs-theme="dark"]) .inventory-order-controls {
  border-color: #2e3a59;
  background: #182035;
  color: #dbeafe;
}

:global([data-bs-theme="dark"]) .inventory-page :deep(.input-group-text),
:global([data-bs-theme="dark"]) .inventory-page :deep(.form-control),
:global([data-bs-theme="dark"]) .inventory-page :deep(.form-select) {
  border-color: #2e3a59 !important;
  background-color: #182035 !important;
  color: #e5e7eb !important;
}

:global([data-bs-theme="dark"]) .inventory-page :deep(.form-control::placeholder) {
  color: #8ea0b8 !important;
}

:global([data-bs-theme="dark"]) .inventory-page :deep(.btn-light) {
  border-color: #2e3a59 !important;
  background-color: #182035 !important;
  color: #e5e7eb !important;
}

:global([data-bs-theme="dark"]) .inventory-page :deep(.table-group-divider) {
  border-top-color: #2e3a59 !important;
}

:global([data-bs-theme="dark"]) .inventory-order-btn {
  background: #111827;
  color: #dbeafe;
}

:global([data-bs-theme="dark"]) .inventory-order-number {
  color: #dbeafe;
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

@media (min-width: 1200px) {
  .inventory-hero-stats {
    grid-template-columns: repeat(4, minmax(0, 1fr));
  }

  .inventory-stat-card {
    grid-template-columns: 40px minmax(0, 1fr);
  }
}

@media (min-width: 768px) and (max-width: 1199.98px) {
  .inventory-hero > .card-body {
    padding: 1rem !important;
  }
}

@media (max-width: 768px) {
  .inventory-hero > .card-body {
    padding: 1rem !important;
  }

  .inventory-primary-btn,
  .inventory-secondary-btn {
    width: 100%;
  }

  .ledger-toolbar-wrap {
    overflow-x: auto;
  }

  .inventory-sort-select {
    flex: 0 0 200px;
    min-width: 200px;
  }
}
</style>

<style>
:is([data-bs-theme="dark"], [data-layout-mode="dark"], .dark-mode, .dark) .inventory-page {
  --inventory-night-page: #0f172a;
  --inventory-night-card: #111827;
  --inventory-night-panel: #151c2f;
  --inventory-night-control: #182035;
  --inventory-night-border: #2e3a59;
  --inventory-night-muted: #9ca9c7;
  --inventory-night-text: #e5e7eb;
  --inventory-night-heading: #f8fafc;
  background: var(--inventory-night-page) !important;
  color: var(--inventory-night-text) !important;
}

:is([data-bs-theme="dark"], [data-layout-mode="dark"], .dark-mode, .dark) .inventory-page .inv-shell,
:is([data-bs-theme="dark"], [data-layout-mode="dark"], .dark-mode, .dark) .inventory-page .inventory-module-shell,
:is([data-bs-theme="dark"], [data-layout-mode="dark"], .dark-mode, .dark) .inventory-page .module-sidebar,
:is([data-bs-theme="dark"], [data-layout-mode="dark"], .dark-mode, .dark) .inventory-page .module-content,
:is([data-bs-theme="dark"], [data-layout-mode="dark"], .dark-mode, .dark) .inventory-page .card,
:is([data-bs-theme="dark"], [data-layout-mode="dark"], .dark-mode, .dark) .inventory-page .ledger-card,
:is([data-bs-theme="dark"], [data-layout-mode="dark"], .dark-mode, .dark) .inventory-page .card-header,
:is([data-bs-theme="dark"], [data-layout-mode="dark"], .dark-mode, .dark) .inventory-page .card-body,
:is([data-bs-theme="dark"], [data-layout-mode="dark"], .dark-mode, .dark) .inventory-page .card-footer,
:is([data-bs-theme="dark"], [data-layout-mode="dark"], .dark-mode, .dark) .inventory-page .bg-white,
:is([data-bs-theme="dark"], [data-layout-mode="dark"], .dark-mode, .dark) .inventory-page .bg-light-subtle,
:is([data-bs-theme="dark"], [data-layout-mode="dark"], .dark-mode, .dark) .inventory-page .stock-card-body,
:is([data-bs-theme="dark"], [data-layout-mode="dark"], .dark-mode, .dark) .inventory-page .inventory-items-body,
:is([data-bs-theme="dark"], [data-layout-mode="dark"], .dark-mode, .dark) .inventory-page .receiving-card-body,
:is([data-bs-theme="dark"], [data-layout-mode="dark"], .dark-mode, .dark) .inventory-page .withdrawal-card-body {
  border-color: var(--inventory-night-border) !important;
  background-color: var(--inventory-night-card) !important;
  color: var(--inventory-night-text) !important;
}

:is([data-bs-theme="dark"], [data-layout-mode="dark"], .dark-mode, .dark) .inventory-page .module-sidebar {
  background: linear-gradient(180deg, #182035, #111827) !important;
}

:is([data-bs-theme="dark"], [data-layout-mode="dark"], .dark-mode, .dark) .inventory-page .module-tile {
  border-color: var(--inventory-night-border) !important;
  background: var(--inventory-night-card) !important;
  color: var(--inventory-night-text) !important;
}

:is([data-bs-theme="dark"], [data-layout-mode="dark"], .dark-mode, .dark) .inventory-page .module-tile.active {
  border-color: #8ea0f4 !important;
  background: linear-gradient(180deg, #202b49, #182035) !important;
  box-shadow: none !important;
}

:is([data-bs-theme="dark"], [data-layout-mode="dark"], .dark-mode, .dark) .inventory-page .text-body,
:is([data-bs-theme="dark"], [data-layout-mode="dark"], .dark-mode, .dark) .inventory-page h5,
:is([data-bs-theme="dark"], [data-layout-mode="dark"], .dark-mode, .dark) .inventory-page h6,
:is([data-bs-theme="dark"], [data-layout-mode="dark"], .dark-mode, .dark) .inventory-page td,
:is([data-bs-theme="dark"], [data-layout-mode="dark"], .dark-mode, .dark) .inventory-page th {
  color: var(--inventory-night-heading) !important;
}

:is([data-bs-theme="dark"], [data-layout-mode="dark"], .dark-mode, .dark) .inventory-page .text-muted,
:is([data-bs-theme="dark"], [data-layout-mode="dark"], .dark-mode, .dark) .inventory-page .module-sidebar-title,
:is([data-bs-theme="dark"], [data-layout-mode="dark"], .dark-mode, .dark) .inventory-page .module-tile-copy small,
:is([data-bs-theme="dark"], [data-layout-mode="dark"], .dark-mode, .dark) .inventory-page .inventory-toolbar-note {
  color: var(--inventory-night-muted) !important;
}

:is([data-bs-theme="dark"], [data-layout-mode="dark"], .dark-mode, .dark) .inventory-page .input-group,
:is([data-bs-theme="dark"], [data-layout-mode="dark"], .dark-mode, .dark) .inventory-page .input-group-text,
:is([data-bs-theme="dark"], [data-layout-mode="dark"], .dark-mode, .dark) .inventory-page .form-control,
:is([data-bs-theme="dark"], [data-layout-mode="dark"], .dark-mode, .dark) .inventory-page .form-select,
:is([data-bs-theme="dark"], [data-layout-mode="dark"], .dark-mode, .dark) .inventory-page .ledger-refresh-btn,
:is([data-bs-theme="dark"], [data-layout-mode="dark"], .dark-mode, .dark) .inventory-page .stock-refresh-trigger,
:is([data-bs-theme="dark"], [data-layout-mode="dark"], .dark-mode, .dark) .inventory-page .inventory-order-controls,
:is([data-bs-theme="dark"], [data-layout-mode="dark"], .dark-mode, .dark) .inventory-page .inventory-order-btn {
  border-color: var(--inventory-night-border) !important;
  background-color: var(--inventory-night-control) !important;
  color: var(--inventory-night-text) !important;
}

:is([data-bs-theme="dark"], [data-layout-mode="dark"], .dark-mode, .dark) .inventory-page .form-control::placeholder {
  color: #8ea0b8 !important;
}

:is([data-bs-theme="dark"], [data-layout-mode="dark"], .dark-mode, .dark) .inventory-page .table-responsive,
:is([data-bs-theme="dark"], [data-layout-mode="dark"], .dark-mode, .dark) .inventory-page .table-card,
:is([data-bs-theme="dark"], [data-layout-mode="dark"], .dark-mode, .dark) .inventory-page .ledger-table-wrap,
:is([data-bs-theme="dark"], [data-layout-mode="dark"], .dark-mode, .dark) .inventory-page .inv-table-wrap,
:is([data-bs-theme="dark"], [data-layout-mode="dark"], .dark-mode, .dark) .inventory-page .table,
:is([data-bs-theme="dark"], [data-layout-mode="dark"], .dark-mode, .dark) .inventory-page table,
:is([data-bs-theme="dark"], [data-layout-mode="dark"], .dark-mode, .dark) .inventory-page thead,
:is([data-bs-theme="dark"], [data-layout-mode="dark"], .dark-mode, .dark) .inventory-page tbody,
:is([data-bs-theme="dark"], [data-layout-mode="dark"], .dark-mode, .dark) .inventory-page tfoot {
  --bs-table-bg: var(--inventory-night-card);
  --bs-table-color: var(--inventory-night-text);
  --bs-table-hover-bg: var(--inventory-night-control);
  --bs-table-hover-color: var(--inventory-night-heading);
  --bs-table-border-color: var(--inventory-night-border);
  --vz-table-bg: var(--inventory-night-card);
  --vz-table-color: var(--inventory-night-text);
  --vz-table-hover-bg: var(--inventory-night-control);
  --vz-table-hover-color: var(--inventory-night-heading);
  --vz-table-border-color: var(--inventory-night-border);
  border-color: var(--inventory-night-border) !important;
  background-color: var(--inventory-night-card) !important;
  color: var(--inventory-night-text) !important;
}

:is([data-bs-theme="dark"], [data-layout-mode="dark"], .dark-mode, .dark) .inventory-page .table > :not(caption) > * > * {
  border-color: var(--inventory-night-border) !important;
  background-color: var(--inventory-night-card) !important;
  color: var(--inventory-night-text) !important;
  box-shadow: none !important;
}

:is([data-bs-theme="dark"], [data-layout-mode="dark"], .dark-mode, .dark) .inventory-page .table-light,
:is([data-bs-theme="dark"], [data-layout-mode="dark"], .dark-mode, .dark) .inventory-page .table-light > :not(caption) > * > *,
:is([data-bs-theme="dark"], [data-layout-mode="dark"], .dark-mode, .dark) .inventory-page thead th {
  --bs-table-bg: var(--inventory-night-control);
  --bs-table-color: #dbeafe;
  --vz-table-bg: var(--inventory-night-control);
  --vz-table-color: #dbeafe;
  background-color: var(--inventory-night-control) !important;
  color: #dbeafe !important;
}

:is([data-bs-theme="dark"], [data-layout-mode="dark"], .dark-mode, .dark) .inventory-page .table-hover > tbody > tr:hover > * {
  background-color: #1d2840 !important;
  color: var(--inventory-night-heading) !important;
}

:is([data-bs-theme="dark"], [data-layout-mode="dark"], .dark-mode, .dark) .inventory-page .table-group-divider {
  border-top-color: var(--inventory-night-border) !important;
}

:is([data-bs-theme="dark"], [data-layout-mode="dark"], .dark-mode, .dark) .inventory-page .page-link {
  border-color: var(--inventory-night-border) !important;
  background-color: var(--inventory-night-control) !important;
  color: var(--inventory-night-text) !important;
}

:is([data-bs-theme="dark"], [data-layout-mode="dark"], .dark-mode, .dark) .inventory-page .page-item.active .page-link {
  border-color: #8ea0f4 !important;
  background-color: #8ea0f4 !important;
  color: #0f172a !important;
}

</style>
