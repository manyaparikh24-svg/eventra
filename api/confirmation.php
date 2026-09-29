<?php

include "db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'attendee') {
    header("Location: login.php");
    exit;
}

$ticket_code = $_GET['code'] ?? '';

if (empty($ticket_code)) {
    echo "Invalid ticket.";
    exit;
}

/* Get booking details */
$stmt = mysqli_prepare(
    $conn,
    "SELECT 
        b.booking_id,
        b.ticket_code,
        b.num_tickets,
        b.total_amount,
        b.payment_method,
        b.booked_at,
        e.title,
        e.description,
        e.venue,
        e.city,
        e.event_date,
        e.event_time
     FROM bookings b
     JOIN events e ON b.event_id = e.event_id
     WHERE b.ticket_code = ?
     AND b.user_id = ?"
);

mysqli_stmt_bind_param(
    $stmt,
    "si",
    $ticket_code,
    $_SESSION['user_id']
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$booking = mysqli_fetch_assoc($result);

include "header.php";
?>

<div class="detail-box">

<?php if (!$booking): ?>

    <h2>Booking Not Found</h2>

    <p>
        We could not find this booking.
    </p>

    <a href="index.php">Back to Events</a>

<?php else: ?>

    <h2>🎉 Booking Confirmed!</h2>

    <p>
        Your event booking was successful.
    </p>

    <hr>

    <h3>
        <?php echo htmlspecialchars($booking['title']); ?>
    </h3>

    <p>
        <strong>Ticket Code:</strong>
        <?php echo htmlspecialchars($booking['ticket_code']); ?>
    </p>

    <p>
        <strong>Venue:</strong>
        <?php echo htmlspecialchars($booking['venue']); ?>
    </p>

    <p>
        <strong>City:</strong>
        <?php echo htmlspecialchars($booking['city']); ?>
    </p>

    <p>
        <strong>Date:</strong>
        <?php echo htmlspecialchars($booking['event_date']); ?>
    </p>

    <p>
        <strong>Time:</strong>
        <?php echo htmlspecialchars($booking['event_time']); ?>
    </p>

    <p>
        <strong>Tickets:</strong>
        <?php echo htmlspecialchars($booking['num_tickets']); ?>
    </p>

    <p>
        <strong>Total Amount:</strong>
        Rs. <?php echo number_format($booking['total_amount'], 2); ?>
    </p>

    <p>
        <strong>Payment Method:</strong>
        <?php echo htmlspecialchars($booking['payment_method']); ?>
    </p>

    <p>
        <strong>Booked At:</strong>
        <?php echo htmlspecialchars($booking['booked_at']); ?>
    </p>

    <br>

    <a href="my-tickets.php">
        View My Tickets
    </a>

<?php endif; ?>

</div>

<?php include "footer.php"; ?>