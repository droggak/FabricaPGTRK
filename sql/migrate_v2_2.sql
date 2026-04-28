-- ============================================================
-- Миграция v2.2 — множественные роли, водитель, новые поля
-- Выполнить в phpMyAdmin: выбрать БД fabrika → вкладка SQL
-- ============================================================
USE fabrika;
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS=0;

-- 1. Роль водитель
INSERT IGNORE INTO roles (slug,label) VALUES ('driver','Водитель');

-- 2. Таблица связи пользователь↔роли (множественные роли)
CREATE TABLE IF NOT EXISTS user_roles (
  user_id INT UNSIGNED NOT NULL,
  role_id TINYINT UNSIGNED NOT NULL,
  PRIMARY KEY(user_id, role_id),
  CONSTRAINT fk_ur_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_ur_role FOREIGN KEY(role_id) REFERENCES roles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Перенести существующие роли в новую таблицу
INSERT IGNORE INTO user_roles (user_id, role_id)
SELECT u.id, u.role_id FROM users u WHERE u.role_id IS NOT NULL;

-- 3. Новые поля в users
ALTER TABLE users
  ADD COLUMN IF NOT EXISTS must_change_password TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS phone VARCHAR(30) NULL,
  ADD COLUMN IF NOT EXISTS position_title VARCHAR(100) NULL,
  ADD COLUMN IF NOT EXISTS read_speed DECIMAL(4,2) NOT NULL DEFAULT 0.40;

-- 4. Новые поля в stories
ALTER TABLE stories
  ADD COLUMN IF NOT EXISTS shoot_location VARCHAR(255) NULL,
  ADD COLUMN IF NOT EXISTS info_reason TEXT NULL,
  ADD COLUMN IF NOT EXISTS material_type_id INT UNSIGNED NULL,
  ADD COLUMN IF NOT EXISTS driver_id INT UNSIGNED NULL,
  ADD COLUMN IF NOT EXISTS voiceover_id INT UNSIGNED NULL;

-- FK для driver_id
ALTER TABLE stories
  ADD CONSTRAINT fk_st_driver2 FOREIGN KEY(driver_id) REFERENCES users(id) ON DELETE SET NULL;

-- 5. Типы материалов
CREATE TABLE IF NOT EXISTS material_types (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL, slug VARCHAR(40) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT IGNORE INTO material_types (name,slug) VALUES
  ('Новостной сюжет','news'),('Съёмка в студии','studio'),
  ('Рекламный ролик','commercial'),('Сценарий программы','script'),
  ('Прямой эфир','live'),('Архивный материал','archive'),
  ('Репортаж','report'),('Интервью','interview');

-- 6. Персоны
CREATE TABLE IF NOT EXISTS persons (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL UNIQUE,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS story_persons (
  story_id INT UNSIGNED NOT NULL, person_id INT UNSIGNED NOT NULL,
  PRIMARY KEY(story_id,person_id),
  CONSTRAINT fk_sp2_story FOREIGN KEY(story_id) REFERENCES stories(id) ON DELETE CASCADE,
  CONSTRAINT fk_sp2_person FOREIGN KEY(person_id) REFERENCES persons(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS=1;

SELECT CONCAT('Готово. Ролей: ',(SELECT COUNT(*) FROM roles),', Пользователей: ',(SELECT COUNT(*) FROM users)) AS status;

-- ============================================================
-- Индексы для производительности (25+ сюжетов в день)
-- ============================================================
ALTER TABLE stories
  ADD INDEX IF NOT EXISTS idx_air_date_status  (air_date,   status),
  ADD INDEX IF NOT EXISTS idx_shoot_date_status(shoot_date, status),
  ADD INDEX IF NOT EXISTS idx_importance_desc  (importance, updated_at),
  ADD INDEX IF NOT EXISTS idx_updated_at       (updated_at);

ALTER TABLE story_versions
  ADD INDEX IF NOT EXISTS idx_sv_story_id_desc (story_id, id DESC);

ALTER TABLE logs
  ADD INDEX IF NOT EXISTS idx_log_story_time   (story_id, created_at DESC);

-- Полнотекстовый поиск по сюжетам
ALTER TABLE stories
  ADD FULLTEXT INDEX IF NOT EXISTS ft_stories_search (title, description, info_reason);
