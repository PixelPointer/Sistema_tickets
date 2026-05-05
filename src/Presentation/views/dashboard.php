<?php 
require_once dirname(__DIR__, 2) . '/Logic/Controllers/AuthController.php';
$auth = new AuthController();
$user = $auth->getUser();

if (!$user) {
    header('Location: ?route=login');
    exit;
}
$pageTitle = "Dashboard - Q-Line";
?>

<header>
    <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
        <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-2 10h-4v4h-2v-4H7v-2h4V7h2v4h4v2z"/>
    </svg>
    Q-Line
    <span class="user-info">| Bienvenido, <?= $user['nombre'] . ' ' . $user['apellido'] ?></span>
    <a href="?route=logout" class="logout-btn">Cerrar Sesión</a>
</header>

<div class="dashboard-content">
    <h1>Panel de Control</h1>
    <p>Gestión de Tickets y Citas Médicas</p>
</div>

<style>
body { margin: 0; font-family: 'Segoe UI', sans-serif; }
header {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 20px 40px;
    background: #066931;
    color: white;
}
.user-info {
    margin-left: auto;
    font-size: 0.9rem;
}
.logout-btn {
    color: white;
    text-decoration: none;
    padding: 8px 16px;
    border: 1px solid white;
    border-radius: 4px;
    margin-left: 15px;
}
.logout-btn:hover {
    background: white;
    color: #066931;
}
.dashboard-content {
    padding: 40px;
    text-align: center;
}
.dashboard-content h1 {
    color: #066931;
    margin-bottom: 5px;
}
.dashboard-content p {
    color: #718096;
}
</style>