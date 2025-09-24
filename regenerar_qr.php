<?php
// Script para limpiar todos los códigos QR existentes

echo "=== LIMPIEZA DE CÓDIGOS QR ===\n";

$qr_directory = 'qr_codes/';

// Verificar si el directorio existe
if (!is_dir($qr_directory)) {
    echo "El directorio qr_codes/ no existe.\n";
    exit;
}

// Obtener todos los archivos QR
$files = glob($qr_directory . 'qr_*.png');
$deleted_count = 0;

if (empty($files)) {
    echo "No se encontraron archivos QR para eliminar.\n";
} else {
    echo "Encontrados " . count($files) . " archivos QR para eliminar.\n\n";
    
    foreach($files as $file) {
        if(is_file($file)) {
            if(unlink($file)) {
                echo "✓ Eliminado: " . basename($file) . "\n";
                $deleted_count++;
            } else {
                echo "✗ Error al eliminar: " . basename($file) . "\n";
            }
        }
    }
}

echo "\n=== RESUMEN ===\n";
echo "Total archivos QR eliminados: $deleted_count\n";

// Opcional: Limpiar también las referencias en la base de datos
$limpiar_bd = true; // Cambia a false si no quieres limpiar la BD

if ($limpiar_bd) {
    $conn = new mysqli("localhost", "root", "", "reforest", 3306);
    
    if ($conn->connect_error) {
        echo "Error de conexión a la BD: " . $conn->connect_error . "\n";
    } else {
        $sql = "UPDATE arboles SET qrUrl = NULL";
        if ($conn->query($sql)) {
            echo "✓ Referencias QR limpiadas en la base de datos\n";
        } else {
            echo "✗ Error al limpiar referencias en la BD: " . $conn->error . "\n";
        }
        $conn->close();
    }
}

echo "\n=== LIMPIEZA COMPLETADA ===\n";
?>