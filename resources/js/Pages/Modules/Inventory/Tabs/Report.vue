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
  </div>
</template>

<script>
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
  },
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
    };
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
</style>
