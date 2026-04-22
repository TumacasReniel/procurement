<template>
  <Head title="Suppliers" />
  <PageHeader title="Suppliers" pageTitle="Procurement" />

  <BRow class="procurement-index-page">
    <div class="col-md-12">
      <div class="card bg-light-subtle shadow-none border">
        <div class="card-header bg-light-subtle">
          <div class="d-flex mb-n3">
            <div class="flex-shrink-0 me-3">
              <div style="height: 2.5rem; width: 2.5rem">
                <span class="avatar-title bg-primary-subtle rounded p-2 mt-n1">
                  <i class="ri-building-2-fill text-primary fs-24"></i>
                </span>
              </div>
            </div>
            <div class="flex-grow-1">
              <h5 class="mb-0 fs-14">
                <span class="text-body">Supplier Directory</span>
              </h5>
              <p class="text-muted text-truncate-two-lines fs-12">
                A detailed list of registered suppliers including code, address,
                representatives, attachments, and availability status.
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
                  v-model="filter.keyword"
                  type="text"
                  placeholder="Search Supplier"
                  class="form-control"
                  style="width: 40%"
                />

                <Multiselect
                  v-model="filter.status"
                  class="white"
                  style="width: 15%"
                  :options="dropdowns.statuses || []"
                  label="name"
                  :searchable="true"
                  :can-clear="true"
                  placeholder="Select Status"
                />

                <span
                  class="input-group-text"
                  style="cursor: pointer"
                  v-b-tooltip.hover
                  title="Refresh"
                  @click="refresh()"
                >
                  <i class="bx bx-refresh search-icon"></i>
                </span>

                <b-button type="button" variant="primary" @click="openSupplier()">
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
                    <th style="width: 12%">Code</th>
                    <th style="width: 18%">Supplier</th>
                    <th style="width: 18%">Address</th>
                    <th style="width: 16%">Representatives</th>
                    <th style="width: 8%">Attachments</th>
                    <th style="width: 12%">Created By/Date</th>
                    <th style="width: 7%">Status</th>
                    <th style="width: 10%" class="text-center">Actions</th>
                  </tr>
                </thead>

                <tbody class="table-group-divider">
                  <tr v-if="!lists.length">
                    <td colspan="9" class="text-center text-muted py-5">
                      No suppliers found.
                    </td>
                  </tr>

                  <tr
                    v-for="(list, index) in lists"
                    :key="list.id"
                    class="cursor-pointer"
                    :class="{ 'table-active': selectedRow === list.id }"
                    @click="selectRow(list.id)"
                  >
                    <td class="text-center fw-semibold">
                      {{ rowNumber(index) }}
                    </td>
                    <td>
                      <h6 class="mb-0 fs-14 fw-semibold text-primary">
                        {{ list.code }}
                      </h6>
                    </td>
                    <td>
                      <h6 class="mb-0 fs-14 fw-semibold">{{ list.name }}</h6>
                      <p class="text-muted mb-0 fs-12">
                        TIN: {{ list.tin || "-" }}
                      </p>
                      <p class="text-muted mb-0 fs-12">
                        Created {{ formatDate(list.created_at) }}
                      </p>
                      <p
                        v-if="list.approval_status === 'Pending Approval'"
                        class="text-warning mb-0 fs-12 fw-semibold"
                      >
                        Awaiting Procurement Officer approval
                      </p>
                      <p
                        v-else-if="list.approved_by"
                        class="text-muted mb-0 fs-12"
                      >
                        Approved by {{ list.approved_by }}
                      </p>
                    </td>
                    <td>
                      <div
                        class="text-truncate"
                        style="max-width: 220px"
                        v-b-tooltip.hover
                        :title="list.address || 'No address provided'"
                      >
                        {{ list.address || "-" }}
                      </div>
                    </td>
                    <td>
                      <div
                        v-if="list.conformes && list.conformes.length"
                        class="d-flex flex-wrap gap-1"
                      >
                        <span
                          v-for="(conforme, conformeIndex) in list.conformes.slice(0, 2)"
                          :key="conforme.id || conformeIndex"
                          class="badge bg-soft-info text-info px-2 py-1 fs-12 fw-medium rounded-pill"
                        >
                          {{ conforme.name }}
                        </span>
                        <span
                          v-if="(list.conformes_count || 0) > 2"
                          class="badge bg-soft-secondary text-secondary px-2 py-1 fs-12 fw-medium rounded-pill"
                        >
                          +{{ list.conformes_count - 2 }}
                        </span>
                      </div>
                      <span v-else class="text-muted">-</span>
                    </td>
                    <td>
                      <span
                        class="badge bg-soft-warning text-warning px-2 py-1 fs-12 fw-medium rounded-pill"
                      >
                        <i class="ri-attachment-2 me-1"></i>
                        {{ list.attachments_count || 0 }}
                      </span>
                    </td>
                    <td>
                      {{ list.created_by || "System" }}
                      <p class="text-muted mb-0 fs-12">
                        {{ formatDate(list.created_at) }}
                      </p>
                      <p v-if="list.approved_at" class="text-muted mb-0 fs-12">
                        Approved {{ formatDate(list.approved_at) }}
                      </p>
                    </td>
                    <td>
                      <b-badge :class="statusClass(list)" class="fs-11">
                        {{ statusName(list) }}
                      </b-badge>
                    </td>
                    <td>
                      <div class="d-flex justify-content-center gap-1">
                        <b-button
                          v-if="canApproveSupplier(list)"
                          size="sm"
                          variant="success"
                          class="btn-icon"
                          style="border-radius: 8px"
                          v-b-tooltip.hover
                          title="Approve Supplier"
                          @click.stop="requestApprove(list)"
                        >
                          <i class="ri-check-double-line"></i>
                        </b-button>

                        <b-button
                          size="sm"
                          variant="primary"
                          class="btn-icon"
                          style="border-radius: 8px"
                          v-b-tooltip.hover
                          title="Edit"
                          @click.stop="editSupplier(list)"
                        >
                          <i class="ri-edit-line"></i>
                        </b-button>

                        <b-button
                          v-if="canToggleStatus(list)"
                          size="sm"
                          :variant="list.is_active == 1 ? 'danger' : 'success'"
                          class="btn-icon"
                          style="border-radius: 8px"
                          v-b-tooltip.hover
                          :title="list.is_active == 1 ? 'Deactivate' : 'Activate'"
                          @click.stop="toggleStatus(list)"
                        >
                          <i
                            :class="
                              list.is_active == 1
                                ? 'ri-pause-circle-line'
                                : 'ri-play-circle-line'
                            "
                          ></i>
                        </b-button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>

              <div class="card-footer">
                <Pagination
                  v-if="meta && lists.length"
                  class="ms-2 me-2 mt-n1"
                  :lists="lists.length"
                  :links="links"
                  :pagination="meta"
                  @fetch="fetch"
                />
              </div>
            </div>
          </div>
        </b-card>
      </div>
    </div>
  </BRow>

  <SupplierModal ref="create" :dropdowns="dropdowns" @add="fetch()" @update="fetch()" />

  <DeactivateModal
    :supplier="selectedSupplierForStatus"
    @cancel="cancelStatusChange"
    @close="closeDeactivateModal"
    @status-changed="onStatusChanged"
  />

  <b-modal
    v-model="showApproveModal"
    style="--vz-modal-width: 600px"
    title="Approve Supplier"
    header-class="p-3 bg-light"
    class="v-modal-custom"
    modal-class="zoomIn"
    centered
    no-close-on-backdrop
  >
    <form class="customform">
      <div class="m-5 text-center" v-if="selectedSupplierForApproval">
        <div>
          Are you sure you want to approve supplier
          <span class="text-primary">"{{ selectedSupplierForApproval.name }}"</span>?
        </div>

        <div class="text-muted small mt-3">
          Once approved, the supplier will follow its current active or inactive setting.
        </div>
      </div>
    </form>

    <template v-slot:footer>
      <b-button
        type="button"
        variant="light"
        :disabled="approvingSupplier"
        @click="closeApproveModal()"
        block
      >
        Close
      </b-button>
      <b-button
        type="button"
        variant="primary"
        :disabled="approvingSupplier"
        @click="confirmApproveSupplier()"
        block
      >
        {{ approvingSupplier ? "Processing..." : "Approve Supplier" }}
      </b-button>
    </template>
  </b-modal>
</template>

<script>
import _ from "lodash";
import { Head } from "@inertiajs/vue3";
import Multiselect from "@vueform/multiselect";
import PageHeader from "@/Shared/Components/PageHeader.vue";
import Pagination from "@/Shared/Components/Pagination.vue";
import SupplierModal from "@/Pages/Modules/FAIMS/Procurement/Modals/Supplier.vue";
import DeactivateModal from "@/Pages/Modules/FAIMS/Procurement/Suppliers/Modals/Deactivate.vue";

export default {
  components: {
    Head,
    PageHeader,
    Pagination,
    SupplierModal,
    DeactivateModal,
    Multiselect,
  },
  props: {
    dropdowns: {
      type: Object,
      default: () => ({}),
    },
  },
  data() {
    return {
      lists: [],
      meta: {},
      links: {},
      selectedRow: null,
      selectedSupplierForStatus: null,
      selectedSupplierForApproval: null,
      showApproveModal: false,
      approvingSupplier: false,
      filter: {
        keyword: null,
        status: null,
      },
    };
  },
  computed: {
    current_roles() {
      return Array.isArray(this.$page?.props?.roles) ? this.$page.props.roles : [];
    },
    canApproveSuppliers() {
      return this.current_roles.some((role) => {
        return ["Procurement Officer", "Administrator"].includes(role);
      });
    },
  },
  watch: {
    "filter.keyword"(value) {
      this.checkSearchStr(value);
    },
    "filter.status"() {
      this.fetch();
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
      const url = pageUrl || "/faims/suppliers";

      axios
        .get(url, {
          params: {
            keyword: this.filter.keyword,
            status: this.filter.status,
            count: 10,
            option: "lists",
          },
        })
        .then((response) => {
          this.lists = response.data.data;
          this.meta = response.data.meta;
          this.links = response.data.links;
        })
        .catch((error) => console.log(error));
    },
    openSupplier() {
      this.$refs.create.show();
    },
    editSupplier(data) {
      this.$refs.create.edit(data);
    },
    requestApprove(supplier) {
      this.selectedSupplierForApproval = supplier;
      this.showApproveModal = true;
    },
    closeApproveModal() {
      this.showApproveModal = false;
      this.selectedSupplierForApproval = null;
      this.approvingSupplier = false;
    },
    confirmApproveSupplier() {
      if (!this.selectedSupplierForApproval) {
        return;
      }

      this.approvingSupplier = true;

      axios
        .patch(`/faims/suppliers/${this.selectedSupplierForApproval.id}/approve`)
        .then((response) => {
          const updatedSupplier = response?.data?.data;
          const index = this.lists.findIndex((item) => item.id === updatedSupplier?.id);

          if (index !== -1 && updatedSupplier) {
            this.lists[index] = updatedSupplier;
          } else {
            this.fetch();
          }

          this.closeApproveModal();
        })
        .catch((error) => {
          console.error(error);
          this.approvingSupplier = false;
        });
    },
    toggleStatus(supplier) {
      this.selectedSupplierForStatus = supplier;
    },
    cancelStatusChange() {
      this.selectedSupplierForStatus = null;
    },
    closeDeactivateModal() {
      this.selectedSupplierForStatus = null;
    },
    onStatusChanged(updatedSupplier) {
      const index = this.lists.findIndex((item) => item.id === updatedSupplier.id);

      if (index !== -1) {
        this.lists[index] = updatedSupplier;
      } else {
        this.fetch();
      }

      this.selectedSupplierForStatus = null;
    },
    selectRow(id) {
      this.selectedRow = this.selectedRow === id ? null : id;
    },
    refresh() {
      this.filter.keyword = null;
      this.filter.status = null;
      this.selectedRow = null;
      this.fetch();
    },
    rowNumber(index) {
      const currentPage = this.meta.current_page || 1;
      const perPage = this.meta.per_page || this.lists.length || 0;

      return (currentPage - 1) * perPage + index + 1;
    },
    canApproveSupplier(supplier) {
      return this.canApproveSuppliers && supplier?.approval_status === "Pending Approval";
    },
    canToggleStatus(supplier) {
      return supplier?.approval_status === "Approved";
    },
    statusName(supplier) {
      return supplier.status?.name || (supplier.is_active == 1 ? "Active" : "Inactive");
    },
    statusClass(supplier) {
      return supplier.status?.bg || (supplier.is_active == 1 ? "bg-success" : "bg-secondary");
    },
    formatDate(date) {
      if (!date) {
        return "-";
      }

      return new Date(date).toLocaleDateString("en-US", {
        year: "numeric",
        month: "short",
        day: "numeric",
      });
    },
  },
};
</script>
