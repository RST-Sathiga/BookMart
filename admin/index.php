<?php

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

require_once __DIR__ . '/includes/analytics_data.php';

$analytics = get_admin_analytics($conn);
$summary = $analytics['summary'];
$revenue = $analytics['revenue'];
$withdrawals = $analytics['withdrawals'];
$recent = $analytics['recent'];

$admin_user = get_user_by_id($conn, current_user_id());

$page_title = 'Admin Analytics Dashboard';
require_once __DIR__ . '/includes/admin_header.php';
?>

<style>
    .analytics-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 1rem;
    }

    .analytics-card {
        background: #fff;
        border: 1px solid rgba(15, 23, 42, .08);
        border-radius: 8px;
        box-shadow: 0 10px 25px rgba(15, 23, 42, .06);
        min-height: 118px;
    }

    .chart-panel {
        min-height: 340px;
    }

    .chart-panel canvas {
        width: 100% !important;
        max-height: 280px;
    }

    .table-sm td,
    .table-sm th {
        white-space: nowrap;
    }
</style>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="section-title h3 mb-1">Admin Analytics Dashboard</h1>
        <p class="text-muted mb-0">Live marketplace, revenue, seller wallet, and withdrawal activity.</p>
    </div>

    <div class="d-flex flex-wrap gap-2">
        <a href="<?= site_url('manage_listings.php?status=pending') ?>" class="btn btn-warning btn-sm">
            <?= (int) $summary['pending_listings'] ?> pending listings
        </a>
        <a href="<?= site_url('manage_withdrawals.php?status=pending') ?>" class="btn btn-gold btn-sm">
            <?= (int) $withdrawals['pending'] ?> pending withdrawals
        </a>
    </div>
</div>

<?php if ($admin_user): ?>
<div class="analytics-card p-4 mb-4">
    <div class="d-flex flex-wrap align-items-center gap-3">
        <img src="<?= profile_image_url($admin_user['profile_image'] ?? null) ?>" alt="Admin" class="rounded-circle" width="64" height="64" style="object-fit:cover">
        <div>
            <h2 class="h5 mb-1"><?= htmlspecialchars($admin_user['fullname']) ?></h2>
            <p class="text-muted small mb-0"><?= htmlspecialchars($admin_user['email']) ?> &middot; Administrator</p>
        </div>
    </div>
</div>
<?php endif; ?>

<section class="mb-4">
    <div class="analytics-grid">
        <?php
        $cards = [
            ['Total Registered Users', $summary['total_users'], 'bi-people', 'text-primary'],
            ['Total Active Listings', $summary['active_listings'], 'bi-book', 'text-success'],
            ['Total Books Sold', $summary['books_sold'], 'bi-bag-check', 'text-primary'],
            ['Pending Listings', $summary['pending_listings'], 'bi-hourglass-split', 'text-warning'],
            ['Active Sellers', $summary['active_sellers'], 'bi-shop', 'text-success'],
            ['New Users This Month', $summary['new_users_month'], 'bi-person-plus', 'text-primary'],
        ];
        ?>
        <?php foreach ($cards as [$label, $value, $icon, $class]): ?>
            <div class="analytics-card p-3">
                <div class="d-flex justify-content-between align-items-start">
                    <p class="text-muted small mb-2"><?= htmlspecialchars($label) ?></p>
                    <i class="bi <?= htmlspecialchars($icon) ?> <?= htmlspecialchars($class) ?>"></i>
                </div>
                <div class="h3 mb-0 <?= htmlspecialchars($class) ?>"><?= (int) $value ?></div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="mb-4">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h2 class="h5 text-primary mb-0">Revenue Overview</h2>
    </div>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="analytics-card p-3 h-100 chart-panel">
                <h3 class="h6 mb-3">Revenue Distribution</h3>
                <canvas id="chartRevenueDistribution"></canvas>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="analytics-grid">
                <div class="analytics-card p-3">
                    <p class="text-muted small mb-1">Total Marketplace Revenue</p>
                    <div class="h4 mb-0"><?= format_currency((float) $revenue['marketplace_revenue']) ?></div>
                </div>
                <div class="analytics-card p-3">
                    <p class="text-muted small mb-1">Total Seller Earnings</p>
                    <div class="h4 mb-0"><?= format_currency((float) $revenue['seller_earnings']) ?></div>
                </div>
                <div class="analytics-card p-3">
                    <p class="text-muted small mb-1">Total Platform Commission</p>
                    <div class="h4 mb-0"><?= format_currency((float) $revenue['platform_commission']) ?></div>
                </div>
                <div class="analytics-card p-3">
                    <p class="text-muted small mb-1">Commission Collected This Month</p>
                    <div class="h4 mb-0"><?= format_currency((float) $revenue['commission_month']) ?></div>
                </div>
                <div class="analytics-card p-3">
                    <p class="text-muted small mb-1">Revenue Generated This Month</p>
                    <div class="h4 mb-0"><?= format_currency((float) $revenue['revenue_month']) ?></div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="mb-4">
    <h2 class="h5 text-primary mb-3">Withdrawals</h2>
    <div class="analytics-grid">
        <?php
        $withdrawal_cards = [
            ['Total Withdrawal Requests', $withdrawals['total_requests'], false],
            ['Pending Withdrawals', $withdrawals['pending'], false],
            ['Approved Withdrawals', $withdrawals['approved'], false],
            ['Rejected Withdrawals', $withdrawals['rejected'], false],
            ['Total Amount Withdrawn', $withdrawals['amount_withdrawn'], true],
            ['Total Pending Withdrawal Amount', $withdrawals['pending_amount'], true],
            ['Average Withdrawal Amount', $withdrawals['average_amount'], true],
        ];
        ?>
        <?php foreach ($withdrawal_cards as [$label, $value, $money]): ?>
            <div class="analytics-card p-3">
                <p class="text-muted small mb-1"><?= htmlspecialchars($label) ?></p>
                <div class="h4 mb-0"><?= $money ? format_currency((float) $value) : (int) $value ?></div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="mb-4">
    <h2 class="h5 text-primary mb-3">Analytics Charts</h2>
    <div class="row g-4">
        <div class="col-xl-4 col-lg-6">
            <div class="analytics-card p-3 chart-panel">
                <h3 class="h6 mb-3">Books Sold Per Month</h3>
                <canvas id="chartBooksSold"></canvas>
            </div>
        </div>
        <div class="col-xl-4 col-lg-6">
            <div class="analytics-card p-3 chart-panel">
                <h3 class="h6 mb-3">New User Registrations Per Month</h3>
                <canvas id="chartRegistrations"></canvas>
            </div>
        </div>
        <div class="col-xl-4 col-lg-6">
            <div class="analytics-card p-3 chart-panel">
                <h3 class="h6 mb-3">Seller Earnings vs Commission</h3>
                <canvas id="chartRevenuePie"></canvas>
            </div>
        </div>
    </div>
</section>

<section>
    <h2 class="h5 text-primary mb-3">Recent Activity</h2>
    <div class="row g-4">
        <div class="col-xl-6">
            <div class="analytics-card p-3">
                <h3 class="h6">Recent User Registrations</h3>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead><tr><th>Name</th><th>Email</th><th>Status</th><th>Joined</th></tr></thead>
                        <tbody>
                            <?php if (!$recent['users']): ?><tr><td colspan="4" class="text-muted text-center py-3">No records found.</td></tr><?php endif; ?>
                            <?php foreach ($recent['users'] as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['fullname']) ?></td>
                                    <td><?= htmlspecialchars($row['email']) ?></td>
                                    <td><?= htmlspecialchars(ucfirst($row['status'])) ?></td>
                                    <td><?= htmlspecialchars($row['created_at']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="analytics-card p-3">
                <h3 class="h6">Recent Listings</h3>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead><tr><th>Book</th><th>Seller</th><th>Price</th><th>Status</th></tr></thead>
                        <tbody>
                            <?php if (!$recent['listings']): ?><tr><td colspan="4" class="text-muted text-center py-3">No records found.</td></tr><?php endif; ?>
                            <?php foreach ($recent['listings'] as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['title']) ?></td>
                                    <td><?= htmlspecialchars($row['seller_name']) ?></td>
                                    <td><?= format_currency((float) $row['price']) ?></td>
                                    <td><?= htmlspecialchars(ucfirst($row['status'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="analytics-card p-3">
                <h3 class="h6">Recent Completed Sales</h3>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead><tr><th>Book</th><th>Buyer</th><th>Seller</th><th>Total</th><th>Commission</th></tr></thead>
                        <tbody>
                            <?php if (!$recent['sales']): ?><tr><td colspan="5" class="text-muted text-center py-3">No records found.</td></tr><?php endif; ?>
                            <?php foreach ($recent['sales'] as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['title']) ?></td>
                                    <td><?= htmlspecialchars($row['buyer_name']) ?></td>
                                    <td><?= htmlspecialchars($row['seller_name']) ?></td>
                                    <td><?= format_currency((float) $row['amount']) ?></td>
                                    <td><?= format_currency((float) $row['commission']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="analytics-card p-3">
                <h3 class="h6">Recent Withdrawal Requests</h3>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead><tr><th>Seller</th><th>Amount</th><th>Status</th><th>Requested</th></tr></thead>
                        <tbody>
                            <?php if (!$recent['withdrawals']): ?><tr><td colspan="4" class="text-muted text-center py-3">No records found.</td></tr><?php endif; ?>
                            <?php foreach ($recent['withdrawals'] as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['seller_name']) ?></td>
                                    <td><?= format_currency((float) $row['amount']) ?></td>
                                    <td><?= htmlspecialchars(ucfirst($row['status'])) ?></td>
                                    <td><?= htmlspecialchars($row['created_at']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const analytics = <?= json_encode($analytics, JSON_NUMERIC_CHECK) ?>;
const chartColors = ['#0d6efd', '#d4a017', '#198754', '#6c757d'];
const revenueValues = [
    analytics.charts.revenue_distribution.seller || 0,
    analytics.charts.revenue_distribution.commission || 0
];

function emptyAware(values) {
    return values.some(value => Number(value) > 0) ? values : [0, 0];
}

new Chart(document.getElementById('chartRevenueDistribution'), {
    type: 'pie',
    data: {
        labels: ['Total Seller Earnings', 'Total Platform Commission'],
        datasets: [{ data: emptyAware(revenueValues), backgroundColor: [chartColors[0], chartColors[1]] }]
    }
});

new Chart(document.getElementById('chartBooksSold'), {
    type: 'line',
    data: {
        labels: analytics.charts.books_sold_month.labels || [],
        datasets: [{
            label: 'Books Sold',
            data: analytics.charts.books_sold_month.values || [],
            borderColor: chartColors[0],
            backgroundColor: 'rgba(13, 110, 253, .12)',
            tension: .35,
            fill: true
        }]
    },
    options: { responsive: true, maintainAspectRatio: false }
});

new Chart(document.getElementById('chartRegistrations'), {
    type: 'bar',
    data: {
        labels: analytics.charts.registrations_month.labels || [],
        datasets: [{
            label: 'New Users',
            data: analytics.charts.registrations_month.values || [],
            backgroundColor: chartColors[2]
        }]
    },
    options: { responsive: true, maintainAspectRatio: false }
});

new Chart(document.getElementById('chartRevenuePie'), {
    type: 'doughnut',
    data: {
        labels: ['Seller Earnings', 'Platform Commission'],
        datasets: [{ data: emptyAware(revenueValues), backgroundColor: [chartColors[0], chartColors[1]] }]
    },
    options: { responsive: true, maintainAspectRatio: false }
});
</script>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
