<?php
// ВРЕМЕННЫЙ ФАЙЛ ДИАГНОСТИКИ — удалить после проверки
define('ROOT', __DIR__);
require ROOT.'/src/Core/DB.php';
$cfg = require ROOT.'/config/database.php';

try {
    $pdo = new PDO(
        "mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['dbname']};charset={$cfg['charset']}",
        $cfg['user'], $cfg['password'],
        [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES=>false]
    );
    
    echo "<h3>Подключение OK</h3>";
    
    // Проверяем количество сюжетов
    $cnt = $pdo->query("SELECT COUNT(*) FROM stories")->fetchColumn();
    echo "<p>Сюжетов в БД: <b>{$cnt}</b></p>";
    
    // Проверяем статусы
    $rows = $pdo->query("SELECT status, COUNT(*) as cnt FROM stories GROUP BY status")->fetchAll(PDO::FETCH_ASSOC);
    echo "<p>По статусам:</p><ul>";
    foreach($rows as $r) echo "<li>{$r['status']}: {$r['cnt']}</li>";
    echo "</ul>";
    
    // Проверяем поля таблицы
    $cols = $pdo->query("DESCRIBE stories")->fetchAll(PDO::FETCH_COLUMN);
    echo "<p>Поля таблицы stories: " . implode(', ', $cols) . "</p>";
    
    // Проверяем роли
    $roles = $pdo->query("SELECT slug, label FROM roles")->fetchAll(PDO::FETCH_ASSOC);
    echo "<p>Роли: ";
    foreach($roles as $r) echo $r['slug'].'='.$r['label'].' | ';
    echo "</p>";
    
    // Пробуем простой запрос плана съёмок
    $stories = $pdo->query("SELECT id, title, status, shoot_date FROM stories WHERE status != 'отменено' LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
    echo "<p>Сюжеты не отменённые (первые 10):</p><ul>";
    foreach($stories as $s) echo "<li>#{$s['id']}: {$s['title']} | {$s['status']} | съёмка: ".($s['shoot_date']??'нет')."</li>";
    echo "</ul>";

} catch(Exception $e) {
    echo "<b style='color:red'>Ошибка: " . $e->getMessage() . "</b>";
}
