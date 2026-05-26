<?php
// Incluir helper centralizado de CORS (DEBE ser lo primero)
require_once __DIR__ . '/../../cors-helper.php';

// Validación estricta del método HTTP. Para la lectura de datos se exige GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Método no permitido. Se requiere GET."]);
    exit();
}

// Inclusión de la conexión centralizada a la base de datos
require_once __DIR__ . '/../../database/connection.php';

// Consulta para obtener la totalidad de las categorías ordenadas alfabéticamente
$query = "SELECT id, nombre, tipo, icono, color, sistema FROM categorias ORDER BY nombre ASC";
$result = $conn->query($query);

if ($result) {
    $categorias = [];
    
    // Construcción del arreglo asociativo con tipado de datos corregido
    while ($row = $result->fetch_assoc()) {
        $categorias[] = [
            "id" => (int)$row['id'],
            "nombre" => $row['nombre'],
            "tipo" => $row['tipo'],
            "icono" => $row['icono'],
            "color" => $row['color'],
            "sistema" => (bool)$row['sistema']
        ];
    }
    
    http_response_code(200);
    echo json_encode([
        "success" => true,
        "categorias" => $categorias
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => "Error al consultar las categorías: " . $conn->error
    ]);
}

// Cierre de la conexión
$conn->close();
?>