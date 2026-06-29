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
<?php $pageTitle = $data ? h($data['name']) . ' — PackLab' : 'PackLab'; require __DIR__ . '/head.php'; ?>
<meta name="robots" content="noindex">
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

<script src="collapse.js?v=1"></script>

</body>
</html>
