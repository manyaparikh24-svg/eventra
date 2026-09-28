<?php
// db.php - connects PHP to the MySQL database.
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "eventra_db";

$conn = mysqli_connect($host, $user, $pass, $dbname);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>
