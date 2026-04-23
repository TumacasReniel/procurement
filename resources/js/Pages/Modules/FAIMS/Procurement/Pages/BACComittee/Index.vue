<template>

    <Head title="BAC Committee" />
    <PageHeader title="BAC Committee" pageTitle="List" />
    <BRow class="procurement-index-page">
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
                            <h5 class="mb-0 fs-14"><span class="text-body">BAC Committee Members</span></h5>
                            <p class="text-muted text-truncate-two-lines fs-12">List of BAC Chairperson, Vice Chairperson, and Members</p>
                        </div>
                        <div v-if="canManageCommittee" class="flex-shrink-0">
                            <button type="button" class="btn btn-primary btn-sm" @click="openCreate">
                                <i class="ri-user-add-line align-bottom me-1"></i>Add Member
                            </button>
                        </div>
                    </div>
                </div>


                <div class="card-body bg-white rounded-bottom" style="height: calc(100vh - 295px); overflow: auto;">
                    <div class="container text-center">
                        <!-- Chairperson -->
                        <h4 class="mb-3">Chairperson</h4>
                        <div class="row justify-content-center mb-4">
                            <div
                                class="col-md-4"
                                :style="{ cursor: isViewable(chairperson) ? 'pointer' : 'default' }"
                                @click="openView(chairperson)"
                            >
                                <div class="card-body border rounded-4 text-center">
                                    <div class="mb-2 mx-auto">
                                        <img :src="chairperson.avatar" alt="" id="candidate-img" class="img-thumbnail avatar-sm rounded-circle shadow-none">
                                    </div>
                                    <h5 v-if="chairperson.user && chairperson.user.name" class="fs-12 mb-0 text-primary fw-semibold">{{chairperson.user.name}}</h5>
                                    <h5 v-else class="fs-12 text-warning mb-0">Not Assigned</h5>
                                    <p class="fs-12 text-muted mb-0">{{chairperson.designation}}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Vice Chairperson -->
                        <h4 class="mb-3">Vice Chairperson</h4>
                        <div class="row justify-content-center mb-4">
                            <div
                                v-for="(vice, index) in vices"
                                :key="index"
                                class="col-md-4 mb-3"
                                :style="{ cursor: isViewable(vice) ? 'pointer' : 'default' }"
                                @click="openView(vice)"
                            >
                                <div class="card-body border rounded-4 text-center">
                                    <div class="mb-2 mx-auto">
                                        <img :src="vice.avatar" alt="" id="candidate-img" class="avatar-sm img-thumbnail rounded-circle shadow-none">
                                    </div>

                                    <h5 v-if="vice.user && vice.user.name" class="fs-12 mb-0 text-primary fw-semibold">{{vice.user.name}}</h5>
                                    <h5 v-else class="fs-12 text-warning mb-0">Not Assigned</h5>
                                    <p class="fs-12 text-muted mb-0">{{vice.designation}}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Members -->
                        <h4 class="mb-3">Members </h4>
                        <div class="row justify-content-center">
                            <div
                                v-for="(member, index) in members"
                                :key="index"
                                class="col-md-4 mb-3"
                                :style="{ cursor: isViewable(member) ? 'pointer' : 'default' }"
                                @click="openView(member)"
                            >
                                <div class="card-body border rounded-4 text-center">
                                    <div class="mb-2 mx-auto">
                                        <img :src="member.avatar" alt="" id="candidate-img" class="avatar-sm img-thumbnail rounded-circle shadow-none">
                                    </div>

                                    <h5 v-if="member.user && member.user.name" class="fs-12 mb-0 text-primary fw-semibold">{{member.user.name}}</h5>
                                    <h5 v-else class="fs-12 text-warning mb-0">Not Assigned</h5>
                                    <p class="fs-12 text-muted mb-0">{{member.designation}}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                
                </div>
            </div>
        </div>
    </BRow>
    <View ref="view"/>
    <AddMember ref="createMember"/>
</template>
<script>
import AddMember from './Modals/Create.vue';
import View from './Modals/View.vue';
import PageHeader from '@/Shared/Components/PageHeader.vue';
export default {
    props: ['designations'],
    components: { AddMember, PageHeader, View },
    computed: {
        canManageCommittee() {
            return (this.$page.props.roles || []).includes('Administrator');
        },
        committeeDesignations() {
            return Array.isArray(this.designations?.data) ? this.designations.data : [];
        },
        chairperson() {
            return this.committeeDesignations.find(d => d.designation === 'BAC Chairperson')
                || this.buildPlaceholder('BAC Chairperson');
        },
        vices() {
            const vices = this.committeeDesignations.filter(d => d.designation === 'BAC Vice Chairperson');

            return this.withPlaceholders(vices, 1, 'BAC Vice Chairperson');
        },
        members() {
            const members = this.committeeDesignations.filter(d => d.designation === 'BAC Member');

            return this.withPlaceholders(members, 3, 'BAC Member');
        }
    },
    methods: {
        buildPlaceholder(designation) {
            return {
                id: null,
                avatar: '/images/avatars/avatar.jpg',
                designation,
                user: null,
            };
        },
        withPlaceholders(items, minimumCount, designation) {
            const entries = [...items];

            while (entries.length < minimumCount) {
                entries.push(this.buildPlaceholder(designation));
            }

            return entries;
        },
        isViewable(data) {
            return Boolean(data?.id);
        },
        openCreate() {
            this.$refs.createMember.show();
        },
        openView(data){
            if (!this.isViewable(data)) {
                return;
            }
            this.$refs.view.show(data);
        }
    }
}
</script>
