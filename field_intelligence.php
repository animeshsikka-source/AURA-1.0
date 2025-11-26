<?php
header('Content-Type: application/json');
date_default_timezone_set('Asia/Kolkata');

// --- Simulated field data ---
$crop = "Wheat";
$temperature = rand(15, 35);
$humidity = rand(45, 85);
$rainChance = rand(0, 100);

// --- Pest risk logic ---
$pestRisk = ($humidity > 70 || $rainChance > 60) ? rand(60, 95) : rand(5, 50);
$riskLevel = $pestRisk > 75 ? "High" : ($pestRisk > 45 ? "Moderate" : "Low");

// --- Irrigation logic ---
if ($rainChance > 70) {
  $irrigationStatus = "Paused — Rain expected 🌧️";
} elseif ($temperature > 34 && $humidity < 45) {
  $irrigationStatus = "Active — Dry soil detected 🔥";
} else {
  $irrigationStatus = "Safe (Normal) ✅";
}

// --- Alerts ---
$alerts = [];
if ($rainChance > 75) $alerts[] = "🌧️ Rain expected soon — irrigation paused.";
if ($temperature > 36) $alerts[] = "🔥 High temperature — increase irrigation.";
if ($humidity < 40) $alerts[] = "💨 Low humidity — soil may dry faster.";

// --- Format date and time for next checkup (1 hour later) ---
$nextCheckup = date("l, d M Y, h:i A", time() + 3600);

// --- Build response ---
$response = [
  "crop" => $crop,
  "weather" => [
    "temperature" => $temperature,
    "humidity" => $humidity,
    "rain_chance" => $rainChance
  ],
  "pest_risk" => $pestRisk,
  "risk_level" => $riskLevel,
  "irrigation_status" => $irrigationStatus,
  "next_checkup" => $nextCheckup,
  "alert" => count($alerts) ? implode(" | ", $alerts) : null
];

echo json_encode($response, JSON_PRETTY_PRINT);
?>