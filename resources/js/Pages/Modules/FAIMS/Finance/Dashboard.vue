<template>
  <Head title="Finance Dashboard" />
  <PageHeader title="Finance Dashboard" pageTitle="Overview" />

  <BRow class="mb-4" data-aos="fade-down">
    <BCol xl="12">
      <BCard class="finance-hero border-0 glass-hero">
        <BCardBody>
          <div class="d-flex flex-column flex-xl-row gap-3 align-items-xl-center justify-content-between">
            <div>
              <p class="text-uppercase fw-semibold text-white-50 mb-1 shimmer-text">Finance Overview</p>
              <h3 class="text-white mb-2 display-6 fw-bold">Track collections, disbursements, and cash position.</h3>
              <div class="d-flex flex-wrap gap-2">
                <span class="badge glass-badge text-primary fw-semibold px-3 py-2">Collections PHP {{ compactNumber(dashboard.total_collections) }}</span>
                <span class="badge glass-badge text-success fw-semibold px-3 py-2">Deposits PHP {{ compactNumber(dashboard.total_deposits) }}</span>
                <span class="badge glass-badge text-warning fw-semibold px-3 py-2">Pending PHP {{ compactNumber(dashboard.pending_payments) }}</span>
                <span class="badge glass-badge text-info fw-semibold px-3 py-2">Net PHP {{ compactNumber(dashboard.net_balance) }}</span>
              </div>
            </div>

            <div class="d-flex flex-column gap-2 align-items-xl-end">
              <BButtonGroup size="sm" class="period-switcher">
                <BButton v-for="period in periodOptions" :key="period.value" :variant="selectedPeriod === period.value ? 'light' : 'outline-light'" @click="setPeriod(period.value)">
                  {{ period.label }}
                </BButton>
              </BButtonGroup>
              <div class="d-flex flex-wrap gap-2">
                <Link href="/faims/finance-requests" class="btn btn-light btn-soft btn-glow btn-sm"><i class="ri-add-circle-line me-1"></i> New Request</Link>
                <Link href="/faims/finance-disbursements-obligations" class="btn btn-outline-light btn-glow btn-sm"><i class="ri-file-list-3-line me-1"></i> Disbursements</Link>
              </div>
            </div>
          </div>
          <p v-if="lastUpdated" class="text-white-50 mb-0 mt-3 fs-12">Last updated: {{ formatDateTime(lastUpdated) }}</p>
        </BCardBody>
      </BCard>
    </BCol>
  </BRow>

  <BRow class="mb-3 dashboard-section" data-aos="fade-up">
    <BCol xl="12">
      <div class="section-header">
        <div>
          <p class="section-kicker">At a Glance</p>
          <h4 class="section-title">Core Finance Metrics</h4>
        </div>
        <span class="section-meta">Window: {{ selectedPeriodLabel }}</span>
      </div>
    </BCol>
  </BRow>

  <BRow class="mb-4">
    <BCol xl="3" md="6">
      <BCard class="metric-card glass-card card-animate"><BCardBody>
        <p class="text-uppercase fw-medium text-muted text-truncate mb-0">Total Collections</p>
        <h4 class="fs-22 fw-semibold mb-1 neon-text">PHP {{ formatCurrency(dashboard.total_collections) }}</h4>
        <p class="metric-delta mb-0" :class="deltaClass(metricDelta.collections)">{{ formatDelta(metricDelta.collections) }} vs previous window</p>
      </BCardBody></BCard>
    </BCol>
    <BCol xl="3" md="6">
      <BCard class="metric-card glass-card card-animate"><BCardBody>
        <p class="text-uppercase fw-medium text-muted text-truncate mb-0">Total Deposits</p>
        <h4 class="fs-22 fw-semibold mb-1 neon-text">PHP {{ formatCurrency(dashboard.total_deposits) }}</h4>
        <p class="metric-delta mb-0" :class="deltaClass(metricDelta.deposits)">{{ formatDelta(metricDelta.deposits) }} vs previous window</p>
      </BCardBody></BCard>
    </BCol>
    <BCol xl="3" md="6">
      <BCard class="metric-card glass-card card-animate"><BCardBody>
        <p class="text-uppercase fw-medium text-muted text-truncate mb-0">Pending Payments</p>
        <h4 class="fs-22 fw-semibold mb-1 neon-text">PHP {{ formatCurrency(dashboard.pending_payments) }}</h4>
        <p class="metric-delta mb-0" :class="deltaClass(-metricDelta.pending)">{{ formatDelta(metricDelta.pending * -1) }} health impact</p>
      </BCardBody></BCard>
    </BCol>
    <BCol xl="3" md="6">
      <BCard class="metric-card glass-card card-animate"><BCardBody>
        <p class="text-uppercase fw-medium text-muted text-truncate mb-0">Net Balance</p>
        <h4 class="fs-22 fw-semibold mb-1 neon-text">PHP {{ formatCurrency(dashboard.net_balance) }}</h4>
        <p class="metric-delta mb-0" :class="deltaClass(metricDelta.net)">{{ formatDelta(metricDelta.net) }} vs previous window</p>
      </BCardBody></BCard>
    </BCol>
  </BRow>

  <BRow class="mb-4">
    <BCol xl="12">
      <BCard class="glass-card panel-card"><BCardBody>
        <BRow class="g-3">
          <BCol md="4"><p class="ratio-label mb-2">Deposit Conversion</p><div class="ratio-track"><div class="ratio-fill ratio-fill-success" :style="{ width: depositConversion + '%' }"></div></div><p class="ratio-value mb-0">{{ depositConversion }}% of collections were deposited</p></BCol>
          <BCol md="4"><p class="ratio-label mb-2">Disbursement Coverage</p><div class="ratio-track"><div class="ratio-fill ratio-fill-info" :style="{ width: disbursementCoverage + '%' }"></div></div><p class="ratio-value mb-0">{{ disbursementCoverage }}% funded by available balance</p></BCol>
          <BCol md="4"><p class="ratio-label mb-2">Pending Load</p><div class="ratio-track"><div class="ratio-fill ratio-fill-warning" :style="{ width: pendingLoad + '%' }"></div></div><p class="ratio-value mb-0">{{ pendingLoad }}% of collections are still pending</p></BCol>
        </BRow>
      </BCardBody></BCard>
    </BCol>
  </BRow>
  <BRow class="mb-4" data-aos="fade-left">
    <BCol xl="8">
      <BCard class="panel-card glass-card"><BCardHeader><h4 class="card-title mb-0">Monthly Cash Flow</h4></BCardHeader>
        <BCardBody><apexchart type="area" height="360" :options="cashflowChartOptions" :series="cashflowChartSeries"></apexchart></BCardBody>
      </BCard>
    </BCol>
    <BCol xl="4">
      <BCard class="panel-card glass-card"><BCardHeader><h4 class="card-title mb-0">Collection Types</h4></BCardHeader>
        <BCardBody>
          <apexchart v-if="collectionMixSeries.length" type="donut" height="280" :options="collectionMixOptions" :series="collectionMixSeries"></apexchart>
          <div class="mix-breakdown mt-3">
            <div class="mix-item" v-for="(item, index) in collectionMixBreakdown" :key="item.name">
              <div class="d-flex align-items-center gap-2"><span class="mix-dot" :style="{ backgroundColor: collectionMixOptions.colors[index] }"></span><span class="text-muted fs-13">{{ item.name }}</span></div>
              <div class="text-end"><p class="mb-0 fw-semibold fs-13">PHP {{ formatCurrency(item.amount) }}</p><p class="mb-0 text-muted fs-12">{{ item.share }}%</p></div>
            </div>
          </div>
        </BCardBody>
      </BCard>
    </BCol>
  </BRow>

  <BRow class="mb-4 dashboard-section" data-aos="fade-right">
    <BCol xl="7">
      <BCard class="panel-card glass-card"><BCardHeader class="d-flex align-items-center justify-content-between"><h4 class="card-title mb-0">Recent Receipts</h4><Link href="/faims/finance-requests" class="btn btn-sm btn-outline-primary btn-glow">View All</Link></BCardHeader>
        <BCardBody class="pt-0">
          <div class="table-responsive table-card">
            <table class="table align-middle table-hover mb-0 neon-table">
              <thead class="table-light"><tr class="fs-12 fw-semibold"><th>OR Number</th><th>Payer</th><th>Type</th><th>Amount</th><th>Date</th></tr></thead>
              <tbody class="table-group-divider">
                <tr v-for="item in recentReceipts" :key="item.or_number" class="table-glow-row">
                  <td class="fw-semibold text-primary">{{ item.or_number }}</td><td>{{ item.payer }}</td><td><span class="badge bg-light text-dark border">{{ item.type }}</span></td><td class="fw-bold text-success">PHP {{ formatCurrency(item.amount) }}</td><td class="text-muted">{{ formatDate(item.date) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </BCardBody>
      </BCard>
    </BCol>

    <BCol xl="5">
      <BCard class="insights-card glass-card panel-card"><BCardHeader><h4 class="card-title mb-0">Insights</h4></BCardHeader>
        <BCardBody>
          <div class="insight-item mb-4"><p class="text-muted mb-1">Cash on Hand</p><h4 class="mb-0 neon-text">PHP {{ formatCurrency(dashboard.cash_on_hand) }}</h4><p class="text-muted mb-0 fs-12">After disbursements</p><div class="radial-progress mt-3" :style="{ '--progress': cashHealth + '%' }"><span>{{ cashHealth }}%</span></div></div>
          <div class="insight-item mb-4"><p class="text-muted mb-1">Top Collection Type</p><h4 class="mb-0">{{ topCollectionType.name }}</h4><p class="text-muted mb-0 fs-12">PHP {{ formatCurrency(topCollectionType.amount) }}</p></div>
          <div class="insight-item"><p class="text-muted mb-1">Collection Efficiency</p><h4 class="mb-0 neon-text">{{ collectionEfficiency }}%</h4><p class="text-muted mb-0 fs-12">Net retained from collections</p></div>
        </BCardBody>
      </BCard>
    </BCol>
  </BRow>
</template>

<script>
import { Head, Link } from '@inertiajs/vue3';
import PageHeader from '@/Shared/Components/PageHeader.vue';
import VueApexCharts from 'vue3-apexcharts';

const PERIOD_LENGTH = { last3: 3, last6: 6, ytd: 12 };

export default {
  components: { Head, Link, PageHeader, apexchart: VueApexCharts },
  props: { dropdowns: Object },
  data() {
    return {
      selectedPeriod: 'last6',
      lastUpdated: null,
      periodOptions: [{ label: '3M', value: 'last3' }, { label: '6M', value: 'last6' }, { label: '12M', value: 'ytd' }],
      monthlySnapshots: [
        { month: 'Jan', collections: 421000, deposits: 340000, pending: 51000, disbursements: 220000, receipts: 183000, oop: 74000 },
        { month: 'Feb', collections: 468000, deposits: 386000, pending: 57000, disbursements: 245000, receipts: 201000, oop: 79000 },
        { month: 'Mar', collections: 495000, deposits: 401000, pending: 63000, disbursements: 252000, receipts: 215000, oop: 83000 },
        { month: 'Apr', collections: 510000, deposits: 418000, pending: 59000, disbursements: 270000, receipts: 224000, oop: 86000 },
        { month: 'May', collections: 532000, deposits: 441000, pending: 64000, disbursements: 278000, receipts: 238000, oop: 89000 },
        { month: 'Jun', collections: 548000, deposits: 456000, pending: 68000, disbursements: 291000, receipts: 246000, oop: 92000 },
        { month: 'Jul', collections: 570000, deposits: 479000, pending: 72000, disbursements: 303000, receipts: 259000, oop: 96000 },
        { month: 'Aug', collections: 590000, deposits: 495000, pending: 69000, disbursements: 312000, receipts: 266000, oop: 99000 },
        { month: 'Sep', collections: 612000, deposits: 512000, pending: 74000, disbursements: 326000, receipts: 274000, oop: 104000 },
        { month: 'Oct', collections: 634000, deposits: 529000, pending: 78000, disbursements: 341000, receipts: 289000, oop: 109000 },
        { month: 'Nov', collections: 652000, deposits: 541000, pending: 81000, disbursements: 354000, receipts: 301000, oop: 113000 },
        { month: 'Dec', collections: 676000, deposits: 562000, pending: 85000, disbursements: 372000, receipts: 318000, oop: 118000 }
      ],
      recentReceipts: [
        { or_number: 'OR-2026-1098', payer: 'Metro Supply Co.', type: 'Services', amount: 54000, date: '2026-03-21' },
        { or_number: 'OR-2026-1097', payer: 'Grandview Trading', type: 'Fees', amount: 38000, date: '2026-03-20' },
        { or_number: 'OR-2026-1096', payer: 'Apex Logistics', type: 'Services', amount: 27000, date: '2026-03-19' },
        { or_number: 'OR-2026-1095', payer: 'Northline Construction', type: 'Refunds', amount: 16500, date: '2026-03-17' },
        { or_number: 'OR-2026-1094', payer: 'Island Utilities', type: 'Others', amount: 12000, date: '2026-03-16' }
      ],
      dashboard: { total_collections: 0, total_deposits: 0, pending_payments: 0, net_balance: 0, total_receipts: 0, total_oop: 0, cash_on_hand: 0, total_disbursements: 0 },
      previousDashboard: { total_collections: 0, total_deposits: 0, pending_payments: 0, net_balance: 0 },
      collectionMixOptions: { chart: { type: 'donut', height: 280, background: 'transparent' }, labels: ['Fees', 'Services', 'Refunds', 'Others'], legend: { show: false }, colors: ['#00b894', '#0984e3', '#e17055', '#6c5ce7'], stroke: { width: 0 } },
      collectionMixSeries: [],
      cashflowChartOptions: { chart: { type: 'area', height: 360, toolbar: { show: false }, background: 'transparent' }, dataLabels: { enabled: false }, stroke: { curve: 'smooth', width: 3 }, colors: ['#00b894', '#e17055'], xaxis: { categories: [] }, grid: { borderColor: 'rgba(148, 163, 184, 0.2)', strokeDashArray: 4 } },
      cashflowChartSeries: [{ name: 'Collections', data: [] }, { name: 'Disbursements', data: [] }]
    };
  },
  computed: {
    selectedPeriodLabel() {
      const selected = this.periodOptions.find((item) => item.value === this.selectedPeriod);
      return selected ? selected.label : '6M';
    },
    metricDelta() {
      return {
        collections: this.percentDiff(this.dashboard.total_collections, this.previousDashboard.total_collections),
        deposits: this.percentDiff(this.dashboard.total_deposits, this.previousDashboard.total_deposits),
        pending: this.percentDiff(this.dashboard.pending_payments, this.previousDashboard.pending_payments),
        net: this.percentDiff(this.dashboard.net_balance, this.previousDashboard.net_balance)
      };
    },
    cashHealth() { return this.clampPercent((this.dashboard.net_balance / (this.dashboard.total_collections || 1)) * 100); },
    collectionEfficiency() { return this.clampPercent((this.dashboard.net_balance / (this.dashboard.total_collections || 1)) * 100); },
    topCollectionType() {
      if (!this.collectionMixSeries.length) return { name: 'N/A', amount: 0 };
      const max = Math.max(...this.collectionMixSeries);
      const maxIndex = this.collectionMixSeries.indexOf(max);
      return { name: this.collectionMixOptions.labels[maxIndex] || 'N/A', amount: this.collectionMixSeries[maxIndex] || 0 };
    },
    collectionMixBreakdown() {
      const total = this.collectionMixSeries.reduce((sum, value) => sum + value, 0) || 1;
      return this.collectionMixOptions.labels.map((label, index) => ({ name: label, amount: this.collectionMixSeries[index] || 0, share: this.clampPercent(((this.collectionMixSeries[index] || 0) / total) * 100) }));
    },
    depositConversion() { return this.clampPercent((this.dashboard.total_deposits / (this.dashboard.total_collections || 1)) * 100); },
    disbursementCoverage() { return this.clampPercent((this.dashboard.cash_on_hand / (this.dashboard.total_disbursements || 1)) * 100); },
    pendingLoad() { return this.clampPercent((this.dashboard.pending_payments / (this.dashboard.total_collections || 1)) * 100); }
  },
  mounted() {
    this.rebuildDashboard();
    this.lastUpdated = new Date();
  },
  methods: {
    setPeriod(period) {
      this.selectedPeriod = period;
      this.rebuildDashboard();
      this.lastUpdated = new Date();
    },
    getWindow(length, offset = 0) {
      const end = this.monthlySnapshots.length - offset;
      const start = Math.max(0, end - length);
      return this.monthlySnapshots.slice(start, end);
    },
    summarize(windowData) {
      return windowData.reduce((acc, item) => {
        acc.total_collections += item.collections;
        acc.total_deposits += item.deposits;
        acc.pending_payments += item.pending;
        acc.total_disbursements += item.disbursements;
        acc.total_receipts += item.receipts;
        acc.total_oop += item.oop;
        return acc;
      }, { total_collections: 0, total_deposits: 0, pending_payments: 0, total_disbursements: 0, total_receipts: 0, total_oop: 0 });
    },
    rebuildDashboard() {
      const length = PERIOD_LENGTH[this.selectedPeriod] || PERIOD_LENGTH.last6;
      const currentWindow = this.getWindow(length);
      const previousWindow = this.getWindow(length, length);
      const current = this.summarize(currentWindow);
      const previous = this.summarize(previousWindow.length ? previousWindow : currentWindow);

      this.dashboard = { ...current, cash_on_hand: Math.max(0, current.total_deposits - current.total_disbursements), net_balance: Math.max(0, current.total_collections - current.total_disbursements - current.pending_payments) };
      this.previousDashboard = { ...previous, net_balance: Math.max(0, previous.total_collections - previous.total_disbursements - previous.pending_payments) };

      this.cashflowChartOptions = { ...this.cashflowChartOptions, xaxis: { ...this.cashflowChartOptions.xaxis, categories: currentWindow.map((item) => item.month) } };
      this.cashflowChartSeries = [{ name: 'Collections', data: currentWindow.map((item) => item.collections) }, { name: 'Disbursements', data: currentWindow.map((item) => item.disbursements) }];

      const total = this.dashboard.total_collections || 1;
      const fees = Math.round(total * 0.34);
      const services = Math.round(total * 0.38);
      const refunds = Math.round(total * 0.16);
      const others = Math.max(0, total - fees - services - refunds);
      this.collectionMixSeries = [fees, services, refunds, others];
    },
    clampPercent(value) { return Math.max(0, Math.min(100, Math.round(value))); },
    percentDiff(current, previous) {
      const base = Number(previous) || 0;
      if (base === 0) return current > 0 ? 100 : 0;
      return Math.round(((current - previous) / base) * 100);
    },
    deltaClass(value) { return value > 0 ? 'delta-positive' : value < 0 ? 'delta-negative' : 'delta-neutral'; },
    formatDelta(value) { return value > 0 ? `+${value}%` : `${value}%`; },
    compactNumber(value) { return new Intl.NumberFormat('en-US', { notation: 'compact', maximumFractionDigits: 1 }).format(value || 0); },
    formatCurrency(value) { return new Intl.NumberFormat('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(value || 0); },
    formatDateTime(date) { return new Date(date).toLocaleString('en-US', { year: 'numeric', month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' }); },
    formatDate(date) { return new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' }); }
  }
};
</script>

<style scoped>
.finance-hero { position: relative; overflow: hidden; border-radius: 22px; box-shadow: 0 20px 45px -16px rgba(11, 18, 32, 0.45); }
.finance-hero::before { content: ''; position: absolute; inset: 0; background: radial-gradient(circle at 20% 80%, rgba(16, 185, 129, 0.25) 0%, transparent 55%), radial-gradient(circle at 80% 20%, rgba(59, 130, 246, 0.2) 0%, transparent 45%); pointer-events: none; z-index: 0; }
.glass-hero { background: linear-gradient(130deg, #0f172a 0%, #134e4a 42%, #1d4ed8 100%); }
.glass-card { background: rgba(255, 255, 255, 0.14) !important; backdrop-filter: blur(16px) saturate(140%) !important; border: 1px solid rgba(255, 255, 255, 0.24) !important; border-radius: 18px !important; }
.glass-badge { background: rgba(255, 255, 255, 0.2) !important; border: 1px solid rgba(255, 255, 255, 0.28); }
.period-switcher .btn { min-width: 52px; }
.card-animate { transition: transform 0.25s ease, box-shadow 0.25s ease; }
.card-animate:hover { transform: translateY(-4px); box-shadow: 0 16px 30px rgba(15, 23, 42, 0.18) !important; }
.neon-text { background: linear-gradient(35deg, #047857, #0369a1); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
.metric-delta { font-size: 12px; font-weight: 600; }
.delta-positive { color: #059669; }
.delta-negative { color: #dc2626; }
.delta-neutral { color: #64748b; }
.section-kicker { text-transform: uppercase; letter-spacing: 0.15em; font-size: 11px; font-weight: 700; color: #64748b; margin-bottom: 6px; }
.section-title { font-family: "Montserrat", sans-serif; font-size: 1.35rem; font-weight: 800; margin: 0; color: #0f172a; }
.section-meta { font-size: 12px; color: #64748b; font-weight: 600; }
.ratio-label { font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b; font-weight: 700; }
.ratio-track { background: rgba(148, 163, 184, 0.25); border-radius: 999px; height: 8px; overflow: hidden; }
.ratio-fill { height: 100%; border-radius: 999px; transition: width 0.3s ease; }
.ratio-fill-success { background: linear-gradient(90deg, #10b981, #34d399); }
.ratio-fill-info { background: linear-gradient(90deg, #0284c7, #38bdf8); }
.ratio-fill-warning { background: linear-gradient(90deg, #f59e0b, #fbbf24); }
.ratio-value { font-size: 12px; color: #475569; margin-top: 6px; }
.mix-breakdown { display: flex; flex-direction: column; gap: 8px; }
.mix-item { display: flex; align-items: center; justify-content: space-between; padding: 8px 10px; border-radius: 10px; background: rgba(248, 250, 252, 0.72); }
.mix-dot { width: 9px; height: 9px; border-radius: 50%; display: inline-block; }
.neon-table tbody tr:hover { background: rgba(16, 185, 129, 0.1) !important; }
.insight-item { padding: 18px; border-radius: 14px; border: 1px solid rgba(148, 163, 184, 0.2); background: rgba(255, 255, 255, 0.24); }
.radial-progress { width: 78px; height: 78px; border-radius: 50%; background: conic-gradient(#10b981 var(--progress), #e2e8f0 0); display: flex; align-items: center; justify-content: center; font-weight: 700; color: #047857; position: relative; }
.radial-progress::before { content: ''; width: 56px; height: 56px; border-radius: 50%; background: #ffffff; position: absolute; }
.radial-progress span { position: relative; z-index: 1; font-size: 13px; }
.shimmer-text { background: linear-gradient(90deg, #ffffff 0%, #d1fae5 45%, #ffffff 100%); background-size: 200% 100%; animation: shimmer 2s infinite linear; -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
@keyframes shimmer { 0% { background-position: -200% 0; } 100% { background-position: 200% 0; } }
@media (max-width: 768px) { .section-title { font-size: 1.2rem; } .metric-card h4 { font-size: 1.35rem !important; } }
</style>
