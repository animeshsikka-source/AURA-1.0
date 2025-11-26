<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

// DB Connection
$conn = new mysqli("localhost", "iotuser", "iotpass", "smart_agri");

if ($conn->connect_error) {
    die(json_encode(["error" => "DB connection failed: " . $conn->connect_error]));
}

// Read URL parameters
$temp = $_GET['temperature'] ?? null;
$hum = $_GET['humidity'] ?? null;
$moist = $_GET['moisture_pct'] ?? null;
$fire = $_GET['fire_detected'] ?? null;
$us1 = $_GET['ultrasonic1_cm'] ?? null;
$us2 = $_GET['ultrasonic2_cm'] ?? null;
$cap = $_GET['capacity_pct'] ?? null;
$motor = $_GET['motor_on'] ?? null;

$sql = "INSERT INTO sensor_data 
(temperature, humidity, moisture_pct, fire_detected, ultrasonic1_cm, ultrasonic2_cm, capacity_pct, motor_on)
VALUES ('$temp', '$hum', '$moist', '$fire', '$us1', '$us2', '$cap', '$motor')";

if ($conn->query($sql) === TRUE) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["error" => $conn->error]);
}

$conn->close();
?>