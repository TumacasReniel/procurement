<template>
    <b-modal body-class="p-0" header-class="p-0" hide-footer class="v-modal-custom" content-class="border-0 overflow-hidden" size="xl" centered no-close-on-backdrop hide-header-close>
        <BRow class="g-0">
            <BCol lg="9">
                <div class="modal-body p-5">
                    <h2 class="lh-base fw-semibold fs-22">Share Your Voice <span class="text-danger">DOST-IX</span> | Help Shape Our Workplace!</h2>
                    <p class="text-muted fs-12 mb-4">Take a moment to complete the Semester Morale Survey and let us know how we can improve your experience. <br /> Your feedback matters—submit your entry today.</p>

                    <div class="border rounded no-border mb-3">
                        <ul class="list-group list-group-flush" style="height: 300px; overflow: auto;">
                            <li class="list-group-item" :class="(list.rating) ? 'bg-dark-subtle' : ''" v-for="(list,index) in questions" v-bind:key="index" style="cursor: pointer;">
                                <div class="text-center">
                                    <p class="fs-12 text-primary fw-semibold">{{list.question}}</p>
                                    <div v-for="(smiley, index1) in smileys" :key="index1" class="smiley-container">
                                        <i :class="[smiley.icon]" 
                                          @click="selectScore(index,smiley.score,smiley.color)"
                                        :style="(list.color === smiley.color) ? 'color: '+ smiley.color : 'color: #777777'"
                                        style="font-size: 25px;"></i>
                                        <span class="smiley-text text-muted mt-0 fs-10">{{ smiley.text }}</span>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </div>
                    <div class="d-flex mb-n3 mt-4">
                        <div class="flex-shrink-0 me-3">
                            <span class="text-muted fs-13">You have answered <span class="fw-semibold" :class="(ratedQuestionsCount == questions.length) ? 'text-success' : 'text-danger'">{{ ratedQuestionsCount }}</span> out of <span class="text-primary fw-semibold">{{questions.length}}</span> questions in the morale survey.</span>
                        </div>
                        <div class="flex-grow-1"></div>
                        <div class="flex-shrink-0">
                            <BButton @click="submit()" :disabled="(ratedQuestionsCount == questions.length) ? false : true" variant="primary" class="mt-n1" type="button" id="button-addon1">Submit Morale Survey</BButton>
                        </div>
                    </div>
                </div>
            </BCol>

            <BCol lg="3">
                <div class="subscribe-modals-cover h-100">
                    <img src="images/auth-one-bg.jpg" alt=""
                    class="h-100 w-100 object-fit-cover"
                    style="clip-path: polygon(100% 0%, 100% 100%, 100% 100%, 0% 100%, 25% 50%, 0% 0%);">
                </div>
            </BCol>
        </BRow>
    </b-modal>
</template>
<script>
import { useForm } from '@inertiajs/vue3';
export default {
    props: ['questions'],
    data(){
        return {
            tsr_id: null,
            comment: null,
            attribute: null,
            progressbarvalue: 0,
            activeTab: 1,
            smileys: [
                { icon: 'bx bxs-happy-beaming', active: false, score: 5, color: '#ee9f03', text: 'Strongly Agree'},
                { icon: 'bx bxs-smile', active: false, score: 4, color: '#feea1a', text: 'Agree' },
                { icon: 'bx bxs-meh', active: false, score: 3, color: '#5cfd9b', text: 'Neutral' },
                { icon: 'bx bxs-sad', active: false, score: 2, color: '#ff8f01', text: 'Disagree' },
                { icon: 'bx bxs-angry', active: false, score: 1, color: '#dd0000', text: 'Strongly Disagree' }
            ],
            form: useForm({
                questions: null,
                option: 'answers'
            }),
        }
    },
    computed: {
        ratedQuestionsCount() {
            return this.questions.filter(q => q.rating !== null).length;
        }
    },
    methods: { 
        selectScore(index,rate,color,) {
            this.questions[index].rating = rate;
            this.questions[index].color = color;
        },
        submit(){
            this.form.questions = this.questions;
            this.form.post('/surveys',{
                preserveScroll: true,
                onSuccess: (response) => {
                    this.form.reset();
                    this.hide();
                    this.$emit('success',true);
                },
            });
        },
        hide(){
            this.showModal = false;
        }
    }
}
</script>
<style>
.smiley-container {
  display: inline-block;
  text-align: center;
  width: 100px;
  cursor: pointer;
}
.smiley-container i:hover {
    transform: scale(1.3);
    color: var(--hover-color);
}
.smiley-text {
  display: block;
  margin-top: 5px;
}
</style>