<?php

error_reporting(0);
ini_set('display_errors', 0);

require_once "db.php";

header("Content-Type: application/json");

$razorpayKeySecret = getenv("RAZORPAY_KEY_SECRET");

$userId = (int)($_POST['user_id'] ?? 0);
$eventId = (int)($_POST['event_id'] ?? 0);
$numTickets = (int)($_POST['num_tickets'] ?? 0);

$paymentId = $_POST['razorpay_payment_id'] ?? '';
$orderId = $_POST['razorpay_order_id'] ?? '';
$signature = $_POST['razorpay_signature'] ?? '';

if (
    $userId <= 0 ||
    $eventId <= 0 ||
    $numTickets <= 0 ||
    empty($paymentId) ||
    empty($orderId) ||
    empty($signature)
) {

    echo json_encode([
        "success" => false,
        "message" => "Invalid payment information."
    ]);

    exit;
}

if (!$razorpayKeySecret) {

    echo json_encode([
        "success" => false,
        "message" => "Razorpay secret is not configured."
    ]);

    exit;
}

/* Verify Razorpay signature */
$signatureData = $orderId . "|" . $paymentId;

$expectedSignature = hash_hmac(
    "sha256",
    $signatureData,
    $razorpayKeySecret
);

if (!hash_equals($expectedSignature, $signature)) {

    echo json_encode([
        "success" => false,
        "message" => "Payment verification failed."
    ]);

    exit;
}

/* Check user */
$userStmt = mysqli_prepare(
    $conn,
    "SELECT user_id, role
     FROM users
     WHERE user_id = ?"
);

mysqli_stmt_bind_param(
    $userStmt,
    "i",
    $userId
);

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
    "SELECT *
     FROM events
     WHERE event_id = ? AND status = 'approved'"
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $eventId
);

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

/* Calculate total */
$totalAmount =
    $event['price'] * $numTickets;

/* Generate ticket */
$ticketCode =
    "EVT" . strtoupper(bin2hex(random_bytes(5)));

/* Payment method */
$paymentMethod = "Razorpay";

/* Insert booking */
$stmt = mysqli_prepare(
    $conn,
    "INSERT INTO bookings
    (
        ticket_code,
        user_id,
        event_id,
        num_tickets,
        total_amount,
        payment_method
    )
    VALUES (?, ?, ?, ?, ?, ?)"
);

mysqli_stmt_bind_param(
    $stmt,
    "siiids",
    $ticketCode,
    $userId,
    $eventId,
    $numTickets,
    $totalAmount,
    $paymentMethod
);

if (!mysqli_stmt_execute($stmt)) {

    echo json_encode([
        "success" => false,
        "message" => "Could not create booking."
    ]);

    exit;
}

/* Reduce seats */
$stmt = mysqli_prepare(
    $conn,
    "UPDATE events
     SET seats_left = seats_left - ?
     WHERE event_id = ?"
);

mysqli_stmt_bind_param(
    $stmt,
    "ii",
    $numTickets,
    $eventId
);

mysqli_stmt_execute($stmt);

/* Success */
echo json_encode([
    "success" => true,
    "ticket_code" => $ticketCode
]);