```php
<?php

require_once "db.php";

/* Razorpay credentials from Vercel Environment Variables */
$razorpayKeyId = getenv('RAZORPAY_KEY_ID');
$razorpayKeySecret = getenv('RAZORPAY_KEY_SECRET');

header("Content-Type: application/json");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'attendee') {
    echo json_encode([
        "success" => false,
        "message" => "Please login as attendee."
    ]);
    exit;
}

$event_id = isset($_POST['event_id']) ? (int)$_POST['event_id'] : 0;
$num_tickets = isset($_POST['num_tickets']) ? (int)$_POST['num_tickets'] : 0;

if ($event_id <= 0 || $num_tickets <= 0) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid event or ticket quantity."
    ]);
    exit;
}

/* Check Razorpay credentials */
if (!$razorpayKeyId || !$razorpayKeySecret) {
    echo json_encode([
        "success" => false,
        "message" => "Razorpay credentials are not configured."
    ]);
    exit;
}

/* Get event details */
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

/* Check seats */
if ($num_tickets > $event['seats_left']) {
    echo json_encode([
        "success" => false,
        "message" => "Not enough seats available."
    ]);
    exit;
}

/* Calculate amount from database */
$total_amount = $event['price'] * $num_tickets;

/* Razorpay uses paise */
$amount_paise = (int) round($total_amount * 100);

/* Unique receipt */
$receipt = "EVENTRA_" . time() . "_" . $event_id;

/* Razorpay Order API data */
$data = [
    "amount" => $amount_paise,
    "currency" => "INR",
    "receipt" => $receipt,
    "notes" => [
        "event_id" => $event_id,
        "user_id" => $_SESSION['user_id'],
        "num_tickets" => $num_tickets
    ]
];

/* Create Razorpay order */
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

if (curl_errno($ch)) {
    echo json_encode([
        "success" => false,
        "message" => "Razorpay connection error."
    ]);

    curl_close($ch);
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
        "razorpay_error" => $order['error']['description'] ?? "Unknown error"
    ]);
}

?>
```
