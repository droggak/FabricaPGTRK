<?php use App\Core\Input; ?>
<style>
.mt-form{display:flex;gap:10px;margin-bottom:20px;align-items:flex-end;flex-wrap:wrap}
.mt-form input{background:var(--bg3);border:1px solid var(--border);border-radius:8px;padding:9px 14px;color:var(--text);font-size:14px;outline:none;transition:border-color .2s;width:280px;}
.mt-form input:focus{border-color:var(--accent);}
</style>

<div class="page-header">
  <div><div class="page-title">Типы материалов</div><div class="page-subtitle">Справочник видов телематериалов</div></div>
</div>
<div class="page-body">

<!-- Форма добавления/редактирования -->
<div class="detail-card" style="max-width:100%">
  <div class="detail-card-title" id="mt-form-title">Добавить тип материала</div>
  <form method="POST" action="/admin/material-types/save" class="mt-form">
    <?=Input::csrfField()?>
    <input type="hidden" name="id" id="mt-edit-id">
    <input type="text" name="name" id="mt-name-input" placeholder="Название типа..." required>
    <button type="submit" class="btn btn-accent">Сохранить</button>
    <button type="button" class="btn btn-ghost" onclick="resetMtForm()">Отмена</button>
  </form>
</div>

<!-- Список -->
<div class="table-wrap" style="max-width:100%">
  <table class="data-table">
    <thead><tr><th>Название</th><th style="width:120px">Действия</th></tr></thead>
    <tbody>
    <?php if(empty($types)): ?>
      <tr><td colspan="2"><div class="empty-state" style="padding:30px"><div class="empty-text">Нет типов материалов</div></div></td></tr>
    <?php else: foreach($types as $t): ?>
      <tr>
        <td><?=Input::e($t['name'])?></td>
        <td>
          <div style="display:flex;gap:6px">
            <button class="btn btn-sm btn-ghost" onclick="editMt(<?=$t['id']?>,'<?=Input::e(addslashes($t['name']))?>')">✎</button>
            <form method="POST" action="/admin/material-types/<?=$t['id']?>/delete" style="margin:0">
              <?=Input::csrfField()?>
              <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Удалить тип?')">✕</button>
            </form>
          </div>
        </td>
      </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>
</div>

<script>
function editMt(id, name){
  document.getElementById('mt-edit-id').value=id;
  document.getElementById('mt-name-input').value=name;
  document.getElementById('mt-form-title').textContent='Редактировать тип';
  document.getElementById('mt-name-input').focus();
}
function resetMtForm(){
  document.getElementById('mt-edit-id').value='';
  document.getElementById('mt-name-input').value='';
  document.getElementById('mt-form-title').textContent='Добавить тип материала';
}
</script>
