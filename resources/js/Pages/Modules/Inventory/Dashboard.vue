<template>
  <div class="inventory-dashboard-page">
    <Head title="Inventory Dashboard" />
    <PageHeader title="Inventory Dashboard" pageTitle="Overview" />

    <!-- Enhanced Hero -->
    <section class="idash-hero mb-4">
      <!-- Decorative background -->
      <div class="idash-deco" aria-hidden="true">
        <div class="idash-deco-ring idash-deco-ring--1"></div>
        <div class="idash-deco-ring idash-deco-ring--2"></div>
        <div class="idash-deco-ring idash-deco-ring--3"></div>
        <div class="idash-deco-grid"></div>
      </div>

      <div class="idash-hero-body">
        <!-- Left: identity + health -->
        <div class="idash-hero-left">
          <div class="idash-kicker">
            <i class="ri-bar-chart-box-line"></i>
            <span>Inventory Analytics</span>
          </div>
          <h1 class="idash-title">
            Real-time<br /><em>Stock Intelligence.</em>
          </h1>
          <p class="idash-desc">
            Track category volumes, catch low-stock signals, and monitor movement — all in one dashboard.
          </p>

          <!-- Period selector inline -->
          <div class="idash-period-bar">
            <span class="idash-period-label"><i class="ri-calendar-2-line me-1"></i>Viewing</span>
            <select class="idash-period-select" :value="selectedPeriod" @change="changePeriod($event.target.value)">
              <option value="monthly">Monthly</option>
              <option value="quarterly">Quarterly</option>
              <option value="yearly">Yearly</option>
            </select>
            <span class="idash-period-range">{{ formattedRange }}</span>
          </div>

          <!-- Health indicator -->
          <div class="idash-health" :class="healthTone">
            <div class="idash-health-dot"></div>
            <div>
              <strong class="idash-health-label">{{ stockHealthLabel }}</strong>
              <span class="idash-health-copy">{{ stockHealthCopy }}</span>
            </div>
          </div>
        </div>

        <!-- Right: stat cards 2×3 -->
        <div class="idash-stat-grid">
          <div
            v-for="card in summaryCards"
            :key="card.label"
            class="idash-stat"
            :style="{ '--sa': card.accent }"
          >
            <div class="idash-stat-icon"><i :class="card.icon"></i></div>
            <strong class="idash-stat-val">{{ card.value }}</strong>
            <span class="idash-stat-lbl">{{ card.label }}</span>
            <p class="idash-stat-note">{{ card.note }}</p>
          </div>
        </div>
      </div>
    </section>

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
                    <th>Item Name</th>
                    <th>Category</th>
                    <th class="text-center">Stock Entries</th>
                    <th class="text-end">Total Qty</th>
                    <th class="text-end">Unit Cost</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="recent.length === 0">
                    <td colspan="6" class="text-center text-muted py-5">No inventory items found for this range.</td>
                  </tr>
                  <tr v-else v-for="row in recent" :key="row.id">
                    <td class="fw-semibold font-monospace">{{ row.code }}</td>
                    <td>{{ row.item_name }}</td>
                    <td><span class="badge bg-primary-subtle text-primary">{{ row.category }}</span></td>
                    <td class="text-center">{{ row.stock_count }}</td>
                    <td class="text-end fw-bold">{{ formatNumber(row.total_quantity) }}</td>
                    <td class="text-end text-muted">{{ formatCurrency(row.unit_cost) }}</td>
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
      validPeriods: ['weekly', 'monthly', 'quarterly', 'annually'],
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
    healthTone() {
      if (this.outOfStock > 0) return 'danger';
      if (this.lowStockItems > 0) return 'warning';
      return 'good';
    },
    summaryCards() {
      return [
        {
          label: 'Total Stocks',
          value: this.formatNumber(this.totalStocks),
          note: 'Stock entries tracked',
          icon: 'ri-archive-stack-line',
          accent: '#60a5fa',
        },
        {
          label: 'Tracked Items',
          value: this.formatNumber(this.totalItems),
          note: 'Active item records',
          icon: 'ri-cube-line',
          accent: '#34d399',
        },
        {
          label: 'Units On Hand',
          value: this.formatNumber(this.totalQuantity),
          note: 'Combined qty in period',
          icon: 'ri-database-2-line',
          accent: '#38bdf8',
        },
        {
          label: 'Low Stock',
          value: this.formatNumber(this.lowStockItems),
          note: 'Items near depletion',
          icon: 'ri-alarm-warning-line',
          accent: '#fb923c',
        },
        {
          label: 'Receivings',
          value: this.formatNumber(this.receivingsCount),
          note: 'Logged in this period',
          icon: 'ri-inbox-archive-line',
          accent: '#a78bfa',
        },
        {
          label: 'Withdrawals',
          value: this.formatNumber(this.withdrawalsCount),
          note: 'Released in this period',
          icon: 'ri-shopping-cart-line',
          accent: '#f472b6',
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
/* ═══════════════════════════════════════════
   INVENTORY DASHBOARD — DESIGN SYSTEM
   ═══════════════════════════════════════════ */
.inventory-dashboard-page {
  --inventory-brand: #4b5b93;
  --inventory-brand-deep: #38467a;
}

/* ── Hero ───────────────────────────────────────── */
.idash-hero {
  position: relative;
  border-radius: 24px;
  overflow: hidden;
  background:
    radial-gradient(ellipse at 85% -15%, rgba(96,165,250,.35) 0%, transparent 45%),
    radial-gradient(ellipse at -5% 95%, rgba(167,139,250,.2) 0%, transparent 42%),
    linear-gradient(135deg, #1a2a68 0%, #2a3d8c 38%, #38467a 68%, #4b5b93 100%);
  box-shadow: 0 28px 64px rgba(26,42,104,.35);
  color: #fff;
  margin-bottom: 0;
}

/* Decorative rings + grid */
.idash-deco { position: absolute; inset: 0; pointer-events: none; overflow: hidden; }
.idash-deco-ring {
  position: absolute;
  border-radius: 50%;
  border: 1px solid rgba(255,255,255,.06);
}
.idash-deco-ring--1 { width: 500px; height: 500px; top: -180px; right: -80px; }
.idash-deco-ring--2 { width: 320px; height: 320px; top: -80px; right: 80px; }
.idash-deco-ring--3 { width: 180px; height: 180px; bottom: -40px; left: 10%; }
.idash-deco-grid {
  position: absolute; inset: 0;
  background-image:
    linear-gradient(rgba(255,255,255,.025) 1px, transparent 1px),
    linear-gradient(90deg, rgba(255,255,255,.025) 1px, transparent 1px);
  background-size: 36px 36px;
}

/* Hero layout */
.idash-hero-body {
  position: relative;
  display: grid;
  grid-template-columns: minmax(280px, 1fr) minmax(0, 1.5fr);
  gap: 2rem;
  align-items: center;
  padding: 1.85rem 2rem;
}

/* Left column */
.idash-kicker {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.28rem 0.75rem;
  border-radius: 999px;
  background: rgba(255,255,255,.1);
  border: 1px solid rgba(255,255,255,.18);
  font-size: 0.67rem;
  font-weight: 800;
  letter-spacing: .12em;
  text-transform: uppercase;
  margin-bottom: 0.85rem;
  color: rgba(255,255,255,.8);
}

.idash-title {
  font-size: clamp(1.8rem, 2.5vw, 2.6rem);
  font-weight: 900;
  line-height: 1.1;
  letter-spacing: -.02em;
  margin: 0 0 0.6rem;
  color: #fff;
}
.idash-title em {
  font-style: normal;
  background: linear-gradient(90deg, #93c5fd, #c4b5fd);
  -webkit-background-clip: text;
  background-clip: text;
  -webkit-text-fill-color: transparent;
}

.idash-desc {
  font-size: 0.88rem;
  line-height: 1.6;
  color: rgba(255,255,255,.62);
  margin: 0 0 1.25rem;
  max-width: 400px;
}

/* Period bar */
.idash-period-bar {
  display: flex;
  align-items: center;
  gap: 0.55rem;
  padding: 0.55rem 0.85rem;
  border-radius: 14px;
  background: rgba(255,255,255,.08);
  border: 1px solid rgba(255,255,255,.12);
  margin-bottom: 0.85rem;
  width: fit-content;
}
.idash-period-label {
  font-size: 0.72rem;
  font-weight: 700;
  color: rgba(255,255,255,.65);
  white-space: nowrap;
}
.idash-period-select {
  border: 1px solid rgba(255,255,255,.25);
  border-radius: 9px;
  background: rgba(255,255,255,.14);
  color: #fff;
  font-size: 0.82rem;
  font-weight: 700;
  padding: 0.25rem 1.8rem 0.25rem 0.6rem;
  appearance: none;
  -webkit-appearance: none;
  cursor: pointer;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='2.5'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right .5rem center;
}
.idash-period-select option { background: #2a3d8c; color: #fff; }
.idash-period-range {
  font-size: 0.72rem;
  color: rgba(255,255,255,.55);
  white-space: nowrap;
}

/* Health indicator */
.idash-health {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  padding: 0.65rem 0.9rem;
  border-radius: 14px;
  background: rgba(255,255,255,.07);
  border: 1px solid rgba(255,255,255,.1);
  width: fit-content;
}
.idash-health-dot {
  width: 10px; height: 10px;
  border-radius: 50%;
  flex-shrink: 0;
}
.idash-health.good  .idash-health-dot { background: #34d399; box-shadow: 0 0 8px #34d39966; }
.idash-health.warning .idash-health-dot { background: #fb923c; box-shadow: 0 0 8px #fb923c66; }
.idash-health.danger  .idash-health-dot { background: #f87171; box-shadow: 0 0 8px #f8717166; }
.idash-health-label {
  display: block;
  font-size: 0.82rem;
  font-weight: 800;
  color: #fff;
  line-height: 1.2;
}
.idash-health-copy {
  display: block;
  font-size: 0.72rem;
  color: rgba(255,255,255,.58);
  line-height: 1.3;
}

/* Stat cards grid 2×3 */
.idash-stat-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 0.65rem;
}

.idash-stat {
  --sa: #60a5fa;
  position: relative;
  display: flex;
  flex-direction: column;
  gap: 0.3rem;
  padding: 0.95rem 1rem 0.8rem;
  border-radius: 18px;
  background: rgba(255,255,255,.07);
  border: 1px solid rgba(255,255,255,.1);
  backdrop-filter: blur(12px);
  overflow: hidden;
  transition: background .2s;
}
.idash-stat:hover { background: rgba(255,255,255,.11); }
.idash-stat::before {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 2px;
  background: var(--sa);
  border-radius: 18px 18px 0 0;
}

.idash-stat-icon {
  width: 34px; height: 34px;
  border-radius: 10px;
  background: color-mix(in srgb, var(--sa) 18%, transparent);
  border: 1px solid color-mix(in srgb, var(--sa) 25%, transparent);
  display: inline-flex; align-items: center; justify-content: center;
  font-size: 0.95rem;
  color: var(--sa);
  margin-bottom: 0.2rem;
}
.idash-stat-val {
  font-size: 1.6rem;
  font-weight: 900;
  color: #fff;
  line-height: 1;
  letter-spacing: -.03em;
}
.idash-stat-lbl {
  font-size: 0.66rem;
  font-weight: 800;
  letter-spacing: .08em;
  text-transform: uppercase;
  color: rgba(255,255,255,.55);
}
.idash-stat-note {
  font-size: 0.72rem;
  color: rgba(255,255,255,.38);
  margin: 0;
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
  .idash-stat-grid { grid-template-columns: repeat(2, minmax(0,1fr)); }
}

@media (max-width: 991.98px) {
  .idash-hero-body { grid-template-columns: 1fr; }
  .idash-stat-grid { grid-template-columns: repeat(3, minmax(0,1fr)); }
}

@media (max-width: 575.98px) {
  .idash-stat-grid { grid-template-columns: repeat(2, minmax(0,1fr)); }
  .idash-title { font-size: 1.7rem; }
}
</style>
