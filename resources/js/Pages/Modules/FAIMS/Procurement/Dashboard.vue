<template>
  <div class="procurement-dashboard-page">
    <Head title="Procurement Dashboard" />
    <PageHeader title="Procurement Dashboard" pageTitle="Overview" />

    <!-- Hero -->
    <section class="card border-0 overflow-hidden mb-2 procurement-hero">
      <div class="card-body">
        <div class="row g-2 align-items-center">
          <div class="col-xl-7">
            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
              <span class="hero-kicker">Procurement Overview</span>
              <BBadge class="bg-white bg-opacity-10 text-white border border-white border-opacity-25 rounded-pill">
                <i class="ri-calendar-line me-1"></i>{{ filteredPeriodLabel }}
              </BBadge>
            </div>

            <h3 class="text-white fw-bold mb-2">
              Keep a close pulse on requests, approvals, and completions.
            </h3>

            <p class="text-white-50 mb-2">
              Track request volume, queue pressure, and unit activity from one focused workspace.
            </p>

            <div class="d-flex flex-wrap gap-2">
              <BBadge class="hero-pill"><i class="ri-stack-line"></i>Total {{ dashboard.total_procurements }}</BBadge>
              <BBadge class="hero-pill is-success"><i class="ri-check-double-line"></i>Completed {{ dashboard.completed_procurements }}</BBadge>
              <BBadge class="hero-pill is-warning"><i class="ri-search-eye-line"></i>For Review {{ dashboard.for_reviews }}</BBadge>
              <BBadge class="hero-pill is-warning"><i class="ri-shield-check-line"></i>For Approval {{ dashboard.for_approvals }}</BBadge>
              <BBadge class="hero-pill is-dark"><i class="ri-pie-chart-2-line"></i>Completion {{ completionRate }}%</BBadge>
            </div>

            <p v-if="lastUpdated" class="text-white-50 mb-0 mt-2 fs-12">
              Last updated: {{ formatDateTime(lastUpdated) }}
            </p>
          </div>

          <div class="col-xl-5">
            <div class="hero-stat-grid">
              <div class="hero-stat-card">
                <i class="ri-folder-open-line hero-stat-watermark"></i>
                <span>Open Requests</span>
                <strong>{{ openRequests }}</strong>
                <small>Still moving through the pipeline</small>
              </div>

              <div class="hero-stat-card">
                <i class="ri-building-4-line hero-stat-watermark"></i>
                <span>Active Requesting Units</span>
                <strong>{{ activeUnitsCount }}</strong>
                <small>Units with procurement activity</small>
              </div>

              <div class="hero-stat-card">
                <i class="ri-money-dollar-circle-line hero-stat-watermark"></i>
                <span>Approved Budget for Contract</span>
                <strong>{{ formatCompactCurrency(totalActualAwardedAmount) }}</strong>
                <small>PR amount {{ formatCompactCurrency(totalApprovedBudgetAmount) }}</small>
              </div>

              <div class="hero-stat-card d-flex flex-column justify-content-center gap-2">
                <BButton variant="light" class="fw-semibold rounded-3" @click="goCreatePage">
                  <i class="ri-add-circle-line me-1"></i> New Request
                </BButton>

                <BButton variant="outline-light" class="fw-semibold rounded-3" @click="goViewAll">
                  <i class="ri-file-list-3-line me-1"></i> View Requests
                </BButton>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Filters -->
    <section class="card border-0 shadow-sm rounded-4 mb-2 dashboard-filter-card">
      <div class="card-body compact-card">
        <div class="dashboard-filter-row">
          <div class="dashboard-filter-copy">
            <span class="section-kicker">Dashboard Filters</span>
            <h5 class="fw-bold mb-0 mt-1">Focus the procurement view</h5>
            <BBadge class="bg-primary-subtle text-primary rounded-pill px-2 py-1">
              <i class="ri-focus-3-line me-1"></i>{{ filteredPeriodLabel }}
            </BBadge>
          </div>

          <div class="dashboard-filter-controls">
            <div class="filter-field">
              <label class="form-label small text-muted">Filter Period</label>
              <Multiselect
                v-model="dashboardFilter.period"
                :options="periodOptions"
                label="label"
                valueProp="value"
                trackBy="label"
                :append-to-body="true"
                :searchable="false"
                placeholder="Select period"
                @change="onPeriodChange"
              />
            </div>

            <div v-if="isQuarterSelected || ['monthly', 'quarterly', 'yearly'].includes(dashboardFilter.period)" class="filter-field">
              <label class="form-label small text-muted">Year</label>
              <Multiselect
                v-model="dashboardFilter.year"
                :options="yearOptions"
                :append-to-body="true"
                placeholder="Select year"
                @change="fetchDashboard"
              />
            </div>

            <div v-if="dashboardFilter.period === 'monthly'" class="filter-field">
              <label class="form-label small text-muted">Month</label>
              <Multiselect
                v-model="dashboardFilter.month"
                :options="monthOptions"
                :append-to-body="true"
                placeholder="Select month"
                @change="fetchDashboard"
              />
            </div>

            <div v-if="dashboardFilter.period === 'quarterly'" class="filter-field">
              <label class="form-label small text-muted">Quarter</label>
              <Multiselect
                v-model="dashboardFilter.quarter"
                :options="quarterOptions"
                :append-to-body="true"
                placeholder="Select quarter"
                @change="fetchDashboard"
              />
            </div>

            <div v-if="dashboardFilter.period === 'custom'" class="filter-field">
              <label class="form-label small text-muted">Start Date</label>
              <input type="date" class="form-control" v-model="dashboardFilter.start_date" @change="fetchDashboard" />
            </div>

            <div v-if="dashboardFilter.period === 'custom'" class="filter-field">
              <label class="form-label small text-muted">End Date</label>
              <input type="date" class="form-control" v-model="dashboardFilter.end_date" @change="fetchDashboard" />
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Metrics -->
    <section class="dashboard-metric-section mt-2">
      <div class="dashboard-metric-section__header">
        <div>
          <span class="section-kicker">Workflow Status</span>
          <h5 class="mb-0">Request movement</h5>
        </div>
        <BBadge class="bg-primary-subtle text-primary rounded-pill">{{ filteredPeriodLabel }}</BBadge>
      </div>

      <BRow class="g-2 mb-3">
        <BCol xl="3" md="6" v-for="(metric, i) in workflowMetrics" :key="`workflow-${i}`">
          <BCard class="metric-card h-100" :style="{ '--metric-accent': metric.accentColor || '#405189' }">
            <BCardBody>
              <div class="d-flex align-items-center gap-2">
                <div class="metric-icon" :class="[metric.bgClass, metric.textClass]">
                  <i :class="metric.icon"></i>
                </div>

                <div class="min-w-0">
                  <p class="text-uppercase text-muted fw-semibold fs-12 mb-1 text-truncate">
                    {{ metric.label }}
                  </p>
                  <h4 class="fw-bold mb-1">{{ metric.value }}</h4>
                  <p class="text-muted fs-12 mb-0">{{ metric.note }}</p>
                </div>
              </div>
            </BCardBody>
          </BCard>
        </BCol>
      </BRow>
    </section>

    <section class="dashboard-metric-section">
      <div class="dashboard-metric-section__header">
        <div>
          <span class="section-kicker">Budget Snapshot</span>
          <h5 class="mb-0">Amounts and balances</h5>
        </div>
        <BBadge class="bg-success-subtle text-success rounded-pill">Financial</BBadge>
      </div>

      <BRow class="g-2 mb-3">
        <BCol xl="3" md="6" v-for="(metric, i) in financialMetrics" :key="`financial-${i}`">
          <BCard class="metric-card h-100" :style="{ '--metric-accent': metric.accentColor || '#405189' }">
            <BCardBody>
              <div class="d-flex align-items-center gap-2">
                <div class="metric-icon" :class="[metric.bgClass, metric.textClass]">
                  <i :class="metric.icon"></i>
                </div>

                <div class="min-w-0">
                  <p class="text-uppercase text-muted fw-semibold fs-12 mb-1 text-truncate">
                    {{ metric.label }}
                  </p>
                  <h4 class="fw-bold mb-1">{{ metric.value }}</h4>
                  <p class="text-muted fs-12 mb-0">{{ metric.note }}</p>
                </div>
              </div>
            </BCardBody>
          </BCard>
        </BCol>
      </BRow>
    </section>


    <BRow class="g-2">
      <BCol  v-for="module in workspaceModules" :key="module.key" xl="4" md="6">
        <BCard class="module-card" :style="{ '--module-accent': module.accentColor || '#405189' }">
          <BCardBody>
            <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
              <div class="module-icon" :class="[module.iconBgClass, module.iconTextClass]">
                <i :class="module.icon"></i>
              </div>

              <BBadge class="bg-primary-subtle text-primary rounded-pill">
                {{ module.tag }}
              </BBadge>
            </div>

            <p class="fw-bold text-dark mb-1">{{ module.title }}</p>

            <h4 class="fw-bold text-primary mb-1 module-value fs-10" :class="module.isTextValue ? 'fs-5' : 'fs-10'">
              {{ module.value }}
            </h4>

            <p class="module-note text-muted mb-1">{{ module.note }}</p>

            <BButton variant="soft-primary" class="module-action" @click="openModule(module.route)">
              <span>{{ module.action }}</span>
              <i class="ri-arrow-right-line"></i>
            </BButton>
          </BCardBody>
        </BCard>
      </BCol>
    </BRow>

    <!-- Charts -->
    <BRow class="g-2 mb-2">
      <BCol xl="12">
        <BCard class="panel-card h-100">
          <BCardHeader>
            <h5><i class="ri-bar-chart-line me-2"></i>Procurement Trends</h5>
            <p>Request volume for {{ filteredPeriodLabel }}</p>
          </BCardHeader>

          <BCardBody class="chart-body">
            <apexchart type="bar" height="300" :options="monthlyChartOptions" :series="monthlyChartSeries" />
          </BCardBody>
        </BCard>
      </BCol>


    </BRow>

    <!-- Unit Breakdown -->
    <BCard class="panel-card mb-2">
      <BCardHeader class="d-flex justify-content-between align-items-center">
        <div>
          <h5><i class="ri-bar-chart-horizontal-line me-2"></i>Unit Breakdown</h5>
          <p>Procurement activity by unit</p>
        </div>

        <BBadge class="bg-primary-subtle text-primary px-3 py-2">
          {{ sortedDivisionDistribution.length }} Units
        </BBadge>
      </BCardHeader>

      <BCardBody class="chart-body">
      
        <div v-if="sortedDivisionDistribution.length">
          <div class="unit-breakdown-chart-scroll">
            <div class="unit-breakdown-chart-canvas" :style="{ minWidth: `${unitBreakdownChartWidth}px` }">
              <apexchart
                type="bar"
                :height="unitBreakdownChartHeight"
                :options="unitBreakdownChartOptions"
                :series="unitBreakdownChartSeries"
              />
            </div>
          </div>

          <div class="unit-breakdown-footer mt-2">
            <div class="unit-breakdown-stat">
              <span>Total Procurements</span>
              <strong>{{ divisionTotal }}</strong>
              <small>Recorded requests across visible units</small>
            </div>

            <div class="unit-breakdown-stat">
              <span>Total Amount Purchase Req</span>
              <strong>{{ formatCompactCurrency(totalApprovedBudgetAmount) }}</strong>
              <small>{{ formatCurrency(totalApprovedBudgetAmount) }}</small>
            </div>

            <div class="unit-breakdown-stat">
              <span>Approved Budget for Contract</span>
              <strong>{{ formatCompactCurrency(totalActualAwardedAmount) }}</strong>
              <small>Completed items only</small>
            </div>

            <div class="unit-breakdown-stat">
              <span>Total Excess Funds</span>
              <strong :class="totalExcessFundsAmount < 0 ? 'text-danger' : 'text-success'">
                {{ formatCompactCurrency(totalExcessFundsAmount) }}
              </strong>
              <small>{{ formatCurrency(totalExcessFundsAmount) }}</small>
            </div>

            <div class="unit-breakdown-stat">
              <span>Top Unit</span>
              <strong>{{ topDivision.name }}</strong>
              <small>{{ topDivision.count }} procurements</small>
            </div>
          </div>
        </div>

        <div v-else class="empty-state py-3">
          <i class="ri-inbox-line"></i>
          <p>No unit data available</p>
        </div>
      </BCardBody>
    </BCard>

    <!-- Recent + Insights -->
    <BRow class="g-2 mb-2">
      <BCol xl="7">
        <BCard class="panel-card h-100">
          <BCardHeader class="d-flex justify-content-between align-items-center">
            <div>
              <h5><i class="ri-time-line me-2"></i>Recent Procurements</h5>
              <p>Latest requests captured in the procurement workspace</p>
            </div>

            <BButton size="sm" variant="outline-primary" @click="goViewAll">
              View All
            </BButton>
          </BCardHeader>

          <BCardBody class="recent-table-body">
            <div class="table-responsive">
              <table class="table align-middle table-hover mb-0">
                <thead class="table-light">
                  <tr>
                    <th>Code</th>
                    <th>Purpose</th>
                    <th>Division</th>
                    <th>Status</th>
                    <th>Date</th>
                  </tr>
                </thead>

                <tbody>
                  <tr v-for="(list, index) in lists" :key="index" class="cursor-pointer" @click="goViewPage(list)">
                    <td class="fw-semibold text-primary">{{ list.code }}</td>

                    <td>
                      <div class="text-truncate" style="max-width: 240px" v-b-tooltip.hover :title="list.purpose">
                        {{ list.purpose }}
                      </div>
                    </td>

                    <td>{{ list.division?.name || 'N/A' }}</td>

                    <td>
                      <b-badge :class="list.status?.bg" class="fs-11">
                        {{ list.status?.name }}
                      </b-badge>
                    </td>

                    <td class="text-muted">{{ formatDate(list.date) }}</td>
                  </tr>

                  <tr v-if="lists.length === 0">
                    <td colspan="5" class="text-center text-muted py-4">
                      No recent procurements found.
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </BCardBody>
        </BCard>
      </BCol>

      <BCol xl="5">
        <BCard class="panel-card insights-card h-100">
          <BCardHeader>
            <h5><i class="ri-lightbulb-line me-2"></i>Insights</h5>
            <p>Operational snapshot of the current workload</p>
          </BCardHeader>

          <BCardBody>
            <div class="insight-highlight mb-2">
              <div>
                <p class="text-muted mb-1">Completion Snapshot</p>
                <h3 class="fw-bold mb-1">{{ completionRate }}%</h3>
                <p class="text-muted fs-12 mb-0">
                  {{ dashboard.completed_procurements }} of {{ dashboard.total_procurements }} requests completed
                </p>
              </div>

              <div class="insight-icon">
                <i class="ri-pulse-line"></i>
              </div>
            </div>

            <div class="progress bg-primary-subtle mb-2" style="height: 8px;">
              <div
                class="progress-bar bg-primary"
                :style="{ width: completionRate + '%' }"
                :aria-valuenow="completionRate"
                aria-valuemin="0"
                aria-valuemax="100"
              ></div>
            </div>

            <div class="insight-grid">
              <div class="insight-item">
                <span>Open Requests</span>
                <strong>{{ openRequests }}</strong>
                <small>Still moving through the process</small>
              </div>

              <div class="insight-item">
                <span>Top Unit</span>
                <strong>{{ topDivision.name }}</strong>
                <small>{{ topDivision.count }} procurements</small>
              </div>

              <div class="insight-item">
                <span>Review Queue</span>
                <strong>{{ dashboard.for_reviews }}</strong>
                <small>Pending review items</small>
              </div>

              <div class="insight-item">
                <span>Approval Queue</span>
                <strong>{{ dashboard.for_approvals }}</strong>
                <small>Awaiting approval sign-off</small>
              </div>
            </div>
          </BCardBody>
        </BCard>
      </BCol>
    </BRow>
  </div>
</template>

<script>
import { Head } from '@inertiajs/vue3';
import PageHeader from '@/Shared/Components/PageHeader.vue';
import Pagination from '@/Shared/Components/Pagination.vue';
import Multiselect from '@vueform/multiselect';
import VueApexCharts from 'vue3-apexcharts';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import _ from 'lodash';

export default {
  components: {
    Head,
    PageHeader,
    Pagination,
    Multiselect,
    apexchart: VueApexCharts,
  },

  props: {
    dropdowns: Object,
  },

  data() {
    return {
      dashboard: {
        total_procurements: 0,
        for_reviews: 0,
        for_approvals: 0,
        completed_procurements: 0,
        total_assignments: 0,
        total_pap_codes: 0,
        total_remaining_pap_budget: 0,
        total_allocated_pap_budget: 0,
        total_responsibility_centers: 0,
        total_modes_of_procurement: 0,
        active_modes_of_procurement: 0,
        total_suppliers: 0,
        active_suppliers: 0,
        pending_supplier_approvals: 0,
        total_quotations: 0,
        total_bac_resolutions: 0,
        total_notice_of_awards: 0,
        total_purchase_orders: 0,
        total_purchase_order_amount: 0,
        total_approved_budget_amount: 0,
        total_completed_awarded_amount: 0,
        total_excess_funds: 0,
        recent_procurements: [],
        monthly_trends: [],
        division_distribution: [],
      },
      lists: [],
      meta: null,
      links: null,
      lastUpdated: null,
      filter: {
        keyword: null,
        status: null,
        type: null,
        mode: null,
      },
      dashboardFilter: {
        period: 'all',
        year: new Date().getFullYear(),
        month: new Date().getMonth() + 1,
        quarter: 1,
        start_date: null,
        end_date: null,
      },
      periodOptions: [
        { value: 'all', label: 'All Time' },
        { value: 'today', label: 'Today' },
        { value: 'weekly', label: 'Weekly' },
        { value: 'monthly', label: 'Monthly' },
        { value: 'quarterly', label: 'Quarterly' },
        { value: 'yearly', label: 'Yearly' },
        { value: 'custom', label: 'Custom Date Range' },
      ],
      monthlyChartOptions: {
        chart: {
          type: 'bar',
          height: 350,
          toolbar: {
            show: false,
          },
          foreColor: '#405189',
        },
        plotOptions: {
          bar: {
            horizontal: false,
            columnWidth: '55%',
            endingShape: 'rounded',
          },
        },
        dataLabels: {
          enabled: false,
        },
        stroke: {
          show: true,
          width: 2,
          colors: ['transparent'],
        },
        xaxis: {
          categories: [],
        },
        yaxis: {
          min: 0,
          max: 20,
          stepSize: 20,
          tickAmount: 1,
          decimalsInFloat: 0,
          title: {
            text: 'Number of Procurements',
          },
          labels: {
            formatter: function (value) {
              const tick = Math.round(Number(value) || 0);
              return tick === 0 ? '' : tick.toLocaleString();
            },
          },
        },
        fill: {
          opacity: 1,
        },
        colors: ['#405189'],
        tooltip: {
          y: {
            formatter: function (val) {
              return `${Number(val || 0).toLocaleString()} procurements`;
            },
          },
        },
      },
      monthlyChartSeries: [{
        name: 'Procurements',
        data: [],
      }],
      unitSummaryChartOptions: {
        chart: {
          type: 'treemap',
          height: 320,
          fontFamily: 'inherit',
          toolbar: {
            show: false,
          },
        },
        colors: ['#405189', '#5c6bc0', '#7986cb', '#9fa8da', '#c5cae9', '#0ab39c', '#20c997', '#ffc107', '#6b7299'],
        legend: {
          show: false,
        },
        grid: {
          show: false,
        },
        plotOptions: {
          treemap: {
            distributed: true,
            enableShades: false,
            borderRadius: 8,
          },
        },
        dataLabels: {
          enabled: true,
          formatter: function (text, opts) {
            const label = text && text.length > 18 ? `${text.slice(0, 18)}...` : (text || 'Unassigned');
            return [label, `${opts.value} req`];
          },
          dropShadow: {
            enabled: false,
          },
          style: {
            fontSize: '11px',
            fontWeight: '600',
            colors: ['#ffffff'],
          },
        },
        stroke: {
          show: true,
          width: 4,
          colors: ['#fff'],
        },
        states: {
          hover: {
            filter: {
              type: 'lighten',
              value: 0.08,
            },
          },
          active: {
            filter: {
              type: 'none',
            },
          },
        },
        tooltip: {
          enabled: true,
          fillSeriesColor: false,
          theme: 'light',
          style: {
            fontSize: '12px',
          },
          custom: function({ seriesIndex, dataPointIndex, w }) {
            const point = w.config.series?.[seriesIndex]?.data?.[dataPointIndex] || {};
            const count = Number(point.y) || 0;
            const amount = Number(point.distributedAmount) || 0;
            const awardedAmount = Number(point.actualAwardedAmount) || 0;
            const share = Number(point.share) || 0;
            const amountLabel = new Intl.NumberFormat('en-PH', {
              style: 'currency',
              currency: 'PHP',
              minimumFractionDigits: 2,
            }).format(amount);
            const awardedAmountLabel = new Intl.NumberFormat('en-PH', {
              style: 'currency',
              currency: 'PHP',
              minimumFractionDigits: 2,
            }).format(awardedAmount);

            return [
              '<div style="padding: 10px 12px; min-width: 220px;">',
              `<div style="font-size: 13px; font-weight: 700; color: #0f172a;">${point.x || 'Unassigned'}</div>`,
              point.divisionLabel ? `<div style="font-size: 11px; color: #64748b; margin-top: 2px;">${point.divisionLabel}</div>` : '',
              `<div style="margin-top: 10px; font-size: 12px; color: #334155;"><strong>${count}</strong> procurements</div>`,
              `<div style="margin-top: 4px; font-size: 12px; color: #334155;">${share}% request share</div>`,
              `<div style="margin-top: 4px; font-size: 12px; color: #334155;">${amountLabel} total PR amount</div>`,
              `<div style="margin-top: 4px; font-size: 12px; color: #334155;">${awardedAmountLabel} approved budget for contract</div>`,
              '</div>',
            ].join('');
          },
        },
        responsive: [
          {
            breakpoint: 1200,
            options: {
              chart: { height: 300 },
              dataLabels: {
                style: {
                  fontSize: '10px',
                },
              },
            },
          },
          {
            breakpoint: 768,
            options: {
              chart: { height: 280 },
              dataLabels: {
                formatter: function (text) {
                  if (!text) {
                    return ['Unassigned'];
                  }
                  return [text.length > 12 ? `${text.slice(0, 12)}...` : text];
                },
              },
            },
          },
        ],
      },
      unitSummaryChartSeries: [{
        name: 'Unit Distribution',
        data: [],
      }],
      unitBreakdownChartOptions: {
        chart: {
          type: 'bar',
          height: 360,
          toolbar: {
            show: false,
          },
          fontFamily: 'inherit',
          foreColor: '#475569',
        },
        plotOptions: {
          bar: {
            horizontal: true,
            borderRadius: 8,
            barHeight: '58%',
            dataLabels: {
              position: 'top',
            },
          },
        },
        grid: {
          borderColor: '#e2e8f0',
          strokeDashArray: 4,
          xaxis: {
            lines: {
              show: true,
            },
          },
          yaxis: {
            lines: {
              show: false,
            },
          },
          padding: {
            left: 20,
            right: 18,
          },
        },
        dataLabels: {
          enabled: true,
          textAnchor: 'start',
          offsetX: 10,
          style: {
            fontSize: '12px',
            fontWeight: '700',
            colors: ['#405189'],
          },
          formatter: function (val) {
            return `${val}`;
          },
        },
        stroke: {
          show: false,
        },
        fill: {
          type: 'gradient',
          gradient: {
            shade: 'light',
            type: 'horizontal',
            shadeIntensity: 0.2,
            inverseColors: false,
            opacityFrom: 1,
            opacityTo: 0.82,
            stops: [0, 100],
          },
        },
        colors: ['#405189'],
        xaxis: {
          min: 0,
          max: 20,
          stepSize: 20,
          tickAmount: 1,
          decimalsInFloat: 0,
          title: {
            text: 'Number of Procurements',
            style: {
              color: '#64748b',
              fontSize: '12px',
              fontWeight: 600,
            },
          },
          labels: {
            style: {
              fontSize: '11px',
              colors: ['#64748b'],
            },
            formatter: function (value) {
              const tick = Math.round(Number(value) || 0);
              return tick === 0 ? '' : tick.toLocaleString();
            },
          },
        },
        yaxis: {
          labels: {
            maxWidth: 360,
            style: {
              fontSize: '11px',
              fontWeight: 600,
              colors: ['#0f172a'],
            },
            formatter: function (value) {
              return value || 'Unassigned';
            },
          },
        },
        legend: {
          show: false,
        },
        tooltip: {
          theme: 'light',
          custom: function({ seriesIndex, dataPointIndex, w }) {
            const point = w.config.series?.[seriesIndex]?.data?.[dataPointIndex] || {};
            const count = Number(point.y) || 0;
            const amount = Number(point.distributedAmount) || 0;
            const awardedAmount = Number(point.actualAwardedAmount) || 0;
            const share = Number(point.share) || 0;
            const amountLabel = new Intl.NumberFormat('en-PH', {
              style: 'currency',
              currency: 'PHP',
              minimumFractionDigits: 2,
            }).format(amount);
            const awardedAmountLabel = new Intl.NumberFormat('en-PH', {
              style: 'currency',
              currency: 'PHP',
              minimumFractionDigits: 2,
            }).format(awardedAmount);

            return [
              '<div style="padding: 10px 12px; min-width: 220px;">',
              `<div style="font-size: 13px; font-weight: 700; color: #0f172a;">${point.x || 'Unassigned'}</div>`,
              point.divisionLabel ? `<div style="font-size: 11px; color: #64748b; margin-top: 2px;">${point.divisionLabel}</div>` : '',
              `<div style="margin-top: 5px; font-size: 12px; color: #334155;"><strong>${count}</strong> procurements</div>`,
              `<div style="margin-top: 2px; font-size: 12px; color: #334155;">${amountLabel} total PR amount</div>`,
              `<div style="margin-top: 2px; font-size: 12px; color: #334155;">${awardedAmountLabel} approved budget for contract</div>`,
              `<div style="margin-top: 2px; font-size: 12px; color: #334155;">${share}% of total requests</div>`,
              '</div>',
            ].join('');
          },
        },
        responsive: [
          {
            breakpoint: 768,
            options: {
              grid: {
                padding: {
                  left: 0,
                  right: 8,
                },
              },
              yaxis: {
                labels: {
                  maxWidth: 260,
                },
              },
              dataLabels: {
                offsetX: 6,
              },
            },
          },
        ],
      },
      unitBreakdownChartSeries: [{
        name: 'Procurements',
        data: [],
      }],
      yearOptions: [],
      monthOptions: [
        { value: 1, label: 'January' },
        { value: 2, label: 'February' },
        { value: 3, label: 'March' },
        { value: 4, label: 'April' },
        { value: 5, label: 'May' },
        { value: 6, label: 'June' },
        { value: 7, label: 'July' },
        { value: 8, label: 'August' },
        { value: 9, label: 'September' },
        { value: 10, label: 'October' },
        { value: 11, label: 'November' },
        { value: 12, label: 'December' },
      ],
      quarterOptions: [
        { value: 1, label: '1st Quarter' },
        { value: 2, label: '2nd Quarter' },
        { value: 3, label: '3rd Quarter' },
        { value: 4, label: '4th Quarter' },
      ],
    };
  },

  mounted() {
    this.generateYearOptions();
    this.fetchDashboard();
    this.fetch();
  },

  computed: {
    isQuarterSelected() {
      return ['q1', 'q2', 'q3', 'q4'].includes(this.dashboardFilter.period);
    },
		userRoles() {
			return Array.isArray(this.$page?.props?.roles) ? this.$page.props.roles : [];
		},
		openRequests() {
			const total = Number(this.dashboard.total_procurements) || 0;
			const completed = Number(this.dashboard.completed_procurements) || 0;
			return Math.max(total - completed, 0);
		},
		activeUnitsCount() {
			return this.sortedDivisionDistribution.length;
		},
		filteredPeriodLabel() {
			const period = this.dashboardFilter.period;
			if (period === 'monthly') {
				const selectedMonth = this.monthOptions.find((item) => item.value === this.dashboardFilter.month);
				return `${selectedMonth ? selectedMonth.label : 'Month'} ${this.dashboardFilter.year}`;
			}
			if (period === 'quarterly') {
				return `Q${this.dashboardFilter.quarter} ${this.dashboardFilter.year}`;
			}
			if (period === 'yearly') {
				return `${this.dashboardFilter.year}`;
			}
			if (period === 'custom') {
				if (this.dashboardFilter.start_date && this.dashboardFilter.end_date) {
					return `${this.formatDate(this.dashboardFilter.start_date)} - ${this.formatDate(this.dashboardFilter.end_date)}`;
				}
				return 'Custom Date Range';
			}
			const selectedPeriod = this.periodOptions.find((item) => item.value === period);
			return selectedPeriod ? selectedPeriod.label : 'All Time';
		},
		metrics() {
			return [
				{
					label: 'Total Purchase Requests',
					value: this.dashboard.total_procurements,
					note: `Captured in ${this.filteredPeriodLabel}`,
					icon: 'ri-stack-line',
					bgClass: 'bg-primary-subtle',
					textClass: 'text-primary',
					accentColor: '#405189',
				},
				{
					label: 'For Reviews',
					value: this.dashboard.for_reviews,
					note: 'Waiting for reviewer action',
					icon: 'ri-search-eye-line',
					bgClass: 'bg-warning-subtle',
					textClass: 'text-warning',
					accentColor: '#f7b84b',
				},
				{
					label: 'For Approvals',
					value: this.dashboard.for_approvals,
					note: 'Ready for approval sign-off',
					icon: 'ri-shield-check-line',
					bgClass: 'bg-info-subtle',
					textClass: 'text-info',
					accentColor: '#299cdb',
				},
				{
					label: 'Completed',
					value: this.dashboard.completed_procurements,
					note: `${this.completionRate}% completion rate`,
					icon: 'ri-check-double-line',
					bgClass: 'bg-success-subtle',
					textClass: 'text-success',
					accentColor: '#0ab39c',
				},
				{
					label: 'Total Amount Purchase Request(PR)',
					value: this.formatCompactCurrency(this.totalApprovedBudgetAmount),
					note: `${this.dashboard.total_procurements} purchase requests in ${this.filteredPeriodLabel}`,
					icon: 'ri-money-dollar-circle-line',
					bgClass: 'bg-primary-subtle',
					textClass: 'text-primary',
					accentColor: '#405189',
				},
				{
					label: 'Approved Budget for Contract(ABC)',
					value: this.formatCompactCurrency(this.totalActualAwardedAmount),
					note: 'Completed awarded contract amount',
					icon: 'ri-shield-star-line',
					bgClass: 'bg-warning-subtle',
					textClass: 'text-warning',
					accentColor: '#f7b84b',
				},
				{
					label: 'Total Amount Purchase Order(PO)',
					value: this.formatCompactCurrency(this.dashboard.total_purchase_order_amount),
					note: `${this.dashboard.total_purchase_orders} purchase orders in ${this.filteredPeriodLabel}`,
					icon: 'ri-file-paper-2-line',
					bgClass: 'bg-info-subtle',
					textClass: 'text-info',
					accentColor: '#299cdb',
				},
				{
					label: 'Remaining Balance',
					value: this.formatCompactCurrency(this.dashboard.total_remaining_pap_budget),
					note: `From ${this.formatCompactCurrency(this.dashboard.total_allocated_pap_budget)} allocated PAP budget`,
					icon: 'ri-wallet-3-line',
					bgClass: 'bg-success-subtle',
					textClass: 'text-success',
					accentColor: '#0ab39c',
				},
			];
		},
		workflowMetrics() {
			return this.metrics.slice(0, 4);
		},
		financialMetrics() {
			return this.metrics.slice(4);
		},
		workspaceModules() {
			const modules = [
				{
					key: 'pap_codes',
					title: 'PAP Codes',
					value: this.dashboard.total_pap_codes,
					note: `${this.formatCompactCurrency(this.dashboard.total_remaining_pap_budget)} remaining budget`,
					route: '/faims/procurement-codes',
					action: 'Open PAP codes',
					icon: 'ri-code-box-line',
					iconBgClass: 'bg-success-subtle',
					iconTextClass: 'text-success',
					tag: 'Budget',
					accentColor: '#0ab39c',
					roles: ['Budget Officer', 'Procurement Officer', 'Administrator'],
				},
				{
					key: 'responsibility_centers',
					title: 'Responsibility Centers',
					value: this.dashboard.total_responsibility_centers,
					note: `${this.dashboard.total_responsibility_centers} unit-to-center mappings available for coding`,
					route: '/faims/responsibility-centers',
					action: 'Open centers',
					icon: 'ri-building-line',
					iconBgClass: 'bg-info-subtle',
					iconTextClass: 'text-info',
					tag: 'Structure',
					accentColor: '#299cdb',
					roles: ['Procurement Officer', 'Administrator'],
				},
				{
					key: 'modes',
					title: 'Modes of Procurement',
					value: this.dashboard.total_modes_of_procurement,
					note: `${this.dashboard.active_modes_of_procurement} active modes currently available for PAP code setup`,
					route: '/faims/modes-of-procurement',
					action: 'Open modes',
					icon: 'ri-git-branch-line',
					iconBgClass: 'bg-warning-subtle',
					iconTextClass: 'text-warning',
					tag: 'Reference',
					accentColor: '#f7b84b',
					roles: ['Procurement Staff', 'Procurement Officer', 'Administrator'],
				},
				{
					key: 'suppliers',
					title: 'Suppliers',
					value: this.dashboard.total_suppliers,
					note: `${this.dashboard.active_suppliers} active suppliers, ${this.dashboard.pending_supplier_approvals} waiting for approval`,
					route: '/faims/suppliers',
					action: 'Open suppliers',
					icon: 'ri-truck-line',
					iconBgClass: 'bg-secondary-subtle',
					iconTextClass: 'text-secondary',
					tag: 'Vendors',
					accentColor: '#6c757d',
					roles: ['Procurement Staff', 'Procurement Officer', 'Administrator'],
				},
				{
					key: 'bac_resolutions',
					title: 'BAC Resolutions',
					value: this.dashboard.total_bac_resolutions,
					note: `${this.dashboard.total_bac_resolutions} BAC resolutions recorded in ${this.filteredPeriodLabel}`,
					route: '/faims/bac-resolutions',
					action: 'Open BAC resolutions',
					icon: 'ri-government-line',
					iconBgClass: 'bg-danger-subtle',
					iconTextClass: 'text-danger',
					tag: 'Records',
					accentColor: '#f06548',
					roles: ['Procurement Staff', 'Procurement Officer', 'Administrator'],
				},
				{
					key: 'notice_of_awards',
					title: 'Notice of Awards',
					value: this.dashboard.total_notice_of_awards,
					note: `${this.dashboard.total_notice_of_awards} award notices prepared in ${this.filteredPeriodLabel}`,
					route: '/faims/notice-of-awards',
					action: 'Open notice of awards',
					icon: 'ri-file-text-line',
					iconBgClass: 'bg-primary-subtle',
					iconTextClass: 'text-primary',
					tag: 'Awards',
					accentColor: '#405189',
					roles: ['Procurement Staff', 'Procurement Officer', 'Administrator'],
				},
				{
					key: 'purchase_orders',
					title: 'Total Amount PO',
					value: this.formatCompactCurrency(this.dashboard.total_purchase_order_amount),
					note: `${this.dashboard.total_purchase_orders} purchase orders released in ${this.filteredPeriodLabel}`,
					route: '/faims/purchase-orders',
					action: 'Open purchase orders',
					icon: 'ri-file-paper-2-line',
					iconBgClass: 'bg-dark-subtle',
					iconTextClass: 'text-dark',
					tag: 'Orders',
					accentColor: '#212529',
					roles: ['Procurement Staff', 'Procurement Officer', 'Administrator'],
				},
				{
					key: 'reports',
					title: 'Procurement Reports',
					value: this.dashboard.total_procurements,
					note: `${this.dashboard.total_procurements} request records ready for reporting in ${this.filteredPeriodLabel}`,
					route: '/faims/procurement-reports',
					action: 'Open reports',
					icon: 'ri-bar-chart-box-line',
					iconBgClass: 'bg-success-subtle',
					iconTextClass: 'text-success',
					tag: 'Reports',
					accentColor: '#0ab39c',
					roles: ['Procurement Staff', 'Procurement Officer', 'Administrator'],
				},
			];

			return modules.filter((module) => this.hasAnyRole(module.roles));
		},
		completionRate() {
			if (!this.dashboard.total_procurements) {
				return 0;
			}
			return Math.round((this.dashboard.completed_procurements / this.dashboard.total_procurements) * 100);
		},
		topDivision() {
			if (!this.dashboard.division_distribution || this.dashboard.division_distribution.length === 0) {
				return { name: 'N/A', count: 0 };
			}
			const sorted = [...this.dashboard.division_distribution].sort((a, b) => b.count - a.count);
			const top = sorted[0];
			return { name: this.getUnitLabel(top), count: top.count };
		},
		sortedDivisionDistribution() {
			const source = this.dashboard.division_distribution || [];
			if (source.length === 0) {
				return [];
			}
			const total = source.reduce((sum, item) => sum + (Number(item.count) || 0), 0) || 1;
			return [...source]
				.sort((a, b) => (b.count || 0) - (a.count || 0))
				.map((item) => {
					const count = Number(item.count) || 0;
					const distributedAmount = Number(item.distributed_amount) || 0;
					const actualAwardedAmount = Number(item.completed_awarded_amount) || 0;
					const share = Math.round((count / total) * 100);
					return { ...item, count, distributed_amount: distributedAmount, completed_awarded_amount: actualAwardedAmount, share, unit_label: this.getUnitLabel(item) };
				});
		},
		divisionTotal() {
			const source = this.dashboard.division_distribution || [];
			return source.reduce((sum, item) => sum + (Number(item.count) || 0), 0);
		},
		divisionTotalAmount() {
			const source = this.dashboard.division_distribution || [];
			return source.reduce((sum, item) => sum + (Number(item.distributed_amount) || 0), 0);
		},
		totalApprovedBudgetAmount() {
			return Number(this.dashboard.total_approved_budget_amount ?? this.divisionTotalAmount) || 0;
		},
		totalActualAwardedAmount() {
			const source = this.dashboard.division_distribution || [];
			if (this.dashboard.total_completed_awarded_amount !== undefined && this.dashboard.total_completed_awarded_amount !== null) {
				return Number(this.dashboard.total_completed_awarded_amount) || 0;
			}
			return source.reduce((sum, item) => sum + (Number(item.completed_awarded_amount) || 0), 0);
		},
		totalExcessFundsAmount() {
			if (this.dashboard.total_excess_funds !== undefined && this.dashboard.total_excess_funds !== null) {
				return Number(this.dashboard.total_excess_funds) || 0;
			}

			return this.totalApprovedBudgetAmount - this.totalActualAwardedAmount;
		},
		monthlyTrendMaxProcurementCount() {
			const source = this.dashboard.monthly_trends || [];
			return Math.max(...source.map((item) => Number(item.count) || 0), 0);
		},
		monthlyTrendAxisStep() {
			return this.getProcurementAxisStep(this.monthlyTrendMaxProcurementCount);
		},
		monthlyTrendAxisMax() {
			return this.getProcurementAxisMax(this.monthlyTrendMaxProcurementCount, this.monthlyTrendAxisStep);
		},
		unitBreakdownChartHeight() {
			const itemCount = this.sortedDivisionDistribution.length;
			return Math.min(Math.max((itemCount * 52) + 56, 280), 700);
		},
		unitBreakdownMaxProcurementCount() {
			return Math.max(
				...this.sortedDivisionDistribution.map((item) => Number(item.count) || 0),
				0
			);
		},
		unitBreakdownAxisStep() {
			return this.getProcurementAxisStep(this.unitBreakdownMaxProcurementCount);
		},
		unitBreakdownAxisMax() {
			return this.getProcurementAxisMax(this.unitBreakdownMaxProcurementCount, this.unitBreakdownAxisStep);
		},
		unitBreakdownChartWidth() {
			const tickCount = this.unitBreakdownAxisMax / this.unitBreakdownAxisStep;
			return Math.max(820, 360 + (tickCount * 56));
		},
  },

  methods: {
		getProcurementAxisStep(maxProcurementCount) {
			if (maxProcurementCount <= 200) {
				return 20;
			}

			const targetTickCount = 10;
			const rawStep = Math.ceil(maxProcurementCount / targetTickCount);
			const magnitude = 10 ** Math.floor(Math.log10(rawStep));
			const normalizedStep = rawStep / magnitude;

			if (normalizedStep <= 2) {
				return 2 * magnitude;
			}
			if (normalizedStep <= 5) {
				return 5 * magnitude;
			}
			return 10 * magnitude;
		},
		getProcurementAxisMax(maxProcurementCount, axisStep) {
			return Math.max(axisStep, Math.ceil(maxProcurementCount / axisStep) * axisStep);
		},
    fetchDashboard() {
      const params = {
        period: this.dashboardFilter.period,
        start_date: this.dashboardFilter.start_date,
        end_date: this.dashboardFilter.end_date,
      };
      if (this.isQuarterSelected || this.dashboardFilter.period === 'monthly' || this.dashboardFilter.period === 'quarterly' || this.dashboardFilter.period === 'yearly') {
        params.year = this.dashboardFilter.year;
      }
      if (this.dashboardFilter.period === 'monthly') {
        params.month = this.dashboardFilter.month;
      }
      if (this.dashboardFilter.period === 'quarterly') {
        params.quarter = this.dashboardFilter.quarter;
      }
      axios.get('/faims/procurements?option=dashboard', { params })
      .then((response) => {
        if (response.data) {
          this.dashboard = response.data;
					this.lastUpdated = new Date();
          this.updateCharts();
        }
      })
      .catch((err) => console.log(err));
    },

    updateCharts() {
      // Update monthly chart
      if (this.dashboard && this.dashboard.monthly_trends && Array.isArray(this.dashboard.monthly_trends)) {
        const axisMax = this.monthlyTrendAxisMax;
        const axisStep = this.monthlyTrendAxisStep;

        this.monthlyChartOptions = {
          ...this.monthlyChartOptions,
          xaxis: {
            ...this.monthlyChartOptions.xaxis,
            categories: this.dashboard.monthly_trends.map(item => item.label || item.month),
          },
          yaxis: {
            ...this.monthlyChartOptions.yaxis,
            max: axisMax,
            stepSize: axisStep,
            tickAmount: axisMax / axisStep,
          },
        };
        this.monthlyChartSeries = [{
          ...this.monthlyChartSeries[0],
          data: this.dashboard.monthly_trends.map(item => Number(item.count) || 0),
        }];
      }

      // Update unit summary chart
      if (this.dashboard && this.dashboard.division_distribution && Array.isArray(this.dashboard.division_distribution)) {
        const items = this.sortedDivisionDistribution;
        if (items.length > 0) {
          const axisMax = this.unitBreakdownAxisMax;
          const axisStep = this.unitBreakdownAxisStep;

          this.unitBreakdownChartOptions = {
            ...this.unitBreakdownChartOptions,
            xaxis: {
              ...this.unitBreakdownChartOptions.xaxis,
              max: axisMax,
              stepSize: axisStep,
              tickAmount: axisMax / axisStep,
            },
          };

          this.unitSummaryChartSeries = [{
            name: 'Unit Distribution',
            data: items.map((item) => ({
              x: this.getUnitLabel(item),
              y: item.count,
              share: item.share,
              distributedAmount: item.distributed_amount,
              actualAwardedAmount: item.completed_awarded_amount,
              divisionLabel: this.getUnitDivisionLabel(item),
            })),
          }];
          this.unitBreakdownChartSeries = [{
            name: 'Procurements',
            data: items.map((item) => ({
              x: this.getUnitLabel(item),
              y: item.count,
              distributedAmount: item.distributed_amount,
              actualAwardedAmount: item.completed_awarded_amount,
              share: item.share,
              divisionLabel: this.getUnitDivisionLabel(item),
            })),
          }];
        } else {
          this.unitBreakdownChartOptions = {
            ...this.unitBreakdownChartOptions,
            xaxis: {
              ...this.unitBreakdownChartOptions.xaxis,
              max: 20,
              stepSize: 20,
              tickAmount: 1,
            },
          };

          this.unitSummaryChartSeries = [{
            name: 'Unit Distribution',
            data: [],
          }];
          this.unitBreakdownChartSeries = [{
            name: 'Procurements',
            data: [],
          }];
        }
      }
    },

    fetch() {
      axios.get('/faims/procurements', {
        params: {
          keyword: this.filter.keyword,
          status: this.filter.status,
          type: this.filter.type,
          mode: this.filter.mode,
          count: 10,
          option: "lists",
        },
      })
      .then((response) => {
        if (response) {
          this.lists = response.data.data;
          this.meta = response.data.meta;
          this.links = response.data.links;
        }
      })
      .catch((err) => console.log(err));
    },

    formatDate(date) {
      return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
      });
    },

		formatCurrency(value) {
			const amount = Number(value) || 0;
			return new Intl.NumberFormat('en-PH', {
				style: 'currency',
				currency: 'PHP',
				minimumFractionDigits: 2,
			}).format(amount);
		},

		formatCompactCurrency(value) {
			return this.formatCurrency(value);
		},

		formatDateTime(date) {
			return new Date(date).toLocaleString('en-US', {
				year: 'numeric',
				month: 'short',
				day: 'numeric',
				hour: 'numeric',
				minute: '2-digit',
			});
		},

    goCreatePage() {
      router.get("/faims/procurements/create", { option: "create" });
    },

    goViewPage(data) {
      router.get("/faims/procurements/" + data.id, { option: "view" });
    },

		goViewAll() {
			router.get("/faims/procurements");
		},

    openPrint(data) {
      window.open(`/faims/procurements/${data.id}?option=print&type=procurement`);
    },

    refresh() {
      this.filter.expense = null;
      this.filter.mode = null;
      this.filter.keyword = null;
      this.fetch();
    },

		hasAnyRole(roles = []) {
			if (!roles.length) {
				return true;
			}

			return roles.some((role) => this.userRoles.includes(role));
		},

		resetDashboardFilters() {
			const currentDate = new Date();
			this.dashboardFilter = {
				period: 'all',
				year: currentDate.getFullYear(),
				month: currentDate.getMonth() + 1,
				quarter: Math.floor(currentDate.getMonth() / 3) + 1,
				start_date: null,
				end_date: null,
			};
			this.fetchDashboard();
		},

		openModule(route) {
			router.get(route);
		},

    onPeriodChange(selectedPeriod) {
      if (selectedPeriod) {
        this.dashboardFilter.period = selectedPeriod.value || selectedPeriod;
      }
      // Reset custom dates when changing period
      if (this.dashboardFilter.period !== 'custom') {
        this.dashboardFilter.start_date = null;
        this.dashboardFilter.end_date = null;
      }
      // Auto-fetch when period changes (except for custom which waits for date selection)
      if (this.dashboardFilter.period !== 'custom') {
        this.fetchDashboard();
      }
    },

    generateYearOptions() {
      const currentYear = new Date().getFullYear();
      this.yearOptions = [];
      for (let year = currentYear - 5; year <= currentYear + 1; year++) {
        this.yearOptions.push({ value: year, label: year.toString() });
      }
    },

		getUnitLabel(item) {
			if (!item) {
				return 'Unassigned';
			}
			return item.unit_name || item.unit || item.division || item.division_name || item.name || 'Unassigned';
		},

		getUnitDivisionLabel(item) {
			if (!item) {
				return 'Unassigned Division';
			}
			return item.division_name || item.division || 'Unassigned Division';
		},
  },
};
</script>

<style scoped>
.procurement-dashboard-page {
  --proc-brand: #405189;
  --proc-brand-dark: #344272;
  --proc-accent: #0ab39c;
  --proc-ink: #0f172a;
  --proc-muted: #64748b;
  --proc-border: #e8edf5;
  --proc-soft: rgba(64, 81, 137, 0.1);
  --proc-surface: rgba(255, 255, 255, .86);
  --proc-card: #ffffff;
  --proc-card-soft: #f8fafc;
  --proc-card-gradient: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
  --proc-panel-header: linear-gradient(180deg, #ffffff 0%, #fbfcff 100%);
  --proc-chart-bg: linear-gradient(180deg, rgba(248, 250, 252, .72), #ffffff 28%), #ffffff;
  --proc-table-row: #ffffff;
  --proc-table-hover: rgba(64, 81, 137, 0.06);
  --proc-input: #ffffff;
  --proc-shadow: rgba(15, 23, 42, .05);
  padding-bottom: 1.5rem;
}

:global([data-bs-theme="dark"]) .procurement-dashboard-page {
  --proc-brand: #93c5fd;
  --proc-brand-dark: #60a5fa;
  --proc-accent: #5eead4;
  --proc-ink: #e5edf7;
  --proc-muted: #9fb0c7;
  --proc-border: rgba(148, 163, 184, 0.18);
  --proc-soft: rgba(96, 165, 250, 0.14);
  --proc-surface: rgba(19, 29, 43, 0.92);
  --proc-card: #131d2b;
  --proc-card-soft: #182235;
  --proc-card-gradient: linear-gradient(180deg, #172235 0%, #101827 100%);
  --proc-panel-header: linear-gradient(180deg, #172235 0%, #131d2b 100%);
  --proc-chart-bg: linear-gradient(180deg, rgba(24, 34, 53, .72), #101827 30%), #101827;
  --proc-table-row: #182235;
  --proc-table-hover: rgba(96, 165, 250, 0.12);
  --proc-input: #101827;
  --proc-shadow: rgba(0, 0, 0, .28);
}

:global([data-bs-theme="dark"]) .procurement-dashboard-page :deep(.text-dark),
:global([data-bs-theme="dark"]) .procurement-dashboard-page :deep(.text-body) {
  color: var(--proc-ink) !important;
}

:global([data-bs-theme="dark"]) .procurement-dashboard-page :deep(.text-muted),
:global([data-bs-theme="dark"]) .procurement-dashboard-page :deep(.text-body-secondary) {
  color: var(--proc-muted) !important;
}

.procurement-hero {
  position: relative;
  border-radius: 22px;
  background:
    radial-gradient(circle at 8% 10%, rgba(255, 255, 255, .22), transparent 32%),
    radial-gradient(circle at 88% 22%, rgba(10, 179, 156, .32), transparent 28%),
    linear-gradient(135deg, #405189 0%, #344272 52%, #1f2a50 100%);
  box-shadow: 0 24px 58px rgba(64, 81, 137, 0.24);
}

.procurement-hero::after {
  position: absolute;
  inset: auto 28px 24px auto;
  width: 140px;
  height: 140px;
  content: "";
  border: 1px solid rgba(255, 255, 255, .18);
  border-radius: 32px;
  transform: rotate(14deg);
  opacity: .5;
}

.procurement-hero .card-body {
  position: relative;
  z-index: 1;
  padding: 1.35rem 1.5rem;
}

.procurement-hero .row {
  min-height: 180px;
  --bs-gutter-x: 1.5rem;
  --bs-gutter-y: .75rem;
}

.procurement-hero h3 {
  max-width: 760px;
  font-size: 1.95rem;
  line-height: 1.22;
  margin-bottom: .45rem !important;
}

.procurement-hero p {
  max-width: 620px;
  font-size: .95rem;
  line-height: 1.55;
  margin-bottom: .65rem !important;
}

.compact-card {
  padding: .8rem;
}

.dashboard-filter-card {
  border-radius: 14px !important;
  background: var(--proc-surface);
  border: 1px solid var(--proc-border) !important;
  box-shadow: 0 14px 34px var(--proc-shadow) !important;
  backdrop-filter: blur(12px);
}

.dashboard-filter-row {
  display: flex;
  align-items: end;
  justify-content: space-between;
  gap: .5rem;
}

.dashboard-filter-copy {
  min-width: 220px;
  display: flex;
  flex-direction: column;
  gap: .25rem;
}

.dashboard-filter-controls {
  flex: 1;
  display: flex;
  flex-wrap: wrap;
  gap: .35rem;
  align-items: end;
  justify-content: flex-end;
}

.filter-field {
  width: 200px;
  max-width: 100%;
  height: 100%;
  padding: .45rem .55rem;
  border: 1px solid var(--proc-border);
  border-radius: 9px;
  background: var(--proc-card-soft);
}

.filter-field .form-label {
  margin-bottom: .2rem;
  font-weight: 800;
  letter-spacing: .02em;
  text-transform: uppercase;
}

.filter-field .form-control {
  min-height: 32px;
  border-color: transparent;
  border-radius: 8px;
  background: var(--proc-input);
  color: var(--proc-ink);
}

.filter-field :deep(.multiselect) {
  min-height: 32px;
  border-color: transparent;
  border-radius: 8px;
  background: var(--proc-input);
  color: var(--proc-ink);
  box-shadow: none;
}

.filter-field :deep(.multiselect-dropdown),
.filter-field :deep(.multiselect-options),
.filter-field :deep(.multiselect-search),
.filter-field :deep(.multiselect-single-label) {
  background: var(--proc-input);
  color: var(--proc-ink);
}

.filter-field :deep(.multiselect-option) {
  color: var(--proc-ink);
}

.filter-field :deep(.multiselect.is-active) {
  border-color: rgba(64, 81, 137, .4);
  box-shadow: 0 0 0 .2rem rgba(64, 81, 137, .1);
}

:global(.multiselect-dropdown) {
  z-index: 3000;
}

.hero-kicker,
.section-kicker {
  color: var(--proc-muted);
  font-size: 10px;
  font-weight: 700;
  letter-spacing: .14em;
  text-transform: uppercase;
}

.hero-kicker {
  color: rgba(255,255,255,.75);
}

.hero-pill {
  display: inline-flex;
  align-items: center;
  gap: .45rem;
  background: rgba(255, 255, 255, .92);
  color: var(--proc-brand);
  font-weight: 700;
  padding: .5rem .85rem;
  border-radius: 999px;
  box-shadow: 0 10px 24px rgba(15, 23, 42, .12);
}

:global([data-bs-theme="dark"]) .hero-pill {
  background: rgba(15, 23, 42, .58);
  border: 1px solid rgba(255, 255, 255, .16);
  color: #dbeafe;
}

.hero-pill i {
  font-size: .95rem;
}

.hero-pill.is-success {
  color: #087f69;
}

.hero-pill.is-warning {
  color: #9a6700;
}

.hero-pill.is-dark {
  color: #1f2937;
}

:global([data-bs-theme="dark"]) .hero-pill.is-success {
  color: #99f6e4;
}

:global([data-bs-theme="dark"]) .hero-pill.is-warning {
  color: #fde68a;
}

:global([data-bs-theme="dark"]) .hero-pill.is-dark {
  color: #e5edf7;
}

.hero-stat-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: .6rem;
}

.hero-stat-card {
  position: relative;
  isolation: isolate;
  min-height: 88px;
  padding: .72rem;
  border-radius: 10px;
  color: #fff;
  background:
    linear-gradient(145deg, rgba(255, 255, 255, .18), rgba(255, 255, 255, .08));
  border: 1px solid rgba(255, 255, 255, .2);
  backdrop-filter: blur(10px);
  overflow: hidden;
}

.hero-stat-watermark {
  position: absolute;
  right: .75rem;
  bottom: .5rem;
  z-index: -1;
  color: rgba(255, 255, 255, .14);
  font-size: 2.45rem;
  line-height: 1;
}

.hero-stat-card span,
.unit-breakdown-stat span,
.insight-item span {
  display: block;
  font-size: .72rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .04em;
  opacity: .75;
}

.hero-stat-card strong {
  display: block;
  font-size: 1.35rem;
  line-height: 1.2;
  margin: .3rem 0;
}

.hero-stat-card small {
  color: rgba(255,255,255,.74);
  font-size: .82rem;
  line-height: 1.4;
}

.section-heading {
  display: flex;
  align-items: end;
  justify-content: space-between;
  gap: .6rem;
  margin: .45rem .15rem .3rem;
}

.section-heading h4 {
  margin: 0;
  font-weight: 800;
  color: var(--proc-ink);
}

.section-heading span {
  font-size: 12px;
  color: #94a3b8;
}

.dashboard-metric-section {
  margin-bottom: .85rem;
}

.dashboard-metric-section__header {
  display: flex;
  align-items: end;
  justify-content: space-between;
  gap: .75rem;
  margin: .2rem .15rem .45rem;
}

.dashboard-metric-section__header h5 {
  margin: 0;
  color: var(--proc-ink);
  font-size: .95rem;
  font-weight: 800;
}

.dashboard-metric-section__header p {
  margin: .15rem 0 0;
  color: #64748b;
  font-size: .78rem;
}

.dashboard-metric-section__header .badge {
  border-radius: 999px;
  font-weight: 700;
}

.metric-card,
.module-card,
.panel-card {
  position: relative;
  border: 0;
  border-radius: 10px;
  background: var(--proc-card);
  color: var(--proc-ink);
  box-shadow: 0 6px 16px var(--proc-shadow);
  overflow: hidden;
}

.metric-card::before,
.module-card::before {
  position: absolute;
  inset: 0 0 auto;
  height: 4px;
  content: "";
  background: var(--metric-accent, var(--module-accent, var(--proc-brand)));
}

.metric-card .card-body,
.module-card .card-body,
.panel-card .card-body {
  padding: .75rem;
}

.module-card .card-body {
  padding: .7rem;
}

.metric-card {
  transition: transform .18s ease, box-shadow .18s ease;
}

.metric-card:hover,
.module-card:hover {
  transform: translateY(-1px);
  box-shadow: 0 10px 22px var(--proc-shadow);
}

.metric-card h4 {
  color: var(--proc-ink);
  font-size: 1.2rem;
}

.metric-icon,
.module-icon {
  width: 34px;
  height: 34px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 9px;
  font-size: .98rem;
  flex-shrink: 0;
  box-shadow: inset 0 0 0 1px rgba(255, 255, 255, .5);
}

.module-card .card-body {
  display: flex;
  flex-direction: column;
}

.module-note {
  min-height: 0;
  line-height: 1.32;
}

.module-value.fs-2 {
  font-size: 1.35rem !important;
}

.module-action {
  display: inline-flex;
  align-items: center;
  justify-content: space-between;
  gap: .5rem;
  border: 1px solid rgba(64, 81, 137, .12);
  border-radius: 8px;
  background: rgba(64, 81, 137, .08);
  padding: .34rem .5rem;
  color: var(--proc-brand);
  font-weight: 700;
  font-size: .82rem;
}

.module-action:hover {
  border-color: transparent;
  background: var(--module-accent);
  color: #fff;
}

.module-action i {
  font-size: 1rem;
}

.panel-card .card-header {
  background: var(--proc-panel-header);
  border-bottom: 1px solid var(--proc-border);
  padding: .65rem .75rem;
}

.panel-card .card-header h5 {
  display: flex;
  align-items: center;
  gap: .3rem;
  margin: 0;
  color: var(--proc-brand);
  font-weight: 800;
}

.panel-card .card-header h5 i {
  width: 24px;
  height: 24px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 7px;
  background: var(--proc-soft);
}

.panel-card .card-header p {
  margin: .1rem 0 0;
  color: var(--proc-muted);
  font-size: .8rem;
}

.unit-breakdown-footer,
.insight-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: .45rem;
}

.unit-breakdown-chart-scroll {
  width: 100%;
  overflow-x: auto;
  overflow-y: hidden;
  padding-bottom: .35rem;
}

.unit-breakdown-chart-canvas {
  width: 100%;
}

.insight-grid {
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: .45rem;
}

.unit-breakdown-stat,
.insight-item,
.insight-highlight {
  padding: .6rem;
  border-radius: 9px;
  background: var(--proc-card-gradient);
  border: 1px solid var(--proc-border);
}

.unit-breakdown-stat strong,
.insight-item strong {
  display: block;
  margin-top: .25rem;
  color: var(--proc-ink);
  font-size: .95rem;
  line-height: 1.25;
}

.unit-breakdown-stat small,
.insight-item small {
  color: var(--proc-muted);
  font-size: .75rem;
  line-height: 1.35;
}

.insight-highlight {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: .5rem;
}

.insight-icon {
  width: 34px;
  height: 34px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  background: var(--proc-soft);
  color: var(--proc-brand);
  font-size: 1rem;
  flex-shrink: 0;
}

.table > :not(caption) > * > * {
  padding: .45rem .55rem;
}

.recent-table-body {
  padding-top: .55rem !important;
}

.recent-table-body .table {
  border-collapse: separate;
  border-spacing: 0 .25rem;
}

.recent-table-body thead th {
  border: 0;
  background: transparent;
  color: var(--proc-muted);
  font-size: .72rem;
  font-weight: 800;
  letter-spacing: .05em;
  text-transform: uppercase;
}

.recent-table-body tbody tr {
  box-shadow: 0 4px 12px var(--proc-shadow);
}

.recent-table-body tbody td {
  border-top: 1px solid var(--proc-border);
  border-bottom: 1px solid var(--proc-border);
  background: var(--proc-table-row);
  color: var(--proc-ink);
}

.recent-table-body tbody td:first-child {
  border-left: 1px solid var(--proc-border);
  border-top-left-radius: 10px;
  border-bottom-left-radius: 10px;
}

.recent-table-body tbody td:last-child {
  border-right: 1px solid var(--proc-border);
  border-top-right-radius: 10px;
  border-bottom-right-radius: 10px;
}

.chart-body {
  background: var(--proc-chart-bg);
}

.empty-state {
  text-align: center;
  color: var(--proc-muted);
}

.empty-state i {
  font-size: 2.5rem;
  opacity: .4;
}

.fs-12 {
  font-size: 12px;
}

.fs-13 {
  font-size: 13px;
}

.cursor-pointer {
  cursor: pointer;
}

.min-w-0 {
  min-width: 0;
}

@media (max-width: 768px) {
  .procurement-hero .card-body,
  .compact-card,
  .metric-card .card-body,
  .module-card .card-body,
  .panel-card .card-body,
  .panel-card .card-header {
    padding: .75rem;
  }

  .procurement-hero h3 {
    font-size: 1.65rem;
  }

  .hero-stat-grid,
  .unit-breakdown-footer,
  .insight-grid {
    grid-template-columns: 1fr;
  }

  .section-heading {
    align-items: flex-start;
    flex-direction: column;
  }

  .dashboard-metric-section__header {
    align-items: flex-start;
    flex-direction: column;
    gap: .35rem;
  }

  .dashboard-filter-row {
    align-items: stretch;
    flex-direction: column;
  }

  .dashboard-filter-copy {
    min-width: 0;
  }

  .dashboard-filter-controls {
    justify-content: stretch;
  }

  .filter-field {
    width: 100%;
  }
}
</style>
