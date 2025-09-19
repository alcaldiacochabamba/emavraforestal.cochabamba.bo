<?php
// Habilitar el manejo de errores para mayor seguridad
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Conexión a la base de datos con manejo de errores
$conn = new mysqli("localhost", "root", "", "reforest", 3306);
if ($conn->connect_error) {
    die("Error en la conexión a la base de datos: " . $conn->connect_error);
}

// Configuración de encabezados para proteger la privacidad y seguridad
header("Content-Security-Policy: default-src 'self'");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validar y sanitizar los datos del formulario
    $especie = htmlspecialchars($_POST['especie']);
    $edad = (int)$_POST['edad'];
    $cuidados = htmlspecialchars($_POST['cuidados']);
    $estado = htmlspecialchars($_POST['estado']);
    $fotoUrl = filter_var($_POST['fotoUrl'], FILTER_SANITIZE_URL);
    $altura = (float)$_POST['altura'];
    $diametroTronco = (float)$_POST['diametroTronco'];
    $lng = (float)$_POST['lng'];
    $lat = (float)$_POST['lat'];

    // Validar datos sensibles
    if (!filter_var($fotoUrl, FILTER_VALIDATE_URL)) {
        die("URL de la foto no válida.");
    }

    $coordenadas = "POINT($lng $lat)";

    // Consulta preparada para evitar inyección SQL
    $stmt = $conn->prepare("INSERT INTO arboles (especie, edad, cuidados, estado, fotoUrl, altura, diametroTronco, coordenadas) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ST_GeomFromText(?))");
    $stmt->bind_param("sisssdds", $especie, $edad, $cuidados, $estado, $fotoUrl, $altura, $diametroTronco, $coordenadas);

    try {
        $stmt->execute();
    } catch (Exception $e) {
        error_log("Error al insertar datos: " . $e->getMessage());
        die("Error al insertar datos.");
    }

    $stmt->close();
}

// Obtener todos los árboles de la base de datos
$sql = "SELECT especie, edad, cuidados, estado, fotoUrl, altura, diametroTronco, ST_AsText(coordenadas) as coordenadas FROM arboles";
$result = $conn->query($sql);

// Array para almacenar los árboles
$arboles = [];
if ($result->num_rows > 0) {
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
    <title>Administrador</title>
    <script src="https://api.mapbox.com/mapbox-gl-js/v2.8.1/mapbox-gl.js"></script>
    <link href="https://api.mapbox.com/mapbox-gl-js/v2.8.1/mapbox-gl.css" rel="stylesheet" />
    <style>
        .tree-marker {
            background-image: url('https://cdn2.iconfinder.com/data/icons/miscellaneous-iii-glyph-style/150/tree-512.png');
            background-size: cover;
            width: 30px;
            height: 30px;
        }
    </style>
</head>

<body>
    <h1>Agregar Árbol</h1>
    <form id="arbolForm">
        <input type="text" name="especie" placeholder="Especie del Árbol" required />
        <input type="number" name="edad" placeholder="Edad del Árbol" required />
        <input type="text" name="cuidados" placeholder="Cuidados Necesarios" required />
        <input type="text" name="estado" placeholder="Estado del Árbol" required />
        <input type="text" name="fotoUrl" placeholder="URL de la foto" required />
        <input type="number" step="0.1" name="altura" placeholder="Altura en metros" required />
        <input type="number" step="0.1" name="diametroTronco" placeholder="Diámetro en cm" required />
        <button type="button" onclick="confirmarUbicacion()">Confirmar Ubicación</button>
        <button type="button" id="agregarArbolBtn" disabled onclick="obtenerCoordenadas()">Agregar Árbol</button>
    </form>

    <div id="map" style="width: 100%; height: 500px;"></div>

    <script>
        mapboxgl.accessToken = 'pk.eyJ1IjoiYWxlc3NpcyIsImEiOiJjbGcxbHBtbHQwdDU5M2RubDFodjY3a2x0In0.NXe43GdM4PJBj7ow0Dnkpw';

        const map = new mapboxgl.Map({
            container: 'map',
            style: 'mapbox://styles/mapbox/streets-v11',
            center: [-66.1704015, -17.3761244],
            zoom: 12
        });

        let marker, lng, lat;

        map.on('click', (e) => {
            lng = e.lngLat.lng;
            lat = e.lngLat.lat;

            if (marker) {
                marker.setLngLat(e.lngLat);
            } else {
                marker = new mapboxgl.Marker({ draggable: true })
                    .setLngLat(e.lngLat)
                    .addTo(map);
            }

            marker.on('dragend', function () {
                const lngLat = marker.getLngLat();
                lng = lngLat.lng;
                lat = lngLat.lat;
            });
        });

        function confirmarUbicacion() {
            if (lng && lat) {
                document.getElementById('agregarArbolBtn').disabled = false;
                alert('Ubicación confirmada');
            } else {
                alert('Por favor selecciona una ubicación en el mapa.');
            }
        }

        function obtenerCoordenadas() {
            const form = document.getElementById('arbolForm');
            const formData = new FormData(form);
            formData.append('lng', lng);
            formData.append('lat', lat);

            fetch('administrador.php', {
                method: 'POST',
                body: formData
            }).then(() => {
                alert('Árbol agregado con éxito');
                form.reset();
                document.getElementById('agregarArbolBtn').disabled = true;
                if (marker) {
                    marker.remove();
                    marker = null;
                }
            });
        }
    </script>
</body>

</html>
