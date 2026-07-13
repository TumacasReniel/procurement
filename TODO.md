# TODO - APP/PPMP/SPP module enhancements

## Step 1: Backend deterministic transition mapping
- [x] Update `app/Services/FAIMS/Procurement/ProcurementPPMPClass.php`
  - [x] Extract/introduce a single mapping method (`transitionPolicy`) to determine allowed transitions.
  - [x] Refactor `ensureUserCanAdvancePpmpSubmission()` to use this mapping.
  - [ ] Ensure `nextSubmissionStep()` uses the same mapping (or shares the same transition map).


## Step 2: UI alignment
- [ ] Update `resources/js/Pages/Modules/FAIMS/Procurement/PPMP/Index.vue`
  - Reduce duplicated booleans by basing advance/consolidate buttons on the same normalized plan_type/status and backend-derived values already present on each `list`.

- [ ] Update `resources/js/Pages/Modules/FAIMS/Procurement/PPMP/View.vue`
  - Align `canShowAdvanceAction`/modal labels with the same transition rules.

## Step 3: Validation / testing
- [ ] Run `php artisan` test suite (if present)
- [ ] Manually verify PPMP/SPP/APP flows:
  - PPMP Pending -> For Review
  - PPMP For Review -> Reviewed/For Submission
  - PPMP Reviewed/For Submission -> Submitted/For Consolidation
  - SPP Pending -> For Review
  - SPP For Review -> Reviewed/For Submission
  - SPP Reviewed/For Submission -> Submitted/For Consolidation
  - Consolidate PPMP/SPP -> APP via `approve_to_app`

