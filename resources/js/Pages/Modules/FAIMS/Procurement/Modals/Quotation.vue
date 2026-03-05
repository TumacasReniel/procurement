<template>
    <b-modal
        v-model="showModal"
        header-class="p-3"
        title="Create Request for Quotation"
        size="xl"
        class="v-modal-custom"
        modal-class="zoomIn"
        centered
        no-close-on-backdrop
    >
        <form class="customform">
            <BRow>
                <BCol lg="6" class="mt-2">
                    <InputLabel for="supplier" value="Supplier" />
                    <Multiselect
                        :options="filteredSuppliers"
                        v-model="form.supplier_ids"
                        :searchable="true"
                        label="name"
                        mode="tags"
                        placeholder="Select Supplier"
                    />
                </BCol>

                <BCol lg="6" class="mt-2">
                    <InputLabel value="PR Number" />
                    <TextInput
                        v-model="procurement.code"
                        type="text"
                        class="form-control"
                        :light="true"
                        readonly
                    />
                </BCol>

                <BCol lg="6" class="mt-2">
                    <InputLabel value="Date" />
                    <TextInput
                        v-model="procurement.date"
                        type="text"
                        class="form-control"
                        :light="true"
                        readonly
                    />
                </BCol>

                <BCol lg="6" class="mt-2">
                    <InputLabel
                        value="Submissions not Later than "
                        :message="form.errors.submission_date"
                    />
                    <TextInput
                        v-model="form.submission_not_later_than"
                        type="date"
                        class="form-control"
                        :light="true"
                    />
                </BCol>

                <BCol lg="6" class="mt-2">
                    <InputLabel
                        for="supply_officer"
                        value="Supply Officer"
                        :message="form.errors.supply_officer_id"
                    />

                    <Multiselect
                        :options="dropdowns.supply_officers || []"
                        v-model="form.supply_officer_id"
                        :searchable="true"
                        label="name"
                        value-prop="value"
                        placeholder="Select Supply Officer"
                    />
                </BCol>

                <BCol lg="12"><hr class="text-muted mt-4 mb-0" /></BCol>
            </BRow>

            <BRow>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr class="fs-11">
                                <th>Item No</th>
                                <th>Quantity/Unit</th>
                                <th>Item Description</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody style="vertical-align: top">
                            <tr
                                v-for="(item, index) in form.items"
                                :key="index"
                            >
                                <td>{{ item.item_no }}</td>
                                <td>
                                    {{ item.item_quantity }}
                                    {{
                                        item.item_quantity > 1
                                            ? item.item_unit_type.name_long
                                            : item.item_unit_type.name_short
                                    }}
                                </td>

                                <td>
                                    <div v-html="item.item_description"></div>
                                </td>
                                <td></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </BRow>
        </form>

        <template v-slot:footer>
            <b-button @click="hide()" variant="light" block>Cancel</b-button>
            <b-button
                @click="submit()"
                variant="primary"
                :disabled="form.processing || !form.supply_officer_id"
                block
                >Save</b-button
            >
        </template>
    </b-modal>
</template>
<script>
import Amount from "@/Shared/Components/Forms/Amount.vue";
import InputError from "@/Shared/Components/Forms/InputError.vue";
import InputLabel from "@/Shared/Components/Forms/InputLabel.vue";
import TextInput from "@/Shared/Components/Forms/TextInput.vue";
import { useForm } from "@inertiajs/vue3";
import Multiselect from "@vueform/multiselect";

export default {
    components: {
        Amount,
        InputError,
        InputLabel,
        TextInput,
        Multiselect,
    },
    props: ["procurement", "dropdowns", "rfqs"],
    data() {
        return {
            currentUrl: window.location.origin,
            form: useForm({
                id: null,
                procurement_id: this.procurement.id,
                supplier_ids: [],
                submission_not_later_than: this.getDatePlusWorkingDays(7),
                supply_officer_id: null,
                items: this.procurement.items,
                option: "quotation_request",
            }),
            editable: false,
            showModal: false,
        };
    },

    computed: {
        filteredSuppliers() {
            const all = this.dropdowns.suppliers || [];

            // 2. Suppliers selected in modal
            const selected_ids = this.form.supplier_ids.map((item) =>
                typeof item === "object" ? item.value : item,
            );

            // 3. Suppliers already in RFQ list shown on page
            const rfq_supplier_ids = this.rfqs.map((r) => r.supplier?.id);

            // Merge exclusions
            const exclude_ids = new Set([...selected_ids, ...rfq_supplier_ids]);

            return all.filter((s) => !exclude_ids.has(s.value));
        },
    },

    methods: {
        show() {
            this.showModal = true;
        },

        hide() {
            this.form.reset();
            this.form.item_unit_cost = 0.0;
            this.showModal = false;
        },

        submit() {
            this.form.option = "save_quotations";
            this.form.post("/faims/quotations", {
                preserveScroll: true,
                onSuccess: () => {
                    this.$emit("add", true);
                    this.hide();
                },
                onError: (errors) => {
                    console.error("Submission failed");
                },
            });
        },

        getDatePlusWorkingDays(days) {
            let date = new Date();
            let addedDays = 0;

            while (addedDays < days) {
                date.setDate(date.getDate() + 1);
                const dayOfWeek = date.getDay();
                if (dayOfWeek !== 0 && dayOfWeek !== 6) {
                    addedDays++;
                }
            }

            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, "0");
            const day = String(date.getDate()).padStart(2, "0");

            return `${year}-${month}-${day}`;
        },
    },
};
</script>
