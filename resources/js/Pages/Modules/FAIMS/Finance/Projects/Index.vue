<template>
  <Head title="Projects" />
  <PageHeader title="Projects" pageTitle="List" />
  <BRow>
    <div class="col-md-12">
      <div class="card bg-light-subtle shadow-none border">
        <div class="card-header bg-light-subtle">
          <div class="d-flex mb-n3">
            <div class="flex-shrink-0 me-3">
              <div style="height: 2.5rem; width: 2.5rem">
                <span class="avatar-title bg-primary-subtle rounded p-2 mt-n1">
                  <i class="ri-projector-line text-primary fs-24"></i>
                </span>
              </div>
            </div>
            <div class="flex-grow-1">
              <h5 class="mb-0 fs-14">
                <span class="text-body">Project Management</span>
              </h5>
              <p class="text-muted text-truncate-two-lines fs-12">
                Manage projects and details.
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
                  placeholder="Search projects"
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
                  <th style="width: 6%" class="text-center">#</th>
                  <th style="width: 24%">Name</th>
                  <th style="width: 30%">Description</th>
                  <th style="width: 20%">Type</th>
                  <th style="width: 14%" class="text-center">Actions</th>
                </tr>
              </thead>

              <tbody class="table-group-divider">
                <tr v-if="lists.length === 0">
                  <td colspan="5" class="p-0">
                    <div class="empty-state">
                      <i class="ri-file-warning-line empty-state-icon"></i>
                      <div class="empty-state-text">No project found.</div>
                    </div>
                  </td>
                </tr>
                <tr
                  v-for="(list, index) in lists"
                  v-bind:key="index"
                  @click="selectRow(index)"
                  :class="{
                    'bg-info-subtle': index === selectedRow,
                  }"
                >
                  <td class="text-center fw-semibold">{{ index + 1 }}</td>
                  <td class="fw-semibold">{{ list.name }}</td>
                  <td class="text-muted fs-12">{{ list.description }}</td>
                  <td>
                    <span class=" fs-11">{{ list.type?.name }}</span>
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
                      <b-button
                        size="sm"
                        variant="danger"
                        v-b-tooltip.hover
                        title="Delete"
                        @click.stop="openDelete(list.id)"
                      >
                        <i class="ri-delete-bin-line"></i>
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
  <Create @add="fetch()" ref="create" :dropdowns="dropdowns" />
</template>
<script>
import _ from "lodash";
import Multiselect from "@vueform/multiselect";
import PageHeader from "@/Shared/Components/PageHeader.vue";
import Pagination from "@/Shared/Components/Pagination.vue";
import Create from "@/Pages/Modules/FAIMS/Finance/Projects/Modals/Create.vue";

export default {
  components: { PageHeader, Pagination, Multiselect, Create },
  props: ['dropdowns'],
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
      page_url = page_url || "/faims/finance-projects";
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
      this.$refs.create.edit(data);
    },

    openDelete(id) {
      this.$swal({
        title: "Are you sure?",
        text: "You won't be able to revert this!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Yes, delete it!",
      }).then((result) => {
        if (result.isConfirmed) {
          axios.delete(`/faims/finance-projects/${id}`).then(() => {
            this.fetch();
            this.$swal("Deleted!", "Your file has been deleted.", "success");
          });
        }
      });
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

