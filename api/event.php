<?php
// event.php - shows full details for one event, with a Book Now button.
include "db.php";

$id = (int) ($_GET['id'] ?? 0); // (int) forces the input to a number, blocking SQL injection tricks

$stmt = mysqli_prepare($conn, "SELECT * FROM events WHERE event_id = ? AND status = 'approved'");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$event = mysqli_fetch_assoc($result);

include "header.php";

if (!$event) {
    echo "<p>Event not found.</p>";
    include "footer.php";
    exit;
}
?>

<div class="detail-box">
    <span class="tag"><?php echo htmlspecialchars($event['category']); ?></span>
    <h2><?php echo htmlspecialchars($event['title']); ?></h2>
    <p><?php echo nl2br(htmlspecialchars($event['description'])); ?></p>

    <table class="detail-table">
        <tr><td>Venue</td><td><?php echo htmlspecialchars($event['venue']); ?>, <?php echo htmlspecialchars($event['city']); ?></td></tr>
        <tr><td>Date</td><td><?php echo $event['event_date']; ?></td></tr>
        <tr><td>Time</td><td><?php echo $event['event_time']; ?></td></tr>
        <tr><td>Price</td><td><?php echo ($event['price'] > 0) ? "Rs. " . $event['price'] . " per ticket" : "Free"; ?></td></tr>
        <tr><td>Seats left</td><td><?php echo $event['seats_left']; ?> of <?php echo $event['total_seats']; ?></td></tr>
    </table>

    <?php if (($_SESSION['role'] ?? '') === 'attendee') { ?>
        <?php if ($event['seats_left'] > 0) { ?>
            <a class="btn" href="book.php?id=<?php echo $event['event_id']; ?>">Book Now</a>
        <?php } else { ?>
            <p class="error">Sold out.</p>
        <?php } ?>
    <?php } elseif (!($_SESSION['role'] ?? false)) { ?>
        <p><a href="login.php">Login</a> as an attendee to book this event.</p>
    <?php } else { ?>
        <p>Only attendees can book tickets.</p>
    <?php } ?>
</div>

<?php include "footer.php"; ?>
