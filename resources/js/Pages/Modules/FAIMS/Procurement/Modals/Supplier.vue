<template>
  <div>
    <b-modal
      v-model="showModal"
      header-class="p-3 supplier-modal-header"
      :title="editable ? 'Update Supplier' : 'Add New Supplier'"
      size="xl"
      class="v-modal-custom"
      modal-class="zoomIn"
      centered
      no-close-on-backdrop
    >
      <form class="customform supplier-form" @submit.prevent="reviewSupplier">
        <b-alert
          v-if="form.errors.body || Object.keys(form.errors).length"
          variant="danger"
          show
          class="mb-3"
        >
          {{ form.errors.body || "Please review the highlighted supplier fields." }}
        </b-alert>

        <div class="border rounded-3 p-3 mb-4">
          <h5 class="d-flex align-items-center gap-2 mb-3">
            <i class="ri-building-line"></i>
            <span>Basic Information</span>
          </h5>
          <b-row class="g-3">
            <b-col lg="4">
              <div class="form-group">
                <InputLabel value="Company/Business Name *" class="fw-bold" />
                <TextInput
                  v-model="form.name"
                  type="text"
                  class="form-control-lg rounded-3"
                  placeholder="Enter company or business name"
                />
                <InputError :message="form.errors.name" class="mt-1" />
              </div>
            </b-col>

            <b-col lg="4">
              <div class="form-group">
                <InputLabel value="Supplier Code" class="fw-bold" />
                <TextInput
                  v-model="form.code"
                  type="text"
                  class="form-control-lg rounded-3"
                  :placeholder="editable ? 'Auto-generated code' : 'Code will be auto-generated'"
                  :disabled="!editable"
                />
                <InputError :message="form.errors.code" class="mt-1" />
              </div>
            </b-col>

            <b-col lg="4">
              <div class="form-group">
                <InputLabel value="TIN" class="fw-bold" />
                <TextInput
                  v-model="form.tin"
                  type="text"
                  class="form-control-lg rounded-3"
                  placeholder="000-000-000-000"
                  inputmode="numeric"
                  maxlength="15"
                />
                <InputError :message="form.errors.tin" class="mt-1" />
              </div>
            </b-col>
          </b-row>
        </div>

        <div class="border rounded-3 p-3 mb-4">
          <h5 class="d-flex align-items-center gap-2 mb-3">
            <i class="ri-map-pin-line"></i>
            <span>Address Information</span>
          </h5>
          <b-row class="g-3">
            <b-col lg="12">
              <div class="form-group">
                <InputLabel value="Complete Address" class="fw-bold" />
                <b-form-textarea
                  v-model="form.address"
                  class="rounded-3"
                  rows="3"
                  placeholder="Enter complete address including street, city, province, and postal code"
                />
                <InputError :message="form.errors.address" class="mt-1" />
              </div>
            </b-col>
          </b-row>
        </div>

        <div class="border rounded-3 p-3 mb-4">
          <h5 class="d-flex align-items-center gap-2 mb-3">
            <i class="ri-user-line"></i>
            <span>Representatives & Authorized Personnel</span>
          </h5>
          <div
            v-for="(conforme, index) in form.conformes"
            :key="index"
            class="bg-light-subtle border rounded-3 p-3 mb-3"
          >
            <b-row class="g-3 align-items-end">
              <b-col lg="4">
                <InputLabel :value="`Representative ${index + 1} Name`" class="fw-bold" />
                <TextInput
                  v-model="conforme.name"
                  type="text"
                  class="rounded-3"
                  :placeholder="`Enter representative ${index + 1} name`"
                />
              </b-col>

              <b-col lg="4">
                <InputLabel value="Position/Title" class="fw-bold" />
                <TextInput
                  v-model="conforme.position"
                  type="text"
                  class="rounded-3"
                  placeholder="Enter position or title"
                />
              </b-col>

              <b-col lg="3">
                <InputLabel value="Contact Number" class="fw-bold" />
                <TextInput
                  v-model="conforme.contact_no"
                  type="text"
                  class="rounded-3"
                  placeholder="09123456789"
                  pattern="^09\\d{9}$"
                  maxlength="11"
                  title="Contact number must start with 09 and be 11 digits"
                />
              </b-col>

              <b-col lg="1">
                <b-button
                  v-if="form.conformes.length > 1"
                  @click="removeConforme(index)"
                  type="button"
                  variant="outline-danger"
                  size="sm"
                  class="w-100"
                >
                  <i class="ri-delete-bin-line"></i>
                </b-button>
              </b-col>
            </b-row>
          </div>

          <b-button
            @click="addConforme"
            type="button"
            variant="outline-primary"
            size="sm"
          >
            <i class="ri-add-line me-1"></i>Add Representative
          </b-button>
        </div>

        <div class="border rounded-3 p-3 mb-4">
          <h5 class="d-flex align-items-center gap-2 mb-3">
            <i class="ri-shield-check-line"></i>
            <span>Required Supplier Documents</span>
          </h5>

          <b-alert variant="info" show class="mb-3">
            {{ required_documents_notice }}
          </b-alert>

          <div
            v-for="(attachment, index) in required_attachments"
            :key="attachment.document_type"
            class="bg-light-subtle border rounded-3 p-3 mb-3"
            :class="{ 'border-danger': attachmentError('required', index, 'file') || attachmentError('required', index, 'code') }"
          >
            <b-row class="g-3 align-items-end">
              <b-col lg="5">
                <InputLabel :value="`${attachment.document_type} *`" class="fw-bold" />
                <input
                  :id="attachmentInputId('required', index)"
                  type="file"
                  class="d-none"
                  accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                  @change="setAttachmentFile($event.target.files[0] || null, 'required', index)"
                />
                <div
                  class="supplier-required-upload"
                  :class="{
                    'is-filled': hasAttachmentFile(attachment),
                    'is-error': attachmentError('required', index, 'file'),
                  }"
                >
                  <div class="supplier-required-upload__content">
                    <span class="supplier-required-upload__icon">
                      <i :class="hasAttachmentFile(attachment) ? 'ri-file-check-line' : 'ri-upload-cloud-2-line'"></i>
                    </span>
                    <div class="supplier-required-upload__text">
                      <div
                        class="supplier-required-upload__title"
                        :title="hasAttachmentFile(attachment) ? resolvedAttachmentName(attachment) : ''"
                      >
                        {{ hasAttachmentFile(attachment) ? resolvedAttachmentName(attachment) : `Upload ${attachment.document_type}` }}
                      </div>
                      <div class="supplier-required-upload__hint">
                        {{ hasAttachmentFile(attachment)
                          ? "File attached. You can preview or replace it before saving."
                          : "Accepted: PDF, DOC, DOCX, JPG, JPEG, PNG" }}
                      </div>
                    </div>
                  </div>

                  <b-button
                    @click="openAttachmentPicker('required', index)"
                    type="button"
                    variant="outline-primary"
                    size="sm"
                    class="supplier-required-upload__button"
                  >
                    <i :class="hasAttachmentFile(attachment) ? 'ri-refresh-line me-1' : 'ri-upload-2-line me-1'"></i>
                    {{ hasAttachmentFile(attachment) ? "Replace" : "Choose File" }}
                  </b-button>
                </div>
                <InputError :message="attachmentError('required', index, 'file')" class="mt-1" />
              </b-col>

              <b-col lg="4">
                <InputLabel value="Reference No. / Permit No. *" class="fw-bold" />
                <TextInput
                  v-model="attachment.code"
                  type="text"
                  class="rounded-3"
                  placeholder="Enter document reference number"
                />
                <InputError :message="attachmentError('required', index, 'code')" class="mt-1" />
              </b-col>

              <b-col lg="3">
                <div class="supplier-required-meta">
                  <span
                    class="badge rounded-pill supplier-required-meta__badge"
                    :class="hasAttachmentFile(attachment) ? 'is-ready' : 'is-missing'"
                  >
                    {{ hasAttachmentFile(attachment) ? "Ready" : "Missing" }}
                  </span>

                  <small class="text-muted">
                    {{ hasAttachmentFile(attachment)
                      ? "Preview the uploaded document before submitting."
                      : "This document is required before approval." }}
                  </small>

                  <div class="d-flex gap-2 flex-wrap">
                    <b-button
                      v-if="hasAttachmentFile(attachment)"
                      @click="viewFile(attachment.file)"
                      type="button"
                      variant="outline-info"
                      size="sm"
                      class="supplier-required-meta__button"
                      v-b-tooltip.hover
                      title="View File"
                    >
                      <i class="ri-eye-line me-1"></i>View
                    </b-button>
                  </div>
                </div>
              </b-col>
            </b-row>
          </div>
        </div>

        <div class="border rounded-3 p-3 mb-4">
          <h5 class="d-flex align-items-center gap-2 mb-3">
            <i class="ri-attachment-line"></i>
            <span>Additional Supporting Attachments</span>
          </h5>

          <div
            v-for="(attachment, index) in supporting_attachments"
            :key="`supporting-${index}`"
            class="bg-light-subtle border rounded-3 p-3 mb-3"
          >
            <b-row class="g-3 align-items-end">
              <b-col lg="3">
                <InputLabel :value="`Document Name ${index + 1}`" class="fw-bold" />
                <TextInput
                  v-model="attachment.document_type"
                  type="text"
                  class="rounded-3"
                  placeholder="Enter document name"
                />
                <InputError :message="attachmentError('supporting', index, 'document_type')" class="mt-1" />
              </b-col>

              <b-col lg="4">
                <InputLabel :value="`Attachment ${index + 1} File`" class="fw-bold" />
                <b-form-file
                  :model-value="isNativeFile(attachment.file) ? attachment.file : null"
                  class="rounded-3"
                  @update:model-value="setAttachmentFile($event, 'supporting', index)"
                  accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                  placeholder="Choose a file"
                />
                <div v-if="hasAttachmentFile(attachment)" class="small text-success mt-2">
                  <i class="ri-check-line me-1"></i>{{ resolvedAttachmentName(attachment) }}
                </div>
                <InputError :message="attachmentError('supporting', index, 'file')" class="mt-1" />
              </b-col>

              <b-col lg="3">
                <InputLabel value="Reference No." class="fw-bold" />
                <TextInput
                  v-model="attachment.code"
                  type="text"
                  class="rounded-3"
                  placeholder="Enter document reference number"
                />
              </b-col>

              <b-col lg="2">
                <div class="d-flex gap-1 mb-1">
                  <b-button
                    v-if="hasAttachmentFile(attachment)"
                    @click="viewFile(attachment.file)"
                    type="button"
                    variant="outline-info"
                    size="sm"
                    v-b-tooltip.hover
                    title="View File"
                  >
                    <i class="ri-eye-line"></i>
                  </b-button>

                  <b-button
                    v-if="supporting_attachments.length > 1"
                    @click="removeSupportingAttachment(index)"
                    type="button"
                    variant="outline-danger"
                    size="sm"
                    v-b-tooltip.hover
                    title="Remove"
                  >
                    <i class="ri-delete-bin-line"></i>
                  </b-button>
                </div>
              </b-col>
            </b-row>
          </div>

          <b-button
            @click="addSupportingAttachment"
            type="button"
            variant="outline-primary"
            size="sm"
          >
            <i class="ri-add-line me-1"></i>Add Supporting Attachment
          </b-button>
        </div>

        <div class="border rounded-3 p-3">
          <h5 class="d-flex align-items-center gap-2 mb-3">
            <i class="ri-settings-line"></i>
            <span>Settings</span>
          </h5>
          <b-row class="g-3">
            <b-col lg="6">
              <b-form-checkbox v-model="form.is_active" switch class="fw-bold">
                Active Supplier
              </b-form-checkbox>
              <small class="text-muted d-block mt-1">
                Inactive suppliers won't be available for selection
              </small>
            </b-col>
          </b-row>
        </div>
      </form>

      <template #footer>
        <b-button @click="hide()" variant="light" block>Cancel</b-button>
        <b-button @click="reviewSupplier()" variant="primary" :disabled="isSaving" block>
          {{ reviewButtonLabel }}
        </b-button>
      </template>
    </b-modal>

    <b-modal
      v-model="showApprovalModal"
      style="--vz-modal-width: 600px"
      header-class="p-3 supplier-modal-header"
      :title="approval_modal_title"
      class="v-modal-custom"
      modal-class="zoomIn"
      centered
      no-close-on-backdrop
    >
      <form class="customform">
        <div class="m-5 text-center">
          <div>
            {{ approval_modal_message }}
          </div>

          <div class="text-muted small mt-3">
            {{ approval_next_step }}
          </div>
        </div>
      </form>
      <template v-slot:footer>
        <b-button
          type="button"
          variant="light"
          :disabled="isSaving"
          @click="showApprovalModal = false"
          block
        >
          Close
        </b-button>
        <b-button
          type="button"
          variant="primary"
          :disabled="isSaving || !canApproveSubmission"
          @click="submitSupplier()"
          block
        >
          <span v-if="isSaving">Processing...</span>
          <span v-else>{{ approveButtonLabel }}</span>
        </b-button>
      </template>
    </b-modal>
  </div>
</template>

<script>
import { useForm } from "@inertiajs/vue3";
import InputError from "@/Shared/Components/Forms/InputError.vue";
import InputLabel from "@/Shared/Components/Forms/InputLabel.vue";
import TextInput from "@/Shared/Components/Forms/TextInput.vue";

const REQUIRED_ATTACHMENT_TYPES = [
  { name: "Business Permit" },
  { name: "PhilGEPS Registration" },
];

const createConforme = () => ({
  name: null,
  position: null,
  contact_no: null,
});

const createAttachment = (documentType = null, isRequired = false) => ({
  id: null,
  file: null,
  code: null,
  document_type: documentType,
  isExisting: false,
  isRequired,
});

export default {
  components: { InputError, InputLabel, TextInput },
  props: ["dropdowns"],
  data() {
    return {
      currentUrl: window.location.origin,
      form: useForm({
        id: null,
        name: null,
        code: null,
        tin: null,
        address: null,
        conformes: [createConforme()],
        is_active: true,
      }),
      showModal: false,
      showApprovalModal: false,
      editable: false,
      required_attachments: REQUIRED_ATTACHMENT_TYPES.map((item) => createAttachment(item.name, true)),
      supporting_attachments: [createAttachment()],
      isSaving: false,
    };
  },
  computed: {
    current_roles() {
      return Array.isArray(this.$page?.props?.roles) ? this.$page.props.roles : [];
    },
    can_directly_approve_supplier() {
      return this.current_roles.some((role) => {
        return ["Procurement Officer", "Administrator"].includes(role);
      });
    },
    reviewButtonLabel() {
      if (this.editable) {
        return "Review Changes";
      }

      return this.can_directly_approve_supplier ? "Review & Approve" : "Review & Submit";
    },
    approveButtonLabel() {
      if (this.editable) {
        return "Save Changes";
      }

      return this.can_directly_approve_supplier ? "Approve Supplier" : "Submit Supplier";
    },
    approval_modal_title() {
      if (this.editable) {
        return "Update Supplier";
      }

      return this.can_directly_approve_supplier
        ? "Approve Supplier"
        : "Submit Supplier";
    },
    approval_modal_message() {
      if (this.editable) {
        return `Are you sure you want to update supplier "${this.form.name || "this supplier"}"?`;
      }

      return this.can_directly_approve_supplier
        ? `Are you sure you want to approve supplier "${this.form.name || "this supplier"}"?`
        : `Are you sure you want to submit supplier "${this.form.name || "this supplier"}" for approval?`;
    },
    approval_next_step() {
      if (this.editable) {
        return "The supplier details will be updated immediately after confirmation.";
      }

      return this.can_directly_approve_supplier
        ? "Once approved, the supplier will follow its current active or inactive setting."
        : "The supplier will stay pending until approved by a Procurement Officer.";
    },
    required_documents_notice() {
      return this.can_directly_approve_supplier
        ? "Business Permit and PhilGEPS Registration are required before the supplier information can be approved and saved."
        : "Business Permit and PhilGEPS Registration are required before the supplier can be submitted for Procurement Officer approval.";
    },
    totalRepresentatives() {
      return this.form.conformes.filter((conforme) => {
        return String(conforme?.name || "").trim() !== "";
      }).length;
    },
    supportingAttachmentCount() {
      return this.supporting_attachments.filter((attachment) => this.attachmentHasAnyContent(attachment)).length;
    },
    missingRequiredDocuments() {
      return this.required_attachments
        .filter((attachment) => !this.hasAttachmentFile(attachment))
        .map((attachment) => attachment.document_type);
    },
    canApproveSubmission() {
      return this.missingRequiredDocuments.length === 0;
    },
  },
  watch: {
    "form.tin"(value) {
      const formattedTin = this.formatTin(value);

      if (formattedTin !== value) {
        this.form.tin = formattedTin;
      }
    },
  },
  methods: {
    isNativeFile(value) {
      return typeof File !== "undefined" && value instanceof File;
    },

    normalizeTin(value) {
      return String(value || "")
        .replace(/\D/g, "")
        .slice(0, 12);
    },

    formatTin(value) {
      const digits = this.normalizeTin(value);

      if (!digits) {
        return null;
      }

      const segments = [];

      for (let index = 0; index < digits.length; index += 3) {
        segments.push(digits.slice(index, index + 3));
      }

      return segments.join("-");
    },

    show() {
      this.editable = false;
      this.resetFormState();
      this.showModal = true;
    },

    edit(data) {
      this.editable = true;
      this.resetFormState();

      const existingAttachments = Array.isArray(data?.attachments) ? data.attachments : [];

      this.form.id = data.id;
      this.form.name = data.name;
      this.form.code = data.code;
      this.form.tin = this.formatTin(data.tin || null);
      this.form.address = data.address;
      this.form.is_active = data.is_active == 1;
      this.form.conformes = data.conformes && data.conformes.length > 0
        ? data.conformes.map((conforme) => ({
            name: conforme.name || null,
            position: conforme.position || null,
            contact_no: conforme.contact_no || null,
          }))
        : [createConforme()];

      this.required_attachments = this.buildRequiredAttachments(existingAttachments);
      this.supporting_attachments = this.buildSupportingAttachments(existingAttachments);
      this.showModal = true;
    },

    hide() {
      this.resetFormState();
      this.showModal = false;
    },

    resetFormState() {
      this.form.reset();
      this.form.clearErrors();
      this.form.id = null;
      this.form.code = null;
      this.form.tin = null;
      this.form.address = null;
      this.form.conformes = [createConforme()];
      this.form.is_active = true;
      this.required_attachments = REQUIRED_ATTACHMENT_TYPES.map((item) => createAttachment(item.name, true));
      this.supporting_attachments = [createAttachment()];
      this.showApprovalModal = false;
      this.isSaving = false;
    },

    buildRequiredAttachments(existingAttachments = []) {
      return REQUIRED_ATTACHMENT_TYPES.map((requiredDocument) => {
        const existingAttachment = existingAttachments.find((attachment) => {
          return this.attachmentMatchesDocument(attachment, requiredDocument.name);
        });

        if (!existingAttachment) {
          return createAttachment(requiredDocument.name, true);
        }

        return this.mapExistingAttachment(existingAttachment, requiredDocument.name, true);
      });
    },

    buildSupportingAttachments(existingAttachments = []) {
      const supportingAttachments = existingAttachments
        .filter((attachment) => {
          return !REQUIRED_ATTACHMENT_TYPES.some((requiredDocument) => {
            return this.attachmentMatchesDocument(attachment, requiredDocument.name);
          });
        })
        .map((attachment) => {
          return this.mapExistingAttachment(
            attachment,
            this.inferAttachmentLabel(attachment),
            false,
          );
        });

      return supportingAttachments.length ? supportingAttachments : [createAttachment()];
    },

    mapExistingAttachment(attachment, documentType = null, isRequired = false) {
      return {
        id: attachment.id,
        file: attachment,
        code: attachment.code || null,
        document_type: documentType || attachment.document_type || null,
        isExisting: true,
        isRequired,
      };
    },

    normalizeDocumentType(value) {
      return String(value || "")
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, " ")
        .trim();
    },

    attachmentMatchesDocument(attachment, documentType) {
      const normalizedTarget = this.normalizeDocumentType(documentType);
      const haystacks = [
        attachment?.document_type,
        attachment?.code,
        attachment?.name,
      ]
        .map((value) => this.normalizeDocumentType(value))
        .filter(Boolean);

      if (!haystacks.length) {
        return false;
      }

      if (normalizedTarget === "business permit") {
        return haystacks.some((value) => {
          return value.includes("business permit")
            || (value.includes("business") && value.includes("permit"));
        });
      }

      if (normalizedTarget === "philgeps registration") {
        return haystacks.some((value) => value.includes("philgeps"));
      }

      return haystacks.some((value) => {
        return value === normalizedTarget || value.includes(normalizedTarget);
      });
    },

    inferAttachmentLabel(attachment) {
      if (this.attachmentMatchesDocument(attachment, "Business Permit")) {
        return "Business Permit";
      }

      if (this.attachmentMatchesDocument(attachment, "PhilGEPS Registration")) {
        return "PhilGEPS Registration";
      }

      return attachment?.document_type || attachment?.name || "Supporting Document";
    },

    attachmentError(group, index, field) {
      const absoluteIndex = group === "required"
        ? index
        : REQUIRED_ATTACHMENT_TYPES.length + index;

      return this.form.errors[`attachment_rows.${absoluteIndex}.${field}`] || null;
    },

    addConforme() {
      this.form.conformes.push(createConforme());
    },

    removeConforme(index) {
      if (this.form.conformes.length > 1) {
        this.form.conformes.splice(index, 1);
      }
    },

    addSupportingAttachment() {
      this.supporting_attachments.push(createAttachment());
    },

    removeSupportingAttachment(index) {
      if (this.supporting_attachments.length > 1) {
        this.supporting_attachments.splice(index, 1);
        return;
      }

      this.supporting_attachments = [createAttachment()];
    },

    attachmentInputId(group, index) {
      return `supplier-${group}-attachment-${index}`;
    },

    openAttachmentPicker(group, index) {
      const input = document.getElementById(this.attachmentInputId(group, index));

      if (!input) {
        return;
      }

      input.value = "";
      input.click();
    },

    setAttachmentFile(file, group, index) {
      const collection = group === "required"
        ? this.required_attachments
        : this.supporting_attachments;

      collection[index].file = Array.isArray(file) ? (file[0] || null) : file;
    },

    hasAttachmentFile(attachment) {
      return this.isNativeFile(attachment?.file) || Boolean(attachment?.file?.path);
    },

    attachmentHasAnyContent(attachment) {
      return Boolean(
        attachment?.id
        || this.isNativeFile(attachment?.file)
        || attachment?.file?.path
        || String(attachment?.document_type || "").trim()
        || String(attachment?.code || "").trim()
      );
    },

    resolvedAttachmentName(attachment) {
      if (this.isNativeFile(attachment?.file)) {
        return attachment.file.name;
      }

      return attachment?.file?.name || "Attached file";
    },

    viewFile(file) {
      if (this.isNativeFile(file)) {
        const url = URL.createObjectURL(file);
        window.open(url, "_blank");
        return;
      }

      if (file?.path) {
        window.open(`${this.currentUrl}/storage/${file.path}`, "_blank");
      }
    },

    reviewSupplier() {
      this.form.clearErrors();

      if (!this.validateBeforeReview()) {
        return;
      }

      this.showApprovalModal = true;
    },

    validateBeforeReview() {
      let hasError = false;

      if (!String(this.form.name || "").trim()) {
        this.form.setError("name", "This field is required");
        hasError = true;
      }

      this.required_attachments.forEach((attachment, index) => {
        if (!this.hasAttachmentFile(attachment)) {
          this.form.setError(
            `attachment_rows.${index}.file`,
            `${attachment.document_type} is required.`,
          );
          hasError = true;
        }

        if (!String(attachment.code || "").trim()) {
          this.form.setError(
            `attachment_rows.${index}.code`,
            `Please enter the reference number for ${attachment.document_type}.`,
          );
          hasError = true;
        }
      });

      this.supporting_attachments.forEach((attachment, index) => {
        const absoluteIndex = REQUIRED_ATTACHMENT_TYPES.length + index;

        if (!this.attachmentHasAnyContent(attachment)) {
          return;
        }

        if (!String(attachment.document_type || "").trim()) {
          this.form.setError(
            `attachment_rows.${absoluteIndex}.document_type`,
            "Please enter the document name.",
          );
          hasError = true;
        }

        if (!attachment.id && !this.isNativeFile(attachment.file) && !attachment.file?.path) {
          this.form.setError(
            `attachment_rows.${absoluteIndex}.file`,
            "Please upload the attachment file.",
          );
          hasError = true;
        }
      });

      if (this.missingRequiredDocuments.length) {
        this.form.setError(
          "body",
          this.can_directly_approve_supplier
            ? `${this.missingRequiredDocuments.join(" and ")} ${this.missingRequiredDocuments.length > 1 ? "are" : "is"} required before approval.`
            : `${this.missingRequiredDocuments.join(" and ")} ${this.missingRequiredDocuments.length > 1 ? "are" : "is"} required before submission for Procurement Officer approval.`,
        );
      }

      return !hasError;
    },

    submitSupplier() {
      this.form.clearErrors();
      this.isSaving = true;

      const formData = new FormData();

      formData.append("name", this.form.name || "");
      formData.append("code", this.form.code || "");
      formData.append("tin", this.formatTin(this.form.tin) || "");
      formData.append("address", this.form.address || "");
      formData.append("is_active", this.form.is_active ? "1" : "0");

      this.form.conformes.forEach((conforme, index) => {
        if (!conforme.name) {
          return;
        }

        formData.append(`conformes[${index}][name]`, conforme.name);
        formData.append(`conformes[${index}][position]`, conforme.position || "");
        formData.append(`conformes[${index}][contact_no]`, conforme.contact_no || "");
      });

      const attachmentRows = [...this.required_attachments, ...this.supporting_attachments];

      attachmentRows.forEach((attachment, index) => {
        formData.append(`attachment_rows[${index}][document_type]`, attachment.document_type || "");
        formData.append(`attachment_rows[${index}][code]`, attachment.code || "");

        if (attachment.id) {
          formData.append(`attachment_rows[${index}][id]`, attachment.id);
        }

        if (this.isNativeFile(attachment.file)) {
          formData.append(`attachment_rows[${index}][file]`, attachment.file);
        }
      });

      if (this.editable) {
        formData.append("_method", "PUT");
      }

      const request = this.editable
        ? axios.post(`/faims/suppliers/${this.form.id}`, formData, {
            headers: {
              "Content-Type": "multipart/form-data",
            },
          })
        : axios.post("/faims/suppliers", formData, {
            headers: {
              "Content-Type": "multipart/form-data",
            },
          });

      request
        .then(() => {
          this.$emit(this.editable ? "update" : "add", true);
          this.hide();
        })
        .catch((error) => {
          if (error.response?.status === 422 && error.response.data?.errors) {
            this.form.setError(error.response.data.errors);
            return;
          }

          this.form.setError(
            "body",
            error.response?.data?.info || `Unable to ${this.editable ? "update" : "create"} the supplier right now.`,
          );
          console.error(`Error ${this.editable ? "updating" : "creating"} supplier:`, error);
        })
        .finally(() => {
          this.isSaving = false;
        });
    },
  },
};
</script>

<style scoped>
.supplier-form {
  --supplier-modal-surface: #ffffff;
  --supplier-modal-surface-soft: #f8fafc;
  --supplier-modal-border: #e2e8f0;
  --supplier-modal-text: #1e293b;
  --supplier-modal-muted: #64748b;
  max-height: 70vh;
  overflow-y: auto;
}

.supplier-form .border,
.supplier-form .bg-light-subtle {
  background: var(--supplier-modal-surface-soft) !important;
  border-color: var(--supplier-modal-border) !important;
  color: var(--supplier-modal-text);
}

.supplier-form :deep(.form-control),
.supplier-form :deep(.form-select),
.supplier-form :deep(textarea) {
  background: var(--supplier-modal-surface) !important;
  border-color: var(--supplier-modal-border) !important;
  color: var(--supplier-modal-text) !important;
}

.form-group {
  margin-bottom: 1rem;
}

.supplier-required-upload {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  min-height: 88px;
  padding: 0.9rem 1rem;
  border: 1px dashed #cbd5e1;
  border-radius: 1rem;
  background: var(--supplier-modal-surface);
  transition: border-color 0.2s ease, background-color 0.2s ease, box-shadow 0.2s ease;
}

.supplier-required-upload.is-filled {
  border-style: solid;
  border-color: #a7f3d0;
  background: linear-gradient(180deg, var(--supplier-modal-surface) 0%, rgba(16, 185, 129, 0.12) 100%);
}

.supplier-required-upload.is-error {
  border-style: solid;
  border-color: #fda4af;
  background: rgba(244, 63, 94, 0.12);
}

.supplier-required-upload__content {
  display: flex;
  align-items: center;
  gap: 0.85rem;
  min-width: 0;
  flex: 1;
}

.supplier-required-upload__icon {
  width: 2.75rem;
  height: 2.75rem;
  border-radius: 0.9rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  color: #2563eb;
  background: #dbeafe;
  font-size: 1.1rem;
}

.supplier-required-upload.is-filled .supplier-required-upload__icon {
  color: #047857;
  background: #d1fae5;
}

.supplier-required-upload__text {
  min-width: 0;
}

.supplier-required-upload__title {
  font-weight: 600;
  color: var(--supplier-modal-text);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.supplier-required-upload__hint {
  margin-top: 0.2rem;
  font-size: 0.8rem;
  line-height: 1.4;
  color: var(--supplier-modal-muted);
}

.supplier-required-upload__button,
.supplier-required-meta__button {
  border-radius: 999px;
}

.supplier-required-meta {
  display: flex;
  flex-direction: column;
  justify-content: center;
  gap: 0.65rem;
  min-height: 100%;
}

.supplier-required-meta__badge {
  align-self: flex-start;
  border: 1px solid transparent;
}

.supplier-required-meta__badge.is-ready {
  color: #166534;
  background: #dcfce7;
  border-color: #bbf7d0;
}

.supplier-required-meta__badge.is-missing {
  color: #991b1b;
  background: #fee2e2;
  border-color: #fecaca;
}

:global([data-bs-theme="dark"]) .supplier-form {
  --supplier-modal-surface: #131d2b;
  --supplier-modal-surface-soft: #182235;
  --supplier-modal-border: rgba(148, 163, 184, 0.18);
  --supplier-modal-text: #e5edf7;
  --supplier-modal-muted: #9fb0c7;
  color: var(--supplier-modal-text);
}

:global([data-bs-theme="dark"]) .supplier-modal-header,
:global([data-bs-theme="dark"]) .v-modal-custom .modal-header {
  background: #182235 !important;
  border-color: rgba(148, 163, 184, 0.18) !important;
  color: #e5edf7 !important;
}

:global([data-bs-theme="dark"]) .v-modal-custom .modal-content,
:global([data-bs-theme="dark"]) .v-modal-custom .modal-body,
:global([data-bs-theme="dark"]) .v-modal-custom .modal-footer {
  background: #131d2b !important;
  border-color: rgba(148, 163, 184, 0.18) !important;
  color: #e5edf7 !important;
}

:global([data-bs-theme="dark"]) .v-modal-custom .bg-light-subtle,
:global([data-bs-theme="dark"]) .v-modal-custom .border {
  background: #182235 !important;
  border-color: rgba(148, 163, 184, 0.18) !important;
}

:global([data-bs-theme="dark"]) .v-modal-custom .text-muted {
  color: #9fb0c7 !important;
}

:global([data-bs-theme="dark"]) .supplier-required-upload__icon {
  background: rgba(96, 165, 250, 0.16);
}

:global([data-bs-theme="dark"]) .supplier-required-upload.is-filled .supplier-required-upload__icon {
  background: rgba(16, 185, 129, 0.18);
}

@media (max-width: 991.98px) {
  .supplier-required-upload {
    flex-direction: column;
    align-items: stretch;
  }

  .supplier-required-upload__button {
    width: 100%;
  }
}
</style>
