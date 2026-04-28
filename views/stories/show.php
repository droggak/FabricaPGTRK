<?php
use App\Core\Input;use App\Middleware\Auth;use App\Models\StoryModel;

// Рендеринг простого markdown: **bold**, _italic_, [u]underline[/u], [синхрон]
function renderTextMarkdown(string $t): string {
    $t = htmlspecialchars($t, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8');
    $t = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $t);
    $t = preg_replace('/_(.+?)_/s', '<em>$1</em>', $t);
    $t = preg_replace('/\[u\](.+?)\[\/u\]/s', '<u>$1</u>', $t);
    $t = preg_replace('/\[синхрон\]/', '<span style="background:rgba(247,183,49,.15);color:var(--amber);padding:1px 6px;border-radius:4px;font-style:italic;font-size:.9em">[синхрон]</span>', $t);
    return nl2br($t);
}
$SC=['запланировано'=>'s-planned','снято'=>'s-shot','на проверке'=>'s-review','проверено'=>'s-checked','смонтировано'=>'s-edited','отсмотрено'=>'s-viewed','готово'=>'s-ready','вышло в эфир'=>'s-aired','отменено'=>'s-cancelled'];
$COLS=['accent'=>'#6c8bff','purple'=>'#a78bfa','green'=>'#3ecf8e','amber'=>'#f7b731','red'=>'#f06b6b','teal'=>'#2dd4bf'];
$canSt=Auth::hasRole(['coordinator','reporter','editor','release','admin']);
$canAs=Auth::hasRole(['coordinator','editor','admin']);
$canEd=Auth::hasRole(['coordinator','reporter','editor','admin']);
$flow=array_values(array_filter(StoryModel::STATUSES,fn($s)=>$s!=='отменено'));
$curIdx=array_search($story['status'],array_values($flow));
$myR=0;foreach($story['ratings'] as $r){if($r['user_id']==$user['id']){$myR=(int)$r['rating'];break;}}
function chrF($t){$v=preg_match_all('/[аеёиоуыэюяaeiou]/iu',$t);$s=(int)round($v*0.4);return sprintf('%d:%02d:%02d',floor($s/3600),floor(($s%3600)/60),$s%60);}
function shB($s,$C){if(!$s['show_id'])return'<span class="text-dim">Не указана</span>';$c=$C[$s['show_color']??'accent']??'#a78bfa';return"<span class='show-badge' style='background:{$c}22;color:{$c}'>📺 ".Input::e($s['show_name']).'</span>';}
function starsH($avg){if(!$avg)return'';$h='<span class="stars-display">';for($i=1;$i<=5;$i++)$h.='<span class="star-icon '.($i<=round($avg)?'filled':'empty').'">★</span>';return $h.'</span>';}
function tmR($story,$users,$key,$label,$canAs,$teamMembers=[]){
    // Основной назначенный (из stories.reporter_id и т.д.)
    $primaryId  = $story[$key.'_id'] ?? null;
    $primaryName= $story[$key.'_name'] ?? null;
    // Все назначенные из story_team
    $allMembers = $teamMembers[$key] ?? [];
    // Объединяем: основной + дополнительные (без дублей)
    $shown = [];
    if($primaryId){
        $shown[$primaryId] = ['id'=>$primaryId,'name'=>$primaryName??''];
    }
    foreach($allMembers as $m){
        if(!isset($shown[$m['id']])) $shown[$m['id']] = $m;
    }

    // Select для назначения
    $roleMap=['reporter'=>['reporter'],'operator'=>['operator'],'editor'=>['editor','coordinator'],
              'montager'=>['montager'],'driver'=>['driver'],'voiceover'=>['reporter','coordinator']];
    $eli=array_filter($users,fn($u)=>in_array($u['role'],$roleMap[$key]??[$key],true));

    $selHtml='';
    if($canAs){
        $opts='<option value="">— Не назначен —</option>';
        foreach($eli as $u){
            $sel=($u['id']==$primaryId)?'selected':'';
            $opts.='<option value="'.$u['id'].'" '.$sel.'>'.Input::e($u['name']).'</option>';
        }
        $selHtml='<select data-assign-select data-story-id="'.$story['id'].'" data-role="'.$key.'"'
             .' style="width:100%;margin-top:8px;background:var(--bg2);border:1px solid var(--border);'
             .'border-radius:6px;padding:8px 10px;color:var(--text);font-size:13px;outline:none;font-family:var(--font)">'
             .$opts.'</select>';
    }

    // Аватарки
    $avatarsHtml='';
    if(empty($shown)){
        $avatarsHtml='<span style="font-size:13px;color:var(--text3)">Не назначен</span>';
    } else {
        foreach($shown as $m){
            $ini=mb_strtoupper(mb_substr($m['name'],0,1,'UTF-8'),'UTF-8');
            $avatarsHtml.='<div style="display:flex;align-items:center;gap:6px;margin-bottom:4px">'
                .'<div style="width:26px;height:26px;border-radius:50%;background:rgba(108,139,255,.2);'
                .'color:var(--accent);display:flex;align-items:center;justify-content:center;'
                .'font-size:11px;font-weight:700;flex-shrink:0">'.$ini.'</div>'
                .'<span style="font-size:13px;font-weight:500">'.Input::e($m['name']).'</span>'
                .'</div>';
        }
    }

    return '<div style="background:var(--bg3);border:1px solid var(--border);border-radius:8px;padding:12px">'
        .'<div style="font-size:11px;color:var(--text3);text-transform:uppercase;letter-spacing:.08em;margin-bottom:8px">'.$label.'</div>'
        .$avatarsHtml
        .$selHtml
        .'</div>';
}

?>
<div class="page-header">
  <div>
    <div class="page-title"><?=Input::e($story['title'])?></div>
    <div class="page-subtitle"><?=shB($story,$COLS)?> &nbsp; <span class="status-badge <?=$SC[$story['status']]??''?>"><?=Input::e($story['status'])?></span></div>
  </div>
  <div style="display:flex;gap:8px;flex-wrap:wrap">
    <?php if(Auth::hasRole(['admin','coordinator','reporter','editor'])): ?>
      <a href="/stories/<?=$story['id']?>/edit" class="btn btn-ghost btn-sm">✎ Редактировать</a>
    <?php endif; ?>
    <?php if(Auth::hasRole(['admin','coordinator','editor'])||($story['created_by']==$user['id'])): ?>
      <button class="btn btn-danger btn-sm"
              onclick="document.getElementById('del-confirm-overlay').classList.add('active')">
        ✕ Удалить
      </button>
      <!-- Встроенная модалка удаления -->
      <div class="modal-overlay" id="del-confirm-overlay">
        <div class="modal" style="max-width:400px">
          <div class="modal-header">
            <div class="modal-title">Удалить сюжет?</div>
            <button class="modal-close" onclick="document.getElementById('del-confirm-overlay').classList.remove('active')">✕</button>
          </div>
          <div class="modal-body">
            <p style="color:var(--text2)">«<?=Input::e($story['title'])?>»</p>
            <p style="color:var(--text3);font-size:13px">Все версии текста и история будут удалены. Действие необратимо.</p>
          </div>
          <div class="modal-footer">
            <button class="btn btn-ghost" onclick="document.getElementById('del-confirm-overlay').classList.remove('active')">Отмена</button>
            <form method="POST" action="/stories/<?=$story['id']?>/delete" style="margin:0">
              <?=\App\Core\Input::csrfField()?>
              <button type="submit" class="btn btn-danger">Удалить</button>
            </form>
          </div>
        </div>
      </div>
    <?php endif; ?>
    <a href="/stories" class="btn btn-ghost btn-sm">← Назад</a>
  </div>
</div>
<div class="page-body">
<div data-tabs>
<div class="tabs" id="detail-tabs">
  <button class="tab-btn active">Основное</button>
  <button class="tab-btn">Текст / Версии (<?=count($story['versions'])?>)</button>
  <button class="tab-btn">История (<?=count($story['logs'])?>)</button>
</div>

<!-- TAB 0 -->
<div class="tab-pane active">
<div class="detail-grid">
<div>
  <div class="detail-card">
    <div class="detail-card-title">Прогресс</div>
    <div class="status-flow"><?php $i=0;foreach($flow as $st): ?><span class="status-step <?=$i<$curIdx?'done':($i==$curIdx?'current':'')  ?>"><?=Input::e($st)?></span><?php if($i<count($flow)-1): ?><span class="status-arrow">→</span><?php endif;$i++; endforeach; ?></div>
    <?php if($canSt&&$story['status']!=='отменено'): ?>
    <div class="btn-group" style="margin-top:12px">
      <?php foreach(StoryModel::FLOW[$story['status']]??[] as $ns): $cls=$ns==='отменено'?'btn-danger':'btn-success'; ?>
      <button class="btn btn-sm <?=$cls?>" data-change-status data-story-id="<?=$story['id']?>" data-status="<?=Input::e($ns)?>"><?=$ns==='отменено'?'✕':''?> <?=Input::e($ns)?></button>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

  <div class="detail-card">
    <div class="detail-card-title">Подводка</div>
    <div style="font-size:14px;color:var(--text2);line-height:1.7"><?=$story['description']?nl2br(Input::e($story['description'])):'<i class="text-dim">Нет подводки</i>'?></div>
    <?php if($story['description']): ?><div style="font-size:11px;color:var(--text3);margin-top:6px">Хронометраж подводки: <?=chrF($story['description'])?></div><?php endif; ?>
  </div>

  <?php if($story['info_reason']): ?>
  <div class="detail-card">
    <div class="detail-card-title">Информационный повод</div>
    <div style="font-size:14px;color:var(--text2);line-height:1.7"><?=nl2br(Input::e($story['info_reason']))?></div>
  </div>
  <?php endif; ?>

  <div class="detail-card">
    <div class="detail-card-title">Хронометраж</div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
      <div class="detail-field"><div class="detail-label">Планируемый</div><div class="detail-value mono"><?=Input::e($story['estimated_duration']??'—')?></div></div>
      <div class="detail-field"><div class="detail-label">Фактический</div><div class="detail-value mono"><?=Input::e($story['duration']??'—')?></div></div>
    </div>
    <?php if($canSt): ?>
    <div style="margin-top:12px"><div class="detail-label" style="margin-bottom:6px">Установить фактический</div>
    <div style="display:flex;gap:8px">
      <input type="text" id="dur-input-<?=$story['id']?>" value="<?=Input::e($story['duration']??'')?>" placeholder="00:02:30" style="background:var(--bg2);border:1px solid var(--border);border-radius:6px;padding:7px 10px;color:var(--text);font-family:var(--mono);font-size:13px;outline:none;width:130px">
      <button class="btn btn-sm btn-ghost" data-save-duration data-story-id="<?=$story['id']?>">Сохранить</button>
    </div></div>
    <?php endif; ?>
  </div>

  <?php if(!empty($story['persons'])): ?>
  <div class="detail-card">
    <div class="detail-card-title">Персоны</div>
    <div style="display:flex;flex-wrap:wrap;gap:6px">
      <?php foreach($story['persons'] as $pr): ?>
      <a href="/persons/<?=$pr['id']?>" class="show-badge" style="background:rgba(62,207,142,.1);color:var(--green)">🧑 <?=Input::e($pr['name'])?></a>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>
</div>

<div>
  <div class="detail-card">
    <div class="detail-card-title">Передача и тип</div>
    <div class="detail-field"><div class="detail-label">Передача</div><?=shB($story,$COLS)?></div>
    <div class="detail-field"><div class="detail-label">Тип материала</div><div class="detail-value"><?=Input::e($story['material_type_name']??'—')?></div></div>
  </div>
  <div class="detail-card">
    <div class="detail-card-title">Даты и место</div>
    <div class="detail-field"><div class="detail-label">Дата съёмки</div><div class="detail-value"><?=Input::e($story['shoot_date']??'—')?></div></div>
    <?php if($story['shoot_location']): ?><div class="detail-field"><div class="detail-label">Место съёмки</div><div class="detail-value" style="font-size:13px"><?=Input::e($story['shoot_location'])?></div></div><?php endif; ?>
    <div class="detail-field"><div class="detail-label">Дата эфира</div><div class="detail-value"><?=Input::e($story['air_date']??'—')?></div></div>
    <div class="detail-field"><div class="detail-label">Важность</div><div class="detail-value"><span class="bi bi<?=(int)$story['importance']?>"><?=(int)$story['importance']?></span> <?=str_repeat('★',(int)$story['importance'])?></div></div>
  </div>
  <div class="detail-card">
    <div class="detail-card-title">Команда</div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;align-items:start">
      <?=tmR($story,$users,'reporter','Корреспондент',$canAs,$teamMembers??[])?>
      <?=tmR($story,$users,'operator','Оператор',$canAs,$teamMembers??[])?>
      <?=tmR($story,$users,'editor','Редактор',$canAs,$teamMembers??[])?>
      <?=tmR($story,$users,'montager','Монтажёр',$canAs,$teamMembers??[])?>
      <?=tmR($story,$users,'driver','Водитель',$canAs,$teamMembers??[])?>
      <?=tmR($story,$users,'voiceover','Озвучка',$canAs,$teamMembers??[])?>
    </div>
  </div>
  <div class="detail-card">
    <div class="detail-card-title">Рейтинг</div>
    <div style="font-size:26px;font-family:var(--mono);font-weight:600;color:var(--amber)"><span id="avg-rating-<?=$story['id']?>"><?=$story['avg_rating']?number_format((float)$story['avg_rating'],1):'—'?></span></div>
    <?=starsH($story['avg_rating']?(float)$story['avg_rating']:null)?>
    <div style="font-size:11px;color:var(--text3);margin-top:4px"><?=(int)$story['rating_count']?> оценок</div>
    <div style="margin-top:14px"><div class="detail-label" style="margin-bottom:8px">Ваша оценка</div>
    <div class="stars-input" data-story-stars data-story-id="<?=$story['id']?>" data-current="<?=$myR?>">
      <?php for($i=1;$i<=5;$i++): ?><span class="star-icon <?=$i<=$myR?'filled':'empty'?>">★</span><?php endfor; ?>
    </div></div>
  </div>
</div>
</div>
</div><!-- tab 0 -->

<!-- TAB 1 -->
<div class="tab-pane">
<?php if($canEd): ?>
<div class="detail-card" id="editor-card-<?=$story['id']?>">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
    <div class="detail-card-title" style="margin-bottom:0">Добавить версию текста</div>
    <span id="unsaved-warn-<?=$story['id']?>" style="display:none;font-size:12px;color:var(--amber)">⚠ Несохранённые изменения</span>
  </div>

  <!-- Тулбар -->
  <div class="rich-toolbar" id="rtoolbar-<?=$story['id']?>">
    <button type="button" class="rt-btn" data-cmd="bold"          title="Жирный"><b>Ж</b></button>
    <button type="button" class="rt-btn" data-cmd="italic"        title="Курсив"><i>К</i></button>
    <button type="button" class="rt-btn" data-cmd="underline"     title="Подчёркнутый"><u>П</u></button>
    <button type="button" class="rt-btn" data-cmd="strikeThrough" title="Зачёркнутый"><s>З</s></button>
    <span class="rt-sep"></span>
    <select class="rt-sel" title="Размер шрифта" id="rt-sz-<?=$story['id']?>"
            onchange="rtApplyFontSize(this,<?=$story['id']?>)">
      <option value="">Размер</option>
      <option value="12px">Мелкий</option>
      <option value="15px">Обычный</option>
      <option value="18px">Крупный</option>
      <option value="24px">Заголовок</option>
    </select>
    <span class="rt-sep"></span>
    <button type="button" class="rt-btn" data-cmd="insertUnorderedList" title="Маркированный список">≡</button>
    <button type="button" class="rt-btn" data-cmd="insertOrderedList"   title="Нумерованный список">1.</button>
    <span class="rt-sep"></span>
    <button type="button" class="rt-btn rt-sync-btn" title="Метка синхрона">[синхрон]</button>
    <button type="button" class="rt-btn" data-cmd="removeFormat" title="Снять форматирование" style="color:var(--red)">✕Ф</button>
    <button type="button" class="rt-btn" onclick="rtClear(<?=$story['id']?>)" title="Очистить" style="color:var(--text3)">🗑</button>
  </div>

  <!-- Редактор -->
  <div id="rich-editor-<?=$story['id']?>"
       class="rich-editor-div"
       contenteditable="true"
       data-story="<?=$story['id']?>"
       data-placeholder="Введите текст сюжета..."></div>

  <div style="margin-top:8px;display:flex;justify-content:space-between;align-items:center">
    <span id="chrono-live-<?=$story['id']?>" class="text-dim" style="font-size:12px"></span>
    <button class="btn btn-sm btn-accent" id="save-ver-btn-<?=$story['id']?>"
            onclick="rtSave(<?=$story['id']?>)">Сохранить версию</button>
  </div>
</div>

<style>
.rich-toolbar{display:flex;flex-wrap:wrap;align-items:center;gap:4px;padding:8px 10px;
  background:var(--bg2);border:1px solid var(--border);border-bottom:none;border-radius:8px 8px 0 0;}
.rt-btn{background:var(--bg3);border:1px solid var(--border);border-radius:5px;padding:4px 10px;
  color:var(--text2);cursor:pointer;font-size:13px;min-width:32px;transition:all .15s;font-family:var(--font);}
.rt-btn:hover,.rt-btn.active{background:rgba(108,139,255,.15);border-color:var(--accent);color:var(--accent);}
.rt-sel{background:var(--bg3);border:1px solid var(--border);border-radius:5px;
  padding:4px 8px;color:var(--text2);font-size:12px;outline:none;cursor:pointer;}
.rt-sep{width:1px;height:20px;background:var(--border);margin:0 2px;}
.rich-editor-div{
  min-height:240px;max-height:600px;overflow-y:auto;
  padding:16px;background:var(--bg3);
  border:1px solid var(--border);border-radius:0 0 8px 8px;
  color:var(--text);font-size:15px;line-height:1.85;
  outline:none;word-break:break-word;white-space:pre-wrap;}
.rich-editor-div:empty:before{content:attr(data-placeholder);color:var(--text3);pointer-events:none;}
.rich-editor-div:focus{border-color:var(--accent);}
.rich-editor-div ul{padding-left:1.4em;list-style-position:outside;margin:4px 0;}
.rich-editor-div ol{padding-left:1.6em;list-style-position:outside;margin:4px 0;}
.rich-editor-div li{margin-bottom:2px;}
.sync-lbl{background:rgba(247,183,49,.2);color:var(--amber);padding:1px 6px;border-radius:4px;font-style:italic;font-size:.9em;}
</style>

<script>
(function(){
  var sid    = <?=$story['id']?>;
  var editor = document.getElementById('rich-editor-'+sid);
  var chrono = document.getElementById('chrono-live-'+sid);
  var warnEl = document.getElementById('unsaved-warn-'+sid);
  var isDirty= false;

  /* ── Защита от случайного закрытия ─────────────────── */
  function setDirty(){
    isDirty=true;
    if(warnEl) warnEl.style.display='inline';
  }
  function setClean(){
    isDirty=false;
    if(warnEl) warnEl.style.display='none';
  }
  window.addEventListener('beforeunload',function(e){
    if(isDirty){
      e.preventDefault();
      e.returnValue='У вас есть несохранённый текст. Покинуть страницу?';
    }
  });

  /* ── Размер шрифта — только для нового текста ────────
     Принцип: вставляем «курсор-якорь» span с нужным размером,
     браузер продолжает печатать внутри него.
     Если есть выделение — оборачиваем выделенный текст.    */
  window.rtApplyFontSize = function(sel, id){
    var sz = sel.value; sel.value = '';
    if(!sz) return;
    var ed = document.getElementById('rich-editor-'+id);
    ed.focus();
    var s = window.getSelection();
    if(!s || s.rangeCount===0) return;

    if(!s.isCollapsed){
      /* Есть выделение — меняем только его */
      var range = s.getRangeAt(0);
      try{
        var frag = range.extractContents();
        var span = document.createElement('span');
        span.style.fontSize = sz;
        span.appendChild(frag);
        range.insertNode(span);
        /* Курсор после span */
        range.setStartAfter(span); range.collapse(true);
        s.removeAllRanges(); s.addRange(range);
      }catch(ex){ /* пересечение тегов — игнорируем */ }
    } else {
      /* Нет выделения — вставляем пустой span-якорь,
         все следующие символы будут его дочерними */
      var anchor = document.createElement('span');
      anchor.style.fontSize = sz;
      /* Нулевой символ ширины чтобы браузер «вошёл» внутрь span */
      anchor.innerHTML = '\u200B';
      var range2 = s.getRangeAt(0);
      range2.insertNode(anchor);
      /* Ставим курсор ПОСЛЕ \u200B, внутри span */
      range2.setStart(anchor.firstChild, 1);
      range2.collapse(true);
      s.removeAllRanges(); s.addRange(range2);
    }
    setDirty();
  };

  /* ── Стандартные кнопки форматирования ──────────────── */
  document.querySelectorAll('#rtoolbar-'+sid+' [data-cmd]').forEach(function(btn){
    if(btn.tagName==='SELECT') return;
    btn.addEventListener('mousedown',function(e){
      e.preventDefault();
      document.execCommand(btn.dataset.cmd, false, null);
      editor.focus();
      refreshToolbar();
    });
  });

  /* ── Синхрон ─────────────────────────────────────────── */
  var syncBtn = document.querySelector('#rtoolbar-'+sid+' .rt-sync-btn');
  if(syncBtn) syncBtn.addEventListener('mousedown',function(e){
    e.preventDefault();
    document.execCommand('insertHTML',false,'<span class="sync-lbl">[синхрон]</span>\u00A0');
    editor.focus();
  });

  /* ── Подсветка активных кнопок ──────────────────────── */
  function refreshToolbar(){
    document.querySelectorAll('#rtoolbar-'+sid+' [data-cmd]').forEach(function(btn){
      if(btn.tagName==='SELECT') return;
      try{ btn.classList.toggle('active', document.queryCommandState(btn.dataset.cmd)); }catch(x){}
    });
  }
  editor.addEventListener('keyup', refreshToolbar);
  editor.addEventListener('mouseup', refreshToolbar);

  /* ── Хронометраж + dirty-флаг ───────────────────────── */
  editor.addEventListener('input',function(){
    refreshToolbar();
    var text = editor.innerText||'';
    var v    = (text.match(/[аеёиоуыэюяaeiou]/gi)||[]).length;
    var sec  = Math.round(v*0.4);
    chrono.textContent='Хронометраж: '
      +Math.floor(sec/3600)+':'
      +String(Math.floor((sec%3600)/60)).padStart(2,'0')+':'
      +String(sec%60).padStart(2,'0');
    setDirty();
  });

  /* ── Enter создаёт новый параграф, сохраняет отступы ── */
  editor.addEventListener('keydown',function(e){
    if(e.key==='Enter' && !e.shiftKey){
      /* Вместо <div> вставляем <br><br> — сохраняет абзацы */
      e.preventDefault();
      document.execCommand('insertHTML', false, '<br><br>');
    }
  });

  /* ── Очистить ────────────────────────────────────────── */
  window.rtClear = function(id){
    var ed=document.getElementById('rich-editor-'+id);
    ed.innerHTML='';
    document.getElementById('chrono-live-'+id).textContent='';
    setClean();
    ed.focus();
  };

  /* ── Сохранить ───────────────────────────────────────── */
  window.rtSave = function(id){
    var ed  = document.getElementById('rich-editor-'+id);
    var html= ed.innerHTML.trim();
    /* Убираем пустые браузерные <br> в конце */
    html = html.replace(/(<br\s*\/?>\s*)+$/i,'').trim();
    if(!html || html==='<br>'){toast('Введите текст','err');return;}
    var btn = document.getElementById('save-ver-btn-'+id);
    btn.disabled=true;
    var fd = new FormData();
    fd.append('_csrf',getCsrf());
    fd.append('content',html);
    fetch('/stories/'+id+'/version',{method:'POST',body:fd})
      .then(function(r){return r.json();})
      .then(function(j){
        if(j.ok){
          setClean();
          toast('Версия сохранена');
          setTimeout(function(){location.reload();},600);
        } else {
          toast(j.error||'Ошибка','err');
          btn.disabled=false;
        }
      }).catch(function(){toast('Ошибка сети','err');btn.disabled=false;});
  };
})();
</script>
<?php endif; ?>

<?php if(empty($story['versions'])): ?>
<div class="empty-state"><div class="empty-icon">📝</div><div class="empty-text">Версий пока нет</div></div>
<?php else: ?>

<?php
// Последняя версия — разворачиваем по умолчанию
$latestVersion = $story['versions'][0] ?? null;
$canCheck = Auth::hasRole(['editor','coordinator','release','admin']);
?>

<?php if($latestVersion): ?>
<div class="detail-card" style="border-color:rgba(108,139,255,.3)">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
    <div>
      <div class="detail-card-title" style="margin-bottom:4px">Текущая версия</div>
      <div style="font-size:12px;color:var(--text3)">
        <span class="log-user"><?=Input::e($latestVersion['author_name']??'?')?></span>
        · <?=Input::e($latestVersion['created_at'])?>
        · ⏱ <?=chrF($latestVersion['content'])?>
      </div>
    </div>
    <?php if($canCheck&&$story['status']==='на проверке'): ?>
    <div style="display:flex;gap:8px">
      <button class="btn btn-sm btn-success" data-change-status data-story-id="<?=$story['id']?>" data-status="проверено">✓ Одобрить</button>
      <button class="btn btn-sm btn-danger" data-change-status data-story-id="<?=$story['id']?>" data-status="снято">← Вернуть на доработку</button>
    </div>
    <?php elseif($canCheck&&$story['status']==='смонтировано'): ?>
    <div style="display:flex;gap:8px">
      <button class="btn btn-sm btn-success" data-change-status data-story-id="<?=$story['id']?>" data-status="отсмотрено">✓ Принять монтаж</button>
      <button class="btn btn-sm btn-danger" data-change-status data-story-id="<?=$story['id']?>" data-status="проверено">← Вернуть на монтаж</button>
    </div>
    <?php endif; ?>
  </div>
  <div class="ver-content-display"><?=$latestVersion['content']?></div>
</div>
<?php endif; ?>

<?php if(count($story['versions'])>1): ?>
<div style="margin-top:8px">
  <button class="btn btn-ghost btn-sm" onclick="toggleOldVersions()" id="old-ver-toggle">▾ Показать предыдущие версии (<?=count($story['versions'])-1?>)</button>
  <div id="old-versions" style="display:none;margin-top:12px">
    <?php foreach($story['versions'] as $idx=>$v): if($idx===0)continue; ?>
    <div class="version-item">
      <div class="version-meta"><span class="log-user"><?=Input::e($v['author_name']??'?')?></span> · <?=Input::e($v['created_at'])?></div>
      <div class="version-content ver-content-display"><?=$v['content']?></div>
      <div style="font-size:11px;color:var(--text3);margin-top:4px">Хронометраж: <?=chrF($v['content'])?></div>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<?php endif; ?>
</div><!-- tab 1 -->

<script>
function toggleOldVersions(){
  const d=document.getElementById('old-versions');
  const b=document.getElementById('old-ver-toggle');
  if(d.style.display==='none'){d.style.display='block';b.textContent=b.textContent.replace('▾','▴').replace('Показать','Скрыть');}
  else{d.style.display='none';b.textContent=b.textContent.replace('▴','▾').replace('Скрыть','Показать');}
}
</script>

<!-- TAB 2 -->
<div class="tab-pane">
<?php if(empty($story['logs'])): ?><div class="empty-state"><div class="empty-icon">📜</div><div class="empty-text">Нет записей</div></div>
<?php else: foreach($story['logs'] as $l): ?>
<div class="log-item">
  <span class="log-time"><?=Input::e(substr($l['created_at'],11,5))?></span>
  <span class="log-action"><span class="log-user"><?=Input::e($l['user_name']??'?')?></span> — <?=Input::e($l['action'])?></span>
  <span class="text-dim" style="font-size:11px"><?=Input::e(substr($l['created_at'],0,10))?></span>
</div>
<?php endforeach; endif; ?>
</div><!-- tab 2 -->
</div><!-- data-tabs -->
</div>
