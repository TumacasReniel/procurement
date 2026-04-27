<template>
    <b-modal v-model="showModal" style="--vz-modal-width: 750px;" header-class="p-3 bg-light" title="Request" class="v-modal-custom" modal-class="zoomIn" centered no-close-on-backdrop>
        <form class="customform">
            <BRow class="g-3 p-2 mb-4">
                <div class="col-sm-6">
                    <div data-bs-toggle="collapse" data-bs-target="#paymentmethodCollapse.show" aria-expanded="false"
                        aria-controls="paymentmethodCollapse">
                        <div class="form-check card-radio">
                            <input id="paymentMethod01" name="paymentMethod" v-model="selectedType" value="travel" type="radio" class="form-check-input">
                            <label class="form-check-label" for="paymentMethod01">
                                <span class="fs-16 text-muted me-2"><i class="ri-flight-takeoff-fill align-bottom"></i></span>
                                <span class="fs-14 text-wrap">Travel Order</span>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div data-bs-toggle="collapse" data-bs-target="#paymentmethodCollapse" aria-expanded="true"
                        aria-controls="paymentmethodCollapse">
                        <div class="form-check card-radio">
                            <input id="paymentMethod02" name="paymentMethod" v-model="selectedType" value="vehicle" type="radio" class="form-check-input">
                            <label class="form-check-label" for="paymentMethod02">
                                <span class="fs-16 text-muted me-2"><i class="ri-car-fill align-bottom"></i></span>
                                <span class="fs-14 text-wrap">Vehicle Registration</span>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div data-bs-toggle="collapse" data-bs-target="#paymentmethodCollapse.show" aria-expanded="false"
                        aria-controls="paymentmethodCollapse">
                        <div class="form-check card-radio">
                            <input id="paymentMethod03" name="paymentMethod" v-model="selectedType" value="leave" type="radio" class="form-check-input">
                            <label class="form-check-label" for="paymentMethod03">
                                <span class="fs-16 text-muted me-2"><i class="ri-calendar-2-fill align-bottom"></i></span>
                                <span class="fs-14 text-wrap">Leave Request</span>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div data-bs-toggle="collapse" data-bs-target="#paymentmethodCollapse.show" aria-expanded="false"
                        aria-controls="paymentmethodCollapse">
                        <div class="form-check card-radio">
                            <input id="paymentMethod04" name="paymentMethod" v-model="selectedType" value="overtime" type="radio" class="form-check-input">
                            <label class="form-check-label" for="paymentMethod04">
                                <span class="fs-16 text-muted me-2"><i class="ri-alarm-fill align-bottom"></i></span>
                                <span class="fs-14 text-wrap">Render Overtime Service</span>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div data-bs-toggle="collapse" data-bs-target="#paymentmethodCollapse.show" aria-expanded="false"
                        aria-controls="paymentmethodCollapse">
                        <div class="form-check card-radio">
                            <input id="paymentMethod05" name="paymentMethod" v-model="selectedType" value="procurement" type="radio" class="form-check-input">
                            <label class="form-check-label" for="paymentMethod05">
                                <span class="fs-16 text-muted me-2"><i class="ri-shopping-bag-3-fill align-bottom"></i></span>
                                <span class="fs-14 text-wrap">Purchase Request</span>
                            </label>
                        </div>
                    </div>
                </div>
            </BRow>
        </form> 
        <template v-slot:footer>
            <!-- <b-button @click="hide()" variant="light" block>Cancel</b-button> -->
            <b-button @click="continueToForm()" variant="primary" block>Continue</b-button>
        </template>
    </b-modal>
    <Vehicle @success="fetch()" :dropdowns="vehicle_dropdowns" ref="vehicle"/>
    <Travel @success="fetch()" :dropdowns="travel_dropdowns" ref="travel"/>
    <Leave @success="fetch()" :dropdowns="leave_dropdowns" ref="leave"/>
    <Overtime @success="fetch()" :dropdowns="leave_dropdowns" ref="overtime"/>
    <Procurement @success="fetch()" :dropdowns="procurement_dropdowns" ref="procurement"/>
</template>
<script>
import Travel from './Travel.vue';
import Vehicle from './Vehicle.vue';
import Leave from './Leave.vue';
import Overtime from './Overtime.vue';
import Procurement from './Procurement.vue';
export default {
    components : { Travel, Vehicle, Leave, Overtime, Procurement },
    props: ['leave_dropdowns','travel_dropdowns','vehicle_dropdowns','procurement_dropdowns'],
    data(){
        return {
            currentUrl: window.location.origin,
            showModal: false,
            selectedType: null
        }
    },

    methods: { 
        show(){
            this.selectedType = null;
            this.showModal = true;
        },
        continueToForm(){
            if (this.selectedType === 'travel') {
                this.$refs.travel.show();
            } else if (this.selectedType === 'vehicle') {
                this.$refs.vehicle.show();
            } else if (this.selectedType === 'leave') {
                this.$refs.leave.show();
            } else if (this.selectedType === 'overtime') {
                this.$refs.overtime.show();
            } else if (this.selectedType === 'procurement') {
                this.$refs.procurement.show();
            }
            this.hide();
        },
        handleInput(field) {
            this.form.errors[field] = false;
        },
        fetch(){
            this.$emit('update',true);
        },
        hide(){
            this.selectedType = null;
            this.showModal = false;
        }
    }
}
</script>
