<template>
  <PageHeader class="m-3 mt-4" title="Notice of Awards" />

  <b-row class="g-2 mb-3 mt-n2">
    <b-col lg>
      <div class="input-group mb-1">
        <span class="input-group-text"> <i class="ri-search-line search-icon"></i></span>
        <input
          type="text"
          v-model="filter.keyword"
          placeholder="Search Notice of Awards"
          class="form-control"
          style="width: 60%"
        />
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
    
  <b-card no-body>
    <div class="table-responsive">
      <table class="table mb-0" style="min-width: 900px;">
        <thead class="table-light">
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
            :class="{ 'bg-info-subtle': selectedRow === list.id }"
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
                    ['Served to Supplier', 'Conformed', 'Delivered/For Inspection'].includes(list.status?.name) &&
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
              <div class="empty-state">
                <div class="empty-state-icon">
                  <i class="ri-trophy-line"></i>
                </div>
                <h6 class="empty-state-title">No Notice of Awards</h6>
                <p class="empty-state-message">No notices of award have been created for this procurement yet.</p>

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

  <UpdateStatus :procurement="procurement" @add="fetch()" ref="updateStatus" />
  <RevertResultModal
    v-model="showRevertResultModal"
    :title="revertResultMessage"
    :info="revertResultInfo"
    :variant="revertResultVariant"
  />
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
.custom-hover-row:hover {
  background-color: hsl(0, 29%, 97%);
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
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.03em;
}

.date-stack-value {
  font-weight: 500;
  text-align: right;
}
</style>
