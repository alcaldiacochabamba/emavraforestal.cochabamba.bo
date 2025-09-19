<?php
require_once 'modelo.php'; // Incluye el archivo modelo.php que contiene la clase DB

$usuario = $_POST['usuario'];
$contrasena = $_POST['contrasena'];

try {
    
    $sql = "SELECT * FROM usuarios_registrados WHERE usuario = :usuario";

   
    $stmt = $_DB->pdo->prepare($sql);


    $stmt->bindParam(':usuario', $usuario, PDO::PARAM_STR);


    $stmt->execute();


    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        // Verificar contraseña
        if (password_verify($contrasena, $row['contrasena'])) {
            // Contraseña correcta, redirigir al usuario
            header("Location: administrador.php");
            exit();
        } else {
            // Contraseña incorrecta
            header("Location: administrador.php");
            exit();
        }
    } else {
        // Usuario no encontrado
        header("Location: index.php?error=1");
        exit();
    }
} catch (PDOException $e) {
 
    die("Error en la consulta: " . $e->getMessage());
}
?>
