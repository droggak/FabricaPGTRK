<?php
namespace App\Controllers;
use App\Core\Input;
use App\Core\DB;
use App\Core\Paginator;
use App\Middleware\Auth;
use App\Models\ShowModel;
use App\Models\StoryModel;
use App\Models\UserModel;

class StoryController extends BaseController {
    private StoryModel $m;
    private const PER_PAGE = 25;
    public function __construct(){ $this->m=new StoryModel(); }

    public function index(array $p): void {
        Auth::require();
        $page    = max(1, Input::int('page','get',1));
        $period  = Input::str('period','get') ?: 'today';
        $dateFrom= Input::date('date_from','get');
        $dateTo  = Input::date('date_to','get');
        $filters = [
            'status'  => Input::enum('status',StoryModel::STATUSES,'get',''),
            'show_id' => Input::int('show_id','get'),
            'search'  => Input::str('search','get'),
        ];
        [$dateFrom,$dateTo] = $this->resolvePeriod($period,$dateFrom,$dateTo);
        if($dateFrom) $filters['date_from']=$dateFrom;
        if($dateTo)   $filters['date_to']  =$dateTo;
        $f     = array_filter($filters,fn($v)=>$v!==''&&$v!==0&&$v!==null);
        $total = $this->m->countList($f);
        $stories=$this->m->getList($f,self::PER_PAGE,($page-1)*self::PER_PAGE);
        $pages = (int)ceil($total/self::PER_PAGE);
        $shows =(new ShowModel())->getAll();
        $types =$this->m->getMaterialTypes();
        $this->view('stories/index',compact('stories','shows','filters','types','page','pages','total','period','dateFrom','dateTo'));
    }

    private function resolvePeriod(string $period,?string $from,?string $to): array {
        $today=date('Y-m-d');
        return match($period){
            'today'  => [$today,$today],
            '3days'  => [date('Y-m-d',strtotime('-2 days')),$today],
            'week'   => [date('Y-m-d',strtotime('-6 days')),$today],
            'month'  => [date('Y-m-d',strtotime('-29 days')),$today],
            'all'    => [null,null],
            'custom' => [$from?:$today,$to?:$today],
            default  => [$today,$today],
        };
    }

    public function show(array $p): void {
        Auth::require();
        $story=$this->m->getById((int)$p['id']);if(!$story)$this->notFound();
        $users=(new UserModel())->getAll();$shows=(new ShowModel())->getAll();
        $types=$this->m->getMaterialTypes();$user=Auth::user();
        $teamMembers=$this->m->getTeamMembers((int)$p['id']);
        $this->view('stories/show',compact('story','users','shows','types','user','teamMembers'));
    }

    public function createForm(array $p): void {
        Auth::requireCanCreate();
        $users=(new UserModel())->getAll();$shows=(new ShowModel())->getAll();$types=$this->m->getMaterialTypes();
        $this->view('stories/create',compact('users','shows','types'));
    }

    public function createPost(array $p): void {
        Auth::requireCanCreate();Input::verifyCsrf();
        $personsRaw=Input::str('persons');
        $persons=!empty($personsRaw)?array_filter(array_map('trim',explode(',',trim($personsRaw)))):[];
        // Первый выбранный — основной (для обратной совместимости)
        $firstId=fn(string $f)=>!empty($_POST[$f.'_ids'])&&is_array($_POST[$f.'_ids'])?(int)reset($_POST[$f.'_ids']):null;
        $data=['title'=>Input::str('title'),'description'=>Input::str('description'),
               'show_id'=>Input::int('show_id')?:null,'material_type_id'=>Input::int('material_type_id')?:null,
               'status'=>'запланировано','shoot_date'=>Input::date('shoot_date'),
               'shoot_location'=>Input::str('shoot_location'),'info_reason'=>Input::str('info_reason'),
               'air_date'=>Input::date('air_date'),'importance'=>Input::intRange('importance',1,5),
               'estimated_duration'=>Input::time('estimated_duration')?:null,
               'reporter_id'=>$firstId('reporter'),'operator_id'=>$firstId('operator'),
               'editor_id'=>$firstId('editor'),'montager_id'=>$firstId('montager'),
               'driver_id'=>$firstId('driver'),'voiceover_id'=>$firstId('voiceover'),
               'persons'=>$persons];
        if(empty($data['title'])){$_SESSION['error']='Введите заголовок.';$this->redirect('/stories/create');}
        $id=$this->m->create($data,Auth::user()['id']);
        // Сохраняем всех выбранных членов команды
        $this->m->syncTeam($id,$_POST);
        $this->redirect('/stories/'.$id);
    }

    private function canEdit(array $story): bool {
        $user = Auth::user();
        // Admin, coordinator, editor — могут редактировать любой сюжет
        if(Auth::hasRole(['admin','coordinator','editor'])) return true;
        // Корреспондент — только свой (назначен или создал)
        if(Auth::hasRole('reporter')){
            return $story['created_by'] == $user['id']
                || $story['reporter_id'] == $user['id'];
        }
        return false;
    }

    public function editForm(array $p): void {
        Auth::require();
        $story=$this->m->getById((int)$p['id']);if(!$story)$this->notFound();
        if(!$this->canEdit($story))$this->forbidden();
        $users=(new UserModel())->getAll();$shows=(new ShowModel())->getAll();$types=$this->m->getMaterialTypes();$user=Auth::user();
        $teamMembers=$this->m->getTeamMembers((int)$p['id']);
        $this->view('stories/edit',compact('story','users','shows','types','user','teamMembers'));
    }

    public function editPost(array $p): void {
        Auth::require();Input::verifyCsrf();
        $story=$this->m->getById((int)$p['id']);if(!$story)$this->notFound();
        if(!$this->canEdit($story))$this->forbidden();
        $id=(int)$p['id'];
        $personsRaw=Input::str('persons');
        $persons=!empty($personsRaw)?array_filter(array_map('trim',explode(',',trim($personsRaw)))):[];
        $data=['title'=>Input::str('title'),'description'=>Input::str('description'),
               'show_id'=>Input::int('show_id')?:null,'material_type_id'=>Input::int('material_type_id')?:null,
               'shoot_date'=>Input::date('shoot_date'),'shoot_location'=>Input::str('shoot_location'),
               'info_reason'=>Input::str('info_reason'),'air_date'=>Input::date('air_date'),
               'importance'=>Input::intRange('importance',1,5),
               'estimated_duration'=>Input::time('estimated_duration')?:null,
               'reporter_id'=>Input::int('reporter_id')?:null,'operator_id'=>Input::int('operator_id')?:null,
               'editor_id'=>Input::int('editor_id')?:null,'montager_id'=>Input::int('montager_id')?:null,
               'driver_id'=>Input::int('driver_id')?:null,'voiceover_id'=>Input::int('voiceover_id')?:null,
               'persons'=>$persons];
        if(empty($data['title'])){$_SESSION['error']='Введите заголовок.';$this->redirect('/stories/'.$id.'/edit');}
        $this->m->update($id,$data,Auth::user()['id']);
        // Синхронизируем команду
        $this->m->syncTeam($id,$_POST);
        $_SESSION['success']='Сюжет обновлён.';
        $this->redirect('/stories/'.$id);
    }

    public function delete(array $p): void {
        Auth::require();Input::verifyCsrf();
        $id  =(int)$p['id'];
        $user=Auth::user();
        $row =DB::getInstance()->row('SELECT created_by FROM stories WHERE id=?',[$id]);
        if(!$row){$_SESSION['error']='Сюжет не найден.';$this->redirect('/stories');}
        $canDel=($row['created_by']==$user['id'])||Auth::hasRole(['admin','coordinator','editor']);
        if(!$canDel){$_SESSION['error']='Нет прав на удаление.';$this->redirect('/stories');}
        $this->m->delete($id);
        $_SESSION['success']='Сюжет удалён.';
        $this->redirect('/stories');
    }

    public function changeStatus(array $p): void {
        Auth::requireCanChangeStatus();Input::verifyCsrf();
        $id=(int)$p['id'];$status=Input::enum('status',StoryModel::STATUSES);
        if(!$status)$this->json(['ok'=>false,'error'=>'Недопустимый статус'],422);
        $ok=$this->m->changeStatus($id,$status,Auth::user()['id']);
        $this->json(['ok'=>$ok]);
    }

    public function assign(array $p): void {
        Auth::requireCanAssign();Input::verifyCsrf();
        $id=(int)$p['id'];
        $role=Input::enum('role',['reporter','operator','editor','montager','driver','voiceover']);
        $uid=Input::int('user_id')?:null;
        if(!$role)$this->json(['ok'=>false,'error'=>'Неверная роль'],422);
        $map=['reporter'=>'reporter_id','operator'=>'operator_id','editor'=>'editor_id',
              'montager'=>'montager_id','driver'=>'driver_id','voiceover'=>'voiceover_id'];
        $this->m->update($id,[$map[$role]=>$uid],Auth::user()['id']);
        $this->json(['ok'=>true]);
    }

    public function setDuration(array $p): void {
        Auth::requireCanChangeStatus();Input::verifyCsrf();
        $this->m->update((int)$p['id'],['duration'=>Input::time('duration')?:null],Auth::user()['id']);
        $this->json(['ok'=>true]);
    }

    public function addVersion(array $p): void {
        Auth::requireRole(['coordinator','reporter','editor']);Input::verifyCsrf();
        $id=(int)$p['id'];$content=Input::str('content');
        if(empty($content))$this->json(['ok'=>false,'error'=>'Пустой текст'],422);
        $this->m->addVersion($id,$content,Auth::user()['id']);
        $this->json(['ok'=>true]);
    }

    public function rate(array $p): void {
        Auth::require();Input::verifyCsrf();
        $avg=$this->m->rate((int)$p['id'],Auth::user()['id'],Input::intRange('rating',1,5));
        $this->json(['ok'=>true,'avg'=>$avg]);
    }

    public function shootPlan(array $p): void {
        Auth::require();
        $date=Input::date('date','get')?:date('Y-m-d');$showId=Input::int('show_id','get');$search=Input::str('search','get');
        $stories=$this->m->getShootPlan($date,$showId,$search);$shows=(new ShowModel())->getAll();
        $this->view('stories/shoot_plan',compact('stories','shows','date','showId','search'));
    }

    public function airPlan(array $p): void {
        Auth::require();
        $date=Input::date('date','get')?:date('Y-m-d');$showId=Input::int('show_id','get');
        $f=['air_date'=>$date];if($showId)$f['show_id']=$showId;
        $stories=$this->m->getList($f);$shows=(new ShowModel())->getAll();
        $noAirDate=Auth::hasRole(['editor','release','coordinator','admin'])?$this->m->getList(['has_air_date'=>false]):[];
        $this->view('stories/air_plan',compact('stories','shows','date','showId','noAirDate'));
    }

    public function anchorText(array $p): void {
        Auth::require();
        $period  = Input::str('period','get') ?: 'today';
        $dateFrom= Input::date('date_from','get');
        $dateTo  = Input::date('date_to','get');
        $showId  = Input::int('show_id','get');
        [$dateFrom,$dateTo] = $this->resolvePeriod($period,$dateFrom,$dateTo);
        $f = ['not_cancelled'=>true];
        if($dateFrom) $f['date_from']=$dateFrom;
        if($dateTo)   $f['date_to']  =$dateTo;
        if($showId)   $f['show_id']  =$showId;
        $stories=$this->m->getList($f);
        $shows=(new ShowModel())->getAll();
        $this->view('stories/anchor_text',compact('stories','shows','period','dateFrom','dateTo','showId'));
    }

    public function reports(array $p): void {
        Auth::require();
        $month=Input::intRange('month',1,12,'get',date('n'));$year=Input::intRange('year',2020,2100,'get',date('Y'));
        $stats=$this->m->getStats($month,$year);
        $this->view('stories/reports',compact('stats','month','year'));
    }

    public function persons(array $p): void {
        Auth::require();
        $page=max(1,Input::int('page','get',1));$search=Input::str('search','get');
        $persons=$this->m->getAllPersons($search,self::PER_PAGE,($page-1)*self::PER_PAGE);
        $total=$this->m->countPersons($search);$pages=(int)ceil($total/self::PER_PAGE);
        $this->view('persons/index',compact('persons','page','pages','total','search'));
    }

    public function personStories(array $p): void {
        Auth::require();$pid=(int)$p['id'];
        $db=DB::getInstance();$person=$db->row('SELECT * FROM persons WHERE id=?',[$pid]);
        if(!$person)$this->notFound();
        $stories=$this->m->getList(['person_id'=>$pid]);
        $this->view('persons/show',compact('person','stories'));
    }
}
