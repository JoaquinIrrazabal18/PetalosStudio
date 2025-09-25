-- Base de datos corregida para Draftosaurus
-- Basada en el modelo entidad-relación del documento

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- Base de datos: `draftosaurus`
CREATE DATABASE IF NOT EXISTS `draftosaurus` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `draftosaurus`;

-- Estructura corregida según el EsRe

-- Tabla Usuario
CREATE TABLE `usuario` (
  `id_usuario` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_usuario` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL UNIQUE,
  `contrasena_cifrada` varchar(255) NOT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `fecha_registro` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ultimo_login` datetime DEFAULT NULL,
  `rol` enum('Jugador','Administrador') NOT NULL DEFAULT 'Jugador',
  PRIMARY KEY (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla Campeonato
CREATE TABLE `campeonato` (
  `id_campeonato` int(11) NOT NULL AUTO_INCREMENT,
  `fecha_inicio` datetime NOT NULL,
  `estado_campeonato` enum('activo','finalizado','cancelado') NOT NULL DEFAULT 'activo',
  PRIMARY KEY (`id_campeonato`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insertar campeonato por defecto
INSERT INTO `campeonato` (`fecha_inicio`, `estado_campeonato`) VALUES (NOW(), 'activo');

-- Tabla Tablero
CREATE TABLE `tablero` (
  `id_tablero` int(11) NOT NULL AUTO_INCREMENT,
  `tipo_tablero` enum('Verano','Invierno') NOT NULL,
  PRIMARY KEY (`id_tablero`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insertar tableros
INSERT INTO `tablero` (`tipo_tablero`) VALUES ('Verano'), ('Invierno');

-- Tabla Partida
CREATE TABLE `partida` (
  `id_partida` int(11) NOT NULL AUTO_INCREMENT,
  `id_campeonato` int(11) NOT NULL DEFAULT 1,
  `fecha_creacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `estado_partida` enum('creada','en_curso','pausada','finalizada','cancelada') NOT NULL DEFAULT 'creada',
  `num_participantes` int(11) NOT NULL,
  `dinos_iniciales_bolsa` int(11) NOT NULL DEFAULT 60,
  `lado_tablero_usado` enum('Verano','Invierno') NOT NULL,
  `ronda_actual` int(11) NOT NULL DEFAULT 1,
  `turno_actual` int(11) NOT NULL DEFAULT 1,
  `dinos_restantes_bolsa` int(11) NOT NULL,
  `fecha_hora_fin` datetime DEFAULT NULL,
  `config_json` longtext DEFAULT NULL,
  `iniciada` TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id_partida`),
  KEY `id_campeonato` (`id_campeonato`),
  FOREIGN KEY (`id_campeonato`) REFERENCES `campeonato` (`id_campeonato`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla Participacion_p 
CREATE TABLE `participacion_p` (
  `id_participacion` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) NOT NULL,
  `id_partida` int(11) NOT NULL,
  `id_tablero` int(11) NOT NULL,
  `gano_partida` tinyint(1) NOT NULL DEFAULT 0,
  `fecha_hora_inicio` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `estado_participacion` enum('Activa','Completada','Abandono') NOT NULL DEFAULT 'Activa',
  `puntuacion_final` int(11) NOT NULL DEFAULT 0,
  `posicion_turno` int(11) NOT NULL DEFAULT 1,
  `listo` TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id_participacion`),
  KEY `id_usuario` (`id_usuario`),
  KEY `id_partida` (`id_partida`),
  KEY `id_tablero` (`id_tablero`),
  FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE,
  FOREIGN KEY (`id_partida`) REFERENCES `partida` (`id_partida`) ON DELETE CASCADE,
  FOREIGN KEY (`id_tablero`) REFERENCES `tablero` (`id_tablero`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla Ronda
CREATE TABLE `ronda` (
  `id_ronda` int(11) NOT NULL AUTO_INCREMENT,
  `id_partida` int(11) NOT NULL,
  `numero_ronda` int(11) NOT NULL,
  `fecha_hora_inicio` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_hora_fin` datetime DEFAULT NULL,
  PRIMARY KEY (`id_ronda`),
  KEY `id_partida` (`id_partida`),
  FOREIGN KEY (`id_partida`) REFERENCES `partida` (`id_partida`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla Dino_ficha
CREATE TABLE `dino_ficha` (
  `id_dino_ficha` int(11) NOT NULL AUTO_INCREMENT,
  `id_partida` int(11) NOT NULL,
  `especie` enum('Tiranosaurio Rex','Triceratops','Brachiosaurus','Stegosaurus','Pterodáctilo','Plesiosaurio') NOT NULL,
  `ubicacion_actual` varchar(50) DEFAULT 'bolsa',
  PRIMARY KEY (`id_dino_ficha`),
  KEY `id_partida` (`id_partida`),
  FOREIGN KEY (`id_partida`) REFERENCES `partida` (`id_partida`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla Restriccion
CREATE TABLE `restriccion` (
  `id_restriccion` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_restriccion` varchar(50) NOT NULL UNIQUE,
  `descripcion` text,
  PRIMARY KEY (`id_restriccion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insertar restricciones del dado
INSERT INTO `restriccion` (`nombre_restriccion`, `descripcion`) VALUES
('El Bosque', 'Los jugadores no-activos solo pueden colocar un dinosaurio en un recinto del bosque'),
('La Llanura', 'Los jugadores no-activos solo pueden colocar un dinosaurio en un recinto de llanura'),
('Los Baños', 'Los jugadores no-activos solo pueden colocar un dinosaurio en el recinto que estén a la derecha del río'),
('La Cafetería', 'Los jugadores no-activos solo pueden colocar un dinosaurio en un recinto a la izquierda del río'),
('Recinto Vacío', 'Los jugadores no-activos solo pueden colocar un dinosaurio en un recinto vacío'),
('¡Cuidado con el T-Rex!', 'Los jugadores no-activos no pueden colocar un dinosaurio en un recinto que contenga un T-Rex');

-- Tabla Dado_turno
CREATE TABLE `dado_turno` (
  `id_dado_turno` int(11) NOT NULL AUTO_INCREMENT,
  `id_ronda` int(11) NOT NULL,
  `id_participacion` int(11) NOT NULL,
  `id_restriccion` int(11) NOT NULL,
  `numero_turno_en_ronda` int(11) NOT NULL,
  `resultado_dado` enum('El Bosque','La Llanura','Los Baños','La Cafetería','Recinto Vacío','¡Cuidado con el T-Rex!') NOT NULL,
  `fecha_hora_lanzamiento` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_dado_turno`),
  KEY `id_ronda` (`id_ronda`),
  KEY `id_participacion` (`id_participacion`),
  KEY `id_restriccion` (`id_restriccion`),
  FOREIGN KEY (`id_ronda`) REFERENCES `ronda` (`id_ronda`) ON DELETE CASCADE,
  FOREIGN KEY (`id_participacion`) REFERENCES `participacion_p` (`id_participacion`) ON DELETE CASCADE,
  FOREIGN KEY (`id_restriccion`) REFERENCES `restriccion` (`id_restriccion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla Recinto
CREATE TABLE `recinto` (
  `id_recinto` int(11) NOT NULL AUTO_INCREMENT,
  `id_tablero` int(11) NOT NULL,
  `nombre_recinto` enum(
    'El Bosque de la Semejanza','El Prado de la Diferencia','La Pradera del Amor',
    'El Trío Frondoso','El Rey de la Selva','La Isla Solitaria',
    'El Bosque Ordenado','El Puente de los Enamorados','La Pirámide',
    'El Puesto de Observación','Zona de Cuarentena','Rio'
  ) NOT NULL,
  `capacidad_maxima` int(11) NOT NULL DEFAULT 6,
  `area` enum('Bosque','Llanura','Rio') DEFAULT NULL,
  `lado` enum('Baños','Cafetería') DEFAULT NULL,
  PRIMARY KEY (`id_recinto`),
  KEY `id_tablero` (`id_tablero`),
  FOREIGN KEY (`id_tablero`) REFERENCES `tablero` (`id_tablero`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insertar recintos para tablero Verano (id_tablero = 1)
INSERT INTO `recinto` (`id_tablero`, `nombre_recinto`, `capacidad_maxima`, `area`, `lado`) VALUES
(1, 'El Bosque de la Semejanza', 6, 'Bosque', 'Cafetería'),
(1, 'El Prado de la Diferencia', 6, 'Llanura', 'Baños'),
(1, 'La Pradera del Amor', 6, 'Llanura', 'Cafetería'),
(1, 'El Trío Frondoso', 3, 'Bosque', 'Cafetería'),
(1, 'El Rey de la Selva', 1, 'Bosque', 'Baños'),
(1, 'La Isla Solitaria', 1, 'Llanura', 'Baños'),
(1, 'Rio', 12, 'Rio', NULL);

-- Insertar recintos para tablero Invierno (id_tablero = 2)
INSERT INTO `recinto` (`id_tablero`, `nombre_recinto`, `capacidad_maxima`, `area`, `lado`) VALUES
(2, 'El Bosque Ordenado', 6, 'Bosque', 'Cafetería'),
(2, 'El Puente de los Enamorados', 6, 'Bosque', 'Cafetería'),
(2, 'La Pirámide', 6, 'Llanura', 'Baños'),
(2, 'El Puesto de Observación', 1, 'Bosque', 'Baños'),
(2, 'Zona de Cuarentena', 1, 'Llanura', 'Cafetería'),
(2, 'Rio', 12, 'Rio', NULL);

-- Tabla Colocado
CREATE TABLE `colocado` (
  `id_colocado` int(11) NOT NULL AUTO_INCREMENT,
  `id_dino_ficha` int(11) NOT NULL,
  `id_participacion` int(11) NOT NULL,
  `id_recinto` int(11) NOT NULL,
  `id_dado_turno` int(11) DEFAULT NULL,
  `posicion_dentro_recinto` int(11) DEFAULT 0,
  `fecha_hora_colocacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_colocado`),
  KEY `id_dino_ficha` (`id_dino_ficha`),
  KEY `id_participacion` (`id_participacion`),
  KEY `id_recinto` (`id_recinto`),
  KEY `id_dado_turno` (`id_dado_turno`),
  FOREIGN KEY (`id_dino_ficha`) REFERENCES `dino_ficha` (`id_dino_ficha`) ON DELETE CASCADE,
  FOREIGN KEY (`id_participacion`) REFERENCES `participacion_p` (`id_participacion`) ON DELETE CASCADE,
  FOREIGN KEY (`id_recinto`) REFERENCES `recinto` (`id_recinto`) ON DELETE CASCADE,
  FOREIGN KEY (`id_dado_turno`) REFERENCES `dado_turno` (`id_dado_turno`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla Dino_en_rio_descarte
CREATE TABLE `dino_en_rio_descarte` (
  `id_dino_en_rio_descarte` int(11) NOT NULL AUTO_INCREMENT,
  `id_dino_ficha` int(11) NOT NULL,
  `id_participacion` int(11) NOT NULL,
  `id_dado_turno` int(11) NOT NULL,
  `fecha_hora_descarte` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_dino_en_rio_descarte`),
  KEY `id_dino_ficha` (`id_dino_ficha`),
  KEY `id_participacion` (`id_participacion`),
  KEY `id_dado_turno` (`id_dado_turno`),
  FOREIGN KEY (`id_dino_ficha`) REFERENCES `dino_ficha` (`id_dino_ficha`) ON DELETE CASCADE,
  FOREIGN KEY (`id_participacion`) REFERENCES `participacion_p` (`id_participacion`) ON DELETE CASCADE,
  FOREIGN KEY (`id_dado_turno`) REFERENCES `dado_turno` (`id_dado_turno`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla auxiliar para manos de jugadores (JSON storage)
CREATE TABLE `mano_dinosaurios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_partida` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `ronda` int(11) NOT NULL,
  `mano_json` longtext NOT NULL,
  `creado_en` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `id_partida` (`id_partida`),
  KEY `id_usuario` (`id_usuario`),
  FOREIGN KEY (`id_partida`) REFERENCES `partida` (`id_partida`) ON DELETE CASCADE,
  FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla auxiliar para posiciones en tablero (JSON storage)
CREATE TABLE `tablero_posiciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_partida` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `recinto` varchar(128) NOT NULL,
  `posicion` int(11) DEFAULT NULL,
  `especie` varchar(128) DEFAULT NULL,
  `meta_json` longtext DEFAULT NULL,
  `creado_en` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `id_partida` (`id_partida`),
  KEY `id_usuario` (`id_usuario`),
  FOREIGN KEY (`id_partida`) REFERENCES `partida` (`id_partida`) ON DELETE CASCADE,
  FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de historial/log de acciones
CREATE TABLE `historial` (
  `id_historial` int(11) NOT NULL AUTO_INCREMENT,
  `id_partida` int(11) NOT NULL,
  `id_usuario` int(11) DEFAULT NULL,
  `accion` varchar(255) NOT NULL,
  `dado` varchar(50) DEFAULT NULL,
  `meta_json` longtext DEFAULT NULL,
  `fecha` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_historial`),
  KEY `id_partida` (`id_partida`),
  KEY `id_usuario` (`id_usuario`),
  FOREIGN KEY (`id_partida`) REFERENCES `partida` (`id_partida`) ON DELETE CASCADE,
  FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Crear usuario de ejemplo para testing
INSERT INTO `usuario` (`nombre_usuario`, `email`, `contrasena_cifrada`, `fecha_registro`, `rol`) 
VALUES ('Admin', 'admin@draftosaurus.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NOW(), 'Administrador');

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;