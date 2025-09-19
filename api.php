<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');


$conn = new mysqli("localhost", "root", "", "reforest", 3306);


if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de conexión: ' . $conn->connect_error]);
    exit;
}


$conn->set_charset("utf8");

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    
    $sql = "SELECT 
                id,
                especie, 
                edad, 
                cuidados, 
                estado, 
                fotoUrl, 
                altura, 
                diametroTronco, 
                ST_X(coordenadas) as lng,
                ST_Y(coordenadas) as lat,
                qrUrl
            FROM arboles 
            ORDER BY id DESC"; 
    
    $result = $conn->query($sql);
    
    if ($result === false) {
        http_response_code(500);
        echo json_encode(['error' => 'Error en la consulta: ' . $conn->error]);
        exit;
    }
    
    $arboles = [];
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
          
            $colorCode = '';
            $statusText = '';
            
            switch (strtolower($row['estado'])) {
                case 'nativo':
                    $colorCode = 'green';
                    $statusText = 'Nativo';
                    break;
                case 'protegido':
                    $colorCode = 'yellow';
                    $statusText = 'Protegido';
                    break;
                case 'peligrosos':
                case 'peligroso':
                    $colorCode = 'red';
                    $statusText = 'Peligroso';
                    break;
                default:
                    $colorCode = 'gray';
                    $statusText = 'Desconocido';
            }
            
          
            $imageUrl = $row['fotoUrl'];
            if (!filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                $imageUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/' . ltrim($imageUrl, '/');
            }
            
            
            $qrUrl = null;
            if (!empty($row['qrUrl'])) {
                $qrUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/' . ltrim($row['qrUrl'], '/');
            }
            
            $arboles[] = [
                'id' => (int)$row['id'],
                'name' => $row['especie'],
                'imageUrl' => $imageUrl,
                'description' => generateDescription($row['estado'], $row['edad']),
                'details' => formatDetails($row),
                'colorCode' => $colorCode,
                'statusText' => $statusText,
                'location' => [
                    'lat' => (float)$row['lat'],
                    'lng' => (float)$row['lng']
                ],
                'qrUrl' => $qrUrl,
                'rawData' => [
                    'especie' => $row['especie'],
                    'edad' => (int)$row['edad'],
                    'cuidados' => $row['cuidados'],
                    'estado' => $row['estado'],
                    'altura' => (float)$row['altura'],
                    'diametroTronco' => (float)$row['diametroTronco'],
                    
                ]
            ];
        }
    }
    
  
    echo json_encode([
        'success' => true,
        'data' => $arboles,
        'total' => count($arboles),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} else {
   
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
}

$conn->close();

function generateDescription($estado, $edad) {
    switch (strtolower($estado)) {
        case 'nativo':
            return 'Especie originaria de la región.';
        case 'protegido':
            return 'Este árbol está protegido por ley.';
        case 'peligrosos':
        case 'peligroso':
            return 'Este árbol representa un riesgo.';
        default:
            return 'Árbol registrado en el sistema.';
    }
}


function formatDetails($row) {
    return "Edad: {$row['edad']} años\n" .
           "Altura: {$row['altura']} m\n" .
           "Diámetro: {$row['diametroTronco']} cm\n" .
           "Cuidados: {$row['cuidados']}\n" .
           "Estado: {$row['estado']}";
}
?>
