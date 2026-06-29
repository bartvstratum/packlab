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

  // Category breakdown chart: collapse on its own (persisted)
  const breakdown = document.getElementById('breakdown');
  if(breakdown){
    if(localStorage.getItem('pl_breakdown_collapsed')==='1') breakdown.classList.add('collapsed');
    breakdown.querySelector('.bd-head').addEventListener('click', ()=>{
      breakdown.classList.toggle('collapsed');
      localStorage.setItem('pl_breakdown_collapsed', breakdown.classList.contains('collapsed')?'1':'0');
    });
  }

  // Whole top analysis: hide for a list-only view (persisted)
  const analysis = document.getElementById('analysis');
  const toggleAnalysis = document.getElementById('toggleAnalysis');
  if(analysis && toggleAnalysis){
    const syncAnalysis = ()=>{
      toggleAnalysis.querySelector('.lbl').textContent =
        analysis.classList.contains('collapsed') ? 'Show analysis' : 'Hide analysis';
    };
    if(localStorage.getItem('pl_analysis_hidden')==='1') analysis.classList.add('collapsed');
    syncAnalysis();
    toggleAnalysis.addEventListener('click', ()=>{
      analysis.classList.toggle('collapsed');
      localStorage.setItem('pl_analysis_hidden', analysis.classList.contains('collapsed')?'1':'0');
      syncAnalysis();
    });
  }
})();
