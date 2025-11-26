<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$esp = "http://172.21.125.29/motor/status";
$response = @file_get_contents($esp);
if ($response === FALSE) {
    echo json_encode(["error" => "ESP not responding"]);
} else {
    echo $response;
}
?>