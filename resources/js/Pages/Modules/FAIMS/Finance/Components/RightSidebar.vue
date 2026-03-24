<template>
  <div :class="['transition-all', isRightCollapsed ? 'col-md-1' : 'col-md-3']" style="transition: all 0.3s ease; height: 100%">
    <div class="card h-90 mb-3 shadow-lg border-0" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border-radius: 15px; height: 100vh;">
      <div class="card-header bg-primary text-white border-0 d-flex align-items-center justify-content-between" style="border-radius: 15px 15px 0 0 !important; padding: 1rem">
        <div v-if="!isRightCollapsed">
          <h5 class="card-title mb-1">
            <i :class="activeRightTab === 1 ? 'ri-chat-1-line' : activeRightTab === 2 ? 'ri-file-list-line' : 'ri-flow-chart'"></i>
            <span class="ms-2 text-white">
              {{ activeRightTab === 1 ? 'Finance Comments' : activeRightTab === 2 ? 'Activity Logs' : 'Status' }}
            </span>
          </h5>
        </div>
        <button @click="toggleRightSidebar" class="btn btn-sm btn-light rounded-circle p-2 ms-2" style="width: 40px; height: 40px">
          <i :class="isRightCollapsed ? 'ri-arrow-left-line' : 'ri-arrow-right-line'" class="text-primary fs-6"></i>
        </button>
      </div>

      <div v-if="!isRightCollapsed" class="card-body p-0" style="height: 100vh; overflow: auto; border-radius: 0 0 15px 15px;">
        <div class="p-3">
          <div class="nav nav-tabs nav-justified mb-3">
            <button :class="['nav-link', activeRightTab === 1 ? 'active' : '']" @click="showRightTab(1)"><i class="ri-chat-1-line me-1"></i>Comments</button>
            <button :class="['nav-link', activeRightTab === 2 ? 'active' : '']" @click="showRightTab(2)"><i class="ri-file-list-line me-1"></i>Logs</button>
            <button :class="['nav-link', activeRightTab === 3 ? 'active' : '']" @click="showRightTab(3)"><i class="ri-flow-chart me-1"></i>Status</button>
          </div>

          <div v-if="activeRightTab === 1">
            <div class="comment-form mb-3">
              <textarea v-model="newComment" class="form-control mb-2" rows="3" placeholder="Add a comment..." :disabled="form.processing"></textarea>
              <div class="d-flex justify-content-end">
                <button @click="submitComment" :disabled="!newComment.trim() || form.processing" class="btn btn-primary btn-sm">
                  <i class="ri-send-plane-line me-1"></i>{{ form.processing ? 'Posting...' : 'Post Comment' }}
                </button>
              </div>
            </div>

            <div v-if="sortedComments.length" class="comments-list">
              <div v-for="comment in sortedComments" :key="comment.id" class="comment-item p-3 mb-2 bg-white rounded border">
                <div class="d-flex justify-content-between align-items-start mb-1">
                  <strong>{{ comment.user?.profile?.fullname || comment.user?.name || 'User' }}</strong>
                  <small class="text-muted">{{ formatDate(comment.created_at) }}</small>
                </div>
                <p class="mb-0">{{ comment.content }}</p>
              </div>
            </div>
            <div v-else class="text-center text-muted mt-3"><small>No comments yet.</small></div>
          </div>

          <div v-if="activeRightTab === 2">
            <div v-if="logs && logs.length" class="logs-list">
              <div v-for="log in logs" :key="log.id" class="log-item p-3 mb-2 bg-white rounded border">
                <div class="fw-semibold mb-1">{{ log.description }}</div>
                <div class="small text-muted d-flex flex-column gap-1">
                  <span><i class="ri-user-line me-1"></i>{{ log.causer?.profile?.fullname || log.causer?.name || 'System' }}</span>
                  <span><i class="ri-time-line me-1"></i>{{ formatDate(log.created_at) }}</span>
                </div>
              </div>
            </div>
            <div v-else class="text-center text-muted mt-3"><small>No logs available.</small></div>
          </div>

          <div v-if="activeRightTab === 3">
            <div class="table-responsive">
              <table class="table table-sm align-middle mb-0">
                <tbody>
                  <tr v-for="step in statusFlow" :key="step.name">
                    <td class="fw-semibold">{{ step.name }}</td>
                    <td class="text-end">
                      <span v-if="step.isCurrent" class="badge bg-primary">Current</span>
                      <span v-else-if="step.isPast" class="badge bg-success">Done</span>
                      <span v-else class="badge bg-secondary">Pending</span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <div v-else class="card-body p-0" style="height: 100vh; overflow: auto; border-radius: 0 0 15px 15px;">
        <div class="p-2 d-flex flex-column align-items-center">
          <button :class="['nav-link mb-2 rounded-pill border-0 transition-all p-2', activeRightTab === 1 ? 'bg-primary text-white shadow-sm' : 'bg-white text-dark hover-bg-light']" @click="showRightTab(1)" style="width: 50px; height: 50px" v-b-tooltip.hover title="Comments"><i class="ri-chat-1-line fs-5"></i></button>
          <button :class="['nav-link mb-2 rounded-pill border-0 transition-all p-2', activeRightTab === 2 ? 'bg-primary text-white shadow-sm' : 'bg-white text-dark hover-bg-light']" @click="showRightTab(2)" style="width: 50px; height: 50px" v-b-tooltip.hover title="Logs"><i class="ri-file-list-line fs-5"></i></button>
          <button :class="['nav-link mb-2 rounded-pill border-0 transition-all p-2', activeRightTab === 3 ? 'bg-primary text-white shadow-sm' : 'bg-white text-dark hover-bg-light']" @click="showRightTab(3)" style="width: 50px; height: 50px" v-b-tooltip.hover title="Status"><i class="ri-flow-chart fs-5"></i></button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { router, useForm } from "@inertiajs/vue3";

export default {
  props: ["request", "logs", "isRightCollapsed"],
  emits: ["toggleRightSidebar"],
  data() {
    return {
      activeRightTab: 1,
      newComment: "",
      form: useForm({
        content: "",
      }),
    };
  },
  computed: {
    sortedComments() {
      const comments = Array.isArray(this.request?.comments) ? this.request.comments : [];
      return [...comments].sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
    },
    statusFlow() {
      const currentStatus = this.request?.status?.name;
      const steps = [
        "Pending",
        "Verified",
        "Validated",
        "Allotment Available",
        "Obligated",
        "For Disbursement",
        "Funds Available",
        "Charged",
        "Approved",
        "Completed/LBP Processing",
      ];
      const currentIndex = steps.findIndex((name) => name === currentStatus);
      return steps.map((name, index) => ({
        name,
        isCurrent: name === currentStatus,
        isPast: currentIndex !== -1 && index < currentIndex,
      }));
    },
  },
  mounted() {
    this.activeRightTab = parseInt(localStorage.getItem("financeActiveRightTab") || "1", 10);
  },
  methods: {
    toggleRightSidebar() {
      this.$emit("toggleRightSidebar");
    },
    showRightTab(tab) {
      this.activeRightTab = tab;
      localStorage.setItem("financeActiveRightTab", String(tab));
    },
    formatDate(dateString) {
      if (!dateString) return "-";
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
      this.form.post(`/faims/finance-requests/${this.request.id}/comments`, {
        preserveScroll: true,
        onSuccess: () => {
          this.newComment = "";
          this.form.reset();
          router.reload({ only: ["request", "logs"] });
        },
      });
    },
  },
};
</script>
