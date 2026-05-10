<?php 
require_once dirname(__DIR__, 2) . '/Logic/Controllers/AuthController.php';
require_once dirname(__DIR__, 2) . '/Logic/Controllers/UserController.php';
require_once dirname(__DIR__, 2) . '/Logic/Controllers/EspecialidadController.php';
require_once dirname(__DIR__, 2) . '/Logic/Controllers/PersonalController.php';
require_once dirname(__DIR__, 2) . '/Logic/Controllers/HorarioController.php';
require_once dirname(__DIR__, 2) . '/Logic/Controllers/TicketController.php';

$auth = new AuthController();
$user = $auth->getUser();

if (!$user || $user['rol'] != 1) {
    header('Location: ?route=login');
    exit;
}

$activeSection = $_GET['view'] ?? 'dashboard';

$pagina = max(1, intval($_GET['page'] ?? 1));
$porPagina = 20;
$offset = ($pagina - 1) * $porPagina;

$userController = new UserController();
$totalUsuarios = $userController->contarUsuarios();
$usuarios = $userController->listUsers($porPagina, $offset);
$roles = $userController->listRoles();

$espCtrl = new EspecialidadController();
$especialidades = $espCtrl->listEspecialidades();
$consultorios = $espCtrl->listConsultorios();

$personalCtrl = new PersonalController();
$totalPersonal = $personalCtrl->contarPersonal();
$personal = $personalCtrl->listPersonal($porPagina, $offset);
$medicosSinPersonal = $personalCtrl->listMedicosSinPersonal();

$horarioCtrl = new HorarioController();
$horarios = $horarioCtrl->listarHorarios();
$personalHorarios = $horarioCtrl->listarPersonal();

$filtroEstado = $_GET['estado'] ?? '';
$paginaTickets = max(1, intval($_GET['page_tickets'] ?? 1));
$porPaginaTickets = 50;
$offsetTickets = ($paginaTickets - 1) * $porPaginaTickets;

$ticketCtrl = new TicketController();
$totalTickets = $ticketCtrl->contarTicketsAdmin($filtroEstado);
$todosTickets = $ticketCtrl->listarTicketsAdmin($filtroEstado, $porPaginaTickets, $offsetTickets);

$totalPacientes = $userController->contarUsuariosPorRol(3);
$totalMedicos = $userController->contarUsuariosPorRol(2);
$ticketsHoy = $ticketCtrl->contarTicketsHoy();
$ticketsEspera = $ticketCtrl->contarTicketsPorEstado('Espera');

$pageTitle = "Dashboard Administrador - Q-Line";
?>

<link rel="stylesheet" href="css/dashboard-common.css">

<!-- Desktop Version -->
<style>
    .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; }
    @media (max-width: 768px) { .two-col { grid-template-columns: 1fr; } }
</style>

<div class="desktop-only">
    <aside class="sidebar">
        <div class="logo-section">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-2 10h-4v4h-2v-4H7v-2h4V7h2v4h4v2z"/></svg>
            Q-Line
        </div>
        
        <div class="sidebar-content">
            <div class="sidebar-title">Administración</div>
            <a href="?route=dashboard_admin&view=dashboard" class="menu-item <?= $activeSection === 'dashboard' ? 'active' : '' ?>">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                Dashboard
            </a>
            <a href="?route=dashboard_admin&view=usuarios" class="menu-item <?= $activeSection === 'usuarios' ? 'active' : '' ?>">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                Gestión Usuarios
            </a>
            <a href="?route=dashboard_admin&view=personal" class="menu-item <?= $activeSection === 'personal' ? 'active' : '' ?>">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                Gestión Personal
            </a>
            <a href="?route=dashboard_admin&view=especialidades" class="menu-item <?= $activeSection === 'especialidades' ? 'active' : '' ?>">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                Especialidades
            </a>
            <a href="?route=dashboard_admin&view=horarios" class="menu-item <?= $activeSection === 'horarios' ? 'active' : '' ?>">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                Horarios
            </a>
            <a href="?route=dashboard_admin&view=tickets" class="menu-item <?= $activeSection === 'tickets' ? 'active' : '' ?>">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path></svg>
                Gestión Tickets
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
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($user['nombre'] . ' ' . $user['apellido']) ?>&background=white&color=066931" alt="Admin" width="100%">
                </div>
                <div class="user-details">
                    <h4>Admin <?= htmlspecialchars($user['nombre'] . ' ' . $user['apellido']) ?></h4>
                    <span>Administrador</span>
                </div>
            </div>
        </header>
        
        <div class="page-body">
            <?php if ($activeSection === 'dashboard'): ?>
                <style>
                    .admin-stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 30px; }
                    .admin-stat-card { background: white; border-radius: 14px; padding: 24px; border: 1px solid var(--border-color); box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
                    .admin-stat-card .stat-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-bottom: 15px; }
                    .admin-stat-card .stat-icon svg { width: 24px; height: 24px; }
                    .admin-stat-card .stat-number { font-size: 2rem; font-weight: 800; color: #2d3748; }
                    .admin-stat-card .stat-label { font-size: 0.85rem; color: #718096; margin-top: 4px; }
                </style>
                <h2 class="section-title">Panel de Administración</h2>
                <div class="admin-stat-grid">
                    <div class="admin-stat-card">
                        <div class="stat-icon" style="background:#d1fae5;color:#059669;">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        </div>
                        <div class="stat-number"><?= $totalPacientes ?></div>
                        <div class="stat-label">Pacientes registrados</div>
                    </div>
                    <div class="admin-stat-card">
                        <div class="stat-icon" style="background:#dbeafe;color:#2563eb;">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        </div>
                        <div class="stat-number"><?= $totalMedicos ?></div>
                        <div class="stat-label">Médicos activos</div>
                    </div>
                    <div class="admin-stat-card">
                        <div class="stat-icon" style="background:#fef3c7;color:#d97706;">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
                        </div>
                        <div class="stat-number"><?= $ticketsHoy ?></div>
                        <div class="stat-label">Turnos solicitados hoy</div>
                    </div>
                    <div class="admin-stat-card">
                        <div class="stat-icon" style="background:#fee2e2;color:#dc2626;">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div class="stat-number"><?= $ticketsEspera ?></div>
                        <div class="stat-label">Pacientes en espera ahora</div>
                    </div>
                </div>
            
            <?php elseif ($activeSection === 'usuarios'): ?>
                <h2 class="section-title">Gestión de Usuarios</h2>
                <table class="data-table">
                    <thead><tr><th>Nombre</th><th>Apellido</th><th>Usuario</th><th>Rol</th><th>Cambiar</th></tr></thead>
                    <tbody>
                        <?php foreach ($usuarios as $u): ?>
                        <tr>
                            <td><?= htmlspecialchars($u['nombre']) ?></td>
                            <td><?= htmlspecialchars($u['apellido']) ?></td>
                            <td><?= htmlspecialchars($u['nombre_usuario']) ?></td>
                            <td><span class="badge badge-<?= $u['id_rol'] ?>"><?= htmlspecialchars($u['nombre_rol']) ?></span></td>
                            <td>
                                <form method="POST" action="?route=dashboard_admin&action=update_role">
                                    <input type="hidden" name="id_usuario" value="<?= $u['id_usuario'] ?>">
                                    <select name="id_rol" style="padding:5px; border:1px solid #ddd; border-radius:4px;">
                                        <?php foreach ($roles as $r): ?>
                                            <option value="<?= $r['id_rol'] ?>" <?= $r['id_rol'] == $u['id_rol'] ? 'selected' : '' ?>><?= htmlspecialchars($r['nombre_rol']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="btn-small">Cambiar</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php $totalPaginasUsuarios = ceil($totalUsuarios / $porPagina); ?>
                <?php if ($totalPaginasUsuarios > 1): ?>
                <div style="display:flex;justify-content:center;gap:8px;margin-top:20px;">
                    <?php for ($i = 1; $i <= $totalPaginasUsuarios; $i++): ?>
                        <a href="?route=dashboard_admin&view=usuarios&page=<?= $i ?>" style="padding:8px 14px;background:<?= $i === $pagina ? '#066931' : '#f0f0f0' ?>;color:<?= $i === $pagina ? 'white' : '#333' ?>;border-radius:6px;text-decoration:none;font-weight:<?= $i === $pagina ? 'bold' : 'normal' ?>;"><?= $i ?></a>
                    <?php endfor; ?>
                </div>
                <?php endif; ?>
            
            <?php elseif ($activeSection === 'especialidades'): ?>
                <h2 class="section-title">Especialidades y Consultorios</h2>
                <div class="two-col">
                    <div class="panel">
                        <h3>Nueva Especialidad</h3>
                        <form method="POST" action="?route=dashboard_admin&action=create_esp">
                            <div class="form-group"><input type="text" name="nombre" placeholder="Nombre" required></div>
                            <button type="submit" class="btn-small">Crear</button>
                        </form>
                        <div style="margin-top:15px;">
                            <?php foreach ($especialidades as $e): ?>
                                <span class="tag-item">
                                    <?= htmlspecialchars($e['nombre_especialidad']) ?>
                                    <form method="POST" action="?route=dashboard_admin&action=delete_esp" style="display:inline;">
                                        <input type="hidden" name="id_especialidad" value="<?= $e['id_especialidad'] ?>">
                                        <button type="submit" class="btn-small btn-danger" style="padding:2px 6px;">×</button>
                                    </form>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="panel">
                        <h3>Nuevo Consultorio</h3>
                        <form method="POST" action="?route=dashboard_admin&action=create_cons">
                            <div class="form-group">
                                <select name="id_especialidad" required>
                                    <option value="">Especialidad</option>
                                    <?php foreach ($especialidades as $e): ?>
                                        <option value="<?= $e['id_especialidad'] ?>"><?= htmlspecialchars($e['nombre_especialidad']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group"><input type="text" name="numero" placeholder="Número" required></div>
                            <div class="form-group"><input type="text" name="piso" placeholder="Piso (opcional)"></div>
                            <button type="submit" class="btn-small">Crear</button>
                        </form>
                    </div>
                </div>
            
            <?php elseif ($activeSection === 'personal'): ?>
                <h2 class="section-title">Personal Médico</h2>
                <div class="two-col">
                    <div class="panel">
                        <h3>Agregar Médico</h3>
                        <?php if (count($medicosSinPersonal) > 0): ?>
                            <form method="POST" action="?route=dashboard_admin&action=create_personal">
                                <div class="form-group">
                                    <select name="id_usuario" required>
                                        <option value="">Médico</option>
                                        <?php foreach ($medicosSinPersonal as $m): ?>
                                            <option value="<?= $m['id_usuario'] ?>"><?= htmlspecialchars($m['apellido'] . ' ' . $m['nombre']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <select name="id_especialidad" required>
                                        <option value="">Especialidad</option>
                                        <?php foreach ($especialidades as $e): ?>
                                            <option value="<?= $e['id_especialidad'] ?>"><?= htmlspecialchars($e['nombre_especialidad']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group"><input type="text" name="matricula" placeholder="Matrícula" required></div>
                                <button type="submit" class="btn-small">Agregar</button>
                            </form>
                        <?php else: ?><p>No hay médicos sin registrar</p><?php endif; ?>
                    </div>
                    <div class="panel">
                        <h3>Personal Registrado</h3>
                        <?php foreach ($personal as $p): ?>
                            <div style="padding:10px; background:#f5f5f5; border-radius:6px; margin:5px 0; display:flex; justify-content:space-between; align-items:center;">
                                <div>
                                    <strong><?= htmlspecialchars($p['apellido'] . ' ' . $p['nombre']) ?></strong><br>
                                    <small><?= htmlspecialchars($p['nombre_especialidad']) ?></small>
                                </div>
                                <form method="POST" action="?route=dashboard_admin&action=delete_personal">
                                    <input type="hidden" name="id_personal" value="<?= $p['id_personal'] ?>">
                                    <button type="submit" class="btn-small btn-danger">Eliminar</button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php $totalPaginasPersonal = ceil($totalPersonal / $porPagina); ?>
                <?php if ($totalPaginasPersonal > 1): ?>
                <div style="display:flex;justify-content:center;gap:8px;margin-top:20px;">
                    <?php for ($i = 1; $i <= $totalPaginasPersonal; $i++): ?>
                        <a href="?route=dashboard_admin&view=personal&page=<?= $i ?>" style="padding:8px 14px;background:<?= $i === $pagina ? '#066931' : '#f0f0f0' ?>;color:<?= $i === $pagina ? 'white' : '#333' ?>;border-radius:6px;text-decoration:none;font-weight:<?= $i === $pagina ? 'bold' : 'normal' ?>;"><?= $i ?></a>
                    <?php endfor; ?>
                </div>
                <?php endif; ?>
            
            <?php elseif ($activeSection === 'horarios'): ?>
                <style>
                    .horario-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
                    .horario-card { background: white; border: 1px solid #e2e8f0; border-radius: 10px; padding: 15px; margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center; }
                    .horario-badge { display: inline-block; padding: 4px 10px; border-radius: 4px; font-size: 0.75rem; font-weight: bold; }
                    .dia-Lunes { background: #eff6ff; color: #1e40af; }
                    .dia-Martes { background: #fef3c7; color: #92400e; }
                    .dia-Miercoles { background: #d1fae5; color: #065f46; }
                    .dia-Jueves { background: #fce7f3; color: #9d174d; }
                    .dia-Viernes { background: #dbeafe; color: #1e40af; }
                    .dia-Sabado { background: #e0e7ff; color: #3730a3; }
                    .dia-Domingo { background: #fee2e2; color: #991b1b; }
                    .asignacion-item { display: flex; justify-content: space-between; align-items: center; padding: 8px 12px; background: #f9fafb; border-radius: 6px; margin-bottom: 5px; }
                    .asignacion-item .tags { display: flex; gap: 5px; flex-wrap: wrap; }
                    .tag-horario { background: #edf2f7; padding: 2px 8px; border-radius: 4px; font-size: 0.75rem; }
                    @media (max-width: 768px) { .horario-grid { grid-template-columns: 1fr; } }
                </style>
                <h2 class="section-title">Gestión de Horarios</h2>
                <div class="horario-grid">
                    <div>
                        <div class="panel">
                            <h3>Nuevo Horario</h3>
                            <form method="POST" action="?route=dashboard_admin&action=create_horario">
                                <div class="form-group">
                                    <select name="dia_semana" required>
                                        <option value="">Día de la semana</option>
                                        <option value="Lunes">Lunes</option>
                                        <option value="Martes">Martes</option>
                                        <option value="Miercoles">Miércoles</option>
                                        <option value="Jueves">Jueves</option>
                                        <option value="Viernes">Viernes</option>
                                        <option value="Sabado">Sábado</option>
                                        <option value="Domingo">Domingo</option>
                                    </select>
                                </div>
                                <div style="display:flex;gap:10px;">
                                    <div class="form-group" style="flex:1;">
                                        <label style="font-size:0.8rem;color:#718096;">Inicio</label>
                                        <input type="time" name="hora_inicio" required>
                                    </div>
                                    <div class="form-group" style="flex:1;">
                                        <label style="font-size:0.8rem;color:#718096;">Fin</label>
                                        <input type="time" name="hora_fin" required>
                                    </div>
                                </div>
                                <button type="submit" class="btn-small">Crear Horario</button>
                            </form>
                        </div>

                        <div class="panel">
                            <h3>Horarios Registrados</h3>
                            <?php if (!empty($horarios)): ?>
                                <?php foreach ($horarios as $h): ?>
                                    <div class="horario-card">
                                        <div>
                                            <span class="horario-badge dia-<?= $h['dia_semana'] ?>"><?= htmlspecialchars($h['dia_semana']) ?></span>
                                            <span style="font-weight:600;margin-left:10px;"><?= htmlspecialchars(substr($h['hora_inicio'],0,5)) ?> - <?= htmlspecialchars(substr($h['hora_fin'],0,5)) ?></span>
                                        </div>
                                        <form method="POST" action="?route=dashboard_admin&action=delete_horario" style="display:inline;">
                                            <input type="hidden" name="id_horario" value="<?= $h['id_horario'] ?>">
                                            <button type="submit" class="btn-small btn-danger">×</button>
                                        </form>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p style="color:#718096;">No hay horarios registrados</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div>
                        <div class="panel">
                            <h3>Asignar Horario a Médico</h3>
                            <form method="POST" action="?route=dashboard_admin&action=asignar_horario">
                                <div class="form-group">
                                    <select name="id_personal" required>
                                        <option value="">Seleccionar médico</option>
                                        <?php foreach ($personalHorarios as $p): ?>
                                            <option value="<?= $p['id_personal'] ?>"><?= htmlspecialchars($p['apellido'] . ' ' . $p['nombre']) ?> (<?= htmlspecialchars($p['nombre_especialidad']) ?>)</option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <select name="id_horario" required>
                                        <option value="">Seleccionar horario</option>
                                        <?php foreach ($horarios as $h): ?>
                                            <option value="<?= $h['id_horario'] ?>"><?= htmlspecialchars($h['dia_semana']) ?> - <?= htmlspecialchars(substr($h['hora_inicio'],0,5)) ?> a <?= htmlspecialchars(substr($h['hora_fin'],0,5)) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button type="submit" class="btn-small">Asignar</button>
                            </form>
                        </div>

                        <div class="panel">
                            <h3>Horarios por Médico</h3>
                            <?php if (!empty($personalHorarios)): ?>
                                <?php foreach ($personalHorarios as $p): ?>
                                    <?php $horariosMedico = $horarioCtrl->horariosPorPersonal($p['id_personal']); ?>
                                    <div class="asignacion-item">
                                        <div>
                                            <strong><?= htmlspecialchars($p['apellido'] . ' ' . $p['nombre']) ?></strong>
                                            <small style="color:#718096;display:block;"><?= htmlspecialchars($p['nombre_especialidad']) ?></small>
                                        </div>
                                        <div class="tags">
                                            <?php if (!empty($horariosMedico)): ?>
                                                <?php foreach ($horariosMedico as $h): ?>
                                                    <span class="tag-horario">
                                                        <?= htmlspecialchars($h['dia_semana']) ?> <?= htmlspecialchars(substr($h['hora_inicio'],0,5)) ?>
                                                        <form method="POST" action="?route=dashboard_admin&action=desasignar_horario" style="display:inline;">
                                                            <input type="hidden" name="id_personal" value="<?= $p['id_personal'] ?>">
                                                            <input type="hidden" name="id_horario" value="<?= $h['id_horario'] ?>">
                                                            <button type="submit" style="background:none;border:none;color:#dc2626;cursor:pointer;padding:0;font-size:0.8rem;">×</button>
                                                        </form>
                                                    </span>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <small style="color:#a0aec0;">Sin horarios</small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p style="color:#718096;">No hay personal registrado</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

            <?php elseif ($activeSection === 'tickets'): ?>
                <h2 class="section-title">Gestión de Tickets</h2>
                <div style="margin-bottom:20px;display:flex;gap:10px;align-items:center;">
                    <a href="?route=dashboard_admin&view=tickets" class="btn-small <?= empty($filtroEstado) ? 'btn-small' : '' ?>" style="<?= empty($filtroEstado) ? 'background:#066931;color:white;' : 'background:#f0f0f0;color:#333;text-decoration:none;' ?>padding:8px 16px;border-radius:6px;">Todos</a>
                    <a href="?route=dashboard_admin&view=tickets&estado=Espera" class="btn-small" style="<?= $filtroEstado === 'Espera' ? 'background:#066931;color:white;' : 'background:#f0f0f0;color:#333;text-decoration:none;' ?>padding:8px 16px;border-radius:6px;">Espera</a>
                    <a href="?route=dashboard_admin&view=tickets&estado=Llamado" class="btn-small" style="<?= $filtroEstado === 'Llamado' ? 'background:#066931;color:white;' : 'background:#f0f0f0;color:#333;text-decoration:none;' ?>padding:8px 16px;border-radius:6px;">Llamado</a>
                    <a href="?route=dashboard_admin&view=tickets&estado=Atendido" class="btn-small" style="<?= $filtroEstado === 'Atendido' ? 'background:#066931;color:white;' : 'background:#f0f0f0;color:#333;text-decoration:none;' ?>padding:8px 16px;border-radius:6px;">Atendido</a>
                    <a href="?route=dashboard_admin&view=tickets&estado=Ausente" class="btn-small" style="<?= $filtroEstado === 'Ausente' ? 'background:#066931;color:white;' : 'background:#f0f0f0;color:#333;text-decoration:none;' ?>padding:8px 16px;border-radius:6px;">Ausente</a>
                    <span style="color:#718096;font-size:0.85rem;margin-left:auto;">Total: <?= $totalTickets ?></span>
                </div>
                <table class="data-table">
                    <thead><tr><th>Código</th><th>Paciente</th><th>Documento</th><th>Especialidad</th><th>Consultorio</th><th>Estado</th><th>Prioridad</th><th>Fecha</th></tr></thead>
                    <tbody>
                        <?php if (!empty($todosTickets)): ?>
                            <?php foreach ($todosTickets as $t): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($t['codigo_ticket']) ?></strong></td>
                                <td><?= htmlspecialchars($t['paciente_nombre'] . ' ' . $t['paciente_apellido']) ?></td>
                                <td><?= htmlspecialchars($t['documento']) ?></td>
                                <td><?= htmlspecialchars($t['nombre_especialidad']) ?></td>
                                <td>N° <?= htmlspecialchars($t['numero_consultorio']) ?></td>
                                <td><span class="badge badge-<?= match($t['estado']) { 'Espera' => '3', 'Llamado' => '2', 'Atendido' => '1', default => '3' } ?>"><?= $t['estado'] ?></span></td>
                                <td><span class="badge badge-<?= match($t['prioridad']) { 'Rojo' => '3', 'Amarillo' => '3', default => '1' } ?>"><?= $t['prioridad'] ?></span></td>
                                <td><?= date('d/m/Y H:i', strtotime($t['fecha_creacion'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="8" style="text-align:center;color:#718096;padding:40px;">No hay tickets registrados</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <?php $totalPaginasTickets = ceil($totalTickets / $porPaginaTickets); ?>
                <?php if ($totalPaginasTickets > 1): ?>
                <div style="display:flex;justify-content:center;gap:8px;margin-top:20px;">
                    <?php for ($i = 1; $i <= $totalPaginasTickets; $i++): ?>
                        <a href="?route=dashboard_admin&view=tickets<?= $filtroEstado ? '&estado=' . urlencode($filtroEstado) : '' ?>&page_tickets=<?= $i ?>" style="padding:8px 14px;background:<?= $i === $paginaTickets ? '#066931' : '#f0f0f0' ?>;color:<?= $i === $paginaTickets ? 'white' : '#333' ?>;border-radius:6px;text-decoration:none;font-weight:<?= $i === $paginaTickets ? 'bold' : 'normal' ?>;"><?= $i ?></a>
                    <?php endfor; ?>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </main>
</div>

<!-- Mobile Version -->
<div class="mobile-only">
    <header class="mobile-header">
        <div class="hamburger" onclick="document.getElementById('mobileNav').classList.toggle('open')">
            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16"></path></svg>
        </div>
        <span class="mobile-logo">Q-Line</span>
    </header>
    
    <nav id="mobileNav" class="mobile-nav">
        <a href="?route=dashboard_admin&view=dashboard" class="mobile-nav-item <?= $activeSection === 'dashboard' ? 'active' : '' ?>">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
            Dashboard
        </a>
        <a href="?route=dashboard_admin&view=usuarios" class="mobile-nav-item <?= $activeSection === 'usuarios' ? 'active' : '' ?>">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
            Usuarios
        </a>
        <a href="?route=dashboard_admin&view=personal" class="mobile-nav-item <?= $activeSection === 'personal' ? 'active' : '' ?>">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
            Personal
        </a>
        <a href="?route=dashboard_admin&view=especialidades" class="mobile-nav-item <?= $activeSection === 'especialidades' ? 'active' : '' ?>">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
            Especialidades
        </a>
        <a href="?route=dashboard_admin&view=horarios" class="mobile-nav-item <?= $activeSection === 'horarios' ? 'active' : '' ?>">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            Horarios
        </a>
        <a href="?route=dashboard_admin&view=tickets" class="mobile-nav-item <?= $activeSection === 'tickets' ? 'active' : '' ?>">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path></svg>
            Tickets
        </a>
        <a href="?route=logout" class="mobile-nav-item logout">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
            Cerrar Sesión
        </a>
    </nav>
    
    <main class="mobile-content">
        <div class="mobile-card">
            <h3 style="color:var(--primary-green); margin:0;">Admin <?= htmlspecialchars($user['nombre'] . ' ' . $user['apellido']) ?></h3>
            <p style="color:#718096; margin:5px 0 0;">Administrador</p>
        </div>
        
        <h2 class="mobile-title">
            <?= match($activeSection) { 'dashboard' => 'Panel Admin', 'usuarios' => 'Usuarios', 'personal' => 'Personal', 'especialidades' => 'Especialidades', 'horarios' => 'Horarios', 'tickets' => 'Tickets', default => 'Admin' } ?>
        </h2>
        
        <?php if ($activeSection === 'usuarios'): ?>
            <?php foreach ($usuarios as $u): ?>
                <div class="mobile-card">
                    <strong><?= htmlspecialchars($u['nombre'] . ' ' . $u['apellido']) ?></strong><br>
                    <small><?= htmlspecialchars($u['nombre_usuario']) ?></small>
                    <form method="POST" action="?route=dashboard_admin&action=update_role" style="margin-top:10px;">
                        <input type="hidden" name="id_usuario" value="<?= $u['id_usuario'] ?>">
                        <select name="id_rol" class="mobile-select">
                            <?php foreach ($roles as $r): ?>
                                <option value="<?= $r['id_rol'] ?>" <?= $r['id_rol'] == $u['id_rol'] ? 'selected' : '' ?>><?= htmlspecialchars($r['nombre_rol']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="mobile-btn">Cambiar Rol</button>
                    </form>
                </div>
            <?php endforeach; ?>
        
        <?php elseif ($activeSection === 'personal'): ?>
            <div class="mobile-card">
                <h3 style="margin-top:0;">Agregar Médico a Personal</h3>
                <?php if (count($medicosSinPersonal) > 0): ?>
                    <form method="POST" action="?route=dashboard_admin&action=create_personal">
                        <select name="id_usuario" class="mobile-select" required>
                            <option value="">Seleccionar Médico</option>
                            <?php foreach ($medicosSinPersonal as $m): ?>
                                <option value="<?= $m['id_usuario'] ?>"><?= htmlspecialchars($m['apellido'] . ' ' . $m['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select name="id_especialidad" class="mobile-select" required>
                            <option value="">Especialidad</option>
                            <?php foreach ($especialidades as $e): ?>
                                <option value="<?= $e['id_especialidad'] ?>"><?= htmlspecialchars($e['nombre_especialidad']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="text" name="matricula" class="mobile-input" placeholder="Matrícula profesional" required>
                        <button type="submit" class="mobile-btn">Agregar</button>
                    </form>
                <?php else: ?>
                    <p>No hay médicos sin registrar en personal</p>
                <?php endif; ?>
            </div>
        
        <?php elseif ($activeSection === 'especialidades'): ?>
            <div class="mobile-card">
                <h3 style="margin-top:0;">Nueva Especialidad</h3>
                <form method="POST" action="?route=dashboard_admin&action=create_esp">
                    <input type="text" name="nombre" class="mobile-input" placeholder="Nombre" required>
                    <button type="submit" class="mobile-btn">Crear</button>
                </form>
            </div>
            <div class="mobile-card">
                <h3 style="margin-top:0;">Nuevo Consultorio</h3>
                <form method="POST" action="?route=dashboard_admin&action=create_cons">
                    <select name="id_especialidad" class="mobile-select" required>
                        <option value="">Especialidad</option>
                        <?php foreach ($especialidades as $e): ?>
                            <option value="<?= $e['id_especialidad'] ?>"><?= htmlspecialchars($e['nombre_especialidad']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="text" name="numero" class="mobile-input" placeholder="Número" required>
                    <input type="text" name="piso" class="mobile-input" placeholder="Piso">
                    <button type="submit" class="mobile-btn">Crear</button>
                </form>
            </div>
        
        <?php elseif ($activeSection === 'horarios'): ?>
            <div class="mobile-card">
                <h3 style="margin-top:0;">Nuevo Horario</h3>
                <form method="POST" action="?route=dashboard_admin&action=create_horario">
                    <select name="dia_semana" class="mobile-select" required>
                        <option value="">Día</option>
                        <option value="Lunes">Lunes</option><option value="Martes">Martes</option>
                        <option value="Miercoles">Miércoles</option><option value="Jueves">Jueves</option>
                        <option value="Viernes">Viernes</option><option value="Sabado">Sábado</option>
                        <option value="Domingo">Domingo</option>
                    </select>
                    <input type="time" name="hora_inicio" class="mobile-input" required>
                    <input type="time" name="hora_fin" class="mobile-input" required>
                    <button type="submit" class="mobile-btn">Crear</button>
                </form>
            </div>
            <div class="mobile-card">
                <h3 style="margin-top:0;">Horarios Registrados</h3>
                <?php if (!empty($horarios)): ?>
                    <?php foreach ($horarios as $h): ?>
                        <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid #edf2f7;">
                            <span><?= htmlspecialchars($h['dia_semana']) ?> <?= htmlspecialchars(substr($h['hora_inicio'],0,5)) ?>-<?= htmlspecialchars(substr($h['hora_fin'],0,5)) ?></span>
                            <form method="POST" action="?route=dashboard_admin&action=delete_horario" style="display:inline;">
                                <input type="hidden" name="id_horario" value="<?= $h['id_horario'] ?>">
                                <button type="submit" style="background:#dc2626;color:white;border:none;border-radius:4px;padding:4px 8px;cursor:pointer;">×</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No hay horarios</p>
                <?php endif; ?>
            </div>
            <div class="mobile-card">
                <h3 style="margin-top:0;">Asignar Horario</h3>
                <form method="POST" action="?route=dashboard_admin&action=asignar_horario">
                    <select name="id_personal" class="mobile-select" required>
                        <option value="">Médico</option>
                        <?php foreach ($personalHorarios as $p): ?>
                            <option value="<?= $p['id_personal'] ?>"><?= htmlspecialchars($p['apellido'] . ' ' . $p['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="id_horario" class="mobile-select" required>
                        <option value="">Horario</option>
                        <?php foreach ($horarios as $h): ?>
                            <option value="<?= $h['id_horario'] ?>"><?= htmlspecialchars($h['dia_semana']) ?> <?= htmlspecialchars(substr($h['hora_inicio'],0,5)) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="mobile-btn">Asignar</button>
                </form>
            </div>
            <div class="mobile-card">
                <h3 style="margin-top:0;">Horarios por Médico</h3>
                <?php foreach ($personalHorarios as $p): ?>
                    <?php $horariosMedico = $horarioCtrl->horariosPorPersonal($p['id_personal']); ?>
                    <div style="padding:8px 0;border-bottom:1px solid #edf2f7;">
                        <strong><?= htmlspecialchars($p['apellido'] . ' ' . $p['nombre']) ?></strong>
                        <div style="margin-top:5px;">
                            <?php if (!empty($horariosMedico)): ?>
                                <?php foreach ($horariosMedico as $h): ?>
                                    <span style="display:inline-block;background:#edf2f7;padding:2px 8px;border-radius:4px;font-size:0.8rem;margin:2px;">
                                        <?= htmlspecialchars($h['dia_semana']) ?> <?= htmlspecialchars(substr($h['hora_inicio'],0,5)) ?>
                                    </span>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <small style="color:#a0aec0;">Sin horarios</small>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php elseif ($activeSection === 'tickets'): ?>
            <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:15px;">
                <a href="?route=dashboard_admin&view=tickets" class="mobile-btn" style="<?= empty($filtroEstado) ? 'background:#066931;' : 'background:#ccc;color:#333;' ?>flex:1;text-align:center;text-decoration:none;padding:10px;border-radius:8px;font-size:0.85rem;">Todos</a>
                <a href="?route=dashboard_admin&view=tickets&estado=Espera" class="mobile-btn" style="<?= $filtroEstado === 'Espera' ? 'background:#066931;' : 'background:#ccc;color:#333;' ?>flex:1;text-align:center;text-decoration:none;padding:10px;border-radius:8px;font-size:0.85rem;">Espera</a>
                <a href="?route=dashboard_admin&view=tickets&estado=Atendido" class="mobile-btn" style="<?= $filtroEstado === 'Atendido' ? 'background:#066931;' : 'background:#ccc;color:#333;' ?>flex:1;text-align:center;text-decoration:none;padding:10px;border-radius:8px;font-size:0.85rem;">Atendido</a>
            </div>
            <?php if (!empty($todosTickets)): ?>
                <?php foreach ($todosTickets as $t): ?>
                    <div class="mobile-card">
                        <div style="display:flex;justify-content:space-between;">
                            <strong><?= htmlspecialchars($t['codigo_ticket']) ?></strong>
                            <span style="font-size:0.8rem;"><?= $t['estado'] ?></span>
                        </div>
                        <p style="margin:5px 0;font-size:0.85rem;"><?= htmlspecialchars($t['paciente_nombre'] . ' ' . $t['paciente_apellido']) ?></p>
                        <small style="color:#718096;"><?= htmlspecialchars($t['nombre_especialidad']) ?> · N° <?= htmlspecialchars($t['numero_consultorio']) ?> · <?= date('d/m/Y', strtotime($t['fecha_creacion'])) ?></small>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="mobile-card">
                    <p>No hay tickets registrados</p>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <p style="color:#718096;">Use el menú hamburguesa para navegar</p>
        <?php endif; ?>
    </main>
</div>