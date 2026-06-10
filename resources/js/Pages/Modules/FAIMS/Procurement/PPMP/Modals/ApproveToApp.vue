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

      <div class="ppmp-consolidation-tabs">
        <button
          type="button"
          class="ppmp-consolidation-tab"
          :class="{ active: activeTab === 'items' }"
          @click="activeTab = 'items'"
        >
          <i class="ri-file-list-3-line"></i>
          PPMP Items
          <span class="badge bg-primary">{{ ppmpItems.length }}</span>
        </button>
        <button
          type="button"
          class="ppmp-consolidation-tab"
          :class="{ active: activeTab === 'matches' }"
          @click="activeTab = 'matches'"
        >
          <i class="ri-git-merge-line"></i>
          Same Specs &amp; Pricing
          <span class="badge bg-warning text-dark">{{ sameSpecGroupCount }}</span>
        </button>
      </div>

      <div v-show="activeTab === 'items'" class="ppmp-items-preview">
        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
          <div>
            <h6 class="mb-1">PPMP Items</h6>
            <p class="text-muted mb-0 small">
              Review the items that will be included in the APP consolidation.
            </p>
          </div>
          <b-badge variant="primary">
            {{ ppmpItems.length }} item{{ ppmpItems.length === 1 ? "" : "s" }}
          </b-badge>
        </div>

        <div v-if="ppmpItems.length" class="ppmp-items-table-container">
          <div class="table-responsive ppmp-items-table-scroll">
          <table class="ppmp-items-table">
            <thead>
              <tr>
                <th style="width: 50px">#</th>
                <th>Item / Specification</th>
                <th>Mode of Procurement</th>
                <th class="text-end">Qty</th>
                <th>Unit</th>
                <th class="text-end">Unit Cost</th>
                <th class="text-end">ABC</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(item, index) in paginatedPpmpItems" :key="item.id || item.displayIndex">
                <td class="text-center ppmp-item-number">{{ item.item_no || item.displayIndex }}</td>
                <td>
                  <div class="fw-semibold">{{ item.name || "-" }}</div>
                  <div class="text-muted small">{{ plainText(item.description) }}</div>
                </td>
                <td>{{ item.recommended_mode_of_procurement || "-" }}</td>
                <td class="text-end">{{ formatQuantity(item.quantity) }}</td>
                <td><span class="ppmp-unit-badge">{{ item.unit || "-" }}</span></td>
                <td class="text-end ppmp-item-cost">{{ formatCurrency(item.unit_price) }}</td>
                <td class="text-end ppmp-item-cost">{{ formatCurrency(itemAmount(item)) }}</td>
              </tr>
            </tbody>
            <tfoot>
              <tr>
                <th colspan="6" class="text-end ppmp-total-label">Total ABC</th>
                <th class="text-end ppmp-total-amount">{{ formatCurrency(ppmpItemsTotal) }}</th>
              </tr>
            </tfoot>
          </table>
          </div>
        </div>
        <div
          v-if="ppmpItems.length"
          class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3"
        >
          <div class="d-flex align-items-center gap-2">
            <label for="ppmp-items-per-page" class="text-muted small mb-0">Show</label>
            <select
              id="ppmp-items-per-page"
              v-model.number="itemsPerPage"
              class="form-select form-select-sm ppmp-items-per-page"
              @change="itemsCurrentPage = 1"
            >
              <option v-for="option in itemsPerPageOptions" :key="option" :value="option">
                {{ option }}
              </option>
            </select>
            <span class="text-muted small">items per page</span>
          </div>
          <b-pagination
            v-if="ppmpItems.length > itemsPerPage"
            v-model="itemsCurrentPage"
            :total-rows="ppmpItems.length"
            :per-page="itemsPerPage"
            size="sm"
            class="mb-0"
          />
        </div>
        <div v-if="!ppmpItems.length" class="text-muted small">
          No PPMP items are available for consolidation.
        </div>
      </div>

      <div
        v-if="priceVarianceGroups.length"
        v-show="activeTab === 'matches'"
        class="ppmp-average-preview"
      >
        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
          <div>
            <h6 class="mb-1">Matching items with different unit costs</h6>
            <p class="text-muted mb-0 small">
              These items match existing APP or approved PPMP items. Consolidation uses the combined ABC and quantity to compute unit cost.
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

                <!-- PR conflict warning: existing item in this group already has an active PR -->
                <div
                  v-if="groupAffectedPrs(group).length"
                  class="alert alert-danger py-2 mb-2 small"
                >
                  <strong><i class="ri-file-warning-line me-1"></i>Active PR{{ groupAffectedPrs(group).length > 1 ? 's' : '' }} will be affected:</strong>
                  {{ groupAffectedPrs(group).join(', ') }}<br />
                  The unit cost in {{ groupAffectedPrs(group).length > 1 ? 'these PRs' : 'this PR' }} will <em>not</em> update automatically. Coordinate with the Procurement Officer to revise {{ groupAffectedPrs(group).length > 1 ? 'them' : 'it' }} after consolidation.
                </div>

                <div
                  class="alert py-2 mb-2"
                  :class="group.requires_price_review ? 'alert-warning' : 'alert-info'"
                >
                  Price range:
                  <strong>{{ formatCurrency(group.minimum_unit_price) }}</strong>
                  to
                  <strong>{{ formatCurrency(group.maximum_unit_price) }}</strong>
                  <span v-if="group.price_spread_percentage !== null">
                    ({{ formatPercentage(group.price_spread_percentage) }} spread)
                  </span>
                  <span v-if="group.requires_price_review" class="d-block small mt-1">
                    This exceeds the 10% review threshold. Confirm the specifications are truly equivalent before proceeding.
                  </span>
                </div>
                <div class="ppmp-pricing-choice mb-3">
                  <div class="fw-semibold mb-2">Consolidated Unit Cost</div>
                  <div class="row g-2 align-items-end">
                    <div class="col-md-7">
                      <select
                        v-model="pricingSelections[group.group_key].method"
                        class="form-select form-select-sm"
                      >
                        <option value="weighted">
                          Weighted factor ({{ formatCurrency(group.computed_weighted_unit_cost) }})
                        </option>
                        <option value="average">
                          Simple average ({{ formatCurrency(group.average_unit_price) }})
                        </option>
                        <option value="manual">Manual unit cost</option>
                      </select>
                      <div
                        v-if="pricingSelections[group.group_key].method === 'average'"
                        class="alert alert-warning py-1 px-2 mt-1 mb-0 small"
                      >
                        <i class="ri-information-line me-1"></i>
                        Simple average ignores item quantities — use Weighted factor if quantities differ significantly.
                      </div>
                    </div>
                    <div
                      v-if="pricingSelections[group.group_key].method === 'manual'"
                      class="col-md-5"
                    >
                      <label class="form-label small mb-1">Manual Unit Cost</label>
                      <input
                        :value="pricingSelections[group.group_key].manual_unit_cost_display"
                        type="text"
                        inputmode="decimal"
                        class="form-control form-control-sm"
                        placeholder="0.00"
                        @input="updateManualUnitCost(group.group_key, $event.target.value)"
                        @blur="formatManualUnitCost(group.group_key)"
                      />
                      <div
                        v-if="Number(pricingSelections[group.group_key].manual_unit_cost || 0) > group.maximum_unit_price"
                        class="alert alert-warning py-1 px-2 mt-1 mb-0 small"
                      >
                        <i class="ri-alert-line me-1"></i>
                        Exceeds highest source price ({{ formatCurrency(group.maximum_unit_price) }}). Verify this is intentional.
                      </div>
                    </div>
                  </div>
                  <div class="small mt-2">
                    Selected unit cost:
                    <strong>{{ formatCurrency(selectedGroupPrice(group)) }}</strong>
                    · Revised ABC:
                    <strong>{{ formatCurrency(selectedGroupPrice(group) * Number(group.quantity || 0)) }}</strong>
                  </div>
                </div>
                <div class="table-responsive">
                  <table class="table table-sm table-bordered align-middle mb-0">
                    <thead class="table-light">
                      <tr>
                        <th>Source</th>
                        <th>Item Description</th>
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
                          <div class="fw-semibold">{{ item.name || group.name || "-" }}</div>
                          <div class="text-muted small">{{ plainText(item.description) }}</div>
                        </td>
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
                        <th colspan="3" class="text-end">
                          {{ selectedPricingLabel(group) }}
                        </th>
                        <th class="text-end">{{ formatQuantity(group.quantity) }}</th>
                        <th class="text-end">{{ formatCurrency(selectedGroupPrice(group)) }}</th>
                        <th class="text-end">
                          {{ formatCurrency(selectedGroupPrice(group) * Number(group.quantity || 0)) }}
                        </th>
                      </tr>
                    </tfoot>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div
        v-if="activeTab === 'matches' && !priceVarianceGroups.length"
        class="ppmp-average-empty"
      >
        No matching same-spec items were found in the APP or approved PPMPs.
      </div>

      <!-- Suggested keyword matches (different specs, shared keywords — won't auto-consolidate) -->
      <div
        v-if="activeTab === 'matches' && suggestedMatchGroups.length"
        class="ppmp-suggested-matches"
      >
        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
          <div>
            <h6 class="mb-1">
              <i class="ri-search-eye-line me-1 text-info"></i>
              Suggested keyword matches
            </h6>
            <p class="text-muted mb-0 small">
              These items share description keywords with existing APP items but have <strong>different specs</strong> — they will <em>not</em> auto-consolidate. Review to confirm they are correctly separated.
            </p>
          </div>
          <b-badge variant="info">{{ suggestedMatchGroups.length }} item{{ suggestedMatchGroups.length === 1 ? "" : "s" }}</b-badge>
        </div>

        <div class="ppmp-suggested-list">
          <div
            v-for="group in suggestedMatchGroups"
            :key="group.id"
            class="ppmp-suggested-item"
          >
            <div class="ppmp-suggested-item__header">
              <div class="flex-grow-1">
                <span class="fw-semibold">{{ group.name || "-" }}</span>
                <span class="text-muted ms-2 small">{{ formatQuantity(group.quantity) }} {{ group.unit || "" }} · {{ formatCurrency(group.unit_price) }}</span>
              </div>
              <span class="ppmp-suggested-item__ppmp">{{ group.ppmp_no || "" }}</span>
            </div>
            <div class="ppmp-suggested-item__matches">
              <div
                v-for="(match, mi) in group.matches.filter(m => !m.will_consolidate_automatically)"
                :key="mi"
                class="ppmp-suggested-match"
              >
                <div class="ppmp-suggested-match__info">
                  <span class="fw-semibold small">{{ match.name || group.name }}</span>
                  <span class="text-muted small ms-1">{{ formatQuantity(match.quantity) }} · {{ formatCurrency(match.unit_price) }}</span>
                </div>
                <div v-if="match.matched_keywords && match.matched_keywords.length" class="ppmp-suggested-match__keywords">
                  <span
                    v-for="kw in match.matched_keywords"
                    :key="kw"
                    class="ppmp-keyword-chip"
                  >{{ kw }}</span>
                </div>
                <small class="text-muted d-block">{{ match.match_reason || "Shared keywords" }}</small>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="ppmp-review-acknowledgement">
        <div class="form-check">
          <input
            id="consolidation-review-acknowledged"
            v-model="reviewAcknowledged"
            class="form-check-input"
            type="checkbox"
          />
          <label class="form-check-label" for="consolidation-review-acknowledged">
            I reviewed the exact matches, suggestion-only items, quantities, and unit-cost differences.
          </label>
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
        @click="confirmConsolidation"
        variant="success"
        :disabled="processing || !reviewAcknowledged || hasInvalidManualPrice"
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
  data() {
    return {
      reviewAcknowledged: false,
      itemsCurrentPage: 1,
      itemsPerPage: 5,
      itemsPerPageOptions: [5, 10, 25, 50],
      pricingSelections: {},
      activeTab: "items",
    };
  },
  watch: {
    show(value) {
      if (value) {
        this.reviewAcknowledged = false;
        this.itemsCurrentPage = 1;
        this.activeTab = "items";
        this.initializePricingSelections();
      }
    },
    priceVarianceGroups: {
      immediate: true,
      handler() {
        this.initializePricingSelections();
      },
    },
  },
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
    suggestedMatchGroups() {
      return this.matchGroups.filter((group) =>
        Array.isArray(group.matches) &&
        group.matches.some((m) => !m.will_consolidate_automatically)
      );
    },
    ppmpItems() {
      if (Array.isArray(this.ppmp?.raw_item_details)) {
        return this.ppmp.raw_item_details;
      }

      return Array.isArray(this.ppmp?.item_details) ? this.ppmp.item_details : [];
    },
    ppmpItemsTotal() {
      return this.ppmpItems.reduce((total, item) => total + this.itemAmount(item), 0);
    },
    paginatedPpmpItems() {
      const start = (this.itemsCurrentPage - 1) * this.itemsPerPage;

      return this.ppmpItems
        .slice(start, start + this.itemsPerPage)
        .map((item, index) => ({
          ...item,
          displayIndex: start + index + 1,
        }));
    },
    hasInvalidManualPrice() {
      return this.priceVarianceGroups.some((group) => {
        const selection = this.pricingSelections[group.group_key];

        return selection?.method === "manual"
          && (selection.manual_unit_cost === "" || selection.manual_unit_cost === null || Number(selection.manual_unit_cost) < 0);
      });
    },
    sameSpecGroupCount() {
      return this.priceVarianceGroups.length + this.suggestedMatchGroups.length;
    },
  },
  methods: {
    initializePricingSelections() {
      this.pricingSelections = this.priceVarianceGroups.reduce((selections, group) => {
        selections[group.group_key] = {
          method: "weighted",
          manual_unit_cost: null,
          manual_unit_cost_display: "",
        };

        return selections;
      }, {});
    },
    selectedGroupPrice(group) {
      const selection = this.pricingSelections[group.group_key] || { method: "weighted" };

      if (selection.method === "average") {
        return Number(group.average_unit_price || 0);
      }

      if (selection.method === "manual") {
        return Number(selection.manual_unit_cost || 0);
      }

      return Number(group.computed_weighted_unit_cost || 0);
    },
    updateManualUnitCost(groupKey, value) {
      const selection = this.pricingSelections[groupKey];
      if (!selection) {
        return;
      }

      const normalized = String(value || "")
        .replace(/[^0-9.]/g, "")
        .replace(/(\..*)\./g, "$1");

      selection.manual_unit_cost_display = normalized;
      selection.manual_unit_cost = normalized === "" ? null : Number(normalized);
    },
    formatManualUnitCost(groupKey) {
      const selection = this.pricingSelections[groupKey];
      if (!selection || selection.manual_unit_cost === null) {
        return;
      }

      selection.manual_unit_cost_display = this.formatDecimal(selection.manual_unit_cost);
    },
    groupAffectedPrs(group) {
      if (!Array.isArray(group.items)) return [];
      const seen = new Set();
      const prs = [];
      group.items.forEach((item) => {
        if (item.source === 'This PPMP') return;
        String(item.pr_no || '').split(',').map((p) => p.trim()).filter(Boolean).forEach((pr) => {
          if (!seen.has(pr)) { seen.add(pr); prs.push(pr); }
        });
      });
      return prs;
    },
    selectedPricingLabel(group) {
      const method = this.pricingSelections[group.group_key]?.method || "weighted";

      if (method === "average") {
        return "Simple Average Unit Cost";
      }

      if (method === "manual") {
        return "Manual Unit Cost";
      }

      return "Weighted Unit Cost";
    },
    confirmConsolidation() {
      if (this.hasInvalidManualPrice) {
        return;
      }

      const consolidationPricing = this.priceVarianceGroups.map((group) => {
        const selection = this.pricingSelections[group.group_key] || { method: "weighted" };

        return {
          group_key: group.group_key,
          method: selection.method,
          manual_unit_cost: selection.method === "manual"
            ? Number(selection.manual_unit_cost)
            : null,
        };
      });

      this.$emit("confirm", {
        consolidation_review_acknowledged: this.reviewAcknowledged,
        consolidation_pricing: consolidationPricing,
      });
    },
    formatCurrency(value) {
      return new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
      }).format(Number(value || 0));
    },
    formatDecimal(value) {
      return new Intl.NumberFormat("en-PH", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      }).format(Number(value || 0));
    },
    formatQuantity(value) {
      const number = Number(value || 0);

      return Number.isInteger(number) ? number.toLocaleString() : number.toLocaleString(undefined, { maximumFractionDigits: 2 });
    },
    formatPercentage(value) {
      return `${Number(value || 0).toLocaleString(undefined, { maximumFractionDigits: 2 })}%`;
    },
    itemAmount(item) {
      return Number(item?.abc ?? item?.total_cost ?? (Number(item?.quantity || 0) * Number(item?.unit_price || 0)));
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

.ppmp-consolidation-tabs {
  grid-column: 1 / -1;
  display: flex;
  gap: 6px;
  padding: 5px;
  background: #f1f5f9;
  border: 1px solid #d8dee6;
  border-radius: 10px;
}

.ppmp-consolidation-tab {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 7px;
  min-height: 40px;
  padding: 8px 14px;
  color: #64748b;
  font-size: 13px;
  font-weight: 700;
  background: transparent;
  border: 0;
  border-radius: 7px;
}

.ppmp-consolidation-tab:hover {
  color: #405189;
}

.ppmp-consolidation-tab.active {
  color: #405189;
  background: #fff;
  box-shadow: 0 2px 8px rgba(15, 23, 42, .08);
}

.ppmp-items-preview,
.ppmp-average-preview,
.ppmp-average-empty,
.ppmp-review-acknowledgement {
  grid-column: 1 / -1;
}

.ppmp-review-acknowledgement {
  padding: 12px;
  border: 1px solid #d8dee6;
  border-radius: 8px;
  background: #fff;
  font-weight: 600;
}

.ppmp-items-preview {
  padding: 12px;
  border: 1px solid #d8dee6;
  border-radius: 8px;
  background: #fff;
}

.ppmp-items-table-container {
  overflow: hidden;
  border: 1px solid #d8dee6;
  border-radius: 10px;
  box-shadow: 0 4px 15px rgba(0, 0, 0, .05);
}

.ppmp-items-table-scroll {
  max-height: 360px;
}

.ppmp-items-table {
  width: 100%;
  min-width: 920px;
  border-collapse: collapse;
  background: #fff;
}

.ppmp-items-table thead {
  position: sticky;
  top: 0;
  z-index: 1;
  color: #fff;
  background: #4c5f98;
}

.ppmp-items-table th {
  padding: .75rem .85rem;
  font-size: .9rem;
  font-weight: 600;
  letter-spacing: .5px;
  text-transform: uppercase;
}

.ppmp-items-table td {
  padding: .75rem .85rem;
  color: #212529;
  vertical-align: top;
  background: #fff;
  border-bottom: 1px solid #d8dee6;
}

.ppmp-items-table tbody tr:nth-child(even) td,
.ppmp-items-table tfoot th {
  background: #f8fafc;
}

.ppmp-items-table tbody tr:hover td {
  background: #f1f5f9;
}

.ppmp-item-number {
  color: #667eea;
  font-weight: 600;
}

.ppmp-unit-badge {
  display: inline-block;
  padding: .25rem .75rem;
  color: #405189;
  font-size: .8rem;
  font-weight: 500;
  white-space: nowrap;
  background: #e8ecf7;
  border-radius: 15px;
}

.ppmp-item-cost,
.ppmp-total-amount {
  color: #28a745;
  font-family: "Courier New", monospace;
  font-weight: 700;
}

.ppmp-items-table tfoot th {
  padding: .75rem .85rem;
  border: 0;
}

.ppmp-total-label {
  color: #6b7280;
  font-weight: 800;
}

.ppmp-total-amount {
  font-weight: 800;
}

.ppmp-items-per-page {
  width: 72px;
}

.ppmp-average-preview {
  padding: 12px;
  border: 1px solid #fde7ba;
  border-radius: 8px;
  background: #fffaf0;
}

.ppmp-pricing-choice {
  padding: 10px;
  border: 1px solid #d8dee6;
  border-radius: 8px;
  background: #fff;
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

.ppmp-average-accordion .accordion-button {
  padding: 10px 12px;
  font-size: 13px;
}

.ppmp-average-accordion .accordion-body {
  padding: 12px;
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

.ppmp-suggested-matches {
  grid-column: 1 / -1;
  padding: 12px;
  border: 1px solid #b6d4fe;
  border-radius: 8px;
  background: #f0f7ff;
}

.ppmp-suggested-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.ppmp-suggested-item {
  padding: 10px 12px;
  border: 1px solid #c9ddf9;
  border-left: 3px solid #0ea5e9;
  border-radius: 8px;
  background: #ffffff;
}

.ppmp-suggested-item__header {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 8px;
}

.ppmp-suggested-item__ppmp {
  color: #0369a1;
  font-size: 11px;
  font-weight: 700;
  flex-shrink: 0;
}

.ppmp-suggested-item__matches {
  display: flex;
  flex-direction: column;
  gap: 6px;
  padding-top: 8px;
  border-top: 1px solid #e0edfd;
}

.ppmp-suggested-match {
  padding: 6px 8px;
  border: 1px solid #dbeafe;
  border-radius: 6px;
  background: #f8fbff;
}

.ppmp-suggested-match__info {
  margin-bottom: 4px;
}

.ppmp-suggested-match__keywords {
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
  margin-bottom: 4px;
}

.ppmp-keyword-chip {
  display: inline-flex;
  align-items: center;
  padding: 1px 7px;
  border: 1px solid #bfdbfe;
  border-radius: 999px;
  background: #dbeafe;
  color: #1d4ed8;
  font-size: 10px;
  font-weight: 700;
}

@media (max-width: 992px) {
  .ppmp-confirm,
  .ppmp-confirm__summary {
    grid-template-columns: 1fr;
  }

  .ppmp-consolidation-tabs {
    flex-direction: column;
  }

  .ppmp-consolidation-tab {
    justify-content: flex-start;
  }
}
</style>
