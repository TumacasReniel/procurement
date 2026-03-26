<template>
    <Head title="Finance Requests" />
    <PageHeader title="Finance Requests" pageTitle="List" />
    <BRow>
        <div class="col-md-12">
            <div class="card bg-light-subtle shadow-none border">
                <div class="card-header bg-light-subtle">
                    <div class="d-flex mb-n3">
                        <div class="flex-shrink-0 me-3">
                            <div style="height: 2.5rem; width: 2.5rem">
                                <span class="avatar-title bg-success-subtle rounded p-2 mt-n1">
                                    <i class="ri-wallet-3-line text-success fs-24"></i>
                                </span>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="mb-0 fs-14">
                                <span class="text-body">Finance Requests</span>
                            </h5>
                            <p class="text-muted text-truncate-two-lines fs-12">
                                A detailed list of submitted finance requests including requester, amount, and status.
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
                                    <i class="ri-search-line search-icon"></i>
                                </span>
                                <input
                                    type="text"
                                    v-model="filter.keyword"
                                    placeholder="Search Finance Request"
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

                                <b-button type="button" variant="primary" @click="openCreate">
                                    <i class="ri-add-circle-fill align-bottom me-1"></i>
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
                            style="margin-top: -39px; height: calc(100vh - 350px); overflow: auto;"
                        >
                            <table class="table align-middle table-hover mb-0">
                                <thead class="table-light thead-fixed">
                                    <tr class="fs-12 fw-semibold">
                                        <th style="width: 4%" class="text-center">#</th>
                                        <th style="width: 12%">Request Number</th>
                                        <th style="width: 16%">Payee</th>
                                        <th style="width: 10%">Amount</th>
                                        <th style="width: 12%">Division</th>
                                        <th style="width: 14%">Obligation Type</th>
                                        <th style="width: 12%">Status</th>
                                        <th style="width: 12%">Created By</th>
                                        <th style="width: 10%" class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="table-group-divider">
                                    <tr
                                        v-for="(list, index) in lists"
                                        :key="index"
                                        @click="selectRow(index)"
                                        :class="{
                                            'bg-info-subtle': index === selectedRow,
                                        }"
                                        class="cursor-pointer"
                                    >
                                        <td class="text-center fw-semibold">{{ index + 1 }}</td>
                                        <td class="fw-semibold text-primary">{{ list.code || '-' }}</td>
                                        <td>{{ list.payee || list.requested_by || '-' }}</td>
                                        <td>{{ list.amount || '-' }}</td>
                                        <td>{{ list.division || '-' }}</td>
                                        <td>{{ list.obligation_type || '-' }}</td>
                                        <td>
                                            <b-badge :class="list.status?.bg || 'bg-soft-secondary text-secondary'" class="fs-11">
                                                {{ list.status?.name || 'N/A' }}
                                            </b-badge>
                                        </td>
                                        <td>{{ list.created_by || '-' }}</td>
                                        <td class="text-center">
                                            <div class="d-flex justify-content-center gap-1">
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
                                                <b-button size="sm" variant="danger" class="btn-icon" v-b-tooltip.hover title="Cancel" style="border-radius: 8px">
                                                    <i class="ri-close-line"></i>
                                                </b-button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="lists.length === 0">
                                        <td colspan="9" class="p-0">
                                            <div class="empty-state">
                                                <i class="ri-file-warning-line empty-state-icon"></i>
                                                <div class="empty-state-text">No finance requests found.</div>
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
    <Create ref="create" :dropdowns="dropdowns" @created="handleCreate" />
</template>

<script>
import _ from "lodash";
import PageHeader from "@/Shared/Components/PageHeader.vue";
import Pagination from "@/Shared/Components/Pagination.vue";
import Create from "@/Pages/Modules/FAIMS/Finance/Modals/Create.vue";
import Multiselect from "@vueform/multiselect";
import { router } from "@inertiajs/vue3";

export default {
    components: { PageHeader, Pagination, Create, Multiselect },
    props: ["dropdowns", "roles"],
    data() {
        return {
            lists: [],
            meta: {},
            links: {},
            filter: {
                keyword: null,
                status: null,
            },
            requestTypes: [],
            selectedRow: null,
        };
    },

    watch: {
        "filter.keyword"(newVal) {
            this.checkSearchStr(newVal);
        },
        "filter.status"() {
            this.fetch();
        },
    },
    created() {
        this.fetch();
    },
    methods: {
        checkSearchStr: _.debounce(function () {
            this.fetch();
        }, 300),
        fetch(page_url) {
            page_url = page_url || "/faims/finance-requests";
            axios
                .get(page_url, {
                    params: {
                        keyword: this.filter.keyword,
                        status: this.filter.status?.value ?? this.filter.status,
                        count: 10,
                        option: "lists",
                    },
                })
                .then((response) => {
                    if (response) {
                        this.lists = response.data.data || [];
                        this.meta = response.data.meta;
                        this.links = response.data.links;
                    }
                })
                .catch((err) => console.log(err));
        },
        goCreatePage() {
            this.openCreate();
        },
        openCreate() {
            this.$refs.create.show();
        },

        handleCreate(form) {
            axios.post('/faims/finance-requests', form)
                .then(() => {
                    this.fetch();
                    if (this.$refs.create?.handleSuccess) {
                        this.$refs.create.handleSuccess();
                    }
                    this.$refs.create.hide();
                })
                .catch((err) => {
                    const errors = err?.response?.data?.errors || {};
                    if (this.$refs.create?.setErrors) {
                        this.$refs.create.setErrors(errors);
                    }
                    console.error(err);
                });
        },

        goViewPage(data) {
            if (!data?.id) return;
            router.get("/faims/finance-requests/" + data.id);
        },
        refresh() {
            this.filter.keyword = null;
            this.fetch();
        },

        selectRow(index) {
            if (this.selectedRow === index) {
                this.selectedRow = null;
            } else {
                this.selectedRow = index;
            }
        },

    },
};
</script>
<style scoped>
:global(body) {
  background: #f6f7fb;
}

.search-icon {
  color: #94a3b8;
}

.pagination-section {
  margin-top: 16px;
  display: flex;
  justify-content: flex-end;
}

.empty-state {
  min-height: calc(100vh - 420px);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 8px;
  color: #94a3b8;
  background: #fff;
}

.empty-state-icon {
  font-size: 40px;
  color: #94a3b8;
}

.empty-state-text {
  font-size: 14px;
}
</style>








