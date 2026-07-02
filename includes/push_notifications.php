<?php
$data = [
    "userId" => 1,
    "message" => "Your listing was approved",
    "type" => "listing"
];

$options = [
    "http" => [
        "header"  => "Content-type: application/json",
        "method"  => "POST",
        "content" => json_encode($data)
    ]
];

$context = stream_context_create($options);
file_get_contents("http://localhost:3000", false, $context);
?>