<template>
  <b-modal
    v-model="modalShow"
    header-class="p-3"
    title="Create Annual Procurement Plan"
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
            All selectable years already have both an Indicative and Final APP.
          </small>
        </BCol>

        <BCol lg="12" class="mt-3">
          <label class="form-label fw-semibold">APP Phase <span class="text-danger">*</span></label>
          <div class="d-flex flex-column gap-2 mt-1">
            <div
              class="app-phase-option"
              :class="{ 'app-phase-option--active': form.plan_phase === 'indicative' }"
              @click="form.plan_phase = 'indicative'"
            >
              <div class="app-phase-option__radio">
                <div class="app-phase-option__dot" :class="{ active: form.plan_phase === 'indicative' }"></div>
              </div>
              <div class="app-phase-option__content">
                <div class="app-phase-option__title">
                  <i class="ri-draft-line me-1 text-warning"></i>
                  Indicative APP
                  <span class="app-phase-option__tag">Pre-Budget</span>
                </div>
                <div class="app-phase-option__desc">
                  Based on the proposed budget. Prepared before the GAA is signed.
                  For transparency and budget proposal purposes.
                </div>
              </div>
            </div>

            <div
              class="app-phase-option"
              :class="{ 'app-phase-option--active': form.plan_phase === 'final' }"
              @click="form.plan_phase = 'final'"
            >
              <div class="app-phase-option__radio">
                <div class="app-phase-option__dot" :class="{ active: form.plan_phase === 'final' }"></div>
              </div>
              <div class="app-phase-option__content">
                <div class="app-phase-option__title">
                  <i class="ri-flag-2-line me-1 text-success"></i>
                  Final APP
                  <span class="app-phase-option__tag app-phase-option__tag--success">Post-GAA</span>
                </div>
                <div class="app-phase-option__desc">
                  Based on the approved budget after GAA enactment. Mandatory PhilGEPS posting
                  within 30 days of budget approval (RA 9184, Sec. 7).
                </div>
              </div>
            </div>
          </div>
          <div v-if="form.errors.plan_phase" class="invalid-feedback d-block mt-1">
            {{ form.errors.plan_phase }}
          </div>
        </BCol>

        <BCol lg="12" class="mt-3" v-if="phaseAlreadyExists">
          <div class="alert alert-warning mb-0 fs-13 py-2">
            <i class="ri-error-warning-line me-1"></i>
            A <strong>{{ form.plan_phase === 'final' ? 'Final' : 'Indicative' }} APP</strong>
            already exists for {{ form.year }}. Please select a different phase or year.
          </div>
        </BCol>

        <BCol lg="12" class="mt-3" v-else-if="form.plan_phase">
          <div class="alert mb-0 fs-13 py-2" :class="form.plan_phase === 'final' ? 'alert-success' : 'alert-info'">
            <i :class="form.plan_phase === 'final' ? 'ri-flag-2-line' : 'ri-information-line'" class="me-1"></i>
            <template v-if="form.plan_phase === 'final'">
              Only <strong>Final PPMPs</strong> (marked as Final by unit heads) will be linked to this APP.
              SPP updates can be created once this Final APP is approved.
            </template>
            <template v-else>
              Only <strong>Indicative PPMPs</strong> will be linked. This APP is for pre-budget
              transparency purposes — create the Final APP after the GAA is signed.
            </template>
          </div>
        </BCol>
      </BRow>
    </form>

    <template v-slot:footer>
      <b-button @click="$emit('close')" variant="light" block>Close</b-button>
      <b-button
        @click="$emit('submit')"
        variant="primary"
        :disabled="form.processing || !form.year || !form.plan_phase || phaseAlreadyExists"
        block
      >
        {{ form.processing ? "Creating..." : `Create ${planPhaseLabel}` }}
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
    indicativeAppYears: { type: Array, default: () => [] },
    finalAppYears: { type: Array, default: () => [] },
  },
  emits: ["update:show", "close", "submit"],
  computed: {
    modalShow: {
      get() { return this.show; },
      set(value) { this.$emit("update:show", value); },
    },
    phaseAlreadyExists() {
      const year = Number(this.form.year);
      if (!year || !this.form.plan_phase) return false;
      if (this.form.plan_phase === "final") return this.finalAppYears.includes(year);
      return this.indicativeAppYears.includes(year);
    },
    planPhaseLabel() {
      return this.form.plan_phase === "final" ? "Final APP" : "Indicative APP";
    },
  },
};
</script>

<style scoped>
.app-phase-option {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  padding: 12px 14px;
  border-radius: 8px;
  border: 1.5px solid #dee2e6;
  cursor: pointer;
  transition: border-color 0.15s, background 0.15s;
}
.app-phase-option:hover {
  border-color: #405189;
  background: #f8f9fa;
}
.app-phase-option--active {
  border-color: #405189;
  background: rgba(64, 81, 137, 0.06);
}
.app-phase-option__radio {
  flex-shrink: 0;
  width: 18px;
  height: 18px;
  border-radius: 50%;
  border: 2px solid #adb5bd;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-top: 2px;
  transition: border-color 0.15s;
}
.app-phase-option--active .app-phase-option__radio {
  border-color: #405189;
}
.app-phase-option__dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: transparent;
  transition: background 0.15s;
}
.app-phase-option__dot.active {
  background: #405189;
}
.app-phase-option__content {
  flex: 1;
}
.app-phase-option__title {
  font-size: 13px;
  font-weight: 600;
  color: #212529;
  display: flex;
  align-items: center;
  gap: 6px;
  flex-wrap: wrap;
}
.app-phase-option__tag {
  font-size: 10px;
  font-weight: 700;
  padding: 1px 6px;
  border-radius: 3px;
  background: rgba(255, 193, 7, 0.15);
  color: #856404;
  text-transform: uppercase;
  letter-spacing: 0.3px;
}
.app-phase-option__tag--success {
  background: rgba(10, 179, 156, 0.15);
  color: #0a6640;
}
.app-phase-option__desc {
  font-size: 12px;
  color: #6c757d;
  margin-top: 3px;
  line-height: 1.4;
}
</style>
