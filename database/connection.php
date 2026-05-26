<?php
// Suprimir errores que rompen el JSON
error_reporting(0);
ini_set('display_errors', 0);

$host = getenv('DB_HOST') ?: 'localhost';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';
$db   = getenv('DB_NAME') ?: 'tu_base_datos';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    // Si falla, enviamos JSON y morimos
    echo json_encode(['error' => 'Error de conexión']);
    exit;
}

$conn->set_charset('utf8mb4');
