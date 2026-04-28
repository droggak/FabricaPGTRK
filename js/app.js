'use strict';

// ── CSRF ──────────────────────────────────────────────────────
function getCsrf(){const m=document.querySelector('meta[name="csrf"]');return m?m.content:'';}

// ── AJAX POST ─────────────────────────────────────────────────
async function apiPost(url,data={}){
  data['_csrf']=getCsrf();
  const form=new FormData();
  Object.entries(data).forEach(([k,v])=>form.append(k,v??''));
  try{const r=await fetch(url,{method:'POST',body:form});return await r.json();}
  catch(e){return{ok:false,error:'Ошибка сети'};}
}

// ── Toast ─────────────────────────────────────────────────────
function toast(msg,type='ok'){
  let w=document.querySelector('.toast-wrap');
  if(!w){w=document.createElement('div');w.className='toast-wrap';document.body.appendChild(w);}
  const t=document.createElement('div');t.className='toast '+type;
  t.textContent=(type==='ok'?'✓ ':'✕ ')+msg;w.appendChild(t);
  setTimeout(()=>t.remove(),3500);
}

// ── Tabs ──────────────────────────────────────────────────────
function initTabs(container){
  const btns=container.querySelectorAll('.tab-btn');
  const panes=container.querySelectorAll('.tab-pane');
  btns.forEach((btn,i)=>btn.addEventListener('click',()=>{
    btns.forEach(b=>b.classList.remove('active'));
    panes.forEach(p=>p.classList.remove('active'));
    btn.classList.add('active');panes[i]?.classList.add('active');
  }));
}
document.querySelectorAll('[data-tabs]').forEach(initTabs);

// ── Stars ─────────────────────────────────────────────────────
function initStars(wrap){
  const stars=wrap.querySelectorAll('.star-icon');
  const sid=wrap.dataset.storyId;
  let cur=parseInt(wrap.dataset.current)||0;
  function paint(n){stars.forEach((s,i)=>{s.classList.toggle('filled',i<n);s.classList.toggle('empty',i>=n);});}
  stars.forEach((s,i)=>{
    s.addEventListener('mouseover',()=>paint(i+1));
    s.addEventListener('mouseleave',()=>paint(cur));
    s.addEventListener('click',async()=>{
      const r=await apiPost(`/stories/${sid}/rate`,{rating:i+1});
      if(r.ok){cur=i+1;paint(cur);wrap.dataset.current=cur;toast('Оценка сохранена');
        const avg=document.getElementById(`avg-rating-${sid}`);if(avg&&r.avg)avg.textContent=r.avg.toFixed(1);}
      else toast(r.error||'Ошибка','err');
    });
  });
  paint(cur);
}
document.querySelectorAll('[data-story-stars]').forEach(initStars);

// ── Смена статуса ─────────────────────────────────────────────
document.querySelectorAll('[data-change-status]').forEach(btn=>{
  btn.addEventListener('click',()=>{
    const sid=btn.dataset.storyId,status=btn.dataset.status;
    const doChange=async()=>{
      btn.disabled=true;
      const r=await apiPost(`/stories/${sid}/status`,{status});
      if(r.ok){toast('Статус: '+status);setTimeout(()=>location.reload(),600);}
      else{toast(r.error||'Ошибка','err');btn.disabled=false;}
    };
    if(status==='отменено'){
      if(typeof showConfirm==='function'){
        showConfirm('Отменить сюжет?','Статус изменится на «отменено». Продолжить?','Отменить сюжет',doChange);
      } else { if(confirm('Отменить сюжет?')) doChange(); }
      return;
    }
    if(status==='вышло в эфир'){
      if(typeof showConfirm==='function'){
        showConfirm('Выход в эфир','Подтвердить выход в эфир?','Подтвердить',doChange);
      } else { if(confirm('Подтвердить выход в эфир?')) doChange(); }
      return;
    }
    doChange();
  });
});

// ── Назначение ────────────────────────────────────────────────
document.querySelectorAll('[data-assign-select]').forEach(sel=>{
  sel.addEventListener('change',async()=>{
    const r=await apiPost(`/stories/${sel.dataset.storyId}/assign`,{role:sel.dataset.role,user_id:sel.value});
    if(r.ok)toast('Назначение обновлено'); else toast(r.error||'Ошибка','err');
  });
});

// ── Хронометраж ───────────────────────────────────────────────
document.querySelectorAll('[data-save-duration]').forEach(btn=>{
  btn.addEventListener('click',async()=>{
    const sid=btn.dataset.storyId;
    const inp=document.getElementById(`dur-input-${sid}`);
    const r=await apiPost(`/stories/${sid}/duration`,{duration:inp?.value});
    if(r.ok)toast('Хронометраж сохранён'); else toast(r.error||'Ошибка','err');
  });
});

// ── Версия текста ─────────────────────────────────────────────
document.querySelectorAll('[data-save-version]').forEach(btn=>{
  btn.addEventListener('click',async()=>{
    const sid=btn.dataset.storyId;
    const ta=document.getElementById(`ver-text-${sid}`);
    if(!ta?.value.trim()){toast('Введите текст','err');return;}
    btn.disabled=true;
    const r=await apiPost(`/stories/${sid}/version`,{content:ta.value});
    if(r.ok){toast('Версия сохранена');setTimeout(()=>location.reload(),600);}
    else{toast(r.error||'Ошибка','err');btn.disabled=false;}
  });
});

// Живой хронометраж
function fmtDur(sec){const h=Math.floor(sec/3600);const m=Math.floor((sec%3600)/60);const s=sec%60;return`${h}:${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;}
document.querySelectorAll('[data-chrono-text]').forEach(ta=>{
  const tgt=document.getElementById(ta.dataset.chronoTarget);
  ta.addEventListener('input',()=>{if(!tgt)return;const v=(ta.value.match(/[аеёиоуыэюяaeiou]/gi)||[]).length;tgt.textContent='Хронометраж: '+fmtDur(Math.round(v*0.4));});
});

// ── Удаление пользователя ─────────────────────────────────────
document.querySelectorAll('[data-delete-user]').forEach(btn=>{
  btn.addEventListener('click',()=>{
    const id=btn.dataset.deleteUser;
    if(typeof showConfirm==='function'){
      showConfirm('Удалить сотрудника?','Все его назначения в сюжетах будут сняты.','Удалить',async()=>{
        const r=await apiPost(`/admin/users/${id}/delete`);
        if(r.ok){
          toast('Сотрудник удалён');
          // Удаляем строку таблицы сразу без перезагрузки
          document.getElementById(`urow-${id}`)?.remove();
        } else toast(r.error||'Ошибка','err');
      });
    } else {
      if(!confirm('Удалить сотрудника?')) return;
      apiPost(`/admin/users/${id}/delete`).then(r=>{
        if(r.ok){toast('Сотрудник удалён');document.getElementById(`urow-${id}`)?.remove();}
        else toast(r.error||'Ошибка','err');
      });
    }
  });
});

// ── Деактивация / активация пользователя ─────────────────────
document.querySelectorAll('[data-toggle-user]').forEach(btn=>{
  btn.addEventListener('click',async()=>{
    const id=btn.dataset.toggleUser;
    const active=btn.dataset.active==='1';
    const r=await apiPost(`/admin/users/${id}/toggle`);
    if(r.ok){
      toast(r.active?'Активирован':'Деактивирован');
      // Обновляем строку без перезагрузки
      const row=document.getElementById(`urow-${id}`);
      if(row){
        row.classList.toggle('inactive-row',!r.active);
        btn.textContent=r.active?'⏸':'▶';
        btn.dataset.active=r.active?'1':'0';
        btn.className='btn btn-sm '+(r.active?'btn-amber':'btn-success');
      }
    } else toast(r.error||'Ошибка','err');
  });
});

// ── Модалка передачи ──────────────────────────────────────────
window.openShowForm=function(id,name,desc,color,time){
  const modal=document.getElementById('modal-show');if(!modal)return;
  document.getElementById('sh-id').value=id||'';
  document.getElementById('sh-name').value=name||'';
  document.getElementById('sh-desc').value=desc||'';
  document.getElementById('sh-color').value=color||'accent';
  document.getElementById('sh-time').value=time||'';
  document.getElementById('modal-show-title').textContent=id?'Редактировать передачу':'Новая передача';
  modal.classList.add('active');
};
window.closeModal=function(id){document.getElementById(id)?.classList.remove('active');};
document.querySelectorAll('.modal-overlay').forEach(o=>o.addEventListener('click',e=>{if(e.target===o)o.classList.remove('active');}));

const showSaveBtn=document.getElementById('btn-save-show');
if(showSaveBtn){
  showSaveBtn.addEventListener('click',async()=>{
    const id=document.getElementById('sh-id').value;
    const data={name:document.getElementById('sh-name').value,description:document.getElementById('sh-desc').value,color:document.getElementById('sh-color').value,air_time:document.getElementById('sh-time').value};
    const url=id?`/admin/shows/${id}/edit`:'/admin/shows/create';
    const r=await apiPost(url,data);
    if(r.ok){toast(id?'Передача обновлена':'Передача добавлена');setTimeout(()=>location.reload(),600);}
    else toast(r.error||'Ошибка','err');
  });
}

document.querySelectorAll('[data-delete-show]').forEach(btn=>{
  btn.addEventListener('click',async()=>{
    if(!confirm('Удалить передачу? Сюжеты потеряют привязку.'))return;
    const r=await apiPost(`/admin/shows/${btn.dataset.deleteShow}/delete`);
    if(r.ok){toast('Передача удалена');setTimeout(()=>location.reload(),600);}
    else toast(r.error||'Ошибка','err');
  });
});
