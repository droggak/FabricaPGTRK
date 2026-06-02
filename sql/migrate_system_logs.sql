USE fabrika;
SET NAMES utf8mb4;

-- Расширенная таблица системных логов
CREATE TABLE IF NOT EXISTS system_logs (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  -- Кто
  user_id     INT UNSIGNED NULL,
  user_name   VARCHAR(120) NOT NULL DEFAULT '',
  user_role   VARCHAR(40)  NOT NULL DEFAULT '',
  -- Что
  category    ENUM('auth','story','user','admin','system') NOT NULL DEFAULT 'system',
  action      VARCHAR(100) NOT NULL,
  description VARCHAR(500) NOT NULL DEFAULT '',
  -- На что направлено
  entity_type VARCHAR(40)  NULL COMMENT 'stories, users, shows...',
  entity_id   INT UNSIGNED NULL,
  entity_name VARCHAR(255) NULL,
  -- Откуда
  ip_address  VARCHAR(45)  NULL,
  user_agent  VARCHAR(300) NULL,
  -- Когда
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

  INDEX idx_sl_user     (user_id),
  INDEX idx_sl_category (category),
  INDEX idx_sl_created  (created_at),
  INDEX idx_sl_entity   (entity_type, entity_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SELECT 'system_logs table created' AS status;
