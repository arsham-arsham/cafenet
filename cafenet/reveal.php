<?php
// reveal.php
require_once 'config.php';
require_login();
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
csrf_check();

$st = $pdo->prepare("SELECT personal_password_enc, sana_password_enc FROM customers WHERE id = ?");
$st->execute([(int)($_POST['id'] ?? 0)]);
$r = $st->fetch();
if (!$r) { http_response_code(404); echo '{}'; exit; }
echo json_encode([
    'personal' => decrypt_val($r['personal_password_enc']) ?: '—',
    'sana'     => decrypt_val($r['sana_password_enc']) ?: '—',
], JSON_UNESCAPED_UNICODE);
