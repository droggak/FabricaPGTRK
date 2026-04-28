<?php
namespace App\Controllers;
use App\Core\Input;

abstract class BaseController {
    protected function view(string $template,array $data=[]): void {
        extract($data);
        $e=fn($v)=>Input::e($v);
        require ROOT.'/views/shared/layout.php';
    }
    protected function json(mixed $data,int $status=200): void {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
        exit;
    }
    protected function redirect(string $url): void { header('Location: '.$url);exit; }
    protected function notFound(): void { http_response_code(404);require ROOT.'/views/shared/404.php';exit; }
    protected function forbidden(): void { http_response_code(403);require ROOT.'/views/shared/403.php';exit; }
}
