<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Akindi Online Player</title>
<link rel="stylesheet" href="assets/style.css">
<script src="https://cdn.jsdelivr.net/npm/shaka-player@4.11.7/dist/shaka-player.compiled.js"></script>
</head>
<body>
<main class="wrap">
  <div class="row"><h1 style="font-size:1.5rem">Online Player</h1><a href="index.php">← Playlist link</a></div>
  <div class="card" style="padding:0;overflow:hidden">
    <video id="v" controls playsinline style="width:100%;aspect-ratio:16/9;background:#000"></video>
    <div style="padding:12px"><span id="now" class="muted">Pick a channel below</span><p id="err" class="err"></p></div>
  </div>
  <div class="row" style="gap:8px;margin:16px 0">
    <input id="q" placeholder="Search channels…" class="input" style="flex:1">
    <select id="g" class="input"></select>
  </div>
  <p class="muted small" id="count">Loading…</p>
  <div id="list" class="chgrid"></div>
</main>
<script>
const $ = (id) => document.getElementById(id);
let channels = [], current = null, player = null;

function parse(m3u){
  const out=[]; let cur={};
  for (const raw of m3u.split('\n')) {
    const l = raw.trim(); if (!l) continue;
    if (l.startsWith('#EXTINF')) {
      cur = { name: l.split(',').pop() || 'Channel',
        logo: (/tvg-logo="([^"]*)"/.exec(l)||[])[1] || '',
        group: (/group-title="([^"]*)"/.exec(l)||[])[1] || 'Other', keys: {} };
    } else if (l.startsWith('#KODIPROP:inputstream.adaptive.license_key=')) {
      const v = l.split('=').slice(1).join('=');
      for (const pair of v.split(',')) { const [k,key]=pair.split(':'); if(k&&key) cur.keys[k]=key; }
    } else if (!l.startsWith('#') && cur.name) { out.push({...cur, url: l.split('|')[0]}); cur = {}; }
  }
  return out;
}

function render(){
  const q = $('q').value.toLowerCase(), g = $('g').value;
  const list = channels.filter(c => (g==='All'||c.group===g) && c.name.toLowerCase().includes(q));
  $('count').textContent = list.length + ' channels';
  $('list').innerHTML = '';
  for (const c of list) {
    const b = document.createElement('button');
    b.className = 'ch' + (c===current?' on':'');
    if (c.logo) { const i=document.createElement('img'); i.src=c.logo; i.loading='lazy'; i.alt=''; b.appendChild(i); }
    const s=document.createElement('span'); s.textContent=c.name; b.appendChild(s);
    b.onclick = () => play(c);
    $('list').appendChild(b);
  }
}

async function play(c){
  current = c; render(); $('now').textContent = c.name; $('now').className=''; $('err').textContent='';
  shaka.polyfill.installAll();
  if (!player) { player = new shaka.Player(); await player.attach($('v')); }
  player.configure({ drm: { clearKeys: c.keys } });
  const token = new URL(c.url).search;
  const net = player.getNetworkingEngine();
  const setup = (relay) => {
    net.clearAllRequestFilters();
    net.registerRequestFilter((_t, req) => {
      req.uris = req.uris.map(uri => {
        if (uri.includes('stream.php?url=')) uri = decodeURIComponent(uri.split('url=')[1] || '');
        if (!/^https:\/\/[^/]*jio\.com\//.test(uri)) return uri;
        const u = new URL(uri); if (!u.search && token) u.search = token;
        return relay ? new URL('stream.php?url=' + encodeURIComponent(u.toString()), location.href).toString() : u.toString();
      });
    });
  };
  let last = null;
  for (const relay of [false, true]) {
    if (current !== c) return;
    setup(relay);
    try { await player.load(c.url); $('v').play().catch(()=>{}); return; } catch (e) { last = e; }
  }
  const status = last && last.data && last.data[1];
  $('err').textContent = `Couldn't play this channel in the browser (code ${last?.code ?? '?'}${status ? ', server said ' + status : ''}). Use NS Player for this channel.`;
}

fetch('jiostb.php').then(r => r.text()).then(t => {
  channels = parse(t);
  const groups = ['All', ...new Set(channels.map(c => c.group))];
  $('g').innerHTML = groups.map(g => `<option>${g.replace(/</g,'&lt;')}</option>`).join('');
  render();
});
$('q').oninput = render; $('g').onchange = render;
</script>
</body>
</html>
