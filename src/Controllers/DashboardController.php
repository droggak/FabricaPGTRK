<?php
namespace App\Controllers;
use App\Core\DB;
use App\Middleware\Auth;
use App\Models\StoryModel;

class DashboardController extends BaseController {
    public function index(array $p): void {
        Auth::require();
        $db   = DB::getInstance();
        $cu   = Auth::user();
        $role = $cu['role'];
        $uid  = $cu['id'];
        $today= date('Y-m-d');

        // Безопасный COUNT
        $q = fn(string $sql, array $p=[]) => (int)($db->row($sql,$p)['n'] ?? 0);

        $totalStories  = $q('SELECT COUNT(*) AS n FROM stories');
        $activeStories = $q("SELECT COUNT(*) AS n FROM stories WHERE status NOT IN ('вышло в эфир','отменено')");
        $todayAir      = $q('SELECT COUNT(*) AS n FROM stories WHERE air_date=?', [$today]);
        $onReview      = $q("SELECT COUNT(*) AS n FROM stories WHERE status='на проверке'");

        $myTasks = $this->buildTasks($db, $uid, $role);

        $activityFeed = [];
        try {
            $activityFeed = $db->rows('
                SELECT l.action, l.created_at,
                       u.name AS user_name,
                       s.id AS story_id, s.title AS story_title
                FROM logs l
                LEFT JOIN users u   ON u.id=l.user_id
                LEFT JOIN stories s ON s.id=l.story_id
                ORDER BY l.created_at DESC LIMIT 20
            ');
        } catch(\Exception $e){}

        // Воронка статусов
        $statusCounts = [];
        foreach(StoryModel::STATUSES as $st){
            $statusCounts[$st] = $q('SELECT COUNT(*) AS n FROM stories WHERE status=?', [$st]);
        }

        // Эфир сегодня
        $todayStories = $db->rows('
            SELECT s.id,s.title,s.status,s.importance,s.duration,s.estimated_duration,
                   sh.name AS show_name, sh.color AS show_color, rep.name AS reporter_name
            FROM stories s
            LEFT JOIN shows sh  ON sh.id=s.show_id
            LEFT JOIN users rep ON rep.id=s.reporter_id
            WHERE s.air_date=? ORDER BY s.importance DESC
        ', [$today]);

        // Топ рейтинга за месяц
        $topStories = [];
        try {
            $topStories = $db->rows('
                SELECT s.id,s.title,ROUND(AVG(r.rating),1) AS avg_r,COUNT(r.id) AS cnt
                FROM stories s JOIN ratings r ON r.story_id=s.id
                WHERE s.air_date>=DATE_FORMAT(NOW(),"%Y-%m-01")
                GROUP BY s.id HAVING cnt>=1
                ORDER BY avg_r DESC,cnt DESC LIMIT 5
            ');
        } catch(\Exception $e){}

        // Для админа
        $adminStats = [];
        if($role === 'admin'){
            $adminStats = [
                'users' => $q('SELECT COUNT(*) AS n FROM users WHERE is_active=1'),
                'shows' => $q('SELECT COUNT(*) AS n FROM shows'),
                'types' => $q('SELECT COUNT(*) AS n FROM material_types'),
            ];
        }

        $this->view('dashboard/index', compact(
            'totalStories','activeStories','todayAir','onReview',
            'myTasks','activityFeed','statusCounts','todayStories',
            'topStories','adminStats','today'
        ));
    }

    private function buildTasks(DB $db, int $uid, string $role): array {
        $allRoles = (array)($_SESSION['user_roles'] ?? [$role]);
        $tasks    = [];
        try {
            if(array_intersect($allRoles,['reporter','coordinator'])){
                $rows=$db->rows("SELECT id,title,shoot_date,air_date FROM stories WHERE status='запланировано' AND reporter_id=? ORDER BY COALESCE(shoot_date,'9999-99-99') ASC LIMIT 5",[$uid]);
                foreach($rows as $r) $tasks[]=['icon'=>'🎬','color'=>'var(--amber)','action'=>'Нужно снять','story'=>$r,
                    'urgency'=>(!empty($r['shoot_date'])&&$r['shoot_date']===date('Y-m-d'))?'today':((!empty($r['shoot_date'])&&$r['shoot_date']<date('Y-m-d'))?'overdue':'')];
            }
            if(array_intersect($allRoles,['reporter','coordinator'])){
                $rows=$db->rows("SELECT id,title,shoot_date,air_date FROM stories WHERE status='снято' AND reporter_id=? ORDER BY COALESCE(air_date,'9999-99-99') ASC LIMIT 5",[$uid]);
                foreach($rows as $r) $tasks[]=['icon'=>'✏️','color'=>'var(--accent)','action'=>'Нужно написать текст','story'=>$r,'urgency'=>''];
            }
            if(array_intersect($allRoles,['editor','coordinator','release'])){
                $rows=$db->rows("SELECT id,title,shoot_date,air_date FROM stories WHERE status='на проверке' ORDER BY COALESCE(air_date,'9999-99-99') ASC LIMIT 8");
                foreach($rows as $r) $tasks[]=['icon'=>'🔍','color'=>'var(--purple)','action'=>'Нужно проверить','story'=>$r,'urgency'=>''];
            }
            if(in_array('montager',$allRoles)){
                $rows=$db->rows("SELECT id,title,shoot_date,air_date FROM stories WHERE status='проверено' AND montager_id=? ORDER BY COALESCE(air_date,'9999-99-99') ASC LIMIT 5",[$uid]);
                foreach($rows as $r) $tasks[]=['icon'=>'✂️','color'=>'var(--teal)','action'=>'Нужно смонтировать','story'=>$r,'urgency'=>''];
            }
            if(array_intersect($allRoles,['release','coordinator','editor'])){
                $rows=$db->rows("SELECT id,title,shoot_date,air_date FROM stories WHERE status='смонтировано' ORDER BY COALESCE(air_date,'9999-99-99') ASC LIMIT 5");
                foreach($rows as $r) $tasks[]=['icon'=>'📺','color'=>'var(--green)','action'=>'Нужно отсмотреть','story'=>$r,'urgency'=>''];
            }
        } catch(\Exception $e){}
        return $tasks;
    }
}
