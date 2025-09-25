<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('America/La_Paz');

$conn = new mysqli("localhost", "root", "", "reforest", 3306);

// Verificar conexión
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

require_once 'phpqrcode/qrlib.php';

// Crear directorios para archivos si no existen
if (!file_exists('uploads/trees/')) {
    mkdir('uploads/trees/', 0777, true);
}

if (!file_exists('uploads/pdfs/')) {
    mkdir('uploads/pdfs/', 0777, true);
}

if (!file_exists('qr_codes/')) {
    mkdir('qr_codes/', 0777, true);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // DEBUG COMPLETO DE LO QUE LLEGA
    error_log("=== DEBUG POST COMPLETO ===");
    error_log("POST array completo: " . print_r($_POST, true));
    error_log("FILES array completo: " . print_r($_FILES, true));
    
    // Procesar cada campo individualmente con debug
    $especie = '';
    $nombre_comun = '';
    $codigo_arbol = '';
    
    if (isset($_POST['especie'])) {
        $especie = trim($_POST['especie']);
        error_log("Campo especie encontrado: '" . $especie . "' (longitud: " . strlen($especie) . ")");
    } else {
        error_log("Campo especie NO ENCONTRADO en $_POST");
    }
    
    if (isset($_POST['nombre_comun'])) {
        $nombre_comun = trim($_POST['nombre_comun']);
        error_log("Campo nombre_comun encontrado: '" . $nombre_comun . "' (longitud: " . strlen($nombre_comun) . ")");
    } else {
        error_log("Campo nombre_comun NO ENCONTRADO en $_POST");
    }
    
    if (isset($_POST['codigo_arbol'])) {
        $codigo_arbol = trim($_POST['codigo_arbol']);
        error_log("Campo codigo_arbol encontrado: '" . $codigo_arbol . "' (longitud: " . strlen($codigo_arbol) . ")");
    } else {
        error_log("Campo codigo_arbol NO ENCONTRADO en $_POST");
    }
    
    // Resto de campos con procesamiento normal
    $edad = isset($_POST['edad']) && is_numeric($_POST['edad']) ? intval($_POST['edad']) : 0;
    $estado = isset($_POST['estado']) ? trim($_POST['estado']) : '';
    $altura = isset($_POST['altura']) && is_numeric($_POST['altura']) ? floatval($_POST['altura']) : 0;
    $diametroTronco = isset($_POST['diametroTronco']) && is_numeric($_POST['diametroTronco']) ? floatval($_POST['diametroTronco']) : 0;
    $diametro_copa = isset($_POST['diametro_copa']) && is_numeric($_POST['diametro_copa']) ? floatval($_POST['diametro_copa']) : 0;
    $latitud = isset($_POST['lat']) && is_numeric($_POST['lat']) ? floatval($_POST['lat']) : 0;
    $longitud = isset($_POST['lng']) && is_numeric($_POST['lng']) ? floatval($_POST['lng']) : 0;
    $propiedad = isset($_POST['propiedad']) ? trim($_POST['propiedad']) : '';
    $otb = isset($_POST['otb']) ? trim($_POST['otb']) : '';
    $nombre_area_verde = isset($_POST['nombre_area_verde']) ? trim($_POST['nombre_area_verde']) : '';
    $inspector = isset($_POST['inspector']) ? trim($_POST['inspector']) : '';
    
    // VALIDACIONES CON DEBUG ESPECÍFICO
    if (empty($especie)) {
        error_log("VALIDACIÓN FALLIDA: especie vacía. Valor: '" . $especie . "'");
        echo json_encode(['success' => false, 'message' => 'El campo "Especie del Árbol" es obligatorio']);
        exit;
    }
    
    if (empty($nombre_comun)) {
        error_log("VALIDACIÓN FALLIDA: nombre_comun vacío. Valor: '" . $nombre_comun . "'");
        echo json_encode(['success' => false, 'message' => 'El campo "Nombre Común" es obligatorio']);
        exit;
    }
    
    if (empty($codigo_arbol)) {
        error_log("VALIDACIÓN FALLIDA: codigo_arbol vacío. Valor: '" . $codigo_arbol . "'");
        echo json_encode(['success' => false, 'message' => 'El código del árbol es obligatorio']);
        exit;
    }
    
    if ($edad <= 0) {
        error_log("VALIDACIÓN FALLIDA: edad inválida. Valor: " . $edad);
        echo json_encode(['success' => false, 'message' => 'La edad debe ser un número mayor a 0']);
        exit;
    }
    
    if (empty($estado)) {
        error_log("VALIDACIÓN FALLIDA: estado vacío. Valor: '" . $estado . "'");
        echo json_encode(['success' => false, 'message' => 'Debe seleccionar un estado/categoría']);
        exit;
    }
    
    if ($altura <= 0) {
        error_log("VALIDACIÓN FALLIDA: altura inválida. Valor: " . $altura);
        echo json_encode(['success' => false, 'message' => 'La altura debe ser un número mayor a 0']);
        exit;
    }
    
    if ($diametroTronco <= 0) {
        error_log("VALIDACIÓN FALLIDA: diametroTronco inválido. Valor: " . $diametroTronco);
        echo json_encode(['success' => false, 'message' => 'El diámetro del tronco debe ser un número mayor a 0']);
        exit;
    }
    
    if ($diametro_copa <= 0) {
        error_log("VALIDACIÓN FALLIDA: diametro_copa inválido. Valor: " . $diametro_copa);
        echo json_encode(['success' => false, 'message' => 'El diámetro de la copa debe ser un número mayor a 0']);
        exit;
    }
    
    if ($latitud == 0 || $longitud == 0) {
        error_log("VALIDACIÓN FALLIDA: coordenadas inválidas. Lat: " . $latitud . ", Lng: " . $longitud);
        echo json_encode(['success' => false, 'message' => 'Debe seleccionar una ubicación válida en el mapa']);
        exit;
    }
    
    if (empty($propiedad)) {
        error_log("VALIDACIÓN FALLIDA: propiedad vacía. Valor: '" . $propiedad . "'");
        echo json_encode(['success' => false, 'message' => 'El campo "Propiedad" es obligatorio']);
        exit;
    }
    
    if (empty($otb)) {
        error_log("VALIDACIÓN FALLIDA: otb vacío. Valor: '" . $otb . "'");
        echo json_encode(['success' => false, 'message' => 'El campo "OTB" es obligatorio']);
        exit;
    }
    
    if (empty($nombre_area_verde)) {
        error_log("VALIDACIÓN FALLIDA: nombre_area_verde vacío. Valor: '" . $nombre_area_verde . "'");
        echo json_encode(['success' => false, 'message' => 'El campo "Nombre del Área Verde" es obligatorio']);
        exit;
    }
    
    if (empty($inspector)) {
        error_log("VALIDACIÓN FALLIDA: inspector vacío. Valor: '" . $inspector . "'");
        echo json_encode(['success' => false, 'message' => 'El campo "Inspector" es obligatorio']);
        exit;
    }

    // Validar formato del código (solo letras, números, guiones)
    if (!preg_match('/^[A-Za-z0-9\-_]+$/', $codigo_arbol)) {
        error_log("VALIDACIÓN FALLIDA: formato código inválido. Valor: '" . $codigo_arbol . "'");
        echo json_encode(['success' => false, 'message' => 'El código del árbol solo puede contener letras, números, guiones (-) y guiones bajos (_)']);
        exit;
    }
    
    // Verificar que el código del árbol no existe ya en la base de datos
    $checkStmt = $conn->prepare("SELECT id FROM arboles WHERE codigo_arbol = ?");
    if (!$checkStmt) {
        error_log("ERROR: No se pudo preparar statement de verificación: " . $conn->error);
        echo json_encode(['success' => false, 'message' => 'Error al verificar código: ' . $conn->error]);
        exit;
    }

    $checkStmt->bind_param("s", $codigo_arbol);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    if ($checkResult->num_rows > 0) {
        error_log("VALIDACIÓN FALLIDA: código duplicado. Valor: '" . $codigo_arbol . "'");
        echo json_encode(['success' => false, 'message' => 'Ya existe un árbol con el código: ' . $codigo_arbol]);
        $checkStmt->close();
        exit;
    }
    $checkStmt->close();

    // Si llegamos hasta aquí, todas las validaciones pasaron
    error_log("=== TODAS LAS VALIDACIONES PASARON ===");
    error_log("Procediendo con el procesamiento de archivos...");

    $fecha_registro = date('Y-m-d');
    $hora_registro = date('H:i:s');
    
    $fotoUrl = null;
    $pdfUrl = null;
    
    // Procesar subida de imagen
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        error_log("Procesando imagen...");
        $allowed = array("jpg" => "image/jpg", "jpeg" => "image/jpeg", "gif" => "image/gif", "png" => "image/png");
        $filename = $_FILES['foto']['name'];
        $filetype = $_FILES['foto']['type'];
        $filesize = $_FILES['foto']['size'];
        
        // Verificar extensión del archivo
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (!array_key_exists($ext, $allowed)) {
            echo json_encode(['success' => false, 'message' => 'Por favor selecciona un formato de imagen válido (JPG, JPEG, PNG, GIF)']);
            exit;
        }
        
        // Verificar tamaño del archivo - 5MB máximo
        $maxsize = 5 * 1024 * 1024;
        if ($filesize > $maxsize) {
            echo json_encode(['success' => false, 'message' => 'La imagen es demasiado grande. Tamaño máximo: 5MB']);
            exit;
        }
        
        // Verificar tipo MIME
        if (!in_array($filetype, $allowed)) {
            echo json_encode(['success' => false, 'message' => 'Ha ocurrido un problema al subir la imagen']);
            exit;
        }
        
        // Generar nombre único para el archivo
        $newName = 'img_' . time() . '_' . uniqid() . '.' . $ext;
        $targetPath = 'uploads/trees/' . $newName;
        
        // Mover archivo al directorio de destino
        if (move_uploaded_file($_FILES['foto']['tmp_name'], $targetPath)) {
            $fotoUrl = $targetPath;
            error_log("Imagen guardada exitosamente: " . $fotoUrl);
        } else {
            echo json_encode(['success' => false, 'message' => 'Ha ocurrido un problema al subir la imagen']);
            exit;
        }
    } else {
        error_log("Error en archivo de imagen: " . print_r($_FILES['foto'], true));
        echo json_encode(['success' => false, 'message' => 'No se ha seleccionado ninguna imagen o ha ocurrido un error en la subida']);
        exit;
    }

    // Procesar subida de PDF (opcional)
    if (isset($_FILES['pdf']) && $_FILES['pdf']['error'] == 0) {
        error_log("Procesando PDF...");
        $pdfFilename = $_FILES['pdf']['name'];
        $pdfFiletype = $_FILES['pdf']['type'];
        $pdfFilesize = $_FILES['pdf']['size'];
        
        // Verificar que sea un PDF
        $pdfExt = strtolower(pathinfo($pdfFilename, PATHINFO_EXTENSION));
        if ($pdfExt !== 'pdf' || $pdfFiletype !== 'application/pdf') {
            echo json_encode(['success' => false, 'message' => 'Por favor selecciona un archivo PDF válido']);
            exit;
        }
        
        // Verificar tamaño del archivo - 10MB máximo para PDFs
        $maxPdfSize = 10 * 1024 * 1024;
        if ($pdfFilesize > $maxPdfSize) {
            echo json_encode(['success' => false, 'message' => 'El archivo PDF es demasiado grande. Tamaño máximo: 10MB']);
            exit;
        }
        
        // Generar nombre único para el PDF
        $newPdfName = 'pdf_' . time() . '_' . uniqid() . '.pdf';
        $pdfTargetPath = 'uploads/pdfs/' . $newPdfName;
        
        // Mover archivo al directorio de destino
        if (move_uploaded_file($_FILES['pdf']['tmp_name'], $pdfTargetPath)) {
            $pdfUrl = $pdfTargetPath;
            error_log("PDF guardado exitosamente: " . $pdfUrl);
        } else {
            echo json_encode(['success' => false, 'message' => 'Ha ocurrido un problema al subir el archivo PDF']);
            exit;
        }
    }

    try {
        error_log("=== INSERTANDO EN BASE DE DATOS ===");
        error_log("Valores a insertar:");
        error_log("especie: " . $especie);
        error_log("nombre_comun: " . $nombre_comun);
        error_log("edad: " . $edad);
        error_log("estado: " . $estado);
        error_log("fotoUrl: " . $fotoUrl);
        error_log("altura: " . $altura);
        error_log("diametroTronco: " . $diametroTronco);
        error_log("diametro_copa: " . $diametro_copa);
        error_log("codigo_arbol: " . $codigo_arbol);
        error_log("latitud: " . $latitud);
        error_log("longitud: " . $longitud);
        error_log("propiedad: " . $propiedad);
        error_log("otb: " . $otb);
        error_log("nombre_area_verde: " . $nombre_area_verde);
        error_log("inspector: " . $inspector);
        error_log("pdfUrl: " . ($pdfUrl ?? 'NULL'));
        error_log("fecha_registro: " . $fecha_registro);
        error_log("hora_registro: " . $hora_registro);
        
        // Crear coordenadas POINT para MySQL
        $coordenadas = "POINT(" . $longitud . " " . $latitud . ")";
        
        // Insertar en la base de datos usando prepared statement - CONSULTA CORREGIDA
        $stmt = $conn->prepare("INSERT INTO arboles (
            especie, nombre_comun, edad, estado, fotoUrl, altura, diametroTronco, 
            diametro_copa, codigo_arbol, latitud, longitud, coordenadas,
            propiedad, otb, nombre_area_verde, inspector, pdfUrl, 
            fecha_registro, hora_registro
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, 
            ?, ?, ?, ?, ST_GeomFromText(?),
            ?, ?, ?, ?, ?, 
            ?, ?
        )");
        
        if (!$stmt) {
            error_log("ERROR: No se pudo preparar statement de inserción: " . $conn->error);
            echo json_encode(['success' => false, 'message' => 'Error al preparar consulta: ' . $conn->error]);
            exit;
        }

        // CORREGIR bind_param - orden correcto de parámetros
        $stmt->bind_param("ssissddssddssssssss", 
            $especie,           // s - string
            $nombre_comun,      // s - string  
            $edad,              // i - integer
            $estado,            // s - string
            $fotoUrl,           // s - string
            $altura,            // d - double
            $diametroTronco,    // d - double
            $diametro_copa,     // d - double
            $codigo_arbol,      // s - string
            $latitud,           // d - double
            $longitud,          // d - double
            $coordenadas,       // s - string (para ST_GeomFromText)
            $propiedad,         // s - string
            $otb,               // s - string
            $nombre_area_verde, // s - string
            $inspector,         // s - string
            $pdfUrl,            // s - string (puede ser null)
            $fecha_registro,    // s - string
            $hora_registro      // s - string
        );

        // REEMPLAZA desde "if ($stmt->execute()) {" hasta "} catch (Exception $e) {" 
// con este código corregido:

        if ($stmt->execute()) {
            $lastId = $conn->insert_id;
            error_log("INSERT EXITOSO. ID generado: " . $lastId);

            // CONSTRUCCIÓN CORREGIDA DE LA URL
            $domain = $_SERVER['HTTP_HOST'];
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
            
            // Obtener el directorio base del proyecto de forma confiable
            $scriptPath = $_SERVER['SCRIPT_NAME']; // /SkyGreen/administrador.php
            $basePath = dirname($scriptPath);      // /SkyGreen
            
            // Si estamos en el directorio raíz del servidor, basePath será '/'
            if ($basePath === '/' || $basePath === '\\') {
                $basePath = '';
            }
            
            // Construir la URL correctamente - ESTA ES LA LÍNEA CLAVE
            $treeUrl = $protocol . "://" . $domain . $basePath . "/index.php?tree_id=" . $lastId . "#map";
            
            // Debug mejorado
            error_log("=== DEBUG URL GENERATION ===");
            error_log("HTTP_HOST: " . $domain);
            error_log("SCRIPT_NAME: " . $_SERVER['SCRIPT_NAME']);
            error_log("Base Path: " . $basePath);
            error_log("URL final generada: " . $treeUrl);
            error_log("============================");

            // Generar el código QR
            $qrFilename = 'qr_codes/qr_' . $lastId . '.png';
            try {
                // Asegurar que el directorio existe
                if (!file_exists('qr_codes/')) {
                    mkdir('qr_codes/', 0777, true);
                }
                
                QRcode::png($treeUrl, $qrFilename, QR_ECLEVEL_L, 4);
                
                // Actualizar el registro con la URL del QR
                $updateStmt = $conn->prepare("UPDATE arboles SET qrUrl = ? WHERE id = ?");
                if ($updateStmt) {
                    $updateStmt->bind_param("si", $qrFilename, $lastId);
                    $updateStmt->execute();
                    $updateStmt->close();
                }
                error_log("QR generado exitosamente: " . $qrFilename . " con URL: " . $treeUrl);
            } catch (Exception $e) {
                error_log("Error al generar QR: " . $e->getMessage());
            }
            
            echo json_encode([
                'success' => true, 
                'message' => 'Árbol registrado exitosamente',
                'id' => $lastId,
                'debug_url' => $treeUrl // Para verificar en frontend
            ]);
            $stmt->close();
            exit; // CORREGIDO: CON PUNTO Y COMA
        } else {
            $error_msg = $stmt->error;
            error_log("Error SQL en insert: " . $error_msg);
            echo json_encode(['success' => false, 'message' => 'Error en base de datos: ' . $error_msg]);
            $stmt->close();
            exit;
        }
    } catch (Exception $e) {
        error_log("EXCEPCIÓN en try-catch: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        exit;
    }
}


// Consulta para mostrar árboles
$sql = "SELECT 
    id, especie, nombre_comun, edad, estado, fotoUrl, altura, diametroTronco, 
    diametro_copa, codigo_arbol, ST_AsText(coordenadas) as coordenadas, 
    latitud, longitud, propiedad, otb, nombre_area_verde, inspector, 
    pdfUrl, qrUrl, DATE_FORMAT(fecha_registro, '%d/%m/%Y') as fecha_formato, 
    hora_registro 
FROM arboles ORDER BY fecha_registro DESC, hora_registro DESC";

$result = $conn->query($sql);

// Array para almacenar los árboles
$arboles = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $arboles[] = $row;
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SkyGreen - Administrador</title>
    <script src="https://api.mapbox.com/mapbox-gl-js/v2.8.1/mapbox-gl.js"></script>
    <link href="https://api.mapbox.com/mapbox-gl-js/v2.8.1/mapbox-gl.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            min-height: 100vh;
            color: #333;
        }

        .header {
            background: white;
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .nav {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 2rem;
        }

        .logo {
            display: flex;
            align-items: center;
            font-size: 1.8rem;
            font-weight: bold;
            color: #333;
            font-family: 'Arciform', 'Poppins', sans-serif;
        }

        .logo i {
            margin-right: 0.5rem;
            color: #482e83;
        }

        .nav-links {
            display: flex;
            list-style: none;
            gap: 2rem;
        }

        .nav-links a {
            text-decoration: none;
            color: #666;
            font-weight: 400;
            transition: color 0.3s ease;
        }

        .nav-links a:hover {
            color: #333;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        .admin-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .admin-title {
            font-size: 3rem;
            font-weight: 300;
            color: #333;
            margin-bottom: 1rem;
            font-family: 'Arciform', 'Poppins', sans-serif;
        }

        .admin-subtitle {
            font-size: 1.1rem;
            color: #666;
            max-width: 600px;
            margin: 0 auto;
            line-height: 1.6;
            font-weight: 300;
        }

        .content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .form-card {
            background: white;
            border-radius: 8px;
            padding: 2rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            border: 1px solid #e9ecef;
            max-height: 800px;
            overflow-y: auto;
        }

        .form-title {
            font-size: 1.3rem;
            font-weight: 400;
            color: #333;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            font-family: 'Arciform', 'Poppins', sans-serif;
        }

        .form-title i {
            margin-right: 0.5rem;
            color: #2d5016;
        }

        .form-section {
            border: 1px solid #e9ecef;
            border-radius: 6px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            background: #f8f9fa;
        }

        .form-section-title {
            font-size: 1rem;
            font-weight: 500;
            color: #2d5016;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
        }

        .form-section-title i {
            margin-right: 0.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .form-row-four {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr 1fr;
            gap: 1rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #555;
        }

        .form-input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: white;
        }

        .form-input:focus {
            outline: none;
            border-color: #2d5016;
            box-shadow: 0 0 0 2px rgba(45, 80, 22, 0.1);
        }

        .form-select {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            font-size: 1rem;
            background: white;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .form-select:focus {
            outline: none;
            border-color: #2d5016;
            box-shadow: 0 0 0 2px rgba(45, 80, 22, 0.1);
        }

        .file-input-container {
            position: relative;
            display: inline-block;
            width: 100%;
        }

        .file-input {
            display: none;
        }

        .file-input-label {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px dashed #dee2e6;
            border-radius: 4px;
            background: #f8f9fa;
            color: #666;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            font-size: 1rem;
        }

        .file-input-label:hover {
            border-color: #2d5016;
            background: #f0f8f0;
            color: #2d5016;
        }

        .file-input-label i {
            margin-right: 0.5rem;
            font-size: 1.2rem;
        }

        .file-preview {
            margin-top: 1rem;
            text-align: center;
        }

        .file-preview img {
            max-width: 200px;
            max-height: 150px;
            border-radius: 4px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .file-info {
            background: #e8f5e8;
            border: 1px solid #c3e6cb;
            border-radius: 4px;
            padding: 0.5rem;
            margin-top: 0.5rem;
            font-size: 0.9rem;
            color: #155724;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 4px;
            font-size: 1rem;
            font-weight: 400;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }

        .btn-primary {
            background: #482e83;
            color: white;
        }

        .btn-primary:hover {
            background: #1a2f0c;
        }

        .btn-secondary {
            background: #3ebeab;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .btn-group {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }

        .map-card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            border: 1px solid #e9ecef;
        }

        .map-title {
            font-size: 1.3rem;
            font-weight: 400;
            color: #333;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            font-family: 'Arciform', 'Poppins', sans-serif;
        }

        .map-title i {
            margin-right: 0.5rem;
            color: #2d5016;
        }

        #map {
            width: 100%;
            height: 500px;
            border-radius: 4px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 1rem;
        }

        .coordinates-display {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 1rem;
            text-align: center;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
            color: #495057;
        }

        .coordinates-display strong {
            color: #2d5016;
        }

        .tree-marker {
            background-image: url('https://cdn2.iconfinder.com/data/icons/miscellaneous-iii-glyph-style/150/tree-512.png');
            background-size: cover;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            cursor: pointer;
            transition: transform 0.2s ease;
        }

        .tree-marker:hover {
            transform: scale(1.1);
        }

        .location-hint {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 4px;
            padding: 1rem;
            margin-bottom: 1rem;
            color: #495057;
            font-size: 0.9rem;
        }

        .location-hint i {
            margin-right: 0.5rem;
            color: #2d5016;
        }

        .success-message {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
            display: none;
        }

        @media (max-width: 768px) {
            .content-grid {
                grid-template-columns: 1fr;
            }

            .form-row, .form-row-four {
                grid-template-columns: 1fr;
            }

            .nav {
                padding: 0 1rem;
            }

            .nav-links {
                display: none;
            }

            .admin-title {
                font-size: 2rem;
            }

            .container {
                padding: 1rem;
            }

            #map {
                height: 350px;
            }
        }

        .mapboxgl-popup-content {
            border-radius: 4px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
            max-width: 350px;
            
        }

        .mapboxgl-popup-content h3 {
            color: #333;
            margin-bottom: 0.5rem;
            font-weight: 400;
        }

        .mapboxgl-popup-content img {
            border-radius: 8px;
            margin: 0.5rem 0;
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .mapboxgl-popup-content p {
            margin: 0.25rem 0;
            font-size: 0.9rem;
            color: #555;
        }

        .view-tree-btn {
            background: #2d5016;
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.8rem;
            display: inline-block;
            margin-top: 0.5rem;
            transition: background 0.3s ease;
        }

        .view-tree-btn:hover {
            background: #1a2f0c;
            color: white;
        }

        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
    </style>
</head>

<body>
    <header class="header">
        <nav class="nav">
            <div class="logo">
                <i class="fas fa-leaf"></i>
                SkyGreen
            </div>
            <ul class="nav-links">
                <li><a href="index.php">Volver Al Inicio</a></li>
            </ul>
        </nav>
    </header>

    <div class="container">
        <div class="admin-header">
            <h1 class="admin-title">Panel de Administración</h1>
            <p class="admin-subtitle">
                Registra y gestiona los árboles de la Zona Norte de Cochabamba. 
                Cada árbol registrado contribuye a construir un futuro más verde y sostenible.
            </p>
        </div>

        <div class="success-message" id="successMessage">
            <i class="fas fa-check-circle"></i>
            ¡Árbol registrado exitosamente!
        </div>

        <div class="content-grid">
            <div class="form-card">
                <h2 class="form-title">
                    <i class="fas fa-plus-circle"></i>
                    Registrar Nuevo Árbol
                </h2>
                
                <form id="arbolForm" enctype="multipart/form-data">
                    <!-- Sección: Información del Árbol -->
                    <div class="form-section">
                        <div class="form-section-title">
                            <i class="fas fa-tree"></i>
                            Información del Árbol
                        </div>
                        
                        <div class="form-group">
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-tree"></i> Especie Del Árbol
                                </label>
                                <input type="text" name="especie" class="form-input" placeholder="Ej: Molle" required />
                            </div>
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-tree"></i> Nombre Común
                                </label>
                                <input type="text" name="nombre_comun" class="form-input" placeholder="Ej: 5" required />
                            </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-calendar"></i> Edad (años)
                                </label>
                                <input type="number" name="edad" class="form-input" placeholder="Ej: 5" required />
                            </div>
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-shield-alt"></i> Estado/Categoría
                                </label>
                                <select name="estado" class="form-select" required>
                                    <option value="">Selecciona una categoría</option>
                                    <option value="exótico">Exótico</option>
                                    <option value="nativo">Nativo</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-row-four">
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-arrows-alt-v"></i> Altura (metros)
                                </label>
                                <input type="number" step="0.1" name="altura" class="form-input" placeholder="Ej: 3.5" required/>
                            </div>
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-circle"></i> Diámetro Tronco (cm)
                                </label>
                                <input type="number" step="0.1" name="diametroTronco" class="form-input" placeholder="Ej: 25.5" required/>
                            </div>
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-circle"></i> Diámetro Copa (m)
                                </label>
                                <input type="number" step="0.1" name="diametro_copa" class="form-input" placeholder="Ej: 4.2" required/>
                            </div>
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-barcode"></i> Código Árbol
                                </label>
                                <input type="text" name="codigo_arbol" class="form-input" placeholder="Ej: P1, P2" required minlength="1"/>
                            </div>
                                
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-camera"></i> Fotografía del Árbol
                            </label>
                            <div class="file-input-container">
                                <input type="file" id="foto" name="foto" class="file-input" accept="image/*" required>
                                <label for="foto" class="file-input-label">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    Seleccionar imagen del árbol
                                </label>
                            </div>
                            <div id="filePreview" class="file-preview"></div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-file-pdf"></i> PDF Informativo 
                            </label>
                            <div class="file-input-container">
                                <input type="file" id="pdf" name="pdf" class="file-input" accept=".pdf">
                                <label for="pdf" class="file-input-label">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    Seleccionar PDF del árbol 
                                </label>
                            </div>
                            <div id="pdfPreview" class="file-preview"></div>
                        </div>
                    </div>

                    <!-- Sección: Información de Registro -->
                    <div class="form-section">
                        <div class="form-section-title">
                            <i class="fas fa-clipboard-list"></i>
                            Información de Registro
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-user-tie"></i> Inspector
                                </label>
                                <input type="text" name="inspector" class="form-input" placeholder="Nombre del inspector" required />
                            </div>
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-home"></i> Propiedad
                                </label>
                                <input type="text" name="propiedad" class="form-input" placeholder="Tipo de propiedad" required />
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-map-signs"></i> OTB
                                </label>
                                <input type="text" name="otb" class="form-input" placeholder="Organización Territorial de Base" required />
                            </div>
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-leaf"></i> Nombre del Área Verde
                                </label>
                                <input type="text" name="nombre_area_verde" class="form-input" placeholder="Nombre del área verde" required />
                            </div>
                        </div>
                    </div>

                    <!-- Sección: Ubicación -->
                    <div class="form-section">
                        <div class="form-section-title">
                            <i class="fas fa-map-marker-alt"></i>
                            Ubicación
                        </div>

                        <div class="location-hint">
                            <i class="fas fa-info-circle"></i>
                            Selecciona la ubicación del árbol en el mapa haciendo clic o usando tu ubicación actual.
                        </div>

                        <div class="btn-group">
                            <button type="button" class="btn btn-secondary" onclick="obtenerUbicacion()">
                                <i class="fas fa-crosshairs"></i>
                                Mi Ubicación
                            </button>
                            <button type="button" class="btn btn-primary" onclick="confirmarUbicacion()">
                                <i class="fas fa-check"></i>
                                Confirmar Ubicación
                            </button>
                        </div>
                    </div>

                    <button type="button" id="agregarArbolBtn" class="btn btn-primary" style="width: 100%; margin-top: 1rem;" disabled onclick="submitForm()">
                        <i class="fas fa-plus"></i>
                        Registrar Árbol
                    </button>
                </form>
            </div>

            
        </div>
        <div class="map-card">
                <h2 class="map-title">
                    <i class="fas fa-map"></i>
                    Mapa de Ubicación
                </h2>
                <div id="map"></div>
                
                <!-- Display de coordenadas -->
                <div class="coordinates-display" id="coordinatesDisplay">
                    <strong>Coordenadas actuales:</strong><br>
                    <span id="currentCoords">Selecciona un punto en el mapa</span>
                </div>
            </div>
    </div>

    <script>
        // Mapbox token
        mapboxgl.accessToken = 'pk.eyJ1IjoiYWxlc3NpcyIsImEiOiJjbGcxbHBtbHQwdDU5M2RubDFodjY3a2x0In0.NXe43GdM4PJBj7ow0Dnkpw';

        const map = new mapboxgl.Map({
            container: 'map',
            style: 'mapbox://styles/mapbox/streets-v11',
            center: [-66.156977, -17.393838],
            zoom: 17,
            pitch: 50,
            bearing: -17.6
        });

        let marker, lng, lat;

        // Función para actualizar las coordenadas mostradas
        function updateCoordinatesDisplay(longitude, latitude) {
            const coordsElement = document.getElementById('currentCoords');
            if (longitude && latitude) {
                coordsElement.innerHTML = `
                    <strong>Latitud:</strong> ${latitude.toFixed(6)}<br>
                    <strong>Longitud:</strong> ${longitude.toFixed(6)}
                `;
            } else {
                coordsElement.textContent = 'Selecciona un punto en el mapa';
            }
        }

        // Preview de archivo imagen
        document.getElementById('foto').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const preview = document.getElementById('filePreview');
            
            if (file) {
                // Validar tipo de archivo
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                if (!allowedTypes.includes(file.type)) {
                    showNotification('Por favor selecciona un archivo de imagen válido (JPG, PNG, GIF)', 'error');
                    e.target.value = '';
                    preview.innerHTML = '';
                    return;
                }
                
                // Validar tamaño (5MB máximo)
                const maxSize = 5 * 1024 * 1024;
                if (file.size > maxSize) {
                    showNotification('El archivo es demasiado grande. Tamaño máximo: 5MB', 'error');
                    e.target.value = '';
                    preview.innerHTML = '';
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(event) {
                    preview.innerHTML = `
                        <img src="${event.target.result}" alt="Vista previa">
                        <div class="file-info">
                            <i class="fas fa-file-image"></i> ${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)
                        </div>
                    `;
                };
                reader.readAsDataURL(file);
                
                // Cambiar texto del label
                document.querySelector('label[for="foto"]').innerHTML = `
                    <i class="fas fa-check-circle"></i>
                    Imagen seleccionada: ${file.name}
                `;
            } else {
                preview.innerHTML = '';
                document.querySelector('label[for="foto"]').innerHTML = `
                    <i class="fas fa-cloud-upload-alt"></i>
                    Seleccionar imagen del árbol
                `;
            }
        });

        // Preview de archivo PDF
        document.getElementById('pdf').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const preview = document.getElementById('pdfPreview');
            
            if (file) {
                // Validar tipo de archivo
                if (file.type !== 'application/pdf') {
                    showNotification('Por favor selecciona un archivo PDF válido', 'error');
                    e.target.value = '';
                    preview.innerHTML = '';
                    return;
                }
                
                // Validar tamaño (10MB máximo)
                const maxSize = 10 * 1024 * 1024;
                if (file.size > maxSize) {
                    showNotification('El archivo PDF es demasiado grande. Tamaño máximo: 10MB', 'error');
                    e.target.value = '';
                    preview.innerHTML = '';
                    return;
                }
                
                preview.innerHTML = `
                    <div class="file-info">
                        <i class="fas fa-file-pdf"></i> ${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)
                    </div>
                `;
                
                // Cambiar texto del label
                document.querySelector('label[for="pdf"]').innerHTML = `
                    <i class="fas fa-check-circle"></i>
                    PDF seleccionado: ${file.name}
                `;
            } else {
                preview.innerHTML = '';
                document.querySelector('label[for="pdf"]').innerHTML = `
                    <i class="fas fa-cloud-upload-alt"></i>
                    Seleccionar PDF del árbol (opcional)
                `;
            }
        });

        map.on('load', function() {
            // Agregar el área de Unifranz
            

            // Mostrar árboles existentes
            const arboles = <?php echo json_encode($arboles); ?>;
            arboles.forEach(arbol => {
                // Crear marcador para cada árbol
                const el = document.createElement('div');
                el.className = 'tree-marker';

                // Color del borde según el estado
                switch (arbol.estado.toLowerCase()) {
                    case 'exótico':
                        el.style.border = '3px solid rgb(229, 255, 0)';
                        break;
                    case 'nativo':
                        el.style.border = '3px solid #2ed573';
                        break;
                    default:
                        el.style.border = '3px solid #57606f';
                }

                const treeMarker = new mapboxgl.Marker(el)
                    .setLngLat([parseFloat(arbol.longitud), parseFloat(arbol.latitud)])
                    .addTo(map);

                const popup = new mapboxgl.Popup({
                        offset: 25
                    })
                    .setHTML(`
                    <div style="max-width: 320px;">
                        <h3><i class="fas fa-tree"></i> ${arbol.especie}</h3>
                        
                        ${arbol.fotoUrl ? `<img src="${arbol.fotoUrl}" alt="Foto del árbol" style="width: 100%; height: 180px; object-fit: cover;" />` : ''}
                        
                        <div style="margin: 10px 0; padding: 8px; background: #f8f9fa; border-radius: 4px;">
                            <p><strong>Nombre común:</strong> ${arbol.nombre_comun}</p>
                            <p><i class="fas fa-calendar"></i> <strong>Edad:</strong> ${arbol.edad} años</p>
                            <p><i class="fas fa-arrows-alt-v"></i> <strong>Altura:</strong> ${arbol.altura}m</p>
                            <p><i class="fas fa-circle"></i> <strong>Diámetro Tronco:</strong> ${arbol.diametroTronco}cm</p>
                            <p><i class="fas fa-circle"></i> <strong>Diámetro Copa:</strong> ${arbol.diametro_copa}m</p>
                            <p><i class="fas fa-barcode"></i> <strong>Código:</strong> ${arbol.codigo_arbol}</p>
                            <p><i class="fas fa-shield-alt"></i> <strong>Estado:</strong> ${arbol.estado}</p>
                        </div>
                        
                        <div style="margin: 10px 0; padding: 8px; background: #e8f5e8; border-radius: 4px;">
                            <p><i class="fas fa-user-tie"></i> <strong>Inspector:</strong> ${arbol.inspector || 'N/A'}</p>
                            <p><i class="fas fa-home"></i> <strong>Propiedad:</strong> ${arbol.propiedad || 'N/A'}</p>
                            <p><i class="fas fa-map-signs"></i> <strong>OTB:</strong> ${arbol.otb || 'N/A'}</p>
                            <p><i class="fas fa-leaf"></i> <strong>Área Verde:</strong> ${arbol.nombre_area_verde || 'N/A'}</p>
                        </div>
                        
                        <div style="margin: 10px 0; padding: 8px; background: #fff3cd; border-radius: 4px;">
                            <p><i class="fas fa-calendar-alt"></i> <strong>Registrado:</strong> ${arbol.fecha_formato || 'N/A'} a las ${arbol.hora_registro || 'N/A'}</p>
                            <p><i class="fas fa-map-marker-alt"></i> <strong>Coordenadas:</strong> ${arbol.latitud}, ${arbol.longitud}</p>
                        </div>
                        
                        <div style="text-align: center; margin-top: 10px;">
                            ${arbol.qrUrl ? `<img src="${arbol.qrUrl}" alt="QR del árbol" style="width: 80px; height: 80px; margin-bottom: 10px;" />` : ''}
                            ${arbol.pdfUrl ? `<br><a href="${arbol.pdfUrl}" target="_blank" class="view-tree-btn">
                                <i class="fas fa-file-pdf"></i> Ver PDF
                            </a>` : ''}
                        </div>
                    </div>
                `);

                treeMarker.setPopup(popup);
            });
        });

        // Event listener para clics en el mapa
        map.on('click', (e) => {
            lng = e.lngLat.lng;
            lat = e.lngLat.lat;

            updateCoordinatesDisplay(lng, lat);

            if (marker) {
                marker.setLngLat(e.lngLat);
            } else {
                marker = new mapboxgl.Marker({
                        draggable: true,
                        color: '#4a7c59'
                    })
                    .setLngLat(e.lngLat)
                    .addTo(map);

                marker.on('dragend', function() {
                    const lngLat = marker.getLngLat();
                    lng = lngLat.lng;
                    lat = lngLat.lat;
                    updateCoordinatesDisplay(lng, lat);
                });
            }
        });

        function confirmarUbicacion() {
            if (lng && lat) {
                document.getElementById('agregarArbolBtn').disabled = false;
                showNotification('Ubicación confirmada correctamente', 'success');
            } else {
                showNotification('Por favor selecciona una ubicación en el mapa', 'error');
            }
        }

        // Agrega esta validación específica en la función submitForm():
// Función de debug mejorada
function debugFormulario() {
    const form = document.getElementById('arbolForm');
    console.log("=== DEBUG COMPLETO DEL FORMULARIO ===");
    
    // Verificar específicamente el campo codigo_arbol
    const codigoInput = form.querySelector('[name="codigo_arbol"]');
    console.log('Campo codigo_arbol encontrado:', codigoInput);
    
    if (codigoInput) {
        console.log('Valor actual:', codigoInput.value);
        console.log('Valor trimmed:', codigoInput.value.trim());
        console.log('Tipo de input:', codigoInput.type);
        console.log('Name attribute:', codigoInput.name);
        console.log('ID attribute:', codigoInput.id);
    } else {
        console.error('CAMPO CODIGO_ARBOL NO ENCONTRADO!');
    }
    
    // Verificar específicamente el campo especie
    const especieInput = form.querySelector('[name="especie"]');
    console.log('Campo especie:', especieInput);
    if (especieInput) {
        console.log('Especie value:', especieInput.value);
        console.log('Especie type:', especieInput.type);
        console.log('Especie name:', especieInput.name);
    } else {
        console.error('CAMPO ESPECIE NO ENCONTRADO!');
    }
    
    // Crear FormData y verificar
    const formData = new FormData(form);
    console.log('=== FORMDATA CONTENTS ===');
    for (let [key, value] of formData.entries()) {
        console.log(`${key}: "${value}" (tipo: ${typeof value})`);
    }
}
// Listener para detectar cambios en el input (debe ir dentro de DOMContentLoaded)
document.addEventListener('DOMContentLoaded', function() {
    const codigoInput = document.querySelector('[name="codigo_arbol"]');
    if (codigoInput) {
        codigoInput.addEventListener('input', function(e) {
            console.log('Código cambiado a:', e.target.value);
        });
        
        // Debug adicional: verificar que el campo tenga el atributo name correcto
        console.log('Input codigo_arbol configurado:', {
            name: codigoInput.name,
            type: codigoInput.type,
            required: codigoInput.required,
            placeholder: codigoInput.placeholder
        });
    }
});
// Función submitForm corregida - VERSIÓN FINAL
function submitForm() {
    console.log("=== INICIO SUBMIT ===");
    
    const form = document.getElementById('arbolForm');
    
    // Debug: verificar todos los campos antes de crear FormData
    console.log("=== VERIFICACIÓN CAMPOS ANTES DE FORMDATA ===");
    const especie = form.querySelector('[name="especie"]').value;
    const codigo_arbol = form.querySelector('[name="codigo_arbol"]').value;
    console.log('Especie valor:', especie);
    console.log('Codigo arbol valor:', codigo_arbol);
    
    const formData = new FormData(form);
    
    // Debug: verificar FormData
    console.log("=== FORMDATA FINAL ===");
    for (let [key, value] of formData.entries()) {
        console.log(`${key}: "${value}"`);
    }
    
    // Validaciones básicas y específicas
    if (!especie || especie.trim() === '') {
        showNotification('El campo "Especie del Árbol" es obligatorio', 'error');
        return;
    }
    
    if (!codigo_arbol || codigo_arbol.trim() === '') {
        showNotification('El código del árbol es obligatorio', 'error');
        return;
    }
    
    // Verificar formato válido del código
    if (!/^[A-Za-z0-9\-_]+$/.test(codigo_arbol.trim())) {
        showNotification('El código del árbol solo puede contener letras, números, guiones (-) y guiones bajos (_)', 'error');
        return;
    }
    
    // Validar imagen
    if (!document.getElementById('foto').files[0]) {
        showNotification('Por favor selecciona una imagen del árbol', 'error');
        return;
    }
    
    // Validar coordenadas
    if (!lng || !lat || lng === 0 || lat === 0) {
        showNotification('Por favor selecciona una ubicación válida en el mapa', 'error');
        return;
    }
    
    // Agregar coordenadas al FormData
    formData.append('lng', lng);
    formData.append('lat', lat);

    // Mostrar loading en el botón
    const btn = document.getElementById('agregarArbolBtn');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Registrando árbol...';
    btn.disabled = true;

    // Enviar datos
    fetch('administrador.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);
        return response.text(); // Cambiar a text() primero para debug
    })
    .then(responseText => {
        console.log('Response text:', responseText);
        
        // Intentar parsear como JSON
        let data;
        try {
            data = JSON.parse(responseText);
        } catch (e) {
            console.error('Error parsing JSON:', e);
            console.error('Response was:', responseText);
            throw new Error('La respuesta del servidor no es JSON válido');
        }
        
        console.log('Respuesta del servidor:', data);
        
        if (data.success) {
            document.getElementById('successMessage').style.display = 'block';
            form.reset();
            document.getElementById('filePreview').innerHTML = '';
            document.getElementById('pdfPreview').innerHTML = '';
            document.querySelector('label[for="foto"]').innerHTML = `
                <i class="fas fa-cloud-upload-alt"></i>
                Seleccionar imagen del árbol
            `;
            document.querySelector('label[for="pdf"]').innerHTML = `
                <i class="fas fa-cloud-upload-alt"></i>
                Seleccionar PDF del árbol (opcional)
            `;
            if (marker) {
                marker.remove();
                marker = null;
            }
            lng = null;
            lat = null;
            updateCoordinatesDisplay();
            btn.innerHTML = originalText;
            btn.disabled = true;
            
            showNotification('¡Árbol registrado exitosamente!', 'success');
            
            setTimeout(() => {
                location.reload();
            }, 2000);
        } else {
            showNotification(data.message || 'Error al registrar el árbol', 'error');
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error completo:', error);
        showNotification('Error al registrar el árbol: ' + error.message, 'error');
        btn.innerHTML = originalText;
        btn.disabled = false;
    });
}
        function obtenerUbicacion() {
    if (navigator.geolocation) {
        
        // 1. Define las opciones para la geolocalización
        const options = {
            enableHighAccuracy: true, // Solicita GPS
            timeout: 20000,          // Espera hasta 20 segundos
            maximumAge: 0            // Sin caché
        };

        navigator.geolocation.getCurrentPosition(
            (position) => {
                const lng = position.coords.longitude;
                const lat = position.coords.latitude;

                // Muestra la precisión de la lectura (opcional, pero útil para depurar)
                console.log('Precisión de la lectura (metros):', position.coords.accuracy);

                // Reemplaza las variables globales con las constantes/variables locales
                // Si tus variables (lng, lat, marker) son globales, asegúrate de mantenerlas.
                // Usando las variables globales originales:
                window.lng = lng;
                window.lat = lat;

                updateCoordinatesDisplay(lng, lat);

                if (marker) {
                    marker.setLngLat([lng, lat]);
                } else {
                    marker = new mapboxgl.Marker({
                        draggable: true,
                        color: '#2d5016'
                    })
                    .setLngLat([lng, lat])
                    .addTo(map);

                    marker.on('dragend', function() {
                        const lngLat = marker.getLngLat();
                        window.lng = lngLat.lng;
                        window.lat = lngLat.lat;
                        updateCoordinatesDisplay(window.lng, window.lat);
                    });
                }

                map.flyTo({
                    center: [lng, lat],
                    zoom: 17
                });

                document.getElementById('agregarArbolBtn').disabled = false;
                showNotification('Ubicación obtenida correctamente (Precisión: ' + position.coords.accuracy.toFixed(2) + 'm)', 'success');
            },
            (error) => {
                // Función de error con manejo mejorado
                let message = 'Error al obtener la ubicación. ';
                switch(error.code) {
                    case error.PERMISSION_DENIED:
                        message += "Debes permitir el acceso a tu ubicación.";
                        break;
                    case error.POSITION_UNAVAILABLE:
                        message += "La información de ubicación no está disponible.";
                        break;
                    case error.TIMEOUT:
                        message += "Se agotó el tiempo de espera para obtener la ubicación de alta precisión.";
                        break;
                    case error.UNKNOWN_ERROR:
                        message += "Ocurrió un error desconocido.";
                        break;
                }
                showNotification(message, 'error');
                console.error(error);
            },
            // 2. Pasamos el objeto de opciones
            options 
        );
    } else {
        showNotification('La geolocalización no es compatible con este navegador.', 'error');
    }
}
        

        function showNotification(message, type) {
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 1rem 1.5rem;
                border-radius: 10px;
                color: white;
                font-weight: 500;
                z-index: 10000;
                animation: slideIn 0.3s ease;
                background: ${type === 'success' ? '#d4edda' : '#f8d7da'};
                color: ${type === 'success' ? '#155724' : '#721c24'};
                border: 1px solid ${type === 'success' ? '#c3e6cb' : '#f5c6cb'};
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            `;
            notification.innerHTML = `<i class="fas fa-${type === 'success' ? 'check' : 'exclamation'}-circle"></i> ${message}`;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.remove();
            }, 4000);
        }
    </script>
</body>
</html>


