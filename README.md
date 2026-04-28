# Фабрика новостей — Инструкция по развёртыванию

## Требования сервера

| Компонент | Версия |
|-----------|--------|
| PHP       | 8.1+   |
| MySQL     | 8.0+   |
| Apache    | 2.4+ с mod_rewrite |
| Расширения PHP | PDO, PDO_MySQL, mbstring, json, session |

---

## 1. Загрузить файлы на сервер

Структура на сервере:

```
/var/www/fabrika/          ← корень проекта (ВНЕ public_html)
├── config/
│   ├── database.php       ← настройки БД (НИКОМУ не показывать)
│   └── app.php
├── src/
├── views/
├── sql/
└── public/                ← DocumentRoot Apache (ТОЛЬКО эта папка открыта)
    ├── index.php
    ├── .htaccess
    ├── css/app.css
    └── js/app.js
```

> **ВАЖНО:** DocumentRoot Apache должен указывать на папку `public/`,
> а не на корень проекта. Это защищает config/ и src/ от прямого доступа.

---

## 2. Настроить Apache VirtualHost

```apache
<VirtualHost *:80>
    ServerName news.example.com
    DocumentRoot /var/www/fabrika/public

    <Directory /var/www/fabrika/public>
        AllowOverride All
        Require all granted
    </Directory>

    # Запретить доступ к корню проекта
    <Directory /var/www/fabrika>
        Require all denied
    </Directory>
    <Directory /var/www/fabrika/public>
        Require all granted
    </Directory>

    ErrorLog  /var/log/apache2/fabrika_error.log
    CustomLog /var/log/apache2/fabrika_access.log combined
</VirtualHost>
```

Включить mod_rewrite:
```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

---

## 3. Создать базу данных и пользователя MySQL

```sql
CREATE DATABASE fabrika
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

CREATE USER 'fabrika_user'@'localhost' IDENTIFIED BY 'СИЛЬНЫЙ_ПАРОЛЬ_ЗДЕСЬ';

GRANT SELECT, INSERT, UPDATE, DELETE ON fabrika.* TO 'fabrika_user'@'localhost';

FLUSH PRIVILEGES;
```

> Не давайте пользователю права DROP, CREATE, ALTER — это лишнее.

Импортировать схему:
```bash
mysql -u root -p fabrika < /var/www/fabrika/sql/schema.sql
```

---

## 4. Заполнить config/database.php

```php
return [
    'host'     => 'localhost',
    'port'     => 3306,
    'dbname'   => 'fabrika',
    'user'     => 'fabrika_user',
    'password' => 'СИЛЬНЫЙ_ПАРОЛЬ_ЗДЕСЬ',
    'charset'  => 'utf8mb4',
];
```

---

## 5. Права на файлы

```bash
# Владелец — пользователь Apache (www-data)
chown -R www-data:www-data /var/www/fabrika

# Папки: 755, файлы: 644
find /var/www/fabrika -type d -exec chmod 755 {} \;
find /var/www/fabrika -type f -exec chmod 644 {} \;

# config/ — только для чтения Apache
chmod 640 /var/www/fabrika/config/database.php
```

---

## 6. Пароли в базе данных

Схема создаёт тестовые аккаунты с паролем `1234` (bcrypt).  
**Смените пароли** через панель администратора после первого входа!

Для смены пароля вручную через SQL:
```sql
UPDATE users
SET password = '$2y$12$...'   -- результат password_hash() в PHP
WHERE login = 'coordinator';
```

Получить хеш в PHP:
```php
echo password_hash('новый_пароль', PASSWORD_BCRYPT, ['cost' => 12]);
```

---

## 7. Добавить в .gitignore

```
/config/database.php
```

---

## 8. HTTPS (рекомендуется)

```bash
sudo apt install certbot python3-certbot-apache
sudo certbot --apache -d news.example.com
```

После получения SSL раскомментируйте в `public/index.php`:
```php
ini_set('session.cookie_secure', '1');
```

---

## Защита от SQL инъекций

Проект использует **только prepared statements** через PDO:
- `PDO::ATTR_EMULATE_PREPARES => false` — настоящие prepared statements (не эмуляция)
- Весь пользовательский ввод передаётся через параметры `?`, НИКОГДА не вставляется в SQL напрямую
- Идентификаторы таблиц/колонок проверяются через whitelist (`quoteIdentifier()`)
- Все enum-значения проверяются через `Input::enum()` до запроса

## Другие меры безопасности

- **CSRF токены** на всех POST-формах
- **bcrypt** (cost=12) для хранения паролей
- **Session regeneration** при входе (защита от session fixation)
- **Rate limiting** на форме входа (5 попыток / 5 минут)
- **XSS** — весь вывод экранируется через `Input::e()` / `htmlspecialchars()`
- **Security headers** в .htaccess и index.php
- **Контроль доступа** по ролям в каждом контроллере через `Auth::requireRole()`
