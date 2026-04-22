<template>
  <b-modal
    v-model="showModal"
    title="Request Additional Budget"
    size="lg"
    centered
    no-close-on-backdrop
  >
    <div v-if="selected" class="rounded-4 border bg-light-subtle p-3 mb-4">
      <div class="d-flex flex-wrap justify-content-between gap-3">
        <div>
          <div class="badge bg-success-subtle text-success mb-2">{{ selected.code }}</div>
          <h5 class="mb-1">{{ selected.title }}</h5>
          <p class="text-muted fs-12 mb-0">
            Submit an additional budget request for this PAP code. It will remain
            pending until a Budget Officer reviews it.
          </p>
        </div>

        <div class="text-md-end">
          <div class="text-muted fs-12">Current Allocated</div>
          <div class="fw-semibold fs-5">{{ formatCurrency(selected.allocated_budget) }}</div>
          <div class="text-muted fs-12 mt-2">Current Remaining</div>
          <div
            class="fw-semibold"
            :class="Number(selected.remaining_budget) < 0 ? 'text-danger' : 'text-success'"
          >
            {{ formatCurrency(selected.remaining_budget) }}
          </div>
        </div>
      </div>
    </div>

    <div v-if="errorMessage" class="alert alert-danger border-0 rounded-4">
      {{ errorMessage }}
    </div>

    <form class="customform" @submit.prevent="submit">
      <b-row>
        <b-col lg="12" class="mt-2">
          <InputLabel value="Additional Amount" />
          <Amount
            ref="amountComponent"
            @amount="setAmount"
            :class="{ 'is-invalid': form.errors.amount }"
          />
          <InputError :message="form.errors.amount" />
        </b-col>

        <b-col lg="12" class="mt-3">
          <InputLabel value="Justification" />
          <textarea
            v-model="form.description"
            rows="5"
            class="form-control"
            :class="{ 'is-invalid': form.errors.description }"
            placeholder="Why is the additional budget needed?"
          ></textarea>
          <InputError :message="form.errors.description" />
        </b-col>
      </b-row>
    </form>

    <template #footer>
      <b-button variant="light" @click="hide" :disabled="submitting">Close</b-button>
      <b-button variant="primary" @click="submit" :disabled="submitting || !selected">
        <span v-if="submitting" class="spinner-border spinner-border-sm me-2"></span>
        Submit Request
      </b-button>
    </template>
  </b-modal>
</template>

<script>
import { useForm } from "@inertiajs/vue3";
import InputError from "@/Shared/Components/Forms/InputError.vue";
import InputLabel from "@/Shared/Components/Forms/InputLabel.vue";
import Amount from "@/Shared/Components/Forms/Amount.vue";

export default {
  components: {
    Amount,
    InputError,
    InputLabel,
  },
  data() {
    return {
      showModal: false,
      submitting: false,
      selected: null,
      errorMessage: null,
      form: useForm({
        amount: null,
        description: "",
      }),
    };
  },
  methods: {
    show(data) {
      this.selected = data;
      this.showModal = true;
      this.submitting = false;
      this.errorMessage = null;
      this.form.reset();
      this.form.clearErrors();

      this.$nextTick(() => {
        this.$refs.amountComponent?.emitValue(0);
      });
    },
    hide() {
      this.showModal = false;
      this.submitting = false;
      this.errorMessage = null;
      this.selected = null;
      this.form.reset();
      this.form.clearErrors();

      this.$nextTick(() => {
        this.$refs.amountComponent?.emitValue(0);
      });
    },
    setAmount(value) {
      this.form.amount = this.cleanCurrency(value);
    },
    cleanCurrency(value) {
      if (!value) {
        return null;
      }

      const cleaned = value.toString().replace(/[^0-9.]/g, "");
      return cleaned ? Number.parseFloat(cleaned) : null;
    },
    submit() {
      if (!this.selected || this.submitting) {
        return;
      }

      this.submitting = true;
      this.errorMessage = null;
      this.form.clearErrors();

      this.form.post(`/faims/procurement-codes/${this.selected.id}/budget-increase-requests`, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
          const flash = this.$page.props.flash || {};

          if (flash.status === false) {
            this.errorMessage = flash.info || flash.message || "Failed to submit the budget increase request.";
            return;
          }

          this.$emit("submitted", {
            ...(flash.data || {}),
            message: flash.message || "Budget increase request submitted successfully.",
            info: flash.info || null,
            type: flash.status === false ? "error" : "success",
          });
          this.hide();
        },
        onError: () => {
          this.errorMessage = null;
        },
        onFinish: () => {
          this.submitting = false;
        },
      });
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
