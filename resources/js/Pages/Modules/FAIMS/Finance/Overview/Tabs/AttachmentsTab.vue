<template>
  <div class="card border-0 shadow-sm modern-attachments-card">
    <div class="card-header modern-attachments-header d-flex align-items-center justify-content-between flex-wrap gap-2">
      <div class="d-flex align-items-center gap-2">
        <span class="modern-attachments-icon"><i class="ri-attachment-2"></i></span>
        <div>
          <div class="fw-semibold text-dark">Required Attachments</div>
          <div class="text-muted fs-12">Upload PDF files and verify before validation.</div>
        </div>
      </div>
      <button class="btn btn-success btn-sm modern-primary-btn" @click="$emit('submit-validation')">
        <i class="ri-checkbox-circle-line me-1"></i>Submit for Validation
      </button>
    </div>
    <div class="card-body">
      <div v-if="!requiredDocuments.length" class="empty-state text-center py-5">
        <div class="empty-state-icon mb-2"><i class="ri-file-list-3-line"></i></div>
        <div class="fw-semibold text-dark">No required documents configured.</div>
        <div class="text-muted fs-12">Once documents are configured, they will appear here.</div>
      </div>
      <div v-else class="table-responsive modern-table-wrap">
        <table class="table table-bordered align-middle mb-0 attachments-table">
          <thead class="table-light">
            <tr>
              <th class="text-center" style="width: 5%">#</th>
              <th style="width: 30%">Required Documents</th>
              <th class="text-center" style="width: 22%">Attachments</th>
              <th class="text-center" style="width: 14%">Signed Attachments</th>
              <th class="text-center" style="width: 10%">File Code</th>
              <th class="text-center" style="width: 9%">Verified</th>
              <th class="text-center" style="width: 10%">Action</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(doc, index) in requiredDocuments" :key="doc.id">
              <td class="text-center fw-semibold text-muted">{{ index + 1 }}</td>
              <td>
                <div class="fw-semibold text-dark">{{ doc.name }}</div>
                <small class="text-muted">Required PDF attachment</small>
              </td>
              <td class="text-center">
                <input
                  type="file"
                  class="d-none"
                  :id="`attachment-input-${doc.id}`"
                  accept=".pdf,application/pdf"
                  @change="$emit('attachment-selected', doc.id, $event)"
                />
                <div v-if="attachmentByDocument[doc.id]" class="mb-2">
                  <div class="fw-semibold text-dark text-truncate" :title="attachmentByDocument[doc.id]?.name || attachmentByDocument[doc.id]?.document_name">
                    {{ attachmentByDocument[doc.id]?.name || attachmentByDocument[doc.id]?.document_name || 'Uploaded file' }}
                  </div>
                </div>
                <div class="d-flex gap-2 justify-content-center flex-wrap">
                  <button v-if="attachmentByDocument[doc.id]" class="btn btn-outline-primary btn-sm modern-action-btn" @click="$emit('open-uploaded', doc.id)"><i class="ri-eye-line me-1"></i>View</button>
                  <button
                    v-if="attachmentByDocument[doc.id]"
                    class="btn btn-outline-warning btn-sm modern-action-btn"
                    :disabled="Number(attachmentProcessingId) === Number(doc.id)"
                    @click="pickAttachmentFile(doc.id)"
                  >
                    <i class="ri-refresh-line me-1"></i>{{ Number(attachmentProcessingId) === Number(doc.id) ? 'Saving...' : 'Replace' }}
                  </button>
                  <button
                    v-if="!attachmentByDocument[doc.id]"
                    class="btn btn-outline-success btn-sm modern-action-btn"
                    :disabled="Number(attachmentProcessingId) === Number(doc.id)"
                    @click="pickAttachmentFile(doc.id)"
                  >
                    <i class="ri-upload-2-line me-1"></i>{{ Number(attachmentProcessingId) === Number(doc.id) ? 'Saving...' : 'Upload' }}
                  </button>
                </div>
              </td>
              <td class="text-center">
                <button
                  v-if="attachmentByDocument[doc.id]"
                  class="btn btn-primary btn-sm modern-primary-btn"
                  @click="$emit('open-uploaded', doc.id)"
                >
                  <i class="ri-file-line me-1"></i>View Uploaded
                </button>
                <span v-else class="text-muted">-</span>
              </td>
              <td class="text-center"><span class="file-code-chip">{{ generateFileCode(doc.id) }}</span></td>
              <td class="text-center">
                <span v-if="attachmentByDocument[doc.id]?.verification_status === 'verified'" class="badge rounded-pill bg-success-subtle text-success border border-success-subtle">Verified</span>
                <span v-else-if="attachmentByDocument[doc.id]?.verification_status === 'incorrect'" class="badge rounded-pill bg-danger-subtle text-danger border border-danger-subtle">Incorrect</span>
                <span v-else class="badge rounded-pill bg-warning-subtle text-warning border border-warning-subtle">Pending</span>
                <div v-if="attachmentErrors[doc.id]" class="text-danger fs-11 mt-1">{{ attachmentErrors[doc.id] }}</div>
              </td>
              <td class="text-center">
                <div class="d-flex gap-2 justify-content-center">
                  <button class="btn btn-success btn-sm modern-action-btn" :disabled="!getAttachmentByDocumentId(doc.id)?.id" @click="$emit('verify-action', doc.id, 'verified')">Verify</button>
                  <button class="btn btn-danger btn-sm modern-action-btn" :disabled="!getAttachmentByDocumentId(doc.id)?.id" @click="$emit('verify-action', doc.id, 'incorrect')">Incorrect</button>
                </div>
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
  emits: ['submit-validation', 'attachment-selected', 'open-uploaded', 'verify-action'],
  props: {
    requestCode: { type: String, default: 'FIN' },
    requiredDocuments: { type: Array, default: () => [] },
    attachmentByDocument: { type: Object, default: () => ({}) },
    attachmentErrors: { type: Object, default: () => ({}) },
    attachmentProcessingId: { type: [Number, String, null], default: null },
  },
  methods: {
    pickAttachmentFile(documentId) {
      const input = document.getElementById(`attachment-input-${documentId}`);
      if (input) {
        input.value = '';
        input.click();
      }
    },
    generateFileCode(documentId) {
      const clean = String(this.requestCode || 'FIN').replace(/[^A-Z0-9]/gi, '').toUpperCase();
      return `${clean}-${String(documentId).padStart(3, '0')}`;
    },
    getAttachmentByDocumentId(documentId) {
      return this.attachmentByDocument?.[documentId] || null;
    },
  },
};
</script>

<style scoped>
.modern-attachments-card {
  border-radius: 14px;
}

.modern-attachments-header {
  background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
  border-bottom: 1px solid #e2e8f0;
}

.modern-attachments-icon {
  width: 30px;
  height: 30px;
  border-radius: 8px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  color: #2563eb;
  background: #dbeafe;
}

.modern-primary-btn {
  border-radius: 8px;
}

.modern-action-btn {
  border-radius: 8px;
}

.modern-table-wrap {
  border: 1px solid #e5e7eb;
  border-radius: 12px;
}

.attachments-table th,
.attachments-table td {
  vertical-align: middle;
}

.attachments-table thead th {
  font-size: 0.82rem;
  letter-spacing: 0.01em;
  color: #334155;
}

.attachments-table tbody tr:hover {
  background: #f8fafc;
}

.file-code-chip {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
  font-size: 0.8rem;
  padding: 0.3rem 0.5rem;
  border-radius: 8px;
  background: #eff6ff;
  border: 1px solid #dbeafe;
  color: #1e3a8a;
}

.empty-state-icon {
  font-size: 1.5rem;
  color: #64748b;
}
</style>

