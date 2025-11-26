<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
$conn = new mysqli("localhost", "iotuser", "iotpass", "smart_agri");
$res = $conn->query("SELECT * FROM crop_data ORDER BY id DESC LIMIT 1");
echo json_encode($res->fetch_assoc() ?: []);
$conn->close();
?>