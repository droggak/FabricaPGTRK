<?php
use App\Core\Input;
use App\Core\Paginator;

$CAT_ICONS=['auth'=>'🔐','story'=>'📰','user'=>'👤','admin'=>'⚙️','system'=>'🖥'];
$CAT_LABELS=['auth'=>'Авторизация','story'=>'Сюжеты','user'=>'Пользователи','admin'=>'Администрирование','system'=>'Система'];
$CAT_COLORS=['auth'=>'var(--accent)','story'=>'var(--green)','user'=>'var(--purple)','admin'=>'var(--amber)','system'=>'var(--text3)'];
?>
<style>
.log-table{width:100%;border-collapse:collapse;font-size:13px;}
.log-table th{text-align:left;padding:9px 12px;font-size:11px;font-weight:500;color:var(--text3);
  text-transform:uppercase;letter-spacing:.08em;border-bottom:2px solid var(--border);white-space:nowrap;}
.log-table td{padding:9px 12px;border-bottom:1px solid var(--border);vertical-align:top;}
.log-table tr:last-child td{border-bottom:none;}
.log-table tr:hover td{background:rgba(255,255,255,.02);}
.log-cat{display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:600;
  padding:2px 8px;border-radius:20px;white-space:nowrap;}
.log-desc{color:var(--text2);font-size:12px;margin-top:2px;}
.log-entity{font-size:12px;color:var(--text3);}
.log-ip{font-family:var(--mono);font-size:11px;color:var(--text3);}
</style>

<div class="page-header">
  <div>
    <div class="page-title">Системный журнал</div>
    <div class="page-subtitle"><?=$total?> записей</div>
  </div>
  <form method="POST" action="/admin/system-logs/cleanup" style="display:flex;gap:8px;align-items:center">
    <?=\App\Core\Input::csrfField()?>
    <select name="days" class="filter-select" style="width:140px">
      <option value="30">Старше 30 дней</option>
      <option value="60">Старше 60 дней</option>
      <option value="90">Старше 90 дней</option>
      <option value="180">Старше 180 дней</option>
      <option value="365">Старше года</option>
    </select>
    <button type="submit" class="btn btn-ghost btn-sm"
            onclick="return confirm('Удалить старые записи журнала?')">🗑 Очистить</button>
  </form>
</div>

<div class="page-body">

<!-- Фильтры -->
<form method="GET" action="/admin/system-logs" class="table-toolbar" style="margin-bottom:16px">
  <select class="filter-select" name="category" onchange="this.form.submit()">
    <option value="">Все категории</option>
    <?php foreach($CAT_LABELS as $k=>$v): ?>
      <option value="<?=$k?>" <?=($category??'')===$k?'selected':''?>><?=$CAT_ICONS[$k]?> <?=$v?></option>
    <?php endforeach; ?>
  </select>
  <input class="search-input" type="text" name="search"
         placeholder="🔍 Пользователь или действие..."
         value="<?=Input::e($search??'')?>" style="width:220px">
  <input type="date" class="filter-select" name="date_from" value="<?=Input::e($dateFrom??'')?>" style="width:140px">
  <span style="color:var(--text3)">—</span>
  <input type="date" class="filter-select" name="date_to"   value="<?=Input::e($dateTo??'')?>"   style="width:140px">
  <button type="submit" class="btn btn-ghost btn-sm">Применить</button>
  <a href="/admin/system-logs" class="btn btn-ghost btn-sm">Сбросить</a>
</form>

<?php if(empty($logs)): ?>
  <div class="empty-state"><div class="empty-icon">📋</div><div class="empty-text">Нет записей</div></div>
<?php else: ?>

<div class="table-wrap">
<table class="log-table">
  <thead>
    <tr>
      <th style="width:140px">Время</th>
      <th style="width:120px">Категория</th>
      <th>Действие / Описание</th>
      <th>Объект</th>
      <th>Пользователь</th>
      <th style="width:110px">IP</th>
    </tr>
  </thead>
  <tbody>
  <?php foreach($logs as $l):
    $cat  = $l['category']??'system';
    $icon = $CAT_ICONS[$cat]??'📋';
    $col  = $CAT_COLORS[$cat]??'var(--text3)';
  ?>
  <tr>
    <td class="log-ip" style="font-size:12px;white-space:nowrap;color:var(--text2)">
      <?=Input::e(substr($l['created_at'],0,16))?>
    </td>
    <td>
      <span class="log-cat" style="background:<?=$col?>22;color:<?=$col?>">
        <?=$icon?> <?=Input::e($CAT_LABELS[$cat]??$cat)?>
      </span>
    </td>
    <td>
      <div style="font-weight:500;color:var(--text)"><?=Input::e($l['action'])?></div>
      <?php if(!empty($l['description'])): ?>
        <div class="log-desc"><?=Input::e($l['description'])?></div>
      <?php endif; ?>
    </td>
    <td class="log-entity">
      <?php if(!empty($l['entity_type'])): ?>
        <span style="opacity:.6"><?=Input::e($l['entity_type'])?></span>
        <?php if($l['entity_id']): ?>
          #<?=$l['entity_id']?>
        <?php endif; ?>
        <?php if(!empty($l['entity_name'])): ?>
          <div style="color:var(--text2);font-size:12px;margin-top:2px"><?=Input::e($l['entity_name'])?></div>
        <?php endif; ?>
      <?php else: ?>—<?php endif; ?>
    </td>
    <td>
      <?php if(!empty($l['user_name'])): ?>
        <div style="font-weight:500;font-size:13px"><?=Input::e($l['user_name'])?></div>
        <div class="log-entity"><?=Input::e($l['user_role']??'')?></div>
      <?php else: ?>
        <span style="color:var(--text3)">—</span>
      <?php endif; ?>
    </td>
    <td class="log-ip"><?=Input::e($l['ip_address']??'—')?></td>
  </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>

<?php if($pages>1) echo Paginator::render($page,$pages,array_filter(['category'=>$category??'','search'=>$search??'','date_from'=>$dateFrom??'','date_to'=>$dateTo??''],fn($v)=>$v!=='')); ?>

<?php endif; ?>
</div>
