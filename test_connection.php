<?php
$conn = new mysqli("mysql", "root", "rootpassword", "reforest", 3306);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

echo "✅ Conexión exitosa<br>";

$result = $conn->query("SHOW TABLES");
while ($row = $result->fetch_row()) {
    echo "Tabla encontrada: " . $row[0] . "<br>";
}

$conn->close();
?>