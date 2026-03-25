<template>
    <Head title="Assignment Page" />
    <PageHeader title="Assignment Management" pageTitle="Procurement" />

    <BRow>
        <BCol lg="12">
                <BCard no-body>
                <BCardBody>
                    <div class="scope-tabs mb-3">
                        <button
                            type="button"
                            class="scope-tab-btn"
                            :class="{ active: activeScope === 'Main' }"
                            @click="activeScope = 'Main'"
                        >
                            Main
                        </button>
                        <button
                            type="button"
                            class="scope-tab-btn"
                            :class="{ active: activeScope === 'Sub' }"
                            @click="activeScope = 'Sub'"
                        >
                            Sub
                        </button>
                    </div>

                    <div class="table-responsive assignment-table-wrap">
                        <table class="table align-middle mb-0 assignment-table">
                            <thead>
                                <tr>
                                    <th style="width: 28%">Step</th>
                                    <th style="width: 52%">Assigned Personnel</th>
                                    <th v-if="canManageProcurementWorkflow" style="width: 20%">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="loading">
                                    <td :colspan="canManageProcurementWorkflow ? 3 : 2" class="text-center text-muted py-4">
                                        Loading assignments...
                                    </td>
                                </tr>
                                <tr v-else-if="visibleRows.length === 0">
                                    <td :colspan="canManageProcurementWorkflow ? 3 : 2" class="text-center text-muted py-4">
                                        No assignments found.
                                    </td>
                                </tr>
                                <tr
                                    v-else
                                    v-for="(row, index) in visibleRows"
                                    :key="`${row.scope}-${row.step}-${index}`"
                                >
                                    <td class="step-name">
                                        {{ row.step }}
                                    </td>
                                    <td>
                                        <div v-if="row.assignees.length" class="assignees-wrap">
                                            <span
                                                v-for="name in row.assignees"
                                                :key="name"
                                                class="assignee-chip"
                                            >
                                                {{ name }}
                                            </span>
                                        </div>
                                        <span v-else class="text-muted">-</span>
                                    </td>
                                    <td v-if="canManageProcurementWorkflow">
                                        <button
                                            type="button"
                                            class="btn assign-btn btn-sm"
                                            @click="openAssignModal(row)"
                                        >
                                            <i class="ri-user-add-line me-1"></i>Assign
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </BCardBody>
            </BCard>
        </BCol>
    </BRow>

    <b-modal
        v-model="showAssignModal"
        style="--vz-modal-width: 600px"
        title="Assign Personnel"
        header-class="p-3 bg-light"
        class="v-modal-custom"
        modal-class="zoomIn"
        centered
        no-close-on-backdrop
    >
        <form class="customform" @submit.prevent="submitAssign">
            <div class="mb-2">
                <label class="form-label">Status</label>
                <input type="text" class="form-control" :value="assignRow?.step || assignForm.status" readonly />
            </div>
            <div class="mb-2">
                <label class="form-label">Employee</label>
                <div v-if="existingAssigned.length" class="alert alert-light border mb-2">
                    <small class="text-muted d-block mb-1">Currently assigned</small>
                    <div class="d-flex flex-wrap gap-2">
                        <span
                            v-for="person in existingAssigned"
                            :key="person.id"
                            class="badge rounded-pill bg-success-subtle text-success"
                            :class="{ 'text-decoration-line-through opacity-75': pendingRemovalAssignmentIds.includes(person.id) }"
                        >
                            {{ person.name }}
                            <i
                                class="ri-close-line ms-1 cursor-pointer"
                                :class="{ 'opacity-50': assignProcessing }"
                                @click="removeExistingAssignee(person)"
                            ></i>
                        </span>
                    </div>
                </div>
                <input
                    ref="assignSearchInput"
                    type="text"
                    class="form-control"
                    v-model="assignSearch"
                    placeholder="Type at least 2 characters"
                    @input="handleAssignSearch"
                />
                <div v-if="assignOptions.length" class="dropdown-menu show w-100">
                    <button
                        v-for="option in assignOptions"
                        :key="option.value ?? option.id"
                        type="button"
                        class="dropdown-item"
                        @click="selectAssignee(option)"
                    >
                        {{ option.name }}
                    </button>
                </div>
                <small class="text-danger" v-if="assignErrors.user_ids">{{ assignErrors.user_ids }}</small>
            </div>
            <div v-if="assignSelected.length" class="alert alert-light border mb-0">
                <small class="text-muted d-block mb-2">Selected to add</small>
                <div class="d-flex flex-wrap gap-2">
                    <span
                        v-for="person in assignSelected"
                        :key="person.value ?? person.id"
                        class="badge rounded-pill bg-primary-subtle text-primary"
                    >
                        {{ person.name }}
                        <i class="ri-close-line ms-1 cursor-pointer" @click="removeAssignee(person)"></i>
                    </span>
                </div>
            </div>
        </form>
        <template #footer>
            <div class="d-flex justify-content-end gap-2 w-100">
                <button type="button" class="btn btn-light" @click="showAssignModal = false">Cancel</button>
                <button
                    type="button"
                    class="btn btn-primary"
                    :disabled="assignProcessing || (!assignSelected.length && !pendingRemovalAssignmentIds.length)"
                    @click="submitAssign"
                >
                    {{ assignProcessing ? "Saving..." : "Assign" }}
                </button>
            </div>
        </template>
    </b-modal>
</template>

<script>
import axios from "axios";
import PageHeader from "@/Shared/Components/PageHeader.vue";

export default {
    props: {
        statuses: {
            type: Array,
            default: () => [],
        },
    },
    components: {
        PageHeader,
    },
    data() {
        return {
            loading: false,
            loadingProcurements: false,
            assignments: [],
            procurements: [],
            activeScope: "Main",
            showAssignModal: false,
            assignRow: null,
            assignForm: {
                procurement_ids: [],
                status: "",
                user_ids: [],
            },
            assignSearch: "",
            assignOptions: [],
            assignSelected: [],
            existingAssigned: [],
            assignErrors: {},
            assignProcessing: false,
            pendingRemovalAssignmentIds: [],
            assignSearchTimer: null,
        };
    },
    computed: {
        canManageProcurementWorkflow() {
            const roles = this.$page.props.roles || [];
            return (
                roles.includes("Procurement Staff") ||
                roles.includes("Procurement Officer") ||
                roles.includes("Administrator")
            );
        },
        mainWorkflow() {
            const mainSteps = [
                { step: "Pending", match: ["Pending"] },
                { step: "Reviewed", match: ["Reviewed"] },
                { step: "Approved", match: ["Approved"] },
                { step: "For RFQ", match: ["For Quotations", "For RFQ"] },
                { step: "For Bids", match: ["For Bids"] },
                { step: "For BAC", match: ["For BAC Resolution", "For BAC"] },
                { step: "For Approval", match: ["For Approval of BAC Resolution", "For Approval"] },
                { step: "For NOA", match: ["For NOA"] },
                { step: "NOA Served", match: ["NOA Served to Supplier", "NOA Served"] },
                { step: "NOA Confirmed", match: ["NOA Conformed", "NOA Confirmed"] },
                { step: "PO Created", match: ["PO Created"] },
                { step: "PO Issued", match: ["PO Issued"] },
                { step: "PO Conformed", match: ["PO Conformed"] },
                { step: "Delivered", match: ["PO Delivered/For Inspection", "Delivered/For Inspection", "Delivered"] },
                { step: "Completed", match: ["Completed"] },
            ];

            return mainSteps.map((step, stepIndex) => {
                const activeIds = [];
                let doneCount = 0;

                this.procurements.forEach((proc) => {
                    const currentStatus = proc?.status?.name || "";
                    const currentIndex = mainSteps.findIndex((s) => this.statusMatches(currentStatus, s.match));
                    if (currentIndex === stepIndex) {
                        activeIds.push(Number(proc.id));
                    } else if (currentIndex > stepIndex) {
                        doneCount += 1;
                    }
                });

                return {
                    ...step,
                    procurementIds: [...new Set(activeIds)],
                    activeCount: activeIds.length,
                    doneCount,
                };
            });
        },
        subWorkflow() {
            const subSteps = [
                { step: "For RFQ", match: ["For Quotations", "For RFQ"] },
                { step: "For Bids", match: ["For Bids"] },
                { step: "For BAC", match: ["For BAC Resolution", "For BAC"] },
                { step: "For Approval", match: ["For Approval of BAC Resolution", "For Approval"] },
                { step: "For Failure", match: ["For Approval of Failure BAC Resolution", "For Failure"] },
                { step: "For NOA", match: ["For NOA"] },
                { step: "NOA Served", match: ["NOA Served to Supplier", "NOA Served"] },
                { step: "NOA Confirmed", match: ["NOA Conformed", "NOA Confirmed"] },
                { step: "PO Created", match: ["PO Created"] },
                { step: "PO Issued", match: ["PO Issued"] },
                { step: "PO Conformed", match: ["PO Conformed"] },
                { step: "Delivered", match: ["PO Delivered/For Inspection", "Delivered/For Inspection", "Delivered"] },
                { step: "Completed", match: ["Completed"] },
            ];

            return subSteps.map((step, stepIndex) => {
                const activeIds = [];
                let doneCount = 0;

                this.procurements.forEach((proc) => {
                    const currentSubStatus = proc?.sub_status?.name || "";
                    const currentIndex = subSteps.findIndex((s) => this.statusMatches(currentSubStatus, s.match));
                    if (currentIndex === stepIndex) {
                        activeIds.push(Number(proc.id));
                    } else if (currentIndex > stepIndex) {
                        doneCount += 1;
                    }
                });

                return {
                    ...step,
                    procurementIds: [...new Set(activeIds)],
                    activeCount: activeIds.length,
                    doneCount,
                };
            });
        },
        combinedRows() {
            const pickCurrentIndex = (workflow) => {
                let bestIndex = -1;
                let bestCount = 0;
                workflow.forEach((step, index) => {
                    if (step.activeCount > bestCount) {
                        bestCount = step.activeCount;
                        bestIndex = index;
                    }
                });
                return bestCount > 0 ? bestIndex : -1;
            };

            const hasRebidOrReaward = this.procurements.some((proc) => {
                const name = (proc?.status?.name || "").trim();
                return name === "Rebid" || name === "Re-award";
            });

            const mainCurrentIndex = pickCurrentIndex(this.mainWorkflow);

            const mainRows = this.mainWorkflow.map((step) => ({
                scope: "Main",
                step: step.step,
                match: step.match,
                procurementIds: step.procurementIds,
                activeCount: step.activeCount,
                assignees: this.assigneesFor(step.match, step.procurementIds),
                isCurrent: hasRebidOrReaward
                    ? step.activeCount > 0
                    : this.mainWorkflow.indexOf(step) === mainCurrentIndex,
                isPast:
                    (hasRebidOrReaward
                        ? step.activeCount === 0
                        : this.mainWorkflow.indexOf(step) !== mainCurrentIndex) &&
                    step.doneCount > 0,
            }));
            const subRows = this.subWorkflow.map((step) => ({
                scope: "Sub",
                step: step.step,
                match: step.match,
                procurementIds: step.procurementIds,
                activeCount: step.activeCount,
                assignees: this.assigneesFor(step.match, step.procurementIds),
                isCurrent: hasRebidOrReaward
                    ? step.activeCount > 0
                    : false,
                isPast:
                    (hasRebidOrReaward
                        ? step.activeCount === 0
                        : true) &&
                    step.doneCount > 0,
            }));
            return [...mainRows, ...subRows];
        },
        visibleRows() {
            return this.combinedRows.filter((row) => row.scope === this.activeScope);
        },
    },
    created() {
        this.fetchProcurements();
        this.fetchAssignments();
    },
    methods: {
        assigneesFor(stepNames, procurementIds = []) {
            const names = this.assignments
                .filter((item) =>
                    procurementIds.includes(Number(item.procurement_id)) &&
                    this.statusMatches(item.status, stepNames)
                )
                .map((item) => item.name)
                .filter(Boolean);
            return [...new Set(names)];
        },
        statusMatches(status, stepNames = []) {
            const value = (status || "").trim();
            return stepNames.some((name) => (name || "").trim() === value);
        },
        fetchProcurements() {
            this.loadingProcurements = true;
            axios
                .get("/faims/procurements", {
                    params: {
                        option: "lists",
                        count: 200,
                    },
                })
                .then((response) => {
                    this.procurements = response.data?.data || [];
                })
                .catch(() => {
                    this.procurements = [];
                })
                .finally(() => {
                    this.loadingProcurements = false;
                });
        },
        fetchAssignments() {
            this.loading = true;
            axios
                .get("/faims/procurement-assignments", {
                    params: {
                        json: 1,
                    },
                })
                .then((response) => {
                    this.assignments = response.data?.data || [];
                })
                .catch(() => {
                    this.assignments = [];
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        syncExistingAssigned() {
            if (!this.assignRow || !this.assignForm.procurement_ids.length) {
                this.existingAssigned = [];
                this.pendingRemovalAssignmentIds = [];
                return;
            }
            this.existingAssigned = this.assignments.filter(
                (item) =>
                    this.assignForm.procurement_ids.includes(Number(item.procurement_id)) &&
                    this.statusMatches(item.status, this.assignRow.match || [this.assignRow.step]),
            );
            this.pendingRemovalAssignmentIds = [];
        },
        openAssignModal(row) {
            this.assignRow = row;
            this.assignForm.procurement_ids = row.procurementIds || [];
            this.assignForm.status = row.match?.[0] || row.step;
            this.assignForm.user_ids = [];
            this.assignSearch = "";
            this.assignOptions = [];
            this.assignSelected = [];
            this.syncExistingAssigned();
            this.assignErrors = {};
            this.showAssignModal = true;
            this.$nextTick(() => {
                if (this.$refs.assignSearchInput) {
                    this.$refs.assignSearchInput.focus();
                }
            });
        },
        handleAssignSearch() {
            if (this.assignSearchTimer) {
                clearTimeout(this.assignSearchTimer);
            }
            const term = (this.assignSearch || "").trim();
            if (term.length < 2) {
                this.assignOptions = [];
                return;
            }
            this.assignSearchTimer = setTimeout(() => {
                this.searchEmployees(term);
            }, 300);
        },
        searchEmployees(term) {
            axios
                .get("/search", {
                    params: {
                        keyword: term,
                        option: "users",
                    },
                })
                .then((response) => {
                    this.assignOptions = Array.isArray(response.data) ? response.data : [];
                })
                .catch(() => {
                    this.assignOptions = [];
                });
        },
        selectAssignee(option) {
            const id = option?.value ?? option?.id ?? null;
            if (!id) return;
            const existing = this.existingAssigned.find((p) => Number(p.user_id) === Number(id));
            if (existing) {
                if (this.pendingRemovalAssignmentIds.includes(existing.id)) {
                    this.pendingRemovalAssignmentIds = this.pendingRemovalAssignmentIds.filter((x) => x !== existing.id);
                }
                this.assignOptions = [];
                this.assignSearch = "";
                return;
            }
            const exists = this.assignSelected.some((p) => (p.value ?? p.id) === id);
            if (!exists) {
                this.assignSelected.push(option);
            }
            this.assignForm.user_ids = this.assignSelected
                .map((p) => p.value ?? p.id)
                .filter(Boolean);
            this.assignSearch = option?.name || "";
            this.assignOptions = [];
            this.assignErrors.user_ids = null;
        },
        removeAssignee(person) {
            const id = person?.value ?? person?.id;
            this.assignSelected = this.assignSelected.filter((p) => (p.value ?? p.id) !== id);
            this.assignForm.user_ids = this.assignSelected
                .map((p) => p.value ?? p.id)
                .filter(Boolean);
        },
        removeExistingAssignee(assignment) {
            const assignmentId = assignment?.id;
            if (!assignmentId) return;
            if (this.pendingRemovalAssignmentIds.includes(assignmentId)) {
                this.pendingRemovalAssignmentIds = this.pendingRemovalAssignmentIds.filter((id) => id !== assignmentId);
                return;
            }
            this.pendingRemovalAssignmentIds.push(assignmentId);
        },
        submitAssign() {
            this.assignErrors = {};
            if (!this.assignForm.procurement_ids.length) {
                this.assignErrors.user_ids = "No procurements found for this step.";
                return;
            }
            if (!this.assignSelected.length && !this.pendingRemovalAssignmentIds.length) {
                this.assignErrors.user_ids = "No changes to save.";
                return;
            }
            this.assignProcessing = true;
            const removeRequests = this.pendingRemovalAssignmentIds.map((id) =>
                axios.delete(`/faims/procurement-assignments/${id}`),
            );
            const addRequests = this.assignSelected.length
                ? this.assignForm.procurement_ids.map((procurementId) =>
                      axios.post("/faims/procurement-assignments", {
                          procurement_id: procurementId,
                          status: this.assignForm.status,
                          user_ids: this.assignForm.user_ids,
                      }),
                  )
                : [];

            Promise.all([...removeRequests, ...addRequests])
                .then(() => {
                    this.showAssignModal = false;
                    this.fetchAssignments();
                })
                .catch((error) => {
                    if (error?.response?.status === 422) {
                        this.assignErrors = error.response.data.errors || {};
                    }
                })
                .finally(() => {
                    this.assignProcessing = false;
                });
        },
    },
};
</script>

<style scoped>
.assignment-table thead th {
    background: #f1f4f9;
    color: #0b1020;
    font-weight: 700;
    border-bottom: 1px solid #d9dfeb;
    padding: 14px 12px;
}

.assignment-table tbody td {
    padding: 14px 12px;
    border-color: #e3e8f2;
}

.step-name {
    font-weight: 700;
    color: #111827;
}

.indicator-wrap {
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.indicator-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    display: inline-block;
}

.dot-current {
    background: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
}

.dot-waiting {
    background: #6b7280;
    box-shadow: 0 0 0 3px rgba(107, 114, 128, 0.18);
}

.dot-done {
    background: #16a34a;
    box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.2);
}

.indicator-text {
    color: #6b7280;
    font-weight: 500;
}

.assignees-wrap {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.assignee-chip {
    padding: 3px 10px;
    font-size: 12px;
    font-weight: 600;
    border-radius: 6px;
    border: 1px solid #acbad6;
    background: #e9effa;
    color: #344f83;
}

.state-current,
.state-pending,
.state-done {
    display: inline-block;
    min-width: 72px;
    text-align: center;
    color: #fff;
    border-radius: 6px;
    padding: 3px 10px;
    font-size: 12px;
    font-weight: 700;
}

.state-current {
    background: #3d4f8f;
}

.state-pending {
    background: #3b82f6;
}

.state-done {
    background: #16a34a;
}

.assign-btn {
    background: #3d4f8f;
    color: #fff;
    border: 0;
    border-radius: 6px;
    font-weight: 600;
}

.assign-btn:hover {
    background: #33437b;
    color: #fff;
}

.scope-tabs {
    display: inline-flex;
    background: #eef2fb;
    border: 1px solid #d6dff0;
    border-radius: 10px;
    padding: 4px;
    gap: 4px;
}

.scope-tab-btn {
    border: 0;
    background: transparent;
    color: #3d4f8f;
    font-weight: 700;
    font-size: 13px;
    border-radius: 8px;
    padding: 6px 14px;
}

.scope-tab-btn.active {
    background: #3d4f8f;
    color: #fff;
}

.flow-section {
    border: 1px solid #dbe4f2;
    border-radius: 10px;
    padding: 12px;
    background: #f6f8fc;
}

.flow-title {
    font-weight: 700;
    color: #213152;
}

.flow-strip {
    display: flex;
    gap: 10px;
    overflow-x: auto;
    padding-bottom: 6px;
}

.flow-item {
    min-width: 105px;
    padding: 10px 8px;
    border-radius: 10px;
    background: #d9dfef;
    color: #66739a;
    text-align: center;
    border: 1px solid transparent;
}

.flow-item.flow-past {
    background: #c7d5f3;
    color: #3d4f8f;
}

.flow-item.flow-current {
    background: #3d4f8f;
    color: #fff;
    border-color: #2b3a6f;
}

.flow-item .flow-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    margin: 0 auto 7px;
    background: currentColor;
    opacity: 0.85;
}

.flow-item .flow-label {
    font-size: 12px;
    font-weight: 700;
    white-space: nowrap;
}
</style>
