<?php
namespace App\Controllers;
use App\Core\DB;
use App\Core\Input;
use App\Middleware\Auth;

class SystemLogController extends BaseController {

    public function index(array $p): void {
        Auth::requireAdmin();
        $db       = DB::getInstance();
        $page     = max(1, Input::int('page','get',1));
        $perPage  = 50;
        $category = Input::str('category','get');
        $search   = Input::str('search','get');
        $dateFrom = Input::date('date_from','get');
        $dateTo   = Input::date('date_to','get');

        $w=[]; $params=[];
        if($category){ $w[]='category=?'; $params[]=$category; }
        if($search){
            $w[]='(user_name LIKE ? OR action LIKE ? OR description LIKE ? OR entity_name LIKE ?)';
            $l='%'.$search.'%'; $params=array_merge($params,[$l,$l,$l,$l]);
        }
        if($dateFrom){ $w[]='DATE(created_at)>=?'; $params[]=$dateFrom; }
        if($dateTo)  { $w[]='DATE(created_at)<=?'; $params[]=$dateTo;   }
        $where = $w ? 'WHERE '.implode(' AND ',$w) : '';

        $total = (int)($db->row("SELECT COUNT(*) AS n FROM system_logs $where", $params)['n']??0);
        $pages = (int)ceil($total/$perPage);
        $logs  = $db->rows(
            "SELECT * FROM system_logs $where ORDER BY created_at DESC LIMIT $perPage OFFSET ".(($page-1)*$perPage),
            $params
        );

        $this->view('admin/system_logs', compact('logs','page','pages','total','category','search','dateFrom','dateTo'));
    }

    // Быстрая очистка старых логов (старше N дней) — только для admin
    public function cleanup(array $p): void {
        Auth::requireAdmin();
        Input::verifyCsrf();
        $days = max(7, Input::int('days'));
        $db   = DB::getInstance();
        $db->query('DELETE FROM system_logs WHERE created_at < NOW() - INTERVAL ? DAY', [$days]);
        $_SESSION['success'] = "Логи старше {$days} дней удалены.";
        $this->redirect('/admin/system-logs');
    }
}
