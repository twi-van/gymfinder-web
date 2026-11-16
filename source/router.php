<?php
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$file = __DIR__ . $uri;
if ($uri !== '/' && is_file($file)) {
    return false;
}
if (str_starts_with($uri, '/api')) {
    require __DIR__ . '/api/index.php';
    return true;
}
if ($uri === '/' || $uri === '/index.php') {
    require __DIR__ . '/index.php';
    return true;
}
http_response_code(404);
echo 'Not Found';
