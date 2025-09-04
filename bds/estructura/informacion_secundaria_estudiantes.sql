-- Estructura de tablas para información secundaria de estudiantes
-- Fecha: 2025-01-27

-- Tabla: estudiante_abandono
CREATE TABLE `estudiante_abandono` (
  `id_abandono` int(11) NOT NULL AUTO_INCREMENT,
  `id_estudiante` int(11) NOT NULL,
  `fecha_abandono` date DEFAULT NULL,
  `motivo_abandono` text DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `fecha_regreso` date DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_abandono`),
  KEY `fk_abandono_estudiante` (`id_estudiante`),
  CONSTRAINT `fk_abandono_estudiante` FOREIGN KEY (`id_estudiante`) REFERENCES `estudiantes` (`id_estudiante`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla: estudiante_actividad_laboral
CREATE TABLE `estudiante_actividad_laboral` (
  `id_actividad_laboral` int(11) NOT NULL AUTO_INCREMENT,
  `id_estudiante` int(11) NOT NULL,
  `trabaja` enum('Si','No') DEFAULT 'No',
  `lugar_trabajo` varchar(255) DEFAULT NULL,
  `cargo` varchar(255) DEFAULT NULL,
  `horario_trabajo` varchar(100) DEFAULT NULL,
  `ingreso_mensual` decimal(10,2) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_actividad_laboral`),
  KEY `fk_actividad_laboral_estudiante` (`id_estudiante`),
  CONSTRAINT `fk_actividad_laboral_estudiante` FOREIGN KEY (`id_estudiante`) REFERENCES `estudiantes` (`id_estudiante`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla: estudiante_dificultades
CREATE TABLE `estudiante_dificultades` (
  `id_dificultad` int(11) NOT NULL AUTO_INCREMENT,
  `id_estudiante` int(11) NOT NULL,
  `tipo_dificultad` enum('Aprendizaje','Conducta','Social','Fisica','Emocional','Otro') DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `diagnostico` text DEFAULT NULL,
  `tratamiento` text DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_dificultad`),
  KEY `fk_dificultades_estudiante` (`id_estudiante`),
  CONSTRAINT `fk_dificultades_estudiante` FOREIGN KEY (`id_estudiante`) REFERENCES `estudiantes` (`id_estudiante`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla: estudiante_direccion
CREATE TABLE `estudiante_direccion` (
  `id_direccion` int(11) NOT NULL AUTO_INCREMENT,
  `id_estudiante` int(11) NOT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `zona` varchar(100) DEFAULT NULL,
  `telefono_casa` varchar(20) DEFAULT NULL,
  `celular` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `referencia` text DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_direccion`),
  KEY `fk_direccion_estudiante` (`id_estudiante`),
  CONSTRAINT `fk_direccion_estudiante` FOREIGN KEY (`id_estudiante`) REFERENCES `estudiantes` (`id_estudiante`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla: estudiante_idioma_cultura
CREATE TABLE `estudiante_idioma_cultura` (
  `id_idioma_cultura` int(11) NOT NULL AUTO_INCREMENT,
  `id_estudiante` int(11) NOT NULL,
  `idioma_materno` varchar(100) DEFAULT NULL,
  `idiomas_adicionales` varchar(255) DEFAULT NULL,
  `nivel_espanol` enum('Nativo','Avanzado','Intermedio','Basico') DEFAULT NULL,
  `pueblo_indigena` varchar(100) DEFAULT NULL,
  `tradiciones` text DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_idioma_cultura`),
  KEY `fk_idioma_cultura_estudiante` (`id_estudiante`),
  CONSTRAINT `fk_idioma_cultura_estudiante` FOREIGN KEY (`id_estudiante`) REFERENCES `estudiantes` (`id_estudiante`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla: estudiante_salud
CREATE TABLE `estudiante_salud` (
  `id_salud` int(11) NOT NULL AUTO_INCREMENT,
  `id_estudiante` int(11) NOT NULL,
  `tipo_sangre` enum('A+','A-','B+','B-','AB+','AB-','O+','O-') DEFAULT NULL,
  `alergias` text DEFAULT NULL,
  `medicamentos` text DEFAULT NULL,
  `enfermedades_cronicas` text DEFAULT NULL,
  `discapacidad` enum('Si','No') DEFAULT 'No',
  `tipo_discapacidad` varchar(255) DEFAULT NULL,
  `observaciones_medicas` text DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_salud`),
  KEY `fk_salud_estudiante` (`id_estudiante`),
  CONSTRAINT `fk_salud_estudiante` FOREIGN KEY (`id_estudiante`) REFERENCES `estudiantes` (`id_estudiante`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla: estudiante_servicios
CREATE TABLE `estudiante_servicios` (
  `id_servicio` int(11) NOT NULL AUTO_INCREMENT,
  `id_estudiante` int(11) NOT NULL,
  `comedor` enum('Si','No') DEFAULT 'No',
  `transporte_escolar` enum('Si','No') DEFAULT 'No',
  `biblioteca` enum('Si','No') DEFAULT 'No',
  `laboratorio` enum('Si','No') DEFAULT 'No',
  `deportes` enum('Si','No') DEFAULT 'No',
  `arte_cultura` enum('Si','No') DEFAULT 'No',
  `observaciones` text DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_servicio`),
  KEY `fk_servicios_estudiante` (`id_estudiante`),
  CONSTRAINT `fk_servicios_estudiante` FOREIGN KEY (`id_estudiante`) REFERENCES `estudiantes` (`id_estudiante`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla: estudiante_transporte
CREATE TABLE `estudiante_transporte` (
  `id_transporte` int(11) NOT NULL AUTO_INCREMENT,
  `id_estudiante` int(11) NOT NULL,
  `tipo_transporte` enum('Caminando','Bicicleta','Motocicleta','Auto familiar','Transporte publico','Transporte escolar','Otro') DEFAULT NULL,
  `tiempo_viaje` varchar(50) DEFAULT NULL,
  `distancia` varchar(50) DEFAULT NULL,
  `costo_mensual` decimal(10,2) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_transporte`),
  KEY `fk_transporte_estudiante` (`id_estudiante`),
  CONSTRAINT `fk_transporte_estudiante` FOREIGN KEY (`id_estudiante`) REFERENCES `estudiantes` (`id_estudiante`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
