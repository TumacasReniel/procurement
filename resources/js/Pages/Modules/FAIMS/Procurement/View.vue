<template>
  <Head title="Profile" />
  <PageHeader title="Procurement Overview" pageTitle="User" />


  <div class="row">
    <div
      :class="['transition-all', isCollapsed ? 'col-md-1' : 'col-md-3']"
      style="transition: all 0.3s ease"
    >
      <div
        class="card shadow-lg border-0"
        style="
          background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
          border-radius: 15px;
          height: 100vh;
        "
      >
        <div
          class="card-header bg-gradient-primary border-0 d-flex align-items-center justify-content-between"
          style="border-radius: 15px 15px 0 0 !important; padding: 1rem"
        >
          <div v-if="!isCollapsed" class="text-center">
            <span class="card-title mb-1"
              ><i class="ri-file-list-3-line me-2"></i
              ><span class="fs-5 text-muted">PR#:</span>
              <span class="fw-bold">{{ procurement?.code }}</span>
            </span>
            <p class="card-title mb-0 fs-10">
              <span>Status:</span>
              <div>
              <b-badge
                :class="procurement.status.bg + ' ms-1'"
                style="font-size: 0.75rem; text-align: center"
                >{{ procurement.status?.name }}</b-badge
              >
              <b-badge
                v-if="isPartiallyCompleted"
                class="bg-warning text-dark ms-1"
                style="font-size: 0.65rem; text-align: center"
                v-b-tooltip.hover
                title="This procurement has partially completed items"
              >
                <i class="ri-information-line me-1"></i>Partial
              </b-badge>
              </div>
            </p>
            <p class="card-title mb-0 fs-10">
              <span v-if="procurement.sub_status">Substatus:</span>
             <div>
              <b-badge
                :class="procurement.sub_status?.bg + ' ms-1'"
                style="font-size: 0.75rem; text-align: center"
                >{{ procurement.sub_status?.name }}</b-badge
              >
             </div>
            </p>
          </div>
          <button
            @click="toggleSidebar"
            class="btn btn-sm btn-light rounded-circle p-2 ms-2"
            style="width: 40px; height: 40px"
          >
            <i
              :class="isCollapsed ? 'ri-arrow-right-line' : 'ri-arrow-left-line'"
              class="text-primary fs-6"
            ></i>
          </button>
        </div>
        <div
          v-if="!isCollapsed"
          class="card-body p-0"
          style="
            height: 100vh;
            overflow: auto;
            border-radius: 0 0 15px 15px;
          "
        >
          <div class="p-3">
            <h6 class="text-muted mb-3 fw-bold">Navigation</h6>
            <div class="nav flex-column">
              <button
                :class="[
                  'nav-link text-start mb-2 rounded-pill border-0 transition-all',
                  activeTab === 1
                    ? 'bg-primary text-white shadow-sm'
                    : 'bg-white text-dark hover-bg-light',
                ]"
                @click="show(1)"
                style="transition: all 0.3s ease"
              >
                <i class="ri-information-line align-middle me-3 fs-5"></i>Procurement
                Details
              </button>
              <button
                :class="[
                  'nav-link text-start mb-2 rounded-pill border-0 transition-all',
                  activeTab === 2
                    ? 'bg-primary text-white shadow-sm'
                    : 'bg-white text-dark hover-bg-light',
                ]"
                @click="show(2)"
                style="transition: all 0.3s ease"

              >
                <i class="ri-check-line text-success me-2" v-if="procurement.status?.name === 'Approved'"></i><i class="ri-file-text-line align-middle me-3 fs-5"></i>Request of
                Quotations(RFQs)

              </button>
              <button
                :class="[
                  'nav-link text-start mb-2 rounded-pill border-0 transition-all',
                  activeTab === 3
                    ? 'bg-primary text-white shadow-sm'
                    : 'bg-white text-dark hover-bg-light',
                ]"
                @click="show(3)"
                style="transition: all 0.3s ease"
              >
                <i class="ri-check-line text-success me-2" v-if="procurement.status?.name === 'For BAC Resolution'"></i><i class="ri-auction-line align-middle me-3 fs-5"></i>Abstract of
                Bids(AOBs)

              </button>
              <button
                :class="[
                  'nav-link text-start mb-2 rounded-pill border-0 transition-all',
                  activeTab === 4
                    ? 'bg-primary text-white shadow-sm'
                    : 'bg-white text-dark hover-bg-light',
                ]"
                @click="show(4)"
                style="transition: all 0.3s ease"
              >
                <i class="ri-check-line text-success me-2" v-if="procurement.status?.name === 'For Approval of BAC Resolution'"></i><i class="ri-file-line align-middle me-3 fs-5"></i>BAC Resolutions
              </button>
              <button
                :class="[
                  'nav-link text-start mb-2 rounded-pill border-0 transition-all',
                  activeTab === 5
                    ? 'bg-primary text-white shadow-sm'
                    : 'bg-white text-dark hover-bg-light',
                ]"
                @click="show(5)"
                style="transition: all 0.3s ease"
              >
                <i class="ri-check-line text-success me-2" v-if="procurement.status?.name === 'For NOA' || procurement.status?.name === 'NOA Served to Supplier' || procurement.status?.name === 'NOA Conformed'"></i><i class="ri-trophy-line align-middle me-3 fs-5"></i>Notice of Award(NOAs)
              </button>
              <button
                :class="[
                  'nav-link text-start mb-2 rounded-pill border-0 transition-all',
                  activeTab === 6
                    ? 'bg-primary text-white shadow-sm'
                    : 'bg-white text-dark hover-bg-light',
                ]"
                @click="show(6)"
                style="transition: all 0.3s ease"
  
              >
                <i class="ri-shopping-cart-line align-middle me-3 fs-5"></i>Purchase
                Order(POs)
              </button>
            </div>
          </div>
        </div>
        <div
          v-else
          class="card-body p-0"
          style="
            height: 100vh;
            overflow: auto;
            border-radius: 0 0 15px 15px;
          "
        >
          <div class="p-2 d-flex flex-column align-items-center">
            <button
              :class="[
                'nav-link mb-2 rounded-pill border-0 transition-all p-2',
                activeTab === 1
                  ? 'bg-primary text-white shadow-sm'
                  : 'bg-white text-dark hover-bg-light',
              ]"
              @click="show(1)"
              style="transition: all 0.3s ease; width: 50px; height: 50px"
              v-b-tooltip.hover
              title="Procurement Details"
            >
              <i class="ri-information-line fs-5"></i>
            </button>
            <button
              :class="[
                'nav-link mb-2 rounded-pill border-0 transition-all p-2',
                activeTab === 2
                  ? 'bg-primary text-white shadow-sm'
                  : 'bg-white text-dark hover-bg-light',
              ]"
              @click="show(2)"
              style="transition: all 0.3s ease; width: 50px; height: 50px; position: relative;"
              v-b-tooltip.hover
              title="Request of Quotations(RFQs)"
            >
              <i class="ri-file-text-line fs-5"></i>
              <span v-if="quotationsCount > 0" class="badge bg-danger" style="position: absolute; top: -5px; right: -5px; font-size: 0.6rem; padding: 0.1rem 0.2rem;">{{ quotationsCount }}</span>
            </button>
            <button
              :class="[
                'nav-link mb-2 rounded-pill border-0 transition-all p-2',
                activeTab === 3
                  ? 'bg-primary text-white shadow-sm'
                  : 'bg-white text-dark hover-bg-light',
              ]"
              @click="show(3)"
              style="transition: all 0.3s ease; width: 50px; height: 50px; position: relative;"
              v-b-tooltip.hover
              title="Abstract of Bids(AOBs)"
            >
              <i class="ri-auction-line fs-5"></i>
              <span v-if="bidsCount > 0" class="badge bg-danger" style="position: absolute; top: -5px; right: -5px; font-size: 0.6rem; padding: 0.1rem 0.2rem;">{{ bidsCount }}</span>
            </button>
            <button
              :class="[
                'nav-link mb-2 rounded-pill border-0 transition-all p-2',
                activeTab === 4
                  ? 'bg-primary text-white shadow-sm'
                  : 'bg-white text-dark hover-bg-light',
              ]"
              @click="show(4)"
              style="transition: all 0.3s ease; width: 50px; height: 50px; position: relative;"
              v-b-tooltip.hover
              title="BAC Resolutions"
            >
              <i class="ri-file-line fs-5"></i>
              <span v-if="bacResolutionsCount > 0" class="badge bg-danger" style="position: absolute; top: -5px; right: -5px; font-size: 0.6rem; padding: 0.1rem 0.2rem;">{{ bacResolutionsCount }}</span>
            </button>
            <button
              :class="[
                'nav-link mb-2 rounded-pill border-0 transition-all p-2',
                activeTab === 5
                  ? 'bg-primary text-white shadow-sm'
                  : 'bg-white text-dark hover-bg-light',
              ]"
              @click="show(5)"
              style="transition: all 0.3s ease; width: 50px; height: 50px; position: relative;"
              v-b-tooltip.hover
              title="Notice of Award(NOAs)"
            >
              <i class="ri-trophy-line fs-5"></i>
              <span v-if="noasCount > 0" class="badge bg-danger" style="position: absolute; top: -5px; right: -5px; font-size: 0.6rem; padding: 0.1rem 0.2rem;">{{ noasCount }}</span>
            </button>
            <button
              :class="[
                'nav-link mb-2 rounded-pill border-0 transition-all p-2',
                activeTab === 6
                  ? 'bg-primary text-white shadow-sm'
                  : 'bg-white text-dark hover-bg-light',
              ]"
              @click="show(6)"
              style="transition: all 0.3s ease; width: 50px; height: 50px; position: relative;"
              v-b-tooltip.hover
              title="Purchase Order(POs)"
            >
              <i class="ri-shopping-cart-line fs-5"></i>
              <span v-if="posCount > 0" class="badge bg-danger" style="position: absolute; top: -5px; right: -5px; font-size: 0.6rem; padding: 0.1rem 0.2rem;">{{ posCount }}</span>
            </button>
          </div>
        </div>
      </div>
    </div>
    <div
      :class="[
        'transition-all',
        isCollapsed && isRightCollapsed
          ? 'col-md-10'
          : isCollapsed || isRightCollapsed
          ? 'col-md-8'
          : 'col-md-6',
      ]"
      style="transition: all 0.3s ease; overflow-x: auto"
    >
      <div
        class="card mb-3 shadow-lg border-0"
        style="
          background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
          border-radius: 15px;
          height: 100vh;
        "
      >
        <div
          class="card-body p-0"
          style="
            height: 100vh;
            overflow: auto;
            border-radius: 0 0 15px 15px;
          "
        >
          <div class="p-3">
            <div v-if="activeTab === 1">
               <Overview :procurement="procurement" />
            </div>
            <Quotation
              :dropdowns="dropdowns"
              :procurement="procurement"
              v-if="activeTab === 2"
            />
            <AbstractOfBids
              :dropdowns="dropdowns"
              :procurement="procurement"
              v-if="activeTab === 3"
            />
            <BACResolution
              :dropdowns="dropdowns"
              :procurement="procurement"
              v-if="activeTab === 4"
            />
            <NoticeOfAward
              :dropdowns="dropdowns"
              :procurement="procurement"
              v-if="activeTab === 5 && !showCreatePOFlag"
              @changeTab="show"
              @showCreatePO="handleShowCreatePO"
            />
            <CreatePO
              :dropdowns="dropdowns"
              :procurement="procurement"
              :noa="selectedNoa"
              v-if="showCreatePOFlag"
            />
            <PurchaseOrder
              :dropdowns="dropdowns"
              :procurement="procurement"
              v-if="activeTab === 6"
            />
          </div>
        </div>
      </div>
    </div>
    <RightSidebar :procurement="procurement" :logs="logs" :isRightCollapsed="isRightCollapsed" @toggleRightSidebar="toggleRightSidebar" />
  </div>
</template>
<script>
import Overview from "./Pages/Detail.vue";
import Quotation from "./Pages/Quotation.vue";
import BACResolution from "./Pages/BACResolution.vue";
import AbstractOfBids from "./Pages/Bids.vue";
import NoticeOfAward from "./Pages/NoticeOfAward.vue";
import PurchaseOrder from "./Pages/PurchaseOrder.vue";
import CreatePO from "./Pages/CreatePO.vue";
import RightSidebar from "./Pages/Components/RightSidebar.vue";
import { router } from "@inertiajs/vue3";

import PageHeader from "@/Shared/Components/PageHeader.vue";
export default {
  components: {
    PageHeader,
    Overview,
    Quotation,
    BACResolution,
    AbstractOfBids,
    NoticeOfAward,
    PurchaseOrder,
    CreatePO,
    RightSidebar,
  },
  props: ["dropdowns", "procurement", "tab", "logs"],
  data() {
    return {
      activeTab: parseInt(this.tab) || 1,
      isCollapsed: false,
      isRightCollapsed: true,
      selectedNoa: null,
      showCreatePOFlag: false,
      showOverview: true,
    };
  },

  computed: {
    quotationsCount() {
      return this.procurement.quotations ? this.procurement.quotations.length : 0;
    },
    bidsCount() {
      return (this.procurement.bids ? this.procurement.bids.length : 0) + (this.procurement.quotations ? this.procurement.quotations.filter(quotation => quotation.status_id == 36).length : 0);
    },
    bacResolutionsCount() {
      return this.procurement.bac_resolutions ? this.procurement.bac_resolutions.length : 0;
    },
    noasCount() {
      return this.procurement.noas ? this.procurement.noas.length : 0;
    },
    posCount() {
      return this.procurement.pos ? this.procurement.pos.length : 0;
    },
    isPartiallyCompleted() {
      // Check if procurement has mixed statuses indicating partial completion
      if (!this.procurement.noas || this.procurement.noas.length === 0) return false;

      const statuses = this.procurement.noas.map(noa => noa.status?.name);
      const uniqueStatuses = [...new Set(statuses)];

      // If there are multiple different statuses, it's partially completed
      return uniqueStatuses.length > 1 && uniqueStatuses.some(status =>
        ['Completed', 'Delivered/For Inspection', 'PO Conformed', 'PO Issued', 'PO Pending'].includes(status)
      );
    },
  },
  watch: {
    tab() {
      this.activeTab = parseInt(this.tab) || 1;
      this.showCreatePOFlag = false; // Reset flag when tab changes
    },
  },
  mounted() {
    this.isRightCollapsed = localStorage.getItem("isRightCollapsed") === "true" || true;
  },
  methods: {
    show(tab) {
      this.activeTab = tab;
      localStorage.setItem("activeTab", tab);
      router.visit(
        "/faims/procurements/" + this.procurement.id + "?option=view&tab=" + tab,
        { replace: true, preserveState: true }
      );
    },

    toggleSidebar() {
      this.isCollapsed = !this.isCollapsed;
    },

    toggleRightSidebar() {
      this.isRightCollapsed = !this.isRightCollapsed;
      localStorage.setItem("isRightCollapsed", this.isRightCollapsed);
    },

    toggleOverview() {
      this.showOverview = !this.showOverview;
    },

    handleShowCreatePO(data) {
      this.selectedNoa = data;
      this.showCreatePOFlag = true;
    },
  },
};
</script>
<style scoped>
.status-flow {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
}

.status-step {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
  opacity: 0.5;
  transition: opacity 0.3s ease;
}

.status-step.active {
  opacity: 1;
}

.status-circle {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  background: linear-gradient(135deg, #007bff, #0056b3);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 1.2rem;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.status-step.active .status-circle {
  background: linear-gradient(135deg, #28a745, #1e7e34);
  box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
}

.status-label {
  font-size: 0.8rem;
  font-weight: 600;
  color: #495057;
  text-align: center;
  max-width: 80px;
}

.status-connector {
  width: 2px;
  height: 30px;
  background: #dee2e6;
  position: relative;
}

.status-connector::after {
  content: "";
  position: absolute;
  top: 0;
  left: -4px;
  width: 10px;
  height: 10px;
  background: #dee2e6;
  border-radius: 50%;
}

.status-step.active ~ .status-step .status-circle {
  background: #6c757d;
}

.status-step.active ~ .status-step .status-label {
  color: #6c757d;
}

.status-step.past {
  opacity: 1;
  animation: fadeInCheck 0.5s ease-in-out;
}

@keyframes fadeInCheck {
  0% {
    opacity: 0;
    transform: scale(0.8);
  }
  50% {
    opacity: 0.7;
    transform: scale(1.1);
  }
  100% {
    opacity: 1;
    transform: scale(1);
  }
}

.status-step.past .status-circle {
  background: linear-gradient(135deg, #28a745, #1e7e34);
  animation: pulseGreen 1s ease-in-out;
}

@keyframes pulseGreen {
  0% {
    box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
  }
  50% {
    box-shadow: 0 4px 20px rgba(40, 167, 69, 0.6);
  }
  100% {
    box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
  }
}

.current-badge {
  background: linear-gradient(135deg, #28a745, #1e7e34);
  color: white;
  font-size: 0.7rem;
  font-weight: bold;
  padding: 0.2rem 0.5rem;
  border-radius: 10px;
  margin-top: 0.25rem;
  box-shadow: 0 2px 4px rgba(40, 167, 69, 0.3);
  text-transform: uppercase;
  letter-spacing: 0.5px;
  animation: badgeGlow 1.5s ease-in-out infinite;
}

@keyframes badgeGlow {
  0%,
  100% {
    box-shadow: 0 2px 4px rgba(40, 167, 69, 0.3);
  }
  50% {
    box-shadow: 0 2px 8px rgba(40, 167, 69, 0.6);
  }
}

.log-item {
  background: #f8f9fa;
  border-left: 4px solid #007bff;
  transition: all 0.3s ease;
}

.log-item:hover {
  background: #e9ecef;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.log-description {
  color: #495057;
  font-size: 0.9rem;
}

.log-details {
  color: #6c757d;
}

.log-changes {
  background: #ffffff;
  padding: 0.5rem;
  border-radius: 4px;
  border: 1px solid #dee2e6;
}

.change-item {
  display: flex;
  justify-content: space-between;
  margin-bottom: 0.25rem;
}

.change-key {
  font-weight: 600;
  color: #495057;
}

.change-value {
  color: #007bff;
  font-family: monospace;
  font-size: 0.85rem;
}

.log-icon {
  margin-left: 1rem;
  opacity: 0.7;
}

.status-item {
  opacity: 0;
  transform: translateY(20px);
  animation: fadeInUp 0.6s ease-out forwards;
}

.current-status .status-badge {
  animation: pulseCurrent 2s ease-in-out infinite;
  box-shadow: 0 0 15px rgba(255, 193, 7, 0.5);
}

@keyframes fadeInUp {
  0% {
    opacity: 0;
    transform: translateY(20px);
  }
  100% {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes pulseCurrent {
  0%, 100% {
    transform: scale(1);
    box-shadow: 0 0 15px rgba(255, 193, 7, 0.5);
  }
  50% {
    transform: scale(1.05);
    box-shadow: 0 0 25px rgba(255, 193, 7, 0.8);
  }
}

.status-flow-container {
  overflow-x: auto;
  white-space: nowrap;
  padding: 1rem 0;
}

.status-flow-wrapper {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 0 1rem;
  min-width: max-content;
}

.status-step-modern {
  display: flex;
  align-items: center;
  gap: 1rem;
  opacity: 0;
  transform: translateY(20px);
  animation: slideInUp 0.6s ease-out forwards;
}

.status-step-modern.current-status {
  animation: slideInUp 0.6s ease-out forwards, pulseGlow 2s ease-in-out infinite;
}

.status-step-modern.past-status {
  opacity: 1;
  transform: translateY(0);
}

.status-step-modern.future-status {
  opacity: 0.6;
}

.status-card {
  background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
  border: 1px solid #e9ecef;
  border-radius: 8px;
  padding: 0.5rem;
  min-width: 100px;
  text-align: center;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
  overflow: hidden;
  cursor: pointer;
}

.status-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.12);
  border-color: #007bff;
}

.status-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: linear-gradient(90deg, #007bff, #6610f2);
  opacity: 0;
  transition: opacity 0.3s ease;
}

.status-step-modern.current-status .status-card::before {
  opacity: 1;
}

.status-step-modern.current-status .status-card {
  border-color: #007bff;
  box-shadow: 0 8px 25px rgba(0, 123, 255, 0.2);
  transform: translateY(-2px);
}

.status-step-modern.past-status .status-card {
  background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
  border-color: #28a745;
  box-shadow: 0 4px 15px rgba(40, 167, 69, 0.15);
}

.status-step-modern.future-status .status-card {
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  border-color: #6c757d;
  box-shadow: 0 4px 15px rgba(108, 117, 125, 0.1);
}

.status-icon-wrapper {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 0.5rem;
  position: relative;
}

.status-icon {
  font-size: 1.5rem;
  transition: all 0.3s ease;
}

.status-icon.completed {
  color: #28a745;
  animation: checkPulse 1.5s ease-in-out infinite;
}

.status-icon.current {
  color: #ffc107;
  animation: starSpin 3s linear infinite;
}

.status-icon.pending {
  color: #6c757d;
}

.status-content {
  margin-top: 0.5rem;
}

.status-title {
  font-size: 0.85rem;
  font-weight: 600;
  color: #495057;
  margin-bottom: 0.25rem;
  line-height: 1.2;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.status-count {
  font-size: 0.75rem;
  color: #6c757d;
  font-weight: 500;
  background: rgba(108, 117, 125, 0.1);
  padding: 0.2rem 0.5rem;
  border-radius: 8px;
  display: inline-block;
}

.status-step-modern.current-status .status-count {
  background: rgba(0, 123, 255, 0.1);
  color: #007bff;
}

.status-step-modern.past-status .status-count {
  background: rgba(40, 167, 69, 0.1);
  color: #28a745;
}

.status-connector-modern {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.connector-line {
  width: 60px;
  height: 3px;
  background: #dee2e6;
  border-radius: 2px;
  transition: all 0.3s ease;
  position: relative;
}

.connector-line.active {
  background: linear-gradient(90deg, #28a745, #20c997);
  box-shadow: 0 0 10px rgba(40, 167, 69, 0.3);
}

.connector-arrow {
  color: #dee2e6;
  font-size: 1rem;
  transition: all 0.3s ease;
}

.connector-arrow.active {
  color: #28a745;
  animation: arrowPulse 1.5s ease-in-out infinite;
}

@keyframes slideInUp {
  0% {
    opacity: 0;
    transform: translateY(30px);
  }
  100% {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes pulseGlow {
  0%, 100% {
    box-shadow: 0 8px 25px rgba(0, 123, 255, 0.2);
  }
  50% {
    box-shadow: 0 8px 35px rgba(0, 123, 255, 0.4);
  }
}

@keyframes checkPulse {
  0%, 100% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.1);
  }
}

@keyframes starSpin {
  0% {
    transform: rotate(0deg);
  }
  100% {
    transform: rotate(360deg);
  }
}

@keyframes arrowPulse {
  0%, 100% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.2);
  }
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .status-flow-wrapper {
    gap: 0.5rem;
    padding: 0 0.5rem;
  }

  .status-card {
    min-width: 120px;
    padding: 0.75rem;
  }

  .status-title {
    font-size: 0.8rem;
  }

  .connector-line {
    width: 40px;
  }
}

/* Enhanced Comment Avatar Styling */
.comment-avatar {
  position: relative;
}

.comment-avatar img {
  border: 2px solid #e9ecef;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  transition: all 0.3s ease;
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
}

.comment-avatar img:hover {
  border-color: #007bff;
  box-shadow: 0 4px 12px rgba(0, 123, 255, 0.2);
  transform: scale(1.05);
}

.reply-avatar {
  position: relative;
}

.reply-avatar img {
  border: 2px solid #dee2e6;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.08);
  transition: all 0.2s ease;
  background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
}

.reply-avatar img:hover {
  border-color: #6c757d;
  box-shadow: 0 2px 6px rgba(108, 117, 125, 0.15);
  transform: scale(1.02);
}

/* Avatar Online Indicator */
.comment-avatar::after,
.reply-avatar::after {
  content: '';
  position: absolute;
  bottom: 2px;
  right: 2px;
  width: 10px;
  height: 10px;
  background: #28a745;
  border: 2px solid white;
  border-radius: 50%;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
}

/* Enhanced Comment Content Styling */
.comment-item {
  background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
  border: 1px solid #e9ecef;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
  transition: all 0.3s ease;
  margin-bottom: 1rem;
}

.comment-item:hover {
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
  transform: translateY(-1px);
}

.comment-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.75rem;
  padding-bottom: 0.5rem;
  border-bottom: 1px solid #f1f3f4;
}

.comment-header .comment-user-info {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.comment-header strong {
  color: #2c3e50;
  font-weight: 600;
  font-size: 0.95rem;
}

.comment-header small {
  color: #6c757d;
  font-size: 0.8rem;
  font-weight: 500;
  background: rgba(108, 117, 125, 0.1);
  padding: 0.25rem 0.5rem;
  border-radius: 6px;
  display: inline-block;
}

.comment-content {
  color: #495057;
  line-height: 1.6;
  font-size: 0.9rem;
  margin-bottom: 0;
}

.comment-content p {
  margin-bottom: 0;
  word-wrap: break-word;
}

/* Reply Styling */
.reply-item {
  background: #f8f9fa;
  border: 1px solid #dee2e6;
  border-radius: 8px;
  margin-left: 2rem;
  margin-top: 0.75rem;
  padding: 0.75rem;
  position: relative;
}

.reply-item::before {
  content: '';
  position: absolute;
  left: -1rem;
  top: 1rem;
  width: 0;
  height: 0;
  border-top: 8px solid transparent;
  border-bottom: 8px solid transparent;
  border-right: 8px solid #dee2e6;
}

.reply-item::after {
  content: '';
  position: absolute;
  left: -0.75rem;
  top: 1rem;
  width: 0;
  height: 0;
  border-top: 7px solid transparent;
  border-bottom: 7px solid transparent;
  border-right: 7px solid #f8f9fa;
}

.reply-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.5rem;
}

.reply-header strong {
  color: #495057;
  font-weight: 600;
  font-size: 0.85rem;
}

.reply-header small {
  color: #6c757d;
  font-size: 0.75rem;
  font-weight: 500;
}

.reply-content {
  color: #6c757d;
  font-size: 0.85rem;
  line-height: 1.5;
}

.reply-content p {
  margin-bottom: 0;
}

/* Comment Form Styling */
.comment-form {
  background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
  border: 1px solid #e9ecef;
  border-radius: 12px;
  padding: 1rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.comment-form textarea {
  border: 1px solid #ced4da;
  border-radius: 8px;
  resize: vertical;
  font-size: 0.9rem;
  line-height: 1.5;
  transition: border-color 0.3s ease, box-shadow 0.3s ease;
}

.comment-form textarea:focus {
  border-color: #007bff;
  box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.comment-form .btn {
  border-radius: 6px;
  font-weight: 500;
  transition: all 0.3s ease;
}

.comment-form .btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

/* Collapsed card styling */
.collapsed-card {
  max-height: 60px;
  overflow: hidden;
  transition: max-height 0.3s ease;
}

.collapsed-card .card-body {
  padding: 0.5rem;
}

/* Enhanced Empty State Styling */
.empty-state {
  text-align: center;
  padding: 2rem 1rem;
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
  margin-top: 1rem;
}

.empty-state-icon {
  width: 80px;
  height: 80px;
  background: linear-gradient(135deg, #6c757d, #495057);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 1rem;
  color: white;
  font-size: 2rem;
  box-shadow: 0 4px 20px rgba(108, 117, 125, 0.3);
  transition: all 0.3s ease;
}

.empty-state-icon:hover {
  transform: scale(1.05);
  box-shadow: 0 6px 25px rgba(0, 0, 0, 0.2);
}

.empty-state-title {
  color: #495057;
  font-weight: 600;
  margin-bottom: 0.5rem;
  font-size: 1.1rem;
}

.empty-state-message {
  color: #6c757d;
  font-size: 0.9rem;
  line-height: 1.5;
  margin-bottom: 0;
  max-width: 250px;
  margin-left: auto;
  margin-right: auto;
}

/* Specific colors for different tabs */
.empty-state .empty-state-icon {
  background: linear-gradient(135deg, #007bff, #0056b3);
  box-shadow: 0 4px 20px rgba(0, 123, 255, 0.3);
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .empty-state {
    padding: 1.5rem 0.5rem;
  }

  .empty-state-icon {
    width: 60px;
    height: 60px;
    font-size: 1.5rem;
  }

  .empty-state-title {
    font-size: 1rem;
  }

  .empty-state-message {
    font-size: 0.85rem;
  }
}
</style>
