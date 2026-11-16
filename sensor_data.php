<?php
// POST: the ESP32 sends a reading (needs the X-API-Key header).
// GET : the dashboard reads the latest reading.
require __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$dir  = __DIR__ . '/data';
$file = $dir . '/latest.json';

function fail(int $code, string $msg): void {
    http_response_code($code);
    echo json_encode(['error' => $msg]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $key = (string)($_SERVER['HTTP_X_API_KEY'] ?? '');
    if (!hash_equals(API_KEY, $key)) fail(401, 'Invalid API key');

    $raw = file_get_contents('php://input');
    if ($raw === false || strlen($raw) > 4096) fail(413, 'Body too large');

    $in = json_decode($raw, true);
    if (!is_array($in)) fail(400, 'Invalid JSON');

    $num  = fn($k) => (isset($in[$k]) && is_numeric($in[$k])) ? (float) $in[$k] : null;
    $text = fn($k) => isset($in[$k]) ? substr((string) $in[$k], 0, 20) : null;

    $out = [
        'temperature' => $num('temperature'),
        'humidity'    => $num('humidity'),
        'distance'    => $num('distance'),
        'motion'      => $text('motion'),
        'light'       => $text('light'),
        'rain'        => $text('rain'),
        'dht_ok'      => ($in['dht_ok'] ?? false) === true,
        'sonar_ok'    => ($in['sonar_ok'] ?? false) === true,
        'received_at' => time(),
    ];

    if (!is_dir($dir) && !@mkdir($dir, 0755, true)) fail(500, 'Cannot create data folder');
    if (@file_put_contents($file, json_encode($out), LOCK_EX) === false) {
        fail(500, 'Cannot write data/latest.json. Check the folder permissions.');
    }
    echo json_encode(['status' => 'OK']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!is_file($file)) { echo json_encode(['error' => 'No data yet']); exit; }
    $data = json_decode((string) @file_get_contents($file), true);
    if (!is_array($data)) { echo json_encode(['error' => 'Corrupt data file']); exit; }
    // Age is computed on the server so the viewer's clock does not matter.
    $data['age_seconds'] = max(0, time() - (int)($data['received_at'] ?? 0));
    echo json_encode($data);
    exit;
}

fail(405, 'Method not allowed');
