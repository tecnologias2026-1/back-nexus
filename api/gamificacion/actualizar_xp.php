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

// Captura del cuerpo JSON
$input = json_decode(file_get_contents("php://input"), true);

// Validación de campos obligatorios
if (empty($input['usuario_id']) || !isset($input['xp_ganada'])) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Datos incompletos. Se requiere usuario_id y xp_ganada."]);
    exit();
}

$usuario_id = (int)$input['usuario_id'];
$xp_ganada = (int)$input['xp_ganada'];

// 1. Obtener las métricas directamente de la tabla 'usuarios'
$query_select = "SELECT nivel, xp_actual, xp_siguiente_nivel, puntos_ranking FROM usuarios WHERE id = ?";
$stmt_select = $conn->prepare($query_select);
$stmt_select->bind_param("i", $usuario_id);
$stmt_select->execute();
$result = $stmt_select->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(["success" => false, "message" => "Usuario no encontrado en el sistema."]);
    $stmt_select->close();
    exit();
}

$current = $result->fetch_assoc();
$stmt_select->close();

$nivel = (int)$current['nivel'];
$xp_actual = (int)$current['xp_actual'];
$xp_siguiente_nivel = (int)$current['xp_siguiente_nivel'];
$puntos_ranking = (int)$current['puntos_ranking'];

// 2. Lógica de incremento de XP y cálculo de subida de nivel (Level Up)
$xp_actual += $xp_ganada;
$puntos_ranking += $xp_ganada; // El acumulado histórico siempre sube
$subio_nivel = false;

// Bucle dinámico por si la XP ganada supera varios niveles de un solo golpe
while ($xp_actual >= $xp_siguiente_nivel) {
    $xp_actual -= $xp_siguiente_nivel;
    $nivel++;
    // Curva de dificultad: cada nivel exige 200 XP más que el anterior
    $xp_siguiente_nivel += 200; 
    $subio_nivel = true;
}

// 3. Actualizar los nuevos valores en la tabla 'usuarios'
$query_update = "UPDATE usuarios 
                 SET nivel = ?, xp_actual = ?, xp_siguiente_nivel = ?, puntos_ranking = ? 
                 WHERE id = ?";

$stmt_update = $conn->prepare($query_update);

if ($stmt_update) {
    $stmt_update->bind_param("iiiii", $nivel, $xp_actual, $xp_siguiente_nivel, $puntos_ranking, $usuario_id);
    
    if ($stmt_update->execute()) {
        http_response_code(200);
        echo json_encode([
            "success" => true,
            "message" => $subio_nivel ? "¡Felicidades! Subiste de nivel en Nexus." : "XP e historial de ranking actualizados con éxito.",
            "subio_nivel" => $subio_nivel,
            "gamificacion" => [
                "nivel" => $nivel,
                "xp_actual" => $xp_actual,
                "xp_siguiente_nivel" => $xp_siguiente_nivel,
                "puntos_ranking" => $puntos_ranking
            ]
        ]);
    } else {
        http_response_code(400);
        echo json_encode(["success" => false, "error" => "Error al actualizar los datos de usuario: " . $conn->error]);
    }
    $stmt_update->close();
} else {
    http_response_code(500);
    echo json_encode(["success" => false, "error" => "Error en la preparación de la actualización: " . $conn->error]);
}

$conn->close();
?>