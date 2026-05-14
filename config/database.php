<?php
// ============================================================
// config/database.php — настройки подключения к MySQL
// ЗАПОЛНИТЬ перед деплоем!
// ============================================================
return [
    'host'     => getenv('DB_HOST')     ?: 'localhost',
    'port'     => (int)(getenv('DB_PORT') ?: 3306),
    'dbname'   => getenv('DB_NAME')     ?: 'fabrika',
    'user'     => getenv('DB_USER')     ?: 'fabrika_user',
    'password' => getenv('DB_PASSWORD') ?: '',
    'charset'  => 'utf8mb4',
];
