<?php
/**
 * CORS Helper - Centraliza la configuración de headers CORS
 * Incluir al inicio de TODOS los archivos PHP de la API
 */

// Limpiar cualquier output previo
if (ob_get_level() > 0) {
    ob_clean();
}

// Headers CORS - DEBE ser lo primero
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS, PATCH');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Origin, Accept');
header('Access-Control-Max-Age: 86400');
header('Content-Type: application/json; charset=utf-8');

// Manejar preflight requests (OPTIONS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Configuración de errores
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
?>
