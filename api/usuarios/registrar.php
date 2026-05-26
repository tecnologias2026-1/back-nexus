<?php
// Incluir helper centralizado de CORS (DEBE ser lo primero)
require_once __DIR__ . '/../../cors-helper.php';

// Inclusión del archivo de configuración de la base de datos
require_once __DIR__ . '/../../database/connection.php';

// Verificar que la petición se realice mediante el método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["error" => "Método no permitido. Se requiere POST"]);
    exit;
}

// Capturar los datos enviados en el cuerpo de la solicitud (formato JSON)
$input = json_decode(file_get_contents("php://input"), true);

// Validación de campos obligatorios para el modelo de datos
if (empty($input['nombre']) || empty($input['email']) || empty($input['password'])) {
    echo json_encode(["error" => "Campos obligatorios incompletos (nombre, email, password)"]);
    exit;
}

// Sanitización de strings para prevenir fallos en las consultas SQL
$nombre = $conn->real_escape_string($input['nombre']);
$email = $conn->real_escape_string($input['email']);
$password_plana = $input['password'];
$ingreso_base = isset($input['ingreso_base']) ? floatval($input['ingreso_base']) : 0.00;
$frecuencia = isset($input['frecuencia_ingreso']) ? $conn->real_escape_string($input['frecuencia_ingreso']) : 'mensual';

// Encriptación de la contraseña con el algoritmo estándar BCRYPT
$password_hash = password_hash($password_plana, PASSWORD_BCRYPT);
$fecha_registro = date('Y-m-d');

// Sentencia de inserción adaptada a la tabla usuarios
$query = "INSERT INTO usuarios (nombre, email, password, fecha_registro, ingreso_base, frecuencia_ingreso) 
          VALUES ('$nombre', '$email', '$password_hash', '$fecha_registro', $ingreso_base, '$frecuencia')";

if ($conn->query($query)) {
    echo json_encode([
        "success" => true,
        "message" => "Usuario registrado exitosamente en el sistema Nexus",
        "usuario_id" => $conn->insert_id
    ]);
} else {
    echo json_encode([
        "success" => false,
        "error" => "Error al almacenar el registro: " . $conn->error
    ]);
}

$conn->close();