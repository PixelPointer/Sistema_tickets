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

    public static function buscarUsuarios($search, $limit = 0, $offset = 0) {
        $conn = Database::getConnection();
        $query = "SELECT u.id_usuario, u.nombre_usuario, u.estado, u.id_rol, 
                  p.nombre, p.apellido, p.documento, p.telefono,
                  r.nombre_rol
                  FROM usuario u 
                  INNER JOIN persona p ON u.id_persona = p.id_persona
                  INNER JOIN rol r ON u.id_rol = r.id_rol
                  WHERE (p.nombre LIKE ? OR p.apellido LIKE ? OR u.nombre_usuario LIKE ?)
                  ORDER BY u.id_usuario DESC";
        if ($limit > 0) {
            $query .= " LIMIT " . intval($limit) . " OFFSET " . intval($offset);
        }
        $stmt = $conn->prepare($query);
        $searchTerm = '%' . $search . '%';
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function contarUsuariosBusqueda($search) {
        $conn = Database::getConnection();
        $query = "SELECT COUNT(*) as total FROM usuario u 
                  INNER JOIN persona p ON u.id_persona = p.id_persona
                  WHERE p.nombre LIKE ? OR p.apellido LIKE ? OR u.nombre_usuario LIKE ?";
        $stmt = $conn->prepare($query);
        $searchTerm = '%' . $search . '%';
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }

    public static function listarTodos($limit = 0, $offset = 0) {
        $conn = Database::getConnection();
        $query = "SELECT u.id_usuario, u.nombre_usuario, u.estado, u.id_rol, 
                  p.nombre, p.apellido, p.documento, p.telefono,
                  r.nombre_rol
                  FROM usuario u 
                  INNER JOIN persona p ON u.id_persona = p.id_persona
                  INNER JOIN rol r ON u.id_rol = r.id_rol
                  ORDER BY u.id_usuario DESC";
        if ($limit > 0) {
            $query .= " LIMIT " . intval($limit) . " OFFSET " . intval($offset);
        }
        $stmt = $conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function contarTodos() {
        $conn = Database::getConnection();
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM usuario");
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }

    public static function actualizarRol($id_usuario, $id_rol) {
        $conn = Database::getConnection();
        $query = "UPDATE usuario SET id_rol = :id_rol WHERE id_usuario = :id_usuario";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(":id_rol", $id_rol);
        $stmt->bindParam(":id_usuario", $id_usuario);
        return $stmt->execute();
    }

    public static function listarRoles() {
        $conn = Database::getConnection();
        $query = "SELECT * FROM rol ORDER BY id_rol";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function actualizarPerfil($id_usuario, $nombre, $apellido) {
        $conn = Database::getConnection();
        $query = "UPDATE persona p 
                  INNER JOIN usuario u ON p.id_persona = u.id_persona 
                  SET p.nombre = :nombre, p.apellido = :apellido 
                  WHERE u.id_usuario = :id_usuario";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(":nombre", $nombre);
        $stmt->bindParam(":apellido", $apellido);
        $stmt->bindParam(":id_usuario", $id_usuario);
        return $stmt->execute();
    }

    public static function cambiarPassword($id_usuario, $password_actual, $password_nueva) {
        $conn = Database::getConnection();
        
        $query = "SELECT contrasena FROM usuario WHERE id_usuario = :id_usuario";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(":id_usuario", $id_usuario);
        $stmt->execute();
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$usuario || !password_verify($password_actual, $usuario['contrasena'])) {
            return false;
        }
        
        $password_hash = password_hash($password_nueva, PASSWORD_DEFAULT);
        $query = "UPDATE usuario SET contrasena = :contrasena WHERE id_usuario = :id_usuario";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(":contrasena", $password_hash);
        $stmt->bindParam(":id_usuario", $id_usuario);
        return $stmt->execute();
    }

    public static function contarPorRol($id_rol) {
        $conn = Database::getConnection();
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM usuario WHERE id_rol = ?");
        $stmt->execute([$id_rol]);
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }

    public static function actualizarDatosCompletos($id_usuario, $data) {
        $conn = Database::getConnection();
        
        try {
            $stmt = $conn->prepare("SELECT id_persona FROM usuario WHERE id_usuario = ?");
            $stmt->execute([$id_usuario]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$usuario) {
                return ['success' => false, 'message' => 'Usuario no encontrado'];
            }
            
            $id_persona = $usuario['id_persona'];
            
            $stmt = $conn->prepare("UPDATE persona SET nombre = ?, apellido = ?, documento = ?, telefono = ?, direccion = ?, fecha_nacimiento = ?, email = ? WHERE id_persona = ?");
            $stmt->execute([$data['nombre'], $data['apellido'], $data['documento'], $data['telefono'], $data['direccion'], $data['fecha_nacimiento'], $data['email'], $id_persona]);
            
            if (!empty($data['email'])) {
                $stmt = $conn->prepare("UPDATE usuario SET nombre_usuario = ? WHERE id_usuario = ?");
                $stmt->execute([$data['email'], $id_usuario]);
            }
            
            $stmt = $conn->prepare("UPDATE paciente SET grupo_sanguineo = ?, num_seguro = ? WHERE id_persona = ?");
            $stmt->execute([$data['grupo_sanguineo'], $data['num_seguro'], $id_persona]);
            
            $_SESSION['nombre'] = $data['nombre'];
            $_SESSION['apellido'] = $data['apellido'];
            $_SESSION['user']['nombre'] = $data['nombre'];
            $_SESSION['user']['apellido'] = $data['apellido'];
            
            return ['success' => true, 'message' => 'Datos actualizados'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}