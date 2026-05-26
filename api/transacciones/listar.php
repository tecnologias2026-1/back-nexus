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

// Casteo explícito a tipo entero para blindar la consulta
$usuario_id = (int)$_GET['usuario_id'];

// Consulta estructurada con JOIN para unificar la transacción y los metadatos de su categoría
$query = "SELECT t.id, t.usuario_id, t.categoria_id, t.tipo, t.nombre, t.valor, t.fecha, t.recurrente, t.fijo,
                 c.nombre AS categoria_nombre, c.icono AS categoria_icono, c.color AS categoria_color
          FROM transacciones t
          INNER JOIN categorias c ON t.categoria_id = c.id
          WHERE t.usuario_id = ?
          ORDER BY t.fecha DESC, t.id DESC";

$stmt = $conn->prepare($query);

if ($stmt) {
    // Vinculación del parámetro de búsqueda
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $transacciones = [];
    
    // Construcción del mapa de datos con tipado estricto
    while ($row = $result->fetch_assoc()) {
        $transacciones[] = [
            "id" => (int)$row['id'],
            "usuario_id" => (int)$row['usuario_id'],
            "categoria_id" => (int)$row['categoria_id'],
            "tipo" => $row['tipo'],
            "nombre" => $row['nombre'],
            "valor" => (float)$row['valor'],
            "fecha" => $row['fecha'],
            "recurrente" => (bool)$row['recurrente'],
            "fijo" => (bool)$row['fijo'],
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
        "transacciones" => $transacciones
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