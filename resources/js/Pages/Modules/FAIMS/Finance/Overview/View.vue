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
      <div :class="leftSidebarClass">
        <div class="card shadow-sm border-0 finance-left-nav-card">
          <div class="card-header finance-left-nav-header d-flex align-items-center justify-content-between">
            <div v-if="!isLeftCollapsed">
              <h5 class="mb-1 fw-semibold text-primary d-flex align-items-center gap-2">
                <i class="ri-file-list-3-line"></i>
                <span>FR#:{{ request?.code || 'N/A' }}</span>
              </h5>
            </div>
            <button
              @click="toggleLeftSidebar"
              class="btn btn-sm btn-light rounded-circle p-2 ms-2"
              style="width: 40px; height: 40px"
              :title="isLeftCollapsed ? 'Expand menu' : 'Collapse menu'"
            >
              <i :class="isLeftCollapsed ? 'ri-arrow-right-line' : 'ri-arrow-left-line'" class="text-primary fs-6"></i>
            </button>
          </div>

          <div v-if="!isLeftCollapsed" class="card-body finance-left-nav-body">
            <div class="p-3">
              <h6 class="text-muted mb-3 fw-bold">Navigation</h6>
              <div class="nav flex-column">
                <button
                  v-for="item in navItems"
                  :key="item.key"
                  :class="[
                    'nav-link text-start mb-2 rounded-pill border-0 transition-all finance-left-nav-btn',
                    activeTab === item.key ? 'bg-primary text-white shadow-sm' : 'bg-white text-dark hover-bg-light'
                  ]"
                  @click="show(item.key)"
                >
                  <i :class="item.icon + ' align-middle me-3 fs-5'"></i>{{ item.label }}
                </button>
              </div>
            </div>
          </div>

          <div v-else class="card-body finance-left-nav-body">
            <div class="p-2 d-flex flex-column align-items-center">
              <button
                v-for="item in navItems"
                :key="`collapsed-${item.key}`"
                :class="[
                  'nav-link mb-2 rounded-pill border-0 transition-all p-2 finance-left-nav-icon-btn',
                  activeTab === item.key ? 'bg-primary text-white shadow-sm' : 'bg-white text-dark hover-bg-light'
                ]"
                @click="show(item.key)"
                v-b-tooltip.hover
                :title="item.label"
              >
                <i :class="item.icon + ' fs-5'"></i>
              </button>
            </div>
          </div>
        </div>
      </div>

      <div :class="mainContentClass">
        <div class="card shadow-sm border-0">
          <div class="card-body p-3">
            <DetailsTab v-if="activeTab === 1" :request="request" />

            <ProcessTab
              v-else-if="activeTab === 2"
              :request="request"
              :status-flow="statusFlow"
              :assignee-map="localAssignedPersonnel"
              @assign="openAssignModal"
            />

            <AttachmentsTab
              v-else
              :request-code="request?.code"
              :required-documents="requiredDocuments"
              :attachment-by-document="attachmentByDocument"
              :attachment-errors="attachmentErrors"
              :attachment-processing-id="attachmentProcessingId"
              @submit-validation="submitForValidation"
              @attachment-selected="onAttachmentSelected"
              @open-uploaded="openUploadedAttachment"
              @verify-action="verifyAttachmentAction"
            />
          </div>
        </div>
      </div>

      <RightSidebar :request="request" :logs="logs" :isRightCollapsed="isRightCollapsed" @toggleRightSidebar="toggleRightSidebar" />
    </div>

    <b-modal
      v-model="showAssignModal"
      style="--vz-modal-width: 560px"
      header-class="p-3 bg-light"
      title="Assign Personnel"
      class="v-modal-custom"
      modal-class="zoomIn"
      centered
      no-close-on-backdrop
    >
      <div class="mb-2">
        <label class="form-label fw-semibold">Process Step</label>
        <input type="text" class="form-control" :value="assignForm.status || '-'" readonly />
        <div v-if="assignErrors.status" class="text-danger fs-12 mt-1">{{ assignErrors.status }}</div>
      </div>

      <div class="mb-2">
        <label class="form-label fw-semibold">Employee</label>
        <div class="position-relative">
          <input
            ref="assignSearchInput"
            type="text"
            class="form-control"
            v-model="assignSearch"
            @input="handleAssignSearch"
            placeholder="Search employee"
          />
          <div v-if="assignOptions.length" class="dropdown-menu show w-100 assign-dropdown">
            <button
              v-for="option in assignOptions"
              :key="option.value || option.id"
              type="button"
              class="dropdown-item d-flex align-items-center gap-2"
              @click="selectAssignee(option)"
            >
              <img
                v-if="option.avatar"
                :src="option.avatar"
                class="rounded-circle"
                style="width: 28px; height: 28px; object-fit: cover;"
              />
              <div>
                <div class="fw-semibold">{{ option.name }}</div>
                <div class="fs-11 text-muted">{{ option.position || '-' }}</div>
              </div>
            </button>
          </div>
        </div>
        <div v-if="assignErrors.user_ids" class="text-danger fs-12 mt-1">{{ assignErrors.user_ids }}</div>
      </div>

      <div v-if="assignSelected.length" class="alert alert-light border mb-0">
        <div class="d-flex align-items-center gap-2 mb-2">
          <i class="ri-group-line text-primary"></i>
          <div class="fw-semibold text-primary">Selected Personnel</div>
        </div>
        <div class="d-flex flex-wrap gap-2">
          <span
            v-for="person in assignSelected"
            :key="person.value || person.id"
            class="badge rounded-pill bg-primary-subtle text-primary d-flex align-items-center gap-1"
          >
            <span>{{ person.name }}</span>
            <button type="button" class="btn btn-sm p-0 text-primary" @click="removeAssignee(person)">
              <i class="ri-close-line"></i>
            </button>
          </span>
        </div>
      </div>

      <template #footer>
        <b-button @click="hideAssignModal" variant="light" block>Cancel</b-button>
        <b-button @click="submitAssign" variant="primary" :disabled="assignProcessing || !assignSelected.length" block>
          {{ assignProcessing ? 'Assigning...' : 'Assign' }}
        </b-button>
      </template>
    </b-modal>

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
      <template #footer>
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
      <template #footer>
        <b-button @click="showValidationFeedbackModal = false" variant="light" block>Close</b-button>
      </template>
    </b-modal>

    <b-modal v-model="showAttachmentUploadErrorModal" style="--vz-modal-width: 460px" header-class="p-3 bg-light" title="Upload Error" class="v-modal-custom" modal-class="zoomIn" centered no-close-on-backdrop>
      <div class="alert alert-danger mb-0">{{ attachmentUploadErrorMessage }}</div>
      <template #footer>
        <b-button @click="showAttachmentUploadErrorModal = false" variant="light" block>Close</b-button>
      </template>
    </b-modal>
  </div>
</template>

<script>
import { Head } from '@inertiajs/vue3';
import PageHeader from '@/Shared/Components/PageHeader.vue';
import axios from 'axios';
import RightSidebar from '../Components/RightSidebar.vue';
import DetailsTab from './Tabs/DetailsTab.vue';
import ProcessTab from './Tabs/ProcessTab.vue';
import AttachmentsTab from './Tabs/AttachmentsTab.vue';

export default {
  components: { Head, PageHeader, RightSidebar, DetailsTab, ProcessTab, AttachmentsTab },
  props: ['request', 'roles', 'logs'],
  data() {
    return {
      activeTab: 1,
      isLeftCollapsed: false,
      isRightCollapsed: true,
      isStatusFlowCollapsed: false,
      localAttachments: Array.isArray(this.request?.attachments) ? [...this.request.attachments] : [],
      localAssignedPersonnel: { ...(this.request?.assignees || this.request?.assigned_personnel || {}) },

      showAssignModal: false,
      assignForm: { status: '', user_ids: [] },
      assignSearch: '',
      assignOptions: [],
      assignSelected: [],
      assignProcessing: false,
      assignErrors: {},
      assignSearchTimer: null,

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
        { key: 1, label: 'Finance Details', icon: 'ri-information-line' },
        { key: 2, label: 'Process', icon: 'ri-route-line' },
        { key: 3, label: 'Attachments', icon: 'ri-attachment-2' },
      ],
    };
  },
  mounted() {
    const saved = Number(localStorage.getItem(this.getTabStorageKey()));
    const exists = this.navItems.some((item) => item.key === saved);
    if (exists) this.activeTab = saved;
  },
  computed: {
    leftSidebarClass() {
      return this.isLeftCollapsed ? 'col-md-1' : 'col-md-3';
    },
    mainContentClass() {
      if (this.isLeftCollapsed && this.isRightCollapsed) return 'col-md-10';
      if (this.isLeftCollapsed && !this.isRightCollapsed) return 'col-md-8';
      if (!this.isLeftCollapsed && this.isRightCollapsed) return 'col-md-8';
      return 'col-md-6';
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
      const steps = ['Pending', 'Verified', 'Validated', 'Allotment Available', 'Obligated', 'For Disbursement', 'Funds Available', 'Charged', 'Approved', 'Completed/LBP Processing'];
      const currentIndex = steps.findIndex((name) => name === currentStatus);
      return steps.map((name, index) => ({
        name,
        isCurrent: name === currentStatus,
        isPast: currentIndex !== -1 && index < currentIndex,
      }));
    },
  },
  methods: {
    show(tab) {
      this.activeTab = tab;
      localStorage.setItem(this.getTabStorageKey(), String(tab));
    },
    getTabStorageKey() {
      return `finance-overview-active-tab-${this.request?.id || 'default'}`;
    },
    toggleLeftSidebar() { this.isLeftCollapsed = !this.isLeftCollapsed; },
    toggleRightSidebar() { this.isRightCollapsed = !this.isRightCollapsed; },
    toggleStatusFlow() { this.isStatusFlowCollapsed = !this.isStatusFlowCollapsed; },

    formatFileSize(size) {
      if (!size && size !== 0) return '-';
      const kb = 1024;
      const mb = kb * 1024;
      if (size >= mb) return `${(size / mb).toFixed(2)} MB`;
      if (size >= kb) return `${(size / kb).toFixed(2)} KB`;
      return `${size} B`;
    },
    getAttachmentByDocumentId(documentId) {
      const targetDocumentId = Number(documentId);
      return (this.localAttachments || []).find((item) => Number(item?.finance_document_id) === targetDocumentId) || null;
    },
    getAttachmentPreviewUrl(attachment) {
      if (!attachment) return null;
      const candidates = [
        attachment.preview_url,
        attachment.url,
        attachment.file_url,
        attachment.download_url,
        attachment.path,
      ];
      for (const value of candidates) {
        const resolved = this.resolveAttachmentUrl(value);
        if (resolved) return resolved;
      }
      return null;
    },
    resolveAttachmentUrl(value) {
      if (!value) return null;
      const raw = String(value).trim();
      if (!raw) return null;
      if (/^https?:\/\//i.test(raw)) return raw;
      if (raw.startsWith('/storage/')) return raw;
      if (raw.startsWith('storage/')) return `/${raw}`;
      if (raw.startsWith('/')) return raw;
      return `/storage/${raw.replace(/^\/+/, '')}`;
    },

    openAssignModal(step) {
      this.assignForm.status = step?.name || '';
      this.assignForm.user_ids = [];
      this.assignSearch = '';
      this.assignOptions = [];
      this.assignSelected = [];
      this.assignErrors = {};
      this.showAssignModal = true;
      this.$nextTick(() => {
        if (this.$refs.assignSearchInput) this.$refs.assignSearchInput.focus();
      });
    },
    hideAssignModal() {
      this.showAssignModal = false;
    },
    handleAssignSearch() {
      if (this.assignSearchTimer) clearTimeout(this.assignSearchTimer);
      const term = (this.assignSearch || '').trim();
      if (term.length < 2) {
        this.assignOptions = [];
        return;
      }
      this.assignSearchTimer = setTimeout(() => {
        this.searchAssignUsers(term);
      }, 300);
    },
    searchAssignUsers(term) {
      axios.get('/search', {
        params: {
          keyword: term,
          option: 'users',
        },
      })
      .then((response) => {
        this.assignOptions = Array.isArray(response.data) ? response.data : [];
      })
      .catch(() => {
        this.assignOptions = [];
      });
    },
    selectAssignee(option) {
      const id = option?.value ?? option?.id ?? null;
      if (!id) return;
      const exists = this.assignSelected.some((p) => (p.value ?? p.id) === id);
      if (!exists) this.assignSelected.push(option);
      this.assignForm.user_ids = this.assignSelected.map((p) => p.value ?? p.id).filter(Boolean);
      this.assignSearch = option?.name || '';
      this.assignOptions = [];
      this.assignErrors.user_ids = null;
    },
    removeAssignee(person) {
      const id = person?.value ?? person?.id;
      this.assignSelected = this.assignSelected.filter((p) => (p.value ?? p.id) !== id);
      this.assignForm.user_ids = this.assignSelected.map((p) => p.value ?? p.id).filter(Boolean);
    },
    submitAssign() {
      this.assignErrors = {};
      if (!this.assignSelected.length) {
        this.assignErrors.user_ids = 'Employee is required.';
        return;
      }
      if (!this.assignForm.status) {
        this.assignErrors.status = 'Status is required.';
        return;
      }

      this.assignProcessing = true;
      axios.post('/faims/finance-requests-assignments', {
        finance_request_id: this.request.id,
        status: this.assignForm.status,
        user_ids: this.assignForm.user_ids,
      })
      .then(() => {
        const names = this.assignSelected.map((p) => p.name).filter(Boolean);
        const current = this.localAssignedPersonnel[this.assignForm.status];
        const merged = Array.isArray(current) ? current : current ? [current] : [];
        const updated = [...merged, ...names].filter((v, i, a) => a.indexOf(v) === i);
        this.localAssignedPersonnel[this.assignForm.status] = updated;
        this.hideAssignModal();
      })
      .catch((error) => {
        if (error?.response?.status === 422) this.assignErrors = error.response.data.errors || {};
      })
      .finally(() => {
        this.assignProcessing = false;
      });
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
      if (!this.request?.id) {
        this.attachmentErrors[documentId] = 'Finance request ID is missing. Please refresh and try again.';
        return;
      }
      this.attachmentProcessingId = documentId;
      this.attachmentErrors[documentId] = null;
      const formData = new FormData();
      formData.append('finance_document_id', documentId);
      formData.append('file', selected);
      axios.post(`/faims/finance-requests/${this.request.id}/attachments`, formData, { headers: { 'Content-Type': 'multipart/form-data' } })
        .then((response) => {
          const apiStatus = response?.data?.status;
          const uploadedCandidate =
            response?.data?.data?.data ||
            response?.data?.data ||
            response?.data?.attachment ||
            null;
          const uploaded = uploadedCandidate && typeof uploadedCandidate === 'object' ? uploadedCandidate : null;

          if (apiStatus === false || !uploaded?.id) {
            const backendMessage = response?.data?.info || response?.data?.message || 'Upload did not save to server.';
            throw new Error(backendMessage);
          }

          const existingIndex = this.localAttachments.findIndex((item) => Number(item.finance_document_id) === Number(documentId));
          const existing = existingIndex >= 0 ? this.localAttachments[existingIndex] : null;
          const normalized = {
            ...uploaded,
            id: uploaded.id,
            finance_document_id: Number(uploaded.finance_document_id || documentId),
            name: uploaded.name || selected.name,
            verification_status: uploaded.verification_status || 'pending',
            url: this.resolveAttachmentUrl(uploaded.url || uploaded.file_url || uploaded.path),
          };
          if (existingIndex >= 0) this.localAttachments.splice(existingIndex, 1, { ...existing, ...normalized });
          else this.localAttachments.push(normalized);

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
          } else {
            this.attachmentErrors[documentId] = error?.message || 'Upload did not complete. Please try again.';
          }
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
        .catch(() => {
          this.attachmentErrors[documentId] = 'Could not update verification status. Please try again.';
        });
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
.finance-left-nav-card {
  border-radius: 15px;
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  height: 100%;
}

.finance-left-nav-header {
  border-radius: 15px 15px 0 0 !important;
  background: #ffffff;
  padding: 1rem;
}

.finance-left-nav-body {
  border-radius: 0 0 15px 15px;
  min-height: 64vh;
}

.finance-left-nav-btn,
.finance-left-nav-icon-btn {
  transition: all 0.3s ease;
}

.finance-left-nav-icon-btn {
  width: 50px;
  height: 50px;
}

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

.assign-dropdown {
  max-height: 240px;
  overflow-y: auto;
  z-index: 1080;
}
</style>







