<template>
	<div class="procurement-dashboard-page">
	<Head title="Procurement Dashboard" />
	<PageHeader title="Procurement Dashboard" pageTitle="Overview" />

	<!-- Hero Summary -->
	<BRow class="mb-4">
		<BCol xl="12">
			<BCard class="procurement-hero border-0">
				<BCardBody>
					<div class="d-flex flex-column flex-xl-row gap-3 align-items-xl-center justify-content-between">
						<div>
							<p class="text-uppercase fw-semibold text-white-50 mb-1">Procurement Overview</p>
							<h3 class="text-white mb-2">Keep a close pulse on requests, approvals, and completions.</h3>
							<div class="d-flex flex-wrap gap-2">
								<span class="badge bg-white text-primary fw-semibold px-3 py-2">
									Total {{ dashboard.total_procurements }}
								</span>
								<span class="badge bg-white text-success fw-semibold px-3 py-2">
									Completed {{ dashboard.completed_procurements }}
								</span>
								<span class="badge bg-white text-warning fw-semibold px-3 py-2">
									For Review {{ dashboard.for_reviews }}
								</span>
								<span class="badge bg-white text-warning fw-semibold px-3 py-2">
									For Approval {{ dashboard.for_approvals }}
								</span>
								<span class="badge bg-white text-dark fw-semibold px-3 py-2">
									Completion {{ completionRate }}%
								</span>
							</div>
						</div>
						<div class="d-flex flex-wrap gap-2">
							<BButton variant="light" class="btn-soft" @click="goCreatePage">
								<i class="ri-add-circle-line me-1"></i> New Request
							</BButton>
							<BButton variant="outline-light" @click="goViewAll">
								<i class="ri-file-list-3-line me-1"></i> View Requests
							</BButton>
						</div>
					</div>
					<p v-if="lastUpdated" class="text-white-50 mb-0 mt-3 fs-12">
						Last updated: {{ formatDateTime(lastUpdated) }}
					</p>
				</BCardBody>
			</BCard>
		</BCol>
	</BRow>


	<!-- Dashboard Filters -->
	<BRow class="mb-4">
		<BCol xl="12">
			<BCard class="filter-card">
				<BCardBody class="py-3">
					<BRow class="align-items-end g-3">
						<BCol md="3">
							<label class="form-label">Filter Period</label>
							<Multiselect
								v-model="dashboardFilter.period"
								:options="periodOptions"
								placeholder="Select period"
								@select="onPeriodChange"
							/>
						</BCol>
						<BCol md="2" v-if="isQuarterSelected || dashboardFilter.period === 'monthly' || dashboardFilter.period === 'quarterly' || dashboardFilter.period === 'yearly'">
							<label class="form-label">Year</label>
							<Multiselect
								v-model="dashboardFilter.year"
								:options="yearOptions"
								placeholder="Select year"
								@select="fetchDashboard"
							/>
						</BCol>
						<BCol md="2" v-if="dashboardFilter.period === 'monthly'">
							<label class="form-label">Month</label>
							<Multiselect
								v-model="dashboardFilter.month"
								:options="monthOptions"
								placeholder="Select month"
								@select="fetchDashboard"
							/>
						</BCol>
						<BCol md="2" v-if="dashboardFilter.period === 'quarterly'">
							<label class="form-label">Quarter</label>
							<Multiselect
								v-model="dashboardFilter.quarter"
								:options="quarterOptions"
								placeholder="Select quarter"
								@select="fetchDashboard"
							/>
						</BCol>
						<BCol md="3" v-if="dashboardFilter.period === 'custom'">
							<label class="form-label">Start Date</label>
							<input
								type="date"
								class="form-control"
								v-model="dashboardFilter.start_date"
								@change="fetchDashboard"
							/>
						</BCol>
						<BCol md="3" v-if="dashboardFilter.period === 'custom'">
							<label class="form-label">End Date</label>
							<input
								type="date"
								class="form-control"
								v-model="dashboardFilter.end_date"
								@change="fetchDashboard"
							/>
						</BCol>
						<!-- <BCol md="3">
							<b-button variant="primary" @click="fetchDashboard">
								<i class="ri-refresh-line me-1"></i> Apply Filters
							</b-button>
						</BCol> -->
					</BRow>
				</BCardBody>
			</BCard>
		</BCol>
	</BRow>

	<!-- Dashboard Metrics Cards -->
	<BRow class="mb-3 dashboard-section">
		<BCol xl="12">
			<div class="section-header">
				<div>
					<p class="section-kicker">At a Glance</p>
					<h4 class="section-title">Core Procurement Metrics</h4>
				</div>
				<span class="section-meta">Updated {{ lastUpdated ? formatDateTime(lastUpdated) : 'just now' }}</span>
			</div>
		</BCol>
	</BRow>
	<BRow class="mb-4">
		<BCol xl="3" md="6">
			<BCard class="metric-card card-animate">
				<BCardBody>
					<div class="d-flex align-items-center">
						<div class="avatar-sm flex-shrink-0">
							<div class="avatar-title bg-primary-subtle text-primary rounded-2 fs-2">
								<i class="ri-file-list-3-line"></i>
							</div>
						</div>
						<div class="flex-grow-1 overflow-hidden ms-3">
							<p class="text-uppercase fw-medium text-muted text-truncate mb-0">Total Procurements</p>
							<h4 class="fs-22 fw-semibold mb-0">
								<span class="counter-value" :data-target="dashboard.total_procurements">{{ dashboard.total_procurements }}</span>
							</h4>
						</div>
					</div>
				</BCardBody>
			</BCard>
		</BCol>

		<BCol xl="3" md="6">
			<BCard class="metric-card card-animate">
				<BCardBody>
					<div class="d-flex align-items-center">
						<div class="avatar-sm flex-shrink-0">
							<div class="avatar-title bg-warning-subtle text-warning rounded-2 fs-2">
								<i class="ri-time-line"></i>
							</div>
						</div>
						<div class="flex-grow-1 overflow-hidden ms-3">
							<p class="text-uppercase fw-medium text-muted text-truncate mb-0">For Reviews</p>
							<h4 class="fs-22 fw-semibold mb-0">
								<span class="counter-value" :data-target="dashboard.for_reviews">{{ dashboard.for_reviews }}</span>
							</h4>
						</div>
					</div>
				</BCardBody>
			</BCard>
		</BCol>

    	<BCol xl="3" md="6">
			<BCard class="metric-card card-animate">
				<BCardBody>
					<div class="d-flex align-items-center">
						<div class="avatar-sm flex-shrink-0">
							<div class="avatar-title bg-warning-subtle text-warning rounded-2 fs-2">
								<i class="ri-time-line"></i>
							</div>
						</div>
						<div class="flex-grow-1 overflow-hidden ms-3">
							<p class="text-uppercase fw-medium text-muted text-truncate mb-0">For Approval</p>
							<h4 class="fs-22 fw-semibold mb-0">
								<span class="counter-value" :data-target="dashboard.for_approvals">{{ dashboard.for_approvals }}</span>
							</h4>
						</div>
					</div>
				</BCardBody>
			</BCard>
		</BCol>

		<BCol xl="3" md="6">
			<BCard class="metric-card card-animate">
				<BCardBody>
					<div class="d-flex align-items-center">
						<div class="avatar-sm flex-shrink-0">
							<div class="avatar-title bg-success-subtle text-success rounded-2 fs-2">
								<i class="ri-check-line"></i>
							</div>
						</div>
						<div class="flex-grow-1 overflow-hidden ms-3">
							<p class="text-uppercase fw-medium text-muted text-truncate mb-0">Completed</p>
							<h4 class="fs-22 fw-semibold mb-0">
								<span class="counter-value" :data-target="dashboard.completed_procurements">{{ dashboard.completed_procurements }}</span>
							</h4>
						</div>
					</div>
				</BCardBody>
			</BCard>
		</BCol>

		
	</BRow>

	<!-- Additional Metrics Cards -->
	<BRow class="mb-3 dashboard-section">
		<BCol xl="12">
			<div class="section-header">
				<div>
					<p class="section-kicker">Pipeline</p>
					<h4 class="section-title">Documents & Outputs</h4>
				</div>
				<span class="section-meta">Totals across selected period</span>
			</div>
		</BCol>
	</BRow>
	<BRow class="mb-4">
    <BCol xl="3" md="6">
			<BCard class="metric-card card-animate">
				<BCardBody>
					<div class="d-flex align-items-center">
						<div class="avatar-sm flex-shrink-0">
							<div class="avatar-title bg-info-subtle text-info rounded-2 fs-2">
								<i class="ri-file-text-line"></i>
							</div>
						</div>
						<div class="flex-grow-1 overflow-hidden ms-3">
							<p class="text-uppercase fw-medium text-muted text-truncate mb-0">Total Quotations</p>
							<h4 class="fs-22 fw-semibold mb-0">
								<span class="counter-value" :data-target="dashboard.total_quotations">{{ dashboard.total_quotations }}</span>
							</h4>
						</div>
					</div>
				</BCardBody>
			</BCard>
		</BCol>

		<BCol xl="3" md="6">
			<BCard class="metric-card card-animate">
				<BCardBody>
					<div class="d-flex align-items-center">
						<div class="avatar-sm flex-shrink-0">
							<div class="avatar-title bg-danger-subtle text-danger rounded-2 fs-2">
								<i class="ri-file-paper-2-line"></i>
							</div>
						</div>
						<div class="flex-grow-1 overflow-hidden ms-3">
							<p class="text-uppercase fw-medium text-muted text-truncate mb-0">BAC Resolutions</p>
							<h4 class="fs-22 fw-semibold mb-0">
								<span class="counter-value" :data-target="dashboard.total_bac_resolutions">{{ dashboard.total_bac_resolutions }}</span>
							</h4>
						</div>
					</div>
				</BCardBody>
			</BCard>
		</BCol>

		<BCol xl="3" md="6">
			<BCard class="metric-card card-animate">
				<BCardBody>
					<div class="d-flex align-items-center">
						<div class="avatar-sm flex-shrink-0">
							<div class="avatar-title bg-secondary-subtle text-secondary rounded-2 fs-2">
								<i class="ri-notification-2-line"></i>
							</div>
						</div>
						<div class="flex-grow-1 overflow-hidden ms-3">
							<p class="text-uppercase fw-medium text-muted text-truncate mb-0">Notice of Awards</p>
							<h4 class="fs-22 fw-semibold mb-0">
								<span class="counter-value" :data-target="dashboard.total_notice_of_awards">{{ dashboard.total_notice_of_awards }}</span>
							</h4>
						</div>
					</div>
				</BCardBody>
			</BCard>
		</BCol>

		<BCol xl="3" md="6">
			<BCard class="metric-card card-animate">
				<BCardBody>
					<div class="d-flex align-items-center">
						<div class="avatar-sm flex-shrink-0">
							<div class="avatar-title bg-dark-subtle text-dark rounded-2 fs-2">
								<i class="ri-shopping-cart-line"></i>
							</div>
						</div>
						<div class="flex-grow-1 overflow-hidden ms-3">
							<p class="text-uppercase fw-medium text-muted text-truncate mb-0">Purchase Orders</p>
							<h4 class="fs-22 fw-semibold mb-0">
								<span class="counter-value" :data-target="dashboard.total_purchase_orders">{{ dashboard.total_purchase_orders }}</span>
							</h4>
						</div>
					</div>
				</BCardBody>
			</BCard>
		</BCol>
	</BRow>

	<!-- Charts Row -->
	<BRow class="mb-3 dashboard-section">
		<BCol xl="12">
			<div class="section-header">
				<div>
					<p class="section-kicker">Trends</p>
					<h4 class="section-title">Monthly Movement & Unit Share</h4>
				</div>
				<span class="section-meta">Visual breakdown of activity</span>
			</div>
		</BCol>
	</BRow>
	<BRow class="mb-4">
		<BCol xl="8">
			<BCard class="panel-card">
				<BCardHeader class="py-3">
					<h4 class="card-title mb-0"><i class="ri-bar-chart-line me-2"></i>Monthly Procurement Trends</h4>
				</BCardHeader>
				<BCardBody>
					<apexchart
						type="bar"
						height="350"
						:options="monthlyChartOptions"
						:series="monthlyChartSeries"
					></apexchart>
				</BCardBody>
			</BCard>
		</BCol>

		<BCol xl="4">
			<BCard class="panel-card unit-summary-card">
				<BCardHeader class="py-3">
					<h4 class="card-title mb-0"><i class="ri-donut-chart-line me-2"></i>Unit Distribution</h4>
				</BCardHeader>
				<BCardBody class="chart-body">
					<div v-if="sortedDivisionDistribution.length" class="chart-container">
						<apexchart
							type="donut"
							height="300"
							:options="unitSummaryChartOptions"
							:series="unitSummaryChartSeries"
						></apexchart>
					</div>
					<div v-else class="empty-state text-center py-5">
						<i class="ri-pie-chart-line fs-1 text-secondary"></i>
						<p class="text-muted mt-2 mb-0">No unit data available</p>
					</div>
				</BCardBody>
			</BCard>
		</BCol>
	</BRow>

	<!-- Division Table -->
	<BRow class="mb-4 dashboard-section">
		<BCol xl="12">
			<BCard class="panel-card unit-table-card">
				<BCardHeader class="d-flex align-items-center justify-content-between py-3">
					<h4 class="card-title mb-0"><i class="ri-layout-grid-line me-2"></i>Unit Breakdown</h4>
					<span class="badge bg-primary-subtle text-primary px-3 py-2">
						<i class="ri-information-line me-1"></i>{{ sortedDivisionDistribution.length }} Units
					</span>
				</BCardHeader>
				<BCardBody class="pt-0">
					<div class="table-responsive table-card">
						<table class="table align-middle table-hover mb-0">
							<thead class="table-light">
								<tr class="fs-12 fw-semibold text-uppercase">
									<th style="width: 5%" class="text-center"><i class="ri-hashtag"></i></th>
									<th style="width: 34%"><i class="ri-building-line me-1"></i>Unit</th>
									<th style="width: 16%" class="text-end"><i class="ri-file-list-3-line me-1"></i>Procurements</th>
									<th style="width: 22%" class="text-end"><i class="ri-money-dollar-circle-line me-1"></i>Amount Distributed</th>
									<th style="width: 23%"><i class="ri-pie-chart-line me-1"></i>Distribution</th>
								</tr>
							</thead>
							<tbody class="table-group-divider">
								<tr v-for="(item, index) in sortedDivisionDistribution" :key="index" class="unit-row">
									<td class="text-center">
										<span class="unit-number">{{ index + 1 }}</span>
									</td>
									<td class="fw-semibold">
										<div class="d-flex align-items-center">
											<div class="unit-icon me-2">
												<i class="ri-community-line"></i>
											</div>
											<div>
												<div>{{ getUnitLabel(item) }}</div>
												<div class="text-muted fs-12">{{ getUnitDivisionLabel(item) }}</div>
											</div>
										</div>
									</td>
									<td class="text-end fw-semibold">
										<span class="procurement-count">{{ item.count }}</span>
									</td>
									<td class="text-end fw-semibold">
										<span class="distributed-amount">{{ formatCurrency(item.distributed_amount) }}</span>
									</td>
									<td>
										<div class="d-flex align-items-center gap-2">
											<div class="progress flex-grow-1 unit-progress" style="height: 10px;">
												<div class="progress-bar bg-gradient" role="progressbar" :style="{ width: item.share + '%' }" :aria-valuenow="item.share" aria-valuemin="0" aria-valuemax="100"></div>
											</div>
											<span class="share-badge">{{ item.share }}%</span>
										</div>
									</td>
								</tr>
								<tr v-if="sortedDivisionDistribution.length > 0" class="table-light total-row">
									<td class="text-center"><i class="ri-calculator-line"></i></td>
									<td class="fw-semibold"><div class="d-flex align-items-center"><i class="ri-stack-line me-2"></i>All Units</div></td>
									<td class="text-end fw-semibold">{{ divisionTotal }}</td>
									<td class="text-end fw-semibold">{{ formatCurrency(divisionTotalAmount) }}</td>
									<td>
										<div class="d-flex align-items-center gap-2">
											<div class="progress flex-grow-1" style="height: 10px;">
												<div class="progress-bar bg-primary" role="progressbar" style="width: 100%"></div>
											</div>
											<span class="share-badge bg-primary text-white">100%</span>
										</div>
									</td>
								</tr>
								<tr v-if="sortedDivisionDistribution.length === 0">
									<td colspan="5" class="text-center text-muted py-5">
										<i class="ri-inbox-line fs-1 d-block mb-2 opacity-50"></i>
										No unit data available
									</td>
								</tr>
							</tbody>
						</table>
					</div>
				</BCardBody>
			</BCard>
		</BCol>
	</BRow>

	<!-- Recent Activity -->
	<BRow class="mb-4 dashboard-section">
		<BCol xl="7">
			<BCard class="panel-card">
				<BCardHeader class="d-flex align-items-center justify-content-between py-3">
					<h4 class="card-title mb-0"><i class="ri-time-line me-2"></i>Recent Procurements</h4>
					<BButton size="sm" variant="outline-primary" @click="goViewAll">
						View All
					</BButton>
				</BCardHeader>
				<BCardBody class="pt-0">
					<div class="table-responsive table-card">
						<table class="table align-middle table-hover mb-0">
							<thead class="table-light">
								<tr class="fs-12 fw-semibold">
									<th style="width: 20%">Code</th>
									<th style="width: 30%">Purpose</th>
									<th style="width: 16%">Division</th>
									<th style="width: 14%">Status</th>
									<th style="width: 20%">Date</th>
								</tr>
							</thead>
							<tbody class="table-group-divider">
								<tr v-for="(list, index) in lists" :key="index" class="cursor-pointer" @click="goViewPage(list)">
									<td>
										<div class="fw-semibold text-primary">{{ list.code }}</div>
									</td>
									<td>
										<div class="text-truncate" style="max-width: 240px" v-b-tooltip.hover :title="list.purpose">
											{{ list.purpose }}
										</div>
									</td>
									<td>{{ list.division?.name }}</td>
									<td>
										<b-badge :class="list.status?.bg" class="fs-11">{{ list.status?.name }}</b-badge>
									</td>
									<td class="text-muted">{{ formatDate(list.date) }}</td>
								</tr>
								<tr v-if="lists.length === 0">
									<td colspan="5" class="text-center text-muted py-4">No recent procurements found.</td>
								</tr>
							</tbody>
						</table>
					</div>
				</BCardBody>
			</BCard>
		</BCol>
		<BCol xl="5">
			<BCard class="insights-card panel-card">
				<BCardHeader class="py-3">
					<h4 class="card-title mb-0"><i class="ri-lightbulb-line me-2"></i>Insights</h4>
				</BCardHeader>
				<BCardBody>
					<div class="d-flex align-items-center justify-content-between mb-3">
						<div>
							<p class="text-muted mb-1">Open Requests</p>
							<h4 class="mb-0">
								{{ dashboard.total_procurements - dashboard.completed_procurements }}
							</h4>
							<p class="text-muted mb-0 fs-12">
								{{ completionRate }}% completion rate
							</p>
						</div>
						<div class="avatar-sm">
							<div class="avatar-title bg-soft-primary text-primary rounded-circle">
								<i class="ri-folder-open-line fs-18"></i>
							</div>
						</div>
					</div>
					<div class="progress bg-soft-primary mb-4" style="height: 8px;">
						<div class="progress-bar bg-primary" role="progressbar" :style="{ width: completionRate + '%' }" :aria-valuenow="completionRate" aria-valuemin="0" aria-valuemax="100"></div>
					</div>
					<div class="d-flex align-items-center justify-content-between mb-3">
						<div>
							<p class="text-muted mb-1">Top Unit</p>
							<h4 class="mb-0">{{ topDivision.name }}</h4>
							<p class="text-muted mb-0 fs-12">{{ topDivision.count }} procurements</p>
						</div>
						<div class="avatar-sm">
							<div class="avatar-title bg-soft-info text-info rounded-circle">
								<i class="ri-building-2-line fs-18"></i>
							</div>
						</div>
					</div>
					<div class="d-flex align-items-center justify-content-between mb-3">
						<div>
							<p class="text-muted mb-1">Review Queue</p>
							<h4 class="mb-0">{{ dashboard.for_reviews }}</h4>
							<p class="text-muted mb-0 fs-12">Pending review items</p>
						</div>
						<div class="avatar-sm">
							<div class="avatar-title bg-soft-warning text-warning rounded-circle">
								<i class="ri-time-line fs-18"></i>
							</div>
						</div>
					</div>
					<div class="d-flex align-items-center justify-content-between">
						<div>
							<p class="text-muted mb-1">Approval Queue</p>
							<h4 class="mb-0">{{ dashboard.for_approvals }}</h4>
							<p class="text-muted mb-0 fs-12">Awaiting approval</p>
						</div>
						<div class="avatar-sm">
							<div class="avatar-title bg-soft-warning text-warning rounded-circle">
								<i class="ri-task-line fs-18"></i>
							</div>
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
          type: 'donut',
          height: 300,
          fontFamily: 'inherit',
        },
        labels: [],
        colors: ['#405189', '#5c6bc0', '#7986cb', '#9fa8da', '#c5cae9', '#0ab39c', '#20c997', '#ffc107', '#6b7299'],
        dataLabels: {
          enabled: true,
          formatter: function (_val, opts) {
            return opts.w.globals.labels[opts.seriesIndex] || '';
          },
          dropShadow: {
            enabled: false,
          },
          style: {
            fontSize: '12px',
            fontWeight: '600',
            colors: ['#1e293b'],
          },
        },
        legend: {
          position: 'bottom',
          horizontalAlign: 'center',
          floating: false,
          fontSize: '12px',
          fontWeight: '500',
          color: '#475569',
          itemMargin: {
            horizontal: 8,
            vertical: 4,
          },
          formatter: function (seriesName, opts) {
            const count = opts.w.globals.series[opts.seriesIndex] || 0;
            const total = opts.w.globals.seriesTotals.reduce((a, b) => a + b, 0) || 1;
            const pct = Math.round((count / total) * 100);
            return `<span class="legend-text">${seriesName}</span> <span class="legend-count">(${count})</span>`;
          },
        },
        plotOptions: {
          pie: {
            donut: {
              size: '65%',
              labels: {
                show: true,
                name: {
                  show: true,
                  offsetY: -10,
                  fontSize: '14px',
                  fontWeight: '600',
                  color: '#405189',
                  formatter: function () {
                    return 'Total';
                  },
                },
                value: {
                  show: true,
                  offsetY: 6,
                  fontSize: '24px',
                  fontWeight: '700',
                  color: '#1e293b',
                  formatter: function (val) {
                    return val;
                  },
                },
                total: {
                  show: true,
                  showAlways: true,
                  label: 'Total',
                  fontSize: '14px',
                  fontWeight: '600',
                  color: '#64748b',
                  formatter: function (w) {
                    const total = w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                    return total;
                  },
                },
              },
            },
            expandOnClick: true,
          },
        },
        stroke: {
          show: true,
          width: 3,
          colors: ['#fff'],
        },
        states: {
          hover: {
            filter: {
              type: 'lighten',
              value: 0.1,
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
          y: {
            formatter: function (val, opts) {
              const total = opts.w.globals.seriesTotals.reduce((a, b) => a + b, 0) || 1;
              const pct = Math.round((val / total) * 100);
              return `<strong>${val}</strong> procurements (${pct}%)`;
            },
          },
        },
        responsive: [
          {
            breakpoint: 1200,
            options: {
              chart: { height: 280 },
              legend: { 
                position: 'bottom',
                fontSize: '11px',
              },
            },
          },
          {
            breakpoint: 768,
            options: {
              chart: { height: 250 },
              legend: { 
                position: 'bottom',
                fontSize: '10px',
              },
            },
          },
        ],
      },
      unitSummaryChartSeries: [],
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
          this.unitSummaryChartOptions.labels = items.map(item => this.getUnitLabel(item));
          this.unitSummaryChartSeries = items.map(item => item.count);
        } else {
          this.unitSummaryChartOptions.labels = [];
          this.unitSummaryChartSeries = [];
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
/* Hero Section */
.procurement-hero {
  background: radial-gradient(circle at top left, rgba(64, 81, 137, 0.95), rgba(64, 81, 137, 0.85)), linear-gradient(120deg, #405189, #6b7299);
  color: #fff;
  border-radius: 0.75rem;
  box-shadow: 0 8px 32px rgba(64, 81, 137, 0.25);
}

.procurement-hero .btn-soft {
  background: rgba(255, 255, 255, 0.92);
  border: none;
  color: #405189;
  font-weight: 600;
  padding: 0.5rem 1.25rem;
  border-radius: 0.5rem;
  transition: all 0.2s ease;
}

.procurement-hero .btn-soft:hover {
  background: #fff;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.procurement-hero .btn-outline-light {
  border-color: rgba(255, 255, 255, 0.6);
  color: #fff;
  font-weight: 500;
  padding: 0.5rem 1.25rem;
  border-radius: 0.5rem;
}

.procurement-hero .btn-outline-light:hover {
  background: rgba(255, 255, 255, 0.15);
  border-color: #fff;
  color: #fff;
}

/* Section Headers */
.dashboard-section {
  position: relative;
}

.section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 14px;
  padding: 6px 4px 0;
}

.section-kicker {
  text-transform: uppercase;
  letter-spacing: 0.18em;
  font-size: 10px;
  font-weight: 600;
  color: #64748b;
  margin-bottom: 6px;
}

.section-title {
  font-family: "Montserrat", "Segoe UI", sans-serif;
  font-weight: 700;
  margin: 0;
  color: #0f172a;
}

.section-meta {
  font-size: 12px;
  color: #94a3b8;
}

/* Metric Cards */
.metric-card {
  border: 0;
  background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
  box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
  border-radius: 0.75rem;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  overflow: hidden;
  position: relative;
}

.metric-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 3px;
  background: linear-gradient(90deg, #405189, #5c6bc0);
  transform: scaleX(0);
  transform-origin: left;
  transition: transform 0.3s ease;
}

.metric-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 20px 40px rgba(64, 81, 137, 0.15);
}

.metric-card:hover::before {
  transform: scaleX(1);
}

.metric-card .avatar-sm {
  width: 52px;
  height: 52px;
  border-radius: 12px;
}

.metric-card .avatar-title {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.35rem;
}

.metric-card .fs-22 {
  color: #1e293b;
  font-weight: 700;
}

/* Panel Cards */
.panel-card {
  border: 0;
  box-shadow: 0 16px 32px rgba(15, 23, 42, 0.08);
  border-radius: 0.75rem;
  overflow: hidden;
}

.panel-card .card-header {
  background: #fff;
  border-bottom: 1px solid #e2e8f0;
  padding: 1rem 1.25rem;
}

.panel-card .card-title {
  color: #405189;
  font-weight: 600;
  font-size: 1rem;
  margin: 0;
}

/* Unit Summary Card */
.unit-summary-card .chart-body {
  padding: 1rem;
}

.unit-summary-card .chart-container {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 280px;
}

.unit-summary-card .empty-state {
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  min-height: 200px;
}

.unit-summary-card .empty-state i {
  opacity: 0.4;
  font-size: 3rem;
}

/* Unit Table Card */
.unit-table-card .unit-row {
  transition: all 0.2s ease;
}

.unit-table-card .unit-row:hover {
  background: linear-gradient(90deg, rgba(64, 81, 137, 0.04) 0%, transparent 100%);
}

.unit-table-card .unit-number {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 28px;
  height: 28px;
  background: linear-gradient(135deg, #405189 0%, #5c6bc0 100%);
  color: #fff;
  border-radius: 50%;
  font-weight: 600;
  font-size: 0.75rem;
}

.unit-table-card .unit-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  background: rgba(64, 81, 137, 0.1);
  color: #405189;
  border-radius: 8px;
}

.unit-table-card .procurement-count {
  font-size: 1rem;
  font-weight: 700;
  color: #405189;
}

.unit-table-card .distributed-amount {
  color: #0ab39c;
  font-weight: 700;
  white-space: nowrap;
}

.unit-table-card .unit-progress {
  border-radius: 5px;
  background: #e2e8f0;
}

.unit-table-card .unit-progress .progress-bar {
  background: linear-gradient(90deg, #405189 0%, #5c6bc0 100%);
  border-radius: 5px;
}

.unit-table-card .share-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 45px;
  padding: 0.25rem 0.5rem;
  background: rgba(64, 81, 137, 0.1);
  color: #405189;
  border-radius: 20px;
  font-weight: 600;
  font-size: 0.75rem;
}

.unit-table-card .total-row {
  background: linear-gradient(90deg, rgba(64, 81, 137, 0.06) 0%, transparent 100%);
  border-top: 2px solid #e2e8f0;
}

.unit-table-card .total-row .unit-number {
  background: #405189;
}

.unit-table-card .total-row .procurement-count {
  color: #1e293b;
}

.unit-table-card .total-row .distributed-amount {
  color: #0f766e;
}

.unit-table-card .badge {
  font-weight: 600;
}

/* Donut Chart Legend Styling */
:deep(.legend-text) {
  color: #475569;
  font-weight: 500;
}

:deep(.legend-count) {
  color: #405189;
  font-weight: 600;
}

:deep(.apexcharts-datalabel-value) {
  font-weight: 700 !important;
  color: #1e293b !important;
}

/* Insights Card */
.insights-card {
  background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
  border-radius: 0.75rem;
  box-shadow: 0 16px 32px rgba(15, 23, 42, 0.08);
}

.insights-card .card-header {
  background: transparent;
  border-bottom: 1px solid #e2e8f0;
  padding: 1rem 1.25rem;
}

.insights-card .card-title {
  color: #405189;
  font-weight: 600;
  font-size: 1rem;
}

.insight-item {
  padding: 0.875rem;
  border-radius: 0.5rem;
  transition: all 0.2s ease;
}

.insight-item:hover {
  background: rgba(64, 81, 137, 0.04);
}

.insight-item .avatar-sm {
  width: 40px;
  height: 40px;
}

/* Progress Bars */
.panel-card .progress {
  height: 8px;
  border-radius: 4px;
  background: #e2e8f0;
  overflow: hidden;
}

.panel-card .progress-bar {
  border-radius: 4px;
  transition: width 0.6s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Table Styling */
.panel-card .table thead th {
  background: #f8fafc;
  color: #475569;
  font-weight: 600;
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  padding: 0.875rem 1rem;
  border-bottom: 1px solid #e2e8f0;
}

.panel-card .table tbody td {
  padding: 0.875rem 1rem;
  vertical-align: middle;
  color: #334155;
}

.panel-card .table tbody tr {
  transition: background 0.2s ease;
}

.panel-card .table tbody tr:hover {
  background: #f8fafc;
}

.panel-card .table .fw-semibold {
  color: #1e293b;
}

/* Badges */
.panel-card .badge {
  padding: 0.4em 0.75em;
  font-weight: 500;
  font-size: 0.7rem;
}

/* Filter Card */
.filter-card {
  border: 0;
  border-radius: 0.75rem;
  box-shadow: 0 4px 16px rgba(15, 23, 42, 0.06);
}

.filter-card .form-label {
  font-weight: 500;
  color: #475569;
  font-size: 0.85rem;
  margin-bottom: 0.375rem;
}

/* Animations */
@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.metric-card {
  animation: fadeInUp 0.5s ease forwards;
  opacity: 0;
}

.metric-card:nth-child(1) { animation-delay: 0.05s; }
.metric-card:nth-child(2) { animation-delay: 0.1s; }
.metric-card:nth-child(3) { animation-delay: 0.15s; }
.metric-card:nth-child(4) { animation-delay: 0.2s; }

/* Responsive */
@media (max-width: 1200px) {
  .section-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 6px;
  }
  
  .metric-card .flex-grow-1 {
    margin-top: 0.5rem;
  }
}

@media (max-width: 768px) {
  .metric-card .d-flex {
    flex-direction: column;
    align-items: flex-start !important;
  }
  
  .metric-card .avatar-sm {
    margin-bottom: 0.75rem;
  }
}
</style>
