<?php
// Configurar headers para JSON desde el inicio
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    header('Content-Type: application/json; charset=utf-8');
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configurar límites de subida optimizados
ini_set('upload_max_filesize', '15M');    // Aumentado para recibir archivos comprimidos
ini_set('post_max_size', '20M');          // Aumentado proporcionalmente
ini_set('max_input_time', 300);
ini_set('max_execution_time', 300);

// Detectar si es un dispositivo móvil
function isMobileDevice() {
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    return preg_match('/(android|webos|iphone|ipad|ipod|blackberry|iemobile|opera mini)/i', $userAgent);
}

// Configurar límites específicos según dispositivo
if (isMobileDevice()) {
    error_log("=== REQUEST DESDE MÓVIL DETECTADO ===");
    error_log("User-Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'No definido'));
    
    // Límites para móviles (archivos ya vienen comprimidos del frontend)
    ini_set('upload_max_filesize', '8M');     // Reducido porque vienen comprimidos
    ini_set('post_max_size', '12M');          // Ajustado
    ini_set('max_execution_time', 300);       // Más tiempo para conexiones lentas
    ini_set('max_input_time', 300);
    ini_set('memory_limit', '256M');
} else {
    // Límites normales para desktop
    ini_set('upload_max_filesize', '15M');
    ini_set('post_max_size', '20M');
    ini_set('max_execution_time', 180);
    ini_set('max_input_time', 180);
}

// Headers específicos para móviles
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    if (isMobileDevice()) {
        // Headers adicionales para móviles
        header('X-Content-Type-Options: nosniff');
        header('Connection: close'); // Cerrar conexión después de la respuesta
        
        // Log específico para debug móvil
        error_log("Content-Length móvil: " . ($_SERVER['CONTENT_LENGTH'] ?? '0'));
        error_log("Connection móvil: " . ($_SERVER['HTTP_CONNECTION'] ?? 'No definido'));
    }
}

// Función processImageFile optimizada para imágenes ya comprimidas
function processImageFile($file) {
    $isMobile = isMobileDevice();
    
    error_log("=== PROCESANDO IMAGEN " . ($isMobile ? "MÓVIL" : "DESKTOP") . " ===");
    error_log("Archivo recibido: " . print_r($file, true));
    
    // Verificar errores de upload
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        $errorMessages = [
            UPLOAD_ERR_INI_SIZE => 'Archivo demasiado grande (límite del servidor)',
            UPLOAD_ERR_FORM_SIZE => 'Archivo demasiado grande (límite del formulario)',
            UPLOAD_ERR_PARTIAL => 'El archivo se subió parcialmente - conexión interrumpida',
            UPLOAD_ERR_NO_FILE => 'No se recibió ningún archivo',
            UPLOAD_ERR_NO_TMP_DIR => 'Error del servidor: directorio temporal faltante',
            UPLOAD_ERR_CANT_WRITE => 'Error del servidor: no se puede escribir',
            UPLOAD_ERR_EXTENSION => 'Subida bloqueada por extensión PHP'
        ];
        
        $errorMsg = $errorMessages[$file['error']] ?? 'Error desconocido al subir archivo';
        
        if ($isMobile) {
            $errorMsg .= " (Móvil - Verifique su conexión)";
        }
        
        error_log("Error upload: " . $errorMsg);
        return ['success' => false, 'message' => $errorMsg];
    }
    
    $filename = $file['name'];
    $filetype = $file['type'];
    $filesize = $file['size'];
    $tmpName = $file['tmp_name'];
    
    // Verificar archivo temporal
    if (empty($tmpName) || !file_exists($tmpName)) {
        error_log("Archivo temporal no existe: " . $tmpName);
        return ['success' => false, 'message' => 'No se recibió correctamente el archivo desde el dispositivo'];
    }
    
    // Tipos MIME permitidos (más permisivos para móviles)
    $allowed = [
        "jpg" => "image/jpeg",
        "jpeg" => "image/jpeg", 
        "png" => "image/png",
        "gif" => "image/gif",
        "webp" => "image/webp",  // Android común
        "heic" => "image/heic",  // iOS común
        "heif" => "image/heif"   // iOS común
    ];
    
    // Verificar extensión
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    // Para archivos comprimidos desde JS, pueden venir como .jpg
    if ($filename === 'compressed_mobile_image.jpg') {
        $ext = 'jpg';
        error_log("Detectado archivo comprimido desde móvil");
    }
    
    if (!array_key_exists($ext, $allowed)) {
        error_log("Extensión no permitida: " . $ext);
        return ['success' => false, 'message' => "Formato no soportado ($ext). Use: JPG, PNG, GIF" . ($isMobile ? ", WEBP, HEIC" : "")];
    }
    
    // Límites ajustados para archivos ya comprimidos
    $maxSize = $isMobile ? 3 * 1024 * 1024 : 8 * 1024 * 1024; // 3MB móvil, 8MB desktop
    if ($filesize > $maxSize) {
        $maxSizeMB = round($maxSize / 1024 / 1024);
        error_log("Archivo demasiado grande: {$filesize} bytes, máximo: {$maxSize}");
        return ['success' => false, 'message' => "Imagen demasiado grande. Máximo " . ($isMobile ? "para móviles" : "") . ": {$maxSizeMB}MB"];
    }
    
    // Verificar tipo MIME de manera flexible
    $allowedMimes = array_values($allowed);
    
    if (!empty($filetype) && !in_array($filetype, $allowedMimes)) {
        // Para móviles y archivos comprimidos, intentar detectar el tipo real
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $detectedType = finfo_file($finfo, $tmpName);
            finfo_close($finfo);
            
            error_log("Tipo original: $filetype, Tipo detectado: $detectedType");
            
            if (in_array($detectedType, $allowedMimes)) {
                $filetype = $detectedType;
                error_log("Usando tipo detectado: $detectedType");
            } else {
                return ['success' => false, 'message' => "Tipo de archivo no soportado: $filetype"];
            }
        } else {
            // Sin finfo, ser más permisivo con archivos comprimidos
            if ($isMobile && (empty($filetype) || $filetype === 'application/octet-stream')) {
                // Validar que al menos sea una imagen válida
                if (@getimagesize($tmpName) !== false) {
                    error_log("Tipo MIME vacío en móvil, pero imagen válida detectada");
                } else {
                    return ['success' => false, 'message' => 'El archivo no es una imagen válida'];
                }
            } else {
                return ['success' => false, 'message' => "Tipo no válido: $filetype"];
            }
        }
    }
    
    // Validación final de imagen
    $imageInfo = @getimagesize($tmpName);
    if ($imageInfo === false) {
        error_log("getimagesize falló para: " . $tmpName);
        return ['success' => false, 'message' => 'El archivo no es una imagen válida'];
    }
    
    // Log info de la imagen
    error_log("Imagen válida - Dimensiones: {$imageInfo[0]}x{$imageInfo[1]}, Tipo: {$imageInfo[2]}");
    error_log("Tamaño final: " . number_format($filesize / 1024 / 1024, 2) . "MB");
    
    // Generar nombre único y guardar
    $newName = ($isMobile ? 'mobile_' : 'desktop_') . 'img_' . time() . '_' . uniqid() . '.' . $ext;
    $targetPath = 'uploads/trees/' . $newName;
    
    if (move_uploaded_file($tmpName, $targetPath)) {
        error_log("Imagen guardada exitosamente: " . $targetPath);
        return ['success' => true, 'path' => $targetPath];
    } else {
        error_log("Error al mover archivo: $tmpName -> $targetPath");
        return ['success' => false, 'message' => 'Error al guardar la imagen en el servidor'];
    }
}

// En el procesamiento POST, agregar log específico
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isMobileDevice()) {
        error_log("=== PROCESAMIENTO MÓVIL INICIADO ===");
        error_log("POST size: " . strlen(file_get_contents('php://input')));
        error_log("FILES count: " . (isset($_FILES) ? count($_FILES) : 0));
        
        // Verificar si hay información del dispositivo enviada desde JS
        if (isset($_POST['is_mobile'])) {
            error_log("Confirmado como móvil desde frontend: " . $_POST['is_mobile']);
        }
        if (isset($_POST['user_agent'])) {
            error_log("User agent desde frontend: " . $_POST['user_agent']);
        }
    }
}

// Función para debug de configuración PHP
function debugPhpConfig() {
    error_log("=== CONFIGURACIÓN PHP ACTUAL ===");
    error_log("upload_max_filesize: " . ini_get('upload_max_filesize'));
    error_log("post_max_size: " . ini_get('post_max_size'));
    error_log("max_file_uploads: " . ini_get('max_file_uploads'));
    error_log("max_input_time: " . ini_get('max_input_time'));
    error_log("max_execution_time: " . ini_get('max_execution_time'));
    error_log("memory_limit: " . ini_get('memory_limit'));
    error_log("file_uploads: " . (ini_get('file_uploads') ? 'ON' : 'OFF'));
    error_log("Es móvil: " . (isMobileDevice() ? 'SÍ' : 'NO'));
}

// Llamar función de debug si es necesario
if (isset($_GET['debug_config'])) {
    debugPhpConfig();
}

// Configurar zona horaria de Bolivia
date_default_timezone_set('America/La_Paz');


// En administrador
$conn = new mysqli("mysql", "root", "rootpassword", "reforest", 3306);

if ($conn->connect_error) {
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        echo json_encode(['success' => false, 'message' => 'Error de conexión: ' . $conn->connect_error]);
        exit;
    }
    die("Error de conexión: " . $conn->connect_error);
}

// Configurar MySQL para usar la zona horaria de Bolivia
$conn->query("SET time_zone = '-04:00'");

// ===============================================
// LIBRERÍAS Y DIRECTORIOS
// ===============================================

require_once 'phpqrcode/qrlib.php';

// Crear directorios necesarios
$directories = ['uploads/trees/', 'uploads/pdfs/', 'qr_codes/'];
foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }
}

// ===============================================
// FUNCIONES AUXILIARES
// ===============================================

/**
 * Obtener la URL base del proyecto
 */
function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $path = dirname($_SERVER['SCRIPT_NAME']);
    
    // Normalizar el path
    if ($path === '/' || $path === '\\') {
        $path = '';
    }
    
    return $protocol . "://" . $host . $path;
}

/**
 * Validar y procesar archivo de imagen
 */
/**
 * Validar y procesar archivo de imagen (VERSIÓN CORREGIDA)
 */

/**
 * Validar y procesar archivo PDF
 */
function processPdfFile($file) {
    $filename = $file['name'];
    $filetype = $file['type'];
    $filesize = $file['size'];
    
    // Verificar que sea PDF
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    if ($ext !== 'pdf' || $filetype !== 'application/pdf') {
        return ['success' => false, 'message' => 'Selecciona un archivo PDF válido'];
    }
    
    // Verificar tamaño (10MB máximo)
    if ($filesize > 10 * 1024 * 1024) {
        return ['success' => false, 'message' => 'El PDF es demasiado grande. Máximo: 10MB'];
    }
    
    // Generar nombre único y mover archivo
    $newName = 'pdf_' . time() . '_' . uniqid() . '.pdf';
    $targetPath = 'uploads/pdfs/' . $newName;
    
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['success' => true, 'path' => $targetPath];
    } else {
        return ['success' => false, 'message' => 'Error al guardar el PDF'];
    }
}

/**
 * Generar código QR usando API externa (sin dependencia GD)
 */
function generateQR($treeId, $url) {
    $qrFilename = 'qr_codes/qr_' . $treeId . '.png';
    
    try {
        // Eliminar QR anterior si existe
        if (file_exists($qrFilename)) {
            unlink($qrFilename);
        }
        
        // Usar API externa para generar QR
        $qrApiUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&format=png&data=' . urlencode($url);
        
        // Descargar imagen QR
        $qrData = file_get_contents($qrApiUrl);
        
        if ($qrData === false) {
            throw new Exception("No se pudo descargar el código QR desde la API");
        }
        
        // Guardar imagen
        $saved = file_put_contents($qrFilename, $qrData);
        
        if ($saved === false) {
            throw new Exception("No se pudo guardar el archivo QR");
        }
        
        if (!file_exists($qrFilename)) {
            throw new Exception("El archivo QR no se creó correctamente");
        }
        
        return ['success' => true, 'filename' => $qrFilename];
        
    } catch (Exception $e) {
        error_log("Error al generar QR: " . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

// ===============================================
// PROCESAMIENTO DEL FORMULARIO (POST)
// ===============================================

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Debug información recibida
        error_log("=== PROCESANDO NUEVO ÁRBOL ===");
        error_log("Datos POST: " . print_r($_POST, true));
        error_log("Archivos: " . print_r($_FILES, true));
        error_log("Hora Bolivia: " . date('Y-m-d H:i:s'));
        
        // ===============================================
        // RECOPILAR Y VALIDAR DATOS DEL FORMULARIO
        // ===============================================
        
        $especie = trim($_POST['especie'] ?? '');
        $nombre_comun = trim($_POST['nombre_comun'] ?? '');
        $codigo_arbol = trim($_POST['codigo_arbol'] ?? '');
        $edad = isset($_POST['edad']) && is_numeric($_POST['edad']) ? intval($_POST['edad']) : 0;
        $estado = trim($_POST['estado'] ?? '');
        $altura = isset($_POST['altura']) && is_numeric($_POST['altura']) ? floatval($_POST['altura']) : 0;
        $diametroTronco = isset($_POST['diametroTronco']) && is_numeric($_POST['diametroTronco']) ? floatval($_POST['diametroTronco']) : 0;
        $diametro_copa = isset($_POST['diametro_copa']) && is_numeric($_POST['diametro_copa']) ? floatval($_POST['diametro_copa']) : 0;
        $latitud = isset($_POST['lat']) && is_numeric($_POST['lat']) ? floatval($_POST['lat']) : 0;
        $longitud = isset($_POST['lng']) && is_numeric($_POST['lng']) ? floatval($_POST['lng']) : 0;
        $propiedad = trim($_POST['propiedad'] ?? '');
        $otb = trim($_POST['otb'] ?? '');
        $nombre_area_verde = trim($_POST['nombre_area_verde'] ?? '');
        $inspector = trim($_POST['inspector'] ?? '');
        
        // ===============================================
        // VALIDACIONES DE CAMPOS OBLIGATORIOS
        // ===============================================
        
        $validations = [
            ['field' => $especie, 'message' => 'El campo "Especie del Árbol" es obligatorio'],
            ['field' => $nombre_comun, 'message' => 'El campo "Nombre Común" es obligatorio'],
            ['field' => $codigo_arbol, 'message' => 'El código del árbol es obligatorio'],
            ['field' => $estado, 'message' => 'Debe seleccionar un estado/categoría'],
            ['field' => $propiedad, 'message' => 'El campo "Propiedad" es obligatorio'],
            ['field' => $otb, 'message' => 'El campo "OTB" es obligatorio'],
            ['field' => $nombre_area_verde, 'message' => 'El campo "Nombre del Área Verde" es obligatorio'],
            ['field' => $inspector, 'message' => 'El campo "Inspector" es obligatorio'],
        ];
        
        foreach ($validations as $validation) {
            if (empty($validation['field'])) {
                echo json_encode(['success' => false, 'message' => $validation['message']]);
                exit;
            }
        }
        
        // Validaciones numéricas
        if ($edad <= 0) {
            echo json_encode(['success' => false, 'message' => 'La edad debe ser mayor a 0']);
            exit;
        }
        
        if ($altura <= 0) {
            echo json_encode(['success' => false, 'message' => 'La altura debe ser mayor a 0']);
            exit;
        }
        
        if ($diametroTronco <= 0) {
            echo json_encode(['success' => false, 'message' => 'El diámetro del tronco debe ser mayor a 0']);
            exit;
        }
        
        if ($diametro_copa <= 0) {
            echo json_encode(['success' => false, 'message' => 'El diámetro de la copa debe ser mayor a 0']);
            exit;
        }
        
        // Validar coordenadas
        if ($latitud == 0 || $longitud == 0) {
            echo json_encode(['success' => false, 'message' => 'Debe seleccionar una ubicación válida']);
            exit;
        }
        
        // Validar formato del código
        if (!preg_match('/^[A-Za-z0-9\-_]+$/', $codigo_arbol)) {
            echo json_encode(['success' => false, 'message' => 'El código solo puede contener letras, números, guiones (-) y guiones bajos (_)']);
            exit;
        }
        
        // ===============================================
        // VERIFICAR CÓDIGO ÚNICO
        // ===============================================
        
        $checkStmt = $conn->prepare("SELECT id FROM arboles WHERE codigo_arbol = ?");
        if (!$checkStmt) {
            echo json_encode(['success' => false, 'message' => 'Error al verificar código: ' . $conn->error]);
            exit;
        }
        
        $checkStmt->bind_param("s", $codigo_arbol);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'Ya existe un árbol con el código: ' . $codigo_arbol]);
            $checkStmt->close();
            exit;
        }
        $checkStmt->close();
        
        // ===============================================
        // PROCESAR ARCHIVOS
        // ===============================================
        
        // Procesar imagen (obligatoria)
        if (!isset($_FILES['foto']) || $_FILES['foto']['error'] !== 0) {
            echo json_encode(['success' => false, 'message' => 'Debe seleccionar una imagen del árbol']);
            exit;
        }
        
        $imageResult = processImageFile($_FILES['foto']);
        if (!$imageResult['success']) {
            echo json_encode(['success' => false, 'message' => $imageResult['message']]);
            exit;
        }
        $fotoUrl = $imageResult['path'];
        
        // Procesar PDF (opcional)
        $pdfUrl = null;
        if (isset($_FILES['pdf']) && $_FILES['pdf']['error'] === 0) {
            $pdfResult = processPdfFile($_FILES['pdf']);
            if (!$pdfResult['success']) {
                echo json_encode(['success' => false, 'message' => $pdfResult['message']]);
                exit;
            }
            $pdfUrl = $pdfResult['path'];
        }
        
        // ===============================================
        // INSERTAR EN BASE DE DATOS
        // ===============================================
        
        $fecha_registro = date('Y-m-d');
        $hora_registro = date('H:i:s');
        $coordenadas = "POINT(" . $longitud . " " . $latitud . ")";
        
        error_log("Insertando árbol en BD con código: " . $codigo_arbol);
        
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
            echo json_encode(['success' => false, 'message' => 'Error al preparar consulta: ' . $conn->error]);
            exit;
        }
        
        $stmt->bind_param("ssissddssddssssssss", 
            $especie, $nombre_comun, $edad, $estado, $fotoUrl, $altura, $diametroTronco, 
            $diametro_copa, $codigo_arbol, $latitud, $longitud, $coordenadas,
            $propiedad, $otb, $nombre_area_verde, $inspector, $pdfUrl, 
            $fecha_registro, $hora_registro
        );
        
        if (!$stmt->execute()) {
            echo json_encode(['success' => false, 'message' => 'Error al guardar: ' . $stmt->error]);
            exit;
        }
        
        $lastId = $conn->insert_id;
        error_log("Árbol insertado con ID: " . $lastId);
        
        // ===============================================
        // GENERAR CÓDIGO QR
        // ===============================================
        
        $baseUrl = getBaseUrl();
        $treeUrl = $baseUrl . "/index.php?tree_id=" . $lastId;
        
        $qrResult = generateQR($lastId, $treeUrl);
        
        // Actualizar registro con URL del QR si se generó correctamente
        if ($qrResult['success']) {
            $updateStmt = $conn->prepare("UPDATE arboles SET qrUrl = ? WHERE id = ?");
            if ($updateStmt) {
                $qrFilename = $qrResult['filename'];
                $updateStmt->bind_param("si", $qrFilename, $lastId);
                $updateStmt->execute();
                $updateStmt->close();
                error_log("QR generado y actualizado en BD: " . $qrFilename);
            }
        }
        
        // ===============================================
        // RESPUESTA EXITOSA
        // ===============================================
        
        $response = [
            'success' => true, 
            'message' => 'Árbol registrado exitosamente',
            'id' => $lastId,
            'qr_url' => $treeUrl,
            'qr_file' => $qrResult['success'] ? $qrResult['filename'] : null,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        echo json_encode($response);
        $stmt->close();
        exit;
        
    } catch (Exception $e) {
        error_log("EXCEPCIÓN: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error del servidor: ' . $e->getMessage()]);
        exit;
    }
}

// ===============================================
// CONSULTAR ÁRBOLES EXISTENTES
// ===============================================

$sql = "SELECT 
    id, especie, nombre_comun, edad, estado, fotoUrl, altura, diametroTronco, 
    diametro_copa, codigo_arbol, ST_AsText(coordenadas) as coordenadas, 
    latitud, longitud, propiedad, otb, nombre_area_verde, inspector, 
    pdfUrl, qrUrl, DATE_FORMAT(fecha_registro, '%d/%m/%Y') as fecha_formato, 
    hora_registro 
FROM arboles 
ORDER BY fecha_registro DESC, hora_registro DESC";

$result = $conn->query($sql);
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
    
    <!-- =============================================== -->
    <!-- LIBRERÍAS EXTERNAS -->
    <!-- =============================================== -->
    
    <script src="https://api.mapbox.com/mapbox-gl-js/v2.8.1/mapbox-gl.js"></script>
    <link href="https://api.mapbox.com/mapbox-gl-js/v2.8.1/mapbox-gl.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;700&display=swap" rel="stylesheet">
    
    <!-- =============================================== -->
    <!-- ESTILOS CSS -->
    <!-- =============================================== -->
    
    <style>
        /* Reset y configuración base */
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

        /* =============================================== */
        /* HEADER Y NAVEGACIÓN */
        /* =============================================== */

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
        }

        .logo-img {
            height: 50px;
            width: auto;
            vertical-align: middle;
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

        /* =============================================== */
        /* LAYOUT PRINCIPAL */
        /* =============================================== */

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

        /* =============================================== */
        /* FORMULARIO */
        /* =============================================== */

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
        }

        .form-title i {
            margin-right: 0.5rem;
            color: #3ebeab;
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
            color: #482e83;
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

        .form-input, .form-select {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: white;
        }

        .form-input:focus, .form-select:focus {
            outline: none;
            border-color: #482e83;
            box-shadow: 0 0 0 2px rgba(72, 46, 131, 0.1);
        }

        .form-select {
            cursor: pointer;
        }

        /* =============================================== */
        /* SUBIDA DE ARCHIVOS */
        /* =============================================== */

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
            border-color: #3ebeab;
            background: #f0f8f0;
            color: #482e83;
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
            color: #482e83;
        }

        /* =============================================== */
        /* BOTONES */
        /* =============================================== */

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
            background: #3a235f;
        }

        .btn-secondary {
            background: #3ebeab;
            color: white;
        }

        .btn-secondary:hover {
            background: #2d9284;
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

        /* =============================================== */
        /* MAPA */
        /* =============================================== */

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
        }

        .map-title i {
            margin-right: 0.5rem;
            color: #3ebeab;
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
            color: #3ebeab;
        }

        /* =============================================== */
        /* MARCADORES Y POPUPS */
        /* =============================================== */

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
            background: #3ebeab;
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
            background: #2d9284;
            color: white;
        }

        /* =============================================== */
        /* UTILITARIOS */
        /* =============================================== */

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
            color: #3ebeab;
        }

        .success-message {
            background: #d4edda;
            color: #482e83;
            border: 1px solid #c3e6cb;
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
            display: none;
        }

        /* =============================================== */
        /* ANIMACIONES */
        /* =============================================== */

        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        /* =============================================== */
        /* RESPONSIVE DESIGN */
        /* =============================================== */

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
    </style>
</head>

<body>
    <!-- =============================================== -->
    <!-- HEADER Y NAVEGACIÓN -->
    <!-- =============================================== -->
    
    <header class="header">
        <nav class="nav">
            <a href="#home" class="logo">
                <img src="img/logoemavrita.png" alt="Emavra Logo" class="logo-img">
            </a>
            <ul class="nav-links">
                <li><a href="index.php">Volver Al Inicio</a></li>
            </ul>
        </nav>
    </header>

    <!-- =============================================== -->
    <!-- CONTENIDO PRINCIPAL -->
    <!-- =============================================== -->
    
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
            <!-- =============================================== -->
            <!-- FORMULARIO DE REGISTRO -->
            <!-- =============================================== -->
            
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
                            <label class="form-label">
                                <i class="fas fa-tree"></i> Especie Del Árbol
                            </label>
                            <input type="text" name="especie" class="form-input" placeholder="Ej: Molle" required />
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-seedling"></i> Nombre Común
                            </label>
                            <input type="text" name="nombre_comun" class="form-input" placeholder="Ej: Ceibo" required />
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

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-map-marker-alt"></i> Latitud
                                </label>
                                <input type="number" step="0.000001" name="lat" id="latInput" class="form-input" placeholder="Ej: -17.393838" />
                            </div>
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-map-marker-alt"></i> Longitud
                                </label>
                                <input type="number" step="0.000001" name="lng" id="lngInput" class="form-input" placeholder="Ej: -66.156977" />
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
                                <i class="fas fa-file-pdf"></i> PDF Informativo (Opcional)
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

            <!-- =============================================== -->
            <!-- MAPA DE UBICACIÓN -->
            <!-- =============================================== -->
            
            <div class="map-card">
                <h2 class="map-title">
                    <i class="fas fa-map"></i>
                    Mapa de Ubicación
                </h2>
                <div id="map"></div>
                
                <div class="coordinates-display" id="coordinatesDisplay">
                    <strong>Coordenadas actuales:</strong><br>
                    <span id="currentCoords">Selecciona un punto en el mapa</span>
                </div>
            </div>
        </div>
    </div>

    <!-- =============================================== -->
    <!-- JAVASCRIPT -->
    <!-- =============================================== -->
    
    <script>
        // ===============================================
        // CONFIGURACIÓN INICIAL
        // ===============================================
        
        mapboxgl.accessToken = 'pk.eyJ1IjoiYWxlc3NpcyIsImEiOiJjbGcxbHBtbHQwdDU5M2RubDFodjY3a2x0In0.NXe43GdM4PJBj7ow0Dnkpw';

        const map = new mapboxgl.Map({
            container: 'map',
            style: 'mapbox://styles/mapbox/streets-v11',
            center: [-66.156977, -17.393838],
            zoom: 17,
            pitch: 0,
            bearing: -17.6
        });

        // Variables globales
        let marker, lng, lat;
        let latInput, lngInput;

        // ===============================================
        // FUNCIONES AUXILIARES
        // ===============================================
        
        /**
         * Obtener la URL base del proyecto
         */
        function getBaseUrl() {
            const protocol = window.location.protocol;
            const hostname = window.location.hostname;
            const port = window.location.port ? ':' + window.location.port : '';
            const pathname = window.location.pathname;
            const directory = pathname.substring(0, pathname.lastIndexOf('/'));
            
            return protocol + '//' + hostname + port + directory;
        }

        /**
         * Actualizar display de coordenadas y inputs
         */
        function updateCoordinatesDisplay(longitude, latitude) {
            const coordsElement = document.getElementById('currentCoords');
            if (longitude && latitude) {
                coordsElement.innerHTML = `
                    <strong>Latitud:</strong> ${latitude.toFixed(6)}<br>
                    <strong>Longitud:</strong> ${longitude.toFixed(6)}
                `;
                
                if (latInput && lngInput) {
                    latInput.value = latitude.toFixed(6);
                    lngInput.value = longitude.toFixed(6);
                }
            } else {
                coordsElement.textContent = 'Selecciona un punto en el mapa';
                if (latInput && lngInput) {
                    latInput.value = '';
                    lngInput.value = '';
                }
            }
        }

        /**
         * Actualizar mapa desde inputs de coordenadas
         */
        function updateMapFromInputs() {
            if (!latInput || !lngInput) return;
            
            const inputLat = parseFloat(latInput.value);
            const inputLng = parseFloat(lngInput.value);
            
            if (isNaN(inputLat) || isNaN(inputLng)) return;
            
            if (inputLat < -90 || inputLat > 90 || inputLng < -180 || inputLng > 180) {
                showNotification('Las coordenadas están fuera del rango válido', 'error');
                return;
            }
            
            lng = inputLng;
            lat = inputLat;
            
            if (marker) {
                marker.setLngLat([inputLng, inputLat]);
            } else {
                marker = new mapboxgl.Marker({
                    draggable: true,
                    color: '#4a7c59'
                })
                .setLngLat([inputLng, inputLat])
                .addTo(map);

                marker.on('dragend', function() {
                    const lngLat = marker.getLngLat();
                    lng = lngLat.lng;
                    lat = lngLat.lat;
                    updateCoordinatesDisplay(lng, lat);
                });
            }
            
            map.flyTo({
                center: [inputLng, inputLat],
                zoom: 17
            });
            
            const coordsElement = document.getElementById('currentCoords');
            coordsElement.innerHTML = `
                <strong>Latitud:</strong> ${inputLat.toFixed(6)}<br>
                <strong>Longitud:</strong> ${inputLng.toFixed(6)}
            `;
        }

        /**
         * Mostrar notificación temporal
         */
        function showNotification(message, type) {
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 1rem 1.5rem;
                border-radius: 10px;
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

        // ===============================================
        // EVENT LISTENERS DE ARCHIVOS
        // ===============================================
        
        // Preview de imagen
       
// Función específica para detectar y manejar problemas de móviles
function isMobileDevice() {
    return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
}

function getConnectionType() {
    return navigator.connection ? navigator.connection.effectiveType : 'unknown';
}

// Event listener mejorado para archivos en móviles
document.getElementById('foto').addEventListener('change', function(e) {
    const file = e.target.files[0];
    const preview = document.getElementById('filePreview');
    
    console.log('=== DEBUG MÓVIL ARCHIVO ===');
    console.log('Es móvil:', isMobileDevice());
    console.log('Conexión:', getConnectionType());
    console.log('Archivo:', file);
    
    if (file) {
        // Límites específicos para móviles
        const maxSize = isMobileDevice() ? 5 * 1024 * 1024 : 8 * 1024 * 1024; // 5MB para móvil, 8MB para desktop
        
        if (file.size > maxSize) {
            const maxSizeMB = Math.round(maxSize / 1024 / 1024);
            showNotification(`Imagen demasiado grande para móviles. Máximo: ${maxSizeMB}MB`, 'error');
            e.target.value = '';
            preview.innerHTML = '';
            return;
        }
        
        // Tipos MIME más permisivos para móviles
        const allowedTypes = [
            'image/jpeg', 
            'image/jpg',
            'image/png', 
            'image/gif',
            'image/webp',    // Común en Android
            'image/heic',    // Común en iPhone
            'image/heif',    // Formato iOS
            ''               // Algunos móviles no envían tipo MIME
        ];
        
        let validType = false;
        if (allowedTypes.includes(file.type)) {
            validType = true;
        } else if (!file.type || file.type === '') {
            // Si no hay tipo MIME, validar por extensión
            const ext = file.name.split('.').pop().toLowerCase();
            validType = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'heic', 'heif'].includes(ext);
            console.log('Validación por extensión:', ext, validType);
        }
        
        if (!validType) {
            showNotification(`Formato no soportado: ${file.type}. Use JPG, PNG o GIF`, 'error');
            e.target.value = '';
            preview.innerHTML = '';
            return;
        }
        
        // Preview optimizado para móviles
        const reader = new FileReader();
        reader.onload = function(event) {
            try {
                const img = new Image();
                img.onload = function() {
                    // Crear canvas para redimensionar si es muy grande
                    const canvas = document.createElement('canvas');
                    const ctx = canvas.getContext('2d');
                    
                    // Redimensionar para preview si es necesario
                    let { width, height } = img;
                    const maxDimension = 300;
                    
                    if (width > maxDimension || height > maxDimension) {
                        if (width > height) {
                            height = (height * maxDimension) / width;
                            width = maxDimension;
                        } else {
                            width = (width * maxDimension) / height;
                            height = maxDimension;
                        }
                    }
                    
                    canvas.width = width;
                    canvas.height = height;
                    ctx.drawImage(img, 0, 0, width, height);
                    
                    preview.innerHTML = `
                        <img src="${canvas.toDataURL('image/jpeg', 0.8)}" alt="Vista previa" style="max-width: 200px; max-height: 150px; border-radius: 4px;">
                        <div class="file-info">
                            <i class="fas fa-mobile-alt"></i> ${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)
                            <br><small>Optimizado para móvil - Tipo: ${file.type || 'Detectado por extensión'}</small>
                        </div>
                    `;
                };
                img.src = event.target.result;
                
            } catch (error) {
                console.error('Error creando preview:', error);
                preview.innerHTML = `
                    <div class="file-info">
                        <i class="fas fa-mobile-alt"></i> ${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)
                        <br><small style="color: orange;">Preview no disponible en este dispositivo</small>
                    </div>
                `;
            }
        };
        
        reader.onerror = function() {
            preview.innerHTML = `
                <div class="file-info">
                    <i class="fas fa-mobile-alt"></i> ${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)
                    <br><small>Archivo válido - Preview no disponible</small>
                </div>
            `;
        };
        
        reader.readAsDataURL(file);
        
        document.querySelector('label[for="foto"]').innerHTML = `
            <i class="fas fa-check-circle"></i>
            ${isMobileDevice() ? 'Foto móvil' : 'Imagen'} seleccionada: ${file.name}
        `;
    }
});

        // Preview de PDF
        document.getElementById('pdf').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const preview = document.getElementById('pdfPreview');
            
            if (file) {
                if (file.type !== 'application/pdf') {
                    showNotification('Selecciona un archivo PDF válido', 'error');
                    e.target.value = '';
                    preview.innerHTML = '';
                    return;
                }
                
                const maxSize = 10 * 1024 * 1024;
                if (file.size > maxSize) {
                    showNotification('El PDF es demasiado grande. Máximo: 10MB', 'error');
                    e.target.value = '';
                    preview.innerHTML = '';
                    return;
                }
                
                preview.innerHTML = `
                    <div class="file-info">
                        <i class="fas fa-file-pdf"></i> ${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)
                    </div>
                `;
                
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

        // ===============================================
        // CONFIGURACIÓN DEL MAPA
        // ===============================================
        
        map.on('load', function() {
            const arboles = <?php echo json_encode($arboles); ?>;
            const baseUrl = getBaseUrl();
            
            arboles.forEach(arbol => {
                const el = document.createElement('div');
                el.className = 'tree-marker';

                // Color según estado
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
                
                const correctQrUrl = `${baseUrl}/index.php?tree_id=${arbol.id}`;

                const treeMarker = new mapboxgl.Marker(el)
                    .setLngLat([parseFloat(arbol.longitud), parseFloat(arbol.latitud)])
                    .addTo(map);

                const popup = new mapboxgl.Popup({
                        offset: 25
                    })
                    .setHTML(`
                    <div style="max-height: 250px; overflow-y: auto; padding-right: 5px;">
                        <h3><i class="fas fa-tree"></i> ${arbol.especie}</h3>
                        
                        ${arbol.fotoUrl ? `<img src="${baseUrl}/${arbol.fotoUrl}" alt="Foto del árbol" style="width: 100%; height: 180px; object-fit: cover;" />` : ''}
                        
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
                        
                        <div style="text-align: center; margin-top: 15px;">
                            ${arbol.qrUrl ? `<img src="${baseUrl}/${arbol.qrUrl}" 
                                 alt="QR del árbol" 
                                 style="width: 80px; height: 80px; margin-bottom: 10px; border: 1px solid #ddd; border-radius: 4px;" />` : 
                                 `<img src="https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=${encodeURIComponent(correctQrUrl)}" 
                                 alt="QR del árbol" 
                                 style="width: 80px; height: 80px; margin-bottom: 10px; border: 1px solid #ddd; border-radius: 4px;" />`}
                            ${arbol.pdfUrl ? `<br><a href="${baseUrl}/${arbol.pdfUrl}" target="_blank" class="view-tree-btn">
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

        // ===============================================
        // FUNCIONES DE UBICACIÓN
        // ===============================================
        
        /**
         * Confirmar ubicación seleccionada
         */
        function confirmarUbicacion() {
            const inputLat = latInput && !isNaN(parseFloat(latInput.value)) ? parseFloat(latInput.value) : null;
            const inputLng = lngInput && !isNaN(parseFloat(lngInput.value)) ? parseFloat(lngInput.value) : null;
            
            const finalLat = inputLat !== null ? inputLat : lat;
            const finalLng = inputLng !== null ? inputLng : lng;
            
            if (finalLng && finalLat && finalLat !== 0 && finalLng !== 0) {
                lng = finalLng;
                lat = finalLat;
                
                if (latInput && latInput.value !== finalLat.toString()) {
                    latInput.value = finalLat.toFixed(6);
                }
                if (lngInput && lngInput.value !== finalLng.toString()) {
                    lngInput.value = finalLng.toFixed(6);
                }
                
                document.getElementById('agregarArbolBtn').disabled = false;
                showNotification('Ubicación confirmada correctamente', 'success');
            } else {
                showNotification('Ingresa o selecciona una ubicación válida en el mapa', 'error');
            }
        }

        /**
         * Obtener ubicación actual del usuario
         */
        function obtenerUbicacion() {
            if (navigator.geolocation) {
                const options = {
                    enableHighAccuracy: true,
                    timeout: 20000,
                    maximumAge: 0
                };

                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        const newLng = position.coords.longitude;
                        const newLat = position.coords.latitude;

                        console.log('Precisión GPS:', position.coords.accuracy + 'm');

                        lng = newLng;
                        lat = newLat;

                        updateCoordinatesDisplay(newLng, newLat);

                        if (marker) {
                            marker.setLngLat([newLng, newLat]);
                        } else {
                            marker = new mapboxgl.Marker({
                                draggable: true,
                                color: '#2d5016'
                            })
                            .setLngLat([newLng, newLat])
                            .addTo(map);

                            marker.on('dragend', function() {
                                const lngLat = marker.getLngLat();
                                lng = lngLat.lng;
                                lat = lngLat.lat;
                                updateCoordinatesDisplay(lng, lat);
                            });
                        }

                        map.flyTo({
                            center: [newLng, newLat],
                            zoom: 17
                        });

                        document.getElementById('agregarArbolBtn').disabled = false;
                        showNotification(`Ubicación obtenida (Precisión: ${position.coords.accuracy.toFixed(2)}m)`, 'success');
                    },
                    (error) => {
                        let message = 'Error al obtener la ubicación. ';
                        switch(error.code) {
                            case error.PERMISSION_DENIED:
                                message += "Permite el acceso a tu ubicación.";
                                break;
                            case error.POSITION_UNAVAILABLE:
                                message += "La ubicación no está disponible.";
                                break;
                            case error.TIMEOUT:
                                message += "Tiempo agotado para obtener ubicación GPS.";
                                break;
                            default:
                                message += "Error desconocido.";
                                break;
                        }
                        showNotification(message, 'error');
                        console.error('Error geolocalización:', error);
                    },
                    options 
                );
            } else {
                showNotification('La geolocalización no es compatible con este navegador.', 'error');
            }
        }

        // ===============================================
        // FUNCIÓN PRINCIPAL DE ENVÍO
        // ===============================================
        
        /**
         * Enviar formulario al servidor
         */
        function submitForm() {
    console.log("=== INICIANDO ENVÍO DE FORMULARIO ===");
    console.log("Dispositivo móvil:", isMobileDevice());
    console.log("Navigator online:", navigator.onLine);
    console.log("URL actual:", window.location.href);
    
    const form = document.getElementById('arbolForm');
    const formData = new FormData(form);
    
    // ===============================================
    // VALIDACIONES DE ARCHIVO DE IMAGEN
    // ===============================================
    
    const fotoInput = document.getElementById('foto');
    const fotoFile = fotoInput.files[0];
    
    console.log('=== DEBUG VALIDACIÓN FOTO ===');
    console.log('Input foto:', fotoInput);
    console.log('Archivos en input:', fotoInput.files);
    console.log('Primer archivo:', fotoFile);
    
    if (!fotoFile) {
        showNotification('Debe seleccionar una imagen del árbol', 'error');
        return;
    }
    
    // Validación adicional del archivo
    if (fotoFile.size === 0) {
        showNotification('El archivo de imagen está vacío', 'error');
        return;
    }
    
    // Límite diferente para móviles
    const maxSize = isMobileDevice() ? 5 * 1024 * 1024 : 8 * 1024 * 1024; // 5MB móvil, 8MB desktop
    if (fotoFile.size > maxSize) {
        const maxMB = Math.round(maxSize / 1024 / 1024);
        showNotification(`La imagen es demasiado grande (máx. ${maxMB}MB${isMobileDevice() ? ' para móviles' : ''})`, 'error');
        return;
    }
    
    // ===============================================
    // VALIDACIONES DE CAMPOS OBLIGATORIOS
    // ===============================================
    
    const especie = form.querySelector('[name="especie"]').value.trim();
    const codigo_arbol = form.querySelector('[name="codigo_arbol"]').value.trim();
    
    if (!especie) {
        showNotification('El campo "Especie del Árbol" es obligatorio', 'error');
        return;
    }
    
    if (!codigo_arbol) {
        showNotification('El código del árbol es obligatorio', 'error');
        return;
    }
    
    if (!/^[A-Za-z0-9\-_]+$/.test(codigo_arbol)) {
        showNotification('El código solo puede contener letras, números, guiones (-) y guiones bajos (_)', 'error');
        return;
    }
    
    // ===============================================
    // VALIDACIÓN DE COORDENADAS
    // ===============================================
    
    const finalLat = latInput && !isNaN(parseFloat(latInput.value)) ? parseFloat(latInput.value) : lat;
    const finalLng = lngInput && !isNaN(parseFloat(lngInput.value)) ? parseFloat(lngInput.value) : lng;

    if (!finalLng || !finalLat || finalLng === 0 || finalLat === 0) {
        showNotification('Selecciona o ingresa una ubicación válida', 'error');
        return;
    }
    
    // Agregar coordenadas finales al FormData
    formData.set('lng', finalLng);
    formData.set('lat', finalLat);
    
    // Agregar información del dispositivo para debug en servidor
    formData.set('is_mobile', isMobileDevice() ? '1' : '0');
    formData.set('user_agent', navigator.userAgent.substring(0, 100));
    
    // ===============================================
    // DEBUG FORMDATA
    // ===============================================
    
    console.log("=== DATOS FORMDATA ===");
    let totalSize = 0;
    for (let [key, value] of formData.entries()) {
        if (value instanceof File) {
            totalSize += value.size;
            console.log(`${key}: [File] ${value.name} (${value.size} bytes, ${value.type || 'sin tipo'})`);
        } else {
            totalSize += new Blob([value]).size;
            console.log(`${key}: "${value}"`);
        }
    }
    console.log("Tamaño total FormData:", (totalSize / 1024 / 1024).toFixed(2) + " MB");

    // ===============================================
    // PREPARAR ENVÍO CON CONFIGURACIÓN MÓVIL
    // ===============================================
    
    const btn = document.getElementById('agregarArbolBtn');
    const originalText = btn.innerHTML;
    btn.innerHTML = `<i class="fas fa-spinner fa-spin"></i> ${isMobileDevice() ? 'Subiendo desde móvil...' : 'Registrando árbol...'}`;
    btn.disabled = true;

    // Timeout más largo para móviles (3 min vs 2 min)
    const controller = new AbortController();
    const timeoutMs = isMobileDevice() ? 180000 : 120000;
    const timeoutId = setTimeout(() => {
        controller.abort();
        console.log('Timeout activado después de', timeoutMs, 'ms');
    }, timeoutMs);

    // ===============================================
    // ENVÍO AJAX OPTIMIZADO PARA MÓVILES
    // ===============================================

    fetch('administrador.php', {
        method: 'POST',
        body: formData,
        signal: controller.signal,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-Mobile-Device': isMobileDevice() ? '1' : '0'
        },
        // Configuraciones adicionales para móviles
        keepalive: false,  // No mantener conexión viva
        cache: 'no-cache'  // No usar caché
    })
    .then(response => {
        clearTimeout(timeoutId);
        
        console.log('=== RESPUESTA DEL SERVIDOR ===');
        console.log('Estado:', response.status);
        console.log('StatusText:', response.statusText);
        console.log('OK:', response.ok);
        console.log('Tipo:', response.type);
        console.log('URL:', response.url);
        
        // Verificar headers de respuesta
        const contentType = response.headers.get('content-type');
        console.log('Content-Type:', contentType);
        
        if (!response.ok) {
            // Errores específicos por código de estado
            if (response.status === 413) {
                throw new Error(`Archivo demasiado grande para el servidor${isMobileDevice() ? ' móvil' : ''} (413)`);
            } else if (response.status === 502) {
                throw new Error('Error del servidor - gateway (502)');
            } else if (response.status === 504) {
                throw new Error(`Timeout del servidor${isMobileDevice() ? ' - conexión móvil lenta' : ''} (504)`);
            } else if (response.status === 0) {
                throw new Error('Sin conexión al servidor');
            } else if (response.status === 500) {
                throw new Error('Error interno del servidor (500)');
            } else {
                throw new Error(`Error HTTP ${response.status}: ${response.statusText}`);
            }
        }
        
        // Verificar que la respuesta sea JSON
        if (contentType && !contentType.includes('application/json')) {
            console.warn('Respuesta no es JSON:', contentType);
        }
        
        return response.text();
    })
    .then(responseText => {
        console.log('=== PROCESANDO RESPUESTA ===');
        console.log('Longitud respuesta:', responseText.length);
        console.log('Primeros 200 caracteres:', responseText.substring(0, 200));
        
        // Limpiar respuesta
        const cleanResponseText = responseText.trim();
        
        // Si la respuesta está vacía
        if (cleanResponseText === '') {
            throw new Error('El servidor devolvió una respuesta vacía');
        }
        
        let data;
        try {
            data = JSON.parse(cleanResponseText);
            console.log('JSON parseado exitosamente:', data);
        } catch (e) {
            console.error('Error parsing JSON:', e);
            console.error('Respuesta completa:', cleanResponseText);
            
            // Análisis detallado del error
            if (cleanResponseText.includes('<!DOCTYPE') || cleanResponseText.includes('<html')) {
                throw new Error('El servidor devolvió HTML en lugar de JSON (posible error 500)');
            } else if (cleanResponseText.includes('Fatal error')) {
                const errorMatch = cleanResponseText.match(/Fatal error:([^<\n]+)/);
                const errorMsg = errorMatch ? errorMatch[1].trim() : 'Error PHP';
                throw new Error('Error PHP: ' + errorMsg);
            } else if (cleanResponseText.includes('Parse error')) {
                throw new Error('Error de sintaxis PHP en el servidor');
            } else if (cleanResponseText.includes('Warning') || cleanResponseText.includes('Notice')) {
                throw new Error('Advertencias PHP interfirieron con la respuesta JSON');
            } else if (cleanResponseText.startsWith('{') || cleanResponseText.startsWith('[')) {
                throw new Error('JSON malformado: ' + e.message);
            } else {
                throw new Error('Respuesta inválida del servidor: ' + cleanResponseText.substring(0, 100));
            }
        }
        
        // Procesar respuesta exitosa
        if (data && data.success) {
            console.log('=== REGISTRO EXITOSO ===');
            
            // Mostrar mensaje de éxito
            document.getElementById('successMessage').style.display = 'block';
            
            // Limpiar formulario
            form.reset();
            document.getElementById('filePreview').innerHTML = '';
            document.getElementById('pdfPreview').innerHTML = '';
            
            // Resetear labels de archivos
            document.querySelector('label[for="foto"]').innerHTML = `
                <i class="fas fa-cloud-upload-alt"></i>
                Seleccionar imagen del árbol
            `;
            document.querySelector('label[for="pdf"]').innerHTML = `
                <i class="fas fa-cloud-upload-alt"></i>
                Seleccionar PDF del árbol (opcional)
            `;
            
            // Limpiar mapa
            if (marker) {
                marker.remove();
                marker = null;
            }
            lng = null;
            lat = null;
            updateCoordinatesDisplay();
            
            // Resetear botón
            btn.innerHTML = originalText;
            btn.disabled = true;
            
            showNotification(`¡Árbol registrado exitosamente${isMobileDevice() ? ' desde móvil' : ''}!`, 'success');
            
            // Recargar página después de 3 segundos (más tiempo para móviles)
            setTimeout(() => {
                location.reload();
            }, isMobileDevice() ? 3000 : 2000);
            
        } else {
            // Error reportado por el servidor
            const errorMessage = (data && data.message) ? data.message : 'Error desconocido al registrar el árbol';
            console.log('Error del servidor:', errorMessage);
            showNotification(errorMessage, 'error');
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    })
    .catch(error => {
        clearTimeout(timeoutId);
        
        console.error('=== ERROR EN CATCH ===');
        console.error('Tipo de error:', error.name);
        console.error('Mensaje:', error.message);
        console.error('Stack:', error.stack);
        
        // Restaurar botón
        btn.innerHTML = originalText;
        btn.disabled = false;
        
        // Mensajes de error específicos
        if (error.name === 'AbortError') {
            showNotification(`Tiempo agotado. ${isMobileDevice() ? 'Verifique su conexión móvil e intente con una imagen más pequeña.' : 'El servidor tardó demasiado en responder.'}`, 'error');
        } else if (error.message.includes('Failed to fetch')) {
            if (!navigator.onLine) {
                showNotification('Sin conexión a internet. Verifique su conexión.', 'error');
            } else if (isMobileDevice()) {
                showNotification('Error de conexión móvil. Intente:\n• Cambiar de WiFi a datos móviles (o viceversa)\n• Usar una imagen más pequeña\n• Intentar en unos minutos', 'error');
            } else {
                showNotification('Error de conexión. Verifique su conexión a internet e intente nuevamente.', 'error');
            }
        } else if (error.message.includes('NetworkError')) {
            showNotification(`Error de red${isMobileDevice() ? ' móvil' : ''}. Intente nuevamente.`, 'error');
        } else {
            showNotification('Error: ' + error.message, 'error');
        }
    });
}

/**
 * Comprimir imagen automáticamente para móviles
 */
function compressImageForMobile(file, options = {}) {
    return new Promise((resolve, reject) => {
        console.log('=== INICIANDO COMPRESIÓN MÓVIL ===');
        console.log('Archivo original:', file.name, (file.size / 1024 / 1024).toFixed(2) + 'MB');
        
        const isMobile = isMobileDevice();
        
        // Configuración por defecto optimizada para móviles
        const config = {
            maxWidth: isMobile ? 1200 : 1920,        
            maxHeight: isMobile ? 1200 : 1920,
            quality: isMobile ? 0.6 : 0.8,           
            maxSizeMB: isMobile ? 1.8 : 3.0,         
            outputFormat: 'image/jpeg',               
            ...options
        };
        
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        const img = new Image();
        
        img.onload = function() {
            try {
                console.log('Imagen cargada:', img.width + 'x' + img.height);
                
                // Calcular nuevas dimensiones manteniendo proporción
                let { width, height } = img;
                const aspectRatio = width / height;
                
                // Redimensionar si excede los límites
                if (width > config.maxWidth) {
                    width = config.maxWidth;
                    height = width / aspectRatio;
                }
                
                if (height > config.maxHeight) {
                    height = config.maxHeight;
                    width = height * aspectRatio;
                }
                
                console.log('Nuevas dimensiones:', Math.round(width) + 'x' + Math.round(height));
                
                // Configurar canvas
                canvas.width = width;
                canvas.height = height;
                
                // Para imágenes JPEG, usar fondo blanco
                if (config.outputFormat === 'image/jpeg') {
                    ctx.fillStyle = '#FFFFFF';
                    ctx.fillRect(0, 0, width, height);
                }
                
                // Dibujar imagen redimensionada con suavizado
                ctx.imageSmoothingEnabled = true;
                ctx.imageSmoothingQuality = 'high';
                ctx.drawImage(img, 0, 0, width, height);
                
                // Comprimir iterativamente hasta alcanzar el tamaño objetivo
                compressIteratively(canvas, config, file.name)
                    .then(resolve)
                    .catch(reject);
                    
            } catch (error) {
                console.error('Error procesando imagen:', error);
                reject(new Error('Error al procesar la imagen: ' + error.message));
            }
        };
        
        img.onerror = function() {
            reject(new Error('No se pudo cargar la imagen'));
        };
        
        // Crear URL para la imagen
        img.src = URL.createObjectURL(file);
    });
}

/**
 * Comprimir iterativamente hasta alcanzar el tamaño objetivo
 */
function compressIteratively(canvas, config, originalName) {
    return new Promise((resolve, reject) => {
        let quality = config.quality;
        let attempt = 0;
        const maxAttempts = 8;
        const targetSizeBytes = config.maxSizeMB * 1024 * 1024;
        
        function tryCompress() {
            attempt++;
            console.log(`Intento de compresión ${attempt}/${maxAttempts}, calidad: ${(quality * 100).toFixed(0)}%`);
            
            canvas.toBlob(function(blob) {
                if (!blob) {
                    reject(new Error('Error al generar imagen comprimida'));
                    return;
                }
                
                console.log('Tamaño generado:', (blob.size / 1024 / 1024).toFixed(2) + 'MB');
                
                // Si el tamaño es aceptable o ya hicimos muchos intentos
                if (blob.size <= targetSizeBytes || attempt >= maxAttempts || quality <= 0.1) {
                    console.log('=== COMPRESIÓN COMPLETADA ===');
                    console.log('Tamaño final:', (blob.size / 1024 / 1024).toFixed(2) + 'MB');
                    
                    // Crear nombre para archivo comprimido
                    const compressedName = originalName.includes('compressed_mobile_') ? 
                        originalName : 
                        'compressed_mobile_' + originalName.replace(/\.[^/.]+$/, '.jpg');
                    
                    // Convertir blob a File
                    const compressedFile = new File(
                        [blob], 
                        compressedName, 
                        { 
                            type: config.outputFormat,
                            lastModified: Date.now()
                        }
                    );
                    
                    resolve(compressedFile);
                } else {
                    // Reducir calidad y volver a intentar
                    quality = Math.max(0.1, quality - 0.1);
                    setTimeout(tryCompress, 100);
                }
            }, config.outputFormat, quality);
        }
        
        tryCompress();
    });
}

/**
 * Mostrar preview de imagen comprimida
 */
function showCompressedPreview(originalFile, compressedFile, previewElement) {
    const originalSizeMB = (originalFile.size / 1024 / 1024).toFixed(2);
    const compressedSizeMB = (compressedFile.size / 1024 / 1024).toFixed(2);
    const reductionPercent = (((originalFile.size - compressedFile.size) / originalFile.size) * 100).toFixed(1);
    
    const reader = new FileReader();
    reader.onload = function(e) {
        previewElement.innerHTML = `
            <div style="text-align: center;">
                <img src="${e.target.result}" alt="Vista previa comprimida" 
                     style="max-width: 200px; max-height: 150px; border-radius: 4px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                
                <div class="file-info" style="margin-top: 10px; padding: 10px; background: #e8f5e8; border-radius: 4px;">
                    <div style="display: flex; align-items: center; justify-content: center; margin-bottom: 8px;">
                        <i class="fas fa-mobile-alt" style="color: #28a745; margin-right: 5px;"></i>
                        <strong>Imagen Optimizada para Móvil</strong>
                    </div>
                    
                    <div style="font-size: 0.9em; color: #495057;">
                        <div style="margin-bottom: 4px;">
                            <span style="color: #dc3545;">Original:</span> ${originalSizeMB} MB
                        </div>
                        <div style="margin-bottom: 4px;">
                            <span style="color: #28a745;">Comprimida:</span> ${compressedSizeMB} MB
                        </div>
                        <div style="font-weight: bold; color: #28a745;">
                            <i class="fas fa-arrow-down"></i> Reducción: ${reductionPercent}%
                        </div>
                    </div>
                    
                    ${compressedFile.size > 2 * 1024 * 1024 ? 
                        '<div style="color: #856404; font-size: 0.8em; margin-top: 5px;"><i class="fas fa-exclamation-triangle"></i> Aún puede ser grande para conexiones lentas</div>' : 
                        '<div style="color: #155724; font-size: 0.8em; margin-top: 5px;"><i class="fas fa-check-circle"></i> Tamaño optimizado para móviles</div>'
                    }
                </div>
            </div>
        `;
    };
    reader.readAsDataURL(compressedFile);
}

        // ===============================================
        // INICIALIZACIÓN
        // ===============================================
        
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM cargado - Inicializando aplicación');
            
            // Inicializar referencias a inputs de coordenadas
            latInput = document.getElementById('latInput');
            lngInput = document.getElementById('lngInput');

            const fotoInput = document.getElementById('foto');
    const preview = document.getElementById('filePreview');
    
    // Remover event listeners existentes clonando el elemento
    const newFotoInput = fotoInput.cloneNode(true);
    fotoInput.parentNode.replaceChild(newFotoInput, fotoInput);

    newFotoInput.addEventListener('change', async function(e) {
        const file = e.target.files[0];
        
        console.log('=== NUEVO ARCHIVO SELECCIONADO ===');
        console.log('Es móvil:', isMobileDevice());
        console.log('Archivo:', file);
        
        if (!file) {
            preview.innerHTML = '';
            document.querySelector('label[for="foto"]').innerHTML = `
                <i class="fas fa-cloud-upload-alt"></i>
                Seleccionar imagen del árbol
            `;
            return;
        }

        // Mostrar estado de procesamiento
        preview.innerHTML = `
            <div style="text-align: center; padding: 20px;">
                <i class="fas fa-spinner fa-spin" style="font-size: 2em; color: #007bff; margin-bottom: 10px;"></i>
                <div style="color: #495057;">
                    <strong>Procesando imagen${isMobileDevice() ? ' móvil' : ''}...</strong>
                    <br><small>Optimizando tamaño y calidad</small>
                </div>
            </div>
        `;
        
        document.querySelector('label[for="foto"]').innerHTML = `
            <i class="fas fa-spinner fa-spin"></i>
            Procesando imagen...
        `;

        try {
            // Verificación básica del archivo
            if (!file.type.startsWith('image/')) {
                throw new Error('El archivo seleccionado no es una imagen válida');
            }
            
            const originalSizeMB = file.size / 1024 / 1024;
            console.log('Tamaño original:', originalSizeMB.toFixed(2) + 'MB');
            
            // Determinar si necesita compresión
            const needsCompression = isMobileDevice() ? 
                originalSizeMB > 1.5 :  // Comprimir si > 1.5MB en móviles
                originalSizeMB > 3.0;   // Comprimir si > 3MB en desktop
                
            let finalFile = file;
            
            if (needsCompression) {
                console.log('Aplicando compresión...');
                finalFile = await compressImageForMobile(file, {
                    maxSizeMB: isMobileDevice() ? 1.8 : 2.5
                });
                
                // Mostrar preview de imagen comprimida
                showCompressedPreview(file, finalFile, preview);
                
                // Actualizar label
                document.querySelector('label[for="foto"]').innerHTML = `
                    <i class="fas fa-check-circle" style="color: #28a745;"></i>
                    Imagen optimizada: ${(finalFile.size / 1024 / 1024).toFixed(1)}MB
                `;
                
            } else {
                console.log('No necesita compresión');
                
                // Mostrar preview normal
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = `
                        <div style="text-align: center;">
                            <img src="${e.target.result}" alt="Vista previa" 
                                 style="max-width: 200px; max-height: 150px; border-radius: 4px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                            <div class="file-info" style="margin-top: 10px;">
                                <i class="fas fa-check-circle" style="color: #28a745;"></i>
                                ${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)
                                <br><small style="color: #28a745;">Tamaño adecuado - Sin compresión necesaria</small>
                            </div>
                        </div>
                    `;
                };
                reader.readAsDataURL(file);
                
                // Actualizar label
                document.querySelector('label[for="foto"]').innerHTML = `
                    <i class="fas fa-check-circle" style="color: #28a745;"></i>
                    Imagen seleccionada: ${file.name}
                `;
            }
            
            // IMPORTANTE: Reemplazar el archivo en el input con el archivo final
            const dt = new DataTransfer();
            dt.items.add(finalFile);
            e.target.files = dt.files;
            
            console.log('Procesamiento completado. Archivo final:', (finalFile.size / 1024 / 1024).toFixed(2) + 'MB');
            
        } catch (error) {
            console.error('Error procesando imagen:', error);
            
            preview.innerHTML = `
                <div style="text-align: center; color: #dc3545; padding: 15px;">
                    <i class="fas fa-exclamation-triangle" style="font-size: 1.5em; margin-bottom: 10px;"></i>
                    <div><strong>Error al procesar imagen</strong></div>
                    <div style="font-size: 0.9em; margin-top: 5px;">${error.message}</div>
                    <div style="font-size: 0.8em; margin-top: 10px; color: #6c757d;">
                        ${isMobileDevice() ? 
                            'Intente con una imagen más pequeña o de menor resolución' : 
                            'Seleccione una imagen válida'
                        }
                    </div>
                </div>
            `;
            
            document.querySelector('label[for="foto"]').innerHTML = `
                <i class="fas fa-exclamation-triangle" style="color: #dc3545;"></i>
                Error - Seleccionar otra imagen
            `;
            
            // Limpiar input
            e.target.value = '';
        }
    });
    
    console.log('=== COMPRESOR DE IMÁGENES MÓVILES ACTIVADO ===');
    console.log('Event listener reemplazado exitosamente');

            // Event listeners para inputs de coordenadas
            if (latInput) {
                latInput.addEventListener('input', function() {
                    clearTimeout(this.timeout);
                    this.timeout = setTimeout(() => {
                        updateMapFromInputs();
                    }, 1000);
                });
                
                latInput.addEventListener('blur', updateMapFromInputs);
            }

            if (lngInput) {
                lngInput.addEventListener('input', function() {
                    clearTimeout(this.timeout);
                    this.timeout = setTimeout(() => {
                        updateMapFromInputs();
                    }, 1000);
                });
                
                lngInput.addEventListener('blur', updateMapFromInputs);
            }

            console.log('Aplicación inicializada correctamente');
        });
    </script>
</body>
</html>