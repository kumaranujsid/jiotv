# Akindi — local PHP edition

JioTV playlist proxy + browser player for your own PC or local server.

## Requirements
- PHP 7.4+ with the **curl** extension
- Apache (XAMPP / WAMP / Laragon) or PHP's built-in server

## Run it
**Quick test (built-in server):**
```
cd akindi-php
php -S 0.0.0.0:8000 router.php
```
Open http://localhost:8000 . On other devices use your PC's IP, e.g. `http://192.168.1.10:8000/jiostb.m3u`.

**Apache / XAMPP:** copy the folder into `htdocs/akindi` and open http://localhost/akindi/ .
Make sure the `cache` folder is writable.

## Files
| File | Purpose |
| --- | --- |
| `index.php` | Front page: playlist link, copy button, QR code, status, setup guides |
| `jiostb.php` | Playlist (`/jiostb.m3u` also works) |
| `stream.php` | Video relay for the browser player (jio.com only) |
| `player.php` | Browser player (shaka-player) |
| `config.php` | Settings, cache and playlist rewriting |
| `router.php` | Routing for `php -S` |
| `cache/` | Cached playlist (3 hours) |

## Options
- `jiostb.php?refresh=1` — fetch a fresh copy now
- `jiostb.php?status=1` — JSON: `exists`, `size`, `age_sec`, `ttl_sec`, `next_fetch`
- `php jiostb.php` — refresh from the command line, e.g. cron: `0 */3 * * * php /path/akindi-php/jiostb.php`

## Adding to players
- **NS Player:** Playlist → Add playlist → paste `http://<your-ip>:8000/jiostb.m3u`
- **OTT Navigator:** Settings → Manage playlists → Add → URL → paste the link

Video playback needs an Indian internet connection (Jio blocks other countries).
