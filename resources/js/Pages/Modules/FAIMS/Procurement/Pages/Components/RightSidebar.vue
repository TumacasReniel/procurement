<template>
  <div class="floating-comments-wrapper">
    <button
      v-if="isRightCollapsed"
      class="floating-comment-trigger"
      type="button"
      @click="toggleRightSidebar"
      :title="isRightCollapsed ? 'Open comments' : 'Close comments'"
    >
      <i class="ri-chat-1-line"></i>
      <span v-if="commentCount > 0" class="badge bg-danger floating-comment-badge">{{ commentCount }}</span>
    </button>

    <transition name="comment-panel">
      <div v-if="!isRightCollapsed" class="floating-comment-panel card shadow-lg border-0">
        <div class="card-header bg-gradient-primary text-white border-0 d-flex align-items-center justify-content-between floating-comment-header">
          <h5 class="card-title mb-0">
            <span class="position-relative me-2">
              <i class="ri-chat-1-line"></i>
              <span v-if="commentCount > 0" class="badge bg-danger position-absolute top-0 start-100 translate-middle" style="font-size: 0.7rem; padding: 0.2rem 0.4rem;">{{ commentCount }}</span>
            </span>
            <span>Comments</span>
          </h5>
          <button
            @click="toggleRightSidebar"
            class="btn btn-sm btn-light rounded-circle p-2 ms-2"
            style="width: 36px; height: 36px"
            type="button"
          >
            <i class="ri-close-line text-primary fs-6"></i>
          </button>
        </div>
        <div class="card-body p-0 overflow-hidden d-flex flex-column floating-comment-body">
          <div class="px-3 pt-3">
            <div class="border rounded-4 p-3 floating-comment-summary">
              <div class="d-flex align-items-start justify-content-between gap-3">
                <div class="min-w-0">
                  <div class="fw-bold text-body text-truncate">
                    {{ procurement?.code || "Procurement Request" }}
                  </div>
                  <p class="mb-0 small text-muted">
                    {{
                      procurement?.purpose ||
                      procurement?.title ||
                      "Overview conversation across procurement stages."
                    }}
                  </p>
                </div>
                <span class="floating-comment-online-pill">overview</span>
              </div>
              <div class="d-flex flex-wrap gap-2 mt-3">
                <span class="floating-comment-stat-badge floating-comment-stat-badge-primary">
                  {{ commentCount }} total {{ commentCount === 1 ? "comment" : "comments" }}
                </span>
                <span
                  v-if="procurement?.status?.name"
                  class="floating-comment-stat-badge floating-comment-stat-badge-info"
                >
                  {{ procurement.status.name }}
                </span>
              </div>
            </div>
          </div>

          <div
            v-if="sortedComments.length"
            ref="thread_container"
            class="px-3 py-3 d-grid gap-3 flex-grow-1 overflow-auto floating-comment-thread"
          >
            <div class="floating-comment-thread-inner d-grid gap-3">
              <div
                v-for="comment in sortedComments"
                :key="comment.id"
                class="d-flex align-items-end gap-2"
                :class="isOwnComment(comment) ? 'justify-content-end' : 'justify-content-start'"
              >
                <template v-if="!isOwnComment(comment)">
                  <img
                    v-if="resolveAvatar(comment.user)"
                    :src="resolveAvatar(comment.user)"
                    :alt="resolveName(comment.user)"
                    class="rounded-circle border floating-comment-avatar flex-shrink-0 align-self-start"
                  />
                  <div
                    v-else
                    class="rounded-circle border floating-comment-avatar floating-comment-avatar-fallback flex-shrink-0 align-self-start"
                  >
                    {{ resolveInitials(comment.user) }}
                  </div>
                </template>

                <div
                  class="floating-comment-message-wrap d-flex flex-column"
                  :class="isOwnComment(comment) ? 'ms-2 align-items-end' : 'me-2 align-items-start'"
                >
                  <div
                    class="small text-muted mb-1 d-flex flex-wrap align-items-center gap-2"
                    :class="isOwnComment(comment) ? 'justify-content-end' : 'justify-content-start'"
                  >
                    <span class="fw-semibold text-body">{{ resolveName(comment.user) }}</span>
                    <span class="floating-comment-source-badge">{{ comment.source }}</span>
                    <span>{{ formatDate(comment.created_at) }}</span>
                  </div>
                  <div
                    class="floating-comment-bubble"
                    :class="isOwnComment(comment) ? 'floating-comment-bubble-own' : 'floating-comment-bubble-other'"
                  >
                    <div
                      class="small mb-0"
                      v-html="renderCommentContent(comment.content)"
                    ></div>
                  </div>

                  <div
                    v-if="comment.replies && comment.replies.length"
                    class="d-grid gap-2 mt-2 floating-comment-replies"
                  >
                    <div
                      v-for="reply in comment.replies"
                      :key="`${comment.id}-${reply.id}`"
                      class="d-flex align-items-end gap-2"
                      :class="isOwnComment(reply) ? 'justify-content-end' : 'justify-content-start'"
                    >
                      <template v-if="!isOwnComment(reply)">
                        <img
                          v-if="resolveAvatar(reply.user)"
                          :src="resolveAvatar(reply.user)"
                          :alt="resolveName(reply.user)"
                          class="rounded-circle border floating-comment-avatar floating-comment-avatar-sm flex-shrink-0 align-self-start"
                        />
                        <div
                          v-else
                          class="rounded-circle border floating-comment-avatar floating-comment-avatar-sm floating-comment-avatar-fallback flex-shrink-0 align-self-start"
                        >
                          {{ resolveInitials(reply.user) }}
                        </div>
                      </template>

                      <div
                        class="floating-comment-message-wrap d-flex flex-column"
                        :class="isOwnComment(reply) ? 'ms-2 align-items-end' : 'me-2 align-items-start'"
                      >
                        <div
                          class="small text-muted mb-1 d-flex flex-wrap align-items-center gap-2"
                          :class="isOwnComment(reply) ? 'justify-content-end' : 'justify-content-start'"
                        >
                          <span class="fw-semibold text-body">{{ resolveName(reply.user) }}</span>
                          <span>{{ formatDate(reply.created_at) }}</span>
                        </div>
                        <div
                          class="floating-comment-bubble floating-comment-bubble-reply"
                          :class="isOwnComment(reply) ? 'floating-comment-bubble-own' : 'floating-comment-bubble-other'"
                        >
                          <div
                            class="small mb-0"
                            v-html="renderCommentContent(reply.content)"
                          ></div>
                        </div>
                      </div>

                      <template v-if="isOwnComment(reply)">
                        <img
                          v-if="resolveAvatar(reply.user)"
                          :src="resolveAvatar(reply.user)"
                          :alt="resolveName(reply.user)"
                          class="rounded-circle border floating-comment-avatar floating-comment-avatar-sm flex-shrink-0 align-self-start"
                        />
                        <div
                          v-else
                          class="rounded-circle border floating-comment-avatar floating-comment-avatar-sm floating-comment-avatar-fallback flex-shrink-0 align-self-start"
                        >
                          {{ resolveInitials(reply.user) }}
                        </div>
                      </template>
                    </div>
                  </div>
                </div>

                <template v-if="isOwnComment(comment)">
                  <img
                    v-if="resolveAvatar(comment.user)"
                    :src="resolveAvatar(comment.user)"
                    :alt="resolveName(comment.user)"
                    class="rounded-circle border floating-comment-avatar flex-shrink-0 align-self-start"
                  />
                  <div
                    v-else
                    class="rounded-circle border floating-comment-avatar floating-comment-avatar-fallback flex-shrink-0 align-self-start"
                  >
                    {{ resolveInitials(comment.user) }}
                  </div>
                </template>
              </div>
            </div>
          </div>

          <div v-else class="px-3 py-3 flex-grow-1 d-flex">
            <div class="border rounded-4 w-100 px-4 py-5 text-center text-muted floating-comment-empty-state">
              <i class="ri-chat-smile-2-line d-block fs-2 text-primary mb-3"></i>
              <div class="fw-semibold text-body">No comments yet</div>
              <div class="small">Start the conversation for this procurement overview.</div>
            </div>
          </div>

          <div class="px-3 pb-0 flex-shrink-0">
            <div class="card shadow-none border floating-comment-composer">
              <div class="card-body p-3">
                <div class="d-flex align-items-start gap-3">
                  <img
                    v-if="currentUserAvatar"
                    :src="currentUserAvatar"
                    :alt="currentUserName"
                    class="rounded-circle border floating-comment-avatar flex-shrink-0"
                  />
                  <div
                    v-else
                    class="rounded-circle border floating-comment-avatar floating-comment-avatar-fallback flex-shrink-0"
                  >
                    {{ currentUserInitials }}
                  </div>

                  <div class="flex-grow-1">
                    <textarea
                      v-model="newComment"
                      class="form-control floating-comment-textarea"
                      rows="3"
                      placeholder="Comment here..."
                      :disabled="commentSubmitting"
                    ></textarea>
                    <small v-if="form.errors.content" class="text-danger d-block mt-2">
                      {{ form.errors.content }}
                    </small>
                    <div class="d-flex justify-content-end mt-3">
                      <button
                        @click="submitComment"
                        :disabled="!newComment.trim() || commentSubmitting"
                        class="btn btn-primary"
                      >
                        <i class="ri-send-plane-line me-1"></i>
                        {{ commentSubmitting ? "Posting..." : "Post Comment" }}
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </transition>
    <b-modal
      v-model="showStatusTipModal"
      header-class="p-3 bg-light"
      :title="statusTipTitle"
      centered
      hide-footer
  >
    <div class="status-tip-body">
      <div v-if="!isEmployeeOnlyRole" class="status-tip-subtitle">{{ statusTipSubtitle }}</div>
      <ul v-if="!isEmployeeOnlyRole" class="status-tip-steps">
        <li v-for="(step, idx) in statusTipSteps" :key="`${step}-${idx}`">{{ step }}</li>
      </ul>
      <div class="status-tip-assigned">
        <strong>Assigned Personnel:</strong>
        <div v-if="statusTipAssigned.length" class="status-tip-badges">
          <span
            v-for="(person, idx) in statusTipAssigned"
            :key="`${person}-${idx}`"
            class="badge rounded-pill bg-primary-subtle text-primary status-tip-person"
          >
            {{ person }}
          </span>
        </div>
        <span v-else> - </span>
      </div>
      <div v-if="isEmployeeOnlyRole" class="status-tip-note">
        Note: If this procurement process is taking longer, please check with the assigned personnel to know the reason, or leave a comment for follow-up.
      </div>
    </div>
  </b-modal>
  </div>
</template>

<script>
import { useForm } from "@inertiajs/vue3";
import axios from "axios";

export default {
  props: ["procurement", "logs", "isRightCollapsed"],
  emits: ["toggleRightSidebar"],
  data() {
    return {
      activeRightTab: 1,
      newComment: '',
      commentSubmitting: false,
      form: useForm({
        content: '',
      }),
      showStatusTipModal: false,
      statusTipTitle: "Status Guide",
      statusTipSubtitle: "",
      statusTipSteps: [],
      statusTipAssigned: [],
    };
  },
  computed: {
    currentUser() {
      return this.$page?.props?.user?.data || {};
    },
    currentRoles() {
      return Array.isArray(this.$page?.props?.roles) ? this.$page.props.roles : [];
    },
    hasProcurementWorkflowRole() {
      return this.currentRoles.some((role) =>
        ["Procurement Staff", "Procurement Officer", "Administrator"].includes(role)
      );
    },
    isEmployeeOnlyRole() {
      return !this.hasProcurementWorkflowRole;
    },
    logsCount() {
      return this.logs ? this.logs.length : 0;
    },
    sortedComments() {
      let allComments = [];

      // Add procurement comments
      if (this.procurement.comments) {
        allComments = allComments.concat(this.procurement.comments.map(comment => ({ ...comment, source: 'Procurement' })));
      }

      // Add quotation comments
      if (this.procurement.quotations) {
        this.procurement.quotations.forEach(quotation => {
          if (quotation.comments) {
            allComments = allComments.concat(quotation.comments.map(comment => ({ ...comment, source: 'Quotation' })));
          }
        });
      }

      // Add BAC resolution comments
      if (this.procurement.bac_resolutions) {
        this.procurement.bac_resolutions.forEach(bac => {
          if (bac.comments) {
            allComments = allComments.concat(bac.comments.map(comment => ({ ...comment, source: 'BAC Resolution' })));
          }
        });
      }

      // Add NOA comments
      if (this.procurement.noas) {
        this.procurement.noas.forEach(noa => {
          if (noa.comments) {
            allComments = allComments.concat(noa.comments.map(comment => ({ ...comment, source: 'NOA' })));
          }
        });
      }

      // Add PO comments
      if (this.procurement.pos) {
        this.procurement.pos.forEach(po => {
          if (po.comments) {
            allComments = allComments.concat(po.comments.map(comment => ({ ...comment, source: 'Purchase Order' })));
          }
        });
      }

      return allComments.sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
    },
    commentCount() {
      return this.sortedComments.length;
    },
    currentUserAvatar() {
      return this.resolveAvatar(this.currentUser);
    },
    currentUserName() {
      return this.resolveName(this.currentUser);
    },
    currentUserInitials() {
      return this.resolveInitials(this.currentUser);
    },
    procAssigneeMap() {
      const fromAssignments = {};
      (this.procurement?.assignments || []).forEach((assignment) => {
        const status = assignment?.status;
        const name =
          assignment?.user?.profile?.full_name ||
          assignment?.user?.profile?.fullname ||
          assignment?.user?.name ||
          assignment?.name ||
          null;
        if (!status || !name) return;
        if (!fromAssignments[status]) fromAssignments[status] = [];
        fromAssignments[status].push(name);
      });
      const map = Object.keys(fromAssignments).length
        ? fromAssignments
        : (this.procurement?.assignees || this.procurement?.assigned_personnel || {});
      const normalized = {};
      Object.keys(map || {}).forEach((key) => {
        const value = map[key];
        if (Array.isArray(value)) {
          normalized[key] = [...new Set(value.filter(Boolean))];
        } else if (typeof value === "string" && value.trim()) {
          normalized[key] = [value];
        } else {
          normalized[key] = [];
        }
      });
      return normalized;
    },
    statusFlow() {
      // Define the procurement main status flow
      const currentStatus = this.procurement.status?.name;
      let statusFlow = [];

      if (currentStatus === 'Re-award' || currentStatus === 'Rebid') {
        statusFlow = [
          { name: 'Pending', isCurrent: false },
          { name: 'Reviewed', isCurrent: false },
          { name: 'Approved', isCurrent: false },
          { name: 'For Quotations', isCurrent: false },
          { name: 'For Bids', isCurrent: false },
          { name: 'For BAC Resolution', isCurrent: false },
          { name: 'For Approval of BAC Resolution', isCurrent: false },
          { name: 'For NOA', isCurrent: false },
          { name: 'NOA Served to Supplier', isCurrent: false },
          { name: currentStatus, isCurrent: true },
          { name: 'NOA Conformed', isCurrent: false },
          { name: 'PO Issued', isCurrent: false },
          { name: 'PO Conformed', isCurrent: false },
          { name: 'Items Delivered', isCurrent: false },
          { name: 'Completed', isCurrent: false },
        ];
      } else {
        statusFlow = [
          { name: 'Pending', isCurrent: currentStatus === 'Pending' },
          { name: 'Reviewed', isCurrent: currentStatus === 'Reviewed' },
          { name: 'Approved', isCurrent: currentStatus === 'Approved' },
          { name: 'For Quotations', isCurrent: currentStatus === 'For Quotations' },
          { name: 'For Bids', isCurrent: currentStatus === 'For Bids' },
          { name: 'For BAC Resolution', isCurrent: currentStatus === 'For BAC Resolution' },
          { name: 'For Approval of BAC Resolution', isCurrent: currentStatus === 'For Approval of BAC Resolution' },
          { name: 'For NOA', isCurrent: currentStatus === 'For NOA' },
          { name: 'NOA Served to Supplier', isCurrent: currentStatus === 'NOA Served to Supplier' },
          { name: 'NOA Conformed', isCurrent: currentStatus === 'NOA Conformed' },
          { name: 'PO Issued', isCurrent: currentStatus === 'PO Issued' },
          { name: 'PO Conformed', isCurrent: currentStatus === 'PO Conformed' },
          { name: 'Items Delivered', isCurrent: currentStatus === 'Items Delivered' || currentStatus === 'Delivered/For Inspection' },
          { name: 'Completed', isCurrent: currentStatus === 'Completed' },
        ];
      }

      const currentIndex = statusFlow.findIndex(s => s.isCurrent);
      statusFlow.forEach((status, index) => {
        status.isPast = index < currentIndex;
      });
      return statusFlow;
    },
    subStatusFlow() {
      // Define the procurement sub-status flow based on main status
      const currentStatus = this.procurement.status?.name;
      const currentSubStatus = this.procurement.sub_status?.name;
      let subStatusFlow = [];

      if (currentStatus === 'Rebid') {
        // Rebid flow: full flow from For Quotations to Completed
        subStatusFlow = [
          { name: 'For Quotations', isCurrent: currentSubStatus === 'For Quotations' },
          { name: 'For Bids', isCurrent: currentSubStatus === 'For Bids' },
          { name: 'For BAC Resolution', isCurrent: currentSubStatus === 'For BAC Resolution' },
          { name: 'For Approval of BAC Resolution', isCurrent: currentSubStatus === 'For Approval of BAC Resolution' },
          { name: 'For Approval of Failure BAC Resolution', isCurrent: currentSubStatus === 'For Approval of Failure BAC Resolution' },
          { name: 'For NOA', isCurrent: currentSubStatus === 'For NOA' },
          { name: 'NOA Served to Supplier', isCurrent: currentSubStatus === 'NOA Served to Supplier' },
          { name: 'NOA Conformed', isCurrent: currentSubStatus === 'NOA Conformed' },
          { name: 'PO Issued', isCurrent: currentSubStatus === 'PO Issued' },
          { name: 'Items Delivered', isCurrent: currentSubStatus === 'Items Delivered' || currentSubStatus === 'Delivered/For Inspection' },
          { name: 'Completed', isCurrent: currentSubStatus === 'Completed' },
        ];
      } else if (currentStatus === 'Re-award') {
        // Re-award flow: starts with For NOA
        subStatusFlow = [
          { name: 'For NOA', isCurrent: currentSubStatus === 'For NOA' },
          { name: 'NOA Served to Supplier', isCurrent: currentSubStatus === 'NOA Served to Supplier' },
          { name: 'NOA Conformed', isCurrent: currentSubStatus === 'NOA Conformed' },
          { name: 'PO Issued', isCurrent: currentSubStatus === 'PO Issued' },
          { name: 'Items Delivered', isCurrent: currentSubStatus === 'Items Delivered' || currentSubStatus === 'Delivered/For Inspection' },
          { name: 'Completed', isCurrent: currentSubStatus === 'Completed' },
        ];
      } else {
        // Default flow for other statuses
        subStatusFlow = [
          { name: 'For Quotations', isCurrent: currentSubStatus === 'For Quotations' },
          { name: 'For Bids', isCurrent: currentSubStatus === 'For Bids' },
          { name: 'For BAC Resolution', isCurrent: currentSubStatus === 'For BAC Resolution' },
          { name: 'For Approval of BAC Resolution', isCurrent: currentSubStatus === 'For Approval of BAC Resolution' },
          { name: 'For NOA', isCurrent: currentSubStatus === 'For NOA' },
          { name: 'NOA Served to Supplier', isCurrent: currentSubStatus === 'NOA Served to Supplier' },
          { name: 'NOA Conformed', isCurrent: currentSubStatus === 'NOA Conformed' },
          { name: 'PO Issued', isCurrent: currentSubStatus === 'PO Issued' },
          { name: 'Items Delivered', isCurrent: currentSubStatus === 'Items Delivered' || currentSubStatus === 'Delivered/For Inspection' },
          { name: 'Completed', isCurrent: currentSubStatus === 'Completed' },
        ];
      }

      const currentIndex = subStatusFlow.findIndex(s => s.isCurrent);
      subStatusFlow.forEach((subStatus, index) => {
        subStatus.isPast = index < currentIndex;
      });
      return subStatusFlow;
    },
  },
  mounted() {
    this.activeRightTab = 1;
    localStorage.setItem("activeRightTab", "1");
    this.listenForComments();
  },
  watch: {
    isRightCollapsed(value) {
      if (!value) {
        this.$nextTick(() => {
          this.scrollThreadToBottom();
        });
      }
    },
    commentCount(newCount, oldCount) {
      if (newCount === oldCount || this.isRightCollapsed) {
        return;
      }

      this.$nextTick(() => {
        this.scrollThreadToBottom(oldCount ? "smooth" : "auto");
      });
    },
  },
  methods: {
    toggleRightSidebar() {
      this.$emit("toggleRightSidebar");
    },
    showRightTab(tab) {
      this.activeRightTab = 1;
      localStorage.setItem("activeRightTab", "1");
    },
    openStatusTip(statusName) {
      const assigned = this.getAssignedForStep(statusName);
      if (statusName === "Rebid") {
        this.statusTipTitle = "Rebid Workflow";
        this.statusTipSubtitle = "Follow these steps after rebid:";
        this.statusTipSteps = [
          "Create and send RFQ",
          "Collect bid offers",
          "Create BAC Resolution",
          "Approve BAC Resolution",
          "Create NOA",
          "Serve and conform NOA",
          "Create and issue PO",
          "Deliver and inspect",
          "Mark as completed",
        ];
      } else if (statusName === "Re-award") {
        this.statusTipTitle = "Re-award Workflow";
        this.statusTipSubtitle = "Follow these steps after re-award:";
        this.statusTipSteps = [
          "Approve the failure of BAC resolution",
          "Create NOA for re-award",
          "Serve and conform NOA",
          "Create and issue PO",
          "Deliver and inspect",
          "Mark as completed",
        ];
      } else {
        this.statusTipTitle = `${statusName} Status`;
        this.statusTipSubtitle = "Status details and suggested next action:";
        const nextByStatus = {
          Pending: "Review procurement request",
          Reviewed: "Approve procurement request",
          Approved: "Create and send RFQ",
          "For Quotations": "Collect quotations from suppliers",
          "For Bids": "Encode and evaluate bid offers",
          "For BAC Resolution": "Create BAC resolution",
          "For Approval of BAC Resolution": "Approve BAC resolution",
          "For NOA": "Create NOA",
          "NOA Served to Supplier": "Wait for supplier confirmation",
          "NOA Conformed": "Create purchase order",
          "PO Created": "Issue purchase order",
          "PO Issued": "Wait for supplier conformity",
          "PO Conformed": "Proceed to delivery and inspection",
          "Items Delivered": "Complete inspection and acceptance",
          "Delivered/For Inspection": "Complete inspection and acceptance",
          Completed: "Process is completed",
        };
        this.statusTipSteps = [nextByStatus[statusName] || "Track and update this status as needed"];
      }
      this.statusTipAssigned = assigned;
      this.showStatusTipModal = true;
    },
    mapStepToAssignmentStatuses(stepName) {
      const map = {
        "For RFQ": ["For Quotations"],
        "For BAC": ["For BAC Resolution"],
        "For Approval": ["For Approval of BAC Resolution"],
        "NOA Served": ["NOA Served to Supplier"],
        "NOA Confirmed": ["NOA Conformed"],
        "Items Delivered": ["PO Items Delivered", "Items Delivered", "PO Delivered/For Inspection", "Delivered/For Inspection", "Delivered"],
        Delivered: ["PO Items Delivered", "Items Delivered", "PO Delivered/For Inspection", "Delivered/For Inspection"],
      };
      return map[stepName] || [stepName];
    },
    getAssignedForStep(stepName) {
      const statuses = this.mapStepToAssignmentStatuses(stepName);
      const names = statuses.flatMap((status) => this.procAssigneeMap?.[status] || []);
      return [...new Set(names.filter(Boolean))];
    },
    formatDate(dateString) {
      const date = new Date(dateString);
      return date.toLocaleString("en-US", {
        year: "numeric",
        month: "short",
        day: "numeric",
        hour: "2-digit",
        minute: "2-digit",
      });
    },
    normalizeAvatarPath(avatar) {
      if (!avatar) {
        return null;
      }

      const value = String(avatar).trim();

      if (!value) {
        return null;
      }

      if (
        value.startsWith("http://") ||
        value.startsWith("https://") ||
        value.startsWith("/")
      ) {
        return value;
      }

      return `/storage/${value.replace(/^storage\//, "")}`;
    },
    resolveAvatar(user) {
      return this.normalizeAvatarPath(user?.profile?.avatar || user?.avatar || null);
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
    resolveInitials(user) {
      const profile = user?.profile || {};
      const parts = [
        profile.firstname,
        profile.middlename,
        profile.lastname,
      ].filter((value) => String(value || "").trim().length);

      if (parts.length) {
        return parts
          .map((value) => String(value).trim().charAt(0).toUpperCase())
          .join("")
          .slice(0, 3);
      }

      return this.resolveName(user)
        .split(/\s+/)
        .filter(Boolean)
        .map((value) => value.charAt(0).toUpperCase())
        .join("")
        .slice(0, 3) || "USR";
    },
    isOwnComment(comment) {
      const currentUserId = Number(this.currentUser?.id || 0);
      return Number(comment?.user?.id || 0) === currentUserId;
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
        .replace(/(@[A-Za-z0-9._-]+)/g, '<span class="overview-comment-mention">$1</span>')
        .replace(/\n/g, "<br>");
    },
    scrollThreadToBottom(behavior = "auto") {
      const threadElement = this.$refs.thread_container;

      if (!threadElement) {
        return;
      }

      threadElement.scrollTo({
        top: threadElement.scrollHeight,
        behavior,
      });
    },
    submitComment() {
      const content = this.newComment.trim();
      if (!content || this.commentSubmitting) return;

      this.commentSubmitting = true;
      this.form.clearErrors();

      axios
        .post(
          `/faims/procurements/${this.procurement.id}/comments`,
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
            this.addIncomingComment(response.data.data);
          }

          this.newComment = "";
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
    addIncomingComment(comment) {
      if (comment.commentable_type === 'App\\Models\\ProcurementBac') {
        const bac = this.procurement.bac_resolutions?.find(b => b.id === comment.commentable_id);
        if (bac) {
          if (!Array.isArray(bac.comments)) {
            bac.comments = [];
          }
          bac.comments.push(comment);
        }
      } else if (comment.commentable_type === 'App\\Models\\ProcurementBacNoa') {
        const noa = this.procurement.noas?.find(n => n.id === comment.commentable_id);
        if (noa) {
          if (!Array.isArray(noa.comments)) {
            noa.comments = [];
          }
          noa.comments.push(comment);
        }
      } else if (comment.commentable_type === 'App\\Models\\ProcurementNoaPo') {
        const po = this.procurement.pos?.find(p => p.id === comment.commentable_id);
        if (po) {
          if (!Array.isArray(po.comments)) {
            po.comments = [];
          }
          po.comments.push(comment);
        }
      } else if (comment.commentable_type === 'App\\Models\\ProcurementQuotation') {
        const quotation = this.procurement.quotations?.find(q => q.id === comment.commentable_id);
        if (quotation) {
          if (!Array.isArray(quotation.comments)) {
            quotation.comments = [];
          }
          quotation.comments.push(comment);
        }
      } else {
        if (!Array.isArray(this.procurement.comments)) {
          this.procurement.comments = [];
        }
        this.procurement.comments.push(comment);
      }
    },
    listenForComments() {
      if (window.Echo) {
        window.Echo.private(`procurement.${this.procurement.id}`)
          .listen('.comment.added', (e) => {
            this.addIncomingComment(e.comment);
          });
      }
    },
  },
};
</script>

<style scoped>
.floating-comments-wrapper {
  position: fixed;
  right: 24px;
  bottom: 24px;
  z-index: 1050;
}

.floating-comment-panel,
.floating-comment-header,
.floating-comment-body,
.floating-comment-summary,
.floating-comment-composer,
.floating-comment-bubble-own,
:deep(.overview-comment-mention) {
  --overview-comment-accent: #3f4f8a;
  --overview-comment-accent-strong: #2e3c70;
  --overview-comment-accent-soft: rgba(63, 79, 138, 0.12);
  --overview-comment-accent-soft-2: rgba(63, 79, 138, 0.2);
  --overview-comment-accent-border: rgba(63, 79, 138, 0.14);
  --overview-comment-mention-bg: #ffe36a;
  --overview-comment-mention-color: #5f4700;
}

.floating-comment-trigger {
  position: relative;
  width: 64px;
  height: 64px;
  border: 0;
  border-radius: 50%;
  background: linear-gradient(135deg, #4a5fa7 0%, #31488f 100%);
  color: #fff;
  box-shadow: 0 18px 36px rgba(49, 72, 143, 0.28);
  font-size: 1.4rem;
}

.floating-comment-trigger:hover {
  transform: translateY(-2px);
}

.floating-comment-badge {
  position: absolute;
  top: 3px;
  right: 3px;
  font-size: 0.7rem;
}

.floating-comment-panel {
  position: absolute;
  right: 0;
  bottom: 0;
  width: 480px;
  max-width: calc(100vw - 32px);
  background:
    radial-gradient(circle at top left, rgba(63, 79, 138, 0.08), transparent 34%),
    linear-gradient(135deg, #f8f9fa 0%, #eef2f7 100%);
  border-radius: 20px;
  overflow: hidden;
}

.floating-comment-header {
  padding: 1rem;
  box-shadow: 0 0.5rem 1.25rem rgba(15, 23, 42, 0.12);
}

.floating-comment-body {
  position: relative;
  display: flex;
  flex-direction: column;
  max-height: min(76vh, 840px);
  min-height: min(66vh, 720px);
  overflow: hidden;
  border-radius: 0 0 20px 20px;
}

.floating-comment-summary {
  background: rgba(255, 255, 255, 0.92);
  border-color: rgba(63, 79, 138, 0.08) !important;
  backdrop-filter: blur(12px);
}

.floating-comment-online-pill {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.25rem 0.55rem;
  border-radius: 999px;
  background: rgba(49, 208, 88, 0.12);
  color: #1f9d45;
  font-size: 0.7rem;
  font-weight: 700;
  text-transform: lowercase;
}

.floating-comment-online-pill::before {
  content: "";
  width: 0.45rem;
  height: 0.45rem;
  border-radius: 50%;
  background: #31d058;
}

.floating-comment-stat-badge {
  display: inline-flex;
  align-items: center;
  padding: 0.35rem 0.7rem;
  border-radius: 999px;
  font-size: 0.72rem;
  font-weight: 700;
}

.floating-comment-stat-badge-primary {
  background: rgba(63, 79, 138, 0.12);
  color: var(--overview-comment-accent);
}

.floating-comment-stat-badge-info {
  background: rgba(37, 99, 235, 0.1);
  color: #1d4ed8;
}

.floating-comment-thread {
  flex: 1 1 auto;
  min-height: 0;
  scroll-behavior: smooth;
  padding-right: 0.85rem !important;
}

.floating-comment-thread-inner {
  min-height: min-content;
}

.floating-comment-message-wrap {
  max-width: min(88%, 340px);
}

.floating-comment-avatar {
  width: 2.5rem;
  height: 2.5rem;
  object-fit: cover;
  background: #f8faff;
}

.floating-comment-avatar-sm {
  width: 2rem;
  height: 2rem;
}

.floating-comment-avatar-fallback {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #eef3ff, #dfe7ff);
  color: var(--overview-comment-accent);
  font-size: 0.78rem;
  font-weight: 700;
}

.floating-comment-source-badge {
  display: inline-flex;
  align-items: center;
  padding: 0.18rem 0.5rem;
  border-radius: 999px;
  background: rgba(63, 79, 138, 0.08);
  color: var(--overview-comment-accent);
  font-size: 0.68rem;
  font-weight: 700;
}

.floating-comment-bubble {
  width: 100%;
  padding: 0.875rem 1rem;
  border: 1px solid rgba(63, 79, 138, 0.08);
  box-shadow: 0 0.25rem 1rem rgba(15, 23, 42, 0.04);
}

.floating-comment-bubble-own {
  background: linear-gradient(135deg, #5168b7, #3c4d8e);
  border-color: transparent;
  color: #fff;
  border-radius: 1.1rem 1.1rem 0.35rem 1.1rem;
}

.floating-comment-bubble-other {
  background: #ffffff;
  color: #24314d;
  border-radius: 1.1rem 1.1rem 1.1rem 0.35rem;
}

.floating-comment-bubble-reply {
  padding: 0.7rem 0.85rem;
}

.floating-comment-replies {
  padding-left: 0.25rem;
}

.floating-comment-empty-state {
  background: rgba(255, 255, 255, 0.86);
  border-color: rgba(63, 79, 138, 0.08) !important;
}

.floating-comment-composer {
  background: #ffffff;
  border-color: rgba(63, 79, 138, 0.08) !important;
  box-shadow: 0 -0.35rem 1.25rem rgba(15, 23, 42, 0.08);
}

.floating-comment-composer :deep(.card-body) {
  background: #ffffff;
}

.floating-comment-textarea {
  min-height: 90px;
  border-radius: 1rem;
  border-color: rgba(63, 79, 138, 0.12);
  background-color: #fff;
  box-shadow: none;
  resize: none;
}

.floating-comment-textarea:focus {
  border-color: rgba(63, 79, 138, 0.28);
  box-shadow: 0 0 0 0.2rem rgba(63, 79, 138, 0.08);
}

.comment-panel-enter-active,
.comment-panel-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}

.comment-panel-enter-from,
.comment-panel-leave-to {
  opacity: 0;
  transform: translateY(12px) scale(0.98);
}

@media (max-width: 768px) {
  .floating-comments-wrapper {
    right: 16px;
    bottom: 16px;
  }

  .floating-comment-panel {
    width: min(440px, calc(100vw - 24px));
    right: -4px;
    bottom: 74px;
  }
}

:deep(.overview-comment-mention) {
  display: inline-block;
  padding: 0.05rem 0.45rem;
  border-radius: 0.55rem;
  background: var(--overview-comment-mention-bg);
  color: var(--overview-comment-mention-color);
  font-weight: 700;
  box-shadow: inset 0 0 0 1px rgba(95, 71, 0, 0.08);
}

/* Log Item Styling */
.log-item {
  background: #f8f9fa;
  border-left: 4px solid #007bff;
  transition: all 0.3s ease;
}

.log-item:hover {
  background: #e9ecef;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.log-description {
  color: #495057;
  font-size: 0.9rem;
}

.log-details {
  color: #6c757d;
}

.log-changes {
  background: #ffffff;
  padding: 0.5rem;
  border-radius: 4px;
  border: 1px solid #dee2e6;
}

.change-item {
  display: flex;
  justify-content: space-between;
  margin-bottom: 0.25rem;
}

.change-key {
  font-weight: 600;
  color: #495057;
}

.change-value {
  color: #007bff;
  font-family: monospace;
  font-size: 0.85rem;
}

.log-icon {
  margin-left: 1rem;
  opacity: 0.7;
}

/* Amazing Status Flow Styling */
.status-flow-header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 15px;
  padding: 1.5rem;
  color: white;
  box-shadow: 0 8px 32px rgba(102, 126, 234, 0.3);
  position: relative;
  overflow: hidden;
}

.status-flow-header::before {
  content: '';
  position: absolute;
  top: -50%;
  left: -50%;
  width: 200%;
  height: 200%;
  background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
  animation: shimmer 3s ease-in-out infinite;
}

@keyframes shimmer {
  0%, 100% { transform: translate(-50%, -50%) rotate(0deg); }
  50% { transform: translate(-50%, -50%) rotate(180deg); }
}

.status-flow-icon {
  background: rgba(255, 255, 255, 0.2);
  border-radius: 50%;
  width: 60px;
  height: 60px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.8rem;
  backdrop-filter: blur(10px);
  border: 2px solid rgba(255, 255, 255, 0.3);
}

.status-flow-panel {
  background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
  border: 1px solid #e9ecef;
  border-radius: 15px;
  padding: 1.5rem;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
}

.status-flow-panel:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
}

.status-flow-panel::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: linear-gradient(90deg, #667eea, #764ba2, #f093fb, #f5576c);
  background-size: 300% 100%;
  animation: gradientShift 4s ease infinite;
}

@keyframes gradientShift {
  0%, 100% { background-position: 0% 50%; }
  50% { background-position: 100% 50%; }
}

.panel-header {
  margin-bottom: 1.5rem;
}

.status-indicator {
  background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
  border-radius: 20px;
  padding: 0.5rem 1rem;
  box-shadow: 0 4px 15px rgba(245, 87, 108, 0.3);
}

.status-flow-container-modern {
  overflow-x: auto;
  padding: 1rem 0;
}

.status-flow-wrapper-modern {
  display: flex;
  align-items: center;
  gap: 1.5rem;
  padding: 0 1rem;
  min-width: max-content;
}

.status-step-amazing {
  display: flex;
  align-items: center;
  gap: 1.5rem;
  opacity: 0;
  transform: translateY(30px);
  animation: slideInAmazing 0.8s ease-out forwards;
}

.status-step-amazing.current-status {
  animation: slideInAmazing 0.8s ease-out forwards, glowPulse 2s ease-in-out infinite;
}

.status-step-amazing.past-status {
  opacity: 1;
  transform: translateY(0);
}

.status-step-amazing.future-status {
  opacity: 0.5;
}

@keyframes slideInAmazing {
  0% {
    opacity: 0;
    transform: translateY(30px);
  }
  100% {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes glowPulse {
  0%, 100% {
    box-shadow: 0 0 20px rgba(102, 126, 234, 0.3);
  }
  50% {
    box-shadow: 0 0 40px rgba(102, 126, 234, 0.6);
  }
}

.status-card-amazing {
  background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
  border: 2px solid #e9ecef;
  border-radius: 15px;
  padding: 1rem;
  min-width: 140px;
  text-align: center;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
  overflow: hidden;
  cursor: pointer;
}

.status-card-amazing:hover {
  transform: translateY(-5px) scale(1.02);
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
  border-color: #667eea;
}

.status-glow {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(45deg, transparent, rgba(102, 126, 234, 0.1), transparent);
  opacity: 0;
  transition: opacity 0.3s ease;
}

.status-glow.active {
  opacity: 1;
  animation: glowRotate 3s linear infinite;
}

@keyframes glowRotate {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.status-step-amazing.current-status .status-card-amazing {
  border-color: #667eea;
  box-shadow: 0 0 30px rgba(102, 126, 234, 0.4);
  transform: translateY(-3px);
}

.status-step-amazing.past-status .status-card-amazing {
  background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
  border-color: #28a745;
  box-shadow: 0 6px 25px rgba(40, 167, 69, 0.2);
}

.status-step-amazing.future-status .status-card-amazing {
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  border-color: #6c757d;
  box-shadow: 0 4px 15px rgba(108, 117, 125, 0.1);
}

.status-icon-wrapper-amazing {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 1rem;
  position: relative;
}

.icon-bg {
  width: 100%;
  height: 100%;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
}

.icon-bg::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(45deg, transparent, rgba(255,255,255,0.3), transparent);
  transform: translateX(-100%);
  transition: transform 0.6s ease;
}

.icon-bg:hover::before {
  transform: translateX(100%);
}

.completed-bg {
  background: linear-gradient(135deg, #28a745, #20c997);
  box-shadow: 0 4px 15px rgba(40, 167, 69, 0.4);
}

.current-bg {
  background: linear-gradient(135deg, #ffc107, #fd7e14);
  box-shadow: 0 4px 15px rgba(255, 193, 7, 0.4);
  animation: currentPulse 2s ease-in-out infinite;
}

.pending-bg {
  background: linear-gradient(135deg, #6c757d, #495057);
  box-shadow: 0 4px 15px rgba(108, 117, 125, 0.3);
}

@keyframes currentPulse {
  0%, 100% {
    transform: scale(1);
    box-shadow: 0 4px 15px rgba(255, 193, 7, 0.4);
  }
  50% {
    transform: scale(1.1);
    box-shadow: 0 6px 25px rgba(255, 193, 7, 0.6);
  }
}

.status-icon-amazing {
  font-size: 1.8rem;
  color: white;
  transition: all 0.3s ease;
  position: relative;
  z-index: 2;
}

.status-icon-amazing.completed {
  animation: checkBounce 0.6s ease-out;
}

.status-icon-amazing.current {
  animation: starTwinkle 2s ease-in-out infinite;
}

@keyframes checkBounce {
  0% { transform: scale(0); }
  50% { transform: scale(1.2); }
  100% { transform: scale(1); }
}

@keyframes starTwinkle {
  0%, 100% { transform: rotate(0deg) scale(1); }
  25% { transform: rotate(90deg) scale(1.1); }
  50% { transform: rotate(180deg) scale(1.2); }
  75% { transform: rotate(270deg) scale(1); }
}

.status-content-amazing {
  margin-top: 0.5rem;
}

.status-title-amazing {
  font-size: 0.85rem;
  font-weight: 700;
  color: #2c3e50;
  margin-bottom: 0.5rem;
  line-height: 1.3;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.current-indicator {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  margin-top: 0.5rem;
}

.pulse-dot {
  width: 8px;
  height: 8px;
  background: #667eea;
  border-radius: 50%;
  animation: dotPulse 1.5s ease-in-out infinite;
}

@keyframes dotPulse {
  0%, 100% {
    transform: scale(1);
    opacity: 1;
  }
  50% {
    transform: scale(1.5);
    opacity: 0.7;
  }
}

.status-connector-amazing {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.connector-line-amazing {
  width: 80px;
  height: 4px;
  background: linear-gradient(90deg, #e9ecef, #dee2e6);
  border-radius: 2px;
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
}

.connector-line-amazing::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(102, 126, 234, 0.6), transparent);
  transition: left 0.5s ease;
}

.connector-line-amazing.active {
  background: linear-gradient(90deg, #28a745, #20c997);
  box-shadow: 0 0 15px rgba(40, 167, 69, 0.4);
}

.connector-line-amazing.active::before {
  left: 100%;
}

.connector-arrow-amazing {
  color: #e9ecef;
  font-size: 1.2rem;
  transition: all 0.3s ease;
  position: relative;
}

.connector-arrow-amazing.active {
  color: #28a745;
  animation: arrowBounce 1.5s ease-in-out infinite;
}

@keyframes arrowBounce {
  0%, 100% { transform: translateX(0); }
  50% { transform: translateX(3px); }
}

.no-substatus-message {
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  border: 2px dashed #dee2e6;
  border-radius: 15px;
  padding: 2rem;
  text-align: center;
  margin-top: 1rem;
}

.no-status-icon {
  width: 80px;
  height: 80px;
  background: linear-gradient(135deg, #6c757d, #495057);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 1rem;
  color: white;
  font-size: 2rem;
  box-shadow: 0 4px 20px rgba(108, 117, 125, 0.3);
}

/* Compact Timeline Design */
.status-flow-header-compact {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 10px;
  padding: 0.75rem 1rem;
  color: white;
  box-shadow: 0 4px 15px rgba(102, 126, 234, 0.2);
  margin-bottom: 0.75rem;
}

.status-timeline-compact {
  background: #ffffff;
  border: 1px solid #e9ecef;
  border-radius: 10px;
  padding: 1rem;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
  margin-bottom: 0.75rem;
}

.timeline-header {
  margin-bottom: 0.75rem;
}

.badge-sm {
  font-size: 0.7rem;
  padding: 0.25rem 0.5rem;
}

.timeline-container {
  overflow-x: auto;
  padding: 0.5rem 0;
}

.timeline-track {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 0 0.5rem;
  min-width: max-content;
}

.timeline-step {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
  min-width: 80px;
  position: relative;
  border: 1px solid transparent;
  border-radius: 10px;
}

.timeline-step.completed {
  background: #ecfdf3;
  border-color: #86efac;
  box-shadow: 0 0 0 1px rgba(34, 197, 94, 0.15) inset;
}

.timeline-step:not(:last-child)::after {
  content: '';
  position: absolute;
  top: 15px;
  left: calc(50% + 20px);
  right: calc(-50% - 20px);
  height: 2px;
  background: #e9ecef;
  z-index: 1;
}

.timeline-step.completed:not(:last-child)::after {
  background: linear-gradient(90deg, #28a745, #20c997);
}

.timeline-step.active:not(:last-child)::after {
  background: linear-gradient(90deg, #ffc107, #fd7e14);
  animation: progressFlow 2s ease-in-out infinite;
}

@keyframes progressFlow {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.7; }
}

.timeline-dot {
  width: 30px;
  height: 30px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 0.9rem;
  position: relative;
  z-index: 2;
  transition: all 0.3s ease;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

.timeline-step.completed .timeline-dot {
  background: linear-gradient(135deg, #28a745, #20c997);
}

.timeline-step.active .timeline-dot {
  background: linear-gradient(135deg, #ffc107, #fd7e14);
  animation: pulseDot 1.5s ease-in-out infinite;
}

.timeline-step.pending .timeline-dot {
  background: linear-gradient(135deg, #6c757d, #495057);
}

.timeline-dot.pulse {
  animation: pulseDot 1.5s ease-in-out infinite;
}

@keyframes pulseDot {
  0%, 100% {
    transform: scale(1);
    box-shadow: 0 2px 8px rgba(255, 193, 7, 0.3);
  }
  50% {
    transform: scale(1.2);
    box-shadow: 0 4px 15px rgba(255, 193, 7, 0.6);
  }
}

.timeline-label {
  text-align: center;
}

.timeline-label small {
  color: #495057;
  font-weight: 600;
  line-height: 1.2;
  display: block;
}

.timeline-step.completed .timeline-label small {
  color: #28a745;
}

.timeline-step.active .timeline-label small {
  color: #ffc107;
}

.timeline-step.pending .timeline-label small {
  color: #6c757d;
}

.no-substatus-compact {
  background: #f8f9fa;
  border: 1px dashed #dee2e6;
  border-radius: 8px;
  padding: 0.75rem;
  text-align: center;
  margin-top: 0.5rem;
}

/* Alternative Design: Progress Bar Style */
.status-progress-bar {
  background: #ffffff;
  border: 1px solid #e9ecef;
  border-radius: 10px;
  padding: 1rem;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
}

.progress-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.progress-title {
  font-size: 0.9rem;
  font-weight: 600;
  color: #495057;
}

.progress-percentage {
  font-size: 0.8rem;
  color: #6c757d;
}

.progress-bar-container {
  width: 100%;
  height: 8px;
  background: #e9ecef;
  border-radius: 4px;
  overflow: hidden;
  margin-bottom: 0.5rem;
}

.progress-fill {
  height: 100%;
  border-radius: 4px;
  transition: width 0.8s ease;
  position: relative;
}

.progress-fill.completed {
  background: linear-gradient(90deg, #28a745, #20c997);
}

.progress-fill.current {
  background: linear-gradient(90deg, #ffc107, #fd7e14);
}

.progress-fill.pending {
  background: #6c757d;
}

.progress-fill::after {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
  animation: progressShine 2s ease-in-out infinite;
}

@keyframes progressShine {
  0% { transform: translateX(-100%); }
  100% { transform: translateX(100%); }
}

.progress-steps {
  display: flex;
  justify-content: space-between;
  margin-top: 0.5rem;
}

.progress-step {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.25rem;
  flex: 1;
}

.progress-step-dot {
  width: 12px;
  height: 12px;
  border-radius: 50%;
  border: 2px solid #e9ecef;
  position: relative;
}

.progress-step.completed .progress-step-dot {
  background: #28a745;
  border-color: #28a745;
}

.progress-step.current .progress-step-dot {
  background: #ffc107;
  border-color: #ffc107;
  animation: stepPulse 1.5s ease-in-out infinite;
}

@keyframes stepPulse {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.3); }
}

.progress-step-label {
  font-size: 0.7rem;
  color: #6c757d;
  text-align: center;
  line-height: 1.1;
}

.progress-step.completed .progress-step-label {
  color: #28a745;
  font-weight: 600;
}

.progress-step.current .progress-step-label {
  color: #ffc107;
  font-weight: 600;
}



/* Responsive adjustments for amazing status flow */
@media (max-width: 768px) {
  .status-flow-wrapper-modern {
    gap: 1rem;
    padding: 0 0.5rem;
  }

  .status-card-amazing {
    min-width: 120px;
    padding: 0.75rem;
  }

  .status-title-amazing {
    font-size: 0.75rem;
  }

  .connector-line-amazing {
    width: 50px;
  }

  .status-flow-header {
    padding: 1rem;
  }

  .status-flow-panel {
    padding: 1rem;
  }

  .timeline-track {
    gap: 0.75rem;
  }

  .timeline-step {
    min-width: 70px;
  }

  .empty-state-comments,
  .empty-state-logs,
  .empty-state-status {
    padding: 1.5rem 0.5rem;
  }

  .empty-state-icon {
    width: 60px;
    height: 60px;
    font-size: 1.5rem;
  }

  .empty-state-title {
    font-size: 1rem;
  }

  .empty-state-message {
    font-size: 0.85rem;
  }
}

.status-tip-body {
  font-size: 0.95rem;
}

.status-tip-subtitle {
  color: #64748b;
  margin-bottom: 0.5rem;
}

.status-tip-steps {
  margin-bottom: 0.75rem;
  padding-left: 1.1rem;
}

.status-tip-assigned {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 0.6rem 0.75rem;
}

.status-tip-badges {
  margin-top: 0.45rem;
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.status-tip-person {
  align-self: flex-start;
}

.status-tip-note {
  margin-top: 0.6rem;
  font-size: 0.85rem;
  color: #64748b;
}
</style>
