<?php
session_start();

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "reforest";
$port = "3306";

require_once 'modelo.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validar y sanitizar los datos recibidos del formulario
    $nombre = htmlspecialchars(trim($_POST["nombre"]));
    $apellido_paterno = htmlspecialchars(trim($_POST["apellido_paterno"]));
    $apellido_materno = htmlspecialchars(trim($_POST["apellido_materno"]));
    $fecha_nacimiento = htmlspecialchars(trim($_POST["fecha_nacimiento"]));
    $genero = htmlspecialchars(trim($_POST["genero"]));
    $telefono = htmlspecialchars(trim($_POST["telefono"]));
    $direccion = htmlspecialchars(trim($_POST["direccion"]));
    $correo = filter_var(trim($_POST["correo"]), FILTER_VALIDATE_EMAIL);
    $numero_identificacion = htmlspecialchars(trim($_POST["numero_identificacion"]));
    $usuario = htmlspecialchars(trim($_POST["usuario"]));
    $contrasena = htmlspecialchars($_POST["contrasena"]);
    $contrasena_hashed = password_hash($contrasena, PASSWORD_DEFAULT);

    // Manejo de errores de validación
    if (!$correo) {
        echo "El correo electrónico no es válido.";
        exit();
    }

    // Manejo de archivos
    $foto_perfil = '';
    $documentos_identificacion = '';

    // Ruta del directorio de subidas
    $upload_dir = __DIR__ . '/uploads/';

    // Crear directorio 'uploads' si no existe
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // Subir foto de perfil
    if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] == UPLOAD_ERR_OK) {
        $foto_perfil = uniqid('foto_') . '_' . basename($_FILES['foto_perfil']['name']);
        $foto_perfil_path = $upload_dir . $foto_perfil;
        if (!move_uploaded_file($_FILES['foto_perfil']['tmp_name'], $foto_perfil_path)) {
            echo "Error al mover el archivo de foto de perfil.";
            exit();
        }
    }

    // Subir documentos de identificación
    if (isset($_FILES['documentos_identificacion']) && $_FILES['documentos_identificacion']['error'] == UPLOAD_ERR_OK) {
        $documentos_identificacion = uniqid('doc_') . '_' . basename($_FILES['documentos_identificacion']['name']);
        $documentos_identificacion_path = $upload_dir . $documentos_identificacion;
        if (!move_uploaded_file($_FILES['documentos_identificacion']['tmp_name'], $documentos_identificacion_path)) {
            echo "Error al mover el archivo de documentos de identificación.";
            exit();
        }
    }

    try {
        // Realiza la conexión a la base de datos con PDO
        $conn = new PDO("mysql:host=$servername;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Prepara la consulta para evitar inyección SQL
        $sql = "INSERT INTO usuarios_registrados 
                (nombre, apellido_paterno, apellido_materno, fecha_nacimiento, genero, telefono, direccion, correo, numero_identificacion, foto_perfil, documentos_identificacion, usuario, contrasena) 
                VALUES 
                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $nombre,
            $apellido_paterno,
            $apellido_materno,
            $fecha_nacimiento,
            $genero,
            $telefono,
            $direccion,
            $correo,
            $numero_identificacion,
            $foto_perfil,
            $documentos_identificacion,
            $usuario,
            $contrasena_hashed
        ]);

        // Redirige después del registro exitoso
        header("Location: index.php");
        exit();
    } catch (PDOException $e) {
        // Maneja errores y registra detalles
        error_log("Error al registrar usuario: " . $e->getMessage());
        echo "Error al registrar usuario. Por favor, inténtalo nuevamente.";
    } finally {
        // Cierra la conexión
        $conn = null;
    }
} else {
    // Respuesta para solicitudes no válidas
    echo "Acceso no autorizado.";
    exit();
}
?>
