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
    <form class="customform" @submit.prevent="submitOnEnter">
      <div class="m-5 text-center">
        <b>
            <!-- Purchase Order -->
          <span v-if="type == 'PO' || type == 'PO Not Conformed'">
            Purchase Order No.:
          </span>
          
          <span class="text-danger flex">{{ form.code }}</span></b>
          <br>
        <span v-if="actionType !== 'revert' && form.status?.name === 'Delivered/For Inspection'">
            Update status from
            <span :class="form.status?.color">"{{ form.status?.name }}"</span>
            to
            <span class="text-primary">"Completed"</span>
            ?
          </span>
          <span v-if="actionType === 'revert'">
            Revert status from
            <span :class="form.status?.color">"{{ form.status?.name }}"</span>
            to
            <span class="text-primary">"{{ revertTargetStatus }}"</span>
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
            @keyup.enter.exact.prevent="submitOnEnter"
            class="text-center  fw-bold"
          ></b-form-input>
          <small v-if="confirmTextError" class="text-danger">
            Please type "confirm" to proceed
          </small>
        </div>
      </div>
    </form>
    <template v-slot:footer>
      <b-button type="button" @click="hide()" variant="light" block>Close</b-button>
      <b-button type="button" @click="submit()" variant="primary" :disabled="form.processing || !isConfirmed" block
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
      actionType: "update",
      type: null,
      showModal: false,
    };
  },

  methods: {
    show(data, type, actionType = "update") {
      this.form.id = data.id;
      this.form.code = data.code;
      this.form.status = data.status;
      this.actionType = actionType;
      this.form.option = actionType === "revert" ? "revert_status" : "update_status";
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
    handleConfirmInput() {
      this.confirmTextError = this.confirmText.toLowerCase() !== "confirm";
    },
    submitOnEnter() {
      if (this.isConfirmed && !this.form.processing) {
        this.submit();
      }
    },
    hide() {
      this.showModal = false;
      this.confirmText = "";
      this.confirmTextError = false;
      this.form.option = "update_status";
      this.actionType = "update";
    },
  },
  computed: {
    isConfirmed() {
      return this.confirmText.toLowerCase() === "confirm";
    },
    revertTargetStatus() {
      if (this.form.status?.name === "Completed") return "Delivered/For Inspection";
      if (this.form.status?.name === "Delivered/For Inspection") return "Conformed";
      return "Previous Status";
    },
  },
};
</script>
