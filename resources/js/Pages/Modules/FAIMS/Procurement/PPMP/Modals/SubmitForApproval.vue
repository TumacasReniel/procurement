<template>
  <b-modal
    v-model="modalShow"
    header-class="p-3"
    :title="modalTitle"
    size="md"
    class="v-modal-custom"
    modal-class="zoomIn"
    centered
    no-close-on-backdrop
  >
    <div v-if="ppmp" class="ppmp-confirm">
      <div class="ppmp-confirm__icon">
        <i class="ri-check-double-line"></i>
      </div>
      <div>
        <h5 class="mb-1">{{ heading }}</h5>
        <p class="text-muted mb-3">
          {{ description }}
        </p>
      </div>

      <div class="ppmp-confirm__summary">
        <div>
          <span>Plan No.</span>
          <strong>{{ ppmp.ppmp_no || "-" }}</strong>
        </div>
        <div>
          <span>Unit</span>
          <strong>{{ ppmp.unit?.name || "-" }}</strong>
        </div>
        <div>
          <span>Total ABC</span>
          <strong>{{ formatCurrency(ppmp.estimated_budget) }}</strong>
        </div>
      </div>
    </div>

    <template v-slot:footer>
      <b-button @click="$emit('cancel')" variant="light" block>
        Cancel
      </b-button>
      <b-button
        @click="$emit('confirm')"
        variant="success"
        :disabled="processing"
        block
      >
        <i class="ri-check-double-line align-bottom me-1"></i>
        {{ processing ? "Processing..." : confirmLabel }}
      </b-button>
    </template>
  </b-modal>
</template>

<script>
export default {
  props: {
    show: { type: Boolean, default: false },
    ppmp: { type: Object, default: null },
    processing: { type: Boolean, default: false },
  },
  emits: ["update:show", "cancel", "confirm"],
  computed: {
    isReviewed() {
      return String(this.ppmp?.ppmp_status || "").toLowerCase() === "reviewed/for submission";
    },
    modalTitle() {
      return this.isReviewed ? "Submit PPMP for Consolidation" : "Review PPMP";
    },
    heading() {
      return this.isReviewed ? "Submit this PPMP for consolidation?" : "Mark this PPMP as reviewed?";
    },
    description() {
      return this.isReviewed
        ? "This will mark the reviewed unit plan as submitted and ready for BAC consolidation."
        : "This will move the pending unit plan to Reviewed/For Submission.";
    },
    confirmLabel() {
      return this.isReviewed ? "Submit for Consolidation" : "Mark as Reviewed";
    },
    modalShow: {
      get() {
        return this.show;
      },
      set(value) {
        this.$emit("update:show", value);
      },
    },
  },
  methods: {
    formatCurrency(value) {
      return new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
      }).format(Number(value || 0));
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
  color: #0ab39c;
  background: rgba(10, 179, 156, .1);
  border-radius: 8px;
  font-size: 24px;
}

.ppmp-confirm__summary {
  grid-column: 1 / -1;
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 8px;
}

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
  .ppmp-confirm__summary {
    grid-template-columns: 1fr;
  }
}
</style>
