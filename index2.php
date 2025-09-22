<?php
$conn = new mysqli("localhost", "root","", "reforest", 3306);
if ($conn->connect_error) {
  die("Error de conexión: " . $conn->connect_error);
}

$sql = "SELECT id, especie, edad, cuidados, estado, fotoUrl, altura, diametroTronco, ST_AsText(coordenadas) as coordenadas, qrUrl FROM arboles";
$result = $conn->query($sql);

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
  <title>SkyGreen - Transformando Cochabamba</title>
  <meta name="description" content="Plataforma Web Ambiental en colaboración con la Empresa Municipal de Áreas Verdes de Cochabamba">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <script src="https://api.mapbox.com/mapbox-gl-js/v2.8.1/mapbox-gl.js"></script>
  <link href="https://api.mapbox.com/mapbox-gl-js/v2.8.1/mapbox-gl.css" rel="stylesheet" />
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      line-height: 1.6;
      color: #333;
      overflow-x: hidden;
    }

    /* Header Styles */
    .header {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(10px);
      padding: 1rem 0;
      box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      z-index: 1000;
      transition: all 0.3s ease;
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
      text-decoration: none;
    }

    .logo i {
      margin-right: 0.5rem;
      color: #3ebeab;
      font-size: 2rem;
    }

    .nav-links {
      display: flex;
      list-style: none;
      gap: 2rem;
      align-items: center;
    }

    .nav-links a {
      text-decoration: none;
      color: #666;
      font-weight: 400;
      transition: color 0.3s ease;
      padding: 0.5rem 1rem;
      border-radius: 20px;
      transition: all 0.3s ease;
    }

    .nav-links a:hover {
      color: #482e83;
      background: rgba(45, 80, 22, 0.1);
    }

    .btn-admin {
      background: #3ebeab;
      color: white !important;
      padding: 0.7rem 1.5rem !important;
      border-radius: 25px;
      font-weight: 500;
      transition: all 0.3s ease;
    }

    .btn-admin:hover {
      background: #1a2f0c;
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(45, 80, 22, 0.3);
    }

    /* Hero Section */
    .hero {
      background: linear-gradient(135deg, #482e83 0%, #685ca8 100%);
      color: white;
      padding: 120px 0 80px;
      position: relative;
      overflow: hidden;
    }

    .hero::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 20"><defs><radialGradient id="a" cx="50%" cy="50%"><stop offset="0%" stop-opacity=".1"/><stop offset="100%" stop-opacity=".05"/></radialGradient></defs><circle cx="10" cy="10" r="10" fill="url(%23a)"/><circle cx="50" cy="10" r="10" fill="url(%23a)"/><circle cx="90" cy="10" r="10" fill="url(%23a)"/></svg>') repeat;
      opacity: 0.1;
    }

    .hero-content {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 2rem;
      text-align: center;
      position: relative;
      z-index: 2;
    }

    .hero h1 {
      font-size: 3.5rem;
      font-weight: 300;
      margin-bottom: 1.5rem;
      opacity: 0;
      animation: fadeInUp 1s ease 0.2s forwards;
    }

    .hero-subtitle {
      font-size: 1.3rem;
      margin-bottom: 2rem;
      opacity: 0.9;
      max-width: 800px;
      margin-left: auto;
      margin-right: auto;
      opacity: 0;
      animation: fadeInUp 1s ease 0.4s forwards;
    }

    .hero-buttons {
      display: flex;
      gap: 1rem;
      justify-content: center;
      flex-wrap: wrap;
      opacity: 0;
      animation: fadeInUp 1s ease 0.6s forwards;
    }

    .btn {
      padding: 1rem 2rem;
      border: none;
      border-radius: 30px;
      font-size: 1rem;
      font-weight: 500;
      cursor: pointer;
      transition: all 0.3s ease;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
    }

    .btn-primary {
      background: white;
      color: #482e83;
    }

    .btn-primary:hover {
      background: #f8f9fa;
      transform: translateY(-3px);
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    }

    .btn-secondary {
      background: transparent;
      color: white;
      border: 2px solid white;
    }

    .btn-secondary:hover {
      background: white;
      color: #3ebeab;
      transform: translateY(-3px);
    }

    /* About Section */
    .about {
      padding: 100px 0;
      background: #f8f9fa;
    }

    .container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 2rem;
    }

    .section-header {
      text-align: center;
      margin-bottom: 4rem;
    }

    .section-title {
      font-size: 2.5rem;
      font-weight: 300;
      color: #333;
      margin-bottom: 1rem;
    }

    .section-subtitle {
      font-size: 1.1rem;
      color: #666;
      max-width: 600px;
      margin: 0 auto;
    }

    .features-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 2rem;
      margin-top: 3rem;
    }

    .feature-card {
      background: white;
      padding: 2rem;
      border-radius: 15px;
      box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
      transition: all 0.3s ease;
      text-align: center;
    }

    .feature-card:hover {
      transform: translateY(-10px);
      box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
    }

    .feature-icon {
      width: 80px;
      height: 80px;
      background: linear-gradient(135deg, #482e83, #685ca8);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 1.5rem;
      color: white;
      font-size: 2rem;
    }

    .feature-title {
      font-size: 1.3rem;
      font-weight: 500;
      color: #333;
      margin-bottom: 1rem;
    }

    .feature-description {
      color: #666;
      line-height: 1.8;
    }

    /* Map Section */
    .map-section {
      padding: 100px 0;
      background: white;
    }

    .map-container {
      background: white;
      border-radius: 20px;
      padding: 2rem;
      box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
      overflow: hidden;
    }

    #map {
      width: 100%;
      height: 600px;
      border-radius: 15px;
    }

    .map-legend {
      position: absolute;
      top: 20px;
      left: 20px;
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(10px);
      padding: 1.5rem;
      border-radius: 15px;
      font-family: 'Segoe UI', sans-serif;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
      z-index: 1000;
      min-width: 200px;
    }

    .map-legend h4 {
      margin: 0 0 1rem;
      font-size: 1rem;
      color: #333;
      font-weight: 500;
    }

    .legend-item {
      display: flex;
      align-items: center;
      margin-bottom: 0.8rem;
      font-size: 0.9rem;
    }

    .legend-icon {
      width: 16px;
      height: 16px;
      border-radius: 50%;
      margin-right: 10px;
      border: 2px solid;
    }

    .legend-icon.protected { background: #ff4757; border-color: #ff4757; }
    .legend-icon.native { background: #2ed573; border-color: #2ed573; }
    .legend-icon.dangerous { background: #ffa502; border-color: #ffa502; }

    /* Honor Wall */
    .honor-wall {
      padding: 100px 0;
      background: #f8f9fa;
    }

    .honor-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 2rem;
      margin-top: 3rem;
    }

    .honor-card {
      background: white;
      padding: 2rem;
      border-radius: 20px;
      box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
      text-align: center;
      transition: all 0.3s ease;
    }

    .honor-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
    }

    .honor-avatar {
      width: 100px;
      height: 100px;
      border-radius: 50%;
      margin: 0 auto 1.5rem;
      border: 4px solid #685ca8;
      object-fit: cover;
    }

    .honor-name {
      font-size: 1.2rem;
      font-weight: 500;
      color: #333;
      margin-bottom: 0.5rem;
    }

    .honor-contribution {
      color: #3ebeab;
      font-weight: 500;
    }

    /* Newsletter */
    .newsletter {
      padding: 80px 0;
      background: linear-gradient(135deg, #3ebeab, #3ebeab);
      color: white;
      text-align: center;
    }

    .newsletter-form {
      max-width: 500px;
      margin: 2rem auto 0;
      display: flex;
      gap: 1rem;
      flex-wrap: wrap;
    }

    .newsletter-input {
      flex: 1;
      min-width: 250px;
      padding: 1rem 1.5rem;
      border: none;
      border-radius: 30px;
      font-size: 1rem;
      outline: none;
    }

    .newsletter-btn {
      padding: 1rem 2rem;
      background: white;
      color: #685ca8;
      border: none;
      border-radius: 30px;
      font-size: 1rem;
      font-weight: 500;
      cursor: pointer;
      transition: all 0.3s ease;
      white-space: nowrap;
    }

    .newsletter-btn:hover {
      background: #f8f9fa;
      transform: translateY(-2px);
    }

    /* Footer */
    .footer {
      background: #482e83;
      color: #ccc;
      padding: 60px 0 30px;
    }

    .footer-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 3rem;
      margin-bottom: 3rem;
    }

    .footer-section h3 {
      color: white;
      margin-bottom: 1.5rem;
      font-size: 1.2rem;
    }

    .footer-section p {
      line-height: 1.8;
      margin-bottom: 1rem;
    }

    .footer-bottom {
      text-align: center;
      padding-top: 2rem;
      border-top: 1px solid #3ebeab;
      font-size: 0.9rem;
    }

    /* Animations */
    @keyframes fadeInUp {
      from {
        opacity: 0;
        transform: translateY(30px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    /* Tree Marker Styles */
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

    /* Popup Styles */
    .mapboxgl-popup-content {
      border-radius: 15px;
      padding: 1rem;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
      max-width: 280px;
    }

    .popup-header {
      display: flex;
      align-items: center;
      margin-bottom: 1rem;
    }

    .popup-header i {
      margin-right: 0.5rem;
      color: #2d5016;
    }

    .popup-header h3 {
      margin: 0;
      font-size: 1.1rem;
      color: #333;
    }

    .popup-image {
      width: 100%;
      height: 120px;
      object-fit: cover;
      border-radius: 10px;
      margin-bottom: 1rem;
    }

    .popup-info {
      font-size: 0.85rem;
      line-height: 1.4;
      margin-bottom: 0.5rem;
    }

    .popup-info i {
      width: 16px;
      color: #666;
      margin-right: 5px;
    }

    .qr-code {
      width: 80px;
      height: 80px;
      margin: 1rem auto 0;
      display: block;
      border-radius: 8px;
    }

    /* Mobile Responsiveness */
    @media (max-width: 768px) {
      .nav {
        padding: 0 1rem;
        flex-wrap: wrap;
      }

      .nav-links {
        display: none;
      }

      .hero h1 {
        font-size: 2.5rem;
      }

      .hero-subtitle {
        font-size: 1.1rem;
      }

      .hero-buttons {
        flex-direction: column;
        align-items: center;
      }

      .section-title {
        font-size: 2rem;
      }

      .newsletter-form {
        flex-direction: column;
      }

      .newsletter-input {
        min-width: auto;
      }

      .map-legend {
        position: relative;
        margin-bottom: 1rem;
      }
    }

    /* Scroll behavior */
    html {
      scroll-behavior: smooth;
    }
  </style>
</head>

<body>
  <!-- Header -->
  <header class="header">
    <nav class="nav">
      <a href="#home" class="logo">
        <i class="fas fa-leaf"></i>
        SkyGreen
      </a>
      <ul class="nav-links">
        <li><a href="#about">¿Qué hacemos?</a></li>
        <li><a href="#map">Mapa de Árboles</a></li>
        <li><a href="#honor">Muro de Honor</a></li>
        <li><a href="#contact">Contacto</a></li>
        <li><a href="administrador.php" class="btn-admin">Panel Admin</a></li>
      </ul>
    </nav>
  </header>

  <!-- Hero Section -->
  <section id="home" class="hero">
    <div class="hero-content">
      
      <h1>Bienvenido a SkyGreen</h1>
      <p class="hero-subtitle">
        Transformamos Cochabamba construyendo un futuro más verde y sostenible. 
        Nuestra plataforma web ambiental conecta la comunidad con la naturaleza 
        en colaboración con la Empresa Municipal de Áreas Verdes.
      </p>
      
      <div class="hero-buttons">
        <a href="#about" class="btn btn-primary">
          <i class="fas fa-leaf"></i>
          Conoce más
        </a>
        <a href="#map" class="btn btn-secondary">
          <i class="fas fa-map"></i>
          Ver mapa
        </a>
      </div>
    </div>
  </section>

  

  <!-- About Section -->
  <section id="about" class="about">
    <div class="container">
      <div class="section-header">
        <h2 class="section-title">¿Qué Hacemos?</h2>
        <p class="section-subtitle">
          Conectamos la tecnología con la naturaleza para crear un impacto positivo 
          en el medio ambiente de Cochabamba
        </p>
      </div>

      <div class="features-grid">
        <div class="feature-card">
          <div class="feature-icon">
            <i class="fas fa-map-marked-alt"></i>
          </div>
          <h3 class="feature-title">Mapa Interactivo</h3>
          <p class="feature-description">
            Descubre cada rincón verde de la Zona Norte, identificando árboles según 
            su estado: protegidos, nativos y peligrosos con información detallada.
          </p>
        </div>

        <div class="feature-card">
          <div class="feature-icon">
            <i class="fas fa-seedling"></i>
          </div>
          <h3 class="feature-title">Registro de Árboles</h3>
          <p class="feature-description">
            Registra tu árbol y conviértete en un guardián del verde. 
            Cada registro contribuye al crecimiento de nuestro ecosistema urbano.
          </p>
        </div>

        <div class="feature-card">
          <div class="feature-icon">
            <i class="fas fa-users"></i>
          </div>
          <h3 class="feature-title">Comunidad Verde</h3>
          <p class="feature-description">
            Únete a eventos, talleres y actividades comunitarias. 
            Aprende sobre jardinería sostenible y participa en la reforestación.
          </p>
        </div>
      </div>
    </div>
  </section>

  <!-- Map Section -->
  <section id="map" class="map-section">
    <div class="container">
      <div class="section-header">
        <h2 class="section-title">Árboles Registrados</h2>
        <p class="section-subtitle">
          Explora la ubicación de cada árbol registrado en nuestra plataforma
        </p>
      </div>

      <div class="map-container">
        <div id="map"></div>
        <div class="map-legend">
          <h4><i class="fas fa-info-circle"></i> Categorías</h4>
          <div class="legend-item">
            <span class="legend-icon protected"></span>
            Árboles Peligrosos
          </div>
          <div class="legend-item">
            <span class="legend-icon native"></span>
            Árboles Nativos
          </div>
          <div class="legend-item">
            <span class="legend-icon dangerous"></span>
            Árboles Protegidos
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Honor Wall -->
  <section id="honor" class="honor-wall">
    <div class="container">
      <div class="section-header">
        <h2 class="section-title">🌳 Muro de Honor</h2>
        <p class="section-subtitle">
          Conoce a los héroes que están ayudando a restaurar nuestro ecosistema
        </p>
      </div>

      <div class="honor-grid">
        <div class="honor-card">
          <img src="img/person1.jpg" alt="Alejandro Pérez" class="honor-avatar">
          <h3 class="honor-name">Alejandro Pérez</h3>
          <p class="honor-contribution">🌿 Ha adoptado un Pino</p>
        </div>

        <div class="honor-card">
          <img src="img/person2.jpg" alt="María Gómez" class="honor-avatar">
          <h3 class="honor-name">María Gómez</h3>
          <p class="honor-contribution">🌿 Ha adoptado un Roble</p>
        </div>

        <div class="honor-card">
          <img src="img/person3.jpg" alt="Carlos Rodríguez" class="honor-avatar">
          <h3 class="honor-name">Carlos Rodríguez</h3>
          <p class="honor-contribution">🌿 Ha adoptado un Ciprés</p>
        </div>

        <div class="honor-card">
          <img src="img/person4.jpg" alt="Laura Méndez" class="honor-avatar">
          <h3 class="honor-name">Laura Méndez</h3>
          <p class="honor-contribution">🌿 Ha adoptado un Olivo</p>
        </div>

        <div class="honor-card">
          <img src="img/person5.jpg" alt="Fernando Ruiz" class="honor-avatar">
          <h3 class="honor-name">Fernando Ruiz</h3>
          <p class="honor-contribution">🌿 Ha adoptado un Almendro</p>
        </div>

        <div class="honor-card">
          <img src="img/person6.jpg" alt="Ana López" class="honor-avatar">
          <h3 class="honor-name">Ana López</h3>
          <p class="honor-contribution">🌿 Ha adoptado un Cerezo</p>
        </div>
      </div>
    </div>
  </section>

  <!-- Newsletter -->
  <section id="contact" class="newsletter">
    <div class="container">
      <h2 class="section-title">Mantente Informado</h2>
      <p class="section-subtitle">
        Recibe información sobre nuevos árboles, eventos y noticias ambientales
      </p>
      <form action="interesados.php" method="post" class="newsletter-form">
        <input 
          type="email" 
          name="email" 
          class="newsletter-input" 
          placeholder="tu@email.com" 
          required
        >
        <button type="submit" class="newsletter-btn">
          <i class="fas fa-paper-plane"></i>
          Suscribirse
        </button>
      </form>
    </div>
  </section>

  <!-- Footer -->
  <footer class="footer">
    <div class="container">
      <div class="footer-grid">
        <div class="footer-section">
          <h3><i class="fas fa-leaf"></i> SkyGreen</h3>
          <p>
            Transformando Cochabamba hacia un futuro más verde y sostenible. 
            Conectamos la comunidad con la naturaleza través de la tecnología.
          </p>
        </div>

        <div class="footer-section">
          <h3>Contacto</h3>
          <p><i class="fas fa-envelope"></i> info@skygreen.com</p>
          <p><i class="fas fa-phone"></i> +591 4 123-4567</p>
          <p><i class="fas fa-map-marker-alt"></i> Cochabamba, Bolivia</p>
        </div>

        <div class="footer-section">
          <h3>Enlaces Útiles</h3>
          <p><a href="info-legal.html" style="color: #ccc;">Información Legal</a></p>
          <p><a href="administrador.php" style="color: #ccc;">Panel de Administración</a></p>
        </div>

        <div class="footer-section">
          <h3>Síguenos</h3>
          <p><i class="fab fa-facebook"></i> Facebook</p>
          <p><i class="fab fa-instagram"></i> Instagram</p>
          <p><i class="fab fa-whatsapp"></i> WhatsApp</p>
        </div>
      </div>

      <div class="footer-bottom">
        <p>&copy; 2024 SkyGreen. Todos los derechos reservados.</p>
      </div>
    </div>
  </footer>

  <script>
    // Mapbox configuration
    mapboxgl.accessToken = 'pk.eyJ1IjoiYWxlc3NpcyIsImEiOiJjbGcxbHBtbHQwdDU5M2RubDFodjY3a2x0In0.NXe43GdM4PJBj7ow0Dnkpw';

    const map = new mapboxgl.Map({
      container: 'map',
      style: 'mapbox://styles/mapbox/outdoors-v11',
      center: [-66.158468, -17.374908],
      zoom: 17,
      pitch: 50,
      bearing: -17.6
    });

    // Get trees data from PHP
    const arboles = <?php echo json_encode($arboles); ?>;
    let allMarkers = [];

    // URL parameter function
    function getURLParameter(name) {
      const urlParams = new URLSearchParams(window.location.search);
      return urlParams.get(name);
    }

    map.on('load', function() {
      // Load all trees
      arboles.forEach(arbol => {
        const coordinates = arbol.coordenadas.replace('POINT(', '').replace(')', '').split(' ');
        const lng = parseFloat(coordinates[0]);
        const lat = parseFloat(coordinates[1]);

        if (isNaN(lng) || isNaN(lat)) {
          console.error("Invalid coordinates:", arbol);
          return;
        }

        // Create marker element
        const el = document.createElement('div');
        el.className = 'tree-marker';

        // Set border color based on tree state
        switch (arbol.estado.toLowerCase()) {
          case 'peligrosos':
            el.style.border = '3px solid #ff4757';
            break;
          case 'protegido':
            el.style.border = '3px solid #ffa502';
            break;
          case 'nativo':
            el.style.border = '3px solid #2ed573';
            break;
          default:
            el.style.border = '3px solid #57606f';
        }

        // Create popup content
        const popupContent = `
          <div class="popup-header">
            <i class="fas fa-tree"></i>
            <h3>${arbol.especie}</h3>
          </div>
          
          <img src="${arbol.fotoUrl}" alt="Foto del árbol" class="popup-image">
          
          <div class="popup-info">
            <i class="fas fa-calendar"></i>
            <strong>Edad:</strong> ${arbol.edad} años
          </div>
          
          <div class="popup-info">
            <i class="fas fa-arrows-alt-v"></i>
            <strong>Altura:</strong> ${arbol.altura} m
          </div>
          
          <div class="popup-info">
            <i class="fas fa-circle"></i>
            <strong>Diámetro:</strong> ${arbol.diametroTronco} cm
          </div>
          
          <div class="popup-info">
            <i class="fas fa-heart"></i>
            <strong>Cuidados:</strong> ${arbol.cuidados}
          </div>
          
          <div class="popup-info">
            <i class="fas fa-shield-alt"></i>
            <strong>Estado:</strong> ${arbol.estado}
          </div>
          
          <img src="${arbol.qrUrl}" alt="QR del árbol" class="qr-code">
        `;

        // Create popup
        const popup = new mapboxgl.Popup({
          offset: [20, -70],
          closeButton: true,
          closeOnClick: true
        }).setHTML(popupContent);

        // Create marker
        const marker = new mapboxgl.Marker(el)
          .setLngLat([lng, lat])
          .setPopup(popup)
          .addTo(map);

        // Store marker with ID for reference
        allMarkers.push({
          id: arbol.id,
          marker: marker,
          popup: popup,
          coordinates: [lng, lat],
          arbol: arbol
        });
      });

      // Check for tree_id in URL (QR code functionality)
      const treeId = getURLParameter('tree_id');
      if (treeId) {
        setTimeout(() => {
          openTreePopup(parseInt(treeId));
        }, 1000);
      }
    });

    // Function to open specific tree popup
    function openTreePopup(treeId) {
      const targetMarker = allMarkers.find(item => item.id == treeId);
      
      if (targetMarker) {
        // Fly to specific tree
        map.flyTo({
          center: targetMarker.coordinates,
          zoom: 18,
          essential: true
        });

        // Open popup after animation
        setTimeout(() => {
          targetMarker.popup.addTo(map);
        }, 1000);

        // Clean URL after showing tree
        if (window.history && window.history.replaceState) {
          const url = new URL(window.location.href);
          url.searchParams.delete('tree_id');
          window.history.replaceState({}, document.title, url.pathname + url.hash);
        }
      } else {
        console.error("Tree not found with ID:", treeId);
        alert("Árbol no encontrado. El código QR podría estar dañado o el árbol ya no existe.");
      }
    }

    // Header scroll effect
    window.addEventListener('scroll', function() {
      const header = document.querySelector('.header');
      if (window.scrollY > 100) {
        header.style.background = 'rgba(255, 255, 255, 0.98)';
        header.style.boxShadow = '0 2px 30px rgba(0, 0, 0, 0.15)';
      } else {
        header.style.background = 'rgba(255, 255, 255, 0.95)';
        header.style.boxShadow = '0 2px 20px rgba(0, 0, 0, 0.1)';
      }
    });

    // Smooth scrolling for navigation links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
      anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
          const offsetTop = target.offsetTop - 80;
          window.scrollTo({
            top: offsetTop,
            behavior: 'smooth'
          });
        }
      });
    });

    // Animation on scroll
    const observerOptions = {
      threshold: 0.1,
      rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver(function(entries) {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.style.opacity = '1';
          entry.target.style.transform = 'translateY(0)';
        }
      });
    }, observerOptions);

    // Observe elements for animation
    document.querySelectorAll('.feature-card, .honor-card').forEach(el => {
      el.style.opacity = '0';
      el.style.transform = 'translateY(30px)';
      el.style.transition = 'all 0.6s ease';
      observer.observe(el);
    });
  </script>
</body>
</html>