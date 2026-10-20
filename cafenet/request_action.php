<?php
// request_action.php
require_once 'config.php';
require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: index.php'); exit; }
csrf_check();

$action = $_POST['action'] ?? '';
$cid = (int)($_POST['customer_id'] ?? 0);
$rid = (int)($_POST['id'] ?? 0);
$status = $_POST['status'] ?? 'pending';
if (!isset($STATUSES[$status])) $status = 'pending';
$back = (($_POST['back'] ?? '') === 'list') ? 'requests.php' : 'customer_view.php?id=' . $cid;

$money = fn($v) => max(0, (int)preg_replace('/\D/', '', fa2en($v)));

if ($action === 'add') {
    $subject = trim($_POST['subject'] ?? '');
    $rd = fa2en(trim($_POST['request_date'] ?? ''));
    $dd = fa2en(trim($_POST['delivery_date'] ?? ''));
    if ($subject === '' || !valid_jdate($rd) || !valid_jdate($dd)) {
        flash('موضوع الزامی است و تاریخ‌ها باید مثل 1405/07/23 باشند.', 'err');
    } else {
        $pdo->prepare("INSERT INTO requests (customer_id, subject, description, request_date, delivery_date, status, price, paid_amount)
                       VALUES (?,?,?,?,?,?,?,?)")
            ->execute([$cid, $subject, trim($_POST['description'] ?? ''), $rd, $dd, $status,
                       $money($_POST['price'] ?? 0), $money($_POST['paid_amount'] ?? 0)]);
        flash('درخواست ثبت شد.');
    }
} elseif ($action === 'update') {
    $dd = fa2en(trim($_POST['delivery_date'] ?? ''));
    if (!valid_jdate($dd)) {
        flash('فرمت تاریخ تحویل نادرست است.', 'err');
    } else {
        $pdo->prepare("UPDATE requests SET status=?, delivery_date=?, paid_amount=? WHERE id=?")
            ->execute([$status, $dd, $money($_POST['paid_amount'] ?? 0), $rid]);
        flash('تغییرات ذخیره شد.');
    }
} elseif ($action === 'status') {
    $pdo->prepare("UPDATE requests SET status=? WHERE id=?")->execute([$status, $rid]);
    flash('وضعیت تغییر کرد.');
} elseif ($action === 'delete') {
    $pdo->prepare("DELETE FROM requests WHERE id=?")->execute([$rid]);
    flash('درخواست حذف شد.');
}
header('Location: ' . $back);
