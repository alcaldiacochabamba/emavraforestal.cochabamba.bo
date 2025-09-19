-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1:3308
-- Tiempo de generación: 02-06-2024 a las 14:23:26
-- Versión del servidor: 8.0.31
-- Versión de PHP: 8.0.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `reforest`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `email`) VALUES
(1, 'alevia030@gmail.com'),
(2, 'jose@gmail.com');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios_registrados`
--

DROP TABLE IF EXISTS `usuarios_registrados`;
CREATE TABLE IF NOT EXISTS `usuarios_registrados` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  `apellido_paterno` varchar(50) NOT NULL,
  `apellido_materno` varchar(50) DEFAULT NULL,
  `fecha_nacimiento` date NOT NULL,
  `genero` enum('Masculino','Femenino','Otro') NOT NULL,
  `telefono` varchar(15) NOT NULL,
  `direccion` varchar(255) NOT NULL,
  `correo` varchar(50) NOT NULL,
  `numero_identificacion` varchar(20) NOT NULL,
  `foto_perfil` varchar(255) DEFAULT NULL,
  `documentos_identificacion` varchar(255) DEFAULT NULL,
  `usuario` varchar(50) NOT NULL,
  `contrasena` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `usuarios_registrados`
--

INSERT INTO `usuarios_registrados` (`id`, `nombre`, `apellido_paterno`, `apellido_materno`, `fecha_nacimiento`, `genero`, `telefono`, `direccion`, `correo`, `numero_identificacion`, `foto_perfil`, `documentos_identificacion`, `usuario`, `contrasena`) VALUES
(1, 'Alejandro Valdivia Montalvo', 'Valdivia', 'Montalvo', '2024-05-24', 'Masculino', '72777632', 'Av. América ', 'asd@asd.com', '24324234', '05 H4EC 2P Page 152 STARTUP.png', '08 H4EC 1P Page 153 STARTUP.png', 'alevia', '$2y$10$SCW9DLfaHMq8EbtiTNUun.i6.zPKkBHpefTk5DkNAouagGoFWvA3e'),
(2, 'Alejandro', 'Valdivia', 'Montalvo', '2024-05-24', 'Masculino', '72777632', 'Av Melchor peres', 'ale@gmail.com', '21312313', '', '', 'Aleviamusic', '$2y$10$6madVjsGid9EIQ07XtAg..4qrdtm2Di4oFh7Mc5Xm/aQo7BE58OMK'),
(3, 'Juan', 'Valdivia', 'asd', '2024-05-27', 'Masculino', '72777632', 'Av. América ', 'asd@asd.com', '24324234', '', '', 'ale', '$2y$10$uDsuZL4qSbLjQf7b0DdTfuVoNEZs6yU15FaURX.HVMETcIzPXoGKi');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
