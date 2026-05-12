<?php
require_once dirname(__DIR__, 3) . '/config/database.php';

// Modelo de Consultorio - representa un consultorio físico vinculado a una especialidad
// Cada ticket se asigna a un consultorio específico
class Consultorio {
    
    public static function listarTodos() {
        $conn = Database::getConnection();
        $query = "SELECT c.*, e.nombre_especialidad 
                  FROM consultorio c 
                  INNER JOIN especialidad e ON c.id_especialidad = e.id_especialidad
                  ORDER BY c.numero_consultorio";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function listarPorEspecialidad($id_especialidad) {
        $conn = Database::getConnection();
        $query = "SELECT * FROM consultorio WHERE id_especialidad = :id_especialidad ORDER BY numero_consultorio";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(":id_especialidad", $id_especialidad);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function crear($id_especialidad, $numero, $piso = '') {
        $conn = Database::getConnection();
        $query = "INSERT INTO consultorio (id_especialidad, numero_consultorio, piso) VALUES (:id_especialidad, :numero, :piso)";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(":id_especialidad", $id_especialidad);
        $stmt->bindParam(":numero", $numero);
        $stmt->bindParam(":piso", $piso);
        return $stmt->execute();
    }

    public static function eliminar($id) {
        $conn = Database::getConnection();
        $query = "DELETE FROM consultorio WHERE id_consultorio = :id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(":id", $id);
        return $stmt->execute();
    }
}