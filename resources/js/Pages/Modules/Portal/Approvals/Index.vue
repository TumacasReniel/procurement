<template>
    <Head title="Requests"/>
    <PageHeader title="Requests" pageTitle="List" />
    <BRow>
        <div class="col-md-12">
            <div class="card bg-light-subtle shadow-none border">
                <div class="card-header bg-light-subtle">
                    <div class="d-flex mb-n3">
                        <div class="flex-shrink-0 me-3">
                            <div style="height:2.5rem;width:2.5rem;">
                                <span class="avatar-title bg-primary-subtle rounded p-2 mt-n1">
                                    <i class="ri-ball-pen-fill text-primary fs-24"></i>
                                </span>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="mb-0 fs-14"><span class="text-body">For Approval</span></h5>
                            <p class="text-muted text-truncate-two-lines fs-12">A detailed list of submitted requests awaiting recommendation, review, or approval, including procurement requests.</p>
                        </div>
                        <div class="flex-shrink-0" style="width: 15%;">
                        </div>
                    </div>
                </div>
                <div class="car-body bg-white border-bottom shadow-none">
                    <b-row class="mb-2 ms-1 me-1" style="margin-top: 12px;">
                        <b-col lg>
                            <div class="input-group mb-1">
                                <span class="input-group-text"> <i class="ri-search-line search-icon"></i></span>
                                <input type="text" v-model="filter.keyword" placeholder="Search Request" class="form-control" style="width: 40%;">
                                <span @click="refresh()" class="input-group-text" v-b-tooltip.hover title="Refresh" style="cursor: pointer;"> 
                                    <i class="bx bx-refresh search-icon"></i>
                                </span>
                                <b-button type="button" variant="primary" @click="openCreate">
                                    <i class="ri-search-line search-icon"></i>
                                </b-button>
                            </div>
                        </b-col>
                    </b-row>
                </div>
                <div class="card bg-white border-bottom shadow-none" no-body>
                    <div class="d-flex">
                        <div class="flex-grow-1">
                            <ul class="nav nav-tabs nav-tabs-custom nav-primary fs-12" role="tablist">
                                <li class="nav-item" v-if="designation == 44">
                                    <BLink @click="viewStatus(null)" class="nav-link py-3 active" data-bs-toggle="tab" role="tab" aria-selected="true">
                                    <i class="ri-apps-2-line me-1 align-bottom"></i> For Recommendation
                                    </BLink>
                                </li>
                                <li class="nav-item" v-if="designation == 43 || !designation">
                                    <BLink @click="viewStatus(null)" class="nav-link py-3 active" data-bs-toggle="tab" role="tab" aria-selected="true">
                                    <i class="ri-apps-2-line me-1 align-bottom"></i> For Approval
                                    </BLink>
                                </li>
                                <!-- <li class="nav-item">
                                    <BLink @click="viewStatus(25)" class="nav-link py-3" data-bs-toggle="tab" role="tab" aria-selected="true">
                                    <i class="ri-record-circle-fill me-1 align-bottom"></i>
                                        Recommended <BBadge v-if="count[0] > 0" class="text-secondary align-middle ms-1">{{count[0]}}</BBadge>
                                    </BLink>
                                </li>
                                <li class="nav-item">
                                    <BLink @click="viewStatus(26)" class="nav-link py-3" data-bs-toggle="tab" role="tab" aria-selected="true">
                                    <i class="ri-checkbox-circle-fill me-1 align-bottom"></i> Approved <BBadge v-if="count[2] > 0" class="bg-success align-middle ms-1">{{count[2]}}</BBadge>
                                    </BLink>
                                </li>
                                <li class="nav-item">
                                    <BLink @click="viewStatus(30)" class="nav-link py-3" data-bs-toggle="tab" role="tab" aria-selected="true">
                                    <i class="ri-close-circle-fill me-1 align-bottom"></i> Disapproved <BBadge v-if="count[2] > 0" class="bg-danger align-middle ms-1">{{count[2]}}</BBadge>
                                    </BLink>
                                </li> -->
                                <li class="nav-item" v-for="(list,index) in tabs" v-bind:key="index">
                                    <BLink @click="viewStatus(index,list.value)" class="nav-link py-3" :class="(this.index == index) ? list.text+' active' : ''" data-bs-toggle="tab" role="tab" aria-selected="false">
                                        <i :class="list.icon" class="me-1 align-bottom"></i>
                                        {{ list.name }} <BBadge v-if="count[index] > 0" :class="list.bg" class="align-middle ms-1">{{count[index]}}</BBadge>
                                    </BLink>
                                </li>
                            </ul>
                        
                        </div>
                        <div class="flex-shrink-0">
                            <div class="d-flex flex-wrap gap-2 mt-3">
                                
                            </div>
                        </div>
                    </div>
                </div>
                <div v-if="lists.length > 0" class="card-body bg-white rounded-bottom">
                    <div class="table-responsive table-card" style="margin-top: -39px; height: calc(100vh - 465px); overflow: auto;">
                        <table class="table align-middle table-striped table-centered mb-0">
                            <thead class="table-light thead-fixed">
                                <tr class="fs-11">
                                    <th style="width: 3%;" class="text-center">#</th>
                                    <th></th>
                                    <th style="width: 15%;" class="text-center">Personnel</th>
                                    <th style="width: 17%;" class="text-center">Dates</th>
                                    <th style="width: 17%;" class="text-center">Date Filed</th>
                                    <th style="width: 10%;" class="text-center">Status</th>
                                    <th style="width: 5%;"></th>
                                </tr>
                            </thead>
                            <tbody class="table-white fs-12">
                                <tr v-for="(list,index) in lists" v-bind:key="index" >
                                    <td class="text-center">{{ (meta.current_page - 1) * meta.per_page + index + 1 }}.</td>
                                    <td>
                                        <h5 class="fs-13 mb-0 fw-semibold text-uppercase text-primary">{{list.type }} "{{ list.code }}"</h5>
                                        <p class="fs-12 text-muted mb-0">{{list.request_code}}</p>
                                    </td>
                                    <td class="text-center align-middle">
                                        <div class="avatar-group  d-inline-flex justify-content-center">
                                            <div class="avatar-group-item material-shadow"  v-for="(list, index) of list.tags" :key="index">
                                                <!-- <a href="javascript: void(0);" class="d-inline-block" 
                                                v-b-tooltip.hover="{title: list.name,placement: 'top',customClass: 'my-tooltip-class'}"> -->
                                                    <img :src="list.avatar" alt="" class="rounded-circle avatar-xs">
                                                <!-- </a> -->
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center">{{formatDateRange(list.start, list.end)}}</td>
                                    <td class="text-center">{{ list.created_at }}</td>
                                    <td class="text-center">
                                        <span :class="'badge '+list.status.bg">{{list.status.name}}</span>
                                    </td>
                                    <td class="text-end">
                                        <Link :href="list.href || `/approvals/${list.link}`">
                                            <b-button variant="soft-info" class="me-1" v-b-tooltip.hover title="View" size="sm">
                                                <i class="ri-eye-fill align-bottom"></i>
                                            </b-button>
                                        </Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div v-else class="card-body bg-white rounded-bottom d-flex justify-content-center align-items-center" style="margin-top: -39px; height: calc(100vh - 394px);">
                    <div class="w-100" style="max-width: 600px;">
                        <div class="alert alert-warning material-shadow text-center" role="alert" v-if="filter.status">
                            <strong>No reviewed requests found.</strong> <br /> There are no requests that have been <b>approved</b> or <b>recommended</b>.
                        </div>
                        <div class="alert alert-danger material-shadow text-center" role="alert" v-else>
                            <strong>No requests available.</strong> <br /> There are no submissions requiring recommendation, approval, or review at this time.
                        </div>
                    </div>
                </div>
                <div class="card-footer" v-if="lists.length > 0">
                    <Pagination class="ms-2 me-2 mt-n1" v-if="meta" @fetch="fetch" :lists="lists.length" :links="links" :pagination="meta" />
                </div>
            </div>
        </div>
    </BRow>
</template>
<script>
import _ from 'lodash';
import Multiselect from "@vueform/multiselect";
import PageHeader from '@/Shared/Components/PageHeader.vue';
import Pagination from "@/Shared/Components/Pagination.vue";
export default {
    components: { PageHeader, Pagination, Multiselect },
    props: ['count'],
    data(){
        return {
            currentUrl: window.location.origin,
            lists: [],
            meta: {},
            links: {},
            filter: {
                keyword: null,
                type: null,
                status: null,
                mode: null,
                expense: null,
                leave: null
            },
            designation: this.$page.props.user?.data?.signatory?.designationable?.designation_id ?? null,
            icons: ['ri-flight-takeoff-fill','ri-car-fill','ri-calendar-2-fill'],
            index: null,
            units: [],
            tabs: [
                { value: 25, name: 'Recommended', bg: 'bg-secondary', text: 'text-secondary', icon: 'ri-record-circle-line'},
                { value: 26, name: 'Approved', bg: 'bg-success', text: 'text-success', icon: 'ri-checkbox-circle-line'},
                { value: 30, name: 'Disapproved', bg: 'bg-danger', text: 'text-danger', icon: 'ri-close-circle-line'}
            ]
        }
    },
    watch: {
        "filter.keyword"(newVal){
            this.checkSearchStr(newVal)
        },
        // "filter.status"(newVal){
        //     this.fetch();
        // },
        "filter.mode"(newVal){
            this.fetch();
        },
        "filter.expense"(newVal){
            this.fetch();
        }
    },
    created(){
        this.fetch();
    },
    methods: {
        checkSearchStr: _.debounce(function(string) {
            this.fetch();
        }, 300),
        fetch(page_url){
            page_url = page_url || '/approvals';
            axios.get(page_url,{
                params : {
                    keyword: this.filter.keyword,
                    status: this.filter.status,
                    count: 10, 
                    option: 'lists'
                }
            })
            .then(response => {
                if(response){
                    this.lists = response.data.data;
                    this.meta = response.data.meta;
                    this.links = response.data.links;          
                }
            })
            .catch(err => console.log(err));
        },
        formatDateRange(start, end) {
            const startDate = new Date(start);
            const endDate = new Date(end);

            const options = { month: 'long', day: 'numeric' };
            const startStr = startDate.toLocaleDateString('en-US', options);
            const endStr = endDate.toLocaleDateString('en-US', { day: 'numeric' });

            if (start === end) {
            return startDate.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
            }

            const year = startDate.getFullYear(); // assume same year
            return `${startStr}-${endStr}, ${year}`;
        },
        viewStatus(index,status){
            this.index = index;
            this.filter.status = status;
            this.fetch();
        },
        openCreate(){
            this.$refs.create.show();
        },
        refresh(){
            this.filter.expense = null;
            this.filter.mode = null;
            this.filter.keyword = null;
            this.fetch();  
        }
    }
}
</script>
