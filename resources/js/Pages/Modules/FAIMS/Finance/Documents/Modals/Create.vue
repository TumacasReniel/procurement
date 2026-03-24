<template>
  <b-modal
    v-model="showModal"
    style="--vz-modal-width: 820px"
    header-class="p-3 bg-light"
    :title="editable ? 'Update Document' : 'Create Document'"
    class="v-modal-custom"
    modal-class="zoomIn"
    centered
    no-close-on-backdrop
  >
    <form class="customform" >
      <div class="row">
        <div class="col-md-12 mb-3">
          <div class="col-md-12 mb-3">
            <InputLabel for="Name" value="Name" />
            <TextInput
              id="name"
              v-model="form.name"
              type="text"
              placeholder="Enter Document Name"
            />
          </div>
           <div class="col-md-12 mb-3">
            <InputLabel for="Description" value="Description" />
            <textarea
              id="description"
              v-model="form.description"
              type="text"
              class="form-control"
              placeholder="Enter Document Description"
            />
          </div>
        </div>
      </div>
    </form>
    <template v-slot:footer>
      <b-button @click="hide()" variant="light" block>Close</b-button>
      <b-button @click="submit()" variant="success" block>
        {{ editable ? "Update" : "Create" }}
      </b-button>
    </template>
  </b-modal>
</template>

<script>
import InputLabel from "@/Shared/Components/Forms/InputLabel.vue";
import TextInput from "@/Shared/Components/Forms/TextInput.vue";
import Multiselect from "@vueform/multiselect";
import { useForm } from '@inertiajs/vue3';


export default {
  components: { InputLabel, TextInput, Multiselect },
  props: {
    requestTypeOptions: {
      type: Array,
      default: () => [],
    },
  },
  data() {
    return {
      showModal: false,
      editable: false,
      form: useForm({
            id: null,
            name: null,
            description: null,
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
      this.form.description = data.description;
      this.editable = true;
      this.showModal = true;
    },
    reset() {
      this.form.reset();
      this.form.id = null;
      this.form.name = null;
      this.form.description = null;
    },
    getNowLocalDateTime() {
      const now = new Date();
      const year = now.getFullYear();
      const month = String(now.getMonth() + 1).padStart(2, "0");
      const day = String(now.getDate()).padStart(2, "0");
      const hours = String(now.getHours()).padStart(2, "0");
      const minutes = String(now.getMinutes()).padStart(2, "0");
      return `${year}-${month}-${day}T${hours}:${minutes}`;
    },
    submit() {
      if (this.editable) {
        this.form.put(`/faims/finance-documents/${this.form.id}`, {
          preserveScroll: true,
          onSuccess: (response) => {
            this.$emit("add", true);
            this.hide();
          },
        });
      } else {
        this.form.post("/faims/finance-documents", {
          preserveScroll: true,
          onSuccess: (response) => {
            this.$emit("add", true);
            this.hide();
          },
        });
      }
    },
  },
};
</script>

<style scoped>
.fund-panel {
  background: #c8f3f5;
  border-radius: 6px;
  padding: 16px 12px;
}
</style>
