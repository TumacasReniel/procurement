<template>
  <b-modal
    v-model="modalValue"
    style="--vz-modal-width: 600px"
    header-class="p-3 bg-light"
    title="Assign Personnel"
    class="v-modal-custom"
    modal-class="zoomIn"
    centered
    no-close-on-backdrop
  >
    <form class="customform" @submit.prevent="$emit('submit')">
      <div class="mb-3">
        <InputLabel value="Status" />
        <input type="text" class="form-control" :value="status" readonly />
      </div>
      <div class="mb-3">
        <InputLabel value="Employee" :message="errors.user_ids" />
        <div class="position-relative">
          <input
            ref="searchInput"
            type="text"
            class="form-control"
            :value="search"
            @input="handleSearchInput"
            placeholder="Search employee"
          />
          <div v-if="options.length" class="dropdown-menu show w-100">
            <button
              v-for="option in options"
              :key="option.value"
              type="button"
              class="dropdown-item d-flex align-items-center gap-2"
              @click="$emit('select', option)"
            >
              <img
                v-if="option.avatar"
                :src="option.avatar"
                class="rounded-circle"
                style="width: 28px; height: 28px; object-fit: cover;"
              />
              <div>
                <div class="fw-semibold">{{ option.name }}</div>
                <div class="fs-11 text-muted">{{ option.position || "-" }}</div>
              </div>
            </button>
          </div>
        </div>
        <InputError :message="errors.user_ids" />
      </div>
      <div v-if="selected.length" class="alert alert-light border">
        <div class="d-flex align-items-center gap-2 mb-2">
          <i class="ri-group-line text-primary"></i>
          <div class="fw-semibold text-primary">Selected Personnel</div>
        </div>
        <div class="d-flex flex-wrap gap-2">
          <span
            v-for="person in selected"
            :key="person.value || person.id"
            class="badge rounded-pill bg-primary-subtle text-primary d-flex align-items-center gap-1"
          >
            <span>{{ person.name }}</span>
            <button
              type="button"
              class="btn btn-sm p-0 text-primary"
              @click="$emit('remove', person)"
            >
              <i class="ri-close-line"></i>
            </button>
          </span>
        </div>
      </div>
    </form>
    <template #footer>
      <b-button @click="modalValue = false" variant="light" block>
        Cancel
      </b-button>
      <b-button
        @click="$emit('submit')"
        variant="primary"
        :disabled="processing || !selected.length"
        block
      >
        Assign
      </b-button>
    </template>
  </b-modal>
</template>

<script>
import InputLabel from "@/Shared/Components/Forms/InputLabel.vue";
import InputError from "@/Shared/Components/Forms/InputError.vue";

export default {
  components: { InputLabel, InputError },
  props: {
    modelValue: {
      type: Boolean,
      default: false,
    },
    status: {
      type: String,
      default: "",
    },
    search: {
      type: String,
      default: "",
    },
    options: {
      type: Array,
      default: () => [],
    },
    selected: {
      type: Array,
      default: () => [],
    },
    processing: {
      type: Boolean,
      default: false,
    },
    errors: {
      type: Object,
      default: () => ({}),
    },
  },
  emits: [
    "update:modelValue",
    "update:search",
    "search",
    "select",
    "remove",
    "submit",
  ],
  computed: {
    modalValue: {
      get() {
        return this.modelValue;
      },
      set(value) {
        this.$emit("update:modelValue", value);
      },
    },
  },
  watch: {
    modelValue(value) {
      if (value) {
        this.$nextTick(() => {
          this.$refs.searchInput?.focus();
        });
      }
    },
  },
  methods: {
    handleSearchInput(event) {
      const value = event?.target?.value || "";
      this.$emit("update:search", value);
      this.$emit("search", value);
    },
  },
};
</script>
