<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$esp = "http://172.21.125.29/motor/off";
$ch = curl_init($esp);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

// Log event
$conn = new mysqli("localhost", "iotuser", "iotpass", "smart_agri");
if (!$conn->connect_error) {
    $conn->query("INSERT INTO motor_actions (action, remarks) VALUES ('OFF', 'Stopped via dashboard')");
}
$conn->close();

echo $response;
?>