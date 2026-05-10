<?php
require_once dirname(__DIR__, 2) . '/Logic/Controllers/AuthController.php';
require_once dirname(__DIR__, 2) . '/Logic/Controllers/TicketController.php';

$auth = new AuthController();
$user = $auth->getUser();
if (!$user || $user['rol'] != 3) {
    header('Location: ?route=login');
    exit;
}

$ticketInfo = $_SESSION['ultimo_ticket'] ?? null;
$personasAntes = $_SESSION['personas_antes'] ?? 0;

if (!$ticketInfo) {
    header('Location: ?route=dashboard_paciente');
    exit;
}

$fechaFormateada = date('M d, Y', strtotime($ticketInfo['fecha_creacion']));
$horaFormateada = date('h:i A', strtotime($ticketInfo['fecha_creacion']));
$prioridadTexto = $ticketInfo['prioridad'] === 'Verde' ? 'ESTÁNDAR' : ($ticketInfo['prioridad'] === 'Amarillo' ? 'URGENTE' : 'EMERGENCIA');
$prioridadBadgeClass = $ticketInfo['prioridad'] === 'Verde' ? '#e6fffa' : ($ticketInfo['prioridad'] === 'Amarillo' ? '#fef3c7' : '#fee2e2');
$prioridadBadgeText = $ticketInfo['prioridad'] === 'Verde' ? '#2c7a7b' : ($ticketInfo['prioridad'] === 'Amarillo' ? '#92400e' : '#991b1b');
$prioridadBadgeBorder = $ticketInfo['prioridad'] === 'Verde' ? '#b2f5ea' : ($ticketInfo['prioridad'] === 'Amarillo' ? '#fde68a' : '#fecaca');
?>
<style>
    :root {
        --primary-green: #28a745;
        --dark-green: #066931;
        --bg-light: #f0f4f8;
        --text-main: #1a202c;
        --text-muted: #718096;
        --white: #ffffff;
    }
    body { margin: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: var(--bg-light); display: flex; justify-content: center; align-items: center; min-height: 100vh; color: var(--text-main); }
    .container { max-width: 900px; width: 100%; padding: 20px; text-align: center; }
    .success-icon { background-color: var(--primary-green); color: white; width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3); }
    .success-icon svg { width: 40px; height: 40px; }
    h1 { font-size: 2.2rem; margin: 10px 0; }
    .subtitle { color: var(--text-muted); margin-bottom: 40px; }
    .cards-container { display: grid; grid-template-columns: 1.5fr 1fr; gap: 25px; margin-bottom: 40px; }
    .ticket-card { background: var(--white); border-radius: 16px; display: flex; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.05); text-align: left; }
    .ticket-left { background-color: var(--primary-green); color: white; padding: 40px 20px; width: 40%; display: flex; flex-direction: column; align-items: center; justify-content: center; }
    .ticket-left span { font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px; opacity: 0.9; }
    .ticket-number { font-size: 3.5rem; font-weight: 800; margin: 10px 0; }
    .ticket-right { padding: 30px; flex: 1; display: flex; flex-direction: column; justify-content: space-between; }
    .info-group label { font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight: bold; }
    .info-group p { margin: 5px 0 15px; font-size: 1.3rem; font-weight: 600; }
    .priority-badge { background-color: <?= $prioridadBadgeClass ?>; color: <?= $prioridadBadgeText ?>; padding: 4px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: bold; border: 1px solid <?= $prioridadBadgeBorder ?>; display: inline-block; }
    .waiting-card { background: #edf2f7; border-radius: 16px; padding: 25px; text-align: left; border: 1px solid #e2e8f0; }
    .waiting-header { display: flex; align-items: center; gap: 10px; color: #3182ce; font-size: 0.85rem; font-weight: bold; margin-bottom: 15px; }
    .room-title { font-size: 1.5rem; font-weight: bold; margin: 0 0 10px; }
    .room-desc { font-size: 0.9rem; color: var(--text-muted); margin-bottom: 20px; line-height: 1.4; }
    .waiting-stat { background: white; padding: 12px; border-radius: 8px; display: flex; align-items: center; gap: 15px; margin-bottom: 10px; }
    .stat-icon { color: #3182ce; }
    .stat-text span { font-size: 0.75rem; color: var(--text-muted); display: block; }
    .stat-text strong { font-size: 0.9rem; }
    .btn-finalize { background-color: var(--dark-green); color: white; border: none; padding: 18px 60px; border-radius: 12px; font-size: 1.2rem; font-weight: bold; cursor: pointer; display: inline-flex; align-items: center; gap: 15px; margin: 0 auto 15px; transition: transform 0.2s; text-decoration: none; }
    .btn-finalize:hover { background-color: #045226; transform: scale(1.02); }
    .print-note { font-size: 0.8rem; color: var(--text-muted); }
    .stepper { text-align: center; margin-bottom: 20px; }
    .dots { display: flex; gap: 8px; justify-content: center; margin-bottom: 5px; }
    .dot { width: 8px; height: 8px; background: #cbd5e0; border-radius: 50%; }
    .dot.active { background: var(--primary-green); }
    .stepper span { font-size: 0.8rem; color: var(--text-muted); }
    @media (max-width: 768px) { .cards-container { grid-template-columns: 1fr; } .ticket-card { flex-direction: column; } .ticket-left { width: 100%; padding: 30px; } }
</style>

<div class="container">
    <div class="stepper">
        <div class="dots">
            <div class="dot"></div>
            <div class="dot"></div>
            <div class="dot active"></div>
        </div>
        <span>Paso 3 de 3</span>
    </div>

    <div class="success-icon">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
    </div>

    <h1>¡Registro Completado!</h1>
    <p class="subtitle">Su turno ha sido generado con éxito. Por favor, tome asiento.</p>

    <div class="cards-container">
        <div class="ticket-card">
            <div class="ticket-left">
                <span>TU NÚMERO</span>
                <div class="ticket-number"><?= htmlspecialchars($ticketInfo['codigo_ticket']) ?></div>
            </div>
            <div class="ticket-right">
                <div class="info-group">
                    <label>ESPECIALIDAD</label>
                    <p><?= htmlspecialchars($ticketInfo['nombre_especialidad']) ?></p>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: flex-end;">
                    <div class="info-group">
                        <label>FECHA Y HORA</label>
                        <div style="font-size: 0.95rem; font-weight: bold; margin-top: 5px;">
                            <?= $fechaFormateada ?> — <?= $horaFormateada ?>
                        </div>
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 5px;">PRIORIDAD</label>
                        <span class="priority-badge"><?= $prioridadTexto ?></span>
                    </div>
                </div>
                <div class="info-group" style="margin-top: 15px;">
                    <label>CONSULTORIO</label>
                    <p style="font-size: 1rem;">N° <?= htmlspecialchars($ticketInfo['numero_consultorio']) ?> - Piso <?= htmlspecialchars($ticketInfo['piso'] ?? '1') ?></p>
                </div>
            </div>
        </div>

        <div class="waiting-card">
            <div class="waiting-header">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                SALA DE ESPERA
            </div>
            <h2 class="room-title">Espere en la sala de espera</h2>
            <p class="room-desc">Hasta que sea atendido</p>

            <div class="waiting-stat">
                <div class="stat-icon">
                    <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><path d="M12 6v6l4 2"></path></svg>
                </div>
                <div class="stat-text">
                    <span>Espera estimada</span>
                    <strong><?= htmlspecialchars($ticketInfo['tiempo_estimado_espera']) ?> minutos</strong>
                </div>
            </div>

            <div class="waiting-stat">
                <div class="stat-icon">
                    <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 00-3-3.87m-4-12a4 4 0 010 7.75"></path></svg>
                </div>
                <div class="stat-text">
                    <span>Pacientes antes</span>
                    <strong><?= $personasAntes ?> <?= $personasAntes === 1 ? 'persona' : 'personas' ?></strong>
                </div>
            </div>
        </div>
    </div>

    <a href="?route=dashboard_paciente" class="btn-finalize">
        Finalizar
        <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 5l7 7-7 7M3 12h18"></path></svg>
    </a>

    <div style="margin-top:15px;">
        <button onclick="window.print()" class="btn-finalize" style="padding:12px 30px;font-size:1rem;background:#4a5568;">
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2M6 14h12v8H6v-8z"/></svg>
            Imprimir Ticket
        </button>
    </div>
    <p class="print-note">Guarde o imprima su ticket para presentarlo en el consultorio.</p>
</div>
<script>
window.addEventListener('load', function() {
    setTimeout(function() { window.print(); }, 500);

    if ('Notification' in window && Notification.permission === 'default') {
        Notification.requestPermission();
    }

    var idTicket = <?= $ticketInfo['id_ticket'] ?>;
    var ticketCode = '<?= $ticketInfo['codigo_ticket'] ?>';
    var lastEstado = '<?= $ticketInfo['estado'] ?>';

    setInterval(function() {
        fetch('?route=api&action=check_ticket&id_ticket=' + idTicket)
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data.success) return;
                if (data.estado !== lastEstado) {
                    lastEstado = data.estado;
                    if (data.estado === 'Llamado' && 'Notification' in window && Notification.permission === 'granted') {
                        new Notification('¡Hora de atención!', {
                            body: 'Su ticket ' + ticketCode + ' ha sido llamado. Diríjase al consultorio N°' + data.consultorio + '.',
                            icon: 'https://ui-avatars.com/api/?name=Q-Line&background=066931&color=white'
                        });
                    }
                }
            })
            .catch(function() {});
    }, 15000);
});
</script>
