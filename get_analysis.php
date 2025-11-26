<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

$conn = new mysqli("localhost", "iotuser", "iotpass", "smart_agri");
if ($conn->connect_error) {
    die(json_encode(["error" => $conn->connect_error]));
}

$data = [];

// Avg readings (last 24 hrs)
$q1 = "SELECT 
          ROUND(AVG(temperature),2) as avg_temp,
          ROUND(AVG(humidity),2) as avg_hum,
          ROUND(AVG(moisture_pct),2) as avg_moisture,
          MAX(temperature) as max_temp,
          MIN(temperature) as min_temp
        FROM sensor_data WHERE timestamp > NOW() - INTERVAL 1 DAY";
$r1 = $conn->query($q1);
$data['sensors'] = $r1->fetch_assoc();

// Motor ON/OFF counts
$q2 = "SELECT 
          SUM(action='ON') AS on_count,
          SUM(action='OFF') AS off_count
        FROM motor_actions WHERE action_time > NOW() - INTERVAL 1 DAY";
$r2 = $conn->query($q2);
$data['motor'] = $r2->fetch_assoc();

// Warehouse fill
$q3 = "SELECT capacity_pct, (capacity_pct/100)*1000 AS est_weight_kg
       FROM sensor_data ORDER BY id DESC LIMIT 1";
$r3 = $conn->query($q3);
$data['warehouse'] = $r3->fetch_assoc();

// Revenue Estimation
$price_per_kg = 28;
$data['revenue'] = [
  "price_per_kg" => $price_per_kg,
  "est_revenue" => isset($data['warehouse']['est_weight_kg'])
      ? round($data['warehouse']['est_weight_kg'] * $price_per_kg, 2)
      : 0
];

$conn->close();
echo json_encode($data);
?>
