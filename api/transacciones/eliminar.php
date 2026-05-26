<?php
// Incluir helper centralizado de CORS (DEBE ser lo primero)
require_once __DIR__ . '/../../cors-helper.php';

// Verificación estricta del método HTTP. Se exige DELETE para la destrucción de recursos
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Método no permitido. Se requiere DELETE."]);
    exit();
}

// Inclusión de la conexión centralizada a la base de datos
require_once __DIR__ . '/../../database/connection.php';

// Captura de parámetros de la URL (?id=X)
if (empty($_GET['id'])) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Falta el parámetro obligatorio: id de la transacción."]);
    exit();
}

$transaccion_id = (int)$_GET['id'];

// Sentencia preparada para verificar primero si la transacción existe
$check_query = "SELECT id FROM transacciones WHERE id = ?";
$stmt_check = $conn->prepare($check_query);
$stmt_check->bind_param("i", $transaccion_id);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

if ($result_check->num_rows === 0) {
    http_response_code(404); // 404 Not Found
    // Limpiamos cualquier salida en blanco extraña antes de mandar el JSON
    if (ob_get_length()) ob_clean(); 
    echo json_encode(["success" => false, "message" => "La transacción con el ID proporcionado no existe o ya fue eliminada."]);
    $stmt_check->close();
    exit();
}
$stmt_check->close();

// Sentencia preparada para ejecutar el borrado físico del registro
$delete_query = "DELETE FROM transacciones WHERE id = ?";
$stmt_delete = $conn->prepare($delete_query);

if ($stmt_delete) {
    $stmt_delete->bind_param("i", $transaccion_id);
    
    if ($stmt_delete->execute()) {
        http_response_code(200); // 200 OK
        
        // Limpiamos el búfer de salida para garantizar que solo se envíe nuestro JSON limpio
        if (ob_get_length()) ob_clean(); 
        
        echo json_encode([
            "success" => true,
            "message" => "Transacción eliminada exitosamente del sistema Nexus."
        ]);
        $stmt_delete->close();
        $conn->close();
        exit(); // Forzamos el cierre inmediato del proceso para que entregue la respuesta
    } else {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "error" => "Error al ejecutar la eliminación: " . $conn->error
        ]);
    }
    $stmt_delete->close();
} else {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => "Error en la preparación de la consulta de eliminación: " . $conn->error
    ]);
}

// Cierre de la conexión por si acaso cae en un flujo de error
$conn->close();
?>