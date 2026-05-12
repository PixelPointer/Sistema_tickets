<?php
// Front Controller - Punto de entrada único
// Arquitectura 3-capas: Presentación (views), Lógica (Controllers/Models), Datos (config/database)
date_default_timezone_set('America/La_Paz');
ob_start();
session_start();

require_once dirname(__DIR__) . '/src/Logic/Controllers/AuthController.php';
AuthController::restoreFromCookie();

define('BASE_PATH', dirname(__DIR__));
define('PRESENTATION_PATH', BASE_PATH . '/src/Presentation');
define('LOGIC_PATH', BASE_PATH . '/src/Logic');

$route = $_GET['route'] ?? 'landing';
$message = '';
$messageType = '';

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

// RFC 20: API endpoint para monitor público (5s polling)
if ($route === 'api_monitor') {
    header('Content-Type: application/json');
    ob_end_clean();
    require_once LOGIC_PATH . '/Models/Ticket.php';

    $especialidad = intval($_GET['especialidad'] ?? 0);
    $data = Ticket::listarMonitor();

    echo json_encode([
        'success' => true,
        'llamados' => $data['llamados'],
        'atendidos' => $data['atendidos'],
        'espera_por_especialidad' => $data['espera_por_especialidad'],
        'total_espera' => $data['total_espera'],
        'timestamp' => $data['timestamp']
    ]);
    exit;
}

// Procesamiento de formularios POST y acciones administrativas
if ($_SERVER['REQUEST_METHOD'] === 'POST' || $route === 'logout') {
    require_once LOGIC_PATH . '/Controllers/AuthController.php';
    
    $auth = new AuthController();

    // RF 3: Cerrar sesión
    if ($route === 'logout') {
        $auth->logout();
        header('Location: ?route=landing');
        exit;
    }

    // RF 2: Registro de nuevo paciente
    if ($route === 'register') {
        $data = [
            'nombre' => $_POST['nombre'] ?? '',
            'apellido' => $_POST['apellido'] ?? '',
            'documento' => $_POST['documento'] ?? '',
            'telefono' => $_POST['telefono'] ?? '',
            'direccion' => $_POST['direccion'] ?? '',
            'fecha_nacimiento' => $_POST['fecha_nacimiento'] ?? '',
            'email' => $_POST['email'] ?? '',
            'password' => $_POST['password'] ?? '',
            'confirm_password' => $_POST['confirm_password'] ?? ''
        ];

        $result = $auth->register($data);
        
        ob_end_clean();
        
        if ($result['success']) {
            echo '<!DOCTYPE html><html><head><meta http-equiv="refresh" content="0;url=?route=login&message=registered"></head></html>';
            exit;
        } else {
            $message = $result['message'];
            $messageType = 'error';
        }
    }

    // RF 1: Inicio de sesión con redirect según rol
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
            echo '<!DOCTYPE html><html><head><meta http-equiv="refresh" content="0;url=?route=' . $dashboardRoute . '"></head></html>';
            exit;
        } else {
            $message = $result['message'];
            $messageType = 'error';
        }
    }

    // Acciones administrativas (CRUD: usuarios, especialidades, consultorios, personal, horarios)
    if (isset($_GET['action']) && $_GET['action'] === 'update_role' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        require_once LOGIC_PATH . '/Controllers/UserController.php';
        
        $userCtrl = new UserController();
        $result = $userCtrl->updateRole($_POST['id_usuario'], $_POST['id_rol']);
        
        ob_end_clean();
        echo '<!DOCTYPE html><html><head><meta http-equiv="refresh" content="0;url=?route=dashboard_admin&view=usuarios&message=updated"></head></html>';
        exit;
    }

    if (isset($_GET['action']) && $_GET['action'] === 'create_esp' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        require_once LOGIC_PATH . '/Controllers/EspecialidadController.php';
        
        $espCtrl = new EspecialidadController();
        $result = $espCtrl->crearEspecialidad($_POST['nombre']);
        
        ob_end_clean();
        echo '<!DOCTYPE html><html><head><meta http-equiv="refresh" content="0;url=?route=dashboard_admin&view=especialidades"></head></html>';
        exit;
    }

    if (isset($_GET['action']) && $_GET['action'] === 'delete_esp' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        require_once LOGIC_PATH . '/Controllers/EspecialidadController.php';
        
        $espCtrl = new EspecialidadController();
        $result = $espCtrl->eliminarEspecialidad($_POST['id_especialidad']);
        
        ob_end_clean();
        echo '<!DOCTYPE html><html><head><meta http-equiv="refresh" content="0;url=?route=dashboard_admin&view=especialidades"></head></html>';
        exit;
    }

    if (isset($_GET['action']) && $_GET['action'] === 'create_cons' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        require_once LOGIC_PATH . '/Controllers/EspecialidadController.php';
        
        $espCtrl = new EspecialidadController();
        $result = $espCtrl->crearConsultorio($_POST['id_especialidad'], $_POST['numero'], $_POST['piso'] ?? '');
        
        ob_end_clean();
        echo '<!DOCTYPE html><html><head><meta http-equiv="refresh" content="0;url=?route=dashboard_admin&view=especialidades"></head></html>';
        exit;
    }

    if (isset($_GET['action']) && $_GET['action'] === 'delete_cons' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        require_once LOGIC_PATH . '/Controllers/EspecialidadController.php';
        
        $espCtrl = new EspecialidadController();
        $result = $espCtrl->eliminarConsultorio($_POST['id_consultorio']);
        
        ob_end_clean();
        echo '<!DOCTYPE html><html><head><meta http-equiv="refresh" content="0;url=?route=dashboard_admin&view=especialidades"></head></html>';
        exit;
    }

    if (isset($_GET['action']) && $_GET['action'] === 'create_personal' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        require_once LOGIC_PATH . '/Controllers/PersonalController.php';
        
        $personalCtrl = new PersonalController();
        $result = $personalCtrl->crearPersonal($_POST['id_usuario'], $_POST['id_especialidad'], $_POST['matricula']);
        
        ob_end_clean();
        echo '<!DOCTYPE html><html><head><meta http-equiv="refresh" content="0;url=?route=dashboard_admin&view=personal"></head></html>';
        exit;
    }

    if (isset($_GET['action']) && $_GET['action'] === 'delete_personal' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        require_once LOGIC_PATH . '/Controllers/PersonalController.php';
        
        $personalCtrl = new PersonalController();
        $result = $personalCtrl->eliminarPersonal($_POST['id_personal']);
        
        ob_end_clean();
        echo '<!DOCTYPE html><html><head><meta http-equiv="refresh" content="0;url=?route=dashboard_admin&view=personal"></head></html>';
        exit;
    }

    if (isset($_GET['action']) && $_GET['action'] === 'create_horario' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        require_once LOGIC_PATH . '/Controllers/HorarioController.php';
        
        $horarioCtrl = new HorarioController();
        $result = $horarioCtrl->crearHorario($_POST['dia_semana'], $_POST['hora_inicio'], $_POST['hora_fin']);
        
        ob_end_clean();
        echo '<!DOCTYPE html><html><head><meta http-equiv="refresh" content="0;url=?route=dashboard_admin&view=horarios"></head></html>';
        exit;
    }

    if (isset($_GET['action']) && $_GET['action'] === 'delete_horario' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        require_once LOGIC_PATH . '/Controllers/HorarioController.php';
        
        $horarioCtrl = new HorarioController();
        $result = $horarioCtrl->eliminarHorario($_POST['id_horario']);
        
        ob_end_clean();
        echo '<!DOCTYPE html><html><head><meta http-equiv="refresh" content="0;url=?route=dashboard_admin&view=horarios"></head></html>';
        exit;
    }

    if (isset($_GET['action']) && $_GET['action'] === 'asignar_horario' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        require_once LOGIC_PATH . '/Controllers/HorarioController.php';
        
        $horarioCtrl = new HorarioController();
        $result = $horarioCtrl->asignarHorario($_POST['id_personal'], $_POST['id_horario']);
        
        ob_end_clean();
        echo '<!DOCTYPE html><html><head><meta http-equiv="refresh" content="0;url=?route=dashboard_admin&view=horarios"></head></html>';
        exit;
    }

    if (isset($_GET['action']) && $_GET['action'] === 'desasignar_horario' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        require_once LOGIC_PATH . '/Controllers/HorarioController.php';
        
        $horarioCtrl = new HorarioController();
        $result = $horarioCtrl->desasignarHorario($_POST['id_personal'], $_POST['id_horario']);
        
        ob_end_clean();
        echo '<!DOCTYPE html><html><head><meta http-equiv="refresh" content="0;url=?route=dashboard_admin&view=horarios"></head></html>';
        exit;
    }
}

if (isset($_GET['message'])) {
    if ($_GET['message'] === 'registered') {
        $message = 'Registro exitoso. Por favor, inicie sesión.';
        $messageType = 'success';
    }
}

if (empty($message) && isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $messageType = $_SESSION['messageType'] ?? 'info';
    unset($_SESSION['message'], $_SESSION['messageType']);
}

$routes = [
    'landing' => [
        'title' => 'Q-Line - Gestión de Salud',
        'view' => 'views/landing.php',
        'css' => 'landing.css'
    ],
    'register' => [
        'title' => 'Registro - Q-Line',
        'view' => 'views/register.php',
        'css' => 'register.css'
    ],
    'login' => [
        'title' => 'Iniciar Sesión - Q-Line',
        'view' => 'views/login.php',
        'css' => 'login.css'
    ],
    'dashboard' => [
        'title' => 'Dashboard - Q-Line',
        'view' => 'views/dashboard.php',
        'css' => 'dashboard.css'
    ],
    'dashboard_paciente' => [
        'title' => 'Dashboard Paciente - Q-Line',
        'view' => 'views/dashboard_paciente.php',
        'css' => 'dashboard.css'
    ],
    'dashboard_medico' => [
        'title' => 'Dashboard Médico - Q-Line',
        'view' => 'views/dashboard_medico.php',
        'css' => 'dashboard.css'
    ],
    'dashboard_admin' => [
        'title' => 'Dashboard Administrador - Q-Line',
        'view' => 'views/dashboard_admin.php',
        'css' => 'dashboard.css'
    ],
    'solicitar_turno' => [
        'title' => 'Solicitar Turno - Q-Line',
        'view' => 'views/solicitar_turno.php',
        'css' => 'dashboard.css'
    ],
    'ticket_confirmado' => [
        'title' => 'Turno Confirmado - Q-Line',
        'view' => 'views/ticket_confirmado.php',
        'css' => 'dashboard.css'
    ],
    'forgot_password' => [
        'title' => 'Recuperar Contraseña - Q-Line',
        'view' => 'views/forgot_password.php',
        'css' => 'register.css'
    ],
    'monitor' => [
        'title' => 'Monitor - Sala de Espera Q-Line',
        'view' => 'views/monitor.php',
        'css' => 'monitor.css'
    ],
    'privacy' => [
        'title' => 'Política de Privacidad - Q-Line',
        'view' => 'views/privacy.php',
        'css' => 'register.css'
    ]
];

$userId = $_SESSION['id_usuario'] ?? $_SESSION['user_id'] ?? 0;

if (isset($_GET['action']) && $_GET['action'] === 'update_profile' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once LOGIC_PATH . '/Controllers/UserController.php';
    
    $userCtrl = new UserController();
    $result = $userCtrl->actualizarPerfil($userId, $_POST['nombre'], $_POST['apellido']);
    
    $_SESSION['message'] = $result['success'] ? 'Perfil actualizado correctamente' : 'Error al actualizar perfil';
    $_SESSION['messageType'] = $result['success'] ? 'success' : 'error';
    
    ob_end_clean();
    echo '<!DOCTYPE html><html><head><meta http-equiv="refresh" content="0;url=?route=dashboard_paciente&section=perfil"></head></html>';
    exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'update_all' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once LOGIC_PATH . '/Controllers/UserController.php';
    
    $data = [
        'nombre' => $_POST['nombre'] ?? '',
        'apellido' => $_POST['apellido'] ?? '',
        'documento' => $_POST['documento'] ?? '',
        'telefono' => $_POST['telefono'] ?? '',
        'direccion' => $_POST['direccion'] ?? '',
        'fecha_nacimiento' => $_POST['fecha_nacimiento'] ?? '',
        'email' => $_POST['email'] ?? '',
        'grupo_sanguineo' => $_POST['grupo_sanguineo'] ?? '',
        'num_seguro' => $_POST['num_seguro'] ?? ''
    ];
    
    $userCtrl = new UserController();
    $result = $userCtrl->actualizarDatosCompletos($userId, $data);
    
    if (is_array($result) && isset($result['success'])) {
        $_SESSION['message'] = $result['message'];
        $_SESSION['messageType'] = $result['success'] ? 'success' : 'error';
    } else {
        $_SESSION['message'] = $result ? 'Datos actualizados correctamente' : 'Error al actualizar datos';
        $_SESSION['messageType'] = $result ? 'success' : 'error';
    }
    
    ob_end_clean();
    echo '<!DOCTYPE html><html><head><meta http-equiv="refresh" content="0;url=?route=dashboard_paciente&section=perfil"></head></html>';
    exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'change_password' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once LOGIC_PATH . '/Controllers/UserController.php';
    
    $userCtrl = new UserController();
    $result = $userCtrl->cambiarPassword($userId, $_POST['password_actual'], $_POST['password_nueva'], $_POST['password_confirmar']);
    
    $_SESSION['message'] = $result['message'];
    $_SESSION['messageType'] = $result['success'] ? 'success' : 'error';
    
    ob_end_clean();
    echo '<!DOCTYPE html><html><head><meta http-equiv="refresh" content="0;url=?route=dashboard_paciente&section=perfil"></head></html>';
    exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'confirmar_ticket' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once LOGIC_PATH . '/Controllers/TicketController.php';

    $ticketCtrl = new TicketController();
    $id_especialidad = $_POST['id_especialidad'] ?? 0;
    $prioridad = $_POST['prioridad'] ?? 'Verde';
    $sintomas = $_POST['sintomas'] ?? '';
    $result = $ticketCtrl->solicitarTicket($userId, $id_especialidad, $prioridad, $sintomas);

    ob_end_clean();

    if ($result['success']) {
        $_SESSION['ultimo_ticket'] = $result['data']['ticket'];
        $_SESSION['personas_antes'] = $result['data']['personas_antes'];
        echo '<!DOCTYPE html><html><head><meta http-equiv="refresh" content="0;url=?route=ticket_confirmado"></head></html>';
    } else {
        $_SESSION['message'] = $result['message'];
        $_SESSION['messageType'] = 'error';
        echo '<!DOCTYPE html><html><head><meta http-equiv="refresh" content="0;url=?route=solicitar_turno"></head></html>';
    }
    exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'cancelar_ticket' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once LOGIC_PATH . '/Controllers/TicketController.php';

    $ticketCtrl = new TicketController();
    $id_ticket = $_POST['id_ticket'] ?? 0;
    $result = $ticketCtrl->cancelarTicket($userId, $id_ticket);

    $_SESSION['message'] = $result['message'];
    $_SESSION['messageType'] = $result['success'] ? 'success' : 'error';

    ob_end_clean();
    echo '<!DOCTYPE html><html><head><meta http-equiv="refresh" content="0;url=?route=dashboard_paciente&section=tickets"></head></html>';
    exit;
}

if ($route === 'forgot_password' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once LOGIC_PATH . '/Models/Usuario.php';

    $email = $_POST['email'] ?? '';
    $usuario = Usuario::buscarPorEmail($email);

    ob_end_clean();
    if ($usuario) {
        echo '<!DOCTYPE html><html><head><meta http-equiv="refresh" content="0;url=?route=forgot_password&sent=1&email=' . urlencode($email) . '"></head></html>';
    } else {
        echo '<!DOCTYPE html><html><head><meta http-equiv="refresh" content="0;url=?route=forgot_password&error=1&email=' . urlencode($email) . '"></head></html>';
    }
    exit;
}

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

if (isset($_GET['action']) && $_GET['action'] === 'update_limite' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once LOGIC_PATH . '/Controllers/AuthController.php';
    require_once LOGIC_PATH . '/Controllers/MedicoController.php';

    $auth = new AuthController();
    $user = $auth->getUser();

    if ($user && $user['rol'] == 2) {
        $medicoCtrl = new MedicoController($user['id_persona']);
        $limite = intval($_POST['limite_diario'] ?? 0);
        $result = $medicoCtrl->actualizarLimiteDiario($limite);
        $_SESSION['message'] = $result['message'];
        $_SESSION['messageType'] = $result['success'] ? 'success' : 'error';
    }

    ob_end_clean();
    echo '<!DOCTYPE html><html><head><meta http-equiv="refresh" content="0;url=?route=dashboard_medico&section=perfil"></head></html>';
    exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'guardar_atencion' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once LOGIC_PATH . '/Controllers/AuthController.php';
    require_once LOGIC_PATH . '/Controllers/MedicoController.php';

    $auth = new AuthController();
    $user = $auth->getUser();

    if ($user && $user['rol'] == 2) {
        $medicoCtrl = new MedicoController($user['id_persona']);
        $result = $medicoCtrl->iniciarAtencion(
            $_POST['id_ticket'],
            $_POST['tipo_diagnostico'],
            $_POST['descripcion_diagnostico'],
            $_POST['tratamiento_prescrito'] ?? '',
            $_POST['fecha_proxima_cita'] ?: null
        );

        $_SESSION['message'] = $result['message'];
        $_SESSION['messageType'] = $result['success'] ? 'success' : 'error';
    }

    ob_end_clean();
    echo '<!DOCTYPE html><html><head><meta http-equiv="refresh" content="0;url=?route=dashboard_medico&section=atencion"></head></html>';
    exit;
}

$currentRoute = $routes[$route] ?? $routes['landing'];
$pageTitle = $currentRoute['title'];
$viewPath = PRESENTATION_PATH . '/' . $currentRoute['view'];
$cssFile = $currentRoute['css'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <link rel="stylesheet" href="css/<?= $cssFile ?>">
    <?php if ($message): ?>
    <style>
        .alert { padding: 15px; margin: 20px; border-radius: 8px; text-align: center; }
        .alert-error { background-color: #fee2e2; color: #dc2626; border: 1px solid #fecaca; }
        .alert-success { background-color: #d1fae5; color: #059669; border: 1px solid #a7f3d0; }
    </style>
    <?php endif; ?>
</head>
<body>
    <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?>"><?= $message ?></div>
    <?php endif; ?>
    <?php include $viewPath; ?>
</body>
</html>
<?php ob_end_flush(); ?>