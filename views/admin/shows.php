<?php use App\Core\Input;
$COLORS=['accent'=>'#6c8bff','purple'=>'#a78bfa','green'=>'#3ecf8e','amber'=>'#f7b731','red'=>'#f06b6b','teal'=>'#2dd4bf'];
$CL=['accent'=>'Синий','purple'=>'Фиолетовый','green'=>'Зелёный','amber'=>'Жёлтый','red'=>'Красный','teal'=>'Бирюзовый'];
?>
<div class="page-header">
  <div><div class="page-title">Передачи</div><div class="page-subtitle">Всего: <?=count($shows)?></div></div>
  <button class="btn btn-accent btn-sm" onclick="openShowForm()">+ Добавить передачу</button>
</div>
<div class="page-body">

<div class="table-wrap">
  <table class="data-table">
    <thead>
      <tr>
        <th style="width:14px"></th>
        <th>Название</th>
        <th>Время эфира</th>
        <th>Описание</th>
        <th>Сюжетов</th>
        <th style="width:120px">Действия</th>
      </tr>
    </thead>
    <tbody>
    <?php if(empty($shows)): ?>
      <tr><td colspan="6">
        <div class="empty-state" style="padding:40px">
          <div class="empty-icon">📺</div>
          <div class="empty-text">Нет передач. Создайте первую!</div>
        </div>
      </td></tr>
    <?php else: foreach($shows as $sh):
      $c=$COLORS[$sh['color']]??'#a78bfa'; ?>
      <tr>
        <td><div style="width:12px;height:12px;border-radius:50%;background:<?=$c?>;flex-shrink:0"></div></td>
        <td><strong><?=Input::e($sh['name'])?></strong></td>
        <td class="mono text-muted"><?=Input::e($sh['air_time']??'—')?></td>
        <td class="text-muted" style="font-size:13px;max-width:280px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
          <?=Input::e($sh['description']??'—')?>
        </td>
        <td class="mono"><?=(int)$sh['story_count']?></td>
        <td>
          <div style="display:flex;gap:6px">
            <button class="btn btn-sm btn-ghost"
                    onclick="openShowForm(<?=$sh['id']?>,'<?=Input::e(addslashes($sh['name']))?>','<?=Input::e(addslashes($sh['description']??''))?>','<?=Input::e($sh['color'])?>','<?=Input::e($sh['air_time']??'')?>')">
              ✎
            </button>
            <button class="btn btn-sm btn-danger" data-delete-show="<?=$sh['id']?>" title="Удалить">✕</button>
          </div>
        </td>
      </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>
</div>

<!-- Модалка -->
<div class="modal-overlay" id="modal-show">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title" id="modal-show-title">Новая передача</div>
      <button class="modal-close" onclick="closeModal('modal-show')">✕</button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="sh-id">
      <div class="form-group"><label>Название *</label><input type="text" id="sh-name" placeholder="Новости 20:00"></div>
      <div class="form-group"><label>Описание</label><textarea id="sh-desc" style="min-height:60px"></textarea></div>
      <div class="form-row">
        <div class="form-group">
          <label>Время эфира</label>
          <input type="text" id="sh-time" placeholder="20:00">
        </div>
        <div class="form-group">
          <label>Цвет</label>
          <select id="sh-color">
            <?php foreach($CL as $slug=>$label): ?>
              <option value="<?=$slug?>"><?=$label?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('modal-show')">Отмена</button>
      <button class="btn btn-accent" id="btn-save-show">Сохранить</button>
    </div>
  </div>
</div>
