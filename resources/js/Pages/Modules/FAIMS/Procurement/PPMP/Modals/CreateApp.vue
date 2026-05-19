<template>
  <b-modal
    v-model="modalShow"
    header-class="p-3"
    title="Create APP"
    size="md"
    class="v-modal-custom"
    modal-class="zoomIn"
    centered
    no-close-on-backdrop
  >
    <form class="customform">
      <BRow>
        <BCol lg="12" class="mt-2">
          <label class="form-label">APP Year</label>
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
          <small v-if="!yearOptions.length" class="text-muted d-block mt-2">
            All selectable years already have an APP.
          </small>
        </BCol>

        <BCol lg="12" class="mt-3">
          <div class="alert alert-info mb-0 fs-13">
            This creates one APP register for the selected year. PPMPs can be consolidated into it once they are ready.
          </div>
        </BCol>
      </BRow>
    </form>

    <template v-slot:footer>
      <b-button @click="$emit('close')" variant="light" block>Close</b-button>
      <b-button
        @click="$emit('submit')"
        variant="primary"
        :disabled="form.processing || !form.year || yearHasExisting"
        block
      >
        {{ form.processing ? "Creating..." : "Create APP" }}
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
    yearHasExisting: { type: Boolean, default: false },
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
