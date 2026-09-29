<?php
// my-tickets.php - lists every ticket the logged-in attendee has booked.
include "db.php";

if (($_SESSION['role'] ?? '') !== 'attendee') {
    header("Location: login.php");
    exit;
}

$stmt = mysqli_prepare($conn, "
    SELECT b.*, e.title, e.venue, e.city, e.event_date, e.event_time
    FROM bookings b
    JOIN events e ON b.event_id = e.event_id
    WHERE b.user_id = ?
    ORDER BY b.booked_at DESC
");
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

include "header.php";
?>

<h2>My Tickets</h2>

<?php if (mysqli_num_rows($result) === 0) { ?>
    <p>You haven't booked any tickets yet. <a href="index.php">Browse events</a>.</p>
<?php } ?>

<div class="grid">
    <?php while ($row = mysqli_fetch_assoc($result)) { ?>
        <div class="card">
            <h3><?php echo htmlspecialchars($row['title']); ?></h3>
            <p><?php echo htmlspecialchars($row['venue']); ?>, <?php echo htmlspecialchars($row['city']); ?></p>
            <p><?php echo $row['event_date']; ?> at <?php echo $row['event_time']; ?></p>
            <p>Tickets: <?php echo $row['num_tickets']; ?></p>
            <p class="ticket-code">ID: <?php echo htmlspecialchars($row['ticket_code']); ?></p>
        </div>
    <?php } ?>
</div>

<?php include "footer.php"; ?>
