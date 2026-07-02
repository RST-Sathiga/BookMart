<?php

$host = "sql208.infinityfree.com";
$username = "if0_42183234";
$password = "Bookmart2026";
$database = "if0_42183234_bookmart";

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");