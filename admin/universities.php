<?php

require_once __DIR__ . '/includes/bootstrap.php';

$universities = $conn->query('
    SELECT universities.*, COUNT(campuses.id) AS campus_count
    FROM universities
    LEFT JOIN campuses ON campuses.university_id = universities.id
    GROUP BY universities.id
    ORDER BY universities.institution_type, universities.name
');

$page_title = 'Locations';
require_once __DIR__ . '/includes/admin_header.php';
?>

<h2 class="section-title h4 mb-4">Universities &amp; Campuses</h2>

<div class="row g-4">
    <?php while ($uni = $universities->fetch_assoc()): ?>
        <div class="col-lg-6">
            <div class="admin-card p-4">
                <h5><?= htmlspecialchars($uni['name']) ?></h5>
                <p class="text-muted small mb-3">
                    <span class="badge bg-<?= ($uni['institution_type'] ?? 'public') === 'private' ? 'warning text-dark' : (($uni['institution_type'] ?? '') === 'other' ? 'secondary' : 'primary') ?>">
                        <?= ucfirst($uni['institution_type'] ?? 'public') ?>
                    </span>
                    <?= htmlspecialchars($uni['city']) ?> · <?= (int) $uni['campus_count'] ?> campus(es)
                </p>
                <?php
                $campuses = get_campuses_by_university($conn, (int) $uni['id']);
                foreach ($campuses as $campus):
                ?>
                    <div class="border rounded p-2 mb-2">
                        <strong><?= htmlspecialchars($campus['name']) ?></strong>
                        <div class="small text-muted">Pickup: <?= htmlspecialchars($campus['pickup_point']) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endwhile; ?>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
