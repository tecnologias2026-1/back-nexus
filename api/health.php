<?php
/**
 * Endpoint de salud — verifica el estado del backend y de la conexión a la DB
 * sin exponer credenciales.
 *
 *   GET /api/health.php
 *
 * 200 OK         si la DB responde
 * 503 Service Unavailable si la DB no conecta
 *
 * En modo APP_DEBUG=true incluye el mensaje exacto del error de conexión.
 */

require_once __DIR__ . '/../bootstrap.php';

$status = [
    'status'    => 'ok',
    'php'       => PHP_VERSION,
    'app_debug' => APP_DEBUG,
    'env' => [
        'DB_HOST_set' => getenv('DB_HOST') !== false && getenv('DB_HOST') !== '',
        'DB_USER_set' => getenv('DB_USER') !== false && getenv('DB_USER') !== '',
        'DB_PASS_set' => getenv('DB_PASS') !== false && getenv('DB_PASS') !== '',
        'DB_NAME_set' => getenv('DB_NAME') !== false && getenv('DB_NAME') !== '',
        'DB_PORT'     => getenv('DB_PORT') ?: 3306,
    ],
];

try {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $host = getenv('DB_HOST') ?: 'localhost';
    $user = getenv('DB_USER') ?: 'root';
    $pass = getenv('DB_PASS') ?: '';
    $db   = getenv('DB_NAME') ?: '';
    $port = (int) (getenv('DB_PORT') ?: 3306);

    $start = microtime(true);
    $c = new mysqli($host, $user, $pass, $db, $port);
    $latencyMs = round((microtime(true) - $start) * 1000, 2);
    $c->set_charset('utf8mb4');

    $row = $c->query('SELECT 1 AS ok')->fetch_assoc();
    $c->close();

    $status['database'] = [
        'connected'  => true,
        'latency_ms' => $latencyMs,
        'select_one' => (int) ($row['ok'] ?? 0),
    ];

    json_response($status, 200);
} catch (Throwable $e) {
    error_log('[health] DB connection failed: ' . $e->getMessage());

    $status['status']   = 'degraded';
    $status['database'] = ['connected' => false];

    if (APP_DEBUG) {
        $status['database']['error'] = $e->getMessage();
        $status['database']['class'] = get_class($e);
    }

    json_response($status, 503);
}
