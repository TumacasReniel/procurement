<template>
    <b-modal v-model="showModal" style="--vz-modal-width: 700px;" header-class="p-3 bg-light" title="Assign Designated Official" class="v-modal-custom" modal-class="zoomIn" centered no-close-on-backdrop>
        <form class="customform">
            <BRow class="p-3">
                <BCol lg="12">
                    <div class="alert alert-danger alert-dismissible alert-label-icon label-arrow fade show material-shadow fs-12" role="alert">
                        <i class="ri-alert-line label-icon"></i>
                        <strong>Notice:</strong>
                        Assigning a <strong>designated official</strong> will permanently link this employee to the selected position until changed or vacated.
                    </div>
                </BCol>
                <BCol lg="12" class="mt-4">
                    <form class="app-search d-none d-md-block mb-n3" style="margin-top: -33px;">
                        <div class="position-relative">
                            <input type="text" class="form-control" placeholder="Search Employee" autocomplete="off" :id="`search-options-${_uid}`" />
                            <span class="mdi mdi-magnify search-widget-icon"></span>
                            <span @click="clear()" class="mdi mdi-close-circle search-widget-icon search-widget-icon-close d-none" :id="`search-close-options-${_uid}`"></span>
                        </div>
                        <div class="dropdown-menu dropdown-menu-lg" :id="`search-dropdown-${_uid}`">
                            <SimpleBar data-simplebar >
                                <div class="notification-list">
                                    <b-link @click="chooseUser(list)" v-for="(list, index) of names" :key="index" class="d-flex dropdown-item notify-item py-2">
                                        <img :src="list.avatar" class="me-3 rounded-circle avatar-xs" alt="user-pic" />
                                        <div class="flex-1">
                                            <h6 class="m-0">{{ list.name}}</h6>
                                            <span class="fs-11 mb-0 text-muted">{{list.position}}</span>
                                        </div>
                                    </b-link>
                                </div>
                            </SimpleBar>
                        </div>
                    </form>
                </BCol>
                <BCol lg="12" class="mt-n1 mb-n2" v-if="user">
                    <hr class="text-muted"/>
                </BCol>
                <BCol md v-if="user">
                    <BRow class="align-items-center g-1">
                        <BCol md="auto">
                            <div style="height: 3.5rem; width: 3.5rem;">
                                <div class="avatar-title bg-white rounded-circle">
                                    <img :src="user.avatar" alt="" class="avatar-sm rounded-circle">
                                </div>
                            </div>
                        </BCol>
                        <BCol md>
                            <div class="ms-2">
                                <h4 class="fs-16 text-uppercase text-primary fw-semibold mb-0 mt-1">{{ user.name }}</h4>
                                <div class="hstack gap-3 flex-wrap">
                                    <div class="text-muted">{{user.position}}</div>
                                </div>
                            </div>
                        </BCol>
                    </BRow>
                </BCol>
                <BCol lg="12" class="mt-n1 mb-n3" v-if="user">
                    <hr class="text-muted"/>
                </BCol>
                <BCol lg="12" style="margin-top: 13px; margin-bottom: -10px;">
                    <div class="d-flex position-relative">
                        <div class="flex-shrink-0 fs-12" :class="(form.errors.is_oic) ? 'text-danger' : ''">
                           Is the employee designated as Officer-in-Charge (OIC)?
                        </div>
                        <div class="flex-grow-1 ms-2"></div>
                        <div class="flex-shrink-0">
                            <div class="d-inline-block">
                                <div class="custom-control custom-radio mb-3 ms-4">
                                    <input type="radio" id="customRadio1" class="custom-control-input me-2" @input="handleInput('is_active')" :value="true" v-model="form.is_oic">
                                    <label class="custom-control-label fs-12 fw-normal" for="customRadio1">Yes</label>
                                </div>
                            </div>
                            <div class="d-inline-block">
                                <div class="custom-control custom-radio mb-3 ms-4">
                                    <input type="radio" id="customRadio1" class="custom-control-input me-2" @input="handleInput('is_active')" :value="false" v-model="form.is_oic">
                                    <label class="custom-control-label fs-12 fw-normal" for="customRadio1">No</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </BCol>
                <BCol v-if="form.is_oic" lg="12"><hr class="text-muted mt-2 mb-0"/></BCol>
                <BCol v-if="form.is_oic" lg="6" class="mt-2">
                    <InputLabel for="name" value="Start Date" :message="form.errors.start_at"/>
                    <TextInput id="name" v-model="form.start_at" type="date" class="form-control" placeholder="Please enter contact no." @input="handleInput('start_at')" :light="true"/>
                </BCol>
                <BCol v-if="form.is_oic" lg="6" class="mt-2">
                    <InputLabel for="name" value="End Date" :message="form.errors.end_at"/>
                    <TextInput id="name" v-model="form.end_at" type="date" class="form-control" placeholder="Please enter contact no." @input="handleInput('end_at')" :light="true"/>
                </BCol>
            </BRow>
        </form>
        <template v-slot:footer>
            <b-button @click="hide()" variant="light" block>Cancel</b-button>
            <b-button @click="submit('ok')" variant="primary" :disabled="form.processing" block>Submit</b-button>
        </template>
    </b-modal>
</template>
<script>
import _ from 'lodash';
import { useForm } from '@inertiajs/vue3';
import Multiselect from "@vueform/multiselect";
import InputLabel from '@/Shared/Components/Forms/InputLabel.vue';
import TextInput from '@/Shared/Components/Forms/TextInput.vue';
export default {
    components: {InputLabel, TextInput, Multiselect },
    props: ['divisions'],
    data(){
        return {
            form: useForm({
                signatory_id: null,
                user_id: null,
                is_oic: null,
                start_at: null,
                end_at: null,
                option: 'designate'
            }),
            keyword: null,
            names: [],
            user: null,
            selected: null,
            showModal: false
        }
    },
     mounted() {
        this.isCustomDropdown();
    },
    methods: { 
        show(data){
            console.log(data, 88);
            this.form.reset();
            this.user = null;
            this.names = [];
            this.selected = data;
            this.form.signatory_id = this.selected.signatory.id;
            this.showModal = true;
        },
        checkSearchStr: _.debounce(function (string) {
            this.keyword = string;
            this.search();
        }, 500),
        search(){
            axios.get('/search', {
                params: {
                    keyword: this.keyword,
                    option: 'users'
                }
            })
            .then(response => {
                if(response){ 
                    this.names = response.data; 
                }
            })
            .catch(err => console.log(err));
        },
        chooseUser(data){
            this.user = data;
            this.form.user_id = data.value;
            this.keyword = null;
            document.getElementById("search-options").value = "";
            document.getElementById("search-options").focus();
        }, 
        submit(){
            this.form.post('/signatories',{
                preserveScroll: true,
                onSuccess: (response) => {
                    this.form.reset();
                    this.hide();
                    this.$emit('success',true);
                },
            });
        },
        handleInput(field) {
            this.form.errors[field] = false;
        },
        hide(){
            this.editable = false;
            this.showModal = false;
        },
        isCustomDropdown() {
            const closeId = `search-close-options-${this._uid}`;
            const dropdownId = `search-dropdown-${this._uid}`;
            const inputId = `search-options-${this._uid}`;

            const searchOptions = document.getElementById(closeId);
            const dropdown = document.getElementById(dropdownId);
            const searchInput = document.getElementById(inputId);

            if (!searchInput) return;

            searchInput.addEventListener("focus", () => {
                if (searchInput.value.length > 0) {
                dropdown.classList.add("show");
                searchOptions.classList.remove("d-none");
                }
            });

            searchInput.addEventListener("keyup", () => {
                if (searchInput.value.length > 0) {
                dropdown.classList.add("show");
                searchOptions.classList.remove("d-none");
                this.checkSearchStr(searchInput.value);
                } else {
                dropdown.classList.remove("show");
                searchOptions.classList.add("d-none");
                }
            });

            searchOptions.addEventListener("click", () => {
                searchInput.value = "";
                dropdown.classList.remove("show");
                searchOptions.classList.add("d-none");
            });

            document.body.addEventListener("click", (e) => {
                if (e.target.id !== inputId) {
                dropdown.classList.remove("show");
                searchOptions.classList.add("d-none");
                }
            });
            }
    }
}
</script>
<style scoped>
    .dropdown-menu-lg {
        width: 95%;
    }
</style>