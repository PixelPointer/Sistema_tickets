<?php
require_once dirname(__DIR__, 3) . '/config/database.php';

class Usuario {
    private $conn;
    private $table_name = "usuario";

    public $id_usuario;
    public $id_persona;
    public $id_rol;
    public $nombre_usuario;
    public $contrasena;
    public $estado;

    public function __construct() {
        $this->conn = Database::getConnection();
    }

    public function crear() {
        try {
            $query = "INSERT INTO " . $this->table_name . " 
                      (id_persona, id_rol, nombre_usuario, contrasena, estado) 
                      VALUES (:id_persona, :id_rol, :nombre_usuario, :contrasena, :estado)";

            $stmt = $this->conn->prepare($query);

            $password_hash = password_hash($this->contrasena, PASSWORD_DEFAULT);

            $stmt->bindParam(":id_persona", $this->id_persona);
            $stmt->bindParam(":id_rol", $this->id_rol);
            $stmt->bindParam(":nombre_usuario", $this->nombre_usuario);
            $stmt->bindParam(":contrasena", $password_hash);
            $stmt->bindParam(":estado", $this->estado);

            if ($stmt->execute()) {
                $this->id_usuario = $this->conn->lastInsertId();
                return true;
            }
            return false;
        } catch (PDOException $e) {
            error_log("Error en Usuario crear: " . $e->getMessage());
            throw new Exception("Error DB: " . $e->getMessage());
        }
    }

    public function buscarPorUsuario($nombre_usuario) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE nombre_usuario = :nombre_usuario";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":nombre_usuario", $nombre_usuario);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function verificarLogin($nombre_usuario, $contrasena) {
        $usuario = $this->buscarPorUsuario($nombre_usuario);
        
        if ($usuario && password_verify($contrasena, $usuario['contrasena'])) {
            return $usuario;
        }
        return false;
    }

    public static function buscarPorId($id) {
        $conn = Database::getConnection();
        $query = "SELECT * FROM usuario WHERE id_usuario = :id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}