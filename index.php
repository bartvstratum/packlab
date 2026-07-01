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
<?php $pageTitle = 'PackLab'; require __DIR__ . '/head.php'; ?>
<script>
(function(){var d=document.documentElement;
if(localStorage.getItem('pl_opt_summary')==='0')d.classList.add('hide-summary');
if(localStorage.getItem('pl_opt_cumulative')==='0')d.classList.add('hide-cumulative');
if(localStorage.getItem('pl_opt_breakdown')==='0')d.classList.add('hide-breakdown');
if(localStorage.getItem('pl_opt_checklist')==='1')d.classList.add('show-checklist');})();
</script>
</head>
<body>

<header>
  <div class="bar">
    <div class="brand">
      <span class="material-symbols-rounded brand-logo">balance</span>
      <span class="name">PackLab</span>
      <a class="brand-gh" href="https://github.com/bartvstratum/packlab" target="_blank" rel="noopener" title="View on GitHub" aria-label="View on GitHub">
        <svg viewBox="0 0 16 16" aria-hidden="true"><path d="M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27.68 0 1.36.09 2 .27 1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.013 8.013 0 0016 8c0-4.42-3.58-8-8-8z"/></svg>
      </a>
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
        <button class="lm-item" id="lmDuplicate"><span class="material-symbols-rounded">content_copy</span>Duplicate list</button>
        <a class="lm-item" href="export.php?list=<?= $listId ?>"><span class="material-symbols-rounded">download</span>Export CSV</a>
<?php endif; ?>
        <button class="lm-item" id="lmImport"><span class="material-symbols-rounded">upload</span>Import CSV</button>
<?php if ($data): ?>
        <button class="lm-item lm-danger" id="lmDelete"><span class="material-symbols-rounded">delete</span>Delete list</button>
<?php endif; ?>
      </div>
    </div>
    <input type="file" id="importFile" accept=".csv,text/csv" hidden>
<?php if ($data): ?>
    <div class="opt-wrap">
      <button class="icon-btn" id="optBtn" title="Options"><span class="material-symbols-rounded">tune</span></button>
      <div class="list-menu opt-menu" id="optMenu" hidden>
        <label class="lm-item opt-item"><input type="checkbox" id="optSummary">Summary &amp; Big 3</label>
        <label class="lm-item opt-item"><input type="checkbox" id="optCumulative">Cumulative weight</label>
        <label class="lm-item opt-item"><input type="checkbox" id="optBreakdown">Category breakdown</label>
        <label class="lm-item opt-item"><input type="checkbox" id="optChecklist">Pack checklist</label>
      </div>
    </div>
<?php endif; ?>
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
      <div class="field ac-field">
        <label for="f-name">Name</label>
        <input type="text" id="f-name" placeholder="e.g. Tent" autocomplete="off">
        <div class="ac-list" id="acList" hidden></div>
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
          <label class="toggle mark">
            <input type="checkbox"><span class="material-symbols-rounded">flag</span>Flag
          </label>
          <label class="toggle big3">
            <input type="checkbox"><span class="material-symbols-rounded">looks_3</span>Big 3
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
const PL = {
  csrf: <?= json_encode(csrf_token()) ?>,
  listId: <?= (int) $listId ?>,
  listName: <?= json_encode($data['name'] ?? '') ?>,
  items: <?= json_encode(array_map(fn($it) => ['name' => $it['name'], 'weight' => (float) $it['weight'], 'desc' => $it['description'], 'url' => $it['url']], items_for_user($userId))) ?>
};
</script>
<script src="collapse.js?v=5"></script>
<script src="app.js?v=8"></script>

</body>
</html>
