<?php
namespace App\Controllers;
use App\Core\DB;
use App\Core\Input;

class AuthController extends BaseController {
    public function loginForm(array $p): void {
        if(!empty($_SESSION['user_id']))$this->redirect('/');
        $error=$_SESSION['login_error']??null;unset($_SESSION['login_error']);
        require ROOT.'/views/auth/login.php';
    }
    public function loginPost(array $p): void {
        Input::verifyCsrf();
        $attempts=$_SESSION['login_attempts']??0;
        $last=$_SESSION['last_login_attempt']??0;
        if($attempts>=5&&(time()-$last)<300){$_SESSION['login_error']='Слишком много попыток. Подождите 5 минут.';$this->redirect('/login');}
        $login=Input::str('login');$password=Input::str('password');
        if($login===''||$password===''){$_SESSION['login_error']='Введите логин и пароль.';$this->redirect('/login');}
        $db=DB::getInstance();
        $user=$db->row('SELECT u.id,u.login,u.password,u.name,u.is_active,u.must_change_password,r.slug AS role FROM users u JOIN roles r ON r.id=u.role_id WHERE u.login=? LIMIT 1',[$login]);
        if(!$user||!$user['is_active']||!password_verify($password,$user['password'])){
            $_SESSION['login_attempts']=$attempts+1;$_SESSION['last_login_attempt']=time();
            $_SESSION['login_error']='Неверный логин или пароль.';$this->redirect('/login');
        }
        session_regenerate_id(true);
        $_SESSION['login_attempts']=0;
        $_SESSION['user_id']  =$user['id'];
        $_SESSION['user_name']=$user['name'];
        $_SESSION['user_role']=$user['role'];
        // Загружаем все роли пользователя
        try {
            $db=DB::getInstance();
            $extraRoles=$db->rows('SELECT r.slug FROM user_roles ur JOIN roles r ON r.id=ur.role_id WHERE ur.user_id=?',[$user['id']]);
            $allRoles=array_unique(array_merge([$user['role']],array_column($extraRoles,'slug')));
        } catch(\Exception $e){ $allRoles=[$user['role']]; }
        $_SESSION['user_roles']=$allRoles;
        if($user['must_change_password'])$this->redirect('/profile?force=1');
        else $this->redirect('/');
    }
    public function logout(array $p): void {
        Input::verifyCsrf();$_SESSION=[];session_destroy();$this->redirect('/login');
    }
}
