<?php
use App\Core\Input;
use App\Core\Paginator;
$CAT=['bug'=>['label'=>'🐛 Ошибка','color'=>'var(--red)'],
      'suggestion'=>['label'=>'💡 Предложение','color'=>'var(--accent)'],
      'other'=>['label'=>'📝 Прочее','color'=>'var(--text3)']];
?>
<div class="page-header">
  <div>
    <div class="page-title">Обращения</div>
    <div class="page-subtitle">
      <?=$total?> всего
      <?php if($unread>0): ?>
        · <span style="color:var(--red);font-weight:600"><?=$unread?> непрочитанных</span>
      <?php endif; ?>
    </div>
  </div>
</div>

<div class="page-body">
<?php if(empty($items)): ?>
  <div class="empty-state"><div class="empty-icon">📬</div><div class="empty-text">Нет обращений</div></div>
<?php else: ?>
<div style="display:flex;flex-direction:column;gap:10px">
  <?php foreach($items as $fb):
    $cat=$CAT[$fb['category']]??$CAT['other'];
  ?>
  <div class="detail-card <?=$fb['is_read']?'':'fb-unread'?>"
       id="fb-<?=$fb['id']?>"
       style="<?=$fb['is_read']?'opacity:.7':'border-color:var(--accent);'?>">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px">
      <div style="flex:1;min-width:0">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;flex-wrap:wrap">
          <span style="font-size:12px;font-weight:600;color:<?=$cat['color']?>"><?=$cat['label']?></span>
          <span style="font-size:12px;color:var(--text3)">
            👤 <?=Input::e($fb['user_name'])?> · <?=Input::e(substr($fb['created_at'],0,16))?>
          </span>
          <?php if(!$fb['is_read']): ?>
            <span style="font-size:11px;background:rgba(240,107,107,.15);color:var(--red);padding:2px 8px;border-radius:20px;font-weight:600">Новое</span>
          <?php endif; ?>
        </div>
        <div style="font-size:14px;color:var(--text);line-height:1.7;white-space:pre-wrap;word-break:break-word"><?=Input::e($fb['message'])?></div>
      </div>
      <div style="display:flex;gap:6px;flex-shrink:0">
        <?php if(!$fb['is_read']): ?>
          <button class="btn btn-sm btn-ghost" onclick="fbRead(<?=$fb['id']?>)" title="Отметить прочитанным">✓ Прочитано</button>
        <?php endif; ?>
        <button class="btn btn-sm btn-danger" onclick="fbDel(<?=$fb['id']?>)" title="Удалить">✕</button>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<?php if($pages>1) echo Paginator::render($page,$pages,[]); ?>

<?php endif; ?>
</div>

<script>
function fbRead(id){
  var fd=new FormData();fd.append('_csrf',getCsrf());
  fetch('/admin/feedback/'+id+'/read',{method:'POST',body:fd})
    .then(r=>r.json()).then(j=>{
      if(j.ok){
        var el=document.getElementById('fb-'+id);
        if(el){el.style.opacity='.7';el.style.borderColor='';
          el.querySelector('button[onclick*="fbRead"]')?.remove();
          el.querySelector('span[style*="Новое"]')?.remove();}
        toast('Отмечено');
      }
    });
}
function fbDel(id){
  if(!confirm('Удалить обращение?'))return;
  var fd=new FormData();fd.append('_csrf',getCsrf());
  fetch('/admin/feedback/'+id+'/delete',{method:'POST',body:fd})
    .then(r=>r.json()).then(j=>{
      if(j.ok){document.getElementById('fb-'+id)?.remove();toast('Удалено');}
    });
}
</script>
