<?php

require_once __DIR__ . '/../config/config.php';

header('Content-Type: text/html; charset=utf-8');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die('Connection failed: ' . htmlspecialchars($conn->connect_error));
}

$sql_file = __DIR__ . '/fix_missing_tables.sql';
$sql = file_get_contents($sql_file);

$statements = array_filter(
    array_map('trim', preg_split('/;\s*[\r\n]+/', $sql)),
    function ($statement) {
        $statement = trim($statement);
        return $statement !== '' && stripos($statement, '--') !== 0;
    }
);

$errors = [];

foreach ($statements as $statement) {
    if (!preg_match('/^(USE|SET|DROP|CREATE|INSERT|ALTER)/i', $statement)) {
        continue;
    }

    if (!$conn->query($statement)) {
        $errors[] = $conn->error . ' — SQL: ' . substr($statement, 0, 120) . '...';
    }
}

$required = [
    'universities',
    'campuses',
    'users',
    'products',
    'cart',
    'wishlist',
    'orders',
    'wallet_transactions',
    'platform_revenue',
    'messages',
    'withdrawals',
    'reviews',
    'reports',
    'notifications',
    'admin_logs',
    'system_settings',
];
$missing = [];

foreach ($required as $table) {
    $check = $conn->query("SHOW TABLES LIKE '$table'");
    if (!$check || $check->num_rows === 0) {
        $missing[] = $table;
    }
}

if ($errors) {
    echo '<h2>Migration errors</h2><ul>';
    foreach ($errors as $error) {
        echo '<li>' . htmlspecialchars($error) . '</li>';
    }
    echo '</ul>';
}

if ($missing) {
    echo '<p><strong>Still missing:</strong> ' . htmlspecialchars(implode(', ', $missing)) . '</p>';
} else {
    echo '<p><strong>Database updated successfully.</strong> All required tables exist.</p>';
    echo '<p><a href="../index.php">Go to BookMart</a> · <a href="../admin/index.php">Admin Dashboard</a></p>';
}

$admin_check = $conn->query("SELECT user_id FROM users WHERE email = 'admin@bookmart.com' LIMIT 1");
if ($admin_check && $admin_check->num_rows === 0) {
    $password = password_hash('password', PASSWORD_BCRYPT);
    $answer = password_hash('pretoria', PASSWORD_BCRYPT);
    $stmt = $conn->prepare('
        INSERT INTO users (fullname, username, email, phone, password, role, status, university_id, campus_id, security_question, security_answer)
        VALUES ("System Administrator", "admin", "admin@bookmart.com", "0000000000", ?, "admin", "active", 1, 1, "What city is the platform based in?", ?)
    ');
    $stmt->bind_param('ss', $password, $answer);
    $stmt->execute();
    echo '<p>Default admin account created (admin@bookmart.com / password).</p>';
}

echo '<h3>Tables in database</h3><ul>';
$tables = $conn->query('SHOW TABLES');
while ($row = $tables->fetch_array()) {
    echo '<li>' . htmlspecialchars($row[0]) . '</li>';
}
echo '</ul>';
