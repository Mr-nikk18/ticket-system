-- Asset department support for department-linked QR URLs and series printing.
-- Run once against the `trs` database.

ALTER TABLE assets
    ADD COLUMN department_id INT(11) UNSIGNED NULL AFTER assigned_user_id;

UPDATE assets a
JOIN users u ON u.user_id = a.assigned_user_id
SET a.department_id = u.department_id
WHERE a.department_id IS NULL
  AND u.department_id IS NOT NULL;
