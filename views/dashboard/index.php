<?php
use App\Core\Input;
use App\Middleware\Auth;
$cu = Auth::user();
$isAdmin = $cu['role'] === 'admin';
$SC=['запланировано'=>'s-planned','снято'=>'s-shot','на проверке'=>'s-review','проверено'=>'s-checked','смонтировано'=>'s-edited','отсмотрено'=>'s-viewed','готово'=>'s-ready','вышло в эфир'=>'s-aired','отменено'=>'s-cancelled'];
$COLS=['accent'=>'#6c8bff','purple'=>'#a78bfa','green'=>'#3ecf8e','amber'=>'#f7b731','red'=>'#f06b6b','teal'=>'#2dd4bf'];
$STATUS_COLORS=['запланировано'=>'#5a5e76','снято'=>'#2dd4bf','на проверке'=>'#f7b731','проверено'=>'#6c8bff','смонтировано'=>'#a78bfa','отсмотрено'=>'#3ecf8e','готово'=>'#5ddb9e','вышло в эфир'=>'#e07878','отменено'=>'#3a3d50'];
$weekDay = ['Воскресенье','Понедельник','Вторник','Среда','Четверг','Пятница','Суббота'][date('w')];
$dateStr  = $weekDay.', '.date('j').' '.['','января','февраля','марта','апреля','мая','июня','июля','августа','сентября','октября','ноября','декабря'][date('n')].' '.date('Y');
?>

<div class="page-header">
  <div>
    <div class="page-title">Добрый день, <?=Input::e(explode(' ',$cu['name'])[0])?>!</div>
    <div class="page-subtitle"><?=Input::e($dateStr)?></div>
  </div>
</div>

<div class="page-body">

<!-- ── 1. Карточки статистики ── -->
<?php if($isAdmin): ?>
<div class="dashboard-grid" style="margin-bottom:24px">
  <div class="dash-card cr"><div class="dash-card-label">Сотрудников</div><div class="dash-card-value"><?=$adminStats['users']?></div><div class="dash-card-sub">активных</div></div>
  <div class="dash-card cp"><div class="dash-card-label">Передач</div><div class="dash-card-value"><?=$adminStats['shows']?></div></div>
  <div class="dash-card ca"><div class="dash-card-label">Сюжетов</div><div class="dash-card-value"><?=$totalStories?></div><div class="dash-card-sub">в системе</div></div>
  <div class="dash-card cg"><div class="dash-card-label">В работе</div><div class="dash-card-value"><?=$activeStories?></div></div>
</div>
<?php else: ?>
<div class="dashboard-grid" style="margin-bottom:24px">
  <div class="dash-card ca"><div class="dash-card-label">Всего сюжетов</div><div class="dash-card-value"><?=$totalStories?></div><div class="dash-card-sub">в системе</div></div>
  <div class="dash-card cg"><div class="dash-card-label">В работе</div><div class="dash-card-value"><?=$activeStories?></div><div class="dash-card-sub">активных</div></div>
  <div class="dash-card cam"><div class="dash-card-label">На проверке</div><div class="dash-card-value"><?=$onReview?></div><div class="dash-card-sub">ждут</div></div>
  <div class="dash-card cr"><div class="dash-card-label">Эфир сегодня</div><div class="dash-card-value"><?=$todayAir?></div><div class="dash-card-sub"><?=Input::e(date('d.m'))?></div></div>
</div>
<?php endif; ?>

<!-- ── 2. Мои задачи (только не-админ) ── -->
<?php if(!$isAdmin): ?>
<div style="margin-bottom:24px">
  <div class="section-title">
    ⚡ Мои задачи
    <?php if(!empty($myTasks)): ?>
      <span style="font-size:12px;font-weight:400;color:var(--text3)"><?=count($myTasks)?></span>
    <?php endif; ?>
  </div>
  <?php if(empty($myTasks)): ?>
    <div style="background:var(--bg2);border:1px solid var(--border);border-radius:12px;padding:30px;text-align:center">
      <div style="font-size:28px;margin-bottom:8px">✅</div>
      <div style="color:var(--text3);font-size:14px">Нет активных задач</div>
    </div>
  <?php else: ?>
    <div class="tasks-list">
    <?php foreach($myTasks as $t): ?>
      <a href="/stories/<?=$t['story']['id']?>" class="task-item" style="text-decoration:none">
        <div style="font-size:18px;flex-shrink:0"><?=$t['icon']?></div>
        <div class="task-info">
          <div class="task-title"><?=Input::e($t['story']['title'])?></div>
          <div class="task-meta">
            <?=$t['action']?>
            <?php if($t['urgency']==='today'): ?>
              <span style="color:var(--red);font-weight:600;margin-left:6px">● сегодня</span>
            <?php elseif($t['urgency']==='overdue'): ?>
              <span style="color:var(--red);font-weight:600;margin-left:6px">⚠ просрочено</span>
            <?php endif; ?>
          </div>
        </div>
        <?php if(!empty($t['story']['air_date'])): ?>
          <span style="font-size:11px;color:var(--text3);flex-shrink:0">эфир <?=Input::e($t['story']['air_date'])?></span>
        <?php endif; ?>
      </a>
    <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
<?php endif; ?>

<!-- ── 3. Воронка статусов ── -->
<div style="margin-bottom:24px">
  <div class="section-title">📊 Статусы сюжетов</div>
  <div style="background:var(--bg2);border:1px solid var(--border);border-radius:12px;padding:20px">
    <?php
    $total4bar = max(1, array_sum($statusCounts));
    foreach($statusCounts as $st=>$cnt):
      if($cnt===0) continue;
      $pct = round($cnt/$total4bar*100);
      $col = $STATUS_COLORS[$st]??'#5a5e76';
    ?>
    <div style="margin-bottom:10px">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px">
        <span style="font-size:13px;color:var(--text2)"><?=Input::e($st)?></span>
        <span style="font-size:13px;font-family:var(--mono);color:var(--text3)"><?=$cnt?></span>
      </div>
      <div style="height:7px;background:var(--bg3);border-radius:4px;overflow:hidden">
        <div style="height:100%;width:<?=$pct?>%;background:<?=$col?>;border-radius:4px;transition:width .6s ease"></div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- ── 4. Эфир сегодня + Топ рейтинга ── -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px">

  <!-- Эфир сегодня -->
  <div>
    <div class="section-title">
      📡 Эфир сегодня
      <a href="/air-plan" style="font-size:12px;color:var(--accent);font-weight:400;margin-left:8px">Все →</a>
    </div>
    <div style="background:var(--bg2);border:1px solid var(--border);border-radius:12px;overflow:hidden">
      <?php if(empty($todayStories)): ?>
        <div style="padding:24px;text-align:center;color:var(--text3);font-size:14px">Нет сюжетов в эфире сегодня</div>
      <?php else: foreach($todayStories as $i=>$s):
        $c=$COLS[$s['show_color']??'accent']??'#6c8bff'; ?>
        <a href="/stories/<?=$s['id']?>"
           style="display:flex;align-items:center;gap:12px;padding:12px 16px;border-bottom:1px solid var(--border);text-decoration:none;transition:background .15s"
           onmouseover="this.style.background='rgba(255,255,255,.02)'"
           onmouseout="this.style.background=''">
          <span style="font-family:var(--mono);font-size:12px;color:var(--text3);min-width:22px"><?=$i+1?>.</span>
          <div style="flex:1;min-width:0">
            <div style="font-size:14px;font-weight:500;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:var(--text)"><?=Input::e($s['title'])?></div>
            <div style="font-size:12px;color:var(--text3);margin-top:2px"><?=Input::e($s['show_name']??'')?> · <?=Input::e($s['reporter_name']??'—')?></div>
          </div>
          <span class="status-badge <?=$SC[$s['status']]??''?>" style="flex-shrink:0"><?=Input::e($s['status'])?></span>
        </a>
      <?php endforeach; endif; ?>
    </div>
  </div>

  <!-- Топ рейтинга -->
  <div>
    <div class="section-title">⭐ Топ за месяц</div>
    <div style="background:var(--bg2);border:1px solid var(--border);border-radius:12px;overflow:hidden">
      <?php if(empty($topStories)): ?>
        <div style="padding:24px;text-align:center;color:var(--text3);font-size:14px">Нет оценённых сюжетов</div>
      <?php else: foreach($topStories as $i=>$s): ?>
        <a href="/stories/<?=$s['id']?>"
           style="display:flex;align-items:center;gap:12px;padding:12px 16px;border-bottom:1px solid var(--border);text-decoration:none;transition:background .15s"
           onmouseover="this.style.background='rgba(255,255,255,.02)'"
           onmouseout="this.style.background=''">
          <span style="font-size:16px;min-width:24px"><?=$i===0?'🥇':($i===1?'🥈':($i===2?'🥉':$i+1))?></span>
          <div style="flex:1;min-width:0">
            <div style="font-size:14px;font-weight:500;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:var(--text)"><?=Input::e($s['title'])?></div>
            <div style="font-size:12px;color:var(--text3);margin-top:2px"><?=$s['cnt']?> оценок</div>
          </div>
          <div style="display:flex;align-items:center;gap:3px;flex-shrink:0">
            <span style="color:var(--amber);font-size:15px">★</span>
            <span style="font-family:var(--mono);font-size:15px;font-weight:700;color:var(--amber)"><?=number_format((float)$s['avg_r'],1)?></span>
          </div>
        </a>
      <?php endforeach; endif; ?>
    </div>
  </div>

</div>

<!-- ── 5. Последние действия — внизу ── -->
<div style="margin-bottom:24px">
  <div class="section-title">📋 Последние действия</div>
  <div style="background:var(--bg2);border:1px solid var(--border);border-radius:12px;overflow:hidden">
    <?php if(empty($activityFeed)): ?>
      <div style="padding:30px;text-align:center;color:var(--text3)">Нет действий</div>
    <?php else: foreach($activityFeed as $a): ?>
      <div class="log-item" style="padding:10px 16px;border-bottom:1px solid var(--border)">
        <div style="flex:1;min-width:0">
          <div style="font-size:13px">
            <span class="log-user"><?=Input::e($a['user_name']??'?')?></span>
            — <?=Input::e($a['action'])?>
          </div>
          <?php if(!empty($a['story_title'])): ?>
            <a href="/stories/<?=$a['story_id']?>"
               style="font-size:12px;color:var(--text3);text-decoration:none;display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;margin-top:2px">
              ↳ <?=Input::e($a['story_title'])?>
            </a>
          <?php endif; ?>
        </div>
        <span style="font-size:12px;color:var(--text3);flex-shrink:0;margin-left:10px;white-space:nowrap">
          <?=Input::e(substr($a['created_at'],0,10))?> <?=Input::e(substr($a['created_at'],11,5))?>
        </span>
      </div>
    <?php endforeach; endif; ?>
  </div>
</div>

</div><!-- page-body -->
