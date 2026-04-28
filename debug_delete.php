<?php
// ВРЕМЕННЫЙ ФАЙЛ — удалить после проверки
session_start();
echo json_encode([
    'session_active' => !empty($_SESSION['user_id']),
    'user_id'        => $_SESSION['user_id'] ?? null,
    'csrf_token_set' => !empty($_SESSION['csrf_token']),
    'csrf_len'       => strlen($_SESSION['csrf_token'] ?? ''),
    'method'         => $_SERVER['REQUEST_METHOD'],
    'post_csrf_len'  => strlen($_POST['_csrf'] ?? ''),
    'post_csrf_match'=> hash_equals($_SESSION['csrf_token']??'', $_POST['_csrf']??''),
]);
