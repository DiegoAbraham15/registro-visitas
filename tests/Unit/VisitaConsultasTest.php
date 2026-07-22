<?php

namespace Tests\Unit;

use App\Models\User;
use App\Support\VisitaConsultas;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreaVisitas;
use Tests\TestCase;

class VisitaConsultasTest extends TestCase
{
    use CreaVisitas;
    use RefreshDatabase;

    public function test_por_area_deduces_tipo_real_for_each_visitor_type(): void
    {
        $idFamiliar = $this->creaVisitaFamiliar(['id_edificio' => 1]);
        $idProveedor = $this->creaVisitaProveedor(['id_edificio' => 1]);
        $idPostulante = $this->creaVisitaPostulante(['id_edificio' => 1]);

        $visitas = VisitaConsultas::porArea('edificio', 1)->keyBy('id_visita');

        $this->assertSame('familiar', $visitas[$idFamiliar]->tipo_real);
        $this->assertSame('proveedor', $visitas[$idProveedor]->tipo_real);
        $this->assertSame('postulante', $visitas[$idPostulante]->tipo_real);
    }

    public function test_por_area_for_torre_uses_tipo_acceso_regardless_of_value(): void
    {
        $idVisita = $this->creaVisitaTorre([], ['tipo_acceso' => 'paciente']);

        $visitas = VisitaConsultas::porArea('edificio', 2)->keyBy('id_visita');

        $this->assertSame('paciente', $visitas[$idVisita]->tipo_real);
    }

    public function test_por_area_marks_orphan_visits_as_sin_datos(): void
    {
        $idVisita = $this->creaVisita(['id_edificio' => 1]);

        $visitas = VisitaConsultas::porArea('edificio', 1)->keyBy('id_visita');

        $this->assertSame('sin-datos', $visitas[$idVisita]->tipo_real);
    }

    public function test_por_area_filters_by_edificio(): void
    {
        $this->creaVisitaFamiliar(['id_edificio' => 1]);
        $this->creaVisitaTorre(['id_edificio' => 2]);

        $visitasHospital = VisitaConsultas::porArea('edificio', 1);

        $this->assertCount(1, $visitasHospital);
        $this->assertSame(1, (int) $visitasHospital->first()->id_edificio);
    }

    public function test_por_area_only_returns_activas_by_default(): void
    {
        $this->creaVisitaFamiliar(['id_edificio' => 1, 'estado' => 'activa']);
        $this->creaVisitaFamiliar(['id_edificio' => 1, 'estado' => 'finalizada']);

        $soloActivas = VisitaConsultas::porArea('edificio', 1);
        $todas = VisitaConsultas::porArea('edificio', 1, false);

        $this->assertCount(1, $soloActivas);
        $this->assertSame('activa', $soloActivas->first()->estado);
        $this->assertCount(2, $todas);
    }

    public function test_por_area_cafeteria_only_returns_cafeteria_providers(): void
    {
        $idCafeteria = $this->creaVisitaCafeteria();
        $this->creaVisitaProveedor(['id_edificio' => 1], ['area_destino' => 'Piso 1']);

        $visitas = VisitaConsultas::porArea('cafeteria', null);

        $this->assertCount(1, $visitas);
        $this->assertSame($idCafeteria, $visitas->first()->id_visita);
    }

    public function test_datos_reporte_scopes_por_edificio_for_a_non_admin_user(): void
    {
        $this->creaVisitaFamiliar(['id_edificio' => 1]);
        $this->creaVisitaTorre(['id_edificio' => 2]);

        $usuario = User::factory()->make(['area' => 'hospital', 'es_admin' => false]);

        $datos = VisitaConsultas::datosReporte($usuario, 'todo');

        $this->assertSame(['Hospital'], $datos['porEdificio']->pluck('edificio')->all());
    }

    public function test_datos_reporte_consolidates_all_areas_for_an_admin_user(): void
    {
        $this->creaVisitaFamiliar(['id_edificio' => 1]);
        $this->creaVisitaTorre(['id_edificio' => 2]);

        $usuario = User::factory()->make(['es_admin' => true]);

        $datos = VisitaConsultas::datosReporte($usuario, 'todo');

        $this->assertSame('Todas las áreas', $datos['areaNombre']);
        $this->assertCount(2, $datos['porEdificio']);
    }

    public function test_datos_reporte_denies_by_default_for_an_area_without_its_own_visits(): void
    {
        $this->creaVisitaFamiliar(['id_edificio' => 1]);
        $this->creaVisitaTorre(['id_edificio' => 2]);

        // 'vinculacion' no gestiona visitas propias en este reporte (solo comida);
        // no debe caer en "sin filtro" = ver el consolidado completo como un admin.
        $usuario = User::factory()->make(['area' => 'vinculacion', 'es_admin' => false]);

        $datos = VisitaConsultas::datosReporte($usuario, 'todo');

        $this->assertCount(0, $datos['porEdificio']);
        $this->assertCount(0, $datos['detalleVisitas']);
    }

    public function test_datos_reporte_cafeteria_user_only_sees_cafeteria_providers(): void
    {
        $this->creaVisitaCafeteria();
        $this->creaVisitaFamiliar(['id_edificio' => 1]);

        $usuario = User::factory()->make(['area' => 'cafeteria', 'es_admin' => false]);

        $datos = VisitaConsultas::datosReporte($usuario, 'todo');

        $this->assertCount(1, $datos['detalleVisitas']);
        $this->assertSame('proveedor', $datos['detalleVisitas']->first()->tipo_visitante);
    }

    public function test_datos_reporte_defaults_to_mes_for_an_invalid_periodo(): void
    {
        $usuario = User::factory()->make(['es_admin' => true]);

        $datos = VisitaConsultas::datosReporte($usuario, 'periodo-invalido');

        $this->assertSame('mes', $datos['periodo']);
    }

    public function test_datos_reporte_dia_period_excludes_visits_from_previous_months(): void
    {
        $this->creaVisitaFamiliar(['id_edificio' => 1, 'fecha_entrada' => now()]);
        $this->creaVisitaFamiliar(['id_edificio' => 1, 'fecha_entrada' => now()->subMonths(2)]);

        $usuario = User::factory()->make(['es_admin' => true]);

        $datos = VisitaConsultas::datosReporte($usuario, 'dia');

        $this->assertCount(1, $datos['detalleVisitas']);
    }

    /**
     * Reproduce un problema real visto en la BD compartida con la app móvil:
     * 'id_visita' en las tablas hija no tiene FK ni unique, así que una fila
     * huérfana de visita_familiar puede terminar compartiendo id_visita con
     * una visita real de Torre de Consultorios. Antes de este candado, esa
     * fila fantasma se colaba en porArea() con tipo_real='familiar' y sus
     * campos de Hospital pisaban los de la visita de Torre.
     */
    private function creaFamiliarFantasmaEn(int $idVisita): void
    {
        DB::table('visita_familiar')->insert([
            'id_visita' => $idVisita,
            'nombre' => 'Fantasma Hospital',
            'parentesco' => 'Hospital',
            'habitacion' => '202',
            'piso' => 'Piso 2',
            'nombre_paciente' => 'Paciente Fantasma',
            'folio' => 'MIA-FANTASMA',
        ]);
    }

    public function test_por_area_ignores_a_phantom_child_row_from_a_different_building(): void
    {
        $idVisitaTorre = $this->creaVisitaTorre(['id_edificio' => 2], ['consultorio' => '103', 'nombre' => 'Uriel']);
        $this->creaFamiliarFantasmaEn($idVisitaTorre);

        $visita = VisitaConsultas::porArea('edificio', 2)->keyBy('id_visita')[$idVisitaTorre];

        $this->assertNotSame('familiar', $visita->tipo_real);
        $this->assertNull($visita->parentesco);
        $this->assertNull($visita->nombre_paciente);
        $this->assertSame('Uriel', $visita->nombre_visitante);
        $this->assertSame('103', $visita->detalle);
    }

    public function test_datos_reporte_excludes_a_phantom_familiar_row_linked_to_a_torre_visit(): void
    {
        $idVisitaTorre = $this->creaVisitaTorre(['id_edificio' => 2]);
        $this->creaFamiliarFantasmaEn($idVisitaTorre);

        $usuarioTorre = User::factory()->make(['area' => 'consultorios', 'es_admin' => false]);
        $usuarioAdmin = User::factory()->make(['es_admin' => true]);

        $this->assertCount(0, VisitaConsultas::datosReporte($usuarioTorre, 'todo')['porPisoFamiliar']);
        $this->assertCount(0, VisitaConsultas::datosReporte($usuarioAdmin, 'todo')['porPisoFamiliar']);
    }

    public function test_por_area_incluye_el_medico_de_una_visita_de_torre(): void
    {
        $idVisita = $this->creaVisitaTorre(['id_edificio' => 2], ['consultorio' => '108', 'nombre_medico' => 'DR. JUAN PEREZ']);

        $visita = VisitaConsultas::porArea('edificio', 2)->keyBy('id_visita')[$idVisita];

        $this->assertSame('DR. JUAN PEREZ', $visita->medico);
    }

    public function test_datos_reporte_rankea_los_consultorios_mas_visitados(): void
    {
        $this->creaVisitaTorre(['id_edificio' => 2], ['consultorio' => '108']);
        $this->creaVisitaTorre(['id_edificio' => 2], ['consultorio' => '108']);
        $this->creaVisitaTorre(['id_edificio' => 2], ['consultorio' => '212']);

        $usuario = User::factory()->make(['es_admin' => true]);
        $datos = VisitaConsultas::datosReporte($usuario, 'todo');

        $this->assertSame('108', $datos['consultoriosMasVisitados']->first()->consultorio);
        $this->assertSame(2, (int) $datos['consultoriosMasVisitados']->first()->total);
    }

    public function test_datos_reporte_rankea_los_medicos_mas_visitados(): void
    {
        $this->creaVisitaTorre(['id_edificio' => 2], ['consultorio' => '108', 'nombre_medico' => 'DR. JUAN PEREZ']);
        $this->creaVisitaTorre(['id_edificio' => 2], ['consultorio' => '108', 'nombre_medico' => 'DR. JUAN PEREZ']);
        $this->creaVisitaTorre(['id_edificio' => 2], ['consultorio' => '212', 'nombre_medico' => 'DRA. MARIA LOPEZ']);
        $this->creaVisitaTorre(['id_edificio' => 2], ['consultorio' => '108', 'nombre_medico' => null]);

        $usuario = User::factory()->make(['es_admin' => true]);
        $datos = VisitaConsultas::datosReporte($usuario, 'todo');

        $this->assertSame('DR. JUAN PEREZ', $datos['doctoresMasVisitados']->first()->medico);
        $this->assertSame(2, (int) $datos['doctoresMasVisitados']->first()->total);
        // 3 visitas con médico capturado (2 de JUAN PEREZ + 1 de MARIA LOPEZ); la
        // cuarta (sin médico) no debe contarse para ningún doctor.
        $this->assertSame(3, (int) $datos['doctoresMasVisitados']->sum('total'));
    }

    public function test_datos_reporte_no_muestra_rankings_de_torre_a_un_usuario_de_hospital(): void
    {
        $this->creaVisitaTorre(['id_edificio' => 2], ['consultorio' => '108', 'nombre_medico' => 'DR. JUAN PEREZ']);

        $usuario = User::factory()->make(['area' => 'hospital', 'es_admin' => false]);
        $datos = VisitaConsultas::datosReporte($usuario, 'todo');

        $this->assertCount(0, $datos['consultoriosMasVisitados']);
        $this->assertCount(0, $datos['doctoresMasVisitados']);
    }
}
