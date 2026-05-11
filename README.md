# Q-Line - Sistema de Gestión de Turnos y Colas

Sistema web para gestión de turnos médicos con arquitectura de 3 capas, desarrollado en PHP nativo + MySQL.

## Arquitectura

```
Sistema_tickets/
├── config/              # Configuración (base de datos)
├── public/              # Front controller y assets
│   ├── index.php        # Punto de entrada único (Front Controller)
│   ├── css/             # Estilos
│   └── js/              # JavaScript
├── src/
│   ├── Data/            # Esquema SQL
│   ├── Logic/
│   │   ├── Controllers/ # Lógica de negocio
│   │   ├── Helpers/     # Utilidades
│   │   └── Models/      # Acceso a datos (DAO)
│   └── Presentation/
│       └── views/       # Plantillas PHP
└── assets/
```

## Requisitos Funcionales - Solución Implementada

### 1. Registro de pacientes, médicos y administrativos

**Pacientes** se registran solos desde `?route=register`. El `AuthController::register()` crea la persona, el paciente y el usuario en una transacción.

**Médicos y administrativos** se crean desde el panel de admin (`dashboard_admin.php`) vinculando un usuario existente a la tabla `personal`.

Archivos: `AuthController.php`, `UserController.php`, `PersonalController.php`

### 2. Inicio de sesión por tipo de usuario

`AuthController::login()` verifica credenciales y redirige según el rol (1=admin, 2=médico, 3=paciente):

```php
$dashboardRoute = match($rol) {
    1 => 'dashboard_admin',
    2 => 'dashboard_medico',
    default => 'dashboard_paciente'
};
```

Archivos: `AuthController.php`, `index.php:106-109`

### 3. Roles y permisos según usuario

Tres roles en tabla `rol`: Administrador, Médico, Paciente. Cada dashboard verifica el rol antes de mostrar contenido:

```php
if (!$user || $user['rol'] != 2) {
    header('Location: ?route=login');
    exit;
}
```

Archivo: `dashboard_medico.php:11-13`

### 4. Solicitar fichas digitales

El paciente selecciona especialidad y prioridad en `solicitar_turno.php`, luego `TicketController::solicitarTicket()` crea el ticket:

```php
$ticket = new Ticket();
$ticket->id_paciente = $paciente['id_paciente'];
$ticket->id_consultorio = $id_consultorio;
$ticket->codigo_ticket = $codigo;
$ticket->prioridad = $prioridad;
$ticket->estado = 'Espera';
$ticket->tiempo_estimado_espera = $tiempo_espera;
$ticket->motivo_consulta = $sintomas;
$ticket->crear();
```

Archivos: `TicketController.php`, `Ticket.php`, `solicitar_turno.php`

### 5. Número de turno único

Código autogenerado por especialidad con reinicio diario:

```php
public static function generarCodigo($id_especialidad) {
    // Abreviatura de 3 letras desde el nombre de la especialidad
    // Ej: MED para Medicina General, TRA para Traumatología, etc.
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM ticket t 
                            INNER JOIN consultorio c ON t.id_consultorio = c.id_consultorio
                            WHERE DATE(t.fecha_creacion) = CURDATE() AND c.id_especialidad = ?");
    $stmt->execute([$id_especialidad]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $seq = str_pad($row['total'] + 1, 3, '0', STR_PAD_LEFT);
    return $abbr . '-' . $seq;
}
```

Formato: `MED-001`, `TRA-001` (cada especialidad tiene su propia secuencia, reinicio diario).

Archivo: `Ticket.php:43-66`

### 6. Posición del paciente en la cola en tiempo real

Polling cada 15 segundos desde `ticket_confirmado.php`. El endpoint `?route=api&action=check_ticket` devuelve `personas_antes`:

```javascript
setInterval(function() {
    fetch('?route=api&action=check_ticket&id_ticket=' + idTicket)
        .then(function(r) { return r.json(); })
        .then(function(data) {
            // Actualiza posición: data.personas_antes
            // Actualiza estado: data.estado
            if (data.estado === 'Llamado') {
                new Notification('¡Hora de atención!', { body: '...' });
            }
        });
}, 15000);
```

El monitor de sala de espera (`?route=monitor`) actualiza cada 5 segundos vía `api_monitor`.

Archivos: `ticket_confirmado.php`, `monitor.php`, `index.php:17-48`

### 7. Cancelar fichas

El paciente puede cancelar tickets en estado `Espera` desde su dashboard:

```php
public static function cancelar($id_ticket, $id_paciente) {
    $stmt = $conn->prepare("SELECT * FROM ticket WHERE id_ticket = ? AND id_paciente = ? AND estado = 'Espera'");
    $stmt->execute([$id_ticket, $id_paciente]);
    if (!$stmt->fetch()) return false;
    $stmt = $conn->prepare("UPDATE ticket SET estado = 'Ausente' WHERE id_ticket = ?");
    return $stmt->execute([$id_ticket]);
}
```

Archivos: `Ticket.php:148-160`, `TicketController.php`, `dashboard_paciente.php`

### 8. Limitar cantidad de fichas por día o por médico

**Por paciente:** Un ticket activo por día (`Ticket::tieneTicketHoy()`):

```php
if (Ticket::tieneTicketHoy($paciente['id_paciente'])) {
    return ['success' => false, 'message' => 'Ya tiene un turno activo hoy.'];
}
```

**Por médico:** Columna `limite_diario` en tabla `personal`. Configurable desde el perfil del médico. Verificado al llamar siguiente paciente:

```php
public function verificarLimiteDiario() {
    $personal = Personal::buscarPorId($this->id_personal);
    if (!$personal || intval($personal['limite_diario']) <= 0) return true;
    $atendidos = Personal::contarAtendidosHoy($this->id_personal);
    return $atendidos < intval($personal['limite_diario']);
}
```

Archivos: `Ticket.php`, `MedicoController.php`, `Personal.php`, `dashboard_medico.php`

### 9. Lista de pacientes en espera al médico

`Ticket::listarEnEspera()` devuelve tickets ordenados por prioridad y antigüedad:

```sql
ORDER BY FIELD(t.estado, 'Llamado', 'Espera'), t.prioridad DESC, t.fecha_creacion ASC
```

Los tickets `Llamado` aparecen primero, luego los `Espera` ordenados por prioridad (Rojo > Amarillo > Verde) y FIFO.

Archivos: `Ticket.php:198-217`, `dashboard_medico.php`

### 10. Médico llama al siguiente paciente

```php
public static function llamarSiguiente($id_consultorios) {
    $stmt = $conn->prepare("SELECT id_ticket FROM ticket 
                            WHERE id_consultorio IN ($placeholders) AND estado = 'Espera'
                            ORDER BY prioridad DESC, fecha_creacion ASC LIMIT 1");
    $stmt->execute($id_consultorios);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$ticket) return null;

    $stmt = $conn->prepare("UPDATE ticket SET estado = 'Llamado', hora_inicio_estimada = CURTIME() WHERE id_ticket = ?");
    $stmt->execute([$ticket['id_ticket']]);
    return $ticket['id_ticket'];
}
```

Archivos: `Ticket.php:219-236`, `MedicoController.php`

### 11. Actualización automática del estado del turno

Flujo de estados: `Espera` → `Llamado` → `Atendido` / `Ausente`

Cada acción (crear ticket, llamar paciente, atender, cancelar) cambia el estado vía `Ticket::cambiarEstado()`.

### 12. Historial de atenciones

`Atencion::listarHistorialCompleto()` devuelve todas las atenciones de un paciente con datos del médico, diagnóstico, tratamiento y próxima cita.

```php
$query = "SELECT a.*, t.codigo_ticket, c.numero_consultorio, c.piso,
                 e.nombre_especialidad,
                 per.nombre as medico_nombre, per.apellido as medico_apellido,
                 p2.nombre as paciente_nombre, p2.apellido as paciente_apellido
          FROM atencion a
          INNER JOIN ticket t ON a.id_ticket = t.id_ticket
          INNER JOIN personal ps ON a.id_personal = ps.id_personal
          INNER JOIN persona per ON ps.id_persona = per.id_persona
          ...";
```

Archivos: `Atencion.php`, `dashboard_paciente.php` (Mi Historial), `dashboard_medico.php` (Historial Pacientes)

### 13. Médico consulta historial del paciente

Sección "Historial Pacientes" en `dashboard_medico.php` con buscador por nombre/documento. Muestra el historial completo con diagnósticos, tratamientos y próximas citas.

### 14. Registrar motivo de consulta

Columna `motivo_consulta` en tabla `ticket`. Capturado del textarea en `solicitar_turno.php` y guardado al crear el ticket. Visible para el médico en el detalle del ticket.

### 15. Notificaciones al paciente

- **Notificación browser** cuando el ticket es llamado (estado → `Llamado`), usando la API `Notification` del navegador
- **Polling en tiempo real** cada 15 segundos desde `ticket_confirmado.php`
- **Monitor público** en `?route=monitor` para la sala de espera
- El sistema solicita permiso de notificaciones al cargar la página de confirmación

### 16. Tiempo estimado de espera

Cálculo simple: cantidad de personas antes × 10 minutos:

```php
public static function estimarTiempoEspera($id_consultorio) {
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM ticket 
                            WHERE id_consultorio = ? AND estado = 'Espera'");
    $stmt->execute([$id_consultorio]);
    $cantidad = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    return $cantidad * 10; // 10 minutos por persona
}
```

Además, se usa el modelo M/M/1 para un análisis más preciso (ver sección siguiente).

### 17. Visualizar pacientes atendidos

Stats en dashboard del médico y admin muestran conteo de atendidos. El monitor de sala de espera muestra los últimos 30 minutos de atenciones.

### 18. Reportes diarios de atención

Sección "Reporte Diario" en `dashboard_medico.php`. Muestra todas las atenciones del día con filtro por fecha, resumen de diagnósticos y próximas citas. Incluye botón para imprimir/exportar PDF.

---

## Modelo Matemático M/M/1 (Teoría de Colas)

El sistema implementa el modelo M/M/1 para análisis y optimización de la cola de pacientes.

### Fórmulas

| Variable | Descripción | Fórmula |
|----------|-------------|---------|
| λ (lambda) | Tasa de llegada (pacientes/hora) | Total tickets últimos 7 días / horas |
| μ (mu) | Tasa de servicio (pacientes/hora) | 60 / duración promedio de atención |
| ρ (rho) | Factor de utilización del sistema | λ / μ |
| Lq | Número promedio en cola | λ² / (μ(μ - λ)) |
| Wq | Tiempo promedio en cola (horas) | Lq / λ |
| W | Tiempo total en sistema (horas) | 1 / (μ - λ) |

### Implementación

Ubicado en `Ticket.php:298-346`, método `calcularMM1()`:

```php
public static function calcularMM1($id_especialidad) {
    // λ: tasa de llegada (historial 7 días)
    $lambda = round($llegadas['total'] / max($llegadas['horas'], 1), 2);

    // μ: tasa de servicio (duración promedio de atenciones)
    $minutos = floatval($servicio['duracion_promedio']);
    $mu = round(60 / max($minutos, 1), 2);

    // ρ: factor de utilización
    $rho = $mu > 0 ? round($lambda / $mu, 4) : 0;

    if ($rho < 1 && $mu > 0) {
        $Lq = round(($lambda * $lambda) / ($mu * ($mu - $lambda)), 2);
        $Wq = $mu > $lambda ? round($Lq / $lambda, 2) : 0;
        $W = $mu > $lambda ? round(1 / ($mu - $lambda), 2) : 0;
    }

    return [
        'lambda' => $lambda, 'mu' => $mu, 'rho' => $rho,
        'Lq' => $Lq, 'Wq' => $Wq, 'W' => $W,
        'Wq_min' => round($Wq * 60), 'W_min' => round($W * 60)
    ];
}
```

### Visualización en el Dashboard del Médico

Los resultados se muestran en `dashboard_medico.php`:

```
┌──────────────────────────────────────────────────┐
│        Modelo M/M/1 - Análisis de la cola         │
├──────────┬──────────┬──────────┬──────────┬───────┤
│ λ=4.5    │ μ=6.0    │ ρ=0.75   │ Lq=2.25  │       │
│ llegadas │ servicios│ factor   │ pacientes│       │
│ /hora    │ /hora    │ de uso   │ en cola  │       │
├──────────┴──────────┴──────────┴──────────┴───────┤
│ Wq = 30 min (tiempo espera en cola)               │
│ W  = 40 min (tiempo total en sistema)             │
│                                                    │
│ Si ρ ≥ 1: ⚠ Sistema saturado, cola crecerá        │
│           indefinidamente                          │
└────────────────────────────────────────────────────┘
```

### Interpretación

- **ρ < 1**: Sistema estable. Ej: ρ = 0.75 significa que el médico está ocupado el 75% del tiempo
- **ρ = 1**: Sistema al límite de capacidad
- **ρ > 1**: Sistema inestable. La cola crece indefinidamente. Se necesita más personal o reducir tiempos de atención
- **Wq**: Tiempo que un paciente espera antes de ser atendido
- **W**: Tiempo total desde que el paciente solicita el turno hasta que termina la atención

---

## Monitor de Sala de Espera

Acceso público en `?route=monitor` (sin autenticación). Muestra en tiempo real:

- Tickets siendo llamados (con consultorio y prioridad)
- Últimos atendidos (30 min)
- Cantidad de pacientes en espera por especialidad

Diseño oscuro apto para TV, actualización cada 5 segundos vía polling.

---

## Instalación

1. Importar `src/Data/sistema_tickets.sql` en MySQL
2. Configurar credenciales en `config/database.php`
3. Apuntar el virtual host a `public/`
4. Los roles se crean automáticamente: 1=Admin, 2=Médico, 3=Paciente
