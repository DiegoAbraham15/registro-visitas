<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CatalogoControllerTest extends TestCase
{
    use RefreshDatabase;

    private function crearHabitacion(string $piso = 'Piso 2', string $numero = '201', int $orden = 3): int
    {
        return DB::table('catalogo_habitaciones')->insertGetId([
            'piso' => $piso,
            'piso_orden' => $orden,
            'numero' => $numero,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function crearArea(string $piso = 'Piso 1', string $nombre = 'CEYE', int $orden = 2): int
    {
        return DB::table('catalogo_areas')->insertGetId([
            'piso' => $piso,
            'piso_orden' => $orden,
            'nombre' => $nombre,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/catalogos')->assertRedirect('/login');
    }

    public function test_non_admin_user_cannot_access_catalogos(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->get('/catalogos')->assertForbidden();
        $this->post('/catalogos/habitaciones', [])->assertForbidden();
        $this->post('/catalogos/areas', [])->assertForbidden();
    }

    public function test_admin_can_view_the_catalogos_page(): void
    {
        $admin = User::factory()->admin()->create();
        $this->crearHabitacion();
        $this->crearArea();
        $this->actingAs($admin);

        $response = $this->get('/catalogos');

        $response->assertOk();
        $response->assertViewIs('catalogos.index');
        $response->assertSee('201');
        $response->assertSee('CEYE');
    }

    public function test_admin_can_create_a_habitacion_reusing_the_floor_order(): void
    {
        $admin = User::factory()->admin()->create();
        $this->crearHabitacion(orden: 3);
        $this->actingAs($admin);

        $response = $this->post('/catalogos/habitaciones', [
            'piso' => 'Piso 2',
            'numero' => '224',
        ]);

        $response->assertRedirect('/catalogos');
        $this->assertDatabaseHas('catalogo_habitaciones', [
            'piso' => 'Piso 2',
            'piso_orden' => 3,
            'numero' => '224',
        ]);
    }

    public function test_admin_can_create_a_habitacion_on_a_brand_new_floor(): void
    {
        $admin = User::factory()->admin()->create();
        $this->crearHabitacion(orden: 5);
        $this->actingAs($admin);

        $response = $this->post('/catalogos/habitaciones', [
            'piso' => 'Piso 4',
            'numero' => '401',
        ]);

        $response->assertRedirect('/catalogos');
        $this->assertDatabaseHas('catalogo_habitaciones', [
            'piso' => 'Piso 4',
            'piso_orden' => 6,
            'numero' => '401',
        ]);
    }

    public function test_creating_a_duplicate_habitacion_on_the_same_floor_fails(): void
    {
        $admin = User::factory()->admin()->create();
        $this->crearHabitacion(piso: 'Piso 2', numero: '201');
        $this->actingAs($admin);

        $response = $this->from('/catalogos')->post('/catalogos/habitaciones', [
            'piso' => 'Piso 2',
            'numero' => '201',
        ]);

        $response->assertSessionHasErrors('numero');
        $this->assertSame(1, DB::table('catalogo_habitaciones')->where('numero', '201')->count());
    }

    public function test_admin_can_update_a_habitacion(): void
    {
        $admin = User::factory()->admin()->create();
        $id = $this->crearHabitacion(piso: 'Piso 2', numero: '201');
        $this->actingAs($admin);

        $response = $this->put("/catalogos/habitaciones/{$id}", [
            'piso' => 'Piso 3',
            'numero' => '301',
        ]);

        $response->assertRedirect('/catalogos');
        $this->assertDatabaseHas('catalogo_habitaciones', [
            'id' => $id,
            'piso' => 'Piso 3',
            'numero' => '301',
        ]);
    }

    public function test_admin_can_delete_a_habitacion(): void
    {
        $admin = User::factory()->admin()->create();
        $id = $this->crearHabitacion();
        $this->actingAs($admin);

        $response = $this->delete("/catalogos/habitaciones/{$id}");

        $response->assertRedirect('/catalogos');
        $this->assertDatabaseMissing('catalogo_habitaciones', ['id' => $id]);
    }

    public function test_admin_can_create_an_area(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $response = $this->post('/catalogos/areas', [
            'piso' => 'Piso 1',
            'nombre' => 'LABORATORIO',
        ]);

        $response->assertRedirect('/catalogos');
        $this->assertDatabaseHas('catalogo_areas', [
            'piso' => 'Piso 1',
            'nombre' => 'LABORATORIO',
        ]);
    }

    public function test_admin_can_update_an_area(): void
    {
        $admin = User::factory()->admin()->create();
        $id = $this->crearArea(piso: 'Piso 1', nombre: 'CEYE');
        $this->actingAs($admin);

        $response = $this->put("/catalogos/areas/{$id}", [
            'piso' => 'Piso 1',
            'nombre' => 'CEYE RENOMBRADA',
        ]);

        $response->assertRedirect('/catalogos');
        $this->assertDatabaseHas('catalogo_areas', [
            'id' => $id,
            'nombre' => 'CEYE RENOMBRADA',
        ]);
    }

    public function test_admin_can_delete_an_area(): void
    {
        $admin = User::factory()->admin()->create();
        $id = $this->crearArea();
        $this->actingAs($admin);

        $response = $this->delete("/catalogos/areas/{$id}");

        $response->assertRedirect('/catalogos');
        $this->assertDatabaseMissing('catalogo_areas', ['id' => $id]);
    }

    public function test_updating_a_habitacion_invalidates_the_cascade_cache(): void
    {
        $admin = User::factory()->admin()->create();
        $id = $this->crearHabitacion(piso: 'Piso 2', numero: '201');
        Cache::put('catalogo_por_piso:catalogo_habitaciones:numero', ['stale' => true], 600);
        Cache::put('catalogo_habitaciones_por_piso_orden', ['stale' => true], 600);
        $this->actingAs($admin);

        $this->put("/catalogos/habitaciones/{$id}", [
            'piso' => 'Piso 2',
            'numero' => '202',
        ]);

        $this->assertNull(Cache::get('catalogo_por_piso:catalogo_habitaciones:numero'));
        $this->assertNull(Cache::get('catalogo_habitaciones_por_piso_orden'));
    }
}
