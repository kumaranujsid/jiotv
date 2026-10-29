<?php
// Router for PHP's built-in server: php -S 0.0.0.0:8000 router.php
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($path === '/jiostb.m3u') { require __DIR__ . '/jiostb.php'; return true; }
if ($path === '/config.php' || strpos($path, '/cache/') === 0 || $path === '/router.php') {
    http_response_code(403); echo 'Forbidden'; return true;
}
return false;
