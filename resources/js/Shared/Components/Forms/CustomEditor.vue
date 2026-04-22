<template>
  <div class="word-editor">
    <!-- Toolbar -->
    <div class="toolbar">
      <button type="button" @mousedown.prevent="exec('bold')" title="Bold"><i class="ri-bold"></i></button>
      <button type="button" @mousedown.prevent="exec('italic')" title="Italic"><i class="ri-italic"></i></button>
      <button type="button" @mousedown.prevent="exec('underline')" title="Underline"><i class="ri-underline"></i></button>
      <div class="divider"></div>
      <button type="button" @mousedown.prevent="exec('justifyLeft')" title="Align Left"><i class="ri-align-left"></i></button>
      <button type="button" @mousedown.prevent="exec('justifyCenter')" title="Center"><i class="ri-align-center"></i></button>
      <button type="button" @mousedown.prevent="exec('justifyRight')" title="Align Right"><i class="ri-align-right"></i></button>
      <button type="button" @mousedown.prevent="exec('justifyFull')" title="Justify"><i class="ri-align-justify"></i></button>
      <div class="divider"></div>
      <button type="button" @mousedown.prevent="exec('insertUnorderedList')" title="Bulleted List"><i class="ri-list-unordered"></i></button>
      <button type="button" @mousedown.prevent="exec('insertOrderedList')" title="Numbered List"><i class="ri-list-ordered"></i></button>
      <div class="divider"></div>
      <button type="button" @mousedown.prevent="exec('undo')" title="Undo"><i class="ri-arrow-go-back-line"></i></button>
      <button type="button" @mousedown.prevent="exec('redo')" title="Redo"><i class="ri-arrow-go-forward-line"></i></button>
    </div>

    <!-- Page Workspace -->
    <div class="workspace">
      <div
        ref="editor"
        class="page"
        contenteditable="true"
        spellcheck="true"
        @input="updateContent"
      ></div>
    </div>
  </div>
</template>

<script>
export default {
  name: "WordLikeEditor",
  props: ["modelValue"],
  emits: ["update:modelValue"],
  mounted() {
    if (this.modelValue) this.$refs.editor.innerHTML = this.modelValue;
  },
  methods: {
    exec(cmd) {
      document.execCommand(cmd, false, null);
      this.updateContent();
      this.$refs.editor.focus();
    },
    updateContent() {
      this.$emit("update:modelValue", this.$refs.editor.innerHTML);
    },
  },
  watch: {
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
/* ==== Word Editor Layout ==== */
.word-editor {
  --word-editor-shell-bg: #f3f4f6;
  --word-editor-toolbar-bg: #ffffff;
  --word-editor-toolbar-border: #dcdcdc;
  --word-editor-button-bg: #f8f9fa;
  --word-editor-button-border: #d0d0d0;
  --word-editor-button-text: #333333;
  --word-editor-divider: #cccccc;
  --word-editor-workspace-bg: #f3f4f6;
  --word-editor-page-bg: #ffffff;
  --word-editor-page-text: #222222;
  --word-editor-page-shadow: 0 0 8px rgba(0, 0, 0, 0.2);
  display: flex;
  flex-direction: column;
  background: var(--word-editor-shell-bg);
  height: 100vh;
  border: 1px solid var(--word-editor-toolbar-border);
  border-radius: 14px;
  overflow: hidden;
}

/* ==== Toolbar (Word Ribbon) ==== */
.toolbar {
  display: flex;
  align-items: center;
  justify-content: center; /* Center all icons horizontally */
  background: var(--word-editor-toolbar-bg);
  border-bottom: 2px solid var(--word-editor-toolbar-border);
  padding: 8px 15px;
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
  flex-wrap: wrap;
  gap: 8px;
}

.toolbar button {
  background: var(--word-editor-button-bg);
  border: 1px solid var(--word-editor-button-border);
  border-radius: 6px;
  width: 38px;
  height: 38px;
  display: flex;
  align-items: center; /* vertically center icon */
  justify-content: center; /* horizontally center icon */
  color: var(--word-editor-button-text);
  font-size: 20px;
  cursor: pointer;
  transition: all 0.15s ease;
}

.toolbar button:hover {
  background: #e7f1ff;
  border-color: #3b82f6;
  color: #1d4ed8;
}

.toolbar button:active {
  background: #dbeafe;
}

.toolbar .divider {
  width: 1px;
  height: 30px;
  background: var(--word-editor-divider);
  margin: 0 8px;
}

/* ==== Workspace and Page ==== */
.workspace {
  display: flex;
  justify-content: center;
  padding: 20px;
  overflow-y: auto;
  flex-grow: 1;
  background: var(--word-editor-workspace-bg);
}

.page {
  background: var(--word-editor-page-bg);
  width: 210mm;
  min-height: 297mm;
  padding: 2cm;
  box-shadow: var(--word-editor-page-shadow);
  font-family: "Calibri", "Times New Roman", serif;
  font-size: 12pt;
  line-height: 1.5;
  color: var(--word-editor-page-text);
  outline: none;
  border: 1px solid var(--word-editor-toolbar-border);
}

.page:focus {
  box-shadow: 0 0 0 2px #3b82f6;
}
</style>

<style>
[data-bs-theme="dark"] .word-editor {
  --word-editor-shell-bg: #161d27;
  --word-editor-toolbar-bg: #232c3a;
  --word-editor-toolbar-border: rgba(148, 163, 184, 0.18);
  --word-editor-button-bg: #1b2230;
  --word-editor-button-border: rgba(148, 163, 184, 0.18);
  --word-editor-button-text: #dbe7f5;
  --word-editor-divider: rgba(148, 163, 184, 0.24);
  --word-editor-workspace-bg: linear-gradient(180deg, #161d27 0%, #111827 100%);
  --word-editor-page-bg: #202937;
  --word-editor-page-text: #e5edf7;
  --word-editor-page-shadow: 0 18px 34px rgba(2, 6, 23, 0.34);
}

[data-bs-theme="dark"] .word-editor .toolbar button:hover {
  color: #eff6ff;
}

[data-bs-theme="dark"] .word-editor .page {
  caret-color: #e5edf7;
}
</style>
