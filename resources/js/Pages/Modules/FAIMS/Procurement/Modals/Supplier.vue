<template>
  <div>
    <b-modal
      v-model="showModal"
      header-class="p-4 bg-gradient-primary text-white"
      :title="editable ? 'Update Supplier' : 'Add New Supplier'"
      size="xl"
      class="v-modal-custom"
      modal-class="zoomIn"
      centered
      no-close-on-backdrop
      body-class="p-4"
    >
      <div class="supplier-form">
        <div
          v-if="form.errors.body || Object.keys(form.errors).length"
          class="alert alert-danger py-2 px-3 mb-4"
        >
          {{ form.errors.body || "Please review the highlighted supplier fields." }}
        </div>

        <div class="form-section mb-4">
          <h5 class="section-title mb-3">
            <i class="ri-building-line me-2"></i>Basic Information
          </h5>
          <b-row class="g-3">
            <b-col lg="4">
              <div class="form-group">
                <InputLabel value="Company/Business Name *" class="fw-bold" />
                <TextInput
                  v-model="form.name"
                  type="text"
                  class="form-control form-control-lg"
                  placeholder="Enter company or business name"
                  style="border-radius: 10px; border: 2px solid #e9ecef"
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
                  class="form-control form-control-lg"
                  :placeholder="editable ? 'Auto-generated code' : 'Code will be auto-generated'"
                  :disabled="!editable"
                  style="border-radius: 10px; border: 2px solid #e9ecef"
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
                  class="form-control form-control-lg"
                  placeholder="Enter supplier TIN"
                  style="border-radius: 10px; border: 2px solid #e9ecef"
                />
                <InputError :message="form.errors.tin" class="mt-1" />
              </div>
            </b-col>
          </b-row>
        </div>

        <div class="form-section mb-4">
          <h5 class="section-title mb-3">
            <i class="ri-map-pin-line me-2"></i>Address Information
          </h5>
          <b-row class="g-3">
            <b-col lg="12">
              <div class="form-group">
                <InputLabel value="Complete Address" class="fw-bold" />
                <textarea
                  v-model="form.address"
                  class="form-control form-control-lg"
                  rows="3"
                  placeholder="Enter complete address including street, city, province, and postal code"
                  style="border-radius: 10px; border: 2px solid #e9ecef; resize: vertical"
                ></textarea>
                <InputError :message="form.errors.address" class="mt-1" />
              </div>
            </b-col>
          </b-row>
        </div>

        <div class="form-section mb-4">
          <h5 class="section-title mb-3">
            <i class="ri-user-line me-2"></i>Representatives & Authorized Personnel
          </h5>
          <div
            v-for="(conforme, index) in form.conformes"
            :key="index"
            class="conforme-item mb-3 p-3 border rounded"
            style="background: #f8f9fa; border-radius: 10px"
          >
            <b-row class="g-3 align-items-end">
              <b-col lg="4">
                <InputLabel :value="`Representative ${index + 1} Name`" class="fw-bold" />
                <TextInput
                  v-model="conforme.name"
                  type="text"
                  class="form-control"
                  :placeholder="`Enter representative ${index + 1} name`"
                  style="border-radius: 8px; border: 1px solid #ced4da"
                />
              </b-col>

              <b-col lg="4">
                <InputLabel value="Position/Title" class="fw-bold" />
                <TextInput
                  v-model="conforme.position"
                  type="text"
                  class="form-control"
                  placeholder="Enter position or title"
                  style="border-radius: 8px; border: 1px solid #ced4da"
                />
              </b-col>

              <b-col lg="3">
                <InputLabel value="Contact Number" class="fw-bold" />
                <TextInput
                  v-model="conforme.contact_no"
                  type="text"
                  class="form-control"
                  placeholder="09123456789"
                  pattern="^09\\d{9}$"
                  maxlength="11"
                  title="Contact number must start with 09 and be 11 digits"
                  style="border-radius: 8px; border: 1px solid #ced4da"
                />
              </b-col>

              <b-col lg="1">
                <b-button
                  v-if="form.conformes.length > 1"
                  @click="removeConforme(index)"
                  variant="outline-danger"
                  size="sm"
                  class="w-100"
                  style="border-radius: 8px"
                >
                  <i class="ri-delete-bin-line"></i>
                </b-button>
              </b-col>
            </b-row>
          </div>

          <b-button
            @click="addConforme"
            variant="outline-primary"
            size="sm"
            style="border-radius: 8px"
          >
            <i class="ri-add-line me-1"></i>Add Representative
          </b-button>
        </div>

        <div class="form-section mb-4">
          <h5 class="section-title mb-3">
            <i class="ri-shield-check-line me-2"></i>Required Supplier Documents
          </h5>

          <div class="alert alert-info py-2 px-3 mb-3">
            {{ required_documents_notice }}
          </div>

          <div
            v-for="(attachment, index) in required_attachments"
            :key="attachment.document_type"
            class="attachment-item mb-3 p-3 border rounded"
            :class="{ 'border-danger': attachmentError('required', index, 'file') }"
            style="background: #f8f9fa; border-radius: 10px"
          >
            <b-row class="g-3 align-items-end">
              <b-col lg="4">
                <InputLabel :value="`${attachment.document_type} *`" class="fw-bold" />
                <input
                  type="file"
                  class="form-control"
                  @change="handleAttachmentFileUpload($event, 'required', index)"
                  accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                  style="border-radius: 8px; border: 1px solid #ced4da"
                />
                <div v-if="hasAttachmentFile(attachment)" class="small text-success mt-2">
                  <i class="ri-check-line me-1"></i>{{ resolvedAttachmentName(attachment) }}
                </div>
                <InputError :message="attachmentError('required', index, 'file')" class="mt-1" />
              </b-col>

              <b-col lg="5">
                <InputLabel value="Reference No. / Permit No." class="fw-bold" />
                <TextInput
                  v-model="attachment.code"
                  type="text"
                  class="form-control"
                  placeholder="Enter document reference number"
                  style="border-radius: 8px; border: 1px solid #ced4da"
                />
              </b-col>

              <b-col lg="3">
                <div class="d-flex align-items-center justify-content-between gap-2">
                  <span class="badge rounded-pill" :class="hasAttachmentFile(attachment) ? 'bg-success' : 'bg-danger'">
                    {{ hasAttachmentFile(attachment) ? "Ready" : "Missing" }}
                  </span>

                  <b-button
                    v-if="hasAttachmentFile(attachment)"
                    @click="viewFile(attachment.file)"
                    variant="outline-info"
                    size="sm"
                    v-b-tooltip.hover
                    title="View File"
                    style="border-radius: 8px"
                  >
                    <i class="ri-eye-line"></i>
                  </b-button>
                </div>
              </b-col>
            </b-row>
          </div>
        </div>

        <div class="form-section mb-4">
          <h5 class="section-title mb-3">
            <i class="ri-attachment-line me-2"></i>Additional Supporting Attachments
          </h5>

          <div
            v-for="(attachment, index) in supporting_attachments"
            :key="`supporting-${index}`"
            class="attachment-item mb-3 p-3 border rounded"
            style="background: #f8f9fa; border-radius: 10px"
          >
            <b-row class="g-3 align-items-end">
              <b-col lg="3">
                <InputLabel :value="`Document Name ${index + 1}`" class="fw-bold" />
                <TextInput
                  v-model="attachment.document_type"
                  type="text"
                  class="form-control"
                  placeholder="Enter document name"
                  style="border-radius: 8px; border: 1px solid #ced4da"
                />
                <InputError :message="attachmentError('supporting', index, 'document_type')" class="mt-1" />
              </b-col>

              <b-col lg="4">
                <InputLabel :value="`Attachment ${index + 1} File`" class="fw-bold" />
                <input
                  type="file"
                  class="form-control"
                  @change="handleAttachmentFileUpload($event, 'supporting', index)"
                  accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                  style="border-radius: 8px; border: 1px solid #ced4da"
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
                  class="form-control"
                  placeholder="Enter document reference number"
                  style="border-radius: 8px; border: 1px solid #ced4da"
                />
              </b-col>

              <b-col lg="2">
                <div class="d-flex gap-1" style="margin-bottom: 5px">
                  <b-button
                    v-if="hasAttachmentFile(attachment)"
                    @click="viewFile(attachment.file)"
                    variant="outline-info"
                    size="sm"
                    v-b-tooltip.hover
                    title="View File"
                    style="border-radius: 8px"
                  >
                    <i class="ri-eye-line"></i>
                  </b-button>

                  <b-button
                    v-if="supporting_attachments.length > 1"
                    @click="removeSupportingAttachment(index)"
                    variant="outline-danger"
                    size="sm"
                    v-b-tooltip.hover
                    title="Remove"
                    style="border-radius: 8px"
                  >
                    <i class="ri-delete-bin-line"></i>
                  </b-button>
                </div>
              </b-col>
            </b-row>
          </div>

          <b-button
            @click="addSupportingAttachment"
            variant="outline-primary"
            size="sm"
            style="border-radius: 8px"
          >
            <i class="ri-add-line me-1"></i>Add Supporting Attachment
          </b-button>
        </div>

        <div class="form-section mb-4">
          <h5 class="section-title mb-3"><i class="ri-settings-line me-2"></i>Settings</h5>
          <b-row class="g-3">
            <b-col lg="6">
              <div class="form-check form-switch">
                <input
                  class="form-check-input"
                  type="checkbox"
                  id="is_active"
                  v-model="form.is_active"
                  style="width: 3rem; height: 1.5rem"
                />
                <label class="form-check-label fw-bold ms-2" for="is_active">
                  Active Supplier
                </label>
                <small class="form-text text-muted d-block">
                  Inactive suppliers won't be available for selection
                </small>
              </div>
            </b-col>
          </b-row>
        </div>
      </div>

      <template #footer>
        <div class="d-flex justify-content-end gap-2">
          <b-button
            @click="hide()"
            variant="light"
            style="border-radius: 8px; padding: 0.5rem 1.5rem"
          >
            <i class="ri-close-line me-1"></i>Cancel
          </b-button>

          <b-button
            @click="reviewSupplier()"
            variant="success"
            :disabled="isSaving"
            style="border-radius: 8px; padding: 0.5rem 1.5rem; box-shadow: 0 4px 15px rgba(25, 135, 84, 0.3)"
          >
            <i class="ri-shield-check-line me-1"></i>
            {{ reviewButtonLabel }}
          </b-button>
        </div>
      </template>
    </b-modal>

    <b-modal
      v-model="showApprovalModal"
      style="--vz-modal-width: 600px"
      header-class="p-3 bg-light"
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
  methods: {
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
      this.form.tin = data.tin || null;
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

    handleAttachmentFileUpload(event, group, index) {
      const file = event.target.files[0];

      if (!file) {
        return;
      }

      const collection = group === "required"
        ? this.required_attachments
        : this.supporting_attachments;

      collection[index].file = file;
    },

    hasAttachmentFile(attachment) {
      return attachment?.file instanceof File || Boolean(attachment?.file?.path);
    },

    attachmentHasAnyContent(attachment) {
      return Boolean(
        attachment?.id
        || attachment?.file instanceof File
        || attachment?.file?.path
        || String(attachment?.document_type || "").trim()
        || String(attachment?.code || "").trim()
      );
    },

    resolvedAttachmentName(attachment) {
      if (attachment?.file instanceof File) {
        return attachment.file.name;
      }

      return attachment?.file?.name || "Attached file";
    },

    viewFile(file) {
      if (file instanceof File) {
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
        if (this.hasAttachmentFile(attachment)) {
          return;
        }

        this.form.setError(
          `attachment_rows.${index}.file`,
          `${attachment.document_type} is required.`,
        );
        hasError = true;
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

        if (!attachment.id && !(attachment.file instanceof File) && !attachment.file?.path) {
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
      formData.append("tin", this.form.tin || "");
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

        if (attachment.file instanceof File) {
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
  max-height: 70vh;
  overflow-y: auto;
}

.section-title {
  color: #495057;
  border-bottom: 2px solid #e9ecef;
  padding-bottom: 0.5rem;
}

.form-group {
  margin-bottom: 1rem;
}

.attachment-item,
.conforme-item {
  transition: all 0.2s ease;
}

.attachment-item:hover,
.conforme-item:hover {
  background: #e9ecef;
}

.form-check-input:checked {
  background-color: #007bff;
  border-color: #007bff;
}

.bg-gradient-primary {
  background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
}
</style>
