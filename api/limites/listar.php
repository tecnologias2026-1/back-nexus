<?php
// Incluir helper centralizado de CORS (DEBE ser lo primero)
require_once __DIR__ . '/../../cors-helper.php';

// Verificación estricta del método HTTP. Se exige GET para la consulta de recursos
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Método no permitido. Se requiere GET."]);
    exit();
}

// Inclusión de la conexión centralizada a la base de datos
require_once __DIR__ . '/../../database/connection.php';

// Validación de la presencia del parámetro identificador en la URL (?usuario_id=X)
if (empty($_GET['usuario_id'])) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Falta el parámetro obligatorio: usuario_id."]);
    exit();
}

// Casteo explícito a tipo entero para blindar la consulta contra inyecciones
$usuario_id = (int)$_GET['usuario_id'];

// Consulta relacional para obtener los límites mapeados con los metadatos de su categoría
$query = "SELECT l.id, l.usuario_id, l.categoria_id, l.monto_limite,
                 c.nombre AS categoria_nombre, c.icono AS categoria_icono, c.color AS categoria_color
          FROM limites l
          INNER JOIN categorias c ON l.categoria_id = c.id
          WHERE l.usuario_id = ?
          ORDER BY c.nombre ASC";

$stmt = $conn->prepare($query);

if ($stmt) {
    // Vinculación del parámetro de búsqueda
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $limites = [];
    
    // Construcción del mapa de datos con tipado estricto para el Frontend
    while ($row = $result->fetch_assoc()) {
        $limites[] = [
            "id" => (int)$row['id'],
            "usuario_id" => (int)$row['usuario_id'],
            "categoria_id" => (int)$row['categoria_id'],
            "monto_limite" => (float)$row['monto_limite'],
            "categoria" => [
                "nombre" => $row['categoria_nombre'],
                "icono" => $row['categoria_icono'],
                "color" => $row['categoria_color']
            ]
        ];
    }
    
    http_response_code(200);
    echo json_encode([
        "success" => true,
        "limites" => $limites
    ]);
    
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