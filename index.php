<?php
$conn = new mysqli("host.docker.internal", "root", "", "reforest", 3306);
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Consulta completa con todos los campos y funciones SQL apropiadas
$sql = "SELECT id, especie, nombre_comun, edad, estado, fotoUrl, altura, diametroTronco, diametro_copa, codigo_arbol, ST_AsText(coordenadas) as coordenadas, latitud, longitud, propiedad, otb, nombre_area_verde, inspector, pdfUrl, qrUrl, DATE_FORMAT(fecha_registro, '%d/%m/%y') as fecha_formato, hora_registro FROM arboles";

$result = $conn->query($sql);
$arboles = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $arboles[] = $row;
    }
}

$conn->close();

// Para verificar que funciona correctamente, puedes descomentar la siguiente línea:
// print_r($arboles);
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Emavra - Transformando Cochabamba</title>
  <meta name="description" content="Plataforma Web Ambiental en colaboración con la Empresa Municipal de Áreas Verdes de Cochabamba">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <script src="https://api.mapbox.com/mapbox-gl-js/v2.8.1/mapbox-gl.js"></script>
  <link href="https://api.mapbox.com/mapbox-gl-js/v2.8.1/mapbox-gl.css" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;700&display=swap" rel="stylesheet">

  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Poppins', sans-serif;
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
      font-family: 'Arciform', 'Poppins', sans-serif;
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
      font-family: 'Poppins', sans-serif;
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
      font-family: 'Poppins', sans-serif;
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
      font-family: 'Arciform', 'Poppins', sans-serif;
    }

    .hero-subtitle {
      font-family: 'Poppins', sans-serif;
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
      font-family: 'Poppins', sans-serif;
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
      padding: 50px 0;
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
      font-family: 'Arciform', 'Poppins', sans-serif;
    }

    .section-subtitle {
      font-size: 1.1rem;
      color: #666;
      max-width: 600px;
      margin: 0 auto;
      font-family: 'Poppins', sans-serif;
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
      padding: 50px 0;
      background: white;
    }

    .map-container {
      background: white;
      border-radius: 20px;
      padding: 3rem;
      box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
      
      position: relative;
      max-width: 900px;
      margin: 0 auto;
    }

    #map {
      width: calc(90% - 3rem);
      height: 580px;
      border-radius: 15px;
      margin: 0 auto;
    }

    .map-legend {
  position: absolute;
  bottom: 700px;/* Anclado a 50px de la parte superior del contenedor del mapa */
  right: 900px;/* Anclado a 50px de la parte derecha del contenedor del mapa */
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(10px);
  padding: 1rem;
  border-radius: 10px;
  font-family: 'Poppins', sans-serif;
  box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
  z-index: 100; /* Baja el z-index para que el Header (1000) siempre esté encima */
  min-width: 160px;
  font-size: 0.9rem;
}

    .map-legend h4 {
      margin: 0 0 0.8rem;
      font-size: 0.9rem;
      color: #333;
      font-weight: 500;
    }

    .legend-item {
      display: flex;
      align-items: center;
      margin-bottom: 0.6rem;
      font-size: 0.8rem;
    }

    .legend-icon {
      width: 12px;
      height: 12px;
      border-radius: 50%;
      margin-right: 8px;
      border: 2px solid;
    }

    .legend-icon.protected { background: #ff4757; border-color: #ff4757; }
    .legend-icon.native { background: #2ed573; border-color: #2ed573; }
    .legend-icon.dangerous { background: #ffa502; border-color: #ffa502; }

    /* Honor Wall */
    .honor-wall {
      padding: 50px 0;
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
        transform: translateY(50px);
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
      transform: scale(1.2);
    }

    /* Popup Styles */
    .mapboxgl-popup-content {
      border-radius: 15px;
      padding: 1.2rem;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25);
      max-width: 250px;
    
      font-family: 'Poppins', sans-serif;
      background: white;
    }

    .mapboxgl-popup-close-button {
      color: #c9c9c9ff;
      font-size: 18px;
      padding: 5px;
    }

    .popup-header {
      display: flex;
      align-items: center;
      margin-bottom: 1rem;
      padding-bottom: 0.8rem;
      border-bottom: 1px solid #eee;
    }

    .popup-header i {
      margin-right: 0.7rem;
      color: #3ebeab;
      font-size: 1.2rem;
    }

    .popup-header h3 {
      margin: 0;
      font-size: 1.1rem;
      color: #333;
      font-weight: 600;
    }

    .popup-image {
      width: 100%;
      height: 150px;
      object-fit: cover;
      border-radius: 10px;
      margin-bottom: 1rem;
    }

    .popup-info {
      font-size: 0.9rem;
      line-height: 1.5;
      margin-bottom: 0.1rem;
      display: flex;
      align-items: center;
    }

    .popup-info i {
      width: 18px;
      color: #666;
      margin-right: 8px;
      font-size: 0.9rem;
    }

    .popup-info strong {
      color: #333;
      margin-right: 5px;
    }

    .pdf-container {
      text-align: center;
      margin: 1rem 0;
    }

    .pdf-button {
      background: linear-gradient(135deg, #482e83, #685ca8);
      color: white;
      padding: 0.6rem 1.2rem;
      border-radius: 20px;
      text-decoration: none;
      font-size: 0.85rem;
      font-weight: 500;
      transition: all 0.3s ease;
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
    }

    .pdf-button:hover {
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(72, 46, 131, 0.3);
    }

    .qr-code {
      width: 80px;
      height: 80px;
      margin: 1rem auto 0;
      display: block;
      border-radius: 8px;
      border: 1px solid #eee;
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
        top: auto;
        right: auto;
      }

      .map-container {
        padding: 1rem;
        margin: 0 1rem;
      }

      
      /* Oculta la leyenda del mapa */
    .map-legend {
        display: none; }

    /* Opcional: Centra el mapa si lo deseas en móviles */
    #map {
      width: 100%;
      height: 600px;
    }
    }

    /* Scroll behavior */
    html {
      scroll-behavior: smooth;
    }
    .logo-img {
  height: 50px;   /* ajusta el tamaño del logo */
  width: auto;    /* mantiene la proporción */
  vertical-align: middle; /* lo alinea con el menú */
}

    
  </style>
</head>

<body>
  <!-- Header -->
  <header class="header">
    <nav class="nav">
      <a href="#home" class="logo">
        <img src="img/logoemavrita.png" alt="Emavra Logo" class="logo-img">
      </a>
      <ul class="nav-links">
        <li><a href="#about">¿Qué hacemos?</a></li>
        <li><a href="#map">Mapa de Árboles</a></li>
        <li><a href="#honor">Muro de Honor</a></li>
        <li><a href="#contact"class="btn-admin">Contacto</a></li>
        
      </ul>
    </nav>
  </header>

  <!-- Hero Section -->
  <section id="home" class="hero">
    <div class="hero-content">
      
      <h1>Bienvenido a Emavra</h1>
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
            Descubre cada rincón verde de la Plaza 14 De Septiembre, identificando árboles según 
            su tipo con información detallada.
          </p>
        </div>

        <div class="feature-card">
          <div class="feature-icon">
            <i class="fas fa-seedling"></i>
          </div>
          <h3 class="feature-title">Cuidado de Árboles</h3>
          <p class="feature-description">
            Conviértete en un guardián del verde. 
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
            Aprende sobre la sostenibilidad y participa en el impacto ambiental.
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
            <span class="legend-icon native"></span>
            Árboles Nativos
          </div>
          <div class="legend-item">
            <span class="legend-icon dangerous"></span>
            Árboles Exóticos
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Honor Wall -->
  <section id="honor" class="honor-wall">
    <div class="container">
      <div class="section-header">
        <h2 class="section-title">🌳 Muro de Héroes Anónimos</h2>
        <p class="section-subtitle">
          Conoce a quienes están haciendo la diferencia en nuestro ecosistema urbano.
        </p>
      </div>

      <div class="honor-grid">
        <div class="honor-card">
          <img src="img/person1.jpg" alt="Voluntario 1" class="honor-avatar">
          <h3 class="honor-name">Voluntarios de Reforestación</h3>
          <p class="honor-contribution">🌿 Plantaron más de 100 plantines en áreas urbanas clave.</p>
        </div>

        <div class="honor-card">
          <img src="img/person2.jpg" alt="Voluntario 2" class="honor-avatar">
          <h3 class="honor-name">Guardianes de Parques</h3>
          <p class="honor-contribution">🌿 Se dedican al cuidado y mantenimiento de jardines públicos.</p>
        </div>

        <div class="honor-card">
          <img src="img/person3.jpg" alt="Voluntario 3" class="honor-avatar">
          <h3 class="honor-name">Defensores Ambientales</h3>
          <p class="honor-contribution">🌿 Promueven el uso de plantas nativas en el paisajismo.</p>
        </div>

        <div class="honor-card">
          <img src="img/person4.jpg" alt="Voluntario 4" class="honor-avatar">
          <h3 class="honor-name">Educadores Comunitarios</h3>
          <p class="honor-contribution">🌿 Lideran talleres sobre la importancia de la flora local.</p>
        </div>

        <div class="honor-card">
          <img src="img/person5.jpg" alt="Voluntario 5" class="honor-avatar">
          <h3 class="honor-name">Innovadores Ecológicos</h3>
          <p class="honor-contribution">🌿 Crearon un sistema de riego sostenible para áreas verdes.</p>
        </div>

        <div class="honor-card">
          <img src="img/person6.jpg" alt="Voluntario 6" class="honor-avatar">
          <h3 class="honor-name">Colaboradores Vecinales</h3>
          <p class="honor-contribution">🌿 Participan activamente en las campañas de limpieza y siembra.</p>
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
          <p><a <a href="https://www.lostiempos.com/sites/default/files/ayma2021guiadeselecciondeespeciesparaelarboladourbanodecochabambaparacompartir_1_0.pdf" style="color: #ccc;">Información Legal</a></p>
          
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
      center: [-66.156977, -17.393838],
      zoom: 17,
      pitch: 25,
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
          
          case 'exótico':
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
            <i class="fas fa-circle"></i>
            <strong>Nombre común:</strong> ${arbol.nombre_comun}
          </div>
          
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
            <i class="fas fa-shield-alt"></i>
            <strong>Estado:</strong> ${arbol.estado}
          </div>
          
        ${arbol.pdfUrl ? `
  <div class="popup-info pdf-container">
    <a href="${arbol.pdfUrl}" target="_blank" class="pdf-button">
      <i class="fas fa-file-pdf"></i> Ver más información
    </a>
  </div>
` : ''}
          
          
          
          <img src="${arbol.qrUrl}" alt="QR del árbol" class="qr-code">
        `;

        // Create popup
        const popup = new mapboxgl.Popup({
          offset: [0, -250],
          closeButton: true,
          closeOnClick: false,
          maxWidth: '300px'
        }).setHTML(popupContent);

        // Create marker
        const marker = new mapboxgl.Marker(el)
          .setLngLat([lng, lat])
          .setPopup(popup)
          .addTo(map);

        // Add click event to marker for zoom effect
        el.addEventListener('click', () => {
          // Fly to marker with smooth animation
          map.flyTo({
            center: [lng, lat],
            zoom: 19,
            pitch: 30,
            bearing: 0,
            essential: true,
            duration: 800
          });
          
          // Open popup after animation
          setTimeout(() => {
            popup.addTo(map);
          }, 1000);
        });

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
        // Fly to specific tree with enhanced zoom
        map.flyTo({
          center: targetMarker.coordinates,
          zoom: 19,
          pitch: 60,
          bearing: 0,
          essential: true,
          duration: 2500
        });

        // Open popup after animation
        setTimeout(() => {
          targetMarker.popup.addTo(map);
        }, 1500);

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