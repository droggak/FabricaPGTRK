<?php use App\Core\Input;
// Роли для каждой позиции команды (точное соответствие)
$teamRoles = [
    'reporter'  => ['label'=>'Корреспондент',  'roles'=>['reporter']],
    'operator'  => ['label'=>'Оператор',        'roles'=>['operator']],
    'editor'    => ['label'=>'Редактор',         'roles'=>['editor','coordinator']],
    'montager'  => ['label'=>'Монтажёр',        'roles'=>['montager']],
    'driver'    => ['label'=>'Водитель',         'roles'=>['driver']],
    'voiceover' => ['label'=>'Озвучка',          'roles'=>['reporter','coordinator']],
];
?>
<div class="page-header">
  <div><div class="page-title">Новый сюжет</div></div>
  <a href="/stories" class="btn btn-ghost btn-sm">← Назад</a>
</div>
<div class="page-body">
<form method="POST" action="/stories/create" style="max-width:100%">
<?=Input::csrfField()?>
<div class="detail-card">
  <div class="detail-card-title">Основное</div>
  <div class="form-group"><label>Заголовок *</label><input type="text" name="title" required value="<?=Input::e($_POST['title']??'')?>"></div>
  <div class="form-group"><label>Подводка</label><textarea name="description" rows="3"><?=Input::e($_POST['description']??'')?></textarea></div>
  <div class="form-group"><label>Информационный повод</label><textarea name="info_reason" rows="2"><?=Input::e($_POST['info_reason']??'')?></textarea></div>
  <div class="form-row">
    <div class="form-group"><label>Передача</label>
      <select name="show_id"><option value="">— Не указана —</option>
      <?php foreach($shows as $sh): ?><option value="<?=$sh['id']?>" <?=($_POST['show_id']??'')==$sh['id']?'selected':''?>><?=Input::e($sh['name'])?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="form-group"><label>Тип материала</label>
      <select name="material_type_id"><option value="">— Не указан —</option>
      <?php foreach($types as $t): ?><option value="<?=$t['id']?>" <?=($_POST['material_type_id']??'')==$t['id']?'selected':''?>><?=Input::e($t['name'])?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="form-group" style="max-width:160px"><label>Важность</label>
      <select name="importance" style="width:100%"><?php for($i=1;$i<=5;$i++): ?><option value="<?=$i?>" <?=($_POST['importance']??3)==$i?'selected':''?>><?=$i?><?=$i==1?' — Низкая':($i==3?' — Средняя':($i==5?' — Высокая':''))?></option><?php endfor; ?></select>
    </div>
    <div class="form-group" style="max-width:160px"><label>Плановый хронометраж</label>
      <div class="time-picker" id="tp-est-c" style=""></div>
      <input type="hidden" name="estimated_duration" id="tp-est-c-h-inp" value="<?=Input::e($_POST['estimated_duration']??'00:03:00')?>">
    </div>
  </div>
</div>
<div class="detail-card">
  <div class="detail-card-title">Даты и место</div>
  <div class="form-row">
    <div class="form-group"><label>Дата съёмки</label><input type="date" name="shoot_date" value="<?=Input::e($_POST['shoot_date']??'')?>"></div>
    <div class="form-group"><label>Дата эфира</label><input type="date" name="air_date" value="<?=Input::e($_POST['air_date']??'')?>"></div>
    <div class="form-group form-full"><label>Место съёмки</label><input type="text" name="shoot_location" placeholder="Адрес или название студии" value="<?=Input::e($_POST['shoot_location']??'')?>"></div>
  </div>
</div>
<div class="detail-card">
  <div class="detail-card-title">Команда</div>
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
    <?php
    $teamRolesDef=[
      'reporter'  =>['label'=>'Корреспондент', 'roles'=>['reporter']],
      'operator'  =>['label'=>'Оператор',       'roles'=>['operator']],
      'editor'    =>['label'=>'Редактор',        'roles'=>['editor','coordinator']],
      'montager'  =>['label'=>'Монтажёр',       'roles'=>['montager']],
      'driver'    =>['label'=>'Водитель',        'roles'=>['driver']],
      'voiceover' =>['label'=>'Озвучка',         'roles'=>['reporter','coordinator']],
    ];
    foreach($teamRolesDef as $field=>$meta):
      $eli=array_values(array_filter($users,fn($u)=>in_array($u['role'],$meta['roles'],true)));
      $preselected=array_map('intval',(array)($_POST[$field.'_ids']??[]));
    ?>
    <div>
      <div style="font-size:11px;font-weight:600;color:var(--text3);text-transform:uppercase;letter-spacing:.08em;margin-bottom:8px"><?=$meta['label']?></div>
      <!-- Добавленные чипы -->
      <div id="chips-<?=$field?>" style="display:flex;flex-wrap:wrap;gap:6px;min-height:32px;margin-bottom:8px">
        <?php foreach($eli as $u): if(!in_array($u['id'],$preselected)) continue; ?>
          <span class="team-chip" data-id="<?=$u['id']?>" data-field="<?=$field?>"
                style="display:inline-flex;align-items:center;gap:6px;padding:4px 10px;
                       background:rgba(108,139,255,.15);border:1px solid var(--accent);
                       border-radius:20px;font-size:13px;color:var(--accent)">
            <?=Input::e($u['name'])?>
            <span onclick="removeChip(this)" style="cursor:pointer;font-size:15px;line-height:1;opacity:.7">×</span>
            <input type="hidden" name="<?=$field?>_ids[]" value="<?=$u['id']?>">
          </span>
        <?php endforeach; ?>
      </div>

      <!-- Поиск с выпадающим списком -->
      <?php if(!empty($eli)): ?>
      <div class="team-search-wrap" data-team-search data-team-select="<?=$field?>"
           data-options="<?=htmlspecialchars(json_encode(array_values(array_map(fn($u)=>['id'=>$u['id'],'name'=>$u['name']],$eli))),ENT_QUOTES)?>">
        <input type="text" class="team-search-input" placeholder="🔍 Поиск по имени...">
        <div class="team-search-dropdown"></div>
      </div>
      <?php else: ?>
        <div style="font-size:13px;color:var(--text3)">Нет сотрудников с этой ролью</div>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<script>
function initTeamSearch(pfx){
  document.querySelectorAll('[data-team-select]').forEach(function(wrap){
    var field=wrap.dataset.teamSelect;
    var inp=wrap.querySelector('.team-search-input');
    var dd=wrap.querySelector('.team-search-dropdown');
    var opts=JSON.parse(wrap.dataset.options||'[]');
    var chipsId=(pfx||'')+'chips-'+field;
    function showDD(q){
      q=(q||'').toLowerCase().trim();
      var f=q?opts.filter(o=>o.name.toLowerCase().includes(q)):opts.slice(0,20);
      if(!f.length){dd.style.display='none';return;}
      dd.innerHTML='';
      f.forEach(function(o){
        var li=document.createElement('div');li.className='team-dd-item';li.textContent=o.name;
        li.addEventListener('mousedown',function(e){
          e.preventDefault();
          addTeamChip(field,o.id,o.name,chipsId);
          inp.value='';dd.style.display='none';
        });
        dd.appendChild(li);
      });
      dd.style.display='block';
    }
    inp.addEventListener('input',function(){showDD(this.value);});
    inp.addEventListener('focus',function(){showDD(this.value);});
    inp.addEventListener('blur',function(){setTimeout(()=>dd.style.display='none',150);});
  });
}
function addTeamChip(field,id,name,chipsId){
  var el=document.getElementById(chipsId);
  if(!el)return;
  if(el.querySelector('[data-cid="'+id+'"]'))return;
  var chip=document.createElement('span');
  chip.dataset.cid=id;
  chip.style.cssText='display:inline-flex;align-items:center;gap:6px;padding:4px 10px;margin:2px;'
    +'background:rgba(108,139,255,.15);border:1px solid var(--accent);border-radius:20px;font-size:13px;color:var(--accent)';
  chip.innerHTML=name
    +'<span style="cursor:pointer;font-size:15px;line-height:1;opacity:.7" onclick="this.parentElement.remove()">×</span>'
    +'<input type="hidden" name="'+field+'_ids[]" value="'+id+'">';
  el.appendChild(chip);
}
document.addEventListener('DOMContentLoaded',function(){ initTeamSearch(); });
</script>
<style>
.team-search-wrap{position:relative;}
.team-search-input{width:100%;background:var(--bg3);border:1px solid var(--border);border-radius:8px;
  padding:8px 12px;color:var(--text);font-size:13px;outline:none;font-family:var(--font);}
.team-search-input:focus{border-color:var(--accent);}
.team-search-dropdown{position:absolute;top:100%;left:0;right:0;z-index:300;
  background:var(--bg2);border:1px solid var(--border);border-radius:8px;
  box-shadow:0 4px 16px rgba(0,0,0,.25);display:none;max-height:200px;overflow-y:auto;margin-top:2px;}
.team-dd-item{padding:9px 12px;font-size:13px;cursor:pointer;color:var(--text);}
.team-dd-item:hover{background:rgba(108,139,255,.1);color:var(--accent);}
</style>
<div class="detail-card">
  <div class="detail-card-title">Персоны (через запятую)</div>
  <div class="form-group">
    <input type="text" name="persons" id="persons-input" placeholder="Иванов Иван, Петрова Мария..." value="<?=Input::e($_POST['persons']??'')?>">
    <div style="font-size:12px;color:var(--text3);margin-top:4px">Имена всех респондентов, которые появляются в сюжете</div>
  </div>
</div>
<div class="btn-group"><button type="submit" class="btn btn-accent">Создать сюжет</button><a href="/stories" class="btn btn-ghost">Отмена</a></div>
</form>
</div>

<script>
(function(){
  function pad(n){return String(n).padStart(2,'0');}
  function makeFormTP(containerId, hiddenId, initVal){
    var el=document.getElementById(containerId);
    if(!el) return;
    var p=(initVal||'00:00:00').split(':');
    var hh=Math.min(23,parseInt(p[0])||0),mm=Math.min(59,parseInt(p[1])||0),ss=Math.min(59,parseInt(p[2])||0);
    function upd(){
      ['h','m','s'].forEach(function(seg){
        var inp=document.getElementById(containerId+'-'+seg);
        if(inp) inp.value=pad(seg==='h'?hh:seg==='m'?mm:ss);
      });
      var hidden=document.getElementById(hiddenId);
      if(hidden) hidden.value=pad(hh)+':'+pad(mm)+':'+pad(ss);
    }
    el.style.cssText='display:inline-flex;align-items:center;width:100%;'+
      'background:var(--bg3);border:1px solid var(--border);border-radius:6px;'+
      'padding:4px 8px;gap:0;font-family:var(--mono);box-sizing:border-box';
    ['h','m','s'].forEach(function(seg,i){
      if(i>0){
        var sep=document.createElement('span');
        sep.textContent=':';
        sep.style.cssText='font-size:13px;font-weight:600;color:var(--text3);padding:0 1px;flex-shrink:0;align-self:center';
        el.appendChild(sep);
      }
      var wrap=document.createElement('div');
      wrap.style.cssText='display:flex;flex-direction:column;align-items:center;flex:1;min-width:0';
      // Кнопка вверх
      var up=document.createElement('button');up.type='button';up.textContent='▴';
      up.style.cssText='width:100%;border:none;background:none;color:var(--text3);cursor:pointer;'+
        'font-size:9px;line-height:1;padding:1px 0;display:block';
      // Поле ввода — без стрелок браузера
      var inp=document.createElement('input');
      inp.type='text'; // text вместо number — нет нативных стрелок!
      inp.inputMode='numeric';inp.pattern='[0-9]*';
      inp.id=containerId+'-'+seg;inp.maxLength=2;
      inp.style.cssText='width:100%;border:none;background:none;color:var(--text);'+
        'font-family:var(--mono);font-size:13px;font-weight:600;text-align:center;'+
        'outline:none;padding:2px 0;line-height:1';
      // Кнопка вниз
      var dn=document.createElement('button');dn.type='button';dn.textContent='▾';
      dn.style.cssText=up.style.cssText;
      // Обработчики
      up.addEventListener('click',function(e){
        e.preventDefault();
        if(seg==='h')hh=(hh+1)%24;if(seg==='m')mm=(mm+1)%60;if(seg==='s')ss=(ss+1)%60;upd();
      });
      dn.addEventListener('click',function(e){
        e.preventDefault();
        if(seg==='h')hh=(hh+23)%24;if(seg==='m')mm=(mm+59)%60;if(seg==='s')ss=(ss+59)%60;upd();
      });
      inp.addEventListener('blur',function(){
        var v=Math.max(0,Math.min(parseInt(this.value)||0,seg==='h'?23:59));
        if(seg==='h')hh=v;if(seg==='m')mm=v;if(seg==='s')ss=v;upd();
      });
      inp.addEventListener('keydown',function(e){
        if(e.key==='ArrowUp'){e.preventDefault();up.click();}
        if(e.key==='ArrowDown'){e.preventDefault();dn.click();}
      });
      inp.addEventListener('wheel',function(e){
        e.preventDefault();
        if(seg==='h')hh=(hh+(e.deltaY<0?1:23))%24;
        if(seg==='m')mm=(mm+(e.deltaY<0?1:59))%60;
        if(seg==='s')ss=(ss+(e.deltaY<0?1:59))%60;upd();
      },{passive:false});
      [up,dn].forEach(function(b){
        b.addEventListener('mouseenter',function(){this.style.color='var(--accent)';});
        b.addEventListener('mouseleave',function(){this.style.color='var(--text3)';});
      });
      wrap.appendChild(up);wrap.appendChild(inp);wrap.appendChild(dn);
      el.appendChild(wrap);
    });
    upd();
  }
  document.addEventListener('DOMContentLoaded',function(){
    var hInp=document.getElementById('tp-est-c-h-inp');
    makeFormTP('tp-est-c','tp-est-c-h-inp',hInp?hInp.value:'00:03:00');
  });
})();
</script>
