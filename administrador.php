<?php
// Conexión a la base de datos
$conn = new mysqli("localhost", "root", "", "reforest", 3306);

// Verifica si la biblioteca phpqrcode está disponible
require_once 'phpqrcode/qrlib.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Procesar datos del formulario
    $especie = $_POST['especie'];
    $edad = $_POST['edad'];
    $cuidados = $_POST['cuidados'];
    $estado = $_POST['estado'];
    $fotoUrl = $_POST['fotoUrl'];
    $altura = $_POST['altura'];
    $diametroTronco = $_POST['diametroTronco'];
    $coordenadas = "POINT(" . $_POST['lng'] . " " . $_POST['lat'] . ")";

    // Insertar el registro inicial sin QR
    $sql = "INSERT INTO arboles (especie, edad, cuidados, estado, fotoUrl, altura, diametroTronco, coordenadas, qrUrl) 
            VALUES ('$especie', $edad, '$cuidados', '$estado', '$fotoUrl', $altura, $diametroTronco, ST_GeomFromText('$coordenadas'), NULL)";

    if ($conn->query($sql)) {
        $lastId = $conn->insert_id; // Obtener el último ID insertado

        // Generar la URL única para este árbol
        $treeUrl = "https://es.wikipedia.org/wiki/Schinus_molle" . $lastId;

        // Generar el código QR y guardarlo en el servidor
        $qrFilename = 'qr_codes/qr_' . $lastId . '.png';
        QRcode::png($treeUrl, $qrFilename, QR_ECLEVEL_L, 4);

        // Actualizar el registro con la URL del QR
        $updateSql = "UPDATE arboles SET qrUrl = '$qrFilename' WHERE id = $lastId";
        $conn->query($updateSql);
    }
}

// Obtener todos los árboles de la base de datos para mostrarlos en el mapa
$sql = "SELECT especie, edad, cuidados, estado, fotoUrl, altura, diametroTronco, ST_AsText(coordenadas) as coordenadas, qrUrl FROM arboles";
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
    <title>SkyGreen - Administrador</title>
    <script src="https://api.mapbox.com/mapbox-gl-js/v2.8.1/mapbox-gl.js"></script>
    <link href="https://api.mapbox.com/mapbox-gl-js/v2.8.1/mapbox-gl.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
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
        }

        .logo i {
            margin-right: 0.5rem;
            color: #2d5016;
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
            max-width: 1200px;
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

        .form-card {
            background: white;
            border-radius: 8px;
            padding: 2rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            border: 1px solid #e9ecef;
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
            color: #2d5016;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
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
            background: #2d5016;
            color: white;
        }

        .btn-primary:hover {
            background: #1a2f0c;
        }

        .btn-secondary {
            background: #6c757d;
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

            .form-row {
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
        }

        .mapboxgl-popup-content {
            border-radius: 4px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
        }

        .mapboxgl-popup-content h3 {
            color: #333;
            margin-bottom: 0.5rem;
            font-weight: 400;
        }

        .mapboxgl-popup-content img {
            border-radius: 8px;
            margin: 0.5rem 0;
        }

        .mapboxgl-popup-content p {
            margin: 0.25rem 0;
            font-size: 0.9rem;
            color: #555;
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
                
                <li><a href="index.php">Volver</a></li>
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
                
                <form id="arbolForm">
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-seedling"></i> Especie del Árbol
                        </label>
                        <input type="text" name="especie" class="form-input" placeholder="Ej: Schinus molle" required />
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
                                <i class="fas fa-shield-alt"></i> Categoría
                            </label>
                            <select name="estado" class="form-select" required>
                                <option value="">Selecciona una categoría</option>
                                <option value="peligrosos">🔴 Peligrosos</option>
                                <option value="protegido">🟡 Protegido</option>
                                <option value="nativo">🟢 Nativo</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-heart"></i> Cuidados Necesarios
                        </label>
                        <input type="text" name="cuidados" class="form-input" placeholder="Ej: Riego semanal, poda anual" required />
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-image"></i> URL de la Fotografía
                        </label>
                        <input type="text" name="fotoUrl" class="form-input" placeholder="https://ejemplo.com/foto.jpg" required />
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-arrows-alt-v"></i> Altura (metros)
                            </label>
                            <input type="number" step="0.1" name="altura" class="form-input" placeholder="Ej: 3.5" required />
                        </div>
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-circle"></i> Diámetro (cm)
                            </label>
                            <input type="number" step="0.1" name="diametroTronco" class="form-input" placeholder="Ej: 25.5" required />
                        </div>
                    </div>

                    <div class="location-hint">
                        <i class="fas fa-map-marker-alt"></i>
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

                    <button type="button" id="agregarArbolBtn" class="btn btn-primary" style="width: 100%; margin-top: 1rem;" disabled onclick="obtenerCoordenadas()">
                        <i class="fas fa-plus"></i>
                        Registrar Árbol
                    </button>
                </form>
            </div>

            <div class="map-card">
                <h2 class="map-title">
                    <i class="fas fa-map"></i>
                    Mapa de Ubicación
                </h2>
                <div id="map"></div>
            </div>
        </div>
    </div>

    <script>
        // Mapbox token
        mapboxgl.accessToken = 'pk.eyJ1IjoiYWxlc3NpcyIsImEiOiJjbGcxbHBtbHQwdDU5M2RubDFodjY3a2x0In0.NXe43GdM4PJBj7ow0Dnkpw';

        const map = new mapboxgl.Map({
            container: 'map',
            style: 'mapbox://styles/mapbox/streets-v11',
            center: [-66.158468, -17.374908],
            zoom: 17,
            pitch: 50,
            bearing: -17.6
        });

        let marker, lng, lat;

        map.on('load', function() {
            map.addLayer({
                'id': 'Unifranz',
                'type': 'fill',
                'source': {
                    'type': 'geojson',
                    'data': {
                        'type': 'Feature',
                        'geometry': {
                            'type': 'Polygon',
                            'coordinates': [
                                [
                                    [-66.157795, -17.374501],
                                    [-66.159077, -17.374442],
                                    [-66.159136, -17.375289],
                                    [-66.157803, -17.375348],
                                    [-66.157773, -17.374501]
                                ]
                            ]
                        }
                    }
                },
                'layout': {},
                'paint': {
                    'fill-color': '#a3dde8',
                    'fill-opacity': 0.5
                }
            });

            const arboles = <?php echo json_encode($arboles); ?>;
            arboles.forEach(arbol => {
                const coordinates = arbol.coordenadas.replace('POINT(', '').replace(')', '').split(' ');

                const el = document.createElement('div');
                el.className = 'tree-marker';

                switch (arbol.estado.toLowerCase()) {
                    case 'peligrosos':
                        el.style.border = '3px solid #ff4757';
                        break;
                    case 'protegido':
                        el.style.border = '3px solid rgb(229, 255, 0)';
                        break;
                    case 'nativo':
                        el.style.border = '3px solid #2ed573';
                        break;
                    default:
                        el.style.border = '3px solid #57606f';
                }

                const marker = new mapboxgl.Marker(el)
                    .setLngLat([parseFloat(coordinates[0]), parseFloat(coordinates[1])])
                    .addTo(map);

                const popup = new mapboxgl.Popup({
                        offset: 25
                    })
                    .setHTML(`
                    <h3><i class="fas fa-tree"></i> ${arbol.especie}</h3>
                    <img src="${arbol.fotoUrl}" alt="Foto del árbol" style="width: 150px; height: 150px; object-fit: cover;" />
                    <p><i class="fas fa-calendar"></i> <strong>Edad:</strong> ${arbol.edad} años</p>
                    <p><i class="fas fa-arrows-alt-v"></i> <strong>Altura:</strong> ${arbol.altura} metros</p>
                    <p><i class="fas fa-circle"></i> <strong>Diámetro:</strong> ${arbol.diametroTronco} cm</p>
                    <p><i class="fas fa-heart"></i> <strong>Cuidados:</strong> ${arbol.cuidados}</p>
                    <p><i class="fas fa-shield-alt"></i> <strong>Categoría:</strong> ${arbol.estado}</p>
                    <img src="${arbol.qrUrl}" alt="QR del árbol" style="width: 100px; height: 100px;" />
                `);

                marker.setPopup(popup);
            });
        });

        map.on('click', (e) => {
            lng = e.lngLat.lng;
            lat = e.lngLat.lat;

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

        function obtenerCoordenadas() {
            const form = document.getElementById('arbolForm');
            const formData = new FormData(form);
            formData.append('lng', lng);
            formData.append('lat', lat);

            // Mostrar loading en el botón
            const btn = document.getElementById('agregarArbolBtn');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Registrando...';
            btn.disabled = true;

            fetch('administrador.php', {
                method: 'POST',
                body: formData
            }).then(() => {
                document.getElementById('successMessage').style.display = 'block';
                form.reset();
                marker.remove();
                marker = null;
                lng = null;
                lat = null;
                btn.innerHTML = originalText;
                btn.disabled = true;
                
                setTimeout(() => {
                    location.reload();
                }, 2000);
            }).catch(error => {
                console.error(error);
                showNotification('Error al registrar el árbol', 'error');
                btn.innerHTML = originalText;
                btn.disabled = false;
            });
        }

        function obtenerUbicacion() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        lng = position.coords.longitude;
                        lat = position.coords.latitude;

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
                                lng = lngLat.lng;
                                lat = lngLat.lat;
                            });
                        }

                        map.flyTo({
                            center: [lng, lat],
                            zoom: 17
                        });

                        document.getElementById('agregarArbolBtn').disabled = false;
                        showNotification('Ubicación obtenida correctamente', 'success');
                    },
                    (error) => {
                        showNotification('Error al obtener la ubicación. Verifica los permisos.', 'error');
                        console.error(error);
                    }
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
            }, 3000);
        }

        // Añadir animación CSS
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>

</html>