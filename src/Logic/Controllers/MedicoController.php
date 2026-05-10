<?php
require_once dirname(__DIR__) . '/Models/Ticket.php';
require_once dirname(__DIR__) . '/Models/Atencion.php';
require_once dirname(__DIR__) . '/Models/Personal.php';
require_once dirname(__DIR__) . '/Models/Paciente.php';
require_once dirname(__DIR__) . '/Models/Consultorio.php';

class MedicoController {

    private $id_personal;
    private $id_especialidad;
    private $consultorios;

    public function __construct($id_persona) {
        $personal = Personal::buscarPorPersona($id_persona);
        if ($personal) {
            $this->id_personal = $personal['id_personal'];
            $this->id_especialidad = $personal['id_especialidad'];
            $this->consultorios = Consultorio::listarPorEspecialidad($this->id_especialidad);
        }
    }

    public function getDatosMedico() {
        return [
            'id_personal' => $this->id_personal,
            'id_especialidad' => $this->id_especialidad,
            'consultorios' => $this->consultorios
        ];
    }

    public function getIdsConsultorios() {
        if (empty($this->consultorios)) return [];
        return array_column($this->consultorios, 'id_consultorio');
    }

    public function listarEnEspera() {
        return Ticket::listarEnEspera($this->getIdsConsultorios());
    }

    public function llamarSiguiente() {
        if (!$this->id_personal) {
            return ['success' => false, 'message' => 'Médico no encontrado'];
        }

        $id_ticket = Ticket::llamarSiguiente($this->getIdsConsultorios());

        if ($id_ticket) {
            return ['success' => true, 'message' => 'Paciente llamado', 'id_ticket' => $id_ticket];
        }

        return ['success' => false, 'message' => 'No hay pacientes en espera'];
    }

    public function obtenerInfoTicket($id_ticket) {
        return Ticket::obtenerPorId($id_ticket);
    }

    public function obtenerHistorialPaciente($id_ticket) {
        $ticket = Ticket::obtenerPorId($id_ticket);
        if (!$ticket) return [];
        return Atencion::listarHistorial($ticket['id_paciente']);
    }

    public function iniciarAtencion($id_ticket, $tipo_diagnostico, $descripcion_diagnostico, $tratamiento_prescrito, $fecha_proxima_cita = null) {
        if (!$this->id_personal) {
            return ['success' => false, 'message' => 'Médico no encontrado'];
        }

        $existente = Atencion::buscarPorTicket($id_ticket);
        if ($existente) {
            Atencion::actualizar($existente['id_atencion'], $tipo_diagnostico, $descripcion_diagnostico, $tratamiento_prescrito, $fecha_proxima_cita);
        } else {
            $atencion = new Atencion();
            $atencion->crear($id_ticket, $this->id_personal, $tipo_diagnostico, $descripcion_diagnostico, $tratamiento_prescrito, $fecha_proxima_cita);
        }

        Ticket::cambiarEstado($id_ticket, 'Atendido');

        return ['success' => true, 'message' => 'Atención registrada correctamente'];
    }

    public function finalizarAtencion($id_ticket) {
        $atencion = Atencion::buscarPorTicket($id_ticket);
        if ($atencion) {
            Atencion::finalizar($atencion['id_atencion']);
            return ['success' => true, 'message' => 'Atención finalizada'];
        }
        return ['success' => false, 'message' => 'Atención no encontrada'];
    }

    public function calcularMM1() {
        if (!$this->id_especialidad) return null;
        return Ticket::calcularMM1($this->id_especialidad);
    }
}
