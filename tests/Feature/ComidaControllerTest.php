<?php

namespace Tests\Feature;

use App\Models\MenuSemanaOpciones;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreaVisitas;
use Tests\TestCase;

class ComidaControllerTest extends TestCase
{
    use CreaVisitas;
    use RefreshDatabase;

    private function creaHabitacionCatalogo(string $piso = 'Piso 1', string $numero = '101', int $pisoOrden = 1): int
    {
        return DB::table('catalogo_habitaciones')->insertGetId([
            'piso' => $piso,
            'piso_orden' => $pisoOrden,
            'numero' => $numero,
        ]);
    }

    private function creaPacienteActivo(string $piso = 'Piso 1', string $habitacion = '101', string $nombre = 'Paciente de Prueba', bool $activo = true): int
    {
        return DB::table('cafeteria_pacientes')->insertGetId([
            'piso' => $piso,
            'habitacion' => $habitacion,
            'nombre' => $nombre,
            'activo' => $activo,
        ]);
    }

    private function creaCortesiaActiva(string $piso = 'Piso 1', string $habitacion = '101', array $atributos = []): int
    {
        return DB::table('cafeteria_cortesias')->insertGetId(array_merge([
            'piso' => $piso,
            'habitacion' => $habitacion,
            'activo' => true,
            // CortesiaVigente considera "de hoy" según updated_at; por default
            // se crea "fresca" (ya en producción MySQL, es lo que pone
            // ComidaController::marcarCortesiaActiva al escribir); los tests que
            // quieran simular una cortesía de un día anterior deben pasar su
            // propio 'updated_at' en $atributos.
            'created_at' => now(),
            'updated_at' => now(),
        ], $atributos));
    }

    private function fijaOpcionesSemana(array $atributos = []): MenuSemanaOpciones
    {
        $opciones = MenuSemanaOpciones::actual();
        $opciones->update($atributos);

        return $opciones->fresh();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/vinculacion/dashboard')->assertRedirect('/login');
    }

    public function test_user_from_another_area_is_forbidden(): void
    {
        $user = User::factory()->create(['area' => 'hospital']);
        $this->actingAs($user);

        $this->get('/vinculacion/dashboard')->assertForbidden();
    }

    public function test_user_with_acceso_vinculacion_can_view_the_dashboard_from_another_area(): void
    {
        $user = User::factory()->create(['area' => 'hospital', 'acceso_vinculacion' => true]);
        $this->actingAs($user);

        $this->get('/vinculacion/dashboard')->assertOk();
    }

    public function test_vinculacion_user_can_view_the_dashboard(): void
    {
        $user = User::factory()->create(['area' => 'vinculacion']);
        $this->actingAs($user);

        $response = $this->get('/vinculacion/dashboard');

        $response->assertOk();
        $response->assertViewIs('comidas.dashboard');
    }

    public function test_admin_can_also_view_the_dashboard(): void
    {
        $admin = User::factory()->admin()->create(['area' => 'hospital']);
        $this->actingAs($admin);

        $this->get('/vinculacion/dashboard')->assertOk();
    }

    public function test_dashboard_only_lists_rooms_with_an_active_patient(): void
    {
        // Habitación en el catálogo pero sin ningún paciente: no debe aparecer.
        $this->creaHabitacionCatalogo('Piso 1', '102');
        $this->creaPacienteActivo('Piso 1', '101', 'Juan Paciente');
        $this->creaPacienteActivo('Piso 1', '103', 'Paciente Dado de Alta', activo: false);
        $this->creaVisitaFamiliar(['estado' => 'activa'], ['piso' => 'Piso 1', 'habitacion' => '101', 'nombre' => 'Ana Visitante']);

        $user = User::factory()->create(['area' => 'vinculacion']);
        $this->actingAs($user);

        $response = $this->get('/vinculacion/dashboard');

        $response->assertViewHas('habitaciones', function ($habitaciones) {
            $habitacion101 = $habitaciones->firstWhere('habitacion', '101');

            return $habitaciones->count() === 1
                && $habitacion101->pacientes->pluck('nombre')->all() === ['Juan Paciente']
                && $habitacion101->visitantes->pluck('nombre')->all() === ['Ana Visitante'];
        });
    }

    public function test_dashboard_flags_rooms_missing_desayuno_or_cena_today(): void
    {
        $this->creaCortesiaActiva('Piso 1', '101', ['platillo_desayuno' => 'Hot cakes', 'platillo_cena' => 'Sopa']);
        $this->creaCortesiaActiva('Piso 1', '102', ['platillo_desayuno' => 'Hot cakes']);

        $user = User::factory()->create(['area' => 'vinculacion']);
        $this->actingAs($user);

        $response = $this->get('/vinculacion/dashboard');

        $response->assertViewHas('habitacionesSinMenu', 1);
    }

    public function test_dashboard_flags_a_room_whose_menu_is_from_a_previous_day(): void
    {
        // Mismo paciente dos días seguidos: ayer se llenó el menú, pero nadie
        // volvió a tocar la habitación hoy — no debe contar como resuelta.
        $this->creaCortesiaActiva('Piso 1', '101', [
            'platillo_desayuno' => 'Hot cakes',
            'platillo_cena' => 'Sopa',
            'updated_at' => now()->subDay(),
        ]);

        $user = User::factory()->create(['area' => 'vinculacion']);
        $this->actingAs($user);

        $response = $this->get('/vinculacion/dashboard');

        $response->assertViewHas('habitacionesSinMenu', 1);
    }

    public function test_reactivating_a_stale_room_clears_its_leftover_menu(): void
    {
        $idCatalogo = $this->creaHabitacionCatalogo('Piso 1', '101');
        $this->creaCortesiaActiva('Piso 1', '101', [
            'activo' => false,
            'platillo_desayuno' => 'Menú del paciente anterior',
            'platillo_cena' => 'Menú del paciente anterior',
            'bebida' => 'Agua',
            'entregar_a' => 'Visitante anterior',
            'updated_at' => now()->subDay(),
        ]);

        $user = User::factory()->create(['area' => 'vinculacion']);
        $this->actingAs($user);

        $this->post('/vinculacion/habitaciones', ['catalogo_habitacion_id' => $idCatalogo]);

        $this->assertDatabaseHas('cafeteria_cortesias', [
            'piso' => 'Piso 1',
            'habitacion' => '101',
            'activo' => 1,
            'platillo_desayuno' => null,
            'platillo_cena' => null,
            'bebida' => null,
            'entregar_a' => null,
        ]);
    }

    public function test_room_with_only_an_active_visit_also_appears_on_the_dashboard(): void
    {
        // Sin paciente en cafeteria_pacientes ni cortesía: solo una visita familiar activa.
        $this->creaVisitaFamiliar(['estado' => 'activa'], ['piso' => 'Piso 2', 'habitacion' => '206', 'nombre' => 'Visitante Nuevo']);

        $user = User::factory()->create(['area' => 'vinculacion']);
        $this->actingAs($user);

        $response = $this->get('/vinculacion/dashboard');

        $response->assertViewHas('habitaciones', function ($habitaciones) {
            $habitacion = $habitaciones->firstWhere('habitacion', '206');

            return $habitacion !== null
                && $habitacion->pacientes->isEmpty()
                && $habitacion->visitantes->pluck('nombre')->all() === ['Visitante Nuevo'];
        });
    }

    public function test_manually_added_room_appears_on_the_dashboard_even_without_a_patient(): void
    {
        $idCatalogo = $this->creaHabitacionCatalogo('Piso 2', '205');

        $user = User::factory()->create(['area' => 'vinculacion']);
        $this->actingAs($user);

        $this->post('/vinculacion/habitaciones', ['catalogo_habitacion_id' => $idCatalogo]);

        $response = $this->get('/vinculacion/dashboard');

        $response->assertViewHas('habitaciones', function ($habitaciones) {
            $agregada = $habitaciones->firstWhere('habitacion', '205');

            return $agregada && $agregada->pacientes->isEmpty();
        });
        $this->assertDatabaseHas('cafeteria_cortesias', ['piso' => 'Piso 2', 'habitacion' => '205', 'activo' => 1]);
    }

    public function test_dashboard_only_offers_rooms_that_are_not_already_shown(): void
    {
        $idOcupada = $this->creaHabitacionCatalogo('Piso 1', '101');
        $idDisponible = $this->creaHabitacionCatalogo('Piso 1', '102');
        $this->creaPacienteActivo('Piso 1', '101');

        $user = User::factory()->create(['area' => 'vinculacion']);
        $this->actingAs($user);

        $response = $this->get('/vinculacion/dashboard');

        $response->assertViewHas('habitacionesPorPiso', function ($porPiso) use ($idOcupada, $idDisponible) {
            $ids = $porPiso->get('Piso 1', collect())->pluck('id');

            return $ids->contains($idDisponible) && ! $ids->contains($idOcupada);
        });
    }

    public function test_store_is_forbidden_for_a_user_from_another_area(): void
    {
        $idCatalogo = $this->creaHabitacionCatalogo('Piso 2', '205');

        $user = User::factory()->create(['area' => 'cafeteria']);
        $this->actingAs($user);

        $this->post('/vinculacion/habitaciones', ['catalogo_habitacion_id' => $idCatalogo])->assertForbidden();
    }

    public function test_store_requires_a_valid_catalogo_habitacion_id(): void
    {
        $user = User::factory()->create(['area' => 'vinculacion']);
        $this->actingAs($user);

        $response = $this->from('/vinculacion/dashboard')->post('/vinculacion/habitaciones', ['catalogo_habitacion_id' => 999999]);

        $response->assertSessionHasErrors('catalogo_habitacion_id');
    }

    public function test_update_saves_the_selected_familiar_as_recipiente(): void
    {
        $this->creaPacienteActivo('Piso 1', '101');
        $idVisita = $this->creaVisitaFamiliar(['estado' => 'activa'], ['piso' => 'Piso 1', 'habitacion' => '101']);
        $this->fijaOpcionesSemana(['desayuno_opciones' => ['Hot cakes', 'Huevo'], 'cena_opciones' => ['Sopa', 'Sandwich']]);

        $user = User::factory()->create(['area' => 'vinculacion']);
        $this->actingAs($user);

        $response = $this->put('/vinculacion/habitaciones/comida', [
            'piso' => 'Piso 1',
            'habitacion' => '101',
            'recipiente' => (string) $idVisita,
            'observaciones' => 'Alergia a mariscos',
            'desayuno' => 'Huevo',
            'cena' => 'Sopa',
            'bebida' => 'Jugo',
        ]);

        $response->assertRedirect('/vinculacion/dashboard');

        // Platillos, bebida y a quién entregarle: en la tabla real de la app móvil.
        $this->assertDatabaseHas('cafeteria_cortesias', [
            'piso' => 'Piso 1',
            'habitacion' => '101',
            'activo' => 1,
            'platillo_desayuno' => 'Huevo',
            'platillo_cena' => 'Sopa',
            'bebida' => 'Jugo',
            'entregar_a' => 'Familiar de Prueba',
        ]);

        // Quién quedó elegido y las observaciones: en nuestra tabla auxiliar.
        $this->assertDatabaseHas('comida_visitantes', [
            'piso' => 'Piso 1',
            'habitacion' => '101',
            'otro_texto' => null,
            'observaciones' => 'Alergia a mariscos',
        ]);
        $seleccion = \App\Models\ComidaVisitantes::where('piso', 'Piso 1')->where('habitacion', '101')->first();
        $this->assertSame([$idVisita], $seleccion->visitantes_seleccionados);
    }

    public function test_update_saves_otro_as_the_recipiente_instead_of_a_familiar(): void
    {
        $this->creaPacienteActivo('Piso 1', '101');
        $this->creaVisitaFamiliar(['estado' => 'activa'], ['piso' => 'Piso 1', 'habitacion' => '101']);

        $user = User::factory()->create(['area' => 'vinculacion']);
        $this->actingAs($user);

        $this->put('/vinculacion/habitaciones/comida', [
            'piso' => 'Piso 1',
            'habitacion' => '101',
            'recipiente' => 'otro',
            'otro' => 'Sr. López (acompañante extra)',
        ]);

        // Al elegir "otro", no cuenta ningún familiar registrado aunque haya uno activo.
        $this->assertDatabaseHas('comida_visitantes', [
            'piso' => 'Piso 1',
            'habitacion' => '101',
            'visitantes_seleccionados' => '[]',
            'otro_texto' => 'Sr. López (acompañante extra)',
        ]);
        $this->assertDatabaseHas('cafeteria_cortesias', [
            'piso' => 'Piso 1',
            'habitacion' => '101',
            'entregar_a' => 'Sr. López (acompañante extra)',
        ]);
    }

    public function test_update_ignores_otro_text_when_recipiente_is_not_otro(): void
    {
        $this->creaPacienteActivo('Piso 1', '101');

        $user = User::factory()->create(['area' => 'vinculacion']);
        $this->actingAs($user);

        $this->put('/vinculacion/habitaciones/comida', [
            'piso' => 'Piso 1',
            'habitacion' => '101',
            'otro' => 'Sr. López (acompañante extra)',
        ]);

        $this->assertDatabaseHas('comida_visitantes', [
            'piso' => 'Piso 1',
            'habitacion' => '101',
            'otro_texto' => null,
        ]);
        $this->assertDatabaseHas('cafeteria_cortesias', [
            'piso' => 'Piso 1',
            'habitacion' => '101',
            'entregar_a' => null,
        ]);
    }

    public function test_update_sets_platillo_comida_from_todays_fixed_menu(): void
    {
        $this->creaPacienteActivo('Piso 1', '101');

        $user = User::factory()->create(['area' => 'vinculacion']);
        $this->actingAs($user);

        $this->put('/vinculacion/habitaciones/comida', ['piso' => 'Piso 1', 'habitacion' => '101']);

        $comida = \App\Models\MenuDia::firstOrCreate(['dia' => \App\Models\MenuDia::DIAS[now()->dayOfWeek]])->comida;

        $this->assertDatabaseHas('cafeteria_cortesias', [
            'piso' => 'Piso 1',
            'habitacion' => '101',
            'platillo_comida' => $comida,
        ]);
    }

    public function test_update_does_not_overwrite_an_existing_cortesia_row_created_at(): void
    {
        $this->creaCortesiaActiva('Piso 1', '101', ['platillo_desayuno' => 'Anterior']);

        $user = User::factory()->create(['area' => 'vinculacion']);
        $this->actingAs($user);

        $this->put('/vinculacion/habitaciones/comida', ['piso' => 'Piso 1', 'habitacion' => '101']);

        $this->assertSame(1, DB::table('cafeteria_cortesias')->where('piso', 'Piso 1')->where('habitacion', '101')->count());
    }

    public function test_update_rejects_a_meal_option_not_in_the_weekly_options(): void
    {
        $this->fijaOpcionesSemana(['desayuno_opciones' => ['Hot cakes'], 'cena_opciones' => ['Sopa']]);

        $user = User::factory()->create(['area' => 'vinculacion']);
        $this->actingAs($user);

        $response = $this->from('/vinculacion/dashboard')->put('/vinculacion/habitaciones/comida', [
            'piso' => 'Piso 1',
            'habitacion' => '101',
            'desayuno' => 'Opción que no existe',
        ]);

        $response->assertSessionHasErrors('desayuno');
    }

    public function test_update_rejects_a_visitor_id_that_does_not_belong_to_the_room(): void
    {
        $idVisitaDeOtroCuarto = $this->creaVisitaFamiliar(['estado' => 'activa'], ['piso' => 'Piso 1', 'habitacion' => '102']);

        $user = User::factory()->create(['area' => 'vinculacion']);
        $this->actingAs($user);

        $response = $this->from('/vinculacion/dashboard')->put('/vinculacion/habitaciones/comida', [
            'piso' => 'Piso 1',
            'habitacion' => '101',
            'recipiente' => (string) $idVisitaDeOtroCuarto,
        ]);

        $response->assertSessionHasErrors('recipiente');
    }

    public function test_update_requires_piso_and_habitacion(): void
    {
        $user = User::factory()->create(['area' => 'vinculacion']);
        $this->actingAs($user);

        $response = $this->from('/vinculacion/dashboard')->put('/vinculacion/habitaciones/comida', []);

        $response->assertSessionHasErrors(['piso', 'habitacion']);
    }

    public function test_update_is_forbidden_for_a_user_from_another_area(): void
    {
        $user = User::factory()->create(['area' => 'cafeteria']);
        $this->actingAs($user);

        $this->put('/vinculacion/habitaciones/comida', ['piso' => 'Piso 1', 'habitacion' => '101'])->assertForbidden();
    }

    public function test_destroy_removes_a_manually_added_room_from_the_dashboard(): void
    {
        $idCatalogo = $this->creaHabitacionCatalogo('Piso 2', '205');

        $user = User::factory()->create(['area' => 'vinculacion']);
        $this->actingAs($user);

        $this->post('/vinculacion/habitaciones', ['catalogo_habitacion_id' => $idCatalogo]);

        $response = $this->delete('/vinculacion/habitaciones/comida', ['piso' => 'Piso 2', 'habitacion' => '205']);

        $response->assertRedirect('/vinculacion/dashboard');
        $this->assertDatabaseHas('cafeteria_cortesias', ['piso' => 'Piso 2', 'habitacion' => '205', 'activo' => 0]);

        $dashboard = $this->get('/vinculacion/dashboard');
        $dashboard->assertViewHas('habitaciones', function ($habitaciones) {
            return $habitaciones->firstWhere('habitacion', '205') === null;
        });
    }

    public function test_destroy_refuses_to_remove_a_room_with_an_active_patient(): void
    {
        $this->creaPacienteActivo('Piso 1', '101');
        $this->creaCortesiaActiva('Piso 1', '101');

        $user = User::factory()->create(['area' => 'vinculacion']);
        $this->actingAs($user);

        $response = $this->from('/vinculacion/dashboard')->delete('/vinculacion/habitaciones/comida', [
            'piso' => 'Piso 1',
            'habitacion' => '101',
        ]);

        $response->assertSessionHasErrors('error');
        $this->assertDatabaseHas('cafeteria_cortesias', ['piso' => 'Piso 1', 'habitacion' => '101', 'activo' => 1]);
    }

    public function test_destroy_refuses_to_remove_a_room_with_an_active_visit(): void
    {
        $this->creaVisitaFamiliar(['estado' => 'activa'], ['piso' => 'Piso 2', 'habitacion' => '206']);
        $this->creaCortesiaActiva('Piso 2', '206');

        $user = User::factory()->create(['area' => 'vinculacion']);
        $this->actingAs($user);

        $response = $this->from('/vinculacion/dashboard')->delete('/vinculacion/habitaciones/comida', [
            'piso' => 'Piso 2',
            'habitacion' => '206',
        ]);

        $response->assertSessionHasErrors('error');
        $this->assertDatabaseHas('cafeteria_cortesias', ['piso' => 'Piso 2', 'habitacion' => '206', 'activo' => 1]);
    }

    public function test_destroy_requires_piso_and_habitacion(): void
    {
        $user = User::factory()->create(['area' => 'vinculacion']);
        $this->actingAs($user);

        $response = $this->from('/vinculacion/dashboard')->delete('/vinculacion/habitaciones/comida', []);

        $response->assertSessionHasErrors(['piso', 'habitacion']);
    }

    public function test_destroy_is_forbidden_for_a_user_from_another_area(): void
    {
        $user = User::factory()->create(['area' => 'cafeteria']);
        $this->actingAs($user);

        $this->delete('/vinculacion/habitaciones/comida', ['piso' => 'Piso 1', 'habitacion' => '101'])->assertForbidden();
    }
}
