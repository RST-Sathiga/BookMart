<?php

require_once __DIR__ . '/../config/config.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

$columns = [
    'latitude' => "ADD COLUMN latitude DECIMAL(10, 7) NULL AFTER city",
    'longitude' => "ADD COLUMN longitude DECIMAL(10, 7) NULL AFTER latitude",
];

$campus_columns = [
    'latitude' => "ADD COLUMN latitude DECIMAL(10, 7) NULL AFTER pickup_point",
    'longitude' => "ADD COLUMN longitude DECIMAL(10, 7) NULL AFTER latitude",
];

foreach ($columns as $name => $alter) {
    $check = $conn->query("SHOW COLUMNS FROM universities LIKE '$name'");
    if ($check->num_rows === 0) {
        $conn->query("ALTER TABLE universities $alter");
    }
}

foreach ($campus_columns as $name => $alter) {
    $check = $conn->query("SHOW COLUMNS FROM campuses LIKE '$name'");
    if ($check->num_rows === 0) {
        $conn->query("ALTER TABLE campuses $alter");
    }
}

$city_coords = [
    'Pretoria' => [-25.7479, 28.2293],
    'Johannesburg' => [-26.2041, 28.0473],
    'Cape Town' => [-33.9249, 18.4241],
    'Stellenbosch' => [-33.9321, 18.8602],
    'Durban' => [-29.8587, 31.0218],
    'Potchefstroom' => [-26.7145, 27.0970],
    'Bloemfontein' => [-29.0852, 26.1596],
    'Midrand' => [-25.9992, 28.1264],
    'Randburg' => [-26.0935, 28.0094],
];

foreach ($city_coords as $city => $coords) {
    $lat = $coords[0];
    $lng = $coords[1];
    $stmt = $conn->prepare('UPDATE universities SET latitude = ?, longitude = ? WHERE city = ? AND (latitude IS NULL OR longitude IS NULL)');
    $stmt->bind_param('dds', $lat, $lng, $city);
    $stmt->execute();
}

$conn->query('
    UPDATE campuses c
    JOIN universities u ON c.university_id = u.id
    SET c.latitude = u.latitude, c.longitude = u.longitude
    WHERE c.latitude IS NULL AND u.latitude IS NOT NULL
');

echo 'GPS location migration complete. <a href="' . PLATFORM_URL . '">Open BookMart</a>';
