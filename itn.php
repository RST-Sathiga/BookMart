<?php

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/payfast_config.php';

$pfData = $_POST;
$signature = $pfData['signature'] ?? '';

if ($signature === '') {
    http_response_code(400);
    die('Missing signature');
}

unset($pfData['signature']);
ksort($pfData);

$signatureString = '';
foreach ($pfData as $key => $value) {
    if ($value !== '') {
        $signatureString .= $key . '=' . urlencode(trim($value)) . '&';
    }
}
$signatureString = rtrim($signatureString, '&');

if (!empty($config['passphrase'])) {
    $signatureString .= '&passphrase=' . urlencode($config['passphrase']);
}

if (md5($signatureString) !== $signature) {
    http_response_code(400);
    die('Invalid signature');
}

$reference = $pfData['m_payment_id'] ?? '';
if (!preg_match('/(\d+)$/', (string) $reference, $matches)) {
    http_response_code(400);
    die('Invalid order reference');
}

if (($pfData['payment_status'] ?? '') === 'COMPLETE') {
    mark_order_paid($conn, (int) $matches[1]);
}

http_response_code(200);
echo 'OK';
