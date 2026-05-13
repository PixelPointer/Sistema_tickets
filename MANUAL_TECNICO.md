# MANUAL TÉCNICO — Q-Line
## Sistema de Gestión de Turnos y Colas Médicas

**Versión:** 1.0 | **Fecha:** 2026-05-13 | **Plataforma:** PHP 8.2 + MariaDB 10.4

---

## 1. DESCRIPCIÓN GENERAL

Q-Line es un sistema web para gestión de turnos médicos con arquitectura de 3 capas, desarrollado en PHP nativo + MySQL/MariaDB. No utiliza frameworks.

**Características principales:**
- Registro y autenticación por rol (Admin, Médico, Paciente)
- Solicitud digital de turnos con priorización (Roja, Amarilla, Verde)
- Generación automática de códigos únicos por especialidad (ej: MED-001)
- Cola en tiempo real con polling cada 15 segundos
- Modelo matemático M/M/1 para análisis de tiempos de espera
- Monitor público de sala de espera sin autenticación
- Historial clínico completo y reportes diarios
- Notificaciones en navegador (Browser Notifications API)

---

## 2. ARQUITECTURA

### 2.1 Diagrama de 3 Capas

```
┌─────────────────────────────────────────────────┐
│           CAPA DE PRESENTACIÓN                  │
│  Vistas PHP + CSS + JS                          │
├─────────────────────────────────────────────────┤
│           CAPA DE LÓGICA                        │
│  Controllers | Models | Helpers                 │
├─────────────────────────────────────────────────┤
│           CAPA DE DATOS                         │
│  MySQL/MariaDB — PDO (Singleton)                │
│  11 tablas con relaciones FK                    │
└─────────────────────────────────────────────────┘
```

### 2.2 Patrón Front Controller

Todo tráfico HTTP pasa por `public/index.php`:
- **Router**: Determina la vista según `?route=`
- **Dispatcher**: Ejecuta acciones POST y redirige
- **API Gateway**: Maneja `?route=api` y `?route=api_monitor`

---

## 3. ENTORNO TECNOLÓGICO

### 3.1 Tecnologías

| Tecnología | Versión | Uso |
|-----------|---------|-----|
| PHP | 8.2 | Lógica servidor |
| MySQL/MariaDB | 10.4+ | Base de datos |
| PDO | Nativa | Acceso seguro a BD |
| CSS3 | — | Estilos |
| JavaScript ES6+ | — | Polling, notificaciones |
| Git | — | Control de versiones |
| Apache (XAMPP) | — | Servidor web |

---

## 4. ESTRUCTURA DEL PROYECTO

```
Sistema_tickets/
├── MANUAL_TECNICO.md
├── README.md
├── config/database.php
├── public/index.php
├── public/css/ (6 archivos CSS)
├── src/Data/sistema_tickets.sql
├── src/Logic/Controllers/ (7 controladores)
├── src/Logic/Models/ (9 modelos)
├── src/Logic/Helpers/UIHelper.php
└── src/Presentation/ (layout + 11 vistas)
```

---

## 5. MODELO DE DATOS

### 5.1 Tablas (11 total)

**`rol`** (3 registros semilla): 1=admin, 2=medico, 3=paciente

**`persona`**: nombre, apellido, documento(UNIQUE), telefono, direccion, fecha_nacimiento, email

**`usuario`**: nombre_usuario(UNIQUE), contrasena(bcrypt), estado, FK persona, FK rol

**`paciente`**: grupo_sanguineo, num_seguro, FK persona

**`personal`**: matricula_profesional(UNIQUE), limite_diario, FK persona, FK especialidad

**`especialidad`**: nombre_especialidad (10 especialidades)

**`consultorio`**: numero_consultorio, piso, FK especialidad

**`horario`**: dia_semana(ENUM), hora_inicio, hora_fin

**`personal_horario`**: PK(id_personal, id_horario)

**`ticket`**: codigo_ticket, prioridad(ENUM), estado(ENUM), tiempo_estimado_espera, motivo_consulta, FK paciente, FK consultorio

**`atencion`**: tipo_diagnostico, descripcion_diagnostico, tratamiento_prescrito, hora_inicio_real, fecha_proxima_cita, FK ticket(UNIQUE), FK personal

### 5.2 Flujo de Estados del Ticket
```
Espera → Llamado → Atendido
Espera → Ausente (cancelación)
```

---

## 6. CONFIGURACIÓN

### 6.1 `config/database.php`

```php
<?php
date_default_timezone_set('America/La_Paz');

class Database {
    private static $host = "localhost";
    private static $db_name = "sistema_tickets";
    private static $username = "root";
    private static $password = "";
    private static $conn = null;

    /*
     * Patrón Singleton: retorna una única conexión PDO
     * Modo de errores: ERRMODE_EXCEPTION
     * Codificación: UTF-8
     */
    public static function getConnection() {
        if (self::$conn === null) {
            try {
                self::$conn = new PDO(
                    "mysql:host=" . self::$host . ";dbname=" . self::$db_name,
                    self::$username,
                    self::$password
                );
                self::$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::$conn->exec("set names utf8");
            } catch(PDOException $exception) {
                die("Error de conexión: " . $exception->getMessage());
            }
        }
        return self::$conn;
    }
}
```

**Parámetros configurables:** `$host`, `$db_name`, `$username`, `$password`, zona horaria.

---

## 7. REQUISITOS FUNCIONALES — CÓDIGO FUENTE DOCUMENTADO

### RF 01: Inicio de Sesión por Tipo de Usuario

**Archivos:** `AuthController.php`, `Usuario.php`, `index.php`

**Modelo Usuario — Verificación Login** (`Usuario.php:59-66`):
```php
/**
 * RF 1: Verifica credenciales. Busca usuario por email
 * y verifica hash bcrypt de la contraseña.
 */
public function verificarLogin($nombre_usuario, $contrasena) {
    $usuario = $this->buscarPorUsuario($nombre_usuario);
    if ($usuario && password_verify($contrasena, $usuario['contrasena'])) {
        return $usuario;
    }
    return false;
}

/** Búsqueda por email con sentencia preparada anti-SQLi */
public function buscarPorUsuario($nombre_usuario) {
    $query = "SELECT * FROM usuario WHERE nombre_usuario = :nombre_usuario";
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(":nombre_usuario", $nombre_usuario);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
```

**Controlador AuthController** (`AuthController.php:121-164`):
```php
/**
 * RF 1: Inicio de sesión con persistencia y cookie "Recordarme".
 * Almacena datos del usuario en $_SESSION.
 * Crea cookie firmada con SHA-256 si se marca "Recordarme".
 */
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

        return ['success' => true, 'message' => 'Login exitoso',
                'data' => ['id_usuario' => $resultado['id_usuario'],
                           'nombre' => $persona['nombre'],
                           'apellido' => $persona['apellido'],
                           'rol' => $resultado['id_rol']]];
    }
    return ['success' => false, 'message' => 'Credenciales inválidas'];
}
```

**Front Controller — Routing** (`index.php:114-136`):
```php
if ($route === 'login') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $result = $auth->login($email, $password);
    
    if ($result['success']) {
        $rol = $result['data']['rol'] ?? 3;
        $dashboardRoute = match($rol) {
            1 => 'dashboard_admin',
            2 => 'dashboard_medico',
            default => 'dashboard_paciente'
        };
        ob_end_clean();
        echo '<!DOCTYPE html><html><head>
        <meta http-equiv="refresh" content="0;url=?route=' . $dashboardRoute . '">
        </head></html>';
        exit;
    } else {
        $message = $result['message'];
        $messageType = 'error';
    }
}
```

---

### RF 02: Registro de Usuarios

**Archivos:** `AuthController.php`, `Persona.php`, `Usuario.php`, `Paciente.php`, `PersonalController.php`

**Controlador AuthController — Registro Paciente** (`AuthController.php:14-116`):
```php
/**
 * RF 2: Registrar nuevo paciente (rol=3).
 * Crea en secuencia: Persona → Usuario → Paciente.
 * Médicos/Admin se crean desde panel admin.
 */
public function register($data) {
    // VALIDACIONES
    $errors = [];
    if (empty($data['nombre'])) $errors[] = 'El nombre es requerido';
    if (empty($data['apellido'])) $errors[] = 'El apellido es requerido';
    if (empty($data['documento'])) $errors[] = 'El documento es requerido';
    if (empty($data['fecha_nacimiento'])) $errors[] = 'La fecha de nacimiento es requerida';
    if (empty($data['email'])) $errors[] = 'El correo electrónico es requerido';
    if (empty($data['password'])) $errors[] = 'La contraseña es requerida';
    if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL))
        $errors[] = 'Formato de correo no válido';
    if (!empty($data['password']) && strlen($data['password']) < 6)
        $errors[] = 'La contraseña debe tener al menos 6 caracteres';
    if ($data['password'] !== $data['confirm_password'])
        $errors[] = 'Las contraseñas no coinciden';
    if (!empty($errors))
        return ['success' => false, 'message' => implode('. ', $errors)];

    // VERIFICAR UNICIDAD
    if (Persona::buscarPorDocumento($data['documento']))
        return ['success' => false, 'message' => 'Documento ya registrado'];
    if (Persona::buscarPorEmail($data['email']))
        return ['success' => false, 'message' => 'Email ya registrado'];

    // PASO 1: Crear Persona
    $persona = new Persona();
    $persona->nombre = $data['nombre'];
    $persona->apellido = $data['apellido'];
    $persona->documento = $data['documento'];
    $persona->telefono = $data['telefono'] ?? '';
    $persona->direccion = $data['direccion'] ?? '';
    $persona->fecha_nacimiento = $data['fecha_nacimiento'];
    $persona->email = $data['email'];
    if (!$persona->crear())
        return ['success' => false, 'message' => 'Error al crear la persona'];

    // PASO 2: Crear Usuario (bcrypt)
    $usuario = new Usuario();
    $usuario->id_persona = $persona->id_persona;
    $usuario->id_rol = 3;
    $usuario->nombre_usuario = $data['email'];
    $usuario->contrasena = $data['password'];
    $usuario->estado = 'Activo';
    try {
        if (!$usuario->crear())
            return ['success' => false, 'message' => 'Error al crear el usuario'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error DB: ' . $e->getMessage()];
    }

    // PASO 3: Crear Paciente
    $paciente = new Paciente();
    $paciente->id_persona = $persona->id_persona;
    $paciente->grupo_sanguineo = '';
    $paciente->num_seguro = '';
    if (!$paciente->crear())
        return ['success' => false, 'message' => 'Error al crear el paciente'];

    return ['success' => true, 'message' => 'Registro exitoso',
            'data' => ['id_persona' => $persona->id_persona,
                       'nombre' => $persona->nombre, 'apellido' => $persona->apellido]];
}
```

**Modelo Persona — Creación y validaciones** (`Persona.php`):
```php
/** Insertar nueva persona */
public function crear() {
    $query = "INSERT INTO persona (nombre, apellido, documento, telefono, direccion, fecha_nacimiento, email) 
              VALUES(:nombre, :apellido, :documento, :telefono, :direccion, :fecha_nacimiento, :email)";
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

/** Validar unicidad por documento */
public static function buscarPorDocumento($documento) {
    $conn = Database::getConnection();
    $stmt = $conn->prepare("SELECT * FROM persona WHERE documento = :documento");
    $stmt->bindParam(":documento", $documento);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/** Validar unicidad por email */
public static function buscarPorEmail($email) {
    $conn = Database::getConnection();
    $stmt = $conn->prepare("SELECT * FROM persona WHERE email = :email");
    $stmt->bindParam(":email", $email);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
```

**Modelo Usuario — Creación con bcrypt** (`Usuario.php:22-47`):
```php
/**
 * Crear usuario con contraseña hasheada bcrypt (PASSWORD_DEFAULT).
 * Lanza excepción PDOException en caso de error.
 */
public function crear() {
    try {
        $query = "INSERT INTO usuario (id_persona, id_rol, nombre_usuario, contrasena, estado) 
                  VALUES(:id_persona, :id_rol, :nombre_usuario, :contrasena, :estado)";
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
```

**Restauración de Sesión por Cookie** (`AuthController.php:180-209`):
```php
/**
 * Restaurar sesión automática desde cookie "Recordarme".
 * Token = id_usuario:SHA256(id_usuario + secreto)
 * Se ejecuta al inicio de index.php.
 */
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
                        $_SESSION['user'] = ['id' => $usuario['id_usuario'],
                                            'nombre' => $persona['nombre'],
                                            'apellido' => $persona['apellido']];
                    }
                }
            }
        }
    }
}
```

---

### RF 03: Gestión de Roles y Permisos

**Tabla de Permisos por Rol:**

| Permiso | Admin(1) | Médico(2) | Paciente(3) |
|---------|----------|-----------|-------------|
| Dashboard | ✅ | ✅ | ✅ |
| CRUD usuarios | ✅ | ❌ | ❌ |
| CRUD especialidades | ✅ | ❌ | ❌ |
| CRUD consultorios | ✅ | ❌ | ❌ |
| CRUD personal | ✅ | ❌ | ❌ |
| CRUD horarios | ✅ | ❌ | ❌ |
| Solicitar turno | ❌ | ❌ | ✅ |
| Lista de espera | ❌ | ✅ | ❌ |
| Llamar paciente | ❌ | ✅ | ❌ |
| Registrar atención | ❌ | ✅ | ❌ |
| Historial pacientes | ❌ | ✅ | ❌ |
| Mi historial | ❌ | ❌ | ✅ |
| Cancelar turno | ❌ | ❌ | ✅ |
| Reportes diarios | ✅ | ✅ | ❌ |
| Modelo M/M/1 | ❌ | ✅ | ❌ |

**Modelo Rol** (`Usuario.php:142-148`):
```php
public static function listarRoles() {
    $conn = Database::getConnection();
    $stmt = $conn->prepare("SELECT * FROM rol ORDER BY id_rol");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
```

**Cambio de Rol** (`Usuario.php:133-140`):
```php
public static function actualizarRol($id_usuario, $id_rol) {
    $conn = Database::getConnection();
    $query = "UPDATE usuario SET id_rol = :id_rol WHERE id_usuario = :id_usuario";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(":id_rol", $id_rol);
    $stmt->bindParam(":id_usuario", $id_usuario);
    return $stmt->execute();
}
```

**Verificación de rol en vistas** (`dashboard_medico.php:11-13`):
```php
if (!$user || $user['rol'] != 2) {
    header('Location: ?route=login');
    exit;
}
```

---

### RF 04: Solicitud Digital de Fichas

**Archivos:** `TicketController.php`, `Ticket.php`, `Consultorio.php`, `Horario.php`

**Controlador TicketController** (`TicketController.php:50-119`):
```php
/**
 * RF 4-5-6-21: Solicitar nuevo ticket de turno.
 * Validaciones: 1 ticket/día, consultorios disponibles, horario activo.
 * Genera código único y calcula tiempo estimado.
 */
public function solicitarTicket($id_usuario, $id_especialidad, $prioridad = 'Verde', $sintomas = '') {
    $paciente = Paciente::buscarPorIdUsuario($id_usuario);
    if (!$paciente)
        return ['success' => false, 'message' => 'Paciente no encontrado'];

    // RF 8: Límite de 1 ticket activo/día
    if (Ticket::tieneTicketHoy($paciente['id_paciente']))
        return ['success' => false, 'message' => 'Ya tiene un turno activo hoy.'];

    $consultorios = Consultorio::listarPorEspecialidad($id_especialidad);
    if (empty($consultorios))
        return ['success' => false, 'message' => 'No hay consultorios disponibles.'];

    // RF 21: Verificar disponibilidad horaria
    if (!Horario::verificarDisponibilidad($id_especialidad)) {
        return ['success' => false, 'message' => 'No hay atención disponible en este momento.'];
    }

    $id_consultorio = $consultorios[0]['id_consultorio'];
    $codigo = Ticket::generarCodigo($id_especialidad);  // RF 5
    $tiempo_espera = Ticket::estimarTiempoEspera($id_consultorio);  // RF 16

    $ticket = new Ticket();
    $ticket->id_paciente = $paciente['id_paciente'];
    $ticket->id_consultorio = $id_consultorio;
    $ticket->codigo_ticket = $codigo;
    $ticket->prioridad = $prioridad;
    $ticket->estado = 'Espera';
    $ticket->tiempo_estimado_espera = $tiempo_espera;
    $ticket->motivo_consulta = $sintomas;

    if ($ticket->crear()) {
        $info = Ticket::obtenerPorId($ticket->id_ticket);
        $personas_antes = Ticket::contarAntes($id_consultorio, $ticket->id_ticket);
        return ['success' => true, 'message' => 'Turno generado exitosamente',
                'data' => ['ticket' => $info, 'personas_antes' => $personas_antes]];
    }
    return ['success' => false, 'message' => 'Error al generar el turno'];
}
```

**Modelo Ticket — Crear** (`Ticket.php:25-44`):
```php
/**
 * RF 4: Insertar nuevo ticket en BD con estado 'Espera'.
 */
public function crear() {
    $query = "INSERT INTO ticket (id_paciente, id_consultorio, codigo_ticket, prioridad, estado, tiempo_estimado_espera, motivo_consulta) 
              VALUES(:id_paciente, :id_consultorio, :codigo_ticket, :prioridad, :estado, :tiempo_estimado_espera, :motivo_consulta)";
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(":id_paciente", $this->id_paciente);
    $stmt->bindParam(":id_consultorio", $this->id_consultorio);
    $stmt->bindParam(":codigo_ticket", $this->codigo_ticket);
    $stmt->bindParam(":prioridad", $this->prioridad);
    $stmt->bindParam(":estado", $this->estado);
    $stmt->bindParam(":tiempo_estimado_espera", $this->tiempo_estimado_espera);
    $stmt->bindParam(":motivo_consulta", $this->motivo_consulta);
    if ($stmt->execute()) {
        $this->id_ticket = $this->conn->lastInsertId();
        return true;
    }
    return false;
}
```

---

### RF 05: Generación de Número de Turno Único

**Formato:** `[ABREVIATURA]-[NNN]` ej: `MED-001`, `TRA-005`

**Algoritmo:**
1. Extraer primeras letras de cada palabra del nombre de la especialidad (máximo 3)
2. Contar tickets del día actual para esa especialidad
3. Incrementar en 1 y rellenar con ceros a la izquierda (001, 002…)

**Código** (`Ticket.php:48-75`):
```php
/**
 * RF 5: Generar código único por especialidad con reinicio diario.
 * Formato: MED-001, PED-003, CAR-012
 * Reinicia la secuencia cada día usando CURDATE().
 */
public static function generarCodigo($id_especialidad) {
    $conn = Database::getConnection();

    // Obtener abreviatura de 3 letras desde el nombre de la especialidad
    $stmt = $conn->prepare("SELECT nombre_especialidad FROM especialidad WHERE id_especialidad = ?");
    $stmt->execute([$id_especialidad]);
    $esp = $stmt->fetch(PDO::FETCH_ASSOC);

    $abbr = 'TKT'; // Valor por defecto
    if ($esp) {
        $palabras = explode(' ', $esp['nombre_especialidad']);
        $abbr = '';
        foreach ($palabras as $p) {
            $abbr .= strtoupper(substr($p, 0, 1));
        }
        $abbr = substr($abbr, 0, 3);
    }

    // Contar tickets de HOY para esta especialidad
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM ticket t 
                            INNER JOIN consultorio c ON t.id_consultorio = c.id_consultorio
                            WHERE DATE(t.fecha_creacion) = CURDATE() AND c.id_especialidad = ?");
    $stmt->execute([$id_especialidad]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $seq = str_pad($row['total'] + 1, 3, '0', STR_PAD_LEFT);

    return $abbr . '-' . $seq;
}
```

**Ejemplos:**
- "Medicina General" → "MED", 23 tickets hoy → `MED-024`
- "Traumatología" → "TRA", 0 tickets hoy → `TRA-001`
- "Pediatría" → "PED", 9 tickets hoy → `PED-010`

---

### RF 06: Posición del Paciente en Cola Tiempo Real

**Mecanismo:** Polling AJAX cada 15 segundos vía endpoint `?route=api`

**Front Controller** (`index.php:19-51`):
```php
// RF 19: API endpoint para polling - estado del ticket
if ($route === 'api') {
    header('Content-Type: application/json');
    ob_end_clean();
    require_once LOGIC_PATH . '/Models/Ticket.php';

    $action = $_GET['action'] ?? '';
    $response = ['success' => false, 'message' => 'Acción no válida'];

    if ($action === 'check_ticket' && isset($_GET['id_ticket'])) {
        $ticket = Ticket::obtenerPorId(intval($_GET['id_ticket']));
        if ($ticket) {
            $personas_antes = Ticket::contarAntes($ticket['id_consultorio'], $ticket['id_ticket']);
            $hora_estimada = $personas_antes * 10;
            $response = [
                'success' => true,
                'id_ticket' => $ticket['id_ticket'],
                'codigo_ticket' => $ticket['codigo_ticket'],
                'estado' => $ticket['estado'],
                'personas_antes' => intval($personas_antes),
                'tiempo_estimado' => intval($ticket['tiempo_estimado_espera']),
                'hora_estimada_minutos' => $hora_estimada,
                'consultorio' => $ticket['numero_consultorio'],
                'piso' => $ticket['piso']
            ];
        } else {
            $response = ['success' => false, 'message' => 'Ticket no encontrado'];
        }
    }
    echo json_encode($response);
    exit;
}
```

**Modelo Ticket — Contar Personas Antes** (`Ticket.php:78-84`):
```php
/**
 * RF 6: Contar personas antes en la cola (mismo consultorio).
 * Cuenta tickets en estado 'Espera' con ID menor al ticket actual.
 * @return int Cantidad de personas antes
 */
public static function contarAntes($id_consultorio, $id_ticket) {
    $conn = Database::getConnection();
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM ticket 
                            WHERE id_consultorio = ? AND estado = 'Espera' AND id_ticket < ?");
    $stmt->execute([$id_consultorio, $id_ticket]);
    return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
}
```

**Modelo Ticket — Obtener Ticket Completo** (`Ticket.php:97-110`):
```php
/**
 * Obtener datos completos de un ticket con todas las relaciones JOIN.
 * Une ticket → consultorio → especialidad → paciente → persona.
 */
public static function obtenerPorId($id_ticket) {
    $conn = Database::getConnection();
    $query = "SELECT t.*, c.numero_consultorio, c.piso, e.nombre_especialidad,
                     per.nombre as paciente_nombre, per.apellido as paciente_apellido
              FROM ticket t
              INNER JOIN consultorio c ON t.id_consultorio = c.id_consultorio
              INNER JOIN especialidad e ON c.id_especialidad = e.id_especialidad
              INNER JOIN paciente p ON t.id_paciente = p.id_paciente
              INNER JOIN persona per ON p.id_persona = per.id_persona
              WHERE t.id_ticket = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$id_ticket]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
```

**JavaScript — Polling 15s** (en `ticket_confirmado.php`):
```javascript
const idTicket = sessionStorage.getItem('ultimo_ticket_id');
if (idTicket) {
    setInterval(function() {
        fetch('?route=api&action=check_ticket&id_ticket=' + idTicket)
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) {
                    document.getElementById('posicion').textContent = data.personas_antes;
                    document.getElementById('estado').textContent = data.estado;
                    document.getElementById('tiempo').textContent = data.hora_estimada_minutos + ' min';
                    document.getElementById('consultorio').textContent = data.consultorio;
                    document.getElementById('piso').textContent = data.piso;
                    document.getElementById('codigo').textContent = data.codigo_ticket;
                    
                    // RF 15: Notificar cuando estado = 'Llamado'
                    if (data.estado === 'Llamado') {
                        if (Notification.permission === 'granted') {
                            new Notification('¡Hora de atención!', {
                                body: 'Turno ' + data.codigo_ticket + 
                                      ' ha sido llamado. Consultorio ' + data.consultorio
                            });
                        }
                    }
                }
            }).catch(function(error) { console.error('Polling error:', error); });
    }, 15000);
}
```

---

### RF 07: Cancelar Fichas

**Archivos:** `TicketController.php`, `Ticket.php`

**Modelo Ticket — Cancelar** (`Ticket.php:166-178`):
```php
/**
 * RF 7: Cancelar ticket (solo estado 'Espera').
 * Verifica que el ticket pertenece al paciente y está en 'Espera'.
 * Cambia estado a 'Ausente'.
 * @return bool true si canceló exitosamente
 */
public static function cancelar($id_ticket, $id_paciente) {
    $conn = Database::getConnection();
    
    // Verificar ticket existe, pertenece al paciente y está en 'Espera'
    $stmt = $conn->prepare("SELECT * FROM ticket 
                            WHERE id_ticket = ? AND id_paciente = ? AND estado = 'Espera'");
    $stmt->execute([$id_ticket, $id_paciente]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$ticket) return false;

    // Cambiar estado a 'Ausente'
    $stmt = $conn->prepare("UPDATE ticket SET estado = 'Ausente' WHERE id_ticket = ?");
    return $stmt->execute([$id_ticket]);
}
```

**Controlador** (`TicketController.php:144-155`):
```php
public function cancelarTicket($id_usuario, $id_ticket) {
    $paciente = Paciente::buscarPorIdUsuario($id_usuario);
    if (!$paciente)
        return ['success' => false, 'message' => 'Paciente no encontrado'];

    if (Ticket::cancelar($id_ticket, $paciente['id_paciente']))
        return ['success' => true, 'message' => 'Ticket cancelado correctamente'];

    return ['success' => false, 'message' => 'No se pudo cancelar. Solo tickets en espera.'];
}
```

---

### RF 08: Límite de Fichas por Día y por Médico

**2 restricciones:**
1. Paciente: 1 ticket activo/día
2. Médico: límite diario configurable

**Modelo Ticket — Límite Paciente** (`Ticket.php:87-94`):
```php
/**
 * RF 8: Verificar si el paciente ya tiene ticket activo hoy.
 * Estados activos: 'Espera', 'Llamado'.
 */
public static function tieneTicketHoy($id_paciente) {
    $conn = Database::getConnection();
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM ticket 
                            WHERE id_paciente = ? AND DATE(fecha_creacion) = CURDATE() 
                            AND estado IN ('Espera', 'Llamado')");
    $stmt->execute([$id_paciente]);
    return $stmt->fetch(PDO::FETCH_ASSOC)['total'] > 0;
}
```

**Modelo Personal — Límite Médico** (`Personal.php`):
```php
/** Actualizar límite diario del médico (0 = sin límite) */
public static function actualizarLimiteDiario($id_personal, $limite) {
    $conn = Database::getConnection();
    $stmt = $conn->prepare("UPDATE personal SET limite_diario = ? WHERE id_personal = ?");
    return $stmt->execute([intval($limite), intval($id_personal)]);
}

/** Contar atenciones del médico hoy */
public static function contarAtendidosHoy($id_personal) {
    $conn = Database::getConnection();
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM atencion 
                            WHERE id_personal = ? AND DATE(fecha_hora_atencion) = CURDATE()");
    $stmt->execute([intval($id_personal)]);
    return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
}
```

**Controlador MedicoController** (`MedicoController.php:46-61`):
```php
/** Verificar si el médico puede atender más hoy */
public function verificarLimiteDiario() {
    if (!$this->id_personal) return false;
    $personal = Personal::buscarPorId($this->id_personal);
    if (!$personal || intval($personal['limite_diario']) <= 0) return true; // sin límite
    $atendidos = Personal::contarAtendidosHoy($this->id_personal);
    return $atendidos < intval($personal['limite_diario']);
}

/** Obtener info del límite: limite, atendidos, restantes */
public function getLimiteDiario() {
    if (!$this->id_personal) return ['limite' => 0, 'atendidos' => 0, 'restantes' => 0];
    $personal = Personal::buscarPorId($this->id_personal);
    $limite = intval($personal['limite_diario'] ?? 0);
    $atendidos = Personal::contarAtendidosHoy($this->id_personal);
    $restantes = $limite > 0 ? max(0, $limite - $atendidos) : -1;
    return ['limite' => $limite, 'atendidos' => $atendidos, 'restantes' => $restantes];
}
```

---

### RF 09: Lista de Pacientes en Espera al Médico

**Orden:** Llamados primero → Espera → Prioridad DESC (Rojo > Amarillo > Verde) → FIFO

**Modelo Ticket** (`Ticket.php:220-238`):
```php
/**
 * RF 9: Listar tickets en espera y llamados para el médico.
 * ORDER BY FIELD(estado, 'Llamado', 'Espera') → llamados primero
 * prioridad DESC → Rojo(1) > Amarillo(2) > Verde(3) (valores ENUM)
 * fecha_creacion ASC → FIFO dentro de misma prioridad
 */
public static function listarEnEspera($id_consultorios) {
    $conn = Database::getConnection();
    if (empty($id_consultorios)) return [];
    $placeholders = implode(',', array_fill(0, count($id_consultorios), '?'));
    
    $query = "SELECT t.*, c.numero_consultorio, c.piso, e.nombre_especialidad,
                     per.nombre as paciente_nombre, per.apellido as paciente_apellido,
                     per.documento, per.telefono, per.fecha_nacimiento,
                     p.grupo_sanguineo, p.num_seguro
              FROM ticket t
              INNER JOIN consultorio c ON t.id_consultorio = c.id_consultorio
              INNER JOIN especialidad e ON c.id_especialidad = e.id_especialidad
              INNER JOIN paciente p ON t.id_paciente = p.id_paciente
              INNER JOIN persona per ON p.id_persona = per.id_persona
              WHERE t.id_consultorio IN ($placeholders) AND t.estado IN ('Espera', 'Llamado')
              ORDER BY FIELD(t.estado, 'Llamado', 'Espera'), t.prioridad DESC, t.fecha_creacion ASC";
    $stmt = $conn->prepare($query);
    $stmt->execute($id_consultorios);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
```

**Controlador**: `MedicoController::listarEnEspera()` delega a `Ticket::listarEnEspera()`.

---

### RF 10: Médico Llama al Siguiente Paciente

**Archivos:** `Ticket.php`, `MedicoController.php`, `index.php`

**Modelo Ticket** (`Ticket.php:242-260`):
```php
/**
 * RF 10: Llamar al siguiente paciente en cola.
 * Selecciona el más prioritario + antiguo en 'Espera'.
 * Cambia estado a 'Llamado' y registra hora estimada de inicio.
 * @return int|null ID del ticket llamado o null si no hay espera
 */
public static function llamarSiguiente($id_consultorios) {
    $conn = Database::getConnection();
    if (empty($id_consultorios)) return null;
    $placeholders = implode(',', array_fill(0, count($id_consultorios), '?'));
    
    // Buscar el ticket más prioritario en espera
    $stmt = $conn->prepare("SELECT id_ticket FROM ticket 
                            WHERE id_consultorio IN ($placeholders) AND estado = 'Espera'
                            ORDER BY prioridad DESC, fecha_creacion ASC LIMIT 1");
    $stmt->execute($id_consultorios);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$ticket) return null;

    // Cambiar estado a 'Llamado' y registrar hora
    $stmt = $conn->prepare("UPDATE ticket SET estado = 'Llamado', hora_inicio_estimada = CURTIME() WHERE id_ticket = ?");
    $stmt->execute([$ticket['id_ticket']]);
    return $ticket['id_ticket'];
}
```

**Controlador MedicoController** (`MedicoController.php:76-92`):
```php
/**
 * RF 10: Llamar siguiente paciente con verificación de límite diario.
 * Antes de llamar, verifica que el médico no haya alcanzado su límite.
 */
public function llamarSiguiente() {
    if (!$this->id_personal)
        return ['success' => false, 'message' => 'Médico no encontrado'];
    if (!$this->verificarLimiteDiario())
        return ['success' => false, 'message' => 'Has alcanzado el límite diario'];

    $id_ticket = Ticket::llamarSiguiente($this->getIdsConsultorios());
    if ($id_ticket)
        return ['success' => true, 'message' => 'Paciente llamado', 'id_ticket' => $id_ticket];
    return ['success' => false, 'message' => 'No hay pacientes en espera'];
}
```

**Front Controller** (`index.php:451-469`):
```php
if (isset($_GET['action']) && $_GET['action'] === 'llamar_siguiente' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once LOGIC_PATH . '/Controllers/AuthController.php';
    require_once LOGIC_PATH . '/Controllers/MedicoController.php';
    $auth = new AuthController();
    $user = $auth->getUser();
    if ($user && $user['rol'] == 2) {
        $medicoCtrl = new MedicoController($user['id_persona']);
        $result = $medicoCtrl->llamarSiguiente();
        $_SESSION['message'] = $result['message'];
        $_SESSION['messageType'] = $result['success'] ? 'success' : 'warning';
    }
    ob_end_clean();
    echo '<!DOCTYPE html><html><head><meta http-equiv="refresh" content="0;url=?route=dashboard_medico&section=tickets"></head></html>';
    exit;
}
```

---

### RF 11: Actualización Automática del Estado del Turno

**Transiciones:** `Espera → Llamado → Atendido` / `Espera → Ausente`

**Modelo Ticket** (`Ticket.php:263-267`):
```php
/**
 * RF 11: Cambiar estado del ticket.
 * Transiciones válidas:
 *   Espera → Llamado   (médico llama)
 *   Llamado → Atendido (médico registra atención)
 *   Espera → Ausente   (paciente cancela)
 */
public static function cambiarEstado($id_ticket, $estado) {
    $conn = Database::getConnection();
    $stmt = $conn->prepare("UPDATE ticket SET estado = ? WHERE id_ticket = ?");
    return $stmt->execute([$estado, $id_ticket]);
}
```

**Diagrama de Estados:**
```
    ┌──────────┐
    │  Espera  │──── Cancela ────► Ausente
    └────┬─────┘
         │ Médico llama (llamarSiguiente)
         ▼
    ┌──────────┐
    │ Llamado  │──── Iniciar Atención ────► Atendido
    └──────────┘
```

**Puntos de invocación:**
| Transición | Método que la ejecuta |
|-----------|----------------------|
| Espera → Llamado | `Ticket::llamarSiguiente()` |
| Espera → Ausente | `Ticket::cancelar()` |
| Llamado → Atendido | `MedicoController::iniciarAtencion()` → `Ticket::cambiarEstado($id, 'Atendido')` |

---

### RF 12: Historial de Atenciones

**Archivos:** `Atencion.php`, `MedicoController.php`

**Modelo Atencion — Crear** (`Atencion.php:15-20`):
```php
/**
 * RF 11-14: Crear registro de atención médica.
 * Registra diagnóstico, tratamiento, hora inicio real y próxima cita.
 */
public function crear($id_ticket, $id_personal, $tipo_diagnostico, $descripcion_diagnostico, $tratamiento_prescrito, $fecha_proxima_cita = null) {
    $query = "INSERT INTO atencion (id_ticket, id_personal, tipo_diagnostico, descripcion_diagnostico, tratamiento_prescrito, hora_inicio_real, fecha_proxima_cita) 
              VALUES (?, ?, ?, ?, ?, CURTIME(), ?)";
    $stmt = $this->conn->prepare($query);
    return $stmt->execute([$id_ticket, $id_personal, $tipo_diagnostico, $descripcion_diagnostico, $tratamiento_prescrito, $fecha_proxima_cita]);
}
```

**Modelo Atencion — Finalizar** (`Atencion.php:23-27`):
```php
/**
 * RF 11: Finalizar atención registrando hora_fin_real.
 * Se usa para calcular duración real (alimenta modelo M/M/1).
 */
public static function finalizar($id_atencion) {
    $conn = Database::getConnection();
    $stmt = $conn->prepare("UPDATE atencion SET hora_fin_real = CURTIME() WHERE id_atencion = ?");
    return $stmt->execute([$id_atencion]);
}
```

**Modelo Atencion — Buscar Por Ticket** (`Atencion.php:30-35`):
```php
public static function buscarPorTicket($id_ticket) {
    $conn = Database::getConnection();
    $stmt = $conn->prepare("SELECT * FROM atencion WHERE id_ticket = ?");
    $stmt->execute([$id_ticket]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
```

**Modelo Atencion — Historial Resumido** (`Atencion.php:38-56`):
```php
/**
 * RF 13: Historial resumido (últimas 10 atenciones).
 * JOIN: atencion → ticket → consultorio → especialidad → personal → persona.
 */
public static function listarHistorial($id_paciente) {
    $conn = Database::getConnection();
    $query = "SELECT a.*, t.codigo_ticket, t.fecha_creacion,
                     c.numero_consultorio,
                     e.nombre_especialidad,
                     per.nombre as medico_nombre, per.apellido as medico_apellido
              FROM atencion a
              INNER JOIN ticket t ON a.id_ticket = t.id_ticket
              INNER JOIN consultorio c ON t.id_consultorio = c.id_consultorio
              INNER JOIN especialidad e ON c.id_especialidad = e.id_especialidad
              INNER JOIN personal m ON a.id_personal = m.id_personal
              INNER JOIN persona per ON m.id_persona = per.id_persona
              WHERE t.id_paciente = ?
              ORDER BY a.fecha_hora_atencion DESC
              LIMIT 10";
    $stmt = $conn->prepare($query);
    $stmt->execute([$id_paciente]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
```

**Modelo Atencion — Historial Completo** (`Atencion.php:59-76`):
```php
public static function listarHistorialCompleto($id_paciente) {
    // Mismo JOIN que listarHistorial pero sin LIMIT
    $query = "SELECT a.*, t.codigo_ticket, t.fecha_creacion,
                     c.numero_consultorio, c.piso,
                     e.nombre_especialidad,
                     per.nombre as medico_nombre, per.apellido as medico_apellido
              FROM atencion a
              INNER JOIN ticket t ON a.id_ticket = t.id_ticket
              INNER JOIN consultorio c ON t.id_consultorio = c.id_consultorio
              INNER JOIN especialidad e ON c.id_especialidad = e.id_especialidad
              INNER JOIN personal m ON a.id_personal = m.id_personal
              INNER JOIN persona per ON m.id_persona = per.id_persona
              WHERE t.id_paciente = ?
              ORDER BY a.fecha_hora_atencion DESC";
    // ... ejecución ...
}
```

**Modelo Atencion — Próximas Citas** (`Atencion.php:99-117`):
```php
public static function listarProximasCitas($id_paciente) {
    // WHERE t.id_paciente = ? AND a.fecha_proxima_cita IS NOT NULL
    // ORDER BY a.fecha_proxima_cita ASC
}
```

**Modelo Atencion — Listar por Personal** (`Atencion.php:79-96`):
```php
public static function listarPorPersonal($id_personal) {
    // GROUP BY t.id_paciente, per datos
    // COUNT(a.id_atencion) as total_atenciones
    // MAX(a.fecha_hora_atencion) as ultima_atencion
}
```

**Modelo Atencion — Reporte Diario** (`Atencion.php:120-138`):
```php
public static function listarAtencionesPorPersonalYFecha($id_personal, $fecha) {
    // WHERE a.id_personal = ? AND DATE(a.fecha_hora_atencion) = ?
    // Datos: código ticket, prioridad, consultorio, especialidad, paciente, diagnóstico, tratamiento
}
```

**Controlador MedicoController — Iniciar Atención** (`MedicoController.php:124-140`):
```php
/**
 * RF 11-14: Iniciar atención médica.
 * Si ya existe atención para el ticket, la actualiza.
 * Si no, crea una nueva. Luego cambia estado a 'Atendido'.
 */
public function iniciarAtencion($id_ticket, $tipo_diagnostico, $descripcion_diagnostico, $tratamiento_prescrito, $fecha_proxima_cita = null) {
    $existente = Atencion::buscarPorTicket($id_ticket);
    if ($existente) {
        Atencion::actualizar($existente['id_atencion'], $tipo_diagnostico, $descripcion_diagnostico, $tratamiento_prescrito, $fecha_proxima_cita);
    } else {
        $atencion = new Atencion();
        $atencion->crear($id_ticket, $this->id_personal, $tipo_diagnostico, $descripcion_diagnostico, $tratamiento_prescrito, $fecha_proxima_cita);
    }
    Ticket::cambiarEstado($id_ticket, 'Atendido');
    return ['success' => true, 'message' => 'Atención registrada correctamente'];
}
```

**Actualizar Atención** (`Atencion.php:141-146`):
```php
public static function actualizar($id_atencion, $tipo_diagnostico, $descripcion_diagnostico, $tratamiento_prescrito, $fecha_proxima_cita = null) {
    $conn = Database::getConnection();
    $query = "UPDATE atencion SET tipo_diagnostico=?, descripcion_diagnostico=?, tratamiento_prescrito=?, fecha_proxima_cita=? WHERE id_atencion=?";
    $stmt = $conn->prepare($query);
    return $stmt->execute([$tipo_diagnostico, $descripcion_diagnostico, $tratamiento_prescrito, $fecha_proxima_cita, $id_atencion]);
}
```

---

### RF 13: Médico Consulta Historial del Paciente

**Archivos:** `Paciente.php`, `MedicoController.php`

**Modelo Paciente — Búsqueda** (`Paciente.php:83-95`):
```php
/**
 * RF 13: Buscar pacientes por nombre, apellido o documento.
 * Para búsqueda rápida desde dashboard del médico.
 */
public static function buscarPacientes($search) {
    $conn = Database::getConnection();
    $query = "SELECT p.id_paciente, per.nombre, per.apellido, per.documento, per.telefono, per.fecha_nacimiento, per.email
              FROM paciente p
              INNER JOIN persona per ON p.id_persona = per.id_persona
              WHERE per.nombre LIKE ? OR per.apellido LIKE ? OR per.documento LIKE ?
              ORDER BY per.apellido, per.nombre
              LIMIT 20";
    $searchTerm = '%' . $search . '%';
    $stmt = $conn->prepare($query);
    $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
```

**Controlador MedicoController** (`MedicoController.php:100-115`):
```php
public function obtenerHistorialPaciente($id_ticket) {
    $ticket = Ticket::obtenerPorId($id_ticket);
    if (!$ticket) return [];
    return Atencion::listarHistorial($ticket['id_paciente']);
}

public function obtenerHistorialCompleto($id_paciente) {
    return Atencion::listarHistorialCompleto($id_paciente);
}

public function buscarPacientes($search) {
    if (empty(trim($search))) return [];
    return Paciente::buscarPacientes($search);
}
```

---

### RF 14: Motivo de Consulta

**Campo:** `motivo_consulta` (TEXT, nullable) en tabla `ticket`.

**Integración:** En `TicketController::solicitarTicket()`, el parámetro `$sintomas` se asigna a `$ticket->motivo_consulta` antes de llamar a `$ticket->crear()`.

**Creación del ticket** (`Ticket.php:25-44`): El campo `motivo_consulta` se incluye en la sentencia INSERT.

El motivo de consulta es visible para el médico en la lista de espera y en el detalle del ticket.

---

### RF 15: Notificaciones al Paciente

**Arquitectura:** Browser Notifications API + Polling cada 15 segundos.

**Solicitud de permiso** (`ticket_confirmado.php`):
```javascript
function solicitarPermisoNotificaciones() {
    if ('Notification' in window && Notification.permission !== 'granted' && Notification.permission !== 'denied') {
        Notification.requestPermission();
    }
}
window.addEventListener('load', solicitarPermisoNotificaciones);
```

**Notificación al cambiar estado a 'Llamado'** (dentro del polling):
```javascript
if (data.estado === 'Llamado' && Notification.permission === 'granted') {
    new Notification('¡Hora de atención!', {
        body: 'Turno ' + data.codigo_ticket + ' ha sido llamado. Consultorio ' + data.consultorio,
        tag: 'ticket-' + data.id_ticket  // Previene duplicados
    });
}
```

El tag `ticket-{id}` asegura que solo se muestre una notificación por cambio de estado, evitando spam.

---

### RF 16: Tiempo Estimado de Espera

**Dos modalidades:**
1. **Simple:** Personas en espera × 10 minutos
2. **Avanzada:** Modelo M/M/1 (ver sección 8)

**Modelo Ticket — Estimación Simple** (`Ticket.php:113-120`):
```php
/**
 * RF 16: Estimar tiempo de espera.
 * Fórmula: cantidad_personas_en_espera × 10 minutos.
 * @param int $id_consultorio ID del consultorio
 * @return int Tiempo estimado en minutos
 */
public static function estimarTiempoEspera($id_consultorio) {
    $conn = Database::getConnection();
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM ticket 
                            WHERE id_consultorio = ? AND estado = 'Espera'");
    $stmt->execute([$id_consultorio]);
    $cantidad = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    return $cantidad * 10; // 10 min por persona en espera
}
```

---

### RF 17: Visualizar Pacientes Atendidos

**Dashboard médico:** Datos de `$medicoCtrl->getLimiteDiario()` que retorna `['limite', 'atendidos', 'restantes']`.

**Monitor público:** Datos en `Ticket::listarMonitor()`.

---

### RF 18: Reportes Diarios de Atención

**Modelo Atencion — Reporte Diario** (`Atencion.php:120-138`):
```php
/**
 * RF 18: Reporte diario de atenciones de un médico.
 * @param int $id_personal ID del médico
 * @param string $fecha Fecha del reporte (YYYY-MM-DD)
 * @return array Lista detallada de atenciones del día
 */
public static function listarAtencionesPorPersonalYFecha($id_personal, $fecha) {
    $conn = Database::getConnection();
    $query = "SELECT a.*, t.codigo_ticket, t.prioridad,
                     c.numero_consultorio, c.piso,
                     e.nombre_especialidad,
                     per.nombre as paciente_nombre, per.apellido as paciente_apellido,
                     per.documento, per.telefono
              FROM atencion a
              INNER JOIN ticket t ON a.id_ticket = t.id_ticket
              INNER JOIN consultorio c ON t.id_consultorio = c.id_consultorio
              INNER JOIN especialidad e ON c.id_especialidad = e.id_especialidad
              INNER JOIN paciente p ON t.id_paciente = p.id_paciente
              INNER JOIN persona per ON p.id_persona = per.id_persona
              WHERE a.id_personal = ? AND DATE(a.fecha_hora_atencion) = ?
              ORDER BY a.fecha_hora_atencion ASC";
    $stmt = $conn->prepare($query);
    $stmt->execute([$id_personal, $fecha]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
```

**Datos del reporte:** Código ticket, prioridad, consultorio, piso, especialidad, nombre del paciente, documento, teléfono, tipo de diagnóstico, descripción, tratamiento, hora de atención, próxima cita.

---

## 8. MODELO MATEMÁTICO M/M/1 — TEORÍA DE COLAS

### 8.1 Fundamento

Modelo de colas Markoviano con tres parámetros (M/M/1):
- **M (Markoviano) en arribos:** Proceso de Poisson con tasa λ
- **M (Markoviano) en servicio:** Distribución exponencial con tasa μ
- **1 servidor:** Un médico por especialidad

Desarrollado por Agner Krarup Erlang (1909).

### 8.2 Variables y Fórmulas

| Variable | Nombre | Descripción | Fórmula de cálculo |
|----------|--------|-------------|---------------------|
| λ (lambda) | Tasa de llegada | Pacientes/hora promedio (últimos 7 días) | `total_tickets / horas_totales` |
| μ (mu) | Tasa de servicio | Pacientes/hora (`60 / duración_promedio_min`) | Calculado de tiempos reales |
| ρ (rho) | Factor de utilización | Porcentaje de tiempo ocupado | `λ / μ` |
| Lq | Longitud promedio de cola | Personas esperando en promedio | `λ² / (μ(μ - λ))` |
| Wq | Tiempo promedio en cola | Tiempo de espera (horas) | `Lq / λ` |
| W | Tiempo promedio en sistema | Llegada → salida (horas) | `1 / (μ - λ)` |

**Relación de Little:** `L = λ × W` (número promedio en sistema = tasa × tiempo)

### 8.3 Condiciones de Estabilidad

| Condición | Estado del Sistema | Interpretación |
|-----------|-------------------|----------------|
| ρ < 1 | Estable | La cola converge, el sistema es sostenible |
| ρ = 1 | Crítico | Sistema al límite, sin margen |
| ρ > 1 | Inestable | La cola crece indefinidamente |

### 8.4 Implementación Completa

**Archivo:** `src/Logic/Models/Ticket.php` (líneas 337-391)

```php
/**
 * MODELO M/M/1 - TEORÍA DE COLAS (RF 16 - Tiempo estimado de espera avanzado)
 *
 * Variables:
 *   λ (lambda) = Tasa de llegada (pacientes/hora) - últimos 7 días
 *   μ (mu)     = Tasa de servicio (pacientes/hora) - duración promedio
 *
 * Fórmulas:
 *   ρ = λ / μ                     Factor de utilización
 *   Lq = λ² / (μ(μ - λ))         Pacientes promedio en cola
 *   Wq = Lq / λ                   Tiempo promedio de espera (horas)
 *   W  = 1 / (μ - λ)             Tiempo total en sistema (horas)
 *
 * Interpretación:
 *   ρ < 1  → Sistema estable
 *   ρ = 1  → Sistema al límite
 *   ρ > 1  → Sistema inestable, cola crece indefinidamente
 *
 * @param int $id_especialidad ID de la especialidad a analizar
 * @return array ['lambda', 'mu', 'rho', 'Lq', 'Wq', 'W', 'Wq_min', 'W_min']
 */
public static function calcularMM1($id_especialidad) {
    $conn = Database::getConnection();

    // ========== PASO 1: Calcular λ (tasa de llegada) ==========
    // Contar tickets de la especialidad en los últimos 7 días
    // Dividir entre las horas transcurridas en ese período
    $stmt = $conn->prepare("SELECT COUNT(*) as total, 
                                   TIMESTAMPDIFF(HOUR, MIN(fecha_creacion), MAX(fecha_creacion)) + 1 as horas
                            FROM ticket 
                            WHERE id_consultorio IN (
                                SELECT id_consultorio FROM consultorio WHERE id_especialidad = ?
                            )
                            AND fecha_creacion >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $stmt->execute([$id_especialidad]);
    $llegadas = $stmt->fetch(PDO::FETCH_ASSOC);

    $lambda = 4; // Valor por defecto si no hay datos
    if ($llegadas && $llegadas['horas'] > 0) {
        $lambda = round($llegadas['total'] / max($llegadas['horas'], 1), 2);
    }

    // ========== PASO 2: Calcular μ (tasa de servicio) ==========
    // Duración promedio de atenciones reales (hora_fin_real - hora_inicio_real)
    $stmt = $conn->prepare("SELECT AVG(TIMESTAMPDIFF(MINUTE, hora_inicio_real, hora_fin_real)) as duracion_promedio
                            FROM atencion a
                            INNER JOIN ticket t ON a.id_ticket = t.id_ticket
                            INNER JOIN consultorio c ON t.id_consultorio = c.id_consultorio
                            WHERE c.id_especialidad = ? AND hora_fin_real IS NOT NULL");
    $stmt->execute([$id_especialidad]);
    $servicio = $stmt->fetch(PDO::FETCH_ASSOC);

    $mu = 6; // Valor por defecto: 10 min por paciente = 6 pacientes/hora
    if ($servicio && $servicio['duracion_promedio'] > 0) {
        $minutos = floatval($servicio['duracion_promedio']);
        $mu = round(60 / max($minutos, 1), 2);
    }

    // ========== PASO 3: Calcular métricas derivadas ==========
    $rho = $mu > 0 ? round($lambda / $mu, 4) : 0;
    $Lq = 0;
    $Wq = 0;
    $W = 0;

    // Las fórmulas solo son válidas cuando ρ < 1 y μ > 0
    if ($rho < 1 && $mu > 0) {
        // Lq = λ² / (μ(μ - λ)) — pacientes promedio en cola
        $Lq = round(($lambda * $lambda) / ($mu * ($mu - $lambda)), 2);
        
        // Wq = Lq / λ — tiempo promedio de espera en cola (horas)
        $Wq = $mu > $lambda ? round($Lq / $lambda, 2) : 0;
        
        // W = 1 / (μ - λ) — tiempo total en sistema (horas)
        $W = $mu > $lambda ? round(1 / ($mu - $lambda), 2) : 0;
    }

    // ========== PASO 4: Retornar resultados ==========
    return [
        'lambda'    => $lambda,     // Tasa de llegada (pacientes/hora)
        'mu'        => $mu,         // Tasa de servicio (pacientes/hora)
        'rho'       => $rho,        // Factor de utilización (0 a ∞)
        'Lq'        => $Lq,         // Pacientes promedio en cola
        'Wq'        => $Wq,         // Tiempo prom. espera en cola (horas)
        'W'         => $W,          // Tiempo prom. total en sistema (horas)
        'Wq_min'    => round($Wq * 60),  // Convertido a minutos
        'W_min'     => round($W * 60)     // Convertido a minutos
    ];
}
```

### 8.5 Ejemplo de Cálculo Manual

**Datos:** En Cardiología los últimos 7 días:
- 504 tickets en 96 horas → λ = 504/96 = **5.25 pacientes/hora**
- Duración promedio de atención: 12 minutos → μ = 60/12 = **5 pacientes/hora**

**Cálculo:**
- ρ = 5.25 / 5 = **1.05** → Sistema inestable (ρ > 1)
- La cola crecerá indefinidamente. Se necesita otro cardiólogo.

**Si se corrige (μ = 8, duración ~7.5 min):**
- ρ = 5.25 / 8 = **0.65625** → Sistema estable
- Lq = 5.25² / (8 × (8 - 5.25)) = 27.56 / 22 = **1.25 pacientes en cola**
- Wq = 1.25 / 5.25 = **0.238 horas = ~14.3 minutos** de espera promedio
- W = 1 / (8 - 5.25) = **0.364 horas = ~21.8 minutos** en sistema

**Interpretación:** Con 8 pacientes/hora de capacidad y 5.25/hora de demanda, un paciente espera ~14 minutos en cola y pasa ~22 minutos en total.

### 8.6 Visualización en Dashboard del Médico

```
┌──────────────────────────────────────────────────┐
│        Modelo M/M/1 - Análisis de la cola         │
├──────────┬──────────┬──────────┬──────────┬───────┤
│ λ=4.5    │ μ=6.0    │ ρ=0.75   │ Lq=2.25  │       │
│ llegadas │ servicios│ factor   │ pacientes│       │
│ /hora    │ /hora    │ de uso   │ en cola  │       │
├──────────┴──────────┴──────────┴──────────┴───────┤
│ Wq = 30 min (tiempo espera en cola)               │
│ W  = 40 min (tiempo total en sistema)             │
│                                                    │
│ Si ρ ≥ 1: ⚠ Sistema saturado, cola crecerá        │
│           indefinidamente                          │
└────────────────────────────────────────────────────┘
```

---

## 9. API — Endpoints y Especificación

### 9.1 Tabla de Endpoints

**Front Controller:** `public/index.php`

| Método | Endpoint | Descripción | Auth |
|--------|----------|-------------|------|
| GET | `?route=api&action=check_ticket&id_ticket=X` | Estado y posición del ticket | No |
| GET | `?route=api_monitor&especialidad=X` | Monitor público sala de espera | No |
| POST | `?route=login` | Autenticación | No |
| POST | `?route=register` | Registro de paciente | No |
| POST | `?route=logout` | Cierre de sesión | Sí |
| POST | `?route=solicitar_turno&action=confirmar_ticket` | Solicitar turno | Sí (paciente) |
| POST | `?route=dashboard_paciente&action=cancelar_ticket` | Cancelar turno | Sí (paciente) |
| POST | `?route=dashboard_medico&action=llamar_siguiente` | Llamar siguiente | Sí (médico) |
| POST | `?route=dashboard_medico&action=guardar_atencion` | Registrar atención | Sí (médico) |
| POST | `?route=dashboard_medico&action=update_limite` | Actualizar límite diario | Sí (médico) |
| POST | `?route=dashboard_paciente&action=update_profile` | Actualizar perfil | Sí (cualquiera) |
| POST | `?route=dashboard_paciente&action=update_all` | Actualizar todos los datos | Sí (paciente) |
| POST | `?route=dashboard_paciente&action=change_password` | Cambiar contraseña | Sí (cualquiera) |
| POST | `?route=dashboard_admin&action=update_role` | Cambiar rol de usuario | Sí (admin) |
| POST | `?route=dashboard_admin&action=create_esp` | Crear especialidad | Sí (admin) |
| POST | `?route=dashboard_admin&action=delete_esp` | Eliminar especialidad | Sí (admin) |
| POST | `?route=dashboard_admin&action=create_cons` | Crear consultorio | Sí (admin) |
| POST | `?route=dashboard_admin&action=delete_cons` | Eliminar consultorio | Sí (admin) |
| POST | `?route=dashboard_admin&action=create_personal` | Crear personal | Sí (admin) |
| POST | `?route=dashboard_admin&action=delete_personal` | Eliminar personal | Sí (admin) |
| POST | `?route=dashboard_admin&action=create_horario` | Crear horario | Sí (admin) |
| POST | `?route=dashboard_admin&action=delete_horario` | Eliminar horario | Sí (admin) |
| POST | `?route=dashboard_admin&action=asignar_horario` | Asignar horario a médico | Sí (admin) |
| POST | `?route=dashboard_admin&action=desasignar_horario` | Desasignar horario de médico | Sí (admin) |
| GET | `?route=forgot_password&POST` | Recuperar contraseña | No |

### 9.2 Formato de Respuestas JSON

**Respuesta exitosa (check_ticket):**
```json
{
    "success": true,
    "id_ticket": 7,
    "codigo_ticket": "MED-005",
    "estado": "Llamado",
    "personas_antes": 3,
    "tiempo_estimado": 0,
    "hora_estimada_minutos": 30,
    "consultorio": "104",
    "piso": "1"
}
```

**Respuesta API Monitor:**
```json
{
    "success": true,
    "llamados": [...],
    "atendidos": [...],
    "espera_por_especialidad": [...],
    "total_espera": 15,
    "timestamp": "14:30:00"
}
```

### 9.3 Respuestas HTML (redirect con mensajes)

Las acciones POST retornan HTML con meta refresh:
```html
<!DOCTYPE html>
<html><head>
<meta http-equiv="refresh" content="0;url=?route=target_route&message=success_msg">
</head></html>
```

---

## 10. FLUJOS DE OPERACIÓN

### 10.1 Flujo de Solicitud de Turno

```
1. Paciente ingresa a solicitar_turno.php
2. Selecciona especialidad → se cargan consultorios disponibles
3. Selecciona prioridad (Verde/Amarillo/Rojo)
4. Escribe motivo de consulta (síntomas)
5. Clic en "Solicitar Turno"
   ├─ Backend verifica: ¿tiene ticket activo hoy?
   ├─ Backend verifica: ¿hay disponibilidad horaria?
   ├─ Backend genera código: ej MED-001
   ├─ Backend calcula tiempo estimado: N × 10 min
   ├─ Se crea ticket en estado "Espera"
   └─ Redirect a ticket_confirmado.php
6. ticket_confirmado.php inicia polling cada 15s y solicita notificaciones
7. El paciente ve su posición en tiempo real
8. Cuando el médico lo llama → estado cambia a "Llamado" → notificación
9. El paciente se dirige al consultorio
```

### 10.2 Flujo de Atención Médica

```
1. Médico inicia sesión → dashboard_medico.php
2. Ve la lista de espera (ordenada por prioridad y antigüedad)
3. Clic en "Siguiente" → llamarSiguiente()
   ├─ Verifica límite diario del médico
   ├─ Selecciona ticket más prioritario en "Espera"
   └─ Cambia estado a "Llamado" + registra hora
4. El paciente es llamado → aparece en pantalla
5. El médico inicia la atención → guardar_atencion()
   ├─ Crea registro en tabla atencion
   └─ Cambia estado del ticket a "Atendido"
6. Opcionalmente finaliza atención (registra hora_fin_real)
7. Si hay próxima cita, se registra fecha_proxima_cita
```

### 10.3 Flujo de Cancelación

```
1. Paciente ingresa a su dashboard
2. Ve sus tickets activos del día
3. Clic en "Cancelar" en un ticket en estado "Espera"
   ├─ Backend verifica: ¿pertenece a este paciente?
   ├─ Backend verifica: ¿estado = 'Espera'?
   └─ Cambia estado a "Ausente"
4. Si intenta cancelar un ticket fuera de estos criterios → error
```

---

## 11. SEGURIDAD

### 11.1 Medidas Implementadas

| Medida | Implementación | Ubicación |
|--------|---------------|-----------|
| **Prevención SQL Injection** | Sentencias preparadas PDO en todo el proyecto | Todos los modelos |
| **Hash de contraseñas** | `password_hash()` con `PASSWORD_DEFAULT` (bcrypt) | `Usuario::crear()` |
| **Verificación de contraseñas** | `password_verify()` | `Usuario::verificarLogin()` |
| **Sesiones PHP** | `session_start()` con datos en servidor | `AuthController` |
| **Cookies firmadas** | SHA-256 con secreto embebido (`Q-Line-SECRET-2026`) | `AuthController::restoreFromCookie()` |
| **Validación en servidor** | Campos requeridos, formato email, longitud mínima | `AuthController::register()` |
| **Verificación de unicidad** | Busca documento y email antes de insertar | `Persona::buscarPorDocumento/Email()` |
| **Control de acceso por rol** | Redirect a login si rol no coincide | Cada dashboard |

### 11.2 Áreas de Mejora de Seguridad

**No implementado (recomendaciones):**
- Tokens CSRF en formularios POST
- Sanitización/Escape de output (riesgo XSS)
- Rate limiting en login
- Timeout de sesión por inactividad
- HTTPS obligatorio en producción
- Lista blanca de IPs para admin
- Encriptación en tránsito de datos sensibles

### 11.3 Manejo de Errores

```php
// En Database.php:
catch(PDOException $exception) {
    die("Error de conexión a la base de datos: " . $exception->getMessage());
}
// Nota: die() expone el mensaje de error al usuario.
// Recomendación: loggear el error y mostrar mensaje genérico.
```

---

## 12. PATRONES DE DISEÑO APLICADOS

### 12.1 Front Controller Pattern
- **Archivo:** `public/index.php`
- **Función:** Punto único de entrada para todas las peticiones HTTP
- **Responsabilidad:** Routing, dispatching, manejo de API

### 12.2 Data Mapper / Active Record (DAO)
- **Archivo:** `src/Logic/Models/*.php`
- **Cada modelo representa una tabla** con métodos CRUD
- Las consultas SQL están encapsuladas en los modelos

### 12.3 Singleton (Conexión de BD)
- **Archivo:** `config/database.php`
- `Database::getConnection()` retorna siempre la misma instancia PDO
- Evita múltiples conexiones simultáneas

### 12.4 Template Method (vistas PHP)
- **Directorio:** `src/Presentation/views/`
- Las vistas PHP son templates que mezclan HTML con datos PHP
- Layout compartido en `src/Presentation/layouts/main.php`

### 12.5 Controller Pattern
- **Directorio:** `src/Logic/Controllers/`
- Cada controlador maneja un dominio funcional
- Recibe datos del Front Controller, interactúa con Models, retorna resultados

---

## 13. GUÍA DE INSTALACIÓN

### 13.1 Requisitos Previos

```
- PHP 8.2+ con extensiones: pdo_mysql, mbstring, openssl, session, json
- MySQL 5.7+ o MariaDB 10.4+
- Servidor web Apache/Nginx con mod_rewrite
- Composer (opcional, no se usa actualmente)
```

### 13.2 Pasos de Instalación

**Paso 1: Clonar/configurar el proyecto**
```bash
cd /opt/lampp/htdocs/
# Copiar archivos del proyecto a Sistema_tickets/
```

**Paso 2: Importar base de datos**
```bash
mysql -u root -p < src/Data/sistema_tickets.sql
# O abrir phpMyAdmin y ejecutar src/Data/sistema_tickets.sql
```

**Paso 3: Configurar conexión a BD**
```php
// Editar config/database.php
private static $host = "localhost";
private static $db_name = "sistema_tickets";
private static $username = "root";
private static $password = "";  // Cambiar en producción
```

**Paso 4: Configurar servidor web**
```apache
# En httpd.conf o VirtualHost de Apache:
DocumentRoot "/opt/lampp/htdocs/Sistema_tickets/public"
<Directory "/opt/lampp/htdocs/Sistema_tickets/public">
    AllowOverride All
    Require all granted
</Directory>
```

**Paso 5: Acceder al sistema**
```
http://localhost/Sistema_tickets/public/
o
http://localhost/Sistema_tickets/public/index.php?route=landing
```

**Paso 6: Credenciales de prueba (del dump SQL)**

| Usuario | Contraseña | Rol |
|---------|-----------|-----|
| admin@gmail.com | (bcrypt hash) | Admin |
| medico@gmail.com | (bcrypt hash) | Médico |
| paciente@gmail.com | (bcrypt hash) | Paciente |

*Nota: Las contraseñas están hasheadas en la BD. Consultar la documentación del equipo para credenciales de prueba.*

### 13.3 Credenciales Semilla

Los usuarios semilla incluyen contraseñas hasheadas con bcrypt. Se proporcionan tres usuarios de prueba:
- admin → `admin@gmail.com` (rol Admin)
- paciente → `paciente@gmail.com` (rol Paciente)
- medico → `medico@gmail.com` (rol Médico)

---

## 14. ANEXOS

### Anexo A: Lista Completa de Archivos del Proyecto

| # | Ruta | Descripción | Líneas |
|---|------|-------------|--------|
| 1 | `config/database.php` | Clase Database (conexión Singleton PDO) | 23 |
| 2 | `public/index.php` | Front Controller — routing, dispatching, API | 544 |
| 3 | `public/css/dashboard.css` | Estilos de dashboards | — |
| 4 | `public/css/dashboard-common.css` | Estilos comunes de dashboard | — |
| 5 | `public/css/landing.css` | Estilos de página de inicio | — |
| 6 | `public/css/login.css` | Estilos de login | — |
| 7 | `public/css/monitor.css` | Estilos del monitor de sala | — |
| 8 | `public/css/register.css` | Estilos de registro | — |
| 9 | `src/Data/sistema_tickets.sql` | Script SQL (estructura + datos semilla) | 519 |
| 10 | `src/Logic/Controllers/AuthController.php` | Autenticación, registro, sesión, cookie | 231 |
| 11 | `src/Logic/Controllers/TicketController.php` | Gestión de turnos del paciente | 156 |
| 12 | `src/Logic/Controllers/MedicoController.php` | Gestión del médico (cola, atención) | 167 |
| 13 | `src/Logic/Controllers/UserController.php` | Gestión de usuarios, roles, perfiles | 123 |
| 14 | `src/Logic/Controllers/EspecialidadController.php` | CRUD de especialidades | — |
| 15 | `src/Logic/Controllers/PersonalController.php` | CRUD de personal médico | — |
| 16 | `src/Logic/Controllers/HorarioController.php` | CRUD de horarios y asignaciones | — |
| 17 | `src/Logic/Models/Ticket.php` | Modelo Ticket + M/M/1 | 392 |
| 18 | `src/Logic/Models/Usuario.php` | Modelo Usuario (auth, roles, perfil) | 230 |
| 19 | `src/Logic/Models/Persona.php` | Modelo base de Persona | 96 |
| 20 | `src/Logic/Models/Paciente.php` | Modelo de Paciente | 96 |
| 21 | `src/Logic/Models/Personal.php` | Modelo de Personal Médico | 89 |
| 22 | `src/Logic/Models/Consultorio.php` | Modelo de Consultorio | 45 |
| 23 | `src/Logic/Models/Horario.php` | Modelo de Horarios | 118 |
| 24 | `src/Logic/Models/Atencion.php` | Modelo de Atenciones Médicas | 147 |
| 25 | `src/Logic/Models/Especialidad.php` | Modelo de Especialidad | — |
| 26 | `src/Logic/Helpers/UIHelper.php` | Funciones auxiliares de UI (SVG) | 41 |
| 27 | `src/Presentation/layouts/main.php` | Layout principal (header, nav, footer) | — |
| 28 | `src/Presentation/views/landing.php` | Página de inicio | — |
| 29 | `src/Presentation/views/login.php` | Formulario de login | — |
| 30 | `src/Presentation/views/register.php` | Formulario de registro | — |
| 31 | `src/Presentation/views/forgot_password.php` | Recuperación de contraseña | — |
| 32 | `src/Presentation/views/privacy.php` | Política de privacidad | — |
| 33 | `src/Presentation/views/dashboard.php` | Dashboard general | — |
| 34 | `src/Presentation/views/dashboard_admin.php` | Panel de administración | — |
| 35 | `src/Presentation/views/dashboard_medico.php` | Panel del médico | — |
| 36 | `src/Presentation/views/dashboard_paciente.php` | Panel del paciente | — |
| 37 | `src/Presentation/views/solicitar_turno.php` | Solicitud de turno | — |
| 38 | `src/Presentation/views/ticket_confirmado.php` | Confirmación de turno | — |
| 39 | `src/Presentation/views/monitor.php` | Monitor de sala de espera | — |

### Anexo B: Glosario de Términos

| Término | Definición |
|---------|-----------|
| **RF** | Requisito Funcional |
| **DA** | Requisito de datos (No Funcional) |
| **PK** | Primary Key (Clave Primaria) |
| **FK** | Foreign Key (Clave Foránea) |
| **AI** | Auto Increment |
| **bcrypt** | Algoritmo de hash adaptativo para contraseñas |
| **PDO** | PHP Data Objects — capa de abstracción de BD |
| **Singleton** | Patrón de diseño que garantiza una sola instancia |
| **Poisson** | Distribución de probabilidad para eventos independientes |
| **M/M/1** | Modelo de colas: Markoviano/Markoviano/1 servidor |
| **λ (lambda)** | Tasa de llegada promedio |
| **μ (mu)** | Tasa de servicio promedio |
| **ρ (rho)** | Factor de utilización del sistema |
| **Lq** | Longitud promedio de la cola |
| **Wq** | Tiempo promedio de espera en cola |
| **W** | Tiempo promedio total en el sistema |
| **FIFO** | First In, First Out (primero en llegar, primero en ser atendido) |
| **CSRF** | Cross-Site Request Forgery (falsificación de petición entre sitios) |
| **XSS** | Cross-Site Scripting (inyección de scripts) |
| **API** | Application Programming Interface |
| **JSON** | JavaScript Object Notation |
| **CRUD** | Create, Read, Update, Delete |

### Anexo C: Diagrama Relacional de la Base de Datos (SQL)

```sql
-- Consulta para obtener todas las FK y sus relaciones:
SELECT 
    TABLE_NAME, COLUMN_NAME, CONSTRAINT_NAME, 
    REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = 'sistema_tickets'
  AND REFERENCED_TABLE_NAME IS NOT NULL
ORDER BY TABLE_NAME;
```

### Anexo D: Consultas SQL Útiles

**Total de tickets por estado:**
```sql
SELECT estado, COUNT(*) as total 
FROM ticket 
GROUP BY estado;
```

**Tickets atendidos hoy:**
```sql
SELECT COUNT(*) as atendidos_hoy 
FROM atencion 
WHERE DATE(fecha_hora_atencion) = CURDATE();
```

**Pacientes únicos atendidos esta semana:**
```sql
SELECT COUNT(DISTINCT p.id_paciente) as pacientes_unicos
FROM atencion a
INNER JOIN ticket t ON a.id_ticket = t.id_ticket
INNER JOIN paciente p ON t.id_paciente = p.id_paciente
WHERE a.fecha_hora_atencion >= DATE_SUB(NOW(), INTERVAL 7 DAY);
```

**Tiempo promedio de atención por especialidad:**
```sql
SELECT e.nombre_especialidad, 
       AVG(TIMESTAMPDIFF(MINUTE, a.hora_inicio_real, a.hora_fin_real)) as duracion_promedio_min
FROM atencion a
INNER JOIN ticket t ON a.id_ticket = t.id_ticket
INNER JOIN consultorio c ON t.id_consultorio = c.id_consultorio
INNER JOIN especialidad e ON c.id_especialidad = e.id_especialidad
WHERE a.hora_fin_real IS NOT NULL
GROUP BY e.nombre_especialidad
ORDER BY duracion_promedio_min DESC;
```

**Usuarios por rol:**
```sql
SELECT r.nombre_rol, COUNT(*) as total_usuarios
FROM usuario u
INNER JOIN rol r ON u.id_rol = r.id_rol
GROUP BY r.nombre_rol;
```

### Anexo E: Checklist de Implementación por RF

| RF | Requisito | Archivos | Estado |
|----|-----------|----------|--------|
| 1 | Login por tipo de usuario | AuthController.php, Usuario.php, index.php | ✅ Implementado |
| 2 | Registro de pacientes, médicos, administrativos | AuthController.php, PersonalController.php, Persona.php, Usuario.php, Paciente.php | ✅ Implementado |
| 3 | Gestión de roles y permisos | Usuario.php, cada dashboard | ✅ Implementado |
| 4 | Solicitud digital de fichas | TicketController.php, Ticket.php, Horario.php | ✅ Implementado |
| 5 | Número de turno único | Ticket.php (generarCodigo) | ✅ Implementado |
| 6 | Posición en cola en tiempo real | Ticket.php, index.php (API), JavaScript polling | ✅ Implementado |
| 7 | Cancelar fichas | TicketController.php, Ticket.php (cancelar) | ✅ Implementado |
| 8 | Límite de fichas por día/médico | Ticket.php, Personal.php, MedicoController.php | ✅ Implementado |
| 9 | Lista de pacientes en espera | Ticket.php (listarEnEspera), MedicoController.php | ✅ Implementado |
| 10 | Médico llama siguiente paciente | Ticket.php (llamarSiguiente), MedicoController.php | ✅ Implementado |
| 11 | Actualización automática de estados | Ticket.php (cambiarEstado) | ✅ Implementado |
| 12 | Historial de atenciones | Atencion.php | ✅ Implementado |
| 13 | Médico consulta historial | Paciente.php, MedicoController.php | ✅ Implementado |
| 14 | Registrar motivo de consulta | Ticket.php (motivo_consulta) | ✅ Implementado |
| 15 | Notificaciones al paciente | JavaScript (Notification API) | ✅ Implementado |
| 16 | Tiempo estimado de espera | Ticket.php (estimarTiempoEspera + calcularMM1) | ✅ Implementado |
| 17 | Visualizar pacientes atendidos | Atencion.php (listarPorPersonal), Ticket.php (listarMonitor) | ✅ Implementado |
| 18 | Reportes diarios de atención | Atencion.php (listarAtencionesPorPersonalYFecha) | ✅ Implementado |

---

> **Fin del Manual Técnico — Q-Line v1.0**
> Documento generado el 2026-05-13. Todos los fragmentos de código están extraídos directamente del código fuente del proyecto.

