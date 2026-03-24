<template>
  <Head title="Users" />
  <PageHeader title="Request Types Management" pageTitle="List" />
  <BRow>
    <div class="col-md-12">
      <div class="card bg-light-subtle shadow-none border">
        <div class="card-header bg-light-subtle">
          <div class="d-flex mb-n3">
            <div class="flex-shrink-0 me-3">
              <div style="height: 2.5rem; width: 2.5rem">
                <span class="avatar-title bg-success-subtle rounded p-2 mt-n1">
                  <i class="ri-shield-user-line text-success fs-24"></i>
                </span>
              </div>
            </div>
            <div class="flex-grow-1">
              <h5 class="mb-0 fs-14">
                <span class="text-body">Request Types Management</span>
              </h5>
              <p class="text-muted text-truncate-two-lines fs-12">
                Manage finance Request Type.
              </p>
            </div>
            <div class="flex-shrink-0" style="width: 45%"></div>
          </div>
        </div>

        <div class="card-body bg-white border-bottom shadow-none">
          <b-row class="mb-2 ms-1 me-1" style="margin-top: 12px">
            <b-col lg>
              <div class="input-group mb-1">
                <span class="input-group-text">
                  <i class="ri-search-line search-icon"></i>
                </span>
                <input
                  type="text"
                  v-model="filter.keyword"
                  placeholder="Search request types"
                  class="form-control"
                  style="width: 40%"
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

                <b-button type="button" variant="primary" @click="openCreate">
                  <i class="ri-add-circle-fill align-bottom me-1"></i>
                  Create
                </b-button>
              </div>
            </b-col>
          </b-row>
        </div>
        <div class="card-body bg-white rounded-bottom mt-3">
          <div
            class="table-responsive table-card"
            style="margin-top: -39px; height: calc(100vh - 350px); overflow: auto"
          >
            <table class="table align-middle table-hover mb-0">
              <thead class="table-light thead-fixed">
                <tr class="fs-12 fw-semibold">
                  <th style="width: 30%">Name</th>
                  <th style="width: 30%">Default Text</th>
                  <th style="width: 25%">Required Documents</th>
                  <th style="width: 15%" class="text-center">Actions</th>
                </tr>
              </thead>

              <tbody class="table-group-divider">
                <tr v-if="lists.length === 0">
                  <td colspan="4" class="p-0">
                    <div class="empty-state">
                      <i class="ri-file-warning-line empty-state-icon"></i>
                      <div class="empty-state-text">No Request Type found.</div>
                    </div>
                  </td>
                </tr>
                <tr
                  v-for="(list, index) in lists"
                  v-bind:key="index"
                  @click="selectRow(index)"
                  :class="{
                    'bg-info-subtle': index === selectedRow,
                    'bg-danger-subtle': list.is_active === 0 && index !== selectedRow,
                  }"
                >
                  <td class="fw-semibold">{{ list.name }}</td>
                  <td class="text-muted fs-12">{{ list.default_text || "-" }}</td>
                  <td class="text-muted fs-12">
                
                    <span v-for="(data, index) in list.documents" :key="index">
                      <b-badge class="me-1">{{  data.document.name }}</b-badge>
                    </span>
                  </td>
                  <td class="text-center">
                    <div class="d-inline-flex gap-1">
                      <b-button
                        size="sm"
                        variant="info"
                        v-b-tooltip.hover
                        title="Edit"
                        @click.stop="openEdit(list, index)"
                      >
                        <i class="ri-pencil-line"></i>
                      </b-button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="pagination-section">
          <Pagination
            v-if="meta"
            @fetch="fetch"
            :lists="lists.length"
            :links="links"
            :pagination="meta"
          />
        </div>
      </div>
    </div>
  </BRow>
  <Create @update="fetch()" ref="create"  :dropdowns="dropdowns"/>
</template>
<script>
import _ from "lodash";
import PageHeader from "@/Shared/Components/PageHeader.vue";
import Pagination from "@/Shared/Components/Pagination.vue";
import Create from "@/Pages/Modules/FAIMS/Finance/RequestTypes/Modals/Create.vue";

export default {
  components: { PageHeader, Pagination, Create },
  props: ["dropdowns"],
  data() {
    return {
      currentUrl: window.location.origin,
      lists: [],
      meta: {},
      links: {},
      filter: {
        keyword: null,
      },
      index: null,
      selectedRow: null,
      units: [],
      requiredDocuments: [],
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
      page_url = page_url || "/faims/finance-request-types";
      axios
        .get(page_url, {
          params: {
            keyword: this.filter.keyword,
            count: 10,
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

    openCreate() {
      this.$refs.create.show();
    },

    openEdit(data, index) {
      this.selectedRow = index;
      this.$refs.create.edit(data, index);
    },
    handleSaved() {
      this.fetch();
    },

    refresh() {
      this.filter.keyword = null;
      this.fetch();
    },

    selectRow(index) {
      if (this.selectedRow === index) {
        this.selectedRow = null;
      } else {
        this.selectedRow = index;
      }
    },

  },
};
</script>
<style scoped>
:global(body) {
  background: #f6f7fb;
}

.search-icon {
  color: #94a3b8;
}

.pagination-section {
  margin-top: 16px;
  display: flex;
  justify-content: flex-end;
}

.empty-state {
  min-height: calc(100vh - 420px);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 8px;
  color: #94a3b8;
  background: #fff;
}

.empty-state-icon {
  font-size: 40px;
  color: #94a3b8;
}

.empty-state-text {
  font-size: 14px;
}
</style>
