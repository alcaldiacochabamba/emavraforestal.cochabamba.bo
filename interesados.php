<?php
require_once 'modelo.php';


if ($_SERVER["REQUEST_METHOD"] == "POST") {
   
    if (isset($_POST["email"])) {
        // Datos del formulario
        $email = $_POST["email"];

      
        $servername = "localhost";
        $username = "root";
        $password = "";
        $dbname = "reforest";
        $port = "3306"; 

        try {
            $conn = new PDO("mysql:host=$servername;port=$port;dbname=$dbname", $username, $password);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            
            $sql = "INSERT INTO usuarios (email) VALUES (?)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$email]);

           
            header("Location: http://localhost/Proyecto%20Reforest/index.php");
            exit();
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
        } finally {
            
            $conn = null;
        }
    } else {
       
        echo "Error: No se proporcionó un correo electrónico.";
    }
} else {
   
    echo "Acceso no autorizado";
}
?>
