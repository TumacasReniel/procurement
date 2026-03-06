<template>
  <div
    :class="['transition-all', isRightCollapsed ? 'col-md-1' : 'col-md-3']"
    style="transition: all 0.3s ease; height: 100%"
  >
    <div
      class="card h-90 mb-3 shadow-lg border-0"
      style="
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border-radius: 15px;
        height: 100vh;
      "
    >
      <div
        class="card-header bg-gradient-primary text-white border-0 d-flex align-items-center justify-content-between"
        style="border-radius: 15px 15px 0 0 !important; padding: 1rem"
      >
        <div v-if="!isRightCollapsed">
          <h5 class="card-title mb-1">
            <span class="position-relative me-2">
              <i v-if="activeRightTab === 1" class="ri-chat-1-line"></i>
              <i v-else-if="activeRightTab === 2" class="ri-file-list-line"></i>
              <i v-else-if="activeRightTab === 3" class="ri-flow-chart"></i>
            <span v-if="activeRightTab === 1 && commentCount > 0" class="badge bg-danger position-absolute top-0 start-100 translate-middle" style="font-size: 0.7rem; padding: 0.2rem 0.4rem;">{{ commentCount }}</span>
            <span v-if="activeRightTab === 2 && logsCount > 0" class="badge bg-danger position-absolute top-0 start-100 translate-middle" style="font-size: 0.7rem; padding: 0.2rem 0.4rem;">{{ logsCount }}</span>
            </span>
            <span class="text-white">
              {{ activeRightTab === 1 ? 'Procurement Comments' : activeRightTab === 2 ? 'Activity Logs' : 'Status' }}
            </span>
          </h5>
        </div>
        <button
          @click="toggleRightSidebar"
          class="btn btn-sm btn-light rounded-circle p-2 ms-2"
          style="width: 40px; height: 40px"
        >
          <i
            :class="isRightCollapsed ? 'ri-arrow-left-line' : 'ri-arrow-right-line'"
            class="text-primary fs-6"
          ></i>
        </button>
      </div>
      <div
        v-if="!isRightCollapsed"
        class="card-body p-0"
        style="
          height: 100vh;
          overflow: auto;
          border-radius: 0 0 15px 15px;
        "
      >
        <div class="p-3">
          <div class="nav nav-tabs nav-justified mb-3">
            <button
              :class="['nav-link', activeRightTab === 1 ? 'active' : '']"
              @click="showRightTab(1)"
            >
              <i class="ri-chat-1-line me-1"></i>Comments
            </button>
            <button
              :class="['nav-link', activeRightTab === 2 ? 'active' : '']"
              @click="showRightTab(2)"
            >
              <i class="ri-file-list-line me-1"></i>Logs
            </button>
            <button
              :class="['nav-link', activeRightTab === 3 ? 'active' : '']"
              @click="showRightTab(3)"
            >
              <i class="ri-flow-chart me-1"></i>Status 
            </button>
          </div>
          <div v-if="activeRightTab === 1" class="comments-section">
            <!-- Comment Input Form -->
            <div class="comment-form mb-4">
              <div class="d-flex align-items-start">
                <div class="comment-avatar me-3">
                  <img
                    :src="$page.props.user.data.avatar"
                    :alt="$page.props.user.data.name || 'User'"
                    class="rounded-circle"
                    style="width: 40px; height: 40px; object-fit: cover"
                  />
                </div>
                <div class="flex-grow-1">
                  <textarea
                    v-model="newComment"
                    class="form-control mb-2"
                    rows="3"
                    placeholder="Add a comment..."
                    :disabled="form.processing"
                  ></textarea>
                  <div class="d-flex justify-content-end">
                    <button
                      @click="submitComment"
                      :disabled="!newComment.trim() || form.processing"
                      class="btn btn-primary btn-sm"
                    >
                      <i class="ri-send-plane-line me-1"></i>
                      {{ form.processing ? 'Posting...' : 'Post Comment' }}
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <div
              v-if="sortedComments && sortedComments.length > 0"
              class="comments-list"
            >
              <div
                v-for="comment in sortedComments"
                :key="comment.id"
                class="comment-item p-3 mb-3"
              >

                <div class="d-flex align-items-start">
                  <div class="comment-avatar me-3">

                    <img
                      :src="comment.user?.profile?.avatar ? '/storage/' + comment.user.profile.avatar : '/images/avatars/avatar.jpg'"
                      alt="image"
                      class="rounded-circle"
                      style="width: 40px; height: 40px; object-fit: cover"
                    />
                  </div>
                  <div class="flex-grow-1">
                    <div
                      class="comment-header d-flex justify-content-between align-items-start mb-2"
                    >
                      <div>
                         <strong
                            >{{ comment.user?.profile?.fullname }}</strong
                          >
                        <small class="text-muted fs-10">{{ comment.source }} - {{
                          formatDate(comment.created_at)
                        }}</small>
                      </div>
                    </div>
                    <div class="comment-content mb-2">
                      <p class="mb-0">{{ comment.content }}</p>
                    </div>
                    <div
                      v-if="comment.replies && comment.replies.length > 0"
                      class="replies-section mt-3"
                    >
                      <div
                        v-for="reply in comment.replies"
                        :key="reply.id"
                        class="reply-item p-2 mb-2 ms-4 border-start"
                      >
                        <div class="d-flex align-items-start">
                          <div class="reply-avatar me-2">
                            <img
                              :src="
                                reply.user?.profile?.avatar ||
                                '/images/avatars/avatar.jpg'
                              "
                              :alt="reply.user?.profile?.firstname"
                              class="rounded-circle"
                              style="width: 30px; height: 30px; object-fit: cover"
                            />
                          </div>
                          <div class="flex-grow-1">
                            <div class="reply-header mb-1">
                              <strong class="small"
                                >{{ reply.user?.profile?.firstname }}
                                {{ reply.user?.profile?.lastname }}</strong
                              >
                              <small class="text-muted ms-2">{{
                                formatDate(reply.created_at)
                              }}</small>
                            </div>
                            <div class="reply-content">
                              <p class="mb-0 small">{{ reply.content }}</p>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div v-else class="text-center text-muted mt-3">
              <small>No comments yet. Be the first to comment!</small>
            </div>
          </div>
          <div v-if="activeRightTab === 2" class="logs-section">
            <div v-if="logs && logs.length > 0" class="logs-list">
              <div v-for="log in logs" :key="log.id" class="log-item p-3 mb-3">
                <div class="d-flex justify-content-between align-items-start">
                  <div class="flex-grow-1">
                    <div class="log-description mb-2">
                      <strong>{{ log.description }}</strong>
                    </div>
                    <div class="log-details small text-muted">
                    
                      <span v-if="log.causer">
                        <i class="ri-user-line me-1"></i>{{ log.causer.profile?.fullname || log.causer.name }}
                      </span>
                      <span class="ms-2">
                        <i class="ri-time-line me-1"></i
                        >{{ formatDate(log.created_at) }}
                      </span>
                    </div>
                    <div
                      v-if="log.changes && Object.keys(log.changes).length > 0"
                      class="log-changes mt-2"
                    >
                      <div class="small fw-bold text-muted mb-1">Changes:</div>
                      <div
                        v-for="(value, key) in log.changes"
                        :key="key"
                        class="change-item"
                      >
                        <span class="change-key">{{ key }}:</span>
                        <span class="change-value">{{ value }}</span>
                      </div>
                    </div>
                  </div>
                  <div class="log-icon">
                    <i class="ri-file-list-line fs-4"></i>
                  </div>
                </div>
              </div>
            </div>
            <div v-else class="text-center text-muted mt-5">
              <i class="ri-file-list-line fs-1"></i>
              <p class="mt-2">No logs available</p>
              <small>Activity logs will appear here</small>
            </div>
          </div>
          <div v-if="activeRightTab === 3" class="status-flow-section">
            <!-- Compact Status Header -->
            <div class="status-flow-header-compact mb-3">
              <div class="d-flex align-items-center">
                <i class="ri-flow-chart text-white me-2 fs-5"></i>
                <div>
                  <h6 class="mb-0 text-white fw-bold">Procurement Progress</h6>
                  <small class="text-white">Track status</small>
                </div>
              </div>
            </div>

            <!-- Main Status - Compact Timeline Design -->
            <div class="status-timeline-compact mb-3">
              <div class="timeline-header d-flex align-items-center justify-content-between mb-2">
                <small class="text-muted fw-bold">
                  <i class="ri-route-line me-1"></i>Main Status
                </small>
                <span class="badge bg-primary badge-sm">{{ procurement.status?.name }}</span>
              </div>
              <div class="timeline-container">
                <div class="timeline-track">
                  <div
                    v-for="(status, index) in statusFlow"
                    :key="status.name"
                    class="timeline-step"
                    :class="{ 'active': status.isCurrent, 'completed': status.isPast, 'pending': !status.isCurrent && !status.isPast }"
                  >
                    <div class="timeline-dot" :class="{ 'pulse': status.isCurrent }">
                      <i v-if="status.isPast" class="ri-check-line"></i>
                      <i v-else-if="status.isCurrent" class="ri-star-fill"></i>
                      <i v-else class="ri-circle-line"></i>
                    </div>
                    <div class="timeline-label">
                      <small class="fw-bold">{{ status.name }}</small>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Sub Status - Compact Timeline Design -->
            <div v-if="procurement.sub_status" class="status-timeline-compact">
              <div class="timeline-header d-flex align-items-center justify-content-between mb-2">
                <small class="text-muted fw-bold">
                  <i class="ri-git-branch-line me-1"></i>Sub Status
                </small>
                <span class="badge bg-info badge-sm">{{ procurement.sub_status?.name }}</span>
              </div>
              <div class="timeline-container">
                <div class="timeline-track">
                  <div
                    v-for="(status, index) in subStatusFlow"
                    :key="status.name"
                    class="timeline-step"
                    :class="{ 'active': status.isCurrent, 'completed': status.isPast, 'pending': !status.isCurrent && !status.isPast }"
                  >
                    <div class="timeline-dot" :class="{ 'pulse': status.isCurrent }">
                      <i v-if="status.isPast" class="ri-check-line"></i>
                      <i v-else-if="status.isCurrent" class="ri-star-fill"></i>
                      <i v-else class="ri-circle-line"></i>
                    </div>
                    <div class="timeline-label">
                      <small class="fw-bold">{{ status.name }}</small>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div v-else class="no-substatus-compact">
              <div class="text-center py-2">
                <i class="ri-information-line text-muted fs-4 mb-1"></i>
                <small class="text-muted d-block">No sub status available</small>
              </div>
            </div>
          </div>

        </div>
      </div>
      <div
        v-else
        class="card-body p-0"
        style="
          height: 100vh;
          overflow: auto;
          border-radius: 0 0 15px 15px;
        "
      >
        <div class="p-2 d-flex flex-column align-items-center">
          <button
            :class="[
              'nav-link mb-2 rounded-pill border-0 transition-all p-2',
              activeRightTab === 1
                ? 'bg-primary text-white shadow-sm'
                : 'bg-white text-dark hover-bg-light',
            ]"
            @click="showRightTab(1)"
            style="transition: all 0.3s ease; width: 50px; height: 50px; position: relative;"
            v-b-tooltip.hover
            title="Comments"
          >
            <i class="ri-chat-1-line fs-5"></i>
            <span v-if="commentCount > 0" class="badge bg-danger"
            style="position: absolute; top: -5px; right: -5px; font-size: 0.9rem; padding: 0.2rem 0.4rem; font-weight: bold;">
            {{ commentCount }}
            </span>
          </button>
          <button
            :class="[
              'nav-link mb-2 rounded-pill border-0 transition-all p-2',
              activeRightTab === 2
                ? 'bg-primary text-white shadow-sm'
                : 'bg-white text-dark hover-bg-light',
            ]"
            @click="showRightTab(2)"
            style="transition: all 0.3s ease; width: 50px; height: 50px; position: relative;"
            v-b-tooltip.hover
            title="Logs"
          >
            <i class="ri-file-list-line fs-5"></i>
            <span v-if="logsCount > 0" class="badge bg-danger" style="position: absolute; top: -5px; right: -5px; font-size: 0.9rem; padding: 0.2rem 0.4rem; font-weight: bold;">
              {{ logsCount }}
            </span>
          </button>
          <button
            :class="[
              'nav-link mb-2 rounded-pill border-0 transition-all p-2',
              activeRightTab === 3
                ? 'bg-primary text-white shadow-sm'
                : 'bg-white text-dark hover-bg-light',
            ]"
            @click="showRightTab(3)"
            style="transition: all 0.3s ease; width: 50px; height: 50px"
            v-b-tooltip.hover
            title="Status"
          >
            <i class="ri-flow-chart fs-5"></i>
          </button>

        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { useForm } from "@inertiajs/vue3";

export default {
  props: ["procurement", "logs", "isRightCollapsed"],
  emits: ["toggleRightSidebar"],
  data() {
    return {
      activeRightTab: 1,
      newComment: '',
      form: useForm({
        content: '',
      }),
    };
  },
  computed: {
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

      return allComments.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
    },
    commentCount() {
      return this.sortedComments.length;
    },
    statusFlow() {
      // Define the procurement main status flow
      const currentStatus = this.procurement.status?.name;
      let statusFlow = [
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
        { name: 'Delivered/For Inspection', isCurrent: currentStatus === 'Delivered/For Inspection' },
        { name: 'Completed', isCurrent: currentStatus === 'Completed' },
      ];

      // Only show Re-award and Rebid if current status is Re-award or Rebid
      if (currentStatus === 'Re-award') {
        statusFlow.splice(-1, 0, { name: 'Re-award', isCurrent: true });
      } else if (currentStatus === 'Rebid') {
        statusFlow.splice(-1, 0, { name: 'Rebid', isCurrent: true });
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
          { name: 'Delivered/For Inspection', isCurrent: currentSubStatus === 'Delivered/For Inspection' },
          { name: 'Completed', isCurrent: currentSubStatus === 'Completed' },
        ];
      } else if (currentStatus === 'Re-award') {
        // Re-award flow: starts with For NOA
        subStatusFlow = [
          { name: 'For NOA', isCurrent: currentSubStatus === 'For NOA' },
          { name: 'NOA Served to Supplier', isCurrent: currentSubStatus === 'NOA Served to Supplier' },
          { name: 'NOA Conformed', isCurrent: currentSubStatus === 'NOA Conformed' },
          { name: 'PO Issued', isCurrent: currentSubStatus === 'PO Issued' },
          { name: 'Delivered/For Inspection', isCurrent: currentSubStatus === 'Delivered/For Inspection' },
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
          { name: 'Delivered/For Inspection', isCurrent: currentSubStatus === 'Delivered/For Inspection' },
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
    this.activeRightTab = parseInt(localStorage.getItem("activeRightTab")) || 1;
    this.listenForComments();
  },
  methods: {
    toggleRightSidebar() {
      this.$emit("toggleRightSidebar");
    },
    showRightTab(tab) {
      this.activeRightTab = tab;
      localStorage.setItem("activeRightTab", tab);
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
    submitComment() {
      if (!this.newComment.trim()) return;

      this.form.content = this.newComment;
      this.form.post(`/faims/procurements/${this.procurement.id}/comments`, {
        onSuccess: () => {
          this.newComment = '';
          this.form.reset();
          // No need to reload since we listen for real-time updates
        },
        onError: () => {
          // Handle error if needed
        }
      });
    },
    listenForComments() {
      if (window.Echo) {
        window.Echo.private(`procurement.${this.procurement.id}`)
          .listen('.comment.added', (e) => {
            // Add the new comment to the appropriate list based on commentable_type
            const comment = e.comment;
            if (comment.commentable_type === 'App\\Models\\ProcurementBac') {
              const bac = this.procurement.bac_resolutions.find(b => b.id === comment.commentable_id);
              if (bac && bac.comments) {
                bac.comments.push(comment);
              }
            } else if (comment.commentable_type === 'App\\Models\\ProcurementBacNoa') {
              const noa = this.procurement.noas.find(n => n.id === comment.commentable_id);
              if (noa && noa.comments) {
                noa.comments.push(comment);
              }
            } else if (comment.commentable_type === 'App\\Models\\ProcurementNoaPo') {
              const po = this.procurement.pos.find(p => p.id === comment.commentable_id);
              if (po && po.comments) {
                po.comments.push(comment);
              }
            } else if (comment.commentable_type === 'App\\Models\\ProcurementQuotation') {
              const quotation = this.procurement.quotations.find(q => q.id === comment.commentable_id);
              if (quotation && quotation.comments) {
                quotation.comments.push(comment);
              }
            } else {
              // Procurement comment
              if (this.procurement.comments) {
                this.procurement.comments.push(comment);
              }
            }
          });
      }
    },
  },
};
</script>

<style scoped>
/* Enhanced Comment Avatar Styling */
.comment-avatar {
  position: relative;
}

.comment-avatar img {
  border: 2px solid #e9ecef;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  transition: all 0.3s ease;
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
}

.comment-avatar img:hover {
  border-color: #007bff;
  box-shadow: 0 4px 12px rgba(0, 123, 255, 0.2);
  transform: scale(1.05);
}

.reply-avatar {
  position: relative;
}

.reply-avatar img {
  border: 2px solid #dee2e6;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.08);
  transition: all 0.2s ease;
  background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
}

.reply-avatar img:hover {
  border-color: #6c757d;
  box-shadow: 0 2px 6px rgba(108, 117, 125, 0.15);
  transform: scale(1.02);
}

/* Avatar Online Indicator */
.comment-avatar::after,
.reply-avatar::after {
  content: '';
  position: absolute;
  bottom: 2px;
  right: 2px;
  width: 10px;
  height: 10px;
  background: #28a745;
  border: 2px solid white;
  border-radius: 50%;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
}

/* Enhanced Comment Content Styling */
.comment-item {
  background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
  border: 1px solid #e9ecef;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
  transition: all 0.3s ease;
  margin-bottom: 1rem;
}

.comment-item:hover {
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
  transform: translateY(-1px);
}

.comment-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.75rem;
  padding-bottom: 0.5rem;
  border-bottom: 1px solid #f1f3f4;
}

.comment-header .comment-user-info {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.comment-header strong {
  color: #2c3e50;
  font-weight: 600;
  font-size: 0.95rem;
}

.comment-header small {
  color: #6c757d;
  font-size: 0.8rem;
  font-weight: 500;
  background: rgba(108, 117, 125, 0.1);
  padding: 0.25rem 0.5rem;
  border-radius: 6px;
  display: inline-block;
}

.comment-content {
  color: #495057;
  line-height: 1.6;
  font-size: 0.9rem;
  margin-bottom: 0;
}

.comment-content p {
  margin-bottom: 0;
  word-wrap: break-word;
}

/* Reply Styling */
.reply-item {
  background: #f8f9fa;
  border: 1px solid #dee2e6;
  border-radius: 8px;
  margin-left: 2rem;
  margin-top: 0.75rem;
  padding: 0.75rem;
  position: relative;
}

.reply-item::before {
  content: '';
  position: absolute;
  left: -1rem;
  top: 1rem;
  width: 0;
  height: 0;
  border-top: 8px solid transparent;
  border-bottom: 8px solid transparent;
  border-right: 8px solid #dee2e6;
}

.reply-item::after {
  content: '';
  position: absolute;
  left: -0.75rem;
  top: 1rem;
  width: 0;
  height: 0;
  border-top: 7px solid transparent;
  border-bottom: 7px solid transparent;
  border-right: 7px solid #f8f9fa;
}

.reply-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.5rem;
}

.reply-header strong {
  color: #495057;
  font-weight: 600;
  font-size: 0.85rem;
}

.reply-header small {
  color: #6c757d;
  font-size: 0.75rem;
  font-weight: 500;
}

.reply-content {
  color: #6c757d;
  font-size: 0.85rem;
  line-height: 1.5;
}

.reply-content p {
  margin-bottom: 0;
}

/* Comment Form Styling */
.comment-form {
  background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
  border: 1px solid #e9ecef;
  border-radius: 12px;
  padding: 1rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.comment-form textarea {
  border: 1px solid #ced4da;
  border-radius: 8px;
  resize: vertical;
  font-size: 0.9rem;
  line-height: 1.5;
  transition: border-color 0.3s ease, box-shadow 0.3s ease;
}

.comment-form textarea:focus {
  border-color: #007bff;
  box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.comment-form .btn {
  border-radius: 6px;
  font-weight: 500;
  transition: all 0.3s ease;
}

.comment-form .btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
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
</style>
