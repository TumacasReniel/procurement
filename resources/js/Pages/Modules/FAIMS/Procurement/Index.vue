<template>
    <Head title="Requests" />
    <PageHeader title="Procurement Management" pageTitle="List" />
    <BRow class="procurement-index-page">
        <div class="col-md-12">
            <div class="card bg-light-subtle shadow-none border">
                <div class="card-header bg-light-subtle">
                    <div class="d-flex mb-n3 align-items-start">
                        <div class="flex-shrink-0 me-3">
                            <div style="height: 2.5rem; width: 2.5rem">
                                <span
                                    class="avatar-title bg-primary-subtle rounded p-2 mt-n1"
                                >
                                    <i
                                        class="ri-pin-distance-fill text-primary fs-24"
                                    ></i>
                                </span>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="mb-0 fs-14">
                                <span class="text-body"
                                    >Purchase Requests</span
                                >
                            </h5>
                            <p class="text-muted text-truncate-two-lines fs-12">
                                A detailed list of submitted purchase
                                requests including code, purpose, total amount,
                                fund source, and status.
                            </p>
                        </div>
                        <b-button
                            variant="light"
                            size="sm"
                            class="ms-2 flex-shrink-0"
                            v-b-tooltip.hover
                            title="How does the PR process work? (PR → Bidding → NOA → NTP → PO → IAR)"
                            @click="prProcessInfoModal.show = true"
                        >
                            <i class="ri-question-line text-primary me-1"></i>
                            Process Guide
                        </b-button>
                    </div>
                </div>

                <div class="car-body bg-white border-bottom shadow-none procurement-toolbar-row">
                    <b-row class="mb-2 ms-1 me-1" style="margin-top: 12px">
                        <b-col lg>
                            <div class="input-group mb-1">
                                <span class="input-group-text">
                                    <i class="ri-search-line search-icon"></i
                                ></span>
                                <input
                                    type="text"
                                    v-model="filter.keyword"
                                    placeholder="Search Procurement Request"
                                    class="form-control"
                                    style="width: 40%"
                                />
                                <Multiselect
                                    class="white"
                                    style="width: 15%"
                                    :options="dropdowns.statuses"
                                    v-model="filter.status"
                                    label="name"
                                    :searchable="true"
                                    placeholder="Select Status"
                                />
                                <select
                                    v-model="filter.sort"
                                    class="form-select"
                                    style="width: 16%"
                                >
                                    <option value="latest">Latest Date</option>
                                    <option value="oldest">Oldest Date</option>
                                    <option value="pr_asc">PR Number A-Z</option>
                                    <option value="pr_desc">PR Number Z-A</option>
                                </select>

                                <span
                                    @click="refresh()"
                                    class="input-group-text"
                                    v-b-tooltip.hover
                                    title="Refresh"
                                    style="cursor: pointer"
                                >
                                    <i class="bx bx-refresh search-icon"></i>
                                </span>


                                <b-button
                                    type="button"
                                    variant="primary"
                                    @click="goCreatePage"
                                >
                                    <i
                                        class="ri-add-circle-fill align-bottom me-1"
                                    ></i>
                                    Create
                                </b-button>
                                <b-button
                                    v-if="canCreateByCategory"
                                    type="button"
                                    variant="success"
                                    @click="goCreateByCategoryPage"
                                >
                                    <i
                                        class="ri-list-check-2 align-bottom me-1"
                                    ></i>
                                    Create by Category
                                </b-button>
                                <b-button
                                    type="button"
                                    variant="outline-secondary"
                                    title="Procurement Calendar"
                                    v-b-tooltip.hover
                                    @click="openCalendar"
                                >
                                    <i class="ri-calendar-event-line align-bottom"></i>
                                </b-button>
                            </div>
                        </b-col>
                    </b-row>
                </div>
                <b-card no-body>
                    <div class="card-body bg-white rounded-bottom mt-3">
                        <div
                            class="table-responsive table-card"
                            style="
                                margin-top: -39px;
                                height: calc(100vh - 350px);
                                overflow: auto;
                            "
                        >
                            <div v-if="!lists.length" class="procurement-empty-state">
                                <div class="procurement-empty-state__icon">
                                    <i class="ri-inbox-2-line"></i>
                                </div>
                                <h6 class="procurement-empty-state__title">No data yet added</h6>
                                <p class="procurement-empty-state__text">
                                    Purchase requests will appear here once they are created.
                                </p>
                            </div>

                            <table v-else class="table align-middle table-hover mb-0">
                                <thead class="table-light thead-fixed">
                                    <tr class="fs-12 fw-semibold">
                                        <th
                                            style="width: 4%"
                                            class="text-center"
                                        >
                                            #
                                        </th>
                                        <th style="width: 12%">Code</th>
                                        <th style="width: 18%">Purpose</th>
                                        <th style="width: 10%">Division</th>
                                        <th style="width: 11%">Total Amount/Fund Source</th>
                                        <th style="width: 11%">
                                            Created By/Date
                                        </th>
                                        <th style="width: 10%">Requested By</th>
                                        <th style="width: 10%">PAP Code</th>
                                        <th style="width: 12%">Status / Sub-status</th>
                                        <th
                                            style="width: 10%"
                                            class="text-center"
                                        >
                                            Actions
                                        </th>
                                    </tr>
                                </thead>

                                <tbody class="table-group-divider">
                                    <tr
                                        v-for="(list, index) in lists"
                                        v-bind:key="index"
                                        @click="selectRow(list.id)"
                                        :class="{
                                            'table-active':
                                                selectedRow === list.id,
                                        }"
                                        class="cursor-pointer"
                                    >
                                        <td class="text-center fw-semibold">
                                            {{ index + 1 }}
                                        </td>
                                        <td>
                                            <div
                                                class="d-flex align-items-center"
                                            >
                                                <div>
                                                    <h6
                                                        class="mb-0 fs-14 fw-semibold text-primary"
                                                    >
                                                        {{ list.code }}
                                                    </h6>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div
                                                class="text-truncate"
                                                style="max-width: 200px"
                                                v-b-tooltip.hover
                                                :title="list.purpose"
                                            >
                                                {{ list.purpose }}
                                            </div>
                                        </td>
                                        <td>{{ list.division?.name || '-' }}</td>
                                       
                                        <td>
                                            <span>{{ formatCurrency(list.total_amount) }}</span>
                                            <span
                                                class="badge bg-soft-info text-info px-2 py-1 fs-12 fw-medium rounded-pill"
                                            >
                                                {{ fundClusterLabel(list) }}
                                            </span>
                                        </td>
                                        <td>
                                            {{ list.created_by }}
                                            <p class="text-muted">
                                                {{ formatDate(list.date) }}
                                            </p>
                                        </td>
                                        <td>{{ list.requested_by }}</td>
                                        <td>
                                            <div class="d-flex flex-wrap gap-1">
                                                <span
                                                    v-for="(
                                                        code, idx
                                                    ) in list.codes"
                                                    :key="idx"
                                                    class="badge bg-soft-primary text-primary px-2 py-1 fs-12 fw-medium rounded-pill"
                                                    v-b-tooltip.hover
                                                    :title="formatPapCode(code)"
                                                >
                                                    {{ formatPapCode(code) }}
                                                </span>
                                            </div>
                                        </td>

                                        <td>
                                            <div class="d-flex align-items-center flex-wrap gap-1">
                                                <b-badge
                                                    :class="list.status.bg"
                                                    class="fs-11 me-1"
                                                >
                                                    {{ list.status?.name }}
                                                </b-badge>
                                                <b-badge
                                                    :class="list.sub_status?.bg"
                                                    class="fs-11"
                                                    v-if="shouldShowSubStatus(list)"
                                                >
                                                    {{ list.sub_status?.name }}
                                                </b-badge>
                                                <span v-else class="text-muted fs-12"></span>
                                            </div>
                                        </td>
                                        <td>
                                            <div
                                                class="d-flex justify-content-center gap-1"
                                            >
                                                <b-button
                                                    @click.stop="openChat(list)"
                                                    size="sm"
                                                    variant="secondary"
                                                    class="btn-icon position-relative overflow-visible"
                                                    v-b-tooltip.hover
                                                    :title="hasComments(list.comments_count) ? `${commentCountLabel(list.comments_count)} for ${list.code}` : 'Chat'"
                                                    style="border-radius: 8px"
                                                >
                                                    <i class="ri-chat-1-line"></i>
                                                    <span
                                                        v-if="hasComments(list.comments_count)"
                                                        class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger text-white"
                                                        style="font-size: 10px; min-width: 18px;"
                                                    >
                                                        {{ list.comments_count }}
                                                    </span>
                                                </b-button>

                                                <b-button
                                                    @click="goViewPage(list)"
                                                    size="sm"
                                                    variant="info"
                                                    class="btn-icon"
                                                    v-b-tooltip.hover
                                                    title="View"
                                                    style="border-radius: 8px"
                                                >
                                                    <i class="ri-eye-line"></i>
                                                </b-button>

                                                <b-button
                                                    v-if="canReviewProcurement(list)"
                                                    @click="goReviewPage(list)"
                                                    size="sm"
                                                    variant="success"
                                                    class="btn-icon"
                                                    v-b-tooltip.hover
                                                    title="Review"
                                                    style="border-radius: 8px"
                                                >
                                                    <i
                                                        class="ri-check-double-line"
                                                    ></i>
                                                </b-button>

                                                <b-button
                                                    v-if="canApproveProcurement(list)"
                                                    @click="goApprovePage(list)"
                                                    size="sm"
                                                    variant="success"
                                                    class="btn-icon"
                                                    v-b-tooltip.hover
                                                    title="Approve"
                                                    style="border-radius: 8px"
                                                >
                                                    <i
                                                        class="ri-check-line"
                                                    ></i>
                                                </b-button>

                                                <b-button
                                                    v-if="
                                                        list.status.name ==
                                                            'Pending' &&
                                                        list.created_by_id ==
                                                            $page.props.user
                                                                .data.id
                                                    "
                                                    @click="goEditPage(list)"
                                                    size="sm"
                                                    variant="primary"
                                                    class="btn-icon"
                                                    v-b-tooltip.hover
                                                    title="Edit"
                                                    style="border-radius: 8px"
                                                >
                                                    <i class="ri-edit-line"></i>
                                                </b-button>

                                                <b-button
                                                    v-if="canCancelProcurement(list)"
                                                    @click="openCancel(list)"
                                                    size="sm"
                                                    variant="danger"
                                                    class="btn-icon"
                                                    v-b-tooltip.hover
                                                    title="Cancel"
                                                    style="border-radius: 8px"
                                                >
                                                    <i
                                                        class="ri-close-line"
                                                    ></i>
                                                </b-button>

                                                <b-button
                                                    @click="openPrint(list)"
                                                    size="sm"
                                                    variant="dark"
                                                    class="btn-icon"
                                                    v-b-tooltip.hover
                                                    title="Print"
                                                    style="border-radius: 8px"
                                                >
                                                    <i
                                                        class="ri-printer-line"
                                                    ></i>
                                                </b-button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            <div v-if="lists.length" class="card-footer">
                                <Pagination
                                    class="ms-2 me-2 mt-n1"
                                    v-if="meta"
                                    @fetch="fetch"
                                    :lists="lists.length"
                                    :links="links"
                                    :pagination="meta"
                                />
                            </div>
                        </div>
                    </div>
                </b-card>
            </div>
        </div>
    </BRow>
    <Cancel @update="fetch()" ref="cancel" />

    <PRProcessInfoModal
        v-model:show="prProcessInfoModal.show"
        @close="prProcessInfoModal.show = false"
    />

    <!-- ── Procurement Calendar Modal ─────────────────────── -->
    <b-modal
        v-model="showCalendarModal"
        fullscreen
        hide-footer
        header-class="border-bottom pb-2"
        body-class="p-0 d-flex flex-column h-100"
        @shown="onCalendarModalShown"
    >
        <template #header>
            <div class="d-flex align-items-center gap-2 w-100">
                <span class="avatar-title bg-primary-subtle rounded p-2" style="width:2rem;height:2rem;display:inline-flex;align-items:center;justify-content:center">
                    <i class="ri-calendar-event-line text-primary"></i>
                </span>
                <div>
                    <div class="fw-bold fs-14">Procurement Calendar</div>
                    <div class="text-muted fs-12">All purchase requests visualized by date</div>
                </div>
                <button type="button" class="btn-close ms-auto" @click="showCalendarModal = false"></button>
            </div>
        </template>

        <!-- Toolbar Row 1: search + filters + view switcher -->
        <div class="d-flex align-items-center gap-2 px-3 pt-3 pb-2 flex-shrink-0 flex-wrap border-bottom">

            <!-- Prev / Today / Next -->
            <div class="btn-group">
                <button type="button" class="btn btn-outline-secondary btn-sm" title="Previous" v-b-tooltip.hover @click="calPrev">
                    <i class="ri-arrow-left-s-line"></i>
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm" @click="calToday">Today</button>
                <button type="button" class="btn btn-outline-secondary btn-sm" title="Next" v-b-tooltip.hover @click="calNext">
                    <i class="ri-arrow-right-s-line"></i>
                </button>
            </div>

            <!-- Current period title -->
            <span class="fw-semibold fs-14 text-body">{{ calTitle }}</span>

            <div class="vr mx-1"></div>

            <!-- Search -->
            <div class="input-group" style="max-width:220px">
                <span class="input-group-text"><i class="ri-search-line"></i></span>
                <input
                    v-model="calKeyword"
                    type="text"
                    class="form-control"
                    placeholder="Filter events…"
                    @input="onCalKeywordInput"
                />
                <button v-if="calKeyword" class="btn btn-outline-secondary" type="button" @click="calKeyword = ''; loadCalEvents()">
                    <i class="ri-close-line"></i>
                </button>
            </div>

            <select v-model="calStatus" class="form-select" style="max-width:150px" @change="loadCalEvents">
                <option value="">All Statuses</option>
                <option v-for="s in dropdowns.statuses" :key="s.id" :value="s.id">{{ s.name }}</option>
            </select>

            <button class="btn btn-outline-secondary btn-sm" type="button" :disabled="calLoading" title="Refresh" v-b-tooltip.hover @click="loadCalEvents">
                <i class="bx bx-refresh" :class="{'cal-spin': calLoading}"></i>
            </button>

            <!-- View switcher -->
            <div class="ms-auto d-flex gap-1">
                <button v-for="v in calViews" :key="v.key" type="button"
                    class="btn btn-sm"
                    :class="calActiveView === v.key ? 'btn-primary' : 'btn-outline-secondary'"
                    @click="switchCalView(v.key)"
                >{{ v.label }}</button>
            </div>
        </div>

        <!-- Toolbar Row 2: legend + event count -->
        <div class="d-flex align-items-center gap-3 px-3 py-2 flex-shrink-0 flex-wrap bg-light-subtle border-bottom">
            <span v-for="s in calLegend" :key="s.label" class="d-inline-flex align-items-center gap-1 small">
                <span class="cal-legend-dot" :style="{background: s.color}"></span>
                <span class="text-muted">{{ s.label }}</span>
            </span>
            <span v-if="calendarOptions.events.length" class="ms-auto small text-muted">
                {{ calendarOptions.events.length }} PR{{ calendarOptions.events.length !== 1 ? 's' : '' }} this period
            </span>
        </div>

        <!-- Calendar + side panel -->
        <div class="d-flex flex-grow-1" style="min-height: 0; overflow: hidden">

            <!-- Calendar area -->
            <div class="flex-grow-1 position-relative p-3" style="min-width: 0; overflow: auto">
                <div v-if="calLoading" class="cal-loading-overlay">
                    <div class="spinner-border spinner-border-sm text-primary"></div>
                    <span class="small text-primary ms-2">Loading…</span>
                </div>
                <FullCalendar ref="calRef" :options="calendarOptions" class="proc-fc" />
            </div>

            <!-- Detail side panel -->
            <transition name="cal-panel-slide">
                <div v-if="calDetail || calDayEvents.length" class="cal-side-panel border-start bg-white">

                    <!-- ── Day-list mode ── -->
                    <template v-if="calDayEvents.length && !calDetail">
                        <div class="d-flex align-items-center justify-content-between p-3 border-bottom">
                            <div>
                                <div class="fw-bold fs-14">
                                    <i class="ri-calendar-2-line me-1 text-primary"></i>
                                    {{ calDayDate ? calDayDate.toLocaleDateString('en-PH', { weekday: 'short', month: 'long', day: 'numeric' }) : '' }}
                                </div>
                                <div class="small text-muted">{{ calDayEvents.length }} purchase request{{ calDayEvents.length !== 1 ? 's' : '' }}</div>
                            </div>
                            <button type="button" class="btn-close" @click="calDayEvents = []; calDayDate = null" title="Close" v-b-tooltip.hover></button>
                        </div>
                        <div class="overflow-auto flex-grow-1 p-2">
                            <div
                                v-for="pr in calDayEvents"
                                :key="pr.id"
                                class="cal-day-pr-item d-flex align-items-start gap-2 p-2 rounded mb-1"
                                @click="calDayBack = { events: calDayEvents, date: calDayDate }; calDayEvents = []; calDetail = pr"
                            >
                                <span class="cal-legend-dot mt-1 flex-shrink-0" :style="{background: calStatusColor(pr.status?.name)}"></span>
                                <div class="flex-grow-1" style="min-width:0">
                                    <div class="fw-semibold text-primary fs-13">{{ pr.code }}</div>
                                    <div class="text-truncate small text-muted">{{ pr.title || pr.purpose || '—' }}</div>
                                    <span class="badge mt-1" :style="{background: calStatusColor(pr.status?.name) + '22', color: calStatusColor(pr.status?.name), border: '1px solid ' + calStatusColor(pr.status?.name) + '44'}">
                                        {{ pr.status?.name }}
                                    </span>
                                </div>
                                <i class="ri-arrow-right-s-line text-muted mt-1 flex-shrink-0"></i>
                            </div>
                        </div>
                    </template>

                    <!-- ── Single PR detail mode ── -->
                    <template v-else-if="calDetail">
                        <div class="d-flex align-items-center justify-content-between p-3 border-bottom">
                            <div class="d-flex align-items-center gap-2">
                                <button v-if="calDayBack" type="button" class="btn btn-sm btn-outline-secondary py-0 px-1" @click="calDayEvents = calDayBack.events; calDayDate = calDayBack.date; calDayBack = null; calDetail = null" title="Back to day list" v-b-tooltip.hover>
                                    <i class="ri-arrow-left-s-line"></i>
                                </button>
                                <div class="fw-bold text-primary fs-14">{{ calDetail.code }}</div>
                            </div>
                            <button type="button" class="btn-close" @click="calDetail = null; calDayBack = null" title="Close" v-b-tooltip.hover></button>
                        </div>
                        <div class="p-3 overflow-auto flex-grow-1">
                            <div class="mb-3">
                                <div class="small text-muted mb-1">PR Date</div>
                                <div class="fw-semibold">{{ calDetail.date || '—' }}</div>
                            </div>
                            <div class="mb-3">
                                <div class="small text-muted mb-1">Title / Purpose</div>
                                <div class="fw-semibold">{{ calDetail.title || calDetail.purpose || '—' }}</div>
                            </div>
                            <div class="mb-3">
                                <div class="small text-muted mb-1">Status</div>
                                <span class="badge" :style="{background: calStatusColor(calDetail.status?.name), color:'#fff'}">
                                    {{ calDetail.status?.name ?? '—' }}
                                </span>
                            </div>
                            <div class="mb-3">
                                <div class="small text-muted mb-1">Requested By</div>
                                <div class="fw-semibold">{{ calDetail.requested_by || '—' }}</div>
                            </div>
                            <div class="mb-3">
                                <div class="small text-muted mb-1">Total Amount</div>
                                <div class="fw-semibold text-success fs-15">₱{{ formatCalNumber(calDetail.total_amount) }}</div>
                            </div>
                            <div class="d-grid gap-2">
                                <a v-if="canViewCalPr(calDetail)" :href="`/faims/procurements/${calDetail.id}?option=view`" class="btn btn-primary">
                                    <i class="ri-eye-line me-1"></i>View Full PR
                                </a>
                                <button type="button" class="btn btn-outline-secondary" @click="calDetail = null; calDayBack = null">
                                    Close
                                </button>
                            </div>
                        </div>
                    </template>

                </div>
            </transition>
        </div>
    </b-modal>

    <FloatingRequestChat
        :requests="chatRequests"
        :requests-loading="chatRequestsLoading"
        :selected-request-id="activeChatRequestId"
        :selected-request="activeChatRequest"
        :loading="chatLoading"
        :open="isChatOpen"
        @toggle="toggleChat"
        @select-request="selectChatRequest"
        @comment-added="appendChatComment"
    />
</template>
<script>
import _ from "lodash";

import PageHeader from "@/Shared/Components/PageHeader.vue";
import Pagination from "@/Shared/Components/Pagination.vue";
import FloatingRequestChat from "./Pages/Components/FloatingRequestChat.vue";
import { router } from "@inertiajs/vue3";
import Multiselect from "@vueform/multiselect";
import Cancel from "./Modals/Cancel.vue";
import PRProcessInfoModal from "./Modals/PRProcessInfoModal.vue";
import FullCalendar from "@fullcalendar/vue3";
import dayGridPlugin from "@fullcalendar/daygrid";
import timeGridPlugin from "@fullcalendar/timegrid";
import listPlugin from "@fullcalendar/list";
import interactionPlugin from "@fullcalendar/interaction";
import bootstrapPlugin from "@fullcalendar/bootstrap";
import axios from "axios";
export default {
    components: { PageHeader, Pagination, Multiselect, Cancel, FloatingRequestChat, FullCalendar, PRProcessInfoModal },
    props: ["dropdowns", "roles", "comment_request_id"],
    data() {
        return {
            currentUrl: window.location.origin,
            lists: [],
            meta: {},
            links: {},
            filter: {
                keyword: null,
                type: null,
                status: null,
                sort: "latest",
                mode: null,
                expense: null,
                leave: null,
            },
            icons: [
                "ri-flight-takeoff-fill",
                "ri-car-fill",
                "ri-calendar-2-fill",
                "ri-alarm-fill",
            ],
            selectedRow: null,
            view_type: "all",
            index: null,
            units: [],
            isChatOpen: false,
            chatLoading: false,
            chatRequestsLoading: false,
            chatRequests: [],
            activeChatRequestId: null,
            activeChatRequest: null,
            pendingChatRequestId: this.comment_request_id ? Number(this.comment_request_id) : null,
            procurementRequestChannel: null,
            prProcessInfoModal: { show: false },
            // Calendar
            showCalendarModal: false,
            calendarReady: false,
            calLoading: false,
            calKeyword: "",
            calStatus: "",
            calKeywordTimer: null,
            calRefreshTimer: null,
            calDetail: null,
            calDayEvents: [],
            calDayDate: null,
            calDayBack: null,
            calActiveView: "dayGridMonth",
            calViews: [
                { key: "dayGridMonth", label: "Month" },
                { key: "timeGridWeek", label: "Week"  },
                { key: "timeGridDay",  label: "Day"   },
                { key: "listMonth",    label: "List"  },
            ],
            calLegend: [
                { label: "Pending",   color: "#6c757d" },
                { label: "Submitted", color: "#0d6efd" },
                { label: "Approved",  color: "#198754" },
                { label: "Ongoing",   color: "#fd7e14" },
                { label: "Completed", color: "#0dcaf0" },
                { label: "Rejected",  color: "#dc3545" },
            ],
            calTitle: "",
            calendarOptions: {
                timeZone: "Asia/Manila",
                plugins: [dayGridPlugin, timeGridPlugin, listPlugin, interactionPlugin, bootstrapPlugin],
                themeSystem: "bootstrap",
                navLinks: true,
                showNonCurrentDates: false,
                fixedWeekCount: false,
                initialView: "dayGridMonth",
                headerToolbar: false,
                height: "auto",
                events: [],
                dayMaxEvents: 3,
                moreLinkClick: null,
                eventClick: null,
                datesSet: null,
                noEventsContent: "No purchase requests found for this period.",
                windowResize: () => {
                    const view = this.getCalInitialView();
                    this.$refs.calRef?.getApi()?.changeView(view);
                    this.calActiveView = view;
                },
                eventDidMount(info) {
                    info.el.setAttribute("title", info.event.extendedProps.tooltip ?? info.event.title);
                },
            },
        };
    },
    computed: {
        canCreateByCategory() {
            const roles = this.$page.props.roles || [];
            return roles.includes("Procurement Encoder") || roles.includes("Administrator");
        },
    },
    watch: {
        comment_request_id: {
            handler(newValue) {
                this.pendingChatRequestId = newValue ? Number(newValue) : null;
                this.tryOpenPendingChat();
            },
        },
        "filter.keyword"(newVal) {
            this.checkSearchStr(newVal);
        },
        "filter.status"(newVal) {
            this.fetch();
        },
        "filter.mode"(newVal) {
            this.fetch();
        },
        "filter.sort"(newVal) {
            this.fetch();
        },
        "filter.expense"(newVal) {
            this.fetch();
        },
        showCalendarModal(isOpen) {
            if (!isOpen) {
                clearInterval(this.calRefreshTimer);
                this.calRefreshTimer = null;
            }
        },
    },
    created() {
        this.fetch();
        this.fetchChatRequests();
    },
    mounted() {
        this.subscribeToProcurementRequestChanges();
    },
    beforeUnmount() {
        clearTimeout(this.calKeywordTimer);
        clearInterval(this.calRefreshTimer);
        this.unsubscribeFromProcurementRequestChanges();
    },
    methods: {
        subscribeToProcurementRequestChanges() {
            if (!window.Echo || this.procurementRequestChannel) {
                return;
            }

            this.procurementRequestChannel = "procurement-requests";
            window.Echo.private(this.procurementRequestChannel)
                .listen(".procurement-request.changed", () => {
                    this.fetch();
                    this.fetchChatRequests();
                });
        },
        unsubscribeFromProcurementRequestChanges() {
            if (!window.Echo || !this.procurementRequestChannel) {
                return;
            }

            window.Echo.leave(this.procurementRequestChannel);
            this.procurementRequestChannel = null;
        },
        shouldShowSubStatus(list) {
            const statusName = String(list?.status?.name || "").trim().toLowerCase();
            const subStatusName = String(list?.sub_status?.name || "").trim().toLowerCase();

            return Boolean(subStatusName && subStatusName !== statusName);
        },
        canCancelProcurement(list) {
            const currentUserId = Number(this.$page.props.user?.data?.id || 0);
            const createdById = Number(list?.created_by_id || 0);

            return list?.status?.name === "Pending" && currentUserId > 0 && createdById === currentUserId;
        },
        canReviewProcurement(list) {
            const currentUserId = Number(this.$page.props.user?.data?.id || 0);
            const createdById = Number(list?.created_by_id || 0);
            const roles = this.$page.props.roles || [];

            return (
                list?.status?.name === "Pending" &&
                roles.includes("Procurement Officer") &&
                currentUserId > 0 &&
                createdById !== currentUserId
            );
        },
        canApproveProcurement(list) {
            const roles = this.$page.props.roles || [];
            const procurementApprovalUserIds =
                this.$page.props.procurement_approval_user_ids || [];

            return (
                list?.status?.name === "Reviewed" &&
                (roles.includes("Procurement Officer") ||
                    roles.includes("Procurement Staff") ||
                    roles.includes("Administrator") ||
                    procurementApprovalUserIds.includes(list.approved_by_id))
            );
        },
        checkSearchStr: _.debounce(function (string) {
            this.fetch();
        }, 300),
        fetch(page_url) {
            page_url = page_url || "/faims/procurements";
            axios
                .get(page_url, {
                    params: {
                        keyword: this.filter.keyword,
                        type: this.filter.type,
                        status: this.filter.status,
                        sort: this.filter.sort,
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
        fetchChatRequests() {
            this.chatRequestsLoading = true;

            return axios
                .get("/faims/procurements", {
                    params: {
                        option: "chat_lists",
                    },
                })
                .then((response) => {
                    this.chatRequests = Array.isArray(response.data?.data)
                        ? response.data.data
                        : [];
                })
                .catch((error) => {
                    console.log(error);
                    this.chatRequests = [];
                })
                .finally(() => {
                    this.chatRequestsLoading = false;
                    this.tryOpenPendingChat();
                });
        },
        ensureChatRequestsLoaded() {
            if (this.chatRequests.length || this.chatRequestsLoading) {
                return;
            }

            this.fetchChatRequests();
        },
        formatDate(date) {
            return new Date(date).toLocaleDateString("en-US", {
                year: "numeric",
                month: "short",
                day: "numeric",
            });
        },

        formatDateRange(start, end) {
            const startDate = new Date(start);
            const endDate = new Date(end);

            const options = { month: "long", day: "numeric" };
            const startStr = startDate.toLocaleDateString("en-US", options);
            const endStr = endDate.toLocaleDateString("en-US", {
                day: "numeric",
            });

            if (start === end) {
                return startDate.toLocaleDateString("en-US", {
                    month: "long",
                    day: "numeric",
                    year: "numeric",
                });
            }

            const year = startDate.getFullYear(); // assume same year
            return `${startStr}-${endStr}, ${year}`;
        },

        formatCurrency(value) {
            return new Intl.NumberFormat("en-PH", {
                style: "currency",
                currency: "PHP",
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            }).format(Number(value) || 0);
        },

        fundClusterLabel(list) {
            return list?.fund_cluster_name || list?.fund_cluster?.name || "-";
        },

        formatPapCode(codeGroup) {
            const code = codeGroup?.procurement_code?.code;
            const title = codeGroup?.procurement_code?.title;

            if (code && title) {
                return `${code} - ${title}`;
            }

            return (
                code ||
                title ||
                codeGroup?.procurement_code?.mode_of_procurement?.name ||
                "-"
            );
        },
        commentCountLabel(count) {
            const total = Number(count) || 0;

            return `${total} ${total === 1 ? "chat" : "chats"}`;
        },
        hasComments(count) {
            return Number(count) > 0;
        },
        getLatestChat(request) {
            if (!request) {
                return null;
            }

            if (request.latest_comment) {
                return request.latest_comment;
            }

            const comments = Array.isArray(request.comments) ? request.comments : [];

            if (!comments.length) {
                return null;
            }

            return comments.reduce((latest, comment) => {
                if (!comment?.created_at) {
                    return latest;
                }

                if (!latest?.created_at) {
                    return comment;
                }

                return new Date(comment.created_at) > new Date(latest.created_at)
                    ? comment
                    : latest;
            }, null);
        },
        getLatestChatTimestamp(request) {
            const latestComment = this.getLatestChat(request);

            if (latestComment?.created_at) {
                return latestComment.created_at;
            }

            if (request?.latest_comment_at) {
                return request.latest_comment_at;
            }

            if (request?.comments_max_created_at) {
                return request.comments_max_created_at;
            }

            return null;
        },
        syncRequestChatMeta(requestId, count, latestComment = null) {
            const normalizedId = Number(requestId);
            const normalizedCount = Number(count) || 0;
            const normalizedLatestComment = latestComment || null;
            const latestCommentAt =
                normalizedLatestComment?.created_at || null;

            this.lists = this.lists.map((list) =>
                Number(list.id) === normalizedId
                    ? {
                        ...list,
                        comments_count: normalizedCount,
                        latest_comment_at: latestCommentAt || list.latest_comment_at || null,
                        latest_comment: normalizedLatestComment || list.latest_comment || null,
                    }
                    : list,
            );

            this.chatRequests = this.chatRequests.map((request) =>
                Number(request.id) === normalizedId
                    ? {
                        ...request,
                        comments_count: normalizedCount,
                        latest_comment_at: latestCommentAt || request.latest_comment_at || null,
                        latest_comment: normalizedLatestComment || request.latest_comment || null,
                    }
                    : request,
            );

            if (Number(this.activeChatRequest?.id) === normalizedId) {
                this.activeChatRequest = {
                    ...this.activeChatRequest,
                    comments_count: normalizedCount,
                    latest_comment_at: latestCommentAt || this.activeChatRequest.latest_comment_at || null,
                    latest_comment: normalizedLatestComment || this.activeChatRequest.latest_comment || null,
                };
            }
        },

        view(view_type) {
            this.view_type = view_type;
        },

        goCreatePage() {
            router.get("/faims/procurements/create", { option: "create" });
        },

        goCreateByCategoryPage() {
            router.get("/faims/procurements/create-by-category");
        },

        goViewPage(data) {
            router.get("/faims/procurements/" + data.id, {
                option: "view",
            });
        },

        goEditPage(data) {
            router.get("/faims/procurements/" + data.id, { option: "edit" });
        },

        goReviewPage(data) {
            if (data.is_create_by_category) {
                router.get("/faims/procurements/create-by-category", {
                    id: data.id,
                    option: "review",
                });
                return;
            }

            router.get("/faims/procurements/" + data.id, { option: "review" });
        },

        goApprovePage(data) {
            if (data.is_create_by_category) {
                router.get("/faims/procurements/create-by-category", {
                    id: data.id,
                    option: "approve",
                });
                return;
            }

            router.get("/faims/procurements/" + data.id, { option: "approve" });
        },

        goQuotationPage(data) {
            router.get("/faims/procurements/" + data.id, {
                option: "quotations",
            });
        },

        goBidsPage(data) {
            router.get("/faims/procurements/" + data.id, { option: "bids" });
        },

        goBACResolutionPage(data) {
            router.get("/faims/procurements/" + data.id, {
                option: "bac_resolutions",
            });
        },

        goReawardPage(data) {
            router.get("/faims/procurements/" + data.id, {
                option: "bac_resolutions",
            });
        },

        openCancel(data) {
            if (!this.canCancelProcurement(data)) {
                return;
            }

            this.$refs.cancel.show(data);
        },
        toggleChat() {
            this.isChatOpen = !this.isChatOpen;

            if (this.isChatOpen) {
                this.ensureChatRequestsLoaded();
            }
        },
        openChat(data) {
            if (!data?.id) {
                return;
            }

            this.isChatOpen = true;
            this.ensureChatRequestsLoaded();
            this.selectChatRequest(data.id);
        },
        tryOpenPendingChat() {
            const requestId = Number(this.pendingChatRequestId);

            if (!requestId) {
                return;
            }

            if (this.chatRequestsLoading) {
                return;
            }

            if (!this.chatRequests.length) {
                this.fetchChatRequests();
                return;
            }

            const hasMatchingRequest = this.chatRequests.some(
                (request) => Number(request.id) === requestId,
            );

            if (!hasMatchingRequest) {
                this.pendingChatRequestId = null;
                this.clearPendingChatQuery();
                return;
            }

            this.isChatOpen = true;
            this.selectChatRequest(requestId);
            this.pendingChatRequestId = null;
            this.clearPendingChatQuery();
        },
        clearPendingChatQuery() {
            const url = new URL(window.location.href);

            if (!url.searchParams.has("comment_request_id")) {
                return;
            }

            url.searchParams.delete("comment_request_id");
            const nextUrl = `${url.pathname}${url.search}${url.hash}`;

            window.history.replaceState({}, "", nextUrl);
        },
        selectChatRequest(requestId) {
            if (!requestId) {
                this.activeChatRequestId = null;
                this.activeChatRequest = null;
                this.chatLoading = false;
                return;
            }

            const normalizedId = Number(requestId);

            if (
                this.activeChatRequestId === normalizedId &&
                this.activeChatRequest?.id === normalizedId
            ) {
                return;
            }

            this.activeChatRequestId = normalizedId;
            this.chatLoading = true;

            axios
                .get(`/faims/procurements/${normalizedId}`, {
                    params: {
                        option: "comments",
                    },
                })
                .then((response) => {
                    this.activeChatRequest = response.data?.data || null;

                    if (this.activeChatRequest?.id) {
                        const count = Array.isArray(this.activeChatRequest.comments)
                            ? this.activeChatRequest.comments.length
                            : Number(this.activeChatRequest.comments_count) || 0;
                        const latestChat = this.getLatestChat(this.activeChatRequest);

                        this.syncRequestChatMeta(
                            this.activeChatRequest.id,
                            count,
                            latestChat,
                        );
                    }
                })
                .catch((error) => {
                    console.log(error);
                    this.activeChatRequest = null;
                })
                .finally(() => {
                    this.chatLoading = false;
                });
        },
        appendChatComment(comment) {
            if (
                !this.activeChatRequest ||
                comment?.commentable_type !== "App\\Models\\Procurement" ||
                Number(comment?.commentable_id) !== Number(this.activeChatRequest.id)
            ) {
                return;
            }

            if (!Array.isArray(this.activeChatRequest.comments)) {
                this.activeChatRequest.comments = [];
            }

            const alreadyExists = this.activeChatRequest.comments.some(
                (existingComment) => Number(existingComment.id) === Number(comment.id),
            );

            if (!alreadyExists) {
                this.activeChatRequest.comments.push(comment);
                this.activeChatRequest.latest_comment = comment;
                this.syncRequestChatMeta(
                    this.activeChatRequest.id,
                    this.activeChatRequest.comments.length,
                    comment,
                );
            }
        },

        openPrint(data) {
            window.open(
                `/faims/procurements/${data.id}?option=print&type=procurement`,
            );
        },

        selectRow(index) {
            this.selectedRow = this.selectedRow == index ? null : index;
        },

        refresh() {
            this.filter.expense = null;
            this.filter.mode = null;
            this.filter.keyword = null;
            this.filter.sort = "latest";
            this.fetch();
        },

        // ── Calendar ──────────────────────────────────────
        getCalInitialView() {
            if (window.innerWidth >= 768 && window.innerWidth < 1200) {
                return "timeGridWeek";
            } else if (window.innerWidth <= 768) {
                return "listMonth";
            } else {
                return "dayGridMonth";
            }
        },
        openCalendar() {
            this.calDetail = null;
            this.calDayEvents = [];
            this.calDayDate = null;
            this.calDayBack = null;
            this.calKeyword = "";
            this.calStatus = "";
            this.showCalendarModal = true;
        },
        onCalendarModalShown() {
            this.calendarReady = true;
            clearInterval(this.calRefreshTimer);
            this.$nextTick(() => {
                const api = this.$refs.calRef?.getApi();
                if (api) {
                    const view = this.getCalInitialView();
                    api.changeView(view);
                    this.calActiveView = view;
                    api.on("datesSet", (info) => {
                        this.calTitle = info.view.title;
                        this.loadCalEvents();
                    });
                    api.setOption("eventClick", (info) => {
                        this.calDayEvents = [];
                        this.calDayBack = null;
                        this.calDetail = info.event.extendedProps.pr;
                    });
                    api.setOption("moreLinkClick", (info) => {
                        info.jsEvent?.preventDefault();
                        this.calDetail = null;
                        this.calDayBack = null;
                        this.calDayDate = info.date;
                        this.calDayEvents = (info.allSegs || [])
                            .map(seg => seg.event.extendedProps?.pr)
                            .filter(Boolean);
                        return "stop";
                    });
                }
                this.loadCalEvents();
                this.calRefreshTimer = setInterval(() => this.loadCalEvents(), 30000);
            });
        },
        onCalKeywordInput() {
            clearTimeout(this.calKeywordTimer);
            this.calKeywordTimer = setTimeout(() => this.loadCalEvents(), 350);
        },
        switchCalView(view) {
            this.calActiveView = view;
            this.$refs.calRef?.getApi()?.changeView(view);
        },
        calPrev()  { this.$refs.calRef?.getApi()?.prev(); },
        calNext()  { this.$refs.calRef?.getApi()?.next(); },
        calToday() { this.$refs.calRef?.getApi()?.today(); },
        calStatusColor(name) {
            const palette = {
                pending: "#6c757d", draft: "#6c757d",
                submitted: "#0d6efd", approved: "#198754",
                rejected: "#dc3545", cancelled: "#dc3545",
                completed: "#0dcaf0", ongoing: "#fd7e14",
                reviewed: "#20c997", awarded: "#6f42c1",
            };
            const key = (name ?? "").toLowerCase().replace(/\s+/g, "");
            for (const [k, v] of Object.entries(palette)) {
                if (key.includes(k)) return v;
            }
            return "#4b5b93";
        },
        async loadCalEvents() {
            this.calLoading = true;
            this.calDayEvents = [];
            this.calDayDate = null;
            this.calDayBack = null;
            try {
                const api = this.$refs.calRef?.getApi();
                const start = api?.view?.currentStart;
                const end   = api?.view?.currentEnd;
                const response = await axios.get("/faims/procurements", {
                    params: {
                        option:    "lists",
                        calendar:   1,
                        json:      1,
                        keyword:   this.calKeyword   || undefined,
                        status_id: this.calStatus    || undefined,
                        date_from: start ? start.toISOString().slice(0, 10) : undefined,
                        date_to:   end   ? end.toISOString().slice(0, 10)   : undefined,
                    },
                });
                const rows = response.data?.data ?? response.data ?? [];
                this.calendarOptions.events = rows.map(pr => ({
                    id:    String(pr.id),
                    title: this.calendarEventTitle(pr),
                    start: (pr.created_at || pr.date_iso || "").slice(0, 10),
                    allDay: true,
                    color: this.calStatusColor(pr.status?.name),
                    extendedProps: {
                        pr,
                        tooltip: [this.calendarEventTitle(pr), pr.title || pr.purpose, pr.status?.name]
                            .filter(Boolean).join(" · "),
                    },
                }));
            } catch (e) {
                console.error("Calendar load failed:", e);
            } finally {
                this.calLoading = false;
            }
        },
        formatCalDate(val) {
            if (!val) return "—";
            return new Date(String(val).replace(" ", "T"))
                .toLocaleDateString("en-PH", { year: "numeric", month: "long", day: "numeric" });
        },
        calendarEventTitle(pr) {
            return pr?.pr_no || pr?.code || "PR";
        },
        formatCalNumber(val) {
            const n = parseFloat(val ?? 0);
            return isNaN(n) ? "0.00"
                : new Intl.NumberFormat("en-PH", { minimumFractionDigits: 2 }).format(n);
        },
        canViewCalPr(pr) {
            if (!pr) return false;
            const roles = this.$page.props.roles || [];
            const elevated = ["Administrator", "Procurement Officer", "Procurement Staff", "Procurement Encoder"];
            if (elevated.some(r => roles.includes(r))) return true;
            const uid = Number(this.$page.props.user?.data?.id || 0);
            return (
                Number(pr.created_by_id) === uid ||
                Number(pr.requested_by_id) === uid ||
                Number(pr.approved_by_id) === uid
            );
        },
    },
};
</script>

<style scoped>
/* Keep the search/filter/calendar toolbar in view while the request list
   below is scrolled, instead of it scrolling away with the page. */
.procurement-toolbar-row {
    position: sticky;
    top: 0;
    z-index: 20;
}

.procurement-empty-state {
    min-height: 320px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 2rem;
    text-align: center;
    color: #64748b;
}

.procurement-empty-state__icon {
    width: 56px;
    height: 56px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 0.8rem;
    border-radius: 14px;
    background: #eef4ff;
    color: #405189;
    font-size: 1.6rem;
}

.procurement-empty-state__title {
    margin: 0;
    color: #1e293b;
    font-size: 1rem;
    font-weight: 700;
}

.procurement-empty-state__text {
    max-width: 360px;
    margin: 0.35rem 0 0;
    font-size: 0.9rem;
}

:global([data-bs-theme="dark"]) .procurement-empty-state {
    color: #9fb0c7;
}

:global([data-bs-theme="dark"]) .procurement-empty-state__icon {
    background: #232c3a;
    color: #9cb7ff;
}

:global([data-bs-theme="dark"]) .procurement-empty-state__title {
    color: #e5edf7;
}

/* ── Calendar modal ─────────────────────────────────── */
@keyframes cal-spin { to { transform: rotate(360deg); } }
.cal-spin { animation: cal-spin 0.75s linear infinite; display: inline-block; }

.cal-legend-dot {
    display: inline-block;
    width: 9px;
    height: 9px;
    border-radius: 50%;
    flex-shrink: 0;
}

.proc-fc :deep(.fc-toolbar-title) {
    font-size: 1rem;
    font-weight: 700;
}

.proc-fc :deep(.fc-event) {
    cursor: pointer;
    font-size: 0.72rem;
    font-weight: 700;
    padding: 2px 8px;
    border: none;
    border-radius: 20px;
    transition: opacity 0.15s ease, transform 0.15s ease, box-shadow 0.15s ease;
}

.proc-fc :deep(.fc-event:hover) {
    opacity: 1 !important;
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.18);
}

.proc-fc :deep(.fc-daygrid-day-number) {
    font-size: 0.8rem;
}

.proc-fc :deep(.fc-list-event:hover td) {
    background: #f0f5ff;
    cursor: pointer;
}

.cal-loading-overlay {
    position: absolute;
    inset: 0;
    z-index: 10;
    background: rgba(255, 255, 255, 0.72);
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    backdrop-filter: blur(1px);
}

.cal-side-panel {
    width: 280px;
    flex-shrink: 0;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.cal-panel-slide-enter-active,
.cal-panel-slide-leave-active {
    transition: max-width 0.22s ease, opacity 0.18s ease;
    overflow: hidden;
}

.cal-panel-slide-enter-from,
.cal-panel-slide-leave-to {
    max-width: 0;
    opacity: 0;
}

.cal-panel-slide-enter-to,
.cal-panel-slide-leave-from {
    max-width: 280px;
    opacity: 1;
}

.cal-day-pr-item {
    cursor: pointer;
    border: 1px solid transparent;
    transition: background 0.12s ease, border-color 0.12s ease;
}

.cal-day-pr-item:hover {
    background: #f0f5ff;
    border-color: #dce4f2;
}
</style>
