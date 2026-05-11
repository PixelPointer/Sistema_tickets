<div class="monitor-container">
    <div class="monitor-header">
        <h1>
            <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor" style="vertical-align:middle;margin-right:8px;">
                <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-2 10h-4v4h-2v-4H7v-2h4V7h2v4h4v2z"/>
            </svg>
            Q-LINE · SALA DE ESPERA
        </h1>
        <div class="timestamp" id="timestamp">--:--:--</div>
    </div>

    <div class="monitor-main">
        <!-- Left: Called tickets -->
        <div class="called-panel">
            <div class="panel-title">
                <span class="pulse-dot" style="display:inline-block;width:8px;height:8px;background:#ef4444;border-radius:50%;margin-right:8px;"></span>
                ATENDIENDO AHORA
            </div>
            <div class="called-grid" id="calledGrid">
                <div class="empty-state">
                    <div class="big-icon">🟢</div>
                    <p>No hay tickets en atención</p>
                </div>
            </div>
        </div>

        <!-- Right: Recent + Queue -->
        <div class="right-panel">
            <div class="recent-panel">
                <div class="panel-title">ÚLTIMOS ATENDIDOS</div>
                <div class="recent-list" id="recentList">
                    <div class="empty-state"><p>Sin movimientos recientes</p></div>
                </div>
            </div>

            <div class="queue-panel">
                <div class="panel-title">PACIENTES EN ESPERA</div>
                <div class="queue-stats" id="queueStats"></div>
                <div class="total-waiting">
                    <div class="big-number" id="totalWaiting">0</div>
                    <div class="label">Total esperando</div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function formatTime(iso) {
    if (!iso) return '--:--';
    var d = new Date(iso);
    if (isNaN(d.getTime())) return iso;
    return d.getHours().toString().padStart(2, '0') + ':' +
           d.getMinutes().toString().padStart(2, '0');
}

function getPriorityClass(p) {
    if (!p) return '';
    return 'priority-' + p.toLowerCase();
}

function priorityDot(p) {
    if (!p) return '';
    return '<span class="priority-dot ' + p.toLowerCase() + '"></span>';
}

function renderMonitor(data) {
    // Update timestamp
    document.getElementById('timestamp').textContent = data.timestamp || '--:--:--';

    // Called tickets
    var calledGrid = document.getElementById('calledGrid');
    if (data.llamados && data.llamados.length > 0) {
        calledGrid.innerHTML = data.llamados.map(function(t) {
            return '<div class="called-card ' + getPriorityClass(t.prioridad) + '">' +
                '<div class="time-called">' + (t.minutos_llamado || 0) + ' min</div>' +
                '<div class="ticket-code">' + escHtml(t.codigo_ticket) + '</div>' +
                '<div class="consultorio-info">Consultorio <strong>N° ' + escHtml(t.numero_consultorio) + '</strong>' +
                (t.piso ? ' · Piso ' + escHtml(t.piso) : '') + '</div>' +
                '<div>' +
                    '<span class="specialty-tag">' + priorityDot(t.prioridad) + escHtml(t.nombre_especialidad) + '</span>' +
                '</div>' +
            '</div>';
        }).join('');
    } else {
        calledGrid.innerHTML = '<div class="empty-state"><div class="big-icon">🟢</div><p>No hay tickets en atención</p></div>';
    }

    // Recent attended
    var recentList = document.getElementById('recentList');
    if (data.atendidos && data.atendidos.length > 0) {
        recentList.innerHTML = data.atendidos.map(function(t) {
            return '<div class="recent-item">' +
                '<span class="code">' + escHtml(t.codigo_ticket) + '</span>' +
                '<span class="cons">Cons. ' + escHtml(t.numero_consultorio) + '</span>' +
                '<span class="time">' + formatTime(t.fecha_hora_atencion) + '</span>' +
            '</div>';
        }).join('');
    } else {
        recentList.innerHTML = '<div class="empty-state"><p>Sin movimientos recientes</p></div>';
    }

    // Queue stats by specialty
    var queueStats = document.getElementById('queueStats');
    if (data.espera_por_especialidad && data.espera_por_especialidad.length > 0) {
        queueStats.innerHTML = data.espera_por_especialidad.map(function(s) {
            return '<div class="queue-stat">' +
                '<div class="count">' + s.total + '</div>' +
                '<div class="label">' + escHtml(s.nombre_especialidad) + '</div>' +
            '</div>';
        }).join('');
    } else {
        queueStats.innerHTML = '<div class="queue-stat"><div class="count">0</div><div class="label">Sin espera</div></div>';
    }

    document.getElementById('totalWaiting').textContent = data.total_espera || 0;
}

function escHtml(s) {
    if (!s) return '';
    var d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
}

function fetchMonitor() {
    fetch('?route=api_monitor&_=' + Date.now())
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) renderMonitor(data);
        })
        .catch(function() {});
}

fetchMonitor();
setInterval(fetchMonitor, 5000);
</script>
