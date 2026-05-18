<template>
  <b-modal
    v-model="modalShow"
    header-class="p-3"
    title="Approve Budget Request"
    size="md"
    class="v-modal-custom"
    modal-class="zoomIn"
    centered
    no-close-on-backdrop
  >
    <div v-if="request" class="budget-confirm">
      <div class="budget-confirm__icon">
        <i class="ri-checkbox-circle-line"></i>
      </div>
      <div>
        <h5 class="mb-1">Approve this budget request?</h5>
        <p class="text-muted mb-3">
          This will approve the requested budget and update the PAP code budget records.
        </p>
      </div>

      <div class="budget-confirm__summary">
        <div>
          <span>PAP Code</span>
          <strong>{{ request.procurement_code?.code || "-" }}</strong>
        </div>
        <div>
          <span>Request Amount</span>
          <strong>{{ formatCurrency(request.amount) }}</strong>
        </div>
        <div>
          <span>Request Type</span>
          <strong>{{ request.request_type_label || "-" }}</strong>
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
        <i class="ri-checkbox-circle-line align-bottom me-1"></i>
        {{ processing ? "Approving..." : "Proceed" }}
      </b-button>
    </template>
  </b-modal>
</template>

<script>
export default {
  props: {
    show: {
      type: Boolean,
      default: false,
    },
    request: {
      type: Object,
      default: null,
    },
    processing: {
      type: Boolean,
      default: false,
    },
  },
  emits: ["update:show", "cancel", "confirm"],
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
.budget-confirm {
  display: grid;
  grid-template-columns: 48px 1fr;
  gap: 14px;
}

.budget-confirm__icon {
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

.budget-confirm__summary {
  grid-column: 1 / -1;
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 8px;
}

.budget-confirm__summary > div {
  padding: 10px;
  background: #f8fafc;
  border: 1px solid #eef0f3;
  border-radius: 8px;
}

.budget-confirm__summary span {
  display: block;
  color: #878a99;
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
}

.budget-confirm__summary strong {
  color: #212529;
  font-size: 13px;
  font-weight: 700;
}

@media (max-width: 992px) {
  .budget-confirm,
  .budget-confirm__summary {
    grid-template-columns: 1fr;
  }
}
</style>
