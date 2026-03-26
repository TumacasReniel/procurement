<template>
    <Head title="Unauthorized Access"/>
    <div id="layout-wrapper" class="auth-page-wrapper pt-5 d-flex justify-content-center align-items-center min-vh-100">
        <div class="auth-page-content">
            <BContainer>

                <BRow class="justify-content-center">
                    <BCol md="9" lg="7" xl="6">
                        <div class="card bg-light-subtle shadow-none border">
                            <div class="card-header bg-primary">
                                <div class="d-flex mb-n2">
                                    <div class="flex-shrink-0 me-3">
                                        <div style="height:2.5rem;width:2.5rem;">
                                        <img src="@assets/images/logo-sm.png" alt="" class="avatar-sm">
                                        </div>
                                    </div>
                                    <!-- <div class="flex-grow-1">
                                        <h5 class="mb-0 mt-2 fs-14"><span class="text-white fw-semibold text-uppercase fs-13">Department Of Science and Technology</span></h5>
                                        <p class="text-white fs-11">Unified Information Management System</p>
                                    </div> -->
                                     <div class="flex-grow-1">
                                        <h5 class="mb-0 mt-2 fs-14 fw-semibold text-uppercase text-white" style="font-size: 10.7px"> DEPARTMENT OF SCIENCE AND TECHNOLOGY</h5>
                                        <p class="text-white fs-11"><span class="fw-semibold">One<span class="text-info">DOST</span>4U</span> : <span class="text-muted">Solutions for All</span></p>
                                    </div>
                                </div>
                            </div>
                            <div class="car-body bg-white shadow-none" style="padding: 33px;">
                                <form class="customform" @submit.prevent="submit">
                                    <div class="alert alert-danger alert-dismissible alert-label-icon label-arrow fade show mb-xl-0 material-shadow" role="alert">
                                        <i class="ri-error-warning-line label-icon"></i><strong> 403 | Unauthorized Access</strong>
                                    </div>
                                    <hr class="text-muted"/>
                                    Unauthorized access attempt detected. This activity is prohibited and may be prosecuted under RA 10175 (Cybercrime Prevention Act of 2012).
                                    <hr class="text-muted"/>
                                    <div class="alert bg-light border-light text-body material-shadow" role="alert">
                                        Cybercrime Prevention Act of 2012 · RA 10175
                                    </div>
                                    <p class="text-muted fs-11 text-center">Unauthorised access, hacking, or any intrusion attempt is a criminal offence. This transaction has been logged.</p>
                                </form>
                            </div>
                            <div class="card-footer bg-light-subtle">
                                 <div class="text-center p-1">
                                    <p class="mb-0 fs-11">Please contact your administrator.
                                    </p>
                                </div>
                            </div>
                        </div>

                    </BCol>
                </BRow>
            </BContainer>
        </div>
    </div>
</template>
<script setup>
import { ref, computed, nextTick, onMounted } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Checkbox from '@/Shared/Components/Forms/Checkbox.vue';
import InputError from '@/Shared/Components/Forms/InputError.vue';
import InputLabel from '@/Shared/Components/Forms/InputLabel.vue';
import TextInput from '@/Shared/Components/Forms/TextInput.vue';
import FingerprintJS from '@fingerprintjs/fingerprintjs';
defineProps({
    canResetPassword: Boolean,
    status: String
});
const deviceId = ref(null);

const initDeviceId = async () => {
    try { 
        const fp = await FingerprintJS.load();
        const result = await fp.get();
        deviceId.value = result.visitorId;
        console.log('Device ID:', deviceId.value);
    } catch (e) {
        console.error("Failed to load FingerprintJS:", e);
    }
};

onMounted(() => {
    initDeviceId();
});

const type = ref('Login');
const togglePassword = ref(false);
const digits = ref(Array(6).fill(''));
const form = useForm({
    mobile: null,
    code: null,
    email: '',
    password: '',
    remember: false,
});
const handleInput = (field) => {
    form.errors[field] = null; // or false
};
const submit = () => {
    if (type.value === 'OTP' && !form.code) {
        sendOtp();
        return;
    }
    form.transform(data => ({
        ...data,
        remember: form.remember ? 'on' : '',
    })).post('/login', {
         onError: (errors) => {
        console.log('Validation Errors:', errors);
    },
        onFinish: () => form.reset('password'),
    });
};
const updateCode = () => {
    form.code = digits.value.join('');
};

const isOtpComplete = computed(() => form.code && form.code.length === 6);

// Focus input
const focusInput = (idx) => {
    const inputs = otpInputs.value;
    if (!inputs) return;
    const el = Array.isArray(inputs) ? inputs[idx] : inputs;
    if (el && el.focus) {
        el.focus();
        try { el.setSelectionRange(0, 1); } catch {}
    }
};

// References for v-for inputs
const otpInputs = ref([]);

// onInput
const onInput = (e, index) => {
    const raw = e.target.value || '';
    const cleaned = raw.replace(/\D/g, '');
    if (!cleaned) {
        digits.value[index] = '';
        updateCode();
        return;
    }

    const chars = cleaned.split('');
    for (let i = 0; i < chars.length && index + i < digits.value.length; i++) {
        digits.value[index + i] = chars[i];
    }

    const focusTo = Math.min(index + chars.length, digits.value.length - 1);
    nextTick(() => focusInput(focusTo));

    updateCode();
};

// onKeydown
const onKeydown = (e, index) => {
    if (e.key === 'Backspace') {
        e.preventDefault();
        if (digits.value[index]) {
            digits.value[index] = '';
            updateCode();
            return;
        }
        if (index > 0) {
            digits.value[index - 1] = '';
            nextTick(() => focusInput(index - 1));
            updateCode();
        }
    } else if (e.key === 'ArrowLeft' && index > 0) {
        e.preventDefault(); focusInput(index - 1);
    } else if (e.key === 'ArrowRight' && index < digits.value.length - 1) {
        e.preventDefault(); focusInput(index + 1);
    }
};

// onPaste
const onPaste = (e, index) => {
    e.preventDefault();
    const paste = (e.clipboardData || window.clipboardData).getData('text') || '';
    const chars = paste.replace(/\D/g, '').split('');
    for (let i = 0; i < chars.length && index + i < digits.value.length; i++) {
        digits.value[index + i] = chars[i];
    }
    const focusTo = Math.min(index + chars.length, digits.value.length - 1);
    nextTick(() => focusInput(focusTo));
    updateCode();
};
const sendOtp = () => {
    form.post('/otp/send', {
        preserveScroll: true,
        onError: (errors) => {
            console.log('OTP Errors:', errors);
        },
        onSuccess: () => {
            // optional: show OTP input step
            // type.value = 'OTP_VERIFY'
        }
    });
};
const verifyOtp = () => {
    form.post('/otp/verify', {
        preserveScroll: true,
        onError: (errors) => {
            console.log('Verify OTP Errors:', errors);
        }
    });
};
const loginGoogle = () => {
    window.location.href = '/auth/google';
}
</script>
<script>
export default {
    layout: null,
}
</script>
<style scoped>
    input::placeholder {
        font-size: 12px;
        color: #999;
    }
</style>