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
    <button class="list-switch" title="Switch list">
      <span class="material-symbols-rounded">checklist</span>
      <span class="label"><?= h($data['name'] ?? 'No list') ?></span>
      <span class="material-symbols-rounded">expand_more</span>
    </button>
    <button class="icon-btn" id="shareBtn" title="Share"><span class="material-symbols-rounded">share</span></button>
    <a class="icon-btn" href="logout.php" title="Log out"><span class="material-symbols-rounded">logout</span></a>
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
</script>

</body>
</html>
