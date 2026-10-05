<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once "db.php";

header("Content-Type: application/json");

$razorpayKeyId = getenv("RAZORPAY_KEY_ID");
$razorpayKeySecret = getenv("RAZORPAY_KEY_SECRET");

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'attendee') {
    echo json_encode([
        "success" => false,
        "message" => "Please login as attendee."
    ]);
    exit;
}

$event_id = (int)($_POST['event_id'] ?? 0);
$num_tickets = (int)($_POST['num_tickets'] ?? 0);

if ($event_id <= 0 || $num_tickets <= 0) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid event or ticket quantity."
    ]);
    exit;
}

if (!$razorpayKeyId || !$razorpayKeySecret) {
    echo json_encode([
        "success" => false,
        "message" => "Razorpay credentials are not configured."
    ]);
    exit;
}

$stmt = mysqli_prepare(
    $conn,
    "SELECT event_id, title, price, seats_left
     FROM events
     WHERE event_id = ? AND status = 'approved'"
);

mysqli_stmt_bind_param($stmt, "i", $event_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$event = mysqli_fetch_assoc($result);

if (!$event) {
    echo json_encode([
        "success" => false,
        "message" => "Event not found."
    ]);
    exit;
}

if ($num_tickets > $event['seats_left']) {
    echo json_encode([
        "success" => false,
        "message" => "Not enough seats available."
    ]);
    exit;
}

$total_amount = $event['price'] * $num_tickets;
$amount_paise = (int)round($total_amount * 100);

$receipt = "EVENTRA_" . time() . "_" . $event_id;

$data = [
    "amount" => $amount_paise,
    "currency" => "INR",
    "receipt" => $receipt
];

$ch = curl_init("https://api.razorpay.com/v1/orders");

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt(
    $ch,
    CURLOPT_USERPWD,
    $razorpayKeyId . ":" . $razorpayKeySecret
);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json"
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if ($response === false) {
    $error = curl_error($ch);
    curl_close($ch);

    echo json_encode([
        "success" => false,
        "message" => "Razorpay connection error: " . $error
    ]);
    exit;
}

curl_close($ch);

$order = json_decode($response, true);

if ($http_code >= 200 && $http_code < 300 && isset($order['id'])) {

    echo json_encode([
        "success" => true,
        "order_id" => $order['id'],
        "amount" => $amount_paise,
        "key_id" => $razorpayKeyId,
        "event_title" => $event['title']
    ]);

} else {

    echo json_encode([
        "success" => false,
        "message" => "Unable to create Razorpay order.",
        "razorpay_error" => $order['error']['description'] ?? $response
    ]);
}