<?php
// Incluir helper centralizado de CORS (DEBE ser lo primero)
require_once __DIR__ . '/../../cors-helper.php';

// Verificación estricta del método HTTP DELETE
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Método no permitido. Se requiere DELETE."]);
    exit();
}

require_once __DIR__ . '/../../database/connection.php';

if (empty($_GET['id'])) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Falta el parámetro obligatorio: id de la relación."]);
    exit();
}

$relacion_id = (int)$_GET['id'];

// Verificar si la relación existe
$check = $conn->prepare("SELECT id FROM amigos WHERE id = ?");
$check->bind_param("i", $relacion_id);
$check->execute();
if ($check->get_result()->num_rows === 0) {
    http_response_code(404);
    echo json_encode(["success" => false, "message" => "El vínculo de amistad no existe o ya fue eliminado."]);
    $check->close();
    exit();
}
$check->close();

// Eliminar relación
$query = "DELETE FROM amigos WHERE id = ?";
$stmt = $conn->prepare($query);

if ($stmt) {
    $stmt->bind_param("i", $relacion_id);
    if ($stmt->execute()) {
        http_response_code(200);
        echo json_encode(["success" => true, "message" => "Amigo eliminado de tu lista correctamente."]);
    } else {
        http_response_code(400);
        echo json_encode(["success" => false, "error" => "Error al eliminar la relación: " . $conn->error]);
    }
    $stmt->close();
}
$conn->close();
?>