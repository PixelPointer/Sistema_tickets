<?php
require_once dirname(__DIR__, 2) . '/Logic/Controllers/AuthController.php';
require_once dirname(__DIR__, 2) . '/Logic/Controllers/TicketController.php';
require_once dirname(__DIR__, 2) . '/Logic/Helpers/UIHelper.php';

$auth = new AuthController();
$user = $auth->getUser();
if (!$user || $user['rol'] != 3) {
    header('Location: ?route=login');
    exit;
}

$ticketCtrl = new TicketController();
$especialidades = $ticketCtrl->listEspecialidades();
?>
<style>
    :root {
        --primary-green: #066931;
        --text-dark: #1a202c;
        --text-muted: #718096;
        --bg-body: #f8fafc;
        --border-color: #e2e8f0;
    }
    body { margin: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: var(--bg-body); color: var(--text-dark); display: flex; flex-direction: column; min-height: 100vh; }
    .container { max-width: 1100px; margin: 0 auto; padding: 40px 20px; flex: 1; }
    header { margin-bottom: 40px; }
    header h1 { font-size: 2rem; margin: 0; text-transform: uppercase; letter-spacing: 1px; }
    header p { color: var(--text-muted); margin-top: 8px; }
    .specialties-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px; }
    .card { background: white; border: 2px solid transparent; border-radius: 16px; padding: 24px; position: relative; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); transition: all 0.2s ease; cursor: pointer; }
    .card:hover { transform: translateY(-2px); box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); }
    .card.selected { border-color: var(--primary-green); }
    .selected-badge { position: absolute; top: 15px; right: 15px; background: var(--primary-green); color: white; width: 24px; height: 24px; border-radius: 50%; display: none; align-items: center; justify-content: center; font-size: 14px; }
    .card.selected .selected-badge { display: flex; }
    .icon-box { width: 45px; height: 45px; border-radius: 8px; display: flex; align-items: center; justify-content: center; margin-bottom: 16px; }
    .icon-dental { background-color: #dcfce7; color: #166534; }
    .icon-general { background-color: #eff6ff; color: #1e40af; }
    .icon-pediatry { background-color: #fef3c7; color: #92400e; }
    .icon-gineco { background-color: #fce7f3; color: #9d174d; }
    .icon-cardio { background-color: #fee2e2; color: #991b1b; }
    .card h3 { margin: 0 0 8px 0; font-size: 1.25rem; }
    .card p { margin: 0; color: var(--text-muted); font-size: 0.95rem; line-height: 1.4; }
    .footer-nav { background: white; padding: 30px 0; border-top: 1px solid var(--border-color); margin-top: 40px; }
    .footer-container { max-width: 1100px; margin: 0 auto; padding: 0 20px; display: flex; justify-content: space-between; align-items: center; }
    .btn { padding: 12px 30px; border-radius: 8px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 10px; font-size: 0.9rem; transition: 0.3s; border: none; }
    .btn-outline { background: transparent; border: 1px solid var(--border-color); color: var(--text-dark); }
    .btn-outline:hover { background: #f1f5f9; }
    .btn-primary { background: var(--primary-green); color: white; border: none; text-transform: uppercase; }
    .btn-primary:hover { background: #045226; }
    .stepper { text-align: center; }
    .dots { display: flex; gap: 8px; justify-content: center; margin-bottom: 5px; }
    .dot { width: 8px; height: 8px; background: #cbd5e0; border-radius: 50%; }
    .dot.active { background: var(--primary-green); }
    .stepper span { font-size: 0.8rem; color: var(--text-muted); }
    @media (max-width: 600px) { .specialties-grid { grid-template-columns: 1fr; } }
</style>

<div class="container">
    <header>
        <h1>Seleccione una especialidad</h1>
        <p>Elija el departamento médico para su consulta.</p>
    </header>

    <form method="POST" action="?route=dashboard_paciente&action=confirmar_ticket" id="esp-form">
        <div class="specialties-grid">
            <?php foreach ($especialidades as $esp): ?>
                <?php $icon = UIHelper::getIconForEspecialidad($esp['nombre_especialidad']); ?>
                <div class="card" data-id="<?= $esp['id_especialidad'] ?>" onclick="seleccionar(this)">
                    <div class="selected-badge">✓</div>
                    <div class="icon-box <?= $icon['class'] ?>">
                        <?= $icon['svg'] ?>
                    </div>
                    <h3><?= htmlspecialchars($esp['nombre_especialidad']) ?></h3>
                    <p>Atención especializada en <?= htmlspecialchars(strtolower($esp['nombre_especialidad'])) ?>.</p>
                </div>
            <?php endforeach; ?>
        </div>

        <input type="hidden" name="id_especialidad" id="id_especialidad" value="">

        <div id="triage-section" style="display:none;margin-top:30px;background:white;border-radius:16px;padding:30px;box-shadow:0 4px 6px -1px rgba(0,0,0,0.05);border:1px solid var(--border-color);">
            <h2 style="margin:0 0 8px;font-size:1.2rem;color:#2d3748;">Información adicional</h2>
            <p style="color:var(--text-muted);margin:0 0 20px;font-size:0.9rem;">Esto ayuda al médico a priorizar su atención.</p>

            <div style="margin-bottom:20px;">
                <label style="display:block;font-weight:600;margin-bottom:8px;color:#2d3748;">¿Cómo describiría su situación?</label>
                <div style="display:flex;gap:15px;flex-wrap:wrap;">
                    <label style="flex:1;min-width:150px;padding:15px 20px;border:2px solid var(--border-color);border-radius:12px;cursor:pointer;text-align:center;transition:all 0.2s;" class="triage-option" data-value="Verde">
                        <div style="font-size:1.5rem;margin-bottom:5px;">🟢</div>
                        <div style="font-weight:600;">Normal</div>
                        <div style="font-size:0.8rem;color:var(--text-muted);">Control o revisión</div>
                        <input type="radio" name="prioridad" value="Verde" style="display:none;">
                    </label>
                    <label style="flex:1;min-width:150px;padding:15px 20px;border:2px solid var(--border-color);border-radius:12px;cursor:pointer;text-align:center;transition:all 0.2s;" class="triage-option" data-value="Amarillo">
                        <div style="font-size:1.5rem;margin-bottom:5px;">🟡</div>
                        <div style="font-weight:600;">Urgente</div>
                        <div style="font-size:0.8rem;color:var(--text-muted);">Malestar o dolor moderado</div>
                        <input type="radio" name="prioridad" value="Amarillo" style="display:none;">
                    </label>

                </div>
            </div>

            <div style="margin-bottom:10px;">
                <label for="sintomas" style="display:block;font-weight:600;margin-bottom:8px;color:#2d3748;">Describa sus síntomas (opcional)</label>
                <textarea name="sintomas" id="sintomas" rows="3" style="width:100%;padding:12px;border:1px solid var(--border-color);border-radius:10px;font-size:0.95rem;box-sizing:border-box;resize:vertical;" placeholder="Ej: Dolor de cabeza desde hace 3 días, fiebre de 38°C..."></textarea>
            </div>
        </div>

        <nav class="footer-nav">
            <div class="footer-container">
                <a href="?route=dashboard_paciente" class="btn btn-outline">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                    ATRÁS
                </a>
                <div class="stepper">
                    <div class="dots">
                        <div class="dot"></div>
                        <div class="dot active"></div>
                        <div class="dot"></div>
                    </div>
                    <span>Paso 2 de 3</span>
                </div>
                <button type="submit" class="btn btn-primary" id="btn-continuar" disabled>
                    CONTINUAR
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                </button>
            </div>
        </nav>
    </form>
</div>

<script>
function seleccionar(el) {
    document.querySelectorAll('.card').forEach(c => c.classList.remove('selected'));
    el.classList.add('selected');
    document.getElementById('id_especialidad').value = el.dataset.id;
    document.getElementById('triage-section').style.display = 'block';
    document.getElementById('btn-continuar').disabled = false;
}

document.querySelectorAll('.triage-option').forEach(function(opt) {
    opt.addEventListener('click', function() {
        document.querySelectorAll('.triage-option').forEach(function(o) {
            o.style.borderColor = '#e2e8f0';
            o.style.background = 'white';
        });
        this.style.borderColor = '#066931';
        this.style.background = '#f0fff4';
        this.querySelector('input[type=radio]').checked = true;
    });
});
</script>
