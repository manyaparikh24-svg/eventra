<?php
// admin.php - admin dashboard: approve/reject pending events, view all users.
include "db.php";

if (($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: login.php");
    exit;
}

// --- Handle approve/reject actions ---
if (isset($_GET['approve'])) {
    $id = (int) $_GET['approve'];
    mysqli_query($conn, "UPDATE events SET status = 'approved' WHERE event_id = $id");
    header("Location: admin.php");
    exit;
}
if (isset($_GET['reject'])) {
    $id = (int) $_GET['reject'];
    mysqli_query($conn, "UPDATE events SET status = 'rejected' WHERE event_id = $id");
    header("Location: admin.php");
    exit;
}

// Events waiting for approval
$pending = mysqli_query($conn, "
    SELECT e.*, u.name AS organizer_name
    FROM events e JOIN users u ON e.organizer_id = u.user_id
    WHERE e.status = 'pending'
    ORDER BY e.created_at ASC
");

// All events (for an overview table)
$allEvents = mysqli_query($conn, "
    SELECT e.*, u.name AS organizer_name
    FROM events e JOIN users u ON e.organizer_id = u.user_id
    ORDER BY e.created_at DESC
");

// All users
$users = mysqli_query($conn, "SELECT * FROM users ORDER BY created_at DESC");

include "header.php";
?>

<h2>Admin Dashboard</h2>

<h3>Pending Approval</h3>
<?php if (mysqli_num_rows($pending) === 0) { ?>
    <p>Nothing waiting for approval.</p>
<?php } ?>
<table class="manage-table">
    <tr><th>Title</th><th>Organizer</th><th>Date</th><th>City</th><th>Action</th></tr>
    <?php while ($row = mysqli_fetch_assoc($pending)) { ?>
        <tr>
            <td><?php echo htmlspecialchars($row['title']); ?></td>
            <td><?php echo htmlspecialchars($row['organizer_name']); ?></td>
            <td><?php echo $row['event_date']; ?></td>
            <td><?php echo htmlspecialchars($row['city']); ?></td>
            <td>
                <a href="admin.php?approve=<?php echo $row['event_id']; ?>">Approve</a> |
                <a href="admin.php?reject=<?php echo $row['event_id']; ?>">Reject</a>
            </td>
        </tr>
    <?php } ?>
</table>

<h3>All Events</h3>
<table class="manage-table">
    <tr><th>Title</th><th>Organizer</th><th>Status</th></tr>
    <?php while ($row = mysqli_fetch_assoc($allEvents)) { ?>
        <tr>
            <td><?php echo htmlspecialchars($row['title']); ?></td>
            <td><?php echo htmlspecialchars($row['organizer_name']); ?></td>
            <td><span class="status-<?php echo $row['status']; ?>"><?php echo ucfirst($row['status']); ?></span></td>
        </tr>
    <?php } ?>
</table>

<h3>All Users</h3>
<table class="manage-table">
    <tr><th>Name</th><th>Email</th><th>Role</th></tr>
    <?php while ($row = mysqli_fetch_assoc($users)) { ?>
        <tr>
            <td><?php echo htmlspecialchars($row['name']); ?></td>
            <td><?php echo htmlspecialchars($row['email']); ?></td>
            <td><?php echo ucfirst($row['role']); ?></td>
        </tr>
    <?php } ?>
</table>

<?php include "footer.php"; ?>
