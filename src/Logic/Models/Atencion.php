<?php
require_once dirname(__DIR__, 3) . '/config/database.php';

// RF 13: Modelo de Registro de Atenciones Médicas
// Almacena diagnósticos, tratamientos, tiempos reales de atención y próximas citas
class Atencion {
    private $conn;

    public function __construct() {
        $this->conn = Database::getConnection();
    }

    // RF 11-14: Crear registro de atención cuando el médico atiende al paciente
    // Guarda diagnóstico, tratamiento y hora_inicio_real, vincula ticket con personal
    public function crear($id_ticket, $id_personal, $tipo_diagnostico, $descripcion_diagnostico, $tratamiento_prescrito, $fecha_proxima_cita = null) {
        $query = "INSERT INTO atencion (id_ticket, id_personal, tipo_diagnostico, descripcion_diagnostico, tratamiento_prescrito, hora_inicio_real, fecha_proxima_cita) 
                  VALUES (?, ?, ?, ?, ?, CURTIME(), ?)";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$id_ticket, $id_personal, $tipo_diagnostico, $descripcion_diagnostico, $tratamiento_prescrito, $fecha_proxima_cita]);
    }

    // RF 11: Finalizar atención registrando hora_fin_real (usado en μ de M/M/1)
    public static function finalizar($id_atencion) {
        $conn = Database::getConnection();
        $stmt = $conn->prepare("UPDATE atencion SET hora_fin_real = CURTIME() WHERE id_atencion = ?");
        return $stmt->execute([$id_atencion]);
    }

    // Buscar atención por ticket (usado para verificar si ya fue atendido)
    public static function buscarPorTicket($id_ticket) {
        $conn = Database::getConnection();
        $stmt = $conn->prepare("SELECT * FROM atencion WHERE id_ticket = ?");
        $stmt->execute([$id_ticket]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // RF 13: Historial resumido (últimas 10 atenciones) para vista del médico
    public static function listarHistorial($id_paciente) {
        $conn = Database::getConnection();
        $query = "SELECT a.*, t.codigo_ticket, t.fecha_creacion,
                         c.numero_consultorio,
                         e.nombre_especialidad,
                         per.nombre as medico_nombre, per.apellido as medico_apellido
                  FROM atencion a
                  INNER JOIN ticket t ON a.id_ticket = t.id_ticket
                  INNER JOIN consultorio c ON t.id_consultorio = c.id_consultorio
                  INNER JOIN especialidad e ON c.id_especialidad = e.id_especialidad
                  INNER JOIN personal m ON a.id_personal = m.id_personal
                  INNER JOIN persona per ON m.id_persona = per.id_persona
                  WHERE t.id_paciente = ?
                  ORDER BY a.fecha_hora_atencion DESC
                  LIMIT 10";
        $stmt = $conn->prepare($query);
        $stmt->execute([$id_paciente]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // RF 13: Historial completo de atenciones del paciente (para vista detallada)
    public static function listarHistorialCompleto($id_paciente) {
        $conn = Database::getConnection();
        $query = "SELECT a.*, t.codigo_ticket, t.fecha_creacion,
                         c.numero_consultorio, c.piso,
                         e.nombre_especialidad,
                         per.nombre as medico_nombre, per.apellido as medico_apellido
                  FROM atencion a
                  INNER JOIN ticket t ON a.id_ticket = t.id_ticket
                  INNER JOIN consultorio c ON t.id_consultorio = c.id_consultorio
                  INNER JOIN especialidad e ON c.id_especialidad = e.id_especialidad
                  INNER JOIN personal m ON a.id_personal = m.id_personal
                  INNER JOIN persona per ON m.id_persona = per.id_persona
                  WHERE t.id_paciente = ?
                  ORDER BY a.fecha_hora_atencion DESC";
        $stmt = $conn->prepare($query);
        $stmt->execute([$id_paciente]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Listar pacientes atendidos por un médico (agrupado, con última atención)
    public static function listarPorPersonal($id_personal) {
        $conn = Database::getConnection();
        $query = "SELECT t.id_paciente, 
                         per.nombre as paciente_nombre, per.apellido as paciente_apellido,
                         per.documento, per.telefono,
                         MAX(a.fecha_hora_atencion) as ultima_atencion,
                         COUNT(a.id_atencion) as total_atenciones
                  FROM atencion a
                  INNER JOIN ticket t ON a.id_ticket = t.id_ticket
                  INNER JOIN paciente p ON t.id_paciente = p.id_paciente
                  INNER JOIN persona per ON p.id_persona = per.id_persona
                  WHERE a.id_personal = ?
                  GROUP BY t.id_paciente, per.nombre, per.apellido, per.documento, per.telefono
                  ORDER BY ultima_atencion DESC";
        $stmt = $conn->prepare($query);
        $stmt->execute([$id_personal]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // RF 15: Listar próximas citas programadas para un paciente
    public static function listarProximasCitas($id_paciente) {
        $conn = Database::getConnection();
        $query = "SELECT a.fecha_proxima_cita, a.tipo_diagnostico, a.descripcion_diagnostico, 
                         t.codigo_ticket, t.fecha_creacion,
                         c.numero_consultorio, c.piso,
                         e.nombre_especialidad,
                         per.nombre as medico_nombre, per.apellido as medico_apellido
                  FROM atencion a
                  INNER JOIN ticket t ON a.id_ticket = t.id_ticket
                  INNER JOIN consultorio c ON t.id_consultorio = c.id_consultorio
                  INNER JOIN especialidad e ON c.id_especialidad = e.id_especialidad
                  INNER JOIN personal m ON a.id_personal = m.id_personal
                  INNER JOIN persona per ON m.id_persona = per.id_persona
                  WHERE t.id_paciente = ? AND a.fecha_proxima_cita IS NOT NULL
                  ORDER BY a.fecha_proxima_cita ASC";
        $stmt = $conn->prepare($query);
        $stmt->execute([$id_paciente]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // RF 18: Reporte diario de atenciones de un médico en una fecha específica
    public static function listarAtencionesPorPersonalYFecha($id_personal, $fecha) {
        $conn = Database::getConnection();
        $query = "SELECT a.*, t.codigo_ticket, t.prioridad,
                         c.numero_consultorio, c.piso,
                         e.nombre_especialidad,
                         per.nombre as paciente_nombre, per.apellido as paciente_apellido,
                         per.documento, per.telefono
                  FROM atencion a
                  INNER JOIN ticket t ON a.id_ticket = t.id_ticket
                  INNER JOIN consultorio c ON t.id_consultorio = c.id_consultorio
                  INNER JOIN especialidad e ON c.id_especialidad = e.id_especialidad
                  INNER JOIN paciente p ON t.id_paciente = p.id_paciente
                  INNER JOIN persona per ON p.id_persona = per.id_persona
                  WHERE a.id_personal = ? AND DATE(a.fecha_hora_atencion) = ?
                  ORDER BY a.fecha_hora_atencion ASC";
        $stmt = $conn->prepare($query);
        $stmt->execute([$id_personal, $fecha]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Actualizar datos de una atención existente
    public static function actualizar($id_atencion, $tipo_diagnostico, $descripcion_diagnostico, $tratamiento_prescrito, $fecha_proxima_cita = null) {
        $conn = Database::getConnection();
        $query = "UPDATE atencion SET tipo_diagnostico = ?, descripcion_diagnostico = ?, tratamiento_prescrito = ?, fecha_proxima_cita = ? WHERE id_atencion = ?";
        $stmt = $conn->prepare($query);
        return $stmt->execute([$tipo_diagnostico, $descripcion_diagnostico, $tratamiento_prescrito, $fecha_proxima_cita, $id_atencion]);
    }
}
