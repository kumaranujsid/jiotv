<?php
// Shared settings and helpers for Akindi (local PHP edition).
define('API_URL', 'https://jiostbapiplugx.lovable.app/api/jiostb');
define('CACHE_TTL', 3 * 60 * 60);              // 3 hours
define('CACHE_DIR', __DIR__ . '/cache');
define('CACHE_FILE', CACHE_DIR . '/jiostb.m3u');
define('FETCH_TIMEOUT', 30);
define('PLAYER_UA', 'plaYtv/7.1.3 (Linux;Android 13) ExoPlayerLib/2.11.7');

function send_cors(): void {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, HEAD, OPTIONS');
    header('Access-Control-Allow-Headers: *');
}

function http_get(string $url, array $headers = []): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => FETCH_TIMEOUT,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $body = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$code, $body === false ? '' : $body];
}

function cache_age(): ?int {
    return is_file(CACHE_FILE) ? time() - filemtime(CACHE_FILE) : null;
}

// Returns playlist text, refreshing when stale or forced. Keeps old copy if upstream fails.
function get_playlist(bool $force = false): ?string {
    if (!is_dir(CACHE_DIR)) @mkdir(CACHE_DIR, 0755, true);
    $age = cache_age();
    if ($force || $age === null || $age >= CACHE_TTL) {
        $lock = fopen(CACHE_DIR . '/.lock', 'c');
        if ($lock && flock($lock, LOCK_EX)) {
            clearstatcache();
            $age = cache_age();
            if ($force || $age === null || $age >= CACHE_TTL) {
                [$code, $body] = http_get(API_URL, ['User-Agent: JioSTB-LocalProxy/1.0']);
                if ($code === 200 && strlen($body) > 20) {
                    $tmp = CACHE_FILE . '.tmp';
                    file_put_contents($tmp, $body);
                    rename($tmp, CACHE_FILE);
                } else {
                    error_log("[jiostb] upstream failed: HTTP $code");
                }
            }
            flock($lock, LOCK_UN);
        }
        if ($lock) fclose($lock);
    }
    return is_file(CACHE_FILE) ? file_get_contents(CACHE_FILE) : null;
}

function cache_status(): array {
    clearstatcache();
    $age = cache_age();
    return [
        'exists' => $age !== null,
        'size' => $age !== null ? filesize(CACHE_FILE) : 0,
        'age_sec' => $age,
        'ttl_sec' => CACHE_TTL,
        'next_fetch' => $age === null ? 0 : max(0, CACHE_TTL - $age),
    ];
}

// Normalise and add cookie / User-Agent lines that NS Player and OTT Navigator read.
function normalize_playlist(string $body): string {
    $t = preg_replace('/^\xEF\xBB\xBF/', '', $body);
    $t = trim(preg_replace('/\r\n?/', "\n", $t));
    if (strpos($t, '#EXTM3U') !== 0) $t = "#EXTM3U\n" . $t;
    $out = [];
    foreach (explode("\n", $t) as $line) {
        if ($line !== '' && $line[0] !== '#' && strpos($line, '|') === false
            && preg_match('/__hdnea__=[^&|\s]+/', $line, $m)) {
            $tok = $m[0];
            $out[] = '#EXTVLCOPT:http-user-agent=' . PLAYER_UA;
            $out[] = '#EXTHTTP:' . json_encode(['cookie' => $tok, 'User-Agent' => PLAYER_UA], JSON_UNESCAPED_SLASHES);
            $out[] = $line . '|cookie=' . $tok . '&User-Agent=' . rawurlencode(PLAYER_UA);
        } else {
            $out[] = $line;
        }
    }
    return implode("\n", $out) . "\n";
}

function base_url(): string {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    $dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    return ($https ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $dir;
}

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
