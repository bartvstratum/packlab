<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/render.php';

$userId = current_user_id();
if ($userId === null) {
    header('Location: login.php');
    exit;
}

$lists = lists_for_user($userId);
$listId = isset($_GET['list']) ? (int) $_GET['list'] : (int) ($lists[0]['id'] ?? 0);
$data = $listId ? list_full($listId) : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>PackLab</title>
<link rel="icon" type="image/svg+xml" href="favicon.svg">
<meta name="theme-color" content="#2f8f5b">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,400,0,0" rel="stylesheet">
<link rel="stylesheet" href="style.css">
</head>
<body>

<header>
  <div class="bar">
    <div class="brand">
      <span class="material-symbols-rounded brand-logo">balance</span>
      <span class="name">PackLab</span>
    </div>
    <div class="spacer"></div>
    <div class="list-switch-wrap">
      <button class="list-switch" id="listSwitch" title="Switch list">
        <span class="material-symbols-rounded">checklist</span>
        <span class="label"><?= h($data['name'] ?? 'No list') ?></span>
        <span class="material-symbols-rounded">expand_more</span>
      </button>
      <div class="list-menu" id="listMenu" hidden>
<?php foreach ($lists as $l): ?>
        <a class="lm-item<?= (int) $l['id'] === $listId ? ' current' : '' ?>" href="?list=<?= (int) $l['id'] ?>">
          <span class="material-symbols-rounded">checklist</span>
          <span class="lm-label"><?= h($l['name']) ?></span>
<?php if ((int) $l['id'] === $listId): ?><span class="material-symbols-rounded lm-check">check</span><?php endif; ?>
        </a>
<?php endforeach; ?>
        <div class="lm-sep"></div>
        <button class="lm-item" id="lmNew"><span class="material-symbols-rounded">add</span>New list</button>
<?php if ($data): ?>
        <button class="lm-item" id="lmRename"><span class="material-symbols-rounded">edit</span>Rename list</button>
        <a class="lm-item" href="export.php?list=<?= $listId ?>"><span class="material-symbols-rounded">download</span>Export CSV</a>
<?php endif; ?>
        <button class="lm-item" id="lmImport"><span class="material-symbols-rounded">upload</span>Import CSV</button>
<?php if ($data): ?>
        <button class="lm-item lm-danger" id="lmDelete"><span class="material-symbols-rounded">delete</span>Delete list</button>
<?php endif; ?>
      </div>
    </div>
    <input type="file" id="importFile" accept=".csv,text/csv" hidden>
    <button class="icon-btn" id="shareBtn" title="Share"><span class="material-symbols-rounded">share</span></button>
    <a class="icon-btn" href="account.php" title="Account"><span class="material-symbols-rounded">account_circle</span></a>
  </div>
</header>

<main>
<?php if (!$data): ?>
  <p style="text-align:center;color:var(--muted);padding:48px 16px">No list yet. Import a CSV or create a list to get started.</p>
<?php else: ?>
  <?php render_list($data, true); ?>
<?php endif; ?>
</main>

<button class="fab" title="Add item"><span class="material-symbols-rounded">add</span></button>

<div class="overlay" id="itemModal">
  <div class="modal" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
    <div class="modal-head">
      <span class="material-symbols-rounded" id="modalIcon">edit</span>
      <span class="modal-title" id="modalTitle">Edit item</span>
      <button class="icon-btn" data-close title="Close"><span class="material-symbols-rounded">close</span></button>
    </div>
    <div class="modal-body">
      <div class="field">
        <label for="f-name">Name</label>
        <input type="text" id="f-name" placeholder="e.g. Tent">
      </div>
      <div class="field">
        <label for="f-desc">Description</label>
        <textarea id="f-desc" placeholder="Optional notes"></textarea>
      </div>
      <div class="field">
        <label for="f-url">Link <span class="opt">optional</span></label>
        <input type="url" id="f-url" placeholder="https://… (shop, review, spec)">
      </div>
      <div class="field">
        <label for="f-cat">Category</label>
        <select id="f-cat">
<?php foreach (($data['categories'] ?? []) as $c): ?>
          <option value="<?= (int) $c['id'] ?>"><?= h($c['name']) ?></option>
<?php endforeach; ?>
        </select>
      </div>
      <div class="row2">
        <div class="field">
          <label for="f-weight">Weight</label>
          <div class="input-suffix">
            <input type="number" id="f-weight" inputmode="decimal" min="0" step="1" placeholder="0">
            <span class="unit">g</span>
          </div>
        </div>
        <div class="field">
          <label for="f-qty">Quantity</label>
          <div class="stepper">
            <button type="button" class="step" data-step="-1" aria-label="Decrease quantity">
              <span class="material-symbols-rounded">remove</span>
            </button>
            <input type="number" id="f-qty" inputmode="numeric" min="0" step="1" value="1">
            <button type="button" class="step" data-step="1" aria-label="Increase quantity">
              <span class="material-symbols-rounded">add</span>
            </button>
          </div>
        </div>
      </div>
      <div class="field">
        <label>Flags</label>
        <div class="toggles">
          <label class="toggle wear">
            <input type="checkbox"><span class="material-symbols-rounded">checkroom</span>Worn
          </label>
          <label class="toggle cons">
            <input type="checkbox"><span class="material-symbols-rounded">restaurant</span>Consumable
          </label>
        </div>
      </div>
    </div>
    <div class="modal-foot">
      <button class="btn btn-danger" id="modalDelete">
        <span class="material-symbols-rounded">delete</span>Delete
      </button>
      <button class="btn" data-close>Cancel</button>
      <button class="btn btn-primary" id="modalSave">Save</button>
    </div>
  </div>
</div>

<div class="overlay" id="shareModal">
  <div class="modal" role="dialog" aria-modal="true">
    <div class="modal-head">
      <span class="material-symbols-rounded">share</span>
      <span class="modal-title">Share list</span>
      <button class="icon-btn" data-close-share title="Close"><span class="material-symbols-rounded">close</span></button>
    </div>
    <div class="modal-body">
      <p class="share-hint">Anyone with this link can view this list (read-only).</p>
      <div class="field">
        <label for="shareUrl">Public link</label>
        <div class="share-row">
          <input type="text" id="shareUrl" readonly>
          <button class="btn btn-primary" id="shareCopy">Copy</button>
        </div>
      </div>
    </div>
    <div class="modal-foot" style="justify-content:flex-end">
      <button class="btn" data-close-share>Done</button>
    </div>
  </div>
</div>

<div class="list-menu cat-menu" id="catMenu" hidden style="position:fixed">
  <button class="lm-item" id="cmRename"><span class="material-symbols-rounded">edit</span>Rename</button>
  <button class="lm-item" id="cmSort"><span class="material-symbols-rounded">sort</span>Sort by weight</button>
  <button class="lm-item" id="cmUp"><span class="material-symbols-rounded">arrow_upward</span>Move up</button>
  <button class="lm-item" id="cmDown"><span class="material-symbols-rounded">arrow_downward</span>Move down</button>
  <div class="lm-sep"></div>
  <button class="lm-item lm-danger" id="cmDelete"><span class="material-symbols-rounded">delete</span>Delete</button>
</div>

<script>
const CSRF = <?= json_encode(csrf_token()) ?>;
const LIST_ID = <?= (int) $listId ?>;

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

const cats = document.querySelectorAll('.category');
const toggleAll = document.getElementById('toggleAll');
function syncToggleAll(){
  if(!toggleAll) return;
  const anyOpen = [...cats].some(c=>!c.classList.contains('collapsed'));
  toggleAll.querySelector('.lbl').textContent = anyOpen ? 'Collapse all' : 'Expand all';
  toggleAll.querySelector('.material-symbols-rounded').textContent = anyOpen ? 'unfold_less' : 'unfold_more';
}
document.querySelectorAll('.cat-head').forEach(h=>{
  h.addEventListener('click',e=>{
    if(e.target.closest('button'))return;
    h.parentElement.classList.toggle('collapsed');
    syncToggleAll();
  });
});
if(toggleAll) toggleAll.addEventListener('click',()=>{
  const anyOpen = [...cats].some(c=>!c.classList.contains('collapsed'));
  cats.forEach(c=>c.classList.toggle('collapsed', anyOpen));
  syncToggleAll();
});

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
const tWear=modal.querySelector('.toggle.wear'), tCons=modal.querySelector('.toggle.cons');
let editingId = null;

function setToggle(t,on){ t.classList.toggle('on',on); t.querySelector('input').checked = on; }

function openAdd(catId){
  editingId = null;
  mTitle.textContent='Add item'; mIcon.textContent='add'; mDelete.style.display='none';
  fName.value=''; fDesc.value=''; fUrl.value=''; fWeight.value=''; fQty.value='1';
  if(catId) fCat.value=String(catId);
  setToggle(tWear,false); setToggle(tCons,false);
  modal.classList.add('open'); fName.focus();
}
function openEdit(tr){
  editingId = parseInt(tr.dataset.itemId);
  mTitle.textContent='Edit item'; mIcon.textContent='edit'; mDelete.style.display='';
  fName.value=tr.dataset.name||''; fDesc.value=tr.dataset.desc||''; fUrl.value=tr.dataset.url||'';
  fWeight.value=tr.dataset.weight||''; fQty.value=tr.dataset.qty||'0';
  const catId=tr.closest('.category').dataset.catId; if(catId) fCat.value=String(catId);
  setToggle(tWear, tr.dataset.worn==='1'); setToggle(tCons, tr.dataset.consumable==='1');
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

document.getElementById('modalSave').addEventListener('click', async ()=>{
  const name=fName.value.trim();
  if(!name){ alert('Name is required'); return; }
  await api({
    action:'item_save', id:editingId||0, category_id:parseInt(fCat.value)||0,
    name, description:fDesc.value.trim(), url:fUrl.value.trim(),
    weight:parseFloat(fWeight.value)||0, qty:parseInt(fQty.value)||0,
    worn:tWear.querySelector('input').checked?1:0,
    consumable:tCons.querySelector('input').checked?1:0
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
  inp.addEventListener('change', ()=>
    inp.closest('.toggle').classList.toggle('on', inp.checked)));

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
  const name = prompt('Rename list:', <?= json_encode($data['name'] ?? '') ?>);
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
</script>

</body>
</html>
