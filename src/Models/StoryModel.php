<?php
namespace App\Models;
use App\Core\DB;

class StoryModel {
    private DB $db;
    public const STATUSES=['запланировано','снято','на проверке (редактор)','на проверке (гл.редактор)','проверено','смонтировано','отсмотрено','готово','вышло в эфир','отменено'];
    public const FLOW=[
        'запланировано'=>['снято','отменено'],
        'снято'=>['на проверке (редактор)','отменено'],
        'на проверке (редактор)'=>['на проверке (гл.редактор)','снято','отменено'],
        'на проверке (гл.редактор)'=>['проверено','на проверке (редактор)','отменено'],
        'проверено'=>['смонтировано','на проверке (гл.редактор)','отменено'],
        'смонтировано'=>['отсмотрено','проверено','отменено'],
        'отсмотрено'=>['готово','смонтировано','отменено'],
        'готово'=>['вышло в эфир','отменено'],
        'вышло в эфир'=>[],
        'отменено'=>[],
    ];
    public function __construct(){ $this->db=DB::getInstance(); }

    private static ?bool $hasDr=null;
    private function checkDriver(): bool {
        if(self::$hasDr===null){
            try{$this->db->query('SELECT driver_id FROM stories LIMIT 0');self::$hasDr=true;}
            catch(\Exception $e){self::$hasDr=false;}
        }
        return self::$hasDr;
    }

    private function buildQuery(array $where,array $params,int $limit=0,int $offset=0): array {
        $hasDr=$this->checkDriver();
        $drJoin=$hasDr?"LEFT JOIN users drv ON drv.id=s.driver_id":"";
        $drSel =$hasDr?"drv.name AS driver_name,":"NULL AS driver_name,";
        $sql="
            SELECT s.*,
                   sh.name AS show_name, sh.color AS show_color,
                   mt.name AS material_type_name,
                   rep.name AS reporter_name,
                   ed.name  AS editor_name,
                   op.name  AS operator_name,
                   mo.name  AS montager_name,
                   {$drSel}
                   vo.name  AS voiceover_name,
                   cr.name  AS creator_name,
                   ROUND(AVG(r.rating),1) AS avg_rating,
                   COUNT(DISTINCT r.id)   AS rating_count,
                   (SELECT sv.content FROM story_versions sv WHERE sv.story_id=s.id ORDER BY sv.id DESC LIMIT 1) AS latest_text
            FROM stories s
            LEFT JOIN shows sh          ON sh.id=s.show_id
            LEFT JOIN material_types mt ON mt.id=s.material_type_id
            LEFT JOIN users rep ON rep.id=s.reporter_id
            LEFT JOIN users ed  ON ed.id=s.editor_id
            LEFT JOIN users op  ON op.id=s.operator_id
            LEFT JOIN users mo  ON mo.id=s.montager_id
            {$drJoin}
            LEFT JOIN users vo  ON vo.id=s.voiceover_id
            LEFT JOIN users cr  ON cr.id=s.created_by
            LEFT JOIN ratings r ON r.story_id=s.id
            WHERE ".implode(' AND ',$where)."
            GROUP BY s.id
            ORDER BY s.importance DESC, s.updated_at DESC";
        if($limit>0) $sql.=" LIMIT ".(int)$limit." OFFSET ".(int)$offset;
        return[$sql,$params];
    }

    public function countList(array $f=[]): int {
        $w=['1=1'];$p=[];
        if(!empty($f['status'])){if(!in_array($f['status'],self::STATUSES,true))return 0;$w[]='s.status=?';$p[]=$f['status'];}
        if(!empty($f['show_id'])){$w[]='s.show_id=?';$p[]=(int)$f['show_id'];}
        if(!empty($f['search'])){$w[]='(s.title LIKE ? OR s.description LIKE ? OR s.info_reason LIKE ?)';$l='%'.$f['search'].'%';$p[]=$l;$p[]=$l;$p[]=$l;}
        if(!empty($f['shoot_date'])){$w[]='s.shoot_date=?';$p[]=$f['shoot_date'];}
        if(!empty($f['air_date'])){$w[]='s.air_date=?';$p[]=$f['air_date'];}
        if(!empty($f['not_cancelled'])){$w[]="s.status != 'отменено'";}
        if(isset($f['has_air_date'])){$w[]=$f['has_air_date']?'s.air_date IS NOT NULL':'s.air_date IS NULL';}
        if(!empty($f['person_id'])){$w[]='EXISTS(SELECT 1 FROM story_persons sp WHERE sp.story_id=s.id AND sp.person_id=?)';$p[]=(int)$f['person_id'];}
        if(!empty($f['date_from'])){$w[]='DATE(s.created_at)>=?';$p[]=$f['date_from'];}
        if(!empty($f['date_to'])){$w[]='DATE(s.created_at)<=?';$p[]=$f['date_to'];}
        $row=$this->db->row('SELECT COUNT(DISTINCT s.id) AS cnt FROM stories s WHERE '.implode(' AND ',$w),$p);
        return (int)($row['cnt']??0);
    }

    public function countPersons(string $search=''): int {
        if($search) $r=$this->db->row('SELECT COUNT(*) AS cnt FROM persons WHERE name LIKE ?',['%'.$search.'%']);
        else $r=$this->db->row('SELECT COUNT(*) AS cnt FROM persons');
        return (int)($r['cnt']??0);
    }

    public function getAllPersons(string $search='', int $limit=0, int $offset=0): array {
        $sql='SELECT p.*,COUNT(sp.story_id) AS story_count FROM persons p LEFT JOIN story_persons sp ON sp.person_id=p.id';
        $p=[];
        if($search){$sql.=' WHERE p.name LIKE ?';$p[]='%'.$search.'%';}
        $sql.=' GROUP BY p.id ORDER BY p.name';
        if($limit>0) $sql.=' LIMIT '.(int)$limit.' OFFSET '.(int)$offset;
        try{return $this->db->rows($sql,$p);}catch(\Exception $e){return[];}
    }

    public function getList(array $f=[], int $limit=0, int $offset=0): array {
        $w=['1=1'];$p=[];
        if(!empty($f['status'])){if(!in_array($f['status'],self::STATUSES,true))return[];$w[]='s.status=?';$p[]=$f['status'];}
        if(!empty($f['show_id'])){$w[]='s.show_id=?';$p[]=(int)$f['show_id'];}
        if(!empty($f['search'])){$w[]='(s.title LIKE ? OR s.description LIKE ? OR s.info_reason LIKE ?)';$l='%'.$f['search'].'%';$p[]=$l;$p[]=$l;$p[]=$l;}
        if(!empty($f['shoot_date'])){$w[]='s.shoot_date=?';$p[]=$f['shoot_date'];}
        if(!empty($f['air_date'])){$w[]='s.air_date=?';$p[]=$f['air_date'];}
        if(!empty($f['not_cancelled'])){$w[]="s.status != 'отменено'";}
        if(isset($f['has_air_date'])){$w[]=$f['has_air_date']?'s.air_date IS NOT NULL':'s.air_date IS NULL';}
        if(!empty($f['month'])&&!empty($f['year'])){$w[]='MONTH(s.air_date)=? AND YEAR(s.air_date)=?';$p[]=(int)$f['month'];$p[]=(int)$f['year'];}
        if(!empty($f['person_id'])){$w[]='EXISTS(SELECT 1 FROM story_persons sp WHERE sp.story_id=s.id AND sp.person_id=?)';$p[]=(int)$f['person_id'];}
        // Фильтр по периоду создания
        if(!empty($f['date_from'])){$w[]='DATE(s.created_at)>=?';$p[]=$f['date_from'];}
        if(!empty($f['date_to'])){$w[]='DATE(s.created_at)<=?';$p[]=$f['date_to'];}
        [$sql,$params]=$this->buildQuery($w,$p,$limit,$offset);
        $rows=$this->db->rows($sql,$params);
        if(empty($rows)) return $rows;
        // Загружаем корреспондентов из story_team одним запросом
        $ids=array_column($rows,'id');
        $ph=implode(',',array_fill(0,count($ids),'?'));
        try{
            $team=$this->db->rows(
                "SELECT st.story_id,st.role_slot,u.name FROM story_team st JOIN users u ON u.id=st.user_id WHERE st.story_id IN({$ph})",
                $ids
            );
            $teamMap=[];
            foreach($team as $t) $teamMap[$t['story_id']][$t['role_slot']][]=$t['name'];
            foreach($rows as &$row) $row['team_by_role']=$teamMap[$row['id']]??[];
            unset($row);
        }catch(\Exception $e){
            foreach($rows as &$row) $row['team_by_role']=[];
            unset($row);
        }
        return $rows;
    }

    // Специальный метод для плана съёмок — простой запрос без лишних JOIN
    public function getShootPlan(?string $date=null, int $showId=0, string $search=''): array {
        $w=["s.status != 'отменено'"];$p=[];
        if($date){$w[]='s.shoot_date=?';$p[]=$date;}
        if($showId){$w[]='s.show_id=?';$p[]=$showId;}
        if($search){$w[]='(s.title LIKE ? OR s.shoot_location LIKE ?)';$l='%'.$search.'%';$p[]=$l;$p[]=$l;}
        $rows=$this->db->rows('
            SELECT s.id, s.title, s.status, s.shoot_date, s.shoot_location,
                   s.air_date, s.importance, s.show_id,
                   sh.name AS show_name, sh.color AS show_color,
                   rep.name AS reporter_name,
                   op.name  AS operator_name
            FROM stories s
            LEFT JOIN shows sh   ON sh.id  = s.show_id
            LEFT JOIN users rep  ON rep.id = s.reporter_id
            LEFT JOIN users op   ON op.id  = s.operator_id
            WHERE '.implode(' AND ',$w).'
            ORDER BY s.shoot_date ASC, s.importance DESC
        ',$p);
        if(empty($rows)) return $rows;
        // Загружаем всю команду из story_team одним запросом
        $ids=array_column($rows,'id');
        $ph=implode(',',array_fill(0,count($ids),'?'));
        try{
            $team=$this->db->rows(
                "SELECT st.story_id,st.role_slot,u.name FROM story_team st JOIN users u ON u.id=st.user_id WHERE st.story_id IN({$ph})",
                $ids
            );
            $teamMap=[];
            foreach($team as $t) $teamMap[$t['story_id']][$t['role_slot']][]=$t['name'];
            foreach($rows as &$row) $row['team_by_role']=$teamMap[$row['id']]??[];
            unset($row);
        }catch(\Exception $e){
            foreach($rows as &$row) $row['team_by_role']=[];
            unset($row);
        }
        return $rows;
    }

    public function getById(int $id): ?array {
        [$sql,$params]=$this->buildQuery(['s.id=?'],[$id]);
        // getById нужна одна запись — убираем LIMIT из конца, берём row
        $s=$this->db->row($sql,$params);
        if(!$s)return null;
        $s['versions']=$this->db->rows('SELECT sv.*,u.name AS author_name FROM story_versions sv LEFT JOIN users u ON u.id=sv.user_id WHERE sv.story_id=? ORDER BY sv.created_at DESC',[$id]);
        $s['logs']=$this->db->rows('SELECT l.*,u.name AS user_name FROM logs l LEFT JOIN users u ON u.id=l.user_id WHERE l.story_id=? ORDER BY l.created_at DESC',[$id]);
        $s['ratings']=$this->db->rows('SELECT user_id,rating FROM ratings WHERE story_id=?',[$id]);
        try{$s['persons']=$this->db->rows('SELECT p.id,p.name FROM persons p JOIN story_persons sp ON sp.person_id=p.id WHERE sp.story_id=?',[$id]);}
        catch(\Exception $e){$s['persons']=[];}
        return $s;
    }

    public function create(array $data,int $uid): int {
        $persons=$data['persons']??[];unset($data['persons']);
        $data['created_by']=$uid;
        $allowed=['title','description','show_id','material_type_id','status','shoot_date',
                  'shoot_location','info_reason','air_date','importance','estimated_duration',
                  'reporter_id','operator_id','editor_id','montager_id','driver_id','voiceover_id','created_by'];
        // Убираем поля которых нет в схеме
        $safe=array_intersect_key($data,array_flip($allowed));
        try{$id=$this->db->insert('stories',$safe);}
        catch(\Exception $e){
            // Убрать driver_id если нет такого поля
            unset($safe['driver_id']);unset($safe['voiceover_id']);
            $id=$this->db->insert('stories',$safe);
        }
        try{$this->syncPersons($id,$persons);}catch(\Exception $e){}
        $this->addLog($id,$uid,'Создал сюжет');
        return $id;
    }

    public function update(int $id,array $data,int $uid): void {
        $persons=$data['persons']??null;unset($data['persons']);
        $allowed=['title','description','show_id','material_type_id','shoot_date','shoot_location',
                  'info_reason','air_date','importance','duration','estimated_duration',
                  'reporter_id','operator_id','editor_id','montager_id','driver_id','voiceover_id'];
        $safe=array_intersect_key($data,array_flip($allowed));
        if(!empty($safe)){try{$this->db->updateById('stories',$id,$safe);}catch(\Exception $e){unset($safe['driver_id']);if(!empty($safe))$this->db->updateById('stories',$id,$safe);}}
        if($persons!==null){try{$this->syncPersons($id,$persons);}catch(\Exception $e){}}
        $this->addLog($id,$uid,'Обновил сюжет');
    }

    public function getRawStatus(int $id): string {
        $r=$this->db->row('SELECT status FROM stories WHERE id=?',[$id]);
        return $r?$r['status']:'не найден';
    }

    public function changeStatus(int $id,string $ns,int $uid): bool {
        if(!in_array($ns,self::STATUSES,true))return false;
        $s=$this->db->row('SELECT status FROM stories WHERE id=?',[$id]);
        if(!$s)return false;
        $old=$s['status'];
        // Обратная совместимость: старый статус "на проверке" = "на проверке (редактор)"
        if($old==='на проверке') $old='на проверке (редактор)';
        if(!in_array($ns,self::FLOW[$old]??[],true))return false;
        $this->db->updateById('stories',$id,['status'=>$ns]);
        $this->addLog($id,$uid,"Изм. статус: {$old} -> {$ns}");
        if($ns==='вышло в эфир'){
            try{$this->db->query('DELETE FROM story_versions WHERE story_id=? AND id NOT IN (SELECT id FROM (SELECT MAX(id) as id FROM story_versions WHERE story_id=?) t)',[$id,$id]);}catch(\Exception $e){}
        }
        return true;
    }

    public function addVersion(int $sid,string $content,int $uid): int {
        // Разрешённые теги — сохраняем форматирование и абзацы
        $allowed = '<b><strong><i><em><u><s><strike><span><ul><ol><li><br><p><div>';
        $clean   = strip_tags($content, $allowed);
        // Убираем опасные атрибуты кроме style и class
        $clean   = preg_replace('/(<[^>]+)\s+on\w+="[^"]*"/i','$1',$clean);
        $clean   = preg_replace('/(<[^>]+)\s+href="javascript:[^"]*"/i','$1',$clean);
        $id=$this->db->insert('story_versions',['story_id'=>$sid,'user_id'=>$uid,'content'=>$clean]);
        $this->addLog($sid,$uid,'Добавил версию текста');
        return $id;
    }

    public function rate(int $sid,int $uid,int $r): float {
        $r=max(1,min(5,$r));
        $this->db->query('INSERT INTO ratings(story_id,user_id,rating)VALUES(?,?,?) ON DUPLICATE KEY UPDATE rating=?',[$sid,$uid,$r,$r]);
        $this->addLog($sid,$uid,"Поставил оценку: {$r}/5");
        $row=$this->db->row('SELECT ROUND(AVG(rating),1) AS avg FROM ratings WHERE story_id=?',[$sid]);
        return(float)($row['avg']??0);
    }

    public function addLog(int $sid,int $uid,string $a): void {
        try {
            $this->db->insert('logs',['story_id'=>$sid,'user_id'=>$uid,'action'=>mb_substr($a,0,255)]);
        } catch(\Exception $e) {
            error_log('addLog failed: '.$e->getMessage());
        }
    }

    public function delete(int $id): void {
        foreach(['story_persons','story_versions','ratings','logs'] as $tbl){
            try{$this->db->query("DELETE FROM {$tbl} WHERE story_id=?",[$id]);}catch(\Exception $e){}
        }
        $this->db->query('DELETE FROM stories WHERE id=?',[$id]);
    }

    // ── Команда (несколько человек на роль) ─────────────
    public function syncTeam(int $sid, array $postData): void {
        // postData: ['reporter_ids'=>[1,3], 'operator_ids'=>[2], ...]
        try {
            $this->db->query('DELETE FROM story_team WHERE story_id=?',[$sid]);
            $slots=['reporter','operator','editor','montager','driver','voiceover'];
            foreach($slots as $slot){
                $ids=(array)($postData[$slot.'_ids']??[]);
                foreach($ids as $uid){
                    $uid=(int)$uid;if(!$uid)continue;
                    $this->db->query(
                        'INSERT IGNORE INTO story_team(story_id,user_id,role_slot)VALUES(?,?,?)',
                        [$sid,$uid,$slot]
                    );
                }
            }
        } catch(\Exception $e){} // таблица может не существовать на старой БД
    }

    public function getTeamMembers(int $sid): array {
        try {
            $rows=$this->db->rows(
                'SELECT st.role_slot,u.id,u.name FROM story_team st JOIN users u ON u.id=st.user_id WHERE st.story_id=? ORDER BY st.role_slot,u.name',
                [$sid]
            );
            $byRole=[];
            foreach($rows as $r) $byRole[$r['role_slot']][]=$r;
            return $byRole;
        } catch(\Exception $e){ return []; }
    }

    public function syncPersons(int $sid,array $names): void {
        $this->db->query('DELETE FROM story_persons WHERE story_id=?',[$sid]);
        foreach($names as $name){
            $name=trim($name);if($name==='')continue;
            $e=$this->db->row('SELECT id FROM persons WHERE name=?',[$name]);
            $pid=$e?$e['id']:$this->db->insert('persons',['name'=>$name]);
            $this->db->query('INSERT IGNORE INTO story_persons(story_id,person_id)VALUES(?,?)',[$sid,$pid]);
        }
    }

    public function getStats(int $month=0,int $year=0): array {
        $w='1=1';$p=[];
        if($month&&$year){$w='MONTH(s.air_date)=? AND YEAR(s.air_date)=?';$p=[$month,$year];}
        return $this->db->rows("SELECT u.id,u.name,r.slug AS role,r.label AS role_label,COUNT(DISTINCT s.id) AS story_count,SUM(TIME_TO_SEC(IFNULL(s.duration,'0:0:0'))) AS total_seconds,ROUND(AVG(rt.rating),1) AS avg_rating FROM users u JOIN roles r ON r.id=u.role_id LEFT JOIN stories s ON ({$w}) AND(s.reporter_id=u.id OR s.operator_id=u.id OR s.editor_id=u.id OR s.montager_id=u.id) LEFT JOIN ratings rt ON rt.user_id=u.id GROUP BY u.id ORDER BY r.id,u.name",$p);
    }

    public function getMaterialTypes(): array {
        try{return $this->db->rows('SELECT * FROM material_types ORDER BY name');}catch(\Exception $e){return[];}
    }


}
