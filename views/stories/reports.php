<?php
use App\Core\Input;
function fmtSec(int $s):string{return sprintf('%d:%02d:%02d',floor($s/3600),floor(($s%3600)/60),$s%60);}
function rptStars(?float $avg):string{
    if(!$avg)return '<span style="color:var(--text3)">—</span>';
    $h='<span class="stars-display">';
    for($i=1;$i<=5;$i++) $h.='<span class="star-icon '.($i<=round($avg)?'filled':'empty').'">★</span>';
    return $h.'</span><span style="font-size:11px;color:var(--text3);margin-left:4px">'.number_format($avg,1).'</span>';
}
$months=['','Январь','Февраль','Март','Апрель','Май','Июнь','Июль','Август','Сентябрь','Октябрь','Ноябрь','Декабрь'];
?>
<style>
.rpt-table{width:100%;border-collapse:collapse;table-layout:fixed;}
.rpt-table th,.rpt-table td{padding:10px 14px;border-bottom:1px solid var(--border);vertical-align:middle;}
.rpt-table th{font-size:11px;font-weight:500;color:var(--text3);text-transform:uppercase;letter-spacing:.08em;text-align:left;white-space:nowrap;}
.rpt-table td{font-size:13px;}
.rpt-table tbody tr:hover td{background:rgba(255,255,255,.02);}
.rpt-table tr:last-child td{border-bottom:none;}
.col-name{width:35%;}
.col-cnt{width:12%;text-align:center;}
.col-dur{width:18%;text-align:center;}
.col-rat{width:35%;}
</style>

<div class="page-header">
  <div>
    <div class="page-title">Отчёты</div>
    <div class="page-subtitle"><?=$months[$month]?> <?=$year?></div>
  </div>
</div>
<div class="page-body">
  <form method="GET" action="/reports" class="table-toolbar" style="margin-bottom:24px">
    <select class="filter-select" name="month">
      <?php for($m=1;$m<=12;$m++): ?>
        <option value="<?=$m?>" <?=$m==$month?'selected':''?>><?=$months[$m]?></option>
      <?php endfor; ?>
    </select>
    <select class="filter-select" name="year">
      <?php for($y=(int)date('Y');$y>=2020;$y--): ?>
        <option value="<?=$y?>" <?=$y==$year?'selected':''?>><?=$y?></option>
      <?php endfor; ?>
    </select>
    <button type="submit" class="btn btn-ghost btn-sm">Показать</button>
  </form>

<?php
$byRole=[];
foreach($stats as $r){ $byRole[$r['role']][]=$r; }
$sections=[
    'reporter'    =>'👤 Корреспонденты',
    'operator'    =>'🎬 Операторы',
    'montager'    =>'✂️ Монтажёры',
    'editor'      =>'✏️ Редакторы',
    'release'     =>'📡 Выпускающие редакторы',
    'coordinator' =>'📋 Координаторы',
    'driver'      =>'🚗 Водители',
];
foreach($sections as $slug=>$title):
    if(empty($byRole[$slug]))continue;
    $total=array_sum(array_column($byRole[$slug],'story_count'));
    $totalSec=array_sum(array_column($byRole[$slug],'total_seconds'));
?>
  <div style="margin-bottom:24px">
    <div class="section-title"><?=$title?> <span style="font-size:12px;color:var(--text3);font-weight:400"><?=$total?> сюжетов · <?=fmtSec((int)$totalSec)?></span></div>
    <div class="table-wrap">
      <table class="rpt-table">
        <thead>
          <tr>
            <th class="col-name">Сотрудник</th>
            <th class="col-cnt">Сюжетов</th>
            <th class="col-dur">Хронометраж</th>
            <th class="col-rat">Средний рейтинг</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($byRole[$slug] as $r): ?>
          <tr>
            <td>
              <strong><?=Input::e($r['name'])?></strong>
            </td>
            <td class="col-cnt mono" style="text-align:center">
              <?=(int)$r['story_count']?>
            </td>
            <td class="col-dur mono" style="text-align:center">
              <?=fmtSec((int)$r['total_seconds'])?>
            </td>
            <td class="col-rat">
              <?=rptStars($r['avg_rating']?(float)$r['avg_rating']:null)?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endforeach; ?>
</div>
