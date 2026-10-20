<?php
// customer_delete.php
require_once 'config.php';
require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: index.php'); exit; }
csrf_check();
$id = (int)($_POST['id'] ?? 0);

$st = $pdo->prepare("SELECT photo FROM customers WHERE id = ?");
$st->execute([$id]);
if ($row = $st->fetch()) {
    if ($row['photo'] && is_file(__DIR__ . '/uploads/' . $row['photo'])) @unlink(__DIR__ . '/uploads/' . $row['photo']);
    $pdo->prepare("DELETE FROM customers WHERE id = ?")->execute([$id]);
    flash('مشتری حذف شد.');
}
header('Location: index.php');
