<?php
// Incluir helper centralizado de CORS (DEBE ser lo primero)
require_once __DIR__ . '/../../cors-helper.php';

// Verificación estricta del método HTTP DELETE
}

require_once __DIR__ . '/../../database/connection.php';

if (empty($_GET['id'])) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Falta el parámetro obligatorio: id de la meta."]);
    exit();
}

$meta_id = (int)$_GET['id'];

// Verificar existencia antes de borrar
$check = $conn->prepare("SELECT id FROM metas WHERE id = ?");
$check->bind_param("i", $meta_id);
$check->execute();
if ($check->get_result()->num_rows === 0) {
    http_response_code(404);
    echo json_encode(["success" => false, "message" => "La meta de ahorro no existe o ya fue eliminada."]);
    $check->close();
    exit();
}
$check->close();

// Ejecutar borrado físico
$query = "DELETE FROM metas WHERE id = ?";
$stmt = $conn->prepare($query);

if ($stmt) {
    $stmt->bind_param("i", $meta_id);
    if ($stmt->execute()) {
        http_response_code(200);
        echo json_encode(["success" => true, "message" => "Meta de ahorro eliminada con éxito."]);
    } else {
        http_response_code(400);
        echo json_encode(["success" => false, "error" => "Error al eliminar la meta: " . $conn->error]);
    }
    $stmt->close();
}
$conn->close();
?>