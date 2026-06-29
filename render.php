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
          <tr><th>Item</th><th class="center">Flags</th><th class="right">Weight</th><th class="center">Qty</th><?php if ($editable): ?><th></th><?php endif; ?></tr>
        </thead>
        <tbody>
<?php foreach ($c['items'] as $it): ?>
          <tr data-item-id="<?= (int) $it['id'] ?>"<?php if ($editable): ?> data-name="<?= h($it['name']) ?>" data-desc="<?= h($it['description'] ?? '') ?>" data-url="<?= h($it['url'] ?? '') ?>" data-weight="<?= h($it['weight']) ?>" data-qty="<?= (int) $it['qty'] ?>" data-worn="<?= (int) $it['worn'] ?>" data-consumable="<?= (int) $it['consumable'] ?>" data-flag="<?= (int) $it['flag'] ?>"<?php endif; ?>>
            <td class="col-item">
              <div class="item-name"><?= h($it['name']) ?></div>
<?php if (($it['description'] ?? '') !== ''): ?>
              <div class="item-desc"><?= h($it['description']) ?></div>
<?php endif; ?>
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

function render_list(array $data, bool $editable): void {
  render_summary($data['totals']); ?>
  <div class="list-tools">
<?php if ($editable): ?>
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
