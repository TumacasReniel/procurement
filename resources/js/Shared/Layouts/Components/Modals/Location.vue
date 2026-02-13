<template>
    <b-modal v-model="showModal" style="--vz-modal-width: 550px;" header-class="p-3 bg-light" :title="(editable) ? 'Update Address' : 'Add Address'" class="v-modal-custom" modal-class="zoomIn" centered no-close-on-backdrop>
        <form class="customform">
            <BRow>
                <BCol lg="12">
                    <BRow class="g-3">
                        <BCol lg="6" class="mt-1">
                            <InputLabel value="Region"/>
                            <Multiselect :options="regions" object v-model="form.region" label="name" :searchable="true" placeholder="Select Region" />
                        </BCol>
                        <BCol lg="6" class="mt-1">
                            <InputLabel value="Province"/>
                            <Multiselect :options="provinces" object v-model="form.province" label="name" :searchable="true" placeholder="Select Province" />
                        </BCol>
                        <BCol lg="6" class="mt-1">
                            <InputLabel value="Municipality" :message="form.errors.municipality_code"/>
                            <Multiselect :options="municipalities" object v-model="form.municipality" label="name" :searchable="true" placeholder="Select Municipality" />
                        </BCol>
                        <!-- $page.props.user.data.agency_id == 11 -->
                         <BCol v-if="districts.length > 0 && $page.props.user.data.agency_id == 11" lg="6" class="mt-1">
                            <InputLabel value="District" :message="form.errors.district_code"/>
                            <Multiselect :options="districts" object v-model="form.district" label="name" :searchable="true" placeholder="Select District" />
                        </BCol>
                        <BCol :lg="(districts.length > 0 && $page.props.user.data.agency_id == 11) ? 12 : 6" class="mt-1">
                            <InputLabel value="Barangay" :message="form.errors.barangay_code"/>
                            <Multiselect :options="barangays" object v-model="form.barangay" label="name" :searchable="true" placeholder="Select Barangay" />
                        </BCol>
                        <BCol lg="12" class="mt-1">
                            <InputLabel value="Street, Landmark, Block, Lot, Unit (Optional)"/>
                            <TextInput v-model="form.address" type="text" class="form-control" placeholder="Please enter st.,road" @input="handleInput('address')" :light="true" />
                        </BCol>
                    </BRow>  
                </BCol>
                <BCol lg="12">
                    <div class="mt-2">
                        <Map @set="handleCoordinates" ref="map" class="leaflet-map" style="height: 200px;"/>
                    </div>
                </BCol>
            </BRow>
        </form>
        <template v-slot:footer>
            <b-button @click="hide()" variant="light" block>Cancel</b-button>
            <b-button @click="submit('ok')" variant="primary" :disabled="form.processing || !isFormValid" block>Submit</b-button>
        </template>
    </b-modal>
</template>
<script>
import { useForm } from '@inertiajs/vue3';
import Map from './Map.vue';
import Multiselect from "@vueform/multiselect";
import InputLabel from '@/Shared/Components/Forms/InputLabel.vue';
import TextInput from '@/Shared/Components/Forms/TextInput.vue';
export default {
    components: {InputLabel, TextInput, Multiselect, Map },
    props: ['regions'],
    data(){
        return {
            currentUrl: window.location.origin,
            form: useForm({
                address: null,
                region: null,
                province: null,
                municipality: null,
                longitude: null,
                latitude: null,
                barangay: null
            }),
            coordinates: {},
            provinces: [],
            municipalities: [],
            barangays: [],
            districts: [],
            index: null,
            showModal: false,
            editable: false,
            selected: null,
        }
    },
    watch: {
        "form.region"(newVal){
            if(!newVal){
                this.form.province = null;
                this.form.municipality = null;
                this.form.barangay = null;
            }
            this.fetchProvince(newVal);
        },
        "form.province"(newVal){
            if(!newVal){
                this.form.municipality = null;
                this.form.barangay = null;
            }
            this.fetchMunicipality(newVal);
        },
        "form.municipality"(newVal){
            if(!newVal){
                this.form.barangay = null;
            }
            this.fetchBarangay(newVal);
        }
    },
    computed: {
        isFormValid() {
            return this.form.region && this.form.province && this.form.municipality && this.form.barangay;
        }
    },
    methods: { 
        handleCoordinates(coords) {
            this.coordinates = coords;
            this.form.longitude = this.coordinates.lng;
            this.form.latitude = this.coordinates.lat;
        },
        openEdit(region){
            this.$refs.map.view();
            this.form.region = region;
            this.fetchProvince(region);
            this.showModal = true;
        },  
        submit(){
            const address = `${this.form.address}, ${this.form.barangay.name}, ${this.form.municipality.name}, ${this.form.province.name}, ${this.form.region.name}`;
            this.$emit('submit', {
                address: address,
                index: this.index,
                form: {
                    info: this.form.address,
                    region: this.form.region,
                    province: this.form.province,
                    municipality: this.form.municipality,
                    barangay: this.form.barangay,
                    longitude: this.form.longitude,
                    latitude: this.form.latitude,
                }
            });

            this.form.region = null;
            this.form.province = null;
            this.form.municipality = null;
            this.form.barangay = null;
            this.form.address = null;
            this.hide();
        },
        fetchProvince(code){
            axios.get('/search',{
                params: {
                    option: 'provinces',
                    code: code
                }
            })
            .then(response => {
                this.provinces = response.data;
            })
            .catch(err => console.log(err));
        },
        fetchMunicipality(code){
            axios.get('/search',{
                params: {
                    option: 'municipalities',
                    code: code
                }
            })
            .then(response => {
                this.municipalities = response.data;
            })
            .catch(err => console.log(err));
        },
        fetchBarangay(code){
            axios.get('/search',{
                params: {
                    option: 'barangays',
                    code: code
                }
            })
            .then(response => {
                this.barangays = response.data;
            })
            .catch(err => console.log(err));
        },
        emptyMap(){
            this.$refs.map.empty();
        },
        handleInput(field) {
            this.form.errors[field] = false;
        },
        hide(){
            this.editable = false;
            this.showModal = false;
        }
    }
}
</script>