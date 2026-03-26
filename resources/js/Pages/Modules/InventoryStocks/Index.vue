<template>
  <div>
    <Head title="Inventory Stocks" />
    <PageHeader title="Inventory Stocks" pageTitle="Inventory" />

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
                    <div class="card-header bg-light-subtle">
                      <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
                        <div class="d-flex">
                          <div class="flex-shrink-0 me-3">
                            <div style="height: 2.5rem; width: 2.5rem">
                              <span class="avatar-title bg-primary-subtle rounded p-2 mt-n1">
                                <i class="ri-barcode-box-line text-primary fs-24"></i>
                              </span>
                            </div>
                          </div>
                          <div class="flex-grow-1">
                            <h5 class="mb-0 fs-14">
                              <span class="text-body">Inventory Items</span>
                            </h5>
                            <p class="text-muted text-truncate-two-lines fs-12 mb-0">
                              Live stock records synced from completed PO receivings.
                            </p>
                          </div>
                        </div>
                      </div>
                    </div>

                    <div class="card-body bg-white rounded-bottom mt-3">
                      <div class="items-stats-grid mb-4">
                        <div class="items-stat-card">
                          <span class="items-stat-label">Listed Items</span>
                          <strong class="items-stat-value">{{ meta?.total || rows.length }}</strong>
                          <small class="items-stat-note">Current inventory records</small>
                        </div>
                        <div class="items-stat-card">
                          <span class="items-stat-label">Available Stocks</span>
                          <strong class="items-stat-value">{{ availableStockCount }}</strong>
                          <small class="items-stat-note">Items with quantity on hand</small>
                        </div>
                        <div class="items-stat-card">
                          <span class="items-stat-label">Total Quantity</span>
                          <strong class="items-stat-value">{{ formatNumber(totalStockQuantity) }}</strong>
                          <small class="items-stat-note">Combined stock on this page</small>
                        </div>
                      </div>

                      <b-row class="mb-2 ms-1 me-1 mb-5">
                        <b-col lg>
                          <div class="items-toolbar-wrap">
                            <div class="input-group items-search-group">
                              <span class="input-group-text">
                                <i class="ri-search-line search-icon"></i>
                              </span>
                              <input
                                v-model="filters.keyword"
                                type="text"
                                placeholder="Search Inventory Items"
                                class="form-control"
                              />
                            </div>

                            <Multiselect
                              v-model="filters.status"
                              class="white items-toolbar-select"
                              :options="statuses"
                              :searchable="true"
                              placeholder="Select Status"
                            />

                            <select v-model="filters.location_id" class="form-select items-location-select">
                              <option value="">All Locations</option>
                              <option v-for="loc in locations" :key="loc.id" :value="String(loc.id)">{{ loc.name }}</option>
                            </select>

                            <button
                              type="button"
                              class="btn items-refresh-btn"
                              title="Refresh"
                              v-b-tooltip.hover
                              @click="refresh"
                            >
                              <i class="bx bx-refresh search-icon"></i>
                            </button>
                          </div>
                        </b-col>
                      </b-row>

                      <div class="table-responsive inv-table-wrap">
                        <table class="table table-hover align-middle mb-0 inv-table">
                          <thead>
                            <tr>
                              <th>Inventory</th>
                              <th>Category</th>
                              <th>Unit</th>
                              <th>Location</th>
                              <th class="text-end">Quantity</th>
                              <th>Status</th>
                              <th>Last Updated</th>
                              <th class="text-center" style="width: 170px">Actions</th>
                            </tr>
                          </thead>
                          <tbody>
                            <tr v-if="loading">
                              <td colspan="8" class="text-center text-muted py-4">Loading stocks...</td>
                            </tr>
                            <tr v-else-if="rows.length === 0">
                              <td colspan="8" class="text-center text-muted py-4">No inventory stocks found.</td>
                            </tr>
                            <tr v-else v-for="row in rows" :key="row.id">
                              <td class="fw-semibold">{{ row.inventory_name }}</td>
                              <td>{{ row.inventory_category }}</td>
                              <td>{{ row.inventory_unit }}</td>
                              <td>{{ row.location_name || '-' }}</td>
                              <td class="text-end fw-semibold">{{ formatNumber(row.quantity) }}</td>
                              <td>
                                <span class="badge rarity-chip" :class="statusBadgeClass(row.status)">{{ prettyStatus(row.status) }}</span>
                              </td>
                              <td>{{ row.last_updated || '-' }}</td>
                              <td class="text-center">
                                <div class="d-inline-flex gap-1">
                                  <button class="btn btn-sm btn-warning" @click="openWithdraw(row)" :disabled="Number(row.quantity || 0) <= 0">
                                    <i class="ri-arrow-up-circle-line"></i>
                                  </button>
                                  <button class="btn btn-sm btn-info" @click="openEdit(row)">
                                    <i class="ri-pencil-line"></i>
                                  </button>
                                  <button class="btn btn-sm btn-danger" @click="remove(row)">
                                    <i class="ri-delete-bin-line"></i>
                                  </button>
                                </div>
                              </td>
                            </tr>
                          </tbody>
                        </table>
                      </div>

                      <Pagination
                        v-if="meta && meta.total"
                        :links="links"
                        :pagination="meta"
                        :lists="rows.length"
                        @fetch="fetch"
                      />
                    </div>
                  </div>
                  <ReceivingLedger
                    v-else-if="activeModule === 'receivings'"
                    :rows="receivingRows"
                    :loading="loading"
                    :meta="receivingMeta"
                    :links="receivingLinks"
                    @fetch="fetchReceivings"
                    @refresh="fetchReceivings"
                    @view="openViewModal('receiving', $event)"
                    @edit="openEditRecordModal('receiving', $event)"
                    @withdraw="openWithdrawFromReceiving"
                  />

                  <WithdrawalLedger
                    v-else-if="activeModule === 'withdrawals'"
                    :rows="withdrawalRows"
                    :loading="loading"
                    :meta="withdrawalMeta"
                    :links="withdrawalLinks"
                    @create="openWithdrawalCreator"
                    @fetch="fetchWithdrawals"
                    @refresh="fetchWithdrawals"
                    @view="openViewModal('withdrawal', $event)"
                  />

                  <div v-else class="module-panel-placeholder">
                    <div class="module-panel-title">{{ activeModuleLabel }}</div>
                    <p class="mb-0 text-muted">
                      This module is now available as a section in Inventory. Next step is wiring its full workflows
                      (forms, logs, and reports) based on your preferred process.
                    </p>
                  </div>


                  <Pagination
                    v-if="activeModule === 'withdrawals' && withdrawalMeta && withdrawalMeta.total"
                    :links="withdrawalLinks"
                    :pagination="withdrawalMeta"
                    :lists="withdrawalRows.length"
                    @fetch="fetchWithdrawals"
                  />
                </section>
              </div>
            </div>
          </BCardBody>
        </BCard>
      </BCol>
    </BRow>

    <b-modal
      v-model="showModal"
      :title="form.id ? 'Edit Inventory Stock' : 'Create Inventory Stock'"
      centered
      no-close-on-backdrop
      hide-footer
    >
      <form @submit.prevent="save">
        <div class="mb-3">
          <label class="form-label">Inventory</label>
          <select v-model="form.inventory_id" class="form-select" :class="{ 'is-invalid': errors.inventory_id }">
            <option value="">Select Inventory</option>
            <option v-if="!form.id" value="__NEW__">+ Create New Item</option>
            <option v-for="item in inventoryOptions" :key="item.id" :value="String(item.id)">
              {{ item.name }} ({{ item.unit }})
            </option>
          </select>
          <div class="invalid-feedback">{{ errors.inventory_id }}</div>
        </div>

        <div v-if="isCreatingNewItem" class="border rounded p-3 mb-3 bg-light-subtle">
          <div class="fw-semibold mb-2">New Item Details</div>
          <div class="mb-2">
            <label class="form-label">Item Name</label>
            <input v-model="form.new_inventory_name" type="text" class="form-control" :class="{ 'is-invalid': errors.new_inventory_name }" />
            <div class="invalid-feedback">{{ errors.new_inventory_name }}</div>
          </div>

          <div class="mb-2">
            <label class="form-label">Description</label>
            <textarea v-model="form.new_inventory_description" class="form-control" rows="2" :class="{ 'is-invalid': errors.new_inventory_description }"></textarea>
            <div class="invalid-feedback">{{ errors.new_inventory_description }}</div>
          </div>

          <div class="row g-2">
            <div class="col-md-4">
              <label class="form-label">Category</label>
              <select v-model="form.new_category_id" class="form-select" :class="{ 'is-invalid': errors.new_category_id }">
                <option value="">Select Category</option>
                <option v-for="cat in categories" :key="cat.value ?? cat.id" :value="String(cat.value ?? cat.id)">{{ cat.name }}</option>
              </select>
              <div class="invalid-feedback">{{ errors.new_category_id }}</div>
            </div>
            <div class="col-md-4">
              <label class="form-label">Unit</label>
              <select v-model="form.new_unit_id" class="form-select" :class="{ 'is-invalid': errors.new_unit_id }">
                <option value="">Select Unit</option>
                <option v-for="unit in units" :key="unit.value ?? unit.id" :value="String(unit.value ?? unit.id)">{{ unit.name }}</option>
              </select>
              <div class="invalid-feedback">{{ errors.new_unit_id }}</div>
            </div>
            <div class="col-md-4">
              <label class="form-label">Min Stock Level</label>
              <input v-model="form.new_min_stock_level" type="number" min="0" step="0.01" class="form-control" :class="{ 'is-invalid': errors.new_min_stock_level }" />
              <div class="invalid-feedback">{{ errors.new_min_stock_level }}</div>
            </div>
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label">Location</label>
          <select v-model="form.location_id" class="form-select" :class="{ 'is-invalid': errors.location_id }">
            <option value="">No Location</option>
            <option v-for="loc in locations" :key="loc.id" :value="String(loc.id)">{{ loc.name }}</option>
          </select>
          <div class="invalid-feedback">{{ errors.location_id }}</div>
        </div>

        <div class="row g-3 mb-3">
          <div class="col-md-6">
            <label class="form-label">Quantity</label>
            <input
              v-model="form.quantity"
              type="number"
              min="0"
              step="0.01"
              class="form-control"
              :class="{ 'is-invalid': errors.quantity }"
            />
            <div class="invalid-feedback">{{ errors.quantity }}</div>
          </div>
          <div class="col-md-6">
            <label class="form-label">Status</label>
            <select v-model="form.status" class="form-select" :class="{ 'is-invalid': errors.status }">
              <option v-for="status in statuses" :key="status" :value="status">{{ prettyStatus(status) }}</option>
            </select>
            <div class="invalid-feedback">{{ errors.status }}</div>
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label">Last Updated</label>
          <input
            v-model="form.last_updated"
            type="datetime-local"
            class="form-control"
            :class="{ 'is-invalid': errors.last_updated }"
          />
          <div class="invalid-feedback">{{ errors.last_updated }}</div>
        </div>

        <div class="d-flex justify-content-end gap-2">
          <button type="button" class="btn btn-light" @click="showModal = false">Cancel</button>
          <button type="submit" class="btn btn-primary" :disabled="saving">
            {{ saving ? 'Saving...' : (form.id ? 'Update' : 'Create') }}
          </button>
        </div>
      </form>    </b-modal>

    <WithdrawModal
      v-model="showWithdrawModal"
      :form="withdrawForm"
      :errors="withdrawErrors"
      :saving="saving"
      :stock-options="withdrawStockOptions"
      :selectable="isCreatingWithdrawalFromTab"
      @update:form="withdrawForm = $event"
      @submit="submitWithdraw"
    />


    <RecordViewModal
      v-model="showViewModal"
      :type="viewRecordType"
      :record="viewRecord"
    />

    <RecordEditModal
      v-model="showEditModal"
      :type="editRecordType"
      :form="editRecord"
      @save="saveEditedRecord"
    />
  </div>
</template>

<script>
import _ from 'lodash';
import axios from 'axios';
import { Head } from '@inertiajs/vue3';
import Multiselect from '@vueform/multiselect';
import PageHeader from '@/Shared/Components/PageHeader.vue';
import Pagination from '@/Shared/Components/Pagination.vue';
import ReceivingLedger from '@/Pages/Modules/InventoryStocks/Tabs/ReceivingLedger.vue';
import RecordViewModal from '@/Pages/Modules/InventoryStocks/Modals/RecordViewModal.vue';
import RecordEditModal from '@/Pages/Modules/InventoryStocks/Modals/RecordEditModal.vue';
import WithdrawModal from '@/Pages/Modules/InventoryStocks/Modals/WithdrawModal.vue';
import WithdrawalLedger from '@/Pages/Modules/InventoryStocks/Tabs/WithdrawalLedger.vue';

export default {
  components: { Head, Multiselect, PageHeader, Pagination, ReceivingLedger, RecordViewModal, RecordEditModal, WithdrawModal, WithdrawalLedger },
  props: {
    dropdowns: { type: Object, default: () => ({}) },
    inventories: { type: [Array, Object], default: () => [] },
    receivings: { type: [Array, Object], default: () => [] },
    withdrawals: { type: [Array, Object], default: () => [] },
  },
  data() {
    const availableModuleKeys = ['items', 'receivings', 'withdrawals', 'reports'];
    const savedModule = typeof window !== 'undefined' ? window.localStorage.getItem('inventory-stocks-active-module') : null;
    return {
      loading: false,
      saving: false,
      rows: [],
      meta: null,
      links: null,
      receivingMeta: null,
      receivingLinks: null,
      withdrawalMeta: null,
      withdrawalLinks: null,
      showModal: false,
      showWithdrawModal: false,
      showViewModal: false,
      viewRecord: null,
      viewRecordType: '',
      showEditModal: false,
      editRecord: null,
      editRecordType: '',
      statuses: ['available', 'low', 'out', 'reserved'],
      activeModule: availableModuleKeys.includes(savedModule) ? savedModule : 'items',
      modules: [
        { key: 'items', label: 'Items', icon: 'ri-barcode-box-line' },
        { key: 'receivings', label: 'Receivings', icon: 'ri-inbox-archive-line' },
        { key: 'withdrawals', label: 'Withdrawals', icon: 'ri-shopping-cart-line' },
        { key: 'reports', label: 'Reports', icon: 'ri-line-chart-line' },
      ],
      filters: {
        keyword: '',
        status: '',
        location_id: '',
      },
      receivingRows: [],
      withdrawalRows: [],
      withdrawStockOptions: [],
      withdrawForm: {
        items: [],
        receiving_id: null,
        requested_by_id: null,
        requested_by: '',
        remarks: '',
        last_updated: '',
      },
      withdrawErrors: {},
      form: {
        id: null,
        inventory_id: '',
        location_id: '',
        quantity: 0,
        status: 'available',
        last_updated: '',
        new_inventory_name: '',
        new_inventory_description: '',
        new_category_id: '',
        new_unit_id: '',
        new_min_stock_level: 0,
      },
      errors: {},
    };
  },
  computed: {
    inventoryOptions() {
      return this.inventories?.data || this.inventories || [];
    },
    locations() {
      return this.dropdowns?.locations?.data || this.dropdowns?.locations || [];
    },
    categories() {
      return this.dropdowns?.categories?.data || this.dropdowns?.categories || [];
    },
    units() {
      return this.dropdowns?.units?.data || this.dropdowns?.units || [];
    },
    isCreatingNewItem() {
      return this.form.inventory_id === '__NEW__';
    },
    activeModuleLabel() {
      const found = this.modules.find((m) => m.key === this.activeModule);
      return found?.label || 'Module';
    },
    isCreatingWithdrawalFromTab() {
      return this.activeModule === 'withdrawals';
    },
    availableStockCount() {
      return this.rows.filter((row) => Number(row.quantity || 0) > 0).length;
    },
    totalStockQuantity() {
      return this.rows.reduce((sum, row) => sum + Number(row.quantity || 0), 0);
    },
  },
  watch: {
    'filters.keyword': _.debounce(function () {
      this.fetch();
    }, 300),
    'filters.status'() {
      this.fetch();
    },
    'filters.location_id'() {
      this.fetch();
    },
    activeModule(value) {
      if (typeof window !== 'undefined') {
        window.localStorage.setItem('inventory-stocks-active-module', value);
      }

      if (value === 'items') {
        this.fetch();
      }
      if (value === 'receivings') {
        this.fetchReceivings();
      }
      if (value === 'withdrawals') {
        this.fetchWithdrawals();
      }
    },
  },
  created() {
    this.receivingRows = this.receivings?.data || this.receivings || [];
    this.receivingMeta = this.receivings?.meta || null;
    this.receivingLinks = this.receivings?.links || null;
    this.withdrawalRows = this.withdrawals?.data || this.withdrawals || [];
    this.withdrawalMeta = this.withdrawals?.meta || null;
    this.withdrawalLinks = this.withdrawals?.links || null;
    this.fetch();
  },
  methods: {
    fetch(pageUrl = '/inventory-stocks') {
      this.loading = true;
      axios
        .get(pageUrl, {
          params: {
            option: 'lists',
            count: 10,
            keyword: this.filters.keyword || null,
            status: this.filters.status || null,
            location_id: this.filters.location_id || null,
          },
        })
        .then((response) => {
          this.rows = response.data?.data || [];
          this.meta = response.data?.meta || null;
          this.links = response.data?.links || null;
        })
        .catch(() => {
          this.rows = [];
          this.meta = null;
          this.links = null;
        })
        .finally(() => {
          this.loading = false;
        });
    },
    refresh() {
      this.filters.keyword = '';
      this.filters.status = '';
      this.filters.location_id = '';
      this.fetch();
    },
    fetchReceivings(pageUrl = '/inventory-stocks') {
      this.loading = true;
      axios
        .get(pageUrl, {
          params: {
            option: 'receivings',
            count: 10,
          },
        })
        .then((response) => {
          this.receivingRows = response.data?.data || [];
          this.receivingMeta = response.data?.meta || null;
          this.receivingLinks = response.data?.links || null;
        })
        .catch(() => {
          this.receivingRows = [];
          this.receivingMeta = null;
          this.receivingLinks = null;
        })
        .finally(() => {
          this.loading = false;
        });
    },
    fetchWithdrawals(pageUrl = '/inventory-stocks') {
      this.loading = true;
      axios
        .get(pageUrl, {
          params: {
            option: 'withdrawals',
            count: 10,
          },
        })
        .then((response) => {
          this.withdrawalRows = response.data?.data || [];
          this.withdrawalMeta = response.data?.meta || null;
          this.withdrawalLinks = response.data?.links || null;
        })
        .catch(() => {
          this.withdrawalRows = [];
          this.withdrawalMeta = null;
          this.withdrawalLinks = null;
        })
        .finally(() => {
          this.loading = false;
        });
    },
    openCreate() {
      this.form = {
        id: null,
        inventory_id: '',
        location_id: '',
        quantity: 0,
        status: 'available',
        last_updated: '',
        new_inventory_name: '',
        new_inventory_description: '',
        new_category_id: '',
        new_unit_id: '',
        new_min_stock_level: 0,
      };
      this.errors = {};
      this.showModal = true;
    },
    openEdit(row) {
      this.form = {
        id: row.id,
        inventory_id: String(row.inventory_id ?? ''),
        location_id: row.location_id ? String(row.location_id) : '',
        quantity: row.quantity ?? 0,
        status: row.status || 'available',
        last_updated: this.toInputDateTime(row.last_updated),
        new_inventory_name: '',
        new_inventory_description: '',
        new_category_id: '',
        new_unit_id: '',
        new_min_stock_level: 0,
      };
      this.errors = {};
      this.showModal = true;
    },
    openWithdraw(row) {
      this.withdrawStockOptions = [row];
      this.withdrawForm = {
        items: [this.createWithdrawItem(row)],
        receiving_id: null,
        requested_by_id: null,
        requested_by: '',
        remarks: '',
        last_updated: this.toInputDateTime(row.last_updated) || this.toInputDateTime(new Date().toISOString().slice(0, 19).replace('T', ' ')),
      };
      this.withdrawErrors = {};
      this.showWithdrawModal = true;
    },
    openWithdrawalCreator() {
      this.withdrawStockOptions = [...this.rows];
      this.withdrawForm = {
        items: [],
        requested_by_id: null,
        requested_by: '',
        remarks: '',
        last_updated: this.toInputDateTime(new Date().toISOString().slice(0, 19).replace('T', ' ')),
      };
      this.withdrawErrors = {};
      this.showWithdrawModal = true;
    },
    async transferReceivingToInventory(receiving) {
      if (!receiving?.id) return;

      if (!confirm(`Transfer completed PO receiving "${receiving.po_number}" to inventory stock?`)) return;

      this.loading = true;

      try {
        const response = await axios.post('/inventory-stocks/transfer-receiving', {
          receiving_id: receiving.id,
        });

        const message = response.data?.info || response.data?.message || 'Receiving transferred successfully.';

        if (this.$store?.dispatch) {
          const action = response.data?.status === 'warning' ? 'notification/error' : 'notification/success';
          this.$store.dispatch(action, message);
        } else {
          alert(message);
        }

        this.fetch();
        this.fetchReceivings();
      } catch (error) {
        const message = error?.response?.data?.message || 'Failed to transfer receiving to inventory.';

        if (this.$store?.dispatch) {
          this.$store.dispatch('notification/error', message);
        } else {
          alert(message);
        }
      } finally {
        this.loading = false;
      }
    },

    openWithdrawFromReceiving(receiving) {
      if (!receiving?.id) return;

      this.loading = true;
      this.withdrawErrors = {};

      axios
        .get('/inventory-stocks', {
          params: {
            option: 'receiving-withdrawal-items',
            receiving_id: receiving.id,
          },
        })
        .then((response) => {
          const stocks = response.data?.data || [];

          this.withdrawStockOptions = stocks;
          this.withdrawForm = {
            items: [],
            receiving_id: receiving.id,
            requested_by_id: null,
            requested_by: '',
            remarks: receiving.remarks || '',
            last_updated: this.toInputDateTime(new Date().toISOString().slice(0, 19).replace('T', ' ')),
          };

          if (!stocks.length) {
            this.withdrawErrors = {
              items: [{ inventory_id: 'No available stock items were found for this receiving.' }],
            };
          }

          this.showWithdrawModal = true;
        })
        .catch(() => {
          this.withdrawErrors = {
            items: [{ inventory_id: 'No available stock items were found for this receiving.' }],
          };
          this.showWithdrawModal = true;
        })
        .finally(() => {
          this.loading = false;
        });
    },
    createWithdrawItem(row = null) {
      return {
        uid: `withdraw-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`,
        id: row?.id ?? null,
        inventory_id: row?.inventory_id ? String(row.inventory_id) : '',
        location_id: row?.location_id ? String(row.location_id) : '',
        inventory_name: row?.inventory_name || '',
        available_quantity: Number(row?.quantity || 0),
        quantity: null,
        current_status: row?.status || 'available',
      };
    },
    save() {
      this.saving = true;
      this.errors = {};

      const payload = {
        inventory_id: this.form.inventory_id || null,
        location_id: this.form.location_id || null,
        quantity: this.form.quantity,
        status: this.form.status,
        last_updated: this.form.last_updated ? this.form.last_updated.replace('T', ' ') + ':00' : null,
      };

      if (!this.form.id && this.isCreatingNewItem) {
        payload.new_inventory_name = this.form.new_inventory_name || null;
        payload.new_inventory_description = this.form.new_inventory_description || null;
        payload.new_category_id = this.form.new_category_id || null;
        payload.new_unit_id = this.form.new_unit_id || null;
        payload.new_min_stock_level = this.form.new_min_stock_level || 0;
      }

      const request = this.form.id
        ? axios.put(`/inventory-stocks/${this.form.id}`, payload)
        : axios.post('/inventory-stocks', payload);

      request
        .then(() => {
          this.showModal = false;
          this.fetch();
          this.fetchWithdrawals();
        })
        .catch((error) => {
          if (error?.response?.status === 422) {
            this.errors = error.response.data.errors || {};
          }
        })
        .finally(() => {
          this.saving = false;
        });
    },
    submitWithdraw() {
      this.withdrawErrors = {};

      const items = Array.isArray(this.withdrawForm.items) ? this.withdrawForm.items : [];

      if (!items.length) {
        this.withdrawErrors = { items: [{ inventory_id: 'Select an item to withdraw.' }] };
        return;
      }

      const itemErrors = [];

      items.forEach((item, index) => {
        const errors = {};
        const requested = Number(item.quantity || 0);
        const available = Number(item.available_quantity || 0);

        if (!item.id) {
          errors.inventory_id = 'Select an item to withdraw.';
        }

        if (!requested || requested <= 0) {
          errors.quantity = 'Enter a valid withdrawal quantity.';
        } else if (requested > available) {
          errors.quantity = 'Withdrawal quantity cannot exceed available stock.';
        }

        if (Object.keys(errors).length) {
          itemErrors[index] = errors;
        }
      });

      if (itemErrors.length) {
        this.withdrawErrors = { items: itemErrors };
        return;
      }

      this.saving = true;
      Promise.all(items.map((item) => {
        const requested = Number(item.quantity || 0);
        const available = Number(item.available_quantity || 0);
        const remainingQuantity = Math.max(available - requested, 0);
        const nextStatus = remainingQuantity === 0 ? 'out' : (item.current_status || 'available');

        return axios.put(`/inventory-stocks/${item.id}`, {
          inventory_id: item.inventory_id || null,
          location_id: item.location_id || null,
          quantity: remainingQuantity,
          status: nextStatus,
          last_updated: this.withdrawForm.last_updated
            ? this.withdrawForm.last_updated.replace('T', ' ') + ':00'
            : null,
          withdrawal_remarks: this.withdrawForm.remarks || null,
          requested_by_id: this.withdrawForm.requested_by_id || null,
        });
      }))
        .then(() => {
          this.showWithdrawModal = false;
          this.fetch();
          this.fetchWithdrawals();
        })
        .catch((error) => {
          if (error?.response?.status === 422) {
            this.withdrawErrors = error.response.data.errors || {};
          }
        })
        .finally(() => {
          this.saving = false;
        });
    },
    openViewModal(type, row) {
      this.viewRecordType = type;
      this.viewRecord = row;
      this.showViewModal = true;
    },
    openEditRecordModal(type, row) {
      this.editRecordType = type;
      this.editRecord = { ...row };
      this.showEditModal = true;
    },
    saveEditedRecord(payload) {
      const { type, data } = payload || {};
      if (!data?.id) return;

      if (type === 'receiving') {
        this.receivingRows = this.receivingRows.map((item) => (item.id === data.id ? { ...item, ...data } : item));
      } else if (type === 'withdrawal') {
        this.withdrawalRows = this.withdrawalRows.map((item) => (item.id === data.id ? { ...item, ...data } : item));
      }

      this.showEditModal = false;
      this.editRecord = null;
      this.editRecordType = '';
    },
    remove(row) {
      if (!confirm(`Delete stock record for "${row.inventory_name}"?`)) return;

      axios.delete(`/inventory-stocks/${row.id}`).then(() => {
        this.fetch();
      });
    },
    statusBadgeClass(status) {
      const classes = {
        available: 'bg-success-subtle text-success border border-success-subtle',
        low: 'bg-warning-subtle text-warning border border-warning-subtle',
        out: 'bg-danger-subtle text-danger border border-danger-subtle',
        reserved: 'bg-info-subtle text-info border border-info-subtle',
      };
      return classes[status] || 'bg-secondary-subtle text-secondary border border-secondary-subtle';
    },
    prettyStatus(status) {
      if (!status) return '-';
      return status.charAt(0).toUpperCase() + status.slice(1);
    },
    formatNumber(value) {
      return new Intl.NumberFormat().format(Number(value || 0));
    },
    toInputDateTime(value) {
      if (!value) return '';
      const normalized = value.replace(' ', 'T');
      return normalized.slice(0, 16);
    },
  },
};
</script>

<style scoped>
.inv-shell {
  border: 1px solid #dce4f2;
  overflow: hidden;
}

.inv-header {
  background:
    radial-gradient(1200px 300px at 0% 0%, rgba(87, 130, 255, 0.35), transparent),
    radial-gradient(900px 260px at 100% 0%, rgba(34, 197, 94, 0.25), transparent),
    linear-gradient(135deg, #1f2a44, #24365c);
}

.inv-eyebrow {
  color: #bfdbfe;
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.08em;
}

.inv-stat-chip {
  border: 1px solid rgba(255, 255, 255, 0.2);
  color: #e2e8f0;
  background: rgba(15, 23, 42, 0.35);
  border-radius: 999px;
  padding: 6px 12px;
  font-size: 12px;
  font-weight: 600;
}

.inv-search .input-group-text {
  background: #f1f5f9;
  border-color: #dbe4f0;
}

.inv-search .form-control {
  border-color: #dbe4f0;
}

.inv-create-btn {
  background: linear-gradient(135deg, #2563eb, #1d4ed8);
  border: 0;
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
  justify-content: flex-start;
  gap: 12px;
  padding: 14px 16px;
  font-size: 14px;
  font-weight: 700;
  text-align: left;
  transition: all 0.2s ease;
}

.module-tile i {
  font-size: 22px;
  color: #64748b;
}

.module-tile.active {
  border-color: #3b82f6;
  background: linear-gradient(180deg, #eef4ff, #dce9ff);
  box-shadow: 0 10px 24px rgba(59, 130, 246, 0.18);
}

.module-tile.active i,
.module-tile.active span {
  color: #1d4ed8;
}

.module-tile:hover {
  transform: translateY(-1px);
  border-color: #b9c9ea;
}

.inv-table-wrap {
  overflow: hidden;
  border: 1px solid #dbe5f1;
  border-radius: 20px;
  background: #fff;
  box-shadow: 0 16px 32px rgba(15, 23, 42, 0.06);
}

.inv-table thead th {
  background: linear-gradient(180deg, #f8fbff 0%, #eef4ff 100%);
  color: #24415f;
  font-weight: 800;
  border-bottom: 1px solid #dbe5f1;
  padding: 1rem 0.9rem;
}

.inv-table tbody td {
  padding: 1rem 0.9rem;
  border-color: #edf2f7;
  color: #334155;
}

.rarity-chip {
  border-radius: 999px;
  padding: 6px 10px;
  font-size: 11px;
  letter-spacing: 0.02em;
}

.items-stats-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 14px;
}

.items-stat-card {
  position: relative;
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
  min-width: 0;
  overflow: hidden;
  border: 1px solid #dbe8ff;
  border-radius: 20px;
  background: linear-gradient(135deg, #ffffff 0%, #eef4ff 100%);
  padding: 1.1rem 1.2rem;
  box-shadow: 0 14px 28px rgba(59, 130, 246, 0.08);
}

.items-stat-card::after {
  content: '';
  position: absolute;
  right: -18px;
  top: -18px;
  width: 86px;
  height: 86px;
  border-radius: 999px;
  background: radial-gradient(circle, rgba(59, 130, 246, 0.12) 0%, rgba(59, 130, 246, 0) 72%);
}

.items-stat-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
}

.items-stat-label {
  display: block;
  font-size: 0.74rem;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: #64748b;
}

.items-stat-icon {
  position: relative;
  z-index: 1;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 38px;
  height: 38px;
  border-radius: 12px;
  background: rgba(37, 99, 235, 0.1);
  color: #2563eb;
  font-size: 1.1rem;
}

.items-stat-value {
  position: relative;
  z-index: 1;
  display: block;
  font-size: 2rem;
  line-height: 1.05;
  font-weight: 800;
  color: #1d4ed8;
}

.items-stat-note {
  display: block;
  color: #64748b;
  font-size: 0.84rem;
  line-height: 1.35;
}

.items-toolbar-wrap {
  display: flex;
  align-items: stretch;
  flex-wrap: nowrap;
  gap: 0;
  width: 100%;
}

.items-search-group {
  flex: 1 1 auto;
  min-width: 320px;
}

.items-search-group .input-group-text {
  background: #f8fbff;
  border-color: #d7dfef;
}

.items-search-group .form-control {
  border-color: #d7dfef;
}

.items-toolbar-select {
  flex: 0 0 230px;
  min-width: 230px;
  margin-left: 0.45rem;
}

.items-toolbar-select :deep(.multiselect) {
  min-height: 52px;
  border: 1px solid #d7dfef;
  border-radius: 14px;
  background: #fff;
}

.items-toolbar-select :deep(.multiselect-wrapper),
.items-toolbar-select :deep(.multiselect-search),
.items-toolbar-select :deep(.multiselect-single-label) {
  min-height: 49px;
}

.items-location-select {
  flex: 0 0 230px;
  min-width: 230px;
  min-height: 52px;
  margin-left: 0.45rem;
  border-color: #d7dfef;
  border-radius: 14px;
  background: #fff;
}

.items-refresh-btn {
  flex: 0 0 52px;
  min-width: 52px;
  min-height: 52px;
  margin-left: 0.45rem;
  border: 1px solid #d7dfef;
  border-radius: 14px;
  background: #fff;
  color: #334155;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.items-refresh-btn:hover,
.items-refresh-btn:focus {
  background: #eef4ff;
  color: #1d4ed8;
}

.module-panel-placeholder {
  border: 1px dashed #cbd5e1;
  border-radius: 12px;
  background: #f8fafc;
  padding: 22px;
}

.module-panel-title {
  font-size: 16px;
  font-weight: 700;
  color: #334155;
  margin-bottom: 8px;
}

@media (max-width: 991.98px) {
  .module-layout {
    grid-template-columns: 1fr;
  }

  .module-sidebar {
    position: static;
  }

  .module-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
  }

  .module-tile {
    justify-content: center;
    text-align: center;
  }

  .items-stats-grid {
    grid-template-columns: 1fr;
  }

  .items-toolbar-wrap {
    overflow-x: auto;
  }
}

</style>
















































