<template>
  <div v-if="versions && versions.length > 1" class="ppmp-version-panel">
    <div class="ppmp-version-panel__header">
      <i class="ri-git-branch-line"></i>
      <span>Version History</span>
    </div>
    <div class="ppmp-version-panel__track">
      <template v-for="(version, index) in versions" :key="version.id">
        <div
          class="ppmp-version-card"
          :class="{
            'ppmp-version-card--current': version.is_current,
            'ppmp-version-card--final': version.ppmp_type === 'final',
            'ppmp-version-card--indicative': version.ppmp_type === 'indicative',
          }"
        >
          <div class="ppmp-version-card__type-icon">
            <i :class="version.ppmp_type === 'final' ? 'ri-flag-fill' : 'ri-draft-line'"></i>
          </div>
          <div class="ppmp-version-card__body">
            <div class="ppmp-version-card__type-label">
              {{ version.ppmp_type_label }}
              <span v-if="version.is_current" class="ppmp-version-card__current-badge">
                Current
              </span>
            </div>
            <div class="ppmp-version-card__code">{{ version.code }}</div>
            <div class="ppmp-version-card__meta">
              <span>
                <i class="ri-file-list-line"></i>
                {{ version.items_count }} item{{ version.items_count !== 1 ? 's' : '' }}
              </span>
              <span class="ppmp-version-card__sep">·</span>
              <span>{{ version.status }}</span>
            </div>
          </div>
          <div class="ppmp-version-card__action">
            <a
              v-if="!version.is_current"
              :href="`/faims/procurement-ppmp/${version.id}`"
              class="ppmp-version-card__view-link"
              @click.prevent="navigate(version.id)"
            >
              View <i class="ri-arrow-right-line"></i>
            </a>
            <span v-else class="ppmp-version-card__viewing">
              <i class="ri-eye-line"></i> Viewing
            </span>
          </div>
        </div>
        <div v-if="index < versions.length - 1" class="ppmp-version-panel__arrow">
          <i class="ri-arrow-right-line"></i>
        </div>
      </template>
    </div>
  </div>
</template>

<script>
import { router } from "@inertiajs/vue3";

export default {
  props: {
    versions: { type: Array, default: () => [] },
  },
  methods: {
    navigate(id) {
      router.get(`/faims/procurement-ppmp/${id}`, {}, { preserveScroll: false });
    },
  },
};
</script>

<style scoped>
.ppmp-version-panel {
  border-top: 1px solid var(--ppmp-border, #e9ebec);
  padding: 12px 20px;
  background: var(--ppmp-surface-soft, #f8fafc);
}

.ppmp-version-panel__header {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: var(--ppmp-muted, #6c757d);
  margin-bottom: 10px;
}

.ppmp-version-panel__track {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 4px;
}

.ppmp-version-panel__arrow {
  display: flex;
  align-items: center;
  color: var(--ppmp-muted, #6c757d);
  font-size: 16px;
  padding: 0 2px;
  flex-shrink: 0;
}

/* Version card */
.ppmp-version-card {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px 12px;
  border-radius: 8px;
  border: 1px solid var(--ppmp-border, #e9ebec);
  background: var(--ppmp-surface, #ffffff);
  min-width: 180px;
  max-width: 240px;
  transition: box-shadow 0.15s;
}

.ppmp-version-card--current {
  border-color: #0ab39c;
  box-shadow: 0 0 0 2px rgba(10, 179, 156, 0.15);
}

.ppmp-version-card--final .ppmp-version-card__type-icon {
  color: #0ab39c;
  background: rgba(10, 179, 156, 0.1);
}

.ppmp-version-card--indicative .ppmp-version-card__type-icon {
  color: #405189;
  background: rgba(64, 81, 137, 0.1);
}

.ppmp-version-card__type-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border-radius: 6px;
  font-size: 15px;
  flex-shrink: 0;
}

.ppmp-version-card__body {
  flex: 1;
  min-width: 0;
}

.ppmp-version-card__type-label {
  font-size: 12px;
  font-weight: 700;
  color: var(--ppmp-text, #212529);
  display: flex;
  align-items: center;
  gap: 5px;
  flex-wrap: wrap;
}

.ppmp-version-card__current-badge {
  font-size: 10px;
  font-weight: 700;
  color: #0ab39c;
  background: rgba(10, 179, 156, 0.12);
  border-radius: 3px;
  padding: 1px 5px;
  text-transform: uppercase;
  letter-spacing: 0.3px;
}

.ppmp-version-card__code {
  font-size: 11px;
  font-weight: 700;
  color: #405189;
  font-family: "Courier New", monospace;
  margin-top: 1px;
  margin-bottom: 3px;
}

.ppmp-version-card__meta {
  font-size: 11px;
  color: var(--ppmp-muted, #6c757d);
  margin-top: 2px;
  display: flex;
  align-items: center;
  gap: 4px;
  flex-wrap: wrap;
}

.ppmp-version-card__sep {
  opacity: 0.4;
}

.ppmp-version-card__action {
  flex-shrink: 0;
}

.ppmp-version-card__view-link {
  font-size: 11px;
  font-weight: 600;
  color: #405189;
  text-decoration: none;
  display: flex;
  align-items: center;
  gap: 2px;
  white-space: nowrap;
}

.ppmp-version-card__view-link:hover {
  text-decoration: underline;
}

.ppmp-version-card__viewing {
  font-size: 11px;
  color: #0ab39c;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 3px;
  white-space: nowrap;
}

@media (max-width: 768px) {
  .ppmp-version-panel__track {
    flex-direction: column;
    align-items: stretch;
  }

  .ppmp-version-panel__arrow {
    transform: rotate(90deg);
    align-self: flex-start;
    margin-left: 20px;
  }

  .ppmp-version-card {
    max-width: none;
  }
}
</style>
