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
})();
