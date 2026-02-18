<template>
  <b-modal
    v-model="showModal"
    style="--vz-modal-width: 600px"
    header-class="p-3 bg-light"
    title="Cancel PR"
    class="v-modal-custom"
    modal-class="zoomIn"
    centered
    no-close-on-backdrop
  >
    <form class="customform">
      <div class="m-5 text-center">
            Are you sure you want to cancel this <span class="text-danger flex">{{ form.code }}</span>
    
      </div>
    </form>
    <template v-slot:footer>
      <b-button @click="hide()" variant="light" block>Close</b-button>
      <b-button @click="submit()" variant="primary" :disabled="form.processing" block
        >Update</b-button
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
        option: "cancel",
      }),
      showModal: false,
    };
  },
  methods: {
    show(data) {
      this.form.id = data.id;
      this.form.code = data.code;
      this.showModal = true;
    },
    submit() {
       this.form.put("/faims/procurements/" + this.form.id, {
          preserveScroll: true,
          onSuccess: (response) => {
            this.$emit("update", true);
            this.hide();
          },
        });
    },
    handleInput(field) {
      this.form.errors[field] = false;
    },
    hide() {
      this.showModal = false;
    },
  },
};
</script>
