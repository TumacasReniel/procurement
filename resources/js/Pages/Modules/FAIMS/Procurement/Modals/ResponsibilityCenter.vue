<template>
  <b-modal
    v-model="showModal"
    style="--vz-modal-width: 500px"
    header-class="p-3 bg-light"
    :title="form.id ? 'Update Responsibility Center' : 'Create Responsibility Center'"
    class="v-modal-custom"
    modal-class="zoomIn"
    centered
    no-close-on-backdrop
  >
    <form class="customform" @submit.prevent="submit">
      <div class="row">
        <div class="col-md-12 mb-3">
        <InputLabel for="unit" value="Unit" />
        <Multiselect
                :options="dropdowns.units"
                v-model="form.list_unit_id"
                :searchable="true"
                label="name"
                placeholder="Select Unit"
            />
        </div>

        <div class="col-md-12 mb-3">
          <InputLabel for="code" value="Code" />
          <TextInput
            id="code"
            v-model="form.code"
            type="text"
            placeholder="Enter Responsibility Center Code"
            :class="{ 'is-invalid': form.errors.code }"
          />
          <div v-if="form.errors.code" class="invalid-feedback d-block">
            {{ form.errors.code }}
          </div>
        </div>
      </div>
    </form>
    <template v-slot:footer>
      <b-button @click="hide()" variant="light" block>Close</b-button>
      <b-button
        @click="submit()"
        variant="primary"
        :disabled="form.processing"
        block
      >
        {{ form.id ? "Update" : "Create" }}
      </b-button>
    </template>
  </b-modal>
</template>

<script>
import { useForm } from "@inertiajs/vue3";
import InputLabel from "@/Shared/Components/Forms/InputLabel.vue";
import TextInput from "@/Shared/Components/Forms/TextInput.vue";
import Multiselect from "@vueform/multiselect";

export default {
  props: ["dropdowns"],
  components: { InputLabel, TextInput, Multiselect },
  data() {
    return {
      showModal: false,
      form: useForm({
        id: null,
        list_unit_id: "",
        code: "",
      }),
    };
  },
  methods: {
    show(data = null) {
      if (data) {
        this.form.id = data.id;
        this.form.list_unit_id = data.list_unit_id;
        this.form.code = data.code;
      } else {
        this.form.reset();
        this.form.id = null;
      }
      this.showModal = true;
    },
    submit() {
      if (this.form.id) {
        // Update
        this.form.put(`/faims/responsibility-centers/${this.form.id}`, {
          preserveScroll: true,
          onSuccess: () => {
            this.$emit("update", true);
            this.hide();
          },
        });
      } else {
        // Create
        this.form.post("/faims/responsibility-centers", {
          preserveScroll: true,
          onSuccess: () => {
            this.$emit("update", true);
            this.hide();
          },
        });
      }
    },
    hide() {
      this.showModal = false;
      this.form.reset();
      this.form.errors = {};
    },
  },
};
</script>
