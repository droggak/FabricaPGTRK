<?php
namespace App\Controllers;
use App\Core\Input;
use App\Core\DB;
use App\Middleware\Auth;

class FeedbackController extends BaseController {

    // Форма отправки (модальное окно — вызывается из любой страницы)
    public function submit(array $p): void {
        Auth::require();
        Input::verifyCsrf();
        $user    = Auth::user();
        $category= Input::enum('category',['bug','suggestion','other']);
        $message = Input::str('message');
        if(empty($message)){
            $this->json(['ok'=>false,'error'=>'Введите сообщение'],422);
        }
        $db=DB::getInstance();
        $db->insert('feedback',[
            'user_id'  => $user['id'],
            'user_name'=> $user['name'],
            'category' => $category ?: 'bug',
            'message'  => mb_substr($message,0,4000),
            'is_read'  => 0,
        ]);
        // Если есть email-отправка — здесь можно добавить
        $this->json(['ok'=>true]);
    }

    // Страница для администратора — список обращений
    public function list(array $p): void {
        Auth::requireAdmin();
        $db   = DB::getInstance();
        $page = max(1,Input::int('page','get',1));
        $perPage=25;
        $unread=(int)$db->row('SELECT COUNT(*) AS n FROM feedback WHERE is_read=0')['n'];
        $total =(int)$db->row('SELECT COUNT(*) AS n FROM feedback')['n'];
        $items = $db->rows('SELECT * FROM feedback ORDER BY is_read ASC, created_at DESC LIMIT '.((int)$perPage).' OFFSET '.(($page-1)*$perPage));
        $pages = (int)ceil($total/$perPage);
        $this->view('admin/feedback',compact('items','page','pages','total','unread'));
    }

    // Пометить как прочитанное
    public function markRead(array $p): void {
        Auth::requireAdmin();
        Input::verifyCsrf();
        $id=(int)$p['id'];
        DB::getInstance()->query('UPDATE feedback SET is_read=1 WHERE id=?',[$id]);
        $this->json(['ok'=>true]);
    }

    // Удалить
    public function delete(array $p): void {
        Auth::requireAdmin();
        Input::verifyCsrf();
        DB::getInstance()->query('DELETE FROM feedback WHERE id=?',[(int)$p['id']]);
        $this->json(['ok'=>true]);
    }
}
