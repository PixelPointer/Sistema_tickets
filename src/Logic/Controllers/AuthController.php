<?php
require_once dirname(__DIR__, 3) . '/config/database.php';
require_once dirname(__DIR__) . '/Models/Persona.php';
require_once dirname(__DIR__) . '/Models/Usuario.php';
require_once dirname(__DIR__) . '/Models/Paciente.php';

class AuthController {

    public function register($data) {
        $errors = [];
        if (empty($data['nombre'])) $errors[] = 'El nombre es requerido';
        if (empty($data['apellido'])) $errors[] = 'El apellido es requerido';
        if (empty($data['documento'])) $errors[] = 'El documento es requerido';
        if (empty($data['fecha_nacimiento'])) $errors[] = 'La fecha de nacimiento es requerida';
        if (empty($data['email'])) $errors[] = 'El correo electrónico es requerido';
        if (empty($data['password'])) $errors[] = 'La contraseña es requerida';

        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'El formato del correo electrónico no es válido';
        }

        if (!empty($data['password']) && strlen($data['password']) < 6) {
            $errors[] = 'La contraseña debe tener al menos 6 caracteres';
        }

        if ($data['password'] !== $data['confirm_password']) {
            $errors[] = 'Las contraseñas no coinciden';
        }

        if (!empty($errors)) {
            return [
                'success' => false,
                'message' => implode('. ', $errors)
            ];
        }

        $existePersona = Persona::buscarPorDocumento($data['documento']);
        if ($existePersona) {
            return [
                'success' => false,
                'message' => 'Ya existe una persona registrada con este documento'
            ];
        }

        $existeEmail = Persona::buscarPorEmail($data['email']);
        if ($existeEmail) {
            return [
                'success' => false,
                'message' => 'Este correo electrónico ya está registrado'
            ];
        }

        $persona = new Persona();
        $persona->nombre = $data['nombre'];
        $persona->apellido = $data['apellido'];
        $persona->documento = $data['documento'];
        $persona->telefono = $data['telefono'] ?? '';
        $persona->direccion = $data['direccion'] ?? '';
        $persona->fecha_nacimiento = $data['fecha_nacimiento'];
        $persona->email = $data['email'];

        if (!$persona->crear()) {
            return [
                'success' => false,
                'message' => 'Error al crear la persona'
            ];
        }

        $usuario = new Usuario();
        $usuario->id_persona = $persona->id_persona;
        $usuario->id_rol = 3;
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
            $_SESSION['user_id'] = $resultado['id_usuario'];
            $_SESSION['id_usuario'] = $resultado['id_usuario'];
            $_SESSION['id_persona'] = $resultado['id_persona'];
            $_SESSION['id_rol'] = $resultado['id_rol'];
            $_SESSION['nombre_usuario'] = $resultado['nombre_usuario'];
            $_SESSION['nombre'] = $persona['nombre'];
            $_SESSION['apellido'] = $persona['apellido'];
            $_SESSION['user'] = [
                'id' => $resultado['id_usuario'],
                'nombre' => $persona['nombre'],
                'apellido' => $persona['apellido']
            ];

            if (isset($_POST['remember'])) {
                $id = $resultado['id_usuario'];
                $cookieValue = $id . ':' . hash('sha256', $id . 'Q-Line-SECRET-2026');
                setcookie('remember_token', $cookieValue, time() + 86400 * 30, '/');
            }

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
        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', time() - 3600, '/');
        }
        return [
            'success' => true,
            'message' => 'Sesión cerrada'
        ];
    }

    public static function restoreFromCookie() {
        if (isset($_COOKIE['remember_token']) && !isset($_SESSION['id_usuario'])) {
            $parts = explode(':', $_COOKIE['remember_token'], 2);
            if (count($parts) === 2) {
                $id_usuario = intval($parts[0]);
                $hash = $parts[1];
                if (hash_equals(hash('sha256', $id_usuario . 'Q-Line-SECRET-2026'), $hash)) {
                    require_once dirname(__DIR__) . '/Models/Usuario.php';
                    require_once dirname(__DIR__) . '/Models/Persona.php';
                    $usuario = Usuario::buscarPorId($id_usuario);
                    if ($usuario) {
                        $persona = Persona::buscarPorId($usuario['id_persona']);
                        if ($persona) {
                            $_SESSION['user_id'] = $usuario['id_usuario'];
                            $_SESSION['id_usuario'] = $usuario['id_usuario'];
                            $_SESSION['id_persona'] = $usuario['id_persona'];
                            $_SESSION['id_rol'] = $usuario['id_rol'];
                            $_SESSION['nombre_usuario'] = $usuario['nombre_usuario'];
                            $_SESSION['nombre'] = $persona['nombre'];
                            $_SESSION['apellido'] = $persona['apellido'];
                            $_SESSION['user'] = [
                                'id' => $usuario['id_usuario'],
                                'nombre' => $persona['nombre'],
                                'apellido' => $persona['apellido']
                            ];
                        }
                    }
                }
            }
        }
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