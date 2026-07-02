<template>
  <b-modal
    v-model="modalShow"
    :size="isFullscreen ? undefined : 'xl'"
    :fullscreen="isFullscreen"
    centered
    hide-footer
    header-class="border-bottom pb-2"
    modal-class="zoomIn"
    :body-class="isFullscreen ? 'p-0 d-flex flex-column' : 'p-2'"
    :scrollable="!isFullscreen"
    @hide="close"
  >
    <template #header>
      <div class="d-flex align-items-center gap-2 w-100">
        <span class="avatar-title bg-warning-subtle rounded p-2" style="width:2.2rem;height:2.2rem;display:inline-flex;align-items:center;justify-content:center;flex-shrink:0">
          <i class="ri-book-open-fill text-warning fs-16"></i>
        </span>
        <div class="flex-grow-1">
          <div class="fw-bold fs-14">Procurement User Manual</div>
          <div class="text-muted fs-12">FAIMS · DOST-IX · Republic Act No. 9184</div>
        </div>
        <span class="text-muted fs-12 me-2">{{ page + 1 }} / {{ spreads.length }}</span>
        <button
          type="button"
          class="pm-hdr-btn"
          :title="isFullscreen ? 'Exit fullscreen' : 'Fullscreen'"
          @click="toggleFullscreen"
        >
          <i :class="isFullscreen ? 'ri-fullscreen-exit-line' : 'ri-fullscreen-line'"></i>
        </button>
        <button type="button" class="pm-hdr-btn" title="Print all pages" @click="printBook">
          <i class="ri-printer-line"></i>
        </button>
        <button type="button" class="btn-close" @click="close"></button>
      </div>
    </template>

    <div class="pm-book-area" :class="{ 'pm-book-area--fs': isFullscreen }" :style="isFullscreen ? 'flex:1' : ''">
      <button class="pm-nav" :disabled="page === 0" @click="turn(-1)" title="Previous">
        <i class="ri-arrow-left-s-line"></i>
      </button>

      <div class="pm-book">
        <div class="pm-book-inner" ref="bookInner">

          <!-- Left static page -->
          <div class="pm-page pm-page--l">
            <div class="pm-pg" v-html="spreads[page].left"></div>
            <div class="pm-pgnum pm-pgnum--l">{{ page * 2 + 1 }}</div>
            <div class="bp-shadow bp-shadow--from-right" ref="shadowLeft"></div>
          </div>

          <!-- Spine -->
          <div class="pm-spine">
            <div class="pm-spine__dot"></div>
            <div class="pm-spine__dot"></div>
          </div>

          <!-- Right static page -->
          <div class="pm-page pm-page--r">
            <div class="pm-pg" v-html="spreads[page].right"></div>
            <div class="pm-pgnum pm-pgnum--r">{{ page * 2 + 2 }}</div>
            <div class="bp-shadow bp-shadow--from-left" ref="shadowRight"></div>
          </div>

          <!-- 3-D turning page (two-sided: front + back face) -->
          <div
            v-show="isFlipping"
            class="bp-flipper"
            :class="dir > 0 ? 'bp-flipper--fwd' : 'bp-flipper--bwd'"
            ref="flipper"
          >
            <div class="bp-face bp-face--front">
              <div class="pm-pg" v-html="flipFront"></div>
              <div class="pm-pgnum" :class="dir > 0 ? 'pm-pgnum--r' : 'pm-pgnum--l'">{{ flipNum.front }}</div>
            </div>
            <div class="bp-face bp-face--back">
              <div class="pm-pg" v-html="flipBack"></div>
              <div class="pm-pgnum" :class="dir > 0 ? 'pm-pgnum--l' : 'pm-pgnum--r'">{{ flipNum.back }}</div>
            </div>
          </div>

        </div>
      </div>

      <button class="pm-nav" :disabled="page === spreads.length - 1" @click="turn(1)" title="Next">
        <i class="ri-arrow-right-s-line"></i>
      </button>
    </div>

    <div class="pm-footer">
      <div class="pm-footer__ch">{{ spreads[page].title }}</div>
      <div class="pm-footer__dots">
        <button
          v-for="(s, i) in spreads"
          :key="i"
          class="pm-dot"
          :class="{ 'pm-dot--on': i === page }"
          @click="goTo(i)"
          :title="s.title"
        ></button>
      </div>
    </div>

  </b-modal>
</template>

<script>
import gsap from 'gsap';

export default {
  name: 'ProcurementUserManualModal',

  props: {
    show: { type: Boolean, default: false },
  },

  emits: ['update:show', 'close'],

  data() {
    return {
      page: 0,
      dir: 1,
      isFlipping: false,
      flipFront: '',
      flipBack: '',
      flipNum: { front: 0, back: 0 },
      isFullscreen: false,
    };
  },

  computed: {
    modalShow: {
      get() { return this.show; },
      set(v) { this.$emit('update:show', v); },
    },

    spreads() {
      return [
        {
          title: 'Welcome — Table of Contents',
          left: `
<div style="height:100%;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;padding:20px;background:linear-gradient(145deg,#eef0f8 0%,#dde1f0 100%);border-radius:3px;min-height:370px">
  <div style="width:60px;height:60px;background:#405189;border-radius:50%;display:flex;align-items:center;justify-content:center;margin-bottom:14px;box-shadow:0 4px 15px rgba(64,81,137,.3)">
    <i class="ri-government-line" style="font-size:28px;color:#fff"></i>
  </div>
  <div style="font-size:10px;font-weight:700;letter-spacing:1.2px;text-transform:uppercase;color:#405189;margin-bottom:4px">Department of Science &amp; Technology</div>
  <div style="font-size:10px;color:#6c757d;margin-bottom:10px">Regional Office IX · Zamboanga Peninsula</div>
  <div style="width:32px;height:2px;background:#405189;margin:0 auto 12px"></div>
  <div style="font-size:22px;font-weight:800;color:#1e2a5e;line-height:1.25;margin-bottom:8px">Procurement<br>User Manual</div>
  <div style="font-size:11px;color:#6c757d;margin-bottom:12px">FAIMS Integrated Procurement System</div>
  <div style="width:32px;height:1px;background:#dee2e6;margin:0 auto 12px"></div>
  <div style="font-size:10px;color:#868e96;line-height:1.7">Republic Act No. 9184<br>Government Procurement Reform Act<br>&amp; 2016 Revised Implementing Rules</div>
  <div style="margin-top:18px;font-size:10px;font-weight:600;color:#adb5bd;letter-spacing:.8px">CY 2026 Edition</div>
</div>`,
          right: `
<div>
  <div class="pm-ch-head"><i class="ri-list-check-3 me-1"></i>Table of Contents</div>
  <div class="pm-ch-rule"></div>
  <div class="pm-toc">
    <div class="pm-toc-row"><span class="pm-toc-num">1</span><span class="pm-toc-name">Procurement Cycle Overview</span><span class="pm-toc-pg">3</span></div>
    <div class="pm-toc-row"><span class="pm-toc-num">2</span><span class="pm-toc-name">Project Procurement Management Plan (PPMP)</span><span class="pm-toc-pg">5</span></div>
    <div class="pm-toc-row"><span class="pm-toc-num">3</span><span class="pm-toc-name">Annual Procurement Plan (APP)</span><span class="pm-toc-pg">7</span></div>
    <div class="pm-toc-row"><span class="pm-toc-num">4</span><span class="pm-toc-name">Supplemental Procurement Plan (SPP)</span><span class="pm-toc-pg">9</span></div>
    <div class="pm-toc-row"><span class="pm-toc-num">5</span><span class="pm-toc-name">Purchase Request, RFQ &amp; Purchase Order</span><span class="pm-toc-pg">11</span></div>
    <div class="pm-toc-row"><span class="pm-toc-num">6</span><span class="pm-toc-name">Roles, Responsibilities &amp; Glossary</span><span class="pm-toc-pg">13</span></div>
  </div>
  <div class="pm-tip mt-3">
    <i class="ri-keyboard-line me-1"></i>
    Navigate with <strong>← →</strong> arrow keys, the dots below, or the arrow buttons on each side.
  </div>
  <div class="pm-box mt-3">
    <div style="font-size:11px;font-weight:700;color:#405189;margin-bottom:6px">The Procurement Cycle</div>
    <div class="pm-cycle">
      <span class="pm-chip pm-chip--ppmp">PPMP</span>
      <i class="ri-arrow-right-line" style="color:#ced4da;font-size:11px"></i>
      <span class="pm-chip pm-chip--app">APP</span>
      <i class="ri-arrow-right-line" style="color:#ced4da;font-size:11px"></i>
      <span class="pm-chip pm-chip--spp">SPP</span>
      <i class="ri-arrow-right-line" style="color:#ced4da;font-size:11px"></i>
      <span class="pm-chip pm-chip--rfq">PR / RFQ</span>
      <i class="ri-arrow-right-line" style="color:#ced4da;font-size:11px"></i>
      <span class="pm-chip pm-chip--po">PO / IAR</span>
    </div>
  </div>
</div>`,
        },

        {
          title: 'Chapter 1 — Procurement Cycle Overview',
          left: `
<div>
  <div class="pm-ch-head"><i class="ri-scales-3-line me-1"></i>Chapter 1 — Overview</div>
  <div class="pm-ch-rule"></div>
  <p class="pm-body">Government procurement in the Philippines is governed by <strong>Republic Act No. 9184</strong> (Government Procurement Reform Act) and its 2016 Revised IRR. All procurement activities must adhere to the principles of transparency, competitiveness, streamlined processes, and accountability.</p>
  <div class="pm-section-label">Legal Framework</div>
  <div class="pm-list">
    <div class="pm-list-item"><i class="ri-checkbox-circle-line pm-list-icon"></i><span><strong>RA 9184</strong> — Governs all government procurement of goods, infrastructure, and consulting services</span></div>
    <div class="pm-list-item"><i class="ri-checkbox-circle-line pm-list-icon"></i><span><strong>2016 Revised IRR</strong> — Detailed rules for each procurement mode and stage</span></div>
    <div class="pm-list-item"><i class="ri-checkbox-circle-line pm-list-icon"></i><span><strong>GPPB Resolutions</strong> — Guidelines issued by the Government Procurement Policy Board</span></div>
    <div class="pm-list-item"><i class="ri-checkbox-circle-line pm-list-icon"></i><span><strong>GAA</strong> — General Appropriations Act sets the approved budgets that fund procurement</span></div>
  </div>
  <div class="pm-section-label mt-2">Core Principle</div>
  <div class="pm-tip">
    <i class="ri-information-line me-1"></i>
    No procurement may proceed without an approved budget in the PPMP and APP. Every purchase must trace back to a specific APP line item.
  </div>
</div>`,
          right: `
<div>
  <div class="pm-ch-head"><i class="ri-git-branch-line me-1"></i>The 5-Stage Procurement Cycle</div>
  <div class="pm-ch-rule"></div>
  <div class="pm-flow-v">
    <div class="pm-fstep pm-fstep--ppmp">
      <div class="pm-fstep__icon"><i class="ri-draft-line"></i></div>
      <div class="pm-fstep__body">
        <div class="pm-fstep__name">PPMP — Project Procurement Management Plan</div>
        <div class="pm-fstep__desc">Units identify procurement needs for the year. Each item has an estimated budget, mode, and quarterly schedule.</div>
      </div>
    </div>
    <div class="pm-fstep pm-fstep--app">
      <div class="pm-fstep__icon"><i class="ri-file-list-3-line"></i></div>
      <div class="pm-fstep__body">
        <div class="pm-fstep__name">APP — Annual Procurement Plan</div>
        <div class="pm-fstep__desc">Consolidated PPMP items form the APP — the agency-wide procurement calendar submitted to GPPB via PhilGEPS.</div>
      </div>
    </div>
    <div class="pm-fstep pm-fstep--spp">
      <div class="pm-fstep__icon"><i class="ri-file-add-line"></i></div>
      <div class="pm-fstep__body">
        <div class="pm-fstep__name">SPP — Supplemental Procurement Plan</div>
        <div class="pm-fstep__desc">Amendments or additions to the APP after finalization — for new funding, emergencies, or unforeseen needs.</div>
      </div>
    </div>
    <div class="pm-fstep pm-fstep--rfq">
      <div class="pm-fstep__icon"><i class="ri-survey-line"></i></div>
      <div class="pm-fstep__body">
        <div class="pm-fstep__name">PR &amp; RFQ / ITB</div>
        <div class="pm-fstep__desc">Purchase Request created from an approved APP item. BAC processes via RFQ (small value) or competitive bidding (ITB).</div>
      </div>
    </div>
    <div class="pm-fstep pm-fstep--po">
      <div class="pm-fstep__icon"><i class="ri-shopping-cart-line"></i></div>
      <div class="pm-fstep__body">
        <div class="pm-fstep__name">PO → Delivery → IAR</div>
        <div class="pm-fstep__desc">Purchase Order issued after award. Supplier delivers; IAR committee inspects and accepts. Items enter inventory automatically.</div>
      </div>
    </div>
  </div>
</div>`,
        },

        {
          title: 'Chapter 2 — PPMP (Project Procurement Management Plan)',
          left: `
<div>
  <div class="pm-ch-head"><i class="ri-draft-line me-1"></i>Chapter 2 — PPMP</div>
  <div class="pm-ch-rule"></div>
  <p class="pm-body">A <strong>Project Procurement Management Plan (PPMP)</strong> is the procurement plan for a specific unit or project for a given calendar year. It lists all goods, services, and infrastructure to be procured, with estimated costs, modes, and quarterly schedules.</p>
  <div class="pm-section-label">Who Creates a PPMP?</div>
  <p class="pm-body">End-user units create their own PPMPs. Each unit submits a PPMP that is reviewed and approved by the unit head, then forwarded to the Procurement Office for consolidation into the APP.</p>
  <div class="pm-section-label">Key Fields</div>
  <div class="pm-field-list">
    <div class="pm-field"><span class="pm-field-k">Plan Year</span><span class="pm-field-v">Fiscal year covered (e.g., CY 2026)</span></div>
    <div class="pm-field"><span class="pm-field-k">Unit</span><span class="pm-field-v">Organizational unit requesting procurement</span></div>
    <div class="pm-field"><span class="pm-field-k">Requested By</span><span class="pm-field-v">Unit head who signs the PPMP</span></div>
    <div class="pm-field"><span class="pm-field-k">Items</span><span class="pm-field-v">Line items: description, unit, qty, unit cost, total</span></div>
    <div class="pm-field"><span class="pm-field-k">Schedule</span><span class="pm-field-v">Q1–Q4 quarterly procurement schedule</span></div>
    <div class="pm-field"><span class="pm-field-k">Mode</span><span class="pm-field-v">Shopping, RFQ, ITB, Direct Contracting, etc.</span></div>
  </div>
</div>`,
          right: `
<div>
  <div class="pm-ch-head"><i class="ri-git-branch-line me-1"></i>PPMP Status Workflow</div>
  <div class="pm-ch-rule"></div>
  <div class="pm-status-flow">
    <div class="pm-sf-item">
      <div class="pm-sf-badge pm-sf-badge--draft">Draft</div>
      <div class="pm-sf-desc">PPMP is being prepared by the end-user unit. Items can be freely added, edited, or removed.</div>
    </div>
    <div class="pm-sf-arrow"><i class="ri-arrow-down-line"></i></div>
    <div class="pm-sf-item">
      <div class="pm-sf-badge pm-sf-badge--review">For Review</div>
      <div class="pm-sf-desc">Submitted to the budget officer for review of estimated costs and fund availability.</div>
    </div>
    <div class="pm-sf-arrow"><i class="ri-arrow-down-line"></i></div>
    <div class="pm-sf-item">
      <div class="pm-sf-badge pm-sf-badge--approval">For Approval</div>
      <div class="pm-sf-desc">Forwarded to the HOPE or authorized approving officer for final PPMP sign-off.</div>
    </div>
    <div class="pm-sf-arrow"><i class="ri-arrow-down-line"></i></div>
    <div class="pm-sf-item">
      <div class="pm-sf-badge pm-sf-badge--approved">Approved</div>
      <div class="pm-sf-desc">PPMP is approved. The Procurement Officer can now consolidate it into the APP.</div>
    </div>
    <div class="pm-sf-arrow"><i class="ri-arrow-down-line"></i></div>
    <div class="pm-sf-item">
      <div class="pm-sf-badge pm-sf-badge--final">Final APP</div>
      <div class="pm-sf-desc">PPMP items have been included in the Final Annual Procurement Plan. PRs can now be created.</div>
    </div>
  </div>
  <div class="pm-tip mt-2">
    <i class="ri-alert-line me-1"></i>
    A PPMP can be <strong>reverted</strong> to a prior status for corrections. All version history is tracked in the system.
  </div>
</div>`,
        },

        {
          title: 'Chapter 3 — Annual Procurement Plan (APP)',
          left: `
<div>
  <div class="pm-ch-head"><i class="ri-file-list-3-line me-1"></i>Chapter 3 — APP</div>
  <div class="pm-ch-rule"></div>
  <p class="pm-body">The <strong>Annual Procurement Plan (APP)</strong> is the agency-wide procurement plan consolidating all approved PPMPs from every unit. It is submitted to the GPPB through PhilGEPS before procurement activities commence.</p>
  <div class="pm-section-label">Plan Phase Types</div>
  <div class="pm-field-list">
    <div class="pm-field"><span class="pm-field-k pm-field-k--blue">PPMP APP</span><span class="pm-field-v">Standard unit PPMP items consolidated into the APP</span></div>
    <div class="pm-field"><span class="pm-field-k pm-field-k--warn">SPP APP</span><span class="pm-field-v">APP created from a Supplemental Procurement Plan</span></div>
    <div class="pm-field"><span class="pm-field-k pm-field-k--green">Final APP</span><span class="pm-field-v">Official consolidated APP posted on PhilGEPS — used for PR creation</span></div>
  </div>
  <div class="pm-section-label">PPMP → APP Conversion</div>
  <p class="pm-body">Once a PPMP is approved, the Procurement Officer clicks <strong>Approve to APP</strong>. The system automatically groups items by mode of procurement and assigns schedule dates.</p>
  <div class="pm-tip">
    <i class="ri-information-line me-1"></i>
    The Final APP is the official version posted on PhilGEPS and used to authorize all Purchase Requests for the fiscal year.
  </div>
</div>`,
          right: `
<div>
  <div class="pm-ch-head"><i class="ri-git-branch-line me-1"></i>APP Approval Workflow</div>
  <div class="pm-ch-rule"></div>
  <div class="pm-status-flow">
    <div class="pm-sf-item">
      <div class="pm-sf-badge pm-sf-badge--draft">Draft</div>
      <div class="pm-sf-desc">APP is being assembled from approved PPMPs. Items can still be adjusted or added.</div>
    </div>
    <div class="pm-sf-arrow"><i class="ri-arrow-down-line"></i></div>
    <div class="pm-sf-item">
      <div class="pm-sf-badge pm-sf-badge--review">For Review</div>
      <div class="pm-sf-desc">APP is reviewed by the Budget Officer to validate estimated costs and fund sources.</div>
    </div>
    <div class="pm-sf-arrow"><i class="ri-arrow-down-line"></i></div>
    <div class="pm-sf-item">
      <div class="pm-sf-badge pm-sf-badge--approval">For Approval</div>
      <div class="pm-sf-desc">Submitted to HOPE for approval. No changes may be made once submitted for approval.</div>
    </div>
    <div class="pm-sf-arrow"><i class="ri-arrow-down-line"></i></div>
    <div class="pm-sf-item">
      <div class="pm-sf-badge pm-sf-badge--approved">Approved</div>
      <div class="pm-sf-desc">APP is officially approved. Ready to be finalized into the Final APP.</div>
    </div>
    <div class="pm-sf-arrow"><i class="ri-arrow-down-line"></i></div>
    <div class="pm-sf-item">
      <div class="pm-sf-badge pm-sf-badge--final">Final APP</div>
      <div class="pm-sf-desc">Finalized and posted on PhilGEPS. Purchase Requests may now be created from items in this APP.</div>
    </div>
  </div>
</div>`,
        },

        {
          title: 'Chapter 4 — Supplemental Procurement Plan (SPP)',
          left: `
<div>
  <div class="pm-ch-head"><i class="ri-file-add-line me-1"></i>Chapter 4 — SPP</div>
  <div class="pm-ch-rule"></div>
  <p class="pm-body">A <strong>Supplemental Procurement Plan (SPP)</strong> is a formal amendment to the existing APP. It covers new procurement needs that arise <em>after</em> the APP has been finalized — such as newly received funding, emergency requirements, or items not included in the original plan.</p>
  <div class="pm-section-label">When to Create an SPP</div>
  <div class="pm-list">
    <div class="pm-list-item"><i class="ri-checkbox-circle-line pm-list-icon"></i><span>New budget received after APP finalization (SARO, grants)</span></div>
    <div class="pm-list-item"><i class="ri-checkbox-circle-line pm-list-icon"></i><span>Emergency or unforeseen procurement needs during the year</span></div>
    <div class="pm-list-item"><i class="ri-checkbox-circle-line pm-list-icon"></i><span>Additional items not included in any unit's original PPMP</span></div>
    <div class="pm-list-item"><i class="ri-checkbox-circle-line pm-list-icon"></i><span>Procurement funded by special projects or reprogrammed funds</span></div>
  </div>
  <div class="pm-section-label">Required Documents</div>
  <div class="pm-list">
    <div class="pm-list-item"><i class="ri-file-text-line pm-list-icon pm-list-icon--warn"></i><span>Supporting PDF (Budget Clearance, SARO, or authority to procure)</span></div>
    <div class="pm-list-item"><i class="ri-file-text-line pm-list-icon pm-list-icon--warn"></i><span>Unit head signature on the SPP form (set via Requested By field)</span></div>
  </div>
</div>`,
          right: `
<div>
  <div class="pm-ch-head"><i class="ri-git-commit-line me-1"></i>Creating an SPP — Step by Step</div>
  <div class="pm-ch-rule"></div>
  <div class="pm-steps">
    <div class="pm-step">
      <div class="pm-step-n">1</div>
      <div class="pm-step-b">
        <div class="pm-step-title">Open PPMP Index</div>
        <div class="pm-step-desc">Go to Procurement → PPMP. Click the <strong>+ Create SPP</strong> button in the top-right action area.</div>
      </div>
    </div>
    <div class="pm-step">
      <div class="pm-step-n">2</div>
      <div class="pm-step-b">
        <div class="pm-step-title">Fill SPP Details</div>
        <div class="pm-step-desc">Select the <strong>Plan Year</strong>, the <strong>Unit</strong>, and the <strong>Requested By</strong> (unit head). Upload the required PDF supporting document.</div>
      </div>
    </div>
    <div class="pm-step">
      <div class="pm-step-n">3</div>
      <div class="pm-step-b">
        <div class="pm-step-title">Add Items</div>
        <div class="pm-step-desc">After the SPP is created, open it and add line items — description, unit, quantity, unit cost, and quarterly schedule.</div>
      </div>
    </div>
    <div class="pm-step">
      <div class="pm-step-n">4</div>
      <div class="pm-step-b">
        <div class="pm-step-title">Submit for Approval</div>
        <div class="pm-step-desc">Follow the same status flow: <em>Draft → For Review → For Approval → Approved → Final APP (SPP)</em>.</div>
      </div>
    </div>
    <div class="pm-step">
      <div class="pm-step-n">5</div>
      <div class="pm-step-b">
        <div class="pm-step-title">SPP APP Created</div>
        <div class="pm-step-desc">Once approved, the Procurement Officer creates an <strong>SPP APP</strong> from the SPP items, finalizes it, and Purchase Requests can be generated.</div>
      </div>
    </div>
  </div>
</div>`,
        },

        {
          title: 'Chapter 5 — Purchase Request, RFQ & Purchase Order',
          left: `
<div>
  <div class="pm-ch-head"><i class="ri-survey-line me-1"></i>Chapter 5 — PR &amp; RFQ</div>
  <div class="pm-ch-rule"></div>
  <p class="pm-body">A <strong>Purchase Request (PR)</strong> initiates the procurement of a specific item. It must reference an approved Final APP or SPP APP line item and be approved by the unit head and budget officer before reaching the BAC.</p>
  <div class="pm-section-label">Creating a Purchase Request</div>
  <div class="pm-steps">
    <div class="pm-step">
      <div class="pm-step-n">1</div>
      <div class="pm-step-b"><div class="pm-step-desc">Open the APP View page and select items from the Final APP to add to a PR.</div></div>
    </div>
    <div class="pm-step">
      <div class="pm-step-n">2</div>
      <div class="pm-step-b"><div class="pm-step-desc">Specify the <strong>purpose/justification</strong>, required delivery date, and the requesting office.</div></div>
    </div>
    <div class="pm-step">
      <div class="pm-step-n">3</div>
      <div class="pm-step-b"><div class="pm-step-desc">Submit for approval: Unit Head → Budget Officer → Procurement Officer → BAC.</div></div>
    </div>
  </div>
  <div class="pm-section-label">Modes of Procurement</div>
  <div class="pm-field-list">
    <div class="pm-field"><span class="pm-field-k pm-field-k--warn">Shopping</span><span class="pm-field-v">ABC ≤ ₱1M — simplified, no public posting required</span></div>
    <div class="pm-field"><span class="pm-field-k pm-field-k--blue">RFQ</span><span class="pm-field-v">Small value — at least 3 quotations from registered suppliers</span></div>
    <div class="pm-field"><span class="pm-field-k pm-field-k--green">ITB</span><span class="pm-field-v">Competitive bidding — ABC above threshold, posted on PhilGEPS</span></div>
    <div class="pm-field"><span class="pm-field-k">Direct</span><span class="pm-field-v">Direct contracting for proprietary or emergency items</span></div>
  </div>
</div>`,
          right: `
<div>
  <div class="pm-ch-head"><i class="ri-shopping-cart-line me-1"></i>RFQ → PO Flow</div>
  <div class="pm-ch-rule"></div>
  <div class="pm-flow-v">
    <div class="pm-fstep pm-fstep--rfq">
      <div class="pm-fstep__icon"><i class="ri-price-tag-3-line"></i></div>
      <div class="pm-fstep__body">
        <div class="pm-fstep__name">RFQ / ITB — Quotation or Bidding</div>
        <div class="pm-fstep__desc">BAC solicits at least 3 quotations (RFQ) or posts an Invitation to Bid on PhilGEPS (ITB).</div>
      </div>
    </div>
    <div class="pm-fstep pm-fstep--aob">
      <div class="pm-fstep__icon"><i class="ri-file-text-line"></i></div>
      <div class="pm-fstep__body">
        <div class="pm-fstep__name">Abstract of Bids</div>
        <div class="pm-fstep__desc">BAC records all bids. The Lowest Calculated and Responsive Bid (LCRB) is identified after post-qualification.</div>
      </div>
    </div>
    <div class="pm-fstep pm-fstep--bac">
      <div class="pm-fstep__icon"><i class="ri-group-line"></i></div>
      <div class="pm-fstep__body">
        <div class="pm-fstep__name">BAC Resolution &amp; NOA</div>
        <div class="pm-fstep__desc">BAC issues a Resolution recommending award. HOPE approves. Notice of Award (NOA) issued to the winning supplier.</div>
      </div>
    </div>
    <div class="pm-fstep pm-fstep--po">
      <div class="pm-fstep__icon"><i class="ri-shopping-cart-line"></i></div>
      <div class="pm-fstep__body">
        <div class="pm-fstep__name">Purchase Order</div>
        <div class="pm-fstep__desc">PO created and issued to supplier. Supplier conforms to PO terms and delivers within the agreed period.</div>
      </div>
    </div>
    <div class="pm-fstep pm-fstep--iar">
      <div class="pm-fstep__icon"><i class="ri-checkbox-circle-line"></i></div>
      <div class="pm-fstep__body">
        <div class="pm-fstep__name">Delivery &amp; IAR</div>
        <div class="pm-fstep__desc">Inspection and Acceptance Report (IAR) generated. Committee verifies quality and quantity. Items enter inventory.</div>
      </div>
    </div>
  </div>
</div>`,
        },

        {
          title: 'Chapter 6 — Roles, Responsibilities & Glossary',
          left: `
<div>
  <div class="pm-ch-head"><i class="ri-team-line me-1"></i>Chapter 6 — Roles &amp; Responsibilities</div>
  <div class="pm-ch-rule"></div>
  <div class="pm-roles">
    <div class="pm-role">
      <div class="pm-role-title"><i class="ri-user-line pm-role-icon pm-role-icon--blue"></i>End User / Unit</div>
      <div class="pm-role-desc">Creates and submits PPMPs and SPPs. Creates Purchase Requests from approved APP items. Participates in goods inspection.</div>
    </div>
    <div class="pm-role">
      <div class="pm-role-title"><i class="ri-user-star-line pm-role-icon pm-role-icon--green"></i>Unit Head</div>
      <div class="pm-role-desc">Reviews and signs PPMPs. Approves PRs from the unit. Acts as the "Requested By" signatory on procurement documents.</div>
    </div>
    <div class="pm-role">
      <div class="pm-role-title"><i class="ri-bank-card-line pm-role-icon pm-role-icon--warn"></i>Budget Officer</div>
      <div class="pm-role-desc">Reviews PPMPs and APPs for fund availability. Certifies allotment before procurement proceeds.</div>
    </div>
    <div class="pm-role">
      <div class="pm-role-title"><i class="ri-briefcase-line pm-role-icon pm-role-icon--purple"></i>Procurement Officer</div>
      <div class="pm-role-desc">Manages the PPMP/APP/SPP workflow. Consolidates PPMPs into the APP. Coordinates with the BAC Secretariat.</div>
    </div>
    <div class="pm-role">
      <div class="pm-role-title"><i class="ri-group-line pm-role-icon pm-role-icon--teal"></i>BAC Secretariat</div>
      <div class="pm-role-desc">Processes RFQs and ITBs. Maintains Abstract of Bids, BAC Resolutions, NOA, and NTP documents.</div>
    </div>
    <div class="pm-role">
      <div class="pm-role-title"><i class="ri-vip-crown-line pm-role-icon pm-role-icon--red"></i>HOPE</div>
      <div class="pm-role-desc">Head of the Procuring Entity. Final approver for APPs, BAC Resolutions, and NOAs. Authorizes all major procurement decisions.</div>
    </div>
  </div>
</div>`,
          right: `
<div>
  <div class="pm-ch-head"><i class="ri-book-2-line me-1"></i>Glossary of Key Terms</div>
  <div class="pm-ch-rule"></div>
  <div class="pm-glossary">
    <div class="pm-gterm"><span class="pm-gt-k">ABC</span><span class="pm-gt-v">Approved Budget for the Contract — ceiling for the procurement price</span></div>
    <div class="pm-gterm"><span class="pm-gt-k">APP</span><span class="pm-gt-v">Annual Procurement Plan — agency-wide plan for the fiscal year</span></div>
    <div class="pm-gterm"><span class="pm-gt-k">BAC</span><span class="pm-gt-v">Bids and Awards Committee — evaluates bids and recommends awards</span></div>
    <div class="pm-gterm"><span class="pm-gt-k">GAA</span><span class="pm-gt-v">General Appropriations Act — annual national budget law</span></div>
    <div class="pm-gterm"><span class="pm-gt-k">GPPB</span><span class="pm-gt-v">Government Procurement Policy Board — issues procurement guidelines</span></div>
    <div class="pm-gterm"><span class="pm-gt-k">HOPE</span><span class="pm-gt-v">Head of the Procuring Entity — top approver for procurement</span></div>
    <div class="pm-gterm"><span class="pm-gt-k">IAR</span><span class="pm-gt-v">Inspection and Acceptance Report — confirms receipt and quality</span></div>
    <div class="pm-gterm"><span class="pm-gt-k">ITB</span><span class="pm-gt-v">Invitation to Bid — public competitive bidding document</span></div>
    <div class="pm-gterm"><span class="pm-gt-k">LCRB</span><span class="pm-gt-v">Lowest Calculated and Responsive Bid — winning bid after evaluation</span></div>
    <div class="pm-gterm"><span class="pm-gt-k">NOA</span><span class="pm-gt-v">Notice of Award — formal notification to winning supplier</span></div>
    <div class="pm-gterm"><span class="pm-gt-k">NTP</span><span class="pm-gt-v">Notice to Proceed — authorizes start of service or construction work</span></div>
    <div class="pm-gterm"><span class="pm-gt-k">PhilGEPS</span><span class="pm-gt-v">Philippine Government Electronic Procurement System</span></div>
    <div class="pm-gterm"><span class="pm-gt-k">PPMP</span><span class="pm-gt-v">Project Procurement Management Plan — unit-level procurement plan</span></div>
    <div class="pm-gterm"><span class="pm-gt-k">PR</span><span class="pm-gt-v">Purchase Request — initiates a specific procurement transaction</span></div>
    <div class="pm-gterm"><span class="pm-gt-k">RFQ</span><span class="pm-gt-v">Request for Quotation — simplified procurement for small value</span></div>
    <div class="pm-gterm"><span class="pm-gt-k">SPP</span><span class="pm-gt-v">Supplemental Procurement Plan — amendment to the approved APP</span></div>
  </div>
</div>`,
        },
      ];
    },
  },

  watch: {
    show(v) {
      if (v) {
        this.page = 0;
        this.dir = 1;
      }
    },
  },

  methods: {
    close() {
      this.isFullscreen = false;
      this.$emit('update:show', false);
      this.$emit('close');
    },

    toggleFullscreen() {
      this.isFullscreen = !this.isFullscreen;
    },

    printBook() {
      window.open('/procurement/user-manual/print', '_blank');
    },

    turn(d) {
      if (this.isFlipping) return;
      const next = this.page + d;
      if (next < 0 || next >= this.spreads.length) return;
      this._startFlip(next, d);
    },

    goTo(i) {
      if (i === this.page || this.isFlipping) return;
      this._startFlip(i, i > this.page ? 1 : -1);
    },

    _startFlip(nextPage, d) {
      this.dir = d;
      const fwd = d > 0;
      this.flipFront = fwd ? this.spreads[this.page].right : this.spreads[this.page].left;
      this.flipBack  = fwd ? this.spreads[nextPage].left   : this.spreads[nextPage].right;
      this.flipNum   = fwd
        ? { front: this.page * 2 + 2, back: nextPage * 2 + 1 }
        : { front: this.page * 2 + 1, back: nextPage * 2 + 2 };
      this.isFlipping = true;
      this.$nextTick(() => this._animateFlip(nextPage));
    },

    _animateFlip(nextPage) {
      const flipper = this.$refs.flipper;
      if (!flipper) { this.page = nextPage; this.isFlipping = false; return; }

      const fwd    = this.dir > 0;
      const shadow = fwd ? this.$refs.shadowLeft : this.$refs.shadowRight;

      gsap.set(flipper, { rotateY: 0 });

      gsap.timeline({ onComplete: () => { this.isFlipping = false; } })
        .to(flipper, { duration: 0.4, rotateY: fwd ? -90 : 90,   ease: 'power2.in'  })
        .to(shadow,  { duration: 0.4, opacity: 1, ease: 'power1.in'  }, 0)
        .add(() => { this.page = nextPage; })
        .to(flipper, { duration: 0.4, rotateY: fwd ? -180 : 180, ease: 'power2.out' })
        .to(shadow,  { duration: 0.4, opacity: 0, ease: 'power1.out' }, '<');
    },

    _keydown(e) {
      if (!this.show) return;
      if (e.key === 'ArrowRight' || e.key === 'ArrowDown') { e.preventDefault(); this.turn(1); }
      if (e.key === 'ArrowLeft'  || e.key === 'ArrowUp')   { e.preventDefault(); this.turn(-1); }
      if (e.key === 'Escape') this.close();
    },
  },

  mounted() {
    document.addEventListener('keydown', this._keydown);
  },

  beforeUnmount() {
    document.removeEventListener('keydown', this._keydown);
  },
};
</script>

<style>
/* ── Header icon buttons ── */
.pm-hdr-btn {
  background: none; border: 1px solid #e9ecef; border-radius: 6px;
  width: 30px; height: 30px; cursor: pointer; color: #6c757d;
  display: inline-flex; align-items: center; justify-content: center;
  font-size: 15px; transition: all .15s; flex-shrink: 0;
}
.pm-hdr-btn:hover { background: #f8f9fa; border-color: #ced4da; color: #212529; }

/* ── Book area ── */
.pm-book-area {
  display: flex; align-items: center; padding: 16px 12px;
  background: #f8f9fa; gap: 8px; flex-shrink: 0;
  border-radius: 8px;
}
.pm-book { flex: 1; min-width: 0; }

/* ══════════════════════════════════════════════
   Fullscreen — expanded layout & enhanced UI
   ══════════════════════════════════════════════ */
.pm-book-area--fs {
  padding: 20px 24px;
  border-radius: 0;
  align-items: stretch;
  background: linear-gradient(135deg, #1a1e2e 0%, #252d47 100%);
}

/* Book structure */
.pm-book-area--fs .pm-book { display: flex; flex-direction: column; }
.pm-book-area--fs .pm-book-inner {
  flex: 1;
  box-shadow: 0 24px 64px rgba(0,0,0,.65), 0 4px 20px rgba(0,0,0,.4);
}
.pm-book-area--fs .pm-page { flex: 1; display: flex; flex-direction: column; background: #fffef9; }
.pm-book-area--fs .pm-spine { width: 20px; }

/* Page content area */
.pm-book-area--fs .pm-pg {
  max-height: calc(100vh - 180px) !important;
  min-height: calc(100vh - 195px) !important;
  flex: 1;
  padding: 28px 28px 10px;
  font-size: 13.5px;
  line-height: 1.65;
}

/* Page number */
.pm-book-area--fs .pm-pgnum { font-size: 12px; bottom: 8px; }
.pm-book-area--fs .pm-pgnum--l { left: 28px; }
.pm-book-area--fs .pm-pgnum--r { right: 28px; }

/* Nav buttons */
.pm-book-area--fs .pm-nav {
  width: 46px; height: 46px; font-size: 26px;
  background: rgba(255,255,255,.1);
  color: #fff;
  border: 1px solid rgba(255,255,255,.18);
  box-shadow: 0 2px 12px rgba(0,0,0,.35);
  align-self: center;
}
.pm-book-area--fs .pm-nav:hover:not(:disabled) {
  background: rgba(255,255,255,.22);
  color: #fff;
  box-shadow: 0 4px 18px rgba(0,0,0,.45);
  transform: scale(1.08);
}

/* ── Typography scale-up ── */
.pm-book-area--fs .pm-ch-head { font-size: 18px; gap: 8px; }
.pm-book-area--fs .pm-ch-rule { height: 3px; margin: 8px 0 14px; }
.pm-book-area--fs .pm-body { font-size: 13.5px; line-height: 1.7; margin-bottom: 12px; }
.pm-book-area--fs .pm-section-label {
  font-size: 11px; margin-top: 14px; margin-bottom: 7px; letter-spacing: .7px;
}

/* Lists */
.pm-book-area--fs .pm-list { gap: 7px; }
.pm-book-area--fs .pm-list-item { font-size: 13px; gap: 8px; }
.pm-book-area--fs .pm-list-icon { font-size: 15px; }

/* Tip / Box */
.pm-book-area--fs .pm-tip { font-size: 12.5px; padding: 9px 13px; }
.pm-book-area--fs .pm-box  { padding: 12px 15px; }

/* Chips */
.pm-book-area--fs .pm-chip { font-size: 11.5px; padding: 3px 10px; }
.pm-book-area--fs .pm-cycle { gap: 7px; margin-top: 6px; }

/* TOC */
.pm-book-area--fs .pm-toc-row { font-size: 13.5px; padding: 8px 0; }
.pm-book-area--fs .pm-toc-num { width: 23px; height: 23px; font-size: 10px; }

/* Fields */
.pm-book-area--fs .pm-field { font-size: 13px; gap: 8px; }
.pm-book-area--fs .pm-field-k { font-size: 12px; width: 96px; }
.pm-book-area--fs .pm-field-list { gap: 6px; }

/* Status flow */
.pm-book-area--fs .pm-sf-item { padding: 8px 13px; }
.pm-book-area--fs .pm-sf-badge { font-size: 11px; padding: 3px 9px; }
.pm-book-area--fs .pm-sf-desc  { font-size: 12.5px; }
.pm-book-area--fs .pm-sf-arrow { font-size: 18px; }

/* Flow steps */
.pm-book-area--fs .pm-fstep { padding: 10px 13px; gap: 12px; }
.pm-book-area--fs .pm-fstep__icon { width: 36px; height: 36px; font-size: 16px; }
.pm-book-area--fs .pm-fstep__name { font-size: 13.5px; }
.pm-book-area--fs .pm-fstep__desc { font-size: 12px; margin-top: 3px; }
.pm-book-area--fs .pm-flow-v { gap: 7px; }

/* Numbered steps */
.pm-book-area--fs .pm-step-n { width: 26px; height: 26px; font-size: 12px; }
.pm-book-area--fs .pm-step-title { font-size: 13.5px; }
.pm-book-area--fs .pm-step-desc  { font-size: 12.5px; }
.pm-book-area--fs .pm-steps { gap: 9px; }

/* Roles */
.pm-book-area--fs .pm-role { padding: 8px 13px; }
.pm-book-area--fs .pm-role-title { font-size: 13.5px; gap: 8px; }
.pm-book-area--fs .pm-role-icon  { font-size: 17px; }
.pm-book-area--fs .pm-role-desc  { font-size: 12.5px; }
.pm-book-area--fs .pm-roles { gap: 8px; }

/* Glossary */
.pm-book-area--fs .pm-gterm { font-size: 12.5px; padding: 5px 0; }
.pm-book-area--fs .pm-gt-k  { width: 72px; }

/* Footer in fullscreen */
.pm-book-area--fs + .pm-footer {
  background: #1a1e2e;
  border-top-color: rgba(255,255,255,.1);
}
.pm-book-area--fs + .pm-footer .pm-footer__ch { color: rgba(255,255,255,.7); font-size: 12px; }
.pm-book-area--fs + .pm-footer .pm-dot { background: rgba(255,255,255,.25); width: 10px; height: 10px; }
.pm-book-area--fs + .pm-footer .pm-dot--on { background: #f59e0b; width: 26px; border-radius: 5px; }
.pm-book-area--fs + .pm-footer .pm-dot:hover:not(.pm-dot--on) { background: rgba(255,255,255,.5); }

/* ── Navigation ── */
.pm-nav {
  width: 36px; height: 36px; border-radius: 50%; border: none;
  background: #fff; cursor: pointer;
  box-shadow: 0 2px 8px rgba(0,0,0,.18);
  display: flex; align-items: center; justify-content: center;
  font-size: 22px; color: #405189; flex-shrink: 0; transition: all .2s;
}
.pm-nav:hover:not(:disabled) {
  background: #405189; color: #fff;
  box-shadow: 0 4px 14px rgba(64,81,137,.45);
  transform: scale(1.05);
}
.pm-nav:disabled { opacity: .25; cursor: not-allowed; }

/* ── Book inner (3D container) ── */
.pm-book-inner {
  position: relative;
  display: flex;
  border-radius: 8px;
  box-shadow: 0 10px 36px rgba(0,0,0,.28), 0 2px 8px rgba(0,0,0,.14);
  perspective: 2000px;
  perspective-origin: center center;
}

/* ── 3D Flipper ── */
.bp-flipper {
  position: absolute;
  top: 0; bottom: 0;
  width: calc(50% - 7px);
  transform-style: preserve-3d;
  z-index: 10;
  pointer-events: none;
}
.bp-flipper--fwd { right: 0; transform-origin: left center; }
.bp-flipper--bwd { left: 0;  transform-origin: right center; }

.bp-face {
  position: absolute;
  inset: 0;
  background: #fefcf7;
  backface-visibility: hidden;
  -webkit-backface-visibility: hidden;
  overflow: hidden;
}
.bp-face--back { transform: rotateY(180deg); }

/* Rounded outer corners */
.bp-flipper--fwd .bp-face--front { border-radius: 0 8px 8px 0; }
.bp-flipper--fwd .bp-face--back  { border-radius: 8px 0 0 8px; }
.bp-flipper--bwd .bp-face--front { border-radius: 8px 0 0 8px; }
.bp-flipper--bwd .bp-face--back  { border-radius: 0 8px 8px 0; }

/* Edge gradient for paper-curl realism */
.bp-face--front::after,
.bp-face--back::after {
  content: ''; position: absolute; inset: 0; pointer-events: none;
}
.bp-flipper--fwd .bp-face--front::after { background: linear-gradient(to left,  rgba(0,0,0,.15) 0%, transparent 50%); }
.bp-flipper--fwd .bp-face--back::after  { background: linear-gradient(to right, rgba(0,0,0,.10) 0%, transparent 50%); }
.bp-flipper--bwd .bp-face--front::after { background: linear-gradient(to right, rgba(0,0,0,.15) 0%, transparent 50%); }
.bp-flipper--bwd .bp-face--back::after  { background: linear-gradient(to left,  rgba(0,0,0,.10) 0%, transparent 50%); }

/* Page turn shadows */
.bp-shadow {
  position: absolute; inset: 0; opacity: 0; pointer-events: none; z-index: 2;
}
.bp-shadow--from-right { background: linear-gradient(to right, transparent 55%, rgba(0,0,0,.22) 100%); }
.bp-shadow--from-left  { background: linear-gradient(to left,  transparent 55%, rgba(0,0,0,.22) 100%); }

/* ── Pages ── */
.pm-page { background: #fefcf7; flex: 1; min-width: 0; position: relative; padding-bottom: 20px; }
.pm-page--l { border-radius: 8px 0 0 8px; overflow: hidden; }
.pm-page--r { border-radius: 0 8px 8px 0; overflow: hidden; }
.pm-pg {
  padding: 18px 18px 6px; overflow-y: auto; max-height: 400px; min-height: 350px;
  font-size: 12px; line-height: 1.55; color: #212529;
}
.pm-pg::-webkit-scrollbar { width: 4px; }
.pm-pg::-webkit-scrollbar-track { background: transparent; }
.pm-pg::-webkit-scrollbar-thumb { background: #dee2e6; border-radius: 2px; }
.pm-pgnum { position: absolute; bottom: 5px; font-size: 10px; color: #bbb; font-style: italic; }
.pm-pgnum--l { left: 18px; }
.pm-pgnum--r { right: 18px; }

/* ── Spine ── */
.pm-spine {
  width: 14px; flex-shrink: 0;
  background: linear-gradient(180deg, #202c6b 0%, #405189 35%, #5870b0 55%, #405189 75%, #202c6b 100%);
  display: flex; flex-direction: column; align-items: center; justify-content: space-between; padding: 10px 0;
}
.pm-spine__dot { width: 5px; height: 5px; border-radius: 50%; background: rgba(255,255,255,.35); }

/* ── Footer ── */
.pm-footer {
  padding: 8px 14px 4px; background: #fff; border-top: 1px solid #e9ecef;
  display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-shrink: 0;
}
.pm-footer__ch { font-size: 11px; color: #405189; font-weight: 600; flex: 1; min-width: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.pm-footer__dots { display: flex; gap: 5px; align-items: center; flex-shrink: 0; }
.pm-dot {
  width: 8px; height: 8px; border-radius: 50%; border: none;
  background: #c0c6df; cursor: pointer; padding: 0; transition: all .2s;
}
.pm-dot--on { background: #405189; width: 22px; border-radius: 4px; }
.pm-dot:hover:not(.pm-dot--on) { background: #8896c8; }

/* ── Page content typography ── */
.pm-ch-head { font-size: 14px; font-weight: 700; color: #1e2a5e; display: flex; align-items: center; }
.pm-ch-rule { height: 2px; background: linear-gradient(90deg, #405189 0%, transparent 100%); margin: 6px 0 10px; border-radius: 1px; }
.pm-body { font-size: 12px; color: #343a40; margin-bottom: 10px; line-height: 1.6; }
.pm-section-label {
  font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px;
  color: #6c757d; margin-top: 10px; margin-bottom: 5px;
  padding-bottom: 3px; border-bottom: 1px solid #e9ecef;
}
.pm-list { display: flex; flex-direction: column; gap: 5px; }
.pm-list-item { display: flex; align-items: flex-start; gap: 6px; font-size: 12px; color: #343a40; }
.pm-list-icon { color: #198754; flex-shrink: 0; font-size: 13px; margin-top: 1px; }
.pm-list-icon--warn { color: #e0a800; }
.pm-tip {
  background: rgba(64,81,137,.07); border: 1px solid rgba(64,81,137,.18);
  border-radius: 6px; padding: 7px 10px; font-size: 11px; color: #405189;
}
.pm-box { background: #f8f9fa; border-radius: 8px; padding: 10px 12px; border: 1px solid #e9ecef; }

/* Cycle chips */
.pm-cycle { display: flex; align-items: center; gap: 5px; flex-wrap: wrap; margin-top: 4px; }
.pm-chip { font-size: 10px; font-weight: 700; padding: 2px 8px; border-radius: 4px; }
.pm-chip--ppmp { background: rgba(64,81,137,.13); color: #405189; }
.pm-chip--app  { background: rgba(25,135,84,.11);  color: #146c43; }
.pm-chip--spp  { background: rgba(255,193,7,.16);  color: #856404; }
.pm-chip--rfq  { background: rgba(13,202,240,.13); color: #055160; }
.pm-chip--po   { background: rgba(220,53,69,.11);  color: #842029; }

/* TOC */
.pm-toc { display: flex; flex-direction: column; }
.pm-toc-row {
  display: flex; align-items: center; gap: 8px; padding: 6px 0;
  border-bottom: 1px dotted #e0e3ef; font-size: 12px;
}
.pm-toc-num {
  width: 19px; height: 19px; background: #405189; color: #fff;
  border-radius: 50%; display: flex; align-items: center; justify-content: center;
  font-size: 9px; font-weight: 700; flex-shrink: 0;
}
.pm-toc-name { flex: 1; color: #343a40; }
.pm-toc-pg { color: #adb5bd; font-size: 10px; flex-shrink: 0; }

/* Field list */
.pm-field-list { display: flex; flex-direction: column; gap: 4px; }
.pm-field { display: flex; align-items: flex-start; gap: 6px; font-size: 12px; line-height: 1.4; }
.pm-field-k { flex-shrink: 0; font-weight: 700; color: #212529; font-size: 11px; width: 84px; padding-top: 1px; }
.pm-field-k--blue   { color: #0a58ca; }
.pm-field-k--green  { color: #146c43; }
.pm-field-k--warn   { color: #856404; }
.pm-field-v { color: #495057; }

/* Status flow */
.pm-status-flow { display: flex; flex-direction: column; gap: 0; }
.pm-sf-item { padding: 6px 10px; background: #f8f9fa; border-radius: 7px; border: 1px solid #e9ecef; }
.pm-sf-arrow { text-align: center; color: #c0c6df; font-size: 15px; line-height: 1; padding: 1px 0; }
.pm-sf-badge {
  display: inline-flex; align-items: center; font-size: 10px; font-weight: 700;
  padding: 2px 7px; border-radius: 4px; margin-bottom: 3px;
}
.pm-sf-badge--draft    { background: #f0f2f8; color: #6c757d; border: 1px solid #dee2e6; }
.pm-sf-badge--review   { background: rgba(255,193,7,.15);  color: #856404; }
.pm-sf-badge--approval { background: rgba(13,202,240,.12); color: #055160; }
.pm-sf-badge--approved { background: rgba(25,135,84,.12);  color: #146c43; }
.pm-sf-badge--final    { background: rgba(64,81,137,.13);  color: #405189; }
.pm-sf-desc { font-size: 11px; color: #6c757d; }

/* Vertical flow (cycle diagram) */
.pm-flow-v { display: flex; flex-direction: column; gap: 5px; }
.pm-fstep {
  display: flex; gap: 10px; align-items: flex-start;
  padding: 7px 10px; border-radius: 7px; border: 1px solid #e9ecef; background: #f8f9fa;
}
.pm-fstep__icon {
  width: 28px; height: 28px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center; font-size: 13px; flex-shrink: 0;
}
.pm-fstep--ppmp .pm-fstep__icon { background: rgba(64,81,137,.12);  color: #405189; }
.pm-fstep--app  .pm-fstep__icon { background: rgba(25,135,84,.12);  color: #146c43; }
.pm-fstep--spp  .pm-fstep__icon { background: rgba(255,193,7,.16);  color: #856404; }
.pm-fstep--rfq  .pm-fstep__icon { background: rgba(13,202,240,.12); color: #055160; }
.pm-fstep--aob  .pm-fstep__icon { background: rgba(111,66,193,.12); color: #4d0099; }
.pm-fstep--bac  .pm-fstep__icon { background: rgba(64,81,137,.12);  color: #405189; }
.pm-fstep--po   .pm-fstep__icon { background: rgba(220,53,69,.11);  color: #842029; }
.pm-fstep--iar  .pm-fstep__icon { background: rgba(10,179,156,.11); color: #0a6640; }
.pm-fstep__name { font-size: 12px; font-weight: 700; color: #212529; }
.pm-fstep__desc { font-size: 11px; color: #6c757d; margin-top: 2px; }

/* Numbered steps */
.pm-steps { display: flex; flex-direction: column; gap: 7px; }
.pm-step { display: flex; gap: 10px; align-items: flex-start; }
.pm-step-n {
  width: 22px; height: 22px; border-radius: 50%; background: #405189; color: #fff;
  font-size: 11px; font-weight: 700; display: flex; align-items: center; justify-content: center;
  flex-shrink: 0; margin-top: 1px;
}
.pm-step-b { flex: 1; }
.pm-step-title { font-size: 12px; font-weight: 700; color: #212529; margin-bottom: 2px; }
.pm-step-desc { font-size: 11px; color: #495057; line-height: 1.5; }

/* Roles */
.pm-roles { display: flex; flex-direction: column; gap: 6px; }
.pm-role { padding: 6px 10px; background: #f8f9fa; border-radius: 7px; border: 1px solid #e9ecef; }
.pm-role-title { font-size: 12px; font-weight: 700; color: #212529; display: flex; align-items: center; gap: 6px; margin-bottom: 2px; }
.pm-role-icon { font-size: 14px; }
.pm-role-icon--blue   { color: #0a58ca; }
.pm-role-icon--green  { color: #146c43; }
.pm-role-icon--warn   { color: #856404; }
.pm-role-icon--purple { color: #4d0099; }
.pm-role-icon--teal   { color: #055160; }
.pm-role-icon--red    { color: #842029; }
.pm-role-desc { font-size: 11px; color: #6c757d; line-height: 1.4; }

/* Glossary */
.pm-glossary { display: flex; flex-direction: column; }
.pm-gterm { display: flex; gap: 8px; padding: 4px 0; border-bottom: 1px solid #f2f2f5; font-size: 11px; line-height: 1.4; }
.pm-gt-k { font-weight: 700; color: #405189; width: 64px; flex-shrink: 0; }
.pm-gt-v { color: #495057; }

/* Page flip handled by GSAP — no CSS keyframes needed */
</style>
