<?php
$title = 'مشتریان';
require 'header.php';

$q = trim(fa2en($_GET['q'] ?? ''));
$sql = "SELECT c.*, (SELECT COUNT(*) FROM requests r WHERE r.customer_id = c.id) AS req_count FROM customers c";
$params = [];
if ($q !== '') {
    $sql .= " WHERE c.first_name LIKE :q OR c.last_name LIKE :q
              OR CONCAT(c.first_name,' ',c.last_name) LIKE :q
              OR c.phone LIKE :q OR c.national_id LIKE :q";
    $params[':q'] = "%$q%";
}
$sql .= " ORDER BY c.id DESC LIMIT 300";
$st = $pdo->prepare($sql);
$st->execute($params);
$customers = $st->fetchAll();

$stats = $pdo->query("SELECT status, COUNT(*) n FROM requests GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
$totalCustomers = (int)$pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn();
?>
<div class="stats">
  <div class="stat"><b><?= $totalCustomers ?></b><span>کل مشتریان</span></div>
  <?php foreach ($STATUSES as $k => $label): ?>
    <div class="stat s-<?= $k ?>"><b><?= (int)($stats[$k] ?? 0) ?></b><span><?= e($label) ?></span></div>
  <?php endforeach; ?>
</div>

<form method="get" class="searchbar">
  <input type="text" name="q" value="<?= e($q) ?>" placeholder="جستجو: نام، شماره تماس یا کد ملی">
  <button class="btn" type="submit">جستجو</button>
  <?php if ($q !== ''): ?><a class="btn gray" href="index.php">پاک کردن</a><?php endif; ?>
</form>

<div class="card table-wrap">
<table>
  <thead>
    <tr><th>عکس</th><th>نام و نام خانوادگی</th><th>تلفن</th><th>کد ملی</th><th>تاریخ تولد</th><th>درخواست‌ها</th><th>عملیات</th></tr>
  </thead>
  <tbody>
  <?php foreach ($customers as $c): ?>
    <tr>
      <td>
        <?php if ($c['photo']): ?>
          <img class="thumb" src="uploads/<?= e($c['photo']) ?>" alt="">
        <?php else: ?><div class="thumb empty">—</div><?php endif; ?>
      </td>
      <td><a href="customer_view.php?id=<?= $c['id'] ?>"><?= e($c['first_name'] . ' ' . $c['last_name']) ?></a></td>
      <td dir="ltr"><?= e($c['phone']) ?></td>
      <td dir="ltr"><?= e($c['national_id']) ?></td>
      <td dir="ltr"><?= e($c['birth_date']) ?></td>
      <td><?= (int)$c['req_count'] ?></td>
      <td class="actions">
        <a class="btn sm" href="customer_view.php?id=<?= $c['id'] ?>">پرونده</a>
        <a class="btn sm gray" href="customer_form.php?id=<?= $c['id'] ?>">ویرایش</a>
        <form method="post" action="customer_delete.php" data-confirm="مشتری و همه درخواست‌هایش حذف شود؟">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= $c['id'] ?>">
          <button class="btn sm red" type="submit">حذف</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$customers): ?><tr><td colspan="7" class="center">موردی پیدا نشد.</td></tr><?php endif; ?>
  </tbody>
</table>
</div>
<?php require 'footer.php'; ?>
