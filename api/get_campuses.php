<?php

require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

$university_id = (int) ($_GET['university_id'] ?? 0);

if ($university_id <= 0) {
    echo json_encode([]);
    exit();
}

$campuses = get_campuses_by_university($conn, $university_id);
echo json_encode($campuses);
