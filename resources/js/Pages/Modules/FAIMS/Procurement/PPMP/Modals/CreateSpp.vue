<template>
  <b-modal
    v-model="modalShow"
    header-class="p-3"
    title="Create SPP"
    size="xl"
    class="v-modal-custom"
    modal-class="zoomIn"
    centered
    no-close-on-backdrop
  >
    <form class="customform spp-create-modal">
      <div class="content-card spp-details-card">
        <div class="card-header-custom">
          <i class="ri-file-add-line card-header-icon"></i>
          <h5 class="card-header-title">Supplemental Procurement Plan</h5>
        </div>
        <div class="card-body-custom">
          <BRow class="g-3 align-items-end">
            <BCol lg="4">
              <label class="form-label">SPP Year</label>
              <input
                type="text"
                class="form-control"
                :class="{ 'is-invalid': form.errors.year || form.errors.plan_type }"
                :value="currentYear"
                readonly
              />
              <div v-if="form.errors.year || form.errors.plan_type" class="invalid-feedback d-block">
                {{ form.errors.year || form.errors.plan_type }}
              </div>
            </BCol>

            <BCol lg="8">
              <label class="form-label">Requesting Unit</label>
              <Multiselect
                :options="unitOptions"
                v-model="form.unit_id"
                label="name"
                value-prop="value"
                :searchable="true"
                :append-to-body="true"
                :class="{ 'is-invalid': form.errors.unit_id }"
                placeholder="Select unit"
              />
              <div v-if="form.errors.unit_id" class="invalid-feedback d-block">
                {{ form.errors.unit_id }}
              </div>
            </BCol>
          </BRow>
        </div>
      </div>

      <div class="content-card spp-items-card mt-3">
        <div class="card-header-custom">
          <i class="ri-shopping-bag-line card-header-icon"></i>
          <h5 class="card-header-title">Procurement Items</h5>
          <div class="ms-auto d-flex align-items-center gap-2">
            <span class="spp-total-pill">{{ formatCurrency(sppItemTotalCost) }}</span>
            <b-button
              size="sm"
              variant="primary"
              class="add-item-btn"
              @click="openSppAddItemModal"
            >
              <i class="ri-add-line me-1"></i>
              {{ hasSppItem ? "Edit Item" : "Add Item" }}
            </b-button>
          </div>
        </div>
        <div class="card-body-custom">
          <div v-if="hasSppItem" class="items-table-container">
            <div class="table-responsive">
              <table class="items-table spp-item-table">
                <thead>
                  <tr>
                    <th class="text-center">#</th>
                    <th>Unit</th>
                    <th>Name/Description</th>
                    <th>Type / Mode</th>
                    <th class="text-center">Qty</th>
                    <th class="text-end">Unit Cost</th>
                    <th class="text-end">Total</th>
                  </tr>
                </thead>
                <tbody>
                  <tr class="item-row">
                    <td class="text-center item-number">1</td>
                    <td class="item-unit">
                      <span class="unit-badge">{{ selectedSppUnitTypeLabel }}</span>
                    </td>
                    <td class="item-description">
                      <span>{{ form.item_name || "-" }}</span>
                      <div v-html="form.item_description"></div>
                    </td>
                    <td>
                      <span class="d-block fw-semibold">{{ form.project_type || "-" }}</span>
                      <small class="text-muted">{{ form.item_category || "-" }}</small>
                      <small class="text-muted d-block">{{ form.recommended_mode_of_procurement || "-" }}</small>
                      <small class="text-muted d-block">Pre-Proc: {{ form.pre_procurement_conference || "-" }}</small>
                    </td>
                    <td class="text-center item-quantity">
                      {{ form.item_quantity }}
                    </td>
                    <td class="text-end item-cost">
                      {{ formatCurrency(form.item_unit_cost) }}
                    </td>
                    <td class="text-end item-total">
                      {{ formatCurrency(sppItemTotalCost) }}
                    </td>
                  </tr>
                </tbody>
                <tfoot>
                  <tr class="grand-total-row">
                    <td colspan="6" class="text-end grand-total-label">Grand Total:</td>
                    <td class="text-end grand-total-amount">{{ formatCurrency(sppItemTotalCost) }}</td>
                  </tr>
                </tfoot>
              </table>
            </div>
          </div>
          <div v-else class="empty-state">
            <div class="empty-state-icon">
              <i class="ri-shopping-bag-line"></i>
            </div>
            <h6 class="empty-state-title">No Items Added</h6>
            <p class="empty-state-text">Click "Add Item" to start adding procurement items.</p>
          </div>

          <div v-if="sppItemError" class="text-danger small fw-semibold mt-2">
            {{ sppItemError }}
          </div>
        </div>
      </div>
    </form>

    <template v-slot:footer>
      <b-button @click="$emit('close')" variant="light" block>Close</b-button>
      <b-button
        @click="$emit('submit')"
        variant="warning"
        :disabled="form.processing || !canSubmitSppForm"
        block
      >
        {{ form.processing ? "Creating..." : "Create SPP" }}
      </b-button>
    </template>
  </b-modal>

  <AddItemModal
    ref="sppAddItemModal"
    mode="draft"
    :ppmp="sppDraftPpmp"
    :draft-item="sppDraftItem"
    :dropdowns="dropdowns"
    @draft="applySppDraftItem"
  />
</template>

<script>
import Multiselect from "@vueform/multiselect";
import AddItemModal from "./AddItem.vue";

export default {
  components: {
    Multiselect,
    AddItemModal,
  },
  props: {
    modelValue: {
      type: Boolean,
      default: false,
    },
    form: {
      type: Object,
      required: true,
    },
    dropdowns: {
      type: Object,
      default: () => ({}),
    },
    unitOptions: {
      type: Array,
      default: () => [],
    },
    currentYear: {
      type: Number,
      required: true,
    },
  },
  emits: ["update:modelValue", "close", "submit"],
  computed: {
    modalShow: {
      get() {
        return this.modelValue;
      },
      set(value) {
        this.$emit("update:modelValue", value);
      },
    },
    unitTypeOptions() {
      const options = this.dropdowns?.unit_types || [];
      const normalized = Array.isArray(options) ? options : Object.values(options);

      return normalized.map((unitType) => ({
        ...unitType,
        name: unitType.name || unitType.name_long || unitType.name_short || "Unit",
      }));
    },
    sppItemTotalCost() {
      return Number(this.form.item_quantity || 0) * Number(this.form.item_unit_cost || 0);
    },
    hasSppItem() {
      return Boolean(
        this.form.item_name ||
        this.form.item_description ||
        Number(this.form.item_quantity) > 0 ||
        this.form.item_unit_type_id ||
        Number(this.form.item_unit_cost) > 0
      );
    },
    sppDraftPpmp() {
      return { id: 0 };
    },
    sppDraftItem() {
      if (!this.hasSppItem) {
        return null;
      }

      return {
        item_name: this.form.item_name,
        item_description: this.form.item_description,
        project_type: this.form.project_type,
        item_category_id: this.form.item_category_id,
        item_category: this.form.item_category,
        recommended_mode_of_procurement: this.form.recommended_mode_of_procurement,
        pre_procurement_conference: this.form.pre_procurement_conference,
        general_description_objective: this.form.general_description_objective || "",
        item_quantity: this.form.item_quantity,
        item_unit_type_id: this.form.item_unit_type_id,
        item_unit_cost: this.form.item_unit_cost,
        end_of_procurement_activity: this.form.end_of_procurement_activity,
        expected_delivery_date: this.form.expected_delivery_date,
        attached_supporting_documents: this.form.attached_supporting_documents,
        supporting_document_file: this.form.supporting_document_file,
        remarks: this.form.remarks,
      };
    },
    selectedSppUnitTypeLabel() {
      const unitType = this.unitTypeOptions.find((item) => Number(item.value) === Number(this.form.item_unit_type_id));
      if (!unitType) {
        return "-";
      }

      return Number(this.form.item_quantity || 0) > 1
        ? (unitType.name_long || unitType.name || unitType.name_short || "-")
        : (unitType.name_short || unitType.name || unitType.name_long || "-");
    },
    sppItemError() {
      return this.form.errors.item_name ||
        this.form.errors.item_description ||
        this.form.errors.project_type ||
        this.form.errors.item_category_id ||
        this.form.errors.recommended_mode_of_procurement ||
        this.form.errors.pre_procurement_conference ||
        this.form.errors.item_quantity ||
        this.form.errors.item_unit_type_id ||
        this.form.errors.item_unit_cost ||
        this.form.errors.end_of_procurement_activity ||
        this.form.errors.expected_delivery_date ||
        this.form.errors.attached_supporting_documents ||
        this.form.errors.supporting_document_file ||
        this.form.errors.remarks ||
        null;
    },
    canSubmitSppForm() {
      return Boolean(
        this.form.unit_id &&
        this.form.item_name &&
        this.form.item_description &&
        this.form.project_type &&
        this.form.item_category_id &&
        this.form.recommended_mode_of_procurement &&
        this.form.pre_procurement_conference &&
        Number(this.form.item_quantity) > 0 &&
        this.form.item_unit_type_id &&
        Number(this.form.item_unit_cost) >= 0
      );
    },
  },
  methods: {
    openSppAddItemModal() {
      this.$refs.sppAddItemModal?.show();
    },
    applySppDraftItem(item) {
      this.form.item_name = item.item_name;
      this.form.item_description = item.item_description;
      this.form.project_type = item.project_type;
      this.form.item_category_id = item.item_category_id;
      this.form.item_category = item.item_category;
      this.form.recommended_mode_of_procurement = item.recommended_mode_of_procurement;
      this.form.pre_procurement_conference = item.pre_procurement_conference;
      if ("general_description_objective" in this.form) {
        this.form.general_description_objective = item.general_description_objective;
      }
      this.form.item_quantity = item.item_quantity;
      this.form.item_unit_type_id = item.item_unit_type_id;
      this.form.item_unit_cost = item.item_unit_cost;
      this.form.end_of_procurement_activity = item.end_of_procurement_activity;
      this.form.expected_delivery_date = item.expected_delivery_date;
      this.form.attached_supporting_documents = item.attached_supporting_documents;
      this.form.supporting_document_file = item.supporting_document_file;
      this.form.remarks = item.remarks;
      this.form.clearErrors();
    },
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
.spp-create-modal {
  --ppmp-border: rgba(91, 105, 153, .14);
  --ppmp-card: #ffffff;
  --ppmp-card-soft: #f8fafc;
  --ppmp-ink: #182039;
  --ppmp-muted: #6f7895;
  --ppmp-hover: rgba(64, 81, 137, .06);
  color: var(--ppmp-ink);
}

.spp-create-modal .content-card {
  border: 1px solid var(--ppmp-border);
  border-radius: 12px;
  background: var(--ppmp-card);
  overflow: hidden;
  box-shadow: 0 4px 15px rgba(0, 0, 0, .05);
}

.spp-create-modal .card-header-custom {
  display: flex;
  align-items: center;
  gap: .6rem;
  min-height: 48px;
  padding: .75rem .9rem;
  border-bottom: 1px solid var(--ppmp-border);
  background: var(--ppmp-card-soft);
}

.spp-create-modal .card-header-icon {
  color: #405189;
  font-size: 1.1rem;
}

.spp-create-modal .card-header-title {
  margin: 0;
  color: var(--ppmp-ink);
  font-size: .94rem;
  font-weight: 700;
}

.spp-create-modal .card-body-custom {
  padding: .95rem;
}

.spp-create-modal .form-label {
  color: var(--ppmp-ink);
  font-size: .8rem;
  font-weight: 700;
  margin-bottom: .35rem;
}

.spp-create-modal .add-item-btn {
  border-radius: 8px;
  font-weight: 600;
}

.spp-create-modal .add-item-btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 15px rgba(0, 123, 255, .24);
}

.spp-total-pill {
  display: inline-flex;
  align-items: center;
  min-height: 30px;
  padding: .2rem .65rem;
  border-radius: 999px;
  background: rgba(10, 179, 156, .14);
  color: #0ab39c;
  font-size: .82rem;
  font-weight: 700;
}

.spp-create-modal .items-table-container {
  border: 1px solid var(--ppmp-border);
  border-radius: 10px;
  overflow: hidden;
  box-shadow: 0 4px 15px rgba(0, 0, 0, .05);
}

.spp-item-table {
  width: 100%;
  border-collapse: collapse;
  background: var(--ppmp-card);
  color: var(--ppmp-ink);
}

.spp-item-table thead {
  background: #4c5f98;
  color: #fff;
}

.spp-item-table th {
  padding: .75rem .85rem;
  font-size: .86rem;
  font-weight: 600;
  letter-spacing: .5px;
  text-transform: uppercase;
}

.spp-item-table td {
  padding: .75rem .85rem;
  border-bottom: 1px solid var(--ppmp-border);
  vertical-align: top;
  background: var(--ppmp-card);
  color: var(--ppmp-ink);
}

.spp-item-table tbody tr:nth-child(even) td {
  background: var(--ppmp-card-soft);
}

.spp-item-table tbody tr:hover td {
  background: var(--ppmp-hover);
}

.spp-item-table .item-number {
  font-weight: 700;
  color: #667eea;
}

.spp-item-table .item-description {
  max-width: none;
}

.spp-item-table .item-quantity,
.spp-item-table .item-unit {
  font-weight: 600;
}

.spp-item-table .item-cost,
.spp-item-table .item-total {
  color: #28a745;
  font-family: "Courier New", monospace;
  font-weight: 700;
}

.spp-item-table tfoot td {
  background: var(--ppmp-card-soft);
  border-bottom: 0;
}

.grand-total-label {
  color: var(--ppmp-muted);
  font-weight: 700;
}

.grand-total-amount {
  color: #28a745;
  font-family: "Courier New", monospace;
  font-weight: 800;
}

.empty-state {
  text-align: center;
  padding: 2.25rem 1rem;
  border: 1px dashed var(--ppmp-border);
  border-radius: 10px;
  background: var(--ppmp-card-soft);
}

.empty-state-icon {
  width: 46px;
  height: 46px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  margin-bottom: .75rem;
  border-radius: 50%;
  background: rgba(64, 81, 137, .12);
  color: #405189;
  font-size: 1.35rem;
}

.empty-state-title {
  color: var(--ppmp-ink);
  font-weight: 700;
  margin-bottom: .25rem;
}

.empty-state-text {
  color: var(--ppmp-muted);
  font-size: .84rem;
  margin-bottom: 0;
}

:global([data-bs-theme="dark"]) .spp-create-modal {
  --ppmp-border: rgba(170, 184, 220, .16);
  --ppmp-card: #151e33;
  --ppmp-card-soft: #10192c;
  --ppmp-ink: #e8edf9;
  --ppmp-muted: #9aa8c7;
  --ppmp-hover: rgba(142, 164, 255, .1);
}
</style>
