<?php
require_once dirname(__DIR__, 3) . '/config/database.php';

// Modelo base de Persona - datos personales compartidos por todos los roles
// Almacena información común: nombre, documento, contacto, dirección, fecha nacimiento
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
    public $email;

    public function __construct() {
        $this->conn = Database::getConnection();
    }

    // Crear una nueva persona en BD
    public function crear() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (nombre, apellido, documento, telefono, direccion, fecha_nacimiento, email) 
                  VALUES (:nombre, :apellido, :documento, :telefono, :direccion, :fecha_nacimiento, :email)";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":nombre", $this->nombre);
        $stmt->bindParam(":apellido", $this->apellido);
        $stmt->bindParam(":documento", $this->documento);
        $stmt->bindParam(":telefono", $this->telefono);
        $stmt->bindParam(":direccion", $this->direccion);
        $stmt->bindParam(":fecha_nacimiento", $this->fecha_nacimiento);
        $stmt->bindParam(":email", $this->email);

        if ($stmt->execute()) {
            $this->id_persona = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    // Actualizar datos de persona existente
    public function actualizar() {
        $query = "UPDATE " . $this->table_name . " 
                  SET nombre = :nombre, apellido = :apellido, documento = :documento, 
                      telefono = :telefono, direccion = :direccion, 
                      fecha_nacimiento = :fecha_nacimiento, email = :email
                  WHERE id_persona = :id_persona";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":nombre", $this->nombre);
        $stmt->bindParam(":apellido", $this->apellido);
        $stmt->bindParam(":documento", $this->documento);
        $stmt->bindParam(":telefono", $this->telefono);
        $stmt->bindParam(":direccion", $this->direccion);
        $stmt->bindParam(":fecha_nacimiento", $this->fecha_nacimiento);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":id_persona", $this->id_persona);

        return $stmt->execute();
    }

    // Buscar persona por número de documento (validación de unicidad)
    public static function buscarPorDocumento($documento) {
        $conn = Database::getConnection();
        $query = "SELECT * FROM persona WHERE documento = :documento";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(":documento", $documento);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Buscar persona por email (validación de unicidad y login)
    public static function buscarPorEmail($email) {
        $conn = Database::getConnection();
        $query = "SELECT * FROM persona WHERE email = :email";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(":email", $email);
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