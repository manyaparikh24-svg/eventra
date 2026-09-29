<?php
// index.php - Home page: browse + search/filter approved events.
include "db.php";

// Read filter values from the URL (?category=Music&city=Ahmedabad&date=2026-11-15)
// trim() removes stray spaces; if a filter is empty we just skip that condition.
$category = trim($_GET['category'] ?? '');
$city     = trim($_GET['city'] ?? '');
$date     = trim($_GET['date'] ?? '');

// Start with the base query - only approved events should ever be public
$sql = "SELECT * FROM events WHERE status = 'approved'";
$params = [];
$types = "";

if ($category !== '') {
    $sql .= " AND category = ?";
    $params[] = $category;
    $types .= "s";
}
if ($city !== '') {
    $sql .= " AND city = ?";
    $params[] = $city;
    $types .= "s";
}
if ($date !== '') {
    $sql .= " AND event_date = ?";
    $params[] = $date;
    $types .= "s";
}
$sql .= " ORDER BY event_date ASC";

// Prepared statement - keeps user input out of the raw SQL string (avoids SQL injection)
$stmt = mysqli_prepare($conn, $sql);
if ($params) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Get the distinct list of categories/cities for the filter dropdowns
$categories = mysqli_query($conn, "SELECT DISTINCT category FROM events WHERE status='approved'");
$cities     = mysqli_query($conn, "SELECT DISTINCT city FROM events WHERE status='approved'");

include "header.php";
?>

<h2>Upcoming Events</h2>

<form method="GET" class="filter-bar">
    <select name="category">
        <option value="">All Categories</option>
        <?php while ($c = mysqli_fetch_assoc($categories)) { ?>
            <option value="<?php echo htmlspecialchars($c['category']); ?>"
                <?php echo ($category === $c['category']) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($c['category']); ?>
            </option>
        <?php } ?>
    </select>

    <select name="city">
        <option value="">All Cities</option>
        <?php while ($ct = mysqli_fetch_assoc($cities)) { ?>
            <option value="<?php echo htmlspecialchars($ct['city']); ?>"
                <?php echo ($city === $ct['city']) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($ct['city']); ?>
            </option>
        <?php } ?>
    </select>

    <input type="date" name="date" value="<?php echo htmlspecialchars($date); ?>">
    <button type="submit">Search</button>
    <a href="index.php" class="clear-link">Clear</a>
</form>

<div class="grid">
    <?php if (mysqli_num_rows($result) === 0) { ?>
        <p>No events match your search.</p>
    <?php } ?>

    <?php while ($row = mysqli_fetch_assoc($result)) { ?>
        <a class="card" href="event.php?id=<?php echo $row['event_id']; ?>">
            <span class="tag"><?php echo htmlspecialchars($row['category']); ?></span>
            <h3><?php echo htmlspecialchars($row['title']); ?></h3>
            <p><?php echo htmlspecialchars($row['venue']); ?>, <?php echo htmlspecialchars($row['city']); ?></p>
            <p><?php echo $row['event_date']; ?> at <?php echo $row['event_time']; ?></p>
            <p class="price"><?php echo ($row['price'] > 0) ? "Rs. " . $row['price'] : "Free"; ?></p>
            <p class="seats">Seats left: <?php echo $row['seats_left']; ?></p>
        </a>
    <?php } ?>
</div>

<?php include "footer.php"; ?>
