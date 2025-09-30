<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('America/La_Paz');

// Configurar límites
ini_set('upload_max_filesize', '15M');
ini_set('post_max_size', '20M');

// Crear directorios si no existen
$directories = ['uploads/trees', 'uploads/pdfs', 'exports'];
foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
}

// Conexión
$conn = new mysqli("mysql", "root", "rootpassword", "reforest", 3306);
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}
$conn->query("SET time_zone = '-04:00'");

// ===============================================
// EXPORTAR DATOS
// ===============================================
if (isset($_GET['export'])) {
    $format = $_GET['export'];
    
    // Obtener todos los árboles
    $result = $conn->query("SELECT * FROM arboles ORDER BY id ASC");
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    switch($format) {
        case 'zip':
            // Crear archivo comprimido usando TAR (disponible en todos los servidores)
            $timestamp = date('Y-m-d_His');
            $tempDir = 'exports/temp_' . $timestamp;
            $archivoFinal = 'arboles_completo_' . $timestamp . '.tar.gz';
            $archivoPath = 'exports/' . $archivoFinal;
            
            // Crear directorio temporal
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0777, true);
            }
            
            // Crear subdirectorios
            mkdir($tempDir . '/fotos', 0777, true);
            mkdir($tempDir . '/pdfs', 0777, true);
            mkdir($tempDir . '/qrcodes', 0777, true);
            
            // Crear CSV
            $csvFile = fopen($tempDir . '/datos_arboles.csv', 'w');
            fprintf($csvFile, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM
            if (!empty($data)) {
                fputcsv($csvFile, array_keys($data[0]));
                foreach ($data as $row) {
                    fputcsv($csvFile, $row);
                }
            }
            fclose($csvFile);
            
            // Crear JSON
            file_put_contents(
                $tempDir . '/datos_arboles.json',
                json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            );
            
            // Contadores
            $fotosAgregadas = 0;
            $pdfsAgregados = 0;
            $qrsAgregados = 0;
            
            // Copiar archivos de cada árbol
            foreach ($data as $arbol) {
                $codigoSeguro = preg_replace('/[^a-zA-Z0-9_-]/', '_', $arbol['codigo_arbol']);
                
                // Copiar foto
                if (!empty($arbol['fotoUrl']) && file_exists($arbol['fotoUrl'])) {
                    $extension = pathinfo($arbol['fotoUrl'], PATHINFO_EXTENSION);
                    $destino = $tempDir . '/fotos/' . $codigoSeguro . '_foto.' . $extension;
                    if (copy($arbol['fotoUrl'], $destino)) {
                        $fotosAgregadas++;
                    }
                }
                
                // Copiar PDF
                if (!empty($arbol['pdfUrl']) && file_exists($arbol['pdfUrl'])) {
                    $destino = $tempDir . '/pdfs/' . $codigoSeguro . '_info.pdf';
                    if (copy($arbol['pdfUrl'], $destino)) {
                        $pdfsAgregados++;
                    }
                }
                
                // Copiar QR
                $qrPath = 'qrcodes/' . $arbol['id'] . '.png';
                if (file_exists($qrPath)) {
                    $destino = $tempDir . '/qrcodes/' . $codigoSeguro . '_qr.png';
                    if (copy($qrPath, $destino)) {
                        $qrsAgregados++;
                    }
                }
            }
            
            // Crear README
            $readme = "EXPORTACIÓN COMPLETA DE ÁRBOLES - EMAVRA\n";
            $readme .= "=========================================\n\n";
            $readme .= "Fecha de exportación: " . date('Y-m-d H:i:s') . "\n";
            $readme .= "Total de árboles: " . count($data) . "\n";
            $readme .= "Fotos incluidas: " . $fotosAgregadas . "\n";
            $readme .= "PDFs incluidos: " . $pdfsAgregados . "\n";
            $readme .= "Códigos QR incluidos: " . $qrsAgregados . "\n\n";
            $readme .= "ESTRUCTURA DEL ARCHIVO:\n";
            $readme .= "----------------------\n";
            $readme .= "- datos_arboles.csv: Datos completos en formato CSV\n";
            $readme .= "- datos_arboles.json: Datos completos en formato JSON\n";
            $readme .= "- fotos/: Fotografías de los árboles\n";
            $readme .= "- pdfs/: Documentos PDF informativos\n";
            $readme .= "- qrcodes/: Códigos QR de cada árbol\n\n";
            $readme .= "Los archivos están nombrados con el código del árbol para fácil identificación.\n";
            $readme .= "\nNOTA: Este es un archivo TAR.GZ. Se puede abrir con:\n";
            $readme .= "- Windows: 7-Zip, WinRAR, o Windows 11 nativo\n";
            $readme .= "- Mac: Doble clic (soporte nativo)\n";
            $readme .= "- Linux: tar -xzf nombre_archivo.tar.gz\n";
            
            file_put_contents($tempDir . '/LEEME.txt', $readme);
            
            // Crear archivo TAR.GZ usando comando del sistema
            $currentDir = getcwd();
            chdir('exports');
            
            $tempDirBasename = basename($tempDir);
            $comando = "tar -czf " . escapeshellarg($archivoFinal) . " " . escapeshellarg($tempDirBasename) . " 2>&1";
            exec($comando, $output, $returnCode);
            
            chdir($currentDir);
            
            // Función para limpiar directorios
            function eliminarDirectorio($dir) {
                if (!is_dir($dir)) return;
                $files = array_diff(scandir($dir), ['.', '..']);
                foreach ($files as $file) {
                    $path = $dir . '/' . $file;
                    is_dir($path) ? eliminarDirectorio($path) : unlink($path);
                }
                rmdir($dir);
            }
            
            // Verificar si se creó el archivo
            if (file_exists($archivoPath) && filesize($archivoPath) > 0) {
                // Descargar el archivo
                header('Content-Type: application/gzip');
                header('Content-Disposition: attachment; filename="' . $archivoFinal . '"');
                header('Content-Length: ' . filesize($archivoPath));
                readfile($archivoPath);
                
                // Limpiar archivos temporales
                eliminarDirectorio($tempDir);
                unlink($archivoPath);
                exit;
            } else {
                // Si falló, intentar crear ZIP manualmente con PHP puro
                eliminarDirectorio($tempDir);
                
                // Último recurso: crear un archivo tar sin comprimir
                $tarFile = 'exports/arboles_' . $timestamp . '.tar';
                $tempDir2 = 'exports/temp2_' . $timestamp;
                
                if (!is_dir($tempDir2)) {
                    mkdir($tempDir2, 0777, true);
                    mkdir($tempDir2 . '/fotos', 0777, true);
                    mkdir($tempDir2 . '/pdfs', 0777, true);
                    mkdir($tempDir2 . '/qrcodes', 0777, true);
                }
                
                // Recrear archivos
                $csvFile = fopen($tempDir2 . '/datos_arboles.csv', 'w');
                fprintf($csvFile, chr(0xEF).chr(0xBB).chr(0xBF));
                if (!empty($data)) {
                    fputcsv($csvFile, array_keys($data[0]));
                    foreach ($data as $row) {
                        fputcsv($csvFile, $row);
                    }
                }
                fclose($csvFile);
                
                file_put_contents($tempDir2 . '/datos_arboles.json', json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                file_put_contents($tempDir2 . '/LEEME.txt', $readme);
                
                foreach ($data as $arbol) {
                    $codigoSeguro = preg_replace('/[^a-zA-Z0-9_-]/', '_', $arbol['codigo_arbol']);
                    
                    if (!empty($arbol['fotoUrl']) && file_exists($arbol['fotoUrl'])) {
                        $extension = pathinfo($arbol['fotoUrl'], PATHINFO_EXTENSION);
                        copy($arbol['fotoUrl'], $tempDir2 . '/fotos/' . $codigoSeguro . '_foto.' . $extension);
                    }
                    
                    if (!empty($arbol['pdfUrl']) && file_exists($arbol['pdfUrl'])) {
                        copy($arbol['pdfUrl'], $tempDir2 . '/pdfs/' . $codigoSeguro . '_info.pdf');
                    }
                    
                    $qrPath = 'qrcodes/' . $arbol['id'] . '.png';
                    if (file_exists($qrPath)) {
                        copy($qrPath, $tempDir2 . '/qrcodes/' . $codigoSeguro . '_qr.png');
                    }
                }
                
                // Intentar tar sin comprimir
                chdir('exports');
                $comando = "tar -cf " . escapeshellarg(basename($tarFile)) . " " . escapeshellarg(basename($tempDir2)) . " 2>&1";
                exec($comando, $output2, $returnCode2);
                chdir($currentDir);
                
                if (file_exists($tarFile) && filesize($tarFile) > 0) {
                    header('Content-Type: application/x-tar');
                    header('Content-Disposition: attachment; filename="arboles_' . $timestamp . '.tar"');
                    header('Content-Length: ' . filesize($tarFile));
                    readfile($tarFile);
                    
                    eliminarDirectorio($tempDir2);
                    unlink($tarFile);
                    exit;
                } else {
                    eliminarDirectorio($tempDir2);
                    die("Error: No se pudo crear el archivo comprimido. Por favor, contacta al administrador del sistema para habilitar 'tar' o 'zip'.");
                }
            }
            break;
            
        case 'csv':
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="arboles_' . date('Y-m-d_His') . '.csv"');
            
            $output = fopen('php://output', 'w');
            
            // BOM para UTF-8
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Encabezados
            if (!empty($data)) {
                fputcsv($output, array_keys($data[0]));
                foreach ($data as $row) {
                    fputcsv($output, $row);
                }
            }
            fclose($output);
            exit;
            
        case 'json':
            header('Content-Type: application/json; charset=utf-8');
            header('Content-Disposition: attachment; filename="arboles_' . date('Y-m-d_His') . '.json"');
            
            echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;
            
        case 'sql':
            header('Content-Type: text/plain; charset=utf-8');
            header('Content-Disposition: attachment; filename="arboles_backup_' . date('Y-m-d_His') . '.sql"');
            
            echo "-- Backup de la tabla arboles\n";
            echo "-- Fecha: " . date('Y-m-d H:i:s') . "\n\n";
            
            // Estructura de la tabla
            $create = $conn->query("SHOW CREATE TABLE arboles");
            if ($row = $create->fetch_assoc()) {
                echo $row['Create Table'] . ";\n\n";
            }
            
            // Datos
            foreach ($data as $row) {
                $values = [];
                foreach ($row as $value) {
                    if ($value === null) {
                        $values[] = 'NULL';
                    } else {
                        $values[] = "'" . $conn->real_escape_string($value) . "'";
                    }
                }
                echo "INSERT INTO arboles VALUES (" . implode(', ', $values) . ");\n";
            }
            exit;
            
        case 'excel':
            header('Content-Type: application/vnd.ms-excel; charset=utf-8');
            header('Content-Disposition: attachment; filename="arboles_' . date('Y-m-d_His') . '.xls"');
            
            echo "\xEF\xBB\xBF"; // BOM
            echo "<table border='1'>";
            
            // Encabezados
            if (!empty($data)) {
                echo "<tr>";
                foreach (array_keys($data[0]) as $header) {
                    echo "<th>" . htmlspecialchars($header) . "</th>";
                }
                echo "</tr>";
                
                // Datos
                foreach ($data as $row) {
                    echo "<tr>";
                    foreach ($row as $cell) {
                        echo "<td>" . htmlspecialchars($cell ?? '') . "</td>";
                    }
                    echo "</tr>";
                }
            }
            echo "</table>";
            exit;
    }
}

// ===============================================
// PROCESAR ACTUALIZACIÓN (POST)
// ===============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $conn->begin_transaction();
    
    try {
        $id = intval($_POST['id']);
        
        if ($id <= 0) {
            throw new Exception("ID inválido");
        }
        
        // Obtener datos actuales
        $stmt = $conn->prepare("SELECT fotoUrl, pdfUrl FROM arboles WHERE id = ?");
        if (!$stmt) {
            throw new Exception("Error preparando consulta: " . $conn->error);
        }
        
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("Árbol no encontrado");
        }
        
        $current = $result->fetch_assoc();
        $stmt->close();
        
        // Validar y recopilar datos
        $campos = [
            'especie' => trim($_POST['especie'] ?? ''),
            'nombre_comun' => trim($_POST['nombre_comun'] ?? ''),
            'codigo_arbol' => trim($_POST['codigo_arbol'] ?? ''),
            'estado' => trim($_POST['estado'] ?? ''),
            'inspector' => trim($_POST['inspector'] ?? ''),
            'propiedad' => trim($_POST['propiedad'] ?? ''),
            'otb' => trim($_POST['otb'] ?? ''),
            'nombre_area_verde' => trim($_POST['nombre_area_verde'] ?? ''),
            'estado_fitosanitario' => trim($_POST['estado_fitosanitario'] ?? '')
        ];
        
        // Validar campos obligatorios
        $requeridos = ['especie', 'nombre_comun', 'codigo_arbol', 'estado'];
        foreach ($requeridos as $campo) {
            if (empty($campos[$campo])) {
                throw new Exception("El campo {$campo} es obligatorio");
            }
        }
        
        $edad = max(0, intval($_POST['edad'] ?? 0));
        $altura = max(0, floatval($_POST['altura'] ?? 0));
        $diametroTronco = max(0, floatval($_POST['diametroTronco'] ?? 0));
        $diametro_copa = max(0, floatval($_POST['diametro_copa'] ?? 0));
        
        $fotoUrl = $current['fotoUrl'];
        $pdfUrl = $current['pdfUrl'];
        
        // Procesar foto
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['foto'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            if (!in_array($ext, $allowed)) {
                throw new Exception("Formato de imagen no permitido");
            }
            
            if ($file['size'] > 8388608) { // 8MB
                throw new Exception("Imagen muy grande (máx 8MB)");
            }
            
            if (!is_dir('uploads/trees')) {
                mkdir('uploads/trees', 0777, true);
            }
            
            $newName = 'img_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
            $targetPath = 'uploads/trees/' . $newName;
            
            if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                throw new Exception("Error al guardar imagen");
            }
            
            if (!empty($current['fotoUrl']) && file_exists($current['fotoUrl'])) {
                @unlink($current['fotoUrl']);
            }
            
            $fotoUrl = $targetPath;
        } elseif (isset($_FILES['foto']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE) {
            throw new Exception("Error al subir foto: código " . $_FILES['foto']['error']);
        }
        
        // Procesar PDF
        if (isset($_FILES['pdf']) && $_FILES['pdf']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['pdf'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            if ($ext !== 'pdf') {
                throw new Exception("Solo archivos PDF permitidos");
            }
            
            if ($file['size'] > 10485760) { // 10MB
                throw new Exception("PDF muy grande (máx 10MB)");
            }
            
            if (!is_dir('uploads/pdfs')) {
                mkdir('uploads/pdfs', 0777, true);
            }
            
            $newName = 'pdf_' . time() . '_' . bin2hex(random_bytes(8)) . '.pdf';
            $targetPath = 'uploads/pdfs/' . $newName;
            
            if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                throw new Exception("Error al guardar PDF");
            }
            
            if (!empty($current['pdfUrl']) && file_exists($current['pdfUrl'])) {
                @unlink($current['pdfUrl']);
            }
            
            $pdfUrl = $targetPath;
        } elseif (isset($_FILES['pdf']) && $_FILES['pdf']['error'] !== UPLOAD_ERR_NO_FILE) {
            throw new Exception("Error al subir PDF: código " . $_FILES['pdf']['error']);
        }
        
        // Eliminar PDF si se solicitó
        if (isset($_POST['eliminar_pdf']) && $_POST['eliminar_pdf'] === '1') {
            if (!empty($current['pdfUrl']) && file_exists($current['pdfUrl'])) {
                @unlink($current['pdfUrl']);
            }
            $pdfUrl = null;
        }
        
        // Actualizar en base de datos
        $sql = "UPDATE arboles SET 
                especie = ?, 
                nombre_comun = ?, 
                codigo_arbol = ?, 
                edad = ?, 
                estado = ?, 
                altura = ?, 
                diametroTronco = ?, 
                diametro_copa = ?, 
                inspector = ?, 
                propiedad = ?, 
                otb = ?, 
                nombre_area_verde = ?, 
                estado_fitosanitario = ?, 
                fotoUrl = ?, 
                pdfUrl = ?
                WHERE id = ?";
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Error en consulta: " . $conn->error);
        }
        
        $stmt->bind_param("sssisdddsssssssi",
            $campos['especie'],
            $campos['nombre_comun'],
            $campos['codigo_arbol'],
            $edad,
            $campos['estado'],
            $altura,
            $diametroTronco,
            $diametro_copa,
            $campos['inspector'],
            $campos['propiedad'],
            $campos['otb'],
            $campos['nombre_area_verde'],
            $campos['estado_fitosanitario'],
            $fotoUrl,
            $pdfUrl,
            $id
        );
        
        if (!$stmt->execute()) {
            throw new Exception("Error al actualizar: " . $stmt->error);
        }
        
        $stmt->close();
        $conn->commit();
        
        $mensaje = "✓ Árbol actualizado correctamente";
        $tipo_mensaje = "success";
        
        // Recargar datos
        $stmt = $conn->prepare("SELECT * FROM arboles WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $arbol_seleccionado = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
    } catch (Exception $e) {
        $conn->rollback();
        $mensaje = "✗ Error: " . $e->getMessage();
        $tipo_mensaje = "error";
        
        error_log("ERROR EDITAR.PHP: " . $e->getMessage());
        error_log("POST: " . print_r($_POST, true));
        error_log("FILES: " . print_r($_FILES, true));
        
        // Recargar árbol seleccionado en caso de error
        if (isset($id) && $id > 0) {
            $stmt = $conn->prepare("SELECT * FROM arboles WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $arbol_seleccionado = $stmt->get_result()->fetch_assoc();
            $stmt->close();
        }
    }
}

// ===============================================
// OBTENER LISTA DE ÁRBOLES
// ===============================================
$arboles = [];
$result = $conn->query("SELECT * FROM arboles ORDER BY fecha_registro DESC, id DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $arboles[] = $row;
    }
}

// Obtener árbol seleccionado
if (!isset($arbol_seleccionado)) {
    $arbol_seleccionado = null;
    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $stmt = $conn->prepare("SELECT * FROM arboles WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $arbol_seleccionado = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Árboles - EMAVRA</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: Arial, sans-serif; 
            background: #f5f5f5; 
            padding: 20px; 
        }
        .container { max-width: 1400px; margin: 0 auto; }
        
        .header {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        .header-left { flex: 1; }
        .header h1 { color: #333; font-size: 24px; margin-bottom: 5px; }
        
        .export-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .btn-export {
            padding: 10px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
            color: white;
        }
        
        .btn-zip {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        .btn-zip:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(102, 126, 234, 0.5);
        }
        
        .btn-csv { background: #28a745; }
        .btn-csv:hover { background: #218838; transform: translateY(-2px); }
        
        .btn-json { background: #17a2b8; }
        .btn-json:hover { background: #138496; transform: translateY(-2px); }
        
        .btn-sql { background: #fd7e14; }
        .btn-sql:hover { background: #e8590c; transform: translateY(-2px); }
        
        .btn-excel { background: #20c997; }
        .btn-excel:hover { background: #1ba87e; transform: translateY(-2px); }
        
        .mensaje {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            font-weight: bold;
        }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        
        .grid {
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: 20px;
        }
        
        .lista-arboles {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            max-height: 800px;
            overflow-y: auto;
        }
        
        .arbol-item {
            padding: 12px;
            margin-bottom: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .arbol-item:hover {
            border-color: #3ebeab;
            background: #f8f9fa;
        }
        .arbol-item.active {
            border-color: #482e83;
            background: #f0ebf8;
        }
        .arbol-item strong { display: block; color: #333; margin-bottom: 5px; }
        .arbol-item small { color: #666; display: block; }
        
        .form-edicion {
            background: white;
            border-radius: 8px;
            padding: 25px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            max-height: 800px;
            overflow-y: auto;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #555;
        }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        .form-group textarea { resize: vertical; }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .archivo-actual {
            background: #e8f5e8;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 10px;
        }
        .archivo-actual img {
            max-width: 200px;
            border-radius: 4px;
            margin-top: 8px;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary {
            background: #482e83;
            color: white;
        }
        .btn-primary:hover { background: #3a235f; }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .btn-secondary:hover { background: #5a6268; }
        
        .placeholder {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        .placeholder i { font-size: 48px; margin-bottom: 15px; }
        
        @media (max-width: 768px) {
            .grid { grid-template-columns: 1fr; }
            .header { flex-direction: column; align-items: flex-start; }
            .export-buttons { width: 100%; }
            .btn-export { flex: 1; justify-content: center; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-left">
                <h1><i class="fas fa-edit"></i> Editor de Árboles Registrados</h1>
                <p style="color: #666; margin-top: 8px;">Selecciona un árbol de la lista para editar sus datos</p>
            </div>
            
            <div class="export-buttons">
                <a href="?export=zip" class="btn-export btn-zip" title="Exportación completa con todos los archivos">
                    <i class="fas fa-file-archive"></i> ZIP Completo
                </a>
                <a href="?export=csv" class="btn-export btn-csv" title="Exportar a CSV">
                    <i class="fas fa-file-csv"></i> CSV
                </a>
                <a href="?export=json" class="btn-export btn-json" title="Exportar a JSON">
                    <i class="fas fa-file-code"></i> JSON
                </a>
                <a href="?export=sql" class="btn-export btn-sql" title="Backup SQL">
                    <i class="fas fa-database"></i> SQL
                </a>
                <a href="?export=excel" class="btn-export btn-excel" title="Exportar a Excel">
                    <i class="fas fa-file-excel"></i> Excel
                </a>
            </div>
        </div>
        
        <?php if (isset($mensaje)): ?>
        <div class="mensaje <?php echo $tipo_mensaje; ?>">
            <?php echo $mensaje; ?>
        </div>
        <?php endif; ?>
        
        <div class="grid">
            <!-- LISTA DE ÁRBOLES -->
            <div class="lista-arboles">
                <h3 style="margin-bottom: 15px; color: #333;">
                    <i class="fas fa-tree"></i> Árboles (<?php echo count($arboles); ?>)
                </h3>
                
                <?php foreach ($arboles as $arbol): ?>
                <div class="arbol-item <?php echo ($arbol_seleccionado && $arbol['id'] == $arbol_seleccionado['id']) ? 'active' : ''; ?>" 
                     onclick="window.location.href='editar.php?id=<?php echo $arbol['id']; ?>'">
                    <strong><i class="fas fa-barcode"></i> <?php echo htmlspecialchars($arbol['codigo_arbol']); ?></strong>
                    <small><?php echo htmlspecialchars($arbol['especie']); ?></small>
                    <small style="color: #999;">
                        <i class="fas fa-calendar"></i> <?php echo $arbol['fecha_registro'] ?? 'N/A'; ?>
                    </small>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- FORMULARIO DE EDICIÓN -->
            <div class="form-edicion">
                <?php if ($arbol_seleccionado): ?>
                    <h2 style="margin-bottom: 20px; color: #333;">
                        <i class="fas fa-pencil-alt"></i> Editando: <?php echo htmlspecialchars($arbol_seleccionado['codigo_arbol']); ?>
                    </h2>
                    
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="id" value="<?php echo $arbol_seleccionado['id']; ?>">
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label><i class="fas fa-tree"></i> Nombre Científico *</label>
                                <input type="text" name="especie" value="<?php echo htmlspecialchars($arbol_seleccionado['especie']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-seedling"></i> Nombre Común *</label>
                                <input type="text" name="nombre_comun" value="<?php echo htmlspecialchars($arbol_seleccionado['nombre_comun']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label><i class="fas fa-barcode"></i> Código *</label>
                                <input type="text" name="codigo_arbol" value="<?php echo htmlspecialchars($arbol_seleccionado['codigo_arbol']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-shield-alt"></i> Estado *</label>
                                <select name="estado" required>
                                    <option value="exótico" <?php echo $arbol_seleccionado['estado'] === 'exótico' ? 'selected' : ''; ?>>Exótico</option>
                                    <option value="nativo" <?php echo $arbol_seleccionado['estado'] === 'nativo' ? 'selected' : ''; ?>>Nativo</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label><i class="fas fa-calendar"></i> Edad (años) *</label>
                                <input type="number" name="edad" value="<?php echo $arbol_seleccionado['edad']; ?>" required>
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-arrows-alt-v"></i> Altura (m) *</label>
                                <input type="number" step="0.1" name="altura" value="<?php echo $arbol_seleccionado['altura']; ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label><i class="fas fa-circle"></i> Diámetro Tronco (cm) *</label>
                                <input type="number" step="0.1" name="diametroTronco" value="<?php echo $arbol_seleccionado['diametroTronco']; ?>" required>
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-circle"></i> Diámetro Copa (m) *</label>
                                <input type="number" step="0.1" name="diametro_copa" value="<?php echo $arbol_seleccionado['diametro_copa']; ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label><i class="fas fa-user-tie"></i> Inspector *</label>
                                <input type="text" name="inspector" value="<?php echo htmlspecialchars($arbol_seleccionado['inspector'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-home"></i> Propiedad *</label>
                                <input type="text" name="propiedad" value="<?php echo htmlspecialchars($arbol_seleccionado['propiedad'] ?? ''); ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label><i class="fas fa-map-signs"></i> OTB *</label>
                                <input type="text" name="otb" value="<?php echo htmlspecialchars($arbol_seleccionado['otb'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-leaf"></i> Área Verde *</label>
                                <input type="text" name="nombre_area_verde" value="<?php echo htmlspecialchars($arbol_seleccionado['nombre_area_verde'] ?? ''); ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-heartbeat"></i> Estado Fitosanitario</label>
                            <textarea name="estado_fitosanitario" rows="4"><?php echo htmlspecialchars($arbol_seleccionado['estado_fitosanitario'] ?? ''); ?></textarea>
                        </div>
                        
                        <hr style="margin: 25px 0; border: none; border-top: 1px solid #ddd;">
                        
                        <!-- FOTO -->
                        <div class="form-group">
                            <label><i class="fas fa-camera"></i> Fotografía</label>
                            <?php if (!empty($arbol_seleccionado['fotoUrl'])): ?>
                            <div class="archivo-actual">
                                <strong>Foto actual:</strong>
                                <img src="<?php echo htmlspecialchars($arbol_seleccionado['fotoUrl']); ?>" alt="Foto actual">
                            </div>
                            <?php endif; ?>
                            <input type="file" name="foto" accept="image/*">
                            <small style="color: #666;">Deja vacío para mantener la foto actual</small>
                        </div>
                        
                        <!-- PDF -->
                        <div class="form-group">
                            <label><i class="fas fa-file-pdf"></i> PDF Informativo</label>
                            <?php if (!empty($arbol_seleccionado['pdfUrl'])): ?>
                            <div class="archivo-actual">
                                <strong>PDF actual:</strong> <?php echo basename($arbol_seleccionado['pdfUrl']); ?>
                                <br>
                                <a href="<?php echo htmlspecialchars($arbol_seleccionado['pdfUrl']); ?>" target="_blank" class="btn btn-secondary" style="margin-top: 8px; padding: 8px 16px; font-size: 14px;">
                                    <i class="fas fa-eye"></i> Ver PDF
                                </a>
                                <br>
                                <label style="margin-top: 10px; display: inline-block; font-weight: normal;">
                                    <input type="checkbox" name="eliminar_pdf" value="1">
                                    Eliminar PDF actual
                                </label>
                            </div>
                            <?php else: ?>
                            <div style="padding: 10px; background: #fff3cd; border-radius: 4px; margin-bottom: 10px;">
                                <small><i class="fas fa-info-circle"></i> No hay PDF cargado actualmente</small>
                            </div>
                            <?php endif; ?>
                            <input type="file" name="pdf" accept=".pdf">
                            <small style="color: #666;">Sube un nuevo PDF (máx 10MB). Deja vacío para mantener el actual</small>
                        </div>
                        
                        <div style="margin-top: 30px; display: flex; gap: 10px;">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Guardar Cambios
                            </button>
                            <a href="administrador.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Volver
                            </a>
                        </div>
                    </form>
                    
                <?php else: ?>
                    <div class="placeholder">
                        <i class="fas fa-hand-point-left"></i>
                        <h3>Selecciona un árbol de la lista</h3>
                        <p>Haz clic en cualquier árbol para editar sus datos</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>

<?php $conn->close(); ?>