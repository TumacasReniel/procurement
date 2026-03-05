<template>
  <!-- Status Change Confirmation Modal -->
  <b-modal
    v-model="showStatusModal"
    title="Confirm Status Change"
    header-class="bg-gradient-primary text-white"
    modal-class="zoomIn"
    centered
    no-close-on-backdrop
  >
    <div class="text-center">
      <div class="mb-3">
        <i
          :class="supplier && supplier.is_active == 1 ? 'ri-pause-circle-line text-warning' : 'ri-play-circle-line text-success'"
          style="font-size: 4rem"
        ></i>
      </div>
      <h5 class="mb-3">
        {{ supplier && supplier.is_active == 1 ? 'Deactivate' : 'Activate' }} Supplier
      </h5>
      <p class="text-muted mb-0">
        Are you sure you want to
        <strong>{{ supplier && supplier.is_active == 1 ? 'deactivate' : 'activate' }}</strong>
        the supplier "<strong>{{ supplier ? supplier.name : '' }}</strong>"?
      </p>
      <p class="text-muted small mt-2">
        {{ supplier && supplier.is_active == 1
          ? 'This supplier will no longer be available for selection in procurement processes.'
          : 'This supplier will be available for selection in procurement processes.' }}
      </p>
    </div>

    <template v-slot:footer>
      <div class="d-flex justify-content-end gap-2">
        <b-button
          @click="cancelStatusChange"
          variant="light"
          style="border-radius: 8px"
        >
          <i class="ri-close-line me-1"></i>Cancel
        </b-button>
        <b-button
          @click="confirmStatusChange"
          :variant="supplier && supplier.is_active == 1 ? 'warning' : 'success'"
          :disabled="statusChanging"
          style="border-radius: 8px"
        >
          <i
            :class="supplier && supplier.is_active == 1 ? 'ri-pause-circle-line' : 'ri-play-circle-line'"
            class="me-1"
          ></i>
          {{ statusChanging ? 'Processing...' : (supplier && supplier.is_active == 1 ? 'Deactivate' : 'Activate') }}
        </b-button>
      </div>
    </template>
  </b-modal>

  <!-- Status Change Result Modal -->
  <b-modal
    v-model="showResultModal"
    :title="statusResult.title"
    :header-class="statusResult.variant === 'success' ? 'bg-success text-white' : 'bg-danger text-white'"
    modal-class="zoomIn"
    centered
    no-close-on-backdrop
  >
    <div class="text-center">
      <div class="mb-3">
        <i
          :class="statusResult.variant === 'success' ? 'ri-checkbox-circle-fill text-success' : 'ri-close-circle-fill text-danger'"
          style="font-size: 4rem"
        ></i>
      </div>
      <h5 class="mb-3">{{ statusResult.title }}</h5>
      <p class="text-muted mb-0">{{ statusResult.message }}</p>
    </div>

    <template v-slot:footer>
      <div class="d-flex justify-content-center">
        <b-button
          @click="closeResultModal"
          variant="light"
          style="border-radius: 8px"
        >
          <i class="ri-close-line me-1"></i>Close
        </b-button>
      </div>
    </template>
  </b-modal>
</template>

<script>
export default {
  name: 'DeactivateModal',
  props: {
    supplier: {
      type: Object,
      default: null
    }
  },
  data() {
    return {
      showStatusModal: false,
      showResultModal: false,
      statusChanging: false,
      statusResult: {
        title: '',
        message: '',
        variant: 'success'
      },
    };
  },
  watch: {
    supplier: {
      handler(newSupplier) {
        if (newSupplier) {
          this.showStatusModal = true;
        }
      },
      immediate: true
    }
  },
  methods: {
    cancelStatusChange() {
      this.showStatusModal = false;
      this.$emit('cancel');
    },

    confirmStatusChange() {
      if (!this.supplier) return;

      this.statusChanging = true;
      const newStatus = this.supplier.is_active == 1 ? 0 : 1;

      axios
        .patch(`/faims/suppliers/${this.supplier.id}/status`, {
          is_active: newStatus,
        })
        .then(() => {
          this.supplier.is_active = newStatus;
          this.showStatusModal = false;
          this.statusResult = {
            title: "Success",
            message: `Supplier ${newStatus ? "activated" : "deactivated"} successfully`,
            variant: "success"
          };
          this.showResultModal = true;
          this.statusChanging = false;
          this.$emit('status-changed', this.supplier);
        })
        .catch((err) => {
          console.error(err);
          this.showStatusModal = false;
          this.statusResult = {
            title: "Error",
            message: "Failed to update supplier status. Please try again.",
            variant: "danger"
          };
          this.showResultModal = true;
          this.statusChanging = false;
        });
    },

    closeResultModal() {
      this.showResultModal = false;
      this.statusResult = {
        title: '',
        message: '',
        variant: 'success'
      };
      this.$emit('close');
    },
  },
};
</script>

<style scoped>
.bg-gradient-primary {
  background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
}
</style>
