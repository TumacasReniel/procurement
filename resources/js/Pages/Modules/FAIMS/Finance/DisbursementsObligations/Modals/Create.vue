<template>
  <b-modal
    v-model="showModal"
    size="xl"
    title="Create Record"
    header-class="p-3 bg-light"
    class="v-modal-custom"
    modal-class="zoomIn"
    centered
    no-close-on-backdrop
  >
    <form class="customform" @submit.prevent="submit">
      <div v-if="Object.keys(errors).length" class="alert alert-danger">
        {{ Object.values(errors)[0]?.[0] || Object.values(errors)[0] }}
      </div>

      <div class="mb-3">
        <h6 class="mb-1">Reference</h6>
        <p class="text-muted fs-12 mb-0">OS/DV and request numbers</p>
      </div>
      <div class="row">
        <div class="col-md-6 mb-3">
          <label class="form-label">OS Number</label>
          <input v-model="form.os_number" type="text" class="form-control" placeholder="OS-XXXX" />
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label">DV Number</label>
          <input v-model="form.dv_number" type="text" class="form-control" placeholder="DV-XXXX" />
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label">Request Number</label>
          <input v-model="form.request_number" type="text" class="form-control" placeholder="Request No." />
        </div>
      </div>

      <hr class="my-3" />
      <div class="mb-3">
        <h6 class="mb-1">Payee Details</h6>
        <p class="text-muted fs-12 mb-0">Recipient and fund source</p>
      </div>
      <div class="row">
        <div class="col-md-6 mb-3">
          <label class="form-label">Payee</label>
          <input v-model="form.payee" type="text" class="form-control" placeholder="Payee name" />
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label">Fund Source</label>
          <input v-model="form.fund_source" type="text" class="form-control" placeholder="Fund source" />
        </div>
      </div>

      <hr class="my-3" />
      <div class="mb-3">
        <h6 class="mb-1">Amount & Status</h6>
        <p class="text-muted fs-12 mb-0">Amount and current status</p>
      </div>
      <div class="row">
        <div class="col-md-6 mb-3">
          <label class="form-label">Amount</label>
          <input v-model="form.amount" type="number" min="0" step="0.01" class="form-control" placeholder="0.00" />
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label">Status</label>
          <input v-model="form.status" type="text" class="form-control" placeholder="Status" />
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label">Created By</label>
          <input v-model="form.created_by" type="text" class="form-control" placeholder="Created by" />
        </div>
      </div>
    </form>
    <template v-slot:footer>
      <b-button @click="hide" variant="light" block>Close</b-button>
      <b-button @click="submit" variant="success" block>Create</b-button>
    </template>
  </b-modal>
</template>

<script>
export default {
  data() {
    return {
      showModal: false,
      errors: {},
      form: {
        os_number: "",
        dv_number: "",
        request_number: "",
        payee: "",
        fund_source: "",
        amount: "",
        status: "",
        created_by: "",
      },
    };
  },
  methods: {
    show() {
      this.reset();
      this.clearErrors();
      this.showModal = true;
    },
    hide() {
      this.showModal = false;
      this.reset();
      this.clearErrors();
    },
    handleSuccess() {
      this.reset();
      this.clearErrors();
    },
    setErrors(errors) {
      this.errors = errors || {};
    },
    clearErrors() {
      this.errors = {};
    },
    reset() {
      this.form = {
        os_number: "",
        dv_number: "",
        request_number: "",
        payee: "",
        fund_source: "",
        amount: "",
        status: "",
        created_by: "",
      };
    },
    submit() {
      this.clearErrors();
      this.$emit("created", { ...this.form });
    },
  },
};
</script>
