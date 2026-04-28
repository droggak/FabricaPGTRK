<?php
namespace App\Models;
use App\Core\DB;

class UserModel {
    private DB $db;
    public const ROLES=['admin','coordinator','reporter','editor','operator','montager','release','driver'];
    public const ROLE_LABELS=['admin'=>'Администратор','coordinator'=>'Координатор','reporter'=>'Корреспондент',
        'editor'=>'Редактор','operator'=>'Оператор','montager'=>'Монтажёр','release'=>'Выпускающий редактор','driver'=>'Водитель'];

    public function __construct(){ $this->db=DB::getInstance(); }

    public function getAll(): array {
        $users=$this->db->rows('SELECT u.*,r.slug AS role,r.label AS role_label FROM users u JOIN roles r ON r.id=u.role_id ORDER BY r.id,u.name');
        // Добавляем дополнительные роли из user_roles
        foreach($users as &$u){
            $u['extra_roles']=$this->getExtraRoles($u['id'],$u['role']);
            $u['all_roles']=array_merge([$u['role']],array_column($u['extra_roles'],'slug'));
        }
        return $users;
    }

    public function getById(int $id): ?array {
        $u=$this->db->row('SELECT u.*,r.slug AS role,r.label AS role_label FROM users u JOIN roles r ON r.id=u.role_id WHERE u.id=?',[$id]);
        if(!$u)return null;
        $u['extra_roles']=$this->getExtraRoles($id,$u['role']);
        $u['extra_role_slugs']=array_column($u['extra_roles'],'slug');
        $u['all_roles']=array_merge([$u['role']],$u['extra_role_slugs']);
        return $u;
    }

    private function getExtraRoles(int $uid, string $primaryRole): array {
        try {
            return $this->db->rows('SELECT r.slug,r.label FROM user_roles ur JOIN roles r ON r.id=ur.role_id WHERE ur.user_id=? AND r.slug!=?',[$uid,$primaryRole]);
        } catch(\Exception $e){ return []; }
    }

    public function getUsersByRole(string $role): array {
        // Ищем по основной роли ИЛИ по дополнительным ролям
        try {
            return $this->db->rows('SELECT DISTINCT u.id,u.name,r.slug AS role FROM users u JOIN roles r ON r.id=u.role_id LEFT JOIN user_roles ur ON ur.user_id=u.id LEFT JOIN roles r2 ON r2.id=ur.role_id WHERE u.is_active=1 AND (r.slug=? OR r2.slug=?) ORDER BY u.name',[$role,$role]);
        } catch(\Exception $e){
            return $this->db->rows('SELECT u.id,u.name,r.slug AS role FROM users u JOIN roles r ON r.id=u.role_id WHERE u.is_active=1 AND r.slug=? ORDER BY u.name',[$role]);
        }
    }

    public function loginExists(string $login,int $excludeId=0): bool {
        return(bool)$this->db->row('SELECT id FROM users WHERE login=? AND id!=?',[$login,$excludeId]);
    }

    public function create(array $data): int {
        $rid=$this->getRoleId($data['role']);
        if(!$rid)throw new \InvalidArgumentException('Bad role');
        $id=$this->db->insert('users',['login'=>$data['login'],'password'=>password_hash($data['password'],PASSWORD_BCRYPT,['cost'=>12]),'name'=>$data['name'],'role_id'=>$rid,'must_change_password'=>1,'phone'=>$data['phone']??null,'position_title'=>$data['position_title']??null]);
        // Сохранить дополнительные роли
        if(!empty($data['extra_roles'])){
            $this->saveExtraRoles($id,$data['extra_roles'],$data['role']);
        }
        return $id;
    }

    public function update(int $id,array $data): void {
        $u=['name'=>$data['name'],'login'=>$data['login'],'phone'=>$data['phone']??null,'position_title'=>$data['position_title']??null];
        if(!empty($data['password']))$u['password']=password_hash($data['password'],PASSWORD_BCRYPT,['cost'=>12]);
        if(!empty($data['role'])){$rid=$this->getRoleId($data['role']);if($rid)$u['role_id']=$rid;}
        if(isset($data['is_active']))$u['is_active']=(int)$data['is_active'];
        $this->db->updateById('users',$id,$u);
        // Обновить дополнительные роли
        if(isset($data['extra_roles'])){
            $this->saveExtraRoles($id,$data['extra_roles'],$data['role']??'');
        }
    }

    public function updateProfile(int $id,array $data): void {
        $u=['name'=>$data['name'],'phone'=>$data['phone']??null,'position_title'=>$data['position_title']??null];
        if(!empty($data['password'])){$u['password']=password_hash($data['password'],PASSWORD_BCRYPT,['cost'=>12]);$u['must_change_password']=0;}
        $this->db->updateById('users',$id,$u);
    }

    private function saveExtraRoles(int $uid, array $slugs, string $primarySlug): void {
        try {
            $this->db->query('DELETE FROM user_roles WHERE user_id=?',[$uid]);
            // Добавить основную роль
            $primaryRid=$this->getRoleId($primarySlug);
            if($primaryRid) $this->db->query('INSERT IGNORE INTO user_roles(user_id,role_id)VALUES(?,?)',[$uid,$primaryRid]);
            // Добавить дополнительные
            foreach($slugs as $slug){
                if($slug===$primarySlug)continue;
                $rid=$this->getRoleId($slug);
                if($rid) $this->db->query('INSERT IGNORE INTO user_roles(user_id,role_id)VALUES(?,?)',[$uid,$rid]);
            }
        } catch(\Exception $e){}
    }

    public function delete(int $id): void {
        foreach(['reporter_id','operator_id','editor_id','montager_id','driver_id','voiceover_id'] as $f){
            try{$this->db->query("UPDATE stories SET {$f}=NULL WHERE {$f}=?",[$id]);}catch(\Exception $e){}
        }
        $this->db->deleteById('users',$id);
    }

    public function setActive(int $id,bool $a): void { $this->db->updateById('users',$id,['is_active'=>(int)$a]); }

    private function getRoleId(string $slug): ?int {
        $r=$this->db->row('SELECT id FROM roles WHERE slug=?',[$slug]);
        return $r?(int)$r['id']:null;
    }
}
