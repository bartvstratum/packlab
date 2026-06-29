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

?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php $pageTitle = 'PackLab — Log in'; require __DIR__ . '/head.php'; ?>
<style>
.login-wrap{min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:14px;padding:20px}
.login-card{background:var(--surface);border:1px solid var(--border);border-radius:16px;box-shadow:var(--shadow);width:100%;max-width:360px;padding:26px}
.login-brand{display:flex;align-items:center;gap:9px;font-weight:700;font-size:20px;justify-content:center;margin-bottom:20px}
.login-brand .material-symbols-rounded{color:var(--accent);font-size:30px}
.login-card .field{margin-bottom:14px}
.login-card .btn-primary{width:100%;justify-content:center;display:flex}
.login-error{background:#fdeaea;color:#b3261e;border-radius:9px;padding:9px 12px;font-size:13px;font-weight:600;margin-bottom:14px}
.demo-cta{width:100%;max-width:360px;text-align:center;font-size:13px;color:var(--muted)}
.demo-cta code{background:var(--surface);border:1px solid var(--border);border-radius:5px;padding:1px 6px;font-size:12px;font-weight:700;color:var(--text)}
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
      <input type="text" id="email" name="email" required autofocus value="<?= h($_POST['email'] ?? '') ?>">
    </div>
    <div class="field">
      <label for="password">Password</label>
      <input type="password" id="password" name="password" required>
    </div>
    <button type="submit" class="btn btn-primary">Log in</button>
  </form>

  <p class="demo-cta">Just exploring? Log in with <code>demo</code> / <code>demo</code>.</p>
</div>
</body>
</html>
