USE fabrika;
SET NAMES utf8mb4;
CREATE TABLE IF NOT EXISTS feedback (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id    INT UNSIGNED NULL,
  user_name  VARCHAR(120) NOT NULL DEFAULT '',
  category   ENUM('bug','suggestion','other') NOT NULL DEFAULT 'bug',
  message    TEXT NOT NULL,
  is_read    TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_fb_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SELECT 'feedback table created' AS status;
