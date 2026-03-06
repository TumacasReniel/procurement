<template>
  <b-modal
    v-model="showModal"
    header-class="p-3"
    title="Edit Bid Offer"
    size="xl"
    class="v-modal-custom"
    modal-class="zoomIn"
    centered
    no-close-on-backdrop
  >
    <form class="customform">
      <BRow>
        <BCol lg="6" class="mt-2">
          <InputLabel value="Bid Price" />
          <TextInput
            v-model="form.bid_price"
            type="Number"
            class="form-control"
            :light="true"
          />
        </BCol>
        <BCol lg="6" class="mt-2">
          <InputLabel value="Delivery Term" />
          <TextInput
            v-model="form.delivery_term"
            type="text"
            class="form-control"
            :light="true"
          />
        </BCol>
        <BCol lg="12" class="mt-2">
          <InputLabel value="Technical Proposal" />
          <CustomEditorMini v-model="form.item_description" :modal-size="modal_size" />
        </BCol>

        <BCol lg="12"><hr class="text-muted mt-4 mb-0" /></BCol>
      </BRow>
    </form>

    <template v-slot:footer>
      <b-button @click="hide()" variant="light" block>Cancel</b-button>
      <b-button
        @click="updateItemBidOffer(form)"
        variant="primary"
        :disabled="form.processing"
        block
        >Update</b-button
      >
    </template>
  </b-modal>
</template>
<script>
import { useForm } from "@inertiajs/vue3";
import Multiselect from "@vueform/multiselect";
import InputError from "@/Shared/Components/Forms/InputError.vue";
import InputLabel from "@/Shared/Components/Forms/InputLabel.vue";
import TextInput from "@/Shared/Components/Forms/TextInput.vue";
import CustomEditorMini from "@/Shared/Components/Forms/CustomEditorMini.vue";

export default {
  components: {
    InputError,
    InputLabel,
    TextInput,
    Multiselect,
    CustomEditorMini
  },
  props: ["dropdowns"],
  data() {
    return {
      currentUrl: window.location.origin,
      form: useForm({
        id: null,
        bid_price: null,
        technical_proposal: "",
        delivery_term: "7 days upon received of PO",
      }),
      action_type: null,
      showModal: false,

    };
  },

  watch: {
    "form.item_unit_id": function (value) {
      if (value) {
        this.getItemUnitType(value);
      }
    },
  },

  methods: {
    edit(item) {
      this.form.id = item.id;
      if (item.technical_proposal) {
        this.form.technical_proposal = item.technical_proposal;
      } else {
        this.form.technical_proposal = item.item.item_description;
      }

      if (item.delivery_term) {
        this.form.delivery_term = item.delivery_term;
      } else {
        this.form.delivery_term = "7 days upon received of Notice to Proceed";
      }

      this.form.bid_price = item.bid_price;
      this.showModal = true;
    },

    updateItemBidOffer(data) {
      this.form.post("/faims/offers", {
        preserveScroll: true,
        onSuccess: () => {
          this.hide(); // Hide only when successful
        },
        onError: () => {
          // Optionally handle errors
          console.error("Failed to update bid price");
        },
      });
    },

    hide() {
      this.form.reset();
      this.showModal = false;
    },
  },
};
</script>
