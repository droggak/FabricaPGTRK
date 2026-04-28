<?php
namespace App\Controllers;
use App\Core\Input;
use App\Middleware\Auth;
use App\Models\UserModel;

class ProfileController extends BaseController {
    public function show(array $p): void {
        Auth::require();
        $user=(new UserModel())->getById(Auth::user()['id']);
        $force=!empty($_GET['force']);
        $this->view('profile/show',compact('user','force'));
    }
    public function update(array $p): void {
        Auth::require();Input::verifyCsrf();
        $uid=Auth::user()['id'];$m=new UserModel();
        $newPass=Input::str('new_password');$confPass=Input::str('confirm_password');
        $errors=[];
        if(empty(Input::str('name')))$errors[]='Введите имя.';
        if($newPass!==''&&strlen($newPass)<4)$errors[]='Новый пароль минимум 4 символа.';
        if($newPass!==''&&$newPass!==$confPass)$errors[]='Пароли не совпадают.';
        // Если force — пароль обязателен
        if(!empty($_POST['force'])&&$newPass==='')$errors[]='Необходимо сменить пароль.';
        if($errors){$_SESSION['errors']=$errors;$this->redirect('/profile'.(!empty($_POST['force'])?'?force=1':''));}
        $data=['name'=>Input::str('name'),'phone'=>Input::str('phone'),'position_title'=>Input::str('position_title')];
        if($newPass!=='')$data['password']=$newPass;
        $m->updateProfile($uid,$data);
        $_SESSION['user_name']=Input::str('name');
        $_SESSION['success']='Профиль обновлён.';
        $this->redirect('/profile');
    }
}
