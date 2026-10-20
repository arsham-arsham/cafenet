<?php
session_start();

define('DB_HOST', 'localhost');
define('DB_NAME', 'cafenet');
define('DB_USER', 'root');
define('DB_PASS', '');
// قبل از ثبت اولین اطلاعات این کلید را به یک رشته طولانی و تصادفی تغییر دهید.
// بعدا تغییر دادنش باعث می‌شود رمزهای قبلی خوانا نباشند.
define('ENC_KEY', 'CHANGE-THIS-TO-A-LONG-RANDOM-SECRET-STRING-123456');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER, DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => true,
        ]
    );
} catch (PDOException $ex) {
    die('خطا در اتصال به دیتابیس. MySQL را در XAMPP روشن کنید و database.sql را import کنید.');
}

$STATUSES = [
    'pending'     => 'در انتظار',
    'in_progress' => 'در حال انجام',
    'done'        => 'انجام شده',
    'delivered'   => 'تحویل شده',
    'cancelled'   => 'لغو شده',
];

function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function require_login() {
    if (empty($_SESSION['uid'])) { header('Location: login.php'); exit; }
}

function csrf_token() {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_field() { return '<input type="hidden" name="csrf" value="' . csrf_token() . '">'; }
function csrf_check() {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(403); die('درخواست نامعتبر است.');
    }
}

function flash($msg, $type = 'ok') { $_SESSION['flash'] = [$msg, $type]; }
function show_flash() {
    if (!empty($_SESSION['flash'])) {
        [$m, $t] = $_SESSION['flash']; unset($_SESSION['flash']);
        echo '<div class="alert ' . e($t) . '">' . e($m) . '</div>';
    }
}

// تبدیل ارقام فارسی و عربی به انگلیسی
function fa2en($s) {
    return strtr((string)$s, [
        '۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9',
        '٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9',
    ]);
}

function valid_national_id($c) {
    if (!preg_match('/^\d{10}$/', $c) || preg_match('/^(\d)\1{9}$/', $c)) return false;
    $sum = 0;
    for ($i = 0; $i < 9; $i++) $sum += (int)$c[$i] * (10 - $i);
    $r = $sum % 11;
    $check = (int)$c[9];
    return ($r < 2 && $check == $r) || ($r >= 2 && $check == 11 - $r);
}

function valid_jdate($d) { return $d === '' || preg_match('#^\d{4}/\d{2}/\d{2}$#', $d); }

function encrypt_val($plain) {
    if ($plain === '') return '';
    $key = hash('sha256', ENC_KEY, true);
    $iv = random_bytes(12); $tag = '';
    $c = openssl_encrypt($plain, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
    return base64_encode($iv . $tag . $c);
}
function decrypt_val($b64) {
    if (!$b64) return '';
    $raw = base64_decode($b64);
    $key = hash('sha256', ENC_KEY, true);
    $p = openssl_decrypt(substr($raw, 28), 'aes-256-gcm', $key, OPENSSL_RAW_DATA, substr($raw, 0, 12), substr($raw, 12, 16));
    return $p === false ? '' : $p;
}
