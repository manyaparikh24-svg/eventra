<?php
// db.php - connects PHP to MySQL.
// Locally (XAMPP) it uses root/no-password/localhost.
// On Vercel it reads DB_HOST / DB_PORT / DB_USER / DB_PASS / DB_NAME
// from Environment Variables (set in the Vercel dashboard) and connects
// over SSL, which TiDB Cloud requires.

session_start(); // must run before any HTML output - lets every page use $_SESSION

$envHost = getenv('DB_HOST');

if ($envHost) {
    // ---- ONLINE (TiDB Cloud) ----
    $host   = $envHost;
    $port   = (int) getenv('DB_PORT');
    $user   = getenv('DB_USER');
    $pass   = getenv('DB_PASS');
    $dbname = getenv('DB_NAME');

    $conn = mysqli_init();
    mysqli_ssl_set($conn, NULL, NULL, NULL, NULL, NULL); // SSL required by TiDB
    $ok = mysqli_real_connect($conn, $host, $user, $pass, $dbname, $port, NULL, MYSQLI_CLIENT_SSL);

    if (!$ok) {
        die("Connection failed: " . mysqli_connect_error());
    }
} else {
    // ---- LOCAL (XAMPP) ----
    $host   = "localhost";
    $user   = "root";
    $pass   = "";
    $dbname = "eventra_db";

    $conn = mysqli_connect($host, $user, $pass, $dbname);

    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }
}
?>
