<template>
  <b-modal
    v-model="modalShow"
    header-class="p-3"
    title="Create SPP"
    size="md"
    class="v-modal-custom"
    modal-class="zoomIn"
    centered
    no-close-on-backdrop
  >
    <form class="customform">
      <BRow>
        <BCol lg="12" class="mt-2">
          <label class="form-label">Plan Year</label>
          <Multiselect
            :options="yearOptions"
            v-model="form.year"
            label="name"
            value-prop="value"
            :searchable="true"
            :class="{ 'is-invalid': form.errors.year || form.errors.plan_type }"
            placeholder="Select Year"
          />
          <div v-if="form.errors.year || form.errors.plan_type" class="invalid-feedback d-block">
            {{ form.errors.year || form.errors.plan_type }}
          </div>
        </BCol>

        <BCol lg="12" class="mt-2">
          <label class="form-label">Unit</label>
          <Multiselect
            :options="unitOptions"
            v-model="form.unit_id"
            label="name"
            value-prop="value"
            :searchable="true"
            :class="{ 'is-invalid': form.errors.unit_id }"
            placeholder="Select Unit"
          />
          <div v-if="form.errors.unit_id" class="invalid-feedback d-block">
            {{ form.errors.unit_id }}
          </div>
        </BCol>

        <BCol lg="12" class="mt-3">
          <label class="form-label">Supporting Document</label>
          <FileDropzone
            :file="form.attachment_file"
            :invalid="!!form.errors.attachment_file"
            accept=".pdf,.jpg,.jpeg,.png"
            :allowed-extensions="['pdf', 'jpg', 'jpeg', 'png']"
            :max-size-mb="10"
            invalid-type-message="Please attach a PDF or image file only."
            invalid-size-message="Please attach a file up to 10 MB only."
            title="Drop attachment here or click to browse"
            hint="PDF or image files only, up to 10 MB"
            @selected="form.attachment_file = $event; form.clearErrors('attachment_file')"
            @rejected="form.setError('attachment_file', $event.message)"
            @remove="form.attachment_file = null"
          />
          <div v-if="form.errors.attachment_file" class="invalid-feedback d-block">
            {{ form.errors.attachment_file }}
          </div>
        </BCol>
      </BRow>
    </form>

    <template v-slot:footer>
      <b-button @click="$emit('close')" variant="light" block>Close</b-button>
      <b-button
        @click="$emit('submit')"
        variant="warning"
        :disabled="form.processing || !form.unit_id || !form.year || !form.attachment_file"
        block
      >
        {{ form.processing ? "Creating..." : "Create SPP" }}
      </b-button>
    </template>
  </b-modal>
</template>

<script>
import Multiselect from "@vueform/multiselect";
import FileDropzone from "@/Shared/Components/Forms/FileDropzone.vue";

export default {
  components: { Multiselect, FileDropzone },
  props: {
    modelValue: { type: Boolean, default: false },
    form: { type: Object, required: true },
    yearOptions: { type: Array, default: () => [] },
    unitOptions: { type: Array, default: () => [] },
  },
  emits: ["update:modelValue", "close", "submit"],
  computed: {
    modalShow: {
      get() {
        return this.modelValue;
      },
      set(value) {
        this.$emit("update:modelValue", value);
      },
    },
  },
};
</script>
