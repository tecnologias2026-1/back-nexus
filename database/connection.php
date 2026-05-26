<?php
// Database connection
// En lugar de poner los datos aquí, los lees del servidor
$host = getenv('DB_HOST') ?: 'localhost';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';
$db   = getenv('DB_NAME') ?: 'tu_base_datos';

$conn = new mysqli($host, $user, $pass, $db);



// Check connection
if ($conn->connect_error) {
  die(json_encode(['error' => 'Error conectando a la base de datos: ' . $conn->connect_error]));
}

// Set charset
$conn->set_charset('utf8mb4');

/*$conn->close();*/
