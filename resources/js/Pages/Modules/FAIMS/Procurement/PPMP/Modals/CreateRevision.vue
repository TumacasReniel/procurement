<template>
  <b-modal
    v-model="modalShow"
    header-class="p-3"
    title="Create Revision"
    size="md"
    class="v-modal-custom"
    modal-class="zoomIn"
    centered
    no-close-on-backdrop
  >
    <div v-if="ppmp" class="ppmp-confirm">
      <div class="ppmp-confirm__icon ppmp-confirm__icon--revision">
        <i class="ri-git-branch-line"></i>
      </div>
      <div>
        <h5 class="mb-1">Create Revision V{{ nextVersion }}?</h5>
        <p class="text-muted mb-3">
          A new <strong>Final V{{ nextVersion }}</strong> will be created by copying this version.
          This version (V{{ currentVersion }}) will become <strong>read-only</strong>.
          All items, funding, and schedule data will be carried over.
        </p>
      </div>

      <div class="ppmp-confirm__summary">
        <div>
          <span>Plan No.</span>
          <strong>{{ ppmp.ppmp_no || "-" }}</strong>
        </div>
        <div>
          <span>Current Version</span>
          <strong>Final V{{ currentVersion }}</strong>
        </div>
        <div>
          <span>New Version</span>
          <strong class="text-warning">Final V{{ nextVersion }}</strong>
        </div>
      </div>

      <div v-if="error" class="alert alert-danger mb-0 ppmp-confirm__error">
        {{ error }}
      </div>
    </div>

    <template v-slot:footer>
      <b-button @click="$emit('cancel')" variant="light" block>
        Cancel
      </b-button>
      <b-button
        @click="$emit('confirm')"
        variant="warning"
        :disabled="processing"
        block
      >
        <i class="ri-git-branch-line align-bottom me-1"></i>
        {{ processing ? "Creating..." : "Create Revision V" + nextVersion }}
      </b-button>
    </template>
  </b-modal>
</template>

<script>
export default {
  props: {
    show:       { type: Boolean, default: false },
    ppmp:       { type: Object,  default: null  },
    processing: { type: Boolean, default: false },
    error:      { type: String,  default: ""    },
  },
  emits: ["update:show", "cancel", "confirm"],
  computed: {
    modalShow: {
      get()      { return this.show; },
      set(value) { this.$emit("update:show", value); },
    },
    currentVersion() {
      return this.ppmp?.ppmp_type_version || 1;
    },
    nextVersion() {
      return this.currentVersion + 1;
    },
  },
};
</script>

<style scoped>
.ppmp-confirm {
  display: grid;
  grid-template-columns: 48px 1fr;
  gap: 14px;
}
.ppmp-confirm__icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 48px;
  height: 48px;
  border-radius: 8px;
  font-size: 24px;
}
.ppmp-confirm__icon--revision {
  color: #f7b731;
  background: rgba(247, 183, 49, .1);
}
.ppmp-confirm__summary {
  grid-column: 1 / -1;
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 8px;
}
.ppmp-confirm__error { grid-column: 1 / -1; }
.ppmp-confirm__summary > div {
  padding: 10px;
  background: #f8fafc;
  border: 1px solid #eef0f3;
  border-radius: 8px;
}
.ppmp-confirm__summary span {
  display: block;
  color: #878a99;
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
}
.ppmp-confirm__summary strong {
  color: #212529;
  font-size: 13px;
  font-weight: 700;
}
@media (max-width: 992px) {
  .ppmp-confirm,
  .ppmp-confirm__summary { grid-template-columns: 1fr; }
}
</style>
