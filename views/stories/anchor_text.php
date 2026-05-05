<?php
use App\Core\Input;
$SC=['запланировано'=>'s-planned','снято'=>'s-shot','на проверке'=>'s-review','проверено'=>'s-checked',
     'смонтировано'=>'s-edited','отсмотрено'=>'s-viewed','готово'=>'s-ready','вышло в эфир'=>'s-aired','отменено'=>'s-cancelled'];
$COLS=['accent'=>'#6c8bff','purple'=>'#a78bfa','green'=>'#3ecf8e','amber'=>'#f7b731','red'=>'#f06b6b','teal'=>'#2dd4bf'];
$period   = $period   ?? 'today';
$dateFrom = $dateFrom ?? '';
$dateTo   = $dateTo   ?? '';
$showId   = $showId   ?? 0;
$PERIODS  = ['today'=>'Сегодня','3days'=>'3 дня','week'=>'Неделя','month'=>'Месяц','all'=>'Все','custom'=>'Период…'];

$list=array_values(array_filter($stories,fn($s)=>
    $s['status']!=='отменено'&&(!empty($s['latest_text'])||!empty($s['description']))
));
function chrA(string $t):string{
    $t=strip_tags($t);
    $v=preg_match_all('/[аеёиоуыэюяaeiou]/iu',$t);
    $s=(int)round($v*0.4);
    return sprintf('%d:%02d:%02d',floor($s/3600),floor(($s%3600)/60),$s%60);
}
function getStoryText(array $s):string{
    // Возвращаем HTML с форматированием (из редактора версий)
    $t=!empty($s['latest_text'])?$s['latest_text']:($s['description']??'');
    return trim($t);
}
function renderStoryHtml(string $t):string{
    // Если это уже HTML (из редактора) — оставляем как есть, только чистим опасное
    if(preg_match('/<(b|i|u|s|span|br|p|div|strong|em|ul|ol|li)/i',$t)){
        // Уже HTML из rich-редактора — пропускаем через разрешённые теги
        $allowed='<b><strong><i><em><u><s><strike><span><ul><ol><li><br><p><div>';
        return strip_tags($t,$allowed);
    }
    // Plaintext — конвертируем переносы в <br>
    $t=htmlspecialchars($t,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
    $t=nl2br($t);
    return $t;
}
?>
<style>
/* ── Список сюжетов ── */
.ac-card{background:var(--bg3);border:1px solid var(--border);border-radius:10px;margin-bottom:8px;overflow:hidden;}
.ac-header{display:flex;align-items:center;gap:10px;padding:12px 16px;cursor:pointer;user-select:none;transition:background .15s;}
.ac-header:hover{background:rgba(255,255,255,.03);}
.ac-num{font-family:var(--mono);font-size:12px;color:var(--text3);min-width:24px;flex-shrink:0;}
.ac-title-main{font-size:14px;font-weight:600;flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.ac-arrow{color:var(--text3);font-size:11px;transition:transform .2s;flex-shrink:0;}
.ac-card.open .ac-arrow{transform:rotate(90deg);}
.ac-body{display:none;padding:0 16px 16px;}
.ac-card.open .ac-body{display:block;}
.ac-text-block{font-size:15px;line-height:1.9;color:var(--text);word-break:break-word;padding:14px;
  background:var(--bg2);border-radius:8px;border:1px solid var(--border);}
.ac-text-block strong{font-weight:700;color:var(--text);}
.ac-text-block em{font-style:italic;color:var(--text2);}
.ac-text-block u{text-decoration:underline;}
.sync-mark{background:rgba(247,183,49,.15);color:var(--amber);padding:1px 6px;border-radius:4px;font-style:italic;font-size:0.9em;}
.ac-sub{font-size:12px;color:var(--text3);font-family:var(--mono);margin-top:8px;}
.tp-launch-btn{margin-left:auto;flex-shrink:0;}

/* ── Телесуфлёр overlay ── */
#tp-overlay{
  display:none;position:fixed;inset:0;z-index:9000;
  background:#000;overflow-y:auto;padding:60px 80px 120px;
}
#tp-overlay.active{display:block;}
#tp-story-title{font-size:26px;color:#ffff00;font-weight:700;margin-bottom:24px;line-height:1.3;}
#tp-story-text{
  font-size:44px;color:#fff;line-height:1.9;word-break:break-word;
  white-space:pre-wrap;  /* сохраняем переносы строк */
}
#tp-story-text span{font-size:inherit;}  /* span от редактора не ломает размер */
#tp-story-text br{display:block;margin-bottom:.4em;}  /* отступ между абзацами */
#tp-story-text strong{font-weight:700;}
#tp-story-text em{font-style:italic;color:#ddd;}
#tp-story-text u{text-decoration:underline;}
#tp-story-text .sync-mark{color:#f7b731;font-style:italic;font-size:0.8em;}

/* ── Панель управления суфлёром ── */
#tp-ctrl{
  position:fixed;bottom:0;left:0;right:0;z-index:9100;
  background:rgba(0,0,0,.85);backdrop-filter:blur(8px);
  display:none;padding:12px 24px;
  align-items:center;gap:12px;flex-wrap:wrap;
}
#tp-overlay.active ~ #tp-ctrl,
body.tp-active #tp-ctrl{display:flex;}
.tp-btn{padding:10px 20px;border-radius:8px;border:none;cursor:pointer;font-size:14px;font-weight:600;transition:opacity .2s;}
.tp-btn:hover{opacity:.85;}

/* ── Печать ── */
@media print{
  .sidebar,.ac-filters,.no-print,.ac-arrow{display:none!important;}
  .app-wrap{display:block;}.main-content{height:auto;overflow:visible;}
  .ac-card{border:1px solid #ccc;margin-bottom:10px;break-inside:avoid;}
  .ac-body{display:block!important;padding:10px 14px;}
  .ac-title-main{font-size:14px;font-weight:bold;font-family:Arial;white-space:normal;}
  .ac-text-block{background:none;border:none;padding:0;font-size:13px;font-family:Arial;line-height:1.7;}
}
</style>

<!-- Страница -->
<div class="page-header ac-filters">
  <div>
    <div class="page-title">Дикторский текст</div>
    <div class="page-subtitle"><?=count($list)?> сюжетов</div>
  </div>
  <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
    <form method="GET" style="display:flex;gap:8px">
      <input type="date" class="filter-select" name="date" value="<?=Input::e($date??date('Y-m-d'))?>">
      <select class="filter-select" name="show_id" onchange="this.form.submit()">
        <option value="">Все передачи</option>
        <?php foreach($shows as $sh): ?>
          <option value="<?=$sh['id']?>" <?=($showId??0)==$sh['id']?'selected':''?>><?=Input::e($sh['name'])?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn btn-ghost btn-sm">Применить</button>
    </form>
    <button class="btn btn-ghost btn-sm no-print" onclick="expandAll()">▿ Все</button>
    <button class="btn btn-ghost btn-sm no-print" onclick="collapseAll()">▵ Свернуть</button>
    <button class="btn btn-ghost btn-sm no-print" onclick="window.print()">🖨 Печать</button>
  </div>
</div>

<!-- Кнопки периода (скрываются в суфлёре через .ac-filters) -->
<div class="page-body ac-filters" style="padding-top:0;padding-bottom:8px">
  <div style="display:flex;gap:6px;flex-wrap:nowrap;overflow-x:auto;padding-bottom:2px;margin-bottom:8px">
    <?php foreach($PERIODS as $key=>$label): ?>
      <a href="/anchor-text?period=<?=$key?>&show_id=<?=$showId?>"
         class="btn btn-sm <?=$period===$key?'btn-accent':'btn-ghost'?>"
         style="flex-shrink:0;white-space:nowrap"><?=$label?></a>
    <?php endforeach; ?>
  </div>
  <?php if($period==='custom'): ?>
  <form method="GET" action="/anchor-text" style="display:flex;gap:8px;align-items:center;margin-bottom:8px">
    <input type="hidden" name="period" value="custom">
    <input type="date" name="date_from" class="filter-select" value="<?=Input::e($dateFrom)?>" style="width:150px">
    <span style="color:var(--text3)">—</span>
    <input type="date" name="date_to" class="filter-select" value="<?=Input::e($dateTo)?>" style="width:150px">
    <button type="submit" class="btn btn-ghost btn-sm">Применить</button>
  </form>
  <?php endif; ?>
  <form method="GET" action="/anchor-text" style="display:flex;gap:8px">
    <input type="hidden" name="period" value="<?=Input::e($period)?>">
    <?php if($period==='custom'&&$dateFrom): ?>
      <input type="hidden" name="date_from" value="<?=Input::e($dateFrom)?>">
      <input type="hidden" name="date_to"   value="<?=Input::e($dateTo)?>">
    <?php endif; ?>
    <select class="filter-select" name="show_id" onchange="this.form.submit()">
      <option value="">Все передачи</option>
      <?php foreach($shows as $sh): ?>
        <option value="<?=$sh['id']?>" <?=$showId==$sh['id']?'selected':''?>><?=Input::e($sh['name'])?></option>
      <?php endforeach; ?>
    </select>
  </form>
</div>

<div class="page-body" id="anchor-list">
<?php if(empty($list)): ?>
  <div class="empty-state"><div class="empty-icon">📢</div><div class="empty-text">Нет материалов на выбранную дату</div></div>
<?php else: foreach($list as $i=>$s):
  $c=$COLS[$s['show_color']??'accent']??'#6c8bff';
  $text=getStoryText($s);
  $hasVer=!empty($s['latest_text']);
?>
<div class="ac-card" id="ac-<?=$s['id']?>">
  <div class="ac-header">
    <span class="ac-num no-print"><?=str_pad($i+1,2,'0',STR_PAD_LEFT)?>.</span>
    <?php if($s['show_id']): ?>
      <span class="show-badge no-print" style="background:<?=$c?>22;color:<?=$c?>;flex-shrink:0">📺 <?=Input::e($s['show_name'])?></span>
    <?php endif; ?>
    <span class="ac-title-main" onclick="toggleCard(<?=$s['id']?>)"><?=Input::e($s['title'])?></span>
    <span class="status-badge <?=$SC[$s['status']]??''?> no-print" style="flex-shrink:0"><?=Input::e($s['status'])?></span>
    <!-- Кнопка суфлёра для этого сюжета -->
    <button class="btn btn-accent btn-sm tp-launch-btn no-print"
            onclick="event.stopPropagation();startTp(<?=$s['id']?>,'<?=Input::e(addslashes($s['title']))?>',this)"
            title="Открыть в суфлёре">▶ Суфлёр</button>
    <span class="ac-arrow no-print" onclick="toggleCard(<?=$s['id']?>)">▶</span>
  </div>
  <div class="ac-body">
    <div class="ac-text-block" id="text-block-<?=$s['id']?>"><?=renderStoryHtml($text)?></div>
    <div class="ac-sub">⏱ <?=chrA($text)?> · <?=$hasVer?'текст сюжета':'подводка'?></div>
  </div>
  <!-- Скрытый источник текста для суфлёра -->
  <template id="tp-src-<?=$s['id']?>"><?=renderStoryHtml($text)?></template>
</div>
<?php endforeach; endif; ?>
</div>

<!-- ── Телесуфлёр overlay ── -->
<div id="tp-overlay">
  <div id="tp-story-title"></div>
  <div id="tp-story-text"></div>
</div>

<!-- Панель управления суфлёра -->
<div id="tp-ctrl">
  <button class="tp-btn" style="background:#f06b6b;color:#fff" onclick="stopTp()">✕ Закрыть</button>
  <button class="tp-btn" style="background:#3ecf8e;color:#000" id="tp-play-btn" onclick="toggleScroll()">▶ Пуск</button>
  <div style="display:flex;align-items:center;gap:8px">
    <span style="font-size:12px;color:#aaa">Скорость:</span>
    <input type="range" id="tp-speed" min="10" max="500" value="120" style="width:140px">
  </div>
  <button class="tp-btn" style="background:#333;color:#fff" onclick="tpFont(6)">A+</button>
  <button class="tp-btn" style="background:#333;color:#fff" onclick="tpFont(-6)">A−</button>
  <div id="tp-story-label" style="font-size:13px;color:#aaa;margin-left:8px;max-width:300px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"></div>
</div>

<script>
// ── Карточки ──────────────────────────────────────────────────
function toggleCard(id){
  const card=document.getElementById('ac-'+id);
  card.classList.toggle('open');
}
function expandAll(){document.querySelectorAll('.ac-card').forEach(c=>c.classList.add('open'));}
function collapseAll(){document.querySelectorAll('.ac-card').forEach(c=>c.classList.remove('open'));}

// ── Телесуфлёр ────────────────────────────────────────────────
let tpScrollT=null, tpScrolling=false, tpFontSize=44;

function startTp(storyId, title, btn){
  // Берём HTML из template-тега
  const tpl=document.getElementById('tp-src-'+storyId);
  if(!tpl){return;}

  document.getElementById('tp-story-title').textContent=title;
  document.getElementById('tp-story-text').innerHTML=tpl.innerHTML;
  document.getElementById('tp-story-label').textContent=title;
  document.getElementById('tp-story-text').style.fontSize=tpFontSize+'px';

  // Сброс прокрутки и кнопки
  const ov=document.getElementById('tp-overlay');
  ov.scrollTop=0;
  ov.classList.add('active');
  document.body.classList.add('tp-active');

  // Сброс воспроизведения
  if(tpScrollT){clearInterval(tpScrollT);tpScrollT=null;tpScrolling=false;}
  const pb=document.getElementById('tp-play-btn');
  pb.textContent='▶ Пуск';pb.style.background='#3ecf8e';pb.style.color='#000';
  document.getElementById('tp-ctrl').style.display='flex';
}

function stopTp(){
  document.getElementById('tp-overlay').classList.remove('active');
  document.body.classList.remove('tp-active');
  document.getElementById('tp-ctrl').style.display='none';
  if(tpScrollT){clearInterval(tpScrollT);tpScrollT=null;tpScrolling=false;}
  document.getElementById('tp-play-btn').textContent='▶ Пуск';
}

function toggleScroll(){
  const btn=document.getElementById('tp-play-btn');
  if(tpScrolling){
    clearInterval(tpScrollT);tpScrollT=null;tpScrolling=false;
    btn.textContent='▶ Пуск';btn.style.background='#3ecf8e';btn.style.color='#000';
  } else {
    tpScrolling=true;
    btn.textContent='⏸ Пауза';btn.style.background='#f7b731';btn.style.color='#000';
    doTpScroll();
  }
}

function doTpScroll(){
  const ov=document.getElementById('tp-overlay');
  const spd=parseInt(document.getElementById('tp-speed').value);
  // spd: 10(медленно)..500(быстро). delay в мс между шагами прокрутки
  const pixPerStep = spd <= 100 ? 1 : Math.floor(spd / 100);
  const delay = Math.max(5, Math.floor(2000 / spd));
  tpScrollT=setInterval(()=>{
    ov.scrollTop+=pixPerStep;
    if(ov.scrollTop+ov.clientHeight>=ov.scrollHeight-4){
      clearInterval(tpScrollT);tpScrollT=null;tpScrolling=false;
      document.getElementById('tp-play-btn').textContent='▶ Пуск';
    }
  },delay);
}

document.getElementById('tp-speed')?.addEventListener('input',()=>{
  if(tpScrolling){clearInterval(tpScrollT);doTpScroll();}
});

function tpFont(d){
  tpFontSize=Math.min(80,Math.max(20,tpFontSize+d));
  document.getElementById('tp-story-text').style.fontSize=tpFontSize+'px';
}

// Клавиши
document.addEventListener('keydown',e=>{
  const active=document.getElementById('tp-overlay').classList.contains('active');
  if(!active)return;
  if(e.key==='Escape')stopTp();
  if(e.key===' '){e.preventDefault();toggleScroll();}
  if(e.key==='ArrowUp')tpFont(4);
  if(e.key==='ArrowDown')tpFont(-4);
});
</script>
