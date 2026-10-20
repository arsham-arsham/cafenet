<?php
$title = 'پرونده مشتری';
require 'header.php';

$id = (int)($_GET['id'] ?? 0);
$st = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
$st->execute([$id]);
$c = $st->fetch();
if (!$c) { echo '<div class="alert err">مشتری پیدا نشد.</div>'; require 'footer.php'; exit; }

$rs = $pdo->prepare("SELECT * FROM requests WHERE customer_id = ? ORDER BY id DESC");
$rs->execute([$id]);
$requests = $rs->fetchAll();
?>
<div class="card profile">
  <div class="photo-box">
    <?php if ($c['photo']): ?><img src="uploads/<?= e($c['photo']) ?>" alt=""><?php else: ?><div class="thumb empty big">بدون عکس</div><?php endif; ?>
  </div>
  <div class="info">
    <h2><?= e($c['first_name'] . ' ' . $c['last_name']) ?></h2>
    <p><b>شماره تماس:</b> <span dir="ltr"><?= e($c['phone']) ?></span></p>
    <p><b>کد ملی:</b> <span dir="ltr"><?= e($c['national_id']) ?></span></p>
    <p><b>سریال شناسنامه:</b> <span dir="ltr"><?= e($c['birth_cert_serial']) ?></span></p>
    <p><b>تاریخ تولد:</b> <span dir="ltr"><?= e($c['birth_date']) ?></span></p>
    <p><b>رمز شخصی:</b> <span id="ppVal" dir="ltr">••••••</span></p>
    <p><b>رمز ثنا:</b> <span id="spVal" dir="ltr">••••••</span></p>
    <?php if ($c['notes']): ?><p><b>یادداشت:</b> <?= nl2br(e($c['notes'])) ?></p><?php endif; ?>
    <button class="btn sm" id="revealBtn" data-id="<?= $c['id'] ?>" data-csrf="<?= csrf_token() ?>">نمایش رمزها</button>
    <a class="btn sm gray" href="customer_form.php?id=<?= $c['id'] ?>">ویرایش</a>
  </div>
</div>

<div class="card">
  <h3>ثبت درخواست جدید</h3>
  <form method="post" action="request_action.php" class="grid">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="add">
    <input type="hidden" name="customer_id" value="<?= $c['id'] ?>">
    <div>
      <label>موضوع درخواست *</label>
      <input type="text" name="subject" list="subjects" required>
      <datalist id="subjects">
        <option value="ثبت‌نام در ثنا"><option value="تغییر رمز ثنا"><option value="ثبت‌نام کنکور">
        <option value="استعلام"><option value="پرینت و اسکن"><option value="تایپ و ترجمه">
      </datalist>
    </div>
    <div>
      <label>تاریخ درخواست</label>
      <input type="text" name="request_date" id="reqDate" class="digits ltr" placeholder="1405/07/23">
    </div>
    <div>
      <label>تاریخ تحویل</label>
      <input type="text" name="delivery_date" class="digits ltr" placeholder="1405/07/25">
    </div>
    <div>
      <label>وضعیت</label>
      <select name="status">
        <?php foreach ($STATUSES as $k => $l): ?><option value="<?= $k ?>"><?= e($l) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div><label>هزینه (تومان)</label><input type="text" name="price" class="digits ltr" value="0"></div>
    <div><label>پرداختی (تومان)</label><input type="text" name="paid_amount" class="digits ltr" value="0"></div>
    <div class="full"><label>توضیحات</label><textarea name="description" rows="2"></textarea></div>
    <div class="full"><button class="btn" type="submit">ثبت درخواست</button></div>
  </form>
</div>

<div class="card table-wrap">
  <h3>درخواست‌های این مشتری</h3>
  <table>
    <thead><tr><th>موضوع</th><th>تاریخ درخواست</th><th>تاریخ تحویل</th><th>وضعیت</th><th>هزینه</th><th>پرداختی</th><th>مانده</th><th>عملیات</th></tr></thead>
    <tbody>
    <?php foreach ($requests as $r): ?>
      <tr>
        <form method="post" action="request_action.php">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="update">
          <input type="hidden" name="id" value="<?= $r['id'] ?>">
          <input type="hidden" name="customer_id" value="<?= $c['id'] ?>">
          <td><?= e($r['subject']) ?><?php if ($r['description']): ?><br><small><?= e($r['description']) ?></small><?php endif; ?></td>
          <td dir="ltr"><?= e($r['request_date']) ?></td>
          <td><input type="text" name="delivery_date" class="digits ltr small-in" value="<?= e($r['delivery_date']) ?>"></td>
          <td>
            <select name="status" class="badge s-<?= e($r['status']) ?>">
              <?php foreach ($STATUSES as $k => $l): ?>
                <option value="<?= $k ?>" <?= $r['status'] === $k ? 'selected' : '' ?>><?= e($l) ?></option>
              <?php endforeach; ?>
            </select>
          </td>
          <td><?= number_format($r['price']) ?></td>
          <td><input type="text" name="paid_amount" class="digits ltr small-in" value="<?= (int)$r['paid_amount'] ?>"></td>
          <td class="<?= $r['price'] - $r['paid_amount'] > 0 ? 'debt' : '' ?>"><?= number_format($r['price'] - $r['paid_amount']) ?></td>
          <td class="actions"><button class="btn sm" type="submit">ذخیره</button></td>
        </form>
        <td class="actions">
          <form method="post" action="request_action.php" data-confirm="این درخواست حذف شود؟">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= $r['id'] ?>">
            <input type="hidden" name="customer_id" value="<?= $c['id'] ?>">
            <button class="btn sm red" type="submit">حذف</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$requests): ?><tr><td colspan="9" class="center">هنوز درخواستی ثبت نشده.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
<?php require 'footer.php'; ?>
