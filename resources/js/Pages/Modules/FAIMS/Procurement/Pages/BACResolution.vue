<template>
  <PageHeader class="m-3 mt-4" title="BAC Resolutions" />
  <b-row class="g-2 mb-3 mt-n2">
    <b-col lg>
      <div class="input-group mb-1">
        <span class="input-group-text"> <i class="ri-search-line search-icon"></i></span>
        <input
          type="text"
          v-model="filter.keyword"
          placeholder="Search BAC Resolutions"
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

  <b-card no-body>
    <table class="table mb-0">
      <thead class="table-light">
        <tr class="fs-11">
          <th>#</th>
          <th>BAC Resolution No.</th>
          <th>Type</th>
          <th>Date Created</th>
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
          <td>{{ list.type }}</td>
          <td>{{ list.created_at }}</td>
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
          <td colspan="6" class="text-center py-5">
            <div class="empty-state">
              <div class="empty-state-icon">
                <i class="ri-file-line"></i>
              </div>
              <h6 class="empty-state-title">No BAC Resolutions</h6>
              <p class="empty-state-message">No BAC resolutions have been created for this procurement yet.</p>
            </div>
          </td>
        </tr>
      </tbody>
    </table>
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
  },
};
</script>

<style scoped>
.custom-hover-row:hover {
  background-color: hsl(0, 29%, 97%);
}
</style>
