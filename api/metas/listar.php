<?php
// Incluir helper centralizado de CORS (DEBE ser lo primero)
require_once __DIR__ . '/../../cors-helper.php';

// Verificación estricta del método HTTP GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Método no permitido. Se requiere GET."]);
    exit();
}

// Inclusión de la conexión centralizada a la base de datos
require_once __DIR__ . '/../../database/connection.php';

// Validación de la presencia del parámetro usuario_id en la URL (?usuario_id=X)
if (empty($_GET['usuario_id'])) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Falta el parámetro obligatorio: usuario_id."]);
    exit();
}

$usuario_id = (int)$_GET['usuario_id'];

// Consulta estructurada para obtener las metas del usuario ordenadas por la fecha límite más cercana
$query = "SELECT id, usuario_id, nombre, monto_objetivo, monto_actual, fecha_limite 
          FROM metas 
          WHERE usuario_id = ? 
          ORDER BY fecha_limite ASC";

$stmt = $conn->prepare($query);

if ($stmt) {
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $metas = [];
    
    // Mapeo estructurado con tipado numérico explícito para floats y enteros
    while ($row = $result->fetch_assoc()) {
        $metas[] = [
            "id" => (int)$row['id'],
            "usuario_id" => (int)$row['usuario_id'],
            "nombre" => $row['nombre'],
            "monto_objetivo" => (float)$row['monto_objetivo'],
            "monto_actual" => (float)$row['monto_actual'],
            "fecha_limite" => $row['fecha_limite']
        ];
    }
    
    http_response_code(200);
    echo json_encode([
        "success" => true,
        "metas" => $metas
    ]);
    
    $stmt->close();
} else {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => "Error en la preparación de la consulta de metas: " . $conn->error
    ]);
}

$conn->close();
?>