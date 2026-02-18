<template>
  <Head title="Notice of Awards" />
  <PageHeader title="Notice of Awards" pageTitle="All" />
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
                <span class="text-body">All Notice of Awards</span>
              </h5>
              <p class="text-muted text-truncate-two-lines fs-12">
                A comprehensive list of all Notice of Awards across all procurements.
              </p>
            </div>
          </div>
        </div>
        <div class="car-body bg-white border-bottom shadow-none">
             <div class="car-body bg-white border-bottom shadow-none">
          <b-row class="mb-2 ms-1 me-1" style="margin-top: 12px">
            <b-col lg>
              <div class="input-group mb-1">
                <span class="input-group-text">
                  <i class="ri-search-line search-icon"></i
                ></span>
                <input
                  type="text"
                  v-model="filter.keyword"
                  placeholder="Search Procurement Request"
                  class="form-control"
                  style="width: 40%"
                />
                <Multiselect
                  class="white"
                  style="width: 15%"
                  :options="dropdowns.statuses"
                  v-model="filter.status"
                  label="name"
                  :searchable="true"
                  placeholder="Select Status"
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
        </div>
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
                    <th style="width: 15%">NOA No.</th>
                    <th style="width: 15%">Procurement Code</th>
                    <th style="width: 18%">Purpose</th>
                    <th style="width: 12%">Supplier</th>
                    <th style="width: 12%">Date Created</th>
                    <th style="width: 10%">Status</th>
                    <th style="width: 10%" class="text-center">Actions</th>
                  </tr>
                </thead>

                <tbody class="table-group-divider">
                  <tr
                    v-for="(list, index) in lists"
                    v-bind:key="index"
                    @click="selectRow(list.id)"
                    :class="{ 'table-active': selectedRow === list.id }"
                    class="cursor-pointer"
                  >
                    <td class="text-center fw-semibold">{{ index + 1 }}</td>
                    <td>
                      <div class="d-flex align-items-center">
                        <div>
                          <h6 class="mb-0 fs-14 fw-semibold text-info">{{ list.code }}</h6>
                        </div>
                      </div>
                    </td>
                    <td>
                      <span class="badge bg-soft-primary text-primary px-2 py-1 fs-12 fw-medium rounded-pill">
                        {{ list.procurement?.code }}
                      </span>
                    </td>
                    <td>
                      <div class="text-truncate" style="max-width: 200px;" v-b-tooltip.hover :title="list.procurement?.purpose">
                        {{ list.procurement?.purpose }}
                      </div>
                    </td>
                    <td>
                      <div class="text-truncate" style="max-width: 150px;" v-b-tooltip.hover :title="list.procurement_quotation?.supplier?.name">
                        {{ list.procurement_quotation?.supplier?.name }}
                      </div>
                    </td>
                    <td>{{ formatDate(list.created_at) }}</td>
                    <td>
                      <b-badge :class="list.status.bg" class="fs-11">{{ list.status?.name }}</b-badge>
                    </td>
                    <td>
                      <div class="d-flex justify-content-center gap-1">
                        <b-button
                          @click="viewProcurement(list)"
                          size="sm"
                          variant="info"
                          class="btn-icon"
                          v-b-tooltip.hover
                          title="View Procurement"
                          style="border-radius: 8px;"
                        >
                          <i class="ri-eye-line"></i>
                        </b-button>

                        <b-button
                          @click="printNOA(list)"
                          size="sm"
                          variant="dark"
                          class="btn-icon"
                          v-b-tooltip.hover
                          title="Print"
                          style="border-radius: 8px;"
                        >
                          <i class="ri-printer-line"></i>
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
</template>
<script>
import _ from "lodash";

import Multiselect from "@vueform/multiselect";
import PageHeader from "@/Shared/Components/PageHeader.vue";
import Pagination from "@/Shared/Components/Pagination.vue";
import { router } from "@inertiajs/vue3";

export default {
  components: { PageHeader, Pagination, Multiselect },
  props: ["dropdowns"],
  data() {
    return {
      currentUrl: window.location.origin,
      lists: [],
      meta: {},
      links: {},
      filter: {
        keyword: null,
        status: null,
      },
      selectedRow: null,
    };
  },
  watch: {
    "filter.keyword"(newVal) {
      this.checkSearchStr(newVal);
    },
    "filter.status"(newVal) {
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
      page_url = page_url || "/faims/notice-of-awards";
      axios
        .get(page_url, {
          params: {
            keyword: this.filter.keyword,
            status: this.filter.status,
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
    formatDate(date) {
      return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
      });
    },
    viewProcurement(list) {
      router.visit(`/faims/procurements/${list.procurement.id}`, {
        method: 'get',
        data: { option: 'view', tab: 1 },
      });
    },
    printNOA(list) {
      window.open(`/faims/notice-of-awards/${list.id}?option=print&type=noa`);
    },
    selectRow(index) {
      this.selectedRow = this.selectedRow == index ? null : index;
    },
    refresh() {
      this.filter.status = null;
      this.filter.keyword = null;
      this.fetch();
    },
  },
};
</script>
