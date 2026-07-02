<template>
  <b-modal
    v-model="localShow"
    size="lg"
    centered
    :title="`Proceed to Final APP — ${planYear}`"
    hide-footer
    no-close-on-backdrop
    @hidden="onHidden"
  >
    <!-- Step indicator -->
    <div class="finalize-steps mb-4">
      <template v-for="(stepLabel, idx) in steps" :key="idx">
        <div
          class="finalize-step"
          :class="{
            'finalize-step--active': currentStep === idx + 1,
            'finalize-step--done': currentStep > idx + 1,
          }"
        >
          <div class="finalize-step__bubble">
            <i v-if="currentStep > idx + 1" class="ri-check-line"></i>
            <span v-else>{{ idx + 1 }}</span>
          </div>
          <span class="finalize-step__label">{{ stepLabel }}</span>
        </div>
        <div v-if="idx < steps.length - 1" class="finalize-step__connector" :class="{ 'finalize-step__connector--done': currentStep > idx + 1 }"></div>
      </template>
    </div>

    <!-- Step 1: Review source PPMPs -->
    <div v-if="currentStep === 1">
      <p class="text-muted small mb-3">
        The PPMPs below are consolidated in the <strong>Indicative APP ({{ ppmp && ppmp.code }})</strong>.
        Proceeding will <strong>mark each PPMP as Final</strong> and create a new <strong>Final APP</strong>
        version for {{ planYear }}.
      </p>

      <div v-if="sourcePpmps.length === 0" class="alert alert-warning d-flex gap-2">
        <i class="ri-alert-line fs-5 flex-shrink-0 mt-1"></i>
        <div>
          <strong>No source PPMPs found.</strong><br />
          This Indicative APP has no consolidated unit PPMPs. Please consolidate unit PPMPs into the Indicative APP first.
        </div>
      </div>

      <div v-else>
        <div class="table-responsive mb-3">
          <table class="table table-sm table-bordered align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>#</th>
                <th>Unit</th>
                <th class="text-center">Items</th>
                <th class="text-end">Budget</th>
                <th class="text-center">Status</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(src, i) in sourcePpmps" :key="src.id">
                <td class="text-muted small">{{ i + 1 }}</td>
                <td>
                  <div class="fw-semibold">{{ src.unit || '—' }}</div>
                  <div class="text-muted" style="font-size:11px">{{ src.ppmp_no || `#${src.id}` }}</div>
                </td>
                <td class="text-center">{{ src.items_count || 0 }}</td>
                <td class="text-end">{{ formatCurrency(src.total_amount || src.estimated_budget || 0) }}</td>
                <td class="text-center">
                  <span class="badge bg-success-subtle text-success" style="font-size:11px">
                    {{ src.approval_status || 'Consolidated' }}
                  </span>
                </td>
              </tr>
            </tbody>
            <tfoot class="table-light fw-semibold">
              <tr>
                <td colspan="2">
                  Total &mdash; {{ sourcePpmps.length }} unit{{ sourcePpmps.length === 1 ? '' : 's' }}
                </td>
                <td class="text-center">{{ totalItems }}</td>
                <td class="text-end">{{ formatCurrency(totalBudget) }}</td>
                <td></td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>

      <div class="d-flex justify-content-end gap-2">
        <b-button variant="light" @click="$emit('cancel')">Cancel</b-button>
        <b-button
          variant="primary"
          :disabled="sourcePpmps.length === 0"
          @click="currentStep = 2"
        >
          Review & Confirm
          <i class="ri-arrow-right-line ms-1"></i>
        </b-button>
      </div>
    </div>

    <!-- Step 2: Confirm -->
    <div v-if="currentStep === 2">
      <div class="alert alert-info d-flex gap-2 mb-3">
        <i class="ri-information-line fs-5 flex-shrink-0 mt-1"></i>
        <div>
          <strong>What will happen when you confirm:</strong>
          <ul class="mb-0 mt-1 ps-3">
            <li>
              <strong>{{ sourcePpmps.length }}</strong> unit PPMP{{ sourcePpmps.length === 1 ? '' : 's' }}
              will be <strong>marked as Final</strong>
            </li>
            <li>
              A new <strong>Final APP</strong> version will be created for <strong>{{ planYear }}</strong>
            </li>
            <li>
              All finalized PPMPs will be consolidated into the new Final APP
            </li>
            <li>
              The Indicative APP <strong>({{ ppmp && ppmp.code }})</strong> remains as a reference record
            </li>
          </ul>
        </div>
      </div>

      <div class="finalize-summary-card mb-4">
        <div class="row g-3 text-center">
          <div class="col-4">
            <div class="fs-2 fw-bold text-primary">{{ sourcePpmps.length }}</div>
            <div class="text-muted small">PPMPs to finalize</div>
          </div>
          <div class="col-4">
            <div class="fs-2 fw-bold text-success">{{ totalItems }}</div>
            <div class="text-muted small">Procurement items</div>
          </div>
          <div class="col-4">
            <div class="fs-5 fw-bold text-warning">{{ formatCurrency(totalBudget) }}</div>
            <div class="text-muted small">Total budget</div>
          </div>
        </div>
      </div>

      <div v-if="error" class="alert alert-danger d-flex gap-2">
        <i class="ri-error-warning-line fs-5 flex-shrink-0 mt-1"></i>
        <div>{{ error }}</div>
      </div>

      <div class="d-flex justify-content-between gap-2">
        <b-button variant="light" :disabled="processing" @click="currentStep = 1">
          <i class="ri-arrow-left-line me-1"></i>Back
        </b-button>
        <b-button
          variant="success"
          :disabled="processing || sourcePpmps.length === 0"
          @click="$emit('confirm')"
        >
          <b-spinner v-if="processing" small class="me-1" />
          <i v-else class="ri-flag-2-fill me-1"></i>
          {{ processing ? 'Creating Final APP…' : 'Create Final APP' }}
        </b-button>
      </div>
    </div>
  </b-modal>
</template>

<script>
export default {
  name: 'FinalizeToFinalAppModal',

  props: {
    show: Boolean,
    ppmp: Object,
    processing: Boolean,
    error: String,
  },

  emits: ['update:show', 'cancel', 'confirm'],

  data() {
    return {
      currentStep: 1,
      steps: ['Review PPMPs', 'Confirm & Create'],
    };
  },

  computed: {
    localShow: {
      get() { return this.show; },
      set(val) { this.$emit('update:show', val); },
    },
    planYear() {
      return (
        this.ppmp?.start_date?.substring(0, 4) ||
        this.ppmp?.date?.substring(0, 4) ||
        new Date().getFullYear()
      );
    },
    sourcePpmps() {
      return this.ppmp?.source_ppmps || [];
    },
    totalItems() {
      return this.sourcePpmps.reduce((sum, src) => sum + (Number(src.items_count) || 0), 0);
    },
    totalBudget() {
      return this.sourcePpmps.reduce(
        (sum, src) => sum + Number(src.total_amount || src.estimated_budget || 0),
        0
      );
    },
  },

  watch: {
    show(val) {
      if (val) this.currentStep = 1;
    },
  },

  methods: {
    onHidden() {
      this.$emit('cancel');
    },
    formatCurrency(value) {
      return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
      }).format(Number(value || 0));
    },
  },
};
</script>

<style scoped>
.finalize-steps {
  display: flex;
  align-items: center;
}

.finalize-step {
  display: flex;
  align-items: center;
  gap: 8px;
}

.finalize-step__connector {
  flex: 1;
  height: 2px;
  background: #dee2e6;
  margin: 0 8px;
}

.finalize-step__connector--done {
  background: #0ab39c;
}

.finalize-step__bubble {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  background: #dee2e6;
  color: #6c757d;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  font-weight: 700;
  flex-shrink: 0;
}

.finalize-step--active .finalize-step__bubble {
  background: #405189;
  color: #fff;
}

.finalize-step--done .finalize-step__bubble {
  background: #0ab39c;
  color: #fff;
}

.finalize-step__label {
  font-size: 12px;
  color: #6c757d;
  white-space: nowrap;
}

.finalize-step--active .finalize-step__label {
  color: #405189;
  font-weight: 600;
}

.finalize-step--done .finalize-step__label {
  color: #0ab39c;
}

.finalize-summary-card {
  background: var(--bs-tertiary-bg, #f8f9fa);
  border: 1px solid var(--bs-border-color, #dee2e6);
  border-radius: 8px;
  padding: 1.25rem;
}
</style>
