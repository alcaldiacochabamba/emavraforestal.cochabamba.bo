<?php
session_start();


if (isset($_POST['submit'])) {
    $usuario = $_POST['usuario'];
    $contraseña = $_POST['contraseña'];



    if ($usuario == "admin" && $contraseña == "admin123") {
       
        $_SESSION['admin'] = true;
        header("Location: http://localhost/Proyecto%20Reforest/administrador.php");
        exit();
    } else {
        
        $error = "Credenciales inválidas";

    }
}
?>

