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
      <div class="mt-3 text-muted">Loading delivered items for IAR...</div>
    </div>

    <div v-else>
      <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
        <div>
          <div class="text-muted fs-12">Purchase Order</div>
          <div class="fw-semibold">{{ selection.po_code || "-" }}</div>
        </div>

        <div>
          <div class="text-muted fs-12">IAR No.</div>
          <div class="fw-semibold text-primary">{{ selection.iar_code || "To be generated" }}</div>
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
            Select all available items
          </label>
        </div>

        <div class="text-muted fs-12">
          {{ selectedCount }} of {{ selection.items.length }} item(s) selected
        </div>
      </div>

      <InputError :message="form.errors.delivered_items || form.errors.selected_item_ids" class="mb-2" />

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
                <small v-if="item.already_delivered_quantity > 0" class="text-muted d-block mt-1">
                  Previously delivered: {{ formatQuantity(item.already_delivered_quantity) }}
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
                All items for this Purchase Order are already fully delivered.
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
        :disabled="loading || form.processing || !selectedCount || hasInvalidSelectedQuantities"
        block
      >
        {{ form.processing ? processingLabel : dialogOptions.submitLabel }}
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
  saved_item_ids: [],
  selected_item_ids: [],
  delivered_items: [],
  items: [],
});

const defaultDialogOptions = () => ({
  title: "Select Delivered Items",
  submitLabel: "Generate & Print IAR",
  infoMessage: "Select the delivered items and enter the actual delivered quantity for each one. The IAR report will use those quantities.",
  printAfterSave: true,
  iarId: null,
  onSuccess: null,
});

export default {
  components: { InputError },
  data() {
    return {
      showModal: false,
      loading: false,
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
  },
  methods: {
    show(data, options = {}) {
      this.form.reset();
      this.form.clearErrors();
      this.selection = emptySelection();
      this.dialogOptions = {
        ...defaultDialogOptions(),
        ...options,
      };
      this.form.id = data.id;
      this.showModal = true;
      this.loading = true;

      axios
        .get("/faims/purchase-orders", {
          params: {
            option: "iar_selection",
            po_id: data.id,
            iar_id: this.dialogOptions.iarId || null,
          },
        })
        .then((response) => {
          const payload = response?.data || emptySelection();

          this.selection = {
            ...emptySelection(),
            ...payload,
            items: Array.isArray(payload.items) ? payload.items : [],
          };
          this.form.delivered_items = this.selection.items.map((item) => ({
            item_id: Number(item.id),
            item_no: item.item_no,
            description: item.description,
            ordered_quantity: Number(item.ordered_quantity ?? item.quantity ?? 0),
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
          this.form.setError("delivered_items", "Unable to load delivered items for this IAR.");
        })
        .finally(() => {
          this.loading = false;
        });
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
      const deliveredItems = this.normalizedDeliveredItems.map((item) => ({
        item_id: item.item_id,
        delivered_quantity: item.delivered_quantity,
      }));

      if (!deliveredItems.length) {
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

      const printWindow = this.dialogOptions.printAfterSave ? window.open("", "_blank") : null;

      this.form.clearErrors();
      this.form.processing = true;

      axios
        .put(
          `/faims/purchase-orders/${this.form.id}`,
          {
            iar_id: this.selection.iar_id || this.dialogOptions.iarId || null,
            delivered_items: deliveredItems,
            option: "update_iar_selection",
          },
          {
            headers: {
              Accept: "application/json",
              "X-Requested-With": "XMLHttpRequest",
            },
          }
        )
        .then((response) => {
          const status = response?.data?.status;
          const onSuccess = this.dialogOptions.onSuccess;

          if (status !== true && status !== "success") {
            if (printWindow && !printWindow.closed) {
              printWindow.close();
            }

            if (response?.data?.errors) {
              this.form.setError(response.data.errors);
            }

            this.form.setError(
              "delivered_items",
              response?.data?.info || "Unable to save the delivered item selection."
            );
            return;
          }

          const responseData = response?.data?.data || null;
          const iarId = responseData?.iar_id || null;
          const printParams = new URLSearchParams({
            option: "print",
            type: "iar",
          });

          if (iarId) {
            printParams.set("iar_id", iarId);
          }

          const printUrl = `/faims/purchase-orders/${this.form.id}?${printParams.toString()}`;

          if (printWindow && !printWindow.closed) {
            printWindow.location.href = printUrl;
          } else if (this.dialogOptions.printAfterSave) {
            window.open(printUrl, "_blank");
          }

          this.$emit("updated", responseData);
          this.hide();

          if (typeof onSuccess === "function") {
            setTimeout(() => {
              onSuccess(responseData);
            }, 150);
          }
        })
        .catch((error) => {
          if (printWindow && !printWindow.closed) {
            printWindow.close();
          }

          if (error.response?.status === 422 && error.response?.data?.errors) {
            Object.entries(error.response.data.errors).forEach(([field, messages]) => {
              this.form.setError(field, Array.isArray(messages) ? messages[0] : messages);
            });
            return;
          }

          console.error(error);
          this.form.setError(
            "delivered_items",
            this.dialogOptions.iarId
              ? "Unable to update the IAR report."
              : "Unable to generate the IAR report."
          );
        })
        .finally(() => {
          this.form.processing = false;
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
