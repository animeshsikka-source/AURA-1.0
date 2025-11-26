<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

// USER MESSAGE
$input = json_decode(file_get_contents("php://input"), true);
$userMsg = $input["message"] ?? "";

// ------------------------------------------------------
// 🔧 MOTOR CONTROL VIA CHAT COMMANDS
// ------------------------------------------------------
$ESP_IP = "172.21.125.29";

if (preg_match('/\bmotor on\b|\bstart motor\b|\bpump on\b|\birrigation on\b/i', $userMsg)) {

    // Check ESP online
    $espOnline = @fsockopen($ESP_IP, 80, $errno, $errstr, 0.5);
    if ($espOnline) {
        fclose($espOnline);
        @file_get_contents("http://$ESP_IP/motor/on");
        echo json_encode(["reply" => "⛲ Motor ON kar diya! Pump ab chalu hai 💧"]);
    } else {
        echo json_encode(["reply" => "⚠ ESP offline. Motor ON nahi kar sakta."]);
    }
    exit;
}

if (preg_match('/\bmotor off\b|\bstop motor\b|\bpump off\b|\birrigation off\b/i', $userMsg)) {

    // Check ESP online
    $espOnline = @fsockopen($ESP_IP, 80, $errno, $errstr, 0.5);
    if ($espOnline) {
        fclose($espOnline);
        @file_get_contents("http://$ESP_IP/motor/off");
        echo json_encode(["reply" => "⛲ Motor OFF kar diya! Pump band ho gaya 📴"]);
    } else {
        echo json_encode(["reply" => "⚠ ESP offline. Motor OFF nahi kar sakta."]);
    }
    exit;
}

// GROQ CONFIG
$API_KEY = "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";
$URL = "https://api.groq.com/openai/v1/chat/completions";

// ESP IP
$ESP_IP = "172.21.125.29";

// ------------------------------------------------------
// 1️⃣ CHECK ESP ONLINE
// ------------------------------------------------------
$espOnline = @fsockopen($ESP_IP, 80, $errno, $errstr, 0.5);

if ($espOnline) {
    fclose($espOnline);
    $isEspOnline = true;
} else {
    $isEspOnline = false;
}

// ------------------------------------------------------
// 2️⃣ READ LATEST SENSOR DATA FROM DB
// ------------------------------------------------------
$conn = new mysqli("localhost", "iotuser", "iotpass", "smart_agri");

$sensorText = "⚠ Sensor offline. Live data unavailable.";
$row = null;

if (!$conn->connect_error) {
    $sql = "SELECT * FROM sensor_data ORDER BY id DESC LIMIT 1";
    $result = $conn->query($sql);

    if ($result && $row = $result->fetch_assoc()) {

        // VALIDATE SENSOR VALUES
        if ($row['temperature'] == -999 || $row['humidity'] == -999) {
            $sensorText = "⚠ Sensor data invalid or corrupted.";
        } else if ($isEspOnline) {
            // ESP online + good data
            $sensorText = "
Temperature: {$row['temperature']} °C
Humidity: {$row['humidity']} %
Soil Moisture: {$row['moisture_pct']} %
Fire: {$row['fire_detected']}
Capacity: {$row['capacity_pct']} %
Motor: {$row['motor_on']}
";
        } else {
            // ESP offline, but DB has last data
            $sensorText = "⚠ ESP offline. Showing last saved sensor data:\n
Temperature: {$row['temperature']} °C
Humidity: {$row['humidity']} %
Soil Moisture: {$row['moisture_pct']} %
Fire: {$row['fire_detected']}
Capacity: {$row['capacity_pct']} %
Motor: {$row['motor_on']}
";
        }
    }
}
$conn->close();

// ------------------------------------------------------
// 3️⃣ AUTOMATION ENGINE (SAFE, ONLY IF ESP ONLINE)
// ------------------------------------------------------
$automation = [];

if ($row && $isEspOnline) {

    $moist = (int)$row['moisture_pct'];
    $fire  = (int)$row['fire_detected'];
    $cap   = (int)$row['capacity_pct'];
    $temp  = (int)$row['temperature'];
    $hum   = (int)$row['humidity'];

    /* FIRE ALERT */
    if ($fire == 1) {
        $automation[] = "🚨 FIRE DETECTED! Motor auto OFF.";
        @file_get_contents("http://$ESP_IP/motor/off");
    }

    /* AUTO IRRIGATION */
    if ($moist < 30) {
        $automation[] = "💧 Moisture low ($moist%). Auto-motor ON.";
        @file_get_contents("http://$ESP_IP/motor/on");
    }

    if ($moist > 70) {
        $automation[] = "💧 Moisture high ($moist%). Auto-motor OFF.";
        @file_get_contents("http://$ESP_IP/motor/off");
    }

    /* WAREHOUSE CAPACITY */
    if ($cap > 80) {
        $automation[] = "📦 Warehouse almost full ($cap%). Sell grains soon!";
    }

    /* CROP RECOMMENDATION */
    if ($temp > 30 && $hum < 60) {
        $automation[] = "🌾 Best crops now: Maize, Bajra, Moong.";
    }
    if ($temp >= 25 && $hum >= 60) {
        $automation[] = "🌧 Suitable crops: Rice, Tur, Soybean.";
    }
    if ($temp < 20) {
        $automation[] = "❄ Great crops: Wheat, Gram, Mustard.";
    }

} else if (!$isEspOnline) {
    $automation[] = "⚠ ESP offline, automation disabled.";
}

// If nothing triggered
if (empty($automation)) {
    $automationText = "No automation triggered right now.";
} else {
    $automationText = implode("\n", $automation);
}

// ------------------------------------------------------
// 4️⃣ SEND TO GROQ
// ------------------------------------------------------
$payload = [
    "model" => "llama-3.3-70b-versatile",
    "messages" => [
        [
            "role" => "system",
            "content" =>
"AURA Smart Farming AI 🤖🌾
Use Hinglish + emojis.

Automation Report:
$automationText

Live Sensor Status:
$sensorText
"
        ],
        [
            "role" => "user",
            "content" => $userMsg
        ]
    ]
];

// SEND REQUEST
$ch = curl_init($URL);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Authorization: Bearer $API_KEY"
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);

// RETURN MESSAGE
if (isset($data["choices"][0]["message"]["content"])) {
    echo json_encode(["reply" => $data["choices"][0]["message"]["content"]]);
} else {
    echo json_encode(["reply" => "⚠ Groq Error: " . $response]);
}
?>
