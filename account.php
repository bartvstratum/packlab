<?php

require_once __DIR__ . '/auth.php';

$uid = current_user_id();
if ($uid === null) {
    header('Location: login.php');
    exit;
}

$user = user_get($uid);
$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? null)) {
        $err = 'Session expired, please try again.';
    } else {
        $cur  = $_POST['current'] ?? '';
        $new  = $_POST['new'] ?? '';
        $conf = $_POST['confirm'] ?? '';
        if (!user_verify($user, $cur)) {
            $err = 'Current password is incorrect.';
        } elseif (strlen($new) < 8) {
            $err = 'New password must be at least 8 characters.';
        } elseif ($new !== $conf) {
            $err = 'New passwords do not match.';
        } else {
            user_set_password($uid, $new);
            $msg = 'Password updated.';
        }
    }
}

function h($s) { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Account — PackLab</title>
<link rel="icon" type="image/svg+xml" href="favicon.svg">
<meta name="theme-color" content="#2f8f5b">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,400,0,0" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<style>
.acct{max-width:480px;margin:0 auto;padding:18px 16px 60px}
.acct-id{display:flex;align-items:center;gap:13px;padding:16px;background:var(--surface);border:1px solid var(--border);border-radius:12px;box-shadow:var(--shadow);margin-bottom:16px}
.acct-id .material-symbols-rounded{font-size:40px;color:var(--accent)}
.acct-id .acct-label{font-size:12px;color:var(--muted);font-weight:500}
.acct-id .acct-mail{font-size:17px;font-weight:700;word-break:break-all;line-height:1.2;margin-top:2px}
.acct-section{background:var(--surface);border:1px solid var(--border);border-radius:12px;box-shadow:var(--shadow);padding:16px;margin-bottom:16px}
.acct-section h2{font-size:15px;margin:0 0 13px}
.acct-section .field{margin-bottom:12px}
.acct-section .btn-primary{display:flex;justify-content:center;width:100%}
.acct-sub{font-size:13px;color:var(--muted);margin:0 0 13px}
.btn-logout{display:flex;align-items:center;justify-content:center;gap:6px;width:100%;border-color:#f0c2c2;color:#d24b4b;background:var(--surface);text-decoration:none}
.btn-logout:hover{background:#fdeaea}
.btn-logout .material-symbols-rounded{font-size:19px}
.acct-msg{border-radius:9px;padding:9px 12px;font-size:13px;font-weight:600;margin-bottom:13px}
.acct-msg.ok{background:var(--accent-soft);color:#2a6f49}
.acct-msg.err{background:#fdeaea;color:#b3261e}
</style>
</head>
<body>

<header>
  <div class="bar">
    <a class="brand" href="index.php" style="text-decoration:none;color:inherit">
      <span class="material-symbols-rounded brand-logo">balance</span>
      <span class="name">PackLab</span>
    </a>
    <div class="spacer"></div>
    <a class="icon-btn" href="index.php" title="Back to list"><span class="material-symbols-rounded">close</span></a>
  </div>
</header>

<main class="acct">

  <div class="acct-id">
    <span class="material-symbols-rounded">account_circle</span>
    <div>
      <div class="acct-label">Signed in as</div>
      <div class="acct-mail"><?= h($user['email']) ?></div>
    </div>
  </div>

  <section class="acct-section">
    <h2>Change password</h2>
    <?php if ($msg): ?><div class="acct-msg ok"><?= h($msg) ?></div><?php endif; ?>
    <?php if ($err): ?><div class="acct-msg err"><?= h($err) ?></div><?php endif; ?>
    <form method="post" action="account.php">
      <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
      <div class="field">
        <label for="current">Current password</label>
        <input type="password" id="current" name="current" required autocomplete="current-password">
      </div>
      <div class="field">
        <label for="new">New password</label>
        <input type="password" id="new" name="new" required minlength="8" autocomplete="new-password">
      </div>
      <div class="field">
        <label for="confirm">Confirm new password</label>
        <input type="password" id="confirm" name="confirm" required minlength="8" autocomplete="new-password">
      </div>
      <button type="submit" class="btn btn-primary">Update password</button>
    </form>
  </section>

  <section class="acct-section">
    <h2>Log out</h2>
    <p class="acct-sub">End your session on this device.</p>
    <a href="logout.php" class="btn btn-logout"><span class="material-symbols-rounded">logout</span>Log out</a>
  </section>

</main>
</body>
</html>
