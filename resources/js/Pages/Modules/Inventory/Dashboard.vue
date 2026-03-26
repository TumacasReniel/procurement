<template>
  <div>
    <Head title="Inventory Dashboard" />
    <PageHeader title="Inventory Dashboard" pageTitle="Overview" />

    <div class="card border-0 glass-hero mb-4">
      <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
          <div>
            <h4 class="mb-1 text-white">Inventory Dashboard (Demo)</h4>
            <p class="text-white-50 mb-0">Static demo data for Monthly, Quarterly, and Yearly views.</p>
            <small class="text-white-50">Range: {{ currentData.range }}</small>
          </div>
          <div class="btn-group" role="group">
            <button class="btn btn-sm px-3" :class="selectedPeriod === 'monthly' ? 'btn-light' : 'btn-outline-light'" @click="selectedPeriod = 'monthly'">Monthly</button>
            <button class="btn btn-sm px-3" :class="selectedPeriod === 'quarterly' ? 'btn-light' : 'btn-outline-light'" @click="selectedPeriod = 'quarterly'">Quarterly</button>
            <button class="btn btn-sm px-3" :class="selectedPeriod === 'yearly' ? 'btn-light' : 'btn-outline-light'" @click="selectedPeriod = 'yearly'">Yearly</button>
          </div>
        </div>
      </div>
    </div>

    <div class="row mb-4">
      <div class="col-xl-3 col-sm-6 mb-3">
        <div class="card h-100 border-0 shadow-sm card-hover"><div class="card-body text-center pt-4">
          <div class="avatar-sm mx-auto mb-3"><div class="avatar-title bg-primary text-white rounded-circle fs-3"><i class="ri-stack-line"></i></div></div>
          <h4 class="mb-1">{{ formatNumber(currentData.stats.totalItems) }}</h4><p class="text-muted mb-0 small">Total Items</p>
        </div></div>
      </div>
      <div class="col-xl-3 col-sm-6 mb-3">
        <div class="card h-100 border-0 shadow-sm card-hover"><div class="card-body text-center pt-4">
          <div class="avatar-sm mx-auto mb-3"><div class="avatar-title bg-success text-white rounded-circle fs-3"><i class="ri-cube-line"></i></div></div>
          <h4 class="mb-1">{{ formatNumber(currentData.stats.totalQuantity) }}</h4><p class="text-muted mb-0 small">Total Quantity</p>
        </div></div>
      </div>
      <div class="col-xl-3 col-sm-6 mb-3">
        <div class="card h-100 border-0 shadow-sm card-hover"><div class="card-body text-center pt-4">
          <div class="avatar-sm mx-auto mb-3"><div class="avatar-title bg-warning text-white rounded-circle fs-3"><i class="ri-alert-line"></i></div></div>
          <h4 class="mb-1 text-warning">{{ formatNumber(currentData.stats.lowStockItems) }}</h4><p class="text-muted mb-0 small">Low Stock Alerts</p>
        </div></div>
      </div>
      <div class="col-xl-3 col-sm-6 mb-3">
        <div class="card h-100 border-0 shadow-sm card-hover"><div class="card-body text-center pt-4">
          <div class="avatar-sm mx-auto mb-3"><div class="avatar-title bg-danger text-white rounded-circle fs-3"><i class="ri-close-circle-line"></i></div></div>
          <h4 class="mb-1 text-danger">{{ formatNumber(currentData.stats.outOfStock) }}</h4><p class="text-muted mb-0 small">Out of Stock</p>
        </div></div>
      </div>
    </div>

    <div class="row mb-4">
      <div class="col-xl-8 mb-3">
        <div class="card h-100">
          <div class="card-header"><h5 class="card-title mb-0">Stock by Category</h5></div>
          <div class="card-body">
            <apexchart type="bar" height="320" :options="categoryChartOptions" :series="categorySeries" />
          </div>
        </div>
      </div>
      <div class="col-xl-4 mb-3">
        <div class="card h-100">
          <div class="card-header"><h5 class="card-title mb-0">Stock Status</h5></div>
          <div class="card-body">
            <apexchart type="donut" height="320" :options="statusChartOptions" :series="statusSeries" />
          </div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><h5 class="card-title mb-0">Stocks Items</h5></div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead>
              <tr>
                <th>Code</th>
                <th>Item Stock Category</th>
                <th>Item Name</th>
                <th>Unit</th>
                <th class="text-end">Quantity</th>
                <th class="text-end">Unit Cost</th>
                <th>Expiration</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in currentData.recent" :key="row.id">
                <td class="fw-semibold">{{ row.code }}</td>
                <td>{{ row.stock_category }}</td>
                <td>{{ row.item_name }}</td>
                <td>{{ row.unit }}</td>
                <td class="text-end">{{ formatNumber(row.quantity) }}</td>
                <td class="text-end">{{ formatCurrency(row.unit_cost) }}</td>
                <td>{{ row.expiration }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { Head } from '@inertiajs/vue3';
import PageHeader from '@/Shared/Components/PageHeader.vue';
import VueApexCharts from 'vue3-apexcharts';

export default {
  components: { Head, PageHeader, apexchart: VueApexCharts },
  data() {
    return {
      selectedPeriod: 'monthly',
      demoData: {
        monthly: {
          range: 'Current Month',
          stats: { totalItems: 124, totalQuantity: 2456, lowStockItems: 8, outOfStock: 3 },
          byCategory: [
            { name: 'Office Supplies', y: 920 },
            { name: 'Electronics', y: 610 },
            { name: 'Cleaning', y: 480 },
            { name: 'Others', y: 446 },
          ],
          byStatus: { available: 1850, low: 420, out: 186 },
          recent: [
            { id: 1, code: 'STK-000101', stock_category: 'Office Supplies', item_name: 'A4 Paper', unit: 'ream', quantity: 120, unit_cost: 185, expiration: 'N/A' },
            { id: 2, code: 'STK-000102', stock_category: 'Electronics', item_name: 'Ink Cartridge', unit: 'pcs', quantity: 20, unit_cost: 950, expiration: 'Dec 31, 2026' },
            { id: 3, code: 'STK-000103', stock_category: 'Cleaning', item_name: 'Alcohol 70%', unit: 'bottle', quantity: 45, unit_cost: 95, expiration: 'Aug 15, 2026' },
          ],
        },
        quarterly: {
          range: 'Current Quarter',
          stats: { totalItems: 128, totalQuantity: 7320, lowStockItems: 11, outOfStock: 4 },
          byCategory: [
            { name: 'Office Supplies', y: 2800 },
            { name: 'Electronics', y: 1750 },
            { name: 'Cleaning', y: 1320 },
            { name: 'Others', y: 1450 },
          ],
          byStatus: { available: 5660, low: 1160, out: 500 },
          recent: [
            { id: 11, code: 'STK-000211', stock_category: 'Office Supplies', item_name: 'Notebook', unit: 'pcs', quantity: 300, unit_cost: 35, expiration: 'N/A' },
            { id: 12, code: 'STK-000212', stock_category: 'Electronics', item_name: 'Mouse', unit: 'pcs', quantity: 70, unit_cost: 420, expiration: 'N/A' },
            { id: 13, code: 'STK-000213', stock_category: 'Cleaning', item_name: 'Disinfectant', unit: 'gallon', quantity: 30, unit_cost: 320, expiration: 'Nov 20, 2026' },
          ],
        },
        yearly: {
          range: 'Current Year',
          stats: { totalItems: 136, totalQuantity: 29480, lowStockItems: 17, outOfStock: 6 },
          byCategory: [
            { name: 'Office Supplies', y: 10900 },
            { name: 'Electronics', y: 7200 },
            { name: 'Cleaning', y: 5400 },
            { name: 'Others', y: 5980 },
          ],
          byStatus: { available: 22900, low: 4700, out: 1880 },
          recent: [
            { id: 21, code: 'STK-000421', stock_category: 'Office Supplies', item_name: 'Printer Paper', unit: 'ream', quantity: 980, unit_cost: 178, expiration: 'N/A' },
            { id: 22, code: 'STK-000422', stock_category: 'Electronics', item_name: 'Keyboard', unit: 'pcs', quantity: 150, unit_cost: 620, expiration: 'N/A' },
            { id: 23, code: 'STK-000423', stock_category: 'Cleaning', item_name: 'Bleach', unit: 'liter', quantity: 240, unit_cost: 48, expiration: 'Jul 10, 2027' },
          ],
        },
      },
    };
  },
  computed: {
    currentData() {
      return this.demoData[this.selectedPeriod] || this.demoData.monthly;
    },
    categorySeries() {
      return [{ name: 'Quantity', data: this.currentData.byCategory.map((i) => Number(i.y || 0)) }];
    },
    categoryChartOptions() {
      return {
        chart: { type: 'bar', toolbar: { show: false } },
        xaxis: { categories: this.currentData.byCategory.map((i) => i.name) },
        colors: ['#3b82f6'],
        dataLabels: { enabled: false },
      };
    },
    statusSeries() {
      return Object.values(this.currentData.byStatus).map((v) => Number(v || 0));
    },
    statusChartOptions() {
      return {
        labels: Object.keys(this.currentData.byStatus),
        chart: { type: 'donut' },
        colors: ['#36ab87', '#f59f00', '#dc3545', '#3b82f6'],
        legend: { position: 'bottom' },
      };
    },
  },
  methods: {
    formatNumber(num) {
      return new Intl.NumberFormat().format(Number(num || 0));
    },
    formatCurrency(value) {
      const n = Number(value || 0);
      return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(n);
    },
  },
};
</script>

<style scoped>
.glass-hero {
  background: linear-gradient(135deg, rgba(13, 110, 253, 0.9), rgba(99, 102, 241, 0.8));
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.2);
}

.card-hover:hover {
  transform: translateY(-4px);
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1) !important;
}
</style>
