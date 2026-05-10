<?php 
require_once dirname(__DIR__, 2) . '/Logic/Controllers/AuthController.php';
require_once dirname(__DIR__, 2) . '/Logic/Controllers/UserController.php';
require_once dirname(__DIR__, 2) . '/Logic/Controllers/TicketController.php';

$auth = new AuthController();
$user = $auth->getUser();

if (!$user || $user['rol'] != 3) {
    header('Location: ?route=login');
    exit;
}

$activeSection = $_GET['section'] ?? 'dashboard';

$userCtrl = new UserController();
$datosPaciente = $userCtrl->obtenerDatosPaciente($_SESSION['id_usuario'] ?? $_SESSION['user_id'] ?? 0);

if (!$datosPaciente) {
    $datosPaciente = [
        'nombre' => $user['nombre'] ?? '',
        'apellido' => $user['apellido'] ?? '',
        'documento' => $user['documento'] ?? '',
        'telefono' => $user['telefono'] ?? '',
        'direccion' => $user['direccion'] ?? '',
        'fecha_nacimiento' => $user['fecha_nacimiento'] ?? '',
        'email' => $user['nombre_usuario'] ?? '',
        'grupo_sanguineo' => '',
        'num_seguro' => ''
    ];
}

$citasProximas = [];
if ($activeSection === 'citas') {
    $citasProximas = $userCtrl->listarCitasPaciente($_SESSION['id_usuario'] ?? $_SESSION['user_id'] ?? 0);
}

$pageTitle = "Dashboard Paciente - Q-Line";
?>

<link rel="stylesheet" href="css/dashboard-common.css">

<div class="desktop-only">
    <aside class="sidebar">
        <div class="logo-section">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-2 10h-4v4h-2v-4H7v-2h4V7h2v4h4v2z"/></svg>
            Q-Line
        </div>
        
        <div class="sidebar-content">
            <div class="sidebar-title">Menú Principal</div>
            <a href="?route=dashboard_paciente&section=dashboard" class="menu-item <?= $activeSection === 'dashboard' ? 'active' : '' ?>">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                Dashboard
            </a>
            <a href="?route=dashboard_paciente&section=tickets" class="menu-item <?= $activeSection === 'tickets' ? 'active' : '' ?>">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path></svg>
                Mis Tickets
            </a>
            <a href="?route=dashboard_paciente&section=citas" class="menu-item <?= $activeSection === 'citas' ? 'active' : '' ?>">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                Mis Citas
            </a>
            <a href="?route=dashboard_paciente&section=perfil" class="menu-item <?= $activeSection === 'perfil' ? 'active' : '' ?>">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                Mi Perfil
            </a>
        </div>
        
        <div class="sidebar-footer">
            <a href="?route=logout" class="logout-btn">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="20" height="20"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                Cerrar Sesión
            </a>
        </div>
    </aside>
    
    <main class="main-content">
        <header class="topbar">
            <div class="user-info">
                <div class="avatar">
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($user['nombre'] . ' ' . $user['apellido']) ?>&background=white&color=066931" alt="Paciente" width="100%">
                </div>
                <div class="user-details">
                    <h4><?= htmlspecialchars($user['nombre'] . ' ' . $user['apellido']) ?></h4>
                    <span>Paciente</span>
                </div>
            </div>
        </header>
        
        <div class="page-body">
            <?php if ($activeSection === 'dashboard'): ?>
                <style>
                    .action-button { display: flex; align-items: center; background-color: #066931; padding: 20px 25px; border-radius: 12px; color: white; text-decoration: none; max-width: 500px; transition: background-color 0.3s ease; cursor: pointer; border: none; width: 100%; box-sizing: border-box; }
                    .action-button:hover { background-color: #045226; }
                    .icon-container { background-color: rgba(255, 255, 255, 0.2); width: 60px; height: 60px; border-radius: 15px; display: flex; align-items: center; justify-content: center; margin-right: 20px; flex-shrink: 0; }
                    .plus-icon { width: 35px; height: 35px; border: 2px solid white; border-radius: 50%; position: relative; display: flex; align-items: center; justify-content: center; }
                    .plus-icon::before, .plus-icon::after { content: ''; position: absolute; background-color: white; }
                    .plus-icon::before { width: 18px; height: 3px; }
                    .plus-icon::after { width: 3px; height: 18px; }
                    .text-content { flex-grow: 1; text-align: left; }
                    .title { display: block; font-size: 1.4rem; font-weight: bold; margin-bottom: 4px; letter-spacing: 0.5px; }
                    .subtitle { display: block; font-size: 0.95rem; opacity: 0.9; }
                    .arrow-icon { margin-left: 15px; border: solid white; border-width: 0 3px 3px 0; display: inline-block; padding: 6px; transform: rotate(-45deg); -webkit-transform: rotate(-45deg); }
                    .stepper { text-align: center; margin-bottom: 20px; }
                    .dots { display: flex; gap: 8px; justify-content: center; margin-bottom: 5px; }
                    .dot { width: 8px; height: 8px; background: #cbd5e0; border-radius: 50%; }
                    .dot.active { background: #066931; }
                    .stepper span { font-size: 0.8rem; color: #718096; }
                </style>
                
                <div class="stepper">
                    <div class="dots">
                        <div class="dot active"></div>
                        <div class="dot"></div>
                        <div class="dot"></div>
                    </div>
                    <span>Paso 1 de 3</span>
                </div>

                <a href="?route=solicitar_turno" class="action-button">
                    <div class="icon-container">
                        <div class="plus-icon"></div>
                    </div>
                    <div class="text-content">
                        <span class="title">OBTENER NUEVO TURNO</span>
                        <span class="subtitle">Solicite una cita médica en menos de un minuto.</span>
                    </div>
                    <div class="arrow-icon"></div>
                </a>
            <?php elseif ($activeSection === 'tickets'): ?>
                <?php
                $ticketCtrl = new TicketController();
                $ticketsData = $ticketCtrl->listarTicketsPaciente($_SESSION['id_usuario'] ?? $_SESSION['user_id'] ?? 0);
                $ticketsHoy = $ticketsData['data']['tickets_hoy'] ?? [];
                $ticketsPasados = $ticketsData['data']['tickets_pasados'] ?? [];
                ?>
                <style>
                    .tickets-container { max-width: 800px; margin: 0 auto; }
                    .tickets-title { font-size: 1.5rem; color: #2d3748; margin-bottom: 5px; }
                    .tickets-subtitle { color: #718096; margin-bottom: 25px; }
                    .ticket-card { background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; margin-bottom: 15px; transition: all 0.2s; }
                    .ticket-card.active { border-color: #066931; box-shadow: 0 0 0 2px rgba(6,105,49,0.15); }
                    .ticket-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
                    .ticket-code { font-size: 1.8rem; font-weight: 800; color: #066931; }
                    .ticket-code.past { color: #718096; }
                    .ticket-status { padding: 4px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: bold; text-transform: uppercase; }
                    .status-espera { background: #e6fffa; color: #2c7a7b; border: 1px solid #b2f5ea; }
                    .status-llamado { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
                    .status-atendido { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
                    .status-ausente { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
                    .ticket-details { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
                    .ticket-detail label { display: block; font-size: 0.7rem; color: #718096; text-transform: uppercase; font-weight: bold; margin-bottom: 2px; }
                    .ticket-detail span { font-size: 0.95rem; font-weight: 500; color: #2d3748; }
                    .section-label { font-size: 1rem; font-weight: 600; color: #2d3748; margin: 30px 0 15px; display: flex; align-items: center; gap: 8px; }
                    .section-label svg { color: #066931; }
                    .no-tickets { text-align: center; padding: 40px; background: white; border: 2px dashed #e2e8f0; border-radius: 12px; color: #718096; }
                    .no-tickets svg { margin-bottom: 10px; }
                    .active-badge { background: #066931; color: white; padding: 2px 10px; border-radius: 4px; font-size: 0.65rem; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; margin-left: 10px; vertical-align: middle; }
                    @media (max-width: 600px) { .ticket-details { grid-template-columns: 1fr; } }
                </style>
                <script>
                function confirmarCancelacion(idTicket) {
                    if (confirm('¿Está seguro de cancelar este ticket?')) {
                        var form = document.createElement('form');
                        form.method = 'POST';
                        form.action = '?route=dashboard_paciente&action=cancelar_ticket';
                        var input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'id_ticket';
                        input.value = idTicket;
                        form.appendChild(input);
                        document.body.appendChild(form);
                        form.submit();
                    }
                }
                </script>
                <div class="tickets-container">
                    <h2 class="tickets-title">Mis Tickets</h2>
                    <p class="tickets-subtitle">Historial de tickets de atención</p>

                    <?php if (!empty($ticketsHoy)): ?>
                        <div class="section-label">
                            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                            Ticket Activo
                        </div>
                        <?php foreach ($ticketsHoy as $t): ?>
                            <div class="ticket-card active">
                                <div class="ticket-header">
                                    <div>
                                        <span class="ticket-code"><?= htmlspecialchars($t['codigo_ticket']) ?></span>
                                        <span class="active-badge">Hoy</span>
                                    </div>
                                    <span class="ticket-status status-<?= strtolower($t['estado']) ?>"><?= htmlspecialchars($t['estado']) ?></span>
                                </div>
                                <div class="ticket-details">
                                    <div class="ticket-detail">
                                        <label>Especialidad</label>
                                        <span><?= htmlspecialchars($t['nombre_especialidad']) ?></span>
                                    </div>
                                    <div class="ticket-detail">
                                        <label>Consultorio</label>
                                        <span>N° <?= htmlspecialchars($t['numero_consultorio']) ?> - Piso <?= htmlspecialchars($t['piso'] ?? '-') ?></span>
                                    </div>
                                    <div class="ticket-detail">
                                        <label>Hora de creación</label>
                                        <span><?= date('h:i A', strtotime($t['fecha_creacion'])) ?></span>
                                    </div>
                                    <div class="ticket-detail">
                                        <label>Prioridad</label>
                                        <span><?= htmlspecialchars($t['prioridad']) ?></span>
                                    </div>
                                    <?php if ($t['estado'] === 'Espera'): ?>
                                        <div style="margin-top:5px;padding-top:10px;border-top:1px solid #e8e8e8;grid-column:span 2;">
                                            <div style="display:flex;gap:20px;">
                                                <div class="ticket-detail" style="flex:1;margin:0;">
                                                    <label>Personas antes que usted</label>
                                                    <span id="personas-antes" style="font-size:1.5rem;font-weight:700;color:#066931;">
                                                        <?php
                                                        $contarAntes = $ticketCtrl->contarAntes($t['id_consultorio'], $t['id_ticket']);
                                                        echo $contarAntes . ' ' . ($contarAntes === 1 ? 'persona' : 'personas');
                                                        ?>
                                                    </span>
                                                </div>
                                                <div class="ticket-detail" style="flex:1;margin:0;">
                                                    <label>Tiempo estimado</label>
                                                    <span id="tiempo-estimado" style="font-size:1.5rem;font-weight:700;color:#d97706;">
                                                        <?= $contarAntes * 10 ?> minutos
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <div style="margin-top:10px;padding-top:10px;border-top:1px solid #e2e8f0;text-align:right;grid-column:span 2;">
                                            <button onclick="confirmarCancelacion(<?= $t['id_ticket'] ?>)" style="background:none;border:1px solid #dc2626;color:#dc2626;padding:8px 16px;border-radius:8px;font-size:0.85rem;font-weight:600;cursor:pointer;transition:all 0.2s;" onmouseover="this.style.background='#dc2626';this.style.color='white'" onmouseout="this.style.background='none';this.style.color='#dc2626'">
                                                Cancelar Ticket
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="no-tickets">
                                <svg width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
                                <p style="margin:5px 0;">No tiene ningún ticket activo hoy.</p>
                                <a href="?route=solicitar_turno" style="color:#066931;font-weight:600;">Solicitar un turno</a>
                            </div>
                        <?php endif; ?>

                    <?php if (!empty($ticketsPasados)): ?>
                        <div class="section-label">
                            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Historial
                        </div>
                        <?php foreach ($ticketsPasados as $t): ?>
                            <div class="ticket-card">
                                <div class="ticket-header">
                                    <span class="ticket-code past"><?= htmlspecialchars($t['codigo_ticket']) ?></span>
                                    <span class="ticket-status status-<?= strtolower($t['estado']) ?>"><?= htmlspecialchars($t['estado']) ?></span>
                                </div>
                                <div class="ticket-details">
                                    <div class="ticket-detail">
                                        <label>Especialidad</label>
                                        <span><?= htmlspecialchars($t['nombre_especialidad']) ?></span>
                                    </div>
                                    <div class="ticket-detail">
                                        <label>Fecha</label>
                                        <span><?= date('d/m/Y', strtotime($t['fecha_creacion'])) ?></span>
                                    </div>
                                    <div class="ticket-detail">
                                        <label>Consultorio</label>
                                        <span>N° <?= htmlspecialchars($t['numero_consultorio']) ?></span>
                                    </div>
                                    <div class="ticket-detail">
                                        <label>Prioridad</label>
                                        <span><?= htmlspecialchars($t['prioridad']) ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php elseif ($activeSection === 'perfil'): ?>
                <style>
                    .perfil-container { max-width: 800px; margin: 0 auto; }
                    .perfil-header { text-align: center; margin-bottom: 40px; }
                    .avatar-wrapper { position: relative; width: 120px; height: 120px; margin: 0 auto 15px; }
                    .avatar-img { width: 100%; height: 100%; background: #e2e8f0; border-radius: 50%; border: 4px solid white; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
                    .perfil-header h1 { margin: 0; font-size: 1.8rem; color: #2d3748; }
                    .perfil-header p { margin: 5px 0 0; color: #718096; font-size: 0.9rem; }
                    .btn-edit { background: #28a745; color: white; border: none; padding: 10px 20px; border-radius: 8px; font-size: 0.9rem; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; margin-top: 10px; }
                    .btn-edit:hover { background: #1e7e34; }
                    .info-card { background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px; margin-bottom: 24px; }
                    .section-title { display: flex; align-items: center; gap: 10px; color: #28a745; font-weight: 600; font-size: 1rem; margin-bottom: 20px; }
                    .data-row { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #e2e8f0; }
                    .data-row:last-child { border-bottom: none; }
                    .data-label { color: #718096; font-size: 0.9rem; }
                    .data-value { color: #2d3748; font-weight: 500; }
                    .edit-form { display: none; }
                    .edit-form.active { display: block; }
                    .grid-form { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
                    .form-group { display: flex; flex-direction: column; gap: 8px; }
                    .form-group label { font-size: 0.8rem; font-weight: 600; color: #718096; }
                    .form-group input, .form-group textarea { padding: 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.95rem; color: #2d3748; }
                    .form-group input:disabled { background: #f7fafc; color: #718096; }
                    .full-width { grid-column: span 2; }
                    .medical-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
                    .medical-box { background: #eff6ff; padding: 15px; border-radius: 8px; }
                    .medical-box label { display: block; font-size: 0.8rem; color: #718096; margin-bottom: 5px; }
                    .medical-box div { font-weight: 600; margin-top: 4px; }
                    .actions { display: flex; justify-content: space-between; margin-top: 40px; gap: 20px; }
                    .btn { flex: 1; padding: 14px; border-radius: 10px; font-size: 1rem; font-weight: 600; cursor: pointer; border: none; }
                    .btn-cancel { background: white; border: 1.5px solid #e2e8f0; color: #718096; }
                    .btn-save { background: #28a745; color: white; }
                    .btn-save:hover { background: #1e7e34; }
                    .password-section { margin-top: 30px; padding-top: 30px; border-top: 2px dashed #e2e8f0; }
                    .danger-title { color: #dc2626 !important; }
                    @media (max-width: 600px) { .grid-form, .medical-grid { grid-template-columns: 1fr; } .full-width { grid-column: span 1; } }
                </style>
                
                <script>
                    function toggleEdit() {
                        const view = document.getElementById('view-mode');
                        const edit = document.getElementById('edit-mode');
                        if (view.style.display === 'none') {
                            view.style.display = 'block';
                            edit.style.display = 'none';
                        } else {
                            view.style.display = 'none';
                            edit.style.display = 'block';
                        }
                    }
                </script>
                
                <div class="perfil-container">
                    <div class="perfil-header">
                        <div class="avatar-wrapper">
                            <img src="https://ui-avatars.com/api/?name=<?= urlencode($datosPaciente['nombre'] . ' ' . $datosPaciente['apellido']) ?>&background=28a745&color=white&size=120" class="avatar-img">
                        </div>
                        <h1><?= htmlspecialchars(($datosPaciente['nombre'] ?? '') . ' ' . ($datosPaciente['apellido'] ?? '')) ?></h1>
                        <p>Paciente</p>
                        <button class="btn-edit" onclick="toggleEdit()">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                            Editar Información
                        </button>
                    </div>

                    <!-- Vista de datos (solo lectura) -->
                    <div id="view-mode">
                        <div class="info-card">
                            <div class="section-title">
                                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                                Datos Personales
                            </div>
                            <div class="data-row">
                                <span class="data-label">Nombres</span>
                                <span class="data-value"><?= htmlspecialchars($datosPaciente['nombre'] ?? '') ?></span>
                            </div>
                            <div class="data-row">
                                <span class="data-label">Apellidos</span>
                                <span class="data-value"><?= htmlspecialchars($datosPaciente['apellido'] ?? '') ?></span>
                            </div>
                            <div class="data-row">
                                <span class="data-label">Documento</span>
                                <span class="data-value"><?= htmlspecialchars($datosPaciente['documento'] ?? '') ?></span>
                            </div>
                            <div class="data-row">
                                <span class="data-label">Teléfono</span>
                                <span class="data-value"><?= htmlspecialchars($datosPaciente['telefono'] ?? '') ?></span>
                            </div>
                            <div class="data-row">
                                <span class="data-label">Fecha de Nacimiento</span>
                                <span class="data-value"><?= htmlspecialchars($datosPaciente['fecha_nacimiento'] ?? '') ?></span>
                            </div>
                        </div>

                        <div class="info-card">
                            <div class="section-title">
                                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 8l7.89 5.26a2 2 0 0 0 2.22 0L21 8M5 19h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2z"></path></svg>
                                Correo Electrónico
                            </div>
                            <div class="data-row">
                                <span class="data-label">Email</span>
                                <span class="data-value"><?= htmlspecialchars($datosPaciente['email'] ?? '') ?></span>
                            </div>
                            <div class="data-row">
                                <span class="data-label">Dirección</span>
                                <span class="data-value"><?= htmlspecialchars($datosPaciente['direccion'] ?? '') ?></span>
                            </div>
                        </div>

                        <div class="info-card">
                            <div class="section-title">
                                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 21l-2-2m-2-2l-2-2a5 5 0 1 1 7.5-6.572A5 5 0 1 1 18 9l-2 2-2 2-2 2z"></path></svg>
                                Datos Médicos
                            </div>
                            <div class="data-row">
                                <span class="data-label">Tipo de Sangre</span>
                                <span class="data-value"><?= htmlspecialchars($datosPaciente['grupo_sanguineo'] ?? '') ?></span>
                            </div>
                            <div class="data-row">
                                <span class="data-label">Número de Seguro Médico</span>
                                <span class="data-value"><?= htmlspecialchars($datosPaciente['num_seguro'] ?? '') ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Formulario de edición -->
                    <div id="edit-mode" class="edit-form">
                        <form method="POST" action="?route=dashboard_paciente&action=update_all" autocomplete="on">
                            <div class="info-card">
                                <div class="section-title">
                                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                                    Datos Personales
                                </div>
                                <div class="grid-form">
                                    <div class="form-group">
                                        <label>Nombres</label>
                                        <input type="text" name="nombre" value="<?= htmlspecialchars($datosPaciente['nombre'] ?? '') ?>" autocomplete="given-name" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Apellidos</label>
                                        <input type="text" name="apellido" value="<?= htmlspecialchars($datosPaciente['apellido'] ?? '') ?>" autocomplete="family-name" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Documento</label>
                                        <input type="text" name="documento" value="<?= htmlspecialchars($datosPaciente['documento'] ?? '') ?>" autocomplete="off" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Teléfono</label>
                                        <input type="tel" name="telefono" value="<?= htmlspecialchars($datosPaciente['telefono'] ?? '') ?>" autocomplete="tel">
                                    </div>
                                    <div class="form-group full-width">
                                        <label>Fecha de Nacimiento</label>
                                        <input type="date" name="fecha_nacimiento" value="<?= htmlspecialchars($datosPaciente['fecha_nacimiento'] ?? '') ?>" autocomplete="bday" required>
                                    </div>
                                </div>
                            </div>

                            <div class="info-card">
                                <div class="section-title">
                                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 8l7.89 5.26a2 2 0 0 0 2.22 0L21 8M5 19h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2z"></path></svg>
                                    Contacto
                                </div>
                                <div class="grid-form">
                                    <div class="form-group full-width">
                                        <label>Correo Electrónico</label>
                                        <input type="email" name="email" value="<?= htmlspecialchars($datosPaciente['email'] ?? '') ?>" autocomplete="email" required>
                                    </div>
                                    <div class="form-group full-width">
                                        <label>Dirección</label>
                                        <textarea name="direccion" autocomplete="street-address"><?= htmlspecialchars($datosPaciente['direccion'] ?? '') ?></textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="info-card">
                                <div class="section-title">
                                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 21l-2-2m-2-2l-2-2a5 5 0 1 1 7.5-6.572A5 5 0 1 1 18 9l-2 2-2 2-2 2z"></path></svg>
                                    Datos Médicos
                                </div>
                                <div class="grid-form">
                                    <div class="form-group">
                                        <label>Tipo de Sangre</label>
                                        <input type="text" name="grupo_sanguineo" value="<?= htmlspecialchars($datosPaciente['grupo_sanguineo'] ?? '') ?>" autocomplete="off" list="grupo-sanguineo-list">
                                        <datalist id="grupo-sanguineo-list">
                                            <option value="A+"><option value="A-"><option value="B+"><option value="B-">
                                            <option value="AB+"><option value="AB-"><option value="O+"><option value="O-">
                                        </datalist>
                                    </div>
                                    <div class="form-group">
                                        <label>Número de Seguro Médico</label>
                                        <input type="text" name="num_seguro" value="<?= htmlspecialchars($datosPaciente['num_seguro'] ?? '') ?>" autocomplete="off">
                                    </div>
                                </div>
                            </div>

                            <div class="actions">
                                <button type="button" class="btn btn-cancel" onclick="toggleEdit()">Cancelar</button>
                                <button type="submit" class="btn btn-save">Guardar Cambios</button>
                            </div>
                        </form>
                    </div>

                    <div class="info-card password-section">
                        <div class="section-title danger-title">
                            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                            Cambiar Contraseña
                        </div>
                        <form method="POST" action="?route=dashboard_paciente&action=change_password">
                            <div class="grid-form">
                                <div class="form-group">
                                    <label>Contraseña Actual</label>
                                    <div class="password-wrapper">
                                        <input type="password" name="password_actual" required>
                                        <button type="button" class="toggle-password" onclick="togglePasswordVisibility(this)">
                                            <svg class="eye-open" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                            <svg class="eye-closed" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
                                        </button>
                                    </div>
                                </div>
                                <div class="form-group"></div>
                                <div class="form-group">
                                    <label>Nueva Contraseña</label>
                                    <div class="password-wrapper">
                                        <input type="password" name="password_nueva" required>
                                        <button type="button" class="toggle-password" onclick="togglePasswordVisibility(this)">
                                            <svg class="eye-open" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                            <svg class="eye-closed" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
                                        </button>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Confirmar Contraseña</label>
                                    <div class="password-wrapper">
                                        <input type="password" name="password_confirmar" required>
                                        <button type="button" class="toggle-password" onclick="togglePasswordVisibility(this)">
                                            <svg class="eye-open" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                            <svg class="eye-closed" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-save" style="margin-top: 20px;">Cambiar Contraseña</button>
                        </form>
                    </div>
                </div>
            
            <?php elseif ($activeSection === 'citas'): ?>
                <style>
                    .citas-container { max-width: 800px; margin: 0 auto; }
                    .cita-card { background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; margin-bottom: 15px; transition: all 0.2s; }
                    .cita-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
                    .cita-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
                    .cita-date { font-size: 1.1rem; font-weight: 700; color: #066931; }
                    .cita-code { font-size: 0.85rem; color: #718096; }
                    .cita-details { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
                    .cita-detail label { display: block; font-size: 0.7rem; color: #718096; text-transform: uppercase; font-weight: bold; }
                    .cita-detail span { font-size: 0.95rem; color: #2d3748; }
                    .no-citas { text-align: center; padding: 50px; background: white; border: 2px dashed #e2e8f0; border-radius: 12px; color: #718096; }
                    .no-citas svg { margin-bottom: 10px; }
                </style>
                <div class="citas-container">
                    <h2 class="section-title">Mis Citas Programadas</h2>
                    <p style="color:#718096;margin-bottom:25px;">Próximas citas de seguimiento indicadas por su médico</p>
                    <?php if (!empty($citasProximas)): ?>
                        <?php foreach ($citasProximas as $cita): ?>
                            <div class="cita-card">
                                <div class="cita-header">
                                    <div class="cita-date"><?= date('d/m/Y', strtotime($cita['fecha_proxima_cita'])) ?></div>
                                    <span class="cita-code"><?= htmlspecialchars($cita['codigo_ticket']) ?></span>
                                </div>
                                <div class="cita-details">
                                    <div class="cita-detail">
                                        <label>Especialidad</label>
                                        <span><?= htmlspecialchars($cita['nombre_especialidad']) ?></span>
                                    </div>
                                    <div class="cita-detail">
                                        <label>Consultorio</label>
                                        <span>N° <?= htmlspecialchars($cita['numero_consultorio']) ?> - Piso <?= htmlspecialchars($cita['piso'] ?? '-') ?></span>
                                    </div>
                                    <div class="cita-detail">
                                        <label>Médico</label>
                                        <span><?= htmlspecialchars($cita['medico_nombre'] . ' ' . $cita['medico_apellido']) ?></span>
                                    </div>
                                    <div class="cita-detail">
                                        <label>Diagnóstico</label>
                                        <span><?= htmlspecialchars($cita['tipo_diagnostico']) ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-citas">
                            <svg width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            <p style="margin:5px 0;">No tiene citas programadas próximamente.</p>
                            <a href="?route=solicitar_turno" style="color:#066931;font-weight:600;">Solicitar un turno</a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <h2 class="section-title">Bienvenido, <?= htmlspecialchars($user['nombre']) ?></h2>
                <p style="color:#718096;">Seleccione una opción del menú lateral</p>
            <?php endif; ?>
        </div>
    </main>
</div>

<script>
(function() {
    var activeTicket = <?= !empty($ticketsHoy) ? json_encode($ticketsHoy[0]) : 'null' ?>;
    if (!activeTicket) return;

    if ('Notification' in window && Notification.permission === 'default') {
        Notification.requestPermission();
    }

    var lastEstado = activeTicket.estado;
    var polling = setInterval(function() {
        fetch('?route=api&action=check_ticket&id_ticket=' + activeTicket.id_ticket)
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data.success) return;

                var estadoElem = document.querySelector('.ticket-status');
                var personasElem = document.getElementById('personas-antes');
                var tiempoElem = document.getElementById('tiempo-estimado');

                if (estadoElem) {
                    var oldClass = estadoElem.className;
                    estadoElem.textContent = data.estado;
                    estadoElem.className = 'ticket-status status-' + data.estado.toLowerCase();
                }

                if (personasElem) {
                    personasElem.textContent = data.personas_antes + ' ' + (data.personas_antes === 1 ? 'persona' : 'personas');
                }
                if (tiempoElem) {
                    tiempoElem.textContent = data.hora_estimada_minutos + ' minutos';
                }

                if (data.estado !== lastEstado) {
                    lastEstado = data.estado;
                    if (data.estado === 'Llamado' && 'Notification' in window && Notification.permission === 'granted') {
                        new Notification('¡Hora de atención!', {
                            body: 'Su ticket ' + data.codigo_ticket + ' ha sido llamado. Diríjase al consultorio N°' + data.consultorio + '.',
                            icon: 'https://ui-avatars.com/api/?name=Q-Line&background=066931&color=white'
                        });
                        clearInterval(polling);
                    }
                }
            })
            .catch(function() {});
    }, 15000);
})();
</script>

<!-- Mobile Version -->
<div class="mobile-only">
    <header class="mobile-header">
        <div class="hamburger" onclick="document.getElementById('mobileNav').classList.toggle('open')">
            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16"></path></svg>
        </div>
        <span class="mobile-logo">Q-Line</span>
    </header>
    
    <nav id="mobileNav" class="mobile-nav">
        <a href="?route=dashboard_paciente&section=dashboard" class="mobile-nav-item <?= $activeSection === 'dashboard' ? 'active' : '' ?>">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
            Dashboard
        </a>
        <a href="?route=dashboard_paciente&section=tickets" class="mobile-nav-item <?= $activeSection === 'tickets' ? 'active' : '' ?>">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path></svg>
            Mis Tickets
        </a>
        <a href="?route=dashboard_paciente&section=citas" class="mobile-nav-item <?= $activeSection === 'citas' ? 'active' : '' ?>">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
            Mis Citas
        </a>
        <a href="?route=dashboard_paciente&section=perfil" class="mobile-nav-item <?= $activeSection === 'perfil' ? 'active' : '' ?>">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
            Mi Perfil
        </a>
        <a href="?route=logout" class="mobile-nav-item logout">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
            Cerrar Sesión
        </a>
    </nav>
    
    <main class="mobile-content">
        <?php if ($activeSection === 'tickets'): ?>
            <style>
                .tickets-container { max-width: 600px; margin: 0 auto; }
                .tickets-title { font-size: 1.2rem; color: #2d3748; margin-bottom: 5px; }
                .tickets-subtitle { color: #718096; font-size: 0.85rem; margin-bottom: 20px; }
                .ticket-card { background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; margin-bottom: 12px; }
                .ticket-card.active { border-color: #066931; box-shadow: 0 0 0 2px rgba(6,105,49,0.15); }
                .ticket-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
                .ticket-code { font-size: 1.4rem; font-weight: 800; color: #066931; }
                .ticket-code.past { color: #718096; }
                .ticket-status { padding: 3px 10px; border-radius: 20px; font-size: 0.7rem; font-weight: bold; text-transform: uppercase; }
                .status-espera { background: #e6fffa; color: #2c7a7b; border: 1px solid #b2f5ea; }
                .status-llamado { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
                .status-atendido { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
                .status-ausente { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
                .ticket-detail { margin-bottom: 8px; }
                .ticket-detail label { display: block; font-size: 0.65rem; color: #718096; text-transform: uppercase; font-weight: bold; }
                .ticket-detail span { font-size: 0.9rem; font-weight: 500; color: #2d3748; }
                .section-label { font-size: 0.9rem; font-weight: 600; color: #2d3748; margin: 20px 0 12px; display: flex; align-items: center; gap: 6px; }
                .no-tickets { text-align: center; padding: 30px; background: white; border: 2px dashed #e2e8f0; border-radius: 12px; color: #718096; }
                .active-badge { background: #066931; color: white; padding: 2px 8px; border-radius: 4px; font-size: 0.6rem; font-weight: bold; text-transform: uppercase; margin-left: 8px; vertical-align: middle; }
            </style>
            <div class="tickets-container">
                <h2 class="tickets-title">Mis Tickets</h2>
                <p class="tickets-subtitle">Historial de tickets de atención</p>

                <?php if (!empty($ticketsHoy)): ?>
                    <div class="section-label">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                        Ticket Activo
                    </div>
                    <?php foreach ($ticketsHoy as $t): ?>
                        <div class="ticket-card active">
                            <div class="ticket-header">
                                <div>
                                    <span class="ticket-code"><?= htmlspecialchars($t['codigo_ticket']) ?></span>
                                    <span class="active-badge">Hoy</span>
                                </div>
                                <span class="ticket-status status-<?= strtolower($t['estado']) ?>"><?= htmlspecialchars($t['estado']) ?></span>
                            </div>
                            <div class="ticket-detail"><label>Especialidad</label><span><?= htmlspecialchars($t['nombre_especialidad']) ?></span></div>
                            <div class="ticket-detail"><label>Consultorio</label><span>N° <?= htmlspecialchars($t['numero_consultorio']) ?> - Piso <?= htmlspecialchars($t['piso'] ?? '-') ?></span></div>
                            <div class="ticket-detail"><label>Hora</label><span><?= date('h:i A', strtotime($t['fecha_creacion'])) ?></span></div>
                            <div class="ticket-detail"><label>Prioridad</label><span><?= htmlspecialchars($t['prioridad']) ?></span></div>
                            <?php if ($t['estado'] === 'Espera'): ?>
                                <button onclick="confirmarCancelacion(<?= $t['id_ticket'] ?>)" style="margin-top:12px;padding-top:12px;border-top:1px solid #e2e8f0;width:100%;background:none;border-left:none;border-right:none;border-bottom:none;border-top:1px solid #e2e8f0;color:#dc2626;font-size:0.85rem;font-weight:600;cursor:pointer;text-align:center;padding:10px;">
                                    Cancelar Ticket
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-tickets">
                        <svg width="40" height="40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
                        <p style="margin:5px 0;">No tiene ningún ticket activo hoy.</p>
                        <a href="?route=solicitar_turno" style="color:#066931;font-weight:600;">Solicitar un turno</a>
                    </div>
                <?php endif; ?>

                <?php if (!empty($ticketsPasados)): ?>
                    <div class="section-label">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Historial
                    </div>
                    <?php foreach ($ticketsPasados as $t): ?>
                        <div class="ticket-card">
                            <div class="ticket-header">
                                <span class="ticket-code past"><?= htmlspecialchars($t['codigo_ticket']) ?></span>
                                <span class="ticket-status status-<?= strtolower($t['estado']) ?>"><?= htmlspecialchars($t['estado']) ?></span>
                            </div>
                            <div class="ticket-detail"><label>Especialidad</label><span><?= htmlspecialchars($t['nombre_especialidad']) ?></span></div>
                            <div class="ticket-detail"><label>Fecha</label><span><?= date('d/m/Y', strtotime($t['fecha_creacion'])) ?></span></div>
                            <div class="ticket-detail"><label>Consultorio</label><span>N° <?= htmlspecialchars($t['numero_consultorio']) ?></span></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        <?php elseif ($activeSection === 'citas'): ?>
            <h2 class="mobile-title">Mis Citas Programadas</h2>
            <?php if (!empty($citasProximas)): ?>
                <?php foreach ($citasProximas as $cita): ?>
                    <div class="mobile-card">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                            <strong style="color:#066931;"><?= date('d/m/Y', strtotime($cita['fecha_proxima_cita'])) ?></strong>
                            <small style="color:#718096;"><?= htmlspecialchars($cita['codigo_ticket']) ?></small>
                        </div>
                        <p style="margin:2px 0;"><?= htmlspecialchars($cita['nombre_especialidad']) ?> · Cons. <?= htmlspecialchars($cita['numero_consultorio']) ?></p>
                        <p style="margin:2px 0;color:var(--text-muted);font-size:0.8rem;">Dr. <?= htmlspecialchars($cita['medico_nombre'] . ' ' . $cita['medico_apellido']) ?></p>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="mobile-card">
                    <h3>Sin citas programadas</h3>
                    <p>No tiene citas de seguimiento próximas</p>
                </div>
            <?php endif; ?>
        <?php elseif ($activeSection === 'perfil'): ?>
            <style>
                .perfil-container { max-width: 600px; margin: 0 auto; }
                .perfil-header { text-align: center; margin-bottom: 20px; }
                .avatar-wrapper { width: 80px; height: 80px; margin: 0 auto 12px; }
                .avatar-img { width: 100%; height: 100%; background: #e2e8f0; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
                .perfil-header h1 { margin: 0; font-size: 1.4rem; color: #2d3748; }
                .perfil-header p { margin: 5px 0 0; color: #718096; font-size: 0.85rem; }
                .btn-edit { background: #28a745; color: white; border: none; padding: 10px 16px; border-radius: 8px; font-size: 0.85rem; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; margin-top: 10px; }
                .info-card { background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; margin-bottom: 12px; }
                .section-title { display: flex; align-items: center; gap: 8px; color: #28a745; font-weight: 600; font-size: 0.9rem; margin-bottom: 12px; }
                .data-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #e2e8f0; }
                .data-row:last-child { border-bottom: none; }
                .data-label { color: #718096; font-size: 0.8rem; }
                .data-value { color: #2d3748; font-weight: 500; font-size: 0.85rem; }
                .edit-form { display: none; }
                .edit-form.active { display: block; }
                .form-group { margin-bottom: 10px; }
                .form-group label { display: block; font-size: 0.75rem; font-weight: 600; color: #718096; margin-bottom: 4px; }
                .form-group input, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.9rem; box-sizing: border-box; }
                .form-group input:disabled { background: #f7fafc; color: #718096; }
                .medical-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
                .medical-box { background: #eff6ff; padding: 10px; border-radius: 8px; }
                .medical-box label { display: block; font-size: 0.7rem; color: #718096; }
                .medical-box div { font-weight: 600; margin-top: 4px; font-size: 0.85rem; }
                .btn { width: 100%; padding: 12px; border-radius: 10px; font-size: 0.95rem; font-weight: 600; border: none; cursor: pointer; margin-top: 10px; }
                .btn-cancel { background: white; border: 1.5px solid #e2e8f0; color: #718096; }
                .btn-save { background: #28a745; color: white; }
                .danger-title { color: #dc2626 !important; }
                .password-section { margin-top: 16px; padding-top: 16px; border-top: 2px dashed #e2e8f0; }
            </style>
            
            <script>
                function toggleEdit() {
                    document.getElementById('view-mode').style.display = 'none';
                    document.getElementById('edit-mode').style.display = 'block';
                }
            </script>
            
            <div class="perfil-container">
                <div class="perfil-header">
                    <div class="avatar-wrapper">
                        <img src="https://ui-avatars.com/api/?name=<?= urlencode(($datosPaciente['nombre'] ?? '') . ' ' . ($datosPaciente['apellido'] ?? '')) ?>&background=28a745&color=white&size=80" class="avatar-img">
                    </div>
                    <h1><?= htmlspecialchars(($datosPaciente['nombre'] ?? '') . ' ' . ($datosPaciente['apellido'] ?? '')) ?></h1>
                    <p>Paciente</p>
                    <button class="btn-edit" onclick="toggleEdit()">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                        Editar
                    </button>
                </div>

                <!-- Vista de datos -->
                <div id="view-mode">
                    <div class="info-card">
                        <div class="section-title">
                            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                            Datos Personales
                        </div>
                        <div class="data-row"><span class="data-label">Nombres</span><span class="data-value"><?= htmlspecialchars($datosPaciente['nombre'] ?? '') ?></span></div>
                        <div class="data-row"><span class="data-label">Apellidos</span><span class="data-value"><?= htmlspecialchars($datosPaciente['apellido'] ?? '') ?></span></div>
                        <div class="data-row"><span class="data-label">Documento</span><span class="data-value"><?= htmlspecialchars($datosPaciente['documento'] ?? '') ?></span></div>
                        <div class="data-row"><span class="data-label">Teléfono</span><span class="data-value"><?= htmlspecialchars($datosPaciente['telefono'] ?? '') ?></span></div>
                        <div class="data-row"><span class="data-label">Fecha Nac.</span><span class="data-value"><?= htmlspecialchars($datosPaciente['fecha_nacimiento'] ?? '') ?></span></div>
                    </div>

                    <div class="info-card">
                        <div class="section-title">
                            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 8l7.89 5.26a2 2 0 0 0 2.22 0L21 8M5 19h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2z"></path></svg>
                            Contacto
                        </div>
                        <div class="data-row"><span class="data-label">Email</span><span class="data-value"><?= htmlspecialchars($datosPaciente['email'] ?? '') ?></span></div>
                        <div class="data-row"><span class="data-label">Dirección</span><span class="data-value"><?= htmlspecialchars($datosPaciente['direccion'] ?? '') ?></span></div>
                    </div>

                    <div class="info-card">
                        <div class="section-title">
                            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 21l-2-2m-2-2l-2-2a5 5 0 1 1 7.5-6.572A5 5 0 1 1 18 9l-2 2-2 2-2 2z"></path></svg>
                            Datos Médicos
                        </div>
                        <div class="medical-grid">
                            <div class="medical-box"><label>Tipo Sangre</label><div><?= htmlspecialchars($datosPaciente['grupo_sanguineo'] ?? '') ?></div></div>
                            <div class="medical-box"><label>N° Seguro</label><div><?= htmlspecialchars($datosPaciente['num_seguro'] ?? '') ?></div></div>
                        </div>
                    </div>
                </div>

                <!-- Formulario de edición -->
                <div id="edit-mode" class="edit-form">
                    <form method="POST" action="?route=dashboard_paciente&action=update_all" autocomplete="on">
                        <div class="info-card">
                            <div class="section-title">Datos Personales</div>
                            <div class="form-group"><label>Nombres</label><input type="text" name="nombre" value="<?= htmlspecialchars($datosPaciente['nombre'] ?? '') ?>" autocomplete="given-name" required></div>
                            <div class="form-group"><label>Apellidos</label><input type="text" name="apellido" value="<?= htmlspecialchars($datosPaciente['apellido'] ?? '') ?>" autocomplete="family-name" required></div>
                            <div class="form-group"><label>Documento</label><input type="text" name="documento" value="<?= htmlspecialchars($datosPaciente['documento'] ?? '') ?>" autocomplete="off" required></div>
                            <div class="form-group"><label>Teléfono</label><input type="tel" name="telefono" value="<?= htmlspecialchars($datosPaciente['telefono'] ?? '') ?>" autocomplete="tel"></div>
                            <div class="form-group"><label>Fecha Nac.</label><input type="date" name="fecha_nacimiento" value="<?= htmlspecialchars($datosPaciente['fecha_nacimiento'] ?? '') ?>" autocomplete="bday" required></div>
                        </div>
                        <div class="info-card">
                            <div class="section-title">Contacto</div>
                            <div class="form-group"><label>Email</label><input type="email" name="email" value="<?= htmlspecialchars($datosPaciente['email'] ?? '') ?>" autocomplete="email" required></div>
                            <div class="form-group"><label>Dirección</label><textarea name="direccion" rows="2" autocomplete="street-address"><?= htmlspecialchars($datosPaciente['direccion'] ?? '') ?></textarea></div>
                        </div>
                        <div class="info-card">
                            <div class="section-title">Datos Médicos</div>
                            <div class="form-group"><label>Tipo de Sangre</label><input type="text" name="grupo_sanguineo" value="<?= htmlspecialchars($datosPaciente['grupo_sanguineo'] ?? '') ?>" autocomplete="off" list="grupo-sanguineo-list-mobile"></div>
                            <div class="form-group"><label>N° Seguro</label><input type="text" name="num_seguro" value="<?= htmlspecialchars($datosPaciente['num_seguro'] ?? '') ?>" autocomplete="off"></div>
                        </div>
                        <button type="submit" class="btn btn-save">Guardar</button>
                        <button type="button" class="btn btn-cancel" onclick="toggleEdit()">Cancelar</button>
                    </form>
                    <datalist id="grupo-sanguineo-list-mobile">
                        <option value="A+"><option value="A-"><option value="B+"><option value="B-">
                        <option value="AB+"><option value="AB-"><option value="O+"><option value="O-">
                    </datalist>
                </div>

                <div class="info-card password-section">
                    <div class="section-title danger-title">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                        Contraseña
                    </div>
                    <form method="POST" action="?route=dashboard_paciente&action=change_password">
                        <div class="form-group"><label>Contraseña Actual</label><div class="password-wrapper"><input type="password" name="password_actual" required><button type="button" class="toggle-password" onclick="togglePasswordVisibility(this)"><svg class="eye-open" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg><svg class="eye-closed" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg></button></div></div>
                        <div class="form-group"><label>Nueva</label><div class="password-wrapper"><input type="password" name="password_nueva" required><button type="button" class="toggle-password" onclick="togglePasswordVisibility(this)"><svg class="eye-open" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg><svg class="eye-closed" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg></button></div></div>
                        <div class="form-group"><label>Confirmar</label><div class="password-wrapper"><input type="password" name="password_confirmar" required><button type="button" class="toggle-password" onclick="togglePasswordVisibility(this)"><svg class="eye-open" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg><svg class="eye-closed" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg></button></div></div>
                        <button type="submit" class="btn btn-save" style="background: #dc2626;">Cambiar</button>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <div class="stepper" style="text-align:center;margin-bottom:15px;">
                <div class="dots" style="display:flex;gap:8px;justify-content:center;margin-bottom:5px;">
                    <div class="dot active" style="width:8px;height:8px;background:#066931;border-radius:50%;"></div>
                    <div class="dot" style="width:8px;height:8px;background:#cbd5e0;border-radius:50%;"></div>
                    <div class="dot" style="width:8px;height:8px;background:#cbd5e0;border-radius:50%;"></div>
                </div>
                <span style="font-size:0.8rem;color:#718096;">Paso 1 de 3</span>
            </div>
            <a href="?route=solicitar_turno" style="display:flex;align-items:center;background-color:#066931;padding:15px;border-radius:12px;color:white;text-decoration:none;gap:12px;">
                <div style="background-color:rgba(255,255,255,0.2);width:45px;height:45px;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <div style="width:28px;height:28px;border:2px solid white;border-radius:50%;position:relative;display:flex;align-items:center;justify-content:center;">
                        <div style="position:absolute;width:14px;height:2px;background:white;"></div>
                        <div style="position:absolute;width:2px;height:14px;background:white;"></div>
                    </div>
                </div>
                <div style="flex-grow:1;text-align:left;">
                    <span style="display:block;font-size:1.1rem;font-weight:bold;margin-bottom:2px;">OBTENER NUEVO TURNO</span>
                    <span style="display:block;font-size:0.8rem;opacity:0.9;">Solicite una cita médica en segundos.</span>
                </div>
                <div style="border:solid white;border-width:0 2px 2px 0;display:inline-block;padding:5px;transform:rotate(-45deg);"></div>
            </a>
        <?php endif; ?>
    </main>
</div>

<script>
function togglePasswordVisibility(btn) {
    var input = btn.parentElement.querySelector('input');
    var open = btn.querySelector('.eye-open');
    var closed = btn.querySelector('.eye-closed');
    if (input.type === 'password') {
        input.type = 'text';
        open.style.display = 'none';
        closed.style.display = 'block';
    } else {
        input.type = 'password';
        open.style.display = 'block';
        closed.style.display = 'none';
    }
}
(function() {
    var activeTicket = <?= !empty($ticketsHoy) ? json_encode($ticketsHoy[0]) : 'null' ?>;
    if (!activeTicket) return;
    if ('Notification' in window && Notification.permission === 'default') {
        Notification.requestPermission();
    }
    var lastEstado = activeTicket.estado;
    setInterval(function() {
        fetch('?route=api&action=check_ticket&id_ticket=' + activeTicket.id_ticket)
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data.success) return;
                if (data.estado !== lastEstado) {
                    lastEstado = data.estado;
                    if (data.estado === 'Llamado' && 'Notification' in window && Notification.permission === 'granted') {
                        new Notification('¡Hora de atención!', {
                            body: 'Su ticket ' + data.codigo_ticket + ' ha sido llamado. Diríjase al consultorio N°' + data.consultorio + '.',
                            icon: 'https://ui-avatars.com/api/?name=Q-Line&background=066931&color=white'
                        });
                    }
                }
            })
            .catch(function() {});
    }, 15000);
})();
</script>