<template>
  <b-modal
    v-model="showModal"
    style="--vz-modal-width: 600px"
    header-class="p-3 bg-light"
    title="Delete Record"
    class="v-modal-custom"
    modal-class="zoomIn"
    centered
    no-close-on-backdrop
  >
    <form class="customform">
      <div class="m-5 text-center">
        
        <div>
            Are you sure you want to delete this record of 
            <span class="text-primary">
                "{{ form.title }}: 
                <span class="text-warning">{{ form.code }}" </span>
            </span>
        </div>

            
      </div>
    </form>
    <template v-slot:footer>
      <b-button @click="hide()" variant="light" block>Close</b-button>
      <b-button @click="submit()" variant="danger" :disabled="form.processing" block
        >Delete</b-button
      >
    </template>
  </b-modal>
</template>
<script>
import { useForm } from "@inertiajs/vue3";
import InputLabel from "@/Shared/Components/Forms/InputLabel.vue";
import TextInput from "@/Shared/Components/Forms/TextInput.vue";

export default {
  props: [],
  components: { InputLabel, TextInput },
  data() {
    return {
      currentUrl: window.location.origin,
      selected: null,
      form: useForm({
        id: null,
        code: null,
        title: null 
      }),
      type: null,
      showModal: false,
    };
  },
  methods: {
    show(data, title) {
      this.form.id = data.id;
      this.form.code = data.code,
      this.form.title = title;
      this.showModal = true;
    },

    submit() {
        if(  this.form.title === "Request for Quotation"){
            this.form.delete("/faims/quotations/" + this.form.id, {
            preserveScroll: true,
            onSuccess: (response) => {
                this.$emit("delete", true);
                this.hide();
            },
            });
        }
      
    },


    hide() {
      this.showModal = false;
    },
  },
};
</script>
