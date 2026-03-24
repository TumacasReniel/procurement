<template>
  <div>
    <Head title="Finance Overview" />
    <PageHeader title="Finance Overview" pageTitle="User" />

    <div class="bg-primary mb-3 p-3 rounded-3">
      <div class="status-flow-banner-header" @click="toggleStatusFlow" style="cursor: pointer;">
        <div class="d-flex align-items-center flex-wrap gap-2">
          <i class="ri-flow-chart text-white"></i>
          <span class="fw-bold text-white">Finance Request Progress</span>
          <b-badge class="bg-white text-primary ms-2" style="font-size: 0.75rem; padding: 0.35rem 0.65rem;">
            {{ request?.status?.name || 'N/A' }}
          </b-badge>
        </div>
        <i :class="isStatusFlowCollapsed ? 'ri-arrow-down-s-line' : 'ri-arrow-up-s-line'" class="text-white" style="font-size: 1.2rem;"></i>
      </div>
      <div v-show="!isStatusFlowCollapsed" class="status-flow-banner-content mt-2">
        <div class="status-flow-banner-track">
          <div v-for="(step, index) in statusFlow" :key="step.name" class="status-flow-banner-step-wrapper">
            <div v-if="index > 0" class="status-flow-banner-line" :class="{ connected: statusFlow[index - 1].isPast, active: statusFlow[index - 1].isPast && step.isCurrent }">
              <i class="ri-arrow-right-s-line"></i>
            </div>
            <div class="status-flow-banner-step" :class="{ completed: step.isPast, active: step.isCurrent, pending: !step.isPast && !step.isCurrent }">
              <div class="status-flow-banner-dot">
                <i v-if="step.isPast" class="ri-check-line"></i>
                <i v-else-if="step.isCurrent" class="ri-star-fill"></i>
                <i v-else class="ri-circle-line"></i>
              </div>
              <div class="status-flow-banner-label">{{ step.name }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="row g-3">
      <div class="col-md-2">
        <div class="card shadow-sm border-0">
          <div class="card-header bg-primary text-white">
            <h6 class="mb-0 text-white">Finance Menu</h6>
          </div>
          <div class="card-body p-2">
            <button
              v-for="item in navItems"
              :key="item.key"
              :class="['btn w-100 text-start mb-2', activeTab === item.key ? 'btn-primary' : 'btn-light']"
              @click="show(item.key)"
            >
              <i :class="item.icon + ' me-2'"></i>{{ item.label }}
            </button>
          </div>
        </div>
      </div>

      <div :class="mainContentClass">
        <div class="card shadow-sm border-0">
          <div class="card-body p-3">
            <template v-if="activeTab === 1">
              <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-primary">
                  <h6 class="mb-0 fs-13 text-white">Request Information</h6>
                </div>
                <div class="card-body">
                  <div class="row g-3">
                    <div class="col-md-6">
                      <div class="text-muted fs-11 fw-medium">Request Number</div>
                      <div class="fw-semibold text-primary">{{ request?.code || "-" }}</div>
                    </div>
                    <div class="col-md-6">
                      <div class="text-muted fs-11 fw-medium">Request Date</div>
                      <div class="fw-semibold">{{ formatDate(request?.date) }}</div>
                    </div>
                    <div class="col-md-6">
                      <div class="text-muted fs-11 fw-medium">Payee / Creditor</div>
                      <div class="fw-semibold">{{ request?.creditor || request?.payee || "-" }}</div>
                    </div>
                    <div class="col-md-6">
                      <div class="text-muted fs-11 fw-medium">Amount</div>
                      <div class="fw-semibold text-success">{{ request?.amount_formatted || formatAmount(request?.amount) }}</div>
                    </div>
                    <div class="col-md-6">
                      <div class="text-muted fs-11 fw-medium">Division</div>
                      <div class="fw-semibold">{{ request?.division || "-" }}</div>
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
                      <div class="fw-semibold">{{ request?.requested_by || "-" }}</div>
                    </div>
                    <div class="col-md-6">
                      <div class="text-muted fs-11 fw-medium">Created By</div>
                      <div class="fw-semibold">{{ request?.created_by || "-" }}</div>
                    </div>
                    <div class="col-12">
                      <div class="text-muted fs-11 fw-medium">Purpose / Particulars</div>
                      <div class="fw-semibold">{{ request?.particulars || "-" }}</div>
                    </div>
                    <div class="col-md-6">
                      <div class="text-muted fs-11 fw-medium">Request Type</div>
                      <div class="fw-semibold">{{ request?.request_type || "-" }}</div>
                    </div>
                    <div class="col-md-6">
                      <div class="text-muted fs-11 fw-medium">Fund Source</div>
                      <div class="fw-semibold">{{ request?.fund_source || "-" }}</div>
                    </div>
                    <div class="col-md-6">
                      <div class="text-muted fs-11 fw-medium">Project Type</div>
                      <div class="fw-semibold">{{ request?.project_type || "-" }}</div>
                    </div>
                    <div class="col-md-6">
                      <div class="text-muted fs-11 fw-medium">Project</div>
                      <div class="fw-semibold">{{ request?.project || "-" }}</div>
                    </div>
                  </div>
                </div>
              </div>
            </template>

            <template v-else-if="activeTab === 2">
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
                          <th>State</th>
                          <th>Assigned</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="step in statusFlow" :key="step.name">
                          <td class="fw-semibold">{{ step.name }}</td>
                          <td>
                            <span v-if="step.isCurrent" class="badge bg-primary">Current</span>
                            <span v-else-if="step.isPast" class="badge bg-success">Done</span>
                            <span v-else class="badge bg-secondary">Pending</span>
                          </td>
                          <td>{{ getAssignee(step.name) }}</td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </template>

            <template v-else>
              <div class="card border-0 shadow-sm">
                <div class="card-header bg-light d-flex align-items-center justify-content-between flex-wrap gap-2">
                  <button class="btn btn-success btn-sm" @click="submitForValidation">Submit for Validation</button>
                </div>
                <div class="card-body">
                  <div v-if="!requiredDocuments.length" class="text-muted">No required documents configured.</div>
                  <div v-else class="table-responsive">
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
                          <td class="text-center">{{ index + 1 }}</td>
                          <td><div class="fw-semibold">{{ doc.name }}</div></td>
                          <td class="text-center">
                            <input type="file" class="d-none" :id="`attachment-input-${doc.id}`" accept=".pdf,application/pdf" @change="onAttachmentSelected(doc.id, $event)" />
                            <div class="d-flex gap-1 justify-content-center flex-wrap">
                              <button v-if="attachmentByDocument[doc.id]" class="btn btn-info btn-sm text-white" @click="openUploadedAttachment(doc.id)"><i class="ri-eye-line me-1"></i>View</button>
                              <button v-if="attachmentByDocument[doc.id]" class="btn btn-warning btn-sm text-white" @click="pickAttachmentFile(doc.id)"><i class="ri-refresh-line me-1"></i>Replace</button>
                              <button v-if="!attachmentByDocument[doc.id]" class="btn btn-warning btn-sm text-white" @click="pickAttachmentFile(doc.id)"><i class="ri-upload-2-line me-1"></i>Upload</button>
                            </div>
                          </td>
                          <td class="text-center">
                            <button
                              v-if="attachmentByDocument[doc.id]"
                              class="btn btn-info btn-sm text-white"
                              @click="openUploadedAttachment(doc.id)"
                            >
                              <i class="ri-file-line me-1"></i>View Uploaded
                            </button>
                            <span v-else class="text-muted">-</span>
                          </td>
                          <td class="text-center">{{ generateFileCode(doc.id) }}</td>
                          <td class="text-center">
                            <span v-if="attachmentByDocument[doc.id]?.verification_status === 'verified'" class="badge bg-success">Verified</span>
                            <span v-else-if="attachmentByDocument[doc.id]?.verification_status === 'incorrect'" class="badge bg-danger">Incorrect</span>
                            <span v-else class="badge bg-secondary">Pending</span>
                            <div v-if="attachmentErrors[doc.id]" class="text-danger fs-11 mt-1">{{ attachmentErrors[doc.id] }}</div>
                          </td>
                          <td class="text-center">
                            <div class="d-flex gap-1 justify-content-center">
                              <button class="btn btn-success btn-sm" :disabled="!getAttachmentByDocumentId(doc.id)?.id" @click="verifyAttachmentAction(doc.id, 'verified')">Verify</button>
                              <button class="btn btn-danger btn-sm" :disabled="!getAttachmentByDocumentId(doc.id)?.id" @click="verifyAttachmentAction(doc.id, 'incorrect')">Incorrect</button>
                            </div>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </template>
          </div>
        </div>
      </div>

      <RightSidebar :request="request" :logs="logs" :isRightCollapsed="isRightCollapsed" @toggleRightSidebar="toggleRightSidebar" />
    </div>

    <b-modal
      v-model="showPdfViewerModal"
      style="--vz-modal-width: 96vw"
      header-class="p-3 bg-dark text-white"
      :title="pdfViewerName || 'PDF Viewer'"
      class="v-modal-custom"
      modal-class="zoomIn pdf-viewer-modal"
      centered
      no-close-on-backdrop
      @hidden="closePdfViewer"
    >
      <div class="pdf-viewer-stage">
        <div class="pdf-paper" v-if="pdfViewerUrl">
          <iframe :src="pdfViewerUrl" title="Attachment PDF Preview" class="pdf-viewer-frame"></iframe>
        </div>
        <div v-else class="text-muted">No PDF available for preview.</div>
      </div>
      <template v-slot:footer>
        <div class="pdf-viewer-footer-info">{{ pdfViewerName || 'No file selected' }} <span v-if="pdfViewerSize">({{ formatFileSize(pdfViewerSize) }})</span></div>
        <b-button @click="openPdfInNewTab" variant="info" class="text-white" :disabled="!pdfViewerUrl" block>Open in New Tab</b-button>
        <b-button @click="showPdfViewerModal = false" variant="light" block>Close</b-button>
      </template>
    </b-modal>

    <b-modal v-model="showValidationFeedbackModal" style="--vz-modal-width: 480px" header-class="p-3 bg-light" title="Attachment Check" class="v-modal-custom" modal-class="zoomIn" centered no-close-on-backdrop>
      <div class="alert alert-warning mb-0">{{ validationFeedbackMessage }}</div>
      <template v-if="validationMissingDocuments.length">
        <div class="mt-3 fw-semibold">Missing documents:</div>
        <ul class="mb-0 mt-2 ps-3">
          <li v-for="doc in validationMissingDocuments" :key="doc.id">{{ doc.name }}</li>
        </ul>
      </template>
      <template v-slot:footer>
        <b-button @click="showValidationFeedbackModal = false" variant="light" block>Close</b-button>
      </template>
    </b-modal>

    <b-modal v-model="showAttachmentUploadErrorModal" style="--vz-modal-width: 460px" header-class="p-3 bg-light" title="Upload Error" class="v-modal-custom" modal-class="zoomIn" centered no-close-on-backdrop>
      <div class="alert alert-danger mb-0">{{ attachmentUploadErrorMessage }}</div>
      <template v-slot:footer>
        <b-button @click="showAttachmentUploadErrorModal = false" variant="light" block>Close</b-button>
      </template>
    </b-modal>
  </div>
</template>

<script>
import { Head } from '@inertiajs/vue3';
import PageHeader from "@/Shared/Components/PageHeader.vue";
import axios from "axios";
import RightSidebar from "./Components/RightSidebar.vue";

export default {
  components: { Head, PageHeader, RightSidebar },
  props: ["request", "roles", "logs"],
  data() {
    return {
      activeTab: 1,
      isRightCollapsed: true,
      isStatusFlowCollapsed: false,
      localAttachments: Array.isArray(this.request?.attachments) ? [...this.request.attachments] : [],
      attachmentForms: {},
      attachmentErrors: {},
      attachmentProcessingId: null,
      showValidationFeedbackModal: false,
      validationFeedbackMessage: '',
      validationMissingDocuments: [],
      showAttachmentUploadErrorModal: false,
      attachmentUploadErrorMessage: '',
      showPdfViewerModal: false,
      pdfViewerUrl: '',
      pdfViewerName: '',
      pdfViewerSize: null,
      navItems: [
        { key: 1, label: 'Overview', icon: 'ri-information-line' },
        { key: 2, label: 'Process', icon: 'ri-route-line' },
        { key: 3, label: 'Attachments', icon: 'ri-attachment-2' },
      ],
    };
  },
  computed: {
    mainContentClass() {
      return this.isRightCollapsed ? 'col-md-9' : 'col-md-7';
    },
    requiredDocuments() {
      return Array.isArray(this.request?.required_documents) ? this.request.required_documents : [];
    },
    attachmentByDocument() {
      const map = {};
      (this.localAttachments || []).forEach((item) => {
        if (item?.finance_document_id) map[item.finance_document_id] = item;
      });
      return map;
    },
    statusFlow() {
      const currentStatus = this.request?.status?.name;
      const steps = ['Pending','Verified','Validated','Allotment Available','Obligated','For Disbursement','Funds Available','Charged','Approved','Completed/LBP Processing'];
      const currentIndex = steps.findIndex((name) => name === currentStatus);
      return steps.map((name, index) => ({ name, isCurrent: name === currentStatus, isPast: currentIndex !== -1 && index < currentIndex }));
    },
  },
  methods: {
    show(tab) { this.activeTab = tab; },
    toggleRightSidebar() { this.isRightCollapsed = !this.isRightCollapsed; },
    toggleStatusFlow() { this.isStatusFlowCollapsed = !this.isStatusFlowCollapsed; },
    formatDate(date) {
      if (!date) return '-';
      return new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
    },
    formatAmount(amount) {
      if (!amount) return '-';
      return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'PHP', minimumFractionDigits: 2 }).format(amount);
    },
    formatFileSize(size) {
      if (!size && size !== 0) return '-';
      const kb = 1024, mb = kb * 1024;
      if (size >= mb) return `${(size / mb).toFixed(2)} MB`;
      if (size >= kb) return `${(size / kb).toFixed(2)} KB`;
      return `${size} B`;
    },
    generateFileCode(documentId) {
      const requestCode = this.request?.code || 'FIN';
      const clean = String(requestCode).replace(/[^A-Z0-9]/gi, '').toUpperCase();
      return `${clean}-${String(documentId).padStart(3, '0')}`;
    },
    getAttachmentByDocumentId(documentId) {
      const targetDocumentId = Number(documentId);
      return (this.localAttachments || []).find((item) => Number(item?.finance_document_id) === targetDocumentId) || null;
    },
    getAttachmentPreviewUrl(attachment) {
      if (!attachment) return null;

      const direct = attachment.url || attachment.file_url || null;
      if (direct) return direct;

      if (attachment.path) {
        const cleanPath = String(attachment.path).replace(/^\/+/, '');
        return `/storage/${cleanPath}`;
      }

      return null;
    },
    getAssigneeList(stepName) {
      const map = this.request?.assignees || this.request?.assigned_personnel || {};
      const value = map?.[stepName];
      if (Array.isArray(value)) return value.filter(Boolean);
      if (typeof value === 'string' && value.trim()) return [value];
      return [];
    },
    getAssignee(stepName) {
      const list = this.getAssigneeList(stepName);
      return list.length ? list.join(', ') : '-';
    },
    pickAttachmentFile(documentId) {
      const input = document.getElementById(`attachment-input-${documentId}`);
      if (input) input.click();
    },
    onAttachmentSelected(documentId, event) {
      const file = event?.target?.files?.[0] || null;
      this.attachmentForms[documentId] = { file };
      this.attachmentErrors[documentId] = null;
      if (file && file.type !== 'application/pdf' && !String(file.name || '').toLowerCase().endsWith('.pdf')) {
        this.attachmentErrors[documentId] = 'Please upload a PDF file only.';
        this.attachmentForms[documentId] = { file: null };
        if (event?.target) event.target.value = '';
        return;
      }
      if (file) this.submitAttachment(documentId);
    },
    submitAttachment(documentId) {
      const selected = this.attachmentForms[documentId]?.file;
      if (!selected) {
        this.attachmentErrors[documentId] = 'Please choose a PDF file to upload.';
        return;
      }
      this.attachmentProcessingId = documentId;
      this.attachmentErrors[documentId] = null;
      const formData = new FormData();
      formData.append('finance_document_id', documentId);
      formData.append('file', selected);
      axios.post(`/faims/finance-requests/${this.request.id}/attachments`, formData, { headers: { 'Content-Type': 'multipart/form-data' } })
        .then((response) => {
          const uploaded = response?.data?.data || null;
          if (uploaded) {
            const existingIndex = this.localAttachments.findIndex((item) => Number(item.finance_document_id) === Number(documentId));
            const existing = existingIndex >= 0 ? this.localAttachments[existingIndex] : null;
            const normalized = {
              ...uploaded,
              id: uploaded.id || existing?.id || null,
              finance_document_id: Number(uploaded.finance_document_id || documentId),
              name: uploaded.name || selected.name,
              verification_status: uploaded.verification_status || 'pending',
              url: uploaded.url || (uploaded.path ? `/storage/${uploaded.path}` : null),
            };
            if (existingIndex >= 0) this.localAttachments.splice(existingIndex, 1, { ...existing, ...normalized });
            else this.localAttachments.push(normalized);
          }
          this.attachmentErrors[documentId] = null;
          this.attachmentForms[documentId] = { file: null };
          const input = document.getElementById(`attachment-input-${documentId}`);
          if (input) input.value = '';
        })
        .catch((error) => {
          if (error?.response?.status === 422) {
            const message = error.response.data?.errors?.file?.[0] || error.response.data?.errors?.finance_document_id?.[0] || 'Upload did not complete. Please try again.';
            if (String(message).toLowerCase().includes('must not be greater than 10240 kilobytes')) {
              this.attachmentUploadErrorMessage = 'The PDF is too large. Please upload a file up to 10 MB.';
              this.showAttachmentUploadErrorModal = true;
              this.attachmentErrors[documentId] = null;
            } else {
              this.attachmentErrors[documentId] = message;
            }
          } else this.attachmentErrors[documentId] = 'Upload did not complete. Please try again.';
        })
        .finally(() => { this.attachmentProcessingId = null; });
    },
    openUploadedAttachment(documentId) {
      const attachment = this.getAttachmentByDocumentId(documentId);
      this.attachmentErrors[documentId] = null;

      if (!attachment) {
        this.attachmentErrors[documentId] = 'No uploaded file found yet.';
        return;
      }

      const previewUrl = this.getAttachmentPreviewUrl(attachment);
      if (!previewUrl) {
        this.attachmentErrors[documentId] = 'Uploaded file has no preview link yet. Please refresh and try again.';
        return;
      }

      this.pdfViewerUrl = previewUrl;
      this.pdfViewerName = attachment.name || attachment.document_name || 'Attachment';
      this.pdfViewerSize = attachment.size ?? null;
      this.showPdfViewerModal = true;
    },
    openPdfInNewTab() {
      if (!this.pdfViewerUrl) return;
      window.open(this.pdfViewerUrl, '_blank');
    },
    closePdfViewer() {
      this.pdfViewerUrl = '';
      this.pdfViewerName = '';
      this.pdfViewerSize = null;
    },
    verifyAttachmentAction(documentId, status) {
      this.attachmentErrors[documentId] = null;
      const attachment = this.getAttachmentByDocumentId(documentId);
      if (!attachment || !attachment.id) {
        this.attachmentErrors[documentId] = 'Please upload the attachment first before verification.';
        return;
      }
      const note = status === 'incorrect' ? prompt('Reason why attachment is incorrect (optional):', '') : '';
      axios.patch(`/faims/finance-requests/${this.request.id}/attachments/${attachment.id}/verify`, { status, note })
        .then((response) => {
          const data = response?.data?.data || {};
          const target = this.localAttachments.find((item) => Number(item.id) === Number(attachment.id));
          if (target) {
            target.verification_status = data.verification_status || status;
            target.verification_note = data.verification_note || null;
            target.verified_at = data.verified_at || null;
            target.verified_by = data.verified_by || null;
          }
        })
        .catch(() => { this.attachmentErrors[documentId] = 'Could not update verification status. Please try again.'; });
    },
    submitForValidation() {
      const missing = this.requiredDocuments.filter((doc) => !this.getAttachmentByDocumentId(doc.id));
      if (missing.length) {
        this.validationFeedbackMessage = `Please upload all required PDF attachments first. Missing: ${missing.length}.`;
        this.validationMissingDocuments = missing;
        this.showValidationFeedbackModal = true;
        return;
      }
      this.validationFeedbackMessage = 'All required attachments are complete and ready for validation.';
      this.validationMissingDocuments = [];
      this.showValidationFeedbackModal = true;
    },
  },
};
</script>

<style scoped>
.attachments-table th,
.attachments-table td { vertical-align: middle; }

.status-flow-banner-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.status-flow-banner-track {
  display: flex;
  align-items: center;
  overflow-x: auto;
  padding: 0.5rem 0;
}

.status-flow-banner-step-wrapper { display: flex; align-items: center; }
.status-flow-banner-line i { color: rgba(255, 255, 255, 0.35); }
.status-flow-banner-line.connected i { color: #bbf7d0; }
.status-flow-banner-line.active i { color: #fef3c7; }

.status-flow-banner-step {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.35rem;
  min-width: 80px;
  padding: 0.5rem;
  border-radius: 12px;
}
.status-flow-banner-step.completed { background: rgba(255, 255, 255, 0.18); }
.status-flow-banner-step.active { background: rgba(255, 255, 255, 0.28); }
.status-flow-banner-step.pending { background: rgba(255, 255, 255, 0.08); }

.status-flow-banner-dot {
  width: 30px;
  height: 30px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
}
.status-flow-banner-step.active .status-flow-banner-dot {
  background: #fff;
  color: #0d6efd;
}
.status-flow-banner-label {
  font-size: 0.65rem;
  color: rgba(255, 255, 255, 0.95);
  text-align: center;
  white-space: nowrap;
}

.pdf-viewer-stage {
  background: #7b7b7b;
  border-radius: 6px;
  min-height: 74vh;
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 18px;
}

.pdf-paper {
  width: min(820px, 100%);
  height: 72vh;
  background: #ffffff;
  box-shadow: 0 8px 18px rgba(0, 0, 0, 0.28);
}

.pdf-viewer-frame {
  width: 100%;
  height: 100%;
  border: 0;
}

.pdf-viewer-footer-info {
  flex: 1;
  color: #495057;
  font-weight: 500;
}
</style>




