<?php use App\Core\Input;
$isEdit=!empty($user);
$title=$isEdit?'Редактировать сотрудника':'Новый сотрудник';
$action=$isEdit?'/admin/users/'.$user['id'].'/edit':'/admin/users/create';
$RL=['reporter'=>'Корреспондент','editor'=>'Редактор','operator'=>'Оператор','montager'=>'Монтажёр',
     'driver'=>'Водитель','release'=>'Выпускающий редактор','coordinator'=>'Координатор','admin'=>'Администратор'];
$primaryRole=$user['role']??$_POST['role']??'reporter';
$extraRoleSlugs=$user['extra_role_slugs']??[];
?>
<div class="page-header">
  <div><div class="page-title"><?=$title?></div></div>
  <a href="/admin/users" class="btn btn-ghost btn-sm">← Назад</a>
</div>
<div class="page-body">
<?php if(!empty($_SESSION['errors'])): ?>
<div class="flash flash-error" style="margin-bottom:16px">
  <?php foreach($_SESSION['errors'] as $e): ?><div><?=Input::e($e)?></div><?php endforeach;unset($_SESSION['errors']); ?>
</div>
<?php endif; ?>
<form method="POST" action="<?=$action?>" style="max-width:560px">
<?=Input::csrfField()?>
<div class="detail-card">
  <div class="detail-card-title">Личные данные</div>
  <div class="form-group"><label>Полное имя *</label>
    <input type="text" name="name" required value="<?=Input::e($user['name']??$_POST['name']??'')?>"></div>
  <div class="form-row">
    <div class="form-group"><label>Телефон</label>
      <input type="text" name="phone" value="<?=Input::e($user['phone']??'')?>"></div>
    <div class="form-group"><label>Должность</label>
      <input type="text" name="position_title" value="<?=Input::e($user['position_title']??'')?>"></div>
  </div>
</div>

<div class="detail-card">
  <div class="detail-card-title">Учётная запись</div>
  <div class="form-row">
    <div class="form-group"><label>Логин *</label>
      <input type="text" name="login" required pattern="[a-zA-Z0-9_]{3,60}"
             value="<?=Input::e($user['login']??$_POST['login']??'')?>">
      <div style="font-size:11px;color:var(--text3);margin-top:4px">Только латиница, цифры и "_"</div>
    </div>
    <div class="form-group"><label>Пароль <?=$isEdit?'<span style="color:var(--text3)">(пусто = не менять)</span>':'*'?></label>
      <input type="text" name="password" <?=$isEdit?'':'required'?> minlength="4" placeholder="Мин. 4 символа"></div>
  </div>
  <?php if($isEdit): ?>
  <div class="form-group"><label>Статус</label>
    <select name="is_active">
      <option value="1" <?=$user['is_active']?'selected':''?>>✓ Активен</option>
      <option value="0" <?=!$user['is_active']?'selected':''?>>⏸ Деактивирован</option>
    </select>
  </div>
  <?php endif; ?>
</div>

<div class="detail-card">
  <div class="detail-card-title">Роли</div>
  <div class="form-group">
    <label>Основная роль *</label>
    <select name="role" required id="primary-role" onchange="updateRoleCheckboxes()">
      <?php foreach($RL as $slug=>$label): ?>
      <option value="<?=$slug?>" <?=$primaryRole===$slug?'selected':''?>><?=$label?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="form-group">
    <label>Дополнительные роли <span style="color:var(--text3)">(можно несколько)</span></label>
    <div id="extra-roles-wrap" style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:6px">
      <?php foreach($RL as $slug=>$label): ?>
      <label class="extra-role-item role-checkbox-label" data-slug="<?=$slug?>">
        <input type="checkbox" name="extra_roles[]" value="<?=$slug?>"
               <?=in_array($slug,$extraRoleSlugs)?'checked':''?>
               <?=$slug===$primaryRole?'disabled style="opacity:.3"':''?>>
        <span class="role-badge role-<?=$slug?>"><?=$label?></span>
        <span style="font-size:13px;color:var(--text2)"><?=$label?></span>
      </label>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<div class="btn-group">
  <button type="submit" class="btn btn-accent">Сохранить</button>
  <a href="/admin/users" class="btn btn-ghost">Отмена</a>
</div>
</form>
</div>

<script>
function updateRoleCheckboxes(){
  const primary=document.getElementById('primary-role').value;
  document.querySelectorAll('.extra-role-item').forEach(item=>{
    const slug=item.dataset.slug;
    const cb=item.querySelector('input[type=checkbox]');
    if(slug===primary){cb.disabled=true;cb.checked=false;item.style.opacity='.4';}
    else{cb.disabled=false;item.style.opacity='1';}
  });
}
document.addEventListener('DOMContentLoaded',updateRoleCheckboxes);
</script>
