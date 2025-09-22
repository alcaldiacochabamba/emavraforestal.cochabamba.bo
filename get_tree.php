<?php
// get_tree.php - API para obtener información de un árbol específico por ID

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');


$conn = new mysqli("localhost", "root", "", "reforest", 3306);


if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de conexión a la base de datos']);
    exit();
}

$tree_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($tree_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de árbol inválido']);
    exit();
}


$sql = "SELECT id, especie, edad, cuidados, estado, fotoUrl, altura, diametroTronco, 
        ST_AsText(coordenadas) as coordenadas, qrUrl, fecha_registro 
        FROM arboles WHERE id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $tree_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['error' => 'Árbol no encontrado']);
    exit();
}

$arbol = $result->fetch_assoc();

// Convertir coordenadas a formato más usable
if ($arbol['coordenadas']) {
    $coordinates = str_replace(['POINT(', ')'], '', $arbol['coordenadas']);
    $coords = explode(' ', $coordinates);
    $arbol['longitude'] = floatval($coords[0]);
    $arbol['latitude'] = floatval($coords[1]);
}


$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$domain = $_SERVER['HTTP_HOST'];
$arbol['full_foto_url'] = $protocol . "://" . $domain . "/" . $arbol['fotoUrl'];
$arbol['full_qr_url'] = $protocol . "://" . $domain . "/" . $arbol['qrUrl'];

$stmt->close();
$conn->close();

echo json_encode($arbol);
?>