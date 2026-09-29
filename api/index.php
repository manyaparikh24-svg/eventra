<?php

$requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$page = basename($requestPath);

/* Homepage */
if ($page === '' || $page === 'index.php') {
    include __DIR__ . "/home.php";
    exit;
}

/* Allowed PHP pages */
$allowedPages = [
    'event.php',
    'login.php',
    'register.php',
    'logout.php',
    'contact.php',
    'book.php',
    'payment.php',
    'confirmation.php',
    'my-tickets.php',
    'organizer.php',
    'admin.php',
    'create_order.php',
    'verify_payment.php'
];

if (in_array($page, $allowedPages, true)) {
    include __DIR__ . "/" . $page;
    exit;
}

/* Page not found */
http_response_code(404);
echo "Page not found.";

?>