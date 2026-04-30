<template>
  <Head title="Budget Requests" />
  <PageHeader title="Budget Requests" pageTitle="PAP Codes" />

  <BRow class="procurement-index-page">
    <div class="col-md-12">
      <div class="card bg-light-subtle shadow-none border">
        <div class="card-header bg-light-subtle">
          <div class="d-flex mb-n3">
            <div class="flex-shrink-0 me-3">
              <div style="height: 2.5rem; width: 2.5rem">
                <span class="avatar-title bg-success-subtle rounded p-2 mt-n1">
                  <i class="ri-funds-line text-success fs-24"></i>
                </span>
              </div>
            </div>
            <div class="flex-grow-1">
              <h5 class="mb-0 fs-14">
                <span class="text-body">PAP Code Budget Requests</span>
              </h5>
              <p class="text-muted text-truncate-two-lines fs-12">
                Additional budget and realignment requests submitted from PAP codes for Budget Officer review.
              </p>
            </div>
          </div>
        </div>

        <div class="car-body bg-white border-bottom shadow-none">
          <b-row class="mb-2 ms-1 me-1" style="margin-top: 12px">
            <b-col lg>
              <div class="input-group mb-1">
                <span class="input-group-text">
                  <i class="ri-search-line search-icon"></i>
                </span>
                <input
                  type="text"
                  v-model="filter.keyword"
                  placeholder="Search PAP code, title, requester, or justification"
                  class="form-control"
                  style="width: 50%"
                />
                <select v-model="filter.status" class="form-select" style="max-width: 180px" >
                  <option value="all">All</option>
                  <option value="pending">Pending</option>
                  <option value="approved">Approved</option>
                  <option value="rejected">Rejected</option>
                </select>
                <span
                  @click="refresh()"
                  class="input-group-text"
                  v-b-tooltip.hover
                  title="Refresh"
                  style="cursor: pointer"
                >
                  <i class="bx bx-refresh search-icon"></i>
                </span>
              </div>
            </b-col>
          </b-row>
        </div>

        <b-card no-body>
          <div class="card-body bg-white rounded-bottom mt-3">
            <div
              class="table-responsive table-card"
              style="margin-top: -39px; height: calc(100vh - 350px); overflow: auto"
            >
              <table class="table align-middle table-hover mb-0">
                <thead class="table-light thead-fixed">
                  <tr class="fs-12 fw-semibold">
                    <th style="width: 4%" class="text-center">#</th>
                    <th style="width: 14%">PAP Code</th>
                    <th style="width: 20%">Project Description/Title</th>
                    <th style="width: 14%">Request</th>
                    <th style="width: 14%">Current Budget</th>
                    <th style="width: 14%">Requested By</th>
                    <th style="width: 10%">Status</th>
                    <th style="width: 10%" class="text-center">Actions</th>
                  </tr>
                </thead>

                <tbody class="table-group-divider">
                  <tr
                    v-for="(list, index) in lists"
                    :key="list.id"
                    @click="selectRow(list.id)"
                    :class="{ 'table-active': selectedRow === list.id }"
                    class="cursor-pointer"
                  >
                    <td class="text-center fw-semibold">{{ rowNumber(index) }}</td>
                    <td>
                      <h6 class="mb-0 fs-14 fw-semibold text-success">
                        {{ list.procurement_code?.code || "-" }}
                      </h6>
                      <small class="text-muted">{{ list.procurement_code?.year || "-" }}</small>
                    </td>
                    <td>
                      <div
                        class="text-truncate"
                        style="max-width: 260px"
                        v-b-tooltip.hover
                        :title="list.procurement_code?.title"
                      >
                        {{ list.procurement_code?.title || "-" }}
                      </div>
                      <small
                        class="d-block text-muted text-truncate"
                        style="max-width: 260px"
                        v-b-tooltip.hover
                        :title="list.description"
                      >
                        {{ list.description || "No justification provided" }}
                      </small>
                    </td>
                    <td>
                      <div class="fw-semibold text-primary">
                        {{ formatCurrency(list.amount) }}
                      </div>
                      <b-badge
                        class="mt-1"
                        :class="list.request_type === 'realignment'
                          ? 'bg-info-subtle text-info'
                          : 'bg-primary-subtle text-primary'"
                      >
                        {{ list.request_type_label }}
                      </b-badge>
                      <small
                        v-if="list.source_procurement_code"
                        class="d-block text-muted mt-1"
                      >
                        From: {{ list.source_procurement_code.code }}
                      </small>
                      <a
                        v-if="list.attachment_url"
                        :href="list.attachment_url"
                        target="_blank"
                        rel="noopener"
                        class="small"
                        @click.stop
                      >
                        <i class="ri-attachment-2 align-bottom"></i>
                        {{ list.attachment_name || "Supporting file" }}
                      </a>
                    </td>
                    <td>
                      <div class="fw-semibold">
                        {{ formatCurrency(list.procurement_code?.allocated_budget) }}
                      </div>
                      <small class="d-block text-muted">
                        Remaining: {{ formatCurrency(list.procurement_code?.remaining_budget) }}
                      </small>
                    </td>
                    <td>
                      <div class="fw-medium">{{ getRequestedBy(list) }}</div>
                      <small class="text-muted">{{ formatDate(list.created_at) }}</small>
                    </td>
                    <td>
                      <b-badge :class="statusBadgeClass(list.status)">
                        {{ list.status_label }}
                      </b-badge>
                    </td>
                    <td>
                      <div class="d-flex justify-content-center gap-1">
                        <b-button
                          @click.stop="openProfile(list)"
                          size="sm"
                          variant="primary"
                          class="btn-icon"
                          v-b-tooltip.hover
                          title="View PAP Code"
                          style="border-radius: 8px"
                        >
                          <i class="ri-eye-line"></i>
                        </b-button>
                        <b-button
                          v-if="canReviewBudgetRequests && isPending(list)"
                          @click.stop="reviewRequest(list, 'approve')"
                          size="sm"
                          variant="success"
                          class="btn-icon"
                          v-b-tooltip.hover
                          title="Approve"
                          :disabled="processingLogId === list.id"
                          style="border-radius: 8px"
                        >
                          <span
                            v-if="processingLogId === list.id"
                            class="spinner-border spinner-border-sm"
                          ></span>
                          <i v-else class="ri-check-line"></i>
                        </b-button>
                        <b-button
                          v-if="canReviewBudgetRequests && isPending(list)"
                          @click.stop="reviewRequest(list, 'reject')"
                          size="sm"
                          variant="outline-danger"
                          class="btn-icon"
                          v-b-tooltip.hover
                          title="Reject"
                          :disabled="processingLogId === list.id"
                          style="border-radius: 8px"
                        >
                          <i class="ri-close-line"></i>
                        </b-button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>

              <div v-if="!lists.length" class="text-center text-muted py-5">
                No budget requests found.
              </div>

              <div class="card-footer">
                <Pagination
                  class="ms-2 me-2 mt-n1"
                  v-if="meta"
                  @fetch="fetch"
                  :lists="lists.length"
                  :links="links"
                  :pagination="meta"
                />
              </div>
            </div>
          </div>
        </b-card>
      </div>
    </div>
  </BRow>
</template>

<script>
import _ from "lodash";
import { router } from "@inertiajs/vue3";
import PageHeader from "@/Shared/Components/PageHeader.vue";
import Pagination from "@/Shared/Components/Pagination.vue";

export default {
  components: {
    Pagination,
    PageHeader,
  },
  data() {
    return {
      lists: [],
      meta: {},
      links: {},
      filter: {
        keyword: null,
        status: "all",
      },
      selectedRow: null,
      processingLogId: null,
    };
  },
  watch: {
    "filter.keyword": function (newVal) {
      this.checkSearchStr(newVal);
    },
    "filter.status": function () {
      this.fetch();
    },
  },
  created() {
    this.fetch();
  },
  computed: {
    currentRoles() {
      return Array.isArray(this.$page?.props?.roles) ? this.$page.props.roles : [];
    },
    canReviewBudgetRequests() {
      return this.currentRoles.includes("Budget Officer");
    },
  },
  methods: {
    checkSearchStr: _.debounce(function () {
      this.fetch();
    }, 300),
    fetch(pageUrl) {
      const url = pageUrl || "/faims/procurement-code-budget-requests";
      axios
        .get(url, {
          params: {
            keyword: this.filter.keyword,
            status: this.filter.status,
            option: "lists",
          },
        })
        .then((response) => {
          this.lists = response.data.data;
          this.meta = response.data.meta;
          this.links = response.data.links;
        })
        .catch((err) => console.log(err));
    },
    reviewRequest(log, action) {
      if (!this.canReviewBudgetRequests || !this.isPending(log) || this.processingLogId) {
        return;
      }

      this.processingLogId = log.id;

      router.patch(
        `/faims/procurement-codes/${log.procurement_code.id}/budget-increase-requests/${log.id}/${action}`,
        {},
        {
          preserveScroll: true,
          preserveState: true,
          onSuccess: () => this.fetch(),
          onFinish: () => {
            this.processingLogId = null;
          },
        }
      );
    },
    openProfile(log) {
      if (log.procurement_code?.id) {
        router.get(`/faims/procurement-codes/${log.procurement_code.id}`);
      }
    },
    refresh() {
      this.filter.keyword = null;
      this.filter.status = "all";
      this.fetch();
    },
    selectRow(index) {
      this.selectedRow = this.selectedRow === index ? null : index;
    },
    rowNumber(index) {
      const currentPage = Number(this.meta?.current_page || 1);
      const perPage = Number(this.meta?.per_page || this.lists.length || 10);
      return (currentPage - 1) * perPage + index + 1;
    },
    isPending(log) {
      return log?.status === "pending";
    },
    getRequestedBy(log) {
      return log?.requested_by?.name || "System";
    },
    formatCurrency(value) {
      return new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
      }).format(Number(value || 0));
    },
    formatDate(value) {
      if (!value) {
        return "-";
      }

      return new Intl.DateTimeFormat("en-PH", {
        year: "numeric",
        month: "short",
        day: "2-digit",
      }).format(new Date(value));
    },
    statusBadgeClass(status) {
      switch (status) {
        case "approved":
          return "bg-success-subtle text-success";
        case "rejected":
          return "bg-danger-subtle text-danger";
        default:
          return "bg-warning-subtle text-warning";
      }
    },
  },
};
</script>
