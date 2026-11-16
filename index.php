<?php $v = max(filemtime(__DIR__ . '/assets/style.css'), filemtime(__DIR__ . '/assets/app.js')); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>ESP32 Sensor Dashboard</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@500;700&family=Public+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/style.css?v=<?= $v ?>">
</head>
<body>
<header class="bar">
  <div class="bar-in">
    <h1 class="brand">ESP32 Sensor Dashboard</h1>
    <p id="status" class="status" data-state="connecting" role="status">
      <span class="dot" aria-hidden="true"></span>
      <span id="status-text">Connecting</span>
    </p>
  </div>
</header>

<main class="shell">
  <section id="warning" class="warning" role="alert" hidden>
    <h2>Sensor not connected</h2>
    <p>Missing: <strong id="missing"></strong>. The other sensors keep reporting.</p>
  </section>

  <section id="waiting" class="panel">
    <p id="waiting-text">Waiting for the first reading from the ESP32.</p>
  </section>

  <div id="board" hidden>
    <section class="hero">
      <div class="now panel">
        <h2 class="panel-title">Right now</h2>
        <p class="temp"><span id="temp" class="num">--</span><span class="unit">°C</span></p>
        <p class="hum"><span id="hum">--</span>% humidity</p>
        <p id="feel" class="feel"></p>
      </div>
      <div class="sensors panel">
        <h2 class="panel-title">Sensors</h2>
        <ul class="health">
          <li id="h-dht"><span class="tick" aria-hidden="true"></span>DHT11 temperature and humidity</li>
          <li id="h-sonar"><span class="tick" aria-hidden="true"></span>HC-SR04 distance</li>
        </ul>
        <p class="muted small">The motion, light and rain modules give a simple on or off signal, so the board cannot tell if one is unplugged.</p>
      </div>
    </section>

    <section class="states" aria-label="Sensor states">
      <div id="state-motion" class="state" data-tone="idle"><h3 class="state-name">Motion</h3><p class="state-value">--</p></div>
      <div id="state-light"  class="state" data-tone="idle"><h3 class="state-name">Light</h3><p class="state-value">--</p></div>
      <div id="state-rain"   class="state" data-tone="idle"><h3 class="state-name">Rain</h3><p class="state-value">--</p></div>
      <div id="state-dist"   class="state" data-tone="idle">
        <h3 class="state-name">Distance</h3>
        <p class="state-value" id="dist">--</p>
        <div class="gauge" aria-hidden="true"><div id="gauge-fill" class="gauge-fill"></div></div>
      </div>
    </section>
  </div>

  <p class="muted small">Last refreshed: <span id="refreshed">--</span></p>
</main>

<script src="assets/app.js?v=<?= $v ?>"></script>
</body>
</html>
