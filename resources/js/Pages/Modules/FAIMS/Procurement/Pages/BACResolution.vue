<template>
  <div class="procurement-bac-resolution-page">
  <PageHeader class="pt-2" title="BAC Resolutions" />
  <b-row class="g-2 mb-3 mt-n2">
    <b-col lg>
      <div class="input-group mb-1 bac-resolution-search-group">
        <span class="input-group-text bac-resolution-input-addon"> <i class="ri-search-line search-icon"></i></span>
        <input
          type="text"
          v-model="filter.keyword"
          placeholder="Search BAC Resolutions"
          class="form-control bac-resolution-search-input"
          style="width: 60%"
        />
        <span
          @click="refresh()"
          class="input-group-text bac-resolution-input-addon"
          v-b-tooltip.hover
          title="Refresh"
          style="cursor: pointer"
        >
          <i class="bx bx-refresh search-icon"></i>
        </span>
        <b-button
          v-if="
            (procurement.status.name === 'Rebid' &&
              procurement.sub_status?.name == null) ||
            procurement.status.name == 'Re-award'
          "
          type="button"
          variant="primary"
          @click="openBACReso()"
        >
          <i class="ri-add-circle-fill align-bottom me-1"></i> New
        </b-button>
      </div>
    </b-col>
  </b-row>

  <b-card no-body class="bac-resolution-shell">
    <div class="table-responsive">
      <table class="table mb-0 bac-resolution-table" style="min-width: 900px;">
        <thead class="table-light bac-resolution-thead">
          <tr class="fs-11">
            <th>#</th>
            <th>BAC Resolution No.</th>
            <th>Type</th>
            <th>Date Created</th>
            <th>Date Approved</th>
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
            :class="{ 'bac-resolution-row-active': selectedRow === list.id }"
          >
            <td>{{ index + 1 }}</td>
            <td>{{ list.code }}</td>
            <td>{{ list.type }}</td>
            <td>{{ list.created_at }}</td>
            <td>
              <span v-if="list.approved_at">{{ list.approved_at }}</span>
              <span v-else class="text-muted">Not yet</span>
            </td>
            <td>
              <b-badge :class="list.status.bg">{{ list.status?.name }}</b-badge>
            </td>
            <td class="text-center">
              <div class="d-flex gap-1 justify-content-center flex-wrap">
                <button
                  @click="printBACReso(list)"
                  class="btn btn-dark btn-sm"
                  v-b-tooltip.hover
                  title="Print"
                >
                  <i class="ri-printer-fill"></i>
                </button>
                <button
                  v-if="list.status?.name == 'Pending' && ($page.props.roles.includes('Procurement Officer') || $page.props.roles.includes('Procurement Staff') )"
                  @click="editBACReso(list)"
                  class="btn btn-success btn-sm"
                  v-b-tooltip.hover
                  title="Edit"
                >
                  <i class="ri-edit-2-fill"></i>
                </button>
                <button
                  v-if="list.status.name == 'Pending' && ($page.props.roles.includes('Procurement Officer') || $page.props.roles.includes('Procurement Staff') )"
                  @click="updateStatus(list)"
                  class="btn btn-warning btn-sm"
                  v-b-tooltip.hover
                  title="Update Status"
                >
                  <i class="ri-edit-circle-fill"></i>
     
                </button>
              </div>
            </td>
          </tr>
          <tr v-if="lists.length === 0">
            <td colspan="7" class="text-center py-5">
              <div class="empty-state bac-resolution-empty-state">
                <div class="empty-state-icon bac-resolution-empty-state__icon">
                  <i class="ri-file-line"></i>
                </div>
                <h6 class="empty-state-title bac-resolution-empty-state__title">No BAC Resolutions</h6>
                <p class="empty-state-message bac-resolution-empty-state__message">No BAC resolutions have been created for this procurement yet.</p>
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

  <BACResolution
    :procurement="procurement"
    @add="fetch()"
    @update="fetch()"
    ref="BACReso"
  />
  <UpdateStatus
    :procurement="procurement"
    @add="fetch()"
    @update="fetch()"
    ref="updateStatus"
  />
  </div>
</template>
<script>
import _ from "lodash";

import PageHeader from "@/Shared/Components/PageHeader.vue";
import Pagination from "@/Shared/Components/Pagination.vue";
import BACResolution from "../Modals/BACResolution.vue";
import UpdateStatus from "../Modals/UpdateStatus.vue";
import { router } from "@inertiajs/vue3";
export default {
  props: ["procurement"],
  components: { PageHeader, Pagination, BACResolution, UpdateStatus },
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
      option: "",
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
      page_url = "/faims/bac-resolutions";
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

    openBACReso() {
      let type = "";
      if (this.procurement.status.name === "For Approval of BAC Resolution") {
        type = "Award";
      } else if (
        this.procurement.status.name === "Re-award" ||
        this.procurement.status.name === "Ongoing Re-award"
      ) {
        type = "Re-award";
      } else {
        type = "Rebid";
      }
      this.$refs.BACReso.show(type);
    },

    editBACReso(data) {
      let type = "";
      if (this.procurement.status_id === 45) {
        type = "Award";
      } else {
        type = "Rebid";
      }
      this.$refs.BACReso.edit(data);
    },

    updateStatus(data) {
      let type = "BACResolution";
      this.$refs.updateStatus.show(data, type);
    },

    printBACReso(data) {
      window.open(
        "/faims/bac-resolutions/" + data.id + "?option=print&type=bac_resolution"
      );
    },

    selectRow(index) {
      this.selectedRow = this.selectedRow == index ? null : index;
    },
    refresh() {
      this.filter.keyword = null;
      this.fetch();
    },
  },
};
</script>

<style scoped>
.procurement-bac-resolution-page {
  --bac-resolution-surface: #ffffff;
  --bac-resolution-surface-soft: #f8fafc;
  --bac-resolution-head: #f8fafc;
  --bac-resolution-border: rgba(148, 163, 184, 0.18);
  --bac-resolution-text: #1e293b;
  --bac-resolution-muted: #64748b;
  --bac-resolution-row-hover: hsl(0, 29%, 97%);
  --bac-resolution-row-active: rgba(59, 130, 246, 0.1);
  --bac-resolution-empty-icon-bg: linear-gradient(135deg, #dbeafe, #eff6ff);
  --bac-resolution-empty-icon-text: #2563eb;
}

.bac-resolution-shell {
  background: var(--bac-resolution-surface) !important;
  border: 1px solid var(--bac-resolution-border) !important;
  box-shadow: none !important;
}

.bac-resolution-search-group .bac-resolution-input-addon,
.bac-resolution-search-group .bac-resolution-search-input {
  background: var(--bac-resolution-surface) !important;
  color: var(--bac-resolution-text) !important;
  border-color: var(--bac-resolution-border) !important;
}

.bac-resolution-search-input::placeholder {
  color: var(--bac-resolution-muted);
}

.bac-resolution-table {
  --bs-table-bg: var(--bac-resolution-surface);
  --bs-table-color: var(--bac-resolution-text);
  --bs-table-border-color: var(--bac-resolution-border);
  margin-bottom: 0;
}

.bac-resolution-thead th {
  background: var(--bac-resolution-head) !important;
  color: var(--bac-resolution-text) !important;
  border-bottom-color: var(--bac-resolution-border) !important;
}

.custom-hover-row > td {
  background: var(--bac-resolution-surface);
  color: var(--bac-resolution-text);
  transition: background-color 0.18s ease, color 0.18s ease;
}

.custom-hover-row:hover > td {
  background-color: var(--bac-resolution-row-hover);
}

.custom-hover-row.bac-resolution-row-active > td {
  background: var(--bac-resolution-row-active) !important;
}

.procurement-bac-resolution-page .text-muted {
  color: var(--bac-resolution-muted) !important;
}

.bac-resolution-empty-state {
  background: var(--bac-resolution-surface-soft);
  border: 1px solid var(--bac-resolution-border);
  border-radius: 14px;
  padding: 2rem 1rem;
}

.bac-resolution-empty-state__icon {
  width: 72px;
  height: 72px;
  margin: 0 auto 1rem;
  border-radius: 999px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--bac-resolution-empty-icon-bg);
  color: var(--bac-resolution-empty-icon-text);
  font-size: 1.8rem;
}

.bac-resolution-empty-state__title {
  color: var(--bac-resolution-text);
}

.bac-resolution-empty-state__message {
  color: var(--bac-resolution-muted);
}
</style>

<style>
[data-bs-theme="dark"] .procurement-bac-resolution-page {
  --bac-resolution-surface: #1b2230;
  --bac-resolution-surface-soft: #202937;
  --bac-resolution-head: #232c3a;
  --bac-resolution-border: rgba(148, 163, 184, 0.18);
  --bac-resolution-text: #e5edf7;
  --bac-resolution-muted: #9fb0c7;
  --bac-resolution-row-hover: rgba(148, 163, 184, 0.08);
  --bac-resolution-row-active: rgba(96, 165, 250, 0.14);
  --bac-resolution-empty-icon-bg: linear-gradient(135deg, rgba(59, 130, 246, 0.18), rgba(30, 41, 59, 0.92));
  --bac-resolution-empty-icon-text: #93c5fd;
}

[data-bs-theme="dark"] .procurement-bac-resolution-page .bac-resolution-table td,
[data-bs-theme="dark"] .procurement-bac-resolution-page .bac-resolution-table th {
  border-color: var(--bac-resolution-border);
}
</style>
