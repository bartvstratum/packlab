<?php

require_once __DIR__ . '/auth.php';

if (current_user_id()) {
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (login(trim($_POST['email'] ?? ''), $_POST['password'] ?? '')) {
        header('Location: index.php');
        exit;
    }
    $error = 'Invalid email or password';
}

function h($s) { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>PackLab — Log in</title>
<link rel="icon" type="image/svg+xml" href="favicon.svg">
<meta name="theme-color" content="#2563eb">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,400,0,0" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<style>
.login-wrap{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
.login-card{background:var(--surface);border:1px solid var(--border);border-radius:16px;box-shadow:var(--shadow);width:100%;max-width:360px;padding:26px}
.login-brand{display:flex;align-items:center;gap:9px;font-weight:700;font-size:20px;justify-content:center;margin-bottom:20px}
.login-brand .material-symbols-rounded{color:var(--accent);font-size:30px}
.login-card .field{margin-bottom:14px}
.login-card .btn-primary{width:100%;justify-content:center;display:flex}
.login-error{background:#fdeaea;color:#b3261e;border-radius:9px;padding:9px 12px;font-size:13px;font-weight:600;margin-bottom:14px}
</style>
</head>
<body>
<div class="login-wrap">
  <form class="login-card" method="post" action="login.php">
    <div class="login-brand">
      <span class="material-symbols-rounded">balance</span>PackLab
    </div>
    <?php if ($error): ?><div class="login-error"><?= h($error) ?></div><?php endif; ?>
    <div class="field">
      <label for="email">Email</label>
      <input type="email" id="email" name="email" required autofocus value="<?= h($_POST['email'] ?? '') ?>">
    </div>
    <div class="field">
      <label for="password">Password</label>
      <input type="password" id="password" name="password" required>
    </div>
    <button type="submit" class="btn btn-primary">Log in</button>
  </form>
</div>
</body>
</html>
