<?php $pageTitle = "Iniciar Sesión - Q-Line"; ?>

<header>
    <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
        <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-2 10h-4v4h-2v-4H7v-2h4V7h2v4h4v2z"/>
    </svg>
    Q-Line
</header>

<div class="login-wrapper">
    <div class="login-header">
        <h1>Q-Line</h1>
        <p>Portal Seguro para profesionales y pacientes</p>
    </div>

    <div class="login-card">
        <form action="?route=login" method="POST">
            <div class="form-group">
                <label>Correo Electrónico</label>
                <div class="input-container">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                        <polyline points="22,6 12,13 2,6"></polyline>
                    </svg>
                    <input type="email" name="email" placeholder="name@medical.com" required>
                </div>
            </div>

            <div class="form-group">
                <div class="label-row">
                    <label>Contraseña</label>
                    <a href="#" class="forgot-link">Olvidaste tu Contraseña?</a>
                </div>
                <div class="input-container">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                    </svg>
                    <input type="password" name="password" placeholder="••••••••" required>
                </div>
            </div>

            <label class="remember-me">
                <input type="checkbox" name="remember" style="width: auto; padding: 0;"> Recordar mi decisión
            </label>

            <button type="submit" class="btn-login">
                Iniciar Sesión
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path>
                    <polyline points="10 17 15 12 10 7"></polyline>
                    <line x1="15" y1="12" x2="3" y2="12"></line>
                </svg>
            </button>
        </form>
    </div>

    <div class="signup-footer">
        No tienes una cuenta? <a href="?route=register">Registrate Ahora</a>
    </div>
</div>

<footer>
    © 2026 Q-Line
</footer>