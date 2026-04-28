<?php use App\Core\Input; ?>
<div class="page-header">
  <div><div class="page-title">Мой профиль</div><div class="page-subtitle">Личные данные и настройки</div></div>
</div>
<div class="page-body">
<?php if(!empty($force)): ?>
<div class="flash flash-error" style="margin-bottom:16px">⚠ Вам необходимо сменить пароль перед продолжением работы.</div>
<?php endif; ?>

<?php if(empty($force)): ?>
<div class="detail-card" style="max-width:500px;margin-bottom:16px">
  <div class="detail-card-title">Оформление</div>
  <div class="theme-switch-wrap">
    <label class="theme-switch">
      <input type="checkbox" id="theme-checkbox" onchange="toggleTheme()">
      <span class="theme-slider"></span>
    </label>
    <div class="theme-switch-label">
      <strong id="theme-label-text">Тёмная тема</strong>
      <span style="font-size:13px;color:var(--text3)">Определяется автоматически по настройкам Windows</span>
    </div>
  </div>
</div>
<?php endif; ?>
<form method="POST" action="/profile" style="max-width:500px">
<?=Input::csrfField()?>
<?php if(!empty($force)): ?><input type="hidden" name="force" value="1"><?php endif; ?>

<!-- Основные данные -->
<div class="detail-card">
  <div class="detail-card-title">Основные данные</div>
  <div class="form-group">
    <label>Полное имя *</label>
    <input type="text" name="name" required value="<?=Input::e($user['name'])?>">
  </div>
  <div class="form-row">
    <div class="form-group">
      <label>Телефон</label>
      <input type="text" name="phone" value="<?=Input::e($user['phone']??'')?>">
    </div>
    <div class="form-group">
      <label>Должность</label>
      <input type="text" name="position_title" value="<?=Input::e($user['position_title']??'')?>">
    </div>
  </div>
  <div class="form-group">
    <label>Логин</label>
    <input type="text" value="<?=Input::e($user['login'])?>" disabled style="opacity:.5">
  </div>
  <div class="form-group">
    <label>Роль</label>
    <input type="text" value="<?=Input::e($user['role_label']??'')?>" disabled style="opacity:.5">
  </div>
</div>

<!-- Смена пароля -->
<div class="detail-card">
  <div class="detail-card-title">Смена пароля <?php if(!empty($force)): ?><span style="color:var(--red)">*</span><?php endif; ?></div>
  <div class="form-row">
    <div class="form-group">
      <label>Новый пароль <?php if(!empty($force)): ?>*<?php endif; ?></label>
      <input type="password" name="new_password" minlength="4" placeholder="Минимум 4 символа" <?=!empty($force)?'required':''?>>
    </div>
    <div class="form-group">
      <label>Подтверждение</label>
      <input type="password" name="confirm_password" placeholder="Повторите пароль">
    </div>
  </div>
</div>



<div class="btn-group">
  <button type="submit" class="btn btn-accent">Сохранить</button>
  <?php if(empty($force)): ?><a href="/" class="btn btn-ghost">Отмена</a><?php endif; ?>
</div>
</form>
</div>

<script>
// Инициализация переключателя при загрузке страницы
(function(){
  var theme = document.getElementById('html-root')?.getAttribute('data-theme') ||
              localStorage.getItem('theme') ||
              (window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark');
  var cb  = document.getElementById('theme-checkbox');
  var lbl = document.getElementById('theme-label-text');
  if(cb)  cb.checked  = (theme === 'light');
  if(lbl) lbl.textContent = (theme === 'light') ? 'Светлая тема' : 'Тёмная тема';
})();
</script>
