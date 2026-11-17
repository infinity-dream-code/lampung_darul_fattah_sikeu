-- Jalankan di database lampung_yys_darul_fattah (Navicat / MySQL)
-- Kolom NO_WA dibutuhkan halaman Data Siswa & Data Tagihan.

SET @db := DATABASE();
SET @exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db
      AND TABLE_NAME = 'scctcust'
      AND COLUMN_NAME = 'NO_WA'
);

SET @sql := IF(
    @exists = 0,
    'ALTER TABLE `scctcust` ADD COLUMN `NO_WA` VARCHAR(20) NULL AFTER `GENUS`',
    'SELECT ''NO_WA already exists'' AS info'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
