<template>
  <div>
    <Head title="Inventory Stocks" />
    <PageHeader title="Inventory Stocks" pageTitle="Inventory" />

    <BRow>
      <BCol lg="12">
        <BCard no-body class="inv-shell">
          <BCardBody class="p-0">
            <div class="inv-header p-3 p-md-4">
              <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                  <div class="inv-eyebrow">Storage Console</div>
                  <h5 class="mb-1 text-white">Inventory Stocks Arsenal</h5>
                  <small class="text-white-50">Manage item stacks, status, and locations in one view.</small>
                </div>
                <div class="d-flex flex-wrap gap-2">
                  <span class="inv-stat-chip">Records: {{ meta?.total || rows.length }}</span>
                  <span class="inv-stat-chip">Items: {{ inventoryOptions.length }}</span>
                </div>
              </div>
            </div>

            <div class="p-3 p-md-4">
              <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
                <div class="input-group inv-search" style="max-width: 360px">
                  <span class="input-group-text"><i class="ri-search-line"></i></span>
                  <input
                    v-model="filters.keyword"
                    type="text"
                    class="form-control"
                    placeholder="Search inventory or location"
                  />
                </div>

                <select v-model="filters.status" class="form-select" style="max-width: 180px">
                  <option value="">All Status</option>
                  <option v-for="status in statuses" :key="status" :value="status">{{ prettyStatus(status) }}</option>
                </select>

                <select v-model="filters.location_id" class="form-select" style="max-width: 220px">
                  <option value="">All Locations</option>
                  <option v-for="loc in locations" :key="loc.id" :value="String(loc.id)">{{ loc.name }}</option>
                </select>

                <button class="btn btn-light" @click="refresh">
                  <i class="ri-refresh-line me-1"></i>Refresh
                </button>

                <button
                  v-if="activeModule === 'items'"
                  class="btn btn-primary ms-auto inv-create-btn"
                  @click="openCreate"
                  
                >
                  <i class="ri-add-line me-1"></i>Create Stock
                </button>
              </div>

              <div class="module-grid mb-3">
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

              <div v-if="activeModule === 'items'" class="table-responsive inv-table-wrap">
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
                      <th class="text-center" style="width: 120px">Actions</th>
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

              <div v-else-if="activeModule === 'receivings'" class="receiving-panel">
                <div class="receiving-panel-head">
                  <div>
                    <div class="receiving-eyebrow">Receiving Ledger</div>
                    <div class="receiving-title">Purchase Order Receivings</div>
                  </div>
                  <span class="receiving-count">Displaying {{ filteredReceivings.length }} results</span>
                </div>

                <div class="table-responsive receiving-table-wrap">
                  <table class="table align-middle mb-0 receiving-table">
                    <thead>
                      <tr>
                        <th>P.O. Number</th>
                        <th>Supplier Name</th>
                        <th>Remarks</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th class="text-center" style="width: 100px">Action</th>
                      </tr>
                      <tr class="receiving-filter-row">
                        <th>
                          <input v-model="receivingFilters.po_number" class="form-control form-control-sm" placeholder="Filter PO" />
                        </th>
                        <th>
                          <input v-model="receivingFilters.supplier_name" class="form-control form-control-sm" placeholder="Filter supplier" />
                        </th>
                        <th>
                          <input v-model="receivingFilters.remarks" class="form-control form-control-sm" placeholder="Filter remarks" />
                        </th>
                        <th>
                          <input v-model="receivingFilters.received_at" class="form-control form-control-sm" placeholder="Filter date" />
                        </th>
                        <th></th>
                        <th></th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-if="filteredReceivings.length === 0">
                        <td colspan="6" class="text-center text-muted py-4">No receiving records found.</td>
                      </tr>
                      <tr v-for="item in filteredReceivings" :key="item.id">
                        <td class="fw-semibold">{{ item.po_number }}</td>
                        <td>{{ item.supplier_name }}</td>
                        <td>{{ item.remarks }}</td>
                        <td>{{ item.received_at }}</td>
                        <td>
                          <span class="badge receiving-status-chip">
                            <i class="ri-checkbox-circle-fill me-1"></i>{{ item.status }}
                          </span>
                        </td>
                        <td class="text-center">
                          <div class="d-inline-flex gap-1">
                            <button class="btn btn-sm btn-outline-success receiving-action-btn" type="button" title="View" @click="openViewModal('receiving', item)">
                              <i class="ri-eye-line"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-primary receiving-action-btn" type="button" title="Edit" @click="openEditRecordModal('receiving', item)">
                              <i class="ri-pencil-line"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger receiving-action-btn" type="button" title="Delete">
                              <i class="ri-close-line"></i>
                            </button>
                          </div>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>

              <div v-else-if="activeModule === 'withdrawals'" class="withdrawal-panel">
                <div class="withdrawal-panel-head">
                  <div>
                    <div class="withdrawal-eyebrow">Release Ledger</div>
                    <div class="withdrawal-title">Item Withdrawals</div>
                  </div>
                  <span class="withdrawal-count">Displaying {{ filteredWithdrawals.length }} results</span>
                </div>

                <div class="table-responsive withdrawal-table-wrap">
                  <table class="table align-middle mb-0 withdrawal-table">
                    <thead>
                      <tr>
                        <th>Ref. No</th>
                        <th>Requested By</th>
                        <th>Item</th>
                        <th class="text-end">Qty</th>
                        <th>Date Released</th>
                        <th>Status</th>
                        <th class="text-center" style="width: 100px">Action</th>
                      </tr>
                      <tr class="withdrawal-filter-row">
                        <th>
                          <input v-model="withdrawalFilters.reference_no" class="form-control form-control-sm" placeholder="Filter ref no" />
                        </th>
                        <th>
                          <input v-model="withdrawalFilters.requested_by" class="form-control form-control-sm" placeholder="Filter requester" />
                        </th>
                        <th>
                          <input v-model="withdrawalFilters.item_name" class="form-control form-control-sm" placeholder="Filter item" />
                        </th>
                        <th></th>
                        <th>
                          <input v-model="withdrawalFilters.released_at" class="form-control form-control-sm" placeholder="Filter date" />
                        </th>
                        <th></th>
                        <th></th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-if="filteredWithdrawals.length === 0">
                        <td colspan="7" class="text-center text-muted py-4">No withdrawal records found.</td>
                      </tr>
                      <tr v-for="item in filteredWithdrawals" :key="item.id">
                        <td class="fw-semibold">{{ item.reference_no }}</td>
                        <td>{{ item.requested_by }}</td>
                        <td>{{ item.item_name }}</td>
                        <td class="text-end fw-semibold">{{ formatNumber(item.quantity) }}</td>
                        <td>{{ item.released_at }}</td>
                        <td>
                          <span class="badge withdrawal-status-chip">
                            <i class="ri-checkbox-circle-fill me-1"></i>{{ item.status }}
                          </span>
                        </td>
                        <td class="text-center">
                          <div class="d-inline-flex gap-1">
                            <button class="btn btn-sm btn-outline-primary withdrawal-action-btn" type="button" title="View" @click="openViewModal('withdrawal', item)">
                              <i class="ri-eye-line"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-secondary withdrawal-action-btn" type="button" title="Edit" @click="openEditRecordModal('withdrawal', item)">
                              <i class="ri-pencil-line"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger withdrawal-action-btn" type="button" title="Delete">
                              <i class="ri-close-line"></i>
                            </button>
                          </div>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>

              <div v-else class="module-panel-placeholder">
                <div class="module-panel-title">{{ activeModuleLabel }}</div>
                <p class="mb-0 text-muted">
                  This module is now available as a section in Inventory. Next step is wiring its full workflows
                  (forms, logs, and reports) based on your preferred process.
                </p>
              </div>

              <Pagination
                v-if="activeModule === 'items' && meta && meta.total"
                :links="links"
                :pagination="meta"
                :lists="rows.length"
                @fetch="fetch"
              />
            </div>
          </BCardBody>
        </BCard>
      </BCol>
    </BRow>

    <b-modal
      v-model="showModal"
      :title="form.id ? 'Edit Stock' : 'Create Stock'"
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
import PageHeader from '@/Shared/Components/PageHeader.vue';
import Pagination from '@/Shared/Components/Pagination.vue';
import RecordViewModal from '@/Pages/Modules/InventoryStocks/Modals/RecordViewModal.vue';
import RecordEditModal from '@/Pages/Modules/InventoryStocks/Modals/RecordEditModal.vue';

export default {
  components: { Head, PageHeader, Pagination, RecordViewModal, RecordEditModal },
  props: {
    dropdowns: { type: Object, default: () => ({}) },
    inventories: { type: [Array, Object], default: () => [] },
  },
  data() {
    return {
      loading: false,
      saving: false,
      rows: [],
      meta: null,
      links: null,
      showModal: false,
      showViewModal: false,
      viewRecord: null,
      viewRecordType: '',
      showEditModal: false,
      editRecord: null,
      editRecordType: '',
      statuses: ['available', 'low', 'out', 'reserved'],
      activeModule: 'items',
      modules: [
        { key: 'items', label: 'Items', icon: 'ri-barcode-box-line' },
        { key: 'receivings', label: 'Receivings', icon: 'ri-inbox-archive-line' },
        { key: 'withdrawals', label: 'Withdrawals', icon: 'ri-shopping-cart-line' },
        { key: 'reports', label: 'Reports', icon: 'ri-line-chart-line' },
        { key: 'par_ics_ris', label: 'PAR/ICS/RIS', icon: 'ri-file-list-3-line' },
      ],
      filters: {
        keyword: '',
        status: '',
        location_id: '',
      },
      receivingFilters: {
        po_number: '',
        supplier_name: '',
        remarks: '',
        received_at: '',
      },
      receivingRows: [
        {
          id: 1,
          po_number: '26-03-2296',
          supplier_name: 'Lines Printing Services',
          remarks: 'For NWMC 2026 and GAD Activity use',
          received_at: '2026-03-19 17:46:42',
          status: 'Updated',
        },
        {
          id: 2,
          po_number: '26-03-2319',
          supplier_name: 'VIA ALTO GENERAL MERCHANDISE',
          remarks: 'For office use - PSTO ZSP',
          received_at: '2026-03-19 09:36:32',
          status: 'Updated',
        },
        {
          id: 3,
          po_number: '25-09-2120',
          supplier_name: 'AMJ Consumer Goods Trading',
          remarks: 'For office use',
          received_at: '2026-03-18 10:23:50',
          status: 'Updated',
        },
        {
          id: 4,
          po_number: '25-11-2218',
          supplier_name: 'HENROSE ENTERPRISES',
          remarks: 'For PSTO-ZSP use',
          received_at: '2026-03-18 07:46:30',
          status: 'Updated',
        },
      ],
      withdrawalFilters: {
        reference_no: '',
        requested_by: '',
        item_name: '',
        released_at: '',
      },
      withdrawalRows: [
        {
          id: 1,
          reference_no: 'WDR-26-0001',
          requested_by: 'Juan Dela Cruz',
          item_name: 'A4 Bond Paper',
          quantity: 15,
          released_at: '2026-03-21 09:14:00',
          status: 'Released',
        },
        {
          id: 2,
          reference_no: 'WDR-26-0002',
          requested_by: 'Maria Santos',
          item_name: 'Printer Ink (Black)',
          quantity: 3,
          released_at: '2026-03-22 14:32:10',
          status: 'Released',
        },
        {
          id: 3,
          reference_no: 'WDR-26-0003',
          requested_by: 'PSTO ZSP Unit',
          item_name: 'Document Folder',
          quantity: 40,
          released_at: '2026-03-23 10:03:56',
          status: 'Released',
        },
      ],
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
    filteredReceivings() {
      const filters = this.receivingFilters;
      return this.receivingRows.filter((item) => {
        const po = (item.po_number || '').toLowerCase();
        const supplier = (item.supplier_name || '').toLowerCase();
        const remarks = (item.remarks || '').toLowerCase();
        const date = (item.received_at || '').toLowerCase();

        return (
          po.includes((filters.po_number || '').toLowerCase()) &&
          supplier.includes((filters.supplier_name || '').toLowerCase()) &&
          remarks.includes((filters.remarks || '').toLowerCase()) &&
          date.includes((filters.received_at || '').toLowerCase())
        );
      });
    },
    filteredWithdrawals() {
      const filters = this.withdrawalFilters;
      return this.withdrawalRows.filter((item) => {
        const reference = (item.reference_no || '').toLowerCase();
        const requestedBy = (item.requested_by || '').toLowerCase();
        const itemName = (item.item_name || '').toLowerCase();
        const releasedAt = (item.released_at || '').toLowerCase();

        return (
          reference.includes((filters.reference_no || '').toLowerCase()) &&
          requestedBy.includes((filters.requested_by || '').toLowerCase()) &&
          itemName.includes((filters.item_name || '').toLowerCase()) &&
          releasedAt.includes((filters.released_at || '').toLowerCase())
        );
      });
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
  },
  created() {
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

.module-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
  gap: 10px;
}

.module-tile {
  border: 1px solid #d7dfef;
  border-radius: 10px;
  background: linear-gradient(180deg, #f9fbff, #f2f6fd);
  color: #334155;
  min-height: 95px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 8px;
  font-size: 13px;
  font-weight: 600;
}

.module-tile i {
  font-size: 26px;
  color: #64748b;
}

.module-tile.active {
  border-color: #3b82f6;
  background: linear-gradient(180deg, #eef4ff, #dce9ff);
  box-shadow: 0 6px 18px rgba(59, 130, 246, 0.2);
}

.module-tile.active i,
.module-tile.active span {
  color: #1d4ed8;
}

.inv-table-wrap {
  border: 1px solid #e2e8f0;
  border-radius: 12px;
}

.inv-table thead th {
  background: #f8fafc;
  color: #334155;
  font-weight: 700;
  border-bottom: 1px solid #e2e8f0;
}

.inv-table tbody td {
  border-color: #eef2f7;
}

.rarity-chip {
  border-radius: 999px;
  padding: 6px 10px;
  font-size: 11px;
  letter-spacing: 0.02em;
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

.receiving-panel {
  border: 1px solid #d7ead6;
  border-radius: 12px;
  overflow: hidden;
  background: #f9fef9;
}

.receiving-panel-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 10px;
  padding: 14px 16px;
  background: linear-gradient(135deg, #2f8f3d, #1d6b2d);
}

.receiving-eyebrow {
  font-size: 11px;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: rgba(255, 255, 255, 0.8);
  font-weight: 700;
}

.receiving-title {
  color: #fff;
  font-size: 17px;
  font-weight: 700;
}

.receiving-count {
  color: #e7f8e8;
  background: rgba(255, 255, 255, 0.12);
  border: 1px solid rgba(255, 255, 255, 0.22);
  border-radius: 999px;
  padding: 6px 10px;
  font-size: 12px;
  font-weight: 600;
}

.receiving-table-wrap {
  border-top: 1px solid #d6ead3;
}

.receiving-table thead th {
  background: #e3f6df;
  color: #1f5129;
  border-bottom: 1px solid #cce8c7;
  font-weight: 700;
}

.receiving-filter-row th {
  background: #f2fbf0 !important;
  padding-top: 8px;
  padding-bottom: 8px;
}

.receiving-filter-row .form-control {
  border-color: #cfe8cc;
  background: #fff;
}

.receiving-table tbody tr:nth-child(odd) td {
  background: #f8fff7;
}

.receiving-table tbody tr:nth-child(even) td {
  background: #eef9ec;
}

.receiving-table tbody td {
  border-color: #dbedd8;
}

.receiving-status-chip {
  background: #dff7d8;
  color: #1a6a29;
  border: 1px solid #a3d59d;
  border-radius: 999px;
  padding: 5px 9px;
  font-weight: 700;
}

.receiving-action-btn {
  border-width: 1px;
}

.withdrawal-panel {
  border: 1px solid #dbe5f3;
  border-radius: 12px;
  overflow: hidden;
  background: #f8fbff;
}

.withdrawal-panel-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 10px;
  padding: 14px 16px;
  background: linear-gradient(135deg, #365fa8, #224685);
}

.withdrawal-eyebrow {
  font-size: 11px;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: rgba(255, 255, 255, 0.78);
  font-weight: 700;
}

.withdrawal-title {
  color: #fff;
  font-size: 17px;
  font-weight: 700;
}

.withdrawal-count {
  color: #e5eeff;
  background: rgba(255, 255, 255, 0.13);
  border: 1px solid rgba(255, 255, 255, 0.22);
  border-radius: 999px;
  padding: 6px 10px;
  font-size: 12px;
  font-weight: 600;
}

.withdrawal-table-wrap {
  border-top: 1px solid #d6e2f4;
}

.withdrawal-table thead th {
  background: #e6eeff;
  color: #1f3f7a;
  border-bottom: 1px solid #d3def5;
  font-weight: 700;
}

.withdrawal-filter-row th {
  background: #f3f7ff !important;
  padding-top: 8px;
  padding-bottom: 8px;
}

.withdrawal-filter-row .form-control {
  border-color: #d4def3;
  background: #fff;
}

.withdrawal-table tbody tr:nth-child(odd) td {
  background: #f9fbff;
}

.withdrawal-table tbody tr:nth-child(even) td {
  background: #f0f5ff;
}

.withdrawal-table tbody td {
  border-color: #dce5f6;
}

.withdrawal-status-chip {
  background: #e4ecff;
  color: #1f4aa1;
  border: 1px solid #b8caf6;
  border-radius: 999px;
  padding: 5px 9px;
  font-weight: 700;
}

.withdrawal-action-btn {
  border-width: 1px;
}
</style>

































