<template>
  <div class="procurement-detail-container">
    <!-- Hero Header Section -->
    <div class="hero-header mb-2">
      <div class="hero-gradient">
        <div class="container-fluid">
          <div class="row align-items-center">
            <div class="col-lg-8">
              <div class="hero-content">
                <div class="d-flex align-items-center mb-2">
                  <div class="hero-icon-wrapper me-3">
                    <i class="ri-information-line hero-icon"></i>
                  </div>
                  <div>
                    <h1 class="hero-title mb-1">Procurement Details</h1>
                    <p class="hero-subtitle mb-0">
                      Comprehensive overview of procurement information
                    </p>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-lg-4 text-end">
              <div class="status-display">
                <div class="status-badge-wrapper">
                  <b-badge
                    :class="procurement.status.bg + ' status-badge-main'"
                    style="font-size: 1rem; padding: 0.5rem 1rem"
                  >
                    <i :class="statusIcons[procurement.status.id] + ' me-2'"></i>
                    {{ procurement.status.name }}
                  </b-badge>
                  <div v-if="procurement.sub_status" class="mt-2">
                    <b-badge
                      :class="procurement.sub_status.bg + ' status-badge-sub'"
                      style="font-size: 0.8rem; padding: 0.3rem 0.8rem"
                    >
                      {{ procurement.sub_status.name }}
                    </b-badge>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="container-fluid">
      <!-- Basic Information Cards Row -->
      <div class="row g-4 mb-4">
        <div class="col-lg-3 col-md-6">
          <div class="info-card">
            <div class="card-icon">
              <i class="ri-hashtag"></i>
            </div>
            <div class="card-content">
              <h6 class="card-title">PR Number</h6>
              <p class="card-value">{{ procurement.code }}</p>
            </div>
          </div>
        </div>
        <div class="col-lg-3 col-md-6">
          <div class="info-card">
            <div class="card-icon">
              <i class="ri-calendar-line"></i>
            </div>
            <div class="card-content">
              <h6 class="card-title">PR Date</h6>
              <p class="card-value">{{ procurement.date }}</p>
            </div>
          </div>
        </div>
        <div class="col-lg-3 col-md-6">
          <div class="info-card">
            <div class="card-icon">
              <i class="ri-building-line"></i>
            </div>
            <div class="card-content">
              <h6 class="card-title">Division</h6>
              <p class="card-value">{{ procurement.division?.name }}</p>
            </div>
          </div>
        </div>
        <div class="col-lg-3 col-md-6">
          <div class="info-card">
            <div class="card-icon">
              <i class="ri-group-line"></i>
            </div>
            <div class="card-content">
              <h6 class="card-title">Unit</h6>
              <p class="card-value">{{ procurement.unit?.name }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Statistics and Personnel Row -->
      <div class="row g-4 mb-4">
        <div class="col-lg-6">
          <div class="content-card">
            <div class="card-header-custom">
              <i class="ri-bar-chart-line card-header-icon"></i>
              <h5 class="card-header-title">Statistics</h5>
            </div>
            <div class="card-body-custom">
              <div class="stat-grid">
                <div class="stat-item">
                  <div class="stat-icon">
                    <i class="ri-file-text-line"></i>
                  </div>
                  <div class="stat-content">
                    <span class="stat-number">{{ procurement.quotation_count }}</span>
                    <span class="stat-label">Quotations</span>
                  </div>
                </div>
                <div class="stat-item">
                  <div class="stat-icon">
                    <i class="ri-refresh-line"></i>
                  </div>
                  <div class="stat-content">
                    <span class="stat-number">{{ procurement.reawarded_count }}</span>
                    <span class="stat-label">Reawarded</span>
                  </div>
                </div>
                <div class="stat-item">
                  <div class="stat-icon">
                    <i class="ri-loop-left-line"></i>
                  </div>
                  <div class="stat-content">
                    <span class="stat-number">{{ procurement.rebidded_count }}</span>
                    <span class="stat-label">Rebid</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-lg-6">
          <div class="content-card">
            <div class="card-header-custom">
              <i class="ri-user-line card-header-icon"></i>
              <h5 class="card-header-title">Personnel</h5>
            </div>
            <div class="card-body-custom">
              <div class="personnel-list">
                <div class="personnel-item">
                  <div class="personnel-avatar">
                    <i class="ri-user-add-line"></i>
                  </div>
                  <div class="personnel-info">
                    <span class="personnel-role">Created By</span>
                    <span class="personnel-name">{{
                      procurement.created_by.profile.fullname
                    }}</span>
                  </div>
                </div>
                <div class="personnel-item">
                  <div class="personnel-avatar">
                    <i class="ri-user-shared-line"></i>
                  </div>
                  <div class="personnel-info">
                    <span class="personnel-role">Requested By</span>
                    <span class="personnel-name">{{
                      procurement.requested_by.profile.fullname
                    }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- PAP Codes Section -->
      <div class="row g-2 mb-3">
        <div class="col-12">
          <div class="content-card">
            <div class="card-header-custom">
              <i class="ri-price-tag-3-line card-header-icon"></i>
              <h5 class="card-header-title">PAP Codes</h5>
            </div>
            <div class="card-body-custom">
              <div class="pap-codes-list">
                <div
                  v-for="code in procurement.codes"
                  :key="code.id"
                  class="pap-code-item"
                >
                  <b-badge
                    variant="primary"
                    :class="procurement.status.bg + ' pap-badge'"
                  >
                    {{ code.procurement_code?.title }}
                  </b-badge>
                </div>
                <div
                  v-if="!procurement.codes || procurement.codes.length === 0"
                  class="text-center text-muted py-2"
                >
                  <i class="ri-price-tag-3-line fs-2 mb-1"></i>
                  <p class="mb-0">No PAP codes assigned</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Procurement Items Section -->
      <div class="row g-2">
        <div class="col-12">
          <div class="content-card">
            <div class="card-header-custom">
              <i class="ri-shopping-bag-line card-header-icon"></i>
              <h5 class="card-header-title">Procurement Items</h5>
            </div>
            <div class="card-body-custom">
              <div
                v-if="procurement.items && procurement.items.length > 0"
                class="items-table-container"
              >
                <div class="table-responsive">
                  <table class="items-table">
                    <thead>
                      <tr>
                        <th class="text-center">#</th>
                        <th class="text-center">Status</th>
                        <th>Description</th>
                        <th class="text-center">Qty</th>
                        <th class="text-center">Unit</th>
                        <th class="text-end">Unit Cost</th>
                        <th class="text-end">Total</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr
                        v-for="(item, index) in procurement.items"
                        :key="item.id"
                        class="item-row"
                      >
                        <td class="text-center item-number">{{ index + 1 }}</td>
                        <td class="text-center">
                          <b-badge
                            :class="item.status?.bg"
                            class="fs-11"
                            v-if="item.status"
                          >
                            {{ item.status?.name }}
                          </b-badge>
                          <span v-else class="text-muted">-</span>
                        </td>
                        <td class="item-description">
                          <span v-html="item.item_description"></span>
                        </td>
                        <td class="text-center item-quantity">
                          {{ item.item_quantity }}
                        </td>
                        <td class="text-center item-unit">
                          {{
                            item.item_quantity > 1
                              ? item.item_unit_type?.name_long
                              : item.item_unit_type?.name_short || "N/A"
                          }}
                        </td>
                        <td class="text-end item-cost">
                          ₱{{
                            Number(item.item_unit_cost).toLocaleString("en-US", {
                              minimumFractionDigits: 2,
                              maximumFractionDigits: 2,
                            })
                          }}
                        </td>
                        <td class="text-end item-total">
                          ₱{{
                            Number(item.total_cost).toLocaleString("en-US", {
                              minimumFractionDigits: 2,
                              maximumFractionDigits: 2,
                            })
                          }}
                        </td>
                      </tr>
                    </tbody>
                    <tfoot>
                      <tr class="grand-total-row">
                        <td colspan="6" class="text-end grand-total-label">
                          Grand Total:
                        </td>
                        <td class="text-end grand-total-amount">
                          ₱{{ calculateGrandTotal() }}
                        </td>
                      </tr>
                    </tfoot>
                  </table>
                </div>
              </div>
              <div v-else class="empty-state">
                <div class="empty-state-icon">
                  <i class="ri-shopping-bag-line"></i>
                </div>
                <h6 class="empty-state-title">No Items Found</h6>
                <p class="empty-state-text">
                  This procurement has no items assigned yet.
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
<script>
export default {
  props: ["dropdowns", "procurement"],
  data() {
    return {
      statusOrder: [36, 37, 38, 39, 40, 41, 42, 43, 44, 45, 46, 49, 51, 52, 53], // Main statuses, partial statuses made optional/situational
      statusLabels: {
        36: "Pending",
        37: "Reviewed",
        38: "Approved",
        39: "Available of Award",
        40: "Available for Re-award",
        41: "Not Available for Award/Re-award",
        42: "For Bids",
        43: "Awarded",
        44: "For BAC Resolution",
        45: "For Approval of BAC Resolution",
        46: "For NOA",
        47: "Served to Supplier",
        48: "NOA Served to Supplier",
        49: "PO Issued",
        50: "Partially NOA Conformed",
        51: "PO Conformed",
        52: "Delivered/For Inspection",
        53: "Completed",
        54: "Conformed",
        55: "Not Conformed",
        56: "NOA Conformed",
        57: "Partially Awarded",
        58: "Partially NOA Conformed",
        59: "Re-award",
        60: "Rebid",
        61: "Not Conformed",
        62: "PO Pending",
        63: "Partially PO Pending",
        64: "PO Partially Issued",
        65: "PO Partially Conformed",
        66: "Partially Delivered/For Inspection",
        67: "Partially Completed/Awaiting for Inspection",
        68: "Not Conformed",
      },
      statusIcons: {
        36: "ri-time-line",
        37: "ri-eye-line",
        38: "ri-check-circle-line",
        39: "ri-trophy-line",
        40: "ri-trophy-line",
        41: "ri-close-circle-line",
        42: "ri-file-text-line",
        43: "ri-auction-line",
        44: "ri-file-line",
        45: "ri-file-line",
        46: "ri-trophy-line",
        47: "ri-send-plane-line",
        48: "ri-send-plane-line",
        49: "ri-shopping-cart-line",
        50: "ri-check-line",
        51: "ri-check-line",
        52: "ri-truck-line",
        53: "ri-check-line",
        54: "ri-check-line",
        55: "ri-close-line",
        56: "ri-check-line",
        57: "ri-auction-line",
        58: "ri-check-line",
        59: "ri-trophy-line",
        60: "ri-refresh-line",
        61: "ri-close-line",
        62: "ri-time-line",
        63: "ri-time-line",
        64: "ri-shopping-cart-line",
        65: "ri-check-line",
        66: "ri-truck-line",
        67: "ri-check-line",
        68: "ri-close-line",
      },
    };
  },
  methods: {
    calculateGrandTotal() {
      if (!this.procurement.items || this.procurement.items.length === 0) {
        return "0.00";
      }
      const total = this.procurement.items.reduce((sum, item) => {
        return sum + (parseFloat(item.total_cost) || 0);
      }, 0);
      return total.toLocaleString("en-US", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      });
    },
  },
};
</script>

<style scoped>
/* Container */
.procurement-detail-container {
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  min-height: 100vh;
  padding: 0 0 1rem 0;
}

/* Hero Header */
.hero-header {
  position: relative;
  overflow: hidden;
  border-radius: 20px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
}

.hero-gradient {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 1.5rem 0;
  position: relative;
}

.hero-gradient::before {
  content: "";
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
  font-size: 1.8rem;
  font-weight: 700;
  color: white;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
  margin-bottom: 0.5rem;
}

.hero-subtitle {
  font-size: 0.9rem;
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

.status-badge-sub {
  border-radius: 15px;
  font-weight: 500;
  opacity: 0.9;
}

/* Info Cards */
.info-card {
  background: white;
  border-radius: 15px;
  padding: 1rem;
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
  border: 1px solid rgba(255, 255, 255, 0.8);
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
  min-height: 120px;
  display: flex;
  flex-direction: column;
}

.info-card::before {
  content: "";
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

.card-icon {
  width: 50px;
  height: 50px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  color: white;
  margin-bottom: 1rem;
  background: linear-gradient(135deg, #667eea, #764ba2);
  box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}

.card-title {
  font-size: 0.75rem;
  font-weight: 600;
  color: #6c757d;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 0.25rem;
}

.card-value {
  font-size: 1rem;
  font-weight: 700;
  color: #2c3e50;
  margin: 0;
}

/* Content Cards */
.content-card {
  background: white;
  border-radius: 15px;
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
  border: 1px solid rgba(255, 255, 255, 0.8);
  overflow: hidden;
  transition: all 0.3s ease;
}

.content-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 15px 35px rgba(0, 0, 0, 0.12);
}

.card-header-custom {
  background: linear-gradient(135deg, #f8f9fa, #e9ecef);
  padding: 1.5rem;
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
  font-weight: 600;
  color: #2c3e50;
  margin: 0;
}

.card-body-custom {
  padding: 1rem;
}

/* Statistics Grid */
.stat-grid {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.stat-item {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  background: linear-gradient(135deg, #f8f9fa, #e9ecef);
  border-radius: 10px;
  transition: all 0.3s ease;
}

.stat-item:hover {
  background: linear-gradient(135deg, #e9ecef, #dee2e6);
  transform: scale(1.02);
}

.stat-icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.2rem;
  color: white;
  background: linear-gradient(135deg, #667eea, #764ba2);
}

.stat-content {
  flex: 1;
}

.stat-number {
  display: block;
  font-size: 1.2rem;
  font-weight: 700;
  color: #2c3e50;
  line-height: 1;
}

.stat-label {
  font-size: 0.8rem;
  color: #6c757d;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

/* Personnel List */
.personnel-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.personnel-item {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  background: linear-gradient(135deg, #f8f9fa, #e9ecef);
  border-radius: 10px;
  transition: all 0.3s ease;
}

.personnel-item:hover {
  background: linear-gradient(135deg, #e9ecef, #dee2e6);
  transform: scale(1.01);
}

.personnel-avatar {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.2rem;
  color: white;
  background: linear-gradient(135deg, #667eea, #764ba2);
}

.personnel-info {
  flex: 1;
}

.personnel-role {
  display: block;
  font-size: 0.8rem;
  color: #6c757d;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 0.25rem;
}

.personnel-name {
  font-size: 1rem;
  font-weight: 600;
  color: #2c3e50;
}

/* PAP Codes */
.pap-codes-list {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.pap-code-item {
  margin-bottom: 0.5rem;
}

.pap-badge {
  font-size: 0.8rem;
  padding: 0.4rem 0.8rem;
  border-radius: 20px;
  font-weight: 500;
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

.item-quantity,
.item-unit {
  font-weight: 600;
  color: #495057;
}

.item-cost,
.item-total {
  font-weight: 700;
  color: #28a745;
  font-family: "Courier New", monospace;
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
  font-family: "Courier New", monospace;
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

/* Responsive Design */
@media (max-width: 768px) {
  .hero-title {
    font-size: 2rem;
  }

  .hero-subtitle {
    font-size: 1rem;
  }

  .hero-icon-wrapper {
    width: 60px;
    height: 60px;
  }

  .hero-icon {
    font-size: 2rem;
  }

  .info-card {
    padding: 1rem;
  }

  .card-icon {
    width: 40px;
    height: 40px;
    font-size: 1.2rem;
  }

  .stat-grid {
    grid-template-columns: 1fr;
  }

  .personnel-list {
    gap: 0.75rem;
  }

  .personnel-item {
    padding: 0.75rem;
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
    padding: 2rem 0;
  }

  .hero-title {
    font-size: 1.75rem;
  }

  .status-display {
    text-align: center;
    margin-top: 1rem;
  }

  .info-card {
    margin-bottom: 1rem;
  }

  .content-card {
    margin-bottom: 1.5rem;
  }
}
</style>
