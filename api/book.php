<?php
// book.php - attendee picks how many tickets. Doesn't book yet -
// it hands off to payment.php, which does the actual booking once
// "payment" is confirmed.
include "db.php";

if (($_SESSION['role'] ?? '') !== 'attendee') {
    header("Location: login.php");
    exit;
}

$id = (int) ($_GET['id'] ?? 0);

$stmt = mysqli_prepare($conn, "SELECT * FROM events WHERE event_id = ? AND status = 'approved'");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$event = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

include "header.php";

if (!$event) {
    echo "<p>Event not found.</p>";
    include "footer.php";
    exit;
}

$error = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $qty = (int) $_POST['num_tickets'];
    if ($qty < 1) {
        $error = "Enter at least 1 ticket.";
    } elseif ($qty > $event['seats_left']) {
        $error = "Only " . $event['seats_left'] . " seats left.";
    }
    // if no error, we'll just fall through and show the form again with
    // the value carried into the payment step via the form below
}
?>

<div class="detail-box">
    <h2>Book: <?php echo htmlspecialchars($event['title']); ?></h2>
    <p><?php echo $event['event_date']; ?> at <?php echo $event['event_time']; ?> - <?php echo htmlspecialchars($event['venue']); ?>, <?php echo htmlspecialchars($event['city']); ?></p>
    <p>Price per ticket: <?php echo ($event['price'] > 0) ? "Rs. " . $event['price'] : "Free"; ?></p>
    <p>Seats left: <?php echo $event['seats_left']; ?></p>

    <?php if ($error) { ?>
        <p class="error"><?php echo htmlspecialchars($error); ?></p>
    <?php } ?>

    <form method="POST" action="payment.php" class="form-box">
        <input type="hidden" name="event_id" value="<?php echo $event['event_id']; ?>">
        <label>Number of tickets</label>
        <input type="number" name="num_tickets" min="1" max="<?php echo $event['seats_left']; ?>" value="1" required>
        <button type="submit">Proceed to Payment</button>
    </form>
</div>

<?php include "footer.php"; ?>
