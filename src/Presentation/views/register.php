<?php $pageTitle = "Registro - Q-Line"; ?>

<header>
    <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
        <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-2 10h-4v4h-2v-4H7v-2h4V7h2v4h4v2z"/>
    </svg>
    Q-Line
</header>

<div class="register-container">
    <div class="card">
        <h2>Registro</h2>
        <p class="subtitle">Por favor, ingrese sus datos personales para crear su cuenta clínica segura.</p>

        <form action="?route=register" method="POST">
            <div class="grid">
                <div class="form-group">
                    <label>Nombres</label>
                    <input type="text" name="nombre" placeholder="Nombres" required>
                </div>
                <div class="form-group">
                    <label>Apellidos</label>
                    <input type="text" name="apellido" placeholder="Apellidos" required>
                </div>
                <div class="form-group">
                    <label>Carnet de Identidad</label>
                    <input type="text" name="documento" placeholder="Numero de CI" required>
                </div>
                <div class="form-group">
                    <label>Fecha de Nacimiento</label>
                    <input type="date" name="fecha_nacimiento" required>
                </div>
                <div class="form-group">
                    <label>Teléfono</label>
                    <input type="tel" name="telefono" placeholder="123-456-7890">
                </div>
                <div class="form-group">
                    <label>Contraseña</label>
                    <input type="password" name="password" placeholder="••••••••" required>
                </div>
                <div class="form-group">
                    <label>Confirmar Contraseña</label>
                    <input type="password" name="confirm_password" placeholder="••••••••" required>
                </div>
                <div class="form-group full-width">
                    <label>Correo Electrónico</label>
                    <input type="email" name="email" placeholder="example@clinical.com" required>
                </div>
                <div class="form-group full-width">
                    <label>Dirección</label>
                    <textarea name="direccion" placeholder="Calle, Zona, Numero"></textarea>
                </div>
            </div>

            <div class="checkbox-group">
                <input type="checkbox" id="terms" required>
                <label for="terms">Acepto la <a href="#" style="color: #2b6cb0;">Política de Privacidad</a> y autorizo el tratamiento de mis datos médicos.</label>
            </div>

            <button type="submit" class="btn-submit">
                Registrarse 
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                    <polyline points="12 5 19 12 12 19"></polyline>
                </svg>
            </button>

            <div class="login-link">
                Ya tiene una cuenta? <a href="?route=login">Iniciar Sesion</a>
            </div>
        </form>
    </div>
</div>

<footer>
    © 2026 Q-Line
</footer>