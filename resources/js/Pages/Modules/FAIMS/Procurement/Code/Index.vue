<template>
  <Head title="PAP Codes" />
  <PageHeader title="PAP Codes" pageTitle="Libraries" />
  <BRow>
    <div class="col-md-12">
      <div class="card bg-light-subtle shadow-none border">
        <div class="card-header bg-light-subtle">
          <div class="d-flex mb-n3">
            <div class="flex-shrink-0 me-3">
              <div style="height: 2.5rem; width: 2.5rem">
                <span class="avatar-title bg-success-subtle rounded p-2 mt-n1">
                  <i class="ri-file-list-fill text-success fs-24"></i>
                </span>
              </div>
            </div>
            <div class="flex-grow-1">
              <h5 class="mb-0 fs-14">
                <span class="text-body">All PAP Codes</span>
              </h5>
              <p class="text-muted text-truncate-two-lines fs-12">
                A comprehensive list of all PAP Codes across all procurements.
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
                  placeholder="Search PAP Code"
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
                <b-button type="button" variant="primary" @click="openPAPCodModal()">
                  <i class="ri-add-circle-fill align-bottom me-1"></i> New
                </b-button>
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
                    <th style="width: 15%">PAP Codes</th>
                    <th style="width: 18%">Project Description/Title</th>
                    <th style="width: 12%">Allocated Budget</th>
                    <th style="width: 15%">Mode of Procurement / APP Type</th>
                    <th style="width: 12%">End Users</th>
                    <th style="width: 12%">Year</th>
                    <th style="width: 10%" class="text-center">Actions</th>
                  </tr>
                </thead>

                <tbody class="table-group-divider">
                  <tr
                    v-for="(list, index) in lists"
                    :key="index"
                    @click="selectRow(list.id)"
                    :class="{ 'table-active': selectedRow === list.id }"
                    class="cursor-pointer"
                  >
                    <td class="text-center fw-semibold">{{ index + 1 }}</td>
                    <td>
                      <div class="d-flex align-items-center">
                        <div>
                          <h6 class="mb-0 fs-14 fw-semibold text-success">{{ list.code }}</h6>
                        </div>
                      </div>
                    </td>
                    <td>
                      <div class="text-truncate" style="max-width: 200px;" v-b-tooltip.hover :title="list.title">
                        {{ list.title }}
                      </div>
                    </td>
                    <td>{{ formatCurrency(list.allocated_budget) }}</td>
                    <td>
                      <span>{{ list.mode_of_procurement.name }}</span> <br />
                      <span class="text-muted">{{ list.app_type.name }}</span>
                    </td>
                    <td>
                    
                      <div v-for="(end_user, index) in list.end_users" :key="index">
                        <b-badge class="me-1">
                          {{ end_user.end_user.name }}
                        </b-badge>
                      </div>
                    </td>
                    <td>
                      {{ list.year }}
                    </td>
                    <td>
                      <div class="d-flex justify-content-center gap-1">
                        <b-button
                          @click="editPAP(list)"
                          size="sm"
                          variant="success"
                          class="btn-icon"
                          v-b-tooltip.hover
                          title="Edit"
                          style="border-radius: 8px;"
                        >
                          <i class="ri-edit-line"></i>
                        </b-button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
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

  <ProcurementCodeModal
    @add="fetch()"
    @update="fetch()"
    :mode_of_procurements="dropdowns.mode_of_procurements"
    :app_types="dropdowns.app_types"
    :end_users="dropdowns.end_users"
    ref="create"
  />
</template>
<script>
import _ from "lodash";
import PageHeader from "@/Shared/Components/PageHeader.vue";
import Pagination from "@/Shared/Components/Pagination.vue";
import ProcurementCodeModal from "@/Pages/Modules/FAIMS/Procurement/Modals/ProcurementCode.vue";

export default {
  props: ["dropdowns"],
  components: { ProcurementCodeModal, Pagination, PageHeader },
  data() {
    return {
      currentUrl: window.location.origin,
      lists: [],
      meta: {},
      links: {},
      filter: {
        keyword: null,
      },
      mode_of_procurements: {},
      index: null,
    };
  },
  watch: {
    "filter.keyword"(newVal) {
      this.checkSearchStr(newVal);
    },
  },

  mounted() {
    // this.getModeOfProcurements();
  },

  created() {
    this.fetch();
  },
  methods: {
    checkSearchStr: _.debounce(function (string) {
      this.fetch();
    }, 300),
    fetch(page_url) {
      page_url = "/faims/procurement-codes";
      axios
        .get(page_url, {
          params: {
            keyword: this.filter.keyword,
            option: "lists",
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

    openPAPCodModal() {
      this.$refs.create.show();
    },

    editPAP(data) {
      this.$refs.create.edit(data);
    },

    viewPAP(data) {
      this.$refs.create.view(data);
    },

    formatCurrency(value) {
      return new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
      }).format(value);
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
