<?php

error_reporting(0);
ini_set('display_errors', 0);

require_once "db.php";

header("Content-Type: application/json");

$razorpayKeyId = getenv("RAZORPAY_KEY_ID");
$razorpayKeySecret = getenv("RAZORPAY_KEY_SECRET");

$userId = (int)($_POST['user_id'] ?? 0);
$eventId = (int)($_POST['event_id'] ?? 0);
$numTickets = (int)($_POST['num_tickets'] ?? 0);

if ($userId <= 0) {
    echo json_encode([
        "success" => false,
        "message" => "User information is missing."
    ]);
    exit;
}

if ($eventId <= 0 || $numTickets <= 0) {
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

/* Check that user exists and is an attendee */
$userStmt = mysqli_prepare(
    $conn,
    "SELECT user_id, role
     FROM users
     WHERE user_id = ?"
);

mysqli_stmt_bind_param($userStmt, "i", $userId);
mysqli_stmt_execute($userStmt);

$user = mysqli_fetch_assoc(
    mysqli_stmt_get_result($userStmt)
);

if (!$user || $user['role'] !== 'attendee') {
    echo json_encode([
        "success" => false,
        "message" => "Invalid attendee."
    ]);
    exit;
}

/* Get event */
$stmt = mysqli_prepare(
    $conn,
    "SELECT event_id, title, price, seats_left
     FROM events
     WHERE event_id = ? AND status = 'approved'"
);

mysqli_stmt_bind_param($stmt, "i", $eventId);
mysqli_stmt_execute($stmt);

$event = mysqli_fetch_assoc(
    mysqli_stmt_get_result($stmt)
);

if (!$event) {
    echo json_encode([
        "success" => false,
        "message" => "Event not found."
    ]);
    exit;
}

/* Check seats */
if ($numTickets > $event['seats_left']) {
    echo json_encode([
        "success" => false,
        "message" => "Not enough seats available."
    ]);
    exit;
}

/* Calculate amount */
$totalAmount = $event['price'] * $numTickets;
$amountPaise = (int)round($totalAmount * 100);

/* Unique receipt */
$receipt = "EVENTRA_" . time() . "_" . $eventId;

/* Razorpay order data */
$data = [
    "amount" => $amountPaise,
    "currency" => "INR",
    "receipt" => $receipt
];

/* Call Razorpay */
$ch = curl_init(
    "https://api.razorpay.com/v1/orders"
);

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);

curl_setopt(
    $ch,
    CURLOPT_USERPWD,
    $razorpayKeyId . ":" . $razorpayKeySecret
);

curl_setopt(
    $ch,
    CURLOPT_HTTPHEADER,
    [
        "Content-Type: application/json"
    ]
);

curl_setopt(
    $ch,
    CURLOPT_POSTFIELDS,
    json_encode($data)
);

$response = curl_exec($ch);

if ($response === false) {

    $error = curl_error($ch);

    curl_close($ch);

    echo json_encode([
        "success" => false,
        "message" => "Razorpay connection error: " . $error
    ]);

    exit;
}

$httpCode = curl_getinfo(
    $ch,
    CURLINFO_HTTP_CODE
);

curl_close($ch);

$order = json_decode(
    $response,
    true
);

if (
    $httpCode >= 200 &&
    $httpCode < 300 &&
    isset($order['id'])
) {

    echo json_encode([
        "success" => true,
        "order_id" => $order['id'],
        "amount" => $amountPaise,
        "key_id" => $razorpayKeyId,
        "event_title" => $event['title']
    ]);

} else {

    echo json_encode([
        "success" => false,
        "message" => "Unable to create Razorpay order.",
        "razorpay_error" =>
            $order['error']['description']
            ?? "Razorpay returned an unknown error."
    ]);
}