<template>
  <div class="procurement-create-container">
    <!-- Hero Header Section -->
    <div class="hero-header mb-3">
      <div class="hero-gradient">
        <div class="container-fluid">
          <div class="row align-items-center">
            <div class="col-lg-8">
              <div class="hero-content">
                <div class="d-flex align-items-center mb-2">
                  <div class="hero-icon-wrapper me-3">
                    <i class="ri-file-2-line hero-icon"></i>
                  </div>
                  <div>
                    <h1 class="hero-title mb-1">
                      {{ option === 'create' ? 'Create Procurement Request' :
                         option === 'edit' ? 'Edit Procurement Request' :
                         option === 'review' ? 'Review Procurement Request' :
                         option === 'approve' ? 'Approve Procurement Request' :
                         'View Procurement Request' }}
                    </h1>
                    <p class="hero-subtitle mb-0">Manage procurement request details and items</p>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-lg-4 text-end">
              <div class="status-display">
                <div class="status-badge-wrapper">
                  <b-badge
                    :class="getStatusBadgeClass()"
                    style="font-size: 1rem; padding: 0.5rem 1rem;"
                  >
                    <i :class="getStatusIcon()" class="me-2"></i>
                    {{ getStatusText() }}
                  </b-badge>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="container-fluid">
      <div class="row d-flex">
        <div :class="[
          'transition-all',
          option == 'create' ? 'col-md-12' : (isRightCollapsed ? 'col-md-11' : 'col-md-9'),
        ]" style="transition: all 0.3s ease; height: 100%; overflow: hidden">
        </div>
        </div>
        </div>
        

    <!-- Main Content -->
    <div class="container-fluid">
      <div class="row d-flex">
        <div :class="[
          'transition-all',
          option == 'create' ? 'col-md-12' : (isRightCollapsed ? 'col-md-11' : 'col-md-9'),
        ]" style="transition: all 0.3s ease; height: 100%; overflow: hidden; ">

          <div class="content-wrapper">
            <form class="customform">
              <!-- Basic Information Section -->
              <div class="row g-3 mb-4">
                <div class="col-lg-6">
                  <div class="content-card">
                    <div class="card-body-custom">
                      <div class="row g-3">
                        <div class="col-md-6">
                          <div class="form-group">
                            <InputLabel for="division" value="Division" :message="form.errors.division_id" />
                            <Multiselect
                              :options="dropdowns.divisions"
                              v-model="form.division_id"
                              :searchable="true"
                              label="name"
                              placeholder="Select Division"
                              class="modern-select"
                            />
                          </div>
                        </div>

                        <div class="col-md-6">
                          <div class="form-group">
                            <InputLabel value="PR Date" :message="form.errors.date" />
                            <TextInput
                              v-model="form.date"
                              type="date"
                              class="form-control modern-input"
                              :light="true"
                            />
                          </div>
                        </div>

                        <div class="col-md-6">
                          <div class="form-group">
                            <InputLabel for="unit" value="Unit" :message="form.errors.unit_id" />
                            <Multiselect
                              :options="units"
                              v-model="form.unit_id"
                              :searchable="true"
                              label="name"
                              placeholder="Select Unit"
                              class="modern-select"
                            />
                          </div>
                        </div>

                        <div class="col-md-6">
                          <div class="form-group">
                            <InputLabel for="fund_cluster" value="Fund Cluster" :message="form.errors.fund_cluster_id" />
                            <Multiselect
                              :options="dropdowns.fund_clusters"
                              v-model="form.fund_cluster_id"
                              :searchable="true"
                              label="name"
                              placeholder="Select Fund Cluster"
                              class="modern-select"
                            />
                          </div>
                        </div>

                        <div class="col-12">
                          <div class="form-group">
                            <InputLabel value="PAP Code" :message="form.errors.procurement_code_ids" />
                            <Multiselect
                              :options="dropdowns.procurement_codes"
                              v-model="form.procurement_code_ids"
                              :searchable="true"
                              label="code"
                              placeholder="Select PAP Codes"
                              mode="tags"
                              class="modern-select"
                            />
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="col-lg-6">
                  <div class="content-card">
                    <div class="card-body-custom">
                      <div class="row g-3">
                        <div class="col-12">
                          <div class="form-group">
                            <InputLabel for="purpose" value="Request Purpose" :message="form.errors.purpose" />
                            <b-form-textarea
                              id="purpose"
                              v-model="form.purpose"
                              placeholder="Enter your request purpose"
                              rows="4"
                              max-rows="8"
                              class="modern-textarea"
                            ></b-form-textarea>
                          </div>
                        </div>

                        <div class="col-12" v-if="option == 'review' || option == 'approve'">
                          <div class="form-group">
                            <InputLabel for="title" value="Request Title" :message="form.errors.title" />
                            <b-form-textarea
                              id="title"
                              v-model="form.title"
                              placeholder="Enter request title"
                              rows="2"
                              max-rows="4"
                              class="modern-textarea"
                            ></b-form-textarea>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Items Section -->
              <div class="row g-3 mb-4">
                <div class="col-12">
                  <div class="content-card">
                    <div class="card-header-custom">
                      <i class="ri-shopping-bag-line card-header-icon"></i>
                      <h5 class="card-header-title">Procurement Items</h5>
                      <div class="ms-auto">
                        <b-button
                          v-if="option == 'create' || option == 'edit'"
                          :disabled="!form.division_id || !form.unit_id || !form.fund_cluster_id || !form.purpose"
                          @click="openAddItem()"
                          variant="primary"
                          size="sm"
                          class="add-item-btn"
                        >
                          <i class="ri-add-line me-1"></i>
                          Add Item
                        </b-button>
                      </div>
                    </div>
                    <div class="card-body-custom">
                      <div v-if="form.items && form.items.length > 0" class="items-table-container">
                        <div class="table-responsive">
                          <table class="items-table">
                            <thead>
                              <tr>
                                <th class="text-center">#</th>
                                <th>Unit</th>
                                <th>Description</th>
                                <th class="text-center">Qty</th>
                                <th class="text-end">Unit Cost</th>
                                <th class="text-end">Total</th>
                                <th class="text-center"></th>
                              </tr>
                            </thead>
                            <tbody>
                              <tr v-for="(item, index) in form.items" :key="index" class="item-row">
                                <td class="text-center item-number">{{ index + 1 }}</td>
                                <td class="item-unit">
                                  <span class="unit-badge">
                                    {{
                                      item.item_quantity > 1
                                        ? item.item_unit_type?.[0]?.name_long || item.item_unit_type?.name_long || ""
                                        : item.item_unit_type?.[0]?.name_short || item.item_unit_type?.name_short || ""
                                    }}
                                  </span>
                                </td>
                                <td class="item-description">
                                  <div v-html="item.item_description"></div>
                                </td>
                                <td class="text-center item-quantity">{{ item.item_quantity }}</td>
                                <td class="text-end item-cost">{{ formatCurrency(item.item_unit_cost) }}</td>
                                <td class="text-end item-total">{{ formatCurrency(item.total_cost) }}</td>
                                <td class="text-center">
                                  <div class="d-flex justify-content-center gap-1">
                                    <b-button
                                      v-if="option == 'create' || option == 'edit'"
                                      @click="editItem(index)"
                                      variant="success"
                                      size="sm"
                                      class="btn-icon"
                                      v-b-tooltip.hover title="Edit Item"
                                      style="border-radius: 8px;"
                                    >
                                      <i class="ri-edit-2-line"></i>
                                    </b-button>

                                      <b-button
                                        v-if="option == 'create' || option == 'edit'"
                                      @click="removeItem(index)"
                                      variant="danger"
                                      size="sm"
                                      class="btn-icon"
                                      v-b-tooltip.hover title="Remove Item"
                                      style="border-radius: 8px;"
                                    >
                                      <i class="ri-delete-bin-line"></i>
                                    </b-button>
                                  </div>
                                </td>
                              </tr>
                            </tbody>
                            <tfoot>
                              <tr class="grand-total-row">
                                <td colspan="5" class="text-end grand-total-label">Grand Total:</td>
                                <td class="text-end grand-total-amount">{{ formatCurrency(totalCostSum) }}</td>
                                <td></td>
                              </tr>
                            </tfoot>
                          </table>
                        </div>
                      </div>
                      <div v-else class="empty-state">
                        <div class="empty-state-icon">
                          <i class="ri-shopping-bag-line"></i>
                        </div>
                        <h6 class="empty-state-title">No Items Added</h6>
                        <p class="empty-state-text">Click "Add Item" to start adding procurement items.</p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>



              <!-- Assignees Section -->
              <div class="row g-3 mb-4">
                <div class="col-12">
                  <div class="content-card">
                    <div class="card-header-custom">
                      <i class="ri-user-line card-header-icon"></i>
                      <h5 class="card-header-title">Assignees</h5>
                    </div>
                    <div class="card-body-custom">
                      <div class="row g-3">
                        <div class="col-md-6">
                          <div class="form-group">
                            <InputLabel for="requested_by" value="Requested By" :message="form.errors.requested_by_id" />
                            <Multiselect
                              :options="dropdowns.requesters"
                              v-model="form.requested_by_id"
                              :searchable="true"
                              label="name"
                              placeholder="Select Requester"
                              class="modern-select"
                            />
                          </div>
                        </div>
                        <div class="col-md-6">
                          <div class="form-group">
                            <InputLabel for="approved_by" value="Approved By" :message="form.errors.approved_by_id" />
                            <Multiselect
                              :options="dropdowns.approvers"
                              v-model="form.approved_by_id"
                              :searchable="true"
                              label="name"
                              placeholder="Select Approver"
                              class="modern-select"
                            />
                          </div>
                        </div>

                         <div class="action-buttons-group">
                      <b-button
                        v-if="option == 'create'"
                        :disabled="!isFormValid"
                        @click="submit()"
                        variant="primary"
                        size="sm"
                        class="action-btn primary-btn"
                      >
                        <i class="ri-save-line me-2"></i>
                        Save Request
                      </b-button>

                      <b-button
                        v-if="option == 'edit'"
                        @click="update(form)"
                        variant="primary"
                        size="sm"
                        class="action-btn primary-btn"
                      >
                        <i class="ri-edit-line me-2"></i>
                        Update Request
                      </b-button>

                      <b-button
                        v-if="option == 'review'"
                        @click="review(form)"
                        variant="success"
                        size="sm"
                        class="action-btn success-btn"
                      >
                        <i class="ri-check-line me-2"></i>
                        Confirm Review
                      </b-button>

                      <b-button
                        v-if="option == 'approve'"
                        @click="approve(form)"
                        variant="success"
                        size="sm"
                        class="action-btn success-btn"
                      >
                        <i class="ri-check-double-line me-2"></i>
                        Approve Request
                      </b-button>

                      <b-button
                        @click="goBackPage()"
                        variant="outline-secondary"
                        size="sm"
                        class="action-btn back-btn"
                      >
                        <i class="ri-arrow-left-line me-2"></i>
                        Back to List
                      </b-button>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

            
            </form>
          </div>
        </div>

        <RightSidebar
          v-if="option != 'create'"
          :procurement="procurement"
          :logs="logs"
          :isRightCollapsed="isRightCollapsed"
          @toggleRightSidebar="toggleRightSidebar"
        />
      </div>
    </div>

    <Item :dropdowns="dropdowns" @refresh="getDataFromLocalStorage()" ref="item" />
  </div>
</template>
<script>
import Item from "./Modals/Item.vue";
import PageHeader from "@/Shared/Components/PageHeader.vue";
import { useForm } from "@inertiajs/vue3";
import Multiselect from "@vueform/multiselect";
import InputError from "@/Shared/Components/Forms/InputError.vue";
import InputLabel from "@/Shared/Components/Forms/InputLabel.vue";
import TextInput from "@/Shared/Components/Forms/TextInput.vue";
import { router } from "@inertiajs/vue3";
import RightSidebar from "./Pages/Components/RightSidebar.vue";

export default {
  components: { PageHeader, InputError, InputLabel, TextInput, Multiselect, Item, RightSidebar },
  props: ["procurement", "dropdowns", "option", "regional_director"],
  data() {
    return {
      currentUrl: window.location.origin,
      form: useForm({
        id: null,
        code: null,
        purpose: null,
        title: null,
        date: this.getCurrentDate(),
        division_id: null,
        unit_id: null,
        fund_cluster_id: null,
        items: null,
        requested_by_id: null,
        approved_by_id: null,
        procurement_code_ids: [],
        option: null,
      }),
      action: null,
      showModal: false,
      units: [],
      isRightCollapsed: false,
      isCollapsed: false,
    };
  },

  watch: {
    "form.division_id"(newVal) {
      if (newVal) {
        this.getUnits(newVal);
      }
    },

    "form.procurement_code_ids": function (value) {
      if (this.action == "create") {
        if (Array.isArray(value) && value.length > 0) {
          // Reset the title before adding new ones
          this.form.title = "";

          value.forEach((id) => {
            this.getProcurementTitle(id);
          });
        }
      }
    },

    action: function (value) {
      if (value == "edit" || value == "review" || value == "approve" || value == "view") {
        this.form.id = this.procurement.id;
        this.form.code = this.procurement.code;
        this.form.purpose = this.procurement.purpose;
        this.form.title = this.procurement.title;
        this.form.date = this.procurement.date;
        this.form.division_id = this.procurement.division_id;
        this.form.unit_id = this.procurement.unit_id;
        this.form.fund_cluster_id = this.procurement.fund_cluster_id;
        this.form.procurement_code_ids = this.procurement.codes.map(
          (code) => code.procurement_code_id
        );
        this.form.requested_by_id = this.procurement.requested_by_id;
        this.form.approved_by_id = this.procurement.approved_by_id;
        this.form.items = this.procurement.items;
        // this.getDataFromLocalStorage(); // update items
      }
    },
  },

  computed: {
    totalCostSum() {
      if (!Array.isArray(this.form.items)) return 0;

      return this.form.items.reduce((sum, item) => {
        return sum + (parseFloat(item.total_cost) || 0);
      }, 0);
    },

    isFormValid() {
      return this.form.division_id &&
             this.form.unit_id &&
             this.form.fund_cluster_id &&
             this.form.purpose &&
             this.form.requested_by_id &&
             this.form.approved_by_id &&
             this.form.items &&
             this.form.items.length > 0;
    },
  },

  mounted() {
    // Load from localStorage on component mount
    this.getDataFromLocalStorage();

    this.action = this.option;
    if (this.option === 'create' && this.dropdowns.regional_director) {
      this.form.approved_by_id = this.dropdowns.regional_director.value;
    }
    try {
      this.isRightCollapsed = JSON.parse(localStorage.getItem("isRightCollapsed")) ?? true;
    } catch (e) {
      this.isRightCollapsed = true;
      localStorage.setItem("isRightCollapsed", JSON.stringify(true));
    }
  },
  

  methods: {

    getStatusBadgeClass() {
      if (this.option === 'create') return 'bg-primary';
      if (this.option === 'edit') return 'bg-warning';
      if (this.option === 'review') return 'bg-info';
      if (this.option === 'approve') return 'bg-success';
      return 'bg-secondary';
    },

    getStatusIcon() {
      if (this.option === 'create') return 'ri-add-circle-line';
      if (this.option === 'edit') return 'ri-edit-line';
      if (this.option === 'review') return 'ri-eye-line';
      if (this.option === 'approve') return 'ri-check-double-line';
      return 'ri-file-line';
    },

    getStatusText() {
      if (this.option === 'create') return 'Creating';
      if (this.option === 'edit') return 'Editing';
      if (this.option === 'review') return 'Reviewing';
      if (this.option === 'approve') return 'Approving';
      return 'Viewing';
    },

    toggleRightSidebar() {
      this.isRightCollapsed = !this.isRightCollapsed;
      localStorage.setItem("isRightCollapsed", this.isRightCollapsed);
    },

    openAddItem() {
      this.$refs.item.show();
    },

    editItem(index) {
      this.$refs.item.edit(this.form.items[index], index);
    },

    removeItem(index) {
      // Get the current items
      let items = JSON.parse(localStorage.getItem("itemsAdded")) || [];

      // Remove 1 item at that index
      if (index >= 0 && index < items.length) {
        items.splice(index, 1);
      }

      // Save the updated array back to localStorage
      localStorage.setItem("itemsAdded", JSON.stringify(items));

      // Update your form items immediately
      this.form.items = items;
    },

    submit() {
      this.form.post("/faims/procurements", {
        onSuccess: () => {
          localStorage.removeItem("itemsAdded");
        },
        onError: (errors) => {
          console.error("Submission failed:", errors);
        },
      });
    },

    update(data) {
      this.form.option = this.action;
      this.form.put(`/faims/procurements/${data.id}`, {
        onSuccess: () => {
          localStorage.removeItem("itemsAdded");
          this.form.reset();
        },
        onError: (errors) => {
          console.error("Update failed:", errors);
        },
      });
    },

    review(data) {
      this.form.option = this.action;
      this.form.put("/faims/procurements/" + data.id);
      this.form.reset();
    },

    approve(data) {
      this.form.option = this.action;
      this.form.put("/faims/procurements/" + data.id);
      this.form.reset();
    },

    goBackPage() {
      router.get("/faims/procurements");
    },

    getDataFromLocalStorage() {
      // Get existing items from localStorage
      let storedItems = [];
      try {
        storedItems = JSON.parse(localStorage.getItem("itemsAdded")) || [];
      } catch (e) {
        storedItems = [];
        localStorage.setItem("itemsAdded", JSON.stringify([]));
      }

      // If form.items is not set yet, initialize it
      if (!Array.isArray(this.form.items)) {
        this.form.items = [];
      }

      // Merge locally stored ones with DB (form.items), giving priority to stored items
      const combined = [...storedItems, ...this.form.items];

      // Remove duplicates based on item id
      const uniqueItems = combined.filter(
        (item, index, self) => index === self.findIndex((t) => t.id === item.id)
      );

      // Update both localStorage and the form
      this.form.items = uniqueItems;
      localStorage.setItem("itemsAdded", JSON.stringify(uniqueItems));
    },

    getCurrentDate() {
      const today = new Date();
      const year = today.getFullYear();
      const month = String(today.getMonth() + 1).padStart(2, "0"); // Months are zero-based
      const day = String(today.getDate()).padStart(2, "0");
      return `${year}-${month}-${day}`;
    },

    formatCurrency(value) {
      return new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
      }).format(value);
    },

    getUnits(division_id) {
      axios
        .get("/faims/procurements/create", {
          params: {
            code: division_id,
            option: "units",
          },
        })
        .then((response) => {
          if (response) {
            this.units = response.data;
          }
        })
        .catch((err) => console.log(err));
    },

    getProcurementTitle(id) {
      axios
        .get("/faims/procurements/create", {
          params: {
            id: id,
            option: "title",
          },
        })
        .then((response) => {
          if (response) {
            if (this.form.title) {
              this.form.title += ", " + response.data;
            } else {
              this.form.title = response.data;
            }
          }
        })
        .catch((err) => console.log(err));
    },
  },
};
</script>

<style scoped>
/* Container */
.procurement-create-container {
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  min-height: 100vh;
  padding: 0 0 2rem 0;
}

/* Hero Header */
.hero-header {
  position: relative;
  overflow: hidden;
  border-radius: 20px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
  margin-bottom: 2rem;
}

.hero-gradient {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 0.5rem 0;
  position: relative;
}

.hero-gradient::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="75" cy="75" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="50" cy="10" r="0.5" fill="rgba(255,255,255,0.1)"/><circle cx="90" cy="40" r="0.5" fill="rgba(255,255,255,0.1)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
  opacity: 0.3;
}

.hero-icon-wrapper {
  width: 80px;
  height: 80px;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  backdrop-filter: blur(10px);
  border: 2px solid rgba(255, 255, 255, 0.3);
}

.hero-icon {
  font-size: 2.5rem;
  color: white;
}

.hero-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: white;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
  margin-bottom: 0.5rem;
}

.hero-subtitle {
  font-size: 1rem;
  color: rgba(255, 255, 255, 0.9);
  font-weight: 400;
}

.status-badge-main {
  border-radius: 25px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
}

/* Content Wrapper */
.content-wrapper {
  background: white;
  border-radius: 15px;
  padding: 2rem;
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
  margin-bottom: 2rem;
}

/* Content Cards */
.content-card {
  background: white;
  border-radius: 15px;
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
  border: 1px solid rgba(255, 255, 255, 0.8);
  overflow: visible;
  transition: all 0.3s ease;
}

.content-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 15px 35px rgba(0, 0, 0, 0.12);
}

.card-header-custom {
  background: linear-gradient(135deg, #f8f9fa, #e9ecef);
  padding: 1rem;
  border-bottom: 1px solid rgba(0, 0, 0, 0.05);
  display: flex;
  align-items: center;
  gap: 1rem;
}

.card-header-icon {
  font-size: 1.5rem;
  color: #667eea;
  background: rgba(102, 126, 234, 0.1);
  padding: 0.5rem;
  border-radius: 10px;
}

.card-header-title {
  font-size: 1.25rem;
  color: #2c3e50;
  margin: 0;
}

.card-body-custom {
  padding: 1.5rem;
  overflow: visible;
}

/* Form Groups */
.form-group {
  margin-bottom: 1.5rem;
}

/* Modern Select */
.modern-select {
  border-radius: 8px;
  border: 1px solid #ced4da;
  transition: all 0.3s ease;
}

.modern-select:focus {
  border-color: #667eea;
  box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

/* Modern Input */
.modern-input {
  border-radius: 8px;
  border: 1px solid #ced4da;
  padding: 0.5rem 1rem;
  transition: all 0.3s ease;
}

.modern-input:focus {
  border-color: #667eea;
  box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

/* Modern Textarea */
.modern-textarea {
  border-radius: 8px;
  border: 1px solid #ced4da;
  resize: vertical;
  transition: all 0.3s ease;
}

.modern-textarea:focus {
  border-color: #667eea;
  box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

/* Add Item Button */
.add-item-btn {
  border-radius: 25px;
  padding: 0.5rem 1.5rem;
  font-weight: 600;
  transition: all 0.3s ease;
}

.add-item-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 15px rgba(0, 123, 255, 0.3);
}

/* Items Table */
.items-table-container {
  border-radius: 10px;
  overflow: hidden;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
}

.items-table {
  width: 100%;
  border-collapse: collapse;
  background: white;
}

.items-table thead {
  background: linear-gradient(135deg, #667eea, #764ba2);
  color: white;
}

.items-table th {
  padding: 1rem;
  font-weight: 600;
  font-size: 0.9rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.items-table tbody tr {
  transition: all 0.3s ease;
}

.items-table td {
  padding: 1rem;
  border-bottom: 1px solid rgba(0, 0, 0, 0.05);
  vertical-align: top;
}

.item-number {
  font-weight: 600;
  color: #667eea;
}

.unit-badge {
  background: linear-gradient(135deg, #e9ecef, #dee2e6);
  color: #495057;
  padding: 0.25rem 0.75rem;
  border-radius: 15px;
  font-size: 0.8rem;
  font-weight: 500;
}

.item-description {
  font-weight: 500;
  color: #2c3e50;
  max-width: 300px;
}

.item-quantity, .item-unit {
  font-weight: 600;
  color: #495057;
}

.item-cost, .item-total {
  font-weight: 700;
  color: #28a745;
  font-family: 'Courier New', monospace;
}

.grand-total-row {
  background: linear-gradient(135deg, #f8f9fa, #e9ecef);
  border-top: 2px solid #667eea;
}

.grand-total-label {
  font-weight: 700;
  color: #2c3e50;
  font-size: 0.9rem;
}

.grand-total-amount {
  font-weight: 700;
  color: #28a745;
  font-size: 1rem;
  font-family: 'Courier New', monospace;
}

/* Action Buttons */
.action-buttons {
  display: flex;
  gap: 0.5rem;
  justify-content: center;
}

.action-btn {
  border-radius: 8px;
  padding: 0.4rem 0.8rem;
  transition: all 0.3s ease;
}



/* Empty State */
.empty-state {
  text-align: center;
  padding: 3rem 1rem;
}

.empty-state-icon {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  background: linear-gradient(135deg, #f8f9fa, #e9ecef);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2rem;
  color: #6c757d;
  margin: 0 auto 1rem;
}

.empty-state-title {
  font-size: 1.25rem;
  font-weight: 600;
  color: #2c3e50;
  margin-bottom: 0.5rem;
}

.empty-state-text {
  color: #6c757d;
  font-size: 0.9rem;
}

/* Action Buttons Container */
.action-buttons-container {
  background: linear-gradient(135deg, #f8f9fa, #e9ecef);
  border-radius: 15px;
  padding: 2rem;
}

.action-buttons-group {
  display: flex;
  gap: 1rem;
  justify-content: center;
  flex-wrap: wrap;
}

.action-btn {
  border-radius: 25px;
  padding: 0.75rem 2rem;
  font-weight: 600;
  font-size: 1rem;
  transition: all 0.3s ease;
  min-width: 200px;
}

.primary-btn {
  background: linear-gradient(135deg, #667eea, #764ba2);
  border: none;
  color: white;
}

.primary-btn:hover {
  transform: translateY(-3px);
  box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
}

.success-btn {
  background: linear-gradient(135deg, #28a745, #20c997);
  border: none;
  color: white;
}

.success-btn:hover {
  transform: translateY(-3px);
  box-shadow: 0 8px 25px rgba(40, 167, 69, 0.4);
}

.back-btn {
  border: 2px solid #6c757d;
  color: #6c757d;
  background: transparent;
}

.back-btn:hover {
  background: #6c757d;
  color: white;
  transform: translateY(-3px);
  box-shadow: 0 8px 25px rgba(108, 117, 125, 0.4);
}

/* Responsive Design */
@media (max-width: 768px) {
  .hero-title {
    font-size: 1.75rem;
  }

  .hero-subtitle {
    font-size: 0.9rem;
  }

  .hero-icon-wrapper {
    width: 60px;
    height: 60px;
  }

  .hero-icon {
    font-size: 2rem;
  }

.content-wrapper {
  padding: 1rem;
  overflow: visible;
}

  .card-body-custom {
    padding: 1rem;
  }

  .action-buttons-group {
    flex-direction: column;
    align-items: stretch;
  }

  .action-btn {
    min-width: auto;
    padding: 0.4rem 1rem;
    font-size: 0.85rem;
  }

  .items-table th,
  .items-table td {
    padding: 0.75rem 0.5rem;
  }

  .item-description {
    max-width: 200px;
  }
}

@media (max-width: 576px) {
  .hero-gradient {
    padding: 1.5rem 0;
  }

  .hero-title {
    font-size: 1.5rem;
  }

  .status-display {
    text-align: center;
    margin-top: 1rem;
  }

  .content-card {
    margin-bottom: 1rem;
  }

  .action-buttons-container {
    padding: 1rem;
  }
}
</style>
