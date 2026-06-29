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
})();
