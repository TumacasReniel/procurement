r<template>
  <b-modal
    v-model="visible"
    header-class="p-0 border-0"
    body-class="p-0"
    content-class="border-0 shadow"
    centered
    hide-footer
    hide-header
  >
    <div class="revert-result-modal">
      <button
        type="button"
        class="btn-close revert-close-btn"
        aria-label="Close"
        @click="visible = false"
      ></button>
        <div class="revert-result-body">
          <div class="revert-icon-wrap" :class="[`is-${variant}`]">
            <i :class="variant === 'success' ? 'ri-check-line' : 'ri-close-line'"></i>
          </div>
          <h4 class="revert-title" :class="[`is-${variant}`]">{{ title }}</h4>
          <p class="revert-subtitle">{{ info }}</p>
        </div>
      <div class="revert-result-footer">
        Any suggestions please contact <span>Administrator</span>
      </div>
    </div>
  </b-modal>
</template>

<script>
export default {
  name: "RevertResultModal",
  props: {
    modelValue: {
      type: Boolean,
      default: false,
    },
    title: {
      type: String,
      default: "Status reverted successfully!",
    },
    info: {
      type: String,
      default: "",
    },
    variant: {
      type: String,
      default: "success",
    },
  },
  emits: ["update:modelValue"],
  computed: {
    visible: {
      get() {
        return this.modelValue;
      },
      set(value) {
        this.$emit("update:modelValue", value);
      },
    },
  },
};
</script>

<style scoped>
.revert-result-modal {
  position: relative;
  animation: modalSlideIn 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
  border-radius: 20px;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25), 0 0 0 1px rgba(255, 255, 255, 0.05);
  overflow: hidden;
}

@keyframes modalSlideIn {
  from {
    opacity: 0;
    transform: scale(0.95) translateY(10px);
  }
  to {
    opacity: 1;
    transform: scale(1) translateY(0);
  }
}

.revert-close-btn {
  position: absolute;
  top: 16px;
  right: 16px;
  z-index: 2;
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.8);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.2);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
  color: #64748b;
  transition: all 0.2s ease;
  cursor: pointer;
}

.revert-close-btn:hover {
  background: rgba(255, 255, 255, 1);
  transform: scale(1.05);
  color: #374151;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.revert-result-body {
  padding: clamp(40px, 8vw, 60px) clamp(24px, 5vw, 32px) clamp(24px, 5vw, 32px);
  text-align: center;
  font-family: "Poppins", "Inter", "Segoe UI", Arial, sans-serif;
}

.revert-icon-wrap {
  width: clamp(80px, 20vw, 100px);
  height: clamp(80px, 20vw, 100px);
  border-radius: 999px;
  margin: 0 auto clamp(20px, 4vw, 28px);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: clamp(24px, 6vw, 32px);
  animation: pulse 2s infinite;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
}

@keyframes pulse {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.05); }
}

.revert-icon-wrap.is-success {
  background: linear-gradient(135deg, #d4f4f0 0%, #a7f3d0 100%);
  color: #10b981;
  box-shadow: 0 0 30px rgba(16, 185, 129, 0.4);
}

.revert-icon-wrap.is-danger {
  background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
  color: #ef4444;
  box-shadow: 0 0 30px rgba(239, 68, 68, 0.4);
}

.revert-title {
  margin-bottom: 12px;
  font-family: "Poppins", "Inter", "Segoe UI", Arial, sans-serif;
  font-size: clamp(1rem, 1.4vw, 1.2rem);
  line-height: 1.25;
  font-weight: 700;
  letter-spacing: 0.01em;
}

.revert-title.is-success {
  background: linear-gradient(135deg, #10b981, #059669);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.revert-title.is-danger {
  background: linear-gradient(135deg, #ef4444, #dc2626);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.revert-subtitle {
  margin-bottom: 0;
  font-family: "Poppins", "Inter", "Segoe UI", Arial, sans-serif;
  font-size: clamp(0.95rem, 1.1vw, 1.05rem);
  line-height: 1.45;
  font-weight: 500;
  color: #6b7280;
}

.revert-result-footer {
  background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
  border-top: 1px solid rgba(255, 255, 255, 0.5);
  text-align: center;
  padding: clamp(16px, 4vw, 20px) clamp(20px, 5vw, 24px);
  font-family: "Poppins", "Inter", "Segoe UI", Arial, sans-serif;
  font-size: clamp(0.875rem, 2.2vw, 0.95rem);
  line-height: 1.5;
  font-weight: 500;
  color: #64748b;
}

.revert-result-footer span {
  background: linear-gradient(135deg, #3f51b5, #5a67d8);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  font-weight: 700;
}
</style>
