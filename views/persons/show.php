<?php use App\Core\Input;
$SC=['запланировано'=>'s-planned','снято'=>'s-shot','на проверке'=>'s-review','проверено'=>'s-checked','смонтировано'=>'s-edited','отсмотрено'=>'s-viewed','готово'=>'s-ready','вышло в эфир'=>'s-aired','отменено'=>'s-cancelled'];
$COLS=['accent'=>'#6c8bff','purple'=>'#a78bfa','green'=>'#3ecf8e','amber'=>'#f7b731','red'=>'#f06b6b','teal'=>'#2dd4bf'];
?>
<div class="page-header">
  <div><div class="page-title">🧑 <?=Input::e($person['name'])?></div>
  <div class="page-subtitle">Сюжеты с упоминанием этой персоны</div></div>
  <a href="/persons" class="btn btn-ghost btn-sm">← Все персоны</a>
</div>
<div class="page-body">
<?php if(empty($stories)): ?>
<div class="empty-state"><div class="empty-icon">📭</div><div class="empty-text">Сюжетов не найдено</div></div>
<?php else: ?>
<div class="table-wrap"><table class="data-table"><thead><tr><th>Сюжет</th><th>Передача</th><th>Статус</th><th>Дата эфира</th><th>Корреспондент</th></tr></thead><tbody>
<?php foreach($stories as $s): $c=$COLS[$s['show_color']??'accent']??'#6c8bff'; ?>
<tr onclick="location.href='/stories/<?=$s['id']?>'">
  <td><span class="cell-title"><?=Input::e($s['title'])?></span></td>
  <td><?=$s['show_id']?"<span class='show-badge' style='background:{$c}22;color:{$c}'>📺 ".Input::e($s['show_name']).'</span>':'—'?></td>
  <td><span class="status-badge <?=$SC[$s['status']]??''?>"><?=Input::e($s['status'])?></span></td>
  <td class="text-muted"><?=Input::e($s['air_date']??'—')?></td>
  <td class="text-muted"><?=Input::e($s['reporter_name']??'—')?></td>
</tr>
<?php endforeach; ?>
</tbody></table></div>
<?php endif; ?>
</div>
