<template>
  <Head title="PPMP" />
  <PageHeader title="Project Procurement Management Plan" pageTitle="Procurement" />

  <BRow class="procurement-index-page ppmp-index-page">
    <div class="col-md-12">
      <div class="card bg-light-subtle ppmp-shell shadow-none border">
        <div class="card-header bg-light-subtle ppmp-shell__header">
          <div class="d-flex mb-n3 align-items-start">
            <div class="flex-shrink-0 me-3">
              <div style="height: 2.5rem; width: 2.5rem">
                <span class="avatar-title bg-primary-subtle rounded p-2 mt-n1">
                  <i class="ri-file-list-3-line text-primary fs-24"></i>
                </span>
              </div>
            </div>
            <div class="flex-grow-1">
              <h5 class="mb-0 fs-14">
                <span class="text-body">{{ activePlanTitle }}</span>
              </h5>
              <p class="text-muted text-truncate-two-lines fs-12">
                {{ activePlanDescription }}
              </p>
            </div>
            <div class="d-flex gap-2 ms-2 flex-shrink-0">
              <b-button
                variant="light"
                size="sm"
                v-b-tooltip.hover="{ title: 'Open the Procurement User Manual' }"
                @click="userManualModal.show = true"
              >
                <i class="ri-book-open-fill text-primary me-1"></i>
                User Manual
              </b-button>
              <b-button
                variant="light"
                size="sm"
                v-b-tooltip.hover="{ title: 'Procurement Process Guide (PPMP → APP → SPP → RFQ → PO)' }"
                @click="processInfoModal.show = true"
              >
                <i class="ri-question-line text-primary me-1"></i>
                Process Guide
              </b-button>
            </div>
          </div>
        </div>

        <div class="car-body bg-white ppmp-filter-panel border-bottom shadow-none">
          <div class="px-3 pt-3">
            <ul class="nav nav-tabs nav-tabs-custom" role="tablist">
              <li class="nav-item" role="presentation">
                <button
                  type="button"
                  class="nav-link"
                  :class="{ active: ['PPMP', 'SPP', 'ALL'].includes(filter.plan_type) }"
                  @click="setPlanTab('ALL')"
                >
                  <i class="ri-table-line align-bottom me-1"></i>
                  PPMP / SPP
                </button>
              </li>
              <li
                class="nav-item"
                role="presentation"
                v-if="
                  $page.props.roles.includes('Procurement Officer') ||
                  $page.props.roles.includes('Budget Officer') ||
                  $page.props.roles.includes('Administrator')
                "
              >
                <button
                  type="button"
                  class="nav-link"
                  :class="{ active: filter.plan_type === 'APP' }"
                  @click="setPlanTab('APP')"
                >
                  <i class="ri-file-list-3-line align-bottom me-1"></i>
                  APP
                </button>
              </li>
            </ul>
          </div>
          <b-row class="mb-2 ms-1 me-1" style="margin-top: 12px">
            <b-col lg>
              <div class="input-group mb-1">
                <span class="input-group-text">
                  <i class="ri-search-line search-icon"></i>
                </span>
                <input
                  type="text"
                  v-model="filter.keyword"
                  :placeholder="`Search ${activePlanLabel}`"
                  class="form-control"
                  :style="{
                    width: filter.plan_type === 'APP' ? '52%' : '37%',
                  }"
                />
                <Multiselect
                  v-if="['PPMP', 'SPP', 'ALL'].includes(filter.plan_type)"
                  class="white"
                  style="width: 10%"
                  :options="[{ value: 'ALL', name: 'All' }, { value: 'PPMP', name: 'PPMP' }, { value: 'SPP', name: 'SPP' }]"
                  :value="filter.plan_type"
                  label="name"
                  value-prop="value"
                  :searchable="false"
                  :append-to-body="true"
                  :can-clear="false"
                  :can-deselect="false"
                  @change="setPlanTab"
                />
                <Multiselect
                  v-if="['PPMP', 'SPP', 'ALL'].includes(filter.plan_type) && canManagePPMP"
                  class="white"
                  style="width: 15%"
                  :options="unitOptions"
                  v-model="filter.unit"
                  label="name"
                  value-prop="value"
                  :searchable="true"
                  :append-to-body="true"
                  placeholder="Select Unit"
                />
                <Multiselect
                  class="white"
                  style="width: 14%"
                  :options="statusOptions"
                  v-model="filter.status"
                  label="name"
                  value-prop="value"
                  :searchable="true"
                  :append-to-body="true"
                  placeholder="Select Status"
                />
                <Multiselect
                  class="white"
                  style="width: 14%"
                  :options="sortOptions"
                  v-model="filter.sort"
                  label="name"
                  value-prop="value"
                  :searchable="false"
                  :append-to-body="true"
                  placeholder="Select Sort"
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
                  v-if="canShowCreatePlanButton"
                  :variant="appIsApprovedThisYear ? 'warning' : 'primary'"
                  class="text-white"
                  style="color: #fff !important"
                  @click="openCreatePlanModal"
                >
                  <i
                    class="ri-add-circle-line align-bottom me-1 text-white"
                    style="color: #fff !important"
                  ></i>
                  {{ appIsApprovedThisYear ? 'Create SPP' : 'Create PPMP' }}
                </b-button>
                <b-button
                  v-if="filter.plan_type === 'APP' && canCreateAppPlans"
                  variant="primary"
                  @click="openCreateAppModal"
                  class="text-white"
                  style="color: #fff !important"
                >
                  <i
                    class="ri-file-add-line align-bottom me-1"
                    style="color: #fff !important"
                  ></i>
                  Create APP
                </b-button>
              </div>
            </b-col>
          </b-row>
        </div>

        <b-card no-body class="ppmp-list-card">
          <div class="card-body bg-white ppmp-table-panel rounded-bottom mt-3">
            <div
              class="table-responsive table-card ppmp-list-scroll"
              style="margin-top: -39px; height: calc(100vh - 350px); overflow: auto"
            >
              <table
                v-if="filter.plan_type === 'APP'"
                class="table align-middle table-hover mb-0 ppmp-table app-register-table"
              >
                <thead class="table-light thead-fixed">
                  <tr class="fs-12 fw-semibold align-middle">
                    <th style="width: 4%" class="text-center">#</th>
                    <th style="min-width: 160px">APP No.</th>
                    <th style="min-width: 130px">Fiscal Year</th>
                    <th style="min-width: 150px" class="text-end">Total ABC</th>
                    <th style="min-width: 180px">Source</th>
                    <th style="min-width: 165px" class="text-center">APP Status</th>
                    <th style="min-width: 120px" class="text-center">Actions</th>
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
                    <td class="text-center fw-semibold">
                      {{ index + 1 }}
                    </td>
                    <td>
                      <div class="fw-semibold text-primary">
                        {{ list.ppmp_no || "-" }}
                      </div>
                      <div class="d-flex flex-wrap gap-1 mt-1">
                        <b-badge
                          :variant="list.plan_phase === 'final' ? 'success' : 'warning'"
                          style="font-size: 10px"
                        >
                          <i :class="list.plan_phase === 'final' ? 'ri-flag-2-line' : 'ri-draft-line'" class="me-1"></i>
                          {{ list.plan_phase_label || list.plan_name || "Annual Procurement Plan" }}
                        </b-badge>
                        <span v-if="list.version" class="version-badge">Version {{ list.version }}</span>
                      </div>
                    </td>
                    <td>
                      <div class="fw-medium">{{ fiscalYear(list) }}</div>
                      <small class="text-muted">Agency-wide</small>
                    </td>
                    <td class="text-end">
                      <span class="fw-semibold">{{
                        formatCurrency(list.estimated_budget)
                      }}</span>
                      <small class="d-block text-muted">{{
                        list.source_of_funds || "-"
                      }}</small>
                    </td>
                    <td>
                      <div class="fw-medium">
                        {{ sourcePpmpCount(list) }} PPMP{{
                          sourcePpmpCount(list) === 1 ? "" : "s"
                        }}
                      </div>
                      <small class="text-muted">{{ sourcePpmpPreview(list) }}</small>
                    </td>
                    <td class="text-center">
                      <b-badge :variant="ppmpStatusBadgeVariant(list)">
                        {{ list.ppmp_status || "Pending" }}
                      </b-badge>
                      <small v-if="statusUserLabel(list)" class="d-block text-muted mt-1">
                        {{ statusUserLabel(list) }}
                      </small>
                    </td>
                    <td class="text-center">
                      <div class="ppmp-action-group">
                        <b-button
                          @click.stop="goViewPage(list, filter.plan_type)"
                          size="sm"
                          variant="info"
                          class="btn-icon"
                          v-b-tooltip.hover
                          title="View APP Details"
                          style="border-radius: 8px"
                        >
                          <i class="ri-eye-line"></i>
                        </b-button>
                        <b-button
                          v-if="canApprovePPMP(list)"
                          @click.stop="goViewPage(list, filter.plan_type)"
                          size="sm"
                          variant="success"
                          class="btn-icon"
                          v-b-tooltip.hover
                          :title="advanceActionTitle(list)"
                          style="border-radius: 8px"
                        >
                          <i class="ri-check-double-line"></i>
                        </b-button>
                        <b-button
                          @click.stop="openTimelineModal(list)"
                          size="sm"
                          variant="light"
                          class="btn-icon"
                          v-b-tooltip.hover
                          title="Status Timeline"
                          style="border-radius: 8px"
                        >
                          <i class="ri-git-branch-line"></i>
                        </b-button>
                        <b-button
                          @click.stop="printPPMP(list)"
                          size="sm"
                          variant="dark"
                          class="btn-icon"
                          v-b-tooltip.hover
                          title="Print APP"
                          style="border-radius: 8px"
                        >
                          <i class="ri-printer-line"></i>
                        </b-button>
                        <b-button
                          @click.stop="openPlanChat(list)"
                          size="sm"
                          variant="soft-info"
                          class="btn-icon position-relative overflow-visible"
                          v-b-tooltip.hover
                          :title="
                            planCommentCount(list)
                              ? `${planCommentCount(list)} comment${
                                  planCommentCount(list) === 1 ? '' : 's'
                                } for ${list.ppmp_no || 'APP'}`
                              : 'Open APP comments'
                          "
                          style="border-radius: 8px"
                        >
                          <i class="ri-chat-1-line"></i>
                          <span
                            v-if="planCommentCount(list) > 0"
                            class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger text-white ppmp-chat-row-count"
                          >
                            {{ planCommentCount(list) }}
                          </span>
                        </b-button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>

              <table v-else class="table align-middle table-hover mb-0 ppmp-table">
                <thead class="table-light thead-fixed">
                  <tr class="fs-12 fw-semibold align-middle">
                    <th style="width: 4%" class="text-center">#</th>
                    <th style="min-width: 160px">{{ planNumberHeader }}</th>
                    <th style="min-width: 260px">Division/Unit</th>
                    <th style="min-width: 130px" class="text-end">Total ABC</th>
                    <th style="min-width: 140px">Schedule</th>
                    <th style="min-width: 165px" class="text-center">Status</th>
                    <th style="min-width: 100px" class="text-center">Actions</th>
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
                    <td class="text-center fw-semibold">
                      {{ index + 1 }}
                    </td>
                    <td>
                      <div class="fw-semibold text-primary">
                        {{ list.ppmp_no || "-" }}
                      </div>
                      <div class="d-flex flex-wrap gap-1 mt-1">
                        <b-badge
                          v-if="list.ppmp_type_label"
                          :variant="list.ppmp_type === 'final' ? 'success' : 'info'"
                          style="font-size: 10px"
                        >
                          {{ list.ppmp_type_label }}
                        </b-badge>
                        <span class="version-badge">Version {{ list.ppmp_type_version || 1 }}</span>
                        <b-badge
                          :variant="isSppRow(list) ? 'warning' : 'primary'"
                          style="font-size: 10px"
                        >
                          {{ isSppRow(list) ? 'SPP' : 'PPMP' }}
                        </b-badge>
                        <b-badge
                          v-if="ppmpQuarter(list)"
                          :variant="ppmpQuarterVariant(list)"
                          style="font-size: 10px"
                          v-b-tooltip.hover
                          :title="`Created: ${formatDate(list.created_at)}`"
                        >
                          {{ ppmpQuarter(list) }}
                        </b-badge>
                      </div>
                    </td>
                    <td>
                      <div class="fw-medium">{{ list.unit?.name || "-" }}</div>
                      <small class="text-muted">{{
                        normalizedPlanType(list.plan_type) === "PPMP"
                          ? list.division?.name || "-"
                          : "All end-user units"
                      }}</small>
                      <small class="d-block text-muted">{{
                        list.plan_name || "PPMP"
                      }}</small>
                    </td>
                    <td class="text-end">
                      <span class="fw-semibold">{{
                        formatCurrency(list.estimated_budget)
                      }}</span>
                      <small class="d-block text-muted">{{
                        list.source_of_funds || "-"
                      }}</small>
                    </td>
                    <td>
                      <div>{{ formatDate(list.start_of_procurement_activity) }}</div>
                      <small class="text-muted">Start date</small>
                    </td>
                    <td class="text-center">
                      <b-badge :variant="ppmpStatusBadgeVariant(list)">
                        {{ list.ppmp_status || "Pending" }}
                      </b-badge>
                      <small v-if="statusUserLabel(list)" class="d-block text-muted mt-1">
                        {{ statusUserLabel(list) }}
                      </small>
                    </td>
                    <td class="text-center">
                      <div class="ppmp-action-group">
                        <b-button
                          @click.stop="goViewPage(list, filter.plan_type === 'APP' ? 'APP' : (normalizedPlanType(list.plan_type) === 'SPP' ? 'SPP' : 'PPMP'))"
                          size="sm"
                          variant="info"
                          class="btn-icon"
                          v-b-tooltip.hover
                          :title="`View ${planShortName(list)} Details`"
                          style="border-radius: 8px"
                        >
                          <i class="ri-eye-line"></i>
                        </b-button>
                        <b-button
                          v-if="canApprovePPMP(list)"
                          @click.stop="goViewPage(list, filter.plan_type === 'APP' ? 'APP' : (normalizedPlanType(list.plan_type) === 'SPP' ? 'SPP' : 'PPMP'))"
                          size="sm"
                          variant="success"
                          class="btn-icon"
                          v-b-tooltip.hover
                          :title="advanceActionTitle(list)"
                          style="border-radius: 8px"
                        >
                          <i class="ri-check-double-line"></i>
                        </b-button>
                        <b-button
                          v-if="canApproveToApp(list)"
                          @click.stop="openApproveToAppModal(list)"
                          size="sm"
                          variant="success"
                          class="btn-icon"
                          v-b-tooltip.hover
                          title="Consolidate PPMP to APP"
                          style="border-radius: 8px"
                          :disabled="approveAppForm.processing"
                        >
                          <i class="ri-checkbox-circle-line"></i>
                        </b-button>
                        <b-button
                          v-if="list.can_revert_status"
                          @click.stop="openRevertStatusModal(list)"
                          size="sm"
                          variant="warning"
                          class="btn-icon"
                          v-b-tooltip.hover
                          title="Revert to previous status"
                          style="border-radius: 8px"
                          :disabled="revertStatusForm.processing"
                        >
                          <i class="ri-arrow-go-back-line"></i>
                        </b-button>
                        <b-button
                          @click.stop="openPlanChat(list)"
                          size="sm"
                          variant="soft-info"
                          class="btn-icon position-relative overflow-visible"
                          v-b-tooltip.hover
                          :title="
                            planCommentCount(list)
                              ? `${planCommentCount(list)} comment${
                                  planCommentCount(list) === 1 ? '' : 's'
                                } for ${list.ppmp_no || planShortName(list)}`
                              : `Open ${planShortName(list)} comments`
                          "
                          style="border-radius: 8px"
                        >
                          <i class="ri-chat-1-line"></i>
                          <span
                            v-if="planCommentCount(list) > 0"
                            class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger text-white ppmp-chat-row-count"
                          >
                            {{ planCommentCount(list) }}
                          </span>
                        </b-button>
                        <b-button
                          v-if="canAddSppItem(list)"
                          @click.stop="openAddSppItemModal(list)"
                          size="sm"
                          variant="primary"
                          class="btn-icon"
                          v-b-tooltip.hover
                          title="Add Item"
                          style="border-radius: 8px"
                        >
                          <i class="ri-add-circle-line"></i>
                        </b-button>
                        <b-button
                          @click.stop="openTimelineModal(list)"
                          size="sm"
                          variant="light"
                          class="btn-icon"
                          v-b-tooltip.hover
                          title="Status Timeline"
                          style="border-radius: 8px"
                        >
                          <i class="ri-git-branch-line"></i>
                        </b-button>
                        <b-button
                          @click.stop="printPPMP(list)"
                          size="sm"
                          variant="dark"
                          class="btn-icon"
                          v-b-tooltip.hover
                          title="Print"
                          style="border-radius: 8px"
                        >
                          <i class="ri-printer-line"></i>
                        </b-button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>

              <div v-if="!lists.length" class="text-center text-muted py-4">
                No {{ activePlanLabel }} entries found. {{ emptyStateHint }}
              </div>

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

  <ApproveToAppModal
    v-model:show="approveAppModal.show"
    :ppmp="approveAppModal.data"
    :processing="approveAppForm.processing"
    :error="approveAppModal.error"
    @cancel="closeApproveToAppModal"
    @confirm="approveToApp"
  />

  <RevertStatusModal
    v-model:show="revertStatusModal.show"
    :plan="revertStatusModal.data"
    :plan-type="filter.plan_type"
    :processing="revertStatusForm.processing"
    :error="revertStatusModal.error"
    @cancel="closeRevertStatusModal"
    @confirm="revertStatus"
  />

  <CreateUnitPpmpModal
    v-model:show="createPpmpModal.show"
    :form="createPpmpForm"
    :year-options="yearOptions"
    :unit-options="availablePpmpUnits"
    :loading="createPpmpModal.loading"
    @close="closeCreatePpmpModal"
    @submit="submitCreatePpmp"
  />

  <CreateAppModal
    v-model:show="createAppModal.show"
    :form="createAppForm"
    :year-options="yearOptions"
    :indicative-app-years="indicativeAppYears"
    :final-app-years="finalAppYears"
    @close="closeCreateAppModal"
    @submit="submitCreateApp"
  />

  <CreateSppModal
    v-model="createSppModal.show"
    :form="createSppForm"
    :year-options="yearOptions"
    :unit-options="availableSppUnits"
    @close="closeCreateSppModal"
    @submit="submitCreateSpp"
  />

  <AddItemModal
    ref="addSppItemModal"
    :ppmp="addSppItemModal.ppmp"
    :dropdowns="dropdowns"
  />

  <b-modal
    v-model="timelineModal.show"
    size="xl"
    centered
    hide-footer
    header-class="border-bottom pb-2"
  >
    <template #header>
      <div class="d-flex align-items-center gap-2 w-100">
        <span
          class="avatar-title rounded p-2"
          :class="{
            'bg-warning-subtle': timelineModal.planType === 'APP',
            'bg-primary-subtle': timelineModal.planType === 'PPMP',
            'bg-success-subtle': timelineModal.planType === 'SPP',
          }"
          style="width:2rem;height:2rem;display:inline-flex;align-items:center;justify-content:center;flex-shrink:0"
        >
          <i
            class="ri-git-branch-line"
            :class="{
              'text-warning': timelineModal.planType === 'APP',
              'text-primary': timelineModal.planType === 'PPMP',
              'text-success': timelineModal.planType === 'SPP',
            }"
          ></i>
        </span>
        <div class="flex-grow-1">
          <div class="fw-bold fs-14">{{ timelineModal.planType }} Status Timeline</div>
          <div class="text-muted fs-12">
            {{ timelineModal.plan?.code || timelineModal.plan?.ppmp_no || '—' }}
            &nbsp;·&nbsp;
            {{ timelineModal.plan?.ppmp_status || timelineModal.plan?.approval_status || 'Pending' }}
          </div>
        </div>
        <button type="button" class="btn-close" @click="timelineModal.show = false"></button>
      </div>
    </template>
    <PlanStatusTimeline
      v-if="timelineModal.plan"
      :plan="timelineModal.plan"
      :plan-type="timelineModal.planType"
    />
  </b-modal>

  <FloatingPlanChat
    ref="planChat"
    :plan="selectedChatPlan"
    :plans="lists"
    :show-trigger="true"
    @comment-added="syncPlanCommentCount"
  />

  <ProcurementProcessInfoModal
    v-model:show="processInfoModal.show"
    @close="processInfoModal.show = false"
  />

  <ProcurementUserManualModal
    :show="userManualModal.show"
    @update:show="userManualModal.show = $event"
    @close="userManualModal.show = false"
  />
</template>

<script>
import _ from "lodash";
import { router, useForm } from "@inertiajs/vue3";
import Multiselect from "@vueform/multiselect";
import PageHeader from "@/Shared/Components/PageHeader.vue";
import Pagination from "@/Shared/Components/Pagination.vue";
import CreateUnitPpmpModal from "./Modals/CreateUnitPpmp.vue";
import CreateAppModal from "./Modals/CreateApp.vue";
import CreateSppModal from "./Modals/CreateSpp.vue";
import AddItemModal from "./Modals/AddItem.vue";
import ApproveToAppModal from "./Modals/ApproveToApp.vue";
import RevertStatusModal from "./Modals/RevertStatus.vue";
import FloatingPlanChat from "./Components/FloatingPlanChat.vue";
import PlanStatusTimeline from "./Components/PlanStatusTimeline.vue";
import ProcurementProcessInfoModal from "./Modals/ProcurementProcessInfoModal.vue";
import ProcurementUserManualModal from "./Modals/ProcurementUserManualModal.vue";

export default {
  props: ["dropdowns"],
  components: {
    Multiselect,
    PageHeader,
    Pagination,
    CreateUnitPpmpModal,
    CreateAppModal,
    CreateSppModal,
    AddItemModal,
    ApproveToAppModal,
    RevertStatusModal,
    FloatingPlanChat,
    PlanStatusTimeline,
    ProcurementProcessInfoModal,
    ProcurementUserManualModal,
  },
  data() {
    return {
      lists: [],
      meta: {},
      links: {},
      selectedRow: null,
      filter: {
        keyword: null,
        status: null,
        unit: null,
        plan_type: "ALL",
        sort: "latest",
      },
      processInfoModal: { show: false },
      userManualModal: { show: false },
      createPpmpModal: {
        show: false,
        loading: false,
      },
      createAppModal: {
        show: false,
      },
      createSppModal: {
        show: false,
      },
      createPpmpForm: useForm({
        option: "create_ppmp",
        unit_id: null,
        year: new Date().getFullYear(),
        quarter: Math.ceil((new Date().getMonth() + 1) / 3),
        plan_type: "PPMP",
        attachment_file: null,
        requested_by_id: null,
      }),
      createAppForm: useForm({
        year: new Date().getFullYear(),
        plan_type: "APP",
        plan_phase: "indicative",
      }),
      approveAppForm: useForm({
        option: "approve_to_app",
        plan_type: "PPMP",
        consolidation_review_acknowledged: false,
        consolidation_pricing: [],
      }),
      approveAppModal: {
        show: false,
        data: null,
        error: "",
      },
      revertStatusForm: useForm({
        option: "revert_status",
        plan_type: "PPMP",
        revert_reason: "",
      }),
      revertStatusModal: {
        show: false,
        data: null,
        error: "",
      },
      createSppForm: useForm({
        year: new Date().getFullYear(),
        plan_type: "SPP",
        unit_id: null,
        attachment_file: null,
        requested_by_id: null,
      }),
      availablePpmpUnits: [],
      availableSppUnits: [],
      addSppItemModal: {
        ppmp: null,
      },
      pendingNotificationPlanId: null,
      pendingNotificationCommentId: null,
      pendingNotificationChatOpened: false,
      annualAppYearCache: null,
      timelineModal: {
        show: false,
        plan: null,
        planType: "PPMP",
      },
    };
  },
  computed: {
    currentRoles() {
      return Array.isArray(this.$page?.props?.roles) ? this.$page.props.roles : [];
    },
    currentRoleNames() {
      return this.currentRoles
        .map((role) => (typeof role === "string" ? role : role?.name))
        .filter(Boolean);
    },
    canManagePPMP() {
      return this.hasRole("Procurement Officer");
    },
    hasProcurementCreateRole() {
      return this.hasRole("Procurement Staff") || this.hasRole("Procurement Officer");
    },
    canShowCreatePpmpButton() {
      if (!["PPMP", "ALL"].includes(this.filter.plan_type)) {
        return false;
      }

      if (this.hasProcurementCreateRole) {
        return true;
      }

      return Boolean(
        this.defaultUserUnitId &&
          this.availablePpmpUnits.some(
            (unit) => Number(unit.value) === this.defaultUserUnitId
          )
      );
    },
    canShowCreateSppButton() {
      return ["SPP", "ALL"].includes(this.filter.plan_type) && this.availableSppUnits.length > 0;
    },
    appIsApprovedThisYear() {
      if (this.defaultUserUnitId) {
        return this.availableSppUnits.some((u) => Number(u.value) === this.defaultUserUnitId);
      }
      return this.availableSppUnits.length > 0;
    },
    canShowCreatePlanButton() {
      if (!["PPMP", "SPP", "ALL"].includes(this.filter.plan_type)) return false;
      return this.canShowCreatePpmpButton || this.canShowCreateSppButton;
    },
    canApprovePPMPPlans() {
      const bacDesignations = ["BAC Chairperson", "BAC Vice Chairperson", "BAC Member"];
      const designation = this.$page.props.user?.data?.designation;

      return (
        this.hasRole("BAC User") ||
        bacDesignations.some((role) => this.hasRole(role)) ||
        bacDesignations.includes(designation)
      );
    },
    canCreateAppPlans() {
      return this.hasRole("Procurement Officer");
    },
    unitOptions() {
      return this.normalizeOptions(this.dropdowns?.units);
    },
    defaultUserUnitId() {
      const unitId = this.$page.props.user?.data?.organization?.unit_id;

      return unitId ? Number(unitId) : null;
    },
    statusOptions() {
      const options = this.normalizeOptions(this.dropdowns?.statuses).map((status) => ({
        ...status,
        name:
          status.name === "Reviewed"
            ? "Reviewed/For Submission"
            : status.name === "Approved"
            ? this.filter.plan_type === "APP"
              ? "Submitted/For Implementation"
              : "Submitted/For Consolidation"
            : status.name,
      }));

      if (!options.some((status) => status.name === "For Review")) {
        options.push({ value: "For Review", name: "For Review" });
      }

      return options;
    },
    sortOptions() {
      return [
        { value: "latest", name: "Latest Date" },
        { value: "oldest", name: "Oldest Date" },
      ];
    },
    yearOptions() {
      const years = [];

      for (let year = this.currentYear + 2; year >= this.currentYear - 5; year--) {
        years.push({ value: year, name: year.toString() });
      }

      return years;
    },
    currentYear() {
      return new Date().getFullYear();
    },
    annualAppYears() {
      if (Array.isArray(this.annualAppYearCache)) {
        return this.annualAppYearCache;
      }

      const years = this.dropdowns?.annual_app_years || [];
      return Array.isArray(years) ? years.map((year) => Number(year)) : [];
    },
    indicativeAppYears() {
      const years = this.dropdowns?.indicative_app_years || [];
      return Array.isArray(years) ? years.map(Number) : [];
    },
    finalAppYears() {
      const years = this.dropdowns?.final_app_years || [];
      return Array.isArray(years) ? years.map(Number) : [];
    },
    availableAppYearOptions() {
      // Show all years — per-phase duplicate check is done in the modal and server-side
      return this.yearOptions;
    },
    appYearHasExisting() {
      // Kept for any legacy use; per-phase check is now in CreateAppModal
      return this.annualAppYears.includes(Number(this.createAppForm.year));
    },
    activePlanTitle() {
      if (this.filter.plan_type === "APP") {
        return "Annual Procurement Plan";
      }

      if (this.filter.plan_type === "SPP") {
        return "Supplemental Procurement Plan";
      }

      return "Project Procurement Management Plans";
    },
    activePlanDescription() {
      if (this.filter.plan_type === "APP") {
        return "This is the agency-wide consolidated plan built from submitted PPMPs ready for BAC consolidation.";
      }

      if (this.filter.plan_type === "SPP") {
        return "SPP entries are organized per end-user unit after APP approval for new needs or budget changes.";
      }

      return "PPMP entries are organized per end-user unit and become the source for purchase request items.";
    },
    activePlanLabel() {
      if (this.filter.plan_type === "SPP") {
        return "SPP";
      }

      if (this.filter.plan_type === "APP") {
        return "agency-wide APP";
      }

      return "PPMP";
    },
    planNumberHeader() {
      if (this.filter.plan_type === "APP") {
        return "APP No.";
      }

      if (this.filter.plan_type === "SPP") {
        return "SPP No.";
      }

      return "PPMP No.";
    },
    emptyStateHint() {
      if (this.filter.plan_type === "APP") {
        return "Move unit PPMPs through review and submission first, then approve the consolidated APP list by year.";
      }

      if (this.filter.plan_type === "SPP") {
        return "Create a unit SPP after APP approval, then add the supplemental items for that unit.";
      }

      return "Create PPMP items first from purchase planning.";
    },
    selectedChatPlan() {
      if (!this.selectedRow) {
        return null;
      }

      return (
        this.lists.find((item) => Number(item.id) === Number(this.selectedRow)) || null
      );
    },
  },
  watch: {
    "filter.keyword"() {
      this.checkSearchStr();
    },
    "filter.status"() {
      this.fetch();
    },
    "filter.unit"() {
      this.fetch();
    },
    "filter.sort"() {
      this.fetch();
    },
    "createPpmpForm.year"() {
      if (this.createPpmpModal.show) {
        this.fetchAvailablePpmpUnits();
      }
    },
    "createPpmpForm.quarter"(quarter) {
      if (!this.createPpmpForm.unit_id) return;
      const unit = this.availablePpmpUnits.find((u) => Number(u.value) === Number(this.createPpmpForm.unit_id));
      if (unit && (unit.taken_quarters ?? []).includes(quarter)) {
        this.createPpmpForm.unit_id = null;
        this.createPpmpForm.requested_by_id = null;
      }
    },
    "createPpmpForm.unit_id"(unitId) {
      this.autoSetRequestedBy(this.createPpmpForm, this.availablePpmpUnits, unitId);
      this.autoSetFirstAvailableQuarter(unitId);
    },
    "createSppForm.year"() {
      if (this.createSppModal.show) {
        this.fetchAvailableSppUnits();
      }
    },
    "createSppForm.unit_id"(unitId) {
      this.autoSetRequestedBy(this.createSppForm, this.availableSppUnits, unitId);
    },
    "$page.url"() {
      this.hydrateNotificationChatParams(true);
    },
  },
  created() {
    this.hydrateNotificationChatParams(false);
    this.filter.plan_type = this.initialPlanType();
    this.createPpmpForm.year = this.currentYear;
    this.fetchAvailablePpmpUnits();
    this.fetchAvailableSppUnits();
    this.fetch();
  },
  mounted() {
    this.subscribeToPlanStatusUpdates();
    document.addEventListener("visibilitychange", this._onVisibilityChange);
  },
  beforeUnmount() {
    this.unsubscribeFromPlanStatusUpdates();
    document.removeEventListener("visibilitychange", this._onVisibilityChange);
  },
  methods: {
    hasRole(roleName) {
      return (
        Array.isArray(this.$page?.props?.roles) &&
        this.$page.props.roles.includes(roleName)
      );
    },
    _onVisibilityChange() {
      if (document.visibilityState === "visible") {
        this.fetch();
      }
    },
    subscribeToPlanStatusUpdates() {
      if (!window.Echo) {
        return;
      }

      window.Echo.channel("procurement-plans").listen(
        ".procurement-plan.status-updated",
        () => {
          this.fetch();
        }
      );
    },
    unsubscribeFromPlanStatusUpdates() {
      if (!window.Echo) {
        return;
      }

      window.Echo.leave("procurement-plans");
    },
    planCommentCount(item) {
      return Number(item?.comments_count || 0);
    },
    syncPlanCommentCount(comment) {
      const commentableId = Number(comment?.commentable_id || 0);

      if (!commentableId) {
        return;
      }

      this.lists = this.lists.map((item) => {
        if (Number(item.id) !== commentableId) {
          return item;
        }

        return {
          ...item,
          comments_count: this.planCommentCount(item) + 1,
        };
      });
    },
    canApprovePPMP(item) {
      if (!item) {
        return false;
      }

      const statusName =
        typeof item.status === "string" ? item.status : item.status?.name;
      const displayStatus = String(item.ppmp_status || "").toLowerCase();
      const planType = this.normalizedPlanType(item.plan_type);
      const isApp = planType === "APP";
      const isSpp = planType === "SPP";

      const isPending = [statusName, item.ppmp_status]
        .filter(Boolean)
        .some((status) => String(status).toLowerCase() === "pending");
      const isForReview = [statusName, item.ppmp_status]
        .filter(Boolean)
        .some((status) => String(status).toLowerCase() === "for review");
      const isReviewed =
        String(statusName || "").toLowerCase() === "reviewed" ||
        displayStatus === "reviewed/for submission";
      const canReview = this.hasRole("Budget Officer");
      const canSubmit = this.hasRole("Procurement Officer");
      const canSubmitForReview = this.canSubmitPendingPpmp(item);

      if (isReviewed && canSubmit) {
        return true;
      }

      if (
        (!isApp && !isSpp && item.is_final) ||
        [
          "submitted/for consolidation",
          "submitted/for implementation",
          "consolidated/added to app",
          "consolidated/added to spp",
        ].includes(displayStatus)
      ) {
        return false;
      }

      return (
        (isPending && canSubmitForReview) ||
        (isForReview && canReview) ||
        (isReviewed && canSubmit)
      );
    },
    canSubmitPendingPpmp(item) {
      const currentUserId = Number(this.$page?.props?.user?.data?.id || 0);
      const createdById = Number(item?.created_by_id || 0);

      return Boolean(
        (currentUserId && currentUserId === createdById) ||
          this.hasRole("Procurement Staff") ||
          this.hasRole("Procurement Officer")
      );
    },
    hasPpmpItems(item) {
      return Number(item?.items_count || 0) > 0
        || (item?.project_rows?.length || 0) > 0;
    },
    requiresItemsBeforeAdvance(item) {
      return ["PPMP", "SPP"].includes(this.normalizedPlanType(item?.plan_type));
    },
    firstFormError(errors) {
      const firstError = Object.values(errors || {})
        .flat()
        .find(Boolean);

      return firstError || "Unable to update this plan status.";
    },
    canApproveToApp(item) {
      return (
        this.canApprovePPMPPlans &&
        Boolean(item?.can_approve_to_app) &&
        (item?.ppmp_status || "").toLowerCase() === "submitted/for consolidation"
      );
    },
    canAddSppItem(item) {
      if (this.normalizedPlanType(item?.plan_type) !== "SPP") {
        return false;
      }

      if (String(item?.ppmp_status || "").toLowerCase() !== "pending") {
        return false;
      }

      const currentUserId = Number(this.$page?.props?.user?.data?.id || 0);
      const createdById = Number(item?.created_by_id || 0);

      return Boolean(
        (currentUserId && currentUserId === createdById) ||
          this.isSameUserUnit(item) ||
          this.hasRole("Procurement Staff") ||
          this.hasRole("Procurement Officer")
      );
    },
    currentUserUnitId() {
      const unitId = this.$page?.props?.user?.data?.organization?.unit_id;

      return unitId ? Number(unitId) : null;
    },
    planUnitId(item) {
      return Number(item?.unit_id || item?.unit?.id || item?.unit?.value || 0) || null;
    },
    isSameUserUnit(item) {
      return Boolean(
        this.currentUserUnitId() && this.planUnitId(item) === this.currentUserUnitId()
      );
    },
    normalizeOptions(options) {
      if (Array.isArray(options)) {
        return options;
      }

      if (options && typeof options === "object") {
        return Object.values(options);
      }

      return [];
    },
    checkSearchStr: _.debounce(function () {
      this.fetch();
    }, 300),
    fetch(pageUrl) {
      const url = pageUrl || "/faims/procurement-ppmp";

      axios
        .get(url, {
          params: {
            keyword: this.filter.keyword,
            status: this.filter.status,
            unit: this.filter.unit,
            plan_type: this.filter.plan_type === "ALL" ? null : this.filter.plan_type,
            sort: this.filter.sort,
            count: 10,
            option: "lists",
          },
        })
        .then((response) => {
          const lists = response.data.data || [];
          this.lists =
            this.filter.plan_type === "SPP"
              ? lists.filter((item) => this.normalizedPlanType(item?.plan_type) === "SPP")
              : this.filter.plan_type === "PPMP"
              ? lists.filter((item) => this.normalizedPlanType(item?.plan_type) !== "SPP")
              : lists;
          if (this.filter.plan_type === "APP") {
            this.refreshAnnualAppYearCache(this.lists);
          }
          this.meta = response.data.meta || {};
          this.links = response.data.links || {};
          this.openNotificationPlanChat();
        })
        .catch((error) => console.log(error));
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
    formatCurrency(value) {
      return new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
      }).format(Number(value || 0));
    },
    formatQuantity(value) {
      const amount = Number(value || 0);

      return Number.isInteger(amount) ? amount.toString() : amount.toFixed(2);
    },
    fiscalYear(item) {
      const sourceDate =
        item?.start_of_procurement_activity || item?.date || item?.created_at;

      if (!sourceDate) {
        return "-";
      }

      const year = new Date(sourceDate).getFullYear();

      return Number.isNaN(year) ? "-" : year;
    },
    isSppRow(item) {
      const name = String(item?.plan_name || "").toLowerCase();
      const code = String(item?.ppmp_no || item?.code || "");

      return this.normalizedPlanType(item?.plan_type) === "SPP"
        || name === "spp"
        || name.includes("supplemental")
        || code.startsWith("SPP-");
    },
    ppmpQuarter(item) {
      if (item?.quarter) return 'Q' + item.quarter;
      const d = item?.created_at || item?.date;
      if (!d) return null;
      return 'Q' + Math.ceil((new Date(d).getMonth() + 1) / 3);
    },
    ppmpQuarterVariant(item) {
      const variants = { Q1: 'info', Q2: 'success', Q3: 'warning', Q4: 'danger' };
      return variants[this.ppmpQuarter(item)] || 'secondary';
    },
    sourcePpmpCount(item) {
      if (Array.isArray(item?.source_ppmps)) {
        return item.source_ppmps.length;
      }

      return Number(item?.source_ppmps_count || item?.items_count || 0);
    },
    sourcePpmpPreview(item) {
      const sources = Array.isArray(item?.source_ppmps) ? item.source_ppmps : [];

      if (!sources.length) {
        return item?.items_count
          ? `${item.items_count} item${Number(item.items_count) === 1 ? "" : "s"}`
          : "-";
      }

      const labels = sources
        .map((source) => source.ppmp_no || source.pr_no || source.unit)
        .filter(Boolean);

      if (!labels.length) {
        return "-";
      }

      return labels.slice(0, 3).join(", ") + (labels.length > 3 ? "..." : "");
    },
    refreshAnnualAppYearCache(lists = []) {
      this.annualAppYearCache = lists
        .map((item) => Number(this.fiscalYear(item)))
        .filter((year) => Number.isInteger(year))
        .filter((year, index, years) => years.indexOf(year) === index);
    },
    normalizedPlanType(planType) {
      switch (planType) {
        case "APP":
        case "annual":
        case "Annual Procurement Plan":
          return "APP";

        case "SPP":
        case "supplemental":
        case "Supplemental Procurement Plan":
          return "SPP";

        case "PPMP":
        case "ppmp":
        default:
          return "PPMP";
      }
    },
    planShortName(item) {
      const planType = this.normalizedPlanType(item?.plan_type);

      if (planType === "APP") {
        return "APP";
      }

      if (planType === "SPP") {
        return "SPP";
      }

      return "PPMP";
    },
    ppmpStatusBadgeVariant(item) {
      const status = (item?.ppmp_status || "").toLowerCase();

      if (status === "approved") {
        return "success";
      }

      if (
        status === "reviewed/for submission" ||
        status === "submitted/for consolidation" ||
        status === "submitted/for implementation"
      ) {
        return "warning";
      }

      if (status === "for review") {
        return "info";
      }

      if (
        status === "consolidated/added to app" ||
        status === "consolidated/added to spp"
      ) {
        return "success";
      }

      return "secondary";
    },
    statusUserLabel(item) {
      const status = (item?.ppmp_status || "").toLowerCase();

      if (status === "pending" && item?.created_by) {
        return `Created by ${item.created_by}`;
      }

      if (status === "for review" && item?.submitted_for_review_by) {
        const submittedAt = item.submitted_for_review_at
          ? ` on ${this.formatDate(item.submitted_for_review_at)}`
          : "";

        return `Submitted for review by ${item.submitted_for_review_by}${submittedAt}`;
      }

      if (item?.reviewed_by) {
        const reviewedAt = item.reviewed_at
          ? ` on ${this.formatDate(item.reviewed_at)}`
          : "";

        return `Reviewed by ${item.reviewed_by}${reviewedAt}`;
      }

      return null;
    },
    advanceActionTitle(item) {
      const status = (item?.ppmp_status || "").toLowerCase();
      const planType = this.normalizedPlanType(item?.plan_type);
      const planLabel = planType === "SPP" ? "SPP" : "PPMP";

      if (status === "pending") {
        return planType === "APP"
          ? "Move APP to For Review"
          : `Submit ${planLabel} for review`;
      }

      if (status === "for review") {
        return planType === "APP"
          ? "Mark APP as reviewed"
          : `Mark ${planLabel} as reviewed`;
      }

      if (status === "reviewed/for submission") {
        return planType === "APP"
          ? "Submit APP for Implementation"
          : `Submit ${planLabel} for consolidation`;
      }

      return `Review ${planLabel}`;
    },

    goViewPage(data, plan_type) {
      router.get(`/faims/procurement-ppmp/${data.id}`, {
        option: "view",
        plan_type: plan_type,
      });
    },
    printPPMP(data) {
      if (!data?.id) {
        return;
      }

      const planType = ["APP", "SPP"].includes(this.filter.plan_type)
        ? this.filter.plan_type
        : "ppmp";
      const params = new URLSearchParams({
        option: "print",
        type: "ppmp",
        plan_type: planType,
      });

      window.open(`/faims/procurement-ppmp/${data.id}?${params.toString()}`, "_blank");
    },
    selectRow(index) {
      this.selectedRow = this.selectedRow == index ? null : index;
    },
    refresh() {
      this.filter.keyword = null;
      this.filter.status = null;
      this.filter.unit = null;
      this.filter.sort = "latest";
      this.fetch();
    },
    openTimelineModal(plan) {
      const activePlanType = this.normalizedPlanType(this.filter.plan_type);

      this.timelineModal.planType = ["PPMP", "APP", "SPP"].includes(activePlanType)
        ? activePlanType
        : this.normalizedPlanType(plan?.plan_type);
      this.timelineModal.plan = {
        ...plan,
        plan_type: this.timelineModal.planType,
      };
      this.timelineModal.show = true;
    },
    openPlanChat(item) {
      if (item?.id) {
        this.selectedRow = item.id;
      }

      this.$refs.planChat?.openPlan(item);
    },
    openNotificationPlanChat() {
      const planId = Number(this.pendingNotificationPlanId || 0);

      if (!planId || this.pendingNotificationChatOpened) {
        return;
      }

      const matchingPlan = this.lists.find((item) => Number(item.id) === planId);
      const plan = matchingPlan || {
        id: planId,
        plan_type: this.filter.plan_type,
        ppmp_no: "Procurement Plan",
      };

      this.selectedRow = planId;

      this.$nextTick(() => {
        const chat = this.$refs.planChat;

        if (!chat) {
          return;
        }

        chat.openPlan(plan);
        this.pendingNotificationChatOpened = true;
        this.pendingNotificationPlanId = null;
        this.pendingNotificationCommentId = null;
        this.clearNotificationChatParams();
      });
    },
    hydrateNotificationChatParams(fetchAfterHydrate = false) {
      const url = new URL(
        this.$page?.url || window.location.href,
        window.location.origin
      );
      const params = url.searchParams;
      const planId = params.get("comment_plan_id");

      if (!planId) {
        return;
      }

      const requestedPlanType = params.get("plan_type");
      const nextPlanType = requestedPlanType
        ? this.normalizedPlanType(requestedPlanType)
        : this.filter.plan_type;
      const shouldFetch = fetchAfterHydrate && nextPlanType !== this.filter.plan_type;

      this.pendingNotificationPlanId = planId;
      this.pendingNotificationCommentId = params.get("comment_id");
      this.pendingNotificationChatOpened = false;

      if (
        ["PPMP", "APP", "SPP"].includes(nextPlanType) &&
        nextPlanType !== this.filter.plan_type
      ) {
        this.activatePlanTab(nextPlanType);
      }

      if (shouldFetch) {
        this.fetch();
        return;
      }

      this.openNotificationPlanChat();
    },
    clearNotificationChatParams() {
      const url = new URL(window.location.href);

      url.searchParams.delete("comment_plan_id");
      url.searchParams.delete("comment_id");
      window.history.replaceState({}, "", url.toString());
    },
    setPlanTab(planType) {
      if (this.filter.plan_type === planType) {
        return;
      }

      this.activatePlanTab(planType);
      this.selectedRow = null;
      this.fetch();
    },
    activatePlanTab(planType) {
      this.filter.plan_type = planType;
      this.persistPlanTab(planType);
    },
    initialPlanType() {
      const params = new URLSearchParams(window.location.search);
      const requestedPlanType = params.get("plan_type");
      const queryPlanType = requestedPlanType
        ? this.normalizedPlanType(requestedPlanType)
        : null;

      if (["PPMP", "APP", "SPP"].includes(queryPlanType)) {
        this.persistPlanTab(queryPlanType);
        return queryPlanType;
      }

      const storedPlanType = this.normalizedPlanType(
        localStorage.getItem("procurement_ppmp_plan_type")
      );

      if (storedPlanType === "APP") return "APP";
      if (storedPlanType === "ALL") return "ALL";
      return "ALL";
    },
    persistPlanTab(planType) {
      localStorage.setItem("procurement_ppmp_plan_type", planType);

      const url = new URL(window.location.href);
      url.searchParams.set("plan_type", planType);
      window.history.replaceState({}, "", url.toString());
    },
    openCreatePlanModal() {
      if (this.appIsApprovedThisYear) {
        this.openCreateSppModal();
      } else {
        this.openCreatePpmpModal();
      }
    },
    openCreatePpmpModal() {
      const now = new Date();
      const currentYear = now.getFullYear();
      const currentQuarter = Math.ceil((now.getMonth() + 1) / 3);

      this.createPpmpForm.clearErrors();
      this.createPpmpForm.unit_id = this.defaultUserUnitId;
      this.createPpmpForm.year = currentYear;
      this.createPpmpForm.quarter = currentQuarter;
      this.createPpmpModal.show = true;
      this.fetchAvailablePpmpUnits();
    },
    closeCreatePpmpModal() {
      this.createPpmpModal.show = false;
      this.createPpmpForm.clearErrors();
      this.createPpmpForm.attachment_file = null;
    },
    autoSetRequestedBy(form, unitOptions, unitId) {
      const unit = unitOptions.find((u) => Number(u.value) === Number(unitId));
      if (!unit?.users?.length) {
        form.requested_by_id = null;
        return;
      }
      const head = unit.users.find((u) => u.is_head) ?? unit.users[0];
      form.requested_by_id = head?.value ?? null;
    },
    autoSetFirstAvailableQuarter(unitId) {
      const unit = this.availablePpmpUnits.find((u) => Number(u.value) === Number(unitId));
      const taken = unit?.taken_quarters ?? [];
      const currentQuarter = Math.ceil((new Date().getMonth() + 1) / 3);
      const all = [1, 2, 3, 4];
      // Keep current selection if still available, otherwise pick first free quarter
      if (!taken.includes(this.createPpmpForm.quarter)) return;
      const firstFree = all.find((q) => !taken.includes(q)) ?? currentQuarter;
      this.createPpmpForm.quarter = firstFree;
    },
    openCreateAppModal() {
      this.createAppForm.clearErrors();
      const currentYear = new Date().getFullYear();
      this.createAppForm.year = currentYear;
      this.createAppForm.plan_type = "APP";
      // Default to Indicative if no Indicative APP yet, otherwise Final
      const hasIndicativeThisYear = this.indicativeAppYears.includes(currentYear);
      this.createAppForm.plan_phase = hasIndicativeThisYear ? "final" : "indicative";
      this.createAppModal.show = true;
    },
    openApproveToAppModal(data) {
      if (!data?.id || this.approveAppForm.processing) {
        return;
      }

      this.approveAppForm.clearErrors();
      this.approveAppModal.error = "";
      this.approveAppModal.data = data;
      this.approveAppModal.show = true;
    },
    closeApproveToAppModal() {
      if (this.approveAppForm.processing) {
        return;
      }

      this.approveAppModal.show = false;
      this.approveAppModal.data = null;
      this.approveAppModal.error = "";
      this.approveAppForm.clearErrors();
    },
    approveToApp(review = {}) {
      const data = this.approveAppModal.data;
      if (!data?.id || this.approveAppForm.processing) {
        return;
      }

      this.approveAppForm.option = "approve_to_app";
      this.approveAppForm.plan_type = data?.plan_type || "PPMP";
      this.approveAppForm.consolidation_review_acknowledged = Boolean(review.consolidation_review_acknowledged);
      this.approveAppForm.consolidation_pricing = review.consolidation_pricing || [];
      this.approveAppForm.patch(`/faims/procurement-ppmp/${data.id}`, {
        preserveScroll: true,
        onSuccess: () => {
          this.approveAppModal.show = false;
          this.approveAppModal.data = null;
          this.approveAppModal.error = "";
          this.fetch();
        },
        onError: (errors) => {
          this.approveAppModal.error = this.firstFormError(errors);
        },
      });
    },
    openRevertStatusModal(data) {
      if (!data?.id || this.revertStatusForm.processing) {
        return;
      }
      this.revertStatusForm.clearErrors();
      this.revertStatusModal.data = data;
      this.revertStatusModal.error = "";
      this.revertStatusModal.show = true;
    },
    closeRevertStatusModal() {
      if (this.revertStatusForm.processing) {
        return;
      }
      this.revertStatusModal.show = false;
      this.revertStatusModal.data = null;
      this.revertStatusModal.error = "";
      this.revertStatusForm.clearErrors();
    },
    revertStatus(reason) {
      const data = this.revertStatusModal.data;
      if (!data?.id || this.revertStatusForm.processing) {
        return;
      }
      this.revertStatusForm.option = "revert_status";
      this.revertStatusForm.plan_type = data.plan_type ||
        (this.filter.plan_type === "ALL" ? "PPMP" : this.filter.plan_type);
      this.revertStatusForm.revert_reason = reason;
      this.revertStatusForm.patch(`/faims/procurement-ppmp/${data.id}`, {
        preserveScroll: true,
        onSuccess: () => {
          this.revertStatusModal.show = false;
          this.revertStatusModal.data = null;
          this.revertStatusModal.error = "";
          this.fetch();
        },
        onError: (errors) => {
          this.revertStatusModal.error = this.firstFormError(errors);
        },
      });
    },
    closeCreateAppModal() {
      this.createAppModal.show = false;
      this.createAppForm.clearErrors();
    },
    openCreateSppModal() {
      this.createSppForm.clearErrors();
      this.createSppForm.year = this.currentYear;
      this.createSppForm.plan_type = "SPP";
      this.fetchAvailableSppUnits();
      this.createSppForm.unit_id = this.defaultUnitExistsInOptions(this.availableSppUnits)
        ? this.defaultUserUnitId
        : null;
      this.createSppModal.show = true;
    },
    defaultUnitExistsInOptions(options = this.unitOptions) {
      return Boolean(
        this.defaultUserUnitId &&
          options.some((unit) => Number(unit.value) === this.defaultUserUnitId)
      );
    },
    closeCreateSppModal() {
      this.createSppModal.show = false;
      this.createSppForm.clearErrors();
      this.createSppForm.attachment_file = null;
    },
    openAddSppItemModal(item) {
      this.addSppItemModal.ppmp = item;
      this.$nextTick(() => {
        this.$refs.addSppItemModal?.show();
      });
    },
    fetchAvailablePpmpUnits() {
      this.createPpmpModal.loading = true;

      axios
        .get("/faims/procurement-ppmp", {
          params: {
            option: "available_units",
            year: this.createPpmpForm.year,
          },
        })
        .then((response) => {
          this.availablePpmpUnits = Array.isArray(response.data) ? response.data : [];
          if (
            !this.createPpmpForm.unit_id &&
            this.defaultUnitExistsInOptions(this.availablePpmpUnits)
          ) {
            this.createPpmpForm.unit_id = this.defaultUserUnitId;
          }
          if (
            !this.availablePpmpUnits.some(
              (unit) => Number(unit.value) === Number(this.createPpmpForm.unit_id)
            )
          ) {
            this.createPpmpForm.unit_id = null;
          }
        })
        .catch((error) => {
          console.log(error);
          this.availablePpmpUnits = [];
        })
        .finally(() => {
          this.createPpmpModal.loading = false;
        });
    },
    fetchAvailableSppUnits() {
      axios
        .get("/faims/procurement-ppmp", {
          params: {
            option: "available_spp_units",
            year: this.createSppForm.year || this.currentYear,
          },
        })
        .then((response) => {
          this.availableSppUnits = Array.isArray(response.data) ? response.data : [];
          if (
            !this.createSppForm.unit_id &&
            this.defaultUnitExistsInOptions(this.availableSppUnits)
          ) {
            this.createSppForm.unit_id = this.defaultUserUnitId;
          }
          if (
            !this.availableSppUnits.some(
              (unit) => Number(unit.value) === Number(this.createSppForm.unit_id)
            )
          ) {
            this.createSppForm.unit_id = null;
          }
        })
        .catch((error) => {
          console.log(error);
          this.availableSppUnits = [];
        });
    },
    submitCreatePpmp() {
      this.createPpmpForm.option = "create_ppmp";

      this.createPpmpForm.post("/faims/procurement-ppmp", {
        preserveScroll: true,
        onSuccess: () => {
          this.closeCreatePpmpModal();
          this.filter.unit = null;
          this.activatePlanTab("PPMP");
          this.fetch();
        },
      });
    },
    submitCreateApp() {
      if (!this.createAppForm.year) {
        this.createAppForm.setError("year", "Please select a plan year.");
        return;
      }

      if (!this.createAppForm.plan_phase) {
        this.createAppForm.setError("plan_phase", "Please select the APP phase.");
        return;
      }

      this.createAppForm.plan_type = "APP";

      this.createAppForm.post("/faims/procurement-ppmp", {
        preserveScroll: true,
        onSuccess: () => {
          this.annualAppYearCache = [
            ...new Set([
              ...(this.annualAppYearCache || this.annualAppYears),
              Number(this.createAppForm.year),
            ]),
          ];
          this.closeCreateAppModal();
          this.activatePlanTab("APP");
          this.filter.unit = null;
          this.fetch();
        },
      });
    },
    submitCreateSpp() {
      this.createSppForm.plan_type = "SPP";

      this.createSppForm.post("/faims/procurement-ppmp", {
        preserveScroll: true,
        onSuccess: () => {
          this.closeCreateSppModal();
          this.fetchAvailableSppUnits();
          this.activatePlanTab("SPP");
          this.filter.unit = null;
          this.fetch();
        },
      });
    },
  },
};
</script>

<style scoped>

.ppmp-index-page {
  --ppmp-bg: #f3f6ff;
  --ppmp-card: #ffffff;
  --ppmp-card-soft: #f8fafc;
  --ppmp-card-strong: #eef2ff;
  --ppmp-filter-bg: #ffffff;
  --ppmp-header: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
  --ppmp-border: rgba(91, 105, 153, 0.14);
  --ppmp-ink: #111827;
  --ppmp-muted: #4b5563;
  --ppmp-input: #ffffff;
  --ppmp-input-hover: #f8fafc;
  --ppmp-hover: rgba(64, 81, 137, 0.06);
  --ppmp-active: rgba(64, 81, 137, 0.1);
  --ppmp-shadow: rgba(31, 45, 92, 0.08);
  padding: 0.5rem;
  background: var(--ppmp-bg);
}

.ppmp-shell {
  overflow: hidden;
  border-color: var(--ppmp-border) !important;
  border-radius: 16px;
  background: var(--ppmp-card);
  color: var(--ppmp-ink);
  box-shadow: 0 16px 34px var(--ppmp-shadow) !important;
}

.ppmp-shell__header,
.ppmp-filter-panel,
.ppmp-table-panel {
  background: var(--ppmp-card) !important;
  color: var(--ppmp-ink);
}

.ppmp-shell__header {
  background: var(--ppmp-header) !important;
  border-bottom-color: var(--ppmp-border);
}

.ppmp-filter-panel {
  padding: 0.75rem 0.75rem 0.55rem;
  border-color: var(--ppmp-border) !important;
}

.ppmp-table-panel {
  padding: 0.65rem;
}

.ppmp-list-card {
  border: 0;
  background: var(--ppmp-card);
}

.ppmp-list-scroll {
  margin-left: 1rem;
  margin-right: 1rem;
  min-height: 360px;
  max-height: calc(100vh - 306px);
  overflow: auto;
  border: 1px solid var(--ppmp-border);
  border-radius: 12px;
  background: var(--ppmp-card);
}

.ppmp-index-page .input-group-text,
.ppmp-index-page .form-control,
.ppmp-index-page .form-select,
.ppmp-index-page :deep(.multiselect) {
  border-color: var(--ppmp-border);
  background: var(--ppmp-filter-bg);
  color: var(--ppmp-ink);
}

.ppmp-index-page .form-control::placeholder {
  color: var(--ppmp-muted);
  opacity: 1;
}

.ppmp-index-page .form-control:focus,
.ppmp-index-page .form-select:focus {
  border-color: rgba(64, 81, 137, 0.42);
  box-shadow: 0 0 0 0.16rem rgba(64, 81, 137, 0.12);
}

.ppmp-index-page :deep(.multiselect-wrapper),
.ppmp-index-page :deep(.multiselect-tags),
.ppmp-index-page :deep(.multiselect-search),
.ppmp-index-page :deep(.multiselect-single-label) {
  background: var(--ppmp-filter-bg);
  color: var(--ppmp-ink);
}

.ppmp-index-page :deep(.multiselect-placeholder) {
  color: var(--ppmp-muted);
}

.ppmp-index-page :deep(.multiselect-dropdown) {
  border-color: var(--ppmp-border);
  background: var(--ppmp-input);
  color: var(--ppmp-ink);
}

.ppmp-index-page :deep(.multiselect-option) {
  color: var(--ppmp-ink);
}

.ppmp-index-page :deep(.multiselect-option.is-pointed) {
  background: var(--ppmp-input-hover);
  color: var(--ppmp-ink);
}

.ppmp-index-page :deep(.multiselect-option.is-selected),
.ppmp-index-page :deep(.multiselect-option.is-selected.is-pointed) {
  background: var(--ppmp-active);
  color: var(--ppmp-ink);
}

.ppmp-index-page .nav-tabs-custom .nav-link {
  color: var(--ppmp-muted);
  border-color: transparent;
  background: transparent;
}

.ppmp-index-page .nav-tabs-custom .nav-link.active {
  color: #405189;
  background: var(--ppmp-card);
  border-color: var(--ppmp-border) var(--ppmp-border) var(--ppmp-card);
}

.ppmp-index-page .fw-medium,
.ppmp-index-page .fw-semibold,
.ppmp-index-page .fw-bold,
.ppmp-index-page td,
.ppmp-index-page th {
  color: var(--ppmp-ink);
}

.ppmp-index-page .text-primary {
  color: #2563eb !important;
}

.ppmp-index-page .text-muted {
  color: var(--ppmp-muted) !important;
}

.ppmp-table {
  --bs-table-bg: transparent;
  --bs-table-color: var(--ppmp-ink);
  --bs-table-hover-bg: var(--ppmp-hover);
  --bs-table-hover-color: var(--ppmp-ink);
  color: var(--ppmp-ink);
}

.ppmp-table > :not(caption) > * > * {
  padding: 0.48rem 0.55rem;
}

.ppmp-table thead th {
  position: sticky;
  top: 0;
  z-index: 2;
  background: #eef2f7;
  color: #111827;
  border-color: var(--ppmp-border);
}

.ppmp-table tbody td {
  color: var(--ppmp-ink);
  border-color: var(--ppmp-border);
}

.ppmp-table tbody tr.table-active > * {
  --bs-table-bg-state: var(--ppmp-active);
  color: var(--ppmp-ink);
}

.ppmp-index-page .card-footer {
  background: var(--ppmp-card);
  border-color: var(--ppmp-border);
}

.ppmp-index-page .btn-icon {
  width: 30px;
  height: 30px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0;
}

.ppmp-index-page .input-group {
  flex-wrap: nowrap;
}

.ppmp-index-page .input-group > .btn,
.ppmp-index-page .input-group > .input-group-text {
  flex: 0 0 auto;
}

.ppmp-action-group {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.28rem;
  white-space: nowrap;
}

.ppmp-chat-row-count {
  min-width: 18px;
  font-size: 10px;
  line-height: 1.1;
}

.ppmp-confirm {
  display: grid;
  grid-template-columns: 44px minmax(0, 1fr);
  gap: 0.85rem;
  color: var(--ppmp-ink);
}

.ppmp-confirm__icon {
  width: 44px;
  height: 44px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 14px;
  background: rgba(10, 179, 156, 0.14);
  color: #0ab39c;
  font-size: 1.35rem;
}

.ppmp-confirm__summary {
  grid-column: 1 / -1;
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 0.5rem;
}

.ppmp-confirm__summary > div {
  padding: 0.65rem;
  border: 1px solid var(--ppmp-border);
  border-radius: 12px;
  background: var(--ppmp-card-soft);
}

.ppmp-confirm__summary span {
  display: block;
  color: var(--ppmp-muted);
  font-size: 0.72rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.ppmp-confirm__summary strong {
  display: block;
  margin-top: 0.18rem;
  color: var(--ppmp-ink);
  font-size: 0.9rem;
  line-height: 1.25;
}

@media (max-width: 1199.98px) {
  .ppmp-index-page .input-group {
    display: grid;
    grid-template-columns: 42px minmax(220px, 1fr) repeat(3, minmax(150px, 0.55fr)) auto auto;
    gap: 0.35rem;
  }

  .ppmp-index-page .input-group > * {
    width: 100% !important;
    border-radius: 0.375rem !important;
  }
}

@media (max-width: 767.98px) {
  .ppmp-index-page .input-group {
    grid-template-columns: 1fr;
  }

  .ppmp-list-scroll {
    margin-left: 0.5rem;
    margin-right: 0.5rem;
    max-height: none;
  }

  .ppmp-confirm,
  .ppmp-confirm__summary {
    grid-template-columns: 1fr;
  }
}

:global([data-bs-theme="dark"]) .ppmp-index-page {
  --ppmp-bg: #0b1220;
  --ppmp-card: #151e33;
  --ppmp-card-soft: #10192c;
  --ppmp-card-strong: #1d2942;
  --ppmp-header: linear-gradient(180deg, #172136 0%, #121b30 100%);
  --ppmp-border: rgba(170, 184, 220, 0.16);
  --ppmp-ink: #e8edf9;
  --ppmp-muted: #9aa8c7;
  --ppmp-input: #0f1728;
  --ppmp-input-hover: #1a2540;
  --ppmp-hover: rgba(142, 164, 255, 0.1);
  --ppmp-active: rgba(142, 164, 255, 0.18);
  --ppmp-shadow: rgba(0, 0, 0, 0.24);
}

:global([data-bs-theme="dark"]) .ppmp-index-page .card,
:global([data-bs-theme="dark"]) .ppmp-index-page .card-body,
:global([data-bs-theme="dark"]) .ppmp-index-page .card-header,
:global([data-bs-theme="dark"]) .ppmp-index-page .table-responsive,
:global([data-bs-theme="dark"]) .ppmp-index-page .table-card {
  background: var(--ppmp-card) !important;
  color: var(--ppmp-ink);
  border-color: var(--ppmp-border) !important;
}

:global([data-bs-theme="dark"]) .ppmp-index-page .bg-white,
:global([data-bs-theme="dark"]) .ppmp-index-page .bg-light,
:global([data-bs-theme="dark"]) .ppmp-index-page .bg-light-subtle,
:global([data-bs-theme="dark"]) .ppmp-index-page .table-light {
  background-color: var(--ppmp-card-soft) !important;
  color: var(--ppmp-ink) !important;
}

:global([data-bs-theme="dark"]) .ppmp-index-page .text-body,
:global([data-bs-theme="dark"]) .ppmp-index-page .text-primary {
  color: var(--ppmp-ink) !important;
}

:global([data-bs-theme="dark"]) .ppmp-index-page .text-muted {
  color: var(--ppmp-muted) !important;
}

:global([data-bs-theme="dark"]) .ppmp-index-page .table,
:global([data-bs-theme="dark"]) .ppmp-index-page .table > :not(caption) > * > * {
  --bs-table-bg: transparent;
  --bs-table-color: var(--ppmp-ink);
  --bs-table-hover-bg: var(--ppmp-hover);
  --bs-table-hover-color: var(--ppmp-ink);
  color: var(--ppmp-ink);
  border-color: var(--ppmp-border);
}

:global([data-bs-theme="dark"]) .ppmp-index-page .table-active,
:global([data-bs-theme="dark"]) .ppmp-index-page .table-active > * {
  --bs-table-bg-state: rgba(142, 164, 255, 0.14);
  color: var(--ppmp-ink);
}

:global([data-bs-theme="dark"]) .ppmp-index-page .input-group-text,
:global([data-bs-theme="dark"]) .ppmp-index-page .form-control,
:global([data-bs-theme="dark"]) .ppmp-index-page .form-select,
:global([data-bs-theme="dark"]) .ppmp-index-page :deep(.multiselect),
:global([data-bs-theme="dark"]) .ppmp-index-page :deep(.multiselect-wrapper),
:global([data-bs-theme="dark"]) .ppmp-index-page :deep(.multiselect-tags),
:global([data-bs-theme="dark"]) .ppmp-index-page :deep(.multiselect-dropdown),
:global([data-bs-theme="dark"]) .ppmp-index-page :deep(.multiselect-options),
:global([data-bs-theme="dark"]) .ppmp-index-page :deep(.multiselect-search),
:global([data-bs-theme="dark"]) .ppmp-index-page :deep(.multiselect-single-label) {
  background: var(--ppmp-input) !important;
  border-color: var(--ppmp-border) !important;
  color: var(--ppmp-ink) !important;
}

:global([data-bs-theme="dark"]) .ppmp-index-page .form-control::placeholder,
:global([data-bs-theme="dark"]) .ppmp-index-page :deep(.multiselect-placeholder) {
  color: var(--ppmp-muted) !important;
  opacity: 1;
}

:global([data-bs-theme="dark"]) .ppmp-index-page :deep(.multiselect-option) {
  background: var(--ppmp-input) !important;
  color: var(--ppmp-ink) !important;
}

:global([data-bs-theme="dark"]) .ppmp-index-page :deep(.multiselect-option.is-pointed) {
  background: var(--ppmp-input-hover) !important;
  color: var(--ppmp-ink) !important;
}

:global([data-bs-theme="dark"]) .ppmp-index-page :deep(.multiselect-option.is-selected),
:global([data-bs-theme="dark"])
  .ppmp-index-page
  :deep(.multiselect-option.is-selected.is-pointed) {
  background: var(--ppmp-active) !important;
  color: var(--ppmp-ink) !important;
}

:global([data-bs-theme="dark"]) .ppmp-index-page .nav-tabs-custom {
  border-bottom-color: var(--ppmp-border);
}

:global([data-bs-theme="dark"]) .ppmp-index-page .nav-tabs-custom .nav-link {
  color: var(--ppmp-muted);
}

:global([data-bs-theme="dark"]) .ppmp-index-page .nav-tabs-custom .nav-link.active {
  background: var(--ppmp-card-strong);
  border-color: var(--ppmp-border) var(--ppmp-border) var(--ppmp-card-strong);
  color: var(--ppmp-ink) !important;
}

.ppmp-index-page .ppmp-filter-panel,
.ppmp-index-page .ppmp-table-panel,
.ppmp-index-page .ppmp-list-scroll,
.ppmp-index-page .ppmp-list-card,
.ppmp-index-page .ppmp-table,
.ppmp-index-page .ppmp-table tbody,
.ppmp-index-page .ppmp-table tr,
.ppmp-index-page .ppmp-table td {
  background-color: var(--ppmp-card) !important;
  color: var(--ppmp-ink) !important;
}

.ppmp-index-page .ppmp-table thead,
.ppmp-index-page .ppmp-table thead tr,
.ppmp-index-page .ppmp-table thead th {
  background-color: var(--ppmp-card-soft) !important;
  color: var(--ppmp-ink) !important;
}

.ppmp-index-page .ppmp-table .fw-medium,
.ppmp-index-page .ppmp-table .fw-semibold,
.ppmp-index-page .ppmp-table .text-truncate,
.ppmp-index-page .ppmp-table .text-truncate-two-lines,
.ppmp-index-page .ppmp-table small,
.ppmp-index-page .ppmp-table span,
.ppmp-index-page .ppmp-table div {
  color: inherit !important;
}

.ppmp-index-page .ppmp-table .text-primary {
  color: #2563eb !important;
}

.ppmp-index-page .ppmp-table .text-muted,
.ppmp-index-page .ppmp-subtitle,
.ppmp-index-page p.text-muted {
  color: var(--ppmp-muted) !important;
}

.ppmp-index-page .ppmp-table :deep(.badge) {
  color: #ffffff !important;
}

.ppmp-index-page .ppmp-table :deep(.bg-warning),
.ppmp-index-page .ppmp-table :deep(.text-bg-warning) {
  color: #111827 !important;
}

.ppmp-index-page .ppmp-shell__header,
.ppmp-index-page .ppmp-shell__header *,
.ppmp-index-page .ppmp-filter-panel,
.ppmp-index-page .ppmp-filter-panel * {
  color: var(--ppmp-ink) !important;
}

.ppmp-index-page .ppmp-shell__header .text-muted,
.ppmp-index-page .ppmp-shell__header p,
.ppmp-index-page .ppmp-filter-panel .text-muted,
.ppmp-index-page .nav-tabs-custom .nav-link:not(.active) {
  color: var(--ppmp-muted) !important;
}

.ppmp-index-page .avatar-title {
  background-color: var(--ppmp-card-strong) !important;
  border: 1px solid var(--ppmp-border);
}

.ppmp-index-page .avatar-title i {
  color: #2563eb !important;
}

.ppmp-index-page .nav-tabs-custom {
  background: var(--ppmp-card-soft);
  border-color: var(--ppmp-border);
}

.ppmp-index-page .nav-tabs-custom .nav-link.active {
  color: #2563eb !important;
  background: var(--ppmp-card) !important;
}

.ppmp-index-page .input-group-text.search-icon,
.ppmp-index-page .search-icon,
.ppmp-index-page .input-group-text i {
  color: var(--ppmp-ink) !important;
}

.ppmp-index-page .input-group-text,
.ppmp-index-page .form-control,
.ppmp-index-page .form-select,
.ppmp-index-page :deep(.multiselect),
.ppmp-index-page :deep(.multiselect-wrapper),
.ppmp-index-page :deep(.multiselect-tags),
.ppmp-index-page :deep(.multiselect-search),
.ppmp-index-page :deep(.multiselect-single-label) {
  background-color: var(--ppmp-filter-bg, var(--ppmp-input)) !important;
  color: var(--ppmp-ink) !important;
  border-color: var(--ppmp-border) !important;
}

:global([data-bs-theme="dark"]) .ppmp-index-page {
  --ppmp-filter-bg: var(--ppmp-input);
}

:global([data-bs-theme="dark"]) .ppmp-index-page .ppmp-table .text-primary {
  color: #93c5fd !important;
}

:global([data-bs-theme="dark"]) .ppmp-index-page .avatar-title i,
:global([data-bs-theme="dark"]) .ppmp-index-page .nav-tabs-custom .nav-link.active {
  color: #93c5fd !important;
}

/* Match the Procurement Management list shell across PPMP, APP, and SPP tabs. */
.ppmp-index-page {
  background: transparent !important;
  padding: 0 !important;
}

.ppmp-index-page .ppmp-shell {
  border-radius: 4px !important;
  box-shadow: none !important;
}

.ppmp-index-page .ppmp-shell__header {
  min-height: 82px;
  padding: 1rem 1.25rem !important;
}

.ppmp-index-page .ppmp-filter-panel {
  padding: 1rem 1.25rem !important;
}

.ppmp-index-page .ppmp-filter-panel .px-3 {
  padding-left: 0 !important;
  padding-right: 0 !important;
}

.ppmp-index-page .nav-tabs-custom {
  border: 0 !important;
  border-radius: 0 !important;
  padding: 0 !important;
}

.ppmp-index-page .nav-tabs-custom .nav-link {
  border-radius: 0 !important;
  padding: 0.8rem 1.25rem !important;
}

.ppmp-index-page .ppmp-table-panel {
  padding: 1rem 0 0 !important;
}

.ppmp-index-page .ppmp-list-scroll {
  border: 0 !important;
  border-radius: 0 !important;
  min-height: 0 !important;
  max-height: none !important;
}

.ppmp-index-page .ppmp-table {
  --bs-table-bg: transparent !important;
}

.ppmp-index-page .ppmp-table thead th {
  font-size: 0.78rem;
  font-weight: 700;
}

.ppmp-index-page .ppmp-table > :not(caption) > * > * {
  padding: 1rem 0.75rem !important;
}

:global([data-bs-theme="light"]) .ppmp-index-page,
:global([data-bs-theme="light"]) .ppmp-index-page .ppmp-shell,
:global([data-bs-theme="light"]) .ppmp-index-page .ppmp-shell__header,
:global([data-bs-theme="light"]) .ppmp-index-page .ppmp-filter-panel,
:global([data-bs-theme="light"]) .ppmp-index-page .ppmp-table-panel,
:global([data-bs-theme="light"]) .ppmp-index-page .ppmp-list-scroll,
:global([data-bs-theme="light"]) .ppmp-index-page .ppmp-list-card,
:global([data-bs-theme="light"]) .ppmp-index-page .ppmp-table,
:global([data-bs-theme="light"]) .ppmp-index-page .ppmp-table tbody,
:global([data-bs-theme="light"]) .ppmp-index-page .ppmp-table tr,
:global([data-bs-theme="light"]) .ppmp-index-page .ppmp-table td {
  background: #ffffff !important;
  color: #111827 !important;
}

:global([data-bs-theme="light"]) .ppmp-index-page .ppmp-table thead,
:global([data-bs-theme="light"]) .ppmp-index-page .ppmp-table thead tr,
:global([data-bs-theme="light"]) .ppmp-index-page .ppmp-table thead th {
  background: #f3f6f9 !important;
  color: #111827 !important;
}

:global([data-bs-theme="dark"]) .ppmp-index-page,
:global([data-bs-theme="dark"]) .ppmp-index-page .ppmp-shell,
:global([data-bs-theme="dark"]) .ppmp-index-page .ppmp-shell__header,
:global([data-bs-theme="dark"]) .ppmp-index-page .ppmp-filter-panel,
:global([data-bs-theme="dark"]) .ppmp-index-page .ppmp-table-panel,
:global([data-bs-theme="dark"]) .ppmp-index-page .ppmp-list-scroll,
:global([data-bs-theme="dark"]) .ppmp-index-page .ppmp-list-card,
:global([data-bs-theme="dark"]) .ppmp-index-page .ppmp-table,
:global([data-bs-theme="dark"]) .ppmp-index-page .ppmp-table tbody,
:global([data-bs-theme="dark"]) .ppmp-index-page .ppmp-table tr,
:global([data-bs-theme="dark"]) .ppmp-index-page .ppmp-table td {
  background: #212a36 !important;
  border-color: #354052 !important;
  color: #f3f6fb !important;
}

:global([data-bs-theme="dark"]) .ppmp-index-page .ppmp-filter-panel,
:global([data-bs-theme="dark"]) .ppmp-index-page .nav-tabs-custom,
:global([data-bs-theme="dark"]) .ppmp-index-page .input-group-text,
:global([data-bs-theme="dark"]) .ppmp-index-page .form-control,
:global([data-bs-theme="dark"]) .ppmp-index-page .form-select,
:global([data-bs-theme="dark"]) .ppmp-index-page :deep(.multiselect),
:global([data-bs-theme="dark"]) .ppmp-index-page :deep(.multiselect-wrapper),
:global([data-bs-theme="dark"]) .ppmp-index-page :deep(.multiselect-tags),
:global([data-bs-theme="dark"]) .ppmp-index-page :deep(.multiselect-search),
:global([data-bs-theme="dark"]) .ppmp-index-page :deep(.multiselect-single-label) {
  background: #252f3d !important;
  border-color: #3a4658 !important;
  color: #f3f6fb !important;
}

:global([data-bs-theme="dark"]) .ppmp-index-page .ppmp-table thead,
:global([data-bs-theme="dark"]) .ppmp-index-page .ppmp-table thead tr,
:global([data-bs-theme="dark"]) .ppmp-index-page .ppmp-table thead th {
  background: #2d3848 !important;
  border-color: #3a4658 !important;
  color: #ffffff !important;
}

:global([data-bs-theme="dark"]) .ppmp-index-page .text-muted,
:global([data-bs-theme="dark"]) .ppmp-index-page .ppmp-table .text-muted,
:global([data-bs-theme="dark"]) .ppmp-index-page small {
  color: #b8c3d6 !important;
}

:global([data-bs-theme="dark"]) .ppmp-index-page .text-body,
:global([data-bs-theme="dark"]) .ppmp-index-page .fw-medium,
:global([data-bs-theme="dark"]) .ppmp-index-page .fw-semibold,
:global([data-bs-theme="dark"]) .ppmp-index-page .fw-bold,
:global([data-bs-theme="dark"]) .ppmp-index-page .ppmp-table div,
:global([data-bs-theme="dark"]) .ppmp-index-page .ppmp-table span {
  color: #f3f6fb !important;
}

:global([data-bs-theme="dark"]) .ppmp-index-page .ppmp-table .text-primary {
  color: #7eb6ff !important;
}

:global([data-bs-theme="dark"]) .ppmp-index-page .nav-tabs-custom .nav-link {
  background: transparent !important;
  color: #b8c3d6 !important;
}

:global([data-bs-theme="dark"]) .ppmp-index-page .nav-tabs-custom .nav-link.active {
  background: #1c2533 !important;
  color: #7eb6ff !important;
}

.ppmp-document-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-bottom: 2px solid #111;
  padding-bottom: 12px;
  margin-bottom: 16px;
}

.ppmp-header-logo {
  width: 82px;
  height: 82px;
  object-fit: contain;
}

.ppmp-title-block {
  text-align: center;
  margin-bottom: 16px;
}

.ppmp-line {
  display: inline-block;
  min-width: 86px;
  border-bottom: 3px solid #111;
}

.ppmp-status-check {
  display: inline-flex;
  align-items: center;
  font-size: 20px;
  font-weight: 800;
}

.ppmp-checkbox {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border: 1.5px solid #111;
  margin-right: 12px;
}

.ppmp-checkbox.checked::after {
  content: "✓";
  font-size: 24px;
  line-height: 1;
}

.ppmp-signatories {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 72px;
  margin-top: 64px;
}

.ppmp-signatory {
  text-align: center;
}

.ppmp-signatory-line {
  min-height: 26px;
  border-bottom: 1.5px solid #111;
  padding-bottom: 4px;
  font-weight: 700;
  text-transform: uppercase;
}

.ppmp-signatory-label {
  margin-top: 7px;
  font-size: 12px;
  font-weight: 700;
}
</style>

<style>
html[data-bs-theme="dark"] .ppmp-index-page,
html[data-bs-theme="dark"] .ppmp-index-page .card,
html[data-bs-theme="dark"] .ppmp-index-page .card-header,
html[data-bs-theme="dark"] .ppmp-index-page .card-body,
html[data-bs-theme="dark"] .ppmp-index-page .bg-white,
html[data-bs-theme="dark"] .ppmp-index-page .bg-light,
html[data-bs-theme="dark"] .ppmp-index-page .bg-light-subtle,
html[data-bs-theme="dark"] .ppmp-index-page .table-card,
html[data-bs-theme="dark"] .ppmp-index-page .table-responsive,
html[data-bs-theme="dark"] .ppmp-index-page .b-card,
html[data-bs-theme="dark"] .ppmp-index-page .ppmp-list-card {
  background-color: #212a36 !important;
  border-color: #354052 !important;
  color: #f3f6fb !important;
}

html[data-bs-theme="dark"] .ppmp-index-page .ppmp-filter-panel,
html[data-bs-theme="dark"] .ppmp-index-page .nav-tabs-custom,
html[data-bs-theme="dark"] .ppmp-index-page .input-group-text,
html[data-bs-theme="dark"] .ppmp-index-page .form-control,
html[data-bs-theme="dark"] .ppmp-index-page .form-select,
html[data-bs-theme="dark"] .ppmp-index-page .multiselect,
html[data-bs-theme="dark"] .ppmp-index-page .multiselect-wrapper,
html[data-bs-theme="dark"] .ppmp-index-page .multiselect-tags,
html[data-bs-theme="dark"] .ppmp-index-page .multiselect-search,
html[data-bs-theme="dark"] .ppmp-index-page .multiselect-single-label {
  background-color: #252f3d !important;
  border-color: #3a4658 !important;
  color: #f3f6fb !important;
}

html[data-bs-theme="dark"] .ppmp-index-page .form-control::placeholder,
html[data-bs-theme="dark"] .ppmp-index-page .multiselect-placeholder {
  color: #9aa8c7 !important;
  opacity: 1;
}

html[data-bs-theme="dark"] .ppmp-index-page .table,
html[data-bs-theme="dark"] .ppmp-index-page .table > :not(caption) > * > *,
html[data-bs-theme="dark"] .ppmp-index-page .table tbody,
html[data-bs-theme="dark"] .ppmp-index-page .table tr,
html[data-bs-theme="dark"] .ppmp-index-page .table td {
  --bs-table-bg: #212a36 !important;
  --bs-table-color: #f3f6fb !important;
  --bs-table-hover-bg: #273244 !important;
  --bs-table-hover-color: #ffffff !important;
  background-color: #212a36 !important;
  border-color: #354052 !important;
  color: #f3f6fb !important;
}

html[data-bs-theme="dark"] .ppmp-index-page .table-light,
html[data-bs-theme="dark"] .ppmp-index-page .table thead,
html[data-bs-theme="dark"] .ppmp-index-page .table thead tr,
html[data-bs-theme="dark"] .ppmp-index-page .table thead th {
  --bs-table-bg: #2d3848 !important;
  --bs-table-color: #ffffff !important;
  background-color: #2d3848 !important;
  border-color: #3a4658 !important;
  color: #ffffff !important;
}

html[data-bs-theme="dark"] .ppmp-index-page .table-active,
html[data-bs-theme="dark"] .ppmp-index-page .table-active > * {
  --bs-table-bg-state: #273244 !important;
  background-color: #273244 !important;
  color: #ffffff !important;
}

html[data-bs-theme="dark"] .ppmp-index-page .text-body,
html[data-bs-theme="dark"] .ppmp-index-page .fw-medium,
html[data-bs-theme="dark"] .ppmp-index-page .fw-semibold,
html[data-bs-theme="dark"] .ppmp-index-page .fw-bold,
html[data-bs-theme="dark"] .ppmp-index-page td,
html[data-bs-theme="dark"] .ppmp-index-page th,
html[data-bs-theme="dark"] .ppmp-index-page div,
html[data-bs-theme="dark"] .ppmp-index-page span {
  color: #f3f6fb !important;
}

html[data-bs-theme="dark"] .ppmp-index-page .text-muted,
html[data-bs-theme="dark"] .ppmp-index-page small,
html[data-bs-theme="dark"] .ppmp-index-page p {
  color: #b8c3d6 !important;
}

html[data-bs-theme="dark"] .ppmp-index-page .text-primary {
  color: #7eb6ff !important;
}

html[data-bs-theme="dark"] .ppmp-index-page .btn-primary,
html[data-bs-theme="dark"] .ppmp-index-page .btn-primary * {
  color: #ffffff !important;
}

.ppmp-index-page .btn-primary,
.ppmp-index-page .btn-primary *,
.ppmp-index-page .btn-success,
.ppmp-index-page .btn-success *,
.ppmp-index-page .btn-warning,
.ppmp-index-page .btn-warning * {
  color: #ffffff !important;
}

</style>
