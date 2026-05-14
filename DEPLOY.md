# Деплой Фабрика новостей v1.0.3

## Требования сервера
- PHP 8.1+ с расширениями: pdo_mysql, mbstring, json, session
- MySQL 8.0+ / MariaDB 10.6+
- Apache 2.4+ с mod_rewrite и AllowOverride All
- HTTPS (рекомендуется)

## Шаги установки

### 1. Загрузка файлов
Скопируйте все файлы в корень сайта (например `/var/www/html/fabrika`).

### 2. База данных
Создайте БД и пользователя:
```sql
CREATE DATABASE fabrika CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'fabrika_user'@'localhost' IDENTIFIED BY 'ВАШ_ПАРОЛЬ';
GRANT ALL PRIVILEGES ON fabrika.* TO 'fabrika_user'@'localhost';
FLUSH PRIVILEGES;
```
Затем импортируйте схему через phpMyAdmin или командную строку:
```bash
mysql -u fabrika_user -p fabrika < sql/schema.sql
mysql -u fabrika_user -p fabrika < sql/migrate_v2_2.sql
mysql -u fabrika_user -p fabrika < sql/migrate_feedback.sql
mysql -u fabrika_user -p fabrika < sql/migrate_story_team.sql
```

### 3. Конфигурация
Отредактируйте `config/database.php`:
```php
'host'     => 'localhost',
'dbname'   => 'fabrika',
'user'     => 'fabrika_user',
'password' => 'ВАШ_ПАРОЛЬ',
```

### 4. Apache VirtualHost
```apache
<VirtualHost *:80>
    ServerName your-domain.ru
    DocumentRoot /var/www/html/fabrika

    <Directory /var/www/html/fabrika>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/fabrika_error.log
    CustomLog ${APACHE_LOG_DIR}/fabrika_access.log combined
</VirtualHost>
```
Включить: `a2enmod rewrite && a2ensite fabrika && systemctl reload apache2`

### 5. Права на файлы
```bash
chown -R www-data:www-data /var/www/html/fabrika
find /var/www/html/fabrika -type d -exec chmod 755 {} \;
find /var/www/html/fabrika -type f -exec chmod 644 {} \;
```

### 6. Первый вход
| Логин | Пароль | Роль |
|-------|--------|------|
| admin | admin  | Администратор |

**Обязательно смените пароль после первого входа!**

### 7. После деплоя — проверить
- [ ] Сайт открывается по домену
- [ ] Вход под admin работает
- [ ] Создание сюжета работает
- [ ] Дикторский текст открывается
- [ ] Отладочные файлы `debug_*.php` удалены

## Обновление
1. Сделайте резервную копию БД
2. Скопируйте новые файлы (не трогайте `config/database.php`)
3. Выполните новые миграции из папки `sql/`
