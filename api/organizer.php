<?php
// organizer.php - organizer dashboard: add a new event, and manage
// (view attendees / cancel) the events they've already listed.
include "db.php";

if (($_SESSION['role'] ?? '') !== 'organizer') {
    header("Location: login.php");
    exit;
}
$organizerId = $_SESSION['user_id'];
$error = "";

// --- Handle "Add New Event" form submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_event'])) {
    $title       = trim($_POST['title']);
    $description = trim($_POST['description']);
    $category    = trim($_POST['category']);
    $venue       = trim($_POST['venue']);
    $city        = trim($_POST['city']);
    $event_date  = $_POST['event_date'];
    $event_time  = $_POST['event_time'];
    $price       = (float) $_POST['price'];
    $seats       = (int) $_POST['total_seats'];

    if ($title === '' || $seats < 1) {
        $error = "Please fill in all fields correctly.";
    } else {
        // New events start as 'pending' until an admin approves them
        $stmt = mysqli_prepare($conn, "
            INSERT INTO events (organizer_id, title, description, category, venue, city, event_date, event_time, price, total_seats, seats_left, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
        ");
        mysqli_stmt_bind_param($stmt, "isssssssdii", $organizerId, $title, $description, $category, $venue, $city, $event_date, $event_time, $price, $seats, $seats);
        mysqli_stmt_execute($stmt);
    }
}

// --- Handle "Cancel Event" action ---
if (isset($_GET['cancel'])) {
    $cancelId = (int) $_GET['cancel'];
    // the "AND organizer_id = ?" makes sure organizers can only cancel their OWN events
    $stmt = mysqli_prepare($conn, "UPDATE events SET status = 'cancelled' WHERE event_id = ? AND organizer_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $cancelId, $organizerId);
    mysqli_stmt_execute($stmt);
    header("Location: organizer.php");
    exit;
}

// Fetch this organizer's own events
$stmt = mysqli_prepare($conn, "SELECT * FROM events WHERE organizer_id = ? ORDER BY event_date DESC");
mysqli_stmt_bind_param($stmt, "i", $organizerId);
mysqli_stmt_execute($stmt);
$myEvents = mysqli_stmt_get_result($stmt);

include "header.php";
?>

<h2>Organizer Dashboard</h2>

<h3>Add New Event</h3>
<?php if ($error) { ?><p class="error"><?php echo htmlspecialchars($error); ?></p><?php } ?>

<form method="POST" class="form-box">
    <input type="hidden" name="add_event" value="1">
    <label>Title</label>
    <input type="text" name="title" required>

    <label>Description</label>
    <textarea name="description" rows="3"></textarea>

    <label>Category</label>
    <input type="text" name="category" placeholder="Music, Tech, Sports..." required>

    <label>Venue</label>
    <input type="text" name="venue" required>

    <label>City</label>
    <input type="text" name="city" required>

    <label>Date</label>
    <input type="date" name="event_date" required>

    <label>Time</label>
    <input type="time" name="event_time" required>

    <label>Price (Rs., enter 0 for free)</label>
    <input type="number" step="0.01" name="price" value="0" required>

    <label>Total Seats</label>
    <input type="number" name="total_seats" min="1" required>

    <button type="submit">Submit for Approval</button>
</form>

<h3>My Events</h3>
<table class="manage-table">
    <tr><th>Title</th><th>Date</th><th>Status</th><th>Seats Left</th><th>Action</th></tr>
    <?php while ($row = mysqli_fetch_assoc($myEvents)) { ?>
        <tr>
            <td><?php echo htmlspecialchars($row['title']); ?></td>
            <td><?php echo $row['event_date']; ?></td>
            <td><span class="status-<?php echo $row['status']; ?>"><?php echo ucfirst($row['status']); ?></span></td>
            <td><?php echo $row['seats_left']; ?> / <?php echo $row['total_seats']; ?></td>
            <td>
                <?php if ($row['status'] !== 'cancelled') { ?>
                    <a href="organizer.php?cancel=<?php echo $row['event_id']; ?>" onclick="return confirm('Cancel this event?')">Cancel</a>
                <?php } ?>
            </td>
        </tr>
    <?php } ?>
</table>

<?php include "footer.php"; ?>
