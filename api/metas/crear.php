<?php
// Incluir helper centralizado de CORS (DEBE ser lo primero)
require_once __DIR__ . '/../../cors-helper.php';

// Verificación estricta del método HTTP POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Método no permitido. Se requiere POST."]);
    exit();
}

// Inclusión de la conexión centralizada a la base de datos
require_once __DIR__ . '/../../database/connection.php';

// Captura del cuerpo JSON de la solicitud
$input = json_decode(file_get_contents("php://input"), true);

// Validación de la presencia de todos los campos obligatorios para el modelo de metas
if (
    empty($input['usuario_id']) || 
    empty($input['nombre']) || 
    !isset($input['monto_objetivo']) || 
    empty($input['fecha_limite'])
) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Campos obligatorios incompletos (usuario_id, nombre, monto_objetivo, fecha_limite)."]);
    exit();
}

// Extracción y casteo de variables
$usuario_id = (int)$input['usuario_id'];
$nombre = trim($input['nombre']);
$monto_objetivo = floatval($input['monto_objetivo']);
$monto_actual = isset($input['monto_actual']) ? floatval($input['monto_actual']) : 0.00;
$fecha_limite = trim($input['fecha_limite']); // Formato 'YYYY-MM-DD'

// Sentencia preparada para mitigar riesgos de inyección SQL
$query = "INSERT INTO metas (usuario_id, nombre, monto_objetivo, monto_actual, fecha_limite) 
          VALUES (?, ?, ?, ?, ?)";

$stmt = $conn->prepare($query);

if ($stmt) {
    // Vinculación de parámetros: i = entero, s = string, d = doble/decimal
    $stmt->bind_param("isdds", $usuario_id, $nombre, $monto_objetivo, $monto_actual, $fecha_limite);
    
    if ($stmt->execute()) {
        http_response_code(201); // 201 Created
        echo json_encode([
            "success" => true,
            "message" => "Meta de ahorro creada exitosamente.",
            "meta_id" => $conn->insert_id
        ]);
    } else {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "error" => "Error al ejecutar la inserción de la meta: " . $conn->error
        ]);
    }
    $stmt->close();
} else {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => "Error en la preparación de la consulta: " . $conn->error
    ]);
}

// Cierre de la conexión
$conn->close();
?>