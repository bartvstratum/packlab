<?php

function fmtg($w) { $w = round((float) $w, 1); return $w == (int) $w ? number_format($w) : number_format($w, 1); }
function fmtg0($w) { return number_format(round((float) $w)); }

function render_summary(array $t): void { ?>
  <section class="summary">
    <div class="stat" data-k="base">
      <div class="k"><span class="material-symbols-rounded">inventory_2</span>Base weight</div>
      <div class="v"><span class="v-num"><?= fmtg0($t['base']) ?></span> <small>g</small><span class="pct"><?= $t['base_pct'] ?>%</span></div>
    </div>
    <div class="op-sep" aria-hidden="true">+</div>
    <div class="stat" data-k="consumable">
      <div class="k"><span class="material-symbols-rounded">restaurant</span>Consumable</div>
      <div class="v"><span class="v-num"><?= fmtg0($t['consumable']) ?></span> <small>g</small><span class="pct"><?= $t['consumable_pct'] ?>%</span></div>
    </div>
    <div class="op-sep" aria-hidden="true">=</div>
    <div class="stat" data-k="pack">
      <div class="k"><span class="material-symbols-rounded">backpack</span>Pack weight</div>
      <div class="v"><span class="v-num"><?= fmtg0($t['pack']) ?></span> <small>g</small><span class="pct"><?= $t['pack_pct'] ?>%</span></div>
    </div>
    <div class="op-sep" aria-hidden="true">+</div>
    <div class="stat" data-k="worn">
      <div class="k"><span class="material-symbols-rounded">checkroom</span>Worn</div>
      <div class="v"><span class="v-num"><?= fmtg0($t['worn']) ?></span> <small>g</small><span class="pct"><?= $t['worn_pct'] ?>%</span></div>
    </div>
    <div class="op-sep" aria-hidden="true">=</div>
    <div class="stat" data-k="total">
      <div class="k"><span class="material-symbols-rounded">scale</span>Total</div>
      <div class="v"><span class="v-num"><?= fmtg0($t['total']) ?></span> <small>g</small><span class="pct">100%</span></div>
    </div>
  </section>
<?php }

function render_categories(array $cats, bool $editable): void {
  foreach ($cats as $c): ?>
  <section class="category" data-cat-id="<?= (int) $c['id'] ?>" style="--cat:<?= h($c['color'] ?: '#cccccc') ?>">
    <div class="cat-head">
      <span class="material-symbols-rounded chev">expand_more</span>
      <span class="cat-dot"></span>
      <span class="cat-title"><?= h($c['name']) ?></span>
<?php if ($editable): ?>
      <button class="cat-add" data-cat-id="<?= (int) $c['id'] ?>" title="Add item"><span class="material-symbols-rounded">add</span><span class="lbl">Add item</span></button>
<?php endif; ?>
      <span class="cat-meta"><b><?= count($c['items']) ?></b> items · <b><?= fmtg0($c['weight']) ?></b> g · <b><?= $c['pct'] ?>%</b></span>
<?php if ($editable): ?>
      <button class="icon-btn mini-btn cat-menu-btn" data-cat-id="<?= (int) $c['id'] ?>" data-cat-name="<?= h($c['name']) ?>" title="Category options"><span class="material-symbols-rounded">more_vert</span></button>
<?php endif; ?>
    </div>
    <div class="table-wrap">
      <table>
        <thead>
          <tr><?php if ($editable): ?><th class="col-check"></th><?php endif; ?><th class="col-item">Item</th><th class="center">Flags</th><th class="right">Weight</th><th class="center">Qty</th><?php if ($editable): ?><th></th><?php endif; ?></tr>
        </thead>
        <tbody>
<?php foreach ($c['items'] as $it): ?>
          <tr class="<?= (int) $it['qty'] === 0 ? 'qty0' : '' ?>" data-item-id="<?= (int) $it['id'] ?>"<?php if ($editable): ?> data-name="<?= h($it['name']) ?>" data-desc="<?= h($it['description'] ?? '') ?>" data-url="<?= h($it['url'] ?? '') ?>" data-weight="<?= h($it['weight']) ?>" data-qty="<?= (int) $it['qty'] ?>" data-worn="<?= (int) $it['worn'] ?>" data-consumable="<?= (int) $it['consumable'] ?>" data-flag="<?= (int) $it['flag'] ?>" data-big3="<?= (int) $it['big3'] ?>" data-packed="<?= (int) $it['packed'] ?>"<?php endif; ?>>
<?php if ($editable): ?>
            <td class="col-check center"><span class="pack-check<?= $it['packed'] ? ' on' : '' ?>" role="button" tabindex="0" title="Packed"><span class="material-symbols-rounded">check</span></span></td>
<?php endif; ?>
            <td class="col-item">
              <div class="item-name"><?= h($it['name']) ?></div>
<?php if (($it['description'] ?? '') !== ''): ?>
              <div class="item-desc"><?= h($it['description']) ?></div>
<?php endif; ?>
<?php if ($it['big3']): ?><span class="big3-badge material-symbols-rounded" title="Big 3 item">looks_3</span><?php endif; ?>
<?php if (($itUrl = safe_url($it['url'] ?? null)) !== null): ?>
              <a class="item-link" href="<?= h($itUrl) ?>" target="_blank" rel="noopener" title="Open link"><span class="material-symbols-rounded">open_in_new</span></a>
<?php endif; ?>
            </td>
            <td class="center col-meta">
              <div class="flags">
                <span class="flag<?= $it['flag'] ? ' on' : '' ?> mark<?= $editable ? ' flag-btn' : '' ?>"<?php if ($editable): ?> data-flag="flag" role="button" tabindex="0"<?php endif; ?> title="Flagged"><span class="material-symbols-rounded">flag</span></span>
                <span class="flag-sep" aria-hidden="true"></span>
                <span class="flag<?= $it['worn'] ? ' on' : '' ?> wear<?= $editable ? ' flag-btn' : '' ?>"<?php if ($editable): ?> data-flag="worn" role="button" tabindex="0"<?php endif; ?> title="Worn"><span class="material-symbols-rounded">checkroom</span></span>
                <span class="flag<?= $it['consumable'] ? ' on' : '' ?> cons<?= $editable ? ' flag-btn' : '' ?>"<?php if ($editable): ?> data-flag="consumable" role="button" tabindex="0"<?php endif; ?> title="Consumable"><span class="material-symbols-rounded">restaurant</span></span>
              </div>
              <span class="mlabel num"><span class="material-symbols-rounded">scale</span><?= fmtg($it['weight']) ?> g</span>
              <span class="mlabel"><span class="material-symbols-rounded">tag</span>×<?= (int) $it['qty'] ?></span>
            </td>
            <td class="right num col-hide-m"><?= fmtg($it['weight']) ?> g</td>
            <td class="center col-hide-m"><span class="qty"><?= (int) $it['qty'] ?></span></td>
<?php if ($editable): ?>
            <td><div class="row-actions">
              <button class="icon-btn mini-btn" title="Edit"><span class="material-symbols-rounded">edit</span></button>
              <button class="icon-btn mini-btn" title="Delete"><span class="material-symbols-rounded">delete</span></button>
            </div></td>
<?php endif; ?>
          </tr>
<?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>
<?php endforeach;
}

function render_big3(array $big3): void {
  if (empty($big3['items'])) return;
  $items = $big3['items']; // sorted heaviest-first in list_full
  $shown = array_slice($items, 0, 4);
  $rest  = array_slice($items, 4);
  $parts = [];
  foreach ($shown as $it) {
    $parts[] = h($it['name']) . ' ' . fmtg0($it['weight']);
  }
  if ($rest) {
    $parts[] = 'rest ' . fmtg0(array_sum(array_column($rest, 'weight')));
  } ?>
  <div class="big3">
    <span class="big3-tag"><span class="material-symbols-rounded">looks_3</span>Big 3</span>
    <span class="big3-val"><b><?= fmtg0($big3['weight']) ?></b> g · <b><?= $big3['pct'] ?>%</b> of base</span>
    <span class="big3-items"><?= implode(' · ', $parts) ?></span>
  </div>
<?php }

function render_breakdown(array $cats): void {
  $rows = array_values(array_filter($cats, fn($c) => (float) $c['weight'] > 0));
  if (!$rows) return;
  usort($rows, fn($a, $b) => $b['weight'] <=> $a['weight']);
  $max = (float) $rows[0]['weight'] ?: 1; ?>
  <section class="breakdown collapsed" id="breakdown">
    <div class="bd-head">
      <span class="material-symbols-rounded chev">expand_more</span>
      <span class="bd-title">Category breakdown</span>
    </div>
    <div class="bd-body">
<?php foreach ($rows as $c): ?>
      <div class="bd-row">
        <span class="bd-name"><?= h($c['name']) ?></span>
        <span class="bd-track"><span class="bd-bar" style="width:<?= round((float) $c['weight'] / $max * 100, 1) ?>%;background:<?= h($c['color'] ?: '#cccccc') ?>"></span></span>
        <span class="bd-val"><b><?= fmtg0($c['weight']) ?></b> g · <?= $c['pct'] ?>%</span>
      </div>
<?php endforeach; ?>
    </div>
  </section>
<?php }

// Per-item weight chart, heaviest first, with each bar tinted by its pack
// category colour. The client can toggle a cumulative view (running % climbing
// to 100%) and include/exclude base/worn/consumable items.
function render_cumulative(array $cats, float $total): void {
  $items = [];
  foreach ($cats as $c) {
    foreach ($c['items'] as $it) {
      $w = (float) $it['weight'];
      $qty = (int) $it['qty'];
      $lw = (float) ($it['line_weight'] ?? $w * $qty);
      if ($lw <= 0) continue;
      $cat = !empty($it['worn']) ? 'worn' : (!empty($it['consumable']) ? 'consumable' : 'base');
      // 'w' is the initial (all-filters-on) line weight; unit weight + qty let the
      // client recompute when worn items get split (1 unit worn, rest in the pack).
      // 'color' is the item's category colour — bars are tinted by category.
      $items[] = ['name' => $it['name'], 'w' => $lw, 'unit' => $w, 'qty' => $qty, 'cat' => $cat, 'color' => $c['color'] ?: '#cccccc'];
    }
  }
  if (!$items) return;
  usort($items, fn($a, $b) => $b['w'] <=> $a['w']);
  $total = $total ?: array_sum(array_column($items, 'w')); ?>
  <section class="breakdown collapsed per-item" id="cumulative">
    <div class="bd-head">
      <span class="material-symbols-rounded chev">expand_more</span>
      <span class="bd-title">Item weights</span>
    </div>
    <div class="bd-body">
      <div class="bd-filters">
        <label><input type="checkbox" class="cum-filter" value="base" checked> Base</label>
        <label><input type="checkbox" class="cum-filter" value="worn" checked> Worn</label>
        <label><input type="checkbox" class="cum-filter" value="consumable" checked> Consumables</label>
        <span class="bd-filter-sep" aria-hidden="true"></span>
        <label><input type="checkbox" id="cumToggle"> Cumulative</label>
      </div>
<?php $max = (float) $items[0]['w']; foreach ($items as $idx => $it):
        $share = $total > 0 ? $it['w'] / $total * 100 : 0;
        $width = $max > 0 ? $it['w'] / $max * 100 : 0; ?>
      <div class="bd-row" data-cat="<?= $it['cat'] ?>" data-unit="<?= $it['unit'] ?>" data-qty="<?= $it['qty'] ?>">
        <span class="bd-num"><?= $idx + 1 ?></span>
        <span class="bd-name"><?= h($it['name']) ?> <span class="bd-sub">(<?= fmtg0($it['w']) ?> g)</span></span>
        <span class="bd-track"><span class="bd-bar" style="width:<?= round($width, 1) ?>%;background:<?= h($it['color']) ?>"></span></span>
        <span class="bd-val"><?= number_format($share, 1) ?>%</span>
      </div>
<?php endforeach; ?>
    </div>
  </section>
<?php }

function render_list(array $data, bool $editable): void { ?>
  <div class="analysis" id="analysis">
<?php
  render_summary($data['totals']);
  render_big3($data['big3'] ?? ['items' => []]);
  render_cumulative($data['categories'], (float) ($data['totals']['total'] ?? 0));
  render_breakdown($data['categories']); ?>
  </div>
  <div class="list-tools">
<?php if ($editable): ?>
    <button class="toggle-all checklist-only" id="resetChecklist" style="margin-right:auto">
      <span class="material-symbols-rounded">restart_alt</span><span class="lbl">Reset checklist</span>
    </button>
    <button class="toggle-all" id="sortCats" title="Sort categories and all items by weight">
      <span class="material-symbols-rounded">sort</span><span class="lbl">Sort by weight</span>
    </button>
<?php endif; ?>
    <button class="toggle-all" id="toggleAll">
      <span class="material-symbols-rounded">unfold_less</span><span class="lbl">Collapse all</span>
    </button>
  </div>
<?php render_categories($data['categories'], $editable);
  if ($editable): ?>
  <button class="add-cat"><span class="material-symbols-rounded">add</span>Add category</button>
<?php endif;
}
