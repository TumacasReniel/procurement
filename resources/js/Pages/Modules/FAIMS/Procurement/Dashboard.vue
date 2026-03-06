tin<template>
	<Head title="Procurement Dashboard" />
	<PageHeader title="Procurement Dashboard" pageTitle="Overview" />



	<!-- Dashboard Filters -->
	<BRow class="mb-4">
		<BCol xl="12">
			<BCard>
				<BCardBody>
					<BRow class="align-items-end">
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
	<BRow class="mb-4">
		<BCol xl="3" md="6">
			<BCard class="card-animate">
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
			<BCard class="card-animate">
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
								<span class="counter-value" data-target="{{ dashboard.for_reviews }}">{{ dashboard.for_reviews }}</span>
							</h4>
						</div>
					</div>
				</BCardBody>
			</BCard>
		</BCol>

    	<BCol xl="3" md="6">
			<BCard class="card-animate">
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
								<span class="counter-value" data-target="{{ dashboard.for_approvals }}">{{ dashboard.for_approvals }}</span>
							</h4>
						</div>
					</div>
				</BCardBody>
			</BCard>
		</BCol>

		<BCol xl="3" md="6">
			<BCard class="card-animate">
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
								<span class="counter-value" data-target="{{ dashboard.completed_procurements }}">{{ dashboard.completed_procurements }}</span>
							</h4>
						</div>
					</div>
				</BCardBody>
			</BCard>
		</BCol>

		
	</BRow>

	<!-- Additional Metrics Cards -->
	<BRow class="mb-4">
    <BCol xl="3" md="6">
			<BCard class="card-animate">
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
								<span class="counter-value" data-target="{{ dashboard.total_quotations }}">{{ dashboard.total_quotations }}</span>
							</h4>
						</div>
					</div>
				</BCardBody>
			</BCard>
		</BCol>

		<BCol xl="3" md="6">
			<BCard class="card-animate">
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
								<span class="counter-value" data-target="{{ dashboard.total_bac_resolutions }}">{{ dashboard.total_bac_resolutions }}</span>
							</h4>
						</div>
					</div>
				</BCardBody>
			</BCard>
		</BCol>

		<BCol xl="3" md="6">
			<BCard class="card-animate">
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
								<span class="counter-value" data-target="{{ dashboard.total_notice_of_awards }}">{{ dashboard.total_notice_of_awards }}</span>
							</h4>
						</div>
					</div>
				</BCardBody>
			</BCard>
		</BCol>

		<BCol xl="3" md="6">
			<BCard class="card-animate">
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
								<span class="counter-value" data-target="{{ dashboard.total_purchase_orders }}">{{ dashboard.total_purchase_orders }}</span>
							</h4>
						</div>
					</div>
				</BCardBody>
			</BCard>
		</BCol>
	</BRow>

	<!-- Charts Row -->
	<BRow class="mb-4">
		<BCol xl="8">
			<BCard>
				<BCardHeader>
					<h4 class="card-title mb-0">Monthly Procurement Trends</h4>
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
			<BCard>
				<BCardHeader>
					<h4 class="card-title mb-0">Procurement by Division</h4>
				</BCardHeader>
				<BCardBody>
					<apexchart
						type="pie"
						height="350"
						:options="divisionChartOptions"
						:series="divisionChartSeries"
					></apexchart>
				</BCardBody>
			</BCard>
		</BCol>
	</BRow>

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
      divisionChartOptions: {
        chart: {
          type: 'pie',
          height: 350,
        },
        labels: [],
        colors: ['#0d6efd', '#ffc107', '#198754', '#dc3545', '#6c757d'],
        legend: {
          position: 'bottom',
        },
        tooltip: {
          y: {
            formatter: function (val) {
              return val + ' procurements';
            },
          },
        },
      },
      divisionChartSeries: [],
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

      // Update division chart
      if (this.dashboard && this.dashboard.division_distribution && Array.isArray(this.dashboard.division_distribution)) {
        this.divisionChartOptions.labels = this.dashboard.division_distribution.map(item => item.division);
        this.divisionChartSeries = this.dashboard.division_distribution.map(item => item.count);
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

    goCreatePage() {
      router.get("/faims/procurements/create", { option: "create" });
    },

    goViewPage(data) {
      router.get("/faims/procurements/" + data.id, { option: "view", tab: 1 });
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
  },
};
</script>

<style scoped>
.card-animate {
  transition: all 0.3s ease;
}

.card-animate:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}
</style>
