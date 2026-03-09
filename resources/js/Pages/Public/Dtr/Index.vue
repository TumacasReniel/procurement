<template>
    <Head title="Date Time Record"/>
    <div class="d-flex justify-content-center align-items-center min-vh-100">
        <div class="auth-page-content">
            <BContainer style="max-width: 1250px;">

                <BRow class="justify-content-center">
                    <div class="col-lg-12">
                  
                        <div class="card border bg-white">
                            <div class="card-header bg-primary">
                                <div class="d-flex mb-n2">
                                    <div class="flex-shrink-0 me-3">
                                        <div style="height:2.5rem;width:2.5rem;">
                                        <img src="@assets/images/logo-sm.png" alt="" class="avatar-sm">
                                        </div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h5 class="mb-0 mt-2 fs-14 fw-semibold text-uppercase text-white"> DEPARTMENT OF SCIENCE AND TECHNOLOGY - IX</h5>
                                        <p class="text-white fs-11"><span class="fw-semibold">One<span class="text-info">DOST</span>4U</span> : <span class="text-muted">Human Resource - Date Time Record</span></p>
                                    </div>
                                    <div class="flex-shrink-0 ms-auto text-end">
                                    
                                        <h5 class="mb-0 mt-2 fs-14 fw-semibold text-uppercase text-white" style="font-size: 10.7px">{{ currentTime }}</h5>
                                        <p class="text-white fs-11">{{ currentDate}}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row g-3 mb-n3">
                                    <!-- <div class="col-md-12">
                                        <h1 class="fw-bold text-primary text-center" style="font-size: 100px;">
                                            {{ currentTime }}
                                        </h1>
                                    </div> -->
                                    <div class="col-md-4" ref="leftCol"> 

                                        <div class="video-wrapper position-relative">
                                            <video
                                                ref="video"
                                                autoplay
                                                playsinline
                                                class="qr-child img-thumbnail">
                                            </video>
                                            <div v-if="isScanning" class="scanner-overlay"></div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-12 mt-2">
                                                <div class="alert alert-warning alert-border-left alert-dismissible fade show material-shadow fs-11" role="alert">
                                                    <span class="fs-10" style="line-height: 1.2; display: inline-block;">
                                                        <strong>Time Selection Notice:</strong> 
                                                        Please observe the following rule: <br>
                                                        - Any time <strong>before 12:30 PM</strong> is considered <strong>AM</strong>.<br>
                                                        - Any time <strong>12:30 PM or later</strong> is considered <strong>PM</strong>.
                                                    </span>
                                                </div>
                                                <div class="d-flex gap-2">
                                                    <button
                                                        class="btn btn-lg fw-semibold btn-primary flex-fill"
                                                        :disabled="!type"
                                                        @click="captureFrame"
                                                    >
                                                        <h5 class="mb-0 fs-15 fw-semibold text-uppercase text-white" style="font-size: 10.7px">
                                                            SUBMIT DTR
                                                        </h5>
                                                        <p class="text-white fw-normal fs-10 mb-0">(Please select one of the options below before submitting)</p>
                                                    </button>
                                                </div>
                                                <hr class="text-muted"/>
                                                <div class="d-flex gap-2 mt-2">
                                                    <button
                                                        class="btn btn-lg fw-semibold flex-fill"
                                                        :class="type === 'Time In (am)' ? 'btn-success' : 'btn-light'"
                                                        @click="selectType('Time In (am)')">
                                                        AM IN
                                                    </button>

                                                    <button
                                                        class="btn btn-lg fw-semibold flex-fill"
                                                        :class="type === 'Time Out (am)' ? 'btn-success' : 'btn-light'"
                                                        @click="selectType('Time Out (am)')">
                                                        AM OUT
                                                    </button>
                                                   
                                                </div>
                                                 <div class="d-flex gap-2 mt-2">
                                                  
                                                    <button
                                                        class="btn btn-lg fw-semibold flex-fill"
                                                        :class="type === 'Time In (pm)' ? 'btn-success' : 'btn-light'"
                                                        @click="selectType('Time In (pm)')">
                                                        PM IN
                                                    </button>

                                                    <button
                                                        class="btn btn-lg fw-semibold flex-fill"
                                                        :class="type === 'Time Out (pm)' ? 'btn-success' : 'btn-light'"
                                                        @click="selectType('Time Out (pm)')">
                                                        PM OUT
                                                    </button>
                                                </div>
                                            </div>
                                        </div>  
                                    </div>

                                    <div class="col-md-8"> 
                                         <h1 class="fw-bold text-primary text-center mt-n3 mb-n1" style="font-size: 125px;">
                                            {{ currentTime }}
                                        </h1>
                                        <div v-if="status == 'New'" class="d-flex w-100 justify-content-center align-items-center mb-2">
                                            <div class="p-4 w-100 border rounded bg-success-subtle">
                                                <div class="d-flex" style="margin-bottom: -12px;">
                                                    <div class="flex-shrink-0 me-3">
                                                        <div style="height:2.5rem;width:2.5rem;">
                                                            <img :src="employee.avatar" alt="user-img" class="avatar-sm rounded-circle mt-n2">
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1">
                                                        <h5 class="mb-0 fs-18 text-uppercase fw-semibold"><span class="text-body">{{employee.name}}</span></h5>
                                                        <p class="text-muted mb-n2 text-truncate-two-lines fs-12">{{employee.division}}</p>
                                                    </div>
                                                    <div class="flex-0 mb-n2">
                                                        <h5 class="mb-0 fs-18"><span class="text-body">{{employee.time}}</span></h5>
                                                        <p class="text-muted text-truncate-two-lines float-end fs-12">{{employee.type}}</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                          <div v-else-if="status == 'Success'" class="d-flex w-100 justify-content-center align-items-center mb-2">
                                            <div class="p-4 w-100 border rounded bg-danger-subtle">
                                                <div class="d-flex" style="margin-bottom: -12px;">
                                                    <div class="flex-shrink-0 me-3">
                                                        <div style="height:2.5rem;width:2.5rem;">
                                                            <img :src="employee.avatar" alt="user-img" class="avatar-sm rounded-circle mt-n2">
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1">
                                                        <h5 class="mb-0 fs-18 text-uppercase fw-semibold"><span class="text-body">{{employee.name}}</span></h5>
                                                        <p class="text-muted mb-n2 text-truncate-two-lines fs-12">{{employee.division}}</p>
                                                    </div>
                                                    <div class="flex-0 mb-n2">
                                                        <h5 class="mb-0 fs-18"><span class="text-body">{{employee.time}}</span></h5>
                                                        <p class="text-muted text-truncate-two-lines float-end fs-12">{{employee.type}}</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div v-else-if="status == 'Unauthorized'" class="d-flex w-100 justify-content-center align-items-center mb-2">
                                            <div class="p-4 w-100 border rounded bg-dark text-center">
                                                <p class="mb-0 text-white fw-bold fs-12">Access Restricted</p>
                                                <p class="mb-0 text-white fs-11">DTR submission blocked. Your device or location is not authorized.</p>
                                            </div>
                                        </div>
                                        <div v-else-if="status == 'Duplicate'" class="d-flex w-100 justify-content-center align-items-center mb-2">
                                            <div class="p-4 w-100 border rounded bg-warning text-center">
                                                <p class="mb-0 text-white fw-bold fs-12">Duplicate attendance detected for <b v-if="employee" class="text-danger text-uppercase">{{ employee.name }}</b>.</p>
                                                <p class="mb-0 text-white fs-11">The system has already logged this employee's time-in/time-out. No additional entry is needed.</p>
                                            </div>
                                        </div>
                                        <div v-else-if="status == 'Error'" class="d-flex w-100 justify-content-center align-items-center mb-2">
                                            <div class="p-4 w-100 border rounded bg-danger text-center">
                                                <p class="mb-0 text-white fw-bold fs-12">Employee not found in the system.</p>
                                                <p class="mb-0 text-white fs-11">No matching employee was found based on the face data. Please contact Admnistrator.</p>
                                            </div>
                                        </div>
                                        <div v-else-if="status == 'Disabled Overlap'" class="d-flex w-100 justify-content-center align-items-center mb-2">
                                            <div class="p-4 w-100 border rounded bg-danger-subtle text-center">
                                                <p class="mb-0 text-dark fs-12">Attendance action is restricted for <b v-if="employee" class="text-danger text-uppercase">{{ employee.name }}</b>.</p>
                                                <p class="mb-0 text-muted fs-11">You cannot time in because you have already timed out for this period.</p>
                                            </div>
                                        </div>
                                        <div v-else-if="status == 'Disabled AM'" class="d-flex w-100 justify-content-center align-items-center mb-2">
                                            <div class="p-4 w-100 border rounded bg-danger-subtle text-center">
                                                <p class="mb-0 text-dark fs-12">Attendance action is restricted.</p>
                                                <p class="mb-0 text-muted fs-11">You cannot time in because it is already PM period.</p>
                                            </div>
                                        </div>
                                        <div v-else class="d-flex w-100 justify-content-center align-items-center mb-2">
                                            <div class="p-4 w-100 border rounded bg-dark-subtle text-center">
                                                <p class="mb-0 text-dark fs-12"> Please face the camera to begin.</p>
                                                <p class="mb-0 text-muted fs-11"> Make sure your face is clearly visible for accurate recognition.</p>
                                            </div>
                                        </div>

                                        <div class="card bg-light-subtle shadow-none border">
                                            <div class="card-header bg-light-subtle">
                                                <div class="d-flex mb-n3">
                                                    <div class="flex-shrink-0 me-3">
                                                        <div style="height:2rem; width:2rem;">
                                                            <span class="avatar-title bg-primary-subtle rounded p-2 mt-n1">
                                                                <i class="ri-file-list-3-line text-primary fs-16"></i>
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1">
                                                        <h5 class="mb-0 fs-11" style="margin-top: -2px;"><span class="text-body">List of Employees</span></h5>
                                                        <p class="text-muted fs-10">
                                                            Shows employees who have successfully logged their time in or out for the day.
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="card-body bg-white rounded-bottom">
                                                <div class="table-responsive table-card" ref="scrollabletable" style="overflow-y:auto; overflow-x:hidden;">
                                                    <table class="table table-nowrap align-middle mb-0">
                                                        <thead class="bg-light thead-fixed">
                                                            <tr class="fs-10">
                                                                <th style="width: 7%;" class="text-center">#</th>
                                                                <th>Name</th>
                                                                <th style="width: 18%;" class="text-center">Type</th>
                                                                <th style="width: 15%;" class="text-center">Time</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody v-if="lists.length">
                                                            <tr v-for="(list,index) in lists"
                                                                :key="index"
                                                                :class="['fs-10',{ 'bg-success-subtle': list.subtype === 'in',
                                                                    'bg-danger-subtle': list.subtype === 'out'
                                                                    }]">
                                                                <td class="text-center">
                                                                    <img :src="list.avatar" alt="user-img" class="avatar-xxs rounded-circle">
                                                                </td>
                                                                <td>{{ list.name }}</td>
                                                                <td class="text-center">{{ list.type }}</td>
                                                                <td class="text-center">{{ list.time }}</td>
                                                            </tr>
                                                        </tbody>
                                                        <tbody v-else>
                                                            <tr><td colspan="4" class="text-center text-muted">No employees found.</td></tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </BRow>
            </BContainer>
        </div>
    </div>
    <div :class="flashClass" class="flash-overlay"></div>
    <audio ref="successBeep" src="/sounds/success.mp3"></audio>
    <audio ref="errorBuzz" src="/sounds/error.mp3"></audio>
</template>
<script>
const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'  };
const options1 = { hour12: false  };
const twelve = new Date("2022-03-25 11:00:00").toLocaleTimeString("en-US",options1);
const twelvethirty = new Date("2022-03-25 12:30:00").toLocaleTimeString("en-US",options1);
const one = new Date("2022-03-25 15:00:00").toLocaleTimeString("en-US",options1);
import { useForm } from '@inertiajs/vue3';
import FingerprintJS from '@fingerprintjs/fingerprintjs'
export default {
    layout: null,
    props: ['code'],
    data() {
        return {
            currentUrl: window.location.origin,
            currentDate: null,
            currentTime: null,
            user: '',
            activebutton: 0,
            inactive: false,
            message: '',
            status: '',
            employee: null,
            form: useForm({
                image: null,
                username: null,
                type:'Time In (am)',
                option: 'dtr'
            }),
            flashClass:'',
            type: null,
            lists: [],
            isScanning: false,
            statusTimeout: null,
            tableHeightLocked: false,
            cameraStream: null,
            deviceId: null
        };
    },
    mounted() {
        this.initDeviceId();
        this.clockInterval = setInterval(() => {
            this.currentTime = new Date().toLocaleTimeString("en-US");
            this.currentDate = new Date().toLocaleDateString("en-US", options);
        }, 1000);
        this.keepAliveInterval = setInterval(() => {
            axios.get('/keep-alive'); 
        }, 1000 * 60 * 30); 
        this.initCamera();
        this.syncTableHeight(true)

        this.onResize = () => this.syncTableHeight(true)
        window.addEventListener('resize', this.onResize)
    },
    beforeUnmount() {
        window.removeEventListener('resize', this.syncTableHeight)
        clearInterval(this.clockInterval);
        clearInterval(this.keepAliveInterval);
        if (this.cameraStream) {
            this.cameraStream.getTracks().forEach(track => track.stop());
        }
    },
    created(){
        this.fetch();
    },
    methods: {
        async initDeviceId() {
            try {
                const fp = await FingerprintJS.load()
                const result = await fp.get()
                this.deviceId = result.visitorId
                console.log(this.deviceId);
            } catch (e) {
                console.error("Failed to load FingerprintJS:", e)
            }},
        flashSuccess(){
            this.$refs.successBeep.currentTime = 0
            this.$refs.successBeep.play()
            this.flashClass = "flash-overlay flash-success"
            setTimeout(()=>{
                this.flashClass = "flash-overlay"
            },400)
        },
        flashError(){
            this.$refs.errorBuzz.currentTime = 0
            this.$refs.errorBuzz.play()
            this.flashClass = "flash-overlay flash-error"
            setTimeout(()=>{
                this.flashClass = "flash-overlay"
            },400)
        },
        resetStatusTimer() {
            // Clear previous timer if exists
            if (this.statusTimeout) {
                clearTimeout(this.statusTimeout);
            }
            this.statusTimeout = setTimeout(() => {
                this.status = null;
                this.employee = null;
                this.statusTimeout = null;
            }, 15000);
        },
        syncTableHeight(force = false) {
            this.$nextTick(() => {
                const left = this.$refs.leftCol
                const table = this.$refs.scrollabletable
                if (!left || !table) return

                if (this.tableHeightLocked && !force) return

                const offset = 180
                table.style.height = `${left.offsetHeight - offset}px`

                this.tableHeightLocked = true
            })
        },
        selectType(value) {
            this.type = value
        },
        fetch(page_url){
            page_url = page_url || '/attendance';
            return axios.get(page_url,{
                params : {
                    option: 'list',
                    code: this.code,
                    count: 20,
                }
            })
            .then(response => {
                this.lists = response.data;       
            });
        },
        find(){
            this.user = ''; 
            this.inactive = false;
            this.capturePhoto();
            this.form.post('/attendance',{
                preserveScroll: true,
                onSuccess: (response) => {
                    if(response.props.flash.info == 'Error'){
                        this.status = response.props.flash.info;
                        setInterval(() => {
                            this.status = null;
                        }, 9000);
                    }else{
                        this.status = response.props.flash.info;
                        this.user = response.props.flash.data;
                        this.form.username = null;
                        setInterval(() => {
                            this.user = null;
                            this.status = null;
                        }, 9000);
                    } 
                },
            });
        },
        async initCamera() {
            this.cameraStream = await navigator.mediaDevices.getUserMedia({ video: true });
            this.$refs.video.srcObject = this.cameraStream;
        },
        async captureFrame() {
             if (!this.type) {
                return
            }
            const type = this.type;
            const video = this.$refs.video;
            const canvas = document.createElement('canvas');
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

            const blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/jpeg'));
            const formData = new FormData();
            formData.append('image', blob, 'capture.jpg');
            formData.append('type', type); 
            formData.append('device', this.deviceId); 
            formData.append('code', this.code); 
            formData.append('option', 'dtr'); 

            try {
                this.isScanning = true;
                const res = await axios.post('/recognize', formData); 
                const data = res.data;

                // Force Vue to detect change even if repeated status
                this.status = null;
                await this.$nextTick();
                this.status = data.info;

                // Update employee only if not an error
                if (data.info === 'New' || data.info === 'Success' || data.info === 'Duplicate' || data.info === 'Disabled Overlap' || data.info === 'Disabled AM') {
                    this.employee = data.data ? { ...data.data } : null;
                    this.user = this.employee;

                    if(data.info == 'Duplicate'){
                        this.$refs.errorBuzz.play()
                        setTimeout(() => {
                            this.speak("Duplicate attendance detected ")
                        }, 600)
                    };
                    if(data.info == 'Disabled Overlap'){
                        this.$refs.errorBuzz.play()
                        setTimeout(() => {
                            this.speak("You cannot time in because you have already timed out for this period.") 
                        }, 600)
                    }
                    if(data.info == 'Disabled AM'){
                        this.$refs.errorBuzz.play()
                        setTimeout(() => {
                            this.speak("You cannot time in because its already PM period.") 
                        }, 600)
                    }
                    if (data.info === 'New' || data.info === 'Success') {
                        // Add to the list only for new/success entries
                        this.$refs.successBeep.play();
                        setTimeout(() => {
                            this.speak("Your attendance has been recorded successfully.")
                        }, 600)
                        this.lists = [this.employee, ...this.lists];
                    }
                }
                this.resetStatusTimer();
            }catch (e) {
                this.$refs.errorBuzz.play()
                setTimeout(() => {
                    this.speak("Employee not found.")
                }, 600)
                this.status = 'Error';
                this.type = null;
                this.resetStatusTimer();
                setTimeout(() => {
                    this.isScanning = false;
                }, 2000);
            }finally {
                this.type = null;
                setTimeout(() => {
                    this.isScanning = false;
                }, 2000);
            }

        },
        speak(text) {
            const message = new SpeechSynthesisUtterance(text)
            message.lang = "en-US"
            speechSynthesis.speak(message)
        }
    }
}
</script>
<style>
.nav-pills .nav-link {
    font-weight: bold;
    font-size: 16px;
}
.qr-child {
    padding-top: 8px;
    padding-left: 8px;
    padding-bottom: 8px;
    width: 100%;
    height: 100%;
    object-fit: cover;   
}
.table-responsive {
    min-height: 200px;
}
.flash-overlay{
    position: fixed;
    top:0;
    left:0;
    width:100%;
    height:100%;
    pointer-events:none;
    opacity:0;
    transition: opacity 0.2s ease;
    z-index:9999;
}

.flash-success{
    background:rgba(0,255,0,0.25);
    opacity:1;
}

.flash-error{
    background:rgba(255,0,0,0.25);
    opacity:1;
}
</style>