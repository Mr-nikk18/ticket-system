-- IT-only sidebar entries for the asset module.

UPDATE sidebar_menus
SET menu_name = 'Assets', icon = 'fas fa-boxes', url = 'assets/create'
WHERE id = 13;

UPDATE sidebar_menus SET sort_order = 1 WHERE parent_id = 13 AND url = 'assets/create';
UPDATE sidebar_menus SET sort_order = 2 WHERE parent_id = 13 AND url = 'assets/manage';
UPDATE sidebar_menus SET sort_order = 3 WHERE parent_id = 13 AND url = 'assets/bulk-upload';
UPDATE sidebar_menus SET sort_order = 4 WHERE parent_id = 13 AND url = 'assets/qr-print-center';

INSERT INTO sidebar_menus (parent_id, menu_name, icon, url, status, sort_order)
SELECT 13, 'Open Asset Entry', 'fas fa-qrcode', 'assets/create', 'Active', 1
WHERE NOT EXISTS (
    SELECT 1
    FROM sidebar_menus
    WHERE parent_id = 13
      AND url = 'assets/create'
);

INSERT INTO sidebar_menus (parent_id, menu_name, icon, url, status, sort_order)
SELECT 13, 'Manage Assets', 'fas fa-edit', 'assets/manage', 'Active', 2
WHERE NOT EXISTS (
    SELECT 1
    FROM sidebar_menus
    WHERE parent_id = 13
      AND url = 'assets/manage'
);

INSERT INTO sidebar_menus (parent_id, menu_name, icon, url, status, sort_order)
SELECT 13, 'Bulk Asset Upload', 'fas fa-file-upload', 'assets/bulk-upload', 'Active', 3
WHERE NOT EXISTS (
    SELECT 1
    FROM sidebar_menus
    WHERE parent_id = 13
      AND url = 'assets/bulk-upload'
);

INSERT INTO sidebar_menus (parent_id, menu_name, icon, url, status, sort_order)
SELECT 13, 'QR Print Center', 'fas fa-print', 'assets/qr-print-center', 'Active', 4
WHERE NOT EXISTS (
    SELECT 1
    FROM sidebar_menus
    WHERE parent_id = 13
      AND url = 'assets/qr-print-center'
);

INSERT INTO role_sidebar_menus (role_id, menu_id, is_it_only)
SELECT 1, 13, 1
WHERE NOT EXISTS (
    SELECT 1
    FROM role_sidebar_menus
    WHERE role_id = 1
      AND menu_id = 13
      AND is_it_only = 1
);

INSERT INTO role_sidebar_menus (role_id, menu_id, is_it_only)
SELECT 2, 13, 1
WHERE NOT EXISTS (
    SELECT 1
    FROM role_sidebar_menus
    WHERE role_id = 2
      AND menu_id = 13
      AND is_it_only = 1
);

INSERT INTO role_sidebar_menus (role_id, menu_id, is_it_only)
SELECT 1, sm.id, 1
FROM sidebar_menus sm
WHERE sm.url IN ('assets/create', 'assets/manage', 'assets/bulk-upload', 'assets/qr-print-center')
  AND NOT EXISTS (
      SELECT 1
      FROM role_sidebar_menus rsm
      WHERE rsm.role_id = 1
        AND rsm.menu_id = sm.id
        AND rsm.is_it_only = 1
  );

INSERT INTO role_sidebar_menus (role_id, menu_id, is_it_only)
SELECT 2, sm.id, 1
FROM sidebar_menus sm
WHERE sm.url IN ('assets/create', 'assets/manage', 'assets/bulk-upload', 'assets/qr-print-center')
  AND NOT EXISTS (
      SELECT 1
      FROM role_sidebar_menus rsm
      WHERE rsm.role_id = 2
        AND rsm.menu_id = sm.id
        AND rsm.is_it_only = 1
  );
