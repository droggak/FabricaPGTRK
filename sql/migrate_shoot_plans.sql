USE fabrika;
SET NAMES utf8mb4;
CREATE TABLE IF NOT EXISTS shoot_plan_files (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  filename     VARCHAR(255) NOT NULL,
  stored_name  VARCHAR(255) NOT NULL,
  plan_date    DATE         NULL,
  show_id      INT UNSIGNED NULL,
  uploaded_by  INT UNSIGNED NULL,
  uploader_name VARCHAR(120) NOT NULL DEFAULT '',
  file_size    INT UNSIGNED NOT NULL DEFAULT 0,
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_spf_show FOREIGN KEY (show_id)     REFERENCES shows(id)  ON DELETE SET NULL,
  CONSTRAINT fk_spf_user FOREIGN KEY (uploaded_by) REFERENCES users(id)  ON DELETE SET NULL,
  INDEX idx_spf_date(plan_date),INDEX idx_spf_created(created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SELECT 'shoot_plan_files OK' AS status;
