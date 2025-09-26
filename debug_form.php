<?php
// SCRIPT DE DEBUG PARA DIAGNOSTICAR EL PROBLEMA
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Limpiar buffer
while (ob_get_level()) {
    ob_end_clean();
}
ob_start();

// Función de respuesta JSON
function sendDebugResponse($data) {
    while (ob_get_level()) {
        ob_end_clean();
    }
    header('Content-Type: application/json');
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}

// Si es POST, mostrar toda la información de debug
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $debug_info = [
        'timestamp' => date('Y-m-d H:i:s'),
        'request_method' => $_SERVER['REQUEST_METHOD'],
        'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'No definido',
        'content_length' => $_SERVER['CONTENT_LENGTH'] ?? 'No definido',
        'post_max_size' => ini_get('post_max_size'),
        'upload_max_filesize' => ini_get('upload_max_filesize'),
        'max_file_uploads' => ini_get('max_file_uploads'),
        'memory_limit' => ini_get('memory_limit'),
        'post_data_received' => !empty($_POST),
        'files_data_received' => !empty($_FILES),
        'post_count' => count($_POST),
        'files_count' => count($_FILES),
        'post_data' => $_POST,
        'files_data' => $_FILES,
        'raw_input' => file_get_contents('php://input'),
        'server_vars' => [
            'HTTP_HOST' => $_SERVER['HTTP_HOST'] ?? 'No definido',
            'SERVER_NAME' => $_SERVER['SERVER_NAME'] ?? 'No definido',
            'REQUEST_URI' => $_SERVER['REQUEST_URI'] ?? 'No definido',
            'SCRIPT_NAME' => $_SERVER['SCRIPT_NAME'] ?? 'No definido'
        ]
    ];
    
    sendDebugResponse($debug_info);
}

// Si no es POST, mostrar formulario de prueba
ob_clean();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug - Formulario de Prueba</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .form-group { margin: 15px 0; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, select { width: 300px; padding: 8px; margin-bottom: 10px; }
        button { background: #007cba; color: white; padding: 10px 20px; border: none; cursor: pointer; }
        .debug-info { background: #f0f0f0; padding: 15px; margin: 20px 0; border-radius: 5px; }
        .result { background: #e8f5e8; padding: 15px; margin: 20px 0; border-radius: 5px; }
        .error { background: #ffe6e6; padding: 15px; margin: 20px 0; border-radius: 5px; }
        pre { background: white; padding: 10px; border: 1px solid #ddd; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>Formulario de Prueba - Debug</h1>
    
    <div class="debug-info">
        <h3>Información del Servidor PHP:</h3>
        <p><strong>PHP Version:</strong> <?php echo phpversion(); ?></p>
        <p><strong>post_max_size:</strong> <?php echo ini_get('post_max_size'); ?></p>
        <p><strong>upload_max_filesize:</strong> <?php echo ini_get('upload_max_filesize'); ?></p>
        <p><strong>max_file_uploads:</strong> <?php echo ini_get('max_file_uploads'); ?></p>
        <p><strong>memory_limit:</strong> <?php echo ini_get('memory_limit'); ?></p>
        <p><strong>max_execution_time:</strong> <?php echo ini_get('max_execution_time'); ?></p>
    </div>

    <form id="testForm" enctype="multipart/form-data" method="POST">
        <div class="form-group">
            <label>Especie:</label>
            <input type="text" name="especie" value="Molle de prueba" required>
        </div>
        
        <div class="form-group">
            <label>Código:</label>
            <input type="text" name="codigo_arbol" value="TEST001" required>
        </div>
        
        <div class="form-group">
            <label>Edad:</label>
            <input type="number" name="edad" value="5" required>
        </div>
        
        <div class="form-group">
            <label>Latitud:</label>
            <input type="number" step="0.000001" name="lat" value="-17.393838" required>
        </div>
        
        <div class="form-group">
            <label>Longitud:</label>
            <input type="number" step="0.000001" name="lng" value="-66.156977" required>
        </div>
        
        <div class="form-group">
            <label>Foto (opcional para prueba):</label>
            <input type="file" name="foto" accept="image/*">
        </div>
        
        <button type="submit">Enviar Prueba</button>
        <button type="button" onclick="testWithJS()">Enviar con JavaScript</button>
    </form>

    <div id="result"></div>

    <script>
        function testWithJS() {
            const form = document.getElementById('testForm');
            const formData = new FormData(form);
            
            // Agregar un archivo de prueba si no hay uno seleccionado
            const fotoInput = form.querySelector('[name="foto"]');
            if (!fotoInput.files.length) {
                // Crear un archivo de prueba pequeño
                const canvas = document.createElement('canvas');
                canvas.width = 100;
                canvas.height = 100;
                const ctx = canvas.getContext('2d');
                ctx.fillStyle = 'green';
                ctx.fillRect(0, 0, 100, 100);
                
                canvas.toBlob(function(blob) {
                    formData.append('foto', blob, 'test.jpg');
                    sendData(formData);
                }, 'image/jpeg');
            } else {
                sendData(formData);
            }
        }
        
        function sendData(formData) {
            console.log('Enviando datos con JavaScript...');
            
            // Log del FormData
            for (let pair of formData.entries()) {
                console.log(pair[0] + ': ' + pair[1]);
            }
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('Status:', response.status);
                console.log('Headers:', response.headers);
                return response.text();
            })
            .then(data => {
                console.log('Respuesta completa:', data);
                
                try {
                    const jsonData = JSON.parse(data);
                    document.getElementById('result').innerHTML = 
                        '<div class="result"><h3>Respuesta del servidor:</h3><pre>' + 
                        JSON.stringify(jsonData, null, 2) + '</pre></div>';
                } catch (e) {
                    document.getElementById('result').innerHTML = 
                        '<div class="error"><h3>Respuesta no JSON:</h3><pre>' + 
                        data + '</pre></div>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('result').innerHTML = 
                    '<div class="error"><h3>Error:</h3><p>' + error.message + '</p></div>';
            });
        }
        
        // También manejar submit normal del formulario
        document.getElementById('testForm').addEventListener('submit', function(e) {
            e.preventDefault();
            testWithJS();
        });
    </script>
</body>
</html>