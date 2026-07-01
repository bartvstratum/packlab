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

  // Cumulative chart: include/exclude base, worn and consumable items. Worn items
  // split into 1 worn unit + (qty-1) pack units, so the effective weight depends
  // on which boxes are checked; rows are re-sorted heaviest-first each time.
  (function(){
    const sec = document.getElementById('cumulative');
    if(!sec) return;
    const filters = [...sec.querySelectorAll('.cum-filter')];
    const body = sec.querySelector('.bd-body');
    const rows = [...sec.querySelectorAll('.bd-row')];
    if(!filters.length || !rows.length) return;
    // Mirror PHP color_scale(): ColorBrewer RdYlBu ramp (red -> blue).
    const stops = [[0xa5,0x00,0x26],[0xd7,0x30,0x27],[0xf4,0x6d,0x43],[0xfd,0xae,0x61],
      [0xfe,0xe0,0x90],[0xff,0xff,0xbf],[0xe0,0xf3,0xf8],[0xab,0xd9,0xe9],
      [0x74,0xad,0xd1],[0x45,0x75,0xb4],[0x31,0x36,0x95]];
    const colorScale = t=>{
      t = Math.max(0, Math.min(1, t));
      const n = stops.length-1, x = t*n, i = Math.min(n-1, Math.floor(x)), f = x-i;
      const a = stops[i], b = stops[i+1], c = j => Math.round(a[j] + (b[j]-a[j])*f);
      return 'rgb('+c(0)+','+c(1)+','+c(2)+')';
    };
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
      const vis = rows.map(r=> ({r, w: eff(r, on)}))
        .filter(x=> x.w > 0)
        .sort((a,b)=> b.w - a.w);
      rows.forEach(r=> r.hidden = true);
      const total = vis.reduce((s,x)=> s + x.w, 0);
      let run = 0, idx = 0;
      vis.forEach(({r, w})=>{
        run += w; idx++;
        const frac = total>0 ? run/total : 0, bar = r.querySelector('.bd-bar');
        // colour tracks cumulative weight at the item's start: heaviest red -> tail blue
        const cfrac = total>0 ? (run-w)/total : 0;
        r.hidden = false;
        body.appendChild(r); // reorder to heaviest-first for the current selection
        r.querySelector('.bd-num').textContent = idx;
        r.querySelector('.bd-sub').textContent = '('+fmtg0(w)+' g)';
        bar.style.width = (frac*100).toFixed(1)+'%';
        bar.style.background = colorScale(cfrac);
        r.querySelector('.bd-val').textContent = Math.round(frac*100)+'%';
      });
    }
    filters.forEach(f=> f.addEventListener('change', recompute));
  })();
})();
