<?php
// Incluir helper centralizado de CORS (DEBE ser lo primero)
require_once __DIR__ . '/../../cors-helper.php';

// Verificación estricta del método HTTP POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Método no permitido. Se requiere POST."]);
    exit();
}

require_once __DIR__ . '/../../database/connection.php';

$input = json_decode(file_get_contents("php://input"), true);

if (empty($input['meta_id']) || !isset($input['monto_abono']) || floatval($input['monto_abono']) <= 0) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Datos inválidos o incompletos. Se requiere meta_id y un monto_abono mayor a 0."]);
    exit();
}

$meta_id = (int)$input['meta_id'];
$monto_abono = floatval($input['monto_abono']);

// 1. Obtener el estado actual de la meta
$query_select = "SELECT monto_actual, monto_objetivo FROM metas WHERE id = ?";
$stmt_select = $conn->prepare($query_select);
$stmt_select->bind_param("i", $meta_id);
$stmt_select->execute();
$result = $stmt_select->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(["success" => false, "message" => "La meta de ahorro especificada no existe."]);
    $stmt_select->close();
    exit();
}

$meta = $result->fetch_assoc();
$stmt_select->close();

$nuevo_monto_actual = floatval($meta['monto_actual']) + $monto_abono;
$monto_objetivo = floatval($meta['monto_objetivo']);

// Condicional opcional: saber si con este abono completó la meta
$completada = ($nuevo_monto_actual >= $monto_objetivo);

// 2. Actualizar el monto acumulado en la base de datos
$query_update = "UPDATE metas SET monto_actual = ? WHERE id = ?";
$stmt_update = $conn->prepare($query_update);

if ($stmt_update) {
    $stmt_update->bind_param("di", $nuevo_monto_actual, $meta_id);
    if ($stmt_update->execute()) {
        http_response_code(200);
        echo json_encode([
            "success" => true,
            "message" => $completada ? "¡Felicidades! Has alcanzado el objetivo de tu meta." : "Abono procesado correctamente.",
            "meta_actualizada" => [
                "meta_id" => $meta_id,
                "monto_anterior" => floatval($meta['monto_actual']),
                "abono_ingresado" => $monto_abono,
                "monto_actual" => $nuevo_monto_actual,
                "monto_objetivo" => $monto_objetivo,
                "completada" => $completada
            ]
        ]);
    } else {
        http_response_code(400);
        echo json_encode(["success" => false, "error" => "Error al registrar el abono: " . $conn->error]);
    }
    $stmt_update->close();
}
$conn->close();
?>