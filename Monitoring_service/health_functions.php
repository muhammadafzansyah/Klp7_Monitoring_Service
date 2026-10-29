
<?php

// GANTI dengan alamat Gateway kamu
define('GATEWAY_URL', 'http://10.194.169.101:8000');

// Endpoint Gateway yang menyediakan daftar service
define('REGISTRY_URL', GATEWAY_URL . '/api/gateway/routes');

// Mengirim respons JSON
function sendJson($data, $statusCode = 200)
{
    http_response_code($statusCode);

    echo json_encode(
        $data,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
    );

    exit;
}

// Memastikan API hanya menerima GET
function requireGet()
{
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        header('Allow: GET');

        sendJson([
            'success' => false,
            'message' => 'Gunakan HTTP method GET'
        ], 405);
    }
}

// Mengirim HTTP GET menggunakan cURL
function httpGet($url)
{
    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_HTTPGET => true,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json'
        ]
    ]);

    $body = curl_exec($ch);
    $error = curl_error($ch);
    $httpCode = (int) curl_getinfo(
        $ch,
        CURLINFO_HTTP_CODE
    );

    curl_close($ch);

    return [
        'body' => $body,
        'http_code' => $httpCode,
        'error' => $error
    ];
}

// Mengambil daftar service dari Gateway

function getServices()
{
    $response = httpGet(REGISTRY_URL);

    if (
        $response['body'] === false ||
        $response['http_code'] < 200 ||
        $response['http_code'] >= 300
    ) {
        sendJson([
            'success' => false,
            'message' => 'Gagal mengambil daftar service dari Gateway',
            'registry_url' => REGISTRY_URL,
            'http_code' => $response['http_code'],
            'error' => $response['error'] ?: null
        ], 502);
    }

    $data = json_decode($response['body'], true);

    if (!is_array($data)) {
        sendJson([
            'success' => false,
            'message' => 'JSON dari Gateway tidak valid'
        ], 502);
    }

    // Struktur respons Gateway:
    // {
    //   "status": "success",
    //   "service_routes": {
    //      "auth": {...},
    //      "course": {...}
    //   }
    // }

    if (
        ($data['status'] ?? '') !== 'success' ||
        !isset($data['service_routes']) ||
        !is_array($data['service_routes'])
    ) {
        sendJson([
            'success' => false,
            'message' => 'Format service_routes tidak sesuai'
        ], 502);
    }

    $services = [];

    foreach ($data['service_routes'] as $name => $service) {
        $service['name'] = $name;
        $services[] = $service;
    }

    return $services;
}


function checkService($service)
{
    $name = $service['name'] ?? null;
    $baseUrl = $service['base_url'] ?? null;
    $endpoints = $service['endpoints'] ?? [];

    if (!$name || !$baseUrl) {
        return [
            'name' => $name,
            'base_url' => $baseUrl,
            'status' => 'DOWN',
            'message' => 'Nama service atau base_url tidak tersedia'
        ];
    }

    if (!preg_match('#^https?://#i', $baseUrl)) {
        return [
            'name' => $name,
            'base_url' => $baseUrl,
            'status' => 'DOWN',
            'message' => 'base_url harus menggunakan HTTP atau HTTPS'
        ];
    }

    // Cari endpoint GET yang berkaitan dengan health
    $healthPath = null;

    foreach ($endpoints as $endpoint) {
        $method = strtoupper($endpoint['method'] ?? '');
        $path = $endpoint['path'] ?? '';

        if (
            $method === 'GET' &&
            preg_match('#(^|/)health/?$#i', $path)
        ) {
            $healthPath = '/' . ltrim($path, '/');
            break;
        }
    }

    if ($healthPath === null) {
        return [
            'name' => $name,
            'base_url' => $baseUrl,
            'status' => 'UNKNOWN',
            'message' => 'Endpoint GET health tidak ditemukan',
            'endpoints' => $endpoints
        ];
    }

    $healthUrl = rtrim($baseUrl, '/') . $healthPath;
    $response = httpGet($healthUrl);

    $isUp = (
        $response['body'] !== false &&
        $response['http_code'] >= 200 &&
        $response['http_code'] < 300
    );

    $healthBody = null;

    if (
        $response['body'] !== false &&
        $response['body'] !== ''
    ) {
        $decoded = json_decode($response['body'], true);

        $healthBody = json_last_error() === JSON_ERROR_NONE
            ? $decoded
            : $response['body'];
    }

    return [
        'name' => $name,
        'base_url' => $baseUrl,
        'health_url' => $healthUrl,
        'method' => 'GET',
        'path' => $healthPath,
        'status' => $isUp ? 'UP' : 'DOWN',
        'http_code' => $response['http_code'],
        'error' => $response['error'] ?: null,
        'endpoints' => $endpoints,
        'health_response' => $healthBody
    ];
}

// Mencari service berdasarkan nama logis
function findService($services, $targetName)
{
    foreach ($services as $service) {
        $name = $service['name']
            ?? $service['service_name']
            ?? $service['logical_name']
            ?? '';

        if (strcasecmp($name, $targetName) === 0) {
            return $service;
        }
    }

    return null;
}
