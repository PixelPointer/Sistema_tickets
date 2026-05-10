<?php
$pageTitle = "Recuperar Contraseña - Q-Line";
$email = $_GET['email'] ?? '';
$sent = isset($_GET['sent']);
$error = isset($_GET['error']);
?>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #f0f4f8 0%, #e2e8f0 100%); min-height: 100vh; display: flex; flex-direction: column; align-items: center; justify-content: center; }
    header { position: absolute; top: 0; left: 0; padding: 20px 30px; display: flex; align-items: center; gap: 10px; color: #066931; font-weight: bold; font-size: 1.2rem; }
    header svg { width: 28px; height: 28px; }
    .card { background: white; padding: 40px; border-radius: 16px; box-shadow: 0 10px 40px rgba(0,0,0,0.08); width: 420px; max-width: 90vw; text-align: center; }
    .card h1 { font-size: 1.5rem; color: #2d3748; margin-bottom: 8px; }
    .card p { color: #718096; margin-bottom: 25px; font-size: 0.9rem; }
    .form-group { margin-bottom: 20px; text-align: left; }
    .form-group label { display: block; font-size: 0.85rem; font-weight: 600; color: #2d3748; margin-bottom: 5px; }
    .form-group input { width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 1rem; box-sizing: border-box; }
    .form-group input:focus { outline: none; border-color: #066931; box-shadow: 0 0 0 2px rgba(6,105,49,0.15); }
    .btn { width: 100%; padding: 14px; background: #066931; color: white; border: none; border-radius: 10px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: background 0.2s; }
    .btn:hover { background: #045226; }
    .alert { padding: 14px; border-radius: 8px; margin-bottom: 20px; font-size: 0.9rem; }
    .alert-success { background: #d1fae5; color: #059669; border: 1px solid #a7f3d0; }
    .alert-error { background: #fee2e2; color: #dc2626; border: 1px solid #fecaca; }
    .back-link { display: block; margin-top: 20px; color: #718096; text-decoration: none; font-size: 0.9rem; }
    .back-link:hover { color: #066931; }
    footer { position: absolute; bottom: 0; padding: 20px; color: #a0aec0; font-size: 0.8rem; }
</style>

<header>
    <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-2 10h-4v4h-2v-4H7v-2h4V7h2v4h4v2z"/></svg>
    Q-Line
</header>

<div class="card">
    <h1>Recuperar Contraseña</h1>
    <p>Ingrese su correo electrónico para recibir instrucciones de recuperación.</p>

    <?php if ($sent): ?>
        <div class="alert alert-success">Se han enviado las instrucciones de recuperación a <strong><?= htmlspecialchars($email) ?></strong>. Revise su bandeja de entrada.</div>
        <a href="?route=login" class="back-link">Volver al inicio de sesión</a>
    <?php elseif ($error): ?>
        <div class="alert alert-error">No encontramos una cuenta con el correo <strong><?= htmlspecialchars($email) ?></strong>. Verifique e intente nuevamente.</div>
    <?php endif; ?>

    <?php if (!$sent): ?>
    <form method="POST" action="?route=forgot_password">
        <div class="form-group">
            <label>Correo Electrónico</label>
            <input type="email" name="email" placeholder="name@medical.com" required value="<?= htmlspecialchars($email) ?>">
        </div>
        <button type="submit" class="btn">Enviar Instrucciones</button>
    </form>
    <a href="?route=login" class="back-link">← Volver al inicio de sesión</a>
    <?php endif; ?>
</div>

<footer>© 2026 Q-Line</footer>
