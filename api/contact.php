<?php
// contact.php - simple About + Contact page (static content).
include "db.php";
include "header.php";
?>

<h2>About Eventra</h2>
<p>
    Eventra is a web-based platform that lets event organizers list events and
    allows attendees to discover, search, and book tickets online in real time.
    It replaces manual registration and paper tickets with a simple digital
    system, tracking seat availability automatically and generating a unique
    ticket ID for every booking.
</p>

<h2>Contact Us</h2>
<form class="form-box" onsubmit="alert('Thanks! We will get back to you soon.'); return false;">
    <label>Name</label>
    <input type="text" required>

    <label>Email</label>
    <input type="email" required>

    <label>Message</label>
    <textarea rows="4" required></textarea>

    <button type="submit">Send Message</button>
</form>

<?php include "footer.php"; ?>
