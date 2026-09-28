-- =====================================================================
-- Migrasi FINAL - Fase 1 (database `todolist`, MariaDB 10.4+)
-- Setelah file ini dijalankan, tidak ada perubahan database lagi di Fase 1.
-- Aman dijalankan ulang (memakai IF EXISTS / IF NOT EXISTS).
-- WAJIB backup database dulu.
-- =====================================================================

-- 1. status tidak boleh NULL (rapikan data lama dulu)
UPDATE `tasks` SET `status` = 'belum selesai' WHERE `status` IS NULL;
ALTER TABLE `tasks`
  MODIFY `status` enum('belum selesai','sedang dikerjakan','selesai') NOT NULL DEFAULT 'belum selesai';

UPDATE `agenda_items` SET `status` = 0 WHERE `status` IS NULL;
ALTER TABLE `agenda_items`
  MODIFY `status` tinyint(1) NOT NULL DEFAULT 0;

-- 2. Hapus kolom lama yang tidak terpakai (duplikat created_at)
ALTER TABLE `tasks` DROP COLUMN IF EXISTS `tanggal_dibuat`;

-- 3. Index untuk kolom yang sering difilter/diurutkan
ALTER TABLE `tasks`
  ADD INDEX IF NOT EXISTS `idx_tasks_deadline` (`deadline`),
  ADD INDEX IF NOT EXISTS `idx_tasks_status` (`status`);
ALTER TABLE `agenda`
  ADD INDEX IF NOT EXISTS `idx_agenda_tanggal` (`tanggal`);

-- 4. Jaga integritas data di level database
--    (kode sudah memvalidasi hal yang sama, ini lapisan pengaman kedua)
ALTER TABLE `tasks` DROP CONSTRAINT IF EXISTS `chk_tasks_progress`;
ALTER TABLE `tasks`
  ADD CONSTRAINT `chk_tasks_progress` CHECK (`progress` BETWEEN 0 AND 100);

ALTER TABLE `tasks` DROP CONSTRAINT IF EXISTS `chk_tasks_selesai_100`;
ALTER TABLE `tasks`
  ADD CONSTRAINT `chk_tasks_selesai_100` CHECK (`status` <> 'selesai' OR `progress` = 100);