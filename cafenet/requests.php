<?php
$title = 'همه درخواست‌ها';
require 'header.php';

$status = $_GET['status'] ?? '';
$q = trim(fa2en($_GET['q'] ?? ''));
$sql = "SELECT r.*, c.first_name, c.last_name, c.phone FROM requests r JOIN customers c ON c.id = r.customer_id WHERE 1=1";
$params = [];
if (isset($STATUSES[$status])) { $sql .= " AND r.status = :st"; $params[':st'] = $status; }
if ($q !== '') {
    $sql .= " AND (r.subject LIKE :q OR c.first_name LIKE :q OR c.last_name LIKE :q OR c.phone LIKE :q OR c.national_id LIKE :q)";
    $params[':q'] = "%$q%";
}
$sql .= " ORDER BY r.id DESC LIMIT 500";
$st = $pdo->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll();
?>
<form method="get" class="searchbar">
  <input type="text" name="q" value="<?= e($q) ?>" placeholder="جستجو در موضوع، نام، تلفن، کد ملی">
  <select name="status">
    <option value="">همه وضعیت‌ها</option>
    <?php foreach ($STATUSES as $k => $l): ?>
      <option value="<?= $k ?>" <?= $status === $k ? 'selected' : '' ?>><?= e($l) ?></option>
    <?php endforeach; ?>
  </select>
  <button class="btn" type="submit">فیلتر</button>
</form>

<div class="card table-wrap">
<table>
  <thead><tr><th>مشتری</th><th>تلفن</th><th>موضوع</th><th>تاریخ درخواست</th><th>تاریخ تحویل</th><th>وضعیت</th><th>مانده</th></tr></thead>
  <tbody>
  <?php foreach ($rows as $r): ?>
    <tr>
      <td><a href="customer_view.php?id=<?= $r['customer_id'] ?>"><?= e($r['first_name'] . ' ' . $r['last_name']) ?></a></td>
      <td dir="ltr"><?= e($r['phone']) ?></td>
      <td><?= e($r['subject']) ?></td>
      <td dir="ltr"><?= e($r['request_date']) ?></td>
      <td dir="ltr"><?= e($r['delivery_date']) ?></td>
      <td>
        <form method="post" action="request_action.php">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="status">
          <input type="hidden" name="back" value="list">
          <input type="hidden" name="id" value="<?= $r['id'] ?>">
          <select name="status" class="badge s-<?= e($r['status']) ?>" onchange="this.form.submit()">
            <?php foreach ($STATUSES as $k => $l): ?>
              <option value="<?= $k ?>" <?= $r['status'] === $k ? 'selected' : '' ?>><?= e($l) ?></option>
            <?php endforeach; ?>
          </select>
        </form>
      </td>
      <td class="<?= $r['price'] - $r['paid_amount'] > 0 ? 'debt' : '' ?>"><?= number_format($r['price'] - $r['paid_amount']) ?></td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$rows): ?><tr><td colspan="7" class="center">موردی پیدا نشد.</td></tr><?php endif; ?>
  </tbody>
</table>
</div>
<?php require 'footer.php'; ?>
