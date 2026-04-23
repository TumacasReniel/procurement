<template>
  <div class="procurement-noa-page">
  <PageHeader class="pt-2" title="Notice of Awards" />

  <b-row class="g-2 mb-3 mt-n2">
    <b-col lg>
      <div class="input-group mb-1 noa-search-group">
        <span class="input-group-text noa-input-addon"> <i class="ri-search-line search-icon"></i></span>
        <input
          type="text"
          v-model="filter.keyword"
          placeholder="Search Notice of Awards"
          class="form-control noa-search-input"
          style="width: 60%"
        />
        <span
          @click="refresh()"
          class="input-group-text noa-input-addon"
          v-b-tooltip.hover
          title="Refresh"
          style="cursor: pointer"
        >
          <i class="bx bx-refresh search-icon"></i>
        </span>
      </div>
    </b-col>
  </b-row>
    
  <b-card no-body class="noa-shell">
    <div class="table-responsive">
      <table class="table mb-0 noa-table" style="min-width: 900px;">
        <thead class="table-light noa-thead">
          <tr class="fs-11">
            <th>#</th>
            <th>NOA No.</th>
            <th>BAC No.</th>
            <th>Timeline</th>
            <th>Status</th>
            <th class="text-center">Actions</th>
          </tr>
        </thead>

        <tbody>
          <tr
            class="custom-hover-row"
            v-for="(list, index) in lists"
            v-bind:key="index"
            @click="selectRow(list.id)"
            :class="{ 'noa-row-active': selectedRow === list.id }"
          >
            <td>{{ index + 1 }}</td>
            <td>{{ list.code }}</td>
            <td>{{ list.bac_resolution?.code }}</td>
            <td>
              <div class="date-stack">
                <div class="date-stack-item">
                  <span class="date-stack-label">Created</span>
                  <span class="date-stack-value">{{ list.created_at }}</span>
                </div>
                <div class="date-stack-item">
                  <span class="date-stack-label">Served</span>
                  <span class="date-stack-value" :class="{ 'text-muted': !list.served_at }">
                    {{ list.served_at || "Not yet" }}
                  </span>
                </div>
                <div class="date-stack-item">
                  <span class="date-stack-label">Conformed</span>
                  <span
                    class="date-stack-value"
                    :class="{ 'text-muted': !list.conformed_at }"
                  >
                    {{ list.conformed_at || "Not yet" }}
                  </span>
                </div>
              </div>
            </td>
            <td>
              <b-badge :class="list.status.bg">{{ list.status?.name }}</b-badge>
            </td>
            <td class="text-center">
              <div class="d-flex gap-1 justify-content-center flex-wrap">
                <button
                  v-if="canEditNOA(list)"
                  @click.stop="editNOA(list)"
                  class="btn btn-success btn-sm"
                  v-b-tooltip.hover
                  title="Edit NOA"
                >
                  <i class="ri-edit-2-fill"></i>
                </button>
                <button
                  v-if="
                    (list.status?.name == 'Pending' ||
                    list.status?.name == 'Served to Supplier') &&
                    ($page.props.roles.includes('Procurement Staff') || $page.props.roles.includes('Procurement Officer'))
                  "
                  @click="updateStatus(list)"
                  class="btn btn-warning btn-sm"
                  v-b-tooltip.hover
                  title="Update Status"
                >
                  <i class="ri-edit-circle-fill"></i>
                </button>
                <button
                  v-if="
                    ['Served to Supplier', 'Conformed', 'Items Delivered', 'Delivered/For Inspection'].includes(list.status?.name) &&
                    ($page.props.roles.includes('Procurement Staff') || $page.props.roles.includes('Procurement Officer'))
                  "
                  @click="revertStatus(list)"
                  class="btn btn-warning btn-sm"
                  v-b-tooltip.hover
                  title="Revert Status"
                >
                  <i class="ri-arrow-go-back-line"></i>
                </button>
                <button
                  v-if="(list.status?.name == 'Served to Supplier') && ($page.props.roles.includes('Procurement Staff') || $page.props.roles.includes('Procurement Officer'))"
                  @click="notConformed(list)"
                  class="btn btn-danger btn-sm"
                  v-b-tooltip.hover
                  title="Not Conformed"
                >
                  <i class="ri-close-circle-fill"></i>
                </button>
                <button
                  v-if="canOpenPO(list)"
                  @click="goPOPage(list)"
                  class="btn btn-success btn-sm"
                  v-b-tooltip.hover
                  title="Purchase Order"
                >
                  <i class="ri-file-2-fill"></i>
                </button>
                <button
                  @click="printNOA(list)"
                  class="btn btn-dark btn-sm"
                  v-b-tooltip.hover
                  title="Print"
                >
                  <i class="ri-printer-fill"></i>
                </button>
              </div>
            </td>
          </tr>
          <tr v-if="lists.length === 0">
            <td colspan="6" class="text-center py-5">
              <div class="empty-state noa-empty-state">
                <div class="empty-state-icon noa-empty-state__icon">
                  <i class="ri-trophy-line"></i>
                </div>
                <h6 class="empty-state-title noa-empty-state__title">No Notice of Awards</h6>
                <p class="empty-state-message noa-empty-state__message">No notices of award have been created for this procurement yet.</p>

              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <Pagination
      class="ms-2 me-2"
      v-if="meta"
      @fetch="fetch"
      :lists="lists.length"
      :links="links"
      :pagination="meta"
    />
  </b-card>
 <NOA :procurement="procurement" @update="fetch()" ref="NOA" />

  <UpdateStatus :procurement="procurement"
  @add="fetch()" ref="updateStatus" />
  <RevertResultModal
    v-model="showRevertResultModal"
    :title="revertResultMessage"
    :info="revertResultInfo"
    :variant="revertResultVariant"
  />
  </div>
</template>
<script>
import _ from "lodash";

import PageHeader from "@/Shared/Components/PageHeader.vue";
import Pagination from "@/Shared/Components/Pagination.vue";
import NOA from "../Modals/NOA.vue";
import UpdateStatus from "../Modals/UpdateStatus.vue";
import RevertResultModal from "@/Shared/Components/RevertResultModal.vue";

export default {
  props: ["bac_resolution", "procurement", "dropdowns"],
  components: { PageHeader, Pagination, NOA, UpdateStatus, RevertResultModal },
  computed: {
    canManageNOA() {
      const roles = this.$page.props.roles || [];

      return (
        roles.includes("Procurement Staff") || roles.includes("Procurement Officer")
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
      },
      selectedRow: null,
      index: null,
      showRevertResultModal: false,
      revertResultMessage: "",
      revertResultInfo: "",
      revertResultVariant: "success",
    };
  },
  watch: {
    "filter.keyword"(newVal) {
      this.checkSearchStr(newVal);
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
      page_url = "/faims/notice-of-awards";
      axios
        .get(page_url, {
          params: {
            keyword: this.filter.keyword,
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

    editNOA(data) {
      this.$refs.NOA.edit(data);
    },
    canEditNOA(data) {
      return data?.status?.name === "Pending" && this.canManageNOA;
    },
    canOpenPO(data) {
      return this.canManageNOA && [
        "Conformed",
        "PO Created",
        "PO Issued",
        "PO Conformed",
        "Items Delivered",
        "PO Items Delivered",
        "Delivered/For Inspection",
        "PO Delivered/For Inspection",
        "Completed",
      ].includes(data?.status?.name);
    },

    updateStatus(data) {
      let type = "NOA";
      this.$refs.updateStatus.show(data, type);
    },

    notConformed(data) {
      let type = "NOA Not Conformed";
      this.$refs.updateStatus.show(data, type);
    },
    revertStatus(data) {
      this.$refs.updateStatus.show(data, "NOA", "revert");
    },

    printNOA(data) {
      window.open(
        "/faims/notice-of-awards/" + data.id + "?option=print&type=notice_of_awards"
      );
    },


    goPOPage(data) {
      this.$emit("showCreatePO", data);
    },

    selectRow(selected_id) {
      this.selectedRow = selected_id;
    },
    refresh() {
      this.filter.keyword = null;
      this.fetch();
    },
  },
};
</script>

<style scoped>
.procurement-noa-page {
  --noa-surface: #ffffff;
  --noa-surface-soft: #f8fafc;
  --noa-head: #f8fafc;
  --noa-border: rgba(148, 163, 184, 0.18);
  --noa-text: #1e293b;
  --noa-muted: #64748b;
  --noa-row-hover: hsl(0, 29%, 97%);
  --noa-row-active: rgba(59, 130, 246, 0.1);
  --noa-empty-icon-bg: linear-gradient(135deg, #dbeafe, #eff6ff);
  --noa-empty-icon-text: #2563eb;
}

.noa-shell {
  background: var(--noa-surface) !important;
  border: 1px solid var(--noa-border) !important;
  box-shadow: none !important;
}

.noa-search-group .noa-input-addon,
.noa-search-group .noa-search-input {
  background: var(--noa-surface) !important;
  color: var(--noa-text) !important;
  border-color: var(--noa-border) !important;
}

.noa-search-input::placeholder {
  color: var(--noa-muted);
}

.noa-table {
  --bs-table-bg: var(--noa-surface);
  --bs-table-color: var(--noa-text);
  --bs-table-border-color: var(--noa-border);
  margin-bottom: 0;
}

.noa-thead th {
  background: var(--noa-head) !important;
  color: var(--noa-text) !important;
  border-bottom-color: var(--noa-border) !important;
}

.custom-hover-row > td {
  background: var(--noa-surface);
  color: var(--noa-text);
  transition: background-color 0.18s ease, color 0.18s ease;
}

.custom-hover-row:hover > td {
  background-color: var(--noa-row-hover);
}

.custom-hover-row.noa-row-active > td {
  background: var(--noa-row-active) !important;
}

.procurement-noa-page .text-muted {
  color: var(--noa-muted) !important;
}

.date-stack {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
  min-width: 220px;
}

.date-stack-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
}

.date-stack-label {
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--noa-muted);
  text-transform: uppercase;
  letter-spacing: 0.03em;
}

.date-stack-value {
  font-weight: 500;
  text-align: right;
  color: var(--noa-text);
}

.noa-empty-state {
  background: var(--noa-surface-soft);
  border: 1px solid var(--noa-border);
  border-radius: 14px;
  padding: 2rem 1rem;
}

.noa-empty-state__icon {
  width: 72px;
  height: 72px;
  margin: 0 auto 1rem;
  border-radius: 999px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--noa-empty-icon-bg);
  color: var(--noa-empty-icon-text);
  font-size: 1.8rem;
}

.noa-empty-state__title {
  color: var(--noa-text);
}

.noa-empty-state__message {
  color: var(--noa-muted);
}
</style>

<style>
[data-bs-theme="dark"] .procurement-noa-page {
  --noa-surface: #1b2230;
  --noa-surface-soft: #202937;
  --noa-head: #232c3a;
  --noa-border: rgba(148, 163, 184, 0.18);
  --noa-text: #e5edf7;
  --noa-muted: #9fb0c7;
  --noa-row-hover: rgba(148, 163, 184, 0.08);
  --noa-row-active: rgba(96, 165, 250, 0.14);
  --noa-empty-icon-bg: linear-gradient(135deg, rgba(59, 130, 246, 0.18), rgba(30, 41, 59, 0.92));
  --noa-empty-icon-text: #93c5fd;
}

[data-bs-theme="dark"] .procurement-noa-page .noa-table td,
[data-bs-theme="dark"] .procurement-noa-page .noa-table th {
  border-color: var(--noa-border);
}
</style>
