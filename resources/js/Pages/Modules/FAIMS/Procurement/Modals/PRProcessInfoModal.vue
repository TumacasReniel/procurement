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
          <div class="fw-bold fs-14">Purchase Request Process Guide</div>
          <div class="text-muted fs-12">PR → Quotation / Bidding → BAC → NOA → NTP → PO → Delivery → IAR — per RA 9184</div>
        </div>
        <button type="button" class="btn-close" @click="$emit('close')"></button>
      </div>
    </template>

    <div class="p-1">

      <!-- Legal basis -->
      <div class="alert alert-primary py-2 px-3 fs-12 mb-3 d-flex align-items-start gap-2">
        <i class="ri-scales-line fs-16 mt-1 flex-shrink-0"></i>
        <div>
          <strong>Legal Basis:</strong> Republic Act No. 9184 (Government Procurement Reform Act) and the
          2016 Revised IRR. Bidding modes: <strong>Sec. 10–19</strong>;
          Evaluation &amp; Award: <strong>Sec. 29–37</strong>;
          Contract &amp; Delivery: <strong>Sec. 37–41</strong>.
        </div>
      </div>

      <!-- Flow strip -->
      <div class="pr-flow-strip mb-4">
        <div class="pr-flow-step pr-flow-step--pr">
          <div class="pr-flow-step__icon"><i class="ri-survey-line"></i></div>
          <div class="pr-flow-step__label">Purchase<br><small>Request</small></div>
        </div>
        <div class="pr-flow-arrow"><i class="ri-arrow-right-line"></i></div>
        <div class="pr-flow-step pr-flow-step--mode">
          <div class="pr-flow-step__icon"><i class="ri-git-branch-line"></i></div>
          <div class="pr-flow-step__label">Mode of<br><small>Procurement</small></div>
        </div>
        <div class="pr-flow-arrow"><i class="ri-arrow-right-line"></i></div>
        <div class="pr-flow-step pr-flow-step--rfq">
          <div class="pr-flow-step__icon"><i class="ri-price-tag-3-line"></i></div>
          <div class="pr-flow-step__label">RFQ / ITB<br><small>Quotation</small></div>
        </div>
        <div class="pr-flow-arrow"><i class="ri-arrow-right-line"></i></div>
        <div class="pr-flow-step pr-flow-step--aob">
          <div class="pr-flow-step__icon"><i class="ri-file-text-line"></i></div>
          <div class="pr-flow-step__label">Abstract<br><small>of Bids</small></div>
        </div>
        <div class="pr-flow-arrow"><i class="ri-arrow-right-line"></i></div>
        <div class="pr-flow-step pr-flow-step--bac">
          <div class="pr-flow-step__icon"><i class="ri-group-line"></i></div>
          <div class="pr-flow-step__label">BAC<br><small>Resolution</small></div>
        </div>
        <div class="pr-flow-arrow"><i class="ri-arrow-right-line"></i></div>
        <div class="pr-flow-step pr-flow-step--noa">
          <div class="pr-flow-step__icon"><i class="ri-award-line"></i></div>
          <div class="pr-flow-step__label">NOA<br><small>Award</small></div>
        </div>
        <div class="pr-flow-arrow"><i class="ri-arrow-right-line"></i></div>
        <div class="pr-flow-step pr-flow-step--ntp">
          <div class="pr-flow-step__icon"><i class="ri-play-circle-line"></i></div>
          <div class="pr-flow-step__label">NTP<br><small>Proceed</small></div>
        </div>
        <div class="pr-flow-arrow"><i class="ri-arrow-right-line"></i></div>
        <div class="pr-flow-step pr-flow-step--po">
          <div class="pr-flow-step__icon"><i class="ri-shopping-cart-line"></i></div>
          <div class="pr-flow-step__label">PO<br><small>Issuance</small></div>
        </div>
        <div class="pr-flow-arrow"><i class="ri-arrow-right-line"></i></div>
        <div class="pr-flow-step pr-flow-step--del">
          <div class="pr-flow-step__icon"><i class="ri-truck-line"></i></div>
          <div class="pr-flow-step__label">Delivery<br><small>Receiving</small></div>
        </div>
        <div class="pr-flow-arrow"><i class="ri-arrow-right-line"></i></div>
        <div class="pr-flow-step pr-flow-step--iar">
          <div class="pr-flow-step__icon"><i class="ri-checkbox-circle-line"></i></div>
          <div class="pr-flow-step__label">IAR<br><small>Acceptance</small></div>
        </div>
      </div>

      <div class="row g-3">

        <!-- PR -->
        <div class="col-12">
          <div class="pr-section-label">
            <i class="ri-survey-line me-1"></i> Step 1 — Purchase Request (PR)
          </div>
        </div>
        <div class="col-12">
          <div class="pr-card">
            <div class="pr-card__header">
              <span class="pr-badge pr-badge--pr">PR</span>
              <div>
                <div class="pr-card__title">What is a Purchase Request?</div>
                <div class="pr-card__subtitle">The starting document for every procurement transaction</div>
              </div>
            </div>
            <div class="pr-card__body">
              <div class="row g-3">
                <div class="col-md-4">
                  <div class="pr-info-block">
                    <div class="pr-info-block__label"><i class="ri-file-add-line me-1 text-primary"></i> How it starts</div>
                    <p class="fs-12 text-muted mb-0">
                      The end-user unit creates a PR specifying the items, quantities, purpose, and estimated amount
                      to be procured. It must reference an approved APP line item.
                    </p>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="pr-info-block">
                    <div class="pr-info-block__label"><i class="ri-user-check-line me-1 text-success"></i> Approval chain</div>
                    <p class="fs-12 text-muted mb-0">
                      Reviewed and approved by the unit head and budget officer before it reaches the
                      Procurement Officer and BAC for processing.
                    </p>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="pr-info-block">
                    <div class="pr-info-block__label"><i class="ri-link me-1 text-warning"></i> Reference APP</div>
                    <p class="fs-12 text-muted mb-0">
                      Every PR is linked to a <strong>Reference APP</strong> in the system. Procurement can only
                      proceed for items that appear in the approved Final APP or SPP.
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Mode of Procurement + RFQ/ITB -->
        <div class="col-12">
          <div class="pr-section-label">
            <i class="ri-git-branch-line me-1"></i> Step 2 — Mode of Procurement &amp; Quotation / Bidding
          </div>
        </div>
        <div class="col-md-6">
          <div class="pr-card h-100">
            <div class="pr-card__header">
              <span class="pr-badge pr-badge--rfq">RFQ</span>
              <div>
                <div class="pr-card__title">Request for Quotation — Small Value / Shopping</div>
                <div class="pr-card__subtitle">RA 9184 Sec. 52 — simplified procurement</div>
              </div>
            </div>
            <div class="pr-card__body">
              <ul class="fs-12 text-muted ps-3 mb-2">
                <li>Used when the <strong>Approved Budget for the Contract (ABC)</strong> is below the competitive bidding threshold.</li>
                <li>BAC solicits price quotations from at least <strong>3 registered and eligible suppliers</strong>.</li>
                <li>Suppliers submit their offers. BAC evaluates and records in the <strong>Abstract of Bids</strong>.</li>
                <li>Fastest mode — no public posting required beyond PhilGEPS registry check.</li>
              </ul>
              <div class="pr-rule-box">
                <i class="ri-information-line me-1 text-info"></i>
                Threshold (goods/services): ABC ≤ PHP 1,000,000 — use RFQ/Shopping mode.
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-6">
          <div class="pr-card h-100">
            <div class="pr-card__header">
              <span class="pr-badge pr-badge--itb">ITB</span>
              <div>
                <div class="pr-card__title">Invitation to Bid — Competitive Bidding</div>
                <div class="pr-card__subtitle">RA 9184 Sec. 21 — public competitive bidding</div>
              </div>
            </div>
            <div class="pr-card__body">
              <ul class="fs-12 text-muted ps-3 mb-2">
                <li>Used when ABC exceeds the shopping threshold. Most transparent and rigorous mode.</li>
                <li>BAC prepares bidding documents and posts the <strong>ITB on PhilGEPS</strong> (mandatory posting).</li>
                <li>Pre-bid conference held for ABCs ≥ PHP 1M (goods). Bids submitted and publicly opened by BAC.</li>
                <li>BAC evaluates bids, conducts post-qualification on the lowest calculated bid.</li>
              </ul>
              <div class="pr-rule-box">
                <i class="ri-information-line me-1 text-info"></i>
                ITB posting period: 7 to 30 calendar days depending on the ABC amount.
              </div>
            </div>
          </div>
        </div>

        <!-- Abstract of Bids + BAC Resolution -->
        <div class="col-12">
          <div class="pr-section-label">
            <i class="ri-group-line me-1"></i> Step 3 — Abstract of Bids &amp; BAC Resolution
          </div>
        </div>
        <div class="col-md-6">
          <div class="pr-card h-100">
            <div class="pr-card__header">
              <span class="pr-badge pr-badge--aob">AOB</span>
              <div>
                <div class="pr-card__title">Abstract of Bids</div>
                <div class="pr-card__subtitle">RA 9184 Sec. 29 — bid evaluation</div>
              </div>
            </div>
            <div class="pr-card__body">
              <ul class="fs-12 text-muted ps-3 mb-0">
                <li>BAC records all submitted quotations or bids item-by-item from each supplier.</li>
                <li>Identifies the <strong>Lowest Calculated Bid (LCB)</strong> or lowest quotation per item.</li>
                <li>Post-qualification is conducted on the LCB — verifies legal, technical, and financial eligibility.</li>
                <li>If LCB passes, it becomes the <strong>Lowest Calculated and Responsive Bid (LCRB)</strong>.</li>
                <li>If LCB fails post-qualification, the next lowest bid is evaluated. If all fail — <strong>Rebid</strong>.</li>
              </ul>
            </div>
          </div>
        </div>
        <div class="col-md-6">
          <div class="pr-card h-100">
            <div class="pr-card__header">
              <span class="pr-badge pr-badge--bac">BAC Reso</span>
              <div>
                <div class="pr-card__title">BAC Resolution</div>
                <div class="pr-card__subtitle">RA 9184 Sec. 37 — formal recommendation</div>
              </div>
            </div>
            <div class="pr-card__body">
              <ul class="fs-12 text-muted ps-3 mb-2">
                <li>BAC generates a formal <strong>Resolution recommending award</strong> to the LCRB.</li>
                <li>Resolution is reviewed and approved by the <strong>Head of the Procuring Entity (HOPE)</strong>.</li>
                <li>In case of failure: BAC generates a <strong>Rebid Resolution</strong> or <strong>Re-award Resolution</strong> depending on the situation.</li>
                <li>System generates the BAC Resolution document automatically based on Abstract of Bids data.</li>
              </ul>
              <div class="pr-rule-box">
                <i class="ri-error-warning-line me-1 text-warning"></i>
                HOPE must approve the BAC Resolution before NOA can be issued.
              </div>
            </div>
          </div>
        </div>

        <!-- NOA + NTP -->
        <div class="col-12">
          <div class="pr-section-label">
            <i class="ri-award-line me-1"></i> Step 4 — Notice of Award (NOA) &amp; Notice to Proceed (NTP)
          </div>
        </div>
        <div class="col-md-6">
          <div class="pr-card h-100">
            <div class="pr-card__header">
              <span class="pr-badge pr-badge--noa">NOA</span>
              <div>
                <div class="pr-card__title">Notice of Award</div>
                <div class="pr-card__subtitle">RA 9184 Sec. 37 — formal award to winning supplier</div>
              </div>
            </div>
            <div class="pr-card__body">
              <ul class="fs-12 text-muted ps-3 mb-0">
                <li>Issued to the winning supplier (LCRB) after the BAC Resolution is approved by HOPE.</li>
                <li>Supplier must <strong>accept within the prescribed period</strong> (usually 5 calendar days).</li>
                <li>If the supplier fails to accept or withdraws, a <strong>Re-award</strong> or <strong>Rebid</strong> is triggered.</li>
                <li>NOA triggers PO creation in the system once acknowledged.</li>
              </ul>
            </div>
          </div>
        </div>
        <div class="col-md-6">
          <div class="pr-card h-100">
            <div class="pr-card__header">
              <span class="pr-badge pr-badge--ntp">NTP</span>
              <div>
                <div class="pr-card__title">Notice to Proceed</div>
                <div class="pr-card__subtitle">RA 9184 IRR — for services &amp; infrastructure</div>
              </div>
            </div>
            <div class="pr-card__body">
              <ul class="fs-12 text-muted ps-3 mb-2">
                <li>Issued <strong>after the contract is signed</strong> for service and infrastructure procurements.</li>
                <li>Sets the <strong>official start date</strong> of the work or service period.</li>
                <li>Contract duration and penalties are counted from the NTP date.</li>
              </ul>
              <div class="pr-rule-box">
                <i class="ri-information-line me-1 text-info"></i>
                NTP is <strong>not required</strong> for simple goods procurement — the PO itself serves as the instruction to deliver.
              </div>
            </div>
          </div>
        </div>

        <!-- Rebid + Re-award -->
        <div class="col-12">
          <div class="pr-section-label">
            <i class="ri-refresh-line me-1"></i> Exception Paths — Rebid &amp; Re-award
          </div>
        </div>
        <div class="col-md-6">
          <div class="pr-card pr-card--rebid h-100">
            <div class="pr-card__header">
              <span class="pr-badge pr-badge--rebid">Rebid</span>
              <div>
                <div class="pr-card__title">Re-bidding</div>
                <div class="pr-card__subtitle">RA 9184 Sec. 35 — when all bids fail</div>
              </div>
            </div>
            <div class="pr-card__body">
              <div class="pr-exception-flow mb-2">
                <span class="pr-ex-step">All Bids Fail</span>
                <i class="ri-arrow-right-line pr-ex-arrow"></i>
                <span class="pr-ex-step pr-ex-step--action">Rebid BAC Reso</span>
                <i class="ri-arrow-right-line pr-ex-arrow"></i>
                <span class="pr-ex-step pr-ex-step--status">Status: Rebid</span>
                <i class="ri-arrow-right-line pr-ex-arrow"></i>
                <span class="pr-ex-step pr-ex-step--restart">New RFQ / ITB</span>
              </div>
              <ul class="fs-12 text-muted ps-3 mb-2">
                <li>Triggered when no valid bids are received, or all bids are non-compliant/fail post-qualification.</li>
                <li>BAC generates a <strong>Rebid BAC Resolution</strong> recommending a full re-bidding.</li>
                <li>PR status is set to <strong>Rebid</strong> and <code>rebidded_count</code> is incremented.</li>
                <li>The entire quotation / bidding cycle restarts from scratch.</li>
                <li>Previously submitted bids are flagged <code>is_rebid = 1</code> so they are excluded from the next BAC Resolution.</li>
              </ul>
            </div>
          </div>
        </div>
        <div class="col-md-6">
          <div class="pr-card pr-card--reaward h-100">
            <div class="pr-card__header">
              <span class="pr-badge pr-badge--reaward">Re-award</span>
              <div>
                <div class="pr-card__title">Re-award</div>
                <div class="pr-card__subtitle">RA 9184 Sec. 41 — when original winner defaults</div>
              </div>
            </div>
            <div class="pr-card__body">
              <div class="pr-exception-flow mb-2">
                <span class="pr-ex-step">Winner Fails</span>
                <i class="ri-arrow-right-line pr-ex-arrow"></i>
                <span class="pr-ex-step pr-ex-step--action">Re-award BAC Reso</span>
                <i class="ri-arrow-right-line pr-ex-arrow"></i>
                <span class="pr-ex-step pr-ex-step--action">New NOA</span>
                <i class="ri-arrow-right-line pr-ex-arrow"></i>
                <span class="pr-ex-step pr-ex-step--restart">Continue to PO</span>
              </div>
              <ul class="fs-12 text-muted ps-3 mb-2">
                <li>Triggered when the original awardee fails to accept the NOA, withdraws, or defaults.</li>
                <li>BAC recommends the <strong>next lowest responsive bidder</strong> from the original Abstract of Bids.</li>
                <li>A new <strong>NOA</strong> is issued to the next supplier. <code>reawarded_count</code> is incremented.</li>
                <li>No new bidding needed — the process resumes from NOA → PO.</li>
                <li>If no next supplier is available in the Abstract of Bids, the system automatically falls back to <strong>Rebid</strong>.</li>
              </ul>
            </div>
          </div>
        </div>

        <!-- PO + Delivery + IAR -->
        <div class="col-12">
          <div class="pr-section-label">
            <i class="ri-truck-line me-1"></i> Step 5 — Purchase Order, Delivery &amp; Acceptance
          </div>
        </div>
        <div class="col-12">
          <div class="pr-card">
            <div class="pr-card__header">
              <span class="pr-badge pr-badge--po">PO → IAR</span>
              <div>
                <div class="pr-card__title">Purchase Order to Completion</div>
                <div class="pr-card__subtitle">RA 9184 Sec. 37–41 — delivery and acceptance</div>
              </div>
            </div>
            <div class="pr-card__body p-0">
              <div class="po-track">
                <div v-for="(step, i) in poSteps" :key="step.key" class="po-track__item">
                  <div class="po-track__dot" :class="`po-track__dot--${step.color}`">
                    <i :class="step.icon"></i>
                  </div>
                  <div class="po-track__line" v-if="i < poSteps.length - 1"></div>
                  <div class="po-track__content">
                    <div class="po-track__title">{{ step.name }}</div>
                    <div class="po-track__desc">{{ step.desc }}</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Full workflow table -->
        <div class="col-12">
          <div class="pr-card">
            <div class="pr-card__header">
              <span class="pr-badge pr-badge--summary"><i class="ri-list-check-3"></i></span>
              <div>
                <div class="pr-card__title">Complete PR-to-PO Workflow</div>
                <div class="pr-card__subtitle">All stages from Purchase Request to acceptance</div>
              </div>
            </div>
            <div class="pr-card__body p-0">
              <div class="table-responsive">
                <table class="table table-sm table-borderless mb-0 fs-12">
                  <thead class="table-light">
                    <tr>
                      <th class="ps-3" style="width:3%">#</th>
                      <th style="width:18%">Stage</th>
                      <th style="width:20%">Who</th>
                      <th>What Happens</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="step in workflowSteps" :key="step.no ?? step.stage" :class="step.isHeader ? 'table-light' : ''">
                      <td class="ps-3 fw-bold text-muted">
                        <span v-if="!step.isHeader">{{ step.no }}</span>
                      </td>
                      <td>
                        <strong v-if="step.isHeader" class="text-muted" style="font-size:11px;text-transform:uppercase;letter-spacing:.4px">{{ step.stage }}</strong>
                        <span v-else class="badge" :class="step.badge">{{ step.stage }}</span>
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
        { key: "created",   color: "blue",  icon: "ri-file-add-line",        name: "PO Created",               desc: "PO generated referencing the PR, supplier, and approved items/amounts." },
        { key: "issued",    color: "blue",  icon: "ri-send-plane-line",      name: "PO Issued to Supplier",     desc: "PO released to supplier. Supplier reviews and must conform to the terms." },
        { key: "conformed", color: "teal",  icon: "ri-shake-hands-line",     name: "PO Conformed",              desc: "Supplier accepts PO terms. Delivery schedule confirmed by supplier." },
        { key: "delivery",  color: "teal",  icon: "ri-truck-line",           name: "Items Delivered",           desc: "Supplier delivers items. Supply unit records received quantities per line item." },
        { key: "iar",       color: "green", icon: "ri-search-eye-line",      name: "IAR — Inspection",          desc: "Inspection and Acceptance Report generated. IAR committee verifies quality and quantity." },
        { key: "completed", color: "green", icon: "ri-checkbox-circle-line", name: "Completed",                 desc: "All IARs accepted and completed. PO marked done. Items enter inventory automatically." },
      ];
    },
    workflowSteps() {
      return [
        { isHeader: true, stage: "Preparation", who: "", what: "" },
        { no: 1,  badge: "bg-info-subtle text-info",             stage: "Purchase Request",    who: "End-user / Unit",          what: "PR created from approved APP line item. Approved by unit head and budget officer." },
        { no: 2,  badge: "bg-secondary-subtle text-secondary",   stage: "Mode of Procurement", who: "BAC",                      what: "BAC selects RFQ (small value) or ITB (competitive bidding) based on the ABC." },
        { isHeader: true, stage: "Quotation / Bidding", who: "", what: "" },
        { no: 3,  badge: "bg-warning-subtle text-warning",       stage: "RFQ / ITB",           who: "BAC / Proc. Officer",      what: "RFQ: at least 3 quotations solicited. ITB: public bidding posted on PhilGEPS." },
        { no: 4,  badge: "bg-warning-subtle text-warning",       stage: "Abstract of Bids",    who: "BAC",                      what: "All bids/quotations recorded and evaluated. Lowest Calculated and Responsive Bid (LCRB) identified." },
        { no: 5,  badge: "bg-primary-subtle text-primary",       stage: "BAC Resolution",      who: "BAC / HOPE",               what: "BAC generates Resolution recommending award. HOPE approves before NOA can be issued." },
        { isHeader: true, stage: "Award", who: "", what: "" },
        { no: 6,  badge: "bg-success-subtle text-success",       stage: "NOA",                 who: "Procurement Officer",      what: "Notice of Award issued to winning supplier. Supplier acknowledges within prescribed period." },
        { no: 7,  badge: "bg-success-subtle text-success",       stage: "NTP",                 who: "Procurement Officer",      what: "Notice to Proceed issued for services/infrastructure. Sets official contract start date. Not required for goods." },
        { isHeader: true, stage: "Exception Paths", who: "", what: "" },
        { no: "!", badge: "bg-warning text-dark",                stage: "Rebid",               who: "BAC",                      what: "All bids fail or no bids received. Rebid BAC Resolution generated. PR status = Rebid. Full bidding cycle restarts." },
        { no: "!", badge: "bg-danger-subtle text-danger",        stage: "Re-award",            who: "BAC / Proc. Officer",      what: "Original winner defaults. Next responsive bidder awarded via new NOA. No new bidding. Falls back to Rebid if no next bidder." },
        { isHeader: true, stage: "PO, Delivery & Acceptance", who: "", what: "" },
        { no: 8,  badge: "bg-danger-subtle text-danger",         stage: "Purchase Order",      who: "Procurement Officer",      what: "PO created and issued. Status: Created → Issued → Conformed (supplier accepts PO terms)." },
        { no: 9,  badge: "bg-warning-subtle text-warning",       stage: "Delivery",            who: "Supply Unit / Supplier",   what: "Supplier delivers items. Supply unit records received quantities per line item." },
        { no: 10, badge: "bg-success-subtle text-success",       stage: "IAR",                 who: "IAR Committee",            what: "Inspection and Acceptance Report generated. Committee verifies quality and quantity. Marks Completed." },
        { no: 11, badge: "bg-dark-subtle text-dark",             stage: "Completed",           who: "System",                   what: "PO marked Completed after all IARs accepted. Items automatically recorded in inventory." },
      ];
    },
  },
};
</script>

<style scoped>
/* Flow strip */
.pr-flow-strip {
  display: flex; align-items: center; gap: 4px; overflow-x: auto;
  padding: 12px 14px; background: #f8f9fa;
  border-radius: 10px; border: 1px solid #e9ecef;
}
.pr-flow-step { display: flex; flex-direction: column; align-items: center; gap: 4px; flex-shrink: 0; }
.pr-flow-step__icon {
  width: 32px; height: 32px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center; font-size: 13px;
}
.pr-flow-step--pr   .pr-flow-step__icon { background: rgba(13,110,253,.12);  color: #0a58ca; }
.pr-flow-step--mode .pr-flow-step__icon { background: rgba(108,117,125,.12); color: #495057; }
.pr-flow-step--rfq  .pr-flow-step__icon { background: rgba(255,193,7,.18);   color: #856404; }
.pr-flow-step--aob  .pr-flow-step__icon { background: rgba(111,66,193,.12);  color: #4d0099; }
.pr-flow-step--bac  .pr-flow-step__icon { background: rgba(64,81,137,.12);   color: #405189; }
.pr-flow-step--noa  .pr-flow-step__icon { background: rgba(25,135,84,.12);   color: #146c43; }
.pr-flow-step--ntp  .pr-flow-step__icon { background: rgba(13,202,240,.15);  color: #055160; }
.pr-flow-step--po   .pr-flow-step__icon { background: rgba(220,53,69,.12);   color: #842029; }
.pr-flow-step--del  .pr-flow-step__icon { background: rgba(253,126,20,.12);  color: #7c3f00; }
.pr-flow-step--iar  .pr-flow-step__icon { background: rgba(10,179,156,.12);  color: #0a6640; }
.pr-flow-step__label { font-size: 10px; font-weight: 600; text-align: center; color: #495057; line-height: 1.3; }
.pr-flow-arrow { color: #ced4da; font-size: 13px; flex-shrink: 0; }

/* Section label */
.pr-section-label {
  font-size: 11px; font-weight: 700; text-transform: uppercase;
  letter-spacing: .5px; color: #6c757d;
  padding: 4px 2px; border-bottom: 1px solid #e9ecef; margin-bottom: 2px;
}

/* Cards */
.pr-card { border: 1.5px solid #e9ecef; border-radius: 10px; overflow: hidden; }
.pr-card--rebid  { border-color: rgba(255,193,7,.4); }
.pr-card--reaward { border-color: rgba(253,126,20,.4); }
.pr-card__header {
  display: flex; align-items: flex-start; gap: 10px;
  padding: 10px 14px; border-bottom: 1px solid #e9ecef; background: #f8f9fa;
}
.pr-card--rebid .pr-card__header  { background: rgba(255,193,7,.06); }
.pr-card--reaward .pr-card__header { background: rgba(253,126,20,.06); }
.pr-card__title    { font-size: 13px; font-weight: 700; color: #212529; }
.pr-card__subtitle { font-size: 11px; color: #6c757d; margin-top: 1px; }
.pr-card__body     { padding: 14px; }

/* Badges */
.pr-badge {
  display: inline-flex; align-items: center; font-size: 10px; font-weight: 800;
  padding: 3px 8px; border-radius: 5px; flex-shrink: 0; letter-spacing: .3px;
  margin-top: 2px; white-space: nowrap;
}
.pr-badge--pr      { background: rgba(13,110,253,.15);  color: #0a58ca; }
.pr-badge--rfq     { background: rgba(255,193,7,.2);    color: #856404; }
.pr-badge--itb     { background: rgba(64,81,137,.15);   color: #405189; }
.pr-badge--aob     { background: rgba(111,66,193,.15);  color: #4d0099; }
.pr-badge--bac     { background: rgba(64,81,137,.15);   color: #405189; }
.pr-badge--noa     { background: rgba(25,135,84,.15);   color: #146c43; }
.pr-badge--ntp     { background: rgba(13,202,240,.2);   color: #055160; }
.pr-badge--po      { background: rgba(220,53,69,.12);   color: #842029; }
.pr-badge--rebid   { background: rgba(255,193,7,.2);    color: #856404; }
.pr-badge--reaward { background: rgba(220,53,69,.12);   color: #842029; }
.pr-badge--summary { background: rgba(33,37,41,.1);     color: #212529; font-size: 13px; }

/* Info blocks */
.pr-info-block { background: #f8f9fa; border-radius: 8px; padding: 12px; height: 100%; }
.pr-info-block__label { font-size: 12px; font-weight: 700; color: #212529; margin-bottom: 6px; }

/* Rule box */
.pr-rule-box {
  background: rgba(13,110,253,.06); border: 1px solid rgba(13,110,253,.2);
  border-radius: 7px; padding: 8px 12px; font-size: 12px; color: #495057;
}

/* Exception flow */
.pr-exception-flow {
  display: flex; align-items: center; flex-wrap: wrap; gap: 4px;
  background: #f8f9fa; border-radius: 7px; padding: 8px 10px; border: 1px solid #e9ecef;
}
.pr-ex-step {
  font-size: 10px; font-weight: 600; color: #495057; text-align: center;
  background: #fff; border: 1px solid #dee2e6; border-radius: 5px; padding: 3px 7px; flex-shrink: 0;
}
.pr-ex-step--action  { background: rgba(64,81,137,.08); border-color: rgba(64,81,137,.2); color: #405189; }
.pr-ex-step--status  { background: rgba(255,193,7,.12); border-color: rgba(255,193,7,.3); color: #856404; }
.pr-ex-step--restart { background: rgba(10,179,156,.08); border-color: rgba(10,179,156,.2); color: #0a6640; }
.pr-ex-arrow { color: #adb5bd; font-size: 13px; flex-shrink: 0; }

/* PO track */
.po-track { display: flex; gap: 0; padding: 16px; overflow-x: auto; }
.po-track__item { display: flex; flex-direction: column; align-items: center; flex: 1; min-width: 85px; position: relative; }
.po-track__dot {
  width: 32px; height: 32px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center; font-size: 14px; z-index: 1; flex-shrink: 0;
}
.po-track__dot--blue  { background: rgba(13,110,253,.12);  color: #0a58ca; }
.po-track__dot--teal  { background: rgba(13,202,240,.15);  color: #055160; }
.po-track__dot--green { background: rgba(10,179,156,.12);  color: #0a6640; }
.po-track__line { position: absolute; top: 16px; left: calc(50% + 16px); right: calc(-50% + 16px); height: 2px; background: #dee2e6; }
.po-track__content { text-align: center; margin-top: 7px; padding: 0 4px; }
.po-track__title { font-size: 11px; font-weight: 700; color: #212529; line-height: 1.3; }
.po-track__desc  { font-size: 10px; color: #6c757d; margin-top: 3px; line-height: 1.3; }

/* Table */
.pr-card:last-child .pr-card__body { padding: 0; }
</style>
