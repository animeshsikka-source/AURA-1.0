<?php
// save_pest_history.php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

$db = new mysqli("localhost", "root", "", "aura");  // change auth if needed

$pest = $_POST["pest"] ?? "{}";
$data = json_decode($pest, true);

if (!$data) {
    echo json_encode(["ok"=>false, "error"=>"No pest data"]);
    exit;
}

// Insert into DB
$stmt = $db->prepare("INSERT INTO pest_history 
(pest_name, confidence, symptoms, organic_treatment, chemical_treatment, prevention, image_path) 
VALUES (?,?,?,?,?,?,?)");

$stmt->bind_param(
    "sdsssss",
    $data["name"],
    $data["confidence"],
    $data["symptoms"],
    $data["organic_treatment"],
    $data["chemical_treatment"],
    $data["prevention"],
    $data["image_path"]
);

$stmt->execute();

echo json_encode(["ok"=>true, "id"=>$stmt->insert_id]);
?>
