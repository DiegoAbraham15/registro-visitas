<?php

namespace Tests\Concerns;

use Illuminate\Support\Facades\DB;

/**
 * Helper para insertar filas crudas en las tablas de visita (no son modelos
 * Eloquent, ver nota en VisitaConsultas) directamente desde los tests.
 */
trait CreaVisitas
{
    private function creaVisita(array $atributos = []): int
    {
        $idVisita = DB::table('visita')->insertGetId(array_merge([
            'id_edificio' => 1,
            'tipo_visitante' => 'visitante',
            'fecha_entrada' => now(),
            'estado' => 'activa',
            'fecha_salida' => null,
        ], $atributos));

        return $idVisita;
    }

    private function creaVisitaFamiliar(array $atributosVisita = [], array $atributosFamiliar = []): int
    {
        $id = $this->creaVisita(array_merge(['id_edificio' => 1], $atributosVisita));

        DB::table('visita_familiar')->insert(array_merge([
            'id_visita' => $id,
            'nombre' => 'Familiar de Prueba',
            'parentesco' => 'Hermano',
            'habitacion' => '101',
            'piso' => 'Piso 1',
            'nombre_paciente' => 'Paciente de Prueba',
            'folio' => 'F-'.$id,
        ], $atributosFamiliar));

        return $id;
    }

    private function creaVisitaProveedor(array $atributosVisita = [], array $atributosProveedor = []): int
    {
        $id = $this->creaVisita(array_merge(['id_edificio' => 1], $atributosVisita));

        DB::table('visita_proveedor')->insert(array_merge([
            'id_visita' => $id,
            'empresa_representada' => 'Empresa de Prueba',
            'nombre' => 'Proveedor de Prueba',
            'piso_destino' => 'Piso 1',
            'area_destino' => 'Piso 1',
            'estado' => 'activa',
            'fecha' => now()->toDateString(),
            'folio' => 'F-'.$id,
        ], $atributosProveedor));

        return $id;
    }

    private function creaVisitaPostulante(array $atributosVisita = [], array $atributosPostulante = []): int
    {
        $id = $this->creaVisita(array_merge(['id_edificio' => 1], $atributosVisita));

        DB::table('visita_postulante')->insert(array_merge([
            'id_visita' => $id,
            'nombre' => 'Postulante de Prueba',
            'puesto' => 'Enfermero',
            'area_destino' => 'Piso 1',
            'responsable_rh' => 'RH de Prueba',
            'tipo_cita' => 'entrevista',
            'cv_entregado' => true,
            'folio' => 'F-'.$id,
        ], $atributosPostulante));

        return $id;
    }

    private function creaVisitaTorre(array $atributosVisita = [], array $atributosTorre = []): int
    {
        $id = $this->creaVisita(array_merge(['id_edificio' => 2], $atributosVisita));

        DB::table('visita_torre')->insert(array_merge([
            'id_visita' => $id,
            'tipo_acceso' => 'visitante',
            'piso' => 'Piso 2',
            'consultorio' => '201',
            'nombre' => 'Visitante Torre de Prueba',
            'folio' => 'F-'.$id,
        ], $atributosTorre));

        return $id;
    }

    private function creaVisitaExEmpleado(array $atributosVisita = [], array $atributosExEmpleado = []): int
    {
        $id = $this->creaVisita(array_merge(['id_edificio' => 1], $atributosVisita));

        DB::table('ex_empleados')->insert(array_merge([
            'id_visita' => $id,
            'folio' => 'F-'.$id,
            'nombre' => 'Ex Empleado de Prueba',
            'motivo' => 'Finiquito',
            'tipo_visita' => 'Ex Empleado',
            'foto_persona' => '/uploads/prueba.jpeg',
        ], $atributosExEmpleado));

        return $id;
    }

    private function creaVisitaCafeteria(array $atributosVisita = [], array $atributosProveedor = []): int
    {
        return $this->creaVisitaProveedor($atributosVisita, array_merge([
            'area_destino' => 'CAFETERÍA',
        ], $atributosProveedor));
    }
}
