<?php
require_once dirname(__DIR__, 3) . '/config/database.php';

class Atencion {
    private $conn;

    public function __construct() {
        $this->conn = Database::getConnection();
    }

    public function crear($id_ticket, $id_personal, $tipo_diagnostico, $descripcion_diagnostico, $tratamiento_prescrito, $fecha_proxima_cita = null) {
        $query = "INSERT INTO atencion (id_ticket, id_personal, tipo_diagnostico, descripcion_diagnostico, tratamiento_prescrito, hora_inicio_real, fecha_proxima_cita) 
                  VALUES (?, ?, ?, ?, ?, CURTIME(), ?)";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$id_ticket, $id_personal, $tipo_diagnostico, $descripcion_diagnostico, $tratamiento_prescrito, $fecha_proxima_cita]);
    }

    public static function finalizar($id_atencion) {
        $conn = Database::getConnection();
        $stmt = $conn->prepare("UPDATE atencion SET hora_fin_real = CURTIME() WHERE id_atencion = ?");
        return $stmt->execute([$id_atencion]);
    }

    public static function buscarPorTicket($id_ticket) {
        $conn = Database::getConnection();
        $stmt = $conn->prepare("SELECT * FROM atencion WHERE id_ticket = ?");
        $stmt->execute([$id_ticket]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

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

    public static function actualizar($id_atencion, $tipo_diagnostico, $descripcion_diagnostico, $tratamiento_prescrito, $fecha_proxima_cita = null) {
        $conn = Database::getConnection();
        $query = "UPDATE atencion SET tipo_diagnostico = ?, descripcion_diagnostico = ?, tratamiento_prescrito = ?, fecha_proxima_cita = ? WHERE id_atencion = ?";
        $stmt = $conn->prepare($query);
        return $stmt->execute([$tipo_diagnostico, $descripcion_diagnostico, $tratamiento_prescrito, $fecha_proxima_cita, $id_atencion]);
    }
}
