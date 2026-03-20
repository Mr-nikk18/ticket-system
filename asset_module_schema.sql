-- Asset module schema support for local TRS environment.
-- Run once against the `trs` database.

ALTER TABLE assets
    ADD COLUMN qr_image_path VARCHAR(255) NULL AFTER qr_code;
