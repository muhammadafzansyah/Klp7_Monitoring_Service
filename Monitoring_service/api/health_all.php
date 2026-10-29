
<?php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../health_functions.php';

requireGet();

$services = getServices();

$results = [];
$up = 0;
$down = 0;

foreach ($services as $service) {
    $result = checkService($service);

    $results[] = $result;

    if ($result['status'] === 'UP') {
        $up++;
    } else {
        $down++;
    }
}

$total = count($results);

sendJson([
    'success' => true,
    'checked_at' => gmdate('c'),
    'total_services' => $total,
    'services_up' => $up,
    'services_down' => $down,
    'overall_status' => $down === 0 ? 'UP' : 'DEGRADED',
    'services' => $results
]);
