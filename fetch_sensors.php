<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// ESP8266 local IP address (update if changed)
$esp_ip = "http://172.21.125.29/sensors";

$response = @file_get_contents($esp_ip);

if ($response === FALSE) {
    echo json_encode(["error" => "ESP unreachable"]);
    exit;
}

// Decode ESP JSON
$data = json_decode($response, true);
if (!$data) {
    echo json_encode(["error" => "Invalid JSON from ESP"]);
    exit;
}

// Store to DB
file_get_contents(
    "http://localhost/data_insert.php?" .
    http_build_query($data)
);

// Return raw data to frontend
echo $response;
?>