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
          <label class="form-label">Quarter</label>
          <Multiselect
            :options="quarterOptions"
            v-model="form.quarter"
            label="name"
            value-prop="value"
            :class="{ 'is-invalid': form.errors.quarter }"
            placeholder="Select Quarter"
          />
          <div v-if="form.errors.quarter" class="invalid-feedback d-block">
            {{ form.errors.quarter }}
          </div>
        </BCol>

        <BCol lg="12" class="mt-2">
          <label class="form-label">Unit</label>
          <Multiselect
            :options="filteredUnitOptions"
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
        </BCol>

        <BCol lg="12" class="mt-2">
          <label class="form-label">Requested By <span class="text-muted">(Unit Head)</span></label>
          <Multiselect
            :options="selectedUnitUsers"
            v-model="form.requested_by_id"
            label="name"
            value-prop="value"
            :searchable="true"
            :class="{ 'is-invalid': form.errors.requested_by_id }"
            placeholder="Select unit head"
          >
            <template #option="{ option }">
              <span>{{ option.name }}</span>
              <small v-if="option.designation" class="text-muted ms-1">— {{ option.designation }}</small>
            </template>
          </Multiselect>
          <div v-if="form.errors.requested_by_id" class="invalid-feedback d-block">
            {{ form.errors.requested_by_id }}
          </div>
        </BCol>

        <BCol lg="12" class="mt-3">
          <label class="form-label">Supporting Document(<span class="text-muted">Line-Item Budget</span>)</label>
          <FileDropzone
            :file="form.attachment_file"
            :invalid="!!form.errors.attachment_file"
            accept=".pdf"
            :allowed-extensions="['pdf']"
            :max-size-mb="10"
            invalid-type-message="Please attach a PDF file only."
            invalid-size-message="Please attach a file up to 10 MB only."
            title="Drop attachment here or click to browse"
            hint="PDF files only, up to 10 MB"
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
        variant="primary"
        :disabled="form.processing"
        block
      >
        {{ form.processing ? "Creating..." : "Create PPMP" }}
      </b-button>
    </template>
  </b-modal>
</template>

<script>
import Multiselect from "@vueform/multiselect";
import FileDropzone from "@/Shared/Components/Forms/FileDropzone.vue";

export default {
  components: { Multiselect, FileDropzone },
  props: ['show', 'form', 'yearOptions', 'unitOptions', 'loading'],
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
    takenQuarters() {
      const unit = this.unitOptions?.find((u) => Number(u.value) === Number(this.form.unit_id));
      return unit?.taken_quarters ?? [];
    },
    quarterOptions() {
      const all = [
        { value: 1, name: 'Q1 — January to March' },
        { value: 2, name: 'Q2 — April to June' },
        { value: 3, name: 'Q3 — July to September' },
        { value: 4, name: 'Q4 — October to December' },
      ];
      return all.filter((q) => !this.takenQuarters.includes(q.value));
    },
    filteredUnitOptions() {
      const q = this.form.quarter;
      if (!q) return this.unitOptions ?? [];
      return (this.unitOptions ?? []).filter(
        (u) => !(u.taken_quarters ?? []).includes(q)
      );
    },
    selectedUnitUsers() {
      const unit = this.unitOptions?.find((u) => Number(u.value) === Number(this.form.unit_id));
      return unit?.users ?? [];
    },
  },
};
</script>
