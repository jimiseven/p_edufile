-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 17-11-2025 a las 21:28:29
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `colegiov4`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `anuncios`
--

CREATE TABLE `anuncios` (
  `id` int(11) NOT NULL,
  `mensaje` text NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  `creado_por` int(11) DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `bimestres_activos`
--

CREATE TABLE `bimestres_activos` (
  `id` int(11) NOT NULL,
  `numero_bimestre` int(11) NOT NULL,
  `esta_activo` tinyint(1) DEFAULT 0,
  `fecha_inicio` date DEFAULT NULL,
  `fecha_fin` date DEFAULT NULL,
  `fecha_modificacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `calificaciones`
--

CREATE TABLE `calificaciones` (
  `id_calificacion` int(11) NOT NULL,
  `id_estudiante` int(11) NOT NULL COMMENT 'FK a estudiantes',
  `id_materia` int(11) NOT NULL COMMENT 'FK a materias',
  `bimestre` int(11) NOT NULL COMMENT 'Número del bimestre: 1, 2, 3, 4',
  `calificacion` float NOT NULL DEFAULT 0,
  `comentario` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `configuracion_sistema`
--

CREATE TABLE `configuracion_sistema` (
  `id` int(11) NOT NULL,
  `cantidad_bimestres` int(11) NOT NULL DEFAULT 3,
  `bimestre_actual` int(11) NOT NULL DEFAULT 1,
  `anio_escolar` varchar(9) NOT NULL,
  `fecha_modificacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cursos`
--

CREATE TABLE `cursos` (
  `id_curso` int(11) NOT NULL,
  `nivel` varchar(20) NOT NULL COMMENT 'Ej: Kinder, Primaria, Secundaria',
  `curso` int(11) NOT NULL COMMENT 'Número del curso, ej: 1, 2, 3',
  `paralelo` varchar(5) NOT NULL COMMENT 'Ej: A, B, C'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cursos_materias`
--

CREATE TABLE `cursos_materias` (
  `id_curso_materia` int(11) NOT NULL,
  `id_curso` int(11) NOT NULL COMMENT 'FK a cursos',
  `id_materia` int(11) NOT NULL COMMENT 'FK a materias'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estudiantes`
--

CREATE TABLE `estudiantes` (
  `id_estudiante` int(11) NOT NULL,
  `nombres` varchar(255) NOT NULL,
  `apellido_paterno` varchar(255) DEFAULT NULL,
  `apellido_materno` varchar(255) DEFAULT NULL,
  `genero` enum('Masculino','Femenino') DEFAULT NULL,
  `rude` varchar(20) NOT NULL COMMENT 'Registro Único de Estudiante',
  `carnet_identidad` varchar(20) DEFAULT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `pais` enum('Bolivia','Chile','Argentina') DEFAULT NULL,
  `provincia_departamento` varchar(100) DEFAULT NULL,
  `id_curso` int(11) DEFAULT NULL COMMENT 'FK al curso en el que está matriculado',
  `id_responsable` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estudiante_abandono`
--

CREATE TABLE `estudiante_abandono` (
  `id` int(11) NOT NULL,
  `id_estudiante` int(11) NOT NULL,
  `abandono` tinyint(1) DEFAULT 0,
  `motivo` enum('trabajo','falta_dinero','otro') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estudiante_actividad_laboral`
--

CREATE TABLE `estudiante_actividad_laboral` (
  `id` int(11) NOT NULL,
  `id_estudiante` int(11) NOT NULL,
  `trabajo` tinyint(1) DEFAULT 0,
  `meses_trabajo` set('enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre') DEFAULT NULL,
  `actividad` varchar(100) DEFAULT NULL,
  `turno_manana` tinyint(1) DEFAULT 0,
  `turno_tarde` tinyint(1) DEFAULT 0,
  `turno_noche` tinyint(1) DEFAULT 0,
  `frecuencia` enum('todos_dias','dias_habiles','fin_de_semana','esporadico','dias_festivos','vacaciones') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estudiante_dificultades`
--

CREATE TABLE `estudiante_dificultades` (
  `id` int(11) NOT NULL,
  `id_estudiante` int(11) NOT NULL,
  `tiene_dificultad` tinyint(1) DEFAULT 0,
  `auditiva` enum('ninguna','leve','grave','muy_grave','multiple') DEFAULT 'ninguna',
  `visual` enum('ninguna','leve','grave','muy_grave','multiple') DEFAULT 'ninguna',
  `intelectual` enum('ninguna','leve','grave','muy_grave','multiple') DEFAULT 'ninguna',
  `fisico_motora` enum('ninguna','leve','grave','muy_grave','multiple') DEFAULT 'ninguna',
  `psiquica_mental` enum('ninguna','leve','grave','muy_grave','multiple') DEFAULT 'ninguna',
  `autista` enum('ninguna','leve','grave','muy_grave','multiple') DEFAULT 'ninguna'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estudiante_direccion`
--

CREATE TABLE `estudiante_direccion` (
  `id` int(11) NOT NULL,
  `id_estudiante` int(11) NOT NULL,
  `departamento` varchar(50) DEFAULT NULL,
  `provincia` varchar(50) DEFAULT NULL,
  `municipio` varchar(50) DEFAULT NULL,
  `localidad` varchar(100) DEFAULT NULL,
  `comunidad` varchar(100) DEFAULT NULL,
  `zona` varchar(100) DEFAULT NULL,
  `numero_vivienda` varchar(20) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `celular` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estudiante_idioma_cultura`
--

CREATE TABLE `estudiante_idioma_cultura` (
  `id` int(11) NOT NULL,
  `id_estudiante` int(11) NOT NULL,
  `idioma` varchar(50) DEFAULT NULL,
  `cultura` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estudiante_salud`
--

CREATE TABLE `estudiante_salud` (
  `id` int(11) NOT NULL,
  `id_estudiante` int(11) NOT NULL,
  `tiene_seguro` tinyint(1) NOT NULL DEFAULT 0,
  `acceso_posta` tinyint(1) NOT NULL DEFAULT 0,
  `acceso_centro_salud` tinyint(1) NOT NULL DEFAULT 0,
  `acceso_hospital` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estudiante_servicios`
--

CREATE TABLE `estudiante_servicios` (
  `id` int(11) NOT NULL,
  `id_estudiante` int(11) NOT NULL,
  `agua_caneria` tinyint(1) DEFAULT 0,
  `bano` tinyint(1) DEFAULT 0,
  `alcantarillado` tinyint(1) DEFAULT 0,
  `internet` tinyint(1) DEFAULT 0,
  `energia` tinyint(1) DEFAULT 0,
  `recojo_basura` tinyint(1) DEFAULT 0,
  `tipo_vivienda` enum('alquilada','propia','cedida','anticretico') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estudiante_transporte`
--

CREATE TABLE `estudiante_transporte` (
  `id` int(11) NOT NULL,
  `id_estudiante` int(11) NOT NULL,
  `medio` enum('a_pie','vehiculo','fluvial','otro') DEFAULT NULL,
  `tiempo_llegada` enum('menos_media_hora','mas_media_hora') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `materias`
--

CREATE TABLE `materias` (
  `id_materia` int(11) NOT NULL,
  `nombre_materia` varchar(255) NOT NULL COMMENT 'Nombre de la materia, ej: Matemáticas, Física',
  `es_submateria` tinyint(1) DEFAULT 0,
  `materia_padre_id` int(11) DEFAULT NULL,
  `es_extra` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `personal`
--

CREATE TABLE `personal` (
  `id_personal` int(11) NOT NULL,
  `nombres` varchar(255) NOT NULL,
  `apellidos` varchar(255) NOT NULL,
  `celular` varchar(20) DEFAULT NULL COMMENT 'Ej: Número de contacto del usuario',
  `carnet_identidad` varchar(20) NOT NULL,
  `id_rol` int(11) NOT NULL COMMENT 'FK a roles',
  `password` varchar(255) NOT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1=habilitado, 0=inhabilitado'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `profesores_materias_cursos`
--

CREATE TABLE `profesores_materias_cursos` (
  `id_profesor_materia_curso` int(11) NOT NULL,
  `id_personal` int(11) NOT NULL COMMENT 'FK a personal (profesor)',
  `id_curso_materia` int(11) NOT NULL COMMENT 'FK a cursos_materias',
  `estado` enum('FALTA','CARGADO') NOT NULL DEFAULT 'FALTA'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reportes_guardados`
--

CREATE TABLE `reportes_guardados` (
  `id_reporte` int(11) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `tipo_base` varchar(50) NOT NULL,
  `id_personal` int(11) NOT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reportes_guardados_columnas`
--

CREATE TABLE `reportes_guardados_columnas` (
  `id_columna` int(11) NOT NULL,
  `id_reporte` int(11) NOT NULL,
  `campo` varchar(100) NOT NULL,
  `alias_mostrar` varchar(100) DEFAULT NULL,
  `orden` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reportes_guardados_filtros`
--

CREATE TABLE `reportes_guardados_filtros` (
  `id_filtro` int(11) NOT NULL,
  `id_reporte` int(11) NOT NULL,
  `campo` varchar(100) NOT NULL,
  `operador` enum('=','<>','>','<','>=','<=','entre','contiene','in') NOT NULL DEFAULT '=',
  `valor1` varchar(255) NOT NULL,
  `valor2` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `responsables`
--

CREATE TABLE `responsables` (
  `id_responsable` int(11) NOT NULL,
  `nombres` varchar(255) NOT NULL,
  `apellido_paterno` varchar(255) DEFAULT NULL,
  `apellido_materno` varchar(255) DEFAULT NULL,
  `carnet_identidad` varchar(20) DEFAULT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `grado_instruccion` enum('Ninguno','Primaria','Secundaria','Técnico','Universitario','Postgrado') DEFAULT NULL,
  `idioma_frecuente` varchar(100) DEFAULT NULL,
  `parentesco` enum('Padre','Madre','Tutor','Otro') DEFAULT NULL,
  `celular` varchar(20) DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `id_rol` int(11) NOT NULL,
  `nombre_rol` varchar(50) NOT NULL COMMENT 'Ej: Administrador, Profesor, Secretario'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `anuncios`
--
ALTER TABLE `anuncios`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `bimestres_activos`
--
ALTER TABLE `bimestres_activos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `calificaciones`
--
ALTER TABLE `calificaciones`
  ADD PRIMARY KEY (`id_calificacion`),
  ADD UNIQUE KEY `id_estudiante` (`id_estudiante`,`id_materia`,`bimestre`),
  ADD KEY `id_materia` (`id_materia`);

--
-- Indices de la tabla `configuracion_sistema`
--
ALTER TABLE `configuracion_sistema`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `cursos`
--
ALTER TABLE `cursos`
  ADD PRIMARY KEY (`id_curso`);

--
-- Indices de la tabla `cursos_materias`
--
ALTER TABLE `cursos_materias`
  ADD PRIMARY KEY (`id_curso_materia`),
  ADD KEY `id_curso` (`id_curso`),
  ADD KEY `id_materia` (`id_materia`);

--
-- Indices de la tabla `estudiantes`
--
ALTER TABLE `estudiantes`
  ADD PRIMARY KEY (`id_estudiante`),
  ADD KEY `id_curso` (`id_curso`),
  ADD KEY `idx_estudiantes_id_responsable` (`id_responsable`);

--
-- Indices de la tabla `estudiante_abandono`
--
ALTER TABLE `estudiante_abandono`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unq_estudiante_abandono` (`id_estudiante`);

--
-- Indices de la tabla `estudiante_actividad_laboral`
--
ALTER TABLE `estudiante_actividad_laboral`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unq_estudiante_trab` (`id_estudiante`);

--
-- Indices de la tabla `estudiante_dificultades`
--
ALTER TABLE `estudiante_dificultades`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unq_estudiante_dif` (`id_estudiante`);

--
-- Indices de la tabla `estudiante_direccion`
--
ALTER TABLE `estudiante_direccion`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unq_estudiante_dir` (`id_estudiante`);

--
-- Indices de la tabla `estudiante_idioma_cultura`
--
ALTER TABLE `estudiante_idioma_cultura`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unq_estudiante_idioma` (`id_estudiante`);

--
-- Indices de la tabla `estudiante_salud`
--
ALTER TABLE `estudiante_salud`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unq_estudiante_salud` (`id_estudiante`);

--
-- Indices de la tabla `estudiante_servicios`
--
ALTER TABLE `estudiante_servicios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unq_estudiante_serv` (`id_estudiante`);

--
-- Indices de la tabla `estudiante_transporte`
--
ALTER TABLE `estudiante_transporte`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unq_estudiante_trans` (`id_estudiante`);

--
-- Indices de la tabla `materias`
--
ALTER TABLE `materias`
  ADD PRIMARY KEY (`id_materia`);

--
-- Indices de la tabla `personal`
--
ALTER TABLE `personal`
  ADD PRIMARY KEY (`id_personal`),
  ADD UNIQUE KEY `carnet_identidad` (`carnet_identidad`),
  ADD KEY `id_rol` (`id_rol`);

--
-- Indices de la tabla `profesores_materias_cursos`
--
ALTER TABLE `profesores_materias_cursos`
  ADD PRIMARY KEY (`id_profesor_materia_curso`),
  ADD UNIQUE KEY `id_personal` (`id_personal`,`id_curso_materia`),
  ADD KEY `id_curso_materia` (`id_curso_materia`);

--
-- Indices de la tabla `reportes_guardados`
--
ALTER TABLE `reportes_guardados`
  ADD PRIMARY KEY (`id_reporte`),
  ADD KEY `idx_reportes_personal` (`id_personal`);

--
-- Indices de la tabla `reportes_guardados_columnas`
--
ALTER TABLE `reportes_guardados_columnas`
  ADD PRIMARY KEY (`id_columna`),
  ADD KEY `idx_rgc_reporte` (`id_reporte`);

--
-- Indices de la tabla `reportes_guardados_filtros`
--
ALTER TABLE `reportes_guardados_filtros`
  ADD PRIMARY KEY (`id_filtro`),
  ADD KEY `idx_rgf_reporte` (`id_reporte`);

--
-- Indices de la tabla `responsables`
--
ALTER TABLE `responsables`
  ADD PRIMARY KEY (`id_responsable`),
  ADD UNIQUE KEY `uk_responsables_ci` (`carnet_identidad`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id_rol`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `anuncios`
--
ALTER TABLE `anuncios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `bimestres_activos`
--
ALTER TABLE `bimestres_activos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `calificaciones`
--
ALTER TABLE `calificaciones`
  MODIFY `id_calificacion` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `configuracion_sistema`
--
ALTER TABLE `configuracion_sistema`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `cursos`
--
ALTER TABLE `cursos`
  MODIFY `id_curso` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `cursos_materias`
--
ALTER TABLE `cursos_materias`
  MODIFY `id_curso_materia` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `estudiantes`
--
ALTER TABLE `estudiantes`
  MODIFY `id_estudiante` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `estudiante_abandono`
--
ALTER TABLE `estudiante_abandono`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `estudiante_actividad_laboral`
--
ALTER TABLE `estudiante_actividad_laboral`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `estudiante_dificultades`
--
ALTER TABLE `estudiante_dificultades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `estudiante_direccion`
--
ALTER TABLE `estudiante_direccion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `estudiante_idioma_cultura`
--
ALTER TABLE `estudiante_idioma_cultura`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `estudiante_salud`
--
ALTER TABLE `estudiante_salud`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `estudiante_servicios`
--
ALTER TABLE `estudiante_servicios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `estudiante_transporte`
--
ALTER TABLE `estudiante_transporte`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `materias`
--
ALTER TABLE `materias`
  MODIFY `id_materia` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `personal`
--
ALTER TABLE `personal`
  MODIFY `id_personal` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `profesores_materias_cursos`
--
ALTER TABLE `profesores_materias_cursos`
  MODIFY `id_profesor_materia_curso` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `reportes_guardados`
--
ALTER TABLE `reportes_guardados`
  MODIFY `id_reporte` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `reportes_guardados_columnas`
--
ALTER TABLE `reportes_guardados_columnas`
  MODIFY `id_columna` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `reportes_guardados_filtros`
--
ALTER TABLE `reportes_guardados_filtros`
  MODIFY `id_filtro` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `responsables`
--
ALTER TABLE `responsables`
  MODIFY `id_responsable` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id_rol` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `calificaciones`
--
ALTER TABLE `calificaciones`
  ADD CONSTRAINT `calificaciones_ibfk_1` FOREIGN KEY (`id_estudiante`) REFERENCES `estudiantes` (`id_estudiante`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `calificaciones_ibfk_2` FOREIGN KEY (`id_materia`) REFERENCES `materias` (`id_materia`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `cursos_materias`
--
ALTER TABLE `cursos_materias`
  ADD CONSTRAINT `cursos_materias_ibfk_1` FOREIGN KEY (`id_curso`) REFERENCES `cursos` (`id_curso`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `cursos_materias_ibfk_2` FOREIGN KEY (`id_materia`) REFERENCES `materias` (`id_materia`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `estudiantes`
--
ALTER TABLE `estudiantes`
  ADD CONSTRAINT `estudiantes_ibfk_1` FOREIGN KEY (`id_curso`) REFERENCES `cursos` (`id_curso`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `estudiantes_ibfk_responsable` FOREIGN KEY (`id_responsable`) REFERENCES `responsables` (`id_responsable`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `estudiante_abandono`
--
ALTER TABLE `estudiante_abandono`
  ADD CONSTRAINT `fk_abandono_estudiante` FOREIGN KEY (`id_estudiante`) REFERENCES `estudiantes` (`id_estudiante`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `estudiante_actividad_laboral`
--
ALTER TABLE `estudiante_actividad_laboral`
  ADD CONSTRAINT `fk_trabajo_estudiante` FOREIGN KEY (`id_estudiante`) REFERENCES `estudiantes` (`id_estudiante`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `estudiante_dificultades`
--
ALTER TABLE `estudiante_dificultades`
  ADD CONSTRAINT `fk_dificultades_estudiante` FOREIGN KEY (`id_estudiante`) REFERENCES `estudiantes` (`id_estudiante`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `estudiante_direccion`
--
ALTER TABLE `estudiante_direccion`
  ADD CONSTRAINT `fk_direccion_estudiante` FOREIGN KEY (`id_estudiante`) REFERENCES `estudiantes` (`id_estudiante`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `estudiante_idioma_cultura`
--
ALTER TABLE `estudiante_idioma_cultura`
  ADD CONSTRAINT `fk_idioma_cultura_estudiante` FOREIGN KEY (`id_estudiante`) REFERENCES `estudiantes` (`id_estudiante`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `estudiante_salud`
--
ALTER TABLE `estudiante_salud`
  ADD CONSTRAINT `fk_salud_estudiante` FOREIGN KEY (`id_estudiante`) REFERENCES `estudiantes` (`id_estudiante`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `estudiante_servicios`
--
ALTER TABLE `estudiante_servicios`
  ADD CONSTRAINT `fk_servicios_estudiante` FOREIGN KEY (`id_estudiante`) REFERENCES `estudiantes` (`id_estudiante`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `estudiante_transporte`
--
ALTER TABLE `estudiante_transporte`
  ADD CONSTRAINT `fk_transporte_estudiante` FOREIGN KEY (`id_estudiante`) REFERENCES `estudiantes` (`id_estudiante`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `personal`
--
ALTER TABLE `personal`
  ADD CONSTRAINT `personal_ibfk_1` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id_rol`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `profesores_materias_cursos`
--
ALTER TABLE `profesores_materias_cursos`
  ADD CONSTRAINT `profesores_materias_cursos_ibfk_1` FOREIGN KEY (`id_personal`) REFERENCES `personal` (`id_personal`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `profesores_materias_cursos_ibfk_2` FOREIGN KEY (`id_curso_materia`) REFERENCES `cursos_materias` (`id_curso_materia`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `reportes_guardados`
--
ALTER TABLE `reportes_guardados`
  ADD CONSTRAINT `fk_reportes_personal` FOREIGN KEY (`id_personal`) REFERENCES `personal` (`id_personal`);

--
-- Filtros para la tabla `reportes_guardados_columnas`
--
ALTER TABLE `reportes_guardados_columnas`
  ADD CONSTRAINT `fk_rgc_reporte` FOREIGN KEY (`id_reporte`) REFERENCES `reportes_guardados` (`id_reporte`);

--
-- Filtros para la tabla `reportes_guardados_filtros`
--
ALTER TABLE `reportes_guardados_filtros`
  ADD CONSTRAINT `fk_rgf_reporte` FOREIGN KEY (`id_reporte`) REFERENCES `reportes_guardados` (`id_reporte`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
