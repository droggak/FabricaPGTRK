<?php
namespace App\Core;

/**
 * Системный логгер — записывает важные события в system_logs
 *
 * Категории:
 *   auth   — вход, выход, смена пароля, неудачные попытки
 *   story  — создание, редактирование, удаление, смена статуса
 *   user   — создание/удаление/деактивация пользователей
 *   admin  — изменения справочников (передачи, типы материалов)
 *   system — системные события, ошибки
 */
class Logger {
    private static ?DB $db = null;

    private static function getDb(): DB {
        if(self::$db === null) self::$db = DB::getInstance();
        return self::$db;
    }

    public static function log(
        string  $category,
        string  $action,
        string  $description = '',
        ?string $entityType  = null,
        ?int    $entityId    = null,
        ?string $entityName  = null
    ): void {
        try {
            $uid      = (int)($_SESSION['user_id']   ?? 0);
            $uname    = (string)($_SESSION['user_name'] ?? '');
            $urole    = (string)($_SESSION['user_role'] ?? '');
            $ip       = self::getIp();
            $ua       = mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 300);

            self::getDb()->insert('system_logs', [
                'user_id'     => $uid ?: null,
                'user_name'   => $uname,
                'user_role'   => $urole,
                'category'    => $category,
                'action'      => mb_substr($action, 0, 100),
                'description' => mb_substr($description, 0, 500),
                'entity_type' => $entityType,
                'entity_id'   => $entityId,
                'entity_name' => $entityName ? mb_substr($entityName, 0, 255) : null,
                'ip_address'  => $ip,
                'user_agent'  => $ua,
            ]);
        } catch (\Exception $e) {
            // Логирование не должно ломать работу приложения
            error_log('Logger::log failed: ' . $e->getMessage());
        }
    }

    // ── Удобные методы ──────────────────────────────────

    public static function auth(string $action, string $desc = '', ?int $uid = null): void {
        self::log('auth', $action, $desc, 'users', $uid);
    }

    public static function story(string $action, int $storyId, string $storyTitle, string $desc = ''): void {
        self::log('story', $action, $desc, 'stories', $storyId, $storyTitle);
    }

    public static function user(string $action, int $targetUid, string $targetName, string $desc = ''): void {
        self::log('user', $action, $desc, 'users', $targetUid, $targetName);
    }

    public static function admin(string $action, string $entityType, ?int $entityId, string $entityName, string $desc = ''): void {
        self::log('admin', $action, $desc, $entityType, $entityId, $entityName);
    }

    private static function getIp(): string {
        foreach(['HTTP_CF_CONNECTING_IP','HTTP_X_FORWARDED_FOR','HTTP_X_REAL_IP','REMOTE_ADDR'] as $k) {
            if(!empty($_SERVER[$k])) return explode(',', $_SERVER[$k])[0];
        }
        return '';
    }
}
