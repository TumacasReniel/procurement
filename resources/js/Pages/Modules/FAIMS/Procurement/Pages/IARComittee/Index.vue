<template>

    <Head title="BAC Committee" />
    <PageHeader title="IAR Committee" pageTitle="List" />
    <BRow>
        <div class="col-md-12">
            <div class="card bg-light-subtle shadow-none border">
                <div class="card-header bg-light-subtle">
                    <div class="d-flex mb-n3">
                        <div class="flex-shrink-0 me-3">
                            <div style="height:2.5rem;width:2.5rem;">
                                <span class="avatar-title bg-primary-subtle rounded p-2 mt-n1">
                                    <i class="ri-mark-pen-fill text-primary fs-24"></i>
                                </span>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="mb-0 fs-14"><span class="text-body">IAR Committee Members</span></h5>
                            <p class="text-muted text-truncate-two-lines fs-12">List of BAC Chairperson, Vice Chairperson, and Members</p>
                        </div>
                        <!-- <div class="flex-shrink-0" style="width: 45%;">

                        </div> -->

                    </div>
                </div>


                <div class="card-body bg-white rounded-bottom" style="height: calc(100vh - 295px); overflow: auto;">
                    <div class="container text-center">
                        <!-- Chairperson -->
                        <h4 class="mb-3">Chairperson</h4>
                        <div class="row justify-content-center mb-4" v-if="chairperson" >
                            <div class="col-md-4" style="cursor: pointer;" @click="openView(chairperson)">
                                <div class="card-body border rounded-4 text-center">
                                    <div class="mb-2 mx-auto">
                                        <img :src="(chairperson.is_oic) ? chairperson.oic_avatar : chairperson.avatar" alt="" id="candidate-img" class="img-thumbnail avatar-sm rounded-circle shadow-none">
                                    </div>
                                    <h5 v-if="chairperson.oic && chairperson.oic.name" class="fs-12 mb-0 text-warning fw-semibold">{{chairperson.oic.name}}</h5>
                                    <h5 v-else-if="chairperson.user && chairperson.user.name" class="fs-12 mb-0 text-primary fw-semibold">{{chairperson.user.name}}</h5>
                                    <h5 v-else class="fs-12 text-warning mb-0">Not Assigned</h5>
                                    <p class="fs-12 text-muted mb-0">{{chairperson.designation}}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Vice Chairperson -->
                        <h4 class="mb-3">Vice Chairperson</h4>
                        <div class="row justify-content-center mb-4">
                            <div v-for="(vice, index) in vices" :key="index" class="col-md-4 mb-3" style="cursor: pointer;" @click="openView(vice)">
                                <div class="card-body border rounded-4 text-center">
                                    <div class="mb-2 mx-auto">
                                        <img :src="(vice.is_oic) ? vice.oic_avatar : vice.avatar" alt="" id="candidate-img" class="avatar-sm img-thumbnail rounded-circle shadow-none">
                                    </div>

                                    <h5 v-if="vice.oic && vice.oic.name" class="fs-12 mb-0 text-warning fw-semibold">{{vice.oic.name}}</h5>
                                    <h5 v-else-if="vice.user && vice.user.name" class="fs-12 mb-0 text-primary fw-semibold">{{vice.user.name}}</h5>
                                    <h5 v-else class="fs-12 text-warning mb-0">Not Assigned</h5>
                                    <p class="fs-12 text-muted mb-0"><span v-if="vice.is_oic">OIC - </span>{{vice.designation}}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Members -->
                        <h4 class="mb-3">Members </h4>
                        <div class="row justify-content-center" v-f="members.length > 0">
                            <div v-for="(member, index) in members" :key="index" class="col-md-4 mb-3" style="cursor: pointer;" @click="openView(member)">
                                <div class="card-body border rounded-4 text-center">
                                    <div class="mb-2 mx-auto">
                                        <img :src="(member.is_oic) ? member.oic_avatar : member.avatar" alt="" id="candidate-img" class="avatar-sm img-thumbnail rounded-circle shadow-none">
                                    </div>

                                    <h5 v-if="member.oic && member.oic.name" class="fs-12 mb-0 text-warning fw-semibold">{{member.oic.name}}</h5>
                                    <h5 v-else-if="member.user && member.user.name" class="fs-12 mb-0 text-primary fw-semibold">{{member.user.name}}</h5>
                                    <h5 v-else class="fs-12 text-warning mb-0">Not Assigned</h5>
                                    <p class="fs-12 text-muted mb-0"><span v-if="member.is_oic">OIC - </span>{{member.designation}}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                
                </div>
            </div>
        </div>
    </BRow>
    <View ref="view"/>
</template>
<script>
import View from './Modals/View.vue';
import PageHeader from '@/Shared/Components/PageHeader.vue';
export default {
    props: ['designations'],
    components: { PageHeader, View },
    data() {
        return {

        }
    },
    computed: {
        chairperson() {
            return this.designations.data.find(d => d.order === 8);
        },
        vices() {
            return this.designations.data.filter(d => d.order === 9);
        },
        members() {
            return this.designations.data.filter(d => d.order === 10);
        }
    },
    methods: {
        openView(data){
            this.$refs.view.show(data);
        }
    }
}
</script>
