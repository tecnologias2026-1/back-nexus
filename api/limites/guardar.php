<?php
// Incluir helper centralizado de CORS (DEBE ser lo primero)
require_once __DIR__ . '/../../cors-helper.php';

// Verificación estricta del método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Método no permitido. Se requiere POST."]);
    exit();
}

// Inclusión de la conexión centralizada a la base de datos
require_once __DIR__ . '/../../database/connection.php';

// Captura y decodificación del cuerpo JSON enviado por el cliente
$input = json_decode(file_get_contents("php://input"), true);

// Validación de la existencia de datos obligatorios
if (empty($input['usuario_id']) || empty($input['categoria_id']) || !isset($input['monto_limite'])) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Datos incompletos. Se requiere usuario_id, categoria_id y monto_limite."]);
    exit();
}

// Extracción y casteo de variables para la consulta
$usuario_id = (int)$input['usuario_id'];
$categoria_id = (int)$input['categoria_id'];
$monto_limite = floatval($input['monto_limite']);

// 1. Verificar si ya existe un límite establecido para ese usuario y esa categoría
$check_query = "SELECT id FROM limites WHERE usuario_id = ? AND categoria_id = ?";
$stmt_check = $conn->prepare($check_query);
$stmt_check->bind_param("ii", $usuario_id, $categoria_id);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

if ($result_check->num_rows > 0) {
    // Escenario A: Ya existe, por lo tanto actualizamos el monto
    $row = $result_check->fetch_assoc();
    $limite_id = $row['id'];
    
    $update_query = "UPDATE limites SET monto_limite = ? WHERE id = ?";
    $stmt_update = $conn->prepare($update_query);
    $stmt_update->bind_param("di", $monto_limite, $limite_id);
    
    if ($stmt_update->execute()) {
        http_response_code(200);
        echo json_encode([
            "success" => true,
            "message" => "Límite de presupuesto actualizado con éxito.",
            "limite_id" => $limite_id,
            "accion" => "actualizado"
        ]);
    } else {
        http_response_code(400);
        echo json_encode(["success" => false, "error" => "Error al actualizar el límite: " . $conn->error]);
    }
    $stmt_update->close();
} else {
    // Escenario B: No existe, creamos un registro nuevo
    $insert_query = "INSERT INTO limites (usuario_id, categoria_id, monto_limite) VALUES (?, ?, ?)";
    $stmt_insert = $conn->prepare($insert_query);
    $stmt_insert->bind_param("iid", $usuario_id, $categoria_id, $monto_limite);
    
    if ($stmt_insert->execute()) {
        http_response_code(201); // 201 Created
        echo json_encode([
            "success" => true,
            "message" => "Límite de presupuesto creado con éxito.",
            "limite_id" => $conn->insert_id,
            "accion" => "creado"
        ]);
    } else {
        http_response_code(400);
        echo json_encode(["success" => false, "error" => "Error al crear el límite: " . $conn->error]);
    }
    $stmt_insert->close();
}

$stmt_check->close();
$conn->close();
?>