<?php
$conn = new mysqli("localhost", "root", "", "reforest", 3306);
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
<html class="h-100" lang="es">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1,shrink-to-fit=no" />
  <meta name="description" content="A growing collection of ready to use components for the CSS framework Bootstrap 5" />
  <link rel="apple-touch-icon" sizes="180x180" href="img/apple-touch-icon.png" />
  <link rel="icon" type="image/png" sizes="32x32" href="img/favicon-32x32.png" />
  <link rel="icon" type="image/png" sizes="16x16" href="img/favicon-16x16.png" />
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />

  <link rel="icon" type="image/png" sizes="96x96" href=".t/img/favicon.png" />
  <meta name="author" content="Holger Koenemann" />
  <meta name="generator" content="Eleventy v2.0.0" />
  <meta name="HandheldFriendly" content="true" />

  <link rel="stylesheet" href="css/theme.min.css" />
  <script src="https://api.mapbox.com/mapbox-gl-js/v2.4.1/mapbox-gl.js"></script>
  <link href="https://api.mapbox.com/mapbox-gl-js/v2.4.1/mapbox-gl.css" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;700&display=swap" rel="stylesheet">

  
  <script>
    mapboxgl.accessToken =
      "pk.eyJ1IjoiYWxlc3NpcyIsImEiOiJjbGcxbHBtbHQwdDU5M2RubDFodjY3a2x0In0.NXe43GdM4PJBj7ow0Dnkpw";
  </script>
 
  <style>
    .pdf-button {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 16px;
  background: linear-gradient(135deg, #ff4e50, #f9d423);
  color: white;
  font-weight: bold;
  border-radius: 12px;
  text-decoration: none;
  font-size: 14px;
  box-shadow: 0 4px 10px rgba(0,0,0,0.15);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.pdf-button:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 14px rgba(0,0,0,0.25);
}

.pdf-button i {
  font-size: 18px;
}

    #skygreen-bot {
      position: fixed;
      bottom: 20px;
      right: 20px;
      width: 320px;
      background: rgb(255, 255, 255);
      border: 2px solidrgb(41, 43, 42);
      border-radius: 12px;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
      font-family: Arial, sans-serif;
      z-index: 9999;
    }

    #skygreen-header {
      background-color: #333940;
      color: white;
      padding: 10px;
      font-weight: bold;
      border-top-left-radius: 10px;
      border-top-right-radius: 10px;
    }

    #skygreen-body {
      padding: 10px;
    }

    #skygreen-question {
      width: 100%;
      padding: 8px;
      margin-bottom: 8px;
      border: 1px solid #ccc;
      border-radius: 6px;
    }

    #skygreen-body button {
      background-color: #333940;
      color: white;
      padding: 8px 12px;
      border: none;
      border-radius: 6px;
      cursor: pointer;
    }

    #skygreen-response {
      margin-top: 10px;
      font-size: 14px;
    }

    #skygreen-image iframe {
      width: 100%;
      height: 200px;
      border: none;
      margin-top: 10px;
    }

    /* inter-300 - latin */
    @font-face {
      font-family: "Inter";
      font-style: normal;
      font-weight: 300;
      font-display: swap;
      src: local(""), url("./fonts/inter-v12-latin-300.woff2") format("woff2"),
        /* Chrome 26+, Opera 23+, Firefox 39+ */
        url("./fonts/inter-v12-latin-300.woff") format("woff");
      /* Chrome 6+, Firefox 3.6+, IE 9+, Safari 5.1+ */
    }

    @font-face {
      font-family: "Inter";
      font-style: normal;
      font-weight: 500;
      font-display: swap;
      src: local(""), url("./fonts/inter-v12-latin-500.woff2") format("woff2"),
        /* Chrome 26+, Opera 23+, Firefox 39+ */
        url("./fonts/inter-v12-latin-500.woff") format("woff");
      /* Chrome 6+, Firefox 3.6+, IE 9+, Safari 5.1+ */
    }

    @font-face {
      font-family: "Inter";
      font-style: normal;
      font-weight: 700;
      font-display: swap;
      src: local(""), url("./fonts/inter-v12-latin-700.woff2") format("woff2"),
        /* Chrome 26+, Opera 23+, Firefox 39+ */
        url("./fonts/inter-v12-latin-700.woff") format("woff");
      /* Chrome 6+, Firefox 3.6+, IE 9+, Safari 5.1+ */
    }

    /* Estilos generales para el carrusel y los formularios */
    .carousel-container {
      width: 550px;
      overflow: hidden;
      margin: 0 auto;
    }

    .carousel {
      display: flex;
      transition: transform 0.5s;
    }

    .slide {
      flex: 0 0 100%;
      width: 500px;
      padding: 20px;

      background-color: #f9f9f9;
      box-shadow: 0 0 5px rgba(0, 0, 0, 0.3);
      display: none;
    }

    .titulo-pagina {
      font-size: 80px;
      font-weight: bold;

      /* Cambia el color según tu preferencia */
      text-align: left;
      /* Otros estilos adicionales según tus necesidades */
    }

    #modal {
      display: none;
      position: fixed;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      z-index: 1000;
      /* Ajusta este valor según sea necesario */
    }

    .modal-contenido {
      background-color: white;
      margin: 5% auto;
      /* Ajusta el margen superior según sea necesario */
      padding: 20px;
      border: 1px solid #888;
      max-width: 100%;
      /* Ajusta el ancho máximo del modal según sea necesario */
      border-radius: 30px;
      box-shadow: 0 0 20px rgba(0, 0, 0, 0.2);
      position: relative;
      z-index: 1001;
      /* Asegura que el contenido del modal esté por encima del fondo del modal */
    }

    .cerrar {
      color: #aaa;
      float: right;
      font-size: 28px;
      font-weight: bold;
      cursor: pointer;
      z-index: 1002;
      /* Asegura que el botón de cerrar esté por encima del contenido del modal */
    }

    .cerrar:hover,
    .cerrar:focus {
      color: black;
      text-decoration: none;
      cursor: pointer;
    }

    /* Estilos personalizados para el mapa */
    #map {
      width: 100%;
    }

    .tree-marker {
      border-radius: 50%;

      background-color: cover;
    }

    .map-legend {
      position: absolute;
      top: 10px;
      left: 10px;
      background-color: rgba(255, 255, 255, 0.8);
      /* Fondo semitransparente */
      padding: 10px;
      border-radius: 5px;
      font-family: Arial, sans-serif;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
      /* Sombra para resaltar la leyenda */
      z-index: 1000;
      /* Z-index alto para asegurar que esté sobre el mapa */
    }

    .map-legend h4 {
      margin: 0 0 10px;
      font-size: 14px;
    }

    .legend-icon {
      display: inline-block;
      width: 12px;
      height: 12px;
      margin-right: 8px;
      border-radius: 2px;
    }

    .legend-icon.protected {
      background-color: #ff0000;
      /* Color rojo para Árboles Protegidos */
    }

    .legend-icon.native {
      background-color: #00ff00;
      /* Color verde para Árboles Nativos */
    }

    .legend-icon.dangerous {
      background-color: #ffcc00;
      /* Color amarillo para Árboles Peligrosos */
    }

    .container-mapa {
      max-width: 1000px;
      /* Cambia este valor según el ancho que prefieras */
      margin: 0 auto;
      /* Centra el contenedor horizontalmente */

      /* Opcional: añade espacio alrededor del mapa */
    }

    #map {
      width: 100%;
      height: 90vh;
    }

    /* Estilos del popup */
    .mapboxgl-popup {
      max-width: 250px;
      font-family: Arial, sans-serif;
      opacity: 0;
      transform: scale(0.8);
      transition: opacity 0.3s ease, transform 0.3s ease;
    }

    .mapboxgl-popup-content {
      border-radius: 10px;
      padding: 10px;
      text-align: center;
      background-color: rgba(255, 255, 255, 0.9);
      box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.2);
      position: relative;
    }

    .mapboxgl-popup-content h3 {
      margin: 0;
      font-size: 16px;
      color: #2a6d2a;
    }

    .mapboxgl-popup-content img {
      width: 100%;
      border-radius: 5px;
      margin: 5px 0;
    }

    /* Botón de cierre */
    .close-popup {
      position: absolute;
      top: 5px;
      right: 8px;
      background: red;
      color: white;
      border: none;
      font-size: 14px;
      width: 20px;
      height: 20px;
      line-height: 20px;
      text-align: center;
      cursor: pointer;
      border-radius: 50%;
    }
  </style>
</head>

<body data-bs-spy="scroll" data-bs-target="#navScroll">
  <nav id="navScroll" class="navbar navbar-expand-lg navbar-light fixed-top" tabindex="0" style="background-color: #f9f9f9e0">
    <div class="container">
      <a class="navbar-brand pe-4 fs-4" href="#top">
        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" class="bi bi-layers-half" viewbox="0 0 16 16"></svg>

        <span class="ms-1 fw-bolde">SkyGreen<i class="bx bxs-tree-alt"></i></span>
      </a>

      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarSupportedContent">
        <ul class="navbar-nav me-auto mb-2 mb-lg-0">
          <li class="nav-item">
            <a class="nav-link" href="#aboutus"> ¿Que hacemos? </a>
          </li>
          <!--<li class="nav-item">
              <a class="nav-link" href="#numbers"></a>
            </li>-->
          <li class="nav-item">
            <a class="nav-link" href="#map"> Árboles Registrados </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#workwithus"> Muro de Honor </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="info-legal.html"> Más Información </a>
          </li>
         
          <li class="nav-item">
            <a class="nav-link btn btn-dark shadow rounded-0" style="color: white" href="administrador.php">
              Panel
            </a>
          </li>

        </ul>
      </div>
    </div>
  </nav>
  <div class="w-100 overflow-hidden bg-gray-100" id="top">
    <div class="container position-relative">
      <div class="col-12 col-lg-8 mt-0 h-100 position-absolute top-0 end-0 bg-cover" data-aos="fade-left" style="background-image: url(img/pla.jpg)"></div>
      <div class="row">
        <div class="col-lg-7 py-vh-6 position-relative" data-aos="fade-right">
          <h1 class="display-1 fw-bold mt-5">
            Bienvenido a SkyGreen
          </h1>
          <p class="lead">
            Transformamos Cochabamba. <br>
            Nuestra misión es construir un futuro más verde y sostenible.
            Nos complace presentar la Plataforma Web Ambiental, una innovadora iniciativa en colaboración con la Empresa Municipal de Áreas Verdes. Aquí, la comunidad y la naturaleza se unen.
            ¡Explora y participa en la transformación verde!
          </p>
          <a href="#aboutus" class="btn btn-dark btn-xl shadow me-3 rounded-0 my-5">Conoce mas sobre nosotros</a>
        </div>
      </div>
    </div>
  </div>

  <div class="py-vh-4 bg-gray-100 w-100 overflow-hidden" id="aboutus">
    <div class="container">
      <div class="row d-flex justify-content-between align-items-center">
        <div class="col-lg-6">
          <div class="row gx-5 d-flex">
            <div class="col-md-11">
              <div class="shadow ratio ratio-16x9 rounded bg-cover bp-center align-self-end" data-aos="fade-right" style="
                      background-image: url(img/mace.jpg);
                      --bs-aspect-ratio: 50%;
                    "></div>
            </div>
            <div class="col-md-5 offset-md-1">
              <div class="shadow ratio ratio-1x1 rounded bg-cover mt-5 bp-center float-end" data-aos="fade-up" style="background-image: url(img/mace2.jpg)"></div>
            </div>
            <div class="col-md-6">
              <div class="col-12 shadow ratio rounded bg-cover mt-5 bp-center" data-aos="fade-left" style="
                      background-image: url(img/mac4.webp);
                      --bs-aspect-ratio: 150%;
                    "></div>
            </div>
          </div>
        </div>
        <div class="col-lg-4">
          <h3 class="py-5 border-top border-dark" data-aos="fade-left">
            ¿Qué Hacemos?
          </h3>
          <p data-aos="fade-left" data-aos-delay="200">
            Mapa Interactivo: Descubre cada rincón verde de la Zona Norte, identificando árboles, parques y áreas verdes. Explora un mapa detallado que clasifica los árboles según su estado: protegidos, nativos y peligrosos.
            <br>
            Información Detallada: Aprende sobre las especies de árboles, su historia y los cuidados necesarios para su florecimiento. Accede a datos específicos como la edad de los árboles y consejos para su mantenimiento.
            <br>
            Eventos y Talleres: Únete a nuestras actividades comunitarias, aprende sobre jardinería sostenible y participa en la siembra de árboles. Promovemos la reforestación y te invitamos a registrar tus plantaciones en nuestra plataforma web.
          </p>
        </div>
      </div>
    </div>
  </div>
  <div class="small py-vh-3 w-100 overflow-hidden">
    <div class="container">
      <div class="row">
        <div class="col-md-6 col-lg-4 border-end" data-aos="fade-up">
          <div class="d-flex">
            <div class="col-md-3 flex-fill pt-3 pe-3 pe-md-0">
              <svg xmlns="http://www.w3.org/2000/svg" width="42" height="42" fill="currentColor" class="bi bi-box-seam" viewbox="0 0 16 16">
                <path d="M8.186 1.113a.5.5 0 0 0-.372 0L1.846 3.5l2.404.961L10.404 2l-2.218-.887zm3.564 1.426L5.596 5 8 5.961 14.154 3.5l-2.404-.961zm3.25 1.7-6.5 2.6v7.922l6.5-2.6V4.24zM7.5 14.762V6.838L1 4.239v7.923l6.5 2.6zM7.443.184a1.5 1.5 0 0 1 1.114 0l7.129 2.852A.5.5 0 0 1 16 3.5v8.662a1 1 0 0 1-.629.928l-7.185 2.874a.5.5 0 0 1-.372 0L.63 13.09a1 1 0 0 1-.63-.928V3.5a.5.5 0 0 1 .314-.464L7.443.184z" />
              </svg>
            </div>
            <div class="col-md-9 flex-fill">
              <h3 class="h5 my-2">Registro de Árboles:</h3>
              <p>
                Registra tu árbol y conviértete en un guardián del verde en tu vecindario.
              </p>
              <h3 class="h5 my-2">Guía del arbolado urbano en Cochabamba</h3>
              <p>
                <a href="https://www.lostiempos.com/sites/default/files/ayma2021guiadeselecciondeespeciesparaelarboladourbanodecochabambaparacompartir_1_0.pdf" target="_blank">Informate aquí....</a>
              </p>
            </div>
          </div>
        </div>
        <div class="col-md-6 col-lg-4 border-end" data-aos="fade-up" data-aos-delay="200">
          <div class="d-flex">
            <div class="col-md-3 flex-fill pt-3 pt-3 pe-3 pe-md-0">
              <svg xmlns="http://www.w3.org/2000/svg" width="42" height="42" fill="currentColor" class="bi bi-card-checklist" viewbox="0 0 16 16">
                <path d="M14.5 3a.5.5 0 0 1 .5.5v9a.5.5 0 0 1-.5.5h-13a.5.5 0 0 1-.5-.5v-9a.5.5 0 0 1 .5-.5h13zm-13-1A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h13a1.5 1.5 0 0 0 1.5-1.5v-9A1.5 1.5 0 0 0 14.5 2h-13z" />
                <path d="M7 5.5a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5zm-1.496-.854a.5.5 0 0 1 0 .708l-1.5 1.5a.5.5 0 0 1-.708 0l-.5-.5a.5.5 0 1 1 .708-.708l.146.147 1.146-1.147a.5.5 0 0 1 .708 0zM7 9.5a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5zm-1.496-.854a.5.5 0 0 1 0 .708l-1.5 1.5a.5.5 0 0 1-.708 0l-.5-.5a.5.5 0 0 1 .708-.708l.146.147 1.146-1.147a.5.5 0 0 1 .708 0z" />
              </svg>
            </div>
            <div class="col-md-9 flex-fill">
              <h3 class="h5 my-2">Especies de Árboles Legales y Recomendadas para Plantar en Cochabamba</h3>
              <p>
                <a href="https://es.wikipedia.org/wiki/Polylepis" target="_blank">Kewiña (Polylepis spp.)</a>
                <br>
                <a href="https://es.wikipedia.org/wiki/Alnus_acuminata" target="_blank">Aliso (Alnus acuminata)</a>
                <br>
                <a href="https://www.minsal.cl/portal/url/item/7d99ff5a580fdbd7e04001011f016dc3.pdf" target="_blank">Molle (Schinus molle)</a>
                <br>
                <a href="https://es.wikipedia.org/wiki/Cinchona_officinalis" target="_blank">Quina (Cinchona officinalis)</a>
                <br>
                <a href="https://ciudadesverdes.com/arboles/jacaranda-mimosifolia/" target="_blank">Tarco (Jacaranda mimosifolia)</a>
                <br>
                <a href="https://es.wikipedia.org/wiki/Buddleja_coriacea" target="_blank">Kari Kari (Buddleja coriacea)</a>
                <br>
                <a href="https://sib.gob.ar/especies/tipuana-tipu" target="_blank">Tipa (Tipuana tipu)</a>
              </p>
            </div>

          </div>
        </div>

        <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="400">
          <div class="d-flex">
            <div class="col-md-3 flex-fill pt-3 pt-3 pe-3 pe-md-0">
              <svg xmlns="http://www.w3.org/2000/svg" width="42" height="42" fill="currentColor" class="bi bi-window-sidebar" viewbox="0 0 16 16">
                <path d="M2.5 4a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1zm2-.5a.5.5 0 1 1-1 0 .5.5 0 0 1 1 0zm1 .5a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z" />
                <path d="M2 1a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V3a2 2 0 0 0-2-2H2zm12 1a1 1 0 0 1 1 1v2H1V3a1 1 0 0 1 1-1h12zM1 13V6h4v8H2a1 1 0 0 1-1-1zm5 1V6h9v7a1 1 0 0 1-1 1H6z" />
              </svg>
            </div>
            <div class="col-md-9 flex-fill">
              <h3 class="h5 my-2">Seguimiento y Transparencia</h3>
              <p>
                Ofrecemos un sistema transparente donde pueden hacer un seguimiento del progreso de las
                plantaciones, proporcionando actualizaciones periódicas y
                datos detallados sobre las plantaciones realizadas.
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="py-vh-5 w-100 overflow-hidden" id="numbers">
    <div class="container">
      <div class="row d-flex justify-content-between align-items-center">
        <div class="col-lg-5">
          <h3 class="py-5 border-top border-dark" data-aos="fade-right">
            Información Educativa
          </h3>
        </div>
        <div class="col-lg-6">
          <div class="row">
            <div class="col-12">
              <h2 class="display-6 mb-5" data-aos="fade-down">
                Importancia de la Reforestación
              </h2>
            </div>
            <div class="col-lg-6" data-aos="fade-up">
              <div class="display-1 fw-bold py-4">80%</div>
              <p class="text-black-50">
                Los bosques albergan al menos el 80% de la biodiversidad
                terrestre, proporcionando hogar y refugio para innumerables
                especies de plantas y animales.
              </p>
            </div>
            <div class="col-lg-6" data-aos="fade-up">
              <div class="display-1 fw-bold py-4">26,000 millas</div>
              <p class="text-black-50">
                Un acre de árboles puede absorber el dióxido de carbono
                producido por un automóvil que recorre 26,000 millas.
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="container-mapa">
    <div class="position-relative overflow-hidden bg-light" id="map" style="height: 550px; width: 100%;">
      <div class="map-legend">
        <h4>Categorías</h4>
        
        <div><span class="legend-icon native"></span> Árboles Nativos</div>
        <div><span class="legend-icon dangerous"></span> Árboles Exoticos</div>
      </div>
    </div>
  </div>
  <div class="container py-vh-4 w-100 overflow-hidden" id="workwithus">
    <div class="row d-flex justify-content-center align-items-center">
      <div class="col-lg-5">
        <h3 class="py-5 border-top border-dark" data-aos="fade-right">
          🌳 Muro de Honor: Héroes de la Reforestación 🌳
        </h3>
        <p>
          Estos son los héroes que están ayudando a restaurar nuestro ecosistema.
          ¡Gracias por formar parte de SkyGreen!
        </p>
      </div>
    </div>

    <div class="row">
      <!-- Tarjeta de Persona 1 -->
      <div class="col-md-4 mb-4" data-aos="fade-up">
        <div class="card text-center" style="background-color: #333940; color: #e0e0e0; border-radius: 12px;">
          <div class="card-body">
            <img src="img/person1.jpg" class="rounded-circle mb-3" alt="Persona 1" width="80" height="80">
            <h5 class="card-title">Alejandro Pérez</h5>
            <p class="card-text">🌿 Ha adoptado un <b>Pino</b></p>
          </div>
        </div>
      </div>

      <!-- Tarjeta de Persona 2 -->
      <div class="col-md-4 mb-4" data-aos="fade-up">
        <div class="card text-center" style="background-color: #333940; color: #e0e0e0; border-radius: 12px;">
          <div class="card-body">
            <img src="img/person2.jpg" class="rounded-circle mb-3" alt="Persona 2" width="80" height="80">
            <h5 class="card-title">María Gómez</h5>
            <p class="card-text">🌿 Ha adoptado un <b>Roble</b></p>
          </div>
        </div>
      </div>

      <!-- Tarjeta de Persona 3 -->
      <div class="col-md-4 mb-4" data-aos="fade-up">
        <div class="card text-center" style="background-color: #333940; color: #e0e0e0; border-radius: 12px;">
          <div class="card-body">
            <img src="img/person3.jpg" class="rounded-circle mb-3" alt="Persona 3" width="80" height="80">
            <h5 class="card-title">Carlos Rodríguez</h5>
            <p class="card-text">🌿 Ha adoptado un <b>Ciprés</b></p>
          </div>
        </div>
      </div>

      <!-- Tarjeta de Persona 4 -->
      <div class="col-md-4 mb-4" data-aos="fade-up">
        <div class="card text-center" style="background-color: #333940; color: #e0e0e0; border-radius: 12px;">
          <div class="card-body">
            <img src="img/person4.jpg" class="rounded-circle mb-3" alt="Persona 4" width="80" height="80">
            <h5 class="card-title">Laura Méndez</h5>
            <p class="card-text">🌿 Ha adoptado un <b>Olivo</b></p>
          </div>
        </div>
      </div>

      <!-- Tarjeta de Persona 5 -->
      <div class="col-md-4 mb-4" data-aos="fade-up">
        <div class="card text-center" style="background-color: #333940; color: #e0e0e0; border-radius: 12px;">
          <div class="card-body">
            <img src="img/person5.jpg" class="rounded-circle mb-3" alt="Persona 5" width="80" height="80">
            <h5 class="card-title">Fernando Ruiz</h5>
            <p class="card-text">🌿 Ha adoptado un <b>Almendro</b></p>
          </div>
        </div>
      </div>

      <!-- Tarjeta de Persona 6 -->
      <div class="col-md-4 mb-4" data-aos="fade-up">
        <div class="card text-center" style="background-color: #333940; color: #e0e0e0; border-radius: 12px;">
          <div class="card-body">
            <img src="img/person6.jpg" class="rounded-circle mb-3" alt="Persona 6" width="80" height="80">
            <h5 class="card-title">Ana López</h5>
            <p class="card-text">🌿 Ha adoptado un <b>Cerezo</b></p>
          </div>
        </div>
      </div>
    </div>
  </div>
  
  <div id="formAdopcionModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.6); z-index: 9999; justify-content: center; align-items: center;">
    <div style="background: white; padding: 20px; border-radius: 12px; width: 350px; position: relative; display: flex; flex-direction: column;">
      <button id="cerrarFormularioBtn" style="position: absolute; top: 8px; right: 10px; background: none; border: none; font-size: 20px; cursor: pointer;">&times;</button>
      <h2 style="margin-bottom: 10px; color: #16a34a;">Formulario de Adopción</h2>
      <form id="formAdopcion">
        <input type="hidden" name="arbol_id" id="arbolIdInput">

        <label for="nombre">Nombre completo:</label>
        <input type="text" id="nombre" name="nombre" required style="width: 100%; padding: 6px; margin-bottom: 8px;">

        <label for="correo">Correo electrónico:</label>
        <input type="email" id="correo" name="correo" required style="width: 100%; padding: 6px; margin-bottom: 8px;">

        <label for="telefono">Teléfono:</label>
        <input type="text" id="telefono" name="telefono" style="width: 100%; padding: 6px; margin-bottom: 8px;">

        <label for="mensaje">¿Por qué deseas adoptar este árbol?</label>
        <textarea id="mensaje" name="mensaje" rows="3" style="width: 100%; padding: 6px; margin-bottom: 10px;"></textarea>

        <button type="submit" style="padding: 8px 12px; background-color: #16a34a; color: white; border: none; border-radius: 6px; cursor: pointer;">Enviar solicitud</button>
      </form>
    </div>
  </div>

  <div class="container py-vh-3 border-top" data-aos="fade" data-aos-delay="200" id="testimonials">
    <div class="row d-flex justify-content-center">
      <div class="col-12 col-lg-8 text-center">
        <h3 class="fs-2 fw-light">
          Ingresa tu<span class="fw-bold"> correo electrónico</span> para
          proporcionarte más información
        </h3>
      </div>
      <div class="col-12 col-lg-8 text-center">
        <div class="row">
          <div class="grouped-inputs border bg-light p-2">
            <div class="row">
              <div class="col">
                <form action="interesados.php" method="post" class="form-floating">
                  <input type="email" name="email" class="form-control p-3" id="email" placeholder="name@example.com" required />
                  <div class="col-auto">
                    <br />
                    <button type="submit" class="btn btn-dark py-3 px-5">
                      Enviar
                    </button>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <footer>
    <div class="container small border-top">
      <div class="row py-2 d-flex justify-content-between">
        <div class="text-secondary mt-3 col-12 col-lg-6 col-xl-3 border-end p-5">
          <strong class="h6 mb-3">SkyGreen<i class="bx bxs-tree-alt"></i></strong><br />

          <address class="text-secondary mt-3">
            "Cambiando la vida del mundo"
          </address>
          <ul class="nav flex-column"></ul>
        </div>
        <div class="text-secondary mt-3 col-12 col-lg-6 col-xl-3 border-end p-5">
          <h3 class="h6 mb-3">Facebook</h3>
          <address class="text-secondary mt-3">Siguenos en facebook:</address>
          <ul class="nav flex-column"></ul>
        </div>
        <div class="text-secondary mt-3 col-12 col-lg-6 col-xl-3 border-end p-5">
          <h3 class="h6 mb-3">Instagram</h3>
          <address class="text-secondary mt-3">
            Siguenos en instagram:
          </address>
          <ul class="nav flex-column"></ul>
        </div>
        <div class="text-secondary mt-3 col-12 col-lg-6 col-xl-3 p-5">
          <h3 class="h6 mb-3">Whatsapp</h3>
          <address class="text-secondary mt-3">Siguenos en WhatsApp:</address>
          <ul class="nav flex-column"></ul>
        </div>
      </div>
    </div>

    <div class="container text-center py-3 small">
      By
      <a href="https://github.com/Aless030" class="link-fancy" target="_blank">SkyGreen.com</a>
    </div>
  </footer>

  <script src="js/bootstrap.bundle.min.js"></script>
  <script src="js/aos.js"></script>
  <script>
    // ESTE CÓDIGO VA EN EL ARCHIVO index.php - REEMPLAZAR EL JAVASCRIPT DEL MAPA

mapboxgl.accessToken = 'pk.eyJ1IjoiYWxlc3NpcyIsImEiOiJjbGcxbHBtbHQwdDU5M2RubDFodjY3a2x0In0.NXe43GdM4PJBj7ow0Dnkpw';

const map = new mapboxgl.Map({
    container: 'map',
    style: 'mapbox://styles/mapbox/outdoors-v11',
    center: [-66.158468, -17.374908],
    zoom: 17,
    pitch: 50,
    bearing: -17.6
});

// Obtener los árboles desde PHP
const arboles = <?php echo json_encode($arboles); ?>;

// Función para obtener parámetros de la URL
function getURLParameter(name) {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get(name);
}

// Variable para almacenar todos los marcadores
let allMarkers = [];

map.on('load', function() {
    // Cargar todos los árboles
    arboles.forEach(arbol => {
        const coordinates = arbol.coordenadas.replace('POINT(', '').replace(')', '').split(' ');
        const lng = parseFloat(coordinates[0]);
        const lat = parseFloat(coordinates[1]);

        if (isNaN(lng) || isNaN(lat)) {
            console.error("Coordenadas inválidas:", arbol);
            return;
        }

        // Crear el elemento del marcador
        const el = document.createElement('div');
        el.className = 'tree-marker';
        el.style.backgroundImage = 'url("https://cdn2.iconfinder.com/data/icons/miscellaneous-iii-glyph-style/150/tree-512.png")';
        el.style.width = '30px';
        el.style.height = '30px';
        el.style.backgroundSize = 'cover';
        el.style.cursor = 'pointer';

        // Asignar borde según estado del árbol
        switch (arbol.estado.toLowerCase()) {
            
            case 'exótico':
                el.style.border = '3px solid yellow';
                break;
            case 'nativo':
                el.style.border = '3px solid green';
                break;
            default:
                el.style.border = '3px solid gray';
        }

        // Crear contenido del popup
        const popupContent = `
        <div style="max-width: 200px; padding: 4px; font-size: 12px; position: relative;">
            <button class="close-popup" style="
                position: absolute;
                top: 3px;
                right: 5px;
                background: #ff4d4f;
                color: #fff;
                border: none;
                font-size: 12px;
                width: 16px;
                height: 16px;
                line-height: 16px;
                text-align: center;
                cursor: pointer;
                border-radius: 50%;
                transition: background 0.2s;
            ">&times;</button>
            
            <h3 style="font-size: 14px; margin-bottom: 5px;">${arbol.especie}</h3>

            <a href="360.html">
                <img src="${arbol.fotoUrl}" alt="Foto del árbol" style="
                width: 80%; 
                height: 90px; 
                border-radius: 5px; 
                margin-bottom: 5px;
                "/>
            </a>
            
            <p style="margin: 3px 0; font-size: 12px; line-height: 1.2;">
                <strong>Edad:</strong> ${arbol.edad} años
            </p>
            
            <p style="margin: 3px 0; font-size: 12px; line-height: 1.2;">
                <strong>Altura:</strong> ${arbol.altura} m
            </p>
            
            <p style="margin: 3px 0; font-size: 12px; line-height: 1.2;">
                <strong>Diámetro:</strong> ${arbol.diametroTronco} cm
            </p>
            
            <p style="margin: 3px 0; font-size: 12px; line-height: 1.2;">
                <strong>Cuidados:</strong> ${arbol.cuidados}
            </p>
            
            <p style="margin: 3px 0; font-size: 12px; line-height: 1.2;">
                <strong>Tipo:</strong> ${arbol.estado}
            </p>
            ${arbol.pdfUrl ? `
  <div class="popup-info pdf-container">
    <a href="${arbol.pdfUrl}" target="_blank" class="pdf-button">
      <i class="fas fa-file-pdf"></i> Ver PDF
    </a>
  </div>
` : ''}

            
            <img src="${arbol.qrUrl}" alt="QR" style="
                width: 60px; 
                height: 60px; 
                border-radius: 5px;
            "/>
            <br>
            
        </div>
        `;

        // Crear el popup
        const popup = new mapboxgl.Popup({
            offset: [20, -70],
            closeButton: false,
            closeOnClick: true
        }).setHTML(popupContent);

        // Crear el marcador
        const marker = new mapboxgl.Marker(el)
            .setLngLat([lng, lat])
            .setPopup(popup)
            .addTo(map);

        // Almacenar el marcador con su ID para referencia
        allMarkers.push({
            id: arbol.id,
            marker: marker,
            popup: popup,
            coordinates: [lng, lat],
            arbol: arbol
        });

        // Event listener para click en marcador
        el.addEventListener('click', () => {
            openTreePopup(arbol.id);
        });
    });

    // **FUNCIONALIDAD QR: Verificar si hay un tree_id en la URL**
    const treeId = getURLParameter('tree_id');
    if (treeId) {
        // Esperar un momento para que el mapa cargue completamente
        setTimeout(() => {
            openTreePopup(parseInt(treeId));
        }, 1000);
    }
});

// Función para abrir el popup de un árbol específico
function openTreePopup(treeId) {
    const targetMarker = allMarkers.find(item => item.id == treeId);
    
    if (targetMarker) {
        // Volar al árbol específico
        map.flyTo({
            center: targetMarker.coordinates,
            zoom: 18,
            essential: true
        });

        // Abrir el popup después de que termine la animación
        setTimeout(() => {
            targetMarker.popup.addTo(map);

            // Configurar el popup visualmente
            const popupElement = document.querySelector('.mapboxgl-popup');
            if (popupElement) {
                popupElement.style.opacity = '1';
                popupElement.style.transform = 'translateX(20px) translateY(-10px) scale(1.1)';
                popupElement.style.transition = 'transform 0.2s ease, opacity 0.2s ease';
            }

            // Agregar funcionalidad al botón de cerrar
            setTimeout(() => {
                const closeBtn = document.querySelector(".close-popup");
                if (closeBtn) {
                    closeBtn.addEventListener("click", () => {
                        targetMarker.popup.remove();
                    });
                }

                // Agregar funcionalidad al botón de adopción
                const adoptBtn = document.querySelector(".abrir-form-adopcion");
                if (adoptBtn) {
                    adoptBtn.addEventListener("click", function() {
                        // Aquí puedes agregar la lógica para abrir el formulario de adopción
                        console.log("Adoptar árbol ID:", this.getAttribute('data-id'));
                        console.log("Especie:", this.getAttribute('data-especie'));
                    });
                }
            }, 100);

        }, 1000); // Esperar a que termine la animación del flyTo

        
        // Esto remueve el parámetro tree_id de la URL sin recargar la página
        if (window.history && window.history.replaceState) {
            const url = new URL(window.location.href);
            url.searchParams.delete('tree_id');
            window.history.replaceState({}, document.title, url.pathname + url.hash);
        }
    } else {
        console.error("Árbol no encontrado con ID:", treeId);
        // Mostrar mensaje de error al usuario
        alert("Árbol no encontrado. El código QR podría estar dañado o el árbol ya no existe.");
    }
}

// Funcionalidad adicional para búsqueda directa por ID (opcional)
function buscarArbolPorId(id) {
    openTreePopup(parseInt(id));
}

   
  </script>
<script>
    AOS.init({
      duration: 800, // values from 0 to 3000, with step 50ms
    });

    const formCount = 3;
    let currentForm = 1;

    // EventListeners para el botón "Siguiente" y "Anterior"
    document.getElementById("next").addEventListener("click", function() {
      if (validarFormularioActual()) {
        if (currentForm < formCount) {
          document.getElementById("slide" + currentForm).style.display =
            "none";
          currentForm++;
          document.getElementById("slide" + currentForm).style.display =
            "block";

          document.getElementById("prev").style.display = "block";
          if (currentForm === formCount) {
            document.getElementById("next").style.display = "none";
            document.querySelector(
              'form button[type="submit"]'
            ).style.display = "block";
          }
        }
      }
    });

    document.getElementById("prev").addEventListener("click", function() {
      if (currentForm > 1) {
        document.getElementById("slide" + currentForm).style.display = "none";
        currentForm--;
        document.getElementById("slide" + currentForm).style.display =
          "block";

        document.getElementById("next").style.display = "block";
        if (currentForm === 1) {
          document.getElementById("prev").style.display = "none";
        }
      }
    });
    
  </script>
  <script src="https://unpkg.com/boxicons@2.1.4/dist/boxicons.js"></script>
</body>
</html>
