<template>
  <b-modal
    v-model="modalShow"
    header-class="p-3"
    title="Edit Unit PPMP"
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
          <input type="text" class="form-control" disabled :value="ppmp?.date ? String(new Date(ppmp.date).getFullYear()) : ''" />
        </BCol>

        <BCol lg="12" class="mt-2">
          <label class="form-label">Quarter</label>
          <input type="text" class="form-control" disabled :value="quarterLabel" />
        </BCol>

        <BCol lg="12" class="mt-2">
          <label class="form-label">Unit</label>
          <input type="text" class="form-control" disabled :value="ppmp?.unit?.name || ''" />
          <div class="form-text">Plan year, quarter, and unit cannot be changed after creation.</div>
        </BCol>

        <BCol lg="12" class="mt-2">
          <label class="form-label">Requested By <span class="text-muted">(Unit Head)</span></label>
          <Multiselect
            :options="unitUsers"
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
            :existing-file-name="ppmp?.attachment_original_name || ''"
            :existing-file-url="ppmp?.attachment_url || ''"
            :invalid="!!form.errors.attachment_file"
            accept=".pdf"
            :allowed-extensions="['pdf']"
            :max-size-mb="10"
            invalid-type-message="Please attach a PDF file only."
            invalid-size-message="Please attach a file up to 10 MB only."
            title="Drop attachment here or click to browse"
            hint="Only attach a file here if you need to replace the current one — PDF files only, up to 10 MB"
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
        {{ form.processing ? "Saving..." : "Save Changes" }}
      </b-button>
    </template>
  </b-modal>
</template>

<script>
import Multiselect from "@vueform/multiselect";
import FileDropzone from "@/Shared/Components/Forms/FileDropzone.vue";

export default {
  components: { Multiselect, FileDropzone },
  props: ['show', 'form', 'ppmp', 'unitUsers'],
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
    quarterLabel() {
      const labels = {
        1: 'Q1 — January to March',
        2: 'Q2 — April to June',
        3: 'Q3 — July to September',
        4: 'Q4 — October to December',
      };
      return labels[this.ppmp?.quarter] || '';
    },
  },
};
</script>
