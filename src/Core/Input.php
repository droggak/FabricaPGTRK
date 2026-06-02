<?php
// src/Core/Input.php — безопасное получение и валидация входных данных
// ВСЕ пользовательские данные ДОЛЖНЫ проходить через этот класс

namespace App\Core;

class Input
{
    // --------------------------------------------------------
    // POST / GET значения
    // --------------------------------------------------------
    public static function post(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $default;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_GET[$key] ?? $default;
    }

    // Строка: обрезать пробелы, убрать null bytes
    public static function str(string $key, string $source = 'post', string $default = ''): string
    {
        $val = ($source === 'get') ? ($_GET[$key] ?? $default) : ($_POST[$key] ?? $default);
        return self::cleanStr((string) $val);
    }

    // Целое число
    public static function int(string $key, string $source = 'post', int $default = 0): int
    {
        $val = ($source === 'get') ? ($_GET[$key] ?? $default) : ($_POST[$key] ?? $default);
        return filter_var($val, FILTER_VALIDATE_INT) !== false
            ? (int) $val
            : $default;
    }

    // Дата YYYY-MM-DD или null
    public static function date(string $key, string $source = 'post'): ?string
    {
        $val = self::str($key, $source);
        if ($val === '') return null;
        $d = \DateTime::createFromFormat('Y-m-d', $val);
        return ($d && $d->format('Y-m-d') === $val) ? $val : null;
    }

    // Время HH:MM или null
    public static function time(string $key, string $source = 'post'): ?string
    {
        $val = self::str($key, $source);
        if ($val === '') return null;
        return preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $val) ? $val : null;
    }

    // Значение из белого списка (enum)
    public static function enum(string $key, array $allowed, string $source = 'post', mixed $default = null): mixed
    {
        $val = self::str($key, $source);
        return in_array($val, $allowed, true) ? $val : $default;
    }

    // Целое в диапазоне
    public static function intRange(string $key, int $min, int $max, string $source = 'post', int $default = 1): int
    {
        $val = self::int($key, $source, $default);
        return max($min, min($max, $val));
    }

    // Безопасный вывод в HTML (экранирование)
    public static function escape(mixed $val): string
    {
        return htmlspecialchars((string) $val, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    // Alias для шаблонов
    public static function e(mixed $val): string
    {
        return self::escape($val);
    }

    // --------------------------------------------------------
    // CSRF защита
    // --------------------------------------------------------
    public static function csrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function csrfField(): string
    {
        return '<input type="hidden" name="_csrf" value="' . self::csrfToken() . '">';
    }

    public static function verifyCsrf(): void
    {
        $token = $_POST['_csrf'] ?? '';
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
            http_response_code(403);
            // Определяем тип запроса: AJAX → JSON, обычный → текст
            $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
                   || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')
                   || !empty($_POST['_csrf']); // fetch с FormData — нет заголовка X-Requested-With
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                die(json_encode([
                    'ok'    => false,
                    'error' => 'Сессия устарела. Обновите страницу (F5).'
                ]));
            }
            die('Недействительный CSRF токен. <a href="/">На главную</a>');
        }
    }

    // --------------------------------------------------------
    // Внутренние методы
    // --------------------------------------------------------
    private static function cleanStr(string $val): string
    {
        // Удалить null bytes и управляющие символы кроме tab/newline
        $val = str_replace(chr(0), '', $val);
        return trim($val);
    }
}
