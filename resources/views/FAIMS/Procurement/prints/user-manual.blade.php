<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Procurement User Manual — FAIMS DOST-IX</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon@4/fonts/remixicon.css">
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: Arial, Helvetica, sans-serif; background: #fff; color: #212529; font-size: 12px; line-height: 1.55; }

    /* ── Print header (agency) ── */
    .um-doc-header {
      text-align: center;
      padding: 18px 24px 10px;
      border-bottom: 2px solid #405189;
      margin-bottom: 18px;
    }
    .um-doc-header__agency { font-size: 10px; letter-spacing: 1.5px; text-transform: uppercase; color: #405189; font-weight: 700; }
    .um-doc-header__title  { font-size: 20px; font-weight: 800; color: #1e2a5e; margin: 4px 0 2px; }
    .um-doc-header__sub    { font-size: 11px; color: #6c757d; }
    .um-doc-header__badge  { display: inline-block; margin-top: 6px; font-size: 9px; font-weight: 700; padding: 2px 10px; border-radius: 20px; background: #405189; color: #fff; letter-spacing: .5px; }

    /* ── Chapter ── */
    .um-chapter { page-break-after: always; padding: 16px 28px 24px; }
    .um-chapter:last-child { page-break-after: avoid; }
    .um-ch-head { font-size: 14px; font-weight: 700; color: #1e2a5e; display: flex; align-items: center; gap: 6px; }
    .um-ch-rule { height: 2px; background: linear-gradient(90deg, #405189 0%, transparent 100%); margin: 5px 0 12px; border-radius: 1px; }
    .um-section-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; color: #6c757d; margin: 12px 0 5px; padding-bottom: 3px; border-bottom: 1px solid #e9ecef; }
    .um-body { font-size: 12px; color: #343a40; margin-bottom: 8px; line-height: 1.6; }
    .um-tip  { background: rgba(64,81,137,.07); border: 1px solid rgba(64,81,137,.18); border-radius: 6px; padding: 7px 10px; font-size: 11px; color: #405189; margin-bottom: 8px; }
    .um-box  { background: #f8f9fa; border-radius: 8px; padding: 10px 12px; border: 1px solid #e9ecef; margin-bottom: 8px; }

    /* ── Lists ── */
    .um-list { display: flex; flex-direction: column; gap: 4px; margin-bottom: 8px; }
    .um-list-item { display: flex; align-items: flex-start; gap: 6px; font-size: 12px; color: #343a40; }
    .um-list-icon { color: #198754; flex-shrink: 0; font-size: 13px; margin-top: 1px; }
    .um-list-icon--warn { color: #e0a800; }

    /* ── Cycle chips ── */
    .um-cycle { display: flex; align-items: center; gap: 5px; flex-wrap: wrap; margin: 4px 0 10px; }
    .um-chip  { font-size: 10px; font-weight: 700; padding: 2px 8px; border-radius: 4px; }
    .um-chip--ppmp { background: rgba(64,81,137,.13); color: #405189; }
    .um-chip--app  { background: rgba(25,135,84,.11);  color: #146c43; }
    .um-chip--spp  { background: rgba(255,193,7,.16);  color: #856404; }
    .um-chip--rfq  { background: rgba(13,202,240,.13); color: #055160; }
    .um-chip--po   { background: rgba(220,53,69,.11);  color: #842029; }

    /* ── Fields ── */
    .um-field-list { display: flex; flex-direction: column; gap: 4px; margin-bottom: 8px; }
    .um-field { display: flex; align-items: flex-start; gap: 6px; font-size: 12px; }
    .um-field-k { flex-shrink: 0; font-weight: 700; color: #212529; font-size: 11px; width: 90px; }
    .um-field-k--blue  { color: #0a58ca; }
    .um-field-k--green { color: #146c43; }
    .um-field-k--warn  { color: #856404; }
    .um-field-v { color: #495057; }

    /* ── Status flow ── */
    .um-status-flow { display: flex; flex-direction: column; gap: 0; margin-bottom: 8px; }
    .um-sf-item  { padding: 5px 10px; background: #f8f9fa; border-radius: 7px; border: 1px solid #e9ecef; }
    .um-sf-arrow { text-align: center; color: #c0c6df; font-size: 14px; line-height: 1.2; }
    .um-sf-badge { display: inline-flex; align-items: center; font-size: 10px; font-weight: 700; padding: 2px 7px; border-radius: 4px; margin-bottom: 2px; }
    .um-sf-badge--draft    { background: #f0f2f8; color: #6c757d; border: 1px solid #dee2e6; }
    .um-sf-badge--review   { background: rgba(255,193,7,.15);  color: #856404; }
    .um-sf-badge--approval { background: rgba(13,202,240,.12); color: #055160; }
    .um-sf-badge--approved { background: rgba(25,135,84,.12);  color: #146c43; }
    .um-sf-badge--final    { background: rgba(64,81,137,.13);  color: #405189; }
    .um-sf-desc { font-size: 11px; color: #6c757d; }

    /* ── Steps ── */
    .um-steps { display: flex; flex-direction: column; gap: 6px; margin-bottom: 8px; }
    .um-step  { display: flex; gap: 10px; align-items: flex-start; }
    .um-step-n { width: 22px; height: 22px; border-radius: 50%; background: #405189; color: #fff; font-size: 11px; font-weight: 700; display: flex; align-items: center; justify-content: center; flex-shrink: 0; margin-top: 1px; }
    .um-step-b { flex: 1; }
    .um-step-title { font-size: 12px; font-weight: 700; color: #212529; margin-bottom: 1px; }
    .um-step-desc  { font-size: 11px; color: #495057; line-height: 1.5; }

    /* ── Flow steps ── */
    .um-flow-v { display: flex; flex-direction: column; gap: 5px; margin-bottom: 8px; }
    .um-fstep  { display: flex; gap: 10px; align-items: flex-start; padding: 7px 10px; border-radius: 7px; border: 1px solid #e9ecef; background: #f8f9fa; }
    .um-fstep__icon { width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 13px; flex-shrink: 0; }
    .um-fstep--ppmp .um-fstep__icon { background: rgba(64,81,137,.12);  color: #405189; }
    .um-fstep--app  .um-fstep__icon { background: rgba(25,135,84,.12);  color: #146c43; }
    .um-fstep--spp  .um-fstep__icon { background: rgba(255,193,7,.16);  color: #856404; }
    .um-fstep--rfq  .um-fstep__icon { background: rgba(13,202,240,.12); color: #055160; }
    .um-fstep--aob  .um-fstep__icon { background: rgba(111,66,193,.12); color: #4d0099; }
    .um-fstep--bac  .um-fstep__icon { background: rgba(64,81,137,.12);  color: #405189; }
    .um-fstep--po   .um-fstep__icon { background: rgba(220,53,69,.11);  color: #842029; }
    .um-fstep--iar  .um-fstep__icon { background: rgba(10,179,156,.11); color: #0a6640; }
    .um-fstep__name { font-size: 12px; font-weight: 700; color: #212529; }
    .um-fstep__desc { font-size: 11px; color: #6c757d; margin-top: 2px; }

    /* ── Roles ── */
    .um-roles { display: flex; flex-direction: column; gap: 5px; margin-bottom: 8px; }
    .um-role  { padding: 6px 10px; background: #f8f9fa; border-radius: 7px; border: 1px solid #e9ecef; }
    .um-role-title { font-size: 12px; font-weight: 700; color: #212529; display: flex; align-items: center; gap: 6px; margin-bottom: 2px; }
    .um-role-desc  { font-size: 11px; color: #6c757d; line-height: 1.4; }

    /* ── Glossary ── */
    .um-glossary { display: flex; flex-direction: column; }
    .um-gterm { display: flex; gap: 8px; padding: 4px 0; border-bottom: 1px solid #f2f2f5; font-size: 11px; line-height: 1.4; }
    .um-gt-k  { font-weight: 700; color: #405189; width: 70px; flex-shrink: 0; }
    .um-gt-v  { color: #495057; }

    /* ── TOC ── */
    .um-toc { display: flex; flex-direction: column; margin-bottom: 8px; }
    .um-toc-row { display: flex; align-items: center; gap: 8px; padding: 6px 0; border-bottom: 1px dotted #e0e3ef; font-size: 12px; }
    .um-toc-num  { width: 19px; height: 19px; background: #405189; color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 9px; font-weight: 700; flex-shrink: 0; }
    .um-toc-name { flex: 1; color: #343a40; }
    .um-toc-pg   { color: #adb5bd; font-size: 10px; }

    /* ── Two-column chapter layout ── */
    .um-cols { display: flex; gap: 20px; }
    .um-col  { flex: 1; min-width: 0; }

    /* ── Print ── */
    @media print {
      .um-chapter { page-break-after: always; }
      .um-chapter:last-child { page-break-after: avoid; }
      body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    }
  </style>
</head>
<body onload="window.print()">

  <!-- Document header (prints on first page) -->
  <div class="um-doc-header">
    <div class="um-doc-header__agency">Department of Science &amp; Technology · Regional Office IX · Zamboanga Peninsula</div>
    <div class="um-doc-header__title">Procurement User Manual</div>
    <div class="um-doc-header__sub">FAIMS Integrated Procurement System</div>
    <span class="um-doc-header__badge">CY {{ date('Y') }} Edition · Republic Act No. 9184</span>
  </div>

  <!-- ══════════════════════════════════════════════════ -->
  <!-- Chapter 1 — Overview / TOC                        -->
  <!-- ══════════════════════════════════════════════════ -->
  <div class="um-chapter">
    <div class="um-ch-head"><i class="ri-list-check-3" style="color:#405189"></i> Table of Contents</div>
    <div class="um-ch-rule"></div>
    <div class="um-toc">
      <div class="um-toc-row"><span class="um-toc-num">1</span><span class="um-toc-name">Procurement Cycle Overview</span><span class="um-toc-pg">2</span></div>
      <div class="um-toc-row"><span class="um-toc-num">2</span><span class="um-toc-name">Project Procurement Management Plan (PPMP)</span><span class="um-toc-pg">3</span></div>
      <div class="um-toc-row"><span class="um-toc-num">3</span><span class="um-toc-name">Annual Procurement Plan (APP)</span><span class="um-toc-pg">4</span></div>
      <div class="um-toc-row"><span class="um-toc-num">4</span><span class="um-toc-name">Supplemental Procurement Plan (SPP)</span><span class="um-toc-pg">5</span></div>
      <div class="um-toc-row"><span class="um-toc-num">5</span><span class="um-toc-name">Purchase Request, RFQ &amp; Purchase Order</span><span class="um-toc-pg">6</span></div>
      <div class="um-toc-row"><span class="um-toc-num">6</span><span class="um-toc-name">Roles, Responsibilities &amp; Glossary</span><span class="um-toc-pg">7</span></div>
    </div>
    <p class="um-body" style="margin-top:10px">
      This manual covers the end-to-end procurement process implemented in the FAIMS system for DOST-IX,
      in accordance with Republic Act No. 9184 (Government Procurement Reform Act) and the 2016 Revised IRR.
    </p>
  </div>

  <!-- ══════════════════════════════════════════════════ -->
  <!-- Chapter 2 — Procurement Cycle Overview            -->
  <!-- ══════════════════════════════════════════════════ -->
  <div class="um-chapter">
    <div class="um-ch-head"><i class="ri-refresh-line" style="color:#405189"></i> Chapter 1 — Procurement Cycle Overview</div>
    <div class="um-ch-rule"></div>
    <p class="um-body">
      The FAIMS procurement module follows a linear, stage-gated workflow mandated by RA 9184.
      Each stage must be completed and approved before the next stage can begin.
    </p>
    <div class="um-cycle">
      <span class="um-chip um-chip--ppmp">PPMP</span>
      <span style="color:#adb5bd;font-size:13px">→</span>
      <span class="um-chip um-chip--app">APP / SPP</span>
      <span style="color:#adb5bd;font-size:13px">→</span>
      <span class="um-chip um-chip--rfq">RFQ / ITB</span>
      <span style="color:#adb5bd;font-size:13px">→</span>
      <span class="um-chip" style="background:rgba(111,66,193,.12);color:#4d0099">BAC</span>
      <span style="color:#adb5bd;font-size:13px">→</span>
      <span class="um-chip um-chip--rfq" style="background:rgba(25,135,84,.11);color:#146c43">NOA / NTP</span>
      <span style="color:#adb5bd;font-size:13px">→</span>
      <span class="um-chip um-chip--po">PO → IAR</span>
    </div>
    <div class="um-cols">
      <div class="um-col">
        <div class="um-section-label">Phase 1 — Planning</div>
        <div class="um-flow-v">
          <div class="um-fstep um-fstep--ppmp">
            <div class="um-fstep__icon"><i class="ri-file-add-line"></i></div>
            <div><div class="um-fstep__name">PPMP</div><div class="um-fstep__desc">Each unit lists planned procurements for the year with budget estimates.</div></div>
          </div>
          <div class="um-fstep um-fstep--app">
            <div class="um-fstep__icon"><i class="ri-file-list-3-line"></i></div>
            <div><div class="um-fstep__name">APP</div><div class="um-fstep__desc">Procurement Office consolidates all PPMPs into the agency APP.</div></div>
          </div>
          <div class="um-fstep um-fstep--spp">
            <div class="um-fstep__icon"><i class="ri-file-add-fill"></i></div>
            <div><div class="um-fstep__name">SPP</div><div class="um-fstep__desc">Mid-year additions after Final APP is approved.</div></div>
          </div>
        </div>
      </div>
      <div class="um-col">
        <div class="um-section-label">Phase 2 — Execution</div>
        <div class="um-flow-v">
          <div class="um-fstep um-fstep--rfq">
            <div class="um-fstep__icon"><i class="ri-price-tag-3-line"></i></div>
            <div><div class="um-fstep__name">RFQ / ITB</div><div class="um-fstep__desc">Quotation or competitive bidding based on ABC threshold.</div></div>
          </div>
          <div class="um-fstep um-fstep--bac">
            <div class="um-fstep__icon"><i class="ri-group-line"></i></div>
            <div><div class="um-fstep__name">BAC Resolution → NOA → NTP</div><div class="um-fstep__desc">Award to lowest compliant bidder, then proceed.</div></div>
          </div>
          <div class="um-fstep um-fstep--po">
            <div class="um-fstep__icon"><i class="ri-shopping-cart-line"></i></div>
            <div><div class="um-fstep__name">PO → Delivery → IAR</div><div class="um-fstep__desc">Purchase Order issued, items delivered and inspected.</div></div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- ══════════════════════════════════════════════════ -->
  <!-- Chapter 3 — PPMP                                  -->
  <!-- ══════════════════════════════════════════════════ -->
  <div class="um-chapter">
    <div class="um-ch-head"><i class="ri-file-add-line" style="color:#405189"></i> Chapter 2 — Project Procurement Management Plan (PPMP)</div>
    <div class="um-ch-rule"></div>
    <p class="um-body">The PPMP is prepared by each implementing unit listing all goods, services, and infrastructure they intend to procure for the calendar year, together with estimated costs and schedules.</p>
    <div class="um-cols">
      <div class="um-col">
        <div class="um-section-label">PPMP Phases</div>
        <div class="um-box">
          <div style="font-size:11px;font-weight:700;color:#856404;margin-bottom:4px"><i class="ri-draft-line"></i> Indicative PPMP <span style="background:rgba(255,193,7,.2);color:#856404;font-size:9px;padding:1px 5px;border-radius:3px;font-weight:700">PRE-GAA</span></div>
          <div style="font-size:11px;color:#6c757d">Based on proposed budget. Used to build the Indicative APP for transparency posting.</div>
        </div>
        <div class="um-box" style="margin-top:6px">
          <div style="font-size:11px;font-weight:700;color:#146c43;margin-bottom:4px"><i class="ri-flag-2-line"></i> Final PPMP <span style="background:rgba(25,135,84,.18);color:#146c43;font-size:9px;padding:1px 5px;border-radius:3px;font-weight:700">POST-GAA</span></div>
          <div style="font-size:11px;color:#6c757d">Based on approved budget. Unit head clicks <em>Mark as Final</em>. Feeds the Final APP.</div>
        </div>
        <div class="um-tip" style="margin-top:8px">
          <strong>Rule:</strong> Indicative PPMPs → Indicative APP only. Final PPMPs → Final APP only.
        </div>
      </div>
      <div class="um-col">
        <div class="um-section-label">Key Fields</div>
        <div class="um-field-list">
          <div class="um-field"><span class="um-field-k um-field-k--blue">Plan Year</span><span class="um-field-v">Calendar year covered by this PPMP.</span></div>
          <div class="um-field"><span class="um-field-k">Unit</span><span class="um-field-v">Implementing unit submitting the plan.</span></div>
          <div class="um-field"><span class="um-field-k um-field-k--warn">Plan Type</span><span class="um-field-v">Indicative or Final — controls which APP it feeds.</span></div>
          <div class="um-field"><span class="um-field-k">Items</span><span class="um-field-v">Line items: description, qty, unit cost, schedule.</span></div>
          <div class="um-field"><span class="um-field-k um-field-k--green">Status</span><span class="um-field-v">Draft → For Review → For Approval → Approved → Final.</span></div>
        </div>
        <div class="um-section-label" style="margin-top:10px">Status Flow</div>
        <div class="um-status-flow">
          <div class="um-sf-item"><span class="um-sf-badge um-sf-badge--draft">Draft</span><div class="um-sf-desc">Being prepared by the unit.</div></div>
          <div class="um-sf-arrow">↓</div>
          <div class="um-sf-item"><span class="um-sf-badge um-sf-badge--review">For Review</span><div class="um-sf-desc">Submitted for review.</div></div>
          <div class="um-sf-arrow">↓</div>
          <div class="um-sf-item"><span class="um-sf-badge um-sf-badge--approved">Approved</span><div class="um-sf-desc">Ready to be added to APP.</div></div>
          <div class="um-sf-arrow">↓</div>
          <div class="um-sf-item"><span class="um-sf-badge um-sf-badge--final">Final</span><div class="um-sf-desc">Locked — feeds the Final APP.</div></div>
        </div>
      </div>
    </div>
  </div>

  <!-- ══════════════════════════════════════════════════ -->
  <!-- Chapter 4 — APP                                   -->
  <!-- ══════════════════════════════════════════════════ -->
  <div class="um-chapter">
    <div class="um-ch-head"><i class="ri-file-list-3-line" style="color:#405189"></i> Chapter 3 — Annual Procurement Plan (APP)</div>
    <div class="um-ch-rule"></div>
    <p class="um-body">The APP is the agency-wide consolidation of all unit PPMPs. The Procurement Officer creates the APP; the HOPE approves it. The Final APP must be posted on PhilGEPS within 30 days of GAA enactment (RA 9184 Sec. 7).</p>
    <div class="um-section-label">How to Create an APP</div>
    <div class="um-steps">
      <div class="um-step"><div class="um-step-n">1</div><div class="um-step-b"><div class="um-step-title">Go to APP tab → Click "Create APP"</div><div class="um-step-desc">Select the plan year and type (Indicative or Final). Only approved PPMPs of the matching type appear.</div></div></div>
      <div class="um-step"><div class="um-step-n">2</div><div class="um-step-b"><div class="um-step-title">Select PPMPs to include</div><div class="um-step-desc">Choose which unit PPMPs to consolidate. All their items are pulled into the APP automatically.</div></div></div>
      <div class="um-step"><div class="um-step-n">3</div><div class="um-step-b"><div class="um-step-title">Submit for HOPE approval</div><div class="um-step-desc">The APP goes through review → approval workflow. Once approved, it can be used to create PRs.</div></div></div>
      <div class="um-step"><div class="um-step-n">4</div><div class="um-step-b"><div class="um-step-title">Post to PhilGEPS</div><div class="um-step-desc">Download the Final APP and post to the PhilGEPS portal within the prescribed period.</div></div></div>
    </div>
    <div class="um-tip">Only items from an <strong>approved Final APP</strong> can generate Purchase Requests (PR). Indicative APP items are for planning visibility only.</div>
  </div>

  <!-- ══════════════════════════════════════════════════ -->
  <!-- Chapter 5 — SPP                                   -->
  <!-- ══════════════════════════════════════════════════ -->
  <div class="um-chapter">
    <div class="um-ch-head"><i class="ri-file-add-fill" style="color:#405189"></i> Chapter 4 — Supplemental Procurement Plan (SPP)</div>
    <div class="um-ch-rule"></div>
    <p class="um-body">The SPP covers unforeseen procurement needs that arise after the Final APP is approved. It follows the same PPMP-to-APP flow but is treated as a mid-year addition.</p>
    <div class="um-cols">
      <div class="um-col">
        <div class="um-section-label">When to Use SPP</div>
        <div class="um-list">
          <div class="um-list-item"><i class="ri-checkbox-circle-line um-list-icon"></i><span>New project funded mid-year not in the original APP.</span></div>
          <div class="um-list-item"><i class="ri-checkbox-circle-line um-list-icon"></i><span>Realignment of funds resulting in new procurement items.</span></div>
          <div class="um-list-item"><i class="ri-checkbox-circle-line um-list-icon"></i><span>Additional requirements approved by management after GAA.</span></div>
        </div>
        <div class="um-section-label" style="margin-top:10px">Restrictions</div>
        <div class="um-list">
          <div class="um-list-item"><i class="ri-error-warning-line um-list-icon um-list-icon--warn"></i><span>SPP can only be created <strong>after</strong> the Final APP is approved.</span></div>
          <div class="um-list-item"><i class="ri-error-warning-line um-list-icon um-list-icon--warn"></i><span>SPP items must have a funding source.</span></div>
          <div class="um-list-item"><i class="ri-error-warning-line um-list-icon um-list-icon--warn"></i><span>Each SPP is numbered sequentially within the year.</span></div>
        </div>
      </div>
      <div class="um-col">
        <div class="um-section-label">SPP vs APP Comparison</div>
        <div class="um-box">
          <table style="width:100%;font-size:11px;border-collapse:collapse">
            <thead><tr style="background:#f0f2f8"><th style="padding:5px 8px;text-align:left">Feature</th><th style="padding:5px 8px;text-align:left">APP</th><th style="padding:5px 8px;text-align:left">SPP</th></tr></thead>
            <tbody>
              <tr style="border-top:1px solid #e9ecef"><td style="padding:4px 8px">Timing</td><td style="padding:4px 8px">Start of year</td><td style="padding:4px 8px">Mid-year</td></tr>
              <tr style="border-top:1px solid #e9ecef"><td style="padding:4px 8px">Basis</td><td style="padding:4px 8px">GAA budget</td><td style="padding:4px 8px">Supplemental budget</td></tr>
              <tr style="border-top:1px solid #e9ecef"><td style="padding:4px 8px">Prerequisite</td><td style="padding:4px 8px">Approved PPMPs</td><td style="padding:4px 8px">Approved Final APP</td></tr>
              <tr style="border-top:1px solid #e9ecef"><td style="padding:4px 8px">PhilGEPS posting</td><td style="padding:4px 8px">Required (30 days)</td><td style="padding:4px 8px">Required</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- ══════════════════════════════════════════════════ -->
  <!-- Chapter 6 — PR / RFQ / PO                        -->
  <!-- ══════════════════════════════════════════════════ -->
  <div class="um-chapter">
    <div class="um-ch-head"><i class="ri-shopping-cart-line" style="color:#405189"></i> Chapter 5 — Purchase Request, RFQ &amp; Purchase Order</div>
    <div class="um-ch-rule"></div>
    <div class="um-cols">
      <div class="um-col">
        <div class="um-section-label">Purchase Request (PR)</div>
        <p class="um-body">Generated from approved APP/SPP items. The requesting unit creates a PR selecting items and quantities. PR goes through the same approval workflow before it reaches BAC.</p>
        <div class="um-section-label" style="margin-top:8px">Mode of Procurement</div>
        <div class="um-list">
          <div class="um-list-item"><i class="ri-checkbox-circle-line um-list-icon"></i><span><strong>Shopping / Small Value:</strong> Below the ABC threshold. BAC solicits at least 3 quotations (RFQ).</span></div>
          <div class="um-list-item"><i class="ri-checkbox-circle-line um-list-icon"></i><span><strong>Competitive Bidding:</strong> Above threshold. Posted on PhilGEPS as Invitation to Bid (ITB).</span></div>
        </div>
        <div class="um-section-label" style="margin-top:8px">Exception Paths</div>
        <div class="um-list">
          <div class="um-list-item"><i class="ri-error-warning-line um-list-icon um-list-icon--warn"></i><span><strong>Rebid:</strong> All bids fail. BAC Resolution declares failure; full bidding restarts.</span></div>
          <div class="um-list-item"><i class="ri-error-warning-line um-list-icon um-list-icon--warn"></i><span><strong>Re-award:</strong> Winner defaults. Next responsive bidder in the AOB is awarded via new NOA.</span></div>
        </div>
      </div>
      <div class="um-col">
        <div class="um-section-label">PO → Delivery → IAR Flow</div>
        <div class="um-flow-v">
          <div class="um-fstep um-fstep--po"><div class="um-fstep__icon"><i class="ri-file-add-line"></i></div><div><div class="um-fstep__name">PO Created</div><div class="um-fstep__desc">Purchase Order generated referencing the PR and awarded supplier.</div></div></div>
          <div class="um-fstep um-fstep--po"><div class="um-fstep__icon"><i class="ri-send-plane-line"></i></div><div><div class="um-fstep__name">PO Issued</div><div class="um-fstep__desc">PO released to supplier for acceptance.</div></div></div>
          <div class="um-fstep um-fstep--po"><div class="um-fstep__icon"><i class="ri-shake-hands-line"></i></div><div><div class="um-fstep__name">PO Conformed</div><div class="um-fstep__desc">Supplier accepts PO terms.</div></div></div>
          <div class="um-fstep um-fstep--iar"><div class="um-fstep__icon"><i class="ri-truck-line"></i></div><div><div class="um-fstep__name">Delivery &amp; IAR</div><div class="um-fstep__desc">Items delivered; IAR committee inspects and accepts.</div></div></div>
          <div class="um-fstep um-fstep--iar"><div class="um-fstep__icon"><i class="ri-checkbox-circle-line"></i></div><div><div class="um-fstep__name">Completed</div><div class="um-fstep__desc">PO completed; items recorded in inventory automatically.</div></div></div>
        </div>
      </div>
    </div>
  </div>

  <!-- ══════════════════════════════════════════════════ -->
  <!-- Chapter 7 — Roles & Glossary                      -->
  <!-- ══════════════════════════════════════════════════ -->
  <div class="um-chapter">
    <div class="um-ch-head"><i class="ri-team-line" style="color:#405189"></i> Chapter 6 — Roles, Responsibilities &amp; Glossary</div>
    <div class="um-ch-rule"></div>
    <div class="um-cols">
      <div class="um-col">
        <div class="um-section-label">Roles &amp; Responsibilities</div>
        <div class="um-roles">
          <div class="um-role"><div class="um-role-title"><i class="ri-user-line" style="color:#0a58ca"></i> Unit Head / Staff</div><div class="um-role-desc">Creates and submits PPMPs and PRs for their implementing unit.</div></div>
          <div class="um-role"><div class="um-role-title"><i class="ri-building-line" style="color:#146c43"></i> Procurement Officer</div><div class="um-role-desc">Consolidates PPMPs into APP/SPP, manages BAC process, issues NOA/NTP/PO.</div></div>
          <div class="um-role"><div class="um-role-title"><i class="ri-group-line" style="color:#4d0099"></i> BAC</div><div class="um-role-desc">Evaluates bids, prepares Abstract of Bids and BAC Resolution, recommends award.</div></div>
          <div class="um-role"><div class="um-role-title"><i class="ri-award-line" style="color:#856404"></i> HOPE</div><div class="um-role-desc">Head of Procuring Entity. Approves APP, BAC Resolution, and major procurement actions.</div></div>
          <div class="um-role"><div class="um-role-title"><i class="ri-shield-check-line" style="color:#055160"></i> IAR Committee</div><div class="um-role-desc">Inspects and accepts delivered items; signs the IAR.</div></div>
          <div class="um-role"><div class="um-role-title"><i class="ri-store-2-line" style="color:#842029"></i> Supply Unit</div><div class="um-role-desc">Records received items, manages inventory after IAR completion.</div></div>
        </div>
      </div>
      <div class="um-col">
        <div class="um-section-label">Glossary</div>
        <div class="um-glossary">
          <div class="um-gterm"><span class="um-gt-k">PPMP</span><span class="um-gt-v">Project Procurement Management Plan</span></div>
          <div class="um-gterm"><span class="um-gt-k">APP</span><span class="um-gt-v">Annual Procurement Plan</span></div>
          <div class="um-gterm"><span class="um-gt-k">SPP</span><span class="um-gt-v">Supplemental Procurement Plan</span></div>
          <div class="um-gterm"><span class="um-gt-k">PR</span><span class="um-gt-v">Purchase Request</span></div>
          <div class="um-gterm"><span class="um-gt-k">RFQ</span><span class="um-gt-v">Request for Quotation</span></div>
          <div class="um-gterm"><span class="um-gt-k">ITB</span><span class="um-gt-v">Invitation to Bid</span></div>
          <div class="um-gterm"><span class="um-gt-k">AOB</span><span class="um-gt-v">Abstract of Bids</span></div>
          <div class="um-gterm"><span class="um-gt-k">BAC</span><span class="um-gt-v">Bids and Awards Committee</span></div>
          <div class="um-gterm"><span class="um-gt-k">NOA</span><span class="um-gt-v">Notice of Award</span></div>
          <div class="um-gterm"><span class="um-gt-k">NTP</span><span class="um-gt-v">Notice to Proceed</span></div>
          <div class="um-gterm"><span class="um-gt-k">PO</span><span class="um-gt-v">Purchase Order</span></div>
          <div class="um-gterm"><span class="um-gt-k">IAR</span><span class="um-gt-v">Inspection and Acceptance Report</span></div>
          <div class="um-gterm"><span class="um-gt-k">HOPE</span><span class="um-gt-v">Head of the Procuring Entity</span></div>
          <div class="um-gterm"><span class="um-gt-k">GAA</span><span class="um-gt-v">General Appropriations Act</span></div>
          <div class="um-gterm"><span class="um-gt-k">ABC</span><span class="um-gt-v">Approved Budget for the Contract</span></div>
          <div class="um-gterm"><span class="um-gt-k">PhilGEPS</span><span class="um-gt-v">Philippine Government Electronic Procurement System</span></div>
          <div class="um-gterm"><span class="um-gt-k">LCRB</span><span class="um-gt-v">Lowest Calculated and Responsive Bid</span></div>
          <div class="um-gterm"><span class="um-gt-k">RA 9184</span><span class="um-gt-v">Government Procurement Reform Act</span></div>
        </div>
      </div>
    </div>
  </div>

</body>
</html>
