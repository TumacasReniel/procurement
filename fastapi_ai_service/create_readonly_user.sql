-- Run as a MySQL admin user, then replace the password before use.
CREATE USER IF NOT EXISTS 'intellibot_readonly'@'localhost' IDENTIFIED BY 'change-this-password';

GRANT SELECT ON oneapp_db.ai_ppmp_view TO 'intellibot_readonly'@'localhost';
GRANT SELECT ON oneapp_db.ai_app_view TO 'intellibot_readonly'@'localhost';
GRANT SELECT ON oneapp_db.ai_procurement_request_view TO 'intellibot_readonly'@'localhost';
GRANT SELECT ON oneapp_db.ai_pr_view TO 'intellibot_readonly'@'localhost';
GRANT SELECT ON oneapp_db.ai_po_view TO 'intellibot_readonly'@'localhost';
GRANT SELECT ON oneapp_db.ai_rfq_view TO 'intellibot_readonly'@'localhost';
GRANT SELECT ON oneapp_db.ai_bac_view TO 'intellibot_readonly'@'localhost';
GRANT SELECT ON oneapp_db.ai_inventory_view TO 'intellibot_readonly'@'localhost';
GRANT SELECT ON oneapp_db.ai_stocks_view TO 'intellibot_readonly'@'localhost';
GRANT SELECT ON oneapp_db.ai_receivings_view TO 'intellibot_readonly'@'localhost';
GRANT SELECT ON oneapp_db.ai_withdrawals_view TO 'intellibot_readonly'@'localhost';
GRANT SELECT ON oneapp_db.ai_csf_view TO 'intellibot_readonly'@'localhost';
GRANT SELECT ON oneapp_db.ai_feedback_view TO 'intellibot_readonly'@'localhost';

FLUSH PRIVILEGES;
