<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

require_admin();

$stmt = $conn->prepare("
    SELECT f.*, u.fullname, u.email
    FROM fraud_logs f
    JOIN users u ON f.user_id = u.user_id
    ORDER BY f.created_at DESC
");

$stmt->execute();
$logs = $stmt->get_result();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">

<h2>Fraud Monitoring</h2>

<table class="table table-bordered">

<tr>
<th>User</th>
<th>Event</th>
<th>Risk Score</th>
<th>Details</th>
<th>Date</th>
</tr>

<?php while ($row = $logs->fetch_assoc()): ?>
<tr>
<td><?= htmlspecialchars($row['fullname']) ?></td>
<td><?= htmlspecialchars($row['event_type']) ?></td>
<td>
    <span class="badge bg-<?= $row['risk_score'] > 70 ? 'danger' : ($row['risk_score'] > 40 ? 'warning' : 'success') ?>">
        <?= (int)$row['risk_score'] ?>%
    </span>
</td>
<td><?= htmlspecialchars($row['details']) ?></td>
<td><?= $row['created_at'] ?></td>
</tr>
<?php endwhile; ?>

</table>

</div>