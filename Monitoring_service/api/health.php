
<?php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../health_functions.php';

// Hanya menerima GET
requireGet();

// Tanpa parameter: cek Health Checker sendiri
if (!isset($_GET['name']) || trim($_GET['name']) === '') {
    sendJson([
        'success' => true,
        'service' => 'Monitoring_service',
        'status' => 'UP',
        'message' => 'Health Checker berjalan dengan baik',
        'checked_at' => gmdate('c')
    ]);
}

// Dengan parameter: cek service yang diminta
$targetName = trim($_GET['name']);

$services = getServices();
$service = findService($services, $targetName);

if ($service === null) {
    sendJson([
        'success' => false,
        'message' => 'Service tidak ditemukan',
        'requested_name' => $targetName
    ], 404);
}

$result = checkService($service);

sendJson([
    'success' => true,
    'checked_at' => gmdate('c'),
    'service' => $result
]);
