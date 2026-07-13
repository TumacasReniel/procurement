<template>
  <b-modal
    :model-value="modelValue"
    :title="title"
    centered
    :scrollable="true"
    :fullscreen="type === 'stock' ? 'lg' : false"
    :style="modalStyle"
    header-class="p-3 bg-body-tertiary border-bottom"
    content-class="border-0 shadow-lg"
    body-class="bg-body p-0"
    footer-class="bg-body-tertiary border-top py-2"
    :size="type === 'stock' ? 'xl' : 'lg'"
    class="v-modal-custom"
    modal-class="zoomIn"
    @update:modelValue="(value) => $emit('update:modelValue', value)"
  >
    <!-- ── ITEM VIEW ──────────────────────────────────────── -->
    <div v-if="type === 'item' && record" class="rvm-item-view">
      <!-- Hero header -->
      <div class="rvm-item-hero">
        <div class="rvm-item-hero-icon">
          <i class="ri-barcode-box-line"></i>
        </div>
        <div class="rvm-item-hero-body">
          <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
            <span class="rvm-item-code-badge">{{ record.code || '—' }}</span>
            <span v-if="record.category" class="rvm-item-cat-badge">
              <i class="ri-price-tag-3-line me-1"></i>{{ record.category }}
            </span>
          </div>
          <h5 class="rvm-item-name">{{ record.name || '—' }}</h5>
        </div>
      </div>

      <!-- Stat cards -->
      <div class="rvm-item-stats">
        <div class="rvm-stat-card rvm-stat-card--qty">
          <div class="rvm-stat-icon"><i class="ri-stack-line"></i></div>
          <div>
            <strong class="rvm-stat-val">{{ formatNumber(record.total_quantity) }}</strong>
            <span class="rvm-stat-lbl">Total Quantity</span>
          </div>
        </div>
        <div class="rvm-stat-card rvm-stat-card--entries">
          <div class="rvm-stat-icon"><i class="ri-inbox-archive-line"></i></div>
          <div>
            <strong class="rvm-stat-val">{{ record.stock_count ?? 0 }}</strong>
            <span class="rvm-stat-lbl">Stock Entries</span>
          </div>
        </div>
        <div class="rvm-stat-card rvm-stat-card--code">
          <div class="rvm-stat-icon"><i class="ri-qr-code-line"></i></div>
          <div>
            <strong class="rvm-stat-val" style="font-size:1rem;letter-spacing:0.03em">
              {{ record.code || '—' }}
            </strong>
            <span class="rvm-stat-lbl">Item Code</span>
          </div>
        </div>
      </div>

      <!-- Stock entries table -->
      <div class="rvm-item-stocks">
        <div class="rvm-item-stocks-header">
          <div>
            <h6 class="rvm-item-stocks-title">
              <i class="ri-list-check-2 me-1 text-primary"></i>Stock Entries
            </h6>
            <p class="rvm-item-stocks-sub">All stock batches for this item</p>
          </div>
        </div>

        <div v-if="stockItemsLoading" class="rvm-table-state">
          <div class="spinner-border spinner-border-sm text-primary me-2"></div>
          <span class="text-muted">Loading stock entries…</span>
        </div>

        <div v-else-if="!stockItems.length" class="rvm-table-state">
          <i class="ri-inbox-line rvm-empty-icon"></i>
          <p class="text-muted mb-0">No stock entries yet for this item.</p>
        </div>

        <div v-else class="table-responsive">
          <table class="table table-hover align-middle mb-0 rvm-stocks-table">
            <thead>
              <tr>
                <th class="text-end" style="width:90px">Quantity</th>
                <th style="width:70px" class="text-center">Unit</th>
                <th class="text-end" style="width:110px">Unit Cost</th>
                <th class="text-end" style="width:120px">Total Value</th>
                <th>Description</th>
                <th style="width:120px">Date Added</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="stock in stockItems" :key="stock.id">
                <td class="text-end fw-bold">
                  <span
                    class="badge"
                    :class="stockQtyBadge(stock.quantity)"
                  >
                    {{ formatNumber(stock.quantity) }}
                  </span>
                </td>
                <td class="text-center">
                  <span class="rvm-unit-chip">{{ stock.unit || '—' }}</span>
                </td>
                <td class="text-end text-muted small">
                  ₱{{ formatNumber(stock.unit_cost) }}
                </td>
                <td class="text-end fw-semibold">
                  ₱{{ formatNumber(stock.quantity * stock.unit_cost) }}
                </td>
                <td class="text-muted small">{{ stock.description || '—' }}</td>
                <td class="text-muted small">{{ formatDate(stock.entry_date) }}</td>
              </tr>
            </tbody>
            <tfoot>
              <tr class="rvm-total-row">
                <td class="text-end fw-bold">{{ formatNumber(totalQty) }}</td>
                <td></td>
                <td></td>
                <td class="text-end fw-bold text-primary">₱{{ formatNumber(totalValue) }}</td>
                <td colspan="2" class="text-muted small">
                  {{ stockItems.length }} entr{{ stockItems.length === 1 ? 'y' : 'ies' }}
                </td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>

    <!-- ── STOCK VIEW (unchanged) ─────────────────────────── -->
    <div v-else-if="type === 'stock' && record" class="p-3">
      <BRow class="g-2">
        <BCol v-for="field in fields" :key="field.label" class="col-lg">
          <BCard no-body class="h-100 border shadow-none">
            <BCardBody class="p-2 p-xl-3">
              <div class="text-muted small fw-semibold text-uppercase mb-1">{{ field.label }}</div>
              <div class="text-body fw-semibold">{{ field.value || '—' }}</div>
            </BCardBody>
          </BCard>
        </BCol>
      </BRow>

      <BCard no-body class="border shadow-none mt-3 mb-0">
        <div class="card-header bg-body-tertiary d-flex flex-wrap justify-content-between align-items-center gap-2 py-2">
          <div>
            <h6 class="mb-1 text-body">Items under this stock</h6>
            <p class="mb-0 text-muted small">Inventory items currently assigned to {{ record.name || 'this stock group' }}.</p>
          </div>
          <div class="d-flex flex-wrap gap-2 align-items-center">
            <span class="badge text-bg-primary rounded-pill px-3 py-2">
              {{ formatNumber(stockItemCount) }} item{{ stockItemCount === 1 ? '' : 's' }}
            </span>
            <b-button
              v-if="canAddStockItem"
              type="button"
              size="sm"
              variant="primary"
              @click="$emit('add-stock-item', record)"
            >
              <i class="ri-add-circle-fill me-1"></i>Add Item
            </b-button>
          </div>
        </div>
        <div class="table-responsive">
          <table class="table table-sm table-hover align-middle mb-0 text-nowrap">
            <thead class="table-light">
              <tr>
                <th>Code</th>
                <th>Item Name</th>
                <th>Category</th>
                <th class="text-end">Quantity</th>
                <th class="text-end">Unit Cost</th>
                <th>Expiration</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="stockItemsLoading">
                <td colspan="6" class="text-center text-muted py-4">Loading linked items...</td>
              </tr>
              <tr v-else-if="stockItems.length === 0">
                <td colspan="6" class="text-center text-muted py-4">No items are linked to this stock yet.</td>
              </tr>
              <tr v-else v-for="item in stockItems" :key="item.id">
                <td class="fw-semibold text-primary">{{ item.code || '—' }}</td>
                <td>{{ item.name || '—' }}</td>
                <td>{{ item.category || '—' }}</td>
                <td class="text-end">{{ formatNumber(item.quantity) }}</td>
                <td class="text-end">{{ formatNumber(item.unit_cost) }}</td>
                <td>{{ item.expiration || '—' }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </BCard>
    </div>

    <!-- ── OTHER TYPES ────────────────────────────────────── -->
    <div v-else-if="record" class="p-3">
      <BRow class="g-2">
        <BCol v-for="field in fields" :key="field.label" cols="6" md="6">
          <BCard no-body class="h-100 border shadow-none">
            <BCardBody class="p-2 p-xl-3">
              <div class="text-muted small fw-semibold text-uppercase mb-1">{{ field.label }}</div>
              <div class="text-body fw-semibold">{{ field.value || '—' }}</div>
            </BCardBody>
          </BCard>
        </BCol>
      </BRow>
    </div>

    <div v-else class="text-muted p-3">No record selected.</div>

    <template #footer>
      <b-button type="button" variant="light" @click="$emit('update:modelValue', false)">Close</b-button>
    </template>
  </b-modal>
</template>

<script>
export default {
  name: 'RecordViewModal',
  props: ['modelValue', 'type', 'record', 'stockItems', 'stockItemsLoading', 'canAddStockItem'],
  emits: ['update:modelValue', 'add-stock-item'],
  computed: {
    modalStyle() {
      if (this.type === 'stock') return '--vz-modal-width: 96vw;';
      if (this.type === 'item') return '--vz-modal-width: 700px;';
      return '';
    },
    title() {
      if (this.type === 'item')       return 'Item Details';
      if (this.type === 'stock')      return 'View Stock';
      if (this.type === 'receiving')  return 'View Receiving';
      if (this.type === 'withdrawal') return 'View Withdrawal';
      return 'View Record';
    },
    stockItemCount() {
      return Number(this.record?.item_count ?? (this.stockItems || []).length) || 0;
    },
    totalQty() {
      return (this.stockItems || []).reduce((s, st) => s + Number(st.quantity || 0), 0);
    },
    totalValue() {
      return (this.stockItems || []).reduce((s, st) => s + Number(st.quantity || 0) * Number(st.unit_cost || 0), 0);
    },
    fields() {
      if (!this.record) return [];
      const maps = {
        stock: [
          ['Code',           this.record.code],
          ['Stock Name',     this.record.name],
          ['Entry Date',     this.record.entry_date],
          ['Linked Items',   this.record.item_count],
          ['Total Quantity', this.record.total_quantity],
        ],
        receiving: [
          ['Item',          this.record.item_name],
          ['Approved By',   this.record.approved_by],
          ['Status',        this.record.status],
          ['Date Received', this.record.received_at],
          ['Remarks',       this.record.remarks],
        ],
        withdrawal: [
          ['Item',          this.record.item_name],
          ['Requested By',  this.record.requested_by],
          ['Approved By',   this.record.approved_by],
          ['Date Released', this.record.released_at],
          ['Status',        this.record.status],
          ['Remarks',       this.record.remarks],
        ],
      };
      const mapped = maps[this.type] || Object.entries(this.record).map(([k, v]) => [
        k.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase()), v,
      ]);
      return mapped.map(([label, value]) => ({ label, value: this.formatValue(value) }));
    },
  },
  methods: {
    formatValue(value) {
      if (value === null || value === undefined || value === '') return '—';
      if (typeof value === 'object') return value.name || value.code || JSON.stringify(value);
      return value;
    },
    formatNumber(value) {
      const n = Number(value || 0);
      return new Intl.NumberFormat('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(n);
    },
    formatDate(val) {
      if (!val) return '—';
      return new Date(val).toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' });
    },
    stockQtyBadge(qty) {
      const n = Number(qty || 0);
      if (n <= 0) return 'bg-danger-subtle text-danger';
      if (n < 5)  return 'bg-warning-subtle text-warning';
      return 'bg-success-subtle text-success';
    },
  },
};
</script>

<style scoped>
/* ── Item Hero ───────────────────────────────────────── */
.rvm-item-view {
  display: flex;
  flex-direction: column;
  gap: 0;
}

.rvm-item-hero {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  padding: 1.25rem 1.25rem 1rem;
  background: linear-gradient(135deg, #1e2d6b 0%, #2d3f8a 50%, #4b5b93 100%);
  color: #fff;
}

.rvm-item-hero-icon {
  width: 52px;
  height: 52px;
  border-radius: 14px;
  background: rgba(255, 255, 255, 0.15);
  border: 1px solid rgba(255, 255, 255, 0.2);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  flex-shrink: 0;
}

.rvm-item-hero-body {
  flex: 1;
  min-width: 0;
}

.rvm-item-code-badge {
  display: inline-flex;
  align-items: center;
  padding: 0.18rem 0.65rem;
  border-radius: 7px;
  background: rgba(255, 255, 255, 0.18);
  border: 1px solid rgba(255, 255, 255, 0.25);
  color: #fff;
  font-size: 0.75rem;
  font-family: monospace;
  font-weight: 700;
  letter-spacing: 0.05em;
}

.rvm-item-cat-badge {
  display: inline-flex;
  align-items: center;
  padding: 0.18rem 0.65rem;
  border-radius: 7px;
  background: rgba(255, 255, 255, 0.1);
  border: 1px solid rgba(255, 255, 255, 0.15);
  color: rgba(255, 255, 255, 0.8);
  font-size: 0.72rem;
  font-weight: 600;
}

.rvm-item-name {
  font-size: 1.15rem;
  font-weight: 800;
  color: #fff;
  margin: 0;
  line-height: 1.3;
  word-break: break-word;
}

/* ── Stat Cards ──────────────────────────────────────── */
.rvm-item-stats {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  border-bottom: 1px solid #e8eef8;
}

.rvm-stat-card {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.9rem 1.1rem;
  border-right: 1px solid #e8eef8;
}
.rvm-stat-card:last-child {
  border-right: none;
}

.rvm-stat-icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.1rem;
  flex-shrink: 0;
}

.rvm-stat-card--qty   .rvm-stat-icon { background:#dbeafe; color:#2563eb; }
.rvm-stat-card--entries .rvm-stat-icon { background:#dcfce7; color:#16a34a; }
.rvm-stat-card--code  .rvm-stat-icon { background:#ede9fe; color:#7c3aed; }

.rvm-stat-val {
  display: block;
  font-size: 1.35rem;
  font-weight: 900;
  color: #0f172a;
  line-height: 1.1;
}

.rvm-stat-lbl {
  display: block;
  font-size: 0.68rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  color: #64748b;
  margin-top: 0.15rem;
}

/* ── Stock Entries Section ───────────────────────────── */
.rvm-item-stocks {
  padding: 1rem 1.25rem 1.25rem;
}

.rvm-item-stocks-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 0.75rem;
}

.rvm-item-stocks-title {
  font-size: 0.88rem;
  font-weight: 800;
  color: #0f172a;
  margin: 0;
}
.rvm-item-stocks-sub {
  font-size: 0.75rem;
  color: #64748b;
  margin: 0.15rem 0 0;
}

.rvm-table-state {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem;
  gap: 0.5rem;
}

.rvm-empty-icon {
  font-size: 1.75rem;
  color: #94a3b8;
}

/* ── Stocks table ─────────────────────────────────────── */
.rvm-stocks-table {
  font-size: 0.82rem;
  border: 1px solid #e8eef8;
  border-radius: 10px;
  overflow: hidden;
}

.rvm-stocks-table thead th {
  background: linear-gradient(180deg, #f8fbff, #f0f5ff);
  color: #4b5b93;
  font-size: 0.7rem;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  padding: 0.6rem 0.75rem;
  border-bottom: 2px solid #dce4f2;
}

.rvm-stocks-table tbody td {
  padding: 0.55rem 0.75rem;
  border-bottom: 1px solid #f0f5ff;
}

.rvm-stocks-table tfoot td {
  padding: 0.55rem 0.75rem;
  border-top: 2px solid #dce4f2;
  background: #f8fbff;
}

.rvm-total-row td {
  font-size: 0.82rem;
}

.rvm-unit-chip {
  display: inline-block;
  padding: 0.08rem 0.45rem;
  border-radius: 5px;
  background: #ede9fe;
  color: #6d28d9;
  font-size: 0.72rem;
  font-weight: 700;
}
</style>
