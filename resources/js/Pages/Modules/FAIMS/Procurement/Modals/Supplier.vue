<template>
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
      <!-- Basic Information -->
      <div class="form-section mb-4">
        <h5 class="section-title mb-3">
          <i class="ri-building-line me-2"></i>Basic Information
        </h5>
        <b-row class="g-3">
          <b-col lg="6">
            <div class="form-group">
              <InputLabel value="Company/Business Name *" class="fw-bold" />
              <TextInput
                v-model="form.name"
                type="text"
                class="form-control form-control-lg"
                placeholder="Enter company or business name"
                style="border-radius: 10px; border: 2px solid #e9ecef"
              />
            </div>
          </b-col>
          <b-col lg="6">
            <div class="form-group">
              <InputLabel value="Supplier Code" class="fw-bold" />
              <TextInput
                v-model="form.code"
                type="text"
                class="form-control form-control-lg"
                :placeholder="
                  editable ? 'Auto-generated code' : 'Code will be auto-generated'
                "
                :disabled="!editable"
                style="border-radius: 10px; border: 2px solid #e9ecef"
              />
            </div>
          </b-col>
        </b-row>
      </div>

      <!-- Address Information -->
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
            </div>
          </b-col>
        </b-row>
      </div>

      <!-- Representatives/Conformes -->
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
                pattern="^09\d{9}$"
                maxlength="11"
                title="Contact number must start with 09 and be 11 digits"
                style="border-radius: 8px; border: 1px solid #ced4da"
              />
            </b-col>
            <b-col lg="1">
              <b-button
                @click="removeConforme(index)"
                variant="outline-danger"
                size="sm"
                class="w-100"
                style="border-radius: 8px"
                v-if="form.conformes.length > 1"
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

      <!-- Attachments -->
      <div class="form-section mb-4">
        <h5 class="section-title mb-3">
          <i class="ri-attachment-line me-2"></i>Attachments & Documents
        </h5>
        <div
          v-for="(attachment, index) in attachments"
          :key="index"
          class="attachment-item mb-3 p-3 border rounded"
          style="background: #f8f9fa; border-radius: 10px"
        >
          <b-row class="g-3 align-items-end">
            <b-col lg="4">
              <InputLabel :value="`Attachment ${index + 1} File`" class="fw-bold" />
              <input
                type="file"
                class="form-control"
                @change="handleFileUpload($event, index)"
                accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                style="border-radius: 8px; border: 1px solid #ced4da"
              />
            </b-col>
            <b-col lg="4">
              <InputLabel value="Code" class="fw-bold" />
              <TextInput
                v-model="attachment.code"
                type="text"
                class="form-control"
                placeholder="Enter code (e.g., business permit number)"
                style="border-radius: 8px; border: 1px solid #ced4da"
              />
            </b-col>
            <b-col lg="2">
              <InputLabel value="Type" class="fw-bold" />
              <Multiselect
                v-model="attachment.type_id"
                :options="dropdowns.attachment_types"
                placeholder="Select type"
                :searchable="true"
                :can-clear="true"
                label="name"
                track-by="value"
                style="border-radius: 8px"
              />
            </b-col>
            <b-col lg="2">
              <div class="d-flex gap-1" style="margin-bottom: 5px;">
                <b-button
                  v-if="attachment.file"
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
                  @click="removeAttachment(index)"
                  variant="outline-danger"
                  size="sm"
                  v-b-tooltip.hover
                  title="Remove"
                  style="border-radius: 8px"
                  v-if="attachments.length > 1"
                >
                  <i class="ri-delete-bin-line"></i>
                </b-button>
              </div>
            </b-col>
          </b-row>
        </div>
        <b-button
          @click="addAttachment"
          variant="outline-primary"
          size="sm"
          style="border-radius: 8px"
        >
          <i class="ri-add-line me-1"></i>Add Attachment
        </b-button>
      </div>

      <!-- Status -->
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
              <small class="form-text text-muted d-block"
                >Inactive suppliers won't be available for selection</small
              >
            </div>
          </b-col>
        </b-row>
      </div>
    </div>

    <template v-slot:footer>
      <div class="d-flex justify-content-end gap-2">
        <b-button
          @click="hide()"
          variant="light"
          style="border-radius: 8px; padding: 0.5rem 1.5rem"
        >
          <i class="ri-close-line me-1"></i>Cancel
        </b-button>
        <b-button
          @click="saveSupplier()"
          variant="success"
          :disabled="form.processing"
          style="
            border-radius: 8px;
            padding: 0.5rem 1.5rem;
            box-shadow: 0 4px 15px rgba(25, 135, 84, 0.3);
          "
        >
          <i class="ri-save-line me-1"></i>
          {{
            form.processing
              ? "Saving..."
              : editable
              ? "Update Supplier"
              : "Create Supplier"
          }}
        </b-button>
      </div>
    </template>
  </b-modal>
</template>
<script>
import { useForm } from "@inertiajs/vue3";
import Multiselect from "@vueform/multiselect";
import InputError from "@/Shared/Components/Forms/InputError.vue";
import InputLabel from "@/Shared/Components/Forms/InputLabel.vue";
import TextInput from "@/Shared/Components/Forms/TextInput.vue";

export default {
  components: { InputError, InputLabel, TextInput, Multiselect },
  props: ["dropdowns"],
  data() {
    return {
      currentUrl: window.location.origin,
      form: useForm({
        id: null,
        name: null,
        code: null,
        address: null,
        conformes: [{ name: null, position: null, contact_no: null }],
        is_active: true,
      }),
      showModal: false,
      editable: false,
      attachments: [{ file: null, code: null, type_id: null }],
    };
  },

  methods: {
    show() {
      this.editable = false;
      this.form.reset();
      this.form.conformes = [{ name: null, position: null, contact_no: null }];
      this.form.is_active = true;
      this.attachments = [{ file: null, code: null, type_id: null, isExisting: false }];
      this.showModal = true;
    },

    edit(data) {
      this.editable = true;
      this.form.id = data.id;
      this.form.name = data.name;
      this.form.code = data.code;
      this.form.address = data.address;
      this.form.is_active = data.is_active == 1;
      this.form.conformes =
        data.conformes && data.conformes.length > 0
          ? data.conformes.map((c) => ({ ...c, contact_no: c.contact_no || null }))
          : [{ name: null, position: null, contact_no: null }];
      this.uploadedFiles = data.attachments || [];
      this.attachments = data.attachments && data.attachments.length > 0
        ? data.attachments.map((a) => ({
            id: a.id, // Add id for existing attachments
            file: a, // Keep the existing file object for viewing
            code: a.code || null,
            type_id: a.type_id || null,
            isExisting: true // Mark as existing
          }))
        : [{ file: null, code: null, type_id: null, isExisting: false }];
      this.showModal = true;
    },

    hide() {
      this.form.reset();
      this.form.conformes = [{ name: null, position: null, contact_no: null }];
      this.form.is_active = true;
      this.attachments = [{ file: null, code: null, type_id: null, isExisting: false }];
      this.showModal = false;
    },

    saveSupplier() {
      // Prepare form data
      const formData = new FormData();

      // Basic fields
      formData.append("name", this.form.name || "");
      formData.append("code", this.form.code || "");
      formData.append("address", this.form.address || "");
      formData.append("is_active", this.form.is_active ? "1" : "0");

      // Conformes
      this.form.conformes.forEach((conforme, index) => {
        if (conforme.name) {
          formData.append(`conformes[${index}][name]`, conforme.name);
          formData.append(`conformes[${index}][position]`, conforme.position || "");
          formData.append(`conformes[${index}][contact_no]`, conforme.contact_no || "");
        }
      });

      // Attachments
      if (this.editable) {
        // For updates, separate existing and new attachments
        const existingAttachments = this.attachments.filter(a => a.isExisting && a.id);
        const newAttachments = this.attachments.filter(a => !a.isExisting && a.file instanceof File);

        // Send existing attachment IDs
        existingAttachments.forEach((attachment, index) => {
          formData.append(`existing_attachments[${index}]`, attachment.id);
          formData.append(`attachment_codes[${index}]`, attachment.code || "");
          formData.append(`attachment_types[${index}]`, attachment.type_id || "");
        });

        // Send new attachments with offset index
        newAttachments.forEach((attachment, index) => {
          const offsetIndex = existingAttachments.length + index;
          formData.append(`attachments[${index}]`, attachment.file);
          formData.append(`attachment_codes[${offsetIndex}]`, attachment.code || "");
          formData.append(`attachment_types[${offsetIndex}]`, attachment.type_id || "");
        });
      } else {
        // For create, send all attachments
        this.attachments.forEach((attachment, index) => {
          if (attachment.file instanceof File) {
            formData.append(`attachments[${index}]`, attachment.file);
            formData.append(`attachment_codes[${index}]`, attachment.code || "");
            formData.append(`attachment_types[${index}]`, attachment.type_id || "");
          }
        });
      }

      if (this.editable) {
        formData.append("_method", "PUT");
        axios
          .post(`/faims/suppliers/${this.form.id}`, formData, {
            headers: {
              "Content-Type": "multipart/form-data",
            },
          })
          .then((response) => {
            this.$emit("update", true);
            this.hide();
          })
          .catch((error) => {
            console.error("Error updating supplier:", error);
          });
      } else {
        axios
          .post("/faims/suppliers", formData, {
            headers: {
              "Content-Type": "multipart/form-data",
            },
          })
          .then((response) => {
            this.$emit("add", true);
            this.hide();
          })
          .catch((error) => {
            console.error("Error creating supplier:", error);
          });
      }
    },

    addConforme() {
      this.form.conformes.push({ name: null, position: null, contact_no: null });
    },

    removeConforme(index) {
      if (this.form.conformes.length > 1) {
        this.form.conformes.splice(index, 1);
      }
    },

    addAttachment() {
      this.attachments.push({ file: null, code: null, type_id: null });
    },

    removeAttachment(index) {
      if (this.attachments.length > 1) {
        this.attachments.splice(index, 1);
      }
    },

    handleFileUpload(event, index) {
      const file = event.target.files[0];
      if (file) {
        this.attachments[index].file = file;
      }
    },

    viewFile(file) {
      if (file instanceof File) {
        // For newly uploaded files, create a temporary URL
        const url = URL.createObjectURL(file);
        window.open(url, "_blank");
      } else if (file.path) {
        // For existing files, construct the full URL
        const url = `${this.currentUrl}/storage/${file.path}`;
        window.open(url, "_blank");
      }
    },

    getFileIcon(file) {
      if (!file) return "ri-file-line";

      const fileName = file.name || file.path || "";
      if (!fileName) return "ri-file-line";

      const extension = fileName.split(".").pop().toLowerCase();

      switch (extension) {
        case "pdf":
          return "ri-file-pdf-line";
        case "doc":
        case "docx":
          return "ri-file-word-line";
        case "xls":
        case "xlsx":
          return "ri-file-excel-line";
        case "jpg":
        case "jpeg":
        case "png":
        case "gif":
          return "ri-image-line";
        default:
          return "ri-file-line";
      }
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

.upload-area:hover {
  background: #f1f3f4;
  border-color: #007bff;
  cursor: pointer;
}

.file-item {
  transition: all 0.2s ease;
}

.file-item:hover {
  background: #f8f9fa;
}

.conforme-item {
  transition: all 0.2s ease;
}

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
