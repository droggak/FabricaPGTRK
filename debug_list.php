<?php
// ВРЕМЕННЫЙ ФАЙЛ ДИАГНОСТИКИ — удалить после проверки
session_start();
define('ROOT', __DIR__);
require ROOT.'/src/Core/DB.php';
$cfg = require ROOT.'/config/database.php';
try {
    $pdo = new PDO(
        "mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['dbname']};charset=utf8mb4",
        $cfg['user'], $cfg['password'],
        [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]
    );
    $pdo->exec("SET NAMES utf8mb4");

    $total = $pdo->query("SELECT COUNT(*) FROM stories")->fetchColumn();
    echo "Сюжетов в БД: <b>$total</b><br>";

    // Тестируем базовый запрос
    try {
        $rows = $pdo->query("
            SELECT s.id, s.title, s.status, cr.name AS creator_name
            FROM stories s
            LEFT JOIN users cr ON cr.id=s.created_by
            LIMIT 5
        ")->fetchAll(PDO::FETCH_ASSOC);
        echo "Базовый запрос OK: " . count($rows) . " строк<br>";
        foreach($rows as $r) echo "- {$r['id']}: {$r['title']} ({$r['status']})<br>";
    } catch(Exception $e) {
        echo "<b style='color:red'>Ошибка запроса: " . $e->getMessage() . "</b><br>";
    }

    // Тестируем полный запрос с material_types
    try {
        $rows2 = $pdo->query("
            SELECT s.id, mt.name AS material_type_name
            FROM stories s
            LEFT JOIN material_types mt ON mt.id=s.material_type_id
            LIMIT 3
        ")->fetchAll(PDO::FETCH_ASSOC);
        echo "material_types JOIN OK<br>";
    } catch(Exception $e) {
        echo "<b style='color:red'>material_types ошибка: " . $e->getMessage() . "</b><br>";
    }

    // Проверяем driver_id
    try {
        $pdo->query("SELECT driver_id FROM stories LIMIT 0");
        echo "driver_id: <b>ЕСТЬ</b><br>";
    } catch(Exception $e) {
        echo "driver_id: <b>НЕТ</b> (это нормально)<br>";
    }

    // Проверяем story_team
    try {
        $pdo->query("SELECT 1 FROM story_team LIMIT 0");
        echo "story_team: <b>ЕСТЬ</b><br>";
    } catch(Exception $e) {
        echo "story_team: <b>НЕТ</b> — нужна миграция migrate_story_team.sql<br>";
    }

} catch(Exception $e) {
    echo "<b style='color:red'>Подключение: " . $e->getMessage() . "</b>";
}
