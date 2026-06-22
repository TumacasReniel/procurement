<template>
  <div>
    <Head title="Procurement Calendar" />
    <PageHeader title="Procurement Calendar" pageTitle="Procurement" />

    <div class="card bg-light-subtle shadow-none border">
      <div class="card-header bg-light-subtle">
        <div class="d-flex align-items-start justify-content-between gap-3">
          <div class="d-flex">
            <div class="flex-shrink-0 me-3">
              <div style="height: 2.5rem; width: 2.5rem">
                <span class="avatar-title bg-primary-subtle rounded p-2 mt-n1">
                  <i class="ri-calendar-event-line text-primary fs-24"></i>
                </span>
              </div>
            </div>
            <div class="flex-grow-1">
              <h5 class="mb-0 fs-14">
                <span class="text-body">Procurement Calendar</span>
              </h5>
              <p class="text-muted text-truncate-two-lines fs-12 mb-0">
                All purchase requests from all users, visualized by date. Click an event to view details.
              </p>
            </div>
          </div>
          <div class="d-flex align-items-center gap-2 flex-shrink-0">
            <!-- Legend -->
            <div class="d-flex align-items-center gap-2 me-2">
              <span v-for="s in statusColors" :key="s.label" class="d-inline-flex align-items-center gap-1 small">
                <span class="cal-legend-dot" :style="{ background: s.color }"></span>
                <span class="text-muted">{{ s.label }}</span>
              </span>
            </div>
            <Link href="/procurements" class="btn btn-sm btn-outline-secondary">
              <i class="ri-arrow-left-line me-1"></i>Back to List
            </Link>
          </div>
        </div>
      </div>

      <div class="card-body bg-white rounded-bottom p-3">
        <!-- Toolbar -->
        <div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
          <div class="input-group" style="max-width: 260px">
            <span class="input-group-text"><i class="ri-search-line"></i></span>
            <input
              v-model="keyword"
              type="text"
              class="form-control"
              placeholder="Filter events…"
              @input="onKeywordInput"
            />
            <button v-if="keyword" class="btn btn-outline-secondary" @click="keyword = ''; loadEvents()">
              <i class="ri-close-line"></i>
            </button>
          </div>

          <select v-model="selectedStatus" class="form-select" style="max-width: 180px" @change="loadEvents">
            <option value="">All Statuses</option>
            <option v-for="s in statuses" :key="s.id" :value="s.id">{{ s.name }}</option>
          </select>

          <select v-model="selectedUser" class="form-select" style="max-width: 200px" @change="loadEvents">
            <option value="">All Users</option>
            <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
          </select>

          <button class="btn btn-outline-secondary" :disabled="calLoading" @click="loadEvents" title="Refresh" v-b-tooltip.hover>
            <i class="bx bx-refresh" :class="{ 'spin': calLoading }"></i>
          </button>

          <div class="ms-auto d-flex gap-1">
            <button
              v-for="v in views"
              :key="v.key"
              class="btn btn-sm"
              :class="activeView === v.key ? 'btn-primary' : 'btn-outline-secondary'"
              @click="switchView(v.key)"
            >{{ v.label }}</button>
          </div>
        </div>

        <!-- Calendar -->
        <div v-if="calLoading && !calendarMounted" class="text-center text-muted py-5">
          <div class="spinner-border text-primary"></div>
          <div class="mt-2">Loading calendar…</div>
        </div>
        <FullCalendar
          ref="calRef"
          :options="calendarOptions"
          class="proc-calendar"
        />
      </div>
    </div>

    <!-- Event Detail Modal -->
    <b-modal
      v-model="showDetail"
      :title="calendarEventTitle(detailPr) || 'Purchase Request'"
      size="md"
      centered
      header-class="border-0 pb-0"
      footer-class="border-top"
    >
      <div v-if="detailPr" class="row g-3">
        <div class="col-6">
          <div class="small text-muted">PR No.</div>
          <div class="fw-semibold">{{ calendarEventTitle(detailPr) }}</div>
        </div>
        <div class="col-6">
          <div class="small text-muted">Date</div>
          <div class="fw-semibold">{{ detailPr.date }}</div>
        </div>
        <div class="col-12">
          <div class="small text-muted">Title / Purpose</div>
          <div class="fw-semibold">{{ detailPr.title || detailPr.purpose || '—' }}</div>
        </div>
        <div class="col-6">
          <div class="small text-muted">Requested By</div>
          <div class="fw-semibold">{{ calendarPersonName(detailPr.requested_by || detailPr.user) }}</div>
        </div>
        <div class="col-6">
          <div class="small text-muted">Status</div>
          <span class="badge" :style="{ background: statusColor(detailPr.status?.name), color: '#fff' }">
            {{ detailPr.status?.name ?? '—' }}
          </span>
        </div>
        <div class="col-6">
          <div class="small text-muted">Total Amount</div>
          <div class="fw-semibold">₱{{ formatNumber(detailPr.total_amount) }}</div>
        </div>
        <div class="col-6">
          <div class="small text-muted">Fund Cluster</div>
          <div class="fw-semibold">{{ detailPr.fund_cluster?.name ?? '—' }}</div>
        </div>
      </div>
      <template #footer>
        <b-button variant="light" @click="showDetail = false">Close</b-button>
        <Link :href="`/procurements/${detailPr?.id}`" class="btn btn-primary" @click="showDetail = false">
          <i class="ri-eye-line me-1"></i>View Full PR
        </Link>
      </template>
    </b-modal>
  </div>
</template>

<script>
import axios from "axios";
import { Head, Link } from "@inertiajs/vue3";
import PageHeader from "@/Shared/Components/PageHeader.vue";
import FullCalendar from "@fullcalendar/vue3";
import dayGridPlugin from "@fullcalendar/daygrid";
import listPlugin from "@fullcalendar/list";
import interactionPlugin from "@fullcalendar/interaction";

const STATUS_PALETTE = {
  pending:    "#6c757d",
  draft:      "#6c757d",
  submitted:  "#0d6efd",
  approved:   "#198754",
  rejected:   "#dc3545",
  cancelled:  "#dc3545",
  completed:  "#0dcaf0",
  ongoing:    "#fd7e14",
  reviewed:   "#20c997",
  awarded:    "#6f42c1",
  default:    "#4b5b93",
};

export default {
  name: "ProcurementCalendar",
  components: { Head, Link, PageHeader, FullCalendar },
  props: {
    statuses: { type: Array, default: () => [] },
    users:    { type: Array, default: () => [] },
  },
  data() {
    return {
      calLoading:     false,
      calendarMounted: false,
      keyword:        "",
      selectedStatus: "",
      selectedUser:   "",
      keywordTimer:   null,
      refreshTimer:   null,
      showDetail:     false,
      detailPr:       null,
      activeView:     "dayGridMonth",
      views: [
        { key: "dayGridMonth", label: "Month" },
        { key: "dayGridWeek",  label: "Week" },
        { key: "listMonth",    label: "List" },
      ],
      statusColors: [
        { label: "Pending/Draft", color: "#6c757d" },
        { label: "Submitted",     color: "#0d6efd" },
        { label: "Approved",      color: "#198754" },
        { label: "Ongoing",       color: "#fd7e14" },
        { label: "Completed",     color: "#0dcaf0" },
        { label: "Rejected",      color: "#dc3545" },
      ],
      calendarOptions: {
        plugins: [dayGridPlugin, listPlugin, interactionPlugin],
        initialView: "dayGridMonth",
        headerToolbar: {
          left:   "prev,next today",
          center: "title",
          right:  "",
        },
        height: "auto",
        events:       [],
        eventClick:   this.onEventClick,
        datesSet:     this.onDatesSet,
        eventDidMount(info) {
          info.el.title = info.event.extendedProps.tooltip ?? info.event.title;
        },
        noEventsContent: "No purchase requests found for this period.",
        listDayFormat: { weekday: "long", month: "long", day: "numeric", year: "numeric" },
      },
    };
  },
  mounted() {
    this.calendarMounted = true;
    this.loadEvents();
    this.refreshTimer = setInterval(() => this.loadEvents(), 30000);
  },
  beforeUnmount() {
    clearTimeout(this.keywordTimer);
    clearInterval(this.refreshTimer);
  },
  methods: {
    onKeywordInput() {
      clearTimeout(this.keywordTimer);
      this.keywordTimer = setTimeout(() => this.loadEvents(), 350);
    },
    onDatesSet() {
      this.loadEvents();
    },
    switchView(view) {
      this.activeView = view;
      this.$refs.calRef?.getApi()?.changeView(view);
    },
    statusColor(name) {
      const key = (name ?? "").toLowerCase().replace(/\s+/g, "");
      for (const [k, v] of Object.entries(STATUS_PALETTE)) {
        if (key.includes(k)) return v;
      }
      return STATUS_PALETTE.default;
    },
    async loadEvents() {
      // Pulling PR code/number from backend response.
      // If modal is blank, we need to ensure `detailPr` exists and has fields.
      // (No console logs here.)
      this.calLoading = true;
      // NOTE: PR code/number may be in different JSON keys depending on backend resource.
      // We'll defensively build a few candidate fields.
      // (Leaving no console logs to avoid clutter/hydration issues.)

      
      try {
        const api = this.$refs.calRef?.getApi();
        const start = api?.view?.currentStart;
        const end   = api?.view?.currentEnd;

        const params = {
          option:    "lists",
          calendar:   1,
          json:      1,
          keyword:   this.keyword   || undefined,
          status_id: this.selectedStatus || undefined,
          user_id:   this.selectedUser   || undefined,
          date_from: start ? start.toISOString().slice(0, 10) : undefined,
          date_to:   end   ? end.toISOString().slice(0, 10)   : undefined,
        };

        const response = await axios.get("/procurements", { params });
        const rows = response.data?.data ?? response.data ?? [];

        this.calendarOptions = {
          ...this.calendarOptions,
          events: rows.map(pr => ({
            id:    String(pr.id),
            title: this.calendarEventTitle(pr),
            start: pr.calendar_start_at || pr.created_at || pr.date_iso || pr.date,
            color: this.statusColor(pr.status?.name ?? pr.status),
            extendedProps: {
              pr,
              tooltip: [this.calendarEventTitle(pr), pr.title || pr.purpose, pr.status?.name, this.calendarPersonName(pr.requested_by || pr.user)]
                .filter(Boolean).join(" · "),
            },
          })),
        };
      } finally {
        this.calLoading = false;
      }
    },
    onEventClick(info) {
      this.detailPr = info.event.extendedProps.pr;
      this.showDetail = true;
    },
    calendarEventTitle(pr) {
      // Prefer PR code/number fields returned by backend.
      // Some APIs may send `pr_no`, others may send `code`, or `pr_number`.
      return (
        pr?.pr_no ||
        pr?.code ||
        pr?.pr_number ||
        "PR"
      );
    },
    calendarPersonName(value) {
      if (!value) return "—";
      if (typeof value === "string") return value;
      return value.name || value.profile?.full_name || value.profile?.name || "—";
    },
    formatNumber(val) {
      const n = parseFloat(val ?? 0);
      return isNaN(n) ? "0.00" : new Intl.NumberFormat("en-PH", { minimumFractionDigits: 2 }).format(n);
    },
  },
};
</script>

<style scoped>
@keyframes spin { to { transform: rotate(360deg); } }
.spin { animation: spin 0.75s linear infinite; display: inline-block; }

.cal-legend-dot {
  display: inline-block;
  width: 10px;
  height: 10px;
  border-radius: 50%;
  flex-shrink: 0;
}

.proc-calendar :deep(.fc-toolbar-title) {
  font-size: 1rem;
  font-weight: 700;
}

.proc-calendar :deep(.fc-event) {
  cursor: pointer;
  font-size: 0.72rem;
  font-weight: 600;
  padding: 1px 4px;
  border: none;
}

.proc-calendar :deep(.fc-daygrid-day-number) {
  font-size: 0.8rem;
}

.proc-calendar :deep(.fc-list-event:hover td) {
  background: #f0f5ff;
  cursor: pointer;
}
</style>
