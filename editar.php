<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('America/La_Paz');

// Configurar límites
ini_set('upload_max_filesize', '15M');
ini_set('post_max_size', '20M');

// Crear directorios si no existen
$directories = ['uploads/trees', 'uploads/pdfs'];
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
// PROCESAR ACTUALIZACIÓN (POST)
// ===============================================
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $id = intval($_POST['id']);
        
        // Obtener datos actuales
        $stmt = $conn->prepare("SELECT fotoUrl, pdfUrl FROM arboles WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $current = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!$current) {
            throw new Exception("Árbol no encontrado");
        }
        
        // Recopilar campos
        $especie = trim($_POST['especie']);
        $nombre_comun = trim($_POST['nombre_comun']);
        $codigo_arbol = trim($_POST['codigo_arbol']);
        $edad = intval($_POST['edad']);
        $estado = trim($_POST['estado']);
        $altura = floatval($_POST['altura']);
        $diametroTronco = floatval($_POST['diametroTronco']);
        $diametro_copa = floatval($_POST['diametro_copa']);
        $inspector = trim($_POST['inspector']);
        $propiedad = trim($_POST['propiedad']);
        $otb = trim($_POST['otb']);
        $nombre_area_verde = trim($_POST['nombre_area_verde']);
        $estado_fitosanitario = trim($_POST['estado_fitosanitario']);
        
        $fotoUrl = $current['fotoUrl'];
        $pdfUrl = $current['pdfUrl'];
        
        // Procesar nueva foto
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['foto'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            if (in_array($ext, $allowed) && $file['size'] <= 8*1024*1024) {
                $newName = 'img_' . time() . '_' . uniqid() . '.' . $ext;
                $targetPath = 'uploads/trees/' . $newName;
                
                // Verificar directorio
                if (!is_dir('uploads/trees')) {
                    mkdir('uploads/trees', 0777, true);
                }
                
                if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                    // Eliminar foto anterior si existe
                    if (!empty($current['fotoUrl']) && file_exists($current['fotoUrl'])) {
                        unlink($current['fotoUrl']);
                    }
                    $fotoUrl = $targetPath;
                } else {
                    throw new Exception("Error al subir la foto");
                }
            } else {
                throw new Exception("Formato de imagen no válido o tamaño excedido");
            }
        }
        
        // Procesar nuevo PDF
        if (isset($_FILES['pdf']) && $_FILES['pdf']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['pdf'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            // Validación mejorada del PDF
            if ($ext === 'pdf' && $file['size'] <= 10*1024*1024) {
                $newName = 'pdf_' . time() . '_' . uniqid() . '.pdf';
                $targetPath = 'uploads/pdfs/' . $newName;
                
                // Verificar directorio
                if (!is_dir('uploads/pdfs')) {
                    mkdir('uploads/pdfs', 0777, true);
                }
                
                if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                    // Eliminar PDF anterior si existe
                    if (!empty($current['pdfUrl']) && file_exists($current['pdfUrl'])) {
                        unlink($current['pdfUrl']);
                    }
                    $pdfUrl = $targetPath;
                    error_log("PDF guardado exitosamente en: " . $targetPath);
                } else {
                    throw new Exception("Error al subir el PDF");
                }
            } else {
                throw new Exception("Archivo PDF no válido o tamaño excedido (máx 10MB)");
            }
        }
        
        // Eliminar PDF si se marcó el checkbox
        if (isset($_POST['eliminar_pdf']) && $_POST['eliminar_pdf'] == '1') {
            if (!empty($current['pdfUrl']) && file_exists($current['pdfUrl'])) {
                unlink($current['pdfUrl']);
            }
            $pdfUrl = null;
        }
        
        // Actualizar BD
        $stmt = $conn->prepare("UPDATE arboles SET 
            especie=?, nombre_comun=?, codigo_arbol=?, edad=?, estado=?, 
            altura=?, diametroTronco=?, diametro_copa=?, inspector=?, propiedad=?, 
            otb=?, nombre_area_verde=?, estado_fitosanitario=?, fotoUrl=?, pdfUrl=?
            WHERE id=?");
        
        $stmt->bind_param("sssisdddsssssssi",
            $especie, $nombre_comun, $codigo_arbol, $edad, $estado,
            $altura, $diametroTronco, $diametro_copa, $inspector, $propiedad,
            $otb, $nombre_area_verde, $estado_fitosanitario, $fotoUrl, $pdfUrl, $id
        );
        
        if ($stmt->execute()) {
            $mensaje = "✓ Árbol actualizado exitosamente";
            $tipo_mensaje = "success";
            
            // Debug: verificar qué se guardó
            error_log("Actualización exitosa - PDF URL: " . ($pdfUrl ?? 'NULL'));
        } else {
            throw new Exception("Error al actualizar: " . $stmt->error);
        }
        $stmt->close();
        
        // Recargar datos actualizados
        $stmt = $conn->prepare("SELECT * FROM arboles WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $arbol_seleccionado = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
    } catch (Exception $e) {
        $mensaje = "✗ Error: " . $e->getMessage();
        $tipo_mensaje = "error";
        error_log("Error en editar.php: " . $e->getMessage());
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
        }
        .header h1 { color: #333; font-size: 24px; }
        
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
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-edit"></i> Editor de Árboles Registrados</h1>
            <p style="color: #666; margin-top: 8px;">Selecciona un árbol de la lista para editar sus datos</p>
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