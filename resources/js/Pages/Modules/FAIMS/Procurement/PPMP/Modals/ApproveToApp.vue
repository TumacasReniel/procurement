<template>
  <b-modal
    v-model="modalShow"
    header-class="p-3"
    title="Consolidate PPMP"
    size="xl"
    class="v-modal-custom"
    modal-class="zoomIn"
    centered
    no-close-on-backdrop
  >
    <div v-if="ppmp" class="ppmp-confirm">
      <div class="ppmp-confirm__icon">
        <i class="ri-checkbox-circle-line"></i>
      </div>
      <div>
        <h5 class="mb-1">Consolidate this PPMP?</h5>
        <p class="text-muted mb-3">
          This will add the submitted PPMP to the APP and mark it Consolidated/Added to APP.
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

      <div v-if="matchGroups.length" class="ppmp-match-preview">
        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
          <div>
            <h6 class="mb-1">Matching APP / approved items</h6>
            <p class="text-muted mb-0 small">
              These current PPMP items match existing APP or approved PPMP items by same specs/description or shared description keywords.
            </p>
          </div>
          <b-badge variant="info">{{ matchGroups.length }} item{{ matchGroups.length === 1 ? "" : "s" }}</b-badge>
        </div>

        <div class="accordion ppmp-match-accordion" id="ppmpMatchPreview">
          <div
            v-for="group in matchGroups"
            :key="group.id"
            class="accordion-item"
          >
            <h2 class="accordion-header">
              <button
                class="accordion-button collapsed"
                type="button"
                data-bs-toggle="collapse"
                :data-bs-target="`#match-group-${group.id}`"
              >
                <span class="flex-grow-1">
                  <span class="fw-semibold">{{ group.name || "-" }}</span>
                  <span class="text-muted ms-2">{{ formatQuantity(group.quantity) }} {{ group.unit || "" }}</span>
                </span>
                <span class="fw-semibold text-primary me-3">
                  {{ group.matches.length }} match{{ group.matches.length === 1 ? "" : "es" }}
                </span>
              </button>
            </h2>
            <div
              :id="`match-group-${group.id}`"
              class="accordion-collapse collapse"
              data-bs-parent="#ppmpMatchPreview"
            >
              <div class="accordion-body">
                <div class="ppmp-current-item mb-2">
                  <small class="text-muted d-block">Current PPMP item</small>
                  <div class="fw-semibold">{{ group.name || "-" }}</div>
                  <div class="text-muted small">{{ plainText(group.description) }}</div>
                  <div class="small mt-1">
                    {{ group.ppmp_no || "-" }} / {{ group.pr_no || "No PR yet" }}
                  </div>
                </div>

                <div class="table-responsive">
                  <table class="table table-sm table-bordered align-middle mb-0">
                    <thead class="table-light">
                      <tr>
                        <th>Matched Item in APP / Approved PPMP</th>
                        <th>PPMP / PR</th>
                        <th>Reason</th>
                        <th class="text-end">Qty</th>
                        <th class="text-end">Unit Cost</th>
                        <th class="text-end">ABC</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="(match, matchIndex) in group.matches" :key="match.id || matchIndex">
                        <td>
                          <div class="fw-semibold">{{ match.name || "-" }}</div>
                          <div class="text-muted small">{{ plainText(match.description) }}</div>
                        </td>
                        <td>
                          <div class="fw-semibold">{{ match.ppmp_no || "-" }}</div>
                          <small class="text-muted">{{ match.pr_no || "No PR yet" }}</small>
                        </td>
                        <td>
                          <div>{{ match.match_reason }}</div>
                          <small v-if="match.matched_keywords?.length" class="text-muted">
                            {{ match.matched_keywords.join(", ") }}
                          </small>
                        </td>
                        <td class="text-end">{{ formatQuantity(match.quantity) }} {{ match.unit || "" }}</td>
                        <td class="text-end">{{ formatCurrency(match.unit_price) }}</td>
                        <td class="text-end fw-semibold">{{ formatCurrency(match.abc) }}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div v-if="priceVarianceGroups.length" class="ppmp-average-preview">
        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
          <div>
            <h6 class="mb-1">Matching items with different unit costs</h6>
            <p class="text-muted mb-0 small">
              These items match existing APP or approved PPMP items. Consolidation uses the combined ABC and quantity to compute the weighted unit cost.
            </p>
          </div>
          <b-badge variant="warning">{{ priceVarianceGroups.length }} group{{ priceVarianceGroups.length === 1 ? "" : "s" }}</b-badge>
        </div>

        <div class="accordion ppmp-average-accordion" id="ppmpAveragePreview">
          <div
            v-for="group in priceVarianceGroups"
            :key="group.id"
            class="accordion-item"
          >
            <h2 class="accordion-header">
              <button
                class="accordion-button collapsed"
                type="button"
                data-bs-toggle="collapse"
                :data-bs-target="`#average-group-${group.id}`"
              >
                <span class="flex-grow-1">
                  <span class="fw-semibold">{{ group.name || "-" }}</span>
                  <span class="text-muted ms-2">
                    {{ formatQuantity(group.quantity) }} {{ group.unit || "" }}
                  </span>
                </span>
                <span class="fw-semibold text-primary me-3">
                  Weighted: {{ formatCurrency(group.computed_weighted_unit_cost || group.average_unit_price) }}
                </span>
              </button>
            </h2>
            <div
              :id="`average-group-${group.id}`"
              class="accordion-collapse collapse"
              data-bs-parent="#ppmpAveragePreview"
            >
              <div class="accordion-body">
                <div class="text-muted small mb-2">{{ plainText(group.description) }}</div>
                <div class="table-responsive">
                  <table class="table table-sm table-bordered align-middle mb-0">
                    <thead class="table-light">
                      <tr>
                        <th>Source</th>
                        <th>PPMP / PR</th>
                        <th class="text-end">Qty</th>
                        <th class="text-end">Unit Cost</th>
                        <th class="text-end">ABC</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="(item, itemIndex) in group.items" :key="itemIndex">
                        <td>{{ item.source }}</td>
                        <td>
                          <div class="fw-semibold">{{ item.ppmp_no || "-" }}</div>
                          <small class="text-muted">{{ item.pr_no || "No PR yet" }}</small>
                        </td>
                        <td class="text-end">{{ formatQuantity(item.quantity) }}</td>
                        <td class="text-end">{{ formatCurrency(item.unit_price) }}</td>
                        <td class="text-end fw-semibold">{{ formatCurrency(item.abc) }}</td>
                      </tr>
                    </tbody>
                    <tfoot>
                      <tr>
                        <th colspan="2" class="text-end">Weighted Unit Cost</th>
                        <th class="text-end">{{ formatQuantity(group.quantity) }}</th>
                        <th class="text-end">{{ formatCurrency(group.computed_weighted_unit_cost || group.average_unit_price) }}</th>
                        <th class="text-end">{{ formatCurrency(group.total_amount) }}</th>
                      </tr>
                    </tfoot>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div v-else class="ppmp-average-empty">
        No unit cost differences were found for matching same-spec items.
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
        variant="success"
        :disabled="processing"
        block
      >
        <i class="ri-checkbox-circle-line align-bottom me-1"></i>
        {{ processing ? "Consolidating..." : "Consolidate" }}
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
    ppmp: {
      type: Object,
      default: null,
    },
    processing: {
      type: Boolean,
      default: false,
    },
    error: {
      type: String,
      default: "",
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
    priceVarianceGroups() {
      return Array.isArray(this.ppmp?.consolidation_price_variance_groups)
        ? this.ppmp.consolidation_price_variance_groups
        : Array.isArray(this.ppmp?.consolidation_average_groups)
        ? this.ppmp.consolidation_average_groups
        : [];
    },
    matchGroups() {
      return Array.isArray(this.ppmp?.consolidation_match_groups)
        ? this.ppmp.consolidation_match_groups
        : [];
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
      const number = Number(value || 0);

      return Number.isInteger(number) ? number.toLocaleString() : number.toLocaleString(undefined, { maximumFractionDigits: 2 });
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

.ppmp-confirm__error {
  grid-column: 1 / -1;
}

.ppmp-match-preview,
.ppmp-average-preview,
.ppmp-average-empty {
  grid-column: 1 / -1;
}

.ppmp-match-preview {
  padding: 12px;
  border: 1px solid #b9e7f8;
  border-radius: 8px;
  background: #f3fbfe;
}

.ppmp-average-preview {
  padding: 12px;
  border: 1px solid #fde7ba;
  border-radius: 8px;
  background: #fffaf0;
}

.ppmp-average-empty {
  padding: 10px 12px;
  border: 1px solid #eef0f3;
  border-radius: 8px;
  background: #f8fafc;
  color: #878a99;
  font-size: 12px;
  font-weight: 600;
}

.ppmp-match-accordion .accordion-button,
.ppmp-average-accordion .accordion-button {
  padding: 10px 12px;
  font-size: 13px;
}

.ppmp-match-accordion .accordion-body,
.ppmp-average-accordion .accordion-body {
  padding: 12px;
}

.ppmp-current-item {
  padding: 8px 10px;
  border: 1px solid #d8edf7;
  border-radius: 8px;
  background: #ffffff;
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
