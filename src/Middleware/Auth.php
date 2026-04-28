<?php
namespace App\Middleware;

class Auth {
    public static function require(): void {
        if(empty($_SESSION['user_id'])){header('Location: /login');exit;}
    }

    public static function requireRole(string|array $roles): void {
        self::require();
        if(!self::hasRole($roles)){
            http_response_code(403);require ROOT.'/views/shared/403.php';exit;
        }
    }

    public static function requireAdmin(): void     { self::requireRole('admin'); }

    public static function requireCanCreate(): void {
        self::requireRole(['admin','coordinator','reporter','editor']);
    }

    public static function requireCanChangeStatus(): void {
        self::requireRole(['admin','coordinator','reporter','editor','release','montager']);
    }

    public static function requireCanAssign(): void {
        self::requireRole(['admin','coordinator','editor']);
    }

    // Возвращает массив ВСЕХ ролей пользователя (основная + дополнительные)
    public static function roles(): array {
        $fromSession = (array)($_SESSION['user_roles'] ?? []);
        $primary     = $_SESSION['user_role'] ?? '';
        if($primary && !in_array($primary, $fromSession, true)){
            $fromSession[] = $primary;
        }
        return array_values(array_unique(array_filter($fromSession)));
    }

    public static function hasRole(string|array $roles): bool {
        return (bool)array_intersect(self::roles(), (array)$roles);
    }

    public static function user(): array {
        return [
            'id'   => (int)($_SESSION['user_id']   ?? 0),
            'name' => (string)($_SESSION['user_name'] ?? ''),
            'role' => (string)($_SESSION['user_role'] ?? ''),
        ];
    }
}
