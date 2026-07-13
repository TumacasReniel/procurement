<template>
  <BButton
    v-if="showTrigger && !open && !isAnyModalOpen"
    variant="primary"
    class="ppmp-plan-chat-fab"
    title="Open plan comments"
    @click="openChat"
  >
    <i class="ri-chat-1-line"></i>
    <span
      v-if="totalCommentCount > 0"
      class="ppmp-plan-chat-count-badge"
    >
      {{ totalCommentCount }}
    </span>
  </BButton>

  <div v-if="open" class="ppmp-plan-chat-backdrop" @click.self="close">
    <BCard no-body class="ppmp-plan-chat-panel shadow-lg border-0">
      <BCardHeader class="ppmp-plan-chat-header">
        <div class="d-flex align-items-start justify-content-between gap-3">
          <div class="min-w-0">
            <div class="ppmp-plan-chat-eyebrow">Comments</div>
            <h5 class="mb-1 text-white text-truncate">
              {{ activePlan?.ppmp_no || resolvedPlan?.ppmp_no || "Choose a procurement plan" }}
            </h5>

          </div>
          <BButton
            variant="light"
            size="sm"
            class="btn-icon rounded-circle flex-shrink-0"
            @click="close"
          >
            <i class="ri-close-line"></i>
          </BButton>
        </div>
      </BCardHeader>

      <BCardBody class="ppmp-plan-chat-body">
        <div class="ppmp-plan-chat-picker">
          <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
            <label class="form-label mb-0 fw-semibold">Select Plan</label>
            <span v-if="planOptions.length" class="small text-muted">
              {{ planOptions.length }} available
            </span>
          </div>
          <div v-if="planOptions.length" class="ppmp-plan-chat-plan-list">
            <button
              v-for="planItem in planOptions"
              :key="planItem.id"
              type="button"
              class="ppmp-plan-chat-plan-item"
              :class="{ active: Number(planItem.id) === Number(resolvedPlan?.id) }"
              @click="openPlan(planItem)"
            >
              <span class="ppmp-plan-chat-plan-icon">
                <i class="ri-file-list-3-line"></i>
              </span>
              <span class="min-w-0">
                <span class="d-block fw-semibold text-truncate">
                  {{ planItem.ppmp_no || planItem.plan_name || "Procurement Plan" }}
                </span>
                <span class="d-block small text-truncate">
                  {{ planSummary(planItem) }}
                </span>
              </span>
              <span
                v-if="planCommentCount(planItem) > 0"
                class="ppmp-plan-chat-plan-count"
              >
                {{ planCommentCount(planItem) }}
              </span>
            </button>
          </div>
          <div v-else class="text-center text-muted small py-3">
            No plans available on this page.
          </div>
        </div>

        <div v-if="loading" class="text-center text-muted py-5">
          <div class="spinner-border spinner-border-sm text-primary mb-2"></div>
          <div>Loading comments...</div>
        </div>

        <div v-else-if="comments.length" class="ppmp-plan-chat-thread">
          <div
            v-for="commentItem in sortedComments"
            :key="commentItem.id"
            class="ppmp-plan-chat-message"
            :class="{ own: isOwnComment(commentItem) }"
          >
            <img
              :src="resolveAvatar(commentItem.user)"
              :alt="resolveCommentUser(commentItem.user)"
              class="ppmp-plan-chat-avatar"
            />
            <div class="ppmp-plan-chat-message-content">
              <div class="ppmp-plan-chat-message-meta">
                <strong>{{ resolveCommentUser(commentItem.user) }}</strong>
                <span>{{ formatChatDate(commentItem.created_at) }}</span>
              </div>
              <div class="ppmp-plan-chat-bubble">
                <span v-html="renderCommentContent(commentItem.content)"></span>
              </div>
            </div>
          </div>
        </div>

        <div v-else class="text-center text-muted py-5">
          <i class="ri-chat-smile-2-line d-block fs-2 text-primary mb-2"></i>
          <div class="fw-semibold text-body">
            {{ resolvedPlan ? "No comments yet" : "Pick a plan first" }}
          </div>
          <div class="small">
            {{ resolvedPlan ? `Start a conversation for this ${planShortName}.` : "Select an APP, PPMP, or SPP above to view comments." }}
          </div>
        </div>
      </BCardBody>

      <div v-if="resolvedPlan" class="ppmp-plan-chat-composer">
        <div class="position-relative">
          <BFormTextarea
            ref="commentTextarea"
            v-model="comment"
            rows="3"
            placeholder="Comment here..."
            :disabled="submitting"
            @input="handleCommentInput"
            @click="updateMentionContext"
            @keyup="updateMentionContext"
            @keydown="handleMentionKeydown"
          />
          <div
            v-if="showMentionMenu"
            class="ppmp-plan-mention-picker"
          >
            <div v-if="mentionSearchLoading" class="px-3 py-2 small text-muted">
              Searching employees...
            </div>
            <div v-else-if="!filteredMentionUsers.length" class="px-3 py-2 small text-muted">
              No employee found. Try a name or username.
            </div>
            <template v-else>
              <button
                v-for="(user, index) in filteredMentionUsers"
                :key="user.id"
                type="button"
                class="ppmp-plan-mention-item"
                :class="{ active: index === activeMentionIndex }"
                @mousedown.prevent="selectMentionUser(user)"
              >
                <img
                  :src="resolveAvatar(user)"
                  :alt="resolveCommentUser(user)"
                  class="rounded-circle border"
                />
                <span class="min-w-0">
                  <span class="d-block fw-semibold text-truncate">
                    {{ resolveCommentUser(user) }}
                  </span>
                  <span class="d-block small text-muted text-truncate">
                    @{{ user.username }}
                  </span>
                </span>
              </button>
            </template>
          </div>
        </div>
        <div class="small text-muted mt-2">
          Type <span class="fw-semibold">@</span> and search any employee by name or username.
        </div>
        <div class="d-flex justify-content-end mt-2">
          <BButton
            variant="primary"
            size="sm"
            :disabled="!comment.trim() || submitting"
            @click="submitComment"
          >
            <i class="ri-send-plane-line me-1"></i>
            {{ submitting ? "Posting..." : "Post Comment" }}
          </BButton>
        </div>
      </div>
    </BCard>
  </div>
</template>

<script>
export default {
  props: {
    plan: { type: Object, default: null },
    plans: { type: Array, default: () => [] },
    showTrigger: { type: Boolean, default: true },
  },
  data() {
    return {
      open: false,
      isAnyModalOpen: false,
      loading: false,
      submitting: false,
      comment: "",
      activePlan: null,
      mentionStartIndex: null,
      mentionQuery: "",
      activeMentionIndex: 0,
      mentionSearchResults: [],
      mentionSearchLoading: false,
      mentionSearchTimer: null,
      mentionSearchRequestId: 0,
      subscribedPlanId: null,
    };
  },
  computed: {
    resolvedPlan() {
      return this.activePlan || this.plan;
    },
    comments() {
      return Array.isArray(this.activePlan?.comments) ? this.activePlan.comments : [];
    },
    planOptions() {
      return Array.isArray(this.plans) ? this.plans.filter((plan) => plan?.id) : [];
    },
    totalCommentCount() {
      return this.planOptions.reduce(
        (total, plan) => total + this.planCommentCount(plan),
        0,
      );
    },
    sortedComments() {
      return [...this.comments].sort((left, right) =>
        new Date(left.created_at) - new Date(right.created_at)
      );
    },
    planShortName() {
      const planType = this.normalizedPlanType(this.resolvedPlan?.plan_type);

      if (planType === "APP") {
        return "APP";
      }

      if (planType === "SPP") {
        return "SPP";
      }

      return "PPMP";
    },
    planLongName() {
      if (this.planShortName === "APP") {
        return "Annual Procurement Plan";
      }

      if (this.planShortName === "SPP") {
        return "Supplemental Procurement Plan";
      }

      return "Project Procurement Management Plan";
    },
    mentionUsers() {
      const users = new Map();
      const appendUser = (user) => {
        if (!user?.id || !user?.username) {
          return;
        }

        if (!users.has(Number(user.id))) {
          users.set(Number(user.id), user);
        }
      };

      appendUser(this.activePlan?.created_by);
      appendUser(this.activePlan?.requested_by);
      appendUser(this.activePlan?.approved_by);

      this.comments.forEach((commentItem) => appendUser(commentItem?.user));
      this.mentionSearchResults.forEach((user) => appendUser(user));

      return Array.from(users.values()).sort((left, right) =>
        this.resolveCommentUser(left).localeCompare(this.resolveCommentUser(right)),
      );
    },
    filteredMentionUsers() {
      const keyword = this.mentionQuery.trim().toLowerCase();
      const currentUserId = Number(this.$page?.props?.user?.data?.id || 0);

      return this.mentionUsers.filter((user) => {
        if (currentUserId && Number(user?.id || 0) === currentUserId) {
          return false;
        }

        if (!keyword) {
          return true;
        }

        return [
          user.username,
          this.resolveCommentUser(user),
        ]
          .filter(Boolean)
          .join(" ")
          .toLowerCase()
          .includes(keyword);
      });
    },
    showMentionMenu() {
      return (
        this.resolvedPlan &&
        this.mentionStartIndex !== null &&
        (this.filteredMentionUsers.length > 0 ||
          this.mentionSearchLoading ||
          this.mentionQuery.trim().length > 0)
      );
    },
  },
  watch: {
    open(val) {
      document.body.classList.toggle('ppmp-chat-open', val);
    },
    mentionQuery(newValue) {
      this.queueMentionSearch(newValue);
    },
  },
  mounted() {
    this._modalObserver = new MutationObserver(() => {
      this.isAnyModalOpen = document.body.classList.contains('modal-open');
    });
    this._modalObserver.observe(document.body, { attributes: true, attributeFilter: ['class'] });
  },

  beforeUnmount() {
    this.clearMentionSearchTimer();
    this.teardownCommentChannel();
    if (this._modalObserver) this._modalObserver.disconnect();
    document.body.classList.remove('ppmp-chat-open');
  },
  methods: {
    openChat() {
      this.open = true;

      if (this.resolvedPlan?.id && !this.activePlan?.comments) {
        this.openPlan(this.resolvedPlan);
      }
    },
    normalizedPlanType(planType) {
      switch (planType) {
        case "APP":
        case "annual":
          return "APP";

        case "SPP":
        case "supplemental":
          return "SPP";

        case "PPMP":
        case "ppmp":
        default:
          return "PPMP";
      }
    },
    openPlan(plan = null) {
      const selectedPlan = plan || this.plan;

      if (!selectedPlan?.id) {
        this.open = true;
        return;
      }

      this.activePlan = {
        ...selectedPlan,
        comments: [],
      };
      this.comment = "";
      this.resetMentionState();
      this.open = true;
      this.loading = true;
      this.syncCommentChannel(selectedPlan);

      axios
        .get(`/faims/procurement-ppmp/${selectedPlan.id}`, {
          params: {
            option: "comments",
            plan_type: this.normalizedPlanType(selectedPlan.plan_type),
          },
        })
        .then((response) => {
          const comments = Array.isArray(response.data?.data?.comments)
            ? response.data.data.comments
            : [];

          this.activePlan = {
            ...selectedPlan,
            ...response.data?.data,
            comments,
            comments_count: comments.length,
          };
        })
        .catch(() => {
          this.activePlan = {
            ...selectedPlan,
            comments: [],
          };
        })
        .finally(() => {
          this.loading = false;
        });
    },
    close() {
      this.open = false;
      this.activePlan = null;
      this.comment = "";
      this.resetMentionState();
      this.loading = false;
      this.submitting = false;
      this.teardownCommentChannel();
    },
    submitComment() {
      const content = this.comment.trim();

      if (!content || !this.resolvedPlan?.id || this.submitting) {
        return;
      }

      this.submitting = true;

      axios
        .post(`/faims/procurement-ppmp/${this.resolvedPlan.id}/comments`, {
          content,
          plan_type: this.normalizedPlanType(this.resolvedPlan.plan_type),
        })
        .then((response) => {
          const newComment = response.data?.data;

          if (newComment) {
            this.appendIncomingComment(newComment);
            this.$emit("comment-added", newComment);
          }

          this.comment = "";
          this.resetMentionState();
        })
        .finally(() => {
          this.submitting = false;
        });
    },
    resolveCommentUser(user) {
      return user?.profile?.full_name
        || user?.profile?.fullname
        || user?.name
        || user?.username
        || "User";
    },
    resolveAvatar(user) {
      return user?.profile?.avatar || user?.avatar || "/images/avatars/avatar.jpg";
    },
    isOwnComment(commentItem) {
      const currentUserId = Number(this.$page?.props?.user?.data?.id || 0);

      return Number(commentItem?.user?.id || 0) === currentUserId;
    },
    formatChatDate(value) {
      if (!value) {
        return "";
      }

      const date = new Date(value);

      if (Number.isNaN(date.getTime())) {
        return "";
      }

      return date.toLocaleString("en-US", {
        month: "short",
        day: "numeric",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
      });
    },
    planSummary(plan) {
      const planType = this.normalizedPlanType(plan?.plan_type);
      const unit = plan?.unit?.name || plan?.division?.name;
      const status = plan?.ppmp_status || plan?.status?.name || plan?.status;

      return [planType, unit, status].filter(Boolean).join(" | ");
    },
    planCommentCount(plan) {
      if (Number(plan?.id) === Number(this.activePlan?.id) && Array.isArray(this.activePlan?.comments)) {
        return this.activePlan.comments.length;
      }

      return Number(plan?.comments_count || 0);
    },
    escapeHtml(value) {
      return String(value ?? "")
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
    },
    renderCommentContent(content) {
      return this.escapeHtml(content)
        .replace(/(@[A-Za-z0-9._-]+)/g, '<span class="ppmp-plan-chat-mention">$1</span>')
        .replace(/\n/g, "<br>");
    },
    getCommentTextarea() {
      return this.$refs.commentTextarea?.$el?.querySelector("textarea") || null;
    },
    resetMentionState() {
      this.clearMentionSearchTimer();
      this.mentionSearchRequestId += 1;
      this.mentionStartIndex = null;
      this.mentionQuery = "";
      this.activeMentionIndex = 0;
      this.mentionSearchResults = [];
      this.mentionSearchLoading = false;
    },
    clearMentionSearchTimer() {
      if (!this.mentionSearchTimer) {
        return;
      }

      window.clearTimeout(this.mentionSearchTimer);
      this.mentionSearchTimer = null;
    },
    queueMentionSearch(value) {
      const keyword = String(value || "").trim();

      this.clearMentionSearchTimer();

      if (!keyword || this.mentionStartIndex === null) {
        this.mentionSearchRequestId += 1;
        this.mentionSearchResults = [];
        this.mentionSearchLoading = false;
        return;
      }

      const requestId = this.mentionSearchRequestId + 1;
      this.mentionSearchRequestId = requestId;
      this.mentionSearchLoading = true;

      this.mentionSearchTimer = window.setTimeout(() => {
        this.fetchMentionUsers(keyword, requestId);
      }, 250);
    },
    fetchMentionUsers(keyword, requestId = null) {
      if (!requestId) {
        requestId = this.mentionSearchRequestId + 1;
        this.mentionSearchRequestId = requestId;
      }

      this.mentionSearchLoading = true;

      axios
        .get("/search", {
          params: {
            option: "users",
            keyword,
            limit: 10,
          },
        })
        .then((response) => {
          if (requestId !== this.mentionSearchRequestId) {
            return;
          }

          this.mentionSearchResults = Array.isArray(response.data)
            ? response.data
            : [];
        })
        .catch(() => {
          if (requestId === this.mentionSearchRequestId) {
            this.mentionSearchResults = [];
          }
        })
        .finally(() => {
          if (requestId === this.mentionSearchRequestId) {
            this.mentionSearchLoading = false;
          }
        });
    },
    updateMentionContext(event) {
      const textarea = event?.target || this.getCommentTextarea();

      if (!textarea) {
        this.resetMentionState();
        return;
      }

      const cursorIndex = textarea.selectionStart ?? this.comment.length;
      const valueBeforeCursor = this.comment.slice(0, cursorIndex);
      const mentionMatch = valueBeforeCursor.match(/(^|\s)@([A-Za-z0-9._-]*)$/);

      if (!mentionMatch) {
        this.resetMentionState();
        return;
      }

      this.mentionStartIndex = cursorIndex - mentionMatch[2].length - 1;
      this.mentionQuery = mentionMatch[2] || "";
      this.activeMentionIndex = 0;
    },
    handleCommentInput(event) {
      this.updateMentionContext(event);
    },
    handleMentionKeydown(event) {
      if (!this.showMentionMenu) {
        return;
      }

      if (event.key === "ArrowDown") {
        if (!this.filteredMentionUsers.length) {
          return;
        }

        event.preventDefault();
        this.activeMentionIndex =
          (this.activeMentionIndex + 1) % this.filteredMentionUsers.length;
        return;
      }

      if (event.key === "ArrowUp") {
        if (!this.filteredMentionUsers.length) {
          return;
        }

        event.preventDefault();
        this.activeMentionIndex =
          (this.activeMentionIndex - 1 + this.filteredMentionUsers.length) %
          this.filteredMentionUsers.length;
        return;
      }

      if (event.key === "Enter" && !event.shiftKey) {
        if (!this.filteredMentionUsers.length) {
          return;
        }

        event.preventDefault();
        this.selectMentionUser(this.filteredMentionUsers[this.activeMentionIndex]);
        return;
      }

      if (event.key === "Escape") {
        event.preventDefault();
        this.resetMentionState();
      }
    },
    selectMentionUser(user) {
      if (!user?.username || this.mentionStartIndex === null) {
        return;
      }

      const textarea = this.getCommentTextarea();
      const cursorIndex = textarea?.selectionStart ?? this.comment.length;
      const mentionText = `@${user.username} `;

      this.comment = [
        this.comment.slice(0, this.mentionStartIndex),
        mentionText,
        this.comment.slice(cursorIndex),
      ].join("");

      const nextCursorIndex = this.mentionStartIndex + mentionText.length;

      this.$nextTick(() => {
        const input = this.getCommentTextarea();

        if (input) {
          input.focus();
          input.setSelectionRange(nextCursorIndex, nextCursorIndex);
        }
      });

      this.resetMentionState();
    },
    appendIncomingComment(newComment) {
      if (!newComment || !this.activePlan) {
        return;
      }

      const commentExists = this.comments.some(
        (commentItem) => Number(commentItem?.id) === Number(newComment.id),
      );

      if (commentExists) {
        return;
      }

      this.activePlan.comments = [
        ...this.comments,
        newComment,
      ];
      this.activePlan.comments_count = this.activePlan.comments.length;
    },
    commentChannelName(plan) {
      if (!plan?.id) {
        return null;
      }

      return this.normalizedPlanType(plan.plan_type) === "APP"
        ? `procurement-plan-app.${Number(plan.id)}`
        : `procurement-plan.${Number(plan.id)}`;
    },
    syncCommentChannel(plan) {
      this.teardownCommentChannel();

      const channelName = this.commentChannelName(plan);

      if (!channelName || !window.Echo) {
        return;
      }

      this.subscribedPlanId = channelName;
      window.Echo.private(channelName)
        .listen(".comment.added", (event) => {
          if (event?.comment) {
            this.appendIncomingComment(event.comment);
            this.$emit("comment-added", event.comment);
          }
        });
    },
    teardownCommentChannel() {
      if (!this.subscribedPlanId || !window.Echo) {
        this.subscribedPlanId = null;
        return;
      }

      window.Echo.leave(this.subscribedPlanId);
      this.subscribedPlanId = null;
    },
  },
};
</script>

<style scoped>
.ppmp-plan-chat-fab {
  position: fixed;
  right: 24px;
  bottom: 24px;
  z-index: 1065;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 52px;
  height: 52px;
  padding: 0;
  border: 0;
  border-radius: 50%;
  color: #ffffff;
  background: linear-gradient(135deg, #5368ae, #3a4a83);
  box-shadow: 0 14px 28px rgba(64, 81, 137, .28);
}

.ppmp-plan-chat-fab:hover,
.ppmp-plan-chat-fab:focus {
  color: #ffffff;
  background: linear-gradient(135deg, #5d73bd, #34457d);
}

.ppmp-plan-chat-fab i {
  font-size: 24px;
}

.ppmp-plan-chat-count-badge {
  position: absolute;
  top: 0;
  left: 100%;
  min-width: 20px;
  padding: 3px 6px;
  border-radius: 999px;
  background: #dc3545;
  color: #ffffff;
  font-size: 11px;
  font-weight: 700;
  line-height: 1.2;
  transform: translate(-50%, -50%);
}

.ppmp-plan-chat-backdrop {
  position: fixed;
  inset: 0;
  z-index: 1050;
  display: flex;
  justify-content: flex-end;
  align-items: flex-end;
  padding: 24px;
  background: rgba(15, 23, 42, .28);
}

.ppmp-plan-chat-panel {
  width: min(520px, calc(100vw - 32px));
  max-height: min(720px, calc(100dvh - 48px));
  border-radius: 16px;
  overflow: hidden;
  background: var(--vz-card-bg, var(--bs-card-bg, #ffffff)) !important;
  color: var(--vz-body-color, var(--bs-body-color, #111827));
}

.ppmp-plan-chat-header {
  padding: 16px;
  border: 0;
  background: linear-gradient(135deg, #405189, #2f3d71);
  color: #ffffff;
}

.ppmp-plan-chat-eyebrow {
  color: rgba(255, 255, 255, .72);
  font-size: 11px;
  font-weight: 800;
  text-transform: uppercase;
}

.ppmp-plan-chat-body {
  display: flex;
  flex-direction: column;
  height: min(440px, calc(100dvh - 260px));
  min-height: 260px;
  padding: 0;
  overflow-y: auto;
  background: var(--vz-tertiary-bg, var(--bs-tertiary-bg, #f8fafc)) !important;
  color: var(--vz-body-color, var(--bs-body-color, #111827));
}

.ppmp-plan-chat-picker {
  flex: 0 0 auto;
  padding: 16px;
  border-bottom: 1px solid var(--vz-border-color, var(--bs-border-color, rgba(91, 105, 153, .14)));
  background: var(--vz-card-bg, var(--bs-card-bg, #ffffff));
}

.ppmp-plan-chat-plan-list {
  display: grid;
  gap: 8px;
  max-height: 180px;
  overflow-y: auto;
}

.ppmp-plan-chat-plan-item {
  display: flex;
  align-items: center;
  gap: 10px;
  width: 100%;
  padding: 10px;
  border: 1px solid var(--vz-border-color, var(--bs-border-color, rgba(91, 105, 153, .14)));
  border-radius: 12px;
  background: transparent;
  color: var(--vz-body-color, var(--bs-body-color, #111827));
  text-align: left;
}

.ppmp-plan-chat-plan-item .min-w-0 {
  flex: 1 1 auto;
}

.ppmp-plan-chat-plan-item:hover,
.ppmp-plan-chat-plan-item.active {
  border-color: rgba(64, 81, 137, .28);
  background: rgba(64, 81, 137, .08);
}

.ppmp-plan-chat-plan-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex: 0 0 auto;
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: #eef2ff;
  color: #405189;
}

.ppmp-plan-chat-plan-count {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex: 0 0 auto;
  min-width: 24px;
  height: 24px;
  padding: 0 7px;
  border-radius: 999px;
  background: #dc3545;
  color: #ffffff;
  font-size: 11px;
  font-weight: 700;
}

.ppmp-plan-chat-thread {
  display: grid;
  gap: 12px;
  padding: 16px;
}

.ppmp-plan-chat-message {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  justify-content: flex-start;
}

.ppmp-plan-chat-message.own {
  flex-direction: row-reverse;
  justify-content: flex-start;
}

.ppmp-plan-chat-avatar {
  flex: 0 0 auto;
  width: 34px;
  height: 34px;
  border: 1px solid var(--vz-border-color, var(--bs-border-color, rgba(91, 105, 153, .14)));
  border-radius: 50%;
  background: var(--vz-card-bg, var(--bs-card-bg, #ffffff));
  object-fit: cover;
}

.ppmp-plan-chat-message-content {
  display: grid;
  gap: 4px;
  max-width: min(88%, 390px);
  justify-items: start;
}

.ppmp-plan-chat-message.own .ppmp-plan-chat-message-content {
  justify-items: end;
}

.ppmp-plan-chat-message-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  color: var(--vz-secondary-color, var(--bs-secondary-color, #4b5563));
  font-size: 11px;
}

.ppmp-plan-chat-bubble {
  max-width: 100%;
  padding: 10px 12px;
  border: 1px solid var(--vz-border-color, var(--bs-border-color, rgba(91, 105, 153, .14)));
  border-radius: 14px 14px 14px 4px;
  background: var(--vz-card-bg, var(--bs-card-bg, #ffffff));
  color: var(--vz-body-color, var(--bs-body-color, #111827));
  font-size: 13px;
  line-height: 1.45;
  white-space: pre-wrap;
  overflow-wrap: anywhere;
}

.ppmp-plan-chat-message.own .ppmp-plan-chat-bubble {
  border-color: transparent;
  border-radius: 14px 14px 4px 14px;
  background: #405189;
  color: #ffffff;
}

.ppmp-plan-chat-composer {
  padding: 14px 16px 16px;
  border-top: 1px solid var(--vz-border-color, var(--bs-border-color, rgba(91, 105, 153, .14)));
  background: var(--vz-card-bg, var(--bs-card-bg, #ffffff)) !important;
  color: var(--vz-body-color, var(--bs-body-color, #111827));
}

.ppmp-plan-chat-composer textarea {
  background: #ffffff !important;
  color: #111827 !important;
  resize: none;
}

.ppmp-plan-mention-picker {
  position: absolute;
  right: 0;
  left: 0;
  bottom: calc(100% + 8px);
  z-index: 10;
  max-height: 220px;
  overflow-y: auto;
  border: 1px solid var(--vz-border-color, var(--bs-border-color, rgba(91, 105, 153, .14)));
  border-radius: 8px;
  background: var(--vz-card-bg, var(--bs-card-bg, #ffffff));
  box-shadow: 0 10px 24px rgba(15, 23, 42, .14);
}

.ppmp-plan-mention-item {
  display: flex;
  align-items: center;
  gap: 10px;
  width: 100%;
  padding: 9px 10px;
  border: 0;
  background: transparent;
  color: var(--vz-body-color, var(--bs-body-color, #111827));
  text-align: left;
}

.ppmp-plan-mention-item:hover,
.ppmp-plan-mention-item.active {
  background: rgba(64, 81, 137, .08);
}

.ppmp-plan-mention-item img {
  flex: 0 0 auto;
  width: 32px;
  height: 32px;
  object-fit: cover;
}

:deep(.ppmp-plan-chat-mention) {
  display: inline-block;
  padding: 0 .35rem;
  border-radius: .5rem;
  background: #ffe36a;
  color: #5f4700;
  font-weight: 700;
}

:global([data-bs-theme="dark"]) .ppmp-plan-chat-panel {
  background: #212a36 !important;
  color: #f3f6fb;
}

:global([data-bs-theme="dark"]) .ppmp-plan-chat-body {
  background: #252f3d !important;
  color: #f3f6fb;
}

:global([data-bs-theme="dark"]) .ppmp-plan-chat-picker {
  border-color: #354052;
  background: #212a36;
}

:global([data-bs-theme="dark"]) .ppmp-plan-chat-plan-item {
  border-color: #354052;
  color: #f3f6fb;
}

:global([data-bs-theme="dark"]) .ppmp-plan-chat-plan-item:hover,
:global([data-bs-theme="dark"]) .ppmp-plan-chat-plan-item.active {
  border-color: #5368ae;
  background: #273244;
}

:global([data-bs-theme="dark"]) .ppmp-plan-chat-plan-icon {
  background: #273244;
  color: #93c5fd;
}

:global([data-bs-theme="dark"]) .ppmp-plan-chat-message-meta {
  color: #b8c3d6;
}

:global([data-bs-theme="dark"]) .ppmp-plan-chat-bubble {
  border-color: #354052;
  background: #212a36;
  color: #f3f6fb;
}

:global([data-bs-theme="dark"]) .ppmp-plan-chat-composer {
  border-color: #354052;
  background: #212a36 !important;
  color: #f3f6fb;
}

:global([data-bs-theme="dark"]) .ppmp-plan-chat-composer textarea {
  background: #252f3d !important;
  border-color: #3a4658 !important;
  color: #f3f6fb !important;
}

:global([data-bs-theme="dark"]) .ppmp-plan-mention-picker {
  border-color: #354052;
  background: #212a36;
}

:global([data-bs-theme="dark"]) .ppmp-plan-mention-item {
  color: #f3f6fb;
}

:global([data-bs-theme="dark"]) .ppmp-plan-mention-item:hover,
:global([data-bs-theme="dark"]) .ppmp-plan-mention-item.active {
  background: #273244;
}

@media (max-width: 768px) {
  .ppmp-plan-chat-backdrop {
    padding: 16px 16px 1px;
  }
}
</style>
