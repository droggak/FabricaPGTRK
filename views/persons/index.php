<?php use App\Core\Input; ?>
<div class="page-header">
  <div><div class="page-title">Персоналии</div><div class="page-subtitle">Поиск сюжетов по респондентам</div></div>
</div>
<div class="page-body">
<?php if(empty($persons)): ?>
<div class="empty-state"><div class="empty-icon">🧑</div><div class="empty-text">Персоны ещё не добавлены</div></div>
<?php else: ?>
<div class="table-toolbar" style="margin-bottom:16px">
  <input class="search-input" type="text" id="persons-filter" placeholder="🔍 Фильтр по имени..." oninput="filterPersons(this.value)">
</div>
<div class="admin-cards" id="persons-grid">
  <?php foreach($persons as $p): ?>
  <a href="/persons/<?=$p['id']?>" class="admin-card" style="text-decoration:none;display:block" data-name="<?=strtolower(Input::e($p['name']))?>">
    <div class="admin-card-header">
      <div class="avatar-big" style="background:rgba(62,207,142,.15);color:var(--green);font-size:20px">🧑</div>
      <div><div class="admin-card-name"><?=Input::e($p['name'])?></div>
      <div class="text-dim" style="font-size:12px">Сюжетов: <?=(int)$p['story_count']?></div></div>
    </div>
  </a>
  <?php endforeach; ?>
</div>
<?php endif; ?>
</div>
<script>
function filterPersons(q){
  document.querySelectorAll('#persons-grid [data-name]').forEach(el=>{
    el.style.display=el.dataset.name.includes(q.toLowerCase())?'':'none';
  });
}
</script>
