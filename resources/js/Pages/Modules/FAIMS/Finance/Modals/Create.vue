<template>
  <b-modal
    v-model="showModal"
    style="--vz-modal-width: 820px"
    header-class="p-3 bg-light"
    title="Create Request"
    class="v-modal-custom"
    modal-class="zoomIn"
    centered
    no-close-on-backdrop
  >
    <form class="customform" @submit.prevent="submit">
      <div class="row">
        <div class="col-md-6 mb-3">
          <InputLabel for="request_date" value="Request Date" :message="errors.request_date" />
          <div class="input-group">
            <span class="input-group-text"><i class="ri-calendar-line"></i></span>
            <input
              id="request_date"
              v-model="form.request_date"
              type="datetime-local"
              class="form-control"
              :class="{ 'is-invalid': errors.request_date }"
            />
            <span class="input-group-text">
              <i class="ri-close-line"></i>
            </span>
          </div>
          <InputError :message="errors.request_date" />
        </div>
        <div class="col-md-6 mb-3"></div>

        <div class="col-md-6 mb-3">
          <InputLabel for="request_type" value="Request Type" :message="errors.request_type_id" />
          <Multiselect
            :options="dropdowns.request_types"
            v-model="form.request_type_id"
            label="name"
            placeholder="Select Request Type"
            :searchable="true"
          />
          
        </div>
        <div class="col-md-6 mb-3">
          <InputLabel for="division" value="Division" :message="errors.division_id" />
          <Multiselect
            :options="dropdowns.divisions"
            v-model="form.division_id"
            label="name"
            placeholder="Select Division"
            :searchable="true"
          />
        </div>

        <div class="col-md-12 mb-3 fund-panel">
          <div class="row">
            <div class="col-md-6 mb-3">
              <InputLabel for="fund_source" value="Fund Source" :message="errors.fund_source_id" />
              <Multiselect
                :options="dropdowns.fund_clusters"
                v-model="form.fund_source_id"
                label="name"
                placeholder="Select Fund Source"
                :searchable="true"
              />

            </div>

            <div class="col-md-6 mb-3">
              <InputLabel for="project_type" value="Project Type" :message="errors.project_type_id" />
              <div class="d-flex gap-2">
                <div class="flex-grow-1">
                  <Multiselect
                    :options="dropdowns.project_types"
                    v-model="form.project_type_id"
                    label="name"
                    placeholder="Select ..."
                    :searchable="true"
                  />
                </div>
                <b-button variant="info" class="btn-icon">
                  <i class="ri-information-line"></i>
                </b-button>
              </div>
            </div>
            <div class="col-md-12" v-if="form.project_type_id">
              <InputLabel for="project" value="Project" :message="errors.project_id" />
              <div class="d-flex gap-2">
                <div class="flex-grow-1">
                  <Multiselect
                    :options="dropdowns.projects"
                    v-model="form.project_id"
                    label="label"
                    placeholder="Select ..."
                    :searchable="true"
                  />
                </div>
                <b-button variant="info" class="btn-icon">
                  <i class="ri-truck-line"></i>
                </b-button>
              </div>
            </div>
          </div>
        </div>

        <div class="col-md-12 mb-3">
          <InputLabel for="payee" value="Payee / Creditor" :message="errors.creditor_id" />
          <div class="d-flex gap-2">
            <div class="flex-grow-1">
              <Multiselect
                :options="dropdowns.creditors"
                v-model="form.creditor_id"
                label="label"
                placeholder="Select Payee"
                :searchable="true"
              />
            </div>
            <b-button variant="info" class="btn-icon">
              <i class="ri-bank-card-2-line"></i>
            </b-button>
          </div>

        </div>

        <div class="col-md-12 mb-3">
          <InputLabel for="particulars" value="Particulars" :message="errors.particulars" />
          <textarea
            id="particulars"
            v-model="form.particulars"
            class="form-control"
            rows="3"
            placeholder="Enter particulars"
            :class="{ 'is-invalid': errors.particulars }"
          ></textarea>
        </div>

        <div class="col-md-6 mb-3">
          <InputLabel for="amount" value="Amount" :message="errors.amount" />
          <Amount
            id="amount"
            class="form-control"
            placeholder="0.00"
            @amount="form.amount = $event"
            ref="amount"
          />
        </div>
      </div>
    </form>
    <template v-slot:footer>
      <b-button @click="hide()" variant="light" block>Close</b-button>
      <b-button @click="submit()" variant="success" block>
        Create
      </b-button>
    </template>
  </b-modal>
</template>

<script>
import InputLabel from "@/Shared/Components/Forms/InputLabel.vue";
import TextInput from "@/Shared/Components/Forms/TextInput.vue";
import Amount from "@/Shared/Components/Forms/Amount.vue";
import Multiselect from "@vueform/multiselect";
import InputError from "@/Shared/Components/Forms/InputError.vue";

export default {
  components: { InputLabel, TextInput, Multiselect, Amount, InputError },
  props: ["dropdowns"],
  data() {
    return {
      showModal: false,
      errors: {},
      form: {
        request_date: "",
        request_type_id: null,
        division_id: null,
        fund_source_id: null,
        project_type_id: null,
        project_id: null,
        creditor_id: null,
        particulars: "",
        amount: "",
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
    reset() {
      const now = this.getNowLocalDateTime();
      this.form = {
        request_date: now,
        request_type_id: null,
        division_id: null,
        fund_source_id: null,
        project_type_id: null,
        project_id: null,
        creditor_id: null,
        particulars: "",
        amount: 0.0,
      };
      this.$nextTick(() => {
        if (this.$refs.amount && this.$refs.amount.emitValue) {
          this.$refs.amount.emitValue(0.0);
        }
      });
    },
    clearErrors() {
      this.errors = {};
    },
    setErrors(errors) {
      const normalized = {};
      Object.keys(errors || {}).forEach((key) => {
        const value = errors[key];
        normalized[key] = Array.isArray(value) ? value[0] : value;
      });
      this.errors = normalized;
    },
    getNowLocalDateTime() {
      const now = new Date();
      const year = now.getFullYear();
      const month = String(now.getMonth() + 1).padStart(2, "0");
      const day = String(now.getDate()).padStart(2, "0");
      const hours = String(now.getHours()).padStart(2, "0");
      const minutes = String(now.getMinutes()).padStart(2, "0");
      return `${year}-${month}-${day}T${hours}:${minutes}`;
    },

    submit() {
      this.clearErrors();
      let amountStr = String(this.form.amount ?? "");
      amountStr = amountStr.replace(/[^0-9.-]/g, "");
      let parts = amountStr.split('.');
      if (parts.length > 1 && parts[1].length > 2) {
        parts[1] = parts[1].substring(0, 2);
        amountStr = parts.join('.');
      }
      this.form.amount = parseFloat(amountStr) || 0;
      this.$emit("created", { ...this.form });
    },
  },
};
</script>

<style scoped>
.fund-panel {
  background: #c8f3f5;
  border-radius: 6px;
  padding: 16px 12px;
}
</style>
