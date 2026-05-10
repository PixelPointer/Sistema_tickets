<?php
require_once dirname(__DIR__) . '/Models/Especialidad.php';
require_once dirname(__DIR__) . '/Models/Consultorio.php';

class EspecialidadController {

    public function listEspecialidades() {
        return Especialidad::listarTodos();
    }

    public function listConsultorios() {
        return Consultorio::listarTodos();
    }

    public function listConsultoriosPorEspecialidad($id_especialidad) {
        return Consultorio::listarPorEspecialidad($id_especialidad);
    }

    public function crearEspecialidad($nombre) {
        if (empty($nombre)) {
            return ['success' => false, 'message' => 'El nombre de la especialidad es requerido'];
        }
        if (Especialidad::crear($nombre)) {
            return ['success' => true, 'message' => 'Especialidad creada correctamente'];
        }
        return ['success' => false, 'message' => 'Error al crear la especialidad'];
    }

    public function eliminarEspecialidad($id) {
        if (Especialidad::eliminar($id)) {
            return ['success' => true, 'message' => 'Especialidad eliminada'];
        }
        return ['success' => false, 'message' => 'Error al eliminar'];
    }

    public function crearConsultorio($id_especialidad, $numero, $piso = '') {
        if (empty($id_especialidad) || empty($numero)) {
            return ['success' => false, 'message' => 'Seleccione especialidad e ingrese número'];
        }
        if (Consultorio::crear($id_especialidad, $numero, $piso)) {
            return ['success' => true, 'message' => 'Consultorio creado correctamente'];
        }
        return ['success' => false, 'message' => 'Error al crear el consultorio'];
    }

    public function eliminarConsultorio($id) {
        if (Consultorio::eliminar($id)) {
            return ['success' => true, 'message' => 'Consultorio eliminado'];
        }
        return ['success' => false, 'message' => 'Error al eliminar'];
    }
}