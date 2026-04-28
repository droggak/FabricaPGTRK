-- ============================================================
-- Миграция v2.1 — выполнить в phpMyAdmin если уже есть v2.0
-- Все команды безопасны — не сломают существующие данные
-- ============================================================
USE fabrika;

SET NAMES utf8mb4;

-- Добавить роль Водитель (если нет)
INSERT IGNORE INTO roles (slug, label) VALUES ('driver', 'Водитель');

-- Добавить поля в stories если их нет
ALTER TABLE stories
  ADD COLUMN IF NOT EXISTS shoot_location VARCHAR(255) NULL COMMENT 'место съёмки',
  ADD COLUMN IF NOT EXISTS info_reason TEXT NULL COMMENT 'информационный повод',
  ADD COLUMN IF NOT EXISTS material_type_id INT UNSIGNED NULL,
  ADD COLUMN IF NOT EXISTS driver_id INT UNSIGNED NULL COMMENT 'водитель',
  ADD COLUMN IF NOT EXISTS voiceover_id INT UNSIGNED NULL COMMENT 'озвучка';

-- Добавить поля в users если их нет
ALTER TABLE users
  ADD COLUMN IF NOT EXISTS must_change_password TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS phone VARCHAR(30) NULL,
  ADD COLUMN IF NOT EXISTS position_title VARCHAR(100) NULL,
  ADD COLUMN IF NOT EXISTS read_speed DECIMAL(4,2) NOT NULL DEFAULT 0.40;

-- Таблица типов материалов
CREATE TABLE IF NOT EXISTS material_types (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL, slug VARCHAR(40) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO material_types (name,slug) VALUES
  ('Новостной сюжет','news'),('Съёмка в студии','studio'),
  ('Рекламный ролик','commercial'),('Сценарий программы','script'),
  ('Прямой эфир','live'),('Архивный материал','archive'),
  ('Репортаж','report'),('Интервью','interview');

-- Таблица персон
CREATE TABLE IF NOT EXISTS persons (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL UNIQUE,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Связь сюжетов и персон
CREATE TABLE IF NOT EXISTS story_persons (
  story_id INT UNSIGNED NOT NULL, person_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (story_id, person_id),
  CONSTRAINT fk_sp_story  FOREIGN KEY (story_id)  REFERENCES stories(id) ON DELETE CASCADE,
  CONSTRAINT fk_sp_person FOREIGN KEY (person_id) REFERENCES persons(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- FK для driver_id (игнорировать если уже есть)
ALTER TABLE stories
  ADD CONSTRAINT fk_st_driver FOREIGN KEY (driver_id) REFERENCES users(id) ON DELETE SET NULL;

-- Проверка
SELECT CONCAT('Миграция выполнена. Ролей: ', COUNT(*)) AS result FROM roles;
