<template>
  <b-modal
    v-model="showModal"
    style="--vz-modal-width: 820px"
    header-class="p-3 bg-light"
    :title="editable ? 'Update Creditor' : 'Create Creditor'"
    class="v-modal-custom"
    modal-class="zoomIn"
    centered
    no-close-on-backdrop
  >
    <form class="customform" >
      <div class="row">
        <div class="col-md-12 mb-3">
          <InputLabel for="name" value="Name" />
          <TextInput
            id="name"
            v-model="form.name"
            type="text"
            class="form-control"
            placeholder="Enter Creditor Name"
            required
          />
        </div>
        <div class="col-md-12 mb-3">
          <InputLabel for="account_code" value="Account Code" />
          <TextInput
            id="account_code"
            v-model="form.account_code"
            type="text"
            class="form-control"
            placeholder="Enter Account Code"
            required
          />
        </div>
        <div class="col-md-12 mb-3">
          <InputLabel for="type_id" value="Type" />
          <Multiselect
            v-model="form.type_id"
            :options="dropdowns.creditor_types"
            label="name"
            placeholder="Select Creditor Type"
            :searchable="true"
            mode="single"
            :close-on-select="true"
          />
        </div>
      </div>
    </form>
    <template v-slot:footer>
      <b-button @click="hide()" variant="light" block>Close</b-button>
      <b-button @click="submit()" variant="success" block :disabled="form.processing">
        {{ editable ? "Update" : "Create" }}
      </b-button>
    </template>
  </b-modal>
</template>

<script>
import InputLabel from "@/Shared/Components/Forms/InputLabel.vue";
import TextInput from "@/Shared/Components/Forms/TextInput.vue";
import Multiselect from "@/Shared/Components/Forms/Multiselect.vue";
import { useForm } from '@inertiajs/vue3';

export default {
  components: { InputLabel, TextInput, Multiselect },
  props: {
    dropdowns: {
      type: Object,
      default: () => ({}),
    },
  },
  data() {
    return {
      showModal: false,
      editable: false,
      form: useForm({
        id: null,
        name: null,
        account_code: null,
        type_id: null,
      }),
    };
  },
  methods: {
    show() {
      this.editable = false;
      this.reset();
      this.showModal = true;
    },
    hide() {
      this.showModal = false;
      this.editable = false;
      this.reset();
    },
    edit(data) {
      this.form.reset();
      this.form.id = data.id;
      this.form.name = data.name;
      this.form.account_code = data.account_code;
      this.form.type_id = data.type?.id || null;
      this.editable = true;
      this.showModal = true;
    },
    reset() {
      this.form.reset();
      this.form.id = null;
      this.form.name = null;
      this.form.account_code = null;
      this.form.type_id = null;
    },
    submit() {
      if (this.form.name && this.form.account_code) {
        if (this.editable) {
          this.form.put(`/faims/finance-creditors/${this.form.id}`, {
            preserveScroll: true,
            onSuccess: () => {
              this.$emit("add", true);
              this.hide();
            },
          });
        } else {
          this.form.post("/faims/finance-creditors", {
            preserveScroll: true,
            onSuccess: () => {
              this.$emit("add", true);
              this.hide();
            },
          });
        }
      }
    },
  },
};
</script>

<style scoped>
textarea {
  resize: vertical;
}
</style>

