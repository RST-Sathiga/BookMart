<?php

require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

$latitude = (float) ($_GET['lat'] ?? 0);
$longitude = (float) ($_GET['lng'] ?? 0);
$university_id = (int) ($_GET['university_id'] ?? 0);

if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
    echo json_encode(['error' => 'Invalid coordinates.']);
    exit();
}

$universities = get_nearest_universities($conn, $latitude, $longitude, 8);
$campuses = get_nearest_campuses(
    $conn,
    $latitude,
    $longitude,
    $university_id > 0 ? $university_id : null,
    8
);

$nearest_university = $universities[0] ?? null;
$nearest_campus = $campuses[0] ?? null;

if ($nearest_university && $nearest_campus && (int) $nearest_campus['university_id'] !== (int) $nearest_university['id']) {
    $campuses_for_uni = get_nearest_campuses($conn, $latitude, $longitude, (int) $nearest_university['id'], 1);
    if ($campuses_for_uni) {
        $nearest_campus = $campuses_for_uni[0];
    }
}

echo json_encode([
    'universities' => $universities,
    'campuses' => $campuses,
    'nearest' => [
        'university_id' => $nearest_university['id'] ?? null,
        'campus_id' => $nearest_campus['id'] ?? null,
        'university_name' => $nearest_university['name'] ?? null,
        'campus_name' => $nearest_campus['name'] ?? null,
        'distance_km' => $nearest_campus['distance_km'] ?? ($nearest_university['distance_km'] ?? null),
    ],
]);
