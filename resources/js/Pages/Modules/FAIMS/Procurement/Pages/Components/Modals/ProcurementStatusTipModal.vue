<template>
  <b-modal
    v-model="modalValue"
    header-class="p-3 bg-light"
    :title="title"
    centered
    hide-footer
  >
    <div class="status-tip-body">
      <div v-if="!isEmployeeOnlyRole" class="status-tip-subtitle">
        {{ subtitle }}
      </div>
      <ul v-if="!isEmployeeOnlyRole" class="status-tip-steps">
        <li v-for="(step, idx) in steps" :key="`${step}-${idx}`">
          {{ step }}
        </li>
      </ul>
      <div class="status-tip-assigned">
        <strong>Assigned Personnel:</strong>
        <div v-if="assigned.length" class="status-tip-badges">
          <span
            v-for="(person, idx) in assigned"
            :key="`${person}-${idx}`"
            class="badge rounded-pill bg-primary-subtle text-primary status-tip-person"
          >
            {{ person }}
          </span>
        </div>
        <span v-else> - </span>
      </div>
      <div v-if="isEmployeeOnlyRole" class="status-tip-note">
        Note: If this procurement process is taking longer, please check with
        the assigned personnel to know the reason, or leave a comment for
        follow-up.
      </div>
    </div>
  </b-modal>
</template>

<script>
export default {
  props: {
    modelValue: {
      type: Boolean,
      default: false,
    },
    title: {
      type: String,
      default: "Status Guide",
    },
    subtitle: {
      type: String,
      default: "",
    },
    steps: {
      type: Array,
      default: () => [],
    },
    assigned: {
      type: Array,
      default: () => [],
    },
    isEmployeeOnlyRole: {
      type: Boolean,
      default: false,
    },
  },
  emits: ["update:modelValue"],
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
};
</script>

<style scoped>
.status-tip-body {
  font-size: 0.95rem;
}

.status-tip-subtitle {
  color: #64748b;
  margin-bottom: 0.5rem;
}

.status-tip-steps {
  margin-bottom: 0.75rem;
  padding-left: 1.1rem;
}

.status-tip-assigned {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 0.6rem 0.75rem;
}

.status-tip-badges {
  margin-top: 0.45rem;
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.status-tip-person {
  align-self: flex-start;
}

.status-tip-note {
  margin-top: 0.6rem;
  font-size: 0.85rem;
  color: #64748b;
}
</style>
