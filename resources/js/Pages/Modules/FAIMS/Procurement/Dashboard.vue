<template>
  <div class="procurement-dashboard-page">
    <Head title="Procurement Dashboard" />
    <PageHeader title="Procurement Dashboard" pageTitle="Overview" />

    <!-- Hero -->
    <section class="card border-0 overflow-hidden mb-3 procurement-hero">
      <div class="card-body">
        <div class="row g-3 align-items-center">
          <div class="col-xl-7">
            <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
              <span class="hero-kicker">Procurement Overview</span>
              <BBadge class="bg-white bg-opacity-10 text-white border border-white border-opacity-25 rounded-pill">
                <i class="ri-calendar-line me-1"></i>{{ filteredPeriodLabel }}
              </BBadge>
            </div>

            <h3 class="text-white fw-bold mb-2">
              Keep a close pulse on requests, approvals, and completions.
            </h3>

            <p class="text-white-50 mb-3">
              Track request volume, queue pressure, and unit activity from one focused workspace.
            </p>

            <div class="d-flex flex-wrap gap-2">
              <BBadge class="hero-pill">Total {{ dashboard.total_procurements }}</BBadge>
              <BBadge class="hero-pill text-success">Completed {{ dashboard.completed_procurements }}</BBadge>
              <BBadge class="hero-pill text-warning">For Review {{ dashboard.for_reviews }}</BBadge>
              <BBadge class="hero-pill text-warning">For Approval {{ dashboard.for_approvals }}</BBadge>
              <BBadge class="hero-pill text-dark">Completion {{ completionRate }}%</BBadge>
            </div>

            <p v-if="lastUpdated" class="text-white-50 mb-0 mt-3 fs-12">
              Last updated: {{ formatDateTime(lastUpdated) }}
            </p>
          </div>

          <div class="col-xl-5">
            <div class="hero-stat-grid">
              <div class="hero-stat-card">
                <span>Open Requests</span>
                <strong>{{ openRequests }}</strong>
                <small>Still moving through the pipeline</small>
              </div>

              <div class="hero-stat-card">
                <span>Active Units</span>
                <strong>{{ activeUnitsCount }}</strong>
                <small>Units with procurement activity</small>
              </div>

              <div class="hero-stat-card">
                <span>Distributed</span>
                <strong>{{ formatCompactCurrency(divisionTotalAmount) }}</strong>
                <small>Value in current view</small>
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
    <section class="card border-0 shadow-sm rounded-4 mb-3">
      <div class="card-body compact-card">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    

          <div class="d-flex flex-wrap gap-2">
            <BBadge class="bg-primary-subtle text-primary rounded-pill px-3 py-2">
              <i class="ri-focus-3-line me-1"></i>{{ filteredPeriodLabel }}
            </BBadge>

          </div>
        </div>

        <BRow class="g-2 align-items-end">
          <BCol md="3">
            <label class="form-label small text-muted">Filter Period</label>
            <Multiselect v-model="dashboardFilter.period" :options="periodOptions" placeholder="Select period" @select="onPeriodChange" />
          </BCol>

          <BCol md="2" v-if="isQuarterSelected || ['monthly', 'quarterly', 'yearly'].includes(dashboardFilter.period)">
            <label class="form-label small text-muted">Year</label>
            <Multiselect v-model="dashboardFilter.year" :options="yearOptions" placeholder="Select year" @select="fetchDashboard" />
          </BCol>

          <BCol md="2" v-if="dashboardFilter.period === 'monthly'">
            <label class="form-label small text-muted">Month</label>
            <Multiselect v-model="dashboardFilter.month" :options="monthOptions" placeholder="Select month" @select="fetchDashboard" />
          </BCol>

          <BCol md="2" v-if="dashboardFilter.period === 'quarterly'">
            <label class="form-label small text-muted">Quarter</label>
            <Multiselect v-model="dashboardFilter.quarter" :options="quarterOptions" placeholder="Select quarter" @select="fetchDashboard" />
          </BCol>

          <BCol md="3" v-if="dashboardFilter.period === 'custom'">
            <label class="form-label small text-muted">Start Date</label>
            <input type="date" class="form-control" v-model="dashboardFilter.start_date" @change="fetchDashboard" />
          </BCol>

          <BCol md="3" v-if="dashboardFilter.period === 'custom'">
            <label class="form-label small text-muted">End Date</label>
            <input type="date" class="form-control" v-model="dashboardFilter.end_date" @change="fetchDashboard" />
          </BCol>
        </BRow>
      </div>
    </section>

    <!-- Metrics -->
    <BRow class="g-3 mb-3">
      <BCol xl="3" md="6" v-for="(metric, i) in metrics" :key="i">
        <BCard class="metric-card h-100">
          <BCardBody>
            <div class="d-flex align-items-center gap-3">
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


    <BRow class="g-2 mb-1">
      <BCol v-for="module in workspaceModules" :key="module.key" xl="4" md="6">
        <BCard class="module-card">
          <BCardBody>
            <div class="d-flex justify-content-between align-items-start">
              <div class="module-icon" :class="[module.iconBgClass, module.iconTextClass]">
                <i :class="module.icon"></i>
              </div>

              <BBadge class="bg-primary-subtle text-primary rounded-pill">
                {{ module.tag }}
              </BBadge>
            </div>

            <p class="fw-bold text-dark">{{ module.title }}</p>

            <h4 class="fw-bold text-primary " :class="module.isTextValue ? 'fs-5' : 'fs-2'">
              {{ module.value }}
            </h4>
          </BCardBody>
        </BCard>
      </BCol>
    </BRow>

    <!-- Charts -->
    <BRow class="g-3 mb-3">
      <BCol xl="12">
        <BCard class="panel-card h-100">
          <BCardHeader>
            <h5><i class="ri-bar-chart-line me-2"></i>Monthly Procurement Trends</h5>
            <p>Request volume for {{ filteredPeriodLabel }}</p>
          </BCardHeader>

          <BCardBody>
            <apexchart type="bar" height="350" :options="monthlyChartOptions" :series="monthlyChartSeries" />
          </BCardBody>
        </BCard>
      </BCol>


    </BRow>

    <!-- Unit Breakdown -->
    <BCard class="panel-card mb-3">
      <BCardHeader class="d-flex justify-content-between align-items-center">
        <div>
          <h5><i class="ri-bar-chart-horizontal-line me-2"></i>Unit Breakdown</h5>
          <p>Procurement activity by unit</p>
        </div>

        <BBadge class="bg-primary-subtle text-primary px-3 py-2">
          {{ sortedDivisionDistribution.length }} Units
        </BBadge>
      </BCardHeader>

      <BCardBody>
        <div v-if="sortedDivisionDistribution.length">
          <apexchart
            type="bar"
            :height="unitBreakdownChartHeight"
            :options="unitBreakdownChartOptions"
            :series="unitBreakdownChartSeries"
          />

          <div class="unit-breakdown-footer mt-3">
            <div class="unit-breakdown-stat">
              <span>Total Procurements</span>
              <strong>{{ divisionTotal }}</strong>
              <small>Recorded requests across visible units</small>
            </div>

            <div class="unit-breakdown-stat">
              <span>Distributed Amount</span>
              <strong>{{ formatCompactCurrency(divisionTotalAmount) }}</strong>
              <small>{{ formatCurrency(divisionTotalAmount) }}</small>
            </div>

            <div class="unit-breakdown-stat">
              <span>Top Unit</span>
              <strong>{{ topDivision.name }}</strong>
              <small>{{ topDivision.count }} procurements</small>
            </div>
          </div>
        </div>

        <div v-else class="empty-state py-5">
          <i class="ri-inbox-line"></i>
          <p>No unit data available</p>
        </div>
      </BCardBody>
    </BCard>

    <!-- Recent + Insights -->
    <BRow class="g-3 mb-3">
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

          <BCardBody class="pt-0">
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
            <div class="insight-highlight mb-3">
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

            <div class="progress bg-primary-subtle mb-3" style="height: 8px;">
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
        { value: 'this_week', label: 'This Week' },
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
          title: {
            text: 'Number of Procurements',
          },
        },
        fill: {
          opacity: 1,
        },
        colors: ['#405189'],
        tooltip: {
          y: {
            formatter: function (val) {
              return val + ' procurements';
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
            const share = Number(point.share) || 0;
            const amountLabel = new Intl.NumberFormat('en-PH', {
              style: 'currency',
              currency: 'PHP',
              minimumFractionDigits: 2,
            }).format(amount);

            return [
              '<div style="padding: 10px 12px; min-width: 220px;">',
              `<div style="font-size: 13px; font-weight: 700; color: #0f172a;">${point.x || 'Unassigned'}</div>`,
              point.divisionLabel ? `<div style="font-size: 11px; color: #64748b; margin-top: 2px;">${point.divisionLabel}</div>` : '',
              `<div style="margin-top: 10px; font-size: 12px; color: #334155;"><strong>${count}</strong> procurements</div>`,
              `<div style="margin-top: 4px; font-size: 12px; color: #334155;">${share}% request share</div>`,
              `<div style="margin-top: 4px; font-size: 12px; color: #334155;">${amountLabel} distributed</div>`,
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
            left: 12,
            right: 24,
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
          },
        },
        yaxis: {
          labels: {
            maxWidth: 240,
            style: {
              fontSize: '12px',
              fontWeight: 600,
              colors: ['#0f172a'],
            },
            formatter: function (value) {
              if (!value) {
                return 'Unassigned';
              }
              return value.length > 26 ? `${value.slice(0, 26)}...` : value;
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
            const share = Number(point.share) || 0;
            const amountLabel = new Intl.NumberFormat('en-PH', {
              style: 'currency',
              currency: 'PHP',
              minimumFractionDigits: 2,
            }).format(amount);

            return [
              '<div style="padding: 10px 12px; min-width: 220px;">',
              `<div style="font-size: 13px; font-weight: 700; color: #0f172a;">${point.x || 'Unassigned'}</div>`,
              point.divisionLabel ? `<div style="font-size: 11px; color: #64748b; margin-top: 2px;">${point.divisionLabel}</div>` : '',
              `<div style="margin-top: 5px; font-size: 12px; color: #334155;"><strong>${count}</strong> procurements</div>`,
              `<div style="margin-top: 2px; font-size: 12px; color: #334155;">${amountLabel} distributed</div>`,
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
                  maxWidth: 180,
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
		workspaceModules() {
			const modules = [
				{
					key: 'pap_codes',
					title: 'PAP Codes',
					value: this.dashboard.total_pap_codes,
					note: `${this.formatCompactCurrency(this.dashboard.total_remaining_pap_budget)} remaining from ${this.formatCompactCurrency(this.dashboard.total_allocated_pap_budget)} allocated budget`,
					route: '/faims/procurement-codes',
					action: 'Open PAP codes',
					icon: 'ri-code-box-line',
					iconBgClass: 'bg-success-subtle',
					iconTextClass: 'text-success',
					tag: 'Budget',
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
					roles: ['Procurement Staff', 'Procurement Officer', 'Administrator'],
				},
				{
					key: 'purchase_orders',
					title: 'Purchase Orders',
					value: this.dashboard.total_purchase_orders,
					note: `${this.dashboard.total_purchase_orders} purchase orders released in ${this.filteredPeriodLabel}`,
					route: '/faims/purchase-orders',
					action: 'Open purchase orders',
					icon: 'ri-file-paper-2-line',
					iconBgClass: 'bg-dark-subtle',
					iconTextClass: 'text-dark',
					tag: 'Orders',
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
					const share = Math.round((count / total) * 100);
					return { ...item, count, distributed_amount: distributedAmount, share, unit_label: this.getUnitLabel(item) };
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
		unitBreakdownChartHeight() {
			const itemCount = this.sortedDivisionDistribution.length;
			return Math.min(Math.max((itemCount * 56) + 60, 320), 760);
		},
  },

  methods: {
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
        this.monthlyChartOptions.xaxis.categories = this.dashboard.monthly_trends.map(item => item.month);
        this.monthlyChartSeries[0].data = this.dashboard.monthly_trends.map(item => item.count);
      }

      // Update unit summary chart
      if (this.dashboard && this.dashboard.division_distribution && Array.isArray(this.dashboard.division_distribution)) {
        const items = this.sortedDivisionDistribution;
        if (items.length > 0) {
          this.unitSummaryChartSeries = [{
            name: 'Unit Distribution',
            data: items.map((item) => ({
              x: this.getUnitLabel(item),
              y: item.count,
              share: item.share,
              distributedAmount: item.distributed_amount,
              divisionLabel: this.getUnitDivisionLabel(item),
            })),
          }];
          this.unitBreakdownChartSeries = [{
            name: 'Procurements',
            data: items.map((item) => ({
              x: this.getUnitLabel(item),
              y: item.count,
              distributedAmount: item.distributed_amount,
              share: item.share,
              divisionLabel: this.getUnitDivisionLabel(item),
            })),
          }];
        } else {
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
			const amount = Number(value) || 0;
			return new Intl.NumberFormat('en-PH', {
				style: 'currency',
				currency: 'PHP',
				notation: 'compact',
				minimumFractionDigits: 0,
				maximumFractionDigits: 1,
			}).format(amount);
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

    onPeriodChange() {
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
  --proc-soft: rgba(64, 81, 137, 0.1);
  padding-bottom: 1.5rem;
}

.procurement-hero {
  border-radius: 18px;
  background:
    radial-gradient(circle at top left, rgba(255,255,255,.18), transparent 34%),
    linear-gradient(135deg, var(--proc-brand), var(--proc-brand-dark));
  box-shadow: 0 18px 40px rgba(64, 81, 137, 0.22);
}

.procurement-hero .card-body,
.compact-card {
  padding: 1.25rem;
}

.hero-kicker,
.section-kicker {
  color: #64748b;
  font-size: 10px;
  font-weight: 700;
  letter-spacing: .16em;
  text-transform: uppercase;
}

.hero-kicker {
  color: rgba(255,255,255,.75);
}

.hero-pill {
  background: #fff;
  color: var(--proc-brand);
  font-weight: 700;
  padding: .5rem .75rem;
  border-radius: 999px;
}

.hero-stat-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: .75rem;
}

.hero-stat-card {
  padding: 1rem;
  border-radius: 16px;
  color: #fff;
  background: rgba(255,255,255,.13);
  border: 1px solid rgba(255,255,255,.18);
  backdrop-filter: blur(8px);
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
}

.hero-stat-card small {
  color: rgba(255,255,255,.72);
}

.section-heading {
  display: flex;
  align-items: end;
  justify-content: space-between;
  gap: 1rem;
  margin: .75rem .25rem .5rem;
}

.section-heading h4 {
  margin: 0;
  font-weight: 800;
  color: #0f172a;
}

.section-heading span {
  font-size: 12px;
  color: #94a3b8;
}

.metric-card,
.module-card,
.panel-card {
  border: 0;
  border-radius: 18px;
  box-shadow: 0 12px 28px rgba(15, 23, 42, .07);
  overflow: hidden;
}

.metric-card .card-body,
.module-card .card-body,
.panel-card .card-body {
  padding: 0;
}

.metric-icon,
.module-icon {
  width: 46px;
  height: 46px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 14px;
  font-size: 1.35rem;
  flex-shrink: 0;
}

.module-card .card-body {
  display: flex;
  flex-direction: column;
  height: 100%;
    margin: 0;
  padding: 0;
}

.panel-card .card-header {
  background: #fff;
  border-bottom: 1px solid #eef2f7;
  padding: .1rem;
}

.panel-card .card-header h5 {
  margin: 0;
  color: var(--proc-brand);
  font-weight: 800;
}

.panel-card .card-header p {
  margin: .2rem 0 0;
  color: #64748b;
  font-size: .8rem;
}

.unit-breakdown-footer,
.insight-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: .75rem;
}

.insight-grid {
  grid-template-columns: repeat(2, minmax(0, 1fr));
}

.unit-breakdown-stat,
.insight-item,
.insight-highlight {
  padding: .9rem;
  border-radius: 16px;
  background: #f8fafc;
  border: 1px solid #eef2f7;
}

.unit-breakdown-stat strong,
.insight-item strong {
  display: block;
  margin-top: .25rem;
  color: #0f172a;
  font-size: 1rem;
}

.unit-breakdown-stat small,
.insight-item small {
  color: #64748b;
  font-size: .75rem;
}

.insight-highlight {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.insight-icon {
  width: 42px;
  height: 42px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  background: var(--proc-soft);
  color: var(--proc-brand);
  font-size: 1.25rem;
}

.empty-state {
  text-align: center;
  color: #64748b;
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
  .hero-stat-grid,
  .unit-breakdown-footer,
  .insight-grid {
    grid-template-columns: 1fr;
  }

  .section-heading {
    align-items: flex-start;
    flex-direction: column;
  }
}
</style>
