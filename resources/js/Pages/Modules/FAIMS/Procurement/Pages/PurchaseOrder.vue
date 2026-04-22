<template>
  <div>
    <PageHeader class="purchase-order-header mt-1 pt-2" title="Purchase Orders" />
    <BRow class="purchase-order-layout g-0">
      <div class="col-12">
        <div class="card purchase-order-shell shadow-none border-0">
          <div class="car-body purchase-order-toolbar border-bottom shadow-none">
            <b-row class="purchase-order-toolbar-row">
              <b-col lg>
                <div class="input-group purchase-order-filters">
                  <span class="input-group-text purchase-order-input-addon">
                    <i class="ri-search-line search-icon"></i
                  ></span>
                  <input
                    type="text"
                    v-model="filter.keyword"
                    placeholder="Search Purchase Orders"
                    class="form-control purchase-order-search-input"
                  />
                  <Multiselect
                    class="white purchase-order-filter"
                    :options="dropdowns.statuses"
                    v-model="filter.status"
                    label="name"
                    :searchable="true"
                    placeholder="Select Status"
                  />

                  <span
                    @click="refresh()"
                    class="input-group-text purchase-order-input-addon"
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

          <div class="card purchase-order-tabs-shell border-bottom shadow-none" no-body>
            <div class="d-flex">
              <div class="flex-grow-1">
                <ul class="nav nav-tabs nav-tabs-custom nav-primary fs-12 purchase-order-tabs" role="tablist">
                  <li class="nav-item">
                    <BLink
                      class="nav-link py-3 active"
                      data-bs-toggle="tab"
                      role="tab"
                      aria-selected="true"
                      @click="viewStatus(null)"
                    >
                      <i class="ri-apps-2-line me-1 align-bottom"></i> All
                    </BLink>
                  </li>
                  <li class="nav-item">
                    <BLink
                      v-if="canAccessInspectionTab"
                      class="nav-link py-3"
                      data-bs-toggle="tab"
                      role="tab"
                      aria-selected="true"
                      @click="viewStatus('Items Delivered')"
                    >
                      <i class="ri-file-search-line me-1 align-bottom"></i> Inspection and
                      Acceptance
                    </BLink>
                  </li>
                </ul>
              </div>
              <div class="flex-shrink-0">
                <div class="d-flex flex-wrap gap-2 mt-3"></div>
              </div>
            </div>
          </div>

          <div class="card-body purchase-order-content rounded-bottom">
            <div class="table-responsive table-card purchase-order-table-shell">
              <table class="table align-middle table-striped table-centered mb-0 purchase-order-table">
                <thead class="table-light thead-fixed purchase-order-thead">
                  <tr class="fs-11">
                    <th style="width: 3%" class="text-center">#</th>
                    <th style="width: 10%">Code</th>
                    <th style="width: 18%; min-width: 220px;" class="text-center">PO Dates</th>
                    <th style="width: 12%" class="text-center">Delivery Term</th>
                    <th style="width: 12%" class="text-center">Payment Term</th>
                    <th style="width: 11%" class="text-center">Date of delivery</th>
                    <th style="width: 14%" class="text-center">NOA Code</th>
                    <th style="width: 10%" class="text-center">Status</th>
                    <th style="width: 10%; min-width: 152px;" class="text-center">Actions</th>
                  </tr>
                </thead>
                <tbody class="table-white fs-12">
                  <tr v-for="(list, index) in lists" v-bind:key="index">
                    <td class="text-center">
                      {{ (meta.current_page - 1) * meta.per_page + index + 1 }}.
                    </td>
                    <td>
                      <h5 class="fs-13 mb-0 fw-semibold text-primary">{{ list.code }}</h5>
                    </td>
                    <td>
                      <div class="po-date-stack">
                        <div class="po-date-row">
                          <span class="po-date-label">PO Date</span>
                          <span class="po-date-value">{{ list.po_date }}</span>
                        </div>
                        <div class="po-date-row">
                          <span class="po-date-label">Released Date</span>
                          <span :class="['po-date-value', { 'text-muted': !list.released_at }]">
                            {{ list.released_at || "Not yet" }}
                          </span>
                        </div>
                        <div class="po-date-row">
                          <span class="po-date-label">Conformed Date</span>
                          <span :class="['po-date-value', { 'text-muted': !list.conformed_at }]">
                            {{ list.conformed_at || "Not yet" }}
                          </span>
                        </div>
                      </div>
                    </td>
                    <td class="text-center">
                      {{ list.delivery_term }}
                    </td>
                    <td class="text-center">{{ list.payment_term }}</td>
                    <td class="text-center">
                      <span>
                        {{ list.date_of_delivery }}
                      </span>
                      <span else></span>
                    </td>
                    <td class="text-center">
                      {{ list.noa.code }}
                    </td>
                    <td class="text-center">
                      <b-badge :class="[list.status.bg, 'purchase-order-status-badge']">{{ list.status?.name }}</b-badge>
                    </td>
                    <td class="text-end purchase-order-actions-cell">
                      <div class="d-flex gap-1 justify-content-center purchase-order-actions">
                        <button
                          @click="viewPO(list)"
                          class="btn btn-primary btn-sm purchase-order-action-btn"
                          v-b-tooltip.hover
                          title="View"
                        >
                          <i class="ri-eye-fill"></i>
                        </button>
                        <button
                          v-if="['Items Delivered', 'Delivered/For Inspection'].includes(list.status.name) && canAccessInspectionTab"
                          @click="updateStatus(list)"
                          class="btn btn-warning btn-sm purchase-order-action-btn"
                          v-b-tooltip.hover
                          title="Update Status"
                        >
                          <i class="ri-edit-circle-fill"></i>
                        </button>
                        <button
                          v-if="list.status.name == 'Completed' && canAccessInspectionTab"
                          @click="revertStatus(list)"
                          class="btn btn-outline-warning btn-sm purchase-order-action-btn"
                          v-b-tooltip.hover
                          title="Revert Status"
                        >
                          <i class="ri-arrow-go-back-line"></i>
                        </button>
                        <button
                          @click="openPrint(list)"
                          class="btn btn-dark btn-sm purchase-order-action-btn"
                          v-b-tooltip.hover
                          title="Print PO"
                        >
                          <i class="ri-printer-line"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                  <tr v-if="lists.length === 0">
                    <td colspan="9" class="text-center py-5">
                      <div class="empty-state purchase-order-empty-state">
                        <div class="empty-state-icon purchase-order-empty-state__icon">
                          <i class="ri-shopping-cart-line"></i>
                        </div>
                        <h6 class="empty-state-title purchase-order-empty-state__title">No Purchase Orders</h6>
                        <p class="empty-state-message purchase-order-empty-state__message">No purchase orders have been created for this procurement yet.</p>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
          <div class="card-footer purchase-order-footer">
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
    </BRow>
    <Inspection :procurement="procurement" @add="fetch()" ref="inspection" />
    <IARItemSelection @updated="fetch()" ref="iarSelection" />
  </div>
</template>
<script>
import _ from "lodash";

import Inspection from "../Modals/Inspection.vue";
import IARItemSelection from "../Modals/IARItemSelection.vue";
import Multiselect from "@vueform/multiselect";
import PageHeader from "@/Shared/Components/PageHeader.vue";
import Pagination from "@/Shared/Components/Pagination.vue";
import { router } from "@inertiajs/vue3";

export default {
  components: { PageHeader, Pagination, Multiselect, Inspection, IARItemSelection },
  props: ["dropdowns", "procurement"],
  computed: {
    canAccessInspectionTab() {
      const roles = this.$page.props.roles || [];
      return (
        roles.includes("Procurement Staff") ||
        roles.includes("Procurement Officer") ||
        roles.includes("Administrator")
      );
    },
  },
  data() {
    return {
      currentUrl: window.location.origin,
      lists: [],
      meta: {},
      links: {},
      filter: {
        keyword: null,
        type: null,
        status: null,
        mode: null,
        expense: null,
        leave: null,
      },
      icons: [
        "ri-flight-takeoff-fill",
        "ri-car-fill",
        "ri-calendar-2-fill",
        "ri-alarm-fill",
      ],
      index: null,
      units: [],
    };
  },
  watch: {
    "filter.keyword"(newVal) {
      this.checkSearchStr(newVal);
    },
    "filter.status"(newVal) {
      this.fetch();
    },
    "filter.mode"(newVal) {
      this.fetch();
    },
    "filter.expense"(newVal) {
      this.fetch();
    },
  },
  created() {
    this.fetch();
  },
  methods: {
    checkSearchStr: _.debounce(function (string) {
      this.fetch();
    }, 300),
    fetch(page_url) {
      page_url = "/faims/purchase-orders";
      axios
        .get(page_url, {
          params: {
            keyword: this.filter.keyword,
            status: this.filter.status,
            option: "lists",
            procurement_id: this.procurement.id,
          },
        })
        .then((response) => {
          if (response) {
            this.lists = response.data.data;
            this.meta = response.data.meta;
            this.links = response.data.links;
          }
        })
        .catch((err) => console.log(err));
    },
    formatDateRange(start, end) {
      const startDate = new Date(start);
      const endDate = new Date(end);

      const options = { month: "long", day: "numeric" };
      const startStr = startDate.toLocaleDateString("en-US", options);
      const endStr = endDate.toLocaleDateString("en-US", { day: "numeric" });

      if (start === end) {
        return startDate.toLocaleDateString("en-US", {
          month: "long",
          day: "numeric",
          year: "numeric",
        });
      }

      const year = startDate.getFullYear(); // assume same year
      return `${startStr}-${endStr}, ${year}`;
    },

    updateStatus(data) {
      this.$refs.inspection.show(data, "PO");
    },
    revertStatus(data) {
      this.$refs.inspection.show(data, "PO", "revert");
    },

    viewStatus(status) {
      if (!this.canAccessInspectionTab && status === "Items Delivered") {
        return;
      }
      this.filter.status = status;
      this.fetch();
    },

    viewPO(data) {
      const noaId = data?.noa_id ?? data?.noa?.id;
      const procurementId = data?.procurement_id ?? data?.noa?.procurement_id ?? this.procurement?.id;

      if (!procurementId || !noaId) {
        return;
      }

      router.visit(`/faims/procurements/${procurementId}?option=view&tab=6&noa_id=${noaId}`);
    },

    openPrint(data) {
      window.open(`/faims/purchase-orders/${data.id}?option=print&type=purchase_order`);
    },


    refresh() {
      this.filter.expense = null;
      this.filter.mode = null;
      this.filter.keyword = null;
      this.fetch();
    },
  },
};
</script>

<style scoped>
.purchase-order-page {
  --po-page-bg: #f6f8ff;
  --po-surface: #ffffff;
  --po-surface-soft: #f8fbff;
  --po-surface-alt: #eef4ff;
  --po-border: rgba(148, 163, 184, 0.22);
  --po-text: #1e293b;
  --po-muted: #64748b;
  --po-primary: #4f6de6;
}

.purchase-order-page :deep(.page-title-box) {
  margin: -8px 0 0.75rem;
  padding: 0.4rem 0;
  background: transparent;
  border-bottom: 0;
  box-shadow: none;
}

.purchase-order-page :deep(.page-title-box h4) {
  letter-spacing: 0.01em;
}

.purchase-order-layout {
  margin: 0;
}

.purchase-order-shell {
  overflow: hidden;
  border-radius: 22px;
  background: linear-gradient(180deg, #fbfdff 0%, var(--po-page-bg) 100%);
  box-shadow: 0 18px 38px rgba(15, 23, 42, 0.08);
}

.purchase-order-toolbar,
.purchase-order-tabs-shell,
.purchase-order-content,
.purchase-order-footer {
  background: transparent;
}

.purchase-order-toolbar {
  padding: 0.5rem 0.75rem 0.35rem;
  border-color: rgba(226, 232, 240, 0.9) !important;
}

.purchase-order-toolbar-row {
  margin: 0 !important;
}

.purchase-order-filters {
  margin-bottom: 0;
  align-items: stretch;
}

.purchase-order-input-addon {
  background: var(--po-surface);
  color: var(--po-primary);
  border-color: rgba(203, 213, 225, 0.95);
  justify-content: center;
  min-width: 50px;
}

.purchase-order-search-input {
  flex: 1 1 380px;
  min-width: 220px;
  background: var(--po-surface);
  border-color: rgba(203, 213, 225, 0.95);
}

.purchase-order-search-input:focus {
  border-color: rgba(79, 109, 230, 0.45);
  box-shadow: 0 0 0 0.18rem rgba(79, 109, 230, 0.12);
}

.purchase-order-filter {
  flex: 0 0 185px;
  min-width: 185px;
}

.purchase-order-tabs-shell {
  padding: 0 0.75rem 0.3rem;
  border-color: rgba(226, 232, 240, 0.9) !important;
}

.purchase-order-tabs-shell :deep(.nav-tabs-custom) {
  background: var(--po-surface-soft);
  border-radius: 16px;
  margin: 0;
  padding: 0.22rem 0.3rem 0;
}

.purchase-order-tabs-shell :deep(.nav-tabs-custom .nav-link) {
  color: var(--po-muted);
  border-radius: 12px 12px 0 0;
  padding: 0.8rem 0.95rem !important;
}

.purchase-order-tabs-shell :deep(.nav-tabs-custom .nav-link.active) {
  background: var(--po-surface);
  color: var(--po-primary);
}

.purchase-order-content {
  padding: 0.45rem 0.75rem 0.55rem;
}

.purchase-order-table-shell {
  background: var(--po-surface);
  border-radius: 18px;
  border: 1px solid var(--po-border);
  height: calc(100vh - 252px);
  overflow: auto;
}

.purchase-order-table {
  --bs-table-bg: transparent;
  --bs-table-striped-bg: rgba(79, 109, 230, 0.035);
  --bs-table-striped-color: var(--po-text);
  color: var(--po-text);
}

.purchase-order-thead {
  --bs-table-bg: transparent;
}

.purchase-order-thead th {
  background: linear-gradient(180deg, #f8fbff 0%, #edf3ff 100%);
  color: #475569;
  border-bottom: 1px solid rgba(203, 213, 225, 0.95);
  padding: 0.8rem 0.75rem;
  white-space: nowrap;
}

.purchase-order-table td {
  border-color: rgba(226, 232, 240, 0.85);
  padding: 0.85rem 0.75rem;
  vertical-align: middle;
}

.purchase-order-table tbody tr:hover td {
  background: rgba(79, 109, 230, 0.05);
}

.po-date-stack {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
  min-width: 0;
}

.po-date-row {
  display: grid;
  grid-template-columns: minmax(5.75rem, auto) minmax(0, 1fr);
  align-items: baseline;
  gap: 0.45rem;
}

.po-date-label {
  font-size: 0.64rem;
  font-weight: 700;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  color: #64748b;
  white-space: nowrap;
}

.po-date-value {
  color: #0f172a;
  font-weight: 500;
  line-height: 1.3;
  min-width: 0;
}

.purchase-order-status-badge {
  white-space: nowrap;
}

.purchase-order-actions-cell {
  min-width: 152px;
}

.purchase-order-actions {
  flex-wrap: nowrap;
  align-items: center;
}

.purchase-order-action-btn {
  width: 2rem;
  height: 2rem;
  padding: 0;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 0.65rem;
}

.purchase-order-action-btn i {
  font-size: 0.95rem;
}

.purchase-order-empty-state {
  max-width: 420px;
  margin: 0 auto;
}

.purchase-order-empty-state__icon {
  background: linear-gradient(135deg, #f0f5ff 0%, #eef2ff 100%);
  color: var(--po-primary);
}

.purchase-order-empty-state__title {
  color: var(--po-text);
  font-weight: 700;
}

.purchase-order-empty-state__message {
  color: var(--po-muted);
  margin-bottom: 0;
}

.purchase-order-footer {
  padding: 0.55rem 0.75rem 0.7rem;
  border-top: 1px solid rgba(226, 232, 240, 0.9);
}

@media (max-width: 991.98px) {
  .purchase-order-page :deep(.page-title-box) {
    margin-bottom: 0.65rem;
  }

  .purchase-order-toolbar {
    padding-inline: 0.6rem;
  }

  .purchase-order-tabs-shell,
  .purchase-order-content,
  .purchase-order-footer {
    padding-inline: 0.6rem;
  }

  .purchase-order-filter {
    flex-basis: 165px;
    min-width: 165px;
  }

  .purchase-order-table-shell {
    height: calc(100vh - 270px);
  }
}

@media (max-width: 767.98px) {
  .purchase-order-filters {
    gap: 0.5rem;
  }

  .purchase-order-input-addon {
    min-width: 44px;
  }

  .purchase-order-search-input,
  .purchase-order-filter {
    flex: 1 1 100%;
    min-width: 100%;
  }

  .purchase-order-tabs-shell :deep(.nav-tabs-custom .nav-link) {
    padding-inline: 0.75rem !important;
  }

  .purchase-order-table-shell {
    height: calc(100vh - 320px);
  }
}

[data-bs-theme="dark"] .purchase-order-page {
  --po-page-bg: #141b27;
  --po-surface: #1b2230;
  --po-surface-soft: #202938;
  --po-surface-alt: #232d3b;
  --po-border: rgba(148, 163, 184, 0.18);
  --po-text: #e5edf7;
  --po-muted: #94a3b8;
  --po-primary: #93c5fd;
}

[data-bs-theme="dark"] .purchase-order-shell {
  box-shadow: none;
}

[data-bs-theme="dark"] .purchase-order-input-addon,
[data-bs-theme="dark"] .purchase-order-search-input,
[data-bs-theme="dark"] .purchase-order-filter {
  background: var(--po-surface) !important;
  color: var(--po-text) !important;
  border-color: var(--po-border) !important;
}

[data-bs-theme="dark"] .purchase-order-tabs-shell {
  border-color: var(--po-border) !important;
}

[data-bs-theme="dark"] .purchase-order-tabs-shell :deep(.nav-tabs-custom) {
  background: var(--po-surface-soft);
}

[data-bs-theme="dark"] .purchase-order-tabs-shell :deep(.nav-tabs-custom .nav-link) {
  color: var(--po-muted);
}

[data-bs-theme="dark"] .purchase-order-tabs-shell :deep(.nav-tabs-custom .nav-link.active) {
  background: var(--po-surface);
  color: var(--po-primary);
}

[data-bs-theme="dark"] .purchase-order-thead th {
  background: linear-gradient(180deg, #232c3a 0%, #1f2937 100%);
  color: var(--po-text);
  border-bottom-color: var(--po-border);
}

[data-bs-theme="dark"] .purchase-order-table {
  --bs-table-color: var(--po-text);
  --bs-table-striped-bg: rgba(147, 197, 253, 0.05);
  --bs-table-striped-color: var(--po-text);
}

[data-bs-theme="dark"] .purchase-order-table td,
[data-bs-theme="dark"] .purchase-order-footer {
  border-color: var(--po-border);
}

[data-bs-theme="dark"] .purchase-order-table tbody tr:hover td {
  background: rgba(147, 197, 253, 0.08);
}

[data-bs-theme="dark"] .po-date-label {
  color: var(--po-muted);
}

[data-bs-theme="dark"] .po-date-value {
  color: var(--po-text);
}

[data-bs-theme="dark"] .purchase-order-empty-state__icon {
  background: linear-gradient(135deg, rgba(59, 130, 246, 0.18), rgba(30, 41, 59, 0.92));
  color: var(--po-primary);
}
</style>

<style>
[data-bs-theme="dark"] .purchase-order-page {
  --po-page-bg: #141b27;
  --po-surface: #1b2230;
  --po-surface-soft: #202938;
  --po-surface-alt: #232d3b;
  --po-border: rgba(148, 163, 184, 0.18);
  --po-text: #e5edf7;
  --po-muted: #94a3b8;
  --po-primary: #93c5fd;
}

[data-bs-theme="dark"] .purchase-order-shell {
  box-shadow: none;
}

[data-bs-theme="dark"] .purchase-order-input-addon,
[data-bs-theme="dark"] .purchase-order-search-input,
[data-bs-theme="dark"] .purchase-order-filter {
  background: var(--po-surface) !important;
  color: var(--po-text) !important;
  border-color: var(--po-border) !important;
}

[data-bs-theme="dark"] .purchase-order-tabs-shell {
  border-color: var(--po-border) !important;
}

[data-bs-theme="dark"] .purchase-order-tabs-shell .nav-tabs-custom {
  background: var(--po-surface-soft);
}

[data-bs-theme="dark"] .purchase-order-tabs-shell .nav-tabs-custom .nav-link {
  color: var(--po-muted);
}

[data-bs-theme="dark"] .purchase-order-tabs-shell .nav-tabs-custom .nav-link.active {
  background: var(--po-surface);
  color: var(--po-primary);
}

[data-bs-theme="dark"] .purchase-order-thead th {
  background: linear-gradient(180deg, #232c3a 0%, #1f2937 100%);
  color: var(--po-text);
  border-bottom-color: var(--po-border);
}

[data-bs-theme="dark"] .purchase-order-table {
  --bs-table-color: var(--po-text);
  --bs-table-striped-bg: rgba(147, 197, 253, 0.05);
  --bs-table-striped-color: var(--po-text);
}

[data-bs-theme="dark"] .purchase-order-table td,
[data-bs-theme="dark"] .purchase-order-footer {
  border-color: var(--po-border);
}

[data-bs-theme="dark"] .purchase-order-table tbody tr:hover td {
  background: rgba(147, 197, 253, 0.08);
}

[data-bs-theme="dark"] .po-date-label {
  color: var(--po-muted);
}

[data-bs-theme="dark"] .po-date-value {
  color: var(--po-text);
}

[data-bs-theme="dark"] .purchase-order-empty-state__icon {
  background: linear-gradient(135deg, rgba(59, 130, 246, 0.18), rgba(30, 41, 59, 0.92));
  color: var(--po-primary);
}
</style>
