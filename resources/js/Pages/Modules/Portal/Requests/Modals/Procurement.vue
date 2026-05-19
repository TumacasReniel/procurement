<template>
    <b-modal
        v-model="show_modal"
        style="--vz-modal-width: 1050px;"
        header-class="p-3 bg-light"
        title="Purchase Request"
        class="v-modal-custom"
        modal-class="zoomIn"
        centered
        no-close-on-backdrop
    >
        <form class="customform">
            <BRow class="g-3 p-2">
                <BCol lg="6" class="mt-3">
                    <InputLabel for="division" value="Division" :message="form.errors.division_id" />
                    <Multiselect
                        :options="dropdowns.divisions"
                        v-model="form.division_id"
                        :searchable="true"
                        label="name"
                        placeholder="Select Division"
                    />
                </BCol>
                <BCol lg="6" class="mt-3">
                    <InputLabel value="PR Date" :message="form.errors.date" />
                    <TextInput
                        v-model="form.date"
                        type="date"
                        class="form-control"
                        :light="true"
                    />
                </BCol>
                <BCol lg="6" class="mt-0">
                    <InputLabel for="unit" value="Unit" :message="form.errors.unit_id" />
                    <Multiselect
                        :options="units"
                        v-model="form.unit_id"
                        :searchable="true"
                        label="name"
                        placeholder="Select Unit"
                    />
                </BCol>
                <BCol lg="6" class="mt-0">
                    <InputLabel for="fund_cluster" value="Fund Cluster" :message="form.errors.fund_cluster_id" />
                    <Multiselect
                        :options="dropdowns.fund_clusters"
                        v-model="form.fund_cluster_id"
                        :searchable="true"
                        label="name"
                        placeholder="Select Fund Cluster"
                    />
                </BCol>
                <BCol lg="12" class="mt-0">
                    <InputLabel for="procurement_codes" value="PAP Codes" :message="form.errors.procurement_code_ids" />
                    <Multiselect
                        :options="available_procurement_codes"
                        v-model="form.procurement_code_ids"
                        :searchable="true"
                        label="label"
                        placeholder="Select PAP Codes"
                        mode="tags"
                    />
                    <small
                        v-if="procurement_code_balance_helper"
                        :class="procurement_code_balance_helper_class"
                        class="d-block mt-2"
                    >
                       
                    </small>
                </BCol>
                <BCol lg="12" class="mt-0">
                    <InputLabel for="purpose" value="Request Purpose" :message="form.errors.purpose" />
                    <b-form-textarea
                        id="purpose"
                        v-model="form.purpose"
                        placeholder="Please enter purpose"
                        rows="3"
                        max-rows="6"
                        class="form-control"
                        style="background-color: #f5f6f7;"
                    ></b-form-textarea>
                </BCol>

                <BCol lg="12">
                    <hr class="text-muted mt-0 mb-n1"/>
                </BCol>

                <BCol lg="12" class="mt-2">
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="fs-11 text-muted">
                            Items <span class="text-danger">{{ form.errors.items }}</span>
                        </span>
                        <b-button
                            :disabled="!form.division_id || !form.unit_id || !form.fund_cluster_id || !form.purpose"
                            @click="open_add_item()"
                            variant="light"
                            size="sm"
                        >
                            <i class="ri-add-line me-1"></i>
                            Add Item
                        </b-button>
                    </div>
                    <hr class="text-muted mt-2 mb-3"/>

                    <div v-if="form.items && form.items.length > 0" class="table-responsive bg-white">
                        <table class="table align-middle table-bordered table-centered mb-0">
                                        <thead>
                                            <tr class="fs-11">
                                                <th style="width: 4%;" class="text-center">#</th>
                                                <th>Unit</th>
                                                <th>Name/Description</th>
                                                <th class="text-center">Qty</th>
                                                <th class="text-end">Unit Cost</th>
                                                <th class="text-end">Total</th>
                                                <th class="text-center"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="(item, index) in form.items" :key="item.id || index" class="fs-12">
                                                <td class="text-center">{{ index + 1 }}</td>
                                                <td>
                                                    <span class="badge bg-light text-muted">
                                                        {{
                                                            item.item_quantity > 1
                                                                ? item.item_unit_type?.[0]?.name_long || item.item_unit_type?.name_long || ""
                                                                : item.item_unit_type?.[0]?.name_short || item.item_unit_type?.name_short || ""
                                                        }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span>{{ item.item_name || "-" }}</span>
                                                    <div v-html="item.item_description"></div>
                                                </td>
                                                <td class="text-center">{{ item.item_quantity }}</td>
                                                <td class="text-end">{{ format_currency(item.item_unit_cost) }}</td>
                                                <td class="text-end fw-semibold">{{ format_currency(item.total_cost) }}</td>
                                                <td class="text-center">
                                                    <div class="d-flex justify-content-center gap-1">
                                                        <b-button
                                                            @click="edit_item(index)"
                                                            variant="soft-success"
                                                            size="sm"
                                                            class="btn-icon"
                                                            v-b-tooltip.hover
                                                            title="Edit Item"
                                                        >
                                                            <i class="ri-edit-2-line"></i>
                                                        </b-button>
                                                        <b-button
                                                            @click="remove_item(index)"
                                                            variant="soft-danger"
                                                            size="sm"
                                                            class="btn-icon"
                                                            v-b-tooltip.hover
                                                            title="Remove Item"
                                                        >
                                                            <i class="ri-delete-bin-line"></i>
                                                        </b-button>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                        <tfoot>
                                            <tr class="table-light fs-12">
                                                <td colspan="5" class="text-end fw-semibold">Grand Total:</td>
                                                <td class="text-end fw-semibold">{{ format_currency(total_cost_sum) }}</td>
                                                <td></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                    </div>
                    <div v-else class="alert alert-secondary text-center fs-12 mb-0" role="alert">
                        No items added. Click <b>Add Item</b> to start adding items.
                    </div>
                </BCol>

                <BCol lg="12">
                    <hr class="text-muted mt-0 mb-n1"/>
                </BCol>

                <BCol lg="6" class="mt-2">
                    <InputLabel for="requested_by" value="Requested By" :message="form.errors.requested_by_id" />
                    <Multiselect
                        :options="dropdowns.requesters"
                        v-model="form.requested_by_id"
                        :searchable="true"
                        label="name"
                        placeholder="Select Requester"
                    />
                </BCol>
                <BCol lg="6" class="mt-2">
                    <InputLabel for="approved_by" value="Approved By" :message="form.errors.approved_by_id" />
                    <Multiselect
                        :options="dropdowns.approvers"
                        v-model="form.approved_by_id"
                        :searchable="true"
                        label="name"
                        placeholder="Select Approver"
                    />
                </BCol>
            </BRow>
        </form>

        <Item
            :dropdowns="dropdowns"
            :storage-key="item_storage_key"
            @refresh="get_data_from_local_storage()"
            ref="item"
        />

        <template v-slot:footer>
            <b-button @click="hide()" variant="light" block>Cancel</b-button>
            <b-button @click="submit()" variant="primary" :disabled="submitting || !can_create_request" block>
                {{ submitting ? "Submitting..." : "Submit" }}
            </b-button>
        </template>
    </b-modal>
</template>

<script>
import { router, useForm } from "@inertiajs/vue3";
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
                date: this.get_current_date(),
                division_id: null,
                unit_id: null,
                fund_cluster_id: null,
                classification_id: null,
                items: [],
                requested_by_id: null,
                approved_by_id: null,
                procurement_code_ids: [],
            }),
            show_modal: false,
            units: [],
            submitting: false,
            item_storage_key: "portal_procurement_items_added",
        };
    },
    computed: {
        total_cost_sum() {
            if (!Array.isArray(this.form.items)) return 0;

            return this.form.items.reduce((sum, item) => {
                return sum + (parseFloat(item.total_cost) || 0);
            }, 0);
        },
        normalized_procurement_codes() {
            const options = Array.isArray(this.dropdowns?.procurement_codes)
                ? this.dropdowns.procurement_codes
                : [];

            return options.map((option) => {
                const remaining_budget = Number(
                    option.remaining_budget ?? option.allocated_budget ?? 0
                );

                return {
                    ...option,
                    remaining_budget: remaining_budget,
                    label: option.label || option.code || option.title || "",
                };
            });
        },
        available_procurement_codes() {
            const selected_ids = new Set(
                Array.isArray(this.form.procurement_code_ids)
                    ? this.form.procurement_code_ids.map((id) => Number(id))
                    : []
            );

            return this.normalized_procurement_codes.filter((option) => {
                return selected_ids.has(Number(option.value)) || option.remaining_budget > 0;
            });
        },
        selected_procurement_code_balance() {
            if (!Array.isArray(this.form.procurement_code_ids) || this.form.procurement_code_ids.length === 0) {
                return 0;
            }

            const selected_ids = new Set(this.form.procurement_code_ids.map((id) => Number(id)));

            return this.normalized_procurement_codes.reduce((sum, option) => {
                if (!selected_ids.has(Number(option.value))) {
                    return sum;
                }

                return sum + (Number(option.remaining_budget) || 0);
            }, 0);
        },
        selected_procurement_code_details() {
            if (!Array.isArray(this.form.procurement_code_ids) || this.form.procurement_code_ids.length === 0) {
                return [];
            }

            const option_map = this.normalized_procurement_codes.reduce((map, option) => {
                map.set(Number(option.value), option);
                return map;
            }, new Map());

            return this.form.procurement_code_ids
                .map((id) => option_map.get(Number(id)))
                .filter(Boolean);
        },
        procurement_code_budget_gap() {
            return this.selected_procurement_code_balance - this.total_cost_sum;
        },
        has_enough_selected_procurement_code_balance() {
            if (!Array.isArray(this.form.procurement_code_ids) || this.form.procurement_code_ids.length === 0) {
                return true;
            }

            if (this.total_cost_sum <= 0) {
                return true;
            }

            return this.selected_procurement_code_balance + 0.009 >= this.total_cost_sum;
        },
        procurement_code_budget_status_class() {
            if (!Array.isArray(this.form.procurement_code_ids) || this.form.procurement_code_ids.length === 0) {
                return "pap-budget-overview--idle";
            }

            if (this.total_cost_sum <= 0) {
                return "pap-budget-overview--idle";
            }

            return this.has_enough_selected_procurement_code_balance
                ? "pap-budget-overview--covered"
                : "pap-budget-overview--short";
        },

        procurement_code_balance_helper_class() {
            return this.has_enough_selected_procurement_code_balance ? "text-muted" : "text-danger";
        },
        procurement_code_balance_helper() {
            return null;
        },
        can_create_request() {
            return this.is_form_valid && this.has_enough_selected_procurement_code_balance;
        },
        is_form_valid() {
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
        "form.division_id"(new_value) {
            this.form.unit_id = null;
            this.units = [];

            if (new_value) {
                this.get_units(new_value);
            }
        },
        "form.procurement_code_ids"(value) {
            this.form.title = "";

            if (Array.isArray(value) && value.length > 0) {
                value.forEach((id) => {
                    this.get_procurement_title(id);
                });
            }
        },
    },
    methods: {
        show() {
            this.reset_form();
            this.show_modal = true;
        },
        hide() {
            this.show_modal = false;
            this.reset_form();
        },
        reset_form() {
            localStorage.removeItem(this.item_storage_key);
            this.form.reset();
            this.form.clearErrors();
            this.form.date = this.get_current_date();
            this.form.items = [];
            this.form.procurement_code_ids = [];
            this.units = [];
            this.submitting = false;

            if (this.dropdowns?.regional_director?.value) {
                this.form.approved_by_id = this.dropdowns.regional_director.value;
            }
        },
        open_add_item() {
            this.$refs.item.show();
        },
        edit_item(index) {
            this.$refs.item.edit(this.form.items[index], index);
        },
        remove_item(index) {
            let items = JSON.parse(localStorage.getItem(this.item_storage_key)) || [];

            if (index >= 0 && index < items.length) {
                items.splice(index, 1);
            }

            localStorage.setItem(this.item_storage_key, JSON.stringify(items));
            this.form.items = items;
        },
        get_data_from_local_storage() {
            let stored_items = [];

            try {
                stored_items = JSON.parse(localStorage.getItem(this.item_storage_key)) || [];
            } catch (e) {
                stored_items = [];
                localStorage.setItem(this.item_storage_key, JSON.stringify([]));
            }

            this.form.items = stored_items;
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

            router.post("/faims/procurements", payload, {
                preserveScroll: true,
                onSuccess: () => {
                    this.$emit("success", true);
                    this.hide();
                },
                onError: (errors) => {
                    this.form.setError(errors || {});
                },
                onFinish: () => {
                    this.submitting = false;
                },
            });
        },
        get_units(division_id) {
            axios
                .get("/faims/procurements/create", {
                    params: {
                        code: division_id,
                        option: "units",
                    },
                })
                .then((response) => {
                    this.units = response.data;
                })
                .catch((err) => console.log(err));
        },
        get_procurement_title(id) {
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
        open_procurement_code_profile(id) {
            if (!id) {
                return;
            }

            window.open(`/faims/procurement-codes/${id}`, "_blank", "noopener");
        },
        format_currency(value) {
            return new Intl.NumberFormat("en-PH", {
                style: "currency",
                currency: "PHP",
            }).format(Number(value) || 0);
        },
        get_procurement_code_balance_class(value) {
            return Number(value) < 0 ? "text-danger" : "text-success";
        },
        get_current_date() {
            const today = new Date();
            const year = today.getFullYear();
            const month = String(today.getMonth() + 1).padStart(2, "0");
            const day = String(today.getDate()).padStart(2, "0");
            return `${year}-${month}-${day}`;
        },
    },
};
</script>
