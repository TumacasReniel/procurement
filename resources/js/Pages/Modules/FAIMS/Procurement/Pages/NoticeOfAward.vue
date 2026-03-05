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
        <b-button
          v-if="procurement.status_id == 44"
          type="button"
          variant="primary"
          @click="openNOA()"
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
          <th>NOA No.</th>
          <th>BAC No.</th>
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
          <td>{{ list.bac_resolution?.code }}</td>
          <td>{{ list.created_at }}</td>
          <td>
            <b-badge :class="list.status.bg">{{ list.status?.name }}</b-badge>
          </td>
          <td class="text-center">
            <div class="d-flex gap-1 justify-content-center flex-wrap">
              <button
                v-if="
                  (list.status.name == 'Pending' ||
                  list.status.name == 'Served to Supplier') &&
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
                v-if="(list.status.name == 'Served to Supplier') && ($page.props.roles.includes('Procurement Staff') || $page.props.roles.includes('Procurement Officer'))"
                @click="notConformed(list)"
                class="btn btn-danger btn-sm"
                v-b-tooltip.hover
                title="Not Conformed"
              >
                <i class="ri-close-circle-fill"></i>
              </button>
              <button
                v-if="
                  (list.status.name == 'Conformed' || list.status.name == 'Not Co nformed'||
                  list.status.name == 'PO Conformed' ||
                  list.status.name == 'PO Issued' ||
                  list.status.name == 'PO Pending' ||
                  list.status.name == 'PO Conformed' ||
                  list.status.name == 'Delivered/For Inspection' || list.status.name == 'PO Delivered/For Inspection' ||
                  list.status.name == 'Completed') &&
                  ($page.props.roles.includes('Procurement Staff') || $page.props.roles.includes('Procurement Officer'))
                "
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
    <Pagination
      class="ms-2 me-2"
      v-if="meta"
      @fetch="fetch"
      :lists="lists.length"
      :links="links"
      :pagination="meta"
    />
  </b-card>

  <NOA :procurement="procurement" ref="NOA" />

  <UpdateStatus :procurement="procurement" @add="fetch()" ref="updateStatus" />
</template>
<script>
import _ from "lodash";

import PageHeader from "@/Shared/Components/PageHeader.vue";
import Pagination from "@/Shared/Components/Pagination.vue";
import NOA from "../Modals/NOA.vue";
import UpdateStatus from "../Modals/UpdateStatus.vue";

export default {
  props: ["bac_resolution", "procurement", "dropdowns"],
  components: { PageHeader, Pagination, NOA, UpdateStatus },
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

    openNOA() {
      this.$refs.NOA.show(type);
    },


    updateStatus(data) {
      let type = "NOA";
      this.$refs.updateStatus.show(data, type);
    },

    notConformed(data) {
      let type = "NOA Not Conformed";
      this.$refs.updateStatus.show(data, type);
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
  },
};
</script>

<style scoped>
.custom-hover-row:hover {
  background-color: hsl(0, 29%, 97%);
}
</style>
