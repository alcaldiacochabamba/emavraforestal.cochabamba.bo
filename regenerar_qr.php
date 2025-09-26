<?php
// regenerar_qr.php - SCRIPT TEMPORAL PARA REGENERAR TODOS LOS QR
// EJECUTAR UNA SOLA VEZ Y LUEGO ELIMINAR ESTE ARCHIVO

error_reporting(E_ALL);
ini_set('display_errors', 1);

$conn = new mysqli("localhost", "root", "", "reforest", 3306);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

require_once 'phpqrcode/qrlib.php';

// Crear directorio si no existe
if (!file_exists('qr_codes/')) {
    mkdir('qr_codes/', 0777, true);
}

// Obtener todos los árboles
$sql = "SELECT id FROM arboles ORDER BY id ASC";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "<h2>Regenerando códigos QR...</h2>";
    
    while ($row = $result->fetch_assoc()) {
        $treeId = $row['id'];
        
        // Construir URL correcta
        $domain = $_SERVER['HTTP_HOST'];
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        
        // Obtener directorio base
        $scriptName = $_SERVER['SCRIPT_NAME']; // /proyecto/regenerar_qr.php
        $basePath = dirname($scriptName);       // /proyecto
        
        if ($basePath === '/' || $basePath === '\\' || $basePath === '.') {
            $basePath = '';
        }
        
        // URL final correcta
        $treeUrl = $protocol . "://" . $domain . $basePath . "/index.php?tree_id=" . $treeId;
        
        // Generar nombre del archivo QR
        $qrFilename = 'qr_codes/qr_' . $treeId . '.png';
        
        try {
            // Eliminar QR anterior si existe
            if (file_exists($qrFilename)) {
                unlink($qrFilename);
            }
            
            // Generar nuevo QR
            QRcode::png($treeUrl, $qrFilename, QR_ECLEVEL_L, 4);
            
            // Actualizar base de datos
            $updateStmt = $conn->prepare("UPDATE arboles SET qrUrl = ? WHERE id = ?");
            $updateStmt->bind_param("si", $qrFilename, $treeId);
            $updateStmt->execute();
            $updateStmt->close();
            
            echo "✅ QR regenerado para árbol ID $treeId -> $treeUrl<br>";
            
        } catch (Exception $e) {
            echo "❌ Error al regenerar QR para árbol ID $treeId: " . $e->getMessage() . "<br>";
        }
    }
    
    echo "<h3>✅ Proceso completado. Todos los QR han sido regenerados.</h3>";
    echo "<p><strong>IMPORTANTE:</strong> Elimina este archivo (regenerar_qr.php) después de usarlo.</p>";
} else {
    echo "No se encontraron árboles en la base de datos.";
}

$conn->close();
?>