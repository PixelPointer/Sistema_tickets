SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- Base de datos: `sistema_tickets`

-- --------------------------------------------------------
-- Estructura de tabla para la tabla `persona`
-- --------------------------------------------------------
CREATE TABLE `persona` (
  `id_persona` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) NOT NULL,
  `documento` varchar(20) NOT NULL,
  `telefono` varchar(15) DEFAULT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `fecha_nacimiento` date NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id_persona`),
  UNIQUE KEY `documento` (`documento`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------
-- Estructura de tabla para la tabla `paciente`
-- --------------------------------------------------------
CREATE TABLE `paciente` (
  `id_paciente` int(11) NOT NULL AUTO_INCREMENT,
  `id_persona` int(11) NOT NULL,
  `grupo_sanguineo` varchar(10) DEFAULT NULL,
  `num_seguro` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id_paciente`),
  KEY `id_persona` (`id_persona`),
  CONSTRAINT `paciente_ibfk_1` FOREIGN KEY (`id_persona`) REFERENCES `persona` (`id_persona`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------
-- Estructura de tabla para la tabla `especialidad`
-- --------------------------------------------------------
CREATE TABLE `especialidad` (
  `id_especialidad` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_especialidad` varchar(100) NOT NULL,
  PRIMARY KEY (`id_especialidad`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------
-- Estructura de tabla para la tabla `personal`
-- --------------------------------------------------------
CREATE TABLE `personal` (
  `id_personal` int(11) NOT NULL AUTO_INCREMENT,
  `id_persona` int(11) NOT NULL,
  `id_especialidad` int(11) NOT NULL,
  `matricula_profesional` varchar(50) NOT NULL,
  PRIMARY KEY (`id_personal`),
  UNIQUE KEY `matricula_profesional` (`matricula_profesional`),
  KEY `id_persona` (`id_persona`),
  KEY `id_especialidad` (`id_especialidad`),
  CONSTRAINT `personal_ibfk_1` FOREIGN KEY (`id_persona`) REFERENCES `persona` (`id_persona`),
  CONSTRAINT `personal_ibfk_2` FOREIGN KEY (`id_especialidad`) REFERENCES `especialidad` (`id_especialidad`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------
-- Estructura de tabla para la tabla `consultorio`
-- --------------------------------------------------------
CREATE TABLE `consultorio` (
  `id_consultorio` int(11) NOT NULL AUTO_INCREMENT,
  `id_especialidad` int(11) NOT NULL,
  `numero_consultorio` varchar(10) NOT NULL,
  `piso` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`id_consultorio`),
  KEY `id_especialidad` (`id_especialidad`),
  CONSTRAINT `consultorio_ibfk_1` FOREIGN KEY (`id_especialidad`) REFERENCES `especialidad` (`id_especialidad`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------
-- Estructura de tabla para la tabla `ticket`
-- --------------------------------------------------------
CREATE TABLE `ticket` (
  `id_ticket` int(11) NOT NULL AUTO_INCREMENT,
  `id_paciente` int(11) NOT NULL,
  `id_consultorio` int(11) NOT NULL,
  `codigo_ticket` varchar(20) NOT NULL,
  `prioridad` enum('Verde','Amarillo','Rojo') DEFAULT 'Verde',
  `estado` enum('Espera','Llamado','Atendido','Ausente') DEFAULT 'Espera',
  `tiempo_estimado_espera` int(11) DEFAULT 0,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `hora_inicio_estimada` time DEFAULT NULL,
  `hora_fin_estimada` time DEFAULT NULL,
  PRIMARY KEY (`id_ticket`),
  KEY `id_paciente` (`id_paciente`),
  KEY `id_consultorio` (`id_consultorio`),
  CONSTRAINT `ticket_ibfk_1` FOREIGN KEY (`id_paciente`) REFERENCES `paciente` (`id_paciente`),
  CONSTRAINT `ticket_ibfk_2` FOREIGN KEY (`id_consultorio`) REFERENCES `consultorio` (`id_consultorio`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------
-- Estructura de tabla para la tabla `atencion`
-- --------------------------------------------------------
CREATE TABLE `atencion` (
  `id_atencion` int(11) NOT NULL AUTO_INCREMENT,
  `id_ticket` int(11) NOT NULL,
  `id_personal` int(11) NOT NULL,
  `tipo_diagnostico` enum('Presuntivo','Definitivo') NOT NULL,
  `descripcion_diagnostico` text NOT NULL,
  `tratamiento_prescrito` text DEFAULT NULL,
  `fecha_hora_atencion` timestamp NOT NULL DEFAULT current_timestamp(),
  `hora_inicio_real` time DEFAULT NULL,
  `hora_fin_real` time DEFAULT NULL,
  `fecha_proxima_cita` date DEFAULT NULL,
  PRIMARY KEY (`id_atencion`),
  UNIQUE KEY `id_ticket` (`id_ticket`),
  KEY `id_personal` (`id_personal`),
  CONSTRAINT `atencion_ibfk_1` FOREIGN KEY (`id_ticket`) REFERENCES `ticket` (`id_ticket`),
  CONSTRAINT `atencion_ibfk_2` FOREIGN KEY (`id_personal`) REFERENCES `personal` (`id_personal`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------
-- Tablas de Horarios y Usuarios (Sin cambios estructurales solicitados)
-- --------------------------------------------------------
CREATE TABLE `horario` (
  `id_horario` int(11) NOT NULL AUTO_INCREMENT,
  `dia_semana` enum('Lunes','Martes','Miercoles','Jueves','Viernes','Sabado','Domingo') DEFAULT NULL,
  `hora_inicio` time DEFAULT NULL,
  `hora_fin` time DEFAULT NULL,
  PRIMARY KEY (`id_horario`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `personal_horario` (
  `id_personal` int(11) NOT NULL,
  `id_horario` int(11) NOT NULL,
  PRIMARY KEY (`id_personal`,`id_horario`),
  CONSTRAINT `ph_ibfk_1` FOREIGN KEY (`id_personal`) REFERENCES `personal` (`id_personal`),
  CONSTRAINT `ph_ibfk_2` FOREIGN KEY (`id_horario`) REFERENCES `horario` (`id_horario`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `rol` (
  `id_rol` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_rol` varchar(50) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id_rol`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `usuario` (
  `id_usuario` int(11) NOT NULL AUTO_INCREMENT,
  `id_persona` int(11) NOT NULL,
  `id_rol` int(11) NOT NULL,
  `nombre_usuario` varchar(50) NOT NULL,
  `contrasena` varchar(255) NOT NULL,
  `estado` enum('Activo','Inactivo') DEFAULT 'Activo',
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `nombre_usuario` (`nombre_usuario`),
  CONSTRAINT `usuario_ibfk_1` FOREIGN KEY (`id_persona`) REFERENCES `persona` (`id_persona`),
  CONSTRAINT `usuario_ibfk_2` FOREIGN KEY (`id_rol`) REFERENCES `rol` (`id_rol`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

COMMIT;