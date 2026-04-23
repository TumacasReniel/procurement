<template>
  <Head title="Modes of Procurement" />
  <PageHeader title="Modes of Procurement" pageTitle="Libraries" />

  <BRow class="procurement-index-page">
    <div class="col-md-12">
      <div class="card bg-light-subtle shadow-none border">
        <div class="card-header bg-light-subtle">
          <div class="d-flex mb-n3">
            <div class="flex-shrink-0 me-3">
              <div style="height: 2.5rem; width: 2.5rem">
                <span class="avatar-title bg-primary-subtle rounded p-2 mt-n1">
                  <i class="ri-list-settings-line text-primary fs-24"></i>
                </span>
              </div>
            </div>
            <div class="flex-grow-1">
              <h5 class="mb-0 fs-14">
                <span class="text-body">Modes of Procurement</span>
              </h5>
              <p class="text-muted text-truncate-two-lines fs-12">
                Maintain the procurement mode library,
                legal basis, active status, and PAP Code usage.
              </p>
            </div>
            <div class="flex-shrink-0" style="width: 45%"></div>
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
                  placeholder="Search Modes of Procurement"
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

                <b-button
                  type="button"
                  variant="primary"
                  @click="openCreate()"
                >
                  <i class="ri-add-circle-fill align-bottom me-1"></i>
                  Create
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
                    <th style="width: 32%">Mode of Procurement</th>
                    <th style="width: 22%">Reference</th>
                    <th style="width: 8%" class="text-center">PAP Codes</th>
                    <th style="width: 10%" class="text-center">Status</th>
                    <th style="width: 10%" class="text-center">Actions</th>
                  </tr>
                </thead>

                <tbody class="table-group-divider">
                  <tr
                    v-for="(list, index) in lists"
                    :key="list.id"
                    @click="selectRow(list.id)"
                    :class="{ 'table-active': selectedRow === list.id }"
                    class="cursor-pointer"
                  >
                    <td class="text-center fw-semibold">{{ index + 1 }}</td>

                    <td>
                      <div class="fw-semibold text-primary">{{ list.name }}</div>
                    </td>
                    <td>
                      <div
                        class="fw-medium text-truncate"
                        style="max-width: 240px"
                        v-b-tooltip.hover
                        :title="displayValue(list.section_reference)"
                      >
                        {{ displayValue(list.section_reference) }}
                      </div>
                      <small
                        v-if="hasDisplayValue(list.legal_basis)"
                        class="text-muted d-block text-truncate"
                        style="max-width: 240px"
                        v-b-tooltip.hover
                        :title="list.legal_basis"
                      >
                        {{ list.legal_basis }}
                      </small>
                    </td>
                    <td class="text-center">
                      <span class="badge bg-soft-secondary text-secondary px-2 py-1 fs-12 fw-medium rounded-pill">
                        {{ list.procurement_codes_count }}
                      </span>
                    </td>
                    <td class="text-center">
                      <b-badge :class="list.is_active ? 'bg-success' : 'bg-secondary'" class="fs-11">
                        {{ list.is_active ? "Active" : "Inactive" }}
                      </b-badge>
                    </td>
                    <td>
                      <div class="d-flex justify-content-center gap-1">
                        <b-button
                          @click.stop="editMode(list)"
                          size="sm"
                          variant="info"
                          class="btn-icon"
                          v-b-tooltip.hover
                          title="Edit"
                          style="border-radius: 8px"
                        >
                          <i class="ri-pencil-line"></i>
                        </b-button>

                        <b-button
                          @click.stop="deleteMode(list)"
                          size="sm"
                          variant="danger"
                          class="btn-icon"
                          v-b-tooltip.hover
                          :title="list.can_delete ? 'Delete' : 'Cannot delete while used by PAP Codes'"
                          style="border-radius: 8px"
                          :disabled="!list.can_delete"
                        >
                          <i class="ri-delete-bin-line"></i>
                        </b-button>
                      </div>
                    </td>
                  </tr>

                  <tr v-if="lists.length === 0">
                    <td colspan="6" class="text-center text-muted py-4">
                      No modes of procurement found.
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

  <ModeOfProcurementModal @update="fetch()" ref="modal" />
</template>

<script>
import _ from "lodash";
import { router } from "@inertiajs/vue3";
import PageHeader from "@/Shared/Components/PageHeader.vue";
import Pagination from "@/Shared/Components/Pagination.vue";
import ModeOfProcurementModal from "@/Pages/Modules/FAIMS/Procurement/Modals/ModeOfProcurement.vue";

export default {
  components: { ModeOfProcurementModal, PageHeader, Pagination },
  data() {
    return {
      lists: [],
      meta: {},
      links: {},
      selectedRow: null,
      filter: {
        keyword: null,
      },
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
    checkSearchStr: _.debounce(function () {
      this.fetch();
    }, 300),
    fetch(pageUrl) {
      const url = pageUrl || "/faims/modes-of-procurement";

      axios
        .get(url, {
          params: {
            keyword: this.filter.keyword,
            count: 10,
            option: "lists",
          },
        })
        .then((response) => {
          this.lists = response.data.data;
          this.meta = response.data.meta;
          this.links = response.data.links;
        })
        .catch((err) => console.log(err));
    },
    openCreate() {
      this.$refs.modal.show();
    },
    editMode(data) {
      this.$refs.modal.show(data);
    },
    deleteMode(data) {
      if (!data.can_delete) {
        return;
      }

      const confirmed = window.confirm(
        `Delete "${data.name}" from Modes of Procurement?`
      );

      if (!confirmed) {
        return;
      }

      router.delete(`/faims/modes-of-procurement/${data.id}`, {
        preserveScroll: true,
        onSuccess: () => {
          this.fetch();
        },
      });
    },
    selectRow(index) {
      this.selectedRow = this.selectedRow === index ? null : index;
    },
    refresh() {
      this.filter.keyword = null;
      this.fetch();
    },
    displayValue(value) {
      if (!value || value === "n/a") {
        return "-";
      }

      return value;
    },
    hasDisplayValue(value) {
      return Boolean(value && value !== "n/a");
    },
  },
};
</script>
