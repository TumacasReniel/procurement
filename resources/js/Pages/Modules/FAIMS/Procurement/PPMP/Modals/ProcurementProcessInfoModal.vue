<template>
  <b-modal
    v-model="modalShow"
    size="xl"
    centered
    hide-footer
    header-class="border-bottom pb-2"
    modal-class="zoomIn"
    scrollable
  >
    <template #header>
      <div class="d-flex align-items-center gap-2 w-100">
        <span class="avatar-title bg-primary-subtle rounded p-2" style="width:2.2rem;height:2.2rem;display:inline-flex;align-items:center;justify-content:center;flex-shrink:0">
          <i class="ri-question-line text-primary fs-16"></i>
        </span>
        <div class="flex-grow-1">
          <div class="fw-bold fs-14">Procurement Process Guide</div>
          <div class="text-muted fs-12">Full cycle: PPMP → APP → PR → Bidding → NOA → NTP → PO → IAR — per RA 9184</div>
        </div>
        <button type="button" class="btn-close" @click="$emit('close')"></button>
      </div>
    </template>

    <div class="p-1">

      <!-- Legal basis banner -->
      <div class="alert alert-primary py-2 px-3 fs-12 mb-3 d-flex align-items-start gap-2">
        <i class="ri-scales-line fs-16 mt-1 flex-shrink-0"></i>
        <div>
          <strong>Legal Basis:</strong> Republic Act No. 9184 (Government Procurement Reform Act) and the
          2016 Revised IRR. Planning: <strong>Sec. 7</strong>; Bidding modes: <strong>Sec. 10–19</strong>;
          Evaluation &amp; Award: <strong>Sec. 29–37</strong>; Contract &amp; Delivery: <strong>Sec. 37–41</strong>.
        </div>
      </div>

      <!-- Flow strip -->
      <div class="proc-flow-strip mb-4">
        <div class="proc-flow-step proc-flow-step--ppmp">
          <div class="proc-flow-step__icon"><i class="ri-file-add-line"></i></div>
          <div class="proc-flow-step__label">PPMP<br><small>Planning</small></div>
        </div>
        <div class="proc-flow-arrow"><i class="ri-arrow-right-line"></i></div>
        <div class="proc-flow-step proc-flow-step--app">
          <div class="proc-flow-step__icon"><i class="ri-file-list-3-line"></i></div>
          <div class="proc-flow-step__label">APP<br><small>Consolidation</small></div>
        </div>
        <div class="proc-flow-arrow"><i class="ri-arrow-right-line"></i></div>
        <div class="proc-flow-step proc-flow-step--spp">
          <div class="proc-flow-step__icon"><i class="ri-file-add-fill"></i></div>
          <div class="proc-flow-step__label">SPP<br><small>Mid-year</small></div>
        </div>
        <div class="proc-flow-arrow"><i class="ri-arrow-right-line"></i></div>
        <div class="proc-flow-step proc-flow-step--rfq">
          <div class="proc-flow-step__icon"><i class="ri-price-tag-3-line"></i></div>
          <div class="proc-flow-step__label">RFQ / ITB<br><small>Bidding</small></div>
        </div>
        <div class="proc-flow-arrow"><i class="ri-arrow-right-line"></i></div>
        <div class="proc-flow-step proc-flow-step--bac">
          <div class="proc-flow-step__icon"><i class="ri-group-line"></i></div>
          <div class="proc-flow-step__label">BAC Reso<br><small>Resolution</small></div>
        </div>
        <div class="proc-flow-arrow"><i class="ri-arrow-right-line"></i></div>
        <div class="proc-flow-step proc-flow-step--noa">
          <div class="proc-flow-step__icon"><i class="ri-award-line"></i></div>
          <div class="proc-flow-step__label">NOA<br><small>Award</small></div>
        </div>
        <div class="proc-flow-arrow"><i class="ri-arrow-right-line"></i></div>
        <div class="proc-flow-step proc-flow-step--ntp">
          <div class="proc-flow-step__icon"><i class="ri-play-circle-line"></i></div>
          <div class="proc-flow-step__label">NTP<br><small>Proceed</small></div>
        </div>
        <div class="proc-flow-arrow"><i class="ri-arrow-right-line"></i></div>
        <div class="proc-flow-step proc-flow-step--po">
          <div class="proc-flow-step__icon"><i class="ri-shopping-cart-line"></i></div>
          <div class="proc-flow-step__label">PO<br><small>Issuance</small></div>
        </div>
        <div class="proc-flow-arrow"><i class="ri-arrow-right-line"></i></div>
        <div class="proc-flow-step proc-flow-step--iar">
          <div class="proc-flow-step__icon"><i class="ri-checkbox-circle-line"></i></div>
          <div class="proc-flow-step__label">IAR<br><small>Acceptance</small></div>
        </div>
      </div>

      <div class="row g-3">

        <!-- Phase 1 -->
        <div class="col-12">
          <div class="proc-section-label">
            <i class="ri-draft-line me-1"></i> Phase 1 — Procurement Planning
          </div>
        </div>

        <!-- PPMP -->
        <div class="col-md-6">
          <div class="proc-section proc-section--ppmp h-100">
            <div class="proc-section__header">
              <span class="proc-section__badge proc-section__badge--ppmp">PPMP</span>
              <div>
                <div class="proc-section__title">Project Procurement Management Plan</div>
                <div class="proc-section__subtitle">RA 9184 Sec. 7 — by each implementing unit</div>
              </div>
            </div>
            <div class="proc-section__body">
              <p class="fs-13 text-muted mb-2">
                Each office submits a PPMP listing all goods, services, and infrastructure they need
                for the year, with estimated schedule and budget. This feeds the APP.
              </p>
              <div class="row g-2 mb-2">
                <div class="col-6">
                  <div class="proc-phase-card proc-phase-card--indicative">
                    <div class="proc-phase-card__header">
                      <i class="ri-draft-line me-1"></i> Indicative
                      <span class="proc-phase-card__tag proc-phase-card__tag--warning">Pre-GAA</span>
                    </div>
                    <div class="fs-12 text-muted mt-1">Based on proposed budget. Created before GAA is signed.</div>
                  </div>
                </div>
                <div class="col-6">
                  <div class="proc-phase-card proc-phase-card--final">
                    <div class="proc-phase-card__header">
                      <i class="ri-flag-2-line me-1"></i> Final
                      <span class="proc-phase-card__tag proc-phase-card__tag--success">Post-GAA</span>
                    </div>
                    <div class="fs-12 text-muted mt-1">Based on approved budget. Unit head clicks <em>Mark as Final</em>.</div>
                  </div>
                </div>
              </div>
              <div class="proc-rule-box">
                <i class="ri-error-warning-line me-1 text-warning"></i>
                Indicative PPMPs → Indicative APP only. Final PPMPs → Final APP only.
              </div>
            </div>
          </div>
        </div>

        <!-- APP + SPP -->
        <div class="col-md-6">
          <div class="proc-section proc-section--app h-100">
            <div class="proc-section__header">
              <span class="proc-section__badge proc-section__badge--app">APP / SPP</span>
              <div>
                <div class="proc-section__title">Annual &amp; Supplemental Procurement Plan</div>
                <div class="proc-section__subtitle">RA 9184 Sec. 7.1 — consolidated agency plan</div>
              </div>
            </div>
            <div class="proc-section__body">
              <p class="fs-13 text-muted mb-2">
                Procurement Officer consolidates all unit PPMPs into one agency-wide plan approved by the HOPE.
              </p>
              <div class="row g-2 mb-2">
                <div class="col-6">
                  <div class="proc-phase-card proc-phase-card--indicative">
                    <div class="proc-phase-card__header"><i class="ri-draft-line me-1"></i> Indicative APP</div>
                    <div class="fs-12 text-muted mt-1">Pre-GAA transparency document. Cannot start actual procurement.</div>
                  </div>
                </div>
                <div class="col-6">
                  <div class="proc-phase-card proc-phase-card--final">
                    <div class="proc-phase-card__header"><i class="ri-file-list-3-line me-1"></i> Final APP</div>
                    <div class="fs-12 text-muted mt-1">Post-GAA. PhilGEPS posting within 30 days. Basis for all PRs.</div>
                  </div>
                </div>
              </div>
              <div class="proc-rule-box">
                <i class="ri-error-warning-line me-1 text-warning"></i>
                <strong>SPP</strong> (mid-year additions) can only be created after the Final APP is approved.
              </div>
            </div>
          </div>
        </div>

        <!-- Phase 2 -->
        <div class="col-12 mt-2">
          <div class="proc-section-label">
            <i class="ri-shopping-bag-line me-1"></i> Phase 2 — Procurement Execution
          </div>
        </div>

        <!-- RFQ / ITB -->
        <div class="col-md-6">
          <div class="proc-section proc-section--rfq h-100">
            <div class="proc-section__header">
              <span class="proc-section__badge proc-section__badge--rfq">RFQ / ITB</span>
              <div>
                <div class="proc-section__title">Quotation / Bidding</div>
                <div class="proc-section__subtitle">RA 9184 Sec. 10–19 — mode of procurement</div>
              </div>
            </div>
            <div class="proc-section__body">
              <div class="proc-phase-card proc-phase-card--indicative mb-2">
                <div class="proc-phase-card__header">RFQ — Small Value / Shopping</div>
                <div class="fs-12 text-muted mt-1">
                  Request for Quotation. Below the shopping threshold. At least 3 price quotations from registered suppliers.
                </div>
              </div>
              <div class="proc-phase-card proc-phase-card--final">
                <div class="proc-phase-card__header">ITB — Competitive Bidding</div>
                <div class="fs-12 text-muted mt-1">
                  Invitation to Bid. Public bidding posted on PhilGEPS. Used for larger procurements above the threshold.
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- BAC / NOA / NTP -->
        <div class="col-md-6">
          <div class="proc-section proc-section--bac h-100">
            <div class="proc-section__header">
              <span class="proc-section__badge proc-section__badge--bac">BAC → NOA → NTP</span>
              <div>
                <div class="proc-section__title">Abstract of Bids, Award &amp; Proceed</div>
                <div class="proc-section__subtitle">RA 9184 Sec. 29–37</div>
              </div>
            </div>
            <div class="proc-section__body">
              <ul class="fs-12 text-muted ps-3 mb-0">
                <li>BAC evaluates bids in the <strong>Abstract of Bids</strong>.</li>
                <li><strong>BAC Resolution</strong> recommends award to the lowest complying bidder.</li>
                <li><strong>NOA (Notice of Award)</strong> — issued to winning supplier. Supplier must acknowledge within the prescribed period.</li>
                <li><strong>NTP (Notice to Proceed)</strong> — issued for service and infrastructure contracts. Marks the official start date of work. Not required for simple goods procurement.</li>
              </ul>
            </div>
          </div>
        </div>

        <!-- Rebid / Re-award -->
        <div class="col-12 mt-2">
          <div class="proc-section-label">
            <i class="ri-refresh-line me-1"></i> Exception Paths — Rebid &amp; Re-award
          </div>
        </div>

        <div class="col-md-6">
          <div class="proc-section proc-section--rebid h-100">
            <div class="proc-section__header">
              <span class="proc-section__badge proc-section__badge--rebid">Rebid</span>
              <div>
                <div class="proc-section__title">Re-bidding</div>
                <div class="proc-section__subtitle">RA 9184 Sec. 35 — when all bids fail</div>
              </div>
            </div>
            <div class="proc-section__body">
              <p class="fs-13 text-muted mb-2">
                Triggered when <strong>no valid bids</strong> are received, all submitted bids are
                non-compliant, or the single bid/quotation fails post-qualification.
              </p>
              <div class="proc-exception-flow mb-2">
                <span class="proc-exception-step">Abstract of Bids<br><small>All bids fail</small></span>
                <i class="ri-arrow-right-line proc-exception-arrow"></i>
                <span class="proc-exception-step proc-exception-step--action">BAC Resolution<br><small>Recommends Rebid</small></span>
                <i class="ri-arrow-right-line proc-exception-arrow"></i>
                <span class="proc-exception-step">Status: Rebid<br><small>PR flagged</small></span>
                <i class="ri-arrow-right-line proc-exception-arrow"></i>
                <span class="proc-exception-step proc-exception-step--restart">New RFQ / ITB<br><small>Full restart</small></span>
              </div>
              <ul class="fs-12 text-muted ps-3 mb-2">
                <li>BAC generates a <strong>Rebid BAC Resolution</strong> declaring failure and recommending re-bidding.</li>
                <li>PR status is set to <strong>Rebid</strong>. The <code>rebidded_count</code> is incremented in the system.</li>
                <li>The entire quotation / bidding process restarts from scratch with new price solicitation.</li>
                <li>Rebid items are flagged with <code>is_rebid = 1</code> so the next BAC Resolution excludes previously awarded items.</li>
              </ul>
              <div class="proc-rule-box">
                <i class="ri-error-warning-line me-1 text-warning"></i>
                A Rebid restarts the <em>full</em> bidding cycle: new RFQ/ITB → new Abstract of Bids → new BAC Resolution → NOA → PO.
              </div>
            </div>
          </div>
        </div>

        <div class="col-md-6">
          <div class="proc-section proc-section--reaward h-100">
            <div class="proc-section__header">
              <span class="proc-section__badge proc-section__badge--reaward">Re-award</span>
              <div>
                <div class="proc-section__title">Re-award</div>
                <div class="proc-section__subtitle">RA 9184 Sec. 41 — when winner fails or defaults</div>
              </div>
            </div>
            <div class="proc-section__body">
              <p class="fs-13 text-muted mb-2">
                Triggered when the <strong>original winning bidder fails</strong> to sign the contract,
                withdraws, or is found non-compliant after award — and there are still other responsive
                bidders in the original Abstract of Bids.
              </p>
              <div class="proc-exception-flow mb-2">
                <span class="proc-exception-step">Winner Fails<br><small>Post-award</small></span>
                <i class="ri-arrow-right-line proc-exception-arrow"></i>
                <span class="proc-exception-step proc-exception-step--action">BAC Resolution<br><small>Recommends Re-award</small></span>
                <i class="ri-arrow-right-line proc-exception-arrow"></i>
                <span class="proc-exception-step proc-exception-step--action">New NOA<br><small>Next supplier</small></span>
                <i class="ri-arrow-right-line proc-exception-arrow"></i>
                <span class="proc-exception-step proc-exception-step--restart">PO<br><small>Continues</small></span>
              </div>
              <ul class="fs-12 text-muted ps-3 mb-2">
                <li>BAC generates a <strong>Re-award BAC Resolution</strong> recommending the next lowest complying bidder.</li>
                <li>PR status is set to <strong>Re-award</strong>. The <code>reawarded_count</code> is incremented.</li>
                <li>A new <strong>NOA</strong> is issued to the next-in-line supplier from the original Abstract of Bids.</li>
                <li>No new bidding is conducted — the process continues from NOA onward.</li>
                <li>If no next supplier is available, the system falls back to <strong>Rebid</strong> automatically.</li>
              </ul>
              <div class="proc-rule-box">
                <i class="ri-error-warning-line me-1 text-warning"></i>
                A Re-award skips new bidding — it picks the next responsive bidder from the existing Abstract of Bids.
              </div>
            </div>
          </div>
        </div>

        <!-- Phase 3 -->
        <div class="col-12 mt-2">
          <div class="proc-section-label">
            <i class="ri-truck-line me-1"></i> Phase 3 — Purchase Order, Delivery &amp; Acceptance
          </div>
        </div>

        <!-- PO flow -->
        <div class="col-12">
          <div class="proc-section proc-section--po">
            <div class="proc-section__header">
              <span class="proc-section__badge proc-section__badge--po">PO → IAR</span>
              <div>
                <div class="proc-section__title">Purchase Order to Completion</div>
                <div class="proc-section__subtitle">RA 9184 Sec. 37–41 — delivery and acceptance</div>
              </div>
            </div>
            <div class="proc-section__body p-0">
              <div class="po-flow-track">
                <div v-for="(step, i) in poSteps" :key="step.key" class="po-flow-item">
                  <div class="po-flow-item__dot">
                    <i :class="step.icon"></i>
                  </div>
                  <div class="po-flow-item__line" v-if="i < poSteps.length - 1"></div>
                  <div class="po-flow-item__content">
                    <div class="po-flow-item__title">{{ step.name }}</div>
                    <div class="po-flow-item__desc">{{ step.desc }}</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Full workflow table -->
        <div class="col-12">
          <div class="proc-section proc-section--summary">
            <div class="proc-section__header">
              <span class="proc-section__badge proc-section__badge--summary"><i class="ri-list-check-3"></i></span>
              <div>
                <div class="proc-section__title">Complete Step-by-Step Workflow</div>
                <div class="proc-section__subtitle">All stages from budget preparation to acceptance</div>
              </div>
            </div>
            <div class="proc-section__body p-0">
              <div class="table-responsive">
                <table class="table table-sm table-borderless mb-0 fs-12">
                  <thead class="table-light">
                    <tr>
                      <th class="ps-3" style="width:3%">#</th>
                      <th style="width:18%">Stage</th>
                      <th style="width:20%">Who</th>
                      <th>What Happens in the System</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="step in workflowSteps" :key="step.no ?? step.stage" :class="step.isPhaseHeader ? 'table-light' : ''">
                      <td class="ps-3 fw-bold text-muted">
                        <span v-if="!step.isPhaseHeader">{{ step.no }}</span>
                      </td>
                      <td>
                        <strong v-if="step.isPhaseHeader" class="text-muted fs-11 text-uppercase">{{ step.stage }}</strong>
                        <span v-else class="badge" :class="step.badgeClass">{{ step.stage }}</span>
                      </td>
                      <td class="text-muted">{{ step.who }}</td>
                      <td>{{ step.what }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

      </div><!-- /row -->
    </div><!-- /p-1 -->
  </b-modal>
</template>

<script>
export default {
  props: {
    show: { type: Boolean, default: false },
  },
  emits: ["update:show", "close"],
  computed: {
    modalShow: {
      get() { return this.show; },
      set(v) { this.$emit("update:show", v); },
    },
    poSteps() {
      return [
        { key: "created",   name: "PO Created",          icon: "ri-file-add-line",        desc: "PO generated referencing the PR, supplier, items, and approved amount." },
        { key: "issued",    name: "PO Issued",            icon: "ri-send-plane-line",      desc: "PO released to the supplier. Supplier reviews and conforms to the terms." },
        { key: "conformed", name: "PO Conformed",         icon: "ri-shake-hands-line",     desc: "Supplier accepts PO terms. Delivery schedule is confirmed." },
        { key: "delivery",  name: "Items Delivered",      icon: "ri-truck-line",           desc: "Supplier delivers items. Supply unit records received quantities per line item." },
        { key: "iar",       name: "IAR — Inspection",     icon: "ri-search-eye-line",      desc: "IAR generated. Committee inspects and accepts quality and quantity of items." },
        { key: "completed", name: "Completed",            icon: "ri-checkbox-circle-line", desc: "All IARs completed. PO marked done. Items enter inventory records automatically." },
      ];
    },
    workflowSteps() {
      return [
        { isPhaseHeader: true, stage: "Phase 1 — Planning", who: "", what: "" },
        {
          no: 1, stage: "Indicative PPMP", badgeClass: "bg-warning-subtle text-warning",
          who: "Unit Head / Staff",
          what: "Create PPMP listing planned procurements based on proposed budget. Status moves Pending → Approved.",
        },
        {
          no: 2, stage: "Indicative APP", badgeClass: "bg-warning-subtle text-warning",
          who: "Procurement Officer",
          what: "Consolidate approved Indicative PPMPs into an agency Indicative APP. Posted for transparency before GAA.",
        },
        {
          no: 3, stage: "GAA Enacted", badgeClass: "bg-secondary-subtle text-secondary",
          who: "Congress / DBM",
          what: "General Appropriations Act signed into law. Agency receives final budget allocation.",
        },
        {
          no: 4, stage: "Final PPMP", badgeClass: "bg-success-subtle text-success",
          who: "Unit Head",
          what: "Unit updates PPMP to approved budget amounts. Clicks 'Mark as Final'. Only Final PPMPs link to the Final APP.",
        },
        {
          no: 5, stage: "Final APP", badgeClass: "bg-success-subtle text-success",
          who: "Procurement Officer / HOPE",
          what: "Consolidate Final PPMPs. HOPE approves. PhilGEPS posting mandatory within 30 days (RA 9184 Sec. 7).",
        },
        {
          no: 6, stage: "SPP (if needed)", badgeClass: "bg-primary-subtle text-primary",
          who: "Unit Head",
          what: "Mid-year: if new items arise, unit submits a Supplemental PPMP. Only allowed after Final APP is approved.",
        },
        { isPhaseHeader: true, stage: "Phase 2 — Execution (starts from Purchase Request)", who: "", what: "" },
        {
          no: 7, stage: "Mode of Procurement", badgeClass: "bg-secondary-subtle text-secondary",
          who: "BAC",
          what: "BAC selects mode: Small Value / Shopping (RFQ) or Competitive Bidding (ITB) based on the Approved Budget for the Contract (ABC).",
        },
        {
          no: 8, stage: "RFQ / ITB", badgeClass: "bg-warning-subtle text-warning",
          who: "BAC / Procurement Officer",
          what: "RFQ: at least 3 quotations from suppliers. ITB: public bidding posted on PhilGEPS, bids opened by BAC.",
        },
        {
          no: 9, stage: "Abstract of Bids", badgeClass: "bg-warning-subtle text-warning",
          who: "BAC",
          what: "BAC evaluates all quotations/bids in the Abstract of Bids. Selects the Lowest Calculated and Responsive Bid (LCRB).",
        },
        {
          no: 10, stage: "BAC Resolution", badgeClass: "bg-primary-subtle text-primary",
          who: "BAC",
          what: "BAC generates a Resolution recommending award to the LCRB. Reviewed and approved by the HOPE.",
        },
        {
          no: 11, stage: "NOA", badgeClass: "bg-success-subtle text-success",
          who: "Procurement Officer",
          what: "Notice of Award issued to the winning supplier. Supplier must acknowledge within the prescribed period (usually 5 calendar days).",
        },
        {
          no: 12, stage: "NTP", badgeClass: "bg-success-subtle text-success",
          who: "Procurement Officer",
          what: "Notice to Proceed issued for service and infrastructure contracts. Sets the official start date of the contract or work. Not required for simple goods POs.",
        },
        { isPhaseHeader: true, stage: "Exception Paths — Rebid & Re-award", who: "", what: "" },
        {
          no: "!", stage: "Rebid", badgeClass: "bg-warning text-dark",
          who: "BAC",
          what: "All bids fail or no bids received. BAC generates Rebid Resolution. PR status = Rebid. Full bidding cycle restarts from RFQ/ITB.",
        },
        {
          no: "!", stage: "Re-award", badgeClass: "bg-danger-subtle text-danger",
          who: "BAC / Procurement Officer",
          what: "Original winner defaults or withdraws. BAC recommends next responsive bidder. New NOA issued. No new bidding needed. If no next bidder, falls back to Rebid.",
        },
        { isPhaseHeader: true, stage: "Phase 3 — PO, Delivery & Acceptance", who: "", what: "" },
        {
          no: 13, stage: "Purchase Order", badgeClass: "bg-danger-subtle text-danger",
          who: "Procurement Officer",
          what: "PO created and issued to supplier. PO status progresses: Created → Issued → Conformed (supplier accepts).",
        },
        {
          no: 14, stage: "Delivery", badgeClass: "bg-warning-subtle text-warning",
          who: "Supply Unit / Supplier",
          what: "Supplier delivers items. Supply unit records received quantities per line item in the system for tracking.",
        },
        {
          no: 15, stage: "IAR", badgeClass: "bg-success-subtle text-success",
          who: "IAR Committee",
          what: "Inspection and Acceptance Report generated. Committee inspects quality and quantity. Marks each report Completed.",
        },
        {
          no: 16, stage: "Completed", badgeClass: "bg-dark-subtle text-dark",
          who: "System",
          what: "PO marked Completed once all IAR reports are accepted. Items are automatically recorded in inventory.",
        },
      ];
    },
  },
};
</script>

<style scoped>
/* Flow strip */
.proc-flow-strip {
  display: flex;
  align-items: center;
  gap: 4px;
  overflow-x: auto;
  padding: 12px 14px;
  background: #f8f9fa;
  border-radius: 10px;
  border: 1px solid #e9ecef;
}
.proc-flow-step {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
  flex-shrink: 0;
}
.proc-flow-step__icon {
  width: 32px; height: 32px;
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 13px;
}
.proc-flow-step--ppmp .proc-flow-step__icon { background: rgba(64,81,137,.12);  color: #405189; }
.proc-flow-step--app  .proc-flow-step__icon { background: rgba(240,180,41,.15); color: #856404; }
.proc-flow-step--spp  .proc-flow-step__icon { background: rgba(10,179,156,.12); color: #0a6640; }
.proc-flow-step--pr   .proc-flow-step__icon { background: rgba(13,110,253,.12); color: #0a58ca; }
.proc-flow-step--rfq  .proc-flow-step__icon { background: rgba(240,101,72,.12); color: #a02b1e; }
.proc-flow-step--bac  .proc-flow-step__icon { background: rgba(111,66,193,.12); color: #4d0099; }
.proc-flow-step--noa  .proc-flow-step__icon { background: rgba(240,180,41,.15); color: #856404; }
.proc-flow-step--ntp  .proc-flow-step__icon { background: rgba(13,202,240,.15); color: #055160; }
.proc-flow-step--po   .proc-flow-step__icon { background: rgba(220,53,69,.12);  color: #842029; }
.proc-flow-step--iar  .proc-flow-step__icon { background: rgba(10,179,156,.12); color: #0a6640; }
.proc-flow-step__label { font-size: 10px; font-weight: 600; text-align: center; color: #495057; line-height: 1.3; }
.proc-flow-arrow { color: #ced4da; font-size: 13px; flex-shrink: 0; }

/* Phase label */
.proc-section-label {
  font-size: 11px; font-weight: 700; text-transform: uppercase;
  letter-spacing: 0.5px; color: #6c757d;
  padding: 4px 2px; border-bottom: 1px solid #e9ecef; margin-bottom: 2px;
}

/* Sections */
.proc-section {
  border: 1.5px solid #e9ecef;
  border-radius: 10px;
  overflow: hidden;
}
.proc-section__header {
  display: flex; align-items: flex-start; gap: 10px;
  padding: 10px 14px; border-bottom: 1px solid #e9ecef; background: #f8f9fa;
}
.proc-section__title   { font-size: 13px; font-weight: 700; color: #212529; }
.proc-section__subtitle { font-size: 11px; color: #6c757d; margin-top: 1px; }
.proc-section__body    { padding: 14px; }
.proc-section__badge {
  display: inline-flex; align-items: center; font-size: 10px; font-weight: 800;
  padding: 3px 8px; border-radius: 5px; flex-shrink: 0; letter-spacing: 0.3px;
  margin-top: 2px; white-space: nowrap;
}
.proc-section__badge--ppmp    { background: rgba(64,81,137,.15);  color: #405189; }
.proc-section__badge--app     { background: rgba(240,180,41,.2);  color: #856404; }
.proc-section__badge--pr      { background: rgba(13,110,253,.12); color: #0a58ca; }
.proc-section__badge--rfq     { background: rgba(240,101,72,.15); color: #a02b1e; }
.proc-section__badge--bac     { background: rgba(111,66,193,.15); color: #4d0099; }
.proc-section__badge--po      { background: rgba(220,53,69,.12);  color: #842029; }
.proc-section__badge--summary { background: rgba(33,37,41,.1);    color: #212529; font-size: 13px; }

/* Phase cards */
.proc-phase-card {
  border-radius: 7px; padding: 10px 12px; border: 1px solid #e9ecef;
}
.proc-phase-card--indicative { background: rgba(255,193,7,.05);  border-color: rgba(255,193,7,.25); }
.proc-phase-card--final      { background: rgba(10,179,156,.05); border-color: rgba(10,179,156,.25); }
.proc-phase-card__header {
  font-size: 12px; font-weight: 700; color: #212529;
  display: flex; align-items: center; gap: 4px; flex-wrap: wrap;
}
.proc-phase-card__tag {
  font-size: 10px; font-weight: 700; padding: 1px 5px;
  border-radius: 3px; text-transform: uppercase; letter-spacing: 0.3px;
}
.proc-phase-card__tag--warning { background: rgba(255,193,7,.2); color: #856404; }
.proc-phase-card__tag--success { background: rgba(10,179,156,.2); color: #0a6640; }

/* Rule box */
.proc-rule-box {
  background: rgba(255,193,7,.08);
  border: 1px solid rgba(255,193,7,.3);
  border-radius: 7px; padding: 8px 12px;
  font-size: 12px; color: #495057;
}

/* PO flow track */
.po-flow-track {
  display: flex; gap: 0; padding: 16px; overflow-x: auto;
}
.po-flow-item {
  display: flex; flex-direction: column; align-items: center;
  flex: 1; min-width: 90px; position: relative;
}
.po-flow-item__dot {
  width: 32px; height: 32px; border-radius: 50%;
  background: rgba(10,179,156,.12); color: #0a6640;
  display: flex; align-items: center; justify-content: center;
  font-size: 14px; z-index: 1; flex-shrink: 0;
}
.po-flow-item__line {
  position: absolute; top: 16px;
  left: calc(50% + 16px); right: calc(-50% + 16px);
  height: 2px; background: #dee2e6;
}
.po-flow-item__content { text-align: center; margin-top: 7px; padding: 0 4px; }
.po-flow-item__title { font-size: 11px; font-weight: 700; color: #212529; line-height: 1.3; }
.po-flow-item__desc  { font-size: 10px; color: #6c757d; margin-top: 3px; line-height: 1.3; }

/* Rebid / Re-award sections */
.proc-section--rebid  { border-color: rgba(255,193,7,.4); }
.proc-section--reaward { border-color: rgba(253,126,20,.4); }
.proc-section--rebid  .proc-section__header { background: rgba(255,193,7,.06); }
.proc-section--reaward .proc-section__header { background: rgba(253,126,20,.06); }
.proc-section__badge--rebid  { background: rgba(255,193,7,.2);  color: #856404; }
.proc-section__badge--reaward { background: rgba(220,53,69,.12); color: #842029; }

/* Exception flow mini-diagram */
.proc-exception-flow {
  display: flex; align-items: center; flex-wrap: wrap; gap: 4px;
  background: #f8f9fa; border-radius: 7px; padding: 8px 10px;
  border: 1px solid #e9ecef;
}
.proc-exception-step {
  font-size: 10px; font-weight: 600; color: #495057;
  text-align: center; line-height: 1.3;
  background: #fff; border: 1px solid #dee2e6;
  border-radius: 5px; padding: 4px 8px; flex-shrink: 0;
}
.proc-exception-step small { font-weight: 400; color: #6c757d; display: block; }
.proc-exception-step--action  { background: rgba(64,81,137,.08); border-color: rgba(64,81,137,.2); color: #405189; }
.proc-exception-step--restart { background: rgba(10,179,156,.08); border-color: rgba(10,179,156,.2); color: #0a6640; }
.proc-exception-arrow { color: #adb5bd; font-size: 13px; flex-shrink: 0; }

/* Summary */
.proc-section--summary .proc-section__body { padding: 0; }
.fs-11 { font-size: 11px; }
</style>
