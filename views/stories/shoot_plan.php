<?php
use App\Core\Input;
$SC=['запланировано'=>'s-planned','снято'=>'s-shot','на проверке'=>'s-review','проверено'=>'s-checked',
     'смонтировано'=>'s-edited','отсмотрено'=>'s-viewed','готово'=>'s-ready','вышло в эфир'=>'s-aired','отменено'=>'s-cancelled'];
$COLS=['accent'=>'#6c8bff','purple'=>'#a78bfa','green'=>'#3ecf8e','amber'=>'#f7b731','red'=>'#f06b6b','teal'=>'#2dd4bf'];

// Разбиваем на 2 группы
$withDate=[];$noDate=[];
foreach($stories as $s){
    if(!empty($s['shoot_date'])) $withDate[]=$s;
    else $noDate[]=$s;
}
?>
<style>
.shoot-table{width:100%;border-collapse:collapse;font-size:13px;}
.shoot-table th{text-align:left;padding:10px 14px;font-size:11px;font-weight:500;color:var(--text3);
  text-transform:uppercase;letter-spacing:.08em;border-bottom:1px solid var(--border);white-space:nowrap;}
.shoot-table td{padding:11px 14px;border-bottom:1px solid var(--border);color:var(--text);vertical-align:middle;}
.shoot-table tr:last-child td{border-bottom:none;}
.shoot-table tbody tr:hover td{background:rgba(255,255,255,.02);cursor:pointer;}
.shoot-date-group{background:var(--bg3);border-left:3px solid var(--accent);}
.shoot-date-group td{padding:8px 14px;font-size:12px;font-weight:600;color:var(--accent);font-family:var(--mono);}
</style>

<div class="page-header">
  <div><div class="page-title">План съёмок</div>
  <div class="page-subtitle">Всего: <?=count($stories)?> сюжетов</div></div>
  <form method="GET" action="/shoot-plan" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
    <input type="date" class="filter-select" name="date" value="<?=Input::e($date??'')?>">
    <select class="filter-select" name="show_id" onchange="this.form.submit()">
      <option value="">Все передачи</option>
      <?php foreach($shows as $sh): ?>
        <option value="<?=$sh['id']?>" <?=($showId??0)==$sh['id']?'selected':''?>><?=Input::e($sh['name'])?></option>
      <?php endforeach; ?>
    </select>
    <input type="text" class="search-input" name="search" placeholder="🔍 Поиск..." value="<?=Input::e($_GET['search']??'')?>" style="width:180px">
    <button type="submit" class="btn btn-ghost btn-sm">Применить</button>
    <a href="/shoot-plan" class="btn btn-ghost btn-sm">Сбросить</a>
  </form>
</div>

<div class="page-body">

<?php if(empty($stories)): ?>
  <div class="empty-state"><div class="empty-icon">📅</div><div class="empty-text">Нет сюжетов для съёмки</div></div>
<?php else: ?>

<!-- С датой съёмки -->
<?php if(!empty($withDate)): ?>
<div class="section-title">📅 Запланированные съёмки (<?=count($withDate)?>)</div>
<div class="table-wrap" style="margin-bottom:20px">
<table class="shoot-table">
  <thead><tr>
    <th style="width:36px">#</th>
    <th>Сюжет</th>
    <th>Статус</th>
    <th>Дата съёмки</th>
    <th>Место</th>
    <th>Корреспондент</th>
    <th>Оператор</th>
    <th>Передача</th>
    <th>Важн.</th>
  </tr></thead>
  <tbody>
  <?php
  $prevDate='';$num=1;
  foreach($withDate as $s):
    $c=$COLS[$s['show_color']??'accent']??'#6c8bff';
    // Разделитель по дате
    if($s['shoot_date']!==$prevDate):
      $prevDate=$s['shoot_date'];
  ?>
  <tr class="shoot-date-group">
    <td colspan="9">📅 <?=Input::e($s['shoot_date'])?></td>
  </tr>
  <?php endif; ?>
  <tr onclick="location.href='/stories/<?=$s['id']?>'">
    <td class="text-dim"><?=$num++?></td>
    <td><span style="font-weight:500"><?=Input::e($s['title'])?></span></td>
    <td><span class="status-badge <?=$SC[$s['status']]??''?>"><?=Input::e($s['status'])?></span></td>
    <td class="mono text-muted" style="white-space:nowrap"><?=Input::e($s['shoot_date']??'—')?></td>
    <td class="text-muted" style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?=Input::e($s['shoot_location']??'—')?></td>
    <td class="text-muted"><?php
      $m=array_merge($s['team_by_role']['reporter']??[],$s['reporter_name']&&!in_array($s['reporter_name'],$s['team_by_role']['reporter']??[])?[$s['reporter_name']]:[]);
      echo Input::e($m?implode(', ',$m):'—');
    ?></td>
    <td class="text-muted"><?php
      $m=array_merge($s['team_by_role']['operator']??[],$s['operator_name']&&!in_array($s['operator_name'],$s['team_by_role']['operator']??[])?[$s['operator_name']]:[]);
      echo Input::e($m?implode(', ',$m):'—');
    ?></td>
    <td><?=$s['show_id']?"<span class='show-badge' style='background:{$c}22;color:{$c}'>".Input::e($s['show_name'])."</span>":'—'?></td>
    <td><span class="bi bi<?=(int)$s['importance']?>"><?=(int)$s['importance']?></span></td>
  </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
<?php endif; ?>

<!-- Без даты -->
<?php if(!empty($noDate)): ?>
<div class="section-title">📭 Без даты съёмки (<?=count($noDate)?>)</div>
<div class="table-wrap">
<table class="shoot-table">
  <thead><tr>
    <th style="width:36px">#</th>
    <th>Сюжет</th>
    <th>Статус</th>
    <th>Передача</th>
    <th>Корреспондент</th>
    <th>Дата эфира</th>
    <th>Важн.</th>
  </tr></thead>
  <tbody>
  <?php $num=1;foreach($noDate as $s): $c=$COLS[$s['show_color']??'accent']??'#6c8bff'; ?>
  <tr onclick="location.href='/stories/<?=$s['id']?>'">
    <td class="text-dim"><?=$num++?></td>
    <td><span style="font-weight:500"><?=Input::e($s['title'])?></span></td>
    <td><span class="status-badge <?=$SC[$s['status']]??''?>"><?=Input::e($s['status'])?></span></td>
    <td><?=$s['show_id']?"<span class='show-badge' style='background:{$c}22;color:{$c}'>".Input::e($s['show_name'])."</span>":'—'?></td>
    <td class="text-muted"><?php
      $m=array_merge($s['team_by_role']['reporter']??[],$s['reporter_name']&&!in_array($s['reporter_name'],$s['team_by_role']['reporter']??[])?[$s['reporter_name']]:[]);
      echo Input::e($m?implode(', ',$m):'—');
    ?></td>
    <td class="text-muted mono"><?=Input::e($s['air_date']??'—')?></td>
    <td><span class="bi bi<?=(int)$s['importance']?>"><?=(int)$s['importance']?></span></td>
  </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
<?php endif; ?>

<?php endif; ?>
</div>
