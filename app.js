'use strict';

const CSRF = PL.csrf;
const LIST_ID = PL.listId;

async function api(payload){
  const res = await fetch('api.php', {
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body: JSON.stringify(Object.assign({csrf: CSRF}, payload))
  });
  let data = {};
  try { data = await res.json(); } catch(e){}
  if(!res.ok || data.error){ alert(data.error || ('Error ' + res.status)); throw new Error(data.error || res.status); }
  return data;
}

const sortCats = document.getElementById('sortCats');
if(sortCats) sortCats.addEventListener('click', async ()=>{
  const msg = 'Sort the whole list by weight?\n\n'
    + '• Categories: heaviest first\n'
    + '• Items in each category: by weight × quantity\n\n'
    + 'You can still reorder manually afterwards.';
  if(!confirm(msg)) return;
  await api({action:'sort_all', list_id:LIST_ID});
  location.reload();
});

const modal  = document.getElementById('itemModal');
const mTitle = document.getElementById('modalTitle');
const mIcon  = document.getElementById('modalIcon');
const mDelete= document.getElementById('modalDelete');
const fName=document.getElementById('f-name'), fDesc=document.getElementById('f-desc'), fUrl=document.getElementById('f-url'),
      fCat=document.getElementById('f-cat'), fWeight=document.getElementById('f-weight'), fQty=document.getElementById('f-qty');
const tWear=modal.querySelector('.toggle.wear'), tCons=modal.querySelector('.toggle.cons'), tMark=modal.querySelector('.toggle.mark'), tBig3=modal.querySelector('.toggle.big3');
let editingId = null;

function setToggle(t,on){ t.classList.toggle('on',on); t.querySelector('input').checked = on; }

function openAdd(catId){
  editingId = null;
  mTitle.textContent='Add item'; mIcon.textContent='add'; mDelete.style.display='none';
  fName.value=''; fDesc.value=''; fUrl.value=''; fWeight.value=''; fQty.value='1';
  if(catId) fCat.value=String(catId);
  setToggle(tWear,false); setToggle(tCons,false); setToggle(tMark,false); setToggle(tBig3,false);
  modal.classList.add('open'); fName.focus();
}
function openEdit(tr){
  editingId = parseInt(tr.dataset.itemId);
  mTitle.textContent='Edit item'; mIcon.textContent='edit'; mDelete.style.display='';
  fName.value=tr.dataset.name||''; fDesc.value=tr.dataset.desc||''; fUrl.value=tr.dataset.url||'';
  fWeight.value=tr.dataset.weight||''; fQty.value=tr.dataset.qty||'0';
  const catId=tr.closest('.category').dataset.catId; if(catId) fCat.value=String(catId);
  setToggle(tWear, tr.dataset.worn==='1'); setToggle(tCons, tr.dataset.consumable==='1'); setToggle(tMark, tr.dataset.flag==='1'); setToggle(tBig3, tr.dataset.big3==='1');
  modal.classList.add('open');
}
function closeModal(){ modal.classList.remove('open'); }

document.querySelectorAll('.mini-btn[title="Edit"]').forEach(b=>
  b.addEventListener('click', ()=>openEdit(b.closest('tr'))));
document.querySelectorAll('.cat-add').forEach(b=>
  b.addEventListener('click', ()=>openAdd(b.dataset.catId)));
const fab=document.querySelector('.fab'); if(fab) fab.addEventListener('click', ()=>openAdd(fCat.value));

document.querySelectorAll('.mini-btn[title="Delete"]').forEach(b=>
  b.addEventListener('click', async ()=>{
    const tr=b.closest('tr');
    if(!confirm('Delete "'+(tr.dataset.name||'item')+'"?')) return;
    await api({action:'item_delete', id:parseInt(tr.dataset.itemId)});
    location.reload();
  }));

function updateSummary(t){
  if(!t) return;
  const pct={base:t.base_pct,consumable:t.consumable_pct,pack:t.pack_pct,worn:t.worn_pct,total:t.total_pct};
  document.querySelectorAll('.summary .stat').forEach(s=>{
    const k=s.dataset.k; if(!k||t[k]===undefined) return;
    const num=s.querySelector('.v-num'); if(num) num.textContent=Math.round(t[k]).toLocaleString('en-US');
    const p=s.querySelector('.pct'); if(p && pct[k]!==undefined) p.textContent=pct[k]+'%';
  });
}

document.querySelectorAll('.flag-btn').forEach(f=>{
  const toggle = async ()=>{
    if(f.dataset.busy) return;
    f.dataset.busy='1';
    const on = !f.classList.contains('on');
    f.classList.toggle('on', on);
    const tr=f.closest('tr');
    // worn and consumable are mutually exclusive: turning one on clears the other
    if(on && (f.dataset.flag==='worn' || f.dataset.flag==='consumable')){
      const other = f.dataset.flag==='worn' ? 'consumable' : 'worn';
      const otherPill = tr.querySelector('.flag-btn[data-flag="'+other+'"]');
      if(otherPill) otherPill.classList.remove('on');
      tr.dataset[other] = '0';
    }
    try{
      const r = await api({action:'item_flag', id:parseInt(tr.dataset.itemId), flag:f.dataset.flag, value:on?1:0});
      // keep the row in sync so the edit modal reflects the new state
      if(f.dataset.flag==='worn') tr.dataset.worn = on?'1':'0';
      else if(f.dataset.flag==='consumable') tr.dataset.consumable = on?'1':'0';
      else if(f.dataset.flag==='flag') tr.dataset.flag = on?'1':'0';
      updateSummary(r.totals);
    }catch(e){
      f.classList.toggle('on', !on);
    }finally{
      delete f.dataset.busy;
    }
  };
  f.addEventListener('click', toggle);
  f.addEventListener('keydown', e=>{ if(e.key==='Enter'||e.key===' '){ e.preventDefault(); toggle(); }});
});

document.getElementById('modalSave').addEventListener('click', async ()=>{
  const name=fName.value.trim();
  if(!name){ alert('Name is required'); return; }
  await api({
    action:'item_save', id:editingId||0, category_id:parseInt(fCat.value)||0,
    name, description:fDesc.value.trim(), url:fUrl.value.trim(),
    weight:parseFloat(fWeight.value)||0, qty:parseInt(fQty.value)||0,
    worn:tWear.querySelector('input').checked?1:0,
    consumable:tCons.querySelector('input').checked?1:0,
    flag:tMark.querySelector('input').checked?1:0,
    big3:tBig3.querySelector('input').checked?1:0
  });
  location.reload();
});

mDelete.addEventListener('click', async ()=>{
  if(!editingId) return;
  if(!confirm('Delete this item?')) return;
  await api({action:'item_delete', id:editingId});
  location.reload();
});

const addCat=document.querySelector('.add-cat');
if(addCat) addCat.addEventListener('click', async ()=>{
  const name=prompt('New category name:');
  if(!name || !name.trim()) return;
  await api({action:'category_create', list_id:LIST_ID, name:name.trim()});
  location.reload();
});

modal.addEventListener('click', e=>{
  if(e.target === modal || e.target.closest('[data-close]')) closeModal();
});
document.addEventListener('keydown', e=>{ if(e.key==='Escape'){ closeModal(); closeShare(); } });

modal.querySelectorAll('.toggle input').forEach(inp=>
  inp.addEventListener('change', ()=>{
    inp.closest('.toggle').classList.toggle('on', inp.checked);
    // worn and consumable are mutually exclusive
    if(inp.checked){
      if(inp===tWear.querySelector('input')) setToggle(tCons,false);
      else if(inp===tCons.querySelector('input')) setToggle(tWear,false);
    }
  }));

modal.querySelectorAll('.step').forEach(btn=>
  btn.addEventListener('click', ()=>{
    const min = parseInt(fQty.min) || 0;
    let v = (parseInt(fQty.value) || 0) + parseInt(btn.dataset.step);
    fQty.value = Math.max(min, v);
  }));

// share
const shareModal = document.getElementById('shareModal');
const shareUrl = document.getElementById('shareUrl');
function closeShare(){ shareModal.classList.remove('open'); }

document.getElementById('shareBtn').addEventListener('click', async ()=>{
  const r = await api({action:'share_enable', list_id:LIST_ID});
  shareUrl.value = r.url;
  shareModal.classList.add('open');
});
document.getElementById('shareCopy').addEventListener('click', ()=>{
  shareUrl.select();
  navigator.clipboard.writeText(shareUrl.value).then(()=>{
    const b=document.getElementById('shareCopy'); const t=b.textContent; b.textContent='Copied'; setTimeout(()=>b.textContent=t,1200);
  });
});
shareModal.addEventListener('click', e=>{
  if(e.target === shareModal || e.target.closest('[data-close-share]')) closeShare();
});

// list switcher menu
const listSwitch = document.getElementById('listSwitch');
const listMenu = document.getElementById('listMenu');
listSwitch.addEventListener('click', e=>{ e.stopPropagation(); listMenu.hidden = !listMenu.hidden; });
document.addEventListener('click', e=>{ if(!listMenu.hidden && !e.target.closest('.list-switch-wrap')) listMenu.hidden = true; });

document.getElementById('lmNew').addEventListener('click', async ()=>{
  const name = prompt('New list name:');
  if(!name || !name.trim()) return;
  const r = await api({action:'list_create', name:name.trim()});
  location.href = '?list=' + r.id;
});
const lmRename = document.getElementById('lmRename');
if(lmRename) lmRename.addEventListener('click', async ()=>{
  const name = prompt('Rename list:', PL.listName);
  if(!name || !name.trim()) return;
  await api({action:'list_rename', list_id:LIST_ID, name:name.trim()});
  location.reload();
});
const lmDelete = document.getElementById('lmDelete');
if(lmDelete) lmDelete.addEventListener('click', async ()=>{
  if(!confirm('Delete this list and everything in it?')) return;
  await api({action:'list_delete', list_id:LIST_ID});
  location.href = 'index.php';
});

// CSV import
const importFile = document.getElementById('importFile');
document.getElementById('lmImport').addEventListener('click', ()=> importFile.click());
importFile.addEventListener('change', async ()=>{
  if(!importFile.files.length) return;
  const fd = new FormData();
  fd.append('csv', importFile.files[0]);
  fd.append('csrf', CSRF);
  const res = await fetch('import.php', {method:'POST', body:fd});
  let d = {}; try { d = await res.json(); } catch(e){}
  if(!res.ok || d.error){ alert(d.error || ('Error ' + res.status)); return; }
  location.href = '?list=' + d.id;
});

// category options menu
const catMenu = document.getElementById('catMenu');
let catMenuId = null;
function openCatMenu(btn){
  catMenuId = parseInt(btn.dataset.catId);
  catMenu.dataset.name = btn.dataset.catName || '';
  catMenu.hidden = false;
  const r = btn.getBoundingClientRect();
  catMenu.style.top = (r.bottom + 6) + 'px';
  catMenu.style.left = Math.max(8, r.right - catMenu.offsetWidth) + 'px';
}
function closeCatMenu(){ catMenu.hidden = true; catMenuId = null; }
document.querySelectorAll('.cat-menu-btn').forEach(b=>
  b.addEventListener('click', e=>{
    e.stopPropagation();
    if(!catMenu.hidden && catMenuId === parseInt(b.dataset.catId)) closeCatMenu();
    else openCatMenu(b);
  }));
document.addEventListener('click', e=>{
  if(!catMenu.hidden && !e.target.closest('#catMenu') && !e.target.closest('.cat-menu-btn')) closeCatMenu();
});
document.getElementById('cmRename').addEventListener('click', async ()=>{
  const id = catMenuId, cur = catMenu.dataset.name || '';
  closeCatMenu();
  const name = prompt('Rename category:', cur);
  if(!name || !name.trim()) return;
  await api({action:'category_update', category_id:id, name:name.trim()});
  location.reload();
});
document.getElementById('cmSort').addEventListener('click', async ()=>{
  const id = catMenuId; closeCatMenu();
  await api({action:'category_sort_items', category_id:id});
  location.reload();
});
document.getElementById('cmUp').addEventListener('click', async ()=>{
  const id = catMenuId; closeCatMenu();
  await api({action:'category_move', category_id:id, dir:-1});
  location.reload();
});
document.getElementById('cmDown').addEventListener('click', async ()=>{
  const id = catMenuId; closeCatMenu();
  await api({action:'category_move', category_id:id, dir:1});
  location.reload();
});
document.getElementById('cmDelete').addEventListener('click', async ()=>{
  const id = catMenuId; closeCatMenu();
  if(!confirm('Delete this category and all its items?')) return;
  await api({action:'category_delete', category_id:id});
  location.reload();
});
