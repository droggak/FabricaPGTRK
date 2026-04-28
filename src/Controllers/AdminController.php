<?php
namespace App\Controllers;
use App\Core\Input;
use App\Middleware\Auth;
use App\Models\ShowModel;
use App\Models\UserModel;

class AdminController extends BaseController {
    public function users(array $p): void {
        Auth::requireAdmin();$users=(new UserModel())->getAll();$this->view('admin/users',compact('users'));
    }
    public function userCreateForm(array $p): void {
        Auth::requireAdmin();$this->view('admin/user_form',['user'=>null,'roles'=>UserModel::ROLES]);
    }
    public function userCreatePost(array $p): void {
        Auth::requireAdmin();Input::verifyCsrf();
        $data=$this->extractUserData();$errors=$this->validateUserData($data);
        $m=new UserModel();
        if($m->loginExists($data['login']))$errors[]='Логин уже занят.';
        if(empty($data['password']))$errors[]='Введите пароль.';
        if($errors){$_SESSION['errors']=$errors;$this->redirect('/admin/users/create');}
        $m->create($data);$_SESSION['success']='Сотрудник добавлен.';$this->redirect('/admin/users');
    }
    public function userEditForm(array $p): void {
        Auth::requireAdmin();$user=(new UserModel())->getById((int)$p['id']);
        if(!$user)$this->notFound();$this->view('admin/user_form',compact('user')+['roles'=>UserModel::ROLES]);
    }
    public function userEditPost(array $p): void {
        Auth::requireAdmin();Input::verifyCsrf();
        $id=(int)$p['id'];$data=$this->extractUserData();$errors=$this->validateUserData($data,false);
        $m=new UserModel();
        if($m->loginExists($data['login'],$id))$errors[]='Логин уже занят.';
        if($errors){$_SESSION['errors']=$errors;$this->redirect('/admin/users/'.$id.'/edit');}
        $m->update($id,$data);$_SESSION['success']='Сотрудник обновлён.';$this->redirect('/admin/users');
    }
    public function userToggle(array $p): void {
        Auth::requireAdmin();Input::verifyCsrf();
        $id=(int)$p['id'];if($id===Auth::user()['id'])$this->json(['ok'=>false,'error'=>'Нельзя деактивировать себя'],422);
        $m=new UserModel();$user=$m->getById($id);if(!$user)$this->json(['ok'=>false],404);
        $newState=!$user['is_active'];$m->setActive($id,$newState);
        $this->json(['ok'=>true,'active'=>$newState]);
    }
    public function userDelete(array $p): void {
        Auth::requireAdmin();Input::verifyCsrf();
        $id=(int)$p['id'];if($id===Auth::user()['id'])$this->json(['ok'=>false,'error'=>'Нельзя удалить себя'],422);
        (new UserModel())->delete($id);$this->json(['ok'=>true]);
    }
    public function shows(array $p): void {
        Auth::requireAdmin();$shows=(new ShowModel())->getAll();$this->view('admin/shows',compact('shows'));
    }
    public function showCreatePost(array $p): void {
        Auth::requireAdmin();Input::verifyCsrf();
        $data=$this->extractShowData();if(empty($data['name']))$this->json(['ok'=>false,'error'=>'Введите название'],422);
        $id=(new ShowModel())->create($data,Auth::user()['id']);$this->json(['ok'=>true,'id'=>$id]);
    }
    public function showEditPost(array $p): void {
        Auth::requireAdmin();Input::verifyCsrf();
        $id=(int)$p['id'];$data=$this->extractShowData();if(empty($data['name']))$this->json(['ok'=>false,'error'=>'Введите название'],422);
        (new ShowModel())->update($id,$data);$this->json(['ok'=>true]);
    }
    public function showDelete(array $p): void {
        Auth::requireAdmin();Input::verifyCsrf();(new ShowModel())->delete((int)$p['id']);$this->json(['ok'=>true]);
    }
    private function extractUserData(): array {
        $extraRoles=isset($_POST['extra_roles'])&&is_array($_POST['extra_roles'])
            ? array_filter($_POST['extra_roles'],fn($r)=>in_array($r,UserModel::ROLES,true))
            : [];
        return[
            'name'=>Input::str('name'),
            'login'=>Input::str('login'),
            'password'=>Input::str('password'),
            'role'=>Input::enum('role',UserModel::ROLES),
            'phone'=>Input::str('phone'),
            'position_title'=>Input::str('position_title'),
            'is_active'=>Input::int('is_active','post',1),
            'extra_roles'=>$extraRoles,
        ];
    }
    private function validateUserData(array $data,bool $reqPass=true): array {
        $e=[];
        if(empty($data['name']))$e[]='Введите имя.';
        if(empty($data['login']))$e[]='Введите логин.';
        if(!preg_match('/^[a-zA-Z0-9_]{3,60}$/',$data['login']))$e[]='Логин: только латиница, цифры и "_", 3–60 символов.';
        if($reqPass&&strlen($data['password'])<4)$e[]='Пароль минимум 4 символа.';
        if(!empty($data['password'])&&strlen($data['password'])<4)$e[]='Пароль минимум 4 символа.';
        if(!$data['role'])$e[]='Выберите роль.';
        return $e;
    }
    private function extractShowData(): array {
        return['name'=>Input::str('name'),'description'=>Input::str('description'),'color'=>Input::enum('color',ShowModel::COLORS,'post','accent'),'air_time'=>Input::time('air_time')?:null];
    }

    // ── Типы материалов ──────────────────────────────────────
    public function materialTypes(array $p): void {
        Auth::requireAdmin();
        $db=\App\Core\DB::getInstance();
        $types=$db->rows('SELECT * FROM material_types ORDER BY name');
        $this->view('admin/material_types',compact('types'));
    }

    public function materialTypeSave(array $p): void {
        Auth::requireAdmin();
        Input::verifyCsrf();
        $name=Input::str('name');
        $id  =Input::int('id');
        if(empty($name)){$_SESSION['errors']=['Введите название.'];$this->redirect('/admin/material-types');}
        $db=\App\Core\DB::getInstance();
        if($id){
            $db->updateById('material_types',$id,['name'=>$name]);
            $_SESSION['success']='Тип обновлён.';
        } else {
            $slug=preg_replace('/[^a-z0-9]+/','_',mb_strtolower($name,'UTF-8'));
            $slug=$slug.'_'.time(); // уникальность
            try{$db->insert('material_types',['name'=>$name,'slug'=>$slug]);}
            catch(\Exception $e){$_SESSION['errors']=['Ошибка сохранения.'];}
            $_SESSION['success']='Тип добавлен.';
        }
        $this->redirect('/admin/material-types');
    }

    public function materialTypeDelete(array $p): void {
        Auth::requireAdmin();
        Input::verifyCsrf();
        $id=(int)$p['id'];
        $db=\App\Core\DB::getInstance();
        // Снять привязку у сюжетов
        try{$db->query('UPDATE stories SET material_type_id=NULL WHERE material_type_id=?',[$id]);}catch(\Exception $e){}
        try{$db->deleteById('material_types',$id);}catch(\Exception $e){}
        $_SESSION['success']='Тип удалён.';
        $this->redirect('/admin/material-types');
    }
}
