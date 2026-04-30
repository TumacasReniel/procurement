<template>
  <b-modal
    v-model="showModal"
    header-class="p-3 bg-light"
    :title="dialogOptions.title"
    size="xl"
    class="v-modal-custom"
    modal-class="zoomIn"
    centered
    no-close-on-backdrop
  >
    <div v-if="loading" class="py-5 text-center">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
      <div class="mt-3 text-muted">{{ dialogOptions.loadingMessage }}</div>
    </div>

    <div v-else>
      <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
        <div>
          <div class="text-muted fs-12">Purchase Order</div>
          <div class="fw-semibold">{{ displayPoCode }}</div>
        </div>

        <div>
          <div class="text-muted fs-12">{{ dialogOptions.referenceLabel }}</div>
          <div class="fw-semibold text-primary">{{ referenceDisplay }}</div>
        </div>

        <div class="text-md-end">
          <div class="text-muted fs-12">Delivery Type</div>
          <div class="fw-semibold" :class="isPartialDelivery ? 'text-warning' : 'text-success'">
            {{ isPartialDelivery ? "Partial Delivery" : "Full Delivery" }}
          </div>
        </div>
      </div>

      <div class="alert alert-info py-2 fs-12">
        {{ dialogOptions.infoMessage }}
      </div>

      <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div class="form-check mb-0">
          <input
            id="iar-select-all-items"
            class="form-check-input"
            type="checkbox"
            :checked="allSelected"
            @change="toggleAll($event.target.checked)"
          />
          <label class="form-check-label" for="iar-select-all-items">
            {{ dialogOptions.selectAllLabel }}
          </label>
        </div>

        <div class="text-muted fs-12">
          {{ selectedCount }} of {{ selection.items.length }} item(s) selected
        </div>
      </div>

      <InputError :message="form.errors.delivered_items" class="mb-2" />

      <div class="table-responsive border rounded">
        <table class="table align-middle mb-0 iar-selection-table">
          <thead>
            <tr class="fs-11">
              <th style="width: 6%" class="text-center">Pick</th>
              <th style="width: 10%">Item No</th>
              <th>Description</th>
              <th style="width: 10%" class="text-center">Available Qty</th>
              <th style="width: 12%" class="text-center">Delivered Qty</th>
              <th style="width: 10%" class="text-center">Unit</th>
              <th style="width: 12%" class="text-end">Unit Cost</th>
              <th style="width: 12%" class="text-end">Amount</th>
            </tr>
          </thead>

          <tbody>
            <tr v-for="(item, index) in form.delivered_items" :key="item.item_id">
              <td class="text-center">
                <input
                  :id="`iar-item-${item.item_id}`"
                  class="form-check-input"
                  type="checkbox"
                  :checked="item.selected"
                  @change="toggleItem(item.item_id, $event.target.checked)"
                />
              </td>
              <td>{{ item.item_no || "-" }}</td>
              <td>
                <div v-html="item.description || '-'" />
                <small v-if="item.received_quantity > 0" class="text-muted d-block mt-1">
                  Received: {{ formatQuantity(item.received_quantity) }}
                </small>
                <small v-if="item.already_delivered_quantity > 0" class="text-muted d-block">
                  Already in IAR: {{ formatQuantity(item.already_delivered_quantity) }}
                </small>
              </td>
              <td class="text-center">{{ formatQuantity(item.available_quantity) }}</td>
              <td class="text-center">
                <input
                  v-model.number="item.delivered_quantity"
                  type="number"
                  min="0"
                  step="any"
                  :max="item.available_quantity"
                  class="form-control form-control-sm text-center"
                  :class="{ 'is-invalid': isInvalidQuantity(item, index) }"
                  :disabled="!item.selected"
                  @input="handleQuantityInput(item)"
                />
                <small
                  v-if="item.selected && quantityErrorMessage(item, index)"
                  class="text-danger d-block mt-1 text-start"
                >
                  {{ quantityErrorMessage(item, index) }}
                </small>
                <small v-if="item.selected" class="text-muted d-block mt-1">
                  Max: {{ formatQuantity(item.available_quantity) }}
                </small>
              </td>
              <td class="text-center">{{ item.unit || "-" }}</td>
              <td class="text-end">{{ formatCurrency(item.unit_cost) }}</td>
              <td class="text-end">{{ formatCurrency(item.unit_cost * (Number(item.delivered_quantity) || 0)) }}</td>
            </tr>

            <tr v-if="form.delivered_items.length === 0">
              <td colspan="8" class="text-center py-4 text-muted">
                {{ emptyItemsMessage }}
              </td>
            </tr>
          </tbody>

          <tfoot v-if="form.delivered_items.length">
            <tr class="fw-semibold">
              <td colspan="7" class="text-end">Selected Amount</td>
              <td class="text-end">{{ formatCurrency(selectedAmount) }}</td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>

    <template v-slot:footer>
      <b-button @click="hide()" variant="light" block>Cancel</b-button>
      <b-button
        @click="submit()"
        variant="primary"
        :disabled="loading || isSubmitting || !selectedCount || hasInvalidSelectedQuantities"
        block
      >
        {{ isSubmitting ? processingLabel : dialogOptions.submitLabel }}
      </b-button>
    </template>
  </b-modal>
</template>

<script>
import { useForm } from "@inertiajs/vue3";
import InputError from "@/Shared/Components/Forms/InputError.vue";

const emptySelection = () => ({
  po_id: null,
  po_code: null,
  iar_id: null,
  iar_code: null,
  items: [],
});

const defaultDialogOptions = () => ({
  title: "Select Delivered Items",
  submitLabel: "Generate & Print IAR",
  infoMessage: "Select the delivered items and enter the actual delivered quantity for each one. The IAR report will use those quantities.",
  printAfterSave: true,
  iarId: null,
  onSuccess: null,
  referenceLabel: "IAR No.",
  referenceValue: null,
  referenceFallback: "To be generated",
  selectionUrl: "/faims/purchase-orders",
  saveUrl: null,
  selectionOption: "iar_selection",
  saveOption: "update_iar_selection",
  selectionParams: {},
  savePayload: {},
  submitUsingAxios: false,
  keepOpenAfterSave: false,
  errorContext: "IAR",
  loadingMessage: "Loading delivered items for IAR...",
  emptyItemsMessage: "All items for this Purchase Order are already fully delivered.",
  selectAllLabel: "Select all available items",
});

export default {
  components: { InputError },
  data() {
    return {
      showModal: false,
      loading: false,
      submitting: false,
      selection: emptySelection(),
      dialogOptions: defaultDialogOptions(),
      form: useForm({
        id: null,
        delivered_items: [],
        option: "update_iar_selection",
      }),
    };
  },
  computed: {
    normalizedDeliveredItems() {
      return this.form.delivered_items
        .filter((item) => item.selected)
        .map((item) => ({
          item_id: Number(item.item_id),
          delivered_quantity: Number(item.delivered_quantity),
          available_quantity: Number(item.available_quantity),
          unit_cost: Number(item.unit_cost) || 0,
        }))
        .filter((item) => item.item_id);
    },
    validDeliveredItems() {
      return this.normalizedDeliveredItems.filter((item) =>
        this.isQuantityValueValid(item.delivered_quantity, item.available_quantity)
      );
    },
    selectedCount() {
      return this.form.delivered_items.filter((item) => item.selected).length;
    },
    allSelected() {
      return this.form.delivered_items.length > 0 && this.form.delivered_items.every((item) => item.selected);
    },
    isPartialDelivery() {
      return this.form.delivered_items.length > 0 && (
        this.selectedCount < this.form.delivered_items.length ||
        this.normalizedDeliveredItems.some((item) => (
          !this.isQuantityValueValid(item.delivered_quantity, item.available_quantity)
          || item.delivered_quantity < item.available_quantity
        ))
      );
    },
    hasInvalidSelectedQuantities() {
      return this.form.delivered_items.some((item, index) => this.isInvalidQuantity(item, index));
    },
    selectedAmount() {
      return this.validDeliveredItems.reduce((sum, item) => {
        return sum + item.unit_cost * item.delivered_quantity;
      }, 0);
    },
    processingLabel() {
      return this.dialogOptions.printAfterSave ? "Generating..." : "Saving...";
    },
    isSubmitting() {
      return this.form.processing || this.submitting;
    },
    emptyItemsMessage() {
      return this.form.errors.delivered_items || this.dialogOptions.emptyItemsMessage;
    },
    displayPoCode() {
      return this.selection.po_code || this.dialogOptions.poCodeFallback || "-";
    },
    referenceDisplay() {
      return this.dialogOptions.referenceValue
        || this.selection.iar_code
        || this.selection.receiving_code
        || this.dialogOptions.referenceFallback;
    },
  },
  methods: {
    show(data, options = {}) {
      this.form.reset();
      this.form.clearErrors();
      this.selection = {
        ...emptySelection(),
        po_id: data.id || null,
        po_code: data.code || null,
        items: this.fallbackSelectionItems(data),
      };
      this.dialogOptions = {
        ...defaultDialogOptions(),
        ...options,
        poCodeFallback: data.code || data.po_code || null,
      };
      this.form.id = data.id;
      this.showModal = true;
      this.loading = true;

      axios
        .get(this.dialogOptions.selectionUrl, {
          params: {
            option: this.dialogOptions.selectionOption,
            po_id: data.id,
            iar_id: this.dialogOptions.iarId || null,
            ...this.dialogOptions.selectionParams,
          },
        })
        .then((response) => {
          const payload = response?.data || emptySelection();
          const payloadItems = this.normalizeSelectionItems(payload.items);
          const fallbackItems = this.normalizeSelectionItems(this.selection.items);
          const resolvedItems = payloadItems.length ? payloadItems : fallbackItems;

          this.selection = {
            ...emptySelection(),
            ...payload,
            po_id: payload.po_id || data.id || null,
            po_code: payload.po_code || data.code || data.po_code || null,
            items: resolvedItems,
          };
          this.form.delivered_items = this.selection.items.map((item) => ({
            item_id: Number(item.id),
            item_no: item.item_no,
            description: item.description,
            received_quantity: Number(item.received_quantity ?? 0),
            already_delivered_quantity: Number(item.already_delivered_quantity ?? 0),
            available_quantity: Number(item.available_quantity ?? item.ordered_quantity ?? item.quantity ?? 0),
            delivered_quantity: Number(item.delivered_quantity ?? 0),
            unit: item.unit,
            unit_cost: Number(item.unit_cost ?? 0),
            selected: Boolean(item.is_selected),
          }));
        })
        .catch((error) => {
          console.error(error);
          this.form.setError(
            "delivered_items",
            `Unable to load delivered items for this ${this.dialogOptions.errorContext}.`
          );
        })
        .finally(() => {
          this.loading = false;
        });
    },
    fallbackSelectionItems(data) {
      const monitoringItems = Array.isArray(data?.delivery_monitoring_items)
        ? data.delivery_monitoring_items
        : [];

      return monitoringItems
        .filter((item) => Number(item?.remaining_quantity ?? item?.available_quantity ?? 0) > 0)
        .map((item) => {
          const availableQuantity = Number(item.remaining_quantity ?? item.available_quantity ?? 0);

          return {
            id: item.id,
            item_no: item.item_no,
            description: item.description || item.item_name,
            already_delivered_quantity: Number(item.delivered_quantity ?? item.already_delivered_quantity ?? 0),
            available_quantity: availableQuantity,
            delivered_quantity: availableQuantity,
            is_selected: true,
            unit: item.unit,
            unit_cost: Number(item.unit_cost ?? 0),
          };
        });
    },
    normalizeSelectionItems(items) {
      if (Array.isArray(items)) {
        return items;
      }

      if (items && typeof items === "object") {
        return Object.values(items);
      }

      return [];
    },
    hide() {
      this.showModal = false;
      this.loading = false;
      this.selection = emptySelection();
      this.dialogOptions = defaultDialogOptions();
      this.form.reset();
      this.form.clearErrors();
    },
    toggleItem(itemId, checked) {
      const row = this.form.delivered_items.find((item) => Number(item.item_id) === Number(itemId));

      if (!row) {
        return;
      }

      row.selected = checked;

      if (
        checked &&
        !this.isQuantityValueValid(Number(row.delivered_quantity), Number(row.available_quantity))
      ) {
        row.delivered_quantity = Number(row.available_quantity) || 0;
      }

      this.form.clearErrors();
    },
    toggleAll(checked) {
      this.form.delivered_items = this.form.delivered_items.map((item) => ({
        ...item,
        selected: checked,
        delivered_quantity: checked && !this.isQuantityValueValid(
          Number(item.delivered_quantity),
          Number(item.available_quantity)
        )
          ? Number(item.available_quantity) || 0
          : item.delivered_quantity,
      }));
      this.form.clearErrors();
    },
    handleQuantityInput() {
      this.form.clearErrors();
    },
    isQuantityValueValid(deliveredQuantity, availableQuantity) {
      return Number.isFinite(deliveredQuantity)
        && deliveredQuantity > 0
        && deliveredQuantity <= availableQuantity;
    },
    quantityErrorMessage(item, index) {
      const serverMessage = this.form.errors[`delivered_items.${index}.delivered_quantity`]
        || this.form.errors[`delivered_items.${index}`];

      if (serverMessage) {
        return serverMessage;
      }

      if (!item.selected) {
        return null;
      }

      const deliveredQuantity = Number(item.delivered_quantity);
      const availableQuantity = Number(item.available_quantity);

      if (!Number.isFinite(deliveredQuantity) || deliveredQuantity <= 0) {
        return "Delivered quantity must be greater than zero.";
      }

      if (deliveredQuantity > availableQuantity) {
        return `Delivered quantity must not exceed ${this.formatQuantity(availableQuantity)}.`;
      }

      return null;
    },
    isInvalidQuantity(item, index) {
      if (!item.selected) {
        return false;
      }

      return Boolean(this.quantityErrorMessage(item, index));
    },
    submit() {
      const delivered_items = this.normalizedDeliveredItems.map((item) => ({
        item_id: item.item_id,
        delivered_quantity: item.delivered_quantity,
      }));

      if (!delivered_items.length) {
        this.form.setError("delivered_items", "Please select at least one delivered item.");
        return;
      }

      if (this.hasInvalidSelectedQuantities) {
        this.form.setError(
          "delivered_items",
          "Delivered quantity must be greater than zero and must not exceed the available quantity."
        );
        return;
      }

      this.form.clearErrors();

      let print_window = null;

      if (this.dialogOptions.printAfterSave) {
        print_window = window.open("", "_blank");
      }

      const payload = {
        ...this.form.data(),
        iar_id: this.selection.iar_id || this.dialogOptions.iarId || null,
        delivered_items,
        option: this.dialogOptions.saveOption,
        ...this.dialogOptions.savePayload,
      };

      if (this.dialogOptions.submitUsingAxios) {
        this.submitting = true;

        axios
          .put(this.dialogOptions.saveUrl || `/faims/purchase-orders/${this.form.id}`, payload, {
            headers: {
              Accept: "application/json",
            },
          })
          .then((response) => {
            const result = response?.data || {};
            const status = result.status;

            if (status !== true && status !== "success") {
              this.form.setError(
                "delivered_items",
                result.info || result.message || "Unable to save the delivered item selection."
              );

              if (result.errors && typeof result.errors === "object") {
                Object.entries(result.errors).forEach(([field, message]) => {
                  this.form.setError(field, Array.isArray(message) ? message[0] : message);
                });
              }

              return;
            }

            const response_data = result.data || null;
            const on_success = this.dialogOptions.onSuccess;

            this.$emit("updated", response_data);

            if (!this.dialogOptions.keepOpenAfterSave) {
              this.hide();
            }

            if (typeof on_success === "function") {
              on_success(response_data);
            }
          })
          .catch((error) => {
            const errors = error?.response?.data?.errors || {};

            if (Object.keys(errors).length) {
              Object.entries(errors).forEach(([field, message]) => {
                this.form.setError(field, Array.isArray(message) ? message[0] : message);
              });
            } else {
              this.form.setError(
                "delivered_items",
                `Unable to save the ${this.dialogOptions.errorContext} record.`
              );
            }
          })
          .finally(() => {
            this.submitting = false;
          });

        return;
      }

      this.form
        .transform(() => payload)
        .put(this.dialogOptions.saveUrl || `/faims/purchase-orders/${this.form.id}`, {
          preserveScroll: true,
          onSuccess: (page) => {
            const flash = page?.props?.flash || {};
            const status = flash.status;
            const on_success = this.dialogOptions.onSuccess;

            if (status !== true && status !== "success") {
              if (print_window && !print_window.closed) {
                print_window.close();
              }

              this.form.setError(
                "delivered_items",
                flash.info || flash.message || "Unable to save the delivered item selection."
              );
              return;
            }

            const response_data = flash.data || null;
            const iar_id = response_data?.iar_id || null;
            const print_params = new URLSearchParams({
              option: "print",
              type: "iar",
            });

            if (iar_id) {
              print_params.set("iar_id", iar_id);
            }

            const print_url = `/faims/purchase-orders/${this.form.id}?${print_params.toString()}`;

            if (print_window && !print_window.closed) {
              print_window.location.href = print_url;
            } else if (this.dialogOptions.printAfterSave) {
              window.open(print_url, "_blank");
            }

            this.$emit("updated", response_data);
            this.hide();

            if (typeof on_success === "function") {
              setTimeout(() => {
                on_success(response_data);
              }, 150);
            }
          },
          onError: (errors) => {
            if (print_window && !print_window.closed) {
              print_window.close();
            }

            if (!errors?.delivered_items) {
              this.form.setError(
                "delivered_items",
                this.dialogOptions.iarId
                  ? `Unable to update the ${this.dialogOptions.errorContext} record.`
                  : `Unable to save the ${this.dialogOptions.errorContext} record.`
              );
            }
          },
        });
    },
    formatQuantity(value) {
      const quantity = Number(value) || 0;

      return Number.isInteger(quantity)
        ? quantity.toString()
        : quantity.toLocaleString("en-US", {
            minimumFractionDigits: 0,
            maximumFractionDigits: 4,
          });
    },
    formatCurrency(value) {
      return new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
      }).format(Number(value) || 0);
    },
  },
};
</script>

<style scoped>
.iar-selection-table thead {
  background: #4a5b93;
  color: #ffffff;
}

.iar-selection-table th {
  background-color: #4a5b93;
  border-bottom: 0;
  color: inherit;
  font-size: 0.72rem;
  font-weight: 800;
  letter-spacing: 0.06em;
  text-transform: uppercase;
}
</style>
