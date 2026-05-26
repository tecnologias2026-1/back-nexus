<?php
// 1. Cabeceras para permitir que el frontend hable con el backend
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// 2. Limpiar cualquier error previo que esté ensuciando la respuesta
if (ob_get_length()) ob_end_clean();

// 3. Si es una petición de preflight de CORS, terminar aquí
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit;
}

// Capturar los datos enviados en el cuerpo de la solicitud (formato JSON)
$input = json_decode(file_get_contents("php://input"), true);

// Validación estricta de campos obligatorios
if (empty($input['nombre']) || empty($input['email']) || empty($input['password'])) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "Campos obligatorios incompletos (nombre, email, password)"]);
    exit();
}

// Sanitización y preparación de las variables de entrada
$nombre = trim($input['nombre']);
$email = trim($input['email']);
$password_plana = $input['password'];

// Captura de campos financieros con valores predeterminados alineados al Frontend
$ingreso_base = isset($input['ingreso_base']) ? floatval($input['ingreso_base']) : 0.00;
$frecuencia = isset($input['frecuencia_ingreso']) ? trim($input['frecuencia_ingreso']) : 'mensual';
$moneda_preferida = isset($input['moneda_preferida']) ? trim($input['moneda_preferida']) : 'COP';

// Encriptación de la contraseña con el algoritmo estándar BCRYPT
$password_hash = password_hash($password_plana, PASSWORD_BCRYPT);
$fecha_registro = date('Y-m-d');

// Sentencia preparada para garantizar la seguridad del sistema
$query = "INSERT INTO usuarios (nombre, email, password, fecha_registro, ingreso_base, frecuencia_ingreso, moneda_preferida) 
          VALUES (?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($query);

if ($stmt) {
    // Vincular los parámetros (s = string, d = double)
    $stmt->bind_param("ssssdss", $nombre, $email, $password_hash, $fecha_registro, $ingreso_base, $frecuencia, $moneda_preferida);
    
    if ($stmt->execute()) {
        http_response_code(201); // 201 Created
        echo json_encode([
            "success" => true,
            "message" => "Usuario registrado exitosamente en el sistema Nexus",
            "usuario_id" => $conn->insert_id
        ]);
    } else {
        http_response_code(400);
        // Manejo específico para el caso en que el correo ya exista (Clave duplicada: Error 1062)
        if ($conn->errno === 1062) {
            echo json_encode([
                "success" => false,
                "error" => "El correo electrónico ya se encuentra registrado en el sistema."
            ]);
        } else {
            echo json_encode([
                "success" => false,
                "error" => "Error al almacenar el registro: " . $conn->error
            ]);
        }
    }
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