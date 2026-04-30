<template>
  <b-modal
    v-model="showModal"
    :title="modalTitle"
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
            Submit a budget request for this PAP code. It will remain
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
          <InputLabel value="Request Type" :message="form.errors.request_type" />
          <div class="d-flex flex-column flex-md-row gap-2">
            <button
              type="button"
              class="btn budget-request-type flex-fill text-start"
              :class="form.request_type === 'additional_budget' ? 'btn-primary' : 'btn-outline-primary'"
              @click="setRequestType('additional_budget')"
            >
              <span class="d-flex align-items-center gap-2">
                <i class="ri-add-circle-line"></i>
                <span>
                  <span class="d-block fw-semibold">Additional Budget</span>
                  <span class="d-block fs-12 opacity-75">Add new funds to this PAP code.</span>
                </span>
              </span>
            </button>
            <button
              type="button"
              class="btn budget-request-type flex-fill text-start"
              :class="form.request_type === 'realignment' ? 'btn-primary' : 'btn-outline-primary'"
              @click="setRequestType('realignment')"
            >
              <span class="d-flex align-items-center gap-2">
                <i class="ri-arrow-left-right-line"></i>
                <span>
                  <span class="d-block fw-semibold">Realignment</span>
                  <span class="d-block fs-12 opacity-75">Move budget from another PAP code.</span>
                </span>
              </span>
            </button>
          </div>
          <InputError :message="form.errors.request_type" />
        </b-col>

        <b-col v-if="form.request_type === 'realignment'" lg="12" class="mt-3">
          <InputLabel value="Source PAP Code" :message="form.errors.source_procurement_code_id" />
        
          <select
            v-model="form.source_procurement_code_id"
            class="form-select"
            :class="{ 'is-invalid': form.errors.source_procurement_code_id }"
          >
            <option :value="null">Select source PAP code</option>
            <option
              v-for="sourceCode in sourceCodes"
              :key="sourceCode.value"
              :value="sourceCode.value"
            >
              {{ sourceCode.code }} - {{ sourceCode.title }} | Remaining:
              {{ formatCurrency(sourceCode.remaining_budget) }}
            </option>
          </select>
          <div v-if="selectedSourceCode" class="rounded border bg-light-subtle p-2 mt-2">
            <div class="d-flex justify-content-between gap-3 fs-12">
              <span class="text-muted">Source remaining</span>
              <span class="fw-semibold">{{ formatCurrency(selectedSourceCode.remaining_budget) }}</span>
            </div>
            <div class="d-flex justify-content-between gap-3 fs-12 mt-1">
              <span class="text-muted">After realignment</span>
              <span
                class="fw-semibold"
                :class="sourceBalanceAfter < 0 ? 'text-danger' : 'text-success'"
              >
                {{ formatCurrency(sourceBalanceAfter) }}
              </span>
            </div>
          </div>
          <InputError :message="form.errors.source_procurement_code_id" />
        </b-col>

        <b-col lg="12" class="mt-2">
          <InputLabel :value="amountLabel" />
          <Amount
            ref="amountComponent"
            @amount="setAmount"
            :class="{ 'is-invalid': form.errors.amount || realignmentAmountError }"
          />
          <InputError :message="form.errors.amount || realignmentAmountError" />
        </b-col>

        <b-col lg="12" class="mt-3">
          <InputLabel value="Justification" />
          <textarea
            v-model="form.description"
            rows="5"
            class="form-control"
            :class="{ 'is-invalid': form.errors.description }"
            :placeholder="descriptionPlaceholder"
          ></textarea>
          <InputError :message="form.errors.description" />
        </b-col>

        <b-col lg="12" class="mt-3">
          <InputLabel value="Supporting Document" />
          <input
            :key="fileInputKey"
            type="file"
            class="form-control"
            :class="{ 'is-invalid': form.errors.attachment }"
            accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
            @change="handleAttachmentChange"
          />
          <div class="text-muted fs-12 mt-1">
            Upload the supporting basis document. Accepted: PDF, Word, JPG, PNG up to 5 MB.
          </div>
          <div v-if="form.attachment" class="fs-12 mt-1">
            Selected: <span class="fw-semibold">{{ form.attachment.name }}</span>
          </div>
          <InputError :message="form.errors.attachment" />
        </b-col>
      </b-row>
    </form>

    <template #footer>
      <b-button variant="light" @click="hide" :disabled="submitting">Close</b-button>
      <b-button variant="primary" @click="submit" :disabled="submitting || !selected || Boolean(realignmentAmountError)">
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
      fileInputKey: 0,
      sourceCodes: [],
      sourceKeyword: "",
      form: useForm({
        request_type: "additional_budget",
        source_procurement_code_id: null,
        amount: null,
        description: "",
        attachment: null,
      }),
    };
  },
  computed: {
    modalTitle() {
      return this.form.request_type === "realignment"
        ? "Request Budget Realignment"
        : "Request Additional Budget";
    },
    amountLabel() {
      return this.form.request_type === "realignment"
        ? "Realignment Amount"
        : "Additional Amount";
    },
    descriptionPlaceholder() {
      return this.form.request_type === "realignment"
        ? "Why should budget be realigned from the selected PAP code?"
        : "Why is the additional budget needed?";
    },
    selectedSourceCode() {
      return this.sourceCodes.find((code) => {
        return Number(code.value) === Number(this.form.source_procurement_code_id);
      }) || null;
    },
    sourceBalanceAfter() {
      if (!this.selectedSourceCode) {
        return 0;
      }

      return Number(this.selectedSourceCode.remaining_budget || 0) - Number(this.form.amount || 0);
    },
    realignmentAmountError() {
      if (
        this.form.request_type !== "realignment" ||
        !this.selectedSourceCode ||
        !Number(this.form.amount || 0)
      ) {
        return null;
      }

      if (Number(this.form.amount || 0) > Number(this.selectedSourceCode.remaining_budget || 0)) {
        return "The realignment amount must not be greater than the source PAP code remaining balance.";
      }

      return null;
    },
  },
  watch: {
    "form.request_type"(value) {
      if (value !== "realignment") {
        this.form.source_procurement_code_id = null;
        return;
      }

      this.fetchSourceCodes();
    },
  },
  methods: {
    show(data) {
      this.selected = data;
      this.showModal = true;
      this.resetFormState();
      this.fetchSourceCodes();
    },
    hide() {
      this.showModal = false;
      this.selected = null;
      this.resetFormState();
    },
    resetFormState() {
      this.submitting = false;
      this.errorMessage = null;
      this.form.reset();
      this.form.clearErrors();
      this.form.request_type = "additional_budget";
      this.form.source_procurement_code_id = null;
      this.form.attachment = null;
      this.sourceCodes = [];
      this.sourceKeyword = "";
      this.fileInputKey += 1;

      this.$nextTick(() => {
        this.$refs.amountComponent?.emitValue(0);
      });
    },
    setAmount(value) {
      this.form.amount = this.cleanCurrency(value);
    },
    setRequestType(value) {
      this.form.request_type = value;
    },
    fetchSourceCodes() {
      if (!this.selected) {
        return;
      }

      axios
        .get("/faims/procurement-codes", {
          params: {
            option: "source_budget_codes",
            except_id: this.selected.id,
            keyword: this.sourceKeyword,
          },
        })
        .then((response) => {
          this.sourceCodes = Array.isArray(response.data) ? response.data : [];
        })
        .catch((err) => console.log(err));
    },
    cleanCurrency(value) {
      if (!value) {
        return null;
      }

      const cleaned = value.toString().replace(/[^0-9.]/g, "");
      return cleaned ? Number.parseFloat(cleaned) : null;
    },
    handleAttachmentChange(event) {
      const [file] = event?.target?.files || [];
      this.form.attachment = file || null;
    },
    submit() {
      if (!this.selected || this.submitting) {
        return;
      }

      if (this.realignmentAmountError) {
        this.form.setError("amount", this.realignmentAmountError);
        return;
      }

      this.submitting = true;
      this.errorMessage = null;
      this.form.clearErrors();

      this.form.post(`/faims/procurement-codes/${this.selected.id}/budget-increase-requests`, {
        preserveScroll: true,
        preserveState: true,
        forceFormData: true,
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

<style scoped>
.budget-request-type {
  min-height: 72px;
  border-radius: 8px;
}
</style>
