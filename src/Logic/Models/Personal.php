<?php
require_once dirname(__DIR__, 3) . '/config/database.php';

class Personal {

    public static function contarTodos() {
        $conn = Database::getConnection();
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM personal");
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }
    
    public static function listarTodos($limit = 0, $offset = 0) {
        $conn = Database::getConnection();
        $query = "SELECT p.*, per.nombre, per.apellido, per.documento, e.nombre_especialidad 
                  FROM personal p 
                  INNER JOIN persona per ON p.id_persona = per.id_persona
                  INNER JOIN especialidad e ON p.id_especialidad = e.id_especialidad
                  ORDER BY per.apellido, per.nombre";
        if ($limit > 0) {
            $query .= " LIMIT " . intval($limit) . " OFFSET " . intval($offset);
        }
        $stmt = $conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function buscarPorPersona($id_persona) {
        $conn = Database::getConnection();
        $query = "SELECT p.*, e.nombre_especialidad
                  FROM personal p
                  LEFT JOIN especialidad e ON p.id_especialidad = e.id_especialidad
                  WHERE p.id_persona = :id_persona";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(":id_persona", $id_persona);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function buscarPorId($id_personal) {
        $conn = Database::getConnection();
        $query = "SELECT p.*, e.nombre_especialidad
                  FROM personal p
                  LEFT JOIN especialidad e ON p.id_especialidad = e.id_especialidad
                  WHERE p.id_personal = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$id_personal]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function crear($id_persona, $id_especialidad, $matricula, $limite_diario = 0) {
        $conn = Database::getConnection();
        $query = "INSERT INTO personal (id_persona, id_especialidad, matricula_profesional, limite_diario) 
                  VALUES (:id_persona, :id_especialidad, :matricula, :limite_diario)";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(":id_persona", $id_persona);
        $stmt->bindParam(":id_especialidad", $id_especialidad);
        $stmt->bindParam(":matricula", $matricula);
        $stmt->bindParam(":limite_diario", $limite_diario);
        return $stmt->execute();
    }

    public static function actualizarLimiteDiario($id_personal, $limite) {
        $conn = Database::getConnection();
        $stmt = $conn->prepare("UPDATE personal SET limite_diario = ? WHERE id_personal = ?");
        return $stmt->execute([intval($limite), intval($id_personal)]);
    }

    public static function contarAtendidosHoy($id_personal) {
        $conn = Database::getConnection();
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM atencion 
                                WHERE id_personal = ? AND DATE(fecha_hora_atencion) = CURDATE()");
        $stmt->execute([intval($id_personal)]);
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }

    public static function eliminar($id) {
        $conn = Database::getConnection();
        $query = "DELETE FROM personal WHERE id_personal = :id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(":id", $id);
        return $stmt->execute();
    }
}