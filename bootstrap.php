<?php
/**
 * Bootstrap del backend Nexus.
 *
 * Inclúyelo PRIMERO en cada endpoint. Se encarga de:
 *   - Handlers globales de errores / excepciones / fatales
 *   - CORS y Content-Type: application/json
 *   - Preflight OPTIONS
 *   - Output buffering (para que el JSON nunca salga sucio)
 *   - Helpers json_response() / json_error()
 *
 * NO abre la conexión a la DB. Eso lo sigue haciendo database/connection.php
 * (que ahora lanza excepciones que este bootstrap captura).
 *
 * Modo debug: APP_DEBUG=true en las variables de entorno de Railway.
 */

if (defined('NEXUS_BOOTSTRAP_LOADED')) {
    return;
}
define('NEXUS_BOOTSTRAP_LOADED', true);

define('APP_DEBUG', filter_var(getenv('APP_DEBUG'), FILTER_VALIDATE_BOOLEAN));

if (ob_get_level() === 0) {
    ob_start();
}

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', 'php://stderr');

$allowedOrigin = getenv('CORS_ORIGIN') ?: 'https://tecnologias2026-1.github.io';
header('Access-Control-Allow-Origin: ' . $allowedOrigin);
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS, PATCH');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Origin, Accept');
header('Access-Control-Max-Age: 86400');
header('Vary: Origin');
header('Content-Type: application/json; charset=utf-8');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

function json_response(array $payload, int $status = 200): void {
    while (ob_get_level() > 0) {
        @ob_end_clean();
    }
    http_response_code($status);
    $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
    if ($body === false) {
        $body = '{"success":false,"error":"json_encode failed: ' . addslashes(json_last_error_msg()) . '"}';
    }
    echo $body;
    exit;
}

function json_error(string $message, int $status = 400, array $extra = []): void {
    json_response(array_merge(['success' => false, 'error' => $message], $extra), $status);
}

function nexus_emit_error(Throwable $e): void {
    $errorId = bin2hex(random_bytes(6));

    $logLine = sprintf(
        "[NEXUS] error_id=%s %s: %s in %s:%d\n%s",
        $errorId,
        get_class($e),
        $e->getMessage(),
        $e->getFile(),
        $e->getLine(),
        $e->getTraceAsString()
    );
    error_log($logLine);

    if (APP_DEBUG) {
        $payload = [
            'success'  => false,
            'error'    => $e->getMessage(),
            'class'    => get_class($e),
            'file'     => $e->getFile(),
            'line'     => $e->getLine(),
            'trace'    => explode("\n", $e->getTraceAsString()),
            'error_id' => $errorId,
        ];
    } else {
        $payload = [
            'success'  => false,
            'error'    => 'Error interno del servidor',
            'error_id' => $errorId,
        ];
    }

    json_response($payload, 500);
}

set_exception_handler(static function (Throwable $e): void {
    nexus_emit_error($e);
});

set_error_handler(static function (int $errno, string $errstr, string $errfile, int $errline): bool {
    if (!(error_reporting() & $errno)) {
        return false;
    }
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});

register_shutdown_function(static function (): void {
    $err = error_get_last();
    if ($err === null) {
        return;
    }
    $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
    if (!in_array($err['type'], $fatalTypes, true)) {
        return;
    }
    nexus_emit_error(new ErrorException(
        $err['message'],
        0,
        $err['type'],
        $err['file'],
        $err['line']
    ));
});
