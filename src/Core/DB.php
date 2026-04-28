<?php
// ============================================================
// src/Core/DB.php — PDO обёртка, защита от SQL инъекций
// Все запросы используют ТОЛЬКО подготовленные выражения (prepared statements).
// Прямая подстановка пользовательских данных в SQL ЗАПРЕЩЕНА.
// ============================================================

namespace App\Core;

use PDO;
use PDOException;
use PDOStatement;

class DB
{
    private static ?DB $instance = null;
    private PDO $pdo;

    private function __construct()
    {
        $cfg = require ROOT . '/config/database.php';

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $cfg['host'],
            $cfg['port'],
            $cfg['dbname'],
            $cfg['charset']
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false, // ВАЖНО: отключить эмуляцию — настоящие prepared statements
            PDO::ATTR_STRINGIFY_FETCHES  => false,
            PDO::MYSQL_ATTR_FOUND_ROWS   => true,
        ];

        try {
            $this->pdo = new PDO($dsn, $cfg['user'], $cfg['password'], $options);
            $this->pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
        } catch (PDOException $e) {
            // Не показываем детали подключения пользователю
            error_log('DB connection failed: ' . $e->getMessage());
            http_response_code(500);
            die('Ошибка подключения к базе данных.');
        }
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // --------------------------------------------------------
    // Выполнить запрос с параметрами (prepared statement)
    // НИКОГДА не вставляйте $params напрямую в $sql!
    // --------------------------------------------------------
    public function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    // Вернуть один ряд
    public function row(string $sql, array $params = []): ?array
    {
        $row = $this->query($sql, $params)->fetch();
        return $row ?: null;
    }

    // Вернуть все ряды
    public function rows(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }

    // Вставить ряд, вернуть lastInsertId
    public function insert(string $table, array $data): int
    {
        $table  = $this->quoteIdentifier($table);
        $cols   = array_map([$this, 'quoteIdentifier'], array_keys($data));
        $places = array_fill(0, count($data), '?');

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $table,
            implode(', ', $cols),
            implode(', ', $places)
        );

        $this->query($sql, array_values($data));
        return (int) $this->pdo->lastInsertId();
    }

    // Обновить ряды по условию WHERE id = ?
    public function updateById(string $table, int $id, array $data): int
    {
        $table = $this->quoteIdentifier($table);
        $sets  = array_map(
            fn($col) => $this->quoteIdentifier($col) . ' = ?',
            array_keys($data)
        );

        $sql = sprintf(
            'UPDATE %s SET %s WHERE id = ?',
            $table,
            implode(', ', $sets)
        );

        $params = array_values($data);
        $params[] = $id;

        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }

    // Удалить по id
    public function deleteById(string $table, int $id): int
    {
        $table = $this->quoteIdentifier($table);
        $stmt  = $this->query("DELETE FROM {$table} WHERE id = ?", [$id]);
        return $stmt->rowCount();
    }

    // Транзакция
    public function transaction(callable $fn): mixed
    {
        $this->pdo->beginTransaction();
        try {
            $result = $fn($this);
            $this->pdo->commit();
            return $result;
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    // Экранирование идентификаторов (таблицы, колонки) — НЕ для данных!
    private function quoteIdentifier(string $name): string
    {
        // Разрешены только буквы, цифры, подчёркивание
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $name)) {
            throw new \InvalidArgumentException("Недопустимое имя идентификатора: {$name}");
        }
        return "`{$name}`";
    }

    // Запретить клонирование и десериализацию (Singleton)
    private function __clone() {}
    public function __wakeup() { throw new \RuntimeException('Cannot unserialize DB singleton'); }
}
