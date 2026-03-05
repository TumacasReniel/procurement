<template>
  <!-- Page Header -->
  <PageHeader title="Purchase Order" class="m-3 mt-4" />

  <!-- Enhanced Action Buttons -->
  <div class="d-flex justify-content-between align-items-center mb-4 mt-3">
    <div class="d-flex gap-2">
      <b-button
        type="button"
        variant="outline-primary"
        @click="goBackPage()"
        class="btn-modern shadow-sm"
        size="sm"
        v-b-tooltip.hover
        title="Back"
      >
        <i class="ri-arrow-left-line align-bottom me-1"></i>
        Back
      </b-button>

      <b-button
        @click="createPO()"
        v-if="!purchase_order"
        variant="success"
        class="btn-modern shadow-sm"
        size="sm"
        v-b-tooltip.hover
        title="Create Purchase Order"
      >
        <i class="ri-add-fill align-bottom me-1"></i>
        Create PO
      </b-button>

      <b-button
        v-if="(purchase_order && purchase_order.status.name != 'Not Conformed') && purchase_order.status.name != 'Completed' && purchase_order.status.name != 'Delivered/For Inspection'"
        variant="outline-success"
        class="btn-modern shadow-sm"
        size="sm"
        v-b-tooltip.hover
        title="Edit Purchase Order"
        @click="editPO()"
      >
        <i class="ri-pencil-fill align-bottom me-1"></i>
        Edit PO
      </b-button>

      <b-button
        variant="outline-info"
        class="btn-modern shadow-sm"
        size="sm"
        v-b-tooltip.hover
        title="Update Status"
        v-if="
          (((purchase_order && purchase_order.status.name != 'Delivered/For Inspection') ) && (purchase_order.status.name != 'Completed' && purchase_order.status.name != 'Not Conformed') )
        "
        @click="updateStatus(purchase_order)"
      >
        <i class="ri-edit-fill align-bottom me-1"></i>
        Update Status
      </b-button>

      <b-button
        variant="outline-danger"
        class="btn-modern shadow-sm"
        size="sm"
        v-if="purchase_order && purchase_order.status.name == 'Served to Supplier'"
        @click="notConformed(purchase_order)"
        v-b-tooltip.hover
        title="Not Conformed"
      >
        <i class="ri-close-circle-fill align-bottom me-1"></i>
        Not Conformed
      </b-button>

      <b-button
        variant="outline-success"
        class="btn-modern shadow-sm"
        size="sm"
        v-if="purchase_order && (purchase_order.status.name == 'Conformed' || purchase_order.status.name == 'Delivered/For Inspection' || purchase_order.status.name == 'Completed')"
        @click="printNTP(purchase_order)"
        v-b-tooltip.hover
        title="Notice to Proceed"
      >
        <i class="ri-file-fill align-bottom me-1"></i>
        Notice to Proceed
      </b-button>

      <b-button
        variant="outline-dark"
        class="btn-modern shadow-sm"
        v-if="purchase_order"
        @click="printPO(purchase_order)"
        size="sm"
        v-b-tooltip.hover
        title="Print Purchase Order"
      >
        <i class="ri-printer-fill align-bottom me-1"></i>
        Print PO
      </b-button>
    </div>
  </div>

  <!-- Enhanced Main Content -->
  <div class="main-content-wrapper">
    <b-card class="modern-card shadow-lg border-0">
      <!-- Purchase Order Content -->
      <div
        v-if="purchase_order"
        class="po-content p-4"
        style="height: calc(90vh - 180px); overflow: auto"
        ref="box"
      >
        <!-- Enhanced PO Header Information -->
        <div class="po-header-section mb-4">
          <b-row class="g-4">
        
            <b-col md="6">
              <div class="info-card p-3 rounded-3 bg-light-subtle border">
                <h6 class="text-success mb-3 fw-bold">
                  <i class="ri-shopping-cart-line me-2"></i>
                  Purchase Order Details
                </h6>
                <div class="info-item mb-2">
                  <span class="text-muted small">PO No:</span>
                  <div class="fw-bold text-success fs-6">{{ purchase_order.code }}</div>
                </div>
                <div class="info-item mb-2">
                  <span class="text-muted small">PO Date:</span>
                  <div class="fw-semibold">{{ purchase_order.po_date }}</div>
                </div>
                <div class="info-item mb-2">
                  <span class="text-muted small">Mode of Procurement:</span>
                  <div class="fw-semibold" v-for="code in procurement.codes" :key="code.id">
                    {{ code.procurement_code.mode_of_procurement.name }}
                  </div>
                </div>
                <div class="info-item">
                  <span class="text-muted small">Status:</span>
                  <div>
                    <b-badge :class="purchase_order.status.bg" class="fs-6 px-3 py-1">
                      {{ purchase_order.status?.name }}
                    </b-badge>
                  </div>
                </div>
              </div>
            </b-col>
               <b-col md="6">
              <div class="info-card p-3 rounded-3 bg-light-subtle border">
                <h6 class="text-primary mb-3 fw-bold">
                  <i class="ri-file-list-3-line me-2"></i>
                  Notice of Award Details
                </h6>
                <div class="info-item mb-2">
                  <span class="text-muted small">NOA No:</span>
                  <div class="fw-bold text-info fs-6">{{ noa.code }}</div>
                </div>
                <div class="info-item mb-2">
                  <span class="text-muted small">Supplier:</span>
                  <div class="fw-semibold">{{ noa.procurement_quotation.supplier.name }}</div>
                </div>
                <div class="info-item mb-2">
                  <span class="text-muted small">Address:</span>
                  <div class="fw-semibold">{{ noa.procurement_quotation.supplier.address?.address }}</div>
                </div>
                <div class="info-item">
                  <span class="text-muted small">TIN:</span>
                  <div class="fw-semibold">{{ noa.procurement_quotation.supplier.tin }}</div>
                </div>
              </div>
            </b-col>
          </b-row>
        </div>

        <!-- Procurement Items Table (Same as Detail.vue) -->
        <div class="items-table-container">
          <div class="table-responsive">
            <table class="items-table">
              <thead>
                <tr>
                  <th class="text-center">#</th>
                  <th>Description</th>
                  <th class="text-center">Qty</th>
                  <th class="text-center">Unit</th>
                  <th class="text-end">Unit Cost</th>
                  <th class="text-end">Total</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(item, index) in noa.items" :key="index" class="item-row">
                  <td class="text-center item-number">{{ index + 1 }}</td>
                  <td class="item-description">
                    <span v-html="item.item.item.item_description"></span>
                  </td>
                  <td class="text-center item-quantity">{{ item.item.item.item_quantity }}</td>
                  <td class="text-center item-unit">
                    {{
                      item.item.item.item_quantity > 1
                        ? item.item.item.item_unit_type.name_long
                        : item.item.item.item_unit_type.name_short
                    }}
                  </td>
                  <td class="text-end item-cost">{{ formatCurrency(item.item.bid_price) }}</td>
                  <td class="text-end item-total">
                    {{ formatCurrency(item.item.bid_price * item.item.item.item_quantity) }}
                  </td>
                </tr>
              </tbody>
              <tfoot>
                <tr class="grand-total-row">
                  <td colspan="5" class="text-end grand-total-label">Grand Total:</td>
                  <td class="text-end grand-total-amount">{{ formatCurrency(totalAmount) }}</td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>
      </div>

      <!-- Enhanced Empty State -->
      <div
        v-else
        class="empty-state-container d-flex justify-content-center align-items-center"
        style="height: calc(90vh - 180px)"
      >
        <div class="text-center empty-state-content">
          <div class="empty-state-icon mb-4">
            <i class="ri-shopping-cart-line display-1 text-muted"></i>
          </div>
          <h4 class="text-muted mb-3 fw-bold">No Purchase Order Found</h4>
          <p class="text-muted mb-4 fs-6">
            You haven't created a Purchase Order yet. Click the button below to get started.
          </p>

        </div>
      </div>
    </b-card>
  </div>

  <!-- Update Status Modal -->
  <PurchaseOrder
    :procurement="procurement"
    :noa="noa"
    :dropdowns="dropdowns"
    @add="fetch()"
    ref="create"
  />
  <UpdateStatus :procurement="procurement" @add="fetch()" ref="updateStatus" />
</template>

<script>
import _ from "lodash";
import PageHeader from "@/Shared/Components/PageHeader.vue";
import Pagination from "@/Shared/Components/Pagination.vue";
import UpdateStatus from "../Modals/UpdateStatus.vue";
import { router } from "@inertiajs/vue3";
import PurchaseOrder from "../Modals/PurchaseOrder.vue";

export default {
  props: ["noa", "procurement", "dropdowns"],
  components: { PageHeader, Pagination, UpdateStatus, PurchaseOrder },
  data() {
    return {
      currentUrl: window.location.origin,
      meta: {},
      links: {},
      index: null,
      purchase_order: null,
      selectedRows: [],
      selectAll: false,
    };
  },
  created() {
    this.fetch();
  },

  computed: {
    totalAmount() {
      return this.noa.items.reduce((sum, item) => {
        return sum + item.item.bid_price * item.item.item.item_quantity;
      }, 0);
    },
  },

  methods: {
    checkSearchStr: _.debounce(function (string) {
      this.fetch();
    }, 300),
    fetch(page_url) {
      page_url = "/faims/purchase-orders";
      axios
        .get(page_url, {
          params: {
            option: "purchase_order",
            noa_id: this.noa.id,
          },
        })
        .then((response) => {
          if (response) {
            this.purchase_order = response.data;
          }
        })
        .catch((err) => console.log(err));
    },

    formatCurrency(value) {
      return new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
      }).format(value);
    },

    createPO() {
      this.$refs.create.show();
    },

    editPO() {
      if (this.purchase_order) {
        this.$refs.create.show(this.purchase_order);
      }
    },
    updateStatus(data) {
      this.$refs.updateStatus.show(data, "PO");
    },
    notConformed(data) {
      this.$refs.updateStatus.show(data, "PO Not Conformed");
    },
    printPO(data) {
      window.open(`/faims/purchase-orders/${data.id}?option=print&type=purchase_order`);
    },
    printNTP(data) {
      window.open(
        `/faims/purchase-orders/${data.id}?option=print&type=notice_to_proceed`
      );
    },
    goBackPage() {
      router.get(`/faims/procurements/${this.procurement.id}`, {
        option: "view",
        tab: 5,
      });
    },

    // Row Selection Methods
    toggleRowSelection(itemId) {
      const index = this.selectedRows.indexOf(itemId);
      if (index > -1) {
        this.selectedRows.splice(index, 1);
      } else {
        this.selectedRows.push(itemId);
      }
      this.updateSelectAllState();
    },

    toggleSelectAll() {
      if (this.selectAll) {
        this.selectedRows = [];
      } else {
        this.selectedRows = this.noa.items.map((item, index) => index);
      }
      this.selectAll = !this.selectAll;
    },

    updateSelectAllState() {
      this.selectAll = this.selectedRows.length === this.noa.items.length && this.noa.items.length > 0;
    },

    isRowSelected(itemId) {
      return this.selectedRows.includes(itemId);
    },

    getSelectedItemsCount() {
      return this.selectedRows.length;
    },

    clearSelection() {
      this.selectedRows = [];
      this.selectAll = false;
    },
  },
};
</script>

<style scoped>
/* Modern Button Styles */
.btn-modern {
  border-radius: 8px;
  font-weight: 500;
  transition: all 0.3s ease;
  border: none;
}

.btn-modern:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15) !important;
}

/* Main Content Wrapper */
.main-content-wrapper {
  min-height: calc(100vh - 200px);
}

/* Modern Card */
.modern-card {
  border-radius: 15px;
  background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
}

/* PO Content */
.po-content {
  background: linear-gradient(135deg, rgba(255, 255, 255, 0.9) 0%, rgba(248, 249, 250, 0.9) 100%);
  border-radius: 12px;
  backdrop-filter: blur(10px);
}

/* PO Header Section */
.po-header-section {
  margin-bottom: 2rem;
}

/* Info Cards */
.info-card {
  background: white;
  border-radius: 15px;
  padding: 1.5rem;
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
  border: 1px solid rgba(255, 255, 255, 0.8);
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
  min-height: 140px;
  display: flex;
  flex-direction: column;
}

.info-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: linear-gradient(90deg, #667eea, #764ba2);
}

.info-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 15px 35px rgba(0, 0, 0, 0.12);
}

.info-item {
  margin-bottom: 0.75rem;
}

.info-item:last-child {
  margin-bottom: 0;
}

/* Items Table (Same as Detail.vue) */
.items-table-container {
  border-radius: 10px;
  overflow: hidden;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
}

/* Selection Actions */
.selection-actions {
  background: linear-gradient(135deg, #f8f9fa, #e9ecef);
  border-radius: 10px;
  padding: 1rem;
  border: 1px solid rgba(102, 126, 234, 0.1);
}

.selection-info .badge {
  font-weight: 600;
  letter-spacing: 0.5px;
}

.selection-buttons .btn {
  border-radius: 6px;
  font-weight: 500;
  transition: all 0.3s ease;
}

.selection-buttons .btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
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
  padding: 0.75rem;
  font-weight: 600;
  font-size: 0.9rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.items-table tbody tr {
  transition: all 0.3s ease;
}

.items-table tbody tr:hover {
  background: rgba(102, 126, 234, 0.05);
}

.items-table td {
  padding: 0.75rem;
  border-bottom: 1px solid rgba(0, 0, 0, 0.05);
  vertical-align: top;
}

.item-number {
  font-weight: 600;
  color: #667eea;
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

/* Empty State */
.empty-state-container {
  background: linear-gradient(135deg, rgba(255, 255, 255, 0.9) 0%, rgba(248, 249, 250, 0.9) 100%);
  border-radius: 12px;
  backdrop-filter: blur(10px);
}

.empty-state-content {
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
  margin: 0 auto 1.5rem;
}

/* Responsive Design */
@media (max-width: 768px) {
  .items-table th,
  .items-table td {
    padding: 0.75rem 0.5rem;
  }

  .item-description {
    max-width: 200px;
  }

  .info-card {
    margin-bottom: 1rem;
  }
}

@media (max-width: 576px) {
  .hero-gradient {
    padding: 2rem 0;
  }

  .info-card {
    padding: 1rem;
  }
}
</style>
