<template>
  <div>
    <div
      :class="['file-dropzone', { 'is-dragging': isDragging, 'is-invalid': invalid }]"
      @dragenter.prevent="isDragging = true"
      @dragover.prevent="isDragging = true"
      @dragleave.prevent="isDragging = false"
      @drop.prevent="handleDrop"
      @click="openPicker"
    >
      <input
        ref="fileInput"
        type="file"
        :accept="accept"
        class="d-none"
        @change="handleSelect"
      />
      <div class="file-dropzone__icon">
        <i :class="icon"></i>
      </div>
      <div>
        <div class="file-dropzone__title">
          {{ fileName || title }}
        </div>
        <div v-if="hint" class="file-dropzone__hint">{{ hint }}</div>
      </div>
    </div>
    <div v-if="fileName" class="file-dropzone-file">
      <span>{{ fileName }}</span>
      <button type="button" class="btn btn-sm btn-link text-danger p-0" @click="removeFile">
        {{ removeLabel }}
      </button>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    file: {
      type: Object,
      default: null,
    },
    existingFileName: {
      type: String,
      default: "",
    },
    accept: {
      type: String,
      default: "",
    },
    allowedMimeTypes: {
      type: Array,
      default: () => [],
    },
    allowedExtensions: {
      type: Array,
      default: () => [],
    },
    maxSizeMb: {
      type: Number,
      default: null,
    },
    invalidTypeMessage: {
      type: String,
      default: "This file type is not allowed.",
    },
    invalidSizeMessage: {
      type: String,
      default: "",
    },
    validator: {
      type: Function,
      default: null,
    },
    title: {
      type: String,
      default: "Drop file here or click to browse",
    },
    hint: {
      type: String,
      default: "",
    },
    icon: {
      type: String,
      default: "ri-upload-cloud-2-line",
    },
    removeLabel: {
      type: String,
      default: "Remove",
    },
    invalid: {
      type: Boolean,
      default: false,
    },
  },
  emits: ["selected", "rejected", "remove"],
  data() {
    return {
      isDragging: false,
    };
  },
  computed: {
    fileName() {
      return this.file?.name || this.existingFileName || "";
    },
  },
  watch: {
    file(value) {
      if (!value && this.$refs.fileInput) {
        this.$refs.fileInput.value = "";
      }
    },
  },
  methods: {
    openPicker() {
      this.$refs.fileInput?.click();
    },
    handleSelect(event) {
      this.handleFile(event.target.files?.[0] || null);
    },
    handleDrop(event) {
      this.isDragging = false;
      this.handleFile(event.dataTransfer.files?.[0] || null);
    },
    handleFile(file) {
      if (!file) {
        return;
      }

      const error = this.validateFile(file);

      if (error) {
        this.clearInput();
        this.$emit("rejected", { file, message: error });
        return;
      }

      this.$emit("selected", file);
    },
    validateFile(file) {
      if (this.validator) {
        const customResult = this.validator(file);

        if (customResult === false) {
          return this.invalidTypeMessage;
        }

        if (typeof customResult === "string") {
          return customResult;
        }
      }

      if (this.allowedMimeTypes.length && !this.allowedMimeTypes.includes(file.type)) {
        return this.invalidTypeMessage;
      }

      if (this.allowedExtensions.length) {
        const fileName = String(file.name || "").toLowerCase();
        const hasAllowedExtension = this.allowedExtensions.some((extension) => {
          const normalizedExtension = String(extension).toLowerCase().replace(/^\./, "");

          return fileName.endsWith(`.${normalizedExtension}`);
        });

        if (!hasAllowedExtension) {
          return this.invalidTypeMessage;
        }
      }

      if (this.maxSizeMb && file.size > this.maxSizeMb * 1024 * 1024) {
        return this.invalidSizeMessage || `Please attach a file up to ${this.maxSizeMb} MB only.`;
      }

      return "";
    },
    removeFile() {
      this.clearInput();

      this.$emit("remove");
    },
    clearInput() {
      if (this.$refs.fileInput) {
        this.$refs.fileInput.value = "";
      }
    },
  },
};
</script>

<style scoped>
.file-dropzone {
  display: flex;
  align-items: center;
  gap: 12px;
  min-height: 92px;
  padding: 16px;
  border: 1px dashed #b7c4d8;
  border-radius: 8px;
  background: #f8fafc;
  color: #334155;
  cursor: pointer;
  transition: border-color .15s ease, background-color .15s ease, box-shadow .15s ease;
}

.file-dropzone:hover,
.file-dropzone.is-dragging {
  border-color: #405189;
  background: #eef4ff;
  box-shadow: 0 8px 20px rgba(64, 81, 137, .12);
}

.file-dropzone.is-invalid {
  border-color: #f06548;
  background: rgba(240, 101, 72, .06);
}

.file-dropzone__icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 44px;
  height: 44px;
  flex: 0 0 44px;
  border-radius: 8px;
  background: #e0ebff;
  color: #405189;
  font-size: 24px;
}

.file-dropzone.is-invalid .file-dropzone__icon {
  background: rgba(240, 101, 72, .12);
  color: #f06548;
}

.file-dropzone__title {
  font-weight: 700;
}

.file-dropzone__hint {
  margin-top: 2px;
  color: #64748b;
  font-size: 12px;
}

.file-dropzone-file {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-top: 8px;
  padding: 8px 10px;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  background: #fff;
  color: #1f2937;
  font-size: 13px;
}
</style>
