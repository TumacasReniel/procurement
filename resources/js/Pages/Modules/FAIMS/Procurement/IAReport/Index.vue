<template>
  <Head :title="page_title" />
  <PageHeader :title="page_title" pageTitle="Procurement" />

  <div class="receiving-page px-3 pb-3">
    <div v-if="is_receiving_mode" class="receiving-tabs mb-3">
      <button
        type="button"
        class="receiving-tab-btn"
        :class="{ active: active_receiving_tab === 'deliveries' }"
        @click="active_receiving_tab = 'deliveries'"
      >
        <i class="ri-truck-line"></i>
        Receiving Deliveries
      </button>
      <button
        type="button"
        class="receiving-tab-btn"
        :class="{ active: active_receiving_tab === 'records' }"
        @click="active_receiving_tab = 'records'"
      >
        <i class="ri-inbox-archive-line"></i>
        Receiving List
      </button>
    </div>

    <div v-if="is_iar_mode" class="receiving-tabs mb-3">
      <button
        type="button"
        class="receiving-tab-btn"
        :class="{ active: active_iar_tab === 'purchase_orders' }"
        @click="active_iar_tab = 'purchase_orders'"
      >
        <i class="ri-file-search-line"></i>
        Generate IAR
      </button>
      <button
        type="button"
        class="receiving-tab-btn"
        :class="{ active: active_iar_tab === 'reports' }"
        @click="active_iar_tab = 'reports'"
      >
        <i class="ri-file-list-3-line"></i>
        IAR List
      </button>
    </div>

    <template v-if="showPurchaseOrderList">
      <div class="receiving-toolbar">
        <div>
          <div class="text-uppercase fw-semibold text-primary small mb-1">
            {{ eyebrow_label }}
          </div>
          <h4 class="h5 fw-bold mb-1">{{ page_heading }}</h4>
          <p class="text-muted small mb-0">
            {{ page_description }}
          </p>
        </div>

        <div class="receiving-search">
          <div class="input-group">
            <span class="input-group-text bg-white">
              <i class="ri-search-line"></i>
            </span>
            <input
              v-model="filter.keyword"
              type="text"
              class="form-control"
              placeholder="Search PO, PR, supplier, or project"
            />
            <select v-model="filter.sort" class="form-select receiving-sort">
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
        <div class="col-md-6 col-xl-3">
          <div class="receiving-stat">
            <span class="receiving-stat-icon text-primary"><i class="ri-file-list-3-line"></i></span>
            <div>
              <div class="small text-muted fw-semibold text-uppercase">Purchase Orders</div>
              <div class="fw-bold fs-5">{{ meta.total || lists.length || 0 }}</div>
            </div>
          </div>
        </div>
        <div class="col-md-6 col-xl-3">
          <div class="receiving-stat">
            <span class="receiving-stat-icon text-warning"><i class="ri-truck-line"></i></span>
            <div>
              <div class="small text-muted fw-semibold text-uppercase">{{ secondary_stat_label }}</div>
              <div class="fw-bold fs-5">{{ totalRemainingItems }}</div>
            </div>
          </div>
        </div>
        <div class="col-md-6 col-xl-3">
          <div class="receiving-stat">
            <span class="receiving-stat-icon text-success"><i class="ri-checkbox-circle-line"></i></span>
            <div>
              <div class="small text-muted fw-semibold text-uppercase">Delivered Items</div>
              <div class="fw-bold fs-5">{{ totalDeliveredItems }}</div>
            </div>
          </div>
        </div>
        <div class="col-md-6 col-xl-3">
          <div class="receiving-stat">
            <span class="receiving-stat-icon text-info"><i class="ri-file-paper-2-line"></i></span>
            <div>
              <div class="small text-muted fw-semibold text-uppercase">{{ iar_stat_label }}</div>
              <div class="fw-bold fs-5">{{ totalIars }}</div>
            </div>
          </div>
        </div>
      </div>

      <b-card no-body class="border-0 shadow-sm receiving-card">
      <div class="table-responsive">
        <table class="table align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th style="width: 12%">PO No.</th>
              <th>Procurement</th>
              <th style="width: 18%">Supplier</th>
              <th style="width: 14%">{{ date_column_label }}</th>
              <th style="width: 18%">{{ progress_column_label }}</th>
              <th style="width: 12%" class="text-center">Action</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="6" class="text-center text-muted py-5">
                {{ loading_message }}
              </td>
            </tr>

            <tr v-else-if="!lists.length">
              <td colspan="6" class="text-center text-muted py-5">
                {{ empty_message }}
              </td>
            </tr>

            <tr v-for="po in sortedLists" v-else :key="po.id">
              <td>
                <div class="fw-bold">{{ po.code }}</div>
                <div class="small text-muted">{{ po.status?.name || "-" }}</div>
              </td>
              <td>
                <div class="fw-semibold">{{ po.procurement_title || "-" }}</div>
                <div class="small text-muted">{{ po.procurement_code || "No PR code" }}</div>
                <div class="receiving-items-preview mt-2">
                  <span
                    v-for="item in previewItems(po)"
                    :key="item.id"
                    class="badge rounded-pill text-bg-danger"
                  >
                    Item {{ item.item_no }}: {{ formatQuantity(item.remaining_quantity) }} left
                  </span>
                  <span
                    v-for="item in deliveredPreviewItems(po)"
                    :key="`delivered-${item.id}`"
                    class="badge rounded-pill text-bg-success"
                  >
                    Item {{ item.item_no }}: {{ formatQuantity(item.delivered_quantity) }} delivered
                  </span>
                </div>
              </td>
              <td>{{ po.supplier_name || "-" }}</td>
              <td>
                <div class="fw-semibold">{{ po.date_of_delivery || "Not set" }}</div>
                <div class="small text-muted">{{ po.place_of_delivery || "No delivery place" }}</div>
              </td>
              <td>
                <div class="d-flex flex-wrap gap-1 mb-2">
                  <span v-if="is_receiving_mode" class="badge rounded-pill text-bg-warning">
                    Remaining: {{ po.remaining_items_count || 0 }}
                  </span>
                  <span class="badge rounded-pill text-bg-success">
                    Delivered: {{ po.delivered_items_count || 0 }}
                  </span>
                  <span v-if="is_receiving_mode && po.partial_items_count" class="badge rounded-pill text-bg-info">
                    Partial: {{ po.partial_items_count }}
                  </span>
                  <span v-if="is_iar_mode" class="badge rounded-pill text-bg-info">
                    IARs: {{ iarCount(po) }}
                  </span>
                </div>
                <div class="progress receiving-progress">
                  <div
                    class="progress-bar bg-success"
                    role="progressbar"
                    :style="{ width: `${deliveryPercent(po)}%` }"
                    :aria-valuenow="deliveryPercent(po)"
                    aria-valuemin="0"
                    aria-valuemax="100"
                  ></div>
                </div>
              </td>
              <td class="text-center">
                <div v-if="is_receiving_mode" class="d-flex flex-wrap justify-content-center gap-2">
                  <b-button
                    type="button"
                    size="sm"
                    variant="soft-secondary"
                    class="receiving-action-btn"
                    @click="open_received_items(po)"
                  >
                    <i class="ri-eye-line me-1"></i>
                    View
                  </b-button>
                </div>
                <div v-else class="d-flex flex-wrap justify-content-center gap-2">
                  <b-button
                    type="button"
                    size="sm"
                    variant="soft-secondary"
                    class="receiving-action-btn"
                    @click="open_iar_reports(po)"
                  >
                    <i class="ri-eye-line me-1"></i>
                    View
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
    </template>

    <ReceivingList v-if="is_receiving_mode && active_receiving_tab === 'records'" embedded />

    <template v-if="is_iar_mode && active_iar_tab === 'reports'">
      <div class="receiving-toolbar">
        <div>
          <div class="text-uppercase fw-semibold text-primary small mb-1">
            Inspection and Acceptance
          </div>
          <h4 class="h5 fw-bold mb-1">Generated IAR List</h4>
          <p class="text-muted small mb-0">
            Review generated Inspection and Acceptance Reports, print copies, and mark eligible reports as inspected.
          </p>
        </div>

        <div class="receiving-search">
          <div class="input-group">
            <span class="input-group-text bg-white">
              <i class="ri-search-line"></i>
            </span>
            <input
              v-model="filter.keyword"
              type="text"
              class="form-control"
              placeholder="Search IAR, PO, PR, supplier, or project"
            />
            <select v-model="filter.sort" class="form-select receiving-sort">
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
          <div class="receiving-stat">
            <span class="receiving-stat-icon text-info"><i class="ri-file-paper-2-line"></i></span>
            <div>
              <div class="small text-muted fw-semibold text-uppercase">IAR Reports</div>
              <div class="fw-bold fs-5">{{ iarListRecords.length }}</div>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="receiving-stat">
            <span class="receiving-stat-icon text-warning"><i class="ri-time-line"></i></span>
            <div>
              <div class="small text-muted fw-semibold text-uppercase">Pending Inspection</div>
              <div class="fw-bold fs-5">{{ pendingIarListRecords.length }}</div>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="receiving-stat">
            <span class="receiving-stat-icon text-success"><i class="ri-shield-check-line"></i></span>
            <div>
              <div class="small text-muted fw-semibold text-uppercase">Inspected</div>
              <div class="fw-bold fs-5">{{ inspectedIarListRecords.length }}</div>
            </div>
          </div>
        </div>
      </div>

      <b-card no-body class="border-0 shadow-sm receiving-card">
        <div class="table-responsive">
          <table class="table align-middle mb-0 ">
            <thead class="table-light">
              <tr>
                <th style="width: 14%">IAR No.</th>
                <th style="width: 14%">PO No.</th>
                <th>Procurement</th>
                <th style="width: 17%">Supplier</th>
                <th style="width: 15%">Generated</th>
                <th style="width: 12%" class="text-center">Items</th>
                <th style="width: 16%">Inspected</th>
                <th style="width: 14%" class="text-center">Action</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="loading">
                <td colspan="8" class="text-center text-muted py-5">
                  Loading inspection and acceptance reports...
                </td>
              </tr>
              <tr v-else-if="!iarListRecords.length">
                <td colspan="8" class="text-center text-muted py-5">
                  No generated IAR reports found.
                </td>
              </tr>
              <tr v-for="entry in sortedIarListRecords" v-else :key="entry.report.id">
                <td class="fw-semibold text-primary">{{ entry.report.code || "IAR" }}</td>
                <td>
                  <div class="fw-semibold">{{ entry.po.code || "-" }}</div>
                  <div class="small text-muted">{{ entry.po.status?.name || "-" }}</div>
                </td>
                <td>
                  <div class="fw-semibold">{{ entry.po.procurement_title || "-" }}</div>
                  <div class="small text-muted">{{ entry.po.procurement_code || "No PR code" }}</div>
                </td>
                <td>{{ entry.po.supplier_name || "-" }}</td>
                <td>{{ formatDateTime(entry.report.created_at) }}</td>
                <td class="text-center">
                  <div class="fw-semibold">{{ entry.report.selected_items_count || 0 }} item(s)</div>
                  <div class="small text-muted">{{ formatQuantity(entry.report.delivered_quantity_total) }} total</div>
                </td>
                <td>
                  <span
                    class="badge rounded-pill"
                    :class="`text-bg-${iarStatusVariant(entry.report)}`"
                  >
                    {{ iarStatusLabel(entry.report) }}
                  </span>
                  <template v-if="inspectedDetails(entry.report)">
                    <div class="fw-semibold mt-1">
                      {{ inspectedDetails(entry.report).name }}
                    </div>
                    <div class="small text-muted">
                      {{ inspectedDetails(entry.report).date }}
                    </div>
                  </template>
                </td>
                <td class="text-center">
                  <div class="d-flex flex-wrap justify-content-center gap-2">
                    <b-button
                      v-if="canMarkIarInspected(entry.report)"
                      type="button"
                      size="sm"
                      variant="soft-primary"
                      class="receiving-action-btn receiving-icon-btn"
                      v-b-tooltip.hover
                      title="Mark as inspected"
                      aria-label="Mark as inspected"
                      :disabled="processing_iar_id === entry.report.id"
                      @click="confirmMarkIarInspected(entry.report, entry.po)"
                    >
                      <i class="ri-shield-check-line"></i>
                    </b-button>
                    <b-button
                      type="button"
                      size="sm"
                      variant="soft-secondary"
                      class="receiving-action-btn receiving-icon-btn"
                      v-b-tooltip.hover
                      title="Print IAR"
                      aria-label="Print IAR"
                      @click="printIAR(entry.po, entry.report)"
                    >
                      <i class="ri-printer-line"></i>
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
    </template>
  </div>

  <IARItemSelection @updated="handleUpdated" ref="iarSelection" />

  <ReceivedPOItems
    :model-value="show_received_items_modal"
    :po="selected_po"
    :can-edit="canEditReceivedItems(selected_po)"
    @update:modelValue="handle_received_items_visibility"
    @add-receive="receive_remaining_items_from_view"
    @edit-record="edit_received_items_from_view"
  />

  <b-modal
    v-model="show_iar_reports_modal"
    header-class="p-3 bg-light"
    title="Inspection and Acceptance Reports"
    size="lg"
    class="v-modal-custom"
    modal-class="zoomIn"
    centered
    @hidden="handle_iar_reports_visibility(false)"
  >
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
      <div>
        <div class="text-muted fs-12">Purchase Order</div>
        <div class="fw-semibold">{{ selected_iar_po?.code || "-" }}</div>
      </div>
      <div>
        <div class="text-muted fs-12">Supplier</div>
        <div class="fw-semibold">{{ selected_iar_po?.supplier_name || "-" }}</div>
      </div>
      <div class="text-md-end">
        <div class="text-muted fs-12">Available for IAR</div>
        <div class="fw-semibold text-success">{{ receivedItemsPendingIar(selected_iar_po).length }}</div>
      </div>
    </div>

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
      <div>
        <div class="text-muted fs-12">Generated Reports</div>
        <div class="fw-semibold">{{ iarCount(selected_iar_po) }} report(s)</div>
      </div>
      <b-button
        v-if="canGenerateIar(selected_iar_po)"
        type="button"
        size="sm"
        variant="primary"
        @click="generate_iar_from_view"
      >
        <i class="ri-add-line me-1"></i>
        Generate IAR
      </b-button>
    </div>

    <div class="table-responsive border rounded">
      <table class="table align-middle mb-0">
        <thead class="table-light">
          <tr class="fs-11">
            <th style="width: 18%">IAR No.</th>
            <th style="width: 20%">Generated</th>
            <th style="width: 16%" class="text-center">Items</th>
            <th style="width: 18%" class="text-center">Status</th>
            <th style="width: 14%" class="text-center">Action</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="report in iarReports(selected_iar_po)" :key="report.id">
            <td class="fw-semibold text-primary">{{ report.code || "IAR" }}</td>
            <td>{{ formatDateTime(report.created_at) }}</td>
            <td class="text-center">
              <div class="fw-semibold">{{ report.selected_items_count || 0 }} item(s)</div>
              <div class="text-muted fs-12">{{ formatQuantity(report.delivered_quantity_total) }} total</div>
            </td>
            <td class="text-center">
              <span
                class="badge rounded-pill"
                :class="`text-bg-${iarStatusVariant(report)}`"
              >
                {{ iarStatusLabel(report) }}
              </span>
            </td>
            <td class="text-center">
              <div class="d-flex flex-wrap justify-content-center gap-2">
                <b-button
                  v-if="canMarkIarInspected(report)"
                  type="button"
                  size="sm"
                  variant="soft-primary"
                  class="receiving-action-btn receiving-icon-btn"
                  v-b-tooltip.hover
                  title="Mark as inspected"
                  aria-label="Mark as inspected"
                  :disabled="processing_iar_id === report.id"
                  @click="confirmMarkIarInspected(report)"
                >
                  <i class="ri-shield-check-line"></i>
                </b-button>
                <b-button
                  type="button"
                  size="sm"
                  variant="soft-secondary"
                  class="receiving-action-btn receiving-icon-btn"
                  v-b-tooltip.hover
                  title="Print IAR"
                  aria-label="Print IAR"
                  @click="printIAR(selected_iar_po, report)"
                >
                  <i class="ri-printer-line"></i>
                </b-button>
              </div>
            </td>
          </tr>
          <tr v-if="!iarReports(selected_iar_po).length">
            <td colspan="5" class="text-center text-muted py-4">
              No IAR has been generated for this Purchase Order yet.
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <template v-slot:footer>
      <b-button variant="light" @click="hide_iar_reports">Close</b-button>
    </template>
  </b-modal>

  <b-modal
    v-model="show_inspect_iar_modal"
    style="--vz-modal-width: 500px"
    header-class="p-3 bg-light"
    title="Mark IAR as Inspected"
    class="v-modal-custom"
    modal-class="zoomIn"
    centered
    no-close-on-backdrop
    :hide-header-close="processing_iar_id === pending_inspect_iar?.id"
  >
    <div class="text-center py-3">
      <div class="text-primary mb-3">
        <i class="ri-shield-check-line fs-1"></i>
      </div>
      <p class="mb-0 lh-lg">
        Are you sure you have inspected
        <strong>{{ pending_inspect_iar?.code || "this IAR" }}</strong>
        and want to mark it as
        <strong>Inspected/Completed</strong>?
      </p>
    </div>

    <template v-slot:footer>
      <b-button
        variant="light"
        :disabled="processing_iar_id === pending_inspect_iar?.id"
        @click="hideInspectIarModal"
      >
        Cancel
      </b-button>
      <b-button
        variant="primary"
        :disabled="processing_iar_id === pending_inspect_iar?.id"
        @click="markIarInspected"
      >
        <span v-if="processing_iar_id === pending_inspect_iar?.id">Updating...</span>
        <span v-else>Yes, Mark Inspected</span>
      </b-button>
    </template>
  </b-modal>
</template>

<script>
import _ from "lodash";
import { Head } from "@inertiajs/vue3";
import PageHeader from "@/Shared/Components/PageHeader.vue";
import Pagination from "@/Shared/Components/Pagination.vue";
import IARItemSelection from "../Modals/IARItemSelection.vue";
import ReceivedPOItems from "../Modals/ReceivedPOItems.vue";
import ReceivingList from "../Receiving/List.vue";

export default {
  components: { Head, PageHeader, Pagination, IARItemSelection, ReceivedPOItems, ReceivingList },
  props: {
    mode: {
      type: String,
      default: "receiving",
    },
  },
  data() {
    return {
      lists: [],
      meta: {},
      links: {},
      loading: false,
      show_received_items_modal: false,
      show_iar_reports_modal: false,
      active_receiving_tab: "deliveries",
      active_iar_tab: "purchase_orders",
      processing_iar_id: null,
      show_inspect_iar_modal: false,
      pending_inspect_iar: null,
      pending_inspect_po: null,
      selected_po: null,
      selected_iar_po: null,
      filter: {
        keyword: null,
        count: 10,
        sort: "latest",
      },
    };
  },
  computed: {
    is_receiving_mode() {
      return this.mode === "receiving";
    },
    is_iar_mode() {
      return this.mode !== "receiving";
    },
    showPurchaseOrderList() {
      return (this.is_receiving_mode && this.active_receiving_tab === "deliveries")
        || (this.is_iar_mode && this.active_iar_tab === "purchase_orders");
    },
    page_title() {
      return this.mode === "receiving" ? "Receiving Deliveries" : "Inspection and Acceptance Reports";
    },
    eyebrow_label() {
      return this.is_receiving_mode ? "Supply Receiving" : "Inspection and Acceptance";
    },
    page_heading() {
      return this.is_receiving_mode
        ? "Receive Delivered Purchase Order Items"
        : "Generate Inspection and Acceptance Reports";
    },
    page_description() {
      return this.is_receiving_mode
        ? "Review conformed purchase orders and record received delivery quantities."
        : "Review purchase orders with received items and generate IARs from already delivered quantities.";
    },
    list_url() {
      return this.is_receiving_mode ? "/faims/receiving-deliveries" : "/faims/ia-reports";
    },
    secondary_stat_label() {
      return this.is_receiving_mode ? "Need Delivery" : "Pending Items";
    },
    iar_stat_label() {
      return this.is_receiving_mode ? "Total Received PO" : "Total Received PO";
    },
    date_column_label() {
      return this.is_receiving_mode ? "Target Delivery" : "Delivery Details";
    },
    progress_column_label() {
      return this.is_receiving_mode ? "Delivery Progress" : "IAR Progress";
    },
    loading_message() {
      return this.is_receiving_mode
        ? "Loading receiving deliveries..."
        : "Loading inspection and acceptance reports...";
    },
    empty_message() {
      return this.is_receiving_mode
        ? "No purchase orders need delivery receiving right now."
        : "No purchase orders with delivered items are ready for IAR generation right now.";
    },
    totalRemainingItems() {
      return this.lists.reduce((sum, po) => sum + Number(po.remaining_items_count || 0), 0);
    },
    totalDeliveredItems() {
      return this.lists.reduce((sum, po) => sum + Number(po.delivered_items_count || 0), 0);
    },
    totalIars() {
      return this.lists.reduce((sum, po) => sum + this.iarCount(po), 0);
    },
    sortedLists() {
      return [...this.lists].sort((first, second) => {
        const firstDate = this.purchaseOrderSortTimestamp(first);
        const secondDate = this.purchaseOrderSortTimestamp(second);

        return this.filter.sort === "oldest" ? firstDate - secondDate : secondDate - firstDate;
      });
    },
    iarListRecords() {
      return this.lists.flatMap((po) => {
        return this.iarReports(po).map((report) => ({
          po,
          report,
        }));
      });
    },
    sortedIarListRecords() {
      return [...this.iarListRecords].sort((first, second) => {
        const firstDate = this.sortTimestamp(first.report?.created_at || first.report?.updated_at);
        const secondDate = this.sortTimestamp(second.report?.created_at || second.report?.updated_at);

        return this.filter.sort === "oldest" ? firstDate - secondDate : secondDate - firstDate;
      });
    },
    pendingIarListRecords() {
      return this.iarListRecords.filter((entry) => this.iarStatusLabel(entry.report) !== "Inspected/Completed");
    },
    inspectedIarListRecords() {
      return this.iarListRecords.filter((entry) => this.iarStatusLabel(entry.report) === "Inspected/Completed");
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
        .get(pageUrl || this.list_url, {
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
    sortTimestamp(value, fallback = 0) {
      if (!value) {
        return fallback;
      }

      const parsedDate = new Date(value);

      return Number.isNaN(parsedDate.getTime()) ? fallback : parsedDate.getTime();
    },
    purchaseOrderSortTimestamp(po) {
      return this.sortTimestamp(
        po?.created_at || po?.updated_at || po?.date_of_delivery,
        Number(po?.id || 0)
      );
    },
    openReceiving(po, edit_received_items = false, receiving_code = null) {
      const receivingCode = receiving_code
        || (edit_received_items && Array.isArray(po?.received_deliveries) ? po.received_deliveries[0]?.code : null)
        || null;

      this.$refs.iarSelection.show(po, {
        title: edit_received_items ? "Edit Received PO Items" : "Receive Delivered Items",
        submitLabel: edit_received_items ? "Save Received Items" : "Receive Delivery",
        infoMessage: edit_received_items
          ? "Update the received item quantities for this Purchase Order. This is allowed only before an IAR is generated."
          : "Select the delivered items and enter the quantity received for this delivery batch. This records receiving only; IAR generation remains under Inspection and Acceptance.",
        printAfterSave: false,
        submitUsingAxios: true,
        keepOpenAfterSave: false,
        referenceLabel: "Receiving No.",
        referenceValue: receivingCode,
        referenceFallback: "To be recorded",
        selectionUrl: "/faims/receiving-deliveries",
        saveUrl: `/faims/receiving-deliveries/${po.id}`,
        selectionOption: "selection",
        saveOption: "receive_delivery",
        selectionParams: {
          edit_received_items: edit_received_items ? 1 : 0,
        },
        savePayload: {
          edit_received_items: edit_received_items ? 1 : 0,
        },
        errorContext: "receiving",
        loadingMessage: edit_received_items
          ? "Loading received PO items..."
          : "Loading items available for receiving...",
        emptyItemsMessage: "No items are available for receiving on this Purchase Order.",
        selectAllLabel: edit_received_items
          ? "Select all received items"
          : "Select all items that need receiving",
        onSuccess: () => {
          this.fetch().then(() => {
            const updatedPo = this.lists.find((item) => Number(item.id) === Number(po.id));

            if (updatedPo) {
              this.selected_po = updatedPo;
              this.show_received_items_modal = true;
            }
          });
        },
      });
    },
    open_received_items(po) {
      this.selected_po = po;
      this.show_received_items_modal = true;
    },
    handle_received_items_visibility(value) {
      this.show_received_items_modal = value;

      if (!value) {
        this.selected_po = null;
      }
    },
    edit_received_items_from_view(record = null) {
      const po = this.selected_po;

      if (!this.canEditReceivedItems(po)) {
        return;
      }

      this.hide_received_items();

      this.$nextTick(() => {
        this.openReceiving(po, true, record?.code || null);
      });
    },
    receive_remaining_items_from_view() {
      const po = this.selected_po;

      if (!po?.can_receive_delivery) {
        return;
      }

      this.hide_received_items();

      this.$nextTick(() => {
        this.openReceiving(po, false);
      });
    },
    hide_received_items() {
      this.show_received_items_modal = false;
      this.selected_po = null;
    },
    open_iar_reports(po) {
      this.selected_iar_po = po;
      this.show_iar_reports_modal = true;
    },
    handle_iar_reports_visibility(value) {
      this.show_iar_reports_modal = value;

      if (!value) {
        this.selected_iar_po = null;
      }
    },
    hide_iar_reports() {
      this.show_iar_reports_modal = false;
      this.selected_iar_po = null;
    },
    generate_iar_from_view() {
      const po = this.selected_iar_po;

      if (!this.canGenerateIar(po)) {
        return;
      }

      this.hide_iar_reports();

      this.$nextTick(() => {
        this.openIAR(po);
      });
    },
    openIAR(po) {
      this.$refs.iarSelection.show(po, {
        title: "Generate New IAR",
        submitLabel: "Generate IAR",
        infoMessage:
          "Select from already received delivered items and enter the quantity to include in this Inspection and Acceptance Report.",
        printAfterSave: false,
        selectionUrl: "/faims/purchase-orders",
        saveUrl: `/faims/purchase-orders/${po.id}`,
        selectionOption: "iar_selection",
        saveOption: "update_iar_selection",
        errorContext: "IAR",
        loadingMessage: "Loading delivered items available for IAR...",
        emptyItemsMessage: "No received delivered items are available for a new IAR on this Purchase Order.",
        selectAllLabel: "Select all delivered items available for IAR",
        onSuccess: () => {
          this.fetch().then(() => {
            const updatedPo = this.lists.find((item) => Number(item.id) === Number(po.id));

            if (updatedPo) {
              this.open_iar_reports(updatedPo);
            }
          });
        },
      });
    },
    printIAR(po, iar) {
      const params = new URLSearchParams({
        option: "print",
        type: "iar",
      });

      if (iar?.id) {
        params.set("iar_id", iar.id);
      }

      window.open(`/faims/purchase-orders/${po.id}?${params.toString()}`);
    },
    canMarkIarInspected(report) {
      const statusName = String(report?.status?.name || "").trim();

      if (!report?.id || statusName === "Completed") {
        return false;
      }

      if (typeof report?.can_update_status === "boolean") {
        return report.can_update_status;
      }

      return ["Generated", "Pending"].includes(statusName);
    },
    confirmMarkIarInspected(report, sourcePo = null) {
      const po = sourcePo || this.selected_iar_po;

      if (!po?.id || !this.canMarkIarInspected(report)) {
        return;
      }

      this.pending_inspect_iar = report;
      this.pending_inspect_po = po;
      this.show_inspect_iar_modal = true;
    },
    hideInspectIarModal() {
      if (this.processing_iar_id === this.pending_inspect_iar?.id) {
        return;
      }

      this.show_inspect_iar_modal = false;
      this.pending_inspect_iar = null;
      this.pending_inspect_po = null;
    },
    markIarInspected() {
      const report = this.pending_inspect_iar;
      const po = this.pending_inspect_po || this.selected_iar_po;

      if (!po?.id || !this.canMarkIarInspected(report)) {
        return;
      }

      this.processing_iar_id = report.id;

      axios
        .put(
          `/faims/purchase-orders/${po.id}`,
          {
            iar_id: report.id,
            option: "update_iar_status",
          },
          {
            headers: {
              Accept: "application/json",
              "X-Requested-With": "XMLHttpRequest",
            },
          }
        )
        .then((response) => {
          const status = response?.data?.status;

          if (status !== true && status !== "success") {
            window.alert(response?.data?.info || "Unable to update the IAR report.");
            return;
          }

          this.fetch().then(() => {
            const updatedPo = this.lists.find((item) => Number(item.id) === Number(po.id));

            if (updatedPo && this.show_iar_reports_modal) {
              this.selected_iar_po = updatedPo;
            }

            this.show_inspect_iar_modal = false;
            this.pending_inspect_iar = null;
            this.pending_inspect_po = null;
          });
        })
        .catch((error) => {
          console.error(error);
          window.alert("Unable to update the IAR report.");
        })
        .finally(() => {
          this.processing_iar_id = null;
        });
    },
    handleUpdated() {
      this.fetch();
    },
    iarReports(po) {
      return Array.isArray(po?.iars)
        ? po.iars
        : (po?.latest_iar ? [po.latest_iar] : []);
    },
    iarCount(po) {
      return this.iarReports(po).length;
    },
    has_generated_iar(po) {
      return this.iarCount(po) > 0 || Boolean(po?.latest_iar);
    },
    canEditReceivedItems(po) {
      if (!po || this.has_generated_iar(po)) {
        return false;
      }

      if (typeof po.can_edit_received_items !== "undefined") {
        return Boolean(po.can_edit_received_items);
      }

      return (po.delivery_monitoring_items || []).some((item) => Number(item.delivered_quantity || 0) > 0);
    },
    hasReceivingNumber(po) {
      return Array.isArray(po?.received_deliveries) && po.received_deliveries.length > 0;
    },
    canShowReceiveAction(po) {
      return !this.hasReceivingNumber(po) && (po?.can_receive_delivery || this.canEditReceivedItems(po));
    },
    latestIar(po) {
      if (!Array.isArray(po?.iars) || !po.iars.length) {
        return po.latest_iar || null;
      }

      return [...po.iars].sort((first, second) => {
        return new Date(second.created_at || 0) - new Date(first.created_at || 0);
      })[0];
    },
    canGenerateIar(po) {
      return Boolean(po)
        && this.receivedItemsPendingIar(po).length > 0
        && ["Conformed", "Items Delivered"].includes(this.normalizedPurchaseOrderStatus(po));
    },
    iarReportedQuantityByItem(po) {
      return this.iarReports(po).reduce((items, report) => {
        const selectedItems = Array.isArray(report?.selected_item_ids)
          ? report.selected_item_ids
          : [];

        selectedItems.forEach((item) => {
          const itemId = Number(item?.item_id ?? item?.id ?? item);

          if (!itemId) {
            return;
          }

          items[itemId] = (items[itemId] || 0) + Number(item?.delivered_quantity || 0);
        });

        return items;
      }, {});
    },
    receivedItemsPendingIar(po) {
      const reportedQuantities = this.iarReportedQuantityByItem(po);

      return (po?.delivery_monitoring_items || []).filter((item) => {
        const itemId = Number(item?.id);
        const receivedQuantity = Number(item?.delivered_quantity || 0);
        const reportedQuantity = Number(reportedQuantities[itemId] || 0);

        return receivedQuantity > reportedQuantity;
      });
    },
    normalizedPurchaseOrderStatus(po) {
      const statusName = String(po?.status?.name || "").trim();

      if (
        [
          "Items Delivered",
          "PO Items Delivered",
          "Partially Delivered/For Inspection",
          "PO Delivered/For Inspection",
          "PO Partially Delivered/For Inspection",
          "Items Partially Delivered",
          "PO Items Partially Delivered",
        ].includes(statusName)
      ) {
        return "Items Delivered";
      }

      if (["PO Conformed", "Partially Conformed", "PO Partially Conformed"].includes(statusName)) {
        return "Conformed";
      }

      return statusName;
    },
    iarStatusLabel(report) {
      const statusName = String(report?.status?.name || "").trim();

      if (statusName === "Completed") {
        return "Inspected/Completed";
      }

      if (["Generated", "Pending"].includes(statusName)) {
        return "Generated";
      }

      return statusName || "Generated";
    },
    iarStatusVariant(report) {
      return this.iarStatusLabel(report) === "Inspected/Completed" ? "success" : "secondary";
    },
    inspectedByName(report) {
      if (this.iarStatusLabel(report) !== "Inspected/Completed") {
        return null;
      }

      return report?.inspected_by_name
        || report?.inspected_by?.profile?.fullname
        || report?.inspected_by?.profile?.full_name
        || report?.inspected_by?.name
        || report?.inspected_by?.username
        || null;
    },
    inspectedDetails(report) {
      if (this.iarStatusLabel(report) !== "Inspected/Completed") {
        return null;
      }

      return {
        name: this.inspectedByName(report) || "-",
        date: this.formatDateTime(report?.inspected_at || report?.inspected_date || report?.updated_at),
      };
    },
    formatDateTime(value) {
      if (!value) {
        return "-";
      }

      const parsedDate = new Date(value);

      if (Number.isNaN(parsedDate.getTime())) {
        return value;
      }

      return new Intl.DateTimeFormat("en-PH", {
        year: "numeric",
        month: "short",
        day: "numeric",
        hour: "numeric",
        minute: "2-digit",
      }).format(parsedDate);
    },
    previewItems(po) {
      return (po.delivery_monitoring_items || [])
        .filter((item) => Number(item.remaining_quantity || 0) > 0)
        .slice(0, 3);
    },
    deliveredPreviewItems(po) {
      const remainingIds = new Set(this.previewItems(po).map((item) => Number(item.id)));

      return (po.delivery_monitoring_items || [])
        .filter((item) => Number(item.delivered_quantity || 0) > 0 && !remainingIds.has(Number(item.id)))
        .slice(0, 3);
    },
    deliveryPercent(po) {
      const total = Number(po.total_items_count || 0);
      const delivered = Number(po.delivered_items_count || 0);

      if (!total) {
        return 0;
      }

      return Math.min(Math.round((delivered / total) * 100), 100);
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
.receiving-page {
  --receiving-border: rgba(148, 163, 184, 0.22);
  --receiving-surface: #ffffff;
  --receiving-soft: #f8fafc;
}

.receiving-toolbar {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 1rem;
  margin-bottom: 0.75rem;
}

.receiving-search {
  width: min(420px, 100%);
}

.receiving-sort {
  max-width: 130px;
}

.receiving-tabs {
  display: inline-flex;
  flex-wrap: wrap;
  gap: 0.35rem;
  padding: 0.25rem;
  border: 1px solid var(--receiving-border);
  border-radius: 8px;
  background: var(--receiving-surface);
}

.receiving-tab-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  min-height: 34px;
  padding: 0.35rem 0.75rem;
  border: 0;
  border-radius: 6px;
  background: transparent;
  color: #64748b;
  font-size: 0.78rem;
  font-weight: 700;
  line-height: 1.2;
}

.receiving-tab-btn.active {
  background: #eef2ff;
  color: #405189;
}

.receiving-stat {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.85rem 1rem;
  border: 1px solid var(--receiving-border);
  border-radius: 8px;
  background: var(--receiving-surface);
  box-shadow: 0 10px 22px rgba(15, 23, 42, 0.05);
}

.receiving-stat-icon {
  width: 38px;
  height: 38px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 999px;
  background: var(--receiving-soft);
  border: 1px solid var(--receiving-border);
  font-size: 1rem;
}

.receiving-card {
  border-radius: 8px;
  overflow: hidden;
}

.receiving-items-preview {
  display: flex;
  flex-wrap: wrap;
  gap: 0.3rem;
}

.receiving-progress {
  height: 0.45rem;
  background: #e5e7eb;
}

.receiving-action-btn {
  --vz-btn-padding-x: 0.45rem;
  --vz-btn-padding-y: 0.18rem;
  --vz-btn-font-size: 0.7rem;
  border-color: transparent !important;
  box-shadow: none;
  line-height: 1.2;
}

.receiving-action-btn i {
  font-size: 0.78rem;
}

.receiving-icon-btn {
  width: 1.85rem;
  height: 1.85rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0 !important;
}

.receiving-icon-btn i {
  font-size: 0.9rem;
  line-height: 1;
}

@media (max-width: 991.98px) {
  .receiving-toolbar {
    flex-direction: column;
  }

  .receiving-search {
    width: 100%;
  }
}

[data-bs-theme="dark"] .receiving-page {
  --receiving-border: rgba(148, 163, 184, 0.18);
  --receiving-surface: #1b2230;
  --receiving-soft: #232c3a;
}

[data-bs-theme="dark"] .receiving-stat,
[data-bs-theme="dark"] .receiving-card,
[data-bs-theme="dark"] .card-footer {
  background: var(--receiving-surface) !important;
  border-color: var(--receiving-border) !important;
}

[data-bs-theme="dark"] .receiving-tab-btn.active {
  background: #232c3a;
  color: #a5b4fc;
}
</style>
