<?php
require_once __DIR__ . '/data.php';

function h($s) { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); }
function fmtg($w) { $w = round((float) $w, 1); return $w == (int) $w ? number_format($w) : number_format($w, 1); }

$userId = 1;
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
    <button class="icon-btn" title="Share"><span class="material-symbols-rounded">share</span></button>
    <button class="icon-btn" title="Account"><span class="material-symbols-rounded">account_circle</span></button>
  </div>
</header>

<main>
<?php if (!$data): ?>
  <p style="text-align:center;color:var(--muted);padding:48px 16px">No list yet. Import a CSV or create a list to get started.</p>
<?php else: $t = $data['totals']; ?>

  <section class="summary">
    <div class="stat">
      <div class="k"><span class="material-symbols-rounded">inventory_2</span>Base weight</div>
      <div class="v"><?= fmtg($t['base']) ?> <small>g</small><span class="pct"><?= $t['base_pct'] ?>%</span></div>
    </div>
    <div class="op-sep" aria-hidden="true">+</div>
    <div class="stat">
      <div class="k"><span class="material-symbols-rounded">restaurant</span>Consumable</div>
      <div class="v"><?= fmtg($t['consumable']) ?> <small>g</small><span class="pct"><?= $t['consumable_pct'] ?>%</span></div>
    </div>
    <div class="op-sep" aria-hidden="true">=</div>
    <div class="stat">
      <div class="k"><span class="material-symbols-rounded">backpack</span>Pack weight</div>
      <div class="v"><?= fmtg($t['pack']) ?> <small>g</small><span class="pct"><?= $t['pack_pct'] ?>%</span></div>
    </div>
    <div class="op-sep" aria-hidden="true">+</div>
    <div class="stat">
      <div class="k"><span class="material-symbols-rounded">checkroom</span>Worn</div>
      <div class="v"><?= fmtg($t['worn']) ?> <small>g</small><span class="pct"><?= $t['worn_pct'] ?>%</span></div>
    </div>
    <div class="op-sep" aria-hidden="true">=</div>
    <div class="stat">
      <div class="k"><span class="material-symbols-rounded">scale</span>Total</div>
      <div class="v"><?= fmtg($t['total']) ?> <small>g</small><span class="pct">100%</span></div>
    </div>
  </section>

  <div class="list-tools">
    <button class="toggle-all" id="toggleAll">
      <span class="material-symbols-rounded">unfold_less</span><span class="lbl">Collapse all</span>
    </button>
  </div>

<?php foreach ($data['categories'] as $c): ?>
  <section class="category" data-cat-id="<?= (int) $c['id'] ?>" style="--cat:<?= h($c['color'] ?: '#cccccc') ?>">
    <div class="cat-head">
      <span class="material-symbols-rounded chev">expand_more</span>
      <span class="cat-dot"></span>
      <span class="cat-title"><?= h($c['name']) ?></span>
      <button class="cat-add" data-cat-id="<?= (int) $c['id'] ?>" title="Add item"><span class="material-symbols-rounded">add</span><span class="lbl">Add item</span></button>
      <span class="cat-meta"><b><?= count($c['items']) ?></b> items · <b><?= fmtg($c['weight']) ?></b> g · <b><?= $c['pct'] ?>%</b></span>
    </div>
    <div class="table-wrap">
      <table>
        <thead>
          <tr><th>Item</th><th class="center">Flags</th><th class="right">Weight</th><th class="center">Qty</th><th></th></tr>
        </thead>
        <tbody>
<?php foreach ($c['items'] as $it): ?>
          <tr data-item-id="<?= (int) $it['id'] ?>">
            <td class="col-item">
              <div class="item-name"><?= h($it['name']) ?></div>
<?php if (($it['description'] ?? '') !== ''): ?>
              <div class="item-desc"><?= h($it['description']) ?></div>
<?php endif; ?>
<?php if (!empty($it['url'])): ?>
              <a class="item-link" href="<?= h($it['url']) ?>" target="_blank" rel="noopener" title="Open link"><span class="material-symbols-rounded">open_in_new</span></a>
<?php endif; ?>
            </td>
            <td class="center col-meta">
              <div class="flags">
                <span class="flag<?= $it['worn'] ? ' on' : '' ?> wear" title="Worn"><span class="material-symbols-rounded">checkroom</span></span>
                <span class="flag<?= $it['consumable'] ? ' on' : '' ?> cons" title="Consumable"><span class="material-symbols-rounded">restaurant</span></span>
              </div>
              <span class="mlabel num"><span class="material-symbols-rounded">scale</span><?= fmtg($it['weight']) ?> g</span>
              <span class="mlabel"><span class="material-symbols-rounded">tag</span>×<?= (int) $it['qty'] ?></span>
            </td>
            <td class="right num col-hide-m"><?= fmtg($it['weight']) ?> g</td>
            <td class="center col-hide-m"><span class="qty"><?= (int) $it['qty'] ?></span></td>
            <td><div class="row-actions">
              <button class="icon-btn mini-btn" title="Edit"><span class="material-symbols-rounded">edit</span></button>
              <button class="icon-btn mini-btn" title="Delete"><span class="material-symbols-rounded">delete</span></button>
            </div></td>
          </tr>
<?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>
<?php endforeach; ?>

  <button class="add-cat"><span class="material-symbols-rounded">add</span>Add category</button>

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
          <option><?= h($c['name']) ?></option>
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
      <button class="btn btn-danger" data-close id="modalDelete">
        <span class="material-symbols-rounded">delete</span>Delete
      </button>
      <button class="btn" data-close>Cancel</button>
      <button class="btn btn-primary" data-close>Save</button>
    </div>
  </div>
</div>

<script>
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

const modal   = document.getElementById('itemModal');
const mTitle  = document.getElementById('modalTitle');
const mIcon   = document.getElementById('modalIcon');
const mDelete = document.getElementById('modalDelete');

function openModal(mode){
  const add = mode === 'add';
  mTitle.textContent = add ? 'Add item' : 'Edit item';
  mIcon.textContent  = add ? 'add' : 'edit';
  mDelete.style.display = add ? 'none' : '';
  modal.classList.add('open');
}
function closeModal(){ modal.classList.remove('open'); }

document.querySelectorAll('.mini-btn[title="Edit"]').forEach(b=>
  b.addEventListener('click', ()=>openModal('edit')));
document.querySelectorAll('.cat-add, .fab').forEach(b=>
  b.addEventListener('click', ()=>openModal('add')));

modal.addEventListener('click', e=>{
  if(e.target === modal || e.target.closest('[data-close]')) closeModal();
});
document.addEventListener('keydown', e=>{ if(e.key==='Escape') closeModal(); });

modal.querySelectorAll('.toggle input').forEach(inp=>
  inp.addEventListener('change', ()=>
    inp.closest('.toggle').classList.toggle('on', inp.checked)));

const qty = document.getElementById('f-qty');
modal.querySelectorAll('.step').forEach(btn=>
  btn.addEventListener('click', ()=>{
    const min = parseInt(qty.min) || 0;
    let v = (parseInt(qty.value) || 0) + parseInt(btn.dataset.step);
    qty.value = Math.max(min, v);
  }));
</script>

</body>
</html>
