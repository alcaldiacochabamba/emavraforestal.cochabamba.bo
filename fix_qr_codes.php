<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'phpqrcode/qrlib.php';

$conn = new mysqli("localhost", "root", "", "reforest", 3306);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

echo "<h2>Limpiando y regenerando códigos QR...</h2>";

// 1. ELIMINAR ARCHIVOS QR EXISTENTES
echo "<h3>Paso 1: Eliminando archivos QR existentes</h3>";
$qrDir = 'qr_codes/';
if (is_dir($qrDir)) {
    $files = glob($qrDir . 'qr_*.png');
    foreach ($files as $file) {
        if (unlink($file)) {
            echo "✓ Eliminado: " . basename($file) . "<br>";
        } else {
            echo "✗ Error eliminando: " . basename($file) . "<br>";
        }
    }
} else {
    mkdir($qrDir, 0777, true);
    echo "✓ Directorio qr_codes/ creado<br>";
}

// 2. OBTENER CONFIGURACIÓN DEL SERVIDOR
$domain = $_SERVER['HTTP_HOST'];
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';

// Detectar la ruta base del proyecto
$scriptPath = $_SERVER['SCRIPT_NAME'];
$basePath = dirname($scriptPath);

if ($basePath === '/' || $basePath === '\\') {
    $basePath = '';
}

echo "<h3>Paso 2: Configuración detectada</h3>";
echo "Protocolo: $protocol<br>";
echo "Dominio: $domain<br>";
echo "Ruta base: '$basePath'<br>";

// 3. REGENERAR TODOS LOS QR
echo "<h3>Paso 3: Regenerando códigos QR</h3>";
$result = $conn->query("SELECT id FROM arboles ORDER BY id");

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $id = $row['id'];
        
        // Generar URL correcta
        $treeUrl = $protocol . "://" . $domain . $basePath . "/index.php?tree_id=" . $id . "#map";
        
        // Generar QR
        $qrFilename = 'qr_codes/qr_' . $id . '.png';
        
        try {
            QRcode::png($treeUrl, $qrFilename, QR_ECLEVEL_L, 4);
            
            // Actualizar base de datos
            $updateStmt = $conn->prepare("UPDATE arboles SET qrUrl = ? WHERE id = ?");
            if ($updateStmt) {
                $updateStmt->bind_param("si", $qrFilename, $id);
                $updateStmt->execute();
                $updateStmt->close();
            }
            
            echo "✓ QR regenerado para árbol #$id: <code>$treeUrl</code><br>";
            
        } catch (Exception $e) {
            echo "✗ Error generando QR para árbol #$id: " . $e->getMessage() . "<br>";
        }
    }
    
    echo "<br><strong>Proceso completado!</strong><br>";
    echo "<br><em>Ahora puedes eliminar este archivo (fix_qr_codes.php)</em>";
    
} else {
    echo "No se encontraron árboles en la base de datos.<br>";
}

$conn->close();
?>