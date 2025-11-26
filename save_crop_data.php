<?php
// save_crop_data.php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

$crop = $_GET["crop"] ?? "";
$date = $_GET["sowing_date"] ?? "";

// Save locally
file_put_contents("crop_data.json", json_encode([
    "crop" => $crop,
    "sowing_date" => $date
], JSON_PRETTY_PRINT));

echo json_encode(["ok"=>true]);
?>
