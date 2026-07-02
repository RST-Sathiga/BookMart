<?php

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

require_login();

$user_id = current_user_id();
$message = '';
$message_type = 'info';

function calculate_available_seller_balance(mysqli $conn, int $user_id, float $stored_balance): float
{
    $earned = 0.0;
    $withdrawn = 0.0;

    if (table_exists($conn, 'orders')) {
        $stmt = $conn->prepare('
            SELECT COALESCE(SUM(seller_payout), 0) AS total
            FROM orders
            WHERE seller_id = ?
              AND payment_status = "paid"
              AND order_status <> "cancelled"
        ');
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $earned = (float) ($stmt->get_result()->fetch_assoc()['total'] ?? 0);
    }

    if (table_exists($conn, 'withdrawals')) {
        $stmt = $conn->prepare('
            SELECT COALESCE(SUM(amount), 0) AS total
            FROM withdrawals
            WHERE seller_id = ?
              AND status IN ("pending", "approved", "completed")
        ');
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $withdrawn = (float) ($stmt->get_result()->fetch_assoc()['total'] ?? 0);
    }

    if ($earned > 0) {
        return max(0.0, $earned - $withdrawn);
    }

    return max(0.0, $stored_balance);
}

/*
────────────────────────────
1. FETCH WALLET BALANCE (SOURCE OF TRUTH)
────────────────────────────
*/
if (table_exists($conn, 'wallets')) {
    $stmt = $conn->prepare("
        SELECT
            COALESCE(wallets.balance, 0) AS wallet_balance,
            COALESCE(users.wallet_balance, 0) AS user_balance
        FROM users
        LEFT JOIN wallets ON wallets.user_id = users.user_id
        WHERE users.user_id = ?
    ");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $wallet = $stmt->get_result()->fetch_assoc() ?: [];

    $balance = calculate_available_seller_balance(
        $conn,
        $user_id,
        max((float)($wallet['wallet_balance'] ?? 0), (float)($wallet['user_balance'] ?? 0))
    );
} else {
    $stmt = $conn->prepare("
        SELECT wallet_balance AS balance FROM users WHERE user_id = ?
    ");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $wallet = $stmt->get_result()->fetch_assoc();

    $balance = calculate_available_seller_balance($conn, $user_id, (float)($wallet['balance'] ?? 0));
}

/*
────────────────────────────
2. LOAD USER + PAYOUT DATA
────────────────────────────
*/
$user = get_user_by_id($conn, $user_id);
/*
────────────────────────────
3. WITHDRAWAL REQUEST
────────────────────────────
*/
if (isset($_POST['withdraw'])) {

    $amount = (float) $_POST['amount'];

    $payout = [
        'account_holder' => trim($_POST['account_holder'] ?? ''),
        'bank_name' => trim($_POST['bank_name'] ?? ''),
        'account_number' => preg_replace('/\s+/', '', $_POST['account_number'] ?? ''),
        'branch_code' => preg_replace('/\s+/', '', $_POST['branch_code'] ?? ''),
        'account_type' => $_POST['account_type'] ?? '',
    ];

    if ($amount <= 0) {
        $message = 'Enter a valid withdrawal amount.';
        $message_type = 'danger';
    }
    elseif ($amount > $balance) {
        $message = 'Insufficient wallet balance.';
        $message_type = 'danger';
    }
    else {

        $error = validate_payout_details($payout);

        if ($error) {
            $message = $error;
            $message_type = 'danger';
        }
        else {

            $conn->begin_transaction();

            try {
                if (table_exists($conn, 'wallets')) {
                    $sync_wallet = $conn->prepare("
                        INSERT INTO wallets (user_id, balance, updated_at)
                        VALUES (?, ?, NOW())
                        ON DUPLICATE KEY UPDATE balance = GREATEST(balance, VALUES(balance)), updated_at = NOW()
                    ");
                    $sync_wallet->bind_param("id", $user_id, $balance);
                    $sync_wallet->execute();

                    $sync_user = $conn->prepare("
                        UPDATE users
                        SET wallet_balance = GREATEST(wallet_balance, ?)
                        WHERE user_id = ?
                    ");
                    $sync_user->bind_param("di", $balance, $user_id);
                    $sync_user->execute();
                }

                /*
                ─────────────────────────────
                1. LOCK & DEDUCT WALLET BALANCE
                ─────────────────────────────
                */
                if (table_exists($conn, 'wallets')) {
                    $deduct = $conn->prepare("
                        UPDATE wallets
                        SET balance = balance - ?
                        WHERE user_id = ? AND balance >= ?
                    ");
                } else {
                    $deduct = $conn->prepare("
                        UPDATE users
                        SET wallet_balance = wallet_balance - ?
                        WHERE user_id = ? AND wallet_balance >= ?
                    ");
                }
                $deduct->bind_param("did", $amount, $user_id, $amount);
                $deduct->execute();

                if ($deduct->affected_rows === 0) {
                    throw new Exception("Wallet deduction failed.");
                }

                if (table_exists($conn, 'wallets')) {
                    $sync_user_balance = $conn->prepare("
                        UPDATE users
                        SET wallet_balance = GREATEST(wallet_balance - ?, 0)
                        WHERE user_id = ?
                    ");
                    $sync_user_balance->bind_param("di", $amount, $user_id);
                    $sync_user_balance->execute();
                }

                /*
                ─────────────────────────────
                2. CREATE WITHDRAWAL REQUEST
                ─────────────────────────────
                */
                $stmt = $conn->prepare("
                    INSERT INTO withdrawals
                    (seller_id, amount, account_holder, bank_name, account_number, branch_code, account_type, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')
                ");

                $stmt->bind_param(
                    'idsssss',
                    $user_id,
                    $amount,
                    $payout['account_holder'],
                    $payout['bank_name'],
                    $payout['account_number'],
                    $payout['branch_code'],
                    $payout['account_type']
                );

                $stmt->execute();

                /*
                ─────────────────────────────
                3. LOG TRANSACTION
                ─────────────────────────────
                */
                $log = $conn->prepare("
                    INSERT INTO wallet_transactions
                    (user_id, amount, type, description)
                    VALUES (?, ?, 'withdrawal', 'Withdrawal request submitted')
                ");
                $log->bind_param("id", $user_id, $amount);
                $log->execute();

                $conn->commit();

                $message = 'Withdrawal request submitted successfully. Approved payouts take 3-5 business days to reflect in your bank account.';
                $message_type = 'success';

                /*
                Refresh balance after transaction
                */
                if (table_exists($conn, 'wallets')) {
                    $stmt = $conn->prepare("
                        SELECT
                            COALESCE(wallets.balance, 0) AS wallet_balance,
                            COALESCE(users.wallet_balance, 0) AS user_balance
                        FROM users
                        LEFT JOIN wallets ON wallets.user_id = users.user_id
                        WHERE users.user_id = ?
                    ");
                } else {
                    $stmt = $conn->prepare("SELECT wallet_balance AS balance FROM users WHERE user_id = ?");
                }
                $stmt->bind_param('i', $user_id);
                $stmt->execute();
                $wallet_row = $stmt->get_result()->fetch_assoc() ?: [];
                $stored_balance = table_exists($conn, 'wallets')
                    ? max((float)($wallet_row['wallet_balance'] ?? 0), (float)($wallet_row['user_balance'] ?? 0))
                    : (float)($wallet_row['balance'] ?? 0);
                $balance = calculate_available_seller_balance($conn, $user_id, $stored_balance);

            } catch (Exception $e) {
                $conn->rollback();
                $message = 'Withdrawal failed: ' . $e->getMessage();
                $message_type = 'danger';
            }
        }
    }
}

/*
────────────────────────────
4. FORM PREFILL DATA
────────────────────────────
*/
$form_payout = [
    'account_holder' => $_POST['account_holder'] ?? '',
    'bank_name' => $_POST['bank_name'] ?? '',
    'account_number' => $_POST['account_number'] ?? '',
    'branch_code' => $_POST['branch_code'] ?? '',
    'account_type' => $_POST['account_type'] ?? '',
];

/*
────────────────────────────
5. TRANSACTIONS
────────────────────────────
*/
$transactions = $conn->prepare("
    SELECT wt.*, o.id AS order_ref
    FROM wallet_transactions wt
    LEFT JOIN orders o ON wt.order_id = o.id
    WHERE wt.user_id = ?
    ORDER BY wt.created_at DESC
    LIMIT 20
");
$transactions->bind_param('i', $user_id);
$transactions->execute();
$tx_list = $transactions->get_result();

/*
────────────────────────────
6. WITHDRAWALS
────────────────────────────
*/
$withdrawals = $conn->prepare("
    SELECT * FROM withdrawals
    WHERE seller_id = ?
    ORDER BY created_at DESC
    LIMIT 10
");
$withdrawals->bind_param('i', $user_id);
$withdrawals->execute();
$withdrawal_list = $withdrawals->get_result();

/*
────────────────────────────
7. PAGE RENDER
────────────────────────────
*/
$page_title = 'Wallet';
$account_page = 'wallet';
$back_url = site_url('account.php');
$back_label = 'Account Home';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/account_layout_start.php';
?>

<h1 class="section-title">Seller Wallet</h1>

<?php if ($message): ?>
    <div class="alert alert-<?= htmlspecialchars($message_type) ?>">
        <?= htmlspecialchars($message) ?>
    </div>
<?php endif; ?>

<div class="row g-4 mb-4">

    <div class="col-md-4">
        <div class="dashboard-card p-4 text-center h-100">
            <p class="text-muted mb-1">Available Balance</p>
            <div class="wallet-balance">
                <?= format_currency($balance) ?>
            </div>
            <p class="small text-muted mt-2">
                Earnings are credited immediately after buyer payment.
                <?= (int)(COMMISSION_RATE * 100) ?>% commission applies.
            </p>
        </div>
    </div>

    <div class="col-md-8">
        <div class="feature-card p-4">

            <h5 class="mb-3">Request Withdrawal</h5>

            <form method="POST">

                <div class="row g-3">

                    <div class="col-md-6">
                        <label class="form-label">Amount</label>
                        <input type="number" step="0.01" min="1"
                               max="<?= max(0, $balance) ?>"
                               name="amount"
                               class="form-control"
                               required
                               <?= $balance <= 0 ? 'disabled' : '' ?>>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Account Holder</label>
                        <input type="text" name="account_holder" class="form-control"
                               value="<?= htmlspecialchars($form_payout['account_holder']) ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Bank</label>
                        <input type="text" name="bank_name" class="form-control"
                               value="<?= htmlspecialchars($form_payout['bank_name']) ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Account Type</label>
                        <select name="account_type" class="form-select" required>
                            <option value="">Select</option>
                            <option value="cheque" <?= $form_payout['account_type'] === 'cheque' ? 'selected' : '' ?>>Cheque</option>
                            <option value="savings" <?= $form_payout['account_type'] === 'savings' ? 'selected' : '' ?>>Savings</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Account Number</label>
                        <input type="text" name="account_number" class="form-control"
                               value="<?= htmlspecialchars($form_payout['account_number']) ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Branch Code</label>
                        <input type="text" name="branch_code" class="form-control"
                               value="<?= htmlspecialchars($form_payout['branch_code']) ?>" required>
                    </div>

                    <div class="col-12">
                        <p class="small text-muted mb-0">
                            Enter banking details for this request. For security, Bookmart does not reuse saved payout details here.
                            Approved payouts take 3-5 business days to reflect in your bank account.
                        </p>
                    </div>
                    <div class="col-12">
                        <button type="submit" name="withdraw"
                                class="btn btn-gold"
                                <?= $balance <= 0 ? 'disabled' : '' ?>>
                            Submit Withdrawal
                        </button>
                    </div>

                </div>

            </form>

        </div>
    </div>

</div>

<?php require_once __DIR__ . '/includes/account_layout_end.php'; ?>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
