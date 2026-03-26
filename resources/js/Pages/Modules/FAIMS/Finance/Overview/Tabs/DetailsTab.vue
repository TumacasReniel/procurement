<template>
  <div>
    <div class="card border-0 shadow-sm mb-3">
      <div class="card-header bg-primary">
        <h6 class="mb-0 fs-13 text-white">Request Information</h6>
      </div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-6">
            <div class="text-muted fs-11 fw-medium">Request Number</div>
            <div class="fw-semibold text-primary">{{ request?.code || '-' }}</div>
          </div>
          <div class="col-md-6">
            <div class="text-muted fs-11 fw-medium">Request Date</div>
            <div class="fw-semibold">{{ formatDate(request?.date) }}</div>
          </div>
          <div class="col-md-6">
            <div class="text-muted fs-11 fw-medium">Payee / Creditor</div>
            <div class="fw-semibold">{{ request?.creditor || request?.payee || '-' }}</div>
          </div>
          <div class="col-md-6">
            <div class="text-muted fs-11 fw-medium">Amount</div>
            <div class="fw-semibold text-success">{{ request?.amount_formatted || formatAmount(request?.amount) }}</div>
          </div>
          <div class="col-md-6">
            <div class="text-muted fs-11 fw-medium">Division</div>
            <div class="fw-semibold">{{ request?.division || '-' }}</div>
          </div>
          <div class="col-md-6">
            <div class="text-muted fs-11 fw-medium">Status</div>
            <b-badge class="bg-primary text-white fs-11">{{ request?.status?.name || 'N/A' }}</b-badge>
          </div>
        </div>
      </div>
    </div>

    <div class="card border-0 shadow-sm">
      <div class="card-header bg-primary">
        <h6 class="mb-0 fs-13 text-white">Request Details</h6>
      </div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-6">
            <div class="text-muted fs-11 fw-medium">Requested By</div>
            <div class="fw-semibold">{{ request?.requested_by || '-' }}</div>
          </div>
          <div class="col-md-6">
            <div class="text-muted fs-11 fw-medium">Created By</div>
            <div class="fw-semibold">{{ request?.created_by || '-' }}</div>
          </div>
          <div class="col-12">
            <div class="text-muted fs-11 fw-medium">Purpose / Particulars</div>
            <div class="fw-semibold">{{ request?.particulars || '-' }}</div>
          </div>
          <div class="col-md-6">
            <div class="text-muted fs-11 fw-medium">Request Type</div>
            <div class="fw-semibold">{{ request?.request_type || '-' }}</div>
          </div>
          <div class="col-md-6">
            <div class="text-muted fs-11 fw-medium">Fund Source</div>
            <div class="fw-semibold">{{ request?.fund_source || '-' }}</div>
          </div>
          <div class="col-md-6">
            <div class="text-muted fs-11 fw-medium">Project Type</div>
            <div class="fw-semibold">{{ request?.project_type || '-' }}</div>
          </div>
          <div class="col-md-6">
            <div class="text-muted fs-11 fw-medium">Project</div>
            <div class="fw-semibold">{{ request?.project || '-' }}</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    request: { type: Object, default: () => ({}) },
  },
  methods: {
    formatDate(date) {
      if (!date) return '-';
      return new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
    },
    formatAmount(amount) {
      if (!amount) return '-';
      return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'PHP', minimumFractionDigits: 2 }).format(amount);
    },
  },
};
</script>

