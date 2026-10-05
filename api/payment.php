<?php

include "db.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* Only attendees can book */
if (($_SESSION['role'] ?? '') !== 'attendee') {
    header("Location: login.php");
    exit;
}

$userId = (int)($_SESSION['user_id'] ?? 0);

/*
 * STEP 1:
 * If the user clicked the Pay button,
 * create the booking.
 */
if (isset($_POST['confirm_payment'])) {

    $eventId = (int)($_POST['event_id'] ?? 0);
    $qty = (int)($_POST['num_tickets'] ?? 0);

    if ($userId <= 0 || $eventId <= 0 || $qty <= 0) {
        die("Invalid booking details.");
    }

    /* Get event */
    $stmt = mysqli_prepare(
        $conn,
        "SELECT *
         FROM events
         WHERE event_id = ? AND status = 'approved'"
    );

    mysqli_stmt_bind_param($stmt, "i", $eventId);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $event = mysqli_fetch_assoc($result);

    if (!$event) {
        die("Event not found.");
    }

    /* Check available seats */
    if ($qty > $event['seats_left']) {
        die("Not enough seats available.");
    }

    /* Calculate total */
    $total = $qty * $event['price'];

    /* Generate ticket code */
    $ticketCode = "EVT" . strtoupper(bin2hex(random_bytes(5)));

    /* Demo payment */
    $paymentMethod = "Demo Payment";

    /* Insert booking */
    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO bookings
        (ticket_code, user_id, event_id, num_tickets, total_amount, payment_method)
        VALUES (?, ?, ?, ?, ?, ?)"
    );

    mysqli_stmt_bind_param(
        $stmt,
        "siiids",
        $ticketCode,
        $userId,
        $eventId,
        $qty,
        $total,
        $paymentMethod
    );

    if (!mysqli_stmt_execute($stmt)) {
        die("Booking failed: " . mysqli_error($conn));
    }

    /* Reduce available seats */
    $stmt = mysqli_prepare(
        $conn,
        "UPDATE events
         SET seats_left = seats_left - ?
         WHERE event_id = ?"
    );

    mysqli_stmt_bind_param(
        $stmt,
        "ii",
        $qty,
        $eventId
    );

    mysqli_stmt_execute($stmt);

    /* Go to confirmation page */
    header(
        "Location: confirmation.php?code=" .
        urlencode($ticketCode)
    );
    exit;
}


/*
 * STEP 2:
 * Normal checkout page.
 */

/* Get event and quantity */
$eventId = (int)($_POST['event_id'] ?? 0);
$qty = (int)($_POST['num_tickets'] ?? 0);

if ($eventId <= 0 || $qty <= 0) {
    header("Location: index.php");
    exit;
}

/* Get event */
$stmt = mysqli_prepare(
    $conn,
    "SELECT *
     FROM events
     WHERE event_id = ? AND status = 'approved'"
);

mysqli_stmt_bind_param($stmt, "i", $eventId);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$event = mysqli_fetch_assoc($result);

if (!$event) {
    include "header.php";
    echo "<p class='error'>Event not found.</p>";
    include "footer.php";
    exit;
}

/* Check seats */
if ($qty > $event['seats_left']) {
    include "header.php";
    echo "<p class='error'>Not enough seats available.</p>";
    include "footer.php";
    exit;
}

/* Calculate total */
$total = $qty * $event['price'];

include "header.php";
?>

<div class="detail-box">

    <h2>Checkout</h2>

    <p>
        <strong>
            <?php echo htmlspecialchars($event['title']); ?>
        </strong>
        &mdash;
        <?php echo $qty; ?> ticket(s)
    </p>

    <p class="price">
        Total: Rs. <?php echo number_format($total, 2); ?>
    </p>

    <form method="POST" action="payment.php">

        <input
            type="hidden"
            name="event_id"
            value="<?php echo $eventId; ?>"
        >

        <input
            type="hidden"
            name="num_tickets"
            value="<?php echo $qty; ?>"
        >

        <input
            type="hidden"
            name="confirm_payment"
            value="1"
        >

        <button type="submit" class="btn">
            Pay Rs. <?php echo number_format($total, 2); ?>
        </button>

    </form>

    <p style="margin-top:15px; color:#777;">
        Demo payment mode
    </p>

</div>

<?php include "footer.php"; ?>