<template>

    <b-modal v-model="showModal" hide-footer  header-class="p-3 bg-light" title="View Designation" class="v-modal-custom" modal-class="zoomIn" centered no-close-on-backdrop>
        <BRow v-if="selected">
            <BCol md>
                <div class="row justify-content-center mb-4">
                    <div class="card-body rounded-4 text-center">
                        <div class="mb-2 mx-auto">
                            <img :src="selected.avatar" alt="" id="candidate-img" class="img-thumbnail avatar-sm rounded-circle shadow-none">
                        </div>
        
                        <h5 v-if="selected.user" class="fs-13 mb-0 text-primary">{{selected.user.name}}</h5>
                        <h5 v-else class="fs-13 text-warning mb-0">Not Assigned</h5>
                        <p class="fs-12 text-muted mb-0">{{selected.designation}}</p>

                        <div class="d-flex gap-2 justify-content-center mt-2">
                            <button @click="openAssignment(selected)" type="button" class="btn avatar-xs p-0 material-shadow-none" v-b-tooltip.hover title="Set Designation">
                                <span class="avatar-title rounded-circle bg-light text-body">
                                    <i class="ri-edit-line"></i>
                                </span>
                            </button>
        
                            <button @click="openSignatory(selected)" type="button" class="btn avatar-xs p-0 material-shadow-none" v-b-tooltip.hover title="Assign Signatory">
                                <span class="avatar-title rounded-circle bg-light text-body">
                                    <i class="ri-mark-pen-fill"></i>
                                </span>
                            </button>
                            <button type="button" class="btn avatar-xs p-0 material-shadow-none" v-b-tooltip.hover title="View">
                                <span class="avatar-title rounded-circle bg-light text-body">
                                    <i class="ri-dribbble-fill"></i>
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </BCol>
            <BCol lg="12" class="mt-n4 mb-0">
                <hr class="text-muted"/>
            </BCol>  
            <div class="col-md-12" v-if="selected.oic">
                <div class="d-flex border border-dashed rounded p-3">
                    <div class="flex-shrink-0 avatar-xs align-self-center me-3">
                        <div class="avatar-title bg-light rounded-circle fs-16 text-primary">
                            <img :src="selected.oic.avatar" alt="" id="candidate-img" class="img-thumbnail rounded-circle shadow-none">
                        </div>
                    </div>
                    <div class="flex-grow-1 overflow-hidden">
                        <p class="mb-0 text-muted fs-12">Offince-in-Charge :</p>
                        <h6 class="fw-semibold fs-12 mb-0">{{ selected.oic.name }}</h6>
                    </div>
                </div>
                <hr class="text-muted"/>
            </div>
            <BCol lg="12" v-if="selected?.signatory?.schedules?.length == 0">
                <div @click="openSignatory()" style="cursor: pointer;" class="alert alert-light alert-dismissible bg-light text-body alert-label-icon fade show material-shadow" role="alert">
                    <i class="ri-mark-pen-fill label-icon"></i>No signatory assigned (using default)
                </div>
            </BCol>  
            <BCol v-else lg="12" style="margin-bottom: -100px;">
                <div class="table-responsive">
                    <table class="table align-middle table-striped table-centered mb-n5">
                        <thead class="table-light thead-fixed">
                            <tr class="fs-11">
                                <th style="width: 3%;"></th>
                                <th>OIC / Signatory</th>
                                <th style="width: 10%;" class="text-center">Status</th>
                                <th style="width: 6%;"></th>
                            </tr>
                        </thead>
                        <tbody class="table-white fs-12">
                            <tr v-for="(list,index) in selected?.signatory?.schedules" v-bind:key="index" @click="selectRow(index)" :class="{
                                'bg-success-subtle': list.is_ongoing === 1
                            }">
                                <td class="text-center"> 
                                    <div class="avatar-xs chat-user-img online">
                                        <img :src="list.user.avatar" alt="" class="avatar-xs rounded-circle">
                                    </div>
                                </td>
                                <td>
                                    <h5 class="fs-12 mt-1 mb-0 fw-semibold text-primary text-uppercase">{{list.user.name}}</h5>
                                    <p class="fs-12 text-muted mb-0">{{list.start_at}} - {{list.end_at}}</p>
                                </td>
                                <td class="text-center">
                                    <span v-if="list.is_ongoing" class="badge bg-success">Ongoing</span>
                                    <span v-else class="badge bg-warning">Upcoming</span>
                                </td>
                                <td></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </BCol>                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                              
        </BRow>
    </b-modal>
    <Signatory @success="showModal = false" ref="signatory"/>
    <Assignment @success="showModal = false" ref="assignment"/>
</template>
<script>
import Signatory from './Signatory.vue';
import Assignment from './Assignment.vue';
export default {
    components: { Signatory, Assignment },
    data(){
        return {
            selected: null,
            showModal: false
        }
    },
    methods: { 
        show(data){
            console.log(data);
            this.selected = data;
            this.showModal = true;
        },
        openSignatory(){
            this.$refs.signatory.show(this.selected);
        }, 
        openAssignment(){
            this.$refs.assignment.show(this.selected);
        },
        hide(){
            this.showModal = false;
        }
    }
}
</script>