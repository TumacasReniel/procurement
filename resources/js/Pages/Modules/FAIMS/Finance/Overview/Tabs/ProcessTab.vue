<template>
  <div class="card border-0 shadow-sm">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
      <h6 class="mb-0 text-white">Process</h6>
      <span class="badge bg-light text-primary">{{ request?.status?.name || 'N/A' }}</span>
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>Step</th>
              <th>Indicator</th>
              <th>Assigned Personnel</th>
              <th>State</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="step in statusFlow" :key="step.name">
              <td class="fw-semibold">{{ step.name }}</td>
              <td>
                <span class="d-inline-flex align-items-center gap-2">
                  <span :class="['step-indicator-dot', getStepIndicatorClass(step)]"></span>
                  <span class="fs-12 text-muted">{{ getStepIndicatorText(step) }}</span>
                </span>
              </td>
              <td>
                <div v-if="getAssigneeList(step.name).length" class="d-flex flex-wrap gap-1">
                  <span
                    v-for="person in getAssigneeList(step.name)"
                    :key="`${step.name}-${person}`"
                    class="badge bg-primary-subtle text-primary border border-primary-subtle"
                  >
                    {{ person }}
                  </span>
                </div>
                <span v-else class="text-muted">-</span>
              </td>
              <td>
                <span v-if="step.isCurrent" class="badge bg-primary">Current</span>
                <span v-else-if="step.isPast" class="badge bg-success">Done</span>
                <span v-else class="badge bg-secondary">Pending</span>
              </td>
              <td>
                <button class="btn btn-primary btn-sm" @click="$emit('assign', step)">
                  <i class="ri-user-add-line me-1"></i>Assign
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  emits: ['assign'],
  props: {
    request: { type: Object, default: () => ({}) },
    statusFlow: { type: Array, default: () => [] },
    assigneeMap: { type: Object, default: () => ({}) },
  },
  methods: {
    getAssigneeList(stepName) {
      const value = this.assigneeMap?.[stepName];
      if (Array.isArray(value)) return value.filter(Boolean);
      if (typeof value === 'string' && value.trim()) return [value];
      return [];
    },
    getStepIndicatorClass(step) {
      if (step.isCurrent) return 'indicator-current';
      if (step.isPast) return 'indicator-done';
      return 'indicator-pending';
    },
    getStepIndicatorText(step) {
      if (step.isCurrent) return 'In Progress';
      if (step.isPast) return 'Completed';
      return 'Waiting';
    },
  },
};
</script>

<style scoped>
.step-indicator-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  display: inline-block;
}

.indicator-current {
  background: #0d6efd;
  box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.18);
}

.indicator-done {
  background: #198754;
  box-shadow: 0 0 0 3px rgba(25, 135, 84, 0.15);
}

.indicator-pending {
  background: #6c757d;
  box-shadow: 0 0 0 3px rgba(108, 117, 125, 0.15);
}
</style>

