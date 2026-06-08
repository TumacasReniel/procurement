<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<SQL
CREATE OR REPLACE VIEW ai_ppmp_view AS
SELECT
    p.id,
    p.code,
    p.title,
    p.date,
    p.purpose,
    p.division_id,
    division.name AS division_name,
    p.unit_id,
    unit.name AS unit_name,
    p.procurement_app_id,
    p.status_id,
    status.name AS status_name,
    p.sub_status_id,
    sub_status.name AS sub_status_name,
    p.submitted_at,
    p.reviewed_at,
    p.approved_at,
    p.consolidated_at,
    p.created_at,
    p.updated_at
FROM procurement_ppmps p
LEFT JOIN list_dropdowns division ON division.id = p.division_id
LEFT JOIN list_units unit ON unit.id = p.unit_id
LEFT JOIN list_statuses status ON status.id = p.status_id
LEFT JOIN list_statuses sub_status ON sub_status.id = p.sub_status_id
SQL);

        DB::statement(<<<SQL
CREATE OR REPLACE VIEW ai_app_view AS
SELECT
    app.id,
    app.code,
    app.year,
    app.version,
    app.title,
    app.app_type_id,
    app_type.name AS app_type_name,
    app.status_id,
    status.name AS status_name,
    app.sub_status_id,
    sub_status.name AS sub_status_name,
    app.created_at,
    app.updated_at,
    COUNT(ppmp.id) AS ppmp_count,
    MAX(ppmp.consolidated_at) AS latest_consolidated_at
FROM procurement_apps app
LEFT JOIN list_dropdowns app_type ON app_type.id = app.app_type_id
LEFT JOIN list_statuses status ON status.id = app.status_id
LEFT JOIN list_statuses sub_status ON sub_status.id = app.sub_status_id
LEFT JOIN procurement_ppmps ppmp ON ppmp.procurement_app_id = app.id
GROUP BY
    app.id,
    app.code,
    app.year,
    app.version,
    app.title,
    app.app_type_id,
    app_type.name,
    app.status_id,
    status.name,
    app.sub_status_id,
    sub_status.name,
    app.created_at,
    app.updated_at
SQL);

        DB::statement(<<<SQL
CREATE OR REPLACE VIEW ai_procurement_request_view AS
SELECT
    pr.id,
    pr.code,
    pr.title,
    pr.date,
    pr.purpose,
    pr.division_id,
    division.name AS division_name,
    pr.unit_id,
    unit.name AS unit_name,
    pr.procurement_app_id,
    pr.status_id,
    status.name AS status_name,
    pr.sub_status_id,
    sub_status.name AS sub_status_name,
    pr.quotation_count,
    pr.reawarded_count,
    pr.rebidded_count,
    pr.created_at,
    pr.updated_at
FROM procurements pr
LEFT JOIN list_dropdowns division ON division.id = pr.division_id
LEFT JOIN list_units unit ON unit.id = pr.unit_id
LEFT JOIN list_statuses status ON status.id = pr.status_id
LEFT JOIN list_statuses sub_status ON sub_status.id = pr.sub_status_id
SQL);

        DB::statement('CREATE OR REPLACE VIEW ai_pr_view AS SELECT * FROM ai_procurement_request_view');

        DB::statement(<<<SQL
CREATE OR REPLACE VIEW ai_po_view AS
SELECT
    po.id,
    po.code,
    po.po_date,
    po.delivery_term,
    po.payment_term,
    po.date_of_delivery,
    po.actual_delivery_date,
    po.procurement_id,
    pr.code AS procurement_code,
    pr.title AS procurement_title,
    pr.division_id,
    division.name AS division_name,
    pr.unit_id,
    unit.name AS unit_name,
    po.status_id,
    status.name AS status_name,
    po.released_at,
    po.conformed_at,
    po.created_at,
    po.updated_at
FROM procurement_noa_pos po
LEFT JOIN procurements pr ON pr.id = po.procurement_id
LEFT JOIN list_dropdowns division ON division.id = pr.division_id
LEFT JOIN list_units unit ON unit.id = pr.unit_id
LEFT JOIN list_statuses status ON status.id = po.status_id
SQL);

        DB::statement(<<<SQL
CREATE OR REPLACE VIEW ai_rfq_view AS
SELECT
    q.id,
    q.code,
    q.submission_not_later_than,
    q.delivery_term,
    q.procurement_id,
    pr.code AS procurement_code,
    pr.title AS procurement_title,
    pr.division_id,
    division.name AS division_name,
    pr.unit_id,
    unit.name AS unit_name,
    q.supplier_id,
    supplier.name AS supplier_name,
    q.status_id,
    status.name AS status_name,
    q.created_at,
    q.updated_at
FROM procurement_quotations q
LEFT JOIN procurements pr ON pr.id = q.procurement_id
LEFT JOIN list_dropdowns division ON division.id = pr.division_id
LEFT JOIN list_units unit ON unit.id = pr.unit_id
LEFT JOIN suppliers supplier ON supplier.id = q.supplier_id
LEFT JOIN list_statuses status ON status.id = q.status_id
SQL);

        DB::statement(<<<SQL
CREATE OR REPLACE VIEW ai_bac_view AS
SELECT
    bac.id,
    bac.code,
    bac.type,
    bac.procurement_id,
    pr.code AS procurement_code,
    pr.title AS procurement_title,
    pr.division_id,
    division.name AS division_name,
    pr.unit_id,
    unit.name AS unit_name,
    bac.status_id,
    status.name AS status_name,
    bac.approved_at,
    bac.created_at,
    bac.updated_at
FROM procurement_bacs bac
LEFT JOIN procurements pr ON pr.id = bac.procurement_id
LEFT JOIN list_dropdowns division ON division.id = pr.division_id
LEFT JOIN list_units unit ON unit.id = pr.unit_id
LEFT JOIN list_statuses status ON status.id = bac.status_id
SQL);

        DB::statement(<<<SQL
CREATE OR REPLACE VIEW ai_inventory_view AS
SELECT
    item.id,
    item.code,
    item.name,
    item.category_id,
    category.name AS category_name,
    COALESCE(SUM(stock.quantity), 0) AS total_quantity,
    COALESCE(SUM(stock.quantity * stock.unit_cost), 0) AS total_value,
    item.created_at,
    item.updated_at
FROM inventory_items item
LEFT JOIN list_dropdowns category ON category.id = item.category_id
LEFT JOIN inventory_stocks stock ON stock.item_id = item.id
GROUP BY item.id, item.code, item.name, item.category_id, category.name, item.created_at, item.updated_at
SQL);

        DB::statement(<<<SQL
CREATE OR REPLACE VIEW ai_stocks_view AS
SELECT
    stock.id,
    stock.item_id,
    item.code AS item_code,
    item.name AS item_name,
    stock.quantity,
    stock.unit_id,
    COALESCE(unit.name_short, unit.name_long) AS unit_name,
    stock.unit_cost,
    stock.description,
    stock.created_at,
    stock.updated_at
FROM inventory_stocks stock
LEFT JOIN inventory_items item ON item.id = stock.item_id
LEFT JOIN unit_types unit ON unit.id = stock.unit_id
SQL);

        DB::statement(<<<SQL
CREATE OR REPLACE VIEW ai_receivings_view AS
SELECT
    receiving.id,
    receiving.item_id,
    item.code AS item_code,
    item.name AS item_name,
    receiving.status_id,
    status.name AS status_name,
    receiving.received_at,
    receiving.remarks,
    receiving.created_at,
    receiving.updated_at
FROM inventory_receivings receiving
LEFT JOIN inventory_items item ON item.id = receiving.item_id
LEFT JOIN list_statuses status ON status.id = receiving.status_id
SQL);

        DB::statement(<<<SQL
CREATE OR REPLACE VIEW ai_withdrawals_view AS
SELECT
    withdrawal.id,
    withdrawal.inventory_id AS item_id,
    item.code AS item_code,
    item.name AS item_name,
    withdrawal.status_id,
    status.name AS status_name,
    withdrawal.released_at,
    withdrawal.remarks,
    withdrawal.created_at,
    withdrawal.updated_at
FROM inventory_withdrawals withdrawal
LEFT JOIN inventory_items item ON item.id = withdrawal.inventory_id
LEFT JOIN list_statuses status ON status.id = withdrawal.status_id
SQL);

        DB::statement(<<<SQL
CREATE OR REPLACE VIEW ai_csf_view AS
SELECT
    CAST(NULL AS UNSIGNED) AS id,
    CAST(NULL AS CHAR(100)) AS code,
    CAST(NULL AS CHAR(100)) AS status_name,
    CAST(NULL AS DATETIME) AS created_at
WHERE 1 = 0
SQL);

        DB::statement(<<<SQL
CREATE OR REPLACE VIEW ai_feedback_view AS
SELECT
    CAST(NULL AS UNSIGNED) AS id,
    CAST(NULL AS CHAR(100)) AS code,
    CAST(NULL AS CHAR(100)) AS status_name,
    CAST(NULL AS DATETIME) AS created_at
WHERE 1 = 0
SQL);
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS ai_feedback_view');
        DB::statement('DROP VIEW IF EXISTS ai_csf_view');
        DB::statement('DROP VIEW IF EXISTS ai_withdrawals_view');
        DB::statement('DROP VIEW IF EXISTS ai_receivings_view');
        DB::statement('DROP VIEW IF EXISTS ai_stocks_view');
        DB::statement('DROP VIEW IF EXISTS ai_inventory_view');
        DB::statement('DROP VIEW IF EXISTS ai_bac_view');
        DB::statement('DROP VIEW IF EXISTS ai_rfq_view');
        DB::statement('DROP VIEW IF EXISTS ai_po_view');
        DB::statement('DROP VIEW IF EXISTS ai_pr_view');
        DB::statement('DROP VIEW IF EXISTS ai_procurement_request_view');
        DB::statement('DROP VIEW IF EXISTS ai_app_view');
        DB::statement('DROP VIEW IF EXISTS ai_ppmp_view');
    }
};
