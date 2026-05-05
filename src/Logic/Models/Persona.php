<?php
require_once dirname(__DIR__, 3) . '/config/database.php';

class Persona {
    private $conn;
    private $table_name = "persona";

    public $id_persona;
    public $nombre;
    public $apellido;
    public $documento;
    public $telefono;
    public $direccion;
    public $fecha_nacimiento;

    public function __construct() {
        $this->conn = Database::getConnection();
    }

    public function crear() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (nombre, apellido, documento, telefono, direccion, fecha_nacimiento) 
                  VALUES (:nombre, :apellido, :documento, :telefono, :direccion, :fecha_nacimiento)";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":nombre", $this->nombre);
        $stmt->bindParam(":apellido", $this->apellido);
        $stmt->bindParam(":documento", $this->documento);
        $stmt->bindParam(":telefono", $this->telefono);
        $stmt->bindParam(":direccion", $this->direccion);
        $stmt->bindParam(":fecha_nacimiento", $this->fecha_nacimiento);

        if ($stmt->execute()) {
            $this->id_persona = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    public function buscarPorDocumento($documento) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE documento = :documento";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":documento", $documento);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function buscarPorId($id) {
        $conn = Database::getConnection();
        $query = "SELECT * FROM persona WHERE id_persona = :id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}