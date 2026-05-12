<?php
require_once dirname(__DIR__, 3) . '/config/database.php';

// RF 5: Modelo de Especialidad Médica
// Cada especialidad define un área médica y se usa para generar códigos de ticket
// y organizar consultorios, médicos y horarios
class Especialidad {
    
    public static function listarTodos() {
        $conn = Database::getConnection();
        $query = "SELECT * FROM especialidad ORDER BY nombre_especialidad";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function buscarPorId($id) {
        $conn = Database::getConnection();
        $query = "SELECT * FROM especialidad WHERE id_especialidad = :id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function crear($nombre) {
        $conn = Database::getConnection();
        $query = "INSERT INTO especialidad (nombre_especialidad) VALUES (:nombre)";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(":nombre", $nombre);
        return $stmt->execute();
    }

    public static function eliminar($id) {
        $conn = Database::getConnection();
        $query = "DELETE FROM especialidad WHERE id_especialidad = :id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(":id", $id);
        return $stmt->execute();
    }
}