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
  [['cumulative','pl_cumulative_collapsed'], ['breakdown','pl_breakdown_collapsed'], ['treemap','pl_treemap_collapsed']].forEach(([id, key])=>{
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

  // Treemap: one flat squarified treemap of all items, sized by effective weight
  // (worn items split like the item-weights chart), coloured by pack category.
  (function(){
    const sec = document.getElementById('treemap');
    const chart = document.getElementById('tmChart');
    const dataEl = document.getElementById('tmData');
    if(!sec || !chart || !dataEl) return;
    let items;
    try { items = JSON.parse(dataEl.textContent); } catch(e){ return; }
    if(!items.length) return;
    const filters = [...sec.querySelectorAll('.tm-filter')];
    const fmtg0 = g => Math.round(g).toLocaleString('en-US');
    const esc = s => String(s).replace(/[&<>"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));
    const catLabel = {base:'Base', worn:'Worn', consumable:'Consumable'};
    const num = v => (Math.round(v*10)/10);

    // Effective weight: worn = 1 unit worn + (qty-1) in the pack (base).
    function eff(it, on){
      if(it.cat === 'worn') return (on.worn ? it.unit : 0) + (on.base ? it.unit*(it.qty-1) : 0);
      return on[it.cat] ? it.unit*it.qty : 0;
    }

    // Squarified treemap (Bruls, Huizing & van Wijk).
    function squarify(nodes, W, H){
      const total = nodes.reduce((s,n)=> s+n.value, 0);
      if(total<=0) return [];
      const scale = (W*H)/total;
      const q = nodes.map(n=> ({n, area:n.value*scale}));
      const out = [];
      let rect = {x:0, y:0, w:W, h:H}, row = [];
      const sum = r => r.reduce((a,x)=> a+x.area, 0);
      const worst = (r, side)=>{
        const s = sum(r); let mx=0, mn=Infinity;
        for(const x of r){ if(x.area>mx)mx=x.area; if(x.area<mn)mn=x.area; }
        const s2=s*s, d2=side*side;
        return Math.max(d2*mx/s2, s2/(d2*mn));
      };
      const lay = (r, rc)=>{
        const s = sum(r);
        if(rc.w >= rc.h){
          const t = s/rc.h; let cy=rc.y;
          for(const x of r){ const nh=x.area/t; out.push({n:x.n, x:rc.x, y:cy, w:t, h:nh}); cy+=nh; }
          return {x:rc.x+t, y:rc.y, w:rc.w-t, h:rc.h};
        }
        const t = s/rc.w; let cx=rc.x;
        for(const x of r){ const nw=x.area/t; out.push({n:x.n, x:cx, y:rc.y, w:nw, h:t}); cx+=nw; }
        return {x:rc.x, y:rc.y+t, w:rc.w, h:rc.h-t};
      };
      while(q.length){
        const side = Math.min(rect.w, rect.h);
        if(row.length===0 || worst(row, side) >= worst(row.concat(q[0]), side)){
          row.push(q.shift());
        } else { rect = lay(row, rect); row = []; }
      }
      if(row.length) lay(row, rect);
      return out;
    }

    let tip;
    function showTip(g, e){
      if(!tip){ tip = document.createElement('div'); tip.className='tm-tip'; }
      if(tip.parentNode !== chart) chart.appendChild(tip);
      tip.textContent = '';
      const b = document.createElement('b'); b.textContent = g.dataset.name;
      const sub = document.createElement('div'); sub.className='tm-tip-sub';
      sub.textContent = g.dataset.category + ' · ' + g.dataset.cat;
      const wl = document.createElement('div');
      wl.textContent = g.dataset.w + ' g · ' + g.dataset.pct + '%';
      tip.append(b, sub, wl);
      tip.classList.add('on');
      const cr = chart.getBoundingClientRect();
      let x = e.clientX - cr.left + 12, y = e.clientY - cr.top + 12;
      if(x + tip.offsetWidth > cr.width) x = e.clientX - cr.left - tip.offsetWidth - 12;
      if(y + tip.offsetHeight > cr.height) y = cr.height - tip.offsetHeight - 4;
      tip.style.left = Math.max(0,x)+'px';
      tip.style.top = Math.max(0,y)+'px';
    }
    function hideTip(){ if(tip) tip.classList.remove('on'); }

    function render(){
      const W = chart.clientWidth;
      if(W <= 0) return; // collapsed / not yet visible
      const H = Math.round(Math.min(Math.max(W*0.55, 240), 420));
      const on = {}; filters.forEach(f=> on[f.value] = f.checked);
      const list = items.map(it=> ({value: eff(it, on), it}))
        .filter(x=> x.value > 0)
        .sort((a,b)=> b.value - a.value);
      if(!list.length){ chart.innerHTML = '<p class="tm-empty">No items selected.</p>'; return; }
      const total = list.reduce((s,x)=> s+x.value, 0);
      const rects = squarify(list, W, H);
      let cells = '';
      for(const r of rects){
        const it = r.n.it, w = r.n.value, pct = total>0 ? w/total*100 : 0;
        let label = '';
        if(r.w >= 46 && r.h >= 18){
          const maxc = Math.floor((r.w-8)/6.2);
          let nm = it.name;
          if(nm.length > maxc) nm = nm.slice(0, Math.max(1,maxc-1)) + '…';
          label = '<text class="tm-label" x="'+num(r.x+5)+'" y="'+num(r.y+5)+'">'+esc(nm)+'</text>';
        }
        cells += '<g class="tm-cell" data-name="'+esc(it.name)+'" data-category="'+esc(it.category)+
          '" data-cat="'+catLabel[it.cat]+'" data-w="'+fmtg0(w)+'" data-pct="'+pct.toFixed(1)+'">'+
          '<rect x="'+num(r.x)+'" y="'+num(r.y)+'" width="'+num(Math.max(0,r.w))+'" height="'+num(Math.max(0,r.h))+
          '" rx="2" fill="'+esc(it.color)+'"></rect>'+label+'</g>';
      }
      chart.innerHTML = '<svg width="'+W+'" height="'+H+'">'+cells+'</svg>';
      const svg = chart.querySelector('svg');
      svg.addEventListener('mousemove', e=>{
        const g = e.target.closest('.tm-cell');
        if(g) showTip(g, e); else hideTip();
      });
      svg.addEventListener('mouseleave', hideTip);
    }

    filters.forEach(f=> f.addEventListener('change', render));
    let raf;
    new ResizeObserver(()=>{ cancelAnimationFrame(raf); raf = requestAnimationFrame(render); }).observe(chart);
  })();
})();
