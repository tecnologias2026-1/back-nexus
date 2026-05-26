<?php
require_once __DIR__ . '/../../cors-helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Método no permitido. Se requiere GET."]);
    exit();
}

require_once __DIR__ . '/../../database/connection.php';

if (empty($_GET['usuario_id'])) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Falta el parámetro obligatorio: usuario_id."]);
    exit();
}

$usuario_id = (int)$_GET['usuario_id'];

$query = "SELECT a.id AS relacion_id, a.amigo_id, u.nombre, u.nivel, u.racha_dias, u.puntos_ranking
          FROM amigos a
          INNER JOIN usuarios u ON u.id = a.amigo_id
          WHERE a.usuario_id = ?
          ORDER BY u.puntos_ranking DESC";

$stmt = $conn->prepare($query);

if ($stmt) {
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $amigos = [];
    while ($row = $result->fetch_assoc()) {
        $amigos[] = [
            "id" => (int)$row['amigo_id'],
            "relacion_id" => (int)$row['relacion_id'],
            "usuario_id" => $usuario_id,
            "nombre" => $row['nombre'],
            "nivel" => (int)$row['nivel'],
            "racha" => (int)$row['racha_dias'],
            "puntos" => (int)$row['puntos_ranking'],
            "tendencia" => "neutral"
        ];
    }

    http_response_code(200);
    echo json_encode([
        "success" => true,
        "amigos" => $amigos
    ]);

    $stmt->close();
} else {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => "Error en la preparación de la consulta: " . $conn->error
    ]);
}

$conn->close();
?>
