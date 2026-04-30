<template>
  <Head title="Procurement Codes" />
  <PageHeader title="Procurement Codes" pageTitle="Libraries" />
  <BRow class="procurement-index-page">
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
                <span class="text-body">All Procurenent Codes</span>
              </h5>
              <p class="text-muted text-truncate-two-lines fs-12">
                A comprehensive list of all Procurenent Codes across all procurements.
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
                <b-button
                  v-if="canManagePapCodes"
                  type="button"
                  variant="primary"
                  @click="openPAPCodModal()"
                >
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
                    <th style="width: 15%">Codes</th>
                    <th style="width: 18%">Project Description/Title</th>
                    <th style="width: 14%">Budget</th>
                    <th style="width: 15%">Mode of Procurement / APP Type</th>
                    <th style="width: 12%">End Users</th>
                    <th style="width: 12%">Year</th>
                    <th style="width: 12%" class="text-center">Actions</th>
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
                      <div
                        class="text-truncate"
                        style="max-width: 200px"
                        v-b-tooltip.hover
                        :title="list.title"
                      >
                        {{ list.title }}
                      </div>
                    </td>
                    <td>
                      <div class="fw-semibold">{{ formatCurrency(list.allocated_budget) }}</div>
                      <small
                        class="d-block"
                        :class="remainingBudget(list) < 0 ? 'text-danger' : 'text-muted'"
                      >
                        Remaining: {{ formatCurrency(remainingBudget(list)) }}
                      </small>
                      <small
                        v-if="Number(list.pending_budget_increase_requests_count) > 0"
                        class="d-block text-primary fw-medium mt-1"
                      >
                        Pending top-ups: {{ list.pending_budget_increase_requests_count }}
                      </small>
                    </td>
                    <td>
                      <span>{{ list.mode_of_procurement.name }}</span> <br />
                      <span class="text-muted">{{ list.app_type.name }}</span>
                    </td>
                    <td>
                      <div v-for="(end_user, endUserIndex) in list.end_users" :key="endUserIndex">
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
                          @click.stop="openProfile(list)"
                          size="sm"
                          variant="primary"
                          class="btn-icon"
                          v-b-tooltip.hover
                          title="View Profile"
                          style="border-radius: 8px"
                        >
                          <i class="ri-eye-line"></i>
                        </b-button>
                        <b-button
                          v-if="canManagePapCodes && list.can_edit"
                          @click.stop="editPAP(list)"
                          size="sm"
                          variant="success"
                          class="btn-icon"
                          v-b-tooltip.hover
                          title="Edit"
                          style="border-radius: 8px"
                        >
                          <i class="ri-edit-line"></i>
                        </b-button>
                        <b-button
                          v-if="canRequestBudgetIncrease"
                          @click.stop="openBudgetIncrease(list)"
                          size="sm"
                          variant="warning"
                          class="btn-icon"
                          v-b-tooltip.hover
                          title="Request Additional Budget"
                          style="border-radius: 8px"
                        >
                          <i class="ri-funds-line"></i>
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
  <ProcurementCodeBudgetIncrease
    ref="budgetIncrease"
    @submitted="handleBudgetIncreaseSubmitted"
  />
</template>
<script>
import _ from "lodash";
import { router } from "@inertiajs/vue3";
import PageHeader from "@/Shared/Components/PageHeader.vue";
import Pagination from "@/Shared/Components/Pagination.vue";
import ProcurementCodeModal from "@/Pages/Modules/FAIMS/Procurement/Modals/ProcurementCode.vue";
import ProcurementCodeBudgetIncrease from "@/Pages/Modules/FAIMS/Procurement/Modals/ProcurementCodeBudgetIncrease.vue";

export default {
  props: ["dropdowns"],
  components: {
    ProcurementCodeModal,
    ProcurementCodeBudgetIncrease,
    Pagination,
    PageHeader,
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
      mode_of_procurements: {},
      index: null,
      selectedRow: null,
    };
  },
  computed: {
    currentRoles() {
      return Array.isArray(this.$page?.props?.roles) ? this.$page.props.roles : [];
    },
    canManagePapCodes() {
      return this.currentRoles.some((role) => {
        return ["Procurement Officer", "Administrator"].includes(role);
      });
    },
    canRequestBudgetIncrease() {
      return this.currentRoles.some((role) => {
        return role === "Procurement Officer";
      });
    },
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
    checkSearchStr: _.debounce(function () {
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

    openProfile(data) {
      router.get(`/faims/procurement-codes/${data.id}`);
    },

    openBudgetIncrease(data) {
      this.$refs.budgetIncrease.show(data);
    },

    handleBudgetIncreaseSubmitted() {
      this.fetch();
    },

    formatCurrency(value) {
      return new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
      }).format(Number(value || 0));
    },

    remainingBudget(list) {
      return Number(list.remaining_budget ?? list.allocated_budget ?? 0);
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
