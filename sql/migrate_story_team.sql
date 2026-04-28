USE fabrika;
SET NAMES utf8mb4;

-- Таблица дополнительных членов команды (один сюжет - много людей на каждую роль)
CREATE TABLE IF NOT EXISTS story_team (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  story_id   INT UNSIGNED NOT NULL,
  user_id    INT UNSIGNED NOT NULL,
  role_slot  ENUM('reporter','operator','editor','montager','driver','voiceover') NOT NULL,
  UNIQUE KEY uq_story_user_role (story_id, user_id, role_slot),
  CONSTRAINT fk_st_story FOREIGN KEY (story_id) REFERENCES stories(id) ON DELETE CASCADE,
  CONSTRAINT fk_st_user  FOREIGN KEY (user_id)  REFERENCES users(id)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SELECT 'story_team table created' AS status;
