<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreaVisitas;
use Tests\TestCase;

class VisitaControllerTest extends TestCase
{
    use CreaVisitas;
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_when_editing(): void
    {
        $response = $this->get('/visitas/1/editar');

        $response->assertRedirect('/login');
    }

    public function test_edit_returns_404_for_unknown_visita(): void
    {
        $user = User::factory()->admin()->create();
        $this->actingAs($user);

        $response = $this->get('/visitas/999999/editar');

        $response->assertNotFound();
    }

    public function test_edit_forbidden_when_visita_belongs_to_another_area(): void
    {
        $idVisita = $this->creaVisitaFamiliar(['id_edificio' => 1]);

        $user = User::factory()->create(['area' => 'consultorios']);
        $this->actingAs($user);

        $response = $this->get("/visitas/{$idVisita}/editar");

        $response->assertForbidden();
    }

    public function test_edit_allowed_for_matching_area(): void
    {
        $idVisita = $this->creaVisitaFamiliar(['id_edificio' => 1]);

        $user = User::factory()->create(['area' => 'hospital']);
        $this->actingAs($user);

        $response = $this->get("/visitas/{$idVisita}/editar");

        $response->assertOk();
        $response->assertViewIs('visitas.editar');
    }

    public function test_admin_can_edit_visita_from_any_area(): void
    {
        $idVisita = $this->creaVisitaTorre();

        $admin = User::factory()->admin()->create(['area' => 'hospital']);
        $this->actingAs($admin);

        $response = $this->get("/visitas/{$idVisita}/editar");

        $response->assertOk();
    }

    /**
     * Reproduce un problema real visto en la BD compartida con la app móvil:
     * 'id_visita' en las tablas hija no tiene FK ni unique, así que una fila
     * huérfana de visita_familiar puede compartir id_visita con una visita
     * real de Torre. La pantalla de edición no debe mostrar los datos de esa
     * fila fantasma (de otro edificio) en vez de los de la visita real.
     */
    public function test_editing_a_torre_visita_ignores_a_phantom_familiar_row_sharing_its_id(): void
    {
        $idVisitaTorre = $this->creaVisitaTorre(['id_edificio' => 2], ['consultorio' => '103', 'nombre' => 'Uriel']);

        DB::table('visita_familiar')->insert([
            'id_visita' => $idVisitaTorre,
            'nombre' => 'Fantasma Hospital',
            'parentesco' => 'Hospital',
            'habitacion' => '202',
            'piso' => 'Piso 2',
            'nombre_paciente' => 'Paciente Fantasma',
            'folio' => 'MIA-FANTASMA',
        ]);

        $user = User::factory()->create(['area' => 'consultorios']);
        $this->actingAs($user);

        $response = $this->get("/visitas/{$idVisitaTorre}/editar");

        $response->assertOk();
        $response->assertSee('Uriel');
        $response->assertDontSee('Fantasma Hospital');
    }

    public function test_edit_shows_medico_options_for_the_visitas_consultorio(): void
    {
        DB::table('consultorios_medicos')->insert([
            ['consultorio' => '108', 'nombre_medico' => 'DR. JUAN PEREZ', 'created_at' => now(), 'updated_at' => now()],
            ['consultorio' => '212', 'nombre_medico' => 'DRA. MARIA LOPEZ', 'created_at' => now(), 'updated_at' => now()],
        ]);
        $idVisita = $this->creaVisitaTorre(['id_edificio' => 2], ['consultorio' => '108']);

        $user = User::factory()->create(['area' => 'consultorios']);
        $this->actingAs($user);

        $response = $this->get("/visitas/{$idVisita}/editar");

        $response->assertOk();
        $response->assertSee('DR. JUAN PEREZ');
        // El <select> solo debe listar como opción seleccionable a los médicos del
        // consultorio actual; el catálogo completo también viaja embebido en el
        // <script> de la cascada (igual que OPCIONES_POR_PISO ya lo hace para piso),
        // así que la aserción se acota al <option> renderizado, no a la página entera.
        $this->assertStringNotContainsString('<option value="DRA. MARIA LOPEZ"', $response->getContent());
    }

    public function test_admin_can_set_a_medico_when_updating_a_torre_visita(): void
    {
        DB::table('catalogo_consultorios')->insert(['piso' => 'Piso 1', 'piso_orden' => 1, 'numero' => '108']);
        DB::table('consultorios_medicos')->insert([
            'consultorio' => '108', 'nombre_medico' => 'DR. JUAN PEREZ', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $idVisita = $this->creaVisitaTorre(['id_edificio' => 2]);

        $user = User::factory()->create(['area' => 'consultorios']);
        $this->actingAs($user);

        $response = $this->put("/visitas/{$idVisita}", [
            'nombre_visitante' => 'Visitante Torre',
            'estado' => 'activa',
            'piso' => 'Piso 1',
            'detalle' => '108',
            'medico' => 'DR. JUAN PEREZ',
        ]);

        $response->assertRedirect('/consultorios/dashboard');
        $this->assertDatabaseHas('visita_torre', [
            'id_visita' => $idVisita,
            'consultorio' => '108',
            'nombre_medico' => 'DR. JUAN PEREZ',
        ]);
    }

    public function test_updating_a_torre_visita_with_a_medico_not_assigned_to_that_consultorio_fails(): void
    {
        DB::table('catalogo_consultorios')->insert(['piso' => 'Piso 1', 'piso_orden' => 1, 'numero' => '108']);
        DB::table('consultorios_medicos')->insert([
            'consultorio' => '212', 'nombre_medico' => 'DRA. MARIA LOPEZ', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $idVisita = $this->creaVisitaTorre(['id_edificio' => 2]);

        $user = User::factory()->create(['area' => 'consultorios']);
        $this->actingAs($user);

        $response = $this->from("/visitas/{$idVisita}/editar")->put("/visitas/{$idVisita}", [
            'nombre_visitante' => 'Visitante Torre',
            'estado' => 'activa',
            'piso' => 'Piso 1',
            'detalle' => '108',
            'medico' => 'DRA. MARIA LOPEZ',
        ]);

        $response->assertSessionHasErrors('medico');
    }

    public function test_updating_a_torre_visita_can_leave_the_medico_unspecified(): void
    {
        DB::table('catalogo_consultorios')->insert(['piso' => 'Piso 1', 'piso_orden' => 1, 'numero' => '108']);
        $idVisita = $this->creaVisitaTorre(['id_edificio' => 2], ['consultorio' => '108', 'nombre_medico' => 'DR. VIEJO']);

        $user = User::factory()->create(['area' => 'consultorios']);
        $this->actingAs($user);

        $response = $this->put("/visitas/{$idVisita}", [
            'nombre_visitante' => 'Visitante Torre',
            'estado' => 'activa',
            'piso' => 'Piso 1',
            'detalle' => '108',
            'medico' => '',
        ]);

        $response->assertRedirect('/consultorios/dashboard');
        $this->assertDatabaseHas('visita_torre', ['id_visita' => $idVisita, 'nombre_medico' => null]);
    }

    public function test_medico_field_is_ignored_for_non_torre_visits(): void
    {
        DB::table('catalogo_habitaciones')->insert(['piso' => 'Piso 1', 'piso_orden' => 1, 'numero' => '101']);
        $idVisita = $this->creaVisitaFamiliar();

        $user = User::factory()->create(['area' => 'hospital']);
        $this->actingAs($user);

        $response = $this->put("/visitas/{$idVisita}", [
            'nombre_visitante' => 'Nuevo Nombre',
            'estado' => 'activa',
            'piso' => 'Piso 1',
            'detalle' => '101',
            'medico' => 'DR. NO DEBERIA GUARDARSE',
        ]);

        $response->assertRedirect('/hospital/dashboard');
        $this->assertDatabaseHas('visita_familiar', ['id_visita' => $idVisita, 'nombre' => 'Nuevo Nombre']);
    }

    public function test_cafeteria_user_can_only_edit_cafeteria_visits(): void
    {
        $idOtraVisita = $this->creaVisitaProveedor(['id_edificio' => 1], ['area_destino' => 'Piso 1']);
        $idCafeteria = $this->creaVisitaCafeteria();

        $user = User::factory()->create(['area' => 'cafeteria']);
        $this->actingAs($user);

        $this->get("/visitas/{$idOtraVisita}/editar")->assertForbidden();
        $this->get("/visitas/{$idCafeteria}/editar")->assertOk();
    }

    public function test_update_requires_nombre_visitante_and_estado(): void
    {
        $idVisita = $this->creaVisitaFamiliar();

        $user = User::factory()->create(['area' => 'hospital']);
        $this->actingAs($user);

        $response = $this->from("/visitas/{$idVisita}/editar")->put("/visitas/{$idVisita}", []);

        $response->assertRedirect("/visitas/{$idVisita}/editar");
        $response->assertSessionHasErrors(['nombre_visitante', 'estado']);
    }

    public function test_update_is_forbidden_for_a_visita_outside_the_users_area(): void
    {
        $idVisita = $this->creaVisitaFamiliar(['id_edificio' => 1]);

        $user = User::factory()->create(['area' => 'consultorios']);
        $this->actingAs($user);

        $response = $this->put("/visitas/{$idVisita}", [
            'nombre_visitante' => 'Nuevo Nombre',
            'estado' => 'activa',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('visita_familiar', ['id_visita' => $idVisita, 'nombre' => 'Familiar de Prueba']);
    }

    public function test_update_familiar_visit_validates_detalle_against_catalog_for_selected_piso(): void
    {
        DB::table('catalogo_habitaciones')->insert(['piso' => 'Piso 1', 'piso_orden' => 1, 'numero' => '101']);

        $idVisita = $this->creaVisitaFamiliar();

        $user = User::factory()->create(['area' => 'hospital']);
        $this->actingAs($user);

        $response = $this->from("/visitas/{$idVisita}/editar")->put("/visitas/{$idVisita}", [
            'nombre_visitante' => 'Nuevo Nombre',
            'estado' => 'activa',
            'piso' => 'Piso 1',
            'detalle' => 'habitacion-que-no-existe',
        ]);

        $response->assertSessionHasErrors('detalle');
    }

    public function test_update_familiar_visit_succeeds_with_valid_catalog_value(): void
    {
        DB::table('catalogo_habitaciones')->insert(['piso' => 'Piso 1', 'piso_orden' => 1, 'numero' => '101']);

        $idVisita = $this->creaVisitaFamiliar();

        $user = User::factory()->create(['area' => 'hospital']);
        $this->actingAs($user);

        $response = $this->put("/visitas/{$idVisita}", [
            'nombre_visitante' => 'Nombre Actualizado',
            'estado' => 'activa',
            'piso' => 'Piso 1',
            'detalle' => '101',
        ]);

        $response->assertRedirect('/hospital/dashboard');
        $this->assertDatabaseHas('visita_familiar', [
            'id_visita' => $idVisita,
            'nombre' => 'Nombre Actualizado',
            'habitacion' => '101',
            'piso' => 'Piso 1',
        ]);
    }

    public function test_update_sets_fecha_salida_when_marked_as_finalizada(): void
    {
        DB::table('catalogo_consultorios')->insert(['piso' => 'Piso 2', 'piso_orden' => 1, 'numero' => '201']);

        $idVisita = $this->creaVisitaTorre();

        $user = User::factory()->create(['area' => 'consultorios']);
        $this->actingAs($user);

        $response = $this->put("/visitas/{$idVisita}", [
            'nombre_visitante' => 'Visitante Torre',
            'estado' => 'finalizada',
            'piso' => 'Piso 2',
            'detalle' => '201',
        ]);

        $response->assertRedirect('/consultorios/dashboard');

        $visita = DB::table('visita')->where('id_visita', $idVisita)->first();
        $this->assertSame('finalizada', $visita->estado);
        $this->assertNotNull($visita->fecha_salida);
    }

    public function test_update_clears_fecha_salida_when_visit_is_reactivated(): void
    {
        DB::table('catalogo_consultorios')->insert(['piso' => 'Piso 2', 'piso_orden' => 1, 'numero' => '201']);

        $idVisita = $this->creaVisitaTorre(['estado' => 'finalizada', 'fecha_salida' => now()]);

        $user = User::factory()->create(['area' => 'consultorios']);
        $this->actingAs($user);

        $this->put("/visitas/{$idVisita}", [
            'nombre_visitante' => 'Visitante Torre',
            'estado' => 'activa',
            'piso' => 'Piso 2',
            'detalle' => '201',
        ]);

        $visita = DB::table('visita')->where('id_visita', $idVisita)->first();
        $this->assertSame('activa', $visita->estado);
        $this->assertNull($visita->fecha_salida);
    }

    public function test_update_sets_hora_salida_on_visita_proveedor_when_marked_as_finalizada(): void
    {
        DB::table('catalogo_areas')->insert(['piso' => 'Piso 1', 'piso_orden' => 1, 'nombre' => 'Piso 1']);

        $idVisita = $this->creaVisitaProveedor();

        $user = User::factory()->create(['area' => 'hospital']);
        $this->actingAs($user);

        $response = $this->put("/visitas/{$idVisita}", [
            'nombre_visitante' => 'Proveedor Actualizado',
            'estado' => 'finalizada',
            'piso' => 'Piso 1',
            'detalle' => 'Piso 1',
        ]);

        $response->assertRedirect('/hospital/dashboard');

        $visita = DB::table('visita')->where('id_visita', $idVisita)->first();
        $this->assertSame('finalizada', $visita->estado);
        $this->assertNotNull($visita->fecha_salida);

        $proveedor = DB::table('visita_proveedor')->where('id_visita', $idVisita)->first();
        $this->assertNotNull($proveedor->hora_salida);
    }

    public function test_update_clears_hora_salida_on_visita_proveedor_when_visit_is_reactivated(): void
    {
        DB::table('catalogo_areas')->insert(['piso' => 'Piso 1', 'piso_orden' => 1, 'nombre' => 'Piso 1']);

        $idVisita = $this->creaVisitaProveedor(
            ['estado' => 'finalizada', 'fecha_salida' => now()],
            ['hora_salida' => now()->format('H:i:s')]
        );

        $user = User::factory()->create(['area' => 'hospital']);
        $this->actingAs($user);

        $this->put("/visitas/{$idVisita}", [
            'nombre_visitante' => 'Proveedor Actualizado',
            'estado' => 'activa',
            'piso' => 'Piso 1',
            'detalle' => 'Piso 1',
        ]);

        $visita = DB::table('visita')->where('id_visita', $idVisita)->first();
        $this->assertSame('activa', $visita->estado);
        $this->assertNull($visita->fecha_salida);

        $proveedor = DB::table('visita_proveedor')->where('id_visita', $idVisita)->first();
        $this->assertNull($proveedor->hora_salida);
    }

    public function test_dashboard_shows_ex_empleado_data(): void
    {
        $idVisita = $this->creaVisitaExEmpleado([], ['nombre' => 'Ana Ex Empleada', 'motivo' => 'Renuncia']);

        $user = User::factory()->create(['area' => 'hospital']);
        $this->actingAs($user);

        $response = $this->get('/hospital/dashboard');

        $response->assertOk();
        $response->assertSee('Ana Ex Empleada');
        $response->assertSee('Renuncia');
    }

    public function test_dashboard_shows_medico_for_torre_visits(): void
    {
        $this->creaVisitaTorre(['id_edificio' => 2], ['consultorio' => '108', 'nombre_medico' => 'DR. JUAN PEREZ']);

        $user = User::factory()->create(['area' => 'consultorios']);
        $this->actingAs($user);

        $response = $this->get('/consultorios/dashboard');

        $response->assertOk();
        $response->assertSee('DR. JUAN PEREZ');
    }

    public function test_update_edits_visita_ex_empleado(): void
    {
        $idVisita = $this->creaVisitaExEmpleado();

        $user = User::factory()->create(['area' => 'hospital']);
        $this->actingAs($user);

        $response = $this->put("/visitas/{$idVisita}", [
            'nombre_visitante' => 'Nombre Actualizado',
            'estado' => 'activa',
            'detalle' => 'Otro motivo',
        ]);

        $response->assertRedirect('/hospital/dashboard');
        $this->assertDatabaseHas('ex_empleados', [
            'id_visita' => $idVisita,
            'nombre' => 'Nombre Actualizado',
            'motivo' => 'Otro motivo',
        ]);
    }

    public function test_update_sets_fecha_salida_on_ex_empleado_when_marked_as_finalizada(): void
    {
        $idVisita = $this->creaVisitaExEmpleado();

        $user = User::factory()->create(['area' => 'hospital']);
        $this->actingAs($user);

        $response = $this->put("/visitas/{$idVisita}", [
            'nombre_visitante' => 'Ex Empleado de Prueba',
            'estado' => 'finalizada',
            'detalle' => 'Finiquito',
        ]);

        $response->assertRedirect('/hospital/dashboard');

        $visita = DB::table('visita')->where('id_visita', $idVisita)->first();
        $this->assertSame('finalizada', $visita->estado);
        $this->assertNotNull($visita->fecha_salida);

        $exEmpleado = DB::table('ex_empleados')->where('id_visita', $idVisita)->first();
        $this->assertNotNull($exEmpleado->fecha_salida);
    }

    public function test_update_postulante_does_not_require_piso(): void
    {
        $idVisita = $this->creaVisitaPostulante();

        $user = User::factory()->create(['area' => 'hospital']);
        $this->actingAs($user);

        $response = $this->put("/visitas/{$idVisita}", [
            'nombre_visitante' => 'Postulante Actualizado',
            'estado' => 'activa',
            'detalle' => 'Enfermero Jefe',
        ]);

        $response->assertRedirect('/hospital/dashboard');
        $this->assertDatabaseHas('visita_postulante', [
            'id_visita' => $idVisita,
            'nombre' => 'Postulante Actualizado',
            'puesto' => 'Enfermero Jefe',
        ]);
    }

    public function test_destroy_is_forbidden_outside_the_users_area(): void
    {
        $idVisita = $this->creaVisitaFamiliar(['id_edificio' => 1]);

        $user = User::factory()->create(['area' => 'consultorios']);
        $this->actingAs($user);

        $response = $this->delete("/visitas/{$idVisita}");

        $response->assertForbidden();
        $this->assertDatabaseHas('visita', ['id_visita' => $idVisita]);
    }

    public function test_destroy_removes_visita_and_its_child_row(): void
    {
        $idVisita = $this->creaVisitaFamiliar();

        $user = User::factory()->create(['area' => 'hospital']);
        $this->actingAs($user);

        $response = $this->delete("/visitas/{$idVisita}");

        $response->assertRedirect('/hospital/dashboard');
        $this->assertDatabaseMissing('visita', ['id_visita' => $idVisita]);
        $this->assertDatabaseMissing('visita_familiar', ['id_visita' => $idVisita]);
    }

    public function test_destroy_writes_to_the_activity_log(): void
    {
        $idVisita = $this->creaVisitaFamiliar();

        $user = User::factory()->create(['area' => 'hospital']);
        $this->actingAs($user);

        $this->delete("/visitas/{$idVisita}");

        $this->assertDatabaseHas('bitacora', ['accion' => 'visita.eliminar']);
    }

    public function test_admin_can_destroy_visita_from_any_area(): void
    {
        $idVisita = $this->creaVisitaCafeteria();

        $admin = User::factory()->admin()->create(['area' => 'hospital']);
        $this->actingAs($admin);

        $response = $this->delete("/visitas/{$idVisita}");

        $response->assertRedirect('/hospital/dashboard');
        $this->assertDatabaseMissing('visita', ['id_visita' => $idVisita]);
    }
}
