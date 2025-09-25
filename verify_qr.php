<?php
// Crear verify_qr.php
$conn = new mysqli("localhost", "root", "", "reforest", 3306);
$result = $conn->query("SELECT id, qrUrl FROM arboles WHERE id = 7");
$row = $result->fetch_assoc();

echo "ID: " . $row['id'] . "<br>";
echo "Ruta QR en BD: " . $row['qrUrl'] . "<br>";
echo "Archivo existe: " . (file_exists($row['qrUrl']) ? 'SI' : 'NO') . "<br>";
echo "Fecha modificación: " . date("Y-m-d H:i:s", filemtime($row['qrUrl'])) . "<br>";
?>