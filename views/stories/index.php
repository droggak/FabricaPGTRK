<?php
use App\Core\Input;
use App\Core\Paginator;
use App\Middleware\Auth;
use App\Models\StoryModel;

$SC=['запланировано'=>'s-planned','снято'=>'s-shot','на проверке'=>'s-review','проверено'=>'s-checked',
     'смонтировано'=>'s-edited','отсмотрено'=>'s-viewed','готово'=>'s-ready','вышло в эфир'=>'s-aired','отменено'=>'s-cancelled'];
$COLS=['accent'=>'#6c8bff','purple'=>'#a78bfa','green'=>'#3ecf8e','amber'=>'#f7b731','red'=>'#f06b6b','teal'=>'#2dd4bf'];
$PERIODS=['today'=>'Сегодня','3days'=>'3 дня','week'=>'Неделя','month'=>'Месяц','all'=>'Все','custom'=>'Период…'];
$canCreate=Auth::hasRole(['admin','coordinator','reporter','editor']);
$canDel=Auth::hasRole(['admin','coordinator','editor']);

function showBdgIdx($s,$C){
    if(!$s['show_id'])return'—';
    $c=$C[$s['show_color']??'accent']??'#6c8bff';
    return"<span class='show-badge' style='background:{$c}22;color:{$c}'>".Input::e($s['show_name'])."</span>";
}
function starsIdx($avg){
    if(!$avg)return'<span style="color:var(--text3)">—</span>';
    $h='<span class="stars-display">';
    for($i=1;$i<=5;$i++) $h.='<span class="star-icon '.($i<=round($avg)?'filled':'empty').'">★</span>';
    return $h.'</span>';
}
$periodLabel=$PERIODS[$period??'today']??'Сегодня';
?>
<div class="page-header">
  <div>
    <div class="page-title">Сюжеты</div>
    <div class="page-subtitle"><?=$total?> сюжетов · <?=$periodLabel?></div>
  </div>
  <?php if($canCreate): ?><a href="/stories/create" class="btn btn-accent btn-sm">+ Создать сюжет</a><?php endif; ?>
</div>

<div class="page-body">

<!-- Кнопки периода -->
<div style="display:flex;gap:6px;margin-bottom:12px;flex-wrap:wrap;align-items:center">
  <?php foreach($PERIODS as $key=>$label): ?>
    <a href="/stories?period=<?=$key?>&status=<?=urlencode($filters['status']??'')?>&show_id=<?=(int)($filters['show_id']??0)?>&search=<?=urlencode($filters['search']??'')?>"
       class="btn btn-sm <?=($period??'today')===$key?'btn-accent':'btn-ghost'?>">
      <?=$label?>
    </a>
  <?php endforeach; ?>
  <?php if(($period??'today')==='custom'): ?>
    <form method="GET" action="/stories" style="display:flex;gap:6px;align-items:center;margin-left:4px">
      <input type="hidden" name="period" value="custom">
      <input type="date" name="date_from" class="filter-select" value="<?=Input::e($dateFrom??'')?>" style="width:150px">
      <span style="color:var(--text3)">—</span>
      <input type="date" name="date_to" class="filter-select" value="<?=Input::e($dateTo??'')?>" style="width:150px">
      <button type="submit" class="btn btn-ghost btn-sm">Применить</button>
    </form>
  <?php endif; ?>
</div>

<!-- Поиск и фильтры -->
<form method="GET" action="/stories" class="table-toolbar" style="margin-bottom:16px">
  <input type="hidden" name="period" value="<?=Input::e($period??'today')?>">
  <?php if(($period??'today')==='custom'): ?>
    <input type="hidden" name="date_from" value="<?=Input::e($dateFrom??'')?>">
    <input type="hidden" name="date_to"   value="<?=Input::e($dateTo??'')?>">
  <?php endif; ?>
  <input class="search-input" type="text" name="search"
         placeholder="🔍 Поиск по названию..."
         value="<?=Input::e($filters['search']??'')?>">
  <select class="filter-select" name="status" onchange="this.form.submit()">
    <option value="">Все статусы</option>
    <?php foreach(StoryModel::STATUSES as $st): ?>
      <option value="<?=Input::e($st)?>" <?=($filters['status']??'')===$st?'selected':''?>><?=Input::e($st)?></option>
    <?php endforeach; ?>
  </select>
  <select class="filter-select" name="show_id" onchange="this.form.submit()">
    <option value="">Все передачи</option>
    <?php foreach($shows as $sh): ?>
      <option value="<?=$sh['id']?>" <?=($filters['show_id']??0)==$sh['id']?'selected':''?>><?=Input::e($sh['name'])?></option>
    <?php endforeach; ?>
  </select>
  <button type="submit" class="btn btn-ghost btn-sm">Найти</button>
  <a href="/stories" class="btn btn-ghost btn-sm">Сбросить</a>
</form>

<!-- Таблица -->
<div class="table-wrap">
  <table class="data-table">
    <thead>
      <tr>
        <th>Сюжет</th><th>Передача</th><th>Тип</th><th>Статус</th>
        <th>Важн.</th><th>Съёмка</th><th>Эфир</th><th>Автор</th><th>★</th>
        <th style="width:90px"></th>
      </tr>
    </thead>
    <tbody>
    <?php if(empty($stories)): ?>
      <tr><td colspan="10">
        <div class="empty-state" style="padding:40px">
          <div class="empty-icon">📭</div>
          <div class="empty-text">Нет сюжетов за выбранный период</div>
        </div>
      </td></tr>
    <?php else: foreach($stories as $s): ?>
      <tr>
        <!-- Все ячейки данных ведут на карточку сюжета -->
        <td onclick="location.href='/stories/<?=$s['id']?>'" style="cursor:pointer">
          <span class="cell-title"><?=Input::e($s['title'])?></span>
        </td>
        <td onclick="location.href='/stories/<?=$s['id']?>'" style="cursor:pointer"><?=showBdgIdx($s,$COLS)?></td>
        <td onclick="location.href='/stories/<?=$s['id']?>'" style="cursor:pointer;color:var(--text2);font-size:12px"><?=Input::e($s['material_type_name']??'—')?></td>
        <td onclick="location.href='/stories/<?=$s['id']?>'" style="cursor:pointer">
          <span class="status-badge <?=$SC[$s['status']]??''?>"><?=Input::e($s['status'])?></span>
        </td>
        <td onclick="location.href='/stories/<?=$s['id']?>'" style="cursor:pointer">
          <span class="bi bi<?=(int)$s['importance']?>"><?=(int)$s['importance']?></span>
        </td>
        <td onclick="location.href='/stories/<?=$s['id']?>'" style="cursor:pointer;color:var(--text2)"><?=Input::e($s['shoot_date']??'—')?></td>
        <td onclick="location.href='/stories/<?=$s['id']?>'" style="cursor:pointer;color:var(--text2)"><?=Input::e($s['air_date']??'—')?></td>
        <td onclick="location.href='/stories/<?=$s['id']?>'" style="cursor:pointer;color:var(--text2)"><?=Input::e($s['reporter_name']??$s['creator_name']??'—')?></td>
        <td onclick="location.href='/stories/<?=$s['id']?>'" style="cursor:pointer"><?=starsIdx($s['avg_rating']?(float)$s['avg_rating']:null)?></td>

        <!-- Последняя ячейка — кнопки, БЕЗ onclick на td -->
        <td>
          <div style="display:flex;gap:4px;align-items:center">
            <?php if($canCreate): ?>
              <a href="/stories/<?=$s['id']?>/edit"
                 onclick="event.stopPropagation()"
                 class="btn btn-sm btn-ghost" style="padding:5px 8px" title="Редактировать">✎</a>
            <?php endif; ?>
            <?php if($canDel): ?>
              <button class="btn btn-sm btn-danger" style="padding:5px 8px" title="Удалить"
                      onclick="event.stopPropagation();openDelModal(<?=$s['id']?>,<?=json_encode($s['title'])?>)">✕</button>
            <?php endif; ?>
          </div>
        </td>
      </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>

<!-- Пагинация -->
<?php if(($pages??1)>1):
  $qp=array_filter([
    'period'   =>$period??'today',
    'date_from'=>$dateFrom??'',
    'date_to'  =>$dateTo??'',
    'status'   =>$filters['status']??'',
    'show_id'  =>$filters['show_id']??0,
    'search'   =>$filters['search']??'',
  ],fn($v)=>$v!==''&&$v!==0&&$v!==null);
  echo Paginator::render($page??1,$pages??1,$qp);
endif; ?>

</div>

<!-- Глобальная модалка удаления сюжета -->
<div class="modal-overlay" id="del-story-modal">
  <div class="modal" style="max-width:400px">
    <div class="modal-header">
      <div class="modal-title" id="del-story-modal-title">Удалить сюжет?</div>
      <button class="modal-close" onclick="closeModal('del-story-modal')">✕</button>
    </div>
    <div class="modal-body">
      <p style="color:var(--text3);font-size:13px">Все версии текста и история будут удалены. Действие необратимо.</p>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('del-story-modal')">Отмена</button>
      <form method="POST" id="del-story-form" action="" style="margin:0">
        <?=\App\Core\Input::csrfField()?>
        <button type="submit" class="btn btn-danger">Удалить</button>
      </form>
    </div>
  </div>
</div>
<script>
function openDelModal(id,title){
  document.getElementById('del-story-modal-title').textContent='Удалить «'+title+'»?';
  document.getElementById('del-story-form').action='/stories/'+id+'/delete';
  document.getElementById('del-story-modal').classList.add('active');
}
</script>
