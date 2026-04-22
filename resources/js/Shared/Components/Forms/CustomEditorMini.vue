<template>
  <div
    :class="['mini-editor', { 'mini-editor--disabled': disabled }]"
    :style="{ height: editorHeight + 'px' }"
  >
    <!-- Toolbar -->
    <div class="toolbar">
      <button type="button" :disabled="disabled" @mousedown.prevent="exec('bold')" title="Bold"><i class="ri-bold"></i></button>
      <button type="button" :disabled="disabled" @mousedown.prevent="exec('italic')" title="Italic"><i class="ri-italic"></i></button>
      <button type="button" :disabled="disabled" @mousedown.prevent="exec('underline')" title="Underline"><i class="ri-underline"></i></button>
      <div class="divider"></div>
      <button type="button" :disabled="disabled" @mousedown.prevent="exec('insertUnorderedList')" title="Bulleted List"><i class="ri-list-unordered"></i></button>
      <button type="button" :disabled="disabled" @mousedown.prevent="exec('insertOrderedList')" title="Numbered List"><i class="ri-list-ordered"></i></button>
    </div>

    <!-- Page Workspace -->
    <div class="workspace">
      <div
        ref="editor"
        class="page"
        :contenteditable="!disabled"
        spellcheck="true"
        @input="updateContent"
      ></div>
    </div>

    <!-- Resize Handle -->
    <div v-if="!disabled" class="resize-handle" @mousedown="startResize"></div>
  </div>
</template>

<script>
export default {
  name: "CustomEditorMini",
  props: {
    modelValue: {
      type: String,
      default: "",
    },
    modalSize: {
      type: String,
      default: "lg",
    },
    disabled: {
      type: Boolean,
      default: false,
    },
  },
  emits: ["update:modelValue"],
  data() {
    return {
      currentHeight: 200,
      isResizing: false,
      startY: 0,
      startHeight: 0,
    };
  },
  mounted() {
    if (this.modelValue) this.$refs.editor.innerHTML = this.modelValue;
    this.setInitialHeight();
    document.addEventListener('mousemove', this.resize);
    document.addEventListener('mouseup', this.stopResize);
  },
  beforeUnmount() {
    document.removeEventListener('mousemove', this.resize);
    document.removeEventListener('mouseup', this.stopResize);
  },
  computed: {
    editorHeight() {
      return this.currentHeight;
    },
    editorLineHeight() {
      switch (this.modalSize) {
        case 'fullscreen':
          return '2';
        case 'xl':
          return '1.8';
        case 'lg':
        default:
          return '1.6';
      }
    },
    editorFontSize() {
      switch (this.modalSize) {
        case 'fullscreen':
          return '16px';
        case 'xl':
          return '15px';
        case 'lg':
        default:
          return '14px';
      }
    },
  },
  methods: {
    setInitialHeight() {
      switch (this.modalSize) {
        case 'fullscreen':
          this.currentHeight = 400;
          break;
        case 'xl':
          this.currentHeight = 250;
          break;
        case 'lg':
        default:
          this.currentHeight = 200;
          break;
      }
    },
    exec(cmd) {
      if (this.disabled) return;
      document.execCommand(cmd, false, null);
      this.updateContent();
      this.$refs.editor.focus();
    },
    updateContent() {
      if (this.disabled) return;
      this.$emit("update:modelValue", this.$refs.editor.innerHTML);
    },
    startResize(event) {
      this.isResizing = true;
      this.startY = event.clientY;
      this.startHeight = this.currentHeight;
    },
    resize(event) {
      if (!this.isResizing) return;
      const deltaY = event.clientY - this.startY;
      this.currentHeight = Math.max(150, this.startHeight + deltaY); // Minimum height 150px
    },
    stopResize() {
      this.isResizing = false;
    },
  },
  watch: {
    modalSize() {
      this.setInitialHeight();
    },
    modelValue(val) {
      if (!this.$refs.editor) return;
      if (val !== this.$refs.editor.innerHTML) {
        this.$refs.editor.innerHTML = val;
      }
    },
  },
};
</script>

<style scoped>
/* ==== Mini Editor Layout ==== */
.mini-editor {
  --mini-editor-shell-bg: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
  --mini-editor-toolbar-bg: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
  --mini-editor-button-bg: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
  --mini-editor-border: #e2e8f0;
  --mini-editor-divider-bg: linear-gradient(135deg, #e2e8f0 0%, #cbd5e1 100%);
  --mini-editor-workspace-bg: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
  --mini-editor-page-bg: #ffffff;
  --mini-editor-page-text: #334155;
  --mini-editor-page-disabled-bg: #f8fafc;
  --mini-editor-page-disabled-text: #64748b;
  --mini-editor-page-focus-bg: #ffffff;
  --mini-editor-resize-bg: linear-gradient(135deg, #e2e8f0 0%, #cbd5e1 100%);
  --mini-editor-resize-indicator: rgba(59, 130, 246, 0.5);
  display: flex;
  flex-direction: column;
  background: var(--mini-editor-shell-bg);
  border-radius: 12px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  overflow: hidden;
  height: 200px;
  border: 1px solid var(--mini-editor-border);
  border-top: none;
}

.mini-editor--disabled {
  opacity: 0.92;
}

/* ==== Toolbar (Mini) ==== */
.toolbar {
  display: flex;
  align-items: center;
  justify-content: flex-start;
  background: var(--mini-editor-toolbar-bg);
  border-bottom: 2px solid var(--mini-editor-border);
  padding: 8px 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
  flex-wrap: wrap;
  gap: 6px;
}

.toolbar button {
  background: var(--mini-editor-button-bg);
  border: 2px solid var(--mini-editor-border);
  border-radius: 8px;
  width: 36px;
  height: 36px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #475569;
  font-size: 18px;
  cursor: pointer;
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
  overflow: hidden;
}

.toolbar button:disabled {
  cursor: not-allowed;
  opacity: 0.55;
  transform: none;
  box-shadow: none;
}

.toolbar button:disabled::before {
  display: none;
}

.toolbar button::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(59, 130, 246, 0.1), transparent);
  transition: left 0.5s;
}

.toolbar button:hover {
  background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
  border-color: #3b82f6;
  color: #ffffff;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

.toolbar button:hover::before {
  left: 100%;
}

.toolbar button:active {
  transform: translateY(0);
  box-shadow: 0 2px 4px rgba(59, 130, 246, 0.2);
}

.toolbar .divider {
  width: 2px;
  height: 28px;
  background: var(--mini-editor-divider-bg);
  border-radius: 1px;
  margin: 0 6px;
}

/* ==== Workspace and Page ==== */
.workspace {
  display: flex;
  justify-content: center;
  padding: 12px;
  overflow-y: auto;
  flex-grow: 1;
  background: var(--mini-editor-workspace-bg);
}

.page {
  width: 100%;
  min-height: 120px;
  padding: 16px;
  border-radius: 8px;
  background: var(--mini-editor-page-bg);
  box-shadow: inset 0 2px 8px rgba(0, 0, 0, 0.05);
  font-family: "Inter", "Segoe UI", "Roboto", sans-serif;
  font-size: v-bind(editorFontSize);
  line-height: v-bind(editorLineHeight);
  color: var(--mini-editor-page-text);
  outline: none;
  border: 1px solid var(--mini-editor-border);
  transition: all 0.25s ease;
}

.mini-editor--disabled .page {
  background: var(--mini-editor-page-disabled-bg);
  color: var(--mini-editor-page-disabled-text);
}

.page:focus {
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1), inset 0 2px 8px rgba(0, 0, 0, 0.05);
  border-color: #3b82f6;
  background: var(--mini-editor-page-focus-bg);
}

.page::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;

  border-radius: 8px 8px 0 0;
  opacity: 0;
  transition: opacity 0.25s ease;
}

.page:focus::before {
  opacity: 1;
}

/* ==== Resize Handle ==== */
.resize-handle {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  height: 8px;
  background: var(--mini-editor-resize-bg);
  cursor: ns-resize;
  border-radius: 0 0 12px 12px;
  transition: background 0.25s ease;
}


.resize-handle::after {
  content: '';
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 20px;
  height: 2px;
  background: var(--mini-editor-resize-indicator);
  border-radius: 1px;
}
</style>

<style>
[data-bs-theme="dark"] .mini-editor {
  --mini-editor-shell-bg: linear-gradient(135deg, #1b2230 0%, #232c3a 100%);
  --mini-editor-toolbar-bg: linear-gradient(135deg, #232c3a 0%, #1f2937 100%);
  --mini-editor-button-bg: linear-gradient(135deg, #2a3444 0%, #1f2937 100%);
  --mini-editor-border: rgba(148, 163, 184, 0.18);
  --mini-editor-divider-bg: linear-gradient(135deg, #334155 0%, #475569 100%);
  --mini-editor-workspace-bg: linear-gradient(135deg, #1b2230 0%, #1f2937 100%);
  --mini-editor-page-bg: #232c3a;
  --mini-editor-page-text: #e5edf7;
  --mini-editor-page-disabled-bg: #202938;
  --mini-editor-page-disabled-text: #94a3b8;
  --mini-editor-page-focus-bg: #232c3a;
  --mini-editor-resize-bg: linear-gradient(135deg, #334155 0%, #475569 100%);
  --mini-editor-resize-indicator: rgba(147, 197, 253, 0.55);
}

[data-bs-theme="dark"] .mini-editor .toolbar button {
  color: #cbd5e1;
}

[data-bs-theme="dark"] .mini-editor .page {
  caret-color: #e5edf7;
}
</style>
