<template>
  <div class="position-fixed bottom-0 end-0 p-3 p-md-4" style="z-index: 1050;">
    <BCard
      v-if="open"
      no-body
      class="shadow-lg border-0 overflow-hidden mb-3 ms-auto floating-chat-panel"
      :style="panelStyle"
    >
      <BCardHeader class="border-0 text-white p-3 floating-chat-header">
        <div class="d-flex align-items-start justify-content-between gap-3">
          <div class="flex-grow-1 overflow-hidden d-flex align-items-center gap-3">
            <div class="floating-chat-header-icon rounded-circle d-inline-flex align-items-center justify-content-center flex-shrink-0">
              <i class="ri-messenger-line fs-5"></i>
            </div>
            <div class="flex-grow-1 overflow-hidden">
              <h5 class="mb-1 text-white">Comments</h5>
              <p class="mb-0 small text-white-50 text-truncate">
                {{ selectedRequest ? selectedRequest.code : "Choose a purchase request" }}
              </p>
            </div>
          </div>
          <BButton
            variant="light"
            size="sm"
            class="rounded-circle d-inline-flex align-items-center justify-content-center flex-shrink-0 p-0 floating-chat-close"
            style="width: 36px; height: 36px;"
            @click="$emit('toggle')"
          >
            <i class="ri-close-line text-primary"></i>
          </BButton>
        </div>
      </BCardHeader>

      <BCardBody class="p-0 overflow-hidden d-flex flex-column floating-chat-body" :style="bodyStyle">
        <div
          class="p-3 border-bottom floating-chat-sidebar"
          :class="{ 'pb-2': isRequestPickerCollapsed && selectedRequest }"
        >
          <div class="d-flex align-items-center justify-content-between gap-2 mb-3 floating-chat-sidebar-header">
            <label class="form-label mb-0 fw-semibold floating-chat-sidebar-title">Select Request</label>
            <div class="d-flex align-items-center gap-2 flex-shrink-0">
              <BButton
                v-if="selectedRequest"
                variant="light"
                size="sm"
                class="rounded-pill px-3"
                @click="isRequestPickerCollapsed = !isRequestPickerCollapsed"
              >
                {{ isRequestPickerCollapsed ? "Show PRs" : "Hide PRs" }}
              </BButton>
              <BButton
                v-if="selectedRequest"
                variant="light"
                size="sm"
                class="rounded-pill px-3"
                @click="clearSelectedRequest"
              >
                Unselect PR
              </BButton>
            </div>
          </div>
          <div
            v-show="!isRequestPickerCollapsed || !selectedRequest"
            class="border rounded-4 floating-chat-surface overflow-hidden floating-chat-request-shell"
          >
            <div class="p-3 border-bottom floating-chat-searchbar">
              <div class="d-flex align-items-center gap-2">
                <BInputGroup size="sm" class="flex-grow-1 floating-chat-input-group">
                  <BInputGroupText class="floating-chat-search-icon">
                    <i class="ri-search-line"></i>
                  </BInputGroupText>
                  <BFormInput
                    v-model="requestSearch"
                    type="search"
                    class="floating-chat-search-input"
                    placeholder="Search by PR code or purpose"
                  />
                </BInputGroup>
                <BButton
                  v-if="hasRequestSearch"
                  variant="light"
                  size="sm"
                  class="flex-shrink-0 rounded-pill px-3"
                  @click="resetRequestSearch"
                >
                  Clear
                </BButton>
              </div>
              <div v-if="requestOptions.length" class="d-flex flex-wrap align-items-center gap-2 mt-3">
                <BBadge pill class="floating-chat-stat-badge floating-chat-stat-badge-primary">
                  {{ activeRequestOptions.length }} active PR{{ activeRequestOptions.length === 1 ? "" : "s" }}
                </BBadge>
                <BBadge pill class="floating-chat-stat-badge floating-chat-stat-badge-info">
                  {{ totalChatCount }} total {{ totalChatCount === 1 ? "comment" : "comments" }}
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
                    Most Comments
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
                {{ hasRequestSearch ? "No matching procurement request found." : "No active comments yet" }}
              </div>
              <div>
                {{
                  hasRequestSearch
                    ? "Try another PR code or purpose."
                    : "Search for a procurement request above to start or review a conversation."
                }}
              </div>
            </div>

            <div
              v-else-if="hasRequestSearch"
              class="px-3 py-3 border-bottom floating-chat-request-heading"
            >
              <div class="small fw-semibold text-body">
                {{ `Search Results (${displayedRequestOptions.length})` }}
              </div>
              <div class="small text-muted">
                Showing matching PRs from all procurement requests.
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
                class="text-start py-3 floating-chat-request-item"
                :active="Number(request.id) === normalizedSelectedRequestId"
                @click="selectRequestOption(request.id)"
              >
                <div class="d-flex align-items-start gap-3">
                  <div class="floating-chat-request-avatar rounded-circle d-inline-flex align-items-center justify-content-center flex-shrink-0">
                    <i class="ri-file-list-3-line"></i>
                  </div>
                  <div class="flex-grow-1 min-w-0">
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
                  </div>
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
                class="w-100 floating-chat-show-more rounded-pill"
                @click="showMoreRequests"
              >
                Show {{ nextRequestLoadCount }} more {{ hasRequestSearch ? "results" : "PR comments" }}
              </BButton>
            </div>
          </div>
        </div>

        <div v-if="loading" class="px-4 py-5 text-center text-muted floating-chat-empty-state">
          <div class="spinner-border text-primary mb-3" role="status"></div>
          <div class="fw-semibold text-body">Loading request conversation...</div>
        </div>

        <div
          v-else-if="selectedRequest"
          class="pt-3 px-3 pb-0 d-flex flex-column flex-grow-1 overflow-hidden floating-chat-conversation"
        >
          <BCard no-body class="shadow-none border mb-3 floating-chat-conversation-header">
            <BCardBody :class="isConversationHeaderCollapsed ? 'p-2' : 'p-3'">
              <div
                class="d-flex gap-3"
                :class="isConversationHeaderCollapsed ? 'align-items-center' : 'align-items-start'"
              >
                <div
                  class="floating-chat-request-avatar rounded-circle d-inline-flex align-items-center justify-content-center flex-shrink-0"
                  :class="{ 'floating-chat-request-avatar-compact': isConversationHeaderCollapsed }"
                >
                  <i class="ri-file-list-3-line"></i>
                </div>
                <div class="flex-grow-1 min-w-0">
                  <div class="d-flex align-items-center justify-content-between gap-2">
                    <div class="d-flex flex-wrap align-items-center gap-2 min-w-0">
                      <div class="fw-bold text-body text-truncate">{{ selectedRequest.code }}</div>
                      <span class="floating-chat-online-pill">active comment</span>
                    </div>
                    <BButton
                      variant="light"
                      size="sm"
                      class="rounded-pill px-3 flex-shrink-0"
                      @click="isConversationHeaderCollapsed = !isConversationHeaderCollapsed"
                    >
                      {{ isConversationHeaderCollapsed ? "Show" : "Hide" }}
                      <i
                        class="ms-1"
                        :class="isConversationHeaderCollapsed ? 'ri-arrow-down-s-line' : 'ri-arrow-up-s-line'"
                      ></i>
                    </BButton>
                  </div>
                  <div v-if="!isConversationHeaderCollapsed" class="mt-2">
                    <p class="mb-3 small text-muted">
                      {{ selectedRequest.purpose || selectedRequest.title || "No purpose provided." }}
                    </p>
                    <div class="d-flex flex-wrap gap-2">
                      <BBadge pill class="floating-chat-stat-badge floating-chat-stat-badge-primary">
                        {{ selectedRequest.status?.name || "No status" }}
                      </BBadge>
                      <BBadge
                        v-if="selectedRequest.sub_status?.name"
                        pill
                        class="floating-chat-stat-badge floating-chat-stat-badge-info"
                      >
                        {{ selectedRequest.sub_status.name }}
                      </BBadge>
                    </div>
                  </div>
                </div>
              </div>
            </BCardBody>
          </BCard>

          <div
            v-if="sortedComments.length"
            ref="thread_container"
            class="d-grid gap-3 flex-grow-1 overflow-auto floating-chat-thread pe-1"
          >
              <div
                class="floating-chat-thread-inner d-grid gap-3"
              >
                <div
                  v-for="comment in sortedComments"
                  :key="comment.id"
                  class="d-flex align-items-end gap-2"
                  :class="is_own_comment(comment) ? 'justify-content-end' : 'justify-content-start'"
                >
                  <img
                    v-if="!is_own_comment(comment)"
                    :src="resolveAvatar(comment.user)"
                    :alt="resolveName(comment.user)"
                    class="rounded-circle border floating-chat-avatar flex-shrink-0 align-self-start"
                    :style="avatarStyle"
                  />
                  <div
                    class="floating-chat-message-wrap d-flex flex-column"
                    :class="is_own_comment(comment) ? 'ms-2 align-items-end' : 'me-2 align-items-start'"
                  >
                    <div
                      class="small text-muted mb-1 d-flex flex-wrap align-items-center gap-2"
                      :class="is_own_comment(comment) ? 'justify-content-end' : 'justify-content-start'"
                    >
                      <span class="fw-semibold text-body">{{ resolveName(comment.user) }}</span>
                      <span>{{ formatDate(comment.created_at) }}</span>
                    </div>
                    <div
                      class="floating-chat-bubble"
                      :class="is_own_comment(comment) ? 'floating-chat-bubble-own' : 'floating-chat-bubble-other'"
                    >
                      <div
                        class="small mb-0"
                        :style="messageStyle"
                        v-html="renderCommentContent(comment.content)"
                      ></div>
                    </div>
                  </div>
                  <img
                    v-if="is_own_comment(comment)"
                    :src="resolveAvatar(comment.user)"
                    :alt="resolveName(comment.user)"
                    class="rounded-circle border floating-chat-avatar flex-shrink-0 align-self-start"
                    :style="avatarStyle"
                  />
                </div>
              </div>
          </div>

          <BCard
            v-else
            no-body
            class="shadow-none border flex-grow-1 floating-chat-empty-state"
          >
            <BCardBody class="px-4 py-5 text-center text-muted">
              <i class="ri-chat-smile-2-line d-block fs-2 text-primary mb-3"></i>
              <div class="fw-semibold text-body">No comments yet</div>
              <div class="small">Start the conversation for this procurement request.</div>
            </BCardBody>
          </BCard>

          <BCard
            no-body
            class="shadow-none border mt-3 mb-0 flex-shrink-0 floating-chat-composer"
          >
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
                      class="floating-chat-textarea"
                      placeholder="Comment here..."
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
        </div>

        <div v-else class="px-4 py-5 text-center text-muted floating-chat-empty-state m-3">
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
      :title="open ? 'Close request comment' : 'Open request comment'"
      @click="$emit('toggle')"
    >
      <i class="ri-chat-1-line fs-4"></i>
      <BBadge
        v-if="commentCount > 0"
        pill
        class="position-absolute top-0 start-100 translate-middle bg-danger text-white"
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
      isRequestPickerCollapsed: false,
      commentSubmitting: false,
      isConversationHeaderCollapsed: false,
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
        return new Date(left.created_at) - new Date(right.created_at);
      });
    },
    commentCount() {
      return this.sortedComments.length;
    },
    panelStyle() {
      return {
        width: "720px",
        maxWidth: "calc(100vw - 1rem)",
      };
    },
    bodyStyle() {
      return {
        height: "min(88vh, 980px)",
        maxHeight: "min(88vh, 980px)",
      };
    },
    requestListStyle() {
      return {
        maxHeight: this.selectedRequest ? "220px" : "320px",
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
        this.isRequestPickerCollapsed = Boolean(newId);
        this.syncCommentChannel(newId ? Number(newId) : null);

        this.$nextTick(() => {
          this.scroll_thread_to_bottom("auto");
        });
      },
    },
    commentCount(new_count, old_count) {
      if (!new_count) {
        return;
      }

      const behavior = old_count ? "smooth" : "auto";

      this.$nextTick(() => {
        this.scroll_thread_to_bottom(behavior);
      });
    },
    loading(is_loading) {
      if (is_loading) {
        return;
      }

      this.$nextTick(() => {
        this.scroll_thread_to_bottom("auto");
      });
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
    is_own_comment(comment) {
      const current_user_id = Number(this.$page?.props?.user?.data?.id || 0);
      return Number(comment?.user?.id || 0) === current_user_id;
    },
    scroll_thread_to_bottom(behavior = "auto") {
      const thread_element = this.$refs.thread_container;

      if (!thread_element) {
        return;
      }

      thread_element.scrollTo({
        top: thread_element.scrollHeight,
        behavior,
      });
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
      return `${total} ${total === 1 ? "comment" : "comments"}`;
    },
    formatActivityDate(dateString) {
      const date = new Date(dateString);

      if (Number.isNaN(date.getTime())) {
        return "";
      }

      return `Last comment ${date.toLocaleString("en-US", {
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
.floating-chat-panel,
.floating-chat-header,
.floating-chat-body,
.floating-chat-sidebar,
.floating-chat-searchbar,
.floating-chat-close,
.floating-chat-stat-badge-primary,
.floating-chat-request-avatar,
.floating-chat-count-badge,
.floating-chat-sort-btn.active,
.floating-chat-request-item.active,
.floating-chat-bubble-own,
:deep(.chat-mention) {
  --request-chat-accent: #3f4f8a;
  --request-chat-accent-strong: #2e3c70;
  --request-chat-accent-soft: rgba(63, 79, 138, 0.12);
  --request-chat-accent-soft-2: rgba(63, 79, 138, 0.2);
  --request-chat-accent-border: rgba(63, 79, 138, 0.22);
  --request-chat-mention-bg: #ffe36a;
  --request-chat-mention-color: #5f4700;
}

.floating-chat-panel {
  border-radius: 1.5rem;
  background:
    linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(247, 249, 253, 0.98)),
    var(--vz-body-bg);
}

.floating-chat-header {
  background: linear-gradient(135deg, #4c5fa3 0%, #34457d 100%);
}

.floating-chat-header-icon {
  width: 2.75rem;
  height: 2.75rem;
  background: rgba(255, 255, 255, 0.14);
  color: #fff;
}

.floating-chat-close {
  box-shadow: 0 0.5rem 1rem rgba(79, 93, 155, 0.22);
}

.floating-chat-body {
  background:
    radial-gradient(circle at top right, rgba(63, 79, 138, 0.08), transparent 36%),
    linear-gradient(180deg, #f8faff, #f1f4fb);
}

.floating-chat-sidebar {
  position: relative;
  background: rgba(63, 79, 138, 0.035);
}

.floating-chat-sidebar-header {
  position: sticky;
  top: 0;
  z-index: 3;
  flex-wrap: wrap;
  row-gap: 0.75rem;
  min-height: 2.5rem;
  padding-bottom: 0.35rem;
  background:
    linear-gradient(180deg, rgba(248, 250, 255, 0.98), rgba(248, 250, 255, 0.92));
}

.floating-chat-sidebar-title {
  line-height: 1.25;
  padding-top: 0.15rem;
}

.floating-chat-surface {
  background-color: var(--vz-secondary-bg);
  color: var(--vz-body-color);
}

.floating-chat-request-shell {
  background: rgba(255, 255, 255, 0.96);
  backdrop-filter: blur(12px);
  border-color: rgba(63, 79, 138, 0.08) !important;
}

.floating-chat-searchbar {
  background:
    linear-gradient(180deg, rgba(63, 79, 138, 0.05), rgba(63, 79, 138, 0.015)),
    #f8faff;
}

.floating-chat-input-group :deep(.input-group-text),
.floating-chat-input-group :deep(.form-control) {
  border-color: rgba(63, 79, 138, 0.1);
  background: #fff;
}

.floating-chat-input-group :deep(.input-group-text) {
  border-top-left-radius: 999px;
  border-bottom-left-radius: 999px;
}

.floating-chat-input-group :deep(.form-control) {
  border-top-right-radius: 999px;
  border-bottom-right-radius: 999px;
  box-shadow: none;
}

.floating-chat-input-group :deep(.form-control:focus) {
  border-color: var(--request-chat-accent-border);
  box-shadow: 0 0 0 0.2rem rgba(63, 79, 138, 0.08);
}

.floating-chat-search-icon {
  color: var(--request-chat-accent);
}

.floating-chat-stat-badge {
  font-size: 0.72rem;
  font-weight: 700;
  padding: 0.45rem 0.7rem;
}

.floating-chat-stat-badge-primary {
  background: #eef2ff;
  color: var(--request-chat-accent);
}

.floating-chat-stat-badge-info {
  background: #eef8ff;
  color: #2878b8;
}

.floating-chat-avatar {
  background-color: var(--vz-secondary-bg);
}

.floating-chat-request-avatar {
  width: 2.75rem;
  height: 2.75rem;
  background: linear-gradient(135deg, #eef2ff, #e4eafc);
  color: var(--request-chat-accent);
  border: 1px solid rgba(63, 79, 138, 0.1);
}

.floating-chat-count-badge {
  background: linear-gradient(135deg, var(--request-chat-accent), var(--request-chat-accent-strong));
  color: #fff;
  min-width: 1.65rem;
}

.floating-chat-sort {
  flex-wrap: wrap;
  background: #edf1f9;
  border-radius: 999px;
  padding: 0.2rem;
}

.floating-chat-sort-btn {
  border: 0;
  background: transparent;
  color: var(--vz-body-color);
  border-radius: 999px;
  padding: 0.4rem 0.9rem;
  font-size: 0.75rem;
  font-weight: 600;
  line-height: 1.4;
}

.floating-chat-sort-btn.active {
  background: #fff;
  color: var(--request-chat-accent);
  box-shadow: 0 0.2rem 0.7rem rgba(46, 60, 112, 0.12);
}

.floating-chat-sort-btn:hover {
  background: rgba(255, 255, 255, 0.9);
}

.floating-chat-request-heading {
  background: #f8faff;
}

.floating-chat-request-item {
  border: 0 !important;
  border-bottom: 1px solid rgba(63, 79, 138, 0.08) !important;
  background: transparent;
  transition: background-color 0.2s ease, transform 0.2s ease;
}

.floating-chat-request-item:hover {
  background: #f5f8ff;
}

.floating-chat-request-item.active {
  background: linear-gradient(135deg, #5368ae, #3a4a83) !important;
  color: #fff !important;
}

.floating-chat-request-item.active .floating-chat-request-avatar {
  background: rgba(255, 255, 255, 0.16);
  border-color: rgba(255, 255, 255, 0.24);
  color: #fff;
}

.floating-chat-show-more {
  background-color: #f1f5ff;
  border-color: rgba(63, 79, 138, 0.12);
  color: var(--request-chat-accent);
}

.floating-chat-show-more:hover,
.floating-chat-show-more:focus {
  background-color: #e8efff;
  border-color: rgba(63, 79, 138, 0.18);
  color: var(--request-chat-accent-strong);
}

.floating-chat-conversation {
  display: flex;
  flex: 1 1 auto;
  flex-direction: column;
  background:
    radial-gradient(circle at top left, rgba(63, 79, 138, 0.05), transparent 34%),
    transparent;
  min-height: 0;
}

.floating-chat-conversation-header {
  background: rgba(255, 255, 255, 0.94);
  backdrop-filter: blur(12px);
  border-color: rgba(63, 79, 138, 0.08) !important;
}

.floating-chat-request-avatar-compact {
  width: 2.3rem;
  height: 2.3rem;
}

.floating-chat-composer {
  background: #ffffff;
  z-index: 2;
  border-color: rgba(63, 79, 138, 0.08) !important;
  box-shadow: 0 -0.35rem 1.25rem rgba(15, 23, 42, 0.08);
}

.floating-chat-composer :deep(.card-body) {
  background: #ffffff;
}

.floating-chat-thread {
  flex: 1 1 auto;
  min-height: 0;
  scroll-behavior: smooth;
  padding: 0.5rem 0.35rem 0.75rem;
}

.floating-chat-thread-inner {
  min-height: 0;
}

.floating-chat-message-wrap {
  max-width: min(92%, 560px);
}

.floating-chat-bubble {
  width: 100%;
  padding: 0.875rem 1rem;
  border: 1px solid rgba(63, 79, 138, 0.08);
  box-shadow: 0 0.25rem 1rem rgba(15, 23, 42, 0.04);
}

.floating-chat-bubble-own {
  background: linear-gradient(135deg, #5168b7, #3c4d8e);
  border-color: transparent;
  color: #fff;
  border-radius: 1.1rem 1.1rem 0.35rem 1.1rem;
}

.floating-chat-bubble-other {
  background: #ffffff;
  color: #24314d;
  border-radius: 1.1rem 1.1rem 1.1rem 0.35rem;
}

.floating-chat-textarea :deep(textarea) {
  min-height: 90px;
  border-radius: 1rem;
  border-color: rgba(63, 79, 138, 0.12);
  background-color: #fff;
  box-shadow: none;
  resize: none;
}

.floating-chat-textarea :deep(textarea:focus) {
  border-color: rgba(63, 79, 138, 0.28);
  box-shadow: 0 0 0 0.2rem rgba(63, 79, 138, 0.08);
}

.floating-chat-online-pill {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.25rem 0.55rem;
  border-radius: 999px;
  background: rgba(49, 208, 88, 0.12);
  color: #1f9d45;
  font-size: 0.7rem;
  font-weight: 700;
}

.floating-chat-online-pill::before {
  content: "";
  width: 0.45rem;
  height: 0.45rem;
  border-radius: 50%;
  background: #31d058;
}

.floating-chat-empty-state {
  background: rgba(255, 255, 255, 0.86);
  border-radius: 1.25rem;
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
  background: #f7f9ff;
}

:deep(.chat-mention) {
  display: inline-block;
  padding: 0.05rem 0.45rem;
  border-radius: 0.55rem;
  background: var(--request-chat-mention-bg);
  color: var(--request-chat-mention-color);
  font-weight: 700;
  box-shadow: inset 0 0 0 1px rgba(95, 71, 0, 0.08);
}

[data-bs-theme="dark"] .floating-chat-count-badge {
  background-color: var(--vz-danger);
  color: #fff;
}

[data-bs-theme="dark"] .floating-chat-panel,
[data-bs-theme="dark"] .floating-chat-conversation-header,
[data-bs-theme="dark"] .floating-chat-request-shell,
[data-bs-theme="dark"] .floating-chat-empty-state {
  backdrop-filter: none;
}

[data-bs-theme="dark"] .floating-chat-bubble {
  box-shadow: none;
}
</style>
