<template>
  <b-modal
    v-model="showModal"
    style="--vz-modal-width: 820px"
    header-class="p-3 bg-light"
    :title="editable ? 'Update Request Type' : 'Create Request Type'"
    class="v-modal-custom"
    modal-class="zoomIn"
    centered
    no-close-on-backdrop
  >
    <form class="customform">
      <div class="row">
        <div class="col-md-12 mb-3">
          <InputLabel for="name" value="Name" />
          <TextInput
            id="name"
            v-model="form.name"
            type="text"
            placeholder="Request type name"
          />
        </div>
        <div class="col-md-12 mb-3">
          <InputLabel for="default_text" value="Default Text" />
          <textarea
            id="default_text"
            v-model="form.default_text"
            class="form-control"
            rows="2"
            placeholder="Default text"
          ></textarea>
        </div>
        <div class="col-md-12 mb-3">
          <InputLabel for="required_documents" value="Required Documents" />
          <Multiselect
            :options="dropdowns.documents"
            v-model="form.required_documents"
            label="name"
            placeholder="Select required documents"
            mode="tags"
            :searchable="true"
          />
        </div>
        <div class="col-md-12 mb-3">
          <div class="d-flex align-items-center justify-content-between">
            <InputLabel for="is_active" value="Active Status" class="mb-0" />
            <div class="form-check form-switch" style="min-height: auto; padding-left: 0;">
              <input
                type="checkbox"
                class="form-check-input"
                id="is_active"
                v-model="form.is_active"
                true-value="1"
                false-value="0"
                style="cursor: pointer;"
              />
              <label class="form-check-label text-muted fs-12" for="is_active">
                {{ form.is_active == 1 ? 'Active' : 'Inactive' }}
              </label>
            </div>
          </div>
        </div>
      </div>
    </form>
    <template v-slot:footer>
      <b-button @click="hide()" variant="light" block>Close</b-button>
      <b-button @click="submit()" variant="success" block :disabled="saving || !form.name">
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
  props: ["dropdowns"],
  data() {
    return {
      showModal: false,
      editable: false,
      form: useForm({
          id: null,
          name: null,
          default_text: null,
          required_documents: [],
          is_active: 1,
      }),
    };
  },
  methods: {
    show() {
      this.editable = false;
      this.reset();
      this.showModal = true;
    },
    edit(data) {
      this.form.id = data.id;
      this.form.name = data.name;
      this.form.default_text = data.default_text || "";
      // Extract document IDs from the nested documents structure
      this.form.required_documents = Array.isArray(data.documents)
        ? data.documents.map(d => d.document.id)
        : [];
      this.form.is_active = data.is_active ? 1 : 0;
      this.editable = true;
      this.showModal = true;
    },
    hide() {
      this.showModal = false;
      this.editable = false;
      this.reset();
    },
    reset() {
      this.form.reset();
      this.form.id = null;
      this.form.name = "";
      this.form.default_text = "";
      this.form.required_documents = [];
      this.form.is_active = 1;
    },
     submit() {
      if (this.editable) {
        this.form.put(`/faims/finance-request-types/${this.form.id}`, {
          preserveScroll: true,
          onSuccess: (response) => {
            this.$emit("update", true);
            this.hide();
          },
        });
      } else {
        this.form.post("/faims/finance-request-types", {
          preserveScroll: true,
          onSuccess: (response) => {
            this.$emit("update", true);
            this.hide();
          },
        });
      }
    },
  },
};
</script>
