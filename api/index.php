<?php

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = basename($path);

if ($path === '' || $path === 'index.php') {
    include __DIR__ . '/home.php';
    exit;
}

$file = __DIR__ . '/' . $path;

if (is_file($file) && strtolower(pathinfo($file, PATHINFO_EXTENSION)) === 'php') {
    include $file;
    exit;
}

include __DIR__ . '/home.php';

?>