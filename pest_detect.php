<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

$API_KEY = "r8_dbb0wv1k0vq2llkne8IwL36AYsOyJzT3ljMo1";   // <-- Replace with your key

/* ==========================================================
   0️⃣ Check if file was received
   ========================================================== */
if (!isset($_FILES['image'])) {
    echo json_encode(["reply" => "⚠ ERROR: No image file received by PHP."]);
    exit;
}

$tmp = $_FILES['image']['tmp_name'];
$size = $_FILES['image']['size'];
$error = $_FILES['image']['error'];

/* ==========================================================
   1️⃣ Debug checks (very important)
   ========================================================== */

if ($error !== UPLOAD_ERR_OK) {
    echo json_encode(["reply" => "⚠ PHP Upload ERROR CODE: $error"]);
    exit;
}

// Check if PHP created the tmp file
if (!file_exists($tmp)) {
    echo json_encode(["reply" => "⚠ ERROR: Temporary upload file not found."]);
    exit;
}

// Check if file has size
if ($size == 0 || filesize($tmp) == 0) {
    echo json_encode([
        "reply" => "⚠ ERROR: PHP received an EMPTY FILE (0 bytes). 
Possible causes:
1. upload_max_filesize too small
2. post_max_size too small
3. file_uploads = Off
4. /tmp not writable
5. Image too large
6. Browser failed to send file"
    ]);
    exit;
}

/* ==========================================================
   2️⃣ Read file bytes
   ========================================================== */
$imageBytes = file_get_contents($tmp);

/* ==========================================================
   3️⃣ Upload image to Replicate (fixed multipart)
   ========================================================== */

$boundary = uniqid();
$delimiter = "-------------" . $boundary;

$postData =
    "--" . $delimiter . "\r\n"
    . "Content-Disposition: form-data; name=\"file\"; filename=\"pest.jpg\"\r\n"
    . "Content-Type: image/jpeg\r\n\r\n"
    . $imageBytes . "\r\n"
    . "--" . $delimiter . "--\r\n";

$ch = curl_init("https://api.replicate.com/v1/files");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer $API_KEY",
        "Content-Type: multipart/form-data; boundary=" . $delimiter
    ],
    CURLOPT_POSTFIELDS => $postData
]);

$response = curl_exec($ch);
curl_close($ch);

$upload = json_decode($response, true);

/* ==========================================================
   4️⃣ Validate Replicate Upload
   ========================================================== */
if (!isset($upload["url"])) {
    echo json_encode([
        "reply" => "⚠ Replicate Upload Error: " . $response
    ]);
    exit;
}

$image_url = $upload["url"];

/* ==========================================================
   5️⃣ Call Vision Model (LLaVA 1.6)
   ========================================================== */

$payload = [
    "version" => "281cc10530edfa7018bd9cbb0d585085d748d4cd77e5fa01c5ac923c67654a96",
    "input" => [
        "image"  => $image_url,
        "prompt" => "Identify crop disease or pest and give treatment in Hinglish with emojis."
    ]
];

$ch2 = curl_init("https://api.replicate.com/v1/predictions");
curl_setopt_array($ch2, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer $API_KEY",
        "Content-Type: application/json"
    ],
    CURLOPT_POSTFIELDS => json_encode($payload)
]);

$result = curl_exec($ch2);
curl_close($ch2);

$data = json_decode($result, true);

if (!isset($data["id"])) {
    echo json_encode(["reply" => "⚠ Model start error: " . $result]);
    exit;
}

$prediction_id = $data["id"];

/* ==========================================================
   6️⃣ Poll model result (max 10 sec)
   ========================================================== */

for ($i = 0; $i < 10; $i++) {

    $ch3 = curl_init("https://api.replicate.com/v1/predictions/$prediction_id");
    curl_setopt_array($ch3, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer $API_KEY"
        ]
    ]);

    $status = curl_exec($ch3);
    curl_close($ch3);

    $json = json_decode($status, true);

    if ($json["status"] === "succeeded") {
        $output = $json["output"][0];
        echo json_encode(["reply" => $output]);
        exit;
    }

    sleep(1);
}

echo json_encode(["reply" => "⚠ Model timeout. Try again."]);
?>
