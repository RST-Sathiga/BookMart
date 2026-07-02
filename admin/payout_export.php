<?php

require_once __DIR__ . '/includes/bootstrap.php';
require_admin();

/*
────────────────────────────
CREATE BATCH ID
────────────────────────────
*/
$batch_id = 'BATCH_' . date('Ymd_His');

/*
────────────────────────────
FETCH APPROVED + NOT EXPORTED
────────────────────────────
*/

$stmt = $conn->prepare("
    SELECT withdrawals.*, users.fullname
    FROM withdrawals
    JOIN users ON withdrawals.seller_id = users.user_id
    WHERE withdrawals.status = 'completed'
      AND withdrawals.exported_at IS NULL
");

$stmt->execute();
$result = $stmt->get_result();

/*
────────────────────────────
SET CSV HEADERS
────────────────────────────
*/

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="payout_' . $batch_id . '.csv"');

$output = fopen('php://output', 'w');

/*
CSV COLUMN STRUCTURE (BANK STANDARD FORMAT)
*/

fputcsv($output, [
    'Batch ID',
    'Seller Name',
    'Amount',
    'Bank Name',
    'Account Holder',
    'Account Number',
    'Branch Code',
    'Account Type'
]);

/*
────────────────────────────
WRITE DATA
────────────────────────────
*/

while ($row = $result->fetch_assoc()) {

    fputcsv($output, [
        $batch_id,
        $row['fullname'],
        $row['amount'],
        $row['bank_name'],
        $row['account_holder'],
        $row['account_number'],
        $row['branch_code'],
        $row['account_type']
    ]);

    // mark as exported
    $update = $conn->prepare("
        UPDATE withdrawals
        SET exported_at = NOW(),
            batch_id = ?
        WHERE id = ?
    ");

    $update->bind_param("si", $batch_id, $row['id']);
    $update->execute();
}

fclose($output);
exit();