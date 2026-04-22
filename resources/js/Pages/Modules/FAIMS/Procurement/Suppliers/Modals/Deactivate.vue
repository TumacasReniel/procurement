<template>
  <b-modal
    v-model="showStatusModal"
    style="--vz-modal-width: 600px"
    :title="supplier && supplier.is_active == 1 ? 'Deactivate Supplier' : 'Activate Supplier'"
    header-class="p-3 bg-light"
    class="v-modal-custom"
    modal-class="zoomIn"
    centered
    no-close-on-backdrop
  >
    <form class="customform">
      <div class="m-5 text-center">
        <div>
          Are you sure you want to
          <span :class="supplier && supplier.is_active == 1 ? 'text-danger' : 'text-success'">
            {{ supplier && supplier.is_active == 1 ? 'deactivate' : 'activate' }}
          </span>
          supplier
          <span class="text-primary">"{{ supplier ? supplier.name : '' }}"</span>?
        </div>

        <div class="text-muted small mt-3">
          {{ supplier && supplier.is_active == 1
            ? 'This supplier will no longer be available for selection in procurement processes.'
            : 'This supplier will be available for selection in procurement processes.' }}
        </div>
      </div>
    </form>

    <template v-slot:footer>
      <b-button
        @click="cancelStatusChange"
        variant="light"
        :disabled="statusChanging"
        block
      >
        Close
      </b-button>
      <b-button
        @click="confirmStatusChange"
        variant="primary"
        :disabled="statusChanging"
        block
      >
        {{ statusChanging ? 'Processing...' : (supplier && supplier.is_active == 1 ? 'Deactivate' : 'Activate') }}
      </b-button>
    </template>
  </b-modal>

  <b-modal
    v-model="showResultModal"
    style="--vz-modal-width: 600px"
    :title="statusResult.title"
    header-class="p-3 bg-light"
    class="v-modal-custom"
    modal-class="zoomIn"
    centered
    no-close-on-backdrop
  >
    <form class="customform">
      <div class="m-5 text-center">
        <div>{{ statusResult.message }}</div>
      </div>
    </form>

    <template v-slot:footer>
      <b-button
        @click="closeResultModal"
        variant="light"
        block
      >
        Close
      </b-button>
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
        .then((response) => {
          const updatedSupplier = response?.data?.data || {
            ...this.supplier,
            is_active: newStatus,
          };

          this.showStatusModal = false;
          this.statusResult = {
            title: "Success",
            message: `Supplier ${newStatus ? "activated" : "deactivated"} successfully`,
            variant: "success"
          };
          this.showResultModal = true;
          this.statusChanging = false;
          this.$emit('status-changed', updatedSupplier);
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
