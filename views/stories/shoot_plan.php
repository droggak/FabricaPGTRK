<?php
use App\Core\Input;
use App\Middleware\Auth;
$isCoord = Auth::hasRole(['coordinator','admin']);
?>
<style>
.shoot-upload-area{border:2px dashed var(--border);border-radius:12px;padding:40px;
  text-align:center;cursor:pointer;transition:border-color .2s;background:var(--bg3);}
.shoot-upload-area:hover,.shoot-upload-area.drag{border-color:var(--accent);background:rgba(108,139,255,.05);}
.shoot-upload-area input[type=file]{display:none;}
#docx-table-wrap table{width:100%;border-collapse:collapse;font-size:13px;margin-top:8px;}
#docx-table-wrap th,#docx-table-wrap td{padding:9px 12px;border:1px solid var(--border);vertical-align:top;word-break:break-word;}
#docx-table-wrap th{background:var(--bg2);font-weight:600;color:var(--text2);font-size:11px;text-transform:uppercase;letter-spacing:.06em;}
#docx-table-wrap tr:hover td{background:rgba(255,255,255,.02);}
.file-chip{display:inline-flex;align-items:center;gap:8px;padding:8px 14px;background:var(--bg3);
  border:1px solid var(--border);border-radius:10px;font-size:13px;cursor:pointer;
  transition:border-color .15s;margin:4px;}
.file-chip:hover{border-color:var(--accent);}
.file-chip.active{border-color:var(--accent);background:rgba(108,139,255,.08);}
</style>

<div class="page-header">
  <div>
    <div class="page-title">План съёмок</div>
    <div class="page-subtitle" id="plan-subtitle">Загрузите или выберите план</div>
  </div>
  <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
    <input type="date" class="filter-select" id="filter-date" value="<?=Input::e($date??date('Y-m-d'))?>">
    <select class="filter-select" id="filter-show">
      <option value="">Все передачи</option>
      <?php foreach($shows as $sh): ?>
        <option value="<?=$sh['id']?>"><?=Input::e($sh['name'])?></option>
      <?php endforeach; ?>
    </select>
  </div>
</div>

<div class="page-body">

<!-- Загруженные файлы — видны ВСЕМ -->
<div style="margin-bottom:20px">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
    <div style="font-size:13px;font-weight:600;color:var(--text2)">📎 Планы съёмок</div>
    <button class="btn btn-ghost btn-sm" onclick="loadFiles()">↻</button>
  </div>
  <div id="files-list" style="display:flex;flex-wrap:wrap;gap:4px;min-height:40px">
    <div style="font-size:13px;color:var(--text3)">Загрузка...</div>
  </div>
</div>

<!-- Загрузка нового файла — только координатор -->
<?php if($isCoord): ?>
<div class="detail-card" style="margin-bottom:20px">
  <div class="detail-card-title">Загрузить план</div>
  <div style="display:flex;gap:12px;margin-bottom:16px;flex-wrap:wrap">
    <div style="flex:1;min-width:160px">
      <label style="font-size:12px;color:var(--text3);display:block;margin-bottom:4px">Дата плана</label>
      <input type="date" id="upload-date" class="filter-select" style="width:100%" value="<?=Input::e($date??date('Y-m-d'))?>">
    </div>
    <div style="flex:1;min-width:160px">
      <label style="font-size:12px;color:var(--text3);display:block;margin-bottom:4px">Передача</label>
      <select id="upload-show" class="filter-select" style="width:100%">
        <option value="">— Все —</option>
        <?php foreach($shows as $sh): ?>
          <option value="<?=$sh['id']?>"><?=Input::e($sh['name'])?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>
  <div class="shoot-upload-area" id="upload-area"
       onclick="document.getElementById('docx-file').click()"
       ondragover="event.preventDefault();this.classList.add('drag')"
       ondragleave="this.classList.remove('drag')"
       ondrop="handleDrop(event)">
    <input type="file" id="docx-file" accept=".docx,.doc" onchange="handleFile(this.files[0])">
    <div style="font-size:36px;margin-bottom:10px">📄</div>
    <div style="font-size:15px;font-weight:600;margin-bottom:6px">Загрузить .docx файл</div>
    <div style="font-size:13px;color:var(--text3)">Перетащите или нажмите для выбора</div>
  </div>
  <div id="upload-status" style="margin-top:10px;font-size:13px;color:var(--text3)"></div>
</div>
<?php endif; ?>

<!-- Содержимое выбранного файла -->
<div id="docx-view" style="display:none">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
    <div id="docx-filename" style="font-weight:600;font-size:14px"></div>
    <button class="btn btn-ghost btn-sm" onclick="closeDocx()">✕ Закрыть</button>
  </div>
  <div id="docx-table-wrap"></div>
</div>

</div>

<script>
var activeFileId=null;

// ── Список файлов ──────────────────────────────────────────
function loadFiles(){
  var date=document.getElementById('filter-date').value;
  fetch('/shoot-plan/files'+(date?'?date='+date:''))
    .then(function(r){return r.json();})
    .then(function(j){
      var el=document.getElementById('files-list');
      if(!j.ok||!j.files||!j.files.length){
        el.innerHTML='<div style="font-size:13px;color:var(--text3)">Нет загруженных планов</div>';
        return;
      }
      el.innerHTML='';
      j.files.forEach(function(f){
        var chip=document.createElement('div');
        chip.className='file-chip';
        chip.id='chip-'+f.id;
        chip.innerHTML='<span>📄</span>'
          +'<div><div style="font-weight:500">'+f.filename+'</div>'
          +'<div style="font-size:11px;color:var(--text3)">'+(f.plan_date?f.plan_date+' · ':'')+f.uploader_name+'</div></div>';
        chip.onclick=function(){openFile(f.id,f.filename);};
        <?php if($isCoord): ?>
        var del=document.createElement('span');
        del.textContent='×';del.title='Удалить';
        del.style.cssText='color:var(--red);font-size:18px;line-height:1;margin-left:4px;opacity:.7';
        del.onclick=function(e){
          e.stopPropagation();
          if(!confirm('Удалить «'+f.filename+'»?'))return;
          var fd=new FormData();fd.append('_csrf',getCsrf());
          fetch('/shoot-plan/file/'+f.id+'/delete',{method:'POST',body:fd})
            .then(function(r){return r.json();})
            .then(function(j){if(j.ok){toast('Файл удалён');loadFiles();if(activeFileId==f.id)closeDocx();}});
        };
        chip.appendChild(del);
        <?php endif; ?>
        el.appendChild(chip);
      });
    })
    .catch(function(){});
}

// ── Открыть сохранённый файл ───────────────────────────────
function openFile(id, name){
  // Снимаем active с предыдущего
  document.querySelectorAll('.file-chip').forEach(function(c){c.classList.remove('active');});
  var chip=document.getElementById('chip-'+id);
  if(chip) chip.classList.add('active');
  activeFileId=id;
  document.getElementById('docx-filename').textContent=name;
  document.getElementById('docx-view').style.display='block';
  document.getElementById('docx-table-wrap').innerHTML='<div style="color:var(--text3);padding:16px">⏳ Загрузка...</div>';
  fetch('/shoot-plan/file/'+id)
    .then(function(r){return r.arrayBuffer();})
    .then(function(buf){renderDocx(buf,name);})
    .catch(function(){document.getElementById('docx-table-wrap').innerHTML='<div style="color:var(--red)">Ошибка загрузки</div>';});
}

function closeDocx(){
  document.getElementById('docx-view').style.display='none';
  document.querySelectorAll('.file-chip').forEach(function(c){c.classList.remove('active');});
  activeFileId=null;
}

// ── Загрузка нового файла ──────────────────────────────────
function handleDrop(e){
  e.preventDefault();
  document.getElementById('upload-area').classList.remove('drag');
  if(e.dataTransfer.files[0]) handleFile(e.dataTransfer.files[0]);
}

function handleFile(file){
  if(!file) return;
  if(!file.name.match(/\.docx?$/i)){setUploadStatus('❌ Только .docx файлы');return;}
  if(file.size>10*1024*1024){setUploadStatus('❌ Максимум 10 МБ');return;}
  setUploadStatus('⏳ Загружаю...');
  var fd=new FormData();
  fd.append('_csrf',getCsrf());
  fd.append('plan_file',file);
  fd.append('plan_date',document.getElementById('upload-date').value||'');
  fd.append('show_id',  document.getElementById('upload-show').value||'');
  fetch('/shoot-plan/upload',{method:'POST',body:fd})
    .then(function(r){return r.json();})
    .then(function(j){
      if(j.ok){
        setUploadStatus('✅ Файл сохранён — виден всем участникам');
        toast('План съёмок загружен');
        loadFiles();
        // Сразу открываем
        openFile(j.id,j.name);
      } else {
        setUploadStatus('❌ '+(j.error||'Ошибка'));
      }
    })
    .catch(function(){setUploadStatus('❌ Ошибка сети');});
}

function setUploadStatus(msg){
  var el=document.getElementById('upload-status');
  if(el) el.textContent=msg;
}

// ── Рендер DOCX ───────────────────────────────────────────
function renderDocx(arrayBuffer, name){
  if(typeof mammoth==='undefined'){
    var s=document.createElement('script');
    s.src='https://cdnjs.cloudflare.com/ajax/libs/mammoth/1.6.0/mammoth.browser.min.js';
    s.onload=function(){renderDocx(arrayBuffer,name);};
    s.onerror=function(){document.getElementById('docx-table-wrap').innerHTML='<div style="color:var(--red)">Ошибка загрузки mammoth.js</div>';};
    document.head.appendChild(s);
    return;
  }
  mammoth.convertToHtml({arrayBuffer:arrayBuffer})
    .then(function(result){
      var wrap=document.getElementById('docx-table-wrap');
      var parser=new DOMParser();
      var doc=parser.parseFromString(result.value,'text/html');
      var tables=doc.querySelectorAll('table');
      if(!tables.length){
        wrap.innerHTML='<div style="background:var(--bg3);border-radius:8px;padding:16px;line-height:1.8">'+result.value+'</div>';
      } else {
        wrap.innerHTML='';
        tables.forEach(function(tbl,i){
          if(tables.length>1){
            var h=document.createElement('div');
            h.className='section-title';h.style.marginTop=i?'24px':'0';
            h.textContent='Таблица '+(i+1);wrap.appendChild(h);
          }
          tbl.querySelectorAll('[style],[width],[bgcolor]').forEach(function(el){
            el.removeAttribute('style');el.removeAttribute('width');el.removeAttribute('bgcolor');
          });
          var first=tbl.querySelector('tr');
          if(first) first.querySelectorAll('td').forEach(function(td){
            var th=document.createElement('th');th.innerHTML=td.innerHTML;td.parentNode.replaceChild(th,td);
          });
          wrap.appendChild(tbl.cloneNode(true));
        });
      }
    })
    .catch(function(e){document.getElementById('docx-table-wrap').innerHTML='<div style="color:var(--red)">Ошибка: '+e.message+'</div>';});
}

// ── Инициализация ─────────────────────────────────────────
document.addEventListener('DOMContentLoaded',function(){
  loadFiles();
  document.getElementById('filter-date').addEventListener('change',function(){
    closeDocx();  // закрываем текущий план при смене даты
    loadFiles();
  });
  document.getElementById('filter-show').addEventListener('change',function(){
    closeDocx();
    loadFiles();
  });
});
</script>
