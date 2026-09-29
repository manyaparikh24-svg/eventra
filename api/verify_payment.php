<?php

require_once "db.php";
require_once "razorpay_config.php";

header("Content-Type: application/json");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'attendee') {
    echo json_encode([
        "success" => false,
        "message" => "Please login again."
    ]);
    exit;
}

$payment_id = $_POST['razorpay_payment_id'] ?? '';
$order_id   = $_POST['razorpay_order_id'] ?? '';
$signature  = $_POST['razorpay_signature'] ?? '';

$event_id   = (int) ($_POST['event_id'] ?? 0);
$num_tickets = (int) ($_POST['num_tickets'] ?? 0);

if (!$payment_id || !$order_id || !$signature) {
    echo json_encode([
        "success" => false,
        "message" => "Payment information is incomplete."
    ]);
    exit;
}

/* Verify Razorpay signature */
$generated_signature = hash_hmac(
    'sha256',
    $order_id . "|" . $payment_id,
    RAZORPAY_KEY_SECRET
);

if (!hash_equals($generated_signature, $signature)) {
    echo json_encode([
        "success" => false,
        "message" => "Payment verification failed."
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
if ($num_tickets <= 0 || $num_tickets > $event['seats_left']) {
    echo json_encode([
        "success" => false,
        "message" => "Not enough seats available."
    ]);
    exit;
}

$total_amount = $event['price'] * $num_tickets;

/* Generate ticket code */
$ticket_code = "EVT" . strtoupper(substr(md5(uniqid()), 0, 10));

/* Start transaction */
mysqli_begin_transaction($conn);

try {

    /* Reduce seats */
    $update = mysqli_prepare(
        $conn,
        "UPDATE events
         SET seats_left = seats_left - ?
         WHERE event_id = ?
         AND seats_left >= ?"
    );

    mysqli_stmt_bind_param(
        $update,
        "iii",
        $num_tickets,
        $event_id,
        $num_tickets
    );

    mysqli_stmt_execute($update);

    if (mysqli_stmt_affected_rows($update) !== 1) {
        throw new Exception("Seats are no longer available.");
    }


    /* Insert booking */
$booking = mysqli_prepare(
    $conn,
    "INSERT INTO bookings
    (
        ticket_code,
        user_id,
        event_id,
        num_tickets,
        total_amount,
        payment_method,
        razorpay_order_id,
        razorpay_payment_id,
        payment_status
    )
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
);

$payment_method = "Razorpay";
$payment_status = "paid";

mysqli_stmt_bind_param(
    $booking,
    "siiidssss",
    $ticket_code,
    $_SESSION['user_id'],
    $event_id,
    $num_tickets,
    $total_amount,
    $payment_method,
    $order_id,
    $payment_id,
    $payment_status
);

    mysqli_stmt_execute($booking);

    mysqli_commit($conn);

    echo json_encode([
        "success" => true,
        "ticket_code" => $ticket_code
    ]);

} catch (Exception $e) {

    mysqli_rollback($conn);

    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}

?>