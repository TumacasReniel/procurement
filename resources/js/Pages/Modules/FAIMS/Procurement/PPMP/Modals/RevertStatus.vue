<template>
  <b-modal
    v-model="modalShow"
    title="Revert Plan Status"
    centered
    no-close-on-backdrop
  >
    <div v-if="plan">
      <div class="alert alert-warning">
        Revert <strong>{{ plan.ppmp_no || plan.code || planShortName }}</strong>
        from <strong>{{ currentStatus }}</strong> to
        <strong>{{ previousStatus }}</strong>?
      </div>

      <InputLabel value="Reason for reverting" />
      <textarea
        v-model.trim="reason"
        rows="4"
        class="form-control"
        maxlength="1000"
        placeholder="Explain why this plan needs to return to the previous workflow step."
      ></textarea>
      <div v-if="error" class="text-danger small mt-2">{{ error }}</div>
    </div>

    <template #footer>
      <b-button variant="light" :disabled="processing" @click="$emit('cancel')">
        Cancel
      </b-button>
      <b-button
        variant="warning"
        :disabled="processing || reason.length < 5"
        @click="$emit('confirm', reason)"
      >
        <i class="ri-arrow-go-back-line align-bottom me-1"></i>
        {{ processing ? "Reverting..." : "Revert Status" }}
      </b-button>
    </template>
  </b-modal>
</template>

<script>
import InputLabel from "@/Shared/Components/Forms/InputLabel.vue";

export default {
  components: { InputLabel },
  props: {
    show: { type: Boolean, default: false },
    plan: { type: Object, default: null },
    planType: { type: String, default: "PPMP" },
    processing: { type: Boolean, default: false },
    error: { type: String, default: "" },
  },
  emits: ["update:show", "cancel", "confirm"],
  data() {
    return { reason: "" };
  },
  computed: {
    modalShow: {
      get() {
        return this.show;
      },
      set(value) {
        this.$emit("update:show", value);
      },
    },
    planShortName() {
      return ["annual", "APP"].includes(this.planType)
        ? "APP"
        : ["supplemental", "SPP"].includes(this.planType)
        ? "SPP"
        : "PPMP";
    },
    currentStatus() {
      return this.plan?.ppmp_status || this.plan?.approval_status || "Current status";
    },
    previousStatus() {
      const status = String(this.currentStatus).toLowerCase();
      if (status.includes("implementation") || status.includes("consolidation")) {
        return "Reviewed/For Submission";
      }
      if (status.includes("reviewed")) {
        return "For Review";
      }
      return "Pending";
    },
  },
  watch: {
    show(value) {
      if (value) {
        this.reason = "";
      }
    },
  },
};
</script>
