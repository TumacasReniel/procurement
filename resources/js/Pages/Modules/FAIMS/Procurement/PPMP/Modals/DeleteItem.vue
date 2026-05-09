<template>
  <b-modal
    v-model="modal.show"
    style="--vz-modal-width: 520px"
    header-class="p-3 bg-light"
    title="Delete PPMP Item"
    class="v-modal-custom"
    modal-class="zoomIn"
    centered
    no-close-on-backdrop
  >
    <div v-if="modal.item" class="delete-ppmp-item-modal text-center">
      <div class="delete-ppmp-item-modal__icon">
        <i class="ri-delete-bin-line"></i>
      </div>
      <h6 class="delete-ppmp-item-modal__title">Remove this item from the PPMP?</h6>
      <p class="delete-ppmp-item-modal__copy">
        This will delete
        <strong>{{ modal.item.name || "this item" }}</strong>
        from {{ ppmp?.ppmp_no || "this PPMP" }}.
      </p>
      <div class="delete-ppmp-item-modal__details">
        <div>
          <span>Quantity</span>
          <strong>{{ formatQuantity(modal.item.quantity) }} {{ modal.item.unit || "" }}</strong>
        </div>
        <div>
          <span>ABC</span>
          <strong>{{ formatCurrency(modal.item.abc) }}</strong>
        </div>
      </div>
    </div>

    <template v-slot:footer>
      <b-button @click="hide" variant="light" block :disabled="form.processing">
        Cancel
      </b-button>
      <b-button @click="submit" variant="danger" :disabled="form.processing" block>
        <i class="ri-delete-bin-line align-bottom me-1"></i>
        Delete Item
      </b-button>
    </template>
  </b-modal>
</template>

<script>
import { useForm } from "@inertiajs/vue3";

export default {
  props: {
    ppmp: {
      type: Object,
      default: null,
    },
  },
  data() {
    return {
      modal: {
        show: false,
        item: null,
      },
      form: useForm({
        option: "delete_item",
        item_id: null,
      }),
    };
  },
  methods: {
    show(item) {
      this.form.clearErrors();
      this.form.item_id = item?.id || null;
      this.modal.item = item || null;
      this.modal.show = true;
    },
    hide() {
      if (this.form.processing) {
        return;
      }

      this.modal.show = false;
      this.modal.item = null;
      this.form.item_id = null;
    },
    submit() {
      if (!this.ppmp?.id || !this.modal.item) {
        return;
      }

      this.form.option = "delete_item";
      this.form.item_id = this.modal.item.id;
      this.form.patch(`/faims/procurement-ppmp/${this.ppmp.id}`, {
        preserveScroll: true,
        onSuccess: () => {
          this.hide();
        },
      });
    },
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
  },
};
</script>

<style scoped>
.delete-ppmp-item-modal {
  padding: 24px 12px 12px;
}

.delete-ppmp-item-modal__icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 56px;
  height: 56px;
  margin-bottom: 16px;
  border-radius: 50%;
  background: #fee2e2;
  color: #dc2626;
  font-size: 28px;
}

.delete-ppmp-item-modal__title {
  margin-bottom: 8px;
  color: #111827;
  font-size: 16px;
  font-weight: 800;
}

.delete-ppmp-item-modal__copy {
  max-width: 380px;
  margin: 0 auto;
  color: #64748b;
  font-size: 13px;
}

.delete-ppmp-item-modal__details {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 10px;
  margin-top: 20px;
}

.delete-ppmp-item-modal__details > div {
  display: flex;
  flex-direction: column;
  gap: 4px;
  min-height: 64px;
  justify-content: center;
  border: 1px solid #e9ebec;
  border-radius: 8px;
  background: #f8fafc;
}

.delete-ppmp-item-modal__details span {
  color: #878a99;
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
}

.delete-ppmp-item-modal__details strong {
  color: #212529;
  font-size: 13px;
  font-weight: 800;
}

@media (max-width: 576px) {
  .delete-ppmp-item-modal__details {
    grid-template-columns: 1fr;
  }
}
</style>
