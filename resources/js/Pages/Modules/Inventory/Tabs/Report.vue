<template>
  <div class="rpt-panel">

    <!-- Date range toolbar -->
    <div class="rpt-toolbar">
      <div class="rpt-period-group">
        <label class="rpt-period-label"><i class="ri-time-line me-1"></i>Period</label>

        <!-- Primary: period type -->
        <select class="rpt-period-select" :value="activePeriod" @change="applyPreset($event.target.value)">
          <option value="monthly">Monthly</option>
          <option value="quarterly">Quarterly</option>
          <option value="yearly">Yearly</option>
          <option value="custom">Custom Range</option>
        </select>

        <!-- Secondary: year (shown for all preset modes) -->
        <template v-if="activePeriod !== 'custom'">
          <span class="rpt-date-sep">·</span>
          <select class="rpt-period-select" v-model="selectedYear" @change="recomputeRange">
            <option v-for="y in yearOptions" :key="y" :value="y">{{ y }}</option>
          </select>
        </template>

        <!-- Tertiary: month (Monthly mode) -->
        <template v-if="activePeriod === 'monthly'">
          <select class="rpt-period-select" v-model="selectedMonth" @change="recomputeRange">
            <option v-for="m in monthOptions" :key="m.value" :value="m.value">{{ m.label }}</option>
          </select>
        </template>

        <!-- Tertiary: quarter (Quarterly mode) -->
        <template v-if="activePeriod === 'quarterly'">
          <select class="rpt-period-select" v-model="selectedQuarter" @change="recomputeRange">
            <option value="1">Q1 (Jan – Mar)</option>
            <option value="2">Q2 (Apr – Jun)</option>
            <option value="3">Q3 (Jul – Sep)</option>
            <option value="4">Q4 (Oct – Dec)</option>
          </select>
        </template>

        <!-- Custom date range -->
        <template v-if="activePeriod === 'custom'">
          <div class="rpt-date-range">
            <input type="date" v-model="customStart" class="rpt-date-input" />
            <span class="rpt-date-sep">–</span>
            <input type="date" v-model="customEnd" class="rpt-date-input" />
          </div>
        </template>
      </div>

      <div class="rpt-range-badge">
        <i class="ri-calendar-line me-1"></i>
        {{ displayRange }}
      </div>
    </div>

    <!-- Summary Cards -->
    <div class="rpt-cards">
      <div class="rpt-card" v-for="card in summaryCards" :key="card.label">
        <div class="rpt-card-icon" :style="{ background: card.color }">
          <i :class="card.icon"></i>
        </div>
        <div class="rpt-card-body">
          <strong class="rpt-card-val">{{ card.value }}</strong>
          <span class="rpt-card-lbl">{{ card.label }}</span>
        </div>
      </div>
    </div>

    <div class="rpt-grid">
      <!-- Stock Levels -->
      <div class="rpt-section">
        <div class="rpt-section-head">
          <i class="ri-bar-chart-2-line"></i> Item Stock Levels
        </div>
        <div class="rpt-section-body">
          <div v-if="!itemRows.length" class="rpt-empty">No items to display.</div>
          <template v-else>
            <div class="rpt-bar-row" v-for="item in topItems" :key="item.id">
              <div class="rpt-bar-label">
                <span class="rpt-item-name">{{ item.name }}</span>
                <span class="rpt-item-code">{{ item.code }}</span>
              </div>
              <div class="rpt-bar-track">
                <div class="rpt-bar-fill" :style="{ width: barWidth(item.total_quantity) + '%', background: barColor(item.total_quantity) }"></div>
              </div>
              <span class="rpt-bar-qty" :class="stockClass(item.total_quantity)">{{ formatNum(item.total_quantity) }}</span>
            </div>
            <p v-if="itemRows.length > 10" class="rpt-more-note">+ {{ itemRows.length - 10 }} more items not shown</p>
          </template>
        </div>
      </div>

      <!-- Categories Breakdown -->
      <div class="rpt-section">
        <div class="rpt-section-head">
          <i class="ri-price-tag-3-line"></i> Categories
        </div>
        <div class="rpt-section-body">
          <div v-if="!categoryRows.length" class="rpt-empty">No categories found.</div>
          <div v-else class="rpt-cat-list">
            <div class="rpt-cat-row" v-for="cat in categoryRows" :key="cat.id">
              <span class="rpt-cat-name">{{ cat.name }}</span>
              <span class="rpt-cat-count">{{ itemCountByCategory(cat.name) }} items</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Recent Receivings -->
      <div class="rpt-section">
        <div class="rpt-section-head">
          <i class="ri-inbox-archive-line"></i> Recent Receivings
        </div>
        <div class="rpt-section-body">
          <div v-if="!receivingRows.length" class="rpt-empty">No receivings recorded.</div>
          <div v-else class="rpt-event-list">
            <div class="rpt-event" v-for="r in recentReceivings" :key="r.id">
              <div class="rpt-event-dot rpt-dot-green"></div>
              <div class="rpt-event-body">
                <span class="fw-semibold">{{ r.item_name }}</span>
                <small class="text-muted ms-1">{{ formatDate(r.received_at) }}</small>
                <span class="rpt-status-chip" :class="statusChip(r.status)">{{ r.status }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Recent Withdrawals -->
      <div class="rpt-section">
        <div class="rpt-section-head">
          <i class="ri-shopping-cart-line"></i> Recent Withdrawals
        </div>
        <div class="rpt-section-body">
          <div v-if="!withdrawalRows.length" class="rpt-empty">No withdrawals recorded.</div>
          <div v-else class="rpt-event-list">
            <div class="rpt-event" v-for="w in recentWithdrawals" :key="w.id">
              <div class="rpt-event-dot rpt-dot-orange"></div>
              <div class="rpt-event-body">
                <span class="fw-semibold">{{ w.item_name }}</span>
                <small class="text-muted ms-1">{{ formatDate(w.released_at) }}</small>
                <span class="rpt-status-chip" :class="statusChip(w.status)">{{ w.status }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- RIS Summary -->
      <div class="rpt-section">
        <div class="rpt-section-head">
          <i class="ri-file-list-3-line"></i> RIS Summary
        </div>
        <div class="rpt-section-body">
          <div v-if="!risRows.length" class="rpt-empty">No RIS records found.</div>
          <div v-else>
            <div class="rpt-stat-row" v-for="stat in risSummary" :key="stat.label">
              <span>{{ stat.label }}</span>
              <strong>{{ stat.value }}</strong>
            </div>
          </div>
        </div>
      </div>

      <!-- Low Stock Alert -->
      <div class="rpt-section">
        <div class="rpt-section-head">
          <i class="ri-alarm-warning-line"></i> Low Stock Alert
          <span v-if="lowStockItems.length" class="rpt-alert-badge">{{ lowStockItems.length }}</span>
        </div>
        <div class="rpt-section-body">
          <div v-if="!lowStockItems.length" class="rpt-empty rpt-empty-ok">
            <i class="ri-checkbox-circle-line"></i> All items have sufficient stock.
          </div>
          <div v-else class="rpt-alert-list">
            <div class="rpt-alert-item" v-for="item in lowStockItems" :key="item.id">
              <span class="rpt-alert-name">{{ item.name }}</span>
              <span class="rpt-alert-qty">{{ formatNum(item.total_quantity) }} left</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Saved Reports -->
    <div class="rpt-section rpt-reports-section">
      <div class="rpt-section-head rpt-reports-head">
        <span><i class="ri-file-chart-2-line"></i> Saved Reports</span>
        <div class="rpt-reports-actions">
          <select class="rpt-period-select" v-model="reportCategoryFilter">
            <option value="">All Categories</option>
            <option v-for="cat in reportCategories" :key="cat.id" :value="String(cat.id)">
              {{ cat.name }}
            </option>
          </select>
          <select class="rpt-period-select" v-model="reportPeriodFilter">
            <option value="">All Periods</option>
            <option v-for="p in reportPeriodOptions" :key="p" :value="p">
              {{ p }}
            </option>
          </select>
          <button type="button" class="btn btn-sm btn-primary rounded-pill px-3" @click="$emit('create-report')">
            <i class="ri-add-line me-1"></i>Generate Report
          </button>
        </div>
      </div>
      <div class="rpt-section-body">
        <div v-if="!filteredReportRows.length" class="rpt-empty">No reports yet.</div>
        <div v-else class="rpt-report-list">
          <div class="rpt-report-row" v-for="report in pagedReportRows" :key="report.id">
            <div class="rpt-report-info">
              <span class="rpt-report-code">{{ report.code }}</span>
              <span class="rpt-report-title">{{ report.title }}</span>
              <span class="rpt-cat-badge">{{ report.category || 'Uncategorized' }}</span>
              <span v-if="report.period_label" class="rpt-period-badge">
                <i class="ri-calendar-line me-1"></i>{{ report.period_label }}
              </span>
            </div>
            <div class="rpt-report-actions">
              <button type="button" class="btn btn-sm btn-outline-secondary px-1 py-0" title="View" @click="viewReport(report)">
                <i class="ri-eye-line"></i>
              </button>
              <button type="button" class="btn btn-sm btn-outline-primary px-1 py-0" title="Print" @click="printReport(report)">
                <i class="ri-printer-line"></i>
              </button>
              <button type="button" class="btn btn-sm btn-outline-danger px-1 py-0" title="Delete" @click="$emit('delete-report', report)">
                <i class="ri-delete-bin-line"></i>
              </button>
            </div>
          </div>
        </div>
      </div>
      <div v-if="reportTotalPages > 1" class="rpt-pagination-bar">
        <span class="text-muted small">
          Page {{ reportPage }} of {{ reportTotalPages }}
          &nbsp;·&nbsp;
          {{ filteredReportRows.length }} total
        </span>
        <nav>
          <ul class="pagination pagination-sm mb-0">
            <li class="page-item" :class="{ disabled: reportPage === 1 }">
              <button class="page-link" @click="reportPage--">‹</button>
            </li>
            <template v-for="page in reportVisiblePages" :key="page">
              <li v-if="page === '...'" class="page-item disabled">
                <span class="page-link">…</span>
              </li>
              <li
                v-else
                class="page-item"
                :class="{ active: reportPage === page }"
              >
                <button class="page-link" @click="reportPage = page">{{ page }}</button>
              </li>
            </template>
            <li class="page-item" :class="{ disabled: reportPage === reportTotalPages }">
              <button class="page-link" @click="reportPage++">›</button>
            </li>
          </ul>
        </nav>
      </div>
    </div>

    <!-- View Report modal -->
    <b-modal
      v-model="showViewModal"
      title="Report Details"
      size="lg"
      centered
      header-class="border-0 pb-0"
      footer-class="border-top"
    >
      <div v-if="viewingReport" class="rpt-view-table">
        <div class="rpt-view-row">
          <span class="rpt-view-label">Report Code</span>
          <span class="rpt-view-value">{{ viewingReport.code }}</span>
        </div>
        <div class="rpt-view-row">
          <span class="rpt-view-label">Category</span>
          <span class="rpt-view-value">{{ viewingReport.category || '—' }}</span>
        </div>
        <div class="rpt-view-row">
          <span class="rpt-view-label">Title</span>
          <span class="rpt-view-value">{{ viewingReport.title || '—' }}</span>
        </div>
        <div class="rpt-view-row">
          <span class="rpt-view-label">Period Covered</span>
          <span class="rpt-view-value">{{ viewingReport.period_label || '—' }}</span>
        </div>
        <div class="rpt-view-row">
          <span class="rpt-view-label">Prepared By</span>
          <span class="rpt-view-value">{{ viewingReport.created_by || '—' }}</span>
        </div>
        <div class="rpt-view-row">
          <span class="rpt-view-label">Date Generated</span>
          <span class="rpt-view-value">{{ formatDate(viewingReport.created_at) }}</span>
        </div>
      </div>

      <div v-if="viewLoading" class="rpt-view-loading">
        <span class="spinner-border spinner-border-sm me-2"></span>Loading records…
      </div>
      <div v-else-if="viewingKind === 'ris_issued'" class="rpt-view-detail">
        <h6 class="rpt-view-detail-title">Issued RIS Items</h6>
        <div v-if="!viewingGroups.length" class="rpt-empty">No issued RIS items found for this period.</div>
        <template v-else>
          <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th>RIS No.</th>
                  <th>Resp Center</th>
                  <th>Item No.</th>
                  <th>Item Name</th>
                  <th>Unit</th>
                  <th class="text-end">Quantity</th>
                  <th class="text-end">Unit Cost</th>
                  <th class="text-end">Amount</th>
                </tr>
              </thead>
              <tbody>
                <template v-for="group in viewingGroups" :key="group.ris_no">
                  <tr v-for="(item, idx) in group.items" :key="group.ris_no + '-' + idx">
                    <td v-if="idx === 0" :rowspan="group.items.length">{{ group.ris_no }}</td>
                    <td v-if="idx === 0" :rowspan="group.items.length">{{ group.responsibility_center || '—' }}</td>
                    <td>{{ item.item_no }}</td>
                    <td>{{ item.item_name }}</td>
                    <td>{{ item.unit }}</td>
                    <td class="text-end">{{ formatNum(item.quantity) }}</td>
                    <td class="text-end">{{ formatNum(item.unit_cost) }}</td>
                    <td class="text-end">{{ formatNum(item.amount) }}</td>
                  </tr>
                </template>
                <tr class="fw-bold">
                  <td colspan="7" class="text-end">TOTAL</td>
                  <td class="text-end">{{ formatNum(viewingGrandTotal) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
          <div v-if="Object.keys(viewingCategoryTotals).length" class="rpt-view-summary">
            <div class="rpt-view-summary-title">Summary by Category</div>
            <div class="rpt-view-row" v-for="(amount, cat) in viewingCategoryTotals" :key="cat">
              <span class="rpt-view-label">{{ cat }}</span>
              <span class="rpt-view-value">{{ formatNum(amount) }}</span>
            </div>
          </div>
        </template>
      </div>
      <div v-else-if="viewingKind !== 'none'" class="rpt-view-detail">
        <h6 class="rpt-view-detail-title">
          {{ viewingKind === 'received' ? 'Receiving Records' : 'Withdrawal Records' }}
        </h6>
        <div v-if="!viewingRows.length" class="rpt-empty">No {{ viewingKind }} records found for this period.</div>
        <div v-else class="table-responsive">
          <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th v-for="col in viewingColumns" :key="col">{{ col }}</th>
              </tr>
            </thead>
            <tbody v-if="viewingKind === 'received'">
              <tr v-for="row in viewingRows" :key="row.id">
                <td>{{ row.code }}</td>
                <td>{{ row.name }}</td>
                <td>{{ row.stock || '—' }}</td>
                <td class="text-end">{{ formatNum(row.quantity) }}</td>
                <td class="text-end">{{ formatNum(row.unit_cost) }}</td>
                <td class="text-end">{{ formatNum(row.total_cost) }}</td>
                <td>{{ row.date }}</td>
                <td>{{ row.status }}</td>
              </tr>
            </tbody>
            <tbody v-else>
              <tr v-for="row in viewingRows" :key="row.id">
                <td>{{ row.code }}</td>
                <td>{{ row.name }}</td>
                <td class="text-end">{{ formatNum(row.quantity) }}</td>
                <td class="text-end">{{ formatNum(row.issued_quantity) }}</td>
                <td class="text-end">{{ formatNum(row.unit_cost) }}</td>
                <td class="text-end">{{ formatNum(row.total_cost) }}</td>
                <td>{{ row.date }}</td>
                <td>{{ row.status }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <template #footer>
        <b-button variant="light" @click="showViewModal = false">Close</b-button>
        <b-button variant="primary" @click="printReport(viewingReport)">
          <i class="ri-printer-line me-1"></i>Print
        </b-button>
      </template>
    </b-modal>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'ReportPanel',
  props: {
    itemRows:       { type: Array, default: () => [] },
    itemMeta:       { type: Object, default: null },
    stockRows:      { type: Array, default: () => [] },
    stockMeta:      { type: Object, default: null },
    receivingRows:  { type: Array, default: () => [] },
    receivingMeta:  { type: Object, default: null },
    withdrawalRows: { type: Array, default: () => [] },
    withdrawalMeta: { type: Object, default: null },
    categoryRows:   { type: Array, default: () => [] },
    risRows:        { type: Array, default: () => [] },
    risMeta:        { type: Object, default: null },
    reportRows:      { type: Array, default: () => [] },
    reportCategories:{ type: Array, default: () => [] },
  },
  emits: ['create-report', 'delete-report'],
  data() {
    const today = new Date();
    const year  = today.getFullYear();
    const month = today.getMonth() + 1;
    const quarter = Math.floor(today.getMonth() / 3) + 1;
    return {
      activePeriod:    'monthly',
      selectedYear:    year,
      selectedMonth:   month,
      selectedQuarter: quarter,
      customStart: this.isoDate(new Date(year, month - 1, 1)),
      customEnd:   this.isoDate(today),
      reportCategoryFilter: '',
      reportPeriodFilter: '',
      reportPage: 1,
      reportPerPage: 10,
      showViewModal: false,
      viewingReport: null,
      viewingKind: 'none',
      viewingColumns: [],
      viewingRows: [],
      viewingGroups: [],
      viewingGrandTotal: 0,
      viewingCategoryTotals: {},
      viewLoading: false,
    };
  },
  watch: {
    reportCategoryFilter() {
      this.reportPage = 1;
    },
    reportPeriodFilter() {
      this.reportPage = 1;
    },
    'reportRows.length'() {
      if (this.reportPage > this.reportTotalPages) this.reportPage = this.reportTotalPages;
    },
  },
  computed: {
    yearOptions() {
      const y = new Date().getFullYear();
      return [y - 3, y - 2, y - 1, y, y + 1];
    },
    monthOptions() {
      return [
        { value: 1, label: 'January' }, { value: 2, label: 'February' },
        { value: 3, label: 'March' },   { value: 4, label: 'April' },
        { value: 5, label: 'May' },     { value: 6, label: 'June' },
        { value: 7, label: 'July' },    { value: 8, label: 'August' },
        { value: 9, label: 'September' },{ value: 10, label: 'October' },
        { value: 11, label: 'November' },{ value: 12, label: 'December' },
      ];
    },
    rangeStart() { return new Date(this.customStart + 'T00:00:00'); },
    rangeEnd()   { return new Date(this.customEnd   + 'T23:59:59'); },
    displayRange() {
      const fmt = (d) => new Intl.DateTimeFormat('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
        .format(new Date(d + 'T00:00:00'));
      return `${fmt(this.customStart)} – ${fmt(this.customEnd)}`;
    },
    filteredReceivingRows() {
      return this.receivingRows.filter((r) => {
        const d = r.received_at ? new Date(r.received_at.replace(' ', 'T')) : null;
        return d && d >= this.rangeStart && d <= this.rangeEnd;
      });
    },
    filteredWithdrawalRows() {
      return this.withdrawalRows.filter((w) => {
        const d = w.released_at ? new Date(w.released_at.replace(' ', 'T')) : null;
        return d && d >= this.rangeStart && d <= this.rangeEnd;
      });
    },
    filteredRisRows() {
      return this.risRows.filter((r) => {
        const d = r.ris_date ? new Date(r.ris_date) : null;
        return d && d >= this.rangeStart && d <= this.rangeEnd;
      });
    },
    summaryCards() {
      return [
        { label: 'Total Items', value: this.formatNum(this.itemMeta?.total ?? this.itemRows.length), icon: 'ri-barcode-box-line', color: 'linear-gradient(135deg,#4b5b93,#38467a)' },
        { label: 'Stock Records', value: this.formatNum(this.stockMeta?.total ?? this.stockRows.length), icon: 'ri-stack-line', color: 'linear-gradient(135deg,#0ea5e9,#0284c7)' },
        { label: 'Receivings', value: this.formatNum(this.receivingMeta?.total ?? this.receivingRows.length), icon: 'ri-inbox-archive-line', color: 'linear-gradient(135deg,#10b981,#059669)' },
        { label: 'Withdrawals', value: this.formatNum(this.withdrawalMeta?.total ?? this.withdrawalRows.length), icon: 'ri-shopping-cart-line', color: 'linear-gradient(135deg,#f59e0b,#d97706)' },
        { label: 'RIS Issued', value: this.formatNum(this.risMeta?.total ?? this.risRows.length), icon: 'ri-file-list-3-line', color: 'linear-gradient(135deg,#8b5cf6,#7c3aed)' },
        { label: 'Low Stock', value: this.lowStockItems.length, icon: 'ri-alarm-warning-line', color: this.lowStockItems.length ? 'linear-gradient(135deg,#ef4444,#dc2626)' : 'linear-gradient(135deg,#6b7280,#4b5563)' },
      ];
    },
    topItems() {
      return [...this.itemRows]
        .sort((a, b) => Number(b.total_quantity || 0) - Number(a.total_quantity || 0))
        .slice(0, 10);
    },
    maxQty() {
      return Math.max(1, ...this.itemRows.map((i) => Number(i.total_quantity || 0)));
    },
    lowStockItems() {
      return this.itemRows.filter((i) => Number(i.total_quantity || 0) <= 5);
    },
    filteredReportRows() {
      return this.reportRows.filter((r) => {
        const matchesCategory = !this.reportCategoryFilter || String(r.category_id) === this.reportCategoryFilter;
        const matchesPeriod = !this.reportPeriodFilter || r.period_label === this.reportPeriodFilter;
        return matchesCategory && matchesPeriod;
      });
    },
    reportPeriodOptions() {
      return [...new Set(this.reportRows.map((r) => r.period_label).filter(Boolean))].sort();
    },
    reportTotalPages() {
      return Math.max(1, Math.ceil(this.filteredReportRows.length / this.reportPerPage));
    },
    pagedReportRows() {
      const start = (this.reportPage - 1) * this.reportPerPage;
      return this.filteredReportRows.slice(start, start + this.reportPerPage);
    },
    reportVisiblePages() {
      const current = this.reportPage;
      const last    = this.reportTotalPages;
      if (last <= 7) return Array.from({ length: last }, (_, i) => i + 1);
      const pages = [1];
      if (current > 3) pages.push("...");
      for (let p = Math.max(2, current - 1); p <= Math.min(last - 1, current + 1); p++) pages.push(p);
      if (current < last - 2) pages.push("...");
      pages.push(last);
      return pages;
    },
    recentReceivings() { return this.filteredReceivingRows.slice(0, 8); },
    recentWithdrawals() { return this.filteredWithdrawalRows.slice(0, 8); },
    risSummary() {
      const byStatus = {};
      this.filteredRisRows.forEach((r) => {
        const s = r.status || 'Unknown';
        byStatus[s] = (byStatus[s] || 0) + 1;
      });
      return Object.entries(byStatus).map(([label, value]) => ({ label, value }));
    },
  },
  methods: {
    isoDate(d) {
      return d.toISOString().slice(0, 10);
    },
    applyPreset(key) {
      this.activePeriod = key;
      if (key === 'custom') return;
      this.recomputeRange();
    },
    recomputeRange() {
      const y = Number(this.selectedYear);
      let start, end;
      if (this.activePeriod === 'monthly') {
        const m = Number(this.selectedMonth) - 1;
        start = new Date(y, m, 1);
        end   = new Date(y, m + 1, 0); // last day of month
      } else if (this.activePeriod === 'quarterly') {
        const q = Number(this.selectedQuarter) - 1;
        start = new Date(y, q * 3, 1);
        end   = new Date(y, q * 3 + 3, 0);
      } else {
        start = new Date(y, 0, 1);
        end   = new Date(y, 11, 31);
      }
      this.customStart = this.isoDate(start);
      this.customEnd   = this.isoDate(end);
    },
    formatNum(v) { return new Intl.NumberFormat().format(Number(v || 0)); },
    formatDate(v) {
      if (!v) return '—';
      return new Intl.DateTimeFormat('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
        .format(new Date(String(v).replace(' ', 'T')));
    },
    barWidth(qty) { return Math.min(100, (Number(qty || 0) / this.maxQty) * 100); },
    barColor(qty) {
      const q = Number(qty || 0);
      if (q <= 0) return '#ef4444';
      if (q <= 5) return '#f59e0b';
      return '#10b981';
    },
    stockClass(qty) {
      const q = Number(qty || 0);
      if (q <= 0) return 'rpt-qty-empty';
      if (q <= 5) return 'rpt-qty-low';
      return 'rpt-qty-ok';
    },
    itemCountByCategory(catName) {
      return this.itemRows.filter((i) => i.category === catName).length;
    },
    async viewReport(report) {
      this.viewingReport = report;
      this.viewingKind = 'none';
      this.viewingColumns = [];
      this.viewingRows = [];
      this.viewingGroups = [];
      this.viewingGrandTotal = 0;
      this.viewingCategoryTotals = {};
      this.showViewModal = true;
      this.viewLoading = true;
      try {
        const response = await axios.get(`/inventory-reports/${report.id}`);
        const detail = response.data?.rows || {};
        this.viewingKind = detail.kind || 'none';
        this.viewingColumns = detail.columns || [];
        this.viewingRows = detail.rows || [];
        this.viewingGroups = detail.groups || [];
        this.viewingGrandTotal = detail.grand_total || 0;
        this.viewingCategoryTotals = detail.category_totals || {};
      } finally {
        this.viewLoading = false;
      }
    },
    printReport(report) {
      if (!report) return;
      window.open(`/inventory-print/report/${report.id}`, '_blank');
    },
    statusChip(status) {
      const map = {
        Pending: 'chip-warning', Approved: 'chip-success',
        Completed: 'chip-primary', Cancelled: 'chip-danger', Disapproved: 'chip-secondary',
      };
      return map[status] || 'chip-secondary';
    },
  },
};
</script>

<style scoped>
.rpt-panel { padding: .5rem; }

/* ── Date range toolbar ── */
.rpt-toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: .65rem;
  padding: .65rem .85rem;
  margin-bottom: 1rem;
  background: #fff;
  border: 1px solid #dce4f2;
  border-radius: 14px;
}
.rpt-period-group {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: .35rem;
}
.rpt-period-label {
  font-size: .76rem;
  font-weight: 700;
  color: #4b5b93;
  white-space: nowrap;
  margin: 0;
}
.rpt-period-select {
  padding: .3rem 2rem .3rem .65rem;
  border: 1px solid #dce4f2;
  border-radius: 9px;
  background: #f8fbff;
  color: #0f172a;
  font-size: .82rem;
  font-weight: 700;
  cursor: pointer;
  appearance: none;
  -webkit-appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%234b5b93' stroke-width='2.5'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right .6rem center;
}
.rpt-period-select:focus { outline: none; border-color: #4b5b93; }
.rpt-date-range {
  display: flex;
  align-items: center;
  gap: .35rem;
}
.rpt-date-input {
  padding: .28rem .55rem;
  border: 1px solid #dce4f2;
  border-radius: 8px;
  font-size: .78rem;
  color: #0f172a;
  background: #f8fbff;
  outline: none;
}
.rpt-date-input:focus { border-color: #4b5b93; }
.rpt-date-sep { color: #94a3b8; font-size: .8rem; }
.rpt-range-badge {
  font-size: .76rem;
  font-weight: 700;
  color: #4b5b93;
  background: rgba(75,91,147,.08);
  padding: .25rem .65rem;
  border-radius: 20px;
  white-space: nowrap;
}

/* ── Summary cards ── */
.rpt-cards {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(170px, 1fr));
  gap: .75rem;
  margin-bottom: 1.25rem;
}
.rpt-card {
  display: flex; align-items: center; gap: .75rem;
  padding: .85rem 1rem;
  border: 1px solid #dce4f2; border-radius: 16px;
  background: #fff; box-shadow: 0 2px 8px rgba(15,23,42,.05);
}
.rpt-card-icon {
  width: 42px; height: 42px; border-radius: 13px;
  display: inline-flex; align-items: center; justify-content: center;
  font-size: 1.1rem; color: #fff; flex-shrink: 0;
}
.rpt-card-body { display: flex; flex-direction: column; }
.rpt-card-val { font-size: 1.35rem; font-weight: 800; color: #0f172a; line-height: 1; }
.rpt-card-lbl { font-size: .68rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: #64748b; }

/* ── Sections grid ── */
.rpt-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: .9rem;
}
.rpt-section {
  border: 1px solid #dce4f2; border-radius: 16px;
  background: #fff; overflow: hidden;
}
.rpt-section-head {
  display: flex; align-items: center; gap: .45rem;
  padding: .7rem 1rem;
  background: linear-gradient(180deg, #f8fbff, #f0f5ff);
  border-bottom: 1px solid #dce4f2;
  font-size: .8rem; font-weight: 800; color: #4b5b93;
  text-transform: uppercase; letter-spacing: .06em;
}
.rpt-section-body { padding: .85rem; max-height: 260px; overflow: auto; }
.rpt-empty { text-align: center; color: #94a3b8; font-size: .85rem; padding: 1.5rem 0; }
.rpt-empty-ok { color: #10b981; display: flex; flex-direction: column; align-items: center; gap: .4rem; }
.rpt-empty-ok i { font-size: 2rem; }

/* ── Bar chart ── */
.rpt-bar-row { display: flex; align-items: center; gap: .6rem; margin-bottom: .55rem; }
.rpt-bar-label { flex: 0 0 130px; display: flex; flex-direction: column; }
.rpt-item-name { font-size: .8rem; font-weight: 600; color: #0f172a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 128px; }
.rpt-item-code { font-size: .68rem; color: #94a3b8; font-family: ui-monospace, monospace; }
.rpt-bar-track { flex: 1; height: 8px; border-radius: 999px; background: #e2e8f0; overflow: hidden; }
.rpt-bar-fill { height: 100%; border-radius: 999px; transition: width .4s ease; }
.rpt-bar-qty { flex: 0 0 40px; font-size: .8rem; font-weight: 800; text-align: right; }
.rpt-qty-empty { color: #ef4444; }
.rpt-qty-low { color: #f59e0b; }
.rpt-qty-ok { color: #10b981; }
.rpt-more-note { font-size: .75rem; color: #94a3b8; margin-top: .5rem; text-align: center; }

/* ── Saved Reports ── */
.rpt-reports-section { margin-bottom: 1.25rem; }
.rpt-reports-head { justify-content: space-between; }
.rpt-reports-actions { display: flex; align-items: center; gap: .5rem; }
.rpt-report-list { display: flex; flex-direction: column; gap: .4rem; }
.rpt-report-row {
  display: flex; align-items: center; justify-content: space-between;
  padding: .5rem .65rem; border-radius: 8px; background: #f8fbff;
}
.rpt-report-info { display: flex; align-items: center; gap: .55rem; flex-wrap: wrap; }
.rpt-report-actions { display: flex; align-items: center; gap: .35rem; flex-shrink: 0; }
.rpt-view-table { display: flex; flex-direction: column; gap: .1rem; }
.rpt-view-row {
  display: flex; align-items: center; justify-content: space-between;
  padding: .5rem 0; border-bottom: 1px solid #f1f5ff;
}
.rpt-view-row:last-child { border-bottom: none; }
.rpt-view-label { font-size: .78rem; font-weight: 700; color: #64748b; }
.rpt-view-value { font-size: .86rem; font-weight: 600; color: #0f172a; text-align: right; }
.rpt-view-loading { display: flex; align-items: center; justify-content: center; padding: 1.5rem 0; color: #64748b; font-size: .85rem; }
.rpt-view-detail { margin-top: 1rem; }
.rpt-view-detail-title { font-size: .82rem; font-weight: 800; color: #4b5b93; text-transform: uppercase; letter-spacing: .04em; margin-bottom: .5rem; }
.rpt-view-summary { margin-top: .85rem; padding-top: .5rem; border-top: 1px dashed #dce4f2; }
.rpt-view-summary-title { font-size: .76rem; font-weight: 700; color: #64748b; margin-bottom: .3rem; }
.rpt-report-title { font-size: .84rem; font-weight: 600; color: #0f172a; }
.rpt-report-code {
  font-size: .7rem; font-weight: 700; font-family: ui-monospace, monospace;
  color: #64748b; background: #eef2ff; padding: .1rem .4rem; border-radius: 6px;
}
.rpt-cat-badge {
  display: inline-block; padding: .1rem .5rem; border-radius: 20px;
  font-size: .68rem; font-weight: 700; background: rgba(75,91,147,.1); color: #4b5b93;
}
.rpt-period-badge {
  display: inline-flex; align-items: center; padding: .1rem .5rem; border-radius: 20px;
  font-size: .68rem; font-weight: 700; background: #f1f5f9; color: #475569;
}
.rpt-pagination-bar {
  display: flex; align-items: center; justify-content: space-between;
  gap: .65rem; flex-wrap: wrap;
  padding: .6rem .85rem; border-top: 1px solid #dce4f2;
}

/* ── Category list ── */
.rpt-cat-list { display: flex; flex-direction: column; gap: .4rem; }
.rpt-cat-row { display: flex; align-items: center; justify-content: space-between; padding: .4rem .5rem; border-radius: 8px; background: #f8fbff; }
.rpt-cat-name { font-size: .84rem; font-weight: 600; color: #0f172a; }
.rpt-cat-count { font-size: .76rem; color: #4b5b93; font-weight: 700; }

/* ── Event list ── */
.rpt-event-list { display: flex; flex-direction: column; gap: .5rem; }
.rpt-event { display: flex; align-items: flex-start; gap: .55rem; }
.rpt-event-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; margin-top: 5px; }
.rpt-dot-green { background: #10b981; }
.rpt-dot-orange { background: #f59e0b; }
.rpt-event-body { display: flex; flex-wrap: wrap; align-items: center; gap: .35rem; font-size: .84rem; }
.rpt-status-chip {
  display: inline-block; padding: .1rem .45rem; border-radius: 20px; font-size: .68rem; font-weight: 700;
}
.chip-warning { background: rgba(245,158,11,.12); color: #d97706; }
.chip-success { background: rgba(16,185,129,.12); color: #059669; }
.chip-primary { background: rgba(75,91,147,.12); color: #4b5b93; }
.chip-danger { background: rgba(239,68,68,.12); color: #dc2626; }
.chip-secondary { background: rgba(100,116,139,.12); color: #64748b; }

/* ── RIS summary ── */
.rpt-stat-row { display: flex; justify-content: space-between; align-items: center; padding: .4rem 0; border-bottom: 1px solid #f1f5ff; font-size: .84rem; }
.rpt-stat-row:last-child { border-bottom: none; }

/* ── Low stock ── */
.rpt-alert-badge {
  margin-left: auto; background: #ef4444; color: #fff;
  border-radius: 999px; font-size: .65rem; font-weight: 800;
  padding: .1rem .45rem; line-height: 1.4;
}
.rpt-alert-list { display: flex; flex-direction: column; gap: .35rem; }
.rpt-alert-item { display: flex; align-items: center; justify-content: space-between; padding: .4rem .6rem; border-radius: 8px; background: #fff1f2; border: 1px solid #fecdd3; }
.rpt-alert-name { font-size: .84rem; font-weight: 600; color: #0f172a; }
.rpt-alert-qty { font-size: .78rem; font-weight: 800; color: #dc2626; }

/* ── Dark mode ──────────────────────────────────────── */
:global([data-bs-theme="dark"] .rpt-toolbar),
:global([data-layout-mode="dark"] .rpt-toolbar) {
  background: #111827;
  border-color: #2e3a59;
}

:global([data-bs-theme="dark"] .rpt-period-select),
:global([data-layout-mode="dark"] .rpt-period-select),
:global([data-bs-theme="dark"] .rpt-date-input),
:global([data-layout-mode="dark"] .rpt-date-input) {
  background: #182035;
  border-color: #2e3a59;
  color: #e5e7eb;
}

:global([data-bs-theme="dark"] .rpt-range-badge),
:global([data-layout-mode="dark"] .rpt-range-badge),
:global([data-bs-theme="dark"] .rpt-cat-badge),
:global([data-layout-mode="dark"] .rpt-cat-badge) {
  background: rgba(142, 160, 244, 0.14);
  color: #8ea0f4;
}

:global([data-bs-theme="dark"] .rpt-period-badge),
:global([data-layout-mode="dark"] .rpt-period-badge) {
  background: #182035;
  color: #9ca9c7;
}

:global([data-bs-theme="dark"] .rpt-card),
:global([data-layout-mode="dark"] .rpt-card) {
  background: #111827;
  border-color: #2e3a59;
}

:global([data-bs-theme="dark"] .rpt-card-val),
:global([data-layout-mode="dark"] .rpt-card-val) {
  color: #e5e7eb;
}

:global([data-bs-theme="dark"] .rpt-section),
:global([data-layout-mode="dark"] .rpt-section) {
  background: #111827;
  border-color: #2e3a59;
}

:global([data-bs-theme="dark"] .rpt-section-head),
:global([data-layout-mode="dark"] .rpt-section-head) {
  background: linear-gradient(180deg, #151e33, #111827);
  border-bottom-color: #2e3a59;
  color: #8ea0f4;
}

:global([data-bs-theme="dark"] .rpt-item-name),
:global([data-layout-mode="dark"] .rpt-item-name),
:global([data-bs-theme="dark"] .rpt-cat-name),
:global([data-layout-mode="dark"] .rpt-cat-name),
:global([data-bs-theme="dark"] .rpt-report-title),
:global([data-layout-mode="dark"] .rpt-report-title),
:global([data-bs-theme="dark"] .rpt-alert-name),
:global([data-layout-mode="dark"] .rpt-alert-name) {
  color: #e5e7eb;
}

:global([data-bs-theme="dark"] .rpt-bar-track),
:global([data-layout-mode="dark"] .rpt-bar-track) {
  background: #182035;
}

:global([data-bs-theme="dark"] .rpt-report-row),
:global([data-layout-mode="dark"] .rpt-report-row),
:global([data-bs-theme="dark"] .rpt-cat-row),
:global([data-layout-mode="dark"] .rpt-cat-row) {
  background: #182035;
}

:global([data-bs-theme="dark"] .rpt-report-code),
:global([data-layout-mode="dark"] .rpt-report-code) {
  background: #0b1220;
  color: #9ca9c7;
}

:global([data-bs-theme="dark"] .rpt-pagination-bar),
:global([data-layout-mode="dark"] .rpt-pagination-bar) {
  border-top-color: #2e3a59;
}

:global([data-bs-theme="dark"] .rpt-stat-row),
:global([data-layout-mode="dark"] .rpt-stat-row) {
  border-bottom-color: #2e3a59;
  color: #e5e7eb;
}

:global([data-bs-theme="dark"] .rpt-alert-item),
:global([data-layout-mode="dark"] .rpt-alert-item) {
  background: rgba(239, 68, 68, 0.1);
  border-color: rgba(239, 68, 68, 0.3);
}

:global([data-bs-theme="dark"] .rpt-view-row),
:global([data-layout-mode="dark"] .rpt-view-row) {
  border-bottom-color: #2e3a59;
}

:global([data-bs-theme="dark"] .rpt-view-value),
:global([data-layout-mode="dark"] .rpt-view-value) {
  color: #e5e7eb;
}
</style>
