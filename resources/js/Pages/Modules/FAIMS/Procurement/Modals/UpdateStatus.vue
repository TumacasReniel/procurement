<template>
  <b-modal
    v-model="showModal"
    style="--vz-modal-width: 600px"
    header-class="p-3 bg-light"
    title="Update Status"
    class="v-modal-custom"
    modal-class="zoomIn"
    centered
    no-close-on-backdrop
  >
    <form class="customform" @submit.prevent="submitOnEnter">
      <div class="m-5 text-center">
        <b>
          <!-- BACResolution -->
          <span v-if="type == 'BACResolution'"> BAC Resolution No.: </span>

          <!-- Notice of Award -->
          <span v-if="type == 'NOA' || type == 'NOA Not Conformed'">
            Notice Of Award No.:
          </span>

          <!-- Purchase Order -->
          <span v-if="type == 'PO' || type == 'PO Not Conformed'">
                 Purchase Order No.: 
          </span>

          <span class="text-danger flex">{{ form.code }}</span></b
        >
        <br />

        <!-- // Bac Resolution Status Updates to 'Approved' -->
        
        <span
          v-if="
            actionType !== 'revert' &&
            form.status?.name === 'Pending'  &&
            (type == 'BACResolution'   )
          "
        >
          Update status from
          <span :class="statusTextClass">"{{ form.status?.name }}"</span>
          to
          <span class="text-primary">"Approved"</span>
          ?
        </span>

        <!-- // Notice of Award -->

        <span
          v-if="
            actionType === 'revert' &&
            (type == 'NOA' || type == 'PO')
          "
        >
          Revert status from
          <span :class="statusTextClass">"{{ form.status?.name }}"</span>
          to
          <span class="text-primary">"{{ revertTargetStatus }}"</span>
          ?
        </span>

        <span
          v-if="
          actionType !== 'revert' &&
          ((form.status?.name === 'PO Issued')|| ((form.status?.name === 'Pending' || form.status?.name === 'Created')  && (type == 'NOA' || type == 'PO')) &&
            type != 'NOA Not Conformed' &&
            type != 'PO Not Conformed')
          "
        >
          Update status from
          <span :class="statusTextClass">"{{ form.status?.name }}"</span>
          to
          <span class="text-primary" v-if="type == 'NOA'">"Served to Supplier"</span>
          <span class="text-primary" v-if="type == 'PO'">"Served to Supplier"</span>
          
          ?
        </span> 

        <span
          v-if="
            actionType !== 'revert' &&
            (form.status?.name === 'Served to Supplier' || form.status?.name === 'Issued') &&
            type != 'NOA Not Conformed' &&
            type != 'PO Not Conformed'
          "
        >
          Update status from
          <span :class="statusTextClass">"{{ form.status?.name }}"</span>
          to
          <span class="text-primary">"Conformed"</span>
          ?
        </span>

        <span v-if="actionType !== 'revert' && form.status?.name === 'Conformed'">
          Update status from
          <span :class="statusTextClass">"{{ form.status?.name }}"</span>
          to
          <span class="text-primary">"Delivered/For Inspection"</span>
          ?
        </span>
        <br />

        <div v-if="actionType !== 'revert' && (type == 'NOA Not Conformed' || type == 'PO Not Conformed')">
          <b-form-textarea
            id="textarea"
            v-model="form.comment"
            placeholder="Reason for not conforming..."
            rows="3"
            max-rows="6"
          ></b-form-textarea>
          <br />
          <span>
            Update status from
            <span :class="statusTextClass">"{{ form.status?.name }}"</span>
            to
            <span class="text-primary">"Not Conformed"</span>
            ?
          </span>
        </div>

        <br />

        <div class="mt-4 p-3 border rounded bg-light">
          <p class="mb-2 fw-bold text-danger ">
            tYPE <span class="text-primary">"CONFIRM"</span> TO PROCEED:
          </p>
          <b-form-input
            v-model="confirmText"
            placeholder="Type confirm here..."
            :class="{ 'is-invalid': confirmTextError }"
            class="text-center fw-bold"
            @input="handleConfirmInput"
            @keyup.enter.exact.prevent="submitOnEnter"
          ></b-form-input>
          <small v-if="confirmTextError" class="text-danger">
            Please type "confirm" to proceed
          </small>
        </div>
      </div>
    </form>
    <template v-slot:footer>
      <b-button type="button" @click="hide()" variant="light" block>Close</b-button>
      <b-button type="button" @click="submit()" variant="primary" :disabled="form.processing || !isConfirmed" block
        >Update</b-button
      >
    </template>
  </b-modal>
</template>
<script>
import { useForm } from "@inertiajs/vue3";
import InputLabel from "@/Shared/Components/Forms/InputLabel.vue";
import TextInput from "@/Shared/Components/Forms/TextInput.vue";

export default {
  props: ["procurement"],
  components: { InputLabel, TextInput },
  data() {
    return {
      currentUrl: window.location.origin,
      selected: null,
      confirmText: "",
      confirmTextError: false,
      form: useForm({
        id: null,
        code: null,
        status: null,
        items: this.procurement.items,
        quotations: this.procurement.quotations.filter((q) =>
          q.items.some(
            (item) =>
              item.status.name ="Awarded" && item.bid_price != null && item.is_rebid == 0
          )
        ),
        bac_reso_type: null,
        comment: null,
        option: "update_status",
      }),
      actionType: "update",
      type: null,
      showModal: false,
    };
  },
  methods: {
    show(data, type, actionType = "update") {
      this.form.id = data.id;
      this.form.code = data.code;
      this.form.status = data.status;
      this.actionType = actionType;
      this.form.option = actionType === "revert" ? "revert_status" : "update_status";
      this.type = type;
      if (this.type === "BACResolution") {
        this.form.bac_reso_type = data.type;
      }
      this.showModal = true;
    },
    submit() {
      if (this.actionType === "revert") {
        if (this.type == "NOA") {
          this.form.put("/faims/notice-of-awards/" + this.form.id, {
            preserveScroll: true,
            onSuccess: () => {
              this.$emit("add", true);
              this.hide();
            },
          });
        } else if (this.type == "PO") {
          this.form.put("/faims/purchase-orders/" + this.form.id, {
            preserveScroll: true,
            onSuccess: () => {
              this.$emit("add", true);
              this.hide();
            },
          });
        }
        return;
      }

      if (this.type == "BACResolution") {
        this.form.put("/faims/bac-resolutions/" + this.form.id, {
          preserveScroll: true,
          onSuccess: (response) => {
            this.$emit("add", true);
            this.hide();
          },
        });
      } else if (this.type == "NOA" || this.type == "NOA Not Conformed") {
        if (this.type == "NOA Not Conformed") {
          this.form.option = "not_conformed";
        }
        this.form.put("/faims/notice-of-awards/" + this.form.id, {
          preserveScroll: true,
          onSuccess: (response) => {
            this.$emit("add", true);
            this.hide();
          },
        });
      } else if (this.type == "PO" || this.type == "PO Not Conformed") {
        if (this.type == "PO Not Conformed") {
          this.form.option = "not_conformed";
        }
        this.form.put("/faims/purchase-orders/" + this.form.id, {
          preserveScroll: true,
          onSuccess: (response) => {
            this.$emit("add", true);
            this.hide();
          },
        });
      }
    },
    handleInput(field) {
      this.form.errors[field] = false;
    },
    handleConfirmInput() {
      this.confirmTextError = this.confirmText.toUpperCase() !== "CONFIRM";
    },
    submitOnEnter() {
      if (this.isConfirmed && !this.form.processing) {
        this.submit();
      }
    },
    hide() {
      this.showModal = false;
      this.confirmText = "";
      this.confirmTextError = false;
      this.form.option = "update_status";
      this.actionType = "update";
    },
  },
  computed: {
    isConfirmed() {
      return this.confirmText.toUpperCase() === "CONFIRM";
    },
    statusTextClass() {
      const raw = this.form.status?.color || "";
      return String(raw)
        .split(" ")
        .filter((cls) => cls && cls !== "text-white")
        .join(" ");
    },
    revertTargetStatus() {
      const current = this.form.status?.name || "";
      if (this.type === "NOA") {
        if (current === "Delivered/For Inspection") return "Conformed";
        if (current === "Conformed") return "Served to Supplier";
        if (current === "Served to Supplier") return "Pending";
      }
      if (this.type === "PO") {
        if (current === "Delivered/For Inspection") return "Conformed";
        if (current === "Conformed") return "Issued";
        if (current === "Issued") return "Created";
      }
      return "Previous Status";
    },
  },
};
</script>
