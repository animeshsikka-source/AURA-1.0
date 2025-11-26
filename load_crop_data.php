<?php
// load_crop_data.php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

if (!file_exists("crop_data.json")) {
    echo json_encode(["crop"=>"", "sowing_date"=>""]);
    exit;
}

echo file_get_contents("crop_data.json");
?>
