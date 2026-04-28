SET NAMES utf8mb4;
SET time_zone = '+03:00';
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS fabrika CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE fabrika;

CREATE TABLE IF NOT EXISTS roles (
  id TINYINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(30) NOT NULL UNIQUE, label VARCHAR(60) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO roles (slug,label) VALUES
  ('admin','Администратор'),('coordinator','Координатор'),('reporter','Корреспондент'),
  ('editor','Редактор'),('operator','Оператор'),('montager','Монтажёр'),('release','Выпускающий редактор');

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  login VARCHAR(60) NOT NULL UNIQUE, password VARCHAR(255) NOT NULL,
  name VARCHAR(120) NOT NULL, role_id TINYINT UNSIGNED NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  must_change_password TINYINT(1) NOT NULL DEFAULT 0,
  phone VARCHAR(30), position_title VARCHAR(100),
  read_speed DECIMAL(4,2) NOT NULL DEFAULT 0.40,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS shows (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL, description TEXT,
  color VARCHAR(20) NOT NULL DEFAULT 'accent', air_time VARCHAR(10),
  created_by INT UNSIGNED, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_shows_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS material_types (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL, slug VARCHAR(40) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO material_types (name,slug) VALUES
  ('Новостной сюжет','news'),('Съёмка в студии','studio'),('Рекламный ролик','commercial'),
  ('Сценарий программы','script'),('Прямой эфир','live'),('Архивный материал','archive'),
  ('Репортаж','report'),('Интервью','interview');

CREATE TABLE IF NOT EXISTS persons (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL UNIQUE,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS stories (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL, description TEXT,
  show_id INT UNSIGNED, material_type_id INT UNSIGNED,
  status ENUM('запланировано','снято','на проверке','проверено','смонтировано','отсмотрено','готово','вышло в эфир','отменено') NOT NULL DEFAULT 'запланировано',
  shoot_date DATE, shoot_location VARCHAR(255), info_reason TEXT, air_date DATE,
  importance TINYINT(1) NOT NULL DEFAULT 3,
  duration VARCHAR(10), estimated_duration VARCHAR(10),
  reporter_id INT UNSIGNED, operator_id INT UNSIGNED, editor_id INT UNSIGNED,
  montager_id INT UNSIGNED, driver_id INT UNSIGNED, voiceover_id INT UNSIGNED,
  created_by INT UNSIGNED,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_st_show      FOREIGN KEY (show_id)         REFERENCES shows(id) ON DELETE SET NULL,
  CONSTRAINT fk_st_mattype   FOREIGN KEY (material_type_id) REFERENCES material_types(id) ON DELETE SET NULL,
  CONSTRAINT fk_st_reporter  FOREIGN KEY (reporter_id)  REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_st_operator  FOREIGN KEY (operator_id)  REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_st_editor    FOREIGN KEY (editor_id)    REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_st_montager  FOREIGN KEY (montager_id)  REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_st_driver    FOREIGN KEY (driver_id)    REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_st_voiceover FOREIGN KEY (voiceover_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_st_created   FOREIGN KEY (created_by)   REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_st_status(status), INDEX idx_st_show(show_id),
  INDEX idx_st_shoot(shoot_date), INDEX idx_st_air(air_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS story_persons (
  story_id INT UNSIGNED NOT NULL, person_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (story_id, person_id),
  CONSTRAINT fk_sp_story  FOREIGN KEY (story_id)  REFERENCES stories(id) ON DELETE CASCADE,
  CONSTRAINT fk_sp_person FOREIGN KEY (person_id) REFERENCES persons(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS story_versions (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  story_id INT UNSIGNED NOT NULL, user_id INT UNSIGNED,
  content LONGTEXT NOT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_sv_story FOREIGN KEY (story_id) REFERENCES stories(id) ON DELETE CASCADE,
  CONSTRAINT fk_sv_user  FOREIGN KEY (user_id)  REFERENCES users(id)   ON DELETE SET NULL,
  INDEX idx_sv_story(story_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS ratings (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  story_id INT UNSIGNED NOT NULL, user_id INT UNSIGNED NOT NULL,
  rating TINYINT(1) NOT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_rating(story_id,user_id),
  CONSTRAINT fk_rat_story FOREIGN KEY (story_id) REFERENCES stories(id) ON DELETE CASCADE,
  CONSTRAINT fk_rat_user  FOREIGN KEY (user_id)  REFERENCES users(id)   ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS logs (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  story_id INT UNSIGNED, user_id INT UNSIGNED,
  action VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_log_story FOREIGN KEY (story_id) REFERENCES stories(id) ON DELETE SET NULL,
  CONSTRAINT fk_log_user  FOREIGN KEY (user_id)  REFERENCES users(id)   ON DELETE SET NULL,
  INDEX idx_log_story(story_id), INDEX idx_log_time(created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;

-- Тестовые пользователи (все пароль '1234', admin='admin')
INSERT IGNORE INTO users (login,password,name,role_id) VALUES
  ('admin',      '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Администратор',   1),
  ('coordinator','$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Анна Ковалёва',   2),
  ('reporter1',  '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Иван Петров',     3),
  ('reporter2',  '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Мария Сидорова',  3),
  ('editor',     '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Сергей Волков',   4),
  ('operator',   '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Дмитрий Кузнецов',5),
  ('montager',   '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Елена Новикова',  6),
  ('release',    '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Павел Орлов',     7);

INSERT IGNORE INTO shows (name,description,color,air_time,created_by) VALUES
  ('Новости 20:00','Главный вечерний выпуск','accent','20:00',1),
  ('Утро региона','Утренние новости','amber','08:00',1),
  ('Час новостей','Дневной выпуск','teal','13:00',1),
  ('Специальный репортаж','Аналитика и расследования','purple','21:30',1);

INSERT IGNORE INTO persons (name) VALUES
  ('Иванов Александр'),('Петрова Елена'),('Сидоров Николай'),('Козлов Дмитрий');

INSERT IGNORE INTO stories
  (title,description,show_id,material_type_id,status,shoot_date,shoot_location,info_reason,air_date,importance,duration,estimated_duration,reporter_id,operator_id,editor_id,montager_id,created_by)
VALUES
  ('Открытие нового парка в центре города',
   'Сегодня в центре города торжественно открыли новый парк площадью 5 гектаров.',
   1,1,'готово','2025-06-10','Центральный район, ул. Ленина','Торжественное открытие парка',
   '2025-06-12',4,'00:02:30','00:03:00',3,6,5,7,2),
  ('Дорожные работы перекроют движение на трёх улицах',
   'С понедельника начнётся ремонт покрытия на улицах Ленина, Мира и Садовой.',
   1,1,'на проверке','2025-06-11','ул. Ленина','Плановый ремонт дорог',
   '2025-06-13',3,NULL,'00:02:00',4,6,5,7,2),
  ('Чемпионат по шахматам среди школьников',
   'Региональный чемпионат пройдёт в Доме культуры.',
   2,1,'запланировано','2025-06-15','Дом культуры, пр. Победы','Ежегодный чемпионат',
   '2025-06-16',2,NULL,'00:01:30',3,6,NULL,NULL,3),
  ('Встреча ветеранов труда в городской администрации',
   'Городские власти организовали торжественный приём ветеранов.',
   1,1,'вышло в эфир','2025-06-08','Городская администрация','День города',
   '2025-06-09',5,'00:03:45','00:03:00',4,6,5,7,2),
  ('Новая автобусная линия свяжет пригород с центром',
   'С 1 июля начнёт работать маршрут №48.',
   3,1,'снято','2025-06-11','Автовокзал','Открытие нового маршрута',
   '2025-06-14',3,NULL,'00:02:30',3,6,NULL,NULL,3),
  ('Расследование: кто скупает земли в пригороде',
   'За год три компании приобрели свыше 300 га пригородных земель.',
   4,7,'проверено','2025-06-09','Пригородная зона','Расследование редакции',
   '2025-06-20',5,NULL,'00:08:00',4,6,5,7,2);

-- Добавить роль водителя (выполнить если уже есть БД)
INSERT IGNORE INTO roles (slug, label) VALUES ('driver', 'Водитель');

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
