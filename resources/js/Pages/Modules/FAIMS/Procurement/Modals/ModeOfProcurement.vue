<template>
  <b-modal
    v-model="showModal"
    style="--vz-modal-width: 650px"
    header-class="p-3 bg-light"
    :title="form.id ? 'Update Mode of Procurement' : 'Create Mode of Procurement'"
    class="v-modal-custom"
    modal-class="zoomIn"
    centered
    no-close-on-backdrop
  >
    <form class="customform" @submit.prevent="submit">
      <div class="row">
        <div class="col-md-12 mb-3">
          <InputLabel for="type" value="IRR Section Reference" />
          <TextInput
            id="type"
            v-model="form.type"
            type="text"
            placeholder="Enter IRR section reference"
            :class="{ 'is-invalid': form.errors.type }"
          />
          <InputError :message="form.errors.type" />
        </div>

        <div class="col-md-12 mb-3">
          <InputLabel for="name" value="Mode of Procurement" />
          <textarea
            id="name"
            v-model="form.name"
            rows="3"
            class="form-control"
            :class="{ 'is-invalid': form.errors.name }"
            placeholder="Enter mode of procurement"
          ></textarea>
          <InputError :message="form.errors.name" />
        </div>

        <div class="col-md-12 mb-3">
          <InputLabel for="others" value="Legal Basis / Notes" />
          <TextInput
            id="others"
            v-model="form.others"
            type="text"
            placeholder="Enter legal basis or notes"
            :class="{ 'is-invalid': form.errors.others }"
          />
          <InputError :message="form.errors.others" />
        </div>

        <div class="col-md-12">
          <div class="form-check form-switch form-switch-md">
            <input
              id="mode-is-active"
              v-model="form.is_active"
              class="form-check-input"
              type="checkbox"
            />
            <label class="form-check-label" for="mode-is-active">
              Active
            </label>
          </div>
          <small class="text-muted d-block mt-1">
            Inactive modes stay in the library but can be visually marked as unavailable.
          </small>
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
import InputError from "@/Shared/Components/Forms/InputError.vue";
import InputLabel from "@/Shared/Components/Forms/InputLabel.vue";
import TextInput from "@/Shared/Components/Forms/TextInput.vue";

export default {
  components: { InputError, InputLabel, TextInput },
  data() {
    return {
      showModal: false,
      form: useForm({
        id: null,
        type: "",
        name: "",
        others: "",
        is_active: true,
      }),
    };
  },
  methods: {
    show(data = null) {
      if (data) {
        this.form.id = data.id;
        this.form.type = this.normalizeOptionalField(data.type);
        this.form.name = data.name || "";
        this.form.others = this.normalizeOptionalField(data.others);
        this.form.is_active = Boolean(data.is_active);
      } else {
        this.form.reset();
        this.form.id = null;
        this.form.is_active = true;
      }

      this.form.clearErrors();
      this.showModal = true;
    },
    submit() {
      const requestOptions = {
        preserveScroll: true,
        onSuccess: () => {
          this.$emit("update", true);
          this.hide();
        },
      };

      const payload = this.form.transform((data) => ({
        ...data,
        is_active: data.is_active ? 1 : 0,
      }));

      if (this.form.id) {
        payload.put(`/faims/modes-of-procurement/${this.form.id}`, requestOptions);
        return;
      }

      payload.post("/faims/modes-of-procurement", requestOptions);
    },
    hide() {
      this.showModal = false;
      this.form.reset();
      this.form.id = null;
      this.form.is_active = true;
      this.form.clearErrors();
    },
    normalizeOptionalField(value) {
      if (!value || value === "n/a") {
        return "";
      }

      return value;
    },
  },
};
</script>
