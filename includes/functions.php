<?php

require_once __DIR__ . '/../db.php';

function generate_pickup_code(): string
{
    return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}

function format_currency(float $amount): string
{
    return 'R' . number_format($amount, 2);
}

function site_url(string $path = ''): string
{
    $path = ltrim(str_replace('\\', '/', $path), '/');
    return SITE_URL . ($path !== '' ? '/' . $path : '');
}

function calculate_commission(float $amount): array
{
    $commission = round($amount * COMMISSION_RATE, 2);
    $seller_payout = round($amount - $commission, 2);

    return [
        'commission' => $commission,
        'seller_payout' => $seller_payout,
    ];
}

function get_universities(mysqli $conn): array
{
    $result = $conn->query('SELECT id, name, city, institution_type FROM universities ORDER BY name');
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

function get_universities_grouped(mysqli $conn): array
{
    $grouped = [
        'public' => [],
        'private' => [],
        'other' => [],
    ];

    $result = $conn->query('SELECT id, name, city, institution_type FROM universities ORDER BY name');

    if (!$result) {
        return $grouped;
    }

    while ($row = $result->fetch_assoc()) {
        $type = $row['institution_type'] ?? 'public';
        if (!isset($grouped[$type])) {
            $type = 'other';
        }
        $grouped[$type][] = $row;
    }

    return $grouped;
}

function find_or_create_university(mysqli $conn, string $name, string $city, string $type = 'other'): int
{
    $name = trim($name);
    $city = trim($city);

    $stmt = $conn->prepare('SELECT id FROM universities WHERE LOWER(name) = LOWER(?) AND LOWER(city) = LOWER(?) LIMIT 1');
    $stmt->bind_param('ss', $name, $city);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();

    if ($existing) {
        return (int) $existing['id'];
    }

    $allowed_types = ['public', 'private', 'other'];
    if (!in_array($type, $allowed_types, true)) {
        $type = 'other';
    }

    $insert = $conn->prepare('INSERT INTO universities (name, city, institution_type) VALUES (?, ?, ?)');
    $insert->bind_param('sss', $name, $city, $type);
    $insert->execute();

    return (int) $conn->insert_id;
}

function find_or_create_campus(mysqli $conn, int $university_id, string $name, string $pickup_point): int
{
    $name = trim($name);
    $pickup_point = trim($pickup_point);

    $stmt = $conn->prepare('
        SELECT id FROM campuses
        WHERE university_id = ? AND LOWER(name) = LOWER(?) AND LOWER(pickup_point) = LOWER(?)
        LIMIT 1
    ');
    $stmt->bind_param('iss', $university_id, $name, $pickup_point);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();

    if ($existing) {
        return (int) $existing['id'];
    }

    $insert = $conn->prepare('INSERT INTO campuses (university_id, name, pickup_point) VALUES (?, ?, ?)');
    $insert->bind_param('iss', $university_id, $name, $pickup_point);
    $insert->execute();

    return (int) $conn->insert_id;
}

function resolve_location_from_request(mysqli $conn, array $data): array
{
    $university_value = $data['university_id'] ?? '';
    $campus_value = $data['campus_id'] ?? '';
    $custom_institution = trim($data['custom_institution'] ?? '');
    $custom_city = trim($data['custom_city'] ?? '');
    $custom_campus = trim($data['custom_campus'] ?? '');
    $custom_pickup = trim($data['custom_pickup'] ?? '');

    if ($university_value === 'other') {
        if ($custom_institution === '' || $custom_city === '' || $custom_campus === '' || $custom_pickup === '') {
            return ['error' => 'Enter your college/university, city, campus, and pickup point on campus.'];
        }

        $university_id = find_or_create_university($conn, $custom_institution, $custom_city, 'other');
        $campus_id = find_or_create_campus($conn, $university_id, $custom_campus, $custom_pickup);

        return ['university_id' => $university_id, 'campus_id' => $campus_id];
    }

    $university_id = (int) $university_value;

    if ($university_id <= 0) {
        return ['error' => 'Select your university, college, or choose Other to enter manually.'];
    }

    if ($campus_value === 'other') {
        if ($custom_campus === '' || $custom_pickup === '') {
            return ['error' => 'Enter your campus name and pickup point on campus.'];
        }

        $campus_id = find_or_create_campus($conn, $university_id, $custom_campus, $custom_pickup);

        return ['university_id' => $university_id, 'campus_id' => $campus_id];
    }

    $campus_id = (int) $campus_value;

    if ($campus_id <= 0) {
        return ['error' => 'Select your campus or choose Other to enter manually.'];
    }

    return ['university_id' => $university_id, 'campus_id' => $campus_id];
}

function get_campuses_by_university(mysqli $conn, int $university_id): array
{
    $gps_cols = column_exists($conn, 'campuses', 'latitude') ? ', latitude, longitude' : '';
    $stmt = $conn->prepare("SELECT id, name, pickup_point{$gps_cols} FROM campuses WHERE university_id = ? ORDER BY name");
    $stmt->bind_param('i', $university_id);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->fetch_all(MYSQLI_ASSOC);
}

function haversine_km(float $lat1, float $lon1, float $lat2, float $lon2): float
{
    $earth_radius = 6371;
    $d_lat = deg2rad($lat2 - $lat1);
    $d_lon = deg2rad($lon2 - $lon1);
    $a = sin($d_lat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($d_lon / 2) ** 2;

    return $earth_radius * 2 * atan2(sqrt($a), sqrt(1 - $a));
}

function get_nearest_universities(mysqli $conn, float $latitude, float $longitude, int $limit = 10): array
{
    if (!column_exists($conn, 'universities', 'latitude')) {
        return [];
    }

    $result = $conn->query('SELECT id, name, city, institution_type, latitude, longitude FROM universities WHERE latitude IS NOT NULL AND longitude IS NOT NULL');

    if (!$result) {
        return [];
    }

    $items = [];

    while ($row = $result->fetch_assoc()) {
        $row['distance_km'] = round(haversine_km($latitude, $longitude, (float) $row['latitude'], (float) $row['longitude']), 1);
        $items[] = $row;
    }

    usort($items, fn($a, $b) => $a['distance_km'] <=> $b['distance_km']);

    return array_slice($items, 0, $limit);
}

function get_nearest_campuses(mysqli $conn, float $latitude, float $longitude, ?int $university_id = null, int $limit = 10): array
{
    if (!column_exists($conn, 'campuses', 'latitude')) {
        return [];
    }

    if ($university_id) {
        $stmt = $conn->prepare('
            SELECT campuses.id, campuses.name, campuses.pickup_point, campuses.university_id, campuses.latitude, campuses.longitude,
                   universities.name AS university_name
            FROM campuses
            JOIN universities ON campuses.university_id = universities.id
            WHERE campuses.university_id = ?
        ');
        $stmt->bind_param('i', $university_id);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $conn->query('
            SELECT campuses.id, campuses.name, campuses.pickup_point, campuses.university_id, campuses.latitude, campuses.longitude,
                   universities.name AS university_name
            FROM campuses
            JOIN universities ON campuses.university_id = universities.id
            WHERE campuses.latitude IS NOT NULL AND campuses.longitude IS NOT NULL
        ');
    }

    if (!$result) {
        return [];
    }

    $items = [];

    while ($row = $result->fetch_assoc()) {
        if ($row['latitude'] === null || $row['longitude'] === null) {
            continue;
        }
        $row['distance_km'] = round(haversine_km($latitude, $longitude, (float) $row['latitude'], (float) $row['longitude']), 1);
        $items[] = $row;
    }

    usort($items, fn($a, $b) => $a['distance_km'] <=> $b['distance_km']);

    return array_slice($items, 0, $limit);
}

function get_user_by_id(mysqli $conn, int $user_id): ?array
{
    $stmt = $conn->prepare('SELECT * FROM users WHERE user_id = ?');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    return $user ?: null;
}

function table_exists(mysqli $conn, string $table): bool
{
    static $cache = [];

    if (isset($cache[$table])) {
        return $cache[$table];
    }

    $database = DB_NAME;
    $stmt = $conn->prepare('
        SELECT 1 FROM information_schema.tables
        WHERE table_schema = ? AND table_name = ?
        LIMIT 1
    ');
    $stmt->bind_param('ss', $database, $table);
    $stmt->execute();
    $cache[$table] = $stmt->get_result()->num_rows > 0;

    return $cache[$table];
}

function column_exists(mysqli $conn, string $table, string $column): bool
{
    static $cache = [];
    $key = $table . '.' . $column;

    if (isset($cache[$key])) {
        return $cache[$key];
    }

    $database = DB_NAME;
    $stmt = $conn->prepare('
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = ? AND table_name = ? AND column_name = ?
        LIMIT 1
    ');
    $stmt->bind_param('sss', $database, $table, $column);
    $stmt->execute();
    $cache[$key] = $stmt->get_result()->num_rows > 0;

    return $cache[$key];
}

function get_unread_message_count(mysqli $conn, int $user_id): int
{
    if (!table_exists($conn, 'messages')) {
        return 0;
    }

    try {
        $stmt = $conn->prepare('SELECT COUNT(*) AS total FROM messages WHERE receiver_id = ? AND is_read = 0');
        if (!$stmt) {
            return 0;
        }

        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        return (int) ($row['total'] ?? 0);
    } catch (mysqli_sql_exception $e) {
        return 0;
    }
}

function get_unread_notification_count(mysqli $conn, int $user_id): int
{
    if (!table_exists($conn, 'notifications')) {
        return 0;
    }

    $stmt = $conn->prepare('SELECT COUNT(*) AS total FROM notifications WHERE user_id = ? AND is_read = 0');
    if (!$stmt) {
        return 0;
    }

    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    return (int) ($row['total'] ?? 0);
}

function get_session_cart_count(): int
{
    $count = 0;

    foreach (($_SESSION['cart'] ?? []) as $item) {
        $count += 1;
    }

    return $count;
}

function create_bookmart_notification(mysqli $conn, int $user_id, string $type, string $message, string $severity = 'low', ?int $related_id = null): void
{
    if (!table_exists($conn, 'notifications')) {
        return;
    }

    $message = '[No Reply] ' . preg_replace('/^\[No Reply\]\s*/', '', trim($message));
    $allowed_types = ['chat', 'system', 'fraud', 'order'];
    $allowed_severity = ['low', 'medium', 'high'];

    if (!in_array($type, $allowed_types, true)) {
        $type = 'system';
    }

    if (!in_array($severity, $allowed_severity, true)) {
        $severity = 'low';
    }

    if (column_exists($conn, 'notifications', 'title')) {
        $title = ucwords($type) . ' Notice';
        $stmt = $conn->prepare('
            INSERT INTO notifications (user_id, title, message, type, severity, related_id, is_read)
            VALUES (?, ?, ?, ?, ?, ?, 0)
        ');
        $stmt->bind_param('issssi', $user_id, $title, $message, $type, $severity, $related_id);
        $stmt->execute();
        return;
    }

    $stmt = $conn->prepare('
        INSERT INTO notifications (user_id, message, type, severity, related_id, is_read)
        VALUES (?, ?, ?, ?, ?, 0)
    ');
    $stmt->bind_param('isssi', $user_id, $message, $type, $severity, $related_id);
    $stmt->execute();
}

function mark_order_paid(mysqli $conn, int $order_id): bool
{
    $stmt = $conn->prepare('
        SELECT orders.*, products.title, products.author, products.course_code
        FROM orders
        JOIN products ON products.id = orders.product_id
        WHERE orders.id = ?
        LIMIT 1
    ');
    $stmt->bind_param('i', $order_id);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();

    if (!$order) {
        return false;
    }

    if ($order['payment_status'] === 'paid') {
        return true;
    }

    $conn->begin_transaction();

    try {
        $update_order = $conn->prepare('
            UPDATE orders
            SET payment_status = "paid", order_status = "awaiting_pickup"
            WHERE id = ? AND payment_status = "pending"
        ');
        $update_order->bind_param('i', $order_id);
        $update_order->execute();

        if ($update_order->affected_rows === 0) {
            throw new RuntimeException('Order is not pending payment.');
        }

        $product_id = (int) $order['product_id'];
        $seller_id = (int) $order['seller_id'];
        $buyer_id = (int) $order['buyer_id'];
        $seller_payout = (float) $order['seller_payout'];
        $commission = (float) $order['commission'];
        $amount = (float) $order['amount'];

        $product = $conn->prepare('UPDATE products SET status = "sold", quantity = 0 WHERE id = ?');
        $product->bind_param('i', $product_id);
        $product->execute();

        $remove_cart = $conn->prepare('DELETE FROM cart WHERE product_id = ?');
        $remove_cart->bind_param('i', $product_id);
        $remove_cart->execute();

        $update_user_wallet = $conn->prepare('UPDATE users SET wallet_balance = wallet_balance + ? WHERE user_id = ?');
        $update_user_wallet->bind_param('di', $seller_payout, $seller_id);
        $update_user_wallet->execute();

        if (table_exists($conn, 'wallets')) {
            $wallet_update = $conn->prepare('UPDATE wallets SET balance = balance + ?, updated_at = NOW() WHERE user_id = ?');
            $wallet_update->bind_param('di', $seller_payout, $seller_id);
            $wallet_update->execute();

            if ($wallet_update->affected_rows === 0) {
                $wallet_insert = $conn->prepare('INSERT INTO wallets (user_id, balance, updated_at) VALUES (?, ?, NOW())');
                $wallet_insert->bind_param('id', $seller_id, $seller_payout);
                $wallet_insert->execute();
            }
        }

        $existing_tx = $conn->prepare('
            SELECT id FROM wallet_transactions
            WHERE order_id = ? AND user_id = ? AND type = "sale"
            LIMIT 1
        ');
        $existing_tx->bind_param('ii', $order_id, $seller_id);
        $existing_tx->execute();

        if (!$existing_tx->get_result()->fetch_assoc()) {
            $wallet_tx = $conn->prepare('
                INSERT INTO wallet_transactions (user_id, order_id, type, amount, description)
                VALUES (?, ?, "sale", ?, "Textbook sale payout credited after payment")
            ');
            $wallet_tx->bind_param('iid', $seller_id, $order_id, $seller_payout);
            $wallet_tx->execute();
        }

        $existing_rev = $conn->prepare('SELECT id FROM platform_revenue WHERE order_id = ? LIMIT 1');
        $existing_rev->bind_param('i', $order_id);
        $existing_rev->execute();

        if (!$existing_rev->get_result()->fetch_assoc()) {
            $revenue = $conn->prepare('INSERT INTO platform_revenue (order_id, amount, commission) VALUES (?, ?, ?)');
            $revenue->bind_param('idd', $order_id, $amount, $commission);
            $revenue->execute();
        }

        $conn->commit();

        $title = $order['title'] ?? 'your textbook';
        $pickup_location = $order['pickup_location'] ?? 'the agreed campus pickup point';
        $seller_chat_link = site_url('chat.php?user_id=' . $buyer_id . '&order_id=' . $order_id . '#textbook-reference');
        $buyer_chat_link = site_url('chat.php?user_id=' . $seller_id . '&order_id=' . $order_id . '#textbook-reference');

        $existing_chat = $conn->prepare('
            SELECT id
            FROM messages
            WHERE order_id = ? AND sender_id = ? AND receiver_id = ?
            LIMIT 1
        ');
        $existing_chat->bind_param('iii', $order_id, $buyer_id, $seller_id);
        $existing_chat->execute();

        if (!$existing_chat->get_result()->fetch_assoc()) {
            $module = trim((string) ($order['course_code'] ?? ''));
            $chat_message = 'Payment received for "' . $title . '". Please use this chat to agree on a campus pickup time and location.';
            if ($module !== '') {
                $chat_message .= ' Module: ' . $module . '.';
            }

            $create_chat = $conn->prepare('
                INSERT INTO messages (sender_id, receiver_id, order_id, message)
                VALUES (?, ?, ?, ?)
            ');
            $create_chat->bind_param('iiis', $buyer_id, $seller_id, $order_id, $chat_message);
            $create_chat->execute();
        }

        create_bookmart_notification(
            $conn,
            $seller_id,
            'order',
            'Buyer payment received for "' . $title . '". Your earnings were credited after commission. Open the textbook chat: ' . $seller_chat_link,
            'medium',
            $order_id
        );
        create_bookmart_notification(
            $conn,
            $buyer_id,
            'order',
            'Payment received for "' . $title . '". The seller has been notified. Open the textbook chat: ' . $buyer_chat_link,
            'medium',
            $order_id
        );

        return true;
    } catch (Throwable $e) {
        $conn->rollback();
        return false;
    }
}

function credit_seller_wallet(mysqli $conn, int $order_id): bool
{
    $stmt = $conn->prepare('
        UPDATE orders
        SET order_status = "completed", pickup_confirmed_at = NOW()
        WHERE id = ? AND payment_status = "paid" AND order_status = "awaiting_pickup"
    ');
    $stmt->bind_param('i', $order_id);
    return $stmt->execute() && $stmt->affected_rows > 0;
}

function sanitize_filename(string $filename): string
{
    return preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
}

function validate_id_passport(string $value): bool
{
    $value = preg_replace('/\s+/', '', $value);
    return (bool) preg_match('/^[A-Za-z0-9]{6,20}$/', $value);
}

function upload_profile_image(array $file): ?string
{
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $allowed = ['image/jpeg', 'image/png', 'image/webp'];
    if (!in_array($file['type'], $allowed, true)) {
        return null;
    }

    if ($file['size'] > 2 * 1024 * 1024) {
        return null;
    }

    if (!is_dir(PROFILE_UPLOAD_DIR)) {
        mkdir(PROFILE_UPLOAD_DIR, 0777, true);
    }

    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('profile_', true) . '.' . strtolower($extension);
    $target = PROFILE_UPLOAD_DIR . $filename;

    if (move_uploaded_file($file['tmp_name'], $target)) {
        return $filename;
    }

    return null;
}

function get_user_payout_details(array $user): array
{
    return [
        'account_holder' => $user['payout_account_holder'] ?? '',
        'bank_name' => $user['payout_bank_name'] ?? '',
        'account_number' => $user['payout_account_number'] ?? '',
        'branch_code' => $user['payout_branch_code'] ?? '',
        'account_type' => $user['payout_account_type'] ?? '',
    ];
}

function validate_payout_details(array $data): ?string
{
    $holder = trim($data['account_holder'] ?? '');
    $bank = trim($data['bank_name'] ?? '');
    $number = preg_replace('/\s+/', '', $data['account_number'] ?? '');
    $branch = preg_replace('/\s+/', '', $data['branch_code'] ?? '');
    $type = $data['account_type'] ?? '';

    if ($holder === '' || $bank === '' || $number === '' || $branch === '') {
        return 'Enter full bank account details (account holder, bank, account number, branch code).';
    }

    if (!preg_match('/^[0-9]{6,20}$/', $number)) {
        return 'Account number must be 6–20 digits.';
    }

    if (!preg_match('/^[0-9]{6}$/', $branch)) {
        return 'Branch code must be 6 digits.';
    }

    if (!in_array($type, ['cheque', 'savings'], true)) {
        return 'Select account type (Cheque/Current or Savings).';
    }

    return null;
}

function save_user_payout_details(mysqli $conn, int $user_id, array $data): bool
{
    $stmt = $conn->prepare('
        UPDATE users
        SET payout_account_holder = ?, payout_bank_name = ?, payout_account_number = ?,
            payout_branch_code = ?, payout_account_type = ?
        WHERE user_id = ?
    ');
    $stmt->bind_param(
        'sssssi',
        $data['account_holder'],
        $data['bank_name'],
        $data['account_number'],
        $data['branch_code'],
        $data['account_type'],
        $user_id
    );

    return $stmt->execute();
}

function mask_account_number(string $number): string
{
    $number = preg_replace('/\s+/', '', $number);
    if (strlen($number) <= 4) {
        return $number;
    }

    return str_repeat('*', strlen($number) - 4) . substr($number, -4);
}

function payout_account_type_label(string $type): string
{
    return $type === 'savings' ? 'Savings' : 'Cheque / Current';
}

function profile_image_url(?string $filename): string
{
    if ($filename) {
        return PROFILE_UPLOAD_URL . rawurlencode($filename);
    }

    return SITE_URL . '/assets/img/default-avatar.svg';
}

function upload_product_image(array $file): ?string
{
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $allowed = ['image/jpeg', 'image/png', 'image/webp'];
    if (!in_array($file['type'], $allowed, true)) {
        return null;
    }

    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0777, true);
    }

    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('book_', true) . '.' . strtolower($extension);
    $target = UPLOAD_DIR . $filename;

    if (move_uploaded_file($file['tmp_name'], $target)) {
        return $filename;
    }

    return null;
}

function book_condition_label(string $condition): string
{
    $labels = [
        'new' => 'New',
        'like_new' => 'Like New',
        'good' => 'Good',
        'fair' => 'Fair',
    ];

    return $labels[$condition] ?? ucfirst($condition);
}

function order_status_badge(string $status): string
{
    $classes = [
        'processing' => 'bg-secondary',
        'awaiting_pickup' => 'bg-success',
        'completed' => 'bg-success',
        'cancelled' => 'bg-danger',
    ];

    $class = $classes[$status] ?? 'bg-secondary';
    $labels = [
        'awaiting_pickup' => 'Completed',
    ];
    $label = $labels[$status] ?? ucwords(str_replace('_', ' ', $status));

    return '<span class="badge ' . $class . '">' . htmlspecialchars($label) . '</span>';
}

function upload_student_card(array $file): ?string
{
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $allowed = ['image/jpeg', 'image/png', 'image/webp'];
    if (!in_array($file['type'], $allowed, true)) {
        return null;
    }

    if ($file['size'] > 3 * 1024 * 1024) {
        return null;
    }

    $dir = UPLOAD_DIR . 'student_cards/';
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('student_', true) . '.' . strtolower($extension);
    $target = $dir . $filename;

    if (move_uploaded_file($file['tmp_name'], $target)) {
        return 'student_cards/' . $filename;
    }

    return null;
}

function student_card_url(?string $filename): string
{
    if (!$filename) {
        return '';
    }

    return site_url('uploads/' . ltrim($filename, '/'));
}

function get_user_missing_fields(mysqli $conn, array $user): array
{
    $missing = [];

    if (!user_has_profile_photo($conn, (int) $user['user_id'])) {
        $missing[] = [
            'message' => 'Profile photo required for campus pickups.',
            'action' => 'Take photo now',
            'link' => 'profile.php?required=1',
        ];
    }

    if (column_exists($conn, 'users', 'student_card_image')) {
        $card = $user['student_card_image'] ?? '';
        if ($card === '' || !is_file(UPLOAD_DIR . ltrim($card, '/'))) {
            $missing[] = [
                'message' => 'Student card not uploaded.',
                'action' => 'Upload student card',
                'link' => 'personal_info.php',
            ];
        }
    }

    if (column_exists($conn, 'users', 'course')) {
        if (trim($user['course'] ?? '') === '') {
            $missing[] = [
                'message' => 'Course not set — helps match textbook listings.',
                'action' => 'Add course',
                'link' => 'personal_info.php',
            ];
        }
    }

    if (empty($user['university_id']) || empty($user['campus_id'])) {
        $missing[] = [
            'message' => 'University and campus not set.',
            'action' => 'Update location',
            'link' => 'personal_info.php',
        ];
    }

    return $missing;
}

function get_seller_analytics(mysqli $conn, int $user_id): array
{
    $stats = [
        'total_sales' => 0,
        'total_earnings' => 0.0,
        'total_listings' => 0,
        'active_listings' => 0,
        'pending_listings' => 0,
        'completed_orders' => 0,
        'sales_by_month' => [],
        'earnings_by_month' => [],
    ];

    if (!table_exists($conn, 'orders')) {
        return $stats;
    }

    $totals = $conn->prepare('
        SELECT COUNT(*) AS sales, COALESCE(SUM(seller_payout), 0) AS earnings
        FROM orders WHERE seller_id = ? AND payment_status = "paid"
    ');
    $totals->bind_param('i', $user_id);
    $totals->execute();
    $row = $totals->get_result()->fetch_assoc();
    $stats['total_sales'] = (int) ($row['sales'] ?? 0);
    $stats['total_earnings'] = (float) ($row['earnings'] ?? 0);

    $completed = $conn->prepare('SELECT COUNT(*) AS c FROM orders WHERE seller_id = ? AND order_status = "completed"');
    $completed->bind_param('i', $user_id);
    $completed->execute();
    $stats['completed_orders'] = (int) $completed->get_result()->fetch_assoc()['c'];

    if (table_exists($conn, 'products')) {
        $listings = $conn->prepare('
            SELECT
                COUNT(*) AS total,
                SUM(status = "approved") AS active,
                SUM(status = "pending") AS pending
            FROM products WHERE user_id = ?
        ');
        $listings->bind_param('i', $user_id);
        $listings->execute();
        $l = $listings->get_result()->fetch_assoc();
        $stats['total_listings'] = (int) ($l['total'] ?? 0);
        $stats['active_listings'] = (int) ($l['active'] ?? 0);
        $stats['pending_listings'] = (int) ($l['pending'] ?? 0);
    }

    $months = $conn->prepare('
        SELECT DATE_FORMAT(created_at, "%Y-%m") AS month, COUNT(*) AS sales, COALESCE(SUM(seller_payout), 0) AS earnings
        FROM orders
        WHERE seller_id = ? AND payment_status = "paid" AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY month ORDER BY month ASC
    ');
    $months->bind_param('i', $user_id);
    $months->execute();
    $result = $months->get_result();
    while ($m = $result->fetch_assoc()) {
        $stats['sales_by_month'][$m['month']] = (int) $m['sales'];
        $stats['earnings_by_month'][$m['month']] = (float) $m['earnings'];
    }

    return $stats;
}

function chat_room_key(int $user_a, int $user_b): string
{
    $ids = [$user_a, $user_b];
    sort($ids);

    return 'chat_' . $ids[0] . '_' . $ids[1];
}
