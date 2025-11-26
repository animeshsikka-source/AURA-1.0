<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

/* ==========================================================
   🌾 AURA Smart Agriculture
   Unified Data Endpoint — get_latest.php
   Combines:
   - ESP sensor data
   - Motor status
   - Warehouse capacity
   - Revenue estimate
   - Weather (via WeatherAPI)
   ========================================================== */

// ---------------- CONFIG ----------------
$ESP_IP = "http://172.21.125.29/sensors";  // Change if your ESP IP differs
$WEATHER_API_KEY = "1009a9b3a995454c80073905250911";
$WEATHER_LOCATION = "Ranchi";  // fallback city
$DB_HOST = "localhost";
$DB_USER = "iotuser";
$DB_PASS = "iotpass";
$DB_NAME = "smart_agri";

// ---------------- INIT ----------------
$data = [
  "esp" => [],
  "weather" => [],
  "motor" => [],
  "revenue" => []
];

// ---------------- ESP SENSOR DATA ----------------
$esp_response = @file_get_contents($ESP_IP);
if ($esp_response !== FALSE) {
    $data["esp"] = json_decode($esp_response, true);
} else {
    $data["esp"]["error"] = "ESP unreachable";
}

// ---------------- DATABASE CONNECTION ----------------
$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($conn->connect_error) {
    $data["db_error"] = $conn->connect_error;
} else {
    // Get latest sensor entry
    $sql = "SELECT * FROM sensor_data ORDER BY id DESC LIMIT 1";
    $result = $conn->query($sql);
    if ($result && $row = $result->fetch_assoc()) {
        $data["latest_db"] = $row;
    }

    // Get latest motor state
    $sql2 = "SELECT action FROM motor_actions ORDER BY id DESC LIMIT 1";
    $r2 = $conn->query($sql2);
    if ($r2 && $row2 = $r2->fetch_assoc()) {
        $data["motor"]["last_action"] = $row2["action"];
    }

    // Calculate revenue
    $cap = $row["capacity_pct"] ?? 0;
    $data["revenue"] = [
        "capacity_pct" => $cap,
        "est_weight_kg" => round(($cap / 100) * 1000, 2),
        "price_per_kg" => 28,
        "estimated_revenue" => round(($cap / 100) * 1000 * 28, 2)
    ];
}

// ---------------- WEATHER DATA ----------------
$weather_url = "https://api.weatherapi.com/v1/current.json?key={$WEATHER_API_KEY}&q={$WEATHER_LOCATION}&aqi=no";
$weather_json = @file_get_contents($weather_url);
if ($weather_json !== FALSE) {
    $weather = json_decode($weather_json, true);
    if ($weather && isset($weather["current"])) {
        $data["weather"] = [
            "location" => $weather["location"]["name"] . ", " . $weather["location"]["region"],
            "temperature" => $weather["current"]["temp_c"],
            "humidity" => $weather["current"]["humidity"],
            "condition" => $weather["current"]["condition"]["text"],
            "wind_kph" => $weather["current"]["wind_kph"]
        ];
    }
} else {
    $data["weather"]["error"] = "Weather unavailable";
}

// ---------------- OUTPUT ----------------
echo json_encode($data, JSON_PRETTY_PRINT);

$conn->close();
?>