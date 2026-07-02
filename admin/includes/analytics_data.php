<?php

function admin_table_count(mysqli $conn, string $table, string $where = '1=1'): int
{
    if (!table_exists($conn, $table)) {
        return 0;
    }

    $result = $conn->query("SELECT COUNT(*) AS total FROM `$table` WHERE $where");
    if (!$result) {
        return 0;
    }

    return (int) ($result->fetch_assoc()['total'] ?? 0);
}

function admin_scalar(mysqli $conn, string $sql, string $field = 'value'): float
{
    $result = $conn->query($sql);
    if (!$result) {
        return 0.0;
    }

    return (float) ($result->fetch_assoc()[$field] ?? 0);
}

function admin_rows(mysqli $conn, string $sql): array
{
    $result = $conn->query($sql);
    if (!$result) {
        return [];
    }

    return $result->fetch_all(MYSQLI_ASSOC);
}

function admin_month_series(mysqli $conn, string $sql): array
{
    $labels = [];
    $values = [];

    $result = $conn->query($sql);
    if (!$result) {
        return ['labels' => $labels, 'values' => $values];
    }

    while ($row = $result->fetch_assoc()) {
        $labels[] = $row['label'];
        $values[] = (float) $row['value'];
    }

    return ['labels' => $labels, 'values' => $values];
}

function admin_paid_orders_where(): string
{
    return 'payment_status = "paid" AND order_status <> "cancelled"';
}

function get_admin_analytics(mysqli $conn): array
{
    $paid_where = admin_paid_orders_where();

    $data = [
        'summary' => [
            'total_users' => 0,
            'active_listings' => 0,
            'books_sold' => 0,
            'pending_listings' => 0,
            'active_sellers' => 0,
            'new_users_month' => 0,
        ],
        'revenue' => [
            'marketplace_revenue' => 0.0,
            'seller_earnings' => 0.0,
            'platform_commission' => 0.0,
            'commission_month' => 0.0,
            'revenue_month' => 0.0,
        ],
        'withdrawals' => [
            'total_requests' => 0,
            'pending' => 0,
            'approved' => 0,
            'rejected' => 0,
            'amount_withdrawn' => 0.0,
            'pending_amount' => 0.0,
            'average_amount' => 0.0,
        ],
        'charts' => [
            'books_sold_month' => ['labels' => [], 'values' => []],
            'registrations_month' => ['labels' => [], 'values' => []],
            'revenue_distribution' => ['seller' => 0.0, 'commission' => 0.0],
        ],
        'recent' => [
            'users' => [],
            'listings' => [],
            'sales' => [],
            'withdrawals' => [],
        ],
    ];

    if (table_exists($conn, 'users')) {
        $data['summary']['total_users'] = admin_table_count($conn, 'users');
        $data['summary']['new_users_month'] = admin_table_count(
            $conn,
            'users',
            'created_at >= DATE_FORMAT(CURDATE(), "%Y-%m-01")'
        );

        $data['charts']['registrations_month'] = admin_month_series($conn, '
            SELECT DATE_FORMAT(created_at, "%b %Y") AS label, COUNT(*) AS value
            FROM users
            WHERE created_at >= DATE_SUB(DATE_FORMAT(CURDATE(), "%Y-%m-01"), INTERVAL 11 MONTH)
            GROUP BY YEAR(created_at), MONTH(created_at)
            ORDER BY YEAR(created_at), MONTH(created_at)
        ');

        $data['recent']['users'] = admin_rows($conn, '
            SELECT fullname, email, role, status, created_at
            FROM users
            ORDER BY created_at DESC
            LIMIT 8
        ');
    }

    if (table_exists($conn, 'products')) {
        $data['summary']['active_listings'] = admin_table_count($conn, 'products', 'status = "approved"');
        $data['summary']['pending_listings'] = admin_table_count($conn, 'products', 'status = "pending"');

        $data['recent']['listings'] = admin_rows($conn, '
            SELECT products.id, products.title, products.price, products.status, products.created_at,
                   users.fullname AS seller_name
            FROM products
            JOIN users ON users.user_id = products.user_id
            ORDER BY products.created_at DESC
            LIMIT 8
        ');
    }

    if (table_exists($conn, 'orders')) {
        $data['summary']['books_sold'] = admin_table_count($conn, 'orders', $paid_where);
        $data['summary']['active_sellers'] = (int) admin_scalar($conn, "
            SELECT COUNT(DISTINCT seller_id) AS value
            FROM orders
            WHERE $paid_where
        ");

        $totals = admin_rows($conn, "
            SELECT
                COALESCE(SUM(amount), 0) AS marketplace_revenue,
                COALESCE(SUM(seller_payout), 0) AS seller_earnings,
                COALESCE(SUM(commission), 0) AS platform_commission
            FROM orders
            WHERE $paid_where
        ");
        if ($totals) {
            $data['revenue']['marketplace_revenue'] = (float) $totals[0]['marketplace_revenue'];
            $data['revenue']['seller_earnings'] = (float) $totals[0]['seller_earnings'];
            $data['revenue']['platform_commission'] = (float) $totals[0]['platform_commission'];
        }

        $month = admin_rows($conn, "
            SELECT
                COALESCE(SUM(amount), 0) AS revenue_month,
                COALESCE(SUM(commission), 0) AS commission_month
            FROM orders
            WHERE $paid_where
              AND created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
        ");
        if ($month) {
            $data['revenue']['revenue_month'] = (float) $month[0]['revenue_month'];
            $data['revenue']['commission_month'] = (float) $month[0]['commission_month'];
        }

        $data['charts']['books_sold_month'] = admin_month_series($conn, "
            SELECT DATE_FORMAT(created_at, '%b %Y') AS label, COUNT(*) AS value
            FROM orders
            WHERE $paid_where
              AND created_at >= DATE_SUB(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL 11 MONTH)
            GROUP BY YEAR(created_at), MONTH(created_at)
            ORDER BY YEAR(created_at), MONTH(created_at)
        ");

        $data['charts']['revenue_distribution'] = [
            'seller' => $data['revenue']['seller_earnings'],
            'commission' => $data['revenue']['platform_commission'],
        ];

        $data['recent']['sales'] = admin_rows($conn, "
            SELECT orders.id, orders.amount, orders.seller_payout, orders.commission, orders.created_at,
                   products.title, buyer.fullname AS buyer_name, seller.fullname AS seller_name
            FROM orders
            JOIN products ON products.id = orders.product_id
            JOIN users buyer ON buyer.user_id = orders.buyer_id
            JOIN users seller ON seller.user_id = orders.seller_id
            WHERE $paid_where
            ORDER BY orders.created_at DESC
            LIMIT 8
        ");
    }

    if (table_exists($conn, 'withdrawals')) {
        $data['withdrawals']['total_requests'] = admin_table_count($conn, 'withdrawals');
        $data['withdrawals']['pending'] = admin_table_count($conn, 'withdrawals', 'status = "pending"');
        $data['withdrawals']['approved'] = admin_table_count($conn, 'withdrawals', 'status IN ("approved", "completed")');
        $data['withdrawals']['rejected'] = admin_table_count($conn, 'withdrawals', 'status = "rejected"');
        $data['withdrawals']['amount_withdrawn'] = admin_scalar(
            $conn,
            'SELECT COALESCE(SUM(amount), 0) AS value FROM withdrawals WHERE status IN ("approved", "completed")'
        );
        $data['withdrawals']['pending_amount'] = admin_scalar(
            $conn,
            'SELECT COALESCE(SUM(amount), 0) AS value FROM withdrawals WHERE status = "pending"'
        );
        $data['withdrawals']['average_amount'] = admin_scalar(
            $conn,
            'SELECT COALESCE(AVG(amount), 0) AS value FROM withdrawals'
        );

        $data['recent']['withdrawals'] = admin_rows($conn, '
            SELECT withdrawals.id, withdrawals.amount, withdrawals.status, withdrawals.created_at,
                   users.fullname AS seller_name
            FROM withdrawals
            JOIN users ON users.user_id = withdrawals.seller_id
            ORDER BY withdrawals.created_at DESC
            LIMIT 8
        ');
    }

    return $data;
}
