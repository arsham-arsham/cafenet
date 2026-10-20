<?php
// header.php
require_once __DIR__ . '/config.php';
require_login();
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title ?? 'مدیریت مشتریان') ?></title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<nav class="nav">
  <div class="brand">مدیریت کافی‌نت</div>
  <div class="links">
    <a href="index.php">مشتریان</a>
    <a href="requests.php">درخواست‌ها</a>
    <a href="customer_form.php">+ مشتری جدید</a>
    <a href="logout.php">خروج</a>
  </div>
</nav>
<main class="container">
<?php show_flash(); ?>
