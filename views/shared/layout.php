<?php
use App\Core\Input;
use App\Middleware\Auth;
$appCfg=require ROOT.'/config/app.php';
$cu=Auth::user();$cr=$cu['role']??'';
// isAdmin: проверяем основную роль, все роли в сессии и slug
$allRoles=Auth::roles();
$isAdmin=in_array('admin',$allRoles,true)||$cr==='admin'||($_SESSION['user_role']??'')==='admin';
$RL=['admin'=>'Администратор','coordinator'=>'Координатор','reporter'=>'Корреспондент',
     'editor'=>'Редактор','operator'=>'Оператор','montager'=>'Монтажёр',
     'release'=>'Выпускающий редактор','driver'=>'Водитель'];
?><!DOCTYPE html>
<html lang="ru" id="html-root">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title><?=Input::e($appCfg['name'])?></title>
<meta name="csrf" content="<?=Input::csrfToken()?>">
<!-- Тема применяется ДО загрузки CSS — нет мигания -->
<script>
(function(){
  var saved=localStorage.getItem('fn_theme');
  var sys='light'; // светлая по умолчанию
  if(window.matchMedia){
    // Если система явно задала тёмную — используем тёмную
    sys=window.matchMedia('(prefers-color-scheme:dark)').matches?'dark':'light';
  }
  document.documentElement.setAttribute('data-theme', saved||sys);
})();
</script>
<link rel="stylesheet" href="/css/app.css">
</head>
<body>
<!-- Кнопка переключения темы — фиксированная позиция -->

<div class="app-wrap">

<!-- Кнопка темы — левый верхний угол, поверх всего -->


<aside class="sidebar">
  <div class="sidebar-logo">
    <div class="logo-tag">НС v2.0</div>
    <div class="logo-name"><?=Input::e($appCfg['name'])?></div>
  </div>
  <div class="sidebar-user">
    <div class="user-name"><?=Input::e($cu['name'])?></div>
    <span class="role-badge role-<?=Input::e($cr)?>"><?=Input::e($RL[$cr]??$cr)?></span>
  </div>
  <nav class="sidebar-nav">
    <div class="nav-section">Основное</div>
    <a class="nav-item <?=$_SERVER['REQUEST_URI']==='/'?'active':''?>" href="/"><span class="nav-icon">⬛</span> Дашборд</a>

    <?php if($isAdmin): ?>
      <div class="nav-section">Администрирование</div>
      <a class="nav-item <?=str_starts_with($_SERVER['REQUEST_URI'],'/admin/users')?'active':''?>" href="/admin/users"><span class="nav-icon">👥</span> Сотрудники</a>
      <a class="nav-item <?=str_starts_with($_SERVER['REQUEST_URI'],'/admin/shows')?'active':''?>" href="/admin/shows"><span class="nav-icon">📺</span> Передачи</a>
      <a class="nav-item <?=str_starts_with($_SERVER['REQUEST_URI'],'/admin/material-types')?'active':''?>" href="/admin/material-types"><span class="nav-icon">🗂</span> Типы материалов</a>
      <div class="nav-section">Просмотр</div>
      <a class="nav-item <?=str_starts_with($_SERVER['REQUEST_URI'],'/stories')?'active':''?>" href="/stories"><span class="nav-icon">📋</span> Сюжеты</a>
      <a class="nav-item <?=str_starts_with($_SERVER['REQUEST_URI'],'/reports')?'active':''?>" href="/reports"><span class="nav-icon">📊</span> Отчёты</a>
    <?php else: ?>
      <a class="nav-item <?=str_starts_with($_SERVER['REQUEST_URI'],'/stories')?'active':''?>" href="/stories"><span class="nav-icon">📋</span> Сюжеты</a>
      <div class="nav-section">Планирование</div>
      <a class="nav-item <?=str_starts_with($_SERVER['REQUEST_URI'],'/shoot-plan')?'active':''?>" href="/shoot-plan"><span class="nav-icon">🎬</span> План съёмок</a>
      <a class="nav-item <?=str_starts_with($_SERVER['REQUEST_URI'],'/air-plan')?'active':''?>" href="/air-plan"><span class="nav-icon">📡</span> План выпуска</a>
      <div class="nav-section">Инструменты</div>
      <a class="nav-item <?=str_starts_with($_SERVER['REQUEST_URI'],'/anchor-text')?'active':''?>" href="/anchor-text"><span class="nav-icon">📢</span> Дикторский текст</a>
      <a class="nav-item <?=str_starts_with($_SERVER['REQUEST_URI'],'/reports')?'active':''?>" href="/reports"><span class="nav-icon">📊</span> Отчёты</a>
    <?php endif; ?>
  </nav>
  <div class="sidebar-bottom">
    <?php if($isAdmin): ?>
    <?php
      try{ $db=\App\Core\DB::getInstance();
        $fbCnt=(int)($db->row('SELECT COUNT(*) AS n FROM feedback WHERE is_read=0')['n']??0);
      }catch(\Exception $e){$fbCnt=0;} ?>
    <a class="nav-item <?=str_starts_with($_SERVER['REQUEST_URI'],'/admin/feedback')?'active':''?>"
       href="/admin/feedback" style="border-left:none">
      <span class="nav-icon">📬</span> Обращения
      <?php if($fbCnt>0): ?>
        <span style="margin-left:auto;background:var(--red);color:#fff;font-size:11px;font-weight:700;padding:1px 7px;border-radius:20px"><?=$fbCnt?></span>
      <?php endif; ?>
    </a>
    <?php endif; ?>
    <a class="nav-item <?=str_starts_with($_SERVER['REQUEST_URI'],'/profile')?'active':''?>" href="/profile" style="border-left:none"><span class="nav-icon">👤</span> Мой профиль</a>
    <button class="nav-item" style="background:none;border:none;cursor:pointer;text-align:left;color:var(--text2);width:100%;border-left:none;padding:9px 20px;font-family:var(--font);font-size:14px;display:flex;align-items:center;gap:10px"
            onclick="document.getElementById('feedback-modal').classList.add('active')">
      <span class="nav-icon">🐛</span> Сообщить об ошибке
    </button>
    <form method="POST" action="/logout" style="margin:4px 0 0"><?=Input::csrfField()?>
      <button type="submit" class="logout-btn">⟵ Выйти</button>
    </form>
  </div>
</aside>

<main class="main-content">
  <?php if(!empty($_SESSION['success'])): ?>
    <div class="flash flash-success"><?=Input::e($_SESSION['success'])?></div>
    <?php unset($_SESSION['success']); ?>
  <?php endif; ?>
  <?php if(!empty($_SESSION['error'])): ?>
    <div class="flash flash-error"><?=Input::e($_SESSION['error'])?></div>
    <?php unset($_SESSION['error']); ?>
  <?php endif; ?>
  <?php if(!empty($_SESSION['errors'])): ?>
    <div class="flash flash-error">
      <?php foreach($_SESSION['errors'] as $err): ?><div><?=Input::e($err)?></div><?php endforeach; ?>
    </div>
    <?php unset($_SESSION['errors']); ?>
  <?php endif; ?>
  <?php require ROOT.'/views/'.$template.'.php'; ?>
</main>
</div>

<script src="/js/app.js"></script>
<script>
function applyTheme(t){
  document.documentElement.setAttribute('data-theme',t);
  var ic=document.getElementById('theme-icon');
  if(ic) ic.textContent=t==='light'?'☀️':'🌙';
}
function toggleTheme(){
  var cur=document.documentElement.getAttribute('data-theme')||'dark';
  var next=cur==='dark'?'light':'dark';
  localStorage.setItem('fn_theme',next);
  applyTheme(next);
}
// Инициализация иконки после загрузки
(function(){
  applyTheme(document.documentElement.getAttribute('data-theme')||'light');
  if(window.matchMedia){
    window.matchMedia('(prefers-color-scheme:light)').addEventListener('change',function(e){
      if(!localStorage.getItem('fn_theme')) applyTheme(e.matches?'light':'dark');
    });
  }
})();
</script>
<!-- Глобальная модалка подтверждения -->
<div class="modal-overlay" id="confirm-modal">
  <div class="modal" style="max-width:380px">
    <div class="modal-header">
      <div class="modal-title" id="confirm-modal-title">Подтверждение</div>
      <button class="modal-close" onclick="confirmModalCancel()">✕</button>
    </div>
    <div class="modal-body">
      <p id="confirm-modal-text" style="color:var(--text2);font-size:14px;margin:0"></p>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="confirmModalCancel()">Отмена</button>
      <button class="btn btn-danger" id="confirm-modal-ok">Подтвердить</button>
    </div>
  </div>
</div>
<script>
var _confirmCb=null;
function showConfirm(title,text,okLabel,cb){
  document.getElementById('confirm-modal-title').textContent=title;
  document.getElementById('confirm-modal-text').textContent=text;
  var okBtn=document.getElementById('confirm-modal-ok');
  okBtn.textContent=okLabel||'Подтвердить';
  okBtn.className='btn '+(okLabel&&okLabel.includes('Отмен')?'btn-danger':'btn-danger');
  _confirmCb=cb;
  document.getElementById('confirm-modal').classList.add('active');
}
function confirmModalCancel(){
  document.getElementById('confirm-modal').classList.remove('active');
  _confirmCb=null;
}
document.getElementById('confirm-modal-ok').addEventListener('click',function(){
  document.getElementById('confirm-modal').classList.remove('active');
  if(_confirmCb){_confirmCb();_confirmCb=null;}
});
</script>
<!-- Модалка обратной связи -->
<div class="modal-overlay" id="feedback-modal">
  <div class="modal" style="max-width:480px">
    <div class="modal-header">
      <div class="modal-title">Сообщить об ошибке</div>
      <button class="modal-close" onclick="closeModal('feedback-modal')">✕</button>
    </div>
    <div class="modal-body">
      <div class="form-group">
        <label>Тип обращения</label>
        <select id="fb-category" class="filter-select" style="width:100%">
          <option value="bug">🐛 Ошибка в системе</option>
          <option value="suggestion">💡 Предложение по улучшению</option>
          <option value="other">📝 Другое</option>
        </select>
      </div>
      <div class="form-group">
        <label>Описание *</label>
        <textarea id="fb-message" rows="5" placeholder="Опишите проблему подробно: что делали, что ожидали, что произошло..."
                  style="width:100%;resize:vertical;min-height:120px"></textarea>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('feedback-modal')">Отмена</button>
      <button class="btn btn-accent" onclick="fbSubmit()">Отправить</button>
    </div>
  </div>
</div>

<script>
function fbSubmit(){
  var msg=document.getElementById('fb-message').value.trim();
  var cat=document.getElementById('fb-category').value;
  if(!msg){toast('Введите описание','err');return;}
  var fd=new FormData();
  fd.append('_csrf',getCsrf());
  fd.append('category',cat);
  fd.append('message',msg);
  fetch('/feedback/submit',{method:'POST',body:fd})
    .then(r=>r.json())
    .then(j=>{
      if(j.ok){
        closeModal('feedback-modal');
        document.getElementById('fb-message').value='';
        toast('Спасибо! Администраторы получат ваше сообщение.');
      } else toast(j.error||'Ошибка','err');
    }).catch(()=>toast('Ошибка сети','err'));
}
</script>
</body>
</html>
