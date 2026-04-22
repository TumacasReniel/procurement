<template>
    <b-modal
        v-model="showModal"
        style="--vz-modal-width: 700px;"
        header-class="p-3 bg-light"
        title="Add IAR Member"
        class="v-modal-custom"
        modal-class="zoomIn"
        centered
        no-close-on-backdrop
    >
        <form class="customform" @submit.prevent="submit">
            <BRow class="p-3">
                <BCol lg="12">
                    <div class="alert alert-info alert-dismissible alert-label-icon label-arrow fade show material-shadow fs-12" role="alert">
                        <i class="ri-information-line label-icon"></i>
                        <strong>Notice:</strong>
                        Adding an IAR member creates a new committee slot and immediately assigns the selected employee to it.
                    </div>
                </BCol>
                <BCol lg="12" class="mt-4">
                    <form class="app-search d-none d-md-block mb-n3" style="margin-top: -33px;" @submit.prevent>
                        <div class="position-relative">
                            <input :id="inputId" type="text" class="form-control" placeholder="Search Employee" autocomplete="off" />
                            <span class="mdi mdi-magnify search-widget-icon"></span>
                            <span :id="closeId" @click="clearSearch" class="mdi mdi-close-circle search-widget-icon search-widget-icon-close d-none"></span>
                        </div>
                        <div class="dropdown-menu dropdown-menu-lg" :id="dropdownId">
                            <SimpleBar data-simplebar>
                                <div class="notification-list">
                                    <b-link
                                        v-for="(list, index) of names"
                                        :key="index"
                                        @click="chooseUser(list)"
                                        class="d-flex dropdown-item notify-item py-2"
                                    >
                                        <img :src="list.avatar" class="me-3 rounded-circle avatar-xs" alt="user-pic" />
                                        <div class="flex-1">
                                            <h6 class="m-0">{{ list.name }}</h6>
                                            <span class="fs-11 mb-0 text-muted">{{ list.position }}</span>
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
                                    <div class="text-muted">{{ user.position }}</div>
                                </div>
                            </div>
                        </BCol>
                    </BRow>
                </BCol>
            </BRow>
        </form>
        <template v-slot:footer>
            <b-button @click="hide()" variant="light" block>Cancel</b-button>
            <b-button @click="submit()" variant="primary" :disabled="form.processing || !form.user_id" block>
                Add Member
            </b-button>
        </template>
    </b-modal>
</template>
<script>
import _ from 'lodash';
import { useForm } from '@inertiajs/vue3';

export default {
    data() {
        return {
            form: useForm({
                user_id: null,
                option: 'create_iar_member',
            }),
            dropdownBound: false,
            keyword: null,
            names: [],
            user: null,
            showModal: false,
        };
    },
    computed: {
        closeId() {
            return `search-close-options-${this._uid}`;
        },
        dropdownId() {
            return `search-dropdown-${this._uid}`;
        },
        inputId() {
            return `search-options-${this._uid}`;
        },
    },
    methods: {
        show() {
            this.form.reset();
            this.form.clearErrors();
            this.keyword = null;
            this.names = [];
            this.user = null;
            this.showModal = true;
            this.$nextTick(() => {
                this.isCustomDropdown();
                this.clearSearchInput();
            });
        },
        checkSearchStr: _.debounce(function (string) {
            this.keyword = string;
            this.search();
        }, 500),
        search() {
            axios.get('/search', {
                params: {
                    keyword: this.keyword,
                    option: 'users',
                },
            })
            .then((response) => {
                if (response) {
                    this.names = response.data;
                }
            })
            .catch((err) => console.log(err));
        },
        chooseUser(data) {
            this.user = data;
            this.form.user_id = data.value;
            this.keyword = null;
            this.names = [];
            this.clearSearchInput();
        },
        submit() {
            this.form.post('/signatories', {
                preserveScroll: true,
                onSuccess: () => {
                    this.hide();
                    this.$emit('success', true);
                },
            });
        },
        clearSearch() {
            this.form.user_id = null;
            this.user = null;
            this.names = [];
            this.keyword = null;
            this.clearSearchInput();
        },
        clearSearchInput() {
            const input = document.getElementById(this.inputId);
            const dropdown = document.getElementById(this.dropdownId);
            const close = document.getElementById(this.closeId);

            if (input) {
                input.value = '';
            }

            if (dropdown) {
                dropdown.classList.remove('show');
            }

            if (close) {
                close.classList.add('d-none');
            }
        },
        hide() {
            this.form.reset();
            this.form.clearErrors();
            this.keyword = null;
            this.names = [];
            this.user = null;
            this.showModal = false;
            this.clearSearchInput();
        },
        isCustomDropdown() {
            if (this.dropdownBound) {
                return;
            }

            const searchOptions = document.getElementById(this.closeId);
            const dropdown = document.getElementById(this.dropdownId);
            const searchInput = document.getElementById(this.inputId);

            if (!searchOptions || !dropdown || !searchInput) {
                return;
            }

            this.dropdownBound = true;

            searchInput.addEventListener('focus', () => {
                if (searchInput.value.length > 0) {
                    dropdown.classList.add('show');
                    searchOptions.classList.remove('d-none');
                }
            });

            searchInput.addEventListener('keyup', () => {
                if (searchInput.value.length > 0) {
                    dropdown.classList.add('show');
                    searchOptions.classList.remove('d-none');
                    this.checkSearchStr(searchInput.value);
                } else {
                    dropdown.classList.remove('show');
                    searchOptions.classList.add('d-none');
                    this.names = [];
                }
            });

            searchOptions.addEventListener('click', () => {
                this.clearSearch();
            });

            document.body.addEventListener('click', (e) => {
                if (e.target.id !== this.inputId) {
                    dropdown.classList.remove('show');
                    searchOptions.classList.add('d-none');
                }
            });
        },
    },
};
</script>
<style scoped>
.dropdown-menu-lg {
    width: 95%;
}
</style>
