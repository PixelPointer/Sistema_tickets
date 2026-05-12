-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost
-- Tiempo de generación: 12-05-2026 a las 03:02:48
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
-- Base de datos: `sistema_tickets`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `atencion`
--

CREATE TABLE `atencion` (
  `id_atencion` int(11) NOT NULL,
  `id_ticket` int(11) NOT NULL,
  `id_personal` int(11) NOT NULL,
  `tipo_diagnostico` enum('Presuntivo','Definitivo') NOT NULL,
  `descripcion_diagnostico` text NOT NULL,
  `tratamiento_prescrito` text DEFAULT NULL,
  `fecha_hora_atencion` timestamp NOT NULL DEFAULT current_timestamp(),
  `hora_inicio_real` time DEFAULT NULL,
  `fecha_proxima_cita` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `atencion`
--

INSERT INTO `atencion` (`id_atencion`, `id_ticket`, `id_personal`, `tipo_diagnostico`, `descripcion_diagnostico`, `tratamiento_prescrito`, `fecha_hora_atencion`, `hora_inicio_real`, `fecha_proxima_cita`) VALUES
(1, 1, 1, 'Definitivo', 'nda', 'nada', '2026-05-08 21:58:21', '17:58:21', '2026-05-14'),
(2, 2, 1, 'Presuntivo', 'nada', '', '2026-05-09 22:47:11', '18:47:11', NULL),
(3, 5, 1, 'Definitivo', 'a', 'a', '2026-05-10 01:44:38', '21:44:38', '2026-05-22'),
(4, 3, 2, 'Presuntivo', 'g', '', '2026-05-10 01:45:15', '21:45:15', NULL),
(5, 4, 2, 'Presuntivo', 'h', 'h', '2026-05-10 01:47:21', '21:47:21', '2026-05-10'),
(6, 6, 3, 'Presuntivo', 'dsfdsf', 'sdfsdf', '2026-05-10 01:50:11', '21:50:11', '2026-05-10');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `consultorio`
--

CREATE TABLE `consultorio` (
  `id_consultorio` int(11) NOT NULL,
  `id_especialidad` int(11) NOT NULL,
  `numero_consultorio` varchar(10) NOT NULL,
  `piso` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `consultorio`
--

INSERT INTO `consultorio` (`id_consultorio`, `id_especialidad`, `numero_consultorio`, `piso`) VALUES
(1, 1, '104', '1'),
(2, 4, '132', '1'),
(3, 5, '102', '1');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `especialidad`
--

CREATE TABLE `especialidad` (
  `id_especialidad` int(11) NOT NULL,
  `nombre_especialidad` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `especialidad`
--

INSERT INTO `especialidad` (`id_especialidad`, `nombre_especialidad`) VALUES
(1, 'Medicina General'),
(2, 'Pediatría'),
(3, 'Ginecología y Obstetricia'),
(4, 'Cardiología'),
(5, 'Dermatología'),
(6, 'Oftalmología'),
(7, 'Odontología'),
(8, 'Traumatología'),
(9, 'Nutrición'),
(10, 'Psicología');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `horario`
--

CREATE TABLE `horario` (
  `id_horario` int(11) NOT NULL,
  `dia_semana` enum('Lunes','Martes','Miercoles','Jueves','Viernes','Sabado','Domingo') DEFAULT NULL,
  `hora_inicio` time DEFAULT NULL,
  `hora_fin` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `horario`
--

INSERT INTO `horario` (`id_horario`, `dia_semana`, `hora_inicio`, `hora_fin`) VALUES
(1, 'Viernes', '08:00:00', '20:00:00'),
(2, 'Sabado', '08:00:00', '12:00:00'),
(3, 'Sabado', '16:00:00', '22:00:00'),
(4, 'Martes', '08:00:00', '10:00:00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `paciente`
--

CREATE TABLE `paciente` (
  `id_paciente` int(11) NOT NULL,
  `id_persona` int(11) NOT NULL,
  `grupo_sanguineo` varchar(10) DEFAULT NULL,
  `num_seguro` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `paciente`
--

INSERT INTO `paciente` (`id_paciente`, `id_persona`, `grupo_sanguineo`, `num_seguro`) VALUES
(1, 1, 'A+', ''),
(2, 2, 'A+', '12334'),
(3, 3, '', ''),
(4, 4, '', ''),
(5, 5, '', ''),
(6, 6, '', ''),
(7, 7, '', ''),
(8, 8, '', ''),
(9, 9, '', '');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `persona`
--

CREATE TABLE `persona` (
  `id_persona` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) NOT NULL,
  `documento` varchar(20) NOT NULL,
  `telefono` varchar(15) DEFAULT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `fecha_nacimiento` date NOT NULL,
  `email` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `persona`
--

INSERT INTO `persona` (`id_persona`, `nombre`, `apellido`, `documento`, `telefono`, `direccion`, `fecha_nacimiento`, `email`) VALUES
(1, 'admin1', 'admin', '123', '1234', 'calle', '2026-05-01', 'admin@gmail.com'),
(2, 'paciente', 'paciente', '123256', '123', 'calle', '2026-05-05', 'paciente@gmail.com'),
(3, 'Rafael', 'Mamani', '123345', '498494', 'calle', '2026-04-29', 'medico@gmail.com'),
(4, 'Alex', 'Rios', '987654', '69889155', 'la paz', '2003-03-02', NULL),
(5, 'Ivette', 'Lima', '9939582', '76243127', '000', '2001-03-12', NULL),
(6, 'rafael', 'mamani', '12345678', '789456', 'calle', '2026-04-28', 'rafael@gmail.com'),
(7, 'Alex', 'Pac', '9911575', '69889165', 'La Paz', '2004-02-14', 'alexpac@gmail.com'),
(8, 'cuenta', 'paciente', '111', '1111111', '000', '2000-01-01', 'ivesharn4@gmail.com'),
(9, 'juan', 'mendez', '1234568', '4984651', 'calle', '2000-01-01', 'juan@gmail.com');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `personal`
--

CREATE TABLE `personal` (
  `id_personal` int(11) NOT NULL,
  `id_persona` int(11) NOT NULL,
  `id_especialidad` int(11) NOT NULL,
  `matricula_profesional` varchar(50) NOT NULL,
  `limite_diario` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `personal`
--

INSERT INTO `personal` (`id_personal`, `id_persona`, `id_especialidad`, `matricula_profesional`, `limite_diario`) VALUES
(1, 3, 1, '7498465', 0),
(2, 5, 4, '23', 0),
(3, 4, 5, '123', 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `personal_horario`
--

CREATE TABLE `personal_horario` (
  `id_personal` int(11) NOT NULL,
  `id_horario` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `personal_horario`
--

INSERT INTO `personal_horario` (`id_personal`, `id_horario`) VALUES
(1, 1),
(1, 2),
(1, 3),
(1, 4),
(2, 2),
(2, 3),
(3, 2),
(3, 3);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rol`
--

CREATE TABLE `rol` (
  `id_rol` int(11) NOT NULL,
  `nombre_rol` varchar(50) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `rol`
--

INSERT INTO `rol` (`id_rol`, `nombre_rol`, `descripcion`) VALUES
(1, 'admin', 'Administrador del sistema con acceso total a la gestión de usuarios, personal y reportes.'),
(2, 'medico', 'Personal médico con permisos para atender tickets y registrar diagnósticos.'),
(3, 'paciente', 'Usuario final con permisos para visualizar sus tickets y estado de atención.');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ticket`
--

CREATE TABLE `ticket` (
  `id_ticket` int(11) NOT NULL,
  `id_paciente` int(11) NOT NULL,
  `id_consultorio` int(11) NOT NULL,
  `codigo_ticket` varchar(20) NOT NULL,
  `prioridad` enum('Verde','Amarillo','Rojo') DEFAULT 'Verde',
  `estado` enum('Espera','Llamado','Atendido','Ausente') DEFAULT 'Espera',
  `tiempo_estimado_espera` int(11) DEFAULT 0,
  `motivo_consulta` text DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `hora_inicio_estimada` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `ticket`
--

INSERT INTO `ticket` (`id_ticket`, `id_paciente`, `id_consultorio`, `codigo_ticket`, `prioridad`, `estado`, `tiempo_estimado_espera`, `motivo_consulta`, `fecha_creacion`, `hora_inicio_estimada`) VALUES
(1, 2, 1, 'MG-001', 'Verde', 'Atendido', 0, NULL, '2026-05-08 20:34:05', '17:57:48'),
(2, 2, 1, 'MG-002', 'Verde', 'Atendido', 0, NULL, '2026-05-08 22:45:03', '18:46:50'),
(3, 8, 2, 'C-001', 'Verde', 'Atendido', 0, NULL, '2026-05-10 01:41:29', '21:43:59'),
(4, 7, 2, 'C-002', 'Verde', 'Atendido', 10, NULL, '2026-05-10 01:41:35', NULL),
(5, 2, 1, 'MG-003', 'Verde', 'Atendido', 0, NULL, '2026-05-10 01:42:56', '21:43:55'),
(6, 7, 3, 'D-004', 'Verde', 'Atendido', 0, NULL, '2026-05-10 01:47:46', '21:49:14'),
(7, 2, 3, 'D-005', 'Verde', 'Llamado', 10, NULL, '2026-05-10 01:47:59', '21:50:21');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario`
--

CREATE TABLE `usuario` (
  `id_usuario` int(11) NOT NULL,
  `id_persona` int(11) NOT NULL,
  `id_rol` int(11) NOT NULL,
  `nombre_usuario` varchar(50) NOT NULL,
  `contrasena` varchar(255) NOT NULL,
  `estado` enum('Activo','Inactivo') DEFAULT 'Activo'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `usuario`
--

INSERT INTO `usuario` (`id_usuario`, `id_persona`, `id_rol`, `nombre_usuario`, `contrasena`, `estado`) VALUES
(1, 1, 1, 'admin@gmail.com', '$2y$10$j8hFO.2t/On5jO4k863sretXRwzUE6gcv71LHqdXgT/qAMEbFy3vq', 'Activo'),
(2, 2, 3, 'paciente@gmail.com', '$2y$10$nBbnC5pVZusMtLHGTqmgjeGdscCOW.WSajinGh9FTXSkicoPv3PEO', 'Activo'),
(3, 3, 2, 'medico@gmail.com', '$2y$10$wgemqmUb5Vixvgdq6pPSiuSJsi2lvldiGNtNmD8C5n59n3LZdWJGu', 'Activo'),
(4, 4, 1, 'alex@gmail.com', '$2y$10$h683OraJzmfpy5KBDfh69.nFq3Ax7aLrJUFDs2OM6jJMLgLXDZ5Cm', 'Activo'),
(5, 5, 2, 'ilimag2@fcpn.edu.bo', '$2y$10$5gh5ti/nQFgXy2jS2BDMdebfJ7Vn1/r0hPWoD1lmKdmdbrLIN88C.', 'Activo'),
(6, 6, 3, 'rafael@gmail.com', '$2y$10$TtDqccoaV4JR2yb.9nod6u10Z.AtslfmCw6IEfzDo1.TkgLnp0yx.', 'Activo'),
(7, 7, 3, 'alexpac@gmail.com', '$2y$10$623EhsiqZ0RpunMvlAQ3FuxKyeI0qBYQ1vTxRccLoI8yyvSDjW4Va', 'Activo'),
(8, 8, 3, 'ivesharn4@gmail.com', '$2y$10$R6H4zBogwYNiIsiJ7QhZP.r0GJJZZGXwltoGS8Q26jqQNBfdT/LxC', 'Activo'),
(9, 9, 1, 'juan@gmail.com', '$2y$10$Wct2L0/qiS8J61MEYenOPOj85t3uKivAuMUN54eaCZ88dffIrLxVi', 'Activo');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `atencion`
--
ALTER TABLE `atencion`
  ADD PRIMARY KEY (`id_atencion`),
  ADD UNIQUE KEY `id_ticket` (`id_ticket`),
  ADD KEY `id_personal` (`id_personal`);

--
-- Indices de la tabla `consultorio`
--
ALTER TABLE `consultorio`
  ADD PRIMARY KEY (`id_consultorio`),
  ADD KEY `id_especialidad` (`id_especialidad`);

--
-- Indices de la tabla `especialidad`
--
ALTER TABLE `especialidad`
  ADD PRIMARY KEY (`id_especialidad`);

--
-- Indices de la tabla `horario`
--
ALTER TABLE `horario`
  ADD PRIMARY KEY (`id_horario`);

--
-- Indices de la tabla `paciente`
--
ALTER TABLE `paciente`
  ADD PRIMARY KEY (`id_paciente`),
  ADD KEY `id_persona` (`id_persona`);

--
-- Indices de la tabla `persona`
--
ALTER TABLE `persona`
  ADD PRIMARY KEY (`id_persona`),
  ADD UNIQUE KEY `documento` (`documento`);

--
-- Indices de la tabla `personal`
--
ALTER TABLE `personal`
  ADD PRIMARY KEY (`id_personal`),
  ADD UNIQUE KEY `matricula_profesional` (`matricula_profesional`),
  ADD KEY `id_persona` (`id_persona`),
  ADD KEY `id_especialidad` (`id_especialidad`);

--
-- Indices de la tabla `personal_horario`
--
ALTER TABLE `personal_horario`
  ADD PRIMARY KEY (`id_personal`,`id_horario`),
  ADD KEY `ph_ibfk_2` (`id_horario`);

--
-- Indices de la tabla `rol`
--
ALTER TABLE `rol`
  ADD PRIMARY KEY (`id_rol`);

--
-- Indices de la tabla `ticket`
--
ALTER TABLE `ticket`
  ADD PRIMARY KEY (`id_ticket`),
  ADD KEY `id_paciente` (`id_paciente`),
  ADD KEY `id_consultorio` (`id_consultorio`);

--
-- Indices de la tabla `usuario`
--
ALTER TABLE `usuario`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `nombre_usuario` (`nombre_usuario`),
  ADD KEY `usuario_ibfk_1` (`id_persona`),
  ADD KEY `usuario_ibfk_2` (`id_rol`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `atencion`
--
ALTER TABLE `atencion`
  MODIFY `id_atencion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `consultorio`
--
ALTER TABLE `consultorio`
  MODIFY `id_consultorio` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `especialidad`
--
ALTER TABLE `especialidad`
  MODIFY `id_especialidad` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `horario`
--
ALTER TABLE `horario`
  MODIFY `id_horario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `paciente`
--
ALTER TABLE `paciente`
  MODIFY `id_paciente` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `persona`
--
ALTER TABLE `persona`
  MODIFY `id_persona` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `personal`
--
ALTER TABLE `personal`
  MODIFY `id_personal` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `rol`
--
ALTER TABLE `rol`
  MODIFY `id_rol` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `ticket`
--
ALTER TABLE `ticket`
  MODIFY `id_ticket` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `usuario`
--
ALTER TABLE `usuario`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `atencion`
--
ALTER TABLE `atencion`
  ADD CONSTRAINT `atencion_ibfk_1` FOREIGN KEY (`id_ticket`) REFERENCES `ticket` (`id_ticket`),
  ADD CONSTRAINT `atencion_ibfk_2` FOREIGN KEY (`id_personal`) REFERENCES `personal` (`id_personal`);

--
-- Filtros para la tabla `consultorio`
--
ALTER TABLE `consultorio`
  ADD CONSTRAINT `consultorio_ibfk_1` FOREIGN KEY (`id_especialidad`) REFERENCES `especialidad` (`id_especialidad`);

--
-- Filtros para la tabla `paciente`
--
ALTER TABLE `paciente`
  ADD CONSTRAINT `paciente_ibfk_1` FOREIGN KEY (`id_persona`) REFERENCES `persona` (`id_persona`);

--
-- Filtros para la tabla `personal`
--
ALTER TABLE `personal`
  ADD CONSTRAINT `personal_ibfk_1` FOREIGN KEY (`id_persona`) REFERENCES `persona` (`id_persona`),
  ADD CONSTRAINT `personal_ibfk_2` FOREIGN KEY (`id_especialidad`) REFERENCES `especialidad` (`id_especialidad`);

--
-- Filtros para la tabla `personal_horario`
--
ALTER TABLE `personal_horario`
  ADD CONSTRAINT `ph_ibfk_1` FOREIGN KEY (`id_personal`) REFERENCES `personal` (`id_personal`),
  ADD CONSTRAINT `ph_ibfk_2` FOREIGN KEY (`id_horario`) REFERENCES `horario` (`id_horario`);

--
-- Filtros para la tabla `ticket`
--
ALTER TABLE `ticket`
  ADD CONSTRAINT `ticket_ibfk_1` FOREIGN KEY (`id_paciente`) REFERENCES `paciente` (`id_paciente`),
  ADD CONSTRAINT `ticket_ibfk_2` FOREIGN KEY (`id_consultorio`) REFERENCES `consultorio` (`id_consultorio`);

--
-- Filtros para la tabla `usuario`
--
ALTER TABLE `usuario`
  ADD CONSTRAINT `usuario_ibfk_1` FOREIGN KEY (`id_persona`) REFERENCES `persona` (`id_persona`),
  ADD CONSTRAINT `usuario_ibfk_2` FOREIGN KEY (`id_rol`) REFERENCES `rol` (`id_rol`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
