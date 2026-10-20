<?php
// login.php
require_once 'config.php';
$count = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $u = trim($_POST['username'] ?? '');
    $p = $_POST['password'] ?? '';
    if ($count === 0) {
        if ($u === '' || strlen($p) < 6) {
            $error = 'نام کاربری لازم است و رمز باید حداقل ۶ کاراکتر باشد.';
        } else {
            $pdo->prepare("INSERT INTO users (username, password_hash) VALUES (?, ?)")
                ->execute([$u, password_hash($p, PASSWORD_DEFAULT)]);
            $id = $pdo->lastInsertId();
            session_regenerate_id(true);
            $_SESSION['uid'] = $id;
            header('Location: index.php'); exit;
        }
    } else {
        $st = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $st->execute([$u]);
        $user = $st->fetch();
        if ($user && password_verify($p, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['uid'] = $user['id'];
            header('Location: index.php'); exit;
        }
        $error = 'نام کاربری یا رمز عبور اشتباه است.';
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>ورود</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body class="login-body">
<form method="post" class="card login-card">
  <h2><?= $count === 0 ? 'ساخت حساب مدیر (اولین اجرا)' : 'ورود به پنل' ?></h2>
  <?php if ($error): ?><div class="alert err"><?= e($error) ?></div><?php endif; ?>
  <?= csrf_field() ?>
  <label>نام کاربری</label>
  <input type="text" name="username" required autofocus>
  <label>رمز عبور</label>
  <input type="password" name="password" required>
  <button class="btn" type="submit"><?= $count === 0 ? 'ساخت حساب' : 'ورود' ?></button>
</form>
</body>
</html>
