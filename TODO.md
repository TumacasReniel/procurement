# TODO - Fix PR Status when updating NOA status

## Task: Fix PR status when updating NOA status from "Served to Supplier" to "Conformed"

### Steps:
- [x] Remove `dd($updated_pr_status);` from ProcurementBacNoaClass.php (Already removed)
- [x] Fix status name mismatch in ProcurementBac.php - Changed from 'NOA Conformed' to 'Conformed' to match the actual status name used in ListStatus
- [x] Fix status name mismatch in ProcurementBac.php - Changed from 'NOA Served to Supplier' to 'Served to Supplier' to match the actual status name used in ListStatus
- [x] Updated both `overall_status()` and `overall_substatus()` methods

### Status: Completed

## Summary of Changes:
The issue was a status name mismatch. In `ProcurementBacNoaClass.php`, when updating NOA status to "Conformed", it uses `ListStatus::getID('Conformed','Procurement')` - which stores the status as "Conformed" (without "NOA" prefix).

However, in `ProcurementBac.php`'s `overall_status()` and `overall_substatus()` methods, the hierarchy was checking for 'NOA Conformed' and 'NOA Served to Supplier' which would never match because the actual status name is 'Conformed' and 'Served to Supplier'.

The fix updates the status_hierarchy arrays in both methods to use the correct status names ('Conformed' and 'Served to Supplier') while still mapping to the correct PR statuses ('NOA Conformed' and 'NOA Served to Supplier').

