<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$hp = isset($_GET['hp']) ? intval($_GET['hp']) : 0;
$esp = "http://172.21.125.29/motor/on";

$ch = curl_init($esp);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

// Log motor ON event
$conn = new mysqli("localhost", "iotuser", "iotpass", "smart_agri");
if (!$conn->connect_error) {
    $stmt = $conn->prepare("INSERT INTO motor_actions (action, motor_hp, remarks) VALUES ('ON', ?, 'Triggered via dashboard')");
    $stmt->bind_param("i", $hp);
    $stmt->execute();
    $stmt->close();
}
$conn->close();

echo $response;
?>