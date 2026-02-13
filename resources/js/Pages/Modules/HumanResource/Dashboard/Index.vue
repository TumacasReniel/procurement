<template>
    <Head title="Dashboard"/>
    <PageHeader title="Dashboard" pageTitle="List" />
    <b-row class="g-2 mb-2 mt-n2">
        <b-col lg="12">
            <div class="input-group mb-1">
                <span class="input-group-text"> <i class="ri-search-line search-icon"></i></span>
                <input type="text" placeholder="Search Request" class="form-control" style="width: 20%;">
                <Multiselect class="white" style="width: 15%;" :options="types" v-model="laboratory" label="name" :allow-empty="false" :searchable="true" placeholder="Select Laboratory" />
                <Multiselect v-if="by == 'By Semester'" class="white" style="width: 13%;" :options="semesters" v-model="semester" label="name" :allow-empty="false" :searchable="true" placeholder="Select Month" />
                <Multiselect v-if="by == 'By Quarter'" class="white" style="width: 13%;" :options="quarters" v-model="quarter" label="name" :allow-empty="false" :searchable="true" placeholder="Select Month" />
                <Multiselect v-if="by == 'By Month'" class="white" style="width: 13%;" :options="months" v-model="month" label="name" :allow-empty="false" :searchable="true" placeholder="Select Month" />
                <Multiselect class="white" style="width: 12%;" :options="['By Month','By Quarter','By Semester']" v-model="by" label="name" :allow-empty="false" :searchable="true" placeholder="Filter By" />
                <Multiselect class="white" style="width: 15%;" :options="years" v-model="year" label="name" :searchable="true" placeholder="Select Year" />
                <b-button type="button" variant="primary"> Filter Data </b-button>
            </div>
        </b-col>
    </b-row>
    <hr class="text-muted"/>
    <BRow class="g-3" style="height: calc(100vh - 300px); overflow: auto;">
        <BCol xl="4">
            <Employee :employee="employee"/>
        </BCol>
        <BCol xl="8">
            <Bar ref="bar"/>
        </BCol>
        <BCol xl="4" class="mt-n1">
            <Absent :absences="absences"/>
        </BCol>
        <BCol xl="4" class="mt-n1">
            <Late :lates="lates"/>
        </BCol>
    </BRow>

</template>
<script>
import Bar from './Components/Bar.vue';
import Employee from './Components/Employee.vue';
import Late from './Components/Late.vue';
import Absent from './Components/Absent.vue';
import Multiselect from "@vueform/multiselect";
import PageHeader from '@/Shared/Components/PageHeader.vue';
export default {
    props: ['employee','counts','divisions','years'],
    components: { PageHeader, Multiselect, Employee, Bar, Absent, Late },
    data(){
        return {
            months: ['January','February','March','April','May','June','July','August','September','October','November','December'],
            quarters: ['1st Quater','2nd Quarter','3rd Quarter','4th Quarter'],
            semesters: ['1st Semester','2nd Semester'],
            absences: [],
            lates: [],
            semester: null,
            quarter: null,
            month: null,
            by: null,
        }
    },
    created(){
        this.fetch();
    },
    computed: {
        filters() {
            return {
                year: this.year,
                quarter: this.quarter,
                semester: this.semester,
                month: this.month
            };
        }
    },
    watch: {
        filters: {
            handler() {
                this.fetch();
            },
            deep: true,
        },
        year(newVal) {
            this.$refs.bar.updateYear(newVal);
        }
    },
    methods: {
        fetch(){
            axios.get('/humanresource',{
                params : {
                    month: this.month,
                    year: this.year,
                    semester: this.semester,
                    quarter: this.quarter,
                    by: this.by,
                    option: 'top'
                }
            })
            .then(response => {
                this.absences = response.data.absences; 
                this.lates = response.data.lates;
            })
            .catch(err => console.log(err));
        }
    }
}
</script>