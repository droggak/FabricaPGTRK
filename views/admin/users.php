<?php use App\Core\Input; ?>
<style>
.user-table{width:100%;border-collapse:collapse;font-size:13px;}
.user-table th{text-align:left;padding:10px 14px;font-size:11px;font-weight:500;color:var(--text3);
  text-transform:uppercase;letter-spacing:.08em;border-bottom:1px solid var(--border);}
.user-table td{padding:10px 14px;border-bottom:1px solid var(--border);vertical-align:middle;}
.user-table tr:last-child td{border-bottom:none;}
.user-table tbody tr:hover td{background:rgba(255,255,255,.02);}
.user-avatar-sm{width:34px;height:34px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;flex-shrink:0;}
.role-tags{display:flex;flex-wrap:wrap;gap:4px;}
.inactive-row td{opacity:.45;}
</style>

<div class="page-header">
  <div><div class="page-title">Сотрудники</div><div class="page-subtitle">Всего: <?=($total??count($users))?></div></div>
  <a href="/admin/users/create" class="btn btn-accent btn-sm">+ Добавить сотрудника</a>
</div>
<div class="page-body">

<form method="GET" action="/admin/users" class="table-toolbar" style="margin-bottom:16px">
  <input class="search-input" type="text" name="search"
         placeholder="🔍 Поиск по имени или логину..."
         value="<?=\App\Core\Input::e($search??'')?>" style="width:300px"
         onkeydown="if(event.key==='Enter'){this.form.submit();}">
  <button type="submit" class="btn btn-ghost btn-sm">Найти</button>
  <a href="/admin/users" class="btn btn-ghost btn-sm">Сбросить</a>
  <span style="font-size:13px;color:var(--text3);margin-left:auto"><?=($total??count($users))?> сотрудников</span>
</form>

<div class="table-wrap">
<table class="user-table" id="user-table">
  <thead><tr>
    <th style="width:44px"></th>
    <th>Сотрудник</th>
    <th>Логин</th>
    <th>Роли</th>
    <th>Должность</th>
    <th>Телефон</th>
    <th style="width:160px">Действия</th>
  </tr></thead>
  <tbody id="user-tbody">
  <?php
  $AVBG=['rgba(108,139,255,.25)','rgba(62,207,142,.25)','rgba(167,139,250,.25)','rgba(247,183,49,.25)','rgba(240,107,107,.25)','rgba(45,212,191,.25)'];
  $AVTX=['var(--accent)','var(--green)','var(--purple)','var(--amber)','var(--red)','var(--teal)'];
  foreach($users as $i=>$u):
    $ci=$i%count($AVBG);
    $ini=mb_strtoupper(mb_substr($u['name'],0,1,'UTF-8'),'UTF-8');
    $isSelf=($u['id']==($_SESSION['user_id']??0));
    $extraRoles=$u['extra_roles']??[];
    $allRoleSlugs=$u['all_roles']??[$u['role']];
  ?>
  <tr class="user-row <?=!$u['is_active']?'inactive-row':''?>" 
      data-name="<?=mb_strtolower(Input::e($u['name']),'UTF-8')?>"
      data-login="<?=mb_strtolower(Input::e($u['login']),'UTF-8')?>"
      data-role="<?=Input::e($u['role'])?>"
      data-extra-roles="<?=Input::e(implode(',',array_column($extraRoles,'slug')))?>"
      data-active="<?=$u['is_active']?'active':'inactive'?>"
      id="urow-<?=$u['id']?>">
    <td>
      <div class="user-avatar-sm" style="background:<?=$AVBG[$ci]?>;color:<?=$AVTX[$ci]?>"><?=$ini?></div>
    </td>
    <td>
      <div style="font-weight:600"><?=Input::e($u['name'])?></div>
      <?php if(!$u['is_active']): ?><div style="font-size:11px;color:var(--red)">деактивирован</div><?php endif; ?>
    </td>
    <td class="mono text-dim">@<?=Input::e($u['login'])?></td>
    <td>
      <div class="role-tags">
        <span class="role-badge role-<?=Input::e($u['role'])?>"><?=Input::e($u['role_label'])?></span>
        <?php foreach($extraRoles as $er): ?>
          <span class="role-badge role-<?=Input::e($er['slug'])?>" style="opacity:.8"><?=Input::e($er['label'])?></span>
        <?php endforeach; ?>
      </div>
    </td>
    <td class="text-muted" style="font-size:12px"><?=Input::e($u['position_title']??'—')?></td>
    <td class="text-muted" style="font-size:12px"><?=Input::e($u['phone']??'—')?></td>
    <td>
      <div style="display:flex;gap:6px;align-items:center">
        <a href="/admin/users/<?=$u['id']?>/edit" class="btn btn-sm btn-ghost" style="padding:5px 10px">✎</a>
        <?php if(!$isSelf): ?>
          <button class="btn btn-sm <?=$u['is_active']?'btn-amber':'btn-success'?>" 
                  data-toggle-user="<?=$u['id']?>" data-active="<?=$u['is_active']?>"
                  style="padding:5px 10px" title="<?=$u['is_active']?'Деактивировать':'Активировать'?>">
            <?=$u['is_active']?'⏸':'▶'?>
          </button>
          <button class="btn btn-sm btn-danger" data-delete-user="<?=$u['id']?>" style="padding:5px 10px" title="Удалить">✕</button>
        <?php else: ?>
          <span style="font-size:11px;color:var(--text3);padding:0 4px">вы</span>
        <?php endif; ?>
      </div>
    </td>
  </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
<div id="no-users" style="display:none" class="empty-state"><div class="empty-icon">🔍</div><div class="empty-text">Пользователи не найдены</div></div>
</div>

<?php
use App\Core\Paginator;
if(($pages??1) > 1):
    $qp = array_filter(['search'=>$search??''], fn($v)=>$v!=='');
    echo Paginator::render($page??1, $pages??1, $qp);
endif;
?>
