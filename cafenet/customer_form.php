<?php
require_once 'config.php';
require_login();

$id = (int)($_GET['id'] ?? 0);
$c = [
    'first_name' => '', 'last_name' => '', 'phone' => '', 'national_id' => '',
    'birth_cert_serial' => '', 'photo' => '', 'birth_date' => '', 'notes' => '',
    'personal_password_enc' => '', 'sana_password_enc' => '',
];
if ($id) {
    $st = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
    $st->execute([$id]);
    $c = $st->fetch();
    if (!$c) die('مشتری پیدا نشد.');
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $first = trim($_POST['first_name'] ?? '');
    $last  = trim($_POST['last_name'] ?? '');
    $phone = fa2en(trim($_POST['phone'] ?? ''));
    $nid   = fa2en(trim($_POST['national_id'] ?? ''));
    $serial = fa2en(trim($_POST['birth_cert_serial'] ?? ''));
    $bdate = fa2en(trim($_POST['birth_date'] ?? ''));
    $notes = trim($_POST['notes'] ?? '');
    $pp = $_POST['personal_password'] ?? '';
    $sp = $_POST['sana_password'] ?? '';

    if ($first === '' || $last === '') $errors[] = 'نام و نام خانوادگی الزامی است.';
    if (!preg_match('/^09\d{9}$/', $phone)) $errors[] = 'شماره تماس باید ۱۱ رقم و با 09 شروع شود.';
    if (!valid_national_id($nid)) $errors[] = 'کد ملی معتبر نیست.';
    if (!valid_jdate($bdate)) $errors[] = 'فرمت تاریخ تولد باید مثل 1380/05/12 باشد.';

    $dup = $pdo->prepare("SELECT id FROM customers WHERE national_id = ? AND id <> ?");
    $dup->execute([$nid, $id]);
    if ($dup->fetch()) $errors[] = 'این کد ملی قبلا ثبت شده است.';

    // آپلود عکس
    $photo = $c['photo'];
    if (!empty($_FILES['photo']['name'])) {
        if ($_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'آپلود عکس با خطا مواجه شد.';
        } else {
            $info = @getimagesize($_FILES['photo']['tmp_name']);
            $mime = $info ? $info['mime'] : '';
            $exts = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
            if (!isset($exts[$mime])) {
                $errors[] = 'فقط تصاویر JPG، PNG و WEBP مجاز است.';
            } elseif ($_FILES['photo']['size'] > 2 * 1024 * 1024) {
                $errors[] = 'حجم عکس نباید بیشتر از ۲ مگابایت باشد.';
            } elseif (!$errors) {
                $name = bin2hex(random_bytes(10)) . '.' . $exts[$mime];
                move_uploaded_file($_FILES['photo']['tmp_name'], __DIR__ . '/uploads/' . $name);
                if ($photo && is_file(__DIR__ . '/uploads/' . $photo)) @unlink(__DIR__ . '/uploads/' . $photo);
                $photo = $name;
            }
        }
    }

    if (!$errors) {
        $ppEnc = $pp !== '' ? encrypt_val($pp) : $c['personal_password_enc'];
        $spEnc = $sp !== '' ? encrypt_val($sp) : $c['sana_password_enc'];
        if ($id) {
            $pdo->prepare("UPDATE customers SET first_name=?, last_name=?, phone=?, national_id=?, birth_cert_serial=?,
                           photo=?, birth_date=?, personal_password_enc=?, sana_password_enc=?, notes=? WHERE id=?")
                ->execute([$first, $last, $phone, $nid, $serial, $photo, $bdate, $ppEnc, $spEnc, $notes, $id]);
        } else {
            $pdo->prepare("INSERT INTO customers (first_name, last_name, phone, national_id, birth_cert_serial,
                           photo, birth_date, personal_password_enc, sana_password_enc, notes)
                           VALUES (?,?,?,?,?,?,?,?,?,?)")
                ->execute([$first, $last, $phone, $nid, $serial, $photo, $bdate, $ppEnc, $spEnc, $notes]);
            $id = $pdo->lastInsertId();
        }
        flash('اطلاعات مشتری ذخیره شد.');
        header('Location: customer_view.php?id=' . $id); exit;
    }
    // نمایش مجدد مقادیر واردشده
    $c = array_merge($c, [
        'first_name' => $first, 'last_name' => $last, 'phone' => $phone, 'national_id' => $nid,
        'birth_cert_serial' => $serial, 'birth_date' => $bdate, 'notes' => $notes,
    ]);
}

$title = $id ? 'ویرایش مشتری' : 'مشتری جدید';
require 'header.php';
?>
<div class="card">
  <h2><?= e($title) ?></h2>
  <?php foreach ($errors as $er): ?><div class="alert err"><?= e($er) ?></div><?php endforeach; ?>

  <form method="post" enctype="multipart/form-data" class="grid">
    <?= csrf_field() ?>
    <div><label>نام *</label><input type="text" name="first_name" value="<?= e($c['first_name']) ?>" required></div>
    <div><label>نام خانوادگی *</label><input type="text" name="last_name" value="<?= e($c['last_name']) ?>" required></div>
    <div><label>شماره تماس *</label><input type="text" name="phone" class="digits ltr" maxlength="11" value="<?= e($c['phone']) ?>" required></div>
    <div><label>کد ملی *</label><input type="text" name="national_id" class="digits ltr" maxlength="10" value="<?= e($c['national_id']) ?>" required></div>
    <div><label>سریال شناسنامه</label><input type="text" name="birth_cert_serial" class="digits ltr" value="<?= e($c['birth_cert_serial']) ?>"></div>
    <div>
      <label>تاریخ تولد (مثال 1380/05/12)</label>
      <input type="text" name="birth_date" class="digits ltr" maxlength="10" placeholder="1380/05/12" value="<?= e($c['birth_date']) ?>">
    </div>
    <div>
      <label>رمز شخصی <?= $id ? '(خالی = بدون تغییر)' : '' ?></label>
      <input type="text" name="personal_password" class="ltr" autocomplete="off">
    </div>
    <div>
      <label>رمز ثنا <?= $id ? '(خالی = بدون تغییر)' : '' ?></label>
      <input type="text" name="sana_password" class="ltr" autocomplete="off">
    </div>
    <div class="full">
      <label>عکس ۳×۴</label>
      <input type="file" name="photo" accept="image/*" id="photoInput">
      <img id="photoPreview" class="preview" src="<?= $c['photo'] ? 'uploads/' . e($c['photo']) : '' ?>" <?= $c['photo'] ? '' : 'style="display:none"' ?> alt="">
    </div>
    <div class="full"><label>یادداشت</label><textarea name="notes" rows="3"><?= e($c['notes']) ?></textarea></div>
    <div class="full">
      <button class="btn" type="submit">ذخیره</button>
      <a class="btn gray" href="index.php">انصراف</a>
    </div>
  </form>
</div>
<?php require 'footer.php'; ?>
