<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\ConsultoriosMedicosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ConsultorioMedicoControllerTest extends TestCase
{
    use RefreshDatabase;

    private function creaMedico(string $consultorio = '108', string $nombre = 'JUAN PEREZ GARCIA'): int
    {
        return DB::table('consultorios_medicos')->insertGetId([
            'consultorio' => $consultorio,
            'nombre_medico' => $nombre,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/medicos')->assertRedirect('/login');
    }

    public function test_non_admin_user_cannot_access_medicos(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->get('/medicos')->assertForbidden();
        $this->post('/medicos', [])->assertForbidden();
    }

    public function test_admin_can_view_the_medicos_page(): void
    {
        $admin = User::factory()->admin()->create();
        $this->creaMedico('108', 'JUAN PEREZ GARCIA');
        $this->actingAs($admin);

        $response = $this->get('/medicos');

        $response->assertOk();
        $response->assertViewIs('medicos.index');
        $response->assertSee('108');
        $response->assertSee('JUAN PEREZ GARCIA');
    }

    public function test_admin_can_search_by_doctor_name(): void
    {
        $admin = User::factory()->admin()->create();
        // Nombres distintos al placeholder de ejemplo del formulario ("JUAN PEREZ
        // GARCIA"), que siempre está presente en la página y rompería assertDontSee.
        $this->creaMedico('108', 'PEDRO SANCHEZ RUIZ');
        $this->creaMedico('212', 'MARIA LOPEZ SOTO');
        $this->actingAs($admin);

        $response = $this->get('/medicos?busqueda=LOPEZ');

        $response->assertOk();
        $response->assertSee('MARIA LOPEZ SOTO');
        $response->assertDontSee('PEDRO SANCHEZ RUIZ');
    }

    public function test_admin_can_search_by_consultorio(): void
    {
        $admin = User::factory()->admin()->create();
        $this->creaMedico('108', 'PEDRO SANCHEZ RUIZ');
        $this->creaMedico('212', 'MARIA LOPEZ SOTO');
        $this->actingAs($admin);

        $response = $this->get('/medicos?busqueda=212');

        $response->assertOk();
        $response->assertSee('MARIA LOPEZ SOTO');
        $response->assertDontSee('PEDRO SANCHEZ RUIZ');
    }

    public function test_admin_can_add_a_doctor(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $response = $this->post('/medicos', [
            'consultorio' => '319',
            'nombre_medico' => 'VIOLETA GRISELDA LUNA GARCIA',
        ]);

        $response->assertRedirect('/medicos');
        $this->assertDatabaseHas('consultorios_medicos', [
            'consultorio' => '319',
            'nombre_medico' => 'VIOLETA GRISELDA LUNA GARCIA',
        ]);
    }

    public function test_a_consultorio_can_have_multiple_doctors(): void
    {
        $admin = User::factory()->admin()->create();
        $this->creaMedico('108', 'JUAN PEREZ GARCIA');
        $this->actingAs($admin);

        $response = $this->post('/medicos', [
            'consultorio' => '108',
            'nombre_medico' => 'ANA TORRES RUIZ',
        ]);

        $response->assertRedirect('/medicos');
        $this->assertSame(2, DB::table('consultorios_medicos')->where('consultorio', '108')->count());
    }

    public function test_adding_a_duplicate_doctor_on_the_same_consultorio_fails(): void
    {
        $admin = User::factory()->admin()->create();
        $this->creaMedico('108', 'JUAN PEREZ GARCIA');
        $this->actingAs($admin);

        $response = $this->from('/medicos')->post('/medicos', [
            'consultorio' => '108',
            'nombre_medico' => 'JUAN PEREZ GARCIA',
        ]);

        $response->assertSessionHasErrors('nombre_medico');
        $this->assertSame(1, DB::table('consultorios_medicos')->where('consultorio', '108')->count());
    }

    public function test_admin_can_update_a_doctor(): void
    {
        $admin = User::factory()->admin()->create();
        $id = $this->creaMedico('108', 'JUAN PEREZ GARCIA');
        $this->actingAs($admin);

        $response = $this->put("/medicos/{$id}", [
            'consultorio' => '212',
            'nombre_medico' => 'JUAN PEREZ GARCIA',
        ]);

        $response->assertRedirect('/medicos');
        $this->assertDatabaseHas('consultorios_medicos', [
            'id' => $id,
            'consultorio' => '212',
        ]);
    }

    public function test_admin_can_delete_a_doctor(): void
    {
        $admin = User::factory()->admin()->create();
        $id = $this->creaMedico();
        $this->actingAs($admin);

        $response = $this->delete("/medicos/{$id}");

        $response->assertRedirect('/medicos');
        $this->assertDatabaseMissing('consultorios_medicos', ['id' => $id]);
    }

    public function test_seeder_imports_the_provided_csv_data(): void
    {
        $total = (new ConsultoriosMedicosSeeder)->importar();

        $this->assertGreaterThan(200, $total);
        $this->assertDatabaseHas('consultorios_medicos', [
            'consultorio' => '108',
            'nombre_medico' => 'ARMANDO OTERO PEREZ',
        ]);
        $this->assertSame($total, DB::table('consultorios_medicos')->count());
    }

    public function test_seeder_is_idempotent_when_run_twice(): void
    {
        (new ConsultoriosMedicosSeeder)->importar();
        $primeraCuenta = DB::table('consultorios_medicos')->count();

        (new ConsultoriosMedicosSeeder)->importar();
        $segundaCuenta = DB::table('consultorios_medicos')->count();

        $this->assertSame($primeraCuenta, $segundaCuenta);
    }

    /**
     * VisitaController cachea consultorio→médicos por 10 minutos para la cascada
     * de /visitas/{id}/editar; si esta pantalla no invalida esa caché, un médico
     * agregado/editado/eliminado aquí tardaría hasta 10 minutos en reflejarse ahí.
     */
    public function test_creating_a_doctor_invalidates_the_cascade_cache(): void
    {
        $admin = User::factory()->admin()->create();
        Cache::put('medicos_por_consultorio', ['stale' => true], 600);
        $this->actingAs($admin);

        $this->post('/medicos', ['consultorio' => '108', 'nombre_medico' => 'NUEVO DOCTOR']);

        $this->assertNull(Cache::get('medicos_por_consultorio'));
    }

    public function test_updating_a_doctor_invalidates_the_cascade_cache(): void
    {
        $admin = User::factory()->admin()->create();
        $id = $this->creaMedico();
        Cache::put('medicos_por_consultorio', ['stale' => true], 600);
        $this->actingAs($admin);

        $this->put("/medicos/{$id}", ['consultorio' => '212', 'nombre_medico' => 'JUAN PEREZ GARCIA']);

        $this->assertNull(Cache::get('medicos_por_consultorio'));
    }

    public function test_deleting_a_doctor_invalidates_the_cascade_cache(): void
    {
        $admin = User::factory()->admin()->create();
        $id = $this->creaMedico();
        Cache::put('medicos_por_consultorio', ['stale' => true], 600);
        $this->actingAs($admin);

        $this->delete("/medicos/{$id}");

        $this->assertNull(Cache::get('medicos_por_consultorio'));
    }
}
