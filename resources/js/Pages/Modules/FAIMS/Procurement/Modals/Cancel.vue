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
        Are you sure you want to cancel this purchase request
        <span class="text-danger fw-semibold">{{ form.code }}</span>?
      </div>
      <div v-if="form.errors.code" class="px-4 pb-2 text-center text-danger small">
        {{ form.errors.code }}
      </div>
    </form>
    <template v-slot:footer>
      <b-button @click="hide()" variant="light" block>Close</b-button>
      <b-button @click="submit()" variant="danger" :disabled="form.processing" block>
        {{ form.processing ? "Cancelling..." : "Cancel PR" }}
      </b-button>
    </template>
  </b-modal>
</template>
<script>
import { useForm } from "@inertiajs/vue3";

export default {
  props: [],
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
      this.form.reset();
      this.form.clearErrors();
      this.form.id = data.id;
      this.form.code = data.code;
      this.form.option = "cancel";
      this.showModal = true;
    },
    submit() {
      this.form.option = "cancel";

      this.form.put("/faims/procurements/" + this.form.id, {
        preserveScroll: true,
        onSuccess: () => {
          this.$emit("update", true);
          this.hide();
        },
      });
    },
    hide() {
      this.form.reset();
      this.form.clearErrors();
      this.form.option = "cancel";
      this.showModal = false;
    },
  },
};
</script>
