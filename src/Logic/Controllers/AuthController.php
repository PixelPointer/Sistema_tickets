<?php
require_once dirname(__DIR__, 3) . '/config/database.php';
require_once dirname(__DIR__) . '/Models/Persona.php';
require_once dirname(__DIR__) . '/Models/Usuario.php';
require_once dirname(__DIR__) . '/Models/Paciente.php';

class AuthController {

    public function register($data) {
        if ($data['password'] !== $data['confirm_password']) {
            return [
                'success' => false,
                'message' => 'Las contraseñas no coinciden'
            ];
        }

        $persona = new Persona();
        
        $existePersona = $persona->buscarPorDocumento($data['documento']);
        if ($existePersona) {
            return [
                'success' => false,
                'message' => 'Ya existe una persona registrada con este documento'
            ];
        }

        $persona->nombre = $data['nombre'];
        $persona->apellido = $data['apellido'];
        $persona->documento = $data['documento'];
        $persona->telefono = $data['telefono'] ?? '';
        $persona->direccion = $data['direccion'] ?? '';
        $persona->fecha_nacimiento = $data['fecha_nacimiento'];

        if (!$persona->crear()) {
            return [
                'success' => false,
                'message' => 'Error al crear la persona'
            ];
        }

        $rolId = 1;
        
        $rolConn = Database::getConnection();
        $rolStmt = $rolConn->query("SELECT id_rol FROM rol LIMIT 1");
        $rolData = $rolStmt->fetch(PDO::FETCH_ASSOC);
        if ($rolData) {
            $rolId = $rolData['id_rol'];
        }

        $usuario = new Usuario();
        $usuario->id_persona = $persona->id_persona;
        $usuario->id_rol = $rolId;
        $usuario->nombre_usuario = $data['email'];
        $usuario->contrasena = $data['password'];
        $usuario->estado = 'Activo';

        try {
            if (!$usuario->crear()) {
                return [
                    'success' => false,
                    'message' => 'Error al crear el usuario'
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al crear el usuario: ' . $e->getMessage()
            ];
        }

        $paciente = new Paciente();
        $paciente->id_persona = $persona->id_persona;
        $paciente->grupo_sanguineo = '';
        $paciente->num_seguro = '';

        if (!$paciente->crear()) {
            return [
                'success' => false,
                'message' => 'Error al crear el paciente'
            ];
        }

        return [
            'success' => true,
            'message' => 'Registro exitoso',
            'data' => [
                'id_persona' => $persona->id_persona,
                'nombre' => $persona->nombre,
                'apellido' => $persona->apellido
            ]
        ];
    }

    public function login($email, $password) {
        $usuario = new Usuario();
        $resultado = $usuario->verificarLogin($email, $password);

        if ($resultado) {
            $persona = Persona::buscarPorId($resultado['id_persona']);
            
            session_start();
            $_SESSION['id_usuario'] = $resultado['id_usuario'];
            $_SESSION['id_persona'] = $resultado['id_persona'];
            $_SESSION['id_rol'] = $resultado['id_rol'];
            $_SESSION['nombre_usuario'] = $resultado['nombre_usuario'];
            $_SESSION['nombre'] = $persona['nombre'];
            $_SESSION['apellido'] = $persona['apellido'];

            return [
                'success' => true,
                'message' => 'Login exitoso',
                'data' => [
                    'id_usuario' => $resultado['id_usuario'],
                    'nombre' => $persona['nombre'],
                    'apellido' => $persona['apellido'],
                    'rol' => $resultado['id_rol']
                ]
            ];
        }

        return [
            'success' => false,
            'message' => 'Credenciales inválidas'
        ];
    }

    public function logout() {
        session_start();
        session_destroy();
        return [
            'success' => true,
            'message' => 'Sesión cerrada'
        ];
    }

    public function isLoggedIn() {
        session_start();
        return isset($_SESSION['id_usuario']);
    }

    public function getUser() {
        session_start();
        if (isset($_SESSION['id_usuario'])) {
            return [
                'id_usuario' => $_SESSION['id_usuario'],
                'id_persona' => $_SESSION['id_persona'],
                'nombre' => $_SESSION['nombre'],
                'apellido' => $_SESSION['apellido'],
                'rol' => $_SESSION['id_rol']
            ];
        }
        return null;
    }
}