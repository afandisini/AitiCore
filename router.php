<?php

declare(strict_types=1);

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
if (!is_string($path) || $path === '') {
    $path = '/';
}

$publicPath = __DIR__ . '/public' . $path;
if (is_file($publicPath)) {
    return false;
}

require __DIR__ . '/public/index.php';
