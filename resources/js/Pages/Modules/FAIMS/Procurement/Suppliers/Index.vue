<template>
  <PageHeader title="Suppliers Management" pageTitle="Procurement" />

  <!-- Search and Filter Section -->
  <b-row class="g-3 mb-4">
    <b-col lg="10">
      <div class="search-box">
        <div class="position-relative">
          <input
            type="text"
            v-model="filter.keyword"
            class="form-control form-control-lg"
            placeholder="Search suppliers by name, code, or address..."
            style="
              border-radius: 10px;
              padding-left: 45px;
              box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            "
          />
          <i
            class="ri-search-line search-icon fs-5"
            style="
              position: absolute;
              left: 15px;
              top: 50%;
              transform: translateY(-50%);
              color: #6c757d;
            "
          ></i>
        </div>
      </div>
    </b-col>
    <b-col lg="2">
      <div class="d-flex gap-2">
        <b-button
          @click="refresh()"
          variant="secondary"
          class="btn-icon"
          v-b-tooltip.hover
          title="Refresh"
          style="border-radius: 10px"
        >
          <i class="bx bx-refresh fs-5"></i>
        </b-button>
        <b-button
          type="button"
          variant="primary"
          @click="openSupplier()"
          class="flex-fill"
          style="border-radius: 10px; box-shadow: 0 4px 15px rgba(13, 110, 253, 0.3)"
        >
          <i class="ri-add-circle-fill align-bottom me-2"></i> New Supplier
        </b-button>
      </div>
    </b-col>
  </b-row>

  <!-- Suppliers Table -->
  <div class="suppliers-table-container">
    <div class="card border-0 shadow-sm" style="border-radius: 15px; overflow: hidden">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead class="table-light">
              <tr style="border-bottom: 2px solid #e9ecef">
                <th class="border-0 fw-bold text-muted ps-4">#</th>
                <th class="border-0 fw-bold text-muted">Code</th>
                <th class="border-0 fw-bold text-muted">Company Name</th>
                <th class="border-0 fw-bold text-muted">Address</th>
                <th class="border-0 fw-bold text-muted">Representatives</th>
                <th class="border-0 fw-bold text-muted">Attachments</th>
                <th class="border-0 fw-bold text-muted">Status</th>
                <th class="border-0 fw-bold text-muted text-center">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="(supplier, index) in lists"
                :key="supplier.id"
                class="supplier-row"
                :class="{ 'table-secondary': supplier.is_active == 0 }"
              >
                <td class="ps-4 fw-bold">{{ index + 1 }}</td>
                <td>
                  <span class="badge bg-soft-primary text-primary px-2 py-1 fs-12 fw-bold rounded-pill">
                    {{ supplier.code }}
                  </span>
                </td>
                <td>
                  <div class="d-flex align-items-center">
                    <div>
                      <h6 class="mb-0 fw-bold">{{ supplier.name }}</h6>
                      <small class="text-muted"
                        >Created {{ formatDate(supplier.created_at) }}</small
                      >
                    </div>
                  </div>
                </td>
                <td>
                  <span
                    class="text-truncate d-inline-block"
                    style="max-width: 200px"
                    v-b-tooltip.hover
                    :title="supplier.address || 'No address'"
                  >
                    {{ supplier.address || "No address provided" }}
                  </span>
                </td>
                <td>
                  <div v-if="supplier.conformes && supplier.conformes.length > 0">
                    <span
                      v-for="(conforme, idx) in supplier.conformes.slice(0, 2)"
                      :key="conforme.id"
                      class="badge bg-success me-1 mb-1"
                    >
                      {{ conforme.name }}
                    </span>
                    <span v-if="supplier.conformes.length > 2" class="badge bg-secondary">
                      +{{ supplier.conformes.length - 2 }}
                    </span>
                  </div>
                  <span v-else class="text-muted small">No representatives</span>
                </td>
                <td>
                  <span class="badge bg-warning text-dark px-2 py-1">
                    <i class="ri-attachment-line me-1"></i
                    >{{ supplier.attachments ? supplier.attachments.length : 0 }}
                  </span>
                </td>
                <td>
                  <span
                    :class="
                      supplier.is_active == 1 ? 'badge bg-success' : 'badge bg-secondary'
                    "
                    style="font-size: 0.8rem; padding: 0.4rem 0.8rem"
                  >
                    {{ supplier.is_active == 1 ? "Active" : "Inactive" }}
                  </span>
                </td>
                <td class="text-center">
                  <div class="d-flex justify-content-center gap-2">
                    <!-- <b-button
                      @click="viewSupplier(supplier)"
                      size="sm"
                      variant="info"
                      class="btn-icon"
                      v-b-tooltip.hover
                      title="View Details"
                      style="border-radius: 8px"
                    >
                      <i class="ri-eye-line"></i>
                    </b-button> -->
                    <b-button
                      @click="editSupplier(supplier)"
                      size="sm"
                      variant="primary"
                      class="btn-icon"
                      v-b-tooltip.hover
                      title="Edit Supplier"
                      style="border-radius: 8px"
                    >
                      <i class="ri-edit-line"></i>
                    </b-button>
                    <b-button
                      @click="toggleStatus(supplier)"
                      size="sm"
                      :variant="
                        supplier.is_active == 1 ? 'danger' : 'success'
                      "
                      class="btn-icon"
                      v-b-tooltip.hover
                      :title="supplier.is_active == 1 ? 'Deactivate' : 'Activate'"
                      style="border-radius: 8px"
                    >
                      <i
                        :class="
                          supplier.is_active == 1
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
        </div>

        <!-- Empty State -->
        <div v-if="lists.length === 0" class="text-center py-5">
          <div class="empty-state">
            <i class="ri-building-line fs-1 text-muted mb-3"></i>
            <h5 class="text-muted">No Suppliers Found</h5>
            <p class="text-muted mb-4">Get started by adding your first supplier</p>
            <b-button
              variant="primary"
              @click="openSupplier()"
              style="border-radius: 10px"
            >
              <i class="ri-add-circle-fill me-2"></i>Add Supplier
            </b-button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Supplier Details Modal -->
  <b-modal
    v-model="showDetailsModal"
    size="xl"
    title="Supplier Details"
    header-class="bg-gradient-primary text-white"
    body-class="p-0"
    modal-class="zoomIn"
    centered
    no-close-on-backdrop
  >
    <div v-if="selectedSupplier">
      <!-- Tabs -->
      <b-tabs content-class="p-4" nav-class="border-bottom-0">
        <!-- Overview Tab -->
        <b-tab title="Overview" active>
          <div class="supplier-overview">
            <b-row class="g-4">
              <b-col lg="8">
                <div class="card border-0 shadow-sm" style="border-radius: 12px">
                  <div class="card-body p-4">
                    <h5 class="card-title mb-4">
                      <i class="ri-building-line me-2 text-primary"></i
                      >{{ selectedSupplier.name }}
                    </h5>
                    <div class="row g-3">
                      <div class="col-md-6">
                        <div class="info-item">
                          <label class="text-muted small fw-bold">SUPPLIER CODE</label>
                          <p class="mb-0">{{ selectedSupplier.code }}</p>
                        </div>
                      </div>
                      <div class="col-md-6">
                        <div class="info-item">
                          <label class="text-muted small fw-bold">STATUS</label>
                          <p class="mb-0">
                            <span
                              :class="
                                selectedSupplier.is_active == 1
                                  ? 'badge bg-success'
                                  : 'badge bg-secondary'
                              "
                            >
                              {{
                                selectedSupplier.is_active == 1 ? "Active" : "Inactive"
                              }}
                            </span>
                          </p>
                        </div>
                      </div>
                      <div class="col-12">
                        <div class="info-item">
                          <label class="text-muted small fw-bold">ADDRESS</label>
                          <p class="mb-0">
                            {{ selectedSupplier.address || "No address provided" }}
                          </p>
                        </div>
                      </div>
                      <div class="col-12">
                        <div class="info-item">
                          <label class="text-muted small fw-bold">CREATED</label>
                          <p class="mb-0">
                            {{ formatDate(selectedSupplier.created_at) }}
                          </p>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </b-col>
              <b-col lg="4">
                <div class="card border-0 shadow-sm" style="border-radius: 12px">
                  <div class="card-body p-4 text-center">
                    <div class="supplier-avatar-large mb-3">
                      <i class="ri-building-line fs-1 text-primary"></i>
                    </div>
                    <h6 class="text-muted mb-2">Quick Stats</h6>
                    <div class="stats-grid">
                      <div class="stat-item">
                        <div class="stat-number">
                          {{
                            selectedSupplier.conformes
                              ? selectedSupplier.conformes.length
                              : 0
                          }}
                        </div>
                        <div class="stat-label">Representatives</div>
                      </div>
                      <div class="stat-item">
                        <div class="stat-number">
                          {{
                            selectedSupplier.attachments
                              ? selectedSupplier.attachments.length
                              : 0
                          }}
                        </div>
                        <div class="stat-label">Documents</div>
                      </div>
                    </div>
                  </div>
                </div>
              </b-col>
            </b-row>
          </div>
        </b-tab>

        <!-- Representatives Tab -->
        <b-tab title="Representatives">
          <div class="representatives-section">
            <div class="d-flex justify-content-between align-items-center mb-4">
              <h5 class="mb-0">
                <i class="ri-user-line me-2 text-success"></i>Authorized Representatives
              </h5>
              <b-button
                variant="primary"
                size="sm"
                @click="editSupplier(selectedSupplier)"
                style="border-radius: 8px"
              >
                <i class="ri-edit-line me-1"></i>Edit Representatives
              </b-button>
            </div>

            <div
              v-if="selectedSupplier.conformes && selectedSupplier.conformes.length > 0"
              class="representatives-list"
            >
              <div class="row g-3">
                <div
                  class="col-md-6"
                  v-for="conforme in selectedSupplier.conformes"
                  :key="conforme.id"
                >
                  <div class="card border-0 shadow-sm" style="border-radius: 12px">
                    <div class="card-body p-3">
                      <div class="d-flex align-items-center">
                        <div class="rep-avatar me-3">
                          <i class="ri-user-line fs-4 text-success"></i>
                        </div>
                        <div class="flex-fill">
                          <h6 class="mb-1 fw-bold">{{ conforme.name }}</h6>
                          <p class="mb-0 small text-muted">
                            {{ conforme.position || "No position specified" }}
                          </p>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div v-else class="text-center py-4">
              <i class="ri-user-line fs-1 text-muted mb-3"></i>
              <h6 class="text-muted">No Representatives Added</h6>
              <p class="text-muted small">Add representatives to this supplier</p>
            </div>
          </div>
        </b-tab>

        <!-- Attachments Tab -->
        <b-tab title="Attachments">
          <div class="attachments-section">
            <div class="d-flex justify-content-between align-items-center mb-4">
              <h5 class="mb-0">
                <i class="ri-attachment-line me-2 text-warning"></i>Documents & Files
              </h5>
              <b-button
                variant="primary"
                size="sm"
                @click="editSupplier(selectedSupplier)"
                style="border-radius: 8px"
              >
                <i class="ri-upload-line me-1"></i>Upload Documents
              </b-button>
            </div>

            <div
              v-if="
                selectedSupplier.attachments && selectedSupplier.attachments.length > 0
              "
              class="attachments-list"
            >
              <div class="row g-3">
                <div
                  class="col-md-6"
                  v-for="attachment in selectedSupplier.attachments"
                  :key="attachment.id"
                >
                  <div class="card border-0 shadow-sm" style="border-radius: 12px">
                    <div class="card-body p-3">
                      <div class="d-flex align-items-center">
                        <div class="file-icon me-3">
                          <i class="ri-file-line fs-3 text-warning"></i>
                        </div>
                        <div class="flex-fill">
                          <h6 class="mb-1 fw-bold">{{ attachment.name }}</h6>
                          <p class="mb-0 small text-muted">{{ attachment.path }}</p>
                          <small class="text-muted">{{
                            formatDate(attachment.created_at)
                          }}</small>
                        </div>
                        <div class="file-actions">
                          <b-button
                            variant="link"
                            size="sm"
                            class="text-primary p-0 me-2"
                            v-b-tooltip.hover
                            title="Download"
                          >
                            <i class="ri-download-line"></i>
                          </b-button>
                          <b-button
                            variant="link"
                            size="sm"
                            class="text-danger p-0"
                            v-b-tooltip.hover
                            title="Delete"
                          >
                            <i class="ri-delete-bin-line"></i>
                          </b-button>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div v-else class="text-center py-4">
              <i class="ri-attachment-line fs-1 text-muted mb-3"></i>
              <h6 class="text-muted">No Documents Uploaded</h6>
              <p class="text-muted small">
                Upload business permits, licenses, and other documents
              </p>
            </div>
          </div>
        </b-tab>
      </b-tabs>
    </div>

    <template v-slot:footer>
      <div class="d-flex justify-content-end gap-2">
        <b-button @click="closeDetailsModal" variant="light" style="border-radius: 8px">
          <i class="ri-close-line me-1"></i>Close
        </b-button>
        <b-button
          @click="editSupplier(selectedSupplier)"
          variant="primary"
          style="border-radius: 8px"
        >
          <i class="ri-edit-line me-1"></i>Edit Supplier
        </b-button>
      </div>
    </template>
  </b-modal>

  <!-- Pagination -->
  <div class="d-flex justify-content-center mt-4" v-if="meta && lists.length > 0">
    <Pagination @fetch="fetch" :lists="lists.length" :links="links" :pagination="meta" />
  </div>

  <SupplierModal :dropdowns="dropdowns" @add="fetch()" @update="fetch()" ref="create" />

  <DeactivateModal
    :supplier="selectedSupplierForStatus"
    @cancel="cancelStatusChange"
    @status-changed="onStatusChanged"
    @close="closeDeactivateModal"
  />
</template>
<script>
import _ from "lodash";
import PageHeader from "@/Shared/Components/PageHeader.vue";
import Pagination from "@/Shared/Components/Pagination.vue";
import SupplierModal from "@/Pages/Modules/FAIMS/Procurement/Modals/Supplier.vue";
import DeactivateModal from "@/Pages/Modules/FAIMS/Procurement/Suppliers/Modals/Deactivate.vue";
import Multiselect from "@vueform/multiselect";

export default {
  props: ["dropdowns"],
  components: { SupplierModal, DeactivateModal, Pagination, PageHeader, Multiselect },
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
      mode_of_procurements: {},
      index: null,
      showDetailsModal: false,
      selectedSupplier: null,
      selectedSupplierForStatus: null,
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
      page_url = page_url || "/faims/suppliers";
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

    openSupplier() {
      this.$refs.create.show();
    },

    editSupplier(data) {
      this.$refs.create.edit(data);
    },

    refresh() {
      this.filter.status = null;
      this.filter.keyword = null;
      this.fetch();
    },

    toggleStatus(supplier) {
      this.selectedSupplierForStatus = supplier;
    },

    cancelStatusChange() {
      this.selectedSupplierForStatus = null;
    },

    onStatusChanged(updatedSupplier) {
      // Update the supplier in the list
      const index = this.lists.findIndex(s => s.id === updatedSupplier.id);
      if (index !== -1) {
        this.lists[index] = updatedSupplier;
      }
      this.selectedSupplierForStatus = null;
    },

    closeDeactivateModal() {
      this.selectedSupplierForStatus = null;
    },

    formatDate(dateString) {
      const date = new Date(dateString);
      return date.toLocaleDateString("en-US", {
        year: "numeric",
        month: "short",
        day: "numeric",
      });
    },
  },
};
</script>
<style scoped>
.search-box {
  position: relative;
}

.search-box input {
  padding-left: 45px;
}

.search-icon {
  position: absolute;
  left: 15px;
  top: 50%;
  transform: translateY(-50%);
  color: #6c757d;
}

.suppliers-table-container {
  border-radius: 15px;
  overflow: hidden;
}

.supplier-row {
  transition: all 0.2s ease;
}

.supplier-row:hover {
  background-color: rgba(0, 123, 255, 0.05);
}

.supplier-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: linear-gradient(135deg, #007bff, #0056b3);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
}

.btn-icon {
  width: 35px;
  height: 35px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.empty-state {
  padding: 3rem;
}

/* Modal Styles */
.supplier-avatar-large {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  background: linear-gradient(135deg, #007bff, #0056b3);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  margin: 0 auto;
}

.stats-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
  margin-top: 1rem;
}

.stat-item {
  text-align: center;
  padding: 1rem;
  background: rgba(0, 123, 255, 0.1);
  border-radius: 10px;
}

.stat-number {
  font-size: 2rem;
  font-weight: bold;
  color: #007bff;
  display: block;
  margin-bottom: 0.25rem;
}

.stat-label {
  font-size: 0.8rem;
  color: #6c757d;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.info-item {
  margin-bottom: 1rem;
}

.info-item label {
  display: block;
  margin-bottom: 0.25rem;
}

.rep-avatar {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  background: linear-gradient(135deg, #28a745, #20c997);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
}

.file-icon {
  width: 50px;
  height: 50px;
  border-radius: 10px;
  background: linear-gradient(135deg, #ffc107, #fd7e14);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
}

.file-actions {
  opacity: 0;
  transition: opacity 0.2s ease;
}

.attachments-list .card:hover .file-actions {
  opacity: 1;
}
</style>
