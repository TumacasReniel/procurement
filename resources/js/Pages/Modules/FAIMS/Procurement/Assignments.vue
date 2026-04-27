<template>
    <Head title="Assignment Page" />
    <PageHeader title="Assignment Management" pageTitle="Procurement" />

    <BRow class="procurement-index-page assignment-management-page">
        <BCol lg="12">
                <BCard no-body class="bg-light-subtle shadow-none border">
                <BCardBody class="bg-white">
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
        class="v-modal-custom assignment-management-modal"
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
                <div v-if="existingAssigned.length" class="alert alert-light border mb-2 assignment-summary">
                    <small class="text-muted d-block mb-1">Currently assigned</small>
                    <div class="d-flex flex-column gap-2">
                        <span
                            v-for="person in existingAssigned"
                            :key="person.user_id || person.name"
                            class="badge rounded-pill bg-success-subtle text-success align-self-start assignment-existing-chip"
                            :class="{ 'text-decoration-line-through opacity-75': isPendingRemovalPerson(person) }"
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
                <div v-if="assignOptions.length" class="dropdown-menu show w-100 assignment-search-dropdown">
                    <button
                        v-for="option in assignOptions"
                        :key="option.value ?? option.id"
                        type="button"
                        class="dropdown-item assignment-search-option"
                        @click="selectAssignee(option)"
                    >
                        {{ option.name }}
                    </button>
                </div>
                <small class="text-danger" v-if="assignErrors.user_ids">{{ assignErrors.user_ids }}</small>
            </div>
            <div v-if="assignSelected.length" class="alert alert-light border mb-0 assignment-summary">
                <small class="text-muted d-block mb-2">Selected to add</small>
                <div class="d-flex flex-column gap-2">
                    <span
                        v-for="person in assignSelected"
                        :key="person.value ?? person.id"
                        class="badge rounded-pill bg-primary-subtle text-primary align-self-start assignment-selected-chip"
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
                { step: "Re-award", match: ["Re-award"] },
                { step: "Rebid", match: ["Rebid"] },
                { step: "For NOA", match: ["For NOA"] },
                { step: "NOA Served", match: ["NOA Served to Supplier", "NOA Served"] },
                { step: "NOA Confirmed", match: ["NOA Conformed", "NOA Confirmed"] },
                { step: "PO Created", match: ["PO Created"] },
                { step: "PO Issued", match: ["PO Issued"] },
                { step: "PO Conformed", match: ["PO Conformed"] },
                { step: "Items Delivered", match: ["PO Items Delivered", "Items Delivered", "PO Delivered/For Inspection", "Delivered/For Inspection", "Delivered"] },
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
                { step: "For Approval of Failure BAC Resolution", match: ["For Approval of Failure BAC Resolution", "For Failure"] },
                { step: "Re-award", match: ["Re-award"] },
                { step: "Rebid", match: ["Rebid"] },
                { step: "For NOA", match: ["For NOA"] },
                { step: "NOA Served", match: ["NOA Served to Supplier", "NOA Served"] },
                { step: "NOA Confirmed", match: ["NOA Conformed", "NOA Confirmed"] },
                { step: "PO Created", match: ["PO Created"] },
                { step: "PO Issued", match: ["PO Issued"] },
                { step: "PO Conformed", match: ["PO Conformed"] },
                { step: "Items Delivered", match: ["PO Items Delivered", "Items Delivered", "PO Delivered/For Inspection", "Delivered/For Inspection", "Delivered"] },
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
        assigneesFor(stepNames) {
            const names = this.assignments
                .filter((item) =>
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
            if (!this.assignRow) {
                this.existingAssigned = [];
                this.pendingRemovalAssignmentIds = [];
                return;
            }

            const relevant = this.assignments.filter(
                (item) =>
                    this.statusMatches(item.status, this.assignRow.match || [this.assignRow.step]),
            );

            const groupedByUser = {};
            relevant.forEach((item) => {
                const key = Number(item.user_id);
                if (!groupedByUser[key]) {
                    groupedByUser[key] = {
                        user_id: key,
                        name: item.name,
                        assignment_ids: [],
                    };
                }
                groupedByUser[key].assignment_ids.push(item.id);
            });

            this.existingAssigned = Object.values(groupedByUser);
            this.pendingRemovalAssignmentIds = [];
        },
        openAssignModal(row) {
            this.assignRow = row;
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
        isPendingRemovalPerson(person) {
            const ids = person?.assignment_ids || [];
            return ids.length > 0 && ids.every((id) => this.pendingRemovalAssignmentIds.includes(id));
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
                const ids = existing.assignment_ids || [];
                if (ids.length && ids.every((assignmentId) => this.pendingRemovalAssignmentIds.includes(assignmentId))) {
                    this.pendingRemovalAssignmentIds = this.pendingRemovalAssignmentIds.filter(
                        (x) => !ids.includes(x),
                    );
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
            const assignmentIds = assignment?.assignment_ids || [];
            if (!assignmentIds.length) return;

            const allMarked = assignmentIds.every((id) => this.pendingRemovalAssignmentIds.includes(id));
            if (allMarked) {
                this.pendingRemovalAssignmentIds = this.pendingRemovalAssignmentIds.filter(
                    (id) => !assignmentIds.includes(id),
                );
                return;
            }
            assignmentIds.forEach((id) => {
                if (!this.pendingRemovalAssignmentIds.includes(id)) {
                    this.pendingRemovalAssignmentIds.push(id);
                }
            });
        },
        submitAssign() {
            this.assignErrors = {};
            if (!this.assignSelected.length && !this.pendingRemovalAssignmentIds.length) {
                this.assignErrors.user_ids = "No changes to save.";
                return;
            }
            this.assignProcessing = true;
            const removeRequests = this.pendingRemovalAssignmentIds.map((id) =>
                axios.delete(`/faims/procurement-assignments/${id}`),
            );
            const addRequests = this.assignSelected.length
                ? [
                      axios.post("/faims/procurement-assignments", {
                          status: this.assignForm.status,
                          user_ids: this.assignForm.user_ids,
                      }),
                  ]
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
.assignment-management-page {
    --assignment-surface: #ffffff;
    --assignment-surface-soft: #f1f4f9;
    --assignment-surface-soft-alt: #eef2fb;
    --assignment-border: #d9dfeb;
    --assignment-border-soft: #e3e8f2;
    --assignment-text: #111827;
    --assignment-muted: #6b7280;
    --assignment-chip-bg: #e9effa;
    --assignment-chip-border: #acbad6;
    --assignment-chip-text: #344f83;
    --assignment-tab-text: #3d4f8f;
    --assignment-tab-active-bg: #3d4f8f;
    --assignment-tab-active-text: #ffffff;
    --assignment-button-bg: #3d4f8f;
    --assignment-button-hover-bg: #33437b;
    --assignment-button-text: #ffffff;
    --assignment-row-hover: rgba(61, 79, 143, 0.05);
    --assignment-dropdown-bg: #ffffff;
    --assignment-dropdown-hover-bg: #f8fafc;
}

[data-bs-theme="dark"] .assignment-management-page {
    --assignment-surface: #1f2630;
    --assignment-surface-soft: #232b36;
    --assignment-surface-soft-alt: #1d2430;
    --assignment-border: #364152;
    --assignment-border-soft: #313b48;
    --assignment-text: #e5edf7;
    --assignment-muted: #9fb0c7;
    --assignment-chip-bg: rgba(96, 165, 250, 0.16);
    --assignment-chip-border: rgba(148, 163, 184, 0.28);
    --assignment-chip-text: #dbeafe;
    --assignment-tab-text: #c7d2fe;
    --assignment-tab-active-bg: #334155;
    --assignment-tab-active-text: #e5edf7;
    --assignment-button-bg: #2563eb;
    --assignment-button-hover-bg: #1d4ed8;
    --assignment-button-text: #eff6ff;
    --assignment-row-hover: rgba(59, 130, 246, 0.12);
    --assignment-dropdown-bg: #1f2630;
    --assignment-dropdown-hover-bg: #27303d;
}

.assignment-table-wrap {
    background: var(--assignment-surface);
    border: 1px solid var(--assignment-border);
    border-radius: 12px;
}

.assignment-table thead th {
    background: var(--assignment-surface-soft);
    color: var(--assignment-text);
    font-weight: 700;
    border-bottom: 1px solid var(--assignment-border);
    padding: 14px 12px;
}

.assignment-table tbody td {
    padding: 14px 12px;
    border-color: var(--assignment-border-soft);
    color: var(--assignment-text);
    background: transparent;
}

.assignment-table tbody tr:hover td {
    background: var(--assignment-row-hover);
}

.step-name {
    font-weight: 700;
    color: var(--assignment-text);
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
    color: var(--assignment-muted);
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
    border: 1px solid var(--assignment-chip-border);
    background: var(--assignment-chip-bg);
    color: var(--assignment-chip-text);
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
    background: var(--assignment-button-bg);
    color: var(--assignment-button-text);
    border: 0;
    border-radius: 6px;
    font-weight: 600;
}

.assign-btn:hover {
    background: var(--assignment-button-hover-bg);
    color: var(--assignment-button-text);
}

.scope-tabs {
    display: inline-flex;
    background: var(--assignment-surface-soft-alt);
    border: 1px solid var(--assignment-border);
    border-radius: 10px;
    padding: 4px;
    gap: 4px;
}

.scope-tab-btn {
    border: 0;
    background: transparent;
    color: var(--assignment-tab-text);
    font-weight: 700;
    font-size: 13px;
    border-radius: 8px;
    padding: 6px 14px;
}

.scope-tab-btn.active {
    background: var(--assignment-tab-active-bg);
    color: var(--assignment-tab-active-text);
}

.flow-section {
    border: 1px solid var(--assignment-border);
    border-radius: 10px;
    padding: 12px;
    background: var(--assignment-surface-soft);
}

.flow-title {
    font-weight: 700;
    color: var(--assignment-text);
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
    background: var(--assignment-surface-soft);
    color: var(--assignment-muted);
    text-align: center;
    border: 1px solid transparent;
}

.flow-item.flow-past {
    background: var(--assignment-chip-bg);
    color: var(--assignment-tab-text);
}

.flow-item.flow-current {
    background: var(--assignment-tab-active-bg);
    color: var(--assignment-tab-active-text);
    border-color: var(--assignment-border);
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

.assignment-summary {
    background: var(--assignment-surface-soft) !important;
    border-color: var(--assignment-border) !important;
}

.assignment-summary .text-muted {
    color: var(--assignment-muted) !important;
}

.assignment-existing-chip,
.assignment-selected-chip {
    border: 1px solid var(--assignment-chip-border);
}

.assignment-search-dropdown {
    background: var(--assignment-dropdown-bg);
    border-color: var(--assignment-border);
}

.assignment-search-option {
    color: var(--assignment-text);
}

.assignment-search-option:hover,
.assignment-search-option:focus {
    background: var(--assignment-dropdown-hover-bg);
    color: var(--assignment-text);
}
</style>
