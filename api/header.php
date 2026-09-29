<?php
// header.php - top of every page: HTML head + navigation bar.
// Assumes db.php (and therefore session_start()) already ran.
$role = $_SESSION['role'] ?? null;
$name = $_SESSION['name'] ?? null;
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Eventra</title>
    <link rel="stylesheet" href="/public/style.css">
</head>
<body>
<nav class="navbar">
    <a class="logo" href="index.php">Eventra</a>
    <div class="links">
        <a href="index.php">Events</a>
        <a href="contact.php">Contact</a>

        <?php if (!$role) { ?>
            <a href="login.php">Login</a>
            <a href="register.php">Register</a>
        <?php } ?>

        <?php if ($role === 'attendee') { ?>
            <a href="my-tickets.php">My Tickets</a>
        <?php } ?>

        <?php if ($role === 'organizer') { ?>
            <a href="organizer.php">Organizer Dashboard</a>
        <?php } ?>

        <?php if ($role === 'admin') { ?>
            <a href="admin.php">Admin Dashboard</a>
        <?php } ?>

        <?php if ($role) { ?>
            <span class="welcome">Hi, <?php echo htmlspecialchars($name); ?></span>
            <a href="logout.php">Logout</a>
        <?php } ?>
    </div>
</nav>
<div class="container">
