<?php
declare(strict_types=1);
define('ROOT', __DIR__);

spl_autoload_register(function(string $class): void {
    $file = ROOT.'/src/'.str_replace(['App\\','\\'],['','/'],$class).'.php';
    if(file_exists($file)) require $file;
});

$appCfg = require ROOT.'/config/app.php';
date_default_timezone_set($appCfg['timezone']);
if($appCfg['debug']){ ini_set('display_errors','1'); error_reporting(E_ALL); }
else { ini_set('display_errors','0'); error_reporting(E_ALL); }

// Глобальный обработчик — при 500 отдаём JSON с деталями ошибки
set_exception_handler(function(Throwable $e) {
    error_log('Uncaught: '.$e->getMessage().' in '.$e->getFile().':'.$e->getLine());
    if(!headers_sent()){
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'ok'    => false,
            // Всегда показываем детали — это внутренняя сеть
            'error' => $e->getMessage(),
            'file'  => basename($e->getFile()).':'.$e->getLine(),
            'trace' => substr($e->getTraceAsString(), 0, 500)
        ], JSON_UNESCAPED_UNICODE);
    }
    exit;
});

ini_set('session.cookie_httponly','1');
ini_set('session.use_strict_mode','1');
ini_set('session.cookie_samesite','Lax');
session_name($appCfg['session_name']);
session_start();

if(!empty($_SESSION['user_id'])){
    if(isset($_SESSION['_last_active'])&&(time()-$_SESSION['_last_active'])>$appCfg['session_lifetime']){
        session_unset();session_destroy();header('Location: /login');exit;
    }
    $_SESSION['_last_active']=time();
}

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

use App\Core\Router;
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\FeedbackController;
use App\Controllers\StoryController;
use App\Controllers\ShootPlanController;
use App\Controllers\AdminController;
use App\Controllers\ProfileController;

$r = new Router();
$r->get('/login',  [AuthController::class,'loginForm']);
$r->post('/login', [AuthController::class,'loginPost']);
$r->post('/logout',[AuthController::class,'logout']);

$r->get('/',               [DashboardController::class,'index']);
$r->get('/stories',        [StoryController::class,'index']);
$r->get('/stories/create', [StoryController::class,'createForm']);
$r->post('/stories/create',[StoryController::class,'createPost']);
$r->get('/stories/{id}',         [StoryController::class,'show']);
$r->post('/stories/{id}/status', [StoryController::class,'changeStatus']);
$r->post('/stories/{id}/assign', [StoryController::class,'assign']);
$r->post('/stories/{id}/duration',[StoryController::class,'setDuration']);
$r->post('/stories/{id}/version',[StoryController::class,'addVersion']);
$r->post('/stories/{id}/rate',   [StoryController::class,'rate']);
$r->get( '/stories/{id}/edit',   [StoryController::class,'editForm']);
$r->post('/stories/{id}/edit',   [StoryController::class,'editPost']);
$r->post('/stories/{id}/delete', [StoryController::class,'delete']);

$r->get('/shoot-plan',  [StoryController::class,'shootPlan']);
$r->get('/air-plan',    [StoryController::class,'airPlan']);
$r->get('/anchor-text', [StoryController::class,'anchorText']);
$r->get('/reports',     [StoryController::class,'reports']);
// Персоналии отключены

$r->get('/profile',   [ProfileController::class,'show']);
$r->post('/profile',  [ProfileController::class,'update']);

$r->get('/admin/users',              [AdminController::class,'users']);
$r->get('/admin/users/create',       [AdminController::class,'userCreateForm']);
$r->post('/admin/users/create',      [AdminController::class,'userCreatePost']);
$r->get('/admin/users/{id}/edit',    [AdminController::class,'userEditForm']);
$r->post('/admin/users/{id}/edit',   [AdminController::class,'userEditPost']);
$r->post('/admin/users/{id}/toggle', [AdminController::class,'userToggle']);
$r->post('/admin/users/{id}/delete', [AdminController::class,'userDelete']);
$r->get('/admin/shows',              [AdminController::class,'shows']);
$r->post('/admin/shows/create',      [AdminController::class,'showCreatePost']);
$r->post('/admin/shows/{id}/edit',   [AdminController::class,'showEditPost']);
$r->post('/admin/shows/{id}/delete', [AdminController::class,'showDelete']);

$r->get('/admin/material-types',              [AdminController::class,'materialTypes']);
$r->post('/admin/material-types/save',         [AdminController::class,'materialTypeSave']);
$r->post('/admin/material-types/{id}/delete',  [AdminController::class,'materialTypeDelete']);

$r->post('/feedback/submit',           [FeedbackController::class,'submit']);
$r->get( '/admin/feedback',            [FeedbackController::class,'list']);
$r->post('/admin/feedback/{id}/read',  [FeedbackController::class,'markRead']);
$r->post('/admin/feedback/{id}/delete',[FeedbackController::class,'delete']);

$r->post('/shoot-plan/upload',          [ShootPlanController::class,'upload']);
$r->get( '/shoot-plan/files',           [ShootPlanController::class,'list']);
$r->get( '/shoot-plan/file/{id}',       [ShootPlanController::class,'download']);
$r->post('/shoot-plan/file/{id}/delete',[ShootPlanController::class,'delete']);

$r->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
