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

    <div class="row g-2 mb-3">
      <div class="col-md-4">
        <div class="receiving-list-stat">
          <span class="receiving-list-stat-icon text-primary"><i class="ri-inbox-archive-line"></i></span>
          <div>
            <div class="small text-muted fw-semibold text-uppercase">Receiving Records</div>
            <div class="fw-bold fs-5">{{ meta.total || lists.length || 0 }}</div>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="receiving-list-stat">
          <span class="receiving-list-stat-icon text-success"><i class="ri-stack-line"></i></span>
          <div>
            <div class="small text-muted fw-semibold text-uppercase">Items Received</div>
            <div class="fw-bold fs-5">{{ totalItems }}</div>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="receiving-list-stat">
          <span class="receiving-list-stat-icon text-info"><i class="ri-calculator-line"></i></span>
          <div>
            <div class="small text-muted fw-semibold text-uppercase">Quantity Received</div>
            <div class="fw-bold fs-5">{{ formatQuantity(totalQuantity) }}</div>
          </div>
        </div>
      </div>
    </div>

    <b-card no-body class="border-0 shadow-sm receiving-list-card">
      <div class="table-responsive">
        <table class="table align-middle mb-0">
          <thead class="table-light">
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
          <tbody>
            <tr v-if="loading">
              <td colspan="7" class="text-center text-muted py-5">Loading receiving records...</td>
            </tr>
            <tr v-else-if="!lists.length">
              <td colspan="7" class="text-center text-muted py-5">
                No receiving records found.
              </td>
            </tr>
            <tr v-for="record in sortedLists" v-else :key="record.id">
              <td>
                <div class="fw-bold text-primary">{{ record.code }}</div>
                <div class="small text-muted">{{ record.invoice_no || "No invoice" }}</div>
              </td>
              <td>
                <div class="fw-semibold">{{ record.po_code || "-" }}</div>
                <div class="small text-muted">{{ record.po_status?.name || "-" }}</div>
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
      loading: false,
      show_items_modal: false,
      selected_record: null,
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
        const firstDate = this.sortTimestamp(first.received_at || first.created_at || first.updated_at);
        const secondDate = this.sortTimestamp(second.received_at || second.created_at || second.updated_at);

        return this.filter.sort === "oldest" ? firstDate - secondDate : secondDate - firstDate;
      });
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
          },
        })
        .then((response) => {
          const payload = response?.data || {};

          this.lists = payload.data || [];
          this.meta = payload.meta || payload || {};
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
      this.fetch();
    },
    sortTimestamp(value) {
      if (!value) {
        return 0;
      }

      const parsedDate = new Date(value);

      return Number.isNaN(parsedDate.getTime()) ? 0 : parsedDate.getTime();
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
  border-radius: 8px;
  overflow: hidden;
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
</style>
