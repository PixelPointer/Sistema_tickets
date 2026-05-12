<?php
require_once dirname(__DIR__) . '/Models/Ticket.php';
require_once dirname(__DIR__) . '/Models/Paciente.php';
require_once dirname(__DIR__) . '/Models/Especialidad.php';
require_once dirname(__DIR__) . '/Models/Consultorio.php';
require_once dirname(__DIR__) . '/Models/Horario.php';

// RF 4-5-6-7-8: Controlador de Tickets - gestión de turnos del paciente
class TicketController {

    public function listEspecialidades() {
        return Especialidad::listarTodos();
    }

    public function listConsultorios($id_especialidad) {
        return Consultorio::listarPorEspecialidad($id_especialidad);
    }

    // Listar tickets del paciente: separa activos hoy del historial pasado
    public function listarTicketsPaciente($id_usuario) {
        $paciente = Paciente::buscarPorIdUsuario($id_usuario);
        if (!$paciente) {
            return ['success' => false, 'message' => 'Paciente no encontrado', 'data' => []];
        }
        $tickets = Ticket::listarPorPaciente($paciente['id_paciente']);
        $activoHoy = Ticket::contarActivosHoy($paciente['id_paciente']);

        $hoy = [];
        $pasados = [];
        foreach ($tickets as $t) {
            if (date('Y-m-d', strtotime($t['fecha_creacion'])) === date('Y-m-d') && in_array($t['estado'], ['Espera', 'Llamado'])) {
                $hoy[] = $t;
            } else {
                $pasados[] = $t;
            }
        }

        return [
            'success' => true,
            'data' => [
                'tickets_hoy' => $hoy,
                'tickets_pasados' => $pasados,
                'tiene_ticket_hoy' => $activoHoy > 0
            ]
        ];
    }

    // RF 4-5-6-21: Solicitar nuevo ticket de turno
    // Valida: 1 ticket/día, disponibilidad horaria, genera código y estima espera
    public function solicitarTicket($id_usuario, $id_especialidad, $prioridad = 'Verde', $sintomas = '') {
        $paciente = Paciente::buscarPorIdUsuario($id_usuario);
        if (!$paciente) {
            return ['success' => false, 'message' => 'Paciente no encontrado'];
        }

        // RF 8: Límite de 1 ticket activo por día
        if (Ticket::tieneTicketHoy($paciente['id_paciente'])) {
            return ['success' => false, 'message' => 'Ya tiene un turno activo hoy. Solo puede solicitar un turno por día.'];
        }

        $consultorios = Consultorio::listarPorEspecialidad($id_especialidad);
        if (empty($consultorios)) {
            return ['success' => false, 'message' => 'No hay consultorios disponibles para esta especialidad'];
        }

        // RF 21: Verificar disponibilidad horaria
        if (!Horario::verificarDisponibilidad($id_especialidad)) {
            $dias = ['Sunday' => 'Domingo', 'Monday' => 'Lunes', 'Tuesday' => 'Martes',
                     'Wednesday' => 'Miercoles', 'Thursday' => 'Jueves', 'Friday' => 'Viernes', 'Saturday' => 'Sabado'];
            $dia = $dias[date('l')];
            $hora = date('H:i');
            $horarios = Horario::horariosParaEspecialidad($id_especialidad);
            $horariosStr = '';
            if (!empty($horarios)) {
                $horariosStr = ' Horarios registrados: ';
                $seen = [];
                foreach ($horarios as $h) {
                    $key = $h['dia_semana'] . $h['hora_inicio'];
                    if (!isset($seen[$key])) {
                        $seen[$key] = true;
                        $horariosStr .= $h['dia_semana'] . ' ' . substr($h['hora_inicio'], 0, 5) . '-' . substr($h['hora_fin'], 0, 5) . ' | ';
                    }
                }
                $horariosStr = rtrim($horariosStr, ' | ');
            } else {
                $horariosStr = ' No hay horarios configurados para esta especialidad.';
            }
            return ['success' => false, 'message' => "No hay atención disponible en este momento. (Hoy es $dia, son las $hora).$horariosStr"];
        }

        $id_consultorio = $consultorios[0]['id_consultorio'];
        // RF 5: Generar código único por especialidad
        $codigo = Ticket::generarCodigo($id_especialidad);
        $tiempo_espera = Ticket::estimarTiempoEspera($id_consultorio);

        $ticket = new Ticket();
        $ticket->id_paciente = $paciente['id_paciente'];
        $ticket->id_consultorio = $id_consultorio;
        $ticket->codigo_ticket = $codigo;
        $ticket->prioridad = $prioridad;
        $ticket->estado = 'Espera';
        $ticket->tiempo_estimado_espera = $tiempo_espera;
        $ticket->motivo_consulta = $sintomas;

        if ($ticket->crear()) {
            $info = Ticket::obtenerPorId($ticket->id_ticket);
            $personas_antes = Ticket::contarAntes($id_consultorio, $ticket->id_ticket);
            return [
                'success' => true,
                'message' => 'Turno generado exitosamente',
                'data' => [
                    'ticket' => $info,
                    'personas_antes' => $personas_antes
                ]
            ];
        }

        return ['success' => false, 'message' => 'Error al generar el turno'];
    }

    // RF 17: Listar tickets con filtro (admin)
    public function listarTicketsAdmin($filtro_estado = '', $limit = 200, $offset = 0) {
        return Ticket::listarTodos($filtro_estado, $limit, $offset);
    }

    public function contarTicketsAdmin($filtro_estado = '') {
        return Ticket::contarTodos($filtro_estado);
    }

    // Cuántas personas están antes en la cola
    public function contarAntes($id_consultorio, $id_ticket) {
        return Ticket::contarAntes($id_consultorio, $id_ticket);
    }

    public function contarTicketsHoy() {
        return Ticket::contarHoy();
    }

    public function contarTicketsPorEstado($estado) {
        return Ticket::contarPorEstado($estado);
    }

    // RF 7: Cancelar ticket (solo en estado Espera)
    public function cancelarTicket($id_usuario, $id_ticket) {
        $paciente = Paciente::buscarPorIdUsuario($id_usuario);
        if (!$paciente) {
            return ['success' => false, 'message' => 'Paciente no encontrado'];
        }

        if (Ticket::cancelar($id_ticket, $paciente['id_paciente'])) {
            return ['success' => true, 'message' => 'Ticket cancelado correctamente'];
        }

        return ['success' => false, 'message' => 'No se pudo cancelar el ticket. Solo puede cancelar tickets en estado de espera.'];
    }
}
