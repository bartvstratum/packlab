<?php
require_once __DIR__ . '/data.php';
require_once __DIR__ . '/render.php';

$token = $_GET['token'] ?? '';
$list = is_string($token) && $token !== '' ? list_by_token($token) : null;
if (!$list) {
    http_response_code(404);
}
$data = $list ? list_full((int) $list['id']) : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $data ? h($data['name']) . ' — PackLab' : 'PackLab' ?></title>
<link rel="icon" type="image/svg+xml" href="favicon.svg">
<meta name="theme-color" content="#2563eb">
<meta name="robots" content="noindex">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,400,0,0" rel="stylesheet">
<link rel="stylesheet" href="style.css">
</head>
<body>

<header>
  <div class="bar">
    <a class="brand" href="index.php" style="text-decoration:none;color:inherit">
      <span class="material-symbols-rounded brand-logo">balance</span>
      <span class="name">PackLab</span>
    </a>
    <div class="spacer"></div>
<?php if ($data): ?>
    <span class="ro-badge"><span class="material-symbols-rounded">visibility</span><?= h($data['name']) ?></span>
<?php endif; ?>
  </div>
</header>

<main>
<?php if (!$data): ?>
  <p style="text-align:center;color:var(--muted);padding:48px 16px">This shared list was not found, or sharing has been turned off.</p>
<?php else: ?>
  <?php render_list($data, false); ?>
  <p style="text-align:center;color:var(--muted);font-size:13px;padding:8px 16px 0">
    Made with <a href="https://github.com/bartvstratum/packlab" target="_blank" rel="noopener" style="color:var(--accent);font-weight:600;text-decoration:none">PackLab</a>
  </p>
<?php endif; ?>
</main>

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
</script>

</body>
</html>
