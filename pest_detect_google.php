<?php
header("Content-Type: application/json");

// GOOGLE VISION API KEY
$API_KEY = "AIzaSyDxSwDmdITCLU8aky_XRi3nxZuBCrbUdiU";

if (!isset($_FILES["image"])) {
    echo json_encode(["ok" => false, "error" => "No image uploaded"]);
    exit;
}

$imageData = base64_encode(file_get_contents($_FILES["image"]["tmp_name"]));

$url = "https://vision.googleapis.com/v1/images:annotate?key=" . $API_KEY;

$payload = json_encode([
    "requests" => [[
        "image" => [ "content" => $imageData ],
        "features" => [
            ["type" => "LABEL_DETECTION", "maxResults" => 5],
            ["type" => "WEB_DETECTION", "maxResults" => 5]
        ]
    ]]
]);

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

$result = curl_exec($ch);

if (curl_errno($ch)) {
    echo json_encode([
        "ok" => false,
        "curl_error" => curl_error($ch)
    ]);
    curl_close($ch);
    exit;
}

curl_close($ch);

// Decode Google reply
$json = json_decode($result, true);

// SHOW GOOGLE ERROR CLEARLY
if (isset($json["error"])) {
    echo json_encode([
        "ok" => false,
        "google_error" => $json["error"]
    ]);
    exit;
}

if (!isset($json["responses"][0]["labelAnnotations"])) {
    echo json_encode([
        "ok" => false,
        "error" => "No labels returned from Google",
        "raw" => $json
    ]);
    exit;
}

$labels = $json["responses"][0]["labelAnnotations"];
$top = $labels[0];

$name = $top["description"];
$confidence = round($top["score"] * 100);

echo json_encode([
  "ok" => true,
  "reply" => [
    "name" => $name,
    "confidence_pct" => $confidence,
    "symptoms" => "Detected using Google Vision.",
    "organic_treatment" => "Use neem oil spray.",
    "chemical_treatment" => "Use recommended pesticide lightly.",
    "prevention" => "Monitor field every 2–3 days."
  ]
]);
?>
