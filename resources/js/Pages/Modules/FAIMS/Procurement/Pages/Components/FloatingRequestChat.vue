<template>
  <div class="position-fixed bottom-0 end-0 p-3 p-md-4" style="z-index: 1050;">
    <BCard
      v-if="open"
      no-body
      class="shadow-lg border-0 overflow-hidden mb-3 ms-auto"
      :style="panelStyle"
    >
      <BCardHeader class="bg-primary border-0 text-white p-3">
        <div class="d-flex align-items-start justify-content-between gap-3">
          <div class="flex-grow-1 overflow-hidden">
            <h5 class="mb-1 text-white">Request Chat</h5>
            <p class="mb-0 small text-white-50 text-truncate">
              {{ selectedRequest ? selectedRequest.code : "Choose a purchase request" }}
            </p>
          </div>
          <BButton
            variant="light"
            size="sm"
            class="rounded-circle d-inline-flex align-items-center justify-content-center flex-shrink-0 p-0"
            style="width: 36px; height: 36px;"
            @click="$emit('toggle')"
          >
            <i class="ri-close-line text-primary"></i>
          </BButton>
        </div>
      </BCardHeader>

      <BCardBody class="p-0 bg-light-subtle overflow-auto" :style="bodyStyle">
        <div class="p-3 border-bottom bg-light-subtle">
          <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
            <label class="form-label mb-0 fw-semibold">Select Request</label>
            <BButton
              v-if="selectedRequest"
              variant="light"
              size="sm"
              class="flex-shrink-0"
              @click="clearSelectedRequest"
            >
              Unselect PR
            </BButton>
          </div>
          <div class="border rounded floating-chat-surface overflow-hidden">
            <div class="p-2 border-bottom bg-body-tertiary">
              <div class="d-flex align-items-center gap-2">
                <BInputGroup size="sm" class="flex-grow-1">
                  <BInputGroupText>
                    <i class="ri-search-line"></i>
                  </BInputGroupText>
                  <BFormInput
                    v-model="requestSearch"
                    type="search"
                    placeholder="Search by PR code or purpose"
                  />
                </BInputGroup>
                <BButton
                  v-if="hasRequestSearch"
                  variant="light"
                  size="sm"
                  class="flex-shrink-0"
                  @click="resetRequestSearch"
                >
                  Clear
                </BButton>
              </div>
              <div v-if="requestOptions.length" class="d-flex flex-wrap align-items-center gap-2 mt-2">
                <BBadge class="bg-primary-subtle text-primary">
                  {{ activeRequestOptions.length }} active PR{{ activeRequestOptions.length === 1 ? "" : "s" }}
                </BBadge>
                <BBadge class="bg-info-subtle text-info">
                  {{ totalChatCount }} total {{ totalChatCount === 1 ? "chat" : "chats" }}
                </BBadge>
                <div class="ms-auto d-inline-flex align-items-center gap-1 floating-chat-sort">
                  <button
                    type="button"
                    class="floating-chat-sort-btn"
                    :class="{ active: activeSort === 'recent' }"
                    @click="setActiveSort('recent')"
                  >
                    Recent
                  </button>
                  <button
                    type="button"
                    class="floating-chat-sort-btn"
                    :class="{ active: activeSort === 'popular' }"
                    @click="setActiveSort('popular')"
                  >
                    Most Chats
                  </button>
                </div>
              </div>
            </div>

            <div v-if="requestsLoading" class="px-3 py-4 text-center text-muted">
              <div class="spinner-border spinner-border-sm text-primary mb-2" role="status"></div>
              <div class="small fw-semibold text-body">Loading requests...</div>
            </div>

            <div v-else-if="!requestOptions.length" class="px-3 py-4 text-center text-muted small">
              No procurement requests available.
            </div>

            <div
              v-else-if="!displayedRequestOptions.length"
              class="px-3 py-4 text-center text-muted small"
            >
              <div class="fw-semibold text-body mb-1">
                {{ hasRequestSearch ? "No matching procurement request found." : "No active chats yet" }}
              </div>
              <div>
                {{
                  hasRequestSearch
                    ? "Try another PR code or purpose."
                    : "Search for a procurement request above to start or review a conversation."
                }}
              </div>
            </div>

            <div v-else class="px-3 py-2 border-bottom floating-chat-surface">
              <div class="small fw-semibold text-body">
                {{ hasRequestSearch ? `Search Results (${displayedRequestOptions.length})` : `Active PR Chats (${displayedRequestOptions.length})` }}
              </div>
              <div class="small text-muted">
                {{
                  hasRequestSearch
                    ? "Showing matching PRs from all procurement requests."
                    : "Search above to browse every PR."
                }}
              </div>
              <div v-if="hasMoreRequestOptions" class="small text-muted mt-1">
                Showing {{ displayedRequestOptions.length }} of {{ requestSelectionOptions.length }}
              </div>
            </div>

            <BListGroup v-if="displayedRequestOptions.length" flush class="overflow-auto" :style="requestListStyle">
              <BListGroupItem
                v-for="request in displayedRequestOptions"
                :key="request.id"
                button
                action
                class="text-start py-3"
                :active="Number(request.id) === normalizedSelectedRequestId"
                @click="selectRequestOption(request.id)"
              >
                <div class="d-flex align-items-center justify-content-between gap-2 mb-1">
                  <div class="fw-semibold text-truncate">
                    {{ request.code }}
                  </div>
                  <BBadge
                    v-if="hasComments(request.comments_count)"
                    pill
                    class="floating-chat-count-badge"
                  >
                    {{ request.comments_count }}
                  </BBadge>
                </div>
                <div
                  class="small text-wrap"
                  :class="Number(request.id) === normalizedSelectedRequestId ? 'text-white-50' : 'text-muted'"
                >
                  {{ hasRequestSearch ? truncateText(request.purpose || request.title, 88) : latestChatPreview(request) }}
                </div>
                <div
                  v-if="hasComments(request.comments_count) || request.latest_comment_at"
                  class="small mt-2 d-flex flex-wrap align-items-center gap-2"
                  :class="Number(request.id) === normalizedSelectedRequestId ? 'text-white-50' : 'text-muted'"
                >
                  <span v-if="!hasRequestSearch && request.latest_comment?.user">
                    {{ latestChatAuthor(request) }}
                  </span>
                  <span v-if="hasComments(request.comments_count)">
                    {{ formatChatLabel(request.comments_count) }}
                  </span>
                  <span v-if="request.latest_comment_at">
                    <i class="ri-time-line me-1"></i>
                    {{ formatActivityDate(request.latest_comment_at) }}
                  </span>
                </div>
              </BListGroupItem>
            </BListGroup>
            <div
              v-if="hasMoreRequestOptions"
              class="p-2 border-top floating-chat-surface"
            >
              <BButton
                variant="light"
                size="sm"
                class="w-100 floating-chat-show-more"
                @click="showMoreRequests"
              >
                Show {{ nextRequestLoadCount }} more {{ hasRequestSearch ? "results" : "PR chats" }}
              </BButton>
            </div>
          </div>
        </div>

        <div v-if="loading" class="px-4 py-5 text-center text-muted">
          <div class="spinner-border text-primary mb-3" role="status"></div>
          <div class="fw-semibold text-body">Loading request conversation...</div>
        </div>

        <div v-else-if="selectedRequest" class="p-3">
          <BCard no-body class="bg-light-subtle shadow-none border mb-3">
            <BCardBody class="p-3">
              <div class="fw-bold text-primary mb-1">{{ selectedRequest.code }}</div>
              <p class="mb-3 small text-muted">
                {{ selectedRequest.purpose || selectedRequest.title || "No purpose provided." }}
              </p>
              <div class="d-flex flex-wrap gap-2">
                <BBadge class="bg-primary-subtle text-primary">
                  {{ selectedRequest.status?.name || "No status" }}
                </BBadge>
                <BBadge
                  v-if="selectedRequest.sub_status?.name"
                  class="bg-info-subtle text-info"
                >
                  {{ selectedRequest.sub_status.name }}
                </BBadge>
              </div>
            </BCardBody>
          </BCard>

          <BCard no-body class="bg-light-subtle shadow-none border mb-3">
            <BCardBody class="p-3">
              <div class="d-flex align-items-start gap-3">
                <img
                  :src="$page.props.user.data.avatar"
                  :alt="$page.props.user.data.name || 'User'"
                  class="rounded-circle border floating-chat-avatar flex-shrink-0"
                  :style="avatarStyle"
                />
                <div class="flex-grow-1">
                  <div class="position-relative">
                    <BFormTextarea
                      ref="commentTextarea"
                      v-model="newComment"
                      rows="3"
                      placeholder="Leave a follow-up comment for this request... Use @ to tag a user."
                      :disabled="commentSubmitting"
                      @input="handleCommentInput"
                      @click="updateMentionContext"
                      @keyup="updateMentionContext"
                      @keydown="handleMentionKeydown"
                    />
                    <div
                      v-if="showMentionMenu"
                      class="mention-picker border rounded shadow-sm floating-chat-surface"
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
                          class="mention-picker-item"
                          :class="{ active: index === activeMentionIndex }"
                          @mousedown.prevent="selectMentionUser(user)"
                        >
                          <img
                            :src="resolveAvatar(user)"
                            :alt="resolveName(user)"
                            class="rounded-circle border floating-chat-avatar flex-shrink-0"
                            :style="mentionAvatarStyle"
                          />
                          <span class="flex-grow-1 text-start overflow-hidden">
                            <span class="d-block fw-semibold text-truncate">
                              {{ resolveName(user) }}
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
                  <small v-if="form.errors.content" class="text-danger d-block mt-2">
                    {{ form.errors.content }}
                  </small>
                  <div class="d-flex justify-content-end mt-3">
                    <BButton
                      variant="primary"
                      size="sm"
                      :disabled="!newComment.trim() || commentSubmitting"
                      @click="submitComment"
                    >
                      <i class="ri-send-plane-line me-1"></i>
                      {{ commentSubmitting ? "Posting..." : "Post Comment" }}
                    </BButton>
                  </div>
                </div>
              </div>
            </BCardBody>
          </BCard>

          <div v-if="sortedComments.length" class="d-grid gap-3">
            <BCard
              v-for="comment in sortedComments"
              :key="comment.id"
              no-body
              class="bg-light-subtle shadow-none border"
            >
              <BCardBody class="p-3">
                <div class="d-flex align-items-start gap-3">
                  <img
                    :src="resolveAvatar(comment.user)"
                    :alt="resolveName(comment.user)"
                    class="rounded-circle border floating-chat-avatar flex-shrink-0"
                    :style="avatarStyle"
                  />
                  <div class="flex-grow-1">
                    <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-2">
                      <div class="fw-semibold text-body">
                        {{ resolveName(comment.user) }}
                      </div>
                      <small class="text-muted">
                        {{ formatDate(comment.created_at) }}
                      </small>
                    </div>
                    <div
                      class="small text-body mb-0"
                      :style="messageStyle"
                      v-html="renderCommentContent(comment.content)"
                    ></div>
                  </div>
                </div>
              </BCardBody>
            </BCard>
          </div>

          <BCard
            v-else
            no-body
            class="bg-light-subtle shadow-none border"
          >
            <BCardBody class="px-4 py-5 text-center text-muted">
              <i class="ri-chat-smile-2-line d-block fs-2 text-primary mb-3"></i>
              <div class="fw-semibold text-body">No comments yet</div>
              <div class="small">Start the conversation for this procurement request.</div>
            </BCardBody>
          </BCard>
        </div>

        <div v-else class="px-4 py-5 text-center text-muted">
          <i class="ri-message-3-line d-block fs-2 text-primary mb-3"></i>
          <div class="fw-semibold text-body">Pick a request first</div>
          <div class="small">
            Use the dropdown above or click the chat button from a request row.
          </div>
        </div>
      </BCardBody>
    </BCard>

    <BButton
      variant="primary"
      class="rounded-circle shadow-lg position-relative d-inline-flex align-items-center justify-content-center border-0 p-0"
      :style="triggerStyle"
      :title="open ? 'Close request chat' : 'Open request chat'"
      @click="$emit('toggle')"
    >
      <i class="ri-chat-1-line fs-4"></i>
      <BBadge
        v-if="commentCount > 0"
        bg-variant="danger"
        pill
        class="position-absolute top-0 start-100 translate-middle"
      >
        {{ commentCount }}
      </BBadge>
    </BButton>
  </div>
</template>

<script>
import axios from "axios";
import { useForm } from "@inertiajs/vue3";

export default {
  props: [
    "requests",
    "selectedRequestId",
    "selectedRequest",
    "loading",
    "requestsLoading",
    "open",
  ],
  emits: ["toggle", "select-request", "comment-added"],
  data() {
    return {
      newComment: "",
      requestSearch: "",
      activeSort: "recent",
      activeVisibleCount: 8,
      searchVisibleCount: 12,
      commentSubmitting: false,
      subscribedRequestId: null,
      mentionStartIndex: null,
      mentionQuery: "",
      activeMentionIndex: 0,
      mentionSearchResults: [],
      mentionSearchLoading: false,
      mentionSearchTimer: null,
      mentionSearchRequestId: 0,
      form: useForm({
        content: "",
      }),
    };
  },
  computed: {
    normalizedSelectedRequestId() {
      return this.selectedRequestId ? Number(this.selectedRequestId) : null;
    },
    hasRequestSearch() {
      return Boolean(this.requestSearch.trim());
    },
    requestOptions() {
      const requests = Array.isArray(this.requests) ? this.requests : [];

      return requests.map((request) => ({
        ...request,
        latest_comment_at: request.latest_comment_at || null,
        latest_comment: request.latest_comment || null,
        searchableText: [
          request.code,
          request.purpose,
          request.title,
        ]
          .filter(Boolean)
          .join(" ")
          .toLowerCase(),
      }));
    },
    activeRequestOptions() {
      return this.sortRequestOptions(
        this.requestOptions.filter((request) => this.hasComments(request.comments_count)),
        this.activeSort,
      );
    },
    filteredRequestOptions() {
      if (!this.hasRequestSearch) {
        return [];
      }

      const keyword = this.requestSearch.trim().toLowerCase();

      return this.sortRequestOptions(
        this.requestOptions.filter((request) =>
          request.searchableText.includes(keyword),
        ),
        this.activeSort,
      );
    },
    requestSelectionOptions() {
      return this.hasRequestSearch ? this.filteredRequestOptions : this.activeRequestOptions;
    },
    displayedRequestOptions() {
      return this.requestSelectionOptions.slice(0, this.currentRequestVisibleCount);
    },
    totalChatCount() {
      return this.requestOptions.reduce(
        (total, request) => total + (Number(request.comments_count) || 0),
        0,
      );
    },
    currentRequestVisibleCount() {
      return this.hasRequestSearch ? this.searchVisibleCount : this.activeVisibleCount;
    },
    hasMoreRequestOptions() {
      return this.displayedRequestOptions.length < this.requestSelectionOptions.length;
    },
    hiddenRequestCount() {
      return Math.max(this.requestSelectionOptions.length - this.displayedRequestOptions.length, 0);
    },
    nextRequestLoadCount() {
      const increment = this.hasRequestSearch ? 12 : 8;
      return Math.min(this.hiddenRequestCount, increment);
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

      appendUser(this.selectedRequest?.created_by);
      appendUser(this.selectedRequest?.requested_by);
      appendUser(this.selectedRequest?.approved_by);

      const comments = Array.isArray(this.selectedRequest?.comments)
        ? this.selectedRequest.comments
        : [];

      comments.forEach((comment) => appendUser(comment?.user));
      this.mentionSearchResults.forEach((user) => appendUser(user));

      return Array.from(users.values()).sort((left, right) =>
        this.resolveName(left).localeCompare(this.resolveName(right)),
      );
    },
    filteredMentionUsers() {
      const keyword = this.mentionQuery.trim().toLowerCase();

      return this.mentionUsers.filter((user) => {
        if (!keyword) {
          return true;
        }

        const searchableText = [
          user.username,
          this.resolveName(user),
        ]
          .filter(Boolean)
          .join(" ")
          .toLowerCase();

        return searchableText.includes(keyword);
      });
    },
    showMentionMenu() {
      return (
        this.selectedRequest &&
        this.mentionStartIndex !== null &&
        (this.filteredMentionUsers.length > 0 ||
          this.mentionSearchLoading ||
          this.mentionQuery.trim().length > 0)
      );
    },
    sortedComments() {
      const comments = Array.isArray(this.selectedRequest?.comments)
        ? [...this.selectedRequest.comments]
        : [];

      return comments.sort((left, right) => {
        return new Date(right.created_at) - new Date(left.created_at);
      });
    },
    commentCount() {
      return this.sortedComments.length;
    },
    panelStyle() {
      return {
        width: "420px",
        maxWidth: "calc(100vw - 1.5rem)",
      };
    },
    bodyStyle() {
      return {
        maxHeight: "min(72vh, 760px)",
      };
    },
    requestListStyle() {
      return {
        maxHeight: "320px",
      };
    },
    triggerStyle() {
      return {
        width: "64px",
        height: "64px",
      };
    },
    avatarStyle() {
      return {
        width: "40px",
        height: "40px",
        objectFit: "cover",
      };
    },
    mentionAvatarStyle() {
      return {
        width: "32px",
        height: "32px",
        objectFit: "cover",
      };
    },
    messageStyle() {
      return {
        whiteSpace: "pre-wrap",
        wordBreak: "break-word",
      };
    },
  },
  watch: {
    selectedRequestId: {
      immediate: true,
      handler(newId) {
        this.newComment = "";
        this.resetRequestSearch();
        this.resetMentionState();
        this.form.clearErrors();
        this.syncCommentChannel(newId ? Number(newId) : null);
      },
    },
    requestSearch() {
      this.searchVisibleCount = 12;
    },
    mentionQuery(newValue) {
      this.queueMentionSearch(newValue);
    },
  },
  beforeUnmount() {
    this.clearMentionSearchTimer();
    this.teardownCommentChannel();
  },
  methods: {
    truncateText(text, limit = 60) {
      if (!text) {
        return "No purpose provided";
      }

      return text.length > limit ? `${text.slice(0, limit)}...` : text;
    },
    resetRequestSearch() {
      this.requestSearch = "";
      this.searchVisibleCount = 12;
    },
    setActiveSort(mode) {
      if (this.activeSort === mode) {
        return;
      }

      this.activeSort = mode;
      this.activeVisibleCount = 8;
      this.searchVisibleCount = 12;
    },
    requestSelectionChanged(value) {
      const nextId = value ? Number(value) : null;
      this.$emit("select-request", nextId);
    },
    selectRequestOption(requestId) {
      if (Number(requestId) === this.normalizedSelectedRequestId) {
        this.clearSelectedRequest();
        return;
      }

      this.requestSelectionChanged(requestId);
      this.resetRequestSearch();
    },
    clearSelectedRequest() {
      this.requestSelectionChanged(null);
      this.resetRequestSearch();
    },
    resolveAvatar(user) {
      return user?.profile?.avatar || user?.avatar || "/images/avatars/avatar.jpg";
    },
    resolveName(user) {
      return (
        user?.profile?.fullname ||
        user?.profile?.full_name ||
        user?.name ||
        user?.username ||
        "User"
      );
    },
    formatDate(dateString) {
      const date = new Date(dateString);

      if (Number.isNaN(date.getTime())) {
        return "";
      }

      return date.toLocaleString("en-US", {
        year: "numeric",
        month: "short",
        day: "numeric",
        hour: "2-digit",
        minute: "2-digit",
      });
    },
    formatChatLabel(count) {
      const total = Number(count) || 0;
      return `${total} ${total === 1 ? "chat" : "chats"}`;
    },
    formatActivityDate(dateString) {
      const date = new Date(dateString);

      if (Number.isNaN(date.getTime())) {
        return "";
      }

      return `Last chat ${date.toLocaleString("en-US", {
        month: "short",
        day: "numeric",
        hour: "2-digit",
        minute: "2-digit",
      })}`;
    },
    hasComments(count) {
      return Number(count) > 0;
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
        .replace(/(@[A-Za-z0-9._-]+)/g, '<span class="chat-mention">$1</span>')
        .replace(/\n/g, "<br>");
    },
    latestChatAuthor(request) {
      const author = this.resolveName(request?.latest_comment?.user)
        || request?.latest_comment?.user?.name
        || request?.latest_comment?.user?.username;

      if (!author) {
        return "";
      }

      return `${author}:`;
    },
    latestChatPreview(request) {
      const preview = request?.latest_comment?.content;

      if (preview) {
        return this.truncateText(preview, 96);
      }

      return this.truncateText(request?.purpose || request?.title, 88);
    },
    showMoreRequests() {
      if (this.hasRequestSearch) {
        this.searchVisibleCount += 12;
        return;
      }

      this.activeVisibleCount += 8;
    },
    sortRequestOptions(requests, sortMode = "recent") {
      return [...requests].sort((left, right) => {
        const leftSelected = Number(left.id) === this.normalizedSelectedRequestId;
        const rightSelected = Number(right.id) === this.normalizedSelectedRequestId;

        if (leftSelected && !rightSelected) {
          return -1;
        }

        if (!leftSelected && rightSelected) {
          return 1;
        }

        const leftLatest = left.latest_comment_at ? new Date(left.latest_comment_at).getTime() : 0;
        const rightLatest = right.latest_comment_at ? new Date(right.latest_comment_at).getTime() : 0;
        const leftCount = Number(left.comments_count) || 0;
        const rightCount = Number(right.comments_count) || 0;

        if (sortMode === "popular") {
          if (rightCount !== leftCount) {
            return rightCount - leftCount;
          }

          if (rightLatest !== leftLatest) {
            return rightLatest - leftLatest;
          }

          return String(left.code || "").localeCompare(String(right.code || ""));
        }

        if (rightLatest !== leftLatest) {
          return rightLatest - leftLatest;
        }

        if (rightCount !== leftCount) {
          return rightCount - leftCount;
        }

        return String(left.code || "").localeCompare(String(right.code || ""));
      });
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

      const cursorIndex = textarea.selectionStart ?? this.newComment.length;
      const valueBeforeCursor = this.newComment.slice(0, cursorIndex);
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
        event.preventDefault();
        this.activeMentionIndex =
          (this.activeMentionIndex + 1) % this.filteredMentionUsers.length;
        return;
      }

      if (event.key === "ArrowUp") {
        event.preventDefault();
        this.activeMentionIndex =
          (this.activeMentionIndex - 1 + this.filteredMentionUsers.length) %
          this.filteredMentionUsers.length;
        return;
      }

      if (event.key === "Enter" && !event.shiftKey) {
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
      const cursorIndex = textarea?.selectionStart ?? this.newComment.length;
      const mentionText = `@${user.username} `;

      this.newComment = [
        this.newComment.slice(0, this.mentionStartIndex),
        mentionText,
        this.newComment.slice(cursorIndex),
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
    submitComment() {
      const content = this.newComment.trim();

      if (!content || this.commentSubmitting || !this.selectedRequest?.id) {
        return;
      }

      this.commentSubmitting = true;
      this.form.clearErrors();

      axios
        .post(
          `/faims/procurements/${this.selectedRequest.id}/comments`,
          { content },
          {
            headers: {
              Accept: "application/json",
              "X-Requested-With": "XMLHttpRequest",
            },
          }
        )
        .then((response) => {
          if (response.data?.data) {
            this.$emit("comment-added", response.data.data);
          }

          this.newComment = "";
          this.resetMentionState();
          this.form.reset();
        })
        .catch((error) => {
          const validationErrors = error.response?.data?.errors || {};
          this.form.setError(validationErrors);
        })
        .finally(() => {
          this.commentSubmitting = false;
        });
    },
    syncCommentChannel(requestId) {
      this.teardownCommentChannel();

      if (!requestId || !window.Echo) {
        return;
      }

      this.subscribedRequestId = requestId;
      window.Echo.private(`procurement.${requestId}`).listen(".comment.added", (event) => {
        if (event?.comment) {
          this.$emit("comment-added", event.comment);
        }
      });
    },
    teardownCommentChannel() {
      if (!this.subscribedRequestId || !window.Echo) {
        this.subscribedRequestId = null;
        return;
      }

      window.Echo.leave(`procurement.${this.subscribedRequestId}`);
      this.subscribedRequestId = null;
    },
  },
};
</script>
<style scoped>
.floating-chat-surface {
  background-color: var(--vz-secondary-bg);
  color: var(--vz-body-color);
}

.floating-chat-avatar {
  background-color: var(--vz-secondary-bg);
}

.floating-chat-count-badge {
  background-color: var(--vz-danger);
  color: #fff;
}

.floating-chat-sort {
  flex-wrap: wrap;
}

.floating-chat-sort-btn {
  border: 1px solid var(--vz-border-color);
  background: var(--vz-secondary-bg);
  color: var(--vz-body-color);
  border-radius: 999px;
  padding: 0.2rem 0.65rem;
  font-size: 0.75rem;
  font-weight: 600;
  line-height: 1.4;
}

.floating-chat-sort-btn.active {
  background: rgba(var(--vz-primary-rgb), 0.14);
  border-color: rgba(var(--vz-primary-rgb), 0.24);
  color: var(--vz-primary);
}

.floating-chat-sort-btn:hover {
  background: var(--vz-tertiary-bg);
}

.floating-chat-show-more {
  background-color: var(--vz-tertiary-bg);
  border-color: var(--vz-border-color);
  color: var(--vz-body-color);
}

.floating-chat-show-more:hover,
.floating-chat-show-more:focus {
  background-color: rgba(var(--vz-primary-rgb), 0.12);
  border-color: rgba(var(--vz-primary-rgb), 0.24);
  color: var(--vz-primary);
}

.mention-picker {
  position: absolute;
  left: 0;
  right: 0;
  bottom: calc(100% + 0.5rem);
  z-index: 10;
  max-height: 220px;
  overflow-y: auto;
}

.mention-picker-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  width: 100%;
  padding: 0.625rem 0.75rem;
  border: 0;
  background: transparent;
}

.mention-picker-item:hover,
.mention-picker-item.active {
  background: rgba(var(--vz-primary-rgb), 0.08);
}

:deep(.chat-mention) {
  display: inline-block;
  padding: 0 0.35rem;
  border-radius: 999px;
  background: rgba(var(--vz-primary-rgb), 0.12);
  color: var(--vz-primary);
  font-weight: 700;
}

[data-bs-theme="dark"] .floating-chat-count-badge {
  background-color: var(--vz-danger);
  color: #fff;
}
</style>
