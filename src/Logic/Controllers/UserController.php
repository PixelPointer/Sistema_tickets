<?php
require_once dirname(__DIR__) . '/Models/Usuario.php';
require_once dirname(__DIR__) . '/Models/Paciente.php';

// Controlador de Usuarios - gestión de usuarios, roles, perfil y contraseñas
class UserController {

    public function listUsers($limit = 0, $offset = 0) {
        return Usuario::listarTodos($limit, $offset);
    }

    public function buscarUsuarios($search, $limit = 0, $offset = 0) {
        if (empty(trim($search))) {
            return Usuario::listarTodos($limit, $offset);
        }
        return Usuario::buscarUsuarios($search, $limit, $offset);
    }

    public function contarUsuariosBusqueda($search) {
        if (empty(trim($search))) {
            return Usuario::contarTodos();
        }
        return Usuario::contarUsuariosBusqueda($search);
    }

    public function contarUsuarios() {
        return Usuario::contarTodos();
    }

    public function listRoles() {
        return Usuario::listarRoles();
    }

    // Cambiar rol de usuario (solo admin)
    public function updateRole($id_usuario, $id_rol) {
        if (Usuario::actualizarRol($id_usuario, $id_rol)) {
            return ['success' => true, 'message' => 'Rol actualizado correctamente'];
        }
        return ['success' => false, 'message' => 'Error al actualizar el rol'];
    }

    // Actualizar nombre y apellido del perfil
    public function actualizarPerfil($id_usuario, $nombre, $apellido) {
        if (empty($nombre) || empty($apellido)) {
            return ['success' => false, 'message' => 'Nombre y apellido son requeridos'];
        }
        if (Usuario::actualizarPerfil($id_usuario, $nombre, $apellido)) {
            $_SESSION['nombre'] = $nombre;
            $_SESSION['apellido'] = $apellido;
            $_SESSION['user']['nombre'] = $nombre;
            $_SESSION['user']['apellido'] = $apellido;
            return ['success' => true, 'message' => 'Perfil actualizado'];
        }
        return ['success' => false, 'message' => 'Error al actualizar'];
    }

    // Cambiar contraseña con validaciones
    public function cambiarPassword($id_usuario, $password_actual, $password_nueva, $password_confirmar) {
        if ($password_nueva !== $password_confirmar) {
            return ['success' => false, 'message' => 'Las contraseñas no coinciden'];
        }
        
        if (strlen($password_nueva) < 6) {
            return ['success' => false, 'message' => 'La contraseña debe tener al menos 6 caracteres'];
        }
        
        if (Usuario::cambiarPassword($id_usuario, $password_actual, $password_nueva)) {
            return ['success' => true, 'message' => 'Contraseña cambiada correctamente'];
        }
        return ['success' => false, 'message' => 'Contraseña actual incorrecta'];
    }

    // Obtener datos completos del paciente (persona + paciente)
    public function obtenerDatosPaciente($id_usuario) {
        return Paciente::buscarPorIdUsuario($id_usuario);
    }

    // Listar próximas citas del paciente
    public function listarCitasPaciente($id_usuario) {
        require_once dirname(__DIR__) . '/Models/Atencion.php';
        $pac = Paciente::buscarPorIdUsuario($id_usuario);
        if ($pac) {
            return Atencion::listarProximasCitas($pac['id_paciente']);
        }
        return [];
    }

    // Listar historial de atenciones del paciente
    public function listarHistorialAtenciones($id_usuario) {
        require_once dirname(__DIR__) . '/Models/Atencion.php';
        require_once dirname(__DIR__) . '/Models/Paciente.php';
        $pac = Paciente::buscarPorIdUsuario($id_usuario);
        if ($pac) {
            return Atencion::listarHistorialCompleto($pac['id_paciente']);
        }
        return [];
    }

    public function contarUsuariosPorRol($id_rol) {
        require_once dirname(__DIR__) . '/Models/Usuario.php';
        return Usuario::contarPorRol($id_rol);
    }

    // Actualizar todos los datos: persona + paciente + email de usuario
    public function actualizarDatosCompletos($id_usuario, $data) {
        $errors = [];
        if (empty($data['nombre'])) $errors[] = 'El nombre es requerido';
        if (empty($data['apellido'])) $errors[] = 'El apellido es requerido';
        if (empty($data['documento'])) $errors[] = 'El documento es requerido';
        if (empty($data['fecha_nacimiento'])) $errors[] = 'La fecha de nacimiento es requerida';

        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'El formato del correo electrónico no es válido';
        }

        if (!empty($errors)) {
            return ['success' => false, 'message' => implode('. ', $errors)];
        }

        $result = Usuario::actualizarDatosCompletos($id_usuario, $data);
        return $result;
    }
}