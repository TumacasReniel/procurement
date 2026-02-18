<template>
    <Head title="Requests" />
    <PageHeader title="Procurement Requests" pageTitle="List" />
    <BRow>
        <div class="col-md-12">
            <div class="card bg-light-subtle shadow-none border">
                <div class="card-header bg-light-subtle">
                    <div class="d-flex mb-n3">
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
                                    >Procurement Requests</span
                                >
                            </h5>
                            <p class="text-muted text-truncate-two-lines fs-12">
                                A detailed list of submitted procurement
                                requests including code, purpose, title, and
                                status.
                            </p>
                        </div>
                        <div class="flex-shrink-0" style="width: 45%"></div>
                    </div>
                </div>

                <div class="car-body bg-white border-bottom shadow-none">
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
                            <table class="table align-middle table-hover mb-0">
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
                                        <th style="width: 12%">Division</th>
                                        <th style="width: 12%">
                                            Created By/Date
                                        </th>
                                        <th style="width: 12%">Requested By</th>
                                        <th style="width: 10%">PAP Code</th>
                                        <th style="width: 10%">Status</th>
                                        <th style="width: 10%">Sub-status</th>
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
                                        <td>{{ list.division?.name }}</td>
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
                                                >
                                                    {{
                                                        code?.procurement_code
                                                            ?.mode_of_procurement
                                                            ?.name
                                                    }}
                                                </span>
                                            </div>
                                        </td>

                                        <td>
                                            <b-badge
                                                :class="list.status.bg"
                                                class="fs-11"
                                                >{{
                                                    list.status?.name
                                                }}</b-badge
                                            >
                                        </td>
                                        <td>
                                            <b-badge
                                                :class="list.sub_status?.bg"
                                                class="fs-11"
                                                v-if="list.sub_status"
                                            >
                                                {{ list.sub_status?.name }}
                                            </b-badge>
                                            <span v-else class="text-muted"
                                                >-</span
                                            >
                                        </td>
                                        <td>
                                            <div
                                                class="d-flex justify-content-center gap-1"
                                            >
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
                                                    v-if="
                                                        list.status.name ==
                                                            'Pending' &&
                                                        $page.props.roles.includes(
                                                            'Procurement Officer',
                                                        )
                                                    "
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
                                                    v-if="
                                                        list.status.name ==
                                                            'Reviewed' &&
                                                        ($page.props.roles.includes(
                                                            'Procurement Officer',
                                                        ) ||
                                                            $page.props.roles.includes(
                                                                'Procurement Staff',
                                                            ) ||
                                                            $page.props.is_regional_director)
                                                    "
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
                                                        'Pending'
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
                                                    v-if="
                                                        list.status.name ==
                                                        'Pending'
                                                    "
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
                            <div class="card-footer">
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
</template>
<script>
import _ from "lodash";

import PageHeader from "@/Shared/Components/PageHeader.vue";
import Pagination from "@/Shared/Components/Pagination.vue";
import { router } from "@inertiajs/vue3";
import Multiselect from "@vueform/multiselect";
import Cancel from "./Modals/Cancel.vue";
export default {
    components: { PageHeader, Pagination, Multiselect, Cancel },
    props: ["dropdowns", "roles"],
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
        };
    },
    watch: {
        "filter.keyword"(newVal) {
            this.checkSearchStr(newVal);
        },
        "filter.status"(newVal) {
            this.fetch();
        },
        "filter.mode"(newVal) {
            this.fetch();
        },
        "filter.expense"(newVal) {
            this.fetch();
        },
    },
    created() {
        this.fetch();
    },
    methods: {
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

        view(view_type) {
            this.view_type = view_type;
        },

        goCreatePage() {
            router.get("/faims/procurements/create", { option: "create" });
        },

        goViewPage(data) {
            router.get("/faims/procurements/" + data.id, {
                option: "view",
                tab: 1,
            });
        },

        goEditPage(data) {
            router.get("/faims/procurements/" + data.id, { option: "edit" });
        },

        goReviewPage(data) {
            router.get("/faims/procurements/" + data.id, { option: "review" });
        },

        goApprovePage(data) {
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
            this.$refs.cancel.show(data);
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
            this.fetch();
        },
    },
};
</script>
