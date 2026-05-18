<template>
  <Head v-if="!embedded" title="Receiving List" />
  <PageHeader v-if="!embedded" title="Receiving List" pageTitle="Procurement" />

  <div class="receiving-list-page" :class="{ 'px-3 pb-3': !embedded }">
    <div class="receiving-list-toolbar">
      <div>
        <div class="text-uppercase fw-semibold text-primary small mb-1">Supply Receiving</div>
        <h4 class="h5 fw-bold mb-1">Received Purchase Order Deliveries</h4>
        <p class="text-muted small mb-0">
          Review all RCV records created from received PO deliveries.
        </p>
      </div>

      <div class="receiving-list-search">
        <div class="input-group">
          <span class="input-group-text bg-white">
            <i class="ri-search-line"></i>
          </span>
          <input
            v-model="filter.keyword"
            type="text"
            class="form-control"
            placeholder="Search RCV, PO, PR, supplier, or invoice"
          />
          <select v-model="filter.sort" class="form-select receiving-list-sort">
            <option value="latest">Latest Date</option>
            <option value="oldest">Oldest Date</option>
          </select>
          <span
            @click="refresh"
            class="input-group-text"
            v-b-tooltip.hover
            title="Refresh"
            style="cursor: pointer"
          >
            <i class="bx bx-refresh search-icon"></i>
          </span>
        </div>
      </div>
    </div>

    <div
      class="receiving-list-alert"
      :class="hasTodayDeliveries ? 'receiving-list-alert-active' : 'receiving-list-alert-quiet'"
    >
      <div class="receiving-list-alert-icon">
        <i :class="hasTodayDeliveries ? 'ri-notification-3-line' : 'ri-time-line'"></i>
      </div>
      <div class="flex-grow-1">
        <div class="fw-bold">{{ deliveryAlertTitle }}</div>
        <div class="small">{{ deliveryAlertMessage }}</div>
      </div>
      <div class="d-flex flex-wrap gap-2 justify-content-end">
        <b-button
          type="button"
          size="sm"
          :variant="show_today_only ? 'primary' : 'soft-primary'"
          class="receiving-list-alert-btn"
          :disabled="!hasTodayDeliveries"
          @click="toggleTodayOnly"
        >
          <i class="ri-calendar-check-line me-1"></i>
          {{ show_today_only ? "Show All" : "Today" }}
        </b-button>
        <b-button
          type="button"
          size="sm"
          variant="soft-secondary"
          class="receiving-list-alert-btn"
          @click="refresh"
        >
          <i class="ri-refresh-line me-1"></i>
          Refresh
        </b-button>
      </div>
    </div>

    <div class="row g-2 mb-3">
      <div class="col-md-3">
        <div class="receiving-list-stat">
          <span class="receiving-list-stat-icon text-primary"><i class="ri-inbox-archive-line"></i></span>
          <div>
            <div class="small text-muted fw-semibold text-uppercase">Receiving Records</div>
            <div class="fw-bold fs-5">{{ meta.total || lists.length || 0 }}</div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="receiving-list-stat">
          <span class="receiving-list-stat-icon text-warning"><i class="ri-notification-badge-line"></i></span>
          <div>
            <div class="small text-muted fw-semibold text-uppercase">Delivered Today</div>
            <div class="fw-bold fs-5">{{ summary.today_count || 0 }}</div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="receiving-list-stat">
          <span class="receiving-list-stat-icon text-success"><i class="ri-stack-line"></i></span>
          <div>
            <div class="small text-muted fw-semibold text-uppercase">Items Received</div>
            <div class="fw-bold fs-5">{{ totalItems }}</div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="receiving-list-stat">
          <span class="receiving-list-stat-icon text-info"><i class="ri-calculator-line"></i></span>
          <div>
            <div class="small text-muted fw-semibold text-uppercase">Quantity Received</div>
            <div class="fw-bold fs-5">{{ formatQuantity(totalQuantity) }}</div>
          </div>
        </div>
      </div>
    </div>

    <b-card no-body class="receiving-list-card">
      <div class="receiving-list-table-shell">
      <div class="table-responsive receiving-list-table-wrap">
        <table class="table align-middle table-hover mb-0 receiving-list-table">
          <thead class="table-light thead-fixed">
            <tr>
              <th style="width: 12%">RCV No.</th>
              <th style="width: 12%">PO No.</th>
              <th>Procurement</th>
              <th style="width: 17%">Supplier</th>
              <th style="width: 15%">Received</th>
              <th style="width: 12%" class="text-center">Items</th>
              <th style="width: 12%" class="text-center">Action</th>
            </tr>
          </thead>
          <tbody class="table-group-divider">
            <tr v-if="loading">
              <td colspan="7" class="text-center text-muted py-5">Loading receiving records...</td>
            </tr>
            <tr v-else-if="show_today_only && !lists.length">
              <td colspan="7" class="text-center text-muted py-5">
                No deliveries received today.
              </td>
            </tr>
            <tr v-else-if="!lists.length">
              <td colspan="7" class="text-center text-muted py-5">
                No receiving records found.
              </td>
            </tr>
            <tr v-for="record in displayLists" v-else :key="record.id" :class="{ 'receiving-list-today-row': isTodayDelivery(record) }">
              <td>
                <div class="fw-bold text-primary">{{ record.code }}</div>
                <div class="small text-muted">{{ record.invoice_no || "No invoice" }}</div>
                <span v-if="isTodayDelivery(record)" class="badge rounded-pill bg-warning-subtle text-warning border border-warning mt-1">
                  Today
                </span>
              </td>
              <td>
                <div class="fw-semibold">{{ record.po_code || "-" }}</div>
                <span class="badge rounded-pill receiving-list-status-badge" :class="poStatusBadgeClass(record.po_status)">
                  {{ record.po_status?.name || "-" }}
                </span>
              </td>
              <td>
                <div class="fw-semibold">{{ record.procurement_title || "-" }}</div>
                <div class="small text-muted">{{ record.procurement_code || "No PR code" }}</div>
              </td>
              <td>{{ record.supplier_name || "-" }}</td>
              <td>
                <div class="fw-semibold">{{ record.received_at || "-" }}</div>
                <div class="small text-muted">{{ record.received_by || "No receiver" }}</div>
              </td>
              <td class="text-center">
                <div class="fw-semibold">{{ record.items_count || 0 }} item(s)</div>
                <div class="small text-muted">{{ formatQuantity(record.total_quantity) }} total</div>
              </td>
              <td class="text-center">
                <div class="d-flex flex-wrap justify-content-center gap-2">
                  <b-button
                    type="button"
                    size="sm"
                    variant="soft-secondary"
                    class="receiving-list-action-btn"
                    @click="openItems(record)"
                  >
                    <i class="ri-eye-line me-1"></i>
                    View
                  </b-button>
                  <b-button
                    v-if="record.can_edit_received_items"
                    type="button"
                    size="sm"
                    variant="soft-primary"
                    class="receiving-list-action-btn"
                    @click="openEdit(record)"
                  >
                    <i class="ri-pencil-line me-1"></i>
                    Edit
                  </b-button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      </div>

      <div class="card-footer bg-white">
        <Pagination
          v-if="meta"
          @fetch="fetch"
          :lists="lists.length"
          :links="links"
          :pagination="meta"
        />
      </div>
    </b-card>
  </div>

  <IARItemSelection @updated="handleUpdated" ref="iarSelection" />

  <ReceivingRecordItems
    :model-value="show_items_modal"
    :record="selected_record"
    @update:modelValue="handleItemsVisibility"
    @edit-record="openEditFromModal"
  />
</template>

<script>
import _ from "lodash";
import { Head } from "@inertiajs/vue3";
import PageHeader from "@/Shared/Components/PageHeader.vue";
import Pagination from "@/Shared/Components/Pagination.vue";
import IARItemSelection from "../Modals/IARItemSelection.vue";
import ReceivingRecordItems from "../Modals/ReceivingRecordItems.vue";

export default {
  components: { Head, PageHeader, Pagination, IARItemSelection, ReceivingRecordItems },
  props: {
    embedded: { type: Boolean, default: false },
  },
  data() {
    return {
      lists: [],
      meta: {},
      links: {},
      summary: {},
      loading: false,
      show_items_modal: false,
      selected_record: null,
      show_today_only: false,
      filter: {
        keyword: null,
        count: 10,
        sort: "latest",
      },
    };
  },
  computed: {
    totalItems() {
      return this.lists.reduce((sum, record) => sum + Number(record.items_count || 0), 0);
    },
    totalQuantity() {
      return this.lists.reduce((sum, record) => sum + Number(record.total_quantity || 0), 0);
    },
    sortedLists() {
      return [...this.lists].sort((first, second) => {
        const firstDate = this.sortTimestamp(first.received_at_raw || first.received_at || first.created_at || first.updated_at);
        const secondDate = this.sortTimestamp(second.received_at_raw || second.received_at || second.created_at || second.updated_at);

        return this.filter.sort === "oldest" ? firstDate - secondDate : secondDate - firstDate;
      });
    },
    displayLists() {
      return this.sortedLists;
    },
    hasTodayDeliveries() {
      return Number(this.summary.today_count || 0) > 0;
    },
    deliveryAlertTitle() {
      if (this.hasTodayDeliveries) {
        const count = Number(this.summary.today_count || 0);

        return `${count} delivery ${count === 1 ? "was" : "were"} received today`;
      }

      return "No deliveries received today";
    },
    deliveryAlertMessage() {
      if (this.hasTodayDeliveries) {
        const latestCode = this.summary.latest_code ? `${this.summary.latest_code} ` : "";
        const latestDate = this.summary.latest_received_at_display || "just now";

        return `Latest receiving record: ${latestCode}at ${latestDate}.`;
      }

      if (this.summary.latest_received_at_display) {
        return `Latest receiving record was ${this.summary.latest_code || "recorded"} on ${this.summary.latest_received_at_display}.`;
      }

      return "Refresh this page to check newly recorded PO deliveries.";
    },
  },
  watch: {
    "filter.keyword": _.debounce(function () {
      this.fetch();
    }, 300),
    "filter.sort"() {
      this.fetch();
    },
  },
  created() {
    this.fetch();
  },
  methods: {
    fetch(pageUrl) {
      this.loading = true;

      return axios
        .get(pageUrl || "/faims/receiving-list", {
          params: {
            option: "lists",
            keyword: this.filter.keyword,
            count: this.filter.count,
            sort: this.filter.sort,
            delivery_date: this.show_today_only ? this.summary.today_date : null,
          },
        })
        .then((response) => {
          const payload = response?.data || {};

          this.lists = payload.data || [];
          this.meta = payload.meta || payload || {};
          this.summary = payload.summary || {};
          this.links = payload.links || {
            first: payload.first_page_url,
            prev: payload.prev_page_url,
            next: payload.next_page_url,
            last: payload.last_page_url,
          };
        })
        .catch((error) => console.error(error))
        .finally(() => {
          this.loading = false;
        });
    },
    refresh() {
      this.filter.keyword = null;
      this.show_today_only = false;
      this.fetch();
    },
    toggleTodayOnly() {
      this.show_today_only = !this.show_today_only;
      this.fetch();
    },
    isTodayDelivery(record) {
      const today = this.summary.today_date || new Date().toISOString().slice(0, 10);
      const recordDate = record?.received_date_raw || String(record?.received_at_raw || record?.received_at || "").slice(0, 10);

      return recordDate === today;
    },
    sortTimestamp(value) {
      if (!value) {
        return 0;
      }

      const parsedDate = new Date(value);

      return Number.isNaN(parsedDate.getTime()) ? 0 : parsedDate.getTime();
    },
    poStatusBadgeClass(status) {
      const statusName = String(status?.name || status || "").trim().toLowerCase();

      if (!statusName) {
        return "text-secondary border-secondary bg-secondary-subtle";
      }

      if (statusName.includes("completed")) {
        return "text-success border-success bg-success-subtle";
      }

      if (statusName.includes("delivered") || statusName.includes("inspection")) {
        return "text-info border-info bg-info-subtle";
      }

      if (statusName.includes("conformed") || statusName.includes("issued") || statusName.includes("served")) {
        return "text-primary border-primary bg-primary-subtle";
      }

      if (statusName.includes("created") || statusName.includes("generated") || statusName.includes("pending")) {
        return "text-warning border-warning bg-warning-subtle";
      }

      if (statusName.includes("not") || statusName.includes("cancel") || statusName.includes("failed")) {
        return "text-danger border-danger bg-danger-subtle";
      }

      return "text-secondary border-secondary bg-secondary-subtle";
    },
    openItems(record) {
      this.selected_record = record;
      this.show_items_modal = true;
    },
    handleItemsVisibility(value) {
      this.show_items_modal = value;

      if (!value) {
        this.selected_record = null;
      }
    },
    hideItems() {
      this.show_items_modal = false;
      this.selected_record = null;
    },
    openEditFromModal() {
      const record = this.selected_record;
      this.hideItems();

      this.$nextTick(() => {
        this.openEdit(record);
      });
    },
    openEdit(record) {
      if (!record?.can_edit_received_items || !record?.po) {
        return;
      }

      this.$refs.iarSelection.show(record.po, {
        title: "Edit Received PO Items",
        submitLabel: "Save Received Items",
        infoMessage:
          "Update the received item quantities for this Purchase Order. This is allowed only before an IAR is generated.",
        printAfterSave: false,
        submitUsingAxios: true,
        keepOpenAfterSave: false,
        referenceLabel: "Receiving No.",
        referenceValue: record.code || null,
        referenceFallback: record.code || "To be recorded",
        selectionUrl: "/faims/receiving-deliveries",
        saveUrl: `/faims/receiving-deliveries/${record.po_id}`,
        selectionOption: "selection",
        saveOption: "receive_delivery",
        selectionParams: {
          edit_received_items: 1,
        },
        savePayload: {
          edit_received_items: 1,
        },
        errorContext: "receiving",
        loadingMessage: "Loading received PO items...",
        emptyItemsMessage: "No items are available for receiving on this Purchase Order.",
        selectAllLabel: "Select all received items",
        onSuccess: (responseData) => {
          this.fetch().then(() => {
            const deliveryId = responseData?.delivery_id || null;
            const updatedRecord = this.lists.find((item) => (
              deliveryId
                ? Number(item.id) === Number(deliveryId)
                : Number(item.po_id) === Number(record.po_id)
            ));

            if (updatedRecord) {
              this.openItems(updatedRecord);
            }
          });
        },
      });
    },
    handleUpdated() {
      this.fetch();
    },
    formatQuantity(value) {
      const numericValue = Number(value ?? 0);

      if (!Number.isFinite(numericValue)) {
        return "-";
      }

      return Number.isInteger(numericValue)
        ? numericValue.toLocaleString("en-PH")
        : numericValue.toLocaleString("en-PH", {
            minimumFractionDigits: 0,
            maximumFractionDigits: 4,
          });
    },
  },
};
</script>

<style scoped>
.receiving-list-page {
  --receiving-list-border: rgba(148, 163, 184, 0.22);
  --receiving-list-surface: #ffffff;
  --receiving-list-soft: #f8fafc;
}

.receiving-list-toolbar {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 1rem;
  margin-bottom: 0.75rem;
}

.receiving-list-search {
  width: min(420px, 100%);
}

.receiving-list-sort {
  max-width: 130px;
}

.receiving-list-alert {
  display: flex;
  align-items: center;
  gap: 0.85rem;
  padding: 0.9rem 1rem;
  margin-bottom: 0.85rem;
  border: 1px solid var(--receiving-list-border);
  border-radius: 8px;
  background: var(--receiving-list-surface);
  box-shadow: 0 10px 22px rgba(15, 23, 42, 0.05);
}

.receiving-list-alert-active {
  border-color: rgba(var(--bs-warning-rgb), 0.4);
  background: linear-gradient(90deg, rgba(var(--bs-warning-rgb), 0.12), var(--receiving-list-surface) 62%);
}

.receiving-list-alert-quiet {
  color: var(--bs-secondary-color);
}

.receiving-list-alert-icon {
  width: 42px;
  height: 42px;
  flex: 0 0 42px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 999px;
  background: rgba(var(--bs-primary-rgb), 0.08);
  color: var(--bs-primary);
  border: 1px solid rgba(var(--bs-primary-rgb), 0.16);
  font-size: 1.1rem;
}

.receiving-list-alert-active .receiving-list-alert-icon {
  background: rgba(var(--bs-warning-rgb), 0.16);
  color: var(--bs-warning);
  border-color: rgba(var(--bs-warning-rgb), 0.32);
}

.receiving-list-alert-btn {
  white-space: nowrap;
}

.receiving-list-stat {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.85rem 1rem;
  border: 1px solid var(--receiving-list-border);
  border-radius: 8px;
  background: var(--receiving-list-surface);
  box-shadow: 0 10px 22px rgba(15, 23, 42, 0.05);
}

.receiving-list-stat-icon {
  width: 38px;
  height: 38px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 999px;
  background: var(--receiving-list-soft);
  border: 1px solid var(--receiving-list-border);
  font-size: 1rem;
}

.receiving-list-card {
  border: 0;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 4px 18px rgba(15, 23, 42, 0.06);
}

.receiving-list-table-shell {
  padding: 0.35rem 0.65rem 0;
  background: var(--receiving-list-surface);
}

.receiving-list-table-wrap {
  max-height: calc(100vh - 430px);
  min-height: 260px;
  overflow: auto;
  border: 1px solid var(--receiving-list-border);
  border-radius: 8px;
}

.receiving-list-table {
  --bs-table-bg: var(--receiving-list-surface);
  --bs-table-color: var(--bs-body-color);
  --bs-table-border-color: var(--receiving-list-border);
}

.receiving-list-table thead th {
  position: sticky;
  top: 0;
  z-index: 2;
  padding: 0.62rem 0.75rem;
  color: var(--bs-secondary-color);
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0;
  background: var(--bs-light-bg-subtle, #f8f9fa);
  border-bottom: 1px solid var(--receiving-list-border);
  white-space: nowrap;
}

.receiving-list-table tbody td {
  padding: 0.68rem 0.75rem;
  border-color: var(--receiving-list-border);
  vertical-align: middle;
}

.receiving-list-table tbody tr:hover td {
  background: rgba(var(--bs-primary-rgb), 0.04);
}

.receiving-list-table tbody tr.receiving-list-today-row td {
  background: rgba(var(--bs-warning-rgb), 0.05);
}

.receiving-list-table tbody tr.receiving-list-today-row:hover td {
  background: rgba(var(--bs-warning-rgb), 0.1);
}

.receiving-list-status-badge {
  margin-top: 0.18rem;
  border: 1px solid currentColor;
  background: transparent !important;
  font-size: 0.68rem;
  font-weight: 700;
  line-height: 1.1;
  padding: 0.28em 0.5em;
}

.receiving-list-action-btn {
  --vz-btn-padding-x: 0.45rem;
  --vz-btn-padding-y: 0.18rem;
  --vz-btn-font-size: 0.7rem;
  border-color: transparent !important;
  box-shadow: none;
  line-height: 1.2;
}

.receiving-list-action-btn i {
  font-size: 0.78rem;
}

@media (max-width: 991.98px) {
  .receiving-list-toolbar {
    flex-direction: column;
  }

  .receiving-list-search {
    width: 100%;
  }

  .receiving-list-alert {
    align-items: flex-start;
    flex-direction: column;
  }
}

[data-bs-theme="dark"] .receiving-list-page {
  --receiving-list-border: rgba(148, 163, 184, 0.18);
  --receiving-list-surface: #1b2230;
  --receiving-list-soft: #232c3a;
}

[data-bs-theme="dark"] .receiving-list-stat,
[data-bs-theme="dark"] .receiving-list-card,
[data-bs-theme="dark"] .card-footer {
  background: var(--receiving-list-surface) !important;
  border-color: var(--receiving-list-border) !important;
}

[data-bs-theme="dark"] .receiving-list-table thead th {
  background: #202937;
}
</style>
