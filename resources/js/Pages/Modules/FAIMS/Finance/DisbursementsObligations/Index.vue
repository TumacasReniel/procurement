<template>
    <Head title="Disbursements and Obligations" />
    <PageHeader title="Disbursements and Obligations" pageTitle="List" />
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
                                <span class="text-body">Disbursements and Obligations</span>
                            </h5>
                            <p class="text-muted text-truncate-two-lines fs-12">
                                A detailed list of finance disbursements and obligations.
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
                                    placeholder="Search Disbursements and Obligations"
                                    class="form-control"
                                    style="width: 40%"
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
                                        <th style="width: 12%">OS Number</th>
                                        <th style="width: 12%">DV Number</th>
                                        <th style="width: 14%">Request Number</th>
                                        <th style="width: 14%">Payee</th>
                                        <th style="width: 12%">Fund Source</th>
                                        <th style="width: 10%">Amount</th>
                                        <th style="width: 12%">Status</th>
                                        <th style="width: 10%">Created By</th>
                                        <th style="width: 8%" class="text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="table-group-divider">
                                    <tr v-for="(list, index) in lists" :key="index" class="cursor-pointer">
                                        <td class="text-center fw-semibold">{{ index + 1 }}</td>
                                        <td class="fw-semibold text-primary">{{ list.os_number || '-' }}</td>
                                        <td>{{ list.dv_number || '-' }}</td>
                                        <td>{{ list.request_number || '-' }}</td>
                                        <td>{{ list.payee || '-' }}</td>
                                        <td>{{ list.fund_source || '-' }}</td>
                                        <td>{{ list.amount || '-' }}</td>
                                        <td>
                                            <b-badge :class="list.status?.bg || 'bg-soft-secondary text-secondary'" class="fs-11">
                                                {{ list.status?.name || list.status || 'N/A' }}
                                            </b-badge>
                                        </td>
                                        <td>{{ list.created_by || list.created_by_id || '-' }}</td>
                                        <td class="text-center">
                                            <div class="d-flex justify-content-center gap-1">
                                                <b-button size="sm" variant="info" class="btn-icon" @click.stop="openEdit(list)" v-b-tooltip.hover title="Edit" style="border-radius: 8px">
                                                    <i class="ri-edit-2-line"></i>
                                                </b-button>
                                                <b-button size="sm" variant="danger" class="btn-icon" @click.stop="deleteItem(list)" v-b-tooltip.hover title="Delete" style="border-radius: 8px">
                                                    <i class="ri-delete-bin-line"></i>
                                                </b-button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="lists.length === 0">
                                        <td colspan="10" class="p-0">
                                            <div class="empty-state">
                                                <i class="ri-file-warning-line empty-state-icon"></i>
                                                <div class="empty-state-text">No disbursements or obligations found.</div>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            <div class="card-footer">
                                <Pagination
                                    class="ms-2 me-2 mt-n1"
                                    v-if="meta && links"
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

    <Create ref="create" @created="handleCreate" />

    <b-modal
        v-model="showEditModal"
        title="Update Record"
        header-class="p-3 bg-light"
        class="v-modal-custom"
        modal-class="zoomIn"
        centered
        no-close-on-backdrop
    >
        <form class="customform" @submit.prevent="submitEdit">
            <div v-if="Object.keys(editErrors).length" class="alert alert-danger">
                {{ Object.values(editErrors)[0]?.[0] || Object.values(editErrors)[0] }}
            </div>
            <div class="mb-3">
                <h6 class="mb-1">Reference</h6>
                <p class="text-muted fs-12 mb-0">OS/DV and request numbers</p>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">OS Number</label>
                    <input v-model="editForm.os_number" type="text" class="form-control" />
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">DV Number</label>
                    <input v-model="editForm.dv_number" type="text" class="form-control" />
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Request Number</label>
                    <input v-model="editForm.request_number" type="text" class="form-control" />
                </div>
            </div>

            <hr class="my-3" />
            <div class="mb-3">
                <h6 class="mb-1">Payee Details</h6>
                <p class="text-muted fs-12 mb-0">Recipient and fund source</p>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Payee</label>
                    <input v-model="editForm.payee" type="text" class="form-control" />
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Fund Source</label>
                    <input v-model="editForm.fund_source" type="text" class="form-control" />
                </div>
            </div>

            <hr class="my-3" />
            <div class="mb-3">
                <h6 class="mb-1">Amount & Status</h6>
                <p class="text-muted fs-12 mb-0">Amount and current status</p>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Amount</label>
                    <input v-model="editForm.amount" type="number" min="0" step="0.01" class="form-control" />
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Status</label>
                    <input v-model="editForm.status" type="text" class="form-control" />
                </div>
            </div>
        </form>
        <template v-slot:footer>
            <b-button @click="closeEdit" variant="light" block>Close</b-button>
            <b-button @click="submitEdit" variant="success" block>Update</b-button>
        </template>
    </b-modal>
</template>

<script>
import _ from "lodash";
import PageHeader from "@/Shared/Components/PageHeader.vue";
import Pagination from "@/Shared/Components/Pagination.vue";
import Create from "@/Pages/Modules/FAIMS/Finance/DisbursementsObligations/Modals/Create.vue";

export default {
    components: { PageHeader, Pagination, Create },
    data() {
        return {
            lists: [],
            meta: null,
            links: null,
            filter: {
                keyword: null,
            },
            showEditModal: false,
            editErrors: {},
            editForm: {
                id: null,
                os_number: "",
                dv_number: "",
                request_number: "",
                payee: "",
                fund_source: "",
                amount: "",
                status: "",
            },
        };
    },
    watch: {
        "filter.keyword"(newVal) {
            this.checkSearchStr(newVal);
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
            page_url = page_url || "/faims/finance-disbursements-obligations";
            axios
                .get(page_url, {
                    params: {
                        keyword: this.filter.keyword,
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
        openCreate() {
            this.$refs.create.show();
        },
        handleCreate(form) {
            axios
                .post("/faims/finance-disbursements-obligations", {
                    ...form,
                    option: "create",
                })
                .then(() => {
                    this.fetch();
                    this.$refs.create.handleSuccess();
                    this.$refs.create.hide();
                })
                .catch((err) => {
                    this.$refs.create.setErrors(err?.response?.data?.errors || {});
                    console.log(err);
                });
        },
        openEdit(item) {
            this.editErrors = {};
            this.editForm = {
                id: item.id,
                os_number: item.os_number || "",
                dv_number: item.dv_number || "",
                request_number: item.request_number || "",
                payee: item.payee || "",
                fund_source: item.fund_source || "",
                amount: item.amount || "",
                status: item.status?.name || item.status || "",
            };
            this.showEditModal = true;
        },
        closeEdit() {
            this.showEditModal = false;
        },
        submitEdit() {
            this.editErrors = {};
            axios
                .post("/faims/finance-disbursements-obligations", {
                    ...this.editForm,
                    option: "update",
                })
                .then(() => {
                    this.fetch();
                    this.closeEdit();
                })
                .catch((err) => {
                    this.editErrors = err?.response?.data?.errors || {};
                    console.log(err);
                });
        },
        deleteItem(item) {
            if (!item?.id) return;
            axios
                .post("/faims/finance-disbursements-obligations", {
                    id: item.id,
                    option: "delete",
                })
                .then(() => this.fetch())
                .catch((err) => console.log(err));
        },
        refresh() {
            this.filter.keyword = null;
            this.fetch();
        },
    },
};
</script>

<style scoped>
.search-icon {
  color: #94a3b8;
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
