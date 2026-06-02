<?php use App\Core\Input;
$SC=['запланировано'=>'s-planned','снято'=>'s-shot',
     'на проверке (редактор)'=>'s-review','на проверке (гл.редактор)'=>'s-review2',
     'проверено'=>'s-checked','смонтировано'=>'s-edited','отсмотрено'=>'s-viewed',
     'готово'=>'s-ready','вышло в эфир'=>'s-aired','отменено'=>'s-cancelled'];
$COLS=['accent'=>'#6c8bff','purple'=>'#a78bfa','green'=>'#3ecf8e','amber'=>'#f7b731','red'=>'#f06b6b','teal'=>'#2dd4bf'];
$list=array_filter($stories,fn($s)=>!empty($s['air_date'])&&$s['status']!=='отменено');
usort($list,fn($a,$b)=>$b['importance']-$a['importance']);
function tS($t){if(!$t)return 0;$p=explode(':',$t);return count($p)===3?(int)$p[0]*3600+(int)$p[1]*60+(int)$p[2]:0;}
function fT($s){return sprintf('%d:%02d:%02d',floor($s/3600),floor(($s%3600)/60),$s%60);}
$tE=array_reduce($list,fn($a,$s)=>$a+tS($s['estimated_duration']??''),0);
$tD=array_reduce($list,fn($a,$s)=>$a+tS($s['duration']??''),0);
?>
<div class="page-header">
  <div><div class="page-title">План выпуска</div><div class="page-subtitle">Эфирное расписание</div></div>
  <form method="GET" style="display:flex;gap:8px">
    <input type="date" class="filter-select" name="date" value="<?=Input::e($date??'')?>">
    <select class="filter-select" name="show_id" onchange="this.form.submit()"><option value="">Все передачи</option><?php foreach($shows as $sh): ?><option value="<?=$sh['id']?>" <?=($showId??0)==$sh['id']?'selected':''?>><?=Input::e($sh['name'])?></option><?php endforeach; ?></select>
    <button type="submit" class="btn btn-ghost btn-sm">Применить</button>
    <a href="/air-plan" class="btn btn-ghost btn-sm">Сбросить</a>
  </form>
</div>
<div class="page-body">
<div style="display:flex;gap:14px;flex-wrap:wrap;margin-bottom:20px">
  <div class="dash-card" style="padding:14px 18px;flex:0 0 auto"><div class="dash-card-label">Сюжетов</div><div class="dash-card-value" style="font-size:24px"><?=count($list)?></div></div>
  <div class="dash-card" style="padding:14px 18px;flex:0 0 auto"><div class="dash-card-label">Хронометраж план</div><div class="dash-card-value mono" style="font-size:22px"><?=fT($tE)?></div></div>
  <div class="dash-card cg" style="padding:14px 18px;flex:0 0 auto"><div class="dash-card-label">Хронометраж факт</div><div class="dash-card-value mono" style="font-size:22px"><?=fT($tD)?></div></div>
</div>
<div style="display:grid;grid-template-columns:<?=!empty($noAirDate)?'2fr 1fr':'1fr'?>;gap:20px">
<div>
  <?php if(empty($list)): ?><div class="empty-state"><div class="empty-icon">📡</div><div class="empty-text">Нет сюжетов</div></div>
  <?php else: ?><div class="plan-grid">
  <?php foreach($list as $s): $c=$COLS[$s['show_color']??'accent']??'#6c8bff'; ?>
  <a href="/stories/<?=$s['id']?>" class="plan-card">
    <div class="plan-card-header"><div class="plan-card-title"><?=Input::e($s['title'])?></div><span class="status-badge <?=$SC[$s['status']]??''?>"><?=Input::e($s['status'])?></span></div>
    <?php if($s['show_id']): ?><div style="margin-bottom:6px"><span class="show-badge" style="background:<?=$c?>22;color:<?=$c?>">📺 <?=Input::e($s['show_name'])?></span></div><?php endif; ?>
    <div class="plan-card-meta">
      <span>📡 <?=Input::e($s['air_date'])?></span>
      <span>⏱ <?=Input::e($s['duration']??$s['estimated_duration']??'—')?></span>
      <span class="bi bi<?=(int)$s['importance']?>"><?=(int)$s['importance']?></span>
      <?php if($s['reporter_name']): ?><span>👤 <?=Input::e($s['reporter_name'])?></span><?php endif; ?>
    </div>
  </a>
  <?php endforeach; ?>
  </div><?php endif; ?>
</div>
<?php if(!empty($noAirDate)): ?>
<div>
  <div class="section-title" style="font-size:13px">📦 Без даты эфира</div>
  <div style="display:flex;flex-direction:column;gap:8px">
  <?php foreach($noAirDate as $s): ?>
  <a href="/stories/<?=$s['id']?>" class="task-item" style="text-decoration:none">
    <div class="task-dot" style="background:var(--text3)"></div>
    <div class="task-info"><div class="task-title"><?=Input::e($s['title'])?></div>
    <div class="task-meta"><span class="status-badge <?=$SC[$s['status']]??''?>"><?=Input::e($s['status'])?></span></div></div>
  </a>
  <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>
</div>
</div>
