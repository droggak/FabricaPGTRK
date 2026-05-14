<?php
// ============================================================
// config/app.php — настройки приложения
// ============================================================
return [
    'name'             => 'Фабрика новостей',
    'version'          => '1.0.3',
    'debug'            => false,   // НИКОГДА true на продакшене!
    'timezone'         => 'Europe/Moscow',
    'session_name'     => 'fn_sess',
    'session_lifetime' => 3600 * 8, // 8 часов
    'base_url'         => getenv('APP_URL') ?: '',
];
