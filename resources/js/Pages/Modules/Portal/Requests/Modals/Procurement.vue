<template>
    <b-modal
        v-model="showModal"
        style="--vz-modal-width: 1050px;"
        header-class="p-3 bg-light"
        title="Purchase Request"
        class="v-modal-custom procurement-request-modal"
        modal-class="zoomIn"
        centered
        no-close-on-backdrop
    >
        <form class="customform">
            <BRow class="g-3">
                <BCol lg="12" class="mt-3">
                    <div class="content-card request-details-card">
                        <div class="card-header-custom">
                            <i class="ri-file-list-3-line card-header-icon"></i>
                            <h5 class="card-header-title">Request Details</h5>
                        </div>
                        <div class="card-body-custom request-details-body">
                            <div class="row g-3 align-items-stretch">
                                <div class="col-xxl-8 col-xl-7">
                                    <div class="row g-3">
                                        <div class="col-lg-6">
                                            <div class="form-group compact-form-group">
                                                <InputLabel for="division" value="Division" :message="form.errors.division_id" />
                                                <Multiselect
                                                    :options="dropdowns.divisions"
                                                    v-model="form.division_id"
                                                    :searchable="true"
                                                    label="name"
                                                    placeholder="Select Division"
                                                    class="modern-select"
                                                />
                                            </div>
                                        </div>

                                        <div class="col-lg-6">
                                            <div class="form-group compact-form-group">
                                                <InputLabel value="PR Date" :message="form.errors.date" />
                                                <TextInput
                                                    v-model="form.date"
                                                    type="date"
                                                    class="form-control modern-input"
                                                />
                                            </div>
                                        </div>

                                        <div class="col-lg-6">
                                            <div class="form-group compact-form-group">
                                                <InputLabel for="unit" value="Unit" :message="form.errors.unit_id" />
                                                <Multiselect
                                                    :options="units"
                                                    v-model="form.unit_id"
                                                    :searchable="true"
                                                    label="name"
                                                    placeholder="Select Unit"
                                                    class="modern-select"
                                                />
                                            </div>
                                        </div>

                                        <div class="col-lg-6">
                                            <div class="form-group compact-form-group">
                                                <InputLabel for="fund_cluster" value="Fund Cluster" :message="form.errors.fund_cluster_id" />
                                                <Multiselect
                                                    :options="dropdowns.fund_clusters"
                                                    v-model="form.fund_cluster_id"
                                                    :searchable="true"
                                                    label="name"
                                                    placeholder="Select Fund Cluster"
                                                    class="modern-select"
                                                />
                                            </div>
                                        </div>

                                        <div class="col-12">
                                            <div class="form-group compact-form-group">
                                                <InputLabel for="procurement_codes" value="PAP Codes" :message="form.errors.procurement_code_ids" />
                                                <Multiselect
                                                    :options="availableProcurementCodes"
                                                    v-model="form.procurement_code_ids"
                                                    :searchable="true"
                                                    label="label"
                                                    placeholder="Select PAP Codes"
                                                    mode="tags"
                                                    class="modern-select"
                                                />
                                                <small
                                                    v-if="procurementCodeBalanceHelper"
                                                    :class="procurementCodeBalanceHelperClass"
                                                    class="d-block mt-2"
                                                >
                                                   
                                                </small>
                                            </div>
                                        </div>

                                       
                                    </div>
                                </div>

                                <div class="col-xxl-4 col-xl-5">
                                    <div class="form-group compact-form-group h-100">
                                        <InputLabel for="purpose" value="Request Purpose" :message="form.errors.purpose" />
                                        <b-form-textarea
                                            id="purpose"
                                            v-model="form.purpose"
                                            placeholder="Enter your request purpose"
                                            rows="7"
                                            max-rows="10"
                                            class="modern-textarea request-purpose-textarea"
                                        ></b-form-textarea>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </BCol>

                <BCol lg="12" class="mt-0">
                    <div class="content-card">
                        <div class="card-header-custom">
                            <i class="ri-shopping-bag-line card-header-icon"></i>
                            <h5 class="card-header-title">Procurement Items</h5>
                            <div class="ms-auto">
                                <b-button
                                    :disabled="!form.division_id || !form.unit_id || !form.fund_cluster_id || !form.purpose"
                                    @click="openAddItem()"
                                    variant="primary"
                                    size="sm"
                                    class="add-item-btn"
                                >
                                    <i class="ri-add-line me-1"></i>
                                    Add Item
                                </b-button>
                            </div>
                        </div>
                        <div class="card-body-custom">
                            <div v-if="form.items && form.items.length > 0" class="items-table-container">
                                <div class="table-responsive">
                                    <table class="items-table">
                                        <thead>
                                            <tr>
                                                <th class="text-center">#</th>
                                                <th>Unit</th>
                                                <th>Name/Description</th>
                                                <th class="text-center">Qty</th>
                                                <th class="text-end">Unit Cost</th>
                                                <th class="text-end">Total</th>
                                                <th class="text-center"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="(item, index) in form.items" :key="item.id || index" class="item-row">
                                                <td class="text-center item-number">{{ index + 1 }}</td>
                                                <td class="item-unit">
                                                    <span class="unit-badge">
                                                        {{
                                                            item.item_quantity > 1
                                                                ? item.item_unit_type?.[0]?.name_long || item.item_unit_type?.name_long || ""
                                                                : item.item_unit_type?.[0]?.name_short || item.item_unit_type?.name_short || ""
                                                        }}
                                                    </span>
                                                </td>
                                                <td class="item-description">
                                                    <span>{{ item.item_name || "-" }}</span>
                                                    <div v-html="item.item_description"></div>
                                                </td>
                                                <td class="text-center item-quantity">{{ item.item_quantity }}</td>
                                                <td class="text-end item-cost">{{ formatCurrency(item.item_unit_cost) }}</td>
                                                <td class="text-end item-total">{{ formatCurrency(item.total_cost) }}</td>
                                                <td class="text-center">
                                                    <div class="d-flex justify-content-center gap-1">
                                                        <b-button
                                                            @click="editItem(index)"
                                                            variant="success"
                                                            size="sm"
                                                            class="btn-icon"
                                                            v-b-tooltip.hover
                                                            title="Edit Item"
                                                            style="border-radius: 8px;"
                                                        >
                                                            <i class="ri-edit-2-line"></i>
                                                        </b-button>
                                                        <b-button
                                                            @click="removeItem(index)"
                                                            variant="danger"
                                                            size="sm"
                                                            class="btn-icon"
                                                            v-b-tooltip.hover
                                                            title="Remove Item"
                                                            style="border-radius: 8px;"
                                                        >
                                                            <i class="ri-delete-bin-line"></i>
                                                        </b-button>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                        <tfoot>
                                            <tr class="grand-total-row">
                                                <td colspan="5" class="text-end grand-total-label">Grand Total:</td>
                                                <td class="text-end grand-total-amount">{{ formatCurrency(totalCostSum) }}</td>
                                                <td></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                            <div v-else class="empty-state">
                                <div class="empty-state-icon">
                                    <i class="ri-shopping-bag-line"></i>
                                </div>
                                <h6 class="empty-state-title">No Items Added</h6>
                                <p class="empty-state-text">Click "Add Item" to start adding procurement items.</p>
                            </div>
                            <small v-if="form.errors.items" class="text-danger d-block mt-2">
                                {{ form.errors.items }}
                            </small>
                        </div>
                    </div>
                </BCol>

                <BCol lg="12" class="mt-0">
                    <div class="content-card">
                        <div class="card-header-custom">
                            <i class="ri-user-line card-header-icon"></i>
                            <h5 class="card-header-title">Assignees</h5>
                        </div>
                        <div class="card-body-custom">
                            <div class="row g-3">
                                <div class="col-lg-6">
                                    <div class="form-group compact-form-group">
                                        <InputLabel for="requested_by" value="Requested By" :message="form.errors.requested_by_id" />
                                        <Multiselect
                                            :options="dropdowns.requesters"
                                            v-model="form.requested_by_id"
                                            :searchable="true"
                                            label="name"
                                            placeholder="Select Requester"
                                            class="modern-select"
                                        />
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="form-group compact-form-group">
                                        <InputLabel for="approved_by" value="Approved By" :message="form.errors.approved_by_id" />
                                        <Multiselect
                                            :options="dropdowns.approvers"
                                            v-model="form.approved_by_id"
                                            :searchable="true"
                                            label="name"
                                            placeholder="Select Approver"
                                            class="modern-select"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </BCol>
            </BRow>
        </form>

        <Item
            :dropdowns="dropdowns"
            :storage-key="itemStorageKey"
            @refresh="getDataFromLocalStorage()"
            ref="item"
        />

        <template v-slot:footer>
            <b-button @click="hide()" variant="light" block>Cancel</b-button>
            <b-button @click="submit()" variant="primary" :disabled="submitting || !canCreateRequest" block>
                {{ submitting ? "Submitting..." : "Submit" }}
            </b-button>
        </template>
    </b-modal>
</template>

<script>
import { useForm } from "@inertiajs/vue3";
import Multiselect from "@vueform/multiselect";
import InputLabel from "@/Shared/Components/Forms/InputLabel.vue";
import TextInput from "@/Shared/Components/Forms/TextInput.vue";
import Item from "@/Pages/Modules/FAIMS/Procurement/Modals/Item.vue";

export default {
    components: { Multiselect, InputLabel, TextInput, Item },
    props: ["dropdowns"],
    data() {
        return {
            form: useForm({
                code: null,
                purpose: null,
                title: null,
                date: this.getCurrentDate(),
                division_id: null,
                unit_id: null,
                fund_cluster_id: null,
                classification_id: null,
                items: [],
                requested_by_id: null,
                approved_by_id: null,
                procurement_code_ids: [],
            }),
            showModal: false,
            units: [],
            submitting: false,
            itemStorageKey: "portalProcurementItemsAdded",
        };
    },
    computed: {
        totalCostSum() {
            if (!Array.isArray(this.form.items)) return 0;

            return this.form.items.reduce((sum, item) => {
                return sum + (parseFloat(item.total_cost) || 0);
            }, 0);
        },
        normalizedProcurementCodes() {
            const options = Array.isArray(this.dropdowns?.procurement_codes)
                ? this.dropdowns.procurement_codes
                : [];

            return options.map((option) => {
                const remainingBudget = Number(
                    option.remaining_budget ?? option.allocated_budget ?? 0
                );

                return {
                    ...option,
                    remaining_budget: remainingBudget,
                    label: option.label || option.code || option.title || "",
                };
            });
        },
        availableProcurementCodes() {
            const selectedIds = new Set(
                Array.isArray(this.form.procurement_code_ids)
                    ? this.form.procurement_code_ids.map((id) => Number(id))
                    : []
            );

            return this.normalizedProcurementCodes.filter((option) => {
                return selectedIds.has(Number(option.value)) || option.remaining_budget > 0;
            });
        },
        selectedProcurementCodeBalance() {
            if (!Array.isArray(this.form.procurement_code_ids) || this.form.procurement_code_ids.length === 0) {
                return 0;
            }

            const selectedIds = new Set(this.form.procurement_code_ids.map((id) => Number(id)));

            return this.normalizedProcurementCodes.reduce((sum, option) => {
                if (!selectedIds.has(Number(option.value))) {
                    return sum;
                }

                return sum + (Number(option.remaining_budget) || 0);
            }, 0);
        },
        selectedProcurementCodeDetails() {
            if (!Array.isArray(this.form.procurement_code_ids) || this.form.procurement_code_ids.length === 0) {
                return [];
            }

            const optionMap = this.normalizedProcurementCodes.reduce((map, option) => {
                map.set(Number(option.value), option);
                return map;
            }, new Map());

            return this.form.procurement_code_ids
                .map((id) => optionMap.get(Number(id)))
                .filter(Boolean);
        },
        procurementCodeBudgetGap() {
            return this.selectedProcurementCodeBalance - this.totalCostSum;
        },
        hasEnoughSelectedProcurementCodeBalance() {
            if (!Array.isArray(this.form.procurement_code_ids) || this.form.procurement_code_ids.length === 0) {
                return true;
            }

            if (this.totalCostSum <= 0) {
                return true;
            }

            return this.selectedProcurementCodeBalance + 0.009 >= this.totalCostSum;
        },
        procurementCodeBudgetStatusClass() {
            if (!Array.isArray(this.form.procurement_code_ids) || this.form.procurement_code_ids.length === 0) {
                return "pap-budget-overview--idle";
            }

            if (this.totalCostSum <= 0) {
                return "pap-budget-overview--idle";
            }

            return this.hasEnoughSelectedProcurementCodeBalance
                ? "pap-budget-overview--covered"
                : "pap-budget-overview--short";
        },

        procurementCodeBalanceHelperClass() {
            return this.hasEnoughSelectedProcurementCodeBalance ? "text-muted" : "text-danger";
        },
        canCreateRequest() {
            return this.isFormValid && this.hasEnoughSelectedProcurementCodeBalance;
        },
        isFormValid() {
            return this.form.division_id &&
                this.form.unit_id &&
                this.form.fund_cluster_id &&
                this.form.purpose &&
                this.form.requested_by_id &&
                this.form.approved_by_id &&
                Array.isArray(this.form.items) &&
                this.form.items.length > 0;
        },
    },
    watch: {
        "form.division_id"(newVal) {
            this.form.unit_id = null;
            this.units = [];

            if (newVal) {
                this.getUnits(newVal);
            }
        },
        "form.procurement_code_ids"(value) {
            this.form.title = "";

            if (Array.isArray(value) && value.length > 0) {
                value.forEach((id) => {
                    this.getProcurementTitle(id);
                });
            }
        },
    },
    methods: {
        show() {
            this.resetForm();
            this.showModal = true;
        },
        hide() {
            this.showModal = false;
            this.resetForm();
        },
        resetForm() {
            localStorage.removeItem(this.itemStorageKey);
            this.form.reset();
            this.form.clearErrors();
            this.form.date = this.getCurrentDate();
            this.form.items = [];
            this.form.procurement_code_ids = [];
            this.units = [];
            this.submitting = false;

            if (this.dropdowns?.regional_director?.value) {
                this.form.approved_by_id = this.dropdowns.regional_director.value;
            }
        },
        openAddItem() {
            this.$refs.item.show();
        },
        editItem(index) {
            this.$refs.item.edit(this.form.items[index], index);
        },
        removeItem(index) {
            let items = JSON.parse(localStorage.getItem(this.itemStorageKey)) || [];

            if (index >= 0 && index < items.length) {
                items.splice(index, 1);
            }

            localStorage.setItem(this.itemStorageKey, JSON.stringify(items));
            this.form.items = items;
        },
        getDataFromLocalStorage() {
            let storedItems = [];

            try {
                storedItems = JSON.parse(localStorage.getItem(this.itemStorageKey)) || [];
            } catch (e) {
                storedItems = [];
                localStorage.setItem(this.itemStorageKey, JSON.stringify([]));
            }

            this.form.items = storedItems;
        },
        submit() {
            if (this.submitting) {
                return;
            }

            this.submitting = true;
            this.form.clearErrors();

            const payload = {
                code: this.form.code,
                purpose: this.form.purpose,
                title: this.form.title,
                date: this.form.date,
                division_id: this.form.division_id,
                unit_id: this.form.unit_id,
                fund_cluster_id: this.form.fund_cluster_id,
                classification_id: this.form.classification_id,
                items: this.form.items,
                requested_by_id: this.form.requested_by_id,
                approved_by_id: this.form.approved_by_id,
                procurement_code_ids: this.form.procurement_code_ids,
            };

            axios
                .post("/faims/procurements", payload, {
                    headers: {
                        Accept: "application/json",
                        "X-Requested-With": "XMLHttpRequest",
                    },
                })
                .then(() => {
                    this.$emit("success", true);
                    this.hide();
                })
                .catch((error) => {
                    const validationErrors = error.response?.data?.errors || {};
                    this.form.setError(validationErrors);
                })
                .finally(() => {
                    this.submitting = false;
                });
        },
        getUnits(divisionId) {
            axios
                .get("/faims/procurements/create", {
                    params: {
                        code: divisionId,
                        option: "units",
                    },
                })
                .then((response) => {
                    this.units = response.data;
                })
                .catch((err) => console.log(err));
        },
        getProcurementTitle(id) {
            axios
                .get("/faims/procurements/create", {
                    params: {
                        id,
                        option: "title",
                    },
                })
                .then((response) => {
                    if (this.form.title) {
                        this.form.title += ", " + response.data;
                    } else {
                        this.form.title = response.data;
                    }
                })
                .catch((err) => console.log(err));
        },
        openProcurementCodeProfile(id) {
            if (!id) {
                return;
            }

            window.open(`/faims/procurement-codes/${id}`, "_blank", "noopener");
        },
        formatCurrency(value) {
            return new Intl.NumberFormat("en-PH", {
                style: "currency",
                currency: "PHP",
            }).format(Number(value) || 0);
        },
        getProcurementCodeBalanceClass(value) {
            return Number(value) < 0 ? "text-danger" : "text-success";
        },
        getCurrentDate() {
            const today = new Date();
            const year = today.getFullYear();
            const month = String(today.getMonth() + 1).padStart(2, "0");
            const day = String(today.getDate()).padStart(2, "0");
            return `${year}-${month}-${day}`;
        },
    },
};
</script>

<style scoped>
.procurement-request-modal {
    --procurement-create-card-bg: #ffffff;
    --procurement-create-card-border: #e9ebec;
    --procurement-create-card-shadow: none;
    --procurement-create-card-hover-shadow: none;
    --procurement-create-text: #212529;
    --procurement-create-muted: #6c757d;
    --procurement-create-header-bg: #ffffff;
    --procurement-create-header-border: #e9ebec;
    --procurement-create-input-border: #e9ebec;
    --procurement-create-input-bg: #f8f9fa;
    --procurement-create-input-text: #212529;
    --procurement-create-placeholder: #6c757d;
    --procurement-create-table-bg: #ffffff;
    --procurement-create-table-row-alt: #ffffff;
    --procurement-create-table-row-hover: #f8f9fa;
    --procurement-create-table-border: #e9ebec;
    --procurement-create-unit-badge-bg: #f1f3f5;
    --procurement-create-unit-badge-text: #495057;
    --procurement-create-empty-icon-bg: #f8f9fa;
    --procurement-create-empty-icon-text: #6c757d;
}

.content-card {
    background: var(--procurement-create-card-bg);
    border-radius: 0.5rem;
    box-shadow: var(--procurement-create-card-shadow);
    border: 1px solid var(--procurement-create-card-border);
    overflow: visible;
    color: var(--procurement-create-text);
}

.content-card:hover {
    box-shadow: var(--procurement-create-card-hover-shadow);
}

.request-details-body {
    padding: 1rem !important;
}

.card-header-custom {
    background: var(--procurement-create-header-bg);
    padding: 0.85rem 1rem;
    border-bottom: 1px solid var(--procurement-create-header-border);
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.card-header-icon {
    font-size: 1rem;
    color: #6c757d;
    background: transparent;
    padding: 0;
    border-radius: 0;
}

.card-header-title {
    font-size: 0.95rem;
    font-weight: 600;
    color: var(--procurement-create-text);
    margin: 0;
}

.card-body-custom {
    padding: 1rem;
    overflow: visible;
}

.form-group {
    margin-bottom: 0.75rem;
}

.compact-form-group {
    margin-bottom: 0;
}

.modern-select {
    border-radius: 0.375rem;
}

.modern-input,
.modern-textarea {
    border-radius: 0.375rem;
    border: 1px solid var(--procurement-create-input-border);
    background: var(--procurement-create-input-bg);
    color: var(--procurement-create-input-text);
}

.modern-input[type="date"] {
    border-width: 1px;
    border-style: solid;
    border-color: #e9ebec;
}

.modern-input {
    padding: 0.45rem 0.85rem;
}

.modern-textarea {
    padding: 0.65rem 0.85rem;
    resize: vertical;
}

.modern-input:focus,
.modern-textarea:focus {
    border-color: #86b7fe;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.15);
}

.request-purpose-textarea {
    min-height: 150px;
    height: calc(100% - 1.55rem);
    border-width: 1px;
    border-style: solid;
    border-color: #e9ebec;
}

.add-item-btn {
    border-radius: 0.375rem;
    padding: 0.35rem 0.9rem;
    font-weight: 600;
}

.items-table-container {
    border-radius: 0.5rem;
    overflow: hidden;
    border: 1px solid var(--procurement-create-table-border);
}

.items-table {
    width: 100%;
    border-collapse: collapse;
    background: var(--procurement-create-table-bg);
}

.items-table thead {
    background: #f8f9fa;
    color: #495057;
}

.items-table th {
    padding: 0.7rem 0.85rem;
    font-weight: 600;
    font-size: 0.8rem;
    text-transform: uppercase;
}

.items-table tbody tr:nth-child(even) td {
    background: var(--procurement-create-table-row-alt);
}

.items-table tbody tr:hover td {
    background: var(--procurement-create-table-row-hover);
}

.items-table td {
    padding: 0.75rem 0.85rem;
    border-bottom: 1px solid var(--procurement-create-table-border);
    vertical-align: top;
    background: var(--procurement-create-table-bg);
    color: var(--procurement-create-text);
}

.item-number {
    font-weight: 600;
    color: #495057;
}

.unit-badge {
    background: var(--procurement-create-unit-badge-bg);
    color: var(--procurement-create-unit-badge-text);
    padding: 0.2rem 0.6rem;
    border-radius: 999px;
    font-size: 0.8rem;
    font-weight: 500;
}

.item-description {
    max-width: 300px;
}

.item-cost,
.item-total,
.grand-total-amount {
    font-weight: 700;
    color: #198754;
}

.grand-total-row {
    background: #f8f9fa;
    border-top: 1px solid #dee2e6;
}

.grand-total-label {
    font-weight: 700;
    color: var(--procurement-create-text);
}

.empty-state {
    text-align: center;
    padding: 1.75rem 1rem;
}

.empty-state-icon {
    width: 52px;
    height: 52px;
    border-radius: 50%;
    background: var(--procurement-create-empty-icon-bg);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: var(--procurement-create-empty-icon-text);
    margin: 0 auto 1rem;
}

.empty-state-title {
    font-size: 1rem;
    font-weight: 600;
    color: var(--procurement-create-text);
    margin-bottom: 0.5rem;
}

.empty-state-text {
    color: var(--procurement-create-muted);
    font-size: 0.9rem;
}

.pap-budget-overview {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 0.75rem;
}

.pap-budget-overview__card {
    border-radius: 0.5rem;
    padding: 0.85rem 1rem;
    border: 1px solid #e9ebec;
    background: #f8f9fa;
}

.pap-budget-overview__label {
    display: block;
    margin-bottom: 0.35rem;
    color: var(--procurement-create-muted);
    font-size: 0.74rem;
    text-transform: uppercase;
}

.pap-budget-overview__value {
    display: block;
    color: var(--procurement-create-text);
    font-size: 1rem;
    font-weight: 700;
}

.pap-budget-overview--covered .pap-budget-overview__card {
    border-color: rgba(25, 135, 84, 0.25);
}

.pap-budget-overview--short .pap-budget-overview__card {
    border-color: rgba(220, 53, 69, 0.25);
}

.pap-selected-code-list {
    display: grid;
    gap: 0.75rem;
}

.pap-selected-code {
    width: 100%;
    padding: 0.85rem 1rem;
    border-radius: 0.5rem;
    border: 1px solid #e9ebec;
    background: #ffffff;
    text-align: left;
}

.pap-selected-code:hover {
    background: #f8f9fa;
}

.pap-selected-code__top,
.pap-selected-code__bottom {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
}

.pap-selected-code__bottom {
    margin-top: 0.45rem;
}

.pap-selected-code__code {
    display: inline-flex;
    align-items: center;
    padding: 0.2rem 0.55rem;
    border-radius: 999px;
    background: #eef2f7;
    color: #495057;
    font-size: 0.78rem;
    font-weight: 700;
}

.pap-selected-code__balance {
    font-size: 0.82rem;
    font-weight: 700;
    white-space: nowrap;
}

.pap-selected-code__title {
    color: var(--procurement-create-text);
    font-size: 0.88rem;
    font-weight: 600;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.pap-selected-code__action {
    color: #0d6efd;
    font-size: 0.78rem;
    font-weight: 700;
}

@media (max-width: 768px) {
    .pap-budget-overview {
        grid-template-columns: 1fr;
    }

    .pap-selected-code__top,
    .pap-selected-code__bottom {
        flex-direction: column;
        align-items: flex-start;
    }
}
</style>
