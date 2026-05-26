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

// Validación de la presencia de todos los campos obligatorios para el modelo de datos
if (
    empty($input['usuario_id']) || 
    empty($input['categoria_id']) || 
    empty($input['tipo']) || 
    empty($input['nombre']) || 
    !isset($input['valor']) || 
    empty($input['fecha'])
) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Campos obligatorios incompletos (usuario_id, categoria_id, tipo, nombre, valor, fecha)."]);
    exit();
}

// Extracción y casteo de variables para evitar inconsistencias de tipos
$usuario_id = (int)$input['usuario_id'];
$categoria_id = (int)$input['categoria_id'];
$tipo = trim($input['tipo']); // 'gasto' o 'ingreso'
$nombre = trim($input['nombre']);
$valor = floatval($input['valor']);
$fecha = trim($input['fecha']); // Formato 'YYYY-MM-DD'

// Manejo de banderas booleanas opcionales (por defecto falsas si no se envían)
$recurrente = isset($input['recurrente']) ? (int)$input['recurrente'] : 0;
$fijo = isset($input['fijo']) ? (int)$input['fijo'] : 0;

// Sentencia preparada para mitigar riesgos de inyección SQL
$query = "INSERT INTO transacciones (usuario_id, categoria_id, tipo, nombre, valor, fecha, recurrente, fijo) 
          VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($query);

if ($stmt) {
    // Vinculación de parámetros: i = entero, s = string, d = doble/decimal
    $stmt->bind_param("iisddsii", $usuario_id, $categoria_id, $tipo, $nombre, $valor, $fecha, $recurrente, $fijo);
    
    if ($stmt->execute()) {
        http_response_code(201); // 201 Created
        echo json_encode([
            "success" => true,
            "message" => "Transacción registrada exitosamente en el sistema.",
            "transaccion_id" => $conn->insert_id
        ]);
    } else {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "error" => "Error al ejecutar la inserción: " . $conn->error
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