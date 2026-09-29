<?php
// logout.php - clears the session and sends the user back to the home page.
session_start();
session_unset();
session_destroy();
header("Location: index.php");
exit;
?>
