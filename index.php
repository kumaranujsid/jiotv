<?php
require __DIR__ . '/config.php';
$link = base_url() . '/jiostb.m3u';
$st = cache_status();
function fmt_age(?int $s): string {
    if ($s === null) return '—';
    if ($s < 60) return $s . 's';
    if ($s < 3600) return floor($s / 60) . 'm';
    return floor($s / 3600) . 'h ' . floor(($s % 3600) / 60) . 'm';
}
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Akindi — JioTV Playlist for NS Player &amp; OTT Navigator</title>
<meta name="description" content="One playlist link for NS Player and OTT Navigator, plus a browser player.">
<link rel="stylesheet" href="assets/style.css">
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
</head>
<body>
<header class="top"><div class="wrap row"><b>Akindi</b><span class="live"><i></i>Live</span></div></header>
<main class="wrap">
  <section class="hero">
    <h1>Your channels, <span class="accent">one link</span>.</h1>
    <p class="muted">Paste this playlist into NS Player or OTT Navigator, or watch right here in the browser.</p>
    <div class="actions"><a class="btn primary" href="player.php">▶ Watch online</a>
      <button class="btn" onclick="copyLink()">Copy link</button></div>
  </section>

  <section class="card">
    <h2>Playlist link</h2>
    <div class="linkrow"><code id="link"><?= h($link) ?></code><button class="btn" onclick="copyLink()" id="copyBtn">Copy</button></div>
    <button class="btn ghost" onclick="toggleQR()">Show QR code for TV</button>
    <div id="qr" class="qr" hidden></div>
  </section>

  <section class="stats">
    <div class="card"><small>Channels</small><b>1,100+</b></div>
    <div class="card"><small>Cache</small><b><?= $st['exists'] ? 'Ready' : 'Empty' ?></b></div>
    <div class="card"><small>Age</small><b><?= fmt_age($st['age_sec']) ?></b></div>
    <div class="card"><small>Next refresh</small><b><?= $st['exists'] ? fmt_age($st['next_fetch']) : '—' ?></b></div>
  </section>

  <section class="grid2">
    <div class="card"><h2>NS Player</h2><ol>
      <li>Open NS Player → <b>Playlist</b> → <b>Add playlist</b>.</li>
      <li>Paste the link above and save.</li>
      <li>Open the playlist and pick a channel.</li></ol></div>
    <div class="card"><h2>OTT Navigator</h2><ol>
      <li>Settings → <b>Manage playlists</b> → <b>Add</b>.</li>
      <li>Choose <b>URL</b> and paste the link.</li>
      <li>Let it load, then refresh if the list looks stale.</li></ol></div>
  </section>
  <p class="muted small">Refresh now: <a href="jiostb.php?refresh=1">jiostb.php?refresh=1</a> · Status: <a href="jiostb.php?status=1">jiostb.php?status=1</a></p>
</main>
<nav class="mbar"><a class="btn primary" href="player.php">▶ Watch</a><button class="btn" onclick="copyLink()">Copy link</button></nav>
<script>
const LINK = <?= json_encode($link, JSON_UNESCAPED_SLASHES) ?>;
function copyLink(){navigator.clipboard?.writeText(LINK).then(()=>{const b=document.getElementById('copyBtn');b.textContent='Copied!';setTimeout(()=>b.textContent='Copy',1500)});}
let qrMade=false;
function toggleQR(){const q=document.getElementById('qr');q.hidden=!q.hidden;if(!qrMade&&window.QRCode){new QRCode(q,{text:LINK,width:200,height:200});qrMade=true;}}
</script>
</body>
</html>
