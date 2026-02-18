<template>
    <b-modal v-model="showModal" header-class="p-3"  :title="editable ? 'Update PAP' : 'New PAP'" size="lg" class="v-modal-custom" modal-class="zoomIn" centered no-close-on-backdrop >
        <form class="customform">
           <BRow>
            <BCol lg="4" class="mt-2">
                <InputLabel value="Code" />
                <TextInput v-model="form.code" type="text" :class="{'form-control': true, 'is-invalid': form.errors.code}" placeholder="Enter code"  />
                <InputError :message="form.errors.code" />
            </BCol>
            <BCol lg="4" class="mt-2">
                <InputLabel value="Allocated Budget" />
                <Amount @amount="amount" ref="amountComponent" :class="{'is-invalid': form.errors.allocated_budget}"/>
                <InputError :message="form.errors.allocated_budget" />
            </BCol>

            <BCol lg="4" class="mt-2">
                <InputLabel value="Year" />
                <Multiselect
                :options="yearOptions"
                v-model="form.year"
                :searchable="true" label="text"
                :class="{'is-invalid': form.errors.year}"
                placeholder="Year"/>
                <InputError :message="form.errors.year" />
            </BCol>

            <BCol lg="12" class="mt-2">
                    <InputLabel for="app_types" value="App Type"/>
                    <Multiselect
                    :options="app_types"
                    v-model="form.app_type_id"
                    :searchable="true" label="name"
                    :class="{'is-invalid': form.errors.app_type_id}"
                    placeholder="Select End Users"/>
                    <InputError :message="form.errors.app_type_id" />
            </BCol>

            <BCol lg="12" class="mt-2">
                    <InputLabel for="end_users" value="End Users"/>
                    <Multiselect
                    :options="end_users"
                    v-model="form.end_user_ids"
                    :searchable="true" label="name"
                    mode="tags"
                    :class="{'is-invalid': form.errors.end_user_ids}"
                    placeholder="Select End Users"/>
                    <InputError :message="form.errors.end_user_ids" />
            </BCol>

            <BCol lg="12" class="mt-2">
                <InputLabel for="mode_of_procurement" value="Mode of Procurement"/>
                <Multiselect
                :options="mode_of_procurements"
                v-model="form.mode_of_procurement_id"
                :searchable="true" label="name"
                :class="{'is-invalid': form.errors.mode_of_procurement_id}"
                placeholder="Mode of Procurement"/>
                <InputError :message="form.errors.mode_of_procurement_id" />
            </BCol>
            <BCol lg="12" class="mt-2">
                <InputLabel value="Project Description/Title" />
                <textarea
                    id="description"
                    v-model="form.title"
                    :class="{'form-control': true, 'is-invalid': form.errors.title}"
                    rows="5"
                    placeholder="Enter project description/title"
                    ></textarea>
                <InputError :message="form.errors.title" />
            </BCol>
        </BRow>

        </form>
   
          <template v-slot:footer>
            <b-button @click="hide()" variant="light" block>Close</b-button>
            <b-button @click="savePAP(form)" variant="success"  block>Save</b-button>
        </template>
        
    </b-modal>
</template>
<script>
import { useForm } from '@inertiajs/vue3';
import Multiselect from "@vueform/multiselect";
import InputError from '@/Shared/Components/Forms/InputError.vue';
import InputLabel from '@/Shared/Components/Forms/InputLabel.vue';
import TextInput from '@/Shared/Components/Forms/TextInput.vue';
import Amount from '@/Shared/Components/Forms/Amount.vue';

export default {
    components: { InputError, InputLabel, TextInput, Multiselect ,Amount },
    props:['mode_of_procurements' , 'app_types' , 'end_users'],
    data(){
        return {
            currentUrl: window.location.origin,
            yearOptions: [],
            selectedYear: null,
            form: useForm({
                id: null,
                title: null,
                code: null,
                allocated_budget: null,
                year: null,
                end_user_ids : [],
                app_type_id: null,
                mode_of_procurement_id: null,
            }),
            showModal: false,
            editable: false,
        }
    },

    mounted() {
        this.generateYearOptions();
    },

    watch: {
        selectedYear(newVal) {
            if (newVal) {
                this.form.year = newVal.value;
            } else {
                this.form.year = null;
            }
        }
    },

    methods: { 

        amount(val){
            this.form.allocated_budget = this.cleanCurrency(val);
        },

        cleanCurrency(value) {
            if (!value) return 0;

            // Remove ₱, commas, and spaces
            const cleaned = value.toString().replace(/[^0-9.]/g, '');

            return parseFloat(cleaned);
        },


        generateYearOptions() {
            const currentYear = new Date().getFullYear();
            const startYear = currentYear - 8;
            const endYear = currentYear + 2;

            this.yearOptions = [];

            for (let year = endYear; year >= startYear; year--) {
                this.yearOptions.push({ value: year, text: year.toString() });
            }

            // Set default selected year to current year
            this.selectedYear = this.yearOptions.find(option => option.value === currentYear);
            this.form.year = currentYear;
        },

        show(){
            this.editable = false;
            this.form.reset();
            this.selectedYear = this.yearOptions.find(option => option.value === new Date().getFullYear());
            this.showModal = true;
        },

        edit(data){
            this.editable= true;
            this.form.reset();
            this.form.id = data.id;
            this.form.title = data.title;
            this.form.code = data.code;
            this.form.allocated_budget = data.allocated_budget;
            this.$refs.amountComponent.emitValue(this.form.allocated_budget);
            this.form.year = data.year;
            this.form.end_user_ids = data.end_users.map(e => e.end_user_id);
            this.form.app_type_id = data.app_type?.id;
            this.form.mode_of_procurement_id = data.mode_of_procurement.id;
            this.showModal = true;
        },
      
        hide(){
             this.$refs.amountComponent.emitValue(0);
             this.form.allocated_budget = 0;
            this.form.reset();
            this.form.errors = {};
            this.showModal = false;
        },

        savePAP(data){
            if(this.editable){
                this.form.put(`/faims/procurement-codes/`+data.id,{
                    preserveScroll: true,
                    onSuccess: (response) => {
                        this.$emit('update', true);
                        this.form.reset();
                        this.hide();
                    },
                    onError: (errors) => {
                        // Errors are automatically handled by form.errors
                    }
                });
            }else{
                this.form.post('/faims/procurement-codes',{
                preserveScroll: true,
                onSuccess: (response) => {
                    this.$emit('add',true);
                    this.hide();
                },
                onError: (errors) => {
                    console.log('Create errors:', errors);
                    // Errors are automatically handled by form.errors
                }
            });
            }
        },


       
    }
}
</script>