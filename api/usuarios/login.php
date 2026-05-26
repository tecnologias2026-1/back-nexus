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

// Captura y decodificación del cuerpo JSON enviado por el cliente
$input = json_decode(file_get_contents("php://input"), true);

// Validación de la existencia de datos obligatorios
if (empty($input['email']) || empty($input['password'])) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Datos incompletos. Se requiere email y password."]);
    exit();
}

// Sanitización elemental de la entrada de texto
$email = $conn->real_escape_string(trim($input['email']));
$password_plana = $input['password'];

// Consulta SQL que incluye los nuevos campos de personalización y gamificación
$query = "SELECT id, nombre, password, frecuencia_ingreso, ingreso_base, moneda_preferida, nivel, xp_actual, xp_siguiente_nivel, racha_dias, puntos_ranking 
          FROM usuarios 
          WHERE email = '$email'";

$result = $conn->query($query);

// Escenario: El correo electrónico no existe en la base de datos
if (!$result || $result->num_rows === 0) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "El correo electrónico no está registrado."]);
    exit();
}

// Extracción de los datos del usuario
$usuario = $result->fetch_assoc();

// Verificación segura de la contraseña mediante hash hash con la función nativa
if (!password_verify($password_plana, $usuario['password'])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Contraseña incorrecta."]);
    exit();
}

// Escenario Exitoso: Construcción de la respuesta JSON idéntica a usuarios.json
http_response_code(200);
echo json_encode([
    "success" => true,
    "message" => "Inicio de sesión exitoso.",
    "usuario" => [
        "id" => (int)$usuario['id'],
        "nombre" => $usuario['nombre'],
        "email" => $email,
        "frecuencia_ingreso" => $usuario['frecuencia_ingreso'],
        "ingreso_base" => (float)$usuario['ingreso_base'],
        "moneda_preferida" => $usuario['moneda_preferida'],
        "gamificacion" => [
            "nivel" => (int)$usuario['nivel'],
            "xp_actual" => (int)$usuario['xp_actual'],
            "xp_siguiente_nivel" => (int)$usuario['xp_siguiente_nivel'],
            "racha_dias" => (int)$usuario['racha_dias'],
            "puntos_ranking" => (int)$usuario['puntos_ranking']
        ]
    ]
]);

// Cierre de la conexión para optimizar recursos del servidor
$conn->close();
?>