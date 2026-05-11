<?php 
require_once dirname(__DIR__, 2) . '/Logic/Controllers/AuthController.php';
require_once dirname(__DIR__, 2) . '/Logic/Controllers/MedicoController.php';
require_once dirname(__DIR__, 2) . '/Logic/Controllers/EspecialidadController.php';
require_once dirname(__DIR__, 2) . '/Logic/Controllers/PersonalController.php';
require_once dirname(__DIR__, 2) . '/Logic/Controllers/UserController.php';

$auth = new AuthController();
$user = $auth->getUser();

if (!$user || $user['rol'] != 2) {
    header('Location: ?route=login');
    exit;
}

$activeSection = $_GET['section'] ?? 'dashboard';

$personalCtrl = new PersonalController();
$miPersonal = $personalCtrl->getPersonalByPersonaId($user['id_persona']);

$medicoCtrl = new MedicoController($user['id_persona']);
$medicoDatos = $medicoCtrl->getDatosMedico();

$ticketsEspera = [];
$mm1 = null;
$limiteInfo = $medicoCtrl->getLimiteDiario();
if ($activeSection === 'tickets' || $activeSection === 'atencion' || $activeSection === 'dashboard') {
    $ticketsEspera = $medicoCtrl->listarEnEspera();
    if ($activeSection === 'dashboard') {
        $mm1 = $medicoCtrl->calcularMM1();
    }
}

$ticketSeleccionado = null;
$historialPaciente = [];
$ticketId = $_GET['ticket_id'] ?? 0;
if ($ticketId) {
    $ticketSeleccionado = $medicoCtrl->obtenerInfoTicket($ticketId);
    if ($ticketSeleccionado) {
        $edad = $ticketSeleccionado['fecha_nacimiento'] ? date_diff(date_create($ticketSeleccionado['fecha_nacimiento']), date_create('today'))->y : '?';
        $ticketSeleccionado['edad'] = $edad;
        if ($activeSection === 'atencion') {
            $historialPaciente = $medicoCtrl->obtenerHistorialPaciente($ticketId);
        }
    }
}

$busquedaPaciente = trim($_GET['busqueda_paciente'] ?? '');
$resultadosBusqueda = [];
$pacienteSeleccionado = null;
$historialCompletoPaciente = [];
$pacientesAtendidos = [];
$idPacienteHistorial = $_GET['id_paciente'] ?? 0;

$fechaReporte = $_GET['fecha_reporte'] ?? date('Y-m-d');
$reporteDiario = [];
if ($activeSection === 'reporte') {
    $reporteDiario = $medicoCtrl->obtenerReporteDiario($fechaReporte);
}

if ($activeSection === 'historial') {
    $pacientesAtendidos = $medicoCtrl->listarPacientesAtendidos();
    if (!empty($busquedaPaciente)) {
        $resultadosBusqueda = $medicoCtrl->buscarPacientes($busquedaPaciente);
    }
    if ($idPacienteHistorial) {
        $historialCompletoPaciente = $medicoCtrl->obtenerHistorialCompleto($idPacienteHistorial);
        $pacInfo = Paciente::buscarPorId($idPacienteHistorial);
        $pacienteSeleccionado = $pacInfo;
    }
}

$pageTitle = "Dashboard Médico - Q-Line";
?>
<link rel="stylesheet" href="css/dashboard-common.css">
<style>
    .flex-row { display: flex; gap: 25px; }
    .flex-col { flex: 1; }
    .flex-col-30 { flex: 0 0 35%; }
    .flex-col-70 { flex: 1; }
    .card { background: white; border-radius: 12px; border: 1px solid var(--border-color); box-shadow: 0 2px 8px rgba(0,0,0,0.05); margin-bottom: 20px; }
    .card-header { padding: 18px 22px; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; justify-content: space-between; }
    .card-header h3 { margin: 0; font-size: 1rem; color: #2d3748; }
    .card-body { padding: 22px; }
    .queue-item { display: flex; align-items: center; padding: 12px 15px; border: 1px solid var(--border-color); border-radius: 8px; margin-bottom: 8px; cursor: pointer; transition: all 0.2s; }
    .queue-item:hover, .queue-item.selected { background: var(--active-bg); border-color: var(--primary-green); }
    .queue-item.selected { box-shadow: 0 0 0 2px rgba(6,105,49,0.2); }
    .queue-priority { width: 10px; height: 10px; border-radius: 50%; margin-right: 12px; flex-shrink: 0; }
    .priority-amarillo { background: #eab308; }
    .priority-verde { background: #22c55e; }
    .queue-info { flex: 1; }
    .queue-info strong { display: block; font-size: 0.9rem; color: #2d3748; }
    .queue-info small { color: #718096; font-size: 0.75rem; }
    .queue-code { font-weight: 700; color: var(--primary-green); font-size: 0.85rem; }
    .queue-status { font-size: 0.75rem; padding: 3px 10px; border-radius: 20px; }
    .status-llamado { background: #dbeafe; color: #1d4ed8; }
    .status-espera { background: #fef3c7; color: #b45309; }
    .btn { padding: 10px 20px; border: none; border-radius: 8px; font-size: 0.9rem; font-weight: 600; cursor: pointer; transition: all 0.2s; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
    .btn-primary { background: var(--primary-green); color: white; }
    .btn-primary:hover { background: #045a28; }
    .btn-success { background: #059669; color: white; }
    .btn-success:hover { background: #047857; }
    .btn-warning { background: #d97706; color: white; }
    .btn-warning:hover { background: #b45309; }
    .btn-danger { background: #dc2626; color: white; }
    .btn-danger:hover { background: #b91c1c; }
    .btn-sm { padding: 6px 14px; font-size: 0.8rem; }
    .btn-outline { background: transparent; border: 1px solid var(--border-color); color: var(--text-main); }
    .btn-outline:hover { background: #f7fafc; }
    .patient-header { display: flex; align-items: center; gap: 15px; margin-bottom: 20px; }
    .patient-avatar { width: 60px; height: 60px; border-radius: 50%; background: var(--primary-green); display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 1.5rem; }
    .patient-name { margin: 0; font-size: 1.2rem; color: #2d3748; }
    .patient-meta { color: #718096; font-size: 0.85rem; }
    .patient-meta span { margin-right: 15px; }
    .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px; }
    .info-item label { display: block; font-size: 0.75rem; color: #718096; text-transform: uppercase; margin-bottom: 3px; }
    .info-item p { margin: 0; font-size: 0.95rem; color: #2d3748; font-weight: 500; }
    .form-control { width: 100%; padding: 10px 12px; border: 1px solid var(--border-color); border-radius: 8px; font-size: 0.9rem; box-sizing: border-box; }
    .form-control:focus { outline: none; border-color: var(--primary-green); box-shadow: 0 0 0 2px rgba(6,105,49,0.15); }
    textarea.form-control { min-height: 80px; resize: vertical; }
    .empty-state { text-align: center; padding: 40px; color: #718096; }
    .empty-state svg { width: 60px; height: 60px; opacity: 0.3; margin-bottom: 15px; }
    .badge-green { background: #d1fae5; color: #059669; }
    .badge-yellow { background: #fef3c7; color: #b45309; }
    .badge-red { background: #fee2e2; color: #dc2626; }
    .badge-blue { background: #dbeafe; color: #1d4ed8; }
    .badge-gray { background: #f3f4f6; color: #6b7280; }
    .stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 25px; }
    .stat-card { background: white; border-radius: 12px; border: 1px solid var(--border-color); padding: 22px; text-align: center; }
    .stat-card .number { font-size: 2rem; font-weight: 700; color: var(--primary-green); }
    .stat-card .label { font-size: 0.85rem; color: #718096; margin-top: 5px; }
    @media (max-width: 768px) { .flex-row { flex-direction: column; } .flex-col-30, .flex-col-70 { flex: none; } }
</style>

<div class="desktop-only">
    <aside class="sidebar">
        <div class="logo-section">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-2 10h-4v4h-2v-4H7v-2h4V7h2v4h4v2z"/></svg>
            Q-Line
        </div>
        
        <div class="sidebar-content">
            <div class="sidebar-title">Menú Principal</div>
            <a href="?route=dashboard_medico&section=dashboard" class="menu-item <?= $activeSection === 'dashboard' ? 'active' : '' ?>">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                Dashboard
            </a>
            <a href="?route=dashboard_medico&section=tickets" class="menu-item <?= $activeSection === 'tickets' ? 'active' : '' ?>">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path></svg>
                Cola de Tickets
            </a>
            <a href="?route=dashboard_medico&section=atencion" class="menu-item <?= $activeSection === 'atencion' ? 'active' : '' ?>">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                Atención
            </a>
            <a href="?route=dashboard_medico&section=historial" class="menu-item <?= $activeSection === 'historial' ? 'active' : '' ?>">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                Historial Pacientes
            </a>
            <a href="?route=dashboard_medico&section=reporte" class="menu-item <?= $activeSection === 'reporte' ? 'active' : '' ?>">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                Reporte Diario
            </a>
            <a href="?route=dashboard_medico&section=perfil" class="menu-item <?= $activeSection === 'perfil' ? 'active' : '' ?>">
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
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($user['nombre'] . ' ' . $user['apellido']) ?>&background=white&color=066931" alt="Dr." width="100%">
                </div>
                <div class="user-details">
                    <h4>Dr. <?= htmlspecialchars($user['nombre'] . ' ' . $user['apellido']) ?></h4>
                    <span><?= $miPersonal ? htmlspecialchars($miPersonal['nombre_especialidad']) : 'Sin especialidad' ?></span>
                </div>
            </div>
        </header>
        
        <div class="page-body">
            <?php if ($activeSection === 'dashboard'): ?>
            <h2 class="section-title">Dashboard</h2>

            <div class="stat-grid">
                <div class="stat-card">
                    <div class="number"><?= count($ticketsEspera) ?></div>
                    <div class="label">Pacientes en espera</div>
                </div>
                <div class="stat-card">
                    <div class="number"><?= count(array_filter($ticketsEspera, fn($t) => $t['estado'] === 'Llamado')) ?></div>
                    <div class="label">Pacientes llamados</div>
                </div>
                <div class="stat-card">
                    <div class="number"><?= $medicoDatos['consultorios'] ? count($medicoDatos['consultorios']) : 0 ?></div>
                    <div class="label">Consultorios asignados</div>
                </div>
                <div class="stat-card">
                    <div class="number" style="color:<?= $limiteInfo['limite'] > 0 && $limiteInfo['restantes'] <= 5 ? ($limiteInfo['restantes'] <= 0 ? '#dc2626' : '#eab308') : 'var(--primary-green)' ?>">
                        <?= $limiteInfo['atendidos'] ?><?= $limiteInfo['limite'] > 0 ? '/' . $limiteInfo['limite'] : '' ?>
                    </div>
                    <div class="label">Atendidos hoy / Límite</div>
                </div>
            </div>

            <?php if ($mm1): ?>
            <div class="card">
                <div class="card-header"><h3>Modelo M/M/1 - Análisis de la cola</h3></div>
                <div class="card-body">
                    <div class="stat-grid">
                        <div class="stat-card">
                            <div class="number"><?= $mm1['lambda'] ?></div>
                            <div class="label">λ (llegadas/hora)</div>
                        </div>
                        <div class="stat-card">
                            <div class="number"><?= $mm1['mu'] ?></div>
                            <div class="label">μ (servicios/hora)</div>
                        </div>
                        <div class="stat-card">
                            <div class="number"><?= $mm1['rho'] ?></div>
                            <div class="label">ρ (factor de uso)</div>
                        </div>
                        <div class="stat-card">
                            <div class="number"><?= $mm1['Lq'] ?></div>
                            <div class="label">Lq (pacientes en cola)</div>
                        </div>
                        <div class="stat-card">
                            <div class="number"><?= $mm1['Wq_min'] ?> min</div>
                            <div class="label">Wq (tiempo espera cola)</div>
                        </div>
                        <div class="stat-card">
                            <div class="number"><?= $mm1['W_min'] ?> min</div>
                            <div class="label">W (tiempo total sistema)</div>
                        </div>
                    </div>
                    <?php if ($mm1['rho'] >= 1): ?>
                    <p style="color:#dc2626;font-weight:600;">⚠ El sistema está saturado (ρ ≥ 1). La cola crecerá indefinidamente.</p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header"><h3>Próximos pacientes</h3></div>
                <div class="card-body">
                    <?php if (empty($ticketsEspera)): ?>
                    <div class="empty-state">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <p>No hay pacientes en espera</p>
                    </div>
                    <?php else: ?>
                    <?php foreach (array_slice($ticketsEspera, 0, 5) as $t): ?>
                    <div class="queue-item">
                        <div class="queue-priority priority-<?= strtolower($t['prioridad']) ?>"></div>
                        <div class="queue-info">
                            <strong><?= htmlspecialchars($t['paciente_nombre'] . ' ' . $t['paciente_apellido']) ?></strong>
                            <small><?= htmlspecialchars($t['codigo_ticket']) ?> · <?= htmlspecialchars($t['nombre_especialidad']) ?></small>
                        </div>
                        <span class="queue-code"><?= htmlspecialchars($t['codigo_ticket']) ?></span>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <?php elseif ($activeSection === 'tickets'): ?>
            <div class="flex-row">
                <div class="flex-col-30">
                    <div class="card">
                        <div class="card-header">
                            <h3>Cola de espera</h3>
                            <span class="badge badge-blue"><?= count($ticketsEspera) ?></span>
                        </div>
                        <div class="card-body" style="max-height: 600px; overflow-y: auto;">
                            <?php if (empty($ticketsEspera)): ?>
                            <div class="empty-state">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <p>No hay pacientes en espera</p>
                            </div>
                            <?php else: ?>
                            <?php foreach ($ticketsEspera as $t): ?>
                            <a href="?route=dashboard_medico&section=tickets&ticket_id=<?= $t['id_ticket'] ?>" class="queue-item <?= ($ticketId == $t['id_ticket']) ? 'selected' : '' ?>">
                                <div class="queue-priority priority-<?= strtolower($t['prioridad']) ?>"></div>
                                <div class="queue-info">
                                    <strong><?= htmlspecialchars($t['paciente_nombre'] . ' ' . $t['paciente_apellido']) ?></strong>
                                    <small><?= htmlspecialchars($t['codigo_ticket']) ?> · Cons. <?= $t['numero_consultorio'] ?></small>
                                </div>
                                <span class="queue-status status-<?= strtolower($t['estado']) ?>"><?= $t['estado'] ?></span>
                            </a>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <form method="POST" action="?route=dashboard_medico&section=tickets&action=llamar_siguiente" style="margin-top:10px;">
                        <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;" <?= empty($ticketsEspera) ? 'disabled' : '' ?>>
                            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                            Llamar siguiente
                        </button>
                    </form>
                </div>
                <div class="flex-col-70">
                    <?php if ($ticketSeleccionado): ?>
                    <div class="card">
                        <div class="card-header">
                            <h3>Información del paciente</h3>
                            <div>
                                <span class="badge badge-<?= strtolower($ticketSeleccionado['prioridad']) === 'amarillo' ? 'yellow' : 'green' ?>">
                                    <?= $ticketSeleccionado['prioridad'] ?>
                                </span>
                                <span class="badge badge-<?= $ticketSeleccionado['estado'] === 'Llamado' ? 'blue' : 'gray' ?>"><?= $ticketSeleccionado['estado'] ?></span>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="patient-header">
                                <div class="patient-avatar"><?= strtoupper(substr($ticketSeleccionado['paciente_nombre'], 0, 1)) . strtoupper(substr($ticketSeleccionado['paciente_apellido'], 0, 1)) ?></div>
                                <div>
                                    <h3 class="patient-name"><?= htmlspecialchars($ticketSeleccionado['paciente_nombre'] . ' ' . $ticketSeleccionado['paciente_apellido']) ?></h3>
                                    <div class="patient-meta">
                                        <span><?= $ticketSeleccionado['edad'] ?> años</span>
                                        <span>Doc: <?= htmlspecialchars($ticketSeleccionado['documento']) ?></span>
                                        <span>📞 <?= htmlspecialchars($ticketSeleccionado['telefono'] ?? 'N/A') ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="info-grid">
                                <div class="info-item">
                                    <label>Código de ticket</label>
                                    <p><?= htmlspecialchars($ticketSeleccionado['codigo_ticket']) ?></p>
                                </div>
                                <div class="info-item">
                                    <label>Consultorio</label>
                                    <p>N° <?= $ticketSeleccionado['numero_consultorio'] ?> (Piso <?= $ticketSeleccionado['piso'] ?>)</p>
                                </div>
                                <div class="info-item">
                                    <label>Especialidad</label>
                                    <p><?= htmlspecialchars($ticketSeleccionado['nombre_especialidad']) ?></p>
                                </div>
                                <div class="info-item">
                                    <label>Fecha de creación</label>
                                    <p><?= date('d/m/Y H:i', strtotime($ticketSeleccionado['fecha_creacion'])) ?></p>
                                </div>
                                <?php if (!empty($ticketSeleccionado['motivo_consulta'])): ?>
                                <div class="info-item" style="grid-column:1/-1;">
                                    <label>Motivo de consulta</label>
                                    <p style="background:#f7fafc;padding:10px 12px;border-radius:8px;margin-top:4px;"><?= htmlspecialchars($ticketSeleccionado['motivo_consulta']) ?></p>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php if ($ticketSeleccionado['estado'] === 'Llamado'): ?>
                            <div style="margin-top:15px;border-top:1px solid var(--border-color);padding-top:15px;">
                                <a href="?route=dashboard_medico&section=atencion&ticket_id=<?= $ticketSeleccionado['id_ticket'] ?>" class="btn btn-success">
                                    Iniciar atención médica
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="card">
                        <div class="card-body">
                            <div class="empty-state">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path></svg>
                                <p>Seleccione un paciente de la cola para ver su información</p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php elseif ($activeSection === 'atencion'): ?>
            <div class="flex-row">
                <div class="flex-col-30">
                    <div class="card">
                        <div class="card-header">
                            <h3>Pacientes en cola</h3>
                            <span class="badge badge-blue"><?= count($ticketsEspera) ?></span>
                        </div>
                        <div class="card-body" style="max-height: 600px; overflow-y: auto;">
                            <?php if (empty($ticketsEspera)): ?>
                            <div class="empty-state">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <p>No hay pacientes en espera</p>
                            </div>
                            <?php else: ?>
                            <?php foreach ($ticketsEspera as $t): ?>
                            <a href="?route=dashboard_medico&section=atencion&ticket_id=<?= $t['id_ticket'] ?>" class="queue-item <?= ($ticketId == $t['id_ticket']) ? 'selected' : '' ?>">
                                <div class="queue-priority priority-<?= strtolower($t['prioridad']) ?>"></div>
                                <div class="queue-info">
                                    <strong><?= htmlspecialchars($t['paciente_nombre'] . ' ' . $t['paciente_apellido']) ?></strong>
                                    <small><?= htmlspecialchars($t['codigo_ticket']) ?></small>
                                </div>
                                <span class="queue-status status-<?= strtolower($t['estado']) ?>"><?= $t['estado'] ?></span>
                            </a>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="flex-col-70">
                    <?php if ($ticketSeleccionado): ?>
                    <div class="card">
                        <div class="card-header">
                            <h3>Atención médica</h3>
                            <span><?= htmlspecialchars($ticketSeleccionado['codigo_ticket']) ?></span>
                        </div>
                        <div class="card-body">
                            <div class="patient-header">
                                <div class="patient-avatar"><?= strtoupper(substr($ticketSeleccionado['paciente_nombre'], 0, 1)) . strtoupper(substr($ticketSeleccionado['paciente_apellido'], 0, 1)) ?></div>
                                <div>
                                    <h3 class="patient-name"><?= htmlspecialchars($ticketSeleccionado['paciente_nombre'] . ' ' . $ticketSeleccionado['paciente_apellido']) ?></h3>
                                    <div class="patient-meta">
                                        <span><?= $ticketSeleccionado['edad'] ?> años</span>
                                        <span>Doc: <?= htmlspecialchars($ticketSeleccionado['documento']) ?></span>
                                        <span>Grupo: <?= htmlspecialchars($ticketSeleccionado['grupo_sanguineo'] ?? 'N/A') ?></span>
                                        <span>Seguro: <?= htmlspecialchars($ticketSeleccionado['num_seguro'] ?? 'N/A') ?></span>
                                    </div>
                                </div>
                            </div>

                            <?php if (!empty($historialPaciente)): ?>
                            <div style="margin-bottom:20px;border:1px solid #e2e8f0;border-radius:10px;overflow:hidden;">
                                <div style="background:#f7fafc;padding:12px 16px;font-weight:600;font-size:0.9rem;color:#2d3748;cursor:pointer;display:flex;align-items:center;gap:8px;" onclick="this.nextElementSibling.style.display = this.nextElementSibling.style.display === 'none' ? 'block' : 'none'">
                                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    Historial clínico (<?= count($historialPaciente) ?> atenciones previas)
                                    <span style="margin-left:auto;font-size:0.8rem;color:#718096;">[clic para expandir]</span>
                                </div>
                                <div style="display:none;padding:16px;">
                                    <?php foreach ($historialPaciente as $h): ?>
                                    <div style="padding:10px 0;border-bottom:1px solid #edf2f7;">
                                        <div style="display:flex;justify-content:space-between;margin-bottom:5px;">
                                            <strong style="font-size:0.85rem;"><?= date('d/m/Y', strtotime($h['fecha_hora_atencion'])) ?> · <?= htmlspecialchars($h['nombre_especialidad']) ?></strong>
                                            <span style="font-size:0.75rem;color:#718096;">Dr. <?= htmlspecialchars($h['medico_nombre'] . ' ' . $h['medico_apellido']) ?></span>
                                        </div>
                                        <div style="font-size:0.85rem;color:#4a5568;margin-bottom:3px;"><strong>Diagnóstico:</strong> <?= htmlspecialchars($h['descripcion_diagnostico']) ?></div>
                                        <?php if ($h['tratamiento_prescrito']): ?>
                                        <div style="font-size:0.85rem;color:#4a5568;"><strong>Tratamiento:</strong> <?= htmlspecialchars($h['tratamiento_prescrito']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>

                            <form method="POST" action="?route=dashboard_medico&section=atencion&action=guardar_atencion">
                                <input type="hidden" name="id_ticket" value="<?= $ticketSeleccionado['id_ticket'] ?>">
                                
                                <div class="form-group">
                                    <label>Tipo de diagnóstico</label>
                                    <select name="tipo_diagnostico" class="form-control" required>
                                        <option value="Presuntivo">Presuntivo</option>
                                        <option value="Definitivo">Definitivo</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label>Descripción del diagnóstico</label>
                                    <textarea name="descripcion_diagnostico" class="form-control" required></textarea>
                                </div>
                                
                                <div class="form-group">
                                    <label>Tratamiento prescrito</label>
                                    <textarea name="tratamiento_prescrito" class="form-control"></textarea>
                                </div>

                                <div class="form-group">
                                    <label>Fecha próxima cita (opcional)</label>
                                    <input type="date" name="fecha_proxima_cita" class="form-control">
                                </div>

                                <div style="display:flex;gap:10px;margin-top:20px;">
                                    <button type="submit" class="btn btn-primary">
                                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"></path></svg>
                                        Guardar y finalizar atención
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="card">
                        <div class="card-body">
                            <div class="empty-state">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                                <p>Seleccione un paciente para iniciar la atención</p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php elseif ($activeSection === 'perfil'): ?>
                <?php
                $userCtrl = new UserController();
                $datosMedico = $userCtrl->obtenerDatosPaciente($_SESSION['id_usuario'] ?? $_SESSION['user_id'] ?? 0);

                if (!$datosMedico) {
                    $datosMedico = [
                        'nombre' => $user['nombre'] ?? '',
                        'apellido' => $user['apellido'] ?? '',
                        'documento' => $user['documento'] ?? '',
                        'telefono' => $user['telefono'] ?? '',
                        'direccion' => $user['direccion'] ?? '',
                        'fecha_nacimiento' => $user['fecha_nacimiento'] ?? '',
                        'email' => $user['nombre_usuario'] ?? '',
                    ];
                }
                ?>
                <style>
                    .perfil-container { max-width: 800px; margin: 0 auto; }
                    .perfil-header { text-align: center; margin-bottom: 40px; }
                    .avatar-wrapper { position: relative; width: 120px; height: 120px; margin: 0 auto 15px; }
                    .avatar-img { width: 100%; height: 100%; background: #e2e8f0; border-radius: 50%; border: 4px solid white; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
                    .perfil-header h1 { margin: 0; font-size: 1.8rem; color: #2d3748; }
                    .perfil-header p { margin: 5px 0 0; color: #718096; font-size: 0.9rem; }
                    .info-card { background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px; margin-bottom: 24px; }
                    .info-card h3 { color: #066931; margin: 0 0 15px; }
                    .data-row { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #e2e8f0; }
                    .data-row:last-child { border-bottom: none; }
                    .data-label { color: #718096; font-size: 0.9rem; }
                    .data-value { color: #2d3748; font-weight: 500; }
                    .btn-save { background: #066931; color: white; border: none; padding: 14px; border-radius: 10px; font-size: 1rem; font-weight: 600; cursor: pointer; width: 100%; }
                    .btn-save:hover { background: #045226; }
                    .form-group { margin-bottom: 20px; }
                    .form-group label { display: block; font-size: 0.85rem; font-weight: 600; color: #2d3748; margin-bottom: 5px; }
                    .form-group input { width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.95rem; box-sizing: border-box; }
                    .password-section { margin-top: 30px; padding-top: 30px; border-top: 2px dashed #e2e8f0; }
                </style>
                <div class="perfil-container">
                    <div class="perfil-header">
                        <div class="avatar-wrapper">
                            <img src="https://ui-avatars.com/api/?name=<?= urlencode($datosMedico['nombre'] . ' ' . $datosMedico['apellido']) ?>&background=066931&color=white&size=120" class="avatar-img">
                        </div>
                        <h1><?= htmlspecialchars(($datosMedico['nombre'] ?? '') . ' ' . ($datosMedico['apellido'] ?? '')) ?></h1>
                        <p><?= $miPersonal ? htmlspecialchars($miPersonal['nombre_especialidad']) : 'Médico' ?></p>
                    </div>

                    <div class="info-card">
                        <h3>Datos Personales</h3>
                        <div class="data-row"><span class="data-label">Nombres</span><span class="data-value"><?= htmlspecialchars($datosMedico['nombre'] ?? '') ?></span></div>
                        <div class="data-row"><span class="data-label">Apellidos</span><span class="data-value"><?= htmlspecialchars($datosMedico['apellido'] ?? '') ?></span></div>
                        <div class="data-row"><span class="data-label">Documento</span><span class="data-value"><?= htmlspecialchars($datosMedico['documento'] ?? '') ?></span></div>
                        <div class="data-row"><span class="data-label">Teléfono</span><span class="data-value"><?= htmlspecialchars($datosMedico['telefono'] ?? '') ?></span></div>
                        <div class="data-row"><span class="data-label">Email</span><span class="data-value"><?= htmlspecialchars($datosMedico['email'] ?? '') ?></span></div>
                        <?php if ($miPersonal): ?>
                        <div class="data-row"><span class="data-label">Especialidad</span><span class="data-value"><?= htmlspecialchars($miPersonal['nombre_especialidad']) ?></span></div>
                        <div class="data-row"><span class="data-label">Matrícula</span><span class="data-value"><?= htmlspecialchars($miPersonal['matricula_profesional'] ?? 'N/A') ?></span></div>
                        <?php endif; ?>
                    </div>

                    <div class="info-card">
                        <h3>Límite Diario de Atenciones</h3>
                        <div class="data-row"><span class="data-label">Atendidos hoy</span><span class="data-value"><?= $limiteInfo['atendidos'] ?></span></div>
                        <div class="data-row"><span class="data-label">Límite configurado</span><span class="data-value"><?= $limiteInfo['limite'] > 0 ? $limiteInfo['limite'] : 'Sin límite' ?></span></div>
                        <?php if ($limiteInfo['restantes'] >= 0): ?>
                        <div class="data-row"><span class="data-label">Restantes hoy</span><span class="data-value" style="color:<?= $limiteInfo['restantes'] <= 3 ? '#dc2626' : 'var(--primary-green)' ?>;font-weight:700;"><?= $limiteInfo['restantes'] ?></span></div>
                        <?php endif; ?>
                        <form method="POST" action="?route=dashboard_medico&action=update_limite" style="margin-top:12px;display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
                            <label style="font-size:0.85rem;color:#4a5568;">Nuevo límite diario:</label>
                            <input type="number" name="limite_diario" min="0" value="<?= $limiteInfo['limite'] ?>" style="width:80px;padding:8px 10px;border:1px solid #e2e8f0;border-radius:6px;font-size:0.9rem;">
                            <button type="submit" class="btn btn-primary btn-sm">Guardar</button>
                            <?php if ($limiteInfo['limite'] > 0): ?>
                            <button type="submit" name="limite_diario" value="0" class="btn btn-outline btn-sm" style="font-size:0.8rem;">Quitar límite</button>
                            <?php endif; ?>
                        </form>
                    </div>

                    <div class="password-section">
                        <div class="info-card">
                            <h3 style="color:#dc2626;">Cambiar Contraseña</h3>
                            <form method="POST" action="?route=dashboard_medico&action=change_password">
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
                                <button type="submit" class="btn-save" style="background:#dc2626;">Cambiar Contraseña</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php elseif ($activeSection === 'historial'): ?>
                <style>
                    .historial-container { max-width: 1000px; margin: 0 auto; }
                    .historial-search { display: flex; gap: 10px; margin-bottom: 20px; }
                    .historial-search input { flex: 1; padding: 10px 14px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.9rem; }
                    .historial-grid { display: grid; grid-template-columns: 1fr 2fr; gap: 20px; }
                    .paciente-list { max-height: 500px; overflow-y: auto; }
                    .paciente-item { display: flex; justify-content: space-between; align-items: center; padding: 12px 15px; border: 1px solid var(--border-color); border-radius: 8px; margin-bottom: 6px; cursor: pointer; transition: all 0.2s; text-decoration: none; color: inherit; }
                    .paciente-item:hover, .paciente-item.active { background: var(--active-bg); border-color: var(--primary-green); }
                    .paciente-item .nombre { font-weight: 600; font-size: 0.9rem; }
                    .paciente-item .meta { font-size: 0.75rem; color: #718096; }
                    .paciente-item .badge-count { background: #066931; color: white; padding: 2px 8px; border-radius: 10px; font-size: 0.7rem; font-weight: bold; }
                    .history-entry { padding: 14px 0; border-bottom: 1px solid #edf2f7; }
                    .history-entry:last-child { border-bottom: none; }
                    .history-header { display: flex; justify-content: space-between; margin-bottom: 6px; }
                    .history-header .date { font-weight: 600; color: #066931; font-size: 0.9rem; }
                    .history-header .doctor { font-size: 0.8rem; color: #718096; }
                    .history-diagnosis { background: #f7fafc; padding: 10px; border-radius: 6px; margin: 6px 0; font-size: 0.85rem; }
                    .history-diagnosis strong { color: #2d3748; }
                    .history-treatment { font-size: 0.85rem; color: #4a5568; }
                    .history-treatment strong { color: #2d3748; }
                    .empty-history { text-align: center; padding: 40px; color: #718096; }
                    @media (max-width: 768px) { .historial-grid { grid-template-columns: 1fr; } }
                </style>
                <div class="historial-container">
                    <h2 class="section-title">Historial de Pacientes</h2>
                    <form method="GET" class="historial-search">
                        <input type="hidden" name="route" value="dashboard_medico">
                        <input type="hidden" name="section" value="historial">
                        <input type="text" name="busqueda_paciente" placeholder="Buscar paciente por nombre, apellido o documento..." value="<?= htmlspecialchars($busquedaPaciente) ?>">
                        <button type="submit" class="btn btn-primary btn-sm">Buscar</button>
                        <?php if (!empty($busquedaPaciente)): ?>
                            <a href="?route=dashboard_medico&section=historial" class="btn btn-outline btn-sm">Limpiar</a>
                        <?php endif; ?>
                    </form>

                    <div class="historial-grid">
                        <div class="card">
                            <div class="card-header">
                                <h3>Pacientes</h3>
                                <?php if (!empty($busquedaPaciente) && !empty($resultadosBusqueda)): ?>
                                    <span class="badge badge-blue"><?= count($resultadosBusqueda) ?> resultados</span>
                                <?php elseif (empty($busquedaPaciente)): ?>
                                    <span class="badge badge-blue"><?= count($pacientesAtendidos) ?> atendidos</span>
                                <?php endif; ?>
                            </div>
                            <div class="card-body paciente-list">
                                <?php if (!empty($busquedaPaciente)): ?>
                                    <?php if (!empty($resultadosBusqueda)): ?>
                                        <?php foreach ($resultadosBusqueda as $p): ?>
                                            <a href="?route=dashboard_medico&section=historial&id_paciente=<?= $p['id_paciente'] ?>&busqueda_paciente=<?= urlencode($busquedaPaciente) ?>" class="paciente-item <?= ($idPacienteHistorial == $p['id_paciente']) ? 'active' : '' ?>">
                                                <div>
                                                    <div class="nombre"><?= htmlspecialchars($p['nombre'] . ' ' . $p['apellido']) ?></div>
                                                    <div class="meta">Doc: <?= htmlspecialchars($p['documento']) ?> · <?= htmlspecialchars($p['telefono'] ?? 'Sin teléfono') ?></div>
                                                </div>
                                            </a>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="empty-history">No se encontraron pacientes con "<strong><?= htmlspecialchars($busquedaPaciente) ?></strong>"</div>
                                    <?php endif; ?>
                                <?php elseif (!empty($pacientesAtendidos)): ?>
                                    <?php foreach ($pacientesAtendidos as $p): ?>
                                        <a href="?route=dashboard_medico&section=historial&id_paciente=<?= $p['id_paciente'] ?>" class="paciente-item <?= ($idPacienteHistorial == $p['id_paciente']) ? 'active' : '' ?>">
                                            <div>
                                                <div class="nombre"><?= htmlspecialchars($p['paciente_nombre'] . ' ' . $p['paciente_apellido']) ?></div>
                                                <div class="meta">Doc: <?= htmlspecialchars($p['documento']) ?> · <?= date('d/m/Y', strtotime($p['ultima_atencion'])) ?></div>
                                            </div>
                                            <span class="badge-count"><?= $p['total_atenciones'] ?></span>
                                        </a>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="empty-history">
                                        <p>No hay pacientes atendidos aún.<br>Use el buscador para encontrar pacientes.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header">
                                <h3><?= $pacienteSeleccionado ? htmlspecialchars($pacienteSeleccionado['nombre'] . ' ' . $pacienteSeleccionado['apellido']) : 'Detalle del historial' ?></h3>
                                <?php if ($pacienteSeleccionado): ?>
                                    <span style="font-size:0.8rem;color:#718096;"><?= count($historialCompletoPaciente) ?> atenciones</span>
                                <?php endif; ?>
                            </div>
                            <div class="card-body">
                                <?php if ($pacienteSeleccionado): ?>
                                    <div style="margin-bottom:15px;padding-bottom:15px;border-bottom:1px solid #e2e8f0;">
                                        <div style="display:flex;gap:15px;font-size:0.85rem;color:#718096;">
                                            <span><strong>Documento:</strong> <?= htmlspecialchars($pacienteSeleccionado['documento'] ?? '') ?></span>
                                            <span><strong>Teléfono:</strong> <?= htmlspecialchars($pacienteSeleccionado['telefono'] ?? 'N/A') ?></span>
                                            <span><strong>Email:</strong> <?= htmlspecialchars($pacienteSeleccionado['email'] ?? 'N/A') ?></span>
                                        </div>
                                    </div>
                                    <?php if (!empty($historialCompletoPaciente)): ?>
                                        <?php foreach ($historialCompletoPaciente as $h): ?>
                                            <div class="history-entry">
                                                <div class="history-header">
                                                    <span class="date"><?= date('d/m/Y H:i', strtotime($h['fecha_hora_atencion'])) ?> · <?= htmlspecialchars($h['nombre_especialidad']) ?></span>
                                                    <span class="doctor">Dr. <?= htmlspecialchars($h['medico_nombre'] . ' ' . $h['medico_apellido']) ?></span>
                                                </div>
                                                <div style="display:flex;gap:10px;font-size:0.8rem;color:#718096;margin-bottom:6px;">
                                                    <span>Ticket: <?= htmlspecialchars($h['codigo_ticket']) ?></span>
                                                    <span>Consultorio: N° <?= htmlspecialchars($h['numero_consultorio']) ?></span>
                                                    <span>Tipo: <?= htmlspecialchars($h['tipo_diagnostico']) ?></span>
                                                </div>
                                                <div class="history-diagnosis">
                                                    <strong>Diagnóstico:</strong> <?= htmlspecialchars($h['descripcion_diagnostico']) ?>
                                                </div>
                                                <?php if ($h['tratamiento_prescrito']): ?>
                                                    <div class="history-treatment">
                                                        <strong>Tratamiento:</strong> <?= htmlspecialchars($h['tratamiento_prescrito']) ?>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if ($h['fecha_proxima_cita']): ?>
                                                    <div style="margin-top:4px;font-size:0.8rem;color:#d97706;">
                                                        <strong>Próxima cita:</strong> <?= date('d/m/Y', strtotime($h['fecha_proxima_cita'])) ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="empty-history">Seleccione un paciente para ver su historial completo</div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div class="empty-history">
                                        <svg width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        <p style="margin-top:10px;">Busque un paciente o seleccione uno de la lista para ver su historial clínico completo.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php elseif ($activeSection === 'reporte'): ?>
                <style>
                    .reporte-container { max-width: 1100px; margin: 0 auto; }
                    .reporte-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; margin-bottom: 20px; }
                    .reporte-header h2 { margin: 0; }
                    .reporte-filtro { display: flex; gap: 10px; align-items: center; }
                    .reporte-filtro input[type="date"] { padding: 8px 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.9rem; }
                    .reporte-filtro .btn { padding: 8px 16px; }
                    .reporte-resumen { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-bottom: 20px; }
                    .reporte-stat { background: white; border: 1px solid #e2e8f0; border-radius: 10px; padding: 15px; text-align: center; }
                    .reporte-stat .num { font-size: 1.5rem; font-weight: 700; color: var(--primary-green); }
                    .reporte-stat .label { font-size: 0.8rem; color: #718096; margin-top: 3px; }
                    .reporte-tabla { width: 100%; border-collapse: collapse; background: white; border-radius: 10px; overflow: hidden; border: 1px solid #e2e8f0; }
                    .reporte-tabla th { background: #f7fafc; padding: 12px 15px; text-align: left; font-size: 0.8rem; font-weight: 600; color: #4a5568; border-bottom: 2px solid #e2e8f0; }
                    .reporte-tabla td { padding: 10px 15px; border-bottom: 1px solid #edf2f7; font-size: 0.85rem; }
                    .reporte-tabla tr:hover td { background: #f7fafc; }
                    .btn-pdf { background: #dc2626; color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; font-size: 0.9rem; }
                    .btn-pdf:hover { background: #b91c1c; }
                    .no-datos { text-align: center; padding: 50px; color: #718096; background: white; border-radius: 10px; border: 1px solid #e2e8f0; }
                    @media print {
                        body * { visibility: hidden; }
                        .print-area, .print-area * { visibility: visible; }
                        .print-area { position: absolute; left: 0; top: 0; width: 100%; }
                        .no-print { display: none !important; }
                        .reporte-tabla th { background: #f0f0f0 !important; }
                    }
                </style>
                <div class="reporte-container">
                    <div class="reporte-header no-print">
                        <h2 class="section-title">Reporte Diario de Atenciones</h2>
                        <div class="reporte-filtro">
                            <form method="GET" style="display:flex;gap:10px;align-items:center;">
                                <input type="hidden" name="route" value="dashboard_medico">
                                <input type="hidden" name="section" value="reporte">
                                <input type="date" name="fecha_reporte" value="<?= htmlspecialchars($fechaReporte) ?>">
                                <button type="submit" class="btn btn-primary btn-sm">Ver</button>
                            </form>
                            <button onclick="window.print()" class="btn-pdf">
                                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/><path d="M6 14h12v8H6z"/></svg>
                                Exportar PDF
                            </button>
                        </div>
                    </div>

                    <div class="print-area">
                        <div style="text-align:center;margin-bottom:15px;display:none;" class="print-header">
                            <h2 style="margin:0;color:#066931;">Q-Line - Reporte Diario de Atenciones</h2>
                            <p style="margin:5px 0;color:#718096;">Dr. <?= htmlspecialchars($user['nombre'] . ' ' . $user['apellido']) ?> · <?= htmlspecialchars($miPersonal['nombre_especialidad'] ?? '') ?></p>
                            <p style="margin:0;color:#718096;">Fecha: <?= date('d/m/Y', strtotime($fechaReporte)) ?></p>
                        </div>

                        <div class="reporte-resumen">
                            <div class="reporte-stat">
                                <div class="num"><?= count($reporteDiario) ?></div>
                                <div class="label">Total atenciones</div>
                            </div>
                            <?php
                            $diagnosticos = array_column($reporteDiario, 'tipo_diagnostico');
                            $presuntivos = count(array_filter($diagnosticos, fn($d) => $d === 'Presuntivo'));
                            $definitivos = count(array_filter($diagnosticos, fn($d) => $d === 'Definitivo'));
                            ?>
                            <div class="reporte-stat">
                                <div class="num"><?= $presuntivos ?></div>
                                <div class="label">Diagnósticos presuntivos</div>
                            </div>
                            <div class="reporte-stat">
                                <div class="num"><?= $definitivos ?></div>
                                <div class="label">Diagnósticos definitivos</div>
                            </div>
                            <?php
                            $conCita = count(array_filter($reporteDiario, fn($r) => !empty($r['fecha_proxima_cita'])));
                            ?>
                            <div class="reporte-stat">
                                <div class="num"><?= $conCita ?></div>
                                <div class="label">Próximas citas agendadas</div>
                            </div>
                        </div>

                        <?php if (!empty($reporteDiario)): ?>
                            <table class="reporte-tabla">
                                <thead>
                                    <tr>
                                        <th>N°</th>
                                        <th>Hora</th>
                                        <th>Paciente</th>
                                        <th>Documento</th>
                                        <th>Ticket</th>
                                        <th>Consultorio</th>
                                        <th>Diagnóstico</th>
                                        <th>Tipo</th>
                                        <th>Tratamiento</th>
                                        <th>Próxima cita</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $n = 1; ?>
                                    <?php foreach ($reporteDiario as $r): ?>
                                    <tr>
                                        <td><?= $n++ ?></td>
                                        <td><?= date('H:i', strtotime($r['fecha_hora_atencion'])) ?></td>
                                        <td><?= htmlspecialchars($r['paciente_nombre'] . ' ' . $r['paciente_apellido']) ?></td>
                                        <td><?= htmlspecialchars($r['documento']) ?></td>
                                        <td><?= htmlspecialchars($r['codigo_ticket']) ?></td>
                                        <td>N° <?= htmlspecialchars($r['numero_consultorio']) ?></td>
                                        <td><?= htmlspecialchars($r['descripcion_diagnostico']) ?></td>
                                        <td><?= htmlspecialchars($r['tipo_diagnostico']) ?></td>
                                        <td><?= htmlspecialchars($r['tratamiento_prescrito'] ?? '-') ?></td>
                                        <td><?= $r['fecha_proxima_cita'] ? date('d/m/Y', strtotime($r['fecha_proxima_cita'])) : '-' ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="no-datos">
                                <svg width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                <p style="margin:10px 0 0;">No se encontraron atenciones para la fecha seleccionada.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
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
    var section = <?= json_encode($activeSection) ?>;
    if (section === 'tickets' || section === 'dashboard') {
        setTimeout(function() {
            location.reload();
        }, 20000);
    }
})();
</script>

<div class="mobile-only">
    <style>
        .mobile-alert { padding: 15px; border-radius: 8px; margin-top: 15px; }
        .mobile-alert-warning { background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; }
        .mobile-alert-success { background: #d1fae5; border: 1px solid #a7f3d0; color: #059669; }
    </style>
    <header class="mobile-header">
        <div class="hamburger" onclick="document.getElementById('mobileNav').classList.toggle('open')">
            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16"></path></svg>
        </div>
        <span class="mobile-logo">Q-Line</span>
    </header>
    
    <nav id="mobileNav" class="mobile-nav">
        <a href="?route=dashboard_medico&section=dashboard" class="mobile-nav-item <?= $activeSection === 'dashboard' ? 'active' : '' ?>">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
            Dashboard
        </a>
        <a href="?route=dashboard_medico&section=tickets" class="mobile-nav-item <?= $activeSection === 'tickets' ? 'active' : '' ?>">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path></svg>
            Cola de Tickets
        </a>
        <a href="?route=dashboard_medico&section=atencion" class="mobile-nav-item <?= $activeSection === 'atencion' ? 'active' : '' ?>">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
            Atención
        </a>
        <a href="?route=dashboard_medico&section=historial" class="mobile-nav-item <?= $activeSection === 'historial' ? 'active' : '' ?>">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
            Historial
        </a>
        <a href="?route=dashboard_medico&section=reporte" class="mobile-nav-item <?= $activeSection === 'reporte' ? 'active' : '' ?>">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
            Reporte
        </a>
        <a href="?route=dashboard_medico&section=perfil" class="mobile-nav-item <?= $activeSection === 'perfil' ? 'active' : '' ?>">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
            Mi Perfil
        </a>
        <a href="?route=logout" class="mobile-nav-item logout">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
            Cerrar Sesión
        </a>
    </nav>
    
    <main class="mobile-content">
        <?php if ($activeSection === 'dashboard'): ?>
        <h2 class="mobile-title">Dashboard</h2>
        <div class="mobile-card">
            <h3>Dr. <?= htmlspecialchars($user['nombre'] . ' ' . $user['apellido']) ?></h3>
            <p><?= $miPersonal ? htmlspecialchars($miPersonal['nombre_especialidad']) : 'Sin especialidad asignada' ?></p>
        </div>
        <div class="mobile-card">
            <h3>Pacientes en espera: <?= count($ticketsEspera) ?></h3>
            <p><?= count(array_filter($ticketsEspera, fn($t) => $t['estado'] === 'Llamado')) ?> llamados</p>
        </div>
        <?php elseif ($activeSection === 'tickets'): ?>
        <h2 class="mobile-title">Cola de Tickets</h2>
        <?php if (empty($ticketsEspera)): ?>
        <div class="mobile-card">
            <h3>Sin pacientes</h3>
            <p>No hay pacientes en espera actualmente</p>
        </div>
        <?php else: ?>
        <?php foreach ($ticketsEspera as $t): ?>
        <a href="?route=dashboard_medico&section=tickets&ticket_id=<?= $t['id_ticket'] ?>" style="text-decoration:none;color:inherit;">
            <div class="mobile-card">
                <h3><?= htmlspecialchars($t['paciente_nombre'] . ' ' . $t['paciente_apellido']) ?></h3>
                <p><?= htmlspecialchars($t['codigo_ticket']) ?> · <?= $t['estado'] ?> · Prioridad: <?= $t['prioridad'] ?></p>
            </div>
        </a>
        <?php endforeach; ?>
        <form method="POST" action="?route=dashboard_medico&section=tickets&action=llamar_siguiente">
            <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">Llamar siguiente</button>
        </form>
        <?php endif; ?>
        <?php elseif ($activeSection === 'atencion'): ?>
        <h2 class="mobile-title">Atención</h2>
        <?php if ($ticketSeleccionado): ?>
        <div class="mobile-card">
            <h3><?= htmlspecialchars($ticketSeleccionado['paciente_nombre'] . ' ' . $ticketSeleccionado['paciente_apellido']) ?></h3>
            <p><?= htmlspecialchars($ticketSeleccionado['codigo_ticket']) ?> · <?= $ticketSeleccionado['edad'] ?> años · Cons. <?= $ticketSeleccionado['numero_consultorio'] ?></p>
            <?php if (!empty($ticketSeleccionado['motivo_consulta'])): ?>
            <div style="background:#f7fafc;padding:10px;border-radius:8px;margin-top:8px;font-size:0.85rem;">
                <strong>Motivo:</strong> <?= htmlspecialchars($ticketSeleccionado['motivo_consulta']) ?>
            </div>
            <?php endif; ?>
        </div>
        <form method="POST" action="?route=dashboard_medico&section=atencion&action=guardar_atencion">
            <input type="hidden" name="id_ticket" value="<?= $ticketSeleccionado['id_ticket'] ?>">
            <select name="tipo_diagnostico" class="mobile-select" required>
                <option value="Presuntivo">Presuntivo</option>
                <option value="Definitivo">Definitivo</option>
            </select>
            <textarea name="descripcion_diagnostico" class="mobile-select" placeholder="Descripción del diagnóstico" required></textarea>
            <textarea name="tratamiento_prescrito" class="mobile-select" placeholder="Tratamiento prescrito"></textarea>
            <input type="date" name="fecha_proxima_cita" class="mobile-select">
            <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">Guardar atención</button>
        </form>
        <?php else: ?>
        <div class="mobile-card">
            <h3>Seleccione un paciente</h3>
            <p>Elija un paciente de la cola de tickets para iniciar la atención</p>
        </div>
        <?php foreach ($ticketsEspera as $t): ?>
        <a href="?route=dashboard_medico&section=atencion&ticket_id=<?= $t['id_ticket'] ?>" style="text-decoration:none;color:inherit;">
            <div class="mobile-card">
                <h3><?= htmlspecialchars($t['paciente_nombre'] . ' ' . $t['paciente_apellido']) ?></h3>
                <p><?= htmlspecialchars($t['codigo_ticket']) ?> · <?= $t['estado'] ?></p>
            </div>
        </a>
        <?php endforeach; ?>
        <?php endif; ?>
        <?php elseif ($activeSection === 'historial'): ?>
            <h2 class="mobile-title">Historial de Pacientes</h2>
            <form method="GET" style="margin-bottom:15px;">
                <input type="hidden" name="route" value="dashboard_medico">
                <input type="hidden" name="section" value="historial">
                <div style="display:flex;gap:8px;">
                    <input type="text" name="busqueda_paciente" placeholder="Buscar paciente..." value="<?= htmlspecialchars($busquedaPaciente) ?>" class="mobile-input" style="flex:1;">
                    <button type="submit" class="mobile-btn" style="flex:0;">Buscar</button>
                </div>
                <?php if (!empty($busquedaPaciente)): ?>
                    <a href="?route=dashboard_medico&section=historial" style="display:block;text-align:center;padding:8px;color:#718096;font-size:0.85rem;">Limpiar</a>
                <?php endif; ?>
            </form>
            <?php if (!empty($busquedaPaciente) && !empty($resultadosBusqueda)): ?>
                <?php foreach ($resultadosBusqueda as $p): ?>
                    <a href="?route=dashboard_medico&section=historial&id_paciente=<?= $p['id_paciente'] ?>&busqueda_paciente=<?= urlencode($busquedaPaciente) ?>" style="text-decoration:none;color:inherit;">
                        <div class="mobile-card">
                            <strong><?= htmlspecialchars($p['nombre'] . ' ' . $p['apellido']) ?></strong>
                            <p style="margin:2px 0 0;font-size:0.8rem;color:#718096;">Doc: <?= htmlspecialchars($p['documento']) ?></p>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php elseif (empty($busquedaPaciente) && !empty($pacientesAtendidos)): ?>
                <?php foreach ($pacientesAtendidos as $p): ?>
                    <a href="?route=dashboard_medico&section=historial&id_paciente=<?= $p['id_paciente'] ?>" style="text-decoration:none;color:inherit;">
                        <div class="mobile-card" style="display:flex;justify-content:space-between;align-items:center;">
                            <div>
                                <strong><?= htmlspecialchars($p['paciente_nombre'] . ' ' . $p['paciente_apellido']) ?></strong>
                                <p style="margin:2px 0 0;font-size:0.8rem;color:#718096;"><?= date('d/m/Y', strtotime($p['ultima_atencion'])) ?> · <?= $p['total_atenciones'] ?> atenciones</p>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="mobile-card">
                    <p style="text-align:center;color:#718096;">No se encontraron pacientes.<br>Use el buscador para encontrar pacientes.</p>
                </div>
            <?php endif; ?>
            <?php if ($pacienteSeleccionado && !empty($historialCompletoPaciente)): ?>
                <div class="mobile-card" style="margin-top:15px;">
                    <h3 style="margin-top:0;"><?= htmlspecialchars($pacienteSeleccionado['nombre'] . ' ' . $pacienteSeleccionado['apellido']) ?></h3>
                    <p style="font-size:0.8rem;color:#718096;">Doc: <?= htmlspecialchars($pacienteSeleccionado['documento'] ?? '') ?> · Tel: <?= htmlspecialchars($pacienteSeleccionado['telefono'] ?? 'N/A') ?></p>
                </div>
                <?php foreach ($historialCompletoPaciente as $h): ?>
                    <div class="mobile-card">
                        <div style="display:flex;justify-content:space-between;margin-bottom:6px;">
                            <strong style="color:#066931;font-size:0.85rem;"><?= date('d/m/Y', strtotime($h['fecha_hora_atencion'])) ?></strong>
                            <span style="font-size:0.75rem;color:#718096;"><?= htmlspecialchars($h['nombre_especialidad']) ?></span>
                        </div>
                        <p style="margin:4px 0;font-size:0.8rem;"><strong>Diagnóstico:</strong> <?= htmlspecialchars($h['descripcion_diagnostico']) ?></p>
                        <?php if ($h['tratamiento_prescrito']): ?>
                            <p style="margin:4px 0;font-size:0.8rem;"><strong>Tratamiento:</strong> <?= htmlspecialchars($h['tratamiento_prescrito']) ?></p>
                        <?php endif; ?>
                        <p style="margin:4px 0 0;font-size:0.75rem;color:#718096;">Dr. <?= htmlspecialchars($h['medico_nombre'] . ' ' . $h['medico_apellido']) ?> · <?= htmlspecialchars($h['codigo_ticket']) ?></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        <?php endif; ?>
    </main>
</div>
