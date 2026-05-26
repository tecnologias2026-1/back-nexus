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

// Validación de los campos obligatorios (ID del remitente y ID del amigo a agregar)
if (empty($input['usuario_id']) || empty($input['amigo_id'])) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Datos incompletos. Se requiere usuario_id y amigo_id."]);
    exit();
}

$usuario_id = (int)$input['usuario_id'];
$amigo_id = (int)$input['amigo_id'];

// Regla de negocio básica: Un usuario no puede agregarse a sí mismo
if ($usuario_id === $amigo_id) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "No puedes agregarte a ti mismo como amigo."]);
    exit();
}

// 1. Verificar si el usuario destino existe en la tabla de usuarios
$user_check = "SELECT id FROM usuarios WHERE id = ?";
$stmt_u = $conn->prepare($user_check);
$stmt_u->bind_param("i", $amigo_id);
$stmt_u->execute();
if ($stmt_u->get_result()->num_rows === 0) {
    http_response_code(444); // Código personalizado o 404 para indicar que el amigo no existe
    echo json_encode(["success" => false, "message" => "El usuario que intentas agregar no existe."]);
    $stmt_u->close();
    exit();
}
$stmt_u->close();

// 2. Verificar si ya existe un vínculo previo entre ambos para evitar duplicados
$check_query = "SELECT id FROM amigos WHERE (usuario_id = ? AND amigo_id = ?) OR (usuario_id = ? AND amigo_id = ?)";
$stmt_check = $conn->prepare($check_query);
$stmt_check->bind_param("iiii", $usuario_id, $amigo_id, $amigo_id, $usuario_id);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

if ($result_check->num_rows > 0) {
    http_response_code(409); // 409 Conflict
    echo json_encode(["success" => false, "message" => "Ya existe una relación o solicitud de amistad activa entre estos usuarios."]);
    $stmt_check->close();
    exit();
}
$stmt_check->close();

// 3. Proceder a insertar el nuevo vínculo de amistad (Por defecto en estado 'pendiente' o 'aceptado' según tu flujo)
// Nota: Ajusta las columnas si tu tabla maneja un campo 'estado', aquí asumiremos una inserción limpia de IDs directos.
$query_insert = "INSERT INTO amigos (usuario_id, amigo_id) VALUES (?, ?)";
$stmt_insert = $conn->prepare($query_insert);

if ($stmt_insert) {
    $stmt_insert->bind_param("ii", $usuario_id, $amigo_id);
    
    if ($stmt_insert->execute()) {
        http_response_code(201); // 201 Created
        echo json_encode([
            "success" => true,
            "message" => "Vínculo de amistad registrado exitosamente.",
            "relacion_id" => $conn->insert_id
        ]);
    } else {
        http_response_code(400);
        echo json_encode(["success" => false, "error" => "Error al registrar la amistad: " . $conn->error]);
    }
    $stmt_insert->close();
} else {
    http_response_code(500);
    echo json_encode(["success" => false, "error" => "Error en la preparación de la inserción: " . $conn->error]);
}

$conn->close();
?>