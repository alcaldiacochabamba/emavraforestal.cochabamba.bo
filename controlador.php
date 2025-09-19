<?php
require_once 'modelo.php';

// Verifica si se recibieron datos del formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Datos del primer formulario
    $nombre = $_POST["nombre"];
    $fecha_nacimiento = $_POST["fecha_nacimiento"];
    $genero = $_POST["genero"];
    $telefono = $_POST["telefono"];
    $direccion = $_POST["direccion"];
    $correo = $_POST["correo"];

    // Datos del segundo formulario
    $numero_identificacion = $_POST["numero_identificacion"];
    // Manejo de la foto de perfil, puedes guardar la ruta en la base de datos
    $foto_perfil = $_FILES['foto_perfil']['name'] ?? '';

    // Datos del tercer formulario
    $documentos_identificacion = $_FILES['documentos_identificacion']['name'] ?? '';
    $certificado_nacimiento = $_FILES['certificado_nacimiento']['name'] ?? '';

    // Realiza la conexión a la base de datos con el puerto especificado
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "reforest";
    $port = "3306"; // Por ejemplo, 3306 es el puerto predeterminado para MySQL

    try {
        $conn = new PDO("mysql:host=$servername;port=$port;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Utiliza consultas preparadas para evitar inyección SQL
        $sql = "INSERT INTO voluntarios(nombre, fecha_nacimiento, genero, telefono, direccion, correo, numero_identificacion, foto_perfil, documentos_identificacion, certificado_nacimiento) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$nombre, $fecha_nacimiento, $genero, $telefono, $direccion, $correo, $numero_identificacion, $foto_perfil, $documentos_identificacion, $certificado_nacimiento]);

        // Redirige a la página de menú, incluyendo la URL anterior como parámetro
        header("Location: http://localhost/Proyecto%20Reforest/administrador.php");
        exit();
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    } finally {
        // Cierra la conexión
        $conn = null;
    }
} else {
    // Maneja el caso en el que no se recibieron datos del formulario de manera adecuada
    echo "Acceso no autorizado";
}
?>

