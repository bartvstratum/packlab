'use strict';
// Category collapse / expand-all. Shared by the owner view and public share view.
(function(){
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

  // Analysis charts: collapsed by default; expand only if the user did so before.
  [['cumulative','pl_cumulative_collapsed'], ['breakdown','pl_breakdown_collapsed']].forEach(([id, key])=>{
    const sec = document.getElementById(id);
    if(!sec) return;
    if(localStorage.getItem(key)==='0') sec.classList.remove('collapsed');
    sec.querySelector('.bd-head').addEventListener('click', ()=>{
      sec.classList.toggle('collapsed');
      localStorage.setItem(key, sec.classList.contains('collapsed')?'1':'0');
    });
  });

  // Item-weights chart: include/exclude base, worn and consumable items, with an
  // optional cumulative view. Worn items split into 1 worn unit + (qty-1) pack
  // units, so effective weight depends on which boxes are checked; rows are
  // re-sorted heaviest-first each time. Bar colour comes from CSS (per category).
  (function(){
    const sec = document.getElementById('cumulative');
    if(!sec) return;
    const filters = [...sec.querySelectorAll('.cum-filter')];
    const cumToggle = sec.querySelector('#cumToggle');
    const body = sec.querySelector('.bd-body');
    const rows = [...sec.querySelectorAll('.bd-row')];
    if(!filters.length || !rows.length) return;
    const fmtg0 = g => Math.round(g).toLocaleString('en-US');
    // Effective weight of a row given which categories are on.
    function eff(r, on){
      const unit = parseFloat(r.dataset.unit), qty = parseInt(r.dataset.qty), cat = r.dataset.cat;
      if(cat === 'worn') return (on.worn ? unit : 0) + (on.base ? unit*(qty-1) : 0);
      return on[cat] ? unit*qty : 0;
    }
    function recompute(){
      const on = {};
      filters.forEach(f=> on[f.value] = f.checked);
      const cumulative = cumToggle.checked;
      sec.classList.toggle('per-item', !cumulative);
      const vis = rows.map(r=> ({r, w: eff(r, on)}))
        .filter(x=> x.w > 0)
        .sort((a,b)=> b.w - a.w);
      rows.forEach(r=> r.hidden = true);
      const total = vis.reduce((s,x)=> s + x.w, 0);
      const maxW = vis.length ? vis[0].w : 0;
      let run = 0, idx = 0;
      vis.forEach(({r, w})=>{
        run += w; idx++;
        const bar = r.querySelector('.bd-bar');
        r.hidden = false;
        body.appendChild(r); // reorder to heaviest-first for the current selection
        r.querySelector('.bd-num').textContent = idx;
        r.querySelector('.bd-sub').textContent = '('+fmtg0(w)+' g)';
        // cumulative: bar climbs to 100% of the shown total; otherwise each bar is
        // the item's weight relative to the heaviest shown item.
        const width = cumulative ? (total>0 ? run/total : 0) : (maxW>0 ? w/maxW : 0);
        bar.style.width = (width*100).toFixed(1)+'%';
        const share = cumulative ? (total>0 ? run/total : 0) : (total>0 ? w/total : 0);
        r.querySelector('.bd-val').textContent = (share*100).toFixed(1)+'%';
      });
    }
    filters.forEach(f=> f.addEventListener('change', recompute));
    if(cumToggle) cumToggle.addEventListener('change', recompute);
  })();
})();
