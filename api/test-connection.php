<?php
// Test de conexión a BD - SOLO PARA DIAGNÓSTICO
require_once __DIR__ . '/../cors-helper.php';

echo json_encode([
    "test" => "connection",
    "db_host" => getenv('DB_HOST') ?: 'localhost',
    "db_name" => getenv('DB_NAME') ?: 'tu_base_datos',
    "db_user" => getenv('DB_USER') ?: 'root'
]);

// Ahora intenta conectar
require_once __DIR__ . '/../database/connection.php';

if (!$conn) {
    echo json_encode(["error" => "No hay conexión"]);
    exit;
}

if ($conn->connect_error) {
    echo json_encode(["error" => $conn->connect_error]);
    exit;
}

echo json_encode(["success" => true, "message" => "Conexión OK"]);
$conn->close();
