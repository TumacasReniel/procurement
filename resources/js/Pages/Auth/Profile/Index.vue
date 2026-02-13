<template>
    <Head title="Profile" />
    <PageHeader title="Profile Information" pageTitle="User" />
    <div class="row">
        <div class="col-md-3">
            <div class="card bg-light-subtle shadow-none border">
                
                <div class="card-body bg-white rounded-bottom rounded-top p-4" style="height: calc(100vh - 223px); overflow: auto;">
                   <div class="text-center">
                        <div class="profile-user position-relative d-inline-block mx-auto mb-3">
                            <img :src="$page.props.user.data.avatar" class="rounded-circle avatar-xl img-thumbnail user-profile-image material-shadow">
                            <div class="avatar-xs p-0 rounded-circle profile-photo-edit">
                                <input id="profile-img-file-input" type="file" class="profile-img-file-input" @change="previewImage"/>
                                <label for="profile-img-file-input" class="profile-photo-edit avatar-xs">
                                    <span class="avatar-title rounded-circle bg-light text-body">
                                    <i class="ri-camera-fill"></i>
                                    </span>
                                </label>
                            </div>
                        </div>
                        <h5 class="fs-16 mb-0">{{ $page.props.user.data.name }}</h5>
                        <p class="text-muted mb-0">{{ $page.props.user.data.position }}</p>
                    </div>
                    <hr class="text-muted">
                    <b-list-group class="list-group-fill-success mt-4">
                        <BListGroupItem :active="activeTab === 1" href="#" class="list-group-item-action" @click="show(1)">
                            <i class="ri-apps-2-fill align-middle me-2"></i>Overview
                        </BListGroupItem>
                        <BListGroupItem :active="activeTab === 2" href="#" class="list-group-item-action" @click="show(2)">
                            <i class="ri-profile-fill align-middle me-2"></i>Personal Information
                        </BListGroupItem>
                        <BListGroupItem :active="activeTab === 3" href="#" class="list-group-item-action" @click="show(3)">
                            <i class="ri-team-fill align-middle me-2"></i>Family Information
                        </BListGroupItem>
                         <BListGroupItem :active="activeTab === 7" href="#" class="list-group-item-action" @click="show(7)">
                            <i class="ri-bank-card-fill  align-middle me-2"></i>Contributions & Account Numbers
                        </BListGroupItem>
                        <BListGroupItem :active="activeTab === 4" href="#" class="list-group-item-action" @click="show(4)">
                            <i class="ri-shield-check-line align-middle me-2"></i>Password & Security
                        </BListGroupItem>
                        <BListGroupItem :active="activeTab === 5" href="#" class="list-group-item-action" @click="show(5)">
                            <i class="ri-shield-keyhole-fill align-middle me-2"></i>Authentication History
                        </BListGroupItem>
                        <BListGroupItem :active="activeTab === 6" href="#" class="list-group-item-action" @click="show(6)">
                            <i class="ri-history-line align-middle me-2"></i>Activity History
                        </BListGroupItem>
                    </b-list-group>
                </div>
            </div>
        </div>
      
        <div class="col-md-9">
            <Overview v-if="activeTab === 1"/>
            <Edit :addresses="addresses" v-if="activeTab === 2"/>
            <Address v-if="activeTab === 3"/>
            <Security v-if="activeTab === 4"/>
            <AuthenticationLog v-if="activeTab === 5"/>
            <ActivityLog v-if="activeTab === 6"/>
            <Account v-if="activeTab === 7"/>
        </div>
    </div>
</template>
<script>
import { useForm } from "@inertiajs/vue3"
import Overview from "./Pages/Overview.vue";
import Edit from "./Pages/Edit.vue";
import Account from './Pages/Account.vue';
import Address from './Pages/Address.vue';
import Security from './Pages/Security.vue';
import ActivityLog from './Pages/ActivityLog.vue';
import AuthenticationLog from "./Pages/AuthenticationLog.vue";
import PageHeader from '@/Shared/Components/PageHeader.vue';
export default {
    components: { PageHeader, Overview, Edit, AuthenticationLog, ActivityLog, Security, Address, Account },
    props: ['addresses'],
    data() {
        return {
            currentUrl: window.location.origin,
            activeTab: 1, 
            form: useForm({
                image: null,
            }),
        };
    },
    methods: {
        show(tab){
            this.activeTab = tab;
        },
        previewImage(e) {
            var fileInput = document.querySelector(".profile-img-file-input");
            var preview = document.querySelector(".user-profile-image");
            var file = fileInput.files[0];

            if (!file) return;

             // Validate file type
            const allowedTypes = ['image/jpeg', 'image/png'];
            if (!allowedTypes.includes(file.type)) {
                alert("Only JPEG or PNG images are allowed.");
                fileInput.value = '';
                return;
            }

            // Validate file size (2MB max)
            const maxSize = 2 * 1024 * 1024; // 2MB in bytes
            if (file.size > maxSize) {
                alert("The image must be less than 2MB.");
                fileInput.value = '';
                return;
            }


            this.form.image = file;
            var reader = new FileReader();

            reader.addEventListener("load", () => { 
                preview.src = reader.result;
                this.form.post('/photo', {
                    preserveScroll: true,
                    onSuccess: () => {
                        this.uploaded = true;
                        this.hasAvatar = true;
                    },
                });
            }, false);

            if (file) { 
                reader.readAsDataURL(file); 
            }
        },
    }
}
</script>