<?php
require_once dirname(__DIR__, 3) . '/config/database.php';

class Paciente {
    private $conn;
    private $table_name = "paciente";

    public $id_paciente;
    public $id_persona;
    public $grupo_sanguineo;
    public $num_seguro;

    public function __construct() {
        $this->conn = Database::getConnection();
    }

    public function crear() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (id_persona, grupo_sanguineo, num_seguro) 
                  VALUES (:id_persona, :grupo_sanguineo, :num_seguro)";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":id_persona", $this->id_persona);
        $stmt->bindParam(":grupo_sanguineo", $this->grupo_sanguineo);
        $stmt->bindParam(":num_seguro", $this->num_seguro);

        if ($stmt->execute()) {
            $this->id_paciente = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    public function buscarPorPersona($id_persona) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id_persona = :id_persona";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id_persona", $id_persona);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function buscarPorId($id) {
        $conn = Database::getConnection();
        $query = "SELECT p.*, per.nombre, per.apellido, per.documento, per.telefono, per.direccion, per.fecha_nacimiento 
                  FROM persona per 
                  INNER JOIN paciente p ON p.id_persona = per.id_persona 
                  WHERE p.id_paciente = :id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}