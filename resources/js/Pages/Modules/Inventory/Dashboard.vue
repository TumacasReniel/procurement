<template>
  <div class="inventory-dashboard-page">
    <Head title="Inventory Dashboard" />
    <PageHeader title="Inventory Dashboard" pageTitle="Overview" />

    <section class="card border-0 inventory-dashboard-hero mb-4">
      <div class="card-body p-4 p-xl-5">
        <div class="hero-grid">
          <div>
            <p class="hero-copy mb-3">
              Review the current range, track category volume, and catch low-stock signals before they turn into delays.
            </p>
            <div class="hero-chip-row">
              <span class="hero-chip">Range: {{ formattedRange }}</span>
              <span class="hero-chip">Tracked items: {{ formatNumber(totalItems) }}</span>
              <span class="hero-chip">Low stock: {{ formatNumber(lowStockItems) }}</span>
            </div>
          </div>

          <div class="hero-controls">
            <div class="period-switch" role="group" aria-label="Select reporting period">
              <button
                class="period-btn"
                :class="{ active: selectedPeriod === 'monthly' }"
                @click="changePeriod('monthly')"
              >
                Monthly
              </button>
              <button
                class="period-btn"
                :class="{ active: selectedPeriod === 'quarterly' }"
                @click="changePeriod('quarterly')"
              >
                Quarterly
              </button>
              <button
                class="period-btn"
                :class="{ active: selectedPeriod === 'yearly' }"
                @click="changePeriod('yearly')"
              >
                Yearly
              </button>
            </div>

            <div class="hero-health-card">
              <span class="hero-health-label">Stock health</span>
              <strong>{{ stockHealthLabel }}</strong>
              <p class="mb-0">{{ stockHealthCopy }}</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <div class="metric-grid mb-4">
      <article
        v-for="card in summaryCards"
        :key="card.label"
        class="metric-card"
        :class="card.tone"
      >
        <div class="metric-icon">
          <i :class="card.icon"></i>
        </div>
        <div>
          <span class="metric-label">{{ card.label }}</span>
          <strong class="metric-value">{{ card.value }}</strong>
          <p class="metric-note mb-0">{{ card.note }}</p>
        </div>
      </article>
    </div>

    <div class="row g-4 mb-4">
      <div class="col-xl-8">
        <section class="card border-0 shadow-sm h-100 panel-card">
          <div class="card-header panel-header">
            <div>
              <span class="panel-kicker">Categories</span>
              <h5 class="panel-title mb-1">Quantity by category</h5>
              <p class="panel-copy mb-0">Volume distribution across inventory classifications.</p>
            </div>
          </div>
          <div class="card-body">
            <apexchart type="bar" height="320" :options="categoryChartOptions" :series="categorySeries" />
          </div>
        </section>
      </div>

      <div class="col-xl-4">
        <section class="card border-0 shadow-sm h-100 panel-card">
          <div class="card-header panel-header">
            <div>
              <span class="panel-kicker">Status</span>
              <h5 class="panel-title mb-1">Inventory status mix</h5>
              <p class="panel-copy mb-0">High-level balance between healthy, low, and empty stocks.</p>
            </div>
          </div>
          <div class="card-body">
            <apexchart type="donut" height="320" :options="statusChartOptions" :series="statusSeries" />
          </div>
        </section>
      </div>
    </div>

    <div class="row g-4">
      <div class="col-xl-8">
        <section class="card border-0 shadow-sm panel-card h-100">
          <div class="card-header panel-header">
            <div>
              <span class="panel-kicker">Latest Items</span>
              <h5 class="panel-title mb-1">Recently tracked inventory</h5>
              <p class="panel-copy mb-0">Newest inventory item records inside the selected date range.</p>
            </div>
          </div>

          <div class="card-body p-0">
            <div class="table-responsive latest-table-wrap">
              <table class="table table-hover align-middle mb-0 latest-table">
                <thead>
                  <tr>
                    <th>Code</th>
                    <th>Stock</th>
                    <th>Category</th>
                    <th>Item Name</th>
                    <th class="text-end">Quantity</th>
                    <th class="text-end">Unit Cost</th>
                    <th>Expiration</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="recent.length === 0">
                    <td colspan="7" class="text-center text-muted py-5">No inventory items found for this range.</td>
                  </tr>
                  <tr v-else v-for="row in recent" :key="row.id">
                    <td class="fw-semibold">{{ row.code }}</td>
                    <td>{{ row.stock_name }}</td>
                    <td>{{ row.stock_category }}</td>
                    <td>{{ row.item_name }}</td>
                    <td class="text-end">{{ formatNumber(row.quantity) }}</td>
                    <td class="text-end">{{ formatCurrency(row.unit_cost) }}</td>
                    <td>{{ row.expiration }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </section>
      </div>

      <div class="col-xl-4">
        <section class="card border-0 shadow-sm panel-card h-100">
          <div class="card-header panel-header">
            <div>
              <span class="panel-kicker">Movement</span>
              <h5 class="panel-title mb-1">Range snapshot</h5>
              <p class="panel-copy mb-0">Receivings and withdrawals recorded in the current period.</p>
            </div>
          </div>

          <div class="card-body">
            <div class="movement-stack">
              <article class="movement-card">
                <div class="movement-icon success">
                  <i class="ri-inbox-archive-line"></i>
                </div>
                <div>
                  <span class="movement-label">Receivings</span>
                  <strong>{{ formatNumber(receivingsCount) }}</strong>
                  <p class="mb-0">Items entering inventory during this range.</p>
                </div>
              </article>

              <article class="movement-card">
                <div class="movement-icon warning">
                  <i class="ri-arrow-left-right-line"></i>
                </div>
                <div>
                  <span class="movement-label">Withdrawals</span>
                  <strong>{{ formatNumber(withdrawalsCount) }}</strong>
                  <p class="mb-0">Released item records captured in the same period.</p>
                </div>
              </article>

              <article class="movement-card">
                <div class="movement-icon danger">
                  <i class="ri-alert-line"></i>
                </div>
                <div>
                  <span class="movement-label">Out of stock</span>
                  <strong>{{ formatNumber(outOfStock) }}</strong>
                  <p class="mb-0">Items currently showing zero quantity in the selected window.</p>
                </div>
              </article>
            </div>
          </div>
        </section>
      </div>
    </div>
  </div>
</template>

<script>
import { Head, router } from '@inertiajs/vue3';
import PageHeader from '@/Shared/Components/PageHeader.vue';
import VueApexCharts from 'vue3-apexcharts';

export default {
  components: { Head, PageHeader, apexchart: VueApexCharts },
  props: {
    dropdowns: { type: Object, default: () => ({}) },
    totalItems: { type: Number, default: 0 },
    lowStockItems: { type: Number, default: 0 },
    outOfStock: { type: Number, default: 0 },
    totalQuantity: { type: Number, default: 0 },
    totalStocks: { type: Number, default: 0 },
    receivingsCount: { type: Number, default: 0 },
    withdrawalsCount: { type: Number, default: 0 },
    byCategory: { type: Array, default: () => [] },
    byStatus: { type: Object, default: () => ({}) },
    recent: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({ period: 'monthly', start_date: null, end_date: null }) },
  },
  data() {
    return {
      selectedPeriod: this.filters?.period || 'monthly',
    };
  },
  computed: {
    formattedRange() {
      if (!this.filters?.start_date || !this.filters?.end_date) {
        return 'No date range available';
      }

      return `${this.formatDate(this.filters.start_date)} to ${this.formatDate(this.filters.end_date)}`;
    },
    stockHealthLabel() {
      if (this.outOfStock > 0) {
        return 'Needs attention';
      }

      if (this.lowStockItems > 0) {
        return 'Watch closely';
      }

      return 'Stable';
    },
    stockHealthCopy() {
      if (this.outOfStock > 0) {
        return 'Some records have already reached zero quantity.';
      }

      if (this.lowStockItems > 0) {
        return 'Several items are approaching low-stock levels.';
      }

      return 'No immediate stock pressure is visible in this range.';
    },
    summaryCards() {
      return [
        {
          label: 'Total Stocks',
          value: this.formatNumber(this.totalStocks),
          note: 'Stock groups currently tracked',
          icon: 'ri-archive-stack-line',
          tone: 'brand',
        },
        {
          label: 'Tracked Items',
          value: this.formatNumber(this.totalItems),
          note: 'Active inventory item records',
          icon: 'ri-cube-line',
          tone: 'success',
        },
        {
          label: 'Units On Hand',
          value: this.formatNumber(this.totalQuantity),
          note: 'Combined quantity in the range',
          icon: 'ri-database-2-line',
          tone: 'info',
        },
        {
          label: 'Low Stock Alerts',
          value: this.formatNumber(this.lowStockItems),
          note: 'Items already near depletion',
          icon: 'ri-alarm-warning-line',
          tone: 'warning',
        },
      ];
    },
    categorySeries() {
      return [{ name: 'Quantity', data: this.byCategory.map((item) => Number(item.y || 0)) }];
    },
    categoryChartOptions() {
      return {
        chart: { type: 'bar', toolbar: { show: false }, fontFamily: 'inherit' },
        xaxis: {
          categories: this.byCategory.map((item) => item.name),
          labels: { style: { colors: '#64748b' } },
        },
        yaxis: {
          labels: { style: { colors: '#64748b' } },
        },
        grid: {
          borderColor: '#edf1f7',
          strokeDashArray: 4,
        },
        plotOptions: {
          bar: {
            borderRadius: 10,
            columnWidth: '48%',
          },
        },
        colors: ['#4b5b93'],
        dataLabels: { enabled: false },
        noData: { text: 'No inventory category data available.' },
      };
    },
    statusSeries() {
      return Object.values(this.byStatus || {}).map((value) => Number(value || 0));
    },
    statusChartOptions() {
      return {
        labels: Object.keys(this.byStatus || {}),
        chart: { type: 'donut', fontFamily: 'inherit' },
        colors: ['#4b5b93', '#f59e0b', '#dc2626', '#38bdf8'],
        legend: { position: 'bottom', fontSize: '13px' },
        stroke: { width: 0 },
        dataLabels: { enabled: false },
        plotOptions: {
          pie: {
            donut: {
              size: '70%',
            },
          },
        },
        noData: { text: 'No inventory status data available.' },
      };
    },
  },
  methods: {
    changePeriod(period) {
      if (this.selectedPeriod === period) return;

      this.selectedPeriod = period;
      router.get('/inventory-dashboard', { period }, {
        preserveScroll: true,
        preserveState: true,
        replace: true,
      });
    },
    formatNumber(num) {
      return new Intl.NumberFormat().format(Number(num || 0));
    },
    formatCurrency(value) {
      const n = Number(value || 0);
      return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(n);
    },
    formatDate(value) {
      return new Intl.DateTimeFormat('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
      }).format(new Date(`${value}T00:00:00`));
    },
  },
};
</script>

<style scoped>
.inventory-dashboard-page {
  --inventory-brand: #4b5b93;
  --inventory-brand-deep: #38467a;
}

.inventory-dashboard-hero {
  background:
    radial-gradient(circle at top right, rgba(255, 255, 255, 0.18), transparent 34%),
    linear-gradient(135deg, var(--inventory-brand) 0%, var(--inventory-brand-deep) 100%);
  color: #fff;
  box-shadow: 0 26px 48px rgba(56, 70, 122, 0.2);
}

.hero-grid {
  display: grid;
  grid-template-columns: minmax(0, 1.2fr) minmax(320px, 0.8fr);
  gap: 24px;
  align-items: center;
}

.hero-kicker,
.panel-kicker {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  font-size: 11px;
  font-weight: 800;
  letter-spacing: 0.12em;
  text-transform: uppercase;
}

.hero-kicker {
  padding: 8px 12px;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.12);
  margin-bottom: 16px;
}

.hero-title {
  font-size: clamp(1.9rem, 2.5vw, 2.8rem);
  line-height: 1.08;
  font-weight: 800;
  margin-bottom: 12px;
}

.hero-copy {
  max-width: 640px;
  color: rgba(255, 255, 255, 0.82);
  font-size: 15px;
}

.hero-chip-row {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.hero-chip {
  display: inline-flex;
  align-items: center;
  border-radius: 999px;
  padding: 8px 12px;
  background: rgba(255, 255, 255, 0.1);
  border: 1px solid rgba(255, 255, 255, 0.14);
  font-size: 12px;
  font-weight: 700;
}

.hero-controls {
  display: grid;
  gap: 16px;
}

.period-switch {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 8px;
  padding: 8px;
  border-radius: 18px;
  background: rgba(255, 255, 255, 0.1);
}

.period-btn {
  border: 0;
  border-radius: 12px;
  min-height: 44px;
  color: rgba(255, 255, 255, 0.82);
  background: transparent;
  font-weight: 700;
}

.period-btn.active {
  background: #fff;
  color: var(--inventory-brand-deep);
}

.hero-health-card {
  padding: 18px;
  border-radius: 22px;
  background: rgba(255, 255, 255, 0.12);
  border: 1px solid rgba(255, 255, 255, 0.12);
}

.hero-health-label {
  display: block;
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: rgba(255, 255, 255, 0.72);
  margin-bottom: 8px;
}

.hero-health-card strong {
  display: block;
  font-size: 1.35rem;
  font-weight: 800;
  margin-bottom: 6px;
}

.hero-health-card p {
  color: rgba(255, 255, 255, 0.78);
  font-size: 13px;
}

.metric-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 16px;
}

.metric-card {
  display: grid;
  grid-template-columns: auto 1fr;
  gap: 14px;
  padding: 18px;
  border-radius: 22px;
  border: 1px solid #e6ebf4;
  background: #fff;
  box-shadow: 0 12px 30px rgba(15, 23, 42, 0.04);
}

.metric-icon {
  width: 48px;
  height: 48px;
  border-radius: 16px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
}

.metric-card.brand .metric-icon {
  background: rgba(75, 91, 147, 0.12);
  color: var(--inventory-brand);
}

.metric-card.success .metric-icon {
  background: rgba(15, 118, 110, 0.12);
  color: #0f766e;
}

.metric-card.info .metric-icon {
  background: rgba(8, 145, 178, 0.12);
  color: #0891b2;
}

.metric-card.warning .metric-icon {
  background: rgba(245, 158, 11, 0.14);
  color: #b45309;
}

.metric-label {
  display: block;
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: #64748b;
  margin-bottom: 6px;
}

.metric-value {
  display: block;
  color: #182032;
  font-size: 1.2rem;
  font-weight: 800;
  margin-bottom: 4px;
}

.metric-note {
  color: #64748b;
  font-size: 13px;
}

.panel-card {
  border-radius: 24px;
}

.panel-header {
  border-bottom: 1px solid #eef2f7;
  background: transparent;
  padding: 1.25rem 1.25rem 0.9rem;
}

.panel-kicker {
  color: var(--inventory-brand);
  margin-bottom: 8px;
}

.panel-title {
  color: #182032;
  font-weight: 800;
}

.panel-copy {
  color: #64748b;
  font-size: 13px;
}

.latest-table-wrap {
  border-top: 1px solid #eef2f7;
}

.latest-table thead th {
  background: linear-gradient(180deg, #f8faff 0%, #edf1fb 100%);
  color: #24324b;
  font-weight: 800;
  font-size: 12px;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  white-space: nowrap;
}

.movement-stack {
  display: grid;
  gap: 14px;
}

.movement-card {
  display: grid;
  grid-template-columns: auto 1fr;
  gap: 14px;
  align-items: start;
  padding: 16px;
  border-radius: 20px;
  border: 1px solid #e6ebf4;
  background: #fbfcff;
}

.movement-icon {
  width: 46px;
  height: 46px;
  border-radius: 16px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
}

.movement-icon.success {
  background: rgba(15, 118, 110, 0.12);
  color: #0f766e;
}

.movement-icon.warning {
  background: rgba(245, 158, 11, 0.14);
  color: #b45309;
}

.movement-icon.danger {
  background: rgba(220, 38, 38, 0.12);
  color: #dc2626;
}

.movement-label {
  display: block;
  color: #64748b;
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  margin-bottom: 6px;
}

.movement-card strong {
  display: block;
  color: #182032;
  font-size: 1.15rem;
  font-weight: 800;
  margin-bottom: 4px;
}

.movement-card p {
  color: #64748b;
  font-size: 13px;
}

@media (max-width: 1199.98px) {
  .metric-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 991.98px) {
  .hero-grid,
  .metric-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 575.98px) {
  .period-switch {
    grid-template-columns: 1fr;
  }

  .hero-title {
    font-size: 1.75rem;
  }
}
</style>
