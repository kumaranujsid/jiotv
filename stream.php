<?php
// Video relay for the browser player. Only https jio.com hosts are allowed.
require __DIR__ . '/config.php';
send_cors();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method === 'OPTIONS') { http_response_code(204); exit; }

$target = $_GET['url'] ?? '';
$u = parse_url($target);
if (!$u || empty($u['scheme']) || empty($u['host'])) { http_response_code(400); exit('bad url'); }
$host = strtolower($u['host']);
if ($u['scheme'] !== 'https' || !($host === 'jio.com' || substr($host, -8) === '.jio.com')) {
    http_response_code(403); exit('host not allowed');
}

$headers = ['User-Agent: ' . PLAYER_UA];
if (!empty($_SERVER['HTTP_RANGE'])) $headers[] = 'Range: ' . $_SERVER['HTTP_RANGE'];
if (preg_match('/__hdnea__=[^&]+/', $u['query'] ?? '', $m)) $headers[] = 'Cookie: ' . $m[0];

$keep = ['content-type', 'content-length', 'content-range', 'accept-ranges'];
$ch = curl_init($target);
curl_setopt_array($ch, [
    CURLOPT_HTTPHEADER => $headers,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT => 60,
    CURLOPT_NOBODY => $method === 'HEAD',
    CURLOPT_HEADERFUNCTION => function ($ch, $line) use ($keep) {
        if (preg_match('#^HTTP/\S+\s+(\d+)#', $line, $m)) http_response_code((int) $m[1]);
        $p = explode(':', $line, 2);
        if (count($p) === 2 && in_array(strtolower(trim($p[0])), $keep, true)) header(trim($line));
        return strlen($line);
    },
    CURLOPT_WRITEFUNCTION => function ($ch, $data) { echo $data; flush(); return strlen($data); },
]);
header('Cache-Control: no-store');
curl_exec($ch);
if (curl_errno($ch)) { http_response_code(502); }
curl_close($ch);
