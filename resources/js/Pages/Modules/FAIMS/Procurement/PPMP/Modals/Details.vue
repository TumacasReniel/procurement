<template>
  <b-modal
    v-model="modalShow"
    header-class="p-3"
    title="PPMP Details"
    size="xl"
    class="v-modal-custom"
    modal-class="zoomIn"
    centered
    no-close-on-backdrop
  >
    <div v-if="ppmp" class="ppmp-print-area">
      <div class="ppmp-document-header">
        <img src="/images/logo-sm.png" alt="DOST Logo" class="ppmp-header-logo" />
        <div class="text-center flex-grow-1">
          <div class="fw-semibold fs-18">Republic of the Philippines</div>
          <div class="fw-bold fs-20">DEPARTMENT OF SCIENCE AND TECHNOLOGY</div>
          <div class="fw-semibold fs-18">Regional Office IX</div>
        </div>
        <img src="/images/bp-logo.webp" alt="Bagong Pilipinas Logo" class="ppmp-header-logo" />
      </div>

      <div class="ppmp-title-block">
        <div class="fw-bold fs-22">
          PROJECT PROCUREMENT MANAGEMENT PLAN (PPMP) NO.
          <span class="ppmp-line">{{ ppmp.ppmp_no || "" }}</span>
        </div>
        <div class="d-flex justify-content-center gap-5 mt-2">
          <div class="ppmp-status-check">
            <span class="ppmp-checkbox" :class="{ checked: !ppmp.is_final }"></span>
            <span>INDICATIVE</span>
          </div>
          <div class="ppmp-status-check">
            <span class="ppmp-checkbox" :class="{ checked: ppmp.is_final }"></span>
            <span>FINAL</span>
          </div>
        </div>
      </div>

      <div class="row g-2 mb-3">
        <div class="col-md-4">
          <div class="border rounded p-2 h-100">
            <small class="text-muted d-block">Plan</small>
            <span class="fw-semibold">{{ ppmp.plan_name }}</span>
          </div>
        </div>
        <div class="col-md-4">
          <div class="border rounded p-2 h-100">
            <small class="text-muted d-block">Unit</small>
            <span class="fw-semibold">{{ ppmp.unit?.name || "-" }}</span>
          </div>
        </div>
        <div class="col-md-4">
          <div class="border rounded p-2 h-100">
            <small class="text-muted d-block">Total ABC</small>
            <span class="fw-semibold">{{ formatCurrency(ppmp.estimated_budget) }}</span>
          </div>
        </div>
      </div>

      <div class="table-responsive">
        <table class="table table-bordered align-middle mb-0">
          <thead class="table-light">
            <tr class="fs-12 text-center">
              <th style="width: 4%">#</th>
              <th>Item</th>
              <th>Description</th>
              <th style="width: 10%">Qty</th>
              <th style="width: 10%">Unit</th>
              <th style="width: 14%">Unit Price</th>
              <th style="width: 14%">ABC</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(item, itemIndex) in ppmpItems" :key="item.id">
              <td class="text-center">{{ itemIndex + 1 }}</td>
              <td>{{ item.name || "-" }}</td>
              <td>{{ plainText(item.description) }}</td>
              <td class="text-end">{{ formatQuantity(item.quantity) }}</td>
              <td>{{ item.unit || "-" }}</td>
              <td class="text-end">{{ formatCurrency(item.unit_price) }}</td>
              <td class="text-end fw-semibold">{{ formatCurrency(item.abc) }}</td>
            </tr>
            <tr v-if="!ppmpItems.length">
              <td colspan="7" class="text-center text-muted">No items found.</td>
            </tr>
          </tbody>
          <tfoot>
            <tr>
              <th colspan="6" class="text-end">Total ABC</th>
              <th class="text-end">{{ formatCurrency(ppmp.estimated_budget) }}</th>
            </tr>
          </tfoot>
        </table>
      </div>

      <div class="ppmp-signatories">
        <div class="ppmp-signatory">
          <div class="ppmp-signatory-line">{{ ppmp.prepared_by || "" }}</div>
          <div class="ppmp-signatory-label">Prepared By</div>
        </div>
        <div class="ppmp-signatory">
          <div class="ppmp-signatory-line">{{ ppmp.submitted_by || "" }}</div>
          <div class="ppmp-signatory-label">Submitted By</div>
        </div>
      </div>
    </div>

    <template v-slot:footer>
      <b-button @click="$emit('close')" variant="light" block>Close</b-button>
      <b-button @click="$emit('print', ppmp)" variant="dark" block>
        <i class="ri-printer-line align-bottom me-1"></i>
        Print
      </b-button>
    </template>
  </b-modal>
</template>

<script>
export default {
  props: {
    show: { type: Boolean, default: false },
    ppmp: { type: Object, default: null },
  },
  emits: ["update:show", "close", "print"],
  computed: {
    modalShow: {
      get() {
        return this.show;
      },
      set(value) {
        this.$emit("update:show", value);
      },
    },
    ppmpItems() {
      return Array.isArray(this.ppmp?.item_details) ? this.ppmp.item_details : [];
    },
  },
  methods: {
    formatCurrency(value) {
      return new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
      }).format(Number(value || 0));
    },
    formatQuantity(value) {
      const amount = Number(value || 0);
      return Number.isInteger(amount) ? amount.toString() : amount.toFixed(2);
    },
    plainText(value) {
      if (!value) {
        return "-";
      }

      const element = document.createElement("div");
      element.innerHTML = String(value);

      return element.textContent?.trim() || "-";
    },
  },
};
</script>

<style scoped>
.ppmp-print-area {
  padding: 8px;
  color: #111827;
}

.ppmp-document-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 14px;
  padding-bottom: 12px;
  border-bottom: 2px solid #111827;
  margin-bottom: 16px;
}

.ppmp-header-logo {
  width: 78px;
  height: 78px;
  object-fit: contain;
}

.ppmp-title-block {
  text-align: center;
  margin-bottom: 16px;
}

.ppmp-line {
  display: inline-block;
  min-width: 90px;
  border-bottom: 2px solid #111827;
}

.ppmp-status-check {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  font-weight: 700;
}

.ppmp-checkbox {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 22px;
  height: 22px;
  border: 1.5px solid #111827;
}

.ppmp-checkbox.checked::after {
  content: "✓";
  font-size: 16px;
  line-height: 1;
}

.ppmp-signatories {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 60px;
  margin-top: 54px;
}

.ppmp-signatory {
  text-align: center;
}

.ppmp-signatory-line {
  min-height: 28px;
  border-bottom: 1.5px solid #111827;
  font-weight: 700;
  text-transform: uppercase;
}

.ppmp-signatory-label {
  margin-top: 6px;
  font-size: 12px;
  font-weight: 700;
}

@media (max-width: 768px) {
  .ppmp-document-header,
  .ppmp-signatories {
    grid-template-columns: 1fr;
  }

  .ppmp-document-header {
    flex-direction: column;
  }
}
</style>
