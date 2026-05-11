<?php
require_once dirname(__DIR__, 3) . '/config/database.php';

class Ticket {
    private $conn;
    private $table_name = "ticket";

    public $id_ticket;
    public $id_paciente;
    public $id_consultorio;
    public $codigo_ticket;
    public $prioridad;
    public $estado;
    public $tiempo_estimado_espera;
    public $motivo_consulta;
    public $fecha_creacion;
    public $hora_inicio_estimada;
    public $hora_fin_estimada;

    public function __construct() {
        $this->conn = Database::getConnection();
    }

    public function crear() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (id_paciente, id_consultorio, codigo_ticket, prioridad, estado, tiempo_estimado_espera, motivo_consulta) 
                  VALUES (:id_paciente, :id_consultorio, :codigo_ticket, :prioridad, :estado, :tiempo_estimado_espera, :motivo_consulta)";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id_paciente", $this->id_paciente);
        $stmt->bindParam(":id_consultorio", $this->id_consultorio);
        $stmt->bindParam(":codigo_ticket", $this->codigo_ticket);
        $stmt->bindParam(":prioridad", $this->prioridad);
        $stmt->bindParam(":estado", $this->estado);
        $stmt->bindParam(":tiempo_estimado_espera", $this->tiempo_estimado_espera);
        $stmt->bindParam(":motivo_consulta", $this->motivo_consulta);

        if ($stmt->execute()) {
            $this->id_ticket = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    public static function generarCodigo($id_especialidad) {
        $conn = Database::getConnection();

        $stmt = $conn->prepare("SELECT nombre_especialidad FROM especialidad WHERE id_especialidad = ?");
        $stmt->execute([$id_especialidad]);
        $esp = $stmt->fetch(PDO::FETCH_ASSOC);

        $abbr = 'TKT';
        if ($esp) {
            $palabras = explode(' ', $esp['nombre_especialidad']);
            $abbr = '';
            foreach ($palabras as $p) {
                $abbr .= strtoupper(substr($p, 0, 1));
            }
            $abbr = substr($abbr, 0, 3);
        }

        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM ticket t 
                                INNER JOIN consultorio c ON t.id_consultorio = c.id_consultorio
                                WHERE DATE(t.fecha_creacion) = CURDATE() AND c.id_especialidad = ?");
        $stmt->execute([$id_especialidad]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $seq = str_pad($row['total'] + 1, 3, '0', STR_PAD_LEFT);

        return $abbr . '-' . $seq;
    }

    public static function contarAntes($id_consultorio, $id_ticket) {
        $conn = Database::getConnection();
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM ticket 
                                WHERE id_consultorio = ? AND estado = 'Espera' AND id_ticket < ?");
        $stmt->execute([$id_consultorio, $id_ticket]);
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }

    public static function tieneTicketHoy($id_paciente) {
        $conn = Database::getConnection();
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM ticket 
                                WHERE id_paciente = ? AND DATE(fecha_creacion) = CURDATE() 
                                AND estado IN ('Espera', 'Llamado')");
        $stmt->execute([$id_paciente]);
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'] > 0;
    }

    public static function obtenerPorId($id_ticket) {
        $conn = Database::getConnection();
        $query = "SELECT t.*, c.numero_consultorio, c.piso, e.nombre_especialidad,
                         per.nombre as paciente_nombre, per.apellido as paciente_apellido
                  FROM ticket t
                  INNER JOIN consultorio c ON t.id_consultorio = c.id_consultorio
                  INNER JOIN especialidad e ON c.id_especialidad = e.id_especialidad
                  INNER JOIN paciente p ON t.id_paciente = p.id_paciente
                  INNER JOIN persona per ON p.id_persona = per.id_persona
                  WHERE t.id_ticket = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$id_ticket]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function estimarTiempoEspera($id_consultorio) {
        $conn = Database::getConnection();
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM ticket 
                                WHERE id_consultorio = ? AND estado = 'Espera'");
        $stmt->execute([$id_consultorio]);
        $cantidad = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        return $cantidad * 10;
    }

    public static function listarPorPaciente($id_paciente) {
        $conn = Database::getConnection();
        $query = "SELECT t.*, c.numero_consultorio, c.piso, e.nombre_especialidad,
                         per.nombre as paciente_nombre, per.apellido as paciente_apellido
                  FROM ticket t
                  INNER JOIN consultorio c ON t.id_consultorio = c.id_consultorio
                  INNER JOIN especialidad e ON c.id_especialidad = e.id_especialidad
                  INNER JOIN paciente p ON t.id_paciente = p.id_paciente
                  INNER JOIN persona per ON p.id_persona = per.id_persona
                  WHERE t.id_paciente = ?
                  ORDER BY t.fecha_creacion DESC";
        $stmt = $conn->prepare($query);
        $stmt->execute([$id_paciente]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function contarActivosHoy($id_paciente) {
        $conn = Database::getConnection();
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM ticket 
                                WHERE id_paciente = ? AND DATE(fecha_creacion) = CURDATE() 
                                AND estado IN ('Espera', 'Llamado')");
        $stmt->execute([$id_paciente]);
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }

    public static function contarHoy() {
        $conn = Database::getConnection();
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM ticket WHERE DATE(fecha_creacion) = CURDATE()");
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }

    public static function contarPorEstado($estado) {
        $conn = Database::getConnection();
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM ticket WHERE estado = ? AND DATE(fecha_creacion) = CURDATE()");
        $stmt->execute([$estado]);
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }

    public static function cancelar($id_ticket, $id_paciente) {
        $conn = Database::getConnection();
        $stmt = $conn->prepare("SELECT * FROM ticket WHERE id_ticket = ? AND id_paciente = ? AND estado = 'Espera'");
        $stmt->execute([$id_ticket, $id_paciente]);
        $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$ticket) {
            return false;
        }

        $stmt = $conn->prepare("UPDATE ticket SET estado = 'Ausente' WHERE id_ticket = ?");
        return $stmt->execute([$id_ticket]);
    }

    public static function listarTodos($filtro_estado = '', $limit = 200, $offset = 0) {
        $conn = Database::getConnection();
        $query = "SELECT t.*, c.numero_consultorio, c.piso, e.nombre_especialidad,
                         per.nombre as paciente_nombre, per.apellido as paciente_apellido,
                         per.documento
                  FROM ticket t
                  INNER JOIN consultorio c ON t.id_consultorio = c.id_consultorio
                  INNER JOIN especialidad e ON c.id_especialidad = e.id_especialidad
                  INNER JOIN paciente p ON t.id_paciente = p.id_paciente
                  INNER JOIN persona per ON p.id_persona = per.id_persona";
        if (!empty($filtro_estado)) {
            $query .= " WHERE t.estado = :estado";
        }
        $query .= " ORDER BY t.fecha_creacion DESC LIMIT " . intval($limit) . " OFFSET " . intval($offset);
        $stmt = $conn->prepare($query);
        if (!empty($filtro_estado)) {
            $stmt->bindParam(':estado', $filtro_estado);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function contarTodos($filtro_estado = '') {
        $conn = Database::getConnection();
        $query = "SELECT COUNT(*) as total FROM ticket t";
        if (!empty($filtro_estado)) {
            $query .= " WHERE t.estado = :estado";
        }
        $stmt = $conn->prepare($query);
        if (!empty($filtro_estado)) {
            $stmt->bindParam(':estado', $filtro_estado);
        }
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }

    public static function listarEnEspera($id_consultorios) {
        $conn = Database::getConnection();
        if (empty($id_consultorios)) return [];

        $placeholders = implode(',', array_fill(0, count($id_consultorios), '?'));
        $query = "SELECT t.*, c.numero_consultorio, c.piso, e.nombre_especialidad,
                         per.nombre as paciente_nombre, per.apellido as paciente_apellido,
                         per.documento, per.telefono, per.fecha_nacimiento,
                         p.grupo_sanguineo, p.num_seguro
                  FROM ticket t
                  INNER JOIN consultorio c ON t.id_consultorio = c.id_consultorio
                  INNER JOIN especialidad e ON c.id_especialidad = e.id_especialidad
                  INNER JOIN paciente p ON t.id_paciente = p.id_paciente
                  INNER JOIN persona per ON p.id_persona = per.id_persona
                  WHERE t.id_consultorio IN ($placeholders) AND t.estado IN ('Espera', 'Llamado')
                  ORDER BY FIELD(t.estado, 'Llamado', 'Espera'), t.prioridad DESC, t.fecha_creacion ASC";
        $stmt = $conn->prepare($query);
        $stmt->execute($id_consultorios);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function llamarSiguiente($id_consultorios) {
        $conn = Database::getConnection();
        if (empty($id_consultorios)) return null;

        $placeholders = implode(',', array_fill(0, count($id_consultorios), '?'));
        $stmt = $conn->prepare("SELECT id_ticket FROM ticket 
                                WHERE id_consultorio IN ($placeholders) AND estado = 'Espera'
                                ORDER BY prioridad DESC, fecha_creacion ASC LIMIT 1");
        $stmt->execute($id_consultorios);
        $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$ticket) return null;

        $stmt = $conn->prepare("UPDATE ticket SET estado = 'Llamado', hora_inicio_estimada = CURTIME() WHERE id_ticket = ?");
        $stmt->execute([$ticket['id_ticket']]);

        return $ticket['id_ticket'];
    }

    public static function cambiarEstado($id_ticket, $estado) {
        $conn = Database::getConnection();
        $stmt = $conn->prepare("UPDATE ticket SET estado = ? WHERE id_ticket = ?");
        return $stmt->execute([$estado, $id_ticket]);
    }

    public static function listarMonitor() {
        $conn = Database::getConnection();

        $llamados = $conn->query("
            SELECT t.codigo_ticket, c.numero_consultorio, c.piso, t.prioridad,
                   t.hora_inicio_estimada, e.nombre_especialidad,
                   TIMESTAMPDIFF(MINUTE, t.hora_inicio_estimada, CURTIME()) as minutos_llamado
            FROM ticket t
            INNER JOIN consultorio c ON t.id_consultorio = c.id_consultorio
            INNER JOIN especialidad e ON c.id_especialidad = e.id_especialidad
            WHERE t.estado = 'Llamado' AND DATE(t.fecha_creacion) = CURDATE()
            ORDER BY t.hora_inicio_estimada ASC
        ")->fetchAll(PDO::FETCH_ASSOC);

        $atendidos = $conn->query("
            SELECT t.codigo_ticket, c.numero_consultorio, c.piso, t.prioridad,
                   a.fecha_hora_atencion, e.nombre_especialidad
            FROM atencion a
            INNER JOIN ticket t ON a.id_ticket = t.id_ticket
            INNER JOIN consultorio c ON t.id_consultorio = c.id_consultorio
            INNER JOIN especialidad e ON c.id_especialidad = e.id_especialidad
            WHERE a.fecha_hora_atencion >= DATE_SUB(NOW(), INTERVAL 30 MINUTE)
            ORDER BY a.fecha_hora_atencion DESC
            LIMIT 15
        ")->fetchAll(PDO::FETCH_ASSOC);

        $espera = $conn->query("
            SELECT e.nombre_especialidad, COUNT(*) as total
            FROM ticket t
            INNER JOIN consultorio c ON t.id_consultorio = c.id_consultorio
            INNER JOIN especialidad e ON c.id_especialidad = e.id_especialidad
            WHERE t.estado = 'Espera' AND DATE(t.fecha_creacion) = CURDATE()
            GROUP BY e.nombre_especialidad
            ORDER BY total DESC
        ")->fetchAll(PDO::FETCH_ASSOC);

        $total_espera = array_sum(array_column($espera, 'total'));

        return [
            'llamados' => $llamados,
            'atendidos' => $atendidos,
            'espera_por_especialidad' => $espera,
            'total_espera' => $total_espera,
            'timestamp' => date('H:i:s')
        ];
    }

    public static function calcularMM1($id_especialidad) {
        $conn = Database::getConnection();

        $stmt = $conn->prepare("SELECT COUNT(*) as total, 
                                       TIMESTAMPDIFF(HOUR, MIN(fecha_creacion), MAX(fecha_creacion)) + 1 as horas
                                FROM ticket 
                                WHERE id_consultorio IN (SELECT id_consultorio FROM consultorio WHERE id_especialidad = ?)
                                AND fecha_creacion >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
        $stmt->execute([$id_especialidad]);
        $llegadas = $stmt->fetch(PDO::FETCH_ASSOC);

        $lambda = 4;
        if ($llegadas && $llegadas['horas'] > 0) {
            $lambda = round($llegadas['total'] / max($llegadas['horas'], 1), 2);
        }

        $stmt = $conn->prepare("SELECT AVG(TIMESTAMPDIFF(MINUTE, hora_inicio_real, hora_fin_real)) as duracion_promedio
                                FROM atencion a
                                INNER JOIN ticket t ON a.id_ticket = t.id_ticket
                                INNER JOIN consultorio c ON t.id_consultorio = c.id_consultorio
                                WHERE c.id_especialidad = ? AND hora_fin_real IS NOT NULL");
        $stmt->execute([$id_especialidad]);
        $servicio = $stmt->fetch(PDO::FETCH_ASSOC);

        $mu = 6;
        if ($servicio && $servicio['duracion_promedio'] > 0) {
            $minutos = floatval($servicio['duracion_promedio']);
            $mu = round(60 / max($minutos, 1), 2);
        }

        $rho = $mu > 0 ? round($lambda / $mu, 4) : 0;
        $Lq = 0;
        $Wq = 0;
        $W = 0;

        if ($rho < 1 && $mu > 0) {
            $Lq = round(($lambda * $lambda) / ($mu * ($mu - $lambda)), 2);
            $Wq = $mu > $lambda ? round($Lq / $lambda, 2) : 0;
            $W = $mu > $lambda ? round(1 / ($mu - $lambda), 2) : 0;
        }

        return [
            'lambda' => $lambda,
            'mu' => $mu,
            'rho' => $rho,
            'Lq' => $Lq,
            'Wq' => $Wq,
            'W' => $W,
            'Wq_min' => round($Wq * 60),
            'W_min' => round($W * 60)
        ];
    }
}
