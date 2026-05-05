<?php
ob_start();

define('BASE_PATH', dirname(__DIR__));
define('PRESENTATION_PATH', BASE_PATH . '/src/Presentation');
define('LOGIC_PATH', BASE_PATH . '/src/Logic');

$route = $_GET['route'] ?? 'landing';
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' || $route === 'logout') {
    require_once LOGIC_PATH . '/Controllers/AuthController.php';
    
    $auth = new AuthController();

    if ($route === 'logout') {
        $auth->logout();
        header('Location: ?route=landing');
        exit;
    }

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

    if ($route === 'login') {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        $result = $auth->login($email, $password);
        
        if ($result['success']) {
            ob_end_clean();
            echo '<!DOCTYPE html><html><head><meta http-equiv="refresh" content="0;url=?route=dashboard"></head></html>';
            exit;
        } else {
            $message = $result['message'];
            $messageType = 'error';
        }
    }
}

if (isset($_GET['message'])) {
    if ($_GET['message'] === 'registered') {
        $message = 'Registro exitoso. Por favor, inicie sesión.';
        $messageType = 'success';
    }
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
    ]
];

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