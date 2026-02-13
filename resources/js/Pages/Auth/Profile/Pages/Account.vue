<template>
<div class="card bg-light-subtle shadow-none border">
    <div class="card-header bg-light-subtle">
        <div class="d-flex mb-n3">
            <div class="flex-shrink-0 me-3">
                <div style="height:2.5rem;width:2.5rem;">
                    <span class="avatar-title bg-primary-subtle rounded p-2 mt-n1">
                        <i class="ri-alarm-fill text-primary fs-24"></i>
                    </span>
                </div>
            </div>
            <div class="flex-grow-1">
                <h5 class="mb-0 fs-14"><span class="text-body">Profile Information</span></h5>
                <p class="text-muted text-truncate-two-lines fs-12">Update your account's profile information and email address.</p>
            </div>
            <div class="flex-shrink-0" style="width: 45%;">
                
            </div>
        </div>
    </div>


    <div class="card-body bg-white rounded-bottom" style="height: calc(100vh - 291px); overflow: auto;">
        <div class="card bg-light-subtle border-1 rounded-bottom shadow-none mb-0 p-3">
            <form class="customform">
                <div class="row g-2 mt-0 mb-1">
                    <div class="col-sm-3">
                        <div class="p-1 border border-dashed bg-white rounded">
                            <div class="d-flex align-items-center">
                                <div class="avatar-sm me-2">
                                    <div class="avatar-title rounded bg-transparent text-primary fs-20"><i class="ri-user-2-fill"></i></div>
                                </div>
                                <div class="flex-grow-1">
                                    <p class="text-muted mb-0 fs-12">Full Name :</p>
                                    <h5 class="mb-0 fs-12">{{form.firstname}}</h5>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="p-1 border border-dashed bg-white rounded">
                            <div class="d-flex align-items-center">
                                <div class="avatar-sm me-2">
                                    <div class="avatar-title rounded bg-transparent text-primary fs-20"><i class="ri-user-2-fill"></i></div>
                                </div>
                                <div class="flex-grow-1">
                                    <p class="text-muted mb-0 fs-12">Middle Name :</p>
                                    <h5 class="mb-0 fs-12">{{form.middlename}}</h5>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="p-1 border border-dashed bg-white rounded">
                            <div class="d-flex align-items-center">
                                <div class="avatar-sm me-2">
                                    <div class="avatar-title rounded bg-transparent text-primary fs-20"><i class="ri-user-2-fill"></i></div>
                                </div>
                                <div class="flex-grow-1">
                                    <p class="text-muted mb-0 fs-12">Last Name :</p>
                                    <h5 class="mb-0 fs-12">{{form.lastname}}</h5>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="p-1 border border-dashed bg-white rounded">
                            <div class="d-flex align-items-center">
                                <div class="avatar-sm me-2">
                                    <div class="avatar-title rounded bg-transparent text-primary fs-20"><i class="ri-user-2-fill"></i></div>
                                </div>
                                <div class="flex-grow-1">
                                    <p class="text-muted mb-0 fs-12">Suffix :</p>
                                    <h5 class="mb-0 fs-12">{{(form.suffix) ? form.suffix : '-'}}</h5>
                                </div>
                            </div>
                        </div>
                    </div>
                        <div class="col-sm-3">
                        <div class="p-1 border border-dashed bg-white rounded">
                            <div class="d-flex align-items-center">
                                <div class="avatar-sm me-2">
                                    <div class="avatar-title rounded bg-transparent text-primary fs-20"><i class="ri-mail-fill"></i></div>
                                </div>
                                <div class="flex-grow-1">
                                    <p class="text-muted mb-0 fs-12">Username :</p>
                                    <h5 class="mb-0 fs-12">{{ form.username }}</h5>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="p-1 border border-dashed bg-white rounded">
                            <div class="d-flex align-items-center">
                                <div class="avatar-sm me-2">
                                    <div class="avatar-title rounded bg-transparent text-primary fs-20"><i class="ri-mail-fill"></i></div>
                                </div>
                                <div class="flex-grow-1">
                                    <p class="text-muted mb-0 fs-12">Email :</p>
                                    <h5 class="mb-0 fs-12">{{ form.email }}</h5>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="p-1 border border-dashed bg-white rounded">
                            <div class="d-flex align-items-center">
                                <div class="avatar-sm me-2">
                                    <div class="avatar-title rounded bg-transparent text-primary fs-20"><i class="ri-phone-fill"></i></div>
                                </div>
                                <div class="flex-grow-1">
                                    <p class="text-muted mb-0 fs-12">Contact no. :</p>
                                    <h5 class="mb-0 fs-12">{{ form.mobile }}</h5>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="p-1 border border-dashed bg-white rounded">
                            <div class="d-flex align-items-center">
                                <div class="avatar-sm me-2">
                                    <div class="avatar-title rounded bg-transparent text-primary fs-20"><i class="ri-heart-fill"></i></div>
                                </div>
                                <div class="flex-grow-1">
                                    <p class="text-muted mb-0 fs-12">Sex :</p>
                                    <h5 class="mb-0 fs-12">{{ form.sex.name }}</h5>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
</template>
<script>
import { useForm } from '@inertiajs/vue3';
import TextInput from '@/Shared/Components/Forms/TextInput.vue';
import InputLabel from '@/Shared/Components/Forms/InputLabel.vue';
export default {
    components: { InputLabel, TextInput },
    data(){
        return {
            form: useForm({
                username: this.$page.props.user.data.username,
                email: this.$page.props.user.data.email,
                firstname: this.$page.props.user.data.firstname,
                middlename: this.$page.props.user.data.middlename,
                lastname: this.$page.props.user.data.lastname,
                suffix: this.$page.props.user.data.suffix,
                sex: this.$page.props.user.data.sex,
                mobile: this.$page.props.user.data.mobile,
            }),
        }
    }
}
</script>