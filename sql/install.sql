-- ============================================================
-- ПОЛНАЯ УСТАНОВКА — запустить один раз на новом сервере
-- ============================================================
CREATE DATABASE IF NOT EXISTS fabrika
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE fabrika;

-- Основная схема
SOURCE schema.sql;

-- Миграции
SOURCE migrate_v2_2.sql;
SOURCE migrate_feedback.sql;
SOURCE migrate_story_team.sql;

SELECT 'Installation complete!' AS status;
