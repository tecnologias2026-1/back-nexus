<?php
/**
 * Conexión a MySQL.
 *
 * Lanza mysqli_sql_exception si la conexión falla. El bootstrap.php
 * captura cualquier excepción y la formatea como JSON.
 *
 * No emite output ni headers — eso es responsabilidad del bootstrap.
 */

require_once __DIR__ . '/../bootstrap.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$host = getenv('DB_HOST') ?: 'localhost';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';
$db   = getenv('DB_NAME') ?: '';
$port = (int) (getenv('DB_PORT') ?: 3306);

$conn = new mysqli($host, $user, $pass, $db, $port);
$conn->set_charset('utf8mb4');
