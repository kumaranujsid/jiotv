<?php
// Playlist endpoint. ?status=1 -> JSON, ?refresh=1 -> force refresh.
// CLI: php jiostb.php        (refreshes the cache, e.g. from cron)
require __DIR__ . '/config.php';

if (PHP_SAPI === 'cli') {
    $body = get_playlist(true);
    echo $body ? "Refreshed: " . strlen($body) . " bytes\n" : "Refresh failed\n";
    exit($body ? 0 : 1);
}

send_cors();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method === 'OPTIONS') { http_response_code(204); exit; }

if (isset($_GET['status'])) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(cache_status(), JSON_PRETTY_PRINT);
    exit;
}

$body = get_playlist(($_GET['refresh'] ?? '') === '1');
if ($body === null) {
    http_response_code(503);
    header('Content-Type: audio/x-mpegurl; charset=utf-8');
    header('Retry-After: 300');
    echo "#EXTM3U\n";
    exit;
}

$out = normalize_playlist($body);
header('Content-Type: audio/x-mpegurl; charset=utf-8');
header('Content-Disposition: inline; filename="jiostb.m3u"');
header('Content-Length: ' . strlen($out));
header('Cache-Control: no-cache, must-revalidate');
header('X-Cache-Age: ' . (int) cache_age());
if ($method !== 'HEAD') echo $out;
