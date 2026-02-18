<template>
  <b-modal
    v-model="showModal"
    style="--vz-modal-width: 600px"
    header-class="p-3 bg-light"
    title="Update Status"
    class="v-modal-custom"
    modal-class="zoomIn"
    centered
    no-close-on-backdrop
  >
    <form class="customform">
      <div class="m-5 text-center">
        <b>
            <!-- Purchase Order -->
          <span v-if="type == 'PO' || type == 'PO Not Conformed'">
            Purchase Order No.:
          </span>
          
          <span class="text-danger flex">{{ form.code }}</span></b>
          <br>
        <span v-if="form.status?.name === 'Delivered/For Inspection'">
            Update status from
            <span :class="form.status?.color">"{{ form.status?.name }}"</span>
            to
            <span class="text-primary">"Completed"</span>
            ?
          </span>
          <br />

        <div class="mt-4 p-3 border rounded bg-light ">
          <p class="mb-2 fw-bold text-danger">
            Type <span class="text-primary">"confirm"</span> to proceed:
          </p>
          <b-form-input
            v-model="confirmText"
            placeholder="Type confirm here..."
            :class="{ 'is-invalid': confirmTextError }"
            @input="handleConfirmInput"
            class="text-center  fw-bold"
          ></b-form-input>
          <small v-if="confirmTextError" class="text-danger">
            Please type "confirm" to proceed
          </small>
        </div>
      </div>
    </form>
    <template v-slot:footer>
      <b-button @click="hide()" variant="light" block>Close</b-button>
      <b-button @click="submit()" variant="primary" :disabled="form.processing || !isConfirmed" block
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
        confirmText: "",
      confirmTextError: false,
      form: useForm({
        id: null,
        code: null,
        status: null,
        comment: null,
        option: "update_status",
      }),
      type: null,
      showModal: false,
    };
  },

  computed: {
    isConfirmed() {
      return this.confirmText.toLowerCase() === "confirm";
    },
  },

  methods: {
    show(data, type) {
      this.form.id = data.id;
      this.form.code = data.code;
      this.form.status = data.status;
      this.type = type;
      this.showModal = true;
    },
    submit() {
      if (this.type == "BACResolution") {
        this.form.put("/faims/bac-resolutions/" + this.form.id, {
          preserveScroll: true,
          onSuccess: (response) => {
            this.$emit("add", true);
            this.hide();
          },
        });
      } else if (this.type == "NOA" || this.type == "NOA Not Conformed") {
        if (this.type == "NOA Not Conformed") {
          this.form.option = "not_conformed";
        }
        this.form.put("/faims/notice-of-awards/" + this.form.id, {
          preserveScroll: true,
          onSuccess: (response) => {
            this.$emit("add", true);
            this.hide();
          },
        });
      }
      else if (this.type == "PO" || this.type == "PO Not Conformed") {
        if (this.type == "PO Not Conformed") {
          this.form.option = "not_conformed";
        }
        this.form.put("/faims/purchase-orders/" + this.form.id, {
          preserveScroll: true,
          onSuccess: (response) => {
            this.$emit("add", true);
            this.hide();
          },
        });
      }
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
