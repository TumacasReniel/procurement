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
                                    <i class="ri-pin-distance-fill text-primary fs-24"></i>
                                </span>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="mb-0 fs-14"><span class="text-body">My Requests</span></h5>
                            <p class="text-muted text-truncate-two-lines fs-12">A detailed list of submitted travel order requests including destination, purpose, schedule, and status.</p>
                        </div>
                        <div class="flex-shrink-0" style="width: 45%;">
                            
                        </div>
                    </div>
                </div>
                <div class="car-body bg-white border-bottom shadow-none">
                    <b-row class="mb-2 ms-1 me-1" style="margin-top: 12px;">
                        <b-col lg>
                            <div class="input-group mb-1">
                                <span class="input-group-text"> <i class="ri-search-line search-icon"></i></span>
                                <input type="text" v-model="filter.keyword" placeholder="Search Request" class="form-control" style="width: 40%;">
                                <template v-if="filter.type == 156">
                                    <Multiselect class="white" style="width: 15%;" :options="travel_dropdowns.modes" v-model="filter.mode" label="name" :searchable="true" placeholder="Select Travel Mode" />
                                    <Multiselect class="white" style="width: 15%;" :options="travel_dropdowns.expenses" v-model="filter.expense" label="name" :searchable="true" placeholder="Select Expense Type" />
                                </template>
                                <template v-else-if="filter.type == 157">
                                    
                                </template>
                                <template v-else-if="filter.type == 158">
                                    <!-- <Multiselect class="white" style="width: 15%;" :options="leave_dropdowns.leaves" v-model="filter.keave" label="name" :searchable="true" placeholder="Select Travel Mode" /> -->
                                    <Multiselect class="white" style="width: 15%;" v-model="filter.leave" :groups="true" :options="leave_dropdowns.leaves" label="name" placeholder="Select type"/>
                                </template>
                                <Multiselect class="white" style="width: 15%;" :options="dropdowns.statuses" v-model="filter.status" label="name" :searchable="true" placeholder="Select Status" />
                                <span @click="refresh()" class="input-group-text" v-b-tooltip.hover title="Refresh" style="cursor: pointer;"> 
                                    <i class="bx bx-refresh search-icon"></i>
                                </span>
                                <b-button type="button" variant="primary" @click="openCreate">
                                    <i class="ri-add-circle-fill align-bottom me-1"></i> Create
                                </b-button>
                            </div>
                        </b-col>
                    </b-row>
                </div>
                <div class="card bg-white border-bottom shadow-none" no-body>
                    <div class="d-flex">
                        <div class="flex-grow-1">
                            <ul class="nav nav-tabs nav-tabs-custom nav-primary fs-12" role="tablist">
                                <li class="nav-item">
                                    <BLink @click="viewStatus(null,null)" class="nav-link py-3 active" data-bs-toggle="tab" role="tab" aria-selected="true">
                                    <i class="ri-apps-2-line me-1 align-bottom"></i> My Request
                                    </BLink>
                                </li>
                                <li class="nav-item" v-for="(list,index) in dropdowns.requests" v-bind:key="index">
                                    <BLink @click="viewStatus(index,list.value)" class="nav-link py-3" :class="(this.index == index) ? 'text-secondary active' : ''" data-bs-toggle="tab" role="tab" aria-selected="false">
                                        <i :class="icons[index]" class="me-1 align-bottom"></i>
                                        {{ list.name }} <BBadge v-if="counts[index] > 0" :class="list.color" class="align-middle ms-1">{{counts[index]}}</BBadge>
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
                <div class="card-body bg-white rounded-bottom">
                    <div class="table-responsive table-card" style="margin-top: -39px; height: calc(100vh - 465px); overflow: auto;">
                        <table class="table align-middle table-striped table-centered mb-0">
                            <thead class="table-light thead-fixed">
                                <tr class="fs-11">
                                    <th style="width: 3%;" class="text-center">#</th>
                                    <th>Code</th>
                                    <th v-if="!filter.type" style="width: 14%;" class="text-center">Type</th>
                                    <th v-else-if="selectedRequestTypeName === 'Travel Order'" style="width: 14%;" class="text-center">Mode</th>
                                    <th v-else-if="selectedRequestTypeName === 'Leave Form'" style="width: 14%;" class="text-center">Type</th>
                                    <th v-else-if="selectedRequestTypeName === 'Vehicle Reservation'" style="width: 14%;" class="text-center">Vehicle</th>
                                    <th v-else-if="isProcurementType" style="width: 14%;" class="text-center">Type</th>
                                    <th v-else style="width: 14%;" class="text-center">Type</th>
                                    <th style="width: 10%;" class="text-center">Personnel</th>
                                    <th style="width: 15%;" class="text-center">Dates</th>
                                    <th style="width: 15%;" class="text-center">Date Filed</th>
                                    <th style="width: 10%;" class="text-center">Status</th>
                                    <th style="width: 5%;"></th>
                                </tr>
                            </thead>
                            <tbody class="table-white fs-12">
                                <tr v-for="(list,index) in lists" v-bind:key="index" >
                                    <td class="text-center">{{ (meta.current_page - 1) * meta.per_page + index + 1 }}.</td>
                                    <td>
                                        <h5 class="fs-13 mb-0 fw-semibold text-primary">{{list.code }}</h5>
                                        <!-- <p class="fs-12 text-muted mb-0">{{list.code}}</p> -->
                                    </td>
                                    <td class="text-center" v-if="!filter.type">
                                        <!-- <span :class="'badge bg-primary'">{{list.type}}</span> -->
                                        <span v-if="list.type == 'Vehicle Reservation'" class="badge bg-secondary-subtle text-secondary">{{list.type}}</span>
                                        <span v-else-if="list.type == 'Travel Order'" class="badge bg-success-subtle text-success">{{list.type}}</span>
                                        <span v-else-if="list.type == 'Leave Form'" class="badge bg-danger-subtle text-danger">{{list.type}}</span>
                                        <span v-else-if="list.type == 'Render Overtime Service'" class="badge bg-info-subtle text-info">{{list.type}}</span>
                                        <span v-else-if="list.type == 'Procurement'" class="badge bg-primary-subtle text-primary">{{list.type}}</span>
                                    </td>
                                    <td class="text-center" v-else>
                                        <span :class="'badge bg-primary'">{{list.subtype}}</span>
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
                                        <!-- <Link :href="`/requests/${list.link}`">
                                            <b-button variant="soft-info" class="me-1" v-b-tooltip.hover title="View" size="sm">
                                                <i class="ri-eye-fill align-bottom"></i>
                                            </b-button>
                                        </Link> -->
                                        <div class="d-flex gap-3 justify-content-center"> 
                                             <div class="dropdown">
                                                <BDropdown variant="link" toggle-class="btn btn-light btn-sm dropdown" no-caret menu-class="dropdown-menu-end" :offset="{ alignmentAxis: -130, crossAxis: 0, mainAxis: 10 }"> 
                                                    <template #button-content> 
                                                        <i class="ri-more-fill"></i>
                                                    </template>
                                                    <li>
                                                        <Link :href="list.href || `/requests/${list.link}`" class="dropdown-item d-flex align-items-center" role="button">
                                                            <i class="ri-eye-fill me-2"></i> View
                                                        </Link>
                                                    </li>
                                                    <li>
                                                        <a @click="openDetail(list,index)" class="dropdown-item d-flex align-items-center" role="button">
                                                            <i class="ri-information-fill me-2"></i> Details
                                                        </a>
                                                    </li>
                                                    <template v-if="list.key">
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <a @click="openPrint(list.key,index,list.type)" class="dropdown-item d-flex align-items-center" role="button">
                                                                <i class="ri-printer-fill me-2"></i> Print
                                                            </a>
                                                            <a @click="openPrint(list.key,index)" class="dropdown-item d-flex align-items-center" role="button">
                                                                <i class="ri-download-cloud-fill me-2"></i> Download
                                                            </a>
                                                        </li>
                                                    </template>
                                               </BDropdown>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer">
                    <Pagination class="ms-2 me-2 mt-n1" v-if="meta" @fetch="fetch" :lists="lists.length" :links="links" :pagination="meta" />
                </div>
            </div>
        </div>
    </BRow>
    <Create @update="fetch()"
    :vehicle_dropdowns="vehicle_dropdowns" 
    :travel_dropdowns="travel_dropdowns"
    :leave_dropdowns="leave_dropdowns"
    :procurement_dropdowns="procurement_dropdowns"
    ref="create"/>
</template>
<script>
import _ from 'lodash';
import Create from './Modals/Create.vue';
import Multiselect from "@vueform/multiselect";
import PageHeader from '@/Shared/Components/PageHeader.vue';
import Pagination from "@/Shared/Components/Pagination.vue";
export default {
    components: { PageHeader, Pagination, Multiselect, Create },
    props: ['counts','leave_dropdowns','travel_dropdowns','vehicle_dropdowns','procurement_dropdowns','dropdowns'],
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
            icons: ['ri-flight-takeoff-fill','ri-car-fill','ri-calendar-2-fill','ri-alarm-fill','ri-shopping-bag-3-fill'],
            index: null,
            units: []
        }
    },
    computed: {
        selectedRequestTypeName() {
            if (!this.filter.type || !Array.isArray(this.dropdowns?.requests)) {
                return null;
            }

            const selected = this.dropdowns.requests.find((item) => Number(item.value) === Number(this.filter.type));
            return selected?.name || null;
        },
        isProcurementType() {
            return ['Procurement', 'Purchase Request', 'Procurement Request'].includes(this.selectedRequestTypeName);
        }
    },
    watch: {
        "filter.keyword"(newVal){
            this.checkSearchStr(newVal)
        },
        "filter.status"(newVal){
            this.fetch();
        },
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
            page_url = page_url || '/requests';
            axios.get(page_url,{
                params : {
                    keyword: this.filter.keyword,
                    type: this.filter.type,
                    status: this.filter.status,
                    expense: this.filter.expense,
                    mode: this.filter.mode,
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
        viewStatus(index,type){
            this.index = index;
            this.filter.type = type;
            this.fetch();
        },
        openCreate(){
            this.$refs.create.show();
        },
        openPrint(key,index,type){
            this.index = index;
            window.open('/requests?option=print&type='+type+'&key='+key);
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
