<?php
// index.php - Home page: shows all approved events.
include "db.php";

$sql = "SELECT * FROM events WHERE status = 'approved' ORDER BY event_date ASC";
$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Eventra - Discover Events</title>
</head>
<body>
    <h1>Eventra</h1>
    <h2>Upcoming Events</h2>

    <?php while ($row = mysqli_fetch_assoc($result)) { ?>
        <div>
            <h3><?php echo $row['title']; ?></h3>
            <p><?php echo $row['category']; ?> | <?php echo $row['city']; ?></p>
            <p>Date: <?php echo $row['event_date']; ?> at <?php echo $row['event_time']; ?></p>
            <p>Price: Rs. <?php echo $row['price']; ?> | Seats left: <?php echo $row['seats_left']; ?></p>
            <hr>
        </div>
    <?php } ?>
</body>
</html>