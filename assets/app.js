(() => {
  'use strict';

  const POLL_MS = 3000;
  const ONLINE_MAX_S = 15;   // ESP32 posts about every 5 s
  const STALE_MAX_S = 60;
  const MAX_DISTANCE = 400;

  const $ = (id) => document.getElementById(id);
  const word = (v) => String(v ?? '').trim().toUpperCase();
  const num = (v) => { const n = parseFloat(v); return Number.isFinite(n) ? n : null; };

  function setStatus(state, text) {
    $('status').dataset.state = state;
    $('status-text').textContent = text;
  }

  function setState(id, tone, label) {
    const el = $(id);
    el.dataset.tone = tone;
    el.querySelector('.state-value').textContent = label;
  }

  function describe(t, h) {
    if (t === null) return '';
    const feel = t >= 33 ? 'Hot' : t >= 29 ? 'Warm' : t >= 23 ? 'Comfortable' : 'Cool';
    const air = h === null ? '' : h >= 75 ? ' and humid' : h <= 40 ? ' and dry' : '';
    return feel + air + '.';
  }

  function render(d) {
    const dhtOk = d.dht_ok === true;
    const sonarOk = d.sonar_ok === true;

    $('h-dht').dataset.ok = String(dhtOk);
    $('h-sonar').dataset.ok = String(sonarOk);

    const missing = [];
    if (!dhtOk) missing.push('DHT11');
    if (!sonarOk) missing.push('HC-SR04');
    $('warning').hidden = missing.length === 0;
    $('missing').textContent = missing.join(', ');

    const t = dhtOk ? num(d.temperature) : null;
    const h = dhtOk ? num(d.humidity) : null;
    $('temp').textContent = t === null ? '--' : t.toFixed(1);
    $('hum').textContent = h === null ? '--' : Math.round(h);
    $('feel').textContent = dhtOk ? describe(t, h) : 'The temperature sensor is not responding.';

    const motion = word(d.motion);
    if (motion === 'DETECTED') setState('state-motion', 'alert', 'Motion detected');
    else if (motion) setState('state-motion', 'ok', 'No motion');
    else setState('state-motion', 'idle', '--');

    const light = word(d.light);
    if (light === 'BRIGHT') setState('state-light', 'ok', 'Bright');
    else if (light === 'DARK') setState('state-light', 'dark', 'Dark');
    else setState('state-light', 'idle', '--');

    const rain = word(d.rain);
    if (rain === 'RAINING') setState('state-rain', 'wet', 'Raining');
    else if (rain) setState('state-rain', 'ok', 'Dry');
    else setState('state-rain', 'idle', '--');

    const dist = sonarOk ? num(d.distance) : null;
    const fill = $('gauge-fill');
    if (dist === null || dist <= 0) {
      $('dist').textContent = sonarOk ? 'No reading' : 'Not connected';
      $('state-dist').dataset.tone = sonarOk ? 'idle' : 'fail';
      fill.style.width = '0%';
    } else {
      $('dist').textContent = Math.round(dist) + ' cm';
      $('state-dist').dataset.tone = 'idle';
      fill.style.width = Math.min(dist / MAX_DISTANCE, 1) * 100 + '%';
    }
  }

  async function poll() {
    if (document.hidden) return;
    try {
      const res = await fetch('sensor_data.php', { cache: 'no-store' });
      const d = await res.json();
      $('refreshed').textContent = new Date().toLocaleString();

      if (d.error) {
        $('board').hidden = true;
        $('warning').hidden = true;
        $('waiting').hidden = false;
        $('waiting-text').textContent = d.error === 'No data yet'
          ? 'Waiting for the first reading from the ESP32.'
          : d.error;
        setStatus('offline', d.error === 'No data yet' ? 'No data received' : 'Server error');
        return;
      }

      $('waiting').hidden = true;
      $('board').hidden = false;
      render(d);

      const age = d.age_seconds ?? 9999;
      if (age <= ONLINE_MAX_S) setStatus('online', 'ESP32 connected');
      else if (age <= STALE_MAX_S) setStatus('stale', 'ESP32 may be disconnected (' + age + ' s ago)');
      else setStatus('offline', 'ESP32 offline (last seen ' + Math.round(age / 60) + ' min ago)');
    } catch (e) {
      setStatus('offline', 'Cannot reach the server');
    }
  }

  poll();
  setInterval(poll, POLL_MS);
  document.addEventListener('visibilitychange', () => { if (!document.hidden) poll(); });
})();
