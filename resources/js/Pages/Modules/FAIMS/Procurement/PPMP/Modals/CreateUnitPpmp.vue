<template>
  <b-modal
    v-model="modalShow"
    header-class="p-3"
    title="Create Unit PPMP"
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
            :class="{ 'is-invalid': form.errors.year }"
            placeholder="Select Year"
          />
          <div v-if="form.errors.year" class="invalid-feedback d-block">
            {{ form.errors.year }}
          </div>
        </BCol>

        <BCol lg="12" class="mt-2">
          <label class="form-label">Unit Without PPMP</label>
          <Multiselect
            :options="unitOptions"
            v-model="form.unit_id"
            label="name"
            value-prop="value"
            :searchable="true"
            :loading="loading"
            :class="{ 'is-invalid': form.errors.unit_id }"
            placeholder="Select Unit"
          />
          <div v-if="form.errors.unit_id" class="invalid-feedback d-block">
            {{ form.errors.unit_id }}
          </div>
          <small v-if="!loading && !unitOptions.length" class="text-muted d-block mt-2">
            All active units already have a PPMP for the selected year.
          </small>
        </BCol>
      </BRow>
    </form>

    <template v-slot:footer>
      <b-button @click="$emit('close')" variant="light" block>Close</b-button>
      <b-button
        @click="$emit('submit')"
        variant="primary"
        :disabled="form.processing || !form.unit_id"
        block
      >
        {{ form.processing ? "Creating..." : "Create PPMP" }}
      </b-button>
    </template>
  </b-modal>
</template>

<script>
import Multiselect from "@vueform/multiselect";

export default {
  components: { Multiselect },
  props: {
    show: { type: Boolean, default: false },
    form: { type: Object, required: true },
    yearOptions: { type: Array, default: () => [] },
    unitOptions: { type: Array, default: () => [] },
    loading: { type: Boolean, default: false },
  },
  emits: ["update:show", "close", "submit"],
  computed: {
    modalShow: {
      get() {
        return this.show;
      },
      set(value) {
        this.$emit("update:show", value);
      },
    },
  },
};
</script>
